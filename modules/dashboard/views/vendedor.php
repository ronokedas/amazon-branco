<?php
/**
 * MÓDULO: DASHBOARD
 * Arquivo: views/vendedor.php - Painel Comercial, Operacional e Financeiro Exclusivo do Vendedor
 * Desenvolvido para facilitar a rotina diária: gerar propostas, agendar vistorias técnicas e acompanhar financeiro.
 */

$kpis = $dashboard['kpis'] ?? [];
$funil = $dashboard['funil'] ?? ['rascunho' => 0, 'enviada' => 0, 'assinada' => 0, 'recusada' => 0, 'cancelada' => 0, 'aguardando_agendamento' => 0];
$filaAgendamentos = $dashboard['fila_agendamentos'] ?? [];
$proximosAgendamentos = $dashboard['proximos_agendamentos'] ?? [];
$financeiroRecentes = $dashboard['financeiro_recentes'] ?? [];
$recentes = $dashboard['recentes'] ?? [];
$carteiraClientes = $dashboard['carteira_clientes'] ?? [];
$conversao = (float)($dashboard['conversao'] ?? 0);
$contribuicao = (float)($dashboard['contribuicao'] ?? 0);
$usuarioNome = $_SESSION['usuario_nome'] ?? 'Vendedor Comercial';

// Helper local para links diretos de WhatsApp
$linkWhatsHelper = function(?string $telefone, string $mensagem): ?string {
    $digitos = preg_replace('/\D/', '', $telefone ?? '');
    if (strlen($digitos) < 10) return null;
    if (!str_starts_with($digitos, '55') && (strlen($digitos) === 10 || strlen($digitos) === 11)) {
        $num = '55' . $digitos;
    } else {
        $num = $digitos;
    }
    return 'https://api.whatsapp.com/send?phone=' . $num . '&text=' . rawurlencode($mensagem);
};
?>

<div class="dash-vendedor-container">

    <!-- 1. HEADER EXCLUSIVO DO VENDEDOR -->
    <header class="dash-vendedor-header">
        <div class="dash-vendedor-header__info">
            <div class="badge-role-commercial">
                <i class="fa-solid fa-briefcase"></i> PAINEL COMERCIAL NAVAL · VENDAS & OPERAÇÕES
            </div>
            <h1>Olá, <?= h($usuarioNome) ?>! 👋</h1>
            <p>Gerencie suas propostas, agende vistorias com vistoriadores e acompanhe o financeiro dos seus clientes.</p>
        </div>

        <div class="dash-vendedor-header__actions">
            <?php if (podeAcessar('comercial')): ?>
                <a href="<?= APP_URL ?>comercial/nova" class="btn btn-vendedor-primary" title="Iniciar elaboração de proposta">
                    <i class="fa-solid fa-file-circle-plus"></i> Nova Proposta
                </a>
            <?php endif; ?>

            <?php if (podeAcessar('agendamentos')): ?>
                <a href="<?= APP_URL ?>agendamentos/form" class="btn btn-vendedor-secondary" title="Agendar nova vistoria técnica">
                    <i class="fa-solid fa-calendar-plus"></i> Agendar Vistoria
                </a>
            <?php endif; ?>

            <?php if (podeAcessar('clientes')): ?>
                <a href="<?= APP_URL ?>clientes/form" class="btn btn-vendedor-secondary" title="Cadastrar novo cliente, armador ou despachante">
                    <i class="fa-solid fa-user-plus"></i> Novo Cliente
                </a>
            <?php endif; ?>

            <?php if (podeAcessar('financeiro')): ?>
                <a href="<?= APP_URL ?>financeiro" class="btn btn-vendedor-outline" title="Acessar módulo financeiro">
                    <i class="fa-solid fa-hand-holding-dollar"></i> Financeiro
                </a>
            <?php endif; ?>
        </div>
    </header>

    <!-- 2. CARD DE META COMERCIAL & CONTRIBUIÇÃO INDIVIDUAL -->
    <section class="dash-vendedor-meta-card">
        <div class="meta-inner-grid">
            <!-- Meta da Empresa / Filial -->
            <div class="meta-column-main">
                <?php require __DIR__ . '/meta.php'; ?>
            </div>

            <!-- Contribuição e Conversão Individual do Vendedor -->
            <div class="meta-column-individual">
                <div class="user-perf-card">
                    <div class="user-perf-header">
                        <div class="user-perf-icon">
                            <i class="fa-solid fa-chart-line"></i>
                        </div>
                        <div>
                            <span class="user-perf-title">Sua Contribuição para a Meta</span>
                            <h3 class="user-perf-val"><?= number_format($contribuicao, 1, ',', '.') ?>%</h3>
                        </div>
                    </div>
                    <div class="progress-bar-vendedor">
                        <div class="progress-bar-vendedor__fill" style="width: <?= min(100, max(5, $contribuicao)) ?>%;"></div>
                    </div>
                    <p class="user-perf-desc">
                        Recebido no mês: <strong><?= formatarMoeda($kpis['financeiro_recebido_mes'] ?? 0) ?></strong>
                    </p>
                </div>

                <div class="user-conversion-card">
                    <div class="conversion-ring">
                        <span><?= number_format($conversao, 1, ',', '.') ?>%</span>
                    </div>
                    <div>
                        <strong>Taxa de Conversão</strong>
                        <small>Propostas assinadas sobre emitidas por você no mês.</small>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 3. GRID DE KPIS COMERCIAIS & OPERACIONAIS (6 CARDS MODERNOS) -->
    <section class="dash-vendedor-kpis">
        <!-- 1. Propostas no Mês -->
        <a href="<?= APP_URL ?>comercial" class="vendedor-kpi-card is-blue">
            <div class="vendedor-kpi-card__top">
                <span class="kpi-label">Propostas no Mês</span>
                <div class="kpi-icon"><i class="fa-solid fa-file-invoice-dollar"></i></div>
            </div>
            <div class="kpi-number"><?= (int)($kpis['emitidas_mes'] ?? 0) ?></div>
            <div class="kpi-subtext">
                Negociado: <strong><?= formatarMoeda($kpis['valor_emitidas_mes'] ?? 0) ?></strong>
            </div>
        </a>

        <!-- 2. Vendas Fechadas (Assinadas) -->
        <a href="<?= APP_URL ?>comercial?status=assinada" class="vendedor-kpi-card is-green">
            <div class="vendedor-kpi-card__top">
                <span class="kpi-label">Vendas Fechadas</span>
                <div class="kpi-icon"><i class="fa-solid fa-file-signature"></i></div>
            </div>
            <div class="kpi-number"><?= (int)($kpis['assinadas_mes'] ?? 0) ?></div>
            <div class="kpi-subtext">
                Faturado: <strong><?= formatarMoeda($kpis['valor_assinadas_mes'] ?? 0) ?></strong>
            </div>
        </a>

        <!-- 3. Aguardando Agendamento (Alerta Operacional) -->
        <a href="#fila-agendamentos" class="vendedor-kpi-card <?= ((int)($kpis['aguardando_agendamento'] ?? 0) > 0) ? 'is-amber has-alert' : 'is-gray' ?>">
            <div class="vendedor-kpi-card__top">
                <span class="kpi-label">Aguardando Agendamento</span>
                <div class="kpi-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
            </div>
            <div class="kpi-number"><?= (int)($kpis['aguardando_agendamento'] ?? 0) ?></div>
            <div class="kpi-subtext">
                <?php if ((int)($kpis['aguardando_agendamento'] ?? 0) > 0): ?>
                    <span class="badge-alert-pulse"><i class="fa-solid fa-bell"></i> Agendar Vistoria!</span>
                <?php else: ?>
                    <span>Tudo agendado nos portos</span>
                <?php endif; ?>
            </div>
        </a>

        <!-- 4. Vistorias Agendadas -->
        <a href="<?= APP_URL ?>agendamentos" class="vendedor-kpi-card is-cyan">
            <div class="vendedor-kpi-card__top">
                <span class="kpi-label">Vistorias Ativas</span>
                <div class="kpi-icon"><i class="fa-solid fa-ship"></i></div>
            </div>
            <div class="kpi-number"><?= (int)($kpis['agendamentos_ativos'] ?? 0) ?></div>
            <div class="kpi-subtext">
                <span>Confirmadas ou pendentes</span>
            </div>
        </a>

        <!-- 5. Títulos a Receber (Pendente) -->
        <a href="<?= podeAcessar('financeiro') ? APP_URL . 'financeiro' : '#secao-financeiro' ?>" class="vendedor-kpi-card is-orange">
            <div class="vendedor-kpi-card__top">
                <span class="kpi-label">A Receber (Clientes)</span>
                <div class="kpi-icon"><i class="fa-solid fa-hand-holding-dollar"></i></div>
            </div>
            <div class="kpi-number" style="font-size: 1.25rem;"><?= formatarMoeda($kpis['financeiro_receber'] ?? 0) ?></div>
            <div class="kpi-subtext">
                <?php if ((float)($kpis['financeiro_vencido'] ?? 0) > 0): ?>
                    <span class="text-danger">Atrasado: <?= formatarMoeda($kpis['financeiro_vencido']) ?></span>
                <?php else: ?>
                    <span class="text-success">Títulos vigentes</span>
                <?php endif; ?>
            </div>
        </a>

        <!-- 6. Receitas Recebidas no Mês -->
        <a href="<?= podeAcessar('financeiro') ? APP_URL . 'financeiro' : '#secao-financeiro' ?>" class="vendedor-kpi-card is-emerald">
            <div class="vendedor-kpi-card__top">
                <span class="kpi-label">Recebido no Mês</span>
                <div class="kpi-icon"><i class="fa-solid fa-circle-check"></i></div>
            </div>
            <div class="kpi-number" style="font-size: 1.25rem;"><?= formatarMoeda($kpis['financeiro_recebido_mes'] ?? 0) ?></div>
            <div class="kpi-subtext">
                <span>Pagamentos confirmados</span>
            </div>
        </a>
    </section>

    <!-- 4. ALERTA CRÍTICO: PROPOSTAS ASSINADAS PRONTAS PARA AGENDAMENTO -->
    <?php if (!empty($filaAgendamentos)): ?>
    <section class="dash-panel panel-critical-scheduling" id="fila-agendamentos">
        <header class="panel-header-critical">
            <div>
                <span class="badge-critical-alert"><i class="fa-solid fa-bell"></i> FLUXO OPERACIONAL IMEDIATO</span>
                <h2>Propostas Assinadas Aguardando Agendamento de Vistoria (<?= count($filaAgendamentos) ?>)</h2>
                <p>O cliente assinou e aceitou a proposta comercial. Faça o agendamento para alocar a data, local e o vistoriador naval.</p>
            </div>
        </header>
        <div class="table-responsive">
            <table class="table-vendedor">
                <thead>
                    <tr>
                        <th>Proposta / Data</th>
                        <th>Cliente / Armador</th>
                        <th>Embarcação</th>
                        <th>Valor</th>
                        <th class="text-right">Ação Imediata</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($filaAgendamentos as $propFila): 
                        $msgWhats = "Olá! Aqui é {$usuarioNome} da Amazon Certificadora Naval. Sua proposta {$propFila['numero']} já foi aceita. Vamos agendar a vistoria técnica da embarcação {$propFila['embarcacao_nome']}?";
                        $urlWhats = $linkWhatsHelper($propFila['cliente_telefone'], $msgWhats);
                    ?>
                    <tr>
                        <td>
                            <strong><?= h($propFila['numero']) ?></strong>
                            <small class="d-block text-muted">Assinada em <?= date('d/m/Y', strtotime($propFila['assinatura_em'] ?: $propFila['created_at'])) ?></small>
                        </td>
                        <td>
                            <strong><?= h($propFila['cliente_nome']) ?></strong>
                            <?php if (!empty($propFila['cliente_telefone'])): ?>
                                <small class="d-block text-muted"><i class="fa-solid fa-phone"></i> <?= h($propFila['cliente_telefone']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <i class="fa-solid fa-ship text-muted"></i> <?= h($propFila['embarcacao_nome']) ?>
                        </td>
                        <td>
                            <strong class="text-success"><?= formatarMoeda($propFila['valor_total']) ?></strong>
                        </td>
                        <td class="text-right">
                            <div class="action-btn-group">
                                <?php if ($urlWhats): ?>
                                    <a href="<?= h($urlWhats) ?>" target="_blank" rel="noopener noreferrer" class="btn-whats-mini" title="Falar com o cliente no WhatsApp">
                                        <i class="fa-brands fa-whatsapp"></i>
                                    </a>
                                <?php endif; ?>
                                <?php
                                $urlAgendarVistoria = !empty($propFila['agendamento_id'])
                                    ? APP_URL . 'agendamentos/form?id=' . urlencode($propFila['agendamento_id'])
                                    : APP_URL . 'agendamentos/form?proposta_id=' . urlencode($propFila['proposta_id']);
                                ?>
                                <a href="<?= $urlAgendarVistoria ?>" class="btn btn-agendar-cta">
                                    <i class="fa-solid fa-calendar-check"></i> Agendar Vistoria
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php endif; ?>

    <!-- 5. GRID DUPLO: PRÓXIMOS AGENDAMENTOS & CONTROLE FINANCEIRO -->
    <div class="dash-vendedor-two-cols">
        <!-- COLUNA 1: PRÓXIMOS AGENDAMENTOS DE VISTORIA -->
        <section class="dash-panel">
            <header class="dash-panel__header">
                <div>
                    <h2><i class="fa-solid fa-calendar-days text-cyan"></i> Próximos Agendamentos de Vistoria</h2>
                    <small>Vistorias confirmadas dos seus clientes nos portos e estaleiros</small>
                </div>
                <?php if (podeAcessar('agendamentos')): ?>
                    <a href="<?= APP_URL ?>agendamentos/form" class="btn-link-action">+ Novo Agendamento</a>
                <?php endif; ?>
            </header>

            <?php if (empty($proximosAgendamentos)): ?>
                <div class="empty-state-card">
                    <i class="fa-solid fa-calendar-xmark empty-icon"></i>
                    <h4>Nenhum agendamento futuro ativo</h4>
                    <p>Assim que suas propostas forem assinadas, agende a vistoria naval para alocar o vistoriador.</p>
                    <?php if (podeAcessar('agendamentos')): ?>
                        <a href="<?= APP_URL ?>agendamentos/form" class="btn btn-vendedor-secondary btn-sm">
                            <i class="fa-solid fa-calendar-plus"></i> Agendar Agora
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="agendamentos-list">
                    <?php foreach ($proximosAgendamentos as $ag): 
                        $isHoje = ($ag['data_vistoria'] === date('Y-m-d'));
                        $dataExibicao = !empty($ag['data_vistoria']) ? date('d/m/Y', strtotime($ag['data_vistoria'])) : 'Data a definir';
                        $horaExibicao = !empty($ag['hora_vistoria']) ? substr($ag['hora_vistoria'], 0, 5) : '';
                        
                        $statusBadge = match($ag['status']) {
                            'confirmado' => 'badge-success',
                            'em_andamento' => 'badge-primary',
                            default => 'badge-warning'
                        };

                        $msgWhatsCli = "Olá, {$ag['cliente_nome']}! Confirmando os detalhes da vistoria da embarcação {$ag['embarcacao_nome']}: marcada para {$dataExibicao} às {$horaExibicao} no local {$ag['local']}. Vistoriador: {$ag['vistoriador_nome']}.";
                        $urlWhatsCli = $linkWhatsHelper($ag['cliente_telefone'], $msgWhatsCli);
                    ?>
                    <div class="agendamento-card-row">
                        <div class="agendamento-date-badge <?= $isHoje ? 'is-today' : '' ?>">
                            <span class="day"><?= !empty($ag['data_vistoria']) ? date('d', strtotime($ag['data_vistoria'])) : '?' ?></span>
                            <span class="month"><?= !empty($ag['data_vistoria']) ? date('M', strtotime($ag['data_vistoria'])) : 'N/D' ?></span>
                        </div>
                        <div class="agendamento-info">
                            <div class="d-flex align-items-center gap-2">
                                <strong><?= h($ag['embarcacao_nome']) ?></strong>
                                <?php if ($isHoje): ?>
                                    <span class="badge badge-danger" style="font-size: 0.7rem;">HOJE</span>
                                <?php endif; ?>
                                <span class="badge <?= $statusBadge ?>" style="font-size: 0.72rem;"><?= ucfirst($ag['status']) ?></span>
                            </div>
                            <small class="d-block text-muted">
                                <i class="fa-solid fa-user text-muted"></i> <?= h($ag['cliente_nome']) ?> · 
                                <i class="fa-solid fa-user-shield text-muted"></i> Vistoriador: <?= h($ag['vistoriador_nome'] ?? 'A definir') ?>
                            </small>
                            <small class="d-block text-muted">
                                <i class="fa-solid fa-location-dot text-muted"></i> <?= h($ag['local'] ?? 'Porto / A definir') ?> 
                                <?php if ($horaExibicao): ?>
                                    · <i class="fa-regular fa-clock text-muted"></i> <?= h($horaExibicao) ?>
                                <?php endif; ?>
                            </small>
                        </div>
                        <div class="agendamento-actions">
                            <?php if ($urlWhatsCli): ?>
                                <a href="<?= h($urlWhatsCli) ?>" target="_blank" rel="noopener noreferrer" class="btn-whats-mini" title="Enviar lembrete da vistoria no WhatsApp">
                                    <i class="fa-brands fa-whatsapp"></i>
                                </a>
                            <?php endif; ?>
                            <a href="<?= APP_URL ?>agendamentos/form?id=<?= urlencode($ag['id']) ?>" class="btn-action-mini" title="Ver detalhes do agendamento">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- COLUNA 2: CONTROLE FINANCEIRO & RECEBÍVEIS (SEÇÃO FINANCEIRO) -->
        <section class="dash-panel" id="secao-financeiro">
            <header class="dash-panel__header">
                <div>
                    <h2><i class="fa-solid fa-hand-holding-dollar text-emerald"></i> Controle Financeiro das Suas Propostas</h2>
                    <small>Acompanhe pagamentos, pendências e cobranças dos clientes</small>
                </div>
                <?php if (podeAcessar('financeiro')): ?>
                    <a href="<?= APP_URL ?>financeiro" class="btn-link-action">Ver Financeiro Completo</a>
                <?php endif; ?>
            </header>

            <?php if (empty($financeiroRecentes)): ?>
                <div class="empty-state-card">
                    <i class="fa-solid fa-receipt empty-icon"></i>
                    <h4>Nenhum lançamento financeiro recente</h4>
                    <p>Ao aprovar e emitir propostas com parcelamento ou entrada, os títulos aparecem aqui para você acompanhar a quitação.</p>
                    <?php if (podeAcessar('comercial')): ?>
                        <a href="<?= APP_URL ?>comercial/nova" class="btn btn-vendedor-primary btn-sm">
                            <i class="fa-solid fa-plus"></i> Criar Proposta
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-vendedor">
                        <thead>
                            <tr>
                                <th>Cliente / Título</th>
                                <th>Vencimento</th>
                                <th>Valor</th>
                                <th>Status</th>
                                <th class="text-right">PIX / Contato</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($financeiroRecentes as $fin): 
                                $isAtrasado = ($fin['status'] === 'PENDENTE' && !empty($fin['data_vencimento']) && $fin['data_vencimento'] < date('Y-m-d'));
                                $statusBadge = match($fin['status']) {
                                    'PAGO' => 'badge-success',
                                    'PARCIAL' => 'badge-info',
                                    default => ($isAtrasado ? 'badge-danger' : 'badge-warning')
                                };
                                $statusTexto = $isAtrasado ? 'ATRASADO' : $fin['status'];

                                $msgWhatsPix = "Olá, {$fin['cliente_nome']}! Aqui é {$usuarioNome} da Amazon Certificadora Naval. Seguem os dados para quitação do título de " . formatarMoeda($fin['valor']) . ($fin['proposta_numero'] ? " ref. à proposta {$fin['proposta_numero']}" : "") . ".";
                                if (!empty($fin['chave_pix'])) {
                                    $msgWhatsPix .= " Chave PIX: {$fin['chave_pix']}";
                                }
                                $urlWhatsPix = $linkWhatsHelper($fin['cliente_telefone'], $msgWhatsPix);
                            ?>
                            <tr>
                                <td>
                                    <strong><?= h($fin['cliente_nome'] ?? 'Cliente') ?></strong>
                                    <small class="d-block text-muted"><?= h(mb_strimwidth($fin['descricao'], 0, 38, '…')) ?></small>
                                </td>
                                <td>
                                    <?php if (!empty($fin['data_vencimento'])): ?>
                                        <span class="<?= $isAtrasado ? 'text-danger font-weight-bold' : '' ?>">
                                            <?= date('d/m/Y', strtotime($fin['data_vencimento'])) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= formatarMoeda($fin['valor']) ?></strong>
                                </td>
                                <td>
                                    <span class="badge <?= $statusBadge ?>"><?= $statusTexto ?></span>
                                </td>
                                <td class="text-right">
                                    <?php if ($urlWhatsPix): ?>
                                        <a href="<?= h($urlWhatsPix) ?>" target="_blank" rel="noopener noreferrer" class="btn-whats-mini" title="Enviar dados de pagamento no WhatsApp">
                                            <i class="fa-brands fa-whatsapp"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <!-- 6. GRID DUPLO: FUNIL COMERCIAL & MINHAS PROPOSTAS RECENTES -->
    <div class="dash-vendedor-two-cols">
        <!-- COLUNA 1: FUNIL DE PROPOSTAS -->
        <section class="dash-panel">
            <header class="dash-panel__header">
                <div>
                    <h2><i class="fa-solid fa-filter text-primary"></i> Funil Comercial</h2>
                    <small>Distribuição das suas propostas por estágio de negociação</small>
                </div>
                <a href="<?= APP_URL ?>comercial" class="btn-link-action">Gerenciar Propostas</a>
            </header>

            <div class="funnel-container">
                <?php 
                $maxFunil = max(array_values($funil) ?: [1]);
                $estagios = [
                    'rascunho'                => ['label' => 'Rascunhos', 'cor' => '#94a3b8', 'icon' => 'fa-pencil'],
                    'enviada'                 => ['label' => 'Enviadas ao Cliente', 'cor' => '#3b82f6', 'icon' => 'fa-paper-plane'],
                    'assinada'                => ['label' => 'Assinadas / Fechadas', 'cor' => '#10b981', 'icon' => 'fa-file-signature'],
                    'recusada'                => ['label' => 'Recusadas', 'cor' => '#ef4444', 'icon' => 'fa-circle-xmark'],
                    'aguardando_agendamento'  => ['label' => 'Aguardando Agendamento', 'cor' => '#f59e0b', 'icon' => 'fa-clock-rotate-left'],
                ];
                foreach ($estagios as $chave => $cfg): 
                    $qtd = (int)($funil[$chave] ?? 0);
                    $pct = $maxFunil > 0 ? max(8, round(($qtd / $maxFunil) * 100)) : 8;
                ?>
                <div class="funnel-item">
                    <div class="funnel-item__header">
                        <span><i class="fa-solid <?= $cfg['icon'] ?>" style="color: <?= $cfg['cor'] ?>; width: 16px;"></i> <?= $cfg['label'] ?></span>
                        <strong><?= $qtd ?></strong>
                    </div>
                    <div class="funnel-item__bar-bg">
                        <div class="funnel-item__bar-fill" style="width: <?= $pct ?>%; background-color: <?= $cfg['cor'] ?>;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- COLUNA 2: MINHAS PROPOSTAS RECENTES -->
        <section class="dash-panel">
            <header class="dash-panel__header">
                <div>
                    <h2><i class="fa-solid fa-file-invoice text-blue"></i> Minhas Propostas Recentes</h2>
                    <small>Últimas negociações iniciadas ou atualizadas por você</small>
                </div>
                <a href="<?= APP_URL ?>comercial" class="btn-link-action">Ver Todas</a>
            </header>

            <?php if (empty($recentes)): ?>
                <div class="empty-state-card">
                    <i class="fa-solid fa-file-invoice-dollar empty-icon"></i>
                    <h4>Você ainda não possui propostas</h4>
                    <p>Comece gerando sua primeira proposta comercial para emissão de certificados estatutários ou vistorias.</p>
                    <a href="<?= APP_URL ?>comercial/nova" class="btn btn-vendedor-primary btn-sm">
                        <i class="fa-solid fa-plus"></i> Gerar Primeira Proposta
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-vendedor">
                        <thead>
                            <tr>
                                <th>Proposta</th>
                                <th>Cliente / Embarcação</th>
                                <th>Valor</th>
                                <th>Status</th>
                                <th class="text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentes as $prop): 
                                $badgeProp = match($prop['status']) {
                                    'assinada' => 'badge-success',
                                    'enviada' => 'badge-info',
                                    'recusada' => 'badge-danger',
                                    'cancelada' => 'badge-warning',
                                    default => 'badge-secondary'
                                };
                                $msgWhatsProp = "Olá, {$prop['cliente_nome']}! Aqui é {$usuarioNome} da Amazon Certificadora. Segue o acompanhamento da proposta naval {$prop['numero']}.";
                                $urlWhatsProp = $linkWhatsHelper($prop['cliente_telefone'], $msgWhatsProp);
                            ?>
                            <tr>
                                <td>
                                    <strong><?= h($prop['numero']) ?></strong>
                                    <small class="d-block text-muted"><?= date('d/m/Y', strtotime($prop['updated_at'] ?? $prop['created_at'])) ?></small>
                                </td>
                                <td>
                                    <strong><?= h($prop['cliente_nome']) ?></strong>
                                    <small class="d-block text-muted"><i class="fa-solid fa-ship"></i> <?= h($prop['embarcacao_nome'] ?? 'Embarcação') ?></small>
                                </td>
                                <td>
                                    <strong><?= formatarMoeda($prop['valor_total']) ?></strong>
                                </td>
                                <td>
                                    <span class="badge <?= $badgeProp ?>"><?= ucfirst($prop['status']) ?></span>
                                </td>
                                <td class="text-right">
                                    <div class="action-btn-group">
                                        <?php if ($urlWhatsProp): ?>
                                            <a href="<?= h($urlWhatsProp) ?>" target="_blank" rel="noopener noreferrer" class="btn-whats-mini" title="Conversar no WhatsApp">
                                                <i class="fa-brands fa-whatsapp"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="<?= APP_URL ?>comercial?proposta=<?= urlencode($prop['id']) ?>" class="btn-action-mini" title="Abrir proposta no Comercial">
                                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <!-- 7. CARTEIRA RÁPIDA DE CLIENTES & ATORES NAVAIS (ATALHO DE VENDAS) -->
    <?php if (!empty($carteiraClientes)): ?>
    <section class="dash-panel">
        <header class="dash-panel__header">
            <div>
                <h2><i class="fa-solid fa-users text-primary"></i> Acesso Rápido: Clientes & Armadores Ativos</h2>
                <small>Gere propostas ou inicie contato com 1 clique para armadores, proprietários e despachantes</small>
            </div>
            <a href="<?= APP_URL ?>clientes" class="btn-link-action">Ver Todos os Clientes</a>
        </header>

        <div class="carteira-grid">
            <?php foreach ($carteiraClientes as $cli): 
                $badgePerfil = match($cli['perfil']) {
                    'armador' => 'badge-primary',
                    'despachante' => 'badge-warning',
                    default => 'badge-info'
                };
                $msgWhatsAtendimento = "Olá! Aqui é {$usuarioNome} da equipe comercial da Amazon Certificadora Naval. Em que podemos lhe ajudar hoje?";
                $urlWhatsAtendimento = $linkWhatsHelper($cli['telefone'], $msgWhatsAtendimento);
            ?>
            <div class="cliente-quick-card">
                <div class="cliente-quick-card__info">
                    <div class="d-flex align-items-center gap-2">
                        <strong><?= h($cli['nome']) ?></strong>
                        <span class="badge <?= $badgePerfil ?>" style="font-size: 0.7rem;"><?= ucfirst($cli['perfil']) ?></span>
                    </div>
                    <small class="text-muted d-block">
                        <i class="fa-solid fa-ship"></i> <?= (int)$cli['total_embarcacoes'] ?> embarcação(ões) · 
                        <?php if (!empty($cli['telefone'])): ?>
                            <i class="fa-solid fa-phone"></i> <?= h($cli['telefone']) ?>
                        <?php else: ?>
                            Sem telefone
                        <?php endif; ?>
                    </small>
                </div>
                <div class="cliente-quick-card__actions">
                    <?php if ($urlWhatsAtendimento): ?>
                        <a href="<?= h($urlWhatsAtendimento) ?>" target="_blank" rel="noopener noreferrer" class="btn-whats-mini" title="WhatsApp com <?= h($cli['nome']) ?>">
                            <i class="fa-brands fa-whatsapp"></i>
                        </a>
                    <?php endif; ?>
                    <a href="<?= APP_URL ?>comercial/nova?cliente_id=<?= urlencode($cli['id']) ?>" class="btn btn-vendedor-secondary btn-sm" title="Criar Proposta para este cliente">
                        <i class="fa-solid fa-file-plus"></i> Gerar Proposta
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

</div>

<style>
/* ========================================================
 * PAINEL EXCLUSIVO DO VENDEDOR - DESIGN SYSTEM NAVAL V3
 * ======================================================== */
.dash-vendedor-container {
    display: flex;
    flex-direction: column;
    gap: 22px;
    padding-bottom: 24px;
}

/* 1. Header do Vendedor */
.dash-vendedor-header {
    background: var(--bg-surface, #ffffff);
    border: 1px solid var(--border, #e2e8f0);
    border-radius: 14px;
    padding: 22px 26px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.03);
}

.badge-role-commercial {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(30, 77, 63, 0.1);
    color: var(--cor-primaria, #1e4d3f);
    font-size: 0.75rem;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 20px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 6px;
}

.dash-vendedor-header__info h1 {
    margin: 0 0 6px;
    font-size: 1.45rem;
    font-weight: 800;
    color: var(--cor-texto, #1e293b);
}

.dash-vendedor-header__info p {
    margin: 0;
    color: var(--cor-texto-secundario, #64748b);
    font-size: 0.88rem;
}

.dash-vendedor-header__actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.btn-vendedor-primary {
    background: var(--cor-primaria, #1e4d3f) !important;
    border: 1px solid var(--cor-primaria, #1e4d3f) !important;
    color: #ffffff !important;
    font-weight: 600;
    font-size: 0.85rem;
    padding: 8px 16px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    text-decoration: none !important;
    transition: all 0.2s ease;
    box-shadow: 0 2px 6px rgba(30, 77, 63, 0.25);
}

.btn-vendedor-primary:hover {
    background: #16382e !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(30, 77, 63, 0.35);
}

.btn-vendedor-secondary {
    background: #ffffff !important;
    border: 1px solid var(--border, #cbd5e1) !important;
    color: var(--cor-texto, #334155) !important;
    font-weight: 600;
    font-size: 0.85rem;
    padding: 8px 14px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    text-decoration: none !important;
    transition: all 0.2s ease;
}

.btn-vendedor-secondary:hover {
    border-color: var(--cor-primaria, #1e4d3f) !important;
    color: var(--cor-primaria, #1e4d3f) !important;
    background: rgba(30, 77, 63, 0.04) !important;
    transform: translateY(-1px);
}

.btn-vendedor-outline {
    background: transparent !important;
    border: 1px dashed var(--border, #94a3b8) !important;
    color: var(--cor-texto-secundario, #64748b) !important;
    font-weight: 600;
    font-size: 0.85rem;
    padding: 8px 14px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    text-decoration: none !important;
    transition: all 0.2s ease;
}

.btn-vendedor-outline:hover {
    border-color: #059669 !important;
    color: #059669 !important;
    background: rgba(5, 150, 105, 0.05) !important;
}

/* 2. Meta Comercial & Contribuição */
.dash-vendedor-meta-card {
    background: var(--bg-surface, #ffffff);
    border: 1px solid var(--border, #e2e8f0);
    border-radius: 14px;
    padding: 20px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.03);
}

.meta-inner-grid {
    display: grid;
    grid-template-columns: 1.3fr 1fr;
    gap: 20px;
    align-items: center;
}

.meta-column-individual {
    display: grid;
    grid-template-columns: 1.2fr 1fr;
    gap: 14px;
    align-items: stretch;
}

.user-perf-card {
    background: var(--bg-surface-secondary, #f8fafc);
    border: 1px solid var(--border, #e2e8f0);
    border-radius: 10px;
    padding: 14px 16px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.user-perf-header {
    display: flex;
    align-items: center;
    gap: 12px;
}

.user-perf-icon {
    width: 38px;
    height: 38px;
    border-radius: 8px;
    background: rgba(16, 185, 129, 0.12);
    color: #10b981;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
}

.user-perf-title {
    font-size: 0.76rem;
    font-weight: 700;
    text-transform: uppercase;
    color: var(--cor-texto-secundario, #64748b);
    display: block;
}

.user-perf-val {
    margin: 2px 0 0;
    font-size: 1.35rem;
    font-weight: 800;
    color: var(--cor-texto, #1e293b);
}

.progress-bar-vendedor {
    height: 6px;
    background: #e2e8f0;
    border-radius: 10px;
    overflow: hidden;
    margin: 10px 0 6px;
}

.progress-bar-vendedor__fill {
    height: 100%;
    background: linear-gradient(90deg, #10b981, #059669);
    border-radius: 10px;
    transition: width 0.5s ease;
}

.user-perf-desc {
    margin: 0;
    font-size: 0.78rem;
    color: var(--cor-texto-secundario, #64748b);
}

.user-conversion-card {
    background: var(--bg-surface-secondary, #f8fafc);
    border: 1px solid var(--border, #e2e8f0);
    border-radius: 10px;
    padding: 14px 16px;
    display: flex;
    align-items: center;
    gap: 14px;
}

.conversion-ring {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    border: 3px solid #3b82f6;
    background: rgba(59, 130, 246, 0.08);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    font-weight: 800;
    color: #3b82f6;
    flex-shrink: 0;
}

.user-conversion-card strong {
    display: block;
    font-size: 0.88rem;
    color: var(--cor-texto, #1e293b);
}

.user-conversion-card small {
    display: block;
    font-size: 0.75rem;
    color: var(--cor-texto-secundario, #64748b);
    line-height: 1.25;
}

/* 3. Grid de KPIs do Vendedor */
.dash-vendedor-kpis {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(185px, 1fr));
    gap: 14px;
}

.vendedor-kpi-card {
    background: var(--bg-surface, #ffffff);
    border: 1px solid var(--border, #e2e8f0);
    border-radius: 12px;
    padding: 16px 18px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    text-decoration: none !important;
    transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.02);
}

.vendedor-kpi-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.08);
}

.vendedor-kpi-card__top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
}

.kpi-label {
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
    color: var(--cor-texto-secundario, #64748b);
}

.kpi-icon {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}

.vendedor-kpi-card.is-blue .kpi-icon { background: rgba(59, 130, 246, 0.12); color: #3b82f6; }
.vendedor-kpi-card.is-green .kpi-icon { background: rgba(16, 185, 129, 0.12); color: #10b981; }
.vendedor-kpi-card.is-amber .kpi-icon { background: rgba(245, 158, 11, 0.12); color: #f59e0b; }
.vendedor-kpi-card.is-cyan .kpi-icon { background: rgba(6, 182, 212, 0.12); color: #06b6d4; }
.vendedor-kpi-card.is-orange .kpi-icon { background: rgba(249, 115, 22, 0.12); color: #f97316; }
.vendedor-kpi-card.is-emerald .kpi-icon { background: rgba(5, 150, 105, 0.12); color: #059669; }
.vendedor-kpi-card.is-gray .kpi-icon { background: rgba(148, 163, 184, 0.12); color: #94a3b8; }

.vendedor-kpi-card.has-alert {
    border-color: #f59e0b;
    background: linear-gradient(180deg, rgba(245, 158, 11, 0.05) 0%, transparent 100%);
}

.kpi-number {
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--cor-texto, #1e293b);
    margin: 2px 0 6px;
    line-height: 1.1;
}

.kpi-subtext {
    font-size: 0.78rem;
    color: var(--cor-texto-secundario, #64748b);
}

.badge-alert-pulse {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: #fef3c7;
    color: #b45309;
    font-weight: 700;
    font-size: 0.72rem;
    padding: 2px 6px;
    border-radius: 4px;
    animation: alertPulse 1.8s infinite;
}

@keyframes alertPulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.04); }
    100% { transform: scale(1); }
}

/* 4. Alerta Crítico de Propostas Prontas para Agendamento */
.panel-critical-scheduling {
    border: 1px solid #fcd34d !important;
    background: linear-gradient(180deg, #fffbeb 0%, #ffffff 100%) !important;
    border-left: 5px solid #f59e0b !important;
}

.panel-header-critical {
    padding: 16px 20px;
    border-bottom: 1px solid rgba(245, 158, 11, 0.2);
}

.badge-critical-alert {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #f59e0b;
    color: #ffffff;
    font-size: 0.72rem;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 4px;
    text-transform: uppercase;
    margin-bottom: 6px;
}

.panel-header-critical h2 {
    margin: 0 0 4px;
    font-size: 1.15rem;
    font-weight: 700;
    color: #92400e;
}

.panel-header-critical p {
    margin: 0;
    font-size: 0.84rem;
    color: #b45309;
}

.btn-agendar-cta {
    background: #1e4d3f !important;
    border-color: #1e4d3f !important;
    color: #ffffff !important;
    font-weight: 600;
    font-size: 0.8rem;
    padding: 6px 12px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    text-decoration: none !important;
    transition: all 0.2s ease;
}

.btn-agendar-cta:hover {
    background: #14352b !important;
    transform: translateY(-1px);
    box-shadow: 0 3px 8px rgba(30, 77, 63, 0.25);
}

/* 5. Painéis Genéricos e Grid Duplo */
.dash-vendedor-two-cols {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.dash-panel {
    background: var(--bg-surface, #ffffff);
    border: 1px solid var(--border, #e2e8f0);
    border-radius: 14px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.03);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.dash-panel__header {
    padding: 16px 20px;
    border-bottom: 1px solid var(--border, #e2e8f0);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
}

.dash-panel__header h2 {
    margin: 0 0 2px;
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--cor-texto, #1e293b);
    display: flex;
    align-items: center;
    gap: 8px;
}

.dash-panel__header small {
    color: var(--cor-texto-secundario, #64748b);
    font-size: 0.8rem;
}

.btn-link-action {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--cor-primaria, #1e4d3f);
    text-decoration: none;
}

.btn-link-action:hover {
    text-decoration: underline;
}

/* Tabelas e Linhas Modernas */
.table-vendedor {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.85rem;
}

.table-vendedor th {
    background: var(--bg-surface-secondary, #f8fafc);
    color: var(--cor-texto-secundario, #64748b);
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    padding: 10px 18px;
    border-bottom: 1px solid var(--border, #e2e8f0);
}

.table-vendedor td {
    padding: 12px 18px;
    border-bottom: 1px solid var(--border, #f1f5f9);
    color: var(--cor-texto, #334155);
    vertical-align: middle;
}

.table-vendedor tbody tr:last-child td {
    border-bottom: none;
}

.table-vendedor tbody tr:hover {
    background: rgba(30, 77, 63, 0.02);
}

/* Botões de Ação Mini e WhatsApp */
.action-btn-group {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    justify-content: flex-end;
}

.btn-whats-mini {
    background: #25d366 !important;
    border: 1px solid #25d366 !important;
    color: #ffffff !important;
    width: 30px;
    height: 30px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    text-decoration: none !important;
    transition: all 0.2s ease;
}

.btn-whats-mini:hover {
    background: #1ebc59 !important;
    transform: translateY(-1px);
    box-shadow: 0 3px 8px rgba(37, 211, 102, 0.35);
}

.btn-action-mini {
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    color: #475569;
    width: 30px;
    height: 30px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    text-decoration: none !important;
    transition: all 0.2s ease;
}

.btn-action-mini:hover {
    border-color: var(--cor-primaria, #1e4d3f);
    color: var(--cor-primaria, #1e4d3f);
    background: #ffffff;
}

/* Agendamentos List */
.agendamentos-list {
    padding: 10px 18px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.agendamento-card-row {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 10px 12px;
    border-radius: 8px;
    border: 1px solid var(--border, #e2e8f0);
    background: var(--bg-surface-secondary, #f8fafc);
    transition: border-color 0.2s ease;
}

.agendamento-card-row:hover {
    border-color: #06b6d4;
}

.agendamento-date-badge {
    width: 44px;
    height: 44px;
    border-radius: 8px;
    background: #e2e8f0;
    color: #334155;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.agendamento-date-badge.is-today {
    background: #fee2e2;
    color: #dc2626;
    border: 1px solid #fca5a5;
}

.agendamento-date-badge .day {
    font-size: 1.05rem;
    font-weight: 800;
    line-height: 1;
}

.agendamento-date-badge .month {
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
}

.agendamento-info {
    flex: 1;
    min-width: 0;
}

.agendamento-actions {
    display: flex;
    align-items: center;
    gap: 6px;
}

/* Funil de Vendas */
.funnel-container {
    padding: 16px 20px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.funnel-item__header {
    display: flex;
    justify-content: space-between;
    font-size: 0.82rem;
    margin-bottom: 4px;
    color: var(--cor-texto, #334155);
}

.funnel-item__bar-bg {
    height: 8px;
    background: #f1f5f9;
    border-radius: 10px;
    overflow: hidden;
}

.funnel-item__bar-fill {
    height: 100%;
    border-radius: 10px;
    transition: width 0.4s ease;
}

/* Carteira Rápida de Clientes */
.carteira-grid {
    padding: 14px 20px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 12px;
}

.cliente-quick-card {
    border: 1px solid var(--border, #e2e8f0);
    border-radius: 8px;
    padding: 12px 14px;
    background: var(--bg-surface-secondary, #f8fafc);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    transition: border-color 0.2s ease, background-color 0.2s ease;
}

.cliente-quick-card:hover {
    border-color: var(--cor-primaria, #1e4d3f);
    background: #ffffff;
}

.cliente-quick-card__info {
    min-width: 0;
    flex: 1;
}

.cliente-quick-card__actions {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-shrink: 0;
}

/* Empty States */
.empty-state-card {
    padding: 35px 20px;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.empty-icon {
    font-size: 2.5rem;
    color: #cbd5e1;
    margin-bottom: 12px;
}

.empty-state-card h4 {
    margin: 0 0 6px;
    font-size: 1rem;
    font-weight: 700;
    color: var(--cor-texto, #334155);
}

.empty-state-card p {
    margin: 0 0 16px;
    font-size: 0.82rem;
    color: var(--cor-texto-secundario, #64748b);
    max-width: 380px;
}

/* Cores de apoio */
.text-cyan { color: #06b6d4 !important; }
.text-emerald { color: #059669 !important; }
.text-blue { color: #3b82f6 !important; }

/* Responsividade */
@media (max-width: 1024px) {
    .meta-inner-grid {
        grid-template-columns: 1fr;
    }
    .dash-vendedor-two-cols {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .meta-column-individual {
        grid-template-columns: 1fr;
    }
    .dash-vendedor-header {
        flex-direction: column;
        align-items: flex-start;
    }
    .dash-vendedor-header__actions {
        width: 100%;
    }
    .dash-vendedor-header__actions .btn {
        flex: 1;
        justify-content: center;
    }
}
</style>
