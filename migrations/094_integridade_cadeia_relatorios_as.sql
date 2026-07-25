-- Integridade das cadeias de relatórios e retornos A/S.
-- Execute após backup. A limpeza preserva o relatório oficial ligado em
-- vistoria_retornos e finaliza ramificações ativas legadas.

-- Vistorias comuns nunca pertencem a uma cadeia A/S.
UPDATE vistorias v
LEFT JOIN agendamentos a ON a.id=v.agendamento_id
SET v.relatorio_anterior_id=NULL
WHERE v.finalidade='VISTORIA'
  AND v.relatorio_anterior_id IS NOT NULL
  AND (a.relatorio_origem_id IS NULL OR a.relatorio_origem_id='');

-- O agendamento de retorno é a única fonte válida do vínculo anterior.
UPDATE vistorias v
JOIN agendamentos a ON a.id=v.agendamento_id
SET v.relatorio_anterior_id=a.relatorio_origem_id,
    v.finalidade='CUMPRIMENTO_EXIGENCIAS'
WHERE a.relatorio_origem_id IS NOT NULL
  AND a.relatorio_origem_id<>''
  AND (
    v.relatorio_anterior_id IS NULL
    OR v.relatorio_anterior_id<>a.relatorio_origem_id
    OR v.finalidade<>'CUMPRIMENTO_EXIGENCIAS'
  );

-- Remove duplicados do mesmo agendamento. A preferência é pelo relatório
-- apontado pelo retorno auditável, depois pelo finalizado e pelo mais recente.
CREATE TEMPORARY TABLE tmp_vistorias_agendamento_excluir (
  id char(36) PRIMARY KEY
) ENGINE=InnoDB;

INSERT INTO tmp_vistorias_agendamento_excluir (id)
SELECT id
FROM (
  SELECT v.id,
         ROW_NUMBER() OVER (
           PARTITION BY v.agendamento_id
           ORDER BY
             EXISTS(
               SELECT 1 FROM vistoria_retornos vr
               WHERE vr.relatorio_resultado_id=v.id OR vr.relatorio_origem_id=v.id
             ) DESC,
             (v.status IN ('APROVADA','APROVADA_COM_EXIGENCIAS')) DESC,
             v.criado_em DESC,
             v.id DESC
         ) AS posicao
  FROM vistorias v
  WHERE v.agendamento_id IS NOT NULL
) classificados
WHERE posicao>1;

DELETE ac
FROM assinatura_convites ac
JOIN tmp_vistorias_agendamento_excluir x
  ON x.id COLLATE utf8mb4_unicode_ci=ac.documento_id
WHERE ac.documento_tipo='RELATORIO';

DELETE da
FROM documento_assinaturas da
JOIN tmp_vistorias_agendamento_excluir x
  ON x.id COLLATE utf8mb4_unicode_ci=da.documento_id
WHERE da.documento_tipo='RELATORIO';

DELETE dp
FROM documento_aprovacoes dp
JOIN tmp_vistorias_agendamento_excluir x
  ON x.id COLLATE utf8mb4_unicode_ci=dp.documento_id
WHERE dp.documento_tipo='RELATORIO';

DELETE dar
FROM documento_artefatos dar
JOIN tmp_vistorias_agendamento_excluir x ON x.id=dar.documento_id
WHERE dar.documento_tipo='RELATORIO';

DELETE vr
FROM vistoria_retornos vr
JOIN tmp_vistorias_agendamento_excluir x ON x.id=vr.relatorio_origem_id;

DELETE v
FROM vistorias v
JOIN tmp_vistorias_agendamento_excluir x ON x.id=v.id;

DROP TEMPORARY TABLE tmp_vistorias_agendamento_excluir;

-- Em ramificações legadas, mantém ativa a versão oficial/mapeada; as demais
-- ficam canceladas e somente para histórico.
CREATE TEMPORARY TABLE tmp_vistorias_ramo_cancelar (
  id char(36) PRIMARY KEY
) ENGINE=InnoDB;

INSERT INTO tmp_vistorias_ramo_cancelar (id)
SELECT id
FROM (
  SELECT v.id,
         ROW_NUMBER() OVER (
           PARTITION BY v.relatorio_anterior_id
           ORDER BY
             EXISTS(
               SELECT 1 FROM vistoria_retornos vr
               WHERE vr.relatorio_resultado_id=v.id
             ) DESC,
             (v.status IN ('APROVADA','APROVADA_COM_EXIGENCIAS')) DESC,
             v.criado_em DESC,
             v.id DESC
         ) AS posicao
  FROM vistorias v
  WHERE v.relatorio_anterior_id IS NOT NULL
    AND v.status<>'CANCELADA'
) classificados
WHERE posicao>1;

UPDATE documento_assinaturas da
JOIN tmp_vistorias_ramo_cancelar x
  ON x.id COLLATE utf8mb4_unicode_ci=da.documento_id
SET da.status='CANCELADO',
    da.cancelado_em=COALESCE(da.cancelado_em,NOW()),
    da.motivo_cancelamento=COALESCE(da.motivo_cancelamento,'Ramificacao A/S duplicada saneada pela migration 094')
WHERE da.documento_tipo='RELATORIO'
  AND da.status='ASSINADO';

UPDATE vistorias v
JOIN tmp_vistorias_ramo_cancelar x ON x.id=v.id
SET v.status='CANCELADA',
    v.assinatura_status=IF(v.assinatura_status='ASSINADO','CANCELADO',v.assinatura_status),
    v.observacao_admin=COALESCE(v.observacao_admin,'Ramificacao A/S duplicada saneada pela migration 094');

DROP TEMPORARY TABLE tmp_vistorias_ramo_cancelar;

ALTER TABLE vistorias
  ADD COLUMN relatorio_anterior_ativo_id char(36)
    CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
    GENERATED ALWAYS AS (
      CASE
        WHEN relatorio_anterior_id IS NOT NULL AND status<>'CANCELADA'
        THEN relatorio_anterior_id
        ELSE NULL
      END
    ) VIRTUAL AFTER status;

ALTER TABLE vistorias
  ADD UNIQUE KEY uk_vistorias_agendamento_unico (agendamento_id),
  ADD UNIQUE KEY uk_vistorias_filho_ativo (relatorio_anterior_ativo_id);
