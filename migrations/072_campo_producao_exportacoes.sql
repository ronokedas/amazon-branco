-- Produção do PWA Campo e central assíncrona de exportações documentais.
CREATE TABLE IF NOT EXISTS campo_login_tentativas (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    email_hash CHAR(64) NOT NULL,
    ip_hash CHAR(64) NOT NULL,
    sucesso TINYINT(1) NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_campo_login_bloqueio (email_hash, ip_hash, criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS campo_sessoes (
    id CHAR(64) NOT NULL,
    usuario_id CHAR(36) NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ultimo_acesso_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expira_em DATETIME NOT NULL,
    revogado_em DATETIME NULL,
    ip_hash CHAR(64) NULL,
    user_agent_hash CHAR(64) NULL,
    PRIMARY KEY (id),
    KEY idx_campo_sessoes_usuario (usuario_id, expira_em),
    CONSTRAINT fk_campo_sessoes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS exportacoes_documentos (
    id CHAR(36) NOT NULL,
    solicitado_por CHAR(36) NOT NULL,
    status ENUM('AGUARDANDO','PROCESSANDO','CONCLUIDA','FALHA','EXPIRADA') NOT NULL DEFAULT 'AGUARDANDO',
    categorias_json JSON NOT NULL,
    filtros_json JSON NULL,
    caminho_arquivo VARCHAR(500) NULL,
    nome_arquivo VARCHAR(255) NULL,
    tamanho_bytes BIGINT UNSIGNED NULL,
    quantidade_arquivos INT UNSIGNED NOT NULL DEFAULT 0,
    sha256 CHAR(64) NULL,
    erro TEXT NULL,
    solicitado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    iniciado_em DATETIME NULL,
    concluido_em DATETIME NULL,
    expira_em DATETIME NULL,
    baixado_em DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_exportacoes_status (status, solicitado_em),
    KEY idx_exportacoes_usuario (solicitado_por, solicitado_em),
    CONSTRAINT fk_exportacoes_usuario FOREIGN KEY (solicitado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS documento_artefatos (
    id CHAR(36) NOT NULL,
    documento_tipo VARCHAR(50) NOT NULL,
    documento_id CHAR(36) NOT NULL,
    versao INT UNSIGNED NOT NULL DEFAULT 1,
    status_documento VARCHAR(50) NULL,
    caminho_arquivo VARCHAR(500) NOT NULL,
    nome_arquivo VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL DEFAULT 'application/pdf',
    tamanho_bytes BIGINT UNSIGNED NOT NULL,
    sha256 CHAR(64) NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_documento_artefato (documento_tipo, documento_id, versao),
    KEY idx_documento_artefatos_tipo (documento_tipo, criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE vistoria_anexos
    ADD COLUMN IF NOT EXISTS excluido_em DATETIME NULL AFTER criado_em,
    ADD COLUMN IF NOT EXISTS excluido_por CHAR(36) NULL AFTER excluido_em;
