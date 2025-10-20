<?php
// faturas/api/integrador.php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
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

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['message' => 'Método não permitido. Utilize POST.']);
        exit();
    }

    $data = json_decode(file_get_contents("php://input"));

    if (empty($data->nome_do_integrador) || empty($data->numero_de_contato)) {
        http_response_code(400);
        echo json_encode(['message' => 'Nome e numero de contato são obrigatórios.']);
        exit();
    }

    $sql = "INSERT INTO integradores (nome_do_integrador, numero_de_contato) VALUES (?, ?)";
    $stmt = $pdo->prepare($sql);

    $stmt->execute([$data->nome_do_integrador, $data->numero_de_contato]);
    $lastId = $pdo->lastInsertId();
    
    http_response_code(201);
    echo json_encode([
        'id' => $lastId,
        'message' => 'Registro Integrador inserido com sucesso!'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Erro interno ao inserir dados no DB.', 'details' => $e->getMessage()]);
}
?>