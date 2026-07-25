-- Fluxo auditavel de retornos para cumprimento de exigencias A/S.

ALTER TABLE agendamentos
  ADD COLUMN relatorio_origem_id CHAR(36) NULL AFTER proposta_id,
  ADD KEY idx_agendamentos_relatorio_origem (relatorio_origem_id),
  ADD CONSTRAINT fk_agendamento_relatorio_origem
    FOREIGN KEY (relatorio_origem_id) REFERENCES vistorias(id) ON DELETE SET NULL;

CREATE TABLE vistoria_retornos (
  id CHAR(36) NOT NULL,
  relatorio_origem_id CHAR(36) NOT NULL,
  agendamento_id CHAR(36) NULL,
  relatorio_resultado_id CHAR(36) NULL,
  status ENUM(
    'PENDENTE_AGENDAMENTO',
    'AGENDADO',
    'RELATORIO_ENVIADO',
    'CONCLUIDO',
    'CANCELADO'
  ) NOT NULL DEFAULT 'PENDENTE_AGENDAMENTO',
  motivo_cancelamento TEXT NULL,
  criado_por CHAR(36) NULL,
  cancelado_por CHAR(36) NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  cancelado_em DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_vistoria_retorno_origem (relatorio_origem_id),
  UNIQUE KEY uk_vistoria_retorno_agendamento (agendamento_id),
  UNIQUE KEY uk_vistoria_retorno_resultado (relatorio_resultado_id),
  KEY idx_vistoria_retornos_status (status),
  CONSTRAINT fk_vistoria_retorno_origem
    FOREIGN KEY (relatorio_origem_id) REFERENCES vistorias(id) ON DELETE RESTRICT,
  CONSTRAINT fk_vistoria_retorno_agendamento
    FOREIGN KEY (agendamento_id) REFERENCES agendamentos(id) ON DELETE SET NULL,
  CONSTRAINT fk_vistoria_retorno_resultado
    FOREIGN KEY (relatorio_resultado_id) REFERENCES vistorias(id) ON DELETE SET NULL,
  CONSTRAINT fk_vistoria_retorno_criador
    FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
  CONSTRAINT fk_vistoria_retorno_cancelador
    FOREIGN KEY (cancelado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Relaciona retornos ja existentes sem alterar os agendamentos ou numeros historicos.
INSERT INTO vistoria_retornos (
  id, relatorio_origem_id, agendamento_id, relatorio_resultado_id,
  status, criado_por, criado_em
)
SELECT UUID(), v.relatorio_anterior_id, v.agendamento_id, v.id,
       CASE
         WHEN v.status IN ('APROVADA','APROVADA_COM_EXIGENCIAS') THEN 'CONCLUIDO'
         WHEN v.status = 'AGUARDANDO_APROVACAO' THEN 'RELATORIO_ENVIADO'
         ELSE 'AGENDADO'
       END,
       v.criado_por, v.criado_em
FROM vistorias v
WHERE v.finalidade = 'CUMPRIMENTO_EXIGENCIAS'
  AND v.relatorio_anterior_id IS NOT NULL
ON DUPLICATE KEY UPDATE
  relatorio_resultado_id = COALESCE(vistoria_retornos.relatorio_resultado_id, VALUES(relatorio_resultado_id));

-- Gera a pendencia que faltava para relatorios validados com A/S e sem retorno.
INSERT INTO vistoria_retornos (id, relatorio_origem_id, status, criado_por)
SELECT UUID(), v.id, 'PENDENTE_AGENDAMENTO', v.aprovado_por
FROM vistorias v
WHERE v.status = 'APROVADA_COM_EXIGENCIAS'
  AND EXISTS (
    SELECT 1
    FROM vistoria_exigencias ve
    WHERE ve.vistoria_id = v.id
      AND ve.antes_de_suspender = 1
      AND ve.conforme = 'nao'
      AND ve.status_item <> 'cumprida'
  )
  AND NOT EXISTS (
    SELECT 1 FROM vistoria_retornos vr WHERE vr.relatorio_origem_id = v.id
  );
