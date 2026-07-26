<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
$cargo = getCargo();
exigirAcesso('relatorios_aprovacao');

global $pdo;

$stmt = $pdo->prepare("SELECT v.id, v.numero, v.status, v.data_vistoria, v.atualizado_em as data_envio, v.agendamento_id, e.nome as embarcacao, e.registro, COALESCE(u.nome,'Sem vistoriador vinculado') as vistoriador, COUNT(ve.id) as total_itens, SUM(CASE WHEN ve.conforme = 'nao' THEN 1 ELSE 0 END) as itens_nao_conformes, (SELECT COUNT(*) FROM vistoria_anexos va WHERE va.vistoria_id = v.id) AS total_fotos FROM vistorias v JOIN embarcacoes e ON v.embarcacao_id = e.id LEFT JOIN agendamentos a ON v.agendamento_id = a.id LEFT JOIN usuarios u ON a.vistoriador_id = u.id LEFT JOIN vistoria_exigencias ve ON v.id = ve.vistoria_id WHERE v.status = 'AGUARDANDO_APROVACAO' AND NOT EXISTS (SELECT 1 FROM vistorias vf WHERE vf.relatorio_anterior_id=v.id AND vf.status<>'CANCELADA') GROUP BY v.id ORDER BY v.atualizado_em ASC");
$stmt->execute();
$pendentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt2 = $pdo->prepare("SELECT v.id, v.numero, v.status, v.data_aprovacao, v.observacao_admin, v.agendamento_id, e.nome as embarcacao, COALESCE(u.nome,'Sem vistoriador vinculado') as vistoriador, adm.nome as aprovado_por_nome, EXISTS(SELECT 1 FROM vistoria_exigencias ve WHERE ve.vistoria_id=v.id AND ve.antes_de_suspender=1 AND ve.conforme='nao' AND ve.status_item<>'cumprida') possui_as FROM vistorias v JOIN embarcacoes e ON v.embarcacao_id = e.id LEFT JOIN agendamentos a ON v.agendamento_id = a.id LEFT JOIN usuarios u ON a.vistoriador_id = u.id LEFT JOIN usuarios adm ON v.aprovado_por = adm.id WHERE v.status IN ('APROVADA','APROVADA_COM_EXIGENCIAS','RETORNO_AS','REPROVADA') ORDER BY v.data_aprovacao DESC LIMIT 20");
$stmt2->execute();
$historico = $stmt2->fetchAll(PDO::FETCH_ASSOC);

$titulo_page = 'Aprovação de Relatórios - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<style>
.approval-table {
    table-layout: auto !important;
    min-width: 940px;
}
.approval-table th:last-child,
.approval-table td:last-child {
    width: 84px;
    min-width: 84px;
    text-align: center;
    white-space: nowrap;
}
.approval-action-btn {
    width: 36px;
    height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    white-space: nowrap;
}
</style>
<div class="conteudo-principal">
    <div class="page-header">
        <h1><i class="fas fa-clipboard-check"></i> Aprovação de Relatórios</h1>
        <?php if (count($pendentes) > 0): ?>
            <span class="badge badge-warning"><?= count($pendentes) ?> aguardando</span>
        <?php endif; ?>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h3>Aguardando Aprovação</h3>
        </div>
        <div class="card-body">
            <?php if (empty($pendentes)): ?>
                <p class="text-muted text-center py-4">
                    <i class="fas fa-check-circle fa-2x mb-2 d-block text-success"></i>
                    Nenhum relatório aguardando aprovação.
                </p>
            <?php else: ?>
                <div class="table-responsive">
                <table class="table table-hover approval-table">
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th>Embarcação</th>
                            <th>Vistoriador</th>
                            <th>Data Vistoria</th>
                            <th>Enviado em</th>
                            <th>Itens</th>
                            <th>Fotos</th>
                            <th>Não Conformes</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendentes as $rel): ?>
                        <tr>
                            <td><?= h($rel['numero'] ?? 'S/N') ?></td>
                            <td><?= h($rel['embarcacao']) ?> <small class="text-muted"><?= h($rel['registro']) ?></small></td>
                            <td><?= h($rel['vistoriador']) ?></td>
                            <td><?= $rel['data_vistoria'] ? formatarData($rel['data_vistoria']) : '—' ?></td>
                            <td><?= formatarDataCompleta($rel['data_envio']) ?></td>
                            <td><?= $rel['total_itens'] ?></td>
                            <td><span class="badge badge-info"><i class="fas fa-camera"></i> <?= (int)$rel['total_fotos'] ?></span></td>
                            <td>
                                <?php if ($rel['itens_nao_conformes'] > 0): ?>
                                    <span class="badge badge-danger"><?= $rel['itens_nao_conformes'] ?> não conforme(s)</span>
                                <?php else: ?>
                                    <span class="badge badge-success">Todos conformes</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php $urlRevisao = !empty($rel['agendamento_id']) ? APP_URL.'vistorias/relatorio?agendamento_id='.urlencode($rel['agendamento_id']).'&vistoria_id='.urlencode($rel['id']) : APP_URL.'vistorias/detalhe?id='.urlencode($rel['id']); ?>
                                <a href="<?= h($urlRevisao) ?>"
                                   class="btn btn-primary btn-sm approval-action-btn"
                                   title="Revisar e aprovar"
                                   aria-label="Revisar e aprovar">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header" style="cursor:pointer" >
            <h3><i class="fas fa-history"></i> Histórico de Aprovações <i class="fas fa-chevron-down" id="icone-historico"></i></h3>
        </div>
        <div class="card-body" id="historico-body" >
            <?php if (empty($historico)): ?>
                <p class="text-muted">Nenhuma aprovação registrada ainda.</p>
            <?php else: ?>
                <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr><th>Número</th><th>Embarcação</th><th>Vistoriador</th><th>Resultado</th><th>Data</th><th>Aprovado por</th><th>Obs.</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historico as $h): ?>
                        <tr>
                            <td><?= h($h['numero'] ?? 'S/N') ?></td>
                            <td><?= h($h['embarcacao']) ?></td>
                            <td><?= h($h['vistoriador']) ?></td>
                            <td>
                                <?php
                                $cores = ['APROVADA'=>'badge-success','APROVADA_COM_EXIGENCIAS'=>'badge-warning','RETORNO_AS'=>'badge-danger','REPROVADA'=>'badge-danger'];
                                $labels = ['APROVADA'=>'Aprovada','APROVADA_COM_EXIGENCIAS'=>'Aprovada c/ Exigências','RETORNO_AS'=>'Retorno A/S necessário','REPROVADA'=>'Reprovada'];
                                $cor = $cores[$h['status']] ?? 'badge-secondary';
                                $label = $labels[$h['status']] ?? $h['status'];
                                ?>
                                <span class="badge <?= $cor ?>"><?= $label ?></span>
                            </td>
                            <td><?= $h['data_aprovacao'] ? formatarData($h['data_aprovacao']) : '—' ?></td>
                            <td><?= h($h['aprovado_por_nome'] ?? '—') ?></td>
                            <td><?= h(mb_strimwidth($h['observacao_admin'] ?? '', 0, 40, '...')) ?></td>
                            <?php $urlHistorico = !empty($h['agendamento_id']) ? APP_URL.'vistorias/relatorio?agendamento_id='.urlencode($h['agendamento_id']).'&vistoria_id='.urlencode($h['id']) : APP_URL.'vistorias/detalhe?id='.urlencode($h['id']); ?>
                            <td><a href="<?= h($urlHistorico) ?>" class="btn btn-sm btn-secondary">Ver</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function toggleHistorico() {
    const body = document.getElementById('historico-body');
    const icone = document.getElementById('icone-historico');
    if (body.style.display === 'none') {
        body.style.display = 'block';
        icone.className = 'fas fa-chevron-up';
    } else {
        body.style.display = 'none';
        icone.className = 'fas fa-chevron-down';
    }
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
