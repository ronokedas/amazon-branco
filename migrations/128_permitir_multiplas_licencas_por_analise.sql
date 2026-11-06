-- Migration 128: Permitir múltiplas licenças (LC, LA, LR, LCEC) vinculadas ao mesmo processo de análise
-- Remove restrição de unicidade analise_id e adiciona índice de busca rápida
ALTER TABLE certificados_lc ADD INDEX idx_lc_analise (analise_id);
ALTER TABLE certificados_lc DROP INDEX uk_licenca_analise;
