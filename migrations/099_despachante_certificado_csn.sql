-- Mantém o CSN compatível com os demais certificados que aceitam despachante.
ALTER TABLE certificados_csn
  ADD COLUMN IF NOT EXISTS despachante_id CHAR(36) NULL AFTER vistoria_id;
