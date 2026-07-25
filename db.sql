-- MySQL dump 10.13  Distrib 8.0.46, for Linux (x86_64)
--
-- Host: localhost    Database: erp_sistema
-- ------------------------------------------------------
-- Server version	8.0.46

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `agendamentos`
--

DROP TABLE IF EXISTS `agendamentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `agendamentos` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT (uuid()),
  `proposta_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `relatorio_origem_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
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
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `proposta_id` (`proposta_id`),
  KEY `embarcacao_id` (`embarcacao_id`),
  KEY `cliente_id` (`cliente_id`),
  KEY `vistoriador_id` (`vistoriador_id`),
  KEY `status` (`status`),
  KEY `data_vistoria` (`data_vistoria`),
  KEY `criado_por` (`criado_por`),
  KEY `idx_agendamentos_armador_id` (`armador_id`),
  KEY `idx_agendamentos_relatorio_origem` (`relatorio_origem_id`),
  CONSTRAINT `agendamentos_ibfk_1` FOREIGN KEY (`proposta_id`) REFERENCES `propostas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `agendamentos_ibfk_2` FOREIGN KEY (`embarcacao_id`) REFERENCES `embarcacoes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `agendamentos_ibfk_3` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `agendamentos_ibfk_4` FOREIGN KEY (`vistoriador_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `agendamentos_ibfk_5` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_agendamento_relatorio_origem` FOREIGN KEY (`relatorio_origem_id`) REFERENCES `vistorias` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_agendamentos_armador` FOREIGN KEY (`armador_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agendamentos`
--

LOCK TABLES `agendamentos` WRITE;
/*!40000 ALTER TABLE `agendamentos` DISABLE KEYS */;
INSERT INTO `agendamentos` VALUES ('244a3876-865e-11f1-a50d-aa44e656c57d','1e08dd85-865e-11f1-a50d-aa44e656c57d',NULL,'09542979-d78e-4095-8ee2-a01e3e7efa07','e82942df-63da-4093-82b7-c2849fe3634e',NULL,NULL,'d2a16613-dfa4-4948-8de4-8c802abdf394','dd121661-feb4-42f6-895a-68eb0608d1e4','Vistoria Inicial de Borda Livre, Vistoria Inicial Flutuando, Vistoria Inicial Seco, Vistoria Intermediária','2026-07-24','16:30:00','belem',NULL,NULL,'concluido','Agendamento gerado automaticamente a partir da aprovação interna da proposta. Favor definir data e vistoriador.','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-07-23 06:17:11','2026-07-23 06:50:32'),('9203af31-871a-11f1-a50d-aa44e656c57d','8b4f4f9b-871a-11f1-a50d-aa44e656c57d',NULL,'09542979-d78e-4095-8ee2-a01e3e7efa07','e82942df-63da-4093-82b7-c2849fe3634e',NULL,NULL,'d2a16613-dfa4-4948-8de4-8c802abdf394','ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','Vistoria Inicial de Arqueação, Vistoria Inicial de Borda Livre, Vistoria Inicial Flutuando, Vistoria Inicial Seco','2026-07-30','13:00:00',NULL,NULL,NULL,'pendente','Agendamento gerado automaticamente a partir da aprovação interna da proposta. Favor definir data e vistoriador.','ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','2026-07-24 04:46:01','2026-07-24 04:47:00'),('c381729a-8666-11f1-a50d-aa44e656c57d','c07d458c-8666-11f1-a50d-aa44e656c57d',NULL,'09542979-d78e-4095-8ee2-a01e3e7efa07','e82942df-63da-4093-82b7-c2849fe3634e',NULL,NULL,'d2a16613-dfa4-4948-8de4-8c802abdf394','ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','Vistoria Inicial de Arqueação, Vistoria Inicial de Borda Livre, Vistoria Inicial Flutuando, Vistoria Inicial Seco','2026-07-24','05:30:00','belem',NULL,NULL,'pendente','Agendamento gerado automaticamente a partir da aprovação interna da proposta. Favor definir data e vistoriador.','ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','2026-07-23 07:18:55','2026-07-23 07:20:26');
/*!40000 ALTER TABLE `agendamentos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `analise_planos_agenda_historico`
--

DROP TABLE IF EXISTS `analise_planos_agenda_historico`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `analise_planos_agenda_historico` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `analise_id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `analista_anterior_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `analista_novo_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `prazo_anterior_em` datetime DEFAULT NULL,
  `prazo_novo_em` datetime NOT NULL,
  `motivo` varchar(500) COLLATE utf8mb4_general_ci NOT NULL,
  `acao` enum('AGENDAMENTO','REAGENDAMENTO','REATRIBUICAO') COLLATE utf8mb4_general_ci NOT NULL,
  `criado_por` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_analise_agenda_historico` (`analise_id`,`criado_em`),
  KEY `fk_analise_agenda_anterior` (`analista_anterior_id`),
  KEY `fk_analise_agenda_novo` (`analista_novo_id`),
  KEY `fk_analise_agenda_usuario` (`criado_por`),
  CONSTRAINT `fk_analise_agenda_anterior` FOREIGN KEY (`analista_anterior_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_analise_agenda_novo` FOREIGN KEY (`analista_novo_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_analise_agenda_processo` FOREIGN KEY (`analise_id`) REFERENCES `analises_planos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_analise_agenda_usuario` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `analise_planos_agenda_historico`
--

LOCK TABLES `analise_planos_agenda_historico` WRITE;
/*!40000 ALTER TABLE `analise_planos_agenda_historico` DISABLE KEYS */;
/*!40000 ALTER TABLE `analise_planos_agenda_historico` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `analise_planos_arquivos`
--

DROP TABLE IF EXISTS `analise_planos_arquivos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `analise_planos_arquivos` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `submissao_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `item_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `categoria` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `classificacao` enum('RECEBIDO','ACEITO','SUBSTITUIDO','REJEITADO') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'RECEBIDO',
  `justificativa_classificacao` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nome_original` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `extensao` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `mime_type` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tamanho_bytes` bigint unsigned NOT NULL,
  `sha256` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `chave_arquivo` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `criado_por` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `classificado_por` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `classificado_em` datetime DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_analise_arquivo_hash` (`submissao_id`,`sha256`),
  KEY `idx_analise_arquivo_submissao` (`submissao_id`,`criado_em`),
  KEY `fk_analise_arquivo_usuario` (`criado_por`),
  KEY `idx_arquivo_classificacao` (`submissao_id`,`classificacao`),
  KEY `fk_arquivo_item` (`item_id`),
  KEY `fk_arquivo_classificador` (`classificado_por`),
  CONSTRAINT `fk_analise_arquivo_submissao` FOREIGN KEY (`submissao_id`) REFERENCES `analise_planos_submissoes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_analise_arquivo_usuario` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `fk_arquivo_classificador` FOREIGN KEY (`classificado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_arquivo_item` FOREIGN KEY (`item_id`) REFERENCES `analise_planos_itens` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `analise_planos_arquivos`
--

LOCK TABLES `analise_planos_arquivos` WRITE;
/*!40000 ALTER TABLE `analise_planos_arquivos` DISABLE KEYS */;
/*!40000 ALTER TABLE `analise_planos_arquivos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `analise_planos_exigencias`
--

DROP TABLE IF EXISTS `analise_planos_exigencias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `analise_planos_exigencias` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `analise_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `item_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ordem` int unsigned NOT NULL,
  `descricao` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `referencia_normativa` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `prazo` date DEFAULT NULL,
  `status` enum('PENDENTE','CUMPRIDA','PARCIAL','NAO_CUMPRIDA','TRANSCRITA') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'PENDENTE',
  `transcricao_admissivel` tinyint(1) NOT NULL DEFAULT '0',
  `fundamento_transcricao` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `saneamento_pendente` tinyint(1) NOT NULL DEFAULT '0',
  `observacao_cumprimento` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `criado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_analise_exigencia` (`analise_id`,`status`,`ordem`),
  KEY `fk_analise_exigencia_item` (`item_id`),
  KEY `fk_analise_exigencia_usuario` (`criado_por`),
  CONSTRAINT `fk_analise_exigencia` FOREIGN KEY (`analise_id`) REFERENCES `analises_planos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_analise_exigencia_item` FOREIGN KEY (`item_id`) REFERENCES `analise_planos_itens` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_analise_exigencia_usuario` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `analise_planos_exigencias`
--

LOCK TABLES `analise_planos_exigencias` WRITE;
/*!40000 ALTER TABLE `analise_planos_exigencias` DISABLE KEYS */;
/*!40000 ALTER TABLE `analise_planos_exigencias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `analise_planos_historico`
--

DROP TABLE IF EXISTS `analise_planos_historico`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `analise_planos_historico` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `analise_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `usuario_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `evento` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `status_anterior` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status_novo` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `detalhe` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_analise_historico` (`analise_id`,`criado_em`),
  KEY `fk_analise_historico_usuario` (`usuario_id`),
  CONSTRAINT `fk_analise_historico` FOREIGN KEY (`analise_id`) REFERENCES `analises_planos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_analise_historico_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `analise_planos_historico`
--

LOCK TABLES `analise_planos_historico` WRITE;
/*!40000 ALTER TABLE `analise_planos_historico` DISABLE KEYS */;
INSERT INTO `analise_planos_historico` VALUES (1,'7f1b9697-2c7f-4e4c-b76d-2c642e3dc13f','9cd7e53a-da9d-4f2b-9b32-328be32da2f0','CRIADA',NULL,'RASCUNHO','Processo AM-RAP-1/26 criado.','2026-07-23 07:01:09');
/*!40000 ALTER TABLE `analise_planos_historico` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `analise_planos_itens`
--

DROP TABLE IF EXISTS `analise_planos_itens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `analise_planos_itens` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `analise_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `submissao_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ordem` int unsigned NOT NULL,
  `documento` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `revisao_documento` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `referencia_normativa` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `versao_normativa` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `obrigatorio` tinyint(1) NOT NULL DEFAULT '1',
  `aplicavel` tinyint(1) NOT NULL DEFAULT '1',
  `impeditivo_emissao` tinyint(1) NOT NULL DEFAULT '1',
  `resultado` enum('PENDENTE','CONFORME','EXIGENCIA','NAO_APLICA') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'PENDENTE',
  `observacao` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `criado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_analise_item_ordem` (`analise_id`,`ordem`),
  KEY `fk_analise_item_submissao` (`submissao_id`),
  KEY `fk_analise_item_usuario` (`criado_por`),
  CONSTRAINT `fk_analise_item` FOREIGN KEY (`analise_id`) REFERENCES `analises_planos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_analise_item_submissao` FOREIGN KEY (`submissao_id`) REFERENCES `analise_planos_submissoes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_analise_item_usuario` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `analise_planos_itens`
--

LOCK TABLES `analise_planos_itens` WRITE;
/*!40000 ALTER TABLE `analise_planos_itens` DISABLE KEYS */;
INSERT INTO `analise_planos_itens` VALUES ('487bac54-8664-11f1-a50d-aa44e656c57d','7f1b9697-2c7f-4e4c-b76d-2c642e3dc13f',NULL,1,'Memorial Descritivo',NULL,'NORMAM, Anexo 3-F',NULL,1,1,1,'PENDENTE',NULL,'9cd7e53a-da9d-4f2b-9b32-328be32da2f0','2026-07-23 07:01:09','2026-07-23 07:01:09'),('487bb2ea-8664-11f1-a50d-aa44e656c57d','7f1b9697-2c7f-4e4c-b76d-2c642e3dc13f',NULL,2,'Plano de Arranjo Geral',NULL,'NORMAM, Anexo 3-F',NULL,1,1,1,'PENDENTE',NULL,'9cd7e53a-da9d-4f2b-9b32-328be32da2f0','2026-07-23 07:01:09','2026-07-23 07:01:09'),('487bbc5a-8664-11f1-a50d-aa44e656c57d','7f1b9697-2c7f-4e4c-b76d-2c642e3dc13f',NULL,3,'Plano de Linhas',NULL,'NORMAM, Anexo 3-F',NULL,1,1,1,'PENDENTE',NULL,'9cd7e53a-da9d-4f2b-9b32-328be32da2f0','2026-07-23 07:01:09','2026-07-23 07:01:09'),('487bc480-8664-11f1-a50d-aa44e656c57d','7f1b9697-2c7f-4e4c-b76d-2c642e3dc13f',NULL,4,'Seção Mestra e Perfil Estrutural',NULL,'NORMAM, Anexo 3-F',NULL,1,1,1,'PENDENTE',NULL,'9cd7e53a-da9d-4f2b-9b32-328be32da2f0','2026-07-23 07:01:09','2026-07-23 07:01:09'),('487bcc44-8664-11f1-a50d-aa44e656c57d','7f1b9697-2c7f-4e4c-b76d-2c642e3dc13f',NULL,5,'Curvas Hidrostáticas e cálculos',NULL,'NORMAM, Anexo 3-F',NULL,1,1,1,'PENDENTE',NULL,'9cd7e53a-da9d-4f2b-9b32-328be32da2f0','2026-07-23 07:01:09','2026-07-23 07:01:09'),('487bd292-8664-11f1-a50d-aa44e656c57d','7f1b9697-2c7f-4e4c-b76d-2c642e3dc13f',NULL,6,'Estudo/Folheto de Estabilidade',NULL,'NORMAM, Anexo 3-F',NULL,1,1,1,'PENDENTE',NULL,'9cd7e53a-da9d-4f2b-9b32-328be32da2f0','2026-07-23 07:01:09','2026-07-23 07:01:09'),('487bd8a6-8664-11f1-a50d-aa44e656c57d','7f1b9697-2c7f-4e4c-b76d-2c642e3dc13f',NULL,7,'Plano de Capacidade',NULL,'NORMAM, Anexo 3-F',NULL,1,1,1,'PENDENTE',NULL,'9cd7e53a-da9d-4f2b-9b32-328be32da2f0','2026-07-23 07:01:09','2026-07-23 07:01:09'),('487bdf20-8664-11f1-a50d-aa44e656c57d','7f1b9697-2c7f-4e4c-b76d-2c642e3dc13f',NULL,8,'Plano de Segurança',NULL,'NORMAM, Anexo 3-F',NULL,1,1,1,'PENDENTE',NULL,'9cd7e53a-da9d-4f2b-9b32-328be32da2f0','2026-07-23 07:01:09','2026-07-23 07:01:09'),('487be6f8-8664-11f1-a50d-aa44e656c57d','7f1b9697-2c7f-4e4c-b76d-2c642e3dc13f',NULL,9,'Anotação de Responsabilidade Técnica (ART)',NULL,'NORMAM, Anexo 3-F',NULL,1,1,1,'PENDENTE',NULL,'9cd7e53a-da9d-4f2b-9b32-328be32da2f0','2026-07-23 07:01:09','2026-07-23 07:01:09');
/*!40000 ALTER TABLE `analise_planos_itens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `analise_planos_pareceres`
--

DROP TABLE IF EXISTS `analise_planos_pareceres`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `analise_planos_pareceres` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `numero` varchar(40) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `analise_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `versao` int unsigned NOT NULL,
  `finalidade` enum('ANALISE_INICIAL','CUMPRIMENTO_EXIGENCIAS','CONCLUSIVO') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `submissao_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `relatorio_anterior_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `norma_versao_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `resultado` enum('EXIGENCIAS','APROVADO','APROVADO_COM_EXIGENCIAS','REPROVADO') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `resumo` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `conclusao` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `snapshot_json` json DEFAULT NULL,
  `status` enum('MINUTA','AGUARDANDO_ASSINATURA_ANALISTA','AGUARDANDO_APROVACAO_ADMIN','PUBLICADO','DEVOLVIDO','CANCELADO') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'MINUTA',
  `responsavel_assinatura_id` int DEFAULT NULL,
  `assinado_analista_em` datetime DEFAULT NULL,
  `assinatura_analista_ip` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `devolvido_motivo` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `caminho_pdf_final` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `hash_pdf_final` char(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `publicado_em` datetime DEFAULT NULL,
  `validado_por` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `validado_em` datetime DEFAULT NULL,
  `criado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_analise_parecer_versao` (`analise_id`,`versao`),
  UNIQUE KEY `uk_relatorio_analise_numero` (`numero`),
  KEY `idx_analise_parecer_publicado` (`analise_id`,`status`,`publicado_em`),
  KEY `fk_analise_parecer_responsavel` (`responsavel_assinatura_id`),
  KEY `fk_analise_parecer_usuario` (`criado_por`),
  KEY `idx_relatorio_analise_cadeia` (`analise_id`,`relatorio_anterior_id`),
  KEY `idx_relatorio_analise_submissao` (`submissao_id`),
  KEY `fk_relatorio_analise_anterior` (`relatorio_anterior_id`),
  KEY `fk_relatorio_analise_norma` (`norma_versao_id`),
  KEY `fk_relatorio_analise_validador` (`validado_por`),
  CONSTRAINT `fk_analise_parecer` FOREIGN KEY (`analise_id`) REFERENCES `analises_planos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_analise_parecer_responsavel` FOREIGN KEY (`responsavel_assinatura_id`) REFERENCES `responsaveis_assinatura` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_analise_parecer_usuario` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `fk_relatorio_analise_anterior` FOREIGN KEY (`relatorio_anterior_id`) REFERENCES `analise_planos_pareceres` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_relatorio_analise_norma` FOREIGN KEY (`norma_versao_id`) REFERENCES `matriz_normativa_versoes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_relatorio_analise_submissao` FOREIGN KEY (`submissao_id`) REFERENCES `analise_planos_submissoes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_relatorio_analise_validador` FOREIGN KEY (`validado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `analise_planos_pareceres`
--

LOCK TABLES `analise_planos_pareceres` WRITE;
/*!40000 ALTER TABLE `analise_planos_pareceres` DISABLE KEYS */;
/*!40000 ALTER TABLE `analise_planos_pareceres` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `analise_planos_relatorio_exigencias`
--

DROP TABLE IF EXISTS `analise_planos_relatorio_exigencias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `analise_planos_relatorio_exigencias` (
  `id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `relatorio_id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `exigencia_id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `submissao_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `resultado` enum('CUMPRIDA','PARCIAL','NAO_CUMPRIDA') COLLATE utf8mb4_general_ci NOT NULL,
  `manifestacao_tecnica` text COLLATE utf8mb4_general_ci NOT NULL,
  `descricao_snapshot` text COLLATE utf8mb4_general_ci NOT NULL,
  `referencia_snapshot` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `criado_por` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_relatorio_resultado_exigencia` (`relatorio_id`,`exigencia_id`),
  KEY `idx_resultado_exigencia_vigente` (`exigencia_id`,`criado_em`),
  KEY `fk_resultado_submissao` (`submissao_id`),
  KEY `fk_resultado_criador` (`criado_por`),
  CONSTRAINT `fk_resultado_criador` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_resultado_exigencia` FOREIGN KEY (`exigencia_id`) REFERENCES `analise_planos_exigencias` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_resultado_relatorio` FOREIGN KEY (`relatorio_id`) REFERENCES `analise_planos_pareceres` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_resultado_submissao` FOREIGN KEY (`submissao_id`) REFERENCES `analise_planos_submissoes` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `analise_planos_relatorio_exigencias`
--

LOCK TABLES `analise_planos_relatorio_exigencias` WRITE;
/*!40000 ALTER TABLE `analise_planos_relatorio_exigencias` DISABLE KEYS */;
/*!40000 ALTER TABLE `analise_planos_relatorio_exigencias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `analise_planos_submissoes`
--

DROP TABLE IF EXISTS `analise_planos_submissoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `analise_planos_submissoes` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `analise_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `revisao` int unsigned NOT NULL,
  `descricao` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `recebido_em` date NOT NULL,
  `origem` enum('ANALISTA','PORTAL') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'ANALISTA',
  `portal_cliente_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `criado_por` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_analise_submissao_revisao` (`analise_id`,`revisao`),
  KEY `fk_analise_submissao_usuario` (`criado_por`),
  KEY `idx_submissao_origem` (`analise_id`,`origem`,`criado_em`),
  KEY `fk_submissao_portal_cliente` (`portal_cliente_id`),
  CONSTRAINT `fk_analise_submissao` FOREIGN KEY (`analise_id`) REFERENCES `analises_planos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_analise_submissao_usuario` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `fk_submissao_portal_cliente` FOREIGN KEY (`portal_cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `analise_planos_submissoes`
--

LOCK TABLES `analise_planos_submissoes` WRITE;
/*!40000 ALTER TABLE `analise_planos_submissoes` DISABLE KEYS */;
/*!40000 ALTER TABLE `analise_planos_submissoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `analises_planos`
--

DROP TABLE IF EXISTS `analises_planos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `analises_planos` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `numero` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `proposta_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `servico_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `vendedor_origem_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `embarcacao_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `solicitante_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tipo_processo` enum('LC','LCEC','LA','LR') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `enquadramento` enum('NORMAM-201','NORMAM-202') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `norma_versao_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `classe_certificacao` enum('EC1','EC2') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `arqueacao_bruta` decimal(10,2) DEFAULT NULL,
  `numero_passageiros` int unsigned DEFAULT NULL,
  `possui_propulsao` tinyint(1) DEFAULT NULL,
  `embarcacao_classificada` tinyint(1) DEFAULT NULL,
  `tipo_navegacao` varchar(120) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `construcao_concluida` tinyint(1) DEFAULT NULL,
  `objeto` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `estaleiro` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `numero_casco` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `responsavel_projeto_nome` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `responsavel_projeto_registro` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `art_numero` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `analista_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `prazo_agendado_em` datetime DEFAULT NULL,
  `iniciado_em` datetime DEFAULT NULL,
  `legado_sem_proposta` tinyint(1) NOT NULL DEFAULT '0',
  `legado_fora_escopo` tinyint(1) NOT NULL DEFAULT '0',
  `fundamento_bloqueio` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `responsavel_assinatura_id` int DEFAULT NULL,
  `status` enum('AGUARDANDO_AGENDAMENTO','AGENDADA','EM_ANALISE','AGUARDANDO_DOCUMENTOS','AGUARDANDO_ASSINATURA_ANALISTA','AGUARDANDO_APROVACAO_ADMIN','CONCLUIDA','REPROVADA','CANCELADA') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'AGUARDANDO_AGENDAMENTO',
  `observacoes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `criado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_analise_planos_numero` (`numero`),
  UNIQUE KEY `uk_analise_origem` (`proposta_id`,`embarcacao_id`,`servico_id`),
  KEY `idx_analise_planos_embarcacao` (`embarcacao_id`,`status`),
  KEY `idx_analise_planos_analista` (`analista_id`,`status`),
  KEY `fk_analise_planos_solicitante` (`solicitante_id`),
  KEY `fk_analise_planos_responsavel` (`responsavel_assinatura_id`),
  KEY `fk_analise_planos_criador` (`criado_por`),
  KEY `idx_analise_vendedor` (`vendedor_origem_id`,`status`),
  KEY `idx_analise_prazo` (`analista_id`,`prazo_agendado_em`,`status`),
  KEY `fk_analise_servico` (`servico_id`),
  KEY `idx_analise_norma_legado` (`enquadramento`,`legado_fora_escopo`),
  KEY `fk_analise_norma_versao` (`norma_versao_id`),
  CONSTRAINT `fk_analise_norma_versao` FOREIGN KEY (`norma_versao_id`) REFERENCES `matriz_normativa_versoes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_analise_planos_analista` FOREIGN KEY (`analista_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `fk_analise_planos_criador` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `fk_analise_planos_embarcacao` FOREIGN KEY (`embarcacao_id`) REFERENCES `embarcacoes` (`id`),
  CONSTRAINT `fk_analise_planos_responsavel` FOREIGN KEY (`responsavel_assinatura_id`) REFERENCES `responsaveis_assinatura` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_analise_planos_solicitante` FOREIGN KEY (`solicitante_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_analise_proposta` FOREIGN KEY (`proposta_id`) REFERENCES `propostas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_analise_servico` FOREIGN KEY (`servico_id`) REFERENCES `servicos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_analise_vendedor` FOREIGN KEY (`vendedor_origem_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `analises_planos`
--

LOCK TABLES `analises_planos` WRITE;
/*!40000 ALTER TABLE `analises_planos` DISABLE KEYS */;
INSERT INTO `analises_planos` VALUES ('7f1b9697-2c7f-4e4c-b76d-2c642e3dc13f','AM-RAP-1/26',NULL,NULL,NULL,'09542979-d78e-4095-8ee2-a01e3e7efa07',NULL,'LC','NORMAM-202','b6db69f4-69dd-4ad4-a7cb-202000000001',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'wwwwwwwwwwww',NULL,NULL,NULL,NULL,NULL,'9cd7e53a-da9d-4f2b-9b32-328be32da2f0',NULL,NULL,1,0,NULL,2,'EM_ANALISE',NULL,'9cd7e53a-da9d-4f2b-9b32-328be32da2f0','2026-07-23 07:01:09','2026-07-24 05:27:36');
/*!40000 ALTER TABLE `analises_planos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assinatura_convites`
--

DROP TABLE IF EXISTS `assinatura_convites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assinatura_convites` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `documento_tipo` enum('CSN','CNBL','CNARQ') COLLATE utf8mb4_unicode_ci NOT NULL,
  `documento_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `responsavel_id` int NOT NULL,
  `usuario_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `token_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_destinatario` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('ATIVO','PROCESSANDO','UTILIZADO','CANCELADO') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ATIVO',
  `autenticacao_metodo` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EMAIL_MAGIC_LINK',
  `expira_em` datetime NOT NULL,
  `enviado_em` datetime DEFAULT NULL,
  `utilizado_em` datetime DEFAULT NULL,
  `cancelado_em` datetime DEFAULT NULL,
  `cancelado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assinatura_convites`
--

LOCK TABLES `assinatura_convites` WRITE;
/*!40000 ALTER TABLE `assinatura_convites` DISABLE KEYS */;
/*!40000 ALTER TABLE `assinatura_convites` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `auditoria_fluxo_normativo`
--

DROP TABLE IF EXISTS `auditoria_fluxo_normativo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `auditoria_fluxo_normativo` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `entidade` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `entidade_id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `evento` varchar(80) COLLATE utf8mb4_general_ci NOT NULL,
  `usuario_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `perfil` varchar(40) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ip` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estado_anterior` varchar(80) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estado_novo` varchar(80) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `norma_versao_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fundamento` text COLLATE utf8mb4_general_ci,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_auditoria_entidade` (`entidade`,`entidade_id`,`criado_em`),
  KEY `fk_auditoria_norma_versao` (`norma_versao_id`),
  CONSTRAINT `fk_auditoria_norma_versao` FOREIGN KEY (`norma_versao_id`) REFERENCES `matriz_normativa_versoes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `auditoria_fluxo_normativo`
--

LOCK TABLES `auditoria_fluxo_normativo` WRITE;
/*!40000 ALTER TABLE `auditoria_fluxo_normativo` DISABLE KEYS */;
/*!40000 ALTER TABLE `auditoria_fluxo_normativo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `campo_login_tentativas`
--

DROP TABLE IF EXISTS `campo_login_tentativas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `campo_login_tentativas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `email_hash` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_hash` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sucesso` tinyint(1) NOT NULL DEFAULT '0',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_campo_login_bloqueio` (`email_hash`,`ip_hash`,`criado_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `campo_login_tentativas`
--

LOCK TABLES `campo_login_tentativas` WRITE;
/*!40000 ALTER TABLE `campo_login_tentativas` DISABLE KEYS */;
/*!40000 ALTER TABLE `campo_login_tentativas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `campo_sessoes`
--

DROP TABLE IF EXISTS `campo_sessoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `campo_sessoes` (
  `id` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `usuario_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ultimo_acesso_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expira_em` datetime NOT NULL,
  `revogado_em` datetime DEFAULT NULL,
  `ip_hash` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_agent_hash` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_campo_sessoes_usuario` (`usuario_id`,`expira_em`),
  CONSTRAINT `fk_campo_sessoes_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `campo_sessoes`
--

LOCK TABLES `campo_sessoes` WRITE;
/*!40000 ALTER TABLE `campo_sessoes` DISABLE KEYS */;
/*!40000 ALTER TABLE `campo_sessoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cert_convalidacoes`
--

DROP TABLE IF EXISTS `cert_convalidacoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cert_convalidacoes` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT (uuid()),
  `tipo_certificado` enum('CNBL') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Convalidacoes exclusivas do certificado CNBL',
  `certificado_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'ID do certificado (certificados_cnbl ou certificados_cnarq)',
  `numero_vistoria` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Ex: 1Âª VIST. ANUAL, 2Âª VIST. ANUAL, etc',
  `data_inicio` date DEFAULT NULL,
  `data_fim` date DEFAULT NULL,
  `local_data` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `vistoriador` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `atualizado_em` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cert_convalidacoes_tipo` (`tipo_certificado`),
  KEY `idx_cert_convalidacoes_certificado` (`certificado_id`,`tipo_certificado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cert_convalidacoes`
--

LOCK TABLES `cert_convalidacoes` WRITE;
/*!40000 ALTER TABLE `cert_convalidacoes` DISABLE KEYS */;
/*!40000 ALTER TABLE `cert_convalidacoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `certificados_cht`
--

DROP TABLE IF EXISTS `certificados_cht`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
  `despachante_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_certificados_cht_numero` (`numero_certificado`),
  KEY `idx_certificados_cht_numero` (`numero_relatorio_ht`),
  KEY `idx_certificados_cht_status` (`status`),
  KEY `idx_certificados_cht_ativo` (`ativo`),
  KEY `idx_certificados_ht_profissional` (`profissional_empresa`),
  KEY `fk_cht_vistoria` (`vistoria_id`),
  CONSTRAINT `fk_cht_vistoria` FOREIGN KEY (`vistoria_id`) REFERENCES `vistorias` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `certificados_cht`
--

LOCK TABLES `certificados_cht` WRITE;
/*!40000 ALTER TABLE `certificados_cht` DISABLE KEYS */;
/*!40000 ALTER TABLE `certificados_cht` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `certificados_cnarq`
--

DROP TABLE IF EXISTS `certificados_cnarq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
  `tipo_vistoria_certificado` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `observacoes_verso` text COLLATE utf8mb4_general_ci,
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
  `despachante_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_certificados_cnarq_numero` (`numero`),
  KEY `idx_certificados_cnarq_status` (`status`),
  KEY `idx_certificados_cnarq_ativo` (`ativo`),
  KEY `fk_cnarq_vistoria` (`vistoria_id`),
  CONSTRAINT `fk_cnarq_vistoria` FOREIGN KEY (`vistoria_id`) REFERENCES `vistorias` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `certificados_cnarq`
--

LOCK TABLES `certificados_cnarq` WRITE;
/*!40000 ALTER TABLE `certificados_cnarq` DISABLE KEYS */;
/*!40000 ALTER TABLE `certificados_cnarq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `certificados_cnbl`
--

DROP TABLE IF EXISTS `certificados_cnbl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
  `tipo_vistoria_certificado` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `observacoes_verso` text COLLATE utf8mb4_general_ci,
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
  `despachante_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_certificados_cnbl_numero` (`numero`),
  KEY `idx_certificados_cnbl_status` (`status`),
  KEY `idx_certificados_cnbl_ativo` (`ativo`),
  KEY `fk_cnbl_vistoria` (`vistoria_id`),
  CONSTRAINT `fk_cnbl_vistoria` FOREIGN KEY (`vistoria_id`) REFERENCES `vistorias` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `certificados_cnbl`
--

LOCK TABLES `certificados_cnbl` WRITE;
/*!40000 ALTER TABLE `certificados_cnbl` DISABLE KEYS */;
/*!40000 ALTER TABLE `certificados_cnbl` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `certificados_csn`
--

DROP TABLE IF EXISTS `certificados_csn`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
  `vistoria_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero` (`numero`),
  UNIQUE KEY `token_assinatura` (`token_assinatura`),
  KEY `criado_por` (`criado_por`),
  KEY `fk_csn_vistoria` (`vistoria_id`),
  CONSTRAINT `certificados_csn_ibfk_1` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_csn_vistoria` FOREIGN KEY (`vistoria_id`) REFERENCES `vistorias` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `certificados_csn`
--

LOCK TABLES `certificados_csn` WRITE;
/*!40000 ALTER TABLE `certificados_csn` DISABLE KEYS */;
INSERT INTO `certificados_csn` VALUES ('e7501710-9765-4ef1-a09d-0e0c77f5e9fe','AM-CSN-1/26','Condicional','80ad3e6beb38ca6f629d1a286104ad517d6f80799ee4c7066ee7701112f3d247','AMAZON NAVAL','LANCHA TESTE AMAZÔNIA 061558','TESTE260723061558','T3061558','Transporte de Passageiros','Lancha','2023',12.80,'24.60','Interior','Área 1','Yamaha - F300 BETX - MOT-260723061558','224 kW / 300 HP','Fibra de Vidro',0,30,'Lotação fictícia para teste','','AM-REL-V-1/26','2026-07-24','2026-07-24','belem','NORMAM-202','Inicial',0,1,'2026-07-23','2026-10-22','Belém-PA','Victal Donanzan','Engenheiro Naval','CREA: 22.537',2,'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAA4QAAAEsCAYAAACbnn2RAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAgAElEQVR4nOzd2bMk130n9m+ulbXeqrv3vqKxNgiCmyhKoqgZWSONYhR2KLw8eCYcdvjBf4b/Az85YiIc4Ziw/TC2NLZG0kjUjESRIgkSIIi90ehG77fvvtReuR4/ZN7fOXmBhhogerm3vp8H8hZO3dtVWVmZefL8FkspBSIiIiIiIpo+9pN+AURERERERPRkcEJIREREREQ0pTghJCIiIiIimlKcEBIREREREU0pTgiJiIiIiIimFCeEREREREREU4oTQiIiIiIioinFCSEREREREdGU4oSQiIiIiIhoSnFCSERERERENKU4ISQiIiIiIppSnBASERERERFNKU4IiYiIiIiIphQnhERERERERFOKE0IiIiIiIqIpxQkhERERERHRlOKEkIiIiIiIaEq5T/oFfNmUUupJvwYiIiIiIjp6LMuynvRr+LJxhZCIiIiIiGhKcUJIREREREQ0pTghJCIiIiIimlKcEBIREREREU0pTgiJiIiIiIimFCeEREREREREU4oTQiIiIiIioinFCSEREREREdGU4oSQiIiIiIhoSnFCSERERERENKU4ISQiIiIiIppSnBASERERERFNKU4IiYiIiIiIphQnhERERERERFOKE0IiIiIiIqIpxQkhERERERHRlOKEkIiIiIiIaEpxQkhERERERDSlOCEkIiIiIiKaUpwQEhERERERTSlOCImIiIiIiKYUJ4RERERERERTihNCIiIiIiKiKcUJIRERERER0ZTihJCIiIiIiGhKcUJIREREREQ0pTghJCIiIiIimlKcEBIREREREU0pTgiJiIiIiIimFCeEREREREREU4oTQiIiIiIioinFCSEREREREdGU4oSQiIiIiIhoSnFCSERERERENKU4ISQiIiIiIppSnBASERERERFNKU4IiYiIiIiIphQnhERERERERFOKE0IiIiIiIqIpxQkhERERERHRlHKf9AsgIpp2UZJBFT/v9SPs9iMZizMlPy+2A8w1fXnsOtbjeolERER0RHFCSET0hPVGiUwI37y2i9c/3JKx3TCTn//Zq8v49vNzAADLAppVHsKJiIjoV8OQUSIiIiIioinFCSEREREREdGUYrwREdFjNhyn6I1i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','172.23.0.1','2026-07-23 03:57:00',1,'storage/documentos_aprovados/2026/csn/c08d2aaf-deb0-4978-b04f-33f19c4cbb00.pdf','c733d5a9a93dea00e20f2b441835ddedcee5caf8b5a06cfac9671b70311449c1','assinado',1,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-07-23 06:54:09','2026-07-23 06:57:00','4f3bd30c-44b9-47f4-800d-25601773e329'),('eb7bc984-380d-4242-8f9e-5e72d0c57e77','AM-CSN-2/26','Condicional','c0dcfe18b5b40368a7e01cbf10b7cc2c4d14c8308514a030666b06834e1846dd','AMAZON NAVAL','LANCHA TESTE AMAZÔNIA 061558','TESTE260723061558','T3061558','Transporte de Passageiros','Lancha','2023',12.80,'24.60','Interior','Área 1','Yamaha - F300 BETX - MOT-260723061558','224 kW / 300 HP','Fibra de Vidro',0,30,'Lotação fictícia para teste','','AM-REL-V-1/26','2026-07-24','2026-07-24','belem','NORMAM-202','Inicial',0,1,'2026-07-23','2026-10-22','Belém-PA','Victal Donanzan','Engenheiro Naval','CREA: 22.537',2,NULL,NULL,NULL,0,NULL,NULL,'emitido',1,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-07-23 06:59:50','2026-07-23 06:59:50','4f3bd30c-44b9-47f4-800d-25601773e329');
/*!40000 ALTER TABLE `certificados_csn` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `certificados_lc`
--

DROP TABLE IF EXISTS `certificados_lc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
  `analise_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `despachante_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_licenca_analise` (`analise_id`),
  KEY `idx_certificados_lc_numero` (`numero_lc`),
  KEY `idx_certificados_lc_status` (`status`),
  KEY `idx_certificados_lc_ativo` (`ativo`),
  KEY `idx_certificados_lc_embarcacao` (`embarcacao_id`),
  KEY `idx_certificados_lc_tipo` (`tipo_licenca`),
  KEY `fk_lc_vistoria` (`vistoria_id`),
  CONSTRAINT `fk_lc_vistoria` FOREIGN KEY (`vistoria_id`) REFERENCES `vistorias` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_licenca_analise` FOREIGN KEY (`analise_id`) REFERENCES `analises_planos` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `certificados_lc`
--

LOCK TABLES `certificados_lc` WRITE;
/*!40000 ALTER TABLE `certificados_lc` DISABLE KEYS */;
/*!40000 ALTER TABLE `certificados_lc` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `certificados_lp`
--

DROP TABLE IF EXISTS `certificados_lp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
  `despachante_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_certificados_lp_numero` (`numero_lp`),
  KEY `idx_certificados_lp_status` (`status`),
  KEY `idx_certificados_lp_ativo` (`ativo`),
  KEY `idx_certificados_lp_embarcacao` (`embarcacao_id`),
  KEY `idx_certificados_lp_tipo` (`tipo_licenca`),
  KEY `fk_lp_vistoria` (`vistoria_id`),
  CONSTRAINT `fk_lp_vistoria` FOREIGN KEY (`vistoria_id`) REFERENCES `vistorias` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `certificados_lp`
--

LOCK TABLES `certificados_lp` WRITE;
/*!40000 ALTER TABLE `certificados_lp` DISABLE KEYS */;
/*!40000 ALTER TABLE `certificados_lp` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cliente_password_resets`
--

DROP TABLE IF EXISTS `cliente_password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cliente_password_resets` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT (uuid()),
  `cliente_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `token_hash` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `expira_em` datetime NOT NULL,
  `usado_em` datetime DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cliente_reset_token` (`token_hash`),
  KEY `idx_cliente_reset_cliente` (`cliente_id`),
  KEY `idx_cliente_reset_expira` (`expira_em`),
  CONSTRAINT `fk_cliente_reset_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cliente_password_resets`
--

LOCK TABLES `cliente_password_resets` WRITE;
/*!40000 ALTER TABLE `cliente_password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `cliente_password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cliente_portal_acessos`
--

DROP TABLE IF EXISTS `cliente_portal_acessos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cliente_portal_acessos` (
  `cliente_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `login` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `senha_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `forcar_troca_senha` tinyint(1) NOT NULL DEFAULT '1',
  `ultimo_login_em` datetime DEFAULT NULL,
  `criado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`cliente_id`),
  UNIQUE KEY `uk_cliente_portal_login` (`login`),
  KEY `idx_cliente_portal_ativo` (`ativo`),
  CONSTRAINT `fk_cliente_portal_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cliente_portal_acessos`
--

LOCK TABLES `cliente_portal_acessos` WRITE;
/*!40000 ALTER TABLE `cliente_portal_acessos` DISABLE KEYS */;
INSERT INTO `cliente_portal_acessos` VALUES ('e82942df-63da-4093-82b7-c2849fe3634e','ronokedas2020@gmail.com','$2y$10$95kLsmxTLneoYsPdrwwRM.o.GmNsdSRcCmu91WqfnR7bjIgkpU3pS',1,0,'2026-07-23 14:09:58','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-07-23 14:09:45','2026-07-23 14:10:05');
/*!40000 ALTER TABLE `cliente_portal_acessos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clientes`
--

DROP TABLE IF EXISTS `clientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
  `atualizado_em` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cpf_cnpj` (`cpf_cnpj`),
  KEY `criado_por` (`criado_por`),
  CONSTRAINT `clientes_ibfk_1` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clientes`
--

LOCK TABLES `clientes` WRITE;
/*!40000 ALTER TABLE `clientes` DISABLE KEYS */;
INSERT INTO `clientes` VALUES ('e82942df-63da-4093-82b7-c2849fe3634e','Rosano Silva De Souza','PF','38303451863','proprietario','(91) 98934-0275','ronokedas2020@gmail.com','Passagem Monte Cristo 7\r\nCasa 7B','ATIVO',NULL,NULL,NULL,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-07-23 06:16:15','2026-07-23 06:16:15');
/*!40000 ALTER TABLE `clientes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clientes_embarcacoes`
--

DROP TABLE IF EXISTS `clientes_embarcacoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clientes_embarcacoes` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT (uuid()),
  `cliente_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `embarcacao_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('ATIVO','INATIVO') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'ATIVO',
  `vinculado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `desvinculado_em` datetime DEFAULT NULL,
  `vinculado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `desvinculado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `vinculo_ativo_chave` varchar(73) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cliente_embarcacao_ativa` (`vinculo_ativo_chave`),
  KEY `embarcacao_id` (`embarcacao_id`),
  KEY `idx_cliente_embarcacao_historico` (`cliente_id`,`embarcacao_id`,`vinculado_em`),
  KEY `idx_cliente_embarcacao_status` (`cliente_id`,`status`),
  CONSTRAINT `clientes_embarcacoes_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `clientes_embarcacoes_ibfk_2` FOREIGN KEY (`embarcacao_id`) REFERENCES `embarcacoes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clientes_embarcacoes`
--

LOCK TABLES `clientes_embarcacoes` WRITE;
/*!40000 ALTER TABLE `clientes_embarcacoes` DISABLE KEYS */;
INSERT INTO `clientes_embarcacoes` VALUES ('02af13c5-865e-11f1-a50d-aa44e656c57d','e82942df-63da-4093-82b7-c2849fe3634e','09542979-d78e-4095-8ee2-a01e3e7efa07','ATIVO','2026-07-23 06:16:15',NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4',NULL,'e82942df-63da-4093-82b7-c2849fe3634e:09542979-d78e-4095-8ee2-a01e3e7efa07','2026-07-23 06:16:15');
/*!40000 ALTER TABLE `clientes_embarcacoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clientes_tipos_embarcacao`
--

DROP TABLE IF EXISTS `clientes_tipos_embarcacao`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clientes_tipos_embarcacao` (
  `cliente_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tipo_embarcacao_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`cliente_id`,`tipo_embarcacao_id`),
  KEY `idx_cte_tipo_embarcacao` (`tipo_embarcacao_id`),
  CONSTRAINT `fk_cte_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cte_tipo_embarcacao` FOREIGN KEY (`tipo_embarcacao_id`) REFERENCES `tipos_embarcacao` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clientes_tipos_embarcacao`
--

LOCK TABLES `clientes_tipos_embarcacao` WRITE;
/*!40000 ALTER TABLE `clientes_tipos_embarcacao` DISABLE KEYS */;
/*!40000 ALTER TABLE `clientes_tipos_embarcacao` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `configuracoes`
--

DROP TABLE IF EXISTS `configuracoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `configuracoes` (
  `chave` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `valor` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `descricao` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `atualizado_em` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`chave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `configuracoes`
--

LOCK TABLES `configuracoes` WRITE;
/*!40000 ALTER TABLE `configuracoes` DISABLE KEYS */;
INSERT INTO `configuracoes` VALUES ('acesso_documentacao_usuarios','[3774]','IDs dos usuários com acesso à documentação','2026-06-29 06:38:14'),('backup_email','ronokedas2020@gmail.com','E-mail para receber backups do banco de dados','2026-06-29 05:22:15'),('dados_teste_embarcacoes','1','Exibe o preenchimento rápido com dados fictícios no cadastro de embarcações','2026-07-19 02:29:40'),('meta_mensagem','Ao bater a meta, teremos um dia especial com toda a equipe.','Mensagem da meta mensal exibida para a equipe','2026-07-16 17:48:32'),('meta_mensal','180000.00','Meta mensal de faturamento comercial em R$','2026-07-06 22:49:46'),('responsavel_assinatura_cargo','Engenheiro Naval',NULL,'2026-07-02 17:34:06'),('responsavel_assinatura_nome','João Responsável',NULL,'2026-07-02 17:34:06'),('responsavel_assinatura_registro','CREA 123456',NULL,'2026-07-02 17:34:06');
/*!40000 ALTER TABLE `configuracoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contratos`
--

DROP TABLE IF EXISTS `contratos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
  `atualizado_em` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `proposta_id` (`proposta_id`),
  KEY `criado_por` (`criado_por`),
  KEY `contratos_cliente_fk` (`cliente_id`),
  CONSTRAINT `contratos_cliente_fk` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `contratos_ibfk_2` FOREIGN KEY (`proposta_id`) REFERENCES `propostas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `contratos_ibfk_3` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contratos`
--

LOCK TABLES `contratos` WRITE;
/*!40000 ALTER TABLE `contratos` DISABLE KEYS */;
/*!40000 ALTER TABLE `contratos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `csn_convalidacoes`
--

DROP TABLE IF EXISTS `csn_convalidacoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `csn_convalidacoes` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT (uuid()),
  `certificado_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `numero_vistoria` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `data_inicio` date DEFAULT NULL,
  `data_fim` date DEFAULT NULL,
  `local_data` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `vistoriador` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `atualizado_em` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `certificado_id` (`certificado_id`),
  CONSTRAINT `csn_convalidacoes_ibfk_1` FOREIGN KEY (`certificado_id`) REFERENCES `certificados_csn` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `csn_convalidacoes`
--

LOCK TABLES `csn_convalidacoes` WRITE;
/*!40000 ALTER TABLE `csn_convalidacoes` DISABLE KEYS */;
/*!40000 ALTER TABLE `csn_convalidacoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `csn_distribuicao_passageiros`
--

DROP TABLE IF EXISTS `csn_distribuicao_passageiros`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `csn_distribuicao_passageiros` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT (uuid()),
  `certificado_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `item_codigo` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `local_nome` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `quantidade` int DEFAULT '0',
  `conves_principal` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `conves_superior` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `area_lazer` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `unidade` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `certificado_id` (`certificado_id`),
  CONSTRAINT `csn_distribuicao_passageiros_ibfk_1` FOREIGN KEY (`certificado_id`) REFERENCES `certificados_csn` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `csn_distribuicao_passageiros`
--

LOCK TABLES `csn_distribuicao_passageiros` WRITE;
/*!40000 ALTER TABLE `csn_distribuicao_passageiros` DISABLE KEYS */;
INSERT INTO `csn_distribuicao_passageiros` VALUES ('0be291a1-8185-43f0-9199-9539f20ee97c','eb7bc984-380d-4242-8f9e-5e72d0c57e77','passageiros_sentados','Passageiros sentados',NULL,'20','','','passageiros'),('240f697e-fb5a-4e57-9fa4-92022ec0a126','eb7bc984-380d-4242-8f9e-5e72d0c57e77','porao_carga_01','Porão de carga 01 (carga geral)',NULL,'','','','t'),('39c216d8-1773-41e7-b60d-ba79b167d129','eb7bc984-380d-4242-8f9e-5e72d0c57e77','almoxarifado_conves_principal','Almoxarifado no convés principal',NULL,'','','','t'),('39ffa841-e850-44fe-870b-1c8be9325834','e7501710-9765-4ef1-a09d-0e0c77f5e9fe','passageiros_em_pe','Passageiros em pé',NULL,'10','','','passageiros'),('5b97651f-6455-4ff5-9fa2-56123f1e1deb','eb7bc984-380d-4242-8f9e-5e72d0c57e77','passageiros_camarote','Passageiros em camarote',NULL,'','','','passageiros'),('5bb74fad-b4fa-44a7-b4b7-881f2b2c43ed','eb7bc984-380d-4242-8f9e-5e72d0c57e77','paiol_casco','Paiol no casco (mantimentos e materiais diversos)',NULL,'','','','t'),('5fec10f4-b555-4295-8ac5-f14368335e5c','e7501710-9765-4ef1-a09d-0e0c77f5e9fe','deposito_conves_principal','Depósito no convés principal',NULL,'','','','t'),('865c2381-6470-4ba7-8f9f-62625dae2cc2','e7501710-9765-4ef1-a09d-0e0c77f5e9fe','passageiros_redes','Passageiros em redes',NULL,'','','','passageiros'),('9d75fdfa-9349-43c9-a5d3-3d84499998fd','e7501710-9765-4ef1-a09d-0e0c77f5e9fe','paiol_casco','Paiol no casco (mantimentos e materiais diversos)',NULL,'','','','t'),('a8b0dac7-67a2-4b39-bc5f-d36cc1753826','e7501710-9765-4ef1-a09d-0e0c77f5e9fe','passageiros_sentados','Passageiros sentados',NULL,'20','','','passageiros'),('b1b601ea-0c39-408f-9991-4ba829901093','eb7bc984-380d-4242-8f9e-5e72d0c57e77','deposito_conves_superior','Depósito no convés superior',NULL,'','','','t'),('bf2e5fbc-d111-44d1-87c2-99d806f5c67f','e7501710-9765-4ef1-a09d-0e0c77f5e9fe','passageiros_camarote','Passageiros em camarote',NULL,'','','','passageiros'),('ce64d7b8-2e07-4dab-b59f-53adbc4458b1','e7501710-9765-4ef1-a09d-0e0c77f5e9fe','almoxarifado_conves_principal','Almoxarifado no convés principal',NULL,'','','','t'),('d412f42e-6118-40bd-8b05-74bb7c444dc7','eb7bc984-380d-4242-8f9e-5e72d0c57e77','passageiros_redes','Passageiros em redes',NULL,'','','','passageiros'),('d50ec6f1-5aab-439e-871f-5ab84e1cef65','e7501710-9765-4ef1-a09d-0e0c77f5e9fe','porao_carga_01','Porão de carga 01 (carga geral)',NULL,'','','','t'),('eb2e6a73-108d-42eb-adfb-7c93642f42f4','eb7bc984-380d-4242-8f9e-5e72d0c57e77','passageiros_em_pe','Passageiros em pé',NULL,'10','','','passageiros'),('ebf54102-0bd6-4a3f-a04c-e3e1ae364574','eb7bc984-380d-4242-8f9e-5e72d0c57e77','deposito_conves_principal','Depósito no convés principal',NULL,'','','','t'),('f63af397-90f5-4eb0-8c07-3c1da51b6938','e7501710-9765-4ef1-a09d-0e0c77f5e9fe','deposito_conves_superior','Depósito no convés superior',NULL,'','','','t');
/*!40000 ALTER TABLE `csn_distribuicao_passageiros` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `documento_aprovacoes`
--

DROP TABLE IF EXISTS `documento_aprovacoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `documento_aprovacoes` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `documento_tipo` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `documento_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `versao` int unsigned NOT NULL DEFAULT '1',
  `responsavel_id` int NOT NULL,
  `aprovador_usuario_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `responsavel_nome` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `responsavel_cpf_cnpj` varchar(18) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `responsavel_cargo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `responsavel_registro` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `aprovador_nome` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `assinatura_arquivo` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `assinatura_hash` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `aprovado_em_utc` datetime NOT NULL,
  `aprovado_em_local` datetime NOT NULL,
  `fuso_horario` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'America/Sao_Paulo',
  `utc_offset` varchar(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `geo_precisao_m` decimal(10,2) DEFAULT NULL,
  `ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `autenticacao_metodo` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SESSAO',
  `assinatura_convite_id` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hash_pdf_original` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hash_pdf_final` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `caminho_pdf_original` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `caminho_pdf_final` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `token_validacao` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('PROCESSANDO','APROVADO','FALHA','CANCELADO') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PROCESSANDO',
  `padrao_assinatura` enum('AUDIT_ONLY','PADES_ICP_BRASIL') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'AUDIT_ONLY',
  `status_pades` enum('NAO_APLICADO','APLICADO','INVALIDO') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'NAO_APLICADO',
  `provedor_assinatura` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `certificado_titular` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `certificado_serial` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `certificado_valido_de` datetime DEFAULT NULL,
  `certificado_valido_ate` datetime DEFAULT NULL,
  `erro_processamento` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_documento_aprovacao_versao` (`documento_tipo`,`documento_id`,`versao`),
  UNIQUE KEY `uk_documento_aprovacao_token` (`token_validacao`),
  KEY `idx_documento_aprovacao_documento` (`documento_tipo`,`documento_id`,`status`),
  KEY `idx_documento_aprovacao_responsavel` (`responsavel_id`),
  KEY `idx_documento_aprovacao_usuario` (`aprovador_usuario_id`),
  KEY `idx_documento_aprovacao_convite` (`assinatura_convite_id`),
  CONSTRAINT `fk_documento_aprovacao_convite` FOREIGN KEY (`assinatura_convite_id`) REFERENCES `assinatura_convites` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `documento_aprovacoes`
--

LOCK TABLES `documento_aprovacoes` WRITE;
/*!40000 ALTER TABLE `documento_aprovacoes` DISABLE KEYS */;
INSERT INTO `documento_aprovacoes` VALUES ('c08d2aaf-deb0-4978-b04f-33f19c4cbb00','CSN','e7501710-9765-4ef1-a09d-0e0c77f5e9fe',1,2,'dd121661-feb4-42f6-895a-68eb0608d1e4','Victal Donanzan','383.034.518-63','Engenheiro Naval','CREA: 22.537','admin','storage/private/assinaturas_responsaveis/2/20260720_100109_90048b1dd4c51d95.png','09da23f7c13fbfbf42c88f65ff2208903086c13f3ed5022813784e45a94bdd13','2026-07-23 06:57:00','2026-07-23 03:57:00','America/Sao_Paulo','-03:00',-1.37620000,-48.37960000,50000.00,'172.23.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0','SESSAO',NULL,'52c61787e6703564b38e95dc51c9c711c9d8c966fea1a078dee2b444c01d681e','c733d5a9a93dea00e20f2b441835ddedcee5caf8b5a06cfac9671b70311449c1','storage/documentos_aprovados/2026/csn/c08d2aaf-deb0-4978-b04f-33f19c4cbb00_original.pdf','storage/documentos_aprovados/2026/csn/c08d2aaf-deb0-4978-b04f-33f19c4cbb00.pdf','c53afaa1be60d940a7a64916761119eea6661ceaaf6489139f63079d039968ea','APROVADO','AUDIT_ONLY','NAO_APLICADO','internal-audit',NULL,NULL,NULL,NULL,NULL,'2026-07-23 06:57:00','2026-07-23 06:57:00');
/*!40000 ALTER TABLE `documento_aprovacoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `documento_artefatos`
--

DROP TABLE IF EXISTS `documento_artefatos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `documento_artefatos` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `documento_tipo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `documento_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `versao` int unsigned NOT NULL DEFAULT '1',
  `status_documento` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `caminho_arquivo` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nome_arquivo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `mime_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'application/pdf',
  `tamanho_bytes` bigint unsigned NOT NULL,
  `sha256` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_documento_artefato` (`documento_tipo`,`documento_id`,`versao`),
  KEY `idx_documento_artefatos_tipo` (`documento_tipo`,`criado_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `documento_artefatos`
--

LOCK TABLES `documento_artefatos` WRITE;
/*!40000 ALTER TABLE `documento_artefatos` DISABLE KEYS */;
/*!40000 ALTER TABLE `documento_artefatos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `documento_assinaturas`
--

DROP TABLE IF EXISTS `documento_assinaturas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `documento_assinaturas` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `documento_tipo` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `documento_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `versao` int unsigned NOT NULL DEFAULT '1',
  `responsavel_id` int NOT NULL,
  `usuario_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
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
  `cancelado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `motivo_cancelamento` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_documento_assinatura_versao` (`documento_tipo`,`documento_id`,`versao`),
  UNIQUE KEY `uk_documento_assinatura_token` (`token_validacao`),
  KEY `idx_documento_assinatura_documento` (`documento_tipo`,`documento_id`,`status`),
  KEY `idx_documento_assinatura_responsavel` (`responsavel_id`),
  KEY `idx_documento_assinatura_usuario` (`usuario_id`),
  CONSTRAINT `fk_documento_assinatura_responsavel` FOREIGN KEY (`responsavel_id`) REFERENCES `responsaveis_assinatura` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_documento_assinatura_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `documento_assinaturas`
--

LOCK TABLES `documento_assinaturas` WRITE;
/*!40000 ALTER TABLE `documento_assinaturas` DISABLE KEYS */;
INSERT INTO `documento_assinaturas` VALUES ('1264f16c-d090-4ff4-bfa3-b3051d07825b','RELATORIO','7a175152-999a-418d-bd14-30d33881910d',1,7,'d2a16613-dfa4-4948-8de4-8c802abdf394','storage/private/assinaturas_responsaveis/7/20260723_042523_00e24fdbe224bad3.png','09da23f7c13fbfbf42c88f65ff2208903086c13f3ed5022813784e45a94bdd13','f0867113c0c28e89604f2f25ae8e21440df824e68b43476557601cb8d9c10e0d','badc1eaf610c618f554b337d18a339d40247a0e6d45b7ddc1d760b5c66f7295f','storage/documentos_assinados/2026/relatorio/1264f16c-d090-4ff4-bfa3-b3051d07825b_original.pdf','storage/documentos_assinados/2026/relatorio/1264f16c-d090-4ff4-bfa3-b3051d07825b.pdf','392c0964f4caced7139b04b7b83175b04286fc4eafe1e2dc0443ad0ef961dec0',-1.37620000,-48.37960000,50000.00,'172.23.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','ASSINADO','2026-07-23 04:25:52',NULL,NULL,NULL,'2026-07-23 07:25:53'),('c2d9cbb8-5288-4556-957b-8d8535fc434d','RELATORIO','0587656a-3ef8-40e9-aeb6-6a6ce918e0bd',1,7,'d2a16613-dfa4-4948-8de4-8c802abdf394','storage/private/assinaturas_responsaveis/7/20260723_042523_00e24fdbe224bad3.png','09da23f7c13fbfbf42c88f65ff2208903086c13f3ed5022813784e45a94bdd13','51051813f92998a9e7399b70e8d1966803190a5210735ba9906894a8d6f42fac','643c4d1c5a9661d3ece94da377c8ce81be32fd82450601819608084088c3dd39','storage/documentos_assinados/2026/relatorio/c2d9cbb8-5288-4556-957b-8d8535fc434d_original.pdf','storage/documentos_assinados/2026/relatorio/c2d9cbb8-5288-4556-957b-8d8535fc434d.pdf','5144f5e2f2634e3b0cfa8514a17ccd35d6875de0e63caf0ccab76e182899032a',-1.37620000,-48.37960000,50000.00,'172.23.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','ASSINADO','2026-07-24 02:10:32',NULL,NULL,NULL,'2026-07-24 05:10:33'),('cff5485e-2762-4681-a0bd-ccd0b10fb385','RELATORIO','4f3bd30c-44b9-47f4-800d-25601773e329',1,2,'dd121661-feb4-42f6-895a-68eb0608d1e4','storage/private/assinaturas_responsaveis/2/20260720_100109_90048b1dd4c51d95.png','09da23f7c13fbfbf42c88f65ff2208903086c13f3ed5022813784e45a94bdd13','689eb25b4fb1ea5d0064aa98fca910edd4d3237b53e95a693719d00874e30926','323723cf0eb51086aa4df48720b14eadf4f00839a77f43f8b5971f7877ed2653','storage/documentos_assinados/2026/relatorio/cff5485e-2762-4681-a0bd-ccd0b10fb385_original.pdf','storage/documentos_assinados/2026/relatorio/cff5485e-2762-4681-a0bd-ccd0b10fb385.pdf','1609393882ddd1eaaa47bd2fdabdda6d5925a39adb4e6555c9e2c01f461f10ec',-1.37620000,-48.37960000,50000.00,'172.23.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','ASSINADO','2026-07-23 03:50:30',NULL,NULL,NULL,'2026-07-23 06:50:31');
/*!40000 ALTER TABLE `documento_assinaturas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_logs`
--

DROP TABLE IF EXISTS `email_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Data/hora do envio',
  PRIMARY KEY (`id`),
  KEY `idx_email_logs_tipo` (`tipo`),
  KEY `idx_email_logs_status` (`status`),
  KEY `idx_email_logs_referencia` (`referencia_tipo`,`referencia_id`),
  KEY `idx_email_logs_enviado_por` (`enviado_por`),
  KEY `idx_email_logs_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_logs`
--

LOCK TABLES `email_logs` WRITE;
/*!40000 ALTER TABLE `email_logs` DISABLE KEYS */;
INSERT INTO `email_logs` VALUES ('269939c1-f557-47a2-9ca2-bc6b781ce064','ronokedas@gmail.com','Documento aguardando assinatura - CSN','assinatura','CSN','e7501710-9765-4ef1-a09d-0e0c77f5e9fe','enviado',NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-07-23 06:54:10'),('28f86df9-86a0-11f1-a50d-aa44e656c57d','ronokedas2020@gmail.com','Acesso ao Portal do Cliente','portal_acesso','clientes','e82942df-63da-4093-82b7-c2849fe3634e','enviado',NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-07-23 14:09:46'),('8b2eda78-b620-472e-9eda-886e62a34a1a','ronokedas@gmail.com','Documento aguardando assinatura - CSN','assinatura','CSN','eb7bc984-380d-4242-8f9e-5e72d0c57e77','enviado',NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-07-23 06:59:51');
/*!40000 ALTER TABLE `email_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `embarcacoes`
--

DROP TABLE IF EXISTS `embarcacoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
  `modelo_motor` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `numero_motor` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
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
  `foto_chave` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `foto_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `foto_nome_original` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `foto_mime_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `foto_tamanho_bytes` bigint unsigned DEFAULT NULL,
  `foto_sha256` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `foto_atualizada_em` datetime DEFAULT NULL,
  `foto_atualizada_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
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
  `acrescimo_agua_salgada` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `registro` (`registro`),
  KEY `criado_por` (`criado_por`),
  KEY `idx_cliente_id` (`cliente_id`),
  KEY `fk_embarcacoes_tipo` (`tipo_embarcacao_id`),
  KEY `fk_embarcacoes_proprietario` (`proprietario_id`),
  KEY `idx_embarcacoes_foto_atualizada` (`foto_atualizada_em`),
  CONSTRAINT `embarcacoes_ibfk_1` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_embarcacoes_proprietario` FOREIGN KEY (`proprietario_id`) REFERENCES `clientes` (`id`),
  CONSTRAINT `fk_embarcacoes_tipo` FOREIGN KEY (`tipo_embarcacao_id`) REFERENCES `tipos_embarcacao` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `embarcacoes`
--

LOCK TABLES `embarcacoes` WRITE;
/*!40000 ALTER TABLE `embarcacoes` DISABLE KEYS */;
INSERT INTO `embarcacoes` VALUES ('09542979-d78e-4095-8ee2-a01e3e7efa07','e82942df-63da-4093-82b7-c2849fe3634e','LANCHA TESTE AMAZÔNIA 061558',NULL,'06a95ffa-75d0-11f1-98f0-5ed0db5eacb7','Lancha','C',1,'Yamaha','F300 BETX','MOT-260723061558','224 kW / 300 HP',NULL,'Rosano Silva De Souza',2023,NULL,12.80,12.20,11.60,1.85,3.75,3.90,'Fibra de Vidro','Transporte de Passageiros','Interior','Área 1','Área 1','24.60','TESTE260723061558','Manaus - AM','T3061558',3,20,10,'Cadastro fictício gerado pelo preenchimento rápido para validação do fluxo do sistema.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-07-23 06:16:00','2026-07-23 06:16:15',0,'Lotação fictícia para teste',1,'Manaus - AM','CASCO-260723061558',8.40,'Estaleiro Modelo Testes Ltda.','12.345.678/0001-90','Avenida Naval, 1000, Distrito Industrial, Manaus - AM',11.30,'Regra I','15/03/2021',1.050,'Casa de máquinas | Popa | 4,20\r\nSalão de passageiros | Meia-nau | 8,50\r\nPique de proa | Proa | 2,10','Salão de passageiros | Meia-nau | 8,50\r\nCompartimento de carga | Proa | 3,20',3.40,'Manaus - AM, 20 de junho de 2022','Não se aplica - primeira arqueação',480,'Tipo B',1.20,'620','740','8200','120','180','240','35');
/*!40000 ALTER TABLE `embarcacoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `escritorios`
--

DROP TABLE IF EXISTS `escritorios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `escritorios` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nome` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `cidade` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `uf` char(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_escritorios_nome_cidade` (`nome`,`cidade`,`uf`),
  KEY `idx_escritorios_ativo` (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `escritorios`
--

LOCK TABLES `escritorios` WRITE;
/*!40000 ALTER TABLE `escritorios` DISABLE KEYS */;
INSERT INTO `escritorios` VALUES ('00000000-0000-4000-8000-000000000100','Matriz','Manaus','AM',1,'2026-07-25 03:31:11','2026-07-25 03:31:11'),('3332440d-dc03-4ab1-8485-8805d098dd6b','Matriz Belém','Belém','PA',1,'2026-07-23 06:10:07','2026-07-23 06:10:07'),('9141f8a5-1d4c-4eba-a749-f5cb040b1630','Matriz Manaus','Manaus','AM',1,'2026-07-23 06:10:19','2026-07-23 06:10:19');
/*!40000 ALTER TABLE `escritorios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exigencias_catalogo`
--

DROP TABLE IF EXISTS `exigencias_catalogo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
  `aplicabilidade_f` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `fk_catalogo_categoria` (`categoria_id`),
  CONSTRAINT `fk_catalogo_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `exigencias_categorias` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exigencias_catalogo`
--

LOCK TABLES `exigencias_catalogo` WRITE;
/*!40000 ALTER TABLE `exigencias_catalogo` DISABLE KEYS */;
INSERT INTO `exigencias_catalogo` VALUES ('001794c9-7765-48f2-aa3e-13b4ff29aba8','EX-344','b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','A dotação de coletes salva vidas atende a totalidade de pessoas a serem transportadas, inclusive crianças (10% para elas)','NORMAM-202/DPC, Cap. 04, Item 4.13.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('005da3a8-7a7b-4fab-b855-6dbbf28f8fa8','EX-373','a5f25230-91c9-4e14-aa33-e83524d5d943','As embarcações com AB maior que 500 deverão ter, pelo menos, duas bombas de incêndio de acionamento não manual, sendo que uma bomba deverá possuir força motriz distinta da outra e independente do motor principal.','NORMAM-202/DPC, Cap. 03, Seção III.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('012e8fb1-9d0f-4d3c-94a4-8bb0ee588991','EX-329','e70f7906-4e9d-4367-b10a-2ad2a007817a','Indicador de rotação do(s) MCP(s) no passadiço ou comando','NORMAM-202/DPC','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,0,1,0,0),('025542ea-e255-4ace-9dbd-b02ef35feabd','EX-358','b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','Data de fabricação (Embarcações de Sobrevivência/Boias)','NORMAM-202/DPC, Cap. 04, Item 4.12.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('0382e720-a8ce-42ef-8146-d19431108b5a','EX-438','b8ed9a31-9fa3-492f-904e-b8158a06d0da','a) os fios são protegidos por meio de eletrodutos rígidos ou flexíveis','NORMAM-202/DPC','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('03d79106-5ac2-42a2-ba86-af98a21c6022','EX-382','a5f25230-91c9-4e14-aa33-e83524d5d943','O número de seções de mangueira, incluindo uniões e esguichos, é de uma para cada 30 m de comprimento da embarcação e há outra sobressalente (sendo que, em nenhum caso, este número poderá ser inferior a três).','NORMAM-202/DPC, Cap. 04, Seção III.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,0,1,0,0),('0470cbba-bc5c-4e90-841d-6de840326f65','EX-339','b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','Classe (Coletes salva-vidas)','NORMAM-202/DPC, Cap. 04, Item 4.13.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('0496349c-9dd7-4bf1-b628-d6a87e9744ab','EX-463','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Existe a bordo um compartimento, com dimensões apropriadas e com possibilidade de trancamento, para a guarda de bagagens e volumes de passageiros, conforme indicado no projeto','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('066394ff-2a85-4b3b-8338-e04f6948b915','EX-371','a5f25230-91c9-4e14-aa33-e83524d5d943','A embarcação é dotada de, pelo menos, uma bomba de incêndio fixa não manual, com vazão maior ou igual a 15 m³/h (tal bomba poderá ser acionada pelo motor principal)','NORMAM-202/DPC, Cap. 04, Seção III.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('06a2613d-ad79-437a-b3b8-190ae85212da','EX-537','71c05e83-0d67-4137-b2b7-478c4241a057','Escala de calado está escrita a boreste e a bombordo, a vante e a ré e a meia nau, em medidas métricas','NORMAM-202/DPC, Cap. 02, Seção II.','flutuando',NULL,30,1,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,1,1,1,1,1),('076e253a-6e6a-4a81-9877-640da3ad73e1','EX-405','65bf89f0-f44d-4746-89f7-f530c9aa990d','As bombas utilizadas para transferência de óleo para consumo da embarcação deverão ser instaladas sobre bandejas coletoras, que possibilitem, em caso de vazamentos, a coleta do óleo derramado','NORMAM-202/DPC, Cap. 03, Seção III.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('07a3393b-429c-447d-bfd0-353a6683bd1b','EX-387','a5f25230-91c9-4e14-aa33-e83524d5d943','A identificação por cores das tubulações em todas as embarcações deverá ser efetuada em conformidade com o disposto na norma ISO 14726:2008.','NORMAM-202/DPC, Cap. 09, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('07f7f40b-5d11-4d8b-b409-54d6f2d9ec76','EX-407','65bf89f0-f44d-4746-89f7-f530c9aa990d','Verificar as proteções térmicas e acústicas do(s) motor(es) de embarcações de transporte de passageiros','NORMAM-202/DPC, Cap. 03, Seção III.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('0812830b-ec4d-4746-bb3b-d6cf8a6eb74a','EX-428','b8ed9a31-9fa3-492f-904e-b8158a06d0da','Quanto aos quadros elétricos: b) o de emergência está próximo à fonte de energia elétrica de emergência','NORMAM-202/DPC, Cap. 03, Seção IV.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('08457e8a-69b4-4157-b040-15d526d41a67','EX-335','e70f7906-4e9d-4367-b10a-2ad2a007817a','Verificar a presença de relógio de parede ou de painel no comando, devidamente sincronizado e operacional.','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('0a144b76-52d6-4e7d-a1c2-8154c5ccf4fb','EX-520','71c05e83-0d67-4137-b2b7-478c4241a057','Abaixo do convés aberto mais baixo, a via de escape principal é uma escada e a via secundária consiste num conduto ou numa escada','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,0,1,1,0,0),('0a212a39-3f21-4932-ab3b-7d5bd4e8721f','EX-368','a5f25230-91c9-4e14-aa33-e83524d5d943','Os botijões de gás estão posicionados em áreas externas, em local seguro e arejado, protegidos do sol e afastados de fontes que possam causar ignição.','NORMAM-202/DPC, Cap. 04, Item 4.29.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('0bb736ea-8f70-4b80-9ac4-c441139fbe3c','EX-374','a5f25230-91c9-4e14-aa33-e83524d5d943','Em EMPURRADORES e REBOCADORES a(s) bomba(s), as duas tomadas e as duas estações de incêndio completas deverão estar posicionadas nas proximidades da proa da embarcação','NORMAM-202/DPC, Cap. 03, Seção III.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('0c21a30d-7637-49bd-94b9-eaa39968b2bc','EX-508','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','A unidade de chuveiro apresenta soleira com uma altura mínima de 100 mm acima do convés e é impermeabilizadas até esse nível','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('0d11a58c-88e9-40df-b7c1-28e0eb4e62b0','EX-325','e70f7906-4e9d-4367-b10a-2ad2a007817a','Ecobatímetro','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,0,1,0,0),('0dc6cd05-01d7-4035-b683-fb1c6251f2d8','EX-350','b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','A dotação das embarcações de sobrevivência está de acordo com o quadro da NORMAM e estão em boas condições (inclusive suas alças, se aparelho rígido)','NORMAM-202/DPC','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('0ddc6914-749b-40e7-8799-15c272201ebf','EX-338','b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','Modelo (Coletes salva-vidas)','NORMAM-202/DPC, Cap. 04, Item 4.13.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('0e8c9c8f-adb8-444a-985e-dc2cebd737b4','EX-502','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','As distâncias mínimas que deverão ser observadas entre as unidades do sanitário coletivo são as seguintes (Unidade em frente a unidade, lavatório, antepara, etc.)','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('0ed1e638-2afc-4cdf-ad7a-3d1e9b3fb6c4','EX-475','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','As cadeiras deverão atender às seguintes dimensões: c) profundidade mínima de 0,40 m','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('0fcd87ed-ac18-4025-a692-d79d5ba5599b','EX-443','b8ed9a31-9fa3-492f-904e-b8158a06d0da','f) os cabos e fiação utilizados nos circuitos elétricos de fornecimento essencial ou de emergência de força, iluminação, comunicações interiores ou sinalização não passam por áreas em que haja risco de incêndio','NORMAM-202/DPC, Cap. 03, Seção IV.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('0fdc1e57-8063-4666-ab7d-cee70fff1cf4','EX-367','a5f25230-91c9-4e14-aa33-e83524d5d943','Todos os extintores portáteis possuem o selo do INMETRO e estão dentro do prazo de validade, com as manutenções periódicas realizadas','NORMAM-202/DPC, Cap. 04, Seção III.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('144a054a-435d-4c39-8a2a-c0ad22d4f20e','EX-500','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Cada módulo do lavatório coletivo possui sua torneira própria, e há um dreno servindo a, no máximo, 5 módulos','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('15d35d22-8df1-4051-ae12-4f75812736d9','EX-359','b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','Nome da embarcação (Embarcações de Sobrevivência/Boias)','NORMAM-202/DPC, Cap. 04, Item 4.12.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('16dbdb50-0884-4e9f-8ee9-0b202a65fc04','EX-314','aa4a7f0d-004d-4a60-924e-693335fdd69b','Tabelas ou quadros em outros locais de fácil visualização: - tabelas ou quadros de primeiros socorros','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,0,1,0,0),('191031d1-a918-4879-9118-a6bce6f4b56b','EX-362','a5f25230-91c9-4e14-aa33-e83524d5d943','Não são utilizados combustíveis com ponto de fulgor inferior a 60 °C (como álcool ou gasolina)','NORMAM-202/DPC','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('19b9e02f-e153-46af-90a9-deb6b1511808','EX-484','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','As têm, no mínimo, 1,9 m de comprimento e 0,68 m de largura','NORMAM-202/DPC','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('1b8b2e7c-37f2-41d2-90e5-27d936a704da','EX-429','b8ed9a31-9fa3-492f-904e-b8158a06d0da','Quanto aos quadros elétricos: c) os lados, a parte de trás e da frente dos quadros elétricos estão devidamente protegidos, tapetes ou estrados não condutores estão no piso na frente e atrás dos referidos quadros.','NORMAM-202/DPC','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('1bb30d90-ee8e-4efe-946d-d3ee1385eb36','EX-398','65bf89f0-f44d-4746-89f7-f530c9aa990d','Verificar a presença de objetos não necessários ao funcionamento dos equipamentos, estivados de forma irregular sobre ou próximo aos equipamentos','NORMAM-202/DPC','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('1c389c2a-ae2a-479b-9303-05f79a2846f8','EX-381','a5f25230-91c9-4e14-aa33-e83524d5d943','A rede e as tomadas de incêndio são pintadas de vermelho','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('1d3e7e6f-55fe-4e02-aa7b-b4e06329ec90','EX-376','a5f25230-91c9-4e14-aa33-e83524d5d943','Nas DEMAIS embarcações, deverá haver uma estação de incêndio no visual de uma pessoa que esteja junto a uma tomada de incêndio.','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('1de8358a-fa6e-4cef-876d-6784f605e96d','EX-334','e70f7906-4e9d-4367-b10a-2ad2a007817a','Verificar a presença e o pleno funcionamento do sistema regulamentar \'Sistran\' no comando da embarcação.','NORMAM-202/DPC, Cap. 04, Item 4.2','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 02:36:44',1,1,1,1,1,1),('1f83e2dd-32fd-4f92-84c3-524af3ceb621','EX-544','71c05e83-0d67-4137-b2b7-478c4241a057','Entrar no porão com o plano de perfil estrutural e confrontar os espaçamentos das cavernas/estruturas em loco (ex: 35 ou 50 cm), inspecionando furos, descontinuidades e corrosão.','NORMAM-202/DPC, Cap. 03, Seção III.','flutuando',NULL,30,1,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,1,1,1,1,1),('20ceea81-c249-4b94-9448-af7887e79124','EX-467','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Os espaços para redes apresentam ventilação natural permanente para o exterior da embarcação, tendo como meio de fechamento sanefas ou janelas móveis. No caso de janela móvel, a área mínima de ventilação é de 40% do vão da abertura','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('20d82a21-815c-4aa1-bdf5-282950555392','EX-541','71c05e83-0d67-4137-b2b7-478c4241a057','Verificar se os acessos aos locais abaixo relacionados estão livres: Embornais, saídas d\'água das tomadas de incêndio, tubos de sondagem, suspiros e bocas de ventiladores','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,1,1,1,1,1),('2127c977-6a9f-4e11-9787-3aa2b600b21a','EX-501','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Em frente a cada lavatório existe um espaço livre igual ou superior a 0,5 x 0,6 m','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('22a1886c-48c1-4323-8cce-d1a9f509b800','EX-459','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Existe separação física que permita isolar carga e passageiros','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('22f45a4b-749e-4340-93bb-4c18b3a8273b','EX-316','aa4a7f0d-004d-4a60-924e-693335fdd69b','Relatório de medição de espessura (cinco pontos por chapa), assinado por profissional qualificado e certificado, com reconhecimento no Sistema Nacional de Qualificação e Certificação de Pessoal em Ensaios Não Destrutivos (SNQC/END), acompanhado de documento que comprove a validade da citada habilitação na data de execução do serviço','NORMAM-202/DPC, Cap. 08, Item 8.5','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 02:36:44',1,1,1,1,1,1),('23a80531-b5d7-4dec-bfc3-a56db5c37e23','EX-542','71c05e83-0d67-4137-b2b7-478c4241a057','Verificar se os acessos aos locais abaixo relacionados estão livres: Elementos de amarração e fundeio e o acesso às máquinas','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,1,1,1,1,1),('2704ff5c-b1e3-4799-8637-fdedf7f3114b','EX-393','65bf89f0-f44d-4746-89f7-f530c9aa990d','Correias, ferramentas e sobressalentes deverão ser acondicionados em local apropriado (como cabides e armários), que evite seu deslocamento','NORMAM-202/DPC','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('27e53b15-99f1-4cd2-a400-ab471fb91c23','EX-486','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','A distância mínima entre o topo de um colchão e a parte inferior do estrado da cama imediatamente superior ou a parte inferior dos reforços do convés superior (teto do camarote) é de 0,6 m','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('2a3a0379-b1ba-40fe-b676-809f122084a1','EX-413','65bf89f0-f44d-4746-89f7-f530c9aa990d','Verificar o indicador do sentido de impulsão do(s) propulsor(es) lateral(ais) no passadiço','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('2b8953dd-9bc1-45c6-92a3-ace223c00b5b','EX-446','b8ed9a31-9fa3-492f-904e-b8158a06d0da','i) as partes condutoras de tomadas e plugs estão protegidas de modo a impedir de serem tocadas, mesmo durante ligamento e desligamento','NORMAM-202/DPC','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('2bd5be9b-36f4-40bf-81ad-20cb8ca52aee','EX-527','71c05e83-0d67-4137-b2b7-478c4241a057','As cores das luzes de navegação estão de acordo com as normas específicas sobre o assunto','RIPEAM 72 / NORMAM-202/DPC, Cap. 04.','flutuando',NULL,30,1,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,1,1,1,1,1),('2c585b69-496a-420b-8fa7-14e372dda5dc','EX-492','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','As portas de acesso de banheiros não abrem diretamente para cozinhas ou refeitórios','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('31bb4064-def1-4e32-8ef7-e207f15562dd','EX-384','a5f25230-91c9-4e14-aa33-e83524d5d943','Há completa permutabilidade entre as uniões, mangueiras e esguichos','NORMAM-202/DPC, Cap. 04, Seção III.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('320476cf-8452-4bbc-908d-9f363b3b2eac','EX-401','65bf89f0-f44d-4746-89f7-f530c9aa990d','Redes de descarga devem ser flangeadas onde ultrapassem anteparas e ou costado (de modo que garanta a estanqueidade)','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('33356298-d44a-451e-b38c-e360b2a5bed5','EX-437','b8ed9a31-9fa3-492f-904e-b8158a06d0da','O quadro das luzes de navegação é alimentado por uma linha independente derivada do quadro principal e de emergência','RIPEAM 72 / NORMAM-202/DPC, Cap. 04.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('33e7f3eb-6d6d-4bdf-8bdb-80a063c683ce','EX-452','b8ed9a31-9fa3-492f-904e-b8158a06d0da','o) nos circuitos polifásicos, se a seção dos condutores fase for igual ou inferior a 16 mm² e nos circuitos monofásicos, seja qual for a seção do condutor fase, o condutor neutro tem a mesma seção que os condutores fase','NORMAM-202/DPC','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('33fbb2e3-ae28-4932-820c-40e2f45974e5','EX-529','71c05e83-0d67-4137-b2b7-478c4241a057','As luzes de navegação são homologadas pela Marinha','RIPEAM 72 / NORMAM-202/DPC, Cap. 04.','flutuando',NULL,30,1,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,1,1,1,1,1),('342986f3-dbc0-4f3e-aedc-cb8f14f10d8a','EX-431','b8ed9a31-9fa3-492f-904e-b8158a06d0da','Quanto aos quadros elétricos: e) os quadros elétricos são bem fixados em locais abrigados que não contêm materiais inflamáveis','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('3443b027-7b7e-4275-bdf3-a916184578f9','EX-515','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Verificar a conformidade e a data de validade de cerca de 5 anos da mangueira de gás regulamentada pela ABNT e da válvula reguladora de pressão na cozinha.','NORMAM-202/DPC, Cap. 04, Item 4.29','flutuando',NULL,30,1,'2026-07-03 05:38:14','2026-07-04 02:36:44',1,1,1,1,1,1),('36b4174a-fda8-4a30-bb87-7917235aaf0f','EX-494','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Os acessórios são de material resistente, não apresentam pontas ou arestas cortantes e estão instalados de modo a não interferir no uso do sanitário','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('37f1473c-43ee-4e4a-88fe-8848ddfc933e','EX-534','71c05e83-0d67-4137-b2b7-478c4241a057','Não há espaço abaixo do convés com comprimento superior a 40% do Lregra, medido a partir da parte superior do espelho ou da roda de proa, somente embarcações de passageiros e de madeira','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,0,1,1,0,0),('39789262-7c98-42cc-98d1-708f7cb4a09e','EX-355','b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','Modelo (Embarcações de Sobrevivência/Boias)','NORMAM-202/DPC, Cap. 04, Item 4.12.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('3a263732-7431-4277-812b-8204b15e1f5d','EX-550','71c05e83-0d67-4137-b2b7-478c4241a057','Visualmente, externa e internamente, o estado das descargas, caixas de mar e toda e qualquer abertura no casco da embarcação abaixo de seu convés principal','NORMAM-202/DPC','seco',NULL,30,1,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,1,1,1,1,1),('3e2d7077-e88b-4268-8d2f-9844471927c0','EX-332','e70f7906-4e9d-4367-b10a-2ad2a007817a','Radar','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,0,1,0,0),('3f973cac-9537-4264-97a5-829b557d3fe1','EX-496','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','A unidade é dotada de sistema de escoamento de água tanto no boxe do chuveiro quanto no restante da área e a água do chuveiro não transborda para a parte externa do boxe','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('3fe4aeef-98fe-4a5c-9544-b36d9cd831b6','EX-504','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Nos sanitários coletivos as unidades sanitárias estão localizadas em compartimentos separados entre si por divisórias fixas com altura mínima de 1,8 m a partir do piso acabado, providos de portas de acesso','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('3feea2e8-f5d7-4bad-88af-bdb77f4659e7','EX-444','b8ed9a31-9fa3-492f-904e-b8158a06d0da','g) os cabos que conectam as bombas de incêndio ao quadro elétrico de emergência são do tipo resistente ao fogo, quando passam próximos de áreas em que haja elevado risco de incêndio','NORMAM-202/DPC, Cap. 03, Seção III.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('40284473-c2f6-481c-8a50-8c4d3c5c8a5f','EX-539','71c05e83-0d67-4137-b2b7-478c4241a057','Verificar se os acessos aos locais abaixo relacionados estão livres: Portas de acesso para tripulação e passageiros','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,1,1,1,1,1),('415b0057-acb3-4884-a57f-e8c3473b0e6f','EX-372','a5f25230-91c9-4e14-aa33-e83524d5d943','O sistema de bomba(s) consegue manter, pelo menos, duas tomadas de incêndio distintas com jatos d\'água nunca inferior a 15 m de alcance','NORMAM-202/DPC, Cap. 04, Item 4.14','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 02:36:44',1,1,1,1,1,1),('4174697f-5b23-4140-ac3e-c24ac861b016','EX-414','65bf89f0-f44d-4746-89f7-f530c9aa990d','Verificar a indicação de funcionamento da máquina motriz do(s) “thruster(s)” no passadiço','NORMAM-202/DPC','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('43a7583f-f880-4cf1-bb2c-1f9df67a29d5','EX-479','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Os camarotes para 2 passageiros ou tripulantes possuem dimensões mínimas de 1,9 m x 1,5 m, contendo um beliche duplo','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('446e0844-e616-4c5e-a073-480d64f291d7','EX-389','65bf89f0-f44d-4746-89f7-f530c9aa990d','O arranjo físico da embarcação está de acordo com o Arranjo Geral.','NORMAM-202/DPC','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('450fd87a-eb93-4031-a7a1-237cbfd57c63','EX-483','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Ocorre o transporte de no máximo 4 passageiros ou 9 tripulantes por camarote','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('45180ac3-9c57-4200-a523-3cc0867b3a6b','EX-356','b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','Classe (Embarcações de Sobrevivência/Boias)','NORMAM-202/DPC, Cap. 04, Item 4.12.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('45e58c28-008c-4b2f-85a0-e3c26155d21a','EX-449','b8ed9a31-9fa3-492f-904e-b8158a06d0da','l) todos os circuitos de luz e força, terminando num espaço que contenha tanques de combustível, ou material inflamável, são dotados de chave colocada por fora do referido espaço, para desconectar tais circuitos','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('45f242ee-96c4-4558-8a4f-86bdac810e1a','EX-419','b8ed9a31-9fa3-492f-904e-b8158a06d0da','A fonte de energia elétrica principal foi dimensionada de forma que a potência aparente fornecida ao sistema seja suficiente para evitar quedas de tensões que resultem em desligamento ou oscilação de consumidores em operação devido a partida de motores elétricos de alta corrente','NORMAM-202/DPC, Cap. 03, Seção III.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('47b78ace-bd63-451e-ae51-001de365baaf','EX-333','e70f7906-4e9d-4367-b10a-2ad2a007817a','Verificar se há compasso, régua paralela, borracha, apontador e lápis disponíveis junto das cartas náuticas para uso operacional no traçado de rotas.','NORMAM-202/DPC','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('48501aad-989d-46d0-b36b-56274659a1de','EX-498','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','O lavatório é equipado com torneira de água corrente e dreno','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('4a802f33-84d3-4b5f-b4a5-f8b3accb328b','EX-510','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','A rampa apresenta largura mínima de 0,5 m e contém balaustrada em pelo menos um dos lados com altura de 1 m ou mais','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,0,0,0),('4add624f-894e-442c-bc48-1bf430208d14','EX-423','b8ed9a31-9fa3-492f-904e-b8158a06d0da','A fonte de energia de emergência está localizada, se possível, acima do convés contínuo superior e é de pronto acesso partindo-se do convés aberto.','NORMAM-202/DPC, Cap. 03, Seção IV.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('4bb658ec-309f-4338-b4e6-3a965db20dc7','EX-511','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','A rampa tem resistência suficiente para possibilitar a passagem das pessoas sem apresentar uma flexão significativa','NORMAM-202/DPC, Cap. 03, Seção V.','flutuando',NULL,30,1,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,0,1,0,0,0),('4c8e77b2-3baa-4674-94e8-8d1fc6708eb1','EX-347','b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','A dotação de boias salva vidas está de acordo com o quadro da NORMAM e estão em boas condições (inclusive as retinidas)','NORMAM-202/DPC, Cap. 04, Item 4.12.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('4dce80a9-ccad-4b7e-b61c-644a54d2978a','EX-552','71c05e83-0d67-4137-b2b7-478c4241a057','Para as embarcações de casco de madeira, a partir da primeira vistoria, verificar o calafeto','NORMAM-202/DPC','seco',NULL,30,1,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,1,1,1,1,1),('4e94ab4a-31be-4329-b6d5-bf08463c68c0','EX-337','b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','Fabricante (Coletes salva-vidas)','NORMAM-202/DPC, Cap. 04, Item 4.13.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('4f0cca2c-efa9-40d3-a863-0488fea72d05','EX-514','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Verificar se as tomadas elétricas instaladas nos camarotes estão em perfeito estado físico, com espelhos protetores e energizadas corretamente.','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,1,1,1,1,1),('51377ad9-666c-49d1-80f0-6e43cd20c12a','EX-357','b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','Número de série (se tiver) (Embarcações de Sobrevivência/Boias)','NORMAM-202/DPC, Cap. 04, Item 4.12.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('525223b6-395d-45f9-ae14-7a1c528215f6','EX-301','aa4a7f0d-004d-4a60-924e-693335fdd69b','Certificado de Segurança de Navegação','NORMAM-202/DPC, Cap. 08, Item 8.2.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('532445e2-6334-4633-ad34-ccc907b62a47','EX-380','a5f25230-91c9-4e14-aa33-e83524d5d943','Há instalada uma válvula ou dispositivo similar em cada tomada de incêndio, em posições tais que permitem o fechamento das tomadas com as bombas de incêndio em funcionamento','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('53fd2924-3c59-434e-9b5c-3ffe3c4c1a7b','EX-451','b8ed9a31-9fa3-492f-904e-b8158a06d0da','n) os fios e cabos elétricos são especificados levando em consideração a capacidade de condução de corrente estabelecida pelo fabricante e a queda de tensão admissível','NORMAM-202/DPC, Cap. 03, Seção IV.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('544e46ae-c5da-46c2-837e-3c112db98f3e','EX-343','b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','Nome da embarcação (Coletes salva-vidas)','NORMAM-202/DPC, Cap. 04, Item 4.13.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('548d1060-cb9d-4fac-b389-8c03c0ccea29','EX-322','e70f7906-4e9d-4367-b10a-2ad2a007817a','Alarme visual e sonoro de baixa pressão do óleo lubrificante do MCP e MCA com potência igual ou superior a 800 HP (597 kW)','NORMAM-202/DPC, Cap. 09, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,0,1,0,0),('54daf75f-7dd4-4064-84b1-dcc73e0dc352','EX-507','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','A unidade de chuveiro não está instalada em um sanitário coletivo, mas possui área destinada à troca de roupa','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('55d90c7d-3aba-4255-970f-43ce4bcfdaff','EX-349','b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','As retinidas das boias salva vidas possuem 20 m de comprimento e são feitas de material sintético e capazes de flutuar.','NORMAM-202/DPC, Cap. 04, Item 4.12.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('58133b9a-53e9-454e-bdb7-e5e2b7a1d90c','EX-365','a5f25230-91c9-4e14-aa33-e83524d5d943','A quantidade, capacidade, localização e tipo dos extintores de incêndio estão de acordo com a tabela da NORMAM. Quanto à localização deles, seguem o determinado no Plano de Segurança (se existente)','NORMAM-202/DPC, Cap. 04, Item 4.2), 4.2.1, m, I.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 02:36:44',1,1,1,1,1,1),('585d1cfe-309c-40aa-be0e-4804eda5310a','EX-473','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','As cadeiras deverão atender às seguintes dimensões: a) largura mínima de 0,45 m de para os bancos simples','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('58e5b2aa-0482-4c9b-82a3-01c000cb1bb5','EX-489','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','A área mínima requerida para o transporte turísticos sem pernoite a bordo, considera a concentração de 1,5 passageiros/m². No cálculo dessas áreas estão computadas as áreas de estivagem de bagagens ou transporte de carga, nem as escadas','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('5a63ec6b-964c-4a41-a1d7-53fa6980ba2e','EX-545','71c05e83-0d67-4137-b2b7-478c4241a057','O comprimento total, boca moldada e pontal moldado do casco da embarcação estão de acordo com aqueles anotados no Memorial Descritivo','NORMAM-202/DPC','seco',NULL,30,1,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,1,1,1,1,1),('5b125c67-ea0c-45a2-905e-437027445eb7','EX-439','b8ed9a31-9fa3-492f-904e-b8158a06d0da','b) os cabos são individualmente fixados a leitos ou suportes','NORMAM-202/DPC, Cap. 03, Seção IV.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('5b502640-d457-410d-9580-8ed3d5e95d81','EX-454','f299c8c7-4402-4efa-89c6-d5add1fa60d5','Toda embarcação que seja dotada de um equipamento fixo de radiocomunicação, deverá possuir a licença rádio, emitida pela Agência Nacional de Telecomunicações (ANATEL).','NORMAM-202/DPC, Cap. 04, Item 4.8), 4.8.1.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:04:13',1,0,0,1,0,0),('5d288f7e-25e6-4e36-b8aa-093601403d54','EX-390','65bf89f0-f44d-4746-89f7-f530c9aa990d','Verificar a limpeza dos espaços de máquinas e equipamentos. Os espaços e equipamentos de máquinas deverão ser mantidos limpos e sem vazamentos de óleos e com os estrados em bom estado de conservação','NORMAM-202/DPC, Cap. 09, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('5df039e6-b400-4fd0-abd2-83959587485a','EX-395','65bf89f0-f44d-4746-89f7-f530c9aa990d','A iluminação deverá possibilitar que nenhuma área superior a 1 m² fique sem iluminação','NORMAM-202/DPC, Cap. 03, Seção IV.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('5f8a7cb6-2019-4100-a02f-96c076e65b5d','EX-366','a5f25230-91c9-4e14-aa33-e83524d5d943','Os extintores com peso bruto superior a 25 kg (quando carregados) possuem mangueiras ou esguichos adequados ou outros meios praticáveis para que atendam o espaço a que se destinam.','NORMAM-202/DPC, Cap. 04, Seção III.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('60f87d12-e57b-4063-ad67-b625f26f3093','EX-361','b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','Dotação de artefatos pirotécnicos conforme NORMAM e catálogo de material homologado da DPC','NORMAM-202/DPC, Cap. 04, Seção II.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('616c56c7-ec03-4fc1-8fe8-c5a5c9321130','EX-369','a5f25230-91c9-4e14-aa33-e83524d5d943','As canalizações utilizadas para a distribuição de gás estão em boas condições e têm proteção adequada contra o calor e, se flexíveis, atendem às normas da Associação Brasileira de Normas Técnicas (ABNT)','NORMAM-202/DPC, Cap. 04, Item 4.29.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('61e1dc4b-494e-46d8-b8eb-f0f2f6f8b8b6','EX-488','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Área mínima requerida em travessia com até 1 hora de duração considera a concentração de 4 passageiros por m²','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('62c73930-c97e-40c7-8241-0ca46b7ce652','EX-551','71c05e83-0d67-4137-b2b7-478c4241a057','Os perfis (transversais, longitudinais e “diagonais”) e anteparas estão devidamente soldados nos respectivos locais onde devem ser ligados','NORMAM-202/DPC, Cap. 04, Seção I.','seco',NULL,30,1,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,1,1,1,1,1),('62ed00d9-c647-40fc-82dc-cdd0feb36475','EX-352','b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','As embarcações de sobrevivência infláveis possuem o certificado de revisão dentro do prazo de validade e foram revisadas em estação de manutenção autorizada pela DPC','NORMAM-202/DPC','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('63ec6d70-d445-4051-9851-f414c26fb7b7','EX-525','71c05e83-0d67-4137-b2b7-478c4241a057','A dotação das luzes atende as regras sobre o assunto para este tipo de embarcação','NORMAM-202/DPC','flutuando',NULL,30,1,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,1,1,1,1,1),('64264fe0-373e-4c75-82be-3665162220eb','EX-317','e70f7906-4e9d-4367-b10a-2ad2a007817a','Lanterna portátil com bateria recarregável ou pilhas sobressalentes','NORMAM-202/DPC, Cap. 03, Seção IV.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('6a368da8-410c-42df-bbc2-f58bfdb9806b','EX-499','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','O lavatório do tipo coletivo considera 0,6 m por pessoa','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('6d6d6309-d8f2-4d2a-86a2-01e902c50df9','EX-400','65bf89f0-f44d-4746-89f7-f530c9aa990d','Redes de descarga e aspiração da praça de máquinas conectadas ao fundo ou ao costado deverão ser metálicas','NORMAM-202/DPC, Cap. 03, Seção III.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('6e55abe3-ccfb-41d0-8365-c6c5f838e658','EX-540','71c05e83-0d67-4137-b2b7-478c4241a057','Verificar se os acessos aos locais abaixo relacionados estão livres: Equipamentos de salvatagem e combate a incêndio','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,1,1,1,1,1),('6f4dc9b2-6ff0-4ca5-9b9f-649913e95d75','EX-547','71c05e83-0d67-4137-b2b7-478c4241a057','Os posicionamentos dos tanques de consumíveis estão de acordo com aqueles anotados no Plano de Capacidades. Caso seja necessário, deverá ser requerida a abertura do fundo duplo','NORMAM-202/DPC','seco',NULL,30,1,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,1,1,1,1,1),('7208560e-f098-4ed4-a6db-04e305b59b2b','EX-436','b8ed9a31-9fa3-492f-904e-b8158a06d0da','Os circuitos das luzes de navegação são individualmente protegidos por fusíveis ou disjuntores instalados no painel de controle ou quadro de luzes de navegação','RIPEAM 72 / NORMAM-202/DPC, Cap. 04.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('73540b8b-e8bd-4d3e-b08d-77ed59461bce','EX-503','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','A unidade sanitária é composta de um vaso sanitário de louça vitrificada, dotado de fluxo de água (descarga) para sua limpeza e acessórios','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('73f848be-eb6b-4e0a-b0b1-67a6ee583f3f','EX-348','b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','As boias salva vidas e sua retinida não estão presas ou amarradas à embarcação, estando apenas apoiadas em seus suportes, prontas para serem lançadas','NORMAM-202/DPC, Cap. 04, Item 4.12.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('76456380-e872-472e-80de-465dc9969111','EX-312','aa4a7f0d-004d-4a60-924e-693335fdd69b','Tabelas ou quadros no comando: - balizamento','NORMAM-202/DPC','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,0,1,0,0),('7661f5f9-cff5-4173-9b00-6e4337d2e45f','EX-330','e70f7906-4e9d-4367-b10a-2ad2a007817a','Quadro elétrico de luzes/sistemas de comunicação','NORMAM-202/DPC, Cap. 03, Seção IV.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,0,1,0,0),('76ed1958-0074-4027-be8a-45a0f35ebaa8','EX-518','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','O arranjo físico da embarcação está de acordo com o Arranjo Geral. Devem ser verificados os compartimentos em relação ao seu posicionamento e destinação','NORMAM-202/DPC','flutuando',NULL,30,1,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,1,1,1,1,1),('7a31837c-64ee-47e2-9f6b-d4b5cd5108b1','EX-403','65bf89f0-f44d-4746-89f7-f530c9aa990d','Os indicadores de níveis dos tanques de óleo deverão ser dotados de válvulas (preferencialmente do tipo esfera), que deverão ser instaladas na parte inferior do respectivo indicador','NORMAM-202/DPC, Cap. 09, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('7a9f7a2a-d2df-43ae-bb1b-14c77c92ad36','EX-519','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Todos os níveis de acomodações, de compartimentos de serviço ou da praça de máquinas possui, pelo menos, duas vias de escape amplamente separadas, provenientes de cada compartimento restrito ou grupos de compartimentos','NORMAM-202/DPC, Cap. 03, Seção III.','flutuando',NULL,30,1,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,0,1,1,0,0),('7c148a99-d39d-4dce-9428-a65d8c9e9a39','EX-474','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','As cadeiras deverão atender às seguintes dimensões: b) largura mínima de 0,86 m de para os bancos duplos ou combinações desses','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('7dca1f10-d3ca-4efb-aaad-05c38b4e02de','EX-548','71c05e83-0d67-4137-b2b7-478c4241a057','Os equipamentos de carga, propulsão, energia e governo da embarcação estão de acordo com o Memorial Descritivo.','NORMAM-202/DPC, Cap. 03, Seção IV.','seco',NULL,30,1,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,1,1,1,1,1),('7fe5827d-bbc9-4041-b881-c55b5edc1563','EX-394','65bf89f0-f44d-4746-89f7-f530c9aa990d','As superfícies quentes deverão ser providas de proteções térmicas, a fim de minimizar o risco de queimaduras nos tripulantes','NORMAM-202/DPC','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('7fed81ee-7071-42cc-8f8b-eb18d5346505','EX-512','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','A rampa é dotada de dispositivo antiderrapante no piso (o qual poderá consistir de travessões instalados no sentido transversal com espaçamento não superior a 0,50 m)','NORMAM-202/DPC, Cap. 03, Seção V.','flutuando',NULL,30,1,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,0,1,0,0,0),('805c0314-b1b1-4061-8c40-d25398d2e53f','EX-472','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','O espaço de cadeiras possui pelo menos 2 portas de acesso opostas','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('83c0e7f6-6a1a-4383-ba22-9544c2018930','EX-555','9e81f468-422b-40e4-8bf8-40b60a027a36','Estão em bom estado o(s) leme(s) e o(s) hélice(s)','NORMAM-202/DPC, Cap. 03, Seção III.','seco',NULL,30,1,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,1,1,1,1,1),('8640b086-97b1-4cf5-b853-86b0b9504e30','EX-490','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Número mínimo de aparelhos sanitários conforme tabelas regulamentares','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('86ccce9f-605d-4896-871b-d7775e23014f','EX-491','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Todos os banheiros são dotados de ventilação natural, através de janela ou cachimbo, ou ventilação forçada','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('88af8f67-9df3-429a-8d9d-bb04d74345ec','EX-538','71c05e83-0d67-4137-b2b7-478c4241a057','As embarcações de propriedade de órgãos públicos serão caracterizadas por meio de letras e distintivos adotados por seus respectivos órgãos.','NORMAM-202/DPC','flutuando',NULL,30,1,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,1,1,1,1,1),('8b5b4c03-0824-4f51-ab4b-2b1c27640900','EX-303','aa4a7f0d-004d-4a60-924e-693335fdd69b','O armador deverá apresentar a Provisão de Registro da Propriedade Marítima (PRPM) ou caso a embarcação não possua apresentar Documento Provisório de Propriedade (DPP).','NORMAM-202/DPC, Cap. 02, Item 2.1.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:04:13',1,1,1,1,1,1),('8d78d063-e888-4a5b-994b-5c61e704fc44','EX-364','a5f25230-91c9-4e14-aa33-e83524d5d943','Na saída de cada tanque de combustível há uma válvula de fechamento capaz de interromper o fluxo da rede','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('8ed00d22-c8ee-40f5-be7c-64f9e9acc83d','EX-456','f299c8c7-4402-4efa-89c6-d5add1fa60d5','A embarcação possui a licença de estação do navio em vigor, emitida pela ANATEL','ANATEL / NORMAM','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-03 05:41:41',1,0,0,1,0,0),('902653ef-7f5d-497e-a1f4-d78f31212d7c','EX-441','b8ed9a31-9fa3-492f-904e-b8158a06d0da','d) os cabos e fiação estão instalados e fixados de modo a evitar desgastes por atrito ou outra avaria','NORMAM-202/DPC, Cap. 03, Seção IV.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('9339e3f3-a72d-48ab-8f33-eb449e5f7395','EX-385','a5f25230-91c9-4e14-aa33-e83524d5d943','Todos os esguichos das mangueiras que servem às tomadas localizadas no compartimento de máquinas ou localizadas junto a tanques de carga de líquidos inflamáveis são de duplo emprego, isto é, borrifo e jato sólido, incluindo um dispositivo de fechamento','NORMAM-202/DPC, Cap. 04, Seção III.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('934b7190-7444-4f16-96bd-a367c6953b9c','EX-321','e70f7906-4e9d-4367-b10a-2ad2a007817a','Limpador de para-brisa ou vigia rotativa','NORMAM-202/DPC, Cap. 03, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,0,1,0,0),('94a99554-75f0-4da2-9e4f-f2c089ee8141','EX-327','e70f7906-4e9d-4367-b10a-2ad2a007817a','Transceptor para o Sistema de Identificação Automática homologado pela ANATEL (Automatic Identification System - AIS)','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,0,1,0,0),('9537c200-5b45-4d8b-b670-505c5c936f79','EX-427','b8ed9a31-9fa3-492f-904e-b8158a06d0da','Quanto aos quadros elétricos: a) todos eles são dispostos de maneira que ofereçam fácil acesso durante a operação e ou manutenção dos equipamentos','NORMAM-202/DPC','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('95822d65-14fa-4d61-a80c-93b779751ed4','EX-528','71c05e83-0d67-4137-b2b7-478c4241a057','As luzes atendem aos setores (ângulos) corretos','NORMAM-202/DPC','flutuando',NULL,30,1,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,1,1,1,1,1),('95f9e766-875a-48f0-93bb-149d9e29f784','EX-460','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Todos os espaços destinados ao transporte e ou permanência de passageiros apresentam pés-direitos (vão entre o piso e o teto) de no mínimo 1,90 m','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('990defff-5140-4561-b20a-e9a67b74e9a0','EX-506','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','A unidade de chuveiro é composta por um chuveiro com jato d ́água com altura de queda mínima de 1,9 m e seus acessórios, localizada em compartimento separado das demais áreas por um meio que evite respingos (box)','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('991a0bbc-deb5-4b81-8305-c4d102e95e50','EX-410','65bf89f0-f44d-4746-89f7-f530c9aa990d','Motores com potência igual ou superior a 800 HP deverão ser dotados de um painel local ou remoto, com as seguintes indicações: RPM, temperatura da água de arrefecimento, pressão e temperatura do óleo lubrificante','NORMAM-202/DPC, Cap. 03, Seção III.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('9979e589-44dd-4790-9574-4adb561aaf7d','EX-461','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','A circulação nas áreas de embarque e desembarque, nos corredores e escadas é livre e independente das demais áreas da embarcação. Nas embarcações com AB maior que 50, os corredores maiores que 7 m, possui, pelo menos, 2 vias de acesso/escape','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('99be0275-f74e-49e6-aac2-fce3b372fecf','EX-517','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Verificar a existência físico-documental e o correto preenchimento do livro de registro de lixo a bordo.','NORMAM-202/DPC, Cap. 09, Item 9.2','flutuando',NULL,30,1,'2026-07-03 05:38:14','2026-07-04 02:36:44',1,1,1,1,1,1),('9ac15939-64b7-4878-8b0e-76c61bf1b55e','EX-553','71c05e83-0d67-4137-b2b7-478c4241a057','Verificar a marcação física da régua de calado com algarismos soldados em relevo na quilha de 20 em 20 cm, pintados com cor de destaque.','NORMAM-202/DPC, Cap. 03, Seção I.','seco',NULL,30,1,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,1,1,1,1,1),('9be9b57c-5702-4e46-9703-4414b0c8ce56','EX-319','e70f7906-4e9d-4367-b10a-2ad2a007817a','Binóculo 7x50','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,0,1,0,0),('9c039242-cd6f-4dae-b2ea-628efe60d3cd','EX-485','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','O topo do colchão inferior está a pelo menos 0,3 m do convés (piso do camarote)','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('9d9028b2-a785-4a1a-bf9a-db04ae0e3e95','EX-331','e70f7906-4e9d-4367-b10a-2ad2a007817a','Sistema de comunicação interna, interligando, pelo menos, passadiço, praça de máquinas e compartimento da máquina do leme, propiciando troca de informações nos dois sentidos','NORMAM-202/DPC, Cap. 03, Seção III.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,0,1,0,0),('9dc4b5a1-2d0e-4821-8be6-c4fe3a8e8ee0','EX-470','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','A largura mínima do vão de acesso ao compartimento é maior ou igual à largura do corredor de acesso à abertura','NORMAM-202/DPC, Cap. 03, Seção V.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('9e411c90-8ac2-4499-8ca7-2bcda5d07503','EX-420','b8ed9a31-9fa3-492f-904e-b8158a06d0da','Para embarcações com AB maior ou igual a 300 a fonte de emergência de energia elétrica é um gerador acionado por um motor com suprimento independente de combustível','NORMAM-202/DPC, Cap. 03, Seção III.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,0,1,0,0),('9e7cda40-92d3-4ba1-b90d-bca3d3071994','EX-328','e70f7906-4e9d-4367-b10a-2ad2a007817a','Indicador do ângulo do leme no passadiço ou comando','NORMAM-202/DPC, Cap. 03, Seção III.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,0,1,0,0),('a0662bd3-30ea-4206-82e2-51b4a8fa3f8a','EX-535','71c05e83-0d67-4137-b2b7-478c4241a057','A estrutura (flutuante fixa) está sinalizada por uma luz fixa amarela, com alcance mínimo de duas milhas náuticas, estabelecida no seu tope ou em local de melhor visibilidade para o navegante.','NORMAM-202/DPC, Cap. 03, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:14','2026-07-04 04:12:38',0,0,1,0,0,0),('a0acbebe-660c-4f48-9da8-64bd45b91455','EX-345','b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','Os coletes salva vidas estão em bom estado de conservação e com apito','RIPEAM 72 / NORMAM-202/DPC, Cap. 04.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('a0e3d499-45d6-4908-bed1-c1da5138641f','EX-416','65bf89f0-f44d-4746-89f7-f530c9aa990d','Verificar se as luminárias na praça de máquinas possuem proteção antichoque física em invólucros do tipo \'tartaruga\' e se acendem normalmente.','NORMAM-202/DPC, Cap. 03, Seção III.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('a194202b-f4c6-4cbe-bf63-a5216292653b','EX-450','b8ed9a31-9fa3-492f-904e-b8158a06d0da','m) os circuitos polifásicos são distribuídos de modo a assegurar o melhor equilíbrio de cargas entre fases','NORMAM-202/DPC','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('a19d11c1-6666-4459-80ae-5e82c990f243','EX-318','e70f7906-4e9d-4367-b10a-2ad2a007817a','Apito','RIPEAM 72 / NORMAM-202/DPC, Cap. 04.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('a1d44288-9e8d-4cc9-abef-7bf1f296e426','EX-422','b8ed9a31-9fa3-492f-904e-b8158a06d0da','O grupo gerador de emergência ou a bateria de emergência foi instalado, preferencialmente, fora do compartimento das máquinas e dos geradores principais. A antepara de separação entre os compartimentos é, preferencialmente, estanque e resistente ao fogo','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('a1f22623-e022-464e-bd02-d1e056aab5db','EX-482','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Os camarotes com camas simples possuem área mínima de 2,6 m² por pessoa','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('a371bf33-76aa-11f1-9eb5-0a1b2af87b16','CBL-001','71c05e83-0d67-4137-b2b7-478c4241a057','Há passagem permanentemente desobstruída de proa à popa, que não é efetivada por cima de tampas de escotilhas. Tal passagem possui largura mínima em conformidade com o estabelecido no Anexo 3-M','NORMAM-202/DPC, Cap. 03, Seção I.','borda_livre',NULL,30,1,'2026-07-03 06:44:28','2026-07-04 04:12:38',1,1,1,1,1,1),('a371da38-76aa-11f1-9eb5-0a1b2af87b16','CBL-002','71c05e83-0d67-4137-b2b7-478c4241a057','Em todas as partes expostas dos conveses principais e de superestruturas há eficientes balaustradas ou bordas falsas (que poderão ser removíveis), com altura não inferior a 1 metro (para embarcações com AB maior que 20)','NORMAM-202/DPC, Cap. 04, Seção I.','borda_livre',NULL,30,1,'2026-07-03 06:44:28','2026-07-04 04:12:38',1,1,1,1,1,1),('a371f205-76aa-11f1-9eb5-0a1b2af87b16','CBL-003','71c05e83-0d67-4137-b2b7-478c4241a057','A abertura inferior da balaustrada apresenta altura menor ou igual a 230 mm e os demais vãos não poderão apresentar espaçamento superior a 380 mm. No caso de embarcações com bordas arredondadas, os suportes das balaustradas deverão ser colocados na parte plana do convés','NORMAM-202/DPC, Cap. 04, Seção I.','borda_livre',NULL,30,1,'2026-07-03 06:44:28','2026-07-04 04:12:38',1,1,1,1,1,1),('a3721459-76aa-11f1-9eb5-0a1b2af87b16','CBL-004','71c05e83-0d67-4137-b2b7-478c4241a057','Para embarcações que possuam borda falsa, estas deverão possuir saídas d’água respeitando o determinado no item 0609','NORMAM-202/DPC','borda_livre',NULL,30,1,'2026-07-03 06:44:28','2026-07-04 04:12:38',1,1,1,1,1,1),('a3722d96-76aa-11f1-9eb5-0a1b2af87b16','CBL-005','71c05e83-0d67-4137-b2b7-478c4241a057','Nas embarcações dos tipos A, B ou D, as vigias e olhos de boi, se existentes nos costados abaixo do convés de borda livre, deverão apresentar as seguintes características: a) ser estanque à água (ou apresentar meios que possibilitem o seu fechamento estanque à água) b) ser de construção sólida c) ser provida de vidros temperados de espessura compatível com seu diâmetro d) não podem ser do tipo “removível” e) caso rebatíveis, deverão permanecer fechadas quando em viagem, devendo haver uma placa, permanentemente fixada junto à vigia, alertando que a mesma deverá permanecer fechada quando em viagem','NORMAM-202/DPC, Cap. 05, Item 5.1.','borda_livre',NULL,30,1,'2026-07-03 06:44:28','2026-07-04 04:12:38',1,1,0,1,0,0),('a37244fe-76aa-11f1-9eb5-0a1b2af87b16','CBL-006','71c05e83-0d67-4137-b2b7-478c4241a057','As aberturas no costado de embarcações dos tipos A, B ou D deverão possuir tampas estanques à água ou vigias e olhos de boi e deverão estar posicionadas de forma que sua aresta inferior esteja a, pelo menos, 300 mm acima da linha d’água carregada, em qualquer condição esperada de trim. Para as embarcações dos tipos C ou E essa distância não deverá ser inferior a 500 mm','NORMAM-202/DPC, Cap. 03, Seção I.','borda_livre',NULL,30,1,'2026-07-03 06:44:28','2026-07-04 04:12:38',1,1,1,1,1,1),('a3725c4c-76aa-11f1-9eb5-0a1b2af87b16','CBL-007','71c05e83-0d67-4137-b2b7-478c4241a057','As portas externas que possibilitem, direta ou indiretamente, o acesso ao interior de qualquer compartimento localizado abaixo do convés de borda livre ou ao interior de uma superestrutura fechada, deverão ter uma soleira mínima de 150 mm (260 mm para embarcações que operam em área 2)','NORMAM-202/DPC, Cap. 05, Item 5.1.','borda_livre',NULL,30,1,'2026-07-03 06:44:28','2026-07-04 04:12:38',1,1,1,1,1,1),('a37275b5-76aa-11f1-9eb5-0a1b2af87b16','CBL-008','71c05e83-0d67-4137-b2b7-478c4241a057','Os escotilhões e as aberturas de escotilha possuem braçola de pelo menos 150 mm de altura (260 mm para embarcações que operam em área 2) e são dotados de tampas que possam ser fixadas às braçolas. As embarcações dos tipos “C” e “E” estão dispensadas da obrigatoriedade de possuírem tampas de escotilha ou dos escotilhões','NORMAM-202/DPC, Cap. 03, Seção I.','borda_livre',NULL,30,1,'2026-07-03 06:44:28','2026-07-04 04:12:38',1,1,0,1,0,1),('a3728c4f-76aa-11f1-9eb5-0a1b2af87b16','CBL-009','71c05e83-0d67-4137-b2b7-478c4241a057','As tampas das aberturas de escotilha, dos escotilhões e seus respectivos dispositivos de fechamento têm resistência suficiente que permite satisfazer as condições de estanqueidade previstas para o tipo de embarcação considerada e apresenta todos os elementos necessários que asseguram a estanqueidade','NORMAM-202/DPC, Cap. 03, Seção III.','borda_livre',NULL,30,1,'2026-07-03 06:44:28','2026-07-04 04:12:38',1,1,1,1,1,1),('a372a38d-76aa-11f1-9eb5-0a1b2af87b16','CBL-010','71c05e83-0d67-4137-b2b7-478c4241a057','Os suspiros externos, situados acima do convés de borda livre, deverão apresentar as seguintes caraterísticas: a) extremidade superior do suspiro em forma de “U” invertido ou com arranjo que proteja a sua abertura da entrada de água proveniente das intempéries; b) distância vertical entre o ponto a partir da qual a água efetivamente tem acesso ao tanque ou compartimento abaixo e o convés onde o suspiro se encontra instalado maior ou igual a 450 mm (760 mm nos conveses de borda livre e 450 mm nos demais conveses para embarcações que operam em área 2)','NORMAM-202/DPC, Cap. 05, Item 5.1.','borda_livre',NULL,30,1,'2026-07-03 06:44:28','2026-07-04 04:12:38',1,1,1,1,1,1),('a372bc98-76aa-11f1-9eb5-0a1b2af87b16','CBL-011','71c05e83-0d67-4137-b2b7-478c4241a057','Dispositivos de iluminação e ou ventilação natural (alboios) de compartimentos situados abaixo do convés de borda livre, que estão situados imediatamente acima do referido convés, deverão: a) ser estanque ao tempo (ou dispor de meios que possibilitem o seu fechamento estanque ao tempo) b) ser dotado de vidros com espessura compatível com sua área e máxima dimensão linear c) apresentar braçolas com, pelo menos, 150 mm de altura (260 mm para embarcações que operam em área 2)','NORMAM-202/DPC, Cap. 05, Item 5.1.','borda_livre',NULL,30,1,'2026-07-03 06:44:28','2026-07-04 04:12:38',1,1,1,1,1,1),('a372d307-76aa-11f1-9eb5-0a1b2af87b16','CBL-012','71c05e83-0d67-4137-b2b7-478c4241a057','Os dutos de ventilação ou exaustão destinados aos espaços situados abaixo do convés de borda livre deverão apresentar a borda inferior de sua extremidade externa com pelo menos 450 mm de altura acima do referido convés (760 mm para embarcações que operam em área 2)','NORMAM-202/DPC, Cap. 05, Item 5.1.','borda_livre',NULL,30,1,'2026-07-03 06:44:28','2026-07-04 04:12:38',1,1,0,1,0,1),('a372e880-76aa-11f1-9eb5-0a1b2af87b16','CBL-013','71c05e83-0d67-4137-b2b7-478c4241a057','Para embarcações que operam em área 2, as venezianas instaladas em anteparas ou portas externas, destinadas à ventilação de compartimentos situados sob o convés de borda livre ou superestruturas fechadas, e que não possuam meios efetivos de fechamento que as tornem estanques ao tempo, deverão possuir altura mínima de 760 mm','NORMAM-202/DPC, Cap. 05, Item 5.1.','borda_livre',NULL,30,1,'2026-07-03 06:44:28','2026-07-04 04:12:38',1,1,1,1,1,1),('a373033e-76aa-11f1-9eb5-0a1b2af87b16','CBL-014','71c05e83-0d67-4137-b2b7-478c4241a057','A extremidade junto ao costado dos tubos de descarga, provenientes de espaços situados abaixo do convés de borda livre ou de superestruturas fechadas, deverá ser dotada de válvulas de retenção e fechamento (combinadas ou não). Os meios disponíveis para operação de válvula de fechamento deverão ser facilmente acessíveis e estar sempre disponíveis (ver exigência abaixo)','NORMAM-202/DPC, Cap. 05, Item 5.1.','borda_livre',NULL,30,1,'2026-07-03 06:44:28','2026-07-04 04:12:38',1,1,1,1,1,1),('a3731baa-76aa-11f1-9eb5-0a1b2af87b16','CBL-015','71c05e83-0d67-4137-b2b7-478c4241a057','Quando a descarga se dá por gravidade e a distância vertical entre o ponto de descarga no costado e a extremidade superior do tubo for maior ou igual a 1,20 m (2,0 m para embarcações que operam em área 2) as válvulas poderão ser de fechamento sem retenção (ver exigência acima)','NORMAM-202/DPC','borda_livre',NULL,30,1,'2026-07-03 06:44:28','2026-07-04 04:12:38',1,1,1,1,1,1),('a3733364-76aa-11f1-9eb5-0a1b2af87b16','CBL-016','71c05e83-0d67-4137-b2b7-478c4241a057','As descargas de gases provenientes de motores de combustão interna que sejam posicionadas na popa ou nos costados, mesmo quando associadas à descarga de água de refrigeração dos motores (“descarga molhada”), estão dispensadas da obrigatoriedade da instalação de válvulas de retenção ou fechamento, mas deverão atender aos seguintes requisitos: a) deverão ser flangeadas no casco b) beverão ser de aço ou material equivalente nas proximidades do casco','NORMAM-202/DPC, Cap. 03, Seção III.','borda_livre',NULL,30,1,'2026-07-03 06:44:28','2026-07-04 04:12:38',1,1,1,1,1,1),('a373534c-76aa-11f1-9eb5-0a1b2af87b16','CBL-017','71c05e83-0d67-4137-b2b7-478c4241a057','Embarcações dos tipos D e E que operem em área 2 deverão possuir altura mínima de proa de acordo com o item 0619','NORMAM-202/DPC','borda_livre',NULL,30,1,'2026-07-03 06:44:28','2026-07-04 04:12:38',0,0,0,1,1,0),('a373c1f4-76aa-11f1-9eb5-0a1b2af87b16','CBL-018','71c05e83-0d67-4137-b2b7-478c4241a057','O Disco de Plimsoll está posicionado conforme Notas para a Marcação da Borda Livre.','NORMAM-202/DPC, Cap. 05, Item 5.1.','borda_livre',NULL,30,1,'2026-07-03 06:44:28','2026-07-04 04:12:38',1,1,1,1,1,1),('a3a06b64-50be-420a-9892-2c189dcbe724','EX-426','b8ed9a31-9fa3-492f-904e-b8158a06d0da','As baterias deverão: c) atender a uma altura mínima de 40 cm do piso, quando fixadas em conveses situados abaixo do convés principal','NORMAM-202/DPC, Cap. 03, Seção IV.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('a431a945-f958-40bc-9491-058a3d643c98','EX-464','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Há espaço livre para circulação nos bordos da embarcação, ao longo de todos os espaços para redes. Essa circulação deverá apresenta largura mínima de 800 mm por bordo','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('a4f04bb2-0533-498c-970e-73a3c5de19e2','EX-412','65bf89f0-f44d-4746-89f7-f530c9aa990d','Verificar o funcionamento do alarme de nível alto de esgoto (visual e ou sonoro), emitido na praça de máquinas e no comando – para embarcações com AB maior que 20','NORMAM-202/DPC, Cap. 03, Seção III.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,0,1,0,0),('a73b0ca4-6bbb-41d6-ac23-410beabbe8b9','EX-309','aa4a7f0d-004d-4a60-924e-693335fdd69b','Certificado de conformidade para transporte de produtos químicos perigosos a granel (se aplicável)','NORMAM-202/DPC','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('a9916551-a7e8-49b4-aa43-ee43ed71e60f','EX-466','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','A área mínima requerida para o transporte de passageiros em redes considera a concentração de 1 passageiro por m², sem rede em cima de rede. No cálculo dessa área não estão computadas as áreas de circulação, de embarque e desembarque, de estivagem de bagagens ou transporte de carga, nem corredores ou escadas','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('ac2e0924-d475-4f40-8429-553d94cbd7c1','EX-445','b8ed9a31-9fa3-492f-904e-b8158a06d0da','h) nos compartimentos e locais onde existe depósito de materiais inflamáveis, os interruptores, tomadas de correntes, luminárias e demais equipamentos elétricos são à prova de explosão','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('ad528287-01ba-4c8f-ac0a-0203113ba8c6','EX-465','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Ocorre o transporte simultâneo de passageiros em redes e em bancos laterais, junto aos bordos, e o limite de espaço para redes se iniciar a não menos de 1,70m da face interna da balaustrada do convés considerado','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('ad8b2645-95b8-4f61-a654-5610123e893e','EX-404','65bf89f0-f44d-4746-89f7-f530c9aa990d','As tubulações advindas dos tanques de óleo, por intermédio da qual o óleo é conduzido às máquinas principais ou auxiliares, deverão ser de material metálico ou material resistente ao fogo e possuir válvula de fechamento rápido, o qual deverá ser testado','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('ae76d3fb-35cf-4108-81f2-4d0e8a579cab','EX-418','b8ed9a31-9fa3-492f-904e-b8158a06d0da','A fonte de energia elétrica principal consegue manter em funcionamento todos os serviços essenciais independentemente do sentido e da velocidade de rotação das máquinas principais e do eixo propulsor','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('af6b1cb2-e94a-452c-a083-9b7e2f41ff69','EX-392','65bf89f0-f44d-4746-89f7-f530c9aa990d','Motores cujo sistema de arrefecimento seja constituído por ventiladores deverão ter os mesmos providos de proteção','NORMAM-202/DPC, Cap. 03, Seção III.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('b16a6bde-ff11-49be-aa7e-ad733190b39c','EX-360','b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','Porto de inscrição (Embarcações de Sobrevivência/Boias)','NORMAM-202/DPC, Cap. 04, Item 4.12.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('b2594475-d99b-47e9-b28f-ef970b9ef621','EX-554','71c05e83-0d67-4137-b2b7-478c4241a057','Acompanhar fisicamente a medição por ultrassom feita por engenheiro qualificado contratado, incluindo o lixamento de um ponto redondo de ~5 cm de diâmetro nas chapas.','NORMAM-202/DPC, Cap. 03, Seção I.','seco',NULL,30,1,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,1,1,1,1,1),('b27da535-c866-4c52-9a83-b3e5b10072e0','EX-320','e70f7906-4e9d-4367-b10a-2ad2a007817a','Prumo de mão','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,0,1,0,0),('b3e0478a-37ea-4ecf-a8f7-d81e816f1a25','EX-408','65bf89f0-f44d-4746-89f7-f530c9aa990d','Toda tubulação de gás (não de cozinha), combustível, óleo lubrificante, substancias inflamáveis em geral e fiações não poderá distar menos que 200 mm das tubulações de descarga ou de quaisquer superfícies em alta temperatura','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('b3f0b053-6c41-42f4-adb3-a3f0d76c9e05','EX-531','71c05e83-0d67-4137-b2b7-478c4241a057','A antepara de colisão de vante está posicionada entre 5 e 8% do Lregra, a partir da parte superior do espelho ou da roda de proa','NORMAM-202/DPC','flutuando',NULL,30,1,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,0,1,1,0,0),('b56def21-6b53-42cc-a16b-35f5a0a63c59','EX-476','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','As cadeiras deverão atender às seguintes dimensões: d) distância mínima de 0,90 m entre os encostos dos assentos montados frente a frente, ou entre o encosto e uma antepara, ou outra divisão que por ventura exista à frente do assento','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('b5ce3089-e78e-4390-99bb-e8855acd1ffd','EX-397','65bf89f0-f44d-4746-89f7-f530c9aa990d','Todo espaço de máquinas deverá ter ventilação (forçada ou natural) apropriada ao funcionamento dos equipamentos','NORMAM-202/DPC','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('b5f8b4f6-cb8d-432f-b7cd-52bdb1121ae8','EX-478','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Os corredores de circulação e ou acesso aos camarotes apresentam largura mínima de 0,8 m para um comprimento máximo de 10 m. Quando o comprimento dos corredores internos excede a 10 m, a largura mínima é acrescida de 0,05 m para cada 2 m ou fração a mais no comprimento, até o máximo de 1 m','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('b6db0410-2703-4196-993a-ed9f04038200','EX-533','71c05e83-0d67-4137-b2b7-478c4241a057','Há antepara a vante da praça de máquinas, somente embarcações de passageiros','NORMAM-202/DPC, Cap. 03, Seção III.','flutuando',NULL,30,1,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,0,1,1,0,0),('b7545aa5-51fe-44d7-9513-fd491720ace9','EX-302','aa4a7f0d-004d-4a60-924e-693335fdd69b','Cartão de Tripulação de Segurança','NORMAM-202/DPC, Cap. 04, Item 4.2), 4.2.1, m, III','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 02:36:44',1,1,1,1,1,1),('b8b68324-6f6c-48d4-af7f-84d98d71eca7','EX-516','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Verificar a afixação de placa educativa em local visível no convés com os dizeres: \'Não jogue lixo no rio, deposite seu lixo aqui\'.','NORMAM-202/DPC, Cap. 09, Item 9.2','flutuando',NULL,30,1,'2026-07-03 05:38:14','2026-07-04 02:36:44',1,1,1,1,1,1),('bac0b5fb-e1ef-4ce4-b171-36716b176f2e','EX-424','b8ed9a31-9fa3-492f-904e-b8158a06d0da','As baterias deverão: a) ser instaladas em locais não habitados, arejados e abrigados','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('bac38230-26ef-427d-b223-0d1b0bc96b03','EX-487','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Nos camarotes há ventilação natural por janela ou alboio, dando para o exterior da embarcação, com uma abertura mínima de 0,1 m² por janela ou alboio. A ventilação natural pode ser substituída por ventilação forçada através de ventilador e ou ar condicionado','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('bb1b61cc-c7fb-4a39-a7b2-749267af3ac9','EX-447','b8ed9a31-9fa3-492f-904e-b8158a06d0da','j) não são utilizadas extensões elétricas (caso usadas numa necessidade eventual, verificar a capacidade de corrente e, dependendo da distância, a queda de tensão)','NORMAM-202/DPC','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('bc4bc5e4-a100-4aa5-a3f0-6f0d7405fb64','EX-386','a5f25230-91c9-4e14-aa33-e83524d5d943','Os esguichos não têm menos de 12 mm de diâmetro','NORMAM-202/DPC, Cap. 04, Seção III.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,0,1,0,0),('bd328ebf-7ae2-4e72-8d75-c1519b935d1b','EX-536','71c05e83-0d67-4137-b2b7-478c4241a057','A embarcação deverá ser marcada de modo visível e durável, com letras e algarismos de tamanho apropriado às dimensões da embarcação, com letras de, no mínimo, 10 cm, na popa, o nome da embarcação juntamente com o porto de inscrição e, na proa, o nome da embarcação nos dois bordos','NORMAM-202/DPC, Cap. 02, Seção II.','flutuando',NULL,30,1,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,1,1,1,1,1),('bd5d3265-5bb4-4d45-a4a3-592dbaeafc7b','EX-351','b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','Os aparelhos flutuantes estão estivados de modo a flutuarem livremente em caso de naufrágio','NORMAM-202/DPC','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('be414d13-fba6-478b-b244-8cae54e7532e','EX-513','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Verificar o estado físico de conservação, higiene e limpeza dos colchões fornecidos nos camarotes.','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,1,1,1,1,1),('bed32fa9-00cb-4821-a92a-f9d913ef261e','EX-425','b8ed9a31-9fa3-492f-904e-b8158a06d0da','As baterias deverão: b) ser mantidas devidamente fixadas e com seus bornes de ligação sem azinhavre e protegidos por material isolante','NORMAM-202/DPC, Cap. 03, Seção IV.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('c01f90ce-7dc7-494d-ac0d-631ac1833ac4','EX-391','65bf89f0-f44d-4746-89f7-f530c9aa990d','Quaisquer polias, correias e demais partes móveis utilizadas para acionamento de máquinas e ou mecanismos deverão ser dotadas de dispositivos adequados de proteção para as pessoas','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('c0b150ff-dbbe-4b9e-9228-6e66a738b87b','EX-481','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Os camarotes destinados a mais de 4 pessoas em beliches possuem área mínima de 1,5 m² por pessoa','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('c1d3a7cb-333e-4e09-96ef-098c409c7c6e','EX-546','71c05e83-0d67-4137-b2b7-478c4241a057','O material empregado na construção da embarcação está de acordo com aquele mencionado no Memorial Descritivo','NORMAM-202/DPC','seco',NULL,30,1,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,1,1,1,1,1),('c1e33d68-30aa-4c63-8059-7c6f66ce4dad','EX-497','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','O sanitário coletivo mínimo é formado por uma unidade sanitária e lavatório, tendo área mínima de 1,26 m² e pode ser usado simultaneamente por mais de uma pessoa','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,0,1,1,0,0),('c231dec1-4488-4a8c-a9bc-3633e4f940c3','EX-523','71c05e83-0d67-4137-b2b7-478c4241a057','As janelas ou escotilhas, indicadas no Plano de Segurança como via de escape, possuem um vão livre mínimo não inferior a 600 x 600 mm, se instaladas em conveses e 600 x 800 mm, se instaladas em anteparas','NORMAM-202/DPC, Cap. 04, Item 4.2), 4.2.1, m, I.','flutuando',NULL,30,1,'2026-07-03 05:38:14','2026-07-04 02:36:44',1,0,1,1,0,0),('c33725e8-227b-4dd2-9f32-e9e083b8d97c','EX-462','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Os corredores ou passarelas externas de circulação e acesso com até 10 m de comprimento apresentam largura mínima de 650 mm. Como o comprimento excede a 10 m, a largura mínima é acrescida de 50 mm para cada 2 m ou fração de comprimento, até no máximo de 800 mm','NORMAM-202/DPC, Cap. 03, Seção V.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,0,1,1,0,0),('c3c80149-529a-42c6-8a26-36c464054bca','EX-396','65bf89f0-f44d-4746-89f7-f530c9aa990d','Toda lâmpada deverá ser protegida contra choques, eficazmente, por luminárias','NORMAM-202/DPC','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('c85334c5-8f56-4ee3-be27-b6783951d5c3','EX-480','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Os camarotes para 3 ou 4 passageiros ou tripulantes possuem dimensões mínimas de 1,9 m x 3,0 m, contendo uma cama e um beliche duplo ou dois beliches duplos','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,0,1,1,0,0),('c8d265a4-62cc-4153-b226-337375cd363d','EX-526','71c05e83-0d67-4137-b2b7-478c4241a057','As alturas das luzes de navegação estão de acordo com as normas específicas sobre o assunto','RIPEAM 72 / NORMAM-202/DPC, Cap. 04.','flutuando',NULL,30,1,'2026-07-03 05:38:14','2026-07-04 04:12:39',1,1,1,1,1,1),('ca1c1aed-7e2a-4d54-92cd-7567486150c7','EX-375','a5f25230-91c9-4e14-aa33-e83524d5d943','Nas DEMAIS embarcações, as tomadas (hidrantes) deverão estar posicionadas de modo a propiciar, pelo menos, dois jatos d\'água não provenientes da mesma tomada de incêndio','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('cad656d0-6125-4f9c-be76-9d9ce5e03c99','EX-556','9e81f468-422b-40e4-8bf8-40b60a027a36','Realizar verificação física detalhada de todo o hélice, leme, bucha e eixo propulsor da embarcação em seco, buscando desgastes, trincas ou folgas anômalas.','NORMAM-202/DPC, Cap. 03, Seção III.','seco',NULL,30,1,'2026-07-03 05:38:14','2026-07-04 04:12:39',1,1,1,1,1,1),('ccaeea91-05ea-4864-a770-5c9b98ae8f48','EX-342','b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','Tamanho (apenas para os coletes salva vidas)','NORMAM-202/DPC, Cap. 04, Item 4.13.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('cd2dfb47-4f43-46b4-a27b-1e977ae0f5f2','EX-409','65bf89f0-f44d-4746-89f7-f530c9aa990d','Motores providos de sistema de abertura das válvulas de admissão e descarga, por intermédio de balancins, deverão ter seus tuchos de acionamento protegidos','NORMAM-202/DPC, Cap. 03, Seção III.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('ce1ba98a-6d1a-4140-a789-ca3efa885333','EX-402','65bf89f0-f44d-4746-89f7-f530c9aa990d','Os tanques de óleo situados no interior da Praça de Maquinas deverão ser dotados de suspiros independentes e cuja saída deverá estar localizada em área externa','NORMAM-202/DPC, Cap. 09, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('ce50512f-13f2-4b0e-a2f7-bc1ae1e5bffd','EX-340','b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','Número de série (se tiver) (Coletes salva-vidas)','NORMAM-202/DPC, Cap. 04, Item 4.13.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('cf097e63-f9a6-4408-ae6e-766baddc6322','EX-477','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Os espaços de cadeiras apresentam ventilação natural permanente para o exterior da embarcação, tendo como meio de fechamento sanefas ou janelas móveis. No caso de janela móvel, a área mínima de ventilação é de 40% do vão da abertura','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,0,1,1,0,0),('cf34c2da-207c-4d4c-a185-8c19374aaedf','EX-323','e70f7906-4e9d-4367-b10a-2ad2a007817a','Alarme visual e sonoro de alta temperatura da água de resfriamento do MCP e MCA com potência igual ou superior a 800 HP (597 kW)','NORMAM-202/DPC','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,0,0,1,0,0),('d11e0a27-5ba2-4d6f-9d9d-1415a92db143','EX-353','b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','Número do certificado de homologação pela DPC (Embarcações de Sobrevivência/Boias)','NORMAM-202/DPC, Cap. 04, Item 4.12.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('d171a5f8-0d0a-4279-9688-68856ea403e3','EX-505','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Os acessos às unidades sanitárias são efetuados através de vão mínimo de 1,8 x 0,55 m, dotados de portas com dispositivo de travamento interno e apresenta uma altura livre de, no máximo 0,3 m e, no mínimo 0,1 m, entre a porta e o piso','NORMAM-202/DPC','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,0,1,1,0,0),('d35a46ed-2908-4475-897d-fe955538be34','EX-453','b8ed9a31-9fa3-492f-904e-b8158a06d0da','Na instalação elétrica não existe fios soltos, desencapados ou qualquer outra condição que possa vir a provocar um curto-circuito','NORMAM-202/DPC','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('d3653240-9326-4f99-a41f-fccfd35e75b2','EX-341','b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','Data de fabricação (Coletes salva-vidas)','NORMAM-202/DPC, Cap. 04, Item 4.13.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('d6c54388-c992-4021-8a62-0a5400976539','EX-509','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Há pelo menos uma rampa, adequada às características da embarcação e ao local onde se efetua o embarque/desembarque de passageiros, para facilitar a entrada e saída dos passageiros','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,0,1,0,0,0),('d7a3466c-1c51-4001-a537-7f02912156a8','EX-406','65bf89f0-f44d-4746-89f7-f530c9aa990d','Toda fiação elétrica dos motores principais, auxiliares e equipamentos acessórios deverá ser protegida por eletrodutos ou acondicionada em “chicotes” apropriados','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('d970e4db-5964-4eaa-add3-dee2763eab6e','EX-313','aa4a7f0d-004d-4a60-924e-693335fdd69b','Tabelas ou quadros no comando: - sinais sonoros e luminosos','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,0,0,1,0,0),('da44538d-807e-40ef-9c99-0bb3c1f0c7a7','EX-532','71c05e83-0d67-4137-b2b7-478c4241a057','A antepara de colisão de ré está colocada de forma que limita o tubo telescópico em um espaço estanque à água de volume moderado','NORMAM-202/DPC, Cap. 03, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:14','2026-07-04 04:12:39',1,0,1,1,0,0),('da807bea-cb86-4be2-8655-97320c8fd059','EX-379','a5f25230-91c9-4e14-aa33-e83524d5d943','Não são usados para as redes de incêndio e para as tomadas de incêndio, materiais cujas características são prejudicadas pelo calor (como plásticos e PVC).','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('dab5c2ba-432e-47f3-a6ab-0a0e67b420a5','EX-315','aa4a7f0d-004d-4a60-924e-693335fdd69b','As embarcações que transportem passageiros deverão ter afixadas, em local visível aos passageiros, uma placa contendo o número de inscrição da embarcação, peso máximo de carga, número máximo de passageiros por convés que a embarcação está autorizada a transportar e número do telefone da OM em cuja jurisdição a embarcação estiver operando','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,0,0,1,0,0),('dbc42c9d-c0f2-44bc-ad57-b78a7b4e0ab3','EX-377','a5f25230-91c9-4e14-aa33-e83524d5d943','Nas DEMAIS embarcações, próximas à entrada da praça de máquinas (lado externo), deverão ser previstas uma tomada de incêndio e uma estação de incêndio com uma ou mais seções de mangueira e um aplicador de neblina','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('dbe76a3f-4454-4836-a600-1c3c99c06475','EX-458','f299c8c7-4402-4efa-89c6-d5add1fa60d5','A embarcação, que navega sob jurisdição da Capitania dos Portos de Barra Bonita, possui o equipamento AIS em pleno funcionamento','ANATEL / NORMAM','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-03 05:41:41',1,0,0,1,0,0),('e125df21-a446-4bef-9486-35a165b9220b','EX-326','e70f7906-4e9d-4367-b10a-2ad2a007817a','Agulha giroscópica ou magnética','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,0,0,1,0,0),('e1a77c79-63a6-4d5e-8906-64f06dee4a9a','EX-432','b8ed9a31-9fa3-492f-904e-b8158a06d0da','Quanto aos quadros elétricos: f) os quadros elétricos não estão localizados a vante da antepara de colisão','NORMAM-202/DPC','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('e204d705-f37b-46c6-88b6-5d46f506064b','EX-543','71c05e83-0d67-4137-b2b7-478c4241a057','Verificar se os acessos aos locais abaixo relacionados estão livres: Porões de carga','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:14','2026-07-04 04:12:39',1,1,1,1,1,1),('e26e80f5-8422-4fb7-8199-6669ac222815','EX-308','aa4a7f0d-004d-4a60-924e-693335fdd69b','Certificado de conformidade para transporte de gases liquefeitos a granel (se aplicável)','NORMAM-202/DPC','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('e27dc4c7-dd3b-4269-bc57-601cbb159450','EX-354','b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','Fabricante (Embarcações de Sobrevivência/Boias)','NORMAM-202/DPC, Cap. 04, Item 4.12.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('e2dc9cdc-437a-4c3a-8710-ce6bb9d4c3f6','EX-411','65bf89f0-f44d-4746-89f7-f530c9aa990d','Qualquer sistema de monitoramento e ou controle de equipamentos instalado no passadiço deverá ser dotado de placas identificadoras, assim como provido de uma iluminação apropriada','NORMAM-202/DPC, Cap. 03, Seção IV.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('e402c282-bbf7-4213-b997-761e8e06227a','EX-311','aa4a7f0d-004d-4a60-924e-693335fdd69b','Tabelas ou quadros no comando: - sinais de salvamento','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,0,0,1,0,0),('e4382149-9351-4ffe-8e6c-004723fdb8a0','EX-448','b8ed9a31-9fa3-492f-904e-b8158a06d0da','k) os acessórios de iluminação são instalados de maneira tal que evitam aumentos de temperatura que possam danificar cabos e fiação e impeçam que o material situado nos arredores se torne excessivamente quente','NORMAM-202/DPC, Cap. 03, Seção IV.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('e4c70296-da8c-4f2d-a1e5-a20287dddb1c','EX-433','b8ed9a31-9fa3-492f-904e-b8158a06d0da','Quanto aos quadros elétricos: g) estão limpos e mantidos','NORMAM-202/DPC','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('e4db742c-931a-43ef-bff3-287ef5d42c1f','EX-521','71c05e83-0d67-4137-b2b7-478c4241a057','Acima do convés aberto mais baixo, as vias de escape são escadas, portas ou janelas ou uma combinação delas, dando para um convés aberto','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:14','2026-07-04 04:12:39',1,0,1,1,0,0),('e556f7ad-a680-44ce-861d-f051aac27a86','EX-417','b8ed9a31-9fa3-492f-904e-b8158a06d0da','A fonte de energia principal tem capacidade suficiente para suprir a carga necessária para manter a embarcação em plenas condições de operação e habitabilidade, levando-se em consideração os fatores de potência, de demanda e a simultaneidade das cargas','NORMAM-202/DPC, Cap. 03, Seção IV.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('e55e3316-1841-41f7-8eca-de405ef9e180','EX-388','a5f25230-91c9-4e14-aa33-e83524d5d943','Somente deverão ser utilizadas redes de aço e acessórios de materiais resistentes ao fogo junto ao casco, nos embornais, nas descargas sanitárias e em outras descargas situadas abaixo do convés estanque.','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('e64d7ec0-fccc-4d7b-91f0-043098347422','EX-307','aa4a7f0d-004d-4a60-924e-693335fdd69b','Certificado de Borda Livre, quando aplicável','NORMAM-202/DPC, Cap. 05, Item 5.1.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('e70fad1d-6ee7-4ceb-9c23-d101f192e2a3','EX-363','a5f25230-91c9-4e14-aa33-e83524d5d943','Nenhum tanque ou rede de combustível está posicionado em local onde qualquer derramamento ou vazamento dele proveniente, venha constituir risco de incêndio pelo contato com superfícies aquecidas ou equipamentos elétricos','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('e8afc2e7-7783-4ea7-9e95-fccf3e8499dd','EX-415','65bf89f0-f44d-4746-89f7-f530c9aa990d','Verificar se os empurradores possuem placa física identificadora com o número do motor ou, se inexistente, exigir Nota Fiscal ou Recibo de Compra e Venda.','NORMAM-202/DPC, Cap. 03, Seção III.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('e9226bc3-3b12-417e-946f-18c0176792e0','EX-324','e70f7906-4e9d-4367-b10a-2ad2a007817a','Sistema de comunicação que possibilita ao comando divulgar informações gerais por intermédio de alto-falantes nos locais destinados aos passageiros (para embarcações com mais de 100 passageiros)','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,0,0,1,0,0),('eae082a5-c90e-4a46-8922-aadbe8cdeea0','EX-471','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','As portas de acesso estão posicionadas de forma que uma pessoa não necessita se deslocar mais de 13 m em linha reta, a partir de qualquer posição do espaço de cadeiras, para alcançar uma das portas','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,0,1,1,0,0),('eb283686-11d5-4d21-aa6a-46fa76015422','EX-469','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Todos os corredores têm livre acesso às saídas do compartimento','NORMAM-202/DPC, Cap. 03, Seção V.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,0,1,1,0,0),('eba785cf-5373-49b1-9f45-74624533cd4e','EX-495','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','As unidades de banheiro têm área maior ou igual a 1,3 m², sendo que as medidas do boxe são de 0,7 x 0,7 m ou maiores. A largura da unidade de banheiro é maior ou igual a 0,8 m','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,0,1,1,0,0),('ec47b315-cde2-4d25-955b-8ef469a3db99','EX-457','f299c8c7-4402-4efa-89c6-d5add1fa60d5','A licença-rádio deverá ser mantida a bordo da embarcação.','ANATEL / NORMAM','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-03 05:41:41',1,0,0,1,0,0),('ec652099-4966-4fea-94f7-0c41adde6ccb','EX-306','aa4a7f0d-004d-4a60-924e-693335fdd69b','Certificado ou notas de arqueação','NORMAM-202/DPC, Cap. 06, Item 6.1.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('ecf0c6d1-02a0-479f-9b92-982e68083700','EX-430','b8ed9a31-9fa3-492f-904e-b8158a06d0da','Quanto aos quadros elétricos: d) se a fonte de emergência de energia for constituída por bateria de acumuladores, ela não está instalada no mesmo compartimento do quadro elétrico de emergência','NORMAM-202/DPC, Cap. 03, Seção IV.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('ecf9e38b-e522-425b-9daa-e0323352bab8','EX-522','71c05e83-0d67-4137-b2b7-478c4241a057','Não há corredores sem saída com mais de 7 m de comprimento (um corredor sem saída é um corredor ou parte de um corredor a partir do qual só há uma via de escape)','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:14','2026-07-04 04:12:39',1,0,1,1,0,0),('ee4ccc12-4cbd-45d3-a239-fd8d70eb6e7b','EX-310','aa4a7f0d-004d-4a60-924e-693335fdd69b','Tabelas ou quadros no comando: - regras de governo e navegação','NORMAM-202/DPC','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,0,0,1,0,0),('eed4571e-88f9-4f4a-833b-bc4cfbb5dc2a','EX-304','aa4a7f0d-004d-4a60-924e-693335fdd69b','Caderneta de Inscrição e Registro de cada tripulante (CIR)','NORMAM-202/DPC','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('ef865d12-3b6a-4d96-b9e0-a32b12b89725','EX-455','f299c8c7-4402-4efa-89c6-d5add1fa60d5','Os equipamentos de radiocomunicação funcionam e podem operar na freqüência de 156,8 Mhz (canal 16)','NORMAM-202/DPC, Cap. 04, Item 4.8), 4.8.1.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 02:36:44',1,0,0,1,0,0),('efb0d9fe-b5be-4c6d-817d-edd230a5c0a9','EX-336','b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','Número do certificado de homologação pela DPC (Coletes salva-vidas)','NORMAM-202/DPC, Cap. 04, Item 4.13.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('f10786b6-5cfd-4656-8789-db333c13166f','EX-346','b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','Os coletes salva vidas estão estivados de maneira a serem prontamente utilizados, em local visível, bem sinalizado e de fácil acesso','NORMAM-202/DPC, Cap. 04, Item 4.13.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('f1305470-ca00-414f-9f1b-8082fc6cb2a6','EX-493','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Os compartimentos sanitários são dotados de meios de drenagem no ponto mais baixo do piso. As unidades de chuveiro possuem dreno específico','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,0,1,1,0,0),('f199c93c-ce4a-424f-8ea6-da60372de2e4','EX-524','71c05e83-0d67-4137-b2b7-478c4241a057','As rotas de escape estão marcadas por setas indicadoras, pintadas em cor contrastante, indicando \'Saída de Emergência\'. A marcação permite, aos passageiros e tripulantes, a identificação de todas as rotas de evacuação e a rápida identificação das saídas','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,'2026-07-03 05:38:14','2026-07-04 04:12:39',1,0,1,1,0,0),('f1abbac0-6684-47e0-b67e-0c850ad377ae','EX-549','71c05e83-0d67-4137-b2b7-478c4241a057','O casco e os conveses estão em condições satisfatórias, sem deterioração acentuada, não apresentando mossas, trincas ou furos por corrosão','NORMAM-202/DPC','seco',NULL,30,1,'2026-07-03 05:38:14','2026-07-04 04:12:39',1,1,1,1,1,1),('f3fa1e72-5aa5-46d3-bde1-caa01704b771','EX-440','b8ed9a31-9fa3-492f-904e-b8158a06d0da','c) os eletrodutos estão instalados com suficiente caimento e furos para dar drenagem e evitar o acúmulo d’água','NORMAM-202/DPC','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('f42be128-51c4-4240-bd88-d0031f30b2e3','EX-468','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Os corredores internos dos salões de cadeiras têm largura mínima de 800mm para um comprimento máximo equivalente a 20 filas de cadeiras consecutivas. Para um comprimento superior, a largura mínima é acrescida de 100 mm para cada 10 filas ou fração de cadeiras a mais','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,0,1,1,0,0),('f5a3cf01-94bc-4944-a3c1-4db1811db59b','EX-399','65bf89f0-f44d-4746-89f7-f530c9aa990d','Não deverá haver vazamentos ou descargas de gases provenientes da queima de combustão no interior dos espaços de máquinas ou outros compartimentos quaisquer.','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('f6b03730-2355-4d50-82d9-573150d8ec4f','EX-442','b8ed9a31-9fa3-492f-904e-b8158a06d0da','e) as extremidades e junções de todos os condutores são feitas de modo a serem conservadas as propriedades originais elétricas e mecânicas','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('f6b5c4dc-45a7-4eb8-b2f0-92e2f01171a2','EX-530','71c05e83-0d67-4137-b2b7-478c4241a057','O ponto de alagamento progressivo (qualquer acesso ao casco não estanque ao tempo) está localizado exatamente no local informado no projeto – geralmente no Estudo de Estabilidade ou nas Curvas','NORMAM-202/DPC, Cap. 03, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:14','2026-07-04 04:12:39',1,1,1,1,1,1),('f91ac072-d60c-4502-8590-472181dc8a53','EX-378','a5f25230-91c9-4e14-aa33-e83524d5d943','As mangueiras e seus acessórios ficam acondicionados em cabides ou estações de incêndio (armário pintado de vermelho, dotado em sua antepara frontal de uma porta)','NORMAM-202/DPC','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('f95612f7-d307-4cdf-8a02-41124b7bf5e2','EX-305','aa4a7f0d-004d-4a60-924e-693335fdd69b','Regras para evitar abalroamento – RIPEAM (exceto para embarcações sem propulsão quando rebocadas/empurradas)','RIPEAM 72 / NORMAM-202/DPC, Cap. 04.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,0,0,1,0,1),('fa01a553-9f0b-4eb4-a2fa-fe53004c7e78','EX-434','b8ed9a31-9fa3-492f-904e-b8158a06d0da','Os circuitos de distribuição, geradores e alimentadores são individualmente protegidos por disjuntores ou fusíveis contra sobrecarga e curto-circuito','NORMAM-202/DPC, Cap. 03, Seção IV.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('fa3a530e-d204-4571-b0ef-3902a2ff8f50','EX-383','a5f25230-91c9-4e14-aa33-e83524d5d943','O diâmetro das mangueiras de incêndio não é inferior a 38 mm (1,5\'\')','NORMAM-202/DPC','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,0,0,1,0,0),('fd836b06-765d-4b56-a022-699234aab52b','EX-435','b8ed9a31-9fa3-492f-904e-b8158a06d0da','Os transformadores são protegidos com disjuntores no primário','NORMAM-202/DPC, Cap. 03, Seção IV.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('fd9cb55e-6e74-4f21-b89a-3c77685d0862','EX-370','a5f25230-91c9-4e14-aa33-e83524d5d943','As embarcações propulsadas empregadas no transporte de passageiros com AB maior que 10 e as demais embarcações propulsadas com AB maior que 20 deverão ser dotadas de pelo menos uma bomba de esgoto com vazão total maior ou igual a 15 m³/h','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,0,0,1,0,1),('fee925e7-19cc-4f27-839e-d320076cd13f','EX-421','b8ed9a31-9fa3-492f-904e-b8158a06d0da','A fonte de energia elétrica de emergência é independente da fonte principal e com capacidade de alimentar por uma hora todos os sistemas elétricos e consumidores necessários à segurança de passageiros e tripulação','NORMAM-202/DPC, Cap. 03, Seção IV.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('ff928f0e-e467-4d37-b188-fe991b28568e','EX-300','aa4a7f0d-004d-4a60-924e-693335fdd69b','Plano de Segurança','NORMAM-202/DPC, Cap. 04, Item 4.2), 4.2.1, m, I.','flutuando',NULL,30,1,'2026-07-03 05:38:13','2026-07-04 02:36:44',1,0,0,1,0,0);
/*!40000 ALTER TABLE `exigencias_catalogo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exigencias_categorias`
--

DROP TABLE IF EXISTS `exigencias_categorias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `exigencias_categorias` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT (uuid()),
  `nome` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_categoria_nome` (`nome`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exigencias_categorias`
--

LOCK TABLES `exigencias_categorias` WRITE;
/*!40000 ALTER TABLE `exigencias_categorias` DISABLE KEYS */;
INSERT INTO `exigencias_categorias` VALUES ('65bf89f0-f44d-4746-89f7-f530c9aa990d','Praça de Máquinas','2026-07-03 05:36:20','2026-07-03 05:36:20'),('71c05e83-0d67-4137-b2b7-478c4241a057','Casco, Estrutura e Porão','2026-07-03 05:36:20','2026-07-03 05:36:20'),('9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Habitabilidade e Cozinha','2026-07-03 05:36:20','2026-07-03 05:36:20'),('9e81f468-422b-40e4-8bf8-40b60a027a36','Sistemas de Propulsão e Governo','2026-07-03 05:36:20','2026-07-03 05:36:20'),('a5f25230-91c9-4e14-aa33-e83524d5d943','Combate a Incêndio','2026-07-03 05:36:20','2026-07-03 05:36:20'),('aa4a7f0d-004d-4a60-924e-693335fdd69b','Documentação e Certificados','2026-07-03 05:36:20','2026-07-03 05:36:20'),('b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','Salvatagem e Segurança','2026-07-03 05:36:20','2026-07-03 05:36:20'),('b8ed9a31-9fa3-492f-904e-b8158a06d0da','Setor Elétrico','2026-07-03 05:36:20','2026-07-03 05:36:20'),('e70f7906-4e9d-4367-b10a-2ad2a007817a','Sistemas de Navegação e Comando','2026-07-03 05:36:20','2026-07-03 05:36:20'),('f299c8c7-4402-4efa-89c6-d5add1fa60d5','Rádio e Comunicações','2026-07-03 05:36:20','2026-07-03 05:36:20');
/*!40000 ALTER TABLE `exigencias_categorias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exportacoes_documentos`
--

DROP TABLE IF EXISTS `exportacoes_documentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `exportacoes_documentos` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `solicitado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('AGUARDANDO','PROCESSANDO','CONCLUIDA','FALHA','EXPIRADA') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'AGUARDANDO',
  `categorias_json` json NOT NULL,
  `filtros_json` json DEFAULT NULL,
  `caminho_arquivo` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nome_arquivo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tamanho_bytes` bigint unsigned DEFAULT NULL,
  `quantidade_arquivos` int unsigned NOT NULL DEFAULT '0',
  `sha256` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `erro` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `solicitado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `iniciado_em` datetime DEFAULT NULL,
  `concluido_em` datetime DEFAULT NULL,
  `expira_em` datetime DEFAULT NULL,
  `baixado_em` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_exportacoes_status` (`status`,`solicitado_em`),
  KEY `idx_exportacoes_usuario` (`solicitado_por`,`solicitado_em`),
  CONSTRAINT `fk_exportacoes_usuario` FOREIGN KEY (`solicitado_por`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exportacoes_documentos`
--

LOCK TABLES `exportacoes_documentos` WRITE;
/*!40000 ALTER TABLE `exportacoes_documentos` DISABLE KEYS */;
/*!40000 ALTER TABLE `exportacoes_documentos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feedback_anexos`
--

DROP TABLE IF EXISTS `feedback_anexos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `feedback_anexos` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `mensagem_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nome_arquivo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `chave_arquivo` varchar(600) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tipo_mime` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `extensao` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tamanho` bigint unsigned NOT NULL,
  `sha256` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_feedback_anexo_mensagem` (`mensagem_id`),
  CONSTRAINT `fk_feedback_anexo_mensagem` FOREIGN KEY (`mensagem_id`) REFERENCES `feedback_mensagens` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feedback_anexos`
--

LOCK TABLES `feedback_anexos` WRITE;
/*!40000 ALTER TABLE `feedback_anexos` DISABLE KEYS */;
/*!40000 ALTER TABLE `feedback_anexos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feedback_mensagens`
--

DROP TABLE IF EXISTS `feedback_mensagens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `feedback_mensagens` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `feedback_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `autor_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `texto` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_feedback_mensagem_autor` (`autor_id`),
  KEY `idx_feedback_mensagem_thread` (`feedback_id`,`criado_em`),
  CONSTRAINT `fk_feedback_mensagem_autor` FOREIGN KEY (`autor_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `fk_feedback_mensagem_feedback` FOREIGN KEY (`feedback_id`) REFERENCES `feedbacks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feedback_mensagens`
--

LOCK TABLES `feedback_mensagens` WRITE;
/*!40000 ALTER TABLE `feedback_mensagens` DISABLE KEYS */;
/*!40000 ALTER TABLE `feedback_mensagens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feedback_participantes`
--

DROP TABLE IF EXISTS `feedback_participantes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `feedback_participantes` (
  `feedback_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `usuario_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `lida` tinyint(1) NOT NULL DEFAULT '0',
  `arquivado_em` datetime DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`feedback_id`,`usuario_id`),
  KEY `idx_feedback_participante_nao_lida` (`usuario_id`,`lida`,`arquivado_em`),
  CONSTRAINT `fk_feedback_participante_feedback` FOREIGN KEY (`feedback_id`) REFERENCES `feedbacks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_feedback_participante_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feedback_participantes`
--

LOCK TABLES `feedback_participantes` WRITE;
/*!40000 ALTER TABLE `feedback_participantes` DISABLE KEYS */;
/*!40000 ALTER TABLE `feedback_participantes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feedback_regras_comunicacao`
--

DROP TABLE IF EXISTS `feedback_regras_comunicacao`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `feedback_regras_comunicacao` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `cargo_origem` enum('ADMIN','VENDEDOR','VISTORIADOR','ANALISTA') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `escopo` enum('ADMIN','GESTOR_DIRETO','SUBORDINADOS','OUTROS_GESTORES','CARGO','TODOS_USUARIOS') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `cargo_destino` enum('ADMIN','VENDEDOR','VISTORIADOR','ANALISTA') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `chave_destino` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci GENERATED ALWAYS AS (coalesce(`cargo_destino`,_utf8mb4'')) STORED,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_feedback_regra` (`cargo_origem`,`escopo`,`chave_destino`),
  KEY `idx_feedback_regra_consulta` (`cargo_origem`,`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feedback_regras_comunicacao`
--

LOCK TABLES `feedback_regras_comunicacao` WRITE;
/*!40000 ALTER TABLE `feedback_regras_comunicacao` DISABLE KEYS */;
INSERT INTO `feedback_regras_comunicacao` (`id`, `cargo_origem`, `escopo`, `cargo_destino`, `ativo`, `criado_em`, `atualizado_em`) VALUES ('018eb4f5-d14c-4e58-9019-7c3996ce0a64','VISTORIADOR','TODOS_USUARIOS',NULL,1,'2026-07-23 06:11:41','2026-07-23 06:11:41'),('0e5e0160-310d-4bbd-aea6-86f5bae6ca6f','ANALISTA','GESTOR_DIRETO',NULL,1,'2026-07-23 06:11:41','2026-07-23 06:11:41'),('0e845ac5-2ea0-4fe4-95e7-c595dfeb0547','VENDEDOR','ADMIN',NULL,1,'2026-07-23 06:11:41','2026-07-23 06:11:41'),('190c898e-b702-4ac4-90a8-55fb17bea8a4','ANALISTA','ADMIN',NULL,1,'2026-07-23 06:11:41','2026-07-23 06:11:41'),('1f5bbb74-d3a9-4652-9b16-807341550e19','VENDEDOR','CARGO','VISTORIADOR',1,'2026-07-23 06:11:41','2026-07-23 06:11:41'),('27246751-ad4a-460c-b1c7-612db9bf3cae','VISTORIADOR','OUTROS_GESTORES',NULL,1,'2026-07-23 06:11:41','2026-07-23 06:11:41'),('287991b1-be6a-42d9-8eb2-370c7aab9633','VISTORIADOR','CARGO','VISTORIADOR',1,'2026-07-23 06:11:41','2026-07-23 06:11:41'),('3127893a-5a2b-4a93-bca1-ede90e577600','VENDEDOR','CARGO','VENDEDOR',1,'2026-07-23 06:11:41','2026-07-23 06:11:41'),('343a5089-c449-49ba-8095-82503a423183','ANALISTA','SUBORDINADOS',NULL,1,'2026-07-23 06:11:41','2026-07-23 06:11:41'),('4cf77a3d-2784-45e7-8901-6a69aea5f297','VISTORIADOR','SUBORDINADOS',NULL,1,'2026-07-23 06:11:41','2026-07-23 06:11:41'),('4e63f882-c47c-4c08-b658-f9fd55fe3c79','VENDEDOR','CARGO','ANALISTA',1,'2026-07-23 06:11:41','2026-07-23 06:11:41'),('4f2dc25f-1d00-4153-b65f-bff67b40ee26','VENDEDOR','OUTROS_GESTORES',NULL,1,'2026-07-23 06:11:41','2026-07-23 06:11:41'),('5a7b6e51-b296-4965-81d8-15f2a55171ff','VISTORIADOR','ADMIN',NULL,1,'2026-07-23 06:11:41','2026-07-23 06:11:41'),('5ad0d3ec-6383-4aae-9952-42547a00b1d5','ANALISTA','CARGO','VENDEDOR',1,'2026-07-23 06:11:41','2026-07-23 06:11:41'),('63cd5274-c125-4732-b759-17bc064b7b4e','VENDEDOR','TODOS_USUARIOS',NULL,1,'2026-07-23 06:11:41','2026-07-23 06:11:41'),('83634dc0-054a-4ef9-b7f3-ae003e8c54bc','VISTORIADOR','GESTOR_DIRETO',NULL,1,'2026-07-23 06:11:41','2026-07-23 06:11:41'),('8c2d0d21-60bf-4009-8eb2-18e32a4fca1d','VENDEDOR','GESTOR_DIRETO',NULL,1,'2026-07-23 06:11:41','2026-07-23 06:11:41'),('a47d026e-e66d-40fa-b207-2b580ca17894','ANALISTA','TODOS_USUARIOS',NULL,1,'2026-07-23 06:11:41','2026-07-23 06:11:41'),('a918465f-a502-4a7d-88a1-cffd17545573','ANALISTA','CARGO','ANALISTA',1,'2026-07-23 06:11:41','2026-07-23 06:11:41'),('b455f633-fd12-41c2-a74f-491ff155ce76','VISTORIADOR','CARGO','VENDEDOR',1,'2026-07-23 06:11:41','2026-07-23 06:11:41'),('d4401d21-a6c7-4011-a9ff-15e41e96e9d0','VISTORIADOR','CARGO','ANALISTA',1,'2026-07-23 06:11:41','2026-07-23 06:11:41'),('dca46c82-8c0d-407e-9a91-3fa221141689','VENDEDOR','SUBORDINADOS',NULL,1,'2026-07-23 06:11:41','2026-07-23 06:11:41'),('f0604b64-4c2d-4b94-9265-77c349dbf0ee','ANALISTA','CARGO','VISTORIADOR',1,'2026-07-23 06:11:41','2026-07-23 06:11:41'),('f41114f5-1ed5-40d9-a568-270fc759684c','ANALISTA','OUTROS_GESTORES',NULL,1,'2026-07-23 06:11:41','2026-07-23 06:11:41');
/*!40000 ALTER TABLE `feedback_regras_comunicacao` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feedbacks`
--

DROP TABLE IF EXISTS `feedbacks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `feedbacks` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `remetente_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `destinatario_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'NULL representa a caixa compartilhada dos administradores',
  `categoria` enum('DUVIDA','SUGESTAO','BUG','RECLAMACAO','ELOGIO') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `prioridade` enum('BAIXA','MEDIA','ALTA','URGENTE') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'MEDIA',
  `status` enum('ABERTO','RESPONDIDO','RESOLVIDO') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'ABERTO',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_feedback_destino` (`destinatario_id`,`atualizado_em`),
  KEY `idx_feedback_remetente` (`remetente_id`,`atualizado_em`),
  KEY `idx_feedback_filtros` (`status`,`prioridade`,`categoria`,`criado_em`),
  CONSTRAINT `fk_feedback_destinatario` FOREIGN KEY (`destinatario_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `fk_feedback_remetente` FOREIGN KEY (`remetente_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feedbacks`
--

LOCK TABLES `feedbacks` WRITE;
/*!40000 ALTER TABLE `feedbacks` DISABLE KEYS */;
/*!40000 ALTER TABLE `feedbacks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `financeiro_comprovantes`
--

DROP TABLE IF EXISTS `financeiro_comprovantes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `financeiro_comprovantes` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT (uuid()),
  `lancamento_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nome_original` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nome_arquivo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `caminho` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `mime_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tamanho` int unsigned NOT NULL DEFAULT '0',
  `criado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_financeiro_comprovantes_lancamento` (`lancamento_id`),
  CONSTRAINT `fk_financeiro_comprovantes_lancamento` FOREIGN KEY (`lancamento_id`) REFERENCES `financeiro_lancamentos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `financeiro_comprovantes`
--

LOCK TABLES `financeiro_comprovantes` WRITE;
/*!40000 ALTER TABLE `financeiro_comprovantes` DISABLE KEYS */;
/*!40000 ALTER TABLE `financeiro_comprovantes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `financeiro_contas_bancarias`
--

DROP TABLE IF EXISTS `financeiro_contas_bancarias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `financeiro_contas_bancarias` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nome` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `banco` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `agencia` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `conta` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_financeiro_contas_ativo` (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `financeiro_contas_bancarias`
--

LOCK TABLES `financeiro_contas_bancarias` WRITE;
/*!40000 ALTER TABLE `financeiro_contas_bancarias` DISABLE KEYS */;
/*!40000 ALTER TABLE `financeiro_contas_bancarias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `financeiro_historico_baixas`
--

DROP TABLE IF EXISTS `financeiro_historico_baixas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `financeiro_historico_baixas` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `lancamento_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `valor_pago` decimal(10,2) NOT NULL,
  `data_pagamento` date NOT NULL,
  `forma_pagamento` enum('a_vista','parcelado','boleto','pix') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `conta_bancaria_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `criado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_financeiro_baixas_lancamento` (`lancamento_id`),
  KEY `idx_financeiro_baixas_data` (`data_pagamento`),
  KEY `idx_financeiro_baixas_conta` (`conta_bancaria_id`),
  KEY `fk_financeiro_baixas_usuario` (`criado_por`),
  CONSTRAINT `fk_financeiro_baixas_conta` FOREIGN KEY (`conta_bancaria_id`) REFERENCES `financeiro_contas_bancarias` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_financeiro_baixas_lancamento` FOREIGN KEY (`lancamento_id`) REFERENCES `financeiro_lancamentos` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_financeiro_baixas_usuario` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `financeiro_historico_baixas`
--

LOCK TABLES `financeiro_historico_baixas` WRITE;
/*!40000 ALTER TABLE `financeiro_historico_baixas` DISABLE KEYS */;
/*!40000 ALTER TABLE `financeiro_historico_baixas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `financeiro_lancamentos`
--

DROP TABLE IF EXISTS `financeiro_lancamentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `financeiro_lancamentos` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT (uuid()),
  `cliente_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `escritorio_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '00000000-0000-4000-8000-000000000100',
  `responsavel_usuario_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `proposta_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tipo` enum('RECEITA','DESPESA') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `descricao` varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `valor_original` decimal(10,2) NOT NULL,
  `saldo_devedor` decimal(10,2) NOT NULL,
  `status` enum('PENDENTE','PARCIAL','PAGO','CANCELADO') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'PENDENTE',
  `frequencia` enum('unica','mensal','trimestral','anual') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'unica',
  `data_vencimento` date DEFAULT NULL,
  `data` date DEFAULT NULL,
  `categoria` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `observacoes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `criado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `criado_por` (`criado_por`),
  KEY `fk_financeiro_cliente` (`cliente_id`),
  KEY `idx_financeiro_escritorio_data` (`escritorio_id`,`data`),
  KEY `idx_financeiro_responsavel` (`responsavel_usuario_id`),
  KEY `idx_financeiro_proposta` (`proposta_id`),
  CONSTRAINT `financeiro_lancamentos_ibfk_1` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_financeiro_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  CONSTRAINT `fk_financeiro_escritorio` FOREIGN KEY (`escritorio_id`) REFERENCES `escritorios` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_financeiro_proposta` FOREIGN KEY (`proposta_id`) REFERENCES `propostas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_financeiro_responsavel` FOREIGN KEY (`responsavel_usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `financeiro_lancamentos`
--

LOCK TABLES `financeiro_lancamentos` WRITE;
/*!40000 ALTER TABLE `financeiro_lancamentos` DISABLE KEYS */;
INSERT INTO `financeiro_lancamentos` VALUES ('2449f8d0-865e-11f1-a50d-aa44e656c57d','e82942df-63da-4093-82b7-c2849fe3634e','3332440d-dc03-4ab1-8485-8805d098dd6b',NULL,'1e08dd85-865e-11f1-a50d-aa44e656c57d','RECEITA','Referente à Proposta Comercial nº AM-ORC-1/26',12800.00,12800.00,12800.00,'PENDENTE','unica','2026-08-07','2026-07-23','SERVIÇOS','Lançamento gerado automaticamente após aprovação interna da proposta.',1,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-07-23 06:17:11','2026-07-23 06:17:11'),('92037b39-871a-11f1-a50d-aa44e656c57d','e82942df-63da-4093-82b7-c2849fe3634e','3332440d-dc03-4ab1-8485-8805d098dd6b','ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','8b4f4f9b-871a-11f1-a50d-aa44e656c57d','RECEITA','Referente à Proposta Comercial nº AM-ORC-3/26',13000.00,13000.00,13000.00,'PENDENTE','unica','2026-08-08','2026-07-24','SERVIÇOS','Lançamento gerado automaticamente após aprovação interna da proposta.',1,'ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','2026-07-24 04:46:01','2026-07-24 04:46:01'),('c381404d-8666-11f1-a50d-aa44e656c57d','e82942df-63da-4093-82b7-c2849fe3634e','3332440d-dc03-4ab1-8485-8805d098dd6b','ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','c07d458c-8666-11f1-a50d-aa44e656c57d','RECEITA','Referente à Proposta Comercial nº AM-ORC-2/26',13000.00,13000.00,13000.00,'PENDENTE','unica','2026-08-07','2026-07-23','SERVIÇOS','Lançamento gerado automaticamente após aprovação interna da proposta.',1,'ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','2026-07-23 07:18:55','2026-07-23 07:18:55');
/*!40000 ALTER TABLE `financeiro_lancamentos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `financeiro_metas_mensais`
--

DROP TABLE IF EXISTS `financeiro_metas_mensais`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `financeiro_metas_mensais` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `competencia` date NOT NULL,
  `escritorio_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `usuario_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `valor` decimal(12,2) NOT NULL DEFAULT '0.00',
  `mensagem` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_meta_escritorio_usuario_competencia` (`escritorio_id`,`usuario_id`,`competencia`),
  KEY `idx_metas_competencia` (`competencia`),
  KEY `idx_metas_usuario` (`usuario_id`),
  CONSTRAINT `fk_metas_escritorio` FOREIGN KEY (`escritorio_id`) REFERENCES `escritorios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_metas_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `financeiro_metas_mensais`
--

LOCK TABLES `financeiro_metas_mensais` WRITE;
/*!40000 ALTER TABLE `financeiro_metas_mensais` DISABLE KEYS */;
INSERT INTO `financeiro_metas_mensais` VALUES ('2a3f571a-55e9-4940-832a-6708094982c7','2026-07-01','9141f8a5-1d4c-4eba-a749-f5cb040b1630',NULL,150000.00,'notas manaus','2026-07-23 06:11:07','2026-07-23 06:11:07'),('a5ba91ef-545f-4b6c-82a7-fc807d6aea9a','2026-07-01','3332440d-dc03-4ab1-8485-8805d098dd6b',NULL,190000.00,'notas belem','2026-07-23 06:11:07','2026-07-23 06:11:07');
/*!40000 ALTER TABLE `financeiro_metas_mensais` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `logs_atividade`
--

DROP TABLE IF EXISTS `logs_atividade`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `logs_atividade` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `acao` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `descricao` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `logs_atividade`
--

LOCK TABLES `logs_atividade` WRITE;
/*!40000 ALTER TABLE `logs_atividade` DISABLE KEYS */;
INSERT INTO `logs_atividade` VALUES (1,'dd121661-feb4-42f6-895a-68eb0608d1e4','proprietario_criado','Proprietário \'Rosano Silva De Souza\' criado.','172.23.0.1','2026-07-23 03:16:15'),(2,'dd121661-feb4-42f6-895a-68eb0608d1e4','proposta_criada','Proposta AM-ORC-1/26 criada para cliente \'Rosano Silva De Souza\'. Subtotal: R$ 12.800,00 | Desconto: 0% | Entrada: R$ 0,00 | Total: R$ 12.800,00','172.23.0.1','2026-07-23 03:17:01'),(3,'dd121661-feb4-42f6-895a-68eb0608d1e4','proposta_aprovada_assinatura_manual','Proposta AM-ORC-1/26 autorizada internamente sem assinatura digital por Autorização interna - admin.','172.23.0.1','2026-07-23 03:17:11'),(4,'dd121661-feb4-42f6-895a-68eb0608d1e4','agendamento_editado','Agendamento ID: 244a3876-865e-11f1-a50d-aa44e656c57d atualizado.','172.23.0.1','2026-07-23 03:17:45'),(5,'d2a16613-dfa4-4948-8de4-8c802abdf394','relatorio_salvo','Relatorio tecnico AM-REL-V-1/26 salvo para agendamento ID: 244a3876-865e-11f1-a50d-aa44e656c57d. Status: PENDENTE.','172.23.0.1','2026-07-23 03:19:27'),(6,'d2a16613-dfa4-4948-8de4-8c802abdf394','relatorio_salvo','Relatorio tecnico AM-REL-V-1/26 salvo para agendamento ID: 244a3876-865e-11f1-a50d-aa44e656c57d. Status: AGUARDANDO_APROVACAO.','172.23.0.1','2026-07-23 03:41:24'),(7,'d2a16613-dfa4-4948-8de4-8c802abdf394','relatorio_salvo','Relatorio tecnico AM-REL-V-1/26 salvo para agendamento ID: 244a3876-865e-11f1-a50d-aa44e656c57d. Status: AGUARDANDO_APROVACAO.','172.23.0.1','2026-07-23 03:42:09'),(8,'dd121661-feb4-42f6-895a-68eb0608d1e4','certificado_csn_criado','Certificado AM-CSN-1/26 (Condicional) - LANCHA TESTE AMAZÔNIA 061558','172.23.0.1','2026-07-23 03:54:09'),(9,'dd121661-feb4-42f6-895a-68eb0608d1e4','documento_aprovado_eletronicamente','Certificado CSN e7501710-9765-4ef1-a09d-0e0c77f5e9fe aprovado. Hash final: c733d5a9a93dea00e20f2b441835ddedcee5caf8b5a06cfac9671b70311449c1','172.23.0.1','2026-07-23 03:57:00'),(10,'dd121661-feb4-42f6-895a-68eb0608d1e4','certificado_csn_criado','Certificado AM-CSN-2/26 (Condicional) - LANCHA TESTE AMAZÔNIA 061558','172.23.0.1','2026-07-23 03:59:50'),(11,'ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','proposta_criada','Proposta AM-ORC-2/26 criada para cliente \'Rosano Silva De Souza\'. Subtotal: R$ 13.000,00 | Desconto: 0% | Entrada: R$ 0,00 | Total: R$ 13.000,00','172.23.0.1','2026-07-23 04:18:49'),(12,'ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','proposta_aprovada_assinatura_manual','Proposta AM-ORC-2/26 autorizada internamente sem assinatura digital por Autorização interna - any.','172.23.0.1','2026-07-23 04:18:55'),(13,'ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','agendamento_editado','Agendamento ID: c381729a-8666-11f1-a50d-aa44e656c57d atualizado.','172.23.0.1','2026-07-23 04:20:26'),(14,'d2a16613-dfa4-4948-8de4-8c802abdf394','relatorio_salvo','Relatorio tecnico AM-REL-AP-1/26 salvo para agendamento ID: c381729a-8666-11f1-a50d-aa44e656c57d. Status: AGUARDANDO_APROVACAO.','172.23.0.1','2026-07-23 04:21:30'),(15,'ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','proposta_criada','Proposta AM-ORC-3/26 criada para cliente \'Rosano Silva De Souza\'. Subtotal: R$ 13.000,00 | Desconto: 0% | Entrada: R$ 0,00 | Total: R$ 13.000,00','172.23.0.1','2026-07-24 01:45:50'),(16,'ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','proposta_aprovada_assinatura_manual','Proposta AM-ORC-3/26 autorizada internamente sem assinatura digital por Autorização interna - any.','172.23.0.1','2026-07-24 01:46:01'),(17,'ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','agendamento_editado','Agendamento ID: 9203af31-871a-11f1-a50d-aa44e656c57d atualizado.','172.23.0.1','2026-07-24 01:47:00'),(18,'d2a16613-dfa4-4948-8de4-8c802abdf394','relatorio_salvo','Relatorio tecnico AM-REL-AP-2/26 salvo para agendamento ID: 9203af31-871a-11f1-a50d-aa44e656c57d. Status: PENDENTE.','172.23.0.1','2026-07-24 02:06:03'),(19,'d2a16613-dfa4-4948-8de4-8c802abdf394','relatorio_salvo','Relatorio tecnico AM-REL-AP-2/26 salvo para agendamento ID: 9203af31-871a-11f1-a50d-aa44e656c57d. Status: AGUARDANDO_APROVACAO.','172.23.0.1','2026-07-24 02:07:29');
/*!40000 ALTER TABLE `logs_atividade` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `matriz_normativa_documentos`
--

DROP TABLE IF EXISTS `matriz_normativa_documentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `matriz_normativa_documentos` (
  `id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `versao_id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `documento` enum('LC','LCEC','LA','LR','CSN','CNBL','CNARQ') COLLATE utf8mb4_general_ci NOT NULL,
  `classe` enum('EC1','EC2') COLLATE utf8mb4_general_ci NOT NULL,
  `aplicavel` tinyint(1) NOT NULL,
  `vigencia_inicio` date DEFAULT NULL,
  `condicao_json` json DEFAULT NULL,
  `fundamento` varchar(500) COLLATE utf8mb4_general_ci NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_matriz_documento` (`versao_id`,`documento`,`classe`),
  CONSTRAINT `fk_matriz_documento_versao` FOREIGN KEY (`versao_id`) REFERENCES `matriz_normativa_versoes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `matriz_normativa_documentos`
--

LOCK TABLES `matriz_normativa_documentos` WRITE;
/*!40000 ALTER TABLE `matriz_normativa_documentos` DISABLE KEYS */;
INSERT INTO `matriz_normativa_documentos` VALUES ('61402745-8720-11f1-a50d-aa44e656c57d','b6db69f4-69dd-4ad4-a7cb-202000000001','LC','EC1',1,NULL,NULL,'NORMAM-202, Cap??tulo 3','2026-07-24 05:27:36'),('614028ea-8720-11f1-a50d-aa44e656c57d','b6db69f4-69dd-4ad4-a7cb-202000000001','LCEC','EC1',1,NULL,NULL,'NORMAM-202, Cap??tulo 3','2026-07-24 05:27:36'),('61402a73-8720-11f1-a50d-aa44e656c57d','b6db69f4-69dd-4ad4-a7cb-202000000001','LA','EC1',1,NULL,NULL,'NORMAM-202, Cap??tulo 3','2026-07-24 05:27:36'),('61402b38-8720-11f1-a50d-aa44e656c57d','b6db69f4-69dd-4ad4-a7cb-202000000001','LR','EC1',1,NULL,NULL,'NORMAM-202, Cap??tulo 3','2026-07-24 05:27:36'),('61402b9e-8720-11f1-a50d-aa44e656c57d','b6db69f4-69dd-4ad4-a7cb-202000000001','LC','EC2',0,'2026-11-01','{\"ab_max\": 50, \"ab_min\": 20, \"excecao\": \"REBOCADOR_OU_EMPURRADOR\"}','EC2 dispensada; exce????o de rebocador/empurrador AB 20 a 50 a partir de 01/11/2026','2026-07-24 05:27:36'),('61402c7a-8720-11f1-a50d-aa44e656c57d','b6db69f4-69dd-4ad4-a7cb-202000000001','LCEC','EC2',0,'2026-11-01','{\"ab_max\": 50, \"ab_min\": 20, \"excecao\": \"REBOCADOR_OU_EMPURRADOR\"}','EC2 dispensada; exce????o de rebocador/empurrador AB 20 a 50 a partir de 01/11/2026','2026-07-24 05:27:36'),('61402ce9-8720-11f1-a50d-aa44e656c57d','b6db69f4-69dd-4ad4-a7cb-202000000001','LA','EC2',0,'2026-11-01','{\"ab_max\": 50, \"ab_min\": 20, \"excecao\": \"REBOCADOR_OU_EMPURRADOR\"}','EC2 dispensada; exce????o de rebocador/empurrador AB 20 a 50 a partir de 01/11/2026','2026-07-24 05:27:36'),('61402d60-8720-11f1-a50d-aa44e656c57d','b6db69f4-69dd-4ad4-a7cb-202000000001','LR','EC2',0,'2026-11-01','{\"ab_max\": 50, \"ab_min\": 20, \"excecao\": \"REBOCADOR_OU_EMPURRADOR\"}','EC2 dispensada; exce????o de rebocador/empurrador AB 20 a 50 a partir de 01/11/2026','2026-07-24 05:27:36');
/*!40000 ALTER TABLE `matriz_normativa_documentos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `matriz_normativa_versoes`
--

DROP TABLE IF EXISTS `matriz_normativa_versoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `matriz_normativa_versoes` (
  `id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `norma_codigo` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `revisao` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `vigencia_inicio` date NOT NULL,
  `vigencia_fim` date DEFAULT NULL,
  `portaria_reconhecimento` varchar(180) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fonte_url` varchar(500) COLLATE utf8mb4_general_ci NOT NULL,
  `ativa` tinyint(1) NOT NULL DEFAULT '1',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_matriz_norma_revisao` (`norma_codigo`,`revisao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `matriz_normativa_versoes`
--

LOCK TABLES `matriz_normativa_versoes` WRITE;
/*!40000 ALTER TABLE `matriz_normativa_versoes` DISABLE KEYS */;
INSERT INTO `matriz_normativa_versoes` VALUES ('b6db69f4-69dd-4ad4-a7cb-202000000001','NORMAM-202','REV.1','2025-01-01',NULL,'Escopo condicionado ?? Portaria/Acordo de Reconhecimento vigente','https://www.marinha.mil.br/sites/default/files/atos-normativos/dpc/normam/normam-202.pdf',1,'2026-07-24 05:27:36');
/*!40000 ALTER TABLE `matriz_normativa_versoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notificacoes`
--

DROP TABLE IF EXISTS `notificacoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notificacoes` (
  `id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `usuario_id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `evento` varchar(60) COLLATE utf8mb4_general_ci NOT NULL,
  `titulo` varchar(180) COLLATE utf8mb4_general_ci NOT NULL,
  `mensagem` varchar(500) COLLATE utf8mb4_general_ci NOT NULL,
  `referencia_tipo` varchar(60) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `referencia_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `url` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lida_em` datetime DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notificacoes_usuario` (`usuario_id`,`lida_em`,`criado_em`),
  CONSTRAINT `fk_notificacoes_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notificacoes`
--

LOCK TABLES `notificacoes` WRITE;
/*!40000 ALTER TABLE `notificacoes` DISABLE KEYS */;
/*!40000 ALTER TABLE `notificacoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ordens_servico`
--

DROP TABLE IF EXISTS `ordens_servico`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero` (`numero`),
  UNIQUE KEY `agendamento_id` (`agendamento_id`),
  KEY `proposta_id` (`proposta_id`),
  KEY `embarcacao_id` (`embarcacao_id`),
  KEY `cliente_id` (`cliente_id`),
  KEY `vistoriador_id` (`vistoriador_id`),
  KEY `status` (`status`),
  KEY `data_vistoria` (`data_vistoria`),
  KEY `criado_por` (`criado_por`),
  CONSTRAINT `ordens_servico_ibfk_1` FOREIGN KEY (`agendamento_id`) REFERENCES `agendamentos` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `ordens_servico_ibfk_2` FOREIGN KEY (`proposta_id`) REFERENCES `propostas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ordens_servico_ibfk_3` FOREIGN KEY (`embarcacao_id`) REFERENCES `embarcacoes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `ordens_servico_ibfk_4` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `ordens_servico_ibfk_5` FOREIGN KEY (`vistoriador_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `ordens_servico_ibfk_6` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ordens_servico`
--

LOCK TABLES `ordens_servico` WRITE;
/*!40000 ALTER TABLE `ordens_servico` DISABLE KEYS */;
/*!40000 ALTER TABLE `ordens_servico` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `portal_auditoria`
--

DROP TABLE IF EXISTS `portal_auditoria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `portal_auditoria` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `cliente_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `perfil` enum('proprietario','despachante') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `evento` enum('LOGIN_SUCESSO','LOGIN_FALHA','VISUALIZACAO','DOWNLOAD','UPLOAD_ANALISE') COLLATE utf8mb4_general_ci NOT NULL,
  `embarcacao_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `documento_tipo` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `documento_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sucesso` tinyint(1) NOT NULL DEFAULT '1',
  `detalhe` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_agent` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_portal_auditoria_cliente` (`cliente_id`,`criado_em`),
  KEY `idx_portal_auditoria_documento` (`documento_tipo`,`documento_id`,`criado_em`),
  KEY `fk_portal_auditoria_embarcacao` (`embarcacao_id`),
  CONSTRAINT `fk_portal_auditoria_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_portal_auditoria_embarcacao` FOREIGN KEY (`embarcacao_id`) REFERENCES `embarcacoes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `portal_auditoria`
--

LOCK TABLES `portal_auditoria` WRITE;
/*!40000 ALTER TABLE `portal_auditoria` DISABLE KEYS */;
INSERT INTO `portal_auditoria` VALUES (1,'e82942df-63da-4093-82b7-c2849fe3634e',NULL,'LOGIN_SUCESSO',NULL,NULL,NULL,1,'Perfil: proprietario','172.23.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-07-23 14:09:58'),(2,'e82942df-63da-4093-82b7-c2849fe3634e','proprietario','VISUALIZACAO','09542979-d78e-4095-8ee2-a01e3e7efa07','rel_vistoria','4f3bd30c-44b9-47f4-800d-25601773e329',1,'Artefato oficial assinado.','172.23.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-07-23 14:10:13'),(3,'e82942df-63da-4093-82b7-c2849fe3634e','proprietario','VISUALIZACAO','09542979-d78e-4095-8ee2-a01e3e7efa07','csn','e7501710-9765-4ef1-a09d-0e0c77f5e9fe',1,'Artefato oficial assinado.','172.23.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-07-23 14:10:38');
/*!40000 ALTER TABLE `portal_auditoria` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `propostas`
--

DROP TABLE IF EXISTS `propostas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `propostas` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT (uuid()),
  `numero` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `cliente_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `armador_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `operador_nome` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `responsavel_fechamento_nome` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `responsavel_fechamento_telefone` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `data_emissao` date NOT NULL,
  `data_validade` date DEFAULT NULL,
  `parcelas` tinyint unsigned NOT NULL DEFAULT '3',
  `forma_pagamento` enum('a_vista','parcelado','boleto','pix') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'parcelado',
  `valor_total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `valor_entrada` decimal(12,2) NOT NULL DEFAULT '0.00',
  `desconto_percentual` decimal(5,2) NOT NULL DEFAULT '0.00',
  `desconto_valor` decimal(12,2) NOT NULL DEFAULT '0.00',
  `observacoes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `status` enum('rascunho','enviada','aprovada','recusada','cancelada','assinada') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'rascunho',
  `criado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `escritorio_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '00000000-0000-4000-8000-000000000100',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `token_assinatura` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `assinado` tinyint(1) DEFAULT '0',
  `assinatura_imagem` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `assinatura_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `assinatura_em` datetime DEFAULT NULL,
  `assinante_nome` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `assinante_documento` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `assinatura_ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero` (`numero`),
  KEY `cliente_id` (`cliente_id`),
  KEY `status` (`status`),
  KEY `criado_por` (`criado_por`),
  KEY `idx_propostas_armador_id` (`armador_id`),
  KEY `idx_propostas_escritorio` (`escritorio_id`),
  CONSTRAINT `fk_propostas_armador` FOREIGN KEY (`armador_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_propostas_escritorio` FOREIGN KEY (`escritorio_id`) REFERENCES `escritorios` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `propostas_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `propostas_ibfk_2` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `propostas`
--

LOCK TABLES `propostas` WRITE;
/*!40000 ALTER TABLE `propostas` DISABLE KEYS */;
INSERT INTO `propostas` VALUES ('1e08dd85-865e-11f1-a50d-aa44e656c57d','AM-ORC-1/26','e82942df-63da-4093-82b7-c2849fe3634e',NULL,NULL,NULL,NULL,'2026-07-23','2026-08-22',3,'parcelado',12800.00,0.00,0.00,0.00,NULL,'assinada','dd121661-feb4-42f6-895a-68eb0608d1e4','3332440d-dc03-4ab1-8485-8805d098dd6b','2026-07-23 06:17:01','2026-07-23 06:17:11','ccc101d9434e0de022f37a39a0ddac616a61b1dd6508b',1,NULL,NULL,'2026-07-23 03:17:11','Autorização interna - admin','Autorização interna sem assinatura digital','172.23.0.1'),('8b4f4f9b-871a-11f1-a50d-aa44e656c57d','AM-ORC-3/26','e82942df-63da-4093-82b7-c2849fe3634e',NULL,NULL,NULL,NULL,'2026-07-24','2026-08-23',3,'parcelado',13000.00,0.00,0.00,0.00,NULL,'assinada','ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','3332440d-dc03-4ab1-8485-8805d098dd6b','2026-07-24 04:45:50','2026-07-24 04:46:01','b9372c6b045777efcd1746bc819468486a62edfe204fa',1,NULL,NULL,'2026-07-24 01:46:01','Autorização interna - any','Autorização interna sem assinatura digital','172.23.0.1'),('c07d458c-8666-11f1-a50d-aa44e656c57d','AM-ORC-2/26','e82942df-63da-4093-82b7-c2849fe3634e',NULL,NULL,NULL,NULL,'2026-07-23','2026-08-22',3,'parcelado',13000.00,0.00,0.00,0.00,NULL,'assinada','ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','3332440d-dc03-4ab1-8485-8805d098dd6b','2026-07-23 07:18:49','2026-07-23 07:18:54','289bd6362d7e4478c940974fd6966c2c6a61c059e5d82',1,NULL,NULL,'2026-07-23 04:18:54','Autorização interna - any','Autorização interna sem assinatura digital','172.23.0.1');
/*!40000 ALTER TABLE `propostas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `propostas_embarcacoes`
--

DROP TABLE IF EXISTS `propostas_embarcacoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `propostas_embarcacoes` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT (uuid()),
  `proposta_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `embarcacao_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `proposta_embarcacao` (`proposta_id`,`embarcacao_id`),
  KEY `embarcacao_id` (`embarcacao_id`),
  CONSTRAINT `propostas_embarcacoes_ibfk_1` FOREIGN KEY (`proposta_id`) REFERENCES `propostas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `propostas_embarcacoes_ibfk_2` FOREIGN KEY (`embarcacao_id`) REFERENCES `embarcacoes` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `propostas_embarcacoes`
--

LOCK TABLES `propostas_embarcacoes` WRITE;
/*!40000 ALTER TABLE `propostas_embarcacoes` DISABLE KEYS */;
INSERT INTO `propostas_embarcacoes` VALUES ('1e0902a3-865e-11f1-a50d-aa44e656c57d','1e08dd85-865e-11f1-a50d-aa44e656c57d','09542979-d78e-4095-8ee2-a01e3e7efa07'),('8b4f6e19-871a-11f1-a50d-aa44e656c57d','8b4f4f9b-871a-11f1-a50d-aa44e656c57d','09542979-d78e-4095-8ee2-a01e3e7efa07'),('c07d63cd-8666-11f1-a50d-aa44e656c57d','c07d458c-8666-11f1-a50d-aa44e656c57d','09542979-d78e-4095-8ee2-a01e3e7efa07');
/*!40000 ALTER TABLE `propostas_embarcacoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `propostas_servicos`
--

DROP TABLE IF EXISTS `propostas_servicos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `propostas_servicos` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT (uuid()),
  `proposta_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `servico_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `embarcacao_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `preco_aplicado` decimal(12,2) NOT NULL DEFAULT '0.00',
  `quantidade` tinyint unsigned NOT NULL DEFAULT '1',
  `subtotal` decimal(12,2) GENERATED ALWAYS AS ((`preco_aplicado` * `quantidade`)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `proposta_embarcacao_servico` (`proposta_id`,`embarcacao_id`,`servico_id`),
  KEY `servico_id` (`servico_id`),
  KEY `idx_propserv_emb` (`embarcacao_id`),
  CONSTRAINT `propostas_servicos_ibfk_1` FOREIGN KEY (`proposta_id`) REFERENCES `propostas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `propostas_servicos_ibfk_2` FOREIGN KEY (`servico_id`) REFERENCES `servicos` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `propostas_servicos_ibfk_3` FOREIGN KEY (`embarcacao_id`) REFERENCES `embarcacoes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `propostas_servicos`
--

LOCK TABLES `propostas_servicos` WRITE;
/*!40000 ALTER TABLE `propostas_servicos` DISABLE KEYS */;
INSERT INTO `propostas_servicos` (`id`, `proposta_id`, `servico_id`, `embarcacao_id`, `preco_aplicado`, `quantidade`) VALUES ('1e091148-865e-11f1-a50d-aa44e656c57d','1e08dd85-865e-11f1-a50d-aa44e656c57d','a1d98e55-6ebc-11f1-86ce-7e17ff5f90bf','09542979-d78e-4095-8ee2-a01e3e7efa07',3500.00,1),('1e0918e8-865e-11f1-a50d-aa44e656c57d','1e08dd85-865e-11f1-a50d-aa44e656c57d','a1d98d8e-6ebc-11f1-86ce-7e17ff5f90bf','09542979-d78e-4095-8ee2-a01e3e7efa07',3500.00,1),('1e0920b2-865e-11f1-a50d-aa44e656c57d','1e08dd85-865e-11f1-a50d-aa44e656c57d','a1d991e9-6ebc-11f1-86ce-7e17ff5f90bf','09542979-d78e-4095-8ee2-a01e3e7efa07',3000.00,1),('1e092856-865e-11f1-a50d-aa44e656c57d','1e08dd85-865e-11f1-a50d-aa44e656c57d','a1d98eaf-6ebc-11f1-86ce-7e17ff5f90bf','09542979-d78e-4095-8ee2-a01e3e7efa07',2800.00,1),('8b4f9951-871a-11f1-a50d-aa44e656c57d','8b4f4f9b-871a-11f1-a50d-aa44e656c57d','a1d98d8e-6ebc-11f1-86ce-7e17ff5f90bf','09542979-d78e-4095-8ee2-a01e3e7efa07',3500.00,1),('8b4fa1b8-871a-11f1-a50d-aa44e656c57d','8b4f4f9b-871a-11f1-a50d-aa44e656c57d','a1d98e55-6ebc-11f1-86ce-7e17ff5f90bf','09542979-d78e-4095-8ee2-a01e3e7efa07',3500.00,1),('8b4fa897-871a-11f1-a50d-aa44e656c57d','8b4f4f9b-871a-11f1-a50d-aa44e656c57d','a1d98eaf-6ebc-11f1-86ce-7e17ff5f90bf','09542979-d78e-4095-8ee2-a01e3e7efa07',2800.00,1),('8b4fb164-871a-11f1-a50d-aa44e656c57d','8b4f4f9b-871a-11f1-a50d-aa44e656c57d','a1d98ef1-6ebc-11f1-86ce-7e17ff5f90bf','09542979-d78e-4095-8ee2-a01e3e7efa07',3200.00,1),('c07d767f-8666-11f1-a50d-aa44e656c57d','c07d458c-8666-11f1-a50d-aa44e656c57d','a1d98d8e-6ebc-11f1-86ce-7e17ff5f90bf','09542979-d78e-4095-8ee2-a01e3e7efa07',3500.00,1),('c07d8238-8666-11f1-a50d-aa44e656c57d','c07d458c-8666-11f1-a50d-aa44e656c57d','a1d98e55-6ebc-11f1-86ce-7e17ff5f90bf','09542979-d78e-4095-8ee2-a01e3e7efa07',3500.00,1),('c07d89e4-8666-11f1-a50d-aa44e656c57d','c07d458c-8666-11f1-a50d-aa44e656c57d','a1d98eaf-6ebc-11f1-86ce-7e17ff5f90bf','09542979-d78e-4095-8ee2-a01e3e7efa07',2800.00,1),('c07d9496-8666-11f1-a50d-aa44e656c57d','c07d458c-8666-11f1-a50d-aa44e656c57d','a1d98ef1-6ebc-11f1-86ce-7e17ff5f90bf','09542979-d78e-4095-8ee2-a01e3e7efa07',3200.00,1);
/*!40000 ALTER TABLE `propostas_servicos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `protocolo_aceites`
--

DROP TABLE IF EXISTS `protocolo_aceites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `protocolo_aceites` (
  `id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `movimentacao_id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `token_hash` char(64) COLLATE utf8mb4_general_ci NOT NULL,
  `expira_em` datetime NOT NULL,
  `nome` varchar(180) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `documento_mascarado` varchar(40) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `termo_aceito` tinyint(1) NOT NULL DEFAULT '0',
  `ip` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `aceito_em` datetime DEFAULT NULL,
  `criado_por` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_protocolo_aceite_token` (`token_hash`),
  UNIQUE KEY `uk_protocolo_aceite_mov` (`movimentacao_id`),
  KEY `fk_protocolo_aceite_criador` (`criado_por`),
  CONSTRAINT `fk_protocolo_aceite_criador` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `fk_protocolo_aceite_mov` FOREIGN KEY (`movimentacao_id`) REFERENCES `protocolo_movimentacoes` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `protocolo_aceites`
--

LOCK TABLES `protocolo_aceites` WRITE;
/*!40000 ALTER TABLE `protocolo_aceites` DISABLE KEYS */;
/*!40000 ALTER TABLE `protocolo_aceites` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `protocolo_auditoria`
--

DROP TABLE IF EXISTS `protocolo_auditoria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `protocolo_auditoria` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `dossie_id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `movimentacao_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `evento` varchar(80) COLLATE utf8mb4_general_ci NOT NULL,
  `usuario_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `perfil` varchar(40) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ip` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estado_anterior` varchar(60) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estado_novo` varchar(60) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `detalhe` text COLLATE utf8mb4_general_ci,
  `hash_referencia` char(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_protocolo_auditoria` (`dossie_id`,`criado_em`),
  KEY `fk_protocolo_auditoria_mov` (`movimentacao_id`),
  KEY `fk_protocolo_auditoria_usuario` (`usuario_id`),
  CONSTRAINT `fk_protocolo_auditoria_dossie` FOREIGN KEY (`dossie_id`) REFERENCES `protocolo_dossies` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_protocolo_auditoria_mov` FOREIGN KEY (`movimentacao_id`) REFERENCES `protocolo_movimentacoes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_protocolo_auditoria_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `protocolo_auditoria`
--

LOCK TABLES `protocolo_auditoria` WRITE;
/*!40000 ALTER TABLE `protocolo_auditoria` DISABLE KEYS */;
INSERT INTO `protocolo_auditoria` VALUES (6,'677dc7fe-29e1-42be-a52e-6fcefcbeb374',NULL,'DOSSIE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','172.23.0.1',NULL,'EM_PREPARACAO','AM-PROT-1/26',NULL,'2026-07-24 12:13:24'),(7,'dd693be9-fcef-418d-8e58-530dc3522a25',NULL,'DOSSIE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','172.23.0.1',NULL,'EM_PREPARACAO','AM-PROT-2/26',NULL,'2026-07-24 16:24:25');
/*!40000 ALTER TABLE `protocolo_auditoria` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `protocolo_catalogo_documentos`
--

DROP TABLE IF EXISTS `protocolo_catalogo_documentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `protocolo_catalogo_documentos` (
  `id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `codigo` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `categoria` enum('ANALISE_PLANOS','VISTORIA','INSCRICAO','CERTIFICADOS','PROPRIEDADE','RESPONSABILIDADE_TECNICA','DOCUMENTOS_PESSOAIS','OUTROS') COLLATE utf8mb4_general_ci NOT NULL,
  `nome` varchar(180) COLLATE utf8mb4_general_ci NOT NULL,
  `contexto` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `norma_referencia` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `ordem` int NOT NULL DEFAULT '0',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_protocolo_catalogo_codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `protocolo_catalogo_documentos`
--

LOCK TABLES `protocolo_catalogo_documentos` WRITE;
/*!40000 ALTER TABLE `protocolo_catalogo_documentos` DISABLE KEYS */;
INSERT INTO `protocolo_catalogo_documentos` VALUES ('1c5c28cc-8757-11f1-a50d-aa44e656c57d','REQ_INTERESSADO','ANALISE_PLANOS','Requerimento do interessado','NORMAM-202','NORMAM-202/DPC',1,10,'2026-07-24 11:59:23'),('1c5c2a30-8757-11f1-a50d-aa44e656c57d','ART','RESPONSABILIDADE_TECNICA','Anotação de Responsabilidade Técnica (ART)','NORMAM-202','NORMAM-202/DPC',1,20,'2026-07-24 11:59:23'),('1c5c2aad-8757-11f1-a50d-aa44e656c57d','MEMORIAL_DESCRITIVO','ANALISE_PLANOS','Memorial descritivo','LC_LCEC','NORMAM-202/DPC',1,30,'2026-07-24 11:59:23'),('1c5c2afc-8757-11f1-a50d-aa44e656c57d','PLANO_ARRANJO_GERAL','ANALISE_PLANOS','Plano de arranjo geral','LC_LCEC','NORMAM-202/DPC',1,40,'2026-07-24 11:59:23'),('1c5c2b40-8757-11f1-a50d-aa44e656c57d','PLANO_LINHAS','ANALISE_PLANOS','Plano de linhas','LC_LCEC','NORMAM-202/DPC',1,50,'2026-07-24 11:59:23'),('1c5c2b87-8757-11f1-a50d-aa44e656c57d','PLANO_SEGURANCA','ANALISE_PLANOS','Plano de segurança','LC_LCEC','NORMAM-202/DPC',1,60,'2026-07-24 11:59:23'),('1c5c2bc8-8757-11f1-a50d-aa44e656c57d','CALCULOS_ESTABILIDADE','ANALISE_PLANOS','Cálculos e folheto de estabilidade','LC_LCEC','NORMAM-202/DPC',1,70,'2026-07-24 11:59:23'),('1c5c2c08-8757-11f1-a50d-aa44e656c57d','RELATORIO_VISTORIA','VISTORIA','Relatório técnico de vistoria','VISTORIA','NORMAM-202/DPC',1,80,'2026-07-24 11:59:23'),('1c5c2c4b-8757-11f1-a50d-aa44e656c57d','CERTIFICADO_EXISTENTE','CERTIFICADOS','Certificado ou licença existente','DOCUMENTACAO',NULL,1,90,'2026-07-24 11:59:23'),('1c5c2c95-8757-11f1-a50d-aa44e656c57d','TIE_TIEM','INSCRICAO','TIE/TIEM ou documento de inscrição','INSCRICAO',NULL,1,100,'2026-07-24 11:59:23'),('1c5c2cd6-8757-11f1-a50d-aa44e656c57d','DOCUMENTO_PROPRIEDADE','PROPRIEDADE','Documento de propriedade da embarcação','PROPRIEDADE',NULL,1,110,'2026-07-24 11:59:23'),('1c5c2d16-8757-11f1-a50d-aa44e656c57d','DOCUMENTO_PESSOAL','DOCUMENTOS_PESSOAIS','Documento de identificação do interessado/representante','GERAL',NULL,1,120,'2026-07-24 11:59:23'),('1c5c2d58-8757-11f1-a50d-aa44e656c57d','PROCURACAO','DOCUMENTOS_PESSOAIS','Procuração do representante','GERAL',NULL,1,130,'2026-07-24 11:59:23');
/*!40000 ALTER TABLE `protocolo_catalogo_documentos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `protocolo_comprovantes`
--

DROP TABLE IF EXISTS `protocolo_comprovantes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `protocolo_comprovantes` (
  `id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `dossie_id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `movimentacao_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tipo` enum('PROTOCOLO_EXTERNO','RECIBO','COMPROVANTE_ENTREGA','RASTREIO','OUTRO') COLLATE utf8mb4_general_ci NOT NULL,
  `nome_original` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `mime_type` varchar(120) COLLATE utf8mb4_general_ci NOT NULL,
  `tamanho_bytes` bigint unsigned NOT NULL,
  `sha256` char(64) COLLATE utf8mb4_general_ci NOT NULL,
  `caminho` varchar(500) COLLATE utf8mb4_general_ci NOT NULL,
  `criado_por` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_protocolo_comprovante_dossie` (`dossie_id`,`movimentacao_id`),
  KEY `fk_protocolo_comprovante_mov` (`movimentacao_id`),
  KEY `fk_protocolo_comprovante_usuario` (`criado_por`),
  CONSTRAINT `fk_protocolo_comprovante_dossie` FOREIGN KEY (`dossie_id`) REFERENCES `protocolo_dossies` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_protocolo_comprovante_mov` FOREIGN KEY (`movimentacao_id`) REFERENCES `protocolo_movimentacoes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_protocolo_comprovante_usuario` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `protocolo_comprovantes`
--

LOCK TABLES `protocolo_comprovantes` WRITE;
/*!40000 ALTER TABLE `protocolo_comprovantes` DISABLE KEYS */;
/*!40000 ALTER TABLE `protocolo_comprovantes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `protocolo_configuracoes`
--

DROP TABLE IF EXISTS `protocolo_configuracoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `protocolo_configuracoes` (
  `chave` varchar(80) COLLATE utf8mb4_general_ci NOT NULL,
  `valor` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `descricao` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `atualizado_por` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`chave`),
  KEY `fk_protocolo_config_usuario` (`atualizado_por`),
  CONSTRAINT `fk_protocolo_config_usuario` FOREIGN KEY (`atualizado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `protocolo_configuracoes`
--

LOCK TABLES `protocolo_configuracoes` WRITE;
/*!40000 ALTER TABLE `protocolo_configuracoes` DISABLE KEYS */;
INSERT INTO `protocolo_configuracoes` VALUES ('dias_alerta_validade','15','Antecedência para alertar validade do protocolo',NULL,'2026-07-24 12:09:40'),('dias_sem_comprovante','3','Dias após uma saída para alertar falta de comprovante',NULL,'2026-07-24 12:09:40'),('dias_sem_protocolo_oficial','3','Dias após envio à Marinha para alertar falta de SISAP/protocolo',NULL,'2026-07-24 12:09:40');
/*!40000 ALTER TABLE `protocolo_configuracoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `protocolo_dossies`
--

DROP TABLE IF EXISTS `protocolo_dossies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `protocolo_dossies` (
  `id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `numero` varchar(40) COLLATE utf8mb4_general_ci NOT NULL,
  `embarcacao_id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `cliente_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `assunto` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `servico_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `proposta_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `analise_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `vistoria_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `certificado_tipo` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `certificado_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('EM_PREPARACAO','ENVIADO_AO_ORGAO','PROTOCOLADO','EM_ANALISE_NO_ORGAO','EM_EXIGENCIA','A_DISPOSICAO','RETIRADO','ENTREGUE_AO_CLIENTE','ENCERRADO','CANCELADO') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'EM_PREPARACAO',
  `protocolo_externo_numero` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `protocolo_externo_em` datetime DEFAULT NULL,
  `protocolo_externo_validade` date DEFAULT NULL,
  `unidade_maritima_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cancelado_motivo` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cancelado_por` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cancelado_em` datetime DEFAULT NULL,
  `criado_por` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_protocolo_dossie_numero` (`numero`),
  KEY `idx_protocolo_dossie_busca` (`embarcacao_id`,`cliente_id`,`status`),
  KEY `idx_protocolo_dossie_vinculos` (`analise_id`,`vistoria_id`),
  KEY `fk_protocolo_dossie_cliente` (`cliente_id`),
  KEY `fk_protocolo_dossie_servico` (`servico_id`),
  KEY `fk_protocolo_dossie_proposta` (`proposta_id`),
  KEY `fk_protocolo_dossie_vistoria` (`vistoria_id`),
  KEY `fk_protocolo_dossie_unidade` (`unidade_maritima_id`),
  KEY `fk_protocolo_dossie_criador` (`criado_por`),
  KEY `fk_protocolo_dossie_cancelador` (`cancelado_por`),
  CONSTRAINT `fk_protocolo_dossie_analise` FOREIGN KEY (`analise_id`) REFERENCES `analises_planos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_protocolo_dossie_cancelador` FOREIGN KEY (`cancelado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_protocolo_dossie_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_protocolo_dossie_criador` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `fk_protocolo_dossie_embarcacao` FOREIGN KEY (`embarcacao_id`) REFERENCES `embarcacoes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_protocolo_dossie_proposta` FOREIGN KEY (`proposta_id`) REFERENCES `propostas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_protocolo_dossie_servico` FOREIGN KEY (`servico_id`) REFERENCES `servicos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_protocolo_dossie_unidade` FOREIGN KEY (`unidade_maritima_id`) REFERENCES `protocolo_unidades_maritimas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_protocolo_dossie_vistoria` FOREIGN KEY (`vistoria_id`) REFERENCES `vistorias` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `protocolo_dossies`
--

LOCK TABLES `protocolo_dossies` WRITE;
/*!40000 ALTER TABLE `protocolo_dossies` DISABLE KEYS */;
INSERT INTO `protocolo_dossies` VALUES ('677dc7fe-29e1-42be-a52e-6fcefcbeb374','AM-PROT-1/26','09542979-d78e-4095-8ee2-a01e3e7efa07','e82942df-63da-4093-82b7-c2849fe3634e','novoprotocolo',NULL,NULL,NULL,NULL,NULL,NULL,'EM_PREPARACAO',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-07-24 12:13:24','2026-07-24 12:13:24'),('dd693be9-fcef-418d-8e58-530dc3522a25','AM-PROT-2/26','09542979-d78e-4095-8ee2-a01e3e7efa07','e82942df-63da-4093-82b7-c2849fe3634e','novoprotocolo',NULL,NULL,NULL,NULL,NULL,NULL,'EM_PREPARACAO',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-07-24 16:24:25','2026-07-24 16:24:25');
/*!40000 ALTER TABLE `protocolo_dossies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `protocolo_movimentacao_itens`
--

DROP TABLE IF EXISTS `protocolo_movimentacao_itens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `protocolo_movimentacao_itens` (
  `id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `movimentacao_id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `catalogo_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `descricao` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `categoria` varchar(60) COLLATE utf8mb4_general_ci NOT NULL,
  `suporte` enum('FISICO','DIGITAL') COLLATE utf8mb4_general_ci NOT NULL,
  `forma` enum('ORIGINAL','COPIA_SIMPLES','COPIA_AUTENTICADA','NATO_DIGITAL','DIGITALIZADO') COLLATE utf8mb4_general_ci NOT NULL,
  `quantidade` int unsigned NOT NULL DEFAULT '1',
  `numero_revisao` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `data_documento` date DEFAULT NULL,
  `condicao_documento` varchar(120) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `requer_devolucao` tinyint(1) NOT NULL DEFAULT '0',
  `devolvido_em` datetime DEFAULT NULL,
  `arquivo_origem_tipo` varchar(60) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `arquivo_origem_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `arquivo_nome` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `arquivo_hash` char(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `observacao` text COLLATE utf8mb4_general_ci,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_protocolo_item_movimento` (`movimentacao_id`),
  KEY `fk_protocolo_item_catalogo` (`catalogo_id`),
  CONSTRAINT `fk_protocolo_item_catalogo` FOREIGN KEY (`catalogo_id`) REFERENCES `protocolo_catalogo_documentos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_protocolo_item_movimento` FOREIGN KEY (`movimentacao_id`) REFERENCES `protocolo_movimentacoes` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `protocolo_movimentacao_itens`
--

LOCK TABLES `protocolo_movimentacao_itens` WRITE;
/*!40000 ALTER TABLE `protocolo_movimentacao_itens` DISABLE KEYS */;
/*!40000 ALTER TABLE `protocolo_movimentacao_itens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `protocolo_movimentacoes`
--

DROP TABLE IF EXISTS `protocolo_movimentacoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `protocolo_movimentacoes` (
  `id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `dossie_id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `sequencia` int unsigned NOT NULL,
  `tipo` enum('ENTRADA','SAIDA') COLLATE utf8mb4_general_ci NOT NULL,
  `natureza` enum('RECEBIMENTO_CLIENTE','ENVIO_ORGAO','RETORNO_ORGAO','CUMPRIMENTO_EXIGENCIA','RETIRADA_ORGAO','ENTREGA_CLIENTE','TRANSFERENCIA_INTERNA','OUTRA') COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('RASCUNHO','CONFIRMADA','RETIFICADA','CANCELADA') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'RASCUNHO',
  `origem_tipo` enum('CLIENTE','REPRESENTANTE','AMAZON_NAVAL','CAPITANIA','DELEGACIA','AGENCIA','CORREIOS','TRANSPORTADORA','OUTRO') COLLATE utf8mb4_general_ci NOT NULL,
  `origem_nome` varchar(180) COLLATE utf8mb4_general_ci NOT NULL,
  `destino_tipo` enum('CLIENTE','REPRESENTANTE','AMAZON_NAVAL','CAPITANIA','DELEGACIA','AGENCIA','CORREIOS','TRANSPORTADORA','OUTRO') COLLATE utf8mb4_general_ci NOT NULL,
  `destino_nome` varchar(180) COLLATE utf8mb4_general_ci NOT NULL,
  `unidade_maritima_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cidade` varchar(120) COLLATE utf8mb4_general_ci NOT NULL,
  `uf` char(2) COLLATE utf8mb4_general_ci NOT NULL,
  `meio_envio` enum('PRESENCIAL','EMAIL','PORTAL','CORREIOS','TRANSPORTADORA','MENSAGEIRO','OUTRO') COLLATE utf8mb4_general_ci NOT NULL,
  `portador_nome` varchar(180) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `codigo_rastreio` varchar(120) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `movimentado_em` datetime NOT NULL,
  `observacoes` text COLLATE utf8mb4_general_ci,
  `retifica_movimentacao_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `protocolo_anterior_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `idempotency_key` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `snapshot_json` json DEFAULT NULL,
  `pdf_caminho` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pdf_hash` char(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `confirmado_por` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `confirmado_em` datetime DEFAULT NULL,
  `criado_por` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_protocolo_movimento_seq` (`dossie_id`,`sequencia`),
  UNIQUE KEY `uk_protocolo_movimento_idempotencia` (`dossie_id`,`idempotency_key`),
  KEY `idx_protocolo_movimento_status` (`dossie_id`,`status`,`movimentado_em`),
  KEY `fk_protocolo_movimento_unidade` (`unidade_maritima_id`),
  KEY `fk_protocolo_movimento_retifica` (`retifica_movimentacao_id`),
  KEY `fk_protocolo_movimento_anterior` (`protocolo_anterior_id`),
  KEY `fk_protocolo_movimento_confirmador` (`confirmado_por`),
  KEY `fk_protocolo_movimento_criador` (`criado_por`),
  CONSTRAINT `fk_protocolo_movimento_anterior` FOREIGN KEY (`protocolo_anterior_id`) REFERENCES `protocolo_movimentacoes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_protocolo_movimento_confirmador` FOREIGN KEY (`confirmado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_protocolo_movimento_criador` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `fk_protocolo_movimento_dossie` FOREIGN KEY (`dossie_id`) REFERENCES `protocolo_dossies` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_protocolo_movimento_retifica` FOREIGN KEY (`retifica_movimentacao_id`) REFERENCES `protocolo_movimentacoes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_protocolo_movimento_unidade` FOREIGN KEY (`unidade_maritima_id`) REFERENCES `protocolo_unidades_maritimas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `protocolo_movimentacoes`
--

LOCK TABLES `protocolo_movimentacoes` WRITE;
/*!40000 ALTER TABLE `protocolo_movimentacoes` DISABLE KEYS */;
/*!40000 ALTER TABLE `protocolo_movimentacoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `protocolo_unidades_maritimas`
--

DROP TABLE IF EXISTS `protocolo_unidades_maritimas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `protocolo_unidades_maritimas` (
  `id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `codigo` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nome` varchar(180) COLLATE utf8mb4_general_ci NOT NULL,
  `tipo` enum('CAPITANIA','DELEGACIA','AGENCIA') COLLATE utf8mb4_general_ci NOT NULL,
  `cidade` varchar(120) COLLATE utf8mb4_general_ci NOT NULL,
  `uf` char(2) COLLATE utf8mb4_general_ci NOT NULL,
  `endereco` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `telefone` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(180) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `jurisdicao` text COLLATE utf8mb4_general_ci,
  `formato_protocolo_regex` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `url_consulta` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `criado_por` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_protocolo_unidade_nome_cidade` (`nome`,`cidade`,`uf`),
  KEY `fk_protocolo_unidade_criador` (`criado_por`),
  CONSTRAINT `fk_protocolo_unidade_criador` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `protocolo_unidades_maritimas`
--

LOCK TABLES `protocolo_unidades_maritimas` WRITE;
/*!40000 ALTER TABLE `protocolo_unidades_maritimas` DISABLE KEYS */;
INSERT INTO `protocolo_unidades_maritimas` VALUES ('1c5ca795-8757-11f1-a50d-aa44e656c57d','CPAOR','Capitania dos Portos da Amazônia Oriental','CAPITANIA','Belém','PA',NULL,NULL,NULL,NULL,NULL,'https://atendimento-dpc.marinha.mil.br/sisap/agendamento/consultaprocesso/#/',1,NULL,'2026-07-24 11:59:23','2026-07-24 12:00:17');
/*!40000 ALTER TABLE `protocolo_unidades_maritimas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `responsaveis_assinatura`
--

DROP TABLE IF EXISTS `responsaveis_assinatura`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `responsaveis_assinatura` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome_completo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `cpf_cnpj` varchar(18) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `usuario_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cargo_titulo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `registro_profissional` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `assinatura_arquivo` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `assinatura_hash` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `assinatura_atualizada_em` datetime DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_responsavel_assinatura_usuario` (`usuario_id`),
  KEY `idx_responsavel_assinatura_email` (`email`),
  CONSTRAINT `fk_responsavel_assinatura_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `responsaveis_assinatura`
--

LOCK TABLES `responsaveis_assinatura` WRITE;
/*!40000 ALTER TABLE `responsaveis_assinatura` DISABLE KEYS */;
INSERT INTO `responsaveis_assinatura` VALUES (2,'Victal Donanzan','383.034.518-63','ronokedas@gmail.com','dd121661-feb4-42f6-895a-68eb0608d1e4','Engenheiro Naval','CREA: 22.537','storage/private/assinaturas_responsaveis/2/20260720_100109_90048b1dd4c51d95.png','09da23f7c13fbfbf42c88f65ff2208903086c13f3ed5022813784e45a94bdd13','2026-07-20 13:01:09',1,'2026-07-02 04:58:28','2026-07-23 06:12:26'),(5,'João Responsável',NULL,NULL,NULL,'Engenheiro Naval','123456',NULL,NULL,NULL,0,'2026-07-02 17:39:46','2026-07-17 06:33:34'),(6,'João Responsável',NULL,NULL,NULL,'Engenheiro Naval','123456',NULL,NULL,NULL,0,'2026-07-02 17:43:53','2026-07-07 21:13:57'),(7,'Osvaldo','278.006.930-90','ronokedas2024@gmail.com','d2a16613-dfa4-4948-8de4-8c802abdf394','Vistoriador','CREA: 22.5888','storage/private/assinaturas_responsaveis/7/20260723_042523_00e24fdbe224bad3.png','09da23f7c13fbfbf42c88f65ff2208903086c13f3ed5022813784e45a94bdd13','2026-07-23 07:25:23',1,'2026-07-23 07:25:23','2026-07-23 07:25:23');
/*!40000 ALTER TABLE `responsaveis_assinatura` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sequenciais_documentos`
--

DROP TABLE IF EXISTS `sequenciais_documentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sequenciais_documentos` (
  `tipo_documento` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ano` int NOT NULL,
  `ultimo_numero` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`tipo_documento`,`ano`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sequenciais_documentos`
--

LOCK TABLES `sequenciais_documentos` WRITE;
/*!40000 ALTER TABLE `sequenciais_documentos` DISABLE KEYS */;
INSERT INTO `sequenciais_documentos` VALUES ('ORC',2026,3),('PROTOCOLO',2026,2),('RAP',2026,1),('RAP-REL',2026,0),('REL-AP',2026,2),('REL-V',2026,1);
/*!40000 ALTER TABLE `sequenciais_documentos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `servicos`
--

DROP TABLE IF EXISTS `servicos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `servicos` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nome` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `descricao` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `codigo_operacional` varchar(60) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `certificado_modelo` enum('CSN','CNBL','CNARQ') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `preco_padrao` decimal(12,2) NOT NULL DEFAULT '0.00',
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `criado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_servicos_codigo_operacional` (`codigo_operacional`),
  KEY `idx_servicos_ativo` (`ativo`),
  KEY `idx_servicos_certificado_modelo` (`certificado_modelo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `servicos`
--

LOCK TABLES `servicos` WRITE;
/*!40000 ALTER TABLE `servicos` DISABLE KEYS */;
INSERT INTO `servicos` VALUES ('a1d980bd-6ebc-11f1-86ce-7e17ff5f90bf','Análise de Planos Ec1','Analise técnica de planos de embarcação“ Etapa 1','ANALISE_PLANOS_EC1',NULL,2500.00,1,NULL,'2026-06-23 04:33:07','2026-07-24 03:47:16'),('a1d98b0e-6ebc-11f1-86ce-7e17ff5f90bf','Análise de Planos Ec2','Analise técnica de planos de embarcação“ Etapa 2','ANALISE_PLANOS_EC2',NULL,2500.00,1,NULL,'2026-06-23 04:33:07','2026-07-24 03:47:16'),('a1d98d8e-6ebc-11f1-86ce-7e17ff5f90bf','Vistoria Inicial Seco','Vistoria inicial realizada com embarcação em seco (estaleiro/dique)',NULL,'CSN',3500.00,1,NULL,'2026-06-23 04:33:07','2026-07-23 06:52:15'),('a1d98e55-6ebc-11f1-86ce-7e17ff5f90bf','Vistoria Inicial Flutuando','Vistoria inicial realizada com embarcação flutuando',NULL,'CSN',3500.00,1,NULL,'2026-06-23 04:33:07','2026-07-23 06:52:15'),('a1d98eaf-6ebc-11f1-86ce-7e17ff5f90bf','Vistoria Inicial de Borda Livre','Vistoria inicial para certificação de borda livre',NULL,'CNBL',2800.00,1,NULL,'2026-06-23 04:33:07','2026-07-23 06:52:15'),('a1d98ef1-6ebc-11f1-86ce-7e17ff5f90bf','Vistoria Inicial de Arqueação','Vistoria inicial para calculo e certificação de ?????? bruta',NULL,'CNARQ',3200.00,1,NULL,'2026-06-23 04:33:07','2026-07-23 06:52:38'),('a1d98f2e-6ebc-11f1-86ce-7e17ff5f90bf','Acompanhamento de Ultrassom','Acompanhamento de ensaios de ultrassom em casco/estruturas',NULL,NULL,1800.00,1,NULL,'2026-06-23 04:33:07','2026-06-29 06:13:07'),('a1d98f6a-6ebc-11f1-86ce-7e17ff5f90bf','Vistoria Anual','Vistoria anual obrigatória para manutenção de certificados',NULL,NULL,2200.00,1,NULL,'2026-06-23 04:33:07','2026-06-29 06:15:13'),('a1d99130-6ebc-11f1-86ce-7e17ff5f90bf','Vistoria Anual Periódica','Vistoria anual periodica conforme regulamento da Capitania',NULL,NULL,2500.00,1,NULL,'2026-06-23 04:33:07','2026-06-29 06:15:50'),('a1d991e9-6ebc-11f1-86ce-7e17ff5f90bf','Vistoria Intermediária','Vistoria intermediaria de meio-ciclo entre renovações',NULL,NULL,3000.00,1,NULL,'2026-06-23 04:33:07','2026-06-29 06:17:26'),('a1d992d7-6ebc-11f1-86ce-7e17ff5f90bf','Licença Provisória','Emissão de licença provisória para navegação',NULL,NULL,1500.00,1,NULL,'2026-06-23 04:33:07','2026-06-29 06:14:47');
/*!40000 ALTER TABLE `servicos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tipos_embarcacao`
--

DROP TABLE IF EXISTS `tipos_embarcacao`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_embarcacao` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nome` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ativo` tinyint(1) DEFAULT '1',
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tipos_embarcacao`
--

LOCK TABLES `tipos_embarcacao` WRITE;
/*!40000 ALTER TABLE `tipos_embarcacao` DISABLE KEYS */;
INSERT INTO `tipos_embarcacao` VALUES ('06a95b60-75d0-11f1-98f0-5ed0db5eacb7','Balsa',1,'2026-07-02 04:39:35'),('06a95eb2-75d0-11f1-98f0-5ed0db5eacb7','Empurrador',1,'2026-07-02 04:39:35'),('06a95ffa-75d0-11f1-98f0-5ed0db5eacb7','Lancha',1,'2026-07-02 04:39:35'),('06a96069-75d0-11f1-98f0-5ed0db5eacb7','Rebocador',1,'2026-07-02 04:39:35'),('06a96097-75d0-11f1-98f0-5ed0db5eacb7','Flutuante',1,'2026-07-02 04:39:35'),('06a960bd-75d0-11f1-98f0-5ed0db5eacb7','Draga',1,'2026-07-02 04:39:35'),('06a960df-75d0-11f1-98f0-5ed0db5eacb7','Pontão',1,'2026-07-02 04:39:35'),('06a96100-75d0-11f1-98f0-5ed0db5eacb7','Bote',1,'2026-07-02 04:39:35'),('06a96123-75d0-11f1-98f0-5ed0db5eacb7','Navio',1,'2026-07-02 04:39:35'),('06a96149-75d0-11f1-98f0-5ed0db5eacb7','Iate',1,'2026-07-02 04:39:35'),('06a96169-75d0-11f1-98f0-5ed0db5eacb7','Chata',1,'2026-07-02 04:39:35'),('06a96189-75d0-11f1-98f0-5ed0db5eacb7','Ferry Boat',1,'2026-07-02 04:39:35');
/*!40000 ALTER TABLE `tipos_embarcacao` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuario_escritorios`
--

DROP TABLE IF EXISTS `usuario_escritorios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuario_escritorios` (
  `usuario_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `escritorio_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `principal` tinyint(1) NOT NULL DEFAULT '0',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`usuario_id`,`escritorio_id`),
  KEY `idx_usuario_escritorios_escritorio` (`escritorio_id`),
  KEY `idx_usuario_escritorios_principal` (`usuario_id`,`principal`),
  CONSTRAINT `fk_usuario_escritorios_escritorio` FOREIGN KEY (`escritorio_id`) REFERENCES `escritorios` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_usuario_escritorios_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario_escritorios`
--

LOCK TABLES `usuario_escritorios` WRITE;
/*!40000 ALTER TABLE `usuario_escritorios` DISABLE KEYS */;
INSERT INTO `usuario_escritorios` VALUES ('349036db-2b7d-4a98-8509-97bdd3e71fe6','9141f8a5-1d4c-4eba-a749-f5cb040b1630',1,'2026-07-23 06:14:19'),('9cd7e53a-da9d-4f2b-9b32-328be32da2f0','3332440d-dc03-4ab1-8485-8805d098dd6b',1,'2026-07-23 06:13:47'),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','3332440d-dc03-4ab1-8485-8805d098dd6b',1,'2026-07-23 06:13:34'),('d2a16613-dfa4-4948-8de4-8c802abdf394','3332440d-dc03-4ab1-8485-8805d098dd6b',1,'2026-07-23 06:14:04'),('dd121661-feb4-42f6-895a-68eb0608d1e4','3332440d-dc03-4ab1-8485-8805d098dd6b',1,'2026-07-23 06:13:39'),('dd121661-feb4-42f6-895a-68eb0608d1e4','9141f8a5-1d4c-4eba-a749-f5cb040b1630',0,'2026-07-23 06:13:39');
/*!40000 ALTER TABLE `usuario_escritorios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuario_perfis`
--

DROP TABLE IF EXISTS `usuario_perfis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuario_perfis` (
  `usuario_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `perfil` enum('ADMIN','VENDEDOR','VISTORIADOR','ANALISTA') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`usuario_id`,`perfil`),
  CONSTRAINT `fk_usuario_perfis_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario_perfis`
--

LOCK TABLES `usuario_perfis` WRITE;
/*!40000 ALTER TABLE `usuario_perfis` DISABLE KEYS */;
INSERT INTO `usuario_perfis` VALUES ('349036db-2b7d-4a98-8509-97bdd3e71fe6','VENDEDOR','2026-07-23 05:05:06'),('9cd7e53a-da9d-4f2b-9b32-328be32da2f0','ANALISTA','2026-07-16 15:38:12'),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','VENDEDOR','2026-07-21 12:47:52'),('d2a16613-dfa4-4948-8de4-8c802abdf394','VISTORIADOR','2026-07-14 22:05:44'),('dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','2026-07-14 22:05:44'),('dd121661-feb4-42f6-895a-68eb0608d1e4','VISTORIADOR','2026-07-20 13:31:05');
/*!40000 ALTER TABLE `usuario_perfis` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuario_permissoes`
--

DROP TABLE IF EXISTS `usuario_permissoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuario_permissoes` (
  `usuario_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `permissao` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `permitido` tinyint(1) NOT NULL DEFAULT '0',
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`usuario_id`,`permissao`),
  CONSTRAINT `fk_usuario_permissoes_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario_permissoes`
--

LOCK TABLES `usuario_permissoes` WRITE;
/*!40000 ALTER TABLE `usuario_permissoes` DISABLE KEYS */;
INSERT INTO `usuario_permissoes` VALUES ('349036db-2b7d-4a98-8509-97bdd3e71fe6','agendamentos',1,'2026-07-23 05:05:06'),('349036db-2b7d-4a98-8509-97bdd3e71fe6','analise_planos',1,'2026-07-24 03:47:18'),('349036db-2b7d-4a98-8509-97bdd3e71fe6','armadores',1,'2026-07-23 05:05:06'),('349036db-2b7d-4a98-8509-97bdd3e71fe6','comercial',1,'2026-07-23 05:05:06'),('349036db-2b7d-4a98-8509-97bdd3e71fe6','dashboard',1,'2026-07-23 05:05:06'),('349036db-2b7d-4a98-8509-97bdd3e71fe6','despachantes',1,'2026-07-23 05:05:06'),('349036db-2b7d-4a98-8509-97bdd3e71fe6','emails',1,'2026-07-23 05:05:06'),('349036db-2b7d-4a98-8509-97bdd3e71fe6','embarcacoes',1,'2026-07-23 05:05:06'),('349036db-2b7d-4a98-8509-97bdd3e71fe6','proprietarios',1,'2026-07-23 05:05:06'),('349036db-2b7d-4a98-8509-97bdd3e71fe6','servicos',1,'2026-07-23 05:05:06'),('349036db-2b7d-4a98-8509-97bdd3e71fe6','vistorias',1,'2026-07-23 05:05:06'),('9cd7e53a-da9d-4f2b-9b32-328be32da2f0','agendamentos',0,'2026-07-16 16:15:33'),('9cd7e53a-da9d-4f2b-9b32-328be32da2f0','analise_planos',1,'2026-07-18 14:27:15'),('9cd7e53a-da9d-4f2b-9b32-328be32da2f0','armadores',0,'2026-07-16 16:15:33'),('9cd7e53a-da9d-4f2b-9b32-328be32da2f0','certificados',0,'2026-07-16 16:15:33'),('9cd7e53a-da9d-4f2b-9b32-328be32da2f0','comercial',0,'2026-07-16 16:15:33'),('9cd7e53a-da9d-4f2b-9b32-328be32da2f0','configuracoes',0,'2026-07-16 16:15:33'),('9cd7e53a-da9d-4f2b-9b32-328be32da2f0','dashboard',1,'2026-07-16 16:15:33'),('9cd7e53a-da9d-4f2b-9b32-328be32da2f0','despachantes',0,'2026-07-16 16:15:33'),('9cd7e53a-da9d-4f2b-9b32-328be32da2f0','documentacao',0,'2026-07-16 16:15:33'),('9cd7e53a-da9d-4f2b-9b32-328be32da2f0','emails',0,'2026-07-16 16:15:33'),('9cd7e53a-da9d-4f2b-9b32-328be32da2f0','embarcacoes',0,'2026-07-16 16:15:33'),('9cd7e53a-da9d-4f2b-9b32-328be32da2f0','financeiro',0,'2026-07-16 16:15:33'),('9cd7e53a-da9d-4f2b-9b32-328be32da2f0','portal_clientes',0,'2026-07-16 16:15:33'),('9cd7e53a-da9d-4f2b-9b32-328be32da2f0','proprietarios',0,'2026-07-16 16:15:33'),('9cd7e53a-da9d-4f2b-9b32-328be32da2f0','relatorios',0,'2026-07-16 16:15:33'),('9cd7e53a-da9d-4f2b-9b32-328be32da2f0','relatorios_aprovacao',0,'2026-07-24 03:47:18'),('9cd7e53a-da9d-4f2b-9b32-328be32da2f0','responsaveis_assinatura',0,'2026-07-16 16:15:33'),('9cd7e53a-da9d-4f2b-9b32-328be32da2f0','servicos',0,'2026-07-16 16:15:33'),('9cd7e53a-da9d-4f2b-9b32-328be32da2f0','usuarios',0,'2026-07-16 16:15:33'),('9cd7e53a-da9d-4f2b-9b32-328be32da2f0','vistorias',1,'2026-07-16 16:15:33'),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','agendamentos',1,'2026-07-21 12:47:52'),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','analise_planos',1,'2026-07-24 03:47:18'),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','armadores',1,'2026-07-21 12:47:52'),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','certificados',1,'2026-07-21 12:48:36'),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','comercial',1,'2026-07-21 12:47:52'),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','configuracoes',0,'2026-07-21 16:21:37'),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','dashboard',1,'2026-07-21 12:47:52'),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','despachantes',1,'2026-07-21 12:47:52'),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','documentacao',1,'2026-07-21 12:48:36'),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','emails',1,'2026-07-21 12:47:52'),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','embarcacoes',1,'2026-07-21 12:47:52'),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','financeiro',1,'2026-07-21 12:48:36'),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','portal_clientes',1,'2026-07-21 12:48:36'),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','proprietarios',1,'2026-07-21 12:47:52'),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','relatorios',1,'2026-07-21 12:48:36'),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','relatorios_aprovacao',1,'2026-07-21 12:48:36'),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','responsaveis_assinatura',0,'2026-07-21 12:48:36'),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','servicos',1,'2026-07-21 12:47:52'),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','usuarios',0,'2026-07-21 12:48:36'),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','vistorias',1,'2026-07-21 12:47:52'),('d2a16613-dfa4-4948-8de4-8c802abdf394','agendamentos',1,'2026-07-17 01:38:21'),('d2a16613-dfa4-4948-8de4-8c802abdf394','analise_planos',0,'2026-07-21 16:21:37'),('d2a16613-dfa4-4948-8de4-8c802abdf394','armadores',0,'2026-07-16 16:15:33'),('d2a16613-dfa4-4948-8de4-8c802abdf394','certificados',1,'2026-07-16 16:39:29'),('d2a16613-dfa4-4948-8de4-8c802abdf394','comercial',0,'2026-07-16 16:15:33'),('d2a16613-dfa4-4948-8de4-8c802abdf394','configuracoes',0,'2026-07-16 16:15:33'),('d2a16613-dfa4-4948-8de4-8c802abdf394','dashboard',1,'2026-07-16 16:15:33'),('d2a16613-dfa4-4948-8de4-8c802abdf394','despachantes',0,'2026-07-16 16:15:33'),('d2a16613-dfa4-4948-8de4-8c802abdf394','documentacao',1,'2026-07-16 16:15:33'),('d2a16613-dfa4-4948-8de4-8c802abdf394','emails',0,'2026-07-16 16:15:33'),('d2a16613-dfa4-4948-8de4-8c802abdf394','embarcacoes',1,'2026-07-16 16:15:33'),('d2a16613-dfa4-4948-8de4-8c802abdf394','financeiro',0,'2026-07-16 16:15:33'),('d2a16613-dfa4-4948-8de4-8c802abdf394','portal_clientes',0,'2026-07-16 16:15:33'),('d2a16613-dfa4-4948-8de4-8c802abdf394','proprietarios',0,'2026-07-16 16:15:33'),('d2a16613-dfa4-4948-8de4-8c802abdf394','relatorios',0,'2026-07-16 16:15:33'),('d2a16613-dfa4-4948-8de4-8c802abdf394','relatorios_aprovacao',1,'2026-07-17 01:38:21'),('d2a16613-dfa4-4948-8de4-8c802abdf394','responsaveis_assinatura',0,'2026-07-16 16:15:33'),('d2a16613-dfa4-4948-8de4-8c802abdf394','servicos',0,'2026-07-16 16:15:33'),('d2a16613-dfa4-4948-8de4-8c802abdf394','usuarios',0,'2026-07-16 16:15:33'),('d2a16613-dfa4-4948-8de4-8c802abdf394','vistorias',1,'2026-07-16 16:15:33'),('dd121661-feb4-42f6-895a-68eb0608d1e4','analise_planos',1,'2026-07-18 14:27:15');
/*!40000 ALTER TABLE `usuario_permissoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT (uuid()),
  `nome` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `senha_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `cargo` enum('ADMIN','VENDEDOR','VISTORIADOR','ANALISTA') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'VISTORIADOR',
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `excluido_em` datetime DEFAULT NULL,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `acesso_documentacao` tinyint(1) DEFAULT '0',
  `acesso_financeiro` tinyint(1) DEFAULT '0',
  `escritorio_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '00000000-0000-4000-8000-000000000100',
  `gestor_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_usuarios_excluido_em` (`excluido_em`),
  KEY `idx_usuarios_escritorio` (`escritorio_id`),
  KEY `idx_usuarios_gestor` (`gestor_id`),
  CONSTRAINT `fk_usuarios_escritorio` FOREIGN KEY (`escritorio_id`) REFERENCES `escritorios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_usuarios_gestor` FOREIGN KEY (`gestor_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES ('11111111-1111-1111-1111-111111111111','Carlos Mendes','excluido.11111111111111111111111111111111@local.invalid','$2y$10$SjdkE2qA2s5C1UHZo/V4yOaIYQ1RWLsybsGP7Vf1cLmGJYmeflMFi','VISTORIADOR',0,'2026-07-19 02:27:38','2026-06-24 17:33:03','2026-07-19 02:27:38',0,0,'00000000-0000-4000-8000-000000000100',NULL),('1c015cb0-3187-4068-bc6d-06585521e165','anabe','excluido.1c015cb031874068bc6d06585521e165@local.invalid','$2y$10$YTFhG9EMyJrdZssxn5aXuelGURp2nULigmFHIKVGdqFiQxbzAXIBu','VENDEDOR',0,'2026-07-19 02:27:32','2026-06-27 03:51:48','2026-07-19 02:27:32',1,1,'00000000-0000-4000-8000-000000000100',NULL),('22222222-2222-2222-2222-222222222222','Ana Paula Silva','excluido.22222222222222222222222222222222@local.invalid','$2y$10$t5EgpXiQyTOM/NZjPcdREep5XsL.u.y8OztQGiCY1EF55VlLklvvO','VISTORIADOR',0,'2026-07-19 02:27:28','2026-06-24 17:33:03','2026-07-19 02:27:28',0,0,'00000000-0000-4000-8000-000000000100',NULL),('33333333-3333-3333-3333-333333333333','Roberto Lima','excluido.33333333333333333333333333333333@local.invalid','$2y$10$lH9jpywZL4ueeCNV1kxUXe4Ayl51gRcqjTqNLiU0S5aW0DA4IqD1y','VISTORIADOR',0,'2026-07-19 02:27:48','2026-06-24 17:33:03','2026-07-19 02:27:48',0,0,'00000000-0000-4000-8000-000000000100',NULL),('349036db-2b7d-4a98-8509-97bdd3e71fe6','Vendedor2','vendedor1@teste.com','$2y$10$ylVeOJcCuz/gro/9rDZFi.0n.skUylm4pM2LKoTWqYpRcQPrCT/la','VENDEDOR',1,NULL,'2026-07-23 05:05:06','2026-07-23 06:14:19',0,0,'9141f8a5-1d4c-4eba-a749-f5cb040b1630',NULL),('3774d80c-2574-470e-88a9-9781936c6de3','Any','excluido.3774d80c2574470e88a99781936c6de3@local.invalid','$2y$10$TzfH61SflMPiQpW4MFIP5OTf2/khZ51Q66XX1HiNl3SjgtruZj8au','VISTORIADOR',0,'2026-07-19 02:27:35','2026-06-23 22:51:43','2026-07-19 02:27:35',1,0,'00000000-0000-4000-8000-000000000100',NULL),('74e02f95-fbe6-42f3-bedf-f8535e4d13aa','Rosano Souza','excluido.74e02f95fbe642f3bedff8535e4d13aa@local.invalid','$2y$10$pEGJqFBciTy5Zm4.xv1CTOi9eF29nXW4NWRaifY/h4f74SWAJd0EG','VISTORIADOR',0,'2026-07-19 02:27:52','2026-06-11 21:44:56','2026-07-19 02:27:52',0,0,'00000000-0000-4000-8000-000000000100',NULL),('95eb5557-65e8-11f1-85ef-047c16b568a3','Administrador','excluido.95eb555765e811f185ef047c16b568a3@local.invalid','$2y$10$WDtKPgD44yf3STmx0SPfOuiy2AgKuWi5EEFozzSOfvZ3vLGGLW7Pq','ADMIN',0,'2026-07-19 02:28:49','2026-06-11 19:55:04','2026-07-19 02:28:49',0,0,'00000000-0000-4000-8000-000000000100',NULL),('9cd7e53a-da9d-4f2b-9b32-328be32da2f0','itamar','analista@teste.com','$2y$10$F5UPnrEEekDBk/e29IjWA.KAf/uraDODMoMU9e.7PkCZNAcGwzdwu','ANALISTA',1,NULL,'2026-07-16 15:38:12','2026-07-23 06:13:47',0,0,'3332440d-dc03-4ab1-8485-8805d098dd6b',NULL),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','any','vendedor@teste.com','$2y$10$3j2BvFNQytOeftJk4nWwHORcMqt9Ru5Jahqn7KjoDPS7pI6w0re.u','VENDEDOR',1,NULL,'2026-07-21 12:47:52','2026-07-23 06:13:34',0,0,'3332440d-dc03-4ab1-8485-8805d098dd6b',NULL),('d2a16613-dfa4-4948-8de4-8c802abdf394','Neto','teste1@teste.com','$2y$10$m0j9tLSTqImLq5InXGX1PODcBq5tIX9sc1vqH2PklDptbWYPq8VQO','VISTORIADOR',1,NULL,'2026-07-07 21:10:28','2026-07-23 06:14:04',1,0,'3332440d-dc03-4ab1-8485-8805d098dd6b',NULL),('dd121661-feb4-42f6-895a-68eb0608d1e4','admin','teste@teste.com','$2y$10$eK05TTRWPQmp7ldYEALHrOMRSVKUGMo6yqVv3kCU0yYiOz5KzBWw6','ADMIN',1,NULL,'2026-07-05 13:39:17','2026-07-23 06:13:39',0,0,'3332440d-dc03-4ab1-8485-8805d098dd6b',NULL),('e5c68a85-c920-4b11-bc93-9343d9d94f14','vistoriador teste','excluido.e5c68a85c9204b11bc939343d9d94f14@local.invalid','$2y$10$LdMu1ZxZP.ysBC10FSV/TeWm5yuEeZkyenLH5fxHKx4QA6MbAPGeW','VISTORIADOR',0,'2026-07-19 02:28:20','2026-07-02 15:06:59','2026-07-19 02:28:20',0,0,'00000000-0000-4000-8000-000000000100',NULL);
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vistoria_anexos`
--

DROP TABLE IF EXISTS `vistoria_anexos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vistoria_anexos` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `vistoria_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `catalogo_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `url_arquivo` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `chave_arquivo` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nome_original` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mime_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tamanho_bytes` int unsigned NOT NULL,
  `sha256` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `capturado_em` datetime DEFAULT NULL,
  `criado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `excluido_em` datetime DEFAULT NULL,
  `excluido_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_vistoria_anexo_hash` (`vistoria_id`,`sha256`),
  KEY `idx_vistoria_anexos_catalogo` (`catalogo_id`),
  KEY `idx_vistoria_anexos_criado_por` (`criado_por`),
  CONSTRAINT `fk_vistoria_anexos_catalogo` FOREIGN KEY (`catalogo_id`) REFERENCES `exigencias_catalogo` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_vistoria_anexos_usuario` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_vistoria_anexos_vistoria` FOREIGN KEY (`vistoria_id`) REFERENCES `vistorias` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vistoria_anexos`
--

LOCK TABLES `vistoria_anexos` WRITE;
/*!40000 ALTER TABLE `vistoria_anexos` DISABLE KEYS */;
/*!40000 ALTER TABLE `vistoria_anexos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vistoria_checklist_respostas`
--

DROP TABLE IF EXISTS `vistoria_checklist_respostas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vistoria_checklist_respostas` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `vistoria_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `catalogo_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('CONFORME','NAO_CONFORME','NAO_SE_APLICA') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `observacao` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `item_normam` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `vencimento` date DEFAULT NULL,
  `sem_prazo` tinyint(1) NOT NULL DEFAULT '0',
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_vistoria_catalogo` (`vistoria_id`,`catalogo_id`),
  KEY `catalogo_id` (`catalogo_id`),
  CONSTRAINT `vistoria_checklist_respostas_ibfk_1` FOREIGN KEY (`vistoria_id`) REFERENCES `vistorias` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vistoria_checklist_respostas_ibfk_2` FOREIGN KEY (`catalogo_id`) REFERENCES `exigencias_catalogo` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vistoria_checklist_respostas`
--

LOCK TABLES `vistoria_checklist_respostas` WRITE;
/*!40000 ALTER TABLE `vistoria_checklist_respostas` DISABLE KEYS */;
INSERT INTO `vistoria_checklist_respostas` VALUES ('20639a96-8667-11f1-a50d-aa44e656c57d','7a175152-999a-418d-bd14-30d33881910d','64264fe0-373e-4c75-82be-3665162220eb','NAO_CONFORME',NULL,'NORMAM-202/DPC, Cap. 03, Seção IV.','2026-10-22',0,'2026-07-23 07:21:30','2026-07-23 07:21:30'),('2063b41a-8667-11f1-a50d-aa44e656c57d','7a175152-999a-418d-bd14-30d33881910d','9be9b57c-5702-4e46-9703-4414b0c8ce56','NAO_CONFORME',NULL,'NORMAM-202/DPC, Cap. 04, Seção I.','2026-10-22',0,'2026-07-23 07:21:30','2026-07-23 07:21:30'),('2063cdd0-8667-11f1-a50d-aa44e656c57d','7a175152-999a-418d-bd14-30d33881910d','cf34c2da-207c-4d4c-a185-8c19374aaedf','NAO_CONFORME',NULL,'NORMAM-202/DPC','2026-10-22',0,'2026-07-23 07:21:30','2026-07-23 07:21:30'),('2063e7a0-8667-11f1-a50d-aa44e656c57d','7a175152-999a-418d-bd14-30d33881910d','e125df21-a446-4bef-9486-35a165b9220b','NAO_CONFORME',NULL,'NORMAM-202/DPC, Cap. 04, Seção I.','2026-10-22',0,'2026-07-23 07:21:30','2026-07-23 07:21:30'),('91d6be16-871d-11f1-a50d-aa44e656c57d','0587656a-3ef8-40e9-aeb6-6a6ce918e0bd','a371bf33-76aa-11f1-9eb5-0a1b2af87b16','NAO_CONFORME',NULL,'NORMAM-202/DPC, Cap. 03, Seção I.','2026-10-28',0,'2026-07-24 05:07:29','2026-07-24 05:07:29'),('91d6d51a-871d-11f1-a50d-aa44e656c57d','0587656a-3ef8-40e9-aeb6-6a6ce918e0bd','a371da38-76aa-11f1-9eb5-0a1b2af87b16','NAO_CONFORME',NULL,'NORMAM-202/DPC, Cap. 04, Seção I.','2026-10-28',0,'2026-07-24 05:07:29','2026-07-24 05:07:29'),('91d6eac7-871d-11f1-a50d-aa44e656c57d','0587656a-3ef8-40e9-aeb6-6a6ce918e0bd','a371f205-76aa-11f1-9eb5-0a1b2af87b16','NAO_CONFORME',NULL,'NORMAM-202/DPC, Cap. 04, Seção I.','2026-10-28',0,'2026-07-24 05:07:29','2026-07-24 05:07:29'),('91d7009a-871d-11f1-a50d-aa44e656c57d','0587656a-3ef8-40e9-aeb6-6a6ce918e0bd','a37244fe-76aa-11f1-9eb5-0a1b2af87b16','NAO_CONFORME',NULL,'NORMAM-202/DPC, Cap. 03, Seção I.','2026-10-28',0,'2026-07-24 05:07:29','2026-07-24 05:07:29'),('91d716cd-871d-11f1-a50d-aa44e656c57d','0587656a-3ef8-40e9-aeb6-6a6ce918e0bd','a37275b5-76aa-11f1-9eb5-0a1b2af87b16','NAO_CONFORME',NULL,'NORMAM-202/DPC, Cap. 03, Seção I.','2026-10-28',0,'2026-07-24 05:07:29','2026-07-24 05:07:29'),('91d72d27-871d-11f1-a50d-aa44e656c57d','0587656a-3ef8-40e9-aeb6-6a6ce918e0bd','5d288f7e-25e6-4e36-b8aa-093601403d54','NAO_CONFORME',NULL,'NORMAM-202/DPC, Cap. 09, Seção I.','2026-10-28',0,'2026-07-24 05:07:29','2026-07-24 05:07:29'),('91d742ee-871d-11f1-a50d-aa44e656c57d','0587656a-3ef8-40e9-aeb6-6a6ce918e0bd','af6b1cb2-e94a-452c-a083-9b7e2f41ff69','NAO_CONFORME',NULL,'NORMAM-202/DPC, Cap. 03, Seção III.','2026-10-28',0,'2026-07-24 05:07:29','2026-07-24 05:07:29'),('91d75833-871d-11f1-a50d-aa44e656c57d','0587656a-3ef8-40e9-aeb6-6a6ce918e0bd','1bb30d90-ee8e-4efe-946d-d3ee1385eb36','NAO_CONFORME',NULL,'NORMAM-202/DPC','2026-10-28',0,'2026-07-24 05:07:29','2026-07-24 05:07:29'),('a0e89235-8661-11f1-a50d-aa44e656c57d','4f3bd30c-44b9-47f4-800d-25601773e329','446e0844-e616-4c5e-a073-480d64f291d7','NAO_CONFORME',NULL,'NORMAM-202/DPC','2026-10-22',0,'2026-07-23 06:42:09','2026-07-23 06:42:09'),('a0e8a8d6-8661-11f1-a50d-aa44e656c57d','4f3bd30c-44b9-47f4-800d-25601773e329','e556f7ad-a680-44ce-861d-f051aac27a86','NAO_CONFORME',NULL,'NORMAM-202/DPC, Cap. 03, Seção IV.','2026-10-22',0,'2026-07-23 06:42:09','2026-07-23 06:42:09'),('a0e8bec8-8661-11f1-a50d-aa44e656c57d','4f3bd30c-44b9-47f4-800d-25601773e329','4add624f-894e-442c-bc48-1bf430208d14','NAO_CONFORME',NULL,'NORMAM-202/DPC, Cap. 03, Seção IV.','2026-10-22',0,'2026-07-23 06:42:09','2026-07-23 06:42:09'),('a0e8d229-8661-11f1-a50d-aa44e656c57d','4f3bd30c-44b9-47f4-800d-25601773e329','9537c200-5b45-4d8b-b670-505c5c936f79','NAO_CONFORME',NULL,'NORMAM-202/DPC','2026-10-22',0,'2026-07-23 06:42:09','2026-07-23 06:42:09'),('a0e8e679-8661-11f1-a50d-aa44e656c57d','4f3bd30c-44b9-47f4-800d-25601773e329','ecf0c6d1-02a0-479f-9b92-982e68083700','NAO_CONFORME',NULL,'NORMAM-202/DPC, Cap. 03, Seção IV.','2026-10-22',0,'2026-07-23 06:42:09','2026-07-23 06:42:09'),('a0e8fa2d-8661-11f1-a50d-aa44e656c57d','4f3bd30c-44b9-47f4-800d-25601773e329','fd836b06-765d-4b56-a022-699234aab52b','NAO_CONFORME',NULL,'NORMAM-202/DPC, Cap. 03, Seção IV.','2026-10-22',0,'2026-07-23 06:42:09','2026-07-23 06:42:09'),('a0e90fa0-8661-11f1-a50d-aa44e656c57d','4f3bd30c-44b9-47f4-800d-25601773e329','902653ef-7f5d-497e-a1f4-d78f31212d7c','NAO_CONFORME',NULL,'NORMAM-202/DPC, Cap. 03, Seção IV.','2026-10-22',0,'2026-07-23 06:42:09','2026-07-23 06:42:09');
/*!40000 ALTER TABLE `vistoria_checklist_respostas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vistoria_exigencias`
--

DROP TABLE IF EXISTS `vistoria_exigencias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vistoria_exigencias` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT (uuid()),
  `vistoria_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `catalogo_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bloco_vistoria` enum('seco','flutuando','borda_livre','arqueacao') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ordem` tinyint unsigned NOT NULL DEFAULT '0',
  `item` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `descricao` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `conforme` enum('sim','nao','na') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'na',
  `observacao` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `item_normam` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `vencimento` date DEFAULT NULL,
  `antes_de_suspender` tinyint(1) NOT NULL DEFAULT '0',
  `status_item` enum('pendente','cumprida','nao_cumprida_transcrita','cumprida_parcial_reescrita','inserida') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'inserida',
  `exigencia_origem_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vistoria_id` (`vistoria_id`),
  KEY `ordem` (`ordem`),
  KEY `fk_vistoria_exig_catalogo` (`catalogo_id`),
  KEY `fk_vistoria_exig_origem` (`exigencia_origem_id`),
  KEY `idx_exigencias_as_pendentes` (`vistoria_id`,`antes_de_suspender`,`conforme`,`status_item`),
  CONSTRAINT `fk_vistoria_exig_catalogo` FOREIGN KEY (`catalogo_id`) REFERENCES `exigencias_catalogo` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_vistoria_exig_origem` FOREIGN KEY (`exigencia_origem_id`) REFERENCES `vistoria_exigencias` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vistoria_exigencias_ibfk_1` FOREIGN KEY (`vistoria_id`) REFERENCES `vistorias` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vistoria_exigencias`
--

LOCK TABLES `vistoria_exigencias` WRITE;
/*!40000 ALTER TABLE `vistoria_exigencias` DISABLE KEYS */;
INSERT INTO `vistoria_exigencias` VALUES ('2063ab6c-8667-11f1-a50d-aa44e656c57d','7a175152-999a-418d-bd14-30d33881910d','64264fe0-373e-4c75-82be-3665162220eb','flutuando',1,'Item Normam: NORMAM-202/DPC, Cap. 03, Seção IV.','Lanterna portátil com bateria recarregável ou pilhas sobressalentes','nao',NULL,'NORMAM-202/DPC, Cap. 03, Seção IV.','2026-10-22',0,'pendente',NULL),('2063c51f-8667-11f1-a50d-aa44e656c57d','7a175152-999a-418d-bd14-30d33881910d','9be9b57c-5702-4e46-9703-4414b0c8ce56','flutuando',2,'Item Normam: NORMAM-202/DPC, Cap. 04, Seção I.','Binóculo 7x50','nao',NULL,'NORMAM-202/DPC, Cap. 04, Seção I.','2026-10-22',0,'pendente',NULL),('2063de2b-8667-11f1-a50d-aa44e656c57d','7a175152-999a-418d-bd14-30d33881910d','cf34c2da-207c-4d4c-a185-8c19374aaedf','flutuando',3,'Item Normam: NORMAM-202/DPC','Alarme visual e sonoro de alta temperatura da água de resfriamento do MCP e MCA com potência igual ou superior a 800 HP (597 kW)','nao',NULL,'NORMAM-202/DPC','2026-10-22',0,'pendente',NULL),('2063f6b8-8667-11f1-a50d-aa44e656c57d','7a175152-999a-418d-bd14-30d33881910d','e125df21-a446-4bef-9486-35a165b9220b','flutuando',4,'Item Normam: NORMAM-202/DPC, Cap. 04, Seção I.','Agulha giroscópica ou magnética','nao',NULL,'NORMAM-202/DPC, Cap. 04, Seção I.','2026-10-22',0,'pendente',NULL),('91d6cd25-871d-11f1-a50d-aa44e656c57d','0587656a-3ef8-40e9-aeb6-6a6ce918e0bd','a371bf33-76aa-11f1-9eb5-0a1b2af87b16','borda_livre',1,'Item Normam: NORMAM-202/DPC, Cap. 03, Seção I.','Há passagem permanentemente desobstruída de proa à popa, que não é efetivada por cima de tampas de escotilhas. Tal passagem possui largura mínima em conformidade com o estabelecido no Anexo 3-M','nao',NULL,'NORMAM-202/DPC, Cap. 03, Seção I.','2026-10-28',0,'pendente',NULL),('91d6e306-871d-11f1-a50d-aa44e656c57d','0587656a-3ef8-40e9-aeb6-6a6ce918e0bd','a371da38-76aa-11f1-9eb5-0a1b2af87b16','borda_livre',2,'Item Normam: NORMAM-202/DPC, Cap. 04, Seção I.','Em todas as partes expostas dos conveses principais e de superestruturas há eficientes balaustradas ou bordas falsas (que poderão ser removíveis), com altura não inferior a 1 metro (para embarcações com AB maior que 20)','nao',NULL,'NORMAM-202/DPC, Cap. 04, Seção I.','2026-10-28',0,'pendente',NULL),('91d6f963-871d-11f1-a50d-aa44e656c57d','0587656a-3ef8-40e9-aeb6-6a6ce918e0bd','a371f205-76aa-11f1-9eb5-0a1b2af87b16','borda_livre',3,'Item Normam: NORMAM-202/DPC, Cap. 04, Seção I.','A abertura inferior da balaustrada apresenta altura menor ou igual a 230 mm e os demais vãos não poderão apresentar espaçamento superior a 380 mm. No caso de embarcações com bordas arredondadas, os suportes das balaustradas deverão ser colocados na parte plana do convés','nao',NULL,'NORMAM-202/DPC, Cap. 04, Seção I.','2026-10-28',0,'pendente',NULL),('91d70fb7-871d-11f1-a50d-aa44e656c57d','0587656a-3ef8-40e9-aeb6-6a6ce918e0bd','a37244fe-76aa-11f1-9eb5-0a1b2af87b16','borda_livre',4,'Item Normam: NORMAM-202/DPC, Cap. 03, Seção I.','As aberturas no costado de embarcações dos tipos A, B ou D deverão possuir tampas estanques à água ou vigias e olhos de boi e deverão estar posicionadas de forma que sua aresta inferior esteja a, pelo menos, 300 mm acima da linha d’água carregada, em qualquer condição esperada de trim. Para as embarcações dos tipos C ou E essa distância não deverá ser inferior a 500 mm','nao',NULL,'NORMAM-202/DPC, Cap. 03, Seção I.','2026-10-28',0,'pendente',NULL),('91d724cc-871d-11f1-a50d-aa44e656c57d','0587656a-3ef8-40e9-aeb6-6a6ce918e0bd','a37275b5-76aa-11f1-9eb5-0a1b2af87b16','borda_livre',5,'Item Normam: NORMAM-202/DPC, Cap. 03, Seção I.','Os escotilhões e as aberturas de escotilha possuem braçola de pelo menos 150 mm de altura (260 mm para embarcações que operam em área 2) e são dotados de tampas que possam ser fixadas às braçolas. As embarcações dos tipos “C” e “E” estão dispensadas da obrigatoriedade de possuírem tampas de escotilha ou dos escotilhões','nao',NULL,'NORMAM-202/DPC, Cap. 03, Seção I.','2026-10-28',0,'pendente',NULL),('91d73c1f-871d-11f1-a50d-aa44e656c57d','0587656a-3ef8-40e9-aeb6-6a6ce918e0bd','5d288f7e-25e6-4e36-b8aa-093601403d54','flutuando',6,'Item Normam: NORMAM-202/DPC, Cap. 09, Seção I.','Verificar a limpeza dos espaços de máquinas e equipamentos. Os espaços e equipamentos de máquinas deverão ser mantidos limpos e sem vazamentos de óleos e com os estrados em bom estado de conservação','nao',NULL,'NORMAM-202/DPC, Cap. 09, Seção I.','2026-10-28',0,'pendente',NULL),('91d74ff7-871d-11f1-a50d-aa44e656c57d','0587656a-3ef8-40e9-aeb6-6a6ce918e0bd','af6b1cb2-e94a-452c-a083-9b7e2f41ff69','flutuando',7,'Item Normam: NORMAM-202/DPC, Cap. 03, Seção III.','Motores cujo sistema de arrefecimento seja constituído por ventiladores deverão ter os mesmos providos de proteção','nao',NULL,'NORMAM-202/DPC, Cap. 03, Seção III.','2026-10-28',0,'pendente',NULL),('91d7665a-871d-11f1-a50d-aa44e656c57d','0587656a-3ef8-40e9-aeb6-6a6ce918e0bd','1bb30d90-ee8e-4efe-946d-d3ee1385eb36','flutuando',8,'Item Normam: NORMAM-202/DPC','Verificar a presença de objetos não necessários ao funcionamento dos equipamentos, estivados de forma irregular sobre ou próximo aos equipamentos','nao',NULL,'NORMAM-202/DPC','2026-10-28',0,'pendente',NULL),('a0e8a0f4-8661-11f1-a50d-aa44e656c57d','4f3bd30c-44b9-47f4-800d-25601773e329','446e0844-e616-4c5e-a073-480d64f291d7','flutuando',1,'Item Normam: NORMAM-202/DPC','O arranjo físico da embarcação está de acordo com o Arranjo Geral.','nao',NULL,'NORMAM-202/DPC','2026-10-22',0,'pendente',NULL),('a0e8b6dc-8661-11f1-a50d-aa44e656c57d','4f3bd30c-44b9-47f4-800d-25601773e329','e556f7ad-a680-44ce-861d-f051aac27a86','flutuando',2,'Item Normam: NORMAM-202/DPC, Cap. 03, Seção IV.','A fonte de energia principal tem capacidade suficiente para suprir a carga necessária para manter a embarcação em plenas condições de operação e habitabilidade, levando-se em consideração os fatores de potência, de demanda e a simultaneidade das cargas','nao',NULL,'NORMAM-202/DPC, Cap. 03, Seção IV.','2026-10-22',0,'pendente',NULL),('a0e8cb55-8661-11f1-a50d-aa44e656c57d','4f3bd30c-44b9-47f4-800d-25601773e329','4add624f-894e-442c-bc48-1bf430208d14','flutuando',3,'Item Normam: NORMAM-202/DPC, Cap. 03, Seção IV.','A fonte de energia de emergência está localizada, se possível, acima do convés contínuo superior e é de pronto acesso partindo-se do convés aberto.','nao',NULL,'NORMAM-202/DPC, Cap. 03, Seção IV.','2026-10-22',0,'pendente',NULL),('a0e8df43-8661-11f1-a50d-aa44e656c57d','4f3bd30c-44b9-47f4-800d-25601773e329','9537c200-5b45-4d8b-b670-505c5c936f79','flutuando',4,'Item Normam: NORMAM-202/DPC','Quanto aos quadros elétricos: a) todos eles são dispostos de maneira que ofereçam fácil acesso durante a operação e ou manutenção dos equipamentos','nao',NULL,'NORMAM-202/DPC','2026-10-22',0,'pendente',NULL),('a0e8f337-8661-11f1-a50d-aa44e656c57d','4f3bd30c-44b9-47f4-800d-25601773e329','ecf0c6d1-02a0-479f-9b92-982e68083700','flutuando',5,'Item Normam: NORMAM-202/DPC, Cap. 03, Seção IV.','Quanto aos quadros elétricos: d) se a fonte de emergência de energia for constituída por bateria de acumuladores, ela não está instalada no mesmo compartimento do quadro elétrico de emergência','nao',NULL,'NORMAM-202/DPC, Cap. 03, Seção IV.','2026-10-22',0,'pendente',NULL),('a0e90883-8661-11f1-a50d-aa44e656c57d','4f3bd30c-44b9-47f4-800d-25601773e329','fd836b06-765d-4b56-a022-699234aab52b','flutuando',6,'Item Normam: NORMAM-202/DPC, Cap. 03, Seção IV.','Os transformadores são protegidos com disjuntores no primário','nao',NULL,'NORMAM-202/DPC, Cap. 03, Seção IV.','2026-10-22',0,'pendente',NULL),('a0e91d97-8661-11f1-a50d-aa44e656c57d','4f3bd30c-44b9-47f4-800d-25601773e329','902653ef-7f5d-497e-a1f4-d78f31212d7c','flutuando',7,'Item Normam: NORMAM-202/DPC, Cap. 03, Seção IV.','d) os cabos e fiação estão instalados e fixados de modo a evitar desgastes por atrito ou outra avaria','nao',NULL,'NORMAM-202/DPC, Cap. 03, Seção IV.','2026-10-22',0,'pendente',NULL);
/*!40000 ALTER TABLE `vistoria_exigencias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vistoria_mobile_sync`
--

DROP TABLE IF EXISTS `vistoria_mobile_sync`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vistoria_mobile_sync` (
  `operacao_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `vistoria_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `usuario_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tipo` enum('RASCUNHO','ANEXO','FOTO_EMBARCACAO','FINALIZACAO') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `payload_hash` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `resposta_json` json DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`operacao_id`),
  KEY `idx_mobile_sync_vistoria` (`vistoria_id`,`criado_em`),
  KEY `fk_mobile_sync_usuario` (`usuario_id`),
  CONSTRAINT `fk_mobile_sync_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_mobile_sync_vistoria` FOREIGN KEY (`vistoria_id`) REFERENCES `vistorias` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vistoria_mobile_sync`
--

LOCK TABLES `vistoria_mobile_sync` WRITE;
/*!40000 ALTER TABLE `vistoria_mobile_sync` DISABLE KEYS */;
/*!40000 ALTER TABLE `vistoria_mobile_sync` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vistoria_retornos`
--

DROP TABLE IF EXISTS `vistoria_retornos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
  `cancelado_em` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_vistoria_retorno_origem` (`relatorio_origem_id`),
  UNIQUE KEY `uk_vistoria_retorno_agendamento` (`agendamento_id`),
  UNIQUE KEY `uk_vistoria_retorno_resultado` (`relatorio_resultado_id`),
  KEY `idx_vistoria_retornos_status` (`status`),
  KEY `fk_vistoria_retorno_criador` (`criado_por`),
  KEY `fk_vistoria_retorno_cancelador` (`cancelado_por`),
  CONSTRAINT `fk_vistoria_retorno_agendamento` FOREIGN KEY (`agendamento_id`) REFERENCES `agendamentos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_vistoria_retorno_cancelador` FOREIGN KEY (`cancelado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_vistoria_retorno_criador` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_vistoria_retorno_origem` FOREIGN KEY (`relatorio_origem_id`) REFERENCES `vistorias` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_vistoria_retorno_resultado` FOREIGN KEY (`relatorio_resultado_id`) REFERENCES `vistorias` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vistoria_retornos`
--

LOCK TABLES `vistoria_retornos` WRITE;
/*!40000 ALTER TABLE `vistoria_retornos` DISABLE KEYS */;
/*!40000 ALTER TABLE `vistoria_retornos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vistorias`
--

DROP TABLE IF EXISTS `vistorias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vistorias` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT (uuid()),
  `numero` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `embarcacao_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `pessoa_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `armador_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `operador_nome` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `agendamento_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `relatorio_anterior_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `finalidade` enum('VISTORIA','CUMPRIMENTO_EXIGENCIAS') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'VISTORIA',
  `data_vistoria` date NOT NULL,
  `prazo_exigencias_dias` smallint unsigned DEFAULT NULL,
  `data_emissao` date DEFAULT NULL,
  `status` enum('PENDENTE','AGUARDANDO_APROVACAO','APROVADA','APROVADA_COM_EXIGENCIAS','REPROVADA','CANCELADA') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'PENDENTE',
  `relatorio_anterior_ativo_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci GENERATED ALWAYS AS ((case when ((`relatorio_anterior_id` is not null) and (`status` <> _utf8mb4'CANCELADA')) then `relatorio_anterior_id` else NULL end)) VIRTUAL,
  `mobile_versao` int unsigned NOT NULL DEFAULT '0',
  `mobile_finalizada_em` datetime DEFAULT NULL,
  `aprovado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `responsavel_assinatura_id` int DEFAULT NULL,
  `assinatura_status` enum('PENDENTE','ASSINADO','CANCELADO') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'PENDENTE',
  `assinatura_em` datetime DEFAULT NULL,
  `data_aprovacao` datetime DEFAULT NULL,
  `observacao_admin` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `observacoes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `resultado` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `observacoes_tecnicas` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `texto_observacoes_geradas` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `criado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero` (`numero`),
  KEY `embarcacao_id` (`embarcacao_id`),
  KEY `pessoa_id` (`pessoa_id`),
  KEY `criado_por` (`criado_por`),
  KEY `agendamento_id` (`agendamento_id`),
  UNIQUE KEY `uk_vistorias_agendamento_unico` (`agendamento_id`),
  UNIQUE KEY `uk_vistorias_filho_ativo` (`relatorio_anterior_ativo_id`),
  KEY `vistorias_ibfk_aprovado_por` (`aprovado_por`),
  KEY `fk_vistoria_anterior` (`relatorio_anterior_id`),
  KEY `fk_vistorias_armador` (`armador_id`),
  KEY `idx_vistorias_agendamento_vigente` (`agendamento_id`,`criado_em`,`id`),
  CONSTRAINT `fk_vistoria_anterior` FOREIGN KEY (`relatorio_anterior_id`) REFERENCES `vistorias` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_vistorias_armador` FOREIGN KEY (`armador_id`) REFERENCES `clientes` (`id`),
  CONSTRAINT `vistorias_ibfk_1` FOREIGN KEY (`embarcacao_id`) REFERENCES `embarcacoes` (`id`),
  CONSTRAINT `vistorias_ibfk_3` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vistorias_ibfk_agendamento` FOREIGN KEY (`agendamento_id`) REFERENCES `agendamentos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vistorias_ibfk_aprovado_por` FOREIGN KEY (`aprovado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vistorias`
--

LOCK TABLES `vistorias` WRITE;
/*!40000 ALTER TABLE `vistorias` DISABLE KEYS */;
INSERT INTO `vistorias` VALUES ('0587656a-3ef8-40e9-aeb6-6a6ce918e0bd','AM-REL-AP-2/26','09542979-d78e-4095-8ee2-a01e3e7efa07','e82942df-63da-4093-82b7-c2849fe3634e',NULL,NULL,'9203af31-871a-11f1-a50d-aa44e656c57d','4f3bd30c-44b9-47f4-800d-25601773e329','CUMPRIMENTO_EXIGENCIAS','2026-07-30',90,NULL,'AGUARDANDO_APROVACAO',0,NULL,NULL,7,'ASSINADO','2026-07-24 02:10:32',NULL,NULL,NULL,NULL,NULL,NULL,'d2a16613-dfa4-4948-8de4-8c802abdf394','2026-07-24 05:06:03','2026-07-24 05:27:36'),('4f3bd30c-44b9-47f4-800d-25601773e329','AM-REL-V-1/26','09542979-d78e-4095-8ee2-a01e3e7efa07','e82942df-63da-4093-82b7-c2849fe3634e',NULL,NULL,'244a3876-865e-11f1-a50d-aa44e656c57d',NULL,'VISTORIA','2026-07-24',90,NULL,'APROVADA_COM_EXIGENCIAS',0,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4',2,'ASSINADO','2026-07-23 03:50:30','2026-07-23 03:50:30',NULL,NULL,NULL,NULL,NULL,'d2a16613-dfa4-4948-8de4-8c802abdf394','2026-07-23 06:19:27','2026-07-23 06:50:32'),('7a175152-999a-418d-bd14-30d33881910d','AM-REL-AP-1/26','09542979-d78e-4095-8ee2-a01e3e7efa07','e82942df-63da-4093-82b7-c2849fe3634e',NULL,NULL,'c381729a-8666-11f1-a50d-aa44e656c57d','4f3bd30c-44b9-47f4-800d-25601773e329','CUMPRIMENTO_EXIGENCIAS','2026-07-24',90,NULL,'AGUARDANDO_APROVACAO',0,NULL,NULL,7,'ASSINADO','2026-07-23 04:25:52',NULL,NULL,NULL,NULL,NULL,NULL,'d2a16613-dfa4-4948-8de4-8c802abdf394','2026-07-23 07:21:30','2026-07-24 05:27:36');
/*!40000 ALTER TABLE `vistorias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'erp_sistema'
--

--
-- Dumping routines for database 'erp_sistema'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-25  4:20:09
