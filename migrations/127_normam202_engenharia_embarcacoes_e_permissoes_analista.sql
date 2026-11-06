-- Migração 127: Adequação NORMAM-202 / Anexo 7-B - Engenharia Naval e Acesso do Analista a Embarcações

ALTER TABLE embarcacoes
    ADD COLUMN IF NOT EXISTS comprimento_regra DECIMAL(10,2) NULL AFTER pontal_moldado,
    ADD COLUMN IF NOT EXISTS contorno_co DECIMAL(10,2) NULL AFTER comprimento_regra,
    ADD COLUMN IF NOT EXISTS comprimento_arqueacao_ca DECIMAL(10,2) NULL AFTER contorno_co,
    ADD COLUMN IF NOT EXISTS pontal_arqueacao_h DECIMAL(10,2) NULL AFTER comprimento_arqueacao_ca,
    ADD COLUMN IF NOT EXISTS pontal_arqueacao_hc DECIMAL(10,2) NULL AFTER pontal_arqueacao_h,
    ADD COLUMN IF NOT EXISTS pontal_arqueacao_hl DECIMAL(10,2) NULL AFTER pontal_arqueacao_hc,
    ADD COLUMN IF NOT EXISTS data_batimento_quilha DATE NULL AFTER pontal_arqueacao_hl,
    ADD COLUMN IF NOT EXISTS calado_leve_av DECIMAL(10,2) NULL AFTER data_batimento_quilha,
    ADD COLUMN IF NOT EXISTS calado_leve_ar DECIMAL(10,2) NULL AFTER calado_leve_av,
    ADD COLUMN IF NOT EXISTS calado_leve_med DECIMAL(10,2) NULL AFTER calado_leve_ar,
    ADD COLUMN IF NOT EXISTS calado_carregado_av DECIMAL(10,2) NULL AFTER calado_leve_med,
    ADD COLUMN IF NOT EXISTS calado_carregado_ar DECIMAL(10,2) NULL AFTER calado_carregado_av,
    ADD COLUMN IF NOT EXISTS calado_carregado_med DECIMAL(10,2) NULL AFTER calado_carregado_ar,
    ADD COLUMN IF NOT EXISTS deslocamento_leve DECIMAL(10,2) NULL AFTER calado_carregado_med,
    ADD COLUMN IF NOT EXISTS deslocamento_carregado DECIMAL(10,2) NULL AFTER deslocamento_leve;

-- Atualizar permissões de analistas existentes para permitir edição de embarcações (dados de engenharia e arqueação)
UPDATE usuario_permissoes up
JOIN usuarios u ON u.id = up.usuario_id
SET up.embarcacoes = 1
WHERE u.cargo = 'ANALISTA';
