ALTER TABLE responsaveis_assinatura
  ADD COLUMN cpf_cnpj varchar(18) NULL AFTER nome_completo,
  ADD COLUMN assinatura_arquivo varchar(500) NULL AFTER registro_profissional,
  ADD COLUMN assinatura_hash char(64) NULL AFTER assinatura_arquivo,
  ADD COLUMN assinatura_atualizada_em datetime NULL AFTER assinatura_hash;

ALTER TABLE certificados_csn ADD COLUMN responsavel_assinatura_id int NULL AFTER assinante_registro;
ALTER TABLE certificados_cnbl ADD COLUMN responsavel_assinatura_id int NULL AFTER assinante_registro;
ALTER TABLE certificados_cnarq ADD COLUMN responsavel_assinatura_id int NULL AFTER assinante_registro;
ALTER TABLE certificados_lp ADD COLUMN responsavel_assinatura_id int NULL AFTER assinante_registro;
ALTER TABLE certificados_lc ADD COLUMN responsavel_assinatura_id int NULL AFTER assinante_registro;
ALTER TABLE certificados_cht ADD COLUMN responsavel_assinatura_id int NULL AFTER assinante_registro;
ALTER TABLE vistorias ADD COLUMN responsavel_assinatura_id int NULL AFTER aprovado_por;

CREATE TABLE documento_aprovacoes (
  id char(36) NOT NULL,
  documento_tipo varchar(20) NOT NULL,
  documento_id char(36) NOT NULL,
  versao int unsigned NOT NULL DEFAULT 1,
  responsavel_id int NOT NULL,
  aprovador_usuario_id char(36) NOT NULL,
  responsavel_nome varchar(255) NOT NULL,
  responsavel_cpf_cnpj varchar(18) NOT NULL,
  responsavel_cargo varchar(255) NOT NULL,
  responsavel_registro varchar(100) NULL,
  aprovador_nome varchar(255) NOT NULL,
  assinatura_arquivo varchar(500) NOT NULL,
  assinatura_hash char(64) NOT NULL,
  aprovado_em_utc datetime NOT NULL,
  aprovado_em_local datetime NOT NULL,
  fuso_horario varchar(64) NOT NULL DEFAULT 'America/Sao_Paulo',
  utc_offset varchar(6) NOT NULL,
  latitude decimal(10,8) NOT NULL,
  longitude decimal(11,8) NOT NULL,
  geo_precisao_m decimal(10,2) NULL,
  ip varchar(45) NOT NULL,
  user_agent varchar(500) NULL,
  hash_pdf_original char(64) NULL,
  hash_pdf_final char(64) NULL,
  caminho_pdf_original varchar(500) NULL,
  caminho_pdf_final varchar(500) NULL,
  token_validacao char(64) NOT NULL,
  status enum('PROCESSANDO','APROVADO','FALHA','CANCELADO') NOT NULL DEFAULT 'PROCESSANDO',
  padrao_assinatura enum('AUDIT_ONLY','PADES_ICP_BRASIL') NOT NULL DEFAULT 'AUDIT_ONLY',
  status_pades enum('NAO_APLICADO','APLICADO','INVALIDO') NOT NULL DEFAULT 'NAO_APLICADO',
  provedor_assinatura varchar(100) NULL,
  certificado_titular varchar(255) NULL,
  certificado_serial varchar(255) NULL,
  certificado_valido_de datetime NULL,
  certificado_valido_ate datetime NULL,
  erro_processamento varchar(1000) NULL,
  criado_em datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_documento_aprovacao_versao (documento_tipo, documento_id, versao),
  UNIQUE KEY uk_documento_aprovacao_token (token_validacao),
  KEY idx_documento_aprovacao_documento (documento_tipo, documento_id, status),
  KEY idx_documento_aprovacao_responsavel (responsavel_id),
  KEY idx_documento_aprovacao_usuario (aprovador_usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
