-- Fluxo definitivo de Retorno A/S: relatorio impeditivo nao e aprovado.
-- Execute somente apos backup e apos o dry-run de scripts/sanear_relatorio_duplicado.php.

ALTER TABLE vistorias
  MODIFY COLUMN status ENUM(
    'PENDENTE',
    'AGUARDANDO_APROVACAO',
    'APROVADA',
    'APROVADA_COM_EXIGENCIAS',
    'RETORNO_AS',
    'REPROVADA',
    'CANCELADA'
  ) DEFAULT 'PENDENTE';

ALTER TABLE vistoria_retornos
  ADD COLUMN vistoriador_origem_id CHAR(36) NULL AFTER criado_por,
  ADD COLUMN vistoriador_retorno_id CHAR(36) NULL AFTER vistoriador_origem_id,
  ADD COLUMN motivo_reatribuicao TEXT NULL AFTER vistoriador_retorno_id,
  ADD COLUMN reatribuido_por CHAR(36) NULL AFTER motivo_reatribuicao,
  ADD COLUMN reatribuido_em DATETIME NULL AFTER reatribuido_por,
  ADD KEY idx_vistoria_retorno_vistoriador_origem (vistoriador_origem_id),
  ADD KEY idx_vistoria_retorno_vistoriador_retorno (vistoriador_retorno_id),
  ADD KEY idx_vistoria_retorno_reatribuido_por (reatribuido_por),
  ADD CONSTRAINT fk_vistoria_retorno_vistoriador_origem
    FOREIGN KEY (vistoriador_origem_id) REFERENCES usuarios(id) ON DELETE SET NULL,
  ADD CONSTRAINT fk_vistoria_retorno_vistoriador_retorno
    FOREIGN KEY (vistoriador_retorno_id) REFERENCES usuarios(id) ON DELETE SET NULL,
  ADD CONSTRAINT fk_vistoria_retorno_reatribuido_por
    FOREIGN KEY (reatribuido_por) REFERENCES usuarios(id) ON DELETE SET NULL;

UPDATE vistoria_retornos vr
JOIN vistorias v ON v.id=vr.relatorio_origem_id
LEFT JOIN agendamentos a0 ON a0.id=v.agendamento_id
LEFT JOIN agendamentos ar ON ar.id=vr.agendamento_id
SET vr.vistoriador_origem_id=COALESCE(vr.vistoriador_origem_id,a0.vistoriador_id),
    vr.vistoriador_retorno_id=COALESCE(vr.vistoriador_retorno_id,ar.vistoriador_id);

-- Corrige relatorios que foram indevidamente classificados como aprovados com A/S.
UPDATE vistorias v
SET v.status='RETORNO_AS'
WHERE v.status IN ('APROVADA','APROVADA_COM_EXIGENCIAS')
  AND EXISTS (
    SELECT 1
    FROM vistoria_exigencias ve
    WHERE ve.vistoria_id=v.id
      AND ve.antes_de_suspender=1
      AND ve.conforme='nao'
      AND ve.status_item<>'cumprida'
  );

-- Reconcilia relatorios de cumprimento legados que ja possuem filho oficial,
-- mas foram criados antes de o agendamento/retorno se tornarem a fonte unica.
UPDATE agendamentos a
JOIN vistorias resultado ON resultado.agendamento_id=a.id
SET a.relatorio_origem_id=resultado.relatorio_anterior_id
WHERE resultado.finalidade='CUMPRIMENTO_EXIGENCIAS'
  AND resultado.relatorio_anterior_id IS NOT NULL
  AND (a.relatorio_origem_id IS NULL OR a.relatorio_origem_id='')
  AND resultado.status<>'CANCELADA';

INSERT INTO vistoria_retornos (
  id,relatorio_origem_id,agendamento_id,relatorio_resultado_id,status,
  criado_por,vistoriador_origem_id,vistoriador_retorno_id
)
SELECT UUID(),resultado.relatorio_anterior_id,resultado.agendamento_id,resultado.id,
       CASE
         WHEN resultado.status IN ('AGUARDANDO_APROVACAO','RETORNO_AS','APROVADA','APROVADA_COM_EXIGENCIAS','REPROVADA')
           THEN 'RELATORIO_ENVIADO'
         ELSE 'AGENDADO'
       END,
       resultado.criado_por,a0.vistoriador_id,ar.vistoriador_id
FROM vistorias resultado
JOIN agendamentos ar ON ar.id=resultado.agendamento_id
LEFT JOIN vistorias origem ON origem.id=resultado.relatorio_anterior_id
LEFT JOIN agendamentos a0 ON a0.id=origem.agendamento_id
WHERE resultado.finalidade='CUMPRIMENTO_EXIGENCIAS'
  AND resultado.relatorio_anterior_id IS NOT NULL
  AND resultado.status<>'CANCELADA'
  AND NOT EXISTS (
    SELECT 1 FROM vistoria_retornos vr
    WHERE vr.relatorio_origem_id=resultado.relatorio_anterior_id
       OR vr.relatorio_resultado_id=resultado.id
  );

-- O retorno que produziu um novo relatorio com A/S foi executado; o novo
-- relatorio passa a ser a origem da proxima etapa.
UPDATE vistoria_retornos vr
JOIN vistorias resultado ON resultado.id=vr.relatorio_resultado_id
SET vr.status='CONCLUIDO'
WHERE resultado.status='RETORNO_AS'
  AND resultado.finalidade='CUMPRIMENTO_EXIGENCIAS'
  AND vr.status IN ('AGENDADO','RELATORIO_ENVIADO');

INSERT INTO vistoria_retornos (
  id,relatorio_origem_id,status,criado_por,vistoriador_origem_id
)
SELECT UUID(),v.id,'PENDENTE_AGENDAMENTO',v.aprovado_por,a.vistoriador_id
FROM vistorias v
LEFT JOIN agendamentos a ON a.id=v.agendamento_id
WHERE v.status='RETORNO_AS'
  AND NOT EXISTS (
    SELECT 1 FROM vistoria_retornos vr WHERE vr.relatorio_origem_id=v.id
  );

-- Certificados ligados diretamente a um relatorio A/S ficam cancelados,
-- sem exclusao de arquivo, hash ou registro de auditoria.
UPDATE certificados_csn c
JOIN vistorias v ON v.id=c.vistoria_id
SET c.status='cancelado',c.ativo=0
WHERE v.status='RETORNO_AS' AND c.status<>'cancelado';

UPDATE certificados_cnbl c
JOIN vistorias v ON v.id=c.vistoria_id
SET c.status='cancelado',c.ativo=0
WHERE v.status='RETORNO_AS' AND c.status<>'cancelado';

UPDATE certificados_cnarq c
JOIN vistorias v ON v.id=c.vistoria_id
SET c.status='cancelado',c.ativo=0
WHERE v.status='RETORNO_AS' AND c.status<>'cancelado';

UPDATE documento_aprovacoes da
JOIN certificados_csn c
  ON da.documento_tipo='CSN'
 AND da.documento_id COLLATE utf8mb4_general_ci=c.id
SET da.status='CANCELADO',
    da.erro_processamento=COALESCE(da.erro_processamento,'Cancelado: certificado vinculado a relatorio com A/S pendente.')
WHERE c.status='cancelado' AND c.ativo=0 AND da.status='APROVADO';

UPDATE documento_aprovacoes da
JOIN certificados_cnbl c
  ON da.documento_tipo='CNBL'
 AND da.documento_id COLLATE utf8mb4_general_ci=c.id
SET da.status='CANCELADO',
    da.erro_processamento=COALESCE(da.erro_processamento,'Cancelado: certificado vinculado a relatorio com A/S pendente.')
WHERE c.status='cancelado' AND c.ativo=0 AND da.status='APROVADO';

UPDATE documento_aprovacoes da
JOIN certificados_cnarq c
  ON da.documento_tipo='CNARQ'
 AND da.documento_id COLLATE utf8mb4_general_ci=c.id
SET da.status='CANCELADO',
    da.erro_processamento=COALESCE(da.erro_processamento,'Cancelado: certificado vinculado a relatorio com A/S pendente.')
WHERE c.status='cancelado' AND c.ativo=0 AND da.status='APROVADO';
