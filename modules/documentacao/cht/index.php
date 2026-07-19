<?php
/**
 * MÓDULO: Documentação > CHT (Certificado de Homologação Técnica)
 * Listagem de Certificados de Homologação Técnica
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

$sql = "SELECT c.id, c.numero_certificado, c.numero_relatorio_ht, c.relatorio_homologacao_numero,
               c.profissional_empresa, c.cpf_cnpj, c.atividade_homologada,
               c.data_emissao, c.data_validade, c.status, c.assinado, c.criado_em
        FROM certificados_cht c WHERE c.ativo = 1";

$params = [];
if (!empty($busca)) {
    $sql .= " AND (c.numero_certificado LIKE :busca OR c.numero_relatorio_ht LIKE :busca2 OR c.profissional_empresa LIKE :busca3 OR c.cpf_cnpj LIKE :busca4)";
    $params[':busca'] = "%{$busca}%";
    $params[':busca2'] = "%{$busca}%";
    $params[':busca3'] = "%{$busca}%";
    $params[':busca4'] = "%{$busca}%";
}
if (!empty($filtro_status) && in_array($filtro_status, ['rascunho','emitido','assinado','cancelado'])) {
    $sql .= " AND c.status = :status";
    $params[':status'] = $filtro_status;
}
$sql .= " ORDER BY c.criado_em DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$certificados = $stmt->fetchAll(PDO::FETCH_ASSOC);

$titulo_page = 'Certificados de Homologação Técnica - ' . APP_NAME;
require_once __DIR__ . '/../../../includes/header.php';
require_once __DIR__ . '/../../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div class="tabela-header">
        <h2><i class="fas fa-file-certificate"></i> Certificados de Homologação Técnica (CHT)</h2>
        <div class="d-flex gap-2">
            <a href="<?php echo APP_URL; ?>certificados/wizard?modelo=CHT" class="btn btn-success"><i class="fas fa-plus"></i> Novo Certificado</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="<?php echo APP_URL; ?>documentacao/cht" class="d-flex gap-2" style="flex-wrap: wrap; align-items: flex-end;">
                <div class="form-group" style="flex:1;min-width:250px;">
                    <label for="busca"><i class="fas fa-search"></i> Buscar</label>
                    <input type="text" id="busca" name="busca" class="form-control" placeholder="Nº relatório, profissional ou CPF/CNPJ..." value="<?php echo h($busca); ?>">
                </div>
                <div class="form-group" style="min-width:180px;">
                    <label for="status"><i class="fas fa-filter"></i> Status</label>
                    <select id="status" name="status" class="form-control">
                        <option value="">Todos</option>
                        <option value="rascunho" <?php echo $filtro_status==='rascunho'?'selected':'';?>>Rascunho</option>
                        <option value="emitido" <?php echo $filtro_status==='emitido'?'selected':'';?>>Emitido</option>
                        <option value="assinado" <?php echo $filtro_status==='assinado'?'selected':'';?>>Assinado</option>
                        <option value="cancelado" <?php echo $filtro_status==='cancelado'?'selected':'';?>>Cancelado</option>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrar</button>
                    <a href="<?php echo APP_URL; ?>documentacao/cht" class="btn btn-secondary"><i class="fas fa-times"></i> Limpar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="tabela-container">
        <?php if (empty($certificados)): ?>
            <div class="tabela-vazia">
                <i class="fas fa-file-certificate" style="font-size:3rem;opacity:0.3;"></i>
                <p>Nenhum certificado CHT encontrado.</p>
                <a href="<?php echo APP_URL; ?>certificados/wizard?modelo=CHT" class="btn btn-success btn-sm"><i class="fas fa-plus"></i> Criar Primeiro</a>
            </div>
        <?php else: ?>
            <table class="tabela">
                <thead>
                    <tr><th>Nº Certificado</th><th>Profissional/Empresa</th><th>Relatório HT</th><th>Validade</th><th>Status</th><th>Assinado</th><th>Ações</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($certificados as $c): ?>
                    <tr>
                        <td><strong><?php echo h($c['numero_certificado'] ?: $c['numero_relatorio_ht']); ?></strong></td>
                        <td><?php echo h($c['profissional_empresa']); ?></td>
                        <td><?php echo h($c['relatorio_homologacao_numero'] ?: $c['numero_relatorio_ht']); ?></td>
                        <td><?php echo formatarData($c['data_validade']); ?></td>
                        <td><?php $bc=['rascunho'=>'badge-secondary','emitido'=>'badge-warning','assinado'=>'badge-success','cancelado'=>'badge-danger']; echo '<span class="badge '.($bc[$c['status']]??'badge-secondary').'">'.h(ucfirst($c['status'])).'</span>'; ?></td>
                        <td><?php echo $c['assinado']?'<span class="badge badge-success"><i class="fas fa-check"></i> Sim</span>':'<span class="badge badge-secondary">Não</span>'; ?></td>
                        <td>
                            <div class="d-flex gap-1" style="flex-wrap:nowrap;">
                                <a href="<?php echo APP_URL; ?>documentacao/cht/form?id=<?php echo h($c['id']); ?>" class="btn btn-sm btn-primary" title="Editar"><i class="fas fa-edit"></i></a>
                                <a href="<?php echo APP_URL; ?>documentacao/cht/pdf?id=<?php echo h($c['id']); ?>" class="btn btn-sm btn-secondary" title="PDF" target="_blank"><i class="fas fa-file-pdf"></i></a>
                                <?php renderBotaoAprovacaoDocumento($pdo,'CHT',$c['id'],$c['status'],(bool)$c['assinado']); ?>
                                <form method="POST" action="<?php echo APP_URL; ?>documentacao/cht/actions" style="display:inline;" onsubmit="return confirm('Enviar CHT <?php echo h(addslashes($c['numero_relatorio_ht'])); ?> por e-mail?')"><input type="hidden" name="action" value="enviar_certificado"><input type="hidden" name="id" value="<?php echo h($c['id']); ?>"><input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>"><button type="submit" class="btn btn-sm btn-success" title="E-mail"><i class="fas fa-envelope"></i></button></form>
                                <?php $t=$pdo->prepare("SELECT token_assinatura FROM certificados_cht WHERE id=:id");$t->execute([':id'=>$c['id']]);$tr=$t->fetch();$la=APP_URL.'assinar/'.$tr['token_assinatura']; ?>
                                <button type="button" class="btn btn-sm btn-info" title="Copiar Link" onclick="copiarLink('<?php echo h($la); ?>',this)"><i class="fas fa-link"></i></button>
                                <form method="POST" action="<?php echo APP_URL; ?>documentacao/cht/actions" style="display:inline;" onsubmit="return confirm('Enviar link de assinatura por e-mail?')"><input type="hidden" name="action" value="enviar_assinatura"><input type="hidden" name="id" value="<?php echo h($c['id']); ?>"><input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>"><button type="submit" class="btn btn-sm btn-warning" title="Link Assinatura"><i class="fas fa-file-signature"></i></button></form>
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
function copiarLink(url,btn){navigator.clipboard.writeText(url).then(function(){const h=btn.innerHTML;btn.innerHTML='<i class="fas fa-check"></i>';btn.classList.remove('btn-info');btn.classList.add('btn-success');setTimeout(function(){btn.innerHTML=h;btn.classList.remove('btn-success');btn.classList.add('btn-info')},2000)}).catch(function(){const i=document.createElement('input');i.value=url;document.body.appendChild(i);i.select();document.execCommand('copy');document.body.removeChild(i);alert('Link copiado!')})}
</script>
<?php renderAprovacaoUi($pdo); require_once __DIR__ . '/../../../includes/footer.php'; ?>
