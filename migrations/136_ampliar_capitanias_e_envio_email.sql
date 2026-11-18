-- =============================================================================
-- Migration 136: Ampliação das Unidades Marítimas (Capitanias) e Envio por E-mail
-- =============================================================================

-- 1. Ampliar campos da tabela protocolo_unidades_maritimas
ALTER TABLE protocolo_unidades_maritimas
  ADD COLUMN autoridade_nome VARCHAR(255) NULL AFTER uf,
  ADD COLUMN autoridade_cargo VARCHAR(150) NULL AFTER autoridade_nome,
  ADD COLUMN emails_adicionais TEXT NULL AFTER email,
  ADD COLUMN sigla VARCHAR(50) NULL AFTER codigo;

-- 2. Tabela para registro de envios oficiais por e-mail para órgãos / clientes
CREATE TABLE IF NOT EXISTS protocolo_envios_email (
  id CHAR(36) NOT NULL,
  dossie_id CHAR(36) NOT NULL,
  movimentacao_id CHAR(36) NULL,
  unidade_maritima_id CHAR(36) NULL,
  destinatario_principal VARCHAR(255) NOT NULL,
  destinatarios_copia TEXT NULL,
  assunto VARCHAR(255) NOT NULL,
  mensagem_html MEDIUMTEXT NOT NULL,
  anexos_nomes TEXT NULL,
  status ENUM('ENVIADO', 'FALHA') NOT NULL DEFAULT 'ENVIADO',
  erro_mensagem TEXT NULL,
  enviado_por CHAR(36) NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_envio_dossie (dossie_id),
  KEY idx_envio_unidade (unidade_maritima_id),
  CONSTRAINT fk_envio_dossie FOREIGN KEY (dossie_id) REFERENCES protocolo_dossies(id) ON DELETE CASCADE,
  CONSTRAINT fk_envio_unidade FOREIGN KEY (unidade_maritima_id) REFERENCES protocolo_unidades_maritimas(id) ON DELETE SET NULL,
  CONSTRAINT fk_envio_usuario FOREIGN KEY (enviado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Atualizar/Inserir unidades marítimas da Amazônia com autoridade e e-mails oficiais
INSERT INTO protocolo_unidades_maritimas (id, codigo, sigla, nome, tipo, cidade, uf, autoridade_nome, autoridade_cargo, email, emails_adicionais, ativo)
VALUES 
  ('cpaor-belem-pa-0000-000000000001', 'CPAOR', 'CPAOR', 'Capitania dos Portos da Amazônia Oriental', 'CAPITANIA', 'Belém', 'PA', 'CAPITÃO DE MAR E GUERRA – ALEXANDRE BATISTA PIMENTEL', 'Capitão dos Portos', 'cpaor.protocolo@marinha.mil.br', 'cpaor.gabinete@marinha.mil.br', 1),
  ('cpaoc-manaus-am-000-000000000002', 'CPAOC', 'CPAOC', 'Capitania Fluvial da Amazônia Ocidental', 'CAPITANIA', 'Manaus', 'AM', 'CAPITÃO DE MAR E GUERRA – COMANDANTE DA CPAOC', 'Capitão Fluvial', 'cpaoc.protocolo@marinha.mil.br', 'cpaoc.gabinete@marinha.mil.br', 1),
  ('cpap-santana-ap-000-000000000003', 'CPAP', 'CPAP', 'Capitania dos Portos do Amapá', 'CAPITANIA', 'Santana', 'AP', 'CAPITÃO DE FRAGATA – CAPITÃO DOS PORTOS DO AMAPÁ', 'Capitão dos Portos', 'cpap.protocolo@marinha.mil.br', NULL, 1),
  ('delsantarem-pa-0000-000000000004', 'DelSantarem', 'DelSantarém', 'Delegacia da Capitania dos Portos em Santarém', 'DELEGACIA', 'Santarém', 'PA', 'CAPITÃO DE CORVETA – DELEGADO EM SANTARÉM', 'Delegado da Capitania', 'delsantarem.protocolo@marinha.mil.br', NULL, 1),
  ('delitacoatiara-am-00-000000000005', 'DelItacoatiara', 'DelItacoatiara', 'Delegacia Fluvial de Itacoatiara', 'DELEGACIA', 'Itacoatiara', 'AM', 'CAPITÃO-TENENTE – DELEGADO FLUVIAL', 'Delegado Fluvial', 'delitacoatiara.protocolo@marinha.mil.br', NULL, 1),
  ('agbreves-pa-0000000-000000000006', 'AgBreves', 'AgBreves', 'Agência Fluvial de Breves', 'AGENCIA', 'Breves', 'PA', 'CAPITÃO-TENENTE – AGENTE FLUVIAL DE BREVES', 'Agente Fluvial', 'agbreves.protocolo@marinha.mil.br', NULL, 1)
ON DUPLICATE KEY UPDATE
  nome = VALUES(nome),
  sigla = VALUES(sigla),
  autoridade_nome = VALUES(autoridade_nome),
  autoridade_cargo = VALUES(autoridade_cargo),
  email = VALUES(email),
  emails_adicionais = VALUES(emails_adicionais),
  ativo = 1;
