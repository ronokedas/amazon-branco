ALTER TABLE financeiro_metas_mensais
  ADD COLUMN mensagem VARCHAR(500) NULL AFTER valor;

-- Preserva a recompensa global anterior na meta atual da Matriz.
UPDATE financeiro_metas_mensais fm
JOIN configuracoes c ON c.chave = 'meta_mensagem'
SET fm.mensagem = NULLIF(TRIM(c.valor), '')
WHERE fm.escritorio_id = '00000000-0000-4000-8000-000000000100'
  AND fm.usuario_id IS NULL
  AND fm.competencia = DATE_FORMAT(CURDATE(), '%Y-%m-01')
  AND (fm.mensagem IS NULL OR fm.mensagem = '');
