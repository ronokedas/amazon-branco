-- Pagamentos/recebimentos parciais com trilha de auditoria.
ALTER TABLE financeiro_lancamentos
  MODIFY COLUMN status ENUM('PENDENTE', 'PARCIAL', 'PAGO', 'CANCELADO') NOT NULL DEFAULT 'PENDENTE',
  ADD COLUMN valor_original DECIMAL(10,2) NULL AFTER valor,
  ADD COLUMN saldo_devedor DECIMAL(10,2) NULL AFTER valor_original;

UPDATE financeiro_lancamentos
SET valor_original = valor,
    saldo_devedor = CASE
      WHEN status = 'PAGO' THEN 0.00
      WHEN status = 'CANCELADO' THEN 0.00
      ELSE valor
    END
WHERE valor_original IS NULL OR saldo_devedor IS NULL;

ALTER TABLE financeiro_lancamentos
  MODIFY COLUMN valor_original DECIMAL(10,2) NOT NULL,
  MODIFY COLUMN saldo_devedor DECIMAL(10,2) NOT NULL;

CREATE TABLE financeiro_contas_bancarias (
  id CHAR(36) NOT NULL,
  nome VARCHAR(120) NOT NULL,
  banco VARCHAR(100) NULL,
  agencia VARCHAR(30) NULL,
  conta VARCHAR(30) NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_financeiro_contas_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO financeiro_contas_bancarias (id, nome, banco, agencia, conta)
VALUES ('00000000-0000-4000-8000-000000000001', 'Conta principal', 'Banco do Brasil', '0000-0', '00000-0');

CREATE TABLE financeiro_historico_baixas (
  id CHAR(36) NOT NULL,
  lancamento_id CHAR(36) NOT NULL,
  valor_pago DECIMAL(10,2) NOT NULL,
  data_pagamento DATE NOT NULL,
  conta_bancaria_id CHAR(36) NOT NULL,
  criado_por CHAR(36) NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_financeiro_baixas_lancamento (lancamento_id),
  KEY idx_financeiro_baixas_data (data_pagamento),
  KEY idx_financeiro_baixas_conta (conta_bancaria_id),
  CONSTRAINT fk_financeiro_baixas_lancamento
    FOREIGN KEY (lancamento_id) REFERENCES financeiro_lancamentos(id) ON DELETE RESTRICT,
  CONSTRAINT fk_financeiro_baixas_conta
    FOREIGN KEY (conta_bancaria_id) REFERENCES financeiro_contas_bancarias(id) ON DELETE RESTRICT,
  CONSTRAINT fk_financeiro_baixas_usuario
    FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
  CONSTRAINT chk_financeiro_baixas_valor CHECK (valor_pago > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
