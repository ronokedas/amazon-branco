-- Migration 126: Suporte a Analista em Agendamentos e Saneamento de Serviços Duplicados
-- Data: 2026-11-04

-- 1. Adicionar colunas de analista e prazo na tabela agendamentos caso não existam
SET @col_analista := (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'agendamentos' 
      AND COLUMN_NAME = 'analista_id'
);

SET @sql_analista := IF(@col_analista = 0, 
    'ALTER TABLE `agendamentos` ADD COLUMN `analista_id` CHAR(36) NULL AFTER `vistoriador_id`, ADD COLUMN `prazo_analise` DATETIME NULL AFTER `hora_vistoria`, ADD INDEX `idx_agendamentos_analista` (`analista_id`)', 
    'SELECT 1'
);
PREPARE stmt FROM @sql_analista;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adicionar chave estrangeira para analista_id se não existir
SET @fk_analista := (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'agendamentos' 
      AND CONSTRAINT_NAME = 'fk_agendamentos_analista'
);

SET @sql_fk := IF(@fk_analista = 0,
    'ALTER TABLE `agendamentos` ADD CONSTRAINT `fk_agendamentos_analista` FOREIGN KEY (`analista_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql_fk;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Limpar serviços duplicados com preço 0,00 gerados por inserção duplicada
DELETE FROM `servicos`
WHERE `id` LIKE '037bb%'
  AND `preco_padrao` = 0.00
  AND `id` NOT IN (SELECT DISTINCT servico_id FROM `propostas_servicos` WHERE servico_id IS NOT NULL)
  AND `id` NOT IN (SELECT DISTINCT servico_id FROM `analises_planos` WHERE servico_id IS NOT NULL)
  AND `id` NOT IN (SELECT DISTINCT servico_id FROM `protocolo_dossies` WHERE servico_id IS NOT NULL);

-- 3. Limpar testes redundantes de "Vistoria Especial NORMAM-202 Teste E3" mantendo apenas 1 se não referenciados
DELETE FROM `servicos`
WHERE `id` IN (
    'fb8791bd-67f7-41e7-a042-4360ba5d8373',
    '1653484e-b540-47e5-bc76-955b662a9d44',
    '186fd77f-2ab2-405b-be03-a740b520c595'
)
AND `id` NOT IN (SELECT DISTINCT servico_id FROM `propostas_servicos` WHERE servico_id IS NOT NULL)
AND `id` NOT IN (SELECT DISTINCT servico_id FROM `analises_planos` WHERE servico_id IS NOT NULL);

-- 4. Reatribuição de processos vinculados a usuários antigos/desativados para os usuários ativos correspondentes
-- Atualizar análises atribuídas ao usuário antigo desativado 'Itamar' para o analista ativo 'Jerson' (se existirem)
UPDATE `analises_planos`
SET `analista_id` = 'b2645d7e-83df-400b-90a9-37b060e82880',
    `status` = IF(`status` = 'AGUARDANDO_AGENDAMENTO', 'AGENDADA', `status`)
WHERE `analista_id` = '9cd7e53a-da9d-4f2b-9b32-328be32da2f0'
  AND EXISTS (SELECT 1 FROM `usuarios` WHERE `id` = 'b2645d7e-83df-400b-90a9-37b060e82880' AND `ativo` = 1);

-- Atualizar agendamentos atribuídos ao vistoriador antigo desativado 'Neto (teste1)' para o vistoriador ativo 'Neto'
UPDATE `agendamentos`
SET `vistoriador_id` = '7251e89b-e31c-473b-a97d-e22555012afb'
WHERE `vistoriador_id` = 'd2a16613-dfa4-4948-8de4-8c802abdf394'
  AND EXISTS (SELECT 1 FROM `usuarios` WHERE `id` = '7251e89b-e31c-473b-a97d-e22555012afb' AND `ativo` = 1);

-- Para o agendamento de Análise de Planos (proposta 8351de41...), associar também o analista Jerson
UPDATE `agendamentos`
SET `analista_id` = 'b2645d7e-83df-400b-90a9-37b060e82880',
    `prazo_analise` = COALESCE(`prazo_analise`, '2026-11-10 18:00:00')
WHERE `proposta_id` = '8351de41-d7e0-11f1-9768-22806dd0d3e3'
  AND EXISTS (SELECT 1 FROM `usuarios` WHERE `id` = 'b2645d7e-83df-400b-90a9-37b060e82880' AND `ativo` = 1);

