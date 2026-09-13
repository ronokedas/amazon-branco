<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/protocolos.php';
protocoloExigirAcesso();

$id = trim($_GET['id'] ?? '');
$d = null;
$movs = [];
$documentosAnexados = [];
$auditoria = [];
$originais = [];
$preEmb = trim($_GET['embarcacao_id'] ?? '');
$aceiteToken = trim($_GET['aceite_token'] ?? '');
$aceiteMov = trim($_GET['aceite_mov'] ?? '');

$embarcacoes = $pdo->query("SELECT e.id, e.nome, e.registro, COALESCE(e.cliente_id, e.proprietario_id) cliente_id, c.nome cliente_nome FROM embarcacoes e LEFT JOIN clientes c ON c.id = COALESCE(e.cliente_id, e.proprietario_id) WHERE e.ativo = 1 ORDER BY e.nome")->fetchAll(PDO::FETCH_ASSOC);
$clientes = $pdo->query('SELECT id, nome, cpf_cnpj FROM clientes ORDER BY nome')->fetchAll(PDO::FETCH_ASSOC);
$unidades = $pdo->query('SELECT * FROM protocolo_unidades_maritimas WHERE ativo = 1 ORDER BY tipo, nome')->fetchAll(PDO::FETCH_ASSOC);
$catalogo = $pdo->query('SELECT * FROM protocolo_catalogo_documentos WHERE ativo = 1 ORDER BY categoria, ordem, nome')->fetchAll(PDO::FETCH_ASSOC);

// Carregar análises e vistorias ativas para seleção inteligente
$analisesAbertas = $pdo->query("SELECT id, numero, embarcacao_id, tipo_processo, status FROM analises_planos ORDER BY criado_em DESC LIMIT 150")->fetchAll(PDO::FETCH_ASSOC);
$vistoriasAbertas = $pdo->query("SELECT v.id, v.numero, v.embarcacao_id, v.finalidade, v.status FROM vistorias v ORDER BY v.criado_em DESC LIMIT 150")->fetchAll(PDO::FETCH_ASSOC);

if ($id) {
    $d = protocoloCarregar($pdo, $id);
    $q = $pdo->prepare("SELECT m.*, u.nome criador_nome, um.nome unidade_nome, (SELECT COUNT(*) FROM protocolo_movimentacao_itens i WHERE i.movimentacao_id = m.id) itens_total, (SELECT token_hash FROM protocolo_aceites pa WHERE pa.movimentacao_id = m.id LIMIT 1) aceite_existente, (SELECT aceito_em FROM protocolo_aceites pa WHERE pa.movimentacao_id = m.id LIMIT 1) aceite_data FROM protocolo_movimentacoes m LEFT JOIN usuarios u ON u.id = m.criado_por LEFT JOIN protocolo_unidades_maritimas um ON um.id = m.unidade_maritima_id WHERE m.dossie_id = :id ORDER BY m.sequencia");
    $q->execute([':id' => $id]);
    $movs = $q->fetchAll(PDO::FETCH_ASSOC);

    $q = $pdo->prepare('SELECT c.*, m.sequencia movimentacao_sequencia, u.nome criador_nome FROM protocolo_comprovantes c LEFT JOIN protocolo_movimentacoes m ON m.id = c.movimentacao_id LEFT JOIN usuarios u ON u.id = c.criado_por WHERE c.dossie_id = :id ORDER BY c.criado_em DESC');
    $q->execute([':id' => $id]);
    $documentosAnexados = $q->fetchAll(PDO::FETCH_ASSOC);

    $q = $pdo->prepare('SELECT a.*, u.nome usuario_nome FROM protocolo_auditoria a LEFT JOIN usuarios u ON u.id = a.usuario_id WHERE a.dossie_id = :id ORDER BY a.criado_em DESC LIMIT 100');
    $q->execute([':id' => $id]);
    $auditoria = $q->fetchAll(PDO::FETCH_ASSOC);

    $q = $pdo->prepare("SELECT i.*, m.sequencia, m.status movimentacao_status FROM protocolo_movimentacao_itens i JOIN protocolo_movimentacoes m ON m.id = i.movimentacao_id WHERE m.dossie_id = :id AND i.requer_devolucao = 1 ORDER BY i.devolvido_em IS NULL DESC, m.sequencia");
    $q->execute([':id' => $id]);
    $originais = $q->fetchAll(PDO::FETCH_ASSOC);
}

$labels = protocoloRotulosStatus();
$auditoriaLabels = [
    'DOSSIE_CRIADO' => 'Dossiê Criado',
    'DOSSIE_EDITADO' => 'Dossiê Editado',
    'MOVIMENTACAO_RASCUNHO' => 'Nova Movimentação (Rascunho)',
    'MOVIMENTACAO_CONFIRMADA' => 'Movimentação Confirmada e Congelada',
    'REGISTRO_ORGAO' => 'Atendimento Registrado no Órgão',
    'ANDAMENTO_ORGAO' => 'Andamento Atualizado pelo Órgão',
    'ACEITE_CRIADO' => 'Link de Aceite Digital Gerado',
    'ACEITE_DIGITAL' => 'Recebimento Confirmado pelo Destinatário',
    'DOCUMENTO_ANEXADO' => 'Documento Digital Anexado',
    'ORIGINAL_DEVOLVIDO' => 'Devolução de Original Registrada',
    'DOSSIE_ENCERRADO' => 'Dossiê Encerrado',
    'DOSSIE_CANCELADO' => 'Dossiê Cancelado'
];
$somenteLeitura = $d && in_array($d['status'], ['ENCERRADO', 'CANCELADO'], true);
$retificar = trim($_GET['retificar'] ?? '');
$abaAtiva = trim($_GET['aba'] ?? ($retificar ? 'movimentacao' : 'timeline'));

$titulo_page = ($d ? $d['numero'] : 'Novo Protocolo') . ' - ERP';
require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/sidebar.php';
?>

<main class="conteudo-principal">
    <!-- Modal de Compartilhamento do Aceite Digital (se gerado) -->
    <?php if ($aceiteToken): ?>
        <?php $urlAceite = APP_URL . 'protocolo-aceite/' . $aceiteToken; ?>
        <div id="modal-aceite-overlay" style="position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 20px;">
            <div style="background: var(--bg-surface, #071f1b); border: 1px solid var(--accent, #56e0ad); border-radius: 14px; max-width: 580px; width: 100%; padding: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="m-0 text-accent" style="font-size: 1.25rem;"><i class="fa-solid fa-file-signature"></i> Link de Aceite Digital Gerado</h3>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="document.getElementById('modal-aceite-overlay').remove()">✕ Fechar</button>
                </div>
                <p class="text-secondary small">Envie este link para o cliente ou portador confirmar o recebimento dos documentos via smartphone. O aceite será registrado com data, identificação mascarada e IP.</p>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Link de Confirmação Pública:</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="input-link-aceite" value="<?= h($urlAceite) ?>" readonly>
                        <button class="btn btn-secondary" type="button" onclick="copiarLinkAceite()"><i class="fa-regular fa-copy"></i> Copiar</button>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <?php 
                    $msgWhats = rawurlencode("Olá! Segue o comprovante de entrega de documentos da embarcação " . ($d['embarcacao_nome'] ?? '') . " pela Amazon Certificadora Naval. Por favor, confirme o recebimento no link: " . $urlAceite); 
                    ?>
                    <a href="https://api.whatsapp.com/send?text=<?= $msgWhats ?>" target="_blank" rel="noopener" class="btn btn-success flex-grow-1" style="background: #25d366; border-color: #25d366; color: #fff;">
                        <i class="fa-brands fa-whatsapp"></i> Enviar via WhatsApp
                    </a>
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('modal-aceite-overlay').remove()">Concluir</button>
                </div>
            </div>
        </div>
        <script>
        function copiarLinkAceite() {
            const input = document.getElementById('input-link-aceite');
            input.select();
            navigator.clipboard.writeText(input.value);
            alert('Link copiado para a área de transferência!');
        }
        </script>
    <?php endif; ?>

    <!-- Cabeçalho Principal -->
    <div class="prot-page-header">
        <div class="prot-page-title">
            <h1>
                <i class="fa-solid fa-folder-tree text-accent"></i> 
                <?= $d ? h($d['numero']) : 'Novo Dossiê de Protocolo' ?>
            </h1>
            <p><?= $d ? h($d['assunto']) : 'Abra um novo processo para controlar custódia de documentos, envio à Capitania e devolução.' ?></p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-secondary" href="<?= APP_URL ?>protocolos">
                <i class="fa-solid fa-arrow-left"></i> Voltar à Lista
            </a>
            <?php if ($d): ?>
                <a class="btn btn-primary" target="_blank" href="<?= APP_URL ?>protocolos/pdf-dossie?id=<?= urlencode($id) ?>">
                    <i class="fa-solid fa-file-pdf"></i> PDF Consolidado
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!$d): ?>
        <!-- ========================================== -->
        <!-- MODO DE CRIAÇÃO DE NOVO DOSSIÊ DE PROTOCOLO -->
        <!-- ========================================== -->
        <div class="prot-helper-box info">
            <i class="fa-solid fa-circle-info text-accent"></i> 
            <strong>Como funciona o Dossiê de Protocolo:</strong><br>
            O dossiê é a pasta que acompanha a documentação da embarcação em todo o seu ciclo. Ao criá-lo, você vincula o barco e o objetivo técnico (ex.: NORMAM-202). A partir daí, você registra quando os documentos físicos chegam, quando vão para a Capitania dos Portos (com número de processo SISAP) e quando os originais são devolvidos ao armador com recibo.
        </div>

        <section class="card">
            <div class="card-body">
                <h3 class="mb-3" style="font-size: 1.15rem; color: var(--accent, #56e0ad);">
                    <i class="fa-solid fa-pen-to-square"></i> 1. Identificação e Objeto do Dossiê
                </h3>

                <form method="post" action="<?= APP_URL ?>protocolos/actions">
                    <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>">
                    <input type="hidden" name="action" value="criar">

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold" for="embarcacao_id">Embarcação *</label>
                            <select class="form-control" required name="embarcacao_id" id="embarcacao_id" onchange="sincronizarDadosEmbarcacao(this)">
                                <option value="">-- Selecione a embarcação --</option>
                                <?php foreach ($embarcacoes as $e): ?>
                                    <option value="<?= h($e['id']) ?>" 
                                            data-cliente="<?= h($e['cliente_id']) ?>" 
                                            <?= $preEmb === $e['id'] ? 'selected' : '' ?>>
                                        <?= h($e['nome'] . ($e['registro'] ? ' · ' . $e['registro'] : '')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">A embarcação vinculada determina o histórico técnico e o cliente.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold" for="cliente_id">Cliente / Solicitante</label>
                            <select class="form-control" name="cliente_id" id="cliente_id">
                                <option value="">Usar vínculo cadastral da embarcação</option>
                                <?php foreach ($clientes as $c): ?>
                                    <option value="<?= h($c['id']) ?>">
                                        <?= h($c['nome'] . ($c['cpf_cnpj'] ? ' (' . $c['cpf_cnpj'] . ')' : '')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Preenchido automaticamente com o proprietário/armador.</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold" for="assunto">Assunto / Finalidade do Processo *</label>
                        <input class="form-control" required maxlength="255" name="assunto" id="assunto" 
                               placeholder="Ex.: Apresentação de Projeto Técnico para Licença de Construção (LC) - NORMAM-202">
                        
                        <!-- Pílulas de Atalho Rápido para Assunto -->
                        <div class="mt-2">
                            <span class="text-secondary small fw-semibold me-1"><i class="fa-solid fa-bolt text-accent"></i> Sugestões de Assunto (clique para preencher):</span>
                            <div class="prot-shortcuts-container d-inline-flex flex-wrap mt-1">
                                <button type="button" class="prot-shortcut-btn" onclick="definirAssunto('Aprovação de Planos e Memoriais / NORMAM-202 (Licença de Construção - LC)')">
                                    Aprovação NORMAM-202 (LC)
                                </button>
                                <button type="button" class="prot-shortcut-btn" onclick="definirAssunto('Licença de Alteração / Reclassificação Naval (LA/LR) - NORMAM-202')">
                                    Alteração / Reclassificação (LA/LR)
                                </button>
                                <button type="button" class="prot-shortcut-btn" onclick="definirAssunto('Regularização de Arqueação e Borda Livre (CNARQ / CNBL)')">
                                    Arqueação e Borda Livre (CNARQ/CNBL)
                                </button>
                                <button type="button" class="prot-shortcut-btn" onclick="definirAssunto('Inscrição Inicial de Embarcação no TIE/TIEM')">
                                    Inscrição Inicial (TIE/TIEM)
                                </button>
                                <button type="button" class="prot-shortcut-btn" onclick="definirAssunto('Cumprimento de Notificação / Exigência da Capitania dos Portos')">
                                    Cumprimento de Exigência da CP
                                </button>
                                <button type="button" class="prot-shortcut-btn" onclick="definirAssunto('Renovação de Certificado de Segurança da Navegação (CSN)')">
                                    Renovação de CSN
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-bold" for="unidade_maritima_id">Destino Previsto (Capitania / Delegacia)</label>
                            <select class="form-control" name="unidade_maritima_id" id="unidade_maritima_id">
                                <option value="">Definir no momento do envio à Marinha</option>
                                <?php foreach ($unidades as $u): ?>
                                    <option value="<?= h($u['id']) ?>">
                                        <?= h($u['nome'] . ' — ' . $u['cidade'] . '/' . $u['uf']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Unidade da Marinha onde o processo será protocolado.</small>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold" for="analise_id">Processo de Análise Vinculado</label>
                            <select class="form-control" name="analise_id" id="analise_id">
                                <option value="">Sem vínculo com análise</option>
                                <?php foreach ($analisesAbertas as $a): ?>
                                    <option value="<?= h($a['id']) ?>" data-embarcacao="<?= h($a['embarcacao_id']) ?>" <?= ($_GET['analise_id'] ?? '') === $a['id'] ? 'selected' : '' ?>>
                                        <?= h($a['numero'] . ' (' . $a['tipo_processo'] . ' - ' . $a['status'] . ')') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Vincule ao processo de análise de planos do engenheiro naval.</small>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold" for="vistoria_id">Vistoria Vinculada</label>
                            <select class="form-control" name="vistoria_id" id="vistoria_id">
                                <option value="">Sem vínculo com vistoria</option>
                                <?php foreach ($vistoriasAbertas as $v): ?>
                                    <option value="<?= h($v['id']) ?>" data-embarcacao="<?= h($v['embarcacao_id']) ?>" <?= ($_GET['vistoria_id'] ?? '') === $v['id'] ? 'selected' : '' ?>>
                                        <?= h($v['numero'] . ' (' . ($v['finalidade'] ?: 'Vistoria') . ' - ' . $v['status'] . ')') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Vincule à ordem de vistoria técnica correspondente.</small>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fa-solid fa-folder-plus"></i> Criar Dossiê de Protocolo
                        </button>
                        <a href="<?= APP_URL ?>protocolos" class="btn btn-secondary btn-lg">Cancelar</a>
                    </div>
                </form>
            </div>
        </section>

        <script>
        function definirAssunto(txt) {
            document.getElementById('assunto').value = txt;
        }
        function sincronizarDadosEmbarcacao(select) {
            const opt = select.selectedOptions[0];
            if (opt && opt.dataset.cliente) {
                document.getElementById('cliente_id').value = opt.dataset.cliente;
            }
            // Filtrar dropdowns de analise e vistoria para a embarcacao selecionada
            const embId = select.value;
            const selAnalise = document.getElementById('analise_id');
            const selVistoria = document.getElementById('vistoria_id');

            for (let i = 1; i < selAnalise.options.length; i++) {
                const o = selAnalise.options[i];
                o.style.display = (!embId || o.dataset.embarcacao === embId) ? '' : 'none';
            }
            for (let i = 1; i < selVistoria.options.length; i++) {
                const o = selVistoria.options[i];
                o.style.display = (!embId || o.dataset.embarcacao === embId) ? '' : 'none';
            }
        }
        document.addEventListener('DOMContentLoaded', function() {
            const embSel = document.getElementById('embarcacao_id');
            if (embSel.value) sincronizarDadosEmbarcacao(embSel);
        });
        </script>

    <?php else: ?>
        <!-- ========================================== -->
        <!-- MODO DE VISUALIZAÇÃO/EDIÇÃO DE DOSSIÊ ABERTO -->
        <!-- ========================================== -->

        <!-- Card Resumo Executivo do Dossiê -->
        <section class="card mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-center">
                    <div class="col-md-3">
                        <small class="text-secondary d-block"><i class="fa-solid fa-ship"></i> Embarcação</small>
                        <strong class="fs-6"><?= h($d['embarcacao_nome']) ?></strong>
                        <?php if ($d['registro'] ?? ''): ?><span class="text-secondary small">(<?= h($d['registro']) ?>)</span><?php endif; ?>
                        <div class="small text-secondary mt-1">
                            <i class="fa-solid fa-user"></i> <?= h($d['cliente_nome'] ?: 'Cliente não informado') ?>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <small class="text-secondary d-block"><i class="fa-solid fa-tag"></i> Situação Atual</small>
                        <span class="prot-badge-status <?= h($d['status']) ?> mt-1">
                            <i class="fa-solid fa-circle" style="font-size: 6px;"></i>
                            <?= h($labels[$d['status']] ?? $d['status']) ?>
                        </span>
                        <?php if ($originais): ?>
                            <div class="mt-1 small" style="color: #f59e0b; font-weight: 600;">
                                <i class="fa-solid fa-box-archive"></i> <?= count(array_filter($originais, fn($x) => empty($x['devolvido_em']))) ?> original(is) sob custódia
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-3">
                        <small class="text-secondary d-block"><i class="fa-solid fa-anchor"></i> Destino Marítimo</small>
                        <strong><?= h($d['unidade_nome'] ?: 'Não definido') ?></strong>
                        <?php $chaveProc = 'protocolo_externo_' . 'numero'; if (!empty($d[$chaveProc])): ?>
                            <div class="text-accent small fw-bold">Proc.: <?= h($d[$chaveProc]) ?></div>
                        <?php endif; ?>
                        <div class="small text-secondary">
                            <?= $d['protocolo_externo_em'] ? 'Atendido em: ' . date('d/m/Y', strtotime($d['protocolo_externo_em'])) : 'Atendimento pendente' ?>
                        </div>
                    </div>

                    <div class="col-md-3 text-md-end">
                        <?php if ($d['protocolo_externo_validade']): ?>
                            <div class="badge bg-secondary p-2 mb-2 d-inline-block">
                                <i class="fa-solid fa-calendar-check"></i> Validade do Protocolo: 
                                <strong><?= date('d/m/Y', strtotime($d['protocolo_externo_validade'])) ?></strong>
                            </div>
                        <?php endif; ?>
                        <?php if ($d['url_consulta']): ?>
                            <div>
                                <a target="_blank" rel="noopener" class="btn btn-sm btn-outline-info" href="<?= h($d['url_consulta']) ?>">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i> Portal Oficial SISAP
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- Navegação em Abas do Dossiê -->
        <div class="prot-tabs-bar mb-4">
            <button type="button" class="prot-tab-btn <?= $abaAtiva === 'timeline' ? 'active' : '' ?>" onclick="trocarAbaDossie('timeline')">
                <i class="fa-solid fa-timeline"></i> Linha do Tempo & Trâmite
                <span class="badge bg-secondary"><?= count($movs) ?></span>
            </button>
            <?php if (!$somenteLeitura): ?>
                <button type="button" class="prot-tab-btn <?= $abaAtiva === 'movimentacao' ? 'active' : '' ?>" onclick="trocarAbaDossie('movimentacao')">
                    <i class="fa-solid fa-plus-circle"></i> Nova Movimentação
                </button>
            <?php endif; ?>
            <button type="button" class="prot-tab-btn <?= $abaAtiva === 'marinha' ? 'active' : '' ?>" onclick="trocarAbaDossie('marinha')">
                <i class="fa-solid fa-anchor"></i> Trâmite na Marinha
            </button>
            <button type="button" class="prot-tab-btn <?= $abaAtiva === 'custodia' ? 'active' : '' ?>" onclick="trocarAbaDossie('custodia')">
                <i class="fa-solid fa-box-archive"></i> Custódia de Originais
                <?php $pendentes = count(array_filter($originais, fn($x) => empty($x['devolvido_em']))); ?>
                <?php if ($pendentes > 0): ?>
                    <span class="badge bg-warning text-dark"><?= $pendentes ?></span>
                <?php endif; ?>
            </button>
            <button type="button" class="prot-tab-btn <?= $abaAtiva === 'anexos' ? 'active' : '' ?>" onclick="trocarAbaDossie('anexos')">
                <i class="fa-solid fa-paperclip"></i> Anexos Digitais
                <span class="badge bg-secondary"><?= count($documentosAnexados) ?></span>
            </button>
            <button type="button" class="prot-tab-btn <?= $abaAtiva === 'auditoria' ? 'active' : '' ?>" onclick="trocarAbaDossie('auditoria')">
                <i class="fa-solid fa-shield-halved"></i> Auditoria & Encerramento
            </button>
        </div>

        <!-- ============================================== -->
        <!-- ABA 1: LINHA DO TEMPO & TRÂMITE               -->
        <!-- ============================================== -->
        <div id="pane-timeline" class="prot-tab-pane <?= $abaAtiva === 'timeline' ? 'active' : '' ?>">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="m-0" style="font-size: 1.15rem; color: var(--accent, #56e0ad);">
                    <i class="fa-solid fa-clock-rotate-left"></i> Histórico Cronológico de Movimentações
                </h3>
                <?php if (!$somenteLeitura): ?>
                    <button type="button" class="btn btn-sm btn-primary" onclick="trocarAbaDossie('movimentacao')">
                        <i class="fa-solid fa-plus"></i> Registrar Nova Entrada/Saída
                    </button>
                <?php endif; ?>
            </div>

            <?php if (!$movs): ?>
                <div class="prot-helper-box info">
                    <i class="fa-solid fa-info-circle text-accent"></i> 
                    <strong>Nenhuma movimentação registrada ainda.</strong><br>
                    O primeiro passo normalmente é registrar a <strong>Entrada</strong> dos documentos entregues pelo cliente ou despachante à Amazon Certificadora.
                    <?php if (!$somenteLeitura): ?>
                        <div class="mt-2">
                            <button type="button" class="btn btn-sm btn-primary" onclick="trocarAbaDossie('movimentacao')">
                                <i class="fa-solid fa-file-import"></i> Registrar Primeira Entrada
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="prot-timeline-list">
                    <?php foreach ($movs as $m): ?>
                        <div class="prot-timeline-item" id="mov-<?= h($m['id']) ?>">
                            <div class="prot-timeline-dot">
                                <?= (int)$m['sequencia'] ?>
                            </div>
                            <div class="prot-timeline-card">
                                <div class="prot-timeline-header">
                                    <div>
                                        <span class="badge <?= $m['tipo'] === 'ENTRADA' ? 'bg-info' : 'bg-primary' ?> me-2">
                                            <?= $m['tipo'] === 'ENTRADA' ? '📥 ENTRADA' : '📤 SAÍDA' ?>
                                        </span>
                                        <strong class="fs-6"><?= h(str_replace('_', ' ', $m['natureza'])) ?></strong>
                                        <span class="badge bg-secondary ms-2 small">Evento #<?= str_pad((string)$m['sequencia'], 2, '0', STR_PAD_LEFT) ?></span>
                                        <?php if ($m['status'] === 'RASCUNHO'): ?>
                                            <span class="badge bg-warning text-dark ms-1">RASCUNHO (Não congelado)</span>
                                        <?php elseif ($m['status'] === 'RETIFICADA'): ?>
                                            <span class="badge bg-danger ms-1">RETIFICADA</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-secondary small">
                                        <i class="fa-regular fa-calendar"></i> <?= formatarDataCompleta($m['movimentado_em']) ?>
                                    </div>
                                </div>

                                <div class="row g-2 mb-2 small">
                                    <div class="col-md-6">
                                        <span class="text-secondary">De (Origem):</span> 
                                        <strong><?= h($m['origem_nome']) ?></strong> 
                                        <span class="text-secondary">(<?= h($m['origem_tipo']) ?>)</span>
                                    </div>
                                    <div class="col-md-6">
                                        <span class="text-secondary">Para (Destino):</span> 
                                        <strong><?= h($m['destino_nome']) ?></strong> 
                                        <span class="text-secondary">(<?= h($m['destino_tipo']) ?>)</span>
                                    </div>
                                    <div class="col-md-6">
                                        <span class="text-secondary">Local & Envio:</span> 
                                        <?= h($m['cidade'] . '/' . $m['uf']) ?> · <?= h($m['meio_envio']) ?>
                                        <?php if ($m['codigo_rastreio']): ?>
                                            · Rastreio: <code><?= h($m['codigo_rastreio']) ?></code>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6">
                                        <span class="text-secondary">Registrado por:</span> 
                                        <?= h($m['criador_nome'] ?: 'Sistema') ?>
                                    </div>
                                </div>

                                <?php if ($m['observacoes']): ?>
                                    <div class="small p-2 rounded mb-3" style="background: rgba(255,255,255,0.03); border: 1px solid var(--border);">
                                        <strong>Observações:</strong> <?= nl2br(h($m['observacoes'])) ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Relação de Itens da Movimentação -->
                                <?php 
                                $qItens = $pdo->prepare("SELECT * FROM protocolo_movimentacao_itens WHERE movimentacao_id = :mid ORDER BY id ASC");
                                $qItens->execute([':mid' => $m['id']]);
                                $mItens = $qItens->fetchAll(PDO::FETCH_ASSOC);
                                ?>
                                <?php if ($mItens): ?>
                                    <div class="mb-3">
                                        <span class="small fw-bold text-secondary d-block mb-1">
                                            <i class="fa-solid fa-files"></i> Documentos desta Movimentação (<?= count($mItens) ?>):
                                        </span>
                                        <div class="table-responsive">
                                            <table class="table table-sm mb-0" style="font-size: 0.82rem;">
                                                <thead>
                                                    <tr class="text-secondary">
                                                        <th>Item</th>
                                                        <th>Suporte / Forma</th>
                                                        <th>Qtd</th>
                                                        <th>Revisão</th>
                                                        <th>Custódia</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($mItens as $it): ?>
                                                        <tr>
                                                            <td><strong><?= h($it['descricao']) ?></strong></td>
                                                            <td><?= h($it['suporte']) ?> · <?= h(str_replace('_', ' ', $it['forma'])) ?></td>
                                                            <td><?= (int)$it['quantidade'] ?></td>
                                                            <td><?= h($it['numero_revisao'] ?: '—') ?></td>
                                                            <td>
                                                                <?php if ($it['requer_devolucao']): ?>
                                                                    <?php if ($it['devolvido_em']): ?>
                                                                        <span class="badge bg-success">Devolvido</span>
                                                                    <?php else: ?>
                                                                        <span class="badge bg-warning text-dark">Exige Devolução</span>
                                                                    <?php endif; ?>
                                                                <?php else: ?>
                                                                    <span class="text-muted">—</span>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Ações da Movimentação -->
                                <div class="d-flex flex-wrap gap-2 pt-2 border-top" style="border-color: var(--border) !important;">
                                    <?php if (in_array($m['status'], ['CONFIRMADA', 'RETIFICADA'], true)): ?>
                                        <a class="btn btn-sm btn-secondary" target="_blank" href="<?= APP_URL ?>protocolos/pdf?id=<?= urlencode($m['id']) ?>">
                                            <i class="fa-solid fa-file-pdf text-danger"></i> Comprovante Oficial (PDF)
                                        </a>

                                        <?php if ($m['aceite_existente']): ?>
                                            <button type="button" class="btn btn-sm btn-outline-info" onclick="abrirModalAceite('<?= h($m['aceite_existente']) ?>')">
                                                <i class="fa-solid fa-file-signature"></i> 
                                                <?= $m['aceite_data'] ? 'Aceite Assinado' : 'Ver Link de Aceite' ?>
                                            </button>
                                        <?php elseif (!$somenteLeitura): ?>
                                            <form method="post" action="<?= APP_URL ?>protocolos/actions" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>">
                                                <input type="hidden" name="action" value="criar_aceite">
                                                <input type="hidden" name="dossie_id" value="<?= h($id) ?>">
                                                <input type="hidden" name="movimentacao_id" value="<?= h($m['id']) ?>">
                                                <button type="submit" class="btn btn-sm btn-secondary">
                                                    <i class="fa-solid fa-signature"></i> Gerar link de aceite
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <?php if ($m['status'] === 'RASCUNHO' && !$somenteLeitura): ?>
                                        <form method="post" action="<?= APP_URL ?>protocolos/actions" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>">
                                            <input type="hidden" name="action" value="confirmar">
                                            <input type="hidden" name="dossie_id" value="<?= h($id) ?>">
                                            <input type="hidden" name="movimentacao_id" value="<?= h($m['id']) ?>">
                                            <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('Atenção: Ao confirmar, este evento terá seu conteúdo congelado e será gerado o comprovante com código de autenticidade. Deseja prosseguir?')">
                                                <i class="fa-solid fa-lock"></i> Confirmar e Congelar Evento
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($m['status'] === 'CONFIRMADA' && !$somenteLeitura): ?>
                                        <a class="btn btn-sm btn-secondary" href="<?= APP_URL ?>protocolos/form?id=<?= urlencode($id) ?>&retificar=<?= urlencode($m['id']) ?>&aba=movimentacao">
                                            <i class="fa-solid fa-rotate-left"></i> Retificar este Evento
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- ============================================== -->
        <!-- ABA 2: NOVA MOVIMENTAÇÃO (ENTRADA / SAÍDA)     -->
        <!-- ============================================== -->
        <?php if (!$somenteLeitura): ?>
            <div id="pane-movimentacao" class="prot-tab-pane <?= $abaAtiva === 'movimentacao' ? 'active' : '' ?>">
                <section class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3 class="m-0" style="font-size: 1.15rem; color: var(--accent, #56e0ad);">
                                <i class="fa-solid fa-plus-circle"></i> 
                                <?= $retificar ? 'Retificar Evento Confirmado' : 'Registrar Nova Movimentação' ?>
                            </h3>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="trocarAbaDossie('timeline')">Cancelar</button>
                        </div>

                        <?php if ($retificar): ?>
                            <div class="prot-helper-box warning">
                                <i class="fa-solid fa-triangle-exclamation text-warning"></i>
                                <strong>Modo de Retificação:</strong> A retificação criará um novo evento oficial com a devida correção. O evento anterior permanecerá gravado na trilha de auditoria e será marcado como retificado para garantia jurídica.
                            </div>
                        <?php else: ?>
                            <div class="prot-helper-box info">
                                <i class="fa-solid fa-lightbulb text-accent"></i>
                                <strong>Como preencher:</strong>
                                <ul>
                                    <li><strong>Entrada:</strong> Selecione quando a Amazon Certificadora estiver <em>recebendo</em> documentos de clientes, estaleiros ou despachantes.</li>
                                    <li><strong>Saída:</strong> Selecione quando a Amazon Certificadora estiver <em>enviando ou protocolando</em> na Capitania dos Portos ou <em>entregando</em> documentos de volta ao cliente.</li>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="post" action="<?= APP_URL ?>protocolos/actions" id="form-movimentacao">
                            <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>">
                            <input type="hidden" name="action" value="adicionar_movimentacao">
                            <input type="hidden" name="dossie_id" value="<?= h($id) ?>">
                            <input type="hidden" name="idempotency_key" value="<?= h(bin2hex(random_bytes(16))) ?>">
                            <input type="hidden" name="retifica_movimentacao_id" value="<?= h($retificar) ?>">

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold" for="mov_tipo">Sentido da Movimentação *</label>
                                    <select class="form-control" name="tipo" id="mov_tipo" required onchange="ajustarSentidoMovimentacao(this.value)">
                                        <option value="ENTRADA">📥 ENTRADA — Amazon Certificadora recebe documentos</option>
                                        <option value="SAIDA">📤 SAÍDA — Amazon Certificadora entrega / protocola</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold" for="mov_natureza">Natureza Operacional *</label>
                                    <select class="form-control" name="natureza" id="mov_natureza" required>
                                        <option value="RECEBIMENTO_CLIENTE">Recebimento do Cliente / Representante</option>
                                        <option value="ENVIO_ORGAO">Envio / Protocolo na Capitania dos Portos (Marinha)</option>
                                        <option value="RETORNO_ORGAO">Retorno / Exigência Recebida da Marinha</option>
                                        <option value="CUMPRIMENTO_EXIGENCIA">Cumprimento de exigência</option>
                                        <option value="RETIRADA_ORGAO">Retirada de Documento Aprovado no Órgão</option>
                                        <option value="ENTREGA_CLIENTE">Entrega Definitiva de Documentos ao Cliente</option>
                                        <option value="TRANSFERENCIA_INTERNA">Transferência Interna entre Departamentos</option>
                                        <option value="OUTRA">Outra Movimentação</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Origem (Quem entrega os documentos) *</label>
                                    <div class="row g-2">
                                        <div class="col-4">
                                            <select class="form-control" name="origem_tipo" id="origem_tipo">
                                                <option value="CLIENTE">Cliente</option>
                                                <option value="REPRESENTANTE">Representante</option>
                                                <option value="AMAZON_NAVAL">Amazon Naval</option>
                                                <option value="CAPITANIA">Capitania</option>
                                                <option value="DELEGACIA">Delegacia</option>
                                                <option value="AGENCIA">Agência</option>
                                                <option value="CORREIOS">Correios</option>
                                                <option value="TRANSPORTADORA">Transportadora</option>
                                                <option value="OUTRO">Outro</option>
                                            </select>
                                        </div>
                                        <div class="col-8">
                                            <input class="form-control" name="origem_nome" id="origem_nome" required 
                                                   value="<?= h($d['cliente_nome'] ?: '') ?>" placeholder="Nome completo do emissor">
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Destino (Quem recebe os documentos) *</label>
                                    <div class="row g-2">
                                        <div class="col-4">
                                            <select class="form-control" name="destino_tipo" id="destino_tipo">
                                                <option value="AMAZON_NAVAL" selected>Amazon Naval</option>
                                                <option value="CAPITANIA">Capitania</option>
                                                <option value="DELEGACIA">Delegacia</option>
                                                <option value="AGENCIA">Agência</option>
                                                <option value="CLIENTE">Cliente</option>
                                                <option value="REPRESENTANTE">Representante</option>
                                                <option value="CORREIOS">Correios</option>
                                                <option value="TRANSPORTADORA">Transportadora</option>
                                                <option value="OUTRO">Outro</option>
                                            </select>
                                        </div>
                                        <div class="col-8">
                                            <input class="form-control" name="destino_nome" id="destino_nome" required 
                                                   value="Amazon Certificadora Naval" placeholder="Nome de quem recebe">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold" for="mov_unidade">Unidade Marítima (se houver envio ao órgão)</label>
                                    <select class="form-control" name="unidade_maritima_id" id="mov_unidade" onchange="aoSelecionarUnidadeMaritima(this)">
                                        <option value="">Selecione quando houver trâmite com a Marinha</option>
                                        <?php foreach ($unidades as $u): ?>
                                            <option value="<?= h($u['id']) ?>" data-cidade="<?= h($u['cidade']) ?>" data-uf="<?= h($u['uf']) ?>" <?= $d['unidade_maritima_id'] === $u['id'] ? 'selected' : '' ?>>
                                                <?= h($u['nome'] . ' — ' . $u['cidade'] . '/' . $u['uf']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label fw-bold" for="movimentado_em">Data e Hora do Evento *</label>
                                    <input class="form-control" type="datetime-local" name="movimentado_em" id="movimentado_em" required value="<?= date('Y-m-d\TH:i') ?>">
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label fw-bold" for="mov_cidade">Cidade *</label>
                                    <input class="form-control" name="cidade" id="mov_cidade" required value="Belém">
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label fw-bold" for="mov_uf">UF *</label>
                                    <input class="form-control" name="uf" id="mov_uf" maxlength="2" required value="PA">
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-3">
                                    <label class="form-label fw-bold" for="meio_envio">Meio de Envio / Transporte *</label>
                                    <select class="form-control" name="meio_envio" id="meio_envio">
                                        <option value="PRESENCIAL">Presencial (Balcão / Em mãos)</option>
                                        <option value="CORREIOS">Correios (SEDEX / PAC / AR)</option>
                                        <option value="PORTAL">Portal Digital SISAP / DPC</option>
                                        <option value="EMAIL">E-mail Oficial</option>
                                        <option value="TRANSPORTADORA">Transportadora Fluvial / Rodoviária</option>
                                        <option value="MENSAGEIRO">Mensageiro / Despachante</option>
                                        <option value="OUTRO">Outro</option>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label fw-bold" for="portador_nome">Portador / Entregador</label>
                                    <input class="form-control" name="portador_nome" id="portador_nome" placeholder="Ex.: João da Silva (Despachante)">
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label fw-bold" for="codigo_rastreio">Código de Rastreio (se houver)</label>
                                    <input class="form-control" name="codigo_rastreio" id="codigo_rastreio" placeholder="Ex.: AA123456789BR">
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label fw-bold" for="protocolo_anterior_id">Vínculo com Evento</label>
                                    <select class="form-control" name="protocolo_anterior_id" id="protocolo_anterior_id">
                                        <option value="">Sem vínculo</option>
                                        <?php foreach ($movs as $m): ?>
                                            <?php if ($m['status'] !== 'RASCUNHO'): ?>
                                                <option value="<?= h($m['id']) ?>">
                                                    Evento #<?= str_pad((string)$m['sequencia'], 2, '0', STR_PAD_LEFT) ?>
                                                </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-bold" for="observacoes">Observações Técnicas / Operacionais</label>
                                    <textarea class="form-control" name="observacoes" id="observacoes" rows="2" placeholder="Informações adicionais sobre o estado dos documentos, exigências verbais, prazos acordados..."></textarea>
                                </div>
                            </div>

                            <!-- Documentos da Movimentação -->
                            <div class="p-3 rounded mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--border);">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h4 class="m-0 text-accent" style="font-size: 1rem;">
                                        <i class="fa-solid fa-list-check"></i> Relação de Documentos Apresentados
                                    </h4>
                                    <button class="btn btn-sm btn-secondary" type="button" id="btn-add-doc">
                                        <i class="fa-solid fa-plus"></i> Adicionar Item
                                    </button>
                                </div>
                                <p class="text-secondary small mb-3">Selecione documentos do catálogo naval ou descreva livremente. Marque o checkbox <strong>"Exige devolução"</strong> para qualquer via original que precise retornar ao cliente.</p>

                                <!-- Atalhos rápidos para adicionar documentos frequentes -->
                                <div class="mb-3">
                                    <span class="text-secondary small me-2"><i class="fa-solid fa-bolt text-accent"></i> Adicionar Rápido:</span>
                                    <div class="d-inline-flex flex-wrap gap-1">
                                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 0.75rem;" onclick="adicionarDocCatalogo('REQ_INTERESSADO')">+ Requerimento</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 0.75rem;" onclick="adicionarDocCatalogo('ART')">+ ART / RRT</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 0.75rem;" onclick="adicionarDocCatalogo('MEMORIAL_DESCRITIVO')">+ Memorial Descritivo</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 0.75rem;" onclick="adicionarDocCatalogo('PLANO_ARRANJO_GERAL')">+ Arranjo Geral</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 0.75rem;" onclick="adicionarDocCatalogo('PLANO_LINHAS')">+ Plano de Linhas</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 0.75rem;" onclick="adicionarDocCatalogo('CALCULOS_ESTABILIDADE')">+ Estabilidade</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 0.75rem;" onclick="adicionarDocCatalogo('PROCURACAO')">+ Procuração</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 0.75rem;" onclick="adicionarDocCatalogo('DOCUMENTO_PROPRIEDADE')">+ Doc. Propriedade</button>
                                    </div>
                                </div>

                                <div id="lista-docs-container"></div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fa-solid fa-floppy-disk"></i> Salvar Movimentação para Conferência
                                </button>
                                <button type="button" class="btn btn-secondary btn-lg" onclick="trocarAbaDossie('timeline')">
                                    Cancelar
                                </button>
                            </div>
                        </form>
                    </div>
                </section>
            </div>
        <?php endif; ?>

        <!-- ============================================== -->
        <!-- ABA 3: TRÂMITE NA MARINHA / CAPITANIA          -->
        <!-- ============================================== -->
        <div id="pane-marinha" class="prot-tab-pane <?= $abaAtiva === 'marinha' ? 'active' : '' ?>">
            <div class="row g-4">
                <!-- Registro Oficial do Atendimento na Marinha -->
                <div class="col-md-6">
                    <section class="card h-100">
                        <div class="card-body">
                            <h3 style="font-size: 1.15rem; color: var(--accent, #56e0ad);" class="mb-3">
                                <i class="fa-solid fa-building-flag"></i> Registro do Atendimento na Marinha
                            </h3>
                            <p class="text-secondary small">Preencha assim que o processo for presencialmente ou digitalmente protocolado na Capitania dos Portos (DPC / SISAP).</p>

                            <?php if (!$somenteLeitura): ?>
                                <form method="post" action="<?= APP_URL ?>protocolos/actions">
                                    <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>">
                                    <input type="hidden" name="action" value="registro_orgao">
                                    <input type="hidden" name="dossie_id" value="<?= h($id) ?>">

                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Unidade Marítima (Capitania / Delegacia) *</label>
                                        <select class="form-control" name="unidade_maritima_id" required>
                                            <option value="">-- Selecione a Capitania / Órgão --</option>
                                            <?php foreach ($unidades as $u): ?>
                                                <option value="<?= h($u['id']) ?>" <?= $d['unidade_maritima_id'] === $u['id'] ? 'selected' : '' ?>>
                                                    <?= h($u['nome'] . ' (' . $u['cidade'] . '/' . $u['uf'] . ')') ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Identificação Oficial do Processo / Protocolo na Marinha</label>
                                        <input class="form-control" name="numero_processo_orgao" 
                                               value="<?= h($d[$chaveProc] ?? '') ?>" 
                                               placeholder="Ex.: 23000.012345/2026-89">
                                        <small class="text-muted">Número fornecido pelo protocolo da Capitania para acompanhamento.</small>
                                    </div>

                                    <div class="row g-2 mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">Data/Hora do Atendimento *</label>
                                            <input class="form-control" type="datetime-local" name="protocolo_externo_em" required 
                                                   value="<?= $d['protocolo_externo_em'] ? date('Y-m-d\TH:i', strtotime($d['protocolo_externo_em'])) : date('Y-m-d\TH:i') ?>">
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">Validade do Protocolo Provisório</label>
                                            <input class="form-control" type="date" name="validade" 
                                                   value="<?= h($d['protocolo_externo_validade'] ?? '') ?>">
                                            <small class="text-muted">Geralmente 90 ou 180 dias.</small>
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa-solid fa-save"></i> Salvar Registro do Atendimento
                                    </button>
                                </form>
                            <?php else: ?>
                                <p class="text-muted">Dossiê encerrado. Registros congelados para consulta.</p>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>

                <!-- Atualização de Andamento / Notificação de Exigência -->
                <div class="col-md-6">
                    <section class="card h-100">
                        <div class="card-body">
                            <h3 style="font-size: 1.15rem; color: var(--accent, #56e0ad);" class="mb-3">
                                <i class="fa-solid fa-arrow-progress"></i> Atualização de Andamento na Marinha
                            </h3>
                            <p class="text-secondary small">Atualize o status do processo conforme as movimentações e notificações da Capitania dos Portos (ex.: quando entrar em exigência ou ficar pronto para retirada).</p>

                            <?php if (!$somenteLeitura): ?>
                                <form method="post" action="<?= APP_URL ?>protocolos/actions">
                                    <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>">
                                    <input type="hidden" name="action" value="andamento_orgao">
                                    <input type="hidden" name="dossie_id" value="<?= h($id) ?>">

                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Novo Andamento Informado pelo Órgão *</label>
                                        <select class="form-control" name="novo_status" required>
                                            <option value="PROTOCOLADO" <?= $d['status'] === 'PROTOCOLADO' ? 'selected' : '' ?>>PROTOCOLADO (Aguardando análise da Capitania)</option>
                                            <option value="EM_ANALISE_NO_ORGAO" <?= $d['status'] === 'EM_ANALISE_NO_ORGAO' ? 'selected' : '' ?>>EM ANÁLISE TÉCNICA (Com o vistoriador da Marinha)</option>
                                            <option value="EM_EXIGENCIA" <?= $d['status'] === 'EM_EXIGENCIA' ? 'selected' : '' ?>>EM EXIGÊNCIA ⚠️ (Ofício de exigência expedido)</option>
                                            <option value="A_DISPOSICAO" <?= $d['status'] === 'A_DISPOSICAO' ? 'selected' : '' ?>>DOCUMENTO À DISPOSIÇÃO (Pronto para retirada)</option>
                                            <option value="RETIRADO" <?= $d['status'] === 'RETIRADO' ? 'selected' : '' ?>>RETIRADO (Documento retirado na Capitania)</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Detalhes do Andamento / Despacho *</label>
                                        <textarea class="form-control" name="andamento_observacao" rows="3" required placeholder="Ex.: Recebido Ofício nº 123/2026 solicitando ajuste na prancha de arranjo com prazo até 20/10/2026..."></textarea>
                                        <small class="text-muted">Esta anotação ficará registrada com carimbo de data/hora e usuário na auditoria.</small>
                                    </div>

                                    <button type="submit" class="btn btn-secondary">
                                        <i class="fa-solid fa-pen-to-square"></i> Registrar Andamento
                                    </button>
                                </form>
                            <?php else: ?>
                                <p class="text-muted">Dossiê encerrado.</p>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>
            </div>
        </div>

        <!-- ============================================== -->
        <!-- ABA 4: CUSTÓDIA DE ORIGINAIS FÍSICOS           -->
        <!-- ============================================== -->
        <div id="pane-custodia" class="prot-tab-pane <?= $abaAtiva === 'custodia' ? 'active' : '' ?>">
            <section class="card">
                <div class="card-body">
                    <h3 style="font-size: 1.15rem; color: var(--accent, #56e0ad);" class="mb-3">
                        <i class="fa-solid fa-box-archive"></i> Controle de Custódia de Documentos Originais
                    </h3>
                    <p class="text-secondary small">
                        Controle rigoroso de documentos físicos originais do cliente (ex.: escrituras públicas, notas fiscais originais dos motores, vias assinadas de plantas) que foram confiados à Amazon Certificadora e devem ser devolvidos após o protocolo ou encerramento.
                    </p>

                    <?php if (!$originais): ?>
                        <div class="text-center py-4 text-secondary">
                            <i class="fa-solid fa-box-open fa-2x mb-2 opacity-50"></i>
                            <p class="mb-0">Nenhum documento original sob custódia registrado neste dossiê.</p>
                            <small>Para registrar custódia, marque o campo "Exige devolução" ao adicionar itens em uma movimentação de entrada.</small>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead>
                                    <tr style="border-bottom: 2px solid var(--border);">
                                        <th>Documento Original</th>
                                        <th>Evento de Entrada</th>
                                        <th>Qtd</th>
                                        <th>Condição / Revisão</th>
                                        <th>Status da Custódia</th>
                                        <th style="text-align: right;">Ação</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($originais as $o): ?>
                                        <tr style="border-bottom: 1px solid var(--border); vertical-align: middle;">
                                            <td>
                                                <strong><?= h($o['descricao']) ?></strong>
                                                <?php if ($o['categoria'] ?? ''): ?>
                                                    <div class="text-secondary small"><?= h($o['categoria']) ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">Evento #<?= str_pad((string)$o['sequencia'], 2, '0', STR_PAD_LEFT) ?></span>
                                            </td>
                                            <td><?= (int)$o['quantidade'] ?></td>
                                            <td><?= h($o['condicao_documento'] ?: ($o['numero_revisao'] ?: 'Conferido')) ?></td>
                                            <td>
                                                <?php if ($o['devolvido_em']): ?>
                                                    <span class="badge bg-success">
                                                        <i class="fa-solid fa-check"></i> Devolvido em <?= formatarDataCompleta($o['devolvido_em']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">
                                                        <i class="fa-solid fa-lock"></i> Sob Custódia da Amazon Naval
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="text-align: right;">
                                                <?php if (!$o['devolvido_em'] && !$somenteLeitura): ?>
                                                    <form method="post" action="<?= APP_URL ?>protocolos/actions" class="d-inline">
                                                        <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>">
                                                        <input type="hidden" name="action" value="registrar_devolucao">
                                                        <input type="hidden" name="dossie_id" value="<?= h($id) ?>">
                                                        <input type="hidden" name="item_id" value="<?= h($o['id']) ?>">
                                                        <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('Confirma a devolução deste documento original ao cliente/representante?')">
                                                            <i class="fa-solid fa-hand-holding-hand"></i> Dar Baixa na Devolução
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="text-muted small">Baixado</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <!-- ============================================== -->
        <!-- ABA 5: ANEXOS & DOCUMENTOS DIGITAIS            -->
        <!-- ============================================== -->
        <div id="pane-anexos" class="prot-tab-pane <?= $abaAtiva === 'anexos' ? 'active' : '' ?>">
            <section class="card mb-4">
                <div class="card-body">
                    <h3 style="font-size: 1.15rem; color: var(--accent, #56e0ad);" class="mb-3">
                        <i class="fa-solid fa-paperclip"></i> Repositório de Documentos & Anexos Digitais
                    </h3>
                    <p class="text-secondary small">Arquivos digitais (pranchas, memoriais, comprovantes de protocolo, recibos ou fotos) protegidos com hash criptográfico SHA-256 para integridade jurídica.</p>

                    <?php if (!$somenteLeitura): ?>
                        <form method="post" enctype="multipart/form-data" action="<?= APP_URL ?>protocolos/actions" class="mb-4 p-3 rounded" style="background: rgba(255,255,255,0.02); border: 1px solid var(--border);">
                            <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>">
                            <input type="hidden" name="action" value="anexar_documentos">
                            <input type="hidden" name="dossie_id" value="<?= h($id) ?>">

                            <div class="row g-2 align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Vincular a um Evento Específico (Opcional)</label>
                                    <select class="form-control" name="movimentacao_id">
                                        <option value="">Vincular ao dossiê geral</option>
                                        <?php foreach ($movs as $m): ?>
                                            <?php if ($m['status'] !== 'RASCUNHO'): ?>
                                                <option value="<?= h($m['id']) ?>">
                                                    Evento #<?= str_pad((string)$m['sequencia'], 2, '0', STR_PAD_LEFT) ?> — <?= h(str_replace('_', ' ', $m['natureza'])) ?>
                                                </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-5">
                                    <label class="form-label small fw-bold">Selecionar Arquivos (PDF, JPG, PNG)</label>
                                    <input class="form-control" type="file" required multiple name="documentos[]" accept=".pdf,.jpg,.jpeg,.png">
                                </div>

                                <div class="col-md-3">
                                    <button class="btn btn-primary w-100" type="submit">
                                        <i class="fa-solid fa-upload"></i> Fazer Upload
                                    </button>
                                </div>
                            </div>
                            <small class="text-secondary d-block mt-2">Envie até 10 arquivos simultâneos, com no máximo 15 MB cada.</small>
                        </form>
                    <?php endif; ?>

                    <?php if (!$documentosAnexados): ?>
                        <div class="text-center py-4 text-secondary">
                            <i class="fa-regular fa-file fa-2x mb-2 opacity-50"></i>
                            <p class="mb-0">Nenhum arquivo digital anexado a este dossiê.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table mb-0" style="font-size: 0.88rem;">
                                <thead>
                                    <tr style="border-bottom: 2px solid var(--border);">
                                        <th>Arquivo</th>
                                        <th>Vínculo</th>
                                        <th>Tamanho</th>
                                        <th>Enviado por / Data</th>
                                        <th>Integridade (SHA-256)</th>
                                        <th style="text-align: right;">Download</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($documentosAnexados as $a): ?>
                                        <tr style="border-bottom: 1px solid var(--border); vertical-align: middle;">
                                            <td>
                                                <a target="_blank" href="<?= APP_URL ?>protocolos/arquivo?id=<?= urlencode($a['id']) ?>" class="fw-semibold text-accent">
                                                    <i class="fa-regular fa-file-pdf"></i> <?= h($a['nome_original']) ?>
                                                </a>
                                            </td>
                                            <td>
                                                <?= $a['movimentacao_sequencia'] ? '<span class="badge bg-secondary">Evento #' . str_pad((string)$a['movimentacao_sequencia'], 2, '0', STR_PAD_LEFT) . '</span>' : '<span class="badge bg-dark">Dossiê</span>' ?>
                                            </td>
                                            <td><?= number_format((int)$a['tamanho_bytes'] / 1024, 1, ',', '.') ?> KB</td>
                                            <td>
                                                <?= h($a['criador_nome'] ?: 'Sistema') ?><br>
                                                <small class="text-secondary"><?= formatarDataCompleta($a['criado_em']) ?></small>
                                            </td>
                                            <td>
                                                <code style="font-size: 0.72rem;"><?= substr($a['sha256'], 0, 16) ?>...</code>
                                            </td>
                                            <td style="text-align: right;">
                                                <a class="btn btn-sm btn-secondary" target="_blank" href="<?= APP_URL ?>protocolos/arquivo?id=<?= urlencode($a['id']) ?>" download>
                                                    <i class="fa-solid fa-download"></i> Baixar
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <!-- ============================================== -->
        <!-- ABA 6: AUDITORIA & ENCERRAMENTO               -->
        <!-- ============================================== -->
        <div id="pane-auditoria" class="prot-tab-pane <?= $abaAtiva === 'auditoria' ? 'active' : '' ?>">
            <div class="row g-4">
                <div class="col-md-8">
                    <section class="card">
                        <div class="card-body">
                            <h3 style="font-size: 1.15rem; color: var(--accent, #56e0ad);" class="mb-3">
                                <i class="fa-solid fa-shield-halved"></i> Trilha de Auditoria Criptográfica
                            </h3>
                            <p class="text-secondary small">Registro cronológico detalhado de todas as operações realizadas neste processo para garantia de conformidade.</p>

                            <div style="max-height: 480px; overflow-y: auto; padding-right: 8px;">
                                <?php foreach ($auditoria as $a): ?>
                                    <div class="p-2 mb-2 rounded" style="background: rgba(255,255,255,0.02); border-left: 3px solid var(--accent); font-size: 0.84rem;">
                                        <div class="d-flex justify-content-between">
                                            <strong><?= h($auditoriaLabels[$a['evento']] ?? str_replace('_', ' ', $a['evento'])) ?></strong>
                                            <span class="text-secondary small"><?= formatarDataCompleta($a['criado_em']) ?></span>
                                        </div>
                                        <div class="text-secondary small">
                                            Operador: <?= h($a['usuario_nome'] ?: 'Acesso Externo / Público') ?> · IP: <?= h($a['ip'] ?: '—') ?>
                                        </div>
                                        <?php if ($a['detalhe']): ?>
                                            <div class="mt-1 small" style="color: var(--text-primary);"><?= h($a['detalhe']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="col-md-4">
                    <section class="card">
                        <div class="card-body">
                            <h3 style="font-size: 1.15rem; color: var(--accent, #56e0ad);" class="mb-3">
                                <i class="fa-solid fa-flag-checkered"></i> Conclusão do Processo
                            </h3>

                            <?php if (!$somenteLeitura): ?>
                                <p class="text-secondary small">Encerre este dossiê quando todo o trâmite tiver sido finalizado com sucesso e todos os originais devolvidos ao cliente.</p>

                                <form method="post" action="<?= APP_URL ?>protocolos/actions" class="mb-3">
                                    <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>">
                                    <input type="hidden" name="action" value="encerrar">
                                    <input type="hidden" name="dossie_id" value="<?= h($id) ?>">
                                    <button type="submit" class="btn btn-success w-100 mb-2" onclick="return confirm('Confirma o encerramento do dossiê? Os dados serão congelados como finalizados.')">
                                        <i class="fa-solid fa-check-circle"></i> Encerrar Dossiê (Concluído)
                                    </button>
                                </form>

                                <hr style="border-color: var(--border);">

                                <p class="text-secondary small">Em caso de desistência ou cancelamento do processo documental:</p>
                                <form method="post" action="<?= APP_URL ?>protocolos/actions" onsubmit="return confirm('Atenção: Deseja realmente cancelar este dossiê? Esta ação é irreversível.')">
                                    <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>">
                                    <input type="hidden" name="action" value="cancelar">
                                    <input type="hidden" name="dossie_id" value="<?= h($id) ?>">
                                    <div class="mb-2">
                                        <input class="form-control form-control-sm" name="motivo" required placeholder="Motivo obrigatório do cancelamento...">
                                    </div>
                                    <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                                        <i class="fa-solid fa-ban"></i> Cancelar Dossiê
                                    </button>
                                </form>
                            <?php else: ?>
                                <div class="badge bg-secondary p-2 w-100">
                                    Dossiê <?= h($labels[$d['status']] ?? $d['status']) ?> em <?= formatarDataCompleta($d['atualizado_em']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>
            </div>
        </div>

        <script>
        // Função para alternar abas do dossiê
        function trocarAbaDossie(abaId) {
            document.querySelectorAll('.prot-tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.prot-tab-pane').forEach(p => p.classList.remove('active'));
            
            const btn = document.querySelector(`.prot-tab-btn[onclick*="${abaId}"]`);
            const pane = document.getElementById(`pane-${abaId}`);
            if (btn) btn.classList.add('active');
            if (pane) pane.classList.add('active');
            
            // Atualizar URL sem recarregar
            const url = new URL(window.location);
            url.searchParams.set('aba', abaId);
            window.history.replaceState({}, '', url);
        }

        // Catálogo dinâmico para adicionar documentos
        const catalogo = <?= json_encode(array_map(fn($x) => ['id' => $x['id'], 'codigo' => $x['codigo'], 'nome' => $x['nome'], 'categoria' => $x['categoria']], $catalogo), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        let docIndex = 0;

        function addDoc(catPre = '', nomePre = '') {
            const i = docIndex++;
            const row = document.createElement('div');
            row.className = 'prot-doc-item-card';

            let optionsHtml = catalogo.map(x => `<option value="${x.id}" data-codigo="${x.codigo}" data-nome="${x.nome.replaceAll('"', '&quot;')}" data-cat="${x.categoria}" ${x.codigo === catPre ? 'selected' : ''}>${x.nome}</option>`).join('');

            row.innerHTML = `
                <div>
                    <label class="form-label small text-secondary mb-1">Documento / Descrição</label>
                    <select class="form-control form-control-sm cat-select mb-1">
                        <option value="">-- Outro documento (digite abaixo) --</option>
                        ${optionsHtml}
                    </select>
                    <input class="form-control form-control-sm doc-desc" name="item_descricao[${i}]" required value="${nomePre ? nomePre.replaceAll('"', '&quot;') : ''}" placeholder="Descrição exata do documento...">
                    <input type="hidden" class="cat-id" name="item_catalogo_id[${i}]">
                    <input type="hidden" class="cat-categoria" name="item_categoria[${i}]" value="OUTROS">
                </div>

                <div>
                    <label class="form-label small text-secondary mb-1">Suporte</label>
                    <select class="form-control form-control-sm" name="item_suporte[${i}]">
                        <option value="FISICO">FÍSICO (Impresso)</option>
                        <option value="DIGITAL">DIGITAL (Arquivo)</option>
                    </select>
                </div>

                <div>
                    <label class="form-label small text-secondary mb-1">Forma</label>
                    <select class="form-control form-control-sm" name="item_forma[${i}]">
                        <option value="ORIGINAL">Original</option>
                        <option value="COPIA_SIMPLES">Cópia Simples</option>
                        <option value="COPIA_AUTENTICADA">Cópia Autenticada</option>
                        <option value="NATO_DIGITAL">Nato-Digital (PDF)</option>
                        <option value="DIGITALIZADO">Digitalizado</option>
                    </select>
                </div>

                <div>
                    <label class="form-label small text-secondary mb-1">Qtd</label>
                    <input class="form-control form-control-sm" type="number" min="1" name="item_quantidade[${i}]" value="1">
                </div>

                <div>
                    <label class="form-label small text-secondary mb-1">Revisão / Custódia</label>
                    <input class="form-control form-control-sm mb-1" name="item_revisao[${i}]" placeholder="Nº ou Revisão">
                    <label class="small d-flex align-items-center gap-1 cursor-pointer m-0" style="color: #fbbf24;">
                        <input type="checkbox" name="item_devolucao[${i}]" value="1"> <strong>Exige Devolução</strong>
                    </label>
                </div>

                <div class="text-end">
                    <button class="btn btn-sm btn-outline-danger" type="button" title="Remover item" onclick="this.closest('.prot-doc-item-card').remove()">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            `;

            const catSelect = row.querySelector('.cat-select');
            catSelect.onchange = e => {
                const opt = e.target.selectedOptions[0];
                if (opt && opt.value) {
                    row.querySelector('.cat-id').value = opt.value;
                    row.querySelector('.doc-desc').value = opt.dataset.nome || '';
                    row.querySelector('.cat-categoria').value = opt.dataset.cat || 'OUTROS';
                }
            };
            if (catPre) {
                const opt = catSelect.querySelector(`option[data-codigo="${catPre}"]`);
                if (opt) {
                    row.querySelector('.cat-id').value = opt.value;
                    if (!nomePre) row.querySelector('.doc-desc').value = opt.dataset.nome;
                    row.querySelector('.cat-categoria').value = opt.dataset.cat || 'OUTROS';
                }
            }

            document.getElementById('lista-docs-container').append(row);
        }

        function adicionarDocCatalogo(codigo) {
            const item = catalogo.find(x => x.codigo === codigo);
            if (item) {
                addDoc(codigo, item.nome);
            } else {
                addDoc();
            }
        }

        const btnAdd = document.getElementById('btn-add-doc');
        if (btnAdd) {
            btnAdd.onclick = () => addDoc();
            // Adicionar primeiro item por padrão se vazio
            if (!document.getElementById('lista-docs-container').children.length) {
                addDoc();
            }
        }

        function ajustarSentidoMovimentacao(sentido) {
            const nat = document.getElementById('mov_natureza');
            const origTipo = document.getElementById('origem_tipo');
            const destTipo = document.getElementById('destino_tipo');
            const origNome = document.getElementById('origem_nome');
            const destNome = document.getElementById('destino_nome');

            if (sentido === 'ENTRADA') {
                nat.value = 'RECEBIMENTO_CLIENTE';
                origTipo.value = 'CLIENTE';
                destTipo.value = 'AMAZON_NAVAL';
                origNome.value = '<?= h($d['cliente_nome'] ?: '') ?>';
                destNome.value = 'Amazon Certificadora Naval';
            } else {
                nat.value = 'ENVIO_ORGAO';
                origTipo.value = 'AMAZON_NAVAL';
                destTipo.value = 'CAPITANIA';
                origNome.value = 'Amazon Certificadora Naval';
                destNome.value = '<?= h($d['unidade_nome'] ?: 'Capitania dos Portos') ?>';
            }
        }

        function aoSelecionarUnidadeMaritima(select) {
            const opt = select.selectedOptions[0];
            if (opt && opt.dataset.cidade) {
                document.getElementById('mov_cidade').value = opt.dataset.cidade;
                document.getElementById('mov_uf').value = opt.dataset.uf;
            }
        }

        function abrirModalAceite(token) {
            const url = '<?= APP_URL ?>protocolo-aceite/' + token;
            const modal = document.createElement('div');
            modal.style = 'position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 20px;';
            modal.innerHTML = `
                <div style="background: var(--bg-surface, #071f1b); border: 1px solid var(--accent, #56e0ad); border-radius: 14px; max-width: 580px; width: 100%; padding: 24px;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="m-0 text-accent" style="font-size: 1.25rem;"><i class="fa-solid fa-file-signature"></i> Link de Aceite Digital</h3>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="this.closest('[style*=fixed]').remove()">✕</button>
                    </div>
                    <p class="text-secondary small">Envie este link para assinatura pelo cliente ou portador:</p>
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" id="modal-link-input" value="${url}" readonly>
                        <button class="btn btn-secondary" onclick="navigator.clipboard.writeText('${url}');alert('Copiado!')">Copiar</button>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="https://api.whatsapp.com/send?text=${encodeURIComponent('Olá, segue link de confirmação de recebimento dos documentos: ' + url)}" target="_blank" class="btn btn-success flex-grow-1" style="background:#25d366;border-color:#25d366;color:#fff;">
                            <i class="fa-brands fa-whatsapp"></i> Compartilhar no WhatsApp
                        </a>
                        <button type="button" class="btn btn-secondary" onclick="this.closest('[style*=fixed]').remove()">Fechar</button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }
        </script>
    <?php endif; ?>
</main>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
