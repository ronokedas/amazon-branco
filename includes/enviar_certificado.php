<?php
/**
 * ENVIO DE CERTIFICADO POR E-MAIL
 * Função compartilhada para CSN, CNBL e CNARQ
 * 
 * Uso:
 *   require_once __DIR__ . '/../../includes/enviar_certificado.php';
 *   enviarCertificadoEmail($pdo, $certificado_id, $tabela, $tipo_label, $pdf_relative_path);
 */

require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/documento_pdf.php';
require_once __DIR__ . '/documento_destinatario.php';

/**
 * Envia certificado por e-mail, anexando o PDF e registrando no email_logs
 *
 * @param PDO    $pdo               Conexão PDO
 * @param string $certificado_id    UUID do certificado
 * @param string $tabela            Nome da tabela (certificados_csn, certificados_cnbl, certificados_cnarq)
 * @param string $tipo_label        Label do tipo (ex: "CSN", "CNBL", "CNARQ")
 * @param string $pdf_rel_path      Caminho relativo ao PDF (ex: "modules/documentacao/certificados/pdf.php" ou "modules/documentacao/cnbl/pdf.php" ou "modules/documentacao/cnarq/pdf.php")
 * 
 * @return array ['success' => bool, 'message' => string]
 */
function enviarCertificadoEmail(PDO $pdo, string $certificado_id, string $tabela, string $tipo_label, string $pdf_rel_path): array
{
    try {
        $mapas = [
            'certificados_csn' => 'id, numero, nome_embarcacao, data_emissao, data_validade, status, assinado, caminho_arquivo_pdf, hash_arquivo_pdf, NULL AS email_destinatario',
            'certificados_cnbl' => 'id, numero, nome_embarcacao, data_emissao, data_validade, status, assinado, caminho_arquivo_pdf, hash_arquivo_pdf, NULL AS email_destinatario',
            'certificados_cnarq' => 'id, numero, nome_embarcacao, data_emissao, data_validade, status, assinado, caminho_arquivo_pdf, hash_arquivo_pdf, NULL AS email_destinatario',
            'certificados_lc' => 'id, numero_lc AS numero, nome_embarcacao, data_emissao, data_validade, status, assinado, caminho_arquivo_pdf, hash_arquivo_pdf, NULL AS email_destinatario',
            'certificados_lp' => 'id, numero_lp AS numero, nome_embarcacao, data_emissao, validade_data AS data_validade, status, assinado, caminho_arquivo_pdf, hash_arquivo_pdf, NULL AS email_destinatario',
            'certificados_cht' => 'id, COALESCE(numero_certificado, numero_relatorio_ht) AS numero, profissional_empresa AS nome_embarcacao, data_emissao, data_validade, status, assinado, caminho_arquivo_pdf, hash_arquivo_pdf, email_destinatario',
        ];
        if (!isset($mapas[$tabela])) {
            return ['success' => false, 'message' => 'Tipo de documento não suportado para envio.'];
        }

        $campos_comuns = $mapas[$tabela];
        $stmt = $pdo->prepare("SELECT {$campos_comuns} FROM {$tabela} WHERE id = :id AND ativo = 1");
        $stmt->execute([':id' => $certificado_id]);
        $cert = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cert) {
            return ['success' => false, 'message' => 'Certificado não encontrado.'];
        }

        if (empty($cert['assinado']) || $cert['status'] !== 'assinado' || empty($cert['caminho_arquivo_pdf']) || empty($cert['hash_arquivo_pdf'])) {
            return ['success' => false, 'message' => 'Aprove e assine eletronicamente o certificado antes de envia-lo por e-mail.'];
        }
        $pdfFile = __DIR__ . '/../' . ltrim(str_replace(['../', '..\\'], '', (string)$cert['caminho_arquivo_pdf']), '/\\');
        if (!is_file($pdfFile) || !hash_equals((string)$cert['hash_arquivo_pdf'], hash_file('sha256', $pdfFile))) {
            return ['success' => false, 'message' => 'O PDF oficial falhou na verificacao de integridade.'];
        }
        $stmtAudit = $pdo->prepare("SELECT token_validacao FROM documento_aprovacoes WHERE documento_id=:id AND status='APROVADO' ORDER BY versao DESC LIMIT 1");
        $stmtAudit->execute([':id'=>$certificado_id]);
        $tokenValidacao = $stmtAudit->fetchColumn();
        if (!$tokenValidacao) return ['success'=>false, 'message'=>'A auditoria publica do certificado nao foi encontrada.'];

        $cliente = resolverDestinatarioDocumento($pdo, $cert, $tabela);

        if (!$cliente || empty($cliente['cliente_email'])) {
            return ['success' => false, 'message' => 'Cliente não encontrado ou sem e-mail cadastrado para esta embarcação.'];
        }

        // Carregar template
        $templatePath = __DIR__ . '/../templates/email/certificado.html';
        if (!file_exists($templatePath)) {
            return ['success' => false, 'message' => 'Template de e-mail não encontrado.'];
        }
        $htmlBody = file_get_contents($templatePath);

        // Link de assinatura
        $link_assinatura = rtrim(APP_URL, '/') . '/validar/' . $tokenValidacao;

        // Substituir placeholders
        $replacements = [
            '{{TIPO_CERTIFICADO}}'    => "Certificado {$tipo_label}",
            '{{NOME_CLIENTE}}'        => h($cliente['cliente_nome']),
            '{{EMBARCACAO_NOME}}'     => h($cert['nome_embarcacao']),
            '{{NUMERO_CERTIFICADO}}'  => h($cert['numero']),
            '{{DATA_EMISSAO}}'        => date('d/m/Y', strtotime($cert['data_emissao'])),
            '{{DATA_VALIDADE}}'       => !empty($cert['data_validade']) ? date('d/m/Y', strtotime($cert['data_validade'])) : 'Não se aplica',
            '{{LINK_ASSINATURA}}'     => $link_assinatura,
            '{{EMAIL_CONTATO}}'       => EMAIL_CONTATO,
            '{{TELEFONE_CONTATO}}'    => TELEFONE_CONTATO,
        ];
        $htmlBody = str_replace(array_keys($replacements), array_values($replacements), $htmlBody);

        // Gerar PDF internamente, sem depender de APP_URL/localhost.
        $pdfContent = file_get_contents($pdfFile);

        if (!$pdfContent || strlen($pdfContent) < 200) {
            return ['success' => false, 'message' => 'Não foi possível gerar o PDF do certificado.'];
        }

        // Incluir mailer e enviar
        $resultado = enviarEmail(
            $cliente['cliente_email'],
            $cliente['cliente_nome'],
            "{$tipo_label} - {$cert['numero']}",
            $htmlBody,
            [$pdfFile]
        );

        // Registrar no log de e-mails
        $stmtLog = $pdo->prepare("
            INSERT INTO email_logs (id, destinatario, assunto, tipo, referencia_tipo, referencia_id, status, mensagem_erro, enviado_por)
            VALUES (UUID(), :destinatario, :assunto, 'certificado', :referencia_tipo, :referencia_id, :status, :mensagem_erro, :enviado_por)
        ");
        $stmtLog->execute([
            ':destinatario'    => $cliente['cliente_email'],
            ':assunto'         => "{$tipo_label} - {$cert['numero']}",
            ':referencia_tipo' => $tabela,
            ':referencia_id'   => $certificado_id,
            ':status'          => $resultado['success'] ? 'enviado' : 'erro',
            ':mensagem_erro'   => $resultado['success'] ? null : $resultado['message'],
            ':enviado_por'     => $_SESSION['usuario_id'] ?? null,
        ]);

        // Limpar PDF temporário
        return $resultado;

    } catch (Exception $e) {
        error_log('Erro ao enviar certificado por e-mail: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()];
    }
}
