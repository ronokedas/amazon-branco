-- Migration 120: Suporte ampliado a modelos de certificados e licenças estatutárias nos serviços (MySQL 8.0)
-- Permite vincular serviços a CSN, CNBL, CNARQ, LP, LC, CHT e NAR sem erro de truncamento de ENUM
ALTER TABLE servicos MODIFY COLUMN certificado_modelo VARCHAR(20) NULL;
