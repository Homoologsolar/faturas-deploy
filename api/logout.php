<?php
// faturas/api/logout.php

// This ensures PHP can find and manage the correct session
session_start();

// Include the central CORS handler to allow the request
require_once 'cors.php';

// Remove all session variables
$_SESSION = array();

// If you want to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session.
session_destroy();

http_response_code(200);
echo json_encode(['message' => 'Logout bem-sucedido.']);
?>