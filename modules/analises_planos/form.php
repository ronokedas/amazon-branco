<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/analise_planos.php';
analisePlanosExigirAcesso();

$id = trim($_GET['id'] ?? '');
if ($id === '') {
    $embarcacoes = $pdo->query("SELECT id, nome, registro, numero_inscricao, cliente_id, proprietario_id, tipo FROM embarcacoes WHERE ativo=1 ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
    $clientes = $pdo->query("SELECT id, nome, cpf_cnpj FROM clientes WHERE status='ATIVO' ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
    $analistas = $pdo->query("SELECT DISTINCT u.id,u.nome FROM usuarios u LEFT JOIN usuario_perfis p ON p.usuario_id=u.id WHERE u.ativo=1 AND u.excluido_em IS NULL AND (u.cargo='ANALISTA' OR p.perfil='ANALISTA') ORDER BY u.nome ASC")->fetchAll(PDO::FETCH_ASSOC);
    $usuarioIdAtual = (string)($_SESSION['usuario_id'] ?? '');

    $titulo_page = 'Nova Análise de Planos - ERP Sistema';
    require_once __DIR__ . '/../../includes/header.php';
    ?>
    <div class="conteudo-principal analise-planos-page">
        <div class="form-container">
            <div class="form-header">
                <div>
                    <h3><i class="fas fa-drafting-compass"></i> Nova Análise de Planos</h3>
                    <small>Abertura de processo técnico naval para conferência de planos e documentos de projeto.</small>
                </div>
                <a class="btn btn-secondary btn-sm" href="<?= APP_URL ?>analises-planos"><i class="fas fa-arrow-left"></i> Voltar</a>
            </div>

            <form method="post" action="<?= APP_URL ?>analises-planos/actions" class="form-padrao" style="padding:20px 0">
                <input type="hidden" name="csrf_token" value="<?= gerarCSRF() ?>">
                <input type="hidden" name="action" value="criar_analise">

                <div class="form-row">
                    <div class="form-group col-6">
                        <label for="embarcacao_id">Embarcação *</label>
                        <select id="embarcacao_id" name="embarcacao_id" required onchange="aoMudarEmbarcacao(this)">
                            <option value="">-- Selecione a embarcação --</option>
                            <?php foreach ($embarcacoes as $emb): ?>
                                <option value="<?= h($emb['id']) ?>" data-cliente="<?= h($emb['cliente_id'] ?: $emb['proprietario_id']) ?>">
                                    <?= h($emb['nome']) ?> <?= !empty($emb['registro']) ? ' - ' . h($emb['registro']) : '' ?> (<?= h($emb['tipo'] ?: 'Naval') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group col-6">
                        <label for="solicitante_id">Solicitante / Cliente *</label>
                        <select id="solicitante_id" name="solicitante_id" required>
                            <option value="">-- Selecione o cliente/solicitante --</option>
                            <?php foreach ($clientes as $cli): ?>
                                <option value="<?= h($cli['id']) ?>"><?= h($cli['nome']) ?> (<?= h($cli['cpf_cnpj']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-4">
                        <label for="tipo_processo">Tipo de Processo *</label>
                        <select id="tipo_processo" name="tipo_processo" required>
                            <option value="LC">LC - Licença de Construção</option>
                            <option value="LA">LA - Licença de Alteração</option>
                            <option value="LR">LR - Licença de Reclassificação</option>
                            <option value="LCEC">LCEC - Construção Embarcação Classificada</option>
                        </select>
                    </div>

                    <div class="form-group col-4">
                        <label for="enquadramento">Norma Aplicável *</label>
                        <select id="enquadramento" name="enquadramento" required>
                            <option value="NORMAM-202" selected>NORMAM-202/DPC (Navegação Interior)</option>
                        </select>
                    </div>

                    <div class="form-group col-4">
                        <label for="classe_certificacao">Classe de Certificação *</label>
                        <select id="classe_certificacao" name="classe_certificacao" required>
                            <option value="EC1">EC1 - Maior Porte / Complexidade Completa</option>
                            <option value="EC2">EC2 - Porte Intermediário / Simplificado</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-6">
                        <label for="objeto">Objeto do Processo *</label>
                        <input type="text" id="objeto" name="objeto" required placeholder="Ex: Análise de Planos de Construção e Estabilidade" value="Análise de Planos de Construção">
                    </div>

                    <div class="form-group col-3">
                        <label for="analista_id">Analista Responsável *</label>
                        <select id="analista_id" name="analista_id" required>
                            <option value="">-- Selecione o analista --</option>
                            <?php foreach ($analistas as $u): ?>
                                <option value="<?= h($u['id']) ?>" <?= $usuarioIdAtual === $u['id'] ? 'selected' : '' ?>>
                                    <?= h($u['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group col-3">
                        <label for="prazo_agendado_em">Prazo Previsto de Conclusão</label>
                        <input type="datetime-local" id="prazo_agendado_em" name="prazo_agendado_em" value="<?= date('Y-m-d\T18:00', strtotime('+7 days')) ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-4">
                        <label for="estaleiro">Estaleiro Construtor</label>
                        <input type="text" id="estaleiro" name="estaleiro" placeholder="Nome do estaleiro">
                    </div>

                    <div class="form-group col-2">
                        <label for="numero_casco">Nº do Casco</label>
                        <input type="text" id="numero_casco" name="numero_casco" placeholder="Nº do casco">
                    </div>

                    <div class="form-group col-3">
                        <label for="responsavel_projeto_nome">Autor do Projeto (Engenheiro)</label>
                        <input type="text" id="responsavel_projeto_nome" name="responsavel_projeto_nome" placeholder="Nome do engenheiro autor">
                    </div>

                    <div class="form-group col-3">
                        <label for="art_numero">Nº da ART / CREA</label>
                        <input type="text" id="art_numero" name="art_numero" placeholder="Ex: ART 2802... / CREA">
                    </div>
                </div>

                <div class="form-group">
                    <label for="observacoes">Observações Iniciais</label>
                    <textarea id="observacoes" name="observacoes" rows="3" placeholder="Informações relevantes sobre o projeto, limitações geográficas da bacia, etc."></textarea>
                </div>

                <div class="form-actions" style="margin-top:20px;display:flex;gap:12px">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Abrir Processo de Análise</button>
                    <a href="<?= APP_URL ?>analises-planos" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
    <script>
    function aoMudarEmbarcacao(el) {
        const opt = el.options[el.selectedIndex];
        const cliId = opt.getAttribute('data-cliente');
        if (cliId) {
            const cliSelect = document.getElementById('solicitante_id');
            if (cliSelect) cliSelect.value = cliId;
        }
    }
    </script>
    <?php
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}
try {
    $a = analisePlanosCarregar($pdo, $id);
} catch (Throwable $e) {
    setMensagem('error', $e->getMessage());
    redirecionar(APP_URL . 'analises-planos');
}

$submissoes = [];
$q = $pdo->prepare('SELECT s.*,u.nome usuario_nome,c.nome portal_nome FROM analise_planos_submissoes s LEFT JOIN usuarios u ON u.id=s.criado_por LEFT JOIN clientes c ON c.id=s.portal_cliente_id WHERE s.analise_id=:id ORDER BY s.revisao DESC');
$q->execute([':id'=>$id]);
$submissoes = $q->fetchAll(PDO::FETCH_ASSOC);
foreach ($submissoes as &$sub) {
    $q = $pdo->prepare('SELECT ar.*,i.documento item_documento,u.nome classificador_nome FROM analise_planos_arquivos ar LEFT JOIN analise_planos_itens i ON i.id=ar.item_id LEFT JOIN usuarios u ON u.id=ar.classificado_por WHERE ar.submissao_id=:id ORDER BY ar.criado_em');
    $q->execute([':id'=>$sub['id']]);
    $sub['arquivos']=$q->fetchAll(PDO::FETCH_ASSOC);
}
unset($sub);
$q=$pdo->prepare('SELECT * FROM analise_planos_itens WHERE analise_id=:id ORDER BY ordem,id');$q->execute([':id'=>$id]);$itens=$q->fetchAll(PDO::FETCH_ASSOC);
$q=$pdo->prepare('SELECT * FROM analise_planos_exigencias WHERE analise_id=:id ORDER BY ordem,id');$q->execute([':id'=>$id]);$exigencias=$q->fetchAll(PDO::FETCH_ASSOC);
$q=$pdo->prepare('SELECT p.*,u.nome criador_nome FROM analise_planos_pareceres p LEFT JOIN usuarios u ON u.id=p.criado_por WHERE p.analise_id=:id ORDER BY p.versao DESC');$q->execute([':id'=>$id]);$pareceres=$q->fetchAll(PDO::FETCH_ASSOC);
$q=$pdo->prepare('SELECT h.*,u.nome usuario_nome FROM analise_planos_historico h LEFT JOIN usuarios u ON u.id=h.usuario_id WHERE h.analise_id=:id ORDER BY h.criado_em DESC LIMIT 60');$q->execute([':id'=>$id]);$historico=$q->fetchAll(PDO::FETCH_ASSOC);
$q=$pdo->prepare('SELECT ah.*,ua.nome analista_anterior_nome,un.nome analista_novo_nome,u.nome autor_nome FROM analise_planos_agenda_historico ah LEFT JOIN usuarios ua ON ua.id=ah.analista_anterior_id LEFT JOIN usuarios un ON un.id=ah.analista_novo_id LEFT JOIN usuarios u ON u.id=ah.criado_por WHERE ah.analise_id=:id ORDER BY ah.criado_em DESC');$q->execute([':id'=>$id]);$agendaHistorico=$q->fetchAll(PDO::FETCH_ASSOC);
$q=$pdo->prepare('SELECT id,numero_lc,tipo_licenca,status,assinado FROM certificados_lc WHERE analise_id=:id LIMIT 1');$q->execute([':id'=>$id]);$licenca=$q->fetch(PDO::FETCH_ASSOC);
$analistas=$pdo->query("SELECT DISTINCT u.id,u.nome FROM usuarios u LEFT JOIN usuario_perfis p ON p.usuario_id=u.id WHERE u.ativo=1 AND u.excluido_em IS NULL AND (u.cargo='ANALISTA' OR p.perfil='ANALISTA') ORDER BY u.nome")->fetchAll(PDO::FETCH_ASSOC);
$propostasLegado=[];$servicosLegado=[];$vendedoresLegado=[];
$isLegadoBloqueado = !empty($a['legado_sem_proposta']) && (empty($a['proposta_id']) || empty($a['servico_id']) || empty($a['vendedor_origem_id']));
if (getCargo() === 'ADMIN' && $isLegadoBloqueado) {
    $q = $pdo->prepare("SELECT id,numero FROM propostas WHERE cliente_id=:cliente AND status='assinada' ORDER BY data_emissao DESC,numero DESC LIMIT 100");
    $q->execute([':cliente' => $a['solicitante_id']]);
    $propostasLegado = $q->fetchAll(PDO::FETCH_ASSOC);
    $servicosLegado = $pdo->query("SELECT id,nome,codigo_operacional FROM servicos WHERE ativo=1 AND codigo_operacional IN ('ANALISE_PLANOS_EC1','ANALISE_PLANOS_EC2') ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
    $vendedoresLegado = $pdo->query("SELECT id,nome FROM usuarios WHERE ativo=1 AND excluido_em IS NULL AND cargo='VENDEDOR' ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
}

$cargo = getCargo();
$usuario = (string)($_SESSION['usuario_id'] ?? '');
$podeTecnico = ($cargo === 'ANALISTA' && $a['analista_id'] === $usuario) || $cargo === 'ADMIN';
$origemComercialCompleta = !empty($a['proposta_id']) && !empty($a['servico_id']) && !empty($a['vendedor_origem_id']);
$podeAgenda = ($cargo === 'ADMIN' || ($cargo === 'VENDEDOR' && $a['vendedor_origem_id'] === $usuario) || ($cargo === 'ANALISTA' && $a['analista_id'] === $usuario)) && !$isLegadoBloqueado;
$iniciada = !empty($a['iniciado_em']) || in_array($a['status'], ['EM_ANALISE','AGUARDANDO_DOCUMENTOS','AGUARDANDO_ASSINATURA_ANALISTA','AGUARDANDO_APROVACAO_ADMIN','CONCLUIDA'], true);
$tecnicoEditavel = $podeTecnico && in_array($a['status'], ['AGENDADA','EM_ANALISE','AGUARDANDO_DOCUMENTOS'], true);
$analiseAberta = $podeTecnico && in_array($a['status'], ['EM_ANALISE','AGUARDANDO_DOCUMENTOS'], true);
$statusLabels = ['AGUARDANDO_AGENDAMENTO'=>'Aguardando agendamento','AGENDADA'=>'Agendada','EM_ANALISE'=>'Em análise','AGUARDANDO_DOCUMENTOS'=>'Aguardando documentos','AGUARDANDO_ASSINATURA_ANALISTA'=>'Aguardando assinatura do analista','AGUARDANDO_APROVACAO_ADMIN'=>'Aguardando admin','CONCLUIDA'=>'Concluída','REPROVADA'=>'Reprovada','CANCELADA'=>'Cancelada'];
$protocolos = [];
if (podeAcessar('protocolos_documentais')) {
    try {
        $q = $pdo->prepare('SELECT id,numero,assunto,status FROM protocolo_dossies WHERE analise_id=:id ORDER BY criado_em');
        $q->execute([':id'=>$id]);
        $protocolos = $q->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {}
}

$vistoriasVinculadas = [];
try {
    $stmtVist = $pdo->prepare("
        SELECT v.id AS vistoria_id, v.numero AS vistoria_numero, v.status AS vistoria_status,
               v.tipo_vistoria AS vistoria_tipo, v.local_vistoria, v.aprovado_em, v.criado_em AS vistoria_data,
               a.id AS agendamento_id, a.data_vistoria, a.hora_vistoria, a.local AS agendamento_local,
               a.status AS agendamento_status, a.tipo_vistoria AS agendamento_tipo_vistoria,
               u.nome AS vistoriador_nome, u.telefone AS vistoriador_telefone,
               (SELECT COUNT(*) FROM vistoria_fotos vf WHERE vf.vistoria_id = v.id) AS total_fotos,
               (SELECT COUNT(*) FROM vistoria_exigencias ve WHERE ve.vistoria_id = v.id) AS total_exigencias,
               (SELECT COUNT(*) FROM vistoria_exigencias ve WHERE ve.vistoria_id = v.id AND ve.status = 'PENDENTE') AS exigencias_pendentes
        FROM agendamentos a
        LEFT JOIN vistorias v ON v.agendamento_id = a.id
        LEFT JOIN usuarios u ON u.id = a.vistoriador_id
        WHERE a.embarcacao_id = :embarcacao_id
          AND a.status <> 'cancelado'
        ORDER BY COALESCE(v.atualizado_em, a.data_vistoria, a.created_at) DESC
        LIMIT 5
    ");
    $stmtVist->execute([':embarcacao_id' => $a['embarcacao_id']]);
    $vistoriasVinculadas = $stmtVist->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('Erro ao buscar vistorias vinculadas à análise: ' . $e->getMessage());
}

$titulo_page = $a['numero'] . ' - Análise de Planos';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="conteudo-principal analise-planos-page">
 <div class="form-container">
  <div class="form-header"><div><h3><i class="fas fa-drafting-compass"></i> <?=h($a['numero'])?></h3><small><?=h($a['proposta_numero'] ?: 'Processo histórico sem proposta')?> · <?=h($a['servico_nome'] ?: 'Serviço não vinculado')?></small></div><a class="btn btn-secondary btn-sm" href="<?=APP_URL?>analises-planos"><i class="fas fa-arrow-left"></i> Voltar</a></div>
  <div class="analise-summary"><span><b>Situação</b><?=h($statusLabels[$a['status']]??$a['status'])?></span><span><b>Embarcação</b><?=h($a['embarcacao_nome'])?></span><span><b>Vendedor de origem</b><?=h($a['vendedor_origem_nome'] ?: 'Legado / Direto')?></span><span><b>Analista</b><?=h($a['analista_nome'] ?: 'Não atribuído')?></span><span><b>Prazo</b><?=!empty($a['prazo_agendado_em'])?formatarDataCompleta($a['prazo_agendado_em']):'Não agendado'?></span></div>
 </div>

 <?php
 $submissoesPortal = array_filter($submissoes, fn($s) => ($s['origem'] ?? '') === 'PORTAL');
 $totalArquivosPortal = array_reduce($submissoesPortal, fn($acc, $s) => $acc + count($s['arquivos'] ?? []), 0);
 $analisePodeIniciar = $podeTecnico && ($a['status'] === 'AGENDADA' || empty($a['iniciado_em']));
 ?>

 <!-- Documentos de Projeto Recebidos do Armador (Portal do Cliente) -->
 <section class="analise-card" style="border-left: 4px solid #0284c7;">
  <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 10px;">
   <div>
    <h3 style="margin-bottom: 4px;"><i class="fa-solid fa-cloud-arrow-up text-primary"></i> Documentos de Projeto do Armador (Portal do Cliente)</h3>
    <p class="text-muted" style="margin-bottom: 0; font-size: 0.88rem;">
     Plantas de engenharia naval, memoriais descritivos, arranjo geral e cálculos enviados pelo cliente via Portal do Armador.
    </p>
   </div>
   <div>
    <?php if ($totalArquivosPortal > 0): ?>
     <span class="badge bg-primary" style="font-size: 0.82rem; padding: 6px 12px;">
      <i class="fa-solid fa-folder-open"></i> <?= (int)$totalArquivosPortal ?> arquivo(s) do armador
     </span>
    <?php endif; ?>
   </div>
  </div>

  <?php if (!empty($submissoesPortal)): ?>
   <div style="margin-top: 14px; display: flex; flex-direction: column; gap: 14px;">
    <?php foreach ($submissoesPortal as $subP): ?>
     <div style="border: 1px solid #e0f2fe; border-radius: 8px; padding: 14px; background: #f8fafc;">
      <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 10px; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px;">
       <div>
        <strong style="color: #0369a1; font-size: 0.95rem;">
         <i class="fa-solid fa-box-archive"></i> Revisão <?= (int)$subP['revisao'] ?> · Portal do Armador
        </strong>
        <span style="font-size: 0.84rem; color: #64748b; margin-left: 8px;">
         <i class="fa-solid fa-calendar-day"></i> Recebido em <?= formatarData($subP['recebido_em']) ?>
         · <i class="fa-solid fa-user"></i> <?= h($subP['portal_nome'] ?: 'Armador/Cliente') ?>
        </span>
       </div>
       <?php if (!empty($subP['descricao'])): ?>
        <span style="font-size: 0.84rem; background: #e0f2fe; color: #0369a1; padding: 3px 10px; border-radius: 12px;">
         <?= h($subP['descricao']) ?>
        </span>
       <?php endif; ?>
      </div>

      <div style="display: flex; flex-direction: column; gap: 8px;">
       <?php foreach ($subP['arquivos'] as $arqP): ?>
        <?php
        $ext = strtolower($arqP['extensao'] ?? 'pdf');
        $iconeArquivo = match($ext) {
            'pdf' => 'fa-file-pdf text-danger',
            'dwg', 'dxf' => 'fa-drafting-compass text-primary',
            'doc', 'docx' => 'fa-file-word text-info',
            'xls', 'xlsx' => 'fa-file-excel text-success',
            'jpg', 'jpeg', 'png' => 'fa-file-image text-warning',
            default => 'fa-file text-secondary'
        };
        $tamBytes = (int)($arqP['tamanho_bytes'] ?? 0);
        $tamFormatado = $tamBytes < 1024 ? $tamBytes . ' B' : ($tamBytes < 1048576 ? round($tamBytes / 1024, 1) . ' KB' : round($tamBytes / 1048576, 2) . ' MB');
        $badgeClassifColor = match($arqP['classificacao'] ?? '') {
            'ACEITO' => 'success',
            'SUBSTITUIDO' => 'warning',
            'REJEITADO' => 'danger',
            default => 'secondary'
        };
        ?>
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; background: #fff; border: 1px solid #e2e8f0; border-radius: 6px; flex-wrap: wrap; gap: 8px;">
         <div style="display: flex; align-items: center; gap: 10px;">
          <i class="fa-solid <?= $iconeArquivo ?>" style="font-size: 1.3rem;"></i>
          <div>
           <strong style="font-size: 0.9rem; color: #1e293b;"><?= h($arqP['nome_original']) ?></strong>
           <div style="font-size: 0.78rem; color: #64748b;">
            <span><?= h($arqP['categoria'] ?: 'Projeto') ?></span> · 
            <span><?= $tamFormatado ?></span> · 
            <span class="badge bg-<?= $badgeClassifColor ?>" style="font-size: 0.72rem;"><?= h($arqP['classificacao'] ?: 'RECEBIDO') ?></span>
            <?php if (!empty($arqP['item_documento'])): ?>
             · <span style="color: #0369a1;"><i class="fa-solid fa-link"></i> <?= h($arqP['item_documento']) ?></span>
            <?php endif; ?>
           </div>
          </div>
         </div>
         <div style="display: flex; gap: 8px; align-items: center;">
          <a class="btn btn-outline-primary btn-sm" href="<?= APP_URL ?>analises-planos/arquivo?id=<?= urlencode($arqP['id']) ?>" target="_blank" title="Abrir / Visualizar documento original">
           <i class="fa-solid fa-arrow-up-right-from-square"></i> Visualizar
          </a>
          <a class="btn btn-secondary btn-sm" href="<?= APP_URL ?>analises-planos/arquivo?id=<?= urlencode($arqP['id']) ?>&download=1" title="Baixar arquivo original para o computador">
           <i class="fa-solid fa-download"></i> Baixar
          </a>
         </div>
        </div>
       <?php endforeach; ?>
      </div>
     </div>
    <?php endforeach; ?>
   </div>

   <?php if ($analisePodeIniciar): ?>
    <div style="margin-top: 14px; padding: 14px 18px; background: rgba(34, 197, 94, 0.08); border: 1px solid rgba(34, 197, 94, 0.3); border-radius: 8px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
     <div>
      <strong style="color: #15803d; font-size: 0.95rem;"><i class="fa-solid fa-circle-check"></i> Documentos prontos para conferência do Analista!</strong>
      <p style="margin: 2px 0 0 0; font-size: 0.84rem; color: #166534;">
       Você pode iniciar a análise técnica agora mesmo. A conferência dos planos não depende da realização da vistoria física de campo.
      </p>
     </div>
     <form method="post" action="<?= APP_URL ?>analises-planos/actions">
      <input type="hidden" name="csrf_token" value="<?= gerarCSRF() ?>">
      <input type="hidden" name="action" value="iniciar">
      <input type="hidden" name="analise_id" value="<?= h($id) ?>">
      <button class="btn btn-success" style="font-weight: 600; box-shadow: 0 4px 12px rgba(22, 101, 52, 0.25);">
       <i class="fas fa-play"></i> Iniciar Análise Técnica Agora
      </button>
     </form>
    </div>
   <?php endif; ?>

  <?php else: ?>
   <div style="padding: 14px; background: rgba(148, 163, 184, 0.08); border-radius: 8px; margin-top: 14px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
    <div>
     <p class="text-muted mb-0" style="font-size: 0.88rem;">
      <i class="fa-solid fa-info-circle"></i> Nenhum arquivo anexado pelo armador no Portal do Cliente até o momento.
      O analista também pode anexar arquivos recebidos diretamente por e-mail ou mídia física na seção <strong>Revisões e arquivos</strong> abaixo.
     </p>
    </div>
    <?php if ($analisePodeIniciar): ?>
     <form method="post" action="<?= APP_URL ?>analises-planos/actions">
      <input type="hidden" name="csrf_token" value="<?= gerarCSRF() ?>">
      <input type="hidden" name="action" value="iniciar">
      <input type="hidden" name="analise_id" value="<?= h($id) ?>">
      <button class="btn btn-outline-primary btn-sm"><i class="fas fa-play"></i> Iniciar Análise Técnica</button>
     </form>
    <?php endif; ?>
   </div>
  <?php endif; ?>
 </section>

 <!-- Vistoria Técnica de Campo (A Bordo) -->
 <section class="analise-card" style="border-left: 4px solid #087653;">
  <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 10px;">
   <div>
    <h3 style="margin-bottom: 4px;"><i class="fa-solid fa-ship text-success"></i> Vistoria Técnica de Campo (A Bordo)</h3>
    <p class="text-muted" style="margin-bottom: 0; font-size: 0.88rem;">
     Confronte as medições, fotos e anteparas inspecionadas a bordo pelo Vistoriador com os planos de projeto e estabilidade.
    </p>
   </div>
  </div>

  <div style="margin-top: 10px; padding: 10px 14px; background: rgba(37, 99, 235, 0.06); border-left: 4px solid #2563eb; border-radius: 4px; font-size: 0.86rem; color: #1e40af;">
   <strong><i class="fa-solid fa-circle-info"></i> Independência Operacional NORMAM-202:</strong>
   A análise e aprovação das plantas de engenharia (Arranjo Geral, Linhas, Estabilidade e Memorial Descritivo) é um processo documental de escritório e <strong>pode ser iniciada e realizada a qualquer momento</strong>, independentemente da realização da vistoria física a bordo.
  </div>

  <?php if (!$vistoriasVinculadas): ?>
   <div style="padding: 14px; background: rgba(148, 163, 184, 0.08); border-radius: 8px; margin-top: 14px;">
    <p class="text-muted mb-0"><i class="fa-solid fa-info-circle"></i> Nenhuma vistoria de campo agendada ou registrada para esta embarcação no momento.</p>
   </div>
  <?php else: ?>
   <div style="display: flex; flex-direction: column; gap: 12px; margin-top: 14px;">
    <?php foreach ($vistoriasVinculadas as $vItem): ?>
     <?php
     $statusVistLabel = match($vItem['vistoria_status'] ?? '') {
         'APROVADA' => 'Vistoria Aprovada (Homologada)',
         'APROVADA_COM_EXIGENCIAS' => 'Aprovada com Exigências de Campo',
         'RETORNO_AS' => 'Retorno A/S Pendente',
         'REPROVADA' => 'Reprovada em Campo',
         'EM_HOMOLOGACAO' => 'Em Homologação Técnica',
         default => (!empty($vItem['vistoria_id']) ? 'Em Andamento a Bordo' : ($vItem['agendamento_status'] === 'confirmado' ? 'Agendada e Confirmada' : 'Agendamento Pendente de Campo'))
     };
     $badgeVistColor = match($vItem['vistoria_status'] ?? '') {
         'APROVADA' => 'success',
         'APROVADA_COM_EXIGENCIAS', 'RETORNO_AS' => 'warning',
         'REPROVADA' => 'danger',
         default => 'info'
     };
     ?>
     <div style="border: 1px solid var(--cor-borda, #e2e8f0); border-radius: 8px; padding: 14px; background: var(--cor-card-bg, #fff); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
      <div>
       <div style="font-weight: 700; font-size: 0.95rem; margin-bottom: 4px;">
        <?= !empty($vItem['vistoria_numero']) ? h($vItem['vistoria_numero']) : 'Ordem de Campo' ?>
        <span class="badge bg-<?= $badgeVistColor ?>" style="font-size: 0.76rem; margin-left: 6px;"><?= $statusVistLabel ?></span>
       </div>
       <div style="font-size: 0.84rem; color: var(--cor-texto-secundario, #64748b);">
        <i class="fa-solid fa-user-gear"></i> Vistoriador: <strong><?= h($vItem['vistoriador_nome'] ?: 'Ainda não atribuído') ?></strong>
        · <i class="fa-solid fa-calendar"></i> Data: <strong><?= !empty($vItem['data_vistoria']) ? date('d/m/Y', strtotime($vItem['data_vistoria'])) : 'A definir' ?></strong>
        <?php if (!empty($vItem['local_vistoria']) || !empty($vItem['agendamento_local'])): ?>
         · <i class="fa-solid fa-location-dot"></i> Local: <?= h($vItem['local_vistoria'] ?: $vItem['agendamento_local']) ?>
        <?php endif; ?>
       </div>
       <div style="font-size: 0.8rem; margin-top: 6px; display: flex; gap: 14px; color: #475569;">
        <span><i class="fa-solid fa-camera"></i> <strong><?= (int)$vItem['total_fotos'] ?></strong> foto(s) de bordo</span>
        <span><i class="fa-solid fa-triangle-exclamation"></i> <strong><?= (int)$vItem['total_exigencias'] ?></strong> exigência(s) de campo <?= (int)$vItem['exigencias_pendentes'] > 0 ? '(' . (int)$vItem['exigencias_pendentes'] . ' pendentes)' : '' ?></span>
       </div>
      </div>
      <div style="display: flex; gap: 8px; align-items: center; flex-wrap: nowrap;">
       <?php if (!empty($vItem['vistoria_id'])): ?>
        <a class="btn btn-secondary btn-sm" target="_blank" href="<?= APP_URL ?>vistorias/relatorio-pdf?id=<?= urlencode($vItem['vistoria_id']) ?>" title="Baixar PDF Oficial do Relatório de Vistoria">
         <i class="fa-solid fa-file-pdf text-danger"></i> PDF RTV
        </a>
        <a class="btn btn-primary btn-sm" href="<?= APP_URL ?>vistorias/relatorio?agendamento_id=<?= urlencode((string)$vItem['agendamento_id']) ?>&vistoria_id=<?= urlencode((string)$vItem['vistoria_id']) ?>" title="Abrir Relatório Técnico de Vistoria e Fotos">
         <i class="fa-solid fa-clipboard-check"></i> Ver Vistoria & Fotos
        </a>
       <?php elseif (!empty($vItem['agendamento_id'])): ?>
        <a class="btn btn-outline-secondary btn-sm" href="<?= APP_URL ?>agendamentos/form?id=<?= urlencode($vItem['agendamento_id']) ?>" title="Ver detalhes do agendamento">
         <i class="fa-solid fa-calendar"></i> Ver Agendamento
        </a>
       <?php endif; ?>
      </div>
     </div>
    <?php endforeach; ?>
   </div>
  <?php endif; ?>
 </section>

 <?php if(podeAcessar('protocolos_documentais')): ?><section class="analise-card"><h3><i class="fas fa-arrow-right-arrow-left"></i> Tramitação documental</h3><p>O protocolo registra custódia e envio; a baixa técnica das exigências continua sendo feita somente pelos relatórios de ciclo.</p><?php foreach($protocolos as $prot): ?><p><a href="<?= APP_URL ?>protocolos/form?id=<?= urlencode($prot['id']) ?>"><strong><?= h($prot['numero']) ?></strong> · <?= h($prot['assunto']) ?></a> <span class="badge"><?= h($prot['status']) ?></span></p><?php endforeach; ?><?php if(!$protocolos): ?><p class="text-muted">Nenhum dossiê vinculado.</p><?php endif; ?><a class="btn btn-secondary btn-sm" href="<?= APP_URL ?>protocolos/form?analise_id=<?= urlencode($id) ?>&embarcacao_id=<?= urlencode($a['embarcacao_id']) ?>"><i class="fas fa-plus"></i> Abrir protocolo deste processo</a></section><?php endif; ?>

 <?php if($cargo==='ADMIN'&&$isLegadoBloqueado):?>
 <section class="analise-card"><h3><i class="fas fa-link"></i> Vincular origem do processo legado</h3>
  <p class="alert alert-warning">Este processo foi preservado da migração anterior, mas requer vínculo comercial antes de agendar ou gerar nova licença.</p>
  <form method="post" action="<?=APP_URL?>analises-planos/actions" class="analise-inline-form">
   <input type="hidden" name="csrf_token" value="<?=gerarCSRF()?>"><input type="hidden" name="action" value="vincular_legado"><input type="hidden" name="analise_id" value="<?=h($id)?>">
   <div><label>Proposta assinada *</label><select name="proposta_id" required><option value="">Selecione</option><?php foreach($propostasLegado as $p):?><option value="<?=h($p['id'])?>"><?=h($p['numero'])?></option><?php endforeach?></select></div>
   <div><label>Serviço de análise *</label><select name="servico_id" required><option value="">Selecione</option><?php foreach($servicosLegado as $s):?><option value="<?=h($s['id'])?>"><?=h($s['nome'])?></option><?php endforeach?></select></div>
   <div><label>Vendedor de origem *</label><select name="vendedor_origem_id" required><option value="">Selecione</option><?php foreach($vendedoresLegado as $v):?><option value="<?=h($v['id'])?>"><?=h($v['nome'])?></option><?php endforeach?></select></div>
   <button class="btn btn-primary"><i class="fas fa-link"></i> Vincular origem</button>
  </form>
 </section>
 <?php endif?>

 <?php if($podeAgenda&&!in_array($a['status'],['CONCLUIDA','REPROVADA','CANCELADA'],true)):?>
 <section class="analise-card"><h3><i class="fas fa-calendar-check"></i> Agenda da análise</h3>
  <form method="post" action="<?=APP_URL?>analises-planos/actions" class="analise-inline-form">
   <input type="hidden" name="csrf_token" value="<?=gerarCSRF()?>"><input type="hidden" name="action" value="agendar"><input type="hidden" name="analise_id" value="<?=h($id)?>">
   <div><label>Analista *</label><select name="analista_id" required <?=($iniciada&&$cargo!=='ADMIN')?'disabled':''?>><option value="">Selecione</option><?php foreach($analistas as $u):?><option value="<?=h($u['id'])?>" <?=$a['analista_id']===$u['id']?'selected':''?>><?=h($u['nome'])?></option><?php endforeach?></select><?php if($iniciada&&$cargo!=='ADMIN'):?><input type="hidden" name="analista_id" value="<?=h($a['analista_id'])?>"><?php endif?></div>
   <div><label>Prazo *</label><input type="datetime-local" name="prazo_agendado_em" value="<?=!empty($a['prazo_agendado_em'])?date('Y-m-d\TH:i',strtotime($a['prazo_agendado_em'])):''?>" required></div>
   <div><label>Motivo <?=!empty($a['prazo_agendado_em'])?'*':''?></label><input name="motivo" maxlength="500" placeholder="<?=!empty($a['prazo_agendado_em'])?'Obrigatório no reagendamento':'Agendamento inicial'?>"></div>
   <button class="btn btn-primary"><i class="fas fa-calendar-check"></i> <?=!empty($a['prazo_agendado_em'])?'Reagendar':'Agendar'?></button>
  </form>
  <?php if($agendaHistorico):?><div class="timeline"><?php foreach($agendaHistorico as $ag):?><div><strong><?=h($ag['acao'])?> · <?=formatarDataCompleta($ag['prazo_novo_em'])?></strong><span><?=h($ag['autor_nome'])?> · <?=formatarDataCompleta($ag['criado_em'])?></span><p><?=h($ag['motivo'])?></p></div><?php endforeach?></div><?php endif?>
 </section>
 <?php endif?>

 <?php if ($podeTecnico && ($a['status'] === 'AGENDADA' || empty($a['iniciado_em']))): ?><section class="analise-card"><form method="post" action="<?=APP_URL?>analises-planos/actions"><input type="hidden" name="csrf_token" value="<?=gerarCSRF()?>"><input type="hidden" name="action" value="iniciar"><input type="hidden" name="analise_id" value="<?=h($id)?>"><button class="btn btn-primary"><i class="fas fa-play"></i> Iniciar análise técnica</button></form></section><?php endif?>

 <section class="analise-card"><h3><i class="fas fa-ship"></i> Enquadramento técnico</h3>
  <form method="post" action="<?=APP_URL?>analises-planos/actions" class="form-padrao"><input type="hidden" name="csrf_token" value="<?=gerarCSRF()?>"><input type="hidden" name="action" value="salvar"><input type="hidden" name="id" value="<?=h($id)?>">
   <div class="form-row"><div class="form-group col-3"><label>Processo *</label><select name="tipo_processo" required <?=$tecnicoEditavel?'':'disabled'?>><?php foreach(analisePlanosTiposPermitidos() as $v):?><option value="<?=$v?>" <?=$a['tipo_processo']===$v?'selected':''?>><?=$v?></option><?php endforeach?></select></div><div class="form-group col-3"><label>Norma *</label><select name="enquadramento" required <?=$tecnicoEditavel?'':'disabled'?>><?php foreach(analisePlanosNormasPermitidas() as $v):?><option value="<?=$v?>" <?=$a['enquadramento']===$v?'selected':''?>><?=$v?></option><?php endforeach?></select></div><div class="form-group col-3"><label>Classe</label><input value="<?=h($a['classe_certificacao'])?>" disabled></div><div class="form-group col-3"><label>Arqueação bruta</label><input type="number" step="0.01" min="0" name="arqueacao_bruta" value="<?=h($a['arqueacao_bruta'])?>" <?=$tecnicoEditavel?'':'disabled'?>></div></div>
   <div class="form-row"><div class="form-group col-3"><label>Passageiros</label><input type="number" min="0" name="numero_passageiros" value="<?=h($a['numero_passageiros'])?>" <?=$tecnicoEditavel?'':'disabled'?>></div><div class="form-group col-3"><label>Propulsão</label><select name="possui_propulsao" <?=$tecnicoEditavel?'':'disabled'?>><option value="">A definir</option><option value="1" <?=$a['possui_propulsao']==='1'?'selected':''?>>Com propulsão</option><option value="0" <?=$a['possui_propulsao']==='0'?'selected':''?>>Sem propulsão</option></select></div><div class="form-group col-3"><label>Classificada</label><select name="embarcacao_classificada" <?=$tecnicoEditavel?'':'disabled'?>><option value="">A definir</option><option value="1" <?=$a['embarcacao_classificada']==='1'?'selected':''?>>Sim</option><option value="0" <?=$a['embarcacao_classificada']==='0'?'selected':''?>>Não</option></select></div><div class="form-group col-3"><label>Construção concluída</label><select name="construcao_concluida" <?=$tecnicoEditavel?'':'disabled'?>><option value="">A definir</option><option value="1" <?=$a['construcao_concluida']==='1'?'selected':''?>>Sim</option><option value="0" <?=$a['construcao_concluida']==='0'?'selected':''?>>Não</option></select></div></div>
   <div class="form-row"><div class="form-group col-6"><label>Objeto *</label><input name="objeto" required value="<?=h($a['objeto'])?>" <?=$tecnicoEditavel?'':'disabled'?>></div><div class="form-group col-3"><label>Navegação</label><input name="tipo_navegacao" value="<?=h($a['tipo_navegacao'])?>" <?=$tecnicoEditavel?'':'disabled'?>></div><div class="form-group col-3"><label>Nº do casco</label><input name="numero_casco" value="<?=h($a['numero_casco'])?>" <?=$tecnicoEditavel?'':'disabled'?>></div></div>
   <div class="form-row"><div class="form-group col-4"><label>Estaleiro</label><input name="estaleiro" value="<?=h($a['estaleiro'])?>" <?=$tecnicoEditavel?'':'disabled'?>></div><div class="form-group col-4"><label>Responsável pelo projeto</label><input name="responsavel_projeto_nome" value="<?=h($a['responsavel_projeto_nome'])?>" <?=$tecnicoEditavel?'':'disabled'?>></div><div class="form-group col-2"><label>Registro/CREA</label><input name="responsavel_projeto_registro" value="<?=h($a['responsavel_projeto_registro'])?>" <?=$tecnicoEditavel?'':'disabled'?>></div><div class="form-group col-2"><label>ART</label><input name="art_numero" value="<?=h($a['art_numero'])?>" <?=$tecnicoEditavel?'':'disabled'?>></div></div>
   <div class="form-group"><label>Observações</label><textarea name="observacoes" rows="3" <?=$tecnicoEditavel?'':'disabled'?>><?=h($a['observacoes'])?></textarea></div>
   <?php if($tecnicoEditavel):?><div class="form-actions"><button class="btn btn-primary"><i class="fas fa-save"></i> Salvar enquadramento e checklist</button></div><?php endif?>
  </form>
 </section>

 <section class="analise-card"><h3><i class="fas fa-cloud-arrow-up"></i> Revisões e arquivos</h3>
  <?php if($analiseAberta):?><form method="post" enctype="multipart/form-data" action="<?=APP_URL?>analises-planos/actions" class="analise-inline-form"><input type="hidden" name="csrf_token" value="<?=gerarCSRF()?>"><input type="hidden" name="action" value="adicionar_submissao"><input type="hidden" name="analise_id" value="<?=h($id)?>"><select name="categoria"><?php foreach(analisePlanosCategoriasPadrao() as $cat):?><option><?=h($cat)?></option><?php endforeach?></select><input type="date" name="recebido_em" value="<?=date('Y-m-d')?>" required><input name="descricao" placeholder="Descrição da revisão"><input type="file" name="arquivos[]" multiple required accept=".pdf,.jpg,.jpeg,.png,.dwg,.dxf,.doc,.docx,.xls,.xlsx"><button class="btn btn-primary"><i class="fas fa-upload"></i> Adicionar revisão</button></form><?php endif?>
  <?php if(!$submissoes):?><p class="text-muted">Nenhuma revisão recebida.</p><?php endif;foreach($submissoes as $s):?><div class="revision-block"><strong>Revisão <?=$s['revisao']?> · <?=h($s['origem'])?></strong><span><?=formatarData($s['recebido_em'])?> · <?=h($s['usuario_nome']?:$s['portal_nome']?:'Origem não informada')?></span><?php foreach($s['arquivos'] as $arq):?><div class="file-review"><a class="file-pill" href="<?=APP_URL?>analises-planos/arquivo?id=<?=urlencode($arq['id'])?>" target="_blank"><i class="fas fa-file"></i><?=h($arq['nome_original'])?></a><span class="badge"><?=h($arq['classificacao'])?></span><?php if($analiseAberta):?><form method="post" action="<?=APP_URL?>analises-planos/actions"><input type="hidden" name="csrf_token" value="<?=gerarCSRF()?>"><input type="hidden" name="action" value="classificar_arquivo"><input type="hidden" name="analise_id" value="<?=h($id)?>"><input type="hidden" name="arquivo_id" value="<?=h($arq['id'])?>"><select name="item_id"><option value="">Sem item</option><?php foreach($itens as $item):?><option value="<?=h($item['id'])?>" <?=$arq['item_id']===$item['id']?'selected':''?>><?=h($item['documento'])?></option><?php endforeach?></select><select name="classificacao"><?php foreach(['ACEITO','SUBSTITUIDO','REJEITADO'] as $cl):?><option <?=$arq['classificacao']===$cl?'selected':''?>><?=$cl?></option><?php endforeach?></select><input name="justificativa" value="<?=h($arq['justificativa_classificacao'])?>" placeholder="Justificativa obrigatória para rejeitar/substituir"><button class="btn btn-secondary btn-sm">Classificar</button></form><?php endif?></div><?php endforeach?></div><?php endforeach?>
 </section>

 <section class="analise-card"><h3><i class="fas fa-list-check"></i> Matriz normativa</h3>
  <?php if(!$itens):?><p>Defina e salve o processo e a norma para gerar o checklist.</p><?php else:?><form method="post" action="<?=APP_URL?>analises-planos/actions"><input type="hidden" name="csrf_token" value="<?=gerarCSRF()?>"><input type="hidden" name="action" value="salvar_itens"><input type="hidden" name="analise_id" value="<?=h($id)?>"><div class="portal-table-wrap"><table><thead><tr><th>#</th><th>Documento/requisito</th><th>Referência</th><th>Obrigatório</th><th>Resultado</th><th>Observação</th></tr></thead><tbody><?php foreach($itens as $i=>$item):?><tr><td><?=$i+1?><input type="hidden" name="item_id[]" value="<?=h($item['id'])?>"></td><td><?=h($item['documento'])?></td><td><?=h($item['referencia_normativa'])?></td><td><?=$item['obrigatorio']?'Sim':'Condicional'?></td><td><select name="resultado[]" <?=$analiseAberta?'':'disabled'?>><?php foreach(['PENDENTE','CONFORME','EXIGENCIA','NAO_APLICA'] as $v):?><option value="<?=$v?>" <?=$item['resultado']===$v?'selected':''?>><?=str_replace('_',' ',$v)?></option><?php endforeach?></select></td><td><textarea name="item_observacao[]" rows="2" <?=$analiseAberta?'':'disabled'?>><?=h($item['observacao'])?></textarea></td></tr><?php endforeach?></tbody></table></div><?php if($analiseAberta):?><button class="btn btn-primary"><i class="fas fa-save"></i> Salvar matriz</button><?php endif?></form><?php endif?>
 </section>

  <?php 
  $categoriasNormam = analisePlanosCategoriasNormam(); 
  $todasReferenciasPreload = analisePlanosBuscarReferenciasNormam($pdo);
  ?>
  <section class="analise-card" id="secao-exigencias">
   <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:10px; margin-bottom:12px;">
    <div>
     <h3 style="margin:0 0 4px 0;"><i class="fas fa-triangle-exclamation text-warning"></i> Exigências vigentes</h3>
     <p class="text-muted" style="margin:0; font-size:0.86rem;">
      A situação não é editada manualmente. A baixa ocorre via manifestação técnica nos relatórios de ciclo com validação da coordenação.
     </p>
    </div>
    <div style="display:flex; gap:8px; align-items:center;">
     <?php if($analiseAberta):?>
      <button type="button" class="btn btn-success btn-sm" onclick="abrirModalBancoNormam()">
       <i class="fa-solid fa-book-bookmark"></i> Inserir do Banco NORMAM
      </button>
     <?php endif;?>
     <a href="<?=APP_URL?>analises-planos/referencias" target="_blank" class="btn btn-outline-secondary btn-sm" title="Gerenciar banco de referências da Autoridade Marítima">
      <i class="fa-solid fa-external-link-alt"></i> Gerenciar Banco
     </a>
    </div>
   </div>

   <form method="post" action="<?=APP_URL?>analises-planos/actions" id="formExigencias">
    <input type="hidden" name="csrf_token" value="<?=gerarCSRF()?>">
    <input type="hidden" name="action" value="salvar_exigencias">
    <input type="hidden" name="analise_id" value="<?=h($id)?>">

    <div class="portal-table-wrap">
     <table>
      <thead>
       <tr>
        <th style="width:40px;">#</th>
        <th style="width:180px;">Categoria / Documento</th>
        <th>Descrição da Exigência</th>
        <th style="width:220px;">Referência Normativa</th>
        <th style="width:110px;">Situação</th>
        <?php if($analiseAberta):?><th style="width:60px;">Ação</th><?php endif;?>
       </tr>
      </thead>
      <tbody>
       <?php if(!$exigencias):?>
        <tr>
         <td colspan="<?=$analiseAberta ? 6 : 5?>" style="text-align:center; padding:18px; color:var(--cor-texto-secundario,#64748b);">
          <i class="fa-solid fa-circle-check text-success" style="font-size:1.2rem; display:block; margin-bottom:6px;"></i>
          Nenhuma exigência cadastrada para esta análise. O projeto está sem pendências ativas.
         </td>
        </tr>
       <?php endif;?>
       <?php foreach($exigencias as $i=>$ex):?>
        <tr>
         <td style="vertical-align:middle; text-align:center;">
          <strong><?=$i+1?></strong>
          <input type="hidden" name="exigencia_id[]" value="<?=h($ex['id'])?>">
         </td>
         <td>
          <select name="exigencia_categoria[]" class="form-control form-control-sm" <?=$analiseAberta?'':'disabled'?> style="font-size:0.82rem;">
           <?php 
           $catAtual = trim($ex['categoria'] ?? 'GERAL') ?: 'GERAL';
           foreach($categoriasNormam as $cNome):?>
            <option value="<?=h($cNome)?>" <?=$catAtual===$cNome?'selected':''?>><?=h($cNome)?></option>
           <?php endforeach;?>
           <?php if(!in_array($catAtual, $categoriasNormam, true)):?>
            <option value="<?=h($catAtual)?>" selected><?=h($catAtual)?></option>
           <?php endif;?>
          </select>
         </td>
         <td>
          <textarea name="exigencia_descricao[]" rows="2" class="form-control" <?=$analiseAberta?'':'disabled'?> style="font-size:0.88rem;"><?=h($ex['descricao'])?></textarea>
         </td>
         <td>
          <input name="exigencia_referencia[]" class="form-control form-control-sm" value="<?=h($ex['referencia_normativa'])?>" <?=$analiseAberta?'':'disabled'?> style="font-size:0.85rem;" placeholder="Ex.: NORMAM-202/DPC">
         </td>
         <td style="vertical-align:middle;">
          <?php 
          $badgeClass = match($ex['status']) {
              'CUMPRIDA' => 'badge-success',
              'PARCIAL' => 'badge-info',
              default => 'badge-warning'
          };
          ?>
          <span class="badge <?=$badgeClass?>"><?=h($ex['status'])?></span>
          <?php if(!empty($ex['saneamento_pendente'])):?>
           <small class="text-warning" style="display:block; font-size:0.75rem; margin-top:2px;">Requer saneamento.</small>
          <?php endif;?>
         </td>
         <?php if($analiseAberta):?>
          <td style="vertical-align:middle; text-align:center;">
           <?php if($ex['status'] === 'PENDENTE'):?>
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="excluirExigencia('<?=h($ex['id'])?>')" title="Excluir exigência cadastrada">
             <i class="fa-solid fa-trash"></i>
            </button>
           <?php else:?>
            <span class="text-muted" title="Exigências em relatório não podem ser excluídas fisicamente">—</span>
           <?php endif;?>
          </td>
         <?php endif;?>
        </tr>
       <?php endforeach;?>
      </tbody>
     </table>
    </div>

    <?php if($analiseAberta):?>
     <div style="margin-top:16px; padding:16px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
       <strong style="color:#0f172a; font-size:0.92rem;"><i class="fa-solid fa-plus-circle text-primary"></i> Nova Exigência para esta Análise</strong>
       <button type="button" class="btn btn-outline-success btn-sm" onclick="abrirModalBancoNormam()">
        <i class="fa-solid fa-book-bookmark"></i> Escolher Modelo no Banco NORMAM
       </button>
      </div>
      <div class="form-row" style="margin-bottom:8px;">
       <div class="form-group col-4" style="margin-bottom:0;">
        <label style="font-size:0.82rem;">Categoria / Documento</label>
        <select name="nova_exigencia_categoria" id="nova_exigencia_categoria" class="form-control form-control-sm">
         <?php foreach($categoriasNormam as $cNome):?>
          <option value="<?=h($cNome)?>"><?=h($cNome)?></option>
         <?php endforeach;?>
        </select>
        <small class="text-muted" style="font-size:0.75rem;">Grupo onde constará no relatório</small>
       </div>
       <div class="form-group col-8" style="margin-bottom:0;">
        <label style="font-size:0.82rem;">Referência Normativa NORMAM / DPC</label>
        <input name="nova_exigencia_referencia" id="nova_exigencia_referencia" class="form-control form-control-sm" placeholder="Ex.: NORMAM-202/DPC, Anexo 3-F, Item 0316">
        <small class="text-muted" style="font-size:0.75rem;">Base legal da Autoridade Marítima ou RIPEAM</small>
       </div>
      </div>
      <div class="form-group" style="margin-bottom:12px;">
       <label style="font-size:0.82rem;">Descrição Técnica da Exigência</label>
       <textarea name="nova_exigencia" id="nova_exigencia" rows="2" class="form-control" placeholder="Descreva tecnicamente o que o armador/projetista deve corrigir ou envie do Banco de Normas..."></textarea>
      </div>
      <div style="display:flex; justify-content:flex-end; gap:8px;">
       <button type="submit" class="btn btn-primary">
        <i class="fas fa-save"></i> Salvar / Registrar Exigências
       </button>
      </div>
     </div>
    <?php endif;?>
   </form>

   <!-- Form oculto para exclusão de exigência pendente -->
   <form id="formExcluirExigencia" method="post" action="<?=APP_URL?>analises-planos/actions" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?=gerarCSRF()?>">
    <input type="hidden" name="action" value="excluir_exigencia">
    <input type="hidden" name="analise_id" value="<?=h($id)?>">
    <input type="hidden" name="exigencia_id" id="excluir_exigencia_id" value="">
   </form>
  </section>

  <!-- Modal Seletor de Referências NORMAM -->
  <div id="modalBancoNormam" class="modal-normam-overlay" style="display:none;">
   <div class="modal-normam-content">
    <div class="modal-normam-header">
     <div>
      <h3 style="margin:0; font-size:1.15rem; color:#0f172a;"><i class="fa-solid fa-book-bookmark text-success"></i> Banco de Referências NORMAM</h3>
      <small style="color:#64748b;">Selecione uma exigência padronizada da Marinha do Brasil / DPC para aplicar com 1 clique.</small>
     </div>
     <button type="button" class="btn-fechar-modal" onclick="fecharModalBancoNormam()">&times;</button>
    </div>
    
    <div class="modal-normam-filtros">
     <div style="flex:1; min-width:200px;">
      <input type="text" id="modalBuscaNormam" placeholder="Buscar por texto, anexo, item ou artigo..." class="form-control form-control-sm" oninput="filtrarNormasModal()">
     </div>
     <div style="width:240px;">
      <select id="modalCategoriaNormam" class="form-control form-control-sm" onchange="filtrarNormasModal()">
       <option value="">Todas as Categorias</option>
       <?php foreach($categoriasNormam as $cNome):?>
        <option value="<?=h($cNome)?>"><?=h($cNome)?></option>
       <?php endforeach;?>
      </select>
     </div>
    </div>

    <div id="modalNormamLista" class="modal-normam-lista">
     <div style="text-align:center; padding:30px; color:#64748b;">
      <i class="fa-solid fa-spinner fa-spin" style="font-size:1.5rem;"></i>
      <p style="margin-top:8px;">Carregando referências normativas...</p>
     </div>
    </div>

    <div class="modal-normam-footer">
     <span id="modalContadorNormas" style="font-size:0.84rem; color:#64748b;"></span>
     <button type="button" class="btn btn-secondary btn-sm" onclick="fecharModalBancoNormam()">Fechar</button>
    </div>
   </div>
  </div>

  <style>
  .modal-normam-overlay{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.65);z-index:99999;display:flex;align-items:center;justify-content:center;padding:16px;backdrop-filter:blur(3px)}
  .modal-normam-content{background:#fff;border-radius:12px;width:100%;max-width:920px;max-height:88vh;display:flex;flex-direction:column;box-shadow:0 20px 40px rgba(0,0,0,0.3);overflow:hidden}
  .modal-normam-header{display:flex;justify-content:space-between;align-items:center;padding:16px 20px;border-bottom:1px solid #e2e8f0;background:#f8fafc}
  .btn-fechar-modal{background:none;border:none;font-size:1.8rem;line-height:1;color:#64748b;cursor:pointer;padding:0 6px}
  .btn-fechar-modal:hover{color:#0f172a}
  .modal-normam-filtros{display:flex;gap:12px;padding:12px 20px;background:#f1f5f9;border-bottom:1px solid #e2e8f0;flex-wrap:wrap}
  .modal-normam-lista{flex:1;overflow-y:auto;padding:16px 20px;display:flex;flex-direction:column;gap:10px}
  .normam-item-card{border:1px solid #e2e8f0;border-radius:8px;padding:12px 14px;background:#fff;transition:all .15s ease}
  .normam-item-card:hover{border-color:#10b981;box-shadow:0 2px 8px rgba(16,185,129,0.12)}
  .normam-item-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;flex-wrap:wrap;gap:6px}
  .normam-item-cat{font-size:0.75rem;font-weight:700;padding:2px 8px;border-radius:4px;background:#d1fae5;color:#065f46}
  .normam-item-ref{font-size:0.8rem;font-weight:600;color:#0284c7}
  .normam-item-desc{font-size:0.86rem;color:#334155;margin:0 0 8px 0;line-height:1.4}
  .normam-item-actions{display:flex;justify-content:flex-end}
  .modal-normam-footer{display:flex;justify-content:space-between;align-items:center;padding:12px 20px;border-top:1px solid #e2e8f0;background:#f8fafc}
  </style>

  <script>
  let bancoNormasCache = <?= json_encode($todasReferenciasPreload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?> || [];
  let ultimosItensFiltrados = [];

  function abrirModalBancoNormam() {
      const modal = document.getElementById('modalBancoNormam');
      modal.style.display = 'flex';
      if (!bancoNormasCache || !Array.isArray(bancoNormasCache) || bancoNormasCache.length === 0) {
          carregarBancoNormas();
      } else {
          filtrarNormasModal();
      }
      setTimeout(() => document.getElementById('modalBuscaNormam')?.focus(), 100);
  }

  function fecharModalBancoNormam() {
      document.getElementById('modalBancoNormam').style.display = 'none';
  }

  function carregarBancoNormas() {
      const lista = document.getElementById('modalNormamLista');
      lista.innerHTML = '<div style="text-align:center; padding:30px; color:#64748b;"><i class="fa-solid fa-spinner fa-spin" style="font-size:1.5rem;"></i><p style="margin-top:8px;">Carregando referências normativas...</p></div>';
      
      fetch('<?= APP_URL ?>analises-planos/referencias-actions?action=buscar_ajax')
          .then(r => {
              if (!r.ok) throw new Error('HTTP ' + r.status);
              return r.json();
          })
          .then(data => {
              const itens = Array.isArray(data) ? data : (data.dados || []);
              bancoNormasCache = itens;
              filtrarNormasModal();
          })
          .catch(err => {
              console.warn('Erro ao atualizar banco de normas via AJAX:', err);
              if (bancoNormasCache && Array.isArray(bancoNormasCache) && bancoNormasCache.length > 0) {
                  filtrarNormasModal();
              } else {
                  lista.innerHTML = '<div style="color:#ef4444; padding:20px; text-align:center;"><i class="fa-solid fa-circle-exclamation"></i> Falha ao carregar referências do servidor (' + escapeHtml(err.message) + ').</div>';
              }
          });
  }

  function filtrarNormasModal() {
      if (!bancoNormasCache || !Array.isArray(bancoNormasCache)) return;
      const busca = (document.getElementById('modalBuscaNormam')?.value || '').toLowerCase().trim();
      const cat = document.getElementById('modalCategoriaNormam')?.value || '';
      
      ultimosItensFiltrados = bancoNormasCache.filter(item => {
          if (cat && item.categoria !== cat) return false;
          if (!busca) return true;
          const texto = ((item.categoria || '') + ' ' + (item.referencia_normativa || '') + ' ' + (item.titulo || '') + ' ' + (item.descricao_padrao || '')).toLowerCase();
          return texto.includes(busca);
      });

      const lista = document.getElementById('modalNormamLista');
      const contador = document.getElementById('modalContadorNormas');
      if (contador) contador.textContent = `${ultimosItensFiltrados.length} referência(s) encontrada(s)`;

      if (ultimosItensFiltrados.length === 0) {
          lista.innerHTML = '<div style="text-align:center; padding:30px; color:#64748b;"><i class="fa-solid fa-folder-open" style="font-size:1.6rem; margin-bottom:8px; display:block;"></i>Nenhuma referência normativa corresponde aos filtros.</div>';
          return;
      }

      lista.innerHTML = ultimosItensFiltrados.map((item, idx) => {
          const cat = escapeHtml(item.categoria || 'GERAL');
          const ref = escapeHtml(item.referencia_normativa || '');
          const desc = escapeHtml(item.descricao_padrao || item.titulo || '');
          
          return `
              <div class="normam-item-card">
                  <div class="normam-item-header">
                      <span class="normam-item-cat">${cat}</span>
                      <span class="normam-item-ref"><i class="fa-solid fa-scale-balanced"></i> ${ref}</span>
                  </div>
                  <p class="normam-item-desc">${desc}</p>
                  <div class="normam-item-actions">
                      <button type="button" class="btn btn-success btn-sm" onclick="aplicarReferenciaPorIndex(${idx})">
                          <i class="fa-solid fa-check"></i> Aplicar nesta Exigência
                      </button>
                  </div>
              </div>
          `;
      }).join('');
  }

  function aplicarReferenciaPorIndex(idx) {
      const item = ultimosItensFiltrados[idx];
      if (!item) return;
      aplicarReferenciaNormam(item);
  }

  function aplicarReferenciaNormam(item) {
      const selectCat = document.getElementById('nova_exigencia_categoria');
      const inputRef = document.getElementById('nova_exigencia_referencia');
      const textDesc = document.getElementById('nova_exigencia');

      if (selectCat && item.categoria) {
          selectCat.value = item.categoria;
      }
      if (inputRef) {
          inputRef.value = item.referencia_normativa || '';
      }
      if (textDesc) {
          textDesc.value = item.descricao_padrao || item.titulo || '';
      }

      fecharModalBancoNormam();

      // Scroll suave até o bloco de cadastro e focar na descrição
      textDesc?.scrollIntoView({ behavior: 'smooth', block: 'center' });
      setTimeout(() => textDesc?.focus(), 300);
  }

  function excluirExigencia(id) {
      if (!confirm('Deseja realmente remover esta exigência pendente?')) return;
      document.getElementById('excluir_exigencia_id').value = id;
      document.getElementById('formExcluirExigencia').submit();
  }

  function escapeHtml(str) {
      return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }
  </script>

 <section class="analise-card" id="pareceres"><h3><i class="fas fa-file-signature"></i> Relatórios por ciclo e licença</h3>
  <?php if($analiseAberta):?><form method="post" action="<?=APP_URL?>analises-planos/actions" class="form-padrao"><input type="hidden" name="csrf_token" value="<?=gerarCSRF()?>"><input type="hidden" name="action" value="criar_parecer"><input type="hidden" name="analise_id" value="<?=h($id)?>"><div class="form-row"><div class="form-group col-4"><label>Resultado do ciclo</label><select name="resultado"><option value="EXIGENCIAS">Exigências pendentes</option><option value="APROVADO">Conclusivo — saldo zero</option><option value="REPROVADO">Reprovado</option></select></div><div class="form-group col-4"><label>Revisão analisada *</label><select name="submissao_id" required><option value="">Selecione</option><?php foreach($submissoes as $s):?><option value="<?=h($s['id'])?>">Revisão <?=$s['revisao']?> · <?=formatarData($s['recebido_em'])?></option><?php endforeach?></select></div><div class="form-group col-4"><label>Resumo</label><textarea name="resumo" required></textarea></div></div><?php if($exigencias):?><h4>Manifestação e baixa das exigências</h4><div class="portal-table-wrap"><table><thead><tr><th>Exigência</th><th>Resultado deste ciclo</th><th>Manifestação técnica</th></tr></thead><tbody><?php foreach($exigencias as $ex):?><tr><td><?=h($ex['descricao'])?></td><td><select name="baixa_resultado[<?=h($ex['id'])?>]" required><option value="NAO_CUMPRIDA">Não cumprida</option><option value="PARCIAL">Parcial</option><option value="CUMPRIDA">Cumprida</option></select></td><td><textarea name="baixa_manifestacao[<?=h($ex['id'])?>]" required placeholder="Indique a evidência e a conclusão técnica"></textarea></td></tr><?php endforeach?></tbody></table></div><?php endif?><div class="form-group"><label>Conclusão</label><textarea name="conclusao" required></textarea></div><button class="btn btn-primary"><i class="fas fa-paper-plane"></i> Preparar relatório do ciclo</button></form><?php endif?>
  <?php foreach($pareceres as $p):?><article class="parecer-row"><div><strong><?=h($p['numero']?:('Relatório histórico v'.$p['versao']))?> · <?=h($p['finalidade']?:$p['resultado'])?></strong><span><?=h($p['status'])?> · <?=formatarDataCompleta($p['criado_em'])?></span><?php if($p['devolvido_motivo']):?><small><?=h($p['devolvido_motivo'])?></small><?php endif?></div><div><a class="btn btn-secondary btn-sm" target="_blank" href="<?=APP_URL?>analises-planos/parecer-pdf?id=<?=urlencode($p['id'])?>"><i class="fas fa-eye"></i> PDF</a><?php if($p['status']==='AGUARDANDO_ASSINATURA_ANALISTA'&&$cargo==='ANALISTA'&&$p['criado_por']===$usuario):?><form method="post" action="<?=APP_URL?>analises-planos/actions" style="display:inline"><input type="hidden" name="csrf_token" value="<?=gerarCSRF()?>"><input type="hidden" name="action" value="assinar_parecer"><input type="hidden" name="analise_id" value="<?=h($id)?>"><input type="hidden" name="parecer_id" value="<?=h($p['id'])?>"><button class="btn btn-success btn-sm"><i class="fas fa-signature"></i> Assinar tecnicamente</button></form><?php endif?><?php if($p['status']==='AGUARDANDO_APROVACAO_ADMIN'&&$cargo==='ADMIN'):?><form method="post" action="<?=APP_URL?>analises-planos/actions" style="display:inline"><input type="hidden" name="csrf_token" value="<?=gerarCSRF()?>"><input type="hidden" name="action" value="publicar"><input type="hidden" name="analise_id" value="<?=h($id)?>"><input type="hidden" name="parecer_id" value="<?=h($p['id'])?>"><button class="btn btn-success btn-sm">Validar e publicar</button><input name="motivo" placeholder="Motivo se devolver"><button name="devolver" value="1" class="btn btn-warning btn-sm">Devolver</button></form><?php endif?></div></article><?php endforeach?>
  <?php if($licenca):?><div class="alert alert-success"><strong>Licença <?=h($licenca['numero_lc'])?></strong> · <?=h($licenca['tipo_licenca'])?> · <?=h($licenca['status'])?> <a href="<?=APP_URL?>documentacao/lc/form?id=<?=urlencode($licenca['id'])?>">Abrir licença</a></div><?php endif?>
 </section>

 <section class="analise-card"><h3><i class="fas fa-clock-rotate-left"></i> Histórico auditável</h3><div class="timeline"><?php foreach($historico as $h):?><div><strong><?=h($h['evento'])?></strong><span><?=h($h['usuario_nome'])?> · <?=formatarDataCompleta($h['criado_em'])?></span><?php if($h['detalhe']):?><p><?=h($h['detalhe'])?></p><?php endif?></div><?php endforeach?></div></section>
</div>
<style>.analise-planos-page{display:grid;gap:18px}.analise-card{background:var(--cor-card,#fff);border:1px solid var(--cor-borda,#ddd);border-radius:12px;padding:20px}.analise-summary{display:grid;grid-template-columns:repeat(5,1fr);gap:10px;padding:14px 20px}.analise-summary span{display:flex;flex-direction:column;gap:4px}.analise-inline-form{display:flex;align-items:end;gap:10px;flex-wrap:wrap}.analise-inline-form>*{flex:1;min-width:150px}.revision-block{padding:14px 0;border-top:1px solid var(--cor-borda,#ddd)}.revision-block>span{margin-left:10px}.file-review{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:8px}.file-review form{display:flex;gap:7px;flex:1}.file-pill{padding:8px;border:1px solid var(--cor-borda,#ddd);border-radius:8px}.parecer-row{display:flex;justify-content:space-between;align-items:center;padding:14px 0;border-top:1px solid var(--cor-borda,#ddd)}.parecer-row>div:first-child{display:flex;flex-direction:column;gap:5px}.timeline>div{border-left:3px solid #2596be;padding:2px 0 14px 14px}.timeline span{display:block;font-size:.85rem;opacity:.7}.timeline p{margin:5px 0}@media(max-width:900px){.analise-summary{grid-template-columns:1fr 1fr}.parecer-row{align-items:flex-start;flex-direction:column}.file-review form{min-width:100%;flex-wrap:wrap}}</style>
<?php require_once __DIR__ . '/../../includes/footer.php';?>
