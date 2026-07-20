<?php
/**
 * MODULO: CONFIGURACOES
 * Arquivo: index.php - Painel de controle de configurações
 * Acesso: apenas ADMIN
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
verificar_cargo('ADMIN');

// Garantir que a tabela configuracoes existe
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS configuracoes (
        chave VARCHAR(100) NOT NULL PRIMARY KEY,
        valor TEXT NOT NULL,
        descricao VARCHAR(255) DEFAULT NULL,
        atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    
    $pdo->exec("INSERT IGNORE INTO configuracoes (chave, valor, descricao) VALUES 
        ('meta_mensal', '50000.00', 'Meta mensal de faturamento comercial em R$'),
        ('dados_teste_embarcacoes', '0', 'Exibe o preenchimento rápido com dados fictícios no cadastro de embarcações')");
    $dadosTesteEmbarcacoesAtivos = $pdo->query(
        "SELECT valor FROM configuracoes WHERE chave = 'dados_teste_embarcacoes' LIMIT 1"
    )->fetchColumn() === '1';
} catch (Exception $e) {
    $dadosTesteEmbarcacoesAtivos = false;
    error_log('Erro ao preparar configurações gerais: ' . $e->getMessage());
}

$titulo_page = 'Configurações - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div class="welcome-section" style="margin-bottom: 30px;">
        <div>
            <h1><i class="fas fa-cog"></i> Configurações do Sistema</h1>
            <p>Selecione a categoria que deseja configurar.</p>
        </div>
    </div>

    <div class="card" style="margin-bottom: 24px; border-left: 4px solid #f0ad4e;">
        <div class="card-body" style="display:flex;align-items:center;justify-content:space-between;gap:24px;flex-wrap:wrap;">
            <div style="flex:1;min-width:260px;">
                <span class="badge" style="margin-bottom:8px;background:#fff3cd;color:#7a5400;">AMBIENTE DE TESTES</span>
                <h3 style="margin:0 0 8px;color:var(--cor-texto);">
                    <i class="fas fa-flask"></i> Dados rápidos para embarcações
                </h3>
                <p style="margin:0;color:var(--cor-texto-secundario);">
                    Quando ativo, o formulário de nova embarcação oferece três modelos fictícios para preencher todos os campos com um clique.
                </p>
            </div>
            <form method="POST" action="<?php echo APP_URL; ?>configuracoes/actions" style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
                <input type="hidden" name="csrf_token" value="<?php echo h(gerarCSRF()); ?>">
                <input type="hidden" name="action" value="salvar">
                <input type="hidden" name="redirect_to" value="configuracoes">
                <input type="hidden" name="cfg[dados_teste_embarcacoes]" value="0">
                <label for="dados_teste_embarcacoes" style="display:flex;align-items:center;gap:9px;margin:0;cursor:pointer;font-weight:700;">
                    <input type="checkbox"
                           id="dados_teste_embarcacoes"
                           name="cfg[dados_teste_embarcacoes]"
                           value="1"
                           <?php echo $dadosTesteEmbarcacoesAtivos ? 'checked' : ''; ?>
                           style="width:22px;height:22px;">
                    <?php echo $dadosTesteEmbarcacoesAtivos ? 'Ativado' : 'Desativado'; ?>
                </label>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Salvar opção
                </button>
            </form>
        </div>
    </div>

    <div class="dashboard-cards" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">

        <a href="<?php echo APP_URL; ?>configuracoes/financeiro" class="card-link" style="text-decoration:none;color:inherit;display:block;">
            <div class="card" style="height:100%;transition:transform .2s,box-shadow .2s;cursor:pointer;">
                <div class="card-body" style="text-align:center;padding:40px 20px;">
                    <i class="fas fa-building-columns" style="font-size:3rem;color:#28a745;margin-bottom:15px;"></i>
                    <h3 style="margin-bottom:10px;color:var(--cor-texto);">Configuração Financeira</h3>
                    <p style="color:var(--cor-texto-secundario);font-size:.95rem;">Cadastre escritórios, vincule funcionários e defina metas mensais por unidade e vendedor.</p>
                </div>
            </div>
        </a>
        
        <!-- Configurações Gerais -->
        <a href="<?php echo APP_URL; ?>configuracoes/geral" class="card-link" style="text-decoration: none; color: inherit; display: block;">
            <div class="card" style="height: 100%; transition: transform 0.2s, box-shadow 0.2s; cursor: pointer;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 10px 20px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='';">
                <div class="card-body" style="text-align: center; padding: 40px 20px;">
                    <i class="fas fa-sliders-h" style="font-size: 3rem; color: var(--cor-destaque); margin-bottom: 15px;"></i>
                    <h3 style="margin-bottom: 10px; color: var(--cor-texto);">Geral & Comercial</h3>
                    <p style="color: var(--cor-texto-secundario); font-size: 0.95rem;">
                        Configurações de meta mensal de faturamento e parâmetros gerais do sistema.
                    </p>
                </div>
            </div>
        </a>

        <!-- Configurações Básicas do Sistema -->
        <a href="<?php echo APP_URL; ?>configuracoes/basicas" class="card-link" style="text-decoration: none; color: inherit; display: block;">
            <div class="card" style="height: 100%; transition: transform 0.2s, box-shadow 0.2s; cursor: pointer;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 10px 20px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='';">
                <div class="card-body" style="text-align: center; padding: 40px 20px;">
                    <i class="fas fa-users-cog" style="font-size: 3rem; color: var(--cor-destaque); margin-bottom: 15px;"></i>
                    <h3 style="margin-bottom: 10px; color: var(--cor-texto);">Configurações Básicas</h3>
                    <p style="color: var(--cor-texto-secundario); font-size: 0.95rem;">
                        Permissões e acesso ao módulo de documentação por usuário.
                    </p>
                </div>
            </div>
        </a>

        <!-- Responsáveis pela Assinatura -->
        <a href="<?php echo APP_URL; ?>responsaveis_assinatura" class="card-link" style="text-decoration: none; color: inherit; display: block;">
            <div class="card" style="height: 100%; transition: transform 0.2s, box-shadow 0.2s; cursor: pointer;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 10px 20px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='';">
                <div class="card-body" style="text-align: center; padding: 40px 20px;">
                    <i class="fas fa-file-signature" style="font-size: 3rem; color: #17a2b8; margin-bottom: 15px;"></i>
                    <h3 style="margin-bottom: 10px; color: var(--cor-texto);">Responsáveis pela Assinatura</h3>
                    <p style="color: var(--cor-texto-secundario); font-size: 0.95rem;">
                        Cadastro de responsáveis para emissão de certificados (Nome, Cargo e Registro).
                    </p>
                </div>
            </div>
        </a>

        <!-- Backup e limpeza do sistema -->
        <a href="<?php echo APP_URL; ?>configuracoes/backup" class="card-link" style="text-decoration: none; color: inherit; display: block;">
            <div class="card" style="height: 100%; transition: transform 0.2s, box-shadow 0.2s; cursor: pointer;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 10px 20px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='';">
                <div class="card-body" style="text-align: center; padding: 40px 20px;">
                    <i class="fas fa-database" style="font-size: 3rem; color: #28a745; margin-bottom: 15px;"></i>
                    <h3 style="margin-bottom: 10px; color: var(--cor-texto);">Backup e Limpeza</h3>
                    <p style="color: var(--cor-texto-secundario); font-size: 0.95rem;">
                        Baixe ou restaure o banco em SQL e remova os dados operacionais quando necessário.
                    </p>
                </div>
            </div>
        </a>

        <a href="<?php echo APP_URL; ?>configuracoes/exportacoes" class="card-link" style="text-decoration: none; color: inherit; display: block;">
            <div class="card" style="height:100%;transition:transform .2s,box-shadow .2s;cursor:pointer">
                <div class="card-body" style="text-align:center;padding:40px 20px">
                    <i class="fas fa-file-archive" style="font-size:3rem;color:#0d6efd;margin-bottom:15px"></i>
                    <h3 style="margin-bottom:10px;color:var(--cor-texto)">Exportação de documentos</h3>
                    <p style="color:var(--cor-texto-secundario);font-size:.95rem">Escolha categorias e filtros para baixar ZIPs organizados com PDFs e fotos de vistorias.</p>
                </div>
            </div>
        </a>

    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
