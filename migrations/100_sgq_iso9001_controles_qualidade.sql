-- Migration 100: Controles de Qualidade ISO 9001:2015 e Requisitos NORMAM (ERP Amazon Naval)

-- 1. Extensao de Competencias e Credenciais da Equipe Tecnica (ISO 7.2) e Documentos (ISO 8.5.2)
DELIMITER $$
DROP PROCEDURE IF EXISTS sp_migration_100_sgq $$
CREATE PROCEDURE sp_migration_100_sgq()
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='usuarios' AND column_name='status_sgq') THEN
        ALTER TABLE usuarios
            ADD COLUMN registro_conselho_tipo ENUM('CREA','CFT','OUTRO') NULL DEFAULT NULL,
            ADD COLUMN registro_conselho_numero VARCHAR(50) NULL DEFAULT NULL,
            ADD COLUMN registro_conselho_validade DATE NULL DEFAULT NULL,
            ADD COLUMN credencial_marinha_numero VARCHAR(50) NULL DEFAULT NULL,
            ADD COLUMN credencial_marinha_validade DATE NULL DEFAULT NULL,
            ADD COLUMN escopo_habilitacao TEXT NULL DEFAULT NULL,
            ADD COLUMN status_sgq ENUM('QUALIFICADO','SUSPENSO_RECICLAGEM','DESQUALIFICADO','INATIVO') NOT NULL DEFAULT 'QUALIFICADO',
            ADD COLUMN data_ultima_avaliacao_competencia DATE NULL DEFAULT NULL;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='documento_artefatos' AND column_name='ordem_servico_id') THEN
        ALTER TABLE documento_artefatos
            ADD COLUMN ordem_servico_id CHAR(36) NULL DEFAULT NULL,
            ADD COLUMN motivo_revisao TEXT NULL DEFAULT NULL,
            ADD COLUMN revisado_por CHAR(36) NULL DEFAULT NULL;
    END IF;
END $$
DELIMITER ;

CALL sp_migration_100_sgq();
DROP PROCEDURE IF EXISTS sp_migration_100_sgq;

-- Atualizar vistoriadores existentes com credencial padrão para não travar a operação diária
UPDATE usuarios
SET status_sgq = 'QUALIFICADO',
    credencial_marinha_validade = COALESCE(credencial_marinha_validade, DATE_ADD(CURRENT_DATE, INTERVAL 1 YEAR)),
    registro_conselho_validade = COALESCE(registro_conselho_validade, DATE_ADD(CURRENT_DATE, INTERVAL 1 YEAR))
WHERE cargo = 'VISTORIADOR';

-- 2. Trilha de Auditoria Cadastral de Mudancas Criticas (ISO 7.5 e ISO 8.2)
CREATE TABLE IF NOT EXISTS sgq_auditoria_cadastral (
    id CHAR(36) NOT NULL PRIMARY KEY,
    entidade_tipo ENUM('CLIENTE','PROPRIETARIO','ARMADOR','DESPACHANTE','EMBARCACAO') NOT NULL,
    entidade_id CHAR(36) NOT NULL,
    acao ENUM('CRIACAO','ALTERACAO','INATIVACAO') NOT NULL,
    dados_anteriores JSON NULL,
    dados_posteriores JSON NULL,
    campos_alterados JSON NULL,
    motivo_justificativa TEXT NULL,
    usuario_id CHAR(36) NULL,
    usuario_nome VARCHAR(150) NULL,
    ip_origem VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_auditoria_entidade (entidade_tipo, entidade_id),
    KEY idx_auditoria_criado_em (criado_em),
    KEY idx_auditoria_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Gestao de Nao Conformidades (RNC - ISO 8.7 e ISO 10.2)
CREATE TABLE IF NOT EXISTS sgq_nao_conformidades (
    id CHAR(36) NOT NULL PRIMARY KEY,
    numero_rnc VARCHAR(30) NOT NULL UNIQUE,
    origem ENUM('AUDITORIA_INTERNA_RT','INSPECAO_CAMPO','RECLAMACAO_CLIENTE','AUDITORIA_EXTERNA') NOT NULL DEFAULT 'AUDITORIA_INTERNA_RT',
    ordem_servico_id CHAR(36) NULL,
    vistoria_id CHAR(36) NULL,
    embarcacao_id CHAR(36) NULL,
    cliente_id CHAR(36) NULL,
    classificacao_falha VARCHAR(100) NULL,
    severidade ENUM('BAIXA','MEDIA','CRITICA_IMPEDITIVA') NOT NULL DEFAULT 'MEDIA',
    titulo VARCHAR(255) NOT NULL,
    descricao_detalhada TEXT NOT NULL,
    analise_causa_raiz TEXT NULL,
    status_ciclo_vida ENUM('ABERTA','EM_ANALISE_CAUSA','PLANO_ACAO_DEFINIDO','EM_EXECUCAO','AGUARDANDO_EFICACIA','ENCERRADA_EFICAZ','REABERTA') NOT NULL DEFAULT 'ABERTA',
    responsavel_abertura_id CHAR(36) NULL,
    responsavel_abertura_nome VARCHAR(150) NULL,
    data_identificacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    data_conclusao_prevista DATE NULL,
    encerrada_em DATETIME NULL,
    encerrada_por CHAR(36) NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_rnc_ordem_servico (ordem_servico_id),
    KEY idx_rnc_vistoria (vistoria_id),
    KEY idx_rnc_embarcacao (embarcacao_id),
    KEY idx_rnc_status (status_ciclo_vida),
    KEY idx_rnc_criado_em (criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Planos de Acao Corretiva 5W2H (ISO 10.2)
CREATE TABLE IF NOT EXISTS sgq_planos_acao (
    id CHAR(36) NOT NULL PRIMARY KEY,
    nao_conformidade_id CHAR(36) NOT NULL,
    o_que_fazer_what TEXT NOT NULL,
    por_que_fazer_why TEXT NULL,
    onde_fazer_where VARCHAR(255) NULL,
    quem_fara_who VARCHAR(150) NOT NULL,
    quando_fara_when DATE NOT NULL,
    como_fazer_how TEXT NULL,
    quanto_custa_how_much DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    status_acao ENUM('PENDENTE','EM_ANDAMENTO','CONCLUIDA','CANCELADA') NOT NULL DEFAULT 'PENDENTE',
    evidencia_cumprimento TEXT NULL,
    eficacia_aprovada_por CHAR(36) NULL,
    eficacia_data DATETIME NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_plano_rnc (nao_conformidade_id),
    KEY idx_plano_status (status_acao),
    CONSTRAINT fk_plano_rnc FOREIGN KEY (nao_conformidade_id) REFERENCES sgq_nao_conformidades (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Pesquisa de Satisfacao do Cliente vinculada a Ordem de Servico (ISO 9.1.2)
CREATE TABLE IF NOT EXISTS sgq_satisfacao_clientes (
    id CHAR(36) NOT NULL PRIMARY KEY,
    ordem_servico_id CHAR(36) NOT NULL UNIQUE,
    cliente_id CHAR(36) NOT NULL,
    embarcacao_id CHAR(36) NULL,
    documento_id CHAR(36) NULL,
    documento_tipo VARCHAR(50) NULL,
    nota_atendimento_comercial TINYINT UNSIGNED NOT NULL,
    nota_qualidade_tecnica TINYINT UNSIGNED NOT NULL,
    nota_cumprimento_prazo TINYINT UNSIGNED NOT NULL,
    nota_nps_geral TINYINT UNSIGNED NOT NULL,
    comentario_elogio_critica TEXT NULL,
    dispositivo_acesso VARCHAR(50) NOT NULL DEFAULT 'PORTAL_WEB',
    ip_origem VARCHAR(45) NULL,
    data_avaliacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_satisfacao_cliente (cliente_id),
    KEY idx_satisfacao_data (data_avaliacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Registrar sequencial RNC se a tabela sequenciais_documentos existir
INSERT INTO sequenciais_documentos (tipo_documento, ano, ultimo_numero)
SELECT 'RNC', YEAR(CURRENT_DATE), 0
WHERE NOT EXISTS (
    SELECT 1 FROM sequenciais_documentos WHERE tipo_documento = 'RNC' AND ano = YEAR(CURRENT_DATE)
);
