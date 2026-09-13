<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/protocolos.php';
protocoloExigirAcesso();

if (getCargo() !== 'ADMIN') {
    setMensagem('error', 'Somente o administrador pode gerenciar os cadastros de apoio dos protocolos.');
    redirecionar(APP_URL . 'protocolos');
}

$abaAtiva = trim($_GET['aba'] ?? 'unidades');

// Processamento de Ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificarCSRF($_POST['csrf_token'] ?? '')) {
        setMensagem('error', 'Sessão expirada. Tente novamente.');
        redirecionar(APP_URL . 'protocolos/configuracoes?aba=' . urlencode($abaAtiva));
    }

    try {
        $a = trim($_POST['action'] ?? '');

        if ($a === 'unidade') {
            $id = trim($_POST['id'] ?? '') ?: gerarUUID();
            $tipo = trim($_POST['tipo'] ?? '');
            if (!in_array($tipo, ['CAPITANIA', 'DELEGACIA', 'AGENCIA'], true)) {
                throw new RuntimeException('Tipo de unidade marítima inválido.');
            }
            $nome = trim($_POST['nome'] ?? '');
            $cidade = trim($_POST['cidade'] ?? '');
            $uf = strtoupper(trim($_POST['uf'] ?? ''));
            if (!$nome || !$cidade || !preg_match('/^[A-Z]{2}$/', $uf)) {
                throw new RuntimeException('Informe o nome da unidade, cidade e UF válida.');
            }

            $q = $pdo->prepare("INSERT INTO protocolo_unidades_maritimas (id, codigo, nome, tipo, cidade, uf, endereco, telefone, email, jurisdicao, url_consulta, ativo, criado_por)
                VALUES (:id, :codigo, :nome, :tipo, :cidade, :uf, :endereco, :telefone, :email, :jurisdicao, :url, 1, :usuario)
                ON DUPLICATE KEY UPDATE 
                    codigo = VALUES(codigo), nome = VALUES(nome), tipo = VALUES(tipo), cidade = VALUES(cidade), uf = VALUES(uf),
                    endereco = VALUES(endereco), telefone = VALUES(telefone), email = VALUES(email), jurisdicao = VALUES(jurisdicao),
                    url_consulta = VALUES(url_consulta), ativo = 1");
            $q->execute([
                ':id' => $id,
                ':codigo' => trim($_POST['codigo'] ?? '') ?: null,
                ':nome' => $nome,
                ':tipo' => $tipo,
                ':cidade' => $cidade,
                ':uf' => $uf,
                ':endereco' => trim($_POST['endereco'] ?? '') ?: null,
                ':telefone' => trim($_POST['telefone'] ?? '') ?: null,
                ':email' => trim($_POST['email'] ?? '') ?: null,
                ':jurisdicao' => trim($_POST['jurisdicao'] ?? '') ?: null,
                ':url' => trim($_POST['url_consulta'] ?? '') ?: null,
                ':usuario' => $_SESSION['usuario_id']
            ]);
            setMensagem('success', 'Unidade marítima salva com sucesso.');
            $abaAtiva = 'unidades';
        } elseif ($a === 'catalogo') {
            $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
            $nome = trim($_POST['nome'] ?? '');
            $cat = trim($_POST['categoria'] ?? 'OUTROS');
            $cats = ['ANALISE_PLANOS', 'VISTORIA', 'INSCRICAO', 'CERTIFICADOS', 'PROPRIEDADE', 'RESPONSABILIDADE_TECNICA', 'DOCUMENTOS_PESSOAIS', 'OUTROS'];
            if (!$codigo || !preg_match('/^[A-Z0-9_]+$/', $codigo) || !$nome || !in_array($cat, $cats, true)) {
                throw new RuntimeException('Informe um código sem espaços, a categoria e a descrição do documento.');
            }

            $q = $pdo->prepare('INSERT INTO protocolo_catalogo_documentos (id, codigo, categoria, nome, contexto, norma_referencia, ativo, ordem)
                VALUES (UUID(), :codigo, :cat, :nome, :contexto, :norma, 1, :ordem)
                ON DUPLICATE KEY UPDATE 
                    categoria = VALUES(categoria), nome = VALUES(nome), contexto = VALUES(contexto),
                    norma_referencia = VALUES(norma_referencia), ativo = 1, ordem = VALUES(ordem)');
            $q->execute([
                ':codigo' => $codigo,
                ':cat' => $cat,
                ':nome' => $nome,
                ':contexto' => trim($_POST['contexto'] ?? '') ?: null,
                ':norma' => trim($_POST['norma_referencia'] ?? '') ?: null,
                ':ordem' => (int)($_POST['ordem'] ?? 0)
            ]);
            setMensagem('success', 'Documento adicionado ao catálogo com sucesso.');
            $abaAtiva = 'catalogo';
        } elseif ($a === 'alertas') {
            $q = $pdo->prepare('UPDATE protocolo_configuracoes SET valor = :valor, atualizado_por = :usuario WHERE chave = :chave');
            foreach (['dias_sem_documento', 'dias_sem_registro_orgao', 'dias_alerta_validade'] as $chave) {
                $valor = max(1, min(365, (int)($_POST[$chave] ?? 0)));
                $q->execute([':valor' => (string)$valor, ':usuario' => $_SESSION['usuario_id'], ':chave' => $chave]);
            }
            setMensagem('success', 'Parâmetros de alerta atualizados com sucesso.');
            $abaAtiva = 'alertas';
        } elseif ($a === 'seed_unidades') {
            // Auto-popula as principais Capitanias da Amazônia se não existirem
            $unidadesPadrao = [
                ['codigo' => 'DelSantarem', 'nome' => 'Delegacia da Capitania dos Portos em Santarém', 'tipo' => 'DELEGACIA', 'cidade' => 'Santarém', 'uf' => 'PA', 'url' => 'https://atendimento-dpc.marinha.mil.br/sisap/agendamento/consultaprocesso/#/'],
                ['codigo' => 'CPAOC', 'nome' => 'Capitania Fluvial da Amazônia Ocidental', 'tipo' => 'CAPITANIA', 'cidade' => 'Manaus', 'uf' => 'AM', 'url' => 'https://atendimento-dpc.marinha.mil.br/sisap/agendamento/consultaprocesso/#/'],
                ['codigo' => 'CPAP', 'nome' => 'Capitania dos Portos do Amapá', 'tipo' => 'CAPITANIA', 'cidade' => 'Santana', 'uf' => 'AP', 'url' => 'https://atendimento-dpc.marinha.mil.br/sisap/agendamento/consultaprocesso/#/'],
                ['codigo' => 'DelItacoatiara', 'nome' => 'Delegacia Fluvial de Itacoatiara', 'tipo' => 'DELEGACIA', 'cidade' => 'Itacoatiara', 'uf' => 'AM', 'url' => 'https://atendimento-dpc.marinha.mil.br/sisap/agendamento/consultaprocesso/#/'],
                ['codigo' => 'AgBreves', 'nome' => 'Agência Fluvial de Breves', 'tipo' => 'AGENCIA', 'cidade' => 'Breves', 'uf' => 'PA', 'url' => 'https://atendimento-dpc.marinha.mil.br/sisap/agendamento/consultaprocesso/#/']
            ];
            $ins = $pdo->prepare("INSERT IGNORE INTO protocolo_unidades_maritimas (id, codigo, nome, tipo, cidade, uf, url_consulta, ativo, criado_por) VALUES (UUID(), :codigo, :nome, :tipo, :cidade, :uf, :url, 1, :usuario)");
            foreach ($unidadesPadrao as $up) {
                $ins->execute([':codigo' => $up['codigo'], ':nome' => $up['nome'], ':tipo' => $up['tipo'], ':cidade' => $up['cidade'], ':uf' => $up['uf'], ':url' => $up['url'], ':usuario' => $_SESSION['usuario_id']]);
            }
            setMensagem('success', 'Capitanias e Delegacias regionais da Amazônia importadas com sucesso.');
            $abaAtiva = 'unidades';
        }
    } catch (Throwable $e) {
        setMensagem('error', $e->getMessage());
    }
    redirecionar(APP_URL . 'protocolos/configuracoes?aba=' . urlencode($abaAtiva));
}

$unidades = $pdo->query('SELECT * FROM protocolo_unidades_maritimas ORDER BY ativo DESC, tipo, nome')->fetchAll(PDO::FETCH_ASSOC);
$docs = $pdo->query('SELECT * FROM protocolo_catalogo_documentos ORDER BY categoria, ordem, nome')->fetchAll(PDO::FETCH_ASSOC);
try {
    $alertas = $pdo->query('SELECT chave, valor FROM protocolo_configuracoes')->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Throwable $e) {
    $alertas = [];
}

$titulo_page = 'Cadastros de Apoio - Protocolos';
require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/sidebar.php';
?>

<main class="conteudo-principal">
    <div class="prot-page-header">
        <div class="prot-page-title">
            <h1><i class="fa-solid fa-gear text-accent"></i> Cadastros de Apoio aos Protocolos</h1>
            <p>Gerencie as Capitanias/Delegacias marítimas, o catálogo sugerido de documentos navais e as regras de prazos.</p>
        </div>
        <a class="btn btn-secondary" href="<?= APP_URL ?>protocolos">
            <i class="fa-solid fa-arrow-left"></i> Voltar aos Protocolos
        </a>
    </div>

    <!-- Abas de Configurações -->
    <div class="prot-tabs-bar mb-4">
        <a href="<?= APP_URL ?>protocolos/configuracoes?aba=unidades" class="prot-tab-btn <?= $abaAtiva === 'unidades' ? 'active' : '' ?>">
            <i class="fa-solid fa-building-flag"></i> Capitanias & Unidades Marítimas
            <span class="badge bg-secondary"><?= count($unidades) ?></span>
        </a>
        <a href="<?= APP_URL ?>protocolos/configuracoes?aba=catalogo" class="prot-tab-btn <?= $abaAtiva === 'catalogo' ? 'active' : '' ?>">
            <i class="fa-solid fa-book-bookmark"></i> Catálogo de Documentos Sugeridos
            <span class="badge bg-secondary"><?= count($docs) ?></span>
        </a>
        <a href="<?= APP_URL ?>protocolos/configuracoes?aba=alertas" class="prot-tab-btn <?= $abaAtiva === 'alertas' ? 'active' : '' ?>">
            <i class="fa-solid fa-bell"></i> Prazos & Alertas
        </a>
    </div>

    <!-- ABA 1: UNIDADES MARÍTIMAS -->
    <?php if ($abaAtiva === 'unidades'): ?>
        <div class="row g-4">
            <div class="col-md-5">
                <section class="card">
                    <div class="card-body">
                        <h3 style="font-size: 1.15rem; color: var(--accent, #56e0ad);" class="mb-3">
                            <i class="fa-solid fa-plus-circle"></i> Nova Capitania / Delegacia
                        </h3>

                        <form method="post" action="<?= APP_URL ?>protocolos/configuracoes?aba=unidades">
                            <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>">
                            <input type="hidden" name="action" value="unidade">
                            <input type="hidden" name="id" id="form-unidade-id" value="">

                            <div class="row g-2 mb-2">
                                <div class="col-4">
                                    <label class="form-label small fw-bold">Sigla / Cód.</label>
                                    <input class="form-control form-control-sm" name="codigo" id="u-codigo" placeholder="Ex: CPAOR">
                                </div>
                                <div class="col-8">
                                    <label class="form-label small fw-bold">Tipo *</label>
                                    <select class="form-control form-control-sm" name="tipo" id="u-tipo" required>
                                        <option value="CAPITANIA">CAPITANIA DOS PORTOS</option>
                                        <option value="DELEGACIA">DELEGACIA FLUVIAL/MARÍTIMA</option>
                                        <option value="AGENCIA">AGÊNCIA DA CAPITANIA</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-2">
                                <label class="form-label small fw-bold">Nome da Unidade Marítima *</label>
                                <input class="form-control form-control-sm" name="nome" id="u-nome" required placeholder="Ex.: Capitania dos Portos da Amazônia Oriental">
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-8">
                                    <label class="form-label small fw-bold">Cidade *</label>
                                    <input class="form-control form-control-sm" name="cidade" id="u-cidade" required placeholder="Belém">
                                </div>
                                <div class="col-4">
                                    <label class="form-label small fw-bold">UF *</label>
                                    <input class="form-control form-control-sm" name="uf" id="u-uf" required maxlength="2" placeholder="PA">
                                </div>
                            </div>

                            <div class="mb-2">
                                <label class="form-label small fw-bold">Endereço da Sede</label>
                                <input class="form-control form-control-sm" name="endereco" id="u-endereco" placeholder="Rua / Avenida, Bairro">
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label small fw-bold">Telefone</label>
                                    <input class="form-control form-control-sm" name="telefone" id="u-telefone" placeholder="(91) 3216-4900">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-bold">E-mail de Contato</label>
                                    <input class="form-control form-control-sm" type="email" name="email" id="u-email" placeholder="cpaor.protocolo@marinha.mil.br">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold">URL do Portal de Consulta de Processo</label>
                                <input class="form-control form-control-sm" type="url" name="url_consulta" id="u-url" 
                                       value="https://atendimento-dpc.marinha.mil.br/sisap/agendamento/consultaprocesso/#/" placeholder="https://...">
                                <small class="text-muted">Link do SISAP ou portal regional da Capitania.</small>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                                    <i class="fa-solid fa-save"></i> Salvar Unidade
                                </button>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="limparFormUnidade()">Limpar</button>
                            </div>
                        </form>

                        <hr style="border-color: var(--border);">
                        
                        <form method="post" action="<?= APP_URL ?>protocolos/configuracoes?aba=unidades" onsubmit="return confirm('Deseja importar automaticamente as principais Capitanias e Delegacias da região Amazônica (Santarém, Manaus, Macapá, etc.)?')">
                            <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>">
                            <input type="hidden" name="action" value="seed_unidades">
                            <button type="submit" class="btn btn-outline-info btn-sm w-100">
                                <i class="fa-solid fa-cloud-arrow-down"></i> Importar Capitanias da Amazônia
                            </button>
                        </form>
                    </div>
                </section>
            </div>

            <div class="col-md-7">
                <section class="card">
                    <div class="card-body p-0">
                        <div class="p-3 border-bottom" style="border-color: var(--border) !important;">
                            <h3 style="font-size: 1.15rem; margin: 0;">
                                <i class="fa-solid fa-list"></i> Unidades Marítimas Cadastradas (<?= count($unidades) ?>)
                            </h3>
                        </div>
                        <div class="table-responsive">
                            <table class="table mb-0" style="font-size: 0.88rem;">
                                <thead>
                                    <tr style="border-bottom: 2px solid var(--border);">
                                        <th>Unidade / Nome</th>
                                        <th>Tipo</th>
                                        <th>Localidade</th>
                                        <th>Portal SISAP</th>
                                        <th style="text-align: right;">Ação</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($unidades as $u): ?>
                                        <tr style="border-bottom: 1px solid var(--border); vertical-align: middle;">
                                            <td>
                                                <strong><?= h($u['nome']) ?></strong>
                                                <?php if ($u['codigo']): ?>
                                                    <span class="badge bg-dark ms-1"><?= h($u['codigo']) ?></span>
                                                <?php endif; ?>
                                                <?php if ($u['telefone']): ?>
                                                    <div class="text-secondary small"><?= h($u['telefone']) ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td><span class="badge bg-secondary"><?= h($u['tipo']) ?></span></td>
                                            <td><?= h($u['cidade'] . '/' . $u['uf']) ?></td>
                                            <td>
                                                <?php if ($u['url_consulta']): ?>
                                                    <a href="<?= h($u['url_consulta']) ?>" target="_blank" rel="noopener" class="btn btn-xs btn-outline-info">
                                                        <i class="fa-solid fa-external-link"></i> SISAP
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="text-align: right;">
                                                <button type="button" class="btn btn-sm btn-secondary" onclick='editarUnidade(<?= json_encode($u, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>)'>
                                                    <i class="fa-solid fa-pen"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <script>
        function editarUnidade(u) {
            document.getElementById('form-unidade-id').value = u.id || '';
            document.getElementById('u-codigo').value = u.codigo || '';
            document.getElementById('u-tipo').value = u.tipo || 'CAPITANIA';
            document.getElementById('u-nome').value = u.nome || '';
            document.getElementById('u-cidade').value = u.cidade || '';
            document.getElementById('u-uf').value = u.uf || '';
            document.getElementById('u-endereco').value = u.endereco || '';
            document.getElementById('u-telefone').value = u.telefone || '';
            document.getElementById('u-email').value = u.email || '';
            document.getElementById('u-url').value = u.url_consulta || '';
            document.getElementById('u-nome').focus();
        }
        function limparFormUnidade() {
            document.getElementById('form-unidade-id').value = '';
            document.getElementById('u-codigo').value = '';
            document.getElementById('u-tipo').value = 'CAPITANIA';
            document.getElementById('u-nome').value = '';
            document.getElementById('u-cidade').value = '';
            document.getElementById('u-uf').value = '';
            document.getElementById('u-endereco').value = '';
            document.getElementById('u-telefone').value = '';
            document.getElementById('u-email').value = '';
        }
        </script>

    <!-- ABA 2: CATÁLOGO DE DOCUMENTOS -->
    <?php elseif ($abaAtiva === 'catalogo'): ?>
        <div class="row g-4">
            <div class="col-md-5">
                <section class="card">
                    <div class="card-body">
                        <h3 style="font-size: 1.15rem; color: var(--accent, #56e0ad);" class="mb-3">
                            <i class="fa-solid fa-plus-circle"></i> Novo Documento no Catálogo
                        </h3>
                        <p class="text-secondary small">Cadastre pranchas e documentos padrão da NORMAM-202 ou documentos de identificação para preenchimento rápido em movimentações.</p>

                        <form method="post" action="<?= APP_URL ?>protocolos/configuracoes?aba=catalogo">
                            <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>">
                            <input type="hidden" name="action" value="catalogo">

                            <div class="mb-2">
                                <label class="form-label small fw-bold">Código Estável (letras maiúsculas e underline) *</label>
                                <input class="form-control form-control-sm" required name="codigo" placeholder="Ex.: PLANO_COMBATE_INCENDIO">
                            </div>

                            <div class="mb-2">
                                <label class="form-label small fw-bold">Nome do Documento / Prancha *</label>
                                <input class="form-control form-control-sm" required name="nome" placeholder="Ex.: Plano de Combate a Incêndio">
                            </div>

                            <div class="mb-2">
                                <label class="form-label small fw-bold">Categoria *</label>
                                <select class="form-control form-control-sm" name="categoria" required>
                                    <option value="ANALISE_PLANOS">Projetos & Planos Navais (NORMAM-202)</option>
                                    <option value="RESPONSABILIDADE_TECNICA">Responsabilidade Técnica (ART/RRT)</option>
                                    <option value="VISTORIA">Relatórios de Vistoria</option>
                                    <option value="INSCRICAO">Inscrição & Registro (TIE/TIEM)</option>
                                    <option value="CERTIFICADOS">Certificados & Licenças</option>
                                    <option value="PROPRIEDADE">Documentos de Propriedade</option>
                                    <option value="DOCUMENTOS_PESSOAIS">Documentos Pessoais & Procurações</option>
                                    <option value="OUTROS">Outros Documentos</option>
                                </select>
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label small fw-bold">Norma de Referência</label>
                                    <input class="form-control form-control-sm" name="norma_referencia" value="NORMAM-202/DPC" placeholder="NORMAM-202/DPC">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-bold">Ordem de Exibição</label>
                                    <input class="form-control form-control-sm" type="number" name="ordem" value="10">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold">Contexto / Serviço</label>
                                <input class="form-control form-control-sm" name="contexto" placeholder="Ex.: LC, LA, LR, Geral">
                            </div>

                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                <i class="fa-solid fa-save"></i> Salvar no Catálogo
                            </button>
                        </form>
                    </div>
                </section>
            </div>

            <div class="col-md-7">
                <section class="card">
                    <div class="card-body p-0">
                        <div class="p-3 border-bottom" style="border-color: var(--border) !important;">
                            <h3 style="font-size: 1.15rem; margin: 0;">
                                <i class="fa-solid fa-folder-open"></i> Itens do Catálogo (<?= count($docs) ?>)
                            </h3>
                        </div>
                        <div class="table-responsive" style="max-height: 540px; overflow-y: auto;">
                            <table class="table mb-0" style="font-size: 0.88rem;">
                                <thead>
                                    <tr style="border-bottom: 2px solid var(--border);">
                                        <th>Documento</th>
                                        <th>Categoria</th>
                                        <th>Código</th>
                                        <th>Norma</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($docs as $doc): ?>
                                        <tr style="border-bottom: 1px solid var(--border); vertical-align: middle;">
                                            <td><strong><?= h($doc['nome']) ?></strong></td>
                                            <td><span class="badge bg-secondary"><?= h($doc['categoria']) ?></span></td>
                                            <td><code><?= h($doc['codigo']) ?></code></td>
                                            <td><small class="text-secondary"><?= h($doc['norma_referencia'] ?: '—') ?></small></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </div>
        </div>

    <!-- ABA 3: ALERTAS E PRAZOS -->
    <?php elseif ($abaAtiva === 'alertas'): ?>
        <div class="row justify-content-center">
            <div class="col-md-8">
                <section class="card">
                    <div class="card-body">
                        <h3 style="font-size: 1.2rem; color: var(--accent, #56e0ad);" class="mb-3">
                            <i class="fa-solid fa-bell"></i> Configuração de Prazos e Alertas Automáticos
                        </h3>
                        <p class="text-secondary small">Defina o tempo limite para que o sistema gere alertas operacionais e notificações preventivas no painel dos administradores e analistas.</p>

                        <form method="post" action="<?= APP_URL ?>protocolos/configuracoes?aba=alertas">
                            <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>">
                            <input type="hidden" name="action" value="alertas">

                            <div class="mb-3">
                                <label class="form-label fw-bold">Dias sem documento anexado após uma SAÍDA:</label>
                                <input class="form-control" type="number" min="1" max="365" name="dias_sem_documento" 
                                       value="<?= h($alertas['dias_sem_documento'] ?? $alertas['dias_sem_comprovante'] ?? 3) ?>">
                                <small class="text-muted">Gera alerta se uma saída para o órgão ou cliente foi registrada há mais de X dias sem que um recibo digitalizado ou comprovante de entrega tenha sido anexado.</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Dias sem registro do número oficial da Marinha após envio:</label>
                                <input class="form-control" type="number" min="1" max="365" name="dias_sem_registro_orgao" 
                                       value="<?= h($alertas['dias_sem_registro_orgao'] ?? $alertas['dias_sem_protocolo_oficial'] ?? 3) ?>">
                                <small class="text-muted">Gera alerta se o dossiê foi enviado à Capitania há mais de X dias mas o número do protocolo do SISAP ainda não foi cadastrado.</small>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">Antecedência para alerta de vencimento da validade do protocolo:</label>
                                <div class="input-group">
                                    <input class="form-control" type="number" min="1" max="365" name="dias_alerta_validade" 
                                           value="<?= h($alertas['dias_alerta_validade'] ?? 15) ?>">
                                    <span class="input-group-text">dias antes</span>
                                </div>
                                <small class="text-muted">Alerta no painel e na listagem com badge amarela quando a validade do protocolo provisório estiver a menos de X dias de expirar.</small>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fa-solid fa-save"></i> Salvar Parâmetros de Alertas
                            </button>
                        </form>
                    </div>
                </section>
            </div>
        </div>
    <?php endif; ?>
</main>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
