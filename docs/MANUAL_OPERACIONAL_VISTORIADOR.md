# Manual Operacional do Vistoriador Naval — ERP Amazon Certificadora

Este diretório contém a documentação técnica oficial e o manual operacional em PDF desenvolvido para capacitar e guiar o **Vistoriador Naval / Perito de Campo** no uso diário do sistema.

---

## 📄 Arquivos do Manual

1. **PDF Oficial (Pronto para Impressão e Estudo):**
   - [`MANUAL_OPERACIONAL_VISTORIADOR.pdf`](file:///C:/sistema/docs/MANUAL_OPERACIONAL_VISTORIADOR.pdf) (10 páginas completas em formato A4)

2. **Código-Fonte Visual em HTML (Editável):**
   - [`MANUAL_OPERACIONAL_VISTORIADOR.html`](file:///C:/sistema/docs/MANUAL_OPERACIONAL_VISTORIADOR.html)

3. **Versão Interativa Dentro do Sistema (ERP Web):**
   - Rota no sistema: `APP_URL . 'vistorias/manual'` (arquivo: [`modules/vistorias/manual.php`](file:///C:/sistema/modules/vistorias/manual.php))

4. **Script Automatizador de Compilação do PDF:**
   - [`gerar_manual_vistoriador_pdf.js`](file:///C:/sistema/docs/gerar_manual_vistoriador_pdf.js)

---

## 🚀 Como Recompilar o PDF

Caso faça alterações no texto ou no layout do arquivo HTML, execute no terminal:

```powershell
cd C:\sistema
node .\docs\gerar_manual_vistoriador_pdf.js
```

O script utiliza o motor headless do Google Chrome / Microsoft Edge com margens calibradas em A4 portrait e renderização fiel de cores.

---

## 📚 Estrutura e Conteúdo do Manual

O manual é 100% focado na rotina do **Vistoriador Naval**, estruturado em 10 páginas didáticas:

| Página | Módulo / Tema | Conteúdo Prático Ensinado |
| :---: | :--- | :--- |
| **01** | **Capa Oficial** | Apresentação institucional, selos NORMAM-201/202/211/212, RIPEAM, NPCP/NPCF e sumário dos 9 capítulos. |
| **02** | **Capítulo 1: Painel do Vistoriador & Escala Diária** | Recepção do agendamento, card de escala imediata, os 6 status de vistoria e rotina matinal. |
| **03** | **Capítulo 2: Modo Campo no Smartphone (PWA Offline)** | Como operar em rios e portos sem sinal 3G/4G (`/campo/`), gravação no IndexedDB local e sincronização anti-perda. |
| **04** | **Capítulo 3: Checklist NORMAM & Categorias Navais** | Enquadramento por categorias da NORMAM-202 ('a' a 'f'), os 4 blocos técnicos (Seco, Flutuando, Borda Livre, Arqueação) e classificação de itens. |
| **05** | **Capítulo 4: O Critério Crítico: A/S vs Comum** | Decisão mandatória de segurança naval: A/S (Antes de Suspender, retenção do navio) vs Comum (prazos de 60 ou 90 dias). |
| **06** | **Capítulo 5: Registro Fotográfico & Evidências** | Enquadramento da Foto Oficial da Embarcação (costado inteiro legível), fotos por item, compressão e exclusão seletiva. |
| **07** | **Capítulo 6: Exigências Avulsas Fora do Catálogo** | Como documentar avarias específicas, redação técnica padronizada, artigo da NORMAM e indicação de A/S. |
| **08** | **Capítulo 7: Vistoria de Retorno & Cumprimento** | Cadeia de rastreabilidade, baixa de pendências herdadas (Cumprida, Parcial, Não Cumprida) e liberação de navio retido. |
| **09** | **Capítulo 8: Conclusão, Assinatura Eletrônica & PDF** | Operador presente a bordo, prazo de validade, assinatura digital credenciada (Hash SHA-256 / carimbo de tempo) e QR Code. |
| **10** | **Capítulo 9: Matriz "O Que Fazer Quando..." & FAQ** | Tabela de resolução rápida para contingências a bordo (sem internet, bateria fraca, armador ausente) e dúvidas operacionais. |

---

## ⚓ Pilares Regulamentares Atendidos

- **NORMAM-201 & NORMAM-202 (DPC)**: Normas da Autoridade Marítima para Embarcações Empregadas na Navegação de Mar Aberto e Interior.
- **NORMAM-211 & NORMAM-212 (DPC)**: Borda Livre e Arqueação de Embarcações.
- **RIPEAM**: Regulamento Internacional para Evitar Abalroamentos no Mar (Luzes, marcas e sinais de manobra).
- **NPCP / NPCF**: Normas e Procedimentos das Capitanias dos Portos e Fluviais regionais.
- **Foco Naval Puro**: Sem interferência de burocracias de auditoria ISO/SGQ.
