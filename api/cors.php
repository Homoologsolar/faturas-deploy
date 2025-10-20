<?php
// faturas/api/cors.php

// Define a origem permitida (sua aplicação React)
header("Access-Control-Allow-Origin: http://localhost:5173");

// Permite o envio de credenciais (necessário para o sistema de login)
header("Access-Control-Allow-Credentials: true");

// Define os métodos HTTP permitidos
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");

// Define os cabeçalhos permitidos na requisição
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Responde imediatamente com sucesso a requisições de "pre-flight" (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}
?>