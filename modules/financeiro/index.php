<?php
/**
 * MODULO: FINANCEIRO
 * Arquivo: index.php - Listagem com filtros (data, tipo, categoria) e cards de resumo
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Exigir login e cargo ADMIN
verificar_sessao();
if (!podeAcessar('financeiro')) {
    header('Location: ' . APP_URL . 'dashboard?erro=sem_permissao');
    exit;
}

// Filtros
$filtro_tipo      = $_GET['tipo'] ?? '';
$filtro_data_ini  = $_GET['data_ini'] ?? '';
$filtro_data_fim  = $_GET['data_fim'] ?? '';
$filtro_categoria = $_GET['categoria'] ?? '';

// Construir query com filtros
$sql = "SELECT id, tipo, descricao, valor, valor_original, saldo_devedor, status, data, categoria, observacoes, criado_em, atualizado_em FROM financeiro_lancamentos WHERE ativo = 1";
$params = [];

if (!empty($filtro_tipo) && in_array($filtro_tipo, ['RECEITA', 'DESPESA'])) {
    $sql .= " AND tipo = :tipo";
    $params[':tipo'] = $filtro_tipo;
}

if (!empty($filtro_data_ini)) {
    $sql .= " AND data >= :data_ini";
    $params[':data_ini'] = $filtro_data_ini;
}

if (!empty($filtro_data_fim)) {
    $sql .= " AND data <= :data_fim";
    $params[':data_fim'] = $filtro_data_fim;
}

if (!empty($filtro_categoria)) {
    $sql .= " AND categoria LIKE :categoria";
    $params[':categoria'] = '%' . $filtro_categoria . '%';
}

$sql .= " ORDER BY data DESC, criado_em DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $lancamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Erro ao listar lancamentos: ' . $e->getMessage());
    $lancamentos = [];
}

// Calcular totais de caixa e valores em aberto (usando os mesmos filtros).
try {
    $sqlTotais = "
        SELECT
            COALESCE(SUM(CASE WHEN l.tipo = 'RECEITA' THEN
                CASE WHEN b.total_baixado IS NOT NULL THEN b.total_baixado WHEN l.status = 'PAGO' THEN l.valor_original ELSE 0 END
            ELSE 0 END), 0) AS total_receitas,
            COALESCE(SUM(CASE WHEN l.tipo = 'RECEITA' AND l.status IN ('PENDENTE', 'PARCIAL') THEN l.saldo_devedor ELSE 0 END), 0) AS total_a_receber,
            COALESCE(SUM(CASE WHEN l.tipo = 'DESPESA' THEN
                CASE WHEN b.total_baixado IS NOT NULL THEN b.total_baixado WHEN l.status = 'PAGO' THEN l.valor_original ELSE 0 END
            ELSE 0 END), 0) AS total_despesas
        FROM financeiro_lancamentos l
        LEFT JOIN (
            SELECT lancamento_id, SUM(valor_pago) AS total_baixado
            FROM financeiro_historico_baixas
            GROUP BY lancamento_id
        ) b ON b.lancamento_id = l.id
        WHERE l.ativo = 1 AND l.status != 'CANCELADO'
    ";
    $paramsTotais = [];

    if (!empty($filtro_tipo) && in_array($filtro_tipo, ['RECEITA', 'DESPESA'])) {
        $sqlTotais .= " AND l.tipo = :tipo";
        $paramsTotais[':tipo'] = $filtro_tipo;
    }
    if (!empty($filtro_data_ini)) {
        $sqlTotais .= " AND l.data >= :data_ini";
        $paramsTotais[':data_ini'] = $filtro_data_ini;
    }
    if (!empty($filtro_data_fim)) {
        $sqlTotais .= " AND l.data <= :data_fim";
        $paramsTotais[':data_fim'] = $filtro_data_fim;
    }
    if (!empty($filtro_categoria)) {
        $sqlTotais .= " AND l.categoria LIKE :categoria";
        $paramsTotais[':categoria'] = '%' . $filtro_categoria . '%';
    }

    $stmtTotais = $pdo->prepare($sqlTotais);
    $stmtTotais->execute($paramsTotais);
    $totais = $stmtTotais->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (Exception $e) {
    $totais = [];
}

$totalReceitas = floatval($totais['total_receitas'] ?? 0);
$totalAReceber = floatval($totais['total_a_receber'] ?? 0);
$totalDespesas = floatval($totais['total_despesas'] ?? 0);
$saldo = $totalReceitas - $totalDespesas;

// Buscar categorias distintas para o filtro
try {
    $categorias = $pdo->query("SELECT DISTINCT categoria FROM financeiro_lancamentos WHERE categoria IS NOT NULL AND categoria != '' ORDER BY categoria ASC")->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $categorias = [];
}

$formasPagamento = [
    'parcelado' => 'Parcelado (cartão / boleto parcelado)',
    'a_vista' => 'À Vista',
    'boleto' => 'Boleto Bancário',
    'pix' => 'PIX',
];

$csrf = gerarCSRF();

$titulo_page = 'Financeiro - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div class="tabela-container">
        <div class="tabela-header">
            <h3><i class="fas fa-dollar-sign"></i> Financeiro</h3>
            <a href="<?php echo APP_URL; ?>financeiro/form" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Novo Lancamento
            </a>
        </div>

        <!-- Cards de resumo -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 15px; margin: 15px 20px;">
            <!-- Total Receitas -->
            <div style="background: linear-gradient(135deg, var(--cor-painel), var(--cor-sidebar)); border: 1px solid var(--cor-borda); border-radius: 12px; padding: 18px; border-left: 4px solid var(--cor-sucesso);">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 45px; height: 45px; border-radius: 10px; background: rgba(46, 204, 113, 0.15); display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-arrow-up" style="color: var(--cor-sucesso); font-size: 1.2rem;"></i>
                    </div>
                    <div>
                        <div style="color: var(--cor-texto-secundario); font-size: 0.8rem; font-weight: 500; text-transform: uppercase;">Receitas</div>
                        <div style="color: var(--cor-sucesso); font-size: 1.4rem; font-weight: 700;"><?php echo formatarMoeda($totalReceitas); ?></div>
                    </div>
                </div>
            </div>

            <!-- A Receber -->
            <div style="background: linear-gradient(135deg, var(--cor-painel), var(--cor-sidebar)); border: 1px solid var(--cor-borda); border-radius: 12px; padding: 18px; border-left: 4px solid var(--status-pending, #d29922);">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 45px; height: 45px; border-radius: 10px; background: rgba(241, 196, 15, 0.15); display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-hourglass-half" style="color: var(--status-pending, #d29922); font-size: 1.2rem;"></i>
                    </div>
                    <div>
                        <div style="color: var(--cor-texto-secundario); font-size: 0.8rem; font-weight: 500; text-transform: uppercase;">A receber</div>
                        <div style="color: var(--status-pending, #d29922); font-size: 1.4rem; font-weight: 700;"><?php echo formatarMoeda($totalAReceber); ?></div>
                    </div>
                </div>
            </div>

            <!-- Total Despesas -->
            <div style="background: linear-gradient(135deg, var(--cor-painel), var(--cor-sidebar)); border: 1px solid var(--cor-borda); border-radius: 12px; padding: 18px; border-left: 4px solid var(--cor-erro);">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 45px; height: 45px; border-radius: 10px; background: rgba(231, 76, 60, 0.15); display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-arrow-down" style="color: var(--cor-erro); font-size: 1.2rem;"></i>
                    </div>
                    <div>
                        <div style="color: var(--cor-texto-secundario); font-size: 0.8rem; font-weight: 500; text-transform: uppercase;">Despesas</div>
                        <div style="color: var(--cor-erro); font-size: 1.4rem; font-weight: 700;"><?php echo formatarMoeda($totalDespesas); ?></div>
                    </div>
                </div>
            </div>

            <!-- Saldo -->
            <div style="background: linear-gradient(135deg, var(--cor-painel), var(--cor-sidebar)); border: 1px solid var(--cor-borda); border-radius: 12px; padding: 18px; border-left: 4px solid <?php echo $saldo >= 0 ? 'var(--cor-sucesso)' : 'var(--cor-erro)'; ?>;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 45px; height: 45px; border-radius: 10px; background: <?php echo $saldo >= 0 ? 'rgba(46, 204, 113, 0.15)' : 'rgba(231, 76, 60, 0.15)'; ?>; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-balance-scale" style="color: <?php echo $saldo >= 0 ? 'var(--cor-sucesso)' : 'var(--cor-erro)'; ?>; font-size: 1.2rem;"></i>
                    </div>
                    <div>
                        <div style="color: var(--cor-texto-secundario); font-size: 0.8rem; font-weight: 500; text-transform: uppercase;">Saldo</div>
                        <div style="color: <?php echo $saldo >= 0 ? 'var(--cor-sucesso)' : 'var(--cor-erro)'; ?>; font-size: 1.4rem; font-weight: 700;"><?php echo formatarMoeda($saldo); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <form method="GET" action="<?php echo APP_URL; ?>financeiro" style="margin: 0 20px 15px;">
            <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end;">
                <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 120px;">
                    <label style="font-size: 0.8rem;"><i class="fas fa-tag"></i> Tipo</label>
                    <select name="tipo" style="width: 100%; padding: 8px 10px;">
                        <option value="">Todos</option>
                        <option value="RECEITA" <?php echo $filtro_tipo === 'RECEITA' ? 'selected' : ''; ?>>Receita</option>
                        <option value="DESPESA" <?php echo $filtro_tipo === 'DESPESA' ? 'selected' : ''; ?>>Despesa</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 120px;">
                    <label style="font-size: 0.8rem;"><i class="fas fa-calendar"></i> Data Inicio</label>
                    <input type="date" name="data_ini" value="<?php echo h($filtro_data_ini); ?>" style="width: 100%; padding: 8px 10px;">
                </div>
                <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 120px;">
                    <label style="font-size: 0.8rem;"><i class="fas fa-calendar"></i> Data Fim</label>
                    <input type="date" name="data_fim" value="<?php echo h($filtro_data_fim); ?>" style="width: 100%; padding: 8px 10px;">
                </div>
                <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 120px;">
                    <label style="font-size: 0.8rem;"><i class="fas fa-folder"></i> Categoria</label>
                    <input type="text" name="categoria" value="<?php echo h($filtro_categoria); ?>" placeholder="Buscar..." style="width: 100%; padding: 8px 10px;">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                </div>
                <?php if (!empty($filtro_tipo) || !empty($filtro_data_ini) || !empty($filtro_data_fim) || !empty($filtro_categoria)): ?>
                <div class="form-group" style="margin-bottom: 0;">
                    <a href="<?php echo APP_URL; ?>financeiro" class="btn btn-secondary btn-sm">
                        <i class="fas fa-times"></i> Limpar
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </form>

        <?php if (empty($lancamentos)): ?>
            <div class="tabela-vazia">
                <i class="fas fa-dollar-sign"></i>
                <h3>Nenhum lancamento encontrado</h3>
                <p>Clique em "Novo Lancamento" para cadastrar o primeiro.</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Tipo</th>
                        <th>Status</th>
                        <th>Descricao</th>
                        <th>Categoria</th>
                        <th>Valor</th>
                        <th>Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lancamentos as $l): ?>
                    <tr>
                        <td><?php echo formatarData($l['data']); ?></td>
                        <td>
                            <?php if ($l['tipo'] === 'RECEITA'): ?>
                                <span class="badge badge-success"><i class="fas fa-arrow-up"></i> Receita</span>
                            <?php else: ?>
                                <span class="badge badge-danger"><i class="fas fa-arrow-down"></i> Despesa</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($l['status'] === 'PAGO'): ?>
                                <span class="badge badge-success">Pago</span>
                            <?php elseif ($l['status'] === 'PARCIAL'): ?>
                                <span class="badge" style="background:#6f42c1;color:#fff;">Parcial</span>
                            <?php elseif ($l['status'] === 'PENDENTE'): ?>
                                <span class="badge badge-warning">Pendente</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Cancelado</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?php echo h($l['descricao']); ?></strong>
                            <?php if (!empty($l['observacoes'])): ?>
                                <br><small class="text-muted"><?php echo h(mb_strimwidth($l['observacoes'], 0, 60, '...')); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($l['categoria'])): ?>
                                <span class="badge badge-info"><?php echo h($l['categoria']); ?></span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="color: <?php echo $l['tipo'] === 'RECEITA' ? 'var(--cor-sucesso)' : 'var(--cor-erro)'; ?>;">
                            <?php $valorExibido = $l['status'] === 'PAGO' ? $l['valor_original'] : $l['saldo_devedor']; ?>
                            <strong style="font-size:1rem;"><?php echo $l['tipo'] === 'RECEITA' ? '+' : '-'; ?> <?php echo formatarMoeda($valorExibido); ?></strong>
                            <?php if ($l['status'] === 'PARCIAL'): ?>
                                <br><small class="text-muted">de <?php echo formatarMoeda($l['valor_original']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <?php if (in_array($l['status'], ['PENDENTE', 'PARCIAL'], true)): ?>
                                <button type="button"
                                        class="btn btn-success btn-sm js-abrir-baixa"
                                        title="Baixar lancamento"
                                        aria-label="Baixar lançamento <?php echo h($l['descricao']); ?>"
                                        data-id="<?php echo h($l['id']); ?>"
                                        data-descricao="<?php echo h($l['descricao']); ?>"
                                        data-saldo="<?php echo h(number_format((float)$l['saldo_devedor'], 2, '.', '')); ?>"
                                        data-tipo="<?php echo h($l['tipo']); ?>">
                                    <i class="fas fa-check"></i>
                                </button>
                                <?php endif; ?>
                                <a href="<?php echo APP_URL; ?>financeiro/form?id=<?php echo urlencode($l['id']); ?>" 
                                   class="btn btn-secondary btn-sm" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="<?php echo APP_URL; ?>financeiro/actions?action=excluir&id=<?php echo urlencode($l['id']); ?>" 
                                   class="btn btn-danger btn-sm" title="Excluir"
                                   onclick="return confirm('Excluir este lancamento?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- Resumo -->
        <div class="card-footer" style="padding: 12px 20px;">
            <small class="text-muted">
                <i class="fas fa-info-circle"></i> 
                Total: <?php echo count($lancamentos); ?> lancamento(s) | 
                Receitas pagas: <?php echo formatarMoeda($totalReceitas); ?> |
                A receber: <?php echo formatarMoeda($totalAReceber); ?> |
                Despesas: <?php echo formatarMoeda($totalDespesas); ?> | 
                Saldo: <strong style="color: <?php echo $saldo >= 0 ? 'var(--cor-sucesso)' : 'var(--cor-erro)'; ?>;"><?php echo formatarMoeda($saldo); ?></strong>
            </small>
        </div>
    </div>
</div>

<div id="modalBaixa" class="financeiro-modal" aria-hidden="true">
    <div class="financeiro-modal__backdrop" data-fechar-baixa></div>
    <section class="financeiro-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="tituloModalBaixa">
        <header class="financeiro-modal__header">
            <div>
                <small id="subtituloModalBaixa" class="text-muted">Registrar movimentação</small>
                <h3 id="tituloModalBaixa" style="margin:3px 0 0;"><i class="fas fa-check-circle"></i> Baixar lançamento</h3>
            </div>
            <button type="button" class="financeiro-modal__close" data-fechar-baixa aria-label="Fechar"><i class="fas fa-times"></i></button>
        </header>
        <form method="POST" action="<?php echo APP_URL; ?>financeiro/actions?action=baixar" id="formBaixa" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo h($csrf); ?>">
            <input type="hidden" name="lancamento_id" id="baixaLancamentoId">
            <div class="financeiro-modal__body">
                <div class="baixa-resumo">
                    <span class="text-muted">Lançamento</span>
                    <strong id="baixaDescricao"></strong>
                    <span class="text-muted" style="margin-top:10px;">Saldo devedor atual</span>
                    <strong id="baixaSaldo" style="font-size:1.35rem;color:var(--cor-destaque);"></strong>
                </div>
                <div class="form-group">
                    <label for="baixaValor"><i class="fas fa-dollar-sign"></i> <span id="baixaValorLabel">Valor a pagar/receber</span> *</label>
                    <input type="text" id="baixaValor" name="valor_pago" inputmode="decimal" required maxlength="15">
                    <small class="text-muted">Digite exatamente o valor que entrou ou saiu do caixa.</small>
                    <div id="baixaSaldoRestante" class="baixa-saldo-restante" aria-live="polite">
                        <span>Após esta baixa, ficará faltando</span>
                        <strong id="baixaSaldoRestanteValor"></strong>
                    </div>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label for="baixaData"><i class="fas fa-calendar-check"></i> Data do pagamento *</label>
                        <input type="date" id="baixaData" name="data_pagamento" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="baixaFormaPagamento"><i class="fas fa-credit-card"></i> Forma de pagamento *</label>
                        <select id="baixaFormaPagamento" name="forma_pagamento" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($formasPagamento as $valorForma => $nomeForma): ?>
                                <option value="<?php echo h($valorForma); ?>"><?php echo h($nomeForma); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="baixaComprovantes"><i class="fas fa-paperclip"></i> Comprovante</label>
                    <input type="file"
                           id="baixaComprovantes"
                           name="comprovantes[]"
                           accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp"
                           multiple>
                    <small class="text-muted">PDF ou imagem, até 10MB por arquivo. O anexo ficará disponível no lápis deste lançamento.</small>
                    <div id="baixaArquivosSelecionados" class="baixa-arquivos" hidden></div>
                </div>
            </div>
            <footer class="financeiro-modal__footer">
                <button type="button" class="btn btn-secondary" data-fechar-baixa>Cancelar</button>
                <button type="submit" class="btn btn-success" id="confirmarBaixa"><i class="fas fa-check"></i> Confirmar baixa</button>
            </footer>
        </form>
    </section>
</div>

<style>
.baixa-saldo-restante{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:12px;padding:12px 14px;border:1px solid rgba(88,166,255,.35);border-radius:9px;background:rgba(88,166,255,.08)}.baixa-saldo-restante strong{font-size:1.15rem;color:var(--cor-destaque)}.baixa-saldo-restante.is-error{border-color:var(--cor-erro);background:rgba(231,76,60,.08)}.baixa-saldo-restante.is-error strong{color:var(--cor-erro)}.baixa-arquivos{margin-top:8px;padding:10px 12px;border:1px solid var(--cor-borda);border-radius:8px;color:var(--cor-texto-secundario);font-size:.85rem}.baixa-arquivos div+div{margin-top:4px}
.financeiro-modal__header{position:sticky;top:0;z-index:2;background:var(--cor-painel,#161b22)}.financeiro-modal__footer{position:sticky;bottom:0;z-index:2;background:var(--cor-painel,#161b22)}
.tabela-container table td:first-child,.tabela-container table th:first-child{min-width:105px!important;white-space:nowrap!important;word-break:normal!important;overflow-wrap:normal!important}
.tabela-container table td:first-child,.tabela-container table th:first-child,.tabela-container table td:nth-child(6),.tabela-container table th:nth-child(6){white-space:nowrap}.tabela-container table td:nth-child(4){min-width:180px}.tabela-container table td:nth-child(6){min-width:145px}.tabela-container table td:nth-child(6) small{display:block}.financeiro-modal{display:none;position:fixed;inset:0;z-index:10000;align-items:center;justify-content:center;padding:18px}.financeiro-modal.is-open{display:flex}.financeiro-modal__backdrop{position:absolute;inset:0;background:rgba(0,0,0,.68);backdrop-filter:blur(2px)}.financeiro-modal__dialog{position:relative;width:min(560px,100%);max-height:calc(100vh - 36px);overflow:auto;background:var(--cor-painel,#161b22);border:1px solid var(--cor-borda,#30363d);border-radius:14px;box-shadow:0 20px 55px rgba(0,0,0,.45)}.financeiro-modal__header,.financeiro-modal__footer{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:18px 20px;border-bottom:1px solid var(--cor-borda,#30363d)}.financeiro-modal__footer{justify-content:flex-end;border-top:1px solid var(--cor-borda,#30363d);border-bottom:0}.financeiro-modal__body{padding:20px}.financeiro-modal__close{border:0;background:transparent;color:var(--cor-texto-secundario,#8b949e);cursor:pointer;font-size:1.1rem;padding:8px}.baixa-resumo{display:flex;flex-direction:column;padding:15px;margin-bottom:18px;border:1px solid var(--cor-borda,#30363d);border-radius:10px;background:rgba(88,166,255,.06)}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('modalBaixa');
    const idInput = document.getElementById('baixaLancamentoId');
    const descricao = document.getElementById('baixaDescricao');
    const saldo = document.getElementById('baixaSaldo');
    const valor = document.getElementById('baixaValor');
    const label = document.getElementById('baixaValorLabel');
    const subtitulo = document.getElementById('subtituloModalBaixa');
    const formaPagamento = document.getElementById('baixaFormaPagamento');
    const comprovantes = document.getElementById('baixaComprovantes');
    const arquivosSelecionados = document.getElementById('baixaArquivosSelecionados');
    const saldoRestanteBox = document.getElementById('baixaSaldoRestante');
    const saldoRestanteValor = document.getElementById('baixaSaldoRestanteValor');
    const confirmarBaixa = document.getElementById('confirmarBaixa');
    const formatar = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });
    let ultimoFoco = null;
    let saldoAtualModal = 0;

    function valorDigitado() {
        const normalizado = valor.value.replace(/\./g, '').replace(',', '.');
        return Number(normalizado || 0);
    }

    function atualizarSaldoRestante() {
        const recebido = valorDigitado();
        const excedeu = recebido > saldoAtualModal;
        const restante = Math.max(0, saldoAtualModal - recebido);
        saldoRestanteValor.textContent = formatar.format(restante);
        saldoRestanteBox.classList.toggle('is-error', excedeu);
        valor.setCustomValidity(excedeu ? 'O valor recebido não pode ser maior que o saldo devedor.' : '');
        confirmarBaixa.disabled = recebido <= 0 || excedeu;
    }

    function fecharModal() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        if (ultimoFoco) ultimoFoco.focus();
    }

    document.querySelectorAll('.js-abrir-baixa').forEach(function (botao) {
        botao.addEventListener('click', function () {
            const saldoAtual = Number(botao.dataset.saldo);
            const receita = botao.dataset.tipo === 'RECEITA';
            saldoAtualModal = saldoAtual;
            ultimoFoco = botao;
            idInput.value = botao.dataset.id;
            descricao.textContent = botao.dataset.descricao;
            saldo.textContent = formatar.format(saldoAtual);
            valor.value = saldoAtual.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            formaPagamento.value = '';
            comprovantes.value = '';
            arquivosSelecionados.hidden = true;
            arquivosSelecionados.innerHTML = '';
            label.textContent = receita ? 'Valor a receber' : 'Valor a pagar';
            subtitulo.textContent = receita ? 'Registrar recebimento' : 'Registrar pagamento';
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            atualizarSaldoRestante();
            window.setTimeout(function () { valor.focus(); valor.select(); }, 0);
        });
    });

    valor.addEventListener('input', function () {
        const digitos = valor.value.replace(/\D/g, '').slice(0, 12);
        const centavos = Number(digitos || 0) / 100;
        valor.value = centavos.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        atualizarSaldoRestante();
    });

    comprovantes.addEventListener('change', function () {
        const arquivos = Array.from(comprovantes.files || []);
        arquivosSelecionados.hidden = arquivos.length === 0;
        arquivosSelecionados.innerHTML = arquivos.map(function (arquivo) {
            return '<div><i class="fas fa-file"></i> ' + arquivo.name.replace(/[<>&"']/g, '') + '</div>';
        }).join('');
    });

    modal.querySelectorAll('[data-fechar-baixa]').forEach(function (elemento) {
        elemento.addEventListener('click', fecharModal);
    });
    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape' && modal.classList.contains('is-open')) fecharModal();
    });
    document.getElementById('formBaixa').addEventListener('submit', function (evento) {
        atualizarSaldoRestante();
        if (!idInput.value || !valor.value || !formaPagamento.value || !valor.checkValidity()) evento.preventDefault();
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
