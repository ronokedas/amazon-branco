-- Migration 132: Configurações de E-mail / Servidor SMTP (Brevo / PHPMailer)
-- Armazena credenciais e parâmetros SMTP na tabela configuracoes e suporta tipo 'teste' em email_logs

CREATE TABLE IF NOT EXISTS `configuracoes` (
  `chave` VARCHAR(100) NOT NULL PRIMARY KEY,
  `valor` TEXT NOT NULL,
  `descricao` VARCHAR(255) DEFAULT NULL,
  `atualizado_em` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `email_logs`
  MODIFY COLUMN `tipo` ENUM('proposta','agendamento','certificado','assinatura','alerta_vencimento','portal_acesso','portal_recuperacao_senha','teste') NOT NULL COMMENT 'Tipo de e-mail enviado';

-- Inserir dados padrão fornecidos para o servidor SMTP Brevo
INSERT INTO `configuracoes` (`chave`, `valor`, `descricao`) VALUES
  ('mail_host', 'smtp-relay.brevo.com', 'Servidor SMTP para envio de e-mails'),
  ('mail_port', '587', 'Porta do servidor SMTP (ex.: 587 ou 465)'),
  ('mail_username', 'b157d8001@smtp-brevo.com', 'Usuário ou login de autenticação SMTP'),
  ('mail_password', 'xsmtpsib-782cfe3f1e6ae55475b72b76140451807a16d512c4cfbf6b24266f56f308fe79-bOlCBIQS6gnPNSaO', 'Senha ou chave de API de autenticação SMTP'),
  ('mail_from_name', 'Amazon Naval', 'Nome do remetente exibido nas mensagens'),
  ('mail_encryption', 'tls', 'Criptografia da conexão SMTP (tls, ssl ou none)'),
  ('mail_verify_peer', '0', 'Verificar certificado SSL do servidor SMTP (1=sim, 0=não)'),
  ('email_contato', 'contato@amazonnaval.com.br', 'E-mail do remetente e endereço para resposta (Reply-To)')
ON DUPLICATE KEY UPDATE descricao = VALUES(descricao);
