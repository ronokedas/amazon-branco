<?php
/**
 * MÓDULO: Documentação > Certificados CNBL
 * Listagem de Certificados de Navegação para Embarcações de Borda Livre
 */

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/aprovacao_ui.php';

// Verificar permissão
verificar_sessao();
if (!podeAcessar('documentacao')) {
    header('Location: ' . APP_URL . 'dashboard?erro=sem_permissao');
    exit;
}

// Filtros
$busca = $_GET['busca'] ?? '';
$filtro_status = $_GET['status'] ?? '';

// Construir query
$sql = "SELECT c.id, c.numero, c.nome_embarcacao, c.data_emissao, c.data_validade, 
               c.status, c.assinado, c.local_emissao, c.criado_em
        FROM certificados_cnbl c
        WHERE c.ativo = 1";

$params = [];

if (!empty($busca)) {
    $sql .= " AND (c.numero LIKE :busca OR c.nome_embarcacao LIKE :busca2)";
    $params[':busca'] = "%{$busca}%";
    $params[':busca2'] = "%{$busca}%";
}

if (!empty($filtro_status) && in_array($filtro_status, ['rascunho', 'emitido', 'assinado', 'cancelado'])) {
    $sql .= " AND c.status = :status";
    $params[':status'] = $filtro_status;
}

$sql .= " ORDER BY c.criado_em DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$certificados = $stmt->fetchAll(PDO::FETCH_ASSOC);

$titulo_page = 'Certificados CNBL - ' . APP_NAME;
require_once __DIR__ . '/../../../includes/header.php';
require_once __DIR__ . '/../../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div class="tabela-header">
        <h2><i class="fas fa-file-certificate"></i> Certificados de Navegação para Embarcações de Borda Livre (CNBL)</h2>
        <div class="d-flex gap-2">
            <a href="<?php echo APP_URL; ?>certificados" class="btn btn-success">
                <i class="fas fa-plus"></i> Novo Certificado
            </a>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="<?php echo APP_URL; ?>documentacao/cnbl" class="d-flex gap-2" style="flex-wrap: wrap; align-items: flex-end;">
                <div class="form-group" style="flex: 1; min-width: 250px;">
                    <label for="busca"><i class="fas fa-search"></i> Buscar</label>
                    <input type="text" id="busca" name="busca" class="form-control" 
                           placeholder="Número ou nome da embarcação..." 
                           value="<?php echo h($busca); ?>">
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
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                    <a href="<?php echo APP_URL; ?>documentacao/cnbl" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Limpar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabela de Certificados -->
    <div class="tabela-container">
        <?php if (empty($certificados)): ?>
            <div class="tabela-vazia">
                <i class="fas fa-file-certificate" style="font-size: 3rem; opacity: 0.3;"></i>
                <p>Nenhum certificado CNBL encontrado.</p>
                <a href="<?php echo APP_URL; ?>certificados" class="btn btn-success btn-sm">
                    <i class="fas fa-plus"></i> Criar Primeiro Certificado
                </a>
            </div>
        <?php else: ?>
            <table class="tabela">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Embarcação</th>
                        <th>Emissão</th>
                        <th>Validade</th>
                        <th>Status</th>
                        <th>Assinado</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($certificados as $c): ?>
                        <tr>
                            <td><strong><?php echo h($c['numero']); ?></strong></td>
                            <td><?php echo h($c['nome_embarcacao']); ?></td>
                            <td><?php echo formatarData($c['data_emissao']); ?></td>
                            <td><?php echo formatarData($c['data_validade']); ?></td>
                            <td>
                                <?php
                                $badge_class = [
                                    'rascunho'  => 'badge-secondary',
                                    'emitido'   => 'badge-warning',
                                    'assinado'  => 'badge-success',
                                    'cancelado' => 'badge-danger',
                                ];
                                $bc = $badge_class[$c['status']] ?? 'badge-secondary';
                                ?>
                                <span class="badge <?php echo $bc; ?>">
                                    <?php echo h(ucfirst($c['status'])); ?>
                                </span>
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
                                    <!-- Editar -->
                                    <a href="<?php echo APP_URL; ?>documentacao/cnbl/form?id=<?php echo h($c['id']); ?>" 
                                       class="btn btn-sm btn-primary" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <!-- Gerar PDF -->
                                    <a href="<?php echo APP_URL; ?>documentacao/cnbl/pdf?id=<?php echo h($c['id']); ?>" 
                                       class="btn btn-sm btn-secondary" title="Gerar PDF" target="_blank">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>
                                    <?php renderBotaoAprovacaoDocumento($pdo,'CNBL',$c['id'],$c['status'],(bool)$c['assinado']); ?>

                                    <!-- Enviar por E-mail -->
                                    <form method="POST" action="<?php echo APP_URL; ?>documentacao/cnbl/actions" 
                                          style="display:inline;" 
                                          onsubmit="return confirm('Enviar certificado <?php echo h(addslashes($c['numero'])); ?> por e-mail para o cliente?')">
                                        <input type="hidden" name="action" value="enviar_certificado">
                                        <input type="hidden" name="id" value="<?php echo h($c['id']); ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>">
                                        <button type="submit" class="btn btn-sm btn-success" title="Enviar por E-mail">
                                            <i class="fas fa-envelope"></i>
                                        </button>
                                    </form>

                                    <!-- Enviar Link de Assinatura por E-mail -->
                                    <form method="POST" action="<?php echo APP_URL; ?>documentacao/cnbl/actions" 
                                          style="display:inline;" 
                                          onsubmit="return confirm('Enviar link de assinatura do certificado <?php echo h(addslashes($c['numero'])); ?> por e-mail?')">
                                        <input type="hidden" name="action" value="enviar_assinatura">
                                        <input type="hidden" name="id" value="<?php echo h($c['id']); ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>">
                                        <button type="submit" class="btn btn-sm btn-warning" title="Enviar Link de Assinatura por E-mail">
                                            <i class="fas fa-file-signature"></i>
                                        </button>
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
