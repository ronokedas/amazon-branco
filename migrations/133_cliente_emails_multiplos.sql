-- Migration 133: Suporte a múltiplos e-mails no cadastro de clientes/proprietários (Despachante, Locatário, Projetista, etc.)
-- Mantém compatibilidade total com clientes.email (e-mail principal / login portal)

CREATE TABLE IF NOT EXISTS `cliente_emails` (
  `id` CHAR(36) NOT NULL DEFAULT (UUID()),
  `cliente_id` CHAR(36) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `papel` ENUM('DESPACHANTE','LOCATARIO','PROJETISTA','FINANCEIRO','OPERACIONAL','OUTRO') NOT NULL DEFAULT 'OUTRO',
  `nome_contato` VARCHAR(150) DEFAULT NULL,
  `recebe_alertas` TINYINT(1) NOT NULL DEFAULT 1,
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cli_emails_cliente` (`cliente_id`),
  KEY `idx_cli_emails_alertas` (`recebe_alertas`),
  CONSTRAINT `fk_cli_emails_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
