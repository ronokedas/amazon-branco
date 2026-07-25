ALTER TABLE responsaveis_assinatura
  ADD COLUMN email varchar(190) NULL AFTER cpf_cnpj,
  ADD COLUMN usuario_id char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL AFTER email,
  ADD UNIQUE KEY uk_responsavel_assinatura_usuario (usuario_id),
  ADD KEY idx_responsavel_assinatura_email (email),
  ADD CONSTRAINT fk_responsavel_assinatura_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT;

ALTER TABLE vistorias
  ADD COLUMN assinatura_status enum('PENDENTE','ASSINADO','CANCELADO') NOT NULL DEFAULT 'PENDENTE' AFTER responsavel_assinatura_id,
  ADD COLUMN assinatura_em datetime NULL AFTER assinatura_status;

CREATE TABLE documento_assinaturas (
  id char(36) NOT NULL,
  documento_tipo varchar(20) NOT NULL,
  documento_id char(36) NOT NULL,
  versao int unsigned NOT NULL DEFAULT 1,
  responsavel_id int NOT NULL,
  usuario_id char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  assinatura_arquivo varchar(500) NOT NULL,
  assinatura_hash char(64) NOT NULL,
  hash_pdf_original char(64) NOT NULL,
  hash_pdf_assinado char(64) NOT NULL,
  caminho_pdf_original varchar(500) NOT NULL,
  caminho_pdf_assinado varchar(500) NOT NULL,
  token_validacao char(64) NOT NULL,
  latitude decimal(10,8) NOT NULL,
  longitude decimal(11,8) NOT NULL,
  geo_precisao_m decimal(10,2) NULL,
  ip varchar(45) NOT NULL,
  user_agent varchar(500) NULL,
  status enum('ASSINADO','CANCELADO') NOT NULL DEFAULT 'ASSINADO',
  assinado_em datetime NOT NULL,
  cancelado_em datetime NULL,
  cancelado_por char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  motivo_cancelamento varchar(1000) NULL,
  criado_em datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_documento_assinatura_versao (documento_tipo, documento_id, versao),
  UNIQUE KEY uk_documento_assinatura_token (token_validacao),
  KEY idx_documento_assinatura_documento (documento_tipo, documento_id, status),
  KEY idx_documento_assinatura_responsavel (responsavel_id),
  KEY idx_documento_assinatura_usuario (usuario_id),
  CONSTRAINT fk_documento_assinatura_responsavel FOREIGN KEY (responsavel_id) REFERENCES responsaveis_assinatura(id) ON DELETE RESTRICT,
  CONSTRAINT fk_documento_assinatura_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
