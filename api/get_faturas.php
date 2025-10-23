<?php
// faturas/api/get_faturas.php
require_once 'cors.php';
header("Content-Type: application/json; charset=UTF-8");
require_once 'Database.php';

try {
    $database = Database::getInstance();
    $pdo = $database->getConnection();
    
    $clienteId = isset($_GET['cliente_id']) ? (int)$_GET['cliente_id'] : null;

    if (!$clienteId) {
        http_response_code(400);
        echo json_encode(['message' => 'O ID do cliente é obrigatório.']);
        exit();
    }

    // Query 1: Buscar o nome do cliente e integrador_id
    $stmtCliente = $pdo->prepare("SELECT nome, integrador_id FROM clientes WHERE id = ?");
    $stmtCliente->execute([$clienteId]);
    $cliente = $stmtCliente->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        http_response_code(404);
        echo json_encode(['message' => 'Cliente não encontrado.']);
        exit();
    }

    // Query 2: Buscar as faturas
    // (A sua query original está aqui)
    $stmtFaturas = $pdo->prepare("
        SELECT f.id, i.codigo_uc, f.mes_referencia, f.valor_total, f.status, f.data_vencimento
        FROM faturas f
        JOIN instalacoes i ON f.instalacao_id = i.id
        WHERE i.cliente_id = ?
        ORDER BY f.mes_referencia DESC
    ");
    $stmtFaturas->execute([$clienteId]);
    $faturas = $stmtFaturas->fetchAll(PDO::FETCH_ASSOC);

    // --- LÓGICA DE VENCIMENTO AUTOMÁTICO ---
    $hoje = new DateTime('today'); // Pega a data de hoje, sem a hora
    
    foreach ($faturas as $key => $fatura) {
        $dataVencimento = new DateTime($fatura['data_vencimento']);
        
        // Se estiver "Pendente" E a data de vencimento for anterior a hoje
        if ($fatura['status'] === 'Pendente' && $dataVencimento < $hoje) {
            $faturas[$key]['status'] = 'Vencida';
        }
    }
    // --- FIM DA LÓGICA ---

    $resultado = [
        'cliente_nome' => $cliente['nome'],
        'integrador_id' => $cliente['integrador_id'],
        'faturas' => $faturas
    ];

    http_response_code(200);
    echo json_encode($resultado);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Erro ao listar faturas.', 'details' => $e->getMessage()]);
}
?>