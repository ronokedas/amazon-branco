-- Relatórios imutáveis por ciclo e baixa comprovada de exigências.

ALTER TABLE analise_planos_pareceres
  ADD COLUMN numero varchar(40) NULL AFTER id,
  ADD COLUMN finalidade enum('ANALISE_INICIAL','CUMPRIMENTO_EXIGENCIAS','CONCLUSIVO') NULL AFTER versao,
  ADD COLUMN submissao_id char(36) NULL AFTER finalidade,
  ADD COLUMN relatorio_anterior_id char(36) NULL AFTER submissao_id,
  ADD COLUMN norma_versao_id char(36) NULL AFTER relatorio_anterior_id,
  ADD COLUMN snapshot_json json NULL AFTER conclusao,
  ADD COLUMN validado_por char(36) NULL AFTER publicado_em,
  ADD COLUMN validado_em datetime NULL AFTER validado_por,
  ADD UNIQUE KEY uk_relatorio_analise_numero (numero),
  ADD KEY idx_relatorio_analise_cadeia (analise_id,relatorio_anterior_id),
  ADD KEY idx_relatorio_analise_submissao (submissao_id),
  ADD CONSTRAINT fk_relatorio_analise_submissao FOREIGN KEY (submissao_id)
    REFERENCES analise_planos_submissoes(id) ON DELETE RESTRICT,
  ADD CONSTRAINT fk_relatorio_analise_anterior FOREIGN KEY (relatorio_anterior_id)
    REFERENCES analise_planos_pareceres(id) ON DELETE RESTRICT,
  ADD CONSTRAINT fk_relatorio_analise_norma FOREIGN KEY (norma_versao_id)
    REFERENCES matriz_normativa_versoes(id) ON DELETE RESTRICT,
  ADD CONSTRAINT fk_relatorio_analise_validador FOREIGN KEY (validado_por)
    REFERENCES usuarios(id) ON DELETE SET NULL;

CREATE TABLE analise_planos_relatorio_exigencias (
  id char(36) NOT NULL,
  relatorio_id char(36) NOT NULL,
  exigencia_id char(36) NOT NULL,
  submissao_id char(36) NULL,
  resultado enum('CUMPRIDA','PARCIAL','NAO_CUMPRIDA') NOT NULL,
  manifestacao_tecnica text NOT NULL,
  descricao_snapshot text NOT NULL,
  referencia_snapshot varchar(255) NULL,
  criado_por char(36) NOT NULL,
  criado_em datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_relatorio_resultado_exigencia (relatorio_id,exigencia_id),
  KEY idx_resultado_exigencia_vigente (exigencia_id,criado_em),
  CONSTRAINT fk_resultado_relatorio FOREIGN KEY (relatorio_id)
    REFERENCES analise_planos_pareceres(id) ON DELETE RESTRICT,
  CONSTRAINT fk_resultado_exigencia FOREIGN KEY (exigencia_id)
    REFERENCES analise_planos_exigencias(id) ON DELETE RESTRICT,
  CONSTRAINT fk_resultado_submissao FOREIGN KEY (submissao_id)
    REFERENCES analise_planos_submissoes(id) ON DELETE RESTRICT,
  CONSTRAINT fk_resultado_criador FOREIGN KEY (criado_por)
    REFERENCES usuarios(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE analise_planos_exigencias
  MODIFY status enum('PENDENTE','CUMPRIDA','PARCIAL','NAO_CUMPRIDA','TRANSCRITA') NOT NULL DEFAULT 'PENDENTE',
  ADD COLUMN saneamento_pendente tinyint(1) NOT NULL DEFAULT 0 AFTER fundamento_transcricao;

UPDATE analise_planos_exigencias
SET saneamento_pendente=1
WHERE status='CUMPRIDA';

INSERT IGNORE INTO sequenciais_documentos (tipo_documento,ano,ultimo_numero)
VALUES ('RAP-REL',YEAR(CURDATE()),0);
