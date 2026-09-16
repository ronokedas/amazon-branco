-- Migração 110: Garantir Tabelas e Colunas do Gerenciador NORMAM-202 na VPS
-- Corrige incompatibilidade em bancos da VPS onde as colunas obrigatoria/exige_foto ainda não existiam.

CREATE TABLE IF NOT EXISTS `exigencias_categorias` (
  `id` char(36) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` text,
  `ativo` tinyint(1) DEFAULT '1',
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `exigencias_categorias` (`id`, `nome`) VALUES 
('65bf89f0-f44d-4746-89f7-f530c9aa990d', 'Praça de Máquinas'),
('71c05e83-0d67-4137-b2b7-478c4241a057', 'Casco, Estrutura e Porão'),
('9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'Habitabilidade e Cozinha'),
('9e81f468-422b-40e4-8bf8-40b60a027a36', 'Sistemas de Propulsão e Governo'),
('a5f25230-91c9-4e14-aa33-e83524d5d943', 'Combate a Incêndio'),
('aa4a7f0d-004d-4a60-924e-693335fdd69b', 'Documentação e Certificados'),
('b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d', 'Salvatagem e Segurança'),
('b8ed9a31-9fa3-492f-904e-b8158a06d0da', 'Setor Elétrico'),
('e70f7906-4e9d-4367-b10a-2ad2a007817a', 'Sistemas de Navegação e Comando'),
('f299c8c7-4402-4efa-89c6-d5add1fa60d5', 'Rádio e Comunicações');

CREATE TABLE IF NOT EXISTS `exigencias_catalogo` (
  `id` char(36) NOT NULL DEFAULT (uuid()),
  `codigo_interno` varchar(50) DEFAULT NULL,
  `categoria_id` char(36) DEFAULT NULL,
  `descricao` text NOT NULL,
  `item_normam` varchar(200) DEFAULT NULL,
  `bloco_vistoria` enum('seco','flutuando','borda_livre','arqueacao') DEFAULT NULL,
  `tipo_vistoria` enum('seco','flutuando','borda_livre','arqueacao') DEFAULT NULL,
  `prazo_padrao_dias` int DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `obrigatoria` tinyint(1) NOT NULL DEFAULT '0',
  `exige_foto` tinyint(1) NOT NULL DEFAULT '0',
  `ordem_exibicao` int NOT NULL DEFAULT '0',
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_exigencias_codigo_interno` (`codigo_interno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `exigencias_catalogo` ADD COLUMN IF NOT EXISTS `obrigatoria` TINYINT(1) NOT NULL DEFAULT 0 AFTER `ativo`;
ALTER TABLE `exigencias_catalogo` ADD COLUMN IF NOT EXISTS `exige_foto` TINYINT(1) NOT NULL DEFAULT 0 AFTER `obrigatoria`;
ALTER TABLE `exigencias_catalogo` ADD COLUMN IF NOT EXISTS `ordem_exibicao` INT NOT NULL DEFAULT 0 AFTER `exige_foto`;

-- Configurar exigências mestras
UPDATE `exigencias_catalogo` 
SET `obrigatoria` = 1, `exige_foto` = 1 
WHERE `descricao` LIKE '%coletes salva vidas%'
   OR `descricao` LIKE '%coletes salva-vidas%'
   OR `descricao` LIKE '%boias salva vidas%'
   OR `descricao` LIKE '%boias salva-vidas%'
   OR `descricao` LIKE '%bombas de inc%'
   OR `descricao` LIKE '%bomba de inc%'
   OR `descricao` LIKE '%extintor%'
   OR `descricao` LIKE '%luzes de navega%'
   OR `descricao` LIKE '%escala de calado%'
   OR `descricao` LIKE '%governo de emerg%'
   OR `descricao` LIKE '%leme%'
   OR `descricao` LIKE '%plimsoll%'
   OR `descricao` LIKE '%borda livre%'
   OR `descricao` LIKE '%esgoto%'
   OR `descricao` LIKE '%bandejas coletoras%'
   OR `descricao` LIKE '%lota%passageiros%';
