ALTER TABLE embarcacoes
    ADD COLUMN foto_chave VARCHAR(500) NULL AFTER observacoes,
    ADD COLUMN foto_url VARCHAR(500) NULL AFTER foto_chave,
    ADD COLUMN foto_nome_original VARCHAR(255) NULL AFTER foto_url,
    ADD COLUMN foto_mime_type VARCHAR(100) NULL AFTER foto_nome_original,
    ADD COLUMN foto_tamanho_bytes BIGINT UNSIGNED NULL AFTER foto_mime_type,
    ADD COLUMN foto_sha256 CHAR(64) NULL AFTER foto_tamanho_bytes,
    ADD COLUMN foto_atualizada_em DATETIME NULL AFTER foto_sha256,
    ADD COLUMN foto_atualizada_por CHAR(36) NULL AFTER foto_atualizada_em;

ALTER TABLE vistoria_mobile_sync
    MODIFY COLUMN vistoria_id CHAR(36) NULL,
    MODIFY COLUMN tipo ENUM('RASCUNHO','ANEXO','FOTO_EMBARCACAO','FINALIZACAO') NOT NULL;

CREATE INDEX idx_embarcacoes_foto_atualizada
    ON embarcacoes (foto_atualizada_em);
