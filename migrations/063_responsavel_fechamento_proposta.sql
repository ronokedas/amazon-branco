-- Responsável comercial que fechou a proposta e deve ficar visível na vistoria.
ALTER TABLE propostas
  ADD COLUMN responsavel_fechamento_nome varchar(255) NULL AFTER operador_nome,
  ADD COLUMN responsavel_fechamento_telefone varchar(30) NULL AFTER responsavel_fechamento_nome;

-- Preserva os nomes livres já informados nas propostas antigas.
UPDATE propostas
SET responsavel_fechamento_nome = operador_nome
WHERE responsavel_fechamento_nome IS NULL
  AND operador_nome IS NOT NULL
  AND operador_nome <> '';

-- Leva o contato comercial existente aos agendamentos já criados.
UPDATE agendamentos a
INNER JOIN propostas p ON p.id = a.proposta_id
SET a.contato_nome = COALESCE(NULLIF(a.contato_nome, ''), p.responsavel_fechamento_nome),
    a.contato_telefone = COALESCE(NULLIF(a.contato_telefone, ''), p.responsavel_fechamento_telefone)
WHERE p.responsavel_fechamento_nome IS NOT NULL;
