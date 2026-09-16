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
  KEY `idx_agendamentos_status_data` (`status`,`data_vistoria`),
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
INSERT INTO `agendamentos` VALUES ('04f4aedd-aec9-11f1-8a7c-be2fb1f77be2','029f1f9e-aec9-11f1-8a7c-be2fb1f77be2',NULL,'317ba743-7aa6-4d66-a845-2d4670f126f0','1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed',NULL,NULL,'d2a16613-dfa4-4948-8de4-8c802abdf394','dd121661-feb4-42f6-895a-68eb0608d1e4','Acompanhamento de Ultrassom, Análise de Planos Ec1, Análise de Planos Ec2, Licença Provisória, Vistoria Inicial de Borda Livre, Vistoria Inicial Flutuando, Vistoria Inicial Seco','2026-10-12','07:30:00','belem',NULL,NULL,'pendente','Agendamento gerado automaticamente a partir da aprovação interna da proposta. Favor definir data e vistoriador.','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-12 16:43:01','2026-09-12 16:43:14'),('25be9af2-ad55-11f1-8a7c-be2fb1f77be2','23f7a7d7-ad55-11f1-8a7c-be2fb1f77be2',NULL,'317ba743-7aa6-4d66-a845-2d4670f126f0','1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed',NULL,NULL,'d2a16613-dfa4-4948-8de4-8c802abdf394','dd121661-feb4-42f6-895a-68eb0608d1e4','Vistoria Inicial de Borda Livre, Vistoria Inicial Flutuando, Vistoria Inicial Seco','2026-09-15','04:00:00','belem',NULL,NULL,'concluido','Agendamento gerado automaticamente a partir da aprovação interna da proposta. Favor definir data e vistoriador.','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-10 20:21:04','2026-09-10 20:22:36'),('e1b20145-ebe6-4640-9f1f-f491d004e485','23f7a7d7-ad55-11f1-8a7c-be2fb1f77be2','c3670566-76b2-4376-88b4-234c09701f37','317ba743-7aa6-4d66-a845-2d4670f126f0','1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed',NULL,NULL,'d2a16613-dfa4-4948-8de4-8c802abdf394',NULL,'Cumprimento de A/S','2026-09-24','08:30:00','belem',NULL,NULL,'concluido','Retorno obrigatório para verificar o cumprimento das exigências A/S.','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-10 20:22:54','2026-09-10 20:24:27'),('eccd8fc8-aeda-11f1-8a7c-be2fb1f77be2','eb29b70e-aeda-11f1-8a7c-be2fb1f77be2',NULL,'317ba743-7aa6-4d66-a845-2d4670f126f0','1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed',NULL,NULL,'d2a16613-dfa4-4948-8de4-8c802abdf394','dd121661-feb4-42f6-895a-68eb0608d1e4','Vistoria Inicial de Borda Livre, Vistoria Inicial Flutuando, Vistoria Inicial Seco','2026-09-25','09:00:00','belem',NULL,NULL,'pendente','Agendamento gerado automaticamente a partir da aprovação interna da proposta. Favor definir data e vistoriador.','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-12 18:51:12','2026-09-12 18:51:31');
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
  `analise_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `analista_anterior_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `analista_novo_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `prazo_anterior_em` datetime DEFAULT NULL,
  `prazo_novo_em` datetime NOT NULL,
  `motivo` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `acao` enum('AGENDAMENTO','REAGENDAMENTO','REATRIBUICAO') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `criado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
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
  `item_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `categoria` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `classificacao` enum('RECEBIDO','ACEITO','SUBSTITUIDO','REJEITADO') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'RECEBIDO',
  `justificativa_classificacao` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nome_original` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `extensao` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `mime_type` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tamanho_bytes` bigint unsigned NOT NULL,
  `sha256` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `chave_arquivo` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `criado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `classificado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
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
  `status` enum('PENDENTE','CUMPRIDA','PARCIAL','NAO_CUMPRIDA','TRANSCRITA') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'PENDENTE',
  `transcricao_admissivel` tinyint(1) NOT NULL DEFAULT '0',
  `fundamento_transcricao` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `analise_planos_historico`
--

LOCK TABLES `analise_planos_historico` WRITE;
/*!40000 ALTER TABLE `analise_planos_historico` DISABLE KEYS */;
INSERT INTO `analise_planos_historico` VALUES (1,'f3956e7b-4005-4dc8-8162-a64df98c6721','dd121661-feb4-42f6-895a-68eb0608d1e4','DEMANDA_CRIADA',NULL,'AGUARDANDO_AGENDAMENTO','Criada automaticamente pela proposta AM-ORC-2/26','2026-09-12 16:43:02'),(2,'9520ddf8-5c36-4726-bcf1-765eaabf3e4e','dd121661-feb4-42f6-895a-68eb0608d1e4','DEMANDA_CRIADA',NULL,'AGUARDANDO_AGENDAMENTO','Criada automaticamente pela proposta AM-ORC-2/26','2026-09-12 16:43:02');
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
  `versao_normativa` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
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
  `numero` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `analise_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `versao` int unsigned NOT NULL,
  `finalidade` enum('ANALISE_INICIAL','CUMPRIMENTO_EXIGENCIAS','CONCLUSIVO') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `submissao_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `relatorio_anterior_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `norma_versao_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `resultado` enum('EXIGENCIAS','APROVADO','APROVADO_COM_EXIGENCIAS','REPROVADO') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `resumo` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `conclusao` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `snapshot_json` json DEFAULT NULL,
  `status` enum('MINUTA','AGUARDANDO_ASSINATURA_ANALISTA','AGUARDANDO_APROVACAO_ADMIN','PUBLICADO','DEVOLVIDO','CANCELADO') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'MINUTA',
  `responsavel_assinatura_id` int DEFAULT NULL,
  `assinado_analista_em` datetime DEFAULT NULL,
  `assinatura_analista_ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `devolvido_motivo` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `caminho_pdf_final` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `hash_pdf_final` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `publicado_em` datetime DEFAULT NULL,
  `validado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
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
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `relatorio_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `exigencia_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `submissao_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `resultado` enum('CUMPRIDA','PARCIAL','NAO_CUMPRIDA') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `manifestacao_tecnica` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `descricao_snapshot` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `referencia_snapshot` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `criado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
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
  `origem` enum('ANALISTA','PORTAL') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'ANALISTA',
  `portal_cliente_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `criado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
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
  `proposta_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `servico_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `vendedor_origem_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `embarcacao_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `solicitante_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tipo_processo` enum('LC','LCEC','LA','LR') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `enquadramento` enum('NORMAM-201','NORMAM-202') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `norma_versao_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `classe_certificacao` enum('EC1','EC2') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `arqueacao_bruta` decimal(10,2) DEFAULT NULL,
  `numero_passageiros` int unsigned DEFAULT NULL,
  `possui_propulsao` tinyint(1) DEFAULT NULL,
  `embarcacao_classificada` tinyint(1) DEFAULT NULL,
  `tipo_navegacao` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `construcao_concluida` tinyint(1) DEFAULT NULL,
  `objeto` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `estaleiro` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `numero_casco` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `responsavel_projeto_nome` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `responsavel_projeto_registro` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `art_numero` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `analista_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `prazo_agendado_em` datetime DEFAULT NULL,
  `iniciado_em` datetime DEFAULT NULL,
  `legado_sem_proposta` tinyint(1) NOT NULL DEFAULT '0',
  `legado_fora_escopo` tinyint(1) NOT NULL DEFAULT '0',
  `fundamento_bloqueio` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `responsavel_assinatura_id` int DEFAULT NULL,
  `status` enum('AGUARDANDO_AGENDAMENTO','AGENDADA','EM_ANALISE','AGUARDANDO_DOCUMENTOS','AGUARDANDO_ASSINATURA_ANALISTA','AGUARDANDO_APROVACAO_ADMIN','CONCLUIDA','REPROVADA','CANCELADA') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'AGUARDANDO_AGENDAMENTO',
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
  KEY `idx_analises_planos_analista_status` (`analista_id`,`status`),
  KEY `idx_analises_planos_status_prazo` (`status`,`prazo_agendado_em`),
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
INSERT INTO `analises_planos` VALUES ('9520ddf8-5c36-4726-bcf1-765eaabf3e4e','AM-RAP-2/26','029f1f9e-aec9-11f1-8a7c-be2fb1f77be2','a1d98b0e-6ebc-11f1-86ce-7e17ff5f90bf','dd121661-feb4-42f6-895a-68eb0608d1e4','317ba743-7aa6-4d66-a845-2d4670f126f0','1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed',NULL,NULL,NULL,'EC2',NULL,NULL,NULL,NULL,NULL,NULL,'Análise de planos EC2',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'AGUARDANDO_AGENDAMENTO',NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-12 16:43:02','2026-09-12 16:43:02'),('f3956e7b-4005-4dc8-8162-a64df98c6721','AM-RAP-1/26','029f1f9e-aec9-11f1-8a7c-be2fb1f77be2','a1d980bd-6ebc-11f1-86ce-7e17ff5f90bf','dd121661-feb4-42f6-895a-68eb0608d1e4','317ba743-7aa6-4d66-a845-2d4670f126f0','1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed',NULL,NULL,NULL,'EC1',NULL,NULL,NULL,NULL,NULL,NULL,'Análise de planos EC1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'AGUARDANDO_AGENDAMENTO',NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-12 16:43:02','2026-09-12 16:43:02');
/*!40000 ALTER TABLE `analises_planos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assinatura_convites`
--

DROP TABLE IF EXISTS `assinatura_convites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assinatura_convites` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `documento_tipo` enum('CSN','CNBL','CNARQ') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `documento_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `responsavel_id` int NOT NULL,
  `usuario_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `token_hash` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `email_destinatario` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('ATIVO','PROCESSANDO','UTILIZADO','CANCELADO') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'ATIVO',
  `autenticacao_metodo` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'EMAIL_MAGIC_LINK',
  `expira_em` datetime NOT NULL,
  `enviado_em` datetime DEFAULT NULL,
  `utilizado_em` datetime DEFAULT NULL,
  `cancelado_em` datetime DEFAULT NULL,
  `cancelado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `aprovacao_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assinatura_convites`
--

LOCK TABLES `assinatura_convites` WRITE;
/*!40000 ALTER TABLE `assinatura_convites` DISABLE KEYS */;
INSERT INTO `assinatura_convites` VALUES ('0451c9be-1472-461e-b50b-f4c22845a6b0','CSN','9f9a16b3-0b78-4f35-a2a6-8476b42dbd8e',2,'dd121661-feb4-42f6-895a-68eb0608d1e4','297d14b43498be816d2011c7a7c7169b56d6fa90cead3c1277ab594835e69a16','neto@amazonnaval.com.br','ATIVO','EMAIL_MAGIC_LINK','2026-09-22 21:32:21',NULL,NULL,NULL,NULL,NULL,'2026-09-16 00:32:21','2026-09-16 00:32:21'),('065f3d9a-c460-4f56-8684-8d14870554bd','CSN','503924ae-041f-4d64-bc53-686a119e85e2',2,'dd121661-feb4-42f6-895a-68eb0608d1e4','1e975a67996c0a5443fc4458efee075a692a66283469da17496cb7fe8d8a7bfe','neto@amazonnaval.com.br','ATIVO','EMAIL_MAGIC_LINK','2026-09-22 21:28:10',NULL,NULL,NULL,NULL,NULL,'2026-09-16 00:28:10','2026-09-16 00:28:10'),('0c224862-8d64-41a0-b129-7a32b302c597','CSN','7ee27829-c4d9-42da-812c-e7465023f3f0',2,'dd121661-feb4-42f6-895a-68eb0608d1e4','02f716c320722a5b0ddb438a158c181a0118fda81e8f59c97249d8f4633468fb','neto@amazonnaval.com.br','ATIVO','EMAIL_MAGIC_LINK','2026-09-23 01:03:09',NULL,NULL,NULL,NULL,NULL,'2026-09-16 04:03:09','2026-09-16 04:03:09'),('18445ff8-1692-4edd-80b9-081894009513','CSN','89624ca3-49e4-405f-a707-967113025cb6',2,'dd121661-feb4-42f6-895a-68eb0608d1e4','f6c5315764cbaed9d9b0fd27da9a71e0db00b2e7e429a6298d218c41323fd38e','neto@amazonnaval.com.br','ATIVO','EMAIL_MAGIC_LINK','2026-09-22 21:32:48',NULL,NULL,NULL,NULL,NULL,'2026-09-16 00:32:48','2026-09-16 00:32:48'),('1cc98e66-8f44-4265-8daf-23afaa4c909f','CSN','9919ec2d-074f-451c-9a61-4ff1918969ee',2,'dd121661-feb4-42f6-895a-68eb0608d1e4','3cb212c8013838463c947370b30eed4f68cf66df43b704b92b3be4d4c234bff8','neto@amazonnaval.com.br','ATIVO','EMAIL_MAGIC_LINK','2026-09-22 21:36:12',NULL,NULL,NULL,NULL,NULL,'2026-09-16 00:36:12','2026-09-16 00:36:12'),('1df313fa-56aa-4fd4-b3fd-f04a418a524e','CSN','42104e8d-41d3-4172-b5f5-ee6e5d9246a3',2,'dd121661-feb4-42f6-895a-68eb0608d1e4','f7c1f1722f758472b74fbc806d29083d43d9d532c978000d69b439a0f202436f','neto@amazonnaval.com.br','ATIVO','EMAIL_MAGIC_LINK','2026-09-22 21:33:56',NULL,NULL,NULL,NULL,NULL,'2026-09-16 00:33:56','2026-09-16 00:33:56'),('28cbdcb9-677f-447e-a8a9-d920455e1a1b','CSN','d2bbd9a7-c7e4-4b2d-a9e6-15c9e07d2b85',2,'dd121661-feb4-42f6-895a-68eb0608d1e4','a276e9e3a7bfb59a144421ff08322856a506e50188bf17529921730f65845ec8','neto@amazonnaval.com.br','ATIVO','EMAIL_MAGIC_LINK','2026-09-22 23:14:59',NULL,NULL,NULL,NULL,NULL,'2026-09-16 02:14:59','2026-09-16 02:14:59'),('30cec36a-57ed-45d0-886b-e4f56027cec2','CSN','278a3508-69c6-4ca7-b8a9-62feef7ec7fd',2,'dd121661-feb4-42f6-895a-68eb0608d1e4','de635c0450e546cb095875a4ba64aeacf864fe9320cf07b5a0fb9bbef5091bf1','neto@amazonnaval.com.br','ATIVO','EMAIL_MAGIC_LINK','2026-09-22 22:04:42',NULL,NULL,NULL,NULL,NULL,'2026-09-16 01:04:42','2026-09-16 01:04:42'),('3f4cba8d-897c-42aa-aabb-fdd86cea5ebb','CSN','ddb10c9c-ed07-4b76-bab5-4f0914c7b8d7',2,'dd121661-feb4-42f6-895a-68eb0608d1e4','522760e3e9b7fa0c34e3b3039072c11561be8d941856bf34190c37f0e251b501','neto@amazonnaval.com.br','ATIVO','EMAIL_MAGIC_LINK','2026-09-22 21:36:32',NULL,NULL,NULL,NULL,NULL,'2026-09-16 00:36:32','2026-09-16 00:36:32'),('4c5c986f-3b09-475e-a800-e9dce5235f16','CSN','0f670e3a-4da5-4bbe-ac13-51f8d45e69a1',2,'dd121661-feb4-42f6-895a-68eb0608d1e4','040568062a80bbe25f5aaf072ea190197d8e9fa38e1da9c2bd5e6c76c965db7e','neto@amazonnaval.com.br','ATIVO','EMAIL_MAGIC_LINK','2026-09-22 21:34:54',NULL,NULL,NULL,NULL,NULL,'2026-09-16 00:34:54','2026-09-16 00:34:54'),('5474a0cf-ef0f-460c-b387-31eecd3a0431','CSN','848b2e8e-dd17-4165-bcdd-de580acad438',2,'dd121661-feb4-42f6-895a-68eb0608d1e4','3b213f2ce30a29e25699e6e14be8ab91ace48907958af009e2957d4291c76819','neto@amazonnaval.com.br','ATIVO','EMAIL_MAGIC_LINK','2026-09-23 00:25:03',NULL,NULL,NULL,NULL,NULL,'2026-09-16 03:25:03','2026-09-16 03:25:03'),('69881744-3f59-40f3-a3ef-c54af98bb275','CSN','cb1a7f1c-e09d-44c6-bc24-16ff26c35b98',2,'dd121661-feb4-42f6-895a-68eb0608d1e4','a0e45a0a122f52098921e9cb561abc3e41b978308b72ca432e755dbe9f6dcc5e','neto@amazonnaval.com.br','ATIVO','EMAIL_MAGIC_LINK','2026-09-23 00:07:37',NULL,NULL,NULL,NULL,NULL,'2026-09-16 03:07:37','2026-09-16 03:07:37'),('7ade7832-be58-4fc7-a35d-215ceb8e608a','CSN','fe44beee-1a41-4158-9c9c-0e0bc24539ae',2,'dd121661-feb4-42f6-895a-68eb0608d1e4','e877d8da893167cb1fd7fd9d0054edad07ab04f8da6e0d09d45590c5e219f4af','neto@amazonnaval.com.br','ATIVO','EMAIL_MAGIC_LINK','2026-09-23 00:23:20',NULL,NULL,NULL,NULL,NULL,'2026-09-16 03:23:20','2026-09-16 03:23:20'),('7f340041-90de-4698-b278-8b391a5b517c','CSN','f7978868-1a3b-446f-a57c-21b344e85f82',2,'dd121661-feb4-42f6-895a-68eb0608d1e4','42586dfe8cca9a41979b5b41ed13480dc0b7cbc5b35bd07cd2499dd08b378278','neto@amazonnaval.com.br','ATIVO','EMAIL_MAGIC_LINK','2026-09-22 21:31:06',NULL,NULL,NULL,NULL,NULL,'2026-09-16 00:31:06','2026-09-16 00:31:06'),('84fd34ca-de3b-4204-9008-c209006431a7','CSN','3841d99d-db30-40ed-b71b-04392111ffd7',2,'dd121661-feb4-42f6-895a-68eb0608d1e4','392f7d6cdb506c671111ca565a5f54280eb544386094267b68a0c796f5cabafd','neto@amazonnaval.com.br','ATIVO','EMAIL_MAGIC_LINK','2026-09-22 21:31:56',NULL,NULL,NULL,NULL,NULL,'2026-09-16 00:31:56','2026-09-16 00:31:56'),('9fa0920e-5e37-4107-9fd9-6c8a85e9ed69','CSN','faa23877-c468-4114-9182-3b6157402f0f',2,'dd121661-feb4-42f6-895a-68eb0608d1e4','8bd0f7ace8d12ec732e417c58a885ae6b7001cf9d15effa84c341d5d09793364','neto@amazonnaval.com.br','ATIVO','EMAIL_MAGIC_LINK','2026-09-17 17:25:10',NULL,NULL,NULL,NULL,NULL,'2026-09-10 20:25:10','2026-09-10 20:25:10'),('a346c906-c298-4bf9-a7ff-4ec6c7da8462','CSN','bf94ed71-44ac-4e02-81dc-66ef89a1893b',2,'dd121661-feb4-42f6-895a-68eb0608d1e4','f3638812527dd4299b2e5276077f82d353891fb1af01c566f8af7c43f2d19062','neto@amazonnaval.com.br','ATIVO','EMAIL_MAGIC_LINK','2026-09-23 00:05:52',NULL,NULL,NULL,NULL,NULL,'2026-09-16 03:05:52','2026-09-16 03:05:52'),('ae00f8e5-43ba-4865-aba2-0124be88830c','CSN','4aee2c61-4fb2-498c-8d0f-06d7cadb3e2b',2,'dd121661-feb4-42f6-895a-68eb0608d1e4','6534567db7aa58470c6af370455e0bb3724cfa8349af24f4ec0f7412f3b3ac3c','neto@amazonnaval.com.br','ATIVO','EMAIL_MAGIC_LINK','2026-09-22 21:32:50',NULL,NULL,NULL,NULL,NULL,'2026-09-16 00:32:50','2026-09-16 00:32:50'),('c1387206-aaf8-4e50-bbca-eaa152a41bb9','CSN','76558869-53c9-426e-9a36-92c8c181d393',2,'dd121661-feb4-42f6-895a-68eb0608d1e4','bd74916a8315f03c294f413968f0e9bd34f278e957250f2a55242a11194ad9ac','neto@amazonnaval.com.br','ATIVO','EMAIL_MAGIC_LINK','2026-09-22 21:48:02',NULL,NULL,NULL,NULL,NULL,'2026-09-16 00:48:02','2026-09-16 00:48:02'),('d9a6e960-4a1c-476b-b0bf-11ef94ee001c','CSN','43cd7f0b-99d5-4ccf-bb5a-927ab849c452',2,'dd121661-feb4-42f6-895a-68eb0608d1e4','2a204cf86908cf1449af7a2e914569562738eda4d15b0ed302794a8ca2e8a5eb','neto@amazonnaval.com.br','ATIVO','EMAIL_MAGIC_LINK','2026-09-22 21:30:43',NULL,NULL,NULL,NULL,NULL,'2026-09-16 00:30:43','2026-09-16 00:30:43'),('dd2b3e3b-6fb3-4e68-a798-bb82efc8f206','CSN','e5153da4-c0c3-4919-b4ef-5f6c2e1924df',2,'dd121661-feb4-42f6-895a-68eb0608d1e4','b605c2ddbab77f0a2013cd5cbf8b37b41f374051ac955feb111c042a980c3df5','neto@amazonnaval.com.br','ATIVO','EMAIL_MAGIC_LINK','2026-09-22 21:30:00',NULL,NULL,NULL,NULL,NULL,'2026-09-16 00:30:00','2026-09-16 00:30:00'),('e7dccfc3-b746-47db-803c-77ee69ae2a93','CSN','2e0d12e3-729f-482e-bc5c-806e01223ee2',2,'dd121661-feb4-42f6-895a-68eb0608d1e4','4bd723a9bfc4c908d7c0eeb9e16e5b4cbebb955138f137344d688a54de8b815e','neto@amazonnaval.com.br','ATIVO','EMAIL_MAGIC_LINK','2026-09-22 21:57:31',NULL,NULL,NULL,NULL,NULL,'2026-09-16 00:57:31','2026-09-16 00:57:31'),('f7082d72-e316-47e2-9083-08a10180cf40','CSN','b01a38f6-ce85-4ca0-a07b-0210984174fd',2,'dd121661-feb4-42f6-895a-68eb0608d1e4','7ea0429f8f0d206da04bc5c1b6f32400cdb3a5b2ca7f57a495a032f5229c3fb2','neto@amazonnaval.com.br','ATIVO','EMAIL_MAGIC_LINK','2026-09-22 21:34:28',NULL,NULL,NULL,NULL,NULL,'2026-09-16 00:34:28','2026-09-16 00:34:28'),('f8787b9e-ff4b-4511-bc06-4e70ede99618','CSN','fba9ad42-4d4e-4f21-ae14-a33f7cd5609c',2,'dd121661-feb4-42f6-895a-68eb0608d1e4','068c10ed63a3be0b7694c6d5ab5b38ea31f662095ff1b51c7760b8e2b9ac707d','neto@amazonnaval.com.br','ATIVO','EMAIL_MAGIC_LINK','2026-09-22 21:31:27',NULL,NULL,NULL,NULL,NULL,'2026-09-16 00:31:27','2026-09-16 00:31:27'),('f983c065-7aab-4a2b-bd6f-0099fd0f50ba','CSN','ba94f512-15d0-43fb-b0b1-cc6174886085',2,'dd121661-feb4-42f6-895a-68eb0608d1e4','9bc3d710f5d1e151b740feb75678857339f63a5a31a6bb4936359113140e02d5','neto@amazonnaval.com.br','ATIVO','EMAIL_MAGIC_LINK','2026-09-22 21:34:25',NULL,NULL,NULL,NULL,NULL,'2026-09-16 00:34:25','2026-09-16 00:34:25');
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
  `entidade` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `entidade_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `evento` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `usuario_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `perfil` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estado_anterior` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estado_novo` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `norma_versao_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fundamento` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
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
  `email_hash` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ip_hash` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `sucesso` tinyint(1) NOT NULL DEFAULT '0',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_campo_login_bloqueio` (`email_hash`,`ip_hash`,`criado_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
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
  `embarcacao_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cliente_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
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
  KEY `idx_cht_embarcacao` (`embarcacao_id`),
  KEY `idx_cht_cliente` (`cliente_id`),
  KEY `idx_cht_ativo_status_emissao` (`ativo`,`status`,`data_emissao`),
  CONSTRAINT `fk_cht_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_cht_embarcacao` FOREIGN KEY (`embarcacao_id`) REFERENCES `embarcacoes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
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
  `embarcacao_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cliente_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
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
  `despachante_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_certificados_cnarq_numero` (`numero`),
  KEY `idx_certificados_cnarq_status` (`status`),
  KEY `idx_certificados_cnarq_ativo` (`ativo`),
  KEY `fk_cnarq_vistoria` (`vistoria_id`),
  KEY `idx_cnarq_embarcacao` (`embarcacao_id`),
  KEY `idx_cnarq_cliente` (`cliente_id`),
  KEY `idx_cnarq_ativo_status_emissao` (`ativo`,`status`,`data_emissao`),
  CONSTRAINT `fk_cnarq_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_cnarq_embarcacao` FOREIGN KEY (`embarcacao_id`) REFERENCES `embarcacoes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
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
  `embarcacao_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cliente_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
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
  `despachante_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_certificados_cnbl_numero` (`numero`),
  KEY `idx_certificados_cnbl_status` (`status`),
  KEY `idx_certificados_cnbl_ativo` (`ativo`),
  KEY `fk_cnbl_vistoria` (`vistoria_id`),
  KEY `idx_cnbl_embarcacao` (`embarcacao_id`),
  KEY `idx_cnbl_cliente` (`cliente_id`),
  KEY `idx_cnbl_ativo_status_emissao` (`ativo`,`status`,`data_emissao`),
  CONSTRAINT `fk_cnbl_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_cnbl_embarcacao` FOREIGN KEY (`embarcacao_id`) REFERENCES `embarcacoes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
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
  `embarcacao_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cliente_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
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
  `despachante_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero` (`numero`),
  UNIQUE KEY `token_assinatura` (`token_assinatura`),
  KEY `criado_por` (`criado_por`),
  KEY `fk_csn_vistoria` (`vistoria_id`),
  KEY `idx_csn_embarcacao` (`embarcacao_id`),
  KEY `idx_csn_cliente` (`cliente_id`),
  KEY `idx_csn_ativo_status_emissao` (`ativo`,`status`,`data_emissao`),
  CONSTRAINT `certificados_csn_ibfk_1` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_csn_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_csn_embarcacao` FOREIGN KEY (`embarcacao_id`) REFERENCES `embarcacoes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_csn_vistoria` FOREIGN KEY (`vistoria_id`) REFERENCES `vistorias` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `certificados_csn`
--

LOCK TABLES `certificados_csn` WRITE;
/*!40000 ALTER TABLE `certificados_csn` DISABLE KEYS */;
INSERT INTO `certificados_csn` VALUES ('2e0d12e3-729f-482e-bc5c-806e01223ee2','317ba743-7aa6-4d66-a845-2d4670f126f0','1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed','AM-CSN-2/26','Provisório','c80025b75fd5ecae5f343d2c92bef7913b7dbb597ad9da3fd17f18de3d802cfc','AMAZON NAVAL','barcoteste14','','','','Balsa','',18.50,'45.00','','','','','',0,0,'','','AM-REL-V-1/26 e AM-REL-V-2/26','2026-09-24','2026-09-24','belem','NORMAM-202','Inicial',0,1,'2026-09-15','2026-12-23','Belém-PA','Victal Donanzan','Engenheiro Naval','CREA: 22.537',2,'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAA4QAAAEsCAYAAACbnn2RAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAgAElEQVR4nOzd2bMk130n9m+ulbXeqrv3vqKxNgiCmyhKoqgZWSONYhR2KLw8eCYcdvjBf4b/Az85YiIc4Ziw/TC2NLZG0kjUjESRIgkSIIi90ehG77fvvtReuR4/ZN7fOXmBhhogerm3vp8H8hZO3dtVWVmZefL8FkspBSIiIiIiIpo+9pN+AURERERERPRkcEJIREREREQ0pTghJCIiIiIimlKcEBIREREREU0pTgiJiIiIiIimFCeEREREREREU4oTQiIiIiIioinFCSEREREREdGU4oSQiIiIiIhoSnFCSERERERENKU4ISQiIiIiIppSnBASERERERFNKU4IiYiIiIiIphQnhERERERERFOKE0IiIiIiIqIpxQkhERERERHRlOKEkIiIiIiIaEq5T/oFfNmUUupJvwYiIiIiIjp6LMuynvRr+LJxhZCIiIiIiGhKcUJIREREREQ0pTghJCIiIiIimlKcEBIREREREU0pTgiJiIiIiIimFCeEREREREREU4oTQiIiIiIioinFCSEREREREdGU4oSQiIiIiIhoSnFCSERERERENKU4ISQiIiIiIppSnBASERERERFNKU4IiYiIiIiIphQnhERERERERFOKE0IiIiIiIqIpxQkhERERERHRlOKEkIiIiIiIaEpxQkhERERERDSlOCEkIiIiIiKaUpwQEhERERERTSlOCImIiIiIiKYUJ4RERERERERTihNCIiIiIiKiKcUJIRERERER0ZTihJCIiIiIiGhKcUJIREREREQ0pTghJCIiIiIimlKcEBIREREREU0pTgiJiIiIiIimFCeEREREREREU4oTQiIiIiIioinFCSEREREREdGU4oSQiIiIiIhoSnFCSERERERENKU4ISQiIiIiIppSnBASERERERFNKU4IiYiIiIiIphQnhERERERERFOKE0IiIiIiIqIpxQkhERERERHRlHKf9AsgIpp2UZJBFT/v9SPs9iMZizMlPy+2A8w1fXnsOtbjeolERER0RHFCSET0hPVGiUwI37y2i9c/3JKx3TCTn//Zq8v49vNzAADLAppVHsKJiIjoV8OQUSIiIiIioinFCSEREREREdGUYrwREdFjNhyn6I1i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','172.23.0.1','2026-09-15 21:57:57',1,'storage/documentos_aprovados/2026/csn/c429d229-d0e6-4e3f-ad90-9cb86761f3de.pdf','531e81d84e107eb30adf93d68cc94a365296587a8918ff6edb2ffb8176848f37','assinado',1,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:57:31','2026-09-16 00:57:58','c0cc81fc-b11d-40a6-b8f8-f410d3c1982a',NULL),('faa23877-c468-4114-9182-3b6157402f0f','317ba743-7aa6-4d66-a845-2d4670f126f0','1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed','AM-CSN-1/26','Provisório','39d2aa456b3da2b48c52a58ab6cd3819da22faddb22dcf65b27f309544ffa64e','AMAZON NAVAL','barcoteste14','','','','Balsa','',NULL,'','','','','','',0,0,'','','AM-REL-V-1/26 e AM-REL-V-2/26','2026-09-24','2026-09-24','belem','NORMAM-202','Inicial',0,1,'2026-09-10','2026-12-23','Belém-PA','Victal Donanzan','Engenheiro Naval','CREA: 22.537',2,'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAA4QAAAEsCAYAAACbnn2RAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAgAElEQVR4nOzd2bMk130n9m+ulbXeqrv3vqKxNgiCmyhKoqgZWSONYhR2KLw8eCYcdvjBf4b/Az85YiIc4Ziw/TC2NLZG0kjUjESRIgkSIIi90ehG77fvvtReuR4/ZN7fOXmBhhogerm3vp8H8hZO3dtVWVmZefL8FkspBSIiIiIiIpo+9pN+AURERERERPRkcEJIREREREQ0pTghJCIiIiIimlKcEBIREREREU0pTgiJiIiIiIimFCeEREREREREU4oTQiIiIiIioinFCSEREREREdGU4oSQiIiIiIhoSnFCSERERERENKU4ISQiIiIiIppSnBASERERERFNKU4IiYiIiIiIphQnhERERERERFOKE0IiIiIiIqIpxQkhERERERHRlOKEkIiIiIiIaEq5T/oFfNmUUupJvwYiIiIiIjp6LMuynvRr+LJxhZCIiIiIiGhKcUJIREREREQ0pTghJCIiIiIimlKcEBIREREREU0pTgiJiIiIiIimFCeEREREREREU4oTQiIiIiIioinFCSEREREREdGU4oSQiIiIiIhoSnFCSERERERENKU4ISQiIiIiIppSnBASERERERFNKU4IiYiIiIiIphQnhERERERERFOKE0IiIiIiIqIpxQkhERERERHRlOKEkIiIiIiIaEpxQkhERERERDSlOCEkIiIiIiKaUpwQEhERERERTSlOCImIiIiIiKYUJ4RERERERERTihNCIiIiIiKiKcUJIRERERER0ZTihJCIiIiIiGhKcUJIREREREQ0pTghJCIiIiIimlKcEBIREREREU0pTgiJiIiIiIimFCeEREREREREU4oTQiIiIiIioinFCSEREREREdGU4oSQiIiIiIhoSnFCSERERERENKU4ISQiIiIiIppSnBASERERERFNKU4IiYiIiIiIphQnhERERERERFOKE0IiIiIiIqIpxQkhERERERHRlHKf9AsgIpp2UZJBFT/v9SPs9iMZizMlPy+2A8w1fXnsOtbjeolERER0RHFCSET0hPVGiUwI37y2i9c/3JKx3TCTn//Zq8v49vNzAADLAppVHsKJiIjoV8OQUSIiIiIioinFCSEREREREdGUYrwREdFjNhyn6I1i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','172.23.0.1','2026-09-10 17:25:32',1,'storage/documentos_aprovados/2026/csn/e7d82b3a-09f0-41bd-bc83-77fed4f6ae63.pdf','e960cc7748271fe92db5fdfa5e52083e89ffc8b5367b4be060646102fbe78ec6','assinado',1,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-10 20:25:09','2026-09-13 20:01:52','c0cc81fc-b11d-40a6-b8f8-f410d3c1982a',NULL);
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
  `cliente_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
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
  `analise_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `despachante_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_licenca_analise` (`analise_id`),
  KEY `idx_certificados_lc_numero` (`numero_lc`),
  KEY `idx_certificados_lc_status` (`status`),
  KEY `idx_certificados_lc_ativo` (`ativo`),
  KEY `idx_certificados_lc_embarcacao` (`embarcacao_id`),
  KEY `idx_certificados_lc_tipo` (`tipo_licenca`),
  KEY `fk_lc_vistoria` (`vistoria_id`),
  KEY `idx_lc_cliente` (`cliente_id`),
  KEY `idx_lc_ativo_status_emissao` (`ativo`,`status`,`data_emissao`),
  CONSTRAINT `fk_lc_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_lc_embarcacao` FOREIGN KEY (`embarcacao_id`) REFERENCES `embarcacoes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
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
  `cliente_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
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
  KEY `idx_lp_cliente` (`cliente_id`),
  KEY `idx_lp_ativo_status_emissao` (`ativo`,`status`,`data_emissao`),
  CONSTRAINT `fk_lp_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_lp_embarcacao` FOREIGN KEY (`embarcacao_id`) REFERENCES `embarcacoes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
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
INSERT INTO `cliente_portal_acessos` VALUES ('1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed','anykedas@gmail.com','$2y$10$yeiF7SXQA4XDKLpKDtoY6udowXxgf3qOQ/uV3ee91d6NDDmjdOj.a',1,0,'2026-09-12 05:11:40','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-12 03:30:55','2026-09-12 06:45:07');
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
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `tipo_recebimento` enum('pix','cc') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `chave_pix` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `banco` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `agencia` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `conta` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `criado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `excluido_em` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cpf_cnpj` (`cpf_cnpj`),
  KEY `criado_por` (`criado_por`),
  KEY `idx_clientes_ativo_excluido` (`ativo`,`excluido_em`),
  CONSTRAINT `clientes_ibfk_1` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clientes`
--

LOCK TABLES `clientes` WRITE;
/*!40000 ALTER TABLE `clientes` DISABLE KEYS */;
INSERT INTO `clientes` VALUES ('1041bc33-843c-4d82-8a09-4f413cfaddcf','Armador Solimões Web Test 2673','PJ','86091655000138','armador','(92) 99123-4567','web_7136@solimoes.com.br','Av. Manaus Moderna, 500 - Centro, Manaus/AM','ATIVO',1,NULL,NULL,NULL,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:45:26','2026-09-16 00:45:26',NULL),('14494531-8ed0-4823-9398-c75c149a8f19','Armador Solimões Web Test 1088','PJ','91356310000107','armador','(92) 99123-4567','web_8336@solimoes.com.br','Av. Manaus Moderna, 500 - Centro, Manaus/AM','ATIVO',1,NULL,NULL,NULL,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:44:41','2026-09-16 00:44:41',NULL),('1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed','Rosano Souza','PF','38303451863','proprietario','(91) 84235-3456','anykedas@gmail.com',NULL,'ATIVO',1,NULL,NULL,NULL,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-10 20:19:42','2026-09-10 20:19:42',NULL),('65d29f6f-5978-4157-9cb1-8d6c9d49d124','Armador Solimões Web Test 4374','PJ','37800516000120','armador','(92) 99123-4567','web_5875@solimoes.com.br','Av. Manaus Moderna, 500 - Centro, Manaus/AM','ATIVO',1,NULL,NULL,NULL,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:45:09','2026-09-16 00:45:09',NULL),('70781264-1186-4baa-9883-a7e6adc272b1','Proprietário Naval Teste','PF','123.456.789-00','proprietario','(92) 99111-2222','proprietario.teste@sistema.com.br','Rua das Palmeiras, 100, Parintins/AM','INATIVO',0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-15 19:07:24','2026-09-15 19:07:24','2026-09-15 19:07:24'),('7e3eef23-baa2-493e-bf1b-777f918549bf','Armador Solimões Web Test 8819','PJ','83695428000123','armador','(92) 99123-4567','web_9519@solimoes.com.br','Av. Manaus Moderna, 500 - Centro, Manaus/AM','ATIVO',1,NULL,NULL,NULL,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:45:35','2026-09-16 00:45:35',NULL),('ad4accaa-b248-4e07-b774-4af53b35433f','Armador Solimões Web Test 9998','PJ','13041981000167','armador','(92) 99123-4567','web_3909@solimoes.com.br','Av. Manaus Moderna, 500 - Centro, Manaus/AM','ATIVO',1,NULL,NULL,NULL,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:42:50','2026-09-16 00:42:50',NULL),('c3258332-9b51-4ed5-bf8d-80157cfe45b6','Despachante Marítimo Expresso','PF','987.654.321-99','despachante','(92) 98444-5555','despachante.teste@sistema.com.br','Rua Tamandaré, 45, Manaus/AM','ATIVO',1,'pix','987.654.321-99','Banco do Brasil','1234-5','98765-4',NULL,'2026-09-15 19:07:24','2026-09-15 19:07:24',NULL),('cb63d1fd-d0b3-47c0-abf1-6914808b8ff1','Armador Solimões Web Test 4663','PJ','46300277000130','armador','(92) 99123-4567','web_4147@solimoes.com.br','Av. Manaus Moderna, 500 - Centro, Manaus/AM','ATIVO',1,NULL,NULL,NULL,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:41:32','2026-09-16 00:41:32',NULL),('d9a02d20-b84c-43c4-a87c-59fc9f18760d','Armador Solimões Web Test 1374','PJ','32570538000128','armador','(92) 99123-4567','web_1809@solimoes.com.br','Av. Manaus Moderna, 500 - Centro, Manaus/AM','ATIVO',1,NULL,NULL,NULL,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:46:12','2026-09-16 00:46:12',NULL),('f4534ddc-3966-43ec-aa74-70fcad4bcd35','Armador Solimões Web Test 4081','PJ','52684611000108','armador','(92) 99123-4567','web_8829@solimoes.com.br','Av. Manaus Moderna, 500 - Centro, Manaus/AM','ATIVO',1,NULL,NULL,NULL,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:40:53','2026-09-16 00:40:53',NULL);
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
INSERT INTO `clientes_embarcacoes` VALUES ('3c1409ab-b168-11f1-8a7c-be2fb1f77be2','1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed','234aaec1-d1dd-4872-94b3-f52c61b04316','ATIVO','2026-09-16 00:47:46',NULL,NULL,NULL,'1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed:234aaec1-d1dd-4872-94b3-f52c61b04316','2026-09-16 00:47:46'),('f4f6e2f8-ad54-11f1-8a7c-be2fb1f77be2','1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed','317ba743-7aa6-4d66-a845-2d4670f126f0','ATIVO','2026-09-10 20:19:42',NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4',NULL,'1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed:317ba743-7aa6-4d66-a845-2d4670f126f0','2026-09-10 20:19:42');
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
INSERT INTO `clientes_tipos_embarcacao` VALUES ('c3258332-9b51-4ed5-bf8d-80157cfe45b6','06a95b60-75d0-11f1-98f0-5ed0db5eacb7','2026-09-15 19:07:24'),('c3258332-9b51-4ed5-bf8d-80157cfe45b6','06a95eb2-75d0-11f1-98f0-5ed0db5eacb7','2026-09-15 19:07:24');
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
INSERT INTO `configuracoes` VALUES ('acesso_documentacao_usuarios','[3774]','IDs dos usuários com acesso à documentação','2026-06-29 06:38:14'),('backup_email','ronokedas2020@gmail.com','E-mail para receber backups do banco de dados','2026-06-29 05:22:15'),('dados_teste_embarcacoes','0','Exibe o preenchimento rápido com dados fictícios no cadastro de embarcações','2026-09-10 15:42:19'),('meta_mensagem','Ao bater a meta, teremos um dia especial com toda a equipe.','Mensagem da meta mensal exibida para a equipe','2026-07-16 17:48:32'),('meta_mensal','180000.00','Meta mensal de faturamento comercial em R$','2026-07-06 22:49:46'),('responsavel_assinatura_cargo','Engenheiro Naval',NULL,'2026-07-02 17:34:06'),('responsavel_assinatura_nome','João Responsável',NULL,'2026-07-02 17:34:06'),('responsavel_assinatura_registro','CREA 123456',NULL,'2026-07-02 17:34:06');
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
INSERT INTO `csn_distribuicao_passageiros` VALUES ('16c0fb45-982a-4095-a50c-d72ced77aadc','2e0d12e3-729f-482e-bc5c-806e01223ee2','passageiros_em_pe','Passageiros em pé',NULL,'','','','passageiros'),('1a78b760-5dab-4bec-91ed-83ea7327f4d7','faa23877-c468-4114-9182-3b6157402f0f','deposito_conves_principal','Depósito no convés principal',NULL,'','','','t'),('21fa2145-188e-456c-9cf1-2459d82244f5','2e0d12e3-729f-482e-bc5c-806e01223ee2','passageiros_sentados','Passageiros sentados',NULL,'','','','passageiros'),('2ecf1b60-7b06-4919-a36c-1ebda7d190df','2e0d12e3-729f-482e-bc5c-806e01223ee2','passageiros_camarote','Passageiros em camarote',NULL,'','','','passageiros'),('39f86c35-6bd3-4493-9d70-79baa6ac8739','2e0d12e3-729f-482e-bc5c-806e01223ee2','almoxarifado_conves_principal','Almoxarifado no convés principal',NULL,'','','','t'),('46517291-3d5e-4b6d-87c6-14a435e8f494','2e0d12e3-729f-482e-bc5c-806e01223ee2','passageiros_redes','Passageiros em redes',NULL,'','','','passageiros'),('5adcd927-c8af-4c29-a993-30b43a2c534f','faa23877-c468-4114-9182-3b6157402f0f','passageiros_camarote','Passageiros em camarote',NULL,'','','','passageiros'),('5c9d3bc7-07cc-4068-bf69-9837dd52c63c','2e0d12e3-729f-482e-bc5c-806e01223ee2','deposito_conves_superior','Depósito no convés superior',NULL,'','','','t'),('68427a7b-05b1-41fb-8b33-50c985117658','2e0d12e3-729f-482e-bc5c-806e01223ee2','porao_carga_01','Porão de carga 01 (carga geral)',NULL,'','','','t'),('6f5e1069-e41e-4ac1-b115-34df1a0d7462','faa23877-c468-4114-9182-3b6157402f0f','passageiros_em_pe','Passageiros em pé',NULL,'','','','passageiros'),('84afab29-fe6e-4fd0-9705-98b8c5187111','faa23877-c468-4114-9182-3b6157402f0f','passageiros_sentados','Passageiros sentados',NULL,'','','','passageiros'),('85f51d64-804c-44f9-8d7e-69ddd691063e','faa23877-c468-4114-9182-3b6157402f0f','deposito_conves_superior','Depósito no convés superior',NULL,'','','','t'),('90953a3c-cbb4-4fe9-bd9c-2e77cc334720','faa23877-c468-4114-9182-3b6157402f0f','paiol_casco','Paiol no casco (mantimentos e materiais diversos)',NULL,'','','','t'),('bee6e22e-525a-406f-a96d-1c9b67aa7263','faa23877-c468-4114-9182-3b6157402f0f','passageiros_redes','Passageiros em redes',NULL,'','','','passageiros'),('c1493060-7aa8-4b36-9c11-f2db70f60c83','faa23877-c468-4114-9182-3b6157402f0f','almoxarifado_conves_principal','Almoxarifado no convés principal',NULL,'','','','t'),('cb8a6b2c-b08e-4d2b-b1a5-e8ed8f9a65c6','faa23877-c468-4114-9182-3b6157402f0f','porao_carga_01','Porão de carga 01 (carga geral)',NULL,'','','','t'),('d6495df6-25e9-42a5-96b8-fdb2f238a81a','2e0d12e3-729f-482e-bc5c-806e01223ee2','paiol_casco','Paiol no casco (mantimentos e materiais diversos)',NULL,'','','','t'),('efda2785-03f3-4081-8e10-ce50e8f1cb89','2e0d12e3-729f-482e-bc5c-806e01223ee2','deposito_conves_principal','Depósito no convés principal',NULL,'','','','t');
/*!40000 ALTER TABLE `csn_distribuicao_passageiros` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `documento_aprovacoes`
--

DROP TABLE IF EXISTS `documento_aprovacoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `documento_aprovacoes` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `documento_tipo` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `documento_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `versao` int unsigned NOT NULL DEFAULT '1',
  `responsavel_id` int NOT NULL,
  `aprovador_usuario_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `responsavel_nome` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `responsavel_cpf_cnpj` varchar(18) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `responsavel_cargo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `responsavel_registro` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `aprovador_nome` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `assinatura_arquivo` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `assinatura_hash` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `aprovado_em_utc` datetime NOT NULL,
  `aprovado_em_local` datetime NOT NULL,
  `fuso_horario` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'America/Sao_Paulo',
  `utc_offset` varchar(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `geo_precisao_m` decimal(10,2) DEFAULT NULL,
  `ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `user_agent` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `autenticacao_metodo` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'SESSAO',
  `assinatura_convite_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `hash_pdf_original` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `hash_pdf_final` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `caminho_pdf_original` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `caminho_pdf_final` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `token_validacao` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('PROCESSANDO','APROVADO','FALHA','CANCELADO') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'PROCESSANDO',
  `padrao_assinatura` enum('AUDIT_ONLY','PADES_ICP_BRASIL') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'AUDIT_ONLY',
  `status_pades` enum('NAO_APLICADO','APLICADO','INVALIDO') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'NAO_APLICADO',
  `provedor_assinatura` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `certificado_titular` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `certificado_serial` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `certificado_valido_de` datetime DEFAULT NULL,
  `certificado_valido_ate` datetime DEFAULT NULL,
  `erro_processamento` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `documento_aprovacoes`
--

LOCK TABLES `documento_aprovacoes` WRITE;
/*!40000 ALTER TABLE `documento_aprovacoes` DISABLE KEYS */;
INSERT INTO `documento_aprovacoes` VALUES ('c429d229-d0e6-4e3f-ad90-9cb86761f3de','CSN','2e0d12e3-729f-482e-bc5c-806e01223ee2',1,2,'dd121661-feb4-42f6-895a-68eb0608d1e4','Victal Donanzan','383.034.518-63','Engenheiro Naval','CREA: 22.537','admin','storage/private/assinaturas_responsaveis/2/20260720_100109_90048b1dd4c51d95.png','09da23f7c13fbfbf42c88f65ff2208903086c13f3ed5022813784e45a94bdd13','2026-09-16 00:57:57','2026-09-15 21:57:57','America/Sao_Paulo','-03:00',-1.37890000,-48.41910000,50000.00,'172.23.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0','SESSAO',NULL,'9d383d26d3d6e328ce1fa2c5e80c73cdd3ff1e3f9b1d6750c7847cf3fe046d16','531e81d84e107eb30adf93d68cc94a365296587a8918ff6edb2ffb8176848f37','storage/documentos_aprovados/2026/csn/c429d229-d0e6-4e3f-ad90-9cb86761f3de_original.pdf','storage/documentos_aprovados/2026/csn/c429d229-d0e6-4e3f-ad90-9cb86761f3de.pdf','600cb97787d1af413362cc19477c6c359bc6ba84b6473088b036661c25521576','APROVADO','AUDIT_ONLY','NAO_APLICADO','internal-audit',NULL,NULL,NULL,NULL,NULL,'2026-09-16 00:57:57','2026-09-16 00:57:58'),('e7d82b3a-09f0-41bd-bc83-77fed4f6ae63','CSN','faa23877-c468-4114-9182-3b6157402f0f',1,2,'dd121661-feb4-42f6-895a-68eb0608d1e4','Victal Donanzan','383.034.518-63','Engenheiro Naval','CREA: 22.537','admin','storage/private/assinaturas_responsaveis/2/20260720_100109_90048b1dd4c51d95.png','09da23f7c13fbfbf42c88f65ff2208903086c13f3ed5022813784e45a94bdd13','2026-09-10 20:25:32','2026-09-10 17:25:32','America/Sao_Paulo','-03:00',-1.37400000,-48.40160000,5000.00,'172.23.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0','SESSAO',NULL,'578ed580fc05341ddc617991cd7062f8e86e840647c80f19b75092fc938ce527','e960cc7748271fe92db5fdfa5e52083e89ffc8b5367b4be060646102fbe78ec6','storage/documentos_aprovados/2026/csn/e7d82b3a-09f0-41bd-bc83-77fed4f6ae63_original.pdf','storage/documentos_aprovados/2026/csn/e7d82b3a-09f0-41bd-bc83-77fed4f6ae63.pdf','ea76ff8c43352df220f71ba1c3b0e2c09392c01ee63ac47b10ba47bbb1fcc754','APROVADO','AUDIT_ONLY','NAO_APLICADO','internal-audit',NULL,NULL,NULL,NULL,NULL,'2026-09-10 20:25:32','2026-09-10 20:25:33');
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
  `ordem_servico_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `motivo_revisao` text COLLATE utf8mb4_general_ci,
  `revisado_por` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
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
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `documento_tipo` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `documento_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `versao` int unsigned NOT NULL DEFAULT '1',
  `responsavel_id` int NOT NULL,
  `usuario_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `assinatura_arquivo` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `assinatura_hash` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `hash_pdf_original` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `hash_pdf_assinado` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `caminho_pdf_original` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `caminho_pdf_assinado` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `token_validacao` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `geo_precisao_m` decimal(10,2) DEFAULT NULL,
  `ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `user_agent` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('ASSINADO','CANCELADO') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'ASSINADO',
  `assinado_em` datetime NOT NULL,
  `cancelado_em` datetime DEFAULT NULL,
  `cancelado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `motivo_cancelamento` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_documento_assinatura_versao` (`documento_tipo`,`documento_id`,`versao`),
  UNIQUE KEY `uk_documento_assinatura_token` (`token_validacao`),
  KEY `idx_documento_assinatura_documento` (`documento_tipo`,`documento_id`,`status`),
  KEY `idx_documento_assinatura_responsavel` (`responsavel_id`),
  KEY `idx_documento_assinatura_usuario` (`usuario_id`),
  CONSTRAINT `fk_documento_assinatura_responsavel` FOREIGN KEY (`responsavel_id`) REFERENCES `responsaveis_assinatura` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_documento_assinatura_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `documento_assinaturas`
--

LOCK TABLES `documento_assinaturas` WRITE;
/*!40000 ALTER TABLE `documento_assinaturas` DISABLE KEYS */;
INSERT INTO `documento_assinaturas` VALUES ('40c2a0cc-66dc-4bd6-9f04-eb5b3fefe1ac','RELATORIO','c0cc81fc-b11d-40a6-b8f8-f410d3c1982a',1,7,'dd121661-feb4-42f6-895a-68eb0608d1e4','storage/private/assinaturas_responsaveis/7/20260723_042523_00e24fdbe224bad3.png','09da23f7c13fbfbf42c88f65ff2208903086c13f3ed5022813784e45a94bdd13','5dcebe043307e6f6b8cecfb8ef66b1edd207ab9368081a74622a3a43c8ee2e74','f2fd3912e00693cb743b98f78b5e71c8912c72100b46d49f387c46b37fb56332','storage/documentos_assinados/2026/relatorio/40c2a0cc-66dc-4bd6-9f04-eb5b3fefe1ac_original.pdf','storage/documentos_assinados/2026/relatorio/40c2a0cc-66dc-4bd6-9f04-eb5b3fefe1ac.pdf','58c13126bc4815f7de691950a51e249083e47628e37434f93d91cbbbfb63b06f',-1.37400000,-48.40160000,5000.00,'172.23.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0','ASSINADO','2026-09-10 17:24:25',NULL,NULL,NULL,'2026-09-10 20:24:27');
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
INSERT INTO `email_logs` VALUES ('0385522c-5d1c-4b90-92b6-1259a4f5158b','neto@amazonnaval.com.br','Documento aguardando assinatura - CSN','assinatura','CSN','3841d99d-db30-40ed-b71b-04392111ffd7','erro','Erro ao enviar e-mail: SMTP Error: Could not authenticate.','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:31:58'),('092e7392-2d18-4728-87b1-798191095320','neto@amazonnaval.com.br','Documento aguardando assinatura - CSN','assinatura','CSN','42104e8d-41d3-4172-b5f5-ee6e5d9246a3','erro','Erro ao enviar e-mail: SMTP Error: Could not authenticate.','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:33:58'),('163aecf2-857e-4ddf-b810-0d5dcd03c8b5','neto@amazonnaval.com.br','Documento aguardando assinatura - CSN','assinatura','CSN','ba94f512-15d0-43fb-b0b1-cc6174886085','erro','Erro ao enviar e-mail: SMTP Error: Could not authenticate.','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:34:28'),('2ca0c95c-a1f4-407b-ab39-0d5bac576f23','neto@amazonnaval.com.br','Documento aguardando assinatura - CSN','assinatura','CSN','43cd7f0b-99d5-4ccf-bb5a-927ab849c452','erro','Erro ao enviar e-mail: SMTP Error: Could not authenticate.','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:30:46'),('3289a355-1732-4466-b162-aed0372b4b3f','neto@amazonnaval.com.br','Documento aguardando assinatura - CSN','assinatura','CSN','cb1a7f1c-e09d-44c6-bc24-16ff26c35b98','erro','Erro ao enviar e-mail: SMTP Error: Could not authenticate.','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 03:07:39'),('328d7066-b316-4785-8b58-4f38fbaf0620','neto@amazonnaval.com.br','Documento aguardando assinatura - CSN','assinatura','CSN','503924ae-041f-4d64-bc53-686a119e85e2','erro','Erro ao enviar e-mail: SMTP Error: Could not authenticate.','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:28:12'),('3994113a-a5a0-4824-82e2-6a02abbfd659','neto@amazonnaval.com.br','Documento aguardando assinatura - CSN','assinatura','CSN','ddb10c9c-ed07-4b76-bab5-4f0914c7b8d7','erro','Erro ao enviar e-mail: SMTP Error: Could not authenticate.','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:36:34'),('5ee11414-ae5a-11f1-8a7c-be2fb1f77be2','anykedas@gmail.com','Acesso ao Portal do Cliente','portal_acesso','clientes','1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed','erro','Erro ao enviar e-mail: SMTP Error: Could not authenticate.','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-12 03:30:58'),('5f6e0e50-100b-4f38-80ac-b0820a56af41','neto@amazonnaval.com.br','Documento aguardando assinatura - CSN','assinatura','CSN','9f9a16b3-0b78-4f35-a2a6-8476b42dbd8e','erro','Erro ao enviar e-mail: SMTP Error: Could not authenticate.','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:32:24'),('6b6127a7-d326-4805-89ac-067e812935ce','neto@amazonnaval.com.br','Documento aguardando assinatura - CSN','assinatura','CSN','89624ca3-49e4-405f-a707-967113025cb6','erro','Erro ao enviar e-mail: SMTP Error: Could not authenticate.','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:32:50'),('732bfb78-8b50-4d23-906b-a3b2d4c54a98','neto@amazonnaval.com.br','Documento aguardando assinatura - CSN','assinatura','CSN','278a3508-69c6-4ca7-b8a9-62feef7ec7fd','erro','Erro ao enviar e-mail: SMTP Error: Could not authenticate.','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 01:04:45'),('7a2412df-ae5a-11f1-8a7c-be2fb1f77be2','anykedas@gmail.com','Acesso ao Portal do Cliente','portal_acesso','clientes','1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed','erro','Erro ao enviar e-mail: SMTP Error: Could not authenticate.','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-12 03:31:44'),('80b94eb0-83d9-4b1b-9133-5b811eaee147','neto@amazonnaval.com.br','Documento aguardando assinatura - CSN','assinatura','CSN','bf94ed71-44ac-4e02-81dc-66ef89a1893b','erro','Erro ao enviar e-mail: SMTP Error: Could not authenticate.','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 03:05:55'),('9d72f199-6021-4d97-982c-f1238cb1df2c','neto@amazonnaval.com.br','Documento aguardando assinatura - CSN','assinatura','CSN','0f670e3a-4da5-4bbe-ac13-51f8d45e69a1','erro','Erro ao enviar e-mail: SMTP Error: Could not authenticate.','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:34:57'),('b5469955-74d0-4190-8e6b-2f76c375df29','neto@amazonnaval.com.br','Documento aguardando assinatura - CSN','assinatura','CSN','76558869-53c9-426e-9a36-92c8c181d393','erro','Erro ao enviar e-mail: SMTP Error: Could not authenticate.','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:48:04'),('b8187d16-f16f-4206-9455-9f8be157842c','neto@amazonnaval.com.br','Documento aguardando assinatura - CSN','assinatura','CSN','4aee2c61-4fb2-498c-8d0f-06d7cadb3e2b','erro','Erro ao enviar e-mail: SMTP Error: Could not authenticate.','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:32:53'),('b9293783-c469-44ff-887e-40a28ddb369e','neto@amazonnaval.com.br','Documento aguardando assinatura - CSN','assinatura','CSN','fe44beee-1a41-4158-9c9c-0e0bc24539ae','erro','Erro ao enviar e-mail: SMTP Error: Could not authenticate.','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 03:23:23'),('bdb50d86-2ca9-4450-90d4-58c6b601cfbb','neto@amazonnaval.com.br','Documento aguardando assinatura - CSN','assinatura','CSN','faa23877-c468-4114-9182-3b6157402f0f','erro','Erro ao enviar e-mail: SMTP Error: Could not authenticate.','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-10 20:25:13'),('c1472cb8-9cd3-4d31-8ed9-3e972c906876','neto@amazonnaval.com.br','Documento aguardando assinatura - CSN','assinatura','CSN','b01a38f6-ce85-4ca0-a07b-0210984174fd','erro','Erro ao enviar e-mail: SMTP Error: Could not authenticate.','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:34:31'),('c99a27ac-6af1-4b60-903c-3b9de0b205f6','neto@amazonnaval.com.br','Documento aguardando assinatura - CSN','assinatura','CSN','d2bbd9a7-c7e4-4b2d-a9e6-15c9e07d2b85','erro','Erro ao enviar e-mail: SMTP Error: Could not authenticate.','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 02:15:02'),('cf3428c4-411c-46b8-8834-bc5f631e56ba','neto@amazonnaval.com.br','Documento aguardando assinatura - CSN','assinatura','CSN','e5153da4-c0c3-4919-b4ef-5f6c2e1924df','erro','Erro ao enviar e-mail: SMTP Error: Could not authenticate.','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:30:03'),('d301fb9d-4821-407f-b96d-d34ba9fcf80b','neto@amazonnaval.com.br','Documento aguardando assinatura - CSN','assinatura','CSN','2e0d12e3-729f-482e-bc5c-806e01223ee2','erro','Erro ao enviar e-mail: SMTP Error: Could not authenticate.','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:57:34'),('d3a51ac7-03c6-4913-bae2-4d7d43e36194','neto@amazonnaval.com.br','Documento aguardando assinatura - CSN','assinatura','CSN','9919ec2d-074f-451c-9a61-4ff1918969ee','erro','Erro ao enviar e-mail: SMTP Error: Could not authenticate.','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:36:14'),('d48102f2-3104-436f-b726-a037435c0446','neto@amazonnaval.com.br','Documento aguardando assinatura - CSN','assinatura','CSN','848b2e8e-dd17-4165-bcdd-de580acad438','erro','Erro ao enviar e-mail: SMTP Error: Could not authenticate.','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 03:25:05'),('d5b192ac-166d-4626-834a-63b3f67ab319','neto@amazonnaval.com.br','Documento aguardando assinatura - CSN','assinatura','CSN','f7978868-1a3b-446f-a57c-21b344e85f82','erro','Erro ao enviar e-mail: SMTP Error: Could not authenticate.','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:31:09'),('eb4dd7d7-09d5-4188-981d-5657907f8e10','neto@amazonnaval.com.br','Documento aguardando assinatura - CSN','assinatura','CSN','7ee27829-c4d9-42da-812c-e7465023f3f0','erro','Erro ao enviar e-mail: SMTP Error: Could not authenticate.','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 04:03:12'),('f483dc91-06a0-4c96-b4a7-fc884f178822','neto@amazonnaval.com.br','Documento aguardando assinatura - CSN','assinatura','CSN','fba9ad42-4d4e-4f21-ae14-a33f7cd5609c','erro','Erro ao enviar e-mail: SMTP Error: Could not authenticate.','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:31:30');
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
  `excluido_em` datetime DEFAULT NULL,
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
  KEY `idx_embarcacoes_ativo_excluido` (`ativo`,`excluido_em`),
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
INSERT INTO `embarcacoes` VALUES ('234aaec1-d1dd-4872-94b3-f52c61b04316',NULL,'B/M SOLIMÕES EXPRESS 883',NULL,'06a95b60-75d0-11f1-98f0-5ed0db5eacb7','Balsa',NULL,1,'Scania','DS11','SC-998877','330',NULL,NULL,2022,NULL,28.50,NULL,NULL,2.10,6.40,NULL,'Aço',NULL,'Interior','Área 1 e 2',NULL,'115.00','021-507232-WEB','Manaus-AM',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:44:42','2026-09-16 00:44:42',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1.50,NULL,NULL,NULL,NULL,NULL,NULL,NULL),('317ba743-7aa6-4d66-a845-2d4670f126f0','1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed','barcoteste14',NULL,'06a95b60-75d0-11f1-98f0-5ed0db5eacb7','Balsa',NULL,0,NULL,NULL,NULL,NULL,NULL,'Rosano Souza',NULL,NULL,18.50,18.00,NULL,2.10,5.20,NULL,NULL,NULL,NULL,NULL,NULL,'45.00',NULL,'Belém - PA',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-16 00:36:18','11111111-1111-1111-1111-111111111111',1,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-10 20:18:59','2026-09-16 00:36:18',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),('5a8be95a-1e34-412b-a831-81cdcf2772c9',NULL,'B/M SOLIMÕES EXPRESS 212',NULL,'06a95b60-75d0-11f1-98f0-5ed0db5eacb7','Balsa',NULL,1,'Scania','DS11','SC-998877','330',NULL,NULL,2022,NULL,28.50,NULL,NULL,2.10,6.40,NULL,'Aço',NULL,'Interior','Área 1 e 2',NULL,'115.00','021-100483-WEB','Manaus-AM',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:45:35','2026-09-16 00:45:35',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1.50,NULL,NULL,NULL,NULL,NULL,NULL,NULL),('7bd04fc6-5070-4ead-b59e-35c7ade999de',NULL,'B/M SOLIMÕES EXPRESS 229',NULL,'06a95b60-75d0-11f1-98f0-5ed0db5eacb7','Balsa',NULL,1,'Scania','DS11','SC-998877','330',NULL,NULL,2022,NULL,28.50,NULL,NULL,2.10,6.40,NULL,'Aço',NULL,'Interior','Área 1 e 2',NULL,'115.00','021-360955-WEB','Manaus-AM',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:45:09','2026-09-16 00:45:09',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1.50,NULL,NULL,NULL,NULL,NULL,NULL,NULL),('81e81cf0-42b3-45f9-94bb-d701c5c02976',NULL,'B/M SOLIMÕES EXPRESS 460',NULL,'06a95b60-75d0-11f1-98f0-5ed0db5eacb7','Balsa',NULL,1,'Scania','DS11','SC-998877','330',NULL,NULL,2022,NULL,28.50,NULL,NULL,2.10,6.40,NULL,'Aço',NULL,'Interior','Área 1 e 2',NULL,'115.00','021-507263-WEB','Manaus-AM',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:46:12','2026-09-16 00:46:12',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1.50,NULL,NULL,NULL,NULL,NULL,NULL,NULL),('b01e993b-140d-4a3e-9155-fca1842ffd49',NULL,'B/M SOLIMÕES EXPRESS 930',NULL,'06a95b60-75d0-11f1-98f0-5ed0db5eacb7','Balsa',NULL,1,'Scania','DS11','SC-998877','330',NULL,NULL,2022,NULL,28.50,NULL,NULL,2.10,6.40,NULL,'Aço',NULL,'Interior','Área 1 e 2',NULL,'115.00','021-230651-WEB','Manaus-AM',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:42:50','2026-09-16 00:42:50',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1.50,NULL,NULL,NULL,NULL,NULL,NULL,NULL),('c687679e-e7ed-4def-b5ff-72c443042db0',NULL,'B/M SOLIMÕES EXPRESS 775',NULL,'06a95b60-75d0-11f1-98f0-5ed0db5eacb7','Balsa',NULL,1,'Scania','DS11','SC-998877','330',NULL,NULL,2022,NULL,28.50,NULL,NULL,2.10,6.40,NULL,'Aço',NULL,'Interior','Área 1 e 2',NULL,'115.00','021-430797-WEB','Manaus-AM',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:45:26','2026-09-16 00:45:26',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1.50,NULL,NULL,NULL,NULL,NULL,NULL,NULL),('c72ee837-94d8-4fe7-8ac9-63edc8ae4c73',NULL,'B/M SOLIMÕES EXPRESS 834',NULL,'06a95b60-75d0-11f1-98f0-5ed0db5eacb7','Balsa',NULL,1,'Scania','DS11','SC-998877','330',NULL,NULL,2022,NULL,28.50,NULL,NULL,2.10,6.40,NULL,'Aço',NULL,'Interior','Área 1 e 2',NULL,'115.00','021-315275-WEB','Manaus-AM',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-16 04:02:24','11111111-1111-1111-1111-111111111111',1,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:41:32','2026-09-16 04:02:24',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1.50,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
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
  `excluido_em` datetime DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_escritorios_nome_cidade` (`nome`,`cidade`,`uf`),
  KEY `idx_escritorios_ativo` (`ativo`),
  KEY `idx_escritorios_ativo_excluido` (`ativo`,`excluido_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `escritorios`
--

LOCK TABLES `escritorios` WRITE;
/*!40000 ALTER TABLE `escritorios` DISABLE KEYS */;
INSERT INTO `escritorios` VALUES ('00000000-0000-4000-8000-000000000100','Escritório Matriz E3','Manaus','AM',1,NULL,'2026-09-15 19:42:19','2026-09-15 19:42:19'),('23fd0c61-2db2-4a41-807c-e18c1a26f974','Matriz Manaus','Manaus','AM',1,NULL,'2026-07-28 06:37:40','2026-07-28 06:37:40'),('342323aa-142c-447b-b392-7421e538f041','Matriz Belém','Bélem','PA',1,NULL,'2026-07-28 06:37:08','2026-07-28 06:37:08');
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
  `obrigatoria` tinyint(1) NOT NULL DEFAULT '0',
  `exige_foto` tinyint(1) NOT NULL DEFAULT '0',
  `ordem_exibicao` int NOT NULL DEFAULT '0',
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `aplicabilidade_a` tinyint(1) NOT NULL DEFAULT '1',
  `aplicabilidade_b` tinyint(1) NOT NULL DEFAULT '1',
  `aplicabilidade_c` tinyint(1) NOT NULL DEFAULT '1',
  `aplicabilidade_d` tinyint(1) NOT NULL DEFAULT '1',
  `aplicabilidade_e` tinyint(1) NOT NULL DEFAULT '1',
  `aplicabilidade_f` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_exigencias_codigo_interno` (`codigo_interno`),
  KEY `fk_catalogo_categoria` (`categoria_id`),
  CONSTRAINT `fk_catalogo_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `exigencias_categorias` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exigencias_catalogo`
--

LOCK TABLES `exigencias_catalogo` WRITE;
/*!40000 ALTER TABLE `exigencias_catalogo` DISABLE KEYS */;
INSERT INTO `exigencias_catalogo` VALUES ('001794c9-7765-48f2-aa3e-13b4ff29aba8','EX-344','b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','A dotação de coletes salva vidas atende a totalidade de pessoas a serem transportadas, inclusive crianças (10% para elas)','NORMAM-202/DPC, Cap. 04, Item 4.13.','flutuando',NULL,30,1,1,1,0,'2026-07-03 05:38:13','2026-09-12 15:21:09',1,1,1,1,1,1),('005da3a8-7a7b-4fab-b855-6dbbf28f8fa8','EX-373','a5f25230-91c9-4e14-aa33-e83524d5d943','As embarcações com AB maior que 500 deverão ter, pelo menos, duas bombas de incêndio de acionamento não manual, sendo que uma bomba deverá possuir força motriz distinta da outra e independente do motor principal.','NORMAM-202/DPC, Cap. 03, Seção III.','flutuando',NULL,30,1,1,1,0,'2026-07-03 05:38:13','2026-09-12 15:21:09',1,1,1,1,1,1),('012e8fb1-9d0f-4d3c-94a4-8bb0ee588991','EX-329','e70f7906-4e9d-4367-b10a-2ad2a007817a','Indicador de rotação do(s) MCP(s) no passadiço ou comando','NORMAM-202/DPC','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,0,1,0,0),('025542ea-e255-4ace-9dbd-b02ef35feabd','EX-358','b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','Data de fabricação (Embarcações de Sobrevivência/Boias)','NORMAM-202/DPC, Cap. 04, Item 4.12.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('0382e720-a8ce-42ef-8146-d19431108b5a','EX-438','b8ed9a31-9fa3-492f-904e-b8158a06d0da','a) os fios são protegidos por meio de eletrodutos rígidos ou flexíveis','NORMAM-202/DPC','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('03d79106-5ac2-42a2-ba86-af98a21c6022','EX-382','a5f25230-91c9-4e14-aa33-e83524d5d943','O número de seções de mangueira, incluindo uniões e esguichos, é de uma para cada 30 m de comprimento da embarcação e há outra sobressalente (sendo que, em nenhum caso, este número poderá ser inferior a três).','NORMAM-202/DPC, Cap. 04, Seção III.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,0,1,0,0),('0470cbba-bc5c-4e90-841d-6de840326f65','EX-339','b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','Classe (Coletes salva-vidas)','NORMAM-202/DPC, Cap. 04, Item 4.13.','flutuando',NULL,30,1,1,1,0,'2026-07-03 05:38:13','2026-09-12 15:21:09',1,1,1,1,1,1),('0496349c-9dd7-4bf1-b628-d6a87e9744ab','EX-463','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Existe a bordo um compartimento, com dimensões apropriadas e com possibilidade de trancamento, para a guarda de bagagens e volumes de passageiros, conforme indicado no projeto','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('066394ff-2a85-4b3b-8338-e04f6948b915','EX-371','a5f25230-91c9-4e14-aa33-e83524d5d943','A embarcação é dotada de, pelo menos, uma bomba de incêndio fixa não manual, com vazão maior ou igual a 15 m³/h (tal bomba poderá ser acionada pelo motor principal)','NORMAM-202/DPC, Cap. 04, Seção III.','flutuando',NULL,30,1,1,1,0,'2026-07-03 05:38:13','2026-09-12 15:21:09',1,1,1,1,1,1),('06a2613d-ad79-437a-b3b8-190ae85212da','EX-537','71c05e83-0d67-4137-b2b7-478c4241a057','Escala de calado está escrita a boreste e a bombordo, a vante e a ré e a meia nau, em medidas métricas','NORMAM-202/DPC, Cap. 02, Seção II.','flutuando',NULL,30,1,1,1,0,'2026-07-03 05:38:14','2026-09-12 15:21:09',1,1,1,1,1,1),('076e253a-6e6a-4a81-9877-640da3ad73e1','EX-405','65bf89f0-f44d-4746-89f7-f530c9aa990d','As bombas utilizadas para transferência de óleo para consumo da embarcação deverão ser instaladas sobre bandejas coletoras, que possibilitem, em caso de vazamentos, a coleta do óleo derramado','NORMAM-202/DPC, Cap. 03, Seção III.','flutuando',NULL,30,1,1,1,0,'2026-07-03 05:38:13','2026-09-12 15:21:09',1,1,1,1,1,1),('07a3393b-429c-447d-bfd0-353a6683bd1b','EX-387','a5f25230-91c9-4e14-aa33-e83524d5d943','A identificação por cores das tubulações em todas as embarcações deverá ser efetuada em conformidade com o disposto na norma ISO 14726:2008.','NORMAM-202/DPC, Cap. 09, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('07f7f40b-5d11-4d8b-b409-54d6f2d9ec76','EX-407','65bf89f0-f44d-4746-89f7-f530c9aa990d','Verificar as proteções térmicas e acústicas do(s) motor(es) de embarcações de transporte de passageiros','NORMAM-202/DPC, Cap. 03, Seção III.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('0812830b-ec4d-4746-bb3b-d6cf8a6eb74a','EX-428','b8ed9a31-9fa3-492f-904e-b8158a06d0da','Quanto aos quadros elétricos: b) o de emergência está próximo à fonte de energia elétrica de emergência','NORMAM-202/DPC, Cap. 03, Seção IV.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('08457e8a-69b4-4157-b040-15d526d41a67','EX-335','e70f7906-4e9d-4367-b10a-2ad2a007817a','Verificar a presença de relógio de parede ou de painel no comando, devidamente sincronizado e operacional.','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('0a144b76-52d6-4e7d-a1c2-8154c5ccf4fb','EX-520','71c05e83-0d67-4137-b2b7-478c4241a057','Abaixo do convés aberto mais baixo, a via de escape principal é uma escada e a via secundária consiste num conduto ou numa escada','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,0,1,1,0,0),('0a212a39-3f21-4932-ab3b-7d5bd4e8721f','EX-368','a5f25230-91c9-4e14-aa33-e83524d5d943','Os botijões de gás estão posicionados em áreas externas, em local seguro e arejado, protegidos do sol e afastados de fontes que possam causar ignição.','NORMAM-202/DPC, Cap. 04, Item 4.29.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('0bb736ea-8f70-4b80-9ac4-c441139fbe3c','EX-374','a5f25230-91c9-4e14-aa33-e83524d5d943','Em EMPURRADORES e REBOCADORES a(s) bomba(s), as duas tomadas e as duas estações de incêndio completas deverão estar posicionadas nas proximidades da proa da embarcação','NORMAM-202/DPC, Cap. 03, Seção III.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('0c21a30d-7637-49bd-94b9-eaa39968b2bc','EX-508','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','A unidade de chuveiro apresenta soleira com uma altura mínima de 100 mm acima do convés e é impermeabilizadas até esse nível','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('0d11a58c-88e9-40df-b7c1-28e0eb4e62b0','EX-325','e70f7906-4e9d-4367-b10a-2ad2a007817a','Ecobatímetro','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,0,1,0,0),('0dc6cd05-01d7-4035-b683-fb1c6251f2d8','EX-350','b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','A dotação das embarcações de sobrevivência está de acordo com o quadro da NORMAM e estão em boas condições (inclusive suas alças, se aparelho rígido)','NORMAM-202/DPC','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('0ddc6914-749b-40e7-8799-15c272201ebf','EX-338','b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','Modelo (Coletes salva-vidas)','NORMAM-202/DPC, Cap. 04, Item 4.13.','flutuando',NULL,30,1,1,1,0,'2026-07-03 05:38:13','2026-09-12 15:21:09',1,1,1,1,1,1),('0e8c9c8f-adb8-444a-985e-dc2cebd737b4','EX-502','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','As distâncias mínimas que deverão ser observadas entre as unidades do sanitário coletivo são as seguintes (Unidade em frente a unidade, lavatório, antepara, etc.)','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('0ed1e638-2afc-4cdf-ad7a-3d1e9b3fb6c4','EX-475','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','As cadeiras deverão atender às seguintes dimensões: c) profundidade mínima de 0,40 m','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('0fcd87ed-ac18-4025-a692-d79d5ba5599b','EX-443','b8ed9a31-9fa3-492f-904e-b8158a06d0da','f) os cabos e fiação utilizados nos circuitos elétricos de fornecimento essencial ou de emergência de força, iluminação, comunicações interiores ou sinalização não passam por áreas em que haja risco de incêndio','NORMAM-202/DPC, Cap. 03, Seção IV.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('0fdc1e57-8063-4666-ab7d-cee70fff1cf4','EX-367','a5f25230-91c9-4e14-aa33-e83524d5d943','Todos os extintores portáteis possuem o selo do INMETRO e estão dentro do prazo de validade, com as manutenções periódicas realizadas','NORMAM-202/DPC, Cap. 04, Seção III.','flutuando',NULL,30,1,1,1,0,'2026-07-03 05:38:13','2026-09-12 15:21:09',1,1,1,1,1,1),('144a054a-435d-4c39-8a2a-c0ad22d4f20e','EX-500','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Cada módulo do lavatório coletivo possui sua torneira própria, e há um dreno servindo a, no máximo, 5 módulos','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('15d35d22-8df1-4051-ae12-4f75812736d9','EX-359','b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','Nome da embarcação (Embarcações de Sobrevivência/Boias)','NORMAM-202/DPC, Cap. 04, Item 4.12.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('16dbdb50-0884-4e9f-8ee9-0b202a65fc04','EX-314','aa4a7f0d-004d-4a60-924e-693335fdd69b','Tabelas ou quadros em outros locais de fácil visualização: - tabelas ou quadros de primeiros socorros','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,0,1,0,0),('191031d1-a918-4879-9118-a6bce6f4b56b','EX-362','a5f25230-91c9-4e14-aa33-e83524d5d943','Não são utilizados combustíveis com ponto de fulgor inferior a 60 °C (como álcool ou gasolina)','NORMAM-202/DPC','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('19b9e02f-e153-46af-90a9-deb6b1511808','EX-484','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','As têm, no mínimo, 1,9 m de comprimento e 0,68 m de largura','NORMAM-202/DPC','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('1b8b2e7c-37f2-41d2-90e5-27d936a704da','EX-429','b8ed9a31-9fa3-492f-904e-b8158a06d0da','Quanto aos quadros elétricos: c) os lados, a parte de trás e da frente dos quadros elétricos estão devidamente protegidos, tapetes ou estrados não condutores estão no piso na frente e atrás dos referidos quadros.','NORMAM-202/DPC','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('1bb30d90-ee8e-4efe-946d-d3ee1385eb36','EX-398','65bf89f0-f44d-4746-89f7-f530c9aa990d','Verificar a presença de objetos não necessários ao funcionamento dos equipamentos, estivados de forma irregular sobre ou próximo aos equipamentos','NORMAM-202/DPC','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('1c389c2a-ae2a-479b-9303-05f79a2846f8','EX-381','a5f25230-91c9-4e14-aa33-e83524d5d943','A rede e as tomadas de incêndio são pintadas de vermelho','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('1d3e7e6f-55fe-4e02-aa7b-b4e06329ec90','EX-376','a5f25230-91c9-4e14-aa33-e83524d5d943','Nas DEMAIS embarcações, deverá haver uma estação de incêndio no visual de uma pessoa que esteja junto a uma tomada de incêndio.','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('1de8358a-fa6e-4cef-876d-6784f605e96d','EX-334','e70f7906-4e9d-4367-b10a-2ad2a007817a','Verificar a presença e o pleno funcionamento do sistema regulamentar \'Sistran\' no comando da embarcação.','NORMAM-202/DPC, Cap. 04, Item 4.2','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 02:36:44',1,1,1,1,1,1),('1f83e2dd-32fd-4f92-84c3-524af3ceb621','EX-544','71c05e83-0d67-4137-b2b7-478c4241a057','Entrar no porão com o plano de perfil estrutural e confrontar os espaçamentos das cavernas/estruturas em loco (ex: 35 ou 50 cm), inspecionando furos, descontinuidades e corrosão.','NORMAM-202/DPC, Cap. 03, Seção III.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,1,1,1,1,1),('20ceea81-c249-4b94-9448-af7887e79124','EX-467','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Os espaços para redes apresentam ventilação natural permanente para o exterior da embarcação, tendo como meio de fechamento sanefas ou janelas móveis. No caso de janela móvel, a área mínima de ventilação é de 40% do vão da abertura','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('20d82a21-815c-4aa1-bdf5-282950555392','EX-541','71c05e83-0d67-4137-b2b7-478c4241a057','Verificar se os acessos aos locais abaixo relacionados estão livres: Embornais, saídas d\'água das tomadas de incêndio, tubos de sondagem, suspiros e bocas de ventiladores','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,1,1,1,1,1),('2127c977-6a9f-4e11-9787-3aa2b600b21a','EX-501','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Em frente a cada lavatório existe um espaço livre igual ou superior a 0,5 x 0,6 m','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('22a1886c-48c1-4323-8cce-d1a9f509b800','EX-459','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Existe separação física que permita isolar carga e passageiros','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('22f45a4b-749e-4340-93bb-4c18b3a8273b','EX-316','aa4a7f0d-004d-4a60-924e-693335fdd69b','Relatório de medição de espessura (cinco pontos por chapa), assinado por profissional qualificado e certificado, com reconhecimento no Sistema Nacional de Qualificação e Certificação de Pessoal em Ensaios Não Destrutivos (SNQC/END), acompanhado de documento que comprove a validade da citada habilitação na data de execução do serviço','NORMAM-202/DPC, Cap. 08, Item 8.5','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 02:36:44',1,1,1,1,1,1),('23a80531-b5d7-4dec-bfc3-a56db5c37e23','EX-542','71c05e83-0d67-4137-b2b7-478c4241a057','Verificar se os acessos aos locais abaixo relacionados estão livres: Elementos de amarração e fundeio e o acesso às máquinas','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,1,1,0,'2026-07-03 05:38:14','2026-09-12 15:21:09',1,1,1,1,1,1),('2704ff5c-b1e3-4799-8637-fdedf7f3114b','EX-393','65bf89f0-f44d-4746-89f7-f530c9aa990d','Correias, ferramentas e sobressalentes deverão ser acondicionados em local apropriado (como cabides e armários), que evite seu deslocamento','NORMAM-202/DPC','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('27e53b15-99f1-4cd2-a400-ab471fb91c23','EX-486','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','A distância mínima entre o topo de um colchão e a parte inferior do estrado da cama imediatamente superior ou a parte inferior dos reforços do convés superior (teto do camarote) é de 0,6 m','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('2a3a0379-b1ba-40fe-b676-809f122084a1','EX-413','65bf89f0-f44d-4746-89f7-f530c9aa990d','Verificar o indicador do sentido de impulsão do(s) propulsor(es) lateral(ais) no passadiço','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('2b8953dd-9bc1-45c6-92a3-ace223c00b5b','EX-446','b8ed9a31-9fa3-492f-904e-b8158a06d0da','i) as partes condutoras de tomadas e plugs estão protegidas de modo a impedir de serem tocadas, mesmo durante ligamento e desligamento','NORMAM-202/DPC','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('2bd5be9b-36f4-40bf-81ad-20cb8ca52aee','EX-527','71c05e83-0d67-4137-b2b7-478c4241a057','As cores das luzes de navegação estão de acordo com as normas específicas sobre o assunto','RIPEAM 72 / NORMAM-202/DPC, Cap. 04.','flutuando',NULL,30,1,1,1,0,'2026-07-03 05:38:14','2026-09-12 15:21:09',1,1,1,1,1,1),('2c585b69-496a-420b-8fa7-14e372dda5dc','EX-492','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','As portas de acesso de banheiros não abrem diretamente para cozinhas ou refeitórios','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('31bb4064-def1-4e32-8ef7-e207f15562dd','EX-384','a5f25230-91c9-4e14-aa33-e83524d5d943','Há completa permutabilidade entre as uniões, mangueiras e esguichos','NORMAM-202/DPC, Cap. 04, Seção III.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('320476cf-8452-4bbc-908d-9f363b3b2eac','EX-401','65bf89f0-f44d-4746-89f7-f530c9aa990d','Redes de descarga devem ser flangeadas onde ultrapassem anteparas e ou costado (de modo que garanta a estanqueidade)','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('33356298-d44a-451e-b38c-e360b2a5bed5','EX-437','b8ed9a31-9fa3-492f-904e-b8158a06d0da','O quadro das luzes de navegação é alimentado por uma linha independente derivada do quadro principal e de emergência','RIPEAM 72 / NORMAM-202/DPC, Cap. 04.','flutuando',NULL,30,1,1,1,0,'2026-07-03 05:38:13','2026-09-12 15:21:09',1,1,1,1,1,1),('33e7f3eb-6d6d-4bdf-8bdb-80a063c683ce','EX-452','b8ed9a31-9fa3-492f-904e-b8158a06d0da','o) nos circuitos polifásicos, se a seção dos condutores fase for igual ou inferior a 16 mm² e nos circuitos monofásicos, seja qual for a seção do condutor fase, o condutor neutro tem a mesma seção que os condutores fase','NORMAM-202/DPC','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('33fbb2e3-ae28-4932-820c-40e2f45974e5','EX-529','71c05e83-0d67-4137-b2b7-478c4241a057','As luzes de navegação são homologadas pela Marinha','RIPEAM 72 / NORMAM-202/DPC, Cap. 04.','flutuando',NULL,30,1,1,1,0,'2026-07-03 05:38:14','2026-09-12 15:21:09',1,1,1,1,1,1),('342986f3-dbc0-4f3e-aedc-cb8f14f10d8a','EX-431','b8ed9a31-9fa3-492f-904e-b8158a06d0da','Quanto aos quadros elétricos: e) os quadros elétricos são bem fixados em locais abrigados que não contêm materiais inflamáveis','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('3443b027-7b7e-4275-bdf3-a916184578f9','EX-515','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Verificar a conformidade e a data de validade de cerca de 5 anos da mangueira de gás regulamentada pela ABNT e da válvula reguladora de pressão na cozinha.','NORMAM-202/DPC, Cap. 04, Item 4.29','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:14','2026-07-04 02:36:44',1,1,1,1,1,1),('36b4174a-fda8-4a30-bb87-7917235aaf0f','EX-494','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Os acessórios são de material resistente, não apresentam pontas ou arestas cortantes e estão instalados de modo a não interferir no uso do sanitário','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('37f1473c-43ee-4e4a-88fe-8848ddfc933e','EX-534','71c05e83-0d67-4137-b2b7-478c4241a057','Não há espaço abaixo do convés com comprimento superior a 40% do Lregra, medido a partir da parte superior do espelho ou da roda de proa, somente embarcações de passageiros e de madeira','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,0,1,1,0,0),('39789262-7c98-42cc-98d1-708f7cb4a09e','EX-355','b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','Modelo (Embarcações de Sobrevivência/Boias)','NORMAM-202/DPC, Cap. 04, Item 4.12.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('3a263732-7431-4277-812b-8204b15e1f5d','EX-550','71c05e83-0d67-4137-b2b7-478c4241a057','Visualmente, externa e internamente, o estado das descargas, caixas de mar e toda e qualquer abertura no casco da embarcação abaixo de seu convés principal','NORMAM-202/DPC','seco',NULL,30,1,0,0,0,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,1,1,1,1,1),('3e2d7077-e88b-4268-8d2f-9844471927c0','EX-332','e70f7906-4e9d-4367-b10a-2ad2a007817a','Radar','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,0,1,0,0),('3f973cac-9537-4264-97a5-829b557d3fe1','EX-496','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','A unidade é dotada de sistema de escoamento de água tanto no boxe do chuveiro quanto no restante da área e a água do chuveiro não transborda para a parte externa do boxe','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('3fe4aeef-98fe-4a5c-9544-b36d9cd831b6','EX-504','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Nos sanitários coletivos as unidades sanitárias estão localizadas em compartimentos separados entre si por divisórias fixas com altura mínima de 1,8 m a partir do piso acabado, providos de portas de acesso','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('3feea2e8-f5d7-4bad-88af-bdb77f4659e7','EX-444','b8ed9a31-9fa3-492f-904e-b8158a06d0da','g) os cabos que conectam as bombas de incêndio ao quadro elétrico de emergência são do tipo resistente ao fogo, quando passam próximos de áreas em que haja elevado risco de incêndio','NORMAM-202/DPC, Cap. 03, Seção III.','flutuando',NULL,30,1,1,1,0,'2026-07-03 05:38:13','2026-09-12 15:21:09',1,1,1,1,1,1),('40284473-c2f6-481c-8a50-8c4d3c5c8a5f','EX-539','71c05e83-0d67-4137-b2b7-478c4241a057','Verificar se os acessos aos locais abaixo relacionados estão livres: Portas de acesso para tripulação e passageiros','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,1,1,1,1,1),('415b0057-acb3-4884-a57f-e8c3473b0e6f','EX-372','a5f25230-91c9-4e14-aa33-e83524d5d943','O sistema de bomba(s) consegue manter, pelo menos, duas tomadas de incêndio distintas com jatos d\'água nunca inferior a 15 m de alcance','NORMAM-202/DPC, Cap. 04, Item 4.14','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 02:36:44',1,1,1,1,1,1),('4174697f-5b23-4140-ac3e-c24ac861b016','EX-414','65bf89f0-f44d-4746-89f7-f530c9aa990d','Verificar a indicação de funcionamento da máquina motriz do(s) “thruster(s)” no passadiço','NORMAM-202/DPC','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('43a7583f-f880-4cf1-bb2c-1f9df67a29d5','EX-479','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Os camarotes para 2 passageiros ou tripulantes possuem dimensões mínimas de 1,9 m x 1,5 m, contendo um beliche duplo','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('446e0844-e616-4c5e-a073-480d64f291d7','EX-389','65bf89f0-f44d-4746-89f7-f530c9aa990d','O arranjo físico da embarcação está de acordo com o Arranjo Geral.','NORMAM-202/DPC','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('450fd87a-eb93-4031-a7a1-237cbfd57c63','EX-483','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Ocorre o transporte de no máximo 4 passageiros ou 9 tripulantes por camarote','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('45180ac3-9c57-4200-a523-3cc0867b3a6b','EX-356','b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','Classe (Embarcações de Sobrevivência/Boias)','NORMAM-202/DPC, Cap. 04, Item 4.12.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('45e58c28-008c-4b2f-85a0-e3c26155d21a','EX-449','b8ed9a31-9fa3-492f-904e-b8158a06d0da','l) todos os circuitos de luz e força, terminando num espaço que contenha tanques de combustível, ou material inflamável, são dotados de chave colocada por fora do referido espaço, para desconectar tais circuitos','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('45f242ee-96c4-4558-8a4f-86bdac810e1a','EX-419','b8ed9a31-9fa3-492f-904e-b8158a06d0da','A fonte de energia elétrica principal foi dimensionada de forma que a potência aparente fornecida ao sistema seja suficiente para evitar quedas de tensões que resultem em desligamento ou oscilação de consumidores em operação devido a partida de motores elétricos de alta corrente','NORMAM-202/DPC, Cap. 03, Seção III.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('47b78ace-bd63-451e-ae51-001de365baaf','EX-333','e70f7906-4e9d-4367-b10a-2ad2a007817a','Verificar se há compasso, régua paralela, borracha, apontador e lápis disponíveis junto das cartas náuticas para uso operacional no traçado de rotas.','NORMAM-202/DPC','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('48501aad-989d-46d0-b36b-56274659a1de','EX-498','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','O lavatório é equipado com torneira de água corrente e dreno','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('4a802f33-84d3-4b5f-b4a5-f8b3accb328b','EX-510','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','A rampa apresenta largura mínima de 0,5 m e contém balaustrada em pelo menos um dos lados com altura de 1 m ou mais','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,0,0,0),('4add624f-894e-442c-bc48-1bf430208d14','EX-423','b8ed9a31-9fa3-492f-904e-b8158a06d0da','A fonte de energia de emergência está localizada, se possível, acima do convés contínuo superior e é de pronto acesso partindo-se do convés aberto.','NORMAM-202/DPC, Cap. 03, Seção IV.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('4bb658ec-309f-4338-b4e6-3a965db20dc7','EX-511','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','A rampa tem resistência suficiente para possibilitar a passagem das pessoas sem apresentar uma flexão significativa','NORMAM-202/DPC, Cap. 03, Seção V.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,0,1,0,0,0),('4c8e77b2-3baa-4674-94e8-8d1fc6708eb1','EX-347','b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','A dotação de boias salva vidas está de acordo com o quadro da NORMAM e estão em boas condições (inclusive as retinidas)','NORMAM-202/DPC, Cap. 04, Item 4.12.','flutuando',NULL,30,1,1,1,0,'2026-07-03 05:38:13','2026-09-12 15:21:09',1,1,1,1,1,1),('4dce80a9-ccad-4b7e-b61c-644a54d2978a','EX-552','71c05e83-0d67-4137-b2b7-478c4241a057','Para as embarcações de casco de madeira, a partir da primeira vistoria, verificar o calafeto','NORMAM-202/DPC','seco',NULL,30,1,0,0,0,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,1,1,1,1,1),('4e94ab4a-31be-4329-b6d5-bf08463c68c0','EX-337','b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','Fabricante (Coletes salva-vidas)','NORMAM-202/DPC, Cap. 04, Item 4.13.','flutuando',NULL,30,1,1,1,0,'2026-07-03 05:38:13','2026-09-12 15:21:09',1,1,1,1,1,1),('4f0cca2c-efa9-40d3-a863-0488fea72d05','EX-514','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Verificar se as tomadas elétricas instaladas nos camarotes estão em perfeito estado físico, com espelhos protetores e energizadas corretamente.','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,1,1,1,1,1),('51377ad9-666c-49d1-80f0-6e43cd20c12a','EX-357','b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','Número de série (se tiver) (Embarcações de Sobrevivência/Boias)','NORMAM-202/DPC, Cap. 04, Item 4.12.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('525223b6-395d-45f9-ae14-7a1c528215f6','EX-301','aa4a7f0d-004d-4a60-924e-693335fdd69b','Certificado de Segurança de Navegação','NORMAM-202/DPC, Cap. 08, Item 8.2.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('532445e2-6334-4633-ad34-ccc907b62a47','EX-380','a5f25230-91c9-4e14-aa33-e83524d5d943','Há instalada uma válvula ou dispositivo similar em cada tomada de incêndio, em posições tais que permitem o fechamento das tomadas com as bombas de incêndio em funcionamento','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,1,1,0,'2026-07-03 05:38:13','2026-09-12 15:21:09',1,1,1,1,1,1),('53fd2924-3c59-434e-9b5c-3ffe3c4c1a7b','EX-451','b8ed9a31-9fa3-492f-904e-b8158a06d0da','n) os fios e cabos elétricos são especificados levando em consideração a capacidade de condução de corrente estabelecida pelo fabricante e a queda de tensão admissível','NORMAM-202/DPC, Cap. 03, Seção IV.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('544e46ae-c5da-46c2-837e-3c112db98f3e','EX-343','b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','Nome da embarcação (Coletes salva-vidas)','NORMAM-202/DPC, Cap. 04, Item 4.13.','flutuando',NULL,30,1,1,1,0,'2026-07-03 05:38:13','2026-09-12 15:21:09',1,1,1,1,1,1),('548d1060-cb9d-4fac-b389-8c03c0ccea29','EX-322','e70f7906-4e9d-4367-b10a-2ad2a007817a','Alarme visual e sonoro de baixa pressão do óleo lubrificante do MCP e MCA com potência igual ou superior a 800 HP (597 kW)','NORMAM-202/DPC, Cap. 09, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,0,1,0,0),('54daf75f-7dd4-4064-84b1-dcc73e0dc352','EX-507','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','A unidade de chuveiro não está instalada em um sanitário coletivo, mas possui área destinada à troca de roupa','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('55d90c7d-3aba-4255-970f-43ce4bcfdaff','EX-349','b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','As retinidas das boias salva vidas possuem 20 m de comprimento e são feitas de material sintético e capazes de flutuar.','NORMAM-202/DPC, Cap. 04, Item 4.12.','flutuando',NULL,30,1,1,1,0,'2026-07-03 05:38:13','2026-09-12 15:21:09',1,1,1,1,1,1),('58133b9a-53e9-454e-bdb7-e5e2b7a1d90c','EX-365','a5f25230-91c9-4e14-aa33-e83524d5d943','A quantidade, capacidade, localização e tipo dos extintores de incêndio estão de acordo com a tabela da NORMAM. Quanto à localização deles, seguem o determinado no Plano de Segurança (se existente)','NORMAM-202/DPC, Cap. 04, Item 4.2), 4.2.1, m, I.','flutuando',NULL,30,1,1,1,0,'2026-07-03 05:38:13','2026-09-12 15:21:09',1,1,1,1,1,1),('585d1cfe-309c-40aa-be0e-4804eda5310a','EX-473','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','As cadeiras deverão atender às seguintes dimensões: a) largura mínima de 0,45 m de para os bancos simples','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('58e5b2aa-0482-4c9b-82a3-01c000cb1bb5','EX-489','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','A área mínima requerida para o transporte turísticos sem pernoite a bordo, considera a concentração de 1,5 passageiros/m². No cálculo dessas áreas estão computadas as áreas de estivagem de bagagens ou transporte de carga, nem as escadas','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('5a63ec6b-964c-4a41-a1d7-53fa6980ba2e','EX-545','71c05e83-0d67-4137-b2b7-478c4241a057','O comprimento total, boca moldada e pontal moldado do casco da embarcação estão de acordo com aqueles anotados no Memorial Descritivo','NORMAM-202/DPC','seco',NULL,30,1,0,0,0,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,1,1,1,1,1),('5b125c67-ea0c-45a2-905e-437027445eb7','EX-439','b8ed9a31-9fa3-492f-904e-b8158a06d0da','b) os cabos são individualmente fixados a leitos ou suportes','NORMAM-202/DPC, Cap. 03, Seção IV.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('5b502640-d457-410d-9580-8ed3d5e95d81','EX-454','f299c8c7-4402-4efa-89c6-d5add1fa60d5','Toda embarcação que seja dotada de um equipamento fixo de radiocomunicação, deverá possuir a licença rádio, emitida pela Agência Nacional de Telecomunicações (ANATEL).','NORMAM-202/DPC, Cap. 04, Item 4.8), 4.8.1.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:04:13',1,0,0,1,0,0),('5d288f7e-25e6-4e36-b8aa-093601403d54','EX-390','65bf89f0-f44d-4746-89f7-f530c9aa990d','Verificar a limpeza dos espaços de máquinas e equipamentos. Os espaços e equipamentos de máquinas deverão ser mantidos limpos e sem vazamentos de óleos e com os estrados em bom estado de conservação','NORMAM-202/DPC, Cap. 09, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('5df039e6-b400-4fd0-abd2-83959587485a','EX-395','65bf89f0-f44d-4746-89f7-f530c9aa990d','A iluminação deverá possibilitar que nenhuma área superior a 1 m² fique sem iluminação','NORMAM-202/DPC, Cap. 03, Seção IV.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('5f8a7cb6-2019-4100-a02f-96c076e65b5d','EX-366','a5f25230-91c9-4e14-aa33-e83524d5d943','Os extintores com peso bruto superior a 25 kg (quando carregados) possuem mangueiras ou esguichos adequados ou outros meios praticáveis para que atendam o espaço a que se destinam.','NORMAM-202/DPC, Cap. 04, Seção III.','flutuando',NULL,30,1,1,1,0,'2026-07-03 05:38:13','2026-09-12 15:21:09',1,1,1,1,1,1),('60f87d12-e57b-4063-ad67-b625f26f3093','EX-361','b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','Dotação de artefatos pirotécnicos conforme NORMAM e catálogo de material homologado da DPC','NORMAM-202/DPC, Cap. 04, Seção II.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('616c56c7-ec03-4fc1-8fe8-c5a5c9321130','EX-369','a5f25230-91c9-4e14-aa33-e83524d5d943','As canalizações utilizadas para a distribuição de gás estão em boas condições e têm proteção adequada contra o calor e, se flexíveis, atendem às normas da Associação Brasileira de Normas Técnicas (ABNT)','NORMAM-202/DPC, Cap. 04, Item 4.29.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('61e1dc4b-494e-46d8-b8eb-f0f2f6f8b8b6','EX-488','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Área mínima requerida em travessia com até 1 hora de duração considera a concentração de 4 passageiros por m²','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('62c73930-c97e-40c7-8241-0ca46b7ce652','EX-551','71c05e83-0d67-4137-b2b7-478c4241a057','Os perfis (transversais, longitudinais e “diagonais”) e anteparas estão devidamente soldados nos respectivos locais onde devem ser ligados','NORMAM-202/DPC, Cap. 04, Seção I.','seco',NULL,30,1,0,0,0,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,1,1,1,1,1),('62ed00d9-c647-40fc-82dc-cdd0feb36475','EX-352','b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','As embarcações de sobrevivência infláveis possuem o certificado de revisão dentro do prazo de validade e foram revisadas em estação de manutenção autorizada pela DPC','NORMAM-202/DPC','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('63ec6d70-d445-4051-9851-f414c26fb7b7','EX-525','71c05e83-0d67-4137-b2b7-478c4241a057','A dotação das luzes atende as regras sobre o assunto para este tipo de embarcação','NORMAM-202/DPC','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,1,1,1,1,1),('64264fe0-373e-4c75-82be-3665162220eb','EX-317','e70f7906-4e9d-4367-b10a-2ad2a007817a','Lanterna portátil com bateria recarregável ou pilhas sobressalentes','NORMAM-202/DPC, Cap. 03, Seção IV.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('6a368da8-410c-42df-bbc2-f58bfdb9806b','EX-499','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','O lavatório do tipo coletivo considera 0,6 m por pessoa','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('6d6d6309-d8f2-4d2a-86a2-01e902c50df9','EX-400','65bf89f0-f44d-4746-89f7-f530c9aa990d','Redes de descarga e aspiração da praça de máquinas conectadas ao fundo ou ao costado deverão ser metálicas','NORMAM-202/DPC, Cap. 03, Seção III.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('6e55abe3-ccfb-41d0-8365-c6c5f838e658','EX-540','71c05e83-0d67-4137-b2b7-478c4241a057','Verificar se os acessos aos locais abaixo relacionados estão livres: Equipamentos de salvatagem e combate a incêndio','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,1,1,1,1,1),('6f4dc9b2-6ff0-4ca5-9b9f-649913e95d75','EX-547','71c05e83-0d67-4137-b2b7-478c4241a057','Os posicionamentos dos tanques de consumíveis estão de acordo com aqueles anotados no Plano de Capacidades. Caso seja necessário, deverá ser requerida a abertura do fundo duplo','NORMAM-202/DPC','seco',NULL,30,1,0,0,0,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,1,1,1,1,1),('7208560e-f098-4ed4-a6db-04e305b59b2b','EX-436','b8ed9a31-9fa3-492f-904e-b8158a06d0da','Os circuitos das luzes de navegação são individualmente protegidos por fusíveis ou disjuntores instalados no painel de controle ou quadro de luzes de navegação','RIPEAM 72 / NORMAM-202/DPC, Cap. 04.','flutuando',NULL,30,1,1,1,0,'2026-07-03 05:38:13','2026-09-12 15:21:09',1,1,1,1,1,1),('73540b8b-e8bd-4d3e-b08d-77ed59461bce','EX-503','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','A unidade sanitária é composta de um vaso sanitário de louça vitrificada, dotado de fluxo de água (descarga) para sua limpeza e acessórios','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('73f848be-eb6b-4e0a-b0b1-67a6ee583f3f','EX-348','b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','As boias salva vidas e sua retinida não estão presas ou amarradas à embarcação, estando apenas apoiadas em seus suportes, prontas para serem lançadas','NORMAM-202/DPC, Cap. 04, Item 4.12.','flutuando',NULL,30,1,1,1,0,'2026-07-03 05:38:13','2026-09-12 15:21:09',1,1,1,1,1,1),('76456380-e872-472e-80de-465dc9969111','EX-312','aa4a7f0d-004d-4a60-924e-693335fdd69b','Tabelas ou quadros no comando: - balizamento','NORMAM-202/DPC','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,0,1,0,0),('7661f5f9-cff5-4173-9b00-6e4337d2e45f','EX-330','e70f7906-4e9d-4367-b10a-2ad2a007817a','Quadro elétrico de luzes/sistemas de comunicação','NORMAM-202/DPC, Cap. 03, Seção IV.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,0,1,0,0),('76ed1958-0074-4027-be8a-45a0f35ebaa8','EX-518','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','O arranjo físico da embarcação está de acordo com o Arranjo Geral. Devem ser verificados os compartimentos em relação ao seu posicionamento e destinação','NORMAM-202/DPC','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,1,1,1,1,1),('7a31837c-64ee-47e2-9f6b-d4b5cd5108b1','EX-403','65bf89f0-f44d-4746-89f7-f530c9aa990d','Os indicadores de níveis dos tanques de óleo deverão ser dotados de válvulas (preferencialmente do tipo esfera), que deverão ser instaladas na parte inferior do respectivo indicador','NORMAM-202/DPC, Cap. 09, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('7a9f7a2a-d2df-43ae-bb1b-14c77c92ad36','EX-519','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Todos os níveis de acomodações, de compartimentos de serviço ou da praça de máquinas possui, pelo menos, duas vias de escape amplamente separadas, provenientes de cada compartimento restrito ou grupos de compartimentos','NORMAM-202/DPC, Cap. 03, Seção III.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,0,1,1,0,0),('7c148a99-d39d-4dce-9428-a65d8c9e9a39','EX-474','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','As cadeiras deverão atender às seguintes dimensões: b) largura mínima de 0,86 m de para os bancos duplos ou combinações desses','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('7dca1f10-d3ca-4efb-aaad-05c38b4e02de','EX-548','71c05e83-0d67-4137-b2b7-478c4241a057','Os equipamentos de carga, propulsão, energia e governo da embarcação estão de acordo com o Memorial Descritivo.','NORMAM-202/DPC, Cap. 03, Seção IV.','seco',NULL,30,1,0,0,0,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,1,1,1,1,1),('7fe5827d-bbc9-4041-b881-c55b5edc1563','EX-394','65bf89f0-f44d-4746-89f7-f530c9aa990d','As superfícies quentes deverão ser providas de proteções térmicas, a fim de minimizar o risco de queimaduras nos tripulantes','NORMAM-202/DPC','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('7fed81ee-7071-42cc-8f8b-eb18d5346505','EX-512','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','A rampa é dotada de dispositivo antiderrapante no piso (o qual poderá consistir de travessões instalados no sentido transversal com espaçamento não superior a 0,50 m)','NORMAM-202/DPC, Cap. 03, Seção V.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,0,1,0,0,0),('805c0314-b1b1-4061-8c40-d25398d2e53f','EX-472','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','O espaço de cadeiras possui pelo menos 2 portas de acesso opostas','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('8640b086-97b1-4cf5-b853-86b0b9504e30','EX-490','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Número mínimo de aparelhos sanitários conforme tabelas regulamentares','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('86ccce9f-605d-4896-871b-d7775e23014f','EX-491','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Todos os banheiros são dotados de ventilação natural, através de janela ou cachimbo, ou ventilação forçada','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('88af8f67-9df3-429a-8d9d-bb04d74345ec','EX-538','71c05e83-0d67-4137-b2b7-478c4241a057','As embarcações de propriedade de órgãos públicos serão caracterizadas por meio de letras e distintivos adotados por seus respectivos órgãos.','NORMAM-202/DPC','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,1,1,1,1,1),('8b5b4c03-0824-4f51-ab4b-2b1c27640900','EX-303','aa4a7f0d-004d-4a60-924e-693335fdd69b','O armador deverá apresentar a Provisão de Registro da Propriedade Marítima (PRPM) ou caso a embarcação não possua apresentar Documento Provisório de Propriedade (DPP).','NORMAM-202/DPC, Cap. 02, Item 2.1.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:04:13',1,1,1,1,1,1),('8d78d063-e888-4a5b-994b-5c61e704fc44','EX-364','a5f25230-91c9-4e14-aa33-e83524d5d943','Na saída de cada tanque de combustível há uma válvula de fechamento capaz de interromper o fluxo da rede','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('8ed00d22-c8ee-40f5-be7c-64f9e9acc83d','EX-456','f299c8c7-4402-4efa-89c6-d5add1fa60d5','A embarcação possui a licença de estação do navio em vigor, emitida pela ANATEL','ANATEL / NORMAM','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-03 05:41:41',1,0,0,1,0,0),('902653ef-7f5d-497e-a1f4-d78f31212d7c','EX-441','b8ed9a31-9fa3-492f-904e-b8158a06d0da','d) os cabos e fiação estão instalados e fixados de modo a evitar desgastes por atrito ou outra avaria','NORMAM-202/DPC, Cap. 03, Seção IV.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('9339e3f3-a72d-48ab-8f33-eb449e5f7395','EX-385','a5f25230-91c9-4e14-aa33-e83524d5d943','Todos os esguichos das mangueiras que servem às tomadas localizadas no compartimento de máquinas ou localizadas junto a tanques de carga de líquidos inflamáveis são de duplo emprego, isto é, borrifo e jato sólido, incluindo um dispositivo de fechamento','NORMAM-202/DPC, Cap. 04, Seção III.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('934b7190-7444-4f16-96bd-a367c6953b9c','EX-321','e70f7906-4e9d-4367-b10a-2ad2a007817a','Limpador de para-brisa ou vigia rotativa','NORMAM-202/DPC, Cap. 03, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,0,1,0,0),('94a99554-75f0-4da2-9e4f-f2c089ee8141','EX-327','e70f7906-4e9d-4367-b10a-2ad2a007817a','Transceptor para o Sistema de Identificação Automática homologado pela ANATEL (Automatic Identification System - AIS)','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,0,1,0,0),('9537c200-5b45-4d8b-b670-505c5c936f79','EX-427','b8ed9a31-9fa3-492f-904e-b8158a06d0da','Quanto aos quadros elétricos: a) todos eles são dispostos de maneira que ofereçam fácil acesso durante a operação e ou manutenção dos equipamentos','NORMAM-202/DPC','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('95822d65-14fa-4d61-a80c-93b779751ed4','EX-528','71c05e83-0d67-4137-b2b7-478c4241a057','As luzes atendem aos setores (ângulos) corretos','NORMAM-202/DPC','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,1,1,1,1,1),('95f9e766-875a-48f0-93bb-149d9e29f784','EX-460','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Todos os espaços destinados ao transporte e ou permanência de passageiros apresentam pés-direitos (vão entre o piso e o teto) de no mínimo 1,90 m','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('990defff-5140-4561-b20a-e9a67b74e9a0','EX-506','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','A unidade de chuveiro é composta por um chuveiro com jato d ́água com altura de queda mínima de 1,9 m e seus acessórios, localizada em compartimento separado das demais áreas por um meio que evite respingos (box)','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('991a0bbc-deb5-4b81-8305-c4d102e95e50','EX-410','65bf89f0-f44d-4746-89f7-f530c9aa990d','Motores com potência igual ou superior a 800 HP deverão ser dotados de um painel local ou remoto, com as seguintes indicações: RPM, temperatura da água de arrefecimento, pressão e temperatura do óleo lubrificante','NORMAM-202/DPC, Cap. 03, Seção III.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('9979e589-44dd-4790-9574-4adb561aaf7d','EX-461','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','A circulação nas áreas de embarque e desembarque, nos corredores e escadas é livre e independente das demais áreas da embarcação. Nas embarcações com AB maior que 50, os corredores maiores que 7 m, possui, pelo menos, 2 vias de acesso/escape','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('99be0275-f74e-49e6-aac2-fce3b372fecf','EX-517','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Verificar a existência físico-documental e o correto preenchimento do livro de registro de lixo a bordo.','NORMAM-202/DPC, Cap. 09, Item 9.2','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:14','2026-07-04 02:36:44',1,1,1,1,1,1),('9be9b57c-5702-4e46-9703-4414b0c8ce56','EX-319','e70f7906-4e9d-4367-b10a-2ad2a007817a','Binóculo 7x50','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,0,1,0,0),('9c039242-cd6f-4dae-b2ea-628efe60d3cd','EX-485','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','O topo do colchão inferior está a pelo menos 0,3 m do convés (piso do camarote)','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('9d9028b2-a785-4a1a-bf9a-db04ae0e3e95','EX-331','e70f7906-4e9d-4367-b10a-2ad2a007817a','Sistema de comunicação interna, interligando, pelo menos, passadiço, praça de máquinas e compartimento da máquina do leme, propiciando troca de informações nos dois sentidos','NORMAM-202/DPC, Cap. 03, Seção III.','flutuando',NULL,30,1,1,1,0,'2026-07-03 05:38:13','2026-09-12 15:21:09',1,0,0,1,0,0),('9dc4b5a1-2d0e-4821-8be6-c4fe3a8e8ee0','EX-470','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','A largura mínima do vão de acesso ao compartimento é maior ou igual à largura do corredor de acesso à abertura','NORMAM-202/DPC, Cap. 03, Seção V.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('9e411c90-8ac2-4499-8ca7-2bcda5d07503','EX-420','b8ed9a31-9fa3-492f-904e-b8158a06d0da','Para embarcações com AB maior ou igual a 300 a fonte de emergência de energia elétrica é um gerador acionado por um motor com suprimento independente de combustível','NORMAM-202/DPC, Cap. 03, Seção III.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,0,1,0,0),('9e7cda40-92d3-4ba1-b90d-bca3d3071994','EX-328','e70f7906-4e9d-4367-b10a-2ad2a007817a','Indicador do ângulo do leme no passadiço ou comando','NORMAM-202/DPC, Cap. 03, Seção III.','flutuando',NULL,30,1,1,1,0,'2026-07-03 05:38:13','2026-09-12 15:21:09',1,0,0,1,0,0),('a0662bd3-30ea-4206-82e2-51b4a8fa3f8a','EX-535','71c05e83-0d67-4137-b2b7-478c4241a057','A estrutura (flutuante fixa) está sinalizada por uma luz fixa amarela, com alcance mínimo de duas milhas náuticas, estabelecida no seu tope ou em local de melhor visibilidade para o navegante.','NORMAM-202/DPC, Cap. 03, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:14','2026-07-04 04:12:38',0,0,1,0,0,0),('a0acbebe-660c-4f48-9da8-64bd45b91455','EX-345','b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','Os coletes salva vidas estão em bom estado de conservação e com apito','RIPEAM 72 / NORMAM-202/DPC, Cap. 04.','flutuando',NULL,30,1,1,1,0,'2026-07-03 05:38:13','2026-09-12 15:21:09',1,1,1,1,1,1),('a0e3d499-45d6-4908-bed1-c1da5138641f','EX-416','65bf89f0-f44d-4746-89f7-f530c9aa990d','Verificar se as luminárias na praça de máquinas possuem proteção antichoque física em invólucros do tipo \'tartaruga\' e se acendem normalmente.','NORMAM-202/DPC, Cap. 03, Seção III.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('a194202b-f4c6-4cbe-bf63-a5216292653b','EX-450','b8ed9a31-9fa3-492f-904e-b8158a06d0da','m) os circuitos polifásicos são distribuídos de modo a assegurar o melhor equilíbrio de cargas entre fases','NORMAM-202/DPC','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('a19d11c1-6666-4459-80ae-5e82c990f243','EX-318','e70f7906-4e9d-4367-b10a-2ad2a007817a','Apito','RIPEAM 72 / NORMAM-202/DPC, Cap. 04.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('a1d44288-9e8d-4cc9-abef-7bf1f296e426','EX-422','b8ed9a31-9fa3-492f-904e-b8158a06d0da','O grupo gerador de emergência ou a bateria de emergência foi instalado, preferencialmente, fora do compartimento das máquinas e dos geradores principais. A antepara de separação entre os compartimentos é, preferencialmente, estanque e resistente ao fogo','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('a1f22623-e022-464e-bd02-d1e056aab5db','EX-482','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Os camarotes com camas simples possuem área mínima de 2,6 m² por pessoa','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('a371bf33-76aa-11f1-9eb5-0a1b2af87b16','CBL-001','71c05e83-0d67-4137-b2b7-478c4241a057','Há passagem permanentemente desobstruída de proa à popa, que não é efetivada por cima de tampas de escotilhas. Tal passagem possui largura mínima em conformidade com o estabelecido no Anexo 3-M','NORMAM-202/DPC, Cap. 03, Seção I.','borda_livre',NULL,30,1,0,0,0,'2026-07-03 06:44:28','2026-07-04 04:12:38',1,1,1,1,1,1),('a371da38-76aa-11f1-9eb5-0a1b2af87b16','CBL-002','71c05e83-0d67-4137-b2b7-478c4241a057','Em todas as partes expostas dos conveses principais e de superestruturas há eficientes balaustradas ou bordas falsas (que poderão ser removíveis), com altura não inferior a 1 metro (para embarcações com AB maior que 20)','NORMAM-202/DPC, Cap. 04, Seção I.','borda_livre',NULL,30,1,0,0,0,'2026-07-03 06:44:28','2026-07-04 04:12:38',1,1,1,1,1,1),('a371f205-76aa-11f1-9eb5-0a1b2af87b16','CBL-003','71c05e83-0d67-4137-b2b7-478c4241a057','A abertura inferior da balaustrada apresenta altura menor ou igual a 230 mm e os demais vãos não poderão apresentar espaçamento superior a 380 mm. No caso de embarcações com bordas arredondadas, os suportes das balaustradas deverão ser colocados na parte plana do convés','NORMAM-202/DPC, Cap. 04, Seção I.','borda_livre',NULL,30,1,0,0,0,'2026-07-03 06:44:28','2026-07-04 04:12:38',1,1,1,1,1,1),('a3721459-76aa-11f1-9eb5-0a1b2af87b16','CBL-004','71c05e83-0d67-4137-b2b7-478c4241a057','Para embarcações que possuam borda falsa, estas deverão possuir saídas d’água respeitando o determinado no item 0609','NORMAM-202/DPC','borda_livre',NULL,30,1,0,0,0,'2026-07-03 06:44:28','2026-07-04 04:12:38',1,1,1,1,1,1),('a3722d96-76aa-11f1-9eb5-0a1b2af87b16','CBL-005','71c05e83-0d67-4137-b2b7-478c4241a057','Nas embarcações dos tipos A, B ou D, as vigias e olhos de boi, se existentes nos costados abaixo do convés de borda livre, deverão apresentar as seguintes características: a) ser estanque à água (ou apresentar meios que possibilitem o seu fechamento estanque à água) b) ser de construção sólida c) ser provida de vidros temperados de espessura compatível com seu diâmetro d) não podem ser do tipo “removível” e) caso rebatíveis, deverão permanecer fechadas quando em viagem, devendo haver uma placa, permanentemente fixada junto à vigia, alertando que a mesma deverá permanecer fechada quando em viagem','NORMAM-202/DPC, Cap. 05, Item 5.1.','borda_livre',NULL,30,1,1,1,0,'2026-07-03 06:44:28','2026-09-12 15:21:09',1,1,0,1,0,0),('a37244fe-76aa-11f1-9eb5-0a1b2af87b16','CBL-006','71c05e83-0d67-4137-b2b7-478c4241a057','As aberturas no costado de embarcações dos tipos A, B ou D deverão possuir tampas estanques à água ou vigias e olhos de boi e deverão estar posicionadas de forma que sua aresta inferior esteja a, pelo menos, 300 mm acima da linha d’água carregada, em qualquer condição esperada de trim. Para as embarcações dos tipos C ou E essa distância não deverá ser inferior a 500 mm','NORMAM-202/DPC, Cap. 03, Seção I.','borda_livre',NULL,30,1,0,0,0,'2026-07-03 06:44:28','2026-07-04 04:12:38',1,1,1,1,1,1),('a3725c4c-76aa-11f1-9eb5-0a1b2af87b16','CBL-007','71c05e83-0d67-4137-b2b7-478c4241a057','As portas externas que possibilitem, direta ou indiretamente, o acesso ao interior de qualquer compartimento localizado abaixo do convés de borda livre ou ao interior de uma superestrutura fechada, deverão ter uma soleira mínima de 150 mm (260 mm para embarcações que operam em área 2)','NORMAM-202/DPC, Cap. 05, Item 5.1.','borda_livre',NULL,30,1,1,1,0,'2026-07-03 06:44:28','2026-09-12 15:21:09',1,1,1,1,1,1),('a37275b5-76aa-11f1-9eb5-0a1b2af87b16','CBL-008','71c05e83-0d67-4137-b2b7-478c4241a057','Os escotilhões e as aberturas de escotilha possuem braçola de pelo menos 150 mm de altura (260 mm para embarcações que operam em área 2) e são dotados de tampas que possam ser fixadas às braçolas. As embarcações dos tipos “C” e “E” estão dispensadas da obrigatoriedade de possuírem tampas de escotilha ou dos escotilhões','NORMAM-202/DPC, Cap. 03, Seção I.','borda_livre',NULL,30,1,0,0,0,'2026-07-03 06:44:28','2026-07-04 04:12:38',1,1,0,1,0,1),('a3728c4f-76aa-11f1-9eb5-0a1b2af87b16','CBL-009','71c05e83-0d67-4137-b2b7-478c4241a057','As tampas das aberturas de escotilha, dos escotilhões e seus respectivos dispositivos de fechamento têm resistência suficiente que permite satisfazer as condições de estanqueidade previstas para o tipo de embarcação considerada e apresenta todos os elementos necessários que asseguram a estanqueidade','NORMAM-202/DPC, Cap. 03, Seção III.','borda_livre',NULL,30,1,1,1,0,'2026-07-03 06:44:28','2026-09-12 15:21:09',1,1,1,1,1,1),('a372a38d-76aa-11f1-9eb5-0a1b2af87b16','CBL-010','71c05e83-0d67-4137-b2b7-478c4241a057','Os suspiros externos, situados acima do convés de borda livre, deverão apresentar as seguintes caraterísticas: a) extremidade superior do suspiro em forma de “U” invertido ou com arranjo que proteja a sua abertura da entrada de água proveniente das intempéries; b) distância vertical entre o ponto a partir da qual a água efetivamente tem acesso ao tanque ou compartimento abaixo e o convés onde o suspiro se encontra instalado maior ou igual a 450 mm (760 mm nos conveses de borda livre e 450 mm nos demais conveses para embarcações que operam em área 2)','NORMAM-202/DPC, Cap. 05, Item 5.1.','borda_livre',NULL,30,1,1,1,0,'2026-07-03 06:44:28','2026-09-12 15:21:09',1,1,1,1,1,1),('a372bc98-76aa-11f1-9eb5-0a1b2af87b16','CBL-011','71c05e83-0d67-4137-b2b7-478c4241a057','Dispositivos de iluminação e ou ventilação natural (alboios) de compartimentos situados abaixo do convés de borda livre, que estão situados imediatamente acima do referido convés, deverão: a) ser estanque ao tempo (ou dispor de meios que possibilitem o seu fechamento estanque ao tempo) b) ser dotado de vidros com espessura compatível com sua área e máxima dimensão linear c) apresentar braçolas com, pelo menos, 150 mm de altura (260 mm para embarcações que operam em área 2)','NORMAM-202/DPC, Cap. 05, Item 5.1.','borda_livre',NULL,30,1,1,1,0,'2026-07-03 06:44:28','2026-09-12 15:21:09',1,1,1,1,1,1),('a372d307-76aa-11f1-9eb5-0a1b2af87b16','CBL-012','71c05e83-0d67-4137-b2b7-478c4241a057','Os dutos de ventilação ou exaustão destinados aos espaços situados abaixo do convés de borda livre deverão apresentar a borda inferior de sua extremidade externa com pelo menos 450 mm de altura acima do referido convés (760 mm para embarcações que operam em área 2)','NORMAM-202/DPC, Cap. 05, Item 5.1.','borda_livre',NULL,30,1,1,1,0,'2026-07-03 06:44:28','2026-09-12 15:21:09',1,1,0,1,0,1),('a372e880-76aa-11f1-9eb5-0a1b2af87b16','CBL-013','71c05e83-0d67-4137-b2b7-478c4241a057','Para embarcações que operam em área 2, as venezianas instaladas em anteparas ou portas externas, destinadas à ventilação de compartimentos situados sob o convés de borda livre ou superestruturas fechadas, e que não possuam meios efetivos de fechamento que as tornem estanques ao tempo, deverão possuir altura mínima de 760 mm','NORMAM-202/DPC, Cap. 05, Item 5.1.','borda_livre',NULL,30,1,1,1,0,'2026-07-03 06:44:28','2026-09-12 15:21:09',1,1,1,1,1,1),('a373033e-76aa-11f1-9eb5-0a1b2af87b16','CBL-014','71c05e83-0d67-4137-b2b7-478c4241a057','A extremidade junto ao costado dos tubos de descarga, provenientes de espaços situados abaixo do convés de borda livre ou de superestruturas fechadas, deverá ser dotada de válvulas de retenção e fechamento (combinadas ou não). Os meios disponíveis para operação de válvula de fechamento deverão ser facilmente acessíveis e estar sempre disponíveis (ver exigência abaixo)','NORMAM-202/DPC, Cap. 05, Item 5.1.','borda_livre',NULL,30,1,1,1,0,'2026-07-03 06:44:28','2026-09-12 15:21:09',1,1,1,1,1,1),('a3731baa-76aa-11f1-9eb5-0a1b2af87b16','CBL-015','71c05e83-0d67-4137-b2b7-478c4241a057','Quando a descarga se dá por gravidade e a distância vertical entre o ponto de descarga no costado e a extremidade superior do tubo for maior ou igual a 1,20 m (2,0 m para embarcações que operam em área 2) as válvulas poderão ser de fechamento sem retenção (ver exigência acima)','NORMAM-202/DPC','borda_livre',NULL,30,1,0,0,0,'2026-07-03 06:44:28','2026-07-04 04:12:38',1,1,1,1,1,1),('a3733364-76aa-11f1-9eb5-0a1b2af87b16','CBL-016','71c05e83-0d67-4137-b2b7-478c4241a057','As descargas de gases provenientes de motores de combustão interna que sejam posicionadas na popa ou nos costados, mesmo quando associadas à descarga de água de refrigeração dos motores (“descarga molhada”), estão dispensadas da obrigatoriedade da instalação de válvulas de retenção ou fechamento, mas deverão atender aos seguintes requisitos: a) deverão ser flangeadas no casco b) beverão ser de aço ou material equivalente nas proximidades do casco','NORMAM-202/DPC, Cap. 03, Seção III.','borda_livre',NULL,30,1,0,0,0,'2026-07-03 06:44:28','2026-07-04 04:12:38',1,1,1,1,1,1),('a373534c-76aa-11f1-9eb5-0a1b2af87b16','CBL-017','71c05e83-0d67-4137-b2b7-478c4241a057','Embarcações dos tipos D e E que operem em área 2 deverão possuir altura mínima de proa de acordo com o item 0619','NORMAM-202/DPC','borda_livre',NULL,30,1,0,0,0,'2026-07-03 06:44:28','2026-07-04 04:12:38',0,0,0,1,1,0),('a373c1f4-76aa-11f1-9eb5-0a1b2af87b16','CBL-018','71c05e83-0d67-4137-b2b7-478c4241a057','O Disco de Plimsoll está posicionado conforme Notas para a Marcação da Borda Livre.','NORMAM-202/DPC, Cap. 05, Item 5.1.','borda_livre',NULL,30,1,1,1,0,'2026-07-03 06:44:28','2026-09-12 15:21:09',1,1,1,1,1,1),('a3a06b64-50be-420a-9892-2c189dcbe724','EX-426','b8ed9a31-9fa3-492f-904e-b8158a06d0da','As baterias deverão: c) atender a uma altura mínima de 40 cm do piso, quando fixadas em conveses situados abaixo do convés principal','NORMAM-202/DPC, Cap. 03, Seção IV.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('a431a945-f958-40bc-9491-058a3d643c98','EX-464','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Há espaço livre para circulação nos bordos da embarcação, ao longo de todos os espaços para redes. Essa circulação deverá apresenta largura mínima de 800 mm por bordo','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('a4f04bb2-0533-498c-970e-73a3c5de19e2','EX-412','65bf89f0-f44d-4746-89f7-f530c9aa990d','Verificar o funcionamento do alarme de nível alto de esgoto (visual e ou sonoro), emitido na praça de máquinas e no comando – para embarcações com AB maior que 20','NORMAM-202/DPC, Cap. 03, Seção III.','flutuando',NULL,30,1,1,1,0,'2026-07-03 05:38:13','2026-09-12 15:21:09',1,0,0,1,0,0),('a73b0ca4-6bbb-41d6-ac23-410beabbe8b9','EX-309','aa4a7f0d-004d-4a60-924e-693335fdd69b','Certificado de conformidade para transporte de produtos químicos perigosos a granel (se aplicável)','NORMAM-202/DPC','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('a9916551-a7e8-49b4-aa43-ee43ed71e60f','EX-466','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','A área mínima requerida para o transporte de passageiros em redes considera a concentração de 1 passageiro por m², sem rede em cima de rede. No cálculo dessa área não estão computadas as áreas de circulação, de embarque e desembarque, de estivagem de bagagens ou transporte de carga, nem corredores ou escadas','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('ac2e0924-d475-4f40-8429-553d94cbd7c1','EX-445','b8ed9a31-9fa3-492f-904e-b8158a06d0da','h) nos compartimentos e locais onde existe depósito de materiais inflamáveis, os interruptores, tomadas de correntes, luminárias e demais equipamentos elétricos são à prova de explosão','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('ad528287-01ba-4c8f-ac0a-0203113ba8c6','EX-465','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Ocorre o transporte simultâneo de passageiros em redes e em bancos laterais, junto aos bordos, e o limite de espaço para redes se iniciar a não menos de 1,70m da face interna da balaustrada do convés considerado','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('ad8b2645-95b8-4f61-a654-5610123e893e','EX-404','65bf89f0-f44d-4746-89f7-f530c9aa990d','As tubulações advindas dos tanques de óleo, por intermédio da qual o óleo é conduzido às máquinas principais ou auxiliares, deverão ser de material metálico ou material resistente ao fogo e possuir válvula de fechamento rápido, o qual deverá ser testado','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('ae76d3fb-35cf-4108-81f2-4d0e8a579cab','EX-418','b8ed9a31-9fa3-492f-904e-b8158a06d0da','A fonte de energia elétrica principal consegue manter em funcionamento todos os serviços essenciais independentemente do sentido e da velocidade de rotação das máquinas principais e do eixo propulsor','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('af6b1cb2-e94a-452c-a083-9b7e2f41ff69','EX-392','65bf89f0-f44d-4746-89f7-f530c9aa990d','Motores cujo sistema de arrefecimento seja constituído por ventiladores deverão ter os mesmos providos de proteção','NORMAM-202/DPC, Cap. 03, Seção III.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('b16a6bde-ff11-49be-aa7e-ad733190b39c','EX-360','b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','Porto de inscrição (Embarcações de Sobrevivência/Boias)','NORMAM-202/DPC, Cap. 04, Item 4.12.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('b27da535-c866-4c52-9a83-b3e5b10072e0','EX-320','e70f7906-4e9d-4367-b10a-2ad2a007817a','Prumo de mão','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,0,1,0,0),('b3e0478a-37ea-4ecf-a8f7-d81e816f1a25','EX-408','65bf89f0-f44d-4746-89f7-f530c9aa990d','Toda tubulação de gás (não de cozinha), combustível, óleo lubrificante, substancias inflamáveis em geral e fiações não poderá distar menos que 200 mm das tubulações de descarga ou de quaisquer superfícies em alta temperatura','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('b3f0b053-6c41-42f4-adb3-a3f0d76c9e05','EX-531','71c05e83-0d67-4137-b2b7-478c4241a057','A antepara de colisão de vante está posicionada entre 5 e 8% do Lregra, a partir da parte superior do espelho ou da roda de proa','NORMAM-202/DPC','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,0,1,1,0,0),('b56def21-6b53-42cc-a16b-35f5a0a63c59','EX-476','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','As cadeiras deverão atender às seguintes dimensões: d) distância mínima de 0,90 m entre os encostos dos assentos montados frente a frente, ou entre o encosto e uma antepara, ou outra divisão que por ventura exista à frente do assento','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('b5ce3089-e78e-4390-99bb-e8855acd1ffd','EX-397','65bf89f0-f44d-4746-89f7-f530c9aa990d','Todo espaço de máquinas deverá ter ventilação (forçada ou natural) apropriada ao funcionamento dos equipamentos','NORMAM-202/DPC','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('b5f8b4f6-cb8d-432f-b7cd-52bdb1121ae8','EX-478','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Os corredores de circulação e ou acesso aos camarotes apresentam largura mínima de 0,8 m para um comprimento máximo de 10 m. Quando o comprimento dos corredores internos excede a 10 m, a largura mínima é acrescida de 0,05 m para cada 2 m ou fração a mais no comprimento, até o máximo de 1 m','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('b6db0410-2703-4196-993a-ed9f04038200','EX-533','71c05e83-0d67-4137-b2b7-478c4241a057','Há antepara a vante da praça de máquinas, somente embarcações de passageiros','NORMAM-202/DPC, Cap. 03, Seção III.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,0,1,1,0,0),('b7545aa5-51fe-44d7-9513-fd491720ace9','EX-302','aa4a7f0d-004d-4a60-924e-693335fdd69b','Cartão de Tripulação de Segurança','NORMAM-202/DPC, Cap. 04, Item 4.2), 4.2.1, m, III','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 02:36:44',1,1,1,1,1,1),('b8b68324-6f6c-48d4-af7f-84d98d71eca7','EX-516','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Verificar a afixação de placa educativa em local visível no convés com os dizeres: \'Não jogue lixo no rio, deposite seu lixo aqui\'.','NORMAM-202/DPC, Cap. 09, Item 9.2','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:14','2026-07-04 02:36:44',1,1,1,1,1,1),('bac0b5fb-e1ef-4ce4-b171-36716b176f2e','EX-424','b8ed9a31-9fa3-492f-904e-b8158a06d0da','As baterias deverão: a) ser instaladas em locais não habitados, arejados e abrigados','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('bac38230-26ef-427d-b223-0d1b0bc96b03','EX-487','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Nos camarotes há ventilação natural por janela ou alboio, dando para o exterior da embarcação, com uma abertura mínima de 0,1 m² por janela ou alboio. A ventilação natural pode ser substituída por ventilação forçada através de ventilador e ou ar condicionado','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('bb1b61cc-c7fb-4a39-a7b2-749267af3ac9','EX-447','b8ed9a31-9fa3-492f-904e-b8158a06d0da','j) não são utilizadas extensões elétricas (caso usadas numa necessidade eventual, verificar a capacidade de corrente e, dependendo da distância, a queda de tensão)','NORMAM-202/DPC','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('bc4bc5e4-a100-4aa5-a3f0-6f0d7405fb64','EX-386','a5f25230-91c9-4e14-aa33-e83524d5d943','Os esguichos não têm menos de 12 mm de diâmetro','NORMAM-202/DPC, Cap. 04, Seção III.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,0,1,0,0),('bd328ebf-7ae2-4e72-8d75-c1519b935d1b','EX-536','71c05e83-0d67-4137-b2b7-478c4241a057','A embarcação deverá ser marcada de modo visível e durável, com letras e algarismos de tamanho apropriado às dimensões da embarcação, com letras de, no mínimo, 10 cm, na popa, o nome da embarcação juntamente com o porto de inscrição e, na proa, o nome da embarcação nos dois bordos','NORMAM-202/DPC, Cap. 02, Seção II.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,1,1,1,1,1),('bd5d3265-5bb4-4d45-a4a3-592dbaeafc7b','EX-351','b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','Os aparelhos flutuantes estão estivados de modo a flutuarem livremente em caso de naufrágio','NORMAM-202/DPC','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('be414d13-fba6-478b-b244-8cae54e7532e','EX-513','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Verificar o estado físico de conservação, higiene e limpeza dos colchões fornecidos nos camarotes.','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,1,1,1,1,1),('bed32fa9-00cb-4821-a92a-f9d913ef261e','EX-425','b8ed9a31-9fa3-492f-904e-b8158a06d0da','As baterias deverão: b) ser mantidas devidamente fixadas e com seus bornes de ligação sem azinhavre e protegidos por material isolante','NORMAM-202/DPC, Cap. 03, Seção IV.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('c01f90ce-7dc7-494d-ac0d-631ac1833ac4','EX-391','65bf89f0-f44d-4746-89f7-f530c9aa990d','Quaisquer polias, correias e demais partes móveis utilizadas para acionamento de máquinas e ou mecanismos deverão ser dotadas de dispositivos adequados de proteção para as pessoas','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,1,1,1,1,1),('c0b150ff-dbbe-4b9e-9228-6e66a738b87b','EX-481','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Os camarotes destinados a mais de 4 pessoas em beliches possuem área mínima de 1,5 m² por pessoa','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:38',1,0,1,1,0,0),('c1d3a7cb-333e-4e09-96ef-098c409c7c6e','EX-546','71c05e83-0d67-4137-b2b7-478c4241a057','O material empregado na construção da embarcação está de acordo com aquele mencionado no Memorial Descritivo','NORMAM-202/DPC','seco',NULL,30,1,0,0,0,'2026-07-03 05:38:14','2026-07-04 04:12:38',1,1,1,1,1,1),('c1e33d68-30aa-4c63-8059-7c6f66ce4dad','EX-497','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','O sanitário coletivo mínimo é formado por uma unidade sanitária e lavatório, tendo área mínima de 1,26 m² e pode ser usado simultaneamente por mais de uma pessoa','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,0,1,1,0,0),('c231dec1-4488-4a8c-a9bc-3633e4f940c3','EX-523','71c05e83-0d67-4137-b2b7-478c4241a057','As janelas ou escotilhas, indicadas no Plano de Segurança como via de escape, possuem um vão livre mínimo não inferior a 600 x 600 mm, se instaladas em conveses e 600 x 800 mm, se instaladas em anteparas','NORMAM-202/DPC, Cap. 04, Item 4.2), 4.2.1, m, I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:14','2026-07-04 02:36:44',1,0,1,1,0,0),('c33725e8-227b-4dd2-9f32-e9e083b8d97c','EX-462','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Os corredores ou passarelas externas de circulação e acesso com até 10 m de comprimento apresentam largura mínima de 650 mm. Como o comprimento excede a 10 m, a largura mínima é acrescida de 50 mm para cada 2 m ou fração de comprimento, até no máximo de 800 mm','NORMAM-202/DPC, Cap. 03, Seção V.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,0,1,1,0,0),('c3c80149-529a-42c6-8a26-36c464054bca','EX-396','65bf89f0-f44d-4746-89f7-f530c9aa990d','Toda lâmpada deverá ser protegida contra choques, eficazmente, por luminárias','NORMAM-202/DPC','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('c85334c5-8f56-4ee3-be27-b6783951d5c3','EX-480','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Os camarotes para 3 ou 4 passageiros ou tripulantes possuem dimensões mínimas de 1,9 m x 3,0 m, contendo uma cama e um beliche duplo ou dois beliches duplos','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,0,1,1,0,0),('c8d265a4-62cc-4153-b226-337375cd363d','EX-526','71c05e83-0d67-4137-b2b7-478c4241a057','As alturas das luzes de navegação estão de acordo com as normas específicas sobre o assunto','RIPEAM 72 / NORMAM-202/DPC, Cap. 04.','flutuando',NULL,30,1,1,1,0,'2026-07-03 05:38:14','2026-09-12 15:21:09',1,1,1,1,1,1),('ca1c1aed-7e2a-4d54-92cd-7567486150c7','EX-375','a5f25230-91c9-4e14-aa33-e83524d5d943','Nas DEMAIS embarcações, as tomadas (hidrantes) deverão estar posicionadas de modo a propiciar, pelo menos, dois jatos d\'água não provenientes da mesma tomada de incêndio','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('ccaeea91-05ea-4864-a770-5c9b98ae8f48','EX-342','b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','Tamanho (apenas para os coletes salva vidas)','NORMAM-202/DPC, Cap. 04, Item 4.13.','flutuando',NULL,30,1,1,1,0,'2026-07-03 05:38:13','2026-09-12 15:21:09',1,1,1,1,1,1),('cd2dfb47-4f43-46b4-a27b-1e977ae0f5f2','EX-409','65bf89f0-f44d-4746-89f7-f530c9aa990d','Motores providos de sistema de abertura das válvulas de admissão e descarga, por intermédio de balancins, deverão ter seus tuchos de acionamento protegidos','NORMAM-202/DPC, Cap. 03, Seção III.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('ce1ba98a-6d1a-4140-a789-ca3efa885333','EX-402','65bf89f0-f44d-4746-89f7-f530c9aa990d','Os tanques de óleo situados no interior da Praça de Maquinas deverão ser dotados de suspiros independentes e cuja saída deverá estar localizada em área externa','NORMAM-202/DPC, Cap. 09, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('ce50512f-13f2-4b0e-a2f7-bc1ae1e5bffd','EX-340','b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','Número de série (se tiver) (Coletes salva-vidas)','NORMAM-202/DPC, Cap. 04, Item 4.13.','flutuando',NULL,30,1,1,1,0,'2026-07-03 05:38:13','2026-09-12 15:21:09',1,1,1,1,1,1),('cf097e63-f9a6-4408-ae6e-766baddc6322','EX-477','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Os espaços de cadeiras apresentam ventilação natural permanente para o exterior da embarcação, tendo como meio de fechamento sanefas ou janelas móveis. No caso de janela móvel, a área mínima de ventilação é de 40% do vão da abertura','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,0,1,1,0,0),('cf34c2da-207c-4d4c-a185-8c19374aaedf','EX-323','e70f7906-4e9d-4367-b10a-2ad2a007817a','Alarme visual e sonoro de alta temperatura da água de resfriamento do MCP e MCA com potência igual ou superior a 800 HP (597 kW)','NORMAM-202/DPC','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,0,0,1,0,0),('d11e0a27-5ba2-4d6f-9d9d-1415a92db143','EX-353','b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','Número do certificado de homologação pela DPC (Embarcações de Sobrevivência/Boias)','NORMAM-202/DPC, Cap. 04, Item 4.12.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('d171a5f8-0d0a-4279-9688-68856ea403e3','EX-505','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Os acessos às unidades sanitárias são efetuados através de vão mínimo de 1,8 x 0,55 m, dotados de portas com dispositivo de travamento interno e apresenta uma altura livre de, no máximo 0,3 m e, no mínimo 0,1 m, entre a porta e o piso','NORMAM-202/DPC','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,0,1,1,0,0),('d35a46ed-2908-4475-897d-fe955538be34','EX-453','b8ed9a31-9fa3-492f-904e-b8158a06d0da','Na instalação elétrica não existe fios soltos, desencapados ou qualquer outra condição que possa vir a provocar um curto-circuito','NORMAM-202/DPC','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('d3653240-9326-4f99-a41f-fccfd35e75b2','EX-341','b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','Data de fabricação (Coletes salva-vidas)','NORMAM-202/DPC, Cap. 04, Item 4.13.','flutuando',NULL,30,1,1,1,0,'2026-07-03 05:38:13','2026-09-12 15:21:09',1,1,1,1,1,1),('d6c54388-c992-4021-8a62-0a5400976539','EX-509','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Há pelo menos uma rampa, adequada às características da embarcação e ao local onde se efetua o embarque/desembarque de passageiros, para facilitar a entrada e saída dos passageiros','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,0,1,0,0,0),('d7a3466c-1c51-4001-a537-7f02912156a8','EX-406','65bf89f0-f44d-4746-89f7-f530c9aa990d','Toda fiação elétrica dos motores principais, auxiliares e equipamentos acessórios deverá ser protegida por eletrodutos ou acondicionada em “chicotes” apropriados','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('d970e4db-5964-4eaa-add3-dee2763eab6e','EX-313','aa4a7f0d-004d-4a60-924e-693335fdd69b','Tabelas ou quadros no comando: - sinais sonoros e luminosos','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,0,0,1,0,0),('da44538d-807e-40ef-9c99-0bb3c1f0c7a7','EX-532','71c05e83-0d67-4137-b2b7-478c4241a057','A antepara de colisão de ré está colocada de forma que limita o tubo telescópico em um espaço estanque à água de volume moderado','NORMAM-202/DPC, Cap. 03, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:14','2026-07-04 04:12:39',1,0,1,1,0,0),('da807bea-cb86-4be2-8655-97320c8fd059','EX-379','a5f25230-91c9-4e14-aa33-e83524d5d943','Não são usados para as redes de incêndio e para as tomadas de incêndio, materiais cujas características são prejudicadas pelo calor (como plásticos e PVC).','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('dab5c2ba-432e-47f3-a6ab-0a0e67b420a5','EX-315','aa4a7f0d-004d-4a60-924e-693335fdd69b','As embarcações que transportem passageiros deverão ter afixadas, em local visível aos passageiros, uma placa contendo o número de inscrição da embarcação, peso máximo de carga, número máximo de passageiros por convés que a embarcação está autorizada a transportar e número do telefone da OM em cuja jurisdição a embarcação estiver operando','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,0,0,1,0,0),('dbc42c9d-c0f2-44bc-ad57-b78a7b4e0ab3','EX-377','a5f25230-91c9-4e14-aa33-e83524d5d943','Nas DEMAIS embarcações, próximas à entrada da praça de máquinas (lado externo), deverão ser previstas uma tomada de incêndio e uma estação de incêndio com uma ou mais seções de mangueira e um aplicador de neblina','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('dbe76a3f-4454-4836-a600-1c3c99c06475','EX-458','f299c8c7-4402-4efa-89c6-d5add1fa60d5','A embarcação, que navega sob jurisdição da Capitania dos Portos de Barra Bonita, possui o equipamento AIS em pleno funcionamento','ANATEL / NORMAM','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-03 05:41:41',1,0,0,1,0,0),('e125df21-a446-4bef-9486-35a165b9220b','EX-326','e70f7906-4e9d-4367-b10a-2ad2a007817a','Agulha giroscópica ou magnética','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,0,0,1,0,0),('e1a77c79-63a6-4d5e-8906-64f06dee4a9a','EX-432','b8ed9a31-9fa3-492f-904e-b8158a06d0da','Quanto aos quadros elétricos: f) os quadros elétricos não estão localizados a vante da antepara de colisão','NORMAM-202/DPC','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('e202-0001-4921-b12a-000000000001','EX-553','71c05e83-0d67-4137-b2b7-478c4241a057','Marcação física da Linha de Borda Livre / Disco de Plimsoll soldada ou marcada em baixo relevo a meia-nau em ambos os bordos, com pintura contrastante de acordo com o Certificado Nacional de Borda Livre (CNBL)','NORMAM-202/DPC, Cap. 02, Seção II','borda_livre','borda_livre',15,1,1,1,0,'2026-09-12 15:21:21','2026-09-16 04:02:52',1,1,1,1,1,1),('e202-0002-4921-b12a-000000000002','EX-554','65bf89f0-f44d-4746-89f7-f530c9aa990d','Válvula de descarga direta de água de porão com lacre numerado ou dispositivo de interrupção bloqueado para impedir descarte involuntário de resíduos oleosos nos rios (Prevenção da Poluição Hídrica)','NORMAM-202/DPC, Cap. 08, Item 8.3','flutuando','flutuando',7,1,1,1,0,'2026-09-12 15:21:21','2026-09-12 15:21:21',1,1,1,1,1,1),('e202-0003-4921-b12a-000000000003','EX-555','e70f7906-4e9d-4367-b10a-2ad2a007817a','Painel de controle e alarme sonoro/visual de falha ou queima de lâmpadas das luzes de navegação no comando/passadiço','NORMAM-202/DPC, Cap. 04, Seção II','flutuando','flutuando',15,1,1,1,0,'2026-09-12 15:21:21','2026-09-12 15:21:21',1,1,1,1,1,1),('e202-0004-4921-b12a-000000000004','EX-556','b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','Placa informativa de capacidade máxima de passageiros (N1 e N2), tripulantes e limites de carga afixada em local visível ao público e passageiros','NORMAM-202/DPC, Cap. 07, Seção I','flutuando','flutuando',10,1,1,1,0,'2026-09-12 15:21:21','2026-09-12 15:21:21',1,1,1,1,1,1),('e204d705-f37b-46c6-88b6-5d46f506064b','EX-543','71c05e83-0d67-4137-b2b7-478c4241a057','Verificar se os acessos aos locais abaixo relacionados estão livres: Porões de carga','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:14','2026-07-04 04:12:39',1,1,1,1,1,1),('e26e80f5-8422-4fb7-8199-6669ac222815','EX-308','aa4a7f0d-004d-4a60-924e-693335fdd69b','Certificado de conformidade para transporte de gases liquefeitos a granel (se aplicável)','NORMAM-202/DPC','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('e27dc4c7-dd3b-4269-bc57-601cbb159450','EX-354','b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','Fabricante (Embarcações de Sobrevivência/Boias)','NORMAM-202/DPC, Cap. 04, Item 4.12.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('e2dc9cdc-437a-4c3a-8710-ce6bb9d4c3f6','EX-411','65bf89f0-f44d-4746-89f7-f530c9aa990d','Qualquer sistema de monitoramento e ou controle de equipamentos instalado no passadiço deverá ser dotado de placas identificadoras, assim como provido de uma iluminação apropriada','NORMAM-202/DPC, Cap. 03, Seção IV.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('e402c282-bbf7-4213-b997-761e8e06227a','EX-311','aa4a7f0d-004d-4a60-924e-693335fdd69b','Tabelas ou quadros no comando: - sinais de salvamento','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,0,0,1,0,0),('e4382149-9351-4ffe-8e6c-004723fdb8a0','EX-448','b8ed9a31-9fa3-492f-904e-b8158a06d0da','k) os acessórios de iluminação são instalados de maneira tal que evitam aumentos de temperatura que possam danificar cabos e fiação e impeçam que o material situado nos arredores se torne excessivamente quente','NORMAM-202/DPC, Cap. 03, Seção IV.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('e4c70296-da8c-4f2d-a1e5-a20287dddb1c','EX-433','b8ed9a31-9fa3-492f-904e-b8158a06d0da','Quanto aos quadros elétricos: g) estão limpos e mantidos','NORMAM-202/DPC','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('e4db742c-931a-43ef-bff3-287ef5d42c1f','EX-521','71c05e83-0d67-4137-b2b7-478c4241a057','Acima do convés aberto mais baixo, as vias de escape são escadas, portas ou janelas ou uma combinação delas, dando para um convés aberto','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:14','2026-07-04 04:12:39',1,0,1,1,0,0),('e556f7ad-a680-44ce-861d-f051aac27a86','EX-417','b8ed9a31-9fa3-492f-904e-b8158a06d0da','A fonte de energia principal tem capacidade suficiente para suprir a carga necessária para manter a embarcação em plenas condições de operação e habitabilidade, levando-se em consideração os fatores de potência, de demanda e a simultaneidade das cargas','NORMAM-202/DPC, Cap. 03, Seção IV.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('e55e3316-1841-41f7-8eca-de405ef9e180','EX-388','a5f25230-91c9-4e14-aa33-e83524d5d943','Somente deverão ser utilizadas redes de aço e acessórios de materiais resistentes ao fogo junto ao casco, nos embornais, nas descargas sanitárias e em outras descargas situadas abaixo do convés estanque.','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('e64d7ec0-fccc-4d7b-91f0-043098347422','EX-307','aa4a7f0d-004d-4a60-924e-693335fdd69b','Certificado de Borda Livre, quando aplicável','NORMAM-202/DPC, Cap. 05, Item 5.1.','flutuando',NULL,30,1,1,1,0,'2026-07-03 05:38:13','2026-09-12 15:21:09',1,1,1,1,1,1),('e70fad1d-6ee7-4ceb-9c23-d101f192e2a3','EX-363','a5f25230-91c9-4e14-aa33-e83524d5d943','Nenhum tanque ou rede de combustível está posicionado em local onde qualquer derramamento ou vazamento dele proveniente, venha constituir risco de incêndio pelo contato com superfícies aquecidas ou equipamentos elétricos','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('e8afc2e7-7783-4ea7-9e95-fccf3e8499dd','EX-415','65bf89f0-f44d-4746-89f7-f530c9aa990d','Verificar se os empurradores possuem placa física identificadora com o número do motor ou, se inexistente, exigir Nota Fiscal ou Recibo de Compra e Venda.','NORMAM-202/DPC, Cap. 03, Seção III.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('e9226bc3-3b12-417e-946f-18c0176792e0','EX-324','e70f7906-4e9d-4367-b10a-2ad2a007817a','Sistema de comunicação que possibilita ao comando divulgar informações gerais por intermédio de alto-falantes nos locais destinados aos passageiros (para embarcações com mais de 100 passageiros)','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,0,0,1,0,0),('eae082a5-c90e-4a46-8922-aadbe8cdeea0','EX-471','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','As portas de acesso estão posicionadas de forma que uma pessoa não necessita se deslocar mais de 13 m em linha reta, a partir de qualquer posição do espaço de cadeiras, para alcançar uma das portas','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,0,1,1,0,0),('eb283686-11d5-4d21-aa6a-46fa76015422','EX-469','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Todos os corredores têm livre acesso às saídas do compartimento','NORMAM-202/DPC, Cap. 03, Seção V.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,0,1,1,0,0),('eba785cf-5373-49b1-9f45-74624533cd4e','EX-495','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','As unidades de banheiro têm área maior ou igual a 1,3 m², sendo que as medidas do boxe são de 0,7 x 0,7 m ou maiores. A largura da unidade de banheiro é maior ou igual a 0,8 m','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,0,1,1,0,0),('ec47b315-cde2-4d25-955b-8ef469a3db99','EX-457','f299c8c7-4402-4efa-89c6-d5add1fa60d5','A licença-rádio deverá ser mantida a bordo da embarcação.','ANATEL / NORMAM','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-03 05:41:41',1,0,0,1,0,0),('ec652099-4966-4fea-94f7-0c41adde6ccb','EX-306','aa4a7f0d-004d-4a60-924e-693335fdd69b','Certificado ou notas de arqueação','NORMAM-202/DPC, Cap. 06, Item 6.1.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('ecf0c6d1-02a0-479f-9b92-982e68083700','EX-430','b8ed9a31-9fa3-492f-904e-b8158a06d0da','Quanto aos quadros elétricos: d) se a fonte de emergência de energia for constituída por bateria de acumuladores, ela não está instalada no mesmo compartimento do quadro elétrico de emergência','NORMAM-202/DPC, Cap. 03, Seção IV.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('ecf9e38b-e522-425b-9daa-e0323352bab8','EX-522','71c05e83-0d67-4137-b2b7-478c4241a057','Não há corredores sem saída com mais de 7 m de comprimento (um corredor sem saída é um corredor ou parte de um corredor a partir do qual só há uma via de escape)','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:14','2026-07-04 04:12:39',1,0,1,1,0,0),('ee4ccc12-4cbd-45d3-a239-fd8d70eb6e7b','EX-310','aa4a7f0d-004d-4a60-924e-693335fdd69b','Tabelas ou quadros no comando: - regras de governo e navegação','NORMAM-202/DPC','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,0,0,1,0,0),('eed4571e-88f9-4f4a-833b-bc4cfbb5dc2a','EX-304','aa4a7f0d-004d-4a60-924e-693335fdd69b','Caderneta de Inscrição e Registro de cada tripulante (CIR)','NORMAM-202/DPC','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('ef865d12-3b6a-4d96-b9e0-a32b12b89725','EX-455','f299c8c7-4402-4efa-89c6-d5add1fa60d5','Os equipamentos de radiocomunicação funcionam e podem operar na freqüência de 156,8 Mhz (canal 16)','NORMAM-202/DPC, Cap. 04, Item 4.8), 4.8.1.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 02:36:44',1,0,0,1,0,0),('efb0d9fe-b5be-4c6d-817d-edd230a5c0a9','EX-336','b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','Número do certificado de homologação pela DPC (Coletes salva-vidas)','NORMAM-202/DPC, Cap. 04, Item 4.13.','flutuando',NULL,30,1,1,1,0,'2026-07-03 05:38:13','2026-09-12 15:21:09',1,1,1,1,1,1),('f10786b6-5cfd-4656-8789-db333c13166f','EX-346','b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d','Os coletes salva vidas estão estivados de maneira a serem prontamente utilizados, em local visível, bem sinalizado e de fácil acesso','NORMAM-202/DPC, Cap. 04, Item 4.13.','flutuando',NULL,30,1,1,1,0,'2026-07-03 05:38:13','2026-09-12 15:21:09',1,1,1,1,1,1),('f1305470-ca00-414f-9f1b-8082fc6cb2a6','EX-493','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Os compartimentos sanitários são dotados de meios de drenagem no ponto mais baixo do piso. As unidades de chuveiro possuem dreno específico','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,0,1,1,0,0),('f199c93c-ce4a-424f-8ea6-da60372de2e4','EX-524','71c05e83-0d67-4137-b2b7-478c4241a057','As rotas de escape estão marcadas por setas indicadoras, pintadas em cor contrastante, indicando \'Saída de Emergência\'. A marcação permite, aos passageiros e tripulantes, a identificação de todas as rotas de evacuação e a rápida identificação das saídas','NORMAM-202/DPC, Cap. 03, Seção II.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:14','2026-07-04 04:12:39',1,0,1,1,0,0),('f1abbac0-6684-47e0-b67e-0c850ad377ae','EX-549','71c05e83-0d67-4137-b2b7-478c4241a057','O casco e os conveses estão em condições satisfatórias, sem deterioração acentuada, não apresentando mossas, trincas ou furos por corrosão','NORMAM-202/DPC','seco',NULL,30,1,0,0,0,'2026-07-03 05:38:14','2026-07-04 04:12:39',1,1,1,1,1,1),('f3fa1e72-5aa5-46d3-bde1-caa01704b771','EX-440','b8ed9a31-9fa3-492f-904e-b8158a06d0da','c) os eletrodutos estão instalados com suficiente caimento e furos para dar drenagem e evitar o acúmulo d’água','NORMAM-202/DPC','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('f42be128-51c4-4240-bd88-d0031f30b2e3','EX-468','9755fe45-1e6f-4fa7-b589-942d8a6f07d2','Os corredores internos dos salões de cadeiras têm largura mínima de 800mm para um comprimento máximo equivalente a 20 filas de cadeiras consecutivas. Para um comprimento superior, a largura mínima é acrescida de 100 mm para cada 10 filas ou fração de cadeiras a mais','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,0,1,1,0,0),('f5a3cf01-94bc-4944-a3c1-4db1811db59b','EX-399','65bf89f0-f44d-4746-89f7-f530c9aa990d','Não deverá haver vazamentos ou descargas de gases provenientes da queima de combustão no interior dos espaços de máquinas ou outros compartimentos quaisquer.','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('f6b03730-2355-4d50-82d9-573150d8ec4f','EX-442','b8ed9a31-9fa3-492f-904e-b8158a06d0da','e) as extremidades e junções de todos os condutores são feitas de modo a serem conservadas as propriedades originais elétricas e mecânicas','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('f6b5c4dc-45a7-4eb8-b2f0-92e2f01171a2','EX-530','71c05e83-0d67-4137-b2b7-478c4241a057','O ponto de alagamento progressivo (qualquer acesso ao casco não estanque ao tempo) está localizado exatamente no local informado no projeto – geralmente no Estudo de Estabilidade ou nas Curvas','NORMAM-202/DPC, Cap. 03, Seção I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:14','2026-07-04 04:12:39',1,1,1,1,1,1),('f91ac072-d60c-4502-8590-472181dc8a53','EX-378','a5f25230-91c9-4e14-aa33-e83524d5d943','As mangueiras e seus acessórios ficam acondicionados em cabides ou estações de incêndio (armário pintado de vermelho, dotado em sua antepara frontal de uma porta)','NORMAM-202/DPC','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('f95612f7-d307-4cdf-8a02-41124b7bf5e2','EX-305','aa4a7f0d-004d-4a60-924e-693335fdd69b','Regras para evitar abalroamento – RIPEAM (exceto para embarcações sem propulsão quando rebocadas/empurradas)','RIPEAM 72 / NORMAM-202/DPC, Cap. 04.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,0,0,1,0,1),('fa01a553-9f0b-4eb4-a2fa-fe53004c7e78','EX-434','b8ed9a31-9fa3-492f-904e-b8158a06d0da','Os circuitos de distribuição, geradores e alimentadores são individualmente protegidos por disjuntores ou fusíveis contra sobrecarga e curto-circuito','NORMAM-202/DPC, Cap. 03, Seção IV.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('fa3a530e-d204-4571-b0ef-3902a2ff8f50','EX-383','a5f25230-91c9-4e14-aa33-e83524d5d943','O diâmetro das mangueiras de incêndio não é inferior a 38 mm (1,5\'\')','NORMAM-202/DPC','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,0,0,1,0,0),('fd836b06-765d-4b56-a022-699234aab52b','EX-435','b8ed9a31-9fa3-492f-904e-b8158a06d0da','Os transformadores são protegidos com disjuntores no primário','NORMAM-202/DPC, Cap. 03, Seção IV.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('fd9cb55e-6e74-4f21-b89a-3c77685d0862','EX-370','a5f25230-91c9-4e14-aa33-e83524d5d943','As embarcações propulsadas empregadas no transporte de passageiros com AB maior que 10 e as demais embarcações propulsadas com AB maior que 20 deverão ser dotadas de pelo menos uma bomba de esgoto com vazão total maior ou igual a 15 m³/h','NORMAM-202/DPC, Cap. 04, Seção I.','flutuando',NULL,30,1,1,1,0,'2026-07-03 05:38:13','2026-09-12 15:21:09',1,0,0,1,0,1),('fee925e7-19cc-4f27-839e-d320076cd13f','EX-421','b8ed9a31-9fa3-492f-904e-b8158a06d0da','A fonte de energia elétrica de emergência é independente da fonte principal e com capacidade de alimentar por uma hora todos os sistemas elétricos e consumidores necessários à segurança de passageiros e tripulação','NORMAM-202/DPC, Cap. 03, Seção IV.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 04:12:39',1,1,1,1,1,1),('ff928f0e-e467-4d37-b188-fe991b28568e','EX-300','aa4a7f0d-004d-4a60-924e-693335fdd69b','Plano de Segurança','NORMAM-202/DPC, Cap. 04, Item 4.2), 4.2.1, m, I.','flutuando',NULL,30,1,0,0,0,'2026-07-03 05:38:13','2026-07-04 02:36:44',1,0,0,1,0,0);
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
INSERT INTO `feedback_regras_comunicacao` (`id`, `cargo_origem`, `escopo`, `cargo_destino`, `ativo`, `criado_em`, `atualizado_em`) VALUES ('00783402-c1db-4a8b-a262-82a9a259e6f4','VENDEDOR','OUTROS_GESTORES',NULL,1,'2026-09-10 16:43:05','2026-09-10 16:43:05'),('0380f7ff-4a70-4710-831e-586da817669c','ANALISTA','TODOS_USUARIOS',NULL,1,'2026-09-10 16:43:05','2026-09-10 16:43:05'),('087b76d6-11b8-482f-a824-a09de3564f55','VISTORIADOR','CARGO','ANALISTA',1,'2026-09-10 16:43:05','2026-09-10 16:43:05'),('1551d2b6-811e-4e75-b4b3-b4fc5e746252','ANALISTA','CARGO','ANALISTA',1,'2026-09-10 16:43:05','2026-09-10 16:43:05'),('16e38d87-6f7a-4a5a-96bf-5856a91b8e44','VISTORIADOR','CARGO','VENDEDOR',1,'2026-09-10 16:43:05','2026-09-10 16:43:05'),('2e1c43e8-a1df-444d-b799-aa9ac8348fc8','VENDEDOR','CARGO','VENDEDOR',1,'2026-09-10 16:43:05','2026-09-10 16:43:05'),('40bc6670-4d33-4f60-ab59-d853a50f39c8','VISTORIADOR','OUTROS_GESTORES',NULL,1,'2026-09-10 16:43:05','2026-09-10 16:43:05'),('4b8155d7-ee33-4c44-9377-9ca83b102c30','ANALISTA','CARGO','VENDEDOR',1,'2026-09-10 16:43:05','2026-09-10 16:43:05'),('4e6da932-75f0-4d60-89cf-9622f92b1213','VISTORIADOR','GESTOR_DIRETO',NULL,1,'2026-09-10 16:43:05','2026-09-10 16:43:05'),('528a68fb-9ee9-430e-bc0d-7e65b4accac7','VISTORIADOR','SUBORDINADOS',NULL,1,'2026-09-10 16:43:05','2026-09-10 16:43:05'),('52c04c40-bbe8-4cd0-8464-c7f4c681603d','VENDEDOR','ADMIN',NULL,1,'2026-09-10 16:43:05','2026-09-10 16:43:05'),('535807cb-2590-4690-93d5-99449bc564ee','VENDEDOR','CARGO','ANALISTA',1,'2026-09-10 16:43:05','2026-09-10 16:43:05'),('623ce55a-4f9c-4752-b667-515ba70d0b33','ANALISTA','OUTROS_GESTORES',NULL,1,'2026-09-10 16:43:05','2026-09-10 16:43:05'),('6a3de091-4a2f-41b7-b2b4-7efa7676fac9','ANALISTA','CARGO','VISTORIADOR',1,'2026-09-10 16:43:05','2026-09-10 16:43:05'),('6efe9d5e-1ac9-4d03-bbe0-5b77a3b51437','ANALISTA','GESTOR_DIRETO',NULL,1,'2026-09-10 16:43:05','2026-09-10 16:43:05'),('84b9b174-f1f9-4efe-9732-c61be21ec9ff','VENDEDOR','CARGO','VISTORIADOR',1,'2026-09-10 16:43:05','2026-09-10 16:43:05'),('97efffdb-85f2-4079-9aac-5d081895bbed','VENDEDOR','GESTOR_DIRETO',NULL,1,'2026-09-10 16:43:05','2026-09-10 16:43:05'),('9e15314a-cc9a-4c17-873f-e8b191b6bab0','ANALISTA','SUBORDINADOS',NULL,1,'2026-09-10 16:43:05','2026-09-10 16:43:05'),('b4432c96-45f3-4b54-8221-e87e980a3602','VENDEDOR','TODOS_USUARIOS',NULL,1,'2026-09-10 16:43:05','2026-09-10 16:43:05'),('b7f35823-0ecb-4861-9940-1f6065bba78a','VISTORIADOR','ADMIN',NULL,1,'2026-09-10 16:43:05','2026-09-10 16:43:05'),('d4292c19-e707-4ec7-b79b-47df91a68a80','VISTORIADOR','CARGO','VISTORIADOR',1,'2026-09-10 16:43:05','2026-09-10 16:43:05'),('d62d58fd-fc1e-4ce3-ada9-74502e35649e','VISTORIADOR','TODOS_USUARIOS',NULL,1,'2026-09-10 16:43:05','2026-09-10 16:43:05'),('d72def95-03c7-4b3c-80c1-467f7106a633','ANALISTA','ADMIN',NULL,1,'2026-09-10 16:43:05','2026-09-10 16:43:05'),('df39b30e-d69e-4bca-8b00-5696b67d8a85','VENDEDOR','SUBORDINADOS',NULL,1,'2026-09-10 16:43:05','2026-09-10 16:43:05');
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
INSERT INTO `financeiro_historico_baixas` VALUES ('306b5d04-b570-4b23-bfcc-2025b03af39c','eccd058b-aeda-11f1-8a7c-be2fb1f77be2',1000.00,'2026-09-15','a_vista',NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 01:00:42');
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
  KEY `idx_fin_ativo_tipo_status_data` (`ativo`,`tipo`,`status`,`data`,`valor`),
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
INSERT INTO `financeiro_lancamentos` VALUES ('04f4201b-aec9-11f1-8a7c-be2fb1f77be2','1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed','342323aa-142c-447b-b392-7421e538f041',NULL,'029f1f9e-aec9-11f1-8a7c-be2fb1f77be2','RECEITA','Referente à Proposta Comercial nº AM-ORC-2/26',18100.00,18100.00,18100.00,'PENDENTE','unica','2026-09-27','2026-09-12','SERVIÇOS','Lançamento gerado automaticamente após aprovação interna da proposta.',1,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-12 16:43:01','2026-09-12 16:43:01'),('25bd0284-ad55-11f1-8a7c-be2fb1f77be2','1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed','342323aa-142c-447b-b392-7421e538f041',NULL,'23f7a7d7-ad55-11f1-8a7c-be2fb1f77be2','RECEITA','Referente à Proposta Comercial nº AM-ORC-1/26',9800.00,9800.00,9800.00,'PENDENTE','unica','2026-09-25','2026-09-10','SERVIÇOS','Lançamento gerado automaticamente após aprovação interna da proposta.',1,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-10 20:21:04','2026-09-10 20:21:04'),('eccd058b-aeda-11f1-8a7c-be2fb1f77be2','1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed','342323aa-142c-447b-b392-7421e538f041',NULL,'eb29b70e-aeda-11f1-8a7c-be2fb1f77be2','RECEITA','Referente à Proposta Comercial nº AM-ORC-3/26',9800.00,9800.00,8800.00,'PARCIAL','unica','2026-09-27','2026-09-15','SERVIÇOS','Lançamento gerado automaticamente após aprovação interna da proposta.',1,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-12 18:51:12','2026-09-16 01:00:42');
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
/*!40000 ALTER TABLE `financeiro_metas_mensais` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_tentativas`
--

DROP TABLE IF EXISTS `login_tentativas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_tentativas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ip` varchar(45) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `sucesso` tinyint(1) NOT NULL DEFAULT '0',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_login_ip_tempo` (`ip`,`criado_em`),
  KEY `idx_login_email_tempo` (`email`,`criado_em`)
) ENGINE=InnoDB AUTO_INCREMENT=251 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_tentativas`
--

LOCK TABLES `login_tentativas` WRITE;
/*!40000 ALTER TABLE `login_tentativas` DISABLE KEYS */;
/*!40000 ALTER TABLE `login_tentativas` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=238 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `logs_atividade`
--

LOCK TABLES `logs_atividade` WRITE;
/*!40000 ALTER TABLE `logs_atividade` DISABLE KEYS */;
INSERT INTO `logs_atividade` VALUES (1,'dd121661-feb4-42f6-895a-68eb0608d1e4','proprietario_criado','Proprietário \'Rosano Souza\' criado.','172.23.0.1','2026-09-10 17:19:42'),(2,'dd121661-feb4-42f6-895a-68eb0608d1e4','proposta_criada','Proposta AM-ORC-1/26 criada para cliente \'Rosano Souza\'. Subtotal: R$ 9.800,00 | Desconto: 0% | Entrada: R$ 0,00 | Total: R$ 9.800,00','172.23.0.1','2026-09-10 17:21:01'),(3,'dd121661-feb4-42f6-895a-68eb0608d1e4','proposta_aprovada_assinatura_manual','Proposta AM-ORC-1/26 autorizada internamente sem assinatura digital por Autorização interna - admin.','172.23.0.1','2026-09-10 17:21:04'),(4,'dd121661-feb4-42f6-895a-68eb0608d1e4','agendamento_editado','Agendamento ID: 25be9af2-ad55-11f1-8a7c-be2fb1f77be2 atualizado.','172.23.0.1','2026-09-10 17:21:15'),(5,'d2a16613-dfa4-4948-8de4-8c802abdf394','relatorio_salvo','Relatorio tecnico AM-REL-V-1/26 salvo para agendamento ID: 25be9af2-ad55-11f1-8a7c-be2fb1f77be2. Status: AGUARDANDO_APROVACAO.','172.23.0.1','2026-09-10 17:22:22'),(6,'dd121661-feb4-42f6-895a-68eb0608d1e4','relatorio_decisao_admin','Relatorio ID c3670566-76b2-4376-88b4-234c09701f37 definido como RETORNO_AS.','172.23.0.1','2026-09-10 17:22:36'),(7,'dd121661-feb4-42f6-895a-68eb0608d1e4','agendamento_criado','Agendamento de Cumprimento de A/S criado para data 2026-09-24.','172.23.0.1','2026-09-10 17:22:54'),(8,'d2a16613-dfa4-4948-8de4-8c802abdf394','relatorio_cumprimento_salvo','Relatorio AM-REL-V-2/26 salvo com status AGUARDANDO_APROVACAO. Decisoes: 6d011d39-ad55-11f1-8a7c-be2fb1f77be2:cumprida_parcial_reescrita, 6d012063-ad55-11f1-8a7c-be2fb1f77be2:cumprida, 6d0120c3-ad55-11f1-8a7c-be2fb1f77be2:cumprida','172.23.0.1','2026-09-10 17:23:21'),(9,'dd121661-feb4-42f6-895a-68eb0608d1e4','relatorio_decisao_admin','Relatorio ID c0cc81fc-b11d-40a6-b8f8-f410d3c1982a definido como APROVADA_COM_EXIGENCIAS.','172.23.0.1','2026-09-10 17:23:34'),(10,'dd121661-feb4-42f6-895a-68eb0608d1e4','relatorio_assinatura_substituta','Relatorio ID c0cc81fc-b11d-40a6-b8f8-f410d3c1982a; responsavel tecnico d2a16613-dfa4-4948-8de4-8c802abdf394; executor dd121661-feb4-42f6-895a-68eb0608d1e4.','172.23.0.1','2026-09-10 17:24:27'),(11,'dd121661-feb4-42f6-895a-68eb0608d1e4','certificado_csn_criado','Certificado AM-CSN-1/26 (Provisório) - barcoteste14','172.23.0.1','2026-09-10 17:25:10'),(12,'dd121661-feb4-42f6-895a-68eb0608d1e4','documento_aprovado_eletronicamente','Certificado CSN faa23877-c468-4114-9182-3b6157402f0f aprovado. Hash final: e960cc7748271fe92db5fdfa5e52083e89ffc8b5367b4be060646102fbe78ec6','172.23.0.1','2026-09-10 17:25:33'),(13,'dd121661-feb4-42f6-895a-68eb0608d1e4','proposta_criada','Proposta AM-ORC-2/26 criada para cliente \'Rosano Souza\'. Subtotal: R$ 18.100,00 | Desconto: 0% | Entrada: R$ 0,00 | Total: R$ 18.100,00','172.23.0.1','2026-09-12 13:42:58'),(14,'dd121661-feb4-42f6-895a-68eb0608d1e4','proposta_aprovada_assinatura_manual','Proposta AM-ORC-2/26 autorizada internamente sem assinatura digital por Autorização interna - admin.','172.23.0.1','2026-09-12 13:43:02'),(15,'dd121661-feb4-42f6-895a-68eb0608d1e4','agendamento_editado','Agendamento ID: 04f4aedd-aec9-11f1-8a7c-be2fb1f77be2 atualizado.','172.23.0.1','2026-09-12 13:43:14'),(16,'d2a16613-dfa4-4948-8de4-8c802abdf394','relatorio_salvo','Relatorio tecnico AM-REL-V-3/26 salvo para agendamento ID: 04f4aedd-aec9-11f1-8a7c-be2fb1f77be2. Status: PENDENTE.','172.23.0.1','2026-09-12 13:43:48'),(17,'dd121661-feb4-42f6-895a-68eb0608d1e4','proposta_criada','Proposta AM-ORC-3/26 criada para cliente \'Rosano Souza\'. Subtotal: R$ 9.800,00 | Desconto: 0% | Entrada: R$ 0,00 | Total: R$ 9.800,00','172.23.0.1','2026-09-12 15:51:09'),(18,'dd121661-feb4-42f6-895a-68eb0608d1e4','proposta_aprovada_assinatura_manual','Proposta AM-ORC-3/26 autorizada internamente sem assinatura digital por Autorização interna - admin.','172.23.0.1','2026-09-12 15:51:12'),(19,'dd121661-feb4-42f6-895a-68eb0608d1e4','agendamento_editado','Agendamento ID: eccd8fc8-aeda-11f1-8a7c-be2fb1f77be2 atualizado.','172.23.0.1','2026-09-12 15:51:31'),(62,'dd121661-feb4-42f6-895a-68eb0608d1e4','certificado_lc_criado','Licença de Construção AM-LC-1/26 - B/M AMAZON EXPLORER VI','0.0.0.0','2026-09-15 19:44:12'),(63,'dd121661-feb4-42f6-895a-68eb0608d1e4','certificado_lc_criado','Licença de Construção AM-LC-2/26 - B/M AMAZON EXPLORER VI','0.0.0.0','2026-09-15 19:44:18'),(64,'dd121661-feb4-42f6-895a-68eb0608d1e4','certificado_lc_criado','Licença de Construção AM-LC-3/26 - B/M AMAZON EXPLORER VI','0.0.0.0','2026-09-15 19:44:44'),(65,'dd121661-feb4-42f6-895a-68eb0608d1e4','certificado_lc_criado','Licença de Construção AM-LC-4/26 - B/M AMAZON EXPLORER VI','0.0.0.0','2026-09-15 19:46:20'),(72,'dd121661-feb4-42f6-895a-68eb0608d1e4','certificado_lc_criado','Licença de Construção AM-LC-5/26 - B/M AMAZON EXPLORER VI','0.0.0.0','2026-09-15 19:46:50'),(79,'dd121661-feb4-42f6-895a-68eb0608d1e4','certificado_lc_criado','Licença de Construção AM-LC-6/26 - B/M AMAZON EXPLORER VI','0.0.0.0','2026-09-15 20:07:00'),(86,'dd121661-feb4-42f6-895a-68eb0608d1e4','certificado_lc_criado','Licença de Construção AM-LC-7/26 - B/M AMAZON EXPLORER VI','0.0.0.0','2026-09-15 20:08:04'),(93,'dd121661-feb4-42f6-895a-68eb0608d1e4','certificado_lc_criado','Licença de Construção AM-LC-8/26 - B/M AMAZON EXPLORER VI','0.0.0.0','2026-09-15 21:19:00'),(100,'dd121661-feb4-42f6-895a-68eb0608d1e4','certificado_lc_criado','Licença de Construção AM-LC-9/26 - B/M AMAZON EXPLORER VI','0.0.0.0','2026-09-15 21:23:51'),(101,'dd121661-feb4-42f6-895a-68eb0608d1e4','certificado_csn_criado','Certificado AM-CSN-2/26 (Definitivo) - B/M SOLIMÕES EXPRESS E2E','0.0.0.0','2026-09-15 21:28:10'),(102,'dd121661-feb4-42f6-895a-68eb0608d1e4','certificado_csn_criado','Certificado AM-CSN-2/26 (Definitivo) - B/M SOLIMÕES EXPRESS E2E','0.0.0.0','2026-09-15 21:30:00'),(103,'dd121661-feb4-42f6-895a-68eb0608d1e4','certificado_csn_criado','Certificado AM-CSN-2/26 (Definitivo) - B/M SOLIMÕES EXPRESS E2E','0.0.0.0','2026-09-15 21:30:43'),(104,'dd121661-feb4-42f6-895a-68eb0608d1e4','certificado_csn_criado','Certificado AM-CSN-2/26 (Definitivo) - B/M SOLIMÕES EXPRESS E2E','0.0.0.0','2026-09-15 21:31:06'),(105,'dd121661-feb4-42f6-895a-68eb0608d1e4','certificado_csn_criado','Certificado AM-CSN-2/26 (Definitivo) - B/M SOLIMÕES EXPRESS E2E','0.0.0.0','2026-09-15 21:31:27'),(106,'dd121661-feb4-42f6-895a-68eb0608d1e4','certificado_csn_criado','Certificado AM-CSN-2/26 (Definitivo) - B/M SOLIMÕES EXPRESS E2E','0.0.0.0','2026-09-15 21:31:56'),(107,'dd121661-feb4-42f6-895a-68eb0608d1e4','certificado_csn_criado','Certificado AM-CSN-2/26 (Definitivo) - B/M SOLIMÕES EXPRESS E2E','0.0.0.0','2026-09-15 21:32:21'),(114,'dd121661-feb4-42f6-895a-68eb0608d1e4','certificado_lc_criado','Licença de Construção AM-LC-10/26 - B/M AMAZON EXPLORER VI','0.0.0.0','2026-09-15 21:32:44'),(115,'dd121661-feb4-42f6-895a-68eb0608d1e4','certificado_csn_criado','Certificado AM-CSN-2/26 (Definitivo) - B/M SOLIMÕES EXPRESS E2E','0.0.0.0','2026-09-15 21:32:48'),(116,'dd121661-feb4-42f6-895a-68eb0608d1e4','certificado_csn_criado','Certificado AM-CSN-2/26 (Definitivo) - B/M SOLIMÕES EXPRESS E2E','0.0.0.0','2026-09-15 21:32:50'),(123,'dd121661-feb4-42f6-895a-68eb0608d1e4','certificado_lc_criado','Licença de Construção AM-LC-11/26 - B/M AMAZON EXPLORER VI','0.0.0.0','2026-09-15 21:33:38'),(124,'dd121661-feb4-42f6-895a-68eb0608d1e4','certificado_csn_criado','Certificado AM-CSN-2/26 (Definitivo) - B/M SOLIMÕES EXPRESS E2E','0.0.0.0','2026-09-15 21:33:56'),(125,'dd121661-feb4-42f6-895a-68eb0608d1e4','certificado_csn_criado','Certificado AM-CSN-2/26 (Definitivo) - B/M SOLIMÕES EXPRESS E2E','0.0.0.0','2026-09-15 21:34:25'),(126,'dd121661-feb4-42f6-895a-68eb0608d1e4','certificado_csn_criado','Certificado AM-CSN-2/26 (Definitivo) - B/M SOLIMÕES EXPRESS E2E','0.0.0.0','2026-09-15 21:34:28'),(133,'dd121661-feb4-42f6-895a-68eb0608d1e4','certificado_lc_criado','Licença de Construção AM-LC-12/26 - B/M AMAZON EXPLORER VI','0.0.0.0','2026-09-15 21:34:50'),(134,'dd121661-feb4-42f6-895a-68eb0608d1e4','certificado_csn_criado','Certificado AM-CSN-2/26 (Definitivo) - B/M SOLIMÕES EXPRESS E2E','0.0.0.0','2026-09-15 21:34:54'),(135,'dd121661-feb4-42f6-895a-68eb0608d1e4','certificado_lc_criado','Licença de Construção AM-LC-13/26 - B/M AMAZON EXPLORER VI','0.0.0.0','2026-09-15 21:35:34'),(142,'dd121661-feb4-42f6-895a-68eb0608d1e4','certificado_lc_criado','Licença de Construção AM-LC-14/26 - B/M AMAZON EXPLORER VI','0.0.0.0','2026-09-15 21:36:07'),(143,'dd121661-feb4-42f6-895a-68eb0608d1e4','certificado_csn_criado','Certificado AM-CSN-2/26 (Definitivo) - B/M SOLIMÕES EXPRESS E2E','0.0.0.0','2026-09-15 21:36:12'),(150,'dd121661-feb4-42f6-895a-68eb0608d1e4','certificado_lc_criado','Licença de Construção AM-LC-15/26 - B/M AMAZON EXPLORER VI','0.0.0.0','2026-09-15 21:36:28'),(151,'dd121661-feb4-42f6-895a-68eb0608d1e4','certificado_csn_criado','Certificado AM-CSN-2/26 (Definitivo) - B/M SOLIMÕES EXPRESS E2E','0.0.0.0','2026-09-15 21:36:32'),(152,'dd121661-feb4-42f6-895a-68eb0608d1e4','cliente_criado','Cliente/Ator Naval \'Armador Solimões Web Test 4081\' (armador) criado.','127.0.0.1','2026-09-15 21:40:53'),(153,'dd121661-feb4-42f6-895a-68eb0608d1e4','cliente_criado','Cliente/Ator Naval \'Armador Solimões Web Test 4663\' (armador) criado.','127.0.0.1','2026-09-15 21:41:32'),(154,'dd121661-feb4-42f6-895a-68eb0608d1e4','cliente_criado','Cliente/Ator Naval \'Armador Solimões Web Test 9998\' (armador) criado.','127.0.0.1','2026-09-15 21:42:50'),(155,'dd121661-feb4-42f6-895a-68eb0608d1e4','cliente_criado','Cliente/Ator Naval \'Armador Solimões Web Test 1088\' (armador) criado.','127.0.0.1','2026-09-15 21:44:41'),(156,'dd121661-feb4-42f6-895a-68eb0608d1e4','cliente_criado','Cliente/Ator Naval \'Armador Solimões Web Test 4374\' (armador) criado.','127.0.0.1','2026-09-15 21:45:09'),(157,'dd121661-feb4-42f6-895a-68eb0608d1e4','cliente_criado','Cliente/Ator Naval \'Armador Solimões Web Test 2673\' (armador) criado.','127.0.0.1','2026-09-15 21:45:26'),(158,'dd121661-feb4-42f6-895a-68eb0608d1e4','cliente_criado','Cliente/Ator Naval \'Armador Solimões Web Test 8819\' (armador) criado.','127.0.0.1','2026-09-15 21:45:35'),(159,'dd121661-feb4-42f6-895a-68eb0608d1e4','cliente_criado','Cliente/Ator Naval \'Armador Solimões Web Test 1374\' (armador) criado.','127.0.0.1','2026-09-15 21:46:12'),(160,'dd121661-feb4-42f6-895a-68eb0608d1e4','cliente_criado','Cliente/Ator Naval \'Armador Solimões Web Test 5614\' (armador) criado.','127.0.0.1','2026-09-15 21:46:28'),(161,'dd121661-feb4-42f6-895a-68eb0608d1e4','cliente_criado','Cliente/Ator Naval \'Armador Solimões Web Test 6511\' (armador) criado.','127.0.0.1','2026-09-15 21:46:43'),(162,'dd121661-feb4-42f6-895a-68eb0608d1e4','cliente_criado','Cliente/Ator Naval \'Armador Solimões Web Test 4348\' (armador) criado.','127.0.0.1','2026-09-15 21:47:12'),(169,'dd121661-feb4-42f6-895a-68eb0608d1e4','certificado_lc_criado','Licença de Construção AM-LC-16/26 - B/M AMAZON EXPLORER VI','0.0.0.0','2026-09-15 21:47:57'),(170,'dd121661-feb4-42f6-895a-68eb0608d1e4','certificado_csn_criado','Certificado AM-CSN-2/26 (Definitivo) - B/M SOLIMÕES EXPRESS E2E','0.0.0.0','2026-09-15 21:48:02'),(171,'dd121661-feb4-42f6-895a-68eb0608d1e4','cliente_criado','Cliente/Ator Naval \'Armador Solimões Web Test 1561\' (armador) criado.','127.0.0.1','2026-09-15 21:48:05'),(172,'dd121661-feb4-42f6-895a-68eb0608d1e4','certificado_csn_criado','Certificado AM-CSN-2/26 (Provisório) - barcoteste14','172.23.0.1','2026-09-15 21:57:31'),(173,'dd121661-feb4-42f6-895a-68eb0608d1e4','documento_aprovado_eletronicamente','Certificado CSN 2e0d12e3-729f-482e-bc5c-806e01223ee2 aprovado. Hash final: 531e81d84e107eb30adf93d68cc94a365296587a8918ff6edb2ffb8176848f37','172.23.0.1','2026-09-15 21:57:58'),(180,'dd121661-feb4-42f6-895a-68eb0608d1e4','certificado_lc_criado','Licença de Construção AM-LC-17/26 - B/M AMAZON EXPLORER VI','0.0.0.0','2026-09-15 22:04:36'),(181,'dd121661-feb4-42f6-895a-68eb0608d1e4','certificado_csn_criado','Certificado AM-CSN-3/26 (Definitivo) - B/M SOLIMÕES EXPRESS E2E','0.0.0.0','2026-09-15 22:04:42'),(182,'dd121661-feb4-42f6-895a-68eb0608d1e4','cliente_criado','Cliente/Ator Naval \'Armador Solimões Web Test 7734\' (armador) criado.','127.0.0.1','2026-09-15 22:04:46'),(183,'dd121661-feb4-42f6-895a-68eb0608d1e4','cliente_criado','Cliente/Ator Naval \'Armador Solimões Web Test 4602\' (armador) criado.','127.0.0.1','2026-09-15 22:07:23'),(190,'dd121661-feb4-42f6-895a-68eb0608d1e4','certificado_lc_criado','Licença de Construção AM-LC-18/26 - B/M AMAZON EXPLORER VI','0.0.0.0','2026-09-15 23:14:55'),(191,'dd121661-feb4-42f6-895a-68eb0608d1e4','certificado_csn_criado','Certificado AM-CSN-3/26 (Definitivo) - B/M SOLIMÕES EXPRESS E2E','0.0.0.0','2026-09-15 23:14:59'),(192,'dd121661-feb4-42f6-895a-68eb0608d1e4','cliente_criado','Cliente/Ator Naval \'Armador Solimões Web Test 1782\' (armador) criado.','127.0.0.1','2026-09-15 23:15:02'),(199,'dd121661-feb4-42f6-895a-68eb0608d1e4','certificado_lc_criado','Licença de Construção AM-LC-19/26 - B/M AMAZON EXPLORER VI','0.0.0.0','2026-09-16 00:05:48'),(200,'dd121661-feb4-42f6-895a-68eb0608d1e4','certificado_csn_criado','Certificado AM-CSN-3/26 (Definitivo) - B/M SOLIMÕES EXPRESS E2E','0.0.0.0','2026-09-16 00:05:52'),(201,'dd121661-feb4-42f6-895a-68eb0608d1e4','cliente_criado','Cliente/Ator Naval \'Armador Solimões Web Test 2024\' (armador) criado.','127.0.0.1','2026-09-16 00:05:56'),(208,'dd121661-feb4-42f6-895a-68eb0608d1e4','certificado_lc_criado','Licença de Construção AM-LC-20/26 - B/M AMAZON EXPLORER VI','0.0.0.0','2026-09-16 00:07:32'),(209,'dd121661-feb4-42f6-895a-68eb0608d1e4','certificado_csn_criado','Certificado AM-CSN-3/26 (Definitivo) - B/M SOLIMÕES EXPRESS E2E','0.0.0.0','2026-09-16 00:07:37'),(210,'dd121661-feb4-42f6-895a-68eb0608d1e4','cliente_criado','Cliente/Ator Naval \'Armador Solimões Web Test 6522\' (armador) criado.','127.0.0.1','2026-09-16 00:07:40'),(217,'dd121661-feb4-42f6-895a-68eb0608d1e4','certificado_lc_criado','Licença de Construção AM-LC-21/26 - B/M AMAZON EXPLORER VI','0.0.0.0','2026-09-16 00:23:02'),(218,'dd121661-feb4-42f6-895a-68eb0608d1e4','certificado_csn_criado','Certificado AM-CSN-3/26 (Definitivo) - B/M SOLIMÕES EXPRESS E2E','0.0.0.0','2026-09-16 00:23:20'),(219,'dd121661-feb4-42f6-895a-68eb0608d1e4','cliente_criado','Cliente/Ator Naval \'Armador Solimões Web Test 2578\' (armador) criado.','127.0.0.1','2026-09-16 00:23:23'),(226,'dd121661-feb4-42f6-895a-68eb0608d1e4','certificado_lc_criado','Licença de Construção AM-LC-22/26 - B/M AMAZON EXPLORER VI','0.0.0.0','2026-09-16 00:24:58'),(227,'dd121661-feb4-42f6-895a-68eb0608d1e4','certificado_csn_criado','Certificado AM-CSN-3/26 (Definitivo) - B/M SOLIMÕES EXPRESS E2E','0.0.0.0','2026-09-16 00:25:03'),(228,'dd121661-feb4-42f6-895a-68eb0608d1e4','cliente_criado','Cliente/Ator Naval \'Armador Solimões Web Test 6654\' (armador) criado.','127.0.0.1','2026-09-16 00:25:06'),(235,'dd121661-feb4-42f6-895a-68eb0608d1e4','certificado_lc_criado','Licença de Construção AM-LC-23/26 - B/M AMAZON EXPLORER VI','0.0.0.0','2026-09-16 01:02:51'),(236,'dd121661-feb4-42f6-895a-68eb0608d1e4','certificado_csn_criado','Certificado AM-CSN-3/26 (Definitivo) - B/M SOLIMÕES EXPRESS E2E','0.0.0.0','2026-09-16 01:03:09'),(237,'dd121661-feb4-42f6-895a-68eb0608d1e4','cliente_criado','Cliente/Ator Naval \'Armador Solimões Web Test 8525\' (armador) criado.','127.0.0.1','2026-09-16 01:03:12');
/*!40000 ALTER TABLE `logs_atividade` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `matriz_normativa_documentos`
--

DROP TABLE IF EXISTS `matriz_normativa_documentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `matriz_normativa_documentos` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `versao_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `documento` enum('LC','LCEC','LA','LR','CSN','CNBL','CNARQ') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `classe` enum('EC1','EC2') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `aplicavel` tinyint(1) NOT NULL,
  `vigencia_inicio` date DEFAULT NULL,
  `condicao_json` json DEFAULT NULL,
  `fundamento` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
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
/*!40000 ALTER TABLE `matriz_normativa_documentos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `matriz_normativa_versoes`
--

DROP TABLE IF EXISTS `matriz_normativa_versoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `matriz_normativa_versoes` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `norma_codigo` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `revisao` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `vigencia_inicio` date NOT NULL,
  `vigencia_fim` date DEFAULT NULL,
  `portaria_reconhecimento` varchar(180) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fonte_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
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
/*!40000 ALTER TABLE `matriz_normativa_versoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notificacoes`
--

DROP TABLE IF EXISTS `notificacoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notificacoes` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `usuario_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `evento` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `titulo` varchar(180) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `mensagem` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `referencia_tipo` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `referencia_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
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
INSERT INTO `notificacoes` VALUES ('242b8bbc-5ed4-4106-9c6b-12a9905f2041','dd121661-feb4-42f6-895a-68eb0608d1e4','ANALISE_AGUARDANDO_AGENDAMENTO','Análise aguardando agendamento','A proposta AM-ORC-2/26 gerou uma demanda EC1.','ANALISE_PLANOS','f3956e7b-4005-4dc8-8162-a64df98c6721','analises-planos/form?id=f3956e7b-4005-4dc8-8162-a64df98c6721','2026-09-13 07:36:56','2026-09-12 16:43:02'),('b4aa5315-465e-4655-8c84-4224433845b9','dd121661-feb4-42f6-895a-68eb0608d1e4','ANALISE_AGUARDANDO_AGENDAMENTO','Análise aguardando agendamento','A proposta AM-ORC-2/26 gerou uma demanda EC2.','ANALISE_PLANOS','9520ddf8-5c36-4726-bcf1-765eaabf3e4e','analises-planos/form?id=9520ddf8-5c36-4726-bcf1-765eaabf3e4e','2026-09-13 07:36:56','2026-09-12 16:43:02');
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
  `evento` enum('LOGIN_SUCESSO','LOGIN_FALHA','VISUALIZACAO','DOWNLOAD','UPLOAD_ANALISE') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `portal_auditoria`
--

LOCK TABLES `portal_auditoria` WRITE;
/*!40000 ALTER TABLE `portal_auditoria` DISABLE KEYS */;
INSERT INTO `portal_auditoria` VALUES (1,'1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed',NULL,'LOGIN_FALHA',NULL,NULL,NULL,0,'Login informado: anykedas@gmail.com','172.23.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0','2026-09-12 03:31:15'),(2,'1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed',NULL,'LOGIN_FALHA',NULL,NULL,NULL,0,'Login informado: anykedas@gmail.com','172.23.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0','2026-09-12 03:31:29'),(3,'1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed',NULL,'LOGIN_SUCESSO',NULL,NULL,NULL,1,'Perfil: proprietario','172.23.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0','2026-09-12 03:31:46'),(4,'1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed',NULL,'LOGIN_SUCESSO',NULL,NULL,NULL,1,'Perfil: proprietario','172.23.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0','2026-09-12 05:11:40'),(5,'1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed','proprietario','VISUALIZACAO','317ba743-7aa6-4d66-a845-2d4670f126f0','csn','faa23877-c468-4114-9182-3b6157402f0f',1,'Artefato oficial assinado.','172.23.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0','2026-09-12 06:25:26');
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
INSERT INTO `propostas` VALUES ('029f1f9e-aec9-11f1-8a7c-be2fb1f77be2','AM-ORC-2/26','1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed',NULL,NULL,NULL,NULL,'2026-09-12','2026-10-12',3,'parcelado',18100.00,0.00,0.00,0.00,NULL,'assinada','dd121661-feb4-42f6-895a-68eb0608d1e4','342323aa-142c-447b-b392-7421e538f041','2026-09-12 16:42:58','2026-09-12 16:43:01','9d9f86ef1914ffee35167e70754929d86aa581121448b',1,NULL,NULL,'2026-09-12 13:43:01','Autorização interna - admin','Autorização interna sem assinatura digital','172.23.0.1'),('23f7a7d7-ad55-11f1-8a7c-be2fb1f77be2','AM-ORC-1/26','1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed',NULL,NULL,NULL,NULL,'2026-09-10','2026-10-10',3,'parcelado',9800.00,0.00,0.00,0.00,NULL,'assinada','dd121661-feb4-42f6-895a-68eb0608d1e4','342323aa-142c-447b-b392-7421e538f041','2026-09-10 20:21:01','2026-09-10 20:21:04','0f5a839edc8f89d8a3c87e8ed7b6fb596aa3112d3bbdc',1,NULL,NULL,'2026-09-10 17:21:04','Autorização interna - admin','Autorização interna sem assinatura digital','172.23.0.1'),('926b5e9d-5364-4822-bf48-7cc5473f5bf0','AM-PROP-6862/26','1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed',NULL,NULL,'João da Silva','(91) 99999-9999','2026-09-15','2026-10-15',3,'parcelado',5850.45,1000.00,10.00,650.05,'Observações comerciais de teste','rascunho','dd121661-feb4-42f6-895a-68eb0608d1e4','00000000-0000-4000-8000-000000000100','2026-09-15 19:45:00','2026-09-15 19:45:00',NULL,0,NULL,NULL,NULL,NULL,NULL,NULL),('eb29b70e-aeda-11f1-8a7c-be2fb1f77be2','AM-ORC-3/26','1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed',NULL,NULL,NULL,NULL,'2026-09-12','2026-10-12',3,'parcelado',9800.00,0.00,0.00,0.00,NULL,'assinada','dd121661-feb4-42f6-895a-68eb0608d1e4','342323aa-142c-447b-b392-7421e538f041','2026-09-12 18:51:09','2026-09-12 18:51:12','5540604746298a5c11879a44ceaf23536aa59f1da2dbb',1,NULL,NULL,'2026-09-12 15:51:12','Autorização interna - admin','Autorização interna sem assinatura digital','172.23.0.1');
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
INSERT INTO `propostas_embarcacoes` VALUES ('029f8638-aec9-11f1-8a7c-be2fb1f77be2','029f1f9e-aec9-11f1-8a7c-be2fb1f77be2','317ba743-7aa6-4d66-a845-2d4670f126f0'),('23f7f042-ad55-11f1-8a7c-be2fb1f77be2','23f7a7d7-ad55-11f1-8a7c-be2fb1f77be2','317ba743-7aa6-4d66-a845-2d4670f126f0'),('eb2a1af6-aeda-11f1-8a7c-be2fb1f77be2','eb29b70e-aeda-11f1-8a7c-be2fb1f77be2','317ba743-7aa6-4d66-a845-2d4670f126f0');
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
INSERT INTO `propostas_servicos` (`id`, `proposta_id`, `servico_id`, `embarcacao_id`, `preco_aplicado`, `quantidade`) VALUES ('029fbff8-aec9-11f1-8a7c-be2fb1f77be2','029f1f9e-aec9-11f1-8a7c-be2fb1f77be2','a1d98e55-6ebc-11f1-86ce-7e17ff5f90bf','317ba743-7aa6-4d66-a845-2d4670f126f0',3500.00,1),('029fed04-aec9-11f1-8a7c-be2fb1f77be2','029f1f9e-aec9-11f1-8a7c-be2fb1f77be2','a1d98eaf-6ebc-11f1-86ce-7e17ff5f90bf','317ba743-7aa6-4d66-a845-2d4670f126f0',2800.00,1),('029ff67c-aec9-11f1-8a7c-be2fb1f77be2','029f1f9e-aec9-11f1-8a7c-be2fb1f77be2','a1d98d8e-6ebc-11f1-86ce-7e17ff5f90bf','317ba743-7aa6-4d66-a845-2d4670f126f0',3500.00,1),('029fff4d-aec9-11f1-8a7c-be2fb1f77be2','029f1f9e-aec9-11f1-8a7c-be2fb1f77be2','a1d980bd-6ebc-11f1-86ce-7e17ff5f90bf','317ba743-7aa6-4d66-a845-2d4670f126f0',2500.00,1),('02a0075f-aec9-11f1-8a7c-be2fb1f77be2','029f1f9e-aec9-11f1-8a7c-be2fb1f77be2','a1d98b0e-6ebc-11f1-86ce-7e17ff5f90bf','317ba743-7aa6-4d66-a845-2d4670f126f0',2500.00,1),('02a01457-aec9-11f1-8a7c-be2fb1f77be2','029f1f9e-aec9-11f1-8a7c-be2fb1f77be2','a1d98f2e-6ebc-11f1-86ce-7e17ff5f90bf','317ba743-7aa6-4d66-a845-2d4670f126f0',1800.00,1),('02a01c0a-aec9-11f1-8a7c-be2fb1f77be2','029f1f9e-aec9-11f1-8a7c-be2fb1f77be2','a1d992d7-6ebc-11f1-86ce-7e17ff5f90bf','317ba743-7aa6-4d66-a845-2d4670f126f0',1500.00,1),('23f81d61-ad55-11f1-8a7c-be2fb1f77be2','23f7a7d7-ad55-11f1-8a7c-be2fb1f77be2','a1d98eaf-6ebc-11f1-86ce-7e17ff5f90bf','317ba743-7aa6-4d66-a845-2d4670f126f0',2800.00,1),('23f8387f-ad55-11f1-8a7c-be2fb1f77be2','23f7a7d7-ad55-11f1-8a7c-be2fb1f77be2','a1d98e55-6ebc-11f1-86ce-7e17ff5f90bf','317ba743-7aa6-4d66-a845-2d4670f126f0',3500.00,1),('23f84197-ad55-11f1-8a7c-be2fb1f77be2','23f7a7d7-ad55-11f1-8a7c-be2fb1f77be2','a1d98d8e-6ebc-11f1-86ce-7e17ff5f90bf','317ba743-7aa6-4d66-a845-2d4670f126f0',3500.00,1),('eb2a479e-aeda-11f1-8a7c-be2fb1f77be2','eb29b70e-aeda-11f1-8a7c-be2fb1f77be2','a1d98eaf-6ebc-11f1-86ce-7e17ff5f90bf','317ba743-7aa6-4d66-a845-2d4670f126f0',2800.00,1),('eb2a6df3-aeda-11f1-8a7c-be2fb1f77be2','eb29b70e-aeda-11f1-8a7c-be2fb1f77be2','a1d98e55-6ebc-11f1-86ce-7e17ff5f90bf','317ba743-7aa6-4d66-a845-2d4670f126f0',3500.00,1),('eb2a79c8-aeda-11f1-8a7c-be2fb1f77be2','eb29b70e-aeda-11f1-8a7c-be2fb1f77be2','a1d98d8e-6ebc-11f1-86ce-7e17ff5f90bf','317ba743-7aa6-4d66-a845-2d4670f126f0',3500.00,1);
/*!40000 ALTER TABLE `propostas_servicos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `protocolo_aceites`
--

DROP TABLE IF EXISTS `protocolo_aceites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `protocolo_aceites` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `movimentacao_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `token_hash` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `expira_em` datetime NOT NULL,
  `nome` varchar(180) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `documento_mascarado` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `termo_aceito` tinyint(1) NOT NULL DEFAULT '0',
  `ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `aceito_em` datetime DEFAULT NULL,
  `criado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
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
INSERT INTO `protocolo_aceites` VALUES ('24169b77-b166-11f1-8a7c-be2fb1f77be2','b7a3f0a0-9eb5-4442-aa83-d7484e1f0cbf','34c19483490a4523ceb7f76da6433970a3453976325e31647bd1a13ada7ae479','2026-10-01 00:32:47',NULL,NULL,0,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:32:47'),('2a00687d-b15a-11f1-8a7c-be2fb1f77be2','05ce7e74-66f4-489f-b571-cf9efecedb71','1c4156458e0c2f563b73e0129daf521caec55b7781adda497193bbf8c2e3b7de','2026-09-30 23:07:03',NULL,NULL,0,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-15 23:07:03'),('2b4a4a58-b183-11f1-8a7c-be2fb1f77be2','8a866c5d-589f-443e-91a1-a38e96fc35c8','50d0d41fad1eeeaf067a0a7abefa9083e1046f271f8c614f2d13433d7e0fa7bd','2026-10-01 04:00:35',NULL,NULL,0,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 04:00:35'),('34046fe2-b17e-11f1-8a7c-be2fb1f77be2','dc90cc88-5fb6-41aa-9910-0b2f55faabe9','2c8bfd1413114b1d57ebb7ae81aff0c0ef7ff9ad89e02cc824758282d7159e79','2026-10-01 03:25:02',NULL,NULL,0,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 03:25:02'),('39856aa9-b164-11f1-8a7c-be2fb1f77be2','1c4b3e70-368c-4dcb-84e7-99de93e1391e','dc63da177c01710f80756c92fcd8f3160e4e3970adc7b707df374ddb96b2b05d','2026-10-01 00:19:04',NULL,NULL,0,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:19:04'),('44d2ffad-b168-11f1-8a7c-be2fb1f77be2','c34ae932-3485-4e89-b7e7-d5378b403320','b738c55b7e5966f2b12a9899799ad242790f4ffd81a267772d7292bb49d44110','2026-10-01 00:48:01',NULL,NULL,0,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:48:01'),('4c9e2904-b166-11f1-8a7c-be2fb1f77be2','978a98ef-972c-4034-8018-778982688712','80041402ce1dc01cbcac7926f15b8670dec86c3077170aa673a4f4fc0d61a79f','2026-10-01 00:33:55',NULL,NULL,0,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:33:55'),('4fe6fd86-b13e-11f1-8a7c-be2fb1f77be2','2c05e7eb-c76a-4ab8-a865-73a3487673ed','e8d848d1c7e54c9981186b0a433c114e12d3314973e28a2df66f6f89907d230b','2026-09-30 19:47:41',NULL,NULL,0,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-15 19:47:41'),('505730c8-b15a-11f1-8a7c-be2fb1f77be2','5cad0cfe-ddfd-4b24-a1ae-b79baddb4743','4a309883e46712fab8ccb8d9629a7c00df2bcf61bfe30978097c2ca90a265721','2026-09-30 23:08:07',NULL,NULL,0,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-15 23:08:07'),('5349679f-af46-11f1-8a7c-be2fb1f77be2','ca21f805-0db1-4a3f-bcd9-96881787ae31','f931c330416a6bd79777bb90c5804adb219bfec47a0770e0779e4313200741bd','2026-09-28 07:40:00',NULL,NULL,0,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-13 07:40:00'),('546380c3-b131-11f1-8a7c-be2fb1f77be2','3eda28e8-76eb-402c-9e6b-9acc495494b0','9d4027a3dbafe6319929fecf75b68848716b2b8e9e00b582b3b147a35ba91e38','2026-09-30 18:14:45',NULL,NULL,0,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-15 18:14:45'),('590da4a8-b157-11f1-8a7c-be2fb1f77be2','19eff16f-90eb-4977-af31-78867d29b712','6342674b33406d7b333117f10e58d4124b2c35a1b61db02199bf132d185c2801','2026-09-30 22:46:53',NULL,NULL,0,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-15 22:46:53'),('655b9815-af3c-11f1-8a7c-be2fb1f77be2','eb15509f-4348-404a-a867-f504ec1f0309','ce1d69338823b1da8b8c8d9a3f8f4d0ecbfb19c380d139114c1da2e820a41b0c','2026-09-28 06:28:55',NULL,NULL,0,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-13 06:28:55'),('6ab8635c-b174-11f1-8a7c-be2fb1f77be2','0d2b6fe8-f1d9-4eda-b027-d4c2a7dc0f3c','2359700c1c18754ba3cd4545f9350b2a0c11733bd06a7363717c900345d5333b','2026-10-01 02:14:58',NULL,NULL,0,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 02:14:58'),('6f4d63d0-b166-11f1-8a7c-be2fb1f77be2','f9596bc8-fb67-410c-a70a-987a6329429f','7a81e6f3cce571ed81997607ada07c170308b7310a22303490f0f06ec714b34d','2026-10-01 00:34:53',NULL,NULL,0,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:34:53'),('707d9fef-b13e-11f1-8a7c-be2fb1f77be2','4db389b4-9b3d-4a8f-91d0-c85f5d05a25a','cd282cee49a0df7162631c77429c4db45f231f9d54ed44195e6ae4c5023fc758','2026-09-30 19:48:35',NULL,NULL,0,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-15 19:48:35'),('7e79652c-b183-11f1-8a7c-be2fb1f77be2','fd700974-4406-4884-a894-3a3244642e02','6eb5eda89af7ec4cbf2b217b58f404fb264b286102e3abd10e2c14e5a6b0d4d0','2026-10-01 04:02:54',NULL,NULL,0,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 04:02:54'),('8674bb46-b17b-11f1-8a7c-be2fb1f77be2','27ede70e-7f03-48a8-9299-37e75029c309','4fcab97ebf489c693b44688d45e6256a83b545a010bb81a15f4ef0e50a7013b8','2026-10-01 03:05:52',NULL,NULL,0,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 03:05:52'),('8b2e7e77-b136-11f1-8a7c-be2fb1f77be2','aee3ea4e-d71b-4e1b-bf22-203ec3ea7db3','a6ef9d6f2f8d15ee31e520b3eacd5846bad4de03fe73453346015d5c19de2b07','2026-09-30 18:52:04',NULL,NULL,0,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-15 18:52:04'),('989bca9b-b16a-11f1-8a7c-be2fb1f77be2','e8e965ea-0182-40d5-87aa-e2ef68326830','fd5b93a6366f76c1e151e3bba6de0b4fff66eff3fa40203d5a898fe2accaa202','2026-10-01 01:04:41',NULL,NULL,0,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 01:04:41'),('99789f73-af3e-11f1-8a7c-be2fb1f77be2','b3c49df0-1ad3-4bca-9840-9249fb849d26','189abf10f5f2ec652e814759c2191f60ad574f8672ea38809960ebc45fd71241','2026-09-28 06:44:42',NULL,NULL,0,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-13 06:44:42'),('9d8b0475-b166-11f1-8a7c-be2fb1f77be2','d545ae9f-6d55-4801-9a90-ea3e6f15fe89','fd2c4890c7b2bc0bb5eab392699cb3e0cb21b4149f754cdab59dd5555daf66f0','2026-10-01 00:36:11',NULL,NULL,0,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:36:11'),('a9d3164f-b166-11f1-8a7c-be2fb1f77be2','7b4b9c41-daa2-4bee-b7c0-01fb69757990','d8454704292f9943aaef99ea62898b3585dd283f3c797877621e71a7fca71c88','2026-10-01 00:36:31',NULL,NULL,0,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:36:31'),('babd552e-b139-11f1-8a7c-be2fb1f77be2','a2483f83-da37-4bb4-b41c-0596c02f9832','d403aff7c0334043fc0e555bb31aece1f9a25929bb912612f16e0fac88697009','2026-09-30 19:14:52',NULL,NULL,0,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-15 19:14:52'),('bbb61f8d-b12f-11f1-8a7c-be2fb1f77be2','d5dc879a-5373-4bdf-8833-44f96a62b9c7','94f4a11e515ac67572ef5e84b647a8fbb9ceef6384c1f524cbed7a3cc8f5b6f5','2026-09-30 18:03:19',NULL,NULL,0,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-15 18:03:19'),('c4aa53bf-b17b-11f1-8a7c-be2fb1f77be2','e867a119-4166-490e-9d10-4042348659c3','ffa9532617d09dbf556f9e278733dd3308e83ea9951b75c460e051574f4063dd','2026-10-01 03:07:36',NULL,NULL,0,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 03:07:36'),('d12f585d-af3c-11f1-8a7c-be2fb1f77be2','17650e69-7a84-417a-986b-21b5cfbb06bd','df7faa3f5396e38501ed50198e303deb03cb921e43cbac4a2724b5d50199742e','2026-09-28 06:31:56',NULL,NULL,0,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-13 06:31:56'),('e683015d-b164-11f1-8a7c-be2fb1f77be2','81ef628e-0452-463a-be5a-12a89f557e86','79477fa1344dd1bc3b84cc34c692a75d760e0316bf393d7487c56e3110087787','2026-10-01 00:23:54',NULL,NULL,0,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:23:54'),('e84d600b-b142-11f1-8a7c-be2fb1f77be2','bef51f8d-70da-4b12-ba87-08fd57195507','08da01453f6d676b78d6ce0a9b50d7136b2b487b45203f3f09e458bb70d10197','2026-09-30 20:20:34',NULL,NULL,0,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-15 20:20:34'),('f70ccfc1-b17d-11f1-8a7c-be2fb1f77be2','c819a998-57ec-40e1-83e8-2fee5f50faa0','f415b3d2f08da43cb4a4909fcaf1d5fd9661768f42fe1b2b831fa216a1cc93ef','2026-10-01 03:23:19',NULL,NULL,0,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 03:23:19'),('f77ef8f2-af43-11f1-8a7c-be2fb1f77be2','c24df4b1-b953-4f98-b19b-b71d12e6c692','aac724dd98629682cec319be402e4a4b05207c9d9950dfceff130091d53f9632','2026-09-28 07:23:07',NULL,NULL,0,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-13 07:23:07');
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
  `dossie_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `movimentacao_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `evento` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `usuario_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `perfil` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estado_anterior` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estado_novo` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `detalhe` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `hash_referencia` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_protocolo_auditoria` (`dossie_id`,`criado_em`),
  KEY `fk_protocolo_auditoria_mov` (`movimentacao_id`),
  KEY `fk_protocolo_auditoria_usuario` (`usuario_id`),
  CONSTRAINT `fk_protocolo_auditoria_dossie` FOREIGN KEY (`dossie_id`) REFERENCES `protocolo_dossies` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_protocolo_auditoria_mov` FOREIGN KEY (`movimentacao_id`) REFERENCES `protocolo_movimentacoes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_protocolo_auditoria_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=419 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `protocolo_auditoria`
--

LOCK TABLES `protocolo_auditoria` WRITE;
/*!40000 ALTER TABLE `protocolo_auditoria` DISABLE KEYS */;
INSERT INTO `protocolo_auditoria` VALUES (1,'5c2f5179-2235-42ab-b85e-ddfff32a4f79',NULL,'DOSSIE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'EM_PREPARACAO','AM-PROT-1/26',NULL,'2026-09-13 06:28:55'),(2,'5c2f5179-2235-42ab-b85e-ddfff32a4f79',NULL,'DOSSIE_EDITADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,NULL,'Assunto atualizado.',NULL,'2026-09-13 06:28:55'),(3,'5c2f5179-2235-42ab-b85e-ddfff32a4f79','eb15509f-4348-404a-a867-f504ec1f0309','MOVIMENTACAO_CONFIRMADA','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','EM_PREPARACAO','Evento 01','9268e7b8af36924ca8d0fccc2605af3470e4adebf64621e4f04e82f2b39f3815','2026-09-13 06:28:55'),(4,'5c2f5179-2235-42ab-b85e-ddfff32a4f79',NULL,'REGISTRO_ORGAO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','PROTOCOLADO','Processo Marinha: 23000.098765/2026-12',NULL,'2026-09-13 06:28:55'),(5,'5c2f5179-2235-42ab-b85e-ddfff32a4f79','eb15509f-4348-404a-a867-f504ec1f0309','ACEITE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'PENDENTE',NULL,NULL,'2026-09-13 06:28:55'),(6,'5c2f5179-2235-42ab-b85e-ddfff32a4f79','eb15509f-4348-404a-a867-f504ec1f0309','ORIGINAL_DEVOLVIDO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'DEVOLVIDO','Baixa de custódia',NULL,'2026-09-13 06:28:55'),(7,'5c2f5179-2235-42ab-b85e-ddfff32a4f79',NULL,'DOSSIE_ENCERRADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','PROTOCOLADO','ENCERRADO',NULL,NULL,'2026-09-13 06:28:56'),(8,'6943e349-8e7e-4ed8-9724-93d94fe0e8f6',NULL,'DOSSIE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'EM_PREPARACAO','AM-PROT-2/26',NULL,'2026-09-13 06:31:56'),(9,'6943e349-8e7e-4ed8-9724-93d94fe0e8f6',NULL,'DOSSIE_EDITADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,NULL,'Assunto atualizado.',NULL,'2026-09-13 06:31:56'),(10,'6943e349-8e7e-4ed8-9724-93d94fe0e8f6','17650e69-7a84-417a-986b-21b5cfbb06bd','MOVIMENTACAO_CONFIRMADA','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','EM_PREPARACAO','Evento 01','39385e12cf6dc69c982540ce9dafeaba784bde0e4f47a4bdfde91e9a1ca21f18','2026-09-13 06:31:56'),(11,'6943e349-8e7e-4ed8-9724-93d94fe0e8f6',NULL,'REGISTRO_ORGAO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','PROTOCOLADO','Processo Marinha: 23000.098765/2026-12',NULL,'2026-09-13 06:31:56'),(12,'6943e349-8e7e-4ed8-9724-93d94fe0e8f6','17650e69-7a84-417a-986b-21b5cfbb06bd','ACEITE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'PENDENTE',NULL,NULL,'2026-09-13 06:31:56'),(13,'6943e349-8e7e-4ed8-9724-93d94fe0e8f6','17650e69-7a84-417a-986b-21b5cfbb06bd','ORIGINAL_DEVOLVIDO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'DEVOLVIDO','Baixa de custódia',NULL,'2026-09-13 06:31:56'),(14,'6943e349-8e7e-4ed8-9724-93d94fe0e8f6',NULL,'DOSSIE_ENCERRADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','PROTOCOLADO','ENCERRADO',NULL,NULL,'2026-09-13 06:31:57'),(15,'4b1b2cb7-71e7-472a-ab95-d70c773c72c0',NULL,'DOSSIE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'EM_PREPARACAO','AM-PROT-3/26',NULL,'2026-09-13 06:44:41'),(16,'4b1b2cb7-71e7-472a-ab95-d70c773c72c0',NULL,'DOSSIE_EDITADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,NULL,'Assunto atualizado.',NULL,'2026-09-13 06:44:41'),(17,'4b1b2cb7-71e7-472a-ab95-d70c773c72c0','b3c49df0-1ad3-4bca-9840-9249fb849d26','MOVIMENTACAO_CONFIRMADA','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','EM_PREPARACAO','Evento 01','7015af64ddc6fcf2a83029f6b2cdaf730495fa8b9993e9fb618e65fb84d24f75','2026-09-13 06:44:42'),(18,'4b1b2cb7-71e7-472a-ab95-d70c773c72c0',NULL,'REGISTRO_ORGAO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','PROTOCOLADO','Processo Marinha: 23000.098765/2026-12',NULL,'2026-09-13 06:44:42'),(19,'4b1b2cb7-71e7-472a-ab95-d70c773c72c0','b3c49df0-1ad3-4bca-9840-9249fb849d26','ACEITE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'PENDENTE',NULL,NULL,'2026-09-13 06:44:42'),(20,'4b1b2cb7-71e7-472a-ab95-d70c773c72c0','b3c49df0-1ad3-4bca-9840-9249fb849d26','ORIGINAL_DEVOLVIDO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'DEVOLVIDO','Baixa de custódia',NULL,'2026-09-13 06:44:42'),(21,'4b1b2cb7-71e7-472a-ab95-d70c773c72c0',NULL,'DOSSIE_ENCERRADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','PROTOCOLADO','ENCERRADO',NULL,NULL,'2026-09-13 06:44:42'),(22,'bcab5c48-6a49-4a42-88c8-a287eda78876',NULL,'DOSSIE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'EM_PREPARACAO','AM-PROT-4/26',NULL,'2026-09-13 07:23:07'),(23,'bcab5c48-6a49-4a42-88c8-a287eda78876',NULL,'DOSSIE_EDITADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,NULL,'Assunto atualizado.',NULL,'2026-09-13 07:23:07'),(24,'bcab5c48-6a49-4a42-88c8-a287eda78876','c24df4b1-b953-4f98-b19b-b71d12e6c692','MOVIMENTACAO_CONFIRMADA','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','EM_PREPARACAO','Evento 01','ea324a1230c8a381cf463132fe2e2c9570e5af7d59fb72fe04bd672e711ebd7a','2026-09-13 07:23:07'),(25,'bcab5c48-6a49-4a42-88c8-a287eda78876',NULL,'REGISTRO_ORGAO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','PROTOCOLADO','Processo Marinha: 23000.098765/2026-12',NULL,'2026-09-13 07:23:07'),(26,'bcab5c48-6a49-4a42-88c8-a287eda78876','c24df4b1-b953-4f98-b19b-b71d12e6c692','ACEITE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'PENDENTE',NULL,NULL,'2026-09-13 07:23:07'),(27,'bcab5c48-6a49-4a42-88c8-a287eda78876','c24df4b1-b953-4f98-b19b-b71d12e6c692','ORIGINAL_DEVOLVIDO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'DEVOLVIDO','Baixa de custódia',NULL,'2026-09-13 07:23:07'),(28,'bcab5c48-6a49-4a42-88c8-a287eda78876',NULL,'DOSSIE_ENCERRADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','PROTOCOLADO','ENCERRADO',NULL,NULL,'2026-09-13 07:23:07'),(29,'0e10393a-e8b8-4fd2-8b83-fee61b6a5cf0',NULL,'DOSSIE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'EM_PREPARACAO','AM-PROT-5/26',NULL,'2026-09-13 07:40:00'),(30,'0e10393a-e8b8-4fd2-8b83-fee61b6a5cf0',NULL,'DOSSIE_EDITADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,NULL,'Assunto atualizado.',NULL,'2026-09-13 07:40:00'),(31,'0e10393a-e8b8-4fd2-8b83-fee61b6a5cf0','ca21f805-0db1-4a3f-bcd9-96881787ae31','MOVIMENTACAO_CONFIRMADA','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','EM_PREPARACAO','Evento 01','acad6406e363d4aad24960561245f01ffc8daba6a7c8b5ae1289c53a9d320f67','2026-09-13 07:40:00'),(32,'0e10393a-e8b8-4fd2-8b83-fee61b6a5cf0',NULL,'REGISTRO_ORGAO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','PROTOCOLADO','Processo Marinha: 23000.098765/2026-12',NULL,'2026-09-13 07:40:00'),(33,'0e10393a-e8b8-4fd2-8b83-fee61b6a5cf0','ca21f805-0db1-4a3f-bcd9-96881787ae31','ACEITE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'PENDENTE',NULL,NULL,'2026-09-13 07:40:00'),(34,'0e10393a-e8b8-4fd2-8b83-fee61b6a5cf0','ca21f805-0db1-4a3f-bcd9-96881787ae31','ORIGINAL_DEVOLVIDO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'DEVOLVIDO','Baixa de custódia',NULL,'2026-09-13 07:40:00'),(35,'0e10393a-e8b8-4fd2-8b83-fee61b6a5cf0',NULL,'DOSSIE_ENCERRADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','PROTOCOLADO','ENCERRADO',NULL,NULL,'2026-09-13 07:40:00'),(36,'caaf28b9-6221-4fc9-ac79-7f11d30423e6',NULL,'DOSSIE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'EM_PREPARACAO','AM-PROT-6/26',NULL,'2026-09-15 18:03:19'),(37,'caaf28b9-6221-4fc9-ac79-7f11d30423e6',NULL,'DOSSIE_EDITADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,NULL,'Assunto atualizado.',NULL,'2026-09-15 18:03:19'),(38,'caaf28b9-6221-4fc9-ac79-7f11d30423e6','d5dc879a-5373-4bdf-8833-44f96a62b9c7','MOVIMENTACAO_CONFIRMADA','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','EM_PREPARACAO','Evento 01','e3005e738e9130da08c3357641f1c23b8714589e1a44d8d5b1d48787a7cad26b','2026-09-15 18:03:19'),(39,'caaf28b9-6221-4fc9-ac79-7f11d30423e6',NULL,'REGISTRO_ORGAO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','PROTOCOLADO','Processo Marinha: 23000.098765/2026-12',NULL,'2026-09-15 18:03:19'),(40,'caaf28b9-6221-4fc9-ac79-7f11d30423e6','d5dc879a-5373-4bdf-8833-44f96a62b9c7','ACEITE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'PENDENTE',NULL,NULL,'2026-09-15 18:03:19'),(41,'caaf28b9-6221-4fc9-ac79-7f11d30423e6','d5dc879a-5373-4bdf-8833-44f96a62b9c7','ORIGINAL_DEVOLVIDO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'DEVOLVIDO','Baixa de custódia',NULL,'2026-09-15 18:03:19'),(42,'caaf28b9-6221-4fc9-ac79-7f11d30423e6',NULL,'DOSSIE_ENCERRADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','PROTOCOLADO','ENCERRADO',NULL,NULL,'2026-09-15 18:03:19'),(43,'84d585b3-cbd9-4a4f-952b-462e5b2e2274',NULL,'DOSSIE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'EM_PREPARACAO','AM-PROT-7/26',NULL,'2026-09-15 18:14:44'),(44,'84d585b3-cbd9-4a4f-952b-462e5b2e2274',NULL,'DOSSIE_EDITADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,NULL,'Assunto atualizado.',NULL,'2026-09-15 18:14:44'),(45,'84d585b3-cbd9-4a4f-952b-462e5b2e2274','3eda28e8-76eb-402c-9e6b-9acc495494b0','MOVIMENTACAO_CONFIRMADA','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','EM_PREPARACAO','Evento 01','ba3bdcafb174d972f0aedfefd5150c09c9e37c425f316f5e52bc2307c966f07d','2026-09-15 18:14:45'),(46,'84d585b3-cbd9-4a4f-952b-462e5b2e2274',NULL,'REGISTRO_ORGAO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','PROTOCOLADO','Processo Marinha: 23000.098765/2026-12',NULL,'2026-09-15 18:14:45'),(47,'84d585b3-cbd9-4a4f-952b-462e5b2e2274','3eda28e8-76eb-402c-9e6b-9acc495494b0','ACEITE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'PENDENTE',NULL,NULL,'2026-09-15 18:14:45'),(48,'84d585b3-cbd9-4a4f-952b-462e5b2e2274','3eda28e8-76eb-402c-9e6b-9acc495494b0','ORIGINAL_DEVOLVIDO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'DEVOLVIDO','Baixa de custódia',NULL,'2026-09-15 18:14:45'),(49,'84d585b3-cbd9-4a4f-952b-462e5b2e2274',NULL,'DOSSIE_ENCERRADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','PROTOCOLADO','ENCERRADO',NULL,NULL,'2026-09-15 18:14:45'),(50,'8cf5fec7-6a72-4a8d-906f-a9b21895bd30',NULL,'DOSSIE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'EM_PREPARACAO','AM-PROT-8/26',NULL,'2026-09-15 18:52:04'),(51,'8cf5fec7-6a72-4a8d-906f-a9b21895bd30',NULL,'DOSSIE_EDITADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,NULL,'Assunto atualizado.',NULL,'2026-09-15 18:52:04'),(52,'8cf5fec7-6a72-4a8d-906f-a9b21895bd30','aee3ea4e-d71b-4e1b-bf22-203ec3ea7db3','MOVIMENTACAO_CONFIRMADA','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','EM_PREPARACAO','Evento 01','72a1977940f39577d5b1070ada2d5db30a4c8b8c2560996cacaf6e5764cfa787','2026-09-15 18:52:04'),(53,'8cf5fec7-6a72-4a8d-906f-a9b21895bd30',NULL,'REGISTRO_ORGAO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','PROTOCOLADO','Processo Marinha: 23000.098765/2026-12',NULL,'2026-09-15 18:52:04'),(54,'8cf5fec7-6a72-4a8d-906f-a9b21895bd30','aee3ea4e-d71b-4e1b-bf22-203ec3ea7db3','ACEITE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'PENDENTE',NULL,NULL,'2026-09-15 18:52:04'),(55,'8cf5fec7-6a72-4a8d-906f-a9b21895bd30','aee3ea4e-d71b-4e1b-bf22-203ec3ea7db3','ORIGINAL_DEVOLVIDO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'DEVOLVIDO','Baixa de custódia',NULL,'2026-09-15 18:52:04'),(56,'8cf5fec7-6a72-4a8d-906f-a9b21895bd30',NULL,'DOSSIE_ENCERRADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','PROTOCOLADO','ENCERRADO',NULL,NULL,'2026-09-15 18:52:05'),(57,'24997b9b-b4c2-45cc-ac84-62ce6c5fee9b',NULL,'DOSSIE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'EM_PREPARACAO','AM-PROT-9/26',NULL,'2026-09-15 19:14:52'),(58,'24997b9b-b4c2-45cc-ac84-62ce6c5fee9b',NULL,'DOSSIE_EDITADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,NULL,'Assunto atualizado.',NULL,'2026-09-15 19:14:52'),(59,'24997b9b-b4c2-45cc-ac84-62ce6c5fee9b','a2483f83-da37-4bb4-b41c-0596c02f9832','MOVIMENTACAO_CONFIRMADA','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','EM_PREPARACAO','Evento 01','f897513643fa42251db65e7530ac35e0aa5353c4a50720ca2a1d7fdffac0e775','2026-09-15 19:14:52'),(60,'24997b9b-b4c2-45cc-ac84-62ce6c5fee9b',NULL,'REGISTRO_ORGAO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','PROTOCOLADO','Processo Marinha: 23000.098765/2026-12',NULL,'2026-09-15 19:14:52'),(61,'24997b9b-b4c2-45cc-ac84-62ce6c5fee9b','a2483f83-da37-4bb4-b41c-0596c02f9832','ACEITE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'PENDENTE',NULL,NULL,'2026-09-15 19:14:52'),(62,'24997b9b-b4c2-45cc-ac84-62ce6c5fee9b','a2483f83-da37-4bb4-b41c-0596c02f9832','ORIGINAL_DEVOLVIDO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'DEVOLVIDO','Baixa de custódia',NULL,'2026-09-15 19:14:52'),(63,'24997b9b-b4c2-45cc-ac84-62ce6c5fee9b',NULL,'DOSSIE_ENCERRADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','PROTOCOLADO','ENCERRADO',NULL,NULL,'2026-09-15 19:14:53'),(64,'513cfd70-6874-4ec6-a899-26622a6d524e',NULL,'DOSSIE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'EM_PREPARACAO','AM-PROT-10/26',NULL,'2026-09-15 19:47:40'),(65,'513cfd70-6874-4ec6-a899-26622a6d524e',NULL,'DOSSIE_EDITADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,NULL,'Assunto atualizado.',NULL,'2026-09-15 19:47:40'),(66,'513cfd70-6874-4ec6-a899-26622a6d524e','2c05e7eb-c76a-4ab8-a865-73a3487673ed','MOVIMENTACAO_CONFIRMADA','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','EM_PREPARACAO','Evento 01','84335858f5d626d483208f71d02db5a96e6eacb927b1cce19fce242195695210','2026-09-15 19:47:41'),(67,'513cfd70-6874-4ec6-a899-26622a6d524e',NULL,'REGISTRO_ORGAO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','PROTOCOLADO','Processo Marinha: 23000.098765/2026-12',NULL,'2026-09-15 19:47:41'),(68,'513cfd70-6874-4ec6-a899-26622a6d524e','2c05e7eb-c76a-4ab8-a865-73a3487673ed','ACEITE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'PENDENTE',NULL,NULL,'2026-09-15 19:47:41'),(69,'513cfd70-6874-4ec6-a899-26622a6d524e','2c05e7eb-c76a-4ab8-a865-73a3487673ed','ORIGINAL_DEVOLVIDO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'DEVOLVIDO','Baixa de custódia',NULL,'2026-09-15 19:47:41'),(70,'513cfd70-6874-4ec6-a899-26622a6d524e',NULL,'DOSSIE_ENCERRADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','PROTOCOLADO','ENCERRADO',NULL,NULL,'2026-09-15 19:47:41'),(71,'f2b06336-c0dc-4f21-9372-c9d8c0b164b9',NULL,'DOSSIE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'EM_PREPARACAO','AM-PROT-11/26',NULL,'2026-09-15 19:48:35'),(72,'f2b06336-c0dc-4f21-9372-c9d8c0b164b9',NULL,'DOSSIE_EDITADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,NULL,'Assunto atualizado.',NULL,'2026-09-15 19:48:35'),(73,'f2b06336-c0dc-4f21-9372-c9d8c0b164b9','4db389b4-9b3d-4a8f-91d0-c85f5d05a25a','MOVIMENTACAO_CONFIRMADA','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','EM_PREPARACAO','Evento 01','779acbe7a13201803e117b9d47dcc752c6c5fcb6e073e9db21d2bf01e2af39ba','2026-09-15 19:48:35'),(74,'f2b06336-c0dc-4f21-9372-c9d8c0b164b9',NULL,'REGISTRO_ORGAO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','PROTOCOLADO','Processo Marinha: 23000.098765/2026-12',NULL,'2026-09-15 19:48:35'),(75,'f2b06336-c0dc-4f21-9372-c9d8c0b164b9','4db389b4-9b3d-4a8f-91d0-c85f5d05a25a','ACEITE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'PENDENTE',NULL,NULL,'2026-09-15 19:48:35'),(76,'f2b06336-c0dc-4f21-9372-c9d8c0b164b9','4db389b4-9b3d-4a8f-91d0-c85f5d05a25a','ORIGINAL_DEVOLVIDO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'DEVOLVIDO','Baixa de custódia',NULL,'2026-09-15 19:48:35'),(77,'f2b06336-c0dc-4f21-9372-c9d8c0b164b9',NULL,'DOSSIE_ENCERRADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','PROTOCOLADO','ENCERRADO',NULL,NULL,'2026-09-15 19:48:36'),(78,'db4bef24-d50b-43b7-a980-61eb3fe58af0',NULL,'DOSSIE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'EM_PREPARACAO','AM-PROT-12/26',NULL,'2026-09-15 20:20:34'),(79,'db4bef24-d50b-43b7-a980-61eb3fe58af0',NULL,'DOSSIE_EDITADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,NULL,'Assunto atualizado.',NULL,'2026-09-15 20:20:34'),(80,'db4bef24-d50b-43b7-a980-61eb3fe58af0','bef51f8d-70da-4b12-ba87-08fd57195507','MOVIMENTACAO_CONFIRMADA','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','EM_PREPARACAO','Evento 01','fb145dacb8f8879564a49169dfb433851e405e12ca6c3ba3ca6095523d736d8d','2026-09-15 20:20:34'),(81,'db4bef24-d50b-43b7-a980-61eb3fe58af0',NULL,'REGISTRO_ORGAO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','PROTOCOLADO','Processo Marinha: 23000.098765/2026-12',NULL,'2026-09-15 20:20:34'),(82,'db4bef24-d50b-43b7-a980-61eb3fe58af0','bef51f8d-70da-4b12-ba87-08fd57195507','ACEITE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'PENDENTE',NULL,NULL,'2026-09-15 20:20:34'),(83,'db4bef24-d50b-43b7-a980-61eb3fe58af0','bef51f8d-70da-4b12-ba87-08fd57195507','ORIGINAL_DEVOLVIDO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'DEVOLVIDO','Baixa de custódia',NULL,'2026-09-15 20:20:34'),(84,'db4bef24-d50b-43b7-a980-61eb3fe58af0',NULL,'DOSSIE_ENCERRADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','PROTOCOLADO','ENCERRADO',NULL,NULL,'2026-09-15 20:20:35'),(143,'17cc06f0-6f7c-4992-9b54-f7d007a63773',NULL,'DOSSIE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'EM_PREPARACAO','AM-PROT-23/26',NULL,'2026-09-15 22:46:53'),(144,'17cc06f0-6f7c-4992-9b54-f7d007a63773',NULL,'DOSSIE_EDITADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,NULL,'Assunto atualizado.',NULL,'2026-09-15 22:46:53'),(145,'17cc06f0-6f7c-4992-9b54-f7d007a63773','19eff16f-90eb-4977-af31-78867d29b712','MOVIMENTACAO_CONFIRMADA','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','EM_PREPARACAO','Evento 01','39f32b5722ee170bf876d31d33d98379687bf7838ec4037002b080951b1fdb4d','2026-09-15 22:46:53'),(146,'17cc06f0-6f7c-4992-9b54-f7d007a63773',NULL,'REGISTRO_ORGAO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','PROTOCOLADO','Processo Marinha: 23000.098765/2026-12',NULL,'2026-09-15 22:46:53'),(147,'17cc06f0-6f7c-4992-9b54-f7d007a63773','19eff16f-90eb-4977-af31-78867d29b712','ACEITE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'PENDENTE',NULL,NULL,'2026-09-15 22:46:53'),(148,'17cc06f0-6f7c-4992-9b54-f7d007a63773','19eff16f-90eb-4977-af31-78867d29b712','ORIGINAL_DEVOLVIDO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'DEVOLVIDO','Baixa de custódia',NULL,'2026-09-15 22:46:53'),(149,'17cc06f0-6f7c-4992-9b54-f7d007a63773',NULL,'DOSSIE_ENCERRADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','PROTOCOLADO','ENCERRADO',NULL,NULL,'2026-09-15 22:46:54'),(157,'ec13aed5-4bce-4a69-a587-9593b242d3e2',NULL,'DOSSIE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'EM_PREPARACAO','AM-PROT-25/26',NULL,'2026-09-15 23:07:03'),(158,'ec13aed5-4bce-4a69-a587-9593b242d3e2',NULL,'DOSSIE_EDITADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,NULL,'Assunto atualizado.',NULL,'2026-09-15 23:07:03'),(159,'ec13aed5-4bce-4a69-a587-9593b242d3e2','05ce7e74-66f4-489f-b571-cf9efecedb71','MOVIMENTACAO_CONFIRMADA','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','EM_PREPARACAO','Evento 01','a8359e7f782d6bef58eeed954b7920dc594ab470d9937f920eef2b4b49dbda30','2026-09-15 23:07:03'),(160,'ec13aed5-4bce-4a69-a587-9593b242d3e2',NULL,'REGISTRO_ORGAO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','PROTOCOLADO','Processo Marinha: 23000.098765/2026-12',NULL,'2026-09-15 23:07:03'),(161,'ec13aed5-4bce-4a69-a587-9593b242d3e2','05ce7e74-66f4-489f-b571-cf9efecedb71','ACEITE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'PENDENTE',NULL,NULL,'2026-09-15 23:07:03'),(162,'ec13aed5-4bce-4a69-a587-9593b242d3e2','05ce7e74-66f4-489f-b571-cf9efecedb71','ORIGINAL_DEVOLVIDO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'DEVOLVIDO','Baixa de custódia',NULL,'2026-09-15 23:07:03'),(163,'ec13aed5-4bce-4a69-a587-9593b242d3e2',NULL,'DOSSIE_ENCERRADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','PROTOCOLADO','ENCERRADO',NULL,NULL,'2026-09-15 23:07:03'),(171,'7877b5d7-6c57-45e9-8f13-26c676d57754',NULL,'DOSSIE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'EM_PREPARACAO','AM-PROT-27/26',NULL,'2026-09-15 23:08:07'),(172,'7877b5d7-6c57-45e9-8f13-26c676d57754',NULL,'DOSSIE_EDITADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,NULL,'Assunto atualizado.',NULL,'2026-09-15 23:08:07'),(173,'7877b5d7-6c57-45e9-8f13-26c676d57754','5cad0cfe-ddfd-4b24-a1ae-b79baddb4743','MOVIMENTACAO_CONFIRMADA','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','EM_PREPARACAO','Evento 01','c43d0e88bdaa471e83132cbb6297c60e73f02ec58e2c2f76ca281f682ce2783a','2026-09-15 23:08:07'),(174,'7877b5d7-6c57-45e9-8f13-26c676d57754',NULL,'REGISTRO_ORGAO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','PROTOCOLADO','Processo Marinha: 23000.098765/2026-12',NULL,'2026-09-15 23:08:07'),(175,'7877b5d7-6c57-45e9-8f13-26c676d57754','5cad0cfe-ddfd-4b24-a1ae-b79baddb4743','ACEITE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'PENDENTE',NULL,NULL,'2026-09-15 23:08:07'),(176,'7877b5d7-6c57-45e9-8f13-26c676d57754','5cad0cfe-ddfd-4b24-a1ae-b79baddb4743','ORIGINAL_DEVOLVIDO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'DEVOLVIDO','Baixa de custódia',NULL,'2026-09-15 23:08:07'),(177,'7877b5d7-6c57-45e9-8f13-26c676d57754',NULL,'DOSSIE_ENCERRADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','PROTOCOLADO','ENCERRADO',NULL,NULL,'2026-09-15 23:08:08'),(185,'beac9b2f-8636-4738-b6b2-2d8ce12a00c4',NULL,'DOSSIE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'EM_PREPARACAO','AM-PROT-29/26',NULL,'2026-09-16 00:19:04'),(186,'beac9b2f-8636-4738-b6b2-2d8ce12a00c4',NULL,'DOSSIE_EDITADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,NULL,'Assunto atualizado.',NULL,'2026-09-16 00:19:04'),(187,'beac9b2f-8636-4738-b6b2-2d8ce12a00c4','1c4b3e70-368c-4dcb-84e7-99de93e1391e','MOVIMENTACAO_CONFIRMADA','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','EM_PREPARACAO','Evento 01','ebf7f786903f70e0bcb120f832f2e5c39c0263672c6341b218746e8c232626dc','2026-09-16 00:19:04'),(188,'beac9b2f-8636-4738-b6b2-2d8ce12a00c4',NULL,'REGISTRO_ORGAO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','PROTOCOLADO','Processo Marinha: 23000.098765/2026-12',NULL,'2026-09-16 00:19:04'),(189,'beac9b2f-8636-4738-b6b2-2d8ce12a00c4','1c4b3e70-368c-4dcb-84e7-99de93e1391e','ACEITE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'PENDENTE',NULL,NULL,'2026-09-16 00:19:04'),(190,'beac9b2f-8636-4738-b6b2-2d8ce12a00c4','1c4b3e70-368c-4dcb-84e7-99de93e1391e','ORIGINAL_DEVOLVIDO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'DEVOLVIDO','Baixa de custódia',NULL,'2026-09-16 00:19:04'),(191,'beac9b2f-8636-4738-b6b2-2d8ce12a00c4',NULL,'DOSSIE_ENCERRADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','PROTOCOLADO','ENCERRADO',NULL,NULL,'2026-09-16 00:19:04'),(199,'4e32e18d-dda5-45c0-9c66-0bb1725c81d6',NULL,'DOSSIE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'EM_PREPARACAO','AM-PROT-31/26',NULL,'2026-09-16 00:23:54'),(200,'4e32e18d-dda5-45c0-9c66-0bb1725c81d6',NULL,'DOSSIE_EDITADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,NULL,'Assunto atualizado.',NULL,'2026-09-16 00:23:54'),(201,'4e32e18d-dda5-45c0-9c66-0bb1725c81d6','81ef628e-0452-463a-be5a-12a89f557e86','MOVIMENTACAO_CONFIRMADA','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','EM_PREPARACAO','Evento 01','7a780ecb85fb08041ca435c88422b0bcd49dc30ce59acc7a60243df27312ff10','2026-09-16 00:23:54'),(202,'4e32e18d-dda5-45c0-9c66-0bb1725c81d6',NULL,'REGISTRO_ORGAO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','PROTOCOLADO','Processo Marinha: 23000.098765/2026-12',NULL,'2026-09-16 00:23:54'),(203,'4e32e18d-dda5-45c0-9c66-0bb1725c81d6','81ef628e-0452-463a-be5a-12a89f557e86','ACEITE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'PENDENTE',NULL,NULL,'2026-09-16 00:23:54'),(204,'4e32e18d-dda5-45c0-9c66-0bb1725c81d6','81ef628e-0452-463a-be5a-12a89f557e86','ORIGINAL_DEVOLVIDO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'DEVOLVIDO','Baixa de custódia',NULL,'2026-09-16 00:23:54'),(205,'4e32e18d-dda5-45c0-9c66-0bb1725c81d6',NULL,'DOSSIE_ENCERRADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','PROTOCOLADO','ENCERRADO',NULL,NULL,'2026-09-16 00:23:55'),(216,'dc57db3d-c1b0-4242-9721-5d9b6e8198da',NULL,'DOSSIE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'EM_PREPARACAO','AM-PROT-33/26',NULL,'2026-09-16 00:32:47'),(217,'dc57db3d-c1b0-4242-9721-5d9b6e8198da',NULL,'DOSSIE_EDITADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,NULL,'Assunto atualizado.',NULL,'2026-09-16 00:32:47'),(218,'dc57db3d-c1b0-4242-9721-5d9b6e8198da','b7a3f0a0-9eb5-4442-aa83-d7484e1f0cbf','MOVIMENTACAO_CONFIRMADA','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','EM_PREPARACAO','Evento 01','95ce8283ea770c72bd1980a420fd3952ba8ff8aa5fcdb7d764a73bbe6729c950','2026-09-16 00:32:47'),(219,'dc57db3d-c1b0-4242-9721-5d9b6e8198da',NULL,'REGISTRO_ORGAO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','PROTOCOLADO','Processo Marinha: 23000.098765/2026-12',NULL,'2026-09-16 00:32:47'),(220,'dc57db3d-c1b0-4242-9721-5d9b6e8198da','b7a3f0a0-9eb5-4442-aa83-d7484e1f0cbf','ACEITE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'PENDENTE',NULL,NULL,'2026-09-16 00:32:47'),(221,'dc57db3d-c1b0-4242-9721-5d9b6e8198da','b7a3f0a0-9eb5-4442-aa83-d7484e1f0cbf','ORIGINAL_DEVOLVIDO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'DEVOLVIDO','Baixa de custódia',NULL,'2026-09-16 00:32:47'),(222,'dc57db3d-c1b0-4242-9721-5d9b6e8198da',NULL,'DOSSIE_ENCERRADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','PROTOCOLADO','ENCERRADO',NULL,NULL,'2026-09-16 00:32:47'),(231,'7254981c-d54a-489b-9018-3472299da5ee',NULL,'DOSSIE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'EM_PREPARACAO','AM-PROT-35/26',NULL,'2026-09-16 00:33:55'),(232,'7254981c-d54a-489b-9018-3472299da5ee',NULL,'DOSSIE_EDITADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,NULL,'Assunto atualizado.',NULL,'2026-09-16 00:33:55'),(233,'7254981c-d54a-489b-9018-3472299da5ee','978a98ef-972c-4034-8018-778982688712','MOVIMENTACAO_CONFIRMADA','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','EM_PREPARACAO','Evento 01','00a5d20aaab0489b3b248f340184478150d227ce14ef7ae8f4261e6595748401','2026-09-16 00:33:55'),(234,'7254981c-d54a-489b-9018-3472299da5ee',NULL,'REGISTRO_ORGAO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','PROTOCOLADO','Processo Marinha: 23000.098765/2026-12',NULL,'2026-09-16 00:33:55'),(235,'7254981c-d54a-489b-9018-3472299da5ee','978a98ef-972c-4034-8018-778982688712','ACEITE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'PENDENTE',NULL,NULL,'2026-09-16 00:33:55'),(236,'7254981c-d54a-489b-9018-3472299da5ee','978a98ef-972c-4034-8018-778982688712','ORIGINAL_DEVOLVIDO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'DEVOLVIDO','Baixa de custódia',NULL,'2026-09-16 00:33:55'),(237,'7254981c-d54a-489b-9018-3472299da5ee',NULL,'DOSSIE_ENCERRADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','PROTOCOLADO','ENCERRADO',NULL,NULL,'2026-09-16 00:33:55'),(247,'cd5190e0-8b56-4e02-8ea0-6c2ac8bbfe60',NULL,'DOSSIE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'EM_PREPARACAO','AM-PROT-37/26',NULL,'2026-09-16 00:34:53'),(248,'cd5190e0-8b56-4e02-8ea0-6c2ac8bbfe60',NULL,'DOSSIE_EDITADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,NULL,'Assunto atualizado.',NULL,'2026-09-16 00:34:53'),(249,'cd5190e0-8b56-4e02-8ea0-6c2ac8bbfe60','f9596bc8-fb67-410c-a70a-987a6329429f','MOVIMENTACAO_CONFIRMADA','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','EM_PREPARACAO','Evento 01','5346a3dda7f36e1aed35c782654e3861388995a01615def6dd0bea3ae94543da','2026-09-16 00:34:53'),(250,'cd5190e0-8b56-4e02-8ea0-6c2ac8bbfe60',NULL,'REGISTRO_ORGAO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','PROTOCOLADO','Processo Marinha: 23000.098765/2026-12',NULL,'2026-09-16 00:34:53'),(251,'cd5190e0-8b56-4e02-8ea0-6c2ac8bbfe60','f9596bc8-fb67-410c-a70a-987a6329429f','ACEITE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'PENDENTE',NULL,NULL,'2026-09-16 00:34:53'),(252,'cd5190e0-8b56-4e02-8ea0-6c2ac8bbfe60','f9596bc8-fb67-410c-a70a-987a6329429f','ORIGINAL_DEVOLVIDO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'DEVOLVIDO','Baixa de custódia',NULL,'2026-09-16 00:34:53'),(253,'cd5190e0-8b56-4e02-8ea0-6c2ac8bbfe60',NULL,'DOSSIE_ENCERRADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','PROTOCOLADO','ENCERRADO',NULL,NULL,'2026-09-16 00:34:54'),(269,'b48ba8be-fbe7-4e6e-98ce-21b19580c8eb',NULL,'DOSSIE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'EM_PREPARACAO','AM-PROT-40/26',NULL,'2026-09-16 00:36:10'),(270,'b48ba8be-fbe7-4e6e-98ce-21b19580c8eb',NULL,'DOSSIE_EDITADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,NULL,'Assunto atualizado.',NULL,'2026-09-16 00:36:10'),(271,'b48ba8be-fbe7-4e6e-98ce-21b19580c8eb','d545ae9f-6d55-4801-9a90-ea3e6f15fe89','MOVIMENTACAO_CONFIRMADA','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','EM_PREPARACAO','Evento 01','8a4f7b32fcb63b0404fa3a1efaa42b33e7df62eefdc8b6a63a325ec8ffef367c','2026-09-16 00:36:11'),(272,'b48ba8be-fbe7-4e6e-98ce-21b19580c8eb',NULL,'REGISTRO_ORGAO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','PROTOCOLADO','Processo Marinha: 23000.098765/2026-12',NULL,'2026-09-16 00:36:11'),(273,'b48ba8be-fbe7-4e6e-98ce-21b19580c8eb','d545ae9f-6d55-4801-9a90-ea3e6f15fe89','ACEITE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'PENDENTE',NULL,NULL,'2026-09-16 00:36:11'),(274,'b48ba8be-fbe7-4e6e-98ce-21b19580c8eb','d545ae9f-6d55-4801-9a90-ea3e6f15fe89','ORIGINAL_DEVOLVIDO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'DEVOLVIDO','Baixa de custódia',NULL,'2026-09-16 00:36:11'),(275,'b48ba8be-fbe7-4e6e-98ce-21b19580c8eb',NULL,'DOSSIE_ENCERRADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','PROTOCOLADO','ENCERRADO',NULL,NULL,'2026-09-16 00:36:11'),(284,'146d5728-e384-41cb-a011-934f2476ba04',NULL,'DOSSIE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'EM_PREPARACAO','AM-PROT-42/26',NULL,'2026-09-16 00:36:31'),(285,'146d5728-e384-41cb-a011-934f2476ba04',NULL,'DOSSIE_EDITADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,NULL,'Assunto atualizado.',NULL,'2026-09-16 00:36:31'),(286,'146d5728-e384-41cb-a011-934f2476ba04','7b4b9c41-daa2-4bee-b7c0-01fb69757990','MOVIMENTACAO_CONFIRMADA','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','EM_PREPARACAO','Evento 01','023126c6cbae89a61fda49a7561147b464aefd52367b2f7db6465c8604d78ad4','2026-09-16 00:36:31'),(287,'146d5728-e384-41cb-a011-934f2476ba04',NULL,'REGISTRO_ORGAO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','PROTOCOLADO','Processo Marinha: 23000.098765/2026-12',NULL,'2026-09-16 00:36:31'),(288,'146d5728-e384-41cb-a011-934f2476ba04','7b4b9c41-daa2-4bee-b7c0-01fb69757990','ACEITE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'PENDENTE',NULL,NULL,'2026-09-16 00:36:31'),(289,'146d5728-e384-41cb-a011-934f2476ba04','7b4b9c41-daa2-4bee-b7c0-01fb69757990','ORIGINAL_DEVOLVIDO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'DEVOLVIDO','Baixa de custódia',NULL,'2026-09-16 00:36:31'),(290,'146d5728-e384-41cb-a011-934f2476ba04',NULL,'DOSSIE_ENCERRADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','PROTOCOLADO','ENCERRADO',NULL,NULL,'2026-09-16 00:36:32'),(299,'2e3f61a9-8aa4-43b5-bcf1-dc3c07af4c6c',NULL,'DOSSIE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'EM_PREPARACAO','AM-PROT-44/26',NULL,'2026-09-16 00:48:01'),(300,'2e3f61a9-8aa4-43b5-bcf1-dc3c07af4c6c',NULL,'DOSSIE_EDITADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,NULL,'Assunto atualizado.',NULL,'2026-09-16 00:48:01'),(301,'2e3f61a9-8aa4-43b5-bcf1-dc3c07af4c6c','c34ae932-3485-4e89-b7e7-d5378b403320','MOVIMENTACAO_CONFIRMADA','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','EM_PREPARACAO','Evento 01','b479c2c5c7fe02dffaafad141a4081ed5b85e0342c2d47e713b71d63b2e85760','2026-09-16 00:48:01'),(302,'2e3f61a9-8aa4-43b5-bcf1-dc3c07af4c6c',NULL,'REGISTRO_ORGAO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','PROTOCOLADO','Processo Marinha: 23000.098765/2026-12',NULL,'2026-09-16 00:48:01'),(303,'2e3f61a9-8aa4-43b5-bcf1-dc3c07af4c6c','c34ae932-3485-4e89-b7e7-d5378b403320','ACEITE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'PENDENTE',NULL,NULL,'2026-09-16 00:48:01'),(304,'2e3f61a9-8aa4-43b5-bcf1-dc3c07af4c6c','c34ae932-3485-4e89-b7e7-d5378b403320','ORIGINAL_DEVOLVIDO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'DEVOLVIDO','Baixa de custódia',NULL,'2026-09-16 00:48:01'),(305,'2e3f61a9-8aa4-43b5-bcf1-dc3c07af4c6c',NULL,'DOSSIE_ENCERRADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','PROTOCOLADO','ENCERRADO',NULL,NULL,'2026-09-16 00:48:01'),(314,'9bc4c2a6-ab76-417a-9e5c-82b0fc049176',NULL,'DOSSIE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'EM_PREPARACAO','AM-PROT-46/26',NULL,'2026-09-16 01:04:40'),(315,'9bc4c2a6-ab76-417a-9e5c-82b0fc049176',NULL,'DOSSIE_EDITADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,NULL,'Assunto atualizado.',NULL,'2026-09-16 01:04:40'),(316,'9bc4c2a6-ab76-417a-9e5c-82b0fc049176','e8e965ea-0182-40d5-87aa-e2ef68326830','MOVIMENTACAO_CONFIRMADA','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','EM_PREPARACAO','Evento 01','341a55181e4270d529fdb14ba8b4d55b298804c7b531857f3272a4d11f686391','2026-09-16 01:04:40'),(317,'9bc4c2a6-ab76-417a-9e5c-82b0fc049176',NULL,'REGISTRO_ORGAO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','PROTOCOLADO','Processo Marinha: 23000.098765/2026-12',NULL,'2026-09-16 01:04:41'),(318,'9bc4c2a6-ab76-417a-9e5c-82b0fc049176','e8e965ea-0182-40d5-87aa-e2ef68326830','ACEITE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'PENDENTE',NULL,NULL,'2026-09-16 01:04:41'),(319,'9bc4c2a6-ab76-417a-9e5c-82b0fc049176','e8e965ea-0182-40d5-87aa-e2ef68326830','ORIGINAL_DEVOLVIDO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'DEVOLVIDO','Baixa de custódia',NULL,'2026-09-16 01:04:41'),(320,'9bc4c2a6-ab76-417a-9e5c-82b0fc049176',NULL,'DOSSIE_ENCERRADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','PROTOCOLADO','ENCERRADO',NULL,NULL,'2026-09-16 01:04:41'),(329,'1fd2ebbc-eca2-42ce-b74e-46ccd80c4119',NULL,'DOSSIE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'EM_PREPARACAO','AM-PROT-48/26',NULL,'2026-09-16 02:14:58'),(330,'1fd2ebbc-eca2-42ce-b74e-46ccd80c4119',NULL,'DOSSIE_EDITADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,NULL,'Assunto atualizado.',NULL,'2026-09-16 02:14:58'),(331,'1fd2ebbc-eca2-42ce-b74e-46ccd80c4119','0d2b6fe8-f1d9-4eda-b027-d4c2a7dc0f3c','MOVIMENTACAO_CONFIRMADA','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','EM_PREPARACAO','Evento 01','a9c29471841bc2ac03dc1ec55e0a1b45a66b48412f02b88e0cd788e5d0fda0b2','2026-09-16 02:14:58'),(332,'1fd2ebbc-eca2-42ce-b74e-46ccd80c4119',NULL,'REGISTRO_ORGAO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','PROTOCOLADO','Processo Marinha: 23000.098765/2026-12',NULL,'2026-09-16 02:14:58'),(333,'1fd2ebbc-eca2-42ce-b74e-46ccd80c4119','0d2b6fe8-f1d9-4eda-b027-d4c2a7dc0f3c','ACEITE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'PENDENTE',NULL,NULL,'2026-09-16 02:14:59'),(334,'1fd2ebbc-eca2-42ce-b74e-46ccd80c4119','0d2b6fe8-f1d9-4eda-b027-d4c2a7dc0f3c','ORIGINAL_DEVOLVIDO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'DEVOLVIDO','Baixa de custódia',NULL,'2026-09-16 02:14:59'),(335,'1fd2ebbc-eca2-42ce-b74e-46ccd80c4119',NULL,'DOSSIE_ENCERRADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','PROTOCOLADO','ENCERRADO',NULL,NULL,'2026-09-16 02:14:59'),(344,'e4f79ceb-4f3f-4c00-a4cc-6d508d99f1d3',NULL,'DOSSIE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'EM_PREPARACAO','AM-PROT-50/26',NULL,'2026-09-16 03:05:51'),(345,'e4f79ceb-4f3f-4c00-a4cc-6d508d99f1d3',NULL,'DOSSIE_EDITADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,NULL,'Assunto atualizado.',NULL,'2026-09-16 03:05:51'),(346,'e4f79ceb-4f3f-4c00-a4cc-6d508d99f1d3','27ede70e-7f03-48a8-9299-37e75029c309','MOVIMENTACAO_CONFIRMADA','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','EM_PREPARACAO','Evento 01','d155ffe58386d25711b0e185d947ddd65bb99884f4fde6d3952f4a172ab277e1','2026-09-16 03:05:51'),(347,'e4f79ceb-4f3f-4c00-a4cc-6d508d99f1d3',NULL,'REGISTRO_ORGAO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','PROTOCOLADO','Processo Marinha: 23000.098765/2026-12',NULL,'2026-09-16 03:05:52'),(348,'e4f79ceb-4f3f-4c00-a4cc-6d508d99f1d3','27ede70e-7f03-48a8-9299-37e75029c309','ACEITE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'PENDENTE',NULL,NULL,'2026-09-16 03:05:52'),(349,'e4f79ceb-4f3f-4c00-a4cc-6d508d99f1d3','27ede70e-7f03-48a8-9299-37e75029c309','ORIGINAL_DEVOLVIDO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'DEVOLVIDO','Baixa de custódia',NULL,'2026-09-16 03:05:52'),(350,'e4f79ceb-4f3f-4c00-a4cc-6d508d99f1d3',NULL,'DOSSIE_ENCERRADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','PROTOCOLADO','ENCERRADO',NULL,NULL,'2026-09-16 03:05:52'),(359,'5d2a77c7-851d-4c02-995b-aa6b264f33c3',NULL,'DOSSIE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'EM_PREPARACAO','AM-PROT-52/26',NULL,'2026-09-16 03:07:36'),(360,'5d2a77c7-851d-4c02-995b-aa6b264f33c3',NULL,'DOSSIE_EDITADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,NULL,'Assunto atualizado.',NULL,'2026-09-16 03:07:36'),(361,'5d2a77c7-851d-4c02-995b-aa6b264f33c3','e867a119-4166-490e-9d10-4042348659c3','MOVIMENTACAO_CONFIRMADA','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','EM_PREPARACAO','Evento 01','d811a637499de09e492d58bc0747e222d58f4f77d12a815eb4a54f4d36a795d8','2026-09-16 03:07:36'),(362,'5d2a77c7-851d-4c02-995b-aa6b264f33c3',NULL,'REGISTRO_ORGAO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','PROTOCOLADO','Processo Marinha: 23000.098765/2026-12',NULL,'2026-09-16 03:07:36'),(363,'5d2a77c7-851d-4c02-995b-aa6b264f33c3','e867a119-4166-490e-9d10-4042348659c3','ACEITE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'PENDENTE',NULL,NULL,'2026-09-16 03:07:36'),(364,'5d2a77c7-851d-4c02-995b-aa6b264f33c3','e867a119-4166-490e-9d10-4042348659c3','ORIGINAL_DEVOLVIDO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'DEVOLVIDO','Baixa de custódia',NULL,'2026-09-16 03:07:36'),(365,'5d2a77c7-851d-4c02-995b-aa6b264f33c3',NULL,'DOSSIE_ENCERRADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','PROTOCOLADO','ENCERRADO',NULL,NULL,'2026-09-16 03:07:36'),(374,'7519d5c2-d949-4fa7-ab79-ee94c5cde88f',NULL,'DOSSIE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'EM_PREPARACAO','AM-PROT-54/26',NULL,'2026-09-16 03:23:19'),(375,'7519d5c2-d949-4fa7-ab79-ee94c5cde88f',NULL,'DOSSIE_EDITADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,NULL,'Assunto atualizado.',NULL,'2026-09-16 03:23:19'),(376,'7519d5c2-d949-4fa7-ab79-ee94c5cde88f','c819a998-57ec-40e1-83e8-2fee5f50faa0','MOVIMENTACAO_CONFIRMADA','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','EM_PREPARACAO','Evento 01','c4e5f2d4aa3b1e296e5cd9a1ee7a3a48e7c752c372a1869cec305a98263e5240','2026-09-16 03:23:19'),(377,'7519d5c2-d949-4fa7-ab79-ee94c5cde88f',NULL,'REGISTRO_ORGAO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','PROTOCOLADO','Processo Marinha: 23000.098765/2026-12',NULL,'2026-09-16 03:23:19'),(378,'7519d5c2-d949-4fa7-ab79-ee94c5cde88f','c819a998-57ec-40e1-83e8-2fee5f50faa0','ACEITE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'PENDENTE',NULL,NULL,'2026-09-16 03:23:19'),(379,'7519d5c2-d949-4fa7-ab79-ee94c5cde88f','c819a998-57ec-40e1-83e8-2fee5f50faa0','ORIGINAL_DEVOLVIDO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'DEVOLVIDO','Baixa de custódia',NULL,'2026-09-16 03:23:19'),(380,'7519d5c2-d949-4fa7-ab79-ee94c5cde88f',NULL,'DOSSIE_ENCERRADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','PROTOCOLADO','ENCERRADO',NULL,NULL,'2026-09-16 03:23:20'),(389,'7950680a-8a22-4d97-8661-6c646c2e9fcf',NULL,'DOSSIE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'EM_PREPARACAO','AM-PROT-56/26',NULL,'2026-09-16 03:25:01'),(390,'7950680a-8a22-4d97-8661-6c646c2e9fcf',NULL,'DOSSIE_EDITADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,NULL,'Assunto atualizado.',NULL,'2026-09-16 03:25:01'),(391,'7950680a-8a22-4d97-8661-6c646c2e9fcf','dc90cc88-5fb6-41aa-9910-0b2f55faabe9','MOVIMENTACAO_CONFIRMADA','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','EM_PREPARACAO','Evento 01','6ad38da67d48109d392f998387cf39fd03cc02abe80923dfd3632dd35f344b07','2026-09-16 03:25:02'),(392,'7950680a-8a22-4d97-8661-6c646c2e9fcf',NULL,'REGISTRO_ORGAO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','PROTOCOLADO','Processo Marinha: 23000.098765/2026-12',NULL,'2026-09-16 03:25:02'),(393,'7950680a-8a22-4d97-8661-6c646c2e9fcf','dc90cc88-5fb6-41aa-9910-0b2f55faabe9','ACEITE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'PENDENTE',NULL,NULL,'2026-09-16 03:25:02'),(394,'7950680a-8a22-4d97-8661-6c646c2e9fcf','dc90cc88-5fb6-41aa-9910-0b2f55faabe9','ORIGINAL_DEVOLVIDO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'DEVOLVIDO','Baixa de custódia',NULL,'2026-09-16 03:25:02'),(395,'7950680a-8a22-4d97-8661-6c646c2e9fcf',NULL,'DOSSIE_ENCERRADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','PROTOCOLADO','ENCERRADO',NULL,NULL,'2026-09-16 03:25:02'),(397,'6a74a73a-cbda-4c42-9051-6d4be7f1bad1',NULL,'DOSSIE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'EM_PREPARACAO','AM-PROT-57/26',NULL,'2026-09-16 04:00:34'),(398,'6a74a73a-cbda-4c42-9051-6d4be7f1bad1',NULL,'DOSSIE_EDITADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,NULL,'Assunto atualizado.',NULL,'2026-09-16 04:00:34'),(399,'6a74a73a-cbda-4c42-9051-6d4be7f1bad1','8a866c5d-589f-443e-91a1-a38e96fc35c8','MOVIMENTACAO_CONFIRMADA','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','EM_PREPARACAO','Evento 01','81b32cc30d3084ae3ece2139cd4c4c0a1c84e721500a470a9c86c5a70af05035','2026-09-16 04:00:35'),(400,'6a74a73a-cbda-4c42-9051-6d4be7f1bad1',NULL,'REGISTRO_ORGAO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','PROTOCOLADO','Processo Marinha: 23000.098765/2026-12',NULL,'2026-09-16 04:00:35'),(401,'6a74a73a-cbda-4c42-9051-6d4be7f1bad1','8a866c5d-589f-443e-91a1-a38e96fc35c8','ACEITE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'PENDENTE',NULL,NULL,'2026-09-16 04:00:35'),(402,'6a74a73a-cbda-4c42-9051-6d4be7f1bad1','8a866c5d-589f-443e-91a1-a38e96fc35c8','ORIGINAL_DEVOLVIDO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'DEVOLVIDO','Baixa de custódia',NULL,'2026-09-16 04:00:35'),(403,'6a74a73a-cbda-4c42-9051-6d4be7f1bad1',NULL,'DOSSIE_ENCERRADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','PROTOCOLADO','ENCERRADO',NULL,NULL,'2026-09-16 04:00:35'),(411,'c5c2340a-0317-45d7-8105-69e67a23c7d9',NULL,'DOSSIE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'EM_PREPARACAO','AM-PROT-59/26',NULL,'2026-09-16 04:02:54'),(412,'c5c2340a-0317-45d7-8105-69e67a23c7d9',NULL,'DOSSIE_EDITADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,NULL,'Assunto atualizado.',NULL,'2026-09-16 04:02:54'),(413,'c5c2340a-0317-45d7-8105-69e67a23c7d9','fd700974-4406-4884-a894-3a3244642e02','MOVIMENTACAO_CONFIRMADA','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','EM_PREPARACAO','Evento 01','e52133d37e8562a8c8457d996da2b1db093c9f8abde20f45efb33167dbd8fd1a','2026-09-16 04:02:54'),(414,'c5c2340a-0317-45d7-8105-69e67a23c7d9',NULL,'REGISTRO_ORGAO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','EM_PREPARACAO','PROTOCOLADO','Processo Marinha: 23000.098765/2026-12',NULL,'2026-09-16 04:02:54'),(415,'c5c2340a-0317-45d7-8105-69e67a23c7d9','fd700974-4406-4884-a894-3a3244642e02','ACEITE_CRIADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'PENDENTE',NULL,NULL,'2026-09-16 04:02:54'),(416,'c5c2340a-0317-45d7-8105-69e67a23c7d9','fd700974-4406-4884-a894-3a3244642e02','ORIGINAL_DEVOLVIDO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0',NULL,'DEVOLVIDO','Baixa de custódia',NULL,'2026-09-16 04:02:54'),(417,'c5c2340a-0317-45d7-8105-69e67a23c7d9',NULL,'DOSSIE_ENCERRADO','dd121661-feb4-42f6-895a-68eb0608d1e4','ADMIN','0.0.0.0','PROTOCOLADO','ENCERRADO',NULL,NULL,'2026-09-16 04:02:54');
/*!40000 ALTER TABLE `protocolo_auditoria` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `protocolo_catalogo_documentos`
--

DROP TABLE IF EXISTS `protocolo_catalogo_documentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `protocolo_catalogo_documentos` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `categoria` enum('ANALISE_PLANOS','VISTORIA','INSCRICAO','CERTIFICADOS','PROPRIEDADE','RESPONSABILIDADE_TECNICA','DOCUMENTOS_PESSOAIS','OUTROS') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nome` varchar(180) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `contexto` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `norma_referencia` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
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
INSERT INTO `protocolo_catalogo_documentos` VALUES ('4f00eeaf-adf5-11f1-8a7c-be2fb1f77be2','REQ_INTERESSADO','ANALISE_PLANOS','Requerimento do interessado','NORMAM-202','NORMAM-202/DPC',1,10,'2026-09-11 15:27:32'),('4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2','ART','RESPONSABILIDADE_TECNICA','Anotação de Responsabilidade Técnica (ART)','NORMAM-202','NORMAM-202/DPC',1,20,'2026-09-11 15:27:32'),('4f00fbed-adf5-11f1-8a7c-be2fb1f77be2','MEMORIAL_DESCRITIVO','ANALISE_PLANOS','Memorial descritivo','LC_LCEC','NORMAM-202/DPC',1,30,'2026-09-11 15:27:32'),('4f00fca4-adf5-11f1-8a7c-be2fb1f77be2','PLANO_ARRANJO_GERAL','ANALISE_PLANOS','Plano de arranjo geral','LC_LCEC','NORMAM-202/DPC',1,40,'2026-09-11 15:27:32'),('4f010c17-adf5-11f1-8a7c-be2fb1f77be2','PLANO_LINHAS','ANALISE_PLANOS','Plano de linhas','LC_LCEC','NORMAM-202/DPC',1,50,'2026-09-11 15:27:32'),('4f010ca6-adf5-11f1-8a7c-be2fb1f77be2','PLANO_SEGURANCA','ANALISE_PLANOS','Plano de segurança','LC_LCEC','NORMAM-202/DPC',1,60,'2026-09-11 15:27:32'),('4f010cfd-adf5-11f1-8a7c-be2fb1f77be2','CALCULOS_ESTABILIDADE','ANALISE_PLANOS','Cálculos e folheto de estabilidade','LC_LCEC','NORMAM-202/DPC',1,70,'2026-09-11 15:27:32'),('4f010d46-adf5-11f1-8a7c-be2fb1f77be2','RELATORIO_VISTORIA','VISTORIA','Relatório técnico de vistoria','VISTORIA','NORMAM-202/DPC',1,80,'2026-09-11 15:27:32'),('4f0112b0-adf5-11f1-8a7c-be2fb1f77be2','CERTIFICADO_EXISTENTE','CERTIFICADOS','Certificado ou licença existente','DOCUMENTACAO',NULL,1,90,'2026-09-11 15:27:32'),('4f011372-adf5-11f1-8a7c-be2fb1f77be2','TIE_TIEM','INSCRICAO','TIE/TIEM ou documento de inscrição','INSCRICAO',NULL,1,100,'2026-09-11 15:27:32'),('4f0113be-adf5-11f1-8a7c-be2fb1f77be2','DOCUMENTO_PROPRIEDADE','PROPRIEDADE','Documento de propriedade da embarcação','PROPRIEDADE',NULL,1,110,'2026-09-11 15:27:32'),('4f011402-adf5-11f1-8a7c-be2fb1f77be2','DOCUMENTO_PESSOAL','DOCUMENTOS_PESSOAIS','Documento de identificação do interessado/representante','GERAL',NULL,1,120,'2026-09-11 15:27:32'),('4f01144c-adf5-11f1-8a7c-be2fb1f77be2','PROCURACAO','DOCUMENTOS_PESSOAIS','Procuração do representante','GERAL',NULL,1,130,'2026-09-11 15:27:32');
/*!40000 ALTER TABLE `protocolo_catalogo_documentos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `protocolo_comprovantes`
--

DROP TABLE IF EXISTS `protocolo_comprovantes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `protocolo_comprovantes` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `dossie_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `movimentacao_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tipo` enum('PROTOCOLO_EXTERNO','RECIBO','COMPROVANTE_ENTREGA','RASTREIO','OUTRO','DOCUMENTO') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nome_original` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `mime_type` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tamanho_bytes` bigint unsigned NOT NULL,
  `sha256` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `caminho` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `criado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
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
  `chave` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `valor` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `descricao` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `atualizado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
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
/*!40000 ALTER TABLE `protocolo_configuracoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `protocolo_dossies`
--

DROP TABLE IF EXISTS `protocolo_dossies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `protocolo_dossies` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `numero` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `embarcacao_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `cliente_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `assunto` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `servico_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `proposta_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `analise_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `vistoria_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `certificado_tipo` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `certificado_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('EM_PREPARACAO','ENVIADO_AO_ORGAO','PROTOCOLADO','EM_ANALISE_NO_ORGAO','EM_EXIGENCIA','A_DISPOSICAO','RETIRADO','ENTREGUE_AO_CLIENTE','ENCERRADO','CANCELADO') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'EM_PREPARACAO',
  `protocolo_externo_numero` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `protocolo_externo_em` datetime DEFAULT NULL,
  `protocolo_externo_validade` date DEFAULT NULL,
  `unidade_maritima_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cancelado_motivo` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cancelado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cancelado_em` datetime DEFAULT NULL,
  `criado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
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
INSERT INTO `protocolo_dossies` VALUES ('0e10393a-e8b8-4fd2-8b83-fee61b6a5cf0','AM-PROT-5/26','317ba743-7aa6-4d66-a845-2d4670f126f0','1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed','Workflow Test - LC NORMAM-202 Atualizado',NULL,NULL,NULL,NULL,NULL,NULL,'ENCERRADO','23000.098765/2026-12','2026-09-13 07:40:00','2026-12-12','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2',NULL,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-13 07:40:00','2026-09-13 07:40:00'),('146d5728-e384-41cb-a011-934f2476ba04','AM-PROT-42/26','317ba743-7aa6-4d66-a845-2d4670f126f0','1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed','Workflow Test - LC NORMAM-202 Atualizado',NULL,NULL,NULL,NULL,NULL,NULL,'ENCERRADO','23000.098765/2026-12','2026-09-16 00:36:31','2026-12-15','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2',NULL,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:36:31','2026-09-16 00:36:32'),('17cc06f0-6f7c-4992-9b54-f7d007a63773','AM-PROT-23/26','317ba743-7aa6-4d66-a845-2d4670f126f0','1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed','Workflow Test - LC NORMAM-202 Atualizado',NULL,NULL,NULL,NULL,NULL,NULL,'ENCERRADO','23000.098765/2026-12','2026-09-15 22:46:53','2026-12-14','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2',NULL,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-15 22:46:53','2026-09-15 22:46:54'),('1fd2ebbc-eca2-42ce-b74e-46ccd80c4119','AM-PROT-48/26','234aaec1-d1dd-4872-94b3-f52c61b04316',NULL,'Workflow Test - LC NORMAM-202 Atualizado',NULL,NULL,NULL,NULL,NULL,NULL,'ENCERRADO','23000.098765/2026-12','2026-09-16 02:14:58','2026-12-15','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2',NULL,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 02:14:58','2026-09-16 02:14:59'),('24997b9b-b4c2-45cc-ac84-62ce6c5fee9b','AM-PROT-9/26','317ba743-7aa6-4d66-a845-2d4670f126f0','1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed','Workflow Test - LC NORMAM-202 Atualizado',NULL,NULL,NULL,NULL,NULL,NULL,'ENCERRADO','23000.098765/2026-12','2026-09-15 19:14:52','2026-12-14','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2',NULL,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-15 19:14:52','2026-09-15 19:14:53'),('2e3f61a9-8aa4-43b5-bcf1-dc3c07af4c6c','AM-PROT-44/26','234aaec1-d1dd-4872-94b3-f52c61b04316',NULL,'Workflow Test - LC NORMAM-202 Atualizado',NULL,NULL,NULL,NULL,NULL,NULL,'ENCERRADO','23000.098765/2026-12','2026-09-16 00:48:01','2026-12-15','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2',NULL,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:48:01','2026-09-16 00:48:01'),('4b1b2cb7-71e7-472a-ab95-d70c773c72c0','AM-PROT-3/26','317ba743-7aa6-4d66-a845-2d4670f126f0','1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed','Workflow Test - LC NORMAM-202 Atualizado',NULL,NULL,NULL,NULL,NULL,NULL,'ENCERRADO','23000.098765/2026-12','2026-09-13 06:44:42','2026-12-12','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2',NULL,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-13 06:44:41','2026-09-13 06:44:42'),('4e32e18d-dda5-45c0-9c66-0bb1725c81d6','AM-PROT-31/26','317ba743-7aa6-4d66-a845-2d4670f126f0','1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed','Workflow Test - LC NORMAM-202 Atualizado',NULL,NULL,NULL,NULL,NULL,NULL,'ENCERRADO','23000.098765/2026-12','2026-09-16 00:23:54','2026-12-15','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2',NULL,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:23:54','2026-09-16 00:23:55'),('513cfd70-6874-4ec6-a899-26622a6d524e','AM-PROT-10/26','317ba743-7aa6-4d66-a845-2d4670f126f0','1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed','Workflow Test - LC NORMAM-202 Atualizado',NULL,NULL,NULL,NULL,NULL,NULL,'ENCERRADO','23000.098765/2026-12','2026-09-15 19:47:41','2026-12-14','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2',NULL,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-15 19:47:40','2026-09-15 19:47:41'),('5c2f5179-2235-42ab-b85e-ddfff32a4f79','AM-PROT-1/26','317ba743-7aa6-4d66-a845-2d4670f126f0','1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed','Workflow Test - LC NORMAM-202 Atualizado',NULL,NULL,NULL,NULL,NULL,NULL,'ENCERRADO','23000.098765/2026-12','2026-09-13 06:28:55','2026-12-12','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2',NULL,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-13 06:28:55','2026-09-13 06:28:56'),('5d2a77c7-851d-4c02-995b-aa6b264f33c3','AM-PROT-52/26','234aaec1-d1dd-4872-94b3-f52c61b04316',NULL,'Workflow Test - LC NORMAM-202 Atualizado',NULL,NULL,NULL,NULL,NULL,NULL,'ENCERRADO','23000.098765/2026-12','2026-09-16 03:07:36','2026-12-15','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2',NULL,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 03:07:36','2026-09-16 03:07:36'),('6943e349-8e7e-4ed8-9724-93d94fe0e8f6','AM-PROT-2/26','317ba743-7aa6-4d66-a845-2d4670f126f0','1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed','Workflow Test - LC NORMAM-202 Atualizado',NULL,NULL,NULL,NULL,NULL,NULL,'ENCERRADO','23000.098765/2026-12','2026-09-13 06:31:56','2026-12-12','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2',NULL,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-13 06:31:56','2026-09-13 06:31:57'),('6a74a73a-cbda-4c42-9051-6d4be7f1bad1','AM-PROT-57/26','234aaec1-d1dd-4872-94b3-f52c61b04316',NULL,'Workflow Test - LC NORMAM-202 Atualizado',NULL,NULL,NULL,NULL,NULL,NULL,'ENCERRADO','23000.098765/2026-12','2026-09-16 04:00:35','2026-12-15','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2',NULL,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 04:00:34','2026-09-16 04:00:35'),('7254981c-d54a-489b-9018-3472299da5ee','AM-PROT-35/26','317ba743-7aa6-4d66-a845-2d4670f126f0','1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed','Workflow Test - LC NORMAM-202 Atualizado',NULL,NULL,NULL,NULL,NULL,NULL,'ENCERRADO','23000.098765/2026-12','2026-09-16 00:33:55','2026-12-15','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2',NULL,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:33:55','2026-09-16 00:33:55'),('7519d5c2-d949-4fa7-ab79-ee94c5cde88f','AM-PROT-54/26','234aaec1-d1dd-4872-94b3-f52c61b04316',NULL,'Workflow Test - LC NORMAM-202 Atualizado',NULL,NULL,NULL,NULL,NULL,NULL,'ENCERRADO','23000.098765/2026-12','2026-09-16 03:23:19','2026-12-15','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2',NULL,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 03:23:19','2026-09-16 03:23:20'),('7877b5d7-6c57-45e9-8f13-26c676d57754','AM-PROT-27/26','317ba743-7aa6-4d66-a845-2d4670f126f0','1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed','Workflow Test - LC NORMAM-202 Atualizado',NULL,NULL,NULL,NULL,NULL,NULL,'ENCERRADO','23000.098765/2026-12','2026-09-15 23:08:07','2026-12-14','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2',NULL,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-15 23:08:07','2026-09-15 23:08:08'),('7950680a-8a22-4d97-8661-6c646c2e9fcf','AM-PROT-56/26','234aaec1-d1dd-4872-94b3-f52c61b04316',NULL,'Workflow Test - LC NORMAM-202 Atualizado',NULL,NULL,NULL,NULL,NULL,NULL,'ENCERRADO','23000.098765/2026-12','2026-09-16 03:25:02','2026-12-15','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2',NULL,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 03:25:01','2026-09-16 03:25:02'),('84d585b3-cbd9-4a4f-952b-462e5b2e2274','AM-PROT-7/26','317ba743-7aa6-4d66-a845-2d4670f126f0','1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed','Workflow Test - LC NORMAM-202 Atualizado',NULL,NULL,NULL,NULL,NULL,NULL,'ENCERRADO','23000.098765/2026-12','2026-09-15 18:14:45','2026-12-14','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2',NULL,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-15 18:14:44','2026-09-15 18:14:45'),('8cf5fec7-6a72-4a8d-906f-a9b21895bd30','AM-PROT-8/26','317ba743-7aa6-4d66-a845-2d4670f126f0','1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed','Workflow Test - LC NORMAM-202 Atualizado',NULL,NULL,NULL,NULL,NULL,NULL,'ENCERRADO','23000.098765/2026-12','2026-09-15 18:52:04','2026-12-14','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2',NULL,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-15 18:52:04','2026-09-15 18:52:05'),('9469057c-b57a-4d42-bd6f-9eecad1382ed','AM-PROT-2026-0001','317ba743-7aa6-4d66-a845-2d4670f126f0',NULL,'Regularização Inicial NORMAM-202',NULL,NULL,NULL,NULL,NULL,NULL,'EM_PREPARACAO',NULL,NULL,NULL,'4f01ef52-adf5-11f1-8a7c-be2fb1f77be2',NULL,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-11 15:29:25','2026-09-11 15:29:25'),('9bc4c2a6-ab76-417a-9e5c-82b0fc049176','AM-PROT-46/26','234aaec1-d1dd-4872-94b3-f52c61b04316',NULL,'Workflow Test - LC NORMAM-202 Atualizado',NULL,NULL,NULL,NULL,NULL,NULL,'ENCERRADO','23000.098765/2026-12','2026-09-16 01:04:40','2026-12-15','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2',NULL,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 01:04:40','2026-09-16 01:04:41'),('b48ba8be-fbe7-4e6e-98ce-21b19580c8eb','AM-PROT-40/26','317ba743-7aa6-4d66-a845-2d4670f126f0','1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed','Workflow Test - LC NORMAM-202 Atualizado',NULL,NULL,NULL,NULL,NULL,NULL,'ENCERRADO','23000.098765/2026-12','2026-09-16 00:36:11','2026-12-15','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2',NULL,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:36:10','2026-09-16 00:36:11'),('bcab5c48-6a49-4a42-88c8-a287eda78876','AM-PROT-4/26','317ba743-7aa6-4d66-a845-2d4670f126f0','1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed','Workflow Test - LC NORMAM-202 Atualizado',NULL,NULL,NULL,NULL,NULL,NULL,'ENCERRADO','23000.098765/2026-12','2026-09-13 07:23:07','2026-12-12','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2',NULL,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-13 07:23:07','2026-09-13 07:23:07'),('beac9b2f-8636-4738-b6b2-2d8ce12a00c4','AM-PROT-29/26','317ba743-7aa6-4d66-a845-2d4670f126f0','1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed','Workflow Test - LC NORMAM-202 Atualizado',NULL,NULL,NULL,NULL,NULL,NULL,'ENCERRADO','23000.098765/2026-12','2026-09-16 00:19:04','2026-12-15','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2',NULL,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:19:04','2026-09-16 00:19:04'),('c5c2340a-0317-45d7-8105-69e67a23c7d9','AM-PROT-59/26','234aaec1-d1dd-4872-94b3-f52c61b04316',NULL,'Workflow Test - LC NORMAM-202 Atualizado',NULL,NULL,NULL,NULL,NULL,NULL,'ENCERRADO','23000.098765/2026-12','2026-09-16 04:02:54','2026-12-15','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2',NULL,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 04:02:54','2026-09-16 04:02:54'),('caaf28b9-6221-4fc9-ac79-7f11d30423e6','AM-PROT-6/26','317ba743-7aa6-4d66-a845-2d4670f126f0','1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed','Workflow Test - LC NORMAM-202 Atualizado',NULL,NULL,NULL,NULL,NULL,NULL,'ENCERRADO','23000.098765/2026-12','2026-09-15 18:03:19','2026-12-14','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2',NULL,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-15 18:03:19','2026-09-15 18:03:19'),('cd5190e0-8b56-4e02-8ea0-6c2ac8bbfe60','AM-PROT-37/26','317ba743-7aa6-4d66-a845-2d4670f126f0','1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed','Workflow Test - LC NORMAM-202 Atualizado',NULL,NULL,NULL,NULL,NULL,NULL,'ENCERRADO','23000.098765/2026-12','2026-09-16 00:34:53','2026-12-15','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2',NULL,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:34:53','2026-09-16 00:34:54'),('db4bef24-d50b-43b7-a980-61eb3fe58af0','AM-PROT-12/26','317ba743-7aa6-4d66-a845-2d4670f126f0','1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed','Workflow Test - LC NORMAM-202 Atualizado',NULL,NULL,NULL,NULL,NULL,NULL,'ENCERRADO','23000.098765/2026-12','2026-09-15 20:20:34','2026-12-14','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2',NULL,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-15 20:20:34','2026-09-15 20:20:35'),('dc57db3d-c1b0-4242-9721-5d9b6e8198da','AM-PROT-33/26','317ba743-7aa6-4d66-a845-2d4670f126f0','1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed','Workflow Test - LC NORMAM-202 Atualizado',NULL,NULL,NULL,NULL,NULL,NULL,'ENCERRADO','23000.098765/2026-12','2026-09-16 00:32:47','2026-12-15','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2',NULL,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:32:47','2026-09-16 00:32:47'),('e4f79ceb-4f3f-4c00-a4cc-6d508d99f1d3','AM-PROT-50/26','234aaec1-d1dd-4872-94b3-f52c61b04316',NULL,'Workflow Test - LC NORMAM-202 Atualizado',NULL,NULL,NULL,NULL,NULL,NULL,'ENCERRADO','23000.098765/2026-12','2026-09-16 03:05:52','2026-12-15','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2',NULL,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 03:05:51','2026-09-16 03:05:52'),('ec13aed5-4bce-4a69-a587-9593b242d3e2','AM-PROT-25/26','317ba743-7aa6-4d66-a845-2d4670f126f0','1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed','Workflow Test - LC NORMAM-202 Atualizado',NULL,NULL,NULL,NULL,NULL,NULL,'ENCERRADO','23000.098765/2026-12','2026-09-15 23:07:03','2026-12-14','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2',NULL,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-15 23:07:03','2026-09-15 23:07:03'),('f2b06336-c0dc-4f21-9372-c9d8c0b164b9','AM-PROT-11/26','317ba743-7aa6-4d66-a845-2d4670f126f0','1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed','Workflow Test - LC NORMAM-202 Atualizado',NULL,NULL,NULL,NULL,NULL,NULL,'ENCERRADO','23000.098765/2026-12','2026-09-15 19:48:35','2026-12-14','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2',NULL,NULL,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-15 19:48:35','2026-09-15 19:48:36');
/*!40000 ALTER TABLE `protocolo_dossies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `protocolo_movimentacao_itens`
--

DROP TABLE IF EXISTS `protocolo_movimentacao_itens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `protocolo_movimentacao_itens` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `movimentacao_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `catalogo_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `descricao` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `categoria` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `suporte` enum('FISICO','DIGITAL') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `forma` enum('ORIGINAL','COPIA_SIMPLES','COPIA_AUTENTICADA','NATO_DIGITAL','DIGITALIZADO') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `quantidade` int unsigned NOT NULL DEFAULT '1',
  `numero_revisao` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `data_documento` date DEFAULT NULL,
  `condicao_documento` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `requer_devolucao` tinyint(1) NOT NULL DEFAULT '0',
  `devolvido_em` datetime DEFAULT NULL,
  `arquivo_origem_tipo` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `arquivo_origem_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `arquivo_nome` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `arquivo_hash` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `observacao` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
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
INSERT INTO `protocolo_movimentacao_itens` VALUES ('02b4c201-aacf-47c4-a446-12783fca046c','ca21f805-0db1-4a3f-bcd9-96881787ae31',NULL,'Escritura Pública de Compra e Venda (Original)','PROPRIEDADE','FISICO','ORIGINAL',1,NULL,NULL,'Via original com selo',1,'2026-09-13 07:40:00',NULL,NULL,NULL,NULL,NULL,'2026-09-13 07:40:00'),('09b125ec-224b-4da3-a42b-0452f4009ff4','c24df4b1-b953-4f98-b19b-b71d12e6c692','4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2','Anotação de Responsabilidade Técnica (ART)','RESPONSABILIDADE_TECNICA','DIGITAL','NATO_DIGITAL',1,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-13 07:23:07'),('0d403b73-edb0-45cd-a578-e75a1b389a05','5cad0cfe-ddfd-4b24-a1ae-b79baddb4743','4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2','Anotação de Responsabilidade Técnica (ART)','RESPONSABILIDADE_TECNICA','DIGITAL','NATO_DIGITAL',1,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-15 23:08:07'),('0e31d0c5-48eb-44c7-a3a0-73050f230394','0d2b6fe8-f1d9-4eda-b027-d4c2a7dc0f3c',NULL,'Escritura Pública de Compra e Venda (Original)','PROPRIEDADE','FISICO','ORIGINAL',1,NULL,NULL,'Via original com selo',1,'2026-09-16 02:14:59',NULL,NULL,NULL,NULL,NULL,'2026-09-16 02:14:58'),('13ced9d0-dc43-4b95-bd7d-61eaf47d98ab','17650e69-7a84-417a-986b-21b5cfbb06bd','4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2','Anotação de Responsabilidade Técnica (ART)','RESPONSABILIDADE_TECNICA','DIGITAL','NATO_DIGITAL',1,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-13 06:31:56'),('152f564d-a5eb-4e97-b215-68873eeac381','eb15509f-4348-404a-a867-f504ec1f0309','4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2','Anotação de Responsabilidade Técnica (ART)','RESPONSABILIDADE_TECNICA','DIGITAL','NATO_DIGITAL',1,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-13 06:28:55'),('27af9f2f-b034-4a20-8ff5-73916cdd7e00','bef51f8d-70da-4b12-ba87-08fd57195507',NULL,'Escritura Pública de Compra e Venda (Original)','PROPRIEDADE','FISICO','ORIGINAL',1,NULL,NULL,'Via original com selo',1,'2026-09-15 20:20:34',NULL,NULL,NULL,NULL,NULL,'2026-09-15 20:20:34'),('32f5b708-91bd-4bec-ac5b-c201c0f6ec77','3eda28e8-76eb-402c-9e6b-9acc495494b0','4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2','Anotação de Responsabilidade Técnica (ART)','RESPONSABILIDADE_TECNICA','DIGITAL','NATO_DIGITAL',1,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-15 18:14:44'),('37a7ee1d-417c-4fd7-aeac-6cb1c3ea5e2b','b3c49df0-1ad3-4bca-9840-9249fb849d26',NULL,'Escritura Pública de Compra e Venda (Original)','PROPRIEDADE','FISICO','ORIGINAL',1,NULL,NULL,'Via original com selo',1,'2026-09-13 06:44:42',NULL,NULL,NULL,NULL,NULL,'2026-09-13 06:44:41'),('3ba72f54-f43b-4102-ac21-963734514675','27ede70e-7f03-48a8-9299-37e75029c309','4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2','Anotação de Responsabilidade Técnica (ART)','RESPONSABILIDADE_TECNICA','DIGITAL','NATO_DIGITAL',1,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-16 03:05:51'),('3c9c794f-a47f-415e-98ba-0f4b1a13b531','978a98ef-972c-4034-8018-778982688712','4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2','Anotação de Responsabilidade Técnica (ART)','RESPONSABILIDADE_TECNICA','DIGITAL','NATO_DIGITAL',1,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-16 00:33:55'),('3f0bdb52-b6b9-43b1-8bd1-93653cf42085','c819a998-57ec-40e1-83e8-2fee5f50faa0',NULL,'Escritura Pública de Compra e Venda (Original)','PROPRIEDADE','FISICO','ORIGINAL',1,NULL,NULL,'Via original com selo',1,'2026-09-16 03:23:19',NULL,NULL,NULL,NULL,NULL,'2026-09-16 03:23:19'),('44611f21-6cbb-4e07-89eb-3fdcbfa3f0f4','7b4b9c41-daa2-4bee-b7c0-01fb69757990',NULL,'Escritura Pública de Compra e Venda (Original)','PROPRIEDADE','FISICO','ORIGINAL',1,NULL,NULL,'Via original com selo',1,'2026-09-16 00:36:31',NULL,NULL,NULL,NULL,NULL,'2026-09-16 00:36:31'),('4e69514a-f105-4b4e-a098-646cbdbf1ee7','bef51f8d-70da-4b12-ba87-08fd57195507','4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2','Anotação de Responsabilidade Técnica (ART)','RESPONSABILIDADE_TECNICA','DIGITAL','NATO_DIGITAL',1,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-15 20:20:34'),('547ca338-4265-4896-a614-388c82fff524','5cad0cfe-ddfd-4b24-a1ae-b79baddb4743',NULL,'Escritura Pública de Compra e Venda (Original)','PROPRIEDADE','FISICO','ORIGINAL',1,NULL,NULL,'Via original com selo',1,'2026-09-15 23:08:07',NULL,NULL,NULL,NULL,NULL,'2026-09-15 23:08:07'),('549b6b02-ca9f-4b63-8752-5b2c48781206','81ef628e-0452-463a-be5a-12a89f557e86','4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2','Anotação de Responsabilidade Técnica (ART)','RESPONSABILIDADE_TECNICA','DIGITAL','NATO_DIGITAL',1,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-16 00:23:54'),('5664ed2f-594f-4c0b-885e-b6d31d7d9df6','dc90cc88-5fb6-41aa-9910-0b2f55faabe9',NULL,'Escritura Pública de Compra e Venda (Original)','PROPRIEDADE','FISICO','ORIGINAL',1,NULL,NULL,'Via original com selo',1,'2026-09-16 03:25:02',NULL,NULL,NULL,NULL,NULL,'2026-09-16 03:25:01'),('6262d3b5-5062-49da-8ff5-38ebefe7671c','4db389b4-9b3d-4a8f-91d0-c85f5d05a25a','4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2','Anotação de Responsabilidade Técnica (ART)','RESPONSABILIDADE_TECNICA','DIGITAL','NATO_DIGITAL',1,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-15 19:48:35'),('6294bee6-75ce-4430-9206-464c70574c11','19eff16f-90eb-4977-af31-78867d29b712','4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2','Anotação de Responsabilidade Técnica (ART)','RESPONSABILIDADE_TECNICA','DIGITAL','NATO_DIGITAL',1,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-15 22:46:53'),('629dc14c-3a41-402f-a1f8-e41c21d33b80','aee3ea4e-d71b-4e1b-bf22-203ec3ea7db3',NULL,'Escritura Pública de Compra e Venda (Original)','PROPRIEDADE','FISICO','ORIGINAL',1,NULL,NULL,'Via original com selo',1,'2026-09-15 18:52:04',NULL,NULL,NULL,NULL,NULL,'2026-09-15 18:52:04'),('62dcb611-8648-4d37-afad-961428dc9609','978a98ef-972c-4034-8018-778982688712',NULL,'Escritura Pública de Compra e Venda (Original)','PROPRIEDADE','FISICO','ORIGINAL',1,NULL,NULL,'Via original com selo',1,'2026-09-16 00:33:55',NULL,NULL,NULL,NULL,NULL,'2026-09-16 00:33:55'),('6bb83fab-48c9-46ad-93c4-6799e65bde40','a2483f83-da37-4bb4-b41c-0596c02f9832',NULL,'Escritura Pública de Compra e Venda (Original)','PROPRIEDADE','FISICO','ORIGINAL',1,NULL,NULL,'Via original com selo',1,'2026-09-15 19:14:52',NULL,NULL,NULL,NULL,NULL,'2026-09-15 19:14:52'),('71c99373-9df3-4490-a7d8-ba8385dfa213','ca21f805-0db1-4a3f-bcd9-96881787ae31','4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2','Anotação de Responsabilidade Técnica (ART)','RESPONSABILIDADE_TECNICA','DIGITAL','NATO_DIGITAL',1,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-13 07:40:00'),('7575aca0-041b-45d8-8c7f-a66e0615327e','1c4b3e70-368c-4dcb-84e7-99de93e1391e','4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2','Anotação de Responsabilidade Técnica (ART)','RESPONSABILIDADE_TECNICA','DIGITAL','NATO_DIGITAL',1,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-16 00:19:04'),('757e5427-90a8-4961-b8e2-d3331aba27e8','d545ae9f-6d55-4801-9a90-ea3e6f15fe89','4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2','Anotação de Responsabilidade Técnica (ART)','RESPONSABILIDADE_TECNICA','DIGITAL','NATO_DIGITAL',1,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-16 00:36:10'),('770fce67-8529-4901-bf9b-c6b31bd0d5fa','fd700974-4406-4884-a894-3a3244642e02','4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2','Anotação de Responsabilidade Técnica (ART)','RESPONSABILIDADE_TECNICA','DIGITAL','NATO_DIGITAL',1,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-16 04:02:54'),('7a70e60b-1b1a-40af-9b27-2ad7876b08bb','1c4b3e70-368c-4dcb-84e7-99de93e1391e',NULL,'Escritura Pública de Compra e Venda (Original)','PROPRIEDADE','FISICO','ORIGINAL',1,NULL,NULL,'Via original com selo',1,'2026-09-16 00:19:04',NULL,NULL,NULL,NULL,NULL,'2026-09-16 00:19:04'),('7bc4751f-be60-4f5a-9b31-b41ce2a37aea','d545ae9f-6d55-4801-9a90-ea3e6f15fe89',NULL,'Escritura Pública de Compra e Venda (Original)','PROPRIEDADE','FISICO','ORIGINAL',1,NULL,NULL,'Via original com selo',1,'2026-09-16 00:36:11',NULL,NULL,NULL,NULL,NULL,'2026-09-16 00:36:10'),('7cfcc0ea-b05d-4438-9426-bbd645a81ace','7b4b9c41-daa2-4bee-b7c0-01fb69757990','4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2','Anotação de Responsabilidade Técnica (ART)','RESPONSABILIDADE_TECNICA','DIGITAL','NATO_DIGITAL',1,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-16 00:36:31'),('7e722fd3-9384-4ba1-be60-4d772a4131ed','e867a119-4166-490e-9d10-4042348659c3',NULL,'Escritura Pública de Compra e Venda (Original)','PROPRIEDADE','FISICO','ORIGINAL',1,NULL,NULL,'Via original com selo',1,'2026-09-16 03:07:36',NULL,NULL,NULL,NULL,NULL,'2026-09-16 03:07:36'),('7f1ebb69-4501-48d2-a1ac-4d7f0447fb97','3eda28e8-76eb-402c-9e6b-9acc495494b0',NULL,'Escritura Pública de Compra e Venda (Original)','PROPRIEDADE','FISICO','ORIGINAL',1,NULL,NULL,'Via original com selo',1,'2026-09-15 18:14:45',NULL,NULL,NULL,NULL,NULL,'2026-09-15 18:14:44'),('81fc1197-19eb-4bfe-8e3f-4f81026a66f7','c24df4b1-b953-4f98-b19b-b71d12e6c692',NULL,'Escritura Pública de Compra e Venda (Original)','PROPRIEDADE','FISICO','ORIGINAL',1,NULL,NULL,'Via original com selo',1,'2026-09-13 07:23:07',NULL,NULL,NULL,NULL,NULL,'2026-09-13 07:23:07'),('8560a371-8261-4124-9aae-ed6551852d33','e8e965ea-0182-40d5-87aa-e2ef68326830','4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2','Anotação de Responsabilidade Técnica (ART)','RESPONSABILIDADE_TECNICA','DIGITAL','NATO_DIGITAL',1,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-16 01:04:40'),('8595dd7c-daef-4896-9068-fcfba73a25c2','8a866c5d-589f-443e-91a1-a38e96fc35c8','4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2','Anotação de Responsabilidade Técnica (ART)','RESPONSABILIDADE_TECNICA','DIGITAL','NATO_DIGITAL',1,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-16 04:00:34'),('89a6523c-59c2-4c7d-91de-741e4826496f','27ede70e-7f03-48a8-9299-37e75029c309',NULL,'Escritura Pública de Compra e Venda (Original)','PROPRIEDADE','FISICO','ORIGINAL',1,NULL,NULL,'Via original com selo',1,'2026-09-16 03:05:52',NULL,NULL,NULL,NULL,NULL,'2026-09-16 03:05:51'),('89cc0f8f-4eac-44e7-8aca-29762391d9a3','81ef628e-0452-463a-be5a-12a89f557e86',NULL,'Escritura Pública de Compra e Venda (Original)','PROPRIEDADE','FISICO','ORIGINAL',1,NULL,NULL,'Via original com selo',1,'2026-09-16 00:23:54',NULL,NULL,NULL,NULL,NULL,'2026-09-16 00:23:54'),('8fbc5e25-b94a-464f-8e74-a9e868dd94cc','eb15509f-4348-404a-a867-f504ec1f0309',NULL,'Escritura Pública de Compra e Venda (Original)','PROPRIEDADE','FISICO','ORIGINAL',1,NULL,NULL,'Via original com selo',1,'2026-09-13 06:28:55',NULL,NULL,NULL,NULL,NULL,'2026-09-13 06:28:55'),('987656f9-5de8-4437-bad8-70a8d9c305f8','d5dc879a-5373-4bdf-8833-44f96a62b9c7','4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2','Anotação de Responsabilidade Técnica (ART)','RESPONSABILIDADE_TECNICA','DIGITAL','NATO_DIGITAL',1,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-15 18:03:19'),('a181f6e1-3914-4ca3-b902-cf0e8ef15b56','c819a998-57ec-40e1-83e8-2fee5f50faa0','4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2','Anotação de Responsabilidade Técnica (ART)','RESPONSABILIDADE_TECNICA','DIGITAL','NATO_DIGITAL',1,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-16 03:23:19'),('a1c5ea19-74c7-420f-90b5-28bc3d55f3e3','05ce7e74-66f4-489f-b571-cf9efecedb71',NULL,'Escritura Pública de Compra e Venda (Original)','PROPRIEDADE','FISICO','ORIGINAL',1,NULL,NULL,'Via original com selo',1,'2026-09-15 23:07:03',NULL,NULL,NULL,NULL,NULL,'2026-09-15 23:07:03'),('a3c7ec66-da64-4fae-a07a-199ec623343a','dc90cc88-5fb6-41aa-9910-0b2f55faabe9','4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2','Anotação de Responsabilidade Técnica (ART)','RESPONSABILIDADE_TECNICA','DIGITAL','NATO_DIGITAL',1,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-16 03:25:01'),('a527276d-4505-45e1-9360-c31baa3e974e','19eff16f-90eb-4977-af31-78867d29b712',NULL,'Escritura Pública de Compra e Venda (Original)','PROPRIEDADE','FISICO','ORIGINAL',1,NULL,NULL,'Via original com selo',1,'2026-09-15 22:46:53',NULL,NULL,NULL,NULL,NULL,'2026-09-15 22:46:53'),('b1a2e5cf-3261-4609-abb8-f8e7fb30e166','2c05e7eb-c76a-4ab8-a865-73a3487673ed',NULL,'Escritura Pública de Compra e Venda (Original)','PROPRIEDADE','FISICO','ORIGINAL',1,NULL,NULL,'Via original com selo',1,'2026-09-15 19:47:41',NULL,NULL,NULL,NULL,NULL,'2026-09-15 19:47:40'),('b27f8452-9a79-424a-95ac-ab85123d1e89','d5dc879a-5373-4bdf-8833-44f96a62b9c7',NULL,'Escritura Pública de Compra e Venda (Original)','PROPRIEDADE','FISICO','ORIGINAL',1,NULL,NULL,'Via original com selo',1,'2026-09-15 18:03:19',NULL,NULL,NULL,NULL,NULL,'2026-09-15 18:03:19'),('b546b375-87f5-4ea9-bfd1-e1d5d5e84237','a2483f83-da37-4bb4-b41c-0596c02f9832','4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2','Anotação de Responsabilidade Técnica (ART)','RESPONSABILIDADE_TECNICA','DIGITAL','NATO_DIGITAL',1,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-15 19:14:52'),('c08d0023-374c-407f-8385-4803dcc9c769','aee3ea4e-d71b-4e1b-bf22-203ec3ea7db3','4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2','Anotação de Responsabilidade Técnica (ART)','RESPONSABILIDADE_TECNICA','DIGITAL','NATO_DIGITAL',1,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-15 18:52:04'),('c4050f96-365b-4b9f-b646-fcde7aaa86f7','e8e965ea-0182-40d5-87aa-e2ef68326830',NULL,'Escritura Pública de Compra e Venda (Original)','PROPRIEDADE','FISICO','ORIGINAL',1,NULL,NULL,'Via original com selo',1,'2026-09-16 01:04:41',NULL,NULL,NULL,NULL,NULL,'2026-09-16 01:04:40'),('c74529f6-0c02-46bf-aa38-c5c56be0330a','17650e69-7a84-417a-986b-21b5cfbb06bd',NULL,'Escritura Pública de Compra e Venda (Original)','PROPRIEDADE','FISICO','ORIGINAL',1,NULL,NULL,'Via original com selo',1,'2026-09-13 06:31:56',NULL,NULL,NULL,NULL,NULL,'2026-09-13 06:31:56'),('cd88632f-4ba0-48bf-960b-9d36eb3745ea','8a866c5d-589f-443e-91a1-a38e96fc35c8',NULL,'Escritura Pública de Compra e Venda (Original)','PROPRIEDADE','FISICO','ORIGINAL',1,NULL,NULL,'Via original com selo',1,'2026-09-16 04:00:35',NULL,NULL,NULL,NULL,NULL,'2026-09-16 04:00:34'),('cf976703-0724-4e78-bc73-dfe5df6d0657','f9596bc8-fb67-410c-a70a-987a6329429f','4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2','Anotação de Responsabilidade Técnica (ART)','RESPONSABILIDADE_TECNICA','DIGITAL','NATO_DIGITAL',1,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-16 00:34:53'),('d227f980-65c1-4fb3-a767-e3ed9e5b13f9','f9596bc8-fb67-410c-a70a-987a6329429f',NULL,'Escritura Pública de Compra e Venda (Original)','PROPRIEDADE','FISICO','ORIGINAL',1,NULL,NULL,'Via original com selo',1,'2026-09-16 00:34:53',NULL,NULL,NULL,NULL,NULL,'2026-09-16 00:34:53'),('d2723e48-0af2-42c7-a62c-987055618701','4db389b4-9b3d-4a8f-91d0-c85f5d05a25a',NULL,'Escritura Pública de Compra e Venda (Original)','PROPRIEDADE','FISICO','ORIGINAL',1,NULL,NULL,'Via original com selo',1,'2026-09-15 19:48:35',NULL,NULL,NULL,NULL,NULL,'2026-09-15 19:48:35'),('d8e66b9a-cdd6-47c8-a659-f094d30fb17a','05ce7e74-66f4-489f-b571-cf9efecedb71','4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2','Anotação de Responsabilidade Técnica (ART)','RESPONSABILIDADE_TECNICA','DIGITAL','NATO_DIGITAL',1,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-15 23:07:03'),('dcdb17c4-97b4-4519-bbe1-4f82afdeb6ec','0d2b6fe8-f1d9-4eda-b027-d4c2a7dc0f3c','4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2','Anotação de Responsabilidade Técnica (ART)','RESPONSABILIDADE_TECNICA','DIGITAL','NATO_DIGITAL',1,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-16 02:14:58'),('e0cc2e4b-c165-42fd-850b-29b58c7bb8e7','c34ae932-3485-4e89-b7e7-d5378b403320',NULL,'Escritura Pública de Compra e Venda (Original)','PROPRIEDADE','FISICO','ORIGINAL',1,NULL,NULL,'Via original com selo',1,'2026-09-16 00:48:01',NULL,NULL,NULL,NULL,NULL,'2026-09-16 00:48:01'),('e61a1f43-1eba-4ddc-a0cc-08bef53e7123','b7a3f0a0-9eb5-4442-aa83-d7484e1f0cbf',NULL,'Escritura Pública de Compra e Venda (Original)','PROPRIEDADE','FISICO','ORIGINAL',1,NULL,NULL,'Via original com selo',1,'2026-09-16 00:32:47',NULL,NULL,NULL,NULL,NULL,'2026-09-16 00:32:47'),('eeed2bc3-20c4-4808-8709-6fa456f36d14','fd700974-4406-4884-a894-3a3244642e02',NULL,'Escritura Pública de Compra e Venda (Original)','PROPRIEDADE','FISICO','ORIGINAL',1,NULL,NULL,'Via original com selo',1,'2026-09-16 04:02:54',NULL,NULL,NULL,NULL,NULL,'2026-09-16 04:02:54'),('f43d21de-0cf3-4175-a5a6-c639a9391e23','b3c49df0-1ad3-4bca-9840-9249fb849d26','4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2','Anotação de Responsabilidade Técnica (ART)','RESPONSABILIDADE_TECNICA','DIGITAL','NATO_DIGITAL',1,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-13 06:44:41'),('fb98047a-6322-43b1-86fd-1766fd411565','e867a119-4166-490e-9d10-4042348659c3','4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2','Anotação de Responsabilidade Técnica (ART)','RESPONSABILIDADE_TECNICA','DIGITAL','NATO_DIGITAL',1,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-16 03:07:36'),('fc1b4c43-36d4-47f0-9f8e-d9f3331aebfa','c34ae932-3485-4e89-b7e7-d5378b403320','4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2','Anotação de Responsabilidade Técnica (ART)','RESPONSABILIDADE_TECNICA','DIGITAL','NATO_DIGITAL',1,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-16 00:48:01'),('fd7dd49f-2769-4d06-9199-8a8e12d1482d','2c05e7eb-c76a-4ab8-a865-73a3487673ed','4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2','Anotação de Responsabilidade Técnica (ART)','RESPONSABILIDADE_TECNICA','DIGITAL','NATO_DIGITAL',1,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-15 19:47:40'),('ffb1ebd9-4a20-49a2-948a-319e0ffd48b6','b7a3f0a0-9eb5-4442-aa83-d7484e1f0cbf','4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2','Anotação de Responsabilidade Técnica (ART)','RESPONSABILIDADE_TECNICA','DIGITAL','NATO_DIGITAL',1,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-16 00:32:47');
/*!40000 ALTER TABLE `protocolo_movimentacao_itens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `protocolo_movimentacoes`
--

DROP TABLE IF EXISTS `protocolo_movimentacoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `protocolo_movimentacoes` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `dossie_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `sequencia` int unsigned NOT NULL,
  `tipo` enum('ENTRADA','SAIDA') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `natureza` enum('RECEBIMENTO_CLIENTE','ENVIO_ORGAO','RETORNO_ORGAO','CUMPRIMENTO_EXIGENCIA','RETIRADA_ORGAO','ENTREGA_CLIENTE','TRANSFERENCIA_INTERNA','OUTRA') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('RASCUNHO','CONFIRMADA','RETIFICADA','CANCELADA') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'RASCUNHO',
  `origem_tipo` enum('CLIENTE','REPRESENTANTE','AMAZON_NAVAL','CAPITANIA','DELEGACIA','AGENCIA','CORREIOS','TRANSPORTADORA','OUTRO') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `origem_nome` varchar(180) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `destino_tipo` enum('CLIENTE','REPRESENTANTE','AMAZON_NAVAL','CAPITANIA','DELEGACIA','AGENCIA','CORREIOS','TRANSPORTADORA','OUTRO') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `destino_nome` varchar(180) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `unidade_maritima_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cidade` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `uf` char(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `meio_envio` enum('PRESENCIAL','EMAIL','PORTAL','CORREIOS','TRANSPORTADORA','MENSAGEIRO','OUTRO') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `portador_nome` varchar(180) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `codigo_rastreio` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `movimentado_em` datetime NOT NULL,
  `observacoes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `retifica_movimentacao_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `protocolo_anterior_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `idempotency_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `snapshot_json` json DEFAULT NULL,
  `pdf_caminho` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pdf_hash` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `confirmado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `confirmado_em` datetime DEFAULT NULL,
  `criado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
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
INSERT INTO `protocolo_movimentacoes` VALUES ('05ce7e74-66f4-489f-b571-cf9efecedb71','ec13aed5-4bce-4a69-a587-9593b242d3e2',1,'ENTRADA','RECEBIMENTO_CLIENTE','CONFIRMADA','CLIENTE','Armador Teste','AMAZON_NAVAL','Amazon Naval','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2','Belém','PA','PRESENCIAL',NULL,NULL,'2026-09-15 23:07:03',NULL,NULL,NULL,'1fb6e139173687c0744727444d0f1fe9','[{\"id\": \"a1c5ea19-74c7-420f-90b5-28bc3d55f3e3\", \"forma\": \"ORIGINAL\", \"suporte\": \"FISICO\", \"categoria\": \"PROPRIEDADE\", \"criado_em\": \"2026-09-15 23:07:03\", \"descricao\": \"Escritura Pública de Compra e Venda (Original)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": null, \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"05ce7e74-66f4-489f-b571-cf9efecedb71\", \"requer_devolucao\": 1, \"arquivo_origem_id\": null, \"condicao_documento\": \"Via original com selo\", \"arquivo_origem_tipo\": null}, {\"id\": \"d8e66b9a-cdd6-47c8-a659-f094d30fb17a\", \"forma\": \"NATO_DIGITAL\", \"suporte\": \"DIGITAL\", \"categoria\": \"RESPONSABILIDADE_TECNICA\", \"criado_em\": \"2026-09-15 23:07:03\", \"descricao\": \"Anotação de Responsabilidade Técnica (ART)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": \"4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2\", \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"05ce7e74-66f4-489f-b571-cf9efecedb71\", \"requer_devolucao\": 0, \"arquivo_origem_id\": null, \"condicao_documento\": null, \"arquivo_origem_tipo\": null}]','storage/protocolos/2026/ec13aed5-4bce-4a69-a587-9593b242d3e2/evento-01.pdf','a8359e7f782d6bef58eeed954b7920dc594ab470d9937f920eef2b4b49dbda30','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-15 23:07:03','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-15 23:07:03','2026-09-15 23:07:03'),('0d2b6fe8-f1d9-4eda-b027-d4c2a7dc0f3c','1fd2ebbc-eca2-42ce-b74e-46ccd80c4119',1,'ENTRADA','RECEBIMENTO_CLIENTE','CONFIRMADA','CLIENTE','Armador Teste','AMAZON_NAVAL','Amazon Naval','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2','Belém','PA','PRESENCIAL',NULL,NULL,'2026-09-16 02:14:58',NULL,NULL,NULL,'5bbb506c4fa32a8233e70ba159ce3d4c','[{\"id\": \"0e31d0c5-48eb-44c7-a3a0-73050f230394\", \"forma\": \"ORIGINAL\", \"suporte\": \"FISICO\", \"categoria\": \"PROPRIEDADE\", \"criado_em\": \"2026-09-16 02:14:58\", \"descricao\": \"Escritura Pública de Compra e Venda (Original)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": null, \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"0d2b6fe8-f1d9-4eda-b027-d4c2a7dc0f3c\", \"requer_devolucao\": 1, \"arquivo_origem_id\": null, \"condicao_documento\": \"Via original com selo\", \"arquivo_origem_tipo\": null}, {\"id\": \"dcdb17c4-97b4-4519-bbe1-4f82afdeb6ec\", \"forma\": \"NATO_DIGITAL\", \"suporte\": \"DIGITAL\", \"categoria\": \"RESPONSABILIDADE_TECNICA\", \"criado_em\": \"2026-09-16 02:14:58\", \"descricao\": \"Anotação de Responsabilidade Técnica (ART)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": \"4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2\", \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"0d2b6fe8-f1d9-4eda-b027-d4c2a7dc0f3c\", \"requer_devolucao\": 0, \"arquivo_origem_id\": null, \"condicao_documento\": null, \"arquivo_origem_tipo\": null}]','storage/protocolos/2026/1fd2ebbc-eca2-42ce-b74e-46ccd80c4119/evento-01.pdf','a9c29471841bc2ac03dc1ec55e0a1b45a66b48412f02b88e0cd788e5d0fda0b2','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 02:14:58','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 02:14:58','2026-09-16 02:14:58'),('17650e69-7a84-417a-986b-21b5cfbb06bd','6943e349-8e7e-4ed8-9724-93d94fe0e8f6',1,'ENTRADA','RECEBIMENTO_CLIENTE','CONFIRMADA','CLIENTE','Armador Teste','AMAZON_NAVAL','Amazon Naval','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2','Belém','PA','PRESENCIAL',NULL,NULL,'2026-09-13 06:31:56',NULL,NULL,NULL,'908210ab855094caad2b3cb7b7113c40','[{\"id\": \"13ced9d0-dc43-4b95-bd7d-61eaf47d98ab\", \"forma\": \"NATO_DIGITAL\", \"suporte\": \"DIGITAL\", \"categoria\": \"RESPONSABILIDADE_TECNICA\", \"criado_em\": \"2026-09-13 06:31:56\", \"descricao\": \"Anotação de Responsabilidade Técnica (ART)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": \"4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2\", \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"17650e69-7a84-417a-986b-21b5cfbb06bd\", \"requer_devolucao\": 0, \"arquivo_origem_id\": null, \"condicao_documento\": null, \"arquivo_origem_tipo\": null}, {\"id\": \"c74529f6-0c02-46bf-aa38-c5c56be0330a\", \"forma\": \"ORIGINAL\", \"suporte\": \"FISICO\", \"categoria\": \"PROPRIEDADE\", \"criado_em\": \"2026-09-13 06:31:56\", \"descricao\": \"Escritura Pública de Compra e Venda (Original)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": null, \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"17650e69-7a84-417a-986b-21b5cfbb06bd\", \"requer_devolucao\": 1, \"arquivo_origem_id\": null, \"condicao_documento\": \"Via original com selo\", \"arquivo_origem_tipo\": null}]','storage/protocolos/2026/6943e349-8e7e-4ed8-9724-93d94fe0e8f6/evento-01.pdf','39385e12cf6dc69c982540ce9dafeaba784bde0e4f47a4bdfde91e9a1ca21f18','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-13 06:31:56','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-13 06:31:56','2026-09-13 06:31:56'),('19eff16f-90eb-4977-af31-78867d29b712','17cc06f0-6f7c-4992-9b54-f7d007a63773',1,'ENTRADA','RECEBIMENTO_CLIENTE','CONFIRMADA','CLIENTE','Armador Teste','AMAZON_NAVAL','Amazon Naval','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2','Belém','PA','PRESENCIAL',NULL,NULL,'2026-09-15 22:46:53',NULL,NULL,NULL,'e9ecd26102100d32b80f18d8bcdb9508','[{\"id\": \"6294bee6-75ce-4430-9206-464c70574c11\", \"forma\": \"NATO_DIGITAL\", \"suporte\": \"DIGITAL\", \"categoria\": \"RESPONSABILIDADE_TECNICA\", \"criado_em\": \"2026-09-15 22:46:53\", \"descricao\": \"Anotação de Responsabilidade Técnica (ART)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": \"4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2\", \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"19eff16f-90eb-4977-af31-78867d29b712\", \"requer_devolucao\": 0, \"arquivo_origem_id\": null, \"condicao_documento\": null, \"arquivo_origem_tipo\": null}, {\"id\": \"a527276d-4505-45e1-9360-c31baa3e974e\", \"forma\": \"ORIGINAL\", \"suporte\": \"FISICO\", \"categoria\": \"PROPRIEDADE\", \"criado_em\": \"2026-09-15 22:46:53\", \"descricao\": \"Escritura Pública de Compra e Venda (Original)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": null, \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"19eff16f-90eb-4977-af31-78867d29b712\", \"requer_devolucao\": 1, \"arquivo_origem_id\": null, \"condicao_documento\": \"Via original com selo\", \"arquivo_origem_tipo\": null}]','storage/protocolos/2026/17cc06f0-6f7c-4992-9b54-f7d007a63773/evento-01.pdf','39f32b5722ee170bf876d31d33d98379687bf7838ec4037002b080951b1fdb4d','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-15 22:46:53','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-15 22:46:53','2026-09-15 22:46:53'),('1c4b3e70-368c-4dcb-84e7-99de93e1391e','beac9b2f-8636-4738-b6b2-2d8ce12a00c4',1,'ENTRADA','RECEBIMENTO_CLIENTE','CONFIRMADA','CLIENTE','Armador Teste','AMAZON_NAVAL','Amazon Naval','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2','Belém','PA','PRESENCIAL',NULL,NULL,'2026-09-16 00:19:04',NULL,NULL,NULL,'a5208a3f84a9c510fdfe721c32c463bd','[{\"id\": \"7575aca0-041b-45d8-8c7f-a66e0615327e\", \"forma\": \"NATO_DIGITAL\", \"suporte\": \"DIGITAL\", \"categoria\": \"RESPONSABILIDADE_TECNICA\", \"criado_em\": \"2026-09-16 00:19:04\", \"descricao\": \"Anotação de Responsabilidade Técnica (ART)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": \"4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2\", \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"1c4b3e70-368c-4dcb-84e7-99de93e1391e\", \"requer_devolucao\": 0, \"arquivo_origem_id\": null, \"condicao_documento\": null, \"arquivo_origem_tipo\": null}, {\"id\": \"7a70e60b-1b1a-40af-9b27-2ad7876b08bb\", \"forma\": \"ORIGINAL\", \"suporte\": \"FISICO\", \"categoria\": \"PROPRIEDADE\", \"criado_em\": \"2026-09-16 00:19:04\", \"descricao\": \"Escritura Pública de Compra e Venda (Original)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": null, \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"1c4b3e70-368c-4dcb-84e7-99de93e1391e\", \"requer_devolucao\": 1, \"arquivo_origem_id\": null, \"condicao_documento\": \"Via original com selo\", \"arquivo_origem_tipo\": null}]','storage/protocolos/2026/beac9b2f-8636-4738-b6b2-2d8ce12a00c4/evento-01.pdf','ebf7f786903f70e0bcb120f832f2e5c39c0263672c6341b218746e8c232626dc','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:19:04','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:19:04','2026-09-16 00:19:04'),('27ede70e-7f03-48a8-9299-37e75029c309','e4f79ceb-4f3f-4c00-a4cc-6d508d99f1d3',1,'ENTRADA','RECEBIMENTO_CLIENTE','CONFIRMADA','CLIENTE','Armador Teste','AMAZON_NAVAL','Amazon Naval','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2','Belém','PA','PRESENCIAL',NULL,NULL,'2026-09-16 03:05:51',NULL,NULL,NULL,'a6ab020239326a164cf3b160e37838de','[{\"id\": \"3ba72f54-f43b-4102-ac21-963734514675\", \"forma\": \"NATO_DIGITAL\", \"suporte\": \"DIGITAL\", \"categoria\": \"RESPONSABILIDADE_TECNICA\", \"criado_em\": \"2026-09-16 03:05:51\", \"descricao\": \"Anotação de Responsabilidade Técnica (ART)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": \"4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2\", \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"27ede70e-7f03-48a8-9299-37e75029c309\", \"requer_devolucao\": 0, \"arquivo_origem_id\": null, \"condicao_documento\": null, \"arquivo_origem_tipo\": null}, {\"id\": \"89a6523c-59c2-4c7d-91de-741e4826496f\", \"forma\": \"ORIGINAL\", \"suporte\": \"FISICO\", \"categoria\": \"PROPRIEDADE\", \"criado_em\": \"2026-09-16 03:05:51\", \"descricao\": \"Escritura Pública de Compra e Venda (Original)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": null, \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"27ede70e-7f03-48a8-9299-37e75029c309\", \"requer_devolucao\": 1, \"arquivo_origem_id\": null, \"condicao_documento\": \"Via original com selo\", \"arquivo_origem_tipo\": null}]','storage/protocolos/2026/e4f79ceb-4f3f-4c00-a4cc-6d508d99f1d3/evento-01.pdf','d155ffe58386d25711b0e185d947ddd65bb99884f4fde6d3952f4a172ab277e1','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 03:05:51','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 03:05:51','2026-09-16 03:05:51'),('2c05e7eb-c76a-4ab8-a865-73a3487673ed','513cfd70-6874-4ec6-a899-26622a6d524e',1,'ENTRADA','RECEBIMENTO_CLIENTE','CONFIRMADA','CLIENTE','Armador Teste','AMAZON_NAVAL','Amazon Naval','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2','Belém','PA','PRESENCIAL',NULL,NULL,'2026-09-15 19:47:40',NULL,NULL,NULL,'6377c746a7c426b2015582348410748b','[{\"id\": \"b1a2e5cf-3261-4609-abb8-f8e7fb30e166\", \"forma\": \"ORIGINAL\", \"suporte\": \"FISICO\", \"categoria\": \"PROPRIEDADE\", \"criado_em\": \"2026-09-15 19:47:40\", \"descricao\": \"Escritura Pública de Compra e Venda (Original)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": null, \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"2c05e7eb-c76a-4ab8-a865-73a3487673ed\", \"requer_devolucao\": 1, \"arquivo_origem_id\": null, \"condicao_documento\": \"Via original com selo\", \"arquivo_origem_tipo\": null}, {\"id\": \"fd7dd49f-2769-4d06-9199-8a8e12d1482d\", \"forma\": \"NATO_DIGITAL\", \"suporte\": \"DIGITAL\", \"categoria\": \"RESPONSABILIDADE_TECNICA\", \"criado_em\": \"2026-09-15 19:47:40\", \"descricao\": \"Anotação de Responsabilidade Técnica (ART)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": \"4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2\", \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"2c05e7eb-c76a-4ab8-a865-73a3487673ed\", \"requer_devolucao\": 0, \"arquivo_origem_id\": null, \"condicao_documento\": null, \"arquivo_origem_tipo\": null}]','storage/protocolos/2026/513cfd70-6874-4ec6-a899-26622a6d524e/evento-01.pdf','84335858f5d626d483208f71d02db5a96e6eacb927b1cce19fce242195695210','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-15 19:47:41','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-15 19:47:40','2026-09-15 19:47:41'),('3eda28e8-76eb-402c-9e6b-9acc495494b0','84d585b3-cbd9-4a4f-952b-462e5b2e2274',1,'ENTRADA','RECEBIMENTO_CLIENTE','CONFIRMADA','CLIENTE','Armador Teste','AMAZON_NAVAL','Amazon Naval','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2','Belém','PA','PRESENCIAL',NULL,NULL,'2026-09-15 18:14:44',NULL,NULL,NULL,'b5b93638ebfacda5ffcecef57a52c734','[{\"id\": \"32f5b708-91bd-4bec-ac5b-c201c0f6ec77\", \"forma\": \"NATO_DIGITAL\", \"suporte\": \"DIGITAL\", \"categoria\": \"RESPONSABILIDADE_TECNICA\", \"criado_em\": \"2026-09-15 18:14:44\", \"descricao\": \"Anotação de Responsabilidade Técnica (ART)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": \"4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2\", \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"3eda28e8-76eb-402c-9e6b-9acc495494b0\", \"requer_devolucao\": 0, \"arquivo_origem_id\": null, \"condicao_documento\": null, \"arquivo_origem_tipo\": null}, {\"id\": \"7f1ebb69-4501-48d2-a1ac-4d7f0447fb97\", \"forma\": \"ORIGINAL\", \"suporte\": \"FISICO\", \"categoria\": \"PROPRIEDADE\", \"criado_em\": \"2026-09-15 18:14:44\", \"descricao\": \"Escritura Pública de Compra e Venda (Original)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": null, \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"3eda28e8-76eb-402c-9e6b-9acc495494b0\", \"requer_devolucao\": 1, \"arquivo_origem_id\": null, \"condicao_documento\": \"Via original com selo\", \"arquivo_origem_tipo\": null}]','storage/protocolos/2026/84d585b3-cbd9-4a4f-952b-462e5b2e2274/evento-01.pdf','ba3bdcafb174d972f0aedfefd5150c09c9e37c425f316f5e52bc2307c966f07d','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-15 18:14:45','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-15 18:14:44','2026-09-15 18:14:45'),('4db389b4-9b3d-4a8f-91d0-c85f5d05a25a','f2b06336-c0dc-4f21-9372-c9d8c0b164b9',1,'ENTRADA','RECEBIMENTO_CLIENTE','CONFIRMADA','CLIENTE','Armador Teste','AMAZON_NAVAL','Amazon Naval','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2','Belém','PA','PRESENCIAL',NULL,NULL,'2026-09-15 19:48:35',NULL,NULL,NULL,'d07593eeeadb43a3b84f3d70f0a3db7f','[{\"id\": \"6262d3b5-5062-49da-8ff5-38ebefe7671c\", \"forma\": \"NATO_DIGITAL\", \"suporte\": \"DIGITAL\", \"categoria\": \"RESPONSABILIDADE_TECNICA\", \"criado_em\": \"2026-09-15 19:48:35\", \"descricao\": \"Anotação de Responsabilidade Técnica (ART)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": \"4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2\", \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"4db389b4-9b3d-4a8f-91d0-c85f5d05a25a\", \"requer_devolucao\": 0, \"arquivo_origem_id\": null, \"condicao_documento\": null, \"arquivo_origem_tipo\": null}, {\"id\": \"d2723e48-0af2-42c7-a62c-987055618701\", \"forma\": \"ORIGINAL\", \"suporte\": \"FISICO\", \"categoria\": \"PROPRIEDADE\", \"criado_em\": \"2026-09-15 19:48:35\", \"descricao\": \"Escritura Pública de Compra e Venda (Original)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": null, \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"4db389b4-9b3d-4a8f-91d0-c85f5d05a25a\", \"requer_devolucao\": 1, \"arquivo_origem_id\": null, \"condicao_documento\": \"Via original com selo\", \"arquivo_origem_tipo\": null}]','storage/protocolos/2026/f2b06336-c0dc-4f21-9372-c9d8c0b164b9/evento-01.pdf','779acbe7a13201803e117b9d47dcc752c6c5fcb6e073e9db21d2bf01e2af39ba','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-15 19:48:35','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-15 19:48:35','2026-09-15 19:48:35'),('5cad0cfe-ddfd-4b24-a1ae-b79baddb4743','7877b5d7-6c57-45e9-8f13-26c676d57754',1,'ENTRADA','RECEBIMENTO_CLIENTE','CONFIRMADA','CLIENTE','Armador Teste','AMAZON_NAVAL','Amazon Naval','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2','Belém','PA','PRESENCIAL',NULL,NULL,'2026-09-15 23:08:07',NULL,NULL,NULL,'10da416ef6059ae45dd33806adb7ce49','[{\"id\": \"0d403b73-edb0-45cd-a578-e75a1b389a05\", \"forma\": \"NATO_DIGITAL\", \"suporte\": \"DIGITAL\", \"categoria\": \"RESPONSABILIDADE_TECNICA\", \"criado_em\": \"2026-09-15 23:08:07\", \"descricao\": \"Anotação de Responsabilidade Técnica (ART)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": \"4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2\", \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"5cad0cfe-ddfd-4b24-a1ae-b79baddb4743\", \"requer_devolucao\": 0, \"arquivo_origem_id\": null, \"condicao_documento\": null, \"arquivo_origem_tipo\": null}, {\"id\": \"547ca338-4265-4896-a614-388c82fff524\", \"forma\": \"ORIGINAL\", \"suporte\": \"FISICO\", \"categoria\": \"PROPRIEDADE\", \"criado_em\": \"2026-09-15 23:08:07\", \"descricao\": \"Escritura Pública de Compra e Venda (Original)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": null, \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"5cad0cfe-ddfd-4b24-a1ae-b79baddb4743\", \"requer_devolucao\": 1, \"arquivo_origem_id\": null, \"condicao_documento\": \"Via original com selo\", \"arquivo_origem_tipo\": null}]','storage/protocolos/2026/7877b5d7-6c57-45e9-8f13-26c676d57754/evento-01.pdf','c43d0e88bdaa471e83132cbb6297c60e73f02ec58e2c2f76ca281f682ce2783a','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-15 23:08:07','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-15 23:08:07','2026-09-15 23:08:07'),('7b4b9c41-daa2-4bee-b7c0-01fb69757990','146d5728-e384-41cb-a011-934f2476ba04',1,'ENTRADA','RECEBIMENTO_CLIENTE','CONFIRMADA','CLIENTE','Armador Teste','AMAZON_NAVAL','Amazon Naval','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2','Belém','PA','PRESENCIAL',NULL,NULL,'2026-09-16 00:36:31',NULL,NULL,NULL,'7e117e332def577e305b1ca98bb4881b','[{\"id\": \"44611f21-6cbb-4e07-89eb-3fdcbfa3f0f4\", \"forma\": \"ORIGINAL\", \"suporte\": \"FISICO\", \"categoria\": \"PROPRIEDADE\", \"criado_em\": \"2026-09-16 00:36:31\", \"descricao\": \"Escritura Pública de Compra e Venda (Original)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": null, \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"7b4b9c41-daa2-4bee-b7c0-01fb69757990\", \"requer_devolucao\": 1, \"arquivo_origem_id\": null, \"condicao_documento\": \"Via original com selo\", \"arquivo_origem_tipo\": null}, {\"id\": \"7cfcc0ea-b05d-4438-9426-bbd645a81ace\", \"forma\": \"NATO_DIGITAL\", \"suporte\": \"DIGITAL\", \"categoria\": \"RESPONSABILIDADE_TECNICA\", \"criado_em\": \"2026-09-16 00:36:31\", \"descricao\": \"Anotação de Responsabilidade Técnica (ART)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": \"4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2\", \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"7b4b9c41-daa2-4bee-b7c0-01fb69757990\", \"requer_devolucao\": 0, \"arquivo_origem_id\": null, \"condicao_documento\": null, \"arquivo_origem_tipo\": null}]','storage/protocolos/2026/146d5728-e384-41cb-a011-934f2476ba04/evento-01.pdf','023126c6cbae89a61fda49a7561147b464aefd52367b2f7db6465c8604d78ad4','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:36:31','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:36:31','2026-09-16 00:36:31'),('81ef628e-0452-463a-be5a-12a89f557e86','4e32e18d-dda5-45c0-9c66-0bb1725c81d6',1,'ENTRADA','RECEBIMENTO_CLIENTE','CONFIRMADA','CLIENTE','Armador Teste','AMAZON_NAVAL','Amazon Naval','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2','Belém','PA','PRESENCIAL',NULL,NULL,'2026-09-16 00:23:54',NULL,NULL,NULL,'f526b05dc24c4882b6bb8d9a11c72d32','[{\"id\": \"549b6b02-ca9f-4b63-8752-5b2c48781206\", \"forma\": \"NATO_DIGITAL\", \"suporte\": \"DIGITAL\", \"categoria\": \"RESPONSABILIDADE_TECNICA\", \"criado_em\": \"2026-09-16 00:23:54\", \"descricao\": \"Anotação de Responsabilidade Técnica (ART)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": \"4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2\", \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"81ef628e-0452-463a-be5a-12a89f557e86\", \"requer_devolucao\": 0, \"arquivo_origem_id\": null, \"condicao_documento\": null, \"arquivo_origem_tipo\": null}, {\"id\": \"89cc0f8f-4eac-44e7-8aca-29762391d9a3\", \"forma\": \"ORIGINAL\", \"suporte\": \"FISICO\", \"categoria\": \"PROPRIEDADE\", \"criado_em\": \"2026-09-16 00:23:54\", \"descricao\": \"Escritura Pública de Compra e Venda (Original)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": null, \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"81ef628e-0452-463a-be5a-12a89f557e86\", \"requer_devolucao\": 1, \"arquivo_origem_id\": null, \"condicao_documento\": \"Via original com selo\", \"arquivo_origem_tipo\": null}]','storage/protocolos/2026/4e32e18d-dda5-45c0-9c66-0bb1725c81d6/evento-01.pdf','7a780ecb85fb08041ca435c88422b0bcd49dc30ce59acc7a60243df27312ff10','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:23:54','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:23:54','2026-09-16 00:23:54'),('8a866c5d-589f-443e-91a1-a38e96fc35c8','6a74a73a-cbda-4c42-9051-6d4be7f1bad1',1,'ENTRADA','RECEBIMENTO_CLIENTE','CONFIRMADA','CLIENTE','Armador Teste','AMAZON_NAVAL','Amazon Naval','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2','Belém','PA','PRESENCIAL',NULL,NULL,'2026-09-16 04:00:34',NULL,NULL,NULL,'075bc40a3436973b92b90302d22a70cf','[{\"id\": \"8595dd7c-daef-4896-9068-fcfba73a25c2\", \"forma\": \"NATO_DIGITAL\", \"suporte\": \"DIGITAL\", \"categoria\": \"RESPONSABILIDADE_TECNICA\", \"criado_em\": \"2026-09-16 04:00:34\", \"descricao\": \"Anotação de Responsabilidade Técnica (ART)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": \"4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2\", \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"8a866c5d-589f-443e-91a1-a38e96fc35c8\", \"requer_devolucao\": 0, \"arquivo_origem_id\": null, \"condicao_documento\": null, \"arquivo_origem_tipo\": null}, {\"id\": \"cd88632f-4ba0-48bf-960b-9d36eb3745ea\", \"forma\": \"ORIGINAL\", \"suporte\": \"FISICO\", \"categoria\": \"PROPRIEDADE\", \"criado_em\": \"2026-09-16 04:00:34\", \"descricao\": \"Escritura Pública de Compra e Venda (Original)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": null, \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"8a866c5d-589f-443e-91a1-a38e96fc35c8\", \"requer_devolucao\": 1, \"arquivo_origem_id\": null, \"condicao_documento\": \"Via original com selo\", \"arquivo_origem_tipo\": null}]','storage/protocolos/2026/6a74a73a-cbda-4c42-9051-6d4be7f1bad1/evento-01.pdf','81b32cc30d3084ae3ece2139cd4c4c0a1c84e721500a470a9c86c5a70af05035','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 04:00:34','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 04:00:34','2026-09-16 04:00:34'),('978a98ef-972c-4034-8018-778982688712','7254981c-d54a-489b-9018-3472299da5ee',1,'ENTRADA','RECEBIMENTO_CLIENTE','CONFIRMADA','CLIENTE','Armador Teste','AMAZON_NAVAL','Amazon Naval','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2','Belém','PA','PRESENCIAL',NULL,NULL,'2026-09-16 00:33:55',NULL,NULL,NULL,'2eb5c27b154801047a3806855d77e839','[{\"id\": \"3c9c794f-a47f-415e-98ba-0f4b1a13b531\", \"forma\": \"NATO_DIGITAL\", \"suporte\": \"DIGITAL\", \"categoria\": \"RESPONSABILIDADE_TECNICA\", \"criado_em\": \"2026-09-16 00:33:55\", \"descricao\": \"Anotação de Responsabilidade Técnica (ART)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": \"4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2\", \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"978a98ef-972c-4034-8018-778982688712\", \"requer_devolucao\": 0, \"arquivo_origem_id\": null, \"condicao_documento\": null, \"arquivo_origem_tipo\": null}, {\"id\": \"62dcb611-8648-4d37-afad-961428dc9609\", \"forma\": \"ORIGINAL\", \"suporte\": \"FISICO\", \"categoria\": \"PROPRIEDADE\", \"criado_em\": \"2026-09-16 00:33:55\", \"descricao\": \"Escritura Pública de Compra e Venda (Original)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": null, \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"978a98ef-972c-4034-8018-778982688712\", \"requer_devolucao\": 1, \"arquivo_origem_id\": null, \"condicao_documento\": \"Via original com selo\", \"arquivo_origem_tipo\": null}]','storage/protocolos/2026/7254981c-d54a-489b-9018-3472299da5ee/evento-01.pdf','00a5d20aaab0489b3b248f340184478150d227ce14ef7ae8f4261e6595748401','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:33:55','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:33:55','2026-09-16 00:33:55'),('a2483f83-da37-4bb4-b41c-0596c02f9832','24997b9b-b4c2-45cc-ac84-62ce6c5fee9b',1,'ENTRADA','RECEBIMENTO_CLIENTE','CONFIRMADA','CLIENTE','Armador Teste','AMAZON_NAVAL','Amazon Naval','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2','Belém','PA','PRESENCIAL',NULL,NULL,'2026-09-15 19:14:52',NULL,NULL,NULL,'14a8bfc523b652fd49afac25d4bd4b1e','[{\"id\": \"6bb83fab-48c9-46ad-93c4-6799e65bde40\", \"forma\": \"ORIGINAL\", \"suporte\": \"FISICO\", \"categoria\": \"PROPRIEDADE\", \"criado_em\": \"2026-09-15 19:14:52\", \"descricao\": \"Escritura Pública de Compra e Venda (Original)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": null, \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"a2483f83-da37-4bb4-b41c-0596c02f9832\", \"requer_devolucao\": 1, \"arquivo_origem_id\": null, \"condicao_documento\": \"Via original com selo\", \"arquivo_origem_tipo\": null}, {\"id\": \"b546b375-87f5-4ea9-bfd1-e1d5d5e84237\", \"forma\": \"NATO_DIGITAL\", \"suporte\": \"DIGITAL\", \"categoria\": \"RESPONSABILIDADE_TECNICA\", \"criado_em\": \"2026-09-15 19:14:52\", \"descricao\": \"Anotação de Responsabilidade Técnica (ART)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": \"4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2\", \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"a2483f83-da37-4bb4-b41c-0596c02f9832\", \"requer_devolucao\": 0, \"arquivo_origem_id\": null, \"condicao_documento\": null, \"arquivo_origem_tipo\": null}]','storage/protocolos/2026/24997b9b-b4c2-45cc-ac84-62ce6c5fee9b/evento-01.pdf','f897513643fa42251db65e7530ac35e0aa5353c4a50720ca2a1d7fdffac0e775','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-15 19:14:52','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-15 19:14:52','2026-09-15 19:14:52'),('aee3ea4e-d71b-4e1b-bf22-203ec3ea7db3','8cf5fec7-6a72-4a8d-906f-a9b21895bd30',1,'ENTRADA','RECEBIMENTO_CLIENTE','CONFIRMADA','CLIENTE','Armador Teste','AMAZON_NAVAL','Amazon Naval','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2','Belém','PA','PRESENCIAL',NULL,NULL,'2026-09-15 18:52:04',NULL,NULL,NULL,'61ce75f2a744dfcfcd5b949784dde1f7','[{\"id\": \"629dc14c-3a41-402f-a1f8-e41c21d33b80\", \"forma\": \"ORIGINAL\", \"suporte\": \"FISICO\", \"categoria\": \"PROPRIEDADE\", \"criado_em\": \"2026-09-15 18:52:04\", \"descricao\": \"Escritura Pública de Compra e Venda (Original)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": null, \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"aee3ea4e-d71b-4e1b-bf22-203ec3ea7db3\", \"requer_devolucao\": 1, \"arquivo_origem_id\": null, \"condicao_documento\": \"Via original com selo\", \"arquivo_origem_tipo\": null}, {\"id\": \"c08d0023-374c-407f-8385-4803dcc9c769\", \"forma\": \"NATO_DIGITAL\", \"suporte\": \"DIGITAL\", \"categoria\": \"RESPONSABILIDADE_TECNICA\", \"criado_em\": \"2026-09-15 18:52:04\", \"descricao\": \"Anotação de Responsabilidade Técnica (ART)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": \"4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2\", \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"aee3ea4e-d71b-4e1b-bf22-203ec3ea7db3\", \"requer_devolucao\": 0, \"arquivo_origem_id\": null, \"condicao_documento\": null, \"arquivo_origem_tipo\": null}]','storage/protocolos/2026/8cf5fec7-6a72-4a8d-906f-a9b21895bd30/evento-01.pdf','72a1977940f39577d5b1070ada2d5db30a4c8b8c2560996cacaf6e5764cfa787','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-15 18:52:04','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-15 18:52:04','2026-09-15 18:52:04'),('b3c49df0-1ad3-4bca-9840-9249fb849d26','4b1b2cb7-71e7-472a-ab95-d70c773c72c0',1,'ENTRADA','RECEBIMENTO_CLIENTE','CONFIRMADA','CLIENTE','Armador Teste','AMAZON_NAVAL','Amazon Naval','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2','Belém','PA','PRESENCIAL',NULL,NULL,'2026-09-13 06:44:41',NULL,NULL,NULL,'06d45010a2f072905fb2a1579a27bc4e','[{\"id\": \"37a7ee1d-417c-4fd7-aeac-6cb1c3ea5e2b\", \"forma\": \"ORIGINAL\", \"suporte\": \"FISICO\", \"categoria\": \"PROPRIEDADE\", \"criado_em\": \"2026-09-13 06:44:41\", \"descricao\": \"Escritura Pública de Compra e Venda (Original)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": null, \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"b3c49df0-1ad3-4bca-9840-9249fb849d26\", \"requer_devolucao\": 1, \"arquivo_origem_id\": null, \"condicao_documento\": \"Via original com selo\", \"arquivo_origem_tipo\": null}, {\"id\": \"f43d21de-0cf3-4175-a5a6-c639a9391e23\", \"forma\": \"NATO_DIGITAL\", \"suporte\": \"DIGITAL\", \"categoria\": \"RESPONSABILIDADE_TECNICA\", \"criado_em\": \"2026-09-13 06:44:41\", \"descricao\": \"Anotação de Responsabilidade Técnica (ART)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": \"4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2\", \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"b3c49df0-1ad3-4bca-9840-9249fb849d26\", \"requer_devolucao\": 0, \"arquivo_origem_id\": null, \"condicao_documento\": null, \"arquivo_origem_tipo\": null}]','storage/protocolos/2026/4b1b2cb7-71e7-472a-ab95-d70c773c72c0/evento-01.pdf','7015af64ddc6fcf2a83029f6b2cdaf730495fa8b9993e9fb618e65fb84d24f75','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-13 06:44:42','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-13 06:44:41','2026-09-13 06:44:42'),('b7a3f0a0-9eb5-4442-aa83-d7484e1f0cbf','dc57db3d-c1b0-4242-9721-5d9b6e8198da',1,'ENTRADA','RECEBIMENTO_CLIENTE','CONFIRMADA','CLIENTE','Armador Teste','AMAZON_NAVAL','Amazon Naval','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2','Belém','PA','PRESENCIAL',NULL,NULL,'2026-09-16 00:32:47',NULL,NULL,NULL,'97157be11658388258f50c1d8ea8b40f','[{\"id\": \"e61a1f43-1eba-4ddc-a0cc-08bef53e7123\", \"forma\": \"ORIGINAL\", \"suporte\": \"FISICO\", \"categoria\": \"PROPRIEDADE\", \"criado_em\": \"2026-09-16 00:32:47\", \"descricao\": \"Escritura Pública de Compra e Venda (Original)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": null, \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"b7a3f0a0-9eb5-4442-aa83-d7484e1f0cbf\", \"requer_devolucao\": 1, \"arquivo_origem_id\": null, \"condicao_documento\": \"Via original com selo\", \"arquivo_origem_tipo\": null}, {\"id\": \"ffb1ebd9-4a20-49a2-948a-319e0ffd48b6\", \"forma\": \"NATO_DIGITAL\", \"suporte\": \"DIGITAL\", \"categoria\": \"RESPONSABILIDADE_TECNICA\", \"criado_em\": \"2026-09-16 00:32:47\", \"descricao\": \"Anotação de Responsabilidade Técnica (ART)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": \"4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2\", \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"b7a3f0a0-9eb5-4442-aa83-d7484e1f0cbf\", \"requer_devolucao\": 0, \"arquivo_origem_id\": null, \"condicao_documento\": null, \"arquivo_origem_tipo\": null}]','storage/protocolos/2026/dc57db3d-c1b0-4242-9721-5d9b6e8198da/evento-01.pdf','95ce8283ea770c72bd1980a420fd3952ba8ff8aa5fcdb7d764a73bbe6729c950','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:32:47','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:32:47','2026-09-16 00:32:47'),('bef51f8d-70da-4b12-ba87-08fd57195507','db4bef24-d50b-43b7-a980-61eb3fe58af0',1,'ENTRADA','RECEBIMENTO_CLIENTE','CONFIRMADA','CLIENTE','Armador Teste','AMAZON_NAVAL','Amazon Naval','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2','Belém','PA','PRESENCIAL',NULL,NULL,'2026-09-15 20:20:34',NULL,NULL,NULL,'a39f30b7468a55c465ad012792c50b62','[{\"id\": \"27af9f2f-b034-4a20-8ff5-73916cdd7e00\", \"forma\": \"ORIGINAL\", \"suporte\": \"FISICO\", \"categoria\": \"PROPRIEDADE\", \"criado_em\": \"2026-09-15 20:20:34\", \"descricao\": \"Escritura Pública de Compra e Venda (Original)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": null, \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"bef51f8d-70da-4b12-ba87-08fd57195507\", \"requer_devolucao\": 1, \"arquivo_origem_id\": null, \"condicao_documento\": \"Via original com selo\", \"arquivo_origem_tipo\": null}, {\"id\": \"4e69514a-f105-4b4e-a098-646cbdbf1ee7\", \"forma\": \"NATO_DIGITAL\", \"suporte\": \"DIGITAL\", \"categoria\": \"RESPONSABILIDADE_TECNICA\", \"criado_em\": \"2026-09-15 20:20:34\", \"descricao\": \"Anotação de Responsabilidade Técnica (ART)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": \"4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2\", \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"bef51f8d-70da-4b12-ba87-08fd57195507\", \"requer_devolucao\": 0, \"arquivo_origem_id\": null, \"condicao_documento\": null, \"arquivo_origem_tipo\": null}]','storage/protocolos/2026/db4bef24-d50b-43b7-a980-61eb3fe58af0/evento-01.pdf','fb145dacb8f8879564a49169dfb433851e405e12ca6c3ba3ca6095523d736d8d','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-15 20:20:34','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-15 20:20:34','2026-09-15 20:20:34'),('c24df4b1-b953-4f98-b19b-b71d12e6c692','bcab5c48-6a49-4a42-88c8-a287eda78876',1,'ENTRADA','RECEBIMENTO_CLIENTE','CONFIRMADA','CLIENTE','Armador Teste','AMAZON_NAVAL','Amazon Naval','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2','Belém','PA','PRESENCIAL',NULL,NULL,'2026-09-13 07:23:07',NULL,NULL,NULL,'015fdd40acea7e689f4b4244cdc9208e','[{\"id\": \"09b125ec-224b-4da3-a42b-0452f4009ff4\", \"forma\": \"NATO_DIGITAL\", \"suporte\": \"DIGITAL\", \"categoria\": \"RESPONSABILIDADE_TECNICA\", \"criado_em\": \"2026-09-13 07:23:07\", \"descricao\": \"Anotação de Responsabilidade Técnica (ART)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": \"4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2\", \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"c24df4b1-b953-4f98-b19b-b71d12e6c692\", \"requer_devolucao\": 0, \"arquivo_origem_id\": null, \"condicao_documento\": null, \"arquivo_origem_tipo\": null}, {\"id\": \"81fc1197-19eb-4bfe-8e3f-4f81026a66f7\", \"forma\": \"ORIGINAL\", \"suporte\": \"FISICO\", \"categoria\": \"PROPRIEDADE\", \"criado_em\": \"2026-09-13 07:23:07\", \"descricao\": \"Escritura Pública de Compra e Venda (Original)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": null, \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"c24df4b1-b953-4f98-b19b-b71d12e6c692\", \"requer_devolucao\": 1, \"arquivo_origem_id\": null, \"condicao_documento\": \"Via original com selo\", \"arquivo_origem_tipo\": null}]','storage/protocolos/2026/bcab5c48-6a49-4a42-88c8-a287eda78876/evento-01.pdf','ea324a1230c8a381cf463132fe2e2c9570e5af7d59fb72fe04bd672e711ebd7a','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-13 07:23:07','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-13 07:23:07','2026-09-13 07:23:07'),('c34ae932-3485-4e89-b7e7-d5378b403320','2e3f61a9-8aa4-43b5-bcf1-dc3c07af4c6c',1,'ENTRADA','RECEBIMENTO_CLIENTE','CONFIRMADA','CLIENTE','Armador Teste','AMAZON_NAVAL','Amazon Naval','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2','Belém','PA','PRESENCIAL',NULL,NULL,'2026-09-16 00:48:01',NULL,NULL,NULL,'fc45b8890bc8f1095eccd2ba470ec5f0','[{\"id\": \"e0cc2e4b-c165-42fd-850b-29b58c7bb8e7\", \"forma\": \"ORIGINAL\", \"suporte\": \"FISICO\", \"categoria\": \"PROPRIEDADE\", \"criado_em\": \"2026-09-16 00:48:01\", \"descricao\": \"Escritura Pública de Compra e Venda (Original)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": null, \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"c34ae932-3485-4e89-b7e7-d5378b403320\", \"requer_devolucao\": 1, \"arquivo_origem_id\": null, \"condicao_documento\": \"Via original com selo\", \"arquivo_origem_tipo\": null}, {\"id\": \"fc1b4c43-36d4-47f0-9f8e-d9f3331aebfa\", \"forma\": \"NATO_DIGITAL\", \"suporte\": \"DIGITAL\", \"categoria\": \"RESPONSABILIDADE_TECNICA\", \"criado_em\": \"2026-09-16 00:48:01\", \"descricao\": \"Anotação de Responsabilidade Técnica (ART)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": \"4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2\", \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"c34ae932-3485-4e89-b7e7-d5378b403320\", \"requer_devolucao\": 0, \"arquivo_origem_id\": null, \"condicao_documento\": null, \"arquivo_origem_tipo\": null}]','storage/protocolos/2026/2e3f61a9-8aa4-43b5-bcf1-dc3c07af4c6c/evento-01.pdf','b479c2c5c7fe02dffaafad141a4081ed5b85e0342c2d47e713b71d63b2e85760','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:48:01','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:48:01','2026-09-16 00:48:01'),('c819a998-57ec-40e1-83e8-2fee5f50faa0','7519d5c2-d949-4fa7-ab79-ee94c5cde88f',1,'ENTRADA','RECEBIMENTO_CLIENTE','CONFIRMADA','CLIENTE','Armador Teste','AMAZON_NAVAL','Amazon Naval','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2','Belém','PA','PRESENCIAL',NULL,NULL,'2026-09-16 03:23:19',NULL,NULL,NULL,'b25f19dc7a799ce14374eb8c1e717337','[{\"id\": \"3f0bdb52-b6b9-43b1-8bd1-93653cf42085\", \"forma\": \"ORIGINAL\", \"suporte\": \"FISICO\", \"categoria\": \"PROPRIEDADE\", \"criado_em\": \"2026-09-16 03:23:19\", \"descricao\": \"Escritura Pública de Compra e Venda (Original)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": null, \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"c819a998-57ec-40e1-83e8-2fee5f50faa0\", \"requer_devolucao\": 1, \"arquivo_origem_id\": null, \"condicao_documento\": \"Via original com selo\", \"arquivo_origem_tipo\": null}, {\"id\": \"a181f6e1-3914-4ca3-b902-cf0e8ef15b56\", \"forma\": \"NATO_DIGITAL\", \"suporte\": \"DIGITAL\", \"categoria\": \"RESPONSABILIDADE_TECNICA\", \"criado_em\": \"2026-09-16 03:23:19\", \"descricao\": \"Anotação de Responsabilidade Técnica (ART)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": \"4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2\", \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"c819a998-57ec-40e1-83e8-2fee5f50faa0\", \"requer_devolucao\": 0, \"arquivo_origem_id\": null, \"condicao_documento\": null, \"arquivo_origem_tipo\": null}]','storage/protocolos/2026/7519d5c2-d949-4fa7-ab79-ee94c5cde88f/evento-01.pdf','c4e5f2d4aa3b1e296e5cd9a1ee7a3a48e7c752c372a1869cec305a98263e5240','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 03:23:19','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 03:23:19','2026-09-16 03:23:19'),('ca21f805-0db1-4a3f-bcd9-96881787ae31','0e10393a-e8b8-4fd2-8b83-fee61b6a5cf0',1,'ENTRADA','RECEBIMENTO_CLIENTE','CONFIRMADA','CLIENTE','Armador Teste','AMAZON_NAVAL','Amazon Naval','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2','Belém','PA','PRESENCIAL',NULL,NULL,'2026-09-13 07:40:00',NULL,NULL,NULL,'fd6bfc0e8cc8e4733788238c8e4bf2b4','[{\"id\": \"02b4c201-aacf-47c4-a446-12783fca046c\", \"forma\": \"ORIGINAL\", \"suporte\": \"FISICO\", \"categoria\": \"PROPRIEDADE\", \"criado_em\": \"2026-09-13 07:40:00\", \"descricao\": \"Escritura Pública de Compra e Venda (Original)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": null, \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"ca21f805-0db1-4a3f-bcd9-96881787ae31\", \"requer_devolucao\": 1, \"arquivo_origem_id\": null, \"condicao_documento\": \"Via original com selo\", \"arquivo_origem_tipo\": null}, {\"id\": \"71c99373-9df3-4490-a7d8-ba8385dfa213\", \"forma\": \"NATO_DIGITAL\", \"suporte\": \"DIGITAL\", \"categoria\": \"RESPONSABILIDADE_TECNICA\", \"criado_em\": \"2026-09-13 07:40:00\", \"descricao\": \"Anotação de Responsabilidade Técnica (ART)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": \"4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2\", \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"ca21f805-0db1-4a3f-bcd9-96881787ae31\", \"requer_devolucao\": 0, \"arquivo_origem_id\": null, \"condicao_documento\": null, \"arquivo_origem_tipo\": null}]','storage/protocolos/2026/0e10393a-e8b8-4fd2-8b83-fee61b6a5cf0/evento-01.pdf','acad6406e363d4aad24960561245f01ffc8daba6a7c8b5ae1289c53a9d320f67','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-13 07:40:00','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-13 07:40:00','2026-09-13 07:40:00'),('d545ae9f-6d55-4801-9a90-ea3e6f15fe89','b48ba8be-fbe7-4e6e-98ce-21b19580c8eb',1,'ENTRADA','RECEBIMENTO_CLIENTE','CONFIRMADA','CLIENTE','Armador Teste','AMAZON_NAVAL','Amazon Naval','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2','Belém','PA','PRESENCIAL',NULL,NULL,'2026-09-16 00:36:10',NULL,NULL,NULL,'477301bdbd83828d9fd043def6a5fec2','[{\"id\": \"757e5427-90a8-4961-b8e2-d3331aba27e8\", \"forma\": \"NATO_DIGITAL\", \"suporte\": \"DIGITAL\", \"categoria\": \"RESPONSABILIDADE_TECNICA\", \"criado_em\": \"2026-09-16 00:36:10\", \"descricao\": \"Anotação de Responsabilidade Técnica (ART)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": \"4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2\", \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"d545ae9f-6d55-4801-9a90-ea3e6f15fe89\", \"requer_devolucao\": 0, \"arquivo_origem_id\": null, \"condicao_documento\": null, \"arquivo_origem_tipo\": null}, {\"id\": \"7bc4751f-be60-4f5a-9b31-b41ce2a37aea\", \"forma\": \"ORIGINAL\", \"suporte\": \"FISICO\", \"categoria\": \"PROPRIEDADE\", \"criado_em\": \"2026-09-16 00:36:10\", \"descricao\": \"Escritura Pública de Compra e Venda (Original)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": null, \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"d545ae9f-6d55-4801-9a90-ea3e6f15fe89\", \"requer_devolucao\": 1, \"arquivo_origem_id\": null, \"condicao_documento\": \"Via original com selo\", \"arquivo_origem_tipo\": null}]','storage/protocolos/2026/b48ba8be-fbe7-4e6e-98ce-21b19580c8eb/evento-01.pdf','8a4f7b32fcb63b0404fa3a1efaa42b33e7df62eefdc8b6a63a325ec8ffef367c','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:36:11','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:36:10','2026-09-16 00:36:11'),('d5dc879a-5373-4bdf-8833-44f96a62b9c7','caaf28b9-6221-4fc9-ac79-7f11d30423e6',1,'ENTRADA','RECEBIMENTO_CLIENTE','CONFIRMADA','CLIENTE','Armador Teste','AMAZON_NAVAL','Amazon Naval','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2','Belém','PA','PRESENCIAL',NULL,NULL,'2026-09-15 18:03:19',NULL,NULL,NULL,'02d2e96307d634dbfd4151722eda5b4a','[{\"id\": \"987656f9-5de8-4437-bad8-70a8d9c305f8\", \"forma\": \"NATO_DIGITAL\", \"suporte\": \"DIGITAL\", \"categoria\": \"RESPONSABILIDADE_TECNICA\", \"criado_em\": \"2026-09-15 18:03:19\", \"descricao\": \"Anotação de Responsabilidade Técnica (ART)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": \"4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2\", \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"d5dc879a-5373-4bdf-8833-44f96a62b9c7\", \"requer_devolucao\": 0, \"arquivo_origem_id\": null, \"condicao_documento\": null, \"arquivo_origem_tipo\": null}, {\"id\": \"b27f8452-9a79-424a-95ac-ab85123d1e89\", \"forma\": \"ORIGINAL\", \"suporte\": \"FISICO\", \"categoria\": \"PROPRIEDADE\", \"criado_em\": \"2026-09-15 18:03:19\", \"descricao\": \"Escritura Pública de Compra e Venda (Original)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": null, \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"d5dc879a-5373-4bdf-8833-44f96a62b9c7\", \"requer_devolucao\": 1, \"arquivo_origem_id\": null, \"condicao_documento\": \"Via original com selo\", \"arquivo_origem_tipo\": null}]','storage/protocolos/2026/caaf28b9-6221-4fc9-ac79-7f11d30423e6/evento-01.pdf','e3005e738e9130da08c3357641f1c23b8714589e1a44d8d5b1d48787a7cad26b','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-15 18:03:19','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-15 18:03:19','2026-09-15 18:03:19'),('dc90cc88-5fb6-41aa-9910-0b2f55faabe9','7950680a-8a22-4d97-8661-6c646c2e9fcf',1,'ENTRADA','RECEBIMENTO_CLIENTE','CONFIRMADA','CLIENTE','Armador Teste','AMAZON_NAVAL','Amazon Naval','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2','Belém','PA','PRESENCIAL',NULL,NULL,'2026-09-16 03:25:01',NULL,NULL,NULL,'163908ab114d09414a16851490777a47','[{\"id\": \"5664ed2f-594f-4c0b-885e-b6d31d7d9df6\", \"forma\": \"ORIGINAL\", \"suporte\": \"FISICO\", \"categoria\": \"PROPRIEDADE\", \"criado_em\": \"2026-09-16 03:25:01\", \"descricao\": \"Escritura Pública de Compra e Venda (Original)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": null, \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"dc90cc88-5fb6-41aa-9910-0b2f55faabe9\", \"requer_devolucao\": 1, \"arquivo_origem_id\": null, \"condicao_documento\": \"Via original com selo\", \"arquivo_origem_tipo\": null}, {\"id\": \"a3c7ec66-da64-4fae-a07a-199ec623343a\", \"forma\": \"NATO_DIGITAL\", \"suporte\": \"DIGITAL\", \"categoria\": \"RESPONSABILIDADE_TECNICA\", \"criado_em\": \"2026-09-16 03:25:01\", \"descricao\": \"Anotação de Responsabilidade Técnica (ART)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": \"4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2\", \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"dc90cc88-5fb6-41aa-9910-0b2f55faabe9\", \"requer_devolucao\": 0, \"arquivo_origem_id\": null, \"condicao_documento\": null, \"arquivo_origem_tipo\": null}]','storage/protocolos/2026/7950680a-8a22-4d97-8661-6c646c2e9fcf/evento-01.pdf','6ad38da67d48109d392f998387cf39fd03cc02abe80923dfd3632dd35f344b07','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 03:25:02','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 03:25:01','2026-09-16 03:25:02'),('e867a119-4166-490e-9d10-4042348659c3','5d2a77c7-851d-4c02-995b-aa6b264f33c3',1,'ENTRADA','RECEBIMENTO_CLIENTE','CONFIRMADA','CLIENTE','Armador Teste','AMAZON_NAVAL','Amazon Naval','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2','Belém','PA','PRESENCIAL',NULL,NULL,'2026-09-16 03:07:36',NULL,NULL,NULL,'3867735215f6d7b4ebcdf898725e5cea','[{\"id\": \"7e722fd3-9384-4ba1-be60-4d772a4131ed\", \"forma\": \"ORIGINAL\", \"suporte\": \"FISICO\", \"categoria\": \"PROPRIEDADE\", \"criado_em\": \"2026-09-16 03:07:36\", \"descricao\": \"Escritura Pública de Compra e Venda (Original)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": null, \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"e867a119-4166-490e-9d10-4042348659c3\", \"requer_devolucao\": 1, \"arquivo_origem_id\": null, \"condicao_documento\": \"Via original com selo\", \"arquivo_origem_tipo\": null}, {\"id\": \"fb98047a-6322-43b1-86fd-1766fd411565\", \"forma\": \"NATO_DIGITAL\", \"suporte\": \"DIGITAL\", \"categoria\": \"RESPONSABILIDADE_TECNICA\", \"criado_em\": \"2026-09-16 03:07:36\", \"descricao\": \"Anotação de Responsabilidade Técnica (ART)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": \"4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2\", \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"e867a119-4166-490e-9d10-4042348659c3\", \"requer_devolucao\": 0, \"arquivo_origem_id\": null, \"condicao_documento\": null, \"arquivo_origem_tipo\": null}]','storage/protocolos/2026/5d2a77c7-851d-4c02-995b-aa6b264f33c3/evento-01.pdf','d811a637499de09e492d58bc0747e222d58f4f77d12a815eb4a54f4d36a795d8','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 03:07:36','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 03:07:36','2026-09-16 03:07:36'),('e8e965ea-0182-40d5-87aa-e2ef68326830','9bc4c2a6-ab76-417a-9e5c-82b0fc049176',1,'ENTRADA','RECEBIMENTO_CLIENTE','CONFIRMADA','CLIENTE','Armador Teste','AMAZON_NAVAL','Amazon Naval','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2','Belém','PA','PRESENCIAL',NULL,NULL,'2026-09-16 01:04:40',NULL,NULL,NULL,'c5686d516d78d8d3911cb89c67467772','[{\"id\": \"8560a371-8261-4124-9aae-ed6551852d33\", \"forma\": \"NATO_DIGITAL\", \"suporte\": \"DIGITAL\", \"categoria\": \"RESPONSABILIDADE_TECNICA\", \"criado_em\": \"2026-09-16 01:04:40\", \"descricao\": \"Anotação de Responsabilidade Técnica (ART)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": \"4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2\", \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"e8e965ea-0182-40d5-87aa-e2ef68326830\", \"requer_devolucao\": 0, \"arquivo_origem_id\": null, \"condicao_documento\": null, \"arquivo_origem_tipo\": null}, {\"id\": \"c4050f96-365b-4b9f-b646-fcde7aaa86f7\", \"forma\": \"ORIGINAL\", \"suporte\": \"FISICO\", \"categoria\": \"PROPRIEDADE\", \"criado_em\": \"2026-09-16 01:04:40\", \"descricao\": \"Escritura Pública de Compra e Venda (Original)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": null, \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"e8e965ea-0182-40d5-87aa-e2ef68326830\", \"requer_devolucao\": 1, \"arquivo_origem_id\": null, \"condicao_documento\": \"Via original com selo\", \"arquivo_origem_tipo\": null}]','storage/protocolos/2026/9bc4c2a6-ab76-417a-9e5c-82b0fc049176/evento-01.pdf','341a55181e4270d529fdb14ba8b4d55b298804c7b531857f3272a4d11f686391','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 01:04:40','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 01:04:40','2026-09-16 01:04:40'),('eb15509f-4348-404a-a867-f504ec1f0309','5c2f5179-2235-42ab-b85e-ddfff32a4f79',1,'ENTRADA','RECEBIMENTO_CLIENTE','CONFIRMADA','CLIENTE','Armador Teste','AMAZON_NAVAL','Amazon Naval','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2','Belém','PA','PRESENCIAL',NULL,NULL,'2026-09-13 06:28:55',NULL,NULL,NULL,'75639f0a96a9d64e26bde3e0326b7640','[{\"id\": \"152f564d-a5eb-4e97-b215-68873eeac381\", \"forma\": \"NATO_DIGITAL\", \"suporte\": \"DIGITAL\", \"categoria\": \"RESPONSABILIDADE_TECNICA\", \"criado_em\": \"2026-09-13 06:28:55\", \"descricao\": \"Anotação de Responsabilidade Técnica (ART)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": \"4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2\", \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"eb15509f-4348-404a-a867-f504ec1f0309\", \"requer_devolucao\": 0, \"arquivo_origem_id\": null, \"condicao_documento\": null, \"arquivo_origem_tipo\": null}, {\"id\": \"8fbc5e25-b94a-464f-8e74-a9e868dd94cc\", \"forma\": \"ORIGINAL\", \"suporte\": \"FISICO\", \"categoria\": \"PROPRIEDADE\", \"criado_em\": \"2026-09-13 06:28:55\", \"descricao\": \"Escritura Pública de Compra e Venda (Original)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": null, \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"eb15509f-4348-404a-a867-f504ec1f0309\", \"requer_devolucao\": 1, \"arquivo_origem_id\": null, \"condicao_documento\": \"Via original com selo\", \"arquivo_origem_tipo\": null}]','storage/protocolos/2026/5c2f5179-2235-42ab-b85e-ddfff32a4f79/evento-01.pdf','9268e7b8af36924ca8d0fccc2605af3470e4adebf64621e4f04e82f2b39f3815','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-13 06:28:55','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-13 06:28:55','2026-09-13 06:28:55'),('f9596bc8-fb67-410c-a70a-987a6329429f','cd5190e0-8b56-4e02-8ea0-6c2ac8bbfe60',1,'ENTRADA','RECEBIMENTO_CLIENTE','CONFIRMADA','CLIENTE','Armador Teste','AMAZON_NAVAL','Amazon Naval','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2','Belém','PA','PRESENCIAL',NULL,NULL,'2026-09-16 00:34:53',NULL,NULL,NULL,'5f5d6f5660679b53557edf33b45c52c7','[{\"id\": \"cf976703-0724-4e78-bc73-dfe5df6d0657\", \"forma\": \"NATO_DIGITAL\", \"suporte\": \"DIGITAL\", \"categoria\": \"RESPONSABILIDADE_TECNICA\", \"criado_em\": \"2026-09-16 00:34:53\", \"descricao\": \"Anotação de Responsabilidade Técnica (ART)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": \"4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2\", \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"f9596bc8-fb67-410c-a70a-987a6329429f\", \"requer_devolucao\": 0, \"arquivo_origem_id\": null, \"condicao_documento\": null, \"arquivo_origem_tipo\": null}, {\"id\": \"d227f980-65c1-4fb3-a767-e3ed9e5b13f9\", \"forma\": \"ORIGINAL\", \"suporte\": \"FISICO\", \"categoria\": \"PROPRIEDADE\", \"criado_em\": \"2026-09-16 00:34:53\", \"descricao\": \"Escritura Pública de Compra e Venda (Original)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": null, \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"f9596bc8-fb67-410c-a70a-987a6329429f\", \"requer_devolucao\": 1, \"arquivo_origem_id\": null, \"condicao_documento\": \"Via original com selo\", \"arquivo_origem_tipo\": null}]','storage/protocolos/2026/cd5190e0-8b56-4e02-8ea0-6c2ac8bbfe60/evento-01.pdf','5346a3dda7f36e1aed35c782654e3861388995a01615def6dd0bea3ae94543da','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:34:53','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 00:34:53','2026-09-16 00:34:53'),('fd700974-4406-4884-a894-3a3244642e02','c5c2340a-0317-45d7-8105-69e67a23c7d9',1,'ENTRADA','RECEBIMENTO_CLIENTE','CONFIRMADA','CLIENTE','Armador Teste','AMAZON_NAVAL','Amazon Naval','4f01ef52-adf5-11f1-8a7c-be2fb1f77be2','Belém','PA','PRESENCIAL',NULL,NULL,'2026-09-16 04:02:54',NULL,NULL,NULL,'10edb2319ac6503467a77948e0d796ca','[{\"id\": \"770fce67-8529-4901-bf9b-c6b31bd0d5fa\", \"forma\": \"NATO_DIGITAL\", \"suporte\": \"DIGITAL\", \"categoria\": \"RESPONSABILIDADE_TECNICA\", \"criado_em\": \"2026-09-16 04:02:54\", \"descricao\": \"Anotação de Responsabilidade Técnica (ART)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": \"4f00f8fd-adf5-11f1-8a7c-be2fb1f77be2\", \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"fd700974-4406-4884-a894-3a3244642e02\", \"requer_devolucao\": 0, \"arquivo_origem_id\": null, \"condicao_documento\": null, \"arquivo_origem_tipo\": null}, {\"id\": \"eeed2bc3-20c4-4808-8709-6fa456f36d14\", \"forma\": \"ORIGINAL\", \"suporte\": \"FISICO\", \"categoria\": \"PROPRIEDADE\", \"criado_em\": \"2026-09-16 04:02:54\", \"descricao\": \"Escritura Pública de Compra e Venda (Original)\", \"observacao\": null, \"quantidade\": 1, \"catalogo_id\": null, \"arquivo_hash\": null, \"arquivo_nome\": null, \"devolvido_em\": null, \"data_documento\": null, \"numero_revisao\": null, \"movimentacao_id\": \"fd700974-4406-4884-a894-3a3244642e02\", \"requer_devolucao\": 1, \"arquivo_origem_id\": null, \"condicao_documento\": \"Via original com selo\", \"arquivo_origem_tipo\": null}]','storage/protocolos/2026/c5c2340a-0317-45d7-8105-69e67a23c7d9/evento-01.pdf','e52133d37e8562a8c8457d996da2b1db093c9f8abde20f45efb33167dbd8fd1a','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 04:02:54','dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-16 04:02:54','2026-09-16 04:02:54');
/*!40000 ALTER TABLE `protocolo_movimentacoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `protocolo_unidades_maritimas`
--

DROP TABLE IF EXISTS `protocolo_unidades_maritimas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `protocolo_unidades_maritimas` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `codigo` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nome` varchar(180) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tipo` enum('CAPITANIA','DELEGACIA','AGENCIA') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `cidade` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `uf` char(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `endereco` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `telefone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(180) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `jurisdicao` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `formato_protocolo_regex` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `url_consulta` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `criado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
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
INSERT INTO `protocolo_unidades_maritimas` VALUES ('4f01ef52-adf5-11f1-8a7c-be2fb1f77be2','CPAOR','Capitania dos Portos da Amazônia Oriental','CAPITANIA','Belém','PA',NULL,NULL,NULL,NULL,NULL,'https://atendimento-dpc.marinha.mil.br/sisap/agendamento/consultaprocesso/#/',1,NULL,'2026-09-11 15:27:32','2026-09-11 15:28:35');
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
  `uuid` char(36) COLLATE utf8mb4_general_ci NOT NULL DEFAULT (uuid()),
  `nome_completo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `cpf_cnpj` varchar(18) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `usuario_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cargo_titulo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `registro_profissional` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `assinatura_arquivo` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `assinatura_hash` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `assinatura_atualizada_em` datetime DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `excluido_em` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_responsaveis_assinatura_uuid` (`uuid`),
  UNIQUE KEY `uk_responsavel_assinatura_usuario` (`usuario_id`),
  KEY `idx_responsavel_assinatura_email` (`email`),
  KEY `idx_responsaveis_ativo_excluido` (`ativo`,`excluido_em`),
  CONSTRAINT `fk_responsavel_assinatura_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `responsaveis_assinatura`
--

LOCK TABLES `responsaveis_assinatura` WRITE;
/*!40000 ALTER TABLE `responsaveis_assinatura` DISABLE KEYS */;
INSERT INTO `responsaveis_assinatura` VALUES (2,'b15d9a16-b14f-11f1-8a7c-be2fb1f77be2','Victal Donanzan','383.034.518-63','neto@amazonnaval.com.br','dd121661-feb4-42f6-895a-68eb0608d1e4','Engenheiro Naval','CREA: 22.537','storage/private/assinaturas_responsaveis/2/20260720_100109_90048b1dd4c51d95.png','09da23f7c13fbfbf42c88f65ff2208903086c13f3ed5022813784e45a94bdd13','2026-07-20 13:01:09',1,NULL,'2026-07-02 04:58:28','2026-09-15 21:52:06'),(5,'b15da21c-b14f-11f1-8a7c-be2fb1f77be2','João Responsável',NULL,NULL,NULL,'Engenheiro Naval','123456',NULL,NULL,NULL,0,NULL,'2026-07-02 17:39:46','2026-09-15 21:52:06'),(6,'b15da321-b14f-11f1-8a7c-be2fb1f77be2','João Responsável',NULL,NULL,NULL,'Engenheiro Naval','123456',NULL,NULL,NULL,0,NULL,'2026-07-02 17:43:53','2026-09-15 21:52:06'),(7,'b15da3b2-b14f-11f1-8a7c-be2fb1f77be2','Osvaldo','278.006.930-90','ronokedas2024@gmail.com','d2a16613-dfa4-4948-8de4-8c802abdf394','Vistoriador','CREA: 22.5888','storage/private/assinaturas_responsaveis/7/20260723_042523_00e24fdbe224bad3.png','09da23f7c13fbfbf42c88f65ff2208903086c13f3ed5022813784e45a94bdd13','2026-07-23 07:25:23',1,NULL,'2026-07-23 07:25:23','2026-09-15 21:52:06');
/*!40000 ALTER TABLE `responsaveis_assinatura` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `schema_migrations`
--

DROP TABLE IF EXISTS `schema_migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `schema_migrations` (
  `versao` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `executado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`versao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `schema_migrations`
--

LOCK TABLES `schema_migrations` WRITE;
/*!40000 ALTER TABLE `schema_migrations` DISABLE KEYS */;
/*!40000 ALTER TABLE `schema_migrations` ENABLE KEYS */;
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
INSERT INTO `sequenciais_documentos` VALUES ('LC',2026,23),('ORC',2026,3),('PROTOCOLO',2026,59),('RAP',2026,39),('REL-V',2026,3),('RNC',2026,68);
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
  `codigo_operacional` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `certificado_modelo` enum('CSN','CNBL','CNARQ') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `preco_padrao` decimal(12,2) NOT NULL DEFAULT '0.00',
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `excluido_em` datetime DEFAULT NULL,
  `criado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_servicos_codigo_operacional` (`codigo_operacional`),
  KEY `idx_servicos_ativo` (`ativo`),
  KEY `idx_servicos_certificado_modelo` (`certificado_modelo`),
  KEY `idx_servicos_ativo_excluido` (`ativo`,`excluido_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `servicos`
--

LOCK TABLES `servicos` WRITE;
/*!40000 ALTER TABLE `servicos` DISABLE KEYS */;
INSERT INTO `servicos` VALUES ('1653484e-b540-47e5-bc76-955b662a9d44','Vistoria Especial NORMAM-202 Teste E3','Descrição atualizada E3',NULL,'CSN',3500.50,1,NULL,'admin','2026-09-15 19:44:15','2026-09-15 19:44:15'),('186fd77f-2ab2-405b-be03-a740b520c595','Vistoria Especial NORMAM-202 Teste E3','Descrição atualizada E3',NULL,'CSN',3500.50,1,NULL,'admin','2026-09-15 19:45:00','2026-09-15 19:45:00'),('4ff145a5-6cc2-4955-855c-40a926d63f9e','Vistoria Especial NORMAM-202 Teste E3','Descrição atualizada E3',NULL,'CSN',3500.50,1,NULL,'admin','2026-09-15 19:41:36','2026-09-15 19:41:36'),('a1d980bd-6ebc-11f1-86ce-7e17ff5f90bf','Análise de Planos Ec1','Analise técnica de planos de embarcação“ Etapa 1','ANALISE_PLANOS_EC1',NULL,2500.00,1,NULL,NULL,'2026-06-23 04:33:07','2026-07-24 03:47:16'),('a1d98b0e-6ebc-11f1-86ce-7e17ff5f90bf','Análise de Planos Ec2','Analise técnica de planos de embarcação“ Etapa 2','ANALISE_PLANOS_EC2',NULL,2500.00,1,NULL,NULL,'2026-06-23 04:33:07','2026-07-24 03:47:16'),('a1d98d8e-6ebc-11f1-86ce-7e17ff5f90bf','Vistoria Inicial Seco','Vistoria inicial realizada com embarcação em seco (estaleiro/dique)',NULL,'CSN',3500.00,1,NULL,NULL,'2026-06-23 04:33:07','2026-07-23 06:52:15'),('a1d98e55-6ebc-11f1-86ce-7e17ff5f90bf','Vistoria Inicial Flutuando','Vistoria inicial realizada com embarcação flutuando',NULL,'CSN',3500.00,1,NULL,NULL,'2026-06-23 04:33:07','2026-07-23 06:52:15'),('a1d98eaf-6ebc-11f1-86ce-7e17ff5f90bf','Vistoria Inicial de Borda Livre','Vistoria inicial para certificação de borda livre',NULL,'CNBL',2800.00,1,NULL,NULL,'2026-06-23 04:33:07','2026-07-23 06:52:15'),('a1d98ef1-6ebc-11f1-86ce-7e17ff5f90bf','Vistoria Inicial de Arqueação','Vistoria inicial para Arqueação',NULL,'CNARQ',3200.00,1,NULL,NULL,'2026-06-23 04:33:07','2026-07-28 01:45:10'),('a1d98f2e-6ebc-11f1-86ce-7e17ff5f90bf','Acompanhamento de Ultrassom','Acompanhamento de ensaios de ultrassom em casco/estruturas',NULL,NULL,1800.00,1,NULL,NULL,'2026-06-23 04:33:07','2026-06-29 06:13:07'),('a1d98f6a-6ebc-11f1-86ce-7e17ff5f90bf','Vistoria Anual','Vistoria anual obrigatória para manutenção de certificados',NULL,NULL,2200.00,1,NULL,NULL,'2026-06-23 04:33:07','2026-06-29 06:15:13'),('a1d99130-6ebc-11f1-86ce-7e17ff5f90bf','Vistoria Anual Periódica','Vistoria anual periodica conforme regulamento da Capitania',NULL,NULL,2500.00,1,NULL,NULL,'2026-06-23 04:33:07','2026-06-29 06:15:50'),('a1d991e9-6ebc-11f1-86ce-7e17ff5f90bf','Vistoria Intermediária','Vistoria intermediaria de meio-ciclo entre renovações',NULL,NULL,3000.00,1,NULL,NULL,'2026-06-23 04:33:07','2026-06-29 06:17:26'),('a1d992d7-6ebc-11f1-86ce-7e17ff5f90bf','Licença Provisória','Emissão de licença provisória para navegação',NULL,NULL,1500.00,1,NULL,NULL,'2026-06-23 04:33:07','2026-06-29 06:14:47'),('fb8791bd-67f7-41e7-a042-4360ba5d8373','Vistoria Especial NORMAM-202 Teste E3','Descrição atualizada E3',NULL,'CSN',3500.50,1,NULL,'admin','2026-09-15 19:42:19','2026-09-15 19:42:19');
/*!40000 ALTER TABLE `servicos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sgq_auditoria_cadastral`
--

DROP TABLE IF EXISTS `sgq_auditoria_cadastral`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sgq_auditoria_cadastral` (
  `id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `entidade_tipo` enum('CLIENTE','PROPRIETARIO','ARMADOR','DESPACHANTE','EMBARCACAO','USUARIO') COLLATE utf8mb4_general_ci NOT NULL,
  `entidade_id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `acao` enum('CRIACAO','ALTERACAO','INATIVACAO') COLLATE utf8mb4_general_ci NOT NULL,
  `dados_anteriores` json DEFAULT NULL,
  `dados_posteriores` json DEFAULT NULL,
  `campos_alterados` json DEFAULT NULL,
  `motivo_justificativa` text COLLATE utf8mb4_general_ci,
  `usuario_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `usuario_nome` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ip_origem` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_auditoria_entidade` (`entidade_tipo`,`entidade_id`),
  KEY `idx_auditoria_criado_em` (`criado_em`),
  KEY `idx_auditoria_usuario` (`usuario_id`),
  KEY `idx_auditoria_entidade_data` (`entidade_tipo`,`criado_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sgq_auditoria_cadastral`
--

LOCK TABLES `sgq_auditoria_cadastral` WRITE;
/*!40000 ALTER TABLE `sgq_auditoria_cadastral` DISABLE KEYS */;
INSERT INTO `sgq_auditoria_cadastral` VALUES ('00725a62-9a64-4a73-86eb-d7453bcbdd3f','EMBARCACAO','f4243b73-58a2-496e-ab81-fecdc495f101','CRIACAO',NULL,'{\":id\": \"f4243b73-58a2-496e-ab81-fecdc495f101\", \":ano\": 2022, \":nome\": \"B/M SOLIMÕES EXPRESS 104\", \":registro\": null, \":criado_por\": \"dd121661-feb4-42f6-895a-68eb0608d1e4\", \":boca_maxima\": null, \":observacoes\": null, \":porte_bruto\": null, \":potencia_kw\": \"330\", \":boca_moldada\": 6.4, \":modelo_motor\": \"DS11\", \":numero_casco\": null, \":numero_motor\": \"SC-998877\", \":tipo_servico\": null, \":acessibilidade\": null, \":area_navegacao\": \"Área 1 e 2\", \":borda_livre_mm\": null, \":estaleiro_nome\": null, \":material_casco\": \"Aço\", \":pontal_moldado\": 2.1, \":tipo_navegacao\": \"Interior\", \":arqueacao_bruta\": \"115.00\", \":calado_maximo_m\": 1.5, \":comprimento_lpp\": null, \":obs_passageiros\": null, \":porto_inscricao\": \"Manaus-AM\", \":tipo_embarcacao\": \"Balsa\", \":autorizado_carga\": null, \":borda_livre_tipo\": null, \":fabricante_motor\": \"Scania\", \":local_construcao\": null, \":metodo_arqueacao\": null, \":numero_inscricao\": \"021-378197-WEB\", \":possui_propulsao\": 1, \":arqueacao_liquida\": null, \":cnarq_data_quilha\": null, \":comprimento_casco\": null, \":comprimento_total\": 28.5, \":estaleiro_cpf_cnpj\": null, \":estaleiro_endereco\": null, \":indicativo_chamada\": null, \":numero_tripulantes\": null, \":tipo_embarcacao_id\": \"06a95b60-75d0-11f1-98f0-5ed0db5eacb7\", \":cnbl_area_navegacao\": null, \":centro_disco_situado\": null, \":cnbl_tipo_embarcacao\": null, \":numero_passageiros_n1\": null, \":numero_passageiros_n2\": null, \":acrescimo_agua_salgada\": null, \":cnarq_calado_moldado_m\": null, \":marca_linha_carga_area1\": null, \":marca_linha_carga_area2\": null, \":cnarq_espacos_excluidos_m3\": null, \":cnarq_espacos_incluidos_ab\": null, \":cnarq_espacos_incluidos_al\": null, \":dist_linha_conves_bico_proa\": null, \":aresta_superior_linha_conves\": null, \":dist_linha_conves_abaixo_disco\": null, \":cnarq_data_local_arqueacao_original\": null, \":cnarq_data_local_ultima_rearqueacao\": null}','[\":nome\", \":registro\", \":tipo_embarcacao_id\", \":tipo_embarcacao\", \":cnbl_tipo_embarcacao\", \":ano\", \":porto_inscricao\", \":numero_inscricao\", \":indicativo_chamada\", \":observacoes\", \":possui_propulsao\", \":fabricante_motor\", \":modelo_motor\", \":numero_motor\", \":potencia_kw\", \":material_casco\", \":tipo_navegacao\", \":area_navegacao\", \":cnbl_area_navegacao\", \":tipo_servico\", \":autorizado_carga\", \":numero_tripulantes\", \":numero_passageiros_n1\", \":numero_passageiros_n2\", \":obs_passageiros\", \":acessibilidade\", \":comprimento_total\", \":comprimento_casco\", \":comprimento_lpp\", \":pontal_moldado\", \":boca_moldada\", \":boca_maxima\", \":arqueacao_bruta\", \":arqueacao_liquida\", \":metodo_arqueacao\", \":cnarq_data_quilha\", \":cnarq_calado_moldado_m\", \":cnarq_espacos_incluidos_ab\", \":cnarq_espacos_incluidos_al\", \":cnarq_espacos_excluidos_m3\", \":cnarq_data_local_arqueacao_original\", \":cnarq_data_local_ultima_rearqueacao\", \":local_construcao\", \":numero_casco\", \":porte_bruto\", \":estaleiro_nome\", \":estaleiro_cpf_cnpj\", \":estaleiro_endereco\", \":borda_livre_mm\", \":borda_livre_tipo\", \":calado_maximo_m\", \":aresta_superior_linha_conves\", \":centro_disco_situado\", \":acrescimo_agua_salgada\", \":dist_linha_conves_bico_proa\", \":dist_linha_conves_abaixo_disco\", \":marca_linha_carga_area1\", \":marca_linha_carga_area2\", \":id\", \":criado_por\"]','Cadastro inicial da embarcação','dd121661-feb4-42f6-895a-68eb0608d1e4','admin','127.0.0.1','','2026-09-15 23:15:03'),('013a9744-d3a4-424a-b053-e62db5b36502','EMBARCACAO','emb-teste-sgq','ALTERACAO','{\"nome\": \"Barco Antigo\", \"arqueacao_bruta\": \"10\"}','{\"nome\": \"Barco Novo\", \"arqueacao_bruta\": \"15\"}','[\"nome\", \"arqueacao_bruta\"]','Teste de auditoria isolada da Etapa 7','u-admin-test','Administrador Teste','127.0.0.1','','2026-09-15 21:34:50'),('014749ec-0fb5-4e28-a387-63cf8d92b2a4','EMBARCACAO','c72ee837-94d8-4fe7-8ac9-63edc8ae4c73','CRIACAO',NULL,'{\":id\": \"c72ee837-94d8-4fe7-8ac9-63edc8ae4c73\", \":ano\": 2022, \":nome\": \"B/M SOLIMÕES EXPRESS 834\", \":registro\": null, \":criado_por\": \"dd121661-feb4-42f6-895a-68eb0608d1e4\", \":boca_maxima\": null, \":observacoes\": null, \":porte_bruto\": null, \":potencia_kw\": \"330\", \":boca_moldada\": 6.4, \":modelo_motor\": \"DS11\", \":numero_casco\": null, \":numero_motor\": \"SC-998877\", \":tipo_servico\": null, \":acessibilidade\": null, \":area_navegacao\": \"Área 1 e 2\", \":borda_livre_mm\": null, \":estaleiro_nome\": null, \":material_casco\": \"Aço\", \":pontal_moldado\": 2.1, \":tipo_navegacao\": \"Interior\", \":arqueacao_bruta\": \"115.00\", \":calado_maximo_m\": 1.5, \":comprimento_lpp\": null, \":obs_passageiros\": null, \":porto_inscricao\": \"Manaus-AM\", \":tipo_embarcacao\": \"Balsa\", \":autorizado_carga\": null, \":borda_livre_tipo\": null, \":fabricante_motor\": \"Scania\", \":local_construcao\": null, \":metodo_arqueacao\": null, \":numero_inscricao\": \"021-315275-WEB\", \":possui_propulsao\": 1, \":arqueacao_liquida\": null, \":cnarq_data_quilha\": null, \":comprimento_casco\": null, \":comprimento_total\": 28.5, \":estaleiro_cpf_cnpj\": null, \":estaleiro_endereco\": null, \":indicativo_chamada\": null, \":numero_tripulantes\": null, \":tipo_embarcacao_id\": \"06a95b60-75d0-11f1-98f0-5ed0db5eacb7\", \":cnbl_area_navegacao\": null, \":centro_disco_situado\": null, \":cnbl_tipo_embarcacao\": null, \":numero_passageiros_n1\": null, \":numero_passageiros_n2\": null, \":acrescimo_agua_salgada\": null, \":cnarq_calado_moldado_m\": null, \":marca_linha_carga_area1\": null, \":marca_linha_carga_area2\": null, \":cnarq_espacos_excluidos_m3\": null, \":cnarq_espacos_incluidos_ab\": null, \":cnarq_espacos_incluidos_al\": null, \":dist_linha_conves_bico_proa\": null, \":aresta_superior_linha_conves\": null, \":dist_linha_conves_abaixo_disco\": null, \":cnarq_data_local_arqueacao_original\": null, \":cnarq_data_local_ultima_rearqueacao\": null}','[\":nome\", \":registro\", \":tipo_embarcacao_id\", \":tipo_embarcacao\", \":cnbl_tipo_embarcacao\", \":ano\", \":porto_inscricao\", \":numero_inscricao\", \":indicativo_chamada\", \":observacoes\", \":possui_propulsao\", \":fabricante_motor\", \":modelo_motor\", \":numero_motor\", \":potencia_kw\", \":material_casco\", \":tipo_navegacao\", \":area_navegacao\", \":cnbl_area_navegacao\", \":tipo_servico\", \":autorizado_carga\", \":numero_tripulantes\", \":numero_passageiros_n1\", \":numero_passageiros_n2\", \":obs_passageiros\", \":acessibilidade\", \":comprimento_total\", \":comprimento_casco\", \":comprimento_lpp\", \":pontal_moldado\", \":boca_moldada\", \":boca_maxima\", \":arqueacao_bruta\", \":arqueacao_liquida\", \":metodo_arqueacao\", \":cnarq_data_quilha\", \":cnarq_calado_moldado_m\", \":cnarq_espacos_incluidos_ab\", \":cnarq_espacos_incluidos_al\", \":cnarq_espacos_excluidos_m3\", \":cnarq_data_local_arqueacao_original\", \":cnarq_data_local_ultima_rearqueacao\", \":local_construcao\", \":numero_casco\", \":porte_bruto\", \":estaleiro_nome\", \":estaleiro_cpf_cnpj\", \":estaleiro_endereco\", \":borda_livre_mm\", \":borda_livre_tipo\", \":calado_maximo_m\", \":aresta_superior_linha_conves\", \":centro_disco_situado\", \":acrescimo_agua_salgada\", \":dist_linha_conves_bico_proa\", \":dist_linha_conves_abaixo_disco\", \":marca_linha_carga_area1\", \":marca_linha_carga_area2\", \":id\", \":criado_por\"]','Cadastro inicial da embarcação','dd121661-feb4-42f6-895a-68eb0608d1e4','admin','127.0.0.1','','2026-09-15 21:41:32'),('047c2262-1def-46a2-b5d6-1a7c8619c5b8','EMBARCACAO','emb-teste-sgq','ALTERACAO','{\"nome\": \"Barco Antigo\", \"arqueacao_bruta\": \"10\"}','{\"nome\": \"Barco Novo\", \"arqueacao_bruta\": \"15\"}','[\"nome\", \"arqueacao_bruta\"]','Teste de auditoria isolada da Etapa 7','u-admin-test','Administrador Teste','127.0.0.1','','2026-09-15 21:23:51'),('07ea9872-dfb4-41b5-a337-3dca41cff546','CLIENTE','d9a02d20-b84c-43c4-a87c-59fc9f18760d','CRIACAO',NULL,'{\"nome\": \"Armador Solimões Web Test 1374\", \"email\": \"web_1809@solimoes.com.br\", \"perfil\": \"armador\", \"cpf_cnpj\": \"32570538000128\", \"endereco\": \"Av. Manaus Moderna, 500 - Centro, Manaus/AM\", \"telefone\": \"(92) 99123-4567\", \"tipo_pessoa\": \"PJ\"}','[\"nome\", \"tipo_pessoa\", \"cpf_cnpj\", \"perfil\", \"telefone\", \"email\", \"endereco\"]','Cadastro de armador no sistema','dd121661-feb4-42f6-895a-68eb0608d1e4','admin','127.0.0.1','','2026-09-15 21:46:12'),('08c4f7c6-2a1a-4ba1-b689-6dea785c42ef','EMBARCACAO','2144ec71-01d0-46dc-9b45-f249080c93d1','CRIACAO',NULL,'{\":id\": \"2144ec71-01d0-46dc-9b45-f249080c93d1\", \":ano\": 2022, \":nome\": \"B/M SOLIMÕES EXPRESS 970\", \":registro\": null, \":criado_por\": \"dd121661-feb4-42f6-895a-68eb0608d1e4\", \":boca_maxima\": null, \":observacoes\": null, \":porte_bruto\": null, \":potencia_kw\": \"330\", \":boca_moldada\": 6.4, \":modelo_motor\": \"DS11\", \":numero_casco\": null, \":numero_motor\": \"SC-998877\", \":tipo_servico\": null, \":acessibilidade\": null, \":area_navegacao\": \"Área 1 e 2\", \":borda_livre_mm\": null, \":estaleiro_nome\": null, \":material_casco\": \"Aço\", \":pontal_moldado\": 2.1, \":tipo_navegacao\": \"Interior\", \":arqueacao_bruta\": \"115.00\", \":calado_maximo_m\": 1.5, \":comprimento_lpp\": null, \":obs_passageiros\": null, \":porto_inscricao\": \"Manaus-AM\", \":tipo_embarcacao\": \"Balsa\", \":autorizado_carga\": null, \":borda_livre_tipo\": null, \":fabricante_motor\": \"Scania\", \":local_construcao\": null, \":metodo_arqueacao\": null, \":numero_inscricao\": \"021-499480-WEB\", \":possui_propulsao\": 1, \":arqueacao_liquida\": null, \":cnarq_data_quilha\": null, \":comprimento_casco\": null, \":comprimento_total\": 28.5, \":estaleiro_cpf_cnpj\": null, \":estaleiro_endereco\": null, \":indicativo_chamada\": null, \":numero_tripulantes\": null, \":tipo_embarcacao_id\": \"06a95b60-75d0-11f1-98f0-5ed0db5eacb7\", \":cnbl_area_navegacao\": null, \":centro_disco_situado\": null, \":cnbl_tipo_embarcacao\": null, \":numero_passageiros_n1\": null, \":numero_passageiros_n2\": null, \":acrescimo_agua_salgada\": null, \":cnarq_calado_moldado_m\": null, \":marca_linha_carga_area1\": null, \":marca_linha_carga_area2\": null, \":cnarq_espacos_excluidos_m3\": null, \":cnarq_espacos_incluidos_ab\": null, \":cnarq_espacos_incluidos_al\": null, \":dist_linha_conves_bico_proa\": null, \":aresta_superior_linha_conves\": null, \":dist_linha_conves_abaixo_disco\": null, \":cnarq_data_local_arqueacao_original\": null, \":cnarq_data_local_ultima_rearqueacao\": null}','[\":nome\", \":registro\", \":tipo_embarcacao_id\", \":tipo_embarcacao\", \":cnbl_tipo_embarcacao\", \":ano\", \":porto_inscricao\", \":numero_inscricao\", \":indicativo_chamada\", \":observacoes\", \":possui_propulsao\", \":fabricante_motor\", \":modelo_motor\", \":numero_motor\", \":potencia_kw\", \":material_casco\", \":tipo_navegacao\", \":area_navegacao\", \":cnbl_area_navegacao\", \":tipo_servico\", \":autorizado_carga\", \":numero_tripulantes\", \":numero_passageiros_n1\", \":numero_passageiros_n2\", \":obs_passageiros\", \":acessibilidade\", \":comprimento_total\", \":comprimento_casco\", \":comprimento_lpp\", \":pontal_moldado\", \":boca_moldada\", \":boca_maxima\", \":arqueacao_bruta\", \":arqueacao_liquida\", \":metodo_arqueacao\", \":cnarq_data_quilha\", \":cnarq_calado_moldado_m\", \":cnarq_espacos_incluidos_ab\", \":cnarq_espacos_incluidos_al\", \":cnarq_espacos_excluidos_m3\", \":cnarq_data_local_arqueacao_original\", \":cnarq_data_local_ultima_rearqueacao\", \":local_construcao\", \":numero_casco\", \":porte_bruto\", \":estaleiro_nome\", \":estaleiro_cpf_cnpj\", \":estaleiro_endereco\", \":borda_livre_mm\", \":borda_livre_tipo\", \":calado_maximo_m\", \":aresta_superior_linha_conves\", \":centro_disco_situado\", \":acrescimo_agua_salgada\", \":dist_linha_conves_bico_proa\", \":dist_linha_conves_abaixo_disco\", \":marca_linha_carga_area1\", \":marca_linha_carga_area2\", \":id\", \":criado_por\"]','Cadastro inicial da embarcação','dd121661-feb4-42f6-895a-68eb0608d1e4','admin','127.0.0.1','','2026-09-16 00:25:06'),('0c5f22ad-be3b-4843-81ae-88f09a0708d9','CLIENTE','867586ac-ec9f-44f1-b60e-03b587ca03b1','CRIACAO',NULL,'{\"nome\": \"Armador Solimões Web Test 6511\", \"email\": \"web_5693@solimoes.com.br\", \"perfil\": \"armador\", \"cpf_cnpj\": \"69895755000106\", \"endereco\": \"Av. Manaus Moderna, 500 - Centro, Manaus/AM\", \"telefone\": \"(92) 99123-4567\", \"tipo_pessoa\": \"PJ\"}','[\"nome\", \"tipo_pessoa\", \"cpf_cnpj\", \"perfil\", \"telefone\", \"email\", \"endereco\"]','Cadastro de armador no sistema','dd121661-feb4-42f6-895a-68eb0608d1e4','admin','127.0.0.1','','2026-09-15 21:46:43'),('15654ce1-4cba-42c1-a2df-930399c66afc','EMBARCACAO','c116cb68-0943-4e7f-93e3-66a6d2678bb6','CRIACAO',NULL,'{\":id\": \"c116cb68-0943-4e7f-93e3-66a6d2678bb6\", \":ano\": 2022, \":nome\": \"B/M SOLIMÕES EXPRESS 228\", \":registro\": null, \":criado_por\": \"dd121661-feb4-42f6-895a-68eb0608d1e4\", \":boca_maxima\": null, \":observacoes\": null, \":porte_bruto\": null, \":potencia_kw\": \"330\", \":boca_moldada\": 6.4, \":modelo_motor\": \"DS11\", \":numero_casco\": null, \":numero_motor\": \"SC-998877\", \":tipo_servico\": null, \":acessibilidade\": null, \":area_navegacao\": \"Área 1 e 2\", \":borda_livre_mm\": null, \":estaleiro_nome\": null, \":material_casco\": \"Aço\", \":pontal_moldado\": 2.1, \":tipo_navegacao\": \"Interior\", \":arqueacao_bruta\": \"115.00\", \":calado_maximo_m\": 1.5, \":comprimento_lpp\": null, \":obs_passageiros\": null, \":porto_inscricao\": \"Manaus-AM\", \":tipo_embarcacao\": \"Balsa\", \":autorizado_carga\": null, \":borda_livre_tipo\": null, \":fabricante_motor\": \"Scania\", \":local_construcao\": null, \":metodo_arqueacao\": null, \":numero_inscricao\": \"021-385417-WEB\", \":possui_propulsao\": 1, \":arqueacao_liquida\": null, \":cnarq_data_quilha\": null, \":comprimento_casco\": null, \":comprimento_total\": 28.5, \":estaleiro_cpf_cnpj\": null, \":estaleiro_endereco\": null, \":indicativo_chamada\": null, \":numero_tripulantes\": null, \":tipo_embarcacao_id\": \"06a95b60-75d0-11f1-98f0-5ed0db5eacb7\", \":cnbl_area_navegacao\": null, \":centro_disco_situado\": null, \":cnbl_tipo_embarcacao\": null, \":numero_passageiros_n1\": null, \":numero_passageiros_n2\": null, \":acrescimo_agua_salgada\": null, \":cnarq_calado_moldado_m\": null, \":marca_linha_carga_area1\": null, \":marca_linha_carga_area2\": null, \":cnarq_espacos_excluidos_m3\": null, \":cnarq_espacos_incluidos_ab\": null, \":cnarq_espacos_incluidos_al\": null, \":dist_linha_conves_bico_proa\": null, \":aresta_superior_linha_conves\": null, \":dist_linha_conves_abaixo_disco\": null, \":cnarq_data_local_arqueacao_original\": null, \":cnarq_data_local_ultima_rearqueacao\": null}','[\":nome\", \":registro\", \":tipo_embarcacao_id\", \":tipo_embarcacao\", \":cnbl_tipo_embarcacao\", \":ano\", \":porto_inscricao\", \":numero_inscricao\", \":indicativo_chamada\", \":observacoes\", \":possui_propulsao\", \":fabricante_motor\", \":modelo_motor\", \":numero_motor\", \":potencia_kw\", \":material_casco\", \":tipo_navegacao\", \":area_navegacao\", \":cnbl_area_navegacao\", \":tipo_servico\", \":autorizado_carga\", \":numero_tripulantes\", \":numero_passageiros_n1\", \":numero_passageiros_n2\", \":obs_passageiros\", \":acessibilidade\", \":comprimento_total\", \":comprimento_casco\", \":comprimento_lpp\", \":pontal_moldado\", \":boca_moldada\", \":boca_maxima\", \":arqueacao_bruta\", \":arqueacao_liquida\", \":metodo_arqueacao\", \":cnarq_data_quilha\", \":cnarq_calado_moldado_m\", \":cnarq_espacos_incluidos_ab\", \":cnarq_espacos_incluidos_al\", \":cnarq_espacos_excluidos_m3\", \":cnarq_data_local_arqueacao_original\", \":cnarq_data_local_ultima_rearqueacao\", \":local_construcao\", \":numero_casco\", \":porte_bruto\", \":estaleiro_nome\", \":estaleiro_cpf_cnpj\", \":estaleiro_endereco\", \":borda_livre_mm\", \":borda_livre_tipo\", \":calado_maximo_m\", \":aresta_superior_linha_conves\", \":centro_disco_situado\", \":acrescimo_agua_salgada\", \":dist_linha_conves_bico_proa\", \":dist_linha_conves_abaixo_disco\", \":marca_linha_carga_area1\", \":marca_linha_carga_area2\", \":id\", \":criado_por\"]','Cadastro inicial da embarcação','dd121661-feb4-42f6-895a-68eb0608d1e4','admin','127.0.0.1','','2026-09-16 00:05:56'),('17e1c84b-fc47-4ef9-a1dd-37d61fef16eb','CLIENTE','f4534ddc-3966-43ec-aa74-70fcad4bcd35','CRIACAO',NULL,'{\"nome\": \"Armador Solimões Web Test 4081\", \"email\": \"web_8829@solimoes.com.br\", \"perfil\": \"armador\", \"cpf_cnpj\": \"52684611000108\", \"endereco\": \"Av. Manaus Moderna, 500 - Centro, Manaus/AM\", \"telefone\": \"(92) 99123-4567\", \"tipo_pessoa\": \"PJ\"}','[\"nome\", \"tipo_pessoa\", \"cpf_cnpj\", \"perfil\", \"telefone\", \"email\", \"endereco\"]','Cadastro de armador no sistema','dd121661-feb4-42f6-895a-68eb0608d1e4','admin','127.0.0.1','','2026-09-15 21:40:53'),('1f7844e5-8621-4b05-a957-9a7194b9de01','CLIENTE','1041bc33-843c-4d82-8a09-4f413cfaddcf','CRIACAO',NULL,'{\"nome\": \"Armador Solimões Web Test 2673\", \"email\": \"web_7136@solimoes.com.br\", \"perfil\": \"armador\", \"cpf_cnpj\": \"86091655000138\", \"endereco\": \"Av. Manaus Moderna, 500 - Centro, Manaus/AM\", \"telefone\": \"(92) 99123-4567\", \"tipo_pessoa\": \"PJ\"}','[\"nome\", \"tipo_pessoa\", \"cpf_cnpj\", \"perfil\", \"telefone\", \"email\", \"endereco\"]','Cadastro de armador no sistema','dd121661-feb4-42f6-895a-68eb0608d1e4','admin','127.0.0.1','','2026-09-15 21:45:26'),('24d27df3-cca7-4596-af96-8f2ae2db8d07','CLIENTE','7e3eef23-baa2-493e-bf1b-777f918549bf','CRIACAO',NULL,'{\"nome\": \"Armador Solimões Web Test 8819\", \"email\": \"web_9519@solimoes.com.br\", \"perfil\": \"armador\", \"cpf_cnpj\": \"83695428000123\", \"endereco\": \"Av. Manaus Moderna, 500 - Centro, Manaus/AM\", \"telefone\": \"(92) 99123-4567\", \"tipo_pessoa\": \"PJ\"}','[\"nome\", \"tipo_pessoa\", \"cpf_cnpj\", \"perfil\", \"telefone\", \"email\", \"endereco\"]','Cadastro de armador no sistema','dd121661-feb4-42f6-895a-68eb0608d1e4','admin','127.0.0.1','','2026-09-15 21:45:35'),('29dc5929-9144-4e3a-a4e8-a121a3b31f7e','EMBARCACAO','emb-teste-sgq','ALTERACAO','{\"nome\": \"Barco Antigo\", \"arqueacao_bruta\": \"10\"}','{\"nome\": \"Barco Novo\", \"arqueacao_bruta\": \"15\"}','[\"nome\", \"arqueacao_bruta\"]','Teste de auditoria isolada da Etapa 7','u-admin-test','Administrador Teste','127.0.0.1','','2026-09-15 20:04:15'),('2b47dff2-d1e8-49e9-ad04-b2f8802d4a5a','EMBARCACAO','emb-teste-sgq','ALTERACAO','{\"nome\": \"Barco Antigo\", \"arqueacao_bruta\": \"10\"}','{\"nome\": \"Barco Novo\", \"arqueacao_bruta\": \"15\"}','[\"nome\", \"arqueacao_bruta\"]','Teste de auditoria isolada da Etapa 7','u-admin-test','Administrador Teste','127.0.0.1','','2026-09-16 00:05:48'),('321db619-e26a-49a0-ba80-eb2653e70b34','EMBARCACAO','emb-teste-sgq','ALTERACAO','{\"nome\": \"Barco Antigo\", \"arqueacao_bruta\": \"10\"}','{\"nome\": \"Barco Novo\", \"arqueacao_bruta\": \"15\"}','[\"nome\", \"arqueacao_bruta\"]','Teste de auditoria isolada da Etapa 7','u-admin-test','Administrador Teste','127.0.0.1','','2026-09-15 20:05:15'),('33bb9b8e-d99d-44b9-b870-b82c56bb557e','CLIENTE','cb63d1fd-d0b3-47c0-abf1-6914808b8ff1','CRIACAO',NULL,'{\"nome\": \"Armador Solimões Web Test 4663\", \"email\": \"web_4147@solimoes.com.br\", \"perfil\": \"armador\", \"cpf_cnpj\": \"46300277000130\", \"endereco\": \"Av. Manaus Moderna, 500 - Centro, Manaus/AM\", \"telefone\": \"(92) 99123-4567\", \"tipo_pessoa\": \"PJ\"}','[\"nome\", \"tipo_pessoa\", \"cpf_cnpj\", \"perfil\", \"telefone\", \"email\", \"endereco\"]','Cadastro de armador no sistema','dd121661-feb4-42f6-895a-68eb0608d1e4','admin','127.0.0.1','','2026-09-15 21:41:32'),('3a383b37-7d41-41a8-be13-d33869f1750e','EMBARCACAO','emb-teste-sgq','ALTERACAO','{\"nome\": \"Barco Antigo\", \"arqueacao_bruta\": \"10\"}','{\"nome\": \"Barco Novo\", \"arqueacao_bruta\": \"15\"}','[\"nome\", \"arqueacao_bruta\"]','Teste de auditoria isolada da Etapa 7','u-admin-test','Administrador Teste','127.0.0.1','','2026-09-15 20:05:38'),('3bbd8f24-d883-4e72-9db7-af3067348a64','CLIENTE','65d29f6f-5978-4157-9cb1-8d6c9d49d124','CRIACAO',NULL,'{\"nome\": \"Armador Solimões Web Test 4374\", \"email\": \"web_5875@solimoes.com.br\", \"perfil\": \"armador\", \"cpf_cnpj\": \"37800516000120\", \"endereco\": \"Av. Manaus Moderna, 500 - Centro, Manaus/AM\", \"telefone\": \"(92) 99123-4567\", \"tipo_pessoa\": \"PJ\"}','[\"nome\", \"tipo_pessoa\", \"cpf_cnpj\", \"perfil\", \"telefone\", \"email\", \"endereco\"]','Cadastro de armador no sistema','dd121661-feb4-42f6-895a-68eb0608d1e4','admin','127.0.0.1','','2026-09-15 21:45:09'),('3bcebb15-252d-431a-aaee-84d52cd99469','EMBARCACAO','234aaec1-d1dd-4872-94b3-f52c61b04316','CRIACAO',NULL,'{\":id\": \"234aaec1-d1dd-4872-94b3-f52c61b04316\", \":ano\": 2022, \":nome\": \"B/M SOLIMÕES EXPRESS 883\", \":registro\": null, \":criado_por\": \"dd121661-feb4-42f6-895a-68eb0608d1e4\", \":boca_maxima\": null, \":observacoes\": null, \":porte_bruto\": null, \":potencia_kw\": \"330\", \":boca_moldada\": 6.4, \":modelo_motor\": \"DS11\", \":numero_casco\": null, \":numero_motor\": \"SC-998877\", \":tipo_servico\": null, \":acessibilidade\": null, \":area_navegacao\": \"Área 1 e 2\", \":borda_livre_mm\": null, \":estaleiro_nome\": null, \":material_casco\": \"Aço\", \":pontal_moldado\": 2.1, \":tipo_navegacao\": \"Interior\", \":arqueacao_bruta\": \"115.00\", \":calado_maximo_m\": 1.5, \":comprimento_lpp\": null, \":obs_passageiros\": null, \":porto_inscricao\": \"Manaus-AM\", \":tipo_embarcacao\": \"Balsa\", \":autorizado_carga\": null, \":borda_livre_tipo\": null, \":fabricante_motor\": \"Scania\", \":local_construcao\": null, \":metodo_arqueacao\": null, \":numero_inscricao\": \"021-507232-WEB\", \":possui_propulsao\": 1, \":arqueacao_liquida\": null, \":cnarq_data_quilha\": null, \":comprimento_casco\": null, \":comprimento_total\": 28.5, \":estaleiro_cpf_cnpj\": null, \":estaleiro_endereco\": null, \":indicativo_chamada\": null, \":numero_tripulantes\": null, \":tipo_embarcacao_id\": \"06a95b60-75d0-11f1-98f0-5ed0db5eacb7\", \":cnbl_area_navegacao\": null, \":centro_disco_situado\": null, \":cnbl_tipo_embarcacao\": null, \":numero_passageiros_n1\": null, \":numero_passageiros_n2\": null, \":acrescimo_agua_salgada\": null, \":cnarq_calado_moldado_m\": null, \":marca_linha_carga_area1\": null, \":marca_linha_carga_area2\": null, \":cnarq_espacos_excluidos_m3\": null, \":cnarq_espacos_incluidos_ab\": null, \":cnarq_espacos_incluidos_al\": null, \":dist_linha_conves_bico_proa\": null, \":aresta_superior_linha_conves\": null, \":dist_linha_conves_abaixo_disco\": null, \":cnarq_data_local_arqueacao_original\": null, \":cnarq_data_local_ultima_rearqueacao\": null}','[\":nome\", \":registro\", \":tipo_embarcacao_id\", \":tipo_embarcacao\", \":cnbl_tipo_embarcacao\", \":ano\", \":porto_inscricao\", \":numero_inscricao\", \":indicativo_chamada\", \":observacoes\", \":possui_propulsao\", \":fabricante_motor\", \":modelo_motor\", \":numero_motor\", \":potencia_kw\", \":material_casco\", \":tipo_navegacao\", \":area_navegacao\", \":cnbl_area_navegacao\", \":tipo_servico\", \":autorizado_carga\", \":numero_tripulantes\", \":numero_passageiros_n1\", \":numero_passageiros_n2\", \":obs_passageiros\", \":acessibilidade\", \":comprimento_total\", \":comprimento_casco\", \":comprimento_lpp\", \":pontal_moldado\", \":boca_moldada\", \":boca_maxima\", \":arqueacao_bruta\", \":arqueacao_liquida\", \":metodo_arqueacao\", \":cnarq_data_quilha\", \":cnarq_calado_moldado_m\", \":cnarq_espacos_incluidos_ab\", \":cnarq_espacos_incluidos_al\", \":cnarq_espacos_excluidos_m3\", \":cnarq_data_local_arqueacao_original\", \":cnarq_data_local_ultima_rearqueacao\", \":local_construcao\", \":numero_casco\", \":porte_bruto\", \":estaleiro_nome\", \":estaleiro_cpf_cnpj\", \":estaleiro_endereco\", \":borda_livre_mm\", \":borda_livre_tipo\", \":calado_maximo_m\", \":aresta_superior_linha_conves\", \":centro_disco_situado\", \":acrescimo_agua_salgada\", \":dist_linha_conves_bico_proa\", \":dist_linha_conves_abaixo_disco\", \":marca_linha_carga_area1\", \":marca_linha_carga_area2\", \":id\", \":criado_por\"]','Cadastro inicial da embarcação','dd121661-feb4-42f6-895a-68eb0608d1e4','admin','127.0.0.1','','2026-09-15 21:44:42'),('41a72de3-c012-4e09-8ee6-9d7e816f1955','CLIENTE','1a20ae66-fc24-4156-85ef-3a9de7d09265','CRIACAO',NULL,'{\"nome\": \"Armador Solimões Web Test 4348\", \"email\": \"web_5593@solimoes.com.br\", \"perfil\": \"armador\", \"cpf_cnpj\": \"67205272000107\", \"endereco\": \"Av. Manaus Moderna, 500 - Centro, Manaus/AM\", \"telefone\": \"(92) 99123-4567\", \"tipo_pessoa\": \"PJ\"}','[\"nome\", \"tipo_pessoa\", \"cpf_cnpj\", \"perfil\", \"telefone\", \"email\", \"endereco\"]','Cadastro de armador no sistema','dd121661-feb4-42f6-895a-68eb0608d1e4','admin','127.0.0.1','','2026-09-15 21:47:12'),('44e6b422-889d-47f7-a9ab-89ee0b6a25a8','EMBARCACAO','emb-teste-sgq','ALTERACAO','{\"nome\": \"Barco Antigo\", \"arqueacao_bruta\": \"10\"}','{\"nome\": \"Barco Novo\", \"arqueacao_bruta\": \"15\"}','[\"nome\", \"arqueacao_bruta\"]','Teste de auditoria isolada da Etapa 7','u-admin-test','Administrador Teste','127.0.0.1','','2026-09-15 21:36:08'),('47e86524-16f9-49d1-886d-c217a7e44d03','EMBARCACAO','f551641e-bd00-4812-b9ca-a320cccbb283','CRIACAO',NULL,'{\":id\": \"f551641e-bd00-4812-b9ca-a320cccbb283\", \":ano\": 2022, \":nome\": \"B/M SOLIMÕES EXPRESS 142\", \":registro\": null, \":criado_por\": \"dd121661-feb4-42f6-895a-68eb0608d1e4\", \":boca_maxima\": null, \":observacoes\": null, \":porte_bruto\": null, \":potencia_kw\": \"330\", \":boca_moldada\": 6.4, \":modelo_motor\": \"DS11\", \":numero_casco\": null, \":numero_motor\": \"SC-998877\", \":tipo_servico\": null, \":acessibilidade\": null, \":area_navegacao\": \"Área 1 e 2\", \":borda_livre_mm\": null, \":estaleiro_nome\": null, \":material_casco\": \"Aço\", \":pontal_moldado\": 2.1, \":tipo_navegacao\": \"Interior\", \":arqueacao_bruta\": \"115.00\", \":calado_maximo_m\": 1.5, \":comprimento_lpp\": null, \":obs_passageiros\": null, \":porto_inscricao\": \"Manaus-AM\", \":tipo_embarcacao\": \"Balsa\", \":autorizado_carga\": null, \":borda_livre_tipo\": null, \":fabricante_motor\": \"Scania\", \":local_construcao\": null, \":metodo_arqueacao\": null, \":numero_inscricao\": \"021-355235-WEB\", \":possui_propulsao\": 1, \":arqueacao_liquida\": null, \":cnarq_data_quilha\": null, \":comprimento_casco\": null, \":comprimento_total\": 28.5, \":estaleiro_cpf_cnpj\": null, \":estaleiro_endereco\": null, \":indicativo_chamada\": null, \":numero_tripulantes\": null, \":tipo_embarcacao_id\": \"06a95b60-75d0-11f1-98f0-5ed0db5eacb7\", \":cnbl_area_navegacao\": null, \":centro_disco_situado\": null, \":cnbl_tipo_embarcacao\": null, \":numero_passageiros_n1\": null, \":numero_passageiros_n2\": null, \":acrescimo_agua_salgada\": null, \":cnarq_calado_moldado_m\": null, \":marca_linha_carga_area1\": null, \":marca_linha_carga_area2\": null, \":cnarq_espacos_excluidos_m3\": null, \":cnarq_espacos_incluidos_ab\": null, \":cnarq_espacos_incluidos_al\": null, \":dist_linha_conves_bico_proa\": null, \":aresta_superior_linha_conves\": null, \":dist_linha_conves_abaixo_disco\": null, \":cnarq_data_local_arqueacao_original\": null, \":cnarq_data_local_ultima_rearqueacao\": null}','[\":nome\", \":registro\", \":tipo_embarcacao_id\", \":tipo_embarcacao\", \":cnbl_tipo_embarcacao\", \":ano\", \":porto_inscricao\", \":numero_inscricao\", \":indicativo_chamada\", \":observacoes\", \":possui_propulsao\", \":fabricante_motor\", \":modelo_motor\", \":numero_motor\", \":potencia_kw\", \":material_casco\", \":tipo_navegacao\", \":area_navegacao\", \":cnbl_area_navegacao\", \":tipo_servico\", \":autorizado_carga\", \":numero_tripulantes\", \":numero_passageiros_n1\", \":numero_passageiros_n2\", \":obs_passageiros\", \":acessibilidade\", \":comprimento_total\", \":comprimento_casco\", \":comprimento_lpp\", \":pontal_moldado\", \":boca_moldada\", \":boca_maxima\", \":arqueacao_bruta\", \":arqueacao_liquida\", \":metodo_arqueacao\", \":cnarq_data_quilha\", \":cnarq_calado_moldado_m\", \":cnarq_espacos_incluidos_ab\", \":cnarq_espacos_incluidos_al\", \":cnarq_espacos_excluidos_m3\", \":cnarq_data_local_arqueacao_original\", \":cnarq_data_local_ultima_rearqueacao\", \":local_construcao\", \":numero_casco\", \":porte_bruto\", \":estaleiro_nome\", \":estaleiro_cpf_cnpj\", \":estaleiro_endereco\", \":borda_livre_mm\", \":borda_livre_tipo\", \":calado_maximo_m\", \":aresta_superior_linha_conves\", \":centro_disco_situado\", \":acrescimo_agua_salgada\", \":dist_linha_conves_bico_proa\", \":dist_linha_conves_abaixo_disco\", \":marca_linha_carga_area1\", \":marca_linha_carga_area2\", \":id\", \":criado_por\"]','Cadastro inicial da embarcação','dd121661-feb4-42f6-895a-68eb0608d1e4','admin','127.0.0.1','','2026-09-16 00:07:40'),('489df880-6b8a-4429-b67b-df9b1ed14330','EMBARCACAO','emb-teste-sgq','ALTERACAO','{\"nome\": \"Barco Antigo\", \"arqueacao_bruta\": \"10\"}','{\"nome\": \"Barco Novo\", \"arqueacao_bruta\": \"15\"}','[\"nome\", \"arqueacao_bruta\"]','Teste de auditoria isolada da Etapa 7','u-admin-test','Administrador Teste','127.0.0.1','','2026-09-15 21:33:38'),('566a68f1-2485-44fe-93e2-3f287b1acc2e','CLIENTE','fb74642c-2795-4f11-9dae-49e0ba22a5b3','CRIACAO',NULL,'{\"nome\": \"Armador Solimões Web Test 2578\", \"email\": \"web_3470@solimoes.com.br\", \"perfil\": \"armador\", \"cpf_cnpj\": \"18542287000147\", \"endereco\": \"Av. Manaus Moderna, 500 - Centro, Manaus/AM\", \"telefone\": \"(92) 99123-4567\", \"tipo_pessoa\": \"PJ\"}','[\"nome\", \"tipo_pessoa\", \"cpf_cnpj\", \"perfil\", \"telefone\", \"email\", \"endereco\"]','Cadastro de armador no sistema','dd121661-feb4-42f6-895a-68eb0608d1e4','admin','127.0.0.1','','2026-09-16 00:23:23'),('56c157f0-2431-4355-8572-0789436c18de','EMBARCACAO','7bd04fc6-5070-4ead-b59e-35c7ade999de','CRIACAO',NULL,'{\":id\": \"7bd04fc6-5070-4ead-b59e-35c7ade999de\", \":ano\": 2022, \":nome\": \"B/M SOLIMÕES EXPRESS 229\", \":registro\": null, \":criado_por\": \"dd121661-feb4-42f6-895a-68eb0608d1e4\", \":boca_maxima\": null, \":observacoes\": null, \":porte_bruto\": null, \":potencia_kw\": \"330\", \":boca_moldada\": 6.4, \":modelo_motor\": \"DS11\", \":numero_casco\": null, \":numero_motor\": \"SC-998877\", \":tipo_servico\": null, \":acessibilidade\": null, \":area_navegacao\": \"Área 1 e 2\", \":borda_livre_mm\": null, \":estaleiro_nome\": null, \":material_casco\": \"Aço\", \":pontal_moldado\": 2.1, \":tipo_navegacao\": \"Interior\", \":arqueacao_bruta\": \"115.00\", \":calado_maximo_m\": 1.5, \":comprimento_lpp\": null, \":obs_passageiros\": null, \":porto_inscricao\": \"Manaus-AM\", \":tipo_embarcacao\": \"Balsa\", \":autorizado_carga\": null, \":borda_livre_tipo\": null, \":fabricante_motor\": \"Scania\", \":local_construcao\": null, \":metodo_arqueacao\": null, \":numero_inscricao\": \"021-360955-WEB\", \":possui_propulsao\": 1, \":arqueacao_liquida\": null, \":cnarq_data_quilha\": null, \":comprimento_casco\": null, \":comprimento_total\": 28.5, \":estaleiro_cpf_cnpj\": null, \":estaleiro_endereco\": null, \":indicativo_chamada\": null, \":numero_tripulantes\": null, \":tipo_embarcacao_id\": \"06a95b60-75d0-11f1-98f0-5ed0db5eacb7\", \":cnbl_area_navegacao\": null, \":centro_disco_situado\": null, \":cnbl_tipo_embarcacao\": null, \":numero_passageiros_n1\": null, \":numero_passageiros_n2\": null, \":acrescimo_agua_salgada\": null, \":cnarq_calado_moldado_m\": null, \":marca_linha_carga_area1\": null, \":marca_linha_carga_area2\": null, \":cnarq_espacos_excluidos_m3\": null, \":cnarq_espacos_incluidos_ab\": null, \":cnarq_espacos_incluidos_al\": null, \":dist_linha_conves_bico_proa\": null, \":aresta_superior_linha_conves\": null, \":dist_linha_conves_abaixo_disco\": null, \":cnarq_data_local_arqueacao_original\": null, \":cnarq_data_local_ultima_rearqueacao\": null}','[\":nome\", \":registro\", \":tipo_embarcacao_id\", \":tipo_embarcacao\", \":cnbl_tipo_embarcacao\", \":ano\", \":porto_inscricao\", \":numero_inscricao\", \":indicativo_chamada\", \":observacoes\", \":possui_propulsao\", \":fabricante_motor\", \":modelo_motor\", \":numero_motor\", \":potencia_kw\", \":material_casco\", \":tipo_navegacao\", \":area_navegacao\", \":cnbl_area_navegacao\", \":tipo_servico\", \":autorizado_carga\", \":numero_tripulantes\", \":numero_passageiros_n1\", \":numero_passageiros_n2\", \":obs_passageiros\", \":acessibilidade\", \":comprimento_total\", \":comprimento_casco\", \":comprimento_lpp\", \":pontal_moldado\", \":boca_moldada\", \":boca_maxima\", \":arqueacao_bruta\", \":arqueacao_liquida\", \":metodo_arqueacao\", \":cnarq_data_quilha\", \":cnarq_calado_moldado_m\", \":cnarq_espacos_incluidos_ab\", \":cnarq_espacos_incluidos_al\", \":cnarq_espacos_excluidos_m3\", \":cnarq_data_local_arqueacao_original\", \":cnarq_data_local_ultima_rearqueacao\", \":local_construcao\", \":numero_casco\", \":porte_bruto\", \":estaleiro_nome\", \":estaleiro_cpf_cnpj\", \":estaleiro_endereco\", \":borda_livre_mm\", \":borda_livre_tipo\", \":calado_maximo_m\", \":aresta_superior_linha_conves\", \":centro_disco_situado\", \":acrescimo_agua_salgada\", \":dist_linha_conves_bico_proa\", \":dist_linha_conves_abaixo_disco\", \":marca_linha_carga_area1\", \":marca_linha_carga_area2\", \":id\", \":criado_por\"]','Cadastro inicial da embarcação','dd121661-feb4-42f6-895a-68eb0608d1e4','admin','127.0.0.1','','2026-09-15 21:45:09'),('572f10ef-e588-40bd-a5c5-531fb6f2fee1','EMBARCACAO','emb-teste-sgq','ALTERACAO','{\"nome\": \"Barco Antigo\", \"arqueacao_bruta\": \"10\"}','{\"nome\": \"Barco Novo\", \"arqueacao_bruta\": \"15\"}','[\"nome\", \"arqueacao_bruta\"]','Teste de auditoria isolada da Etapa 7','u-admin-test','Administrador Teste','127.0.0.1','','2026-09-16 00:07:33'),('57c850f1-7326-4a18-91d8-c5f40bca71da','EMBARCACAO','07878916-db01-4a32-a160-8ee4c6eb32c1','CRIACAO',NULL,'{\":id\": \"07878916-db01-4a32-a160-8ee4c6eb32c1\", \":ano\": 2022, \":nome\": \"B/M SOLIMÕES EXPRESS 898\", \":registro\": null, \":criado_por\": \"dd121661-feb4-42f6-895a-68eb0608d1e4\", \":boca_maxima\": null, \":observacoes\": null, \":porte_bruto\": null, \":potencia_kw\": \"330\", \":boca_moldada\": 6.4, \":modelo_motor\": \"DS11\", \":numero_casco\": null, \":numero_motor\": \"SC-998877\", \":tipo_servico\": null, \":acessibilidade\": null, \":area_navegacao\": \"Área 1 e 2\", \":borda_livre_mm\": null, \":estaleiro_nome\": null, \":material_casco\": \"Aço\", \":pontal_moldado\": 2.1, \":tipo_navegacao\": \"Interior\", \":arqueacao_bruta\": \"115.00\", \":calado_maximo_m\": 1.5, \":comprimento_lpp\": null, \":obs_passageiros\": null, \":porto_inscricao\": \"Manaus-AM\", \":tipo_embarcacao\": \"Balsa\", \":autorizado_carga\": null, \":borda_livre_tipo\": null, \":fabricante_motor\": \"Scania\", \":local_construcao\": null, \":metodo_arqueacao\": null, \":numero_inscricao\": \"021-716996-WEB\", \":possui_propulsao\": 1, \":arqueacao_liquida\": null, \":cnarq_data_quilha\": null, \":comprimento_casco\": null, \":comprimento_total\": 28.5, \":estaleiro_cpf_cnpj\": null, \":estaleiro_endereco\": null, \":indicativo_chamada\": null, \":numero_tripulantes\": null, \":tipo_embarcacao_id\": \"06a95b60-75d0-11f1-98f0-5ed0db5eacb7\", \":cnbl_area_navegacao\": null, \":centro_disco_situado\": null, \":cnbl_tipo_embarcacao\": null, \":numero_passageiros_n1\": null, \":numero_passageiros_n2\": null, \":acrescimo_agua_salgada\": null, \":cnarq_calado_moldado_m\": null, \":marca_linha_carga_area1\": null, \":marca_linha_carga_area2\": null, \":cnarq_espacos_excluidos_m3\": null, \":cnarq_espacos_incluidos_ab\": null, \":cnarq_espacos_incluidos_al\": null, \":dist_linha_conves_bico_proa\": null, \":aresta_superior_linha_conves\": null, \":dist_linha_conves_abaixo_disco\": null, \":cnarq_data_local_arqueacao_original\": null, \":cnarq_data_local_ultima_rearqueacao\": null}','[\":nome\", \":registro\", \":tipo_embarcacao_id\", \":tipo_embarcacao\", \":cnbl_tipo_embarcacao\", \":ano\", \":porto_inscricao\", \":numero_inscricao\", \":indicativo_chamada\", \":observacoes\", \":possui_propulsao\", \":fabricante_motor\", \":modelo_motor\", \":numero_motor\", \":potencia_kw\", \":material_casco\", \":tipo_navegacao\", \":area_navegacao\", \":cnbl_area_navegacao\", \":tipo_servico\", \":autorizado_carga\", \":numero_tripulantes\", \":numero_passageiros_n1\", \":numero_passageiros_n2\", \":obs_passageiros\", \":acessibilidade\", \":comprimento_total\", \":comprimento_casco\", \":comprimento_lpp\", \":pontal_moldado\", \":boca_moldada\", \":boca_maxima\", \":arqueacao_bruta\", \":arqueacao_liquida\", \":metodo_arqueacao\", \":cnarq_data_quilha\", \":cnarq_calado_moldado_m\", \":cnarq_espacos_incluidos_ab\", \":cnarq_espacos_incluidos_al\", \":cnarq_espacos_excluidos_m3\", \":cnarq_data_local_arqueacao_original\", \":cnarq_data_local_ultima_rearqueacao\", \":local_construcao\", \":numero_casco\", \":porte_bruto\", \":estaleiro_nome\", \":estaleiro_cpf_cnpj\", \":estaleiro_endereco\", \":borda_livre_mm\", \":borda_livre_tipo\", \":calado_maximo_m\", \":aresta_superior_linha_conves\", \":centro_disco_situado\", \":acrescimo_agua_salgada\", \":dist_linha_conves_bico_proa\", \":dist_linha_conves_abaixo_disco\", \":marca_linha_carga_area1\", \":marca_linha_carga_area2\", \":id\", \":criado_por\"]','Cadastro inicial da embarcação','dd121661-feb4-42f6-895a-68eb0608d1e4','admin','127.0.0.1','','2026-09-15 21:48:05'),('5f45d890-1575-40a6-882a-cc9a6621f315','EMBARCACAO','096088f1-fed3-4ad5-9c02-f5d8562cdbd8','CRIACAO',NULL,'{\":id\": \"096088f1-fed3-4ad5-9c02-f5d8562cdbd8\", \":ano\": 2022, \":nome\": \"B/M SOLIMÕES EXPRESS 508\", \":registro\": null, \":criado_por\": \"dd121661-feb4-42f6-895a-68eb0608d1e4\", \":boca_maxima\": null, \":observacoes\": null, \":porte_bruto\": null, \":potencia_kw\": \"330\", \":boca_moldada\": 6.4, \":modelo_motor\": \"DS11\", \":numero_casco\": null, \":numero_motor\": \"SC-998877\", \":tipo_servico\": null, \":acessibilidade\": null, \":area_navegacao\": \"Área 1 e 2\", \":borda_livre_mm\": null, \":estaleiro_nome\": null, \":material_casco\": \"Aço\", \":pontal_moldado\": 2.1, \":tipo_navegacao\": \"Interior\", \":arqueacao_bruta\": \"115.00\", \":calado_maximo_m\": 1.5, \":comprimento_lpp\": null, \":obs_passageiros\": null, \":porto_inscricao\": \"Manaus-AM\", \":tipo_embarcacao\": \"Balsa\", \":autorizado_carga\": null, \":borda_livre_tipo\": null, \":fabricante_motor\": \"Scania\", \":local_construcao\": null, \":metodo_arqueacao\": null, \":numero_inscricao\": \"021-941909-WEB\", \":possui_propulsao\": 1, \":arqueacao_liquida\": null, \":cnarq_data_quilha\": null, \":comprimento_casco\": null, \":comprimento_total\": 28.5, \":estaleiro_cpf_cnpj\": null, \":estaleiro_endereco\": null, \":indicativo_chamada\": null, \":numero_tripulantes\": null, \":tipo_embarcacao_id\": \"06a95b60-75d0-11f1-98f0-5ed0db5eacb7\", \":cnbl_area_navegacao\": null, \":centro_disco_situado\": null, \":cnbl_tipo_embarcacao\": null, \":numero_passageiros_n1\": null, \":numero_passageiros_n2\": null, \":acrescimo_agua_salgada\": null, \":cnarq_calado_moldado_m\": null, \":marca_linha_carga_area1\": null, \":marca_linha_carga_area2\": null, \":cnarq_espacos_excluidos_m3\": null, \":cnarq_espacos_incluidos_ab\": null, \":cnarq_espacos_incluidos_al\": null, \":dist_linha_conves_bico_proa\": null, \":aresta_superior_linha_conves\": null, \":dist_linha_conves_abaixo_disco\": null, \":cnarq_data_local_arqueacao_original\": null, \":cnarq_data_local_ultima_rearqueacao\": null}','[\":nome\", \":registro\", \":tipo_embarcacao_id\", \":tipo_embarcacao\", \":cnbl_tipo_embarcacao\", \":ano\", \":porto_inscricao\", \":numero_inscricao\", \":indicativo_chamada\", \":observacoes\", \":possui_propulsao\", \":fabricante_motor\", \":modelo_motor\", \":numero_motor\", \":potencia_kw\", \":material_casco\", \":tipo_navegacao\", \":area_navegacao\", \":cnbl_area_navegacao\", \":tipo_servico\", \":autorizado_carga\", \":numero_tripulantes\", \":numero_passageiros_n1\", \":numero_passageiros_n2\", \":obs_passageiros\", \":acessibilidade\", \":comprimento_total\", \":comprimento_casco\", \":comprimento_lpp\", \":pontal_moldado\", \":boca_moldada\", \":boca_maxima\", \":arqueacao_bruta\", \":arqueacao_liquida\", \":metodo_arqueacao\", \":cnarq_data_quilha\", \":cnarq_calado_moldado_m\", \":cnarq_espacos_incluidos_ab\", \":cnarq_espacos_incluidos_al\", \":cnarq_espacos_excluidos_m3\", \":cnarq_data_local_arqueacao_original\", \":cnarq_data_local_ultima_rearqueacao\", \":local_construcao\", \":numero_casco\", \":porte_bruto\", \":estaleiro_nome\", \":estaleiro_cpf_cnpj\", \":estaleiro_endereco\", \":borda_livre_mm\", \":borda_livre_tipo\", \":calado_maximo_m\", \":aresta_superior_linha_conves\", \":centro_disco_situado\", \":acrescimo_agua_salgada\", \":dist_linha_conves_bico_proa\", \":dist_linha_conves_abaixo_disco\", \":marca_linha_carga_area1\", \":marca_linha_carga_area2\", \":id\", \":criado_por\"]','Cadastro inicial da embarcação','dd121661-feb4-42f6-895a-68eb0608d1e4','admin','127.0.0.1','','2026-09-15 21:47:12'),('636839c2-2e71-4619-a33b-fb46e736f8ea','EMBARCACAO','emb-teste-sgq','ALTERACAO','{\"nome\": \"Barco Antigo\", \"arqueacao_bruta\": \"10\"}','{\"nome\": \"Barco Novo\", \"arqueacao_bruta\": \"15\"}','[\"nome\", \"arqueacao_bruta\"]','Teste de auditoria isolada da Etapa 7','u-admin-test','Administrador Teste','127.0.0.1','','2026-09-15 23:14:55'),('64f5c65e-3c73-4f7b-8582-d4e3b7ccff6a','CLIENTE','51cd5b8f-a19b-43ed-a45b-d9b90c1c1316','CRIACAO',NULL,'{\"nome\": \"Armador Solimões Web Test 7734\", \"email\": \"web_8517@solimoes.com.br\", \"perfil\": \"armador\", \"cpf_cnpj\": \"65953170000136\", \"endereco\": \"Av. Manaus Moderna, 500 - Centro, Manaus/AM\", \"telefone\": \"(92) 99123-4567\", \"tipo_pessoa\": \"PJ\"}','[\"nome\", \"tipo_pessoa\", \"cpf_cnpj\", \"perfil\", \"telefone\", \"email\", \"endereco\"]','Cadastro de armador no sistema','dd121661-feb4-42f6-895a-68eb0608d1e4','admin','127.0.0.1','','2026-09-15 22:04:46'),('66cacf38-dfdc-42a8-b07a-48a605d9f8e7','EMBARCACAO','49908c1b-2d10-41f0-b885-efca813cc1e4','CRIACAO',NULL,'{\":id\": \"49908c1b-2d10-41f0-b885-efca813cc1e4\", \":ano\": 2022, \":nome\": \"B/M SOLIMÕES EXPRESS 424\", \":registro\": null, \":criado_por\": \"dd121661-feb4-42f6-895a-68eb0608d1e4\", \":boca_maxima\": null, \":observacoes\": null, \":porte_bruto\": null, \":potencia_kw\": \"330\", \":boca_moldada\": 6.4, \":modelo_motor\": \"DS11\", \":numero_casco\": null, \":numero_motor\": \"SC-998877\", \":tipo_servico\": null, \":acessibilidade\": null, \":area_navegacao\": \"Área 1 e 2\", \":borda_livre_mm\": null, \":estaleiro_nome\": null, \":material_casco\": \"Aço\", \":pontal_moldado\": 2.1, \":tipo_navegacao\": \"Interior\", \":arqueacao_bruta\": \"115.00\", \":calado_maximo_m\": 1.5, \":comprimento_lpp\": null, \":obs_passageiros\": null, \":porto_inscricao\": \"Manaus-AM\", \":tipo_embarcacao\": \"Balsa\", \":autorizado_carga\": null, \":borda_livre_tipo\": null, \":fabricante_motor\": \"Scania\", \":local_construcao\": null, \":metodo_arqueacao\": null, \":numero_inscricao\": \"021-707601-WEB\", \":possui_propulsao\": 1, \":arqueacao_liquida\": null, \":cnarq_data_quilha\": null, \":comprimento_casco\": null, \":comprimento_total\": 28.5, \":estaleiro_cpf_cnpj\": null, \":estaleiro_endereco\": null, \":indicativo_chamada\": null, \":numero_tripulantes\": null, \":tipo_embarcacao_id\": \"06a95b60-75d0-11f1-98f0-5ed0db5eacb7\", \":cnbl_area_navegacao\": null, \":centro_disco_situado\": null, \":cnbl_tipo_embarcacao\": null, \":numero_passageiros_n1\": null, \":numero_passageiros_n2\": null, \":acrescimo_agua_salgada\": null, \":cnarq_calado_moldado_m\": null, \":marca_linha_carga_area1\": null, \":marca_linha_carga_area2\": null, \":cnarq_espacos_excluidos_m3\": null, \":cnarq_espacos_incluidos_ab\": null, \":cnarq_espacos_incluidos_al\": null, \":dist_linha_conves_bico_proa\": null, \":aresta_superior_linha_conves\": null, \":dist_linha_conves_abaixo_disco\": null, \":cnarq_data_local_arqueacao_original\": null, \":cnarq_data_local_ultima_rearqueacao\": null}','[\":nome\", \":registro\", \":tipo_embarcacao_id\", \":tipo_embarcacao\", \":cnbl_tipo_embarcacao\", \":ano\", \":porto_inscricao\", \":numero_inscricao\", \":indicativo_chamada\", \":observacoes\", \":possui_propulsao\", \":fabricante_motor\", \":modelo_motor\", \":numero_motor\", \":potencia_kw\", \":material_casco\", \":tipo_navegacao\", \":area_navegacao\", \":cnbl_area_navegacao\", \":tipo_servico\", \":autorizado_carga\", \":numero_tripulantes\", \":numero_passageiros_n1\", \":numero_passageiros_n2\", \":obs_passageiros\", \":acessibilidade\", \":comprimento_total\", \":comprimento_casco\", \":comprimento_lpp\", \":pontal_moldado\", \":boca_moldada\", \":boca_maxima\", \":arqueacao_bruta\", \":arqueacao_liquida\", \":metodo_arqueacao\", \":cnarq_data_quilha\", \":cnarq_calado_moldado_m\", \":cnarq_espacos_incluidos_ab\", \":cnarq_espacos_incluidos_al\", \":cnarq_espacos_excluidos_m3\", \":cnarq_data_local_arqueacao_original\", \":cnarq_data_local_ultima_rearqueacao\", \":local_construcao\", \":numero_casco\", \":porte_bruto\", \":estaleiro_nome\", \":estaleiro_cpf_cnpj\", \":estaleiro_endereco\", \":borda_livre_mm\", \":borda_livre_tipo\", \":calado_maximo_m\", \":aresta_superior_linha_conves\", \":centro_disco_situado\", \":acrescimo_agua_salgada\", \":dist_linha_conves_bico_proa\", \":dist_linha_conves_abaixo_disco\", \":marca_linha_carga_area1\", \":marca_linha_carga_area2\", \":id\", \":criado_por\"]','Cadastro inicial da embarcação','dd121661-feb4-42f6-895a-68eb0608d1e4','admin','127.0.0.1','','2026-09-15 22:07:23'),('6efa8c6c-ca33-44b6-af59-f160b847f683','EMBARCACAO','5a8be95a-1e34-412b-a831-81cdcf2772c9','CRIACAO',NULL,'{\":id\": \"5a8be95a-1e34-412b-a831-81cdcf2772c9\", \":ano\": 2022, \":nome\": \"B/M SOLIMÕES EXPRESS 212\", \":registro\": null, \":criado_por\": \"dd121661-feb4-42f6-895a-68eb0608d1e4\", \":boca_maxima\": null, \":observacoes\": null, \":porte_bruto\": null, \":potencia_kw\": \"330\", \":boca_moldada\": 6.4, \":modelo_motor\": \"DS11\", \":numero_casco\": null, \":numero_motor\": \"SC-998877\", \":tipo_servico\": null, \":acessibilidade\": null, \":area_navegacao\": \"Área 1 e 2\", \":borda_livre_mm\": null, \":estaleiro_nome\": null, \":material_casco\": \"Aço\", \":pontal_moldado\": 2.1, \":tipo_navegacao\": \"Interior\", \":arqueacao_bruta\": \"115.00\", \":calado_maximo_m\": 1.5, \":comprimento_lpp\": null, \":obs_passageiros\": null, \":porto_inscricao\": \"Manaus-AM\", \":tipo_embarcacao\": \"Balsa\", \":autorizado_carga\": null, \":borda_livre_tipo\": null, \":fabricante_motor\": \"Scania\", \":local_construcao\": null, \":metodo_arqueacao\": null, \":numero_inscricao\": \"021-100483-WEB\", \":possui_propulsao\": 1, \":arqueacao_liquida\": null, \":cnarq_data_quilha\": null, \":comprimento_casco\": null, \":comprimento_total\": 28.5, \":estaleiro_cpf_cnpj\": null, \":estaleiro_endereco\": null, \":indicativo_chamada\": null, \":numero_tripulantes\": null, \":tipo_embarcacao_id\": \"06a95b60-75d0-11f1-98f0-5ed0db5eacb7\", \":cnbl_area_navegacao\": null, \":centro_disco_situado\": null, \":cnbl_tipo_embarcacao\": null, \":numero_passageiros_n1\": null, \":numero_passageiros_n2\": null, \":acrescimo_agua_salgada\": null, \":cnarq_calado_moldado_m\": null, \":marca_linha_carga_area1\": null, \":marca_linha_carga_area2\": null, \":cnarq_espacos_excluidos_m3\": null, \":cnarq_espacos_incluidos_ab\": null, \":cnarq_espacos_incluidos_al\": null, \":dist_linha_conves_bico_proa\": null, \":aresta_superior_linha_conves\": null, \":dist_linha_conves_abaixo_disco\": null, \":cnarq_data_local_arqueacao_original\": null, \":cnarq_data_local_ultima_rearqueacao\": null}','[\":nome\", \":registro\", \":tipo_embarcacao_id\", \":tipo_embarcacao\", \":cnbl_tipo_embarcacao\", \":ano\", \":porto_inscricao\", \":numero_inscricao\", \":indicativo_chamada\", \":observacoes\", \":possui_propulsao\", \":fabricante_motor\", \":modelo_motor\", \":numero_motor\", \":potencia_kw\", \":material_casco\", \":tipo_navegacao\", \":area_navegacao\", \":cnbl_area_navegacao\", \":tipo_servico\", \":autorizado_carga\", \":numero_tripulantes\", \":numero_passageiros_n1\", \":numero_passageiros_n2\", \":obs_passageiros\", \":acessibilidade\", \":comprimento_total\", \":comprimento_casco\", \":comprimento_lpp\", \":pontal_moldado\", \":boca_moldada\", \":boca_maxima\", \":arqueacao_bruta\", \":arqueacao_liquida\", \":metodo_arqueacao\", \":cnarq_data_quilha\", \":cnarq_calado_moldado_m\", \":cnarq_espacos_incluidos_ab\", \":cnarq_espacos_incluidos_al\", \":cnarq_espacos_excluidos_m3\", \":cnarq_data_local_arqueacao_original\", \":cnarq_data_local_ultima_rearqueacao\", \":local_construcao\", \":numero_casco\", \":porte_bruto\", \":estaleiro_nome\", \":estaleiro_cpf_cnpj\", \":estaleiro_endereco\", \":borda_livre_mm\", \":borda_livre_tipo\", \":calado_maximo_m\", \":aresta_superior_linha_conves\", \":centro_disco_situado\", \":acrescimo_agua_salgada\", \":dist_linha_conves_bico_proa\", \":dist_linha_conves_abaixo_disco\", \":marca_linha_carga_area1\", \":marca_linha_carga_area2\", \":id\", \":criado_por\"]','Cadastro inicial da embarcação','dd121661-feb4-42f6-895a-68eb0608d1e4','admin','127.0.0.1','','2026-09-15 21:45:35'),('72ea9968-b488-4ee0-8eeb-eae7c10a2177','CLIENTE','8ba9f558-eabb-4ca8-9c1d-d3e5c28788d2','CRIACAO',NULL,'{\"nome\": \"Armador Solimões Web Test 2024\", \"email\": \"web_2755@solimoes.com.br\", \"perfil\": \"armador\", \"cpf_cnpj\": \"12697374000197\", \"endereco\": \"Av. Manaus Moderna, 500 - Centro, Manaus/AM\", \"telefone\": \"(92) 99123-4567\", \"tipo_pessoa\": \"PJ\"}','[\"nome\", \"tipo_pessoa\", \"cpf_cnpj\", \"perfil\", \"telefone\", \"email\", \"endereco\"]','Cadastro de armador no sistema','dd121661-feb4-42f6-895a-68eb0608d1e4','admin','127.0.0.1','','2026-09-16 00:05:56'),('72fd8de0-9f1a-48b5-9adc-ec47029ec564','CLIENTE','14494531-8ed0-4823-9398-c75c149a8f19','CRIACAO',NULL,'{\"nome\": \"Armador Solimões Web Test 1088\", \"email\": \"web_8336@solimoes.com.br\", \"perfil\": \"armador\", \"cpf_cnpj\": \"91356310000107\", \"endereco\": \"Av. Manaus Moderna, 500 - Centro, Manaus/AM\", \"telefone\": \"(92) 99123-4567\", \"tipo_pessoa\": \"PJ\"}','[\"nome\", \"tipo_pessoa\", \"cpf_cnpj\", \"perfil\", \"telefone\", \"email\", \"endereco\"]','Cadastro de armador no sistema','dd121661-feb4-42f6-895a-68eb0608d1e4','admin','127.0.0.1','','2026-09-15 21:44:41'),('78f11686-2abd-405c-9aa8-a2537bf59556','EMBARCACAO','emb-teste-sgq','ALTERACAO','{\"nome\": \"Barco Antigo\", \"arqueacao_bruta\": \"10\"}','{\"nome\": \"Barco Novo\", \"arqueacao_bruta\": \"15\"}','[\"nome\", \"arqueacao_bruta\"]','Teste de auditoria isolada da Etapa 7','u-admin-test','Administrador Teste','127.0.0.1','','2026-09-16 00:24:58'),('7c39de1b-2f72-40f1-82c2-cab39cc45319','EMBARCACAO','emb-teste-sgq','ALTERACAO','{\"nome\": \"Barco Antigo\", \"arqueacao_bruta\": \"10\"}','{\"nome\": \"Barco Novo\", \"arqueacao_bruta\": \"15\"}','[\"nome\", \"arqueacao_bruta\"]','Teste de auditoria isolada da Etapa 7','u-admin-test','Administrador Teste','127.0.0.1','','2026-09-15 20:06:32'),('7f0cf8a0-872d-40c2-8b23-166d35775109','EMBARCACAO','emb-teste-sgq','ALTERACAO','{\"nome\": \"Barco Antigo\", \"arqueacao_bruta\": \"10\"}','{\"nome\": \"Barco Novo\", \"arqueacao_bruta\": \"15\"}','[\"nome\", \"arqueacao_bruta\"]','Teste de auditoria isolada da Etapa 7','u-admin-test','Administrador Teste','127.0.0.1','','2026-09-15 22:04:37'),('7f999f2e-9680-4c5c-bdac-dd908239d70f','EMBARCACAO','emb-teste-sgq','ALTERACAO','{\"nome\": \"Barco Antigo\", \"arqueacao_bruta\": \"10\"}','{\"nome\": \"Barco Novo\", \"arqueacao_bruta\": \"15\"}','[\"nome\", \"arqueacao_bruta\"]','Teste de auditoria isolada da Etapa 7','u-admin-test','Administrador Teste','127.0.0.1','','2026-09-15 20:04:36'),('83a7439b-53e8-4e84-85e5-98162583f060','EMBARCACAO','emb-teste-sgq','ALTERACAO','{\"nome\": \"Barco Antigo\", \"arqueacao_bruta\": \"10\"}','{\"nome\": \"Barco Novo\", \"arqueacao_bruta\": \"15\"}','[\"nome\", \"arqueacao_bruta\"]','Teste de auditoria isolada da Etapa 7','u-admin-test','Administrador Teste','127.0.0.1','','2026-09-16 00:22:37'),('8427c1d9-093f-4452-9fd1-de67b2556eb1','CLIENTE','db416623-dad0-4bc6-89ba-45943dd5d536','CRIACAO',NULL,'{\"nome\": \"Armador Solimões Web Test 5614\", \"email\": \"web_5110@solimoes.com.br\", \"perfil\": \"armador\", \"cpf_cnpj\": \"34351404000141\", \"endereco\": \"Av. Manaus Moderna, 500 - Centro, Manaus/AM\", \"telefone\": \"(92) 99123-4567\", \"tipo_pessoa\": \"PJ\"}','[\"nome\", \"tipo_pessoa\", \"cpf_cnpj\", \"perfil\", \"telefone\", \"email\", \"endereco\"]','Cadastro de armador no sistema','dd121661-feb4-42f6-895a-68eb0608d1e4','admin','127.0.0.1','','2026-09-15 21:46:28'),('86daad79-43c1-4e42-8c8c-95a4c7905a86','EMBARCACAO','bd7911d8-5a51-47b9-b6e3-cce168a78f7e','CRIACAO',NULL,'{\":id\": \"bd7911d8-5a51-47b9-b6e3-cce168a78f7e\", \":ano\": 2022, \":nome\": \"B/M SOLIMÕES EXPRESS 995\", \":registro\": null, \":criado_por\": \"dd121661-feb4-42f6-895a-68eb0608d1e4\", \":boca_maxima\": null, \":observacoes\": null, \":porte_bruto\": null, \":potencia_kw\": \"330\", \":boca_moldada\": 6.4, \":modelo_motor\": \"DS11\", \":numero_casco\": null, \":numero_motor\": \"SC-998877\", \":tipo_servico\": null, \":acessibilidade\": null, \":area_navegacao\": \"Área 1 e 2\", \":borda_livre_mm\": null, \":estaleiro_nome\": null, \":material_casco\": \"Aço\", \":pontal_moldado\": 2.1, \":tipo_navegacao\": \"Interior\", \":arqueacao_bruta\": \"115.00\", \":calado_maximo_m\": 1.5, \":comprimento_lpp\": null, \":obs_passageiros\": null, \":porto_inscricao\": \"Manaus-AM\", \":tipo_embarcacao\": \"Balsa\", \":autorizado_carga\": null, \":borda_livre_tipo\": null, \":fabricante_motor\": \"Scania\", \":local_construcao\": null, \":metodo_arqueacao\": null, \":numero_inscricao\": \"021-250336-WEB\", \":possui_propulsao\": 1, \":arqueacao_liquida\": null, \":cnarq_data_quilha\": null, \":comprimento_casco\": null, \":comprimento_total\": 28.5, \":estaleiro_cpf_cnpj\": null, \":estaleiro_endereco\": null, \":indicativo_chamada\": null, \":numero_tripulantes\": null, \":tipo_embarcacao_id\": \"06a95b60-75d0-11f1-98f0-5ed0db5eacb7\", \":cnbl_area_navegacao\": null, \":centro_disco_situado\": null, \":cnbl_tipo_embarcacao\": null, \":numero_passageiros_n1\": null, \":numero_passageiros_n2\": null, \":acrescimo_agua_salgada\": null, \":cnarq_calado_moldado_m\": null, \":marca_linha_carga_area1\": null, \":marca_linha_carga_area2\": null, \":cnarq_espacos_excluidos_m3\": null, \":cnarq_espacos_incluidos_ab\": null, \":cnarq_espacos_incluidos_al\": null, \":dist_linha_conves_bico_proa\": null, \":aresta_superior_linha_conves\": null, \":dist_linha_conves_abaixo_disco\": null, \":cnarq_data_local_arqueacao_original\": null, \":cnarq_data_local_ultima_rearqueacao\": null}','[\":nome\", \":registro\", \":tipo_embarcacao_id\", \":tipo_embarcacao\", \":cnbl_tipo_embarcacao\", \":ano\", \":porto_inscricao\", \":numero_inscricao\", \":indicativo_chamada\", \":observacoes\", \":possui_propulsao\", \":fabricante_motor\", \":modelo_motor\", \":numero_motor\", \":potencia_kw\", \":material_casco\", \":tipo_navegacao\", \":area_navegacao\", \":cnbl_area_navegacao\", \":tipo_servico\", \":autorizado_carga\", \":numero_tripulantes\", \":numero_passageiros_n1\", \":numero_passageiros_n2\", \":obs_passageiros\", \":acessibilidade\", \":comprimento_total\", \":comprimento_casco\", \":comprimento_lpp\", \":pontal_moldado\", \":boca_moldada\", \":boca_maxima\", \":arqueacao_bruta\", \":arqueacao_liquida\", \":metodo_arqueacao\", \":cnarq_data_quilha\", \":cnarq_calado_moldado_m\", \":cnarq_espacos_incluidos_ab\", \":cnarq_espacos_incluidos_al\", \":cnarq_espacos_excluidos_m3\", \":cnarq_data_local_arqueacao_original\", \":cnarq_data_local_ultima_rearqueacao\", \":local_construcao\", \":numero_casco\", \":porte_bruto\", \":estaleiro_nome\", \":estaleiro_cpf_cnpj\", \":estaleiro_endereco\", \":borda_livre_mm\", \":borda_livre_tipo\", \":calado_maximo_m\", \":aresta_superior_linha_conves\", \":centro_disco_situado\", \":acrescimo_agua_salgada\", \":dist_linha_conves_bico_proa\", \":dist_linha_conves_abaixo_disco\", \":marca_linha_carga_area1\", \":marca_linha_carga_area2\", \":id\", \":criado_por\"]','Cadastro inicial da embarcação','dd121661-feb4-42f6-895a-68eb0608d1e4','admin','127.0.0.1','','2026-09-15 21:46:28'),('9460d304-8247-4dab-9324-a523b5f4c6b1','CLIENTE','18fdec8e-6df4-4287-ae26-8f3c143175df','CRIACAO',NULL,'{\"nome\": \"Armador Solimões Web Test 1782\", \"email\": \"web_2130@solimoes.com.br\", \"perfil\": \"armador\", \"cpf_cnpj\": \"21072888000148\", \"endereco\": \"Av. Manaus Moderna, 500 - Centro, Manaus/AM\", \"telefone\": \"(92) 99123-4567\", \"tipo_pessoa\": \"PJ\"}','[\"nome\", \"tipo_pessoa\", \"cpf_cnpj\", \"perfil\", \"telefone\", \"email\", \"endereco\"]','Cadastro de armador no sistema','dd121661-feb4-42f6-895a-68eb0608d1e4','admin','127.0.0.1','','2026-09-15 23:15:02'),('9691d586-0585-4710-b3e2-2796448952bf','EMBARCACAO','emb-teste-sgq','ALTERACAO','{\"nome\": \"Barco Antigo\", \"arqueacao_bruta\": \"10\"}','{\"nome\": \"Barco Novo\", \"arqueacao_bruta\": \"15\"}','[\"nome\", \"arqueacao_bruta\"]','Teste de auditoria isolada da Etapa 7','u-admin-test','Administrador Teste','127.0.0.1','','2026-09-15 21:32:44'),('96c01fa4-634c-45c5-9771-b8fd6a61b66e','EMBARCACAO','emb-teste-sgq','ALTERACAO','{\"nome\": \"Barco Antigo\", \"arqueacao_bruta\": \"10\"}','{\"nome\": \"Barco Novo\", \"arqueacao_bruta\": \"15\"}','[\"nome\", \"arqueacao_bruta\"]','Teste de auditoria isolada da Etapa 7','u-admin-test','Administrador Teste','127.0.0.1','','2026-09-16 00:23:02'),('97c9f1af-ee48-4f12-b998-d4a41471b8a9','EMBARCACAO','c687679e-e7ed-4def-b5ff-72c443042db0','CRIACAO',NULL,'{\":id\": \"c687679e-e7ed-4def-b5ff-72c443042db0\", \":ano\": 2022, \":nome\": \"B/M SOLIMÕES EXPRESS 775\", \":registro\": null, \":criado_por\": \"dd121661-feb4-42f6-895a-68eb0608d1e4\", \":boca_maxima\": null, \":observacoes\": null, \":porte_bruto\": null, \":potencia_kw\": \"330\", \":boca_moldada\": 6.4, \":modelo_motor\": \"DS11\", \":numero_casco\": null, \":numero_motor\": \"SC-998877\", \":tipo_servico\": null, \":acessibilidade\": null, \":area_navegacao\": \"Área 1 e 2\", \":borda_livre_mm\": null, \":estaleiro_nome\": null, \":material_casco\": \"Aço\", \":pontal_moldado\": 2.1, \":tipo_navegacao\": \"Interior\", \":arqueacao_bruta\": \"115.00\", \":calado_maximo_m\": 1.5, \":comprimento_lpp\": null, \":obs_passageiros\": null, \":porto_inscricao\": \"Manaus-AM\", \":tipo_embarcacao\": \"Balsa\", \":autorizado_carga\": null, \":borda_livre_tipo\": null, \":fabricante_motor\": \"Scania\", \":local_construcao\": null, \":metodo_arqueacao\": null, \":numero_inscricao\": \"021-430797-WEB\", \":possui_propulsao\": 1, \":arqueacao_liquida\": null, \":cnarq_data_quilha\": null, \":comprimento_casco\": null, \":comprimento_total\": 28.5, \":estaleiro_cpf_cnpj\": null, \":estaleiro_endereco\": null, \":indicativo_chamada\": null, \":numero_tripulantes\": null, \":tipo_embarcacao_id\": \"06a95b60-75d0-11f1-98f0-5ed0db5eacb7\", \":cnbl_area_navegacao\": null, \":centro_disco_situado\": null, \":cnbl_tipo_embarcacao\": null, \":numero_passageiros_n1\": null, \":numero_passageiros_n2\": null, \":acrescimo_agua_salgada\": null, \":cnarq_calado_moldado_m\": null, \":marca_linha_carga_area1\": null, \":marca_linha_carga_area2\": null, \":cnarq_espacos_excluidos_m3\": null, \":cnarq_espacos_incluidos_ab\": null, \":cnarq_espacos_incluidos_al\": null, \":dist_linha_conves_bico_proa\": null, \":aresta_superior_linha_conves\": null, \":dist_linha_conves_abaixo_disco\": null, \":cnarq_data_local_arqueacao_original\": null, \":cnarq_data_local_ultima_rearqueacao\": null}','[\":nome\", \":registro\", \":tipo_embarcacao_id\", \":tipo_embarcacao\", \":cnbl_tipo_embarcacao\", \":ano\", \":porto_inscricao\", \":numero_inscricao\", \":indicativo_chamada\", \":observacoes\", \":possui_propulsao\", \":fabricante_motor\", \":modelo_motor\", \":numero_motor\", \":potencia_kw\", \":material_casco\", \":tipo_navegacao\", \":area_navegacao\", \":cnbl_area_navegacao\", \":tipo_servico\", \":autorizado_carga\", \":numero_tripulantes\", \":numero_passageiros_n1\", \":numero_passageiros_n2\", \":obs_passageiros\", \":acessibilidade\", \":comprimento_total\", \":comprimento_casco\", \":comprimento_lpp\", \":pontal_moldado\", \":boca_moldada\", \":boca_maxima\", \":arqueacao_bruta\", \":arqueacao_liquida\", \":metodo_arqueacao\", \":cnarq_data_quilha\", \":cnarq_calado_moldado_m\", \":cnarq_espacos_incluidos_ab\", \":cnarq_espacos_incluidos_al\", \":cnarq_espacos_excluidos_m3\", \":cnarq_data_local_arqueacao_original\", \":cnarq_data_local_ultima_rearqueacao\", \":local_construcao\", \":numero_casco\", \":porte_bruto\", \":estaleiro_nome\", \":estaleiro_cpf_cnpj\", \":estaleiro_endereco\", \":borda_livre_mm\", \":borda_livre_tipo\", \":calado_maximo_m\", \":aresta_superior_linha_conves\", \":centro_disco_situado\", \":acrescimo_agua_salgada\", \":dist_linha_conves_bico_proa\", \":dist_linha_conves_abaixo_disco\", \":marca_linha_carga_area1\", \":marca_linha_carga_area2\", \":id\", \":criado_por\"]','Cadastro inicial da embarcação','dd121661-feb4-42f6-895a-68eb0608d1e4','admin','127.0.0.1','','2026-09-15 21:45:27'),('9bac9d4e-2cb0-44a9-9713-eccb829b3a38','EMBARCACAO','emb-teste-sgq','ALTERACAO','{\"nome\": \"Barco Antigo\", \"arqueacao_bruta\": \"10\"}','{\"nome\": \"Barco Novo\", \"arqueacao_bruta\": \"15\"}','[\"nome\", \"arqueacao_bruta\"]','Teste de auditoria isolada da Etapa 7','u-admin-test','Administrador Teste','127.0.0.1','','2026-09-15 20:07:42'),('a506d1b8-3de2-49f2-b95a-3d649289c0ee','USUARIO','9cd7e53a-da9d-4f2b-9b32-328be32da2f0','ALTERACAO','{\"id\": \"9cd7e53a-da9d-4f2b-9b32-328be32da2f0\", \"nome\": \"itamar\", \"ativo\": 1, \"cargo\": \"ANALISTA\", \"email\": \"analista@teste.com\", \"criado_em\": \"2026-07-16 15:38:12\", \"gestor_id\": null, \"senha_hash\": \"$2y$10$Fynqbm7wWlmJrGljeaK.iuu3YXucyUBwVZwSE.JY8RXQDA7lAwSOK\", \"status_sgq\": \"QUALIFICADO\", \"excluido_em\": null, \"atualizado_em\": \"2026-09-13 05:37:38\", \"escritorio_id\": \"342323aa-142c-447b-b392-7421e538f041\", \"versao_sessao\": 1, \"acesso_financeiro\": 0, \"escopo_habilitacao\": null, \"acesso_documentacao\": 0, \"registro_conselho_tipo\": null, \"registro_conselho_numero\": null, \"credencial_marinha_numero\": null, \"registro_conselho_validade\": null, \"credencial_marinha_validade\": null, \"data_ultima_avaliacao_competencia\": null}','{\"id\": \"9cd7e53a-da9d-4f2b-9b32-328be32da2f0\", \"nome\": \"itamar\", \"ativo\": 1, \"cargo\": \"ANALISTA\", \"email\": \"analista@teste.com\", \"criado_em\": \"2026-07-16 15:38:12\", \"gestor_id\": null, \"senha_hash\": \"$2y$10$Wx89flrgig.DQhcX7i.yHeItCiTtHgYfJYHIoX/K0A4ospK6zkKsO\", \"status_sgq\": \"QUALIFICADO\", \"excluido_em\": null, \"atualizado_em\": \"2026-09-16 01:45:33\", \"escritorio_id\": \"342323aa-142c-447b-b392-7421e538f041\", \"versao_sessao\": 2, \"acesso_financeiro\": 0, \"escopo_habilitacao\": null, \"acesso_documentacao\": 0, \"registro_conselho_tipo\": null, \"registro_conselho_numero\": null, \"credencial_marinha_numero\": null, \"registro_conselho_validade\": null, \"credencial_marinha_validade\": null, \"data_ultima_avaliacao_competencia\": null}','[\"senha_hash\", \"versao_sessao\", \"atualizado_em\"]','Atualização cadastral e de credenciais técnicas do usuário.','dd121661-feb4-42f6-895a-68eb0608d1e4','admin','172.23.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0','2026-09-15 22:45:33'),('a8c722d3-0766-4171-b76f-548ced201f48','EMBARCACAO','emb-teste-sgq','ALTERACAO','{\"nome\": \"Barco Antigo\", \"arqueacao_bruta\": \"10\"}','{\"nome\": \"Barco Novo\", \"arqueacao_bruta\": \"15\"}','[\"nome\", \"arqueacao_bruta\"]','Teste de auditoria isolada da Etapa 7','u-admin-test','Administrador Teste','127.0.0.1','','2026-09-15 21:36:28'),('aa63c532-f6ad-4030-b224-4a217656d083','CLIENTE','d0211f8e-b261-48f9-88c4-549bb232bf8a','CRIACAO',NULL,'{\"nome\": \"Armador Solimões Web Test 6654\", \"email\": \"web_3040@solimoes.com.br\", \"perfil\": \"armador\", \"cpf_cnpj\": \"43391248000133\", \"endereco\": \"Av. Manaus Moderna, 500 - Centro, Manaus/AM\", \"telefone\": \"(92) 99123-4567\", \"tipo_pessoa\": \"PJ\"}','[\"nome\", \"tipo_pessoa\", \"cpf_cnpj\", \"perfil\", \"telefone\", \"email\", \"endereco\"]','Cadastro de armador no sistema','dd121661-feb4-42f6-895a-68eb0608d1e4','admin','127.0.0.1','','2026-09-16 00:25:06'),('ae726df1-b0dc-4bde-a9b5-5bfccd44e098','EMBARCACAO','b01e993b-140d-4a3e-9155-fca1842ffd49','CRIACAO',NULL,'{\":id\": \"b01e993b-140d-4a3e-9155-fca1842ffd49\", \":ano\": 2022, \":nome\": \"B/M SOLIMÕES EXPRESS 930\", \":registro\": null, \":criado_por\": \"dd121661-feb4-42f6-895a-68eb0608d1e4\", \":boca_maxima\": null, \":observacoes\": null, \":porte_bruto\": null, \":potencia_kw\": \"330\", \":boca_moldada\": 6.4, \":modelo_motor\": \"DS11\", \":numero_casco\": null, \":numero_motor\": \"SC-998877\", \":tipo_servico\": null, \":acessibilidade\": null, \":area_navegacao\": \"Área 1 e 2\", \":borda_livre_mm\": null, \":estaleiro_nome\": null, \":material_casco\": \"Aço\", \":pontal_moldado\": 2.1, \":tipo_navegacao\": \"Interior\", \":arqueacao_bruta\": \"115.00\", \":calado_maximo_m\": 1.5, \":comprimento_lpp\": null, \":obs_passageiros\": null, \":porto_inscricao\": \"Manaus-AM\", \":tipo_embarcacao\": \"Balsa\", \":autorizado_carga\": null, \":borda_livre_tipo\": null, \":fabricante_motor\": \"Scania\", \":local_construcao\": null, \":metodo_arqueacao\": null, \":numero_inscricao\": \"021-230651-WEB\", \":possui_propulsao\": 1, \":arqueacao_liquida\": null, \":cnarq_data_quilha\": null, \":comprimento_casco\": null, \":comprimento_total\": 28.5, \":estaleiro_cpf_cnpj\": null, \":estaleiro_endereco\": null, \":indicativo_chamada\": null, \":numero_tripulantes\": null, \":tipo_embarcacao_id\": \"06a95b60-75d0-11f1-98f0-5ed0db5eacb7\", \":cnbl_area_navegacao\": null, \":centro_disco_situado\": null, \":cnbl_tipo_embarcacao\": null, \":numero_passageiros_n1\": null, \":numero_passageiros_n2\": null, \":acrescimo_agua_salgada\": null, \":cnarq_calado_moldado_m\": null, \":marca_linha_carga_area1\": null, \":marca_linha_carga_area2\": null, \":cnarq_espacos_excluidos_m3\": null, \":cnarq_espacos_incluidos_ab\": null, \":cnarq_espacos_incluidos_al\": null, \":dist_linha_conves_bico_proa\": null, \":aresta_superior_linha_conves\": null, \":dist_linha_conves_abaixo_disco\": null, \":cnarq_data_local_arqueacao_original\": null, \":cnarq_data_local_ultima_rearqueacao\": null}','[\":nome\", \":registro\", \":tipo_embarcacao_id\", \":tipo_embarcacao\", \":cnbl_tipo_embarcacao\", \":ano\", \":porto_inscricao\", \":numero_inscricao\", \":indicativo_chamada\", \":observacoes\", \":possui_propulsao\", \":fabricante_motor\", \":modelo_motor\", \":numero_motor\", \":potencia_kw\", \":material_casco\", \":tipo_navegacao\", \":area_navegacao\", \":cnbl_area_navegacao\", \":tipo_servico\", \":autorizado_carga\", \":numero_tripulantes\", \":numero_passageiros_n1\", \":numero_passageiros_n2\", \":obs_passageiros\", \":acessibilidade\", \":comprimento_total\", \":comprimento_casco\", \":comprimento_lpp\", \":pontal_moldado\", \":boca_moldada\", \":boca_maxima\", \":arqueacao_bruta\", \":arqueacao_liquida\", \":metodo_arqueacao\", \":cnarq_data_quilha\", \":cnarq_calado_moldado_m\", \":cnarq_espacos_incluidos_ab\", \":cnarq_espacos_incluidos_al\", \":cnarq_espacos_excluidos_m3\", \":cnarq_data_local_arqueacao_original\", \":cnarq_data_local_ultima_rearqueacao\", \":local_construcao\", \":numero_casco\", \":porte_bruto\", \":estaleiro_nome\", \":estaleiro_cpf_cnpj\", \":estaleiro_endereco\", \":borda_livre_mm\", \":borda_livre_tipo\", \":calado_maximo_m\", \":aresta_superior_linha_conves\", \":centro_disco_situado\", \":acrescimo_agua_salgada\", \":dist_linha_conves_bico_proa\", \":dist_linha_conves_abaixo_disco\", \":marca_linha_carga_area1\", \":marca_linha_carga_area2\", \":id\", \":criado_por\"]','Cadastro inicial da embarcação','dd121661-feb4-42f6-895a-68eb0608d1e4','admin','127.0.0.1','','2026-09-15 21:42:50'),('aebb2dd6-4c7c-4d79-96f6-cfe7715400b0','EMBARCACAO','emb-teste-sgq','ALTERACAO','{\"nome\": \"Barco Antigo\", \"arqueacao_bruta\": \"10\"}','{\"nome\": \"Barco Novo\", \"arqueacao_bruta\": \"15\"}','[\"nome\", \"arqueacao_bruta\"]','Teste de auditoria isolada da Etapa 7','u-admin-test','Administrador Teste','127.0.0.1','','2026-09-15 20:08:04'),('b4306a37-36d0-4010-af62-63da9b6d0e68','EMBARCACAO','emb-teste-sgq','ALTERACAO','{\"nome\": \"Barco Antigo\", \"arqueacao_bruta\": \"10\"}','{\"nome\": \"Barco Novo\", \"arqueacao_bruta\": \"15\"}','[\"nome\", \"arqueacao_bruta\"]','Teste de auditoria isolada da Etapa 7','u-admin-test','Administrador Teste','127.0.0.1','','2026-09-16 01:02:51'),('bb038f1b-f97f-4b59-959b-2cff92ab5598','EMBARCACAO','7e8375e3-edae-4d93-a4de-2aa98fb2899a','CRIACAO',NULL,'{\":id\": \"7e8375e3-edae-4d93-a4de-2aa98fb2899a\", \":ano\": 2022, \":nome\": \"B/M SOLIMÕES EXPRESS 201\", \":registro\": null, \":criado_por\": \"dd121661-feb4-42f6-895a-68eb0608d1e4\", \":boca_maxima\": null, \":observacoes\": null, \":porte_bruto\": null, \":potencia_kw\": \"330\", \":boca_moldada\": 6.4, \":modelo_motor\": \"DS11\", \":numero_casco\": null, \":numero_motor\": \"SC-998877\", \":tipo_servico\": null, \":acessibilidade\": null, \":area_navegacao\": \"Área 1 e 2\", \":borda_livre_mm\": null, \":estaleiro_nome\": null, \":material_casco\": \"Aço\", \":pontal_moldado\": 2.1, \":tipo_navegacao\": \"Interior\", \":arqueacao_bruta\": \"115.00\", \":calado_maximo_m\": 1.5, \":comprimento_lpp\": null, \":obs_passageiros\": null, \":porto_inscricao\": \"Manaus-AM\", \":tipo_embarcacao\": \"Balsa\", \":autorizado_carga\": null, \":borda_livre_tipo\": null, \":fabricante_motor\": \"Scania\", \":local_construcao\": null, \":metodo_arqueacao\": null, \":numero_inscricao\": \"021-969302-WEB\", \":possui_propulsao\": 1, \":arqueacao_liquida\": null, \":cnarq_data_quilha\": null, \":comprimento_casco\": null, \":comprimento_total\": 28.5, \":estaleiro_cpf_cnpj\": null, \":estaleiro_endereco\": null, \":indicativo_chamada\": null, \":numero_tripulantes\": null, \":tipo_embarcacao_id\": \"06a95b60-75d0-11f1-98f0-5ed0db5eacb7\", \":cnbl_area_navegacao\": null, \":centro_disco_situado\": null, \":cnbl_tipo_embarcacao\": null, \":numero_passageiros_n1\": null, \":numero_passageiros_n2\": null, \":acrescimo_agua_salgada\": null, \":cnarq_calado_moldado_m\": null, \":marca_linha_carga_area1\": null, \":marca_linha_carga_area2\": null, \":cnarq_espacos_excluidos_m3\": null, \":cnarq_espacos_incluidos_ab\": null, \":cnarq_espacos_incluidos_al\": null, \":dist_linha_conves_bico_proa\": null, \":aresta_superior_linha_conves\": null, \":dist_linha_conves_abaixo_disco\": null, \":cnarq_data_local_arqueacao_original\": null, \":cnarq_data_local_ultima_rearqueacao\": null}','[\":nome\", \":registro\", \":tipo_embarcacao_id\", \":tipo_embarcacao\", \":cnbl_tipo_embarcacao\", \":ano\", \":porto_inscricao\", \":numero_inscricao\", \":indicativo_chamada\", \":observacoes\", \":possui_propulsao\", \":fabricante_motor\", \":modelo_motor\", \":numero_motor\", \":potencia_kw\", \":material_casco\", \":tipo_navegacao\", \":area_navegacao\", \":cnbl_area_navegacao\", \":tipo_servico\", \":autorizado_carga\", \":numero_tripulantes\", \":numero_passageiros_n1\", \":numero_passageiros_n2\", \":obs_passageiros\", \":acessibilidade\", \":comprimento_total\", \":comprimento_casco\", \":comprimento_lpp\", \":pontal_moldado\", \":boca_moldada\", \":boca_maxima\", \":arqueacao_bruta\", \":arqueacao_liquida\", \":metodo_arqueacao\", \":cnarq_data_quilha\", \":cnarq_calado_moldado_m\", \":cnarq_espacos_incluidos_ab\", \":cnarq_espacos_incluidos_al\", \":cnarq_espacos_excluidos_m3\", \":cnarq_data_local_arqueacao_original\", \":cnarq_data_local_ultima_rearqueacao\", \":local_construcao\", \":numero_casco\", \":porte_bruto\", \":estaleiro_nome\", \":estaleiro_cpf_cnpj\", \":estaleiro_endereco\", \":borda_livre_mm\", \":borda_livre_tipo\", \":calado_maximo_m\", \":aresta_superior_linha_conves\", \":centro_disco_situado\", \":acrescimo_agua_salgada\", \":dist_linha_conves_bico_proa\", \":dist_linha_conves_abaixo_disco\", \":marca_linha_carga_area1\", \":marca_linha_carga_area2\", \":id\", \":criado_por\"]','Cadastro inicial da embarcação','dd121661-feb4-42f6-895a-68eb0608d1e4','admin','127.0.0.1','','2026-09-16 01:03:12'),('c3df4973-7d4d-4e71-a8ee-614b551bcdd0','CLIENTE','ad4accaa-b248-4e07-b774-4af53b35433f','CRIACAO',NULL,'{\"nome\": \"Armador Solimões Web Test 9998\", \"email\": \"web_3909@solimoes.com.br\", \"perfil\": \"armador\", \"cpf_cnpj\": \"13041981000167\", \"endereco\": \"Av. Manaus Moderna, 500 - Centro, Manaus/AM\", \"telefone\": \"(92) 99123-4567\", \"tipo_pessoa\": \"PJ\"}','[\"nome\", \"tipo_pessoa\", \"cpf_cnpj\", \"perfil\", \"telefone\", \"email\", \"endereco\"]','Cadastro de armador no sistema','dd121661-feb4-42f6-895a-68eb0608d1e4','admin','127.0.0.1','','2026-09-15 21:42:50'),('cb748cd6-02cc-445f-a413-9e3a7106a236','EMBARCACAO','emb-teste-sgq','ALTERACAO','{\"nome\": \"Barco Antigo\", \"arqueacao_bruta\": \"10\"}','{\"nome\": \"Barco Novo\", \"arqueacao_bruta\": \"15\"}','[\"nome\", \"arqueacao_bruta\"]','Teste de auditoria isolada da Etapa 7','u-admin-test','Administrador Teste','127.0.0.1','','2026-09-15 21:19:00'),('ccc426c3-7a91-44f0-8218-784218070703','CLIENTE','ab9ab402-22c4-4ee9-a29d-f8fb63e07a47','CRIACAO',NULL,'{\"nome\": \"Armador Solimões Web Test 4602\", \"email\": \"web_2605@solimoes.com.br\", \"perfil\": \"armador\", \"cpf_cnpj\": \"37132239000125\", \"endereco\": \"Av. Manaus Moderna, 500 - Centro, Manaus/AM\", \"telefone\": \"(92) 99123-4567\", \"tipo_pessoa\": \"PJ\"}','[\"nome\", \"tipo_pessoa\", \"cpf_cnpj\", \"perfil\", \"telefone\", \"email\", \"endereco\"]','Cadastro de armador no sistema','dd121661-feb4-42f6-895a-68eb0608d1e4','admin','127.0.0.1','','2026-09-15 22:07:23'),('cd4b9bb2-fdd7-4efb-89ef-e92b7e91ac56','CLIENTE','2566e862-6d2f-4158-9140-4d0c88b8a6d1','CRIACAO',NULL,'{\"nome\": \"Armador Solimões Web Test 1561\", \"email\": \"web_7887@solimoes.com.br\", \"perfil\": \"armador\", \"cpf_cnpj\": \"52385436000140\", \"endereco\": \"Av. Manaus Moderna, 500 - Centro, Manaus/AM\", \"telefone\": \"(92) 99123-4567\", \"tipo_pessoa\": \"PJ\"}','[\"nome\", \"tipo_pessoa\", \"cpf_cnpj\", \"perfil\", \"telefone\", \"email\", \"endereco\"]','Cadastro de armador no sistema','dd121661-feb4-42f6-895a-68eb0608d1e4','admin','127.0.0.1','','2026-09-15 21:48:05'),('ce0f4c8c-4def-45a9-9d07-c8c0bd4a714d','CLIENTE','ae5b01a8-f8cb-44cf-ab42-cfc55de97ac2','CRIACAO',NULL,'{\"nome\": \"Armador Solimões Web Test 6522\", \"email\": \"web_1151@solimoes.com.br\", \"perfil\": \"armador\", \"cpf_cnpj\": \"50415833000191\", \"endereco\": \"Av. Manaus Moderna, 500 - Centro, Manaus/AM\", \"telefone\": \"(92) 99123-4567\", \"tipo_pessoa\": \"PJ\"}','[\"nome\", \"tipo_pessoa\", \"cpf_cnpj\", \"perfil\", \"telefone\", \"email\", \"endereco\"]','Cadastro de armador no sistema','dd121661-feb4-42f6-895a-68eb0608d1e4','admin','127.0.0.1','','2026-09-16 00:07:40'),('d29e0287-0539-4489-b3af-7407a1f90be1','EMBARCACAO','4ed8c64f-1db5-43db-ad39-d55f8e1c8503','CRIACAO',NULL,'{\":id\": \"4ed8c64f-1db5-43db-ad39-d55f8e1c8503\", \":ano\": 2022, \":nome\": \"B/M SOLIMÕES EXPRESS 588\", \":registro\": null, \":criado_por\": \"dd121661-feb4-42f6-895a-68eb0608d1e4\", \":boca_maxima\": null, \":observacoes\": null, \":porte_bruto\": null, \":potencia_kw\": \"330\", \":boca_moldada\": 6.4, \":modelo_motor\": \"DS11\", \":numero_casco\": null, \":numero_motor\": \"SC-998877\", \":tipo_servico\": null, \":acessibilidade\": null, \":area_navegacao\": \"Área 1 e 2\", \":borda_livre_mm\": null, \":estaleiro_nome\": null, \":material_casco\": \"Aço\", \":pontal_moldado\": 2.1, \":tipo_navegacao\": \"Interior\", \":arqueacao_bruta\": \"115.00\", \":calado_maximo_m\": 1.5, \":comprimento_lpp\": null, \":obs_passageiros\": null, \":porto_inscricao\": \"Manaus-AM\", \":tipo_embarcacao\": \"Balsa\", \":autorizado_carga\": null, \":borda_livre_tipo\": null, \":fabricante_motor\": \"Scania\", \":local_construcao\": null, \":metodo_arqueacao\": null, \":numero_inscricao\": \"021-714694-WEB\", \":possui_propulsao\": 1, \":arqueacao_liquida\": null, \":cnarq_data_quilha\": null, \":comprimento_casco\": null, \":comprimento_total\": 28.5, \":estaleiro_cpf_cnpj\": null, \":estaleiro_endereco\": null, \":indicativo_chamada\": null, \":numero_tripulantes\": null, \":tipo_embarcacao_id\": \"06a95b60-75d0-11f1-98f0-5ed0db5eacb7\", \":cnbl_area_navegacao\": null, \":centro_disco_situado\": null, \":cnbl_tipo_embarcacao\": null, \":numero_passageiros_n1\": null, \":numero_passageiros_n2\": null, \":acrescimo_agua_salgada\": null, \":cnarq_calado_moldado_m\": null, \":marca_linha_carga_area1\": null, \":marca_linha_carga_area2\": null, \":cnarq_espacos_excluidos_m3\": null, \":cnarq_espacos_incluidos_ab\": null, \":cnarq_espacos_incluidos_al\": null, \":dist_linha_conves_bico_proa\": null, \":aresta_superior_linha_conves\": null, \":dist_linha_conves_abaixo_disco\": null, \":cnarq_data_local_arqueacao_original\": null, \":cnarq_data_local_ultima_rearqueacao\": null}','[\":nome\", \":registro\", \":tipo_embarcacao_id\", \":tipo_embarcacao\", \":cnbl_tipo_embarcacao\", \":ano\", \":porto_inscricao\", \":numero_inscricao\", \":indicativo_chamada\", \":observacoes\", \":possui_propulsao\", \":fabricante_motor\", \":modelo_motor\", \":numero_motor\", \":potencia_kw\", \":material_casco\", \":tipo_navegacao\", \":area_navegacao\", \":cnbl_area_navegacao\", \":tipo_servico\", \":autorizado_carga\", \":numero_tripulantes\", \":numero_passageiros_n1\", \":numero_passageiros_n2\", \":obs_passageiros\", \":acessibilidade\", \":comprimento_total\", \":comprimento_casco\", \":comprimento_lpp\", \":pontal_moldado\", \":boca_moldada\", \":boca_maxima\", \":arqueacao_bruta\", \":arqueacao_liquida\", \":metodo_arqueacao\", \":cnarq_data_quilha\", \":cnarq_calado_moldado_m\", \":cnarq_espacos_incluidos_ab\", \":cnarq_espacos_incluidos_al\", \":cnarq_espacos_excluidos_m3\", \":cnarq_data_local_arqueacao_original\", \":cnarq_data_local_ultima_rearqueacao\", \":local_construcao\", \":numero_casco\", \":porte_bruto\", \":estaleiro_nome\", \":estaleiro_cpf_cnpj\", \":estaleiro_endereco\", \":borda_livre_mm\", \":borda_livre_tipo\", \":calado_maximo_m\", \":aresta_superior_linha_conves\", \":centro_disco_situado\", \":acrescimo_agua_salgada\", \":dist_linha_conves_bico_proa\", \":dist_linha_conves_abaixo_disco\", \":marca_linha_carga_area1\", \":marca_linha_carga_area2\", \":id\", \":criado_por\"]','Cadastro inicial da embarcação','dd121661-feb4-42f6-895a-68eb0608d1e4','admin','127.0.0.1','','2026-09-15 21:46:43'),('d2e86eec-2568-49ab-9872-48b16eaea640','EMBARCACAO','emb-teste-sgq','ALTERACAO','{\"nome\": \"Barco Antigo\", \"arqueacao_bruta\": \"10\"}','{\"nome\": \"Barco Novo\", \"arqueacao_bruta\": \"15\"}','[\"nome\", \"arqueacao_bruta\"]','Teste de auditoria isolada da Etapa 7','u-admin-test','Administrador Teste','127.0.0.1','','2026-09-15 21:47:57'),('dd60f887-f1ff-48d2-a6f4-12a2ed8e029a','USUARIO','9cd7e53a-da9d-4f2b-9b32-328be32da2f0','ALTERACAO','{\"id\": \"9cd7e53a-da9d-4f2b-9b32-328be32da2f0\", \"nome\": \"itamar\", \"ativo\": 1, \"cargo\": \"ANALISTA\", \"email\": \"analista@teste.com\", \"criado_em\": \"2026-07-16 15:38:12\", \"gestor_id\": null, \"senha_hash\": \"$2y$10$UVcrg97kOC70a7pwjPMQZ./v0r/3ceeJJl0XztnYnXxxveAz32b22\", \"status_sgq\": \"QUALIFICADO\", \"excluido_em\": null, \"atualizado_em\": \"2026-07-28 06:40:01\", \"escritorio_id\": \"342323aa-142c-447b-b392-7421e538f041\", \"acesso_financeiro\": 0, \"escopo_habilitacao\": null, \"acesso_documentacao\": 0, \"registro_conselho_tipo\": null, \"registro_conselho_numero\": null, \"credencial_marinha_numero\": null, \"registro_conselho_validade\": null, \"credencial_marinha_validade\": null, \"data_ultima_avaliacao_competencia\": null}','{\"id\": \"9cd7e53a-da9d-4f2b-9b32-328be32da2f0\", \"nome\": \"itamar\", \"ativo\": 1, \"cargo\": \"ANALISTA\", \"email\": \"analista@teste.com\", \"criado_em\": \"2026-07-16 15:38:12\", \"gestor_id\": null, \"senha_hash\": \"$2y$10$Fynqbm7wWlmJrGljeaK.iuu3YXucyUBwVZwSE.JY8RXQDA7lAwSOK\", \"status_sgq\": \"QUALIFICADO\", \"excluido_em\": null, \"atualizado_em\": \"2026-09-13 05:37:38\", \"escritorio_id\": \"342323aa-142c-447b-b392-7421e538f041\", \"acesso_financeiro\": 0, \"escopo_habilitacao\": null, \"acesso_documentacao\": 0, \"registro_conselho_tipo\": null, \"registro_conselho_numero\": null, \"credencial_marinha_numero\": null, \"registro_conselho_validade\": null, \"credencial_marinha_validade\": null, \"data_ultima_avaliacao_competencia\": null}','[\"senha_hash\", \"atualizado_em\"]','Atualização cadastral e de credenciais técnicas do usuário.','dd121661-feb4-42f6-895a-68eb0608d1e4','admin','172.23.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0','2026-09-13 02:37:38'),('e432b257-27a1-4169-ad1f-b73794f35d82','EMBARCACAO','51248719-4e15-416c-bba8-f2cfcc5be14d','CRIACAO',NULL,'{\":id\": \"51248719-4e15-416c-bba8-f2cfcc5be14d\", \":ano\": 2022, \":nome\": \"B/M SOLIMÕES EXPRESS 838\", \":registro\": null, \":criado_por\": \"dd121661-feb4-42f6-895a-68eb0608d1e4\", \":boca_maxima\": null, \":observacoes\": null, \":porte_bruto\": null, \":potencia_kw\": \"330\", \":boca_moldada\": 6.4, \":modelo_motor\": \"DS11\", \":numero_casco\": null, \":numero_motor\": \"SC-998877\", \":tipo_servico\": null, \":acessibilidade\": null, \":area_navegacao\": \"Área 1 e 2\", \":borda_livre_mm\": null, \":estaleiro_nome\": null, \":material_casco\": \"Aço\", \":pontal_moldado\": 2.1, \":tipo_navegacao\": \"Interior\", \":arqueacao_bruta\": \"115.00\", \":calado_maximo_m\": 1.5, \":comprimento_lpp\": null, \":obs_passageiros\": null, \":porto_inscricao\": \"Manaus-AM\", \":tipo_embarcacao\": \"Balsa\", \":autorizado_carga\": null, \":borda_livre_tipo\": null, \":fabricante_motor\": \"Scania\", \":local_construcao\": null, \":metodo_arqueacao\": null, \":numero_inscricao\": \"021-370881-WEB\", \":possui_propulsao\": 1, \":arqueacao_liquida\": null, \":cnarq_data_quilha\": null, \":comprimento_casco\": null, \":comprimento_total\": 28.5, \":estaleiro_cpf_cnpj\": null, \":estaleiro_endereco\": null, \":indicativo_chamada\": null, \":numero_tripulantes\": null, \":tipo_embarcacao_id\": \"06a95b60-75d0-11f1-98f0-5ed0db5eacb7\", \":cnbl_area_navegacao\": null, \":centro_disco_situado\": null, \":cnbl_tipo_embarcacao\": null, \":numero_passageiros_n1\": null, \":numero_passageiros_n2\": null, \":acrescimo_agua_salgada\": null, \":cnarq_calado_moldado_m\": null, \":marca_linha_carga_area1\": null, \":marca_linha_carga_area2\": null, \":cnarq_espacos_excluidos_m3\": null, \":cnarq_espacos_incluidos_ab\": null, \":cnarq_espacos_incluidos_al\": null, \":dist_linha_conves_bico_proa\": null, \":aresta_superior_linha_conves\": null, \":dist_linha_conves_abaixo_disco\": null, \":cnarq_data_local_arqueacao_original\": null, \":cnarq_data_local_ultima_rearqueacao\": null}','[\":nome\", \":registro\", \":tipo_embarcacao_id\", \":tipo_embarcacao\", \":cnbl_tipo_embarcacao\", \":ano\", \":porto_inscricao\", \":numero_inscricao\", \":indicativo_chamada\", \":observacoes\", \":possui_propulsao\", \":fabricante_motor\", \":modelo_motor\", \":numero_motor\", \":potencia_kw\", \":material_casco\", \":tipo_navegacao\", \":area_navegacao\", \":cnbl_area_navegacao\", \":tipo_servico\", \":autorizado_carga\", \":numero_tripulantes\", \":numero_passageiros_n1\", \":numero_passageiros_n2\", \":obs_passageiros\", \":acessibilidade\", \":comprimento_total\", \":comprimento_casco\", \":comprimento_lpp\", \":pontal_moldado\", \":boca_moldada\", \":boca_maxima\", \":arqueacao_bruta\", \":arqueacao_liquida\", \":metodo_arqueacao\", \":cnarq_data_quilha\", \":cnarq_calado_moldado_m\", \":cnarq_espacos_incluidos_ab\", \":cnarq_espacos_incluidos_al\", \":cnarq_espacos_excluidos_m3\", \":cnarq_data_local_arqueacao_original\", \":cnarq_data_local_ultima_rearqueacao\", \":local_construcao\", \":numero_casco\", \":porte_bruto\", \":estaleiro_nome\", \":estaleiro_cpf_cnpj\", \":estaleiro_endereco\", \":borda_livre_mm\", \":borda_livre_tipo\", \":calado_maximo_m\", \":aresta_superior_linha_conves\", \":centro_disco_situado\", \":acrescimo_agua_salgada\", \":dist_linha_conves_bico_proa\", \":dist_linha_conves_abaixo_disco\", \":marca_linha_carga_area1\", \":marca_linha_carga_area2\", \":id\", \":criado_por\"]','Cadastro inicial da embarcação','dd121661-feb4-42f6-895a-68eb0608d1e4','admin','127.0.0.1','','2026-09-16 00:23:23'),('ec46ca35-64c4-42be-ae11-d353417ac220','EMBARCACAO','81e81cf0-42b3-45f9-94bb-d701c5c02976','CRIACAO',NULL,'{\":id\": \"81e81cf0-42b3-45f9-94bb-d701c5c02976\", \":ano\": 2022, \":nome\": \"B/M SOLIMÕES EXPRESS 460\", \":registro\": null, \":criado_por\": \"dd121661-feb4-42f6-895a-68eb0608d1e4\", \":boca_maxima\": null, \":observacoes\": null, \":porte_bruto\": null, \":potencia_kw\": \"330\", \":boca_moldada\": 6.4, \":modelo_motor\": \"DS11\", \":numero_casco\": null, \":numero_motor\": \"SC-998877\", \":tipo_servico\": null, \":acessibilidade\": null, \":area_navegacao\": \"Área 1 e 2\", \":borda_livre_mm\": null, \":estaleiro_nome\": null, \":material_casco\": \"Aço\", \":pontal_moldado\": 2.1, \":tipo_navegacao\": \"Interior\", \":arqueacao_bruta\": \"115.00\", \":calado_maximo_m\": 1.5, \":comprimento_lpp\": null, \":obs_passageiros\": null, \":porto_inscricao\": \"Manaus-AM\", \":tipo_embarcacao\": \"Balsa\", \":autorizado_carga\": null, \":borda_livre_tipo\": null, \":fabricante_motor\": \"Scania\", \":local_construcao\": null, \":metodo_arqueacao\": null, \":numero_inscricao\": \"021-507263-WEB\", \":possui_propulsao\": 1, \":arqueacao_liquida\": null, \":cnarq_data_quilha\": null, \":comprimento_casco\": null, \":comprimento_total\": 28.5, \":estaleiro_cpf_cnpj\": null, \":estaleiro_endereco\": null, \":indicativo_chamada\": null, \":numero_tripulantes\": null, \":tipo_embarcacao_id\": \"06a95b60-75d0-11f1-98f0-5ed0db5eacb7\", \":cnbl_area_navegacao\": null, \":centro_disco_situado\": null, \":cnbl_tipo_embarcacao\": null, \":numero_passageiros_n1\": null, \":numero_passageiros_n2\": null, \":acrescimo_agua_salgada\": null, \":cnarq_calado_moldado_m\": null, \":marca_linha_carga_area1\": null, \":marca_linha_carga_area2\": null, \":cnarq_espacos_excluidos_m3\": null, \":cnarq_espacos_incluidos_ab\": null, \":cnarq_espacos_incluidos_al\": null, \":dist_linha_conves_bico_proa\": null, \":aresta_superior_linha_conves\": null, \":dist_linha_conves_abaixo_disco\": null, \":cnarq_data_local_arqueacao_original\": null, \":cnarq_data_local_ultima_rearqueacao\": null}','[\":nome\", \":registro\", \":tipo_embarcacao_id\", \":tipo_embarcacao\", \":cnbl_tipo_embarcacao\", \":ano\", \":porto_inscricao\", \":numero_inscricao\", \":indicativo_chamada\", \":observacoes\", \":possui_propulsao\", \":fabricante_motor\", \":modelo_motor\", \":numero_motor\", \":potencia_kw\", \":material_casco\", \":tipo_navegacao\", \":area_navegacao\", \":cnbl_area_navegacao\", \":tipo_servico\", \":autorizado_carga\", \":numero_tripulantes\", \":numero_passageiros_n1\", \":numero_passageiros_n2\", \":obs_passageiros\", \":acessibilidade\", \":comprimento_total\", \":comprimento_casco\", \":comprimento_lpp\", \":pontal_moldado\", \":boca_moldada\", \":boca_maxima\", \":arqueacao_bruta\", \":arqueacao_liquida\", \":metodo_arqueacao\", \":cnarq_data_quilha\", \":cnarq_calado_moldado_m\", \":cnarq_espacos_incluidos_ab\", \":cnarq_espacos_incluidos_al\", \":cnarq_espacos_excluidos_m3\", \":cnarq_data_local_arqueacao_original\", \":cnarq_data_local_ultima_rearqueacao\", \":local_construcao\", \":numero_casco\", \":porte_bruto\", \":estaleiro_nome\", \":estaleiro_cpf_cnpj\", \":estaleiro_endereco\", \":borda_livre_mm\", \":borda_livre_tipo\", \":calado_maximo_m\", \":aresta_superior_linha_conves\", \":centro_disco_situado\", \":acrescimo_agua_salgada\", \":dist_linha_conves_bico_proa\", \":dist_linha_conves_abaixo_disco\", \":marca_linha_carga_area1\", \":marca_linha_carga_area2\", \":id\", \":criado_por\"]','Cadastro inicial da embarcação','dd121661-feb4-42f6-895a-68eb0608d1e4','admin','127.0.0.1','','2026-09-15 21:46:12'),('f4e5979b-a476-4b4c-959e-57539d529ec1','EMBARCACAO','emb-teste-sgq','ALTERACAO','{\"nome\": \"Barco Antigo\", \"arqueacao_bruta\": \"10\"}','{\"nome\": \"Barco Novo\", \"arqueacao_bruta\": \"15\"}','[\"nome\", \"arqueacao_bruta\"]','Teste de auditoria isolada da Etapa 7','u-admin-test','Administrador Teste','127.0.0.1','','2026-09-15 20:07:00'),('f6ce9fa3-e406-4ec1-9d4a-e4cb800ac695','EMBARCACAO','b72a809a-8d44-411c-ac4f-b9f5a7fc6986','CRIACAO',NULL,'{\":id\": \"b72a809a-8d44-411c-ac4f-b9f5a7fc6986\", \":ano\": 2022, \":nome\": \"B/M SOLIMÕES EXPRESS 445\", \":registro\": null, \":criado_por\": \"dd121661-feb4-42f6-895a-68eb0608d1e4\", \":boca_maxima\": null, \":observacoes\": null, \":porte_bruto\": null, \":potencia_kw\": \"330\", \":boca_moldada\": 6.4, \":modelo_motor\": \"DS11\", \":numero_casco\": null, \":numero_motor\": \"SC-998877\", \":tipo_servico\": null, \":acessibilidade\": null, \":area_navegacao\": \"Área 1 e 2\", \":borda_livre_mm\": null, \":estaleiro_nome\": null, \":material_casco\": \"Aço\", \":pontal_moldado\": 2.1, \":tipo_navegacao\": \"Interior\", \":arqueacao_bruta\": \"115.00\", \":calado_maximo_m\": 1.5, \":comprimento_lpp\": null, \":obs_passageiros\": null, \":porto_inscricao\": \"Manaus-AM\", \":tipo_embarcacao\": \"Balsa\", \":autorizado_carga\": null, \":borda_livre_tipo\": null, \":fabricante_motor\": \"Scania\", \":local_construcao\": null, \":metodo_arqueacao\": null, \":numero_inscricao\": \"021-586919-WEB\", \":possui_propulsao\": 1, \":arqueacao_liquida\": null, \":cnarq_data_quilha\": null, \":comprimento_casco\": null, \":comprimento_total\": 28.5, \":estaleiro_cpf_cnpj\": null, \":estaleiro_endereco\": null, \":indicativo_chamada\": null, \":numero_tripulantes\": null, \":tipo_embarcacao_id\": \"06a95b60-75d0-11f1-98f0-5ed0db5eacb7\", \":cnbl_area_navegacao\": null, \":centro_disco_situado\": null, \":cnbl_tipo_embarcacao\": null, \":numero_passageiros_n1\": null, \":numero_passageiros_n2\": null, \":acrescimo_agua_salgada\": null, \":cnarq_calado_moldado_m\": null, \":marca_linha_carga_area1\": null, \":marca_linha_carga_area2\": null, \":cnarq_espacos_excluidos_m3\": null, \":cnarq_espacos_incluidos_ab\": null, \":cnarq_espacos_incluidos_al\": null, \":dist_linha_conves_bico_proa\": null, \":aresta_superior_linha_conves\": null, \":dist_linha_conves_abaixo_disco\": null, \":cnarq_data_local_arqueacao_original\": null, \":cnarq_data_local_ultima_rearqueacao\": null}','[\":nome\", \":registro\", \":tipo_embarcacao_id\", \":tipo_embarcacao\", \":cnbl_tipo_embarcacao\", \":ano\", \":porto_inscricao\", \":numero_inscricao\", \":indicativo_chamada\", \":observacoes\", \":possui_propulsao\", \":fabricante_motor\", \":modelo_motor\", \":numero_motor\", \":potencia_kw\", \":material_casco\", \":tipo_navegacao\", \":area_navegacao\", \":cnbl_area_navegacao\", \":tipo_servico\", \":autorizado_carga\", \":numero_tripulantes\", \":numero_passageiros_n1\", \":numero_passageiros_n2\", \":obs_passageiros\", \":acessibilidade\", \":comprimento_total\", \":comprimento_casco\", \":comprimento_lpp\", \":pontal_moldado\", \":boca_moldada\", \":boca_maxima\", \":arqueacao_bruta\", \":arqueacao_liquida\", \":metodo_arqueacao\", \":cnarq_data_quilha\", \":cnarq_calado_moldado_m\", \":cnarq_espacos_incluidos_ab\", \":cnarq_espacos_incluidos_al\", \":cnarq_espacos_excluidos_m3\", \":cnarq_data_local_arqueacao_original\", \":cnarq_data_local_ultima_rearqueacao\", \":local_construcao\", \":numero_casco\", \":porte_bruto\", \":estaleiro_nome\", \":estaleiro_cpf_cnpj\", \":estaleiro_endereco\", \":borda_livre_mm\", \":borda_livre_tipo\", \":calado_maximo_m\", \":aresta_superior_linha_conves\", \":centro_disco_situado\", \":acrescimo_agua_salgada\", \":dist_linha_conves_bico_proa\", \":dist_linha_conves_abaixo_disco\", \":marca_linha_carga_area1\", \":marca_linha_carga_area2\", \":id\", \":criado_por\"]','Cadastro inicial da embarcação','dd121661-feb4-42f6-895a-68eb0608d1e4','admin','127.0.0.1','','2026-09-15 22:04:46'),('f8723fe0-42ca-4555-87f5-ddf79620dbea','CLIENTE','b20b7e3e-c109-4804-900b-6c4143b91390','CRIACAO',NULL,'{\"nome\": \"Armador Solimões Web Test 8525\", \"email\": \"web_8496@solimoes.com.br\", \"perfil\": \"armador\", \"cpf_cnpj\": \"78107473000180\", \"endereco\": \"Av. Manaus Moderna, 500 - Centro, Manaus/AM\", \"telefone\": \"(92) 99123-4567\", \"tipo_pessoa\": \"PJ\"}','[\"nome\", \"tipo_pessoa\", \"cpf_cnpj\", \"perfil\", \"telefone\", \"email\", \"endereco\"]','Cadastro de armador no sistema','dd121661-feb4-42f6-895a-68eb0608d1e4','admin','127.0.0.1','','2026-09-16 01:03:12');
/*!40000 ALTER TABLE `sgq_auditoria_cadastral` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sgq_matriz_riscos`
--

DROP TABLE IF EXISTS `sgq_matriz_riscos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sgq_matriz_riscos` (
  `id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `codigo_risco` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `processo_setor` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `tipo_risco` enum('AMEACA','OPORTUNIDADE') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'AMEACA',
  `descricao_risco` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `causas` text COLLATE utf8mb4_general_ci,
  `impacto_consequencias` text COLLATE utf8mb4_general_ci,
  `probabilidade` tinyint unsigned NOT NULL DEFAULT '3',
  `impacto` tinyint unsigned NOT NULL DEFAULT '3',
  `nivel_risco` enum('BAIXO','MEDIO','ALTO','CRITICO') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'MEDIO',
  `acao_mitigacao` text COLLATE utf8mb4_general_ci NOT NULL,
  `responsavel_nome` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `prazo_revisao` date DEFAULT NULL,
  `status_tratamento` enum('IDENTIFICADO','EM_MITIGACAO','MITIGADO','RESIDUAL_ACEITO') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'EM_MITIGACAO',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo_risco` (`codigo_risco`),
  KEY `idx_risco_processo` (`processo_setor`),
  KEY `idx_risco_nivel` (`nivel_risco`),
  KEY `idx_risco_status` (`status_tratamento`),
  KEY `idx_matriz_risco_tipo_nivel` (`tipo_risco`,`nivel_risco`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sgq_matriz_riscos`
--

LOCK TABLES `sgq_matriz_riscos` WRITE;
/*!40000 ALTER TABLE `sgq_matriz_riscos` DISABLE KEYS */;
INSERT INTO `sgq_matriz_riscos` VALUES ('3bc8d4e8-ae05-11f1-8a7c-be2fb1f77be2','RSK-2026-001','Operação de Campo','AMEACA','Indisponibilidade de sinal 4G/5G em áreas fluviais e terminais de carga remotos','Infraestrutura de telecomunicações instável na bacia amazônica','Atraso no sincronismo de laudos e fotos da vistoria',4,3,'ALTO','Utilização obrigatória do PWA de Campo com suporte a armazenamento local (IndexedDB) e sincronização resiliente em segundo plano.','Coordenador Operacional','2027-03-11','EM_MITIGACAO','2026-09-11 17:21:32','2026-09-11 17:21:32'),('3bc8d856-ae05-11f1-8a7c-be2fb1f77be2','RSK-2026-002','Competência Técnica','AMEACA','Escalação de vistoriador com credencial profissional ou da Autoridade Marítima (DPC) expirada','Falha no acompanhamento tempestivo do calendário de reciclagem','Rejeição do laudo pela Capitania dos Portos e não conformidade ISO 7.2',2,5,'ALTO','Trava automática de validação no ERP no ato do agendamento contra a data da vistoria com bloqueio e retorno HTTP 400 em caso de tentativa de burla.','Responsável Técnico','2027-09-11','MITIGADO','2026-09-11 17:21:32','2026-09-11 17:21:32'),('3bc8db58-ae05-11f1-8a7c-be2fb1f77be2','RSK-2026-003','Controle Metrológico','AMEACA','Utilização de aparelho de medição de espessura de chapeamento com laudo de calibração RBC vencido','Falta de calibração periódica do transdutor de ultrassom','Imprecisão na medição de perda de espessura de casco e apontamentos A/S contestados',2,4,'MEDIO','Plano de calibração anual compulsório de todos os instrumentos de medição da empresa em laboratório RBC credenciado pelo Inmetro.','Gerente da Qualidade','2027-03-11','EM_MITIGACAO','2026-09-11 17:21:32','2026-09-11 17:21:32'),('3bc8dc51-ae05-11f1-8a7c-be2fb1f77be2','RSK-2026-004','Regulatório / NORMAM','AMEACA','Publicação de nova Portaria da DPC alterando critérios de salvatagem sem atualização imediata dos checklists','Descompasso entre o Diário Oficial da União e as rotinas operacionais','Emissão de relatório com base em versão revogada de NORMAM',3,3,'MEDIO','Reunião mensal de alinhamento normativo do corpo técnico e revisão imediata dos blocos de checklist no ERP.','Responsável Técnico','2026-12-11','EM_MITIGACAO','2026-09-11 17:21:32','2026-09-11 17:21:32'),('3bc8dce2-ae05-11f1-8a7c-be2fb1f77be2','RSK-2026-005','Comercial e Estratégico','OPORTUNIDADE','Aumento de demanda por certificação de comboios de empurradores fluviais de grande porte no Arco Norte','Crescimento do escoamento de safras agrícolas por hidrovias','Expansão da receita e consolidação da liderança técnica regional',4,4,'ALTO','Treinamento e habilitação do corpo de engenheiros navais para vistorias em comboios integrados e balsas oceânicas.','Diretoria Executiva','2027-03-11','EM_MITIGACAO','2026-09-11 17:21:32','2026-09-11 17:21:32');
/*!40000 ALTER TABLE `sgq_matriz_riscos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sgq_nao_conformidades`
--

DROP TABLE IF EXISTS `sgq_nao_conformidades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sgq_nao_conformidades` (
  `id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `numero_rnc` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `origem` enum('AUDITORIA_INTERNA_RT','INSPECAO_CAMPO','RECLAMACAO_CLIENTE','AUDITORIA_EXTERNA') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'AUDITORIA_INTERNA_RT',
  `ordem_servico_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `vistoria_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `embarcacao_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cliente_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `classificacao_falha` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `severidade` enum('BAIXA','MEDIA','CRITICA_IMPEDITIVA') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'MEDIA',
  `titulo` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `descricao_detalhada` text COLLATE utf8mb4_general_ci NOT NULL,
  `analise_causa_raiz` text COLLATE utf8mb4_general_ci,
  `status_ciclo_vida` enum('ABERTA','EM_ANALISE_CAUSA','PLANO_ACAO_DEFINIDO','EM_EXECUCAO','AGUARDANDO_EFICACIA','ENCERRADA_EFICAZ','REABERTA') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'ABERTA',
  `responsavel_abertura_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `responsavel_abertura_nome` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `data_identificacao` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data_conclusao_prevista` date DEFAULT NULL,
  `encerrada_em` datetime DEFAULT NULL,
  `encerrada_por` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_rnc` (`numero_rnc`),
  KEY `idx_rnc_ordem_servico` (`ordem_servico_id`),
  KEY `idx_rnc_vistoria` (`vistoria_id`),
  KEY `idx_rnc_embarcacao` (`embarcacao_id`),
  KEY `idx_rnc_status` (`status_ciclo_vida`),
  KEY `idx_rnc_criado_em` (`criado_em`),
  KEY `idx_rnc_status_severidade` (`status_ciclo_vida`,`severidade`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sgq_nao_conformidades`
--

LOCK TABLES `sgq_nao_conformidades` WRITE;
/*!40000 ALTER TABLE `sgq_nao_conformidades` DISABLE KEYS */;
INSERT INTO `sgq_nao_conformidades` VALUES ('0f6e952c-5b5f-4e0d-83e3-219ab26eba2f','RNC-2026-0003','RECLAMACAO_CLIENTE',NULL,NULL,'317ba743-7aa6-4d66-a845-2d4670f126f0','1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed','Atraso de Prazo / Vistoria','CRITICA_IMPEDITIVA','[Reclamação Formal (ISO 10.2)] solicito a documentação','MANIFESTAÇÃO REGISTRADA PELO CLIENTE NO PORTAL:\nTipo: Reclamação Formal (ISO 10.2)\nCliente: Rosano Souza\nCategoria: Atraso de Prazo / Vistoria\nData de Registro: 12/09/2026 02:42\n\nRELATO DO CLIENTE:\ncertificado atrasado',NULL,'EM_EXECUCAO',NULL,'Portal do Cliente - Rosano Souza','2026-09-12 05:42:48',NULL,NULL,NULL,'2026-09-12 05:42:48','2026-09-12 06:24:10'),('8bd56fbf-5d15-4dc6-b681-89e48d8da542','RNC-2026-0002','RECLAMACAO_CLIENTE',NULL,NULL,'317ba743-7aa6-4d66-a845-2d4670f126f0','1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed','Atraso de Prazo / Vistoria','MEDIA','[Reclamação Formal] Teste de Envio pelo Portal','Relato de teste de envio de manifestação no portal.','Identificada falha operacional e retificado procedimento conforme norma.','EM_EXECUCAO',NULL,'Portal do Cliente - Rosano Souza','2026-09-12 05:17:45',NULL,NULL,NULL,'2026-09-12 05:17:45','2026-09-12 06:17:02');
/*!40000 ALTER TABLE `sgq_nao_conformidades` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sgq_planos_acao`
--

DROP TABLE IF EXISTS `sgq_planos_acao`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sgq_planos_acao` (
  `id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `nao_conformidade_id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `o_que_fazer_what` text COLLATE utf8mb4_general_ci NOT NULL,
  `por_que_fazer_why` text COLLATE utf8mb4_general_ci,
  `onde_fazer_where` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `quem_fara_who` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `quando_fara_when` date NOT NULL,
  `como_fazer_how` text COLLATE utf8mb4_general_ci,
  `quanto_custa_how_much` decimal(12,2) NOT NULL DEFAULT '0.00',
  `status_acao` enum('PENDENTE','EM_ANDAMENTO','CONCLUIDA','CANCELADA') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'PENDENTE',
  `evidencia_cumprimento` text COLLATE utf8mb4_general_ci,
  `eficacia_aprovada_por` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `eficacia_data` datetime DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_plano_rnc` (`nao_conformidade_id`),
  KEY `idx_plano_status` (`status_acao`),
  CONSTRAINT `fk_plano_rnc` FOREIGN KEY (`nao_conformidade_id`) REFERENCES `sgq_nao_conformidades` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sgq_planos_acao`
--

LOCK TABLES `sgq_planos_acao` WRITE;
/*!40000 ALTER TABLE `sgq_planos_acao` DISABLE KEYS */;
INSERT INTO `sgq_planos_acao` VALUES ('d52dd331-de91-4b22-a3a1-c77242ccd344','0f6e952c-5b5f-4e0d-83e3-219ab26eba2f','wwewe','eeee',NULL,'amzon','2026-09-27',NULL,0.00,'CONCLUIDA',NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','2026-09-12 06:24:37','2026-09-12 06:12:07','2026-09-12 06:24:37');
/*!40000 ALTER TABLE `sgq_planos_acao` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sgq_satisfacao_clientes`
--

DROP TABLE IF EXISTS `sgq_satisfacao_clientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sgq_satisfacao_clientes` (
  `id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `ordem_servico_id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `cliente_id` char(36) COLLATE utf8mb4_general_ci NOT NULL,
  `embarcacao_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `documento_id` char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `documento_tipo` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nota_atendimento_comercial` tinyint unsigned NOT NULL,
  `nota_qualidade_tecnica` tinyint unsigned NOT NULL,
  `nota_cumprimento_prazo` tinyint unsigned NOT NULL,
  `nota_nps_geral` tinyint unsigned NOT NULL,
  `comentario_elogio_critica` text COLLATE utf8mb4_general_ci,
  `dispositivo_acesso` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'PORTAL_WEB',
  `ip_origem` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `data_avaliacao` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ordem_servico_id` (`ordem_servico_id`),
  KEY `idx_satisfacao_cliente` (`cliente_id`),
  KEY `idx_satisfacao_data` (`data_avaliacao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sgq_satisfacao_clientes`
--

LOCK TABLES `sgq_satisfacao_clientes` WRITE;
/*!40000 ALTER TABLE `sgq_satisfacao_clientes` DISABLE KEYS */;
/*!40000 ALTER TABLE `sgq_satisfacao_clientes` ENABLE KEYS */;
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
INSERT INTO `usuario_escritorios` VALUES ('349036db-2b7d-4a98-8509-97bdd3e71fe6','342323aa-142c-447b-b392-7421e538f041',1,'2026-09-11 15:28:35'),('9cd7e53a-da9d-4f2b-9b32-328be32da2f0','342323aa-142c-447b-b392-7421e538f041',1,'2026-09-16 01:45:33'),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','342323aa-142c-447b-b392-7421e538f041',1,'2026-09-11 15:28:35'),('d2a16613-dfa4-4948-8de4-8c802abdf394','342323aa-142c-447b-b392-7421e538f041',1,'2026-07-28 06:40:54'),('dd121661-feb4-42f6-895a-68eb0608d1e4','23fd0c61-2db2-4a41-807c-e18c1a26f974',0,'2026-07-28 06:39:31'),('dd121661-feb4-42f6-895a-68eb0608d1e4','342323aa-142c-447b-b392-7421e538f041',1,'2026-07-28 06:39:31');
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
  KEY `idx_permissoes_modulo` (`permissao`,`permitido`),
  CONSTRAINT `fk_usuario_permissoes_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario_permissoes`
--

LOCK TABLES `usuario_permissoes` WRITE;
/*!40000 ALTER TABLE `usuario_permissoes` DISABLE KEYS */;
INSERT INTO `usuario_permissoes` VALUES ('11111111-1111-1111-1111-111111111111','certificados',1,'2026-09-11 18:55:26'),('11111111-1111-1111-1111-111111111111','dashboard',1,'2026-09-11 18:55:26'),('11111111-1111-1111-1111-111111111111','documentacao',1,'2026-09-11 18:55:26'),('11111111-1111-1111-1111-111111111111','embarcacoes',1,'2026-09-11 18:55:26'),('11111111-1111-1111-1111-111111111111','vistorias',1,'2026-09-11 18:55:26'),('1c015cb0-3187-4068-bc6d-06585521e165','agendamentos',1,'2026-09-11 18:55:26'),('1c015cb0-3187-4068-bc6d-06585521e165','armadores',1,'2026-09-11 18:55:26'),('1c015cb0-3187-4068-bc6d-06585521e165','comercial',1,'2026-09-11 18:55:26'),('1c015cb0-3187-4068-bc6d-06585521e165','contratos',1,'2026-09-11 18:55:26'),('1c015cb0-3187-4068-bc6d-06585521e165','dashboard',1,'2026-09-11 18:55:26'),('1c015cb0-3187-4068-bc6d-06585521e165','despachantes',1,'2026-09-11 18:55:26'),('1c015cb0-3187-4068-bc6d-06585521e165','emails',1,'2026-09-11 18:55:26'),('1c015cb0-3187-4068-bc6d-06585521e165','embarcacoes',1,'2026-09-11 18:55:26'),('1c015cb0-3187-4068-bc6d-06585521e165','proprietarios',1,'2026-09-11 18:55:26'),('1c015cb0-3187-4068-bc6d-06585521e165','servicos',1,'2026-09-11 18:55:26'),('1c015cb0-3187-4068-bc6d-06585521e165','vistorias',1,'2026-09-11 18:55:26'),('22222222-2222-2222-2222-222222222222','certificados',1,'2026-09-11 18:55:26'),('22222222-2222-2222-2222-222222222222','dashboard',1,'2026-09-11 18:55:26'),('22222222-2222-2222-2222-222222222222','documentacao',1,'2026-09-11 18:55:26'),('22222222-2222-2222-2222-222222222222','embarcacoes',1,'2026-09-11 18:55:26'),('22222222-2222-2222-2222-222222222222','vistorias',1,'2026-09-11 18:55:26'),('33333333-3333-3333-3333-333333333333','certificados',1,'2026-09-11 18:55:26'),('33333333-3333-3333-3333-333333333333','dashboard',1,'2026-09-11 18:55:26'),('33333333-3333-3333-3333-333333333333','documentacao',1,'2026-09-11 18:55:26'),('33333333-3333-3333-3333-333333333333','embarcacoes',1,'2026-09-11 18:55:26'),('33333333-3333-3333-3333-333333333333','vistorias',1,'2026-09-11 18:55:26'),('349036db-2b7d-4a98-8509-97bdd3e71fe6','agendamentos',1,'2026-09-11 18:55:26'),('349036db-2b7d-4a98-8509-97bdd3e71fe6','analise_planos',0,'2026-09-11 18:15:07'),('349036db-2b7d-4a98-8509-97bdd3e71fe6','armadores',1,'2026-09-11 18:55:26'),('349036db-2b7d-4a98-8509-97bdd3e71fe6','certificados',0,'2026-07-28 01:37:51'),('349036db-2b7d-4a98-8509-97bdd3e71fe6','comercial',1,'2026-09-11 18:55:26'),('349036db-2b7d-4a98-8509-97bdd3e71fe6','configuracoes',0,'2026-07-28 01:37:51'),('349036db-2b7d-4a98-8509-97bdd3e71fe6','configuracoes_backup',0,'2026-09-16 02:07:53'),('349036db-2b7d-4a98-8509-97bdd3e71fe6','configuracoes_basicas',0,'2026-09-16 02:07:53'),('349036db-2b7d-4a98-8509-97bdd3e71fe6','configuracoes_exportacoes',0,'2026-09-16 02:07:53'),('349036db-2b7d-4a98-8509-97bdd3e71fe6','configuracoes_financeiro',0,'2026-09-16 02:07:53'),('349036db-2b7d-4a98-8509-97bdd3e71fe6','configuracoes_normam202',0,'2026-09-16 02:07:53'),('349036db-2b7d-4a98-8509-97bdd3e71fe6','contratos',1,'2026-09-11 18:55:26'),('349036db-2b7d-4a98-8509-97bdd3e71fe6','dashboard',1,'2026-09-11 18:55:26'),('349036db-2b7d-4a98-8509-97bdd3e71fe6','despachantes',1,'2026-09-11 18:55:26'),('349036db-2b7d-4a98-8509-97bdd3e71fe6','documentacao',0,'2026-07-28 01:37:51'),('349036db-2b7d-4a98-8509-97bdd3e71fe6','emails',1,'2026-09-11 18:55:26'),('349036db-2b7d-4a98-8509-97bdd3e71fe6','embarcacoes',1,'2026-09-11 18:55:26'),('349036db-2b7d-4a98-8509-97bdd3e71fe6','financeiro',0,'2026-07-28 01:37:51'),('349036db-2b7d-4a98-8509-97bdd3e71fe6','gestao_acessos_portal',0,'2026-09-16 02:07:53'),('349036db-2b7d-4a98-8509-97bdd3e71fe6','portal_clientes',0,'2026-07-28 01:37:51'),('349036db-2b7d-4a98-8509-97bdd3e71fe6','proprietarios',1,'2026-09-11 18:55:26'),('349036db-2b7d-4a98-8509-97bdd3e71fe6','protocolos_documentais',0,'2026-07-28 01:37:51'),('349036db-2b7d-4a98-8509-97bdd3e71fe6','relatorios',0,'2026-07-28 01:37:51'),('349036db-2b7d-4a98-8509-97bdd3e71fe6','relatorios_aprovacao',0,'2026-07-28 01:37:51'),('349036db-2b7d-4a98-8509-97bdd3e71fe6','responsaveis_assinatura',0,'2026-07-28 01:37:51'),('349036db-2b7d-4a98-8509-97bdd3e71fe6','servicos',1,'2026-09-11 18:55:26'),('349036db-2b7d-4a98-8509-97bdd3e71fe6','sgq',0,'2026-09-13 07:31:29'),('349036db-2b7d-4a98-8509-97bdd3e71fe6','usuarios',0,'2026-07-28 01:37:51'),('349036db-2b7d-4a98-8509-97bdd3e71fe6','vistorias',0,'2026-09-13 07:31:29'),('3774d80c-2574-470e-88a9-9781936c6de3','certificados',1,'2026-09-11 18:55:26'),('3774d80c-2574-470e-88a9-9781936c6de3','dashboard',1,'2026-09-11 18:55:26'),('3774d80c-2574-470e-88a9-9781936c6de3','documentacao',1,'2026-09-11 18:55:26'),('3774d80c-2574-470e-88a9-9781936c6de3','embarcacoes',1,'2026-09-11 18:55:26'),('3774d80c-2574-470e-88a9-9781936c6de3','vistorias',1,'2026-09-11 18:55:26'),('74e02f95-fbe6-42f3-bedf-f8535e4d13aa','certificados',1,'2026-09-11 18:55:26'),('74e02f95-fbe6-42f3-bedf-f8535e4d13aa','dashboard',1,'2026-09-11 18:55:26'),('74e02f95-fbe6-42f3-bedf-f8535e4d13aa','documentacao',1,'2026-09-11 18:55:26'),('74e02f95-fbe6-42f3-bedf-f8535e4d13aa','embarcacoes',1,'2026-09-11 18:55:26'),('74e02f95-fbe6-42f3-bedf-f8535e4d13aa','vistorias',1,'2026-09-11 18:55:26'),('9cd7e53a-da9d-4f2b-9b32-328be32da2f0','agendamentos',0,'2026-09-16 02:28:56'),('9cd7e53a-da9d-4f2b-9b32-328be32da2f0','analise_planos',1,'2026-09-13 05:38:01'),('9cd7e53a-da9d-4f2b-9b32-328be32da2f0','armadores',1,'2026-09-13 05:38:01'),('9cd7e53a-da9d-4f2b-9b32-328be32da2f0','certificados',1,'2026-09-13 05:38:01'),('9cd7e53a-da9d-4f2b-9b32-328be32da2f0','comercial',0,'2026-09-16 02:28:56'),('9cd7e53a-da9d-4f2b-9b32-328be32da2f0','configuracoes',0,'2026-09-16 02:28:56'),('9cd7e53a-da9d-4f2b-9b32-328be32da2f0','configuracoes_backup',0,'2026-09-16 02:28:56'),('9cd7e53a-da9d-4f2b-9b32-328be32da2f0','configuracoes_basicas',0,'2026-09-16 02:28:56'),('9cd7e53a-da9d-4f2b-9b32-328be32da2f0','configuracoes_exportacoes',0,'2026-09-16 02:28:56'),('9cd7e53a-da9d-4f2b-9b32-328be32da2f0','configuracoes_financeiro',0,'2026-09-16 02:28:56'),('9cd7e53a-da9d-4f2b-9b32-328be32da2f0','configuracoes_normam202',1,'2026-09-16 02:23:05'),('9cd7e53a-da9d-4f2b-9b32-328be32da2f0','dashboard',1,'2026-09-11 18:55:26'),('9cd7e53a-da9d-4f2b-9b32-328be32da2f0','despachantes',0,'2026-09-16 02:28:56'),('9cd7e53a-da9d-4f2b-9b32-328be32da2f0','documentacao',1,'2026-09-13 05:38:01'),('9cd7e53a-da9d-4f2b-9b32-328be32da2f0','emails',0,'2026-09-16 02:28:56'),('9cd7e53a-da9d-4f2b-9b32-328be32da2f0','embarcacoes',1,'2026-09-13 05:38:01'),('9cd7e53a-da9d-4f2b-9b32-328be32da2f0','financeiro',0,'2026-09-16 02:28:56'),('9cd7e53a-da9d-4f2b-9b32-328be32da2f0','gestao_acessos_portal',0,'2026-09-16 02:28:56'),('9cd7e53a-da9d-4f2b-9b32-328be32da2f0','portal_clientes',0,'2026-09-13 07:31:29'),('9cd7e53a-da9d-4f2b-9b32-328be32da2f0','proprietarios',1,'2026-09-13 05:38:01'),('9cd7e53a-da9d-4f2b-9b32-328be32da2f0','protocolos_documentais',1,'2026-09-13 05:38:01'),('9cd7e53a-da9d-4f2b-9b32-328be32da2f0','relatorios',0,'2026-09-16 02:28:56'),('9cd7e53a-da9d-4f2b-9b32-328be32da2f0','relatorios_aprovacao',1,'2026-09-11 18:55:26'),('9cd7e53a-da9d-4f2b-9b32-328be32da2f0','responsaveis_assinatura',1,'2026-09-16 02:23:05'),('9cd7e53a-da9d-4f2b-9b32-328be32da2f0','servicos',0,'2026-09-16 02:28:56'),('9cd7e53a-da9d-4f2b-9b32-328be32da2f0','sgq',0,'2026-09-16 02:28:56'),('9cd7e53a-da9d-4f2b-9b32-328be32da2f0','usuarios',0,'2026-09-16 02:28:56'),('9cd7e53a-da9d-4f2b-9b32-328be32da2f0','vistorias',1,'2026-09-11 18:55:26'),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','agendamentos',1,'2026-09-11 18:55:26'),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','analise_planos',0,'2026-09-11 18:15:07'),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','armadores',1,'2026-09-11 18:55:26'),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','certificados',0,'2026-09-11 18:15:07'),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','comercial',1,'2026-09-11 18:55:26'),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','configuracoes',0,'2026-07-21 16:21:37'),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','configuracoes_backup',0,'2026-09-16 02:07:54'),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','configuracoes_basicas',0,'2026-09-16 02:07:54'),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','configuracoes_exportacoes',0,'2026-09-16 02:07:54'),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','configuracoes_financeiro',0,'2026-09-16 02:07:54'),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','configuracoes_normam202',0,'2026-09-16 02:07:54'),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','contratos',1,'2026-09-11 18:55:26'),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','dashboard',1,'2026-09-11 18:55:26'),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','despachantes',1,'2026-09-11 18:55:26'),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','documentacao',0,'2026-09-11 18:15:07'),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','emails',1,'2026-09-11 18:55:26'),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','embarcacoes',1,'2026-09-11 18:55:26'),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','financeiro',0,'2026-09-11 18:15:07'),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','gestao_acessos_portal',0,'2026-09-16 02:07:53'),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','portal_clientes',0,'2026-09-11 18:15:07'),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','proprietarios',1,'2026-09-11 18:55:26'),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','protocolos_documentais',0,'2026-07-28 01:37:51'),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','relatorios',0,'2026-09-11 18:15:07'),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','relatorios_aprovacao',0,'2026-09-11 18:15:07'),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','responsaveis_assinatura',0,'2026-07-21 12:48:36'),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','servicos',1,'2026-09-11 18:55:26'),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','sgq',0,'2026-09-13 07:31:29'),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','usuarios',0,'2026-07-21 12:48:36'),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','vistorias',0,'2026-09-13 07:31:29'),('d2a16613-dfa4-4948-8de4-8c802abdf394','agendamentos',1,'2026-09-12 19:35:01'),('d2a16613-dfa4-4948-8de4-8c802abdf394','analise_planos',0,'2026-07-21 16:21:37'),('d2a16613-dfa4-4948-8de4-8c802abdf394','armadores',0,'2026-07-16 16:15:33'),('d2a16613-dfa4-4948-8de4-8c802abdf394','certificados',0,'2026-09-13 07:31:29'),('d2a16613-dfa4-4948-8de4-8c802abdf394','comercial',0,'2026-07-16 16:15:33'),('d2a16613-dfa4-4948-8de4-8c802abdf394','configuracoes',0,'2026-07-16 16:15:33'),('d2a16613-dfa4-4948-8de4-8c802abdf394','configuracoes_backup',0,'2026-09-16 02:07:54'),('d2a16613-dfa4-4948-8de4-8c802abdf394','configuracoes_basicas',0,'2026-09-16 02:07:54'),('d2a16613-dfa4-4948-8de4-8c802abdf394','configuracoes_exportacoes',0,'2026-09-16 02:07:54'),('d2a16613-dfa4-4948-8de4-8c802abdf394','configuracoes_financeiro',0,'2026-09-16 02:07:54'),('d2a16613-dfa4-4948-8de4-8c802abdf394','configuracoes_normam202',1,'2026-09-16 02:07:54'),('d2a16613-dfa4-4948-8de4-8c802abdf394','dashboard',1,'2026-09-12 19:35:01'),('d2a16613-dfa4-4948-8de4-8c802abdf394','despachantes',0,'2026-07-16 16:15:33'),('d2a16613-dfa4-4948-8de4-8c802abdf394','documentacao',1,'2026-09-12 19:35:01'),('d2a16613-dfa4-4948-8de4-8c802abdf394','emails',0,'2026-07-16 16:15:33'),('d2a16613-dfa4-4948-8de4-8c802abdf394','embarcacoes',1,'2026-09-12 19:35:01'),('d2a16613-dfa4-4948-8de4-8c802abdf394','financeiro',0,'2026-07-16 16:15:33'),('d2a16613-dfa4-4948-8de4-8c802abdf394','gestao_acessos_portal',0,'2026-09-16 02:07:54'),('d2a16613-dfa4-4948-8de4-8c802abdf394','portal_clientes',0,'2026-07-16 16:15:33'),('d2a16613-dfa4-4948-8de4-8c802abdf394','proprietarios',0,'2026-07-16 16:15:33'),('d2a16613-dfa4-4948-8de4-8c802abdf394','protocolos_documentais',0,'2026-07-28 01:37:51'),('d2a16613-dfa4-4948-8de4-8c802abdf394','relatorios',0,'2026-07-16 16:15:33'),('d2a16613-dfa4-4948-8de4-8c802abdf394','relatorios_aprovacao',0,'2026-09-11 18:15:07'),('d2a16613-dfa4-4948-8de4-8c802abdf394','responsaveis_assinatura',0,'2026-07-16 16:15:33'),('d2a16613-dfa4-4948-8de4-8c802abdf394','servicos',0,'2026-07-16 16:15:33'),('d2a16613-dfa4-4948-8de4-8c802abdf394','sgq',0,'2026-09-13 07:31:29'),('d2a16613-dfa4-4948-8de4-8c802abdf394','usuarios',0,'2026-07-16 16:15:33'),('d2a16613-dfa4-4948-8de4-8c802abdf394','vistorias',1,'2026-09-12 19:35:01'),('dd121661-feb4-42f6-895a-68eb0608d1e4','analise_planos',1,'2026-07-18 14:27:15'),('e5c68a85-c920-4b11-bc93-9343d9d94f14','certificados',1,'2026-09-11 18:55:26'),('e5c68a85-c920-4b11-bc93-9343d9d94f14','dashboard',1,'2026-09-11 18:55:26'),('e5c68a85-c920-4b11-bc93-9343d9d94f14','documentacao',1,'2026-09-11 18:55:26'),('e5c68a85-c920-4b11-bc93-9343d9d94f14','embarcacoes',1,'2026-09-11 18:55:26'),('e5c68a85-c920-4b11-bc93-9343d9d94f14','vistorias',1,'2026-09-11 18:55:26');
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
  `versao_sessao` int NOT NULL DEFAULT '1',
  `excluido_em` datetime DEFAULT NULL,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `acesso_documentacao` tinyint(1) DEFAULT '0',
  `acesso_financeiro` tinyint(1) DEFAULT '0',
  `escritorio_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `gestor_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `registro_conselho_tipo` enum('CREA','CFT','OUTRO') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `registro_conselho_numero` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `registro_conselho_validade` date DEFAULT NULL,
  `credencial_marinha_numero` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `credencial_marinha_validade` date DEFAULT NULL,
  `escopo_habilitacao` text COLLATE utf8mb4_general_ci,
  `status_sgq` enum('QUALIFICADO','SUSPENSO_RECICLAGEM','DESQUALIFICADO','INATIVO') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'QUALIFICADO',
  `data_ultima_avaliacao_competencia` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_usuarios_excluido_em` (`excluido_em`),
  KEY `idx_usuarios_escritorio` (`escritorio_id`),
  KEY `idx_usuarios_gestor` (`gestor_id`),
  KEY `idx_usuarios_cargo_ativo` (`cargo`,`ativo`),
  KEY `idx_usuarios_status_sgq` (`status_sgq`),
  KEY `idx_usuarios_versao_sessao` (`id`,`versao_sessao`,`ativo`),
  CONSTRAINT `fk_usuarios_escritorio` FOREIGN KEY (`escritorio_id`) REFERENCES `escritorios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_usuarios_gestor` FOREIGN KEY (`gestor_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES ('11111111-1111-1111-1111-111111111111','Carlos Mendes','excluido.11111111111111111111111111111111@local.invalid','$2y$10$SjdkE2qA2s5C1UHZo/V4yOaIYQ1RWLsybsGP7Vf1cLmGJYmeflMFi','VISTORIADOR',0,1,'2026-07-19 02:27:38','2026-06-24 17:33:03','2026-09-11 15:28:35',0,0,'342323aa-142c-447b-b392-7421e538f041',NULL,NULL,NULL,'2027-09-11',NULL,'2027-09-11',NULL,'QUALIFICADO',NULL),('1c015cb0-3187-4068-bc6d-06585521e165','anabe','excluido.1c015cb031874068bc6d06585521e165@local.invalid','$2y$10$YTFhG9EMyJrdZssxn5aXuelGURp2nULigmFHIKVGdqFiQxbzAXIBu','VENDEDOR',0,1,'2026-07-19 02:27:32','2026-06-27 03:51:48','2026-09-11 15:28:35',1,1,'342323aa-142c-447b-b392-7421e538f041',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'QUALIFICADO',NULL),('22222222-2222-2222-2222-222222222222','Ana Paula Silva','excluido.22222222222222222222222222222222@local.invalid','$2y$10$t5EgpXiQyTOM/NZjPcdREep5XsL.u.y8OztQGiCY1EF55VlLklvvO','VISTORIADOR',0,1,'2026-07-19 02:27:28','2026-06-24 17:33:03','2026-09-11 15:28:35',0,0,'342323aa-142c-447b-b392-7421e538f041',NULL,NULL,NULL,'2027-09-11',NULL,'2027-09-11',NULL,'QUALIFICADO',NULL),('33333333-3333-3333-3333-333333333333','Roberto Lima','excluido.33333333333333333333333333333333@local.invalid','$2y$10$lH9jpywZL4ueeCNV1kxUXe4Ayl51gRcqjTqNLiU0S5aW0DA4IqD1y','VISTORIADOR',0,1,'2026-07-19 02:27:48','2026-06-24 17:33:03','2026-09-11 15:28:35',0,0,'342323aa-142c-447b-b392-7421e538f041',NULL,NULL,NULL,'2027-09-11',NULL,'2027-09-11',NULL,'QUALIFICADO',NULL),('349036db-2b7d-4a98-8509-97bdd3e71fe6','Vendedor2','vendedor1@teste.com','$2y$10$ylVeOJcCuz/gro/9rDZFi.0n.skUylm4pM2LKoTWqYpRcQPrCT/la','VENDEDOR',1,5,NULL,'2026-07-23 05:05:06','2026-09-16 02:28:56',0,0,'342323aa-142c-447b-b392-7421e538f041',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'QUALIFICADO',NULL),('3774d80c-2574-470e-88a9-9781936c6de3','Any','excluido.3774d80c2574470e88a99781936c6de3@local.invalid','$2y$10$TzfH61SflMPiQpW4MFIP5OTf2/khZ51Q66XX1HiNl3SjgtruZj8au','VISTORIADOR',0,1,'2026-07-19 02:27:35','2026-06-23 22:51:43','2026-09-11 15:28:35',1,0,'342323aa-142c-447b-b392-7421e538f041',NULL,NULL,NULL,'2027-09-11',NULL,'2027-09-11',NULL,'QUALIFICADO',NULL),('74e02f95-fbe6-42f3-bedf-f8535e4d13aa','Rosano Souza','excluido.74e02f95fbe642f3bedff8535e4d13aa@local.invalid','$2y$10$pEGJqFBciTy5Zm4.xv1CTOi9eF29nXW4NWRaifY/h4f74SWAJd0EG','VISTORIADOR',0,1,'2026-07-19 02:27:52','2026-06-11 21:44:56','2026-09-11 15:28:35',0,0,'342323aa-142c-447b-b392-7421e538f041',NULL,NULL,NULL,'2027-09-11',NULL,'2027-09-11',NULL,'QUALIFICADO',NULL),('95eb5557-65e8-11f1-85ef-047c16b568a3','Administrador','excluido.95eb555765e811f185ef047c16b568a3@local.invalid','$2y$10$WDtKPgD44yf3STmx0SPfOuiy2AgKuWi5EEFozzSOfvZ3vLGGLW7Pq','ADMIN',0,1,'2026-07-19 02:28:49','2026-06-11 19:55:04','2026-09-11 15:28:35',0,0,'342323aa-142c-447b-b392-7421e538f041',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'QUALIFICADO',NULL),('9cd7e53a-da9d-4f2b-9b32-328be32da2f0','itamar','analista@teste.com','$2y$10$Wx89flrgig.DQhcX7i.yHeItCiTtHgYfJYHIoX/K0A4ospK6zkKsO','ANALISTA',1,6,NULL,'2026-07-16 15:38:12','2026-09-16 02:28:56',0,0,'342323aa-142c-447b-b392-7421e538f041',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'QUALIFICADO',NULL),('ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5','any','vendedor@teste.com','$2y$10$3j2BvFNQytOeftJk4nWwHORcMqt9Ru5Jahqn7KjoDPS7pI6w0re.u','VENDEDOR',1,5,NULL,'2026-07-21 12:47:52','2026-09-16 02:28:56',0,0,'342323aa-142c-447b-b392-7421e538f041',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'QUALIFICADO',NULL),('d2a16613-dfa4-4948-8de4-8c802abdf394','Neto','teste1@teste.com','$2y$10$c28aBKoHfboGVMXbz1/cwubQquu9E3ptBHdShsaW.RYOQUdr.GBb6','VISTORIADOR',1,5,NULL,'2026-07-07 21:10:28','2026-09-16 02:28:56',1,0,'342323aa-142c-447b-b392-7421e538f041',NULL,NULL,NULL,'2027-09-11',NULL,'2027-09-11',NULL,'QUALIFICADO',NULL),('dd121661-feb4-42f6-895a-68eb0608d1e4','admin','teste@teste.com','$2y$10$1fKvtUMEi.KpWMZefJP5Ve60By4/BSelmtiuwLg76tnFrL9mdZBsi','ADMIN',1,1,NULL,'2026-07-05 13:39:17','2026-09-16 00:37:14',0,0,'342323aa-142c-447b-b392-7421e538f041',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'QUALIFICADO',NULL),('e5c68a85-c920-4b11-bc93-9343d9d94f14','vistoriador teste','excluido.e5c68a85c9204b11bc939343d9d94f14@local.invalid','$2y$10$LdMu1ZxZP.ysBC10FSV/TeWm5yuEeZkyenLH5fxHKx4QA6MbAPGeW','VISTORIADOR',0,1,'2026-07-19 02:28:20','2026-07-02 15:06:59','2026-09-11 15:28:35',0,0,'342323aa-142c-447b-b392-7421e538f041',NULL,NULL,NULL,'2027-09-11',NULL,'2027-09-11',NULL,'QUALIFICADO',NULL);
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
INSERT INTO `vistoria_anexos` VALUES ('0356c3da-27c7-4371-b825-e246df2e99f1','c3670566-76b2-4376-88b4-234c09701f37',NULL,'http://localhost:8082/embarcacoes/foto?id=317ba743-7aa6-4d66-a845-2d4670f126f0&v=957cb7e02f9f','embarcacoes/317ba743-7aa6-4d66-a845-2d4670f126f0/foto-oficial/0356c3da-27c7-4371-b825-e246df2e99f1.jpg','FOTO_OFICIAL_foto_teste_barco.jpg','image/jpeg',1919,'957cb7e02f9f595f22b96db67935ea2aa60cf583b196a2d66ab417fd2e8f74cb','2026-09-15 22:33:53','11111111-1111-1111-1111-111111111111','2026-09-15 22:33:53',NULL,NULL),('10eb19f3-65fc-4206-ba62-cc9716a91d6b','c3670566-76b2-4376-88b4-234c09701f37',NULL,'http://localhost:8082/embarcacoes/foto?id=317ba743-7aa6-4d66-a845-2d4670f126f0&v=b1f130c385dd','embarcacoes/317ba743-7aa6-4d66-a845-2d4670f126f0/foto-oficial/10eb19f3-65fc-4206-ba62-cc9716a91d6b.jpg','FOTO_OFICIAL_foto_teste_barco.jpg','image/jpeg',1911,'b1f130c385dd26d67ff130f317ebb8d4b04b7e0e6b6e13450c47b15792bb854b','2026-09-15 22:13:27','11111111-1111-1111-1111-111111111111','2026-09-15 22:13:27',NULL,NULL),('1d7edebb-ffe1-472e-aa30-7de3fc3c8559','c3670566-76b2-4376-88b4-234c09701f37',NULL,'http://localhost:8082/embarcacoes/foto?id=317ba743-7aa6-4d66-a845-2d4670f126f0&v=00a727f3d50f','embarcacoes/317ba743-7aa6-4d66-a845-2d4670f126f0/foto-oficial/1d7edebb-ffe1-472e-aa30-7de3fc3c8559.jpg','FOTO_OFICIAL_foto_teste_barco.jpg','image/jpeg',1915,'00a727f3d50f2e24d73cb7f01a085ae313e12c7099556db0944b85c3868b99b9','2026-09-15 22:06:09','11111111-1111-1111-1111-111111111111','2026-09-15 22:06:09',NULL,NULL),('43afb8b8-53d8-45de-95df-ff8bce59dc3e','c3670566-76b2-4376-88b4-234c09701f37',NULL,'http://localhost:8082/embarcacoes/foto?id=c72ee837-94d8-4fe7-8ac9-63edc8ae4c73&v=618f8a707408','embarcacoes/c72ee837-94d8-4fe7-8ac9-63edc8ae4c73/foto-oficial/43afb8b8-53d8-45de-95df-ff8bce59dc3e.jpg','FOTO_OFICIAL_foto_teste_barco.jpg','image/jpeg',1921,'618f8a707408ccc93cd004f1c83f3559eedf451b6fc81bfa3d41e9ad57e3df1f','2026-09-16 03:07:20','11111111-1111-1111-1111-111111111111','2026-09-16 03:07:20',NULL,NULL),('5c8286ed-6d58-4bf6-aa76-c33c5e915156','c3670566-76b2-4376-88b4-234c09701f37',NULL,'http://localhost:8082/embarcacoes/foto?id=317ba743-7aa6-4d66-a845-2d4670f126f0&v=025b37c2feea','embarcacoes/317ba743-7aa6-4d66-a845-2d4670f126f0/foto-oficial/5c8286ed-6d58-4bf6-aa76-c33c5e915156.jpg','FOTO_OFICIAL_foto_teste_barco.jpg','image/jpeg',1910,'025b37c2feeae88f0203084867d825b8931a0a596402d4183e5c81e026fefd04','2026-09-15 20:14:25','11111111-1111-1111-1111-111111111111','2026-09-15 20:14:25',NULL,NULL),('66665388-5821-491c-8f73-82980ca59a1c','c3670566-76b2-4376-88b4-234c09701f37',NULL,'http://localhost:8082/embarcacoes/foto?id=317ba743-7aa6-4d66-a845-2d4670f126f0&v=05f1e3a2e62a','embarcacoes/317ba743-7aa6-4d66-a845-2d4670f126f0/foto-oficial/66665388-5821-491c-8f73-82980ca59a1c.jpg','FOTO_OFICIAL_foto_teste_barco.jpg','image/jpeg',1922,'05f1e3a2e62aa6ef7c1fa9bbbbcfe8bbbc20ee1d04a64775eea1eb1c57e13656','2026-09-16 00:18:47','11111111-1111-1111-1111-111111111111','2026-09-16 00:18:47',NULL,NULL),('75db623f-f7cf-4eab-8331-53fe2f3a8bf3','c3670566-76b2-4376-88b4-234c09701f37',NULL,'http://localhost:8082/embarcacoes/foto?id=317ba743-7aa6-4d66-a845-2d4670f126f0&v=feea9ebf137f','embarcacoes/317ba743-7aa6-4d66-a845-2d4670f126f0/foto-oficial/75db623f-f7cf-4eab-8331-53fe2f3a8bf3.jpg','FOTO_OFICIAL_foto_teste_barco.jpg','image/jpeg',1920,'feea9ebf137fcc45422e5b6ef106df755cb7068b54e3adacc57782231761b155','2026-09-16 00:32:32','11111111-1111-1111-1111-111111111111','2026-09-16 00:32:32',NULL,NULL),('7768f067-542d-4454-bbf5-55366d5b53ac','c3670566-76b2-4376-88b4-234c09701f37',NULL,'http://localhost:8082/embarcacoes/foto?id=317ba743-7aa6-4d66-a845-2d4670f126f0&v=fa2bdab11850','embarcacoes/317ba743-7aa6-4d66-a845-2d4670f126f0/foto-oficial/7768f067-542d-4454-bbf5-55366d5b53ac.jpg','FOTO_OFICIAL_foto_teste_barco.jpg','image/jpeg',1931,'fa2bdab1185063a2eca1423ae1cca9ac6502baa3492ec65c214f62c204b13c23','2026-09-15 22:46:38','11111111-1111-1111-1111-111111111111','2026-09-15 22:46:38',NULL,NULL),('79eeac36-3ae2-478c-9473-d67bb1a93021','c3670566-76b2-4376-88b4-234c09701f37',NULL,'http://localhost:8082/embarcacoes/foto?id=317ba743-7aa6-4d66-a845-2d4670f126f0&v=5ec7c2bc5067','embarcacoes/317ba743-7aa6-4d66-a845-2d4670f126f0/foto-oficial/79eeac36-3ae2-478c-9473-d67bb1a93021.jpg','FOTO_OFICIAL_foto_teste_barco.jpg','image/jpeg',1929,'5ec7c2bc5067fb9ae1be7b7635137ee3f2745e2dd1a837dec103e0825f041bf7','2026-09-15 18:03:16','11111111-1111-1111-1111-111111111111','2026-09-15 18:03:16',NULL,NULL),('7a6f9f5d-1f62-407c-bb52-1f90d22b050e','c3670566-76b2-4376-88b4-234c09701f37',NULL,'http://localhost:8082/embarcacoes/foto?id=317ba743-7aa6-4d66-a845-2d4670f126f0&v=51d2d9c12470','embarcacoes/317ba743-7aa6-4d66-a845-2d4670f126f0/foto-oficial/7a6f9f5d-1f62-407c-bb52-1f90d22b050e.jpg','FOTO_OFICIAL_foto_teste_barco.jpg','image/jpeg',1914,'51d2d9c124705a48568dbcfd08fd3489fb12707ffa5b0577aa6b9452d1e114bd','2026-09-13 07:59:30','11111111-1111-1111-1111-111111111111','2026-09-13 07:59:30',NULL,NULL),('7d5bdc3c-ec25-49e6-ab8b-49b647af1892','c3670566-76b2-4376-88b4-234c09701f37',NULL,'http://localhost:8082/embarcacoes/foto?id=c72ee837-94d8-4fe7-8ac9-63edc8ae4c73&v=a1f5ad58516e','embarcacoes/c72ee837-94d8-4fe7-8ac9-63edc8ae4c73/foto-oficial/7d5bdc3c-ec25-49e6-ab8b-49b647af1892.jpg','FOTO_OFICIAL_foto_teste_barco.jpg','image/jpeg',1924,'a1f5ad58516e8cecf459097fdef526d6b5d37cc587b5e5157fd9a0a066b57702','2026-09-16 03:24:47','11111111-1111-1111-1111-111111111111','2026-09-16 03:24:47',NULL,NULL),('817f6d2e-3bef-4e02-88e4-d1425f7b8d77','c3670566-76b2-4376-88b4-234c09701f37',NULL,'http://localhost:8082/embarcacoes/foto?id=317ba743-7aa6-4d66-a845-2d4670f126f0&v=020f1bf038bf','embarcacoes/317ba743-7aa6-4d66-a845-2d4670f126f0/foto-oficial/817f6d2e-3bef-4e02-88e4-d1425f7b8d77.jpg','FOTO_OFICIAL_foto_teste_barco.jpg','image/jpeg',1923,'020f1bf038bfac6dca732f70607147c5937d52c7f61d3392c3e4b281722c5545','2026-09-15 23:06:48','11111111-1111-1111-1111-111111111111','2026-09-15 23:06:48',NULL,NULL),('82f6c6dd-bd01-447d-8b88-24b284e0ab4d','c3670566-76b2-4376-88b4-234c09701f37',NULL,'http://localhost:8082/embarcacoes/foto?id=317ba743-7aa6-4d66-a845-2d4670f126f0&v=2014991382f2','embarcacoes/317ba743-7aa6-4d66-a845-2d4670f126f0/foto-oficial/82f6c6dd-bd01-447d-8b88-24b284e0ab4d.jpg','FOTO_OFICIAL_foto_teste_barco.jpg','image/jpeg',1918,'2014991382f2095bd334fdf01473a90ad986a4eb43475f215a20fb0566c922f0','2026-09-16 00:23:40','11111111-1111-1111-1111-111111111111','2026-09-16 00:23:40',NULL,NULL),('8877f199-f756-45a2-8fe1-ac21642fb49c','c3670566-76b2-4376-88b4-234c09701f37',NULL,'http://localhost:8082/embarcacoes/foto?id=c72ee837-94d8-4fe7-8ac9-63edc8ae4c73&v=328756c0f50b','embarcacoes/c72ee837-94d8-4fe7-8ac9-63edc8ae4c73/foto-oficial/8877f199-f756-45a2-8fe1-ac21642fb49c.jpg','FOTO_OFICIAL_foto_teste_barco.jpg','image/jpeg',1918,'328756c0f50b9a9bef1094ad0f627e09391b2e9d18091b91313ba86efef873dd','2026-09-16 00:47:45','11111111-1111-1111-1111-111111111111','2026-09-16 00:47:45',NULL,NULL),('90086ec0-f0e0-4fc8-8f2a-db4aa11bc988','c3670566-76b2-4376-88b4-234c09701f37',NULL,'http://localhost:8082/embarcacoes/foto?id=317ba743-7aa6-4d66-a845-2d4670f126f0&v=c335af76250f','embarcacoes/317ba743-7aa6-4d66-a845-2d4670f126f0/foto-oficial/90086ec0-f0e0-4fc8-8f2a-db4aa11bc988.jpg','FOTO_OFICIAL_foto_teste_barco.jpg','image/jpeg',1926,'c335af76250f29b6ce6d5ec7409b6ed71612aedad547421f439b194ea11c1bfa','2026-09-16 00:35:42','11111111-1111-1111-1111-111111111111','2026-09-16 00:35:42',NULL,NULL),('947f54ce-f4da-46e1-96de-253891cc6732','c3670566-76b2-4376-88b4-234c09701f37',NULL,'http://localhost:8082/embarcacoes/foto?id=317ba743-7aa6-4d66-a845-2d4670f126f0&v=92a394d26db6','embarcacoes/317ba743-7aa6-4d66-a845-2d4670f126f0/foto-oficial/947f54ce-f4da-46e1-96de-253891cc6732.jpg','FOTO_OFICIAL_foto_teste_barco.jpg','image/jpeg',1924,'92a394d26db69b9f4fc939b4bb51a0468336cfa5e1618fb16630f60f0d89bad0','2026-09-16 00:34:38','11111111-1111-1111-1111-111111111111','2026-09-16 00:34:38',NULL,NULL),('9a3b3d02-2f71-4e38-8694-99d80303ebfa','c3670566-76b2-4376-88b4-234c09701f37',NULL,'http://localhost:8082/embarcacoes/foto?id=317ba743-7aa6-4d66-a845-2d4670f126f0&v=b473a8ed3cf4','embarcacoes/317ba743-7aa6-4d66-a845-2d4670f126f0/foto-oficial/9a3b3d02-2f71-4e38-8694-99d80303ebfa.jpg','FOTO_OFICIAL_foto_teste_barco.jpg','image/jpeg',1917,'b473a8ed3cf4071067e44d5564eeff45565a27a2e5154755fca39b70bad738d6','2026-09-15 20:20:28','11111111-1111-1111-1111-111111111111','2026-09-15 20:20:28',NULL,NULL),('b069b52f-46b2-4afa-9e10-a96ef36b9124','c3670566-76b2-4376-88b4-234c09701f37',NULL,'http://localhost:8082/embarcacoes/foto?id=317ba743-7aa6-4d66-a845-2d4670f126f0&v=e98a3d43b98d','embarcacoes/317ba743-7aa6-4d66-a845-2d4670f126f0/foto-oficial/b069b52f-46b2-4afa-9e10-a96ef36b9124.jpg','FOTO_OFICIAL_foto_teste_barco.jpg','image/jpeg',1924,'e98a3d43b98d76e5c5589f4d521a16fca5752d4aec31c7cca866c873e3854003','2026-09-15 19:47:37','11111111-1111-1111-1111-111111111111','2026-09-15 19:47:37',NULL,NULL),('b25d8a8d-71cb-47bb-8c5d-c0abcfb557b9','c3670566-76b2-4376-88b4-234c09701f37',NULL,'http://localhost:8082/embarcacoes/foto?id=c72ee837-94d8-4fe7-8ac9-63edc8ae4c73&v=8c0fe9969411','embarcacoes/c72ee837-94d8-4fe7-8ac9-63edc8ae4c73/foto-oficial/b25d8a8d-71cb-47bb-8c5d-c0abcfb557b9.jpg','FOTO_OFICIAL_foto_teste_barco.jpg','image/jpeg',1919,'8c0fe996941141c63e0525625b70d2a515bceddd9f43cbe8bc6020690dd1c3ca','2026-09-16 03:05:36','11111111-1111-1111-1111-111111111111','2026-09-16 03:05:36',NULL,NULL),('c5aa3aaa-88cd-4f13-9f11-40beea08ba63','c3670566-76b2-4376-88b4-234c09701f37',NULL,'http://localhost:8082/embarcacoes/foto?id=317ba743-7aa6-4d66-a845-2d4670f126f0&v=88d8b62472e7','embarcacoes/317ba743-7aa6-4d66-a845-2d4670f126f0/foto-oficial/c5aa3aaa-88cd-4f13-9f11-40beea08ba63.jpg','FOTO_OFICIAL_foto_teste_barco.jpg','image/jpeg',1922,'88d8b62472e7848f273bb9ddd55654b955e3008df3d86493796dbe025cb27e97','2026-09-16 00:36:18','11111111-1111-1111-1111-111111111111','2026-09-16 00:36:18',NULL,NULL),('d0287aba-38de-458a-bdc0-a5af730c50c9','c3670566-76b2-4376-88b4-234c09701f37',NULL,'http://localhost:8082/embarcacoes/foto?id=317ba743-7aa6-4d66-a845-2d4670f126f0&v=5af4ab5a4bb9','embarcacoes/317ba743-7aa6-4d66-a845-2d4670f126f0/foto-oficial/d0287aba-38de-458a-bdc0-a5af730c50c9.jpg','FOTO_OFICIAL_foto_teste_barco.jpg','image/jpeg',830,'5af4ab5a4bb9f7281255bd98db4485724e760d1238b2f4380a511ec0d6b5c776','2026-09-13 07:56:57','11111111-1111-1111-1111-111111111111','2026-09-13 07:56:57',NULL,NULL),('d051c638-0597-4004-b5ac-5ee90dfcd18d','c3670566-76b2-4376-88b4-234c09701f37',NULL,'http://localhost:8082/embarcacoes/foto?id=317ba743-7aa6-4d66-a845-2d4670f126f0&v=60e0daa29388','embarcacoes/317ba743-7aa6-4d66-a845-2d4670f126f0/foto-oficial/d051c638-0597-4004-b5ac-5ee90dfcd18d.jpg','FOTO_OFICIAL_foto_teste_barco.jpg','image/jpeg',1916,'60e0daa29388c8ec9737585a0c6b96677bc9ccd94a121a72946071952b7ee2ed','2026-09-15 21:49:07','11111111-1111-1111-1111-111111111111','2026-09-15 21:49:07',NULL,NULL),('d0af622b-478f-407a-8ac9-8b99b634a3e8','c3670566-76b2-4376-88b4-234c09701f37',NULL,'http://localhost:8082/embarcacoes/foto?id=c72ee837-94d8-4fe7-8ac9-63edc8ae4c73&v=02b26711aa2f','embarcacoes/c72ee837-94d8-4fe7-8ac9-63edc8ae4c73/foto-oficial/d0af622b-478f-407a-8ac9-8b99b634a3e8.jpg','FOTO_OFICIAL_foto_teste_barco.jpg','image/jpeg',1917,'02b26711aa2f6800226d8caf6a0d320ee5de7379cf118d509e6e94cd2600178c','2026-09-16 04:02:24','11111111-1111-1111-1111-111111111111','2026-09-16 04:02:24',NULL,NULL),('d1a3cf43-12b3-4f9c-8120-ec0e6d7c6e2e','c3670566-76b2-4376-88b4-234c09701f37',NULL,'http://localhost:8082/embarcacoes/foto?id=c72ee837-94d8-4fe7-8ac9-63edc8ae4c73&v=324ee24cfa4f','embarcacoes/c72ee837-94d8-4fe7-8ac9-63edc8ae4c73/foto-oficial/d1a3cf43-12b3-4f9c-8120-ec0e6d7c6e2e.jpg','FOTO_OFICIAL_foto_teste_barco.jpg','image/jpeg',1919,'324ee24cfa4f8f7e84ac84c0534802731d05c69265f4c70537a2ddd2785a4c79','2026-09-16 02:14:43','11111111-1111-1111-1111-111111111111','2026-09-16 02:14:43',NULL,NULL),('d35755ea-b5a3-4fd8-a4ae-fdd8168a7c8d','c3670566-76b2-4376-88b4-234c09701f37',NULL,'http://localhost:8082/embarcacoes/foto?id=317ba743-7aa6-4d66-a845-2d4670f126f0&v=e946e4ea72fb','embarcacoes/317ba743-7aa6-4d66-a845-2d4670f126f0/foto-oficial/d35755ea-b5a3-4fd8-a4ae-fdd8168a7c8d.jpg','FOTO_OFICIAL_foto_teste_barco.jpg','image/jpeg',1928,'e946e4ea72fb0adbc205d5e79d408ececcc9e5509f94f9dc700694de2ee81e9b','2026-09-16 00:33:27','11111111-1111-1111-1111-111111111111','2026-09-16 00:33:27',NULL,NULL),('d7a711ec-7d44-475b-8d22-7944c3cc17e9','c3670566-76b2-4376-88b4-234c09701f37',NULL,'http://localhost:8082/embarcacoes/foto?id=317ba743-7aa6-4d66-a845-2d4670f126f0&v=6b50c196f8ab','embarcacoes/317ba743-7aa6-4d66-a845-2d4670f126f0/foto-oficial/d7a711ec-7d44-475b-8d22-7944c3cc17e9.jpg','FOTO_OFICIAL_foto_teste_barco.jpg','image/jpeg',1912,'6b50c196f8abf21ac67cd6b225ac5ed5cee12df25dab741d8229efc964a27767','2026-09-15 19:48:32','11111111-1111-1111-1111-111111111111','2026-09-15 19:48:32',NULL,NULL),('dc1535f6-8eb9-46c9-bb1b-4140f6f15b31','c3670566-76b2-4376-88b4-234c09701f37',NULL,'http://localhost:8082/embarcacoes/foto?id=317ba743-7aa6-4d66-a845-2d4670f126f0&v=31d4263b95b3','embarcacoes/317ba743-7aa6-4d66-a845-2d4670f126f0/foto-oficial/dc1535f6-8eb9-46c9-bb1b-4140f6f15b31.jpg','FOTO_OFICIAL_foto_teste_barco.jpg','image/jpeg',1924,'31d4263b95b3561f0c81b80422edaf3842177e432af0de8cdd98802cc5e6d261','2026-09-15 18:52:01','11111111-1111-1111-1111-111111111111','2026-09-15 18:52:01',NULL,NULL),('e2b48748-08b6-4199-87c8-0881705adb70','c3670566-76b2-4376-88b4-234c09701f37',NULL,'http://localhost:8082/embarcacoes/foto?id=317ba743-7aa6-4d66-a845-2d4670f126f0&v=a3ac5484ef42','embarcacoes/317ba743-7aa6-4d66-a845-2d4670f126f0/foto-oficial/e2b48748-08b6-4199-87c8-0881705adb70.jpg','FOTO_OFICIAL_foto_teste_barco.jpg','image/jpeg',1905,'a3ac5484ef428c630c885502e650b918d8ce4c25c98ac055b432cb1923ac9d57','2026-09-15 18:14:42','11111111-1111-1111-1111-111111111111','2026-09-15 18:14:42',NULL,NULL),('e4224e0f-f0d8-4789-ace7-c57da3643098','c3670566-76b2-4376-88b4-234c09701f37',NULL,'http://localhost:8082/embarcacoes/foto?id=c72ee837-94d8-4fe7-8ac9-63edc8ae4c73&v=137089d4eb95','embarcacoes/c72ee837-94d8-4fe7-8ac9-63edc8ae4c73/foto-oficial/e4224e0f-f0d8-4789-ace7-c57da3643098.jpg','FOTO_OFICIAL_foto_teste_barco.jpg','image/jpeg',1921,'137089d4eb95a82f4ef319e72361297c626c6107ff8c118ee40bafaa922055a8','2026-09-16 03:22:51','11111111-1111-1111-1111-111111111111','2026-09-16 03:22:51',NULL,NULL),('e5dd55b5-b4c2-4c43-bb7d-bd6a9e38fdc5','c3670566-76b2-4376-88b4-234c09701f37',NULL,'http://localhost:8082/embarcacoes/foto?id=317ba743-7aa6-4d66-a845-2d4670f126f0&v=ddaeff192e5a','embarcacoes/317ba743-7aa6-4d66-a845-2d4670f126f0/foto-oficial/e5dd55b5-b4c2-4c43-bb7d-bd6a9e38fdc5.jpg','FOTO_OFICIAL_foto_teste_barco.jpg','image/jpeg',1922,'ddaeff192e5a8f29b86723753ad1bdf9ec5b533a8ff28f6983e0d5d79b73f260','2026-09-15 23:07:51','11111111-1111-1111-1111-111111111111','2026-09-15 23:07:51',NULL,NULL),('fe6e7596-4132-491d-80a6-cec02cdc092a','c3670566-76b2-4376-88b4-234c09701f37',NULL,'http://localhost:8082/embarcacoes/foto?id=317ba743-7aa6-4d66-a845-2d4670f126f0&v=8cffa05e81e0','embarcacoes/317ba743-7aa6-4d66-a845-2d4670f126f0/foto-oficial/fe6e7596-4132-491d-80a6-cec02cdc092a.jpg','FOTO_OFICIAL_foto_teste_barco.jpg','image/jpeg',1921,'8cffa05e81e0ddf19d6674692972fe6b5c8985522970f7a8e3fdd92407916a66','2026-09-15 19:14:49','11111111-1111-1111-1111-111111111111','2026-09-15 19:14:49',NULL,NULL),('feb97cdb-faf4-4553-85eb-44a8e23db24f','c3670566-76b2-4376-88b4-234c09701f37',NULL,'http://localhost:8082/embarcacoes/foto?id=c72ee837-94d8-4fe7-8ac9-63edc8ae4c73&v=b7879d425c9f','embarcacoes/c72ee837-94d8-4fe7-8ac9-63edc8ae4c73/foto-oficial/feb97cdb-faf4-4553-85eb-44a8e23db24f.jpg','FOTO_OFICIAL_foto_teste_barco.jpg','image/jpeg',1919,'b7879d425c9f369c4b5e09f30d06b6113158127f69a4f3e65e1080ed3e865985','2026-09-16 01:04:10','11111111-1111-1111-1111-111111111111','2026-09-16 01:04:10',NULL,NULL);
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
INSERT INTO `vistoria_checklist_respostas` VALUES ('54a1d930-ad55-11f1-8a7c-be2fb1f77be2','c3670566-76b2-4376-88b4-234c09701f37','191031d1-a918-4879-9118-a6bce6f4b56b','NAO_CONFORME',NULL,'NORMAM-202/DPC','2026-12-14',0,'2026-09-10 20:22:22','2026-09-10 20:22:22'),('54a23968-ad55-11f1-8a7c-be2fb1f77be2','c3670566-76b2-4376-88b4-234c09701f37','58133b9a-53e9-454e-bdb7-e5e2b7a1d90c','NAO_CONFORME',NULL,'NORMAM-202/DPC, Cap. 04, Item 4.2), 4.2.1, m, I.','2026-12-14',0,'2026-09-10 20:22:22','2026-09-10 20:22:22'),('54a260a2-ad55-11f1-8a7c-be2fb1f77be2','c3670566-76b2-4376-88b4-234c09701f37','dbc42c9d-c0f2-44bc-ad57-b78a7b4e0ab3','NAO_CONFORME',NULL,'NORMAM-202/DPC, Cap. 04, Seção I.',NULL,1,'2026-09-10 20:22:22','2026-09-10 20:22:22');
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
  `numero_origem` smallint unsigned DEFAULT NULL,
  `numero_sequencial` smallint unsigned DEFAULT NULL,
  `item` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `descricao` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `descricao_reescrita` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
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
  KEY `idx_vistoria_exigencias_sequencial` (`vistoria_id`,`numero_sequencial`),
  KEY `idx_exigencias_status_vencimento` (`status_item`,`vencimento`),
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
INSERT INTO `vistoria_exigencias` VALUES ('54a20289-ad55-11f1-8a7c-be2fb1f77be2','c3670566-76b2-4376-88b4-234c09701f37','191031d1-a918-4879-9118-a6bce6f4b56b','flutuando',1,NULL,NULL,'Item Normam: NORMAM-202/DPC','Não são utilizados combustíveis com ponto de fulgor inferior a 60 °C (como álcool ou gasolina)',NULL,'nao',NULL,'NORMAM-202/DPC','2026-12-14',0,'pendente',NULL),('54a254bd-ad55-11f1-8a7c-be2fb1f77be2','c3670566-76b2-4376-88b4-234c09701f37','58133b9a-53e9-454e-bdb7-e5e2b7a1d90c','flutuando',2,NULL,NULL,'Item Normam: NORMAM-202/DPC, Cap. 04, Item 4.2), 4.2.1, m, I.','A quantidade, capacidade, localização e tipo dos extintores de incêndio estão de acordo com a tabela da NORMAM. Quanto à localização deles, seguem o determinado no Plano de Segurança (se existente)',NULL,'nao',NULL,'NORMAM-202/DPC, Cap. 04, Item 4.2), 4.2.1, m, I.','2026-12-14',0,'pendente',NULL),('54a275a0-ad55-11f1-8a7c-be2fb1f77be2','c3670566-76b2-4376-88b4-234c09701f37','dbc42c9d-c0f2-44bc-ad57-b78a7b4e0ab3','flutuando',3,NULL,NULL,'Item Normam: NORMAM-202/DPC, Cap. 04, Seção I.','Nas DEMAIS embarcações, próximas à entrada da praça de máquinas (lado externo), deverão ser previstas uma tomada de incêndio e uma estação de incêndio com uma ou mais seções de mangueira e um aplicador de neblina',NULL,'nao',NULL,'NORMAM-202/DPC, Cap. 04, Seção I.',NULL,1,'pendente',NULL),('6d011d39-ad55-11f1-8a7c-be2fb1f77be2','c0cc81fc-b11d-40a6-b8f8-f410d3c1982a','191031d1-a918-4879-9118-a6bce6f4b56b','flutuando',1,1,1,'Item Normam: NORMAM-202/DPC','Não são utilizados combustíveis com ponto de fulgor inferior a 60 °C (como álcool ou gasolina)','yrteyr','nao',NULL,'NORMAM-202/DPC','2026-12-14',0,'cumprida_parcial_reescrita','54a20289-ad55-11f1-8a7c-be2fb1f77be2'),('6d012063-ad55-11f1-8a7c-be2fb1f77be2','c0cc81fc-b11d-40a6-b8f8-f410d3c1982a','58133b9a-53e9-454e-bdb7-e5e2b7a1d90c','flutuando',2,2,NULL,'Item Normam: NORMAM-202/DPC, Cap. 04, Item 4.2), 4.2.1, m, I.','A quantidade, capacidade, localização e tipo dos extintores de incêndio estão de acordo com a tabela da NORMAM. Quanto à localização deles, seguem o determinado no Plano de Segurança (se existente)',NULL,'sim',NULL,'NORMAM-202/DPC, Cap. 04, Item 4.2), 4.2.1, m, I.','2026-12-14',0,'cumprida','54a254bd-ad55-11f1-8a7c-be2fb1f77be2'),('6d0120c3-ad55-11f1-8a7c-be2fb1f77be2','c0cc81fc-b11d-40a6-b8f8-f410d3c1982a','dbc42c9d-c0f2-44bc-ad57-b78a7b4e0ab3','flutuando',3,3,NULL,'Item Normam: NORMAM-202/DPC, Cap. 04, Seção I.','Nas DEMAIS embarcações, próximas à entrada da praça de máquinas (lado externo), deverão ser previstas uma tomada de incêndio e uma estação de incêndio com uma ou mais seções de mangueira e um aplicador de neblina',NULL,'sim',NULL,'NORMAM-202/DPC, Cap. 04, Seção I.',NULL,1,'cumprida','54a275a0-ad55-11f1-8a7c-be2fb1f77be2');
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
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `relatorio_origem_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `agendamento_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `relatorio_resultado_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tipo` enum('AS','EXIGENCIAS') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'AS',
  `status` enum('PENDENTE_AGENDAMENTO','AGENDADO','RELATORIO_ENVIADO','CONCLUIDO','CANCELADO') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'PENDENTE_AGENDAMENTO',
  `motivo_cancelamento` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `criado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `vistoriador_origem_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `vistoriador_retorno_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `motivo_reatribuicao` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `reatribuido_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reatribuido_em` datetime DEFAULT NULL,
  `cancelado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
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
  KEY `idx_vistoria_retorno_vistoriador_origem` (`vistoriador_origem_id`),
  KEY `idx_vistoria_retorno_vistoriador_retorno` (`vistoriador_retorno_id`),
  KEY `idx_vistoria_retorno_reatribuido_por` (`reatribuido_por`),
  KEY `idx_vistoria_retornos_tipo_status` (`tipo`,`status`),
  CONSTRAINT `fk_vistoria_retorno_agendamento` FOREIGN KEY (`agendamento_id`) REFERENCES `agendamentos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_vistoria_retorno_cancelador` FOREIGN KEY (`cancelado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_vistoria_retorno_criador` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_vistoria_retorno_origem` FOREIGN KEY (`relatorio_origem_id`) REFERENCES `vistorias` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_vistoria_retorno_reatribuido_por` FOREIGN KEY (`reatribuido_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_vistoria_retorno_resultado` FOREIGN KEY (`relatorio_resultado_id`) REFERENCES `vistorias` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_vistoria_retorno_vistoriador_origem` FOREIGN KEY (`vistoriador_origem_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_vistoria_retorno_vistoriador_retorno` FOREIGN KEY (`vistoriador_retorno_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vistoria_retornos`
--

LOCK TABLES `vistoria_retornos` WRITE;
/*!40000 ALTER TABLE `vistoria_retornos` DISABLE KEYS */;
INSERT INTO `vistoria_retornos` VALUES ('20088f20-905e-4ba4-8d19-d93364d6ce5e','c3670566-76b2-4376-88b4-234c09701f37','e1b20145-ebe6-4640-9f1f-f491d004e485','c0cc81fc-b11d-40a6-b8f8-f410d3c1982a','AS','CONCLUIDO',NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','d2a16613-dfa4-4948-8de4-8c802abdf394','d2a16613-dfa4-4948-8de4-8c802abdf394',NULL,NULL,NULL,NULL,'2026-09-10 20:22:36','2026-09-10 20:24:27',NULL),('413a64a6-fa81-49e4-8f52-8ab56cf17740','c0cc81fc-b11d-40a6-b8f8-f410d3c1982a',NULL,NULL,'EXIGENCIAS','PENDENTE_AGENDAMENTO',NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4','d2a16613-dfa4-4948-8de4-8c802abdf394',NULL,NULL,NULL,NULL,NULL,'2026-09-10 20:23:34',NULL,NULL);
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
  `status` enum('PENDENTE','AGUARDANDO_APROVACAO','APROVADA','APROVADA_COM_EXIGENCIAS','RETORNO_AS','REPROVADA','CANCELADA') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'PENDENTE',
  `mobile_versao` int unsigned NOT NULL DEFAULT '0',
  `mobile_finalizada_em` datetime DEFAULT NULL,
  `aprovado_por` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `responsavel_assinatura_id` int DEFAULT NULL,
  `assinatura_status` enum('PENDENTE','ASSINADO','CANCELADO') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'PENDENTE',
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
  `relatorio_anterior_ativo_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci GENERATED ALWAYS AS (if((`status` = _utf8mb4'CANCELADA'),NULL,`relatorio_anterior_id`)) VIRTUAL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero` (`numero`),
  UNIQUE KEY `uk_vistorias_agendamento_unico` (`agendamento_id`),
  UNIQUE KEY `uk_vistorias_filho_ativo` (`relatorio_anterior_ativo_id`),
  KEY `embarcacao_id` (`embarcacao_id`),
  KEY `pessoa_id` (`pessoa_id`),
  KEY `criado_por` (`criado_por`),
  KEY `agendamento_id` (`agendamento_id`),
  KEY `vistorias_ibfk_aprovado_por` (`aprovado_por`),
  KEY `fk_vistoria_anterior` (`relatorio_anterior_id`),
  KEY `fk_vistorias_armador` (`armador_id`),
  KEY `idx_vistorias_agendamento_vigente` (`agendamento_id`,`criado_em`,`id`),
  KEY `idx_vistorias_status_data` (`status`,`data_vistoria`),
  KEY `idx_vistorias_status_aprovacao` (`status`,`data_aprovacao`),
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
INSERT INTO `vistorias` (`id`, `numero`, `embarcacao_id`, `pessoa_id`, `armador_id`, `operador_nome`, `agendamento_id`, `relatorio_anterior_id`, `finalidade`, `data_vistoria`, `prazo_exigencias_dias`, `data_emissao`, `status`, `mobile_versao`, `mobile_finalizada_em`, `aprovado_por`, `responsavel_assinatura_id`, `assinatura_status`, `assinatura_em`, `data_aprovacao`, `observacao_admin`, `observacoes`, `resultado`, `observacoes_tecnicas`, `texto_observacoes_geradas`, `criado_por`, `criado_em`, `atualizado_em`) VALUES ('b43ae751-f1dc-4872-a965-f00bab12491d','AM-REL-V-3/26','317ba743-7aa6-4d66-a845-2d4670f126f0','1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed',NULL,NULL,'04f4aedd-aec9-11f1-8a7c-be2fb1f77be2',NULL,'VISTORIA','2026-10-12',90,NULL,'PENDENTE',0,NULL,NULL,NULL,'PENDENTE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'d2a16613-dfa4-4948-8de4-8c802abdf394','2026-09-12 16:43:48','2026-09-12 16:43:48'),('c0cc81fc-b11d-40a6-b8f8-f410d3c1982a','AM-REL-V-2/26','317ba743-7aa6-4d66-a845-2d4670f126f0','1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed',NULL,NULL,'e1b20145-ebe6-4640-9f1f-f491d004e485','c3670566-76b2-4376-88b4-234c09701f37','CUMPRIMENTO_EXIGENCIAS','2026-09-24',90,NULL,'APROVADA_COM_EXIGENCIAS',0,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4',7,'ASSINADO','2026-09-10 17:24:25','2026-09-10 20:23:34',NULL,NULL,NULL,NULL,NULL,'d2a16613-dfa4-4948-8de4-8c802abdf394','2026-09-10 20:23:03','2026-09-10 20:24:27'),('c3670566-76b2-4376-88b4-234c09701f37','AM-REL-V-1/26','317ba743-7aa6-4d66-a845-2d4670f126f0','1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed',NULL,NULL,'25be9af2-ad55-11f1-8a7c-be2fb1f77be2',NULL,'VISTORIA','2026-09-15',90,NULL,'RETORNO_AS',0,NULL,'dd121661-feb4-42f6-895a-68eb0608d1e4',NULL,'PENDENTE',NULL,'2026-09-10 20:22:36',NULL,NULL,NULL,NULL,NULL,'d2a16613-dfa4-4948-8de4-8c802abdf394','2026-09-10 20:22:22','2026-09-10 20:22:36');
/*!40000 ALTER TABLE `vistorias` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-16  4:03:19
