<?php
/**
 * MÓDULO: DOCUMENTOS VENCIDOS
 * Arquivo: actions.php - Processamento de Ações e Alertas para Documentos Vencidos
 * 
 * Normas de Referência: NORMAM-201 / NORMAM-202 / NORMAM-211 (DPC / Marinha do Brasil)
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/mailer.php';

verificar_sessao();

if (!podeAcessar('documentos_vencidos')) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado para esta operação.']);
    exit;
}

$action = trim((string)($_REQUEST['action'] ?? ''));

header('Content-Type: application/json; charset=utf-8');

try {
    switch ($action) {
        case 'buscar_destinatarios':
            $embarcacaoId = trim((string)($_GET['embarcacao_id'] ?? ''));
            $embarcacaoNome = trim((string)($_GET['embarcacao_nome'] ?? ''));
            $clienteId = trim((string)($_GET['cliente_id'] ?? ''));

            $destinatarios = [];
            if (!empty($embarcacaoId) || !empty($embarcacaoNome)) {
                $destinatarios = obterDestinatariosNotificaveisEmbarcacao($pdo, $embarcacaoId ?: null, $embarcacaoNome ?: null);
            } elseif (!empty($clienteId)) {
                $destinatarios = obterDestinatariosNotificaveisCliente($pdo, $clienteId);
            }

            // Normalizar labels
            foreach ($destinatarios as &$dest) {
                $dest['papel_label'] = papelEmailLabel($dest['papel']);
            }
            unset($dest);

            echo json_encode([
                'success' => true,
                'destinatarios' => $destinatarios
            ]);
            exit;

        case 'alertar_cliente_email':
            $docId = trim((string)($_POST['id'] ?? ''));
            $tabela = trim((string)($_POST['tabela_origem'] ?? ''));
            $tipoDoc = strtoupper(trim((string)($_POST['tipo_documento'] ?? '')));
            $embarcacaoId = trim((string)($_POST['embarcacao_id'] ?? ''));
            $embarcacaoNome = trim((string)($_POST['embarcacao_nome'] ?? ''));
            $clienteId = trim((string)($_POST['cliente_id'] ?? ''));
            $emailsEscolhidos = $_POST['emails_selecionados'] ?? [];

            if (empty($docId) || empty($tabela)) {
                echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos para disparo de alerta.']);
                exit;
            }

            // Buscar dados detalhados do documento conforme a tabela
            $doc = null;
            $dataValidade = null;
            $numeroDoc = 'S/N';
            $docNomeCompleto = 'Documento Naval';
            $refNormam = 'NORMAM-201/202';
            $numInscricao = 'N/D';

            if ($tabela === 'vistorias') {
                $stmt = $pdo->prepare("
                    SELECT v.*, 
                           DATE_ADD(v.data_vistoria, INTERVAL v.prazo_exigencias_dias DAY) AS data_vencimento,
                           DATEDIFF(CURDATE(), DATE_ADD(v.data_vistoria, INTERVAL v.prazo_exigencias_dias DAY)) AS dias_vencido,
                           e.nome AS embarcacao_nome,
                           COALESCE(e.numero_inscricao, e.porto_inscricao, 'N/D') AS embarcacao_inscricao
                    FROM vistorias v
                    LEFT JOIN embarcacoes e ON e.id = v.embarcacao_id
                    WHERE v.id = :id
                ");
                $stmt->execute([':id' => $docId]);
                $doc = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($doc) {
                    $numeroDoc = $doc['numero'] ?: 'Vistoria S/N';
                    $dataValidade = $doc['data_vencimento'];
                    $embarcacaoNome = $doc['embarcacao_nome'] ?: $embarcacaoNome;
                    $numInscricao = $doc['embarcacao_inscricao'];
                    $docNomeCompleto = 'Relatório de Vistoria Naval (Prazo de Exigências)';
                    $refNormam = 'NORMAM-202 (Vistorias e Exigências)';
                }
            } elseif ($tabela === 'propostas') {
                $stmt = $pdo->prepare("
                    SELECT p.*,
                           DATEDIFF(CURDATE(), p.data_validade) AS dias_vencido,
                           c.nome AS cliente_nome,
                           c.email AS cliente_email
                    FROM propostas p
                    LEFT JOIN clientes c ON c.id = p.cliente_id
                    WHERE p.id = :id
                ");
                $stmt->execute([':id' => $docId]);
                $doc = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($doc) {
                    $numeroDoc = $doc['numero'];
                    $dataValidade = $doc['data_validade'];
                    $docNomeCompleto = 'Proposta Comercial Naval';
                    $refNormam = 'Comercial / Orçamentário';
                }
            } elseif ($tabela === 'certificados_lp') {
                $stmt = $pdo->prepare("
                    SELECT c.*,
                           DATEDIFF(CURDATE(), c.validade_data) AS dias_vencido,
                           COALESCE(c.nome_embarcacao, e.nome, 'Embarcação') AS embarcacao_nome,
                           COALESCE(e.numero_inscricao, e.porto_inscricao, 'N/D') AS embarcacao_inscricao
                    FROM certificados_lp c
                    LEFT JOIN embarcacoes e ON e.id = c.embarcacao_id
                    WHERE c.id = :id
                ");
                $stmt->execute([':id' => $docId]);
                $doc = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($doc) {
                    $numeroDoc = $doc['numero_lp'] ?: 'S/N';
                    $dataValidade = $doc['validade_data'];
                    $embarcacaoNome = $doc['embarcacao_nome'];
                    $numInscricao = $doc['embarcacao_inscricao'];
                    $docNomeCompleto = 'Licença Provisória (LP)';
                    $refNormam = 'NORMAM-201/202';
                }
            } elseif ($tabela === 'certificados_lc') {
                $stmt = $pdo->prepare("
                    SELECT c.*,
                           DATEDIFF(CURDATE(), c.data_validade) AS dias_vencido,
                           COALESCE(c.nome_embarcacao, e.nome, 'Embarcação') AS embarcacao_nome,
                           COALESCE(e.numero_inscricao, e.porto_inscricao, 'N/D') AS embarcacao_inscricao
                    FROM certificados_lc c
                    LEFT JOIN embarcacoes e ON e.id = c.embarcacao_id
                    WHERE c.id = :id
                ");
                $stmt->execute([':id' => $docId]);
                $doc = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($doc) {
                    $numeroDoc = $doc['numero_lc'] ?: 'S/N';
                    $dataValidade = $doc['data_validade'];
                    $embarcacaoNome = $doc['embarcacao_nome'];
                    $numInscricao = $doc['embarcacao_inscricao'];
                    $docNomeCompleto = 'Licença de Construção (LC)';
                    $refNormam = 'NORMAM-201/202';
                }
            } else {
                // Demais certificados: CSN, CNBL, CNARQ, CHT
                $nomeCampoNumero = ($tabela === 'certificados_cht') ? 'numero_certificado' : 'numero';
                $stmt = $pdo->prepare("
                    SELECT c.*,
                           DATEDIFF(CURDATE(), c.data_validade) AS dias_vencido,
                           COALESCE(NULLIF(c.nome_embarcacao, ''), e.nome, 'Embarcação') AS embarcacao_nome,
                           COALESCE(e.numero_inscricao, e.porto_inscricao, 'N/D') AS embarcacao_inscricao
                    FROM `{$tabela}` c
                    LEFT JOIN embarcacoes e ON e.id = c.embarcacao_id
                    WHERE c.id = :id
                ");
                $stmt->execute([':id' => $docId]);
                $doc = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($doc) {
                    $numeroDoc = $doc[$nomeCampoNumero] ?: 'S/N';
                    $dataValidade = $doc['data_validade'];
                    $embarcacaoNome = $doc['embarcacao_nome'] ?: $embarcacaoNome;
                    $numInscricao = $doc['embarcacao_inscricao'];
                    $nomesMap = [
                        'certificados_csn' => 'Certificado de Segurança da Navegação (CSN)',
                        'certificados_cnbl' => 'Certificado Nacional de Borda Livre (CNBL)',
                        'certificados_cnarq' => 'Certificado Nacional de Arqueação (CNARQ)',
                        'certificados_cht' => 'Certificado de Habilitação Técnica (CHT)',
                    ];
                    $docNomeCompleto = $nomesMap[$tabela] ?? $tipoDoc;
                    $refNormam = 'NORMAM-201/202 (DPC)';
                }
            }

            if (!$doc) {
                echo json_encode(['success' => false, 'message' => 'Documento não localizado no banco de dados.']);
                exit;
            }

            $diasVencido = max(1, (int)($doc['dias_vencido'] ?? 1));
            $dataValidadeFmt = !empty($dataValidade) ? date('d/m/Y', strtotime($dataValidade)) : 'Expirada';

            // Destinatários
            $destinatarios = [];
            if (!empty($embarcacaoId) || !empty($embarcacaoNome)) {
                $destinatarios = obterDestinatariosNotificaveisEmbarcacao($pdo, $embarcacaoId ?: null, $embarcacaoNome ?: null);
            } elseif (!empty($clienteId)) {
                $destinatarios = obterDestinatariosNotificaveisCliente($pdo, $clienteId);
            }

            // Filtrar se o usuário selecionou apenas alguns emails específicos
            if (!empty($emailsEscolhidos) && is_array($emailsEscolhidos)) {
                $emailsEscolhidosLower = array_map('strtolower', array_map('trim', $emailsEscolhidos));
                $destinatarios = array_filter($destinatarios, function($d) use ($emailsEscolhidosLower) {
                    return in_array(strtolower(trim($d['email'])), $emailsEscolhidosLower, true);
                });
            }

            if (empty($destinatarios)) {
                echo json_encode(['success' => false, 'message' => 'Nenhum destinatário com e-mail válido selecionado para o alerta.']);
                exit;
            }

            // Template de email
            $templatePath = __DIR__ . '/../../templates/email/documento_vencido.html';
            $templateHtml = file_exists($templatePath) ? file_get_contents($templatePath) : '';
            if (empty($templateHtml)) {
                echo json_encode(['success' => false, 'message' => 'Template de e-mail não encontrado.']);
                exit;
            }

            $totalEnviados = 0;
            $totalErros = 0;
            $errosMensagens = [];
            $assunto = "ALERTA URGENTE: {$tipoDoc} Vencido - {$embarcacaoNome}";

            $usuarioLogado = $_SESSION['usuario_id'] ?? null;

            foreach ($destinatarios as $dest) {
                $email = strtolower(trim($dest['email']));
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }

                $replacements = [
                    '{{NOME_DESTINATARIO}}'  => h($dest['nome']),
                    '{{PAPEL_DESTINATARIO}}' => papelEmailLabel($dest['papel']),
                    '{{TIPO_DOCUMENTO}}'     => h($tipoDoc),
                    '{{DOCUMENTO_NOME}}'     => h($docNomeCompleto),
                    '{{NUMERO_DOCUMENTO}}'   => h($numeroDoc),
                    '{{EMBARCACAO_NOME}}'    => h($embarcacaoNome ?: 'Não informada'),
                    '{{NUMERO_INSCRICAO}}'   => h($numInscricao),
                    '{{REFERENCIA_NORMAM}}'  => h($refNormam),
                    '{{DATA_VALIDADE}}'      => $dataValidadeFmt,
                    '{{DIAS_VENCIDO}}'       => (string)$diasVencido,
                    '{{EMAIL_CONTATO}}'      => EMAIL_CONTATO,
                    '{{TELEFONE_CONTATO}}'   => TELEFONE_CONTATO,
                ];

                $corpoEmail = str_replace(array_keys($replacements), array_values($replacements), $templateHtml);
                $res = enviarEmail($email, $dest['nome'], $assunto, $corpoEmail);

                // Gravar log de envio
                $stmtLog = $pdo->prepare("
                    INSERT INTO email_logs (id, destinatario, assunto, tipo, referencia_tipo, referencia_id, status, mensagem_erro, enviado_por)
                    VALUES (UUID(), :destinatario, :assunto, 'alerta_vencimento', :referencia_tipo, :referencia_id, :status, :mensagem_erro, :enviado_por)
                ");
                $stmtLog->execute([
                    ':destinatario'    => $email,
                    ':assunto'         => $assunto,
                    ':referencia_tipo' => $tabela,
                    ':referencia_id'   => $docId,
                    ':status'          => $res['success'] ? 'enviado' : 'erro',
                    ':mensagem_erro'   => $res['success'] ? null : ($res['message'] ?? 'Falha de envio'),
                    ':enviado_por'     => $usuarioLogado,
                ]);

                if ($res['success']) {
                    $totalEnviados++;
                } else {
                    $totalErros++;
                    $errosMensagens[] = "{$email}: " . ($res['message'] ?? 'Erro desconhecido');
                }
            }

            if ($totalEnviados > 0) {
                echo json_encode([
                    'success' => true,
                    'enviados' => $totalEnviados,
                    'erros' => $totalErros,
                    'message' => "Alerta de documento vencido enviado com sucesso para {$totalEnviados} destinatário(s)!" . ($totalErros > 0 ? " ({$totalErros} falhas)." : "")
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Falha ao enviar alertas: ' . implode('; ', $errosMensagens)
                ]);
            }
            exit;

        default:
            echo json_encode(['success' => false, 'message' => 'Ação não reconhecida.']);
            exit;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
    exit;
}
