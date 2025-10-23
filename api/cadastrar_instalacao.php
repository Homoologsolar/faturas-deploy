<?php
// faturas/api/cadastrar_instalacao.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'Database.php';

try {
    $database = Database::getInstance();
    $pdo = $database->getConnection();
    
    $data = json_decode(file_get_contents("php://input"));

    // Validação para os campos da INSTALAÇÃO
    if (
        empty($data->cliente_id) || // ID do cliente existente
        empty($data->integrador_id) || // ID do integrador
        empty($data->codigo_uc) || 
        empty($data->endereco_instalacao) ||
        empty($data->tipo_instalacao)
    ) {
        http_response_code(400);
        echo json_encode(['message' => 'Todos os campos da instalação são obrigatórios (Cliente, Integrador, Código UC, Endereço).']);
        exit();
    }

    $pdo->beginTransaction();

    // Inserção APENAS na tabela 'instalacoes'
    $sqlInstalacao = "INSERT INTO instalacoes (
                        cliente_id, integrador_id, codigo_uc, endereco_instalacao, tipo_de_ligacao, 
                        tipo_contrato, tipo_instalacao, regra_faturamento
                      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmtInstalacao = $pdo->prepare($sqlInstalacao);
    $stmtInstalacao->execute([
        $data->cliente_id, 
        $data->integrador_id, 
        $data->codigo_uc, 
        $data->endereco_instalacao, 
        $data->tipo_de_ligacao ?? 'Monofásica',
        'Investimento', // Valor fixo
        $data->tipo_instalacao ?? 'Beneficiária',
        $data->regra_faturamento ?? 'Depois da Taxação'
    ]);

    $pdo->commit();

    http_response_code(201);
    echo json_encode(['message' => 'Instalação cadastrada com sucesso para o cliente!']);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    if ($e->getCode() == 23000) {
        // Agora o erro de duplicidade é o Código da UC
        echo json_encode(['message' => 'Erro: Código da UC já existe no sistema.']);
    } else {
        echo json_encode(['message' => 'Erro interno ao cadastrar instalação.', 'details' => $e->getMessage()]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Erro no servidor.', 'details' => $e->getMessage()]);
}
?>