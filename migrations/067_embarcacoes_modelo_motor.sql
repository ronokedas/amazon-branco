ALTER TABLE embarcacoes
    ADD COLUMN modelo_motor VARCHAR(150) NULL
    AFTER fabricante_motor;
