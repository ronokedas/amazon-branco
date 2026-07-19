INSERT INTO configuracoes (chave, valor, descricao)
VALUES ('meta_mensagem', '', 'Mensagem da meta mensal exibida para a equipe')
ON DUPLICATE KEY UPDATE chave = VALUES(chave);
