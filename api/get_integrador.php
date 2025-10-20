<?php
// faturas/api/get_integrador.php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'Database.php';

try {
    $database = Database::getInstance();
    $pdo = $database->getConnection();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['message' => 'Método não permitido. Utilize GET.']);
        exit();
    }

    if (empty($_GET['id'])) {
        http_response_code(400);
        echo json_encode(['message' => 'O ID do integrador é obrigatório.']);
        exit();
    }

    $id = $_GET['id'];
    
    $stmt = $pdo->prepare("SELECT id, nome_do_integrador, numero_de_contato FROM integradores WHERE id = ?");
    $stmt->execute([$id]);
    
    $integrador = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($integrador) {
        http_response_code(200);
        echo json_encode($integrador);
    } else {
        http_response_code(404);
        echo json_encode(['message' => 'Integrador não encontrado.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Erro no servidor.', 'details' => $e->getMessage()]);
}
?>