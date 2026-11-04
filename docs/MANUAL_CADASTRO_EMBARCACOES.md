# Guia Operacional Naval: Cadastro de Embarcações
**Amazon Naval — Engenharia e Serviços Marítimos**  
*Conforme NORMAM-201/DPC, NORMAM-202/DPC, RIPEAM-72 e Regulamentos da Autoridade Marítima*

---

## 1. Visão Geral e Finalidade

O módulo de **Embarcações** é o coração técnico e operacional do sistema. É através dele que todas as especificações técnicas, arqueação, propulsão, dimensões principais e parâmetros de borda livre são centralizados. 

O correto preenchimento garante:
1. **Emissão fidedigna de Certificados Estatutários** (CSN, CEE, Termos de Vistoria).
2. **Cálculos automáticos e geração de Memoriais Técnicos** de Arqueação (NORMAM-201) e Borda Livre (NORMAM-202).
3. **Agendamento preciso de vistorias de campo e análises de planos**, associando automaticamente o Armador/Proprietário responsável.
4. **Alimentação direta do SISAP / Capitania dos Portos**.

---

## 2. Passo a Passo do Cadastro Guiado

Para iniciar um novo cadastro:
1. Acesse o menu lateral esquerdo e clique em **Embarcações**.
2. No topo da tela, clique no botão verde **`+ Nova Embarcação`**.
3. O formulário é dividido em **4 Abas Especializadas**:

```
+-----------------------------------------------------------------------------------+
|  [ Aba 1: Dados Gerais ]  [ Aba 2: Propulsão ]  [ Aba 3: Arqueação ]  [ Aba 4: Borda Livre ] |
+-----------------------------------------------------------------------------------+
```

---

### Aba 1: Dados Gerais e Armador

Esta aba identifica a embarcação perante a Autoridade Marítima e define o seu proprietário legal.

* **Nome da Embarcação (`nome`)**:
  * *Descrição*: Nome oficial registrado no TIE / PRPM da Capitania.
  * *Exemplo*: `B/M AMAZON EXPLORER`
* **Armador / Proprietário (`cliente_id`)**:
  * *Descrição*: Selecione o cliente previamente cadastrado. O sistema vincula automaticamente CNPJ/CPF e dados para faturamento e laudos.
* **Número de Inscrição na Capitania (`numero_inscricao`)**:
  * *Descrição*: Número TIE / PRPM emitido pela Capitania dos Portos / Delegacia / Agência Fluvial.
  * *Formato padrão*: `021-123456-7`
* **Tipo / Atividade da Embarcação (`tipo_embarcacao`)**:
  * *Opções comuns*: Carga Geral, Empurrador, Barcaça, Transporte de Passageiros, Pesca, Apoio Portuário.
* **Porto de Inscrição (`porto_inscricao`)**:
  * *Exemplo*: `Manaus / AM`, `Santarém / PA`, `Belém / PA`.
* **Material do Casco (`material_casco`)**:
  * *Opções*: Aço Naval, Alumínio Naval, Madeira, Fibra de Vidro (PRFV).
* **Ano de Construção / Modificação (`ano_construcao`)**:
  * *Ano de batimento de quilha ou conclusão da obra*.

---

### Aba 2: Dados Técnicos e Propulsão

Define as capacidades de máquinas, potência e limites de lotação e tripulação.

* **Possui Propulsão? (`tem_propulsao`)**:
  * *Sim*: Para empurradores, balsas autopropelidas, barcos a motor, rebocadores.
  * *Não*: Para barcaças sem propulsão, chatas de carga, balsas rebocadas.
* **Tipo de Propulsão / Combustível (`tipo_propulsao`)**:
  * *Opções*: Motor Diesel, Elétrico, Misto.
* **Quantidade e Potência dos Motores (`quantidade_motores` / `potencia_total`)**:
  * Potência nominal expressa em **HP** ou **kW** (conforme plaqueta do fabricante e laudo do engenheiro).
* **Lotação e Capacidade de Pessoas**:
  * **Tripulantes (CTS - Cartão de Tripulação de Segurança)**: Número mínimo de aquaviários exigido pela Capitania.
  * **Passageiros**: Lotação máxima autorizada conforme NORMAM-202.

---

### Aba 3: Arqueação e Dimensões Principais (NORMAM-201)

Dimensões estruturais e de cálculo hidroestático obrigatórias para arqueação e estabilidade.

* **Comprimento Total — LOA (`comprimento_total`)**:
  * *Medida máxima de proa a popa em metros*.
* **Comprimento entre Perpendiculares — LPP (`comprimento_lpp`)**:
  * *Distância entre a perpendicular de vante e ré*.
* **Boca Moldada (`boca`)**:
  * *Maior largura do casco em metros*.
* **Pontal Moldado (`pontal`)**:
  * *Altura medida a meio-navio do fundo do casco até o convés principal*.
* **Calado Máximo de Operação (`calado_maximo`)**:
  * *Imersão máxima autorizada da embarcação carregada*.
* **Arqueação Bruta — AB (`arqueacao_bruta`)**:
  * *Adimensional, calculada conforme regra NORMAM-201*.
* **Arqueação Líquida — AL (`arqueacao_liquida`)**:
  * *Volume útil dos espaços fechados destinados à carga/passageiros*.
* **Porte Bruto — TPB / DWT (`porte_bruto`)**:
  * *Capacidade total de carga útil, combustível e provisões em toneladas*.

---

### Aba 4: Linha de Carga e Borda Livre (NORMAM-202)

Parâmetros de segurança e marcação do Disco de Plimsoll para navegação interior ou costeira.

* **Borda Livre de Verão (`borda_livre_verao`)**:
  * *Distância mínima em milímetros (mm) entre o convés principal e a linha de flutuação máxima autorizada*.
* **Calado de Verão (`calado_verao`)**:
  * *Imersão correspondente à borda livre de verão*.
* **Área de Navegação**:
  * *Navegação Interior 1 (águas abrigadas)*, *Interior 2 (águas expostas/baías)* ou *Mar Aberto (Cabotagem / Longo Curso)*.

---

## 3. Salvamento e Validação

1. Ao preencher os campos necessários, clique no botão verde **`Salvar Embarcação`** no canto inferior direito.
2. O sistema executa validação instantânea dos dados e campos numéricos.
3. Uma notificação de sucesso (*Toast*) confirmará:  
   `"Embarcação cadastrada com sucesso!"`
4. A nova embarcação passará a constar na lista principal com ações rápidas para:
   * **Nova Proposta / Orçamento**
   * **Agendar Vistoria Técnica de Campo**
   * **Abrir Dossiê de Análise de Planos (Ec1/Ec2)**
   * **Visualizar Ficha Técnica Completa**

---

## 4. Recursos Multimídia Disponíveis

* **Vídeo Tutorial em Alta Resolução (1080p c/ Locução em Português)**:
  * Arquivo: [`docs/video_tutorial_cadastro_embarcacao.mp4`](file:///c:/sistema/docs/video_tutorial_cadastro_embarcacao.mp4)
* **Simulador Interativo Web (Player HTML5 c/ Destaques Passo a Passo)**:
  * Arquivo: [`tutorial_cadastro_embarcacao.html`](file:///C:/Users/ronok/.gemini/antigravity/brain/d27d3526-ed01-43d2-95f6-62d87b1448aa/tutorial_cadastro_embarcacao.html)

