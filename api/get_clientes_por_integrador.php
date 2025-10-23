<?php
// faturas/api/get_clientes_por_integrador.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'cors.php'; // Gerencia CORS
require_once 'Database.php';

// Validar o ID do integrador
$integrador_id = filter_input(INPUT_GET, 'integrador_id', FILTER_VALIDATE_INT);
if (!$integrador_id) {
    http_response_code(400);
    echo json_encode(['message' => 'ID do integrador inválido.']);
    exit();
}

try {
    $database = Database::getInstance();
    $pdo = $database->getConnection();

    /*
     * MODIFICADO:
     * 1. Seleciona os dados do cliente (id, nome, documento).
     * 2. Usa LEFT JOIN para incluir clientes mesmo que não tenham instalações (COUNT será 0).
     * 3. Conta (COUNT(i.id)) o número de instalações para cada cliente.
     * 4. Filtra clientes pelo 'integrador_id' na tabela 'clientes'.
     * 5. Agrupa por cliente para retornar uma linha por cliente.
    */
    $sql = "SELECT
                c.id AS cliente_id,
                c.nome,
                c.documento,
                COUNT(i.id) AS total_instalacoes
            FROM
                clientes c
            LEFT JOIN
                instalacoes i ON c.id = i.cliente_id
            WHERE
                c.integrador_id = ?
            GROUP BY
                c.id, c.nome, c.documento
            ORDER BY
                c.nome";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$integrador_id]);
    
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode($clientes);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Erro ao buscar clientes.', 'details' => $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Erro no servidor.', 'details' => $e->getMessage()]);
}
?>