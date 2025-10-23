<?php
// faturas/api/listar_integradores.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'cors.php'; // Gerencia CORS
require_once 'Database.php';

try {
    $database = Database::getInstance();
    $pdo = $database->getConnection();

    /*
     * MODIFICADO:
     * 1. Seleciona os dados do integrador (i.*).
     * 2. Usa LEFT JOIN para incluir integradores mesmo que não tenham clientes (COUNT será 0).
     * 3. Conta (COUNT(c.id)) o número de clientes vinculados.
     * 4. Agrupa por integrador para retornar uma linha por integrador.
    */
    $sql = "SELECT
                i.id,
                i.nome_do_integrador,
                i.numero_de_contato,
                COUNT(c.id) AS total_clientes
            FROM
                integradores i
            LEFT JOIN
                clientes c ON i.id = c.integrador_id
            GROUP BY
                i.id, i.nome_do_integrador, i.numero_de_contato
            ORDER BY
                i.nome_do_integrador";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    $integradores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode($integradores);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Erro ao buscar integradores.', 'details' => $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Erro no servidor.', 'details' => $e->getMessage()]);
}
?>