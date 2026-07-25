ALTER TABLE servicos
    ADD COLUMN certificado_modelo ENUM('CSN', 'CNBL', 'CNARQ') NULL AFTER descricao,
    ADD INDEX idx_servicos_certificado_modelo (certificado_modelo);

UPDATE servicos
SET certificado_modelo = 'CSN'
WHERE nome IN ('Vistoria Inicial Seco', 'Vistoria Inicial Flutuando');

UPDATE servicos
SET certificado_modelo = 'CNBL'
WHERE nome = 'Vistoria Inicial de Borda Livre';

UPDATE servicos
SET certificado_modelo = 'CNARQ'
WHERE id = 'a1d98ef1-6ebc-11f1-86ce-7e17ff5f90bf'
   OR nome = 'Vistoria Inicial de Arqueação';
