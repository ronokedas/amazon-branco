-- Migration 115: Adicionar coluna as_impeditivo nas exigencias de analise de planos
-- Permite ao analista marcar exigencias graves como A/S (Acao/Assunto Suspensivo) que impedem a emissao de licencas/certificados.

ALTER TABLE analise_planos_exigencias 
ADD COLUMN as_impeditivo TINYINT(1) NOT NULL DEFAULT 0 AFTER referencia_normativa;

ALTER TABLE analise_planos_relatorio_exigencias 
ADD COLUMN as_snapshot TINYINT(1) NOT NULL DEFAULT 0 AFTER manifestacao_tecnica;
