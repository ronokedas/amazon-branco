<?php
/**
 * COMPONENTE: Cabeçalho de contexto da vistoria (OS, Cliente, Embarcação, Categoria NORMAM)
 */
?>
<div class="form-header report-summary-header">
        <div class="report-summary-heading">
            <h3>
                <i class="fas fa-clipboard-list"></i>
                Relatório Técnico de Vistoria
            </h3>
            <span class="help-text">Checklist, exigências e resultado final</span>
        </div>
        <?php if (!empty($vistoria['id'])): ?>
            <a href="<?= APP_URL ?>vistorias/relatorio_pdf.php?id=<?= urlencode($vistoria['id']); ?>"
               target="_blank"
               rel="noopener"
               class="report-pdf-primary"
               data-testid="abrir-pdf-completo">
                <span class="report-pdf-primary-icon"><i class="fas fa-file-pdf"></i></span>
                <span><strong>Visualizar PDF do relatório</strong><small>Disponível também enquanto estiver pendente</small></span>
                <i class="fas fa-arrow-up-right-from-square"></i>
            </a>
        <?php endif; ?>
    </div>

    <!-- ===== DADOS DO AGENDAMENTO ===== -->
    <div class="report-context">
        <div class="report-context-grid">
            <div class="report-context-item">
                <small class="text-muted"><i class="fas fa-file-invoice"></i> OS</small>
                <div style="font-weight: 600;"><?php echo $ag['os_numero'] ? h($ag['os_numero']) : '<em class="text-muted">Pendente</em>'; ?></div>
            </div>
            <div class="report-context-item">
                <small class="text-muted"><i class="fas fa-calendar-day"></i> Data da Vistoria</small>
                <div style="font-weight: 600;"><?php echo formatarData($ag['data_vistoria']); ?></div>
            </div>
            <div class="report-context-item">
                <small class="text-muted"><i class="fas fa-user-check"></i> Vistoriador</small>
                <div style="font-weight: 600;"><?php echo h($ag['vistoriador_nome'] ?? 'Não atribuído'); ?></div>
            </div>
            <div class="report-context-item">
                <small class="text-muted"><i class="fas fa-ship"></i> Categoria Normam</small>
                <div><span class="badge bg-info">Tipo <?php echo strtoupper($categoria_embarcacao); ?></span></div>
            </div>
        </div>

        <div class="report-context-grid report-context-grid--secondary">
            <div class="report-context-item">
                <small class="text-muted"><i class="fas fa-user-tie"></i> Cliente</small>
                <div style="font-weight: 600;"><?php echo h($ag['cliente_nome']); ?></div>
            </div>
            <div class="report-context-item">
                <small class="text-muted"><i class="fas fa-ship"></i> Embarcação</small>
                <div style="font-weight: 600; display:flex; align-items:center; gap:8px;">
                    <img id="thumbFotoCabecalhoEmbarcacao" src="<?= !empty($ag['foto_url']) ? h($ag['foto_url']) : '' ?>" alt="Foto da Embarcação" style="width:28px;height:28px;border-radius:6px;object-fit:cover;border:1px solid #cbd5e1;<?= empty($ag['foto_url']) ? 'display:none;' : '' ?>">
                    <span><?php echo h($ag['embarcacao_nome']); ?> <?php echo $ag['embarcacao_registro'] ? '(' . h($ag['embarcacao_registro']) . ')' : ''; ?></span>
                </div>
            </div>
            <div class="report-context-item report-context-item--wide">
                <small class="text-muted"><i class="fas fa-clipboard-check"></i> Tipo de Vistoria</small>
                <div><?php echo h($ag['tipo_vistoria']); ?></div>
            </div>
            <div class="report-context-item">
                <small class="text-muted"><i class="fas fa-info-circle"></i> Status Agendamento</small>
                <div><?php echo h($ag['status']); ?></div>
            </div>
        </div>
    </div>
