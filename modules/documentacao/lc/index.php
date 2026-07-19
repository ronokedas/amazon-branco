<?php
/**
 * MÓDULO: Documentação > LC (Licença de Construção / LCEC)
 * Listagem de Licenças de Construção
 * 
 * Numeração: AM-LC:{n}/{ano} ou AM-EC:{n}/{ano}
 */

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/aprovacao_ui.php';

verificar_sessao();
if (!podeAcessar('documentacao')) {
    header('Location: ' . APP_URL . 'dashboard?erro=sem_permissao');
    exit;
}

$busca = $_GET['busca'] ?? '';
$filtro_status = $_GET['status'] ?? '';
$filtro_tipo = $_GET['tipo_licenca'] ?? '';

$sql = "SELECT c.id, c.numero_lc, c.nome_embarcacao, c.tipo_licenca, 
               c.data_emissao, c.data_validade, c.status, c.assinado, c.criado_em
        FROM certificados_lc c
        WHERE c.ativo = 1";

$params = [];

if (!empty($busca)) {
    $sql .= " AND (c.numero_lc LIKE :busca OR c.nome_embarcacao LIKE :busca2)";
    $params[':busca'] = "%{$busca}%";
    $params[':busca2'] = "%{$busca}%";
}

if (!empty($filtro_status) && in_array($filtro_status, ['rascunho', 'emitido', 'assinado', 'cancelado'])) {
    $sql .= " AND c.status = :status";
    $params[':status'] = $filtro_status;
}

if (!empty($filtro_tipo) && in_array($filtro_tipo, ['LC','LA','LR','LCEC'])) {
    $sql .= " AND c.tipo_licenca = :tipo";
    $params[':tipo'] = $filtro_tipo;
}

$sql .= " ORDER BY c.criado_em DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$licencas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$titulo_page = 'Licenças de Construção/LCEC - ' . APP_NAME;
require_once __DIR__ . '/../../../includes/header.php';
require_once __DIR__ . '/../../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div class="tabela-header">
        <h2><i class="fas fa-file-certificate"></i> Licenças de Construção / LCEC</h2>
        <div class="d-flex gap-2">
            <a href="<?php echo APP_URL; ?>certificados/wizard?modelo=LC" class="btn btn-success">
                <i class="fas fa-plus"></i> Nova Licença
            </a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="<?php echo APP_URL; ?>documentacao/lc" class="d-flex gap-2" style="flex-wrap: wrap; align-items: flex-end;">
                <div class="form-group" style="flex: 1; min-width: 250px;">
                    <label for="busca"><i class="fas fa-search"></i> Buscar</label>
                    <input type="text" id="busca" name="busca" class="form-control" 
                           placeholder="Número ou nome da embarcação..." 
                           value="<?php echo h($busca); ?>">
                </div>
                <div class="form-group" style="min-width: 180px;">
                    <label for="tipo_licenca"><i class="fas fa-tag"></i> Tipo</label>
                    <select id="tipo_licenca" name="tipo_licenca" class="form-control">
                        <option value="">Todos</option>
                        <option value="LC" <?php echo $filtro_tipo === 'LC' ? 'selected' : ''; ?>>LC - Construção</option>
                        <option value="LA" <?php echo $filtro_tipo === 'LA' ? 'selected' : ''; ?>>LA - Alteração</option>
                        <option value="LR" <?php echo $filtro_tipo === 'LR' ? 'selected' : ''; ?>>LR - Reclassificação</option>
                        <option value="LCEC" <?php echo $filtro_tipo === 'LCEC' ? 'selected' : ''; ?>>LCEC - Exploração Comercial</option>
                    </select>
                </div>
                <div class="form-group" style="min-width: 180px;">
                    <label for="status"><i class="fas fa-filter"></i> Status</label>
                    <select id="status" name="status" class="form-control">
                        <option value="">Todos</option>
                        <option value="rascunho" <?php echo $filtro_status === 'rascunho' ? 'selected' : ''; ?>>Rascunho</option>
                        <option value="emitido" <?php echo $filtro_status === 'emitido' ? 'selected' : ''; ?>>Emitido</option>
                        <option value="assinado" <?php echo $filtro_status === 'assinado' ? 'selected' : ''; ?>>Assinado</option>
                        <option value="cancelado" <?php echo $filtro_status === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrar</button>
                    <a href="<?php echo APP_URL; ?>documentacao/lc" class="btn btn-secondary"><i class="fas fa-times"></i> Limpar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="tabela-container">
        <?php if (empty($licencas)): ?>
            <div class="tabela-vazia">
                <i class="fas fa-file-certificate" style="font-size: 3rem; opacity: 0.3;"></i>
                <p>Nenhuma licença encontrada.</p>
                <a href="<?php echo APP_URL; ?>certificados/wizard?modelo=LC" class="btn btn-success btn-sm">
                    <i class="fas fa-plus"></i> Criar Primeira Licença
                </a>
            </div>
        <?php else: ?>
            <table class="tabela">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Embarcação</th>
                        <th>Tipo</th>
                        <th>Emissão</th>
                        <th>Validade</th>
                        <th>Status</th>
                        <th>Assinado</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($licencas as $c): ?>
                        <tr>
                            <td><strong><?php echo h($c['numero_lc']); ?></strong></td>
                            <td><?php echo h($c['nome_embarcacao']); ?></td>
                            <td><?php echo h($c['tipo_licenca']); ?></td>
                            <td><?php echo formatarData($c['data_emissao']); ?></td>
                            <td><?php echo formatarData($c['data_validade']); ?></td>
                            <td>
                                <?php
                                $badge_class = ['rascunho'=>'badge-secondary','emitido'=>'badge-warning','assinado'=>'badge-success','cancelado'=>'badge-danger'];
                                $bc = $badge_class[$c['status']] ?? 'badge-secondary';
                                ?>
                                <span class="badge <?php echo $bc; ?>"><?php echo h(ucfirst($c['status'])); ?></span>
                            </td>
                            <td>
                                <?php if ($c['assinado']): ?>
                                    <span class="badge badge-success"><i class="fas fa-check"></i> Sim</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Não</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex gap-1" style="flex-wrap: nowrap;">
                                    <a href="<?php echo APP_URL; ?>documentacao/lc/form?id=<?php echo h($c['id']); ?>" class="btn btn-sm btn-primary" title="Editar"><i class="fas fa-edit"></i></a>
                                    <a href="<?php echo APP_URL; ?>documentacao/lc/pdf?id=<?php echo h($c['id']); ?>" class="btn btn-sm btn-secondary" title="Gerar PDF" target="_blank"><i class="fas fa-file-pdf"></i></a>
                                    <?php renderBotaoAprovacaoDocumento($pdo,'LC',$c['id'],$c['status'],(bool)$c['assinado']); ?>
                                    <form method="POST" action="<?php echo APP_URL; ?>documentacao/lc/actions" style="display:inline;" onsubmit="return confirm('Enviar licença <?php echo h(addslashes($c['numero_lc'])); ?> por e-mail?')">
                                        <input type="hidden" name="action" value="enviar_certificado">
                                        <input type="hidden" name="id" value="<?php echo h($c['id']); ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>">
                                        <button type="submit" class="btn btn-sm btn-success" title="Enviar por E-mail"><i class="fas fa-envelope"></i></button>
                                    </form>
                                    <?php
                                    $token_stmt = $pdo->prepare("SELECT token_assinatura FROM certificados_lc WHERE id = :id");
                                    $token_stmt->execute([':id' => $c['id']]);
                                    $token_row = $token_stmt->fetch();
                                    $link_assinatura = APP_URL . 'assinar/' . $token_row['token_assinatura'];
                                    ?>
                                    <button type="button" class="btn btn-sm btn-info" title="Copiar Link de Assinatura" onclick="copiarLink('<?php echo h($link_assinatura); ?>', this)"><i class="fas fa-link"></i></button>
                                    <form method="POST" action="<?php echo APP_URL; ?>documentacao/lc/actions" style="display:inline;" onsubmit="return confirm('Enviar link de assinatura da licença <?php echo h(addslashes($c['numero_lc'])); ?> por e-mail?')">
                                        <input type="hidden" name="action" value="enviar_assinatura">
                                        <input type="hidden" name="id" value="<?php echo h($c['id']); ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>">
                                        <button type="submit" class="btn btn-sm btn-warning" title="Enviar Link de Assinatura"><i class="fas fa-file-signature"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
function copiarLink(url, btn) {
    navigator.clipboard.writeText(url).then(function() {
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i>';
        btn.classList.remove('btn-info');
        btn.classList.add('btn-success');
        setTimeout(function() {
            btn.innerHTML = originalHtml;
            btn.classList.remove('btn-success');
            btn.classList.add('btn-info');
        }, 2000);
    }).catch(function() {
        const input = document.createElement('input');
        input.value = url;
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        document.body.removeChild(input);
        alert('Link copiado!');
    });
}
</script>

<?php renderAprovacaoUi($pdo); require_once __DIR__ . '/../../../includes/footer.php'; ?>
