-- Migration 119: Garantir coluna observacoes na tabela certificados_lc (Compatibilidade MySQL 8.0)
-- NORMAM-202 Anexo 3-A: Licenças de Construção, Alteração, Reclassificação e LCEC
-- Corrige erro SQLSTATE[42S22]: Column not found: 1054 Unknown column 'observacoes' in 'field list'
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'certificados_lc' AND COLUMN_NAME = 'observacoes');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE certificados_lc ADD COLUMN observacoes TEXT NULL AFTER dados_json', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
