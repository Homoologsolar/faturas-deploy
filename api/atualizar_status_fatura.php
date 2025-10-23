<?php
// faturas/api/atualizar_status_fatura.php
require_once 'cors.php';
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Resposta pré-voo OPTIONS
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'Database.php';

try {
    $database = Database::getInstance();
    $pdo = $database->getConnection();
    
    // Pega o corpo da requisição (JSON)
    $data = json_decode(file_get_contents("php://input"));

    if (empty($data->fatura_id) || empty($data->novo_status)) {
        http_response_code(400);
        echo json_encode(['message' => 'ID da fatura e novo status são obrigatórios.']);
        exit();
    }

    $faturaId = (int)$data->fatura_id;
    $novoStatus = htmlspecialchars(strip_tags($data->novo_status));

    // Validação simples de status
    $statusPermitidos = ['Pendente', 'Pago', 'Vencida', 'Cancelada'];
    if (!in_array($novoStatus, $statusPermitidos)) {
        http_response_code(400);
        echo json_encode(['message' => 'Status inválido.']);
        exit();
    }

    $stmt = $pdo->prepare("UPDATE faturas SET status = ? WHERE id = ?");
    
    if ($stmt->execute([$novoStatus, $faturaId])) {
        http_response_code(200);
        echo json_encode(['message' => 'Status da fatura atualizado com sucesso.']);
    } else {
        throw new Exception('Não foi possível atualizar o status da fatura.');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Erro ao atualizar status da fatura.', 'details' => $e->getMessage()]);
}
?>