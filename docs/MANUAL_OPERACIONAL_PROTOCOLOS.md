# Manual Operacional: Gestão de Protocolos, SISAP e Custódia de Originais
## ERP Amazon Certificadora Naval

> **Documento de Treinamento e Instrução de Trabalho (IT-PROT-01)**  
> **Público-Alvo:** Assistentes Administrativos, Despachantes Náuticos, Secretários e Analistas Navais.  
> **Módulo do Sistema:** `Protocolos Documentais` (`/protocolos`)  
> **Fundamentação Técnica e Operacional:** NORMAM-201, NORMAM-202, NPCP/NPCF (Capitanias, Delegacias e Agências Fluviais da Marinha do Brasil) e Diretrizes de Fé Pública e Custódia Documental Naval.

---

## Sumário
1. [Objetivo e Importância do Módulo](#1-objetivo-e-importância-do-módulo)
2. [Conhecendo a Tela Principal e Indicadores (KPIs)](#2-conhecendo-a-tela-principal-e-indicadores-kpis)
3. [A Estrutura do Dossiê em 6 Abas Especializadas](#3-a-estrutura-do-dossiê-em-6-abas-especializadas)
4. [Passo a Passo Operacional (Do Início ao Encerramento)](#4-passo-a-passo-operacional-do-início-ao-encerramento)
   - [Passo 1: Abertura do Dossiê Naval](#passo-1-abertura-do-dossiê-naval)
   - [Passo 2: Registro de Entrada de Documentos](#passo-2-registro-de-entrada-de-documentos)
   - [Passo 3: Confirmação e Congelamento do Recibo em PDF (Hash SHA-256)](#passo-3-confirmação-e-congelamento-do-recibo-em-pdf-hash-sha-256)
   - [Passo 4: Movimentação de Saída para a Marinha](#passo-4-movimentação-de-saída-para-a-marinha)
   - [Passo 5: Registro do Protocolo Oficial na Capitania (SISAP)](#passo-5-registro-do-protocolo-oficial-na-capitania-sisap)
   - [Passo 6: Acompanhamento de Exigências e Andamento no Órgão](#passo-6-acompanhamento-de-exigências-e-andamento-no-órgão)
   - [Passo 7: Devolução e Baixa da Custódia de Documentos Originais](#passo-7-devolução-e-baixa-da-custódia-de-documentos-originais)
   - [Passo 8: Aceite Digital Externo com o Cliente via WhatsApp](#passo-8-aceite-digital-externo-com-o-cliente-via-whatsapp)
   - [Passo 9: Emissão do PDF Consolidado e Encerramento](#passo-9-emissão-do-pdf-consolidado-e-encerramento)
5. [Guia de Resolução Rápida: "O Que Fazer Quando..."](#5-guia-de-resolução-rápida-o-que-fazer-quando)
6. [Checklist e Mandamentos de Segurança Operacional](#6-checklist-e-mandamentos-de-segurança-operacional)

---

## 1. Objetivo e Importância do Módulo

O **Módulo de Protocolos e Dossiês Navais** foi projetado para eliminar extravios de documentos, desorganização em pastas físicas e desencontros de informações sobre processos náuticos.

Na atividade naval, a empresa lida diariamente com:
* **Documentos originais insubstituíveis dos clientes:** TIE, TIEM, TPP, Notas Fiscais de motores/casco, procurações por instrumento público e livros de registro.
* **Documentação técnica regulamentada:** ARTs de engenharia naval, Memoriais de Cálculo, Planos de Segurança e Laudos de Estabilidade (NORMAM-201/202).
* **Trâmites burocráticos oficiais:** Protocolos de entrada no SISAP da Marinha do Brasil, prazos fatais de notas de exigência e validades de protocolos provisórios de navegação.

Com este módulo, cada processo se torna um **Dossiê Digital Único**, auditado com carimbos de tempo, georreferenciamento e assinatura criptográfica SHA-256.

---

## 2. Conhecendo a Tela Principal e Indicadores (KPIs)

Ao acessar no menu lateral **Protocolos Documentais** (`/protocolos`), o operador visualiza imediatamente o panorama geral da empresa:

### 2.1 Cards de Indicadores (KPIs)
No topo da página, os números são atualizados em tempo real:
* **Total de Dossiês:** Volume geral de processos em acompanhamento.
* **Em Preparação:** Processos abertos na empresa que ainda estão reunindo documentos antes do envio à Capitania.
* **Na Marinha:** Processos entregues que estão na fila de análise dos inspetores/oficiais navais.
* **Em Exigência:** Alerta prioritário! Processos que possuem apontamento da Capitania aguardando regularização.
* **Prontos para Retirada:** Documentos já despachados pelo Capitão dos Portos aguardando que o portador da empresa vá buscar.
* **Custódia Pendente (Alerta Vermelho):** Indica exatamente quantos documentos originais físicos de armadores estão no cofre/arquivo da empresa e ainda não foram devolvidos.

### 2.2 Abas de Filtragem Rápida em 1 Clique
Abaixo dos indicadores, use as abas para filtrar instantaneamente a listagem:
`[Todos]` • `[Em Preparação]` • `[Na Marinha]` • `[Em Exigência]` • `[Disponível]` • `[Custódia]` • `[Concluído]`

---

## 3. A Estrutura do Dossiê em 6 Abas Especializadas

Ao abrir um dossiê, a tela é organizada em **6 abas temáticas**:

```
┌────────────────────────────────────────────────────────────────────────────────────────┐
│ [1. Identificação]  [2. Linha do Tempo]  [3. Nova Ação]  [4. SISAP]  [5. Custódia]  [6. Auditoria] │
└────────────────────────────────────────────────────────────────────────────────────────┘
```

1. **Identificação e Vínculos:** Dados cadastrais da embarcação, do armador/cliente, capitania de destino e conexões inteligentes com vistorias ou propostas.
2. **Linha do Tempo e Eventos:** Histórico visual de todas as entradas e saídas de documentos, com recibos e carimbos de hora.
3. **Nova Ação Guiada:** Formulário de registro de movimentação de documentos (com catálogo e travas de custódia).
4. **Trâmite Oficial / SISAP:** Registro do número de protocolo da Marinha, data de entrada e validade provisória.
5. **Custódia de Originais:** Tabela dedicada para controle de devolução de TIEs e notas fiscais originais dos clientes.
6. **Anexos e Auditoria Criptográfica:** Upload de fotos/carimbos do processo e geração de links de aceite via WhatsApp.

---

## 4. Passo a Passo Operacional (Do Início ao Encerramento)

---

### Passo 1: Abertura do Dossiê Naval

Quando o armador ou despachante contratar a regularização de um barco ou serviço náutico:

1. Na listagem principal, clique no botão verde **`+ Novo Dossiê`**.
2. **Embarcação:** Comece a digitar o nome da embarcação ou o número de inscrição. Ao selecionar a embarcação, o sistema **preenche automaticamente o Cliente/Armador** correspondente.
3. **Assunto do Processo:** Utilize os botões de preenchimento rápido (Chips de 1 clique) ou digite:
   - *Inscrição / Registro de Embarcação*
   - *Transferência de Propriedade Náutica*
   - *Emissão / Renovação de Certificado Estatutário (CSN, CNBL, CNARQ)*
   - *Alteração Cadastral / Mudança de Motor*
   - *Cumprimento de Notificação da Capitania*
4. **Unidade Marítima de Destino:** Selecione a capitania ou agência onde o processo vai dar entrada:
   - *Capitania Fluvial da Amazônia Ocidental (CFAOC - Manaus/AM)*
   - *Delegacia Fluvial de Parintins (DelParintins/AM)*
   - *Agência Fluvial de Itacoatiara (AgItacoatiara/AM)*
   - *Outra Capitania ou Delegacia do Brasil*
5. **Vínculos Inteligentes (Opcional, se houver):** Se o processo originou de uma Proposta Comercial aprovada, de uma Análise de Planos de Engenharia ou de uma Vistoria Naval, selecione no campo correspondente para unificar os históricos.
6. Clique em **`Salvar e Abrir Dossiê`**.
   - O sistema gera a numeração oficial automática (ex.: **`AM-PROT-15/26`**).

---

### Passo 2: Registro de Entrada de Documentos

Assim que a empresa receber os documentos físicos ou digitais trazidos pelo armador:

1. Acesse a aba **`Nova Ação Guiada`**.
2. **Tipo de Movimentação:** Escolha **`ENTRADA`**.
3. **Natureza:** Selecione **`RECEBIMENTO DO CLIENTE`** (ou *Retorno do Órgão*).
4. **Origem:** Selecione *Cliente / Armador* e confirme o nome da pessoa que entregou.
5. **Destino:** Selecione *Amazon Certificadora Naval*.
6. **Meio de Envio:** *Presencial*, *Balcão*, *Portador*, *Correios* ou *E-mail*.
7. **Adicionar os Documentos na Tabela:**
   - **Catálogo / Descrição:** Selecione no catálogo (ex.: *TIE / TIEM*, *ART de Engenharia*, *Memorial Descritivo*, *Plano de Arranjo Geral*, *Nota Fiscal de Motor*).
   - **Suporte:** Físico (papel) ou Digital (PDF).
   - **Forma:** Original, Cópia Simples, Cópia Autenticada ou Nato-Digital.
   - **Quantidade:** Número de vias.
   - **🔴 REGRA DE OURO — Requer Devolução de Original (Custódia):**  
     Se o documento entregue for um **ORIGINAL FÍSICO DO CLIENTE** (ex.: TIE original em papel moeda, nota fiscal original, contrato com firma reconhecida), **MARQUE A OPÇÃO "REQUER DEVOLUÇÃO"**!
8. Clique em **`Salvar Rascunho da Movimentação`**.

---

### Passo 3: Confirmação e Congelamento do Recibo em PDF (Hash SHA-256)

1. A movimentação aparecerá na aba **`Linha do Tempo`** com a tarja amarela **`RASCUNHO`**.
2. Faça a conferência de todos os itens cadastrados. (Caso falte algo, você pode editar).
3. Estando tudo correto, clique no botão azul **`Confirmar e Congelar PDF`**.
4. **O que o sistema faz automaticamente:**
   - Registra o evento de forma imutável (não pode mais ser alterado sem rastro).
   - Gera um código hash **SHA-256** exclusivo para a operação.
   - Cria o **Recibo Oficial de Protocolo** em PDF com QR Code de autenticidade.
   - Permite que você imprima ou envie o recibo ao cliente para comprovar formalmente os documentos sob responsabilidade da empresa.

---

### Passo 4: Movimentação de Saída para a Marinha

No dia em que o portador ou despachante da empresa for até a Capitania dos Portos protocolar o processo:

1. Acesse a aba **`Nova Ação Guiada`**.
2. **Tipo de Movimentação:** Escolha **`SAÍDA`**.
3. **Natureza:** Selecione **`ENVIO AO ÓRGÃO (CAPITANIA / DELEGACIA)`**.
4. **Origem:** *Amazon Certificadora Naval*.
5. **Destino:** Selecione a Capitania de destino (ex.: *Capitania Fluvial da Amazônia Ocidental*).
6. **Portador:** Preencha o nome do despachante ou funcionário que está levando a pasta física.
7. Adicione os itens documentais que estão dentro da pasta entregue ao órgão.
8. Salve e clique em **`Confirmar e Congelar PDF`**.

---

### Passo 5: Registro do Protocolo Oficial na Capitania (SISAP)

Assim que o despachante náutico retornar com o carimbo e o comprovante do protocolo da Marinha:

1. Acesse a aba **`Trâmite Oficial / SISAP`**.
2. Preencha os dados do protocolo da Capitania:
   - **Nº do Processo / SISAP:** Digite o número oficial gerado pela Marinha (ex.: `23000.004521/2026-89`).
   - **Data do Atendimento:** Data e horário exatos em que a Capitania recebeu o processo.
   - **Validade do Protocolo Provisório:** Se a Capitania concedeu prazo provisório para a embarcação navegar enquanto o documento definitivo é confeccionado, informe a data de vencimento.
3. Clique em **`Registrar Atendimento no Órgão`**.
   - O status do dossiê muda imediatamente para **`PROTOCOLADO`**.
   - O sistema monitora a validade do protocolo e emitirá avisos automáticos no painel quando faltarem **15 dias para vencer**.

---

### Passo 6: Acompanhamento de Exigências e Andamento no Órgão

Durante a análise pelos inspetores navais da Capitania:

1. Acesse a aba **`Trâmite Oficial / SISAP`** e use a seção **Registrar Andamento**:
   - Se o processo estiver aguardando vistoriador: Mude para **`EM ANÁLISE NO ÓRGÃO`**.
   - Se a Capitania emitir nota de exigência: Mude para **`EM EXIGÊNCIA`** e descreva o motivo no campo de texto (ex.: *Capitania solicitou atualização do cálculo de arqueação conforme NORMAM-202*).
   - Quando a exigência for cumprida: Registre uma nova movimentação de saída com natureza *Cumprimento de Exigência*.
   - Quando o documento estiver assinado pelo Capitão dos Portos: Mude para **`À DISPOSIÇÃO / RETIRADO`**.

---

### Passo 7: Devolução e Baixa da Custódia de Documentos Originais

Quando o processo na Marinha terminar e os documentos originais do armador precisarem ser devolvidos:

1. Acesse a aba **`Custódia de Originais`**.
2. Você verá todos os documentos originais que foram marcados com a trava de custódia no Passo 2.
3. No momento em que o armador ou o representante dele vier retirar o TIE ou notas fiscais físicas:
   - Localize o item na tabela e clique no botão **`Registrar Baixa / Devolução`**.
   - O sistema carimba a data, o horário exato e o nome do operador que entregou o documento.
   - O alerta vermelho de custódia pendente é zerado com total segurança jurídica para a empresa.

---

### Passo 8: Aceite Digital Externo com o Cliente via WhatsApp

Ao entregar os certificados emitidos e os documentos de volta para o cliente, você **não precisa recolher assinatura em papel**:

1. Crie uma movimentação de **SAÍDA** com natureza **`ENTREGA AO CLIENTE`** e confirme.
2. Na linha do tempo do evento confirmado, clique em **`Gerar Link de Aceite Digital`**.
3. O sistema abre uma janela com:
   - **Link de Confirmação Pública** (protegido por token criptografado).
   - Botão **`Copiar Link`**.
   - Botão verde **`Enviar pelo WhatsApp`** com mensagem pronta:
     > *"Olá! Segue o comprovante de entrega de documentos da embarcação [Nome] pela Amazon Certificadora Naval. Por favor, confirme o recebimento no link: [URL]"*
4. **O que o cliente faz no celular dele:**
   - Ele abre o link pelo WhatsApp (sem precisar fazer login nem ter cadastro no sistema).
   - Vê a lista detalhada de tudo o que foi entregue.
   - Preenche seu Nome e CPF e clica no botão verde **`Confirmar Recebimento`**.
   - O sistema grava a confirmação com endereço IP, navegador, data e hora com plena validade legal.

---

### Passo 9: Emissão do PDF Consolidado e Encerramento

Com o processo finalizado, certificados entregues e custódia de originais baixada:

1. **Anexar Comprovantes Digitais:** Na aba **`Anexos e Auditoria`**, anexe a foto da folha de rosto carimbada pela Capitania ou cópia em PDF do certificado novo (arquivos de até 15 MB protegidos contra substituição indevida).
2. **Gerar o PDF Consolidado do Dossiê:** No topo da página, clique no botão **`PDF Consolidado`**.
   - O sistema gera um relatório executivo completo contendo:
     - Capa institucional da Amazon Certificadora Naval.
     - Resumo cadastral do armador e embarcação.
     - Histórico cronológico de todos os eventos com hashes SHA-256.
     - Número oficial do SISAP e unidade da Marinha.
     - Tabela de custódia com baixa de devolução assinada.
3. **Encerrar o Processo:** Na aba *Trâmite Oficial*, clique no botão verde **`Encerrar Dossiê`**. O processo passa para a aba de concluídos e o ciclo está fechado com 100% de sucesso.

---

## 5. Guia de Resolução Rápida: "O Que Fazer Quando..."

| Situação da Rotina | O que fazer no sistema |
| :--- | :--- |
| **O cliente me entregou o TIE original em mãos no escritório.** | Registre uma Movimentação de **Entrada**, selecione *TIE* e marque obrigatoriamente a caixinha **`Requer devolução de original (Custódia)`**. |
| **A Capitania devolveu o processo com exigência.** | Acesse a aba *Trâmite Oficial*, altere o status para **`EM EXIGÊNCIA`** e descreva a pendência. O sistema avisará os responsáveis. |
| **O protocolo provisório da Marinha vai vencer em breve.** | Consulte a coluna de Validade do Protocolo. O ERP marca em **amarelo/vermelho** e dispara alertas para requerer prorrogação na Capitania antes dos 15 dias fatais. |
| **O armador afirma que não recebeu os certificados da embarcação.** | Abra a aba *Linha do Tempo*, localize o evento de Entrega ao Cliente e envie o **Link de Aceite via WhatsApp** ou consulte o carimbo com IP e CPF de quem deu o aceite digital. |
| **Errei um documento em uma movimentação que já confirmei.** | Documentos confirmados são imutáveis (congelados). Clique em **`Retificar Movimentação`**; o sistema criará um evento corretivo referenciando o anterior sem apagar a trilha de auditoria. |

---

## 6. Checklist e Mandamentos de Segurança Operacional

- [ ] **1. Identificação Rápida:** Vincule sempre a Embarcação primeiro; o Cliente/Armador será preenchido automaticamente.
- [ ] **2. Unidade da Marinha:** Nunca deixe a Unidade Marítima em branco. Especifique se é Capitania de Manaus, Delegacia de Parintins, Agência de Itacoatiara, etc.
- [ ] **3. Trava de Custódia:** Todo documento físico pertencente ao cliente deve ter a opção *Requer Devolução* marcada para que a empresa nunca seja cobrada indevidamente por papéis perdidos.
- [ ] **4. Número SISAP:** Registrou na Capitania? Pegue o protocolo e cadastre o número do processo no mesmo dia para garantir o acompanhamento de prazos.
- [ ] **5. Baixa na Entrega:** Nunca devolva um TIE ou documento original ao cliente sem clicar em *Registrar Baixa* na aba Custódia de Originais.
- [ ] **6. Aceite Digital via WhatsApp:** Use o botão de envio direto pelo WhatsApp para formalizar a entrega de certificados em menos de 1 minuto pelo celular do armador.
