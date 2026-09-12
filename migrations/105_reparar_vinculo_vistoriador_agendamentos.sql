-- Migration 105: Reparar e Associar Agendamentos ao Vistoriador Ativo
-- 1. Garantir que o usuário teste1@teste.com esteja ativo, qualificado e com cargo VISTORIADOR
UPDATE usuarios
SET ativo = 1, status_sgq = 'QUALIFICADO', cargo = 'VISTORIADOR'
WHERE email = 'teste1@teste.com';

-- 2. Vincular perfil VISTORIADOR na tabela usuario_perfis
INSERT IGNORE INTO usuario_perfis (usuario_id, perfil)
SELECT id, 'VISTORIADOR' FROM usuarios WHERE email = 'teste1@teste.com';

-- 3. Garantir permissões operacionais do vistoriador
INSERT INTO usuario_permissoes (usuario_id, permissao, permitido, atualizado_em)
SELECT u.id, p.permissao, 1, NOW()
FROM usuarios u
CROSS JOIN (
    SELECT 'dashboard' AS permissao 
    UNION SELECT 'vistorias' 
    UNION SELECT 'agendamentos' 
    UNION SELECT 'embarcacoes' 
    UNION SELECT 'certificados' 
    UNION SELECT 'documentacao'
) p
WHERE u.email = 'teste1@teste.com'
ON DUPLICATE KEY UPDATE permitido = 1, atualizado_em = NOW();

-- 4. Associar todos os agendamentos ao usuário teste1@teste.com (usando o id real da VPS)
UPDATE agendamentos a
SET a.vistoriador_id = (SELECT id FROM usuarios WHERE email = 'teste1@teste.com' LIMIT 1)
WHERE (
    a.vistoriador_id IS NULL 
    OR a.vistoriador_id = ''
    OR a.vistoriador_id = 'd2a16613-dfa4-4948-8de4-8c802abdf394'
    OR a.vistoriador_id NOT IN (SELECT id FROM usuarios)
)
AND EXISTS (SELECT 1 FROM usuarios WHERE email = 'teste1@teste.com');

-- 5. Vincular as vistorias existentes ao mesmo vistoriador
UPDATE vistorias v
SET v.criado_por = (SELECT id FROM usuarios WHERE email = 'teste1@teste.com' LIMIT 1)
WHERE (
    v.criado_por IS NULL 
    OR v.criado_por = ''
    OR v.criado_por = 'd2a16613-dfa4-4948-8de4-8c802abdf394'
    OR v.criado_por NOT IN (SELECT id FROM usuarios)
)
AND EXISTS (SELECT 1 FROM usuarios WHERE email = 'teste1@teste.com');
