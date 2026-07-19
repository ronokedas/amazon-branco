-- Migration 011: Tabela certificados_cnarq
-- Fase 4 - Documentação: Certificado CNARQ (Arqueação)
-- Segue o padrão das tabelas certificados_csn e certificados_cnbl existentes
-- CNARQ nao possui convalidacoes. A tabela cert_convalidacoes e exclusiva do CNBL.

-- --------------------------------------------------------
-- Tabela: certificados_cnarq
-- Certificado Nacional de Arqueação
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `certificados_cnarq` (
  `id` char(36) NOT NULL DEFAULT (uuid()),
  `numero` varchar(30) NOT NULL,
  `token_assinatura` char(64) NOT NULL,
  
  -- Dados da embarcação (puxados automaticamente do cadastro)
  `nome_embarcacao` varchar(200) NOT NULL,
  `numero_inscricao` varchar(80) DEFAULT NULL,
  `indicativo_chamada` varchar(80) DEFAULT NULL,
  `tipo_embarcacao` varchar(200) DEFAULT NULL,
  `ano_construcao` varchar(10) DEFAULT NULL,
  `material_casco` varchar(100) DEFAULT NULL,
  `porto_inscricao` varchar(200) DEFAULT NULL,
  `local_construcao` varchar(200) DEFAULT NULL,
  
  -- Dimensões para arqueação
  `comprimento_total` decimal(8,2) DEFAULT NULL,
  `comprimento_casco` decimal(8,2) DEFAULT NULL,
  `comprimento_lpp` decimal(8,2) DEFAULT NULL COMMENT 'Comprimento entre perpendiculares',
  `boca_moldada` decimal(8,2) DEFAULT NULL,
  `boca_maxima` decimal(8,2) DEFAULT NULL,
  `pontal_moldado` decimal(8,2) DEFAULT NULL,
  
  -- Arqueação
  `arqueacao_bruta` decimal(10,2) DEFAULT NULL COMMENT 'Arqueação bruta (AB)',
  `arqueacao_liquida` decimal(10,2) DEFAULT NULL COMMENT 'Arqueação líquida (AL)',
  `metodo_arqueacao` varchar(100) DEFAULT NULL COMMENT 'Método utilizado (NORMAM, Convenção, etc)',
  
  -- Informações do relatório de vistoria
  `relatorio_numero` varchar(100) DEFAULT NULL,
  `data_vistoria` date DEFAULT NULL,
  `local_vistoria` varchar(200) DEFAULT NULL,
  
  -- Dados de emissão e validade
  `data_emissao` date NOT NULL,
  `data_validade` date NOT NULL,
  `local_emissao` varchar(100) DEFAULT 'Belém-PA',
  
  -- Assinatura digital
  `assinante_nome` varchar(200) DEFAULT NULL,
  `assinante_titulo` varchar(200) DEFAULT NULL,
  `assinante_registro` varchar(100) DEFAULT NULL,
  `assinatura_imagem` longtext DEFAULT NULL,
  `assinatura_ip` varchar(45) DEFAULT NULL,
  `assinatura_em` datetime DEFAULT NULL,
  `assinado` tinyint(1) DEFAULT 0,
  
  -- Status e controle
  `status` enum('rascunho','emitido','assinado','cancelado') DEFAULT 'rascunho',
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_por` char(36) DEFAULT NULL,
  `criado_em` datetime DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  
  PRIMARY KEY (`id`),
  KEY `idx_certificados_cnarq_numero` (`numero`),
  KEY `idx_certificados_cnarq_status` (`status`),
  KEY `idx_certificados_cnarq_ativo` (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
