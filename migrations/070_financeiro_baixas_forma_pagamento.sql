-- Permite registrar a forma de pagamento usada em cada baixa.
ALTER TABLE financeiro_historico_baixas
  MODIFY COLUMN conta_bancaria_id CHAR(36) NULL,
  ADD COLUMN forma_pagamento ENUM('a_vista', 'parcelado', 'boleto', 'pix') NULL AFTER data_pagamento;

