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

---

## 🚀 Guia de Instalação do Zero: Nova VPS com Ubuntu 22.04 LTS (HostGator - Recomendado)

O **Ubuntu 22.04 LTS** é a melhor escolha possível para o seu ERP: é o sistema operacional mais estável, padronizado e rápido de configurar para Docker, sem necessidade de lidar com bloqueios de SELinux.

Se você contratou uma VPS nova na **HostGator** com **Ubuntu 22.04 LTS** (sistema limpo e sem nada instalado), siga este passo a passo:

### 1. Acessar a VPS via SSH e Atualizar o Sistema
No seu terminal (PowerShell, PuTTY ou terminal Linux):
```bash
ssh root@IP_DA_SUA_VPS
```

Atualize os pacotes do sistema e instale utilitários essenciais:
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y git curl ufw ca-certificates gnupg
```

---

### 2. Configurar Memória Swap (Fundamental para Evitar Travamentos de RAM)
Em qualquer VPS, o conjunto Docker (MySQL 8.0, PHP, MinIO) precisa de uma reserva de memória para evitar que o servidor trave por falta de RAM (*Out-of-Memory*). Crie 2GB de Swap com estes comandos rápidos:
```bash
# 1. Criar arquivo de 2GB de Swap
sudo fallocate -l 2G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile

# 2. Tornar o Swap permanente no boot
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab

# 3. Conferir se o Swap está ativo
free -h
```

---

### 3. Instalar o Docker e Docker Compose Oficial
No Ubuntu, a instalação recomendada pela Docker é direta via script oficial:
```bash
# Baixa e instala o Docker Engine + plugin Docker Compose
curl -fsSL https://get.docker.com | sudo sh

# Iniciar o serviço e habilitar para iniciar automaticamente no boot
sudo systemctl start docker
sudo systemctl enable docker

# Conferir as versões instaladas
docker --version
docker compose version
```

---

### 4. Liberar as Portas no Firewall do Ubuntu (`ufw`)
Configure o firewall do Ubuntu para liberar o SSH e as portas do sistema:
```bash
# Liberar porta de acesso SSH (imprescindível para não perder a conexão)
sudo ufw allow OpenSSH

# Liberar portas Web do sistema e phpMyAdmin
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw allow 8082/tcp
sudo ufw allow 8083/tcp

# Ativar o firewall
sudo ufw --force enable
sudo ufw status
```

---

### 5. Baixar o Sistema do GitHub e Subir os Containers
```bash
# 1. Acessar a pasta /opt e clonar o repositório
cd /opt
sudo git clone https://github.com/ronokedas/amazon-branco.git sistema-amazon
cd /opt/sistema-amazon

# 2. Conceder permissão ao seu usuário na pasta
sudo chown -R "$USER":"$USER" /opt/sistema-amazon

# 3. Subir todos os containers pela primeira vez (o banco db.sql será importado automaticamente)
sudo docker compose up -d --build

# 4. Ajustar permissões para pastas de uploads, laudos e sessões
sudo chown -R www-data:www-data storage uploads logs temp_pdf tmp
sudo chmod -R 775 storage uploads logs temp_pdf tmp

# 5. Conferir se todos os containers subiram saudáveis
sudo docker compose ps
```

Pronto! Seu sistema já estará disponível no navegador:
* **Sistema ERP:** `http://IP_DA_SUA_VPS:8082`
* **phpMyAdmin:** `http://IP_DA_SUA_VPS:8083`

---

## 🐧 Guia Alternativo: Configuração Inicial em VPS com AlmaLinux (RHEL)

Se você contratou uma VPS nova na **HostGator** com **AlmaLinux** (sistema limpo, recém-instalado e sem nada prévio), siga este roteiro único do zero para deixar a VPS pronta e o sistema rodando:

### 1. Acessar a VPS via SSH e Atualizar o Sistema
Abra o seu terminal (ou PuTTY / PowerShell) e acerte a conexão:
```bash
ssh root@IP_DA_SUA_VPS
```

Atualize todos os pacotes básicos e instale o Git e utilitários de repositório:
```bash
sudo dnf update -y
sudo dnf install -y git curl dnf-plugins-core
```

---

### 2. Instalar o Docker e Docker Compose no AlmaLinux
O AlmaLinux é 100% compatível com o Docker empresarial (RHEL/CentOS):
```bash
# 1. Adicionar o repositório oficial do Docker
sudo dnf config-manager --add-repo https://download.docker.com/linux/centos/docker-ce.repo

# 2. Instalar o Docker Engine e o plugin oficial Docker Compose
sudo dnf install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin

# 3. Iniciar o serviço do Docker e habilitar para iniciar automaticamente com a VPS
sudo systemctl start docker
sudo systemctl enable docker

# 4. Conferir a instalação
docker --version
docker compose version
```

---

### 3. Configurar o SELinux (Fundamental no AlmaLinux)
Por padrão, o AlmaLinux vem com o **SELinux** ativado em modo restritivo (`Enforcing`), o que costuma bloquear o Docker de ler e gravar pastas de uploads e banco de dados. Configure para modo permissivo:
```bash
# Desativa temporariamente para uso imediato
sudo setenforce 0

# Torna a alteração permanente para reinicializações futuras
sudo sed -i 's/^SELINUX=enforcing/SELINUX=permissive/' /etc/selinux/config
```

---

### 4. Liberar as Portas no Firewall do AlmaLinux (`firewalld`)
O AlmaLinux utiliza o firewall nativo `firewalld`. Execute os comandos abaixo para liberar o tráfego web do sistema:
```bash
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --permanent --add-port=8082/tcp
sudo firewall-cmd --permanent --add-port=8083/tcp
sudo firewall-cmd --reload
```

---

### 5. Clonar o Repositório e Subir o Sistema Amazon
Com o Docker ativo e as portas abertas, basta baixar o projeto do GitHub e iniciar os containers:
```bash
# 1. Ir para a pasta de aplicações do servidor
cd /opt

# 2. Baixar o repositório completo
sudo git clone https://github.com/ronokedas/amazon-branco.git sistema-amazon
cd /opt/sistema-amazon

# 3. Dar permissão ao seu usuário na pasta
sudo chown -R "$USER":"$USER" /opt/sistema-amazon

# 4. Subir todos os containers pela primeira vez (o banco db.sql será importado sozinho)
sudo docker compose up -d --build

# 5. Configurar permissões de escrita para as pastas de uploads e relatórios
# (O UID 33 corresponde ao usuário www-data do Apache dentro do container)
sudo chown -R 33:33 storage uploads logs temp_pdf tmp
sudo chmod -R 775 storage uploads logs temp_pdf tmp

# 6. Conferir se todos os containers subiram saudáveis
sudo docker compose ps
```

Pronto! Seu sistema já estará disponível no navegador no IP da sua VPS:
* **Sistema ERP:** `http://IP_DA_SUA_VPS:8082`
* **phpMyAdmin:** `http://IP_DA_SUA_VPS:8083`

---

## ⚡ Otimizações Nativas Implementadas (Leve, Rápido e Econômico em RAM/Disco)

O sistema conta com configurações de infraestrutura prontas para rodar em VPS compactas (1GB a 2GB de RAM) sem travar e sem esgotar o disco:

1. **MySQL 8.0 Otimizado (`docker-compose.yml`)**:
   - `performance-schema=OFF`: Economiza ~400MB de memória RAM no container do banco.
   - `binlog-expire-logs-seconds=172800` e `max-binlog-size=50M`: Limita os logs binários a 48h e 50MB, prevenindo estouro de espaço em disco.
   - `innodb-buffer-pool-size=128M`: Uso racional de memória para consultas ultrarrápidas.

2. **Compressão GZIP e Cache de Navegador (Apache)**:
   - Módulos `mod_deflate`, `mod_headers` e `mod_expires` ativos no Docker e no `.htaccess`.
   - Redução de mais de 75% no tamanho transferido de CSS e JavaScript.
   - Cache no navegador para imagens, CSS, JS e fontes.

3. **PHP OPcache em Produção**:
   - Execução pré-compilada em memória RAM, acelerando requisições em até 5x.
   - Revalidação a cada 2 segundos (`revalidate_freq=2`), refletindo qualquer alteração de código sem necessidade de reiniciar o Apache.
   - Desativação automática apenas em modo de depuração (`APP_DEBUG=1`).

4. **Limpeza Automática de Sessões e Temporários (`scripts/prepare_storage.php`)**:
   - Purgas periódicas de sessões vazias (0 bytes) e sessões inativas com mais de 14 dias.
   - Limpeza de PDFs temporários em `temp_pdf/` e `tmp/pdfs/` com mais de 2 dias.

5. **Índices de Performance SGQ e Usuários (Migration 102)**:
   - Índices compostos criados para `usuarios`, `usuario_permissoes`, `sgq_auditoria_cadastral`, `sgq_matriz_riscos` e `sgq_nao_conformidades`.


