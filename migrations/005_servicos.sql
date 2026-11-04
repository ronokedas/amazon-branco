-- Migration 005: Criar tabela servicos com preços cadastráveis
-- Fase 2 - passo 4: módulo comercial/servicos
-- Data: 2026-06-23

CREATE TABLE IF NOT EXISTS `servicos` (
  `id` char(36) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `descricao` text DEFAULT NULL,
  `preco_padrao` decimal(12,2) NOT NULL DEFAULT 0.00,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_por` char(36) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  INDEX `idx_servicos_ativo` (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Seed: 11 serviços padrão do catálogo Amazon Naval (protegido contra duplicação)
INSERT INTO `servicos` (`id`, `nome`, `descricao`, `preco_padrao`, `ativo`, `created_at`)
SELECT UUID(), 'Análise de Planos Ec1', 'Análise técnica de planos de embarcação – Etapa 1', 0.00, 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM `servicos` WHERE `nome` = 'Análise de Planos Ec1');

INSERT INTO `servicos` (`id`, `nome`, `descricao`, `preco_padrao`, `ativo`, `created_at`)
SELECT UUID(), 'Análise de Planos Ec2', 'Análise técnica de planos de embarcação – Etapa 2', 0.00, 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM `servicos` WHERE `nome` = 'Análise de Planos Ec2');

INSERT INTO `servicos` (`id`, `nome`, `descricao`, `preco_padrao`, `ativo`, `created_at`)
SELECT UUID(), 'Vistoria Inicial Seco', 'Vistoria inicial realizada com embarcação em seco (estaleiro/dique)', 0.00, 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM `servicos` WHERE `nome` = 'Vistoria Inicial Seco');

INSERT INTO `servicos` (`id`, `nome`, `descricao`, `preco_padrao`, `ativo`, `created_at`)
SELECT UUID(), 'Vistoria Inicial Flutuando', 'Vistoria inicial realizada com embarcação flutuando', 0.00, 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM `servicos` WHERE `nome` = 'Vistoria Inicial Flutuando');

INSERT INTO `servicos` (`id`, `nome`, `descricao`, `preco_padrao`, `ativo`, `created_at`)
SELECT UUID(), 'Vistoria Inicial de Borda Livre', 'Vistoria inicial para certificação de borda livre', 0.00, 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM `servicos` WHERE `nome` = 'Vistoria Inicial de Borda Livre');

INSERT INTO `servicos` (`id`, `nome`, `descricao`, `preco_padrao`, `ativo`, `created_at`)
SELECT UUID(), 'Vistoria Inicial de Arqueação', 'Vistoria inicial para cálculo e certificação de arqueação bruta', 0.00, 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM `servicos` WHERE `nome` = 'Vistoria Inicial de Arqueação');

INSERT INTO `servicos` (`id`, `nome`, `descricao`, `preco_padrao`, `ativo`, `created_at`)
SELECT UUID(), 'Acompanhamento de Ultrassom', 'Acompanhamento técnico de ensaios de ultrassom em casco/estruturas', 0.00, 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM `servicos` WHERE `nome` = 'Acompanhamento de Ultrassom');

INSERT INTO `servicos` (`id`, `nome`, `descricao`, `preco_padrao`, `ativo`, `created_at`)
SELECT UUID(), 'Vistoria Anual', 'Vistoria anual obrigatória para manutenção de certificados', 0.00, 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM `servicos` WHERE `nome` = 'Vistoria Anual');

INSERT INTO `servicos` (`id`, `nome`, `descricao`, `preco_padrao`, `ativo`, `created_at`)
SELECT UUID(), 'Vistoria Anual Periódica', 'Vistoria anual periódica conforme regulamento da Capitania', 0.00, 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM `servicos` WHERE `nome` = 'Vistoria Anual Periódica');

INSERT INTO `servicos` (`id`, `nome`, `descricao`, `preco_padrao`, `ativo`, `created_at`)
SELECT UUID(), 'Vistoria Intermediária', 'Vistoria intermediária de meio-ciclo entre renovações', 0.00, 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM `servicos` WHERE `nome` = 'Vistoria Intermediária');

INSERT INTO `servicos` (`id`, `nome`, `descricao`, `preco_padrao`, `ativo`, `created_at`)
SELECT UUID(), 'Licença Provisória', 'Emissão de licença provisória para navegação', 0.00, 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM `servicos` WHERE `nome` = 'Licença Provisória');
