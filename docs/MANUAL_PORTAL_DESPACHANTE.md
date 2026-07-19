# Manual simples — Portal do Despachante

## 1. Cadastrar o despachante

1. Entre no ERP com um usuário **Administrador**.
2. No menu lateral, abra **Cadastros > Despachantes**.
3. Clique em **Novo despachante**.
4. Informe nome, CPF/CNPJ, e-mail e os demais dados.
5. Em **Embarcações vinculadas**, marque as embarcações atendidas pelo despachante.
6. Salve o cadastro.

O e-mail precisa estar correto: ele será o login usado no portal e receberá a senha temporária.

## 2. Criar a senha e liberar o acesso

1. No menu do ERP, abra **Portal de clientes**.
2. Use a busca ou filtre por **Despachantes**.
3. Na linha do despachante, clique no botão **Acesso** (ícone de chave).
4. No painel lateral, confira o nome, e-mail e as embarcações vinculadas.
5. O sistema sugere uma senha temporária. Para trocar a sugestão, clique em **Gerar**.
6. Clique em **Enviar dados para o e-mail**.

Essa ação cria ou redefine o acesso. A senha é armazenada de forma protegida e não pode ser consultada depois. Para gerar uma nova senha, repita o processo.

## 3. Como o despachante acessa

1. Abra `http://SEU-ENDERECO/portal/login` ou clique em **Abrir portal** na tela de administração.
2. No campo **E-mail**, informe o e-mail cadastrado no despachante.
3. Informe a senha temporária recebida.
4. No primeiro acesso, defina uma senha definitiva.

Depois do login, o despachante verá somente as embarcações com vínculo ativo e poderá visualizar ou baixar:

- certificados vigentes;
- relatórios de vistoria aprovados;
- pareceres de análise de planos publicados.

O despachante tem acesso somente para leitura. Ele não pode alterar embarcações, documentos ou análises.

## 4. Alterar embarcações do despachante

1. Abra **Cadastros > Despachantes**.
2. Edite o despachante.
3. Marque ou desmarque as embarcações.
4. Salve.

Ao desmarcar uma embarcação, o acesso a ela é revogado imediatamente. O sistema mantém o período anterior no histórico do vínculo.

## 5. Problemas comuns

- **Não recebeu o e-mail:** confira o endereço cadastrado e o registro em **E-mails**. O acesso pode ter sido criado mesmo quando o envio falha; gere e envie uma nova senha.
- **Não aparece nenhuma embarcação:** confira se há ao menos um vínculo ativo no cadastro do despachante.
- **Login não funciona:** use o e-mail exato do cadastro. Se necessário, gere outra senha em **Portal de clientes**.
- **Documento não aparece:** certificados vencidos/cancelados e relatórios não publicados não são exibidos no portal.
- **O mesmo e-mail já está em uso:** cada login do portal precisa ser único. Corrija o e-mail duplicado antes de ativar o acesso.

## 6. Segurança e auditoria

O ERP registra login, visualização e download, incluindo data, cliente/despachante, embarcação, documento, IP e navegador. A inativação de um vínculo bloqueia imediatamente novas visualizações e downloads daquela embarcação.
