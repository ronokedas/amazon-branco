-- Permite habilitar comunicação lateral entre todos os usuários por cargo.
ALTER TABLE feedback_regras_comunicacao
    MODIFY COLUMN escopo ENUM('ADMIN','GESTOR_DIRETO','SUBORDINADOS','OUTROS_GESTORES','CARGO','TODOS_USUARIOS') NOT NULL;

INSERT INTO feedback_regras_comunicacao (id, cargo_origem, escopo, cargo_destino, ativo)
SELECT UUID(), cargo, 'TODOS_USUARIOS', NULL, 1
FROM (
    SELECT 'VENDEDOR' cargo UNION ALL
    SELECT 'VISTORIADOR' UNION ALL
    SELECT 'ANALISTA'
) cargos
ON DUPLICATE KEY UPDATE ativo=1, atualizado_em=CURRENT_TIMESTAMP;

-- Rollback manual:
-- DELETE FROM feedback_regras_comunicacao WHERE escopo='TODOS_USUARIOS';
-- ALTER TABLE feedback_regras_comunicacao MODIFY COLUMN escopo ENUM('ADMIN','GESTOR_DIRETO','SUBORDINADOS','OUTROS_GESTORES','CARGO') NOT NULL;
