<?php
/**
 * MÓDULO: DOCUMENTOS VENCIDOS
 * Arquivo: index.php - Painel Centralizado de Documentos Navais com Prazo Expirado
 * 
 * Normas de Referência: NORMAM-201 / NORMAM-202 / NORMAM-211 / RIPEAM (DPC / Marinha do Brasil)
 * Consolida e monitora documentos com validade expirada:
 * - Certificados Estatutários (CSN, CNBL, CNARQ, CHT)
 * - Licenças e Declarações Navais (LP, LC)
 * - Relatórios de Vistoria (Prazo de cumprimento de exigências vencido)
 * - Propostas Comerciais expiradas
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();

if (!podeAcessar('documentos_vencidos')) {
    setMensagem('error', 'Acesso negado para este módulo.');
    redirecionar(APP_URL . 'dashboard');
}

// -------------------------------------------------------------
// Filtros e Parâmetros
// -------------------------------------------------------------
$subcategoriaAtiva = trim((string)($_GET['subcategoria'] ?? 'todos'));
if (!in_array($subcategoriaAtiva, ['todos', 'estatutarios', 'licencas', 'vistorias', 'propostas'], true)) {
    $subcategoriaAtiva = 'todos';
}

$busca = trim((string)($_GET['q'] ?? ''));
$faixaDias = trim((string)($_GET['faixa_dias'] ?? 'todas')); // 'todas', 'critico' (>90d), 'moderado' (30-90d), 'recente' (<30d)

// -------------------------------------------------------------
// Montagem da Query Unificada de Documentos Vencidos
// -------------------------------------------------------------
$subqueries = [];

// 1. CSN (NORMAM-201/202)
$subqueries[] = "
    SELECT 
        'estatutarios' AS subcategoria,
        'CSN' AS tipo_documento,
        'Certificado de Segurança da Navegação' AS tipo_documento_nome,
        'NORMAM-201/202' AS referencia_normam,
        c.id,
        COALESCE(NULLIF(c.numero, ''), 'S/N') AS numero_documento,
        COALESCE(NULLIF(c.nome_embarcacao, ''), emb.nome, 'Embarcação não identificada') AS embarcacao_nome,
        COALESCE(NULLIF(c.numero_inscricao, ''), emb.numero_inscricao, emb.registro, 'N/D') AS embarcacao_inscricao,
        c.embarcacao_id,
        c.cliente_id,
        c.data_emissao,
        c.data_validade,
        c.status,
        DATEDIFF(CURDATE(), c.data_validade) AS dias_vencido,
        cli.nome AS cliente_nome,
        cli.telefone AS cliente_telefone,
        cli.email AS cliente_email,
        'documentacao/certificados/pdf?id=' AS rota_pdf,
        'documentacao/certificados/form?id=' AS rota_visualizar,
        'certificados_csn' AS tabela_origem
    FROM certificados_csn c
    LEFT JOIN embarcacoes emb ON emb.id = c.embarcacao_id
    LEFT JOIN clientes cli ON cli.id = c.cliente_id
    WHERE c.ativo = 1 AND c.status <> 'cancelado' 
      AND c.data_validade IS NOT NULL AND c.data_validade > '1970-01-01'
      AND c.data_validade < CURDATE()
";

// 2. CNBL (NORMAM-201/202)
$subqueries[] = "
    SELECT 
        'estatutarios' AS subcategoria,
        'CNBL' AS tipo_documento,
        'Certificado Nacional de Borda Livre' AS tipo_documento_nome,
        'NORMAM-201/202' AS referencia_normam,
        c.id,
        COALESCE(NULLIF(c.numero, ''), 'S/N') AS numero_documento,
        COALESCE(NULLIF(c.nome_embarcacao, ''), emb.nome, 'Embarcação não identificada') AS embarcacao_nome,
        COALESCE(NULLIF(c.numero_inscricao, ''), emb.numero_inscricao, emb.registro, 'N/D') AS embarcacao_inscricao,
        c.embarcacao_id,
        c.cliente_id,
        c.data_emissao,
        c.data_validade,
        c.status,
        DATEDIFF(CURDATE(), c.data_validade) AS dias_vencido,
        cli.nome AS cliente_nome,
        cli.telefone AS cliente_telefone,
        cli.email AS cliente_email,
        'documentacao/cnbl/pdf?id=' AS rota_pdf,
        'documentacao/cnbl?id=' AS rota_visualizar,
        'certificados_cnbl' AS tabela_origem
    FROM certificados_cnbl c
    LEFT JOIN embarcacoes emb ON emb.id = c.embarcacao_id
    LEFT JOIN clientes cli ON cli.id = c.cliente_id
    WHERE c.ativo = 1 AND c.status <> 'cancelado' 
      AND c.data_validade IS NOT NULL AND c.data_validade > '1970-01-01'
      AND c.data_validade < CURDATE()
";

// 3. CNARQ (NORMAM-201/202)
$subqueries[] = "
    SELECT 
        'estatutarios' AS subcategoria,
        'CNARQ' AS tipo_documento,
        'Certificado Nacional de Arqueação' AS tipo_documento_nome,
        'NORMAM-201/202' AS referencia_normam,
        c.id,
        COALESCE(NULLIF(c.numero, ''), 'S/N') AS numero_documento,
        COALESCE(NULLIF(c.nome_embarcacao, ''), emb.nome, 'Embarcação não identificada') AS embarcacao_nome,
        COALESCE(NULLIF(c.numero_inscricao, ''), emb.numero_inscricao, emb.registro, 'N/D') AS embarcacao_inscricao,
        c.embarcacao_id,
        c.cliente_id,
        c.data_emissao,
        c.data_validade,
        c.status,
        DATEDIFF(CURDATE(), c.data_validade) AS dias_vencido,
        cli.nome AS cliente_nome,
        cli.telefone AS cliente_telefone,
        cli.email AS cliente_email,
        'documentacao/cnarq/pdf?id=' AS rota_pdf,
        'documentacao/cnarq?id=' AS rota_visualizar,
        'certificados_cnarq' AS tabela_origem
    FROM certificados_cnarq c
    LEFT JOIN embarcacoes emb ON emb.id = c.embarcacao_id
    LEFT JOIN clientes cli ON cli.id = c.cliente_id
    WHERE c.ativo = 1 AND c.status <> 'cancelado' 
      AND c.data_validade IS NOT NULL AND c.data_validade > '1970-01-01'
      AND c.data_validade < CURDATE()
";

// 4. CHT (Certificado de Habilitação Técnica / Arqueação)
$subqueries[] = "
    SELECT 
        'estatutarios' AS subcategoria,
        'CHT' AS tipo_documento,
        'Certificado de Habilitação Técnica' AS tipo_documento_nome,
        'NORMAM-201/202' AS referencia_normam,
        c.id,
        COALESCE(NULLIF(c.numero_certificado, ''), 'S/N') AS numero_documento,
        COALESCE(emb.nome, 'Embarcação não identificada') AS embarcacao_nome,
        COALESCE(emb.numero_inscricao, emb.registro, 'N/D') AS embarcacao_inscricao,
        c.embarcacao_id,
        c.cliente_id,
        c.data_emissao,
        c.data_validade,
        c.status,
        DATEDIFF(CURDATE(), c.data_validade) AS dias_vencido,
        cli.nome AS cliente_nome,
        cli.telefone AS cliente_telefone,
        cli.email AS cliente_email,
        'documentacao/cht/pdf?id=' AS rota_pdf,
        'documentacao/cht/form?id=' AS rota_visualizar,
        'certificados_cht' AS tabela_origem
    FROM certificados_cht c
    LEFT JOIN embarcacoes emb ON emb.id = c.embarcacao_id
    LEFT JOIN clientes cli ON cli.id = c.cliente_id
    WHERE c.ativo = 1 AND c.status <> 'cancelado' 
      AND c.data_validade IS NOT NULL AND c.data_validade > '1970-01-01'
      AND c.data_validade < CURDATE()
";

// 5. LC (Licenças de Construção, Alteração, Reclassificação)
$subqueries[] = "
    SELECT 
        'licencas' AS subcategoria,
        'LC' AS tipo_documento,
        'Licença de Construção / Alteração' AS tipo_documento_nome,
        'NORMAM-201/202' AS referencia_normam,
        c.id,
        COALESCE(NULLIF(c.numero_lc, ''), 'S/N') AS numero_documento,
        COALESCE(NULLIF(c.nome_embarcacao, ''), emb.nome, 'Embarcação não identificada') AS embarcacao_nome,
        COALESCE(emb.numero_inscricao, emb.registro, 'N/D') AS embarcacao_inscricao,
        c.embarcacao_id,
        c.cliente_id,
        c.data_emissao,
        c.data_validade,
        c.status,
        DATEDIFF(CURDATE(), c.data_validade) AS dias_vencido,
        cli.nome AS cliente_nome,
        cli.telefone AS cliente_telefone,
        cli.email AS cliente_email,
        'documentacao/lc/pdf?id=' AS rota_pdf,
        'documentacao/lc/form?id=' AS rota_visualizar,
        'certificados_lc' AS tabela_origem
    FROM certificados_lc c
    LEFT JOIN embarcacoes emb ON emb.id = c.embarcacao_id
    LEFT JOIN clientes cli ON cli.id = c.cliente_id
    WHERE c.ativo = 1 AND c.status <> 'cancelado' 
      AND c.data_validade IS NOT NULL AND c.data_validade > '1970-01-01'
      AND c.data_validade < CURDATE()
";

// 6. LP (Licença Provisória)
$subqueries[] = "
    SELECT 
        'licencas' AS subcategoria,
        'LP' AS tipo_documento,
        'Licença Provisória para Entrada em Tráfego' AS tipo_documento_nome,
        'NORMAM-201/202' AS referencia_normam,
        c.id,
        COALESCE(NULLIF(c.numero_lp, ''), 'S/N') AS numero_documento,
        COALESCE(NULLIF(c.nome_embarcacao, ''), emb.nome, 'Embarcação não identificada') AS embarcacao_nome,
        COALESCE(emb.numero_inscricao, emb.registro, 'N/D') AS embarcacao_inscricao,
        c.embarcacao_id,
        c.cliente_id,
        c.data_emissao,
        c.validade_data AS data_validade,
        c.status,
        DATEDIFF(CURDATE(), c.validade_data) AS dias_vencido,
        cli.nome AS cliente_nome,
        cli.telefone AS cliente_telefone,
        cli.email AS cliente_email,
        'documentacao/lp/pdf?id=' AS rota_pdf,
        'documentacao/lp/form?id=' AS rota_visualizar,
        'certificados_lp' AS tabela_origem
    FROM certificados_lp c
    LEFT JOIN embarcacoes emb ON emb.id = c.embarcacao_id
    LEFT JOIN clientes cli ON cli.id = c.cliente_id
    WHERE c.ativo = 1 AND c.status <> 'cancelado' 
      AND c.validade_data IS NOT NULL AND c.validade_data > '1970-01-01'
      AND c.validade_data < CURDATE()
";

// 7. Vistorias com prazo de cumprimento de exigências vencido
$subqueries[] = "
    SELECT 
        'vistorias' AS subcategoria,
        'VISTORIA' AS tipo_documento,
        'Relatório de Vistoria (Prazo de Exigências)' AS tipo_documento_nome,
        'NORMAM-202 (Vistorias)' AS referencia_normam,
        v.id,
        COALESCE(NULLIF(v.numero, ''), 'Vistoria S/N') AS numero_documento,
        COALESCE(emb.nome, 'Embarcação não identificada') AS embarcacao_nome,
        COALESCE(emb.numero_inscricao, emb.registro, 'N/D') AS embarcacao_inscricao,
        v.embarcacao_id,
        COALESCE(v.pessoa_id, v.armador_id) AS cliente_id,
        v.data_vistoria AS data_emissao,
        DATE_ADD(v.data_vistoria, INTERVAL v.prazo_exigencias_dias DAY) AS data_validade,
        v.status,
        DATEDIFF(CURDATE(), DATE_ADD(v.data_vistoria, INTERVAL v.prazo_exigencias_dias DAY)) AS dias_vencido,
        cli.nome AS cliente_nome,
        cli.telefone AS cliente_telefone,
        cli.email AS cliente_email,
        'vistorias/relatorio-pdf?id=' AS rota_pdf,
        'vistorias/detalhe?id=' AS rota_visualizar,
        'vistorias' AS tabela_origem
    FROM vistorias v
    LEFT JOIN embarcacoes emb ON emb.id = v.embarcacao_id
    LEFT JOIN clientes cli ON cli.id = COALESCE(v.pessoa_id, v.armador_id)
    WHERE v.status NOT IN ('CANCELADA', 'REPROVADA')
      AND v.prazo_exigencias_dias IS NOT NULL AND v.prazo_exigencias_dias > 0
      AND DATE_ADD(v.data_vistoria, INTERVAL v.prazo_exigencias_dias DAY) < CURDATE()
";

// 8. Propostas Comerciais expiradas
$subqueries[] = "
    SELECT 
        'propostas' AS subcategoria,
        'PROPOSTA' AS tipo_documento,
        'Proposta Comercial de Serviços Navais' AS tipo_documento_nome,
        'Comercial' AS referencia_normam,
        p.id,
        p.numero AS numero_documento,
        COALESCE(
            (SELECT GROUP_CONCAT(DISTINCT e.nome SEPARATOR ', ')
             FROM propostas_embarcacoes pe
             JOIN embarcacoes e ON e.id = pe.embarcacao_id
             WHERE pe.proposta_id = p.id),
            'Embarcação em Negociação'
        ) AS embarcacao_nome,
        'N/D' AS embarcacao_inscricao,
        (SELECT pe.embarcacao_id FROM propostas_embarcacoes pe WHERE pe.proposta_id = p.id LIMIT 1) AS embarcacao_id,
        p.cliente_id,
        p.data_emissao,
        p.data_validade,
        p.status,
        DATEDIFF(CURDATE(), p.data_validade) AS dias_vencido,
        cli.nome AS cliente_nome,
        cli.telefone AS cliente_telefone,
        cli.email AS cliente_email,
        'comercial/pdf?id=' AS rota_pdf,
        'comercial/nova?id=' AS rota_visualizar,
        'propostas' AS tabela_origem
    FROM propostas p
    LEFT JOIN clientes cli ON cli.id = p.cliente_id
    WHERE p.status IN ('rascunho', 'enviada')
      AND p.data_validade IS NOT NULL AND p.data_validade > '1970-01-01'
      AND p.data_validade < CURDATE()
";

$unionSql = implode(" UNION ALL ", $subqueries);

// -------------------------------------------------------------
// Contadores para os Cards e Abas
// -------------------------------------------------------------
$sqlContadores = "
    SELECT 
        COUNT(*) AS total_geral,
        SUM(CASE WHEN subcategoria = 'estatutarios' THEN 1 ELSE 0 END) AS total_estatutarios,
        SUM(CASE WHEN subcategoria = 'licencas' THEN 1 ELSE 0 END) AS total_licencas,
        SUM(CASE WHEN subcategoria = 'vistorias' THEN 1 ELSE 0 END) AS total_vistorias,
        SUM(CASE WHEN subcategoria = 'propostas' THEN 1 ELSE 0 END) AS total_propostas,
        SUM(CASE WHEN dias_vencido > 90 THEN 1 ELSE 0 END) AS total_critico
    FROM ({$unionSql}) AS uniao_docs
";
$stmtContadores = $pdo->query($sqlContadores);
$contadores = $stmtContadores->fetch(PDO::FETCH_ASSOC) ?: [
    'total_geral' => 0,
    'total_estatutarios' => 0,
    'total_licencas' => 0,
    'total_vistorias' => 0,
    'total_propostas' => 0,
    'total_critico' => 0,
];

// Contador de e-mails de alerta enviados nos últimos 7 dias
$stmtAlertas7d = $pdo->query("
    SELECT COUNT(*) FROM email_logs
    WHERE tipo = 'alerta_vencimento'
      AND status = 'enviado'
      AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
");
$totalAlertados7d = (int)($stmtAlertas7d->fetchColumn() ?: 0);

// -------------------------------------------------------------
// Montagem da Consulta Filtrada
// -------------------------------------------------------------
$whereConditions = ["1 = 1"];
$params = [];

if ($subcategoriaAtiva !== 'todos') {
    $whereConditions[] = "subcategoria = :subcategoria";
    $params[':subcategoria'] = $subcategoriaAtiva;
}

if (!empty($busca)) {
    $whereConditions[] = "(
        embarcacao_nome LIKE :busca 
        OR numero_documento LIKE :busca 
        OR cliente_nome LIKE :busca 
        OR embarcacao_inscricao LIKE :busca
    )";
    $params[':busca'] = "%{$busca}%";
}

if ($faixaDias === 'critico') {
    $whereConditions[] = "dias_vencido > 90";
} elseif ($faixaDias === 'moderado') {
    $whereConditions[] = "dias_vencido BETWEEN 30 AND 90";
} elseif ($faixaDias === 'recente') {
    $whereConditions[] = "dias_vencido < 30";
}

$whereSql = implode(" AND ", $whereConditions);

$sqlLista = "
    SELECT *
    FROM ({$unionSql}) AS uniao_docs
    WHERE {$whereSql}
    ORDER BY dias_vencido DESC, data_validade ASC
";
$stmtLista = $pdo->prepare($sqlLista);
$stmtLista->execute($params);
$documentosVencidos = $stmtLista->fetchAll(PDO::FETCH_ASSOC);

$titulo_page = 'Documentos Vencidos - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<style>
.docvenc-container {
    padding: 24px 30px;
}
.kpi-card {
    background: #ffffff;
    border-radius: 12px;
    padding: 20px 22px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    display: flex;
    align-items: center;
    gap: 16px;
    transition: transform 0.2s, box-shadow 0.2s;
}
.kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}
.kpi-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
}
.kpi-icon.danger { background: #fee2e2; color: #dc2626; }
.kpi-icon.warning { background: #fef3c7; color: #d97706; }
.kpi-icon.info { background: #e0f2fe; color: #0284c7; }
.kpi-icon.success { background: #dcfce7; color: #16a34a; }
.kpi-val { font-size: 1.6rem; font-weight: 700; color: #111827; line-height: 1.2; }
.kpi-label { font-size: 0.82rem; color: #6b7280; font-weight: 500; margin-top: 2px; }

.subcat-tabs {
    display: flex;
    gap: 8px;
    border-bottom: 2px solid #e5e7eb;
    margin-bottom: 20px;
    overflow-x: auto;
    padding-bottom: 2px;
}
.subcat-tab {
    padding: 10px 16px;
    font-size: 0.88rem;
    font-weight: 600;
    color: #4b5563;
    text-decoration: none;
    border-bottom: 3px solid transparent;
    display: flex;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
    transition: all 0.2s;
}
.subcat-tab:hover {
    color: #065f46;
}
.subcat-tab.active {
    color: #047857;
    border-bottom-color: #047857;
}

.table-docvenc th {
    background: #f9fafb;
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #4b5563;
    padding: 12px 14px;
    border-bottom: 1px solid #e5e7eb;
}
.table-docvenc td {
    padding: 14px;
    font-size: 0.86rem;
    vertical-align: middle;
    border-bottom: 1px solid #f3f4f6;
}
.badge-dias-venc {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 700;
}
.badge-dias-venc.critico { background: #fee2e2; color: #b91c1c; }
.badge-dias-venc.moderado { background: #ffedd5; color: #c2410c; }
.badge-dias-venc.recente { background: #fef9c3; color: #854d0e; }

.pill-normam {
    display: inline-block;
    font-size: 0.68rem;
    padding: 2px 7px;
    background: #eef2f6;
    color: #475569;
    border-radius: 4px;
    font-weight: 600;
}

/* Modal de Alerta */
.modal-alertar-wrap {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(15, 23, 42, 0.6);
    z-index: 1050;
    align-items: center;
    justify-content: center;
}
.modal-alertar-box {
    background: #ffffff;
    width: 90%;
    max-width: 600px;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    overflow: hidden;
    animation: fadeInModal 0.2s ease-out;
}
@keyframes fadeInModal {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<div class="docvenc-container">
    <!-- Header e Diretrizes -->
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h3 class="fw-bold mb-1" style="color: #064e3b;">
                <i class="fa-solid fa-triangle-exclamation text-danger me-2"></i> Documentos Vencidos
            </h3>
            <p class="text-muted small mb-0">
                Monitoramento consolidado e emissão de alertas para certificados, licenças e vistorias navais com prazo expirado.
            </p>
        </div>
        <div>
            <a href="<?= APP_URL ?>certificados/vencimentos" class="btn btn-outline-secondary btn-sm me-2">
                <i class="fa-solid fa-clock-rotate-left me-1"></i> Vencimentos Futuros
            </a>
            <a href="<?= APP_URL ?>comercial/nova" class="btn btn-success btn-sm">
                <i class="fa-solid fa-plus me-1"></i> Nova Proposta / Regularização
            </a>
        </div>
    </div>

    <!-- Alert Informativo Autodidático (NORMAM) -->
    <div class="alert alert-light border border-secondary-subtle p-3 mb-4 rounded-3 d-flex align-items-center gap-3">
        <i class="fa-solid fa-circle-info fs-4 text-primary"></i>
        <div class="small">
            <strong>Instrução Normativa NORMAM (DPC/Marinha do Brasil):</strong>
            Embarcações com certificados ou prazos de vistoria vencidos estão impedidas de trafegar e sujeitas a apreensão pela Capitania dos Portos.
            Utilize os atalhos de <strong>Alertar Cliente</strong> para acionar simultaneamente o proprietário, despachante naval e locatário com 1 clique.
        </div>
    </div>

    <!-- Cards de Indicadores (KPIs) -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="kpi-card">
                <div class="kpi-icon danger">
                    <i class="fa-solid fa-file-circle-xmark"></i>
                </div>
                <div>
                    <div class="kpi-val"><?= (int)$contadores['total_geral'] ?></div>
                    <div class="kpi-label">Total de Documentos Vencidos</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="kpi-card">
                <div class="kpi-icon warning">
                    <i class="fa-solid fa-award"></i>
                </div>
                <div>
                    <div class="kpi-val"><?= (int)$contadores['total_estatutarios'] ?></div>
                    <div class="kpi-label">Certificados Estatutários (CSN/CNBL/CNARQ)</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="kpi-card">
                <div class="kpi-icon info">
                    <i class="fa-solid fa-clipboard-check"></i>
                </div>
                <div>
                    <div class="kpi-val"><?= (int)($contadores['total_vistorias'] + $contadores['total_licencas']) ?></div>
                    <div class="kpi-label">Vistorias & Licenças Vencidas</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="kpi-card">
                <div class="kpi-icon success">
                    <i class="fa-solid fa-paper-plane"></i>
                </div>
                <div>
                    <div class="kpi-val"><?= $totalAlertados7d ?></div>
                    <div class="kpi-label">Alertas Disparados (Últimos 7 dias)</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Abas Especializadas por Subcategoria -->
    <div class="subcat-tabs">
        <a href="?subcategoria=todos&q=<?= urlencode($busca) ?>&faixa_dias=<?= $faixaDias ?>" class="subcat-tab <?= $subcategoriaAtiva === 'todos' ? 'active' : '' ?>">
            <i class="fa-solid fa-layer-group"></i> Todos os Vencidos
            <span class="badge bg-secondary rounded-pill"><?= (int)$contadores['total_geral'] ?></span>
        </a>
        <a href="?subcategoria=estatutarios&q=<?= urlencode($busca) ?>&faixa_dias=<?= $faixaDias ?>" class="subcat-tab <?= $subcategoriaAtiva === 'estatutarios' ? 'active' : '' ?>">
            <i class="fa-solid fa-award"></i> Certificados Estatutários
            <span class="badge bg-danger rounded-pill"><?= (int)$contadores['total_estatutarios'] ?></span>
        </a>
        <a href="?subcategoria=licencas&q=<?= urlencode($busca) ?>&faixa_dias=<?= $faixaDias ?>" class="subcat-tab <?= $subcategoriaAtiva === 'licencas' ? 'active' : '' ?>">
            <i class="fa-solid fa-id-card"></i> Licenças Navais (LP/LC)
            <span class="badge bg-warning text-dark rounded-pill"><?= (int)$contadores['total_licencas'] ?></span>
        </a>
        <a href="?subcategoria=vistorias&q=<?= urlencode($busca) ?>&faixa_dias=<?= $faixaDias ?>" class="subcat-tab <?= $subcategoriaAtiva === 'vistorias' ? 'active' : '' ?>">
            <i class="fa-solid fa-clipboard-list"></i> Relatórios de Vistoria
            <span class="badge bg-warning text-dark rounded-pill"><?= (int)$contadores['total_vistorias'] ?></span>
        </a>
        <a href="?subcategoria=propostas&q=<?= urlencode($busca) ?>&faixa_dias=<?= $faixaDias ?>" class="subcat-tab <?= $subcategoriaAtiva === 'propostas' ? 'active' : '' ?>">
            <i class="fa-solid fa-file-invoice-dollar"></i> Propostas Comerciais
            <span class="badge bg-secondary rounded-pill"><?= (int)$contadores['total_propostas'] ?></span>
        </a>
    </div>

    <!-- Barra de Filtros e Busca Rápida -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" class="row g-2 align-items-center">
                <input type="hidden" name="subcategoria" value="<?= h($subcategoriaAtiva) ?>">
                
                <div class="col-12 col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" name="q" value="<?= h($busca) ?>" class="form-control border-start-0" placeholder="Buscar por embarcação, cliente, número do documento...">
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <select name="faixa_dias" class="form-select" onchange="this.form.submit()">
                        <option value="todas" <?= $faixaDias === 'todas' ? 'selected' : '' ?>>Todas as faixas de vencimento</option>
                        <option value="critico" <?= $faixaDias === 'critico' ? 'selected' : '' ?>>Crítico: Vencido há mais de 90 dias</option>
                        <option value="moderado" <?= $faixaDias === 'moderado' ? 'selected' : '' ?>>Moderado: Vencido entre 30 e 90 dias</option>
                        <option value="recente" <?= $faixaDias === 'recente' ? 'selected' : '' ?>>Recente: Vencido há menos de 30 dias</option>
                    </select>
                </div>

                <div class="col-12 col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fa-solid fa-filter me-1"></i> Filtrar
                    </button>
                    <?php if (!empty($busca) || $faixaDias !== 'todas'): ?>
                        <a href="?subcategoria=<?= h($subcategoriaAtiva) ?>" class="btn btn-outline-secondary" title="Limpar filtros">
                            <i class="fa-solid fa-xmark"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabela Consolidada de Documentos Vencidos -->
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover table-docvenc mb-0">
                <thead>
                    <tr>
                        <th>Documento</th>
                        <th>Embarcação</th>
                        <th>Cliente / Armador</th>
                        <th>Vencimento</th>
                        <th>Tempo Vencido</th>
                        <th class="text-end">Ações Rápidas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($documentosVencidos)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-circle-check fs-1 text-success mb-3 d-block"></i>
                                <strong>Nenhum documento vencido encontrado!</strong>
                                <p class="small text-muted mb-0">Todos os documentos cadastrados nesta categoria estão com a validade em dia.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($documentosVencidos as $doc): ?>
                            <?php 
                            $dias = (int)$doc['dias_vencido'];
                            $badgeClasse = ($dias > 90) ? 'critico' : (($dias >= 30) ? 'moderado' : 'recente');
                            $dataValidadeFmt = !empty($doc['data_validade']) ? date('d/m/Y', strtotime($doc['data_validade'])) : 'N/D';
                            
                            // Link para abrir PDF ou visualização
                            $linkPdf = !empty($doc['rota_pdf']) ? APP_URL . $doc['rota_pdf'] . $doc['id'] : '#';
                            $linkVis = !empty($doc['rota_visualizar']) ? APP_URL . $doc['rota_visualizar'] . $doc['id'] : '#';
                            
                            // Mensagem WhatsApp formatada
                            $textoWhats = urlencode("Olá, informamos que o documento {$doc['tipo_documento']} nº {$doc['numero_documento']} da embarcação {$doc['embarcacao_nome']} encontra-se vencido desde {$dataValidadeFmt}. Favor entrar em contato com a Amazon Naval para regularização.");
                            $telCliente = preg_replace('/\D/', '', (string)$doc['cliente_telefone']);
                            $linkWhats = !empty($telCliente) ? "https://wa.me/55{$telCliente}?text={$textoWhats}" : "https://wa.me/?text={$textoWhats}";
                            ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-dark-subtle text-dark border fw-bold"><?= h($doc['tipo_documento']) ?></span>
                                        <strong><?= h($doc['numero_documento']) ?></strong>
                                    </div>
                                    <div class="text-muted small mt-1">
                                        <?= h($doc['tipo_documento_nome']) ?>
                                    </div>
                                    <span class="pill-normam mt-1"><?= h($doc['referencia_normam']) ?></span>
                                </td>

                                <td>
                                    <strong><?= h($doc['embarcacao_nome']) ?></strong>
                                    <div class="text-muted small">
                                        Inscrição: <?= h($doc['embarcacao_inscricao']) ?>
                                    </div>
                                </td>

                                <td>
                                    <div><strong><?= h($doc['cliente_nome'] ?: 'Não vinculado') ?></strong></div>
                                    <?php if (!empty($doc['cliente_email'])): ?>
                                        <div class="text-muted small"><i class="fa-regular fa-envelope me-1"></i><?= h($doc['cliente_email']) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($doc['cliente_telefone'])): ?>
                                        <div class="text-muted small"><i class="fa-brands fa-whatsapp text-success me-1"></i><?= h($doc['cliente_telefone']) ?></div>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <span class="text-danger fw-bold"><?= $dataValidadeFmt ?></span>
                                    <div class="text-muted small">
                                        Emitido: <?= !empty($doc['data_emissao']) ? date('d/m/Y', strtotime($doc['data_emissao'])) : 'N/D' ?>
                                    </div>
                                </td>

                                <td>
                                    <span class="badge-dias-venc <?= $badgeClasse ?>">
                                        <i class="fa-solid fa-triangle-exclamation"></i>
                                        Vencido há <?= $dias ?> dia(s)
                                    </span>
                                </td>

                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <!-- Botão Alertar por E-mail (Abre Modal com contatos múltiplos) -->
                                        <button type="button" class="btn btn-outline-danger" 
                                                title="Alertar Cliente e Envolvidos por E-mail"
                                                onclick="abrirModalAlerta(<?= htmlspecialchars(json_encode([
                                                    'id' => $doc['id'],
                                                    'tabela_origem' => $doc['tabela_origem'],
                                                    'tipo_documento' => $doc['tipo_documento'],
                                                    'numero_documento' => $doc['numero_documento'],
                                                    'embarcacao_id' => $doc['embarcacao_id'],
                                                    'embarcacao_nome' => $doc['embarcacao_nome'],
                                                    'embarcacao_inscricao' => $doc['embarcacao_inscricao'],
                                                    'cliente_id' => $doc['cliente_id'],
                                                    'cliente_nome' => $doc['cliente_nome'],
                                                    'data_validade' => $dataValidadeFmt,
                                                    'dias_vencido' => $dias,
                                                ]), ENT_QUOTES, 'UTF-8') ?>)">
                                            <i class="fa-solid fa-paper-plane me-1"></i> Alertar E-mail
                                        </button>

                                        <!-- Botão WhatsApp -->
                                        <a href="<?= $linkWhats ?>" target="_blank" class="btn btn-outline-success" title="Enviar cobrança via WhatsApp">
                                            <i class="fa-brands fa-whatsapp"></i>
                                        </a>

                                        <!-- Botão Ver PDF -->
                                        <?php if (!empty($doc['rota_pdf'])): ?>
                                            <a href="<?= $linkPdf ?>" target="_blank" class="btn btn-outline-secondary" title="Visualizar Documento em PDF">
                                                <i class="fa-solid fa-file-pdf"></i>
                                            </a>
                                        <?php endif; ?>

                                        <!-- Botão Iniciar Renovação -->
                                        <?php if (!empty($doc['embarcacao_id'])): ?>
                                            <a href="<?= APP_URL ?>comercial/nova?embarcacao_id=<?= urlencode($doc['embarcacao_id']) ?>" class="btn btn-success" title="Iniciar Nova Proposta para Regularização">
                                                <i class="fa-solid fa-rotate"></i> Renovar
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Interativo de Disparo de Alerta -->
<div id="modalAlertarWrap" class="modal-alertar-wrap">
    <div class="modal-alertar-box">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light">
            <h5 class="mb-0 fw-bold text-dark">
                <i class="fa-solid fa-envelope-circle-check text-danger me-2"></i> Enviar Alerta de Documento Vencido
            </h5>
            <button type="button" class="btn-close" onclick="fecharModalAlerta()"></button>
        </div>

        <form id="formDispararAlerta" onsubmit="enviarAlertaEmail(event)">
            <input type="hidden" id="alertaDocId" name="id">
            <input type="hidden" id="alertaTabela" name="tabela_origem">
            <input type="hidden" id="alertaTipoDoc" name="tipo_documento">
            <input type="hidden" id="alertaEmbarcacaoId" name="embarcacao_id">
            <input type="hidden" id="alertaEmbarcacaoNome" name="embarcacao_nome">
            <input type="hidden" id="alertaClienteId" name="cliente_id">

            <div class="p-4">
                <!-- Resumo do Documento -->
                <div class="bg-light p-3 rounded-3 mb-3 border">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <strong id="alertaResumoDoc" class="text-dark">Documento</strong>
                        <span id="alertaBadgeDias" class="badge bg-danger">Vencido</span>
                    </div>
                    <div class="small text-muted">
                        Embarcação: <strong id="alertaResumoEmbarcacao" class="text-dark">-</strong> | 
                        Vencimento: <strong id="alertaResumoValidade" class="text-dark">-</strong>
                    </div>
                </div>

                <!-- Lista de Destinatários Notificáveis -->
                <label class="form-label fw-bold mb-1">
                    Destinatários Vinculados à Embarcação / Processo:
                </label>
                <div class="text-muted small mb-2">
                    Marque os e-mails que devem receber o aviso oficial com as informações técnicas da embarcação:
                </div>

                <div id="alertaListaContatos" class="border rounded-3 p-3 bg-white" style="max-height: 220px; overflow-y: auto;">
                    <div class="text-center py-3 text-muted">
                        <i class="fa-solid fa-spinner fa-spin me-2"></i> Carregando contatos vinculados...
                    </div>
                </div>
            </div>

            <div class="p-3 bg-light border-top d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-secondary btn-sm" onclick="fecharModalAlerta()">Cancelar</button>
                <button type="submit" id="btnConfirmarEnvio" class="btn btn-danger btn-sm">
                    <i class="fa-solid fa-paper-plane me-1"></i> Disparar Alerta Agora
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let modalDocAtual = null;

function abrirModalAlerta(docData) {
    modalDocAtual = docData;
    document.getElementById('alertaDocId').value = docData.id;
    document.getElementById('alertaTabela').value = docData.tabela_origem;
    document.getElementById('alertaTipoDoc').value = docData.tipo_documento;
    document.getElementById('alertaEmbarcacaoId').value = docData.embarcacao_id || '';
    document.getElementById('alertaEmbarcacaoNome').value = docData.embarcacao_nome || '';
    document.getElementById('alertaClienteId').value = docData.cliente_id || '';

    document.getElementById('alertaResumoDoc').innerText = docData.tipo_documento + ' - ' + docData.numero_documento;
    document.getElementById('alertaResumoEmbarcacao').innerText = docData.embarcacao_nome;
    document.getElementById('alertaResumoValidade').innerText = docData.data_validade;
    document.getElementById('alertaBadgeDias').innerText = 'Vencido há ' + docData.dias_vencido + ' dia(s)';

    const containerContatos = document.getElementById('alertaListaContatos');
    containerContatos.innerHTML = '<div class="text-center py-3 text-muted"><i class="fa-solid fa-spinner fa-spin me-2"></i> Localizando e-mails vinculados (Proprietário, Despachante, Locatário, etc.)...</div>';

    document.getElementById('modalAlertarWrap').style.display = 'flex';

    // Buscar lista de destinatários vinculados
    const urlBusca = '<?= APP_URL ?>documentos-vencidos/actions?action=buscar_destinatarios' +
        '&embarcacao_id=' + encodeURIComponent(docData.embarcacao_id || '') +
        '&embarcacao_nome=' + encodeURIComponent(docData.embarcacao_nome || '') +
        '&cliente_id=' + encodeURIComponent(docData.cliente_id || '');

    fetch(urlBusca)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.destinatarios && data.destinatarios.length > 0) {
                let html = '';
                data.destinatarios.forEach((dest, idx) => {
                    const isChecked = dest.recebe_alertas ? 'checked' : '';
                    html += `
                        <div class="form-check p-2 border-bottom d-flex align-items-center justify-content-between">
                            <div>
                                <input class="form-check-input me-2" type="checkbox" name="emails_selecionados[]" value="${dest.email}" id="destCheck_${idx}" ${isChecked}>
                                <label class="form-check-label fw-bold text-dark" for="destCheck_${idx}">
                                    ${dest.nome}
                                </label>
                                <div class="small text-muted ms-4">${dest.email}</div>
                            </div>
                            <span class="badge bg-secondary-subtle text-dark border">${dest.papel_label || dest.papel}</span>
                        </div>
                    `;
                });
                containerContatos.innerHTML = html;
            } else {
                containerContatos.innerHTML = '<div class="text-center py-3 text-muted"><i class="fa-solid fa-circle-exclamation text-warning me-2"></i> Nenhum e-mail vinculado encontrado. Verifique o cadastro da embarcação e do proprietário.</div>';
            }
        })
        .catch(err => {
            containerContatos.innerHTML = '<div class="text-center py-3 text-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i> Erro ao carregar contatos: ' + err.message + '</div>';
        });
}

function fecharModalAlerta() {
    document.getElementById('modalAlertarWrap').style.display = 'none';
}

function enviarAlertaEmail(event) {
    event.preventDefault();
    const btn = document.getElementById('btnConfirmarEnvio');
    const form = document.getElementById('formDispararAlerta');
    const formData = new FormData(form);
    formData.append('action', 'alertar_cliente_email');

    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Enviando...';

    fetch('<?= APP_URL ?>documentos-vencidos/actions', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-paper-plane me-1"></i> Disparar Alerta Agora';

        if (data.success) {
            alert(data.message);
            fecharModalAlerta();
        } else {
            alert('Atenção: ' + (data.message || 'Erro ao processar envio de alerta.'));
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-paper-plane me-1"></i> Disparar Alerta Agora';
        alert('Falha na requisição: ' + err.message);
    });
}

// Fechar modal ao pressionar ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        fecharModalAlerta();
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
