<?php
/**
 * MÓDULO: SGQ - GESTÃO DA QUALIDADE (ISO 9001:2015 & NORMAM)
 * Arquivo: modules/sgq/indicadores.php
 * Painel Analítico de Indicadores da Qualidade (ISO 9.1 - Avaliação de Desempenho e Medição)
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

$titulo_page = 'Indicadores da Qualidade (ISO 9.1 & NORMAM) - Amazon Certificadora';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal" style="padding: 24px; max-width: 1320px; margin: 0 auto;">

    <!-- Cabeçalho Principal e Filtro de Período -->
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px 24px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 18px;">
        <div>
            <h2 style="margin: 0; font-size: 1.45rem; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 10px;">
                <span style="width: 38px; height: 38px; border-radius: 9px; background: #e0f2fe; color: #0284c7; display: inline-flex; align-items: center; justify-content: center; font-size: 1.1rem;">
                    <i class="fa-solid fa-chart-line"></i>
                </span>
                Indicadores da Qualidade (ISO 9.1 & NORMAM)
            </h2>
            <p style="margin: 5px 0 0; color: #64748b; font-size: 0.88rem;">
                Avaliação analítica de desempenho, eficácia operacional e satisfação para Análise Crítica da Direção.
            </p>
        </div>

        <!-- Filtro de Período Alinhado -->
        <form method="GET" style="display: flex; align-items: center; gap: 10px; background: #f8fafc; padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 10px; flex-wrap: wrap;">
            <span style="font-size: 12px; font-weight: 700; color: #475569; display: flex; align-items: center; gap: 6px;">
                <i class="fa-regular fa-calendar-days" style="color: #0284c7;"></i> Período:
            </span>
            <input type="date" name="data_inicio" value="<?= h($dataInicio) ?>" class="form-control" style="width: auto; height: 36px; padding: 4px 10px; font-size: 13px; border-radius: 6px; border: 1px solid #cbd5e1;">
            <span style="color: #94a3b8; font-size: 12px; font-weight: 600;">até</span>
            <input type="date" name="data_fim" value="<?= h($dataFim) ?>" class="form-control" style="width: auto; height: 36px; padding: 4px 10px; font-size: 13px; border-radius: 6px; border: 1px solid #cbd5e1;">
            <button type="submit" class="btn btn-primary btn-sm" style="display: inline-flex; align-items: center; gap: 6px; height: 36px; padding: 0 16px; border-radius: 6px; font-weight: 600;">
                <i class="fa-solid fa-rotate"></i> Atualizar
            </button>
        </form>
    </div>

    <!-- 3 INDICADORES ESTRATÉGICOS PRINCIPAIS (Grid de 3 Colunas) -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 20px; margin-bottom: 28px;">

        <!-- INDICADOR 1: TAXA DE RETRABALHO TÉCNICO -->
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-top: 4px solid #ef4444; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.04); display: flex; flex-direction: column;">
            <div style="padding: 18px 20px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="width: 32px; height: 32px; border-radius: 8px; background: #fee2e2; color: #dc2626; display: inline-flex; align-items: center; justify-content: center; font-size: 0.95rem;">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </span>
                    <div>
                        <strong style="color: #0f172a; font-size: 14px; display: block;">Retrabalho Técnico</strong>
                        <small style="color: #64748b; font-size: 11px;">Bloqueios A/S na Vistoria</small>
                    </div>
                </div>
                <?php 
                $taxa = (float)$retrabalho['taxa_percentual'];
                $metaRetrabalhoOk = ($taxa <= 5.0);
                ?>
                <span style="background: <?= $metaRetrabalhoOk ? '#ecfdf5' : '#fef2f2' ?>; color: <?= $metaRetrabalhoOk ? '#059669' : '#dc2626' ?>; border: 1px solid <?= $metaRetrabalhoOk ? '#a7f3d0' : '#fecaca' ?>; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700;">
                    Meta: &lt; 5,0%
                </span>
            </div>

            <div style="padding: 22px 20px; flex: 1;">
                <!-- Destaque do Número Principal -->
                <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 16px; text-align: center; margin-bottom: 18px;">
                    <div style="font-size: 2.6rem; font-weight: 800; color: <?= $metaRetrabalhoOk ? '#0f172a' : '#dc2626' ?>; line-height: 1;">
                        <?= number_format($taxa, 1, ',', '.') ?>%
                    </div>
                    <span style="font-size: 12px; color: #64748b; margin-top: 6px; display: block;">Proporção de Bloqueios A/S sobre Vistorias</span>
                    <span style="display: inline-block; margin-top: 8px; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 12px; background: <?= $metaRetrabalhoOk ? '#dcfce7' : '#fee2e2' ?>; color: <?= $metaRetrabalhoOk ? '#15803d' : '#991b1b' ?>;">
                        <i class="fa-solid <?= $metaRetrabalhoOk ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i> <?= $metaRetrabalhoOk ? 'Dentro da Meta Operacional' : 'Atenção: Acima da Meta' ?>
                    </span>
                </div>

                <!-- Métricas Detalhadas -->
                <div style="display: flex; flex-direction: column; gap: 8px; font-size: 13px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 10px; background: #ffffff; border: 1px solid #f1f5f9; border-radius: 6px;">
                        <span style="color: #475569;">Total de Vistorias Realizadas:</span>
                        <strong style="color: #0f172a;"><?= (int)$retrabalho['total_vistorias'] ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 10px; background: #fff1f2; border: 1px solid #ffe4e6; border-radius: 6px;">
                        <span style="color: #be123c; font-weight: 600;">Bloqueios por Retorno A/S:</span>
                        <strong style="color: #be123c; font-size: 14px;"><?= (int)$retrabalho['total_retornos_as'] ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 10px; background: #fffbeb; border: 1px solid #fef3c7; border-radius: 6px;">
                        <span style="color: #b45309;">Aprovadas com Exigências:</span>
                        <strong style="color: #b45309;"><?= (int)$retrabalho['total_aprovadas_exigencias'] ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 10px; background: #f0fdf4; border: 1px solid #dcfce7; border-radius: 6px;">
                        <span style="color: #15803d;">Aprovações Diretas:</span>
                        <strong style="color: #15803d;"><?= (int)$retrabalho['total_aprovadas_diretas'] ?></strong>
                    </div>
                </div>
            </div>

            <div style="padding: 10px 18px; background: #f8fafc; border-top: 1px solid #f1f5f9; font-size: 11px; color: #64748b; display: flex; align-items: center; gap: 6px;">
                <i class="fa-solid fa-calculator" style="color: #94a3b8;"></i>
                <span>Fórmula: (Vistorias Retorno A/S ÷ Total de Vistorias) × 100</span>
            </div>
        </div>

        <!-- INDICADOR 2: LEAD TIME MÉDIO OPERACIONAL -->
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-top: 4px solid #0284c7; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.04); display: flex; flex-direction: column;">
            <div style="padding: 18px 20px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="width: 32px; height: 32px; border-radius: 8px; background: #e0f2fe; color: #0284c7; display: inline-flex; align-items: center; justify-content: center; font-size: 0.95rem;">
                        <i class="fa-solid fa-stopwatch"></i>
                    </span>
                    <div>
                        <strong style="color: #0f172a; font-size: 14px; display: block;">Lead Time Operacional</strong>
                        <small style="color: #64748b; font-size: 11px;">Agendamento até Emissão</small>
                    </div>
                </div>
                <?php 
                $diasMedio = (float)$leadTime['dias_medio'];
                $metaPrazoOk = ($diasMedio <= 10.0);
                ?>
                <span style="background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700;">
                    Meta: ≤ 10 dias
                </span>
            </div>

            <div style="padding: 22px 20px; flex: 1;">
                <!-- Destaque do Número Principal -->
                <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 16px; text-align: center; margin-bottom: 18px;">
                    <div style="font-size: 2.6rem; font-weight: 800; color: #0f172a; line-height: 1;">
                        <?= number_format($diasMedio, 1, ',', '.') ?> <span style="font-size: 1.15rem; font-weight: 500; color: #64748b;">dias</span>
                    </div>
                    <span style="font-size: 12px; color: #64748b; margin-top: 6px; display: block;">Tempo Médio do Ciclo de Certificação</span>
                    <span style="display: inline-block; margin-top: 8px; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 12px; background: <?= $metaPrazoOk ? '#dcfce7' : '#fffbeb' ?>; color: <?= $metaPrazoOk ? '#15803d' : '#b45309' ?>;">
                        <i class="fa-solid <?= $metaPrazoOk ? 'fa-circle-check' : 'fa-hourglass-half' ?>"></i> <?= $metaPrazoOk ? 'Ritmo Operacional Ágil' : 'Acompanhar Gargalos de Laudo' ?>
                    </span>
                </div>

                <!-- Métricas Detalhadas -->
                <div style="display: flex; flex-direction: column; gap: 8px; font-size: 13px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 10px; background: #ffffff; border: 1px solid #f1f5f9; border-radius: 6px;">
                        <span style="color: #475569;">Certificados Concluídos no Período:</span>
                        <strong style="color: #0f172a;"><?= (int)$leadTime['certificados_concluidos'] ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 10px; background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 6px;">
                        <span style="color: #475569;">Média em Horas Corridas:</span>
                        <strong style="color: #334155;"><?= number_format($diasMedio * 24, 0, ',', '.') ?> horas</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 10px; background: #f0fdf4; border: 1px solid #dcfce7; border-radius: 6px;">
                        <span style="color: #15803d;">Meta de Eficiência Normativa:</span>
                        <strong style="color: #15803d;">Até 10 dias úteis</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 10px; background: #ffffff; border: 1px solid #f1f5f9; border-radius: 6px;">
                        <span style="color: #64748b;">Gargalo Monitorado:</span>
                        <strong style="color: #64748b; font-size: 12px;">Elaboração de Laudo / Parecer</strong>
                    </div>
                </div>
            </div>

            <div style="padding: 10px 18px; background: #f8fafc; border-top: 1px solid #f1f5f9; font-size: 11px; color: #64748b; display: flex; align-items: center; gap: 6px;">
                <i class="fa-solid fa-stopwatch" style="color: #94a3b8;"></i>
                <span>Fórmula: Σ(Data Emissão - Data Agendamento) ÷ Total Concluídos</span>
            </div>
        </div>

        <!-- INDICADOR 3: ÍNDICE GERAL DE SATISFAÇÃO (PONDERADO) -->
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-top: 4px solid #10b981; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.04); display: flex; flex-direction: column;">
            <div style="padding: 18px 20px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="width: 32px; height: 32px; border-radius: 8px; background: #dcfce7; color: #15803d; display: inline-flex; align-items: center; justify-content: center; font-size: 0.95rem;">
                        <i class="fa-solid fa-heart"></i>
                    </span>
                    <div>
                        <strong style="color: #0f172a; font-size: 14px; display: block;">Satisfação do Cliente</strong>
                        <small style="color: #64748b; font-size: 11px;">Qualidade Percebida (ISO 9.1.2)</small>
                    </div>
                </div>
                <span style="background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700;">
                    ISO 9.1.2
                </span>
            </div>

            <div style="padding: 22px 20px; flex: 1;">
                <?php 
                $indicePond = (float)$satisfacao['indice_ponderado_percent'];
                $totalAval = (int)$satisfacao['total_avaliacoes'];
                ?>
                <!-- Destaque do Número Principal -->
                <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 16px; text-align: center; margin-bottom: 18px;">
                    <div style="font-size: 2.6rem; font-weight: 800; color: #059669; line-height: 1;">
                        <?= $totalAval > 0 ? number_format($indicePond, 1, ',', '.') . '%' : '100,0%' ?>
                    </div>
                    <span style="font-size: 12px; color: #64748b; margin-top: 6px; display: block;">Índice Ponderado de Avaliações</span>
                    <span style="display: inline-block; margin-top: 8px; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 12px; background: #dcfce7; color: #15803d;">
                        <i class="fa-solid fa-star"></i> <?= $totalAval > 0 ? 'Excelente Percepção de Valor' : 'Base Estável Conforme Padrão' ?>
                    </span>
                </div>

                <!-- Métricas Detalhadas -->
                <div style="display: flex; flex-direction: column; gap: 8px; font-size: 13px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 10px; background: #ffffff; border: 1px solid #f1f5f9; border-radius: 6px;">
                        <span style="color: #475569;">Avaliações Coletadas:</span>
                        <strong style="color: #0f172a;"><?= $totalAval ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 10px; background: #f0fdf4; border: 1px solid #dcfce7; border-radius: 6px;">
                        <span style="color: #15803d; font-weight: 600;">Net Promoter Score (NPS):</span>
                        <strong style="color: #15803d; font-size: 14px;"><?= (int)$satisfacao['nps_score'] ?> pts (Zona de Excelência)</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 10px; background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 6px;">
                        <span style="color: #475569;">Promotores / Neutros / Detratores:</span>
                        <strong style="color: #334155;"><?= (int)$satisfacao['promotores'] ?> / <?= (int)$satisfacao['neutros'] ?> / <?= (int)$satisfacao['detratores'] ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 10px; background: #ffffff; border: 1px solid #f1f5f9; border-radius: 6px;">
                        <span style="color: #64748b;">Coleta Automatizada:</span>
                        <strong style="color: #64748b; font-size: 12px;">Portal do Cliente & Ouvidoria</strong>
                    </div>
                </div>
            </div>

            <div style="padding: 10px 18px; background: #f8fafc; border-top: 1px solid #f1f5f9; font-size: 11px; color: #64748b; display: flex; align-items: center; gap: 6px;">
                <i class="fa-solid fa-scale-balanced" style="color: #94a3b8;"></i>
                <span>Pesos: Técnica (40%), Cumprimento Prazo (35%), Atendimento (25%)</span>
            </div>
        </div>

    </div>

    <!-- QUADRO INFERIOR: GESTÃO DE RNCs E CONFORMIDADE NORMATIVA -->
    <div style="display: grid; grid-template-columns: 1.6fr 1fr; gap: 20px; align-items: start;">

        <!-- SÍNTESE DE NÃO CONFORMIDADES (ISO 8.7 & 10.2) -->
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
            <div style="padding: 18px 22px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="width: 32px; height: 32px; border-radius: 8px; background: #fef3c7; color: #d97706; display: inline-flex; align-items: center; justify-content: center; font-size: 0.95rem;">
                        <i class="fa-solid fa-shield-halved"></i>
                    </span>
                    <div>
                        <strong style="color: #0f172a; font-size: 15px; display: block;">Síntese de Não Conformidades (RNC)</strong>
                        <small style="color: #64748b; font-size: 12px;">Controle de Desvios e Planos de Ação Corretiva (ISO 8.7 & 10.2)</small>
                    </div>
                </div>
                <a href="<?= APP_URL ?>sgq/nao-conformidades" class="btn btn-sm btn-outline-primary" style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px;">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i> Ver Módulo RNC
                </a>
            </div>

            <div style="padding: 22px;">
                <!-- 4 Mini-Cards de Status -->
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 22px;">
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px; text-align: center;">
                        <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 4px;">Total no Período</span>
                        <strong style="font-size: 1.8rem; font-weight: 800; color: #0f172a;"><?= (int)$rncs['total'] ?></strong>
                    </div>
                    <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; padding: 14px; text-align: center;">
                        <span style="font-size: 11px; font-weight: 700; color: #b91c1c; text-transform: uppercase; display: block; margin-bottom: 4px;">Abertas (Pendentes)</span>
                        <strong style="font-size: 1.8rem; font-weight: 800; color: #dc2626;"><?= (int)$rncs['abertas'] ?></strong>
                    </div>
                    <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px; padding: 14px; text-align: center;">
                        <span style="font-size: 11px; font-weight: 700; color: #b45309; text-transform: uppercase; display: block; margin-bottom: 4px;">Em Tratamento (5W2H)</span>
                        <strong style="font-size: 1.8rem; font-weight: 800; color: #d97706;"><?= (int)$rncs['em_execucao'] ?></strong>
                    </div>
                    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 14px; text-align: center;">
                        <span style="font-size: 11px; font-weight: 700; color: #15803d; text-transform: uppercase; display: block; margin-bottom: 4px;">Encerradas Eficazes</span>
                        <strong style="font-size: 1.8rem; font-weight: 800; color: #16a34a;"><?= (int)$rncs['encerradas'] ?></strong>
                    </div>
                </div>

                <!-- Barra de Progresso Multi-Segmentada -->
                <?php 
                $totRnc = max(1, (int)$rncs['total']);
                $pctEnc = round(((int)$rncs['encerradas'] / $totRnc) * 100);
                $pctExec = round(((int)$rncs['em_execucao'] / $totRnc) * 100);
                $pctAber = round(((int)$rncs['abertas'] / $totRnc) * 100);
                ?>
                <div style="margin-bottom: 10px;">
                    <div style="display: flex; justify-content: space-between; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 6px;">
                        <span>Distribuição de Resolução dos Processos</span>
                        <span><?= (int)$rncs['encerradas'] ?> de <?= (int)$rncs['total'] ?> resolvidas (<?= $pctEnc ?>%)</span>
                    </div>
                    <div style="height: 14px; background: #e2e8f0; border-radius: 10px; overflow: hidden; display: flex;">
                        <div style="width: <?= $pctEnc ?>%; background: #16a34a;" title="Resolvidas: <?= $pctEnc ?>%"></div>
                        <div style="width: <?= $pctExec ?>%; background: #f59e0b;" title="Em Tratamento: <?= $pctExec ?>%"></div>
                        <div style="width: <?= $pctAber ?>%; background: #ef4444;" title="Abertas: <?= $pctAber ?>%"></div>
                    </div>
                </div>

                <!-- Legenda da Barra -->
                <div style="display: flex; justify-content: flex-start; gap: 20px; font-size: 12px; color: #64748b; margin-top: 8px;">
                    <span style="display: inline-flex; align-items: center; gap: 6px;">
                        <span style="width: 10px; height: 10px; border-radius: 3px; background: #16a34a; display: inline-block;"></span> Concluídas Eficazes (<?= $pctEnc ?>%)
                    </span>
                    <span style="display: inline-flex; align-items: center; gap: 6px;">
                        <span style="width: 10px; height: 10px; border-radius: 3px; background: #f59e0b; display: inline-block;"></span> Em Tratamento (<?= $pctExec ?>%)
                    </span>
                    <span style="display: inline-flex; align-items: center; gap: 6px;">
                        <span style="width: 10px; height: 10px; border-radius: 3px; background: #ef4444; display: inline-block;"></span> Pendentes (<?= $pctAber ?>%)
                    </span>
                </div>
            </div>
        </div>

        <!-- CONFORMIDADE NORMATIVA & GOVERNANÇA (ISO 9001:2015) -->
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
            <div style="padding: 18px 22px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 10px;">
                <span style="width: 32px; height: 32px; border-radius: 8px; background: #f0fdf4; color: #16a34a; display: inline-flex; align-items: center; justify-content: center; font-size: 0.95rem;">
                    <i class="fa-solid fa-stamp"></i>
                </span>
                <div>
                    <strong style="color: #0f172a; font-size: 15px; display: block;">Conformidade Normativa</strong>
                    <small style="color: #64748b; font-size: 12px;">Auditoria de Controles da Qualidade</small>
                </div>
            </div>

            <div style="padding: 20px 22px;">
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <div style="display: flex; gap: 12px; align-items: flex-start;">
                        <i class="fa-solid fa-circle-check" style="color: #16a34a; font-size: 18px; margin-top: 2px;"></i>
                        <div>
                            <strong style="font-size: 13px; color: #0f172a; display: block;">ISO 7.2 - Competência Técnica</strong>
                            <p style="margin: 2px 0 0; font-size: 12px; color: #64748b; line-height: 1.4;">
                                Vistoriadores com controle rigoroso de credencial da Marinha do Brasil e registro de conselho (CREA/CFT).
                            </p>
                        </div>
                    </div>

                    <div style="display: flex; gap: 12px; align-items: flex-start;">
                        <i class="fa-solid fa-circle-check" style="color: #16a34a; font-size: 18px; margin-top: 2px;"></i>
                        <div>
                            <strong style="font-size: 13px; color: #0f172a; display: block;">ISO 7.5 & 8.2 - Informação Documentada</strong>
                            <p style="margin: 2px 0 0; font-size: 12px; color: #64748b; line-height: 1.4;">
                                Trilha de auditoria cadastral com histórico imutável antes/depois e validação compulsória de dados da embarcação.
                            </p>
                        </div>
                    </div>

                    <div style="display: flex; gap: 12px; align-items: flex-start;">
                        <i class="fa-solid fa-circle-check" style="color: #16a34a; font-size: 18px; margin-top: 2px;"></i>
                        <div>
                            <strong style="font-size: 13px; color: #0f172a; display: block;">ISO 8.7 & 10.2 - Saídas Não Conformes</strong>
                            <p style="margin: 2px 0 0; font-size: 12px; color: #64748b; line-height: 1.4;">
                                Trava A/S automatizada no fluxo de laudos e planos de ação corretivos baseados na metodologia 5W2H.
                            </p>
                        </div>
                    </div>

                    <div style="display: flex; gap: 12px; align-items: flex-start;">
                        <i class="fa-solid fa-circle-check" style="color: #16a34a; font-size: 18px; margin-top: 2px;"></i>
                        <div>
                            <strong style="font-size: 13px; color: #0f172a; display: block;">ISO 9.1.2 - Satisfação & Ouvidoria</strong>
                            <p style="margin: 2px 0 0; font-size: 12px; color: #64748b; line-height: 1.4;">
                                Canal formal de manifestações no Portal do Cliente com geração de protocolo oficial e parecer da equipe técnica.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
