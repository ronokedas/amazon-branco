<?php
/**
 * Módulo de Protocolos e Dossiês Navais - Ficha e Acompanhamento
 * Arquivo principal reestruturado em abas modulares especializadas (AGENTS.md).
 * Componentes em: modules/protocolos/components/
 */

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
$certificadoVinculado = null;
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

    // Consulta de certificado vinculado se presente
    if (!empty($d['certificado_tipo']) && !empty($d['certificado_id'])) {
        $tabelaCert = match (strtoupper($d['certificado_tipo'])) {
            'CSN' => 'certificados_csn',
            'CNBL' => 'certificados_cnbl',
            'CNARQ' => 'certificados_cnarq',
            'LP' => 'certificados_lp',
            'LC' => 'certificados_lc',
            'CHT' => 'certificados_cht',
            default => null
        };
        if ($tabelaCert) {
            try {
                $qc = $pdo->prepare("SELECT id, numero, status, data_emissao, data_validade FROM {$tabelaCert} WHERE id = :cid OR numero = :cnum LIMIT 1");
                $qc->execute([':cid' => $d['certificado_id'], ':cnum' => $d['certificado_id']]);
                $certificadoVinculado = $qc->fetch(PDO::FETCH_ASSOC) ?: null;
            } catch (Throwable $e) {
                // Tabela pode não estar disponível
            }
        }
    }
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
        <div class="d-flex gap-2 flex-wrap">
            <a class="btn btn-secondary" href="<?= APP_URL ?>protocolos">
                <i class="fa-solid fa-arrow-left"></i> Voltar à Lista
            </a>
            <?php if ($d): ?>
                <?php
                $chaveProc = 'protocolo_externo_' . 'numero';
                $procNum = $d[$chaveProc] ?? '';
                $msgWhatsStatus = rawurlencode("Olá! Informamos que o processo da embarcação *" . $d['embarcacao_nome'] . "* (Dossiê " . $d['numero'] . ") está em andamento na " . ($d['unidade_nome'] ?: 'Capitania/Delegacia') . ".\nSituação atual: *" . ($labels[$d['status']] ?? $d['status']) . "*" . ($procNum ? "\nNº Oficial no Órgão: *" . $procNum . "*" : "") . "\n\nAmazon Certificadora Naval");
                ?>
                <a class="btn btn-success" target="_blank" rel="noopener" href="https://api.whatsapp.com/send?text=<?= $msgWhatsStatus ?>" style="background: #25d366; border-color: #25d366; color: #fff;" title="Avisar cliente/armador via WhatsApp">
                    <i class="fa-brands fa-whatsapp"></i> Notificar Cliente
                </a>
                <a class="btn btn-primary" target="_blank" href="<?= APP_URL ?>protocolos/pdf-dossie?id=<?= urlencode($id) ?>">
                    <i class="fa-solid fa-file-pdf"></i> PDF Consolidado
                </a>
                <?php if (!$somenteLeitura): ?>
                    <button type="button" class="btn btn-outline-secondary" onclick="const p = document.getElementById('painel-edicao-dossie'); p.classList.toggle('show');">
                        <i class="fa-solid fa-sliders"></i> Editar Vínculos
                    </button>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!$d): ?>
        <!-- Modo de Criação -->
        <?php require __DIR__ . '/components/dossie_identificacao.php'; ?>

    <?php else: ?>
        <!-- Modo de Visualização / Gestão do Dossiê Existente -->

        <!-- Painel Retrátil de Edição de Vínculos -->
        <?php require __DIR__ . '/components/dossie_identificacao.php'; ?>

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
                        <?php if ($d['protocolo_externo_validade']): 
                            $diasVal = (int)ceil((strtotime($d['protocolo_externo_validade']) - time()) / 86400);
                            $badgeStyle = $diasVal < 0 ? 'background: #ef4444; color: #fff;' : ($diasVal <= 7 ? 'background: #f59e0b; color: #000;' : 'background: rgba(255,255,255,0.1); color: var(--text-primary);');
                        ?>
                            <div class="badge p-2 mb-2 d-inline-block text-start" style="<?= $badgeStyle ?> font-size: 0.82rem; border-radius: 8px;">
                                <i class="fa-solid <?= $diasVal < 0 ? 'fa-triangle-exclamation' : ($diasVal <= 7 ? 'fa-hourglass-half' : 'fa-calendar-check') ?>"></i>
                                Prazo no Órgão: <strong><?= date('d/m/Y', strtotime($d['protocolo_externo_validade'])) ?></strong>
                                <?php if ($diasVal < 0): ?>
                                    <div class="fw-bold mt-1">⚠️ Prazo expirou há <?= abs($diasVal) ?> dia(s)!</div>
                                <?php elseif ($diasVal === 0): ?>
                                    <div class="fw-bold mt-1">🚨 Prazo vence HOJE!</div>
                                <?php elseif ($diasVal <= 7): ?>
                                    <div class="fw-bold mt-1">⏳ Faltam <?= $diasVal ?> dia(s) para o limite</div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($certificadoVinculado || (!empty($d['certificado_tipo']) && !empty($d['certificado_id']))): ?>
                            <div class="mt-1">
                                <span class="badge bg-info text-dark" title="Certificado emitido conectado a este dossiê">
                                    <i class="fa-solid fa-certificate"></i> <?= h($d['certificado_tipo']) ?>: <?= h($certificadoVinculado['numero'] ?? $d['certificado_id']) ?>
                                </span>
                            </div>
                        <?php endif; ?>

                        <?php if ($d['url_consulta']): ?>
                            <div class="mt-1">
                                <a target="_blank" rel="noopener" class="btn btn-sm btn-outline-info" href="<?= h($d['url_consulta']) ?>">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i> Portal SISAP
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <?php if ($d['status'] === 'EM_EXIGENCIA'): ?>
            <div class="prot-helper-box warning mb-4" style="border-left: 5px solid #f59e0b; background: rgba(245, 158, 11, 0.12); padding: 16px 20px; border-radius: 10px;">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="fa-solid fa-triangle-exclamation text-warning fs-5"></i>
                            <strong style="color: #f59e0b; font-size: 1.05rem;">Processo em Exigência na Autoridade Marítima</strong>
                        </div>
                        <p class="mb-0 text-secondary small">A Capitania/Delegacia apontou pendências ou notas técnicas. Atente-se ao prazo limite para cumprimento para evitar cancelamento ou arquivamento do processo.</p>
                    </div>
                    <?php if (!$somenteLeitura): ?>
                        <button type="button" class="btn btn-warning text-dark fw-bold" onclick="trocarAbaDossie('movimentacao'); document.getElementById('mov_natureza').value='CUMPRIMENTO_EXIGENCIA';">
                            <i class="fa-solid fa-arrow-right"></i> Cumprir Exigência Agora
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Navegação em Abas do Dossiê (Padrão AGENTS.md) -->
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

        <!-- Componente 2: Linha do Tempo e Movimentações Guiadas -->
        <?php require __DIR__ . '/components/movimentacoes_historico.php'; ?>

        <!-- Componente 3: Trâmite Oficial na Capitania / SISAP -->
        <?php require __DIR__ . '/components/tramite_oficial.php'; ?>

        <!-- Componente 4: Termo de Custódia de Originais Físicos -->
        <?php require __DIR__ . '/components/custodia_originais.php'; ?>

        <!-- Componente 5: Repositório de Anexos e Trilha de Auditoria -->
        <?php require __DIR__ . '/components/auditoria_aceite.php'; ?>

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
            const container = document.getElementById('lista-docs-container');
            if (!container) return;
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

            container.append(row);
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
            const container = document.getElementById('lista-docs-container');
            if (container && !container.children.length) {
                addDoc();
            }
        }

        function ajustarSentidoMovimentacao(sentido) {
            const nat = document.getElementById('mov_natureza');
            const origTipo = document.getElementById('origem_tipo');
            const destTipo = document.getElementById('destino_tipo');
            const origNome = document.getElementById('origem_nome');
            const destNome = document.getElementById('destino_nome');
            if (!nat) return;

            if (sentido === 'ENTRADA') {
                nat.value = 'RECEBIMENTO_CLIENTE';
                if (origTipo) origTipo.value = 'CLIENTE';
                if (destTipo) destTipo.value = 'AMAZON_NAVAL';
                if (origNome) origNome.value = '<?= h($d['cliente_nome'] ?: '') ?>';
                if (destNome) destNome.value = 'Amazon Certificadora Naval';
            } else {
                nat.value = 'ENVIO_ORGAO';
                if (origTipo) origTipo.value = 'AMAZON_NAVAL';
                if (destTipo) destTipo.value = 'CAPITANIA';
                if (origNome) origNome.value = 'Amazon Certificadora Naval';
                if (destNome) destNome.value = '<?= h($d['unidade_nome'] ?: 'Capitania dos Portos') ?>';
            }
        }

        function aoSelecionarUnidadeMaritima(select) {
            const opt = select.selectedOptions[0];
            if (opt && opt.dataset.cidade) {
                const c = document.getElementById('mov_cidade');
                const u = document.getElementById('mov_uf');
                if (c) c.value = opt.dataset.cidade;
                if (u) u.value = opt.dataset.uf;
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
