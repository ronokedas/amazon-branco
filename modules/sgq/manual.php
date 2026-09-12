<?php
/**
 * MÓDULO: SGQ - GESTÃO DA QUALIDADE (ISO 9001:2015 & NORMAM)
 * Arquivo: modules/sgq/manual.php
 * Manual da Qualidade, Política do SGQ e Matriz de Evidências para Auditoria
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
exigirAcesso('dashboard');

$titulo_page = 'Manual da Qualidade & Política SGQ (ISO 9001:2015) - Amazon Certificadora';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal" style="padding: 24px; max-width: 1320px; margin: 0 auto;">

    <!-- Barra Superior de Ações do Manual -->
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px 24px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div>
            <h2 style="margin: 0; font-size: 1.45rem; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 10px;">
                <span style="width: 38px; height: 38px; border-radius: 9px; background: #e0f2fe; color: #0284c7; display: inline-flex; align-items: center; justify-content: center; font-size: 1.1rem;">
                    <i class="fa-solid fa-book-bookmark"></i>
                </span>
                Manual da Qualidade & Governança SGQ
            </h2>
            <p style="margin: 5px 0 0; color: #64748b; font-size: 0.88rem;">
                Documento de Referência Oficial em conformidade com a <strong>ABNT NBR ISO 9001:2015</strong> e Normas da Autoridade Marítima (<strong>NORMAM-201/202 - DPC</strong>).
            </p>
        </div>

        <div style="display: flex; gap: 10px; align-items: center;">
            <button type="button" class="btn btn-outline-secondary" onclick="window.print()" style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; font-weight: 600;">
                <i class="fa-solid fa-print"></i> Imprimir / Exportar PDF
            </button>
            <span style="background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                <i class="fa-solid fa-stamp"></i> Versão Vigente 2026.1
            </span>
        </div>
    </div>

    <!-- SEÇÃO 1: POLÍTICA DA QUALIDADE E ESCOPO -->
    <div style="display: grid; grid-template-columns: 1.3fr 1fr; gap: 20px; margin-bottom: 24px;">
        
        <!-- POLÍTICA DA QUALIDADE (ISO 5.2) -->
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-left: 5px solid #08a774; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 14px;">
                <span style="width: 32px; height: 32px; border-radius: 8px; background: #dcfce7; color: #15803d; display: inline-flex; align-items: center; justify-content: center; font-size: 1rem;">
                    <i class="fa-solid fa-handshake"></i>
                </span>
                <div>
                    <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: #0f172a;">Política da Qualidade (ISO 5.2)</h3>
                    <small style="color: #64748b; font-size: 12px;">Compromisso da Alta Direção da Amazon Certificadora</small>
                </div>
            </div>

            <blockquote style="margin: 0 0 16px; padding: 14px 18px; background: #f8fafc; border-left: 4px solid #0284c7; border-radius: 6px; font-style: italic; color: #1e293b; line-height: 1.6; font-size: 13.5px;">
                "A <strong>Amazon Certificadora</strong> compromete-se a prestar serviços técnicos de engenharia naval, análise estrutural de planos e vistorias de conformidade com excelência, ética, independência e pleno atendimento aos requisitos regulatórios da <strong>Autoridade Marítima Brasileira (DPC/NORMAM)</strong> e da norma <strong>ISO 9001:2015</strong>, promovendo a segurança da vida humana no mar e vias navegáveis interiores, a prevenção da poluição hídrica e a melhoria contínua da satisfação dos nossos clientes."
            </blockquote>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 12.5px; color: #475569;">
                <div style="display: flex; gap: 8px; align-items: flex-start;">
                    <i class="fa-solid fa-check" style="color: #08a774; margin-top: 3px;"></i>
                    <span>Rigor técnico e imparcialidade em todas as vistorias estatutárias.</span>
                </div>
                <div style="display: flex; gap: 8px; align-items: flex-start;">
                    <i class="fa-solid fa-check" style="color: #08a774; margin-top: 3px;"></i>
                    <span>Corpo técnico habilitado perante o CREA/CFT e Marinha do Brasil.</span>
                </div>
                <div style="display: flex; gap: 8px; align-items: flex-start;">
                    <i class="fa-solid fa-check" style="color: #08a774; margin-top: 3px;"></i>
                    <span>Tolerância zero a saídas não conformes impeditivas (Trava A/S).</span>
                </div>
                <div style="display: flex; gap: 8px; align-items: flex-start;">
                    <i class="fa-solid fa-check" style="color: #08a774; margin-top: 3px;"></i>
                    <span>Transparência ativa e canal formal de ouvidoria ao armador/cliente.</span>
                </div>
            </div>
        </div>

        <!-- ESCOPO DO SISTEMA DE GESTÃO (ISO 4.3) -->
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-left: 5px solid #0284c7; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 14px;">
                <span style="width: 32px; height: 32px; border-radius: 8px; background: #e0f2fe; color: #0284c7; display: inline-flex; align-items: center; justify-content: center; font-size: 1rem;">
                    <i class="fa-solid fa-compass"></i>
                </span>
                <div>
                    <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: #0f172a;">Escopo do SGQ (ISO 4.3)</h3>
                    <small style="color: #64748b; font-size: 12px;">Limites e Aplicação dos Serviços Certificados</small>
                </div>
            </div>

            <p style="margin: 0 0 14px; font-size: 13px; color: #334155; line-height: 1.5;">
                O Sistema de Gestão da Qualidade da Amazon Certificadora abrange integralmente os seguintes serviços técnicos:
            </p>

            <ul style="margin: 0; padding-left: 18px; font-size: 13px; color: #475569; line-height: 1.6;">
                <li><strong>Análise de Planos e Documentos Técnicos:</strong> Estabilidade intacta, avaria, arranjo geral, salvatagem, combate a incêndio e arqueação (NORMAM-201/202).</li>
                <li><strong>Vistorias de Campo:</strong> Vistorias inicial, intermediária, de renovação e perícias navais com suporte a PWA offline.</li>
                <li><strong>Emissão de Certificados Navais:</strong> CSN, CNBL, CNARQ, LP, LC e CHT.</li>
                <li><strong>Gestão Documental e Protocolos:</strong> Rastreabilidade digital com cadeia de custódia e assinatura eletrônica qualificada.</li>
            </ul>
        </div>

    </div>

    <!-- SEÇÃO 2: MACROFLUXO DE PROCESSOS OPERACIONAIS (MAPA DE PROCESSOS) -->
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
        <div style="margin-bottom: 20px;">
            <h3 style="margin: 0; font-size: 1.2rem; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-diagram-project" style="color: #0284c7;"></i> Macroprocesso Operacional da Qualidade (ISO 4.4)
            </h3>
            <p style="margin: 4px 0 0; font-size: 13px; color: #64748b;">
                Fluxo integrado ponta a ponta com travas de integridade técnica nativas do software ERP.
            </p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px;">
            <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; padding: 16px; text-align: center;">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: #0284c7; color: #fff; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; margin-bottom: 8px;">1</div>
                <strong style="font-size: 13px; display: block; color: #0f172a;">Comercial & Prontidão</strong>
                <small style="color: #64748b; font-size: 11px; display: block; margin-top: 4px;">Validação de requisitos da embarcação (ISO 8.2)</small>
            </div>

            <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; padding: 16px; text-align: center;">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: #0284c7; color: #fff; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; margin-bottom: 8px;">2</div>
                <strong style="font-size: 13px; display: block; color: #0f172a;">Análise de Planos</strong>
                <small style="color: #64748b; font-size: 11px; display: block; margin-top: 4px;">Cálculos e pareceres técnicos NORMAM (ISO 8.3/8.5)</small>
            </div>

            <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; padding: 16px; text-align: center;">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: #0284c7; color: #fff; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; margin-bottom: 8px;">3</div>
                <strong style="font-size: 13px; display: block; color: #0f172a;">Agendamento & Escala</strong>
                <small style="color: #64748b; font-size: 11px; display: block; margin-top: 4px;">Trava de competência técnica e conselho (ISO 7.2)</small>
            </div>

            <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; padding: 16px; text-align: center;">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: #0284c7; color: #fff; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; margin-bottom: 8px;">4</div>
                <strong style="font-size: 13px; display: block; color: #0f172a;">Vistoria de Campo</strong>
                <small style="color: #64748b; font-size: 11px; display: block; margin-top: 4px;">Inspeção in loco, fotos e apontamentos (ISO 8.5)</small>
            </div>

            <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; padding: 16px; text-align: center;">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: #0284c7; color: #fff; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; margin-bottom: 8px;">5</div>
                <strong style="font-size: 13px; display: block; color: #0f172a;">Auditoria Técnica (RT)</strong>
                <small style="color: #64748b; font-size: 11px; display: block; margin-top: 4px;">Trava A/S automática & Emissão (ISO 8.7)</small>
            </div>

            <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; padding: 16px; text-align: center;">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: #16a34a; color: #fff; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; margin-bottom: 8px;">6</div>
                <strong style="font-size: 13px; display: block; color: #0f172a;">Ouvidoria & Indicadores</strong>
                <small style="color: #64748b; font-size: 11px; display: block; margin-top: 4px;">Satisfação, RNC 5W2H e Melhoria (ISO 9.1/10.2)</small>
            </div>
        </div>
    </div>

    <!-- SEÇÃO 3: MATRIZ DE EVIDÊNCIAS AUDITÁVEIS CLÁUSULA POR CLÁUSULA DA ISO 9001:2015 -->
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.04); margin-bottom: 24px;">
        <div style="padding: 18px 24px; border-bottom: 1px solid #f1f5f9; background: #f8fafc; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 style="margin: 0; font-size: 1.2rem; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-list-check" style="color: #0284c7;"></i> Matriz de Evidências Digitais para o Auditor (Inmetro / Certificadora)
                </h3>
                <small style="color: #64748b; font-size: 12px;">Mapeamento rigoroso das cláusulas da ISO 9001:2015 implementadas no código-fonte e banco de dados.</small>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table mb-0" style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead style="background: #f1f5f9; border-bottom: 2px solid #e2e8f0;">
                    <tr>
                        <th style="padding: 12px 16px; font-weight: 700; color: #334155; text-align: left; width: 150px;">Cláusula ISO</th>
                        <th style="padding: 12px 16px; font-weight: 700; color: #334155; text-align: left; width: 230px;">Requisito Normativo</th>
                        <th style="padding: 12px 16px; font-weight: 700; color: #334155; text-align: left;">Como o Sistema ERP Implementa na Prática</th>
                        <th style="padding: 12px 16px; font-weight: 700; color: #334155; text-align: left; width: 220px;">Evidência / Módulo no ERP</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="border-top: 1px solid #f1f5f9;">
                        <td style="padding: 14px 16px; font-weight: 700; color: #0284c7;">6.1</td>
                        <td style="padding: 14px 16px; font-weight: 600; color: #0f172a;">Ações para Abordar Riscos e Oportunidades</td>
                        <td style="padding: 14px 16px; color: #334155; line-height: 1.5;">
                            Mapeamento matricial 5x5 de probabilidade e impacto com classificação automática (BAIXO, MEDIO, ALTO, CRITICO) e planos de mitigação obrigatórios com responsável e prazo.
                        </td>
                        <td style="padding: 14px 16px;">
                            <a href="<?= APP_URL ?>sgq/riscos" class="btn btn-xs btn-outline-primary" style="display: inline-flex; align-items: center; gap: 4px;">
                                <i class="fa-solid fa-shield-virus"></i> Matriz de Riscos (ISO 6.1)
                            </a>
                            <small style="display: block; color: #64748b; font-family: monospace; margin-top: 4px;">Tabela: sgq_matriz_riscos</small>
                        </td>
                    </tr>

                    <tr style="border-top: 1px solid #f1f5f9; background: #fafafa;">
                        <td style="padding: 14px 16px; font-weight: 700; color: #0284c7;">7.2</td>
                        <td style="padding: 14px 16px; font-weight: 600; color: #0f172a;">Competência Técnica e Habilitação</td>
                        <td style="padding: 14px 16px; color: #334155; line-height: 1.5;">
                            Controle compulsório de validade das portarias da Capitania/DPC e do registro profissional (CREA/CFT). Trava de agendamento que rejeita vistoriador com credencial vencida com bloqueio HTTP 400.
                        </td>
                        <td style="padding: 14px 16px;">
                            <span style="background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; padding: 2px 8px; border-radius: 4px; font-weight: 600; font-size: 11px;">
                                Trava Ativa no Agendamento
                            </span>
                            <small style="display: block; color: #64748b; font-family: monospace; margin-top: 4px;">Função: vistoriadorElegivelParaAgendamento</small>
                        </td>
                    </tr>

                    <tr style="border-top: 1px solid #f1f5f9;">
                        <td style="padding: 14px 16px; font-weight: 700; color: #0284c7;">7.5</td>
                        <td style="padding: 14px 16px; font-weight: 600; color: #0f172a;">Informação Documentada & Rastreabilidade</td>
                        <td style="padding: 14px 16px; color: #334155; line-height: 1.5;">
                            Trilha de auditoria cadastral que armazena o estado anterior (JSON), estado posterior (JSON), delta de campos modificados, responsável, data/hora, IP e motivo da alteração.
                        </td>
                        <td style="padding: 14px 16px;">
                            <a href="<?= APP_URL ?>sgq/auditoria" class="btn btn-xs btn-outline-primary" style="display: inline-flex; align-items: center; gap: 4px;">
                                <i class="fa-solid fa-clock-rotate-left"></i> Trilha de Auditoria (ISO 7.5)
                            </a>
                            <small style="display: block; color: #64748b; font-family: monospace; margin-top: 4px;">Tabela: sgq_auditoria_cadastral</small>
                        </td>
                    </tr>

                    <tr style="border-top: 1px solid #f1f5f9; background: #fafafa;">
                        <td style="padding: 14px 16px; font-weight: 700; color: #0284c7;">8.2</td>
                        <td style="padding: 14px 16px; font-weight: 600; color: #0f172a;">Requisitos Relativos a Produtos e Serviços</td>
                        <td style="padding: 14px 16px; color: #334155; line-height: 1.5;">
                            Validação técnica de prontidão cadastral da embarcação. O ERP proíbe gerar propostas comerciais para embarcações sem dimensões (comp, boca, pontal), arqueação bruta (AB) ou dados de motor.
                        </td>
                        <td style="padding: 14px 16px;">
                            <span style="background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; padding: 2px 8px; border-radius: 4px; font-weight: 600; font-size: 11px;">
                                Validador de Prontidão
                            </span>
                            <small style="display: block; color: #64748b; font-family: monospace; margin-top: 4px;">Função: embarcacaoValidarProntidaoComercial</small>
                        </td>
                    </tr>

                    <tr style="border-top: 1px solid #f1f5f9;">
                        <td style="padding: 14px 16px; font-weight: 700; color: #0284c7;">8.7 & 10.2</td>
                        <td style="padding: 14px 16px; font-weight: 600; color: #0f172a;">Controle de Saídas Não Conformes & Ações Corretivas</td>
                        <td style="padding: 14px 16px; color: #334155; line-height: 1.5;">
                            Mecanismo de Retorno A/S que bloqueia a certificação de embarcações com exigências graves. O sistema dispara automaticamente um RNC oficial com plano de ação estruturado em 5W2H.
                        </td>
                        <td style="padding: 14px 16px;">
                            <a href="<?= APP_URL ?>sgq/nao-conformidades" class="btn btn-xs btn-outline-primary" style="display: inline-flex; align-items: center; gap: 4px;">
                                <i class="fa-solid fa-triangle-exclamation"></i> Módulo RNC (5W2H)
                            </a>
                            <small style="display: block; color: #64748b; font-family: monospace; margin-top: 4px;">Tabelas: sgq_nao_conformidades / sgq_planos_acao</small>
                        </td>
                    </tr>

                    <tr style="border-top: 1px solid #f1f5f9; background: #fafafa;">
                        <td style="padding: 14px 16px; font-weight: 700; color: #0284c7;">9.1</td>
                        <td style="padding: 14px 16px; font-weight: 600; color: #0f172a;">Monitoramento, Medição e Indicadores</td>
                        <td style="padding: 14px 16px; color: #334155; line-height: 1.5;">
                            Dashboard analítico da qualidade calculando automaticamente: 1) Taxa de Retrabalho Técnico (<5%); 2) Lead Time Médio Operacional; 3) Índice Geral de Satisfação e NPS.
                        </td>
                        <td style="padding: 14px 16px;">
                            <a href="<?= APP_URL ?>sgq/indicadores" class="btn btn-xs btn-outline-primary" style="display: inline-flex; align-items: center; gap: 4px;">
                                <i class="fa-solid fa-chart-pie"></i> Indicadores da Qualidade
                            </a>
                            <small style="display: block; color: #64748b; font-family: monospace; margin-top: 4px;">Função: sgqObterIndicadores</small>
                        </td>
                    </tr>

                    <tr style="border-top: 1px solid #f1f5f9;">
                        <td style="padding: 14px 16px; font-weight: 700; color: #0284c7;">9.1.2</td>
                        <td style="padding: 14px 16px; font-weight: 600; color: #0f172a;">Satisfação do Cliente & Ouvidoria</td>
                        <td style="padding: 14px 16px; color: #334155; line-height: 1.5;">
                            Canal de Ouvidoria integrado no Portal do Cliente com geração de protocolo formal, relato com fotos, acompanhamento de parecer técnico e pesquisa de satisfação com ponderação de pesos.
                        </td>
                        <td style="padding: 14px 16px;">
                            <span style="background: #fdf2f8; color: #be185d; border: 1px solid #fbcfe8; padding: 2px 8px; border-radius: 4px; font-weight: 600; font-size: 11px;">
                                Portal do Cliente / Ouvidoria
                            </span>
                            <small style="display: block; color: #64748b; font-family: monospace; margin-top: 4px;">Rota: /portal/ouvidoria</small>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- SEÇÃO 4: TERMO DE ANÁLISE CRÍTICA E ASSINATURA -->
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
            <div>
                <strong style="color: #0f172a; font-size: 14px; display: block;">Amazon Certificadora de Embarcações</strong>
                <span style="color: #64748b; font-size: 13px;">Sistema de Gestão da Qualidade auditável e rastreado eletronicamente.</span>
            </div>
            <div style="text-align: right; font-size: 12px; color: #64748b;">
                <span>Belém/PA · Atualizado em: <?= date('d/m/Y H:i') ?></span><br>
                <strong style="color: #0f172a;">Responsável Técnico / Coordenação da Qualidade</strong>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
