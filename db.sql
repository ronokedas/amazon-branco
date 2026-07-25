-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: db:3306
-- Tempo de geração: 20/07/2026 às 15:58
-- Versão do servidor: 8.0.46
-- Versão do PHP: 8.2.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `erp_sistema`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `agendamentos`
--

CREATE TABLE `agendamentos` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT (uuid()),
  `proposta_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `relatorio_origem_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `embarcacao_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `cliente_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `armador_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `operador_nome` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `vistoriador_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `vendedor_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tipo_vistoria` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `data_vistoria` date DEFAULT NULL,
  `hora_vistoria` time DEFAULT NULL,
  `local` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `contato_nome` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `contato_telefone` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('pendente','confirmado','em_andamento','concluido','cancelado') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pendente',
  `observacoes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `criado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `analises_planos`
--

CREATE TABLE `analises_planos` (
  `id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `numero` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `embarcacao_id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `solicitante_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tipo_processo` enum('LC','LCEC','LA','LR','OUTRO') COLLATE utf8mb4_general_ci NOT NULL,
  `enquadramento` enum('NORMAM-201','NORMAM-202','OUTRO') COLLATE utf8mb4_general_ci NOT NULL,
  `objeto` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `estaleiro` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `numero_casco` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `responsavel_projeto_nome` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `responsavel_projeto_registro` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `art_numero` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `analista_id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `responsavel_assinatura_id` int DEFAULT NULL,
  `status` enum('RASCUNHO','EM_ANALISE','AGUARDANDO_CORRECAO','AGUARDANDO_APROVACAO','CONCLUIDA','REPROVADA','CANCELADA') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'RASCUNHO',
  `observacoes` text COLLATE utf8mb4_general_ci,
  `criado_por` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `analise_planos_arquivos`
--

CREATE TABLE `analise_planos_arquivos` (
  `id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `submissao_id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `categoria` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `nome_original` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `extensao` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `mime_type` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `tamanho_bytes` bigint UNSIGNED NOT NULL,
  `sha256` char(64) COLLATE utf8mb4_general_ci NOT NULL,
  `chave_arquivo` varchar(500) COLLATE utf8mb4_general_ci NOT NULL,
  `criado_por` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `analise_planos_exigencias`
--

CREATE TABLE `analise_planos_exigencias` (
  `id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `analise_id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `item_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ordem` int UNSIGNED NOT NULL,
  `descricao` text COLLATE utf8mb4_general_ci NOT NULL,
  `referencia_normativa` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `prazo` date DEFAULT NULL,
  `status` enum('PENDENTE','CUMPRIDA','PARCIAL','TRANSCRITA') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'PENDENTE',
  `observacao_cumprimento` text COLLATE utf8mb4_general_ci,
  `criado_por` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `analise_planos_historico`
--

CREATE TABLE `analise_planos_historico` (
  `id` bigint UNSIGNED NOT NULL,
  `analise_id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `usuario_id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `evento` varchar(60) COLLATE utf8mb4_general_ci NOT NULL,
  `status_anterior` varchar(40) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status_novo` varchar(40) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `detalhe` text COLLATE utf8mb4_general_ci,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `analise_planos_itens`
--

CREATE TABLE `analise_planos_itens` (
  `id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `analise_id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `submissao_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ordem` int UNSIGNED NOT NULL,
  `documento` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `revisao_documento` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `referencia_normativa` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `resultado` enum('PENDENTE','CONFORME','EXIGENCIA','NAO_APLICA') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'PENDENTE',
  `observacao` text COLLATE utf8mb4_general_ci,
  `criado_por` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `analise_planos_pareceres`
--

CREATE TABLE `analise_planos_pareceres` (
  `id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `analise_id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `versao` int UNSIGNED NOT NULL,
  `resultado` enum('EXIGENCIAS','APROVADO','APROVADO_COM_EXIGENCIAS','REPROVADO') COLLATE utf8mb4_general_ci NOT NULL,
  `resumo` text COLLATE utf8mb4_general_ci NOT NULL,
  `conclusao` text COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('MINUTA','AGUARDANDO_APROVACAO','PUBLICADO','CANCELADO') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'MINUTA',
  `responsavel_assinatura_id` int DEFAULT NULL,
  `publicado_em` datetime DEFAULT NULL,
  `criado_por` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `analise_planos_submissoes`
--

CREATE TABLE `analise_planos_submissoes` (
  `id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `analise_id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `revisao` int UNSIGNED NOT NULL,
  `descricao` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `recebido_em` date NOT NULL,
  `criado_por` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `campo_login_tentativas`
--

CREATE TABLE `campo_login_tentativas` (
  `id` bigint UNSIGNED NOT NULL,
  `email_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sucesso` tinyint(1) NOT NULL DEFAULT '0',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `campo_sessoes`
--

CREATE TABLE `campo_sessoes` (
  `id` char(64) COLLATE utf8mb4_general_ci NOT NULL,
  `usuario_id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ultimo_acesso_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expira_em` datetime NOT NULL,
  `revogado_em` datetime DEFAULT NULL,
  `ip_hash` char(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_agent_hash` char(64) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `certificados_cht`
--

CREATE TABLE `certificados_cht` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT (uuid()),
  `numero_certificado` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `numero_relatorio_ht` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'N??mero do relat??rio (AM-REL-HT:{n}/{ano})',
  `token_assinatura` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `profissional_empresa` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Nome do profissional ou empresa homologada',
  `cpf_cnpj` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email_destinatario` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `atividade_homologada` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Atividade t??cnica homologada',
  `relatorio_homologacao_numero` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `observacoes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `data_emissao` date NOT NULL,
  `data_validade` date DEFAULT NULL,
  `local_emissao` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'Bel??m-PA',
  `assinante_nome` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `assinante_titulo` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `assinante_registro` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `responsavel_assinatura_id` int DEFAULT NULL,
  `assinatura_imagem` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `assinatura_ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `assinatura_em` datetime DEFAULT NULL,
  `assinado` tinyint(1) DEFAULT '0',
  `dados_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `status` enum('rascunho','emitido','assinado','cancelado') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'rascunho',
  `caminho_arquivo_pdf` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `hash_arquivo_pdf` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `criado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `vistoria_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `despachante_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
) ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `certificados_cnarq`
--

CREATE TABLE `certificados_cnarq` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT (uuid()),
  `numero` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tipo` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'Condicional',
  `token_assinatura` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nome_embarcacao` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `numero_inscricao` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `indicativo_chamada` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tipo_embarcacao` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ano_construcao` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `material_casco` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `porto_inscricao` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `local_construcao` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `data_quilha` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `comprimento_total` decimal(8,2) DEFAULT NULL,
  `comprimento_casco` decimal(8,2) DEFAULT NULL,
  `comprimento_lpp` decimal(8,2) DEFAULT NULL COMMENT 'Comprimento entre perpendiculares',
  `boca_moldada` decimal(8,2) DEFAULT NULL,
  `boca_maxima` decimal(8,2) DEFAULT NULL,
  `pontal_moldado` decimal(8,2) DEFAULT NULL,
  `arqueacao_bruta` decimal(10,2) DEFAULT NULL COMMENT 'ArqueaÃ§Ã£o bruta (AB)',
  `arqueacao_liquida` decimal(10,2) DEFAULT NULL COMMENT 'ArqueaÃ§Ã£o lÃ­quida (AL)',
  `metodo_arqueacao` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'MÃ©todo utilizado (NORMAM, ConvenÃ§Ã£o, etc)',
  `calado_moldado_m` decimal(8,3) DEFAULT NULL,
  `passageiros_camarotes` int DEFAULT '0',
  `passageiros_outros` int DEFAULT '0',
  `espacos_incluidos_ab` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `espacos_incluidos_al` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `espacos_excluidos_m3` decimal(10,2) DEFAULT '0.00',
  `data_local_arqueacao_original` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `data_local_ultima_rearqueacao` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `relatorio_numero` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `data_vistoria` date DEFAULT NULL,
  `local_vistoria` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tipo_vistoria_certificado` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `observacoes_verso` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `data_emissao` date NOT NULL,
  `data_validade` date NOT NULL,
  `local_emissao` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'BelÃ©m-PA',
  `assinante_nome` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `assinante_titulo` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `assinante_registro` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `responsavel_assinatura_id` int DEFAULT NULL,
  `assinatura_imagem` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `assinatura_ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `assinatura_em` datetime DEFAULT NULL,
  `assinado` tinyint(1) DEFAULT '0',
  `caminho_arquivo_pdf` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `hash_arquivo_pdf` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('rascunho','emitido','assinado','cancelado') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'rascunho',
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `criado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `vistoria_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `despachante_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `certificados_cnbl`
--

CREATE TABLE `certificados_cnbl` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT (uuid()),
  `numero` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tipo` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Condicional',
  `token_assinatura` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nome_embarcacao` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `numero_inscricao` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `porto_inscricao` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `indicativo_chamada` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `atividades_servicos` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tipo_embarcacao` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ano_construcao` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `comprimento_total` decimal(8,2) DEFAULT NULL,
  `comprimento_casco` decimal(8,2) DEFAULT NULL,
  `boca_moldada` decimal(8,2) DEFAULT NULL,
  `pontal_moldado` decimal(8,2) DEFAULT NULL,
  `arqueacao_bruta` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tipo_navegacao` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `area_navegacao` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `material_casco` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `borda_livre_mm` int DEFAULT NULL,
  `borda_livre_tipo` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Tipo de borda livre (verÃ£o, tropical, etc)',
  `calado_maximo_m` decimal(8,2) DEFAULT NULL,
  `relatorio_numero` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `data_vistoria` date DEFAULT NULL,
  `local_vistoria` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tipo_vistoria_certificado` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `observacoes_verso` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `data_emissao` date NOT NULL,
  `data_validade` date NOT NULL,
  `local_emissao` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'BelÃ©m-PA',
  `assinante_nome` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `assinante_titulo` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `assinante_registro` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `responsavel_assinatura_id` int DEFAULT NULL,
  `assinatura_imagem` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `assinatura_ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `assinatura_em` datetime DEFAULT NULL,
  `assinado` tinyint(1) DEFAULT '0',
  `caminho_arquivo_pdf` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `hash_arquivo_pdf` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('rascunho','emitido','assinado','cancelado') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'rascunho',
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `criado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `aresta_superior_linha_conves` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '0 mm',
  `centro_disco_situado` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '0 mm',
  `dist_linha_conves_bico_proa` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '',
  `dist_linha_conves_abaixo_disco` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '',
  `marca_linha_carga_area1` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '0 mm',
  `marca_linha_carga_area2` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '0 mm',
  `acrescimo_agua_salgada` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '0 mm',
  `vistoria_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `despachante_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `certificados_csn`
--

CREATE TABLE `certificados_csn` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT (uuid()),
  `numero` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tipo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'Definitivo',
  `token_assinatura` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `emitente` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nome_embarcacao` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `numero_inscricao` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `indicativo_chamada` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `atividades_servicos` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tipo_embarcacao` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ano_construcao` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `comprimento_m` decimal(8,2) DEFAULT NULL,
  `arqueacao_bruta` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tipo_navegacao` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `area_navegacao` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fabricante_motor` varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `potencia_kw` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `material_casco` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `autorizado_carga` tinyint(1) DEFAULT '0',
  `qtd_passageiros` int DEFAULT '0',
  `obs_passageiros` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `observacoes_verso` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `relatorio_numero` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `data_vistoria_seco` date DEFAULT NULL,
  `data_vistoria_flutuando` date DEFAULT NULL,
  `local_vistoria` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `normam_aplicavel` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tipo_vistoria_certificado` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `acessibilidade_sim` tinyint(1) DEFAULT '0',
  `acessibilidade_nao` tinyint(1) DEFAULT '1',
  `data_emissao` date NOT NULL,
  `data_validade` date NOT NULL,
  `local_emissao` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'BelÃ©m-PA',
  `assinante_nome` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `assinante_titulo` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `assinante_registro` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `responsavel_assinatura_id` int DEFAULT NULL,
  `assinatura_imagem` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `assinatura_ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `assinatura_em` datetime DEFAULT NULL,
  `assinado` tinyint(1) DEFAULT '0',
  `caminho_arquivo_pdf` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `hash_arquivo_pdf` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('rascunho','emitido','assinado','cancelado') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'rascunho',
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `criado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `vistoria_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `certificados_lc`
--

CREATE TABLE `certificados_lc` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT (uuid()),
  `numero_lc` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'N??mero da licen??a (AM-LC:{n}/{ano} ou AM-EC:{n}/{ano})',
  `embarcacao_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'ID da embarca????o no cadastro',
  `token_assinatura` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tipo_licenca` enum('LC','LA','LR','LCEC') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'LC',
  `data_termino_construcao` date DEFAULT NULL,
  `nome_embarcacao` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tipo_embarcacao` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `numero_casco` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `material_casco` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sociedade_classificadora` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `comprimento_total` decimal(8,2) DEFAULT NULL,
  `comprimento_pp` decimal(8,2) DEFAULT NULL COMMENT 'Comprimento entre perpendiculares',
  `boca_moldada` decimal(8,2) DEFAULT NULL,
  `pontal_moldado` decimal(8,2) DEFAULT NULL,
  `calado_maximo` decimal(8,2) DEFAULT NULL,
  `porte_bruto` decimal(10,2) DEFAULT NULL,
  `numero_tripulantes` int DEFAULT NULL,
  `numero_passageiros` int DEFAULT NULL,
  `tipo_navegacao` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `area_navegacao` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `atividade_servico` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `propulsao` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `proprietario_nome` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `proprietario_cpf_cnpj` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `proprietario_endereco` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `estaleiro_nome` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estaleiro_cpf_cnpj` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estaleiro_endereco` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `data_emissao` date NOT NULL,
  `data_validade` date DEFAULT NULL,
  `local_emissao` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'Bel??m-PA',
  `relatorio_numero` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `assinante_nome` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `assinante_titulo` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `assinante_registro` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `responsavel_assinatura_id` int DEFAULT NULL,
  `assinatura_imagem` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `assinatura_ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `assinatura_em` datetime DEFAULT NULL,
  `assinado` tinyint(1) DEFAULT '0',
  `caminho_arquivo_pdf` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `hash_arquivo_pdf` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dados_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `status` enum('rascunho','emitido','assinado','cancelado') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'rascunho',
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `criado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `vistoria_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `despachante_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
) ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `certificados_lp`
--

CREATE TABLE `certificados_lp` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT (uuid()),
  `numero_lp` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'N??mero da licen??a (AM-LP:{n}/{ano})',
  `embarcacao_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'ID da embarca????o no cadastro',
  `token_assinatura` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tipo_licenca` enum('construcao','alteracao','reclassificacao','lcec') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'construcao',
  `nome_embarcacao` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tipo_embarcacao` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `numero_casco` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `material_casco` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `comprimento_total` decimal(8,2) DEFAULT NULL,
  `boca_moldada` decimal(8,2) DEFAULT NULL,
  `pontal_moldado` decimal(8,2) DEFAULT NULL,
  `proprietario_nome` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `proprietario_cpf_cnpj` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `proprietario_endereco` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `estaleiro_nome` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estaleiro_cpf_cnpj` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estaleiro_endereco` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `observacoes_exigencias` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `data_emissao` date NOT NULL,
  `validade_dias` int DEFAULT NULL COMMENT 'Validade em dias',
  `validade_data` date DEFAULT NULL COMMENT 'Data de validade calculada',
  `data_requerimento` date DEFAULT NULL,
  `assinante_nome` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `assinante_titulo` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `assinante_registro` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `responsavel_assinatura_id` int DEFAULT NULL,
  `assinatura_imagem` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `assinatura_ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `assinatura_em` datetime DEFAULT NULL,
  `assinado` tinyint(1) DEFAULT '0',
  `caminho_arquivo_pdf` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `hash_arquivo_pdf` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dados_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `status` enum('rascunho','emitido','assinado','cancelado') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'rascunho',
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `criado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `vistoria_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `despachante_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
) ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `cert_convalidacoes`
--

CREATE TABLE `cert_convalidacoes` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT (uuid()),
  `tipo_certificado` enum('CNBL') COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Convalidacoes exclusivas do certificado CNBL',
  `certificado_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'ID do certificado (certificados_cnbl ou certificados_cnarq)',
  `numero_vistoria` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Ex: 1Âª VIST. ANUAL, 2Âª VIST. ANUAL, etc',
  `data_inicio` date DEFAULT NULL,
  `data_fim` date DEFAULT NULL,
  `local_data` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `vistoriador` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `atualizado_em` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `clientes`
--

CREATE TABLE `clientes` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT (uuid()),
  `nome` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tipo_pessoa` enum('PF','PJ') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'PF',
  `cpf_cnpj` varchar(18) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `perfil` enum('armador','proprietario','despachante') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'proprietario',
  `telefone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `endereco` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `status` enum('ATIVO','INATIVO') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'ATIVO',
  `tipo_recebimento` enum('pix','cc') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `chave_pix` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `banco` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `agencia` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `conta` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `criado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `clientes_embarcacoes`
--

CREATE TABLE `clientes_embarcacoes` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT (uuid()),
  `cliente_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `embarcacao_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('ATIVO','INATIVO') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'ATIVO',
  `vinculado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `desvinculado_em` datetime DEFAULT NULL,
  `vinculado_por` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `desvinculado_por` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `vinculo_ativo_chave` varchar(73) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `clientes_tipos_embarcacao`
--

CREATE TABLE `clientes_tipos_embarcacao` (
  `cliente_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tipo_embarcacao_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `cliente_password_resets`
--

CREATE TABLE `cliente_password_resets` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT (uuid()),
  `cliente_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `token_hash` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `expira_em` datetime NOT NULL,
  `usado_em` datetime DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `cliente_portal_acessos`
--

CREATE TABLE `cliente_portal_acessos` (
  `cliente_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `login` varchar(190) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `senha_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `forcar_troca_senha` tinyint(1) NOT NULL DEFAULT '1',
  `ultimo_login_em` datetime DEFAULT NULL,
  `criado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `configuracoes`
--

CREATE TABLE `configuracoes` (
  `chave` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `valor` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `descricao` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `atualizado_em` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `configuracoes`
--

INSERT INTO `configuracoes` (`chave`, `valor`, `descricao`, `atualizado_em`) VALUES
('acesso_documentacao_usuarios', '[3774]', 'IDs dos usuários com acesso à documentação', '2026-06-29 06:38:14'),
('backup_email', 'ronokedas2020@gmail.com', 'E-mail para receber backups do banco de dados', '2026-06-29 05:22:15'),
('dados_teste_embarcacoes', '1', 'Exibe o preenchimento rápido com dados fictícios no cadastro de embarcações', '2026-07-19 02:29:40'),
('meta_mensagem', 'Ao bater a meta, teremos um dia especial com toda a equipe.', 'Mensagem da meta mensal exibida para a equipe', '2026-07-16 17:48:32'),
('meta_mensal', '180000.00', 'Meta mensal de faturamento comercial em R$', '2026-07-06 22:49:46'),
('responsavel_assinatura_cargo', 'Engenheiro Naval', NULL, '2026-07-02 17:34:06'),
('responsavel_assinatura_nome', 'João Responsável', NULL, '2026-07-02 17:34:06'),
('responsavel_assinatura_registro', 'CREA 123456', NULL, '2026-07-02 17:34:06');

-- --------------------------------------------------------

--
-- Estrutura para tabela `contratos`
--

CREATE TABLE `contratos` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `proposta_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cliente_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `numero` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('MINUTA','AGUARDANDO_ASSINATURA','ASSINADO','CANCELADO') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'MINUTA',
  `frequencia` enum('ÃšNICA','MENSAL','TRIMESTRAL','SEMESTRAL','ANUAL') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'ÃšNICA',
  `dia_vencimento` tinyint DEFAULT NULL,
  `proximo_faturamento` date DEFAULT NULL,
  `renovacao_automatica` tinyint(1) NOT NULL DEFAULT '1',
  `data_emissao` date DEFAULT NULL,
  `data_vencimento` date DEFAULT NULL,
  `valor_total` decimal(10,2) DEFAULT NULL,
  `conteudo` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `assinado_por` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `assinado_ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `assinado_em` datetime DEFAULT NULL,
  `caminho_arquivo_pdf` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `hash_arquivo_pdf` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `criado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `csn_convalidacoes`
--

CREATE TABLE `csn_convalidacoes` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT (uuid()),
  `certificado_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `numero_vistoria` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `data_inicio` date DEFAULT NULL,
  `data_fim` date DEFAULT NULL,
  `local_data` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `vistoriador` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `atualizado_em` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `csn_distribuicao_passageiros`
--

CREATE TABLE `csn_distribuicao_passageiros` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT (uuid()),
  `certificado_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `item_codigo` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `local_nome` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `quantidade` int DEFAULT '0',
  `conves_principal` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `conves_superior` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `area_lazer` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `unidade` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `documento_aprovacoes`
--

CREATE TABLE `documento_aprovacoes` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `documento_tipo` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `documento_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `versao` int UNSIGNED NOT NULL DEFAULT '1',
  `responsavel_id` int NOT NULL,
  `aprovador_usuario_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `responsavel_nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `responsavel_cpf_cnpj` varchar(18) COLLATE utf8mb4_unicode_ci NOT NULL,
  `responsavel_cargo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `responsavel_registro` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `aprovador_nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `assinatura_arquivo` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `assinatura_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `aprovado_em_utc` datetime NOT NULL,
  `aprovado_em_local` datetime NOT NULL,
  `fuso_horario` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'America/Sao_Paulo',
  `utc_offset` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `geo_precisao_m` decimal(10,2) DEFAULT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `autenticacao_metodo` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SESSAO',
  `assinatura_convite_id` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hash_pdf_original` char(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hash_pdf_final` char(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `caminho_pdf_original` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `caminho_pdf_final` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `token_validacao` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('PROCESSANDO','APROVADO','FALHA','CANCELADO') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PROCESSANDO',
  `padrao_assinatura` enum('AUDIT_ONLY','PADES_ICP_BRASIL') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'AUDIT_ONLY',
  `status_pades` enum('NAO_APLICADO','APLICADO','INVALIDO') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'NAO_APLICADO',
  `provedor_assinatura` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `certificado_titular` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `certificado_serial` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `certificado_valido_de` datetime DEFAULT NULL,
  `certificado_valido_ate` datetime DEFAULT NULL,
  `erro_processamento` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

CREATE TABLE `assinatura_convites` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `documento_tipo` enum('CSN','CNBL','CNARQ') COLLATE utf8mb4_unicode_ci NOT NULL,
  `documento_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `responsavel_id` int NOT NULL,
  `usuario_id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `token_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_destinatario` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('ATIVO','PROCESSANDO','UTILIZADO','CANCELADO') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ATIVO',
  `autenticacao_metodo` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EMAIL_MAGIC_LINK',
  `expira_em` datetime NOT NULL,
  `enviado_em` datetime DEFAULT NULL,
  `utilizado_em` datetime DEFAULT NULL,
  `cancelado_em` datetime DEFAULT NULL,
  `cancelado_por` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `aprovacao_id` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_assinatura_convite_token` (`token_hash`),
  KEY `idx_assinatura_convite_documento` (`documento_tipo`,`documento_id`,`status`),
  KEY `idx_assinatura_convite_expiracao` (`status`,`expira_em`),
  KEY `idx_assinatura_convite_responsavel` (`responsavel_id`),
  KEY `idx_assinatura_convite_usuario` (`usuario_id`),
  CONSTRAINT `fk_assinatura_convite_responsavel` FOREIGN KEY (`responsavel_id`) REFERENCES `responsaveis_assinatura` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_assinatura_convite_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

CREATE TABLE `documento_assinaturas` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `documento_tipo` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `documento_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `versao` int UNSIGNED NOT NULL DEFAULT '1',
  `responsavel_id` int NOT NULL,
  `usuario_id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `assinatura_arquivo` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `assinatura_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hash_pdf_original` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hash_pdf_assinado` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `caminho_pdf_original` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `caminho_pdf_assinado` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token_validacao` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `geo_precisao_m` decimal(10,2) DEFAULT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('ASSINADO','CANCELADO') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ASSINADO',
  `assinado_em` datetime NOT NULL,
  `cancelado_em` datetime DEFAULT NULL,
  `cancelado_por` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `motivo_cancelamento` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_documento_assinatura_versao` (`documento_tipo`,`documento_id`,`versao`),
  UNIQUE KEY `uk_documento_assinatura_token` (`token_validacao`),
  KEY `idx_documento_assinatura_documento` (`documento_tipo`,`documento_id`,`status`),
  KEY `idx_documento_assinatura_responsavel` (`responsavel_id`),
  KEY `idx_documento_assinatura_usuario` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `documento_artefatos`
--

CREATE TABLE `documento_artefatos` (
  `id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `documento_tipo` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `documento_id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `versao` int UNSIGNED NOT NULL DEFAULT '1',
  `status_documento` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `caminho_arquivo` varchar(500) COLLATE utf8mb4_general_ci NOT NULL,
  `nome_arquivo` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `mime_type` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'application/pdf',
  `tamanho_bytes` bigint UNSIGNED NOT NULL,
  `sha256` char(64) COLLATE utf8mb4_general_ci NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `email_logs`
--

CREATE TABLE `email_logs` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT (uuid()),
  `destinatario` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'E-mail do destinat??rio',
  `assunto` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Assunto do e-mail enviado',
  `tipo` enum('proposta','agendamento','certificado','assinatura','alerta_vencimento','portal_acesso','portal_recuperacao_senha') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `referencia_tipo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Tipo da entidade referenciada (ex: propostas, certificados_cnbl)',
  `referencia_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'ID da entidade referenciada',
  `status` enum('enviado','erro') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'enviado' COMMENT 'Status do envio',
  `mensagem_erro` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Mensagem de erro se o envio falhou',
  `enviado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'ID do usu??rio que enviou',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Data/hora do envio'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `embarcacoes`
--

CREATE TABLE `embarcacoes` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT (uuid()),
  `proprietario_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nome` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tipo` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tipo_embarcacao_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tipo_embarcacao` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cnbl_tipo_embarcacao` varchar(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `possui_propulsao` tinyint(1) DEFAULT NULL,
  `fabricante_motor` varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `modelo_motor` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `numero_motor` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `potencia_kw` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `registro` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `proprietario` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ano` int DEFAULT NULL,
  `cliente_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `comprimento_total` decimal(8,2) DEFAULT NULL,
  `comprimento_casco` decimal(8,2) DEFAULT NULL,
  `comprimento_lpp` decimal(8,2) DEFAULT NULL,
  `pontal_moldado` decimal(8,2) DEFAULT NULL,
  `boca_moldada` decimal(8,2) DEFAULT NULL,
  `boca_maxima` decimal(8,2) DEFAULT NULL,
  `material_casco` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tipo_servico` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tipo_navegacao` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `area_navegacao` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cnbl_area_navegacao` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `arqueacao_bruta` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `numero_inscricao` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `porto_inscricao` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `indicativo_chamada` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `numero_tripulantes` int DEFAULT '0',
  `numero_passageiros_n1` int DEFAULT '0',
  `numero_passageiros_n2` int DEFAULT '0',
  `observacoes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `foto_chave` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `foto_url` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `foto_nome_original` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `foto_mime_type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `foto_tamanho_bytes` bigint UNSIGNED DEFAULT NULL,
  `foto_sha256` char(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `foto_atualizada_em` datetime DEFAULT NULL,
  `foto_atualizada_por` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `criado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `autorizado_carga` tinyint(1) DEFAULT NULL,
  `obs_passageiros` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `acessibilidade` tinyint(1) DEFAULT NULL,
  `local_construcao` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `numero_casco` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `porte_bruto` decimal(10,2) DEFAULT NULL,
  `estaleiro_nome` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estaleiro_cpf_cnpj` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estaleiro_endereco` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `arqueacao_liquida` decimal(10,2) DEFAULT NULL,
  `metodo_arqueacao` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cnarq_data_quilha` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cnarq_calado_moldado_m` decimal(8,3) DEFAULT NULL,
  `cnarq_espacos_incluidos_ab` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `cnarq_espacos_incluidos_al` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `cnarq_espacos_excluidos_m3` decimal(10,2) DEFAULT NULL,
  `cnarq_data_local_arqueacao_original` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cnarq_data_local_ultima_rearqueacao` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `borda_livre_mm` int DEFAULT NULL,
  `borda_livre_tipo` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `calado_maximo_m` decimal(8,2) DEFAULT NULL,
  `aresta_superior_linha_conves` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `centro_disco_situado` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dist_linha_conves_bico_proa` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dist_linha_conves_abaixo_disco` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `marca_linha_carga_area1` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `marca_linha_carga_area2` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `acrescimo_agua_salgada` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `escritorios`
--

CREATE TABLE `escritorios` (
  `id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `nome` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `cidade` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `uf` char(2) COLLATE utf8mb4_general_ci NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `escritorios`
--

INSERT INTO `escritorios` (`id`, `nome`, `cidade`, `uf`, `ativo`, `criado_em`, `atualizado_em`) VALUES
('00000000-0000-4000-8000-000000000100', 'Matriz', 'Manaus', 'AM', 1, '2026-07-20 15:13:20', '2026-07-20 15:13:20'),
('1801fc90-5734-417c-acf2-fed7399a23f1', 'Conquista', 'Ananindeua', 'PA', 1, '2026-07-20 15:13:20', '2026-07-20 15:13:20');

-- --------------------------------------------------------

--
-- Estrutura para tabela `exigencias_catalogo`
--

CREATE TABLE `exigencias_catalogo` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT (uuid()),
  `codigo_interno` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `categoria_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `descricao` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `item_normam` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bloco_vistoria` enum('seco','flutuando','borda_livre','arqueacao') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tipo_vistoria` enum('seco','flutuando','borda_livre','arqueacao') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `prazo_padrao_dias` int DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `aplicabilidade_a` tinyint(1) NOT NULL DEFAULT '1',
  `aplicabilidade_b` tinyint(1) NOT NULL DEFAULT '1',
  `aplicabilidade_c` tinyint(1) NOT NULL DEFAULT '1',
  `aplicabilidade_d` tinyint(1) NOT NULL DEFAULT '1',
  `aplicabilidade_e` tinyint(1) NOT NULL DEFAULT '1',
  `aplicabilidade_f` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `exigencias_catalogo`
--

INSERT INTO `exigencias_catalogo` (`id`, `codigo_interno`, `categoria_id`, `descricao`, `item_normam`, `bloco_vistoria`, `tipo_vistoria`, `prazo_padrao_dias`, `ativo`, `criado_em`, `atualizado_em`, `aplicabilidade_a`, `aplicabilidade_b`, `aplicabilidade_c`, `aplicabilidade_d`, `aplicabilidade_e`, `aplicabilidade_f`) VALUES
('001794c9-7765-48f2-aa3e-13b4ff29aba8', 'EX-344', 'b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d', 'A dotação de coletes salva vidas atende a totalidade de pessoas a serem transportadas, inclusive crianças (10% para elas)', 'NORMAM-202/DPC, Cap. 04, Item 4.13.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('005da3a8-7a7b-4fab-b855-6dbbf28f8fa8', 'EX-373', 'a5f25230-91c9-4e14-aa33-e83524d5d943', 'As embarcações com AB maior que 500 deverão ter, pelo menos, duas bombas de incêndio de acionamento não manual, sendo que uma bomba deverá possuir força motriz distinta da outra e independente do motor principal.', 'NORMAM-202/DPC, Cap. 03, Seção III.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('012e8fb1-9d0f-4d3c-94a4-8bb0ee588991', 'EX-329', 'e70f7906-4e9d-4367-b10a-2ad2a007817a', 'Indicador de rotação do(s) MCP(s) no passadiço ou comando', 'NORMAM-202/DPC', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 0, 1, 0, 0),
('025542ea-e255-4ace-9dbd-b02ef35feabd', 'EX-358', 'b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d', 'Data de fabricação (Embarcações de Sobrevivência/Boias)', 'NORMAM-202/DPC, Cap. 04, Item 4.12.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('0382e720-a8ce-42ef-8146-d19431108b5a', 'EX-438', 'b8ed9a31-9fa3-492f-904e-b8158a06d0da', 'a) os fios são protegidos por meio de eletrodutos rígidos ou flexíveis', 'NORMAM-202/DPC', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('03d79106-5ac2-42a2-ba86-af98a21c6022', 'EX-382', 'a5f25230-91c9-4e14-aa33-e83524d5d943', 'O número de seções de mangueira, incluindo uniões e esguichos, é de uma para cada 30 m de comprimento da embarcação e há outra sobressalente (sendo que, em nenhum caso, este número poderá ser inferior a três).', 'NORMAM-202/DPC, Cap. 04, Seção III.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 0, 1, 0, 0),
('0470cbba-bc5c-4e90-841d-6de840326f65', 'EX-339', 'b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d', 'Classe (Coletes salva-vidas)', 'NORMAM-202/DPC, Cap. 04, Item 4.13.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('0496349c-9dd7-4bf1-b628-d6a87e9744ab', 'EX-463', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'Existe a bordo um compartimento, com dimensões apropriadas e com possibilidade de trancamento, para a guarda de bagagens e volumes de passageiros, conforme indicado no projeto', 'NORMAM-202/DPC, Cap. 03, Seção II.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 1, 1, 0, 0),
('066394ff-2a85-4b3b-8338-e04f6948b915', 'EX-371', 'a5f25230-91c9-4e14-aa33-e83524d5d943', 'A embarcação é dotada de, pelo menos, uma bomba de incêndio fixa não manual, com vazão maior ou igual a 15 m³/h (tal bomba poderá ser acionada pelo motor principal)', 'NORMAM-202/DPC, Cap. 04, Seção III.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('06a2613d-ad79-437a-b3b8-190ae85212da', 'EX-537', '71c05e83-0d67-4137-b2b7-478c4241a057', 'Escala de calado está escrita a boreste e a bombordo, a vante e a ré e a meia nau, em medidas métricas', 'NORMAM-202/DPC, Cap. 02, Seção II.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:14', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('076e253a-6e6a-4a81-9877-640da3ad73e1', 'EX-405', '65bf89f0-f44d-4746-89f7-f530c9aa990d', 'As bombas utilizadas para transferência de óleo para consumo da embarcação deverão ser instaladas sobre bandejas coletoras, que possibilitem, em caso de vazamentos, a coleta do óleo derramado', 'NORMAM-202/DPC, Cap. 03, Seção III.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('07a3393b-429c-447d-bfd0-353a6683bd1b', 'EX-387', 'a5f25230-91c9-4e14-aa33-e83524d5d943', 'A identificação por cores das tubulações em todas as embarcações deverá ser efetuada em conformidade com o disposto na norma ISO 14726:2008.', 'NORMAM-202/DPC, Cap. 09, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('07f7f40b-5d11-4d8b-b409-54d6f2d9ec76', 'EX-407', '65bf89f0-f44d-4746-89f7-f530c9aa990d', 'Verificar as proteções térmicas e acústicas do(s) motor(es) de embarcações de transporte de passageiros', 'NORMAM-202/DPC, Cap. 03, Seção III.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 1, 1, 0, 0),
('0812830b-ec4d-4746-bb3b-d6cf8a6eb74a', 'EX-428', 'b8ed9a31-9fa3-492f-904e-b8158a06d0da', 'Quanto aos quadros elétricos: b) o de emergência está próximo à fonte de energia elétrica de emergência', 'NORMAM-202/DPC, Cap. 03, Seção IV.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('08457e8a-69b4-4157-b040-15d526d41a67', 'EX-335', 'e70f7906-4e9d-4367-b10a-2ad2a007817a', 'Verificar a presença de relógio de parede ou de painel no comando, devidamente sincronizado e operacional.', 'NORMAM-202/DPC, Cap. 03, Seção II.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('0a144b76-52d6-4e7d-a1c2-8154c5ccf4fb', 'EX-520', '71c05e83-0d67-4137-b2b7-478c4241a057', 'Abaixo do convés aberto mais baixo, a via de escape principal é uma escada e a via secundária consiste num conduto ou numa escada', 'NORMAM-202/DPC, Cap. 04, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:14', '2026-07-04 04:12:38', 1, 0, 1, 1, 0, 0),
('0a212a39-3f21-4932-ab3b-7d5bd4e8721f', 'EX-368', 'a5f25230-91c9-4e14-aa33-e83524d5d943', 'Os botijões de gás estão posicionados em áreas externas, em local seguro e arejado, protegidos do sol e afastados de fontes que possam causar ignição.', 'NORMAM-202/DPC, Cap. 04, Item 4.29.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('0bb736ea-8f70-4b80-9ac4-c441139fbe3c', 'EX-374', 'a5f25230-91c9-4e14-aa33-e83524d5d943', 'Em EMPURRADORES e REBOCADORES a(s) bomba(s), as duas tomadas e as duas estações de incêndio completas deverão estar posicionadas nas proximidades da proa da embarcação', 'NORMAM-202/DPC, Cap. 03, Seção III.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('0c21a30d-7637-49bd-94b9-eaa39968b2bc', 'EX-508', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'A unidade de chuveiro apresenta soleira com uma altura mínima de 100 mm acima do convés e é impermeabilizadas até esse nível', 'NORMAM-202/DPC, Cap. 03, Seção II.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 1, 1, 0, 0),
('0d11a58c-88e9-40df-b7c1-28e0eb4e62b0', 'EX-325', 'e70f7906-4e9d-4367-b10a-2ad2a007817a', 'Ecobatímetro', 'NORMAM-202/DPC, Cap. 04, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 0, 1, 0, 0),
('0dc6cd05-01d7-4035-b683-fb1c6251f2d8', 'EX-350', 'b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d', 'A dotação das embarcações de sobrevivência está de acordo com o quadro da NORMAM e estão em boas condições (inclusive suas alças, se aparelho rígido)', 'NORMAM-202/DPC', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('0ddc6914-749b-40e7-8799-15c272201ebf', 'EX-338', 'b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d', 'Modelo (Coletes salva-vidas)', 'NORMAM-202/DPC, Cap. 04, Item 4.13.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('0e8c9c8f-adb8-444a-985e-dc2cebd737b4', 'EX-502', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'As distâncias mínimas que deverão ser observadas entre as unidades do sanitário coletivo são as seguintes (Unidade em frente a unidade, lavatório, antepara, etc.)', 'NORMAM-202/DPC, Cap. 03, Seção II.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 1, 1, 0, 0),
('0ed1e638-2afc-4cdf-ad7a-3d1e9b3fb6c4', 'EX-475', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'As cadeiras deverão atender às seguintes dimensões: c) profundidade mínima de 0,40 m', 'NORMAM-202/DPC, Cap. 03, Seção II.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 1, 1, 0, 0),
('0fcd87ed-ac18-4025-a692-d79d5ba5599b', 'EX-443', 'b8ed9a31-9fa3-492f-904e-b8158a06d0da', 'f) os cabos e fiação utilizados nos circuitos elétricos de fornecimento essencial ou de emergência de força, iluminação, comunicações interiores ou sinalização não passam por áreas em que haja risco de incêndio', 'NORMAM-202/DPC, Cap. 03, Seção IV.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('0fdc1e57-8063-4666-ab7d-cee70fff1cf4', 'EX-367', 'a5f25230-91c9-4e14-aa33-e83524d5d943', 'Todos os extintores portáteis possuem o selo do INMETRO e estão dentro do prazo de validade, com as manutenções periódicas realizadas', 'NORMAM-202/DPC, Cap. 04, Seção III.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('144a054a-435d-4c39-8a2a-c0ad22d4f20e', 'EX-500', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'Cada módulo do lavatório coletivo possui sua torneira própria, e há um dreno servindo a, no máximo, 5 módulos', 'NORMAM-202/DPC, Cap. 03, Seção II.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 1, 1, 0, 0),
('15d35d22-8df1-4051-ae12-4f75812736d9', 'EX-359', 'b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d', 'Nome da embarcação (Embarcações de Sobrevivência/Boias)', 'NORMAM-202/DPC, Cap. 04, Item 4.12.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('16dbdb50-0884-4e9f-8ee9-0b202a65fc04', 'EX-314', 'aa4a7f0d-004d-4a60-924e-693335fdd69b', 'Tabelas ou quadros em outros locais de fácil visualização: - tabelas ou quadros de primeiros socorros', 'NORMAM-202/DPC, Cap. 04, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 0, 1, 0, 0),
('191031d1-a918-4879-9118-a6bce6f4b56b', 'EX-362', 'a5f25230-91c9-4e14-aa33-e83524d5d943', 'Não são utilizados combustíveis com ponto de fulgor inferior a 60 °C (como álcool ou gasolina)', 'NORMAM-202/DPC', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('19b9e02f-e153-46af-90a9-deb6b1511808', 'EX-484', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'As têm, no mínimo, 1,9 m de comprimento e 0,68 m de largura', 'NORMAM-202/DPC', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 1, 1, 0, 0),
('1b8b2e7c-37f2-41d2-90e5-27d936a704da', 'EX-429', 'b8ed9a31-9fa3-492f-904e-b8158a06d0da', 'Quanto aos quadros elétricos: c) os lados, a parte de trás e da frente dos quadros elétricos estão devidamente protegidos, tapetes ou estrados não condutores estão no piso na frente e atrás dos referidos quadros.', 'NORMAM-202/DPC', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('1bb30d90-ee8e-4efe-946d-d3ee1385eb36', 'EX-398', '65bf89f0-f44d-4746-89f7-f530c9aa990d', 'Verificar a presença de objetos não necessários ao funcionamento dos equipamentos, estivados de forma irregular sobre ou próximo aos equipamentos', 'NORMAM-202/DPC', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('1c389c2a-ae2a-479b-9303-05f79a2846f8', 'EX-381', 'a5f25230-91c9-4e14-aa33-e83524d5d943', 'A rede e as tomadas de incêndio são pintadas de vermelho', 'NORMAM-202/DPC, Cap. 03, Seção II.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('1d3e7e6f-55fe-4e02-aa7b-b4e06329ec90', 'EX-376', 'a5f25230-91c9-4e14-aa33-e83524d5d943', 'Nas DEMAIS embarcações, deverá haver uma estação de incêndio no visual de uma pessoa que esteja junto a uma tomada de incêndio.', 'NORMAM-202/DPC, Cap. 04, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('1de8358a-fa6e-4cef-876d-6784f605e96d', 'EX-334', 'e70f7906-4e9d-4367-b10a-2ad2a007817a', 'Verificar a presença e o pleno funcionamento do sistema regulamentar \'Sistran\' no comando da embarcação.', 'NORMAM-202/DPC, Cap. 04, Item 4.2', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 02:36:44', 1, 1, 1, 1, 1, 1),
('1f83e2dd-32fd-4f92-84c3-524af3ceb621', 'EX-544', '71c05e83-0d67-4137-b2b7-478c4241a057', 'Entrar no porão com o plano de perfil estrutural e confrontar os espaçamentos das cavernas/estruturas em loco (ex: 35 ou 50 cm), inspecionando furos, descontinuidades e corrosão.', 'NORMAM-202/DPC, Cap. 03, Seção III.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:14', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('20ceea81-c249-4b94-9448-af7887e79124', 'EX-467', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'Os espaços para redes apresentam ventilação natural permanente para o exterior da embarcação, tendo como meio de fechamento sanefas ou janelas móveis. No caso de janela móvel, a área mínima de ventilação é de 40% do vão da abertura', 'NORMAM-202/DPC, Cap. 03, Seção II.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 1, 1, 0, 0),
('20d82a21-815c-4aa1-bdf5-282950555392', 'EX-541', '71c05e83-0d67-4137-b2b7-478c4241a057', 'Verificar se os acessos aos locais abaixo relacionados estão livres: Embornais, saídas d\'água das tomadas de incêndio, tubos de sondagem, suspiros e bocas de ventiladores', 'NORMAM-202/DPC, Cap. 04, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:14', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('2127c977-6a9f-4e11-9787-3aa2b600b21a', 'EX-501', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'Em frente a cada lavatório existe um espaço livre igual ou superior a 0,5 x 0,6 m', 'NORMAM-202/DPC, Cap. 03, Seção II.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 1, 1, 0, 0),
('22a1886c-48c1-4323-8cce-d1a9f509b800', 'EX-459', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'Existe separação física que permita isolar carga e passageiros', 'NORMAM-202/DPC, Cap. 03, Seção II.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 1, 1, 0, 0),
('22f45a4b-749e-4340-93bb-4c18b3a8273b', 'EX-316', 'aa4a7f0d-004d-4a60-924e-693335fdd69b', 'Relatório de medição de espessura (cinco pontos por chapa), assinado por profissional qualificado e certificado, com reconhecimento no Sistema Nacional de Qualificação e Certificação de Pessoal em Ensaios Não Destrutivos (SNQC/END), acompanhado de documento que comprove a validade da citada habilitação na data de execução do serviço', 'NORMAM-202/DPC, Cap. 08, Item 8.5', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 02:36:44', 1, 1, 1, 1, 1, 1),
('23a80531-b5d7-4dec-bfc3-a56db5c37e23', 'EX-542', '71c05e83-0d67-4137-b2b7-478c4241a057', 'Verificar se os acessos aos locais abaixo relacionados estão livres: Elementos de amarração e fundeio e o acesso às máquinas', 'NORMAM-202/DPC, Cap. 04, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:14', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('2704ff5c-b1e3-4799-8637-fdedf7f3114b', 'EX-393', '65bf89f0-f44d-4746-89f7-f530c9aa990d', 'Correias, ferramentas e sobressalentes deverão ser acondicionados em local apropriado (como cabides e armários), que evite seu deslocamento', 'NORMAM-202/DPC', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('27e53b15-99f1-4cd2-a400-ab471fb91c23', 'EX-486', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'A distância mínima entre o topo de um colchão e a parte inferior do estrado da cama imediatamente superior ou a parte inferior dos reforços do convés superior (teto do camarote) é de 0,6 m', 'NORMAM-202/DPC, Cap. 03, Seção II.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 1, 1, 0, 0),
('2a3a0379-b1ba-40fe-b676-809f122084a1', 'EX-413', '65bf89f0-f44d-4746-89f7-f530c9aa990d', 'Verificar o indicador do sentido de impulsão do(s) propulsor(es) lateral(ais) no passadiço', 'NORMAM-202/DPC, Cap. 04, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('2b8953dd-9bc1-45c6-92a3-ace223c00b5b', 'EX-446', 'b8ed9a31-9fa3-492f-904e-b8158a06d0da', 'i) as partes condutoras de tomadas e plugs estão protegidas de modo a impedir de serem tocadas, mesmo durante ligamento e desligamento', 'NORMAM-202/DPC', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('2bd5be9b-36f4-40bf-81ad-20cb8ca52aee', 'EX-527', '71c05e83-0d67-4137-b2b7-478c4241a057', 'As cores das luzes de navegação estão de acordo com as normas específicas sobre o assunto', 'RIPEAM 72 / NORMAM-202/DPC, Cap. 04.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:14', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('2c585b69-496a-420b-8fa7-14e372dda5dc', 'EX-492', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'As portas de acesso de banheiros não abrem diretamente para cozinhas ou refeitórios', 'NORMAM-202/DPC, Cap. 03, Seção II.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 1, 1, 0, 0),
('31bb4064-def1-4e32-8ef7-e207f15562dd', 'EX-384', 'a5f25230-91c9-4e14-aa33-e83524d5d943', 'Há completa permutabilidade entre as uniões, mangueiras e esguichos', 'NORMAM-202/DPC, Cap. 04, Seção III.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('320476cf-8452-4bbc-908d-9f363b3b2eac', 'EX-401', '65bf89f0-f44d-4746-89f7-f530c9aa990d', 'Redes de descarga devem ser flangeadas onde ultrapassem anteparas e ou costado (de modo que garanta a estanqueidade)', 'NORMAM-202/DPC, Cap. 03, Seção II.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('33356298-d44a-451e-b38c-e360b2a5bed5', 'EX-437', 'b8ed9a31-9fa3-492f-904e-b8158a06d0da', 'O quadro das luzes de navegação é alimentado por uma linha independente derivada do quadro principal e de emergência', 'RIPEAM 72 / NORMAM-202/DPC, Cap. 04.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('33e7f3eb-6d6d-4bdf-8bdb-80a063c683ce', 'EX-452', 'b8ed9a31-9fa3-492f-904e-b8158a06d0da', 'o) nos circuitos polifásicos, se a seção dos condutores fase for igual ou inferior a 16 mm² e nos circuitos monofásicos, seja qual for a seção do condutor fase, o condutor neutro tem a mesma seção que os condutores fase', 'NORMAM-202/DPC', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('33fbb2e3-ae28-4932-820c-40e2f45974e5', 'EX-529', '71c05e83-0d67-4137-b2b7-478c4241a057', 'As luzes de navegação são homologadas pela Marinha', 'RIPEAM 72 / NORMAM-202/DPC, Cap. 04.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:14', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('342986f3-dbc0-4f3e-aedc-cb8f14f10d8a', 'EX-431', 'b8ed9a31-9fa3-492f-904e-b8158a06d0da', 'Quanto aos quadros elétricos: e) os quadros elétricos são bem fixados em locais abrigados que não contêm materiais inflamáveis', 'NORMAM-202/DPC, Cap. 04, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('3443b027-7b7e-4275-bdf3-a916184578f9', 'EX-515', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'Verificar a conformidade e a data de validade de cerca de 5 anos da mangueira de gás regulamentada pela ABNT e da válvula reguladora de pressão na cozinha.', 'NORMAM-202/DPC, Cap. 04, Item 4.29', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:14', '2026-07-04 02:36:44', 1, 1, 1, 1, 1, 1),
('36b4174a-fda8-4a30-bb87-7917235aaf0f', 'EX-494', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'Os acessórios são de material resistente, não apresentam pontas ou arestas cortantes e estão instalados de modo a não interferir no uso do sanitário', 'NORMAM-202/DPC, Cap. 03, Seção II.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 1, 1, 0, 0),
('37f1473c-43ee-4e4a-88fe-8848ddfc933e', 'EX-534', '71c05e83-0d67-4137-b2b7-478c4241a057', 'Não há espaço abaixo do convés com comprimento superior a 40% do Lregra, medido a partir da parte superior do espelho ou da roda de proa, somente embarcações de passageiros e de madeira', 'NORMAM-202/DPC, Cap. 03, Seção II.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:14', '2026-07-04 04:12:38', 1, 0, 1, 1, 0, 0),
('39789262-7c98-42cc-98d1-708f7cb4a09e', 'EX-355', 'b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d', 'Modelo (Embarcações de Sobrevivência/Boias)', 'NORMAM-202/DPC, Cap. 04, Item 4.12.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('3a263732-7431-4277-812b-8204b15e1f5d', 'EX-550', '71c05e83-0d67-4137-b2b7-478c4241a057', 'Visualmente, externa e internamente, o estado das descargas, caixas de mar e toda e qualquer abertura no casco da embarcação abaixo de seu convés principal', 'NORMAM-202/DPC', 'seco', NULL, 30, 1, '2026-07-03 05:38:14', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('3e2d7077-e88b-4268-8d2f-9844471927c0', 'EX-332', 'e70f7906-4e9d-4367-b10a-2ad2a007817a', 'Radar', 'NORMAM-202/DPC, Cap. 04, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 0, 1, 0, 0),
('3f973cac-9537-4264-97a5-829b557d3fe1', 'EX-496', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'A unidade é dotada de sistema de escoamento de água tanto no boxe do chuveiro quanto no restante da área e a água do chuveiro não transborda para a parte externa do boxe', 'NORMAM-202/DPC, Cap. 03, Seção II.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 1, 1, 0, 0),
('3fe4aeef-98fe-4a5c-9544-b36d9cd831b6', 'EX-504', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'Nos sanitários coletivos as unidades sanitárias estão localizadas em compartimentos separados entre si por divisórias fixas com altura mínima de 1,8 m a partir do piso acabado, providos de portas de acesso', 'NORMAM-202/DPC, Cap. 03, Seção II.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 1, 1, 0, 0),
('3feea2e8-f5d7-4bad-88af-bdb77f4659e7', 'EX-444', 'b8ed9a31-9fa3-492f-904e-b8158a06d0da', 'g) os cabos que conectam as bombas de incêndio ao quadro elétrico de emergência são do tipo resistente ao fogo, quando passam próximos de áreas em que haja elevado risco de incêndio', 'NORMAM-202/DPC, Cap. 03, Seção III.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('40284473-c2f6-481c-8a50-8c4d3c5c8a5f', 'EX-539', '71c05e83-0d67-4137-b2b7-478c4241a057', 'Verificar se os acessos aos locais abaixo relacionados estão livres: Portas de acesso para tripulação e passageiros', 'NORMAM-202/DPC, Cap. 04, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:14', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('415b0057-acb3-4884-a57f-e8c3473b0e6f', 'EX-372', 'a5f25230-91c9-4e14-aa33-e83524d5d943', 'O sistema de bomba(s) consegue manter, pelo menos, duas tomadas de incêndio distintas com jatos d\'água nunca inferior a 15 m de alcance', 'NORMAM-202/DPC, Cap. 04, Item 4.14', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 02:36:44', 1, 1, 1, 1, 1, 1),
('4174697f-5b23-4140-ac3e-c24ac861b016', 'EX-414', '65bf89f0-f44d-4746-89f7-f530c9aa990d', 'Verificar a indicação de funcionamento da máquina motriz do(s) “thruster(s)” no passadiço', 'NORMAM-202/DPC', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('43a7583f-f880-4cf1-bb2c-1f9df67a29d5', 'EX-479', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'Os camarotes para 2 passageiros ou tripulantes possuem dimensões mínimas de 1,9 m x 1,5 m, contendo um beliche duplo', 'NORMAM-202/DPC, Cap. 03, Seção II.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 1, 1, 0, 0),
('446e0844-e616-4c5e-a073-480d64f291d7', 'EX-389', '65bf89f0-f44d-4746-89f7-f530c9aa990d', 'O arranjo físico da embarcação está de acordo com o Arranjo Geral.', 'NORMAM-202/DPC', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('450fd87a-eb93-4031-a7a1-237cbfd57c63', 'EX-483', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'Ocorre o transporte de no máximo 4 passageiros ou 9 tripulantes por camarote', 'NORMAM-202/DPC, Cap. 03, Seção II.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 1, 1, 0, 0),
('45180ac3-9c57-4200-a523-3cc0867b3a6b', 'EX-356', 'b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d', 'Classe (Embarcações de Sobrevivência/Boias)', 'NORMAM-202/DPC, Cap. 04, Item 4.12.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('45e58c28-008c-4b2f-85a0-e3c26155d21a', 'EX-449', 'b8ed9a31-9fa3-492f-904e-b8158a06d0da', 'l) todos os circuitos de luz e força, terminando num espaço que contenha tanques de combustível, ou material inflamável, são dotados de chave colocada por fora do referido espaço, para desconectar tais circuitos', 'NORMAM-202/DPC, Cap. 04, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('45f242ee-96c4-4558-8a4f-86bdac810e1a', 'EX-419', 'b8ed9a31-9fa3-492f-904e-b8158a06d0da', 'A fonte de energia elétrica principal foi dimensionada de forma que a potência aparente fornecida ao sistema seja suficiente para evitar quedas de tensões que resultem em desligamento ou oscilação de consumidores em operação devido a partida de motores elétricos de alta corrente', 'NORMAM-202/DPC, Cap. 03, Seção III.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('47b78ace-bd63-451e-ae51-001de365baaf', 'EX-333', 'e70f7906-4e9d-4367-b10a-2ad2a007817a', 'Verificar se há compasso, régua paralela, borracha, apontador e lápis disponíveis junto das cartas náuticas para uso operacional no traçado de rotas.', 'NORMAM-202/DPC', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('48501aad-989d-46d0-b36b-56274659a1de', 'EX-498', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'O lavatório é equipado com torneira de água corrente e dreno', 'NORMAM-202/DPC, Cap. 03, Seção II.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 1, 1, 0, 0),
('4a802f33-84d3-4b5f-b4a5-f8b3accb328b', 'EX-510', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'A rampa apresenta largura mínima de 0,5 m e contém balaustrada em pelo menos um dos lados com altura de 1 m ou mais', 'NORMAM-202/DPC, Cap. 04, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 1, 0, 0, 0),
('4add624f-894e-442c-bc48-1bf430208d14', 'EX-423', 'b8ed9a31-9fa3-492f-904e-b8158a06d0da', 'A fonte de energia de emergência está localizada, se possível, acima do convés contínuo superior e é de pronto acesso partindo-se do convés aberto.', 'NORMAM-202/DPC, Cap. 03, Seção IV.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('4bb658ec-309f-4338-b4e6-3a965db20dc7', 'EX-511', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'A rampa tem resistência suficiente para possibilitar a passagem das pessoas sem apresentar uma flexão significativa', 'NORMAM-202/DPC, Cap. 03, Seção V.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:14', '2026-07-04 04:12:38', 1, 0, 1, 0, 0, 0),
('4c8e77b2-3baa-4674-94e8-8d1fc6708eb1', 'EX-347', 'b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d', 'A dotação de boias salva vidas está de acordo com o quadro da NORMAM e estão em boas condições (inclusive as retinidas)', 'NORMAM-202/DPC, Cap. 04, Item 4.12.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('4dce80a9-ccad-4b7e-b61c-644a54d2978a', 'EX-552', '71c05e83-0d67-4137-b2b7-478c4241a057', 'Para as embarcações de casco de madeira, a partir da primeira vistoria, verificar o calafeto', 'NORMAM-202/DPC', 'seco', NULL, 30, 1, '2026-07-03 05:38:14', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('4e94ab4a-31be-4329-b6d5-bf08463c68c0', 'EX-337', 'b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d', 'Fabricante (Coletes salva-vidas)', 'NORMAM-202/DPC, Cap. 04, Item 4.13.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('4f0cca2c-efa9-40d3-a863-0488fea72d05', 'EX-514', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'Verificar se as tomadas elétricas instaladas nos camarotes estão em perfeito estado físico, com espelhos protetores e energizadas corretamente.', 'NORMAM-202/DPC, Cap. 03, Seção II.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:14', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('51377ad9-666c-49d1-80f0-6e43cd20c12a', 'EX-357', 'b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d', 'Número de série (se tiver) (Embarcações de Sobrevivência/Boias)', 'NORMAM-202/DPC, Cap. 04, Item 4.12.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('525223b6-395d-45f9-ae14-7a1c528215f6', 'EX-301', 'aa4a7f0d-004d-4a60-924e-693335fdd69b', 'Certificado de Segurança de Navegação', 'NORMAM-202/DPC, Cap. 08, Item 8.2.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('532445e2-6334-4633-ad34-ccc907b62a47', 'EX-380', 'a5f25230-91c9-4e14-aa33-e83524d5d943', 'Há instalada uma válvula ou dispositivo similar em cada tomada de incêndio, em posições tais que permitem o fechamento das tomadas com as bombas de incêndio em funcionamento', 'NORMAM-202/DPC, Cap. 04, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('53fd2924-3c59-434e-9b5c-3ffe3c4c1a7b', 'EX-451', 'b8ed9a31-9fa3-492f-904e-b8158a06d0da', 'n) os fios e cabos elétricos são especificados levando em consideração a capacidade de condução de corrente estabelecida pelo fabricante e a queda de tensão admissível', 'NORMAM-202/DPC, Cap. 03, Seção IV.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('544e46ae-c5da-46c2-837e-3c112db98f3e', 'EX-343', 'b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d', 'Nome da embarcação (Coletes salva-vidas)', 'NORMAM-202/DPC, Cap. 04, Item 4.13.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('548d1060-cb9d-4fac-b389-8c03c0ccea29', 'EX-322', 'e70f7906-4e9d-4367-b10a-2ad2a007817a', 'Alarme visual e sonoro de baixa pressão do óleo lubrificante do MCP e MCA com potência igual ou superior a 800 HP (597 kW)', 'NORMAM-202/DPC, Cap. 09, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 0, 1, 0, 0),
('54daf75f-7dd4-4064-84b1-dcc73e0dc352', 'EX-507', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'A unidade de chuveiro não está instalada em um sanitário coletivo, mas possui área destinada à troca de roupa', 'NORMAM-202/DPC, Cap. 03, Seção II.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 1, 1, 0, 0),
('55d90c7d-3aba-4255-970f-43ce4bcfdaff', 'EX-349', 'b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d', 'As retinidas das boias salva vidas possuem 20 m de comprimento e são feitas de material sintético e capazes de flutuar.', 'NORMAM-202/DPC, Cap. 04, Item 4.12.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('58133b9a-53e9-454e-bdb7-e5e2b7a1d90c', 'EX-365', 'a5f25230-91c9-4e14-aa33-e83524d5d943', 'A quantidade, capacidade, localização e tipo dos extintores de incêndio estão de acordo com a tabela da NORMAM. Quanto à localização deles, seguem o determinado no Plano de Segurança (se existente)', 'NORMAM-202/DPC, Cap. 04, Item 4.2), 4.2.1, m, I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 02:36:44', 1, 1, 1, 1, 1, 1),
('585d1cfe-309c-40aa-be0e-4804eda5310a', 'EX-473', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'As cadeiras deverão atender às seguintes dimensões: a) largura mínima de 0,45 m de para os bancos simples', 'NORMAM-202/DPC, Cap. 03, Seção II.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 1, 1, 0, 0),
('58e5b2aa-0482-4c9b-82a3-01c000cb1bb5', 'EX-489', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'A área mínima requerida para o transporte turísticos sem pernoite a bordo, considera a concentração de 1,5 passageiros/m². No cálculo dessas áreas estão computadas as áreas de estivagem de bagagens ou transporte de carga, nem as escadas', 'NORMAM-202/DPC, Cap. 03, Seção II.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 1, 1, 0, 0),
('5a63ec6b-964c-4a41-a1d7-53fa6980ba2e', 'EX-545', '71c05e83-0d67-4137-b2b7-478c4241a057', 'O comprimento total, boca moldada e pontal moldado do casco da embarcação estão de acordo com aqueles anotados no Memorial Descritivo', 'NORMAM-202/DPC', 'seco', NULL, 30, 1, '2026-07-03 05:38:14', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('5b125c67-ea0c-45a2-905e-437027445eb7', 'EX-439', 'b8ed9a31-9fa3-492f-904e-b8158a06d0da', 'b) os cabos são individualmente fixados a leitos ou suportes', 'NORMAM-202/DPC, Cap. 03, Seção IV.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('5b502640-d457-410d-9580-8ed3d5e95d81', 'EX-454', 'f299c8c7-4402-4efa-89c6-d5add1fa60d5', 'Toda embarcação que seja dotada de um equipamento fixo de radiocomunicação, deverá possuir a licença rádio, emitida pela Agência Nacional de Telecomunicações (ANATEL).', 'NORMAM-202/DPC, Cap. 04, Item 4.8), 4.8.1.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:04:13', 1, 0, 0, 1, 0, 0),
('5d288f7e-25e6-4e36-b8aa-093601403d54', 'EX-390', '65bf89f0-f44d-4746-89f7-f530c9aa990d', 'Verificar a limpeza dos espaços de máquinas e equipamentos. Os espaços e equipamentos de máquinas deverão ser mantidos limpos e sem vazamentos de óleos e com os estrados em bom estado de conservação', 'NORMAM-202/DPC, Cap. 09, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('5df039e6-b400-4fd0-abd2-83959587485a', 'EX-395', '65bf89f0-f44d-4746-89f7-f530c9aa990d', 'A iluminação deverá possibilitar que nenhuma área superior a 1 m² fique sem iluminação', 'NORMAM-202/DPC, Cap. 03, Seção IV.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('5f8a7cb6-2019-4100-a02f-96c076e65b5d', 'EX-366', 'a5f25230-91c9-4e14-aa33-e83524d5d943', 'Os extintores com peso bruto superior a 25 kg (quando carregados) possuem mangueiras ou esguichos adequados ou outros meios praticáveis para que atendam o espaço a que se destinam.', 'NORMAM-202/DPC, Cap. 04, Seção III.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('60f87d12-e57b-4063-ad67-b625f26f3093', 'EX-361', 'b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d', 'Dotação de artefatos pirotécnicos conforme NORMAM e catálogo de material homologado da DPC', 'NORMAM-202/DPC, Cap. 04, Seção II.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('616c56c7-ec03-4fc1-8fe8-c5a5c9321130', 'EX-369', 'a5f25230-91c9-4e14-aa33-e83524d5d943', 'As canalizações utilizadas para a distribuição de gás estão em boas condições e têm proteção adequada contra o calor e, se flexíveis, atendem às normas da Associação Brasileira de Normas Técnicas (ABNT)', 'NORMAM-202/DPC, Cap. 04, Item 4.29.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('61e1dc4b-494e-46d8-b8eb-f0f2f6f8b8b6', 'EX-488', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'Área mínima requerida em travessia com até 1 hora de duração considera a concentração de 4 passageiros por m²', 'NORMAM-202/DPC, Cap. 03, Seção II.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 1, 1, 0, 0),
('62c73930-c97e-40c7-8241-0ca46b7ce652', 'EX-551', '71c05e83-0d67-4137-b2b7-478c4241a057', 'Os perfis (transversais, longitudinais e “diagonais”) e anteparas estão devidamente soldados nos respectivos locais onde devem ser ligados', 'NORMAM-202/DPC, Cap. 04, Seção I.', 'seco', NULL, 30, 1, '2026-07-03 05:38:14', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('62ed00d9-c647-40fc-82dc-cdd0feb36475', 'EX-352', 'b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d', 'As embarcações de sobrevivência infláveis possuem o certificado de revisão dentro do prazo de validade e foram revisadas em estação de manutenção autorizada pela DPC', 'NORMAM-202/DPC', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('63ec6d70-d445-4051-9851-f414c26fb7b7', 'EX-525', '71c05e83-0d67-4137-b2b7-478c4241a057', 'A dotação das luzes atende as regras sobre o assunto para este tipo de embarcação', 'NORMAM-202/DPC', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:14', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('64264fe0-373e-4c75-82be-3665162220eb', 'EX-317', 'e70f7906-4e9d-4367-b10a-2ad2a007817a', 'Lanterna portátil com bateria recarregável ou pilhas sobressalentes', 'NORMAM-202/DPC, Cap. 03, Seção IV.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('6a368da8-410c-42df-bbc2-f58bfdb9806b', 'EX-499', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'O lavatório do tipo coletivo considera 0,6 m por pessoa', 'NORMAM-202/DPC, Cap. 03, Seção II.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 1, 1, 0, 0),
('6d6d6309-d8f2-4d2a-86a2-01e902c50df9', 'EX-400', '65bf89f0-f44d-4746-89f7-f530c9aa990d', 'Redes de descarga e aspiração da praça de máquinas conectadas ao fundo ou ao costado deverão ser metálicas', 'NORMAM-202/DPC, Cap. 03, Seção III.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('6e55abe3-ccfb-41d0-8365-c6c5f838e658', 'EX-540', '71c05e83-0d67-4137-b2b7-478c4241a057', 'Verificar se os acessos aos locais abaixo relacionados estão livres: Equipamentos de salvatagem e combate a incêndio', 'NORMAM-202/DPC, Cap. 04, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:14', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('6f4dc9b2-6ff0-4ca5-9b9f-649913e95d75', 'EX-547', '71c05e83-0d67-4137-b2b7-478c4241a057', 'Os posicionamentos dos tanques de consumíveis estão de acordo com aqueles anotados no Plano de Capacidades. Caso seja necessário, deverá ser requerida a abertura do fundo duplo', 'NORMAM-202/DPC', 'seco', NULL, 30, 1, '2026-07-03 05:38:14', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('7208560e-f098-4ed4-a6db-04e305b59b2b', 'EX-436', 'b8ed9a31-9fa3-492f-904e-b8158a06d0da', 'Os circuitos das luzes de navegação são individualmente protegidos por fusíveis ou disjuntores instalados no painel de controle ou quadro de luzes de navegação', 'RIPEAM 72 / NORMAM-202/DPC, Cap. 04.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('73540b8b-e8bd-4d3e-b08d-77ed59461bce', 'EX-503', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'A unidade sanitária é composta de um vaso sanitário de louça vitrificada, dotado de fluxo de água (descarga) para sua limpeza e acessórios', 'NORMAM-202/DPC, Cap. 03, Seção II.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 1, 1, 0, 0),
('73f848be-eb6b-4e0a-b0b1-67a6ee583f3f', 'EX-348', 'b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d', 'As boias salva vidas e sua retinida não estão presas ou amarradas à embarcação, estando apenas apoiadas em seus suportes, prontas para serem lançadas', 'NORMAM-202/DPC, Cap. 04, Item 4.12.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('76456380-e872-472e-80de-465dc9969111', 'EX-312', 'aa4a7f0d-004d-4a60-924e-693335fdd69b', 'Tabelas ou quadros no comando: - balizamento', 'NORMAM-202/DPC', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 0, 1, 0, 0),
('7661f5f9-cff5-4173-9b00-6e4337d2e45f', 'EX-330', 'e70f7906-4e9d-4367-b10a-2ad2a007817a', 'Quadro elétrico de luzes/sistemas de comunicação', 'NORMAM-202/DPC, Cap. 03, Seção IV.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 0, 1, 0, 0),
('76ed1958-0074-4027-be8a-45a0f35ebaa8', 'EX-518', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'O arranjo físico da embarcação está de acordo com o Arranjo Geral. Devem ser verificados os compartimentos em relação ao seu posicionamento e destinação', 'NORMAM-202/DPC', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:14', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('7a31837c-64ee-47e2-9f6b-d4b5cd5108b1', 'EX-403', '65bf89f0-f44d-4746-89f7-f530c9aa990d', 'Os indicadores de níveis dos tanques de óleo deverão ser dotados de válvulas (preferencialmente do tipo esfera), que deverão ser instaladas na parte inferior do respectivo indicador', 'NORMAM-202/DPC, Cap. 09, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('7a9f7a2a-d2df-43ae-bb1b-14c77c92ad36', 'EX-519', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'Todos os níveis de acomodações, de compartimentos de serviço ou da praça de máquinas possui, pelo menos, duas vias de escape amplamente separadas, provenientes de cada compartimento restrito ou grupos de compartimentos', 'NORMAM-202/DPC, Cap. 03, Seção III.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:14', '2026-07-04 04:12:38', 1, 0, 1, 1, 0, 0),
('7c148a99-d39d-4dce-9428-a65d8c9e9a39', 'EX-474', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'As cadeiras deverão atender às seguintes dimensões: b) largura mínima de 0,86 m de para os bancos duplos ou combinações desses', 'NORMAM-202/DPC, Cap. 03, Seção II.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 1, 1, 0, 0),
('7dca1f10-d3ca-4efb-aaad-05c38b4e02de', 'EX-548', '71c05e83-0d67-4137-b2b7-478c4241a057', 'Os equipamentos de carga, propulsão, energia e governo da embarcação estão de acordo com o Memorial Descritivo.', 'NORMAM-202/DPC, Cap. 03, Seção IV.', 'seco', NULL, 30, 1, '2026-07-03 05:38:14', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('7fe5827d-bbc9-4041-b881-c55b5edc1563', 'EX-394', '65bf89f0-f44d-4746-89f7-f530c9aa990d', 'As superfícies quentes deverão ser providas de proteções térmicas, a fim de minimizar o risco de queimaduras nos tripulantes', 'NORMAM-202/DPC', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('7fed81ee-7071-42cc-8f8b-eb18d5346505', 'EX-512', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'A rampa é dotada de dispositivo antiderrapante no piso (o qual poderá consistir de travessões instalados no sentido transversal com espaçamento não superior a 0,50 m)', 'NORMAM-202/DPC, Cap. 03, Seção V.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:14', '2026-07-04 04:12:38', 1, 0, 1, 0, 0, 0),
('805c0314-b1b1-4061-8c40-d25398d2e53f', 'EX-472', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'O espaço de cadeiras possui pelo menos 2 portas de acesso opostas', 'NORMAM-202/DPC, Cap. 03, Seção II.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 1, 1, 0, 0),
('83c0e7f6-6a1a-4383-ba22-9544c2018930', 'EX-555', '9e81f468-422b-40e4-8bf8-40b60a027a36', 'Estão em bom estado o(s) leme(s) e o(s) hélice(s)', 'NORMAM-202/DPC, Cap. 03, Seção III.', 'seco', NULL, 30, 1, '2026-07-03 05:38:14', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('8640b086-97b1-4cf5-b853-86b0b9504e30', 'EX-490', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'Número mínimo de aparelhos sanitários conforme tabelas regulamentares', 'NORMAM-202/DPC, Cap. 03, Seção II.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 1, 1, 0, 0),
('86ccce9f-605d-4896-871b-d7775e23014f', 'EX-491', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'Todos os banheiros são dotados de ventilação natural, através de janela ou cachimbo, ou ventilação forçada', 'NORMAM-202/DPC, Cap. 03, Seção II.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 1, 1, 0, 0),
('88af8f67-9df3-429a-8d9d-bb04d74345ec', 'EX-538', '71c05e83-0d67-4137-b2b7-478c4241a057', 'As embarcações de propriedade de órgãos públicos serão caracterizadas por meio de letras e distintivos adotados por seus respectivos órgãos.', 'NORMAM-202/DPC', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:14', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('8b5b4c03-0824-4f51-ab4b-2b1c27640900', 'EX-303', 'aa4a7f0d-004d-4a60-924e-693335fdd69b', 'O armador deverá apresentar a Provisão de Registro da Propriedade Marítima (PRPM) ou caso a embarcação não possua apresentar Documento Provisório de Propriedade (DPP).', 'NORMAM-202/DPC, Cap. 02, Item 2.1.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:04:13', 1, 1, 1, 1, 1, 1),
('8d78d063-e888-4a5b-994b-5c61e704fc44', 'EX-364', 'a5f25230-91c9-4e14-aa33-e83524d5d943', 'Na saída de cada tanque de combustível há uma válvula de fechamento capaz de interromper o fluxo da rede', 'NORMAM-202/DPC, Cap. 03, Seção II.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('8ed00d22-c8ee-40f5-be7c-64f9e9acc83d', 'EX-456', 'f299c8c7-4402-4efa-89c6-d5add1fa60d5', 'A embarcação possui a licença de estação do navio em vigor, emitida pela ANATEL', 'ANATEL / NORMAM', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-03 05:41:41', 1, 0, 0, 1, 0, 0),
('902653ef-7f5d-497e-a1f4-d78f31212d7c', 'EX-441', 'b8ed9a31-9fa3-492f-904e-b8158a06d0da', 'd) os cabos e fiação estão instalados e fixados de modo a evitar desgastes por atrito ou outra avaria', 'NORMAM-202/DPC, Cap. 03, Seção IV.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('9339e3f3-a72d-48ab-8f33-eb449e5f7395', 'EX-385', 'a5f25230-91c9-4e14-aa33-e83524d5d943', 'Todos os esguichos das mangueiras que servem às tomadas localizadas no compartimento de máquinas ou localizadas junto a tanques de carga de líquidos inflamáveis são de duplo emprego, isto é, borrifo e jato sólido, incluindo um dispositivo de fechamento', 'NORMAM-202/DPC, Cap. 04, Seção III.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('934b7190-7444-4f16-96bd-a367c6953b9c', 'EX-321', 'e70f7906-4e9d-4367-b10a-2ad2a007817a', 'Limpador de para-brisa ou vigia rotativa', 'NORMAM-202/DPC, Cap. 03, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 0, 1, 0, 0),
('94a99554-75f0-4da2-9e4f-f2c089ee8141', 'EX-327', 'e70f7906-4e9d-4367-b10a-2ad2a007817a', 'Transceptor para o Sistema de Identificação Automática homologado pela ANATEL (Automatic Identification System - AIS)', 'NORMAM-202/DPC, Cap. 04, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 0, 1, 0, 0),
('9537c200-5b45-4d8b-b670-505c5c936f79', 'EX-427', 'b8ed9a31-9fa3-492f-904e-b8158a06d0da', 'Quanto aos quadros elétricos: a) todos eles são dispostos de maneira que ofereçam fácil acesso durante a operação e ou manutenção dos equipamentos', 'NORMAM-202/DPC', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('95822d65-14fa-4d61-a80c-93b779751ed4', 'EX-528', '71c05e83-0d67-4137-b2b7-478c4241a057', 'As luzes atendem aos setores (ângulos) corretos', 'NORMAM-202/DPC', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:14', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('95f9e766-875a-48f0-93bb-149d9e29f784', 'EX-460', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'Todos os espaços destinados ao transporte e ou permanência de passageiros apresentam pés-direitos (vão entre o piso e o teto) de no mínimo 1,90 m', 'NORMAM-202/DPC, Cap. 03, Seção II.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 1, 1, 0, 0),
('990defff-5140-4561-b20a-e9a67b74e9a0', 'EX-506', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'A unidade de chuveiro é composta por um chuveiro com jato d ́água com altura de queda mínima de 1,9 m e seus acessórios, localizada em compartimento separado das demais áreas por um meio que evite respingos (box)', 'NORMAM-202/DPC, Cap. 04, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 1, 1, 0, 0),
('991a0bbc-deb5-4b81-8305-c4d102e95e50', 'EX-410', '65bf89f0-f44d-4746-89f7-f530c9aa990d', 'Motores com potência igual ou superior a 800 HP deverão ser dotados de um painel local ou remoto, com as seguintes indicações: RPM, temperatura da água de arrefecimento, pressão e temperatura do óleo lubrificante', 'NORMAM-202/DPC, Cap. 03, Seção III.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('9979e589-44dd-4790-9574-4adb561aaf7d', 'EX-461', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'A circulação nas áreas de embarque e desembarque, nos corredores e escadas é livre e independente das demais áreas da embarcação. Nas embarcações com AB maior que 50, os corredores maiores que 7 m, possui, pelo menos, 2 vias de acesso/escape', 'NORMAM-202/DPC, Cap. 04, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 1, 1, 0, 0),
('99be0275-f74e-49e6-aac2-fce3b372fecf', 'EX-517', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'Verificar a existência físico-documental e o correto preenchimento do livro de registro de lixo a bordo.', 'NORMAM-202/DPC, Cap. 09, Item 9.2', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:14', '2026-07-04 02:36:44', 1, 1, 1, 1, 1, 1),
('9ac15939-64b7-4878-8b0e-76c61bf1b55e', 'EX-553', '71c05e83-0d67-4137-b2b7-478c4241a057', 'Verificar a marcação física da régua de calado com algarismos soldados em relevo na quilha de 20 em 20 cm, pintados com cor de destaque.', 'NORMAM-202/DPC, Cap. 03, Seção I.', 'seco', NULL, 30, 1, '2026-07-03 05:38:14', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('9be9b57c-5702-4e46-9703-4414b0c8ce56', 'EX-319', 'e70f7906-4e9d-4367-b10a-2ad2a007817a', 'Binóculo 7x50', 'NORMAM-202/DPC, Cap. 04, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 0, 1, 0, 0),
('9c039242-cd6f-4dae-b2ea-628efe60d3cd', 'EX-485', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'O topo do colchão inferior está a pelo menos 0,3 m do convés (piso do camarote)', 'NORMAM-202/DPC, Cap. 03, Seção II.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 1, 1, 0, 0);
INSERT INTO `exigencias_catalogo` (`id`, `codigo_interno`, `categoria_id`, `descricao`, `item_normam`, `bloco_vistoria`, `tipo_vistoria`, `prazo_padrao_dias`, `ativo`, `criado_em`, `atualizado_em`, `aplicabilidade_a`, `aplicabilidade_b`, `aplicabilidade_c`, `aplicabilidade_d`, `aplicabilidade_e`, `aplicabilidade_f`) VALUES
('9d9028b2-a785-4a1a-bf9a-db04ae0e3e95', 'EX-331', 'e70f7906-4e9d-4367-b10a-2ad2a007817a', 'Sistema de comunicação interna, interligando, pelo menos, passadiço, praça de máquinas e compartimento da máquina do leme, propiciando troca de informações nos dois sentidos', 'NORMAM-202/DPC, Cap. 03, Seção III.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 0, 1, 0, 0),
('9dc4b5a1-2d0e-4821-8be6-c4fe3a8e8ee0', 'EX-470', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'A largura mínima do vão de acesso ao compartimento é maior ou igual à largura do corredor de acesso à abertura', 'NORMAM-202/DPC, Cap. 03, Seção V.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 1, 1, 0, 0),
('9e411c90-8ac2-4499-8ca7-2bcda5d07503', 'EX-420', 'b8ed9a31-9fa3-492f-904e-b8158a06d0da', 'Para embarcações com AB maior ou igual a 300 a fonte de emergência de energia elétrica é um gerador acionado por um motor com suprimento independente de combustível', 'NORMAM-202/DPC, Cap. 03, Seção III.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 0, 1, 0, 0),
('9e7cda40-92d3-4ba1-b90d-bca3d3071994', 'EX-328', 'e70f7906-4e9d-4367-b10a-2ad2a007817a', 'Indicador do ângulo do leme no passadiço ou comando', 'NORMAM-202/DPC, Cap. 03, Seção III.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 0, 1, 0, 0),
('a0662bd3-30ea-4206-82e2-51b4a8fa3f8a', 'EX-535', '71c05e83-0d67-4137-b2b7-478c4241a057', 'A estrutura (flutuante fixa) está sinalizada por uma luz fixa amarela, com alcance mínimo de duas milhas náuticas, estabelecida no seu tope ou em local de melhor visibilidade para o navegante.', 'NORMAM-202/DPC, Cap. 03, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:14', '2026-07-04 04:12:38', 0, 0, 1, 0, 0, 0),
('a0acbebe-660c-4f48-9da8-64bd45b91455', 'EX-345', 'b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d', 'Os coletes salva vidas estão em bom estado de conservação e com apito', 'RIPEAM 72 / NORMAM-202/DPC, Cap. 04.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('a0e3d499-45d6-4908-bed1-c1da5138641f', 'EX-416', '65bf89f0-f44d-4746-89f7-f530c9aa990d', 'Verificar se as luminárias na praça de máquinas possuem proteção antichoque física em invólucros do tipo \'tartaruga\' e se acendem normalmente.', 'NORMAM-202/DPC, Cap. 03, Seção III.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('a194202b-f4c6-4cbe-bf63-a5216292653b', 'EX-450', 'b8ed9a31-9fa3-492f-904e-b8158a06d0da', 'm) os circuitos polifásicos são distribuídos de modo a assegurar o melhor equilíbrio de cargas entre fases', 'NORMAM-202/DPC', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('a19d11c1-6666-4459-80ae-5e82c990f243', 'EX-318', 'e70f7906-4e9d-4367-b10a-2ad2a007817a', 'Apito', 'RIPEAM 72 / NORMAM-202/DPC, Cap. 04.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('a1d44288-9e8d-4cc9-abef-7bf1f296e426', 'EX-422', 'b8ed9a31-9fa3-492f-904e-b8158a06d0da', 'O grupo gerador de emergência ou a bateria de emergência foi instalado, preferencialmente, fora do compartimento das máquinas e dos geradores principais. A antepara de separação entre os compartimentos é, preferencialmente, estanque e resistente ao fogo', 'NORMAM-202/DPC, Cap. 04, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('a1f22623-e022-464e-bd02-d1e056aab5db', 'EX-482', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'Os camarotes com camas simples possuem área mínima de 2,6 m² por pessoa', 'NORMAM-202/DPC, Cap. 03, Seção II.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 1, 1, 0, 0),
('a371bf33-76aa-11f1-9eb5-0a1b2af87b16', 'CBL-001', '71c05e83-0d67-4137-b2b7-478c4241a057', 'Há passagem permanentemente desobstruída de proa à popa, que não é efetivada por cima de tampas de escotilhas. Tal passagem possui largura mínima em conformidade com o estabelecido no Anexo 3-M', 'NORMAM-202/DPC, Cap. 03, Seção I.', 'borda_livre', NULL, 30, 1, '2026-07-03 06:44:28', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('a371da38-76aa-11f1-9eb5-0a1b2af87b16', 'CBL-002', '71c05e83-0d67-4137-b2b7-478c4241a057', 'Em todas as partes expostas dos conveses principais e de superestruturas há eficientes balaustradas ou bordas falsas (que poderão ser removíveis), com altura não inferior a 1 metro (para embarcações com AB maior que 20)', 'NORMAM-202/DPC, Cap. 04, Seção I.', 'borda_livre', NULL, 30, 1, '2026-07-03 06:44:28', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('a371f205-76aa-11f1-9eb5-0a1b2af87b16', 'CBL-003', '71c05e83-0d67-4137-b2b7-478c4241a057', 'A abertura inferior da balaustrada apresenta altura menor ou igual a 230 mm e os demais vãos não poderão apresentar espaçamento superior a 380 mm. No caso de embarcações com bordas arredondadas, os suportes das balaustradas deverão ser colocados na parte plana do convés', 'NORMAM-202/DPC, Cap. 04, Seção I.', 'borda_livre', NULL, 30, 1, '2026-07-03 06:44:28', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('a3721459-76aa-11f1-9eb5-0a1b2af87b16', 'CBL-004', '71c05e83-0d67-4137-b2b7-478c4241a057', 'Para embarcações que possuam borda falsa, estas deverão possuir saídas d’água respeitando o determinado no item 0609', 'NORMAM-202/DPC', 'borda_livre', NULL, 30, 1, '2026-07-03 06:44:28', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('a3722d96-76aa-11f1-9eb5-0a1b2af87b16', 'CBL-005', '71c05e83-0d67-4137-b2b7-478c4241a057', 'Nas embarcações dos tipos A, B ou D, as vigias e olhos de boi, se existentes nos costados abaixo do convés de borda livre, deverão apresentar as seguintes características: a) ser estanque à água (ou apresentar meios que possibilitem o seu fechamento estanque à água) b) ser de construção sólida c) ser provida de vidros temperados de espessura compatível com seu diâmetro d) não podem ser do tipo “removível” e) caso rebatíveis, deverão permanecer fechadas quando em viagem, devendo haver uma placa, permanentemente fixada junto à vigia, alertando que a mesma deverá permanecer fechada quando em viagem', 'NORMAM-202/DPC, Cap. 05, Item 5.1.', 'borda_livre', NULL, 30, 1, '2026-07-03 06:44:28', '2026-07-04 04:12:38', 1, 1, 0, 1, 0, 0),
('a37244fe-76aa-11f1-9eb5-0a1b2af87b16', 'CBL-006', '71c05e83-0d67-4137-b2b7-478c4241a057', 'As aberturas no costado de embarcações dos tipos A, B ou D deverão possuir tampas estanques à água ou vigias e olhos de boi e deverão estar posicionadas de forma que sua aresta inferior esteja a, pelo menos, 300 mm acima da linha d’água carregada, em qualquer condição esperada de trim. Para as embarcações dos tipos C ou E essa distância não deverá ser inferior a 500 mm', 'NORMAM-202/DPC, Cap. 03, Seção I.', 'borda_livre', NULL, 30, 1, '2026-07-03 06:44:28', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('a3725c4c-76aa-11f1-9eb5-0a1b2af87b16', 'CBL-007', '71c05e83-0d67-4137-b2b7-478c4241a057', 'As portas externas que possibilitem, direta ou indiretamente, o acesso ao interior de qualquer compartimento localizado abaixo do convés de borda livre ou ao interior de uma superestrutura fechada, deverão ter uma soleira mínima de 150 mm (260 mm para embarcações que operam em área 2)', 'NORMAM-202/DPC, Cap. 05, Item 5.1.', 'borda_livre', NULL, 30, 1, '2026-07-03 06:44:28', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('a37275b5-76aa-11f1-9eb5-0a1b2af87b16', 'CBL-008', '71c05e83-0d67-4137-b2b7-478c4241a057', 'Os escotilhões e as aberturas de escotilha possuem braçola de pelo menos 150 mm de altura (260 mm para embarcações que operam em área 2) e são dotados de tampas que possam ser fixadas às braçolas. As embarcações dos tipos “C” e “E” estão dispensadas da obrigatoriedade de possuírem tampas de escotilha ou dos escotilhões', 'NORMAM-202/DPC, Cap. 03, Seção I.', 'borda_livre', NULL, 30, 1, '2026-07-03 06:44:28', '2026-07-04 04:12:38', 1, 1, 0, 1, 0, 1),
('a3728c4f-76aa-11f1-9eb5-0a1b2af87b16', 'CBL-009', '71c05e83-0d67-4137-b2b7-478c4241a057', 'As tampas das aberturas de escotilha, dos escotilhões e seus respectivos dispositivos de fechamento têm resistência suficiente que permite satisfazer as condições de estanqueidade previstas para o tipo de embarcação considerada e apresenta todos os elementos necessários que asseguram a estanqueidade', 'NORMAM-202/DPC, Cap. 03, Seção III.', 'borda_livre', NULL, 30, 1, '2026-07-03 06:44:28', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('a372a38d-76aa-11f1-9eb5-0a1b2af87b16', 'CBL-010', '71c05e83-0d67-4137-b2b7-478c4241a057', 'Os suspiros externos, situados acima do convés de borda livre, deverão apresentar as seguintes caraterísticas: a) extremidade superior do suspiro em forma de “U” invertido ou com arranjo que proteja a sua abertura da entrada de água proveniente das intempéries; b) distância vertical entre o ponto a partir da qual a água efetivamente tem acesso ao tanque ou compartimento abaixo e o convés onde o suspiro se encontra instalado maior ou igual a 450 mm (760 mm nos conveses de borda livre e 450 mm nos demais conveses para embarcações que operam em área 2)', 'NORMAM-202/DPC, Cap. 05, Item 5.1.', 'borda_livre', NULL, 30, 1, '2026-07-03 06:44:28', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('a372bc98-76aa-11f1-9eb5-0a1b2af87b16', 'CBL-011', '71c05e83-0d67-4137-b2b7-478c4241a057', 'Dispositivos de iluminação e ou ventilação natural (alboios) de compartimentos situados abaixo do convés de borda livre, que estão situados imediatamente acima do referido convés, deverão: a) ser estanque ao tempo (ou dispor de meios que possibilitem o seu fechamento estanque ao tempo) b) ser dotado de vidros com espessura compatível com sua área e máxima dimensão linear c) apresentar braçolas com, pelo menos, 150 mm de altura (260 mm para embarcações que operam em área 2)', 'NORMAM-202/DPC, Cap. 05, Item 5.1.', 'borda_livre', NULL, 30, 1, '2026-07-03 06:44:28', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('a372d307-76aa-11f1-9eb5-0a1b2af87b16', 'CBL-012', '71c05e83-0d67-4137-b2b7-478c4241a057', 'Os dutos de ventilação ou exaustão destinados aos espaços situados abaixo do convés de borda livre deverão apresentar a borda inferior de sua extremidade externa com pelo menos 450 mm de altura acima do referido convés (760 mm para embarcações que operam em área 2)', 'NORMAM-202/DPC, Cap. 05, Item 5.1.', 'borda_livre', NULL, 30, 1, '2026-07-03 06:44:28', '2026-07-04 04:12:38', 1, 1, 0, 1, 0, 1),
('a372e880-76aa-11f1-9eb5-0a1b2af87b16', 'CBL-013', '71c05e83-0d67-4137-b2b7-478c4241a057', 'Para embarcações que operam em área 2, as venezianas instaladas em anteparas ou portas externas, destinadas à ventilação de compartimentos situados sob o convés de borda livre ou superestruturas fechadas, e que não possuam meios efetivos de fechamento que as tornem estanques ao tempo, deverão possuir altura mínima de 760 mm', 'NORMAM-202/DPC, Cap. 05, Item 5.1.', 'borda_livre', NULL, 30, 1, '2026-07-03 06:44:28', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('a373033e-76aa-11f1-9eb5-0a1b2af87b16', 'CBL-014', '71c05e83-0d67-4137-b2b7-478c4241a057', 'A extremidade junto ao costado dos tubos de descarga, provenientes de espaços situados abaixo do convés de borda livre ou de superestruturas fechadas, deverá ser dotada de válvulas de retenção e fechamento (combinadas ou não). Os meios disponíveis para operação de válvula de fechamento deverão ser facilmente acessíveis e estar sempre disponíveis (ver exigência abaixo)', 'NORMAM-202/DPC, Cap. 05, Item 5.1.', 'borda_livre', NULL, 30, 1, '2026-07-03 06:44:28', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('a3731baa-76aa-11f1-9eb5-0a1b2af87b16', 'CBL-015', '71c05e83-0d67-4137-b2b7-478c4241a057', 'Quando a descarga se dá por gravidade e a distância vertical entre o ponto de descarga no costado e a extremidade superior do tubo for maior ou igual a 1,20 m (2,0 m para embarcações que operam em área 2) as válvulas poderão ser de fechamento sem retenção (ver exigência acima)', 'NORMAM-202/DPC', 'borda_livre', NULL, 30, 1, '2026-07-03 06:44:28', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('a3733364-76aa-11f1-9eb5-0a1b2af87b16', 'CBL-016', '71c05e83-0d67-4137-b2b7-478c4241a057', 'As descargas de gases provenientes de motores de combustão interna que sejam posicionadas na popa ou nos costados, mesmo quando associadas à descarga de água de refrigeração dos motores (“descarga molhada”), estão dispensadas da obrigatoriedade da instalação de válvulas de retenção ou fechamento, mas deverão atender aos seguintes requisitos: a) deverão ser flangeadas no casco b) beverão ser de aço ou material equivalente nas proximidades do casco', 'NORMAM-202/DPC, Cap. 03, Seção III.', 'borda_livre', NULL, 30, 1, '2026-07-03 06:44:28', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('a373534c-76aa-11f1-9eb5-0a1b2af87b16', 'CBL-017', '71c05e83-0d67-4137-b2b7-478c4241a057', 'Embarcações dos tipos D e E que operem em área 2 deverão possuir altura mínima de proa de acordo com o item 0619', 'NORMAM-202/DPC', 'borda_livre', NULL, 30, 1, '2026-07-03 06:44:28', '2026-07-04 04:12:38', 0, 0, 0, 1, 1, 0),
('a373c1f4-76aa-11f1-9eb5-0a1b2af87b16', 'CBL-018', '71c05e83-0d67-4137-b2b7-478c4241a057', 'O Disco de Plimsoll está posicionado conforme Notas para a Marcação da Borda Livre.', 'NORMAM-202/DPC, Cap. 05, Item 5.1.', 'borda_livre', NULL, 30, 1, '2026-07-03 06:44:28', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('a3a06b64-50be-420a-9892-2c189dcbe724', 'EX-426', 'b8ed9a31-9fa3-492f-904e-b8158a06d0da', 'As baterias deverão: c) atender a uma altura mínima de 40 cm do piso, quando fixadas em conveses situados abaixo do convés principal', 'NORMAM-202/DPC, Cap. 03, Seção IV.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('a431a945-f958-40bc-9491-058a3d643c98', 'EX-464', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'Há espaço livre para circulação nos bordos da embarcação, ao longo de todos os espaços para redes. Essa circulação deverá apresenta largura mínima de 800 mm por bordo', 'NORMAM-202/DPC, Cap. 03, Seção II.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 1, 1, 0, 0),
('a4f04bb2-0533-498c-970e-73a3c5de19e2', 'EX-412', '65bf89f0-f44d-4746-89f7-f530c9aa990d', 'Verificar o funcionamento do alarme de nível alto de esgoto (visual e ou sonoro), emitido na praça de máquinas e no comando – para embarcações com AB maior que 20', 'NORMAM-202/DPC, Cap. 03, Seção III.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 0, 1, 0, 0),
('a73b0ca4-6bbb-41d6-ac23-410beabbe8b9', 'EX-309', 'aa4a7f0d-004d-4a60-924e-693335fdd69b', 'Certificado de conformidade para transporte de produtos químicos perigosos a granel (se aplicável)', 'NORMAM-202/DPC', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('a9916551-a7e8-49b4-aa43-ee43ed71e60f', 'EX-466', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'A área mínima requerida para o transporte de passageiros em redes considera a concentração de 1 passageiro por m², sem rede em cima de rede. No cálculo dessa área não estão computadas as áreas de circulação, de embarque e desembarque, de estivagem de bagagens ou transporte de carga, nem corredores ou escadas', 'NORMAM-202/DPC, Cap. 03, Seção II.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 1, 1, 0, 0),
('ac2e0924-d475-4f40-8429-553d94cbd7c1', 'EX-445', 'b8ed9a31-9fa3-492f-904e-b8158a06d0da', 'h) nos compartimentos e locais onde existe depósito de materiais inflamáveis, os interruptores, tomadas de correntes, luminárias e demais equipamentos elétricos são à prova de explosão', 'NORMAM-202/DPC, Cap. 04, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('ad528287-01ba-4c8f-ac0a-0203113ba8c6', 'EX-465', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'Ocorre o transporte simultâneo de passageiros em redes e em bancos laterais, junto aos bordos, e o limite de espaço para redes se iniciar a não menos de 1,70m da face interna da balaustrada do convés considerado', 'NORMAM-202/DPC, Cap. 04, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 1, 1, 0, 0),
('ad8b2645-95b8-4f61-a654-5610123e893e', 'EX-404', '65bf89f0-f44d-4746-89f7-f530c9aa990d', 'As tubulações advindas dos tanques de óleo, por intermédio da qual o óleo é conduzido às máquinas principais ou auxiliares, deverão ser de material metálico ou material resistente ao fogo e possuir válvula de fechamento rápido, o qual deverá ser testado', 'NORMAM-202/DPC, Cap. 04, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('ae76d3fb-35cf-4108-81f2-4d0e8a579cab', 'EX-418', 'b8ed9a31-9fa3-492f-904e-b8158a06d0da', 'A fonte de energia elétrica principal consegue manter em funcionamento todos os serviços essenciais independentemente do sentido e da velocidade de rotação das máquinas principais e do eixo propulsor', 'NORMAM-202/DPC, Cap. 04, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('af6b1cb2-e94a-452c-a083-9b7e2f41ff69', 'EX-392', '65bf89f0-f44d-4746-89f7-f530c9aa990d', 'Motores cujo sistema de arrefecimento seja constituído por ventiladores deverão ter os mesmos providos de proteção', 'NORMAM-202/DPC, Cap. 03, Seção III.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('b16a6bde-ff11-49be-aa7e-ad733190b39c', 'EX-360', 'b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d', 'Porto de inscrição (Embarcações de Sobrevivência/Boias)', 'NORMAM-202/DPC, Cap. 04, Item 4.12.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('b2594475-d99b-47e9-b28f-ef970b9ef621', 'EX-554', '71c05e83-0d67-4137-b2b7-478c4241a057', 'Acompanhar fisicamente a medição por ultrassom feita por engenheiro qualificado contratado, incluindo o lixamento de um ponto redondo de ~5 cm de diâmetro nas chapas.', 'NORMAM-202/DPC, Cap. 03, Seção I.', 'seco', NULL, 30, 1, '2026-07-03 05:38:14', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('b27da535-c866-4c52-9a83-b3e5b10072e0', 'EX-320', 'e70f7906-4e9d-4367-b10a-2ad2a007817a', 'Prumo de mão', 'NORMAM-202/DPC, Cap. 04, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 0, 1, 0, 0),
('b3e0478a-37ea-4ecf-a8f7-d81e816f1a25', 'EX-408', '65bf89f0-f44d-4746-89f7-f530c9aa990d', 'Toda tubulação de gás (não de cozinha), combustível, óleo lubrificante, substancias inflamáveis em geral e fiações não poderá distar menos que 200 mm das tubulações de descarga ou de quaisquer superfícies em alta temperatura', 'NORMAM-202/DPC, Cap. 04, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('b3f0b053-6c41-42f4-adb3-a3f0d76c9e05', 'EX-531', '71c05e83-0d67-4137-b2b7-478c4241a057', 'A antepara de colisão de vante está posicionada entre 5 e 8% do Lregra, a partir da parte superior do espelho ou da roda de proa', 'NORMAM-202/DPC', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:14', '2026-07-04 04:12:38', 1, 0, 1, 1, 0, 0),
('b56def21-6b53-42cc-a16b-35f5a0a63c59', 'EX-476', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'As cadeiras deverão atender às seguintes dimensões: d) distância mínima de 0,90 m entre os encostos dos assentos montados frente a frente, ou entre o encosto e uma antepara, ou outra divisão que por ventura exista à frente do assento', 'NORMAM-202/DPC, Cap. 03, Seção II.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 1, 1, 0, 0),
('b5ce3089-e78e-4390-99bb-e8855acd1ffd', 'EX-397', '65bf89f0-f44d-4746-89f7-f530c9aa990d', 'Todo espaço de máquinas deverá ter ventilação (forçada ou natural) apropriada ao funcionamento dos equipamentos', 'NORMAM-202/DPC', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('b5f8b4f6-cb8d-432f-b7cd-52bdb1121ae8', 'EX-478', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'Os corredores de circulação e ou acesso aos camarotes apresentam largura mínima de 0,8 m para um comprimento máximo de 10 m. Quando o comprimento dos corredores internos excede a 10 m, a largura mínima é acrescida de 0,05 m para cada 2 m ou fração a mais no comprimento, até o máximo de 1 m', 'NORMAM-202/DPC, Cap. 04, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 1, 1, 0, 0),
('b6db0410-2703-4196-993a-ed9f04038200', 'EX-533', '71c05e83-0d67-4137-b2b7-478c4241a057', 'Há antepara a vante da praça de máquinas, somente embarcações de passageiros', 'NORMAM-202/DPC, Cap. 03, Seção III.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:14', '2026-07-04 04:12:38', 1, 0, 1, 1, 0, 0),
('b7545aa5-51fe-44d7-9513-fd491720ace9', 'EX-302', 'aa4a7f0d-004d-4a60-924e-693335fdd69b', 'Cartão de Tripulação de Segurança', 'NORMAM-202/DPC, Cap. 04, Item 4.2), 4.2.1, m, III', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 02:36:44', 1, 1, 1, 1, 1, 1),
('b8b68324-6f6c-48d4-af7f-84d98d71eca7', 'EX-516', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'Verificar a afixação de placa educativa em local visível no convés com os dizeres: \'Não jogue lixo no rio, deposite seu lixo aqui\'.', 'NORMAM-202/DPC, Cap. 09, Item 9.2', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:14', '2026-07-04 02:36:44', 1, 1, 1, 1, 1, 1),
('bac0b5fb-e1ef-4ce4-b171-36716b176f2e', 'EX-424', 'b8ed9a31-9fa3-492f-904e-b8158a06d0da', 'As baterias deverão: a) ser instaladas em locais não habitados, arejados e abrigados', 'NORMAM-202/DPC, Cap. 04, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('bac38230-26ef-427d-b223-0d1b0bc96b03', 'EX-487', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'Nos camarotes há ventilação natural por janela ou alboio, dando para o exterior da embarcação, com uma abertura mínima de 0,1 m² por janela ou alboio. A ventilação natural pode ser substituída por ventilação forçada através de ventilador e ou ar condicionado', 'NORMAM-202/DPC, Cap. 03, Seção II.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 1, 1, 0, 0),
('bb1b61cc-c7fb-4a39-a7b2-749267af3ac9', 'EX-447', 'b8ed9a31-9fa3-492f-904e-b8158a06d0da', 'j) não são utilizadas extensões elétricas (caso usadas numa necessidade eventual, verificar a capacidade de corrente e, dependendo da distância, a queda de tensão)', 'NORMAM-202/DPC', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('bc4bc5e4-a100-4aa5-a3f0-6f0d7405fb64', 'EX-386', 'a5f25230-91c9-4e14-aa33-e83524d5d943', 'Os esguichos não têm menos de 12 mm de diâmetro', 'NORMAM-202/DPC, Cap. 04, Seção III.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 0, 1, 0, 0),
('bd328ebf-7ae2-4e72-8d75-c1519b935d1b', 'EX-536', '71c05e83-0d67-4137-b2b7-478c4241a057', 'A embarcação deverá ser marcada de modo visível e durável, com letras e algarismos de tamanho apropriado às dimensões da embarcação, com letras de, no mínimo, 10 cm, na popa, o nome da embarcação juntamente com o porto de inscrição e, na proa, o nome da embarcação nos dois bordos', 'NORMAM-202/DPC, Cap. 02, Seção II.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:14', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('bd5d3265-5bb4-4d45-a4a3-592dbaeafc7b', 'EX-351', 'b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d', 'Os aparelhos flutuantes estão estivados de modo a flutuarem livremente em caso de naufrágio', 'NORMAM-202/DPC', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('be414d13-fba6-478b-b244-8cae54e7532e', 'EX-513', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'Verificar o estado físico de conservação, higiene e limpeza dos colchões fornecidos nos camarotes.', 'NORMAM-202/DPC, Cap. 03, Seção II.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:14', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('bed32fa9-00cb-4821-a92a-f9d913ef261e', 'EX-425', 'b8ed9a31-9fa3-492f-904e-b8158a06d0da', 'As baterias deverão: b) ser mantidas devidamente fixadas e com seus bornes de ligação sem azinhavre e protegidos por material isolante', 'NORMAM-202/DPC, Cap. 03, Seção IV.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('c01f90ce-7dc7-494d-ac0d-631ac1833ac4', 'EX-391', '65bf89f0-f44d-4746-89f7-f530c9aa990d', 'Quaisquer polias, correias e demais partes móveis utilizadas para acionamento de máquinas e ou mecanismos deverão ser dotadas de dispositivos adequados de proteção para as pessoas', 'NORMAM-202/DPC, Cap. 04, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('c0b150ff-dbbe-4b9e-9228-6e66a738b87b', 'EX-481', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'Os camarotes destinados a mais de 4 pessoas em beliches possuem área mínima de 1,5 m² por pessoa', 'NORMAM-202/DPC, Cap. 04, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:38', 1, 0, 1, 1, 0, 0),
('c1d3a7cb-333e-4e09-96ef-098c409c7c6e', 'EX-546', '71c05e83-0d67-4137-b2b7-478c4241a057', 'O material empregado na construção da embarcação está de acordo com aquele mencionado no Memorial Descritivo', 'NORMAM-202/DPC', 'seco', NULL, 30, 1, '2026-07-03 05:38:14', '2026-07-04 04:12:38', 1, 1, 1, 1, 1, 1),
('c1e33d68-30aa-4c63-8059-7c6f66ce4dad', 'EX-497', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'O sanitário coletivo mínimo é formado por uma unidade sanitária e lavatório, tendo área mínima de 1,26 m² e pode ser usado simultaneamente por mais de uma pessoa', 'NORMAM-202/DPC, Cap. 04, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 0, 1, 1, 0, 0),
('c231dec1-4488-4a8c-a9bc-3633e4f940c3', 'EX-523', '71c05e83-0d67-4137-b2b7-478c4241a057', 'As janelas ou escotilhas, indicadas no Plano de Segurança como via de escape, possuem um vão livre mínimo não inferior a 600 x 600 mm, se instaladas em conveses e 600 x 800 mm, se instaladas em anteparas', 'NORMAM-202/DPC, Cap. 04, Item 4.2), 4.2.1, m, I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:14', '2026-07-04 02:36:44', 1, 0, 1, 1, 0, 0),
('c33725e8-227b-4dd2-9f32-e9e083b8d97c', 'EX-462', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'Os corredores ou passarelas externas de circulação e acesso com até 10 m de comprimento apresentam largura mínima de 650 mm. Como o comprimento excede a 10 m, a largura mínima é acrescida de 50 mm para cada 2 m ou fração de comprimento, até no máximo de 800 mm', 'NORMAM-202/DPC, Cap. 03, Seção V.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 0, 1, 1, 0, 0),
('c3c80149-529a-42c6-8a26-36c464054bca', 'EX-396', '65bf89f0-f44d-4746-89f7-f530c9aa990d', 'Toda lâmpada deverá ser protegida contra choques, eficazmente, por luminárias', 'NORMAM-202/DPC', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 1, 1, 1, 1, 1),
('c85334c5-8f56-4ee3-be27-b6783951d5c3', 'EX-480', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'Os camarotes para 3 ou 4 passageiros ou tripulantes possuem dimensões mínimas de 1,9 m x 3,0 m, contendo uma cama e um beliche duplo ou dois beliches duplos', 'NORMAM-202/DPC, Cap. 03, Seção II.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 0, 1, 1, 0, 0),
('c8d265a4-62cc-4153-b226-337375cd363d', 'EX-526', '71c05e83-0d67-4137-b2b7-478c4241a057', 'As alturas das luzes de navegação estão de acordo com as normas específicas sobre o assunto', 'RIPEAM 72 / NORMAM-202/DPC, Cap. 04.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:14', '2026-07-04 04:12:39', 1, 1, 1, 1, 1, 1),
('ca1c1aed-7e2a-4d54-92cd-7567486150c7', 'EX-375', 'a5f25230-91c9-4e14-aa33-e83524d5d943', 'Nas DEMAIS embarcações, as tomadas (hidrantes) deverão estar posicionadas de modo a propiciar, pelo menos, dois jatos d\'água não provenientes da mesma tomada de incêndio', 'NORMAM-202/DPC, Cap. 04, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 1, 1, 1, 1, 1),
('cad656d0-6125-4f9c-be76-9d9ce5e03c99', 'EX-556', '9e81f468-422b-40e4-8bf8-40b60a027a36', 'Realizar verificação física detalhada de todo o hélice, leme, bucha e eixo propulsor da embarcação em seco, buscando desgastes, trincas ou folgas anômalas.', 'NORMAM-202/DPC, Cap. 03, Seção III.', 'seco', NULL, 30, 1, '2026-07-03 05:38:14', '2026-07-04 04:12:39', 1, 1, 1, 1, 1, 1),
('ccaeea91-05ea-4864-a770-5c9b98ae8f48', 'EX-342', 'b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d', 'Tamanho (apenas para os coletes salva vidas)', 'NORMAM-202/DPC, Cap. 04, Item 4.13.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 1, 1, 1, 1, 1),
('cd2dfb47-4f43-46b4-a27b-1e977ae0f5f2', 'EX-409', '65bf89f0-f44d-4746-89f7-f530c9aa990d', 'Motores providos de sistema de abertura das válvulas de admissão e descarga, por intermédio de balancins, deverão ter seus tuchos de acionamento protegidos', 'NORMAM-202/DPC, Cap. 03, Seção III.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 1, 1, 1, 1, 1),
('ce1ba98a-6d1a-4140-a789-ca3efa885333', 'EX-402', '65bf89f0-f44d-4746-89f7-f530c9aa990d', 'Os tanques de óleo situados no interior da Praça de Maquinas deverão ser dotados de suspiros independentes e cuja saída deverá estar localizada em área externa', 'NORMAM-202/DPC, Cap. 09, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 1, 1, 1, 1, 1),
('ce50512f-13f2-4b0e-a2f7-bc1ae1e5bffd', 'EX-340', 'b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d', 'Número de série (se tiver) (Coletes salva-vidas)', 'NORMAM-202/DPC, Cap. 04, Item 4.13.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 1, 1, 1, 1, 1),
('cf097e63-f9a6-4408-ae6e-766baddc6322', 'EX-477', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'Os espaços de cadeiras apresentam ventilação natural permanente para o exterior da embarcação, tendo como meio de fechamento sanefas ou janelas móveis. No caso de janela móvel, a área mínima de ventilação é de 40% do vão da abertura', 'NORMAM-202/DPC, Cap. 03, Seção II.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 0, 1, 1, 0, 0),
('cf34c2da-207c-4d4c-a185-8c19374aaedf', 'EX-323', 'e70f7906-4e9d-4367-b10a-2ad2a007817a', 'Alarme visual e sonoro de alta temperatura da água de resfriamento do MCP e MCA com potência igual ou superior a 800 HP (597 kW)', 'NORMAM-202/DPC', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 0, 0, 1, 0, 0),
('d11e0a27-5ba2-4d6f-9d9d-1415a92db143', 'EX-353', 'b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d', 'Número do certificado de homologação pela DPC (Embarcações de Sobrevivência/Boias)', 'NORMAM-202/DPC, Cap. 04, Item 4.12.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 1, 1, 1, 1, 1),
('d171a5f8-0d0a-4279-9688-68856ea403e3', 'EX-505', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'Os acessos às unidades sanitárias são efetuados através de vão mínimo de 1,8 x 0,55 m, dotados de portas com dispositivo de travamento interno e apresenta uma altura livre de, no máximo 0,3 m e, no mínimo 0,1 m, entre a porta e o piso', 'NORMAM-202/DPC', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 0, 1, 1, 0, 0),
('d35a46ed-2908-4475-897d-fe955538be34', 'EX-453', 'b8ed9a31-9fa3-492f-904e-b8158a06d0da', 'Na instalação elétrica não existe fios soltos, desencapados ou qualquer outra condição que possa vir a provocar um curto-circuito', 'NORMAM-202/DPC', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 1, 1, 1, 1, 1),
('d3653240-9326-4f99-a41f-fccfd35e75b2', 'EX-341', 'b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d', 'Data de fabricação (Coletes salva-vidas)', 'NORMAM-202/DPC, Cap. 04, Item 4.13.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 1, 1, 1, 1, 1),
('d6c54388-c992-4021-8a62-0a5400976539', 'EX-509', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'Há pelo menos uma rampa, adequada às características da embarcação e ao local onde se efetua o embarque/desembarque de passageiros, para facilitar a entrada e saída dos passageiros', 'NORMAM-202/DPC, Cap. 03, Seção II.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 0, 1, 0, 0, 0),
('d7a3466c-1c51-4001-a537-7f02912156a8', 'EX-406', '65bf89f0-f44d-4746-89f7-f530c9aa990d', 'Toda fiação elétrica dos motores principais, auxiliares e equipamentos acessórios deverá ser protegida por eletrodutos ou acondicionada em “chicotes” apropriados', 'NORMAM-202/DPC, Cap. 04, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 1, 1, 1, 1, 1),
('d970e4db-5964-4eaa-add3-dee2763eab6e', 'EX-313', 'aa4a7f0d-004d-4a60-924e-693335fdd69b', 'Tabelas ou quadros no comando: - sinais sonoros e luminosos', 'NORMAM-202/DPC, Cap. 04, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 0, 0, 1, 0, 0),
('da44538d-807e-40ef-9c99-0bb3c1f0c7a7', 'EX-532', '71c05e83-0d67-4137-b2b7-478c4241a057', 'A antepara de colisão de ré está colocada de forma que limita o tubo telescópico em um espaço estanque à água de volume moderado', 'NORMAM-202/DPC, Cap. 03, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:14', '2026-07-04 04:12:39', 1, 0, 1, 1, 0, 0),
('da807bea-cb86-4be2-8655-97320c8fd059', 'EX-379', 'a5f25230-91c9-4e14-aa33-e83524d5d943', 'Não são usados para as redes de incêndio e para as tomadas de incêndio, materiais cujas características são prejudicadas pelo calor (como plásticos e PVC).', 'NORMAM-202/DPC, Cap. 04, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 1, 1, 1, 1, 1),
('dab5c2ba-432e-47f3-a6ab-0a0e67b420a5', 'EX-315', 'aa4a7f0d-004d-4a60-924e-693335fdd69b', 'As embarcações que transportem passageiros deverão ter afixadas, em local visível aos passageiros, uma placa contendo o número de inscrição da embarcação, peso máximo de carga, número máximo de passageiros por convés que a embarcação está autorizada a transportar e número do telefone da OM em cuja jurisdição a embarcação estiver operando', 'NORMAM-202/DPC, Cap. 03, Seção II.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 0, 0, 1, 0, 0),
('dbc42c9d-c0f2-44bc-ad57-b78a7b4e0ab3', 'EX-377', 'a5f25230-91c9-4e14-aa33-e83524d5d943', 'Nas DEMAIS embarcações, próximas à entrada da praça de máquinas (lado externo), deverão ser previstas uma tomada de incêndio e uma estação de incêndio com uma ou mais seções de mangueira e um aplicador de neblina', 'NORMAM-202/DPC, Cap. 04, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 1, 1, 1, 1, 1),
('dbe76a3f-4454-4836-a600-1c3c99c06475', 'EX-458', 'f299c8c7-4402-4efa-89c6-d5add1fa60d5', 'A embarcação, que navega sob jurisdição da Capitania dos Portos de Barra Bonita, possui o equipamento AIS em pleno funcionamento', 'ANATEL / NORMAM', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-03 05:41:41', 1, 0, 0, 1, 0, 0),
('e125df21-a446-4bef-9486-35a165b9220b', 'EX-326', 'e70f7906-4e9d-4367-b10a-2ad2a007817a', 'Agulha giroscópica ou magnética', 'NORMAM-202/DPC, Cap. 04, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 0, 0, 1, 0, 0),
('e1a77c79-63a6-4d5e-8906-64f06dee4a9a', 'EX-432', 'b8ed9a31-9fa3-492f-904e-b8158a06d0da', 'Quanto aos quadros elétricos: f) os quadros elétricos não estão localizados a vante da antepara de colisão', 'NORMAM-202/DPC', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 1, 1, 1, 1, 1),
('e204d705-f37b-46c6-88b6-5d46f506064b', 'EX-543', '71c05e83-0d67-4137-b2b7-478c4241a057', 'Verificar se os acessos aos locais abaixo relacionados estão livres: Porões de carga', 'NORMAM-202/DPC, Cap. 04, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:14', '2026-07-04 04:12:39', 1, 1, 1, 1, 1, 1),
('e26e80f5-8422-4fb7-8199-6669ac222815', 'EX-308', 'aa4a7f0d-004d-4a60-924e-693335fdd69b', 'Certificado de conformidade para transporte de gases liquefeitos a granel (se aplicável)', 'NORMAM-202/DPC', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 1, 1, 1, 1, 1),
('e27dc4c7-dd3b-4269-bc57-601cbb159450', 'EX-354', 'b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d', 'Fabricante (Embarcações de Sobrevivência/Boias)', 'NORMAM-202/DPC, Cap. 04, Item 4.12.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 1, 1, 1, 1, 1),
('e2dc9cdc-437a-4c3a-8710-ce6bb9d4c3f6', 'EX-411', '65bf89f0-f44d-4746-89f7-f530c9aa990d', 'Qualquer sistema de monitoramento e ou controle de equipamentos instalado no passadiço deverá ser dotado de placas identificadoras, assim como provido de uma iluminação apropriada', 'NORMAM-202/DPC, Cap. 03, Seção IV.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 1, 1, 1, 1, 1),
('e402c282-bbf7-4213-b997-761e8e06227a', 'EX-311', 'aa4a7f0d-004d-4a60-924e-693335fdd69b', 'Tabelas ou quadros no comando: - sinais de salvamento', 'NORMAM-202/DPC, Cap. 04, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 0, 0, 1, 0, 0),
('e4382149-9351-4ffe-8e6c-004723fdb8a0', 'EX-448', 'b8ed9a31-9fa3-492f-904e-b8158a06d0da', 'k) os acessórios de iluminação são instalados de maneira tal que evitam aumentos de temperatura que possam danificar cabos e fiação e impeçam que o material situado nos arredores se torne excessivamente quente', 'NORMAM-202/DPC, Cap. 03, Seção IV.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 1, 1, 1, 1, 1),
('e4c70296-da8c-4f2d-a1e5-a20287dddb1c', 'EX-433', 'b8ed9a31-9fa3-492f-904e-b8158a06d0da', 'Quanto aos quadros elétricos: g) estão limpos e mantidos', 'NORMAM-202/DPC', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 1, 1, 1, 1, 1),
('e4db742c-931a-43ef-bff3-287ef5d42c1f', 'EX-521', '71c05e83-0d67-4137-b2b7-478c4241a057', 'Acima do convés aberto mais baixo, as vias de escape são escadas, portas ou janelas ou uma combinação delas, dando para um convés aberto', 'NORMAM-202/DPC, Cap. 04, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:14', '2026-07-04 04:12:39', 1, 0, 1, 1, 0, 0),
('e556f7ad-a680-44ce-861d-f051aac27a86', 'EX-417', 'b8ed9a31-9fa3-492f-904e-b8158a06d0da', 'A fonte de energia principal tem capacidade suficiente para suprir a carga necessária para manter a embarcação em plenas condições de operação e habitabilidade, levando-se em consideração os fatores de potência, de demanda e a simultaneidade das cargas', 'NORMAM-202/DPC, Cap. 03, Seção IV.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 1, 1, 1, 1, 1),
('e55e3316-1841-41f7-8eca-de405ef9e180', 'EX-388', 'a5f25230-91c9-4e14-aa33-e83524d5d943', 'Somente deverão ser utilizadas redes de aço e acessórios de materiais resistentes ao fogo junto ao casco, nos embornais, nas descargas sanitárias e em outras descargas situadas abaixo do convés estanque.', 'NORMAM-202/DPC, Cap. 04, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 1, 1, 1, 1, 1),
('e64d7ec0-fccc-4d7b-91f0-043098347422', 'EX-307', 'aa4a7f0d-004d-4a60-924e-693335fdd69b', 'Certificado de Borda Livre, quando aplicável', 'NORMAM-202/DPC, Cap. 05, Item 5.1.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 1, 1, 1, 1, 1),
('e70fad1d-6ee7-4ceb-9c23-d101f192e2a3', 'EX-363', 'a5f25230-91c9-4e14-aa33-e83524d5d943', 'Nenhum tanque ou rede de combustível está posicionado em local onde qualquer derramamento ou vazamento dele proveniente, venha constituir risco de incêndio pelo contato com superfícies aquecidas ou equipamentos elétricos', 'NORMAM-202/DPC, Cap. 03, Seção II.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 1, 1, 1, 1, 1),
('e8afc2e7-7783-4ea7-9e95-fccf3e8499dd', 'EX-415', '65bf89f0-f44d-4746-89f7-f530c9aa990d', 'Verificar se os empurradores possuem placa física identificadora com o número do motor ou, se inexistente, exigir Nota Fiscal ou Recibo de Compra e Venda.', 'NORMAM-202/DPC, Cap. 03, Seção III.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 1, 1, 1, 1, 1),
('e9226bc3-3b12-417e-946f-18c0176792e0', 'EX-324', 'e70f7906-4e9d-4367-b10a-2ad2a007817a', 'Sistema de comunicação que possibilita ao comando divulgar informações gerais por intermédio de alto-falantes nos locais destinados aos passageiros (para embarcações com mais de 100 passageiros)', 'NORMAM-202/DPC, Cap. 04, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 0, 0, 1, 0, 0),
('eae082a5-c90e-4a46-8922-aadbe8cdeea0', 'EX-471', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'As portas de acesso estão posicionadas de forma que uma pessoa não necessita se deslocar mais de 13 m em linha reta, a partir de qualquer posição do espaço de cadeiras, para alcançar uma das portas', 'NORMAM-202/DPC, Cap. 04, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 0, 1, 1, 0, 0),
('eb283686-11d5-4d21-aa6a-46fa76015422', 'EX-469', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'Todos os corredores têm livre acesso às saídas do compartimento', 'NORMAM-202/DPC, Cap. 03, Seção V.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 0, 1, 1, 0, 0),
('eba785cf-5373-49b1-9f45-74624533cd4e', 'EX-495', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'As unidades de banheiro têm área maior ou igual a 1,3 m², sendo que as medidas do boxe são de 0,7 x 0,7 m ou maiores. A largura da unidade de banheiro é maior ou igual a 0,8 m', 'NORMAM-202/DPC, Cap. 03, Seção II.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 0, 1, 1, 0, 0),
('ec47b315-cde2-4d25-955b-8ef469a3db99', 'EX-457', 'f299c8c7-4402-4efa-89c6-d5add1fa60d5', 'A licença-rádio deverá ser mantida a bordo da embarcação.', 'ANATEL / NORMAM', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-03 05:41:41', 1, 0, 0, 1, 0, 0),
('ec652099-4966-4fea-94f7-0c41adde6ccb', 'EX-306', 'aa4a7f0d-004d-4a60-924e-693335fdd69b', 'Certificado ou notas de arqueação', 'NORMAM-202/DPC, Cap. 06, Item 6.1.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 1, 1, 1, 1, 1),
('ecf0c6d1-02a0-479f-9b92-982e68083700', 'EX-430', 'b8ed9a31-9fa3-492f-904e-b8158a06d0da', 'Quanto aos quadros elétricos: d) se a fonte de emergência de energia for constituída por bateria de acumuladores, ela não está instalada no mesmo compartimento do quadro elétrico de emergência', 'NORMAM-202/DPC, Cap. 03, Seção IV.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 1, 1, 1, 1, 1),
('ecf9e38b-e522-425b-9daa-e0323352bab8', 'EX-522', '71c05e83-0d67-4137-b2b7-478c4241a057', 'Não há corredores sem saída com mais de 7 m de comprimento (um corredor sem saída é um corredor ou parte de um corredor a partir do qual só há uma via de escape)', 'NORMAM-202/DPC, Cap. 04, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:14', '2026-07-04 04:12:39', 1, 0, 1, 1, 0, 0),
('ee4ccc12-4cbd-45d3-a239-fd8d70eb6e7b', 'EX-310', 'aa4a7f0d-004d-4a60-924e-693335fdd69b', 'Tabelas ou quadros no comando: - regras de governo e navegação', 'NORMAM-202/DPC', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 0, 0, 1, 0, 0),
('eed4571e-88f9-4f4a-833b-bc4cfbb5dc2a', 'EX-304', 'aa4a7f0d-004d-4a60-924e-693335fdd69b', 'Caderneta de Inscrição e Registro de cada tripulante (CIR)', 'NORMAM-202/DPC', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 1, 1, 1, 1, 1),
('ef865d12-3b6a-4d96-b9e0-a32b12b89725', 'EX-455', 'f299c8c7-4402-4efa-89c6-d5add1fa60d5', 'Os equipamentos de radiocomunicação funcionam e podem operar na freqüência de 156,8 Mhz (canal 16)', 'NORMAM-202/DPC, Cap. 04, Item 4.8), 4.8.1.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 02:36:44', 1, 0, 0, 1, 0, 0),
('efb0d9fe-b5be-4c6d-817d-edd230a5c0a9', 'EX-336', 'b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d', 'Número do certificado de homologação pela DPC (Coletes salva-vidas)', 'NORMAM-202/DPC, Cap. 04, Item 4.13.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 1, 1, 1, 1, 1),
('f10786b6-5cfd-4656-8789-db333c13166f', 'EX-346', 'b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d', 'Os coletes salva vidas estão estivados de maneira a serem prontamente utilizados, em local visível, bem sinalizado e de fácil acesso', 'NORMAM-202/DPC, Cap. 04, Item 4.13.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 1, 1, 1, 1, 1),
('f1305470-ca00-414f-9f1b-8082fc6cb2a6', 'EX-493', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'Os compartimentos sanitários são dotados de meios de drenagem no ponto mais baixo do piso. As unidades de chuveiro possuem dreno específico', 'NORMAM-202/DPC, Cap. 04, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 0, 1, 1, 0, 0),
('f199c93c-ce4a-424f-8ea6-da60372de2e4', 'EX-524', '71c05e83-0d67-4137-b2b7-478c4241a057', 'As rotas de escape estão marcadas por setas indicadoras, pintadas em cor contrastante, indicando \'Saída de Emergência\'. A marcação permite, aos passageiros e tripulantes, a identificação de todas as rotas de evacuação e a rápida identificação das saídas', 'NORMAM-202/DPC, Cap. 03, Seção II.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:14', '2026-07-04 04:12:39', 1, 0, 1, 1, 0, 0),
('f1abbac0-6684-47e0-b67e-0c850ad377ae', 'EX-549', '71c05e83-0d67-4137-b2b7-478c4241a057', 'O casco e os conveses estão em condições satisfatórias, sem deterioração acentuada, não apresentando mossas, trincas ou furos por corrosão', 'NORMAM-202/DPC', 'seco', NULL, 30, 1, '2026-07-03 05:38:14', '2026-07-04 04:12:39', 1, 1, 1, 1, 1, 1),
('f3fa1e72-5aa5-46d3-bde1-caa01704b771', 'EX-440', 'b8ed9a31-9fa3-492f-904e-b8158a06d0da', 'c) os eletrodutos estão instalados com suficiente caimento e furos para dar drenagem e evitar o acúmulo d’água', 'NORMAM-202/DPC', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 1, 1, 1, 1, 1),
('f42be128-51c4-4240-bd88-d0031f30b2e3', 'EX-468', '9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'Os corredores internos dos salões de cadeiras têm largura mínima de 800mm para um comprimento máximo equivalente a 20 filas de cadeiras consecutivas. Para um comprimento superior, a largura mínima é acrescida de 100 mm para cada 10 filas ou fração de cadeiras a mais', 'NORMAM-202/DPC, Cap. 04, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 0, 1, 1, 0, 0),
('f5a3cf01-94bc-4944-a3c1-4db1811db59b', 'EX-399', '65bf89f0-f44d-4746-89f7-f530c9aa990d', 'Não deverá haver vazamentos ou descargas de gases provenientes da queima de combustão no interior dos espaços de máquinas ou outros compartimentos quaisquer.', 'NORMAM-202/DPC, Cap. 04, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 1, 1, 1, 1, 1),
('f6b03730-2355-4d50-82d9-573150d8ec4f', 'EX-442', 'b8ed9a31-9fa3-492f-904e-b8158a06d0da', 'e) as extremidades e junções de todos os condutores são feitas de modo a serem conservadas as propriedades originais elétricas e mecânicas', 'NORMAM-202/DPC, Cap. 04, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 1, 1, 1, 1, 1),
('f6b5c4dc-45a7-4eb8-b2f0-92e2f01171a2', 'EX-530', '71c05e83-0d67-4137-b2b7-478c4241a057', 'O ponto de alagamento progressivo (qualquer acesso ao casco não estanque ao tempo) está localizado exatamente no local informado no projeto – geralmente no Estudo de Estabilidade ou nas Curvas', 'NORMAM-202/DPC, Cap. 03, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:14', '2026-07-04 04:12:39', 1, 1, 1, 1, 1, 1),
('f91ac072-d60c-4502-8590-472181dc8a53', 'EX-378', 'a5f25230-91c9-4e14-aa33-e83524d5d943', 'As mangueiras e seus acessórios ficam acondicionados em cabides ou estações de incêndio (armário pintado de vermelho, dotado em sua antepara frontal de uma porta)', 'NORMAM-202/DPC', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 1, 1, 1, 1, 1),
('f95612f7-d307-4cdf-8a02-41124b7bf5e2', 'EX-305', 'aa4a7f0d-004d-4a60-924e-693335fdd69b', 'Regras para evitar abalroamento – RIPEAM (exceto para embarcações sem propulsão quando rebocadas/empurradas)', 'RIPEAM 72 / NORMAM-202/DPC, Cap. 04.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 0, 0, 1, 0, 1),
('fa01a553-9f0b-4eb4-a2fa-fe53004c7e78', 'EX-434', 'b8ed9a31-9fa3-492f-904e-b8158a06d0da', 'Os circuitos de distribuição, geradores e alimentadores são individualmente protegidos por disjuntores ou fusíveis contra sobrecarga e curto-circuito', 'NORMAM-202/DPC, Cap. 03, Seção IV.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 1, 1, 1, 1, 1),
('fa3a530e-d204-4571-b0ef-3902a2ff8f50', 'EX-383', 'a5f25230-91c9-4e14-aa33-e83524d5d943', 'O diâmetro das mangueiras de incêndio não é inferior a 38 mm (1,5\'\')', 'NORMAM-202/DPC', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 0, 0, 1, 0, 0),
('fd836b06-765d-4b56-a022-699234aab52b', 'EX-435', 'b8ed9a31-9fa3-492f-904e-b8158a06d0da', 'Os transformadores são protegidos com disjuntores no primário', 'NORMAM-202/DPC, Cap. 03, Seção IV.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 1, 1, 1, 1, 1),
('fd9cb55e-6e74-4f21-b89a-3c77685d0862', 'EX-370', 'a5f25230-91c9-4e14-aa33-e83524d5d943', 'As embarcações propulsadas empregadas no transporte de passageiros com AB maior que 10 e as demais embarcações propulsadas com AB maior que 20 deverão ser dotadas de pelo menos uma bomba de esgoto com vazão total maior ou igual a 15 m³/h', 'NORMAM-202/DPC, Cap. 04, Seção I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 0, 0, 1, 0, 1);
INSERT INTO `exigencias_catalogo` (`id`, `codigo_interno`, `categoria_id`, `descricao`, `item_normam`, `bloco_vistoria`, `tipo_vistoria`, `prazo_padrao_dias`, `ativo`, `criado_em`, `atualizado_em`, `aplicabilidade_a`, `aplicabilidade_b`, `aplicabilidade_c`, `aplicabilidade_d`, `aplicabilidade_e`, `aplicabilidade_f`) VALUES
('fee925e7-19cc-4f27-839e-d320076cd13f', 'EX-421', 'b8ed9a31-9fa3-492f-904e-b8158a06d0da', 'A fonte de energia elétrica de emergência é independente da fonte principal e com capacidade de alimentar por uma hora todos os sistemas elétricos e consumidores necessários à segurança de passageiros e tripulação', 'NORMAM-202/DPC, Cap. 03, Seção IV.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 04:12:39', 1, 1, 1, 1, 1, 1),
('ff928f0e-e467-4d37-b188-fe991b28568e', 'EX-300', 'aa4a7f0d-004d-4a60-924e-693335fdd69b', 'Plano de Segurança', 'NORMAM-202/DPC, Cap. 04, Item 4.2), 4.2.1, m, I.', 'flutuando', NULL, 30, 1, '2026-07-03 05:38:13', '2026-07-04 02:36:44', 1, 0, 0, 1, 0, 0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `exigencias_categorias`
--

CREATE TABLE `exigencias_categorias` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT (uuid()),
  `nome` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `exigencias_categorias`
--

INSERT INTO `exigencias_categorias` (`id`, `nome`, `criado_em`, `atualizado_em`) VALUES
('65bf89f0-f44d-4746-89f7-f530c9aa990d', 'Praça de Máquinas', '2026-07-03 05:36:20', '2026-07-03 05:36:20'),
('71c05e83-0d67-4137-b2b7-478c4241a057', 'Casco, Estrutura e Porão', '2026-07-03 05:36:20', '2026-07-03 05:36:20'),
('9755fe45-1e6f-4fa7-b589-942d8a6f07d2', 'Habitabilidade e Cozinha', '2026-07-03 05:36:20', '2026-07-03 05:36:20'),
('9e81f468-422b-40e4-8bf8-40b60a027a36', 'Sistemas de Propulsão e Governo', '2026-07-03 05:36:20', '2026-07-03 05:36:20'),
('a5f25230-91c9-4e14-aa33-e83524d5d943', 'Combate a Incêndio', '2026-07-03 05:36:20', '2026-07-03 05:36:20'),
('aa4a7f0d-004d-4a60-924e-693335fdd69b', 'Documentação e Certificados', '2026-07-03 05:36:20', '2026-07-03 05:36:20'),
('b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d', 'Salvatagem e Segurança', '2026-07-03 05:36:20', '2026-07-03 05:36:20'),
('b8ed9a31-9fa3-492f-904e-b8158a06d0da', 'Setor Elétrico', '2026-07-03 05:36:20', '2026-07-03 05:36:20'),
('e70f7906-4e9d-4367-b10a-2ad2a007817a', 'Sistemas de Navegação e Comando', '2026-07-03 05:36:20', '2026-07-03 05:36:20'),
('f299c8c7-4402-4efa-89c6-d5add1fa60d5', 'Rádio e Comunicações', '2026-07-03 05:36:20', '2026-07-03 05:36:20');

-- --------------------------------------------------------

--
-- Estrutura para tabela `exportacoes_documentos`
--

CREATE TABLE `exportacoes_documentos` (
  `id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `solicitado_por` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('AGUARDANDO','PROCESSANDO','CONCLUIDA','FALHA','EXPIRADA') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'AGUARDANDO',
  `categorias_json` json NOT NULL,
  `filtros_json` json DEFAULT NULL,
  `caminho_arquivo` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nome_arquivo` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tamanho_bytes` bigint UNSIGNED DEFAULT NULL,
  `quantidade_arquivos` int UNSIGNED NOT NULL DEFAULT '0',
  `sha256` char(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `erro` text COLLATE utf8mb4_general_ci,
  `solicitado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `iniciado_em` datetime DEFAULT NULL,
  `concluido_em` datetime DEFAULT NULL,
  `expira_em` datetime DEFAULT NULL,
  `baixado_em` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `financeiro_comprovantes`
--

CREATE TABLE `financeiro_comprovantes` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT (uuid()),
  `lancamento_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nome_original` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nome_arquivo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `caminho` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `mime_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tamanho` int UNSIGNED NOT NULL DEFAULT '0',
  `criado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `financeiro_contas_bancarias`
--

CREATE TABLE `financeiro_contas_bancarias` (
  `id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `nome` varchar(120) COLLATE utf8mb4_general_ci NOT NULL,
  `banco` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `agencia` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `conta` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `financeiro_historico_baixas`
--

CREATE TABLE `financeiro_historico_baixas` (
  `id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `lancamento_id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `valor_pago` decimal(10,2) NOT NULL,
  `data_pagamento` date NOT NULL,
  `forma_pagamento` enum('a_vista','parcelado','boleto','pix') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `conta_bancaria_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `criado_por` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `financeiro_lancamentos`
--

CREATE TABLE `financeiro_lancamentos` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT (uuid()),
  `cliente_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `escritorio_id` char(36) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '00000000-0000-4000-8000-000000000100',
  `responsavel_usuario_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `proposta_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tipo` enum('RECEITA','DESPESA') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `descricao` varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `valor_original` decimal(10,2) NOT NULL,
  `saldo_devedor` decimal(10,2) NOT NULL,
  `status` enum('PENDENTE','PARCIAL','PAGO','CANCELADO') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'PENDENTE',
  `frequencia` enum('unica','mensal','trimestral','anual') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'unica',
  `data_vencimento` date DEFAULT NULL,
  `data` date DEFAULT NULL,
  `categoria` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `observacoes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `criado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `financeiro_metas_mensais`
--

CREATE TABLE `financeiro_metas_mensais` (
  `id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `competencia` date NOT NULL,
  `escritorio_id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `usuario_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `valor` decimal(12,2) NOT NULL DEFAULT '0.00',
  `mensagem` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `logs_atividade`
--

CREATE TABLE `logs_atividade` (
  `id` int NOT NULL,
  `usuario_id` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `acao` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `descricao` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `ordens_servico`
--

CREATE TABLE `ordens_servico` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT (uuid()),
  `numero` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `agendamento_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `proposta_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `embarcacao_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `cliente_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `vistoriador_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tipo_vistoria` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `data_vistoria` date NOT NULL,
  `hora_vistoria` time DEFAULT NULL,
  `local` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `contato_nome` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `contato_telefone` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('pendente','em_andamento','executado','cancelado') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pendente',
  `observacoes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `criado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `portal_auditoria`
--

CREATE TABLE `portal_auditoria` (
  `id` bigint UNSIGNED NOT NULL,
  `cliente_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `perfil` enum('proprietario','despachante') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `evento` enum('LOGIN_SUCESSO','LOGIN_FALHA','VISUALIZACAO','DOWNLOAD') COLLATE utf8mb4_general_ci NOT NULL,
  `embarcacao_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `documento_tipo` varchar(40) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `documento_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sucesso` tinyint(1) NOT NULL DEFAULT '1',
  `detalhe` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ip` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_agent` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `propostas`
--

CREATE TABLE `propostas` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT (uuid()),
  `numero` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `cliente_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `armador_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `operador_nome` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `responsavel_fechamento_nome` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `responsavel_fechamento_telefone` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `data_emissao` date NOT NULL,
  `data_validade` date DEFAULT NULL,
  `parcelas` tinyint UNSIGNED NOT NULL DEFAULT '3',
  `forma_pagamento` enum('a_vista','parcelado','boleto','pix') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'parcelado',
  `valor_total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `valor_entrada` decimal(12,2) NOT NULL DEFAULT '0.00',
  `desconto_percentual` decimal(5,2) NOT NULL DEFAULT '0.00',
  `desconto_valor` decimal(12,2) NOT NULL DEFAULT '0.00',
  `observacoes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `status` enum('rascunho','enviada','aprovada','recusada','cancelada','assinada') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'rascunho',
  `criado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `escritorio_id` char(36) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '00000000-0000-4000-8000-000000000100',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `token_assinatura` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `assinado` tinyint(1) DEFAULT '0',
  `assinatura_imagem` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `assinatura_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `assinatura_em` datetime DEFAULT NULL,
  `assinante_nome` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `assinante_documento` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `assinatura_ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
) ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `propostas_embarcacoes`
--

CREATE TABLE `propostas_embarcacoes` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT (uuid()),
  `proposta_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `embarcacao_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `propostas_servicos`
--

CREATE TABLE `propostas_servicos` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT (uuid()),
  `proposta_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `servico_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `embarcacao_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `preco_aplicado` decimal(12,2) NOT NULL DEFAULT '0.00',
  `quantidade` tinyint UNSIGNED NOT NULL DEFAULT '1',
  `subtotal` decimal(12,2) GENERATED ALWAYS AS ((`preco_aplicado` * `quantidade`)) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `responsaveis_assinatura`
--

CREATE TABLE `responsaveis_assinatura` (
  `id` int NOT NULL,
  `nome_completo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cpf_cnpj` varchar(18) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `usuario_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cargo_titulo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `registro_profissional` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `assinatura_arquivo` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `assinatura_hash` char(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `assinatura_atualizada_em` datetime DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `responsaveis_assinatura`
--

INSERT INTO `responsaveis_assinatura` (`id`, `nome_completo`, `cpf_cnpj`, `cargo_titulo`, `registro_profissional`, `assinatura_arquivo`, `assinatura_hash`, `assinatura_atualizada_em`, `ativo`, `created_at`, `updated_at`) VALUES
(2, 'Victal Donanzan', '383.034.518-63', 'Engenheiro Naval', 'CREA: 22.537', 'storage/private/assinaturas_responsaveis/2/20260720_100109_90048b1dd4c51d95.png', '09da23f7c13fbfbf42c88f65ff2208903086c13f3ed5022813784e45a94bdd13', '2026-07-20 13:01:09', 1, '2026-07-02 04:58:28', '2026-07-20 13:01:09'),
(5, 'João Responsável', NULL, 'Engenheiro Naval', '123456', NULL, NULL, NULL, 0, '2026-07-02 17:39:46', '2026-07-17 06:33:34'),
(6, 'João Responsável', NULL, 'Engenheiro Naval', '123456', NULL, NULL, NULL, 0, '2026-07-02 17:43:53', '2026-07-07 21:13:57');

-- --------------------------------------------------------

--
-- Estrutura para tabela `sequenciais_documentos`
--

CREATE TABLE `sequenciais_documentos` (
  `tipo_documento` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `ano` int NOT NULL,
  `ultimo_numero` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `servicos`
--

CREATE TABLE `servicos` (
  `id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `nome` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_general_ci,
  `certificado_modelo` enum('CSN','CNBL','CNARQ') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `preco_padrao` decimal(12,2) NOT NULL DEFAULT '0.00',
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `criado_por` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `servicos`
--

INSERT INTO `servicos` (`id`, `nome`, `descricao`, `preco_padrao`, `ativo`, `criado_por`, `created_at`, `updated_at`) VALUES
('a1d980bd-6ebc-11f1-86ce-7e17ff5f90bf', 'Análise de Planos Ec1', 'Analise técnica de planos de embarcação“ Etapa 1', 2500.00, 1, NULL, '2026-06-23 04:33:07', '2026-06-29 06:13:39'),
('a1d98b0e-6ebc-11f1-86ce-7e17ff5f90bf', 'Análise de Planos Ec2', 'Analise técnica de planos de embarcação“ Etapa 2', 2500.00, 1, NULL, '2026-06-23 04:33:07', '2026-06-29 06:14:16'),
('a1d98d8e-6ebc-11f1-86ce-7e17ff5f90bf', 'Vistoria Inicial Seco', 'Vistoria inicial realizada com embarcação em seco (estaleiro/dique)', 3500.00, 1, NULL, '2026-06-23 04:33:07', '2026-06-29 06:16:55'),
('a1d98e55-6ebc-11f1-86ce-7e17ff5f90bf', 'Vistoria Inicial Flutuando', 'Vistoria inicial realizada com embarcação flutuando', 3500.00, 1, NULL, '2026-06-23 04:33:07', '2026-06-29 06:16:44'),
('a1d98eaf-6ebc-11f1-86ce-7e17ff5f90bf', 'Vistoria Inicial de Borda Livre', 'Vistoria inicial para certificação de borda livre', 2800.00, 1, NULL, '2026-06-23 04:33:07', '2026-06-29 06:16:31'),
('a1d98ef1-6ebc-11f1-86ce-7e17ff5f90bf', 'Vistoria Inicial de Arqueação', 'Vistoria inicial para calculo e certificação de ?????? bruta', 3200.00, 1, NULL, '2026-06-23 04:33:07', '2026-06-29 06:16:14'),
('a1d98f2e-6ebc-11f1-86ce-7e17ff5f90bf', 'Acompanhamento de Ultrassom', 'Acompanhamento de ensaios de ultrassom em casco/estruturas', 1800.00, 1, NULL, '2026-06-23 04:33:07', '2026-06-29 06:13:07'),
('a1d98f6a-6ebc-11f1-86ce-7e17ff5f90bf', 'Vistoria Anual', 'Vistoria anual obrigatória para manutenção de certificados', 2200.00, 1, NULL, '2026-06-23 04:33:07', '2026-06-29 06:15:13'),
('a1d99130-6ebc-11f1-86ce-7e17ff5f90bf', 'Vistoria Anual Periódica', 'Vistoria anual periodica conforme regulamento da Capitania', 2500.00, 1, NULL, '2026-06-23 04:33:07', '2026-06-29 06:15:50'),
('a1d991e9-6ebc-11f1-86ce-7e17ff5f90bf', 'Vistoria Intermediária', 'Vistoria intermediaria de meio-ciclo entre renovações', 3000.00, 1, NULL, '2026-06-23 04:33:07', '2026-06-29 06:17:26'),
('a1d992d7-6ebc-11f1-86ce-7e17ff5f90bf', 'Licença Provisória', 'Emissão de licença provisória para navegação', 1500.00, 1, NULL, '2026-06-23 04:33:07', '2026-06-29 06:14:47');

UPDATE `servicos` SET `certificado_modelo` = 'CSN' WHERE `nome` IN ('Vistoria Inicial Seco', 'Vistoria Inicial Flutuando');
UPDATE `servicos` SET `certificado_modelo` = 'CNBL' WHERE `nome` = 'Vistoria Inicial de Borda Livre';
UPDATE `servicos` SET `certificado_modelo` = 'CNARQ' WHERE `id` = 'a1d98ef1-6ebc-11f1-86ce-7e17ff5f90bf' OR `nome` = 'Vistoria Inicial de Arqueação';

-- --------------------------------------------------------

--
-- Estrutura para tabela `tipos_embarcacao`
--

CREATE TABLE `tipos_embarcacao` (
  `id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `nome` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `ativo` tinyint(1) DEFAULT '1',
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `tipos_embarcacao`
--

INSERT INTO `tipos_embarcacao` (`id`, `nome`, `ativo`, `criado_em`) VALUES
('06a95b60-75d0-11f1-98f0-5ed0db5eacb7', 'Balsa', 1, '2026-07-02 04:39:35'),
('06a95eb2-75d0-11f1-98f0-5ed0db5eacb7', 'Empurrador', 1, '2026-07-02 04:39:35'),
('06a95ffa-75d0-11f1-98f0-5ed0db5eacb7', 'Lancha', 1, '2026-07-02 04:39:35'),
('06a96069-75d0-11f1-98f0-5ed0db5eacb7', 'Rebocador', 1, '2026-07-02 04:39:35'),
('06a96097-75d0-11f1-98f0-5ed0db5eacb7', 'Flutuante', 1, '2026-07-02 04:39:35'),
('06a960bd-75d0-11f1-98f0-5ed0db5eacb7', 'Draga', 1, '2026-07-02 04:39:35'),
('06a960df-75d0-11f1-98f0-5ed0db5eacb7', 'Pontão', 1, '2026-07-02 04:39:35'),
('06a96100-75d0-11f1-98f0-5ed0db5eacb7', 'Bote', 1, '2026-07-02 04:39:35'),
('06a96123-75d0-11f1-98f0-5ed0db5eacb7', 'Navio', 1, '2026-07-02 04:39:35'),
('06a96149-75d0-11f1-98f0-5ed0db5eacb7', 'Iate', 1, '2026-07-02 04:39:35'),
('06a96169-75d0-11f1-98f0-5ed0db5eacb7', 'Chata', 1, '2026-07-02 04:39:35'),
('06a96189-75d0-11f1-98f0-5ed0db5eacb7', 'Ferry Boat', 1, '2026-07-02 04:39:35');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` char(36) COLLATE utf8mb4_general_ci NOT NULL DEFAULT (uuid()),
  `nome` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `senha_hash` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `cargo` enum('ADMIN','VENDEDOR','VISTORIADOR','ANALISTA') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'VISTORIADOR',
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `excluido_em` datetime DEFAULT NULL,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `acesso_documentacao` tinyint(1) DEFAULT '0',
  `acesso_financeiro` tinyint(1) DEFAULT '0',
  `escritorio_id` char(36) COLLATE utf8mb4_general_ci DEFAULT '00000000-0000-4000-8000-000000000100'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha_hash`, `cargo`, `ativo`, `excluido_em`, `criado_em`, `atualizado_em`, `acesso_documentacao`, `acesso_financeiro`, `escritorio_id`) VALUES
('11111111-1111-1111-1111-111111111111', 'Carlos Mendes', 'excluido.11111111111111111111111111111111@local.invalid', '$2y$10$SjdkE2qA2s5C1UHZo/V4yOaIYQ1RWLsybsGP7Vf1cLmGJYmeflMFi', 'VISTORIADOR', 0, '2026-07-19 02:27:38', '2026-06-24 17:33:03', '2026-07-19 02:27:38', 0, 0, '00000000-0000-4000-8000-000000000100'),
('1c015cb0-3187-4068-bc6d-06585521e165', 'anabe', 'excluido.1c015cb031874068bc6d06585521e165@local.invalid', '$2y$10$YTFhG9EMyJrdZssxn5aXuelGURp2nULigmFHIKVGdqFiQxbzAXIBu', 'VENDEDOR', 0, '2026-07-19 02:27:32', '2026-06-27 03:51:48', '2026-07-19 02:27:32', 1, 1, '00000000-0000-4000-8000-000000000100'),
('22222222-2222-2222-2222-222222222222', 'Ana Paula Silva', 'excluido.22222222222222222222222222222222@local.invalid', '$2y$10$t5EgpXiQyTOM/NZjPcdREep5XsL.u.y8OztQGiCY1EF55VlLklvvO', 'VISTORIADOR', 0, '2026-07-19 02:27:28', '2026-06-24 17:33:03', '2026-07-19 02:27:28', 0, 0, '00000000-0000-4000-8000-000000000100'),
('33333333-3333-3333-3333-333333333333', 'Roberto Lima', 'excluido.33333333333333333333333333333333@local.invalid', '$2y$10$lH9jpywZL4ueeCNV1kxUXe4Ayl51gRcqjTqNLiU0S5aW0DA4IqD1y', 'VISTORIADOR', 0, '2026-07-19 02:27:48', '2026-06-24 17:33:03', '2026-07-19 02:27:48', 0, 0, '00000000-0000-4000-8000-000000000100'),
('3774d80c-2574-470e-88a9-9781936c6de3', 'Any', 'excluido.3774d80c2574470e88a99781936c6de3@local.invalid', '$2y$10$TzfH61SflMPiQpW4MFIP5OTf2/khZ51Q66XX1HiNl3SjgtruZj8au', 'VISTORIADOR', 0, '2026-07-19 02:27:35', '2026-06-23 22:51:43', '2026-07-19 02:27:35', 1, 0, '00000000-0000-4000-8000-000000000100'),
('74e02f95-fbe6-42f3-bedf-f8535e4d13aa', 'Rosano Souza', 'excluido.74e02f95fbe642f3bedff8535e4d13aa@local.invalid', '$2y$10$pEGJqFBciTy5Zm4.xv1CTOi9eF29nXW4NWRaifY/h4f74SWAJd0EG', 'VISTORIADOR', 0, '2026-07-19 02:27:52', '2026-06-11 21:44:56', '2026-07-19 02:27:52', 0, 0, '00000000-0000-4000-8000-000000000100'),
('95eb5557-65e8-11f1-85ef-047c16b568a3', 'Administrador', 'excluido.95eb555765e811f185ef047c16b568a3@local.invalid', '$2y$10$WDtKPgD44yf3STmx0SPfOuiy2AgKuWi5EEFozzSOfvZ3vLGGLW7Pq', 'ADMIN', 0, '2026-07-19 02:28:49', '2026-06-11 19:55:04', '2026-07-19 02:28:49', 0, 0, '00000000-0000-4000-8000-000000000100'),
('9cd7e53a-da9d-4f2b-9b32-328be32da2f0', 'itamar', 'analista@teste.com', '$2y$10$PftOdZbu7u.NZ65.r1NpO.s4jBDtysUHLwrLVH0jxALGB/VlWvn.2', 'ANALISTA', 1, NULL, '2026-07-16 15:38:12', '2026-07-20 15:13:20', 0, 0, '1801fc90-5734-417c-acf2-fed7399a23f1'),
('d2a16613-dfa4-4948-8de4-8c802abdf394', 'Neto', 'teste1@teste.com', '$2y$10$nho8g81ikeWtP9U7G3Ft7uHZOARhHcfD.oVRW5/hMKQnZ7MRsLxOy', 'VISTORIADOR', 1, NULL, '2026-07-07 21:10:28', '2026-07-20 15:13:20', 1, 0, '1801fc90-5734-417c-acf2-fed7399a23f1'),
('dd121661-feb4-42f6-895a-68eb0608d1e4', 'teste admin', 'teste@teste.com', '$2y$10$eK05TTRWPQmp7ldYEALHrOMRSVKUGMo6yqVv3kCU0yYiOz5KzBWw6', 'ADMIN', 1, NULL, '2026-07-05 13:39:17', '2026-07-20 15:13:20', 0, 0, '1801fc90-5734-417c-acf2-fed7399a23f1'),
('e5c68a85-c920-4b11-bc93-9343d9d94f14', 'vistoriador teste', 'excluido.e5c68a85c9204b11bc939343d9d94f14@local.invalid', '$2y$10$LdMu1ZxZP.ysBC10FSV/TeWm5yuEeZkyenLH5fxHKx4QA6MbAPGeW', 'VISTORIADOR', 0, '2026-07-19 02:28:20', '2026-07-02 15:06:59', '2026-07-19 02:28:20', 0, 0, '00000000-0000-4000-8000-000000000100');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuario_escritorios`
--

CREATE TABLE `usuario_escritorios` (
  `usuario_id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `escritorio_id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `principal` tinyint(1) NOT NULL DEFAULT '0',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuario_escritorios`
--

INSERT INTO `usuario_escritorios` (`usuario_id`, `escritorio_id`, `principal`, `criado_em`) VALUES
('9cd7e53a-da9d-4f2b-9b32-328be32da2f0', '1801fc90-5734-417c-acf2-fed7399a23f1', 1, '2026-07-20 15:13:20'),
('d2a16613-dfa4-4948-8de4-8c802abdf394', '1801fc90-5734-417c-acf2-fed7399a23f1', 1, '2026-07-20 15:13:20'),
('dd121661-feb4-42f6-895a-68eb0608d1e4', '1801fc90-5734-417c-acf2-fed7399a23f1', 1, '2026-07-20 15:13:20');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuario_perfis`
--

CREATE TABLE `usuario_perfis` (
  `usuario_id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `perfil` enum('ADMIN','VENDEDOR','VISTORIADOR','ANALISTA') COLLATE utf8mb4_general_ci NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuario_perfis`
--

INSERT INTO `usuario_perfis` (`usuario_id`, `perfil`, `criado_em`) VALUES
('9cd7e53a-da9d-4f2b-9b32-328be32da2f0', 'ANALISTA', '2026-07-16 15:38:12'),
('d2a16613-dfa4-4948-8de4-8c802abdf394', 'VISTORIADOR', '2026-07-14 22:05:44'),
('dd121661-feb4-42f6-895a-68eb0608d1e4', 'ADMIN', '2026-07-14 22:05:44'),
('dd121661-feb4-42f6-895a-68eb0608d1e4', 'VISTORIADOR', '2026-07-20 13:31:05');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuario_permissoes`
--

CREATE TABLE `usuario_permissoes` (
  `usuario_id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `permissao` varchar(80) COLLATE utf8mb4_general_ci NOT NULL,
  `permitido` tinyint(1) NOT NULL DEFAULT '0',
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuario_permissoes`
--

INSERT INTO `usuario_permissoes` (`usuario_id`, `permissao`, `permitido`, `atualizado_em`) VALUES
('9cd7e53a-da9d-4f2b-9b32-328be32da2f0', 'agendamentos', 0, '2026-07-16 16:15:33'),
('9cd7e53a-da9d-4f2b-9b32-328be32da2f0', 'analise_planos', 1, '2026-07-18 14:27:15'),
('9cd7e53a-da9d-4f2b-9b32-328be32da2f0', 'armadores', 0, '2026-07-16 16:15:33'),
('9cd7e53a-da9d-4f2b-9b32-328be32da2f0', 'certificados', 0, '2026-07-16 16:15:33'),
('9cd7e53a-da9d-4f2b-9b32-328be32da2f0', 'comercial', 0, '2026-07-16 16:15:33'),
('9cd7e53a-da9d-4f2b-9b32-328be32da2f0', 'configuracoes', 0, '2026-07-16 16:15:33'),
('9cd7e53a-da9d-4f2b-9b32-328be32da2f0', 'dashboard', 1, '2026-07-16 16:15:33'),
('9cd7e53a-da9d-4f2b-9b32-328be32da2f0', 'despachantes', 0, '2026-07-16 16:15:33'),
('9cd7e53a-da9d-4f2b-9b32-328be32da2f0', 'documentacao', 0, '2026-07-16 16:15:33'),
('9cd7e53a-da9d-4f2b-9b32-328be32da2f0', 'emails', 0, '2026-07-16 16:15:33'),
('9cd7e53a-da9d-4f2b-9b32-328be32da2f0', 'embarcacoes', 0, '2026-07-16 16:15:33'),
('9cd7e53a-da9d-4f2b-9b32-328be32da2f0', 'financeiro', 0, '2026-07-16 16:15:33'),
('9cd7e53a-da9d-4f2b-9b32-328be32da2f0', 'portal_clientes', 0, '2026-07-16 16:15:33'),
('9cd7e53a-da9d-4f2b-9b32-328be32da2f0', 'proprietarios', 0, '2026-07-16 16:15:33'),
('9cd7e53a-da9d-4f2b-9b32-328be32da2f0', 'relatorios', 0, '2026-07-16 16:15:33'),
('9cd7e53a-da9d-4f2b-9b32-328be32da2f0', 'relatorios_aprovacao', 1, '2026-07-16 16:15:33'),
('9cd7e53a-da9d-4f2b-9b32-328be32da2f0', 'responsaveis_assinatura', 0, '2026-07-16 16:15:33'),
('9cd7e53a-da9d-4f2b-9b32-328be32da2f0', 'servicos', 0, '2026-07-16 16:15:33'),
('9cd7e53a-da9d-4f2b-9b32-328be32da2f0', 'usuarios', 0, '2026-07-16 16:15:33'),
('9cd7e53a-da9d-4f2b-9b32-328be32da2f0', 'vistorias', 1, '2026-07-16 16:15:33'),
('d2a16613-dfa4-4948-8de4-8c802abdf394', 'agendamentos', 1, '2026-07-17 01:38:21'),
('d2a16613-dfa4-4948-8de4-8c802abdf394', 'armadores', 0, '2026-07-16 16:15:33'),
('d2a16613-dfa4-4948-8de4-8c802abdf394', 'certificados', 1, '2026-07-16 16:39:29'),
('d2a16613-dfa4-4948-8de4-8c802abdf394', 'comercial', 0, '2026-07-16 16:15:33'),
('d2a16613-dfa4-4948-8de4-8c802abdf394', 'configuracoes', 0, '2026-07-16 16:15:33'),
('d2a16613-dfa4-4948-8de4-8c802abdf394', 'dashboard', 1, '2026-07-16 16:15:33'),
('d2a16613-dfa4-4948-8de4-8c802abdf394', 'despachantes', 0, '2026-07-16 16:15:33'),
('d2a16613-dfa4-4948-8de4-8c802abdf394', 'documentacao', 1, '2026-07-16 16:15:33'),
('d2a16613-dfa4-4948-8de4-8c802abdf394', 'emails', 0, '2026-07-16 16:15:33'),
('d2a16613-dfa4-4948-8de4-8c802abdf394', 'embarcacoes', 1, '2026-07-16 16:15:33'),
('d2a16613-dfa4-4948-8de4-8c802abdf394', 'financeiro', 0, '2026-07-16 16:15:33'),
('d2a16613-dfa4-4948-8de4-8c802abdf394', 'portal_clientes', 0, '2026-07-16 16:15:33'),
('d2a16613-dfa4-4948-8de4-8c802abdf394', 'proprietarios', 0, '2026-07-16 16:15:33'),
('d2a16613-dfa4-4948-8de4-8c802abdf394', 'relatorios', 0, '2026-07-16 16:15:33'),
('d2a16613-dfa4-4948-8de4-8c802abdf394', 'relatorios_aprovacao', 1, '2026-07-17 01:38:21'),
('d2a16613-dfa4-4948-8de4-8c802abdf394', 'responsaveis_assinatura', 0, '2026-07-16 16:15:33'),
('d2a16613-dfa4-4948-8de4-8c802abdf394', 'servicos', 0, '2026-07-16 16:15:33'),
('d2a16613-dfa4-4948-8de4-8c802abdf394', 'usuarios', 0, '2026-07-16 16:15:33'),
('d2a16613-dfa4-4948-8de4-8c802abdf394', 'vistorias', 1, '2026-07-16 16:15:33'),
('dd121661-feb4-42f6-895a-68eb0608d1e4', 'analise_planos', 1, '2026-07-18 14:27:15');

-- --------------------------------------------------------

--
-- Estrutura para tabela `vistoria_retornos`
--

CREATE TABLE `vistoria_retornos` (
  `id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `relatorio_origem_id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `agendamento_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `relatorio_resultado_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('PENDENTE_AGENDAMENTO','AGENDADO','RELATORIO_ENVIADO','CONCLUIDO','CANCELADO') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'PENDENTE_AGENDAMENTO',
  `motivo_cancelamento` text COLLATE utf8mb4_general_ci,
  `criado_por` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cancelado_por` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `cancelado_em` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `vistorias`
--

CREATE TABLE `vistorias` (
  `id` char(36) COLLATE utf8mb4_general_ci NOT NULL DEFAULT (uuid()),
  `numero` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `embarcacao_id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `pessoa_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `armador_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `operador_nome` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `agendamento_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `relatorio_anterior_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `finalidade` enum('VISTORIA','CUMPRIMENTO_EXIGENCIAS') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'VISTORIA',
  `data_vistoria` date NOT NULL,
  `prazo_exigencias_dias` smallint UNSIGNED DEFAULT NULL,
  `data_emissao` date DEFAULT NULL,
  `status` enum('PENDENTE','AGUARDANDO_APROVACAO','APROVADA','APROVADA_COM_EXIGENCIAS','REPROVADA','CANCELADA') COLLATE utf8mb4_general_ci DEFAULT 'PENDENTE',
  `mobile_versao` int UNSIGNED NOT NULL DEFAULT '0',
  `mobile_finalizada_em` datetime DEFAULT NULL,
  `aprovado_por` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `responsavel_assinatura_id` int DEFAULT NULL,
  `assinatura_status` enum('PENDENTE','ASSINADO','CANCELADO') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'PENDENTE',
  `assinatura_em` datetime DEFAULT NULL,
  `data_aprovacao` datetime DEFAULT NULL,
  `observacao_admin` text COLLATE utf8mb4_general_ci,
  `observacoes` text COLLATE utf8mb4_general_ci,
  `resultado` text COLLATE utf8mb4_general_ci,
  `observacoes_tecnicas` text COLLATE utf8mb4_general_ci,
  `texto_observacoes_geradas` text COLLATE utf8mb4_general_ci,
  `criado_por` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `vistoria_anexos`
--

CREATE TABLE `vistoria_anexos` (
  `id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `vistoria_id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `catalogo_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `url_arquivo` varchar(1000) COLLATE utf8mb4_general_ci NOT NULL,
  `chave_arquivo` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nome_original` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mime_type` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `tamanho_bytes` int UNSIGNED NOT NULL,
  `sha256` char(64) COLLATE utf8mb4_general_ci NOT NULL,
  `capturado_em` datetime DEFAULT NULL,
  `criado_por` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `excluido_em` datetime DEFAULT NULL,
  `excluido_por` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `vistoria_checklist_respostas`
--

CREATE TABLE `vistoria_checklist_respostas` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `vistoria_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `catalogo_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('CONFORME','NAO_CONFORME','NAO_SE_APLICA') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `observacao` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `item_normam` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `vencimento` date DEFAULT NULL,
  `sem_prazo` tinyint(1) NOT NULL DEFAULT '0',
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `vistoria_exigencias`
--

CREATE TABLE `vistoria_exigencias` (
  `id` char(36) COLLATE utf8mb4_general_ci NOT NULL DEFAULT (uuid()),
  `vistoria_id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `catalogo_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bloco_vistoria` enum('seco','flutuando','borda_livre','arqueacao') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ordem` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `item` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_general_ci,
  `conforme` enum('sim','nao','na') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'na',
  `observacao` text COLLATE utf8mb4_general_ci,
  `item_normam` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `vencimento` date DEFAULT NULL,
  `antes_de_suspender` tinyint(1) NOT NULL DEFAULT '0',
  `status_item` enum('pendente','cumprida','nao_cumprida_transcrita','cumprida_parcial_reescrita','inserida') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'inserida',
  `exigencia_origem_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `vistoria_mobile_sync`
--

CREATE TABLE `vistoria_mobile_sync` (
  `operacao_id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `vistoria_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `usuario_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tipo` enum('RASCUNHO','ANEXO','FOTO_EMBARCACAO','FINALIZACAO') COLLATE utf8mb4_general_ci NOT NULL,
  `payload_hash` char(64) COLLATE utf8mb4_general_ci NOT NULL,
  `resposta_json` json DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `agendamentos`
--
ALTER TABLE `agendamentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `proposta_id` (`proposta_id`),
  ADD KEY `embarcacao_id` (`embarcacao_id`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `vistoriador_id` (`vistoriador_id`),
  ADD KEY `status` (`status`),
  ADD KEY `data_vistoria` (`data_vistoria`),
  ADD KEY `criado_por` (`criado_por`),
  ADD KEY `idx_agendamentos_armador_id` (`armador_id`),
  ADD KEY `idx_agendamentos_relatorio_origem` (`relatorio_origem_id`);

--
-- Índices de tabela `analises_planos`
--
ALTER TABLE `analises_planos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_analise_planos_numero` (`numero`),
  ADD KEY `idx_analise_planos_embarcacao` (`embarcacao_id`,`status`),
  ADD KEY `idx_analise_planos_analista` (`analista_id`,`status`),
  ADD KEY `fk_analise_planos_solicitante` (`solicitante_id`),
  ADD KEY `fk_analise_planos_responsavel` (`responsavel_assinatura_id`),
  ADD KEY `fk_analise_planos_criador` (`criado_por`);

--
-- Índices de tabela `analise_planos_arquivos`
--
ALTER TABLE `analise_planos_arquivos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_analise_arquivo_hash` (`submissao_id`,`sha256`),
  ADD KEY `idx_analise_arquivo_submissao` (`submissao_id`,`criado_em`),
  ADD KEY `fk_analise_arquivo_usuario` (`criado_por`);

--
-- Índices de tabela `analise_planos_exigencias`
--
ALTER TABLE `analise_planos_exigencias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_analise_exigencia` (`analise_id`,`status`,`ordem`),
  ADD KEY `fk_analise_exigencia_item` (`item_id`),
  ADD KEY `fk_analise_exigencia_usuario` (`criado_por`);

--
-- Índices de tabela `analise_planos_historico`
--
ALTER TABLE `analise_planos_historico`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_analise_historico` (`analise_id`,`criado_em`),
  ADD KEY `fk_analise_historico_usuario` (`usuario_id`);

--
-- Índices de tabela `analise_planos_itens`
--
ALTER TABLE `analise_planos_itens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_analise_item_ordem` (`analise_id`,`ordem`),
  ADD KEY `fk_analise_item_submissao` (`submissao_id`),
  ADD KEY `fk_analise_item_usuario` (`criado_por`);

--
-- Índices de tabela `analise_planos_pareceres`
--
ALTER TABLE `analise_planos_pareceres`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_analise_parecer_versao` (`analise_id`,`versao`),
  ADD KEY `idx_analise_parecer_publicado` (`analise_id`,`status`,`publicado_em`),
  ADD KEY `fk_analise_parecer_responsavel` (`responsavel_assinatura_id`),
  ADD KEY `fk_analise_parecer_usuario` (`criado_por`);

--
-- Índices de tabela `analise_planos_submissoes`
--
ALTER TABLE `analise_planos_submissoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_analise_submissao_revisao` (`analise_id`,`revisao`),
  ADD KEY `fk_analise_submissao_usuario` (`criado_por`);

--
-- Índices de tabela `campo_login_tentativas`
--
ALTER TABLE `campo_login_tentativas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_campo_login_bloqueio` (`email_hash`,`ip_hash`,`criado_em`);

--
-- Índices de tabela `campo_sessoes`
--
ALTER TABLE `campo_sessoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_campo_sessoes_usuario` (`usuario_id`,`expira_em`);

--
-- Índices de tabela `certificados_cht`
--
ALTER TABLE `certificados_cht`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_certificados_cht_numero` (`numero_certificado`),
  ADD KEY `idx_certificados_cht_numero` (`numero_relatorio_ht`),
  ADD KEY `idx_certificados_cht_status` (`status`),
  ADD KEY `idx_certificados_cht_ativo` (`ativo`),
  ADD KEY `idx_certificados_ht_profissional` (`profissional_empresa`),
  ADD KEY `fk_cht_vistoria` (`vistoria_id`);

--
-- Índices de tabela `certificados_cnarq`
--
ALTER TABLE `certificados_cnarq`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_certificados_cnarq_numero` (`numero`),
  ADD KEY `idx_certificados_cnarq_status` (`status`),
  ADD KEY `idx_certificados_cnarq_ativo` (`ativo`),
  ADD KEY `fk_cnarq_vistoria` (`vistoria_id`);

--
-- Índices de tabela `certificados_cnbl`
--
ALTER TABLE `certificados_cnbl`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_certificados_cnbl_numero` (`numero`),
  ADD KEY `idx_certificados_cnbl_status` (`status`),
  ADD KEY `idx_certificados_cnbl_ativo` (`ativo`),
  ADD KEY `fk_cnbl_vistoria` (`vistoria_id`);

--
-- Índices de tabela `certificados_csn`
--
ALTER TABLE `certificados_csn`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero` (`numero`),
  ADD UNIQUE KEY `token_assinatura` (`token_assinatura`),
  ADD KEY `criado_por` (`criado_por`),
  ADD KEY `fk_csn_vistoria` (`vistoria_id`);

--
-- Índices de tabela `certificados_lc`
--
ALTER TABLE `certificados_lc`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_certificados_lc_numero` (`numero_lc`),
  ADD KEY `idx_certificados_lc_status` (`status`),
  ADD KEY `idx_certificados_lc_ativo` (`ativo`),
  ADD KEY `idx_certificados_lc_embarcacao` (`embarcacao_id`),
  ADD KEY `idx_certificados_lc_tipo` (`tipo_licenca`),
  ADD KEY `fk_lc_vistoria` (`vistoria_id`);

--
-- Índices de tabela `certificados_lp`
--
ALTER TABLE `certificados_lp`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_certificados_lp_numero` (`numero_lp`),
  ADD KEY `idx_certificados_lp_status` (`status`),
  ADD KEY `idx_certificados_lp_ativo` (`ativo`),
  ADD KEY `idx_certificados_lp_embarcacao` (`embarcacao_id`),
  ADD KEY `idx_certificados_lp_tipo` (`tipo_licenca`),
  ADD KEY `fk_lp_vistoria` (`vistoria_id`);

--
-- Índices de tabela `cert_convalidacoes`
--
ALTER TABLE `cert_convalidacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cert_convalidacoes_tipo` (`tipo_certificado`),
  ADD KEY `idx_cert_convalidacoes_certificado` (`certificado_id`,`tipo_certificado`);

--
-- Índices de tabela `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cpf_cnpj` (`cpf_cnpj`),
  ADD KEY `criado_por` (`criado_por`);

--
-- Índices de tabela `clientes_embarcacoes`
--
ALTER TABLE `clientes_embarcacoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_cliente_embarcacao_ativa` (`vinculo_ativo_chave`),
  ADD KEY `embarcacao_id` (`embarcacao_id`),
  ADD KEY `idx_cliente_embarcacao_historico` (`cliente_id`,`embarcacao_id`,`vinculado_em`),
  ADD KEY `idx_cliente_embarcacao_status` (`cliente_id`,`status`);

--
-- Índices de tabela `clientes_tipos_embarcacao`
--
ALTER TABLE `clientes_tipos_embarcacao`
  ADD PRIMARY KEY (`cliente_id`,`tipo_embarcacao_id`),
  ADD KEY `idx_cte_tipo_embarcacao` (`tipo_embarcacao_id`);

--
-- Índices de tabela `cliente_password_resets`
--
ALTER TABLE `cliente_password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_cliente_reset_token` (`token_hash`),
  ADD KEY `idx_cliente_reset_cliente` (`cliente_id`),
  ADD KEY `idx_cliente_reset_expira` (`expira_em`);

--
-- Índices de tabela `cliente_portal_acessos`
--
ALTER TABLE `cliente_portal_acessos`
  ADD PRIMARY KEY (`cliente_id`),
  ADD UNIQUE KEY `uk_cliente_portal_login` (`login`),
  ADD KEY `idx_cliente_portal_ativo` (`ativo`);

--
-- Índices de tabela `configuracoes`
--
ALTER TABLE `configuracoes`
  ADD PRIMARY KEY (`chave`);

--
-- Índices de tabela `contratos`
--
ALTER TABLE `contratos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `proposta_id` (`proposta_id`),
  ADD KEY `criado_por` (`criado_por`),
  ADD KEY `contratos_cliente_fk` (`cliente_id`);

--
-- Índices de tabela `csn_convalidacoes`
--
ALTER TABLE `csn_convalidacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `certificado_id` (`certificado_id`);

--
-- Índices de tabela `csn_distribuicao_passageiros`
--
ALTER TABLE `csn_distribuicao_passageiros`
  ADD PRIMARY KEY (`id`),
  ADD KEY `certificado_id` (`certificado_id`);

--
-- Índices de tabela `documento_aprovacoes`
--
ALTER TABLE `documento_aprovacoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_documento_aprovacao_versao` (`documento_tipo`,`documento_id`,`versao`),
  ADD UNIQUE KEY `uk_documento_aprovacao_token` (`token_validacao`),
  ADD KEY `idx_documento_aprovacao_documento` (`documento_tipo`,`documento_id`,`status`),
  ADD KEY `idx_documento_aprovacao_responsavel` (`responsavel_id`),
  ADD KEY `idx_documento_aprovacao_usuario` (`aprovador_usuario_id`),
  ADD KEY `idx_documento_aprovacao_convite` (`assinatura_convite_id`);

--
-- Índices de tabela `documento_artefatos`
--
ALTER TABLE `documento_artefatos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_documento_artefato` (`documento_tipo`,`documento_id`,`versao`),
  ADD KEY `idx_documento_artefatos_tipo` (`documento_tipo`,`criado_em`);

--
-- Índices de tabela `email_logs`
--
ALTER TABLE `email_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email_logs_tipo` (`tipo`),
  ADD KEY `idx_email_logs_status` (`status`),
  ADD KEY `idx_email_logs_referencia` (`referencia_tipo`,`referencia_id`),
  ADD KEY `idx_email_logs_enviado_por` (`enviado_por`),
  ADD KEY `idx_email_logs_created_at` (`created_at`);

--
-- Índices de tabela `embarcacoes`
--
ALTER TABLE `embarcacoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `registro` (`registro`),
  ADD KEY `criado_por` (`criado_por`),
  ADD KEY `idx_cliente_id` (`cliente_id`),
  ADD KEY `fk_embarcacoes_tipo` (`tipo_embarcacao_id`),
  ADD KEY `fk_embarcacoes_proprietario` (`proprietario_id`),
  ADD KEY `idx_embarcacoes_foto_atualizada` (`foto_atualizada_em`);

--
-- Índices de tabela `escritorios`
--
ALTER TABLE `escritorios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_escritorios_nome_cidade` (`nome`,`cidade`,`uf`),
  ADD KEY `idx_escritorios_ativo` (`ativo`);

--
-- Índices de tabela `exigencias_catalogo`
--
ALTER TABLE `exigencias_catalogo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_catalogo_categoria` (`categoria_id`);

--
-- Índices de tabela `exigencias_categorias`
--
ALTER TABLE `exigencias_categorias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_categoria_nome` (`nome`);

--
-- Índices de tabela `exportacoes_documentos`
--
ALTER TABLE `exportacoes_documentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_exportacoes_status` (`status`,`solicitado_em`),
  ADD KEY `idx_exportacoes_usuario` (`solicitado_por`,`solicitado_em`);

--
-- Índices de tabela `financeiro_comprovantes`
--
ALTER TABLE `financeiro_comprovantes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_financeiro_comprovantes_lancamento` (`lancamento_id`);

--
-- Índices de tabela `financeiro_contas_bancarias`
--
ALTER TABLE `financeiro_contas_bancarias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_financeiro_contas_ativo` (`ativo`);

--
-- Índices de tabela `financeiro_historico_baixas`
--
ALTER TABLE `financeiro_historico_baixas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_financeiro_baixas_lancamento` (`lancamento_id`),
  ADD KEY `idx_financeiro_baixas_data` (`data_pagamento`),
  ADD KEY `idx_financeiro_baixas_conta` (`conta_bancaria_id`),
  ADD KEY `fk_financeiro_baixas_usuario` (`criado_por`);

--
-- Índices de tabela `financeiro_lancamentos`
--
ALTER TABLE `financeiro_lancamentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `criado_por` (`criado_por`),
  ADD KEY `fk_financeiro_cliente` (`cliente_id`),
  ADD KEY `idx_financeiro_escritorio_data` (`escritorio_id`,`data`),
  ADD KEY `idx_financeiro_responsavel` (`responsavel_usuario_id`),
  ADD KEY `idx_financeiro_proposta` (`proposta_id`);

--
-- Índices de tabela `financeiro_metas_mensais`
--
ALTER TABLE `financeiro_metas_mensais`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_meta_escritorio_usuario_competencia` (`escritorio_id`,`usuario_id`,`competencia`),
  ADD KEY `idx_metas_competencia` (`competencia`),
  ADD KEY `idx_metas_usuario` (`usuario_id`);

--
-- Índices de tabela `logs_atividade`
--
ALTER TABLE `logs_atividade`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `ordens_servico`
--
ALTER TABLE `ordens_servico`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero` (`numero`),
  ADD UNIQUE KEY `agendamento_id` (`agendamento_id`),
  ADD KEY `proposta_id` (`proposta_id`),
  ADD KEY `embarcacao_id` (`embarcacao_id`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `vistoriador_id` (`vistoriador_id`),
  ADD KEY `status` (`status`),
  ADD KEY `data_vistoria` (`data_vistoria`),
  ADD KEY `criado_por` (`criado_por`);

--
-- Índices de tabela `portal_auditoria`
--
ALTER TABLE `portal_auditoria`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_portal_auditoria_cliente` (`cliente_id`,`criado_em`),
  ADD KEY `idx_portal_auditoria_documento` (`documento_tipo`,`documento_id`,`criado_em`),
  ADD KEY `fk_portal_auditoria_embarcacao` (`embarcacao_id`);

--
-- Índices de tabela `propostas`
--
ALTER TABLE `propostas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero` (`numero`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `status` (`status`),
  ADD KEY `criado_por` (`criado_por`),
  ADD KEY `idx_propostas_armador_id` (`armador_id`),
  ADD KEY `idx_propostas_escritorio` (`escritorio_id`);

--
-- Índices de tabela `propostas_embarcacoes`
--
ALTER TABLE `propostas_embarcacoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `proposta_embarcacao` (`proposta_id`,`embarcacao_id`),
  ADD KEY `embarcacao_id` (`embarcacao_id`);

--
-- Índices de tabela `propostas_servicos`
--
ALTER TABLE `propostas_servicos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `proposta_embarcacao_servico` (`proposta_id`,`embarcacao_id`,`servico_id`),
  ADD KEY `servico_id` (`servico_id`),
  ADD KEY `idx_propserv_emb` (`embarcacao_id`);

--
-- Índices de tabela `responsaveis_assinatura`
--
ALTER TABLE `responsaveis_assinatura`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_responsavel_assinatura_usuario` (`usuario_id`),
  ADD KEY `idx_responsavel_assinatura_email` (`email`);

--
-- Índices de tabela `sequenciais_documentos`
--
ALTER TABLE `sequenciais_documentos`
  ADD PRIMARY KEY (`tipo_documento`,`ano`);

--
-- Índices de tabela `servicos`
--
ALTER TABLE `servicos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_servicos_ativo` (`ativo`),
  ADD KEY `idx_servicos_certificado_modelo` (`certificado_modelo`);

--
-- Índices de tabela `tipos_embarcacao`
--
ALTER TABLE `tipos_embarcacao`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_usuarios_excluido_em` (`excluido_em`),
  ADD KEY `idx_usuarios_escritorio` (`escritorio_id`);

--
-- Índices de tabela `usuario_escritorios`
--
ALTER TABLE `usuario_escritorios`
  ADD PRIMARY KEY (`usuario_id`,`escritorio_id`),
  ADD KEY `idx_usuario_escritorios_escritorio` (`escritorio_id`),
  ADD KEY `idx_usuario_escritorios_principal` (`usuario_id`,`principal`);

--
-- Índices de tabela `usuario_perfis`
--
ALTER TABLE `usuario_perfis`
  ADD PRIMARY KEY (`usuario_id`,`perfil`);

--
-- Índices de tabela `usuario_permissoes`
--
ALTER TABLE `usuario_permissoes`
  ADD PRIMARY KEY (`usuario_id`,`permissao`);

--
-- Índices de tabela `vistoria_retornos`
--
ALTER TABLE `vistoria_retornos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_vistoria_retorno_origem` (`relatorio_origem_id`),
  ADD UNIQUE KEY `uk_vistoria_retorno_agendamento` (`agendamento_id`),
  ADD UNIQUE KEY `uk_vistoria_retorno_resultado` (`relatorio_resultado_id`),
  ADD KEY `idx_vistoria_retornos_status` (`status`),
  ADD KEY `fk_vistoria_retorno_criador` (`criado_por`),
  ADD KEY `fk_vistoria_retorno_cancelador` (`cancelado_por`);

--
-- Índices de tabela `vistorias`
--
ALTER TABLE `vistorias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero` (`numero`),
  ADD KEY `embarcacao_id` (`embarcacao_id`),
  ADD KEY `pessoa_id` (`pessoa_id`),
  ADD KEY `criado_por` (`criado_por`),
  ADD KEY `agendamento_id` (`agendamento_id`),
  ADD KEY `vistorias_ibfk_aprovado_por` (`aprovado_por`),
  ADD KEY `fk_vistoria_anterior` (`relatorio_anterior_id`),
  ADD KEY `fk_vistorias_armador` (`armador_id`),
  ADD KEY `idx_vistorias_agendamento_vigente` (`agendamento_id`,`criado_em`,`id`);

--
-- Índices de tabela `vistoria_anexos`
--
ALTER TABLE `vistoria_anexos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_vistoria_anexo_hash` (`vistoria_id`,`sha256`),
  ADD KEY `idx_vistoria_anexos_catalogo` (`catalogo_id`),
  ADD KEY `idx_vistoria_anexos_criado_por` (`criado_por`);

--
-- Índices de tabela `vistoria_checklist_respostas`
--
ALTER TABLE `vistoria_checklist_respostas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_vistoria_catalogo` (`vistoria_id`,`catalogo_id`),
  ADD KEY `catalogo_id` (`catalogo_id`);

--
-- Índices de tabela `vistoria_exigencias`
--
ALTER TABLE `vistoria_exigencias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vistoria_id` (`vistoria_id`),
  ADD KEY `ordem` (`ordem`),
  ADD KEY `fk_vistoria_exig_catalogo` (`catalogo_id`),
  ADD KEY `fk_vistoria_exig_origem` (`exigencia_origem_id`),
  ADD KEY `idx_exigencias_as_pendentes` (`vistoria_id`,`antes_de_suspender`,`conforme`,`status_item`);

--
-- Índices de tabela `vistoria_mobile_sync`
--
ALTER TABLE `vistoria_mobile_sync`
  ADD PRIMARY KEY (`operacao_id`),
  ADD KEY `idx_mobile_sync_vistoria` (`vistoria_id`,`criado_em`),
  ADD KEY `fk_mobile_sync_usuario` (`usuario_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `analise_planos_historico`
--
ALTER TABLE `analise_planos_historico`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `campo_login_tentativas`
--
ALTER TABLE `campo_login_tentativas`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `logs_atividade`
--
ALTER TABLE `logs_atividade`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `portal_auditoria`
--
ALTER TABLE `portal_auditoria`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `responsaveis_assinatura`
--
ALTER TABLE `responsaveis_assinatura`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Restrições para tabelas despejadas
--

ALTER TABLE `documento_aprovacoes`
  ADD CONSTRAINT `fk_documento_aprovacao_convite` FOREIGN KEY (`assinatura_convite_id`) REFERENCES `assinatura_convites` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `agendamentos`
--
ALTER TABLE `agendamentos`
  ADD CONSTRAINT `agendamentos_ibfk_1` FOREIGN KEY (`proposta_id`) REFERENCES `propostas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_agendamento_relatorio_origem` FOREIGN KEY (`relatorio_origem_id`) REFERENCES `vistorias` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `agendamentos_ibfk_2` FOREIGN KEY (`embarcacao_id`) REFERENCES `embarcacoes` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `agendamentos_ibfk_3` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `agendamentos_ibfk_4` FOREIGN KEY (`vistoriador_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `agendamentos_ibfk_5` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_agendamentos_armador` FOREIGN KEY (`armador_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `analises_planos`
--
ALTER TABLE `analises_planos`
  ADD CONSTRAINT `fk_analise_planos_analista` FOREIGN KEY (`analista_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `fk_analise_planos_criador` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `fk_analise_planos_embarcacao` FOREIGN KEY (`embarcacao_id`) REFERENCES `embarcacoes` (`id`),
  ADD CONSTRAINT `fk_analise_planos_responsavel` FOREIGN KEY (`responsavel_assinatura_id`) REFERENCES `responsaveis_assinatura` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_analise_planos_solicitante` FOREIGN KEY (`solicitante_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `analise_planos_arquivos`
--
ALTER TABLE `analise_planos_arquivos`
  ADD CONSTRAINT `fk_analise_arquivo_submissao` FOREIGN KEY (`submissao_id`) REFERENCES `analise_planos_submissoes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_analise_arquivo_usuario` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `analise_planos_exigencias`
--
ALTER TABLE `analise_planos_exigencias`
  ADD CONSTRAINT `fk_analise_exigencia` FOREIGN KEY (`analise_id`) REFERENCES `analises_planos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_analise_exigencia_item` FOREIGN KEY (`item_id`) REFERENCES `analise_planos_itens` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_analise_exigencia_usuario` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `analise_planos_historico`
--
ALTER TABLE `analise_planos_historico`
  ADD CONSTRAINT `fk_analise_historico` FOREIGN KEY (`analise_id`) REFERENCES `analises_planos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_analise_historico_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `analise_planos_itens`
--
ALTER TABLE `analise_planos_itens`
  ADD CONSTRAINT `fk_analise_item` FOREIGN KEY (`analise_id`) REFERENCES `analises_planos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_analise_item_submissao` FOREIGN KEY (`submissao_id`) REFERENCES `analise_planos_submissoes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_analise_item_usuario` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `analise_planos_pareceres`
--
ALTER TABLE `analise_planos_pareceres`
  ADD CONSTRAINT `fk_analise_parecer` FOREIGN KEY (`analise_id`) REFERENCES `analises_planos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_analise_parecer_responsavel` FOREIGN KEY (`responsavel_assinatura_id`) REFERENCES `responsaveis_assinatura` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_analise_parecer_usuario` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `analise_planos_submissoes`
--
ALTER TABLE `analise_planos_submissoes`
  ADD CONSTRAINT `fk_analise_submissao` FOREIGN KEY (`analise_id`) REFERENCES `analises_planos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_analise_submissao_usuario` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `campo_sessoes`
--
ALTER TABLE `campo_sessoes`
  ADD CONSTRAINT `fk_campo_sessoes_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `certificados_cht`
--
ALTER TABLE `certificados_cht`
  ADD CONSTRAINT `fk_cht_vistoria` FOREIGN KEY (`vistoria_id`) REFERENCES `vistorias` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `certificados_cnarq`
--
ALTER TABLE `certificados_cnarq`
  ADD CONSTRAINT `fk_cnarq_vistoria` FOREIGN KEY (`vistoria_id`) REFERENCES `vistorias` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `certificados_cnbl`
--
ALTER TABLE `certificados_cnbl`
  ADD CONSTRAINT `fk_cnbl_vistoria` FOREIGN KEY (`vistoria_id`) REFERENCES `vistorias` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `certificados_csn`
--
ALTER TABLE `certificados_csn`
  ADD CONSTRAINT `certificados_csn_ibfk_1` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_csn_vistoria` FOREIGN KEY (`vistoria_id`) REFERENCES `vistorias` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `certificados_lc`
--
ALTER TABLE `certificados_lc`
  ADD CONSTRAINT `fk_lc_vistoria` FOREIGN KEY (`vistoria_id`) REFERENCES `vistorias` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `certificados_lp`
--
ALTER TABLE `certificados_lp`
  ADD CONSTRAINT `fk_lp_vistoria` FOREIGN KEY (`vistoria_id`) REFERENCES `vistorias` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `clientes`
--
ALTER TABLE `clientes`
  ADD CONSTRAINT `clientes_ibfk_1` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `clientes_embarcacoes`
--
ALTER TABLE `clientes_embarcacoes`
  ADD CONSTRAINT `clientes_embarcacoes_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `clientes_embarcacoes_ibfk_2` FOREIGN KEY (`embarcacao_id`) REFERENCES `embarcacoes` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `clientes_tipos_embarcacao`
--
ALTER TABLE `clientes_tipos_embarcacao`
  ADD CONSTRAINT `fk_cte_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cte_tipo_embarcacao` FOREIGN KEY (`tipo_embarcacao_id`) REFERENCES `tipos_embarcacao` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `cliente_password_resets`
--
ALTER TABLE `cliente_password_resets`
  ADD CONSTRAINT `fk_cliente_reset_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `cliente_portal_acessos`
--
ALTER TABLE `cliente_portal_acessos`
  ADD CONSTRAINT `fk_cliente_portal_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `contratos`
--
ALTER TABLE `contratos`
  ADD CONSTRAINT `contratos_cliente_fk` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `contratos_ibfk_2` FOREIGN KEY (`proposta_id`) REFERENCES `propostas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `contratos_ibfk_3` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `csn_convalidacoes`
--
ALTER TABLE `csn_convalidacoes`
  ADD CONSTRAINT `csn_convalidacoes_ibfk_1` FOREIGN KEY (`certificado_id`) REFERENCES `certificados_csn` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `csn_distribuicao_passageiros`
--
ALTER TABLE `csn_distribuicao_passageiros`
  ADD CONSTRAINT `csn_distribuicao_passageiros_ibfk_1` FOREIGN KEY (`certificado_id`) REFERENCES `certificados_csn` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `embarcacoes`
--
ALTER TABLE `embarcacoes`
  ADD CONSTRAINT `embarcacoes_ibfk_1` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_embarcacoes_proprietario` FOREIGN KEY (`proprietario_id`) REFERENCES `clientes` (`id`),
  ADD CONSTRAINT `fk_embarcacoes_tipo` FOREIGN KEY (`tipo_embarcacao_id`) REFERENCES `tipos_embarcacao` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `exigencias_catalogo`
--
ALTER TABLE `exigencias_catalogo`
  ADD CONSTRAINT `fk_catalogo_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `exigencias_categorias` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `exportacoes_documentos`
--
ALTER TABLE `exportacoes_documentos`
  ADD CONSTRAINT `fk_exportacoes_usuario` FOREIGN KEY (`solicitado_por`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `financeiro_comprovantes`
--
ALTER TABLE `financeiro_comprovantes`
  ADD CONSTRAINT `fk_financeiro_comprovantes_lancamento` FOREIGN KEY (`lancamento_id`) REFERENCES `financeiro_lancamentos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `financeiro_historico_baixas`
--
ALTER TABLE `financeiro_historico_baixas`
  ADD CONSTRAINT `fk_financeiro_baixas_conta` FOREIGN KEY (`conta_bancaria_id`) REFERENCES `financeiro_contas_bancarias` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_financeiro_baixas_lancamento` FOREIGN KEY (`lancamento_id`) REFERENCES `financeiro_lancamentos` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_financeiro_baixas_usuario` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `financeiro_lancamentos`
--
ALTER TABLE `financeiro_lancamentos`
  ADD CONSTRAINT `financeiro_lancamentos_ibfk_1` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_financeiro_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  ADD CONSTRAINT `fk_financeiro_escritorio` FOREIGN KEY (`escritorio_id`) REFERENCES `escritorios` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_financeiro_proposta` FOREIGN KEY (`proposta_id`) REFERENCES `propostas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_financeiro_responsavel` FOREIGN KEY (`responsavel_usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `financeiro_metas_mensais`
--
ALTER TABLE `financeiro_metas_mensais`
  ADD CONSTRAINT `fk_metas_escritorio` FOREIGN KEY (`escritorio_id`) REFERENCES `escritorios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_metas_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `ordens_servico`
--
ALTER TABLE `ordens_servico`
  ADD CONSTRAINT `ordens_servico_ibfk_1` FOREIGN KEY (`agendamento_id`) REFERENCES `agendamentos` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `ordens_servico_ibfk_2` FOREIGN KEY (`proposta_id`) REFERENCES `propostas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `ordens_servico_ibfk_3` FOREIGN KEY (`embarcacao_id`) REFERENCES `embarcacoes` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `ordens_servico_ibfk_4` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `ordens_servico_ibfk_5` FOREIGN KEY (`vistoriador_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `ordens_servico_ibfk_6` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `portal_auditoria`
--
ALTER TABLE `portal_auditoria`
  ADD CONSTRAINT `fk_portal_auditoria_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_portal_auditoria_embarcacao` FOREIGN KEY (`embarcacao_id`) REFERENCES `embarcacoes` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `propostas`
--
ALTER TABLE `propostas`
  ADD CONSTRAINT `fk_propostas_armador` FOREIGN KEY (`armador_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_propostas_escritorio` FOREIGN KEY (`escritorio_id`) REFERENCES `escritorios` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `propostas_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `propostas_ibfk_2` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `propostas_embarcacoes`
--
ALTER TABLE `propostas_embarcacoes`
  ADD CONSTRAINT `propostas_embarcacoes_ibfk_1` FOREIGN KEY (`proposta_id`) REFERENCES `propostas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `propostas_embarcacoes_ibfk_2` FOREIGN KEY (`embarcacao_id`) REFERENCES `embarcacoes` (`id`) ON DELETE RESTRICT;

--
-- Restrições para tabelas `propostas_servicos`
--
ALTER TABLE `propostas_servicos`
  ADD CONSTRAINT `propostas_servicos_ibfk_1` FOREIGN KEY (`proposta_id`) REFERENCES `propostas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `propostas_servicos_ibfk_2` FOREIGN KEY (`servico_id`) REFERENCES `servicos` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `propostas_servicos_ibfk_3` FOREIGN KEY (`embarcacao_id`) REFERENCES `embarcacoes` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `usuarios`
--
ALTER TABLE `responsaveis_assinatura`
  ADD CONSTRAINT `fk_responsavel_assinatura_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT;

ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_escritorio` FOREIGN KEY (`escritorio_id`) REFERENCES `escritorios` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `usuario_escritorios`
--
ALTER TABLE `usuario_escritorios`
  ADD CONSTRAINT `fk_usuario_escritorios_escritorio` FOREIGN KEY (`escritorio_id`) REFERENCES `escritorios` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_usuario_escritorios_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `usuario_perfis`
--
ALTER TABLE `usuario_perfis`
  ADD CONSTRAINT `fk_usuario_perfis_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `usuario_permissoes`
--
ALTER TABLE `usuario_permissoes`
  ADD CONSTRAINT `fk_usuario_permissoes_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `vistoria_retornos`
--
ALTER TABLE `vistoria_retornos`
  ADD CONSTRAINT `fk_vistoria_retorno_origem` FOREIGN KEY (`relatorio_origem_id`) REFERENCES `vistorias` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_vistoria_retorno_agendamento` FOREIGN KEY (`agendamento_id`) REFERENCES `agendamentos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_vistoria_retorno_resultado` FOREIGN KEY (`relatorio_resultado_id`) REFERENCES `vistorias` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_vistoria_retorno_criador` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_vistoria_retorno_cancelador` FOREIGN KEY (`cancelado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `vistorias`
--
ALTER TABLE `vistorias`
  ADD CONSTRAINT `fk_vistoria_anterior` FOREIGN KEY (`relatorio_anterior_id`) REFERENCES `vistorias` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_vistorias_armador` FOREIGN KEY (`armador_id`) REFERENCES `clientes` (`id`),
  ADD CONSTRAINT `vistorias_ibfk_1` FOREIGN KEY (`embarcacao_id`) REFERENCES `embarcacoes` (`id`),
  ADD CONSTRAINT `vistorias_ibfk_3` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `vistorias_ibfk_agendamento` FOREIGN KEY (`agendamento_id`) REFERENCES `agendamentos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `vistorias_ibfk_aprovado_por` FOREIGN KEY (`aprovado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `vistoria_anexos`
--
ALTER TABLE `vistoria_anexos`
  ADD CONSTRAINT `fk_vistoria_anexos_catalogo` FOREIGN KEY (`catalogo_id`) REFERENCES `exigencias_catalogo` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_vistoria_anexos_usuario` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_vistoria_anexos_vistoria` FOREIGN KEY (`vistoria_id`) REFERENCES `vistorias` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `vistoria_checklist_respostas`
--
ALTER TABLE `vistoria_checklist_respostas`
  ADD CONSTRAINT `vistoria_checklist_respostas_ibfk_1` FOREIGN KEY (`vistoria_id`) REFERENCES `vistorias` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `vistoria_checklist_respostas_ibfk_2` FOREIGN KEY (`catalogo_id`) REFERENCES `exigencias_catalogo` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `vistoria_exigencias`
--
ALTER TABLE `vistoria_exigencias`
  ADD CONSTRAINT `fk_vistoria_exig_catalogo` FOREIGN KEY (`catalogo_id`) REFERENCES `exigencias_catalogo` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_vistoria_exig_origem` FOREIGN KEY (`exigencia_origem_id`) REFERENCES `vistoria_exigencias` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `vistoria_exigencias_ibfk_1` FOREIGN KEY (`vistoria_id`) REFERENCES `vistorias` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `vistoria_mobile_sync`
--
ALTER TABLE `vistoria_mobile_sync`
  ADD CONSTRAINT `fk_mobile_sync_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_mobile_sync_vistoria` FOREIGN KEY (`vistoria_id`) REFERENCES `vistorias` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
