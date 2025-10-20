<?php
// faturas/api/atualizar_status_fatura.php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Usa a classe de conexão centralizada
require_once 'Database.php';

try {
    $database = Database::getInstance();
    $pdo = $database->getConnection();

    $data = json_decode(file_get_contents("php://input"));

    if (empty($data->fatura_id) || empty($data->status)) {
        http_response_code(400);
        die(json_encode(['message' => 'ID da fatura e novo status são obrigatórios.']));
    }

    $allowed_statuses = ['pendente', 'paga', 'vencida', 'cancelada'];
    if (!in_array($data->status, $allowed_statuses)) {
        http_response_code(400);
        die(json_encode(['message' => 'Status inválido.']));
    }

    $stmt = $pdo->prepare("UPDATE faturas SET status = ? WHERE id = ?");
    $stmt->execute([$data->status, $data->fatura_id]);

    http_response_code(200);
    echo json_encode(['message' => 'Status da fatura atualizado com sucesso!']);

} catch (Exception $e) {
    http_response_code(500);
    // Agora envia uma mensagem mais detalhada para o frontend em caso de erro
    echo json_encode(['message' => 'Erro ao atualizar o status.', 'details' => $e->getMessage()]);
}
?>