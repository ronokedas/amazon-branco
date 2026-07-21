<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/financeiro_escritorios.php';

verificar_sessao();
exigirAcesso('configuracoes');

$competencia = financeiroCompetencia($_GET['competencia'] ?? $_POST['competencia'] ?? date('Y-m'));

function salvarMetaEscritorio(PDO $pdo, string $escritorioId, string $competencia, float $valor, string $mensagem): void {
    $stmt = $pdo->prepare('SELECT id FROM financeiro_metas_mensais WHERE escritorio_id=:escritorio AND usuario_id IS NULL AND competencia=:competencia LIMIT 1 FOR UPDATE');
    $stmt->execute([':escritorio'=>$escritorioId, ':competencia'=>$competencia]);
    $id = $stmt->fetchColumn();
    if ($id) {
        $pdo->prepare('UPDATE financeiro_metas_mensais SET valor=:valor,mensagem=:mensagem WHERE id=:id')->execute([':valor'=>$valor, ':mensagem'=>$mensagem !== '' ? $mensagem : null, ':id'=>$id]);
    } else {
        $pdo->prepare('INSERT INTO financeiro_metas_mensais (id,competencia,escritorio_id,usuario_id,valor,mensagem) VALUES (:id,:competencia,:escritorio,NULL,:valor,:mensagem)')
            ->execute([':id'=>gerarUUID(), ':competencia'=>$competencia, ':escritorio'=>$escritorioId, ':valor'=>$valor, ':mensagem'=>$mensagem !== '' ? $mensagem : null]);
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
                if (!isset($validos[$escritorioId])) throw new RuntimeException('Meta de escritório inválida.');
                $valorNormalizado = financeiroNormalizarMoedaBr((string)$valor);
                $mensagem = mb_substr(strip_tags(trim((string)($_POST['meta_mensagem'][$escritorioId] ?? ''))), 0, 500, 'UTF-8');
                salvarMetaEscritorio($pdo, $escritorioId, $competencia, $valorNormalizado, $mensagem);
            }
            $pdo->commit();
            setMensagem('success', 'Metas e recompensas dos escritórios de ' . date('m/Y', strtotime($competencia)) . ' atualizadas.');
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Configuração financeira: '.$e->getMessage());
        setMensagem('error', $e instanceof RuntimeException ? $e->getMessage() : 'Não foi possível salvar a configuração.');
    }
    redirecionar(APP_URL . 'configuracoes/financeiro?competencia=' . substr($competencia, 0, 7));
}

$escritorios = financeiroEscritorios($pdo, false);
$stmt=$pdo->prepare('SELECT escritorio_id,valor,mensagem FROM financeiro_metas_mensais WHERE usuario_id IS NULL AND competencia=:competencia');
$stmt->execute([':competencia'=>$competencia]);
$metasEscritorio=[];
foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $m) $metasEscritorio[$m['escritorio_id']]=['valor'=>$m['valor'],'mensagem'=>(string)($m['mensagem']??'')];
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
<section class="card">
    <div class="card-header"><h3>Escritórios cadastrados</h3></div>
    <div class="card-body" style="display:flex;flex-direction:column;gap:12px">
        <?php foreach($escritorios as $e): ?>
        <article style="border:1px solid var(--cor-borda);border-radius:10px;padding:14px;display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap">
            <div style="min-width:180px;flex:1">
                <strong style="display:block"><?= h($e['nome']) ?></strong>
                <small class="text-muted"><i class="fas fa-location-dot"></i> <?= h($e['cidade'].'/'.$e['uf']) ?></small>
            </div>
            <span class="badge <?= $e['ativo']?'badge-success':'badge-secondary' ?>"><?= $e['ativo']?'Ativo':'Inativo' ?></span>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <a class="btn btn-secondary btn-sm" href="?editar=<?= urlencode($e['id']) ?>&competencia=<?= h(substr($competencia,0,7)) ?>"><i class="fas fa-edit"></i> Editar</a>
                <form method="post" onsubmit="return confirm('<?= $e['ativo']?'Desativar este escritório?':'Ativar este escritório?' ?>')">
                    <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>">
                    <input type="hidden" name="action" value="alternar_escritorio">
                    <input type="hidden" name="id" value="<?= h($e['id']) ?>">
                    <button class="btn btn-sm <?= $e['ativo']?'btn-danger':'btn-success' ?>" type="submit"><i class="fas fa-power-off"></i> <?= $e['ativo']?'Desativar':'Ativar' ?></button>
                </form>
            </div>
        </article>
        <?php endforeach ?>
    </div>
</section>
</div>
<section class="card" style="margin-top:20px"><div class="card-header" style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap"><h3><i class="fas fa-bullseye"></i> Metas e recompensas dos escritórios</h3><form method="get"><label>Competência <input type="month" name="competencia" value="<?= h(substr($competencia,0,7)) ?>" onchange="this.form.submit()"></label></form></div><div class="card-body"><form method="post"><input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>"><input type="hidden" name="action" value="salvar_metas_escritorios"><input type="hidden" name="competencia" value="<?= h(substr($competencia,0,7)) ?>"><div class="grid-2"><?php foreach($escritorios as $e)if($e['ativo']): $metaAtual=$metasEscritorio[$e['id']]??['valor'=>'0.00','mensagem'=>'']; ?><article style="border:1px solid var(--cor-borda);border-radius:10px;padding:16px"><h4 style="margin:0 0 14px"><i class="fas fa-building"></i> <?= h($e['nome'].' · '.$e['cidade'].'/'.$e['uf']) ?></h4><div class="form-group"><label>Meta mensal (R$)</label><input type="text" inputmode="decimal" autocomplete="off" maxlength="18" data-meta-moeda name="meta_escritorio[<?= h($e['id']) ?>]" value="<?= h(number_format((float)$metaAtual['valor'],2,',','.')) ?>" placeholder="0,00"></div><div class="form-group"><label for="meta_mensagem_<?= h($e['id']) ?>"><i class="fas fa-gift"></i> Recompensa ao atingir a meta</label><textarea id="meta_mensagem_<?= h($e['id']) ?>" name="meta_mensagem[<?= h($e['id']) ?>]" rows="3" maxlength="500" placeholder="Ex.: Ao bater a meta, a equipe deste escritório terá um dia especial." style="width:100%;padding:10px 14px"><?= h($metaAtual['mensagem']) ?></textarea><small class="text-muted">Apenas os usuários deste escritório verão esta mensagem.</small></div></article><?php endif ?></div><button class="btn btn-primary btn-lg" style="margin-top:18px"><i class="fas fa-save"></i> Salvar metas e recompensas</button></form></div></section>
</div>
<script>
document.querySelectorAll('[data-meta-moeda]').forEach(function (campo) {
    campo.addEventListener('input', function () {
        const digitos = campo.value.replace(/\D/g, '').slice(0, 14);
        const valor = Number(digitos || 0) / 100;
        campo.value = valor.toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    });
});
</script>
<?php require_once __DIR__.'/../../includes/footer.php'; ?>
