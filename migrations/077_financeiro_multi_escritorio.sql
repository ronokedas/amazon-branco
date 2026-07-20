-- Financeiro multi-escritorio, metas mensais e atribuicao comercial.
CREATE TABLE escritorios (
  id CHAR(36) NOT NULL,
  nome VARCHAR(150) NOT NULL,
  cidade VARCHAR(150) NOT NULL,
  uf CHAR(2) NOT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_escritorios_nome_cidade (nome, cidade, uf),
  KEY idx_escritorios_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO escritorios (id, nome, cidade, uf, ativo)
VALUES ('00000000-0000-4000-8000-000000000100', 'Matriz', 'Manaus', 'AM', 1);

ALTER TABLE usuarios
  ADD COLUMN escritorio_id CHAR(36) NULL DEFAULT '00000000-0000-4000-8000-000000000100' AFTER acesso_financeiro,
  ADD KEY idx_usuarios_escritorio (escritorio_id),
  ADD CONSTRAINT fk_usuarios_escritorio FOREIGN KEY (escritorio_id) REFERENCES escritorios(id) ON DELETE SET NULL;

ALTER TABLE propostas
  ADD COLUMN escritorio_id CHAR(36) NULL DEFAULT '00000000-0000-4000-8000-000000000100' AFTER criado_por,
  ADD KEY idx_propostas_escritorio (escritorio_id),
  ADD CONSTRAINT fk_propostas_escritorio FOREIGN KEY (escritorio_id) REFERENCES escritorios(id) ON DELETE RESTRICT;

ALTER TABLE financeiro_lancamentos
  ADD COLUMN escritorio_id CHAR(36) NULL DEFAULT '00000000-0000-4000-8000-000000000100' AFTER cliente_id,
  ADD COLUMN responsavel_usuario_id CHAR(36) NULL AFTER escritorio_id,
  ADD COLUMN proposta_id CHAR(36) NULL AFTER responsavel_usuario_id,
  ADD KEY idx_financeiro_escritorio_data (escritorio_id, data),
  ADD KEY idx_financeiro_responsavel (responsavel_usuario_id),
  ADD KEY idx_financeiro_proposta (proposta_id),
  ADD CONSTRAINT fk_financeiro_escritorio FOREIGN KEY (escritorio_id) REFERENCES escritorios(id) ON DELETE RESTRICT,
  ADD CONSTRAINT fk_financeiro_responsavel FOREIGN KEY (responsavel_usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
  ADD CONSTRAINT fk_financeiro_proposta FOREIGN KEY (proposta_id) REFERENCES propostas(id) ON DELETE SET NULL;

UPDATE usuarios SET escritorio_id = '00000000-0000-4000-8000-000000000100' WHERE escritorio_id IS NULL;
UPDATE propostas SET escritorio_id = '00000000-0000-4000-8000-000000000100' WHERE escritorio_id IS NULL;
UPDATE financeiro_lancamentos l
LEFT JOIN usuarios u ON u.id = l.criado_por AND u.cargo = 'VENDEDOR'
SET l.escritorio_id = '00000000-0000-4000-8000-000000000100',
    l.responsavel_usuario_id = u.id
WHERE l.escritorio_id IS NULL;

ALTER TABLE propostas DROP FOREIGN KEY fk_propostas_escritorio;
ALTER TABLE propostas MODIFY COLUMN escritorio_id CHAR(36) NOT NULL DEFAULT '00000000-0000-4000-8000-000000000100';
ALTER TABLE propostas ADD CONSTRAINT fk_propostas_escritorio FOREIGN KEY (escritorio_id) REFERENCES escritorios(id) ON DELETE RESTRICT;
ALTER TABLE financeiro_lancamentos DROP FOREIGN KEY fk_financeiro_escritorio;
ALTER TABLE financeiro_lancamentos MODIFY COLUMN escritorio_id CHAR(36) NOT NULL DEFAULT '00000000-0000-4000-8000-000000000100';
ALTER TABLE financeiro_lancamentos ADD CONSTRAINT fk_financeiro_escritorio FOREIGN KEY (escritorio_id) REFERENCES escritorios(id) ON DELETE RESTRICT;

CREATE TABLE financeiro_metas_mensais (
  id CHAR(36) NOT NULL,
  competencia DATE NOT NULL,
  escritorio_id CHAR(36) NOT NULL,
  usuario_id CHAR(36) NULL,
  valor DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_meta_escritorio_usuario_competencia (escritorio_id, usuario_id, competencia),
  KEY idx_metas_competencia (competencia),
  KEY idx_metas_usuario (usuario_id),
  CONSTRAINT fk_metas_escritorio FOREIGN KEY (escritorio_id) REFERENCES escritorios(id) ON DELETE CASCADE,
  CONSTRAINT fk_metas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT chk_metas_valor CHECK (valor >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
