-- Mantem o marcador A/S consistente entre a resposta editavel do checklist
-- e a exigencia oficial usada pela aprovacao, pelo PDF e pelos certificados.
START TRANSACTION;

-- Se qualquer uma das representacoes historicas registrou A/S, preserve-a.
UPDATE vistoria_checklist_respostas r
JOIN (
    SELECT vistoria_id, catalogo_id, MAX(antes_de_suspender) AS antes_de_suspender
    FROM vistoria_exigencias
    WHERE catalogo_id IS NOT NULL
    GROUP BY vistoria_id, catalogo_id
) ve
  ON ve.vistoria_id = r.vistoria_id
 AND ve.catalogo_id = r.catalogo_id
SET r.sem_prazo = GREATEST(r.sem_prazo, ve.antes_de_suspender);

UPDATE vistoria_exigencias ve
JOIN vistoria_checklist_respostas r
  ON r.vistoria_id = ve.vistoria_id
 AND r.catalogo_id = ve.catalogo_id
SET ve.antes_de_suspender = 1,
    ve.vencimento = NULL
WHERE ve.catalogo_id IS NOT NULL
  AND ve.conforme = 'nao'
  AND ve.status_item <> 'cumprida'
  AND r.status = 'NAO_CONFORME'
  AND r.sem_prazo = 1
  AND ve.antes_de_suspender = 0;

COMMIT;
