-- Migration 122: Sincronizar vínculos de embarcações e proprietários na tabela mestra clientes_embarcacoes
-- Garante que embarcações vinculadas ao cliente/proprietário constem ativas imediatamente em clientes_embarcacoes.

-- 1. Limpar vinculo_ativo_chave de vínculos inativos residuais para prevenir colisão de chave única uk_cliente_embarcacao_ativa
UPDATE clientes_embarcacoes 
SET vinculo_ativo_chave = NULL 
WHERE status = 'INATIVO' AND vinculo_ativo_chave IS NOT NULL;

-- 2. Inserir vínculo ativo para todas as embarcações com proprietário definido que não possuam linha ativa em clientes_embarcacoes
INSERT INTO clientes_embarcacoes (id, cliente_id, embarcacao_id, status, vinculado_em, vinculo_ativo_chave, criado_em)
SELECT 
    UUID() AS id,
    COALESCE(e.proprietario_id, e.cliente_id) AS cliente_id,
    e.id AS embarcacao_id,
    'ATIVO' AS status,
    NOW() AS vinculado_em,
    CONCAT(COALESCE(e.proprietario_id, e.cliente_id), ':', e.id) AS vinculo_ativo_chave,
    NOW() AS criado_em
FROM embarcacoes e
INNER JOIN clientes c ON c.id = COALESCE(e.proprietario_id, e.cliente_id)
LEFT JOIN clientes_embarcacoes ce 
    ON ce.embarcacao_id = e.id 
   AND ce.cliente_id = COALESCE(e.proprietario_id, e.cliente_id)
   AND ce.status = 'ATIVO'
WHERE (e.proprietario_id IS NOT NULL OR e.cliente_id IS NOT NULL)
  AND (e.ativo = 1 OR e.ativo IS NULL)
  AND e.excluido_em IS NULL
  AND ce.id IS NULL
ON DUPLICATE KEY UPDATE 
    status = 'ATIVO',
    vinculo_ativo_chave = VALUES(vinculo_ativo_chave),
    desvinculado_em = NULL,
    desvinculado_por = NULL;

-- 3. Atualizar nome e ID do proprietário em embarcacoes a partir dos vínculos ativos
UPDATE embarcacoes e
INNER JOIN clientes_embarcacoes ce ON ce.embarcacao_id = e.id AND ce.status = 'ATIVO'
INNER JOIN clientes c ON c.id = ce.cliente_id
SET e.proprietario_id = ce.cliente_id,
    e.cliente_id = ce.cliente_id,
    e.proprietario = c.nome
WHERE e.proprietario_id IS NULL OR e.proprietario IS NULL;
