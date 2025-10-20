<?php
// faturas/api/gerar_fatura.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'Database.php';

try {
    $database = Database::getInstance();
    $pdo = $database->getConnection();

    $data = json_decode(file_get_contents("php://input"));

    if (empty($data->instalacao_id) || empty($data->mes_referencia)) {
        http_response_code(400);
        die(json_encode(['message' => 'Instalação e Mês de Referência são obrigatórios.']));
    }

    $instalacaoId = $data->instalacao_id;

    $stmtInst = $pdo->prepare("SELECT cliente_id, tipo_contrato, tipo_instalacao, regra_faturamento FROM instalacoes WHERE id = ?");
    $stmtInst->execute([$instalacaoId]);
    $instalacao = $stmtInst->fetch();

    if (!$instalacao) {
        throw new Exception("Instalação não encontrada.");
    }

    $pdo->beginTransaction();
    
    // Variáveis
    $valorFinal = 0;
    $subtotal = 0;
    $valorDesconto = 0;
    $percentualDesconto = (int)($data->percentual_desconto ?? 0);
    $consumoEmReais = (float)($data->consumo_kwh ?? 0);
    $taxaMinima = (float)($data->taxa_minima ?? 0);
    
    // Outras variáveis
    $injecaoKwh = (float)($data->injecao_kwh ?? 0);
    $creditos = (float)($data->creditos ?? 0);
    $dataLeitura = $data->data_leitura ?? null;
    $numeroDias = isset($data->numero_dias) ? (int)$data->numero_dias : null;
    $dataVencimento = $data->data_vencimento ?? null;

    if ($instalacao['tipo_contrato'] == 'Investimento') {
        if (!isset($data->consumo_kwh) || empty($data->data_leitura) || !isset($data->numero_dias) || empty($data->data_vencimento)) {
            throw new Exception("Para Investimento, Consumo, Data da Leitura, Nº de Dias e Vencimento são obrigatórios.");
        }
        
        // CORREÇÃO DA LÓGICA DE CÁLCULO
        if ($instalacao['regra_faturamento'] == 'Antes da Taxação') {
            // Subtotal é a soma de tudo antes do desconto
            $subtotal = $consumoEmReais + $taxaMinima;
            // Desconto é calculado sobre o subtotal
            $valorDesconto = $subtotal * ($percentualDesconto / 100);
            // Valor final é o subtotal menos o desconto
            $valorFinal = $subtotal - $valorDesconto;
        } else { // 'Depois da Taxação' (padrão)
            // Subtotal continua sendo a soma de tudo para clareza
            $subtotal = $consumoEmReais + $taxaMinima;
            // Desconto é calculado apenas sobre o consumo
            $valorDesconto = $consumoEmReais * ($percentualDesconto / 100);
            // Valor final é o consumo com desconto, mais a taxa mínima
            $valorFinal = ($consumoEmReais - $valorDesconto) + $taxaMinima;
        }

    } else { // Lógica para Monitoramento
        if (!isset($data->valor_total) || empty($data->data_vencimento) || empty($data->data_leitura)) {
             throw new Exception("Para Monitoramento, Valor da Fatura, Vencimento e Data da Leitura são obrigatórios.");
        }
        $valorFinal = (float)($data->valor_total ?? 0);
        $subtotal = $valorFinal;
    }

    // Grava a leitura do medidor
    $stmtLeitura = $pdo->prepare(
        "INSERT INTO leituras_medidor (instalacao_id, mes_referencia, consumo_kwh, injecao_kwh, data_leitura, numero_dias) 
         VALUES (?, ?, ?, ?, ?, ?) 
         ON DUPLICATE KEY UPDATE consumo_kwh = VALUES(consumo_kwh), injecao_kwh = VALUES(injecao_kwh), data_leitura = VALUES(data_leitura), numero_dias = VALUES(numero_dias)"
    );
    $stmtLeitura->execute([$instalacaoId, $data->mes_referencia . '-01', $consumoEmReais, $injecaoKwh, $dataLeitura, $numeroDias]);

    // Insere a fatura principal
    $stmtFatura = $pdo->prepare(
        "INSERT INTO faturas (cliente_id, instalacao_id, mes_referencia, data_emissao, data_vencimento, valor_total, subtotal, valor_desconto, status, taxa_minima, percentual_desconto, creditos) 
         VALUES (?, ?, ?, CURDATE(), ?, ?, ?, ?, 'pendente', ?, ?, ?)"
    );
    
    $stmtFatura->execute([
        $instalacao['cliente_id'], $instalacaoId, $data->mes_referencia . '-01',
        $dataVencimento, $valorFinal, $subtotal, $valorDesconto,
        $taxaMinima, $percentualDesconto, $creditos
    ]);
    $faturaId = $pdo->lastInsertId();
    
    if ($instalacao['tipo_contrato'] == 'Monitoramento') {
        $stmtItem = $pdo->prepare("INSERT INTO fatura_itens (fatura_id, descricao, valor_total_item) VALUES (?, ?, ?)");
        $stmtItem->execute([$faturaId, 'Serviços de Monitoramento de Energia Solar', $valorFinal]);
    }

    $pdo->commit();

    http_response_code(201);
    echo json_encode(['message' => 'Fatura gerada com sucesso!', 'fatura_id' => $faturaId]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    die(json_encode(['message' => 'Erro ao gerar fatura.', 'details' => $e->getMessage()]));
}
?>