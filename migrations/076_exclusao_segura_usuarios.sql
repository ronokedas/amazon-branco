-- Exclusão lógica preserva referências históricas e libera o e-mail para reutilização.
ALTER TABLE usuarios
    ADD COLUMN excluido_em DATETIME NULL AFTER ativo,
    ADD INDEX idx_usuarios_excluido_em (excluido_em);
