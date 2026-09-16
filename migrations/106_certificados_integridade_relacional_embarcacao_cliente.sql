-- Migration 106: Integridade Relacional entre Certificados e Entidades (Embarcações e Clientes)
-- Prioridade Máxima: Evitar desatualização silenciosa mantendo 100% da integridade histórica.

DELIMITER $$
DROP PROCEDURE IF EXISTS sp_migration_106_certificados $$
CREATE PROCEDURE sp_migration_106_certificados()
BEGIN
    -- 1. Colunas de vínculo em certificados_csn
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='certificados_csn' AND column_name='embarcacao_id') THEN
        ALTER TABLE certificados_csn ADD COLUMN embarcacao_id CHAR(36) NULL AFTER id;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='certificados_csn' AND column_name='cliente_id') THEN
        ALTER TABLE certificados_csn ADD COLUMN cliente_id CHAR(36) NULL AFTER embarcacao_id;
    END IF;

    -- 2. Colunas de vínculo em certificados_cnbl
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='certificados_cnbl' AND column_name='embarcacao_id') THEN
        ALTER TABLE certificados_cnbl ADD COLUMN embarcacao_id CHAR(36) NULL AFTER id;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='certificados_cnbl' AND column_name='cliente_id') THEN
        ALTER TABLE certificados_cnbl ADD COLUMN cliente_id CHAR(36) NULL AFTER embarcacao_id;
    END IF;

    -- 3. Colunas de vínculo em certificados_cnarq
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='certificados_cnarq' AND column_name='embarcacao_id') THEN
        ALTER TABLE certificados_cnarq ADD COLUMN embarcacao_id CHAR(36) NULL AFTER id;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='certificados_cnarq' AND column_name='cliente_id') THEN
        ALTER TABLE certificados_cnarq ADD COLUMN cliente_id CHAR(36) NULL AFTER embarcacao_id;
    END IF;

    -- 4. Colunas de vínculo em certificados_lp
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='certificados_lp' AND column_name='cliente_id') THEN
        ALTER TABLE certificados_lp ADD COLUMN cliente_id CHAR(36) NULL AFTER embarcacao_id;
    END IF;

    -- 5. Colunas de vínculo em certificados_lc
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='certificados_lc' AND column_name='cliente_id') THEN
        ALTER TABLE certificados_lc ADD COLUMN cliente_id CHAR(36) NULL AFTER embarcacao_id;
    END IF;

    -- 6. Colunas de vínculo em certificados_cht
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='certificados_cht' AND column_name='embarcacao_id') THEN
        ALTER TABLE certificados_cht ADD COLUMN embarcacao_id CHAR(36) NULL AFTER id;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='certificados_cht' AND column_name='cliente_id') THEN
        ALTER TABLE certificados_cht ADD COLUMN cliente_id CHAR(36) NULL AFTER embarcacao_id;
    END IF;

    -- 7. Backfill Inteligente para registros existentes
    -- 7.1 CSN
    UPDATE certificados_csn c
    JOIN vistorias v ON v.id = c.vistoria_id
    SET c.embarcacao_id = v.embarcacao_id,
        c.cliente_id = COALESCE(v.armador_id, (SELECT e.proprietario_id FROM embarcacoes e WHERE e.id = v.embarcacao_id))
    WHERE c.embarcacao_id IS NULL;

    UPDATE certificados_csn c
    JOIN embarcacoes e ON (
      (c.numero_inscricao IS NOT NULL AND c.numero_inscricao <> '' AND e.numero_inscricao = c.numero_inscricao)
      OR (c.nome_embarcacao IS NOT NULL AND c.nome_embarcacao <> '' AND LOWER(TRIM(e.nome)) = LOWER(TRIM(c.nome_embarcacao)))
    )
    SET c.embarcacao_id = e.id,
        c.cliente_id = COALESCE(c.cliente_id, e.proprietario_id)
    WHERE c.embarcacao_id IS NULL;

    -- 7.2 CNBL
    UPDATE certificados_cnbl c
    JOIN vistorias v ON v.id = c.vistoria_id
    SET c.embarcacao_id = v.embarcacao_id,
        c.cliente_id = COALESCE(v.armador_id, (SELECT e.proprietario_id FROM embarcacoes e WHERE e.id = v.embarcacao_id))
    WHERE c.embarcacao_id IS NULL;

    UPDATE certificados_cnbl c
    JOIN embarcacoes e ON (
      (c.numero_inscricao IS NOT NULL AND c.numero_inscricao <> '' AND e.numero_inscricao = c.numero_inscricao)
      OR (c.nome_embarcacao IS NOT NULL AND c.nome_embarcacao <> '' AND LOWER(TRIM(e.nome)) = LOWER(TRIM(c.nome_embarcacao)))
    )
    SET c.embarcacao_id = e.id,
        c.cliente_id = COALESCE(c.cliente_id, e.proprietario_id)
    WHERE c.embarcacao_id IS NULL;

    -- 7.3 CNARQ
    UPDATE certificados_cnarq c
    JOIN vistorias v ON v.id = c.vistoria_id
    SET c.embarcacao_id = v.embarcacao_id,
        c.cliente_id = COALESCE(v.armador_id, (SELECT e.proprietario_id FROM embarcacoes e WHERE e.id = v.embarcacao_id))
    WHERE c.embarcacao_id IS NULL;

    UPDATE certificados_cnarq c
    JOIN embarcacoes e ON (
      (c.numero_inscricao IS NOT NULL AND c.numero_inscricao <> '' AND e.numero_inscricao = c.numero_inscricao)
      OR (c.nome_embarcacao IS NOT NULL AND c.nome_embarcacao <> '' AND LOWER(TRIM(e.nome)) = LOWER(TRIM(c.nome_embarcacao)))
    )
    SET c.embarcacao_id = e.id,
        c.cliente_id = COALESCE(c.cliente_id, e.proprietario_id)
    WHERE c.embarcacao_id IS NULL;

    -- 7.4 LP
    UPDATE certificados_lp c
    JOIN vistorias v ON v.id = c.vistoria_id
    SET c.embarcacao_id = COALESCE(c.embarcacao_id, v.embarcacao_id),
        c.cliente_id = COALESCE(c.cliente_id, v.armador_id, (SELECT e.proprietario_id FROM embarcacoes e WHERE e.id = COALESCE(c.embarcacao_id, v.embarcacao_id)))
    WHERE c.embarcacao_id IS NULL OR c.cliente_id IS NULL;

    UPDATE certificados_lp c
    JOIN embarcacoes e ON e.id = c.embarcacao_id
    SET c.cliente_id = COALESCE(c.cliente_id, e.proprietario_id)
    WHERE c.cliente_id IS NULL AND c.embarcacao_id IS NOT NULL;

    -- 7.5 LC
    UPDATE certificados_lc c
    JOIN analises_planos ap ON ap.id = c.analise_id
    SET c.embarcacao_id = COALESCE(c.embarcacao_id, ap.embarcacao_id),
        c.cliente_id = COALESCE(c.cliente_id, ap.solicitante_id)
    WHERE c.embarcacao_id IS NULL OR c.cliente_id IS NULL;

    UPDATE certificados_lc c
    JOIN vistorias v ON v.id = c.vistoria_id
    SET c.embarcacao_id = COALESCE(c.embarcacao_id, v.embarcacao_id),
        c.cliente_id = COALESCE(c.cliente_id, v.armador_id, (SELECT e.proprietario_id FROM embarcacoes e WHERE e.id = COALESCE(c.embarcacao_id, v.embarcacao_id)))
    WHERE c.embarcacao_id IS NULL OR c.cliente_id IS NULL;

    UPDATE certificados_lc c
    JOIN embarcacoes e ON e.id = c.embarcacao_id
    SET c.cliente_id = COALESCE(c.cliente_id, e.proprietario_id)
    WHERE c.cliente_id IS NULL AND c.embarcacao_id IS NOT NULL;

    -- 7.6 CHT
    UPDATE certificados_cht c
    JOIN vistorias v ON v.id = c.vistoria_id
    SET c.embarcacao_id = v.embarcacao_id,
        c.cliente_id = COALESCE(v.armador_id, (SELECT e.proprietario_id FROM embarcacoes e WHERE e.id = v.embarcacao_id))
    WHERE c.embarcacao_id IS NULL AND c.vistoria_id IS NOT NULL;

    UPDATE certificados_cht c
    JOIN clientes cl ON cl.cpf_cnpj = c.cpf_cnpj
    SET c.cliente_id = cl.id
    WHERE c.cliente_id IS NULL AND c.cpf_cnpj IS NOT NULL AND c.cpf_cnpj <> '';

    -- 8. Índices e Foreign Keys (se não existirem)
    -- CSN
    IF NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='certificados_csn' AND index_name='idx_csn_embarcacao') THEN
        CREATE INDEX idx_csn_embarcacao ON certificados_csn (embarcacao_id);
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='certificados_csn' AND index_name='idx_csn_cliente') THEN
        CREATE INDEX idx_csn_cliente ON certificados_csn (cliente_id);
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE table_schema=DATABASE() AND table_name='certificados_csn' AND constraint_name='fk_csn_embarcacao') THEN
        ALTER TABLE certificados_csn ADD CONSTRAINT fk_csn_embarcacao FOREIGN KEY (embarcacao_id) REFERENCES embarcacoes (id) ON UPDATE CASCADE ON DELETE RESTRICT;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE table_schema=DATABASE() AND table_name='certificados_csn' AND constraint_name='fk_csn_cliente') THEN
        ALTER TABLE certificados_csn ADD CONSTRAINT fk_csn_cliente FOREIGN KEY (cliente_id) REFERENCES clientes (id) ON UPDATE CASCADE ON DELETE RESTRICT;
    END IF;

    -- CNBL
    IF NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='certificados_cnbl' AND index_name='idx_cnbl_embarcacao') THEN
        CREATE INDEX idx_cnbl_embarcacao ON certificados_cnbl (embarcacao_id);
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='certificados_cnbl' AND index_name='idx_cnbl_cliente') THEN
        CREATE INDEX idx_cnbl_cliente ON certificados_cnbl (cliente_id);
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE table_schema=DATABASE() AND table_name='certificados_cnbl' AND constraint_name='fk_cnbl_embarcacao') THEN
        ALTER TABLE certificados_cnbl ADD CONSTRAINT fk_cnbl_embarcacao FOREIGN KEY (embarcacao_id) REFERENCES embarcacoes (id) ON UPDATE CASCADE ON DELETE RESTRICT;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE table_schema=DATABASE() AND table_name='certificados_cnbl' AND constraint_name='fk_cnbl_cliente') THEN
        ALTER TABLE certificados_cnbl ADD CONSTRAINT fk_cnbl_cliente FOREIGN KEY (cliente_id) REFERENCES clientes (id) ON UPDATE CASCADE ON DELETE RESTRICT;
    END IF;

    -- CNARQ
    IF NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='certificados_cnarq' AND index_name='idx_cnarq_embarcacao') THEN
        CREATE INDEX idx_cnarq_embarcacao ON certificados_cnarq (embarcacao_id);
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='certificados_cnarq' AND index_name='idx_cnarq_cliente') THEN
        CREATE INDEX idx_cnarq_cliente ON certificados_cnarq (cliente_id);
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE table_schema=DATABASE() AND table_name='certificados_cnarq' AND constraint_name='fk_cnarq_embarcacao') THEN
        ALTER TABLE certificados_cnarq ADD CONSTRAINT fk_cnarq_embarcacao FOREIGN KEY (embarcacao_id) REFERENCES embarcacoes (id) ON UPDATE CASCADE ON DELETE RESTRICT;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE table_schema=DATABASE() AND table_name='certificados_cnarq' AND constraint_name='fk_cnarq_cliente') THEN
        ALTER TABLE certificados_cnarq ADD CONSTRAINT fk_cnarq_cliente FOREIGN KEY (cliente_id) REFERENCES clientes (id) ON UPDATE CASCADE ON DELETE RESTRICT;
    END IF;

    -- LP
    IF NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='certificados_lp' AND index_name='idx_lp_cliente') THEN
        CREATE INDEX idx_lp_cliente ON certificados_lp (cliente_id);
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE table_schema=DATABASE() AND table_name='certificados_lp' AND constraint_name='fk_lp_embarcacao') THEN
        ALTER TABLE certificados_lp ADD CONSTRAINT fk_lp_embarcacao FOREIGN KEY (embarcacao_id) REFERENCES embarcacoes (id) ON UPDATE CASCADE ON DELETE RESTRICT;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE table_schema=DATABASE() AND table_name='certificados_lp' AND constraint_name='fk_lp_cliente') THEN
        ALTER TABLE certificados_lp ADD CONSTRAINT fk_lp_cliente FOREIGN KEY (cliente_id) REFERENCES clientes (id) ON UPDATE CASCADE ON DELETE RESTRICT;
    END IF;

    -- LC
    IF NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='certificados_lc' AND index_name='idx_lc_cliente') THEN
        CREATE INDEX idx_lc_cliente ON certificados_lc (cliente_id);
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE table_schema=DATABASE() AND table_name='certificados_lc' AND constraint_name='fk_lc_embarcacao') THEN
        ALTER TABLE certificados_lc ADD CONSTRAINT fk_lc_embarcacao FOREIGN KEY (embarcacao_id) REFERENCES embarcacoes (id) ON UPDATE CASCADE ON DELETE RESTRICT;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE table_schema=DATABASE() AND table_name='certificados_lc' AND constraint_name='fk_lc_cliente') THEN
        ALTER TABLE certificados_lc ADD CONSTRAINT fk_lc_cliente FOREIGN KEY (cliente_id) REFERENCES clientes (id) ON UPDATE CASCADE ON DELETE RESTRICT;
    END IF;

    -- CHT
    IF NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='certificados_cht' AND index_name='idx_cht_embarcacao') THEN
        CREATE INDEX idx_cht_embarcacao ON certificados_cht (embarcacao_id);
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='certificados_cht' AND index_name='idx_cht_cliente') THEN
        CREATE INDEX idx_cht_cliente ON certificados_cht (cliente_id);
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE table_schema=DATABASE() AND table_name='certificados_cht' AND constraint_name='fk_cht_embarcacao') THEN
        ALTER TABLE certificados_cht ADD CONSTRAINT fk_cht_embarcacao FOREIGN KEY (embarcacao_id) REFERENCES embarcacoes (id) ON UPDATE CASCADE ON DELETE RESTRICT;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE table_schema=DATABASE() AND table_name='certificados_cht' AND constraint_name='fk_cht_cliente') THEN
        ALTER TABLE certificados_cht ADD CONSTRAINT fk_cht_cliente FOREIGN KEY (cliente_id) REFERENCES clientes (id) ON UPDATE CASCADE ON DELETE RESTRICT;
    END IF;

END $$
DELIMITER ;

CALL sp_migration_106_certificados();
DROP PROCEDURE IF EXISTS sp_migration_106_certificados;
