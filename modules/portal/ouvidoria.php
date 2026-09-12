<?php
/**
 * MÓDULO: PORTAL DO CLIENTE - OUVIDORIA & GESTÃO DA QUALIDADE (ISO 9001:2015)
 * Arquivo: modules/portal/ouvidoria.php
 * Canal formal de manifestações, reclamações e melhoria contínua (ISO 8.2.1, 9.1.2 e 10.2)
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/cliente_portal.php';

requireClienteSenhaDefinitiva();

$clienteId = clientePortalId();
$clienteNome = clientePortalNome();
$embarcacoes = clientePortalEmbarcacoes($pdo, $clienteId);

// Consultar manifestações do cliente vinculadas ao SGQ
$stmt = $pdo->prepare("
    SELECT r.*, e.nome AS embarcacao_nome,
           (SELECT COUNT(*) FROM sgq_planos_acao pa WHERE pa.nao_conformidade_id = r.id) AS total_planos_5w2h,
           (SELECT COUNT(*) FROM sgq_planos_acao pa WHERE pa.nao_conformidade_id = r.id AND pa.status_acao = 'CONCLUIDA') AS planos_concluidos
    FROM sgq_nao_conformidades r
    LEFT JOIN embarcacoes e ON e.id = r.embarcacao_id
    WHERE r.cliente_id = :cli_id AND r.origem = 'RECLAMACAO_CLIENTE'
    ORDER BY r.criado_em DESC
");
$stmt->execute([':cli_id' => $clienteId]);
$manifestacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$acoesPorManifestacao = [];
$manifestacaoIds = array_column($manifestacoes, 'id');
if (!empty($manifestacaoIds)) {
    $inSql = implode(',', array_fill(0, count($manifestacaoIds), '?'));
    $stmtAcoes = $pdo->prepare("
        SELECT * FROM sgq_planos_acao 
        WHERE nao_conformidade_id IN ($inSql)
        ORDER BY quando_fara_when ASC, criado_em ASC
    ");
    $stmtAcoes->execute($manifestacaoIds);
    while ($acao = $stmtAcoes->fetch(PDO::FETCH_ASSOC)) {
        $acoesPorManifestacao[$acao['nao_conformidade_id']][] = $acao;
    }
}

$total = count($manifestacoes);
$abertas = 0;
$emTratamento = 0;
$encerradas = 0;

foreach ($manifestacoes as $m) {
    if ($m['status_ciclo_vida'] === 'ABERTA') {
        $abertas++;
    } elseif ($m['status_ciclo_vida'] === 'ENCERRADA_EFICAZ') {
        $encerradas++;
    } else {
        $emTratamento++;
    }
}

$titulo_page = 'Ouvidoria & Qualidade - ' . APP_NAME;
require_once __DIR__ . '/../../includes/portal_header.php';
?>

<section class="portal-page-header">
    <div>
        <h1>Ouvidoria & Qualidade</h1>
        <p>Canal oficial de comunicação, feedback e tratamento de reclamações em conformidade com a ISO 9001:2015.</p>
    </div>
    <div class="portal-page-header-mark">
        <i class="fa-solid fa-headset"></i>
    </div>
</section>

<!-- Banner de Conformidade ISO 9001 -->
<div class="portal-preserve" style="margin-bottom: 24px;">
    <i class="fa-solid fa-award"></i>
    <div>
        <strong>Compromisso com a Qualidade & Satisfação do Cliente (ISO 9001:2015)</strong>
        <span>
            Conforme as cláusulas <strong>8.2.1</strong> (Comunicação com o Cliente), <strong>9.1.2</strong> (Satisfação do Cliente) e <strong>10.2</strong> (Não Conformidade e Ação Corretiva), toda manifestação ou reclamação gera um protocolo oficial e é tratada com investigação técnica de causa raiz e plano de ação corretiva pela Diretoria Técnica da Amazon Certificadora.
        </span>
    </div>
</div>

<!-- Cards de Métricas de Manifestações -->
<div class="portal-metrics" style="margin-bottom: 24px;">
    <div class="portal-metric">
        <i class="fa-regular fa-clipboard"></i>
        <strong><?php echo $total; ?></strong>
        <span>Total de<br>manifestações</span>
    </div>
    <div class="portal-metric">
        <i class="fa-solid fa-hourglass-half" style="color: #d97706;"></i>
        <strong><?php echo $abertas; ?></strong>
        <span>Aguardando<br>análise inicial</span>
    </div>
    <div class="portal-metric">
        <i class="fa-solid fa-gears" style="color: #2563eb;"></i>
        <strong><?php echo $emTratamento; ?></strong>
        <span>Em tratamento<br>técnico (5W2H)</span>
    </div>
    <div class="portal-metric">
        <i class="fa-regular fa-circle-check" style="color: #059669;"></i>
        <strong><?php echo $encerradas; ?></strong>
        <span>Concluídas &<br>resolvidas</span>
    </div>
</div>

<div class="portal-analysis-layout">
    <!-- Formulário de Registro de Nova Manifestação -->
    <section class="portal-panel portal-analysis-main">
        <div class="portal-panel-header" style="margin-bottom: 20px;">
            <h2><i class="fa-solid fa-pen-to-square"></i> Registrar Nova Manifestação / Reclamação</h2>
        </div>

        <form method="POST" action="<?php echo APP_URL; ?>portal/ouvidoria/actions" class="portal-analysis-form">
            <input type="hidden" name="csrf_token" value="<?php echo h(gerarCSRF()); ?>">
            <input type="hidden" name="action" value="registrar_manifestacao">

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px;">
                <div class="form-group">
                    <label for="tipo_manifestacao"><i class="fa-solid fa-tag"></i> Tipo de Manifestação *</label>
                    <select id="tipo_manifestacao" name="tipo_manifestacao" required>
                        <option value="RECLAMACAO" selected>Reclamação Formal (ISO 10.2)</option>
                        <option value="SUGESTAO">Sugestão de Melhoria Contínua (ISO 10.3)</option>
                        <option value="DUVIDA">Dúvida / Esclarecimento Técnico</option>
                        <option value="ELOGIO">Elogio / Reconhecimento</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="classificacao_falha"><i class="fa-solid fa-folder-tree"></i> Categoria / Assunto *</label>
                    <select id="classificacao_falha" name="classificacao_falha" required>
                        <option value="Atraso de Prazo / Vistoria">Atraso de Prazo / Agendamento de Vistoria</option>
                        <option value="Qualidade Técnica / Laudo">Qualidade Técnica / Divergência em Laudo</option>
                        <option value="Atendimento Comercial">Atendimento Comercial / Suporte</option>
                        <option value="Erro em Documento / Certificado">Erro em Documento / Certificado Naval</option>
                        <option value="Financeiro / Faturamento">Financeiro / Cobrança / Faturamento</option>
                        <option value="Outros / Geral">Outros / Geral</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px;">
                <div class="form-group">
                    <label for="embarcacao_id"><i class="fa-solid fa-ship"></i> Embarcação Vinculada (opcional)</label>
                    <select id="embarcacao_id" name="embarcacao_id">
                        <option value="">Geral (não vinculada a uma embarcação específica)</option>
                        <?php foreach ($embarcacoes as $emb): ?>
                            <option value="<?php echo h($emb['id']); ?>">
                                <?php echo h($emb['nome'] . ($emb['registro'] ? ' - Reg: ' . $emb['registro'] : '')); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="severidade"><i class="fa-solid fa-triangle-exclamation"></i> Gravidade / Urgência *</label>
                    <select id="severidade" name="severidade" required>
                        <option value="MEDIA" selected>Normal (Tratamento padrão SGQ)</option>
                        <option value="CRITICA_IMPEDITIVA">Urgente (Afeta Despacho da Embarcação / Navegabilidade)</option>
                        <option value="BAIXA">Baixa (Informativo / Sem impacto operacional)</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="titulo"><i class="fa-solid fa-heading"></i> Assunto / Título Resumido *</label>
                <input type="text" id="titulo" name="titulo" required placeholder="Ex.: Divergência nas dimensões registradas no laudo da balsa" maxlength="200">
            </div>

            <div class="form-group">
                <label for="descricao"><i class="fa-solid fa-align-left"></i> Relato Detalhado dos Fatos *</label>
                <textarea id="descricao" name="descricao" rows="5" required style="width: 100%; padding: 12px; border: 1px solid #cfdcd6; border-radius: 9px; font: inherit; resize: vertical;" placeholder="Descreva com detalhes o que aconteceu, incluindo datas, nomes envolvidos, impacto e a solução esperada para que nossa equipe técnica possa atuar com precisão."></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; margin-top: 10px;">
                <button type="submit" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 10px; padding: 0 24px;">
                    <i class="fa-solid fa-paper-plane"></i> Enviar Manifestação com Protocolo Oficial
                </button>
            </div>
        </form>
    </section>

    <!-- Barra Lateral Informativa -->
    <aside class="portal-analysis-side">
        <div class="portal-side-card">
            <h2><i class="fa-solid fa-shield-halved" style="color: var(--p-green);"></i> Garantia ISO 9001</h2>
            <ul class="portal-steps" style="margin-top: 12px;">
                <li class="portal-step">
                    <span class="portal-step-number">1</span>
                    <div>
                        <strong>Protocolo Imediato</strong>
                        <p>Ao registrar, o sistema gera o identificador oficial RNC para rastreabilidade.</p>
                    </div>
                </li>
                <li class="portal-step">
                    <span class="portal-step-number">2</span>
                    <div>
                        <strong>Análise Técnica pelo RT</strong>
                        <p>O Responsável Técnico analisa a causa raiz para entender a origem da ocorrência.</p>
                    </div>
                </li>
                <li class="portal-step">
                    <span class="portal-step-number">3</span>
                    <div>
                        <strong>Plano de Ação Corretiva</strong>
                        <p>Ações 5W2H são executadas para sanar o ocorrido e evitar reincidência.</p>
                    </div>
                </li>
                <li class="portal-step">
                    <span class="portal-step-number">4</span>
                    <div>
                        <strong>Retorno ao Cliente</strong>
                        <p>Você acompanha o parecer e o fechamento diretamente por este painel.</p>
                    </div>
                </li>
            </ul>
        </div>
    </aside>
</div>

<!-- Listagem do Histórico de Manifestações do Cliente -->
<section class="portal-panel" style="margin-top: 24px;">
    <div class="portal-panel-header" style="margin-bottom: 20px;">
        <h2><i class="fa-solid fa-clock-rotate-left"></i> Histórico de Manifestações & Acompanhamento</h2>
    </div>

    <?php if (empty($manifestacoes)): ?>
        <div class="portal-empty" style="text-align: center; padding: 40px 20px;">
            <i class="fa-solid fa-check-double" style="font-size: 36px; color: var(--p-green); margin-bottom: 12px;"></i>
            <h3>Nenhuma manifestação registrada</h3>
            <p style="color: var(--p-muted); margin-top: 6px;">Você ainda não possui reclamações ou manifestações registradas. Quando enviar, o protocolo e o andamento aparecerão aqui em tempo real.</p>
        </div>
    <?php else: ?>
        <div class="portal-table-wrap">
            <table class="portal-table">
                <thead>
                    <tr>
                        <th>Protocolo / Assunto</th>
                        <th>Embarcação</th>
                        <th>Classificação</th>
                        <th>Data de Registro</th>
                        <th>Situação SGQ</th>
                        <th style="text-align: center;">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($manifestacoes as $item): ?>
                        <?php
                        $status = $item['status_ciclo_vida'];
                        $badgeClass = 'is-valid';
                        $statusNome = 'Concluída';

                        switch ($status) {
                            case 'ABERTA':
                                $badgeClass = 'is-warning';
                                $statusNome = 'Aberta / Aguardando RT';
                                break;
                            case 'EM_ANALISE_CAUSA':
                                $badgeClass = 'is-analysis';
                                $statusNome = 'Em Investigação Técnica';
                                break;
                            case 'PLANO_ACAO_DEFINIDO':
                            case 'EM_EXECUCAO':
                            case 'AGUARDANDO_EFICACIA':
                                $badgeClass = 'is-analysis';
                                $statusNome = 'Plano de Ação em Andamento';
                                break;
                            case 'ENCERRADA_EFICAZ':
                                $badgeClass = 'is-valid';
                                $statusNome = 'Encerrada / Resolvida';
                                break;
                            case 'REABERTA':
                                $badgeClass = 'is-rejected';
                                $statusNome = 'Reaberta';
                                break;
                        }
                        ?>
                        <tr>
                            <td data-label="Protocolo / Assunto">
                                <div style="display: flex; align-items: flex-start; gap: 10px;">
                                    <i class="fa-solid fa-file-signature portal-table-icon" style="margin-top: 3px;"></i>
                                    <div>
                                        <span style="display: inline-block; font-family: monospace; font-weight: 800; color: var(--p-green); font-size: 13px;">
                                            <?php echo h($item['numero_rnc']); ?>
                                        </span>
                                        <strong style="display: block; margin-top: 2px;"><?php echo h($item['titulo']); ?></strong>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Embarcação">
                                <?php if (!empty($item['embarcacao_nome'])): ?>
                                    <i class="fa-solid fa-ship" style="color: var(--p-muted); margin-right: 4px;"></i>
                                    <?php echo h($item['embarcacao_nome']); ?>
                                <?php else: ?>
                                    <span style="color: var(--p-muted);">Geral</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Classificação">
                                <span style="display: inline-block; padding: 2px 8px; border-radius: 6px; background: #f0f4f2; font-size: 12px; font-weight: 600;">
                                    <?php echo h($item['classificacao_falha'] ?: 'Geral'); ?>
                                </span>
                            </td>
                            <td data-label="Data"><?php echo date('d/m/Y H:i', strtotime($item['criado_em'])); ?></td>
                            <td data-label="Situação">
                                <span class="portal-status <?php echo $badgeClass; ?>">
                                    <?php echo h($statusNome); ?>
                                </span>
                                <?php if (!empty($item['analise_causa_raiz'])): ?>
                                    <div style="margin-top: 6px; font-size: 11px; color: var(--p-green); font-weight: 600;">
                                        <i class="fa-solid fa-circle-check"></i> Parecer técnico disponível
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td data-label="Ação" style="text-align: center;">
                                <button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#modalDetalhe<?php echo h($item['id']); ?>" style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; font-size: 12px; font-weight: 600;">
                                    <i class="fa-solid fa-eye"></i> Ver Detalhes
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Modais de Detalhes de Cada Manifestação -->
        <?php foreach ($manifestacoes as $item): ?>
            <?php
            $status = $item['status_ciclo_vida'];
            $badgeClass = 'is-valid';
            $statusNome = 'Concluída';

            switch ($status) {
                case 'ABERTA':
                    $badgeClass = 'is-warning';
                    $statusNome = 'Aberta / Aguardando RT';
                    break;
                case 'EM_ANALISE_CAUSA':
                    $badgeClass = 'is-analysis';
                    $statusNome = 'Em Investigação Técnica';
                    break;
                case 'PLANO_ACAO_DEFINIDO':
                case 'EM_EXECUCAO':
                case 'AGUARDANDO_EFICACIA':
                    $badgeClass = 'is-analysis';
                    $statusNome = 'Plano de Ação em Andamento';
                    break;
                case 'ENCERRADA_EFICAZ':
                    $badgeClass = 'is-valid';
                    $statusNome = 'Encerrada / Resolvida';
                    break;
                case 'REABERTA':
                    $badgeClass = 'is-rejected';
                    $statusNome = 'Reaberta';
                    break;
            }
            ?>
            <div class="modal fade" id="modalDetalhe<?php echo h($item['id']); ?>" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                <span style="background: #0f172a; color: #fff; padding: 5px 12px; border-radius: 6px; font-weight: 700; font-size: 13px; font-family: monospace;">
                                    <?php echo h($item['numero_rnc']); ?>
                                </span>
                                <strong style="font-size: 1.05rem; color: #1e293b;"><?php echo h($item['titulo']); ?></strong>
                            </div>
                            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                        </div>
                        <div class="modal-body" style="padding: 24px;">
                            <!-- Resumo em cards -->
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 20px;">
                                <div style="background: #f8fafc; padding: 12px 14px; border-radius: 8px; border: 1px solid #e2e8f0;">
                                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block;">Data de Registro</span>
                                    <strong style="color: #1e293b; font-size: 13px;"><?php echo date('d/m/Y H:i', strtotime($item['criado_em'])); ?></strong>
                                </div>
                                <div style="background: #f8fafc; padding: 12px 14px; border-radius: 8px; border: 1px solid #e2e8f0;">
                                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block;">Embarcação Vinculada</span>
                                    <strong style="color: #1e293b; font-size: 13px;"><?php echo h($item['embarcacao_nome'] ?: 'Geral / Administrativo'); ?></strong>
                                </div>
                                <div style="background: #f8fafc; padding: 12px 14px; border-radius: 8px; border: 1px solid #e2e8f0;">
                                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block;">Classificação</span>
                                    <strong style="color: #1e293b; font-size: 13px;"><?php echo h($item['classificacao_falha'] ?: 'Reclamação'); ?></strong>
                                </div>
                                <div style="background: #f8fafc; padding: 12px 14px; border-radius: 8px; border: 1px solid #e2e8f0;">
                                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block;">Situação Atual</span>
                                    <span class="portal-status <?php echo $badgeClass; ?>" style="margin-top: 4px; display: inline-block;">
                                        <?php echo h($statusNome); ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Relato do Cliente -->
                            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px 18px; margin-bottom: 20px;">
                                <div style="font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 8px; display: flex; align-items: center; gap: 8px;">
                                    <i class="fa-solid fa-file-lines" style="color: #08a774;"></i> Relato Registrado por Você:
                                </div>
                                <div style="font-size: 14px; color: #1e293b; line-height: 1.6; white-space: pre-line;">
                                    <?php echo h($item['descricao_detalhada']); ?>
                                </div>
                            </div>

                            <!-- Retorno Técnico da Amazon Certificadora -->
                            <div style="margin-bottom: 20px;">
                                <div style="font-size: 13px; font-weight: 700; color: #1e293b; margin-bottom: 8px; display: flex; align-items: center; gap: 8px;">
                                    <i class="fa-solid fa-shield-halved" style="color: #08a774;"></i> Parecer Técnico da Amazon Certificadora:
                                </div>
                                <?php if (!empty($item['analise_causa_raiz'])): ?>
                                    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 16px 18px;">
                                        <div style="font-size: 14px; color: #14532d; line-height: 1.6; white-space: pre-line;">
                                            <?php echo h($item['analise_causa_raiz']); ?>
                                        </div>
                                        <?php if (!empty($item['encerrada_em'])): ?>
                                            <div style="margin-top: 10px; font-size: 12px; color: #16a34a; font-weight: 600;">
                                                <i class="fa-solid fa-circle-check"></i> Manifestação concluída e resolvida em <?php echo date('d/m/Y \à\s H:i', strtotime($item['encerrada_em'])); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px; padding: 14px 18px; color: #92400e; font-size: 13px; display: flex; align-items: flex-start; gap: 10px;">
                                        <i class="fa-solid fa-hourglass-half" style="font-size: 16px; margin-top: 2px;"></i>
                                        <div>
                                            <strong>Em Investigação Técnica pela Diretoria de Qualidade</strong>
                                            <p style="margin: 3px 0 0; font-size: 12px; color: #a16207;">Sua manifestação foi recebida e está sendo apurada pela equipe técnica. O parecer e as providências adotadas serão detalhados aqui em breve.</p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Ações Corretivas Executadas (se houver) -->
                            <?php $acoes = $acoesPorManifestacao[$item['id']] ?? []; ?>
                            <?php if (!empty($acoes)): ?>
                                <div>
                                    <div style="font-size: 13px; font-weight: 700; color: #1e293b; margin-bottom: 8px; display: flex; align-items: center; gap: 8px;">
                                        <i class="fa-solid fa-clipboard-check" style="color: #08a774;"></i> Providências e Ações Adotadas:
                                    </div>
                                    <div style="border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
                                        <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                                            <thead style="background: #f8fafc;">
                                                <tr>
                                                    <th style="padding: 10px 12px; text-align: left; font-weight: 700; color: #475569; font-size: 12px;">Ação Corretiva</th>
                                                    <th style="padding: 10px 12px; text-align: left; font-weight: 700; color: #475569; font-size: 12px;">Prazo</th>
                                                    <th style="padding: 10px 12px; text-align: center; font-weight: 700; color: #475569; font-size: 12px;">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($acoes as $ac): ?>
                                                    <tr style="border-top: 1px solid #f1f5f9;">
                                                        <td style="padding: 10px 12px; font-weight: 600; color: #1e293b;"><?php echo h($ac['o_que_fazer_what']); ?></td>
                                                        <td style="padding: 10px 12px; color: #64748b; font-size: 12px;"><?php echo formatarData($ac['quando_fara_when']); ?></td>
                                                        <td style="padding: 10px 12px; text-align: center;">
                                                            <?php if ($ac['status_acao'] === 'CONCLUIDA'): ?>
                                                                <span style="background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 700;">Concluída</span>
                                                            <?php else: ?>
                                                                <span style="background: #fffbeb; color: #d97706; border: 1px solid #fde68a; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 700;">Em Andamento</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="modal-footer" style="padding: 14px 24px; background: #f8fafc; border-top: 1px solid #e2e8f0;">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../../includes/portal_footer.php'; ?>
