<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/analise_planos.php';
analisePlanosExigirAcesso();

$busca = trim($_GET['busca'] ?? '');
$status = trim($_GET['status'] ?? '');
$prazoFiltro = trim($_GET['prazo'] ?? '');
$paginaAtual = max(1, (int)($_GET['pagina'] ?? 1));
$porPagina = (int)($_GET['por_pagina'] ?? 15);
if (!in_array($porPagina, [5, 10, 15, 25, 50, 100], true)) {
    $porPagina = 15;
}

$params = [];
$where = [];
$cargo = getCargo();
$usuarioId = (string)($_SESSION['usuario_id'] ?? '');

if ($cargo === 'ANALISTA') {
    $where[] = 'ap.analista_id=:usuario';
    $params[':usuario'] = $usuarioId;
} elseif ($cargo === 'VENDEDOR') {
    $where[] = 'ap.vendedor_origem_id=:usuario';
    $params[':usuario'] = $usuarioId;
}

if ($busca !== '') {
    $where[] = '(ap.numero LIKE :busca1 OR e.nome LIKE :busca2 OR ap.objeto LIKE :busca3 OR p.numero LIKE :busca4)';
    $termoBusca = '%' . $busca . '%';
    $params[':busca1'] = $termoBusca;
    $params[':busca2'] = $termoBusca;
    $params[':busca3'] = $termoBusca;
    $params[':busca4'] = $termoBusca;
}

$statusLabels = [
    'AGUARDANDO_AGENDAMENTO' => 'Aguardando agendamento',
    'AGENDADA' => 'Agendada',
    'EM_ANALISE' => 'Em análise',
    'AGUARDANDO_DOCUMENTOS' => 'Aguardando documentos',
    'AGUARDANDO_ASSINATURA_ANALISTA' => 'Aguardando assinatura do analista',
    'AGUARDANDO_APROVACAO_ADMIN' => 'Aguardando admin',
    'CONCLUIDA' => 'Concluída',
    'REPROVADA' => 'Reprovada',
    'CANCELADA' => 'Cancelada',
];

if (isset($statusLabels[$status])) {
    $where[] = 'ap.status=:status';
    $params[':status'] = $status;
}

if ($prazoFiltro === 'hoje') {
    $where[] = "DATE(ap.prazo_agendado_em) = CURDATE() AND ap.status IN ('" . implode("','", analisePlanosStatusAtivos()) . "')";
} elseif ($prazoFiltro === 'atrasadas') {
    $where[] = "DATE(ap.prazo_agendado_em) < CURDATE() AND ap.status IN ('" . implode("','", analisePlanosStatusAtivos()) . "')";
}

// Helper para construir URLs preservando parâmetros de filtro e paginação
if (!function_exists('analiseUrl')) {
    function analiseUrl(array $overrides = []): string {
        global $busca, $status, $prazoFiltro, $porPagina, $paginaAtual;
        $params = [
            'busca'      => $busca !== '' ? $busca : null,
            'status'     => $status !== '' ? $status : null,
            'prazo'      => $prazoFiltro !== '' ? $prazoFiltro : null,
            'por_pagina' => (int)$porPagina !== 15 ? (int)$porPagina : null,
            'pagina'     => (int)$paginaAtual > 1 ? (int)$paginaAtual : null,
        ];
        foreach ($overrides as $k => $v) {
            if ($v === null || $v === '' || ($k === 'pagina' && (int)$v <= 1) || ($k === 'por_pagina' && (int)$v === 15)) {
                unset($params[$k]);
            } else {
                $params[$k] = $v;
            }
        }
        $qs = http_build_query(array_filter($params, fn($val) => $val !== null && $val !== ''));
        return APP_URL . 'analises-planos' . ($qs ? '?' . $qs : '');
    }
}

// Contagem total para paginação com os mesmos filtros aplicados
$sqlCount = "SELECT COUNT(*) FROM analises_planos ap
             INNER JOIN embarcacoes e ON e.id=ap.embarcacao_id
             LEFT JOIN usuarios u ON u.id=ap.analista_id
             LEFT JOIN propostas p ON p.id=ap.proposta_id"
             . ($where ? ' WHERE ' . implode(' AND ', $where) : '');
$stmtCount = $pdo->prepare($sqlCount);
$stmtCount->execute($params);
$totalAnalises = (int)$stmtCount->fetchColumn();

$totalPaginas = max(1, (int)ceil($totalAnalises / $porPagina));
if ($paginaAtual > $totalPaginas) {
    $paginaAtual = $totalPaginas;
}
$offset = ($paginaAtual - 1) * $porPagina;

// Consulta dos registros paginados
$sql = "SELECT ap.*, e.nome embarcacao_nome, u.nome analista_nome, p.numero proposta_numero,
        (SELECT COUNT(*) FROM analise_planos_exigencias x WHERE x.analise_id=ap.id AND (x.status<>'CUMPRIDA' OR x.saneamento_pendente=1)) exigencias_pendentes
        FROM analises_planos ap
        INNER JOIN embarcacoes e ON e.id=ap.embarcacao_id
        LEFT JOIN usuarios u ON u.id=ap.analista_id
        LEFT JOIN propostas p ON p.id=ap.proposta_id"
        . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
        . ' ORDER BY (ap.status="AGUARDANDO_AGENDAMENTO") DESC, ap.prazo_agendado_em IS NULL, ap.prazo_agendado_em, ap.atualizado_em DESC'
        . ' LIMIT ' . (int)$porPagina . ' OFFSET ' . (int)$offset;
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$analises = $stmt->fetchAll(PDO::FETCH_ASSOC);

$registroInicio = $totalAnalises > 0 ? ($offset + 1) : 0;
$registroFim = min($offset + $porPagina, $totalAnalises);

// Métricas globais da fila do analista/escopo
$whereMetricas = [];
$paramsMetricas = [];
if ($cargo === 'ANALISTA') {
    $whereMetricas[] = 'ap.analista_id=:usuario';
    $paramsMetricas[':usuario'] = $usuarioId;
} elseif ($cargo === 'VENDEDOR') {
    $whereMetricas[] = 'ap.vendedor_origem_id=:usuario';
    $paramsMetricas[':usuario'] = $usuarioId;
}
$sqlMetricas = "SELECT ap.status, ap.prazo_agendado_em FROM analises_planos ap"
    . ($whereMetricas ? ' WHERE ' . implode(' AND ', $whereMetricas) : '');
$stmtMetricas = $pdo->prepare($sqlMetricas);
$stmtMetricas->execute($paramsMetricas);
$metricas = ['aguardando' => 0, 'hoje' => 0, 'atrasadas' => 0, 'documentos' => 0, 'admin' => 0];
$hoje = date('Y-m-d');
$ativos = analisePlanosStatusAtivos();
foreach ($stmtMetricas->fetchAll(PDO::FETCH_ASSOC) as $item) {
    if ($item['status'] === 'AGUARDANDO_AGENDAMENTO') $metricas['aguardando']++;
    if ($item['status'] === 'AGUARDANDO_DOCUMENTOS') $metricas['documentos']++;
    if ($item['status'] === 'AGUARDANDO_APROVACAO_ADMIN') $metricas['admin']++;
    if (!empty($item['prazo_agendado_em']) && in_array($item['status'], $ativos, true)) {
        $data = substr($item['prazo_agendado_em'], 0, 10);
        if ($data === $hoje) $metricas['hoje']++;
        elseif ($data < $hoje) $metricas['atrasadas']++;
    }
}

$titulo_page = 'Análise de Planos - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="conteudo-principal">
 <div class="tabela-container">
  <div class="tabela-header">
   <div>
    <h3><i class="fas fa-drafting-compass"></i> Análise de Planos</h3>
    <small>Agenda documental e relatórios técnicos conforme NORMAM-202/DPC.</small>
   </div>
   <div style="display:flex;gap:8px;align-items:center;">
    <a class="btn btn-secondary btn-sm" href="<?=APP_URL?>analises-planos/referencias"><i class="fas fa-book-bookmark"></i> Banco de Normas NORMAM</a>
    <a class="btn btn-primary btn-sm" href="<?=APP_URL?>analises-planos/form"><i class="fas fa-plus"></i> Nova Análise</a>
   </div>
  </div>

  <!-- Cards de Indicadores Acionáveis com Filtro em 1 Clique -->
  <div class="analise-metrics">
   <a href="<?=h(analiseUrl(['status'=>'AGUARDANDO_AGENDAMENTO','prazo'=>null,'pagina'=>1]))?>" class="analise-metric-card <?=$status==='AGUARDANDO_AGENDAMENTO'?'active':''?>" title="Filtrar aguardando agendamento">
       <span>Aguardando agendamento</span><strong><?=$metricas['aguardando']?></strong>
   </a>
   <a href="<?=h(analiseUrl(['prazo'=>'hoje','status'=>null,'pagina'=>1]))?>" class="analise-metric-card <?=$prazoFiltro==='hoje'?'active':''?>" title="Filtrar prazo para hoje">
       <span>Prazo hoje</span><strong><?=$metricas['hoje']?></strong>
   </a>
   <a href="<?=h(analiseUrl(['prazo'=>'atrasadas','status'=>null,'pagina'=>1]))?>" class="analise-metric-card <?=$prazoFiltro==='atrasadas'?'active':''?>" title="Filtrar prazos atrasados">
       <span style="<?=$metricas['atrasadas']>0?'color:#dc2626;font-weight:600':''?>">Atrasadas</span><strong style="<?=$metricas['atrasadas']>0?'color:#dc2626':''?>"><?=$metricas['atrasadas']?></strong>
   </a>
   <a href="<?=h(analiseUrl(['status'=>'AGUARDANDO_DOCUMENTOS','prazo'=>null,'pagina'=>1]))?>" class="analise-metric-card <?=$status==='AGUARDANDO_DOCUMENTOS'?'active':''?>" title="Filtrar aguardando envio de pranchas">
       <span>Aguardando documentos</span><strong><?=$metricas['documentos']?></strong>
   </a>
   <a href="<?=h(analiseUrl(['status'=>'AGUARDANDO_APROVACAO_ADMIN','prazo'=>null,'pagina'=>1]))?>" class="analise-metric-card <?=$status==='AGUARDANDO_APROVACAO_ADMIN'?'active':''?>" title="Filtrar aguardando validação admin">
       <span>Aguardando admin</span><strong><?=$metricas['admin']?></strong>
   </a>
  </div>

  <!-- Formulário de Filtros -->
  <form method="get" class="filtros" style="margin:15px 20px; display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">
   <div class="form-group" style="flex:1; min-width:240px;">
       <label class="form-label">Buscar</label>
       <input name="busca" value="<?=h($busca)?>" class="form-control" placeholder="Número, proposta, embarcação ou objeto">
   </div>
   <div class="form-group" style="min-width:190px;">
       <label class="form-label">Situação</label>
       <select name="status" class="form-control">
           <option value="">Todas as situações</option>
           <?php foreach($statusLabels as $v=>$l):?>
               <option value="<?=$v?>" <?=$status===$v?'selected':''?>><?=h($l)?></option>
           <?php endforeach?>
       </select>
   </div>
   <input type="hidden" name="por_pagina" value="<?=(int)$porPagina?>">
   <?php if ($prazoFiltro !== ''): ?>
       <input type="hidden" name="prazo" value="<?=h($prazoFiltro)?>">
   <?php endif; ?>
   <div style="display:flex; gap:8px;">
       <button class="btn btn-primary"><i class="fas fa-search"></i> Filtrar</button>
       <a class="btn btn-secondary" href="<?=APP_URL?>analises-planos">Limpar</a>
   </div>
  </form>

  <?php if (!$analises): ?>
      <div class="tabela-vazia">
          <i class="fas fa-drafting-compass"></i>
          <h3>Nenhuma análise encontrada</h3>
          <p>Não há processos para os filtros selecionados. Clique em "Limpar" ou cadastre uma nova análise.</p>
      </div>
  <?php else: ?>
      <div style="overflow-x:auto;">
          <table>
              <thead>
                  <tr>
                      <th>Número / Origem</th>
                      <th>Embarcação</th>
                      <th>Processo</th>
                      <th>Analista Técnico</th>
                      <th>Prazo Agendado</th>
                      <th>Situação</th>
                      <th style="text-align:right; width:60px;">Ação</th>
                  </tr>
              </thead>
              <tbody>
                  <?php foreach ($analises as $a): ?>
                      <tr>
                          <td>
                              <strong><?=h($a['numero'])?></strong>
                              <small style="display:block; color:var(--cor-texto-secundario,#64748b); font-size:0.8rem;"><?=h($a['proposta_numero'] ?: 'Processo legado')?></small>
                          </td>
                          <td><strong><?=h($a['embarcacao_nome'])?></strong></td>
                          <td>
                              <?=h($a['tipo_processo'] ?: 'A definir')?>
                              <small style="display:block; color:var(--cor-texto-secundario,#64748b); font-size:0.8rem;"><?=h($a['classe_certificacao'] ?: '')?></small>
                          </td>
                          <td><?=h($a['analista_nome'] ?: 'Não atribuído')?></td>
                          <td><?=!empty($a['prazo_agendado_em']) ? formatarDataCompleta($a['prazo_agendado_em']) : '—'?></td>
                          <td>
                              <span class="badge badge-<?=$a['status']==='CONCLUIDA'?'success':($a['status']==='REPROVADA'||$a['status']==='CANCELADA'?'danger':'warning')?>">
                                  <?=h($statusLabels[$a['status']] ?? $a['status'])?>
                              </span>
                          </td>
                          <td style="text-align:right;">
                              <a class="btn btn-primary btn-sm btn-icon-action" href="<?=APP_URL?>analises-planos/form?id=<?=urlencode($a['id'])?>" title="Abrir análise" aria-label="Abrir análise <?=h($a['numero'])?>">
                                  <i class="fas fa-eye" aria-hidden="true"></i>
                              </a>
                          </td>
                      </tr>
                  <?php endforeach; ?>
              </tbody>
          </table>
      </div>

      <!-- Rodapé com Resumo de Registros e Paginação -->
      <div class="paginacao-footer">
          <div class="paginacao-info">
              <span>
                  <i class="fas fa-circle-info" style="color:var(--cor-primaria,#087653); margin-right:4px;"></i>
                  Mostrando <strong><?= $registroInicio ?></strong> a <strong><?= $registroFim ?></strong> de <strong><?= $totalAnalises ?></strong> processo(s)
                  <?php if ($busca !== ''): ?>
                      (filtrando por "<strong><?= h($busca) ?></strong>")
                  <?php endif; ?>
              </span>

              <!-- Seletor de registros por página -->
              <div style="display:inline-flex; align-items:center; gap:6px;">
                  <label for="selectPorPagina" style="margin:0; font-size:0.82rem; color:var(--cor-texto-secundario,#64748b); white-space:nowrap;">Exibir:</label>
                  <select id="selectPorPagina" class="form-control form-control-sm" style="width:auto; height:32px; padding:2px 24px 2px 8px; font-size:0.82rem; border-radius:6px; display:inline-block;" onchange="window.location.href=this.value">
                      <?php foreach ([10, 15, 25, 50, 100] as $qtd): ?>
                          <option value="<?= h(analiseUrl(['por_pagina' => $qtd, 'pagina' => 1])) ?>" <?= $porPagina === $qtd ? 'selected' : '' ?>>
                              <?= $qtd ?> por pág.
                          </option>
                      <?php endforeach; ?>
                  </select>
              </div>
          </div>

          <!-- Navegação de Páginas -->
          <?php if ($totalPaginas > 1): ?>
              <nav aria-label="Navegação de páginas de análises de planos">
                  <ul class="paginacao-nav">
                      <!-- Primeira página -->
                      <li class="paginacao-item <?= $paginaAtual <= 1 ? 'disabled' : '' ?>">
                          <a class="paginacao-link" href="<?= $paginaAtual <= 1 ? 'javascript:void(0)' : h(analiseUrl(['pagina' => 1])) ?>" title="Primeira página">
                              <i class="fa-solid fa-angles-left"></i>
                          </a>
                      </li>

                      <!-- Página anterior -->
                      <li class="paginacao-item <?= $paginaAtual <= 1 ? 'disabled' : '' ?>">
                          <a class="paginacao-link" href="<?= $paginaAtual <= 1 ? 'javascript:void(0)' : h(analiseUrl(['pagina' => $paginaAtual - 1])) ?>" title="Página anterior">
                              <i class="fa-solid fa-chevron-left"></i>
                          </a>
                      </li>

                      <!-- Janela dinâmica de números de página -->
                      <?php
                      $janelaInicio = max(1, $paginaAtual - 2);
                      $janelaFim = min($totalPaginas, $paginaAtual + 2);

                      if ($janelaInicio > 1) {
                          echo '<li class="paginacao-item"><a class="paginacao-link" href="' . h(analiseUrl(['pagina' => 1])) . '">1</a></li>';
                          if ($janelaInicio > 2) {
                              echo '<li class="paginacao-ellipsis">...</li>';
                          }
                      }

                      for ($p = $janelaInicio; $p <= $janelaFim; $p++) {
                          if ($p === $paginaAtual) {
                              echo '<li class="paginacao-item active"><span class="paginacao-link">' . $p . '</span></li>';
                          } else {
                              echo '<li class="paginacao-item"><a class="paginacao-link" href="' . h(analiseUrl(['pagina' => $p])) . '">' . $p . '</a></li>';
                          }
                      }

                      if ($janelaFim < $totalPaginas) {
                          if ($janelaFim < $totalPaginas - 1) {
                              echo '<li class="paginacao-ellipsis">...</li>';
                          }
                          echo '<li class="paginacao-item"><a class="paginacao-link" href="' . h(analiseUrl(['pagina' => $totalPaginas])) . '">' . $totalPaginas . '</a></li>';
                      }
                      ?>

                      <!-- Próxima página -->
                      <li class="paginacao-item <?= $paginaAtual >= $totalPaginas ? 'disabled' : '' ?>">
                          <a class="paginacao-link" href="<?= $paginaAtual >= $totalPaginas ? 'javascript:void(0)' : h(analiseUrl(['pagina' => $paginaAtual + 1])) ?>" title="Próxima página">
                              <i class="fa-solid fa-chevron-right"></i>
                          </a>
                      </li>

                      <!-- Última página -->
                      <li class="paginacao-item <?= $paginaAtual >= $totalPaginas ? 'disabled' : '' ?>">
                          <a class="paginacao-link" href="<?= $paginaAtual >= $totalPaginas ? 'javascript:void(0)' : h(analiseUrl(['pagina' => $totalPaginas])) ?>" title="Última página">
                              <i class="fa-solid fa-angles-right"></i>
                          </a>
                      </li>
                  </ul>
              </nav>
          <?php endif; ?>
      </div>
  <?php endif; ?>
 </div>
</div>

<style>
.analise-metrics {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 12px;
    padding: 16px 20px 8px 20px;
}
.analise-metric-card {
    display: block;
    text-decoration: none;
    padding: 14px 16px;
    border: 1px solid var(--cor-borda, #e2e8f0);
    border-radius: 10px;
    background: var(--cor-fundo-card, #ffffff);
    transition: all 0.2s ease;
    color: inherit;
}
.analise-metric-card:hover {
    border-color: #087653;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(8, 118, 83, 0.12);
    text-decoration: none;
    color: inherit;
}
.analise-metric-card.active {
    border-color: #087653;
    background: #f0fdf4;
    box-shadow: 0 0 0 2px rgba(8, 118, 83, 0.25);
}
.analise-metric-card span {
    display: block;
    font-size: 0.82rem;
    color: var(--cor-texto-secundario, #64748b);
    margin-bottom: 4px;
}
.analise-metric-card strong {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--cor-texto, #0f172a);
}

/* Componente de Paginação Naval */
.paginacao-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 14px;
    padding: 16px 20px;
    border-top: 1px solid var(--cor-borda, #e2e8f0);
    background: var(--cor-fundo-card, #ffffff);
    border-bottom-left-radius: 10px;
    border-bottom-right-radius: 10px;
}
.paginacao-info {
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
    font-size: 0.88rem;
    color: var(--cor-texto-secundario, #64748b);
}
.paginacao-nav {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    list-style: none;
    margin: 0;
    padding: 0;
}
.paginacao-item {
    display: inline-block;
}
.paginacao-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 34px;
    height: 34px;
    padding: 0 8px;
    border-radius: 6px;
    border: 1px solid var(--cor-borda, #cbd5e1);
    background: var(--cor-fundo, #ffffff);
    color: var(--cor-texto, #1e293b);
    font-size: 0.85rem;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.15s ease;
    user-select: none;
}
.paginacao-link:hover:not(.disabled) {
    border-color: #087653;
    color: #087653;
    background: #f0fdf4;
    text-decoration: none;
}
.paginacao-item.active .paginacao-link {
    background: #087653 !important;
    border-color: #087653 !important;
    color: #ffffff !important;
    font-weight: 700;
    box-shadow: 0 2px 6px rgba(8, 118, 83, 0.3);
}
.paginacao-item.disabled .paginacao-link {
    color: #94a3b8 !important;
    border-color: #e2e8f0 !important;
    background: #f8fafc !important;
    cursor: not-allowed;
    pointer-events: none;
}
.paginacao-ellipsis {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 28px;
    height: 34px;
    color: #94a3b8;
    font-size: 0.85rem;
}

@media (max-width: 900px) {
    .analise-metrics {
        grid-template-columns: repeat(2, 1fr);
    }
}
@media (max-width: 768px) {
    .paginacao-footer {
        flex-direction: column;
        align-items: stretch;
    }
    .paginacao-nav {
        justify-content: center;
        flex-wrap: wrap;
    }
}
</style>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
