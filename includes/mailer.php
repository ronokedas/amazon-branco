<?php
/**
 * MAILER - Envio de e-mails via PHPMailer
 * 
 * Função central: enviarEmail()
 * Suporta leitura de credenciais dinâmicas do banco (tabela configuracoes)
 * com fallback para constantes do config.php / .env
 * 
 * Uso:
 *   enviarEmail('destino@email.com', 'Nome', 'Assunto', '<html>...</html>');
 *   enviarEmail('destino@email.com', 'Nome', 'Assunto', '<html>...</html>', ['caminho/arquivo.pdf']);
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Retorna as configurações SMTP ativas (Banco de Dados > Constantes config.php)
 *
 * @param PDO|null $pdo
 * @return array
 */
function obterConfiguracaoSMTP(?PDO $pdo = null): array
{
    if ($pdo === null) {
        global $pdo;
    }

    $config = [
        'mail_host'        => defined('MAIL_HOST') && MAIL_HOST !== '' ? MAIL_HOST : 'smtp-relay.brevo.com',
        'mail_port'        => defined('MAIL_PORT') && (int)MAIL_PORT > 0 ? (int)MAIL_PORT : 587,
        'mail_username'    => defined('MAIL_USERNAME') && MAIL_USERNAME !== '' ? MAIL_USERNAME : 'b157d8001@smtp-brevo.com',
        'mail_password'    => defined('MAIL_PASSWORD') && MAIL_PASSWORD !== '' ? MAIL_PASSWORD : 'xsmtpsib-782cfe3f1e6ae55475b72b76140451807a16d512c4cfbf6b24266f56f308fe79-bOlCBIQS6gnPNSaO',
        'mail_from_name'   => defined('MAIL_FROM_NAME') && MAIL_FROM_NAME !== '' ? MAIL_FROM_NAME : 'Amazon Naval',
        'mail_encryption'  => defined('MAIL_ENCRYPTION') && MAIL_ENCRYPTION !== '' ? MAIL_ENCRYPTION : 'tls',
        'mail_verify_peer' => defined('MAIL_VERIFY_PEER') ? (int)MAIL_VERIFY_PEER : 0,
        'email_contato'    => defined('EMAIL_CONTATO') && EMAIL_CONTATO !== '' ? EMAIL_CONTATO : 'contato@amazonnaval.com.br',
    ];

    if ($pdo instanceof PDO) {
        try {
            $chaves = array_keys($config);
            $inClause = implode(',', array_fill(0, count($chaves), '?'));
            $stmt = $pdo->prepare("SELECT chave, valor FROM configuracoes WHERE chave IN ($inClause)");
            $stmt->execute($chaves);
            $dbConfigs = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            foreach ($config as $chave => $padrao) {
                if (isset($dbConfigs[$chave]) && trim((string)$dbConfigs[$chave]) !== '') {
                    if ($chave === 'mail_port' || $chave === 'mail_verify_peer') {
                        $config[$chave] = (int)$dbConfigs[$chave];
                    } else {
                        $config[$chave] = trim((string)$dbConfigs[$chave]);
                    }
                }
            }
        } catch (Throwable $e) {
            error_log('Erro ao ler configuracoes de e-mail do banco: ' . $e->getMessage());
        }
    }

    return $config;
}

/**
 * Salva as configurações de e-mail SMTP na tabela configuracoes
 *
 * @param PDO $pdo
 * @param array $dados
 * @return bool
 */
function salvarConfiguracaoSMTP(PDO $pdo, array $dados): bool
{
    $mapaDescricoes = [
        'mail_host'        => 'Servidor SMTP para envio de e-mails',
        'mail_port'        => 'Porta do servidor SMTP (ex.: 587 ou 465)',
        'mail_username'    => 'Usuário ou login de autenticação SMTP',
        'mail_password'    => 'Senha ou chave de API de autenticação SMTP',
        'mail_from_name'   => 'Nome do remetente exibido nas mensagens',
        'mail_encryption'  => 'Criptografia da conexão SMTP (tls, ssl ou none)',
        'mail_verify_peer' => 'Verificar certificado SSL do servidor SMTP (1=sim, 0=não)',
        'email_contato'    => 'E-mail do remetente e endereço para resposta (Reply-To)',
    ];

    $stmt = $pdo->prepare("
        INSERT INTO configuracoes (chave, valor, descricao)
        VALUES (:chave, :valor, :descricao)
        ON DUPLICATE KEY UPDATE valor = VALUES(valor), descricao = VALUES(descricao)
    ");

    foreach ($mapaDescricoes as $chave => $descricao) {
        if (!array_key_exists($chave, $dados)) {
            continue;
        }
        $valor = trim((string)$dados[$chave]);
        if ($chave === 'mail_port') {
            $valor = (string)((int)$valor > 0 ? (int)$valor : 587);
        } elseif ($chave === 'mail_verify_peer') {
            $valor = $valor === '1' ? '1' : '0';
        }
        $stmt->execute([
            ':chave'     => $chave,
            ':valor'     => $valor,
            ':descricao' => $descricao,
        ]);
    }

    return true;
}

/**
 * Dispara um e-mail de teste para validação imediata do servidor SMTP
 *
 * @param string $destinatario
 * @param array|null $overrideConfig
 * @param PDO|null $pdo
 * @return array ['success' => bool, 'message' => string, 'debug' => string]
 */
function testarConexaoSMTP(string $destinatario, ?array $overrideConfig = null, ?PDO $pdo = null): array
{
    if ($pdo === null) {
        global $pdo;
    }

    $cfg = obterConfiguracaoSMTP($pdo);
    if (!empty($overrideConfig)) {
        foreach ($overrideConfig as $k => $v) {
            if (isset($cfg[$k]) && $v !== null && $v !== '') {
                $cfg[$k] = $v;
            }
        }
    }

    $destinatario = filter_var(trim($destinatario), FILTER_VALIDATE_EMAIL);
    if (!$destinatario) {
        return [
            'success' => false,
            'message' => 'E-mail de destino inválido para o teste.',
            'debug'   => '',
        ];
    }

    $mail = new PHPMailer(true);
    $debugLog = '';

    try {
        $mail->isSMTP();
        $mail->Host     = $cfg['mail_host'];
        $mail->SMTPAuth = true;

        if (stripos($cfg['mail_host'], 'brevo') !== false || stripos($cfg['mail_host'], 'sendinblue') !== false) {
            $mail->AuthType = 'LOGIN';
        }

        $mail->Username   = $cfg['mail_username'];
        $mail->Password   = $cfg['mail_password'];
        $mail->SMTPSecure = strtolower($cfg['mail_encryption']) === 'none' ? '' : strtolower($cfg['mail_encryption']);
        if (empty($mail->SMTPSecure)) {
            $mail->SMTPAutoTLS = false;
        }
        $mail->Port = (int)$cfg['mail_port'];

        if (empty($cfg['mail_verify_peer'])) {
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true,
                ],
            ];
        }

        $mail->Timeout     = 25;
        $mail->SMTPDebug   = SMTP::DEBUG_CONNECTION;
        $mail->Debugoutput = function ($str, $level) use (&$debugLog) {
            $strLimpo = preg_replace('/(PASS(WORD)?|AUTH(ENTICATE)?|LOGIN)[^\r\n]*/i', '$1 [ocultado]', $str);
            $debugLog .= $strLimpo . "\n";
        };

        $remetente = !empty($cfg['email_contato']) ? $cfg['email_contato'] : $cfg['mail_username'];
        $mail->setFrom($remetente, $cfg['mail_from_name']);
        $mail->addAddress($destinatario);
        $mail->addReplyTo($remetente, $cfg['mail_from_name']);

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'Teste de Conexão SMTP - Amazon Naval';
        $agora = date('d/m/Y H:i:s');
        $usuarioNome = $_SESSION['usuario_nome'] ?? 'Administrador';

        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #dcdcdc; border-radius: 8px; overflow: hidden; background: #ffffff;'>
                <div style='background: #0b5944; color: #ffffff; padding: 22px; text-align: center;'>
                    <h2 style='margin: 0; font-size: 20px;'>Amazon Naval &bull; Teste SMTP</h2>
                    <p style='margin: 6px 0 0 0; font-size: 13px; opacity: 0.9;'>Sistema de Gestão Naval Integrada</p>
                </div>
                <div style='padding: 24px; color: #2d3748; line-height: 1.6;'>
                    <p style='font-size: 15px; margin-top: 0;'>Olá <strong>" . htmlspecialchars($destinatario, ENT_QUOTES, 'UTF-8') . "</strong>,</p>
                    <div style='background: #e6f4ea; border-left: 4px solid #137333; padding: 12px 16px; border-radius: 4px; margin: 16px 0;'>
                        <strong style='color: #137333; font-size: 15px;'>✔ Conexão SMTP estabelecida com sucesso!</strong>
                        <p style='margin: 4px 0 0 0; color: #202124; font-size: 13px;'>
                            O servidor de e-mail da Amazon Certificadora está autenticado e pronto para despachar propostas, agendamentos e notificações operacionais.
                        </p>
                    </div>
                    <table style='width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 13px;'>
                        <tr style='background: #f8fafc;'>
                            <td style='padding: 8px 12px; border: 1px solid #e2e8f0; font-weight: 600; width: 35%;'>Servidor SMTP:</td>
                            <td style='padding: 8px 12px; border: 1px solid #e2e8f0;'>" . htmlspecialchars($cfg['mail_host'] . ':' . $cfg['mail_port'], ENT_QUOTES, 'UTF-8') . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 12px; border: 1px solid #e2e8f0; font-weight: 600;'>Criptografia:</td>
                            <td style='padding: 8px 12px; border: 1px solid #e2e8f0;'>" . strtoupper(htmlspecialchars($cfg['mail_encryption'], ENT_QUOTES, 'UTF-8')) . "</td>
                        </tr>
                        <tr style='background: #f8fafc;'>
                            <td style='padding: 8px 12px; border: 1px solid #e2e8f0; font-weight: 600;'>Remetente:</td>
                            <td style='padding: 8px 12px; border: 1px solid #e2e8f0;'>" . htmlspecialchars($cfg['mail_from_name'] . ' <' . $remetente . '>', ENT_QUOTES, 'UTF-8') . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 12px; border: 1px solid #e2e8f0; font-weight: 600;'>Disparado por:</td>
                            <td style='padding: 8px 12px; border: 1px solid #e2e8f0;'>" . htmlspecialchars($usuarioNome, ENT_QUOTES, 'UTF-8') . "</td>
                        </tr>
                        <tr style='background: #f8fafc;'>
                            <td style='padding: 8px 12px; border: 1px solid #e2e8f0; font-weight: 600;'>Data / Horário:</td>
                            <td style='padding: 8px 12px; border: 1px solid #e2e8f0;'>" . $agora . "</td>
                        </tr>
                    </table>
                </div>
                <div style='background: #f8fafc; padding: 14px; text-align: center; color: #718096; font-size: 11px; border-top: 1px solid #e2e8f0;'>
                    Mensagem de teste automático &bull; Amazon Certificadora Naval
                </div>
            </div>
        ";
        $mail->AltBody = "Teste de conexao SMTP da Amazon Naval realizado com sucesso em {$agora}.";

        $mail->send();

        // Registrar no histórico de e-mails
        if ($pdo instanceof PDO) {
            $idNovo = function_exists('gerarUUID') ? gerarUUID() : bin2hex(random_bytes(16));
            $usuarioId = $_SESSION['usuario_id'] ?? null;
            try {
                $stmtLog = $pdo->prepare("
                    INSERT INTO email_logs (id, destinatario, assunto, tipo, status, enviado_por, created_at)
                    VALUES (:id, :dest, :assunto, 'teste', 'enviado', :usuario, NOW())
                ");
                $stmtLog->execute([
                    ':id'      => $idNovo,
                    ':dest'    => $destinatario,
                    ':assunto' => $mail->Subject,
                    ':usuario' => $usuarioId,
                ]);
            } catch (Throwable $eLog) {
                try {
                    $stmtLog = $pdo->prepare("
                        INSERT INTO email_logs (id, destinatario, assunto, tipo, status, enviado_por, created_at)
                        VALUES (:id, :dest, :assunto, 'assinatura', 'enviado', :usuario, NOW())
                    ");
                    $stmtLog->execute([
                        ':id'      => $idNovo,
                        ':dest'    => $destinatario,
                        ':assunto' => $mail->Subject,
                        ':usuario' => $usuarioId,
                    ]);
                } catch (Throwable $ex) {}
            }
        }

        return [
            'success' => true,
            'message' => 'E-mail de teste enviado com sucesso para ' . $destinatario . '!',
            'debug'   => $debugLog,
        ];
    } catch (Exception $e) {
        $erro = trim((string) $mail->ErrorInfo);
        if ($erro === '') {
            $erro = $e->getMessage();
        }
        if (function_exists('iconv')) {
            $convertido = @iconv('UTF-8', 'UTF-8//IGNORE', $erro);
            if ($convertido !== false) {
                $erro = $convertido;
            }
        }
        $erro = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', ' ', $erro);
        $erro = trim(preg_replace('/\s+/u', ' ', (string) $erro));

        // Registrar falha no histórico
        if ($pdo instanceof PDO) {
            $idNovo = function_exists('gerarUUID') ? gerarUUID() : bin2hex(random_bytes(16));
            $usuarioId = $_SESSION['usuario_id'] ?? null;
            try {
                $stmtLog = $pdo->prepare("
                    INSERT INTO email_logs (id, destinatario, assunto, tipo, status, mensagem_erro, enviado_por, created_at)
                    VALUES (:id, :dest, :assunto, 'teste', 'erro', :erro, :usuario, NOW())
                ");
                $stmtLog->execute([
                    ':id'      => $idNovo,
                    ':dest'    => $destinatario,
                    ':assunto' => 'Teste SMTP (Falha)',
                    ':erro'    => $erro,
                    ':usuario' => $usuarioId,
                ]);
            } catch (Throwable $eLog) {
                try {
                    $stmtLog = $pdo->prepare("
                        INSERT INTO email_logs (id, destinatario, assunto, tipo, status, mensagem_erro, enviado_por, created_at)
                        VALUES (:id, :dest, :assunto, 'assinatura', 'erro', :erro, :usuario, NOW())
                    ");
                    $stmtLog->execute([
                        ':id'      => $idNovo,
                        ':dest'    => $destinatario,
                        ':assunto' => 'Teste SMTP (Falha)',
                        ':erro'    => $erro,
                        ':usuario' => $usuarioId,
                    ]);
                } catch (Throwable $ex) {}
            }
        }

        return [
            'success' => false,
            'message' => 'Falha na conexão SMTP: ' . $erro,
            'debug'   => $debugLog,
        ];
    }
}

/**
 * Envia e-mail usando PHPMailer com configurações SMTP do banco ou config.php
 *
 * @param string $destinatario E-mail do destinatário
 * @param string $nome         Nome do destinatário
 * @param string $assunto      Assunto do e-mail
 * @param string $htmlBody     Corpo do e-mail em HTML
 * @param array  $anexos       Array opcional com caminhos absolutos dos arquivos para anexar
 * 
 * @return array ['success' => bool, 'message' => string]
 */
function enviarEmail(string $destinatario, string $nome, string $assunto, string $htmlBody, array $anexos = []): array
{
    $mail = new PHPMailer(true);

    try {
        $cfg = obterConfiguracaoSMTP();

        // Configurações do servidor SMTP
        $mail->isSMTP();
        $mail->Host     = $cfg['mail_host'];
        $mail->SMTPAuth = true;

        if (stripos($cfg['mail_host'], 'brevo') !== false || stripos($cfg['mail_host'], 'sendinblue') !== false) {
            $mail->AuthType = 'LOGIN';
        }

        $mail->Username   = $cfg['mail_username'];
        $mail->Password   = $cfg['mail_password'];
        $mail->SMTPSecure = strtolower($cfg['mail_encryption']) === 'none' ? '' : strtolower($cfg['mail_encryption']);
        if (empty($mail->SMTPSecure)) {
            $mail->SMTPAutoTLS = false;
        }
        $mail->Port = (int)$cfg['mail_port'];

        if (empty($cfg['mail_verify_peer'])) {
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true,
                ],
            ];
        }

        // Timeout e debug (desabilitado em produção)
        $mail->Timeout   = 30;
        $mail->SMTPDebug = SMTP::DEBUG_OFF;

        // Remetente e destinatário
        $remetente = !empty($cfg['email_contato']) ? $cfg['email_contato'] : $cfg['mail_username'];
        $mail->setFrom($remetente, $cfg['mail_from_name']);
        $mail->addAddress($destinatario, $nome);
        $mail->addReplyTo($remetente, $cfg['mail_from_name']);

        // Anexos
        if (!empty($anexos)) {
            foreach ($anexos as $caminho) {
                if (file_exists($caminho)) {
                    $mail->addAttachment($caminho);
                }
            }
        }

        // Conteúdo
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $assunto;
        $mail->Body    = $htmlBody;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $htmlBody));

        $mail->send();

        return [
            'success' => true,
            'message' => 'E-mail enviado com sucesso para ' . $destinatario
        ];
    } catch (Exception $e) {
        $erro = trim((string) $mail->ErrorInfo);
        if ($erro === '') {
            $erro = $e->getMessage();
        }
        if (function_exists('iconv')) {
            $convertido = @iconv('UTF-8', 'UTF-8//IGNORE', $erro);
            if ($convertido !== false) {
                $erro = $convertido;
            }
        }
        $erro = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', ' ', $erro);
        $erro = trim(preg_replace('/\s+/u', ' ', (string) $erro));
        return [
            'success' => false,
            'message' => 'Erro ao enviar e-mail: ' . $erro
        ];
    }
}
