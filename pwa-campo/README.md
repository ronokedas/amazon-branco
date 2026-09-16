# Aplicativo de Campo (PWA Vistoriador)

Este diretório contém o código-fonte do **Aplicativo de Campo PWA** para vistoriadores navais, permitindo a execução de vistorias técnicas e preenchimento de checklists NORMAM mesmo em locais sem conectividade com a internet (offline-first).

---

## 📁 Estrutura de Diretórios

- **`pwa-campo/`**: Projeto fonte (Vite + React + Dexie.js / IndexedDB).
  - `src/screens/`: Telas da aplicação (Login, Lista de Vistorias, Checklist por Categoria NORMAM, Gestão de Exigências, Evidências Fotográficas, Resumo e Conclusão).
  - `src/components/`: Componentes reutilizáveis (CameraCapture, Accordion, Badges de Status, Modais de Sincronização).
  - `src/db.js`: Gerenciador de persistência local no navegador via IndexedDB.
  - `src/sync.js`: Fila de sincronização bidirecional e resolução de pendências offline.
  - `src/api.js`: Cliente HTTP com suporte a autenticação por Bearer Token e retry.
  - `src/media.js`: Compressão e otimização de fotografias antes do envio.
- **`campo/`**: Build estático gerado (`dist`) servido diretamente pelo servidor web (Apache) em `/campo/`.
- **`modules/campo/api.php`**: Backend REST do aplicativo em `/api/campo/v1/`, expondo endpoints de autenticação, catálogo de itens, sincronização em lote e upload de fotos/evidências.

---

## 🔄 Fluxo de Sincronização Offline-First

1. **Carga Inicial / Online**:
   - O vistoriador faz login e baixa os dados dos agendamentos atribuídos a ele.
   - Os blocos e itens normativos NORMAM são armazenados localmente no IndexedDB.
2. **Operação em Campo (Offline)**:
   - Respostas do checklist (Conforme, Não Conforme, N/A) são salvas imediatamente no IndexedDB.
   - Fotos de evidências e a foto oficial da embarcação são convertidas em Blob/Base64 e enfileiradas.
   - O status da vistoria local é marcado como pendente de sincronização.
3. **Sincronização com o Sistema Principal**:
   - Ao restabelecer conexão (ou sob comando do vistoriador), o módulo `sync.js` envia:
     - `POST /api/campo/v1/vistorias/rascunho`: Respostas do checklist, observações técnicas e validade.
     - `POST /api/campo/v1/vistorias/anexos`: Evidências fotográficas comprimidas.
     - `POST /api/campo/v1/vistorias/foto-embarcacao`: Foto oficial canônica da embarcação.
     - `POST /api/campo/v1/vistorias/finalizar`: Conclusão da vistoria e encaminhamento para análise/aprovação.
   - O sistema principal registra o evento em `vistoria_mobile_sync` para auditoria completa.

---

## 🛠️ Comandos de Desenvolvimento e Build

```bash
# Instalar dependências
npm install

# Executar servidor de desenvolvimento local
npm run dev

# Gerar build de produção para implantação em /campo/
npm run build
```
O build compila os assets otimizados para o diretório raiz `../campo/`.
