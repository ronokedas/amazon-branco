# Manual de recuperação — erro 521 no VPS

## O que significa

O erro **521 Web server is down** é exibido pelo Cloudflare quando ele não
consegue abrir conexão com o servidor de origem. No incidente de 12/08/2026,
a porta 80 do VPS estava publicada, mas o Apache dentro do container `erp_app`
ainda não havia iniciado.

A causa foi o script `docker/prepare-runtime-dirs.sh`: ele aplicava `chmod`
arquivo por arquivo durante o boot. Após restaurar o volume, `storage` tinha
mais de 224 mil arquivos. A inicialização ficou bloqueada nessa etapa e o
Cloudflare recebeu uma conexão encerrada.

O script foi corrigido no commit `e702b2db` para aplicar permissões em lote.

## Diagnóstico rápido

No terminal SSH do VPS:

```bash
cd /opt/sistema-amazon
docker compose ps
curl -sS -o /dev/null -w 'HTTP_LOCAL=%{http_code}\n' --max-time 10 http://127.0.0.1/
docker compose exec -T app ps -ef
```

Resultado esperado:

- `erp_app` como `Up` ou `healthy`;
- `HTTP_LOCAL=200`;
- processos `apache2 -DFOREGROUND` em execução.

Se `HTTP_LOCAL=000`, `Connection reset by peer`, ou não houver processos
Apache, confira se a preparação de permissões está bloqueada:

```bash
docker compose exec -T app ps -ef | grep -E 'prepare-runtime|find .*/storage|apache2'
docker compose logs --tail=150 app
```

## Recuperação segura

Use este procedimento quando o código já estiver no GitHub. Ele **não remove**
o banco MySQL, MinIO, uploads, PDFs ou demais dados persistidos; recria apenas
os containers `app` e `worker`.

```bash
cd /opt/sistema-amazon

# Permite que o Git atualize arquivos versionados que foram criados pelo container.
sudo chown -R "$USER:$USER" uploads logs storage tmp temp_pdf

# Busca a correção e substitui somente os arquivos do repositório.
git fetch origin main
git reset --hard origin/main

# Reinicia somente a aplicação e o worker usando o código atualizado.
docker compose up -d --force-recreate app worker
```

Depois aguarde a inicialização e valide:

```bash
docker compose ps
curl -sS -o /dev/null -w 'HTTP_LOCAL=%{http_code}\n' --max-time 20 http://127.0.0.1/
docker compose exec -T app ps -ef | grep '[a]pache2'
```

Quando o resultado for `HTTP_LOCAL=200`, teste o domínio:

```text
https://sistema.amazonnaval.com.br/
```

## Se ainda não voltar

```bash
cd /opt/sistema-amazon
docker compose logs --tail=200 app
docker compose logs --tail=100 db
docker compose exec -T app apachectl configtest
docker compose exec -T app php scripts/healthcheck.php
```

Não execute `docker compose down -v`, `docker volume rm`, `git clean -fd` ou
qualquer comando de remoção de `storage`, `uploads`, `minio-data` ou do volume
`db_data` durante uma recuperação: eles podem apagar banco e documentos.

## Prevenção

- Mantenha o VPS no commit `e702b2db` ou posterior.
- Em atualização comum, prefira `docker compose up -d --force-recreate app worker`
  em vez de derrubar banco e MinIO.
- Antes de importar um banco via phpMyAdmin, confirme que a aplicação está
  parada apenas se a importação exigir manutenção; a importação do banco não é
  a causa direta de um erro 521.
- Se restaurar muitos documentos, execute o procedimento acima uma única vez e
  aguarde a aplicação terminar de subir antes de testar o domínio.
