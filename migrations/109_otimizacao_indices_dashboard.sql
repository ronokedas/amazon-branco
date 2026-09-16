-- Migration 109: Otimização de Índices Compostos para Dashboard e Relatórios Navais de Alta Performance

DELIMITER $$
DROP PROCEDURE IF EXISTS sp_migration_109_indices $$
CREATE PROCEDURE sp_migration_109_indices()
BEGIN
    -- 1. Financeiro: Aceleração de metas e faturamento do mês/anterior
    IF NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='financeiro_lancamentos' AND index_name='idx_fin_ativo_tipo_status_data') THEN
        ALTER TABLE financeiro_lancamentos ADD INDEX idx_fin_ativo_tipo_status_data (ativo, tipo, status, data, valor);
    END IF;

    -- 2. Vistorias: Aceleração de contadores de vistorias do mês e finalizadas
    IF NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='vistorias' AND index_name='idx_vistorias_status_data') THEN
        ALTER TABLE vistorias ADD INDEX idx_vistorias_status_data (status, data_vistoria);
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='vistorias' AND index_name='idx_vistorias_status_aprovacao') THEN
        ALTER TABLE vistorias ADD INDEX idx_vistorias_status_aprovacao (status, data_aprovacao);
    END IF;

    -- 3. Agendamentos: Aceleração da agenda diária e agendamentos pendentes
    IF NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='agendamentos' AND index_name='idx_agendamentos_status_data') THEN
        ALTER TABLE agendamentos ADD INDEX idx_agendamentos_status_data (status, data_vistoria);
    END IF;

    -- 4. Exigências de Vistoria: Aceleração de alertas de exigências vencidas
    IF NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='vistoria_exigencias' AND index_name='idx_exigencias_status_vencimento') THEN
        ALTER TABLE vistoria_exigencias ADD INDEX idx_exigencias_status_vencimento (status_item, vencimento);
    END IF;

    -- 5. Certificados: Aceleração das consultas estatutárias consolidadas (UNION de 6 modelos)
    IF NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='certificados_csn' AND index_name='idx_csn_ativo_status_emissao') THEN
        ALTER TABLE certificados_csn ADD INDEX idx_csn_ativo_status_emissao (ativo, status, data_emissao);
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='certificados_cnbl' AND index_name='idx_cnbl_ativo_status_emissao') THEN
        ALTER TABLE certificados_cnbl ADD INDEX idx_cnbl_ativo_status_emissao (ativo, status, data_emissao);
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='certificados_cnarq' AND index_name='idx_cnarq_ativo_status_emissao') THEN
        ALTER TABLE certificados_cnarq ADD INDEX idx_cnarq_ativo_status_emissao (ativo, status, data_emissao);
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='certificados_cht' AND index_name='idx_cht_ativo_status_emissao') THEN
        ALTER TABLE certificados_cht ADD INDEX idx_cht_ativo_status_emissao (ativo, status, data_emissao);
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='certificados_lc' AND index_name='idx_lc_ativo_status_emissao') THEN
        ALTER TABLE certificados_lc ADD INDEX idx_lc_ativo_status_emissao (ativo, status, data_emissao);
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='certificados_lp' AND index_name='idx_lp_ativo_status_emissao') THEN
        ALTER TABLE certificados_lp ADD INDEX idx_lp_ativo_status_emissao (ativo, status, data_emissao);
    END IF;

    -- 6. Análise de Planos: Aceleração de processos por analista e prazos agendados
    IF NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='analises_planos' AND index_name='idx_analises_planos_analista_status') THEN
        ALTER TABLE analises_planos ADD INDEX idx_analises_planos_analista_status (analista_id, status);
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='analises_planos' AND index_name='idx_analises_planos_status_prazo') THEN
        ALTER TABLE analises_planos ADD INDEX idx_analises_planos_status_prazo (status, prazo_agendado_em);
    END IF;

END $$
DELIMITER ;

CALL sp_migration_109_indices();
DROP PROCEDURE IF EXISTS sp_migration_109_indices;
