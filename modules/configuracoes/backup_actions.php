<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
exigirAcesso('configuracoes');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    redirecionar(APP_URL . 'configuracoes/backup');
}

if (!verificarCSRF($_POST['csrf_token'] ?? '')) {
    setMensagem('error', 'Token de segurança inválido. Atualize a página e tente novamente.');
    redirecionar(APP_URL . 'configuracoes/backup');
}

$action = $_POST['action'] ?? '';

function criarBackupSql(): array
{
    $backupDir = BASE_PATH . '/storage/backups';
    if (!is_dir($backupDir) && !mkdir($backupDir, 0775, true) && !is_dir($backupDir)) {
        throw new RuntimeException('Não foi possível preparar a pasta de backups.');
    }

    $filename = 'backup_' . DB_NAME . '_' . date('Y-m-d_H-i-s') . '.sql';
    $filepath = $backupDir . '/' . $filename;
    $errorFile = tempnam(sys_get_temp_dir(), 'backup_error_');
    if ($errorFile === false) {
        throw new RuntimeException('Não foi possível preparar o processo de backup.');
    }

    $command = sprintf(
        'MYSQL_PWD=%s mysqldump --ssl=FALSE --no-tablespaces --host=%s --user=%s --single-transaction --quick --routines --triggers --events --default-character-set=utf8mb4 %s > %s 2> %s',
        escapeshellarg(DB_PASS), escapeshellarg(DB_HOST), escapeshellarg(DB_USER),
        escapeshellarg(DB_NAME), escapeshellarg($filepath), escapeshellarg($errorFile)
    );

    exec($command, $output, $exitCode);
    $error = trim((string) file_get_contents($errorFile));
    @unlink($errorFile);

    if ($exitCode !== 0 || !is_file($filepath) || filesize($filepath) === 0) {
        @unlink($filepath);
        error_log('Falha ao gerar backup SQL: ' . $error);
        throw new RuntimeException('Não foi possível gerar o backup do banco de dados.');
    }

    return [$filepath, $filename];
}

try {
    if ($action === 'baixar_sql') {
        [$filepath, $filename] = criarBackupSql();
        session_write_close();
        header('Content-Type: application/sql; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('X-Content-Type-Options: nosniff');
        readfile($filepath);
        exit;
    }

    if ($action === 'importar_sql') {
        if (trim((string) ($_POST['confirmacao_importacao'] ?? '')) !== 'IMPORTAR') {
            throw new RuntimeException('Digite IMPORTAR para confirmar a restauração do banco.');
        }

        $upload = $_FILES['arquivo_sql'] ?? null;
        if (!$upload || !isset($upload['error']) || is_array($upload['error'])) {
            throw new RuntimeException('Selecione um arquivo SQL válido.');
        }

        if ($upload['error'] !== UPLOAD_ERR_OK) {
            $mensagensUpload = [
                UPLOAD_ERR_INI_SIZE => 'O arquivo excede o limite de 10 MB permitido pelo servidor.',
                UPLOAD_ERR_FORM_SIZE => 'O arquivo excede o tamanho permitido.',
                UPLOAD_ERR_PARTIAL => 'O envio do arquivo foi interrompido. Tente novamente.',
                UPLOAD_ERR_NO_FILE => 'Selecione o arquivo SQL que deseja importar.',
            ];
            throw new RuntimeException($mensagensUpload[$upload['error']] ?? 'Não foi possível receber o arquivo SQL.');
        }

        $nomeOriginal = (string) ($upload['name'] ?? '');
        $arquivoTemporario = (string) ($upload['tmp_name'] ?? '');
        $tamanho = (int) ($upload['size'] ?? 0);

        if (strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION)) !== 'sql') {
            throw new RuntimeException('O arquivo precisa ter a extensão .sql.');
        }
        if ($tamanho <= 0 || $tamanho > 10 * 1024 * 1024 || !is_uploaded_file($arquivoTemporario)) {
            throw new RuntimeException('O arquivo SQL está vazio, é inválido ou excede o limite de 10 MB.');
        }

        $inicioArquivo = (string) file_get_contents($arquivoTemporario, false, null, 0, 8192);
        if ($inicioArquivo === '' || strpos($inicioArquivo, "\0") !== false) {
            throw new RuntimeException('O conteúdo enviado não parece ser um arquivo SQL válido.');
        }

        // Mantém uma cópia recuperável do estado anterior a cada restauração.
        [, $backupSeguranca] = criarBackupSql();
        $errorFile = tempnam(sys_get_temp_dir(), 'import_error_');
        if ($errorFile === false) {
            throw new RuntimeException('Não foi possível preparar a importação.');
        }

        $command = sprintf(
            'MYSQL_PWD=%s mysql --ssl=FALSE --host=%s --user=%s --default-character-set=utf8mb4 %s < %s 2> %s',
            escapeshellarg(DB_PASS), escapeshellarg(DB_HOST), escapeshellarg(DB_USER),
            escapeshellarg(DB_NAME), escapeshellarg($arquivoTemporario), escapeshellarg($errorFile)
        );

        exec($command, $output, $exitCode);
        $error = trim((string) file_get_contents($errorFile));
        @unlink($errorFile);

        if ($exitCode !== 0) {
            error_log('Falha ao importar backup SQL: ' . $error);
            throw new RuntimeException('A importação falhou. O banco anterior foi preservado no backup de segurança ' . $backupSeguranca . '.');
        }

        setMensagem('success', 'Banco de dados restaurado com sucesso. Backup de segurança criado: ' . $backupSeguranca . '.');
        redirecionar(APP_URL . 'configuracoes/backup');
    }

    if ($action === 'limpar_dados') {
        if (trim((string) ($_POST['confirmacao'] ?? '')) !== 'LIMPAR') {
            throw new RuntimeException('Digite LIMPAR para confirmar a exclusão dos dados.');
        }

        $preservadas = [
            'configuracoes', 'usuarios', 'usuario_perfis', 'usuario_permissoes',
            'exigencias_catalogo', 'exigencias_categorias', 'servicos',
            'tipos_embarcacao', 'responsaveis_assinatura', 'escritorios',
            'usuario_escritorios',
        ];

        $stmt = $pdo->prepare(
            "SELECT TABLE_NAME FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = :schema AND TABLE_TYPE = 'BASE TABLE'"
        );
        $stmt->execute([':schema' => DB_NAME]);
        $tabelas = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $limpas = array_values(array_diff($tabelas, $preservadas));

        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        try {
            foreach ($limpas as $tabela) {
                $nomeSeguro = '`' . str_replace('`', '``', $tabela) . '`';
                $pdo->exec("DELETE FROM {$nomeSeguro}");
                $pdo->exec("ALTER TABLE {$nomeSeguro} AUTO_INCREMENT = 1");
            }
        } finally {
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        }

        setMensagem('success', 'Dados operacionais removidos com sucesso. Usuários, cargos, responsáveis e suas assinaturas, escritórios, vínculos, configurações e catálogos básicos foram mantidos.');
        redirecionar(APP_URL . 'configuracoes/backup');
    }

    throw new RuntimeException('Ação de backup inválida.');
} catch (Throwable $e) {
    error_log('Configuracoes de backup: ' . $e->getMessage());
    setMensagem('error', $e instanceof RuntimeException ? $e->getMessage() : 'Não foi possível concluir a operação.');
    redirecionar(APP_URL . 'configuracoes/backup');
}
