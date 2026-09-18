<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/cliente_portal.php';

requireClienteSenhaDefinitiva();

$clienteId = clientePortalId();
$embarcacoes = clientePortalEmbarcacoes($pdo, $clienteId);
$embarcacoesDetalhadas = clientePortalSelectEmbarcacoesDetalhadas($pdo, $clienteId);

$busca = trim($_GET['busca'] ?? '');
$tipoFiltro = trim($_GET['tipo'] ?? '');

if ($busca !== '' || $tipoFiltro !== '') {
    $embarcacoesDetalhadas = array_values(array_filter($embarcacoesDetalhadas, function($emb) use ($busca, $tipoFiltro) {
        $matchBusca = true;
        $matchTipo = true;
        if ($busca !== '') {
            $termo = mb_strtolower($busca, 'UTF-8');
            $nome = mb_strtolower($emb['nome'] ?? '', 'UTF-8');
            $reg = mb_strtolower($emb['registro'] ?? $emb['numero_inscricao'] ?? '', 'UTF-8');
            $porto = mb_strtolower($emb['porto_inscricao'] ?? '', 'UTF-8');
            $matchBusca = (str_contains($nome, $termo) || str_contains($reg, $termo) || str_contains($porto, $termo));
        }
        if ($tipoFiltro !== '') {
            $matchTipo = ($emb['tipo_embarcacao'] ?? '') === $tipoFiltro;
        }
        return $matchBusca && $matchTipo;
    }));
}

// Tipos distintos para filtro
$tiposDisponiveis = array_values(array_unique(array_filter(array_column($embarcacoesDetalhadas, 'tipo_embarcacao'))));

$titulo_page = 'Minha Frota de Embarcações - Portal do Cliente';
require_once __DIR__ . '/../../includes/portal_header.php';
?>
<section class="portal-page-header">
    <div>
        <h1>Minha Frota de Embarcações</h1>
        <p>Acompanhe os dados técnicos, especificações navais, certificados e trâmites de cada embarcação cadastrada.</p>
    </div>
    <div class="portal-page-header-mark"><i class="fas fa-ship"></i></div>
</section>

<!-- KPIs DA FROTA -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-bottom: 20px;">
    <div class="portal-metric" style="border: 1px solid var(--p-line)!important; border-radius: var(--p-radius)!important;">
        <i class="fa-solid fa-ship" style="color:var(--p-green)"></i>
        <strong><?php echo count($embarcacoes); ?></strong>
        <span>Embarcações<br>na frota</span>
    </div>
    <div class="portal-metric" style="border: 1px solid var(--p-line)!important; border-radius: var(--p-radius)!important;">
        <i class="fa-solid fa-compass" style="color:#0284c7"></i>
        <strong><?php echo count(array_filter($embarcacoesDetalhadas, fn($e) => ($e['analises_ativas'] ?? 0) > 0)); ?></strong>
        <span>Projetos em<br>análise naval</span>
    </div>
    <div class="portal-metric" style="border: 1px solid var(--p-line)!important; border-radius: var(--p-radius)!important;">
        <i class="fa-solid fa-landmark-flag" style="color:#eab308"></i>
        <strong><?php echo count(array_filter($embarcacoesDetalhadas, fn($e) => ($e['protocolos_ativos'] ?? 0) > 0)); ?></strong>
        <span>Processos em<br>trâmite SISAP</span>
    </div>
    <div class="portal-metric" style="border: 1px solid var(--p-line)!important; border-radius: var(--p-radius)!important;">
        <i class="fa-solid fa-clipboard-check" style="color:#10b981"></i>
        <strong><?php echo count(array_filter($embarcacoesDetalhadas, fn($e) => ($e['total_vistorias'] ?? 0) > 0)); ?></strong>
        <span>Vistorias<br>registradas</span>
    </div>
</div>

<!-- FILTROS -->
<form method="GET" class="portal-filters" style="grid-template-columns: 1fr auto auto auto; margin-bottom: 20px;">
    <div class="form-group" style="margin:0;">
        <label for="busca">Buscar Embarcação</label>
        <input type="text" id="busca" name="busca" value="<?php echo h($busca); ?>" placeholder="Nome, inscrição ou porto...">
    </div>
    <?php if (!empty($tiposDisponiveis)): ?>
        <div class="form-group" style="margin:0;">
            <label for="tipo">Tipo</label>
            <select id="tipo" name="tipo">
                <option value="">Todos os tipos</option>
                <?php foreach ($tiposDisponiveis as $t): ?>
                    <option value="<?php echo h($t); ?>" <?php echo $tipoFiltro === $t ? 'selected' : ''; ?>><?php echo h($t); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>
    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrar</button>
    <a href="<?php echo APP_URL; ?>portal/embarcacoes" class="btn btn-secondary"><i class="fas fa-xmark"></i> Limpar</a>
</form>

<?php if (empty($embarcacoesDetalhadas)): ?>
    <section class="portal-panel">
        <div class="portal-empty">
            <i class="fas fa-ship"></i>
            <h2>Nenhuma embarcação localizada</h2>
            <p><?php echo ($busca !== '' || $tipoFiltro !== '') ? 'Nenhum resultado para os filtros aplicados. Tente limpar os filtros.' : 'Entre em contato com a certificadora caso espere encontrar uma embarcação vinculada ao seu cadastro.'; ?></p>
        </div>
    </section>
<?php else: ?>
    <section class="portal-fleet-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 20px;">
        <?php foreach ($embarcacoesDetalhadas as $emb): ?>
            <article class="portal-panel" style="display:flex; flex-direction:column; justify-content:space-between; padding:22px; transition: transform 0.2s ease, box-shadow 0.2s ease;">
                <div>
                    <!-- TOPO DO CARD: FOTO / ÍCONE + IDENTIFICAÇÃO -->
                    <div style="display: flex; gap: 16px; align-items: flex-start; margin-bottom: 16px;">
                        <?php if (!empty($emb['foto_url'])): ?>
                            <img src="<?php echo h($emb['foto_url']); ?>" alt="<?php echo h($emb['nome']); ?>" style="width:70px; height:70px; object-fit:cover; border-radius:10px; border:1px solid var(--p-line);">
                        <?php else: ?>
                            <div style="width:60px; height:60px; border-radius:12px; background:var(--p-mint-soft); color:var(--p-green-dark); display:grid; place-items:center; font-size:24px; flex-shrink:0;">
                                <i class="fa-solid fa-ship"></i>
                            </div>
                        <?php endif; ?>
                        <div style="flex:1; min-width:0;">
                            <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:8px;">
                                <h2 style="margin:0; font-size:1.2rem; color:var(--p-ink); font-weight:800; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    <?php echo h($emb['nome']); ?>
                                </h2>
                                <span class="portal-status is-valid" style="font-size:11px; padding:2px 8px;">Ativa</span>
                            </div>
                            <div style="margin-top:4px; font-size:0.86rem; color:var(--p-muted);">
                                <strong>Inscrição:</strong> <?php echo h($emb['registro'] ?: ($emb['numero_inscricao'] ?: 'Não informada')); ?>
                                <?php if (!empty($emb['porto_inscricao'])): ?>
                                    &bull; <span><?php echo h($emb['porto_inscricao']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- ESPECIFICAÇÕES TÉCNICAS NAVAIS -->
                    <div style="background:var(--p-soft); border-radius:10px; padding:12px; margin-bottom:16px; display:grid; grid-template-columns:repeat(2, 1fr); gap:8px 12px; font-size:0.82rem;">
                        <div>
                            <span style="color:var(--p-muted); display:block;">Tipo de Embarcação</span>
                            <strong style="color:var(--p-ink);"><?php echo h($emb['tipo_embarcacao'] ?: ($emb['tipo'] ?: '-')); ?></strong>
                        </div>
                        <div>
                            <span style="color:var(--p-muted); display:block;">Área de Navegação</span>
                            <strong style="color:var(--p-ink);"><?php echo h($emb['area_navegacao'] ?: ($emb['tipo_navegacao'] ?: 'Interior')); ?></strong>
                        </div>
                        <div>
                            <span style="color:var(--p-muted); display:block;">Arqueação Bruta (AB)</span>
                            <strong style="color:var(--p-ink);"><?php echo !empty($emb['arqueacao_bruta']) ? h($emb['arqueacao_bruta']) : '-'; ?></strong>
                        </div>
                        <div>
                            <span style="color:var(--p-muted); display:block;">Porte Bruto (TPB)</span>
                            <strong style="color:var(--p-ink);"><?php echo !empty($emb['porte_bruto']) ? number_format((float)$emb['porte_bruto'], 2, ',', '.') . ' t' : '-'; ?></strong>
                        </div>
                        <?php if (!empty($emb['comprimento_total']) || !empty($emb['boca_moldada'])): ?>
                            <div>
                                <span style="color:var(--p-muted); display:block;">Comprimento (LOA)</span>
                                <strong style="color:var(--p-ink);"><?php echo !empty($emb['comprimento_total']) ? number_format((float)$emb['comprimento_total'], 2, ',', '.') . ' m' : '-'; ?></strong>
                            </div>
                            <div>
                                <span style="color:var(--p-muted); display:block;">Boca Moldada</span>
                                <strong style="color:var(--p-ink);"><?php echo !empty($emb['boca_moldada']) ? number_format((float)$emb['boca_moldada'], 2, ',', '.') . ' m' : '-'; ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- INDICADORES DE ATIVIDADE -->
                    <div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:16px;">
                        <?php if (($emb['protocolos_ativos'] ?? 0) > 0): ?>
                            <span class="badge-sisap" style="font-size:11px;">
                                <i class="fa-solid fa-landmark-flag"></i> <?php echo (int)$emb['protocolos_ativos']; ?> Trâmite(s) SISAP
                            </span>
                        <?php endif; ?>
                        <?php if (($emb['analises_ativas'] ?? 0) > 0): ?>
                            <span class="portal-status is-analysis" style="font-size:11px; padding:3px 8px;">
                                <i class="fa-solid fa-drafting-compass"></i> Projeto em análise
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- AÇÕES RÁPIDAS DA EMBARCAÇÃO -->
                <div style="display:flex; gap:8px; flex-wrap:wrap; border-top:1px solid var(--p-line); padding-top:14px;">
                    <a class="btn btn-sm btn-primary" href="<?php echo APP_URL; ?>portal/documentos?embarcacao_id=<?php echo urlencode($emb['id']); ?>" style="flex:1; justify-content:center;">
                        <i class="fa-solid fa-file-shield"></i> Ver Documentos
                    </a>
                    <a class="btn btn-sm btn-outline-secondary" href="<?php echo APP_URL; ?>portal/protocolos?embarcacao_id=<?php echo urlencode($emb['id']); ?>" title="Ver trâmites na Capitania">
                        <i class="fa-solid fa-landmark-flag"></i> Trâmites
                    </a>
                    <a class="btn btn-sm btn-outline-secondary" href="<?php echo APP_URL; ?>portal/vistorias?embarcacao_id=<?php echo urlencode($emb['id']); ?>" title="Ver vistorias">
                        <i class="fa-solid fa-clipboard-check"></i> Vistorias
                    </a>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/portal_footer.php'; ?>
