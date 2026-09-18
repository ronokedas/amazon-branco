<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/cliente_portal.php';

requireClienteSenhaDefinitiva();

$clienteId = clientePortalId();
$embarcacoes = clientePortalEmbarcacoes($pdo, $clienteId);
$tiposDocumentos = clientePortalTiposDocumentos();

$filtros = [
    'busca' => trim($_GET['busca'] ?? ''),
    'tipo' => trim($_GET['tipo'] ?? ''),
    'status' => trim($_GET['status'] ?? ''),
    'embarcacao_id' => trim($_GET['embarcacao_id'] ?? ''),
];
if (!empty($_GET['vencendo'])) {
    $filtros['vencendo_dias'] = 90;
}

$documentos = clientePortalSelectDocumentos($pdo, $clienteId, $filtros);

// SGQ ISO 9.1.2: Identificar documentos já avaliados pelo cliente
$avaliacoesDocs = [];
try {
    $stmtAv = $pdo->prepare("SELECT documento_id FROM sgq_satisfacao_clientes WHERE cliente_id = :cli_id AND documento_id IS NOT NULL");
    $stmtAv->execute([':cli_id' => $clienteId]);
    $avaliacoesDocs = $stmtAv->fetchAll(PDO::FETCH_COLUMN) ?: [];
} catch (Throwable $e) {}

$titulo_page = 'Meus documentos - Portal do Cliente';
require_once __DIR__ . '/../../includes/portal_header.php';
?>
<section class="portal-page-header">
    <div>
        <h1>Meus documentos</h1>
        <p>Filtre, localize e visualize os PDFs emitidos para suas embarcações.</p>
    </div>
    <div class="portal-page-header-mark">
        <i class="fas fa-file-shield"></i>
    </div>
</section>

<form method="GET" class="portal-filters">
    <div class="form-group">
        <label for="busca">Buscar</label>
        <input type="text" id="busca" name="busca" value="<?php echo h($filtros['busca']); ?>" placeholder="Número ou embarcação">
    </div>
    <div class="form-group">
        <label for="embarcacao_id">Embarcação</label>
        <select id="embarcacao_id" name="embarcacao_id">
            <option value="">Todas</option>
            <?php foreach ($embarcacoes as $emb): ?>
                <option value="<?php echo h($emb['id']); ?>" <?php echo $filtros['embarcacao_id'] === $emb['id'] ? 'selected' : ''; ?>>
                    <?php echo h($emb['nome']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="tipo">Tipo</label>
        <select id="tipo" name="tipo">
            <option value="">Todos</option>
            <?php foreach ($tiposDocumentos as $tipo => $label): ?>
                <option value="<?php echo h($tipo); ?>" <?php echo $filtros['tipo'] === $tipo ? 'selected' : ''; ?>>
                    <?php echo h($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="status">Status</label>
        <select id="status" name="status">
            <option value="">Todos</option>
            <option value="emitido" <?php echo $filtros['status'] === 'emitido' ? 'selected' : ''; ?>>Emitido</option>
            <option value="assinado" <?php echo $filtros['status'] === 'assinado' ? 'selected' : ''; ?>>Assinado</option>
        </select>
    </div>
    <label class="portal-check">
        <input type="checkbox" name="vencendo" value="1" <?php echo !empty($_GET['vencendo']) ? 'checked' : ''; ?>>
        <span>Vencendo em 90 dias</span>
    </label>
    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrar</button>
    <a href="<?php echo APP_URL; ?>portal/documentos" class="btn btn-secondary"><i class="fas fa-xmark"></i> Limpar</a>
</form>

<section class="portal-panel">
    <?php if (empty($documentos)): ?>
        <div class="portal-empty">Nenhum documento encontrado para os filtros selecionados.</div>
    <?php else: ?>
        <div class="portal-table-wrap">
            <table class="portal-table">
                <thead>
                    <tr>
                        <th>Documento</th>
                        <th>Embarcação</th>
                        <th>Emissão</th>
                        <th>Validade</th>
                        <th>Status</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($documentos as $doc): ?>
                        <tr>
                            <td data-label="Documento">
                                <strong><?php echo h($doc['tipo_label']); ?></strong>
                                <small><?php echo h($doc['numero']); ?></small>
                            </td>
                            <td data-label="Embarcação"><?php echo h($doc['embarcacao_nome']); ?></td>
                            <td data-label="Emissão"><?php echo formatarData($doc['data_emissao']); ?></td>
                            <td data-label="Validade"><?php echo formatarData($doc['data_validade']); ?></td>
                            <td data-label="Status"><span class="portal-status <?php echo strtolower((string)$doc['status']) === 'assinado' ? 'is-valid' : 'is-issued'; ?>"><?php echo h(ucfirst(strtolower(str_replace('_', ' ', $doc['status'])))); ?></span></td>
                            <td data-label="Ação">
                                <?php $jaAvaliado = in_array($doc['id'], $avaliacoesDocs, true); ?>
                                <div class="portal-table-actions">
                                    <?php if ($jaAvaliado): ?>
                                        <a class="btn btn-primary btn-sm btn-has-text" target="_blank" href="<?php echo APP_URL; ?>portal/documentos/pdf?acao=visualizar&tipo=<?php echo h($doc['tipo']); ?>&id=<?php echo h($doc['id']); ?>">
                                            <i class="fas fa-eye"></i> Visualizar
                                        </a>
                                        <a class="btn btn-secondary btn-sm btn-has-text" href="<?php echo APP_URL; ?>portal/documentos/pdf?acao=download&tipo=<?php echo h($doc['tipo']); ?>&id=<?php echo h($doc['id']); ?>">
                                            <i class="fas fa-download"></i> PDF
                                        </a>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-primary btn-sm btn-has-text" onclick="solicitarAvaliacaoDownload('<?php echo h($doc['id']); ?>', '<?php echo h($doc['tipo']); ?>', '<?php echo h($doc['embarcacao_id'] ?? ''); ?>', 'visualizar')">
                                            <i class="fas fa-eye"></i> Visualizar
                                        </button>
                                        <button type="button" class="btn btn-secondary btn-sm btn-has-text" onclick="solicitarAvaliacaoDownload('<?php echo h($doc['id']); ?>', '<?php echo h($doc['tipo']); ?>', '<?php echo h($doc['embarcacao_id'] ?? ''); ?>', 'download')">
                                            <i class="fas fa-download"></i> PDF
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<!-- Modal Amigável de Pesquisa de Satisfação (ISO 9.1.2) -->
<div id="modalSgqSatisfacao" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:9999;align-items:center;justify-content:center;padding:15px;box-sizing:border-box;">
    <div style="background:#fff;border-radius:12px;max-width:560px;width:100%;padding:24px;box-shadow:0 15px 35px rgba(0,0,0,0.3);position:relative;max-height:90vh;overflow-y:auto;">
        <div style="text-align:center;margin-bottom:18px;">
            <div style="width:48px;height:48px;border-radius:50%;background:#e6f4ea;color:#137333;display:inline-flex;align-items:center;justify-content:center;font-size:22px;margin-bottom:8px;">
                <i class="fas fa-award"></i>
            </div>
            <h3 style="margin:0;font-size:1.25rem;color:#1a1a1a;">Sua opinião garante nossa qualidade</h3>
            <p style="margin:6px 0 0;font-size:0.88rem;color:#5f6368;">Avalie nosso serviço em menos de 1 minuto para prosseguir com o download do documento oficial autenticado.</p>
        </div>

        <form id="formSgqSatisfacao" onsubmit="enviarAvaliacaoDownload(event)">
            <input type="hidden" id="sgq_doc_id" name="documento_id" value="">
            <input type="hidden" id="sgq_doc_tipo" name="documento_tipo" value="">
            <input type="hidden" id="sgq_emb_id" name="embarcacao_id" value="">
            <input type="hidden" id="sgq_acao_tipo" name="acao_tipo" value="download">

            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:6px;color:#3c4043;">1. Atendimento Comercial e Clareza</label>
                <select name="nota_atendimento" class="form-control" style="width:100%;padding:8px 12px;border:1px solid #dadce0;border-radius:6px;" required>
                    <option value="5">⭐⭐⭐⭐⭐ 5 - Excelente</option>
                    <option value="4">⭐⭐⭐⭐ 4 - Bom</option>
                    <option value="3">⭐⭐⭐ 3 - Regular</option>
                    <option value="2">⭐⭐ 2 - Ruim</option>
                    <option value="1">⭐ 1 - Péssimo</option>
                </select>
            </div>

            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:6px;color:#3c4043;">2. Rigor e Competência Técnica na Vistoria</label>
                <select name="nota_tecnica" class="form-control" style="width:100%;padding:8px 12px;border:1px solid #dadce0;border-radius:6px;" required>
                    <option value="5">⭐⭐⭐⭐⭐ 5 - Excelente</option>
                    <option value="4">⭐⭐⭐⭐ 4 - Bom</option>
                    <option value="3">⭐⭐⭐ 3 - Regular</option>
                    <option value="2">⭐⭐ 2 - Ruim</option>
                    <option value="1">⭐ 1 - Péssimo</option>
                </select>
            </div>

            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:6px;color:#3c4043;">3. Cumprimento de Prazos e Agilidade</label>
                <select name="nota_prazo" class="form-control" style="width:100%;padding:8px 12px;border:1px solid #dadce0;border-radius:6px;" required>
                    <option value="5">⭐⭐⭐⭐⭐ 5 - Excelente</option>
                    <option value="4">⭐⭐⭐⭐ 4 - Bom</option>
                    <option value="3">⭐⭐⭐ 3 - Regular</option>
                    <option value="2">⭐⭐ 2 - Ruim</option>
                    <option value="1">⭐ 1 - Péssimo</option>
                </select>
            </div>

            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:6px;color:#3c4043;">4. O quanto você recomendaria nossos serviços? (NPS 0 a 10)</label>
                <select name="nota_nps" class="form-control" style="width:100%;padding:8px 12px;border:1px solid #dadce0;border-radius:6px;" required>
                    <option value="10">10 - Com certeza recomendaria</option>
                    <option value="9">9 - Recomendaria</option>
                    <option value="8">8 - Provavelmente</option>
                    <option value="7">7 - Talvez</option>
                    <option value="6">6 - Neutro</option>
                    <option value="5">5 ou menos - Precisa melhorar</option>
                </select>
            </div>

            <div style="margin-bottom:18px;">
                <label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:6px;color:#3c4043;">Elogios, críticas ou sugestões (Opcional)</label>
                <textarea name="comentario" rows="2" style="width:100%;padding:8px 12px;border:1px solid #dadce0;border-radius:6px;box-sizing:border-box;" placeholder="Conte-nos como foi sua experiência..."></textarea>
            </div>

            <div style="display:flex;justify-content:space-between;align-items:center;">
                <button type="button" onclick="fecharPesquisaSatisfacao()" style="background:transparent;border:none;color:#5f6368;cursor:pointer;font-weight:600;">Agora não</button>
                <button type="submit" id="btnSgqSubmit" style="background:#137333;color:#fff;border:none;border-radius:6px;padding:10px 20px;font-weight:600;cursor:pointer;">
                    <i class="fas fa-check mr-1"></i> Enviar e Liberar Documento
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let pendenciaDownloadUrl = '';

function solicitarAvaliacaoDownload(docId, docTipo, embId, acaoTipo) {
    document.getElementById('sgq_doc_id').value = docId;
    document.getElementById('sgq_doc_tipo').value = docTipo;
    document.getElementById('sgq_emb_id').value = embId;
    document.getElementById('sgq_acao_tipo').value = acaoTipo;

    pendenciaDownloadUrl = '<?php echo APP_URL; ?>portal/documentos/pdf?acao=' + encodeURIComponent(acaoTipo) + '&tipo=' + encodeURIComponent(docTipo) + '&id=' + encodeURIComponent(docId);
    
    const modal = document.getElementById('modalSgqSatisfacao');
    modal.style.display = 'flex';
}

function fecharPesquisaSatisfacao() {
    document.getElementById('modalSgqSatisfacao').style.display = 'none';
    if (pendenciaDownloadUrl) {
        if (document.getElementById('sgq_acao_tipo').value === 'visualizar') {
            window.open(pendenciaDownloadUrl, '_blank');
        } else {
            window.location.href = pendenciaDownloadUrl;
        }
    }
}

async function enviarAvaliacaoDownload(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSgqSubmit');
    btn.disabled = true;
    btn.innerText = 'Enviando avaliação...';

    const formData = new FormData(document.getElementById('formSgqSatisfacao'));

    try {
        const resp = await fetch('<?php echo APP_URL; ?>portal/satisfacao/actions', {
            method: 'POST',
            body: formData
        });
        const data = await resp.json();
    } catch (err) {
        console.error(err);
    } finally {
        document.getElementById('modalSgqSatisfacao').style.display = 'none';
        btn.disabled = false;
        btn.innerText = 'Enviar e Liberar Documento';

        if (pendenciaDownloadUrl) {
            if (document.getElementById('sgq_acao_tipo').value === 'visualizar') {
                window.open(pendenciaDownloadUrl, '_blank');
            } else {
                window.location.href = pendenciaDownloadUrl;
            }
        }
    }
}
</script>
<?php require_once __DIR__ . '/../../includes/portal_footer.php'; ?>
