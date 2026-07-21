-- Permite vincular um usuario a um ou mais escritorios.
-- usuarios.escritorio_id continua representando o escritorio principal.
CREATE TABLE IF NOT EXISTS usuario_escritorios (
  usuario_id CHAR(36) NOT NULL,
  escritorio_id CHAR(36) NOT NULL,
  principal TINYINT(1) NOT NULL DEFAULT 0,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (usuario_id, escritorio_id),
  KEY idx_usuario_escritorios_escritorio (escritorio_id),
  KEY idx_usuario_escritorios_principal (usuario_id, principal),
  CONSTRAINT fk_usuario_escritorios_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_usuario_escritorios_escritorio FOREIGN KEY (escritorio_id) REFERENCES escritorios(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO usuario_escritorios (usuario_id, escritorio_id, principal)
SELECT id, escritorio_id, 1
FROM usuarios
WHERE escritorio_id IS NOT NULL AND excluido_em IS NULL;
