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
$statusLabels = [
    'AGUARDANDO_AGENDAMENTO' => 'Aguardando agendamento',
    'AGENDADA' => 'Agendada',
    'EM_ANALISE' => 'Em análise técnica',
    'AGUARDANDO_DOCUMENTOS' => 'Aguardando documentos',
    'AGUARDANDO_ASSINATURA_ANALISTA' => 'Aguardando assinatura do analista',
    'AGUARDANDO_APROVACAO_ADMIN' => 'Aguardando aprovação da diretoria',
    'CONCLUIDA' => 'Concluída / Aprovada',
    'REPROVADA' => 'Reprovada',
    'CANCELADA' => 'Cancelada'
];

$statusBadges = [
    'AGUARDANDO_AGENDAMENTO' => 'badge-warning',
    'AGENDADA' => 'badge-info',
    'EM_ANALISE' => 'badge-primary',
    'AGUARDANDO_DOCUMENTOS' => 'badge-warning',
    'AGUARDANDO_ASSINATURA_ANALISTA' => 'badge-warning',
    'AGUARDANDO_APROVACAO_ADMIN' => 'badge-info',
    'CONCLUIDA' => 'badge-success',
    'REPROVADA' => 'badge-danger',
    'CANCELADA' => 'badge-secondary'
];

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

// Métricas e dados calculados para o painel de topo
$submissoesPortal = array_filter($submissoes, fn($s) => ($s['origem'] ?? '') === 'PORTAL');
$totalArquivos = array_reduce($submissoes, fn($acc, $s) => $acc + count($s['arquivos'] ?? []), 0);
$totalArquivosPortal = array_reduce($submissoesPortal, fn($acc, $s) => $acc + count($s['arquivos'] ?? []), 0);
$totalExigencias = count($exigencias);
$exigenciasPendentes = count(array_filter($exigencias, fn($e) => $e['status'] === 'PENDENTE'));
$exigenciasCumpridas = count(array_filter($exigencias, fn($e) => $e['status'] === 'CUMPRIDA'));
$totalPareceres = count($pareceres);
$ultimoParecer = !empty($pareceres) ? $pareceres[0] : null;

$prazoItem = $a['prazo_agendado_em'] ?? '';
$isPrazoVencido = !empty($prazoItem) && substr($prazoItem, 0, 10) < date('Y-m-d');
$isPrazoHoje = !empty($prazoItem) && substr($prazoItem, 0, 10) === date('Y-m-d');

$analisePodeIniciar = $podeTecnico && ($a['status'] === 'AGENDADA' || empty($a['iniciado_em']));

$abaAtiva = trim($_GET['aba'] ?? 'exigencias');
if (!in_array($abaAtiva, ['exigencias', 'arquivos', 'pareceres', 'enquadramento', 'vistoria_tramite'], true)) {
    $abaAtiva = 'exigencias';
}

$categoriasNormam = analisePlanosCategoriasNormam();
$todasReferenciasPreload = analisePlanosBuscarReferenciasNormam($pdo);

$titulo_page = $a['numero'] . ' - Análise Técnica de Planos';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="conteudo-principal analise-planos-page">
    <!-- Breadcrumb e Hero Header Executivo do Processo -->
    <div class="analise-hero-card">
        <div class="analise-hero-main">
            <div class="analise-hero-info">
                <div class="analise-hero-meta">
                    <span class="analise-tag-processo"><i class="fa-solid fa-compass-drafting"></i> MESA TÉCNICA NAVAL</span>
                    <span class="badge <?= $statusBadges[$a['status']] ?? 'badge-secondary' ?>">
                        <i class="fa-solid fa-circle" style="font-size:6px; vertical-align:middle; margin-right:4px;"></i>
                        <?= h($statusLabels[$a['status']] ?? $a['status']) ?>
                    </span>
                    <?php if (!empty($a['tipo_processo'])): ?>
                        <span class="analise-tag-tipo"><?= h($a['tipo_processo']) ?> (<?= h($a['classe_certificacao'] ?: 'EC1') ?>)</span>
                    <?php endif; ?>
                </div>
                <h1 class="analise-hero-title"><?= h($a['numero']) ?></h1>
                <div class="analise-hero-sub">
                    <span><i class="fa-solid fa-ship"></i> <strong><?= h($a['embarcacao_nome']) ?></strong></span>
                    <span><i class="fa-solid fa-user"></i> <?= h($a['solicitante_nome'] ?: 'Armador / Solicitante') ?></span>
                    <span><i class="fa-solid fa-user-gear"></i> Analista: <strong><?= h($a['analista_nome'] ?: 'Não atribuído') ?></strong></span>
                    <?php if (!empty($prazoItem)): ?>
                        <span class="<?= $isPrazoVencido ? 'prazo-alerta-vencido' : ($isPrazoHoje ? 'prazo-alerta-hoje' : '') ?>">
                            <i class="fa-solid fa-clock"></i> Prazo: <?= date('d/m/Y H:i', strtotime($prazoItem)) ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="analise-hero-actions">
                <?php if ($analiseAberta): ?>
                    <button type="button" class="btn btn-success" onclick="abrirModalBancoNormam()">
                        <i class="fa-solid fa-book-bookmark"></i> + Banco NORMAM
                    </button>
                    <button type="button" class="btn btn-primary" onclick="trocarAbaAnalise('pareceres')">
                        <i class="fa-solid fa-file-signature"></i> Relatório RAP
                    </button>
                <?php endif; ?>
                <a class="btn btn-secondary" href="<?= APP_URL ?>analises-planos">
                    <i class="fas fa-arrow-left"></i> Voltar à Fila
                </a>
            </div>
        </div>

        <!-- Faixa de Indicadores e Métricas do Processo -->
        <div class="analise-kpi-strip">
            <article class="kpi-box">
                <div class="kpi-box__icon is-status"><i class="fa-solid fa-signal"></i></div>
                <div class="kpi-box__content">
                    <small>Situação Operacional</small>
                    <strong><?= h($statusLabels[$a['status']] ?? $a['status']) ?></strong>
                    <span><?= !empty($prazoItem) ? date('d/m/Y', strtotime($prazoItem)) : 'Prazo a definir' ?></span>
                </div>
            </article>

            <article class="kpi-box" onclick="trocarAbaAnalise('arquivos')" style="cursor:pointer">
                <div class="kpi-box__icon is-files"><i class="fa-solid fa-folder-open"></i></div>
                <div class="kpi-box__content">
                    <small>Pranchas & Arquivos</small>
                    <strong><?= (int)$totalArquivos ?> arquivo(s)</strong>
                    <span><?= (int)$totalArquivosPortal > 0 ? (int)$totalArquivosPortal . ' recebido(s) do armador' : 'Nenhuma prancha externa' ?></span>
                </div>
            </article>

            <article class="kpi-box" onclick="trocarAbaAnalise('exigencias')" style="cursor:pointer">
                <div class="kpi-box__icon is-exigencias"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <div class="kpi-box__content">
                    <small>Exigências NORMAM</small>
                    <strong><?= (int)$exigenciasPendentes ?> pendente(s)</strong>
                    <span><?= (int)$exigenciasCumpridas ?> de <?= (int)$totalExigencias ?> cumprida(s)</span>
                </div>
            </article>

            <article class="kpi-box" onclick="trocarAbaAnalise('pareceres')" style="cursor:pointer">
                <div class="kpi-box__icon is-reports"><i class="fa-solid fa-file-signature"></i></div>
                <div class="kpi-box__content">
                    <small>Relatórios Técnicos RAP</small>
                    <strong><?= (int)$totalPareceres ?> parecer(es)</strong>
                    <span><?= $ultimoParecer ? 'Último: ' . h($ultimoParecer['numero']) : 'Nenhum emitido' ?></span>
                </div>
            </article>
        </div>
    </div>

    <!-- Banner Independência Operacional / Início de Análise Técnica -->
    <?php if ($analisePodeIniciar): ?>
        <div class="banner-iniciar-analise">
            <div class="banner-iniciar-icon"><i class="fa-solid fa-compass-drafting"></i></div>
            <div class="banner-iniciar-content">
                <strong>Documentos e plantas prontos para conferência técnica do Analista Naval!</strong>
                <p>
                    A aprovação de planos (NORMAM-202/DPC) é um processo documental de engenharia. Você pode iniciar a análise e registrar exigências a qualquer momento, sem depender da realização da vistoria física a bordo.
                </p>
            </div>
            <form method="post" action="<?= APP_URL ?>analises-planos/actions">
                <input type="hidden" name="csrf_token" value="<?= gerarCSRF() ?>">
                <input type="hidden" name="action" value="iniciar">
                <input type="hidden" name="analise_id" value="<?= h($id) ?>">
                <button class="btn btn-success btn-lg btn-iniciar-glow">
                    <i class="fas fa-play"></i> Iniciar Análise Técnica Agora
                </button>
            </form>
        </div>
    <?php endif; ?>

    <!-- Barra de Abas Especializadas do Analista (AGENTS.md) -->
    <nav class="analise-tabs-bar">
        <button type="button" class="analise-tab-btn <?= $abaAtiva === 'exigencias' ? 'active' : '' ?>" onclick="trocarAbaAnalise('exigencias')">
            <i class="fa-solid fa-triangle-exclamation"></i> Exigências & Banco NORMAM
            <?php if ($exigenciasPendentes > 0): ?>
                <span class="badge bg-warning text-dark"><?= $exigenciasPendentes ?></span>
            <?php else: ?>
                <span class="badge bg-success"><i class="fa-solid fa-check"></i></span>
            <?php endif; ?>
        </button>

        <button type="button" class="analise-tab-btn <?= $abaAtiva === 'arquivos' ? 'active' : '' ?>" onclick="trocarAbaAnalise('arquivos')">
            <i class="fa-solid fa-folder-open"></i> Pranchas & Arquivos do Projeto
            <span class="badge bg-secondary"><?= $totalArquivos ?></span>
        </button>

        <button type="button" class="analise-tab-btn <?= $abaAtiva === 'pareceres' ? 'active' : '' ?>" onclick="trocarAbaAnalise('pareceres')">
            <i class="fa-solid fa-file-signature"></i> Relatórios Técnicos (RAP) & Licença
            <span class="badge bg-secondary"><?= $totalPareceres ?></span>
        </button>

        <button type="button" class="analise-tab-btn <?= $abaAtiva === 'enquadramento' ? 'active' : '' ?>" onclick="trocarAbaAnalise('enquadramento')">
            <i class="fa-solid fa-ship"></i> Enquadramento & Características Navais
        </button>

        <button type="button" class="analise-tab-btn <?= $abaAtiva === 'vistoria_tramite' ? 'active' : '' ?>" onclick="trocarAbaAnalise('vistoria_tramite')">
            <i class="fa-solid fa-anchor"></i> Vistoria de Campo & Trâmite
            <?php if (!empty($vistoriasVinculadas)): ?>
                <span class="badge bg-info text-dark"><?= count($vistoriasVinculadas) ?></span>
            <?php endif; ?>
        </button>
    </nav>

    <!-- ABA 1: EXIGÊNCIAS TÉCNICAS & BANCO NORMAM -->
    <div id="pane-exigencias" class="analise-tab-pane <?= $abaAtiva === 'exigencias' ? 'active' : '' ?>">
        <section class="analise-card" id="secao-exigencias">
            <div class="analise-card__head-flex">
                <div>
                    <h3><i class="fa-solid fa-triangle-exclamation text-warning"></i> Exigências Técnicas Vigentes (NORMAM-202)</h3>
                    <p class="text-muted">
                        As não-conformidades técnicas cadastradas nesta seção compõem o modelo oficial do <strong>Relatório de Análise de Planos (RAP)</strong> enviado ao Armador e à Capitania dos Portos.
                    </p>
                </div>
                <div class="analise-card__actions-head">
                    <?php if ($analiseAberta): ?>
                        <button type="button" class="btn btn-success" onclick="abrirModalBancoNormam()">
                            <i class="fa-solid fa-book-bookmark"></i> Inserir do Banco NORMAM (221 Modelos)
                        </button>
                    <?php endif; ?>
                    <a href="<?= APP_URL ?>analises-planos/referencias" target="_blank" class="btn btn-outline-secondary btn-sm" title="Gerenciar acervo de exigências e normas da Autoridade Marítima">
                        <i class="fa-solid fa-external-link-alt"></i> Gerenciar Banco
                    </a>
                </div>
            </div>

            <form method="post" action="<?= APP_URL ?>analises-planos/actions" id="formExigencias">
                <input type="hidden" name="csrf_token" value="<?= gerarCSRF() ?>">
                <input type="hidden" name="action" value="salvar_exigencias">
                <input type="hidden" name="analise_id" value="<?= h($id) ?>">

                <div class="table-responsive">
                    <table class="tabela-exigencias-moderna">
                        <thead>
                            <tr>
                                <th style="width:45px; text-align:center;">#</th>
                                <th style="width:200px;">Categoria Técnica</th>
                                <th>Descrição da Exigência</th>
                                <th style="width:230px;">Referência Normativa</th>
                                <th style="width:115px; text-align:center;">Situação</th>
                                <?php if ($analiseAberta): ?><th style="width:70px; text-align:center;">Ação</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$exigencias): ?>
                                <tr>
                                    <td colspan="<?= $analiseAberta ? 6 : 5 ?>" class="exigencias-empty-cell">
                                        <div class="empty-exigencias-box">
                                            <i class="fa-solid fa-circle-check text-success"></i>
                                            <h4>Nenhuma exigência técnica pendente</h4>
                                            <p>O projeto naval está sem pendências ativas cadastradas. Caso encontre inconformidades nos planos, utilize o <strong>Banco NORMAM</strong> ou o formulário abaixo.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($exigencias as $i => $ex): ?>
                                <tr>
                                    <td style="vertical-align:middle; text-align:center;">
                                        <strong><?= $i + 1 ?></strong>
                                        <input type="hidden" name="exigencia_id[]" value="<?= h($ex['id']) ?>">
                                    </td>
                                    <td>
                                        <select name="exigencia_categoria[]" class="form-control form-control-sm" <?= $analiseAberta ? '' : 'disabled' ?>>
                                            <?php 
                                            $catAtual = trim($ex['categoria'] ?? 'GERAL') ?: 'GERAL';
                                            foreach ($categoriasNormam as $cNome): ?>
                                                <option value="<?= h($cNome) ?>" <?= $catAtual === $cNome ? 'selected' : '' ?>><?= h($cNome) ?></option>
                                            <?php endforeach; ?>
                                            <?php if (!in_array($catAtual, $categoriasNormam, true)): ?>
                                                <option value="<?= h($catAtual) ?>" selected><?= h($catAtual) ?></option>
                                            <?php endif; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <textarea name="exigencia_descricao[]" rows="2" class="form-control" <?= $analiseAberta ? '' : 'disabled' ?> style="font-size:0.88rem;"><?= h($ex['descricao']) ?></textarea>
                                    </td>
                                    <td>
                                        <input name="exigencia_referencia[]" class="form-control form-control-sm" value="<?= h($ex['referencia_normativa']) ?>" <?= $analiseAberta ? '' : 'disabled' ?> placeholder="Ex.: NORMAM-202/DPC, Anexo 3-F">
                                    </td>
                                    <td style="vertical-align:middle; text-align:center;">
                                        <?php 
                                        $badgeClass = match($ex['status']) {
                                            'CUMPRIDA' => 'badge-success',
                                            'PARCIAL' => 'badge-info',
                                            default => 'badge-warning'
                                        };
                                        ?>
                                        <span class="badge <?= $badgeClass ?>"><?= h($ex['status']) ?></span>
                                        <?php if (!empty($ex['saneamento_pendente'])): ?>
                                            <small class="text-warning d-block" style="font-size:0.72rem; margin-top:2px;">Requer saneamento</small>
                                        <?php endif; ?>
                                    </td>
                                    <?php if ($analiseAberta): ?>
                                        <td style="vertical-align:middle; text-align:center;">
                                            <?php if ($ex['status'] === 'PENDENTE'): ?>
                                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="excluirExigencia('<?= h($ex['id']) ?>')" title="Remover exigência não homologada">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted" title="Exigência vinculada a relatório emitido">—</span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($analiseAberta): ?>
                    <!-- Bloco Rápido e Autodidático para Adicionar Exigência -->
                    <div class="card-nova-exigencia">
                        <div class="card-nova-exigencia__header">
                            <div>
                                <strong><i class="fa-solid fa-circle-plus text-primary"></i> Cadastrar Nova Exigência Técnica</strong>
                                <small class="text-muted d-block">Clique nos atalhos rápidos de categoria ou busque diretamente no Banco NORMAM:</small>
                            </div>
                            <button type="button" class="btn btn-outline-success btn-sm" onclick="abrirModalBancoNormam()">
                                <i class="fa-solid fa-book-bookmark"></i> Consultar Banco NORMAM (221 Itens)
                            </button>
                        </div>

                        <!-- Chips / Pills de Seleção Rápida de Categoria em 1 Clique -->
                        <div class="category-pills-row">
                            <span class="pills-label"><i class="fa-solid fa-bolt"></i> Atalhos:</span>
                            <?php 
                            $chipsAtalhos = [
                                'GERAL',
                                'PLANO DE ARRANJO GERAL, LUZES, SEGURANÇA E CAPACIDADE.',
                                'ESTUDO DE ESTABILIDADE',
                                'PLANOS DE LINHAS',
                                'NOTAS DE ARQUEAÇÃO',
                                'NOTAS DE BORDA LIVRE',
                                'PLANO DE PERFIL ESTRUTURAL E SEÇÃO MESTRA.',
                                'PROVA DE INCLINAÇÃO OU PORTE BRUTO',
                                'MEMORIAL DESCRITIVO'
                            ];
                            foreach ($chipsAtalhos as $chip): 
                                $labelChip = match($chip) {
                                    'PLANO DE ARRANJO GERAL, LUZES, SEGURANÇA E CAPACIDADE.' => 'Arranjo Geral',
                                    'ESTUDO DE ESTABILIDADE' => 'Estabilidade',
                                    'PLANOS DE LINHAS' => 'Linhas',
                                    'NOTAS DE ARQUEAÇÃO' => 'Arqueação',
                                    'NOTAS DE BORDA LIVRE' => 'Borda Livre',
                                    'PLANO DE PERFIL ESTRUTURAL E SEÇÃO MESTRA.' => 'Estrutura / Seção',
                                    'PROVA DE INCLINAÇÃO OU PORTE BRUTO' => 'Prova de Inclinação',
                                    'MEMORIAL DESCRITIVO' => 'Memorial',
                                    default => 'Geral'
                                };
                            ?>
                                <button type="button" class="chip-category-btn" onclick="selecionarChipCategoria('<?= addslashes($chip) ?>', this)">
                                    <?= h($labelChip) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>

                        <div class="form-row" style="margin-top:12px;">
                            <div class="form-group col-4">
                                <label for="nova_exigencia_categoria" class="form-label-bold">Categoria Técnica *</label>
                                <select name="nova_exigencia_categoria" id="nova_exigencia_categoria" class="form-control form-control-sm">
                                    <?php foreach ($categoriasNormam as $cNome): ?>
                                        <option value="<?= h($cNome) ?>"><?= h($cNome) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Seção onde constará no laudo oficial</small>
                            </div>

                            <div class="form-group col-8">
                                <label for="nova_exigencia_referencia" class="form-label-bold">Referência Normativa NORMAM / DPC</label>
                                <input name="nova_exigencia_referencia" id="nova_exigencia_referencia" class="form-control form-control-sm" placeholder="Ex.: NORMAM-202/DPC, Anexo 3-F, Item 0316">
                                <div class="quick-normam-refs">
                                    <small class="text-muted">Sugestões rápidas:</small>
                                    <a href="javascript:void(0)" onclick="setQuickRef('NORMAM-202/DPC, Anexo 3-F')">Anexo 3-F</a> ·
                                    <a href="javascript:void(0)" onclick="setQuickRef('NORMAM-202/DPC, Anexo 3-G')">Anexo 3-G</a> ·
                                    <a href="javascript:void(0)" onclick="setQuickRef('NORMAM-202/DPC, Cap. 3')">Capítulo 3</a> ·
                                    <a href="javascript:void(0)" onclick="setQuickRef('RIPEAM')">RIPEAM</a>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="nova_exigencia" class="form-label-bold">Descrição Técnica da Exigência</label>
                            <textarea name="nova_exigencia" id="nova_exigencia" rows="3" class="form-control" placeholder="Descreva tecnicamente o que o armador/engenheiro projetista deve corrigir na prancha ou cálculo naval..."></textarea>
                            <small class="text-muted">Seja claro e específico para agilizar o atendimento da exigência pelo projetista naval.</small>
                        </div>

                        <div class="card-nova-exigencia__footer">
                            <button type="submit" class="btn btn-primary btn-salvar-exigencia">
                                <i class="fas fa-save"></i> Salvar e Registrar Exigências
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </form>

            <!-- Form Oculto para Exclusão de Exigência Pendente -->
            <form id="formExcluirExigencia" method="post" action="<?= APP_URL ?>analises-planos/actions" style="display:none;">
                <input type="hidden" name="csrf_token" value="<?= gerarCSRF() ?>">
                <input type="hidden" name="action" value="excluir_exigencia">
                <input type="hidden" name="analise_id" value="<?= h($id) ?>">
                <input type="hidden" name="exigencia_id" id="excluir_exigencia_id" value="">
            </form>
        </section>
    </div>

    <!-- ABA 2: PRANCHAS & ARQUIVOS DO PROJETO -->
    <div id="pane-arquivos" class="analise-tab-pane <?= $abaAtiva === 'arquivos' ? 'active' : '' ?>">
        <!-- Arquivos Recebidos do Armador via Portal do Cliente -->
        <section class="analise-card" style="border-left: 4px solid #0284c7;">
            <div class="analise-card__head-flex">
                <div>
                    <h3><i class="fa-solid fa-cloud-arrow-up text-primary"></i> Pranchas e Documentos Recebidos do Armador</h3>
                    <p class="text-muted">
                        Plantas de engenharia naval (DWG/PDF), memoriais descritivos, arranjo geral e cálculos submetidos pelo armador/projetista.
                    </p>
                </div>
                <div>
                    <?php if ($totalArquivosPortal > 0): ?>
                        <span class="badge bg-primary" style="font-size:0.84rem; padding:6px 12px;">
                            <i class="fa-solid fa-folder-open"></i> <?= (int)$totalArquivosPortal ?> arquivo(s) do armador
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($submissoesPortal)): ?>
                <div class="revisoes-portal-list">
                    <?php foreach ($submissoesPortal as $subP): ?>
                        <div class="revisao-card">
                            <div class="revisao-card__head">
                                <div>
                                    <strong style="color:#0369a1; font-size:0.95rem;">
                                        <i class="fa-solid fa-box-archive"></i> Revisão <?= (int)$subP['revisao'] ?> · Portal do Armador
                                    </strong>
                                    <span style="font-size:0.84rem; color:#64748b; margin-left:8px;">
                                        <i class="fa-solid fa-calendar-day"></i> <?= formatarData($subP['recebido_em']) ?>
                                        · <i class="fa-solid fa-user"></i> <?= h($subP['portal_nome'] ?: 'Armador / Cliente') ?>
                                    </span>
                                </div>
                                <?php if (!empty($subP['descricao'])): ?>
                                    <span class="badge bg-info text-dark"><?= h($subP['descricao']) ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="arquivos-grid">
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
                                    <div class="arquivo-item-row">
                                        <div class="arquivo-item-row__left">
                                            <i class="fa-solid <?= $iconeArquivo ?>" style="font-size:1.4rem;"></i>
                                            <div>
                                                <strong style="color:#0f172a; font-size:0.9rem;"><?= h($arqP['nome_original']) ?></strong>
                                                <div style="font-size:0.78rem; color:#64748b;">
                                                    <span><?= h($arqP['categoria'] ?: 'Projeto') ?></span> · 
                                                    <span><?= $tamFormatado ?></span> · 
                                                    <span class="badge bg-<?= $badgeClassifColor ?>" style="font-size:0.72rem;"><?= h($arqP['classificacao'] ?: 'RECEBIDO') ?></span>
                                                    <?php if (!empty($arqP['item_documento'])): ?>
                                                        · <span style="color:#0369a1;"><i class="fa-solid fa-link"></i> <?= h($arqP['item_documento']) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="arquivo-item-row__actions">
                                            <a class="btn btn-outline-primary btn-sm" href="<?= APP_URL ?>analises-planos/arquivo?id=<?= urlencode($arqP['id']) ?>" target="_blank" title="Abrir e visualizar prancha">
                                                <i class="fa-solid fa-arrow-up-right-from-square"></i> Visualizar
                                            </a>
                                            <a class="btn btn-secondary btn-sm" href="<?= APP_URL ?>analises-planos/arquivo?id=<?= urlencode($arqP['id']) ?>&download=1" title="Baixar arquivo original">
                                                <i class="fa-solid fa-download"></i> Baixar
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-box-card">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                    <p class="mb-0">Nenhum arquivo submetido pelo armador pelo Portal do Cliente até o momento.</p>
                </div>
            <?php endif; ?>
        </section>

        <!-- Todas as Revisões & Upload pelo Analista -->
        <section class="analise-card">
            <div class="analise-card__head-flex">
                <div>
                    <h3><i class="fa-solid fa-upload text-success"></i> Upload de Nova Revisão de Projeto</h3>
                    <p class="text-muted">Anexe arquivos recebidos por e-mail, mídia física ou novas pranchas retificadas pelo projetista.</p>
                </div>
            </div>

            <?php if ($analiseAberta): ?>
                <form method="post" enctype="multipart/form-data" action="<?= APP_URL ?>analises-planos/actions" class="form-upload-revisao">
                    <input type="hidden" name="csrf_token" value="<?= gerarCSRF() ?>">
                    <input type="hidden" name="action" value="adicionar_submissao">
                    <input type="hidden" name="analise_id" value="<?= h($id) ?>">

                    <div class="form-row">
                        <div class="form-group col-3">
                            <label>Categoria da Revisão</label>
                            <select name="categoria" class="form-control form-control-sm">
                                <?php foreach (analisePlanosCategoriasPadrao() as $cat): ?>
                                    <option value="<?= h($cat) ?>"><?= h($cat) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-3">
                            <label>Data de Recebimento *</label>
                            <input type="date" name="recebido_em" value="<?= date('Y-m-d') ?>" required class="form-control form-control-sm">
                        </div>
                        <div class="form-group col-6">
                            <label>Descrição / Observações da Revisão</label>
                            <input name="descricao" placeholder="Ex.: Pranchas revisadas atendendo às exigências do ciclo 1" class="form-control form-control-sm">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Selecionar Arquivos (PDF, DWG, DXF, Word, Excel) *</label>
                        <input type="file" name="arquivos[]" multiple required accept=".pdf,.jpg,.jpeg,.png,.dwg,.dxf,.doc,.docx,.xls,.xlsx" class="form-control">
                        <small class="text-muted">Suporte a uploads simultâneos de pranchas, memoriais e arquivos CAD.</small>
                    </div>

                    <button class="btn btn-primary">
                        <i class="fas fa-upload"></i> Salvar e Registrar Nova Revisão
                    </button>
                </form>
            <?php endif; ?>

            <!-- Classificação Técnica dos Arquivos -->
            <div style="margin-top:20px;">
                <h4 style="font-size:0.95rem; color:#0f172a; margin-bottom:12px;"><i class="fa-solid fa-list-check"></i> Todas as Revisões Registradas</h4>
                <?php if (!$submissoes): ?>
                    <p class="text-muted">Nenhuma revisão cadastrada.</p>
                <?php endif; ?>
                <?php foreach ($submissoes as $s): ?>
                    <div class="revision-block">
                        <div class="revisao-block-header">
                            <strong>Revisão <?= $s['revisao'] ?> · <?= h($s['origem']) ?></strong>
                            <span><?= formatarData($s['recebido_em']) ?> · <?= h($s['usuario_nome'] ?: $s['portal_nome'] ?: 'Origem não informada') ?></span>
                        </div>
                        <?php foreach ($s['arquivos'] as $arq): ?>
                            <div class="file-review">
                                <a class="file-pill" href="<?= APP_URL ?>analises-planos/arquivo?id=<?= urlencode($arq['id']) ?>" target="_blank">
                                    <i class="fas fa-file"></i> <?= h($arq['nome_original']) ?>
                                </a>
                                <span class="badge"><?= h($arq['classificacao']) ?></span>
                                <?php if ($analiseAberta): ?>
                                    <form method="post" action="<?= APP_URL ?>analises-planos/actions" class="form-classificar-inline">
                                        <input type="hidden" name="csrf_token" value="<?= gerarCSRF() ?>">
                                        <input type="hidden" name="action" value="classificar_arquivo">
                                        <input type="hidden" name="analise_id" value="<?= h($id) ?>">
                                        <input type="hidden" name="arquivo_id" value="<?= h($arq['id']) ?>">
                                        <select name="item_id" class="form-control form-control-sm" style="max-width:180px;">
                                            <option value="">Sem item</option>
                                            <?php foreach ($itens as $item): ?>
                                                <option value="<?= h($item['id']) ?>" <?= $arq['item_id'] === $item['id'] ? 'selected' : '' ?>><?= h($item['documento']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <select name="classificacao" class="form-control form-control-sm" style="max-width:140px;">
                                            <?php foreach (['ACEITO','SUBSTITUIDO','REJEITADO'] as $cl): ?>
                                                <option <?= $arq['classificacao'] === $cl ? 'selected' : '' ?>><?= $cl ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input name="justificativa" value="<?= h($arq['justificativa_classificacao']) ?>" placeholder="Justificativa (obrigatória ao rejeitar)" class="form-control form-control-sm">
                                        <button class="btn btn-secondary btn-sm">Classificar</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <!-- ABA 3: RELATÓRIOS TÉCNICOS RAP & LICENÇA -->
    <div id="pane-pareceres" class="analise-tab-pane <?= $abaAtiva === 'pareceres' ? 'active' : '' ?>">
        <section class="analise-card" id="pareceres">
            <div class="analise-card__head-flex">
                <div>
                    <h3><i class="fa-solid fa-file-signature text-success"></i> Relatórios Técnicos de Análise de Planos (RAP)</h3>
                    <p class="text-muted">Emissão, assinatura técnica com CREA/Token e publicação de relatórios por ciclo da NORMAM-202.</p>
                </div>
            </div>

            <!-- Formulário de Preparo do Parecer / Relatório de Ciclo -->
            <?php if ($analiseAberta): ?>
                <div class="card-preparar-parecer">
                    <h4 style="margin-bottom:14px; font-size:1.05rem; color:#0f172a;">
                        <i class="fa-solid fa-file-pen text-primary"></i> Preparar Novo Relatório de Ciclo (RAP)
                    </h4>
                    <form method="post" action="<?= APP_URL ?>analises-planos/actions" class="form-padrao">
                        <input type="hidden" name="csrf_token" value="<?= gerarCSRF() ?>">
                        <input type="hidden" name="action" value="criar_parecer">
                        <input type="hidden" name="analise_id" value="<?= h($id) ?>">

                        <div class="form-row">
                            <div class="form-group col-4">
                                <label class="form-label-bold">Resultado do Ciclo *</label>
                                <select name="resultado" class="form-control form-control-sm" required>
                                    <option value="EXIGENCIAS">Exigências pendentes (Ciclo Preliminar)</option>
                                    <option value="APROVADO">Conclusivo — Aprovado sem exigências</option>
                                    <option value="REPROVADO">Reprovado</option>
                                </select>
                                <small class="text-muted">Para "Aprovado", todas as exigências devem ser baixadas como "Cumprida".</small>
                            </div>

                            <div class="form-group col-4">
                                <label class="form-label-bold">Revisão Documental Analisada *</label>
                                <select name="submissao_id" class="form-control form-control-sm" required>
                                    <option value="">-- Selecione a revisão --</option>
                                    <?php foreach ($submissoes as $s): ?>
                                        <option value="<?= h($s['id']) ?>">Revisão <?= $s['revisao'] ?> · <?= formatarData($s['recebido_em']) ?> (<?= h($s['origem']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group col-4">
                                <label class="form-label-bold">Resumo Executivo *</label>
                                <textarea name="resumo" required rows="2" class="form-control" placeholder="Síntese técnica da conferência documental deste ciclo..."></textarea>
                            </div>
                        </div>

                        <?php if ($exigencias): ?>
                            <div style="margin:16px 0;">
                                <h5 style="font-size:0.92rem; color:#0f172a; margin-bottom:8px;">
                                    <i class="fa-solid fa-clipboard-check text-success"></i> Baixa Guiada das Exigências Técnicas
                                </h5>
                                <div class="table-responsive">
                                    <table class="tabela-exigencias-moderna">
                                        <thead>
                                            <tr>
                                                <th>Exigência Técnica</th>
                                                <th style="width:160px;">Resultado do Ciclo</th>
                                                <th>Manifestação Técnica do Analista</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($exigencias as $ex): ?>
                                                <tr>
                                                    <td style="font-size:0.86rem; color:#1e293b;">
                                                        <strong style="color:#0369a1;"><?= h($ex['categoria']) ?></strong><br>
                                                        <?= h($ex['descricao']) ?>
                                                    </td>
                                                    <td style="vertical-align:top;">
                                                        <select name="baixa_resultado[<?= h($ex['id']) ?>]" class="form-control form-control-sm" required>
                                                            <option value="NAO_CUMPRIDA">Não cumprida</option>
                                                            <option value="PARCIAL">Parcial</option>
                                                            <option value="CUMPRIDA">Cumprida</option>
                                                        </select>
                                                    </td>
                                                    <td style="vertical-align:top;">
                                                        <textarea name="baixa_manifestacao[<?= h($ex['id']) ?>]" required rows="2" class="form-control form-control-sm" placeholder="Indique a evidência técnica ou prancha que atende/não atende..."></textarea>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="form-group">
                            <label class="form-label-bold">Conclusão Técnica do Analista *</label>
                            <textarea name="conclusao" required rows="2" class="form-control" placeholder="Parecer conclusivo sobre a conformidade das plantas com a NORMAM-202/DPC..."></textarea>
                        </div>

                        <button class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Preparar Relatório Técnico do Ciclo (RAP)
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Histórico de Pareceres Emitidos -->
            <div style="margin-top:24px;">
                <h4 style="font-size:1rem; color:#0f172a; margin-bottom:14px;"><i class="fa-solid fa-clock-rotate-left"></i> Histórico de Relatórios Emitidos</h4>
                <?php if (empty($pareceres)): ?>
                    <p class="text-muted">Nenhum relatório técnico emitido para este processo até o momento.</p>
                <?php endif; ?>
                <?php foreach ($pareceres as $p): ?>
                    <article class="parecer-row">
                        <div>
                            <strong><i class="fa-solid fa-file-signature text-success"></i> <?= h($p['numero'] ?: ('Relatório v' . $p['versao'])) ?> · <?= h($p['finalidade'] ?: $p['resultado']) ?></strong>
                            <span><?= h($p['status']) ?> · <?= formatarDataCompleta($p['criado_em']) ?> por <?= h($p['criador_nome']) ?></span>
                            <?php if ($p['devolvido_motivo']): ?>
                                <small class="text-danger"><i class="fa-solid fa-triangle-exclamation"></i> Devolvido: <?= h($p['devolvido_motivo']) ?></small>
                            <?php endif; ?>
                        </div>
                        <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                            <a class="btn btn-secondary btn-sm" target="_blank" href="<?= APP_URL ?>analises-planos/parecer-pdf?id=<?= urlencode($p['id']) ?>">
                                <i class="fas fa-file-pdf text-danger"></i> PDF Oficial
                            </a>
                            <?php if ($p['status'] === 'AGUARDANDO_ASSINATURA_ANALISTA' && $cargo === 'ANALISTA' && $p['criado_por'] === $usuario): ?>
                                <form method="post" action="<?= APP_URL ?>analises-planos/actions" style="display:inline">
                                    <input type="hidden" name="csrf_token" value="<?= gerarCSRF() ?>">
                                    <input type="hidden" name="action" value="assinar_parecer">
                                    <input type="hidden" name="analise_id" value="<?= h($id) ?>">
                                    <input type="hidden" name="parecer_id" value="<?= h($p['id']) ?>">
                                    <button class="btn btn-success btn-sm"><i class="fas fa-signature"></i> Assinar Tecnicamente</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($p['status'] === 'AGUARDANDO_APROVACAO_ADMIN' && $cargo === 'ADMIN'): ?>
                                <form method="post" action="<?= APP_URL ?>analises-planos/actions" style="display:inline-flex; gap:6px; align-items:center;">
                                    <input type="hidden" name="csrf_token" value="<?= gerarCSRF() ?>">
                                    <input type="hidden" name="action" value="publicar">
                                    <input type="hidden" name="analise_id" value="<?= h($id) ?>">
                                    <input type="hidden" name="parecer_id" value="<?= h($p['id']) ?>">
                                    <button class="btn btn-success btn-sm"><i class="fa-solid fa-check-double"></i> Validar e Publicar</button>
                                    <input name="motivo" placeholder="Motivo de devolução" class="form-control form-control-sm" style="width:180px;">
                                    <button name="devolver" value="1" class="btn btn-warning btn-sm">Devolver</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <?php if ($licenca): ?>
                <div class="alert alert-success" style="margin-top:20px; border-radius:8px;">
                    <strong><i class="fa-solid fa-certificate"></i> Licença Oficial Emitida: <?= h($licenca['numero_lc']) ?></strong>
                    (<?= h($licenca['tipo_licenca']) ?> · <?= h($licenca['status']) ?>)
                    <a href="<?= APP_URL ?>documentacao/lc/form?id=<?= urlencode($licenca['id']) ?>" class="btn btn-outline-success btn-sm" style="margin-left:12px;">
                        Abrir Licença de Construção
                    </a>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <!-- ABA 4: ENQUADRAMENTO & CARACTERÍSTICAS NAVAIS -->
    <div id="pane-enquadramento" class="analise-tab-pane <?= $abaAtiva === 'enquadramento' ? 'active' : '' ?>">
        <section class="analise-card">
            <div class="analise-card__head-flex">
                <div>
                    <h3><i class="fa-solid fa-ship text-success"></i> Características Principais & Enquadramento Técnico</h3>
                    <p class="text-muted">Os parâmetros informados abaixo compõem o cabeçalho e a Seção 1 do laudo oficial da Capitania dos Portos.</p>
                </div>
            </div>

            <form method="post" action="<?= APP_URL ?>analises-planos/actions" class="form-padrao">
                <input type="hidden" name="csrf_token" value="<?= gerarCSRF() ?>">
                <input type="hidden" name="action" value="salvar">
                <input type="hidden" name="id" value="<?= h($id) ?>">

                <div class="form-row">
                    <div class="form-group col-3">
                        <label class="form-label-bold">Tipo de Processo *</label>
                        <select name="tipo_processo" required <?= $tecnicoEditavel ? '' : 'disabled' ?> class="form-control">
                            <?php foreach (analisePlanosTiposPermitidos() as $v): ?>
                                <option value="<?= $v ?>" <?= $a['tipo_processo'] === $v ? 'selected' : '' ?>><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group col-3">
                        <label class="form-label-bold">Norma Aplicável *</label>
                        <select name="enquadramento" required <?= $tecnicoEditavel ? '' : 'disabled' ?> class="form-control">
                            <?php foreach (analisePlanosNormasPermitidas() as $v): ?>
                                <option value="<?= $v ?>" <?= $a['enquadramento'] === $v ? 'selected' : '' ?>><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group col-3">
                        <label class="form-label-bold">Classe de Certificação</label>
                        <input value="<?= h($a['classe_certificacao']) ?>" disabled class="form-control">
                    </div>

                    <div class="form-group col-3">
                        <label class="form-label-bold">Arqueação Bruta (AB)</label>
                        <input type="number" step="0.01" min="0" name="arqueacao_bruta" value="<?= h($a['arqueacao_bruta']) ?>" <?= $tecnicoEditavel ? '' : 'disabled' ?> class="form-control" placeholder="Ex.: 45.20">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-3">
                        <label class="form-label-bold">Nº de Passageiros</label>
                        <input type="number" min="0" name="numero_passageiros" value="<?= h($a['numero_passageiros']) ?>" <?= $tecnicoEditavel ? '' : 'disabled' ?> class="form-control">
                    </div>

                    <div class="form-group col-3">
                        <label class="form-label-bold">Propulsão</label>
                        <select name="possui_propulsao" <?= $tecnicoEditavel ? '' : 'disabled' ?> class="form-control">
                            <option value="">A definir</option>
                            <option value="1" <?= $a['possui_propulsao'] === '1' ? 'selected' : '' ?>>Com propulsão (Motor)</option>
                            <option value="0" <?= $a['possui_propulsao'] === '0' ? 'selected' : '' ?>>Sem propulsão (Chata/Balsa)</option>
                        </select>
                    </div>

                    <div class="form-group col-3">
                        <label class="form-label-bold">Embarcação Classificada</label>
                        <select name="embarcacao_classificada" <?= $tecnicoEditavel ? '' : 'disabled' ?> class="form-control">
                            <option value="">A definir</option>
                            <option value="1" <?= $a['embarcacao_classificada'] === '1' ? 'selected' : '' ?>>Sim (Sociedade Classificadora)</option>
                            <option value="0" <?= $a['embarcacao_classificada'] === '0' ? 'selected' : '' ?>>Não</option>
                        </select>
                    </div>

                    <div class="form-group col-3">
                        <label class="form-label-bold">Construção Concluída</label>
                        <select name="construcao_concluida" <?= $tecnicoEditavel ? '' : 'disabled' ?> class="form-control">
                            <option value="">A definir</option>
                            <option value="1" <?= $a['construcao_concluida'] === '1' ? 'selected' : '' ?>>Sim</option>
                            <option value="0" <?= $a['construcao_concluida'] === '0' ? 'selected' : '' ?>>Não (Em projeto/obra)</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-6">
                        <label class="form-label-bold">Objeto do Processo *</label>
                        <input name="objeto" required value="<?= h($a['objeto']) ?>" <?= $tecnicoEditavel ? '' : 'disabled' ?> class="form-control">
                    </div>

                    <div class="form-group col-3">
                        <label class="form-label-bold">Área de Navegação</label>
                        <input name="tipo_navegacao" value="<?= h($a['tipo_navegacao']) ?>" <?= $tecnicoEditavel ? '' : 'disabled' ?> class="form-control" placeholder="Ex.: Interior Área 1 e 2">
                    </div>

                    <div class="form-group col-3">
                        <label class="form-label-bold">Nº do Casco</label>
                        <input name="numero_casco" value="<?= h($a['numero_casco']) ?>" <?= $tecnicoEditavel ? '' : 'disabled' ?> class="form-control" placeholder="Nº do casco">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-4">
                        <label class="form-label-bold">Estaleiro Construtor</label>
                        <input name="estaleiro" value="<?= h($a['estaleiro']) ?>" <?= $tecnicoEditavel ? '' : 'disabled' ?> class="form-control" placeholder="Nome do estaleiro">
                    </div>

                    <div class="form-group col-4">
                        <label class="form-label-bold">Autor do Projeto (Engenheiro Naval)</label>
                        <input name="responsavel_projeto_nome" value="<?= h($a['responsavel_projeto_nome']) ?>" <?= $tecnicoEditavel ? '' : 'disabled' ?> class="form-control" placeholder="Nome do engenheiro autor">
                    </div>

                    <div class="form-group col-2">
                        <label class="form-label-bold">Registro / CREA</label>
                        <input name="responsavel_projeto_registro" value="<?= h($a['responsavel_projeto_registro']) ?>" <?= $tecnicoEditavel ? '' : 'disabled' ?> class="form-control" placeholder="CREA">
                    </div>

                    <div class="form-group col-2">
                        <label class="form-label-bold">Nº da ART</label>
                        <input name="art_numero" value="<?= h($a['art_numero']) ?>" <?= $tecnicoEditavel ? '' : 'disabled' ?> class="form-control" placeholder="ART">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label-bold">Observações Técnicas Gerais</label>
                    <textarea name="observacoes" rows="3" <?= $tecnicoEditavel ? '' : 'disabled' ?> class="form-control" placeholder="Informações de projeto, particularidades da bacia hidrográfica..."><?= h($a['observacoes']) ?></textarea>
                </div>

                <?php if ($tecnicoEditavel): ?>
                    <div class="form-actions">
                        <button class="btn btn-primary"><i class="fas fa-save"></i> Salvar Enquadramento e Características</button>
                    </div>
                <?php endif; ?>
            </form>
        </section>
    </div>

    <!-- ABA 5: VISTORIA DE CAMPO & TRÂMITE DOCUMENTAL -->
    <div id="pane-vistoria_tramite" class="analise-tab-pane <?= $abaAtiva === 'vistoria_tramite' ? 'active' : '' ?>">
        <!-- Vistoria de Campo Vinculada -->
        <section class="analise-card" style="border-left: 4px solid #087653;">
            <div class="analise-card__head-flex">
                <div>
                    <h3><i class="fa-solid fa-ship text-success"></i> Vistoria Técnica de Campo (A Bordo)</h3>
                    <p class="text-muted">Confronte fotos, medições e anteparas verificadas a bordo pelo Vistoriador com os planos analisados.</p>
                </div>
            </div>

            <?php if (!$vistoriasVinculadas): ?>
                <div class="empty-box-card">
                    <i class="fa-solid fa-clipboard-question"></i>
                    <p class="mb-0">Nenhuma vistoria física de campo vinculada a esta embarcação no momento.</p>
                </div>
            <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:12px; margin-top:14px;">
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
                        <div class="vistoria-card-box">
                            <div>
                                <div style="font-weight:700; font-size:0.95rem; margin-bottom:4px;">
                                    <?= !empty($vItem['vistoria_numero']) ? h($vItem['vistoria_numero']) : 'Ordem de Campo' ?>
                                    <span class="badge bg-<?= $badgeVistColor ?>" style="font-size:0.76rem; margin-left:6px;"><?= $statusVistLabel ?></span>
                                </div>
                                <div style="font-size:0.84rem; color:#64748b;">
                                    <i class="fa-solid fa-user-gear"></i> Vistoriador: <strong><?= h($vItem['vistoriador_nome'] ?: 'Ainda não atribuído') ?></strong>
                                    · <i class="fa-solid fa-calendar"></i> Data: <strong><?= !empty($vItem['data_vistoria']) ? date('d/m/Y', strtotime($vItem['data_vistoria'])) : 'A definir' ?></strong>
                                    <?php if (!empty($vItem['local_vistoria']) || !empty($vItem['agendamento_local'])): ?>
                                        · <i class="fa-solid fa-location-dot"></i> Local: <?= h($vItem['local_vistoria'] ?: $vItem['agendamento_local']) ?>
                                    <?php endif; ?>
                                </div>
                                <div style="font-size:0.8rem; margin-top:6px; display:flex; gap:14px; color:#475569;">
                                    <span><i class="fa-solid fa-camera"></i> <strong><?= (int)$vItem['total_fotos'] ?></strong> foto(s) de bordo</span>
                                    <span><i class="fa-solid fa-triangle-exclamation"></i> <strong><?= (int)$vItem['total_exigencias'] ?></strong> exigência(s) de campo <?= (int)$vItem['exigencias_pendentes'] > 0 ? '(' . (int)$vItem['exigencias_pendentes'] . ' pendentes)' : '' ?></span>
                                </div>
                            </div>
                            <div style="display:flex; gap:8px; align-items:center;">
                                <?php if (!empty($vItem['vistoria_id'])): ?>
                                    <a class="btn btn-secondary btn-sm" target="_blank" href="<?= APP_URL ?>vistorias/relatorio-pdf?id=<?= urlencode($vItem['vistoria_id']) ?>" title="Baixar Relatório Oficial de Vistoria">
                                        <i class="fa-solid fa-file-pdf text-danger"></i> PDF RTV
                                    </a>
                                    <a class="btn btn-primary btn-sm" href="<?= APP_URL ?>vistorias/relatorio?agendamento_id=<?= urlencode((string)$vItem['agendamento_id']) ?>&vistoria_id=<?= urlencode((string)$vItem['vistoria_id']) ?>">
                                        <i class="fa-solid fa-clipboard-check"></i> Ver Vistoria & Fotos
                                    </a>
                                <?php elseif (!empty($vItem['agendamento_id'])): ?>
                                    <a class="btn btn-outline-secondary btn-sm" href="<?= APP_URL ?>agendamentos/form?id=<?= urlencode($vItem['agendamento_id']) ?>">
                                        <i class="fa-solid fa-calendar"></i> Ver Agendamento
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- Tramitação Documental / Dossiês de Protocolo -->
        <?php if (podeAcessar('protocolos_documentais')): ?>
            <section class="analise-card">
                <div class="analise-card__head-flex">
                    <div>
                        <h3><i class="fa-solid fa-arrow-right-arrow-left text-primary"></i> Tramitação Documental na Capitania / Órgão</h3>
                        <p class="text-muted">Acompanhamento do processo protocolado na Capitania dos Portos (SISAP).</p>
                    </div>
                    <a class="btn btn-secondary btn-sm" href="<?= APP_URL ?>protocolos/form?analise_id=<?= urlencode($id) ?>&embarcacao_id=<?= urlencode($a['embarcacao_id']) ?>">
                        <i class="fas fa-plus"></i> Abrir Protocolo deste Processo
                    </a>
                </div>

                <?php foreach ($protocolos as $prot): ?>
                    <p style="padding:8px 12px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; margin-bottom:8px;">
                        <a href="<?= APP_URL ?>protocolos/form?id=<?= urlencode($prot['id']) ?>">
                            <strong><?= h($prot['numero']) ?></strong> · <?= h($prot['assunto']) ?>
                        </a>
                        <span class="badge"><?= h($prot['status']) ?></span>
                    </p>
                <?php endforeach; ?>
                <?php if (!$protocolos): ?>
                    <p class="text-muted">Nenhum dossiê de protocolo vinculado até o momento.</p>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <!-- Agenda Técnica do Processo -->
        <?php if ($podeAgenda && !in_array($a['status'], ['CONCLUIDA','REPROVADA','CANCELADA'], true)): ?>
            <section class="analise-card">
                <h3><i class="fa-solid fa-calendar-check text-info"></i> Agenda & Prazos da Análise Técnica</h3>
                <form method="post" action="<?= APP_URL ?>analises-planos/actions" class="form-inline-agenda">
                    <input type="hidden" name="csrf_token" value="<?= gerarCSRF() ?>">
                    <input type="hidden" name="action" value="agendar">
                    <input type="hidden" name="analise_id" value="<?= h($id) ?>">

                    <div class="form-group">
                        <label>Analista Responsável *</label>
                        <select name="analista_id" required <?= ($iniciada && $cargo !== 'ADMIN') ? 'disabled' : '' ?> class="form-control form-control-sm">
                            <option value="">Selecione</option>
                            <?php foreach ($analistas as $u): ?>
                                <option value="<?= h($u['id']) ?>" <?= $a['analista_id'] === $u['id'] ? 'selected' : '' ?>><?= h($u['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($iniciada && $cargo !== 'ADMIN'): ?>
                            <input type="hidden" name="analista_id" value="<?= h($a['analista_id']) ?>">
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Prazo de Entrega *</label>
                        <input type="datetime-local" name="prazo_agendado_em" value="<?= !empty($a['prazo_agendado_em']) ? date('Y-m-d\TH:i', strtotime($a['prazo_agendado_em'])) : '' ?>" required class="form-control form-control-sm">
                    </div>

                    <div class="form-group" style="flex:2;">
                        <label>Motivo <?= !empty($a['prazo_agendado_em']) ? '*' : '' ?></label>
                        <input name="motivo" maxlength="500" placeholder="<?= !empty($a['prazo_agendado_em']) ? 'Obrigatório no reagendamento' : 'Agendamento inicial' ?>" class="form-control form-control-sm">
                    </div>

                    <button class="btn btn-primary btn-sm">
                        <i class="fas fa-calendar-check"></i> <?= !empty($a['prazo_agendado_em']) ? 'Reagendar' : 'Agendar' ?>
                    </button>
                </form>

                <?php if ($agendaHistorico): ?>
                    <div class="timeline" style="margin-top:16px;">
                        <?php foreach ($agendaHistorico as $ag): ?>
                            <div>
                                <strong><?= h($ag['acao']) ?> · <?= formatarDataCompleta($ag['prazo_novo_em']) ?></strong>
                                <span><?= h($ag['autor_nome']) ?> · <?= formatarDataCompleta($ag['criado_em']) ?></span>
                                <p><?= h($ag['motivo']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <!-- Histórico Auditável de Eventos -->
        <section class="analise-card">
            <h3><i class="fa-solid fa-clock-rotate-left text-secondary"></i> Histórico Auditável de Eventos</h3>
            <div class="timeline">
                <?php foreach ($historico as $h): ?>
                    <div>
                        <strong><?= h($h['evento']) ?></strong>
                        <span><?= h($h['usuario_nome']) ?> · <?= formatarDataCompleta($h['criado_em']) ?></span>
                        <?php if ($h['detalhe']): ?>
                            <p><?= h($h['detalhe']) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</div>

<!-- Modal Seletor de Referências NORMAM -->
<div id="modalBancoNormam" class="modal-normam-overlay" style="display:none;">
    <div class="modal-normam-content">
        <div class="modal-normam-header">
            <div>
                <h3 style="margin:0; font-size:1.15rem; color:#0f172a;"><i class="fa-solid fa-book-bookmark text-success"></i> Banco de Referências NORMAM-202/DPC</h3>
                <small style="color:#64748b;">Selecione uma exigência padronizada da Autoridade Marítima para aplicar com 1 clique.</small>
            </div>
            <button type="button" class="btn-fechar-modal" onclick="fecharModalBancoNormam()">&times;</button>
        </div>
        
        <div class="modal-normam-filtros">
            <div style="flex:1; min-width:200px;">
                <input type="text" id="modalBuscaNormam" placeholder="Buscar por texto, anexo, item ou artigo..." class="form-control form-control-sm" oninput="filtrarNormasModal()">
            </div>
            <div style="width:260px;">
                <select id="modalCategoriaNormam" class="form-control form-control-sm" onchange="filtrarNormasModal()">
                    <option value="">Todas as Categorias</option>
                    <?php foreach ($categoriasNormam as $cNome): ?>
                        <option value="<?= h($cNome) ?>"><?= h($cNome) ?></option>
                    <?php endforeach; ?>
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
/* Estilos da Página de Análise Técnica de Planos (Design System Amazon Naval) */
.analise-planos-page {
    display: flex;
    flex-direction: column;
    gap: 18px;
    padding-bottom: 40px;
}

/* Hero Card */
.analise-hero-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.04);
}
.analise-hero-main {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 18px;
    padding-bottom: 20px;
    border-bottom: 1px solid #f1f5f9;
}
.analise-hero-info {
    flex: 1;
    min-width: 280px;
}
.analise-hero-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 8px;
}
.analise-tag-processo {
    font-size: 0.74rem;
    font-weight: 800;
    color: #0d4a40;
    background: #e6f4f1;
    padding: 3px 8px;
    border-radius: 6px;
    letter-spacing: 0.04em;
}
.analise-tag-tipo {
    font-size: 0.74rem;
    font-weight: 700;
    color: #0369a1;
    background: #e0f2fe;
    padding: 3px 8px;
    border-radius: 6px;
}
.analise-hero-title {
    font-size: 1.65rem;
    font-weight: 800;
    color: #0f172a;
    margin: 0 0 6px 0;
}
.analise-hero-sub {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
    font-size: 0.88rem;
    color: #475569;
}
.analise-hero-sub span {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.prazo-alerta-vencido {
    color: #dc2626 !important;
    font-weight: 700;
}
.prazo-alerta-hoje {
    color: #d97706 !important;
    font-weight: 700;
}
.analise-hero-actions {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}

/* KPI Strip */
.analise-kpi-strip {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    margin-top: 18px;
}
.kpi-box {
    background: #f8fafc;
    border: 1px solid #edf2f7;
    border-radius: 10px;
    padding: 12px 14px;
    display: flex;
    align-items: center;
    gap: 12px;
    transition: all 0.15s ease;
}
.kpi-box:hover {
    border-color: #cbd5e1;
    background: #f1f5f9;
}
.kpi-box__icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    flex-shrink: 0;
}
.kpi-box__icon.is-status { background: #e0f2fe; color: #0284c7; }
.kpi-box__icon.is-files { background: #e0f2fe; color: #0369a1; }
.kpi-box__icon.is-exigencias { background: #fef3c7; color: #d97706; }
.kpi-box__icon.is-reports { background: #dcfce7; color: #16a34a; }

.kpi-box__content {
    display: flex;
    flex-direction: column;
}
.kpi-box__content small {
    font-size: 0.72rem;
    color: #64748b;
    font-weight: 600;
    text-transform: uppercase;
}
.kpi-box__content strong {
    font-size: 1.05rem;
    color: #0f172a;
    font-weight: 700;
    line-height: 1.2;
}
.kpi-box__content span {
    font-size: 0.74rem;
    color: #94a3b8;
    margin-top: 2px;
}

/* Banner Iniciar Análise */
.banner-iniciar-analise {
    background: linear-gradient(100deg, #e6f4f1 0%, #ffffff 100%);
    border: 1px solid #99d5c8;
    border-left: 5px solid #0d4a40;
    border-radius: 12px;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    flex-wrap: wrap;
    box-shadow: 0 2px 8px rgba(13, 74, 64, 0.08);
}
.banner-iniciar-icon {
    width: 46px;
    height: 46px;
    border-radius: 50%;
    background: #0d4a40;
    color: #5eead4;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    flex-shrink: 0;
}
.banner-iniciar-content {
    flex: 1;
    min-width: 260px;
}
.banner-iniciar-content strong {
    font-size: 1rem;
    color: #0d4a40;
    display: block;
    margin-bottom: 3px;
}
.banner-iniciar-content p {
    font-size: 0.85rem;
    color: #334155;
    margin: 0;
    line-height: 1.35;
}
.btn-iniciar-glow {
    box-shadow: 0 4px 14px rgba(13, 74, 64, 0.3) !important;
    font-weight: 700 !important;
    padding: 10px 18px !important;
}

/* Barra de Abas */
.analise-tabs-bar {
    display: flex;
    gap: 8px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 6px;
    overflow-x: auto;
    scrollbar-width: thin;
}
.analise-tab-btn {
    border: none;
    background: transparent;
    padding: 10px 18px;
    border-radius: 8px;
    font-size: 0.88rem;
    font-weight: 600;
    color: #64748b;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.18s ease;
}
.analise-tab-btn:hover {
    color: #0f172a;
    background: #f1f5f9;
}
.analise-tab-btn.active {
    background: #0d4a40;
    color: #ffffff;
    box-shadow: 0 2px 6px rgba(13, 74, 64, 0.25);
}
.analise-tab-btn.active .badge {
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}

/* Panes */
.analise-tab-pane {
    display: none;
}
.analise-tab-pane.active {
    display: block;
}

/* Cards Gerais */
.analise-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 22px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.analise-card__head-flex {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 14px;
    margin-bottom: 18px;
    padding-bottom: 14px;
    border-bottom: 1px solid #f1f5f9;
}
.analise-card__head-flex h3 {
    font-size: 1.18rem;
    color: #0f172a;
    font-weight: 700;
    margin: 0 0 4px 0;
}
.analise-card__head-flex p {
    font-size: 0.84rem;
    color: #64748b;
    margin: 0;
    line-height: 1.35;
}
.analise-card__actions-head {
    display: flex;
    gap: 8px;
    align-items: center;
}

/* Tabela Moderna de Exigências */
.tabela-exigencias-moderna {
    width: 100%;
    border-collapse: collapse;
}
.tabela-exigencias-moderna th {
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    color: #64748b;
    font-size: 0.75rem;
    text-transform: uppercase;
    padding: 10px 12px;
    text-align: left;
}
.tabela-exigencias-moderna td {
    padding: 12px;
    border-bottom: 1px solid #f1f5f9;
    font-size: 0.88rem;
}
.exigencias-empty-cell {
    padding: 40px 20px !important;
    text-align: center;
}
.empty-exigencias-box i {
    font-size: 2.2rem;
    margin-bottom: 10px;
    display: inline-block;
}
.empty-exigencias-box h4 {
    font-size: 1.05rem;
    color: #0f172a;
    margin: 0 0 6px 0;
}
.empty-exigencias-box p {
    font-size: 0.85rem;
    color: #64748b;
    margin: 0 auto;
    max-width: 540px;
    line-height: 1.4;
}

/* Card Nova Exigência */
.card-nova-exigencia {
    margin-top: 20px;
    padding: 18px 20px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
}
.card-nova-exigencia__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 12px;
}
.form-label-bold {
    font-size: 0.82rem;
    font-weight: 700;
    color: #1e293b;
    display: block;
    margin-bottom: 4px;
}

/* Category Pills Row */
.category-pills-row {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
    padding: 8px 12px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    margin-bottom: 10px;
}
.pills-label {
    font-size: 0.74rem;
    font-weight: 700;
    color: #0d4a40;
    margin-right: 4px;
}
.chip-category-btn {
    border: 1px solid #cbd5e1;
    background: #f8fafc;
    color: #334155;
    padding: 3px 10px;
    border-radius: 14px;
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
}
.chip-category-btn:hover {
    background: #e6f4f1;
    border-color: #0d4a40;
    color: #0d4a40;
}
.chip-category-btn.is-selected {
    background: #0d4a40;
    border-color: #0d4a40;
    color: #ffffff;
}

.quick-normam-refs {
    margin-top: 4px;
    font-size: 0.75rem;
}
.quick-normam-refs a {
    color: #0369a1;
    text-decoration: none;
    font-weight: 600;
}
.quick-normam-refs a:hover {
    text-decoration: underline;
}

.card-nova-exigencia__footer {
    display: flex;
    justify-content: flex-end;
    margin-top: 12px;
}
.btn-salvar-exigencia {
    font-weight: 700 !important;
    padding: 8px 18px !important;
}

/* Arquivos */
.revisoes-portal-list {
    display: flex;
    flex-direction: column;
    gap: 14px;
    margin-top: 14px;
}
.revisao-card {
    border: 1px solid #e0f2fe;
    border-radius: 8px;
    padding: 14px;
    background: #f8fafc;
}
.revisao-card__head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 10px;
    border-bottom: 1px solid #e2e8f0;
    padding-bottom: 8px;
}
.arquivos-grid {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.arquivo-item-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 12px;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    flex-wrap: wrap;
    gap: 8px;
}
.arquivo-item-row__left {
    display: flex;
    align-items: center;
    gap: 10px;
}
.arquivo-item-row__actions {
    display: flex;
    gap: 8px;
    align-items: center;
}
.empty-box-card {
    padding: 30px;
    text-align: center;
    color: #64748b;
    background: #f8fafc;
    border-radius: 8px;
    border: 1px dashed #cbd5e1;
}
.empty-box-card i {
    font-size: 1.8rem;
    margin-bottom: 8px;
    display: block;
    color: #94a3b8;
}

/* Vistoria Box */
.vistoria-card-box {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 14px;
    background: #ffffff;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}

/* Formulário de Agenda Inline */
.form-inline-agenda {
    display: flex;
    align-items: flex-end;
    gap: 12px;
    flex-wrap: wrap;
    margin-top: 14px;
    padding: 14px;
    background: #f8fafc;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}
.form-inline-agenda .form-group {
    flex: 1;
    min-width: 180px;
    margin-bottom: 0;
}

/* Modal Normam */
.modal-normam-overlay {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(15, 23, 42, 0.65);
    z-index: 99999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
    backdrop-filter: blur(3px);
}
.modal-normam-content {
    background: #fff;
    border-radius: 12px;
    width: 100%;
    max-width: 920px;
    max-height: 88vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 40px rgba(0,0,0,0.3);
    overflow: hidden;
}
.modal-normam-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    border-bottom: 1px solid #e2e8f0;
    background: #f8fafc;
}
.btn-fechar-modal {
    background: none;
    border: none;
    font-size: 1.8rem;
    line-height: 1;
    color: #64748b;
    cursor: pointer;
    padding: 0 6px;
}
.btn-fechar-modal:hover { color: #0f172a; }
.modal-normam-filtros {
    display: flex;
    gap: 12px;
    padding: 12px 20px;
    background: #f1f5f9;
    border-bottom: 1px solid #e2e8f0;
    flex-wrap: wrap;
}
.modal-normam-lista {
    flex: 1;
    overflow-y: auto;
    padding: 16px 20px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.normam-item-card {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 12px 14px;
    background: #fff;
    transition: all .15s ease;
}
.normam-item-card:hover {
    border-color: #10b981;
    box-shadow: 0 2px 8px rgba(16,185,129,0.12);
}
.normam-item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 6px;
    flex-wrap: wrap;
    gap: 6px;
}
.normam-item-cat {
    font-size: 0.75rem;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 4px;
    background: #d1fae5;
    color: #065f46;
}
.normam-item-ref {
    font-size: 0.8rem;
    font-weight: 600;
    color: #0284c7;
}
.normam-item-desc {
    font-size: 0.86rem;
    color: #334155;
    margin: 0 0 8px 0;
    line-height: 1.4;
}
.normam-item-actions {
    display: flex;
    justify-content: flex-end;
}
.modal-normam-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 20px;
    border-top: 1px solid #e2e8f0;
    background: #f8fafc;
}

/* Timeline & Pareceres */
.card-preparar-parecer {
    padding: 18px 20px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    margin-bottom: 20px;
}
.parecer-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 16px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #ffffff;
    margin-bottom: 10px;
    flex-wrap: wrap;
    gap: 12px;
}
.parecer-row > div:first-child {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.timeline > div {
    border-left: 3px solid #0d4a40;
    padding: 2px 0 14px 14px;
    position: relative;
}
.timeline > div::before {
    content: '';
    position: absolute;
    left: -6px;
    top: 4px;
    width: 9px;
    height: 9px;
    border-radius: 50%;
    background: #0d4a40;
}
.timeline span {
    display: block;
    font-size: 0.8rem;
    color: #64748b;
}
.timeline p {
    margin: 4px 0 0 0;
    font-size: 0.85rem;
    color: #334155;
}

@media (max-width: 992px) {
    .analise-kpi-strip { grid-template-columns: 1fr 1fr; }
    .analise-hero-main { flex-direction: column; align-items: flex-start; }
    .analise-hero-actions { width: 100%; justify-content: flex-start; }
}
@media (max-width: 600px) {
    .analise-kpi-strip { grid-template-columns: 1fr; }
    .analise-tabs-bar { padding: 4px; }
    .analise-tab-btn { padding: 8px 12px; font-size: 0.8rem; }
}
</style>

<script>
// Controle das Abas da Análise
function trocarAbaAnalise(abaId) {
    document.querySelectorAll('.analise-tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.analise-tab-pane').forEach(p => p.classList.remove('active'));

    const btn = document.querySelector(`.analise-tab-btn[onclick*="${abaId}"]`);
    const pane = document.getElementById(`pane-${abaId}`);
    if (btn) btn.classList.add('active');
    if (pane) pane.classList.add('active');

    // Atualizar URL silenciosamente para permitir reload na mesma aba
    const url = new URL(window.location);
    url.searchParams.set('aba', abaId);
    window.history.replaceState({}, '', url);
}

// Atalho para seleção de categoria em 1 clique via chips
function selecionarChipCategoria(catNome, btnEl) {
    const select = document.getElementById('nova_exigencia_categoria');
    if (select) {
        select.value = catNome;
    }
    document.querySelectorAll('.chip-category-btn').forEach(b => b.classList.remove('is-selected'));
    if (btnEl) btnEl.classList.add('is-selected');
}

// Atalho rápido para preencher referência normativa
function setQuickRef(texto) {
    const input = document.getElementById('nova_exigencia_referencia');
    if (input) {
        input.value = texto;
        input.focus();
    }
}

// Modal do Banco NORMAM
let bancoNormasCache = <?= json_encode($todasReferenciasPreload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?> || [];
let ultimosItensFiltrados = [];

function abrirModalBancoNormam() {
    const modal = document.getElementById('modalBancoNormam');
    if (!modal) return;
    modal.style.display = 'flex';
    if (!bancoNormasCache || !Array.isArray(bancoNormasCache) || bancoNormasCache.length === 0) {
        carregarBancoNormas();
    } else {
        filtrarNormasModal();
    }
    setTimeout(() => document.getElementById('modalBuscaNormam')?.focus(), 100);
}

function fecharModalBancoNormam() {
    const modal = document.getElementById('modalBancoNormam');
    if (modal) modal.style.display = 'none';
}

function carregarBancoNormas() {
    const lista = document.getElementById('modalNormamLista');
    if (!lista) return;
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
    // Garantir que a aba de exigências está visível
    trocarAbaAnalise('exigencias');

    const selectCat = document.getElementById('nova_exigencia_categoria');
    const inputRef = document.getElementById('nova_exigencia_referencia');
    const textDesc = document.getElementById('nova_exigencia');

    if (selectCat && item.categoria) {
        selectCat.value = item.categoria;
        // Atualizar visual do chip
        document.querySelectorAll('.chip-category-btn').forEach(b => {
            if (b.textContent.trim() === item.categoria || item.categoria.includes(b.textContent.trim())) {
                b.classList.add('is-selected');
            } else {
                b.classList.remove('is-selected');
            }
        });
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

<?php
require_once __DIR__ . '/../../includes/footer.php';
