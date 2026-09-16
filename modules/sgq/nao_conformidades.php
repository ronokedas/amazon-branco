<?php
/**
 * MÓDULO: SGQ - GESTÃO DA QUALIDADE (ISO 9001:2015 & NORMAM)
 * Arquivo: modules/sgq/nao_conformidades.php
 * Gestão de Ocorrências, Não Conformidades (RNC) e Ações Corretivas
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
exigirAcesso('sgq');

$usuarioId = $_SESSION['usuario_id'] ?? '';
$cargo = getCargo();

// Filtros
$filtroStatus = trim((string)($_GET['status'] ?? ''));
$filtroOrigem = trim((string)($_GET['origem'] ?? ''));
$filtroSeveridade = trim((string)($_GET['severidade'] ?? ''));
$busca = trim((string)($_GET['busca'] ?? ''));
$detalheId = trim((string)($_GET['id'] ?? ($_GET['detalhe'] ?? '')));

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
    $whereSql .= " AND (r.numero_rnc LIKE :busca1 OR r.titulo LIKE :busca2 OR e.nome LIKE :busca3)";
    $termoBusca = '%' . $busca . '%';
    $params[':busca1'] = $termoBusca;
    $params[':busca2'] = $termoBusca;
    $params[':busca3'] = $termoBusca;
}

// Consultar RNCs
$sql = "
    SELECT r.*, 
           e.nome AS embarcacao_nome,
           c.nome AS cliente_nome,
           os.numero AS os_numero,
           (SELECT COUNT(*) FROM sgq_planos_acao pa WHERE pa.nao_conformidade_id = r.id) AS total_acoes,
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

// Mapas de rótulos amigáveis
$origemLabels = [
    'RECLAMACAO_CLIENTE' => 'Reclamação de Cliente',
    'INSPECAO_CAMPO' => 'Inspeção de Campo',
    'AUDITORIA_INTERNA_RT' => 'Auditoria Interna (RT)',
    'AUDITORIA_EXTERNA' => 'Auditoria Externa (NORMAM / Marinha)',
];

$statusLabels = [
    'ABERTA' => 'Aberta (Pendente)',
    'EM_ANALISE_CAUSA' => 'Em Análise de Causa',
    'PLANO_ACAO_DEFINIDO' => 'Plano de Ação Definido',
    'EM_EXECUCAO' => 'Em Execução',
    'AGUARDANDO_EFICACIA' => 'Aguardando Avaliação',
    'ENCERRADA_EFICAZ' => 'Concluída & Resolvida',
];

$severidadeLabels = [
    'CRITICA_IMPEDITIVA' => ['label' => 'Crítica (Impeditiva)', 'color' => '#dc2626', 'bg' => '#fef2f2', 'border' => '#fecaca'],
    'MEDIA' => ['label' => 'Média', 'color' => '#d97706', 'bg' => '#fffbeb', 'border' => '#fde68a'],
    'BAIXA' => ['label' => 'Baixa', 'color' => '#2563eb', 'bg' => '#eff6ff', 'border' => '#bfdbfe'],
];

// Detalhe selecionado
$rncDetalhe = null;
$planosAcao = [];
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
        $planosAcao = $stmtPlanos->fetchAll(PDO::FETCH_ASSOC);
    }
}

$titulo_page = 'Gestão de Ocorrências & Não Conformidades (RNC)';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<div class="conteudo-principal" style="padding: 24px; max-width: 1300px; margin: 0 auto;">

    <!-- Cabeçalho da Página -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div>
            <h2 style="margin: 0; font-size: 1.45rem; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-triangle-exclamation" style="color: #f59e0b;"></i> Gestão de Ocorrências & Não Conformidades (RNC)
            </h2>
            <p style="margin: 4px 0 0; color: #64748b; font-size: 14px;">
                Registro de desvios operacionais, manifestações de clientes e controle de ações corretivas.
            </p>
        </div>
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalNovaRnc" style="display: inline-flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-plus"></i> Nova Ocorrência (RNC)
        </button>
    </div>

    <!-- Indicadores Rápidos -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div class="card" style="padding: 16px 20px; border-left: 4px solid #2563eb; background: #ffffff; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px;">Total de Ocorrências</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #1e293b; margin: 4px 0;"><?= (int)($totais['total'] ?? 0) ?></div>
            <small style="color: #94a3b8; font-size: 12px;">Histórico geral registrado</small>
        </div>
        <div class="card" style="padding: 16px 20px; border-left: 4px solid #ef4444; background: #ffffff; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #ef4444; letter-spacing: 0.5px;">Abertas (Pendentes)</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #ef4444; margin: 4px 0;"><?= (int)($totais['abertas'] ?? 0) ?></div>
            <small style="color: #94a3b8; font-size: 12px;">Aguardando ação da equipe</small>
        </div>
        <div class="card" style="padding: 16px 20px; border-left: 4px solid #f59e0b; background: #ffffff; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #d97706; letter-spacing: 0.5px;">Em Tratamento</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #d97706; margin: 4px 0;"><?= (int)($totais['em_andamento'] ?? 0) ?></div>
            <small style="color: #94a3b8; font-size: 12px;">Com ações em andamento</small>
        </div>
        <div class="card" style="padding: 16px 20px; border-left: 4px solid #10b981; background: #ffffff; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #059669; letter-spacing: 0.5px;">Concluídas & Resolvidas</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #059669; margin: 4px 0;"><?= (int)($totais['encerradas'] ?? 0) ?></div>
            <small style="color: #94a3b8; font-size: 12px;">Finalizadas com sucesso</small>
        </div>
    </div>

    <!-- Se estiver detalhando uma RNC -->
    <?php if ($rncDetalhe): ?>
        <div class="card" style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); margin-bottom: 28px; overflow: hidden;">
            <!-- Cabeçalho do Card de Detalhes -->
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 18px 24px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; flex-wrap: wrap; gap: 12px;">
                <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                    <span style="background: #0f172a; color: #ffffff; padding: 5px 12px; border-radius: 6px; font-weight: 700; font-size: 13px; letter-spacing: 0.5px;">
                        <?= h($rncDetalhe['numero_rnc']) ?>
                    </span>
                    <span style="font-size: 1.15rem; font-weight: 700; color: #1e293b;">
                        <?= h($rncDetalhe['titulo']) ?>
                    </span>
                </div>
                <a href="<?= APP_URL ?>sgq/nao-conformidades" class="btn btn-outline-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-xmark"></i> Fechar Detalhes
                </a>
            </div>

            <div style="padding: 24px;">
                <!-- Grid 4 colunas de informações gerais -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
                    <div style="background: #f8fafc; padding: 14px 16px; border-radius: 8px; border: 1px solid #e2e8f0;">
                        <span style="font-size: 12px; font-weight: 600; color: #64748b; display: block; margin-bottom: 4px;">Origem do Registro</span>
                        <strong style="color: #1e293b; font-size: 14px;"><?= h($origemLabels[$rncDetalhe['origem']] ?? str_replace('_', ' ', $rncDetalhe['origem'])) ?></strong>
                    </div>
                    <div style="background: #f8fafc; padding: 14px 16px; border-radius: 8px; border: 1px solid #e2e8f0;">
                        <span style="font-size: 12px; font-weight: 600; color: #64748b; display: block; margin-bottom: 6px;">Severidade</span>
                        <?php $sev = $severidadeLabels[$rncDetalhe['severidade']] ?? ['label' => $rncDetalhe['severidade'], 'color' => '#64748b', 'bg' => '#f1f5f9', 'border' => '#e2e8f0']; ?>
                        <span style="background: <?= $sev['bg'] ?>; color: <?= $sev['color'] ?>; border: 1px solid <?= $sev['border'] ?>; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; display: inline-block;">
                            <?= h($sev['label']) ?>
                        </span>
                    </div>
                    <div style="background: #f8fafc; padding: 14px 16px; border-radius: 8px; border: 1px solid #e2e8f0;">
                        <span style="font-size: 12px; font-weight: 600; color: #64748b; display: block; margin-bottom: 4px;">Embarcação & Cliente</span>
                        <strong style="color: #1e293b; font-size: 14px; display: block;"><?= h($rncDetalhe['embarcacao_nome'] ?: 'Não informada') ?></strong>
                        <small style="color: #64748b; font-size: 12px;"><?= h($rncDetalhe['cliente_nome'] ?: 'Sem cliente vinculado') ?></small>
                    </div>
                    <div style="background: #f8fafc; padding: 14px 16px; border-radius: 8px; border: 1px solid #e2e8f0;">
                        <span style="font-size: 12px; font-weight: 600; color: #64748b; display: block; margin-bottom: 6px;">Status do Processo</span>
                        <span style="background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; display: inline-block;">
                            <?= h($statusLabels[$rncDetalhe['status_ciclo_vida']] ?? str_replace('_', ' ', $rncDetalhe['status_ciclo_vida'])) ?>
                        </span>
                    </div>
                </div>

                <!-- Box: Descrição / Relato Registrado -->
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 18px 20px; margin-bottom: 24px;">
                    <div style="font-size: 13px; font-weight: 700; color: #475569; margin-bottom: 8px; display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-file-lines" style="color: #08a774;"></i> Descrição do Ocorrido / Relato da Manifestação:
                    </div>
                    <div style="font-size: 14px; color: #1e293b; line-height: 1.6; white-space: pre-line;"><?= h($rncDetalhe['descricao_detalhada']) ?></div>
                </div>

                <!-- Box: Diagnóstico da Causa do Problema -->
                <form action="<?= APP_URL ?>sgq/nao-conformidades/actions" method="POST" style="background: #ffffff; border: 1px solid #d1d5db; border-radius: 10px; padding: 20px; margin-bottom: 28px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                    <input type="hidden" name="action" value="salvar_causa_raiz">
                    <input type="hidden" name="id" value="<?= h($rncDetalhe['id']) ?>">
                    <input type="hidden" name="csrf_token" value="<?= gerarCSRF() ?>">

                    <div style="margin-bottom: 14px;">
                        <label style="font-size: 14px; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                            <i class="fa-solid fa-magnifying-glass" style="color: #08a774;"></i> Diagnóstico e Motivo do Desvio:
                        </label>
                        <p style="margin: 0 0 8px; color: #64748b; font-size: 13px;">
                            Descreva a causa principal identificada pela equipe técnica para orientar as ações corretivas.
                        </p>
                        <textarea name="analise_causa_raiz" class="form-control" rows="3" placeholder="Explique aqui o motivo que ocasionou este problema ou falha operacional..."><?= h($rncDetalhe['analise_causa_raiz'] ?? '') ?></textarea>
                    </div>

                    <!-- Barra de Ações: Status à esquerda e Salvar à direita (perfeitamente alinhados) -->
                    <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 14px; border-top: 1px solid #f1f5f9; flex-wrap: wrap; gap: 14px;">
                        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                            <label style="font-size: 13px; font-weight: 700; color: #334155; margin: 0;">Atualizar Status:</label>
                            <select name="status_ciclo_vida" class="form-control" style="width: auto; min-width: 240px; height: 40px; font-size: 13px;">
                                <?php foreach ($statusLabels as $stKey => $stName): ?>
                                    <option value="<?= $stKey ?>" <?= $rncDetalhe['status_ciclo_vida'] === $stKey ? 'selected' : '' ?>><?= $stName ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px; padding: 9px 22px;">
                            <i class="fa-solid fa-floppy-disk"></i> Salvar Diagnóstico e Status
                        </button>
                    </div>
                </form>

                <!-- Seção de Ações Corretivas -->
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
                        <div>
                            <h4 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                                <i class="fa-solid fa-clipboard-check" style="color: #08a774;"></i> Ações Corretivas & Plano de Resolução
                            </h4>
                            <p style="margin: 3px 0 0; color: #64748b; font-size: 13px;">
                                Medidas práticas definidas para solucionar a ocorrência e evitar repetição.
                            </p>
                        </div>
                        <button type="button" class="btn btn-success" data-toggle="modal" data-target="#modalNovoPlano5w2h" style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 18px;">
                            <i class="fa-solid fa-plus"></i> Adicionar Ação
                        </button>
                    </div>

                    <?php if (empty($planosAcao)): ?>
                        <div style="text-align: center; padding: 36px 20px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 12px;">
                            <i class="fa-solid fa-clipboard-list" style="font-size: 36px; color: #94a3b8; margin-bottom: 12px; display: block;"></i>
                            <p style="margin: 0 0 14px; font-weight: 600; color: #475569; font-size: 14px;">Nenhuma ação corretiva cadastrada para esta ocorrência ainda.</p>
                            <button type="button" class="btn btn-outline-success btn-sm" data-toggle="modal" data-target="#modalNovoPlano5w2h" style="display: inline-flex; align-items: center; gap: 6px;">
                                <i class="fa-solid fa-plus"></i> Cadastrar Primeira Ação
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive" style="border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
                            <table class="table mb-0" style="width: 100%; border-collapse: collapse;">
                                <thead style="background: #f1f5f9;">
                                    <tr>
                                        <th style="padding: 10px 14px; font-size: 12px; font-weight: 700; color: #334155; text-align: left;">Ação a Realizar</th>
                                        <th style="padding: 10px 14px; font-size: 12px; font-weight: 700; color: #334155; text-align: left;">Motivo / Justificativa</th>
                                        <th style="padding: 10px 14px; font-size: 12px; font-weight: 700; color: #334155; text-align: left;">Responsável</th>
                                        <th style="padding: 10px 14px; font-size: 12px; font-weight: 700; color: #334155; text-align: left;">Prazo Limite</th>
                                        <th style="padding: 10px 14px; font-size: 12px; font-weight: 700; color: #334155; text-align: left;">Como Executar</th>
                                        <th style="padding: 10px 14px; font-size: 12px; font-weight: 700; color: #334155; text-align: center;">Status</th>
                                        <th style="padding: 10px 14px; font-size: 12px; font-weight: 700; color: #334155; text-align: center;">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($planosAcao as $p): ?>
                                        <tr style="border-top: 1px solid #f1f5f9;">
                                            <td style="padding: 12px 14px; font-size: 13px; font-weight: 600; color: #0f172a;"><?= h($p['o_que_fazer_what']) ?></td>
                                            <td style="padding: 12px 14px; font-size: 13px; color: #64748b;"><?= h($p['por_que_fazer_why'] ?: '-') ?></td>
                                            <td style="padding: 12px 14px; font-size: 13px; color: #334155; font-weight: 500;"><?= h($p['quem_fara_who']) ?></td>
                                            <td style="padding: 12px 14px; font-size: 13px; color: #334155; white-space: nowrap;"><?= formatarData($p['quando_fara_when']) ?></td>
                                            <td style="padding: 12px 14px; font-size: 13px; color: #64748b;"><?= h($p['como_fazer_how'] ?: '-') ?></td>
                                            <td style="padding: 12px 14px; text-align: center;">
                                                <?php if ($p['status_acao'] === 'CONCLUIDA'): ?>
                                                    <span style="background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 700;">Concluída</span>
                                                <?php else: ?>
                                                    <span style="background: #fffbeb; color: #d97706; border: 1px solid #fde68a; padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 700;">Em Andamento</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 12px 14px; text-align: center;">
                                                <?php if ($p['status_acao'] !== 'CONCLUIDA'): ?>
                                                    <form action="<?= APP_URL ?>sgq/nao-conformidades/actions" method="POST" style="display:inline; margin: 0;">
                                                        <input type="hidden" name="action" value="concluir_acao_5w2h">
                                                        <input type="hidden" name="plano_id" value="<?= h($p['id']) ?>">
                                                        <input type="hidden" name="rnc_id" value="<?= h($rncDetalhe['id']) ?>">
                                                        <input type="hidden" name="csrf_token" value="<?= gerarCSRF() ?>">
                                                        <button type="submit" class="btn btn-xs btn-outline-success" title="Marcar como Concluída" style="padding: 4px 10px; font-size: 12px;">
                                                            <i class="fa-solid fa-check mr-1"></i> Concluir
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span style="color: #16a34a; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                                                        <i class="fa-solid fa-circle-check"></i> Concluída
                                                    </span>
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
        </div>
    <?php endif; ?>

    <!-- Abas de Seleção Rápida de Origem -->
    <div style="display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap;">
        <a href="<?= APP_URL ?>sgq/nao-conformidades" class="btn btn-sm <?= empty($filtroOrigem) ? 'btn-primary' : 'btn-outline-secondary' ?>" style="display: inline-flex; align-items: center; gap: 6px; border-radius: 20px; padding: 6px 16px;">
            <i class="fa-solid fa-list-check"></i> Todas as Ocorrências
        </a>
        <a href="<?= APP_URL ?>sgq/nao-conformidades?origem=RECLAMACAO_CLIENTE" class="btn btn-sm <?= $filtroOrigem === 'RECLAMACAO_CLIENTE' ? 'btn-primary' : 'btn-outline-secondary' ?>" style="display: inline-flex; align-items: center; gap: 6px; border-radius: 20px; padding: 6px 16px;">
            <i class="fa-solid fa-headset"></i> Reclamações de Clientes (Ouvidoria)
        </a>
        <a href="<?= APP_URL ?>sgq/nao-conformidades?origem=INSPECAO_CAMPO" class="btn btn-sm <?= $filtroOrigem === 'INSPECAO_CAMPO' ? 'btn-primary' : 'btn-outline-secondary' ?>" style="display: inline-flex; align-items: center; gap: 6px; border-radius: 20px; padding: 6px 16px;">
            <i class="fa-solid fa-clipboard-check"></i> Inspeções de Campo
        </a>
        <a href="<?= APP_URL ?>sgq/nao-conformidades?origem=AUDITORIA_INTERNA_RT" class="btn btn-sm <?= $filtroOrigem === 'AUDITORIA_INTERNA_RT' ? 'btn-primary' : 'btn-outline-secondary' ?>" style="display: inline-flex; align-items: center; gap: 6px; border-radius: 20px; padding: 6px 16px;">
            <i class="fa-solid fa-shield-check"></i> Auditorias Internas (RT)
        </a>
    </div>

    <!-- Filtros e Busca -->
    <div class="card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 18px 20px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
        <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)) auto; gap: 14px; align-items: flex-end;">
            <div>
                <label style="font-size: 12px; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Busca Textual</label>
                <input type="text" name="busca" class="form-control" placeholder="Número da RNC, assunto ou embarcação..." value="<?= h($busca) ?>">
            </div>
            <div>
                <label style="font-size: 12px; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Status do Processo</label>
                <select name="status" class="form-control">
                    <option value="">Todos os Status</option>
                    <?php foreach ($statusLabels as $stKey => $stName): ?>
                        <option value="<?= $stKey ?>" <?= $filtroStatus === $stKey ? 'selected' : '' ?>><?= $stName ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label style="font-size: 12px; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Origem do Registro</label>
                <select name="origem" class="form-control">
                    <option value="">Todas as Origens</option>
                    <?php foreach ($origemLabels as $orKey => $orName): ?>
                        <option value="<?= $orKey ?>" <?= $filtroOrigem === $orKey ? 'selected' : '' ?>><?= $orName ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label style="font-size: 12px; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Severidade</label>
                <select name="severidade" class="form-control">
                    <option value="">Todas as Severidades</option>
                    <?php foreach ($severidadeLabels as $svKey => $svData): ?>
                        <option value="<?= $svKey ?>" <?= $filtroSeveridade === $svKey ? 'selected' : '' ?>><?= $svData['label'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display: flex; gap: 8px; align-items: center;">
                <button type="submit" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 6px; padding: 9px 18px;">
                    <i class="fa-solid fa-filter"></i> Filtrar
                </button>
                <a href="<?= APP_URL ?>sgq/nao-conformidades" class="btn btn-outline-secondary" style="display: inline-flex; align-items: center; gap: 6px; padding: 9px 16px;">
                    <i class="fa-solid fa-xmark"></i> Limpar
                </a>
            </div>
        </form>
    </div>

    <!-- Tabela Principal de Ocorrências -->
    <div class="card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="width: 100%; border-collapse: collapse;">
                <thead style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                    <tr>
                        <th style="padding: 12px 16px; font-size: 12px; font-weight: 700; color: #475569; text-align: left;">Número RNC</th>
                        <th style="padding: 12px 16px; font-size: 12px; font-weight: 700; color: #475569; text-align: left;">Título / Assunto</th>
                        <th style="padding: 12px 16px; font-size: 12px; font-weight: 700; color: #475569; text-align: left;">Origem</th>
                        <th style="padding: 12px 16px; font-size: 12px; font-weight: 700; color: #475569; text-align: left;">Embarcação / Cliente</th>
                        <th style="padding: 12px 16px; font-size: 12px; font-weight: 700; color: #475569; text-align: center;">Severidade</th>
                        <th style="padding: 12px 16px; font-size: 12px; font-weight: 700; color: #475569; text-align: center;">Status</th>
                        <th style="padding: 12px 16px; font-size: 12px; font-weight: 700; color: #475569; text-align: center;">Ações</th>
                        <th style="padding: 12px 16px; font-size: 12px; font-weight: 700; color: #475569; text-align: center;">Opções</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rncs)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 36px 20px; color: #64748b;">
                                <i class="fa-solid fa-inbox" style="font-size: 32px; color: #cbd5e1; margin-bottom: 8px; display: block;"></i>
                                Nenhuma ocorrência encontrada para os filtros selecionados.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rncs as $r): ?>
                            <tr style="border-top: 1px solid #f1f5f9;">
                                <td style="padding: 14px 16px; font-size: 13px;">
                                    <span style="font-weight: 700; color: #0f172a;"><?= h($r['numero_rnc']) ?></span>
                                </td>
                                <td style="padding: 14px 16px; font-size: 13px;">
                                    <strong style="color: #1e293b; display: block;"><?= h($r['titulo']) ?></strong>
                                    <small style="color: #64748b;">Aberta em: <?= formatarData($r['data_identificacao']) ?></small>
                                </td>
                                <td style="padding: 14px 16px; font-size: 13px; color: #334155;">
                                    <?= h($origemLabels[$r['origem']] ?? str_replace('_', ' ', $r['origem'])) ?>
                                </td>
                                <td style="padding: 14px 16px; font-size: 13px; color: #1e293b;">
                                    <strong><?= h($r['embarcacao_nome'] ?: 'Sem embarcação') ?></strong>
                                    <?php if (!empty($r['cliente_nome'])): ?>
                                        <small style="display: block; color: #0284c7; margin-top: 2px; font-weight: 600;">
                                            <i class="fa-solid fa-user"></i> <?= h($r['cliente_nome']) ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 14px 16px; text-align: center;">
                                    <?php $sevR = $severidadeLabels[$r['severidade']] ?? ['label' => $r['severidade'], 'color' => '#64748b', 'bg' => '#f1f5f9', 'border' => '#e2e8f0']; ?>
                                    <span style="background: <?= $sevR['bg'] ?>; color: <?= $sevR['color'] ?>; border: 1px solid <?= $sevR['border'] ?>; padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; display: inline-block;">
                                        <?= h($sevR['label']) ?>
                                    </span>
                                </td>
                                <td style="padding: 14px 16px; text-align: center;">
                                    <?php $isResolvida = ($r['status_ciclo_vida'] === 'ENCERRADA_EFICAZ'); ?>
                                    <span style="background: <?= $isResolvida ? '#f0fdf4' : '#eff6ff' ?>; color: <?= $isResolvida ? '#15803d' : '#1d4ed8' ?>; border: 1px solid <?= $isResolvida ? '#bbf7d0' : '#bfdbfe' ?>; padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; display: inline-block;">
                                        <?= h($statusLabels[$r['status_ciclo_vida']] ?? str_replace('_', ' ', $r['status_ciclo_vida'])) ?>
                                    </span>
                                </td>
                                <td style="padding: 14px 16px; text-align: center; font-size: 12px; color: #64748b;">
                                    <?= (int)$r['acoes_concluidas'] ?>/<?= (int)$r['total_acoes'] ?> resolvidas
                                </td>
                                <td style="padding: 14px 16px; text-align: center;">
                                    <a href="<?= APP_URL ?>sgq/nao-conformidades?id=<?= urlencode($r['id']) ?>" class="btn btn-xs btn-outline-primary" style="display: inline-flex; align-items: center; gap: 6px;">
                                        <i class="fa-solid fa-eye"></i> Detalhar
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

<!-- Modal Nova RNC Manual -->
<div class="modal fade" id="modalNovaRnc" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form action="<?= APP_URL ?>sgq/nao-conformidades/actions" method="POST" class="modal-content">
            <input type="hidden" name="action" value="criar_rnc">
            <input type="hidden" name="csrf_token" value="<?= gerarCSRF() ?>">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold" style="display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-triangle-exclamation" style="color: #f59e0b;"></i>
                    Abrir Nova Ocorrência / Não Conformidade (RNC)
                </h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group col-8 col-md-8">
                        <label class="font-weight-bold">Título / Assunto da Ocorrência *</label>
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
                        <label class="font-weight-bold">Origem do Registro *</label>
                        <select name="origem" class="form-control" required>
                            <option value="INSPECAO_CAMPO" selected>Inspeção de Campo</option>
                            <option value="RECLAMACAO_CLIENTE">Reclamação de Cliente</option>
                            <option value="AUDITORIA_INTERNA_RT">Auditoria Interna (RT)</option>
                            <option value="AUDITORIA_EXTERNA">Auditoria Externa (NORMAM / Marinha)</option>
                        </select>
                    </div>
                    <div class="form-group col-6 col-md-6">
                        <label class="font-weight-bold">Prazo Limite para Resolução *</label>
                        <input type="date" name="data_conclusao_prevista" class="form-control" required min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold">Descrição Detalhada do Problema *</label>
                    <textarea name="descricao_detalhada" class="form-control" rows="4" required placeholder="Descreva os fatos ocorridos, evidências encontradas e os impactos identificados..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-floppy-disk"></i> Abrir Ocorrência
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Nova Ação Corretiva -->
<?php if ($rncDetalhe): ?>
<div class="modal fade" id="modalNovoPlano5w2h" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form action="<?= APP_URL ?>sgq/nao-conformidades/actions" method="POST" class="modal-content">
            <input type="hidden" name="action" value="adicionar_plano_5w2h">
            <input type="hidden" name="nao_conformidade_id" value="<?= h($rncDetalhe['id']) ?>">
            <input type="hidden" name="csrf_token" value="<?= gerarCSRF() ?>">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold" style="display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-clipboard-check" style="color: #08a774;"></i>
                    Adicionar Ação Corretiva
                </h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="font-weight-bold">O que será feito? (Ação) *</label>
                    <input type="text" name="o_que_fazer_what" class="form-control" required placeholder="Ex.: Realizar teste hidrostático e substituição da junta da escotilha...">
                </div>
                <div class="form-row">
                    <div class="form-group col-6 col-md-6">
                        <label class="font-weight-bold">Por qual motivo? (Justificativa)</label>
                        <input type="text" name="por_que_fazer_why" class="form-control" placeholder="Ex.: Garantir estanqueidade e conformidade com a NORMAM...">
                    </div>
                    <div class="form-group col-6 col-md-6">
                        <label class="font-weight-bold">Onde será executado? (Local)</label>
                        <input type="text" name="onde_fazer_where" class="form-control" placeholder="Ex.: Convés principal / Praça de máquinas...">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-6 col-md-6">
                        <label class="font-weight-bold">Responsável pela Execução *</label>
                        <input type="text" name="quem_fara_who" class="form-control" required placeholder="Ex.: Inspetor Naval João / Equipe de Reparo...">
                    </div>
                    <div class="form-group col-6 col-md-6">
                        <label class="font-weight-bold">Prazo Limite para Conclusão *</label>
                        <input type="date" name="quando_fara_when" class="form-control" required min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d', strtotime('+15 days')) ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-8 col-md-8">
                        <label class="font-weight-bold">Como será executado? (Instruções ou Procedimento)</label>
                        <input type="text" name="como_fazer_how" class="form-control" placeholder="Ex.: Seguir manual do fabricante com torqueamento de 45Nm...">
                    </div>
                    <div class="form-group col-4 col-md-4">
                        <label class="font-weight-bold">Custo Estimado (R$)</label>
                        <input type="number" step="0.01" name="quanto_custa_how_much" class="form-control" value="0.00">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success" style="display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-plus"></i> Registrar Ação
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
