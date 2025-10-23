<?php
// faturas/api/cadastrar_cliente.php
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

    // Validação SIMPLIFICADA (Apenas campos do cliente)
    if (
        empty($data->nome) ||
        empty($data->documento) ||
        empty($data->integrador_id) // Esta coluna é da tabela 'clientes' no novo schema
    ) {
        http_response_code(400);
        echo json_encode(['message' => 'Campos obrigatórios (Nome, Documento e Integrador) devem ser preenchidos.']);
        exit();
    }

    // 1. Inserção na tabela 'clientes' (CORRIGIDO para cadastro separado)
    // Insere apenas os dados do cliente
    $sqlCliente = "INSERT INTO clientes (
                        nome, documento, telefone, integrador_id
                   ) VALUES (?, ?, ?, ?)";
    $stmtCliente = $pdo->prepare($sqlCliente);
    $stmtCliente->execute([
        $data->nome, 
        $data->documento, 
        $data->telefone ?? null,
        $data->integrador_id
    ]);
    
    $clienteId = $pdo->lastInsertId();

    // A lógica de inserir na tabela 'instalacoes' foi REMOVIDA
    // Isso será feito pelo seu outro script (cadastrar_instalacao.php)

    http_response_code(201);
    echo json_encode([
        'message' => 'Cliente cadastrado com sucesso!',
        'cliente_id' => $clienteId
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    
    if ($e->getCode() == 23000) {
        // O erro 23000 (chave duplicada) agora só pode ser no 'documento'
        if (strpos($e->getMessage(), 'documento') !== false) {
             echo json_encode(['message' => 'Erro: Documento (CPF/CNPJ) já existe no sistema.']);
        } else {
             echo json_encode(['message' => 'Erro: Violação de chave única. Verifique os dados.']);
        }
    } else {
        echo json_encode(['message' => 'Erro interno ao cadastrar cliente.', 'details' => $e->getMessage()]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Erro no servidor.', 'details' => $e->getMessage()]);
}
?>