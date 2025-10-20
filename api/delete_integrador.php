<?php
// faturas/api/delete_integrador.php

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-with");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 1. Usa a classe de conexão centralizada
require_once 'Database.php';

try {
    $database = Database::getInstance();
    $pdo = $database->getConnection();

    if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
        http_response_code(405);
        die(json_encode(['message' => 'Método não permitido. Utilize DELETE.']));
    }

    if (empty($_GET['id'])) {
        http_response_code(400);
        die(json_encode(['message' => 'ID do integrador é obrigatório.']));
    }

    $id = $_GET['id'];
    $sql = "DELETE FROM integradores WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() > 0) {
        http_response_code(200);
        echo json_encode(['message' => 'Integrador deletado com sucesso!']);
    } else {
        http_response_code(404);
        echo json_encode(['message' => 'Integrador não encontrado ou já deletado.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    // 2. Adiciona uma verificação específica para erro de chave estrangeira
    if ($e->getCode() == '23000') {
        // SQLSTATE[23000] é o código para violação de integridade (como chave estrangeira)
        echo json_encode(['message' => 'Erro: Este integrador não pode ser excluído pois possui clientes vinculados a ele.']);
    } else {
        echo json_encode(['message' => 'Erro ao deletar integrador no banco de dados.', 'details' => $e->getMessage()]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Erro no servidor.', 'details' => $e->getMessage()]);
}
?>