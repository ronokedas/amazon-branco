-- Migração 121: Adiciona coluna tipo_desconto na tabela propostas para preservar escolha de Desconto Percentual ou Valor Fixo
SET @col_exists = (
    SELECT COUNT(*) 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'propostas' 
      AND COLUMN_NAME = 'tipo_desconto'
);

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE propostas ADD COLUMN tipo_desconto VARCHAR(10) NOT NULL DEFAULT \'perc\' AFTER desconto_valor', 
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
