-- Migration 130: Padronização de códigos operacionais de serviços e inserção do Acompanhamento Técnico
-- Suporte aos Pacotes Comerciais NORMAM: Balsa (Sem propulsão) e Propulsada (Com propulsão)

-- 1. Padronizar códigos operacionais dos serviços que compõem os pacotes
UPDATE servicos 
SET codigo_operacional = 'VISTORIA_INICIAL_SECO' 
WHERE (id = 'a1d98d8e-6ebc-11f1-86ce-7e17ff5f90bf' OR nome LIKE '%Vistoria Inicial Seco%')
  AND (codigo_operacional IS NULL OR codigo_operacional = '');

UPDATE servicos 
SET codigo_operacional = 'VISTORIA_INICIAL_FLUTUANDO' 
WHERE (id = 'a1d98e55-6ebc-11f1-86ce-7e17ff5f90bf' OR nome LIKE '%Vistoria Inicial Flutuando%')
  AND (codigo_operacional IS NULL OR codigo_operacional = '');

UPDATE servicos 
SET codigo_operacional = 'VISTORIA_INICIAL_BORDA_LIVRE' 
WHERE (id = 'a1d98eaf-6ebc-11f1-86ce-7e17ff5f90bf' OR nome LIKE '%Borda Livre%')
  AND (codigo_operacional IS NULL OR codigo_operacional = '');

UPDATE servicos 
SET codigo_operacional = 'VISTORIA_INICIAL_ARQUEACAO' 
WHERE (id = 'a1d98ef1-6ebc-11f1-86ce-7e17ff5f90bf' OR nome LIKE '%Arquea%')
  AND (codigo_operacional IS NULL OR codigo_operacional = '');

-- 2. Inserir o serviço Acompanhamento Técnico (R$ 500,00) caso ainda não exista
INSERT INTO servicos (id, nome, descricao, codigo_operacional, preco_padrao, ativo, created_at)
SELECT 'a1d99333-6ebc-11f1-86ce-7e17ff5f90bf', 'Acompanhamento Técnico', 'Acompanhamento técnico presencial de serviços e vistorias navais', 'ACOMPANHAMENTO_TECNICO', 500.00, 1, NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM servicos WHERE codigo_operacional = 'ACOMPANHAMENTO_TECNICO' OR nome = 'Acompanhamento Técnico'
);

