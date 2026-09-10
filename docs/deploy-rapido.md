# ⚡ Deploy Rápido: Windows Local ➔ GitHub ➔ VPS Linux

Guia prático e direto em **3 etapas** para atualizar os arquivos do sistema e o banco de dados da sua máquina local para a VPS.

---

## 📦 ETAPA 1: Gerar o Backup do Banco de Dados Local

Você pode exportar o banco de dados atualizado de duas formas (escolha a que preferir):

### Opção A: Pelo Terminal do Windows / PowerShell (Recomendado — 100% compatível MySQL 8.0)
Com o Docker rodando, abra o PowerShell e execute:

```powershell
docker exec erp_db sh -c "exec mysqldump -u root -proot_pass_2026 --default-character-set=utf8mb4 erp_sistema > /tmp/db.sql"
docker cp erp_db:/tmp/db.sql C:\sistema\db.sql
```
*(Esse comando utiliza o cliente nativo do MySQL 8.0 dentro do container de banco, exportando colunas geradas e UTF-8 de forma 100% perfeita para o Linux da VPS).*

---

### Opção B: Pelo phpMyAdmin no Navegador
1. Abra o phpMyAdmin no navegador: [http://localhost:8083](http://localhost:8083)
2. No menu lateral esquerdo, clique no banco de dados **`erp_sistema`**.
3. No menu superior, clique na aba **Exportar** (*Export*).
4. Método de exportação: selecione **Rápido** (*Quick*).
5. Formato: selecione **SQL**.
6. Clique no botão **Exportar** (*Executar*) e salve o arquivo com o nome **`db.sql`** dentro de:
   ```text
   C:\sistema\db.sql
   ```

---

## 🚀 ETAPA 2: Enviar Tudo para o GitHub

No PowerShell do Windows, acesse a pasta do projeto e envie as alterações:

```powershell
# 1. Entrar na pasta do sistema
cd C:\sistema

# 2. Adicionar arquivos modificados
git add .

# 3. Forçar a adição do db.sql (garante inclusão caso esteja no .gitignore)
git add -f db.sql

# 4. Gravar o commit
git commit -m "Deploy: Atualizacao do sistema e banco de dados"

# 5. Enviar para o GitHub
git push origin main
```

> **Verificação rápida:** execute `git status`. Deve retornar `nothing to commit, working tree clean`.

---

## 🐧 ETAPA 3: Atualizar na VPS (Linux / Ubuntu com Docker)

Acesse sua VPS via SSH:
```bash
ssh usuario@ip-da-sua-vps
```

Em seguida, execute o bloco de comandos abaixo. Ele já inclui a correção de permissões de usuário do Linux, atualização dos arquivos, recriação automática do banco no Docker e permissões para o servidor web (`www-data`):

```bash
# 1. Entrar na pasta do sistema na VPS
cd /opt/sistema-amazon

# 2. Conceder permissão ao seu usuário Linux para o Git atualizar sem travar
sudo chown -R "$USER":"$USER" /opt/sistema-amazon

# 3. Baixar a versão mais recente do GitHub (espelho exato do repositório)
git fetch origin main
git reset --hard origin/main

# 4. Parar os containers antigos e recriar com o novo db.sql automaticamente
sudo docker compose down -v
sudo docker compose up -d --build

# 5. Ajustar permissões do Linux para pastas de uploads, PDFs e logs (servidor Apache / PHP)
sudo chown -R www-data:www-data storage uploads logs temp_pdf tmp
sudo chmod -R 775 storage uploads logs temp_pdf tmp

# 6. Conferir se todos os containers subiram saudáveis
sudo docker compose ps

# 7. Confirmar se o banco e os usuários foram carregados com sucesso
sudo docker compose exec -T db mysql -u root -proot_pass_2026 erp_sistema -e "SELECT count(*) AS total_usuarios FROM usuarios;"
```

---

### 💡 Dica: Comando Único de Atualização na VPS (Opcional)

Para atualizar a VPS no futuro com apenas **um único comando**, copie e cole esta linha no terminal da sua VPS:

```bash
cd /opt/sistema-amazon && sudo chown -R "$USER":"$USER" . && git fetch origin main && git reset --hard origin/main && sudo docker compose down -v && sudo docker compose up -d --build && sudo chown -R www-data:www-data storage uploads logs temp_pdf tmp && sudo chmod -R 775 storage uploads logs temp_pdf tmp && sudo docker compose ps && sudo docker compose exec -T db mysql -u root -proot_pass_2026 erp_sistema -e "SELECT count(*) AS total_usuarios FROM usuarios;"
```
