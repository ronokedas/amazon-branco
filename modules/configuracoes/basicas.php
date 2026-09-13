<?php
/**
 * MODULO: CONFIGURACOES
 * Arquivo: basicas.php - Matriz de permissões individuais por usuário e cargo
 * Acesso: exclusivo de administradores
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
exigirAcesso('configuracoes');

// Catálogo organizado por categorias operacionais
$categoriasPermissoes = [
    'OPERACAO' => [
        'titulo' => 'Operação Naval & Técnica',
        'icone' => 'fa-solid fa-ship',
        'itens' => [
            'dashboard' => ['Dashboard', 'Visão geral e atalhos operacionais'],
            'vistorias' => ['Vistorias', 'Execução e consulta de ordens de vistoria'],
            'agendamentos' => ['Agendamentos & OS', 'Agenda e emissão de ordens de serviço'],
            'analise_planos' => ['Análise de Planos', 'Projetos navais, estabilidade e arqueação'],
            'relatorios_aprovacao' => ['Relatórios em Aprovação', 'Pareceres técnicos de vistorias pendentes'],
            'protocolos_documentais' => ['Protocolos Documentais', 'Dossiês, custódia e trâmite na Capitania'],
            'certificados' => ['Certificados', 'Consulta e emissão de certificados navais'],
            'documentacao' => ['Documentação', 'Workspace de laudos, plantas e arquivos'],
        ]
    ],
    'CADASTROS' => [
        'titulo' => 'Cadastros da Frota & Clientes',
        'icone' => 'fa-solid fa-folder-open',
        'itens' => [
            'embarcacoes' => ['Embarcações', 'Cadastro e dados técnicos das embarcações'],
            'armadores' => ['Armadores', 'Cadastro de armadores e empresas de navegação'],
            'proprietarios' => ['Proprietários', 'Cadastro de proprietários e operadores'],
            'despachantes' => ['Despachantes', 'Cadastro de despachantes marítimos parceiros'],
        ]
    ],
    'COMERCIAL' => [
        'titulo' => 'Comercial & Financeiro',
        'icone' => 'fa-solid fa-file-invoice-dollar',
        'itens' => [
            'comercial' => ['Comercial / Propostas', 'Elaboração e envio de orçamentos e propostas'],
            'servicos' => ['Serviços & Catálogo', 'Tabelas de preços e catálogo de serviços'],
            'financeiro' => ['Financeiro', 'Lançamentos de contas a pagar e receber'],
            'emails' => ['E-mails', 'Central de disparos e histórico de mensagens'],
            'portal_clientes' => ['Portal de Clientes', 'Gestão de acessos dos clientes externos'],
            'relatorios' => ['Relatórios Gerenciais', 'Consultas consolidadas e métricas do sistema'],
        ]
    ],
    'SISTEMA' => [
        'titulo' => 'Qualidade & Sistema',
        'icone' => 'fa-solid fa-sliders',
        'itens' => [
            'sgq' => ['Qualidade (SGQ)', 'Manual, RNCs, ouvidoria e auditoria ISO'],
            'usuarios' => ['Usuários & Equipe', 'Gestão de colaboradores e senhas'],
            'configuracoes' => ['Configurações Gerais', 'Parâmetros do sistema e banco de dados'],
            'responsaveis_assinatura' => ['Responsáveis por Assinatura', 'Credenciais para certificados navais'],
        ]
    ]
];

// Lista linear de todas as permissões para validação e persistência
$permissoes = [];
foreach ($categoriasPermissoes as $cat) {
    foreach ($cat['itens'] as $chave => $dados) {
        $permissoes[$chave] = $dados;
    }
}

// Garantir que a tabela usuario_permissoes existe
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS usuario_permissoes (
        usuario_id CHAR(36) NOT NULL,
        permissao VARCHAR(80) NOT NULL,
        permitido TINYINT(1) NOT NULL DEFAULT 0,
        atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (usuario_id, permissao),
        CONSTRAINT fk_usuario_permissoes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
} catch (Throwable $e) {
    setMensagem('error', 'Não foi possível preparar o controle de permissões.');
    redirecionar(APP_URL . 'configuracoes');
}

// Processar formulário de atualização de permissões
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificarCSRF($_POST['csrf_token'] ?? '')) {
        setMensagem('error', 'Token de segurança inválido.');
        redirecionar(APP_URL . 'configuracoes/basicas');
    }

    $selecionadas = $_POST['permissoes'] ?? [];

    try {
        $ids = $pdo->query("SELECT id FROM usuarios WHERE cargo != 'ADMIN' AND excluido_em IS NULL")->fetchAll(PDO::FETCH_COLUMN);
        $pdo->beginTransaction();

        $upsert = $pdo->prepare('INSERT INTO usuario_permissoes (usuario_id, permissao, permitido) 
            VALUES (:usuario_id, :permissao, :permitido) 
            ON DUPLICATE KEY UPDATE permitido = VALUES(permitido)');

        foreach ($ids as $id) {
            $permitidas = array_flip(array_filter($selecionadas[$id] ?? [], fn($chave) => isset($permissoes[$chave])));
            foreach (array_keys($permissoes) as $chave) {
                $upsert->execute([
                    ':usuario_id' => $id,
                    ':permissao'  => $chave,
                    ':permitido'  => isset($permitidas[$chave]) ? 1 : 0
                ]);
            }
        }

        $pdo->commit();
        setMensagem('success', 'Permissões de todos os funcionários atualizadas com sucesso!');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Erro ao salvar permissões: ' . $e->getMessage());
        setMensagem('error', 'Erro ao salvar permissões no banco de dados.');
    }

    redirecionar(APP_URL . 'configuracoes/basicas');
}

// Carregar usuários e permissões salvas
try {
    $usuarios = $pdo->query("SELECT id, nome, email, cargo, ativo FROM usuarios WHERE cargo != 'ADMIN' AND excluido_em IS NULL ORDER BY cargo, nome ASC")->fetchAll(PDO::FETCH_ASSOC);
    $linhas = $pdo->query('SELECT usuario_id, permissao, permitido FROM usuario_permissoes')->fetchAll(PDO::FETCH_ASSOC);
    
    $acessos = [];
    foreach ($linhas as $linha) {
        $acessos[$linha['usuario_id']][$linha['permissao']] = ((int)$linha['permitido'] === 1);
    }
} catch (Throwable $e) {
    $usuarios = [];
    $acessos = [];
}

$titulo_page = 'Permissões de Usuários - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div class="welcome-section" style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
        <div>
            <h1 style="margin: 0 0 6px;"><i class="fas fa-shield-halved text-accent"></i> Permissões de Acesso por Usuário</h1>
            <p style="margin: 0; color: var(--cor-texto-secundario);">
                Controle granular de acesso a módulos e telas operacionais para cada colaborador.
            </p>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="<?= APP_URL ?>configuracoes" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Voltar a Configurações
            </a>
            <a href="<?= APP_URL ?>usuarios/form" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> Novo Funcionário
            </a>
        </div>
    </div>

    <!-- Caixa Didática de Orientações -->
    <div class="prot-helper-box info mb-4" style="background: rgba(13, 110, 253, 0.08); border-left: 4px solid #0d6efd; padding: 16px 20px; border-radius: 8px;">
        <div class="d-flex align-items-center gap-2 mb-1">
            <i class="fa-solid fa-circle-info text-primary fs-5"></i>
            <strong style="color: var(--cor-texto);">Regras de Segurança & Visibilidade do Sistema:</strong>
        </div>
        <ul style="margin: 8px 0 0; padding-left: 20px; font-size: 0.9rem; color: var(--cor-texto-secundario); line-height: 1.6;">
            <li><strong>Ocultação Real do Menu:</strong> Se um módulo estiver desmarcado, ele <em>não aparecerá</em> na barra lateral nem em qualquer atalho do funcionário.</li>
            <li><strong>Bloqueio Rígido por URL:</strong> Tentativas de acesso direto pela barra de endereços (ex.: digitar <code>/financeiro</code> ou <code>/sgq</code>) serão bloqueadas com redirecionamento e aviso de segurança.</li>
            <li><strong>Administradores:</strong> Possuem acesso total irrestrito a todos os módulos e não são listados nesta tela.</li>
            <li><strong>Agilidade em 1 Clique:</strong> Utilize o botão <code>⚡ Padrão [Cargo]</code> para preencher instantaneamente a dotação recomendada para aquela função.</li>
        </ul>
    </div>

    <!-- Filtro de Busca de Colaboradores -->
    <div class="card mb-4" style="background: var(--bg-surface, #071f1b); border: 1px solid var(--border, rgba(255,255,255,0.1));">
        <div class="card-body" style="padding: 16px 20px;">
            <div class="row align-items-center g-3">
                <div class="col-md-6">
                    <div style="position: relative;">
                        <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 14px; top: 12px; color: var(--cor-texto-secundario);"></i>
                        <input type="text" id="filtro-colaborador" class="form-control" style="padding-left: 40px;" placeholder="Buscar colaborador por nome, e-mail ou cargo..." onkeyup="filtrarFuncionarios(this.value)">
                    </div>
                </div>
                <div class="col-md-6 text-md-end">
                    <small class="text-muted">
                        Total de colaboradores operacionais: <strong><?= count($usuarios) ?></strong>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <form method="post" action="<?= APP_URL ?>configuracoes/basicas" id="form-permissoes">
        <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>">

        <?php if (!$usuarios): ?>
            <div class="card p-5 text-center text-muted">
                <i class="fa-solid fa-users-slash fa-3x mb-3 opacity-25"></i>
                <h4>Nenhum usuário operacional encontrado</h4>
                <p>Todos os usuários cadastrados atualmente são Administradores ou estão inativos.</p>
            </div>
        <?php endif; ?>

        <?php foreach ($usuarios as $usuario): ?>
            <?php
            $uId = $usuario['id'];
            $uCargo = $usuario['cargo'];
            $uAcessos = $acessos[$uId] ?? [];
            
            // Se o usuário ainda não tiver nenhuma linha na tabela, inicializa com o padrão recomendado
            if (empty($uAcessos)) {
                $padrao = permissoesPadraoCargo($uCargo);
                foreach ($padrao as $modPadrao) {
                    $uAcessos[$modPadrao] = true;
                }
            }

            $totalLiberados = count(array_filter($uAcessos));
            $buscaString = strtolower($usuario['nome'] . ' ' . $usuario['email'] . ' ' . $uCargo);
            ?>

            <section class="card mb-4 funcionario-card" data-busca="<?= h($buscaString) ?>" style="border: 1px solid var(--border, rgba(255,255,255,0.1)); border-radius: 12px; overflow: hidden;">
                <!-- Cabeçalho do Colaborador -->
                <div class="card-header" style="background: rgba(255,255,255,0.03); padding: 16px 20px; border-bottom: 1px solid var(--border, rgba(255,255,255,0.08));">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div style="width: 44px; height: 44px; border-radius: 50%; background: rgba(86,224,173,0.15); color: var(--cor-destaque, #56e0ad); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; font-weight: bold;">
                                <?= strtoupper(substr($usuario['nome'], 0, 1)) ?>
                            </div>
                            <div>
                                <h3 style="margin: 0; font-size: 1.15rem; color: var(--cor-texto);">
                                    <?= h($usuario['nome']) ?>
                                    <span class="badge" style="background: rgba(255,255,255,0.1); color: var(--cor-texto); font-weight: 600; font-size: 0.78rem; margin-left: 6px;">
                                        <?= h($uCargo) ?>
                                    </span>
                                    <?php if (!$usuario['ativo']): ?>
                                        <span class="badge bg-danger" style="font-size: 0.72rem;">Inativo</span>
                                    <?php endif; ?>
                                </h3>
                                <small style="color: var(--cor-texto-secundario);"><?= h($usuario['email']) ?></small>
                            </div>
                        </div>

                        <!-- Ações Rápidas de Marcação -->
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="badge" id="count-<?= h($uId) ?>" style="background: rgba(86,224,173,0.15); color: var(--cor-destaque, #56e0ad); padding: 6px 12px; font-size: 0.85rem; border-radius: 6px;">
                                <?= $totalLiberados ?> liberados
                            </span>

                            <button type="button" class="btn btn-sm btn-outline-info" onclick="aplicarPadraoCargo('<?= h($uId) ?>', '<?= h($uCargo) ?>')" title="Aplica a configuração padrão recomendada para o cargo <?= h($uCargo) ?>">
                                <i class="fa-solid fa-bolt"></i> Padrão <?= h($uCargo) ?>
                            </button>

                            <button type="button" class="btn btn-sm btn-secondary" onclick="marcarTodos('<?= h($uId) ?>', true)" title="Marcar todos os módulos">
                                <i class="fa-solid fa-check-double"></i> Todos
                            </button>

                            <button type="button" class="btn btn-sm btn-secondary" onclick="marcarTodos('<?= h($uId) ?>', false)" title="Desmarcar todos os módulos">
                                <i class="fa-solid fa-xmark"></i> Limpar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Corpo de Permissões Agrupadas por Categoria -->
                <div class="card-body" style="padding: 20px;">
                    <div class="row g-4">
                        <?php foreach ($categoriasPermissoes as $catKey => $cat): ?>
                            <div class="col-lg-6">
                                <div style="background: rgba(255,255,255,0.015); border: 1px solid var(--border, rgba(255,255,255,0.06)); border-radius: 8px; padding: 14px;">
                                    <div class="d-flex align-items-center gap-2 mb-3 pb-2" style="border-bottom: 1px solid var(--border, rgba(255,255,255,0.06));">
                                        <i class="<?= h($cat['icone']) ?>" style="color: var(--cor-destaque, #56e0ad);"></i>
                                        <strong style="color: var(--cor-texto); font-size: 0.95rem;"><?= h($cat['titulo']) ?></strong>
                                    </div>

                                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 8px;">
                                        <?php foreach ($cat['itens'] as $modChave => [$modNome, $modDesc]): ?>
                                            <?php $isMarcado = !empty($uAcessos[$modChave]); ?>
                                            <label class="prot-perm-label" style="display: flex; gap: 8px; padding: 8px 10px; border: 1px solid var(--border, rgba(255,255,255,0.08)); border-radius: 6px; cursor: pointer; align-items: flex-start; background: rgba(255,255,255,0.01); transition: background .15s;">
                                                <input type="checkbox" 
                                                       name="permissoes[<?= h($uId) ?>][]" 
                                                       value="<?= h($modChave) ?>" 
                                                       data-user="<?= h($uId) ?>"
                                                       <?= $isMarcado ? 'checked' : '' ?> 
                                                       onchange="atualizarContador('<?= h($uId) ?>')"
                                                       style="margin-top: 3px; transform: scale(1.1); cursor: pointer;">
                                                <span style="line-height: 1.3;">
                                                    <strong style="font-size: 0.88rem; color: var(--cor-texto);"><?= h($modNome) ?></strong>
                                                    <small style="display: block; color: var(--cor-texto-secundario); font-size: 0.74rem; margin-top: 2px;"><?= h($modDesc) ?></small>
                                                </span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endforeach; ?>

        <?php if ($usuarios): ?>
            <!-- Barra de Ações Fixa no Rodapé -->
            <div class="card p-3" style="position: sticky; bottom: 15px; z-index: 100; box-shadow: 0 10px 30px rgba(0,0,0,0.5); border: 1px solid var(--cor-destaque, #56e0ad); background: var(--bg-surface, #071f1b);">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <strong style="color: var(--cor-texto);"><i class="fa-solid fa-circle-check text-accent"></i> Salvar Matriz de Permissões</strong>
                        <div class="text-secondary small">As configurações passam a valer imediatamente para os funcionários ao navegar ou logar.</div>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-secondary" type="reset">
                            <i class="fa-solid fa-rotate-left"></i> Desfazer Alterações
                        </button>
                        <button class="btn btn-primary btn-lg" type="submit" style="min-width: 200px;">
                            <i class="fas fa-save"></i> Gravar Permissões
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </form>
</div>

<script>
const padroesCargos = {
    'VISTORIADOR': ['dashboard', 'vistorias', 'agendamentos', 'embarcacoes', 'documentacao'],
    'ANALISTA': ['dashboard', 'analise_planos', 'relatorios_aprovacao', 'protocolos_documentais', 'embarcacoes', 'armadores', 'proprietarios', 'vistorias', 'certificados', 'documentacao'],
    'VENDEDOR': ['dashboard', 'comercial', 'servicos', 'embarcacoes', 'armadores', 'proprietarios', 'despachantes', 'agendamentos', 'emails'],
    'ADMIN': [
        'dashboard', 'vistorias', 'agendamentos', 'analise_planos', 'relatorios_aprovacao',
        'protocolos_documentais', 'certificados', 'documentacao', 'embarcacoes', 'armadores',
        'proprietarios', 'despachantes', 'comercial', 'servicos', 'financeiro', 'emails',
        'portal_clientes', 'relatorios', 'sgq', 'usuarios', 'configuracoes', 'responsaveis_assinatura'
    ]
};

function aplicarPadraoCargo(usuarioId, cargo) {
    const padrao = padroesCargos[cargo] || ['dashboard'];
    const checkboxes = document.querySelectorAll('input[data-user="' + usuarioId + '"]');
    checkboxes.forEach(cb => {
        cb.checked = padrao.includes(cb.value);
    });
    atualizarContador(usuarioId);
}

function marcarTodos(usuarioId, marcar) {
    const checkboxes = document.querySelectorAll('input[data-user="' + usuarioId + '"]');
    checkboxes.forEach(cb => {
        cb.checked = marcar;
    });
    atualizarContador(usuarioId);
}

function atualizarContador(usuarioId) {
    const marcados = document.querySelectorAll('input[data-user="' + usuarioId + '"]:checked').length;
    const badge = document.getElementById('count-' + usuarioId);
    if (badge) {
        badge.textContent = marcados + ' liberados';
    }
}

function filtrarFuncionarios(termo) {
    termo = (termo || '').toLowerCase().trim();
    document.querySelectorAll('.funcionario-card').forEach(card => {
        const busca = card.getAttribute('data-busca') || '';
        card.style.display = (!termo || busca.includes(termo)) ? '' : 'none';
    });
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
