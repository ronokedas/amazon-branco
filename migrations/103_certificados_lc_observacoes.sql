-- Migration 103: Adicionar coluna observacoes na tabela certificados_lc
-- NORMAM-202 Anexo 3-A: Licenças de Construção, Alteração, Reclassificação e LCEC
ALTER TABLE `certificados_lc` 
  ADD COLUMN IF NOT EXISTS `observacoes` TEXT NULL AFTER `dados_json`;
