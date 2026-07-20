# Deploy do Sistema Amazon no Ubuntu 24.04 com Docker

Este manual instala o sistema em um VPS Linux Ubuntu 24.04 usando Docker Compose.

## 1. Atualizar o servidor

```bash
sudo apt update
sudo apt upgrade -y
sudo apt install -y ca-certificates curl gnupg git unzip nano ufw
sudo reboot
```

Depois do reboot, conecte novamente no servidor.

## 2. Instalar Docker e Docker Compose

```bash
sudo install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
sudo chmod a+r /etc/apt/keyrings/docker.gpg

echo \
  "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu \
  $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | \
  sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
sudo usermod -aG docker $USER
```

Saia do SSH e entre novamente para o grupo `docker` valer.

Teste:

```bash
docker --version
docker compose version
```

## 3. Liberar portas no firewall

```bash
sudo ufw allow OpenSSH
sudo ufw allow 8082/tcp
sudo ufw allow 8083/tcp
sudo ufw allow 9002/tcp
sudo ufw allow 9003/tcp
sudo ufw enable
sudo ufw status
```

Portas:

- `8082`: sistema ERP
- `8083`: phpMyAdmin
- `9002`: MinIO API
- `9003`: MinIO Console

## 4. Baixar o sistema do GitHub

```bash
cd /opt
sudo git clone https://github.com/ronokedas/neto-amazon.git sistema-amazon
sudo chown -R $USER:$USER /opt/sistema-amazon
cd /opt/sistema-amazon
```

Como o repositório está público, o clone deve baixar sem pedir login.

## 5. Configurar variáveis de ambiente

```bash
cp .env.example .env
nano .env
```

Para testar no VPS igual ao ambiente local, mantenha as credenciais abaixo. Ajuste apenas `APP_URL` para o IP ou domínio do VPS:

```env
APP_URL=http://SEU_IP_OU_DOMINIO:8082/
DB_NAME=erp_sistema
DB_USER=erp_user
DB_PASS=erp_pass_2026
MYSQL_ROOT_PASSWORD=root_pass_2026
MINIO_ROOT_USER=erp_minio_admin
MINIO_ROOT_PASSWORD=erp_minio_pass_2026
```

Essas são as mesmas credenciais usadas no ambiente Docker local. Para produção aberta ao público, o ideal é trocar depois, mas para teste/homologação no VPS pode manter assim para facilitar.

Se futuramente usar domínio com HTTPS, altere:

```env
APP_URL=https://seudominio.com/
```

## 6. Subir os containers

```bash


cd /opt/sistema-amazon
docker compose up -d --build
```

Verifique:

```bash
docker compose ps
docker compose logs -f app
```

Acesse:

- Sistema: `http://SEU_IP:8082`
- phpMyAdmin: `http://SEU_IP:8083`
- MinIO Console: `http://SEU_IP:9003`

## 7. Banco de dados inicial

Na primeira subida, o MySQL executa automaticamente estes arquivos:

- `docker/init-custom.sql`
- `docker/02-erp_sistema_mysql.sql`
- `docker/03-add_minio_columns.sql`

### Forma preferida: exportar pelo phpMyAdmin local e importar pelo phpMyAdmin do VPS

Este e o fluxo recomendado para manter o VPS igual ao sistema local.

No computador local:

1. Acesse o phpMyAdmin local: `http://localhost:8083`
2. Selecione o banco `erp_sistema`
3. Clique em **Exportar**
4. Use a opcao **Personalizado**
5. Formato: **SQL**
6. Exporte **estrutura e dados**
7. Se aparecer a opcao, marque para incluir:
   - `DROP TABLE / VIEW / PROCEDURE / FUNCTION / EVENT / TRIGGER`
   - `CREATE TABLE`
   - `IF NOT EXISTS`
8. Baixe o arquivo `.sql`

No VPS:

1. Acesse o phpMyAdmin do VPS: `http://SEU_IP:8083`
2. Entre usando:
   - usuario: `root`
   - senha: `root_pass_2026`
3. Selecione o banco `erp_sistema`
4. Limpe o banco antes de importar o novo arquivo
5. Va em **Importar**
6. Selecione o arquivo `.sql` exportado do phpMyAdmin local
7. Execute a importacao

Se o arquivo for maior que o limite do phpMyAdmin, aumente temporariamente o `UPLOAD_LIMIT` no `docker-compose.yml` ou use a importacao pelo terminal abaixo.

### Alternativa pelo terminal

Se quiser importar um dump completo pelo terminal, coloque o arquivo no servidor e rode:

```bash
docker compose exec -T db mysql -u root -p erp_sistema < arquivo-db.sql
```

O terminal vai pedir a senha definida em `MYSQL_ROOT_PASSWORD`.


## 8. Permissões das pastas de runtime

Normalmente o Dockerfile já prepara as permissões. Se precisar corrigir:

```bash
docker compose exec app mkdir -p uploads logs storage/backups temp_pdf
docker compose exec app chown -R www-data:www-data uploads logs storage temp_pdf
docker compose exec app chmod -R 775 uploads logs storage temp_pdf
```

## 9. Criar bucket no MinIO

Acesse `http://SEU_IP:9003` com:

- usuário: `erp_minio_admin`
- senha: `erp_minio_pass_2026`

Crie o bucket:

```text
erp-storage
```

Ou use o nome configurado em `MINIO_BUCKET`.

### Criar/configurar o bucket sem entrar no MinIO Console

Credenciais padrao do MinIO Console:

- usuario: `erp_minio_admin`
- senha: `erp_minio_pass_2026`

Se preferir fazer tudo pelo terminal do VPS, rode este comando dentro da pasta do sistema:

```bash
cd /opt/sistema-amazon
docker run --rm --network container:erp_minio --entrypoint sh minio/mc -c "mc alias set local http://127.0.0.1:9000 erp_minio_admin erp_minio_pass_2026 && mc mb -p local/erp-storage || true && mc anonymous set download local/erp-storage"
```

Esse comando conecta no MinIO, cria o bucket `erp-storage` se ele ainda nao existir e libera leitura dos arquivos salvos pelo sistema.

## 10. Comandos úteis

Status:

```bash
docker compose ps
```

Logs:

```bash
docker compose logs -f
docker compose logs -f app
docker compose logs -f db
```

Reiniciar:

```bash
docker compose restart
```

Parar:

```bash
docker compose down
```

Subir:

```bash
docker compose up -d
```

Rebuild:

```bash
docker compose up -d --build
```

## 11. Enviar alteracoes locais para o GitHub

Use estes comandos no computador local, dentro da pasta do sistema:

```powershell
cd C:\sistema
git status
git add .
git commit -m "botoes"
git push
```



======

depois no vps:


cd /opt/sistema-amazon
git pull origin main



cd /opt/sistema-amazon
git pull --ff-only origin main && sudo docker compose up -d --build && sudo docker compose ps

ou sem db
docker compose up -d --force-recreate app


manunteção: 

git gc
git prune


========
34.132.186.4:8083

   - usuario: `root`
   - senha: `root_pass_2026`



nano .env




fluxo 

01
Proposta
02
Agendamento
03
Vistoria
04
Aprovação
05
Certificados




Servidor SMTP	smtp-relay.brevo.com
Porta	587
Fazer login	b157d8001@smtp-brevo.com
Senha	6zPdnrCTtUQGZ2cM






































Exemplo:

```powershell
git commit -m "Ajusta dashboard e manual de instalacao"
```

Se quiser enviar somente alguns arquivos, use:

```powershell
git add modules/dashboard/index.php docs/DEPLOY_UBUNTU_24_DOCKER.md
git commit -m "Atualiza dashboard e manual"
git push
```

## 12. Atualizar o sistema no VPS a partir do GitHub

```bash
cd /opt/sistema-amazon
git pull
docker compose up -d --build
```

Depois confira se os containers estao rodando:

```bash
docker compose ps
```

Se quiser acompanhar os logs:

```bash
docker compose logs -f app
```

Se aparecer erro de PDF dizendo `Autoloader do Composer nao encontrado`, rode:

```bash
docker compose exec app composer install --no-interaction --prefer-dist --no-dev
docker compose restart app
```

Esse comando instala as bibliotecas usadas para gerar PDFs, enviar e-mails e usar o MinIO. O `docker-compose.yml` atual ja faz isso automaticamente quando o container sobe, mas este comando resolve manualmente se o VPS estiver com uma versao antiga.

Se houver nova migration SQL manual, aplique uma por vez:

```bash
docker compose exec -T db mysql -u root -p erp_sistema < migrations/048_pre_agendamentos_sem_data.sql
```

Quando existir uma migration nova, troque o nome do arquivo no comando. Exemplo:

```bash
docker compose exec -T db mysql -u root -p erp_sistema < migrations/049_checklist_respostas_unicas.sql
```

O terminal vai pedir a senha do MySQL root:

```text
root_pass_2026
```

## 13. Backup do banco

Criar backup:

```bash
mkdir -p ~/backups-amazon
docker compose exec -T db mysqldump -u root -p erp_sistema > ~/backups-amazon/erp_sistema_$(date +%F_%H-%M).sql
```

Restaurar backup:

```bash
docker compose exec -T db mysql -u root -p erp_sistema < ~/backups-amazon/arquivo.sql
```

## 14. Atualizar Ubuntu e imagens Docker

```bash
sudo apt update
sudo apt upgrade -y
cd /opt/sistema-amazon
docker compose pull
docker compose up -d --build
```

## 15. Segurança recomendada

- Troque todas as senhas do `.env`.
- Não envie `.env` para o GitHub.
- Evite deixar phpMyAdmin aberto publicamente em produção.
- Use HTTPS com Nginx Proxy Manager, Caddy ou Nginx quando apontar domínio.
- Faça backup antes de atualizar.
- Guarde os backups fora do VPS também.
