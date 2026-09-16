-- Migration 112: Sincronizar entradas pagas à vista de propostas no financeiro
-- Garante que valores de entrada gerados anteriormente entrem como receita e abatam o saldo devedor

-- 1. Inserir baixas para entradas de propostas que ainda não possuíam registro em financeiro_historico_baixas
INSERT INTO financeiro_historico_baixas (id, lancamento_id, valor_pago, data_pagamento, forma_pagamento, criado_por)
SELECT 
    UUID(),
    fl.id,
    ROUND(LEAST(fl.valor_original, p.valor_entrada), 2),
    COALESCE(fl.data, CURDATE()),
    CASE 
        WHEN p.forma_pagamento IN ('a_vista', 'parcelado', 'boleto', 'pix') THEN p.forma_pagamento 
        ELSE 'a_vista' 
    END,
    COALESCE(fl.criado_por, p.criado_por)
FROM financeiro_lancamentos fl
JOIN propostas p ON p.id = fl.proposta_id
WHERE fl.ativo = 1
  AND fl.tipo = 'RECEITA'
  AND p.valor_entrada > 0
  AND NOT EXISTS (
      SELECT 1 FROM financeiro_historico_baixas bx WHERE bx.lancamento_id = fl.id
  );

-- 2. Atualizar saldo_devedor e status dos lançamentos correspondentes
UPDATE financeiro_lancamentos fl
JOIN propostas p ON p.id = fl.proposta_id
SET 
    fl.saldo_devedor = ROUND(GREATEST(0, fl.valor_original - p.valor_entrada), 2),
    fl.status = CASE 
        WHEN p.valor_entrada >= fl.valor_original THEN 'PAGO' 
        ELSE 'PARCIAL' 
    END,
    fl.observacoes = CONCAT(COALESCE(fl.observacoes, ''), ' [Entrada à vista regularizada]')
WHERE fl.ativo = 1
  AND fl.tipo = 'RECEITA'
  AND p.valor_entrada > 0
  AND fl.status = 'PENDENTE';
