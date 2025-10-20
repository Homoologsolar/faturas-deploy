<?php
// faturas/api/login.php

// Bloco de depuração e CORS no topo de tudo
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}
// Fim do bloco

session_start();
header("Content-Type: application/json; charset=UTF-8");

require_once 'Database.php';

try {
    $database = Database::getInstance();
    $pdo = $database->getConnection();
    
    $data = json_decode(file_get_contents("php://input"));

    if (empty($data->usuario) || empty($data->senha)) {
        http_response_code(400);
        die(json_encode(['message' => 'Usuário e senha são obrigatórios.']));
    }

    $stmt = $pdo->prepare("SELECT * FROM administradores WHERE usuario = ?");
    $stmt->execute([$data->usuario]);
    $user = $stmt->fetch();

    if ($user && password_verify($data->senha, $user['senha'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['usuario'] = $user['usuario'];
        
        http_response_code(200);
        echo json_encode(['message' => 'Login bem-sucedido.', 'usuario' => $user['usuario']]);
    } else {
        http_response_code(401);
        echo json_encode(['message' => 'Usuário ou senha inválidos.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    die(json_encode(['message' => 'Erro no servidor.', 'details' => $e->getMessage()]));
}
?>