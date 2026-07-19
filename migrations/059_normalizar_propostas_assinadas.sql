-- Normaliza propostas antigas em que a assinatura foi registrada, mas o status
-- permaneceu como "aprovada". O aceite assinado é o estado comercial final.

UPDATE propostas
SET status = 'assinada'
WHERE assinado = 1
  AND status <> 'assinada';

-- Impede que futuras gravações voltem a separar o indicador de assinatura do
-- status comercial correspondente.
ALTER TABLE propostas
    ADD CONSTRAINT chk_propostas_assinatura_status
    CHECK (assinado = 0 OR status = 'assinada');
