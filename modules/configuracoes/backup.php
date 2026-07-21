<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
exigirAcesso('configuracoes');

$titulo_page = 'Backup e Limpeza - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div class="welcome-section" style="margin-bottom:20px;">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:20px;width:100%;flex-wrap:wrap;">
            <div>
                <h1><i class="fas fa-database"></i> Backup e Limpeza</h1>
                <p>Baixe, restaure ou limpe os dados do banco do sistema.</p>
            </div>
            <a href="<?= APP_URL ?>configuracoes" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar</a>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:20px;align-items:start;">
        <div class="card">
            <div class="card-header">
                <h3 style="color:var(--cor-destaque);"><i class="fas fa-file-download"></i> Baixar banco de dados</h3>
            </div>
            <div class="card-body">
                <p style="color:var(--cor-texto-secundario);margin-bottom:12px;">
                    Gera um arquivo <strong>.sql</strong> completo, com estrutura e dados, pronto para ser restaurado quando necessário.
                </p>
                <p style="color:var(--cor-texto-secundario);font-size:.9rem;margin-bottom:22px;">
                    O download pode levar alguns segundos, dependendo do tamanho do banco.
                </p>
                <form method="POST" action="<?= APP_URL ?>configuracoes/backup_actions">
                    <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>">
                    <input type="hidden" name="action" value="baixar_sql">
                    <button type="submit" class="btn btn-success"><i class="fas fa-download"></i> Baixar backup SQL</button>
                </form>
            </div>
        </div>

        <div class="card" style="border:1px solid #f0ad4e;">
            <div class="card-header" style="background:rgba(240,173,78,.08);">
                <h3 style="color:#b26a00;"><i class="fas fa-file-import"></i> Restaurar banco de dados</h3>
            </div>
            <div class="card-body">
                <p style="margin-bottom:12px;">
                    Importe um arquivo <strong>.sql</strong> para restaurar a estrutura e os dados salvos anteriormente.
                </p>
                <p style="color:var(--cor-texto-secundario);font-size:.9rem;margin-bottom:18px;">
                    O banco atual será substituído. Antes da restauração, o sistema cria automaticamente um backup de segurança.
                </p>
                <form method="POST" enctype="multipart/form-data" action="<?= APP_URL ?>configuracoes/backup_actions" id="form-importar-sql">
                    <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>">
                    <input type="hidden" name="action" value="importar_sql">
                    <div class="form-group" style="margin-bottom:16px;">
                        <label for="arquivo-sql">Arquivo SQL <small style="color:var(--cor-texto-secundario);">(máximo 10 MB)</small></label>
                        <input type="file" id="arquivo-sql" name="arquivo_sql" accept=".sql,application/sql,text/plain" required
                               style="display:block;width:100%;margin-top:7px;padding:9px;border:1px solid var(--cor-borda);border-radius:6px;">
                    </div>
                    <div class="form-group" style="margin-bottom:18px;">
                        <label for="confirmacao-importacao">Digite <strong>IMPORTAR</strong> para confirmar</label>
                        <input type="text" id="confirmacao-importacao" name="confirmacao_importacao" autocomplete="off" required
                               placeholder="IMPORTAR" style="width:100%;padding:10px 14px;margin-top:6px;">
                    </div>
                    <button type="submit" class="btn btn-warning" id="btn-importar-sql" disabled>
                        <i class="fas fa-upload"></i> Importar e restaurar banco
                    </button>
                </form>
            </div>
        </div>

        <div class="card" style="border:1px solid #dc3545;">
            <div class="card-header" style="background:rgba(220,53,69,.08);">
                <h3 style="color:#dc3545;"><i class="fas fa-exclamation-triangle"></i> Limpar dados do sistema</h3>
            </div>
            <div class="card-body">
                <p style="margin-bottom:12px;">
                    Remove permanentemente clientes, embarcações, propostas, vistorias, certificados, financeiro, contratos, agendamentos e históricos.
                </p>
                <p style="color:var(--cor-texto-secundario);font-size:.9rem;margin-bottom:18px;">
                    Usuários, permissões, configurações e catálogos básicos serão mantidos para que o sistema continue acessível.
                </p>
                <div style="padding:12px 14px;background:rgba(220,53,69,.08);border-radius:6px;color:#a71d2a;margin-bottom:18px;">
                    <i class="fas fa-shield-alt"></i> Recomendamos baixar o backup SQL antes de continuar. Esta ação não pode ser desfeita.
                </div>
                <form method="POST" action="<?= APP_URL ?>configuracoes/backup_actions" id="form-limpar-dados">
                    <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>">
                    <input type="hidden" name="action" value="limpar_dados">
                    <div class="form-group" style="margin-bottom:18px;">
                        <label for="confirmacao-limpeza">Digite <strong>LIMPAR</strong> para confirmar</label>
                        <input type="text" id="confirmacao-limpeza" name="confirmacao" autocomplete="off" required
                               placeholder="LIMPAR" style="width:100%;padding:10px 14px;margin-top:6px;">
                    </div>
                    <button type="submit" class="btn btn-danger" id="btn-limpar-dados" disabled>
                        <i class="fas fa-trash-alt"></i> Excluir todos os dados operacionais
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const input = document.getElementById('confirmacao-limpeza');
    const button = document.getElementById('btn-limpar-dados');
    const form = document.getElementById('form-limpar-dados');
    const importInput = document.getElementById('confirmacao-importacao');
    const importFile = document.getElementById('arquivo-sql');
    const importButton = document.getElementById('btn-importar-sql');
    const importForm = document.getElementById('form-importar-sql');

    input.addEventListener('input', function () {
        button.disabled = input.value.trim() !== 'LIMPAR';
    });

    form.addEventListener('submit', function (event) {
        if (input.value.trim() !== 'LIMPAR' || !window.confirm('Confirma a exclusão permanente de todos os dados operacionais?')) {
            event.preventDefault();
        }
    });

    function atualizarImportacao() {
        importButton.disabled = importInput.value.trim() !== 'IMPORTAR' || importFile.files.length !== 1;
    }

    importInput.addEventListener('input', atualizarImportacao);
    importFile.addEventListener('change', atualizarImportacao);

    importForm.addEventListener('submit', function (event) {
        if (importInput.value.trim() !== 'IMPORTAR' || importFile.files.length !== 1 ||
            !window.confirm('Confirma a substituição do banco atual pelos dados deste arquivo SQL?')) {
            event.preventDefault();
        }
    });
})();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
