<?php
/**
 * COMPONENTE: Linha do tempo da cadeia de relatórios e alertas de certificação
 */
?>
<?php if (count($cadeia_relatorios) > 1): ?>
    <div class="form-container" style="margin-bottom:20px">
        <div class="form-header"><h3><i class="fas fa-timeline"></i>
            <?= $tipo_retorno_cadeia === 'AS'
                ? 'Linha do tempo dos relatórios A/S'
                : 'Linha do tempo dos relatórios de exigências' ?>
        </h3></div>
        <div style="display:grid;gap:10px;padding:16px">
            <?php foreach ($cadeia_relatorios as $indiceCadeia => $itemCadeia): ?>
                <div style="display:flex;justify-content:space-between;gap:16px;align-items:center;padding:12px;border:1px solid #dce8e4;border-radius:9px">
                    <span>
                        <strong><?= h($itemCadeia['numero'] ?: $itemCadeia['id']) ?></strong>
                        · <?= $indiceCadeia === 0
                            ? 'Relatório técnico original'
                            : ($tipo_retorno_cadeia === 'AS' ? 'Cumprimento de A/S' : 'Verificação de exigências') ?>
                        · <?= h($itemCadeia['status']) ?>
                    </span>
                    <a class="btn btn-secondary btn-sm" target="_blank" rel="noopener"
                       href="<?= APP_URL ?>vistorias/relatorio_pdf.php?id=<?= urlencode($itemCadeia['id']) ?>">PDF</a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>
<?php if ($relatorio_substituto_aprovado): ?>
    <div class="alert alert-info" style="margin-bottom:20px;">
        <strong>Relatório histórico/substituído — somente leitura.</strong>
        Nenhuma decisão pode ser registrada nesta versão. Use o relatório vigente
        <a href="<?= APP_URL ?>vistorias/relatorio?agendamento_id=<?= urlencode((string)$relatorio_substituto_aprovado['agendamento_id']) ?>&vistoria_id=<?= urlencode((string)$relatorio_substituto_aprovado['id']) ?>"><?= h($relatorio_substituto_aprovado['numero']) ?></a>.
    </div>
<?php endif; ?>
<?php if ($vistoria && $possui_as_pendente && (string)$vistoria['status'] === 'RETORNO_AS'): ?>
    <div class="alert alert-danger" style="margin-bottom:20px;">
        <strong>Certificação bloqueada por exigência A/S.</strong>
        A embarcação não pode receber certificados até a aprovação da verificação de cumprimento.
        <?php if ($relatorio_cumprimento_aberto_id): ?>
            <a class="btn btn-warning ms-3" href="<?= APP_URL ?>vistorias/relatorio?agendamento_id=<?= urlencode($agendamento_id) ?>&vistoria_id=<?= urlencode($relatorio_cumprimento_aberto_id) ?>">Continuar verificação de cumprimento</a>
        <?php elseif (!$retorno_as && getCargo() === 'ADMIN'): ?>
            <form method="POST" action="<?= APP_URL ?>vistorias/actions?action=iniciar_cumprimento_exigencias" style="display:inline-block;margin-left:12px;">
                <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>">
                <input type="hidden" name="vistoria_id" value="<?= h($vistoria['id']) ?>">
                <input type="hidden" name="retorno_tipo" value="AS">
                <button type="submit" class="btn btn-warning"><i class="fas fa-calendar-plus"></i> Criar pendência de retorno A/S</button>
            </form>
        <?php endif; ?>
        <?php if ($retorno_as && $retorno_as['status'] === 'PENDENTE_AGENDAMENTO' && getCargo() === 'ADMIN'): ?>
            <a class="btn btn-warning ms-3" href="<?= APP_URL ?>agendamentos/form?relatorio_origem_id=<?= urlencode($vistoria['id']) ?>">
                <i class="fas fa-calendar-plus"></i> Agendar retorno A/S
            </a>
        <?php elseif ($retorno_as && $retorno_as['status'] === 'CANCELADO' && getCargo() === 'ADMIN'): ?>
            <form method="POST" action="<?= APP_URL ?>vistorias/actions?action=iniciar_cumprimento_exigencias" style="display:inline-block;margin-left:12px;">
                <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>">
                <input type="hidden" name="vistoria_id" value="<?= h($vistoria['id']) ?>">
                <input type="hidden" name="retorno_tipo" value="AS">
                <button type="submit" class="btn btn-warning"><i class="fas fa-rotate-right"></i> Reabrir e agendar retorno</button>
            </form>
        <?php elseif ($retorno_as && in_array($retorno_as['status'], ['AGENDADO','RELATORIO_ENVIADO'], true)): ?>
            <span style="display:inline-block;margin-left:12px">
                Retorno: <strong><?= h($retorno_as['status']) ?></strong>
                <?= !empty($retorno_as['data_vistoria']) ? ' em ' . h(date('d/m/Y', strtotime($retorno_as['data_vistoria']))) : '' ?>
                <?= !empty($retorno_as['vistoriador_nome']) ? ' · ' . h($retorno_as['vistoriador_nome']) : '' ?>
            </span>
        <?php endif; ?>
    </div>
<?php elseif ($vistoria
    && getCargo() === 'ADMIN'
    && $eh_relatorio_vigente
    && (string)$vistoria['status'] === 'APROVADA_COM_EXIGENCIAS'
    && !$possui_as_pendente
    && $possui_exigencia_comum_pendente): ?>
    <div class="alert alert-warning" style="margin-bottom:20px;">
        <strong>Relatório aprovado com exigências pendentes.</strong>
        Os certificados já liberados permanecem válidos e este relatório pode receber uma vistoria de retorno.
        <?php if (!$retorno_as): ?>
            <form method="POST" action="<?= APP_URL ?>vistorias/actions?action=iniciar_cumprimento_exigencias" style="display:inline-block;margin-left:12px;">
                <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>">
                <input type="hidden" name="vistoria_id" value="<?= h($vistoria['id']) ?>">
                <input type="hidden" name="retorno_tipo" value="EXIGENCIAS">
                <button type="submit" class="btn btn-warning"><i class="fas fa-calendar-plus"></i> Reagendar retorno de exigências</button>
            </form>
        <?php elseif ($retorno_as['status'] === 'PENDENTE_AGENDAMENTO'): ?>
            <a class="btn btn-warning ms-3" href="<?= APP_URL ?>agendamentos/form?relatorio_origem_id=<?= urlencode($vistoria['id']) ?>">
                <i class="fas fa-calendar-plus"></i> Agendar retorno de exigências
            </a>
        <?php elseif ($retorno_as['status'] === 'CANCELADO'): ?>
            <form method="POST" action="<?= APP_URL ?>vistorias/actions?action=iniciar_cumprimento_exigencias" style="display:inline-block;margin-left:12px;">
                <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>">
                <input type="hidden" name="vistoria_id" value="<?= h($vistoria['id']) ?>">
                <input type="hidden" name="retorno_tipo" value="EXIGENCIAS">
                <button type="submit" class="btn btn-warning"><i class="fas fa-rotate-right"></i> Reabrir retorno</button>
            </form>
        <?php else: ?>
            <span style="display:inline-block;margin-left:12px">
                Retorno: <strong><?= h($retorno_as['status']) ?></strong>
                <?= !empty($retorno_as['data_vistoria']) ? ' em ' . h(date('d/m/Y', strtotime($retorno_as['data_vistoria']))) : '' ?>
                <?= !empty($retorno_as['vistoriador_nome']) ? ' · ' . h($retorno_as['vistoriador_nome']) : '' ?>
            </span>
        <?php endif; ?>
    </div>
<?php elseif (getCargo() === 'ADMIN' && !empty($liberacao_certificacao['permitido'])): ?>
    <div class="alert alert-success" style="margin-bottom:20px;">
        <strong>Relatório vigente aprovado.</strong> Você pode gerar os certificados agora.
        <a href="<?= APP_URL ?>documentacao/novo_certificado?agendamento_id=<?= urlencode($agendamento_id) ?>&vistoria_id=<?= urlencode((string)$vistoria['id']) ?>" class="btn btn-success ms-3"><i class="fas fa-certificate"></i> Ir para Etapa 2 — Gerar Certificado</a>
    </div>
<?php endif; ?>
