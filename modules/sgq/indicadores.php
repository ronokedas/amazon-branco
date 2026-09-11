<?php
/**
 * MÓDULO: SGQ - GESTÃO DA QUALIDADE (ISO 9001:2015 & NORMAM)
 * Arquivo: modules/sgq/indicadores.php
 * Painel de Indicadores da Qualidade (ISO 9.1 - Avaliação de Desempenho e Medição)
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
exigirAcesso('dashboard');

// Período de análise
$dataInicio = trim((string)($_GET['data_inicio'] ?? date('Y-01-01')));
$dataFim = trim((string)($_GET['data_fim'] ?? date('Y-m-d')));

$indicadores = sgqObterIndicadores($pdo, $dataInicio, $dataFim);

$retrabalho = $indicadores['indicador_retrabalho'];
$leadTime = $indicadores['indicador_lead_time'];
$satisfacao = $indicadores['indicador_satisfacao'];
$rncs = $indicadores['rncs'];

$titulo_page = 'Dashboard de Indicadores SGQ (ISO 9.1) - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<main class="app-main">
    <div class="container-fluid p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h3 font-weight-bold mb-1"><i class="fa-solid fa-chart-line text-primary mr-2"></i> Indicadores da Qualidade (ISO 9.1 & NORMAM)</h2>
                <p class="text-muted mb-0">Avaliação analítica de desempenho, medição de eficácia e satisfação para Reuniões de Análise Crítica da Direção.</p>
            </div>
            <div>
                <form method="GET" class="form-inline">
                    <label class="mr-2 small font-weight-bold">Período:</label>
                    <input type="date" name="data_inicio" class="form-control form-control-sm mr-2" value="<?= h($dataInicio) ?>">
                    <span class="mr-2">até</span>
                    <input type="date" name="data_fim" class="form-control form-control-sm mr-2" value="<?= h($dataFim) ?>">
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fa-solid fa-rotate mr-1"></i> Atualizar</button>
                </form>
            </div>
        </div>

        <!-- 3 INDICADORES ESTRATÉGICOS MANDATÓRIOS -->
        <div class="row mb-4">
            
            <!-- INDICADOR 1: TAXA DE RETRABALHO TÉCNICO -->
            <div class="col-lg-4 mb-4">
                <div class="card shadow-sm h-100 border-left-danger">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-danger">
                            <i class="fa-solid fa-triangle-exclamation mr-1"></i> Indicador 1: Taxa de Retrabalho Técnico
                        </h6>
                        <span class="badge badge-<?= $retrabalho['taxa_percentual'] > 10 ? 'danger' : ($retrabalho['taxa_percentual'] > 5 ? 'warning' : 'success') ?>">
                            Meta: &lt; 5.0%
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="text-center py-2">
                            <div class="display-4 font-weight-bold text-dark"><?= number_format($retrabalho['taxa_percentual'], 1, ',', '.') ?>%</div>
                            <small class="text-muted">Proporção de Bloqueios A/S sobre Total de Vistorias</small>
                        </div>
                        <hr>
                        <div class="small">
                            <div class="d-flex justify-content-between py-1">
                                <span>Total de Vistorias Realizadas:</span>
                                <strong><?= (int)$retrabalho['total_vistorias'] ?></strong>
                            </div>
                            <div class="d-flex justify-content-between py-1 text-danger">
                                <span>Bloqueios por Retorno A/S:</span>
                                <strong><?= (int)$retrabalho['total_retornos_as'] ?></strong>
                            </div>
                            <div class="d-flex justify-content-between py-1 text-warning">
                                <span>Aprovadas com Exigências:</span>
                                <strong><?= (int)$retrabalho['total_aprovadas_exigencias'] ?></strong>
                            </div>
                            <div class="d-flex justify-content-between py-1 text-success">
                                <span>Aprovações Diretas:</span>
                                <strong><?= (int)$retrabalho['total_aprovadas_diretas'] ?></strong>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light small text-muted">
                        Fórmula: (Vistorias Retorno A/S ÷ Total de Vistorias) × 100
                    </div>
                </div>
            </div>

            <!-- INDICADOR 2: LEAD TIME MÉDIO OPERACIONAL -->
            <div class="col-lg-4 mb-4">
                <div class="card shadow-sm h-100 border-left-info">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-info">
                            <i class="fa-solid fa-stopwatch mr-1"></i> Indicador 2: Lead Time Operacional
                        </h6>
                        <span class="badge badge-info">Eficiência</span>
                    </div>
                    <div class="card-body">
                        <div class="text-center py-2">
                            <div class="display-4 font-weight-bold text-dark"><?= number_format($leadTime['dias_medio'], 1, ',', '.') ?> <span class="h4 font-weight-normal text-muted">dias</span></div>
                            <small class="text-muted">Tempo Médio entre Agendamento e Emissão</small>
                        </div>
                        <hr>
                        <div class="small">
                            <div class="d-flex justify-content-between py-1">
                                <span>Certificados Concluídos no Período:</span>
                                <strong><?= (int)$leadTime['certificados_concluidos'] ?></strong>
                            </div>
                            <div class="d-flex justify-content-between py-1">
                                <span>Média em Horas Corridas:</span>
                                <strong><?= number_format($leadTime['dias_medio'] * 24, 0, ',', '.') ?> horas</strong>
                            </div>
                            <div class="d-flex justify-content-between py-1 text-success">
                                <span>Meta de Eficiência:</span>
                                <strong>Até 10 dias úteis</strong>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light small text-muted">
                        Fórmula: Σ(Data Emissão Certificado - Data Agendamento) ÷ Total Concluídas
                    </div>
                </div>
            </div>

            <!-- INDICADOR 3: ÍNDICE GERAL DE SATISFAÇÃO (PONDERADO) -->
            <div class="col-lg-4 mb-4">
                <div class="card shadow-sm h-100 border-left-success">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-success">
                            <i class="fa-solid fa-heart mr-1"></i> Indicador 3: Satisfação do Cliente
                        </h6>
                        <span class="badge badge-success">ISO 9.1.2</span>
                    </div>
                    <div class="card-body">
                        <div class="text-center py-2">
                            <div class="display-4 font-weight-bold text-dark"><?= number_format($satisfacao['indice_ponderado_percent'], 1, ',', '.') ?>%</div>
                            <small class="text-muted">Média Ponderada da Qualidade Percebida</small>
                        </div>
                        <hr>
                        <div class="small">
                            <div class="d-flex justify-content-between py-1">
                                <span>Total de Avaliações Coletadas:</span>
                                <strong><?= (int)$satisfacao['total_avaliacoes'] ?></strong>
                            </div>
                            <div class="d-flex justify-content-between py-1">
                                <span>Net Promoter Score (NPS):</span>
                                <strong class="<?= $satisfacao['nps_score'] >= 50 ? 'text-success' : 'text-warning' ?>"><?= (int)$satisfacao['nps_score'] ?> pts</strong>
                            </div>
                            <div class="d-flex justify-content-between py-1 text-muted">
                                <span>Promotores / Neutros / Detratores:</span>
                                <span><?= (int)$satisfacao['promotores'] ?> / <?= (int)$satisfacao['neutros'] ?> / <?= (int)$satisfacao['detratores'] ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light small text-muted">
                        Pesos: Técnica (40%), Prazo (35%), Atendimento (25%)
                    </div>
                </div>
            </div>

        </div>

        <!-- QUADRO DE GESTÃO DE RNCs E CONFORMIDADE -->
        <div class="row">
            <div class="col-lg-8 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-dark">
                            <i class="fa-solid fa-shield-halved text-primary mr-1"></i> Síntese de Não Conformidades (ISO 8.7 & 10.2)
                        </h6>
                        <a href="<?= APP_URL ?>sgq/nao-conformidades" class="btn btn-xs btn-outline-primary">
                            Ver todas as RNCs <i class="fa-solid fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="row text-center py-3">
                            <div class="col-3">
                                <div class="h3 font-weight-bold mb-1"><?= (int)$rncs['total'] ?></div>
                                <small class="text-muted">Total no Período</small>
                            </div>
                            <div class="col-3">
                                <div class="h3 font-weight-bold text-danger mb-1"><?= (int)$rncs['abertas'] ?></div>
                                <small class="text-muted">Abertas (Pendentes)</small>
                            </div>
                            <div class="col-3">
                                <div class="h3 font-weight-bold text-warning mb-1"><?= (int)$rncs['em_execucao'] ?></div>
                                <small class="text-muted">Em Execução 5W2H</small>
                            </div>
                            <div class="col-3">
                                <div class="h3 font-weight-bold text-success mb-1"><?= (int)$rncs['encerradas'] ?></div>
                                <small class="text-muted">Encerradas Eficazes</small>
                            </div>
                        </div>
                        <div class="progress mt-2" style="height: 12px;">
                            <?php 
                            $totRnc = max(1, (int)$rncs['total']);
                            $pctEnc = round(((int)$rncs['encerradas'] / $totRnc) * 100);
                            $pctExec = round(((int)$rncs['em_execucao'] / $totRnc) * 100);
                            $pctAber = round(((int)$rncs['abertas'] / $totRnc) * 100);
                            ?>
                            <div class="progress-bar bg-success" style="width: <?= $pctEnc ?>%" title="Eficazes: <?= $pctEnc ?>%"></div>
                            <div class="progress-bar bg-warning" style="width: <?= $pctExec ?>%" title="Em execução: <?= $pctExec ?>%"></div>
                            <div class="progress-bar bg-danger" style="width: <?= $pctAber ?>%" title="Abertas: <?= $pctAber ?>%"></div>
                        </div>
                        <div class="d-flex justify-content-between mt-2 small text-muted">
                            <span><i class="fa-solid fa-square text-success"></i> Resolvidas (<?= $pctEnc ?>%)</span>
                            <span><i class="fa-solid fa-square text-warning"></i> Tratamento (<?= $pctExec ?>%)</span>
                            <span><i class="fa-solid fa-square text-danger"></i> Abertas (<?= $pctAber ?>%)</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white py-3">
                        <h6 class="m-0 font-weight-bold text-dark">
                            <i class="fa-solid fa-stamp text-secondary mr-1"></i> Conformidade Normativa
                        </h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0 small">
                            <li class="mb-3 d-flex align-items-start">
                                <i class="fa-solid fa-circle-check text-success mt-1 mr-2"></i>
                                <div>
                                    <strong>ISO 7.2 - Competência Técnica</strong>
                                    <div class="text-muted">Vistoriadores com controle de credenciais da Marinha e conselho profissional (CREA/CFT).</div>
                                </div>
                            </li>
                            <li class="mb-3 d-flex align-items-start">
                                <i class="fa-solid fa-circle-check text-success mt-1 mr-2"></i>
                                <div>
                                    <strong>ISO 7.5 & 8.2 - Informação Documentada</strong>
                                    <div class="text-muted">Trilha de auditoria imutável antes/depois e validação técnica de cadastros.</div>
                                </div>
                            </li>
                            <li class="mb-3 d-flex align-items-start">
                                <i class="fa-solid fa-circle-check text-success mt-1 mr-2"></i>
                                <div>
                                    <strong>ISO 8.7 & 10.2 - Saídas Não Conformes</strong>
                                    <div class="text-muted">Trava A/S automatizada com planos corretivos no formato 5W2H.</div>
                                </div>
                            </li>
                            <li class="d-flex align-items-start">
                                <i class="fa-solid fa-circle-check text-success mt-1 mr-2"></i>
                                <div>
                                    <strong>ISO 9.1.2 - Satisfação do Cliente</strong>
                                    <div class="text-muted">Coleta ativa de percepção de atendimento, técnica, prazo e NPS no Portal do Cliente.</div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
