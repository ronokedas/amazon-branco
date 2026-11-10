<?php
/**
 * Helpers compartilhados de filtragem, busca por embarcação e atualização sem reload
 * para as listagens de propostas (/comercial e /comercial/propostas).
 */

if (!function_exists('propostasAplicarFiltroBusca')) {
    /**
     * Aplica filtro de busca por cliente, CPF/CNPJ, número da proposta ou nome/inscrição da embarcação.
     */
    function propostasAplicarFiltroBusca(array &$where, array &$params, string $filtroBusca): void
    {
        $termo = trim($filtroBusca);
        if ($termo === '') {
            return;
        }

        $like = '%' . $termo . '%';
        $where[] = '(
            c.nome LIKE :busca
            OR c.cpf_cnpj LIKE :busca2
            OR p.numero LIKE :busca3
            OR EXISTS (
                SELECT 1
                FROM propostas_embarcacoes pe
                INNER JOIN embarcacoes e ON e.id = pe.embarcacao_id
                WHERE pe.proposta_id = p.id
                  AND (e.nome LIKE :busca4 OR e.registro LIKE :busca5 OR e.numero_inscricao LIKE :busca6)
            )
            OR EXISTS (
                SELECT 1
                FROM propostas_servicos ps
                INNER JOIN embarcacoes e2 ON e2.id = ps.embarcacao_id
                WHERE ps.proposta_id = p.id
                  AND (e2.nome LIKE :busca7 OR e2.registro LIKE :busca8 OR e2.numero_inscricao LIKE :busca9)
            )
        )';

        $params[':busca']  = $like;
        $params[':busca2'] = $like;
        $params[':busca3'] = $like;
        $params[':busca4'] = $like;
        $params[':busca5'] = $like;
        $params[':busca6'] = $like;
        $params[':busca7'] = $like;
        $params[':busca8'] = $like;
        $params[':busca9'] = $like;
    }
}

if (!function_exists('propostasBuscarEmbarcacoesMapa')) {
    /**
     * Retorna mapa [proposta_id => ['BARCO (REGISTRO)', ...]] considerando vínculos e serviços.
     */
    function propostasBuscarEmbarcacoesMapa(PDO $pdo, array $propostaIds): array
    {
        $ids = array_values(array_filter(array_unique(array_map('strval', $propostaIds)), static fn($v) => $v !== ''));
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmtEmb = $pdo->prepare("
            SELECT DISTINCT sub.proposta_id, sub.nome, sub.registro
            FROM (
                SELECT pe.proposta_id, e.id AS emb_id, e.nome, COALESCE(NULLIF(e.numero_inscricao, ''), e.registro) AS registro
                FROM propostas_embarcacoes pe
                INNER JOIN embarcacoes e ON e.id = pe.embarcacao_id
                WHERE pe.proposta_id IN ({$placeholders})
                UNION
                SELECT ps.proposta_id, e2.id AS emb_id, e2.nome, COALESCE(NULLIF(e2.numero_inscricao, ''), e2.registro) AS registro
                FROM propostas_servicos ps
                INNER JOIN embarcacoes e2 ON e2.id = ps.embarcacao_id
                WHERE ps.proposta_id IN ({$placeholders})
            ) sub
            ORDER BY sub.nome ASC
        ");
        $stmtEmb->execute(array_merge($ids, $ids));
        $vinculos = $stmtEmb->fetchAll(PDO::FETCH_ASSOC);

        $mapa = [];
        foreach ($vinculos as $v) {
            $pid = $v['proposta_id'];
            if (!isset($mapa[$pid])) {
                $mapa[$pid] = [];
            }
            $mapa[$pid][] = $v['nome'] . (!empty($v['registro']) ? ' (' . $v['registro'] . ')' : '');
        }

        return $mapa;
    }
}

if (!function_exists('propostasRenderFiltrosBar')) {
    /**
     * Renderiza barra uniforme de filtros de propostas sem reload ao digitar.
     */
    function propostasRenderFiltrosBar(
        array $statusConfig,
        string $filtroBusca,
        string $filtroStatus,
        string $filtroDataIni,
        string $filtroDataFim
    ): void {
        ?>
        <div class="filtros" style="margin: 15px 20px 6px; display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end;">
            <div class="form-group" style="margin-bottom: 0; flex: 1.4; min-width: 240px;">
                <label for="buscaProposta"><i class="fas fa-search"></i> Buscar</label>
                <input type="search"
                       id="buscaProposta"
                       name="busca"
                       autocomplete="off"
                       placeholder="Cliente, nº da proposta ou nome da embarcação..."
                       value="<?php echo h($filtroBusca); ?>"
                       oninput="agendarBuscaPropostas()"
                       onkeydown="if(event.key==='Enter'){event.preventDefault(); filtrarPropostas(true);}">
            </div>
            <div class="form-group" style="margin-bottom: 0; min-width: 150px;">
                <label for="filtroStatus"><i class="fas fa-filter"></i> Status</label>
                <select id="filtroStatus" name="status" onchange="filtrarPropostas(true)">
                    <option value="todos" <?php echo $filtroStatus === 'todos' ? 'selected' : ''; ?>>Todos</option>
                    <?php foreach ($statusConfig as $val => $cfg): ?>
                        <option value="<?php echo h($val); ?>" <?php echo $filtroStatus === $val ? 'selected' : ''; ?>>
                            <?php echo h($cfg['label']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 0; min-width: 135px;">
                <label for="filtroDataIni"><i class="fas fa-calendar"></i> Data Início</label>
                <input type="date" id="filtroDataIni" name="data_ini" value="<?php echo h($filtroDataIni); ?>" onchange="filtrarPropostas(true)">
            </div>
            <div class="form-group" style="margin-bottom: 0; min-width: 135px;">
                <label for="filtroDataFim"><i class="fas fa-calendar"></i> Data Fim</label>
                <input type="date" id="filtroDataFim" name="data_fim" value="<?php echo h($filtroDataFim); ?>" onchange="filtrarPropostas(true)">
            </div>
            <div class="form-group" style="margin-bottom: 0; display: flex; align-items: flex-end; gap: 8px;">
                <button type="button" id="btnBuscarPropostas" class="btn btn-primary btn-sm" onclick="filtrarPropostas(true)" style="height: 38px; display: inline-flex; align-items: center; gap: 6px; padding: 0 14px;">
                    <i class="fas fa-search"></i> Buscar
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="limparFiltros()" style="height: 38px; display: inline-flex; align-items: center; gap: 6px; padding: 0 12px;">
                    <i class="fas fa-times"></i> Limpar
                </button>
            </div>
        </div>
        <div style="margin: 0 20px 14px;">
            <small class="text-muted">
                <i class="fas fa-info-circle"></i>
                Pesquise pelo nome ou CPF/CNPJ do cliente, número da proposta (ex.: <code>AM-ORC-7/26</code>) ou nome/inscrição da embarcação.
            </small>
        </div>
        <?php
    }
}

if (!function_exists('propostasRenderScriptFiltroAjax')) {
    /**
     * Script de atualização dinâmica da listagem (#propostasListaContainer) sem reload ao digitar.
     */
    function propostasRenderScriptFiltroAjax(string $baseUrl, ?int $porPagina = null): void
    {
        ?>
        <script>
        (function() {
            const BASE_URL_PROPOSTAS = <?php echo json_encode($baseUrl, JSON_UNESCAPED_SLASHES); ?>;
            const POR_PAGINA_PROPOSTAS = <?php echo $porPagina !== null ? (int)$porPagina : 'null'; ?>;
            let timerBuscaPropostas = null;
            let reqSeqPropostas = 0;
            let abortPropostas = null;

            function montarUrlPropostas(pagina) {
                const statusEl = document.getElementById('filtroStatus');
                const buscaEl = document.getElementById('buscaProposta');
                const dataIniEl = document.getElementById('filtroDataIni');
                const dataFimEl = document.getElementById('filtroDataFim');

                const params = [];
                if (statusEl && statusEl.value !== 'todos') {
                    params.push('status=' + encodeURIComponent(statusEl.value));
                }
                if (buscaEl && buscaEl.value.trim() !== '') {
                    params.push('busca=' + encodeURIComponent(buscaEl.value.trim()));
                }
                if (dataIniEl && dataIniEl.value !== '') {
                    params.push('data_ini=' + encodeURIComponent(dataIniEl.value));
                }
                if (dataFimEl && dataFimEl.value !== '') {
                    params.push('data_fim=' + encodeURIComponent(dataFimEl.value));
                }
                if (POR_PAGINA_PROPOSTAS !== null && POR_PAGINA_PROPOSTAS !== 15) {
                    params.push('por_pagina=' + encodeURIComponent(String(POR_PAGINA_PROPOSTAS)));
                }
                if (pagina && pagina > 1) {
                    params.push('pagina=' + encodeURIComponent(String(pagina)));
                }

                return params.length > 0 ? (BASE_URL_PROPOSTAS + '?' + params.join('&')) : BASE_URL_PROPOSTAS;
            }

            async function atualizarListaPropostasUrl(targetUrl) {
                const container = document.getElementById('propostasListaContainer');
                if (!container) {
                    window.location.href = targetUrl;
                    return;
                }

                const seq = ++reqSeqPropostas;
                if (abortPropostas) {
                    abortPropostas.abort();
                }
                abortPropostas = typeof AbortController === 'function' ? new AbortController() : null;

                container.style.opacity = '0.58';
                container.style.transition = 'opacity 0.15s ease';

                try {
                    const resp = await fetch(targetUrl, {
                        credentials: 'same-origin',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        signal: abortPropostas ? abortPropostas.signal : undefined
                    });
                    if (!resp.ok) {
                        window.location.href = targetUrl;
                        return;
                    }

                    const html = await resp.text();
                    if (seq !== reqSeqPropostas) {
                        return;
                    }

                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const novoContainer = doc.getElementById('propostasListaContainer');
                    if (!novoContainer) {
                        window.location.href = targetUrl;
                        return;
                    }

                    container.innerHTML = novoContainer.innerHTML;
                    container.style.opacity = '1';
                    if (window.history && typeof window.history.replaceState === 'function') {
                        window.history.replaceState({}, '', targetUrl);
                    }
                } catch (err) {
                    if (err && err.name === 'AbortError') {
                        return;
                    }
                    container.style.opacity = '1';
                    window.location.href = targetUrl;
                }
            }

            window.filtrarPropostas = function() {
                if (timerBuscaPropostas) {
                    clearTimeout(timerBuscaPropostas);
                    timerBuscaPropostas = null;
                }
                atualizarListaPropostasUrl(montarUrlPropostas(1));
            };

            window.agendarBuscaPropostas = function() {
                if (timerBuscaPropostas) {
                    clearTimeout(timerBuscaPropostas);
                }
                timerBuscaPropostas = setTimeout(function() {
                    atualizarListaPropostasUrl(montarUrlPropostas(1));
                }, 320);
            };

            window.limparFiltros = function() {
                if (timerBuscaPropostas) {
                    clearTimeout(timerBuscaPropostas);
                    timerBuscaPropostas = null;
                }
                const statusEl = document.getElementById('filtroStatus');
                const buscaEl = document.getElementById('buscaProposta');
                const dataIniEl = document.getElementById('filtroDataIni');
                const dataFimEl = document.getElementById('filtroDataFim');

                if (statusEl) statusEl.value = 'todos';
                if (buscaEl) {
                    buscaEl.value = '';
                    buscaEl.focus();
                }
                if (dataIniEl) dataIniEl.value = '';
                if (dataFimEl) dataFimEl.value = '';

                atualizarListaPropostasUrl(BASE_URL_PROPOSTAS);
            };

            // Paginação via AJAX para manter o fluxo sem reload ao navegar entre páginas
            document.addEventListener('click', function(e) {
                const link = e.target.closest('#propostasListaContainer .page-number, #propostasListaContainer .page-step, #propostasListaContainer .paginacao-link');
                if (!link) return;
                const href = link.getAttribute('href') || '';
                if (!href || href === '#' || href.startsWith('javascript:')) return;
                e.preventDefault();
                atualizarListaPropostasUrl(href);
            });
        })();
        </script>
        <?php
    }
}
