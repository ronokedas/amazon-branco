-- Migration 088: fluxo comercial, agenda, revisoes e licenca da analise de planos

ALTER TABLE servicos
  ADD COLUMN codigo_operacional varchar(60) NULL AFTER descricao,
  ADD UNIQUE KEY uk_servicos_codigo_operacional (codigo_operacional);

UPDATE servicos
SET codigo_operacional = 'ANALISE_PLANOS_EC1'
WHERE id = 'a1d980bd-6ebc-11f1-86ce-7e17ff5f90bf'
   OR lower(nome) = lower('Análise de Planos Ec1')
   OR lower(nome) = lower('Analise de Planos Ec1');

UPDATE servicos
SET codigo_operacional = 'ANALISE_PLANOS_EC2'
WHERE id = 'a1d98b0e-6ebc-11f1-86ce-7e17ff5f90bf'
   OR lower(nome) = lower('Análise de Planos Ec2')
   OR lower(nome) = lower('Analise de Planos Ec2');

ALTER TABLE analises_planos
  MODIFY analista_id char(36) NULL,
  MODIFY status enum(
    'RASCUNHO','AGUARDANDO_AGENDAMENTO','AGENDADA','EM_ANALISE',
    'AGUARDANDO_CORRECAO','AGUARDANDO_DOCUMENTOS',
    'AGUARDANDO_ASSINATURA_ANALISTA','AGUARDANDO_APROVACAO',
    'AGUARDANDO_APROVACAO_ADMIN','CONCLUIDA','REPROVADA','CANCELADA'
  ) NOT NULL DEFAULT 'AGUARDANDO_AGENDAMENTO',
  ADD COLUMN proposta_id char(36) NULL AFTER numero,
  ADD COLUMN servico_id char(36) NULL AFTER proposta_id,
  ADD COLUMN vendedor_origem_id char(36) NULL AFTER servico_id,
  ADD COLUMN classe_certificacao enum('EC1','EC2') NULL AFTER enquadramento,
  ADD COLUMN arqueacao_bruta decimal(10,2) NULL AFTER classe_certificacao,
  ADD COLUMN numero_passageiros int unsigned NULL AFTER arqueacao_bruta,
  ADD COLUMN possui_propulsao tinyint(1) NULL AFTER numero_passageiros,
  ADD COLUMN embarcacao_classificada tinyint(1) NULL AFTER possui_propulsao,
  ADD COLUMN tipo_navegacao varchar(120) NULL AFTER embarcacao_classificada,
  ADD COLUMN construcao_concluida tinyint(1) NULL AFTER tipo_navegacao,
  ADD COLUMN prazo_agendado_em datetime NULL AFTER analista_id,
  ADD COLUMN iniciado_em datetime NULL AFTER prazo_agendado_em,
  ADD COLUMN legado_sem_proposta tinyint(1) NOT NULL DEFAULT 0 AFTER iniciado_em,
  ADD UNIQUE KEY uk_analise_origem (proposta_id, embarcacao_id, servico_id),
  ADD KEY idx_analise_vendedor (vendedor_origem_id, status),
  ADD KEY idx_analise_prazo (analista_id, prazo_agendado_em, status),
  ADD CONSTRAINT fk_analise_proposta FOREIGN KEY (proposta_id) REFERENCES propostas(id) ON DELETE SET NULL,
  ADD CONSTRAINT fk_analise_servico FOREIGN KEY (servico_id) REFERENCES servicos(id) ON DELETE SET NULL,
  ADD CONSTRAINT fk_analise_vendedor FOREIGN KEY (vendedor_origem_id) REFERENCES usuarios(id) ON DELETE SET NULL;

UPDATE analises_planos
SET legado_sem_proposta = 1
WHERE proposta_id IS NULL;

UPDATE analises_planos SET status='EM_ANALISE' WHERE status='RASCUNHO';
UPDATE analises_planos SET status='AGUARDANDO_DOCUMENTOS' WHERE status='AGUARDANDO_CORRECAO';
UPDATE analises_planos SET status='AGUARDANDO_APROVACAO_ADMIN' WHERE status='AGUARDANDO_APROVACAO';

ALTER TABLE analises_planos
  MODIFY status enum(
    'AGUARDANDO_AGENDAMENTO','AGENDADA','EM_ANALISE','AGUARDANDO_DOCUMENTOS',
    'AGUARDANDO_ASSINATURA_ANALISTA','AGUARDANDO_APROVACAO_ADMIN',
    'CONCLUIDA','REPROVADA','CANCELADA'
  ) NOT NULL DEFAULT 'AGUARDANDO_AGENDAMENTO';

DROP PROCEDURE IF EXISTS reduzir_enums_analise_planos;
DELIMITER $$
CREATE PROCEDURE reduzir_enums_analise_planos()
BEGIN
  IF NOT EXISTS (SELECT 1 FROM analises_planos WHERE tipo_processo='OUTRO') THEN
    ALTER TABLE analises_planos
      MODIFY tipo_processo enum('LC','LCEC','LA','LR') NULL;
  ELSE
    ALTER TABLE analises_planos
      MODIFY tipo_processo enum('LC','LCEC','LA','LR','OUTRO') NULL;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM analises_planos WHERE enquadramento='OUTRO') THEN
    ALTER TABLE analises_planos
      MODIFY enquadramento enum('NORMAM-201','NORMAM-202') NULL;
  ELSE
    ALTER TABLE analises_planos
      MODIFY enquadramento enum('NORMAM-201','NORMAM-202','OUTRO') NULL;
  END IF;
END$$
DELIMITER ;
CALL reduzir_enums_analise_planos();
DROP PROCEDURE reduzir_enums_analise_planos;

CREATE TABLE analise_planos_agenda_historico (
  id bigint unsigned NOT NULL AUTO_INCREMENT,
  analise_id char(36) NOT NULL,
  analista_anterior_id char(36) NULL,
  analista_novo_id char(36) NULL,
  prazo_anterior_em datetime NULL,
  prazo_novo_em datetime NOT NULL,
  motivo varchar(500) NOT NULL,
  acao enum('AGENDAMENTO','REAGENDAMENTO','REATRIBUICAO') NOT NULL,
  criado_por char(36) NOT NULL,
  criado_em datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_analise_agenda_historico (analise_id, criado_em),
  CONSTRAINT fk_analise_agenda_processo FOREIGN KEY (analise_id) REFERENCES analises_planos(id) ON DELETE CASCADE,
  CONSTRAINT fk_analise_agenda_anterior FOREIGN KEY (analista_anterior_id) REFERENCES usuarios(id) ON DELETE SET NULL,
  CONSTRAINT fk_analise_agenda_novo FOREIGN KEY (analista_novo_id) REFERENCES usuarios(id) ON DELETE SET NULL,
  CONSTRAINT fk_analise_agenda_usuario FOREIGN KEY (criado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE notificacoes (
  id char(36) NOT NULL,
  usuario_id char(36) NOT NULL,
  evento varchar(60) NOT NULL,
  titulo varchar(180) NOT NULL,
  mensagem varchar(500) NOT NULL,
  referencia_tipo varchar(60) NULL,
  referencia_id char(36) NULL,
  url varchar(500) NULL,
  lida_em datetime NULL,
  criado_em datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_notificacoes_usuario (usuario_id, lida_em, criado_em),
  CONSTRAINT fk_notificacoes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE analise_planos_submissoes
  MODIFY criado_por char(36) NULL,
  ADD COLUMN origem enum('ANALISTA','PORTAL') NOT NULL DEFAULT 'ANALISTA' AFTER recebido_em,
  ADD COLUMN portal_cliente_id char(36) NULL AFTER origem,
  ADD KEY idx_submissao_origem (analise_id, origem, criado_em),
  ADD CONSTRAINT fk_submissao_portal_cliente FOREIGN KEY (portal_cliente_id) REFERENCES clientes(id) ON DELETE SET NULL;

ALTER TABLE portal_auditoria
  MODIFY evento enum('LOGIN_SUCESSO','LOGIN_FALHA','VISUALIZACAO','DOWNLOAD','UPLOAD_ANALISE') NOT NULL;

ALTER TABLE analise_planos_arquivos
  MODIFY criado_por char(36) NULL,
  ADD COLUMN item_id char(36) NULL AFTER submissao_id,
  ADD COLUMN classificacao enum('RECEBIDO','ACEITO','SUBSTITUIDO','REJEITADO') NOT NULL DEFAULT 'RECEBIDO' AFTER categoria,
  ADD COLUMN justificativa_classificacao varchar(500) NULL AFTER classificacao,
  ADD COLUMN classificado_por char(36) NULL AFTER criado_por,
  ADD COLUMN classificado_em datetime NULL AFTER classificado_por,
  ADD KEY idx_arquivo_classificacao (submissao_id, classificacao),
  ADD CONSTRAINT fk_arquivo_item FOREIGN KEY (item_id) REFERENCES analise_planos_itens(id) ON DELETE SET NULL,
  ADD CONSTRAINT fk_arquivo_classificador FOREIGN KEY (classificado_por) REFERENCES usuarios(id) ON DELETE SET NULL;

ALTER TABLE analise_planos_itens
  ADD COLUMN versao_normativa varchar(30) NULL AFTER referencia_normativa,
  ADD COLUMN obrigatorio tinyint(1) NOT NULL DEFAULT 1 AFTER versao_normativa,
  ADD COLUMN aplicavel tinyint(1) NOT NULL DEFAULT 1 AFTER obrigatorio,
  ADD COLUMN impeditivo_emissao tinyint(1) NOT NULL DEFAULT 1 AFTER aplicavel;

ALTER TABLE analise_planos_exigencias
  ADD COLUMN transcricao_admissivel tinyint(1) NOT NULL DEFAULT 0 AFTER status,
  ADD COLUMN fundamento_transcricao varchar(500) NULL AFTER transcricao_admissivel;

ALTER TABLE analise_planos_pareceres
  MODIFY status enum(
    'MINUTA','AGUARDANDO_APROVACAO','AGUARDANDO_ASSINATURA_ANALISTA','AGUARDANDO_APROVACAO_ADMIN',
    'PUBLICADO','DEVOLVIDO','CANCELADO'
  ) NOT NULL DEFAULT 'MINUTA',
  ADD COLUMN assinado_analista_em datetime NULL AFTER responsavel_assinatura_id,
  ADD COLUMN assinatura_analista_ip varchar(45) NULL AFTER assinado_analista_em,
  ADD COLUMN devolvido_motivo varchar(500) NULL AFTER assinatura_analista_ip,
  ADD COLUMN caminho_pdf_final varchar(500) NULL AFTER devolvido_motivo,
  ADD COLUMN hash_pdf_final char(64) NULL AFTER caminho_pdf_final;

UPDATE analise_planos_pareceres
SET status='AGUARDANDO_APROVACAO_ADMIN'
WHERE status='AGUARDANDO_APROVACAO';

ALTER TABLE analise_planos_pareceres
  MODIFY status enum(
    'MINUTA','AGUARDANDO_ASSINATURA_ANALISTA','AGUARDANDO_APROVACAO_ADMIN',
    'PUBLICADO','DEVOLVIDO','CANCELADO'
  ) NOT NULL DEFAULT 'MINUTA';

ALTER TABLE certificados_lc
  ADD COLUMN analise_id char(36) NULL AFTER vistoria_id,
  ADD UNIQUE KEY uk_licenca_analise (analise_id),
  ADD CONSTRAINT fk_licenca_analise FOREIGN KEY (analise_id) REFERENCES analises_planos(id) ON DELETE SET NULL;

INSERT INTO usuario_permissoes (usuario_id, permissao, permitido)
SELECT u.id, 'analise_planos', 1
FROM usuarios u
WHERE u.ativo=1 AND u.cargo IN ('ANALISTA','VENDEDOR')
ON DUPLICATE KEY UPDATE permitido=1;

INSERT INTO usuario_permissoes (usuario_id, permissao, permitido)
SELECT u.id, 'relatorios_aprovacao', 0
FROM usuarios u
WHERE u.cargo='ANALISTA'
ON DUPLICATE KEY UPDATE permitido=0;
