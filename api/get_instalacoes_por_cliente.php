<?php
// faturas/api/get_instalacoes_por_cliente.php
require_once 'cors.php'; // Usa o arquivo de CORS centralizado
header("Content-Type: application/json; charset=UTF-8");
require_once 'Database.php';

try {
    $database = Database::getInstance();
    $pdo = $database->getConnection();
    
    if (empty($_GET['cliente_id'])) {
        http_response_code(400);
        echo json_encode(['message' => 'O ID do cliente é obrigatório.']);
        exit();
    }

    // Linha 17 (aproximadamente)
    $clienteId = (int)$_GET['cliente_id'];

    // --- CORREÇÃO 1: Buscar o 'nome' E 'integrador_id' ---
    $stmtCliente = $pdo->prepare("SELECT nome, integrador_id FROM clientes WHERE id = ?");
    $stmtCliente->execute([$clienteId]);
    $cliente = $stmtCliente->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        http_response_code(404);
        echo json_encode(['message' => 'Cliente não encontrado.']);
        exit();
    }

    // Query 2: Buscar as instalações (esta parte estava correta)
    $stmt = $pdo->prepare("
        SELECT id, codigo_uc, endereco_instalacao, tipo_contrato, tipo_instalacao, regra_faturamento
        FROM instalacoes 
        WHERE cliente_id = ?
        ORDER BY id
    ");
    $stmt->execute([$clienteId]);
    $instalacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- CORREÇÃO 2: Adicionar o 'integrador_id' ao resultado ---
    $resultado = [
        'cliente_nome' => $cliente['nome'],
        'integrador_id' => $cliente['integrador_id'], // <-- Adicionado
        'instalacoes' => $instalacoes
    ];

    http_response_code(200);
    echo json_encode($resultado);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Erro ao listar instalações.', 'details' => $e->getMessage()]);
}
?>