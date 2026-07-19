-- Remove apenas liberações de acesso ao módulo desativado.
-- A tabela e os contratos existentes são preservados.
DELETE FROM usuario_permissoes WHERE permissao = 'contratos';
