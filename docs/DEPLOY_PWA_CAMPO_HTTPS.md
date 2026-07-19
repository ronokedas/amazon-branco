# Publicação do Amazon Campo em produção

## Ordem de publicação

1. Gere um backup conjunto do banco, `storage/private` e volume do MinIO com `scripts/backup_docker.sh`.
2. Publique o ERP e a pasta compilada `campo/` no mesmo domínio HTTPS.
3. Aplique, em ordem, as migrations pendentes até `073_foto_oficial_embarcacao.sql`.
4. Configure `APP_URL` com `https://`, credenciais próprias do banco/MinIO e segredos fora do repositório.
5. Suba `app`, `db`, `minio` e `worker` com `docker compose up -d --build`.
6. Agende o backup diário e confira os checksums gerados. O worker processa exportações e limpeza a cada minuto.

Não execute o seed de demonstração em produção. O primeiro login do vistoriador exige internet e apenas contas ativas cujo cargo principal seja `VISTORIADOR` são aceitas.

## Segurança e armazenamento

- Redirecione todo HTTP para HTTPS no proxy reverso e mantenha HSTS no domínio de produção.
- Não publique as portas do MinIO na internet; objetos de vistoria são entregues somente pela API autenticada.
- Preserve cookies `Secure`, `HttpOnly` e `SameSite=Lax` e o token CSRF enviado pela PWA.
- Restrinja `storage/private`, backups e ZIPs ao usuário do serviço.
- Monitore `docker compose logs app worker minio` e encaminhe erros de upload/exportação para o coletor de alertas da infraestrutura.
- Os ZIPs expiram em 24 horas; tentativas de login antigas e sessões vencidas são limpas pelo worker.

## Build e atualização da PWA

```text
cd pwa-campo
npm ci
npm run build
```

O build é gravado em `campo/`. Valide a atualização instalada em Android real; câmera e instalação de PWA exigem HTTPS fora de `localhost`.

## Verificação antes da liberação

- `docker compose ps` mostra banco, MinIO e app saudáveis e worker em execução.
- VISTORIADOR ativo entra; ADMIN, ANALISTA e VENDEDOR são rejeitados no `/campo/`.
- Uma resposta e uma foto sobrevivem ao modo avião, fechamento e reabertura do PWA.
- A reconexão esvazia cada operação uma única vez e o envio torna a vistoria somente leitura.
- Foto JPEG, PNG ou WebP mantém tamanho/hash; MIME falso e arquivo acima de 15 MB são rejeitados.
- A foto oficial capturada no Campo aparece no cartão da agenda e nas telas de embarcação e vistorias do ERP.
- A Central de Exportações gera manifestos válidos e o download exige ADMIN.
- Um backup recente é restaurado em ambiente isolado, incluindo a inspeção dos arquivos privados e do volume MinIO.
