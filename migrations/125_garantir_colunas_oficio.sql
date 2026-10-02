-- Migração 125: Garantia definitiva de colunas para ofício e assinatura digital
-- Compatível com MySQL 8.0 / MariaDB

DROP PROCEDURE IF EXISTS sp_add_col_dossies;
DELIMITER $$
CREATE PROCEDURE sp_add_col_dossies(IN p_col VARCHAR(64), IN p_def TEXT)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'protocolo_dossies' AND COLUMN_NAME = p_col
    ) THEN
        SET @s = CONCAT('ALTER TABLE `protocolo_dossies` ADD COLUMN `', p_col, '` ', p_def);
        PREPARE stmt FROM @s;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END $$
DELIMITER ;

CALL sp_add_col_dossies('destinatario_autoridade', 'VARCHAR(255) NULL');
CALL sp_add_col_dossies('numero_oficio', 'VARCHAR(50) NULL');
CALL sp_add_col_dossies('normam_referencia', 'VARCHAR(50) NULL DEFAULT "NORMAM 202/DPC"');
CALL sp_add_col_dossies('assinado', 'TINYINT(1) NOT NULL DEFAULT 0');
CALL sp_add_col_dossies('assinatura_em', 'DATETIME NULL');
CALL sp_add_col_dossies('responsavel_assinatura_id', 'INT NULL');
CALL sp_add_col_dossies('assinante_nome', 'VARCHAR(200) NULL');
CALL sp_add_col_dossies('assinante_cargo', 'VARCHAR(200) NULL');
CALL sp_add_col_dossies('assinante_registro', 'VARCHAR(100) NULL');
CALL sp_add_col_dossies('assinatura_imagem', 'LONGTEXT NULL');
CALL sp_add_col_dossies('assinatura_ip', 'VARCHAR(45) NULL');
DROP PROCEDURE IF EXISTS sp_add_col_dossies;

DROP PROCEDURE IF EXISTS sp_add_col_movs;
DELIMITER $$
CREATE PROCEDURE sp_add_col_movs(IN p_col VARCHAR(64), IN p_def TEXT)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'protocolo_movimentacoes' AND COLUMN_NAME = p_col
    ) THEN
        SET @s = CONCAT('ALTER TABLE `protocolo_movimentacoes` ADD COLUMN `', p_col, '` ', p_def);
        PREPARE stmt FROM @s;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END $$
DELIMITER ;

CALL sp_add_col_movs('destinatario_autoridade', 'VARCHAR(255) NULL');
CALL sp_add_col_movs('numero_oficio', 'VARCHAR(50) NULL');
CALL sp_add_col_movs('assinado', 'TINYINT(1) NOT NULL DEFAULT 0');
CALL sp_add_col_movs('assinatura_em', 'DATETIME NULL');
CALL sp_add_col_movs('responsavel_assinatura_id', 'INT NULL');
CALL sp_add_col_movs('assinante_nome', 'VARCHAR(200) NULL');
CALL sp_add_col_movs('assinante_cargo', 'VARCHAR(200) NULL');
CALL sp_add_col_movs('assinante_registro', 'VARCHAR(100) NULL');
CALL sp_add_col_movs('assinatura_imagem', 'LONGTEXT NULL');
CALL sp_add_col_movs('assinatura_ip', 'VARCHAR(45) NULL');
DROP PROCEDURE IF EXISTS sp_add_col_movs;
