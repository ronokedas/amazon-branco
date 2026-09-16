-- Migration 111: Ampliar relatorio_numero em certificados_lc para acomodar processo + relatório de ciclo
ALTER TABLE `certificados_lc` MODIFY COLUMN `relatorio_numero` VARCHAR(100) DEFAULT NULL;
