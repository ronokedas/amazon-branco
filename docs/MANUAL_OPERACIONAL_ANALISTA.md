# Manual Operacional do Analista Naval — ERP Amazon Certificadora

Este diretório contém a documentação técnica oficial e o manual operacional em PDF desenvolvido para capacitar e guiar o **Analista Naval / Engenheiro Responsável** no uso diário do sistema.

---

## 📄 Arquivos do Manual

1. **PDF Oficial (Pronto para Impressão e Estudo):**
   - [`MANUAL_OPERACIONAL_ANALISTA.pdf`](file:///C:/sistema/docs/MANUAL_OPERACIONAL_ANALISTA.pdf) (10 páginas completas em formato A4)

2. **Código-Fonte Visual em HTML (Editável):**
   - [`MANUAL_OPERACIONAL_ANALISTA.html`](file:///C:/sistema/docs/MANUAL_OPERACIONAL_ANALISTA.html)

3. **Script Automatizador de Compilação do PDF:**
   - [`gerar_manual_analista_pdf.js`](file:///C:/sistema/docs/gerar_manual_analista_pdf.js)

---

## 🚀 Como Recompilar o PDF

Caso faça alterações no texto ou no layout do arquivo HTML, execute no terminal:

```powershell
cd C:\sistema
node .\docs\gerar_manual_analista_pdf.js
```

O script utiliza o motor do Google Chrome / Microsoft Edge em modo headless com margem zero e impressão exata de cores, gerando o PDF com 10 páginas calibradas.

---

## 📚 Estrutura e Conteúdo do Manual

O manual é 100% focado no perfil do **Analista Naval**, estruturado em 10 páginas didáticas:

| Página | Módulo / Tema | Conteúdo Prático Ensinado |
| :---: | :--- | :--- |
| **01** | **Capa Oficial** | Apresentação institucional, selos NORMAM-202/201 e indicadores da Mesa Técnica. |
| **02** | **Capítulo 1: Dashboard do Analista** | Interpretação do banner de processos urgentes, os 5 KPIs estratégicos, fila de prioridades e rotina diária matinal. |
| **03** | **Capítulo 2: Abertura & Enquadramento** | Como abrir nova análise, seleção inteligente de embarcação/armador, tipos de processo (`LC`, `LA`, `LR`, `LCEC`), classes (`EC1`/`EC2`) e dados do autor/ART. |
| **04** | **Capítulo 2: Revisões & Matriz Normativa** | Gestão de revisões de pranchas/cálculos, classificação de arquivos (`ACEITO`, `SUBSTITUÍDO`, `REJEITADO`) e auditoria dos 11 itens da NORMAM-202. |
| **05** | **Capítulo 2: Exigências & Parecer Técnico** | Regra mandatória de auditoria naval (sem baixa manual), elaboração do relatório de ciclo, manifestação sobre exigências e envio para publicação. |
| **06** | **Capítulo 3: Homologação de Vistorias** | Fila de relatórios de campo, auditoria de fotos georreferenciadas e os 4 caminhos de decisão: `APROVADA`, `APROVADA COM EXIGÊNCIAS`, `RETORNO_AS` e `REPROVADA`. |
| **07** | **Capítulo 4: Protocolos, SISAP & Custódia** | Barra de filtros por chips, as 6 abas temáticas do dossiê, número SISAP da Marinha, custódia física de originais (CRPG/TIE) e avisos por WhatsApp. |
| **08** | **Capítulo 5: Certificados Estatutários** | Emissão de `CSN`, `CNBL`, `CNARQ` e `CHT` através do Wizard em 3 etapas, limites de carga, dotação e tipos de validade (Definitivo, Condicional e Provisório). |
| **09** | **Capítulo 6: Mesa de Assinaturas Digitais** | Aplicação da rubrica com geolocalização e carimbo de tempo, garantia de fé pública, hash SHA-256 e validação por QR Code no rodapé. |
| **10** | **Capítulo 7: Guia Rápido & Prazos** | Tabela "O Que Fazer Quando...", prazos fatais da Capitania (30 dias) e canais de apoio técnico. |

---

## ⚓ Conformidade Regulatória

O conteúdo segue rigorosamente as diretrizes fixadas no [`AGENTS.md`](file:///C:/sistema/AGENTS.md):
- **NORMAM-202/DPC**: Embarcações na Navegação Interior.
- **NORMAM-201/DPC**: Embarcações na Navegação de Mar Aberto.
- **RIPEAM**: Regulamento Internacional para Evitar Abalroamentos no Mar.
- **NPCP / NPCF**: Normas e Procedimentos das Capitanias e Delegacias Fluviais.
- Foco em simplicidade operacional, clareza técnica e segurança da navegação (sem desvios burocráticos de ISO/SGQ).
