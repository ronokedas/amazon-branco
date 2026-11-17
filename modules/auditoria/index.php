<?php
/**
 * MÓDULO: AUDITORIA DO SISTEMA
 * Arquivo: modules/auditoria/index.php
 * Finalidade: Registro completo e detalhado de movimentações para Secretaria e Diretoria (Admin).
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../dashboard/data.php';

verificar_sessao();

// Acesso permitido para ADMIN e SECRETARIA
if (!temPerfil('ADMIN') && !temPerfil('SECRETARIA') && !podeAcessar('auditoria')) {
    setMensagem('error', 'Acesso restrito à Diretoria e Secretaria.');
    redirecionar(APP_URL . 'dashboard?erro=sem_permissao');
}

$meuUsuarioId = $_SESSION['usuario_id'] ?? '';
$meuCargo = getCargo();

// Filtros
$filtroUsuario = trim((string)($_GET['usuario_id'] ?? ''));
$filtroAcao    = trim((string)($_GET['categoria'] ?? ''));
$filtroBusca   = trim((string)($_GET['busca'] ?? ''));
$dataInicio    = trim((string)($_GET['data_inicio'] ?? ''));
$dataFim       = trim((string)($_GET['data_fim'] ?? ''));
$pagina        = max(1, (int)($_GET['p'] ?? 1));
$porPagina     = 30;
$offset        = ($pagina - 1) * $porPagina;

// Lista de usuários para o dropdown de filtro
$usuariosFiltro = [];
try {
    $usuariosFiltro = $pdo->query("SELECT id, nome, cargo, email FROM usuarios WHERE ativo = 1 ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

// Montar cláusulas WHERE
$where = ['1=1'];
$params = [];

if ($filtroUsuario !== '') {
    $where[] = 'l.usuario_id = :uid';
    $params[':uid'] = $filtroUsuario;
}

if ($dataInicio !== '') {
    $where[] = 'DATE(l.criado_em) >= :dt_ini';
    $params[':dt_ini'] = $dataInicio;
}

if ($dataFim !== '') {
    $where[] = 'DATE(l.criado_em) <= :dt_fim';
    $params[':dt_fim'] = $dataFim;
}

if ($filtroBusca !== '') {
    $where[] = '(l.descricao LIKE :busca OR l.acao LIKE :busca OR u.nome LIKE :busca)';
    $params[':busca'] = '%' . $filtroBusca . '%';
}

if ($filtroAcao !== '') {
    switch ($filtroAcao) {
        case 'assinatura':
            $where[] = "(l.acao LIKE '%assinatura%' OR l.descricao LIKE '%responsavel tecnico%')";
            break;
        case 'vistoria':
            $where[] = "(l.acao LIKE '%relatorio%' OR l.acao LIKE '%vistoria%' OR l.descricao LIKE '%relatorio%')";
            break;
        case 'proposta':
            $where[] = "(l.acao LIKE '%proposta%' OR l.descricao LIKE '%proposta%')";
            break;
        case 'certificado':
            $where[] = "(l.acao LIKE '%certificado%' OR l.acao LIKE '%documento_aprovado%' OR l.descricao LIKE '%certificado%' OR l.descricao LIKE '%licença%')";
            break;
        case 'agendamento':
            $where[] = "(l.acao LIKE '%agendamento%' OR l.descricao LIKE '%agendamento%')";
            break;
        case 'cadastro':
            $where[] = "(l.acao LIKE '%cliente%' OR l.acao LIKE '%proprietario%' OR l.acao LIKE '%armador%' OR l.acao LIKE '%embarcacao%' OR l.acao LIKE '%usuario%')";
            break;
    }
}

$whereSql = implode(' AND ', $where);

// Total de registros
$totalRegistros = 0;
try {
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM logs_atividade l LEFT JOIN usuarios u ON u.id = l.usuario_id WHERE {$whereSql}");
    $stmtCount->execute($params);
    $totalRegistros = (int)$stmtCount->fetchColumn();
} catch (Throwable $e) {
    $totalRegistros = 0;
}

$totalPaginas = max(1, (int)ceil($totalRegistros / $porPagina));
if ($pagina > $totalPaginas) $pagina = $totalPaginas;

// Buscar registros da página
$logs = [];
try {
    $sqlLogs = "SELECT l.id, l.usuario_id, l.acao, l.descricao, l.ip, l.criado_em,
                       u.nome AS usuario_nome, u.cargo AS usuario_cargo
                FROM logs_atividade l
                LEFT JOIN usuarios u ON u.id = l.usuario_id
                WHERE {$whereSql}
                ORDER BY l.criado_em DESC, l.id DESC
                LIMIT {$porPagina} OFFSET {$offset}";
    $stmtLogs = $pdo->prepare($sqlLogs);
    $stmtLogs->execute($params);
    $logs = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $logs = [];
}

// Métricas de auditoria (para os cards de indicadores)
$totalHoje = (int)dashScalar($pdo, "SELECT COUNT(*) FROM logs_atividade WHERE DATE(criado_em) = CURDATE()");
$totalAssinaturas = (int)dashScalar($pdo, "SELECT COUNT(*) FROM logs_atividade WHERE acao LIKE '%assinatura%' OR descricao LIKE '%responsavel tecnico%'");
$totalDecisoes = (int)dashScalar($pdo, "SELECT COUNT(*) FROM logs_atividade WHERE acao LIKE '%decisao%' OR descricao LIKE '%definido como%'");
$totalPropostas = (int)dashScalar($pdo, "SELECT COUNT(*) FROM logs_atividade WHERE acao LIKE '%proposta%' OR descricao LIKE '%Proposta%'");

$titulo_page = 'Auditoria do Sistema - Amazon Certificadora';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div style="margin-bottom: 24px; display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:16px;">
        <div>
            <div style="display:inline-flex; align-items:center; gap:6px; font-size:11.5px; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#0d9488; background:#ccfbf1; padding:4px 10px; border-radius:999px; margin-bottom:8px;">
                <i class="fa-solid fa-shield-halved"></i> Rastreabilidade & Governança Naval
            </div>
            <h1 style="margin:0 0 6px 0; font-size:1.6rem; font-weight:800; color:#0f172a; letter-spacing:-0.02em;">
                Auditoria do Sistema & Movimentações
            </h1>
            <p style="margin:0; font-size:0.92rem; color:#64748b; max-width:850px;">
                Acompanhe em tempo real cada ação realizada no ERP: quem fez, o que fez, data, hora, IP de origem e acesse diretamente os documentos vinculados.
            </p>
        </div>
        <div style="display:flex; gap:8px;">
            <a href="<?= APP_URL ?>dashboard" class="btn btn-secondary btn-sm" style="display:inline-flex; align-items:center; gap:6px;">
                <i class="fa-solid fa-arrow-left"></i> Voltar ao Dashboard
            </a>
        </div>
    </div>

    <!-- CARDS DE INDICADORES RÁPIDOS -->
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:14px; margin-bottom:24px;">
        <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:16px 20px; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                <span style="font-size:0.8rem; font-weight:600; color:#64748b; text-transform:uppercase;">Movimentações Hoje</span>
                <i class="fa-solid fa-bolt text-warning" style="font-size:1.1rem;"></i>
            </div>
            <div style="font-size:1.55rem; font-weight:800; color:#0f172a;"><?= number_format($totalHoje, 0, ',', '.') ?></div>
            <small style="color:#94a3b8; font-size:0.75rem;">Ações registradas no dia</small>
        </div>

        <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:16px 20px; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                <span style="font-size:0.8rem; font-weight:600; color:#64748b; text-transform:uppercase;">Assinaturas Digitais</span>
                <i class="fa-solid fa-file-signature text-success" style="font-size:1.1rem;"></i>
            </div>
            <div style="font-size:1.55rem; font-weight:800; color:#059669;"><?= number_format($totalAssinaturas, 0, ',', '.') ?></div>
            <small style="color:#94a3b8; font-size:0.75rem;">Com fé pública e token seguro</small>
        </div>

        <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:16px 20px; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                <span style="font-size:0.8rem; font-weight:600; color:#64748b; text-transform:uppercase;">Decisões da Diretoria</span>
                <i class="fa-solid fa-stamp text-info" style="font-size:1.1rem;"></i>
            </div>
            <div style="font-size:1.55rem; font-weight:800; color:#0284c7;"><?= number_format($totalDecisoes, 0, ',', '.') ?></div>
            <small style="color:#94a3b8; font-size:0.75rem;">Aprovações e retornos técnicos</small>
        </div>

        <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:16px 20px; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                <span style="font-size:0.8rem; font-weight:600; color:#64748b; text-transform:uppercase;">Comercial & Propostas</span>
                <i class="fa-solid fa-file-invoice-dollar text-primary" style="font-size:1.1rem;"></i>
            </div>
            <div style="font-size:1.55rem; font-weight:800; color:#7c3aed;"><?= number_format($totalPropostas, 0, ',', '.') ?></div>
            <small style="color:#94a3b8; font-size:0.75rem;">Criações e atualizações de propostas</small>
        </div>
    </div>

    <!-- FORMULÁRIO DE FILTROS AUTODIDÁTICO -->
    <div class="card mb-4" style="border:1px solid #e2e8f0; border-radius:12px; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
        <div class="card-body" style="padding:18px 22px;">
            <form method="GET" action="<?= APP_URL ?>auditoria" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap:14px; align-items:flex-end;">
                <!-- Filtro por Colaborador -->
                <div class="form-group mb-0">
                    <label class="form-label" style="font-size:0.82rem; font-weight:600; color:#475569; margin-bottom:4px;">
                        <i class="fa-solid fa-user text-muted"></i> Colaborador
                    </label>
                    <select name="usuario_id" class="form-select form-select-sm" style="font-size:0.85rem; border-radius:6px;">
                        <option value="">Todos os colaboradores</option>
                        <?php foreach ($usuariosFiltro as $u): ?>
                            <option value="<?= h($u['id']) ?>" <?= $filtroUsuario === $u['id'] ? 'selected' : '' ?>>
                                <?= h($u['nome']) ?> (<?= h($u['cargo']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted" style="font-size:0.72rem; display:block; margin-top:2px;">Filtre quem executou a ação</small>
                </div>

                <!-- Filtro por Categoria de Ação -->
                <div class="form-group mb-0">
                    <label class="form-label" style="font-size:0.82rem; font-weight:600; color:#475569; margin-bottom:4px;">
                        <i class="fa-solid fa-tags text-muted"></i> Categoria da Operação
                    </label>
                    <select name="categoria" class="form-select form-select-sm" style="font-size:0.85rem; border-radius:6px;">
                        <option value="">Todas as categorias</option>
                        <option value="assinatura" <?= $filtroAcao === 'assinatura' ? 'selected' : '' ?>>Assinaturas Digitais</option>
                        <option value="vistoria" <?= $filtroAcao === 'vistoria' ? 'selected' : '' ?>>Vistorias & Relatórios</option>
                        <option value="proposta" <?= $filtroAcao === 'proposta' ? 'selected' : '' ?>>Propostas Comerciais</option>
                        <option value="certificado" <?= $filtroAcao === 'certificado' ? 'selected' : '' ?>>Certificados Estatutários</option>
                        <option value="agendamento" <?= $filtroAcao === 'agendamento' ? 'selected' : '' ?>>Agendamentos</option>
                        <option value="cadastro" <?= $filtroAcao === 'cadastro' ? 'selected' : '' ?>>Clientes & Embarcações</option>
                    </select>
                    <small class="text-muted" style="font-size:0.72rem; display:block; margin-top:2px;">Selecione o tipo de trâmite</small>
                </div>

                <!-- Data Início -->
                <div class="form-group mb-0">
                    <label class="form-label" style="font-size:0.82rem; font-weight:600; color:#475569; margin-bottom:4px;">
                        <i class="fa-solid fa-calendar-day text-muted"></i> Data Inicial
                    </label>
                    <input type="date" name="data_inicio" value="<?= h($dataInicio) ?>" class="form-control form-control-sm" style="font-size:0.85rem; border-radius:6px;">
                    <small class="text-muted" style="font-size:0.72rem; display:block; margin-top:2px;">A partir de</small>
                </div>

                <!-- Data Fim -->
                <div class="form-group mb-0">
                    <label class="form-label" style="font-size:0.82rem; font-weight:600; color:#475569; margin-bottom:4px;">
                        <i class="fa-solid fa-calendar-check text-muted"></i> Data Final
                    </label>
                    <input type="date" name="data_fim" value="<?= h($dataFim) ?>" class="form-control form-control-sm" style="font-size:0.85rem; border-radius:6px;">
                    <small class="text-muted" style="font-size:0.72rem; display:block; margin-top:2px;">Até a data</small>
                </div>

                <!-- Busca Textual -->
                <div class="form-group mb-0">
                    <label class="form-label" style="font-size:0.82rem; font-weight:600; color:#475569; margin-bottom:4px;">
                        <i class="fa-solid fa-magnifying-glass text-muted"></i> Palavra-chave
                    </label>
                    <input type="text" name="busca" value="<?= h($filtroBusca) ?>" placeholder="Embarcação, proposta, etc..." class="form-control form-control-sm" style="font-size:0.85rem; border-radius:6px;">
                    <small class="text-muted" style="font-size:0.72rem; display:block; margin-top:2px;">Ex: MACEDÃO, AM-ORC...</small>
                </div>

                <!-- Botões de Ação -->
                <div class="form-group mb-0" style="display:flex; gap:6px;">
                    <button type="submit" class="btn btn-primary btn-sm" style="flex:1; display:inline-flex; align-items:center; justify-content:center; gap:6px; font-weight:700;">
                        <i class="fa-solid fa-filter"></i> Filtrar
                    </button>
                    <a href="<?= APP_URL ?>auditoria" class="btn btn-outline-secondary btn-sm" title="Limpar todos os filtros" style="display:inline-flex; align-items:center; justify-content:center;">
                        <i class="fa-solid fa-xmark"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- TABELA DE REGISTROS -->
    <div class="card" style="border:1px solid #e2e8f0; border-radius:12px; box-shadow:0 1px 3px rgba(0,0,0,0.04); overflow:hidden;">
        <div style="padding:14px 20px; background:#f8fafc; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
            <div style="font-size:0.88rem; font-weight:700; color:#334155;">
                <i class="fa-solid fa-list text-muted"></i> Total de registros: <strong><?= number_format($totalRegistros, 0, ',', '.') ?></strong>
            </div>
            <div style="font-size:0.82rem; color:#64748b;">
                Página <strong><?= $pagina ?></strong> de <strong><?= $totalPaginas ?></strong>
            </div>
        </div>

        <?php if (empty($logs)): ?>
            <div style="padding:48px 24px; text-align:center; color:#94a3b8;">
                <i class="fa-solid fa-clipboard-question" style="font-size:2.8rem; margin-bottom:12px; color:#cbd5e1; display:block;"></i>
                <h3 style="font-size:1.1rem; color:#475569; margin:0 0 4px 0;">Nenhum registro encontrado</h3>
                <p style="margin:0; font-size:0.85rem;">Tente ajustar os filtros ou pesquisar por outro termo.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0" style="font-size:0.88rem; border-collapse:collapse;">
                    <thead>
                        <tr style="background:#f1f5f9; color:#475569; font-size:0.78rem; text-transform:uppercase; letter-spacing:0.04em; border-bottom:1px solid #cbd5e1;">
                            <th style="padding:12px 16px; width:170px;">Data & Horário</th>
                            <th style="padding:12px 16px; width:220px;">Colaborador</th>
                            <th style="padding:12px 16px;">Operação Realizada</th>
                            <th style="padding:12px 16px; width:130px; text-align:center;">Origem / IP</th>
                            <th style="padding:12px 16px; width:150px; text-align:right;">Ação Rápida</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <?php 
                            $infoFmt = formatarLogAtividadeDidatico($log, $pdo);
                            $isEu = !empty($log['usuario_id']) && $log['usuario_id'] === $meuUsuarioId;
                            $dt = new DateTime($log['criado_em']);
                            $dtFmt = $dt->format('d/m/Y');
                            $hrFmt = $dt->format('H:i:s');
                            $hojeStr = date('d/m/Y');
                            $ontemStr = date('d/m/Y', strtotime('-1 day'));
                            $diaExibicao = $dtFmt === $hojeStr ? 'Hoje' : ($dtFmt === $ontemStr ? 'Ontem' : $dtFmt);
                            ?>
                            <tr style="border-bottom:1px solid #f1f5f9; transition:background .1s;">
                                <!-- Data e Horário -->
                                <td style="padding:12px 16px; vertical-align:middle; white-space:nowrap;">
                                    <div style="font-weight:700; color:#0f172a; font-size:0.85rem;">
                                        <?= $diaExibicao ?> <span style="font-weight:400; color:#64748b;">às <?= $hrFmt ?></span>
                                    </div>
                                    <small style="color:#94a3b8; font-size:0.75rem;"><?= $dtFmt ?></small>
                                </td>

                                <!-- Colaborador -->
                                <td style="padding:12px 16px; vertical-align:middle;">
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        <div style="width:32px; height:32px; border-radius:50%; background:#e2e8f0; display:flex; align-items:center; justify-content:center; font-weight:700; color:#334155; font-size:0.8rem; flex-shrink:0;">
                                            <?= mb_substr($log['usuario_nome'] ?: 'S', 0, 1) ?>
                                        </div>
                                        <div>
                                            <div style="font-weight:700; color:#0f172a; display:flex; align-items:center; gap:6px;">
                                                <span><?= h($log['usuario_nome'] ?: 'Sistema') ?></span>
                                                <?php if ($isEu): ?>
                                                    <span class="badge" style="background:#ccfbf1; color:#0f766e; font-size:0.68rem; padding:1px 5px; border-radius:4px;">Você</span>
                                                <?php endif; ?>
                                            </div>
                                            <span style="font-size:0.75rem; color:#64748b;"><?= h($log['usuario_cargo'] ?: 'Automático') ?></span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Descrição Didática -->
                                <td style="padding:12px 16px; vertical-align:middle;">
                                    <div style="display:flex; align-items:flex-start; gap:10px;">
                                        <div style="width:26px; height:26px; border-radius:6px; background:#f1f5f9; display:flex; align-items:center; justify-content:center; flex-shrink:0; margin-top:2px;">
                                            <i class="<?= h($infoFmt['icone']) ?>" style="color:<?= h($infoFmt['cor_icone']) ?>; font-size:0.85rem;"></i>
                                        </div>
                                        <div>
                                            <strong style="color:#0f172a; font-size:0.88rem; display:block;">
                                                <?= h($infoFmt['titulo']) ?>
                                            </strong>
                                            <?php if (!empty($infoFmt['detalhe'])): ?>
                                                <div style="font-size:0.8rem; color:#64748b; margin-top:2px; line-height:1.35;">
                                                    <?= h($infoFmt['detalhe']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>

                                <!-- IP -->
                                <td style="padding:12px 16px; vertical-align:middle; text-align:center; white-space:nowrap;">
                                    <code style="font-size:0.75rem; background:#f8fafc; border:1px solid #e2e8f0; padding:2px 6px; border-radius:4px; color:#64748b;">
                                        <?= h($log['ip'] ?: '127.0.0.1') ?>
                                    </code>
                                </td>

                                <!-- Ação Rápida / Link -->
                                <td style="padding:12px 16px; vertical-align:middle; text-align:right; white-space:nowrap;">
                                    <?php if (!empty($infoFmt['url'])): ?>
                                        <a href="<?= h($infoFmt['url']) ?>" class="btn btn-sm btn-outline-primary" style="font-size:0.75rem; padding:4px 9px; display:inline-flex; align-items:center; gap:5px; font-weight:600;">
                                            <span>Acessar</span>
                                            <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:0.7rem;"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted" style="font-size:0.75rem;">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- PAGINAÇÃO -->
            <?php if ($totalPaginas > 1): ?>
                <?php
                $queryParams = $_GET;
                unset($queryParams['p']);
                $baseQuery = http_build_query($queryParams);
                $urlPagina = fn(int $p) => APP_URL . 'auditoria?' . ($baseQuery ? $baseQuery . '&' : '') . 'p=' . $p;
                ?>
                <div style="padding:14px 20px; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                    <div style="font-size:0.82rem; color:#64748b;">
                        Exibindo registros de <?= $offset + 1 ?> a <?= min($totalRegistros, $offset + $porPagina) ?> de <?= $totalRegistros ?>
                    </div>
                    <div style="display:flex; gap:4px;">
                        <?php if ($pagina > 1): ?>
                            <a href="<?= $urlPagina($pagina - 1) ?>" class="btn btn-sm btn-outline-secondary" style="font-size:0.8rem;">
                                &laquo; Anterior
                            </a>
                        <?php endif; ?>

                        <?php
                        $startP = max(1, $pagina - 2);
                        $endP = min($totalPaginas, $pagina + 2);
                        for ($i = $startP; $i <= $endP; $i++):
                        ?>
                            <a href="<?= $urlPagina($i) ?>" class="btn btn-sm <?= $i === $pagina ? 'btn-primary' : 'btn-outline-secondary' ?>" style="font-size:0.8rem; font-weight:<?= $i === $pagina ? '700' : '400' ?>;">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($pagina < $totalPaginas): ?>
                            <a href="<?= $urlPagina($pagina + 1) ?>" class="btn btn-sm btn-outline-secondary" style="font-size:0.8rem;">
                                Próxima &raquo;
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
