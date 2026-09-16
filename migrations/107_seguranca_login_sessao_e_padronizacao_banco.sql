-- ==============================================================================
-- Migration 107: Segurança de Login, Invalidação de Sessão e Padronização de Collation/Soft-Delete
-- Etapa 1 da Reestruturação do Sistema
-- ==============================================================================

-- 1. Tabela para Rate Limiting e Auditoria de Tentativas de Login Web
CREATE TABLE IF NOT EXISTS login_tentativas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip VARCHAR(45) NOT NULL,
    email VARCHAR(150) NOT NULL,
    sucesso TINYINT(1) NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_login_ip_tempo (ip, criado_em),
    INDEX idx_login_email_tempo (email, criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Coluna de Versão de Sessão em usuarios para invalidação instantânea
-- Ao inativar, alterar cargo ou permissões de um usuário, versao_sessao é incrementada,
-- invalidando imediatamente qualquer sessão aberta em outros navegadores.
SET @col_versao_sessao = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'usuarios' 
      AND COLUMN_NAME = 'versao_sessao'
);

SET @sql_add_versao_sessao = IF(
    @col_versao_sessao = 0,
    'ALTER TABLE usuarios ADD COLUMN versao_sessao INT NOT NULL DEFAULT 1 AFTER ativo, ADD INDEX idx_usuarios_versao_sessao (id, versao_sessao, ativo)',
    'SELECT "Coluna versao_sessao ja existe em usuarios"'
);
PREPARE stmt_versao FROM @sql_add_versao_sessao;
EXECUTE stmt_versao;
DEALLOCATE PREPARE stmt_versao;

-- 3. Padronização de Collation das 5 tabelas divergentes para utf8mb4_general_ci
SET FOREIGN_KEY_CHECKS = 0;
ALTER TABLE responsaveis_assinatura CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE assinatura_convites CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE campo_login_tentativas CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE documento_aprovacoes CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE documento_assinaturas CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
SET FOREIGN_KEY_CHECKS = 1;

-- 4. Padronização de Soft Delete em tabelas mestres navais
-- Adiciona excluido_em nas entidades que possuem ativo mas nao possuiam timestamp de soft delete

-- embarcacoes
SET @col_emb_excluido = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'embarcacoes' AND COLUMN_NAME = 'excluido_em'
);
SET @sql_emb_excluido = IF(
    @col_emb_excluido = 0,
    'ALTER TABLE embarcacoes ADD COLUMN excluido_em DATETIME NULL AFTER atualizado_em, ADD INDEX idx_embarcacoes_ativo_excluido (ativo, excluido_em)',
    'SELECT "Coluna excluido_em ja existe em embarcacoes"'
);
PREPARE stmt_emb FROM @sql_emb_excluido;
EXECUTE stmt_emb;
DEALLOCATE PREPARE stmt_emb;

-- clientes
SET @col_cli_ativo = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'clientes' AND COLUMN_NAME = 'ativo'
);
SET @sql_cli_ativo = IF(
    @col_cli_ativo = 0,
    'ALTER TABLE clientes ADD COLUMN ativo TINYINT(1) NOT NULL DEFAULT 1 AFTER status',
    'SELECT "Coluna ativo ja existe em clientes"'
);
PREPARE stmt_cli_atv FROM @sql_cli_ativo;
EXECUTE stmt_cli_atv;
DEALLOCATE PREPARE stmt_cli_atv;

-- Atualizar clientes.ativo baseado no status existente
UPDATE clientes SET ativo = IF(status = 'ATIVO', 1, 0) WHERE ativo IS NOT NULL;

SET @col_cli_excluido = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'clientes' AND COLUMN_NAME = 'excluido_em'
);
SET @sql_cli_excluido = IF(
    @col_cli_excluido = 0,
    'ALTER TABLE clientes ADD COLUMN excluido_em DATETIME NULL AFTER atualizado_em, ADD INDEX idx_clientes_ativo_excluido (ativo, excluido_em)',
    'SELECT "Coluna excluido_em ja existe em clientes"'
);
PREPARE stmt_cli_exc FROM @sql_cli_excluido;
EXECUTE stmt_cli_exc;
DEALLOCATE PREPARE stmt_cli_exc;

-- servicos
SET @col_srv_excluido = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'servicos' AND COLUMN_NAME = 'excluido_em'
);
SET @sql_srv_excluido = IF(
    @col_srv_excluido = 0,
    'ALTER TABLE servicos ADD COLUMN excluido_em DATETIME NULL AFTER ativo, ADD INDEX idx_servicos_ativo_excluido (ativo, excluido_em)',
    'SELECT "Coluna excluido_em ja existe em servicos"'
);
PREPARE stmt_srv FROM @sql_srv_excluido;
EXECUTE stmt_srv;
DEALLOCATE PREPARE stmt_srv;

-- escritorios
SET @col_esc_excluido = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'escritorios' AND COLUMN_NAME = 'excluido_em'
);
SET @sql_esc_excluido = IF(
    @col_esc_excluido = 0,
    'ALTER TABLE escritorios ADD COLUMN excluido_em DATETIME NULL AFTER ativo, ADD INDEX idx_escritorios_ativo_excluido (ativo, excluido_em)',
    'SELECT "Coluna excluido_em ja existe em escritorios"'
);
PREPARE stmt_esc FROM @sql_esc_excluido;
EXECUTE stmt_esc;
DEALLOCATE PREPARE stmt_esc;

-- responsaveis_assinatura
SET @col_resp_excluido = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'responsaveis_assinatura' AND COLUMN_NAME = 'excluido_em'
);
SET @sql_resp_excluido = IF(
    @col_resp_excluido = 0,
    'ALTER TABLE responsaveis_assinatura ADD COLUMN excluido_em DATETIME NULL AFTER ativo, ADD INDEX idx_responsaveis_ativo_excluido (ativo, excluido_em)',
    'SELECT "Coluna excluido_em ja existe em responsaveis_assinatura"'
);
PREPARE stmt_resp FROM @sql_resp_excluido;
EXECUTE stmt_resp;
DEALLOCATE PREPARE stmt_resp;
