-- Permite configurar no assistente o tipo de vistoria e as observacoes do verso.
ALTER TABLE certificados_cnbl
  ADD COLUMN tipo_vistoria_certificado varchar(50) NULL AFTER local_vistoria,
  ADD COLUMN observacoes_verso text NULL AFTER tipo_vistoria_certificado;

ALTER TABLE certificados_cnarq
  ADD COLUMN tipo_vistoria_certificado varchar(50) NULL AFTER local_vistoria,
  ADD COLUMN observacoes_verso text NULL AFTER tipo_vistoria_certificado;
