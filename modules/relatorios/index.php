<?php
/**
 * MÓDULO: CENTRAL DE RELATÓRIOS NAVAIS E GERENCIAIS
 * Arquivo: index.php - Hub centralizado de acesso a relatórios operacionais
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar autenticação
exigirAcesso('relatorios');

$titulo_page = 'Central de Relatórios - Amazon Certificadora';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-content" id="mainContent">
    <div class="page-header" style="margin-bottom: 24px;">
        <div>
            <span class="flow-eyebrow" style="color: var(--cor-destaque, #2596be); font-weight: 600; font-size: 0.85rem; text-transform: uppercase;">
                <i class="fa-solid fa-chart-pie"></i> Inteligência & Auditoria Naval
            </span>
            <h1 class="page-title" style="margin: 4px 0 6px 0; font-size: 1.6rem; color: var(--cor-texto, #1e293b);">
                Central de Relatórios Operacionais
            </h1>
            <p class="page-subtitle" style="color: var(--cor-texto-secundario, #64748b); margin: 0;">
                Acesse demonstrativos estatutários da Marinha do Brasil, relatórios financeiros consolidados e auditorias de campo.
            </p>
        </div>
    </div>

    <!-- Grid de Hubs de Relatórios Especializados -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; margin-bottom: 30px;">
        
        <!-- Card 1: Relatórios de Vistorias e Laudos Técnicos -->
        <div class="card" style="background: var(--cor-painel, #fff); border: 1px solid var(--cor-borda, #e2e8f0); border-radius: 12px; padding: 22px; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div>
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 14px;">
                    <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(37, 150, 190, 0.12); color: #2596be; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                        <i class="fa-solid fa-clipboard-check"></i>
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 1.1rem; color: var(--cor-texto, #1e293b);">Vistorias Técnicas e Laudos</h3>
                        <small class="text-muted">NORMAM-201 / NORMAM-202 / NPCP</small>
                    </div>
                </div>
                <p style="color: var(--cor-texto-secundario, #64748b); font-size: 0.9rem; line-height: 1.5; margin-bottom: 18px;">
                    Consulte os relatórios completos de checklist, registros fotográficos das exigências, histórico de Retorno A/S e laudos periciais emitidos.
                </p>
            </div>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <?php if (podeAcessar('vistorias')): ?>
                    <a href="<?= APP_URL ?>vistorias" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-list"></i> Ver Vistorias
                    </a>
                <?php endif; ?>
                <?php if (podeAcessar('relatorios_aprovacao')): ?>
                    <a href="<?= APP_URL ?>documentacao/aprovacao_relatorios" class="btn btn-secondary btn-sm">
                        <i class="fa-solid fa-file-circle-check"></i> Fila de Aprovação
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Card 2: Relatórios Financeiros e Faturamento -->
        <div class="card" style="background: var(--cor-painel, #fff); border: 1px solid var(--cor-borda, #e2e8f0); border-radius: 12px; padding: 22px; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div>
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 14px;">
                    <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(46, 204, 113, 0.12); color: #27ae60; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 1.1rem; color: var(--cor-texto, #1e293b);">Financeiro e Metas</h3>
                        <small class="text-muted">Receitas, despesas e faturamento por escritório</small>
                    </div>
                </div>
                <p style="color: var(--cor-texto-secundario, #64748b); font-size: 0.9rem; line-height: 1.5; margin-bottom: 18px;">
                    Demonstrativos financeiros por competência, atingimento de metas mensais, faturamento por tipo de serviço naval e exportação para Excel.
                </p>
            </div>
            <div>
                <?php if (podeAcessar('financeiro')): ?>
                    <a href="<?= APP_URL ?>financeiro/relatorios" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i> Relatórios Financeiros
                    </a>
                <?php else: ?>
                    <span class="badge badge-secondary" style="font-size: 0.8rem; padding: 6px 10px;">Acesso restrito ao Financeiro</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Card 3: Protocolos e Dossiês na Marinha (SISAP) -->
        <div class="card" style="background: var(--cor-painel, #fff); border: 1px solid var(--cor-borda, #e2e8f0); border-radius: 12px; padding: 22px; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div>
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 14px;">
                    <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(243, 156, 18, 0.12); color: #d35400; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                        <i class="fa-solid fa-folder-tree"></i>
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 1.1rem; color: var(--cor-texto, #1e293b);">Dossiês & Trâmites SISAP</h3>
                        <small class="text-muted">Capitanias, Delegacias e Agências Fluviais</small>
                    </div>
                </div>
                <p style="color: var(--cor-texto-secundario, #64748b); font-size: 0.9rem; line-height: 1.5; margin-bottom: 18px;">
                    Relatório consolidado de movimentações documentais, comprovantes de protocolo com hash SHA-256 e termos de custódia de documentos originais.
                </p>
            </div>
            <div>
                <?php if (podeAcessar('protocolos_documentais')): ?>
                    <a href="<?= APP_URL ?>protocolos" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-arrow-right-arrow-left"></i> Consultar Dossiês
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Card 4: Indicadores de Desempenho e Auditoria -->
        <div class="card" style="background: var(--cor-painel, #fff); border: 1px solid var(--cor-borda, #e2e8f0); border-radius: 12px; padding: 22px; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div>
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 14px;">
                    <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(142, 68, 173, 0.12); color: #8e44ad; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                        <i class="fa-solid fa-gauge"></i>
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 1.1rem; color: var(--cor-texto, #1e293b);">Indicadores da Qualidade</h3>
                        <small class="text-muted">Auditoria de Processos e Lead Time</small>
                    </div>
                </div>
                <p style="color: var(--cor-texto-secundario, #64748b); font-size: 0.9rem; line-height: 1.5; margin-bottom: 18px;">
                    Taxa de retrabalho de vistorias, lead time de emissão de certificados, satisfação de armadores e índice de resolutividade técnica.
                </p>
            </div>
            <div>
                <?php if (podeAcessar('sgq')): ?>
                    <a href="<?= APP_URL ?>sgq/indicadores" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-chart-column"></i> Ver Indicadores
                    </a>
                <?php else: ?>
                    <span class="badge badge-secondary" style="font-size: 0.8rem; padding: 6px 10px;">Exclusivo para Gestores</span>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
