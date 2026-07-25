-- Amazon Naval: escopo exclusivo NORMAM-202 e saneamento das cadeias A/S.

CREATE TABLE IF NOT EXISTS matriz_normativa_versoes (
  id char(36) NOT NULL,
  norma_codigo varchar(30) NOT NULL,
  revisao varchar(30) NOT NULL,
  vigencia_inicio date NOT NULL,
  vigencia_fim date NULL,
  portaria_reconhecimento varchar(180) NULL,
  fonte_url varchar(500) NOT NULL,
  ativa tinyint(1) NOT NULL DEFAULT 1,
  criado_em datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_matriz_norma_revisao (norma_codigo,revisao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS matriz_normativa_documentos (
  id char(36) NOT NULL,
  versao_id char(36) NOT NULL,
  documento enum('LC','LCEC','LA','LR','CSN','CNBL','CNARQ') NOT NULL,
  classe enum('EC1','EC2') NOT NULL,
  aplicavel tinyint(1) NOT NULL,
  vigencia_inicio date NULL,
  condicao_json json NULL,
  fundamento varchar(500) NOT NULL,
  criado_em datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_matriz_documento (versao_id,documento,classe),
  CONSTRAINT fk_matriz_documento_versao FOREIGN KEY (versao_id)
    REFERENCES matriz_normativa_versoes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO matriz_normativa_versoes
  (id,norma_codigo,revisao,vigencia_inicio,portaria_reconhecimento,fonte_url)
VALUES
  ('b6db69f4-69dd-4ad4-a7cb-202000000001','NORMAM-202','REV.1','2025-01-01',
   'Escopo condicionado à Portaria/Acordo de Reconhecimento vigente',
   'https://www.marinha.mil.br/sites/default/files/atos-normativos/dpc/normam/normam-202.pdf');

INSERT IGNORE INTO matriz_normativa_documentos
  (id,versao_id,documento,classe,aplicavel,vigencia_inicio,condicao_json,fundamento)
VALUES
  (UUID(),'b6db69f4-69dd-4ad4-a7cb-202000000001','LC','EC1',1,NULL,NULL,'NORMAM-202, Capítulo 3'),
  (UUID(),'b6db69f4-69dd-4ad4-a7cb-202000000001','LCEC','EC1',1,NULL,NULL,'NORMAM-202, Capítulo 3'),
  (UUID(),'b6db69f4-69dd-4ad4-a7cb-202000000001','LA','EC1',1,NULL,NULL,'NORMAM-202, Capítulo 3'),
  (UUID(),'b6db69f4-69dd-4ad4-a7cb-202000000001','LR','EC1',1,NULL,NULL,'NORMAM-202, Capítulo 3'),
  (UUID(),'b6db69f4-69dd-4ad4-a7cb-202000000001','LC','EC2',0,'2026-11-01',
   JSON_OBJECT('excecao','REBOCADOR_OU_EMPURRADOR','ab_min',20,'ab_max',50),
   'EC2 dispensada; exceção de rebocador/empurrador AB 20 a 50 a partir de 01/11/2026'),
  (UUID(),'b6db69f4-69dd-4ad4-a7cb-202000000001','LCEC','EC2',0,'2026-11-01',
   JSON_OBJECT('excecao','REBOCADOR_OU_EMPURRADOR','ab_min',20,'ab_max',50),
   'EC2 dispensada; exceção de rebocador/empurrador AB 20 a 50 a partir de 01/11/2026'),
  (UUID(),'b6db69f4-69dd-4ad4-a7cb-202000000001','LA','EC2',0,'2026-11-01',
   JSON_OBJECT('excecao','REBOCADOR_OU_EMPURRADOR','ab_min',20,'ab_max',50),
   'EC2 dispensada; exceção de rebocador/empurrador AB 20 a 50 a partir de 01/11/2026'),
  (UUID(),'b6db69f4-69dd-4ad4-a7cb-202000000001','LR','EC2',0,'2026-11-01',
   JSON_OBJECT('excecao','REBOCADOR_OU_EMPURRADOR','ab_min',20,'ab_max',50),
   'EC2 dispensada; exceção de rebocador/empurrador AB 20 a 50 a partir de 01/11/2026');

ALTER TABLE analises_planos
  ADD COLUMN norma_versao_id char(36) NULL AFTER enquadramento,
  ADD COLUMN legado_fora_escopo tinyint(1) NOT NULL DEFAULT 0 AFTER legado_sem_proposta,
  ADD COLUMN fundamento_bloqueio varchar(500) NULL AFTER legado_fora_escopo,
  ADD KEY idx_analise_norma_legado (enquadramento,legado_fora_escopo),
  ADD CONSTRAINT fk_analise_norma_versao FOREIGN KEY (norma_versao_id)
    REFERENCES matriz_normativa_versoes(id) ON DELETE RESTRICT;

UPDATE analises_planos
SET legado_fora_escopo=1,
    fundamento_bloqueio='LEGADO_FORA_ESCOPO: Amazon Naval opera exclusivamente pela NORMAM-202.'
WHERE enquadramento='NORMAM-201';

UPDATE analises_planos
SET norma_versao_id='b6db69f4-69dd-4ad4-a7cb-202000000001'
WHERE enquadramento='NORMAM-202' AND norma_versao_id IS NULL;

-- Todo descendente é relatório de cumprimento, mesmo quando pertence a outro agendamento.
UPDATE vistorias
SET finalidade='CUMPRIMENTO_EXIGENCIAS'
WHERE relatorio_anterior_id IS NOT NULL
  AND finalidade<>'CUMPRIMENTO_EXIGENCIAS';

CREATE TABLE IF NOT EXISTS auditoria_fluxo_normativo (
  id bigint unsigned NOT NULL AUTO_INCREMENT,
  entidade varchar(50) NOT NULL,
  entidade_id char(36) NOT NULL,
  evento varchar(80) NOT NULL,
  usuario_id char(36) NULL,
  perfil varchar(40) NULL,
  ip varchar(45) NULL,
  estado_anterior varchar(80) NULL,
  estado_novo varchar(80) NULL,
  norma_versao_id char(36) NULL,
  fundamento text NULL,
  criado_em datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_auditoria_entidade (entidade,entidade_id,criado_em),
  CONSTRAINT fk_auditoria_norma_versao FOREIGN KEY (norma_versao_id)
    REFERENCES matriz_normativa_versoes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
