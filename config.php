<?php
/**
 * CONFIGURACAO DO SISTEMA ERP
 * Arquivo de configuracao principal - Conexao MySQL e constantes
 */

// Forcar reload dos arquivos (desabilitar OPcache)
ini_set('opcache.enable', '0');
ini_set('opcache.enable_cli', '0');

// Erros: registrar sempre, mas so exibir quando APP_DEBUG estiver habilitado.
define('APP_DEBUG', filter_var(getenv('APP_DEBUG') ?: '0', FILTER_VALIDATE_BOOL));
error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');

// Configurar encoding UTF-8 para PHP
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// Sessao
if (session_status() === PHP_SESSION_NONE) {
    // Nao expirar a autenticacao por inatividade durante o uso normal.
    // O prazo longo abaixo existe apenas para a manutencao tecnica dos arquivos
    // de sessao e do cookie; a saida continua sendo feita pelo logout explicito.
    $sessionLifetime = 60 * 60 * 24 * 365;
    ini_set('session.gc_maxlifetime', (string)$sessionLifetime);
    ini_set('session.cookie_lifetime', (string)$sessionLifetime);

    $sessionSecure = str_starts_with(strtolower((string)(getenv('APP_URL') ?: '')), 'https://')
        || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => $sessionLifetime,
        'path' => '/',
        'secure' => $sessionSecure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Constantes do sistema (definidas antes de serem usadas)
define('APP_NAME', 'Sistema Amazon');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost:8082/');
define('APP_THEME', 'verde_escuro');

// Conexao com o banco de dados (usa variaveis de ambiente do Docker ou fallback)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'erp_sistema');
define('DB_USER', getenv('DB_USER') ?: 'erp_user');
define('DB_PASS', getenv('DB_PASS') ?: 'erp_pass_2026');
define('DB_CHARSET', 'utf8mb4');

// Credenciais MinIO / S3 Storage
define('MINIO_ENDPOINT', getenv('MINIO_ENDPOINT') ?: 'http://minio:9000');
define('MINIO_ACCESS_KEY', getenv('MINIO_ACCESS_KEY') ?: 'erp_minio_admin');
define('MINIO_SECRET_KEY', getenv('MINIO_SECRET_KEY') ?: 'erp_minio_pass_2026');
define('MINIO_BUCKET', getenv('MINIO_BUCKET') ?: 'erp-storage');
define('MINIO_CAMPO_BUCKET', getenv('MINIO_CAMPO_BUCKET') ?: 'erp-campo-private');

// Credenciais SMTP para envio de e-mails (PHPMailer)
define('MAIL_HOST', getenv('MAIL_HOST') ?: 'smtp.gmail.com');
define('MAIL_PORT', getenv('MAIL_PORT') ?: 587);
define('MAIL_USERNAME', getenv('MAIL_USERNAME') ?: '');
define('MAIL_PASSWORD', getenv('MAIL_PASSWORD') ?: '');
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: APP_NAME);
define('MAIL_ENCRYPTION', getenv('MAIL_ENCRYPTION') ?: 'tls');
define('MAIL_VERIFY_PEER', filter_var(getenv('MAIL_VERIFY_PEER') ?: '0', FILTER_VALIDATE_BOOL));

// Dados bancários para propostas e e-mails
define('BANCO_NOME', getenv('BANCO_NOME') ?: 'Banco do Brasil');
define('BANCO_AGENCIA', getenv('BANCO_AGENCIA') ?: '0000-0');
define('BANCO_CONTA', getenv('BANCO_CONTA') ?: '00000-0');
define('PIX_CHAVE', getenv('PIX_CHAVE') ?: 'contato@amazonnaval.com.br');
define('EMAIL_CONTATO', getenv('EMAIL_CONTATO') ?: 'contato@amazonnaval.com.br');
define('TELEFONE_CONTATO', getenv('TELEFONE_CONTATO') ?: '(91) 0000-0000');

// Meta mensal comercial (valor em R$)
define('META_MENSAL', getenv('META_MENSAL') ? (float)getenv('META_MENSAL') : 50000.00);

// Diretorios
define('BASE_PATH', __DIR__);
define('UPLOADS_PATH', __DIR__ . '/uploads/');

// Conexao PDO com MySQL
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    die("Erro de conexao com o banco de dados: " . $e->getMessage());
}
