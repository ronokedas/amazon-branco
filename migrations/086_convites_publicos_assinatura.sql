CREATE TABLE assinatura_convites (
  id char(36) NOT NULL,
  documento_tipo enum('CSN','CNBL','CNARQ') NOT NULL,
  documento_id char(36) NOT NULL,
  responsavel_id int NOT NULL,
  usuario_id char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  token_hash char(64) NOT NULL,
  email_destinatario varchar(190) NOT NULL,
  status enum('ATIVO','PROCESSANDO','UTILIZADO','CANCELADO') NOT NULL DEFAULT 'ATIVO',
  autenticacao_metodo varchar(32) NOT NULL DEFAULT 'EMAIL_MAGIC_LINK',
  expira_em datetime NOT NULL,
  enviado_em datetime NULL,
  utilizado_em datetime NULL,
  cancelado_em datetime NULL,
  cancelado_por char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  aprovacao_id char(36) NULL,
  criado_em datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_assinatura_convite_token (token_hash),
  KEY idx_assinatura_convite_documento (documento_tipo,documento_id,status),
  KEY idx_assinatura_convite_expiracao (status,expira_em),
  KEY idx_assinatura_convite_responsavel (responsavel_id),
  KEY idx_assinatura_convite_usuario (usuario_id),
  CONSTRAINT fk_assinatura_convite_responsavel FOREIGN KEY (responsavel_id) REFERENCES responsaveis_assinatura(id) ON DELETE RESTRICT,
  CONSTRAINT fk_assinatura_convite_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE documento_aprovacoes
  ADD COLUMN autenticacao_metodo varchar(32) NOT NULL DEFAULT 'SESSAO' AFTER user_agent,
  ADD COLUMN assinatura_convite_id char(36) NULL AFTER autenticacao_metodo,
  ADD KEY idx_documento_aprovacao_convite (assinatura_convite_id),
  ADD CONSTRAINT fk_documento_aprovacao_convite FOREIGN KEY (assinatura_convite_id) REFERENCES assinatura_convites(id) ON DELETE SET NULL;
