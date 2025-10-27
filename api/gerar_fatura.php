<?php
// faturas/api/gerar_fatura.php
require_once 'cors.php';
require_once 'Database.php';

session_start();
// Descomente esta seção se você tiver um sistema de login
/*
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['message' => 'Acesso não autorizado.']);
    exit;
}
*/

try {
    $data = json_decode(file_get_contents("php://input"));

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON inválido.');
    }

    // --- 1. VALIDAÇÃO ESSENCIAL ---
    if (empty($data->instalacao_id) || empty($data->mes_referencia) || empty($data->data_vencimento) || empty($data->data_leitura)) {
        throw new Exception('Campos obrigatórios (instalação, mês referência, vencimento, data leitura) não foram preenchidos.');
    }

    $db = Database::getInstance()->getConnection();

    // --- 2. BUSCAR DADOS DA INSTALAÇÃO (CLIENTE_ID, REGRA, ETC) ---
    $stmt = $db->prepare("SELECT cliente_id, tipo_contrato, regra_faturamento FROM instalacoes WHERE id = :instalacao_id");
    $stmt->execute([':instalacao_id' => $data->instalacao_id]);
    $instalacao = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$instalacao) {
        throw new Exception('Instalação não encontrada.');
    }

    $cliente_id = $instalacao['cliente_id'];

    // --- 3. DEFINIR VARIÁVEIS DA LEITURA E CÁLCULO DE CRÉDITO ---
    // Define valores padrão 0 caso não venham do front-end
    $consumo_kwh = $data->consumo_kwh ?? 0;
    $injecao_kwh = $data->injecao_kwh ?? 0;
    $numero_dias = $data->numero_dias ?? null;
    $data_leitura = $data->data_leitura;
    
    // =================================================================
    // LÓGICA SOLICITADA: Calcula o crédito gerado/consumido NESTA fatura
    // Este é o "delta" (variação) do mês.
    $creditos_do_mes = $injecao_kwh - $consumo_kwh;
    // =================================================================

    // --- 4. CÁLCULO DO VALOR TOTAL (RECALCULAR NO BACKEND) ---
    // É uma boa prática recalcular no backend para segurança
    $valor_total = $data->valor_total ?? 0;
    $subtotal = 0;
    $valor_desconto = 0;
    $taxa_minima = $data->taxa_minima ?? 0;
    $percentual_desconto = $data->percentual_desconto ?? 0;

    if ($instalacao['tipo_contrato'] === 'Investimento') {
        if ($instalacao['regra_faturamento'] === 'Antes da Taxação') {
            $subtotal = $consumo_kwh + $taxa_minima;
            $valor_desconto = $subtotal * ($percentual_desconto / 100);
            $valor_total = $subtotal - $valor_desconto;
        } else { // 'Depois da Taxação' ou 'geracao'
            $subtotal = $consumo_kwh; // Subtotal é o consumo antes da taxa
            $valor_desconto = $consumo_kwh * ($percentual_desconto / 100);
            $valor_total = ($consumo_kwh - $valor_desconto) + $taxa_minima;
        }
    }
    // Se for 'Monitoramento', o $valor_total já veio pronto do $data->valor_total

    // --- 5. INICIAR TRANSAÇÃO ---
    $db->beginTransaction();

    // --- 6. INSERIR LEITURA NA 'leituras_medidor' ---
    // Se você implementou o Trigger da resposta anterior,
    // ele será disparado AQUI e atualizará o 'saldo_creditos_kwh' em 'instalacoes'.
    $stmtLeitura = $db->prepare(
        "INSERT INTO leituras_medidor (instalacao_id, mes_referencia, consumo_kwh, injecao_kwh, data_leitura, numero_dias)
         VALUES (:instalacao_id, :mes_referencia, :consumo_kwh, :injecao_kwh, :data_leitura, :numero_dias)
         ON DUPLICATE KEY UPDATE 
         consumo_kwh = VALUES(consumo_kwh), 
         injecao_kwh = VALUES(injecao_kwh), 
         data_leitura = VALUES(data_leitura), 
         numero_dias = VALUES(numero_dias)"
    );
    $stmtLeitura->execute([
        ':instalacao_id' => $data->instalacao_id,
        ':mes_referencia' => $data->mes_referencia . '-01', // Garante que é o dia 1
        ':consumo_kwh' => $consumo_kwh,
        ':injecao_kwh' => $injecao_kwh,
        ':data_leitura' => $data_leitura,
        ':numero_dias' => $numero_dias
    ]);

    // --- 7. INSERIR FATURA NA 'faturas' ---
    // Agora incluindo o valor na coluna 'creditos'
    $stmtFatura = $db->prepare(
        "INSERT INTO faturas (cliente_id, instalacao_id, mes_referencia, data_emissao, data_vencimento, 
         valor_total, subtotal, valor_desconto, taxa_minima, valor_consumo, percentual_desconto, creditos, status)
         VALUES (:cliente_id, :instalacao_id, :mes_referencia, CURDATE(), :data_vencimento, 
         :valor_total, :subtotal, :valor_desconto, :taxa_minima, :valor_consumo, :percentual_desconto, :creditos, 'pendente')"
    );
    
    $stmtFatura->execute([
        ':cliente_id' => $cliente_id,
        ':instalacao_id' => $data->instalacao_id,
        ':mes_referencia' => $data->mes_referencia . '-01',
        ':data_vencimento' => $data->data_vencimento,
        ':valor_total' => $valor_total,
        ':subtotal' => $subtotal,
        ':valor_desconto' => $valor_desconto,
        ':taxa_minima' => $taxa_minima,
        ':valor_consumo' => $consumo_kwh, // Armazena o consumo em Kwh
        ':percentual_desconto' => $percentual_desconto,
        ':creditos' => $creditos_do_mes // <--- AQUI ESTÁ A LÓGICA SOLICITADA
    ]);

    // --- 8. COMMIT ---
    $db->commit();

    http_response_code(201);
    echo json_encode(['message' => 'Fatura e leitura geradas com sucesso!']);

} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['message' => 'Erro no banco de dados: ' . $e->getMessage()]);
} catch (Exception $e) {
    // Apenas chame rollBack se a transação foi iniciada
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(400);
    echo json_encode(['message' => $e->getMessage()]);
}
?>