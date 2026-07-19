-- Número do motor usado no quadro de propulsão do Certificado de Segurança da Navegação.
ALTER TABLE embarcacoes
    ADD COLUMN numero_motor VARCHAR(100) NULL
    AFTER fabricante_motor;
