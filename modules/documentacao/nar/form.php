<?php
/**
 * MÓDULO: Documentação > Notas de Arqueação (AM-NAR)
 * Formulário com pré-preenchimento automático, calculadora interativa e chips de notas navais
 */

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/functions.php';

verificar_sessao();
if (!podeAcessar('documentacao')) {
    header('Location: ' . APP_URL . 'dashboard?erro=sem_permissao');
    exit;
}

$id = trim($_GET['id'] ?? '');
$editando = !empty($id);
$nar = null;

if ($editando) {
    $stmt = $pdo->prepare("SELECT * FROM certificados_nar WHERE id = :id AND ativo = 1");
    $stmt->execute([':id' => $id]);
    $nar = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$nar) {
        setMensagem('error', 'Nota de Arqueação não encontrada.');
        redirecionar(APP_URL . 'documentacao/nar');
    }
}

// Parâmetros opcionais para nova emissão a partir de RAP ou Embarcação
$analise_id_get = trim($_GET['analise_id'] ?? '');
$embarcacao_id_get = trim($_GET['embarcacao_id'] ?? '');

$dadosPreload = [];
if (!$editando) {
    if (!empty($analise_id_get)) {
        $stmtAn = $pdo->prepare("SELECT ap.*, e.nome AS emb_nome, e.tipo AS emb_tipo, e.registro, e.numero_inscricao,
                                         e.comprimento_total, e.comprimento_regra, e.comprimento_casco, e.comprimento_lpp,
                                         e.boca_moldada, e.pontal_moldado, e.calado_leve, e.calado_maximo,
                                         e.deslocamento_leve, e.deslocamento_carregado, e.porte_bruto,
                                         e.material_casco, e.porto_inscricao, e.ano_construcao,
                                         e.arqueacao_bruta, e.arqueacao_liquida, e.tripulantes, e.passageiros,
                                         c.nome AS cliente_nome, ra.id AS resp_id, ra.nome_completo AS resp_nome
                                  FROM analises_planos ap
                                  INNER JOIN embarcacoes e ON e.id = ap.embarcacao_id
                                  LEFT JOIN clientes c ON c.id = ap.solicitante_id
                                  LEFT JOIN responsaveis_assinatura ra ON ra.id = ap.responsavel_assinatura_id
                                  WHERE ap.id = :id");
        $stmtAn->execute([':id' => $analise_id_get]);
        $dadosPreload = $stmtAn->fetch(PDO::FETCH_ASSOC) ?: [];
    } elseif (!empty($embarcacao_id_get)) {
        $stmtEmb = $pdo->prepare("SELECT e.*, e.nome AS emb_nome, e.tipo AS emb_tipo, c.nome AS cliente_nome, c.id AS cliente_id
                                  FROM embarcacoes e
                                  LEFT JOIN clientes c ON c.id = e.cliente_id
                                  WHERE e.id = :id");
        $stmtEmb->execute([':id' => $embarcacao_id_get]);
        $dadosPreload = $stmtEmb->fetch(PDO::FETCH_ASSOC) ?: [];
    }
}

// Lista de Embarcações ativas para o select
$embarcacoes = $pdo->query("SELECT id, nome, registro, numero_inscricao, tipo, comprimento_total, boca_moldada, pontal_moldado FROM embarcacoes WHERE ativo = 1 ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

// Lista de Responsáveis por Assinatura
$responsaveis = $pdo->query("SELECT id, nome_completo, cargo_titulo, registro_profissional FROM responsaveis_assinatura WHERE ativo = 1 ORDER BY nome_completo ASC")->fetchAll(PDO::FETCH_ASSOC);

// Valores de campo pré-carregados
$embIdAtual = $nar['embarcacao_id'] ?? $dadosPreload['embarcacao_id'] ?? $dadosPreload['id'] ?? $embarcacao_id_get;
$analiseIdAtual = $nar['analise_id'] ?? $analise_id_get;
$clienteIdAtual = $nar['cliente_id'] ?? $dadosPreload['solicitante_id'] ?? $dadosPreload['cliente_id'] ?? '';

$nomeEmbarcacao = $nar['nome_embarcacao'] ?? $dadosPreload['emb_nome'] ?? $dadosPreload['nome'] ?? '';
$armador = $nar['armador'] ?? $dadosPreload['armador'] ?? $dadosPreload['cliente_nome'] ?? '';
$construtor = $nar['construtor'] ?? $dadosPreload['estaleiro_construtor'] ?? $dadosPreload['estaleiro'] ?? $armador;
$numeroCasco = $nar['numero_casco'] ?? $dadosPreload['numero_casco'] ?? 'x-x-x';
$materialCasco = $nar['material_casco'] ?? $dadosPreload['material_casco'] ?? 'AÇO';
$tipoEmbarcacao = $nar['tipo_embarcacao'] ?? $dadosPreload['emb_tipo'] ?? $dadosPreload['tipo'] ?? 'EMPURRADOR';
$atividadeServico = $nar['atividade_servico'] ?? $dadosPreload['atividade_servico'] ?? 'EMPURRADOR / TRANSPORTE DE CARGA';
$classificacao = $nar['classificacao'] ?? $dadosPreload['classificacao'] ?? 'CARGA GERAL / INTERIOR';
$portoInscricao = $nar['porto_inscricao'] ?? $dadosPreload['porto_inscricao'] ?? 'BELÉM - PA';
$dataConstrucao = $nar['data_construcao_quilha'] ?? $dadosPreload['ano_construcao'] ?? date('Y');

$ct = $nar['comprimento_total_ct'] ?? $dadosPreload['comprimento_total'] ?? '';
$l = $nar['comprimento_regra_l'] ?? $dadosPreload['comprimento_regra'] ?? $dadosPreload['comprimento_casco'] ?? '';
$lpp = $nar['comprimento_lpp'] ?? $dadosPreload['comprimento_lpp'] ?? '';
$b = $nar['boca_moldada_b'] ?? $dadosPreload['boca_moldada'] ?? '';
$p = $nar['pontal_moldado_p'] ?? $dadosPreload['pontal_moldado'] ?? '';

$caladoLeveAv = $nar['calado_leve_av'] ?? '0.400';
$caladoLeveAr = $nar['calado_leve_ar'] ?? '0.400';
$caladoLeveMed = $nar['calado_leve_medio'] ?? $dadosPreload['calado_leve'] ?? '0.400';

$caladoCarrAv = $nar['calado_carregado_av'] ?? '1.766';
$caladoCarrAr = $nar['calado_carregado_ar'] ?? '1.765';
$caladoCarrMed = $nar['calado_carregado_medio'] ?? $dadosPreload['calado_maximo'] ?? '1.765';

$tripulantes = $nar['numero_tripulantes'] ?? $dadosPreload['tripulantes'] ?? 0;
$n1 = $nar['n1_passageiros_camarotes'] ?? $dadosPreload['passageiros'] ?? 0;
$n2 = $nar['n2_demais_passageiros'] ?? 0;

$deslocCarregado = $nar['deslocamento_carregado'] ?? $dadosPreload['deslocamento_carregado'] ?? '';
$deslocLeve = $nar['deslocamento_leve'] ?? $dadosPreload['deslocamento_leve'] ?? '';
$porteBruto = $nar['porte_bruto'] ?? $dadosPreload['porte_bruto'] ?? '';

$espAbaixo = $nar['espacos_fechados_abaixo_conves'] ?? '';
$espAcima = $nar['espacos_fechados_acima_conves'] ?? '0.00';
$espExcluidos = $nar['espacos_excluidos'] ?? '0.00';
$volCarga = $nar['volume_espacos_carga_vc'] ?? '0.00';

$volTotal = $nar['volume_total_fechado_v'] ?? '';
$k1 = $nar['coeficiente_k1'] ?? '';
$ab = $nar['arqueacao_bruta_ab'] ?? $dadosPreload['arqueacao_bruta'] ?? '';
$k2 = $nar['coeficiente_k2'] ?? '';
$al = $nar['arqueacao_liquida_al'] ?? $dadosPreload['arqueacao_liquida'] ?? '';

$metodoAbaixo = $nar['metodo_obtencao_abaixo'] ?? 'Volume obtido com a utilização de curvas hidrostáticas.';
$metodoAcima = $nar['metodo_obtencao_acima'] ?? 'Volume obtido com a utilização de formas geométricas.';

// Deserializar itens dos volumes ou carregar padrões
$volumesAbaixo = !empty($nar['volumes_abaixo_conves_json']) ? json_decode($nar['volumes_abaixo_conves_json'], true) : [
    ['descricao' => 'Volume do casco mais tosamento', 'volume' => $espAbaixo ?: '1650.06']
];
$volumesAcima = !empty($nar['volumes_acima_conves_json']) ? json_decode($nar['volumes_acima_conves_json'], true) : [
    ['descricao' => 'Casaria do Convés Principal Completa.', 'volume' => '0.00'],
    ['descricao' => 'Casaria do Convés Superior.', 'volume' => '0.00'],
    ['descricao' => 'Casaria do convés intermediário', 'volume' => '0.00'],
    ['descricao' => 'Casaria do convés comando.', 'volume' => '0.00']
];

$observacoesNotas = $nar['observacoes_notas'] ?? (
    "A embarcação possui um comprimento total com apêndice de " . ($ct ? number_format((float)$ct, 3, ',', '') . " m" : "24,215 m") . " e de casco de " . ($l ? number_format((float)$l, 3, ',', '') . " m" : "22,700 m") . ".\n\n" .
    "A emb. Está autorizada a acomodar até 08 Extra Roll, de acordo com planos e doc. técnicos apresentados.\n\n" .
    "A embarcação tem como atividade/serviço empurra e transporte de carga.\n\n" .
    "Os espaços considerados excluídos, não poderão der usados para transportes de carga ou passageiros somente para a tripulação, se enquadra no item 7.9.) 7.9.1. caso a), figura 7.6, espaços excluídos.\n\n" .
    "A embarcação tem como atividade/serviço empurra. O espaço considerado excluído, não poderão ser usados para provisões, ele se enquadra no item 7.9.) 7.9.2. caso b), figura 7-7: Espaços excluídos."
);

$enquadramento = $nar['enquadramento_comprimento'] ?? ((float)$l >= 24.0 ? 'L_MAIOR_IGUAL_24' : 'L_MENOR_24');
$respIdAtual = $nar['responsavel_assinatura_id'] ?? $dadosPreload['resp_id'] ?? ($responsaveis[0]['id'] ?? '');

$titulo_page = ($editando ? 'Editar Nota de Arqueação ' . $nar['numero'] : 'Nova Nota de Arqueação (AM-NAR)') . ' - ' . APP_NAME;
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="conteudo-principal">
    <div class="tabela-header">
        <div>
            <h2>
                <i class="fa-solid fa-calculator text-primary"></i> 
                <?= $editando ? 'Editar Nota de Arqueação ' . h($nar['numero']) : 'Nova Nota de Arqueação (AM-NAR)' ?>
            </h2>
            <p class="text-muted" style="margin:4px 0 0; font-size:0.88rem;">
                Memória oficial de cálculo de arqueação naval com resgate automático de dados e geração do PDF em 3 páginas.
            </p>
        </div>
        <div class="d-flex gap-2">
            <?php if ($editando): ?>
                <a href="<?= APP_URL ?>documentacao/nar/pdf?id=<?= urlencode($id) ?>" target="_blank" class="btn btn-outline-danger">
                    <i class="fa-solid fa-file-pdf"></i> Visualizar PDF
                </a>
            <?php endif; ?>
            <a href="<?= APP_URL ?>documentacao/nar" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Voltar à Listagem
            </a>
        </div>
    </div>

    <form method="POST" action="<?= APP_URL ?>documentacao/nar/actions" id="formNar" class="card p-4">
        <input type="hidden" name="action" value="salvar">
        <input type="hidden" name="csrf_token" value="<?= gerarCSRF() ?>">
        <input type="hidden" name="id" value="<?= h($id) ?>">
        <input type="hidden" name="cliente_id" id="cliente_id" value="<?= h($clienteIdAtual) ?>">
        <input type="hidden" name="analise_id" value="<?= h($analiseIdAtual) ?>">

        <!-- CABEÇALHO DO PROCESSO & ENQUADRAMENTO NAVAL -->
        <div class="card mb-4" style="background:#f8fafc; border:1px solid #cbd5e1; border-radius:10px;">
            <div class="card-body">
                <div class="row g-3 align-items-center">
                    <div class="col-md-5">
                        <label class="form-label-bold"><i class="fa-solid fa-ship text-primary"></i> Embarcação *</label>
                        <select name="embarcacao_id" id="embarcacao_id" class="form-control" required onchange="carregarDadosEmbarcacao(this.value)">
                            <option value="">Selecione uma embarcação...</option>
                            <?php foreach ($embarcacoes as $emb): ?>
                                <option value="<?= h($emb['id']) ?>" 
                                        <?= $emb['id'] === $embIdAtual ? 'selected' : '' ?>
                                        data-nome="<?= h($emb['nome']) ?>"
                                        data-ct="<?= h($emb['comprimento_total']) ?>"
                                        data-b="<?= h($emb['boca_moldada']) ?>"
                                        data-p="<?= h($emb['pontal_moldado']) ?>"
                                        data-tipo="<?= h($emb['tipo']) ?>">
                                    <?= h($emb['nome']) ?> (<?= h($emb['tipo'] ?: 'Naval') ?> - <?= h($emb['numero_inscricao'] ?: $emb['registro'] ?: 'Sem reg.') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">A seleção da embarcação atualiza as dimensões e parâmetros da arqueação.</small>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label-bold"><i class="fa-solid fa-ruler-horizontal text-secondary"></i> Enquadramento da Norma *</label>
                        <select name="enquadramento_comprimento" id="enquadramento_comprimento" class="form-control" required>
                            <option value="L_MAIOR_IGUAL_24" <?= $enquadramento === 'L_MAIOR_IGUAL_24' ? 'selected' : '' ?>>
                                Embarcação de Grande Porte (L ≥ 24 m)
                            </option>
                            <option value="L_MENOR_24" <?= $enquadramento === 'L_MENOR_24' ? 'selected' : '' ?>>
                                Embarcação de Médio / Pequeno Porte (L < 24 m)
                            </option>
                        </select>
                        <small class="text-muted">Define o título oficial no cabeçalho do documento de arqueação.</small>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label-bold"><i class="fa-solid fa-calendar-day text-info"></i> Data de Emissão *</label>
                        <input type="date" name="data_emissao" class="form-control" value="<?= h($nar['data_emissao'] ?? date('Y-m-d')) ?>" required>
                    </div>
                </div>
            </div>
        </div>

        <!-- 1. CARACTERÍSTICAS GERAIS -->
        <h4 style="font-weight:750; color:#0f172a; margin-bottom:12px; display:flex; align-items:center; gap:8px;">
            <span class="badge bg-primary" style="font-size:0.85rem;">1</span> Características Gerais da Embarcação
        </h4>
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <label class="form-label-bold">Nome da Embarcação *</label>
                <input type="text" name="nome_embarcacao" id="nome_embarcacao" class="form-control" value="<?= h($nomeEmbarcacao) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label-bold">Armador / Proprietário</label>
                <input type="text" name="armador" id="armador" class="form-control" value="<?= h($armador) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label-bold">Estaleiro / Construtor</label>
                <input type="text" name="construtor" id="construtor" class="form-control" value="<?= h($construtor) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label-bold">Número do Casco</label>
                <input type="text" name="numero_casco" class="form-control" value="<?= h($numeroCasco) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label-bold">Material do Casco</label>
                <input type="text" name="material_casco" class="form-control" value="<?= h($materialCasco) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label-bold">Tipo da Embarcação</label>
                <input type="text" name="tipo_embarcacao" id="tipo_embarcacao" class="form-control" value="<?= h($tipoEmbarcacao) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label-bold">Atividade / Serviço</label>
                <input type="text" name="atividade_servico" class="form-control" value="<?= h($atividadeServico) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label-bold">Classificação Operacional</label>
                <input type="text" name="classificacao" class="form-control" value="<?= h($classificacao) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label-bold">Porto de Inscrição</label>
                <input type="text" name="porto_inscricao" class="form-control" value="<?= h($portoInscricao) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label-bold">Ano de Construção / Batimento de Quilha</label>
                <input type="text" name="data_construcao_quilha" class="form-control" value="<?= h($dataConstrucao) ?>">
            </div>
        </div>

        <!-- 2. CARACTERÍSTICAS DO CASCO E CALADOS -->
        <h4 style="font-weight:750; color:#0f172a; margin-bottom:12px; display:flex; align-items:center; gap:8px;">
            <span class="badge bg-primary" style="font-size:0.85rem;">2</span> Características do Casco e Calados
        </h4>
        <div class="row g-3 mb-4">
            <div class="col-md-2">
                <label class="form-label-bold">Ct (Comprimento Total) [m]</label>
                <input type="number" step="0.001" name="comprimento_total_ct" id="ct" class="form-control" value="<?= h($ct) ?>" oninput="recalcularArqueacao()">
            </div>
            <div class="col-md-2">
                <label class="form-label-bold">L (Comprimento de Regra) [m]</label>
                <input type="number" step="0.001" name="comprimento_regra_l" id="l" class="form-control" value="<?= h($l) ?>" oninput="recalcularArqueacao()">
            </div>
            <div class="col-md-2">
                <label class="form-label-bold">Lpp (Entre Perpendiculares) [m]</label>
                <input type="number" step="0.001" name="comprimento_lpp" class="form-control" value="<?= h($lpp) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label-bold">B (Boca Moldada) [m]</label>
                <input type="number" step="0.001" name="boca_moldada_b" id="b" class="form-control" value="<?= h($b) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label-bold">P (Pontal Moldado) [m]</label>
                <input type="number" step="0.001" name="pontal_moldado_p" id="p" class="form-control" value="<?= h($p) ?>" oninput="recalcularArqueacao()">
            </div>

            <!-- Calados Leve e Carregado -->
            <div class="col-md-6">
                <div class="p-3" style="background:#f1f5f9; border-radius:8px; border:1px solid #e2e8f0;">
                    <strong class="d-block mb-2 text-dark"><i class="fa-solid fa-water"></i> Calado Leve (Hl)</strong>
                    <div class="row g-2">
                        <div class="col-4">
                            <label style="font-size:0.75rem;">AV (m)</label>
                            <input type="number" step="0.001" name="calado_leve_av" class="form-control form-control-sm" value="<?= h($caladoLeveAv) ?>">
                        </div>
                        <div class="col-4">
                            <label style="font-size:0.75rem;">AR (m)</label>
                            <input type="number" step="0.001" name="calado_leve_ar" class="form-control form-control-sm" value="<?= h($caladoLeveAr) ?>">
                        </div>
                        <div class="col-4">
                            <label style="font-size:0.75rem;">Médio (m)</label>
                            <input type="number" step="0.001" name="calado_leve_medio" id="calado_leve_medio" class="form-control form-control-sm" value="<?= h($caladoLeveMed) ?>" oninput="recalcularArqueacao()">
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="p-3" style="background:#f0fdf4; border-radius:8px; border:1px solid #bbf7d0;">
                    <strong class="d-block mb-2 text-success"><i class="fa-solid fa-anchor"></i> Calado Carregado (Hc)</strong>
                    <div class="row g-2">
                        <div class="col-4">
                            <label style="font-size:0.75rem;">AV (m)</label>
                            <input type="number" step="0.001" name="calado_carregado_av" class="form-control form-control-sm" value="<?= h($caladoCarrAv) ?>">
                        </div>
                        <div class="col-4">
                            <label style="font-size:0.75rem;">AR (m)</label>
                            <input type="number" step="0.001" name="calado_carregado_ar" class="form-control form-control-sm" value="<?= h($caladoCarrAr) ?>">
                        </div>
                        <div class="col-4">
                            <label style="font-size:0.75rem;">Médio (m)</label>
                            <input type="number" step="0.001" name="calado_carregado_medio" id="calado_carregado_medio" class="form-control form-control-sm" value="<?= h($caladoCarrMed) ?>" oninput="recalcularArqueacao()">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. TRIPULANTES E PASSAGEIROS & CARACTERÍSTICAS CALCULADAS -->
        <h4 style="font-weight:750; color:#0f172a; margin-bottom:12px; display:flex; align-items:center; gap:8px;">
            <span class="badge bg-primary" style="font-size:0.85rem;">3</span> Tripulação, Deslocamentos e Espaços
        </h4>
        <div class="row g-3 mb-4">
            <div class="col-md-2">
                <label class="form-label-bold">Tripulantes</label>
                <input type="number" name="numero_tripulantes" id="numero_tripulantes" class="form-control" value="<?= h($tripulantes) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label-bold">N1 (Camarotes < 8)</label>
                <input type="number" name="n1_passageiros_camarotes" id="n1" class="form-control" value="<?= h($n1) ?>" oninput="recalcularArqueacao()">
            </div>
            <div class="col-md-2">
                <label class="form-label-bold">N2 (Demais Passag.)</label>
                <input type="number" name="n2_demais_passageiros" id="n2" class="form-control" value="<?= h($n2) ?>" oninput="recalcularArqueacao()">
            </div>
            <div class="col-md-2">
                <label class="form-label-bold">Desloc. Carregado [t]</label>
                <input type="number" step="0.001" name="deslocamento_carregado" class="form-control" value="<?= h($deslocCarregado) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label-bold">Desloc. Leve [t]</label>
                <input type="number" step="0.001" name="deslocamento_leve" class="form-control" value="<?= h($deslocLeve) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label-bold">Porte Bruto [t]</label>
                <input type="number" step="0.001" name="porte_bruto" class="form-control" value="<?= h($porteBruto) ?>">
            </div>
        </div>

        <!-- 4. MEMÓRIA DE CÁLCULO DE ARQUEAÇÃO (AB e AL) INTERATIVA -->
        <div class="card mb-4" style="background: linear-gradient(180deg, #f0fdfa, #ccfbf1); border:1px solid #99f6e4; border-radius:10px;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 style="font-weight:750; color:#0f766e; margin:0;">
                        <i class="fa-solid fa-calculator"></i> Memória de Cálculo Automático de Arqueação (NORMAM)
                    </h5>
                    <button type="button" class="btn btn-sm btn-outline-teal" onclick="recalcularArqueacao(true)" style="background:#fff;">
                        <i class="fa-solid fa-arrows-rotate"></i> Recalcular Fórmulas
                    </button>
                </div>

                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label-bold">Espaços Abaixo do Convés (V1) [m³] *</label>
                        <input type="number" step="0.01" name="espacos_fechados_abaixo_conves" id="espacos_abaixo" class="form-control font-weight-bold" value="<?= h($espAbaixo) ?>" required oninput="recalcularArqueacao()">
                        <small class="text-muted">Volume do casco + tosamento</small>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label-bold">Casarias Acima do Convés (V2) [m³]</label>
                        <input type="number" step="0.01" name="espacos_fechados_acima_conves" id="espacos_acima" class="form-control font-weight-bold" value="<?= h($espAcima) ?>" oninput="recalcularArqueacao()">
                        <small class="text-muted">Soma dos volumes das casarias</small>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label-bold">Volume Total Fechado (V) [m³]</label>
                        <input type="number" step="0.01" name="volume_total_fechado_v" id="volume_total" class="form-control font-weight-bold" value="<?= h($volTotal) ?>" style="background:#e6fffa; color:#0f766e;" readonly>
                        <small class="text-muted">V = V1 + V2</small>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label-bold">Volume Espaços Carga (Vc) [m³]</label>
                        <input type="number" step="0.01" name="volume_espacos_carga_vc" id="volume_carga" class="form-control" value="<?= h($volCarga) ?>" oninput="recalcularArqueacao()">
                        <small class="text-muted">Espaços de carga fechados</small>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label-bold">Coeficiente K1</label>
                        <input type="number" step="0.0001" name="coeficiente_k1" id="k1" class="form-control" value="<?= h($k1) ?>" readonly>
                        <small class="text-muted">K1 = 0,2 + 0,02 &times; log10(V)</small>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label-bold" style="color:#0f766e; font-size:1.05rem;">Arqueação Bruta (AB) *</label>
                        <input type="number" name="arqueacao_bruta_ab" id="ab" class="form-control form-control-lg font-weight-bold" style="border:2px solid #0f766e; color:#0f766e;" value="<?= h($ab) ?>" required>
                        <small class="text-muted">AB = K1 &times; V (arredondado)</small>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label-bold">Coeficiente K2</label>
                        <input type="number" step="0.0001" name="coeficiente_k2" id="k2" class="form-control" value="<?= h($k2) ?>" readonly>
                        <small class="text-muted">K2 = 0,2 + 0,02 &times; log10(Vc)</small>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label-bold" style="color:#0369a1; font-size:1.05rem;">Arqueação Líquida (AL) *</label>
                        <input type="number" name="arqueacao_liquida_al" id="al" class="form-control form-control-lg font-weight-bold" style="border:2px solid #0369a1; color:#0369a1;" value="<?= h($al) ?>" required>
                        <small class="text-muted">AL ≥ 30% da AB</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. ANEXO - VOLUMES DAS CASARIAS E MÉTODOS DE OBTENÇÃO -->
        <h4 style="font-weight:750; color:#0f172a; margin-bottom:12px; display:flex; align-items:center; gap:8px;">
            <span class="badge bg-primary" style="font-size:0.85rem;">5</span> ANEXO: Detalhamento dos Volumes das Casarias (Página 3 do PDF)
        </h4>

        <div class="row g-3 mb-4">
            <!-- Volumes Abaixo do Convés -->
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <strong>a.1) Volumes Abaixo do Convés Principal</strong>
                        <button type="button" class="btn btn-xs btn-outline-primary" onclick="adicionarLinhaVolume('tabelaVolumesAbaixo', 'volumes_abaixo')">
                            <i class="fa-solid fa-plus"></i> Item
                        </button>
                    </div>
                    <div class="card-body p-2">
                        <table class="table table-sm table-borderless mb-2" id="tabelaVolumesAbaixo">
                            <tbody>
                                <?php foreach ($volumesAbaixo as $idx => $vItem): ?>
                                    <tr>
                                        <td>
                                            <input type="text" name="volumes_abaixo[<?= $idx ?>][descricao]" class="form-control form-control-sm" value="<?= h($vItem['descricao']) ?>" placeholder="Ex: Volume do casco mais tosamento">
                                        </td>
                                        <td style="width:130px;">
                                            <input type="number" step="0.01" name="volumes_abaixo[<?= $idx ?>][volume]" class="form-control form-control-sm input-vol-abaixo" value="<?= h($vItem['volume']) ?>" placeholder="m³" oninput="somarVolumesAbaixo()">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="form-group mt-2">
                            <label style="font-size:0.78rem; font-weight:700;">Método de Obtenção dos Volumes Abaixo:</label>
                            <select name="metodo_obtencao_abaixo" class="form-control form-control-sm">
                                <option value="Volume obtido com a utilização de curvas hidrostáticas." <?= $metodoAbaixo === 'Volume obtido com a utilização de curvas hidrostáticas.' ? 'selected' : '' ?>>
                                    Volume obtido com a utilização de curvas hidrostáticas.
                                </option>
                                <option value="Volume obtido com a utilização de formas geométricas." <?= $metodoAbaixo === 'Volume obtido com a utilização de formas geométricas.' ? 'selected' : '' ?>>
                                    Volume obtido com a utilização de formas geométricas.
                                </option>
                                <option value="Volume obtido com a utilização de formas geométricas tabela de capacidade." <?= $metodoAbaixo === 'Volume obtido com a utilização de formas geométricas tabela de capacidade.' ? 'selected' : '' ?>>
                                    Volume obtido com a utilização de formas geométricas tabela de capacidade.
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Volumes Acima do Convés (Casarias) -->
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <strong>a.2) Volumes Acima do Convés Superior (Casarias)</strong>
                        <button type="button" class="btn btn-xs btn-outline-primary" onclick="adicionarLinhaVolume('tabelaVolumesAcima', 'volumes_acima')">
                            <i class="fa-solid fa-plus"></i> Casaria
                        </button>
                    </div>
                    <div class="card-body p-2">
                        <table class="table table-sm table-borderless mb-2" id="tabelaVolumesAcima">
                            <tbody>
                                <?php foreach ($volumesAcima as $idx => $vItem): ?>
                                    <tr>
                                        <td>
                                            <input type="text" name="volumes_acima[<?= $idx ?>][descricao]" class="form-control form-control-sm" value="<?= h($vItem['descricao']) ?>" placeholder="Ex: Casaria do Convés Superior">
                                        </td>
                                        <td style="width:130px;">
                                            <input type="number" step="0.01" name="volumes_acima[<?= $idx ?>][volume]" class="form-control form-control-sm input-vol-acima" value="<?= h($vItem['volume']) ?>" placeholder="m³" oninput="somarVolumesAcima()">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="form-group mt-2">
                            <label style="font-size:0.78rem; font-weight:700;">Método de Obtenção dos Volumes das Casarias:</label>
                            <select name="metodo_obtencao_acima" class="form-control form-control-sm">
                                <option value="Volume obtido com a utilização de formas geométricas." <?= $metodoAcima === 'Volume obtido com a utilização de formas geométricas.' ? 'selected' : '' ?>>
                                    Volume obtido com a utilização de formas geométricas.
                                </option>
                                <option value="Volume obtido com a utilização de formas geométricas tabela de capacidade." <?= $metodoAcima === 'Volume obtido com a utilização de formas geométricas tabela de capacidade.' ? 'selected' : '' ?>>
                                    Volume obtido com a utilização de formas geométricas tabela de capacidade.
                                </option>
                                <option value="Volume obtido com a utilização de curvas hidrostáticas." <?= $metodoAcima === 'Volume obtido com a utilização de curvas hidrostáticas.' ? 'selected' : '' ?>>
                                    Volume obtido com a utilização de curvas hidrostáticas.
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 6. SEÇÃO DE NOTAS / OBSERVAÇÕES TÉCNICAS (PÁGINA 2 DO PDF) -->
        <h4 style="font-weight:750; color:#0f172a; margin-bottom:12px; display:flex; align-items:center; gap:8px;">
            <span class="badge bg-primary" style="font-size:0.85rem;">6</span> Seção 4: Observações e NOTAS Técnicas (Página 2 do PDF)
        </h4>

        <!-- Chips de Inserção Rápida em 1 Clique -->
        <div class="p-3 mb-2" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px;">
            <div style="font-size:0.8rem; font-weight:700; color:#475569; margin-bottom:8px;">
                <i class="fa-solid fa-bolt text-warning"></i> Atalhos para Inserir Textos Oficiais da NORMAM em 1 Clique:
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="inserirTextoNota('dimensoes')">
                    + Dimensões com Apêndice e Casco
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="inserirTextoNota('extraroll')">
                    + Lotação Extra Roll
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="inserirTextoNota('atividade')">
                    + Atividade Empurrador/Carga
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="inserirTextoNota('excluidos_791')">
                    + Espaços Excluídos (Item 7.9.1 caso a, fig 7.6)
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="inserirTextoNota('excluidos_792')">
                    + Espaços Excluídos (Item 7.9.2 caso b, fig 7.7)
                </button>
            </div>
        </div>

        <div class="form-group mb-4">
            <textarea name="observacoes_notas" id="observacoes_notas" rows="6" class="form-control" style="font-family:monospace; font-size:0.9rem;" placeholder="Insira aqui as notas, condições e observações oficiais da arqueação..."><?= h($observacoesNotas) ?></textarea>
            <small class="text-muted">Estes textos serão impressos na Seção 4 (Observações) da Página 2 da Nota de Arqueação oficial.</small>
        </div>

        <!-- 7. RESPONSÁVEL TÉCNICO E ASSINATURA -->
        <h4 style="font-weight:750; color:#0f172a; margin-bottom:12px; display:flex; align-items:center; gap:8px;">
            <span class="badge bg-primary" style="font-size:0.85rem;">7</span> Responsável Técnico e Local de Expedição
        </h4>
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <label class="form-label-bold">Responsável Técnico Habilitado *</label>
                <select name="responsavel_assinatura_id" id="responsavel_assinatura_id" class="form-control" required onchange="atualizarResponsavel(this)">
                    <option value="">Selecione o profissional...</option>
                    <?php foreach ($responsaveis as $resp): ?>
                        <option value="<?= h($resp['id']) ?>" 
                                <?= $resp['id'] == $respIdAtual ? 'selected' : '' ?>
                                data-nome="<?= h($resp['nome_completo']) ?>"
                                data-titulo="<?= h($resp['cargo_titulo']) ?>"
                                data-reg="<?= h($resp['registro_profissional']) ?>">
                            <?= h($resp['nome_completo']) ?> (<?= h($resp['cargo_titulo']) ?> - <?= h($resp['registro_profissional']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label-bold">Nome do Signatário</label>
                <input type="text" name="assinante_nome" id="assinante_nome" class="form-control" value="<?= h($nar['assinante_nome'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label-bold">Título Profissional</label>
                <input type="text" name="assinante_titulo" id="assinante_titulo" class="form-control" value="<?= h($nar['assinante_titulo'] ?? 'TECNÓLOGO NAVAL') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label-bold">Registro CREA</label>
                <input type="text" name="assinante_registro" id="assinante_registro" class="form-control" value="<?= h($nar['assinante_registro'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label-bold">Local de Emissão</label>
                <input type="text" name="local_emissao" class="form-control" value="<?= h($nar['local_emissao'] ?? 'Belém - PA') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label-bold">Situação da NAR</label>
                <select name="status" class="form-control">
                    <option value="emitido" <?= ($nar['status'] ?? 'emitido') === 'emitido' ? 'selected' : '' ?>>Emitido (Pronto para assinatura)</option>
                    <option value="rascunho" <?= ($nar['status'] ?? '') === 'rascunho' ? 'selected' : '' ?>>Rascunho</option>
                    <?php if ($editando && $nar['status'] === 'assinado'): ?>
                        <option value="assinado" selected>Assinado Digitalmente</option>
                    <?php endif; ?>
                    <option value="cancelado" <?= ($nar['status'] ?? '') === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                </select>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
            <a href="<?= APP_URL ?>documentacao/nar" class="btn btn-secondary">
                <i class="fas fa-times"></i> Cancelar
            </a>
            <button type="submit" class="btn btn-success btn-lg">
                <i class="fas fa-save"></i> <?= $editando ? 'Salvar Alterações da NAR' : 'Gerar e Emitir Nota de Arqueação (AM-NAR)' ?>
            </button>
        </div>
    </form>
</div>

<script>
// Atualizar dados da embarcação ao selecionar no dropdown
function carregarDadosEmbarcacao(embId) {
    if (!embId) return;
    const opt = document.querySelector(`#embarcacao_id option[value="${embId}"]`);
    if (!opt) return;

    if (opt.dataset.nome) document.getElementById('nome_embarcacao').value = opt.dataset.nome;
    if (opt.dataset.ct) document.getElementById('ct').value = opt.dataset.ct;
    if (opt.dataset.b) document.getElementById('b').value = opt.dataset.b;
    if (opt.dataset.p) document.getElementById('p').value = opt.dataset.p;
    if (opt.dataset.tipo) document.getElementById('tipo_embarcacao').value = opt.dataset.tipo;

    recalcularArqueacao();
}

// Atualizar dados do Responsável Técnico
function atualizarResponsavel(sel) {
    const opt = sel.options[sel.selectedIndex];
    if (opt && opt.dataset.nome) {
        document.getElementById('assinante_nome').value = opt.dataset.nome;
        document.getElementById('assinante_titulo').value = opt.dataset.titulo || 'TECNÓLOGO NAVAL';
        document.getElementById('assinante_registro').value = opt.dataset.reg || '';
    }
}

// Somar volumes da tabela abaixo
function somarVolumesAbaixo() {
    let tot = 0;
    document.querySelectorAll('.input-vol-abaixo').forEach(input => {
        const v = parseFloat(input.value) || 0;
        tot += v;
    });
    if (tot > 0) {
        document.getElementById('espacos_abaixo').value = tot.toFixed(2);
        recalcularArqueacao();
    }
}

// Somar volumes da tabela acima
function somarVolumesAcima() {
    let tot = 0;
    document.querySelectorAll('.input-vol-acima').forEach(input => {
        const v = parseFloat(input.value) || 0;
        tot += v;
    });
    document.getElementById('espacos_acima').value = tot.toFixed(2);
    recalcularArqueacao();
}

// Adicionar nova linha de volume no anexo
function adicionarLinhaVolume(tabelaId, prefixo) {
    const tbody = document.querySelector(`#${tabelaId} tbody`);
    const count = tbody.querySelectorAll('tr').length;
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>
            <input type="text" name="${prefixo}[${count}][descricao]" class="form-control form-control-sm" placeholder="Descrição do espaço / casaria">
        </td>
        <td style="width:130px;">
            <input type="number" step="0.01" name="${prefixo}[${count}][volume]" class="form-control form-control-sm ${prefixo === 'volumes_abaixo' ? 'input-vol-abaixo' : 'input-vol-acima'}" placeholder="m³" oninput="${prefixo === 'volumes_abaixo' ? 'somarVolumesAbaixo()' : 'somarVolumesAcima()'}">
        </td>
    `;
    tbody.appendChild(tr);
}

// Recalcular fórmulas de Arqueação Bruta (AB) e Líquida (AL)
function recalcularArqueacao(forcar = false) {
    const v1 = parseFloat(document.getElementById('espacos_abaixo').value) || 0;
    const v2 = parseFloat(document.getElementById('espacos_acima').value) || 0;
    const vc = parseFloat(document.getElementById('volume_carga').value) || 0;
    const p = parseFloat(document.getElementById('p').value) || 1;
    const hc = parseFloat(document.getElementById('calado_carregado_medio').value) || parseFloat(document.getElementById('calado_leve_medio').value) || 1;
    const n1 = parseInt(document.getElementById('n1').value) || 0;
    const n2 = parseInt(document.getElementById('n2').value) || 0;

    const vTotal = v1 + v2;
    document.getElementById('volume_total').value = vTotal.toFixed(2);

    if (vTotal > 0) {
        const k1 = 0.2 + 0.02 * Math.log10(vTotal);
        document.getElementById('k1').value = k1.toFixed(4);

        const abInput = document.getElementById('ab');
        if (forcar || !abInput.value || parseFloat(abInput.value) <= 0) {
            const abCalc = Math.floor(k1 * vTotal);
            abInput.value = abCalc;
        }

        let k2 = 0;
        if (vc > 0) {
            k2 = 0.2 + 0.02 * Math.log10(vc);
        }
        document.getElementById('k2').value = k2.toFixed(4);

        const alInput = document.getElementById('al');
        if (forcar || !alInput.value || parseFloat(alInput.value) <= 0) {
            const abValor = parseFloat(abInput.value) || Math.floor(k1 * vTotal);
            const nTotal = n1 + n2;
            const fatorN = (nTotal >= 13) ? (1.25 * (abValor + 10000) / 10000 * (n1 + (n2 / 10.0))) : 0;

            const exp1 = Math.pow((4 * hc) / (3 * p), 2);
            const exp1Usar = exp1 > 1.0 ? 1.0 : exp1;

            let termoCarga = k2 * vc * exp1Usar;
            if (termoCarga <= (0.25 * abValor)) {
                termoCarga = 0.25 * abValor;
            }

            const alCalc = Math.ceil(Math.max(termoCarga + fatorN, 0.30 * abValor));
            alInput.value = alCalc;
        }
    }
}

// Inserir textos rápidos de notas navais no textarea
function inserirTextoNota(tipo) {
    const textarea = document.getElementById('observacoes_notas');
    const ct = document.getElementById('ct').value || '24,215';
    const l = document.getElementById('l').value || '22,700';

    let texto = '';
    switch (tipo) {
        case 'dimensoes':
            texto = `A embarcação possui um comprimento total com apêndice de ${ct} m e de casco de ${l} m.`;
            break;
        case 'extraroll':
            texto = `A emb. Está autorizada a acomodar até 08 Extra Roll, de acordo com planos e doc. técnicos apresentados.`;
            break;
        case 'atividade':
            texto = `A embarcação tem como atividade/serviço empurra e transporte de carga.`;
            break;
        case 'excluidos_791':
            texto = `Os espaços considerados excluídos, não poderão der usados para transportes de carga ou passageiros somente para a tripulação, se enquadra no item 7.9.) 7.9.1. caso a), figura 7.6, espaços excluídos.`;
            break;
        case 'excluidos_792':
            texto = `A embarcação tem como atividade/serviço empurra. O espaço considerado excluído, não poderão ser usados para provisões, ele se enquadra no item 7.9.) 7.9.2. caso b), figura 7-7: Espaços excluídos.`;
            break;
    }

    if (textarea.value.trim() !== '') {
        textarea.value = textarea.value.trim() + "\n\n" + texto;
    } else {
        textarea.value = texto;
    }
    textarea.focus();
}

// Inicializar responsável se estiver vazio
document.addEventListener('DOMContentLoaded', function() {
    const sel = document.getElementById('responsavel_assinatura_id');
    if (sel && sel.value && !document.getElementById('assinante_nome').value) {
        atualizarResponsavel(sel);
    }
    if (document.getElementById('volume_total').value === '' || document.getElementById('volume_total').value === '0.00') {
        recalcularArqueacao();
    }
});
</script>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
