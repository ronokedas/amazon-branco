CREATE TABLE protocolo_configuracoes (
  chave varchar(80) NOT NULL,
  valor varchar(255) NOT NULL,
  descricao varchar(255) NULL,
  atualizado_por char(36) NULL,
  atualizado_em datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (chave),
  CONSTRAINT fk_protocolo_config_usuario FOREIGN KEY (atualizado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO protocolo_configuracoes(chave,valor,descricao) VALUES
('dias_sem_comprovante','3','Dias após uma saída para alertar falta de comprovante'),
('dias_sem_protocolo_oficial','3','Dias após envio à Marinha para alertar falta de SISAP/protocolo'),
('dias_alerta_validade','15','Antecedência para alertar validade do protocolo')
ON DUPLICATE KEY UPDATE descricao=VALUES(descricao);
