<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/cliente_portal.php';
require_once __DIR__ . '/../../includes/analise_planos.php';

requireClienteSenhaDefinitiva();

$clienteId = (string) clientePortalId();
$embarcacaoIds = clientePortalEmbarcacaoIds($pdo, $clienteId);
$analises = [];

if ($embarcacaoIds) {
    $params = [];
    $in = clientePortalSqlIn($embarcacaoIds, 'ap_emb_', $params);
    $sql = "SELECT ap.id, ap.numero, ap.tipo_processo, ap.enquadramento, ap.status,
                   ap.prazo_agendado_em, e.nome AS embarcacao_nome, u.nome AS analista_nome,
                   (SELECT COALESCE(MAX(s.revisao), 0)
                      FROM analise_planos_submissoes s
                     WHERE s.analise_id = ap.id) AS ultima_revisao
              FROM analises_planos ap
              JOIN embarcacoes e ON e.id = ap.embarcacao_id
         LEFT JOIN usuarios u ON u.id = ap.analista_id
             WHERE ap.embarcacao_id IN ({$in})
               AND ap.status IN ('EM_ANALISE', 'AGUARDANDO_DOCUMENTOS')
          ORDER BY ap.atualizado_em DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $analises = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$statusLabels = [
    'EM_ANALISE' => 'Em análise',
    'AGUARDANDO_DOCUMENTOS' => 'Aguardando documentos',
];

$titulo_page = 'Análise de Planos - Portal do Cliente';
require_once __DIR__ . '/../../includes/portal_header.php';
?>
<section class="portal-page-header">
    <div>
        <h1>Análise de Planos</h1>
        <p>Envie uma nova revisão sem substituir os arquivos já analisados.</p>
    </div>
    <a class="btn btn-secondary" href="<?php echo APP_URL; ?>portal/documentos">
        <i class="fas fa-file-lines"></i> Meus documentos
    </a>
</section>

<?php if (!$analises): ?>
    <section class="portal-panel">
        <div class="portal-empty">
            <i class="fas fa-folder-open"></i>
            <h2>Nenhuma análise recebendo documentos</h2>
            <p>Quando o analista solicitar uma revisão, ela aparecerá aqui.</p>
        </div>
    </section>
<?php else: ?>
    <div class="portal-preserve">
        <i class="fas fa-shield-check"></i>
        <div>
            <strong>Seus arquivos anteriores serão preservados.</strong>
            <span>Ao enviar uma nova revisão, os arquivos já analisados não serão substituídos.</span>
        </div>
    </div>

    <?php foreach ($analises as $a): ?>
        <?php
        $statusLabel = $statusLabels[$a['status']] ?? ucfirst(strtolower(str_replace('_', ' ', $a['status'])));
        $statusClass = $a['status'] === 'AGUARDANDO_DOCUMENTOS' ? 'is-warning' : 'is-analysis';
        ?>
        <section class="portal-analysis-layout">
            <div class="portal-analysis-main">
                <form class="portal-analysis-form" data-portal-upload method="post" enctype="multipart/form-data" action="<?php echo APP_URL; ?>portal/analises-planos/actions">
                    <input type="hidden" name="csrf_token" value="<?php echo h(gerarCSRF()); ?>">
                    <input type="hidden" name="analise_id" value="<?php echo h($a['id']); ?>">

                    <div class="portal-analysis-identify">
                        <div class="portal-analysis-summary">
                            <strong><?php echo h($a['numero']); ?> · <?php echo h($a['embarcacao_nome']); ?></strong>
                            <span>
                                <?php echo h($a['tipo_processo'] ?: 'Processo em enquadramento'); ?>
                                · <span class="portal-status <?php echo $statusClass; ?>"><?php echo h($statusLabel); ?></span>
                                · Analista: <?php echo h($a['analista_nome'] ?: 'A definir'); ?>
                            </span>
                        </div>
                        <div class="portal-analysis-fields">
                            <div>
                                <label for="descricao-<?php echo h($a['id']); ?>">Descrição da revisão</label>
                                <input id="descricao-<?php echo h($a['id']); ?>" name="descricao" maxlength="500" required placeholder="Ex.: correção solicitada no parecer">
                            </div>
                            <div>
                                <label for="categoria-<?php echo h($a['id']); ?>">Categoria</label>
                                <select id="categoria-<?php echo h($a['id']); ?>" name="categoria">
                                    <?php foreach (analisePlanosCategoriasPadrao() as $cat): ?>
                                        <option><?php echo h($cat); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="portal-upload-section">
                        <h3>Anexe os arquivos</h3>
                        <p>Selecione ou arraste todos os arquivos que fazem parte desta revisão.</p>
                        <div class="portal-upload-zone" role="button" tabindex="0" aria-label="Selecionar arquivos para a revisão">
                            <input type="file" name="arquivos[]" multiple required accept=".pdf,.jpg,.jpeg,.png,.dwg,.dxf,.doc,.docx,.xls,.xlsx">
                            <div>
                                <i class="fas fa-cloud-arrow-up"></i>
                                <strong>Arraste e solte os arquivos aqui</strong>
                                <span>ou</span><br>
                                <span class="portal-upload-button">Escolher arquivos</span>
                            </div>
                        </div>
                        <p class="portal-upload-help">
                            Formatos aceitos: PDF, DWG, DXF, DOC, DOCX, XLS, XLSX, JPG e PNG.<br>
                            Tamanho máximo por arquivo: 50 MB.
                        </p>
                    </div>

                    <div class="portal-selected-files">
                        <div class="portal-selected-files-header">
                            <span>Arquivos selecionados</span>
                            <span><span data-file-count>0</span> arquivo(s)</span>
                        </div>
                        <div class="portal-file-empty">Nenhum arquivo selecionado.</div>
                        <ul class="portal-file-list" aria-live="polite"></ul>
                    </div>

                    <button class="btn btn-primary portal-analysis-submit" type="submit">
                        <i class="fas fa-upload"></i> Enviar nova revisão
                    </button>
                </form>
            </div>

            <aside class="portal-analysis-side">
                <section class="portal-side-card">
                    <h2>Como funciona</h2>
                    <ol class="portal-steps">
                        <li class="portal-step"><span class="portal-step-number">1</span><div><strong>Identifique a revisão</strong><p>Descreva a correção e selecione a categoria.</p></div></li>
                        <li class="portal-step"><span class="portal-step-number">2</span><div><strong>Anexe os arquivos</strong><p>Adicione os novos documentos. Os anteriores serão preservados.</p></div></li>
                        <li class="portal-step"><span class="portal-step-number">3</span><div><strong>Envie para análise</strong><p>Nossa equipe será notificada para continuar o processo.</p></div></li>
                    </ol>
                </section>
                <section class="portal-side-card">
                    <h2>Resumo da análise atual</h2>
                    <div class="portal-current-summary">
                        <div><span>Análise</span><strong><?php echo h($a['numero']); ?></strong></div>
                        <div><span>Embarcação</span><strong><?php echo h($a['embarcacao_nome']); ?></strong></div>
                        <div><span>Processo</span><strong><?php echo h($a['tipo_processo'] ?: 'Em enquadramento'); ?></strong></div>
                        <div><span>Status</span><strong><span class="portal-status <?php echo $statusClass; ?>"><?php echo h($statusLabel); ?></span></strong></div>
                        <div><span>Analista</span><strong><?php echo h($a['analista_nome'] ?: 'A definir'); ?></strong></div>
                        <div><span>Última revisão</span><strong><?php echo (int) $a['ultima_revisao'] > 0 ? 'Revisão ' . (int) $a['ultima_revisao'] : 'Nenhuma enviada'; ?></strong></div>
                    </div>
                </section>
            </aside>
        </section>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/portal_footer.php'; ?>
