<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/financeiro_escritorios.php';

verificar_sessao();
verificar_cargo('ADMIN');

$competencia = financeiroCompetencia($_GET['competencia'] ?? $_POST['competencia'] ?? date('Y-m'));

function salvarMetaEscritorio(PDO $pdo, string $escritorioId, string $competencia, float $valor): void {
    $stmt = $pdo->prepare('SELECT id FROM financeiro_metas_mensais WHERE escritorio_id=:escritorio AND usuario_id IS NULL AND competencia=:competencia LIMIT 1 FOR UPDATE');
    $stmt->execute([':escritorio'=>$escritorioId, ':competencia'=>$competencia]);
    $id = $stmt->fetchColumn();
    if ($id) {
        $pdo->prepare('UPDATE financeiro_metas_mensais SET valor=:valor WHERE id=:id')->execute([':valor'=>$valor, ':id'=>$id]);
    } else {
        $pdo->prepare('INSERT INTO financeiro_metas_mensais (id,competencia,escritorio_id,usuario_id,valor) VALUES (:id,:competencia,:escritorio,NULL,:valor)')
            ->execute([':id'=>gerarUUID(), ':competencia'=>$competencia, ':escritorio'=>$escritorioId, ':valor'=>$valor]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificarCSRF($_POST['csrf_token'] ?? '')) {
        setMensagem('error', 'Token de segurança inválido.');
        redirecionar(APP_URL . 'configuracoes/financeiro');
    }
    $acao = $_POST['action'] ?? '';
    try {
        if ($acao === 'salvar_escritorio') {
            $id = trim($_POST['id'] ?? '');
            $nome = trim($_POST['nome'] ?? '');
            $cidade = trim($_POST['cidade'] ?? '');
            $uf = strtoupper(trim($_POST['uf'] ?? ''));
            if (mb_strlen($nome) < 2 || mb_strlen($cidade) < 2 || !preg_match('/^[A-Z]{2}$/', $uf)) throw new RuntimeException('Informe nome, cidade e UF válidos.');
            if ($id) {
                $pdo->prepare('UPDATE escritorios SET nome=:nome,cidade=:cidade,uf=:uf WHERE id=:id')->execute([':nome'=>$nome,':cidade'=>$cidade,':uf'=>$uf,':id'=>$id]);
            } else {
                $pdo->prepare('INSERT INTO escritorios (id,nome,cidade,uf) VALUES (:id,:nome,:cidade,:uf)')->execute([':id'=>gerarUUID(),':nome'=>$nome,':cidade'=>$cidade,':uf'=>$uf]);
            }
            setMensagem('success', 'Escritório salvo com sucesso.');
        } elseif ($acao === 'alternar_escritorio') {
            $id = trim($_POST['id'] ?? '');
            $stmt = $pdo->prepare('SELECT ativo FROM escritorios WHERE id=:id');
            $stmt->execute([':id'=>$id]);
            $ativo = $stmt->fetchColumn();
            if ($ativo === false) throw new RuntimeException('Escritório não encontrado.');
            if ((int)$ativo === 1 && (int)$pdo->query('SELECT COUNT(*) FROM escritorios WHERE ativo=1')->fetchColumn() <= 1) throw new RuntimeException('Não é possível desativar o único escritório ativo.');
            if ((int)$ativo === 1) {
                $stmt=$pdo->prepare("SELECT COUNT(*) FROM usuarios u WHERE u.ativo=1 AND u.excluido_em IS NULL AND EXISTS(SELECT 1 FROM usuario_escritorios ue WHERE ue.usuario_id=u.id AND ue.escritorio_id=:e) AND NOT EXISTS(SELECT 1 FROM usuario_escritorios ue2 JOIN escritorios e2 ON e2.id=ue2.escritorio_id AND e2.ativo=1 WHERE ue2.usuario_id=u.id AND ue2.escritorio_id<>:e2)");
                $stmt->execute([':e'=>$id,':e2'=>$id]);
                if((int)$stmt->fetchColumn()>0) throw new RuntimeException('Realoque os funcionários que possuem somente este escritório antes de desativá-lo.');
            }
            $pdo->prepare('UPDATE escritorios SET ativo=:ativo WHERE id=:id')->execute([':ativo'=>(int)$ativo === 1 ? 0 : 1, ':id'=>$id]);
            setMensagem('success', 'Status do escritório atualizado.');
        } elseif ($acao === 'salvar_metas_escritorios') {
            $pdo->beginTransaction();
            $validos = array_flip(array_column(financeiroEscritorios($pdo), 'id'));
            foreach ($_POST['meta_escritorio'] ?? [] as $escritorioId=>$valor) {
                if (!isset($validos[$escritorioId]) || !is_numeric($valor) || (float)$valor < 0) throw new RuntimeException('Meta de escritório inválida.');
                salvarMetaEscritorio($pdo, $escritorioId, $competencia, (float)$valor);
            }
            $pdo->commit();
            setMensagem('success', 'Metas dos escritórios de ' . date('m/Y', strtotime($competencia)) . ' atualizadas.');
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Configuração financeira: '.$e->getMessage());
        setMensagem('error', $e instanceof RuntimeException ? $e->getMessage() : 'Não foi possível salvar a configuração.');
    }
    redirecionar(APP_URL . 'configuracoes/financeiro?competencia=' . substr($competencia, 0, 7));
}

$escritorios = financeiroEscritorios($pdo, false);
$stmt=$pdo->prepare('SELECT escritorio_id,valor FROM financeiro_metas_mensais WHERE usuario_id IS NULL AND competencia=:competencia');
$stmt->execute([':competencia'=>$competencia]);
$metasEscritorio=[];
foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $m) $metasEscritorio[$m['escritorio_id']]=$m['valor'];
$editId=trim($_GET['editar']??'');
$edit=null;
foreach($escritorios as $e) if($e['id']===$editId) $edit=$e;
$titulo_page='Configuração Financeira';
require_once __DIR__.'/../../includes/header.php';
require_once __DIR__.'/../../includes/sidebar.php';
?>
<div class="conteudo-principal">
<div class="welcome-section" style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap"><div><h1><i class="fas fa-building-columns"></i> Configuração Financeira</h1><p>Cadastro de escritórios e metas mensais por unidade.</p></div><a class="btn btn-secondary" href="<?= APP_URL ?>configuracoes"><i class="fas fa-arrow-left"></i> Voltar</a></div>
<div class="grid-2" style="align-items:start">
<section class="card"><div class="card-header"><h3><?= $edit?'Editar':'Novo' ?> escritório</h3></div><div class="card-body"><form method="post"><input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>"><input type="hidden" name="action" value="salvar_escritorio"><input type="hidden" name="id" value="<?= h($edit['id']??'') ?>"><div class="form-group"><label>Nome *</label><input name="nome" maxlength="150" required value="<?= h($edit['nome']??'') ?>"></div><div class="grid-2"><div class="form-group"><label>Cidade *</label><input name="cidade" maxlength="150" required value="<?= h($edit['cidade']??'') ?>"></div><div class="form-group"><label>UF *</label><input name="uf" maxlength="2" pattern="[A-Za-z]{2}" required value="<?= h($edit['uf']??'AM') ?>"></div></div><button class="btn btn-primary"><i class="fas fa-save"></i> Salvar escritório</button></form></div></section>
<section class="card"><div class="card-header"><h3>Escritórios cadastrados</h3></div><div class="card-body" style="overflow:auto"><table><thead><tr><th>Escritório</th><th>Local</th><th>Status</th><th>Ações</th></tr></thead><tbody><?php foreach($escritorios as $e): ?><tr><td><strong><?= h($e['nome']) ?></strong></td><td><?= h($e['cidade'].'/'.$e['uf']) ?></td><td><span class="badge <?= $e['ativo']?'badge-success':'badge-secondary' ?>"><?= $e['ativo']?'Ativo':'Inativo' ?></span></td><td style="display:flex;gap:6px"><a class="btn btn-secondary btn-sm" href="?editar=<?= urlencode($e['id']) ?>&competencia=<?= h(substr($competencia,0,7)) ?>"><i class="fas fa-edit"></i></a><form method="post"><input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>"><input type="hidden" name="action" value="alternar_escritorio"><input type="hidden" name="id" value="<?= h($e['id']) ?>"><button class="btn btn-sm <?= $e['ativo']?'btn-danger':'btn-success' ?>" title="<?= $e['ativo']?'Desativar':'Ativar' ?>"><i class="fas fa-power-off"></i></button></form></td></tr><?php endforeach ?></tbody></table></div></section>
</div>
<section class="card" style="margin-top:20px"><div class="card-header" style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap"><h3><i class="fas fa-bullseye"></i> Metas dos escritórios</h3><form method="get"><label>Competência <input type="month" name="competencia" value="<?= h(substr($competencia,0,7)) ?>" onchange="this.form.submit()"></label></form></div><div class="card-body"><form method="post"><input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>"><input type="hidden" name="action" value="salvar_metas_escritorios"><input type="hidden" name="competencia" value="<?= h(substr($competencia,0,7)) ?>"><div class="grid-2"><?php foreach($escritorios as $e)if($e['ativo']): ?><div class="form-group"><label><?= h($e['nome'].' · '.$e['cidade'].'/'.$e['uf']) ?></label><input type="number" min="0" step="0.01" name="meta_escritorio[<?= h($e['id']) ?>]" value="<?= h($metasEscritorio[$e['id']]??'0.00') ?>"></div><?php endif ?></div><button class="btn btn-primary btn-lg" style="margin-top:18px"><i class="fas fa-save"></i> Salvar metas dos escritórios</button></form></div></section>
</div>
<?php require_once __DIR__.'/../../includes/footer.php'; ?>
