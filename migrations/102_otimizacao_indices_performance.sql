-- Migration 102: Otimização de Performance e Índices para Operação Leve e Rápida (ISO 9001 / SGQ)

DELIMITER $$
DROP PROCEDURE IF EXISTS sp_migration_102_indices $$
CREATE PROCEDURE sp_migration_102_indices()
BEGIN
    -- Índice composto em usuarios (cargo, ativo) para acelerar filtros de login e vistoriadores
    IF NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='usuarios' AND index_name='idx_usuarios_cargo_ativo') THEN
        ALTER TABLE usuarios ADD INDEX idx_usuarios_cargo_ativo (cargo, ativo);
    END IF;

    -- Índice de qualificação técnica SGQ
    IF NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='usuarios' AND index_name='idx_usuarios_status_sgq') THEN
        ALTER TABLE usuarios ADD INDEX idx_usuarios_status_sgq (status_sgq);
    END IF;

    -- Índice para consultas da trilha de auditoria cadastral por data e entidade
    IF NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='sgq_auditoria_cadastral' AND index_name='idx_auditoria_entidade_data') THEN
        ALTER TABLE sgq_auditoria_cadastral ADD INDEX idx_auditoria_entidade_data (entidade_tipo, criado_em);
    END IF;

    -- Índice composto para consultas da Matriz de Riscos (tipo e nível)
    IF NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='sgq_matriz_riscos' AND index_name='idx_matriz_risco_tipo_nivel') THEN
        ALTER TABLE sgq_matriz_riscos ADD INDEX idx_matriz_risco_tipo_nivel (tipo_risco, nivel_risco);
    END IF;

    -- Índice composto para gestão de Não Conformidades (status e severidade)
    IF NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='sgq_nao_conformidades' AND index_name='idx_rnc_status_severidade') THEN
        ALTER TABLE sgq_nao_conformidades ADD INDEX idx_rnc_status_severidade (status_ciclo_vida, severidade);
    END IF;

    -- Índice para checagem rápida de permissões de usuário
    IF NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='usuario_permissoes' AND index_name='idx_permissoes_modulo') THEN
        ALTER TABLE usuario_permissoes ADD INDEX idx_permissoes_modulo (permissao, permitido);
    END IF;
END $$
DELIMITER ;

CALL sp_migration_102_indices();
DROP PROCEDURE IF EXISTS sp_migration_102_indices;
