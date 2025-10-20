<?php
// faturas/api/get_detalhes_fatura.php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once 'Database.php';

try {
    $database = Database::getInstance();
    $pdo = $database->getConnection();
    
    if (empty($_GET['fatura_id'])) {
        http_response_code(400);
        die(json_encode(['message' => 'O ID da fatura é obrigatório.']));
    }

    $faturaId = $_GET['fatura_id'];
    $response = [];

    // CORREÇÃO: Adicionada a coluna "f.cliente_id" à consulta
    $stmt = $pdo->prepare("
        SELECT 
            f.id, f.mes_referencia, f.data_emissao, f.data_vencimento, f.valor_total, f.status,
            f.taxa_minima, f.percentual_desconto, f.subtotal, f.valor_desconto, f.cliente_id,
            c.nome as cliente_nome, c.documento as cliente_documento,
            i.codigo_uc, i.endereco_instalacao, i.tipo_de_ligacao, i.tipo_contrato,
            lm.consumo_kwh, lm.injecao_kwh, lm.data_leitura
        FROM faturas f
        JOIN clientes c ON f.cliente_id = c.id
        JOIN instalacoes i ON f.instalacao_id = i.id
        LEFT JOIN leituras_medidor lm ON f.instalacao_id = lm.instalacao_id AND f.mes_referencia = lm.mes_referencia
        WHERE f.id = ?
    ");
    $stmt->execute([$faturaId]);
    $fatura = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$fatura) {
        http_response_code(404);
        die(json_encode(['message' => 'Fatura não encontrada.']));
    }
    $response['fatura'] = $fatura;

    $stmtItens = $pdo->prepare("SELECT * FROM fatura_itens WHERE fatura_id = ? ORDER BY id");
    $stmtItens->execute([$faturaId]);
    $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);
    $response['itens'] = $itens;

    http_response_code(200);
    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    die(json_encode(['message' => 'Erro ao buscar detalhes da fatura.', 'details' => $e->getMessage()]));
}
?>