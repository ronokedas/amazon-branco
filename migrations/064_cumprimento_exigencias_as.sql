-- Cumprimento de exigencias A/S (antes de suspender).
ALTER TABLE vistorias
  ADD COLUMN finalidade ENUM('VISTORIA','CUMPRIMENTO_EXIGENCIAS') NOT NULL DEFAULT 'VISTORIA'
  AFTER relatorio_anterior_id;

ALTER TABLE vistoria_exigencias
  ADD COLUMN antes_de_suspender TINYINT(1) NOT NULL DEFAULT 0
  AFTER vencimento;

-- Classificacao conservadora do legado: nao conformidade sem vencimento era o antigo "AS/sem prazo".
UPDATE vistoria_exigencias
SET antes_de_suspender = 1
WHERE conforme = 'nao'
  AND vencimento IS NULL;

CREATE INDEX idx_vistorias_agendamento_vigente
  ON vistorias (agendamento_id, criado_em, id);

CREATE INDEX idx_exigencias_as_pendentes
  ON vistoria_exigencias (vistoria_id, antes_de_suspender, conforme, status_item);
