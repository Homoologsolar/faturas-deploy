<?php
// faturas/api/get_instalacoes_por_cliente.php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
require_once 'Database.php';

try {
    $database = Database::getInstance();
    $pdo = $database->getConnection();
    
    if (empty($_GET['cliente_id'])) {
        http_response_code(400);
        echo json_encode(['message' => 'O ID do cliente é obrigatório.']);
        exit();
    }

    // CORREÇÃO: Adicionada a coluna regra_faturamento
    $stmt = $pdo->prepare("
        SELECT id, codigo_uc, endereco_instalacao, tipo_contrato, tipo_instalacao, regra_faturamento
        FROM instalacoes 
        WHERE cliente_id = ?
        ORDER BY id
    ");
    $stmt->execute([$_GET['cliente_id']]);
    $instalacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($instalacoes);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Erro ao listar instalações.', 'details' => $e->getMessage()]);
}
?>