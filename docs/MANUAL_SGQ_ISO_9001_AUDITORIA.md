# 📘 Dossiê Técnico do SGQ & Manual de Auditoria ISO 9001:2015 e NORMAM
**Organização:** Amazon Certificadora de Embarcações  
**Referenciais Normativos:** ABNT NBR ISO 9001:2015 · NORMAM-201/DPC · NORMAM-202/DPC  
**Documento:** DT-SGQ-2026-V1  
**Destinatários:** Auditores Líderes de Organismos de Certificação Credenciados (Inmetro/Cgcre), Peritos e Inspetores Navais da Autoridade Marítima (DPC/Marinha do Brasil), Responsável Técnico e Alta Direção.

---

## 🏛️ 1. Apresentação e Escopo do Sistema de Gestão da Qualidade (ISO 4.3 & NORMAM)

Este documento comprova, mediante auditoria de processos e código-fonte, a conformidade integral do software ERP da **Amazon Certificadora** com os requisitos normativos da **ABNT NBR ISO 9001:2015** e com as normas regulamentadoras da **Diretoria de Portos e Costas (DPC)** da Marinha do Brasil.

### 1.1 Escopo Certificado dos Serviços
O Sistema de Gestão da Qualidade (SGQ) abrange todos os processos de:
1. **Engenharia e Análise de Planos:** Análise estrutural, cálculos de estabilidade intacta e em avaria, borda livre, arqueação e plano de segurança/combate a incêndio para embarcações fluviais e marítimas.
2. **Vistorias Estatutárias de Campo:** Inspeções navais iniciais, intermediárias, de renovação e re-inspeções (A/S e cumprimento de exigências) com suporte a PWA em modo desconectado (offline).
3. **Emissão de Certificados e Documentos Oficiais:** Emissão, controle e rastreamento de CSN (Certificado de Segurança da Navegação), CNBL (Certificado Nacional de Borda Livre), CNARQ (Certificado Nacional de Arqueação), LP (Licença Provisória), LC (Laudo Pericial) e CHT (Certificado de Homologação de Tirante).
4. **Ouvidoria, Gestão de Não Conformidades e Melhoria Contínua:** Tratamento formal de manifestações do armador/cliente e planos de ação corretiva 5W2H.

---

## 🎯 2. Política da Qualidade e Objetivos Mensuráveis (ISO 5.2 & 6.2)

### 2.1 Política da Qualidade
> *"A Amazon Certificadora compromete-se a prestar serviços técnicos de engenharia naval, análise estrutural de planos e vistorias de conformidade com excelência, ética, independência e pleno atendimento aos requisitos regulatórios da Autoridade Marítima Brasileira (DPC/NORMAM) e da norma ISO 9001:2015, promovendo a segurança da vida humana no mar e vias navegáveis interiores, a prevenção da poluição hídrica e a melhoria contínua da satisfação dos nossos clientes."*

### 2.2 Objetivos da Qualidade Mensuráveis (Calculados pelo ERP)
| Objetivo da Qualidade | Indicador no Sistema | Meta Estabelecida | Cláusula ISO |
| :--- | :--- | :--- | :--- |
| **Rigor e Eficácia das Vistorias** | Taxa de Retrabalho Técnico (Bloqueios A/S) | **< 5,0%** das vistorias | ISO 9.1 & NORMAM |
| **Agilidade Operacional** | Lead Time Médio (Agendamento até Emissão) | **≤ 10 dias úteis** | ISO 9.1 |
| **Qualidade Percebida pelo Cliente** | Índice Ponderado de Satisfação / NPS | **NPS ≥ 50 pontos** (Zona de Excelência) | ISO 9.1.2 |
| **Qualificação do Corpo Técnico** | Vistoriadores Aptos perante DPC e CREA/CFT | **100%** dos vistoriadores escalados | ISO 7.2 |
| **Controle de Saídas Não Conformes** | Resolução Eficaz de RNCs com metodologia 5W2H | **100%** das RNCs encerradas eficazes | ISO 8.7 & 10.2 |

---

## 🗺️ 3. Macroprocesso Operacional da Qualidade (ISO 4.4)

O ERP implementa um fluxo linear com bloqueios programados que impedem o avanço de etapas caso os requisitos da qualidade não sejam satisfeitos:

```
[1. COMERCIAL] ➔ [2. ANÁLISE DE PLANOS] ➔ [3. AGENDAMENTO] ➔ [4. VISTORIA CAMPO] ➔ [5. AUDITORIA TÉCNICA] ➔ [6. EMISSÃO & MELHORIA]
     │                      │                    │                   │                     │                        │
Trava Prontidão        Conformidade        Trava de Competência   Checklist NORMAM     Trava A/S Bloqueante       Protocolos & Ouvidoria
Técnica Embarcação       NORMAM             Vistoriador CREA/DPC   Fotos com Metadados   Disparo Auto de RNC      Satisfação (ISO 9.1.2)
   (ISO 8.2)           (ISO 8.5)                 (ISO 7.2)              (ISO 8.5)             (ISO 8.7)                (ISO 10.2)
```

---

## 🔍 4. Matriz de Auditoria: Cláusula por Cláusula no ERP

### Cláusula 6.1 — Ações para Abordar Riscos e Oportunidades
- **Onde fica no ERP:** Menu Lateral ➔ `QUALIDADE (SGQ)` ➔ **Gestão de Riscos (ISO 6.1)** (`/sgq/riscos`).
- **Base de Dados:** Tabela `sgq_matriz_riscos` e sequencial `RSK-AAAA-NNNN`.
- **Como funciona:**
  - O sistema calcula a criticidade através da matriz $5 \times 5$ ($\text{Probabilidade} \times \text{Impacto}$).
  - A criticidade resultante categoriza o risco em `BAIXO`, `MEDIO`, `ALTO` ou `CRITICO`.
  - Todo risco requer plano de mitigação compulsório, nome do responsável e prazo limite de revisão.
- **Riscos Regulatórios Padrão Cadastrados no Banco:**
  1. `RSK-2026-001` (Operação de Campo): Indisponibilidade de sinal de internet em terminais remotos na Amazônia (Mitigação: PWA com armazenamento IndexedDB local e sincronização resiliente).
  2. `RSK-2026-002` (Competência Técnica): Escalação de vistoriador com credencial profissional vencida (Mitigação: Trava automatizada no agendamento com erro HTTP 400).
  3. `RSK-2026-003` (Controle Metrológico): Uso de medidor de espessura com calibração RBC vencida (Mitigação: Plano anual de calibração em laboratório credenciado).
  4. `RSK-2026-004` (Regulatório/NORMAM): Alteração de critérios da Marinha sem atualização de checklist (Mitigação: Revisão mensal do corpo técnico e atualização imediata do checklist digital).
  5. `RSK-2026-005` (Estratégico/Comercial): Crescimento de comboios fluviais no Arco Norte (Oportunidade de expansão técnica).

---

### Cláusula 7.2 — Competência Técnica e Credenciamento
- **Onde fica no ERP:** Menu Lateral ➔ `CADASTROS` ➔ **Usuários** (`/usuarios`).
- **Base de Dados:** Tabela `usuarios` (colunas: `status_sgq`, `credencial_marinha_numero`, `credencial_marinha_validade`, `registro_conselho_tipo`, `registro_conselho_numero`, `registro_conselho_validade`, `escopo_habilitacao`).
- **Validador no Código:** Função `vistoriadorElegivelParaAgendamento()` em `includes/sgq.php`.
- **Como o auditor testa na prática:**
  - Ao agendar uma vistoria em `/agendamentos`, o sistema checa a data da vistoria contra a validade da Portaria da Marinha e do CREA/CFT do vistoriador selecionado.
  - Se a credencial expirar antes da data da vistoria, o agendamento é **imediatamente rejeitado** com mensagem explicativa e código de violação da ISO 7.2.
  - Vistoriadores com `status_sgq` em `SUSPENSO_RECICLAGEM` ou `DESQUALIFICADO` não podem ser escalados.

---

### Cláusula 7.5 — Informação Documentada e Rastreabilidade
- **Onde fica no ERP:** Menu Lateral ➔ `QUALIDADE (SGQ)` ➔ **Trilha de Auditoria** (`/sgq/auditoria`).
- **Base de Dados:** Tabela `sgq_auditoria_cadastral` (colunas: `id`, `entidade_tipo`, `entidade_id`, `acao`, `dados_anteriores`, `dados_posteriores`, `campos_alterados`, `motivo_justificativa`, `usuario_nome`, `ip_origem`, `criado_em`).
- **Validador no Código:** Função `sgqRegistrarAuditoriaCadastral()` em `includes/sgq.php`.
- **Como funciona:**
  - Qualquer criação, modificação ou inativação em entidades críticas (`EMBARCACAO`, `CLIENTE`, `ARMADOR`, `DESPACHANTE`, `USUARIO`) gera um registro imutável no banco.
  - O sistema grava um **comparativo Antes vs Depois** em formato JSON estruturado, guardando IP, nome do responsável e motivo da alteração.
  - O auditor pode clicar no botão **"Comparar"** em qualquer linha da tabela para ver exatamente o que mudou lado a lado.

---

### Cláusula 8.2 — Requisitos Relativos a Produtos e Serviços
- **Onde fica no ERP:** Menu Lateral ➔ `COMERCIAL` ➔ **Propostas** (`/comercial`).
- **Validador no Código:** Função `embarcacaoValidarProntidaoComercial()` em `includes/sgq.php`.
- **Como funciona:**
  - A ISO 8.2 exige que a organização assegure que tem capacidade para atender aos requisitos antes de se comprometer a fornecer serviços.
  - O ERP proíbe emitir proposta comercial se a embarcação não possuir dados técnicos indispensáveis:
    - Tipo de embarcação e nome;
    - Comprimento total ou de casco;
    - Boca moldada ou máxima;
    - Pontal moldado;
    - Arqueação Bruta (AB);
    - Declaração de propulsão (se propulsada: fabricante, modelo e potência do motor).
  - No formulário de cadastro de embarcações (`/embarcacoes/form`), os campos exibem asteriscos vermelhos (*) e badge de aviso da ISO alertando os campos mandatórios.

---

### Cláusula 8.7 — Controle de Saídas Não Conformes (Trava A/S)
- **Onde fica no ERP:** Menu Lateral ➔ `OPERAÇÃO` ➔ **Relatórios** (`/documentacao/aprovacao_relatorios`).
- **Validador no Código:** Funções `encaminharRelatorioParaRetornoAS()` em `includes/functions.php` e `sgqGerarRncAutomaticaPorRetornoAS()` em `includes/sgq.php`.
- **Como funciona:**
  - Quando um vistoriador aponta uma exigência impeditiva (A/S - Atestado de Segurança), a embarcação **não pode ser certificada**.
  - O Responsável Técnico aciona a decisão de **"Retorno A/S"**.
  - Automaticamente:
    1. A emissão do certificado CSN é bloqueada no ERP;
    2. O sistema dispara a abertura de uma **RNC Oficial** (ex: `RNC-2026-0004`) com severidade `CRITICA_IMPEDITIVA`;
    3. Cria um **Plano de Ação 5W2H** compulsório para a realização da nova vistoria de verificação in loco;
    4. Gera pendência auditável na fila operacional do Administrador.

---

### Cláusula 9.1 & 9.1.2 — Medição, Indicadores e Satisfação do Cliente
- **Onde fica no ERP:** Menu Lateral ➔ `QUALIDADE (SGQ)` ➔ **Indicadores SGQ** (`/sgq/indicadores`) e **Portal do Cliente** ➔ **Ouvidoria & Qualidade** (`/portal/ouvidoria`).
- **Base de Dados:** Tabelas `vistorias`, `certificados_csn`, `sgq_satisfacao_clientes` e `sgq_nao_conformidades`.
- **Motor de Cálculo:** Função `sgqObterIndicadores()` em `includes/sgq.php`.
- **Fórmulas e Métricas Implementadas:**
  1. **Taxa de Retrabalho Técnico:**
     $$\text{Taxa} = \left( \frac{\text{Total de Vistorias com Retorno A/S}}{\text{Total Geral de Vistorias Realizadas}} \right) \times 100$$
     *(Meta da Qualidade: inferior a 5,0%)*
  2. **Lead Time Médio Operacional:**
     $$\text{Lead Time} = \frac{\sum (\text{Data de Emissão do Certificado} - \text{Data de Agendamento da Vistoria})}{\text{Total de Certificados Emitidos}}$$
     *(Meta da Qualidade: até 10 dias úteis)*
  3. **Índice Ponderado de Satisfação (ISO 9.1.2):**
     $$\text{Nota Ponderada} = (\text{Qualidade Técnica} \times 0,40) + (\text{Cumprimento de Prazo} \times 0,35) + (\text{Atendimento Comercial} \times 0,25)$$
     $$\text{NPS} = \% \text{Promotores (notas 9-10)} - \% \text{Detratores (notas 0-6)}$$
     *(Meta da Qualidade: NPS na Zona de Excelência $\ge 50$ pontos)*

---

### Cláusula 10.2 — Não Conformidades e Ações Corretivas (5W2H)
- **Onde fica no ERP:** Menu Lateral ➔ `QUALIDADE (SGQ)` ➔ **Não Conformidades (RNC)** (`/sgq/nao-conformidades`).
- **Base de Dados:** Tabelas `sgq_nao_conformidades` e `sgq_planos_acao`.
- **Ciclo de Vida Formal:**
  `ABERTA` ➔ `EM_ANALISE_CAUSA` ➔ `PLANO_ACAO_DEFINIDO` ➔ `EM_EXECUCAO` ➔ `AGUARDANDO_EFICACIA` ➔ `ENCERRADA_EFICAZ`.
- **Metodologia 5W2H Nativa:**
  Para cada RNC, o Responsável da Qualidade cadastra uma ou mais ações detalhando:
  - **What (O que fazer):** Ação prática a ser realizada.
  - **Why (Por que fazer):** Justificativa da ação contra a causa-raiz.
  - **Where (Onde fazer):** Local ou processo em que será executado.
  - **Who (Quem fará):** Nome do responsável designado.
  - **When (Quando fará):** Prazo limite obrigatório.
  - **How (Como executar):** Procedimento operacional.
  - **How Much (Quanto custa):** Custo estimado para resolução.
- **Canal de Ouvidoria do Cliente:**
  O cliente acessa o Portal (`/portal/ouvidoria`) e registra manifestações de tipo:
  - *Reclamação Formal (ISO 10.2)*;
  - *Sugestão de Melhoria (ISO 10.3)*;
  - *Dúvida Técnica*;
  - *Elogio*.
  Cada manifestação gera protocolo oficial, entra diretamente no fluxo do SGQ e exibe o parecer técnico da certificadora e o status das ações para o cliente.

---

## 💻 5. Roteiro Prático de Demonstração para o Auditor

Quando o Auditor da Certificadora ou Fiscal da DPC solicitar a auditoria do sistema, siga este roteiro de 5 passos no computador:

### Passo 1: Apresentar o Manual da Qualidade e a Política no Sistema
- Clique no menu lateral: **QUALIDADE (SGQ) ➔ Manual & Política SGQ** (`/sgq/manual`).
- **O que mostrar:** A Política da Qualidade assinada, o Escopo dos serviços navais certificados, o Macrofluxo de 6 etapas e a Matriz de Evidências com links para cada módulo.

### Passo 2: Mostrar os Indicadores e Análise Crítica da Direção
- Clique no menu lateral: **QUALIDADE (SGQ) ➔ Indicadores SGQ** (`/sgq/indicadores`).
- **O que mostrar:** O painel analítico com a Taxa de Retrabalho Técnico (com meta de 5%), o Lead Time em dias e o Índice de Satisfação/NPS, além do gráfico de status de Não Conformidades.

### Passo 3: Demonstrar a Matriz de Riscos Operacionais
- Clique no menu lateral: **QUALIDADE (SGQ) ➔ Gestão de Riscos (ISO 6.1)** (`/sgq/riscos`).
- **O que mostrar:** Os riscos regulatórios cadastrados (como o risco de conectividade em área remota, credencial de vistoriador e calibração RBC de ultrassom), seus níveis de severidade e seus planos de mitigação ativos.

### Passo 4: Demonstrar a Trilha de Auditoria e Imutabilidade dos Dados
- Clique no menu lateral: **QUALIDADE (SGQ) ➔ Trilha de Auditoria** (`/sgq/auditoria`).
- **O que mostrar:** Clique em **"Comparar"** em qualquer registro para abrir a tela que demonstra o antes e o depois de um cadastro em formato estruturado com carimbo de data, hora e IP de origem.

### Passo 5: Demonstrar a Ouvidoria e o Tratamento 5W2H
- Clique no menu lateral: **QUALIDADE (SGQ) ➔ Reclamações / Ouvidoria** (`/sgq/nao-conformidades?origem=RECLAMACAO_CLIENTE`).
- **O que mostrar:** Abra uma das reclamações do cliente (ex: `RNC-2026-0003`). Mostre o relato do cliente, o campo de **Diagnóstico da Causa do Problema** preenchido pela equipe e a tabela com o **Plano de Ação 5W2H** com responsável e prazo.

---

## 🗄️ 6. Especificação Técnica de Banco de Dados (Estruturas Físicas)

| Tabela no MySQL | Função no SGQ | Cláusula ISO |
| :--- | :--- | :--- |
| `sgq_matriz_riscos` | Registro matricial 5x5 de riscos e oportunidades e ações preventivas | ISO 6.1 |
| `usuarios` (`status_sgq`, `credencial_*`, `registro_*`) | Qualificação de vistoriadores e travas de credencial da Marinha e CREA/CFT | ISO 7.2 |
| `sgq_auditoria_cadastral` | Trilha de auditoria imutável (antes vs depois) de mudanças críticas | ISO 7.5 |
| `embarcacoes` | Prontidão cadastral obrigatória de características técnicas mínimas | ISO 8.2 & NORMAM |
| `vistorias` (`status = 'RETORNO_AS'`) | Bloqueio automático de certificação por exigência impeditiva A/S | ISO 8.7 |
| `sgq_nao_conformidades` | Cadastro e ciclo de vida de RNCs internas e externas da ouvidoria | ISO 8.7 & 10.2 |
| `sgq_planos_acao` | Planos de ação corretivos e preventivos no padrão 5W2H | ISO 10.2 |
| `sgq_satisfacao_clientes` | Avaliações de satisfação e pesquisas NPS no Portal do Cliente | ISO 9.1.2 |
| `sequenciais_documentos` | Controle sequencial oficial de protocolos imutáveis (RNC, RSK, CSN...) | ISO 7.5 & 8.5 |

---

## ✅ 7. Conclusão da Auditoria do Sistema

O software ERP da **Amazon Certificadora** encontra-se em conformidade de nível corporativo (*Enterprise Grade*) com os requisitos da **ISO 9001:2015** e da **NORMAM (DPC)**. As regras não dependem apenas de procedimentos manuais em papel, mas estão **programadas e travadas no código-fonte e no banco de dados**, assegurando que nenhuma certificação seja emitida sem conformidade técnica e que todos os dados permaneçam auditáveis e rastreáveis.
