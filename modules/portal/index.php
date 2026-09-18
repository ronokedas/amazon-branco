<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/cliente_portal.php';

requireClienteSenhaDefinitiva();

$clienteId = clientePortalId();
$resumo = clientePortalResumoGeral($pdo, $clienteId);
$embarcacoes = clientePortalEmbarcacoes($pdo, $clienteId);
$documentos = clientePortalSelectDocumentos($pdo, $clienteId);
$vencendo = clientePortalSelectDocumentos($pdo, $clienteId, ['vencendo_dias' => 90]);
$vencendo = array_values(array_filter($vencendo, fn($doc) => !empty($doc['data_validade'])));
$documentosRecentes = array_slice($documentos, 0, 6);
$documentosAssinados = array_filter($documentos, fn($doc) => ($doc['status'] ?? '') === 'assinado');
$documentosPendentes = array_filter($documentos, fn($doc) => ($doc['status'] ?? '') === 'emitido');

$protocolos = clientePortalSelectProtocolos($pdo, $clienteId);
$protocolosAtivos = array_filter($protocolos, fn($p) => !in_array($p['status'], ['ENCERRADO', 'CANCELADO'], true));

$analises = clientePortalSelectAnalises($pdo, $clienteId, ['status_grupo' => 'ativas']);
$agendamentos = clientePortalSelectAgendamentos($pdo, $clienteId);
$propostas = clientePortalSelectPropostas($pdo, $clienteId);

$diasAteVencer = function (?string $data): ?int {
    if (empty($data)) {
        return null;
    }
    $hoje = new DateTimeImmutable('today');
    $validade = new DateTimeImmutable($data);
    return (int)$hoje->diff($validade)->format('%r%a');
};

$statusProtocoloMap = [
    'EM_PREPARACAO' => ['label' => 'Em Preparação', 'class' => 'is-warning', 'icon' => 'fa-pen-to-square'],
    'ENVIADO_AO_ORGAO' => ['label' => 'Enviado à Capitania', 'class' => 'is-analysis', 'icon' => 'fa-paper-plane'],
    'PROTOCOLADO' => ['label' => 'Protocolado no SISAP', 'class' => 'is-valid', 'icon' => 'fa-stamp'],
    'EM_ANALISE_NO_ORGAO' => ['label' => 'Em Análise na Capitania', 'class' => 'is-analysis', 'icon' => 'fa-magnifying-glass'],
    'EM_EXIGENCIA' => ['label' => 'Notificação de Exigência', 'class' => 'is-expired', 'icon' => 'fa-triangle-exclamation'],
    'A_DISPOSICAO' => ['label' => 'Disponível p/ Retirada', 'class' => 'is-valid', 'icon' => 'fa-box-archive'],
    'RETIRADO' => ['label' => 'Retirado na Capitania', 'class' => 'is-valid', 'icon' => 'fa-check-double'],
    'ENTREGUE_AO_CLIENTE' => ['label' => 'Entregue ao Cliente', 'class' => 'is-valid', 'icon' => 'fa-circle-check'],
    'ENCERRADO' => ['label' => 'Concluído', 'class' => 'is-valid', 'icon' => 'fa-circle-check'],
    'CANCELADO' => ['label' => 'Cancelado', 'class' => 'is-expired', 'icon' => 'fa-ban'],
];

$titulo_page = 'Portal do Cliente - Visão Operacional';
require_once __DIR__ . '/../../includes/portal_header.php';
?>
<div class="portal-dashboard">
    <!-- 1. HERO BANNER OPERACIONAL (FULL WIDTH) -->
    <section class="portal-hero">
        <div class="portal-hero-media"></div>
        <div class="portal-hero-content">
            <div class="portal-hero-tagline">
                <span>Olá, <?php echo h(clientePortalNome()); ?></span>
                <span class="portal-role-tag"><?php echo h(clientePortalPerfil() === 'proprietario' ? 'Armador / Proprietário' : strtoupper(clientePortalPerfil())); ?></span>
            </div>
            <h1>Portal do Cliente</h1>
            <p>Visão centralizada de certificados estatutários NORMAM, processos na Capitania dos Portos (SISAP), projetos navais e vistorias da sua frota.</p>
            
            <div class="portal-hero-actions">
                <a class="portal-hero-button" href="<?php echo APP_URL; ?>portal/documentos">
                    <i class="fa-solid fa-file-shield"></i> Meus certificados (<?php echo count($documentos); ?>)
                </a>
                <a class="portal-hero-button portal-hero-button-sec" href="<?php echo APP_URL; ?>portal/protocolos">
                    <i class="fa-solid fa-landmark-flag"></i> Trâmites SISAP (<?php echo count($protocolosAtivos); ?>)
                </a>
                <?php if (!empty($analises)): ?>
                    <a class="portal-hero-button portal-hero-button-sec" href="<?php echo APP_URL; ?>portal/analises-planos">
                        <i class="fa-solid fa-cloud-arrow-up"></i> Planos navais (<?php echo count($analises); ?>)
                    </a>
                <?php endif; ?>
                <a class="portal-hero-button portal-hero-button-sec" href="<?php echo APP_URL; ?>portal/embarcacoes">
                    <i class="fa-solid fa-ship"></i> Minha frota (<?php echo count($embarcacoes); ?>)
                </a>
            </div>
        </div>
    </section>

    <!-- 2. CARDS DE KPIS ACIONÁVEIS (FULL WIDTH) -->
    <section class="portal-metrics">
        <a href="<?php echo APP_URL; ?>portal/embarcacoes" class="portal-metric" title="Ver minha frota">
            <div class="portal-metric-icon-wrap is-green">
                <i class="fa-solid fa-ship"></i>
            </div>
            <div class="portal-metric-body">
                <strong><?php echo count($embarcacoes); ?></strong>
                <span>Embarcações na frota</span>
            </div>
        </a>
        <a href="<?php echo APP_URL; ?>portal/documentos" class="portal-metric" title="Ver documentos válidos">
            <div class="portal-metric-icon-wrap is-mint">
                <i class="fa-regular fa-circle-check"></i>
            </div>
            <div class="portal-metric-body">
                <strong><?php echo $resumo['docs_validos']; ?></strong>
                <span>Documentos válidos</span>
            </div>
        </a>
        <a href="<?php echo APP_URL; ?>portal/documentos?vencendo=1" class="portal-metric" title="Ver documentos a vencer">
            <div class="portal-metric-icon-wrap is-amber">
                <i class="fa-regular fa-clock"></i>
            </div>
            <div class="portal-metric-body">
                <strong><?php echo count($vencendo); ?></strong>
                <span><?php echo count($vencendo) === 0 ? 'Documentos a vencer (Regular)' : 'Documentos a vencer'; ?></span>
            </div>
        </a>
        <a href="<?php echo APP_URL; ?>portal/protocolos" class="portal-metric" title="Ver trâmites na Capitania">
            <div class="portal-metric-icon-wrap is-blue">
                <i class="fa-solid fa-landmark-flag"></i>
            </div>
            <div class="portal-metric-body">
                <strong><?php echo count($protocolosAtivos); ?></strong>
                <span>Trâmites SISAP na Capitania</span>
            </div>
        </a>
    </section>

    <!-- 3. ALERTAS OPERACIONAIS INTELIGENTES (SE HOUVER EXIGÊNCIAS OU CUSTÓDIA) -->
    <?php if ($resumo['protocolos_exigencia'] > 0 || $resumo['custodia_pendente'] > 0 || !empty($agendamentos)): ?>
        <section class="portal-alerts-strip">
            <?php if ($resumo['protocolos_exigencia'] > 0): ?>
                <div class="portal-alert-box alert-danger">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <div>
                        <strong>Notificação de Exigência na Capitania</strong>
                        <p>Há <?php echo $resumo['protocolos_exigencia']; ?> processo(s) com exigência técnica aguardando providências.</p>
                        <a href="<?php echo APP_URL; ?>portal/protocolos?status=EM_EXIGENCIA" class="btn btn-sm btn-danger">Ver processos em exigência</a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($resumo['custodia_pendente'] > 0): ?>
                <div class="portal-alert-box alert-warning">
                    <i class="fa-solid fa-box-archive"></i>
                    <div>
                        <strong>Documentos sob Custódia Operacional</strong>
                        <p><?php echo $resumo['custodia_pendente']; ?> via(s) original(is) sob custódia da certificadora/despachante em trâmite.</p>
                        <a href="<?php echo APP_URL; ?>portal/protocolos" class="btn btn-sm btn-secondary">Acompanhar custódia</a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($agendamentos)): ?>
                <div class="portal-alert-box alert-info">
                    <i class="fa-regular fa-calendar-check"></i>
                    <div>
                        <strong>Próxima Vistoria Agendada</strong>
                        <p><strong><?php echo h($agendamentos[0]['embarcacao_nome']); ?>:</strong> <?php echo formatarData($agendamentos[0]['data_vistoria']); ?> em <?php echo h($agendamentos[0]['local'] ?: 'Local a confirmar'); ?>.</p>
                        <a href="<?php echo APP_URL; ?>portal/vistorias" class="btn btn-sm btn-outline-info">Ver agendamentos</a>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <!-- 4. ESTRUTURA BALANCEADA MASTER / SIDEBAR (GRID 2 COLUNAS) -->
    <div class="portal-dashboard-grid">
        <!-- COLUNA PRINCIPAL (ESQUERDA - 70% LARGURA) -->
        <div class="portal-dashboard-main">
            <!-- 4.1 TRÂMITES NA CAPITANIA DOS PORTOS (SISAP & PROTOCOLOS) -->
            <section class="portal-panel portal-sisap-panel">
                <div class="portal-panel-header">
                    <div>
                        <h2><i class="fa-solid fa-landmark-flag" style="color:var(--p-info)"></i> Trâmites Oficiais na Capitania (SISAP & Protocolos)</h2>
                        <small>Acompanhe o andamento oficial dos processos abertos na Capitania dos Portos e Delegacias.</small>
                    </div>
                    <a href="<?php echo APP_URL; ?>portal/protocolos">Ver todos os trâmites (<?php echo count($protocolos); ?>)</a>
                </div>

                <?php if (empty($protocolos)): ?>
                    <div class="portal-empty">
                        <i class="fa-solid fa-folder-open"></i>
                        <h2>Nenhum processo de protocolo aberto</h2>
                        <p>Quando seus processos navais forem protocolados na Capitania, o andamento oficial aparecerá aqui.</p>
                    </div>
                <?php else: ?>
                    <div class="portal-table-wrap">
                        <table class="portal-table">
                            <thead>
                                <tr>
                                    <th>Dossiê / Assunto</th>
                                    <th>Embarcação</th>
                                    <th>Capitania / Órgão</th>
                                    <th>Processo SISAP</th>
                                    <th>Situação</th>
                                    <th>Custódia</th>
                                    <th style="text-align: right;">Detalhes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($protocolos, 0, 5) as $prot): ?>
                                    <?php $stInfo = $statusProtocoloMap[$prot['status']] ?? ['label' => $prot['status'], 'class' => 'is-warning', 'icon' => 'fa-clock']; ?>
                                    <tr>
                                        <td data-label="Dossiê / Assunto">
                                            <strong><?php echo h($prot['numero']); ?></strong>
                                            <small class="portal-doc-sub"><?php echo h($prot['assunto']); ?></small>
                                        </td>
                                        <td data-label="Embarcação">
                                            <i class="fa-solid fa-ship portal-table-icon" style="color:var(--p-green); margin-right:4px;"></i>
                                            <strong><?php echo h($prot['embarcacao_nome'] ?: '-'); ?></strong>
                                        </td>
                                        <td data-label="Órgão">
                                            <?php 
                                            $org = $prot['unidade_maritima_nome'] ?: 'Capitania dos Portos';
                                            $orgCurto = str_ireplace(['Capitania dos Portos da ', 'Capitania dos Portos do ', 'Capitania dos Portos '], ['CP ', 'CP ', 'CP '], $org);
                                            echo h($orgCurto); 
                                            ?>
                                        </td>
                                        <td data-label="SISAP">
                                            <?php if (!empty($prot['protocolo_externo_numero'])): ?>
                                                <span class="badge-sisap"><i class="fa-solid fa-fingerprint"></i> <?php echo h($prot['protocolo_externo_numero']); ?></span>
                                            <?php else: ?>
                                                <span style="color:var(--p-muted); font-size:12px;">Aguardando</span>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="Situação">
                                            <span class="portal-status <?php echo $stInfo['class']; ?>">
                                                <i class="fa-solid <?php echo $stInfo['icon']; ?>" style="margin-right:4px;"></i> <?php echo h($stInfo['label']); ?>
                                            </span>
                                        </td>
                                        <td data-label="Custódia">
                                            <?php if ((int)$prot['originais_sob_custodia'] > 0): ?>
                                                <span class="badge-custodia-warn" title="Via original sob custódia">
                                                    <i class="fa-solid fa-lock"></i> <?php echo (int)$prot['originais_sob_custodia']; ?> orig.
                                                </span>
                                            <?php else: ?>
                                                <span style="color:var(--p-muted); font-size:12px;">Regular</span>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="Detalhes" style="text-align: right;">
                                            <a class="btn btn-sm btn-outline-secondary btn-has-text" style="white-space: nowrap;" href="<?php echo APP_URL; ?>portal/protocolos?id=<?php echo urlencode($prot['id']); ?>" title="Ver detalhes do processo">
                                                <i class="fa-solid fa-eye"></i> Trâmite
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <a class="portal-panel-footer-link" href="<?php echo APP_URL; ?>portal/protocolos">Ver lista completa de protocolos na Marinha</a>
                <?php endif; ?>
            </section>

            <!-- 4.2 DOCUMENTOS E CERTIFICADOS RECENTES (LARGURA COMPLETA, SEM CORTES) -->
            <section class="portal-panel portal-docs-panel">
                <div class="portal-panel-header">
                    <div>
                        <h2><i class="fa-solid fa-file-shield" style="color:var(--p-green)"></i> Documentos e Certificados Recentes</h2>
                        <small>Acesse e baixe os certificados estatutários e licenças emitidas pela Marinha e certificadora.</small>
                    </div>
                    <a href="<?php echo APP_URL; ?>portal/documentos">Ver todos os documentos (<?php echo count($documentos); ?>)</a>
                </div>

                <?php if (empty($documentosRecentes)): ?>
                    <div class="portal-empty">
                        <i class="fa-regular fa-file-lines"></i>
                        <h2>Nenhum documento emitido</h2>
                        <p>Nenhum documento emitido foi encontrado para o seu cadastro.</p>
                    </div>
                <?php else: ?>
                    <div class="portal-table-wrap">
                        <table class="portal-table">
                            <thead>
                                <tr>
                                    <th>Documento</th>
                                    <th>Emissão</th>
                                    <th>Validade</th>
                                    <th>Situação</th>
                                    <th style="text-align: right;">Ação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($documentosRecentes as $doc): ?>
                                    <?php $dias = $diasAteVencer($doc['data_validade']); ?>
                                    <tr>
                                        <td data-label="Documento">
                                            <a class="portal-doc-link" target="_blank" href="<?php echo APP_URL; ?>portal/documentos/pdf?tipo=<?php echo h($doc['tipo']); ?>&id=<?php echo h($doc['id']); ?>" title="Visualizar PDF">
                                                <i class="fa-regular fa-file-lines portal-table-icon" style="color:var(--p-green); font-size:18px;"></i>
                                                <div>
                                                    <strong><?php echo h($doc['tipo_label'] . ' - ' . $doc['numero']); ?></strong>
                                                    <small class="portal-doc-sub"><i class="fa-solid fa-ship"></i> <?php echo h($doc['embarcacao_nome']); ?></small>
                                                </div>
                                            </a>
                                        </td>
                                        <td data-label="Emissão"><?php echo formatarData($doc['data_emissao']); ?></td>
                                        <td data-label="Validade"><?php echo !empty($doc['data_validade']) ? formatarData($doc['data_validade']) : '<span style="color:var(--p-muted);">-</span>'; ?></td>
                                        <td data-label="Situação">
                                            <span class="portal-status <?php echo ($dias !== null && $dias < 0) ? 'is-expired' : (($dias !== null && $dias <= 90) ? 'is-warning' : 'is-valid'); ?>">
                                                <?php echo ($dias !== null && $dias < 0) ? 'Vencido' : (($dias !== null && $dias <= 90) ? 'A vencer' : 'Válido'); ?>
                                            </span>
                                        </td>
                                        <td data-label="Ação" style="text-align: right;">
                                            <a class="portal-btn-pdf" target="_blank" href="<?php echo APP_URL; ?>portal/documentos/pdf?tipo=<?php echo h($doc['tipo']); ?>&id=<?php echo h($doc['id']); ?>" title="Visualizar PDF Oficial">
                                                <i class="fa-solid fa-file-pdf"></i> Baixar PDF
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <a class="portal-panel-footer-link" href="<?php echo APP_URL; ?>portal/documentos">Ver todos os documentos</a>
                <?php endif; ?>
            </section>

            <!-- 4.3 MINHA FROTA (EMBARCAÇÕES DA FROTA) -->
            <section class="portal-panel portal-boats-panel">
                <div class="portal-panel-header">
                    <div>
                        <h2><i class="fa-solid fa-ship" style="color:var(--p-green)"></i> Embarcações da Frota</h2>
                        <small>Gerencie embarcações vinculadas ao seu cadastro de armador/proprietário.</small>
                    </div>
                    <a href="<?php echo APP_URL; ?>portal/embarcacoes">Ver todas (<?php echo count($embarcacoes); ?>)</a>
                </div>

                <?php if (empty($embarcacoes)): ?>
                    <div class="portal-empty">
                        <i class="fa-solid fa-ship"></i>
                        <h2>Nenhuma embarcação vinculada</h2>
                        <p>Nenhuma embarcação vinculada ao seu cadastro no momento.</p>
                    </div>
                <?php else: ?>
                    <div class="portal-table-wrap">
                        <table class="portal-table">
                            <thead>
                                <tr>
                                    <th>Nome da embarcação</th>
                                    <th>Registro / Inscrição</th>
                                    <th>Tipo de Embarcação</th>
                                    <th>Situação</th>
                                    <th style="text-align: right;">Documentos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($embarcacoes, 0, 4) as $emb): ?>
                                    <tr>
                                        <td data-label="Embarcação">
                                            <i class="fa-solid fa-ship portal-table-icon" style="color:var(--p-green); margin-right:6px;"></i>
                                            <strong><?php echo h($emb['nome']); ?></strong>
                                        </td>
                                        <td data-label="Registro"><?php echo h($emb['registro'] ?: ($emb['numero_inscricao'] ?: '-')); ?></td>
                                        <td data-label="Tipo"><?php echo h($emb['tipo_embarcacao'] ?: '-'); ?></td>
                                        <td data-label="Situação"><span class="portal-status is-valid"><i class="fa-solid fa-check" style="margin-right:4px;"></i> Ativa</span></td>
                                        <td data-label="Documentos" style="text-align: right;">
                                            <a class="btn btn-sm btn-outline-secondary btn-has-text" style="white-space: nowrap; padding: 4px 10px; font-size: 12px;" href="<?php echo APP_URL; ?>portal/documentos?embarcacao_id=<?php echo urlencode($emb['id']); ?>" title="Ver documentos da embarcação">
                                                <i class="fa-solid fa-file-shield" style="margin-right:4px;"></i> Certificados <i class="fa-solid fa-arrow-right" style="margin-left:3px;"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <a class="portal-panel-footer-link" href="<?php echo APP_URL; ?>portal/embarcacoes">Ver todas as embarcações</a>
                <?php endif; ?>
            </section>

            <!-- 4.4 PROJETOS EM ANÁLISE TÉCNICA (SE HOUVER) -->
            <?php if (!empty($analises)): ?>
                <section class="portal-panel">
                    <div class="portal-panel-header">
                        <div>
                            <h2><i class="fa-solid fa-drafting-compass" style="color:var(--p-info)"></i> Projetos de Engenharia em Análise Técnica</h2>
                            <small>Acompanhe o envio e aprovação das plantas e memoriais da sua frota.</small>
                        </div>
                        <a href="<?php echo APP_URL; ?>portal/analises-planos">Enviar pranchas / Gerenciar</a>
                    </div>
                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:16px;">
                        <?php foreach ($analises as $an): ?>
                            <div style="border:1px solid var(--p-line); border-radius:12px; padding:18px; background:#fff; box-shadow: 0 4px 14px rgba(16,47,41,0.03);">
                                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
                                    <div>
                                        <strong style="font-size:1.05rem; color:var(--p-ink); display:block;"><?php echo h($an['numero']); ?></strong>
                                        <span style="font-size:0.85rem; color:var(--p-muted);"><i class="fa-solid fa-ship"></i> <?php echo h($an['embarcacao_nome']); ?></span>
                                    </div>
                                    <span class="portal-status is-analysis"><?php echo h($an['status']); ?></span>
                                </div>
                                <p style="font-size:0.85rem; color:var(--p-muted); margin:0 0 14px;"><?php echo h($an['objeto'] ?: 'Análise de Planos e Memoriais Navais (NORMAM-202)'); ?></p>
                                <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.82rem; color:var(--p-muted); padding-top:12px; border-top:1px dashed var(--p-line);">
                                    <span><i class="fa-solid fa-paperclip"></i> <?php echo (int)($an['total_arquivos_enviados'] ?? 0); ?> arquivo(s) em <?php echo (int)($an['ultima_revisao'] ?? 1); ?> revisão(ões)</span>
                                    <a href="<?php echo APP_URL; ?>portal/analises-planos" class="btn btn-sm btn-primary">
                                        <i class="fa-solid fa-upload"></i> Enviar arquivos
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </div>

        <!-- COLUNA LATERAL (DIREITA - 30% LARGURA) -->
        <div class="portal-dashboard-side">
            <!-- 4.5 PRÓXIMOS VENCIMENTOS (CARD HARMONIOSO) -->
            <section class="portal-panel portal-expiry-panel">
                <div class="portal-panel-header">
                    <h2><i class="fa-regular fa-calendar" style="color:var(--p-warn)"></i> Próximos vencimentos</h2>
                    <a href="<?php echo APP_URL; ?>portal/documentos?vencendo=1">Ver todos</a>
                </div>
                <?php if (empty($vencendo)): ?>
                    <div class="portal-regular-card">
                        <div class="portal-regular-icon">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                        <strong>Frota 100% Regular</strong>
                        <p>Nenhum certificado vencendo nos próximos 90 dias.</p>
                        <small>Todos os certificados estatutários e vistorias da sua frota estão regulares perante a Capitania dos Portos.</small>
                    </div>
                <?php else: ?>
                    <div class="portal-expiry-list">
                        <?php foreach (array_slice($vencendo, 0, 5) as $doc): ?>
                            <?php $dias = $diasAteVencer($doc['data_validade']); ?>
                            <a class="portal-expiry-row <?php echo ($dias !== null && $dias < 0) ? 'is-expired' : ''; ?>" href="<?php echo APP_URL; ?>portal/documentos/pdf?tipo=<?php echo h($doc['tipo']); ?>&id=<?php echo h($doc['id']); ?>" target="_blank">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                                <span>
                                    <strong><?php echo h($doc['tipo_label'] . ' - ' . $doc['numero']); ?></strong>
                                    <small><i class="fa-solid fa-ship"></i> <?php echo h($doc['embarcacao_nome']); ?></small>
                                </span>
                                <em>
                                    <?php if ($dias === null): ?>
                                        Validade<br><strong>Sem data</strong>
                                    <?php elseif ($dias < 0): ?>
                                        Vencido há<br><strong><?php echo h((string)abs($dias)); ?> dias</strong>
                                    <?php else: ?>
                                        Vence em<br><strong><?php echo h((string)$dias); ?> dias</strong>
                                    <?php endif; ?>
                                </em>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <a class="portal-panel-footer-link" href="<?php echo APP_URL; ?>portal/documentos?vencendo=1">Ver todos os vencimentos</a>
                <?php endif; ?>
            </section>

            <!-- 4.6 AÇÕES RÁPIDAS DO ARMADOR -->
            <section class="portal-panel portal-quick-actions-panel">
                <div class="portal-panel-header">
                    <h2><i class="fa-solid fa-bolt" style="color:#eab308"></i> Ações Rápidas</h2>
                </div>
                <div class="portal-quick-actions-list">
                    <a class="portal-quick-action-link" href="<?php echo APP_URL; ?>portal/analises-planos">
                        <div>
                            <i class="fa-action-icon fa-solid fa-cloud-arrow-up" style="background:#eaf8f2; color:var(--p-green);"></i>
                            <span>Enviar Planos Navais</span>
                        </div>
                        <i class="fa-solid fa-chevron-right" style="color:var(--p-muted); font-size:12px;"></i>
                    </a>
                    <a class="portal-quick-action-link" href="<?php echo APP_URL; ?>portal/vistorias">
                        <div>
                            <i class="fa-action-icon fa-regular fa-calendar-check" style="background:#eaf2ff; color:#0284c7;"></i>
                            <span>Solicitar Vistoria Técnica</span>
                        </div>
                        <i class="fa-solid fa-chevron-right" style="color:var(--p-muted); font-size:12px;"></i>
                    </a>
                    <a class="portal-quick-action-link" href="<?php echo APP_URL; ?>portal/propostas">
                        <div>
                            <i class="fa-action-icon fa-solid fa-file-invoice-dollar" style="background:#fef3c7; color:#b45309;"></i>
                            <span>Minhas Propostas & Orçamentos</span>
                        </div>
                        <i class="fa-solid fa-chevron-right" style="color:var(--p-muted); font-size:12px;"></i>
                    </a>
                    <a class="portal-quick-action-link" href="<?php echo APP_URL; ?>portal/ouvidoria">
                        <div>
                            <i class="fa-action-icon fa-solid fa-headset" style="background:#f3e8ff; color:#7e22ce;"></i>
                            <span>Canal Direto de Ouvidoria</span>
                        </div>
                        <i class="fa-solid fa-chevron-right" style="color:var(--p-muted); font-size:12px;"></i>
                    </a>
                </div>
            </section>

            <!-- 4.7 RESUMO COMERCIAL (PROPOSTAS RECENTES) -->
            <?php if (!empty($propostas)): ?>
                <section class="portal-panel">
                    <div class="portal-panel-header">
                        <h2><i class="fa-solid fa-file-invoice-dollar" style="color:var(--p-green)"></i> Propostas Recentes</h2>
                        <a href="<?php echo APP_URL; ?>portal/propostas">Ver todas (<?php echo count($propostas); ?>)</a>
                    </div>
                    <div style="display:flex; flex-direction:column; gap:10px;">
                        <?php foreach (array_slice($propostas, 0, 3) as $prop): ?>
                            <a href="<?php echo APP_URL; ?>portal/propostas" style="display:flex; justify-content:space-between; align-items:center; padding:12px 14px; border:1px solid var(--p-line); border-radius:10px; text-decoration:none; color:inherit; background:#fff; transition:0.15s ease;">
                                <div>
                                    <strong style="color:var(--p-ink); font-size:13px; display:block;"><?php echo h($prop['numero']); ?></strong>
                                    <small style="color:var(--p-muted); font-size:12px;"><i class="fa-solid fa-ship"></i> <?php echo h($prop['embarcacao_nome'] ?: 'Serviço Naval'); ?></small>
                                </div>
                                <div style="text-align:right;">
                                    <strong style="color:var(--p-green); font-size:13px; display:block;">R$ <?php echo number_format((float)$prop['valor_total'], 2, ',', '.'); ?></strong>
                                    <span class="portal-status is-valid" style="font-size:11px; padding:2px 6px;"><?php echo h($prop['status']); ?></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </div>
    </div>

    <!-- 5. RODAPÉ INSTITUCIONAL (FULL WIDTH) -->
    <footer class="portal-dashboard-footer">
        <span><i class="fa-regular fa-shield-check"></i> Conformidade NORMAM, DPC e Capitanias dos Portos.</span>
        <span>© <?php echo date('Y'); ?> Amazon Certificadora. Todos os direitos reservados.</span>
        <strong><i class="fa-solid fa-lock"></i> Conexão Criptografada e Segura</strong>
    </footer>
</div>
<?php require_once __DIR__ . '/../../includes/portal_footer.php'; ?>
