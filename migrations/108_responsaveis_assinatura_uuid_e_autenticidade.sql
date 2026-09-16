-- ==============================================================================
-- Migration 108: Identificador Único Universal (UUID) em responsaveis_assinatura
-- Etapa 5 da Reestruturação do Sistema
-- Padronização da única entidade mestre que utilizava apenas ID inteiro sequencial
-- ==============================================================================

-- 1. Adicionar coluna uuid se não existir
SET @col_uuid_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'responsaveis_assinatura' 
      AND COLUMN_NAME = 'uuid'
);

SET @sql_add_uuid = IF(
    @col_uuid_exists = 0,
    'ALTER TABLE responsaveis_assinatura ADD COLUMN uuid CHAR(36) NULL AFTER id',
    'SELECT "Coluna uuid ja existe em responsaveis_assinatura"'
);
PREPARE stmt_add_uuid FROM @sql_add_uuid;
EXECUTE stmt_add_uuid;
DEALLOCATE PREPARE stmt_add_uuid;

-- 2. Preenchimento retroativo de UUID para registros legados existentes
UPDATE responsaveis_assinatura 
SET uuid = UUID() 
WHERE uuid IS NULL OR uuid = '';

-- 3. Definir valor padrao DEFAULT (UUID()) nativo do MySQL 8.0 e NOT NULL
ALTER TABLE responsaveis_assinatura 
    MODIFY COLUMN uuid CHAR(36) NOT NULL DEFAULT (UUID());

-- 4. Adicionar índice UNIQUE se não existir
SET @idx_uuid_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'responsaveis_assinatura' 
      AND INDEX_NAME = 'uk_responsaveis_assinatura_uuid'
);

SET @sql_idx_uuid = IF(
    @idx_uuid_exists = 0,
    'ALTER TABLE responsaveis_assinatura ADD UNIQUE KEY uk_responsaveis_assinatura_uuid (uuid)',
    'SELECT "Indice uk_responsaveis_assinatura_uuid ja existe"'
);
PREPARE stmt_idx_uuid FROM @sql_idx_uuid;
EXECUTE stmt_idx_uuid;
DEALLOCATE PREPARE stmt_idx_uuid;
