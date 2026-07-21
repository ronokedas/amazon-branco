<?php
/** Matriz de permissões individuais. Acesso exclusivo de administradores. */
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
verificar_sessao(); exigirAcesso('configuracoes');

$permissoes = [
 'analise_planos'=>['Análise de planos','Análise técnica de projetos'],
 'dashboard'=>['Dashboard','Visão geral'], 'vistorias'=>['Vistorias','Execução e consulta'], 'relatorios_aprovacao'=>['Relatórios aguardando aprovação','Aba específica para análise técnica'], 'agendamentos'=>['Agendamentos','Agenda e OS'], 'certificados'=>['Certificados','Emissão e consulta'], 'embarcacoes'=>['Embarcações','Cadastro'], 'armadores'=>['Armadores','Cadastro'], 'proprietarios'=>['Proprietários','Cadastro'], 'despachantes'=>['Despachantes','Cadastro'], 'comercial'=>['Comercial','Propostas'], 'servicos'=>['Serviços','Catálogo'], 'financeiro'=>['Financeiro','Lançamentos e relatórios'], 'documentacao'=>['Documentação','Workspace de documentos'], 'relatorios'=>['Relatórios gerenciais','Consultas do sistema'], 'emails'=>['E-mails','Central de e-mails'], 'portal_clientes'=>['Portal de clientes','Gestão de acessos'], 'usuarios'=>['Usuários','Gestão'], 'configuracoes'=>['Configurações','Parâmetros'], 'responsaveis_assinatura'=>['Responsáveis por assinatura','Cadastros para emissão'],
];
try {
 $pdo->exec("CREATE TABLE IF NOT EXISTS usuario_permissoes (usuario_id CHAR(36) NOT NULL, permissao VARCHAR(80) NOT NULL, permitido TINYINT(1) NOT NULL DEFAULT 0, atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (usuario_id, permissao), CONSTRAINT fk_usuario_permissoes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
} catch (Throwable $e) { setMensagem('error','Não foi possível preparar o controle de permissões.'); redirecionar(APP_URL.'configuracoes'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 if (!verificarCSRF($_POST['csrf_token'] ?? '')) { setMensagem('error','Token de segurança inválido.'); redirecionar(APP_URL.'configuracoes/basicas'); }
 $selecionadas = $_POST['permissoes'] ?? [];
 try {
  $ids = $pdo->query("SELECT id FROM usuarios WHERE cargo != 'ADMIN' AND excluido_em IS NULL")->fetchAll(PDO::FETCH_COLUMN); $pdo->beginTransaction();
  $upsert = $pdo->prepare('INSERT INTO usuario_permissoes (usuario_id, permissao, permitido) VALUES (:usuario_id,:permissao,:permitido) ON DUPLICATE KEY UPDATE permitido=VALUES(permitido)');
  foreach ($ids as $id) { $permitidas = array_flip(array_filter($selecionadas[$id] ?? [], fn($chave) => isset($permissoes[$chave]))); foreach (array_keys($permissoes) as $chave) $upsert->execute([':usuario_id'=>$id,':permissao'=>$chave,':permitido'=>isset($permitidas[$chave])?1:0]); }
  $pdo->commit(); setMensagem('success','Permissões atualizadas com sucesso.');
 } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); error_log('Erro ao salvar permissões: '.$e->getMessage()); setMensagem('error','Erro ao salvar permissões.'); }
 redirecionar(APP_URL.'configuracoes/basicas');
}
try { $usuarios=$pdo->query("SELECT id,nome,email,cargo,acesso_documentacao,acesso_financeiro FROM usuarios WHERE cargo != 'ADMIN' AND excluido_em IS NULL ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC); $linhas=$pdo->query('SELECT usuario_id,permissao,permitido FROM usuario_permissoes')->fetchAll(PDO::FETCH_ASSOC); $acessos=[]; foreach($linhas as $linha) $acessos[$linha['usuario_id']][$linha['permissao']]=(int)$linha['permitido']===1; } catch(Throwable $e) {$usuarios=[];$acessos=[];}
$titulo_page='Permissões de Usuários - ERP Sistema'; require_once __DIR__.'/../../includes/header.php'; require_once __DIR__.'/../../includes/sidebar.php';
?>
<div class="conteudo-principal"><div class="welcome-section" style="margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;"><div><h1><i class="fas fa-shield-alt"></i> Permissões de usuários</h1><p>Defina exatamente quais módulos e telas cada usuário pode acessar.</p></div><a href="<?= APP_URL ?>configuracoes" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar</a></div><div class="card"><div class="card-body"><p class="text-muted">Administradores sempre têm acesso total. Ao salvar, as permissões marcadas passam a ser a regra do usuário, inclusive para acesso direto por URL.</p><form method="post" action="<?= APP_URL ?>configuracoes/basicas"><input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>">
<?php foreach($usuarios as $usuario): $usuarioAcessos=$acessos[$usuario['id']]??[]; if(!$usuarioAcessos){foreach(permissoesPadraoCargo($usuario['cargo']) as $chave)$usuarioAcessos[$chave]=true; if(!empty($usuario['acesso_documentacao']))$usuarioAcessos['documentacao']=true; if(!empty($usuario['acesso_financeiro']))$usuarioAcessos['financeiro']=true;} ?><section style="border:1px solid var(--cor-borda,#ddd);border-radius:10px;padding:18px;margin:16px 0;"><header style="margin-bottom:14px;"><strong><?= h($usuario['nome']) ?></strong> <span class="badge bg-secondary"><?= h($usuario['cargo']) ?></span><br><small class="text-muted"><?= h($usuario['email']) ?></small></header><div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:10px;"><?php foreach($permissoes as $chave=>[$nome,$descricao]): ?><label style="display:flex;gap:9px;padding:10px;border:1px solid var(--cor-borda,#ddd);border-radius:7px;cursor:pointer;align-items:flex-start;"><input type="checkbox" name="permissoes[<?= h($usuario['id']) ?>][]" value="<?= h($chave) ?>" <?= !empty($usuarioAcessos[$chave])?'checked':'' ?> style="margin-top:3px;transform:scale(1.15);"><span><strong><?= h($nome) ?></strong><br><small class="text-muted"><?= h($descricao) ?></small></span></label><?php endforeach; ?></div></section><?php endforeach; ?>
<?php if(!$usuarios): ?><p class="text-muted">Nenhum usuário não administrador encontrado.</p><?php endif; ?><button class="btn btn-primary btn-lg" type="submit"><i class="fas fa-save"></i> Salvar permissões</button></form></div></div></div>
<?php require_once __DIR__.'/../../includes/footer.php'; ?>
