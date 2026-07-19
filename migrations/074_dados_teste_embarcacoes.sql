INSERT INTO configuracoes (chave, valor, descricao)
VALUES (
    'dados_teste_embarcacoes',
    '0',
    'Exibe o preenchimento rápido com dados fictícios no cadastro de embarcações'
)
ON DUPLICATE KEY UPDATE chave = VALUES(chave);
