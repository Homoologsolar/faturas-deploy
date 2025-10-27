<?php
// faturas/api/get_detalhes_fatura.php
require_once 'cors.php';
header("Content-Type: application/json; charset=UTF-8");
require_once 'Database.php';

try {
    $database = Database::getInstance();
    $pdo = $database->getConnection();
    
    $faturaId = isset($_GET['fatura_id']) ? (int)$_GET['fatura_id'] : 0;

    if ($faturaId <= 0) {
        http_response_code(400);
        echo json_encode(['message' => 'O ID da fatura é obrigatório.']);
        exit();
    }

    // Query 1: (f.*) - Esta query funciona, pois f.* pega os nomes corretos.
    $stmt = $pdo->prepare("
       SELECT 
            f.*, 
            lm.consumo_kwh AS consumo_kwh_registrado,
            lm.data_leitura,
            lm.numero_dias,
            lm.injecao_kwh,
            i.id as instalacao_id_para_historico,
            i.codigo_uc, i.endereco_instalacao, i.tipo_contrato, i.tipo_instalacao, i.regra_faturamento,
            c.nome as cliente_nome, c.documento as cliente_documento,
            ig.nome_do_integrador
        FROM faturas f
        JOIN instalacoes i ON f.instalacao_id = i.id
        JOIN clientes c ON i.cliente_id = c.id
        JOIN integradores ig ON c.integrador_id = ig.id
        LEFT JOIN leituras_medidor lm ON f.instalacao_id = lm.instalacao_id AND f.mes_referencia = lm.mes_referencia
        WHERE f.id = ?
    ");
    $stmt->execute([$faturaId]);
    $fatura = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$fatura) {
        http_response_code(404);
        echo json_encode(['message' => 'Fatura não encontrada.']);
        exit();
    }

    // --- LÓGICA DE VENCIMENTO AUTOMÁTICO ---
    $hoje = new DateTime('today');
    $dataVencimento = new DateTime($fatura['data_vencimento']);
    if ($fatura['status'] === 'Pendente' && $dataVencimento < $hoje) {
        $fatura['status'] = 'Vencida';
    }

    // --- NOVA QUERY: Buscar o Histórico de Consumo ---
    // A tabela que armazena leituras é `leituras_medidor` (inserida em gerar_fatura.php).
    // Usamos a coluna `consumo_kwh` como a métrica de consumo para o histórico.
    $instalacaoId = $fatura['instalacao_id_para_historico'];

    $stmtHistorico = $pdo->prepare("
        SELECT id, mes_referencia, consumo_kwh as consumo
        FROM leituras_medidor
        WHERE instalacao_id = ?
        ORDER BY mes_referencia DESC
        LIMIT 12
    ");
    $stmtHistorico->execute([$instalacaoId]);
    $historico = $stmtHistorico->fetchAll(PDO::FETCH_ASSOC);

    // --- NOVO RESULTADO COMBINADO ---
    $resultado = [
        'fatura_detalhes' => $fatura,
        'historico_consumo' => $historico
    ];

    http_response_code(200);
    echo json_encode($resultado); 

} catch (Exception $e) {
    http_response_code(500);
    // Retorna o erro real do PHP/SQL para depuração
    echo json_encode(['message' => 'Erro ao buscar detalhes da fatura.', 'details' => $e->getMessage()]);
}
?>