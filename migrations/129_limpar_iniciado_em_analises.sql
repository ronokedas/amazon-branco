-- Migration 129: Garantir que analises ja iniciadas ou concluidas possuam iniciado_em preenchido
-- Impede que processos em andamento exibam indevidamente botoes de inicio inicial

UPDATE analises_planos 
SET iniciado_em = COALESCE(iniciado_em, criado_em, NOW()) 
WHERE status IN ('EM_ANALISE', 'AGUARDANDO_DOCUMENTOS', 'AGUARDANDO_ASSINATURA_ANALISTA', 'AGUARDANDO_APROVACAO_ADMIN', 'CONCLUIDA') 
  AND iniciado_em IS NULL;
