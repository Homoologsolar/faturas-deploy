<?php
// faturas/api/listar_integradores.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");

// ADICIONE ESTAS 3 LINHAS PARA DESABILITAR O CACHE
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once 'Database.php';

try {
    $database = Database::getInstance();
    $pdo = $database->getConnection();

    $query = "
        SELECT 
            i.id, 
            i.nome_do_integrador, 
            i.numero_de_contato,
            COUNT(DISTINCT inst.cliente_id) as client_count
        FROM 
            integradores i
        LEFT JOIN 
            instalacoes inst ON i.id = inst.integrador_id
        GROUP BY 
            i.id, i.nome_do_integrador, i.numero_de_contato
        ORDER BY 
            i.id DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $integradores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode($integradores);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Erro no servidor.', 'details' => $e->getMessage()]);
}
?>