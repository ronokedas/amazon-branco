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
                            <td data-label="Ação"><div class="portal-table-actions">
                                <a class="btn btn-primary btn-sm" target="_blank" href="<?php echo APP_URL; ?>portal/documentos/pdf?acao=visualizar&tipo=<?php echo h($doc['tipo']); ?>&id=<?php echo h($doc['id']); ?>">
                                    <i class="fas fa-eye"></i> Visualizar
                                </a>
                                <a class="btn btn-secondary btn-sm" href="<?php echo APP_URL; ?>portal/documentos/pdf?acao=download&tipo=<?php echo h($doc['tipo']); ?>&id=<?php echo h($doc['id']); ?>"><i class="fas fa-download"></i> PDF</a>
                            </div></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php require_once __DIR__ . '/../../includes/portal_footer.php'; ?>
