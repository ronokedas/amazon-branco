-- Generaliza o fluxo auditavel de retornos e preserva a numeracao comparativa.

ALTER TABLE vistoria_retornos
  ADD COLUMN tipo ENUM('AS','EXIGENCIAS') NOT NULL DEFAULT 'AS' AFTER relatorio_resultado_id,
  ADD KEY idx_vistoria_retornos_tipo_status (tipo,status);

ALTER TABLE vistoria_exigencias
  ADD COLUMN descricao_reescrita TEXT NULL AFTER descricao,
  ADD COLUMN numero_origem SMALLINT UNSIGNED NULL AFTER ordem,
  ADD COLUMN numero_sequencial SMALLINT UNSIGNED NULL AFTER numero_origem,
  ADD KEY idx_vistoria_exigencias_sequencial (vistoria_id,numero_sequencial);
