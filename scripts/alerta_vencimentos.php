<?php
/**
 * SCRIPT DE ALERTA DE VENCIMENTO DE CERTIFICADOS
 * 
 * Verifica certificados CSN, CNBL e CNARQ com vencimento em 30 e 7 dias
 * e dispara e-mail automático para o cliente vinculado à embarcação.
 * 
 * Uso via cron (diário às 08h):
 *   php c:\sistema\scripts\alerta_vencimentos.php
 * 
 * Ou via agendador de tarefas do Windows:
 *   C:\php\php.exe c:\sistema\scripts\alerta_vencimentos.php
 */

// Carregar configurações
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';

// Log de execução
$log_file = __DIR__ . '/../logs/alerta_vencimentos.log';
$data_execucao = date('Y-m-d H:i:s');

function logMensagem($msg) {
    global $log_file, $data_execucao;
    $linha = "[{$data_execucao}] {$msg}" . PHP_EOL;
    file_put_contents($log_file, $linha, FILE_APPEND);
}

logMensagem("=== Início da execução do script de alerta de vencimentos ===");

try {
    // Períodos de alerta: 30 dias e 7 dias
    $periodos = [30, 7];
    $total_enviados = 0;
    $total_erros = 0;

    // Template de e-mail
    $templatePath = __DIR__ . '/../templates/email/vencimento.html';
    if (!file_exists($templatePath)) {
        logMensagem("ERRO: Template de e-mail não encontrado: {$templatePath}");
        exit(1);
    }
    $templateHtml = file_get_contents($templatePath);

    // Buscar certificados próximos do vencimento
    $certificados_encontrados = [];

    foreach ($periodos as $dias) {
        $data_limite = date('Y-m-d', strtotime("+{$dias} days"));

        // CSN
        $stmt = $pdo->prepare("
            SELECT id, numero, nome_embarcacao, data_validade, status
            FROM certificados_csn
            WHERE ativo = 1
              AND status IN ('emitido', 'assinado')
              AND data_validade <= :data_limite
              AND data_validade >= CURDATE()
            ORDER BY data_validade ASC
        ");
        $stmt->execute([':data_limite' => $data_limite]);
        $csn = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($csn as $c) {
            $c['tipo'] = 'CSN';
            $c['tabela'] = 'certificados_csn';
            $c['dias_vencimento'] = $dias;
            $certificados_encontrados[] = $c;
        }

        // CNBL
        $stmt = $pdo->prepare("
            SELECT id, numero, nome_embarcacao, data_validade, status
            FROM certificados_cnbl
            WHERE ativo = 1
              AND status IN ('emitido', 'assinado')
              AND data_validade <= :data_limite
              AND data_validade >= CURDATE()
            ORDER BY data_validade ASC
        ");
        $stmt->execute([':data_limite' => $data_limite]);
        $cnbl = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($cnbl as $c) {
            $c['tipo'] = 'CNBL';
            $c['tabela'] = 'certificados_cnbl';
            $c['dias_vencimento'] = $dias;
            $certificados_encontrados[] = $c;
        }

        // CNARQ
        $stmt = $pdo->prepare("
            SELECT id, numero, nome_embarcacao, data_validade, status
            FROM certificados_cnarq
            WHERE ativo = 1
              AND status IN ('emitido', 'assinado')
              AND data_validade <= :data_limite
              AND data_validade >= CURDATE()
            ORDER BY data_validade ASC
        ");
        $stmt->execute([':data_limite' => $data_limite]);
        $cnarq = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($cnarq as $c) {
            $c['tipo'] = 'CNARQ';
            $c['tabela'] = 'certificados_cnarq';
            $c['dias_vencimento'] = $dias;
            $certificados_encontrados[] = $c;
        }
    }

    // Remover duplicatas (mesmo certificado pode aparecer para 30 e 7 dias)
    $unicos = [];
    $vistos = [];
    foreach ($certificados_encontrados as $c) {
        $chave = $c['tabela'] . ':' . $c['id'];
        // Priorizar o menor período (7 dias tem prioridade sobre 30)
        if (!isset($vistos[$chave]) || $c['dias_vencimento'] < $vistos[$chave]['dias_vencimento']) {
            $vistos[$chave] = $c;
        }
    }
    $certificados_encontrados = array_values($vistos);

    logMensagem("Total de certificados próximos do vencimento: " . count($certificados_encontrados));

    if (empty($certificados_encontrados)) {
        logMensagem("Nenhum certificado com vencimento próximo encontrado. Script finalizado.");
        exit(0);
    }

    // Agrupar por cliente para enviar um e-mail por cliente
    $por_cliente = [];
    foreach ($certificados_encontrados as $cert) {
        // Buscar cliente vinculado à embarcação
        $stmtCli = $pdo->prepare("
            SELECT c.nome AS cliente_nome, c.email AS cliente_email
            FROM clientes c
            INNER JOIN clientes_embarcacoes ce ON ce.cliente_id = c.id AND ce.status='ATIVO'
            INNER JOIN embarcacoes e ON e.id = ce.embarcacao_id
            WHERE e.nome = :emb_nome AND c.status = 'ATIVO'
            LIMIT 1
        ");
        $stmtCli->execute([':emb_nome' => $cert['nome_embarcacao']]);
        $cliente = $stmtCli->fetch(PDO::FETCH_ASSOC);

        if (!$cliente || empty($cliente['cliente_email'])) {
            logMensagem("AVISO: Cliente não encontrado para embarcação '{$cert['nome_embarcacao']}' (certificado {$cert['tipo']} {$cert['numero']})");
            continue;
        }

        $email = $cliente['cliente_email'];
        if (!isset($por_cliente[$email])) {
            $por_cliente[$email] = [
                'cliente_nome' => $cliente['cliente_nome'],
                'cliente_email' => $email,
                'certificados' => [],
            ];
        }

        $por_cliente[$email]['certificados'][] = $cert;
    }

    logMensagem("Clientes a serem notificados: " . count($por_cliente));

    // Enviar e-mails
    foreach ($por_cliente as $email => $dados) {
        try {
            // Montar HTML do e-mail
            $htmlBody = $templateHtml;

            // Determinar classe do alerta
            $dias_minimo = min(array_column($dados['certificados'], 'dias_vencimento'));
            $alerta_class = ($dias_minimo <= 7) ? 'alerta-urgente' : '';
            $alerta_titulo = ($dias_minimo <= 7) ? 'URGENTE: Vencimento em até 7 dias!' : 'Aviso: Vencimento em até 30 dias';
            $alerta_descricao = ($dias_minimo <= 7)
                ? 'Os seguintes certificados vencerão em menos de 7 dias e requerem ação imediata.'
                : 'Os seguintes certificados vencerão nos próximos 30 dias. Recomenda-se iniciar o processo de renovação.';

            // Montar linhas da tabela
            $linhas = '';
            foreach ($dados['certificados'] as $cert) {
                $data_validade = date('d/m/Y', strtotime($cert['data_validade']));
                $linhas .= "<tr>
                    <td>{$cert['tipo']} - {$cert['numero']}</td>
                    <td>{$cert['nome_embarcacao']}</td>
                    <td>{$cert['tipo']}</td>
                    <td><strong>{$data_validade}</strong></td>
                </tr>";
            }

            // Substituir placeholders
            $replacements = [
                '{{NOME_GESTOR}}'         => h($dados['cliente_nome']),
                '{{ALERTA_CLASS}}'        => $alerta_class,
                '{{ALERTA_TITULO}}'       => $alerta_titulo,
                '{{ALERTA_DESCRICAO}}'    => $alerta_descricao,
                '{{LINHAS_CERTIFICADOS}}' => $linhas,
                '{{APP_URL}}'             => APP_URL,
                '{{EMAIL_CONTATO}}'       => EMAIL_CONTATO,
                '{{TELEFONE_CONTATO}}'    => TELEFONE_CONTATO,
            ];
            $htmlBody = str_replace(array_keys($replacements), array_values($replacements), $htmlBody);

            // Enviar e-mail
            $assunto = 'Alerta de Vencimento de Certificados - Amazon Naval';
            $resultado = enviarEmail($dados['cliente_email'], $dados['cliente_nome'], $assunto, $htmlBody);

            // Registrar no log de e-mails (um registro por certificado)
            foreach ($dados['certificados'] as $cert) {
                $stmtLog = $pdo->prepare("
                    INSERT INTO email_logs (id, destinatario, assunto, tipo, referencia_tipo, referencia_id, status, mensagem_erro, enviado_por)
                    VALUES (UUID(), :destinatario, :assunto, 'alerta_vencimento', :referencia_tipo, :referencia_id, :status, :mensagem_erro, :enviado_por)
                ");
                $stmtLog->execute([
                    ':destinatario'    => $dados['cliente_email'],
                    ':assunto'         => $assunto,
                    ':referencia_tipo' => $cert['tabela'],
                    ':referencia_id'   => $cert['id'],
                    ':status'          => $resultado['success'] ? 'enviado' : 'erro',
                    ':mensagem_erro'   => $resultado['success'] ? null : $resultado['message'],
                    ':enviado_por'     => null, // Sistema automático
                ]);
            }

            if ($resultado['success']) {
                $total_enviados++;
                logMensagem("SUCESSO: E-mail enviado para {$dados['cliente_email']} ({$dados['cliente_nome']}) - " . count($dados['certificados']) . " certificado(s)");
            } else {
                $total_erros++;
                logMensagem("ERRO: Falha ao enviar para {$dados['cliente_email']}: {$resultado['message']}");
            }

        } catch (Exception $e) {
            $total_erros++;
            logMensagem("ERRO: Exceção ao enviar para {$dados['cliente_email']}: {$e->getMessage()}");
        }
    }

    logMensagem("=== Fim da execução ===");
    logMensagem("Total enviados: {$total_enviados}");
    logMensagem("Total erros: {$total_erros}");

    // Saída para console (útil para debug)
    echo "Script de alerta de vencimentos executado com sucesso.\n";
    echo "Total de e-mails enviados: {$total_enviados}\n";
    echo "Total de erros: {$total_erros}\n";
    echo "Log: {$log_file}\n";

    exit(0);

} catch (Exception $e) {
    logMensagem("ERRO CRÍTICO: {$e->getMessage()}");
    echo "ERRO CRÍTICO: {$e->getMessage()}\n";
    exit(1);
}
