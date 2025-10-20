<?php
// faturas/api/check_session.php

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

if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    http_response_code(200);
    echo json_encode(['loggedIn' => true, 'usuario' => $_SESSION['usuario']]);
} else {
    http_response_code(200);
    echo json_encode(['loggedIn' => false]);
}
?>