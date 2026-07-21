-- Preserva a meta global legada no novo modelo por escritorio.
-- A meta vai para a Matriz somente quando ainda nao existem metas do mes,
-- mantendo o total exibido no dashboard ate que o administrador distribua
-- os valores entre os escritorios na configuracao financeira.
INSERT INTO financeiro_metas_mensais (id, competencia, escritorio_id, usuario_id, valor)
SELECT UUID(), DATE_FORMAT(CURDATE(), '%Y-%m-01'), e.id, NULL,
       GREATEST(CAST(c.valor AS DECIMAL(12,2)), 0)
FROM configuracoes c
JOIN escritorios e ON e.id = '00000000-0000-4000-8000-000000000100'
WHERE c.chave = 'meta_mensal'
  AND NOT EXISTS (
      SELECT 1
      FROM financeiro_metas_mensais fm
      WHERE fm.usuario_id IS NULL
        AND fm.competencia = DATE_FORMAT(CURDATE(), '%Y-%m-01')
  );
