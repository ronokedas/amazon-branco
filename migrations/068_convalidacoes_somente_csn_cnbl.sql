-- Convalidacoes existem apenas nos modelos CSN e CNBL.
-- CSN usa csn_convalidacoes; esta tabela compartilhada fica exclusiva do CNBL.
DELETE FROM cert_convalidacoes WHERE tipo_certificado = 'CNARQ';

ALTER TABLE cert_convalidacoes
  MODIFY COLUMN tipo_certificado enum('CNBL') NOT NULL
  COMMENT 'Convalidacoes exclusivas do certificado CNBL',
  ADD COLUMN atualizado_em datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE csn_convalidacoes
  ADD COLUMN atualizado_em datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP;
