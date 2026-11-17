<?php
/**
 * SCRIPT DE ALERTA DE VENCIMENTOS NAVAIS E VISTORIAS
 * 
 * 1. Certificados CSN, CNBL, CNARQ com vencimento em 30 e 7 dias
 *    (Dispara para todos os contatos ativos vinculados: proprietário, despachante, etc.)
 * 2. Relatórios de Vistoria com vencimento de prazo de exigências em até 10 dias
 *    (Dispara para todos os contatos da embarcação + cópia para o Vistoriador responsável)
 * 
 * Uso via cron (diário às 08h):
 *   php c:\sistema\scripts\alerta_vencimentos.php
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
    $total_enviados = 0;
    $total_erros = 0;

    // =========================================================================
    // BLOCO 1: CERTIFICADOS (CSN, CNBL, CNARQ) COM VENCIMENTO EM 30 E 7 DIAS
    // =========================================================================
    $periodos = [30, 7];
    $templateCertPath = __DIR__ . '/../templates/email/vencimento.html';
    $templateCertHtml = file_exists($templateCertPath) ? file_get_contents($templateCertPath) : '';

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
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $c) {
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
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $c) {
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
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $c) {
            $c['tipo'] = 'CNARQ';
            $c['tabela'] = 'certificados_cnarq';
            $c['dias_vencimento'] = $dias;
            $certificados_encontrados[] = $c;
        }
    }

    // Deduplicação de certificados (menor prazo tem precedência)
    $vistos = [];
    foreach ($certificados_encontrados as $c) {
        $chave = $c['tabela'] . ':' . $c['id'];
        if (!isset($vistos[$chave]) || $c['dias_vencimento'] < $vistos[$chave]['dias_vencimento']) {
            $vistos[$chave] = $c;
        }
    }
    $certificados_encontrados = array_values($vistos);
    logMensagem("Total de certificados com vencimento em 30 ou 7 dias: " . count($certificados_encontrados));

    if (!empty($certificados_encontrados) && !empty($templateCertHtml)) {
        // Agrupar certificados por destinatário
        $por_destinatario = [];
        foreach ($certificados_encontrados as $cert) {
            // Obter todos os e-mails notificáveis vinculados à embarcação (proprietário + adicionais)
            $destinatarios = obterDestinatariosNotificaveisEmbarcacao($pdo, null, $cert['nome_embarcacao']);

            if (empty($destinatarios)) {
                logMensagem("AVISO: Nenhum e-mail de contato encontrado para a embarcação '{$cert['nome_embarcacao']}' ({$cert['tipo']} {$cert['numero']})");
                continue;
            }

            foreach ($destinatarios as $dest) {
                $email = strtolower(trim($dest['email']));
                if (!isset($por_destinatario[$email])) {
                    $por_destinatario[$email] = [
                        'nome' => $dest['nome'],
                        'papel' => $dest['papel'],
                        'email' => $email,
                        'certificados' => [],
                    ];
                }
                $por_destinatario[$email]['certificados'][] = $cert;
            }
        }

        foreach ($por_destinatario as $email => $dados) {
            try {
                $dias_minimo = min(array_column($dados['certificados'], 'dias_vencimento'));
                $alerta_class = ($dias_minimo <= 7) ? 'alerta-urgente' : '';
                $alerta_titulo = ($dias_minimo <= 7) ? 'URGENTE: Vencimento em até 7 dias!' : 'Aviso: Vencimento em até 30 dias';
                $alerta_descricao = ($dias_minimo <= 7)
                    ? 'Os seguintes certificados vencerão em menos de 7 dias e requerem ação imediata.'
                    : 'Os seguintes certificados vencerão nos próximos 30 dias. Recomenda-se iniciar o processo de renovação.';

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

                $replacements = [
                    '{{NOME_GESTOR}}'         => h($dados['nome'] . ($dados['papel'] !== 'PROPRIETARIO' ? ' (' . papelEmailLabel($dados['papel']) . ')' : '')),
                    '{{ALERTA_CLASS}}'        => $alerta_class,
                    '{{ALERTA_TITULO}}'       => $alerta_titulo,
                    '{{ALERTA_DESCRICAO}}'    => $alerta_descricao,
                    '{{LINHAS_CERTIFICADOS}}' => $linhas,
                    '{{APP_URL}}'             => APP_URL,
                    '{{EMAIL_CONTATO}}'       => EMAIL_CONTATO,
                    '{{TELEFONE_CONTATO}}'    => TELEFONE_CONTATO,
                ];
                $htmlBody = str_replace(array_keys($replacements), array_values($replacements), $templateCertHtml);

                $assunto = 'Alerta de Vencimento de Certificados - Amazon Naval';
                $resultado = enviarEmail($dados['email'], $dados['nome'], $assunto, $htmlBody);

                foreach ($dados['certificados'] as $cert) {
                    $stmtLog = $pdo->prepare("
                        INSERT INTO email_logs (id, destinatario, assunto, tipo, referencia_tipo, referencia_id, status, mensagem_erro, enviado_por)
                        VALUES (UUID(), :destinatario, :assunto, 'alerta_vencimento', :referencia_tipo, :referencia_id, :status, :mensagem_erro, NULL)
                    ");
                    $stmtLog->execute([
                        ':destinatario'    => $dados['email'],
                        ':assunto'         => $assunto,
                        ':referencia_tipo' => $cert['tabela'],
                        ':referencia_id'   => $cert['id'],
                        ':status'          => $resultado['success'] ? 'enviado' : 'erro',
                        ':mensagem_erro'   => $resultado['success'] ? null : $resultado['message'],
                    ]);
                }

                if ($resultado['success']) {
                    $total_enviados++;
                    logMensagem("SUCESSO: E-mail de certificado enviado para {$dados['email']} ({$dados['nome']}) - " . count($dados['certificados']) . " certificado(s)");
                } else {
                    $total_erros++;
                    logMensagem("ERRO: Falha ao enviar certificado para {$dados['email']}: {$resultado['message']}");
                }
            } catch (Exception $e) {
                $total_erros++;
                logMensagem("ERRO: Exceção ao enviar certificado para {$dados['email']}: {$e->getMessage()}");
            }
        }
    }

    // =========================================================================
    // BLOCO 2: RELATÓRIOS DE VISTORIA VENCENDO EM ATÉ 10 DIAS (PRAZO EXIGÊNCIAS)
    // =========================================================================
    logMensagem("--- Verificando relatórios de vistoria com vencimento em até 10 dias ---");
    $templateVistoriaPath = __DIR__ . '/../templates/email/vencimento_relatorio_vistoria.html';
    $templateVistoriaHtml = file_exists($templateVistoriaPath) ? file_get_contents($templateVistoriaPath) : '';

    if (!empty($templateVistoriaHtml)) {
        // Query de relatórios de vistoria que possuem prazo de exigências e vencimento em <= 10 dias
        $stmtVistorias = $pdo->prepare("
            SELECT 
                v.id,
                v.numero,
                v.data_vistoria,
                v.prazo_exigencias_dias,
                v.status,
                v.embarcacao_id,
                DATE_ADD(v.data_vistoria, INTERVAL v.prazo_exigencias_dias DAY) AS data_vencimento,
                DATEDIFF(DATE_ADD(v.data_vistoria, INTERVAL v.prazo_exigencias_dias DAY), CURDATE()) AS dias_restantes,
                e.nome AS embarcacao_nome,
                COALESCE(e.numero_inscricao, e.porto_inscricao, 'N/D') AS embarcacao_inscricao,
                COALESCE(u_vist.nome, u_cria.nome, 'Vistoriador Naval') AS vistoriador_nome,
                COALESCE(u_vist.email, u_cria.email) AS vistoriador_email
            FROM vistorias v
            JOIN embarcacoes e ON e.id = v.embarcacao_id
            LEFT JOIN agendamentos a ON a.id = v.agendamento_id
            LEFT JOIN usuarios u_vist ON u_vist.id = a.vistoriador_id
            LEFT JOIN usuarios u_cria ON u_cria.id = v.criado_por
            WHERE v.status NOT IN ('CANCELADA', 'REPROVADA')
              AND v.prazo_exigencias_dias IS NOT NULL
              AND v.prazo_exigencias_dias > 0
              AND DATEDIFF(DATE_ADD(v.data_vistoria, INTERVAL v.prazo_exigencias_dias DAY), CURDATE()) BETWEEN 0 AND 10
            ORDER BY dias_restantes ASC
        ");
        $stmtVistorias->execute();
        $vistorias_proximas = $stmtVistorias->fetchAll(PDO::FETCH_ASSOC);

        logMensagem("Total de vistorias com prazo expirando em até 10 dias: " . count($vistorias_proximas));

        foreach ($vistorias_proximas as $vist) {
            $vistoriaId = $vist['id'];
            $numeroVistoria = $vist['numero'] ?: 'Vistoria S/N';
            $embarcacaoNome = $vist['embarcacao_nome'];
            $diasRestantes = (int)$vist['dias_restantes'];
            $dataVencFormatada = date('d/m/Y', strtotime($vist['data_vencimento']));
            $dataVistFormatada = date('d/m/Y', strtotime($vist['data_vistoria']));

            // Evitar spam: verificar se já foi enviado alerta nos últimos 4 dias para este relatório
            $stmtCheckLog = $pdo->prepare("
                SELECT COUNT(*) FROM email_logs
                WHERE referencia_tipo = 'vistorias'
                  AND referencia_id = :id
                  AND status = 'enviado'
                  AND created_at >= DATE_SUB(NOW(), INTERVAL 4 DAY)
            ");
            $stmtCheckLog->execute([':id' => $vistoriaId]);
            if ($stmtCheckLog->fetchColumn() > 0) {
                logMensagem("Vistoria {$numeroVistoria} ({$embarcacaoNome}): Alerta já enviado recentemente. Ignorando hoje.");
                continue;
            }

            // Destinatários: Todos os e-mails da embarcação (proprietário + adicionais)
            $destinatarios = obterDestinatariosNotificaveisEmbarcacao($pdo, $vist['embarcacao_id'], $embarcacaoNome);

            // Adicionar também o Vistoriador responsável no envio do alerta
            if (!empty($vist['vistoriador_email'])) {
                $destinatarios[] = [
                    'email' => $vist['vistoriador_email'],
                    'nome' => $vist['vistoriador_nome'],
                    'papel' => 'VISTORIADOR',
                    'recebe_alertas' => 1
                ];
            }

            // Deduplicar e-mails para esta vistoria
            $destinatariosUnicos = [];
            foreach ($destinatarios as $dest) {
                $emailKey = strtolower(trim($dest['email']));
                if (!empty($emailKey) && filter_var($emailKey, FILTER_VALIDATE_EMAIL)) {
                    $destinatariosUnicos[$emailKey] = $dest;
                }
            }

            if (empty($destinatariosUnicos)) {
                logMensagem("AVISO: Nenhum e-mail válido para notificar da vistoria {$numeroVistoria} ({$embarcacaoNome})");
                continue;
            }

            $assunto = "ALERTA: Vencimento de Vistoria em {$diasRestantes} dia(s) - {$embarcacaoNome}";

            foreach ($destinatariosUnicos as $dest) {
                try {
                    $replacements = [
                        '{{NOME_DESTINATARIO}}'  => h($dest['nome']),
                        '{{PAPEL_DESTINATARIO}}' => papelEmailLabel($dest['papel']),
                        '{{EMBARCACAO_NOME}}'    => h($embarcacaoNome),
                        '{{NUMERO_INSCRICAO}}'   => h($vist['embarcacao_inscricao']),
                        '{{NUMERO_VISTORIA}}'    => h($numeroVistoria),
                        '{{VISTORIADOR_NOME}}'   => h($vist['vistoriador_nome']),
                        '{{DATA_VISTORIA}}'      => $dataVistFormatada,
                        '{{PRAZO_DIAS}}'         => (string)$vist['prazo_exigencias_dias'],
                        '{{DATA_VENCIMENTO}}'    => $dataVencFormatada,
                        '{{DIAS_RESTANTES}}'     => (string)$diasRestantes,
                        '{{EMAIL_CONTATO}}'      => EMAIL_CONTATO,
                        '{{TELEFONE_CONTATO}}'   => TELEFONE_CONTATO,
                    ];
                    $htmlVistoria = str_replace(array_keys($replacements), array_values($replacements), $templateVistoriaHtml);

                    $resultado = enviarEmail($dest['email'], $dest['nome'], $assunto, $htmlVistoria);

                    $stmtLog = $pdo->prepare("
                        INSERT INTO email_logs (id, destinatario, assunto, tipo, referencia_tipo, referencia_id, status, mensagem_erro, enviado_por)
                        VALUES (UUID(), :destinatario, :assunto, 'alerta_vencimento', 'vistorias', :referencia_id, :status, :mensagem_erro, NULL)
                    ");
                    $stmtLog->execute([
                        ':destinatario'    => $dest['email'],
                        ':assunto'         => $assunto,
                        ':referencia_id'   => $vistoriaId,
                        ':status'          => $resultado['success'] ? 'enviado' : 'erro',
                        ':mensagem_erro'   => $resultado['success'] ? null : $resultado['message'],
                    ]);

                    if ($resultado['success']) {
                        $total_enviados++;
                        logMensagem("SUCESSO: Alerta de vistoria enviado para {$dest['email']} ({$dest['nome']} - {$dest['papel']}) da embarcação {$embarcacaoNome}");
                    } else {
                        $total_erros++;
                        logMensagem("ERRO: Falha ao enviar alerta de vistoria para {$dest['email']}: {$resultado['message']}");
                    }
                } catch (Exception $e) {
                    $total_erros++;
                    logMensagem("ERRO: Exceção ao enviar alerta de vistoria para {$dest['email']}: {$e->getMessage()}");
                }
            }
        }
    }

    logMensagem("=== Fim da execução ===");
    logMensagem("Total enviados: {$total_enviados}");
    logMensagem("Total erros: {$total_erros}");

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
