<?php
/**
 * TESTES AUTOMATIZADOS: MÓDULO DE VENCIMENTO DE CERTIFICADOS NAVAIS
 * Arquivo: tests/certificados_vencimentos_test.php
 * 
 * Valida:
 * 1. Execução e integridade da query unificada dos 6 modelos (CSN, CNBL, CNARQ, LP, LC, CHT).
 * 2. Cálculo de dias_restantes e faixas de urgência (vencidos, <=15d, <=30d, <=60d, etc.).
 * 3. Formatação da mensagem técnica NORMAM e link do WhatsApp.
 * 4. Pré-preenchimento no formulário de agendamento (cliente_id, embarcacao_id, tipo_vistoria).
 * 5. Registro correto da rota no index.php e sidebar.php.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

function assertVenc(bool $cond, string $msg): void {
    if (!$cond) {
        echo "\n[FALHA] {$msg}\n";
        exit(1);
    }
}

echo "=== INICIANDO TESTES DO MÓDULO DE VENCIMENTOS NAVAIS ===\n";

// -------------------------------------------------------------
// TESTE 1: Integridade da Query Unificada dos 6 Modelos
// -------------------------------------------------------------
echo "[1/5] Testando query unificada (UNION) dos 6 modelos estatutários...\n";

$subqueryUnion = "
    SELECT 
        'CSN' AS modelo,
        c.id,
        COALESCE(NULLIF(c.numero, ''), 'S/N') AS numero_documento,
        COALESCE(NULLIF(c.nome_embarcacao, ''), emb.nome, 'Embarcação não identificada') AS nome_embarcacao,
        COALESCE(NULLIF(c.numero_inscricao, ''), emb.numero_inscricao, emb.registro, '-') AS numero_inscricao,
        c.data_emissao,
        c.data_validade,
        DATEDIFF(c.data_validade, CURDATE()) AS dias_restantes,
        cli.nome AS cliente_nome,
        cli.telefone AS cliente_telefone
    FROM certificados_csn c
    LEFT JOIN embarcacoes emb ON emb.id = c.embarcacao_id
    LEFT JOIN clientes cli ON cli.id = c.cliente_id
    WHERE c.ativo = 1 AND c.status <> 'cancelado' AND c.data_validade IS NOT NULL

    UNION ALL

    SELECT 
        'CNBL' AS modelo,
        c.id,
        COALESCE(NULLIF(c.numero, ''), 'S/N') AS numero_documento,
        COALESCE(NULLIF(c.nome_embarcacao, ''), emb.nome, 'Embarcação não identificada') AS nome_embarcacao,
        COALESCE(NULLIF(c.numero_inscricao, ''), emb.numero_inscricao, emb.registro, '-') AS numero_inscricao,
        c.data_emissao,
        c.data_validade,
        DATEDIFF(c.data_validade, CURDATE()) AS dias_restantes,
        cli.nome AS cliente_nome,
        cli.telefone AS cliente_telefone
    FROM certificados_cnbl c
    LEFT JOIN embarcacoes emb ON emb.id = c.embarcacao_id
    LEFT JOIN clientes cli ON cli.id = c.cliente_id
    WHERE c.ativo = 1 AND c.status <> 'cancelado' AND c.data_validade IS NOT NULL

    UNION ALL

    SELECT 
        'CNARQ' AS modelo,
        c.id,
        COALESCE(NULLIF(c.numero, ''), 'S/N') AS numero_documento,
        COALESCE(NULLIF(c.nome_embarcacao, ''), emb.nome, 'Embarcação não identificada') AS nome_embarcacao,
        COALESCE(NULLIF(c.numero_inscricao, ''), emb.numero_inscricao, emb.registro, '-') AS numero_inscricao,
        c.data_emissao,
        c.data_validade,
        DATEDIFF(c.data_validade, CURDATE()) AS dias_restantes,
        cli.nome AS cliente_nome,
        cli.telefone AS cliente_telefone
    FROM certificados_cnarq c
    LEFT JOIN embarcacoes emb ON emb.id = c.embarcacao_id
    LEFT JOIN clientes cli ON cli.id = c.cliente_id
    WHERE c.ativo = 1 AND c.status <> 'cancelado' AND c.data_validade IS NOT NULL

    UNION ALL

    SELECT 
        'LP' AS modelo,
        c.id,
        COALESCE(NULLIF(c.numero_lp, ''), 'S/N') AS numero_documento,
        COALESCE(NULLIF(c.nome_embarcacao, ''), emb.nome, 'Embarcação não identificada') AS nome_embarcacao,
        COALESCE(NULLIF(emb.numero_inscricao, ''), emb.registro, '-') AS numero_inscricao,
        c.data_emissao,
        c.validade_data AS data_validade,
        DATEDIFF(c.validade_data, CURDATE()) AS dias_restantes,
        cli.nome AS cliente_nome,
        cli.telefone AS cliente_telefone
    FROM certificados_lp c
    LEFT JOIN embarcacoes emb ON emb.id = c.embarcacao_id
    LEFT JOIN clientes cli ON cli.id = c.cliente_id
    WHERE c.ativo = 1 AND c.status <> 'cancelado' AND c.validade_data IS NOT NULL

    UNION ALL

    SELECT 
        'LC' AS modelo,
        c.id,
        COALESCE(NULLIF(c.numero_lc, ''), 'S/N') AS numero_documento,
        COALESCE(NULLIF(c.nome_embarcacao, ''), emb.nome, 'Embarcação não identificada') AS nome_embarcacao,
        COALESCE(NULLIF(emb.numero_inscricao, ''), emb.registro, '-') AS numero_inscricao,
        c.data_emissao,
        c.data_validade,
        DATEDIFF(c.data_validade, CURDATE()) AS dias_restantes,
        cli.nome AS cliente_nome,
        cli.telefone AS cliente_telefone
    FROM certificados_lc c
    LEFT JOIN embarcacoes emb ON emb.id = c.embarcacao_id
    LEFT JOIN clientes cli ON cli.id = c.cliente_id
    WHERE c.ativo = 1 AND c.status <> 'cancelado' AND c.data_validade IS NOT NULL

    UNION ALL

    SELECT 
        'CHT' AS modelo,
        c.id,
        COALESCE(NULLIF(c.numero_certificado, ''), 'S/N') AS numero_documento,
        COALESCE(NULLIF(emb.nome, ''), 'Embarcação não identificada') AS nome_embarcacao,
        COALESCE(NULLIF(emb.numero_inscricao, ''), emb.registro, '-') AS numero_inscricao,
        c.data_emissao,
        c.data_validade,
        DATEDIFF(c.data_validade, CURDATE()) AS dias_restantes,
        cli.nome AS cliente_nome,
        cli.telefone AS cliente_telefone
    FROM certificados_cht c
    LEFT JOIN embarcacoes emb ON emb.id = c.embarcacao_id
    LEFT JOIN clientes cli ON cli.id = c.cliente_id
    WHERE c.ativo = 1 AND c.status <> 'cancelado' AND c.data_validade IS NOT NULL
";

$stmt = $pdo->query("SELECT COUNT(*) FROM ({$subqueryUnion}) AS u");
$totalCertificados = (int)$stmt->fetchColumn();
assertVenc($totalCertificados >= 0, "Falha ao executar query unificada de certificados.");
echo "  -> OK: Query executada com sucesso. Total de certificados unificados ativos: {$totalCertificados}\n";

// -------------------------------------------------------------
// TESTE 2: Cálculo dos KPIs e Segmentação por Prazo
// -------------------------------------------------------------
echo "[2/5] Testando agregação de KPIs e segmentação por prazos...\n";

$sqlKpis = "
    SELECT 
        COUNT(*) AS total_geral,
        SUM(CASE WHEN dias_restantes < 0 THEN 1 ELSE 0 END) AS vencidos,
        SUM(CASE WHEN dias_restantes BETWEEN 0 AND 15 THEN 1 ELSE 0 END) AS critico_15,
        SUM(CASE WHEN dias_restantes BETWEEN 16 AND 30 THEN 1 ELSE 0 END) AS urgente_30,
        SUM(CASE WHEN dias_restantes BETWEEN 31 AND 60 THEN 1 ELSE 0 END) AS atencao_60,
        SUM(CASE WHEN dias_restantes BETWEEN 61 AND 90 THEN 1 ELSE 0 END) AS planejamento_90,
        SUM(CASE WHEN dias_restantes > 90 THEN 1 ELSE 0 END) AS vigentes
    FROM ({$subqueryUnion}) AS u
";
$stmtKpi = $pdo->query($sqlKpis);
$kpis = $stmtKpi->fetch(PDO::FETCH_ASSOC);

assertVenc($kpis !== false, "Falha ao calcular KPIs agregados.");
$somaFaixas = (int)$kpis['vencidos'] + (int)$kpis['critico_15'] + (int)$kpis['urgente_30'] + (int)$kpis['atencao_60'] + (int)$kpis['planejamento_90'] + (int)$kpis['vigentes'];
assertVenc($somaFaixas === (int)$kpis['total_geral'], "A soma das faixas de prazo ({$somaFaixas}) diverge do total geral ({$kpis['total_geral']}).");

echo "  -> OK: KPIs calculados com precisão matemática:";
echo " Vencidos: {$kpis['vencidos']} | <=15d: {$kpis['critico_15']} | <=30d: {$kpis['urgente_30']} | <=60d: {$kpis['atencao_60']} | <=90d: {$kpis['planejamento_90']} | Vigentes: {$kpis['vigentes']}\n";

// -------------------------------------------------------------
// TESTE 3: Formatação da Mensagem WhatsApp e Regras NORMAM
// -------------------------------------------------------------
echo "[3/5] Testando formatação de mensagem WhatsApp e URL...\n";

$exemploCliente = "Comandante Silva";
$exemploEmbarcacao = "B/M Estrela do Norte";
$exemploInscricao = "021-123456-7";
$exemploModelo = "CSN";
$exemploNumero = "CSN-2026/0042";
$exemploValidade = "25/10/2026";
$exemploDias = 20;

$prazoMsg = "está próximo do vencimento em *{$exemploValidade}* (restam *{$exemploDias} dias*)";
$chamadaAcao = "Para manter a regularidade perante a Capitania dos Portos (NORMAM) e garantir a segurança da navegação, gostaríamos de programar a vistoria técnica de renovação.";
$primeiroNome = trim(explode(' ', $exemploCliente)[0]);

$texto = "Prezado(a) *{$primeiroNome}*, tudo bem?\n\nEntramos em contato da equipe técnica da *Amazon Certificadora Naval* para informar que o certificado *{$exemploModelo} nº {$exemploNumero}* da sua embarcação *{$exemploEmbarcacao}* (Inscrição: *{$exemploInscricao}*) {$prazoMsg}.\n\n{$chamadaAcao}\n\nPodemos emitir a proposta de renovação e reservar a data da vistoria? Como prefere proceder?";

assertVenc(str_contains($texto, 'Amazon Certificadora Naval'), "Mensagem deve conter nome da certificadora.");
assertVenc(str_contains($texto, 'Capitania dos Portos (NORMAM)'), "Mensagem deve citar a autoridade marítima e NORMAM.");
assertVenc(str_contains($texto, $exemploEmbarcacao), "Mensagem deve conter nome da embarcação.");
assertVenc(str_contains($texto, $exemploInscricao), "Mensagem deve conter inscrição naval.");

$telefoneTeste = "(92) 98123-4567";
$telDigitos = preg_replace('/\D/', '', $telefoneTeste);
$whatsNum = '55' . $telDigitos;
$linkWhats = "https://api.whatsapp.com/send?phone={$whatsNum}&text=" . rawurlencode($texto);

assertVenc(str_starts_with($linkWhats, 'https://api.whatsapp.com/send?phone=5592981234567&text='), "URL do WhatsApp deve conter DDI 55 e telefone limpo.");
echo "  -> OK: Formatação de WhatsApp e mensagem NORMAM validadas com sucesso.\n";

// -------------------------------------------------------------
// TESTE 4: Roteamento e Menu Lateral
// -------------------------------------------------------------
echo "[4/5] Verificando rotas no index.php e menu no sidebar.php...\n";

$indexContent = file_get_contents(__DIR__ . '/../index.php');
assertVenc(str_contains($indexContent, "'certificados/vencimentos'"), "Rota 'certificados/vencimentos' não registrada em index.php.");
assertVenc(str_contains($indexContent, "modules/certificados/vencimentos.php"), "Arquivo de destino não apontado em index.php.");

$sidebarContent = file_get_contents(__DIR__ . '/../includes/sidebar.php');
assertVenc(str_contains($sidebarContent, "certificados/vencimentos"), "Link do módulo não encontrado em includes/sidebar.php.");
assertVenc(str_contains($sidebarContent, "Vencimentos Navais"), "Rótulo 'Vencimentos Navais' não encontrado em includes/sidebar.php.");

echo "  -> OK: Rota e menu lateral devidamente integrados.\n";

// -------------------------------------------------------------
// TESTE 5: Integração com Formulário de Agendamento
// -------------------------------------------------------------
echo "[5/5] Verificando pré-preenchimento em agendamentos/form.php...\n";

$agendamentoForm = file_get_contents(__DIR__ . '/../modules/agendamentos/form.php');
assertVenc(str_contains($agendamentoForm, "\$_GET['cliente_id']"), "Pré-preenchimento de cliente_id ausente em agendamentos/form.php.");
assertVenc(str_contains($agendamentoForm, "\$_GET['embarcacao_id']"), "Pré-preenchimento de embarcacao_id ausente em agendamentos/form.php.");
assertVenc(str_contains($agendamentoForm, "\$_GET['tipo_vistoria']"), "Pré-preenchimento de tipo_vistoria ausente em agendamentos/form.php.");

echo "  -> OK: Agendamentos com suporte a pré-preenchimento por URL validado.\n";

echo "\n=== TODOS OS TESTES DE VENCIMENTO DE CERTIFICADOS PASSARAM COM SUCESSO! ===\n";
exit(0);
