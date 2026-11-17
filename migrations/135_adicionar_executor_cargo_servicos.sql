-- Migration 135: Adicionar executor_cargo na tabela servicos para segregação estrita entre Vistoriador e Analista
-- Garante que serviços de Análise de Planos (Engenharia) fiquem com o Analista e Vistorias fiquem com o Vistoriador

ALTER TABLE `servicos`
    ADD COLUMN `executor_cargo` ENUM('VISTORIADOR', 'ANALISTA', 'AMBOS') NOT NULL DEFAULT 'VISTORIADOR' AFTER `certificado_modelo`,
    ADD INDEX `idx_servicos_executor_cargo` (`executor_cargo`);

-- 1. Serviços exclusivos de Analista Naval (Engenharia / Escritório)
UPDATE `servicos`
SET `executor_cargo` = 'ANALISTA'
WHERE `codigo_operacional` IN ('ANALISE_PLANOS_EC1', 'ANALISE_PLANOS_EC2')
   OR LOWER(`nome`) LIKE '%análise de planos%'
   OR LOWER(`nome`) LIKE '%analise de planos%';

-- 2. Serviços de Vistoria Técnica de Campo (Vistoriador)
UPDATE `servicos`
SET `executor_cargo` = 'VISTORIADOR'
WHERE `certificado_modelo` IS NOT NULL
   OR LOWER(`nome`) LIKE '%vistoria%';

-- 3. Serviços que podem ser acompanhados por ambos ou apoio técnico
UPDATE `servicos`
SET `executor_cargo` = 'AMBOS'
WHERE LOWER(`nome`) LIKE '%acompanhamento%'
  AND LOWER(`nome`) NOT LIKE '%vistoria%';
