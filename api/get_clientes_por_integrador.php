<?php
// faturas/api/get_clientes_por_integrador.php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once 'Database.php';

try {
    $database = Database::getInstance();
    $pdo = $database->getConnection();

    if (empty($_GET['integrador_id'])) {
        http_response_code(400);
        echo json_encode(['message' => 'O ID do integrador é obrigatório.']);
        exit();
    }
    
    $integradorId = $_GET['integrador_id'];
    
    // CORREÇÃO: Adicionada a coluna i.regra_faturamento
    $sql = "SELECT 
                c.id as cliente_id, c.nome, i.id, i.codigo_uc,
                i.tipo_contrato, i.tipo_instalacao, i.regra_faturamento
            FROM clientes c
            JOIN instalacoes i ON c.id = i.cliente_id
            WHERE i.integrador_id = ?";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$integradorId]);
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode($clientes);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Erro ao buscar clientes.', 'details' => $e->getMessage()]);
}
?>