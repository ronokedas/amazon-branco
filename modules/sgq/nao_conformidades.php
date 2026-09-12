<?php
/**
 * MÓDULO: SGQ - GESTÃO DA QUALIDADE (ISO 9001:2015 & NORMAM)
 * Arquivo: modules/sgq/nao_conformidades.php
 * Gestão de Não Conformidades (RNC - ISO 8.7 e 10.2) e Planos de Ação 5W2H
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
exigirAcesso('dashboard'); // Acesso liberado para cargos operacionais/admin

$usuarioId = $_SESSION['usuario_id'] ?? '';
$cargo = getCargo();

// Filtros
$filtroStatus = trim((string)($_GET['status'] ?? ''));
$filtroOrigem = trim((string)($_GET['origem'] ?? ''));
$filtroSeveridade = trim((string)($_GET['severidade'] ?? ''));
$busca = trim((string)($_GET['busca'] ?? ''));
$detalheId = trim((string)($_GET['id'] ?? ''));

// Cláusula WHERE
$params = [];
$whereSql = "WHERE 1=1";

if ($filtroStatus !== '') {
    $whereSql .= " AND r.status_ciclo_vida = :status";
    $params[':status'] = $filtroStatus;
}
if ($filtroOrigem !== '') {
    $whereSql .= " AND r.origem = :origem";
    $params[':origem'] = $filtroOrigem;
}
if ($filtroSeveridade !== '') {
    $whereSql .= " AND r.severidade = :severidade";
    $params[':severidade'] = $filtroSeveridade;
}
if ($busca !== '') {
    $whereSql .= " AND (r.numero_rnc LIKE :busca OR r.titulo LIKE :busca OR e.nome LIKE :busca)";
    $params[':busca'] = '%' . $busca . '%';
}

// Consultar RNCs
$sql = "
    SELECT r.*, 
           e.nome AS embarcacao_nome,
           c.nome AS cliente_nome,
           os.numero AS os_numero,
           (SELECT COUNT(*) FROM sgq_planos_acao pa WHERE pa.nao_conformidade_id = r.id) AS total_acoes_5w2h,
           (SELECT COUNT(*) FROM sgq_planos_acao pa WHERE pa.nao_conformidade_id = r.id AND pa.status_acao = 'CONCLUIDA') AS acoes_concluidas
    FROM sgq_nao_conformidades r
    LEFT JOIN embarcacoes e ON e.id = r.embarcacao_id
    LEFT JOIN clientes c ON c.id = r.cliente_id
    LEFT JOIN ordens_servico os ON os.id = r.ordem_servico_id
    {$whereSql}
    ORDER BY r.criado_em DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rncs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Totais para cards superiores
$totais = $pdo->query("
    SELECT 
        COUNT(*) AS total,
        SUM(CASE WHEN status_ciclo_vida = 'ABERTA' THEN 1 ELSE 0 END) AS abertas,
        SUM(CASE WHEN status_ciclo_vida IN ('EM_ANALISE_CAUSA','PLANO_ACAO_DEFINIDO','EM_EXECUCAO','AGUARDANDO_EFICACIA') THEN 1 ELSE 0 END) AS em_andamento,
        SUM(CASE WHEN status_ciclo_vida = 'ENCERRADA_EFICAZ' THEN 1 ELSE 0 END) AS encerradas
    FROM sgq_nao_conformidades
")->fetch(PDO::FETCH_ASSOC);

// Se houver detalhe selecionado
$rncDetalhe = null;
$planos5w2h = [];
if ($detalheId !== '') {
    $stmtDet = $pdo->prepare("
        SELECT r.*, e.nome AS embarcacao_nome, c.nome AS cliente_nome, os.numero AS os_numero
        FROM sgq_nao_conformidades r
        LEFT JOIN embarcacoes e ON e.id = r.embarcacao_id
        LEFT JOIN clientes c ON c.id = r.cliente_id
        LEFT JOIN ordens_servico os ON os.id = r.ordem_servico_id
        WHERE r.id = :id
        LIMIT 1
    ");
    $stmtDet->execute([':id' => $detalheId]);
    $rncDetalhe = $stmtDet->fetch(PDO::FETCH_ASSOC);

    if ($rncDetalhe) {
        $stmtPlanos = $pdo->prepare("
            SELECT * FROM sgq_planos_acao
            WHERE nao_conformidade_id = :id
            ORDER BY quando_fara_when ASC, criado_em ASC
        ");
        $stmtPlanos->execute([':id' => $detalheId]);
        $planos5w2h = $stmtPlanos->fetchAll(PDO::FETCH_ASSOC);
    }
}

$titulo_page = 'Gestão de Não Conformidades (RNC / 5W2H) - SGQ';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<main class="app-main">
    <div class="container-fluid p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h3 font-weight-bold mb-1"><i class="fa-solid fa-triangle-exclamation text-warning mr-2"></i> Não Conformidades (RNC & 5W2H)</h2>
                <p class="text-muted mb-0">Controle de Saídas Não Conformes (ISO 8.7) e Ações Corretivas (ISO 10.2) integrado à NORMAM.</p>
            </div>
            <div>
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalNovaRnc">
                    <i class="fa-solid fa-plus mr-1"></i> Nova RNC Manual
                </button>
            </div>
        </div>

        <!-- Indicadores Rápidos -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card border-left-primary shadow-sm h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Registrado</div>
                        <div class="h4 mb-0 font-weight-bold"><?= (int)($totais['total'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-left-danger shadow-sm h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Abertas (Pendentes)</div>
                        <div class="h4 mb-0 font-weight-bold text-danger"><?= (int)($totais['abertas'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-left-warning shadow-sm h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Em Tratamento 5W2H</div>
                        <div class="h4 mb-0 font-weight-bold text-warning"><?= (int)($totais['em_andamento'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-left-success shadow-sm h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Encerradas Eficazes</div>
                        <div class="h4 mb-0 font-weight-bold text-success"><?= (int)($totais['encerradas'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Se estiver detalhando uma RNC -->
        <?php if ($rncDetalhe): ?>
            <div class="card shadow-sm mb-4 border-warning">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge badge-dark mr-2"><?= h($rncDetalhe['numero_rnc']) ?></span>
                        <strong><?= h($rncDetalhe['titulo']) ?></strong>
                    </div>
                    <div>
                        <a href="<?= APP_URL ?>sgq/nao-conformidades" class="btn btn-sm btn-outline-secondary">
                            <i class="fa-solid fa-times mr-1"></i> Fechar Detalhes
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <small class="text-muted d-block">Origem</small>
                            <strong><?= h($rncDetalhe['origem']) ?></strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">Severidade</small>
                            <span class="badge badge-<?= $rncDetalhe['severidade'] === 'CRITICA_IMPEDITIVA' ? 'danger' : 'warning' ?>">
                                <?= h($rncDetalhe['severidade']) ?>
                            </span>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">Embarcação / Cliente</small>
                            <strong><?= h($rncDetalhe['embarcacao_nome'] ?: 'N/D') ?></strong>
                            <small class="text-muted d-block"><?= h($rncDetalhe['cliente_nome'] ?: '') ?></small>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">Status do Ciclo</small>
                            <span class="badge badge-info"><?= h(str_replace('_', ' ', $rncDetalhe['status_ciclo_vida'])) ?></span>
                        </div>
                    </div>

                    <div class="bg-light p-3 rounded mb-3">
                        <small class="text-muted font-weight-bold d-block mb-1">Descrição Factual do Desvio:</small>
                        <p class="mb-0"><?= nl2br(h($rncDetalhe['descricao_detalhada'])) ?></p>
                    </div>

                    <!-- Análise de Causa Raiz (5 Porquês / Ishikawa) -->
                    <form action="<?= APP_URL ?>sgq/nao-conformidades/actions" method="POST" class="mb-4">
                        <input type="hidden" name="action" value="salvar_causa_raiz">
                        <input type="hidden" name="id" value="<?= h($rncDetalhe['id']) ?>">
                        <input type="hidden" name="csrf_token" value="<?= gerarCSRF() ?>">

                        <div class="form-group">
                            <label class="font-weight-bold">
                                <i class="fa-solid fa-magnifying-glass-chart mr-1"></i> Análise de Causa Raiz (5 Porquês / Ishikawa - ISO 10.2):
                            </label>
                            <textarea name="analise_causa_raiz" class="form-control" rows="3" placeholder="Descreva o método dos 5 Porquês ou espinha de peixe para determinar a causa primária da não conformidade..."><?= h($rncDetalhe['analise_causa_raiz'] ?? '') ?></textarea>
                        </div>

                        <div class="d-flex justify-content-between">
                            <div class="form-inline">
                                <label class="mr-2 font-weight-bold">Mudar Status:</label>
                                <select name="status_ciclo_vida" class="form-control form-control-sm mr-2">
                                    <?php foreach (['ABERTA','EM_ANALISE_CAUSA','PLANO_ACAO_DEFINIDO','EM_EXECUCAO','AGUARDANDO_EFICACIA','ENCERRADA_EFICAZ'] as $st): ?>
                                        <option value="<?= $st ?>" <?= $rncDetalhe['status_ciclo_vida'] === $st ? 'selected' : '' ?>><?= str_replace('_', ' ', $st) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="fa-solid fa-save mr-1"></i> Salvar Análise de Causa
                            </button>
                        </div>
                    </form>

                    <!-- Planos de Ação 5W2H -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="font-weight-bold mb-0"><i class="fa-solid fa-list-check text-primary mr-2"></i> Planos de Ação 5W2H (Ações Corretivas)</h5>
                        <button type="button" class="btn btn-sm btn-success" data-toggle="modal" data-target="#modalNovoPlano5w2h">
                            <i class="fa-solid fa-plus mr-1"></i> Adicionar Ação 5W2H
                        </button>
                    </div>

                    <?php if (empty($planos5w2h)): ?>
                        <div class="alert alert-info mb-0">Nenhuma ação 5W2H cadastrada para esta Não Conformidade ainda.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="thead-light">
                                    <tr>
                                        <th>O que (What)</th>
                                        <th>Por que (Why)</th>
                                        <th>Quem (Who)</th>
                                        <th>Quando (When)</th>
                                        <th>Como (How)</th>
                                        <th>Status</th>
                                        <th>Ação</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($planos5w2h as $p): ?>
                                        <tr>
                                            <td><strong><?= h($p['o_que_fazer_what']) ?></strong></td>
                                            <td><small class="text-muted"><?= h($p['por_que_fazer_why'] ?: '-') ?></small></td>
                                            <td><?= h($p['quem_fara_who']) ?></td>
                                            <td><?= formatarData($p['quando_fara_when']) ?></td>
                                            <td><small><?= h($p['como_fazer_how'] ?: '-') ?></small></td>
                                            <td>
                                                <span class="badge badge-<?= $p['status_acao'] === 'CONCLUIDA' ? 'success' : ($p['status_acao'] === 'EM_ANDAMENTO' ? 'warning' : 'secondary') ?>">
                                                    <?= h($p['status_acao']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($p['status_acao'] !== 'CONCLUIDA'): ?>
                                                    <form action="<?= APP_URL ?>sgq/nao-conformidades/actions" method="POST" style="display:inline;">
                                                        <input type="hidden" name="action" value="concluir_acao_5w2h">
                                                        <input type="hidden" name="plano_id" value="<?= h($p['id']) ?>">
                                                        <input type="hidden" name="rnc_id" value="<?= h($rncDetalhe['id']) ?>">
                                                        <input type="hidden" name="csrf_token" value="<?= gerarCSRF() ?>">
                                                        <button type="submit" class="btn btn-xs btn-success" title="Marcar como Concluída">
                                                            <i class="fa-solid fa-check"></i> Concluir
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="text-success small"><i class="fa-solid fa-check-double"></i> Eficaz</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Filtros e Busca -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="form-row align-items-end">
                    <div class="form-group col-md-3 mb-2">
                        <label class="small font-weight-bold">Busca textual</label>
                        <input type="text" name="busca" class="form-control form-control-sm" placeholder="Número, título ou embarcação..." value="<?= h($busca) ?>">
                    </div>
                    <div class="form-group col-md-2 mb-2">
                        <label class="small font-weight-bold">Status do Ciclo</label>
                        <select name="status" class="form-control form-control-sm">
                            <option value="">Todos</option>
                            <?php foreach (['ABERTA','EM_ANALISE_CAUSA','PLANO_ACAO_DEFINIDO','EM_EXECUCAO','AGUARDANDO_EFICACIA','ENCERRADA_EFICAZ'] as $st): ?>
                                <option value="<?= $st ?>" <?= $filtroStatus === $st ? 'selected' : '' ?>><?= str_replace('_', ' ', $st) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-2 mb-2">
                        <label class="small font-weight-bold">Origem</label>
                        <select name="origem" class="form-control form-control-sm">
                            <option value="">Todas</option>
                            <option value="AUDITORIA_INTERNA_RT" <?= $filtroOrigem === 'AUDITORIA_INTERNA_RT' ? 'selected' : '' ?>>Auditoria Interna (RT / Trava A/S)</option>
                            <option value="INSPECAO_CAMPO" <?= $filtroOrigem === 'INSPECAO_CAMPO' ? 'selected' : '' ?>>Inspeção de Campo</option>
                            <option value="RECLAMACAO_CLIENTE" <?= $filtroOrigem === 'RECLAMACAO_CLIENTE' ? 'selected' : '' ?>>Reclamação de Cliente</option>
                            <option value="AUDITORIA_EXTERNA" <?= $filtroOrigem === 'AUDITORIA_EXTERNA' ? 'selected' : '' ?>>Auditoria Externa (DPC/Marinha)</option>
                        </select>
                    </div>
                    <div class="form-group col-md-2 mb-2">
                        <label class="small font-weight-bold">Severidade</label>
                        <select name="severidade" class="form-control form-control-sm">
                            <option value="">Todas</option>
                            <option value="CRITICA_IMPEDITIVA" <?= $filtroSeveridade === 'CRITICA_IMPEDITIVA' ? 'selected' : '' ?>>Crítica (Impeditiva)</option>
                            <option value="MEDIA" <?= $filtroSeveridade === 'MEDIA' ? 'selected' : '' ?>>Média</option>
                            <option value="BAIXA" <?= $filtroSeveridade === 'BAIXA' ? 'selected' : '' ?>>Baixa</option>
                        </select>
                    </div>
                    <div class="form-group col-md-3 mb-2">
                        <button type="submit" class="btn btn-sm btn-primary mr-2"><i class="fa-solid fa-filter mr-1"></i> Filtrar</button>
                        <a href="<?= APP_URL ?>sgq/nao-conformidades" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-xmark mr-1"></i> Limpar</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabela Principal de RNCs -->
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Número</th>
                                <th>Título / Assunto</th>
                                <th>Origem</th>
                                <th>Embarcação</th>
                                <th>Severidade</th>
                                <th>Status</th>
                                <th>5W2H</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rncs)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">Nenhuma Não Conformidade encontrada para os filtros aplicados.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($rncs as $r): ?>
                                    <tr>
                                        <td><strong><?= h($r['numero_rnc']) ?></strong></td>
                                        <td>
                                            <strong><?= h($r['titulo']) ?></strong>
                                            <small class="text-muted d-block">Aberta em: <?= formatarData($r['data_identificacao']) ?></small>
                                        </td>
                                        <td><small><?= h(str_replace('_', ' ', $r['origem'])) ?></small></td>
                                        <td><?= h($r['embarcacao_nome'] ?: 'N/D') ?></td>
                                        <td>
                                            <span class="badge badge-<?= $r['severidade'] === 'CRITICA_IMPEDITIVA' ? 'danger' : 'warning' ?>">
                                                <?= h($r['severidade']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?= $r['status_ciclo_vida'] === 'ENCERRADA_EFICAZ' ? 'success' : 'info' ?>">
                                                <?= h(str_replace('_', ' ', $r['status_ciclo_vida'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small><?= (int)$r['acoes_concluidas'] ?>/<?= (int)$r['total_acoes_5w2h'] ?> ações</small>
                                        </td>
                                        <td>
                                            <a href="<?= APP_URL ?>sgq/nao-conformidades?id=<?= urlencode($r['id']) ?>" class="btn btn-xs btn-outline-primary">
                                                <i class="fa-solid fa-eye mr-1"></i> Detalhar
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modal Nova RNC Manual -->
<div class="modal fade" id="modalNovaRnc" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form action="<?= APP_URL ?>sgq/nao-conformidades/actions" method="POST" class="modal-content">
            <input type="hidden" name="action" value="criar_rnc">
            <input type="hidden" name="csrf_token" value="<?= gerarCSRF() ?>">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold"><i class="fa-solid fa-triangle-exclamation text-warning mr-2"></i> Abrir Nova Não Conformidade (RNC)</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group col-8 col-md-8">
                        <label class="font-weight-bold">Título do Desvio *</label>
                        <input type="text" name="titulo" class="form-control" required placeholder="Ex.: Falha na vedação da antepara estanque...">
                    </div>
                    <div class="form-group col-4 col-md-4">
                        <label class="font-weight-bold">Severidade *</label>
                        <select name="severidade" class="form-control" required>
                            <option value="CRITICA_IMPEDITIVA">Crítica (Impeditiva)</option>
                            <option value="MEDIA" selected>Média</option>
                            <option value="BAIXA">Baixa</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-6 col-md-6">
                        <label class="font-weight-bold">Origem *</label>
                        <select name="origem" class="form-control" required>
                            <option value="AUDITORIA_INTERNA_RT">Auditoria Interna (RT)</option>
                            <option value="INSPECAO_CAMPO" selected>Inspeção de Campo</option>
                            <option value="RECLAMACAO_CLIENTE">Reclamação de Cliente (ISO 9.1.2)</option>
                            <option value="AUDITORIA_EXTERNA">Auditoria Externa (NORMAM / Marinha)</option>
                        </select>
                    </div>
                    <div class="form-group col-6 col-md-6">
                        <label class="font-weight-bold">Prazo Previsto de Resolução *</label>
                        <input type="date" name="data_conclusao_prevista" class="form-control" required min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold">Descrição Factual da Não Conformidade *</label>
                    <textarea name="descricao_detalhada" class="form-control" rows="3" required placeholder="Detalhe a evidência física, não conformidade com a norma ou reclamação apresentada..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save mr-1"></i> Abrir RNC</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Novo Plano 5W2H -->
<?php if ($rncDetalhe): ?>
<div class="modal fade" id="modalNovoPlano5w2h" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form action="<?= APP_URL ?>sgq/nao-conformidades/actions" method="POST" class="modal-content">
            <input type="hidden" name="action" value="adicionar_plano_5w2h">
            <input type="hidden" name="nao_conformidade_id" value="<?= h($rncDetalhe['id']) ?>">
            <input type="hidden" name="csrf_token" value="<?= gerarCSRF() ?>">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold"><i class="fa-solid fa-list-check text-primary mr-2"></i> Adicionar Ação 5W2H</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="font-weight-bold">O que fazer? (What) *</label>
                    <input type="text" name="o_que_fazer_what" class="form-control" required placeholder="Ação corretiva imediata ou de contenção...">
                </div>
                <div class="form-row">
                    <div class="form-group col-6 col-md-6">
                        <label class="font-weight-bold">Por que fazer? (Why)</label>
                        <input type="text" name="por_que_fazer_why" class="form-control" placeholder="Justificativa técnica...">
                    </div>
                    <div class="form-group col-6 col-md-6">
                        <label class="font-weight-bold">Onde executar? (Where)</label>
                        <input type="text" name="onde_fazer_where" class="form-control" placeholder="Local da ação (ex: A bordo, Oficina...)">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-6 col-md-6">
                        <label class="font-weight-bold">Quem executará? (Who) *</label>
                        <input type="text" name="quem_fara_who" class="form-control" required placeholder="Responsável pela ação...">
                    </div>
                    <div class="form-group col-6 col-md-6">
                        <label class="font-weight-bold">Quando concluir? (When) *</label>
                        <input type="date" name="quando_fara_when" class="form-control" required min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d', strtotime('+15 days')) ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-8 col-md-8">
                        <label class="font-weight-bold">Como será executado? (How)</label>
                        <input type="text" name="como_fazer_how" class="form-control" placeholder="Método ou procedimento operacional...">
                    </div>
                    <div class="form-group col-4 col-md-4">
                        <label class="font-weight-bold">Custo Estimado (How much)</label>
                        <input type="number" step="0.01" name="quanto_custa_how_much" class="form-control" value="0.00">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success"><i class="fa-solid fa-plus mr-1"></i> Salvar Ação 5W2H</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
