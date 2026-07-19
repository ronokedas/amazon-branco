-- Migration 071: portal de despachantes e modulo de analise de planos

ALTER TABLE clientes_embarcacoes
  ADD COLUMN status enum('ATIVO','INATIVO') NOT NULL DEFAULT 'ATIVO' AFTER embarcacao_id,
  ADD COLUMN vinculado_em datetime NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER status,
  ADD COLUMN desvinculado_em datetime NULL AFTER vinculado_em,
  ADD COLUMN vinculado_por char(36) NULL AFTER desvinculado_em,
  ADD COLUMN desvinculado_por char(36) NULL AFTER vinculado_por,
  ADD KEY idx_cliente_embarcacao_historico (cliente_id, embarcacao_id, vinculado_em),
  ADD KEY idx_cliente_embarcacao_status (cliente_id, status);

ALTER TABLE clientes_embarcacoes
  DROP FOREIGN KEY clientes_embarcacoes_ibfk_1,
  DROP FOREIGN KEY clientes_embarcacoes_ibfk_2,
  DROP INDEX cliente_embarcacao;

ALTER TABLE clientes_embarcacoes
  ADD CONSTRAINT clientes_embarcacoes_ibfk_1 FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
  ADD CONSTRAINT clientes_embarcacoes_ibfk_2 FOREIGN KEY (embarcacao_id) REFERENCES embarcacoes(id) ON DELETE CASCADE;

ALTER TABLE clientes_embarcacoes
  ADD COLUMN vinculo_ativo_chave varchar(73) NULL AFTER desvinculado_por,
  ADD UNIQUE KEY uk_cliente_embarcacao_ativa (vinculo_ativo_chave);

UPDATE clientes_embarcacoes
SET vinculado_em = COALESCE(criado_em, CURRENT_TIMESTAMP),
    vinculo_ativo_chave = CASE WHEN status='ATIVO' THEN concat(cliente_id, ':', embarcacao_id) ELSE NULL END;

ALTER TABLE cliente_portal_acessos
  ADD COLUMN login varchar(190) NULL AFTER cliente_id;

UPDATE cliente_portal_acessos a
INNER JOIN clientes c ON c.id = a.cliente_id
SET a.login = lower(trim(c.email))
WHERE a.login IS NULL AND c.email IS NOT NULL AND trim(c.email) <> '';

ALTER TABLE cliente_portal_acessos
  ADD UNIQUE KEY uk_cliente_portal_login (login);

CREATE TABLE portal_auditoria (
  id bigint unsigned NOT NULL AUTO_INCREMENT,
  cliente_id char(36) NULL,
  perfil enum('proprietario','despachante') NULL,
  evento enum('LOGIN_SUCESSO','LOGIN_FALHA','VISUALIZACAO','DOWNLOAD') NOT NULL,
  embarcacao_id char(36) NULL,
  documento_tipo varchar(40) NULL,
  documento_id char(36) NULL,
  sucesso tinyint(1) NOT NULL DEFAULT 1,
  detalhe varchar(500) NULL,
  ip varchar(45) NULL,
  user_agent varchar(500) NULL,
  criado_em datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_portal_auditoria_cliente (cliente_id, criado_em),
  KEY idx_portal_auditoria_documento (documento_tipo, documento_id, criado_em),
  CONSTRAINT fk_portal_auditoria_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL,
  CONSTRAINT fk_portal_auditoria_embarcacao FOREIGN KEY (embarcacao_id) REFERENCES embarcacoes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE analises_planos (
  id char(36) NOT NULL,
  numero varchar(30) NOT NULL,
  embarcacao_id char(36) NOT NULL,
  solicitante_id char(36) NULL,
  tipo_processo enum('LC','LCEC','LA','LR','OUTRO') NOT NULL,
  enquadramento enum('NORMAM-201','NORMAM-202','OUTRO') NOT NULL,
  objeto varchar(255) NOT NULL,
  estaleiro varchar(255) NULL,
  numero_casco varchar(100) NULL,
  responsavel_projeto_nome varchar(255) NULL,
  responsavel_projeto_registro varchar(100) NULL,
  art_numero varchar(100) NULL,
  analista_id char(36) NOT NULL,
  responsavel_assinatura_id int NULL,
  status enum('RASCUNHO','EM_ANALISE','AGUARDANDO_CORRECAO','AGUARDANDO_APROVACAO','CONCLUIDA','REPROVADA','CANCELADA') NOT NULL DEFAULT 'RASCUNHO',
  observacoes text NULL,
  criado_por char(36) NOT NULL,
  criado_em datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_analise_planos_numero (numero),
  KEY idx_analise_planos_embarcacao (embarcacao_id, status),
  KEY idx_analise_planos_analista (analista_id, status),
  CONSTRAINT fk_analise_planos_embarcacao FOREIGN KEY (embarcacao_id) REFERENCES embarcacoes(id),
  CONSTRAINT fk_analise_planos_solicitante FOREIGN KEY (solicitante_id) REFERENCES clientes(id) ON DELETE SET NULL,
  CONSTRAINT fk_analise_planos_analista FOREIGN KEY (analista_id) REFERENCES usuarios(id),
  CONSTRAINT fk_analise_planos_responsavel FOREIGN KEY (responsavel_assinatura_id) REFERENCES responsaveis_assinatura(id) ON DELETE SET NULL,
  CONSTRAINT fk_analise_planos_criador FOREIGN KEY (criado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE analise_planos_submissoes (
  id char(36) NOT NULL,
  analise_id char(36) NOT NULL,
  revisao int unsigned NOT NULL,
  descricao varchar(500) NULL,
  recebido_em date NOT NULL,
  criado_por char(36) NOT NULL,
  criado_em datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_analise_submissao_revisao (analise_id, revisao),
  CONSTRAINT fk_analise_submissao FOREIGN KEY (analise_id) REFERENCES analises_planos(id) ON DELETE CASCADE,
  CONSTRAINT fk_analise_submissao_usuario FOREIGN KEY (criado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE analise_planos_arquivos (
  id char(36) NOT NULL,
  submissao_id char(36) NOT NULL,
  categoria varchar(100) NOT NULL,
  nome_original varchar(255) NOT NULL,
  extensao varchar(10) NOT NULL,
  mime_type varchar(150) NOT NULL,
  tamanho_bytes bigint unsigned NOT NULL,
  sha256 char(64) NOT NULL,
  chave_arquivo varchar(500) NOT NULL,
  criado_por char(36) NOT NULL,
  criado_em datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_analise_arquivo_hash (submissao_id, sha256),
  KEY idx_analise_arquivo_submissao (submissao_id, criado_em),
  CONSTRAINT fk_analise_arquivo_submissao FOREIGN KEY (submissao_id) REFERENCES analise_planos_submissoes(id) ON DELETE CASCADE,
  CONSTRAINT fk_analise_arquivo_usuario FOREIGN KEY (criado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE analise_planos_itens (
  id char(36) NOT NULL,
  analise_id char(36) NOT NULL,
  submissao_id char(36) NULL,
  ordem int unsigned NOT NULL,
  documento varchar(255) NOT NULL,
  revisao_documento varchar(50) NULL,
  referencia_normativa varchar(255) NULL,
  resultado enum('PENDENTE','CONFORME','EXIGENCIA','NAO_APLICA') NOT NULL DEFAULT 'PENDENTE',
  observacao text NULL,
  criado_por char(36) NOT NULL,
  criado_em datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_analise_item_ordem (analise_id, ordem),
  CONSTRAINT fk_analise_item FOREIGN KEY (analise_id) REFERENCES analises_planos(id) ON DELETE CASCADE,
  CONSTRAINT fk_analise_item_submissao FOREIGN KEY (submissao_id) REFERENCES analise_planos_submissoes(id) ON DELETE SET NULL,
  CONSTRAINT fk_analise_item_usuario FOREIGN KEY (criado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE analise_planos_exigencias (
  id char(36) NOT NULL,
  analise_id char(36) NOT NULL,
  item_id char(36) NULL,
  ordem int unsigned NOT NULL,
  descricao text NOT NULL,
  referencia_normativa varchar(255) NULL,
  prazo date NULL,
  status enum('PENDENTE','CUMPRIDA','PARCIAL','TRANSCRITA') NOT NULL DEFAULT 'PENDENTE',
  observacao_cumprimento text NULL,
  criado_por char(36) NOT NULL,
  criado_em datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_analise_exigencia (analise_id, status, ordem),
  CONSTRAINT fk_analise_exigencia FOREIGN KEY (analise_id) REFERENCES analises_planos(id) ON DELETE CASCADE,
  CONSTRAINT fk_analise_exigencia_item FOREIGN KEY (item_id) REFERENCES analise_planos_itens(id) ON DELETE SET NULL,
  CONSTRAINT fk_analise_exigencia_usuario FOREIGN KEY (criado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE analise_planos_pareceres (
  id char(36) NOT NULL,
  analise_id char(36) NOT NULL,
  versao int unsigned NOT NULL,
  resultado enum('EXIGENCIAS','APROVADO','APROVADO_COM_EXIGENCIAS','REPROVADO') NOT NULL,
  resumo text NOT NULL,
  conclusao text NOT NULL,
  status enum('MINUTA','AGUARDANDO_APROVACAO','PUBLICADO','CANCELADO') NOT NULL DEFAULT 'MINUTA',
  responsavel_assinatura_id int NULL,
  publicado_em datetime NULL,
  criado_por char(36) NOT NULL,
  criado_em datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_analise_parecer_versao (analise_id, versao),
  KEY idx_analise_parecer_publicado (analise_id, status, publicado_em),
  CONSTRAINT fk_analise_parecer FOREIGN KEY (analise_id) REFERENCES analises_planos(id) ON DELETE CASCADE,
  CONSTRAINT fk_analise_parecer_responsavel FOREIGN KEY (responsavel_assinatura_id) REFERENCES responsaveis_assinatura(id) ON DELETE SET NULL,
  CONSTRAINT fk_analise_parecer_usuario FOREIGN KEY (criado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE analise_planos_historico (
  id bigint unsigned NOT NULL AUTO_INCREMENT,
  analise_id char(36) NOT NULL,
  usuario_id char(36) NOT NULL,
  evento varchar(60) NOT NULL,
  status_anterior varchar(40) NULL,
  status_novo varchar(40) NULL,
  detalhe text NULL,
  criado_em datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_analise_historico (analise_id, criado_em),
  CONSTRAINT fk_analise_historico FOREIGN KEY (analise_id) REFERENCES analises_planos(id) ON DELETE CASCADE,
  CONSTRAINT fk_analise_historico_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO sequenciais_documentos (tipo_documento, ano, ultimo_numero)
VALUES ('RAP', YEAR(CURDATE()), 0)
ON DUPLICATE KEY UPDATE ultimo_numero = ultimo_numero;

INSERT INTO usuario_permissoes (usuario_id, permissao, permitido)
SELECT u.id, 'analise_planos', 1
FROM usuarios u
WHERE u.ativo = 1 AND u.cargo IN ('ANALISTA','ADMIN')
ON DUPLICATE KEY UPDATE permitido = 1;
