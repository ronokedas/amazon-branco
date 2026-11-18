-- Migration 137: Parcelas de propostas como lancamentos financeiros independentes.
-- numero_parcela = 0 identifica a entrada; 1..N identificam as parcelas futuras.
-- Registros antigos permanecem com numero_parcela/total_parcelas NULL.
ALTER TABLE financeiro_lancamentos
    ADD COLUMN numero_parcela SMALLINT UNSIGNED NULL DEFAULT NULL AFTER proposta_id,
    ADD COLUMN total_parcelas SMALLINT UNSIGNED NULL DEFAULT NULL AFTER numero_parcela,
    ADD UNIQUE KEY uk_financeiro_proposta_parcela (proposta_id, numero_parcela);
