-- PWA de vistorias em campo: perfis múltiplos, evidências e sincronização idempotente.

CREATE TABLE IF NOT EXISTS usuario_perfis (
    usuario_id CHAR(36) NOT NULL,
    perfil ENUM('ADMIN','VENDEDOR','VISTORIADOR') NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (usuario_id, perfil),
    CONSTRAINT fk_usuario_perfis_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO usuario_perfis (usuario_id, perfil)
SELECT id, cargo FROM usuarios;

ALTER TABLE vistorias
    ADD COLUMN mobile_versao INT UNSIGNED NOT NULL DEFAULT 0 AFTER status,
    ADD COLUMN mobile_finalizada_em DATETIME NULL AFTER mobile_versao;

ALTER TABLE vistoria_checklist_respostas
    ADD COLUMN sem_prazo TINYINT(1) NOT NULL DEFAULT 0 AFTER vencimento;

CREATE TABLE IF NOT EXISTS vistoria_anexos (
    id CHAR(36) NOT NULL,
    vistoria_id CHAR(36) NOT NULL,
    catalogo_id CHAR(36) NULL,
    url_arquivo VARCHAR(1000) NOT NULL,
    chave_arquivo VARCHAR(500) NULL,
    nome_original VARCHAR(255) NULL,
    mime_type VARCHAR(100) NOT NULL,
    tamanho_bytes INT UNSIGNED NOT NULL,
    sha256 CHAR(64) NOT NULL,
    capturado_em DATETIME NULL,
    criado_por CHAR(36) NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_vistoria_anexo_hash (vistoria_id, sha256),
    KEY idx_vistoria_anexos_catalogo (catalogo_id),
    KEY idx_vistoria_anexos_criado_por (criado_por),
    CONSTRAINT fk_vistoria_anexos_vistoria
        FOREIGN KEY (vistoria_id) REFERENCES vistorias(id) ON DELETE CASCADE,
    CONSTRAINT fk_vistoria_anexos_catalogo
        FOREIGN KEY (catalogo_id) REFERENCES exigencias_catalogo(id) ON DELETE SET NULL,
    CONSTRAINT fk_vistoria_anexos_usuario
        FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS vistoria_mobile_sync (
    operacao_id CHAR(36) NOT NULL,
    vistoria_id CHAR(36) NOT NULL,
    usuario_id CHAR(36) NULL,
    tipo ENUM('RASCUNHO','ANEXO','FINALIZACAO') NOT NULL,
    payload_hash CHAR(64) NOT NULL,
    resposta_json JSON NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (operacao_id),
    KEY idx_mobile_sync_vistoria (vistoria_id, criado_em),
    CONSTRAINT fk_mobile_sync_vistoria
        FOREIGN KEY (vistoria_id) REFERENCES vistorias(id) ON DELETE CASCADE,
    CONSTRAINT fk_mobile_sync_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
