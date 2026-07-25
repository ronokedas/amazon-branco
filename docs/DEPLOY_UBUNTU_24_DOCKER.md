# Deploy completo do Sistema Amazon no Ubuntu 24.04 com Docker

Este manual considera o repositório GitHub como a cópia completa e oficial do
sistema. O repositório inclui código, `.env`, dependências, banco de dados,
uploads, PDFs, assinaturas, backups, logs e demais arquivos de runtime.
Os objetos do MinIO ficam em `minio-data/` e também fazem parte do repositório.

O banco inicial de uma VPS nova é carregado exclusivamente de `db.sql`.

## 1. Preparar o Ubuntu

```bash
sudo apt update
sudo apt upgrade -y
sudo apt install -y ca-certificates curl gnupg git unzip nano ufw
```

Instale o Docker:

```bash
sudo install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg |
  sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
sudo chmod a+r /etc/apt/keyrings/docker.gpg

echo \
  "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu \
  $(. /etc/os-release && echo "$VERSION_CODENAME") stable" |
  sudo tee /etc/apt/sources.list.d/docker.list >/dev/null

sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
sudo usermod -aG docker "$USER"
```

Saia do SSH e conecte novamente. Confirme:

```bash
docker --version
docker compose version
```

## 2. Configurar o firewall

```bash
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw allow 8082/tcp
sudo ufw allow 8083/tcp
sudo ufw allow 9002/tcp
sudo ufw allow 9003/tcp
sudo ufw enable
sudo ufw status
```

Portas padrão:

- `8082`: ERP, salvo se `APP_PORT` definir outra porta;
- `8083`: phpMyAdmin;
- `9002`: API do MinIO;
- `9003`: console do MinIO.

## 3. Publicar absolutamente tudo do computador local

No PowerShell do Windows:

```powershell
cd C:\sistema
powershell -ExecutionPolicy Bypass -File .\scripts\publicar_tudo_github.ps1 `
  -Mensagem "Atualiza sistema, banco e arquivos completos"
```

O script executa cinco ações:

1. exporta o MySQL local completo e substitui `db.sql`;
2. sincroniza os objetos do MinIO para `minio-data/`;
3. pausa os containers para congelar os arquivos de runtime;
4. executa `git add --all`;
5. cria o commit e envia para `origin/main`;
6. religa os containers locais, inclusive quando ocorrer uma falha.

Se o Docker local estiver parado e o `db.sql` já estiver atualizado:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\publicar_tudo_github.ps1 `
  -SemExportarBanco `
  -Mensagem "Publica todos os arquivos"
```

Verifique o resultado:

```powershell
git status
git log -1 --oneline
```

O resultado esperado de `git status` é `working tree clean`.

## 4. Instalação em uma VPS nova

Baixe o repositório completo:

```bash
cd /opt
sudo git clone https://github.com/ronokedas/amazon-branco.git sistema-amazon
sudo chown -R "$USER":"$USER" /opt/sistema-amazon
cd /opt/sistema-amazon
```

Como o `.env` é versionado por decisão deste projeto, não é necessário
copiá-lo de `.env.example`. Confira apenas os valores:

```bash
nano .env
```

Confirme principalmente:

```env
APP_URL=https://sistema.amazonnaval.com.br/
DB_NAME=erp_sistema
DB_USER=erp_user
DB_PASS=erp_pass_2026
MYSQL_ROOT_PASSWORD=root_pass_2026
MINIO_ROOT_USER=erp_minio_admin
MINIO_ROOT_PASSWORD=erp_minio_pass_2026
```

### Importante: banco novo

O serviço `db` monta apenas este arquivo:

```text
db.sql -> /docker-entrypoint-initdb.d/01-db.sql
```

O MySQL executa `db.sql` automaticamente somente quando o volume `db_data`
está vazio. Nenhuma migration e nenhum outro arquivo SQL são executados
automaticamente.

Confirme que o dump existe antes de subir:

```bash
test -s db.sql && echo "db.sql encontrado"
```

Suba o sistema:

```bash
docker compose up -d --build
docker compose ps
```

Acompanhe a primeira importação:

```bash
docker compose logs -f db
```

Quando o banco estiver saudável, verifique a aplicação:

```bash
docker compose logs --tail=100 app
```

## 5. Recriar uma instalação usando novamente o `db.sql`

Esta operação exclui o banco existente na VPS e o recria com o conteúdo
versionado em `db.sql`. Use somente quando a intenção for substituir todo o
estado da VPS pelo estado publicado no GitHub.

```bash
cd /opt/sistema-amazon
docker compose down
docker volume ls | grep sistema
docker compose down -v
docker compose up -d --build
docker compose logs -f db
```

O nome real do volume depende do nome da pasta/projeto do Compose. O comando
`docker compose down -v` remove apenas os volumes declarados por esse projeto.

## 6. Arquivos persistentes e documentos do portal

Os PDFs oficiais não ficam dentro do MySQL. O banco armazena apenas caminho e
hash. Os arquivos físicos ficam principalmente em:

```text
storage/certificados/
storage/documentos_aprovados/
storage/documentos_assinados/
storage/documentos_gerados/
storage/private/
storage/protocolos/
uploads/
```

Essas pastas são versionadas. Portanto, um clone completo contém os documentos
necessários para o portal do cliente. Depois do clone ou atualização, corrija
as permissões:

```bash
cd /opt/sistema-amazon
sudo chown -R www-data:www-data storage uploads logs temp_pdf tmp
sudo chmod -R u+rwX,g+rwX storage uploads logs temp_pdf tmp
sudo chown -R root:root minio-data
```

Se alguma pasta ainda não existir:

```bash
mkdir -p storage uploads logs temp_pdf tmp
sudo chown -R www-data:www-data storage uploads logs temp_pdf tmp
sudo chmod -R u+rwX,g+rwX storage uploads logs temp_pdf tmp
sudo chown -R root:root minio-data
```

## 7. Atualizar uma VPS existente como espelho exato do GitHub

Como arquivos de runtime também são versionados, os containers podem deixar o
repositório da VPS modificado. Pare-os antes de atualizar:

```bash
cd /opt/sistema-amazon
docker compose down
sudo chown -R "$USER":"$USER" /opt/sistema-amazon
git fetch origin main
git reset --hard origin/main
git clean -fd
mkdir -p storage uploads logs temp_pdf tmp minio-data
sudo chown -R "$USER":www-data storage uploads logs temp_pdf tmp
sudo chmod -R u+rwX,g+rwX storage uploads logs temp_pdf tmp
sudo chown -R "$USER":"$USER" minio-data
docker compose up -d --build
docker compose ps
```

O primeiro `chown` é obrigatório porque os containers podem criar arquivos
como `www-data` ou `root`; sem ele, o Git não conseguirá substituir logs,
backups, PDFs, `tmp` ou objetos do MinIO. `git reset --hard` e `git clean -fd`
descartam alterações existentes somente na VPS. Esse fluxo é apropriado quando
o computador local/GitHub é a fonte oficial de todo o conteúdo.

Uma atualização comum não recria o banco, pois o volume `db_data` já existe.
Para substituir também o banco pelo `db.sql`, siga a seção 5.

## 8. Verificações depois da instalação

Containers:

```bash
docker compose ps
```

Banco:

```bash
docker compose exec db mysql \
  -u root -p"$MYSQL_ROOT_PASSWORD" \
  -e "USE erp_sistema; SHOW TABLES;"
```

Existência dos PDFs:

```bash
find storage/documentos_aprovados -type f | head
find storage/documentos_assinados -type f | head
```

Permissões:

```bash
docker compose exec app sh -c \
  'test -w /var/www/html/storage && test -w /var/www/html/uploads && echo OK'
```

Logs:

```bash
docker compose logs --tail=200 app
docker compose logs --tail=200 db
docker compose logs --tail=200 worker
```

## 9. MinIO

Acesse o console na porta configurada por `MINIO_CONSOLE_PORT`. Para criar os
buckets usados pelo sistema:

```bash
cd /opt/sistema-amazon
docker run --rm --network container:erp_minio --entrypoint sh minio/mc -c \
  "mc alias set local http://127.0.0.1:9000 erp_minio_admin erp_minio_pass_2026 &&
   mc mb -p local/erp-storage || true &&
   mc mb -p local/erp-campo-private || true"
```

O Compose monta `./minio-data:/data`. Assim, os objetos e metadados do MinIO
baixados do GitHub são carregados automaticamente em uma VPS nova. O script
local de publicação copia o estado atual do container para essa pasta antes de
executar o commit.

## 10. Comandos úteis

```bash
docker compose ps
docker compose logs -f app
docker compose restart
docker compose down
docker compose up -d
docker compose up -d --build
```

## 11. Diagnóstico do portal de documentos

Se o portal mostrar:

```text
Não foi possível disponibilizar o documento oficial.
```

confira o log:

```bash
docker compose logs --tail=200 app | grep "Erro PDF portal"
```

Depois confira se o caminho registrado no banco existe fisicamente no clone.
Exemplo:

```bash
test -f storage/documentos_aprovados/2026/csn/ARQUIVO.pdf
sha256sum storage/documentos_aprovados/2026/csn/ARQUIVO.pdf
```

O hash precisa ser igual ao campo `hash_arquivo_pdf` do documento.

## 12. Observações sobre a publicação integral

- O projeto foi configurado deliberadamente para não ignorar nenhum arquivo.
- Credenciais do `.env` são publicadas conforme a política deste projeto.
- Dependências de `vendor/` e `node_modules/` também são versionadas.
- Objetos e metadados do MinIO são versionados em `minio-data/`.
- Logs, sessões e backups podem aumentar rapidamente o tamanho do repositório.
- O GitHub rejeita arquivos individuais maiores que 100 MB.
- Antes de cada publicação, revise `git status` e a lista de arquivos grandes.
- `db.sql` é a única fonte automática do banco em instalações novas.
