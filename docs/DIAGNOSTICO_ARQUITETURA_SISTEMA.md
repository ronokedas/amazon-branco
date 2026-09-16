# RAIO-X COMPLETO E DIAGNÓSTICO ARQUITETURAL DO SISTEMA

> **Documento de Auditoria Técnica e Mapa de Dependências**  
> **Data:** Setembro de 2026  
> **Escopo:** Leitura e análise estática profunda do código-fonte, rotas, banco de dados MySQL, PWA de campo e microsserviços. Nenhuma alteração em código funcional foi realizada nesta etapa.

---

## ÍNDICE
1. [Visão Geral e Métricas Gerais da Base de Código](#1-visão-geral-e-métricas-gerais)
2. [Inventário e Diagnóstico Individual dos 32 Módulos](#2-inventário-e-diagnóstico-individual-dos-32-módulos)
3. [Mapa Geral de Dependência Topológica (Da Base ao Topo)](#3-mapa-geral-de-dependência-topológica)
4. [Diagnóstico Sem Filtros: O Tamanho Real do Problema](#4-diagnóstico-sem-filtros-o-tamanho-real-do-problema)
   - 4.1 Problemas Graves de Estrutura de Pastas e Duplicação de Código
   - 4.2 Problemas Críticos de Banco de Dados e Modelagem
   - 4.3 Arquivos Monolíticos de Alto Risco de Manutenção
   - 4.4 Inconsistências de Nomenclatura e Código Órfão/Morto
   - 4.5 Brechas de Segurança e Fragilidades de Validação
5. [Ordem Recomendada de Refatoração e Próximos Passos](#5-ordem-recomendada-de-refatoração)

---

## 1. VISÃO GERAL E MÉTRICAS GERAIS

- **Total de Módulos Identificados em `modules/`:** 32 módulos.
- **Total de Arquivos PHP em `modules/`:** ~160 arquivos (~54.200 linhas de código).
- **Total de Bibliotecas / Includes Centrais em `includes/`:** 32 arquivos (~31.800 linhas).
- **Total de Tabelas no Banco de Dados MySQL (`erp_sistema`):** 87 tabelas ativas.
- **Migrations Versionadas em `migrations/`:** 108 arquivos SQL numerados.
- **Bateria de Testes Automatizados em `tests/`:** 44 scripts de teste funcional/unitário.
- **Aplicações Satélites:**
  - PWA Offline de Campo (`campo/` estático compilado + `pwa-campo/` código-fonte Vite/TS).
  - MinIO S3 Object Storage (`erp-storage` e `erp-campo-private`).
  - Worker assíncrono PHP (`sistema-worker-1`).

---

## 2. INVENTÁRIO E DIAGNÓSTICO INDIVIDUAL DOS 32 MÓDULOS

Para cada um dos 32 módulos, a análise avalia:
- **O que faz**
- **Módulos dos quais depende**
- **Módulos que dependem dele**
- **Validação de dados atual**
- **Bugs, inconsistências e itens incompletos**
- **Avaliação da nomenclatura**

---

### 2.1. `login`
- **O que faz:** Autentica usuários internos do ERP, gerencia sessão PHP, redireciona o usuário para o dashboard (ou para rota com retorno guardado) e efetua encerramento de sessão (logout).
- **Depende de:** `usuarios`, `config.php`, `includes/auth.php`, `includes/functions.php`.
- **Módulos que dependem dele:** Todos os módulos internos do ERP (31 módulos exigem sessão válida gerada aqui).
- **Validação de dados:** Básica. Sanitiza o e-mail e confere se usuário existe e senha confere com `password_verify()`. **Atenção:** NÃO possui proteção de rate limiting contra força bruta (diferente da API de campo) e **NÃO possui token CSRF** no formulário de login (`modules/login/index.php`).
- **Bugs/Inconsistências:** Apenas guarda rota de retorno para `minhas-assinaturas`; se o usuário deslogado tentar acessar qualquer outra tela (ex.: uma vistoria ou proposta específica), o redirecionamento pós-login sempre o joga para a raiz da dashboard, perdendo o link de trabalho.
- **Nomenclatura:** Arquivo único `index.php`. Nomes de campos e rotas claros e intuitivos.

---

### 2.2. `perfil`
- **O que faz:** Permite ao colaborador autenticado visualizar seus dados cadastrais, alterar sua senha de acesso e atualizar telefone e assinatura digital.
- **Depende de:** `usuarios`, `includes/auth.php`, `includes/functions.php`.
- **Módulos que dependem dele:** Nenhum módulo operacional depende dele diretamente.
- **Validação de dados:** Sim, valida se a nova senha possui tamanho mínimo, se a confirmação confere e valida CSRF.
- **Bugs/Inconsistências:** Não bloqueia tentativa de trocar e-mail para um já existente caso o campo seja editado; sem feedback visual assíncrono durante upload de rubrica em conexões móveis.
- **Nomenclatura:** Arquivo único `index.php`, nomes claros.

---

### 2.3. `usuarios`
- **O que faz:** Gestão completa de usuários internos (CRUD), associação a múltiplos perfis/cargos (`ADMIN`, `VENDEDOR`, `VISTORIADOR`, `ANALISTA`), permissões granulares (`usuario_permissoes`) e vinculação a escritórios físicos (`usuario_escritorios`).
- **Depende de:** `configuracoes` (escritórios), `includes/auth.php`, `includes/functions.php`.
- **Módulos que dependem dele:** `agendamentos`, `comercial`, `vistorias`, `analises_planos`, `protocolos`, `financeiro`, `feedback`, `notificacoes`, `responsaveis_assinatura`.
- **Validação de dados:** Boa. Valida formato de e-mail, unicidade de e-mail no banco, tamanho de senha, obrigatoriedade de ao menos um perfil atribuído e token CSRF.
- **Bugs/Inconsistências:** A inativação lógica (`ativo = 0`) não invalida sessões PHP que já estejam abertas em outros navegadores; na edição de permissões, a deleção e reinserção direta em `usuario_permissoes` pode zerar regras personalizadas se o salvamento falhar a meio caminho.
- **Nomenclatura:** Muito boa. Tabelas `usuarios`, `usuario_perfis`, `usuario_permissoes`, `usuario_escritorios` bem nomeadas.

---

### 2.4. `configuracoes`
- **O que faz:** Central de configurações administrativas do ERP: dados institucionais da empresa naval, escritórios operacionais, metas financeiras globais e por escritório, catálogo da NORMAM-202 (itens e categorias de checklist de vistoria), backup do banco de dados e exportações em lote.
- **Depende de:** `usuarios`, `includes/auth.php`, `includes/functions.php`, `includes/xlsx_export.php`.
- **Módulos que dependem dele:** `comercial` (escritórios/metas), `financeiro` (escritórios), `vistorias` e `campo` (itens NORMAM-202).
- **Validação de dados:** Parcial. Backup e NORMAM-202 possuem validações rígidas de permissão `ADMIN` e integridade de formulário. No entanto, a tela `basicas.php` salva chaves genéricas na tabela `configuracoes` sem tipagem estrita.
- **Bugs/Inconsistências:** Mistura de conceitos gritante: na mesma pasta convivem rotinas de infraestrutura pura (`backup.php`), parametrizações financeiras (`financeiro.php`) e regras de inspeção naval (`normam202.php`). Além disso, o catálogo NORMAM-202 está dividido entre esta pasta e o módulo órfão `exigencias_catalogo`.
- **Nomenclatura:** Confusa. `configuracoes/geral` foi redirecionada para `configuracoes/financeiro` por remendo de rota em `index.php`.

---

### 2.5. `exigencias_catalogo`
- **O que faz:** Possui apenas o arquivo `actions.php` (144 linhas) para criação/edição rápida de itens do catálogo NORMAM-202.
- **Depende de:** Tabela `exigencias_catalogo`, `includes/auth.php`.
- **Módulos que dependem dele:** Nenhum (o sistema usa `configuracoes/normam202`).
- **Validação de dados:** Básica (verifica se código e descrição foram preenchidos).
- **Bugs/Inconsistências:** **MÓDULO FANTASMA / ÓRFÃO.** Não tem `index.php`, não tem interface gráfica e não está mapeado no roteador principal `index.php`. É código redundante mantido solto na raiz de `modules/`.
- **Nomenclatura:** Redundante em relação a `normam202`.

---

### 2.6. `clientes`
- **O que faz:** CRUD principal de pessoas físicas e jurídicas atendidas pela empresa naval (proprietários, armadores, despachantes, estaleiros). Cadastra CPF/CNPJ, endereços, contatos e vincula embarcações atendidas.
- **Depende de:** `embarcacoes`, `includes/cliente_vinculos.php`, `includes/auth.php`, `includes/functions.php`.
- **Módulos que dependem dele:** Praticamente todos: `embarcacoes`, `comercial`, `agendamentos`, `vistorias`, `analises_planos`, `protocolos`, `financeiro`, `portal`, `portal_clientes`.
- **Validação de dados:** Forte. Valida matematicamente dígitos verificadores de CPF e CNPJ, formatos de e-mail, duplicidade de documento e validação de token CSRF.
- **Bugs/Inconsistências:** O sistema possui quatro módulos separados (`clientes`, `armadores`, `proprietarios`, `despachantes`) que apontam para a mesma tabela física `clientes`. Isso gera quadruplicação de código e risco de descompasso funcional.
- **Nomenclatura:** Nomes de tabela e campos adequados. O erro está na proliferação de pastas irmãs que duplicam seu papel.

---

### 2.7. `armadores`
- **O que faz:** Interface filtrada para listar e cadastrar especificamente armadores (empresas de navegação/operadores comerciais), vinculando-os a embarcações.
- **Depende de:** Tabela física `clientes`, `embarcacoes`, `includes/cliente_vinculos.php`.
- **Módulos que dependem dele:** `comercial` (seleção de armador), `vistorias` (`armador_id`), `protocolos`.
- **Validação de dados:** Sim, herda a mesma validação de CPF/CNPJ de `clientes`.
- **Bugs/Inconsistências:** É uma cópia quase idêntica do código de `clientes`. No arquivo `actions.php`, o cabeçalho ainda diz `MODULO: CLIENTES` e mensagens contêm erro ortográfico: `Armadore cadastrado com sucesso!`.
- **Nomenclatura:** Antipattern arquitetural (deveria ser apenas uma rota/filtro `clientes?perfil=armador`).

---

### 2.8. `proprietarios`
- **O que faz:** Outra interface clonada de `clientes`, filtrada para proprietários legais das embarcações (constantes no TIE/PRPM da Capitania).
- **Depende de:** Tabela `clientes`, `embarcacoes`.
- **Módulos que dependem dele:** `embarcacoes` (`proprietario_id`), `comercial`.
- **Validação de dados:** Sim (valida CPF/CNPJ, obrigatoriedade de campos e CSRF).
- **Bugs/Inconsistências:** Mais 729 linhas de código PHP/HTML clonadas sem necessidade.
- **Nomenclatura:** Mesma duplicação de `clientes`.

---

### 2.9. `despachantes`
- **O que faz:** Quarta variação sobre a tabela `clientes`, com a adição de seleção de tipos de embarcação autorizados (`clientes_tipos_embarcacao`).
- **Depende de:** `clientes`, `tipos_embarcacao`, `clientes_tipos_embarcacao`.
- **Módulos que dependem dele:** `comercial`, `protocolos`, `certificados_csn`, `analises_planos`.
- **Validação de dados:** Sim (CPF/CNPJ, CSRF, campos obrigatórios).
- **Bugs/Inconsistências:** A tabela relacional `clientes_tipos_embarcacao` não possui chave estrangeira em cascata no banco para exclusão limpa.
- **Nomenclatura:** Mesma fragmentação de `clientes`.

---

### 2.10. `embarcacoes`
- **O que faz:** Cadastro técnico mestre das embarcações (marítimas, fluviais, apoio portuário, recreio). Armazena dados técnicos navais mandatórios (NORMAM-201/202): nome, número de inscrição na Capitania (TIE/PRPM), indicativo de chamada, porto de inscrição, arqueação bruta e líquida, comprimento total, boca moldada, pontal, calado máximo, material do casco, fabricante, modelo e número do motor, potência em kW/HP, tipo e área de navegação, lotação de passageiros e tripulantes e foto oficial do barco.
- **Depende de:** `clientes` (proprietário), `tipos_embarcacao`, `includes/embarcacao_foto.php`.
- **Módulos que dependem dele:** `comercial`, `agendamentos`, `vistorias`, `analises_planos`, `protocolos`, `certificados`, `documentacao`, `portal`.
- **Validação de dados:** Muito boa. Valida dimensões numéricas navais (comprimento, boca, pontal, calado), ano de construção, formato do TIE e CSRF.
- **Bugs/Inconsistências:** A foto principal da embarcação ficava isolada de relatórios de vistoria. Além disso, quando um certificado oficial é emitido em `documentacao`, esses dados técnicos são gravados de forma redundante como texto estático nas tabelas `certificados_*`.
- **Nomenclatura:** Excelente. Alinhada à NORMAM e à terminologia da Marinha do Brasil.

---

### 2.11. `comercial`
- **O que faz:** Núcleo de vendas e propostas. Elabora orçamentos com múltiplos serviços e embarcações (`propostas`, `propostas_embarcacoes`, `propostas_servicos`), calcula descontos, prazos, formas de pagamento, emite proposta comercial formal em PDF com identidade visual naval, envia por e-mail com registro de log, disponibiliza assinatura eletrônica externa para o cliente via token e gera automaticamente lançamentos no módulo financeiro e demandas para agendamento operacional. Também abriga o CRUD do catálogo de serviços (`modules/comercial/servicos`).
- **Depende de:** `clientes`, `embarcacoes`, `servicos`, `usuarios`, `configuracoes` (metas/escritórios), `includes/proposta_pdf.php`, `includes/mailer.php`.
- **Módulos que dependem dele:** `agendamentos` (inicia a partir de propostas aprovadas), `financeiro` (gera contas a receber), `protocolos` (dossiê vinculado), `dashboard`.
- **Validação de dados:** Forte. Valida itens da proposta, valores não negativos, formas de pagamento, regras de transição de status e CSRF.
- **Bugs/Inconsistências:** O arquivo `nova.php` possui **94.880 bytes** em um só script, dificultando manutenção. Além disso, o catálogo de `servicos` (tabela mestre de todo o ERP) está "escondido" em uma subpasta de comercial (`modules/comercial/servicos`), quando deveria ser um cadastro base independente.
- **Nomenclatura:** Boa na raiz, mas a subpasta `propostas/actions.php` com 46 KB sobrepõe fluxos do próprio comercial.

---

### 2.12. `contratos`
- **O que faz:** Antigo gerador de minutas de contratos formais de prestação de serviços a partir de propostas.
- **Depende de:** `clientes`, `propostas`.
- **Módulos que dependem dele:** NENHUM.
- **Validação de dados:** Inacessível.
- **Bugs/Inconsistências:** **MÓDULO MORTO / CÓDIGO FANTASMA.** Foi formalmente desativado na migration `075_remover_modulo_contratos.sql`, não consta no menu lateral (`sidebar.php`), não possui permissões no banco e **não está mapeado no roteador `index.php`**. Ocupa 747 linhas de código inútil no repositório e mantém a tabela `contratos` vazia no MySQL.
- **Nomenclatura:** Obsoleta.

---

### 2.13. `agendamentos`
- **O que faz:** Gestão da escala de vistorias técnicas navais. Converte propostas comerciais aprovadas em Ordens de Serviço (`ordens_servico`) e agendamentos de campo. Atribui vistoriador técnico naval credenciado, define data, horário, estaleiro/local, dados de contato do comandante/operador e tipo de vistoria (inicial, periódica, renovação, arqueação, borda livre). Suporta pré-agendamentos e autorizações de saída técnica.
- **Depende de:** `comercial`, `embarcacoes`, `clientes`, `usuarios` (vistoriadores), `servicos`, `includes/auth.php`.
- **Módulos que dependem dele:** `vistorias` (obrigatório: não existe vistoria sem agendamento!), `campo` (alimenta a fila do app offline do vistoriador), `dashboard`.
- **Validação de dados:** Sim, valida disponibilidade de vistoriador, campos de localização, datas válidas, vínculos relacionais e CSRF.
- **Bugs/Inconsistências:** A tabela `ordens_servico` é uma duplicação parcial da tabela `agendamentos`, gerando redundância cadastral. A tela `os.php` usa formatação visual antiga e inline.
- **Nomenclatura:** Excelente, clara e representativa do processo naval.

---

### 2.14. `vistorias`
- **O que faz:** O módulo operacional mais crítico do sistema para conformidade com a Autoridade Marítima (NORMAM-202/201). Executa o relatório de vistoria técnica naval completo: checklist de itens obrigatórios com evidências fotográficas, identificação de não conformidades/exigências navais, apontamento de prazos de cumprimento, re-vistorias e retornos de exigências (`vistoria_retornos`), histórico de vistorias anteriores, emissão do Relatório de Vistoria Oficial em PDF (`relatorio_pdf.php`), e encaminhamento para aprovação eletrônica do responsável técnico naval (`documento_aprovacoes`).
- **Depende de:** `agendamentos`, `embarcacoes`, `clientes`, `usuarios`, `configuracoes` (catálogo NORMAM-202), `includes/aprovacao_documentos.php`, `includes/assinaturas_usuarios.php`.
- **Módulos que dependem dele:** `documentacao` (aprovação de relatórios e baixa de exigências para emissão de certificados), `certificados` (CSN, CNBL, CNARQ, LP, LC, CHT), `protocolos` (dossiês navais), `dashboard`, `campo`.
- **Validação de dados:** Altíssimo rigor: valida fotos obrigatórias da NORMAM-202, integridade do checklist, impede emissão com exigências impeditivas não sanadas, validação de tokens e hashes de assinatura.
- **Bugs/Inconsistências:** O arquivo `relatorio.php` é um monolito massivo de **201.988 bytes** (mais de 4.000 linhas de código em um único arquivo!), misturando dezenas de modais, scripts JS inline, processamento AJAX e renderização HTML. Além disso, `vistorias/nova.php` tem apenas 10 linhas e redireciona direto para agendamentos.
- **Nomenclatura:** Nomenclatura boa, tabelas bem estruturadas (`vistorias`, `vistoria_checklist_respostas`, `vistoria_exigencias`, `vistoria_anexos`, `vistoria_retornos`).

---

### 2.15. `campo` (e diretórios `campo/` e `pwa-campo/`)
- **O que faz:** Aplicação Web Progressiva (PWA) e API dedicada para os vistoriadores navais realizarem inspeções embarcadas em campo, inclusive em áreas remotas (ilhas, rios da Amazônia, estaleiros) sem conexão com a internet. Permite login offline, download prévio das vistorias atribuídas, preenchimento do checklist NORMAM-202, captura de fotos com geolocalização e sincronização bidirecional em segundo plano (`vistoria_mobile_sync`).
- **Depende de:** `vistorias`, `agendamentos`, `embarcacoes`, `usuarios`, `includes/campo_storage.php` (MinIO/S3).
- **Módulos que dependem dele:** `vistorias` (recebe os dados sincronizados do campo).
- **Validação de dados:** Sim, valida tokens de sessão de campo (`campo_sessoes`), limite de tentativas de login com rate-limiting (`campo_login_tentativas`), integridade de hashes de sincronização e integridade dos arquivos JPEG/PNG enviados.
- **Bugs/Inconsistências:** Dispersão arquitetural em três lugares diferentes da árvore de pastas:
  1. `modules/campo/api.php` (backend PHP de 48 KB)
  2. `campo/` (arquivos estáticos finais compilados do PWA: `index.html`, `sw.js`, `manifest.webmanifest`)
  3. `pwa-campo/` (projeto de desenvolvimento frontend em TypeScript/Vite/Tailwind).
- **Nomenclatura:** Três pastas com nomes parecidos (`campo`, `pwa-campo`, `modules/campo`), causando confusão em quem faz manutenção.

---

### 2.16. `analises_planos`
- **O que faz:** Módulo técnico de Engenharia Naval para análise e aprovação de planos e projetos navais (planos de linhas, arranjo geral, estabilidade intacta e em avaria, cálculo estrutural, arqueação e borda livre), em conformidade com as NORMAMs da DPC. Gerencia submissões sucessivas de plantas navais, emissão de folhas de exigências de planos, relatórios de pendências e geração do Parecer Técnico de Análise de Planos em PDF com assinatura digital qualificada.
- **Depende de:** `embarcacoes`, `clientes`, `usuarios` (engenheiros navais/analistas), `servicos`, `includes/analise_planos.php`, `includes/aprovacao_documentos.php`.
- **Módulos que dependem dele:** `documentacao/lc` (Laudo de Conformidade depende diretamente da aprovação de planos), `protocolos` (dossiê protocolado na Capitania dos Portos), `portal` (cliente envia novas revisões de planos), `dashboard`.
- **Validação de dados:** Forte. Não permite emitir parecer favorável com exigências em aberto; valida tipos de arquivos de engenharia (PDF, DWG), CSRF e tokens de aprovação.
- **Bugs/Inconsistências:** Possui 9 tabelas próprias no banco. Algumas usam chave primária `bigint` auto-increment e outras usam `char(36)` UUID, criando mistura de tipos de chaves primárias dentro do mesmo módulo.
- **Nomenclatura:** Muito clara e aderente ao vocabulário naval da Marinha do Brasil.

---

### 2.17. `documentacao`
- **O que faz:** O maior módulo em volume de arquivos do ERP (37 arquivos, mais de 12.600 linhas). É a central de emissão de Certificados Estatutários Navais e Laudos Técnicos oficiais chancelados pela Capitania/DPC. Abriga as rotinas especializadas para:
  - CSN (Certificado de Segurança da Navegação - NORMAM-201/202)
  - CNBL (Certificado Nacional de Borda Livre - NORMAM-202)
  - CNARQ (Certificado Nacional de Arqueação - NORMAM-202)
  - LP (Laudo Pericial Naval)
  - LC (Laudo de Conformidade de Planos)
  - CHT (Certificado de Homologação de Tirantes)
  Também controla a aprovação final de relatórios técnicos (`aprovacao_relatorios.php`) e a baixa formal de exigências pendentes (`baixa_exigencias.php`).
- **Depende de:** `vistorias`, `analises_planos`, `embarcacoes`, `clientes`, `usuarios`, `responsaveis_assinatura`, `includes/aprovacao_documentos.php`, `includes/aprovacao_pdf.php`.
- **Módulos que dependem dele:** `protocolos` (anexa certificados aos dossiês das Capitanias), `portal` (clientes baixam seus certificados), `dashboard`, `minhas_assinaturas`.
- **Validação de dados:** Forte em regras de negócio: impede emissão sem vistoria aprovada ou com exigências pendentes; valida numeração anual sequencial (`sequenciais_documentos`), validades e hashes SHA-256.
- **Bugs/Inconsistências:**
  1. Concorre com `modules/certificados` (que possui telas duplicadas de emissão em wizard).
  2. Desnormalização brutal: todas as tabelas `certificados_*` copiam mais de 15 colunas textuais da embarcação em vez de se relacionar via foreign key.
  3. Ausência de Foreign Keys formais para `embarcacoes` e `clientes` no MySQL.
- **Nomenclatura:** Subpastas técnicas (`csn`, `cnbl`, `cnarq`, `lp`, `lc`, `cht`) muito bem nomeadas, porém o nome da pasta pai `documentacao` entra em conflito conceitual com `documentos` e `certificados`.

---

### 2.18. `certificados`
- **O que faz:** Interface alternativa em formato de assistente passo a passo ("wizard") para emissão de certificados (`wizard.php`, `wizard_step2.php`, `wizard_cht.php`).
- **Depende de:** `vistorias`, `embarcacoes`, `clientes`, tabelas `certificados_*`.
- **Módulos que dependem dele:** Operadores que utilizam o fluxo em etapas.
- **Validação de dados:** Similar à de `documentacao`.
- **Bugs/Inconsistências:** `wizard_step2.php` é outro arquivo monolítico de **81 KB** que duplica regras de cálculo e gravação que já existem em `modules/documentacao/certificados/salvar.php`. Risco de divergência caso uma regra NORMAM seja corrigida em um arquivo e esquecida no outro.
- **Nomenclatura:** Causa confusão direta com `modules/documentacao/certificados/`.

---

### 2.19. `documentos`
- **O que faz:** Nome enganoso. NÃO é gestão de arquivos. É o motor de aprovação eletrônica e validação pública de autenticidade documental via QR Code e Token (`aprovar.php`, `cancelar.php`, `validar.php`, `validar_assinatura.php`).
- **Depende de:** `documento_aprovacoes`, `documento_assinaturas`, `responsaveis_assinatura`, `vistorias`.
- **Módulos que dependem dele:** Todos os documentos emitidos com validação pública por QR Code (Capitanias, agentes de fiscalização naval, clientes).
- **Validação de dados:** Forte. Valida tokens SHA-256 de 64 caracteres, integridade criptográfica de PDFs, registros de IP e geolocalização.
- **Bugs/Inconsistências:** Mantém o arquivo `assinatura_publica_desativada.php` apenas para responder status HTTP 410.
- **Nomenclatura:** **NOME CONFUSO.** Deveria se chamar `autenticidade` ou `validacao_documental`.

---

### 2.20. `responsaveis_assinatura`
- **O que faz:** Cadastro de engenheiros navais e vistoriadores autorizados a assinar laudos e certificados oficiais perante a Marinha e conselhos de classe (CREA/DPC). Guarda registro profissional, títulos, assinaturas e rubricas digitalizadas.
- **Depende de:** `usuarios`, `includes/auth.php`.
- **Módulos que dependem dele:** `documentacao` (todos os certificados), `vistorias`, `analises_planos`, `documentos`, `minhas_assinaturas`.
- **Validação de dados:** Sim, valida upload de PNGs com transparência, vínculos com usuários ativos e CSRF.
- **Bugs/Inconsistências:** **É a única tabela mestra que usa chave primária `id INT AUTO_INCREMENT`** enquanto o sistema inteiro padronizou `char(36) UUID`.
- **Nomenclatura:** Nomenclatura clara e direta.

---

### 2.21. `minhas_assinaturas`
- **O que faz:** Caixa de entrada de assinaturas do técnico logado. Exibe documentos pendentes de sua assinatura (`RELATORIO`, `PARECER_PLANOS`, `CSN`, etc.), captura geolocalização GPS e IP do navegador e aplica carimbo digital. Permite ao Administrador realizar assinatura substituta autorizada.
- **Depende de:** `responsaveis_assinatura`, `vistorias`, `analises_planos`, `certificados_*`, `includes/assinaturas_usuarios.php`.
- **Módulos que dependem dele:** Nenhum (tela final de trabalho do engenheiro/vistoriador).
- **Validação de dados:** Forte. Exige coordenadas de GPS ativas para assinar, valida token do documento e CSRF.
- **Bugs/Inconsistências:** O código de `index.php` está condensado em apenas 28 linhas de código longo e ilegível.
- **Nomenclatura:** Boa para o usuário final, mas usa hífen na URL (`minhas-assinaturas`) e underline na pasta (`minhas_assinaturas`).

---

### 2.22. `assinaturas_publicas`
- **O que faz:** Portal público tokenizado para que engenheiros ou signatários externos sem usuário no ERP possam visualizar a prévia do certificado em PDF (`preview.php`) e assinar remotamente (`confirmar.php`, `certificado.php`) via link enviado por e-mail.
- **Depende de:** `certificados_*`, `assinatura_convites`, `includes/assinaturas_usuarios.php`.
- **Módulos que dependem dele:** Fluxo de assinatura remota de certificados por terceiros.
- **Validação de dados:** Valida hash do convite, validade temporal e CSRF.
- **Bugs/Inconsistências:** `confirmar.php` tem 4 linhas de código minificado. O fluxo concorre com `modules/documentos/assinatura_publica_desativada.php`.
- **Nomenclatura:** Aceitável.

---

### 2.23. `protocolos`
- **O que faz:** Gestão de Dossiês e Trâmites Físicos/Digitais junto à Capitania dos Portos (CP), Delegacias Fluviais, estaleiros e despachantes. Controla a custódia de documentos originais (comprovante de recolhimento de documentos), histórico de movimentações (entrada, saída, exigência da Capitania, retirada de certidão), capa de dossiê em PDF, recibo de movimentação com QR Code, alertas de prazos de trâmite e aceite digital pelo cliente/despachante via link público tokenizado (`aceite.php`).
- **Depende de:** `embarcacoes`, `clientes`, `propostas`, `vistorias`, `analises_planos`, `servicos`, `usuarios`, `includes/protocolos.php`.
- **Módulos que dependem dele:** `dashboard` (alertas de custódia e exigências da Capitania), `portal` (cliente consulta trâmite na Marinha).
- **Validação de dados:** Altíssimo rigor: valida transições de estado do dossiê, trava devolução de originais sem termo assinado, valida tokens e hashes de auditoria.
- **Bugs/Inconsistências:** O arquivo `form.php` é muito grande (**97,5 KB**), agrupando todas as abas operacionais em uma só tela.
- **Nomenclatura:** Excelente e rigorosamente alinhada ao vocabulário de trâmite das Capitanias.

---

### 2.24. `financeiro`
- **O que faz:** Contas a receber e a pagar dos serviços navais. Integração direta com Comercial (cria títulos automaticamente ao aprovar propostas). Gerencia parcelas, baixas parciais e totais, upload de comprovantes bancários, segregação por múltiplos escritórios físicos, metas mensais e exportação de relatórios em XLSX.
- **Depende de:** `comercial`, `clientes`, `configuracoes` (escritórios/metas), `usuarios`, `includes/financeiro_escritorios.php`, `includes/xlsx_export.php`.
- **Módulos que dependem dele:** `dashboard` (KPIs financeiros de faturamento e inadimplência).
- **Validação de dados:** Sim, valida valores numéricos positivos, coerência de datas, rateios por escritório, integridade de baixas parciais e CSRF.
- **Bugs/Inconsistências:** Mantém relatórios avançados internamente (`relatorios.php` e `relatorios_exportar.php`), enquanto o módulo geral `modules/relatorios` permanece como página em branco.
- **Nomenclatura:** Arquivos e tabelas bem organizados.

---

### 2.25. `relatorios`
- **O que faz:** Atualmente nada. É uma página de 37 linhas com a mensagem: *"Módulo em desenvolvimento"*.
- **Depende de:** `includes/auth.php`.
- **Módulos que dependem dele:** Nenhum.
- **Validação de dados:** **NÃO POSSUI NENHUMA VALIDAÇÃO.**
- **Bugs/Inconsistências:** **MÓDULO INCOMPLETO / PLACEHOLDER.** Está exposto no menu lateral do sistema como se existisse, mas é apenas uma casca vazia. Os relatórios reais estão dispersos em `financeiro`, `documentacao` e `sgq`.
- **Nomenclatura:** Inadequada pelo estado incompleto.

---

### 2.26. `dashboard`
- **O que faz:** Painel executivo principal após o login. Apresenta métricas acionáveis da rotina naval: propostas em aberto, vistorias agendadas da semana, vistorias pendentes de relatório, exigências navais com prazo vencendo, certificados com validade próxima da expiração, dossiês com exigências na Capitania, faturamento vs meta por escritório e log de atividades recentes.
- **Depende de:** Quase todos os módulos operacionais: `vistorias`, `agendamentos`, `comercial`, `financeiro`, `protocolos`, `documentacao`, `analises_planos`, `usuarios`, `configuracoes`.
- **Módulos que dependem dele:** Nenhum (módulo de consumo final no topo da pirâmide).
- **Validação de dados:** Somente leitura. **Não valida CSRF** em seus filtros de sessão.
- **Bugs/Inconsistências:** Possui 7 arquivos e 2.138 linhas. Executa queries analíticas pesadas a cada refresh sem cache, gerando gargalo potencial no banco conforme o histórico crescer.
- **Nomenclatura:** Padrão e clara.

---

### 2.27. `portal`
- **O que faz:** Portal web externo do armador, proprietário ou despachante. Permite ao cliente logar-se separadamente, visualizar suas embarcações cadastradas, consultar e baixar PDFs de certificados vigentes e laudos periciais, acompanhar o status dos dossiês na Capitania, enviar novos arquivos de projetos navais e responder a exigências de planos, registrar manifestações e responder pesquisas.
- **Depende de:** `clientes`, `embarcacoes`, `certificados_*`, `analises_planos`, `protocolos`, `includes/cliente_portal.php`, `includes/portal_header.php`, `includes/portal_footer.php`.
- **Módulos que dependem dele:** Nenhum (interface externa consumidora).
- **Validação de dados:** Forte na camada de acesso: login com credenciais próprias de cliente, verificação estrita de posse (cliente só enxerga embarcações que possuem vínculo em `clientes_embarcacoes`), validação de upload e CSRF.
- **Bugs/Inconsistências:** Não reaproveita o layout principal do sistema, possuindo header e footer próprios. Contém telas com acoplamento direto a tabelas ISO (`sgq_satisfacao_clientes`, `sgq_nao_conformidades`).
- **Nomenclatura:** O nome `portal` para o cliente externo e `portal_clientes` para a gestão interna de acessos gera confusão recorrente.

---

### 2.28. `portal_clientes`
- **O que faz:** Módulo interno administrativo do ERP para liberar acesso, bloquear, redefinir senhas e gerar convites de acesso para os clientes usarem o `portal`.
- **Depende de:** `clientes`, `cliente_portal_acessos`, `includes/cliente_portal.php`.
- **Módulos que dependem dele:** `portal` (depende das contas ativadas aqui).
- **Validação de dados:** Sim, valida e-mails, permissões e CSRF.
- **Bugs/Inconsistências:** Nenhuma falha funcional grave, apenas confusão com o módulo irmão.
- **Nomenclatura:** **NOME CONFUSO.** Deveria se chamar `gestao_acessos_portal`.

---

### 2.29. `emails`
- **O que faz:** Tela de auditoria e monitoramento do histórico de e-mails disparados pelo sistema (`email_logs`). Exibe destinatário, assunto, data/hora de envio, status de entrega e mensagens de erro do servidor SMTP.
- **Depende de:** `includes/auth.php`, tabela `email_logs`.
- **Módulos que dependem dele:** Nenhum módulo operacional depende dele diretamente.
- **Validação de dados:** Somente leitura e filtros de pesquisa.
- **Bugs/Inconsistências:** Não oferece botão de reprocessamento/reenvio com 1 clique para mensagens que falharam por indisponibilidade momentânea do SMTP.
- **Nomenclatura:** Clara e direta.

---

### 2.30. `feedback`
- **O que faz:** Sistema interno de comunicação e chamados/tickets entre colaboradores do ERP. Permite troca de mensagens internas com anexos, controle de status (aberto, em atendimento, resolvido) e contador de pendências no topo do sistema.
- **Depende de:** `usuarios`, `includes/feedback.php`, tabelas `feedbacks`, `feedback_mensagens`, etc.
- **Módulos que dependem dele:** O contador de mensagens não lidas é consumido pelo `header.php` em todas as páginas do ERP.
- **Validação de dados:** Valida preenchimento de mensagem, tamanho dos anexos, participantes da conversa e CSRF.
- **Bugs/Inconsistências:** Funciona como um "chat/helpdesk" interno embutido. Foge do escopo puramente naval, atuando como ferramenta acessória interna.
- **Nomenclatura:** Bem organizada (`feedback_*`).

---

### 2.31. `notificacoes`
- **O que faz:** Painel para exibição de notificações do sistema destinadas ao usuário logado (ex.: atribuição de novo agendamento, proposta assinada). Permite marcar todas como lidas.
- **Depende de:** `usuarios`, tabela `notificacoes`.
- **Módulos que dependem dele:** O sino de notificações no `header.php`.
- **Validação de dados:** **FRACA.** O arquivo `actions.php` tem apenas 604 bytes e quase nenhuma checagem além de verificar sessão e CSRF.
- **Bugs/Inconsistências:** **MÓDULO SEMI-ACABADO.** O arquivo `index.php` possui layout condensado que não inclui o menu lateral (`sidebar.php`), causando uma quebra visual grave ao abrir a tela completa de notificações.
- **Nomenclatura:** Padrão.

---

### 2.32. `sgq`
- **O que faz:** Subsistema de Gestão da Qualidade baseado na norma ISO 9001. Contém controle de Não Conformidades (RNC), Planos de Ação (`5W2H`), Matriz de Riscos operacionais e corporativos, Indicadores de Desempenho do SGQ, Manual da Qualidade e módulo de Auditorias Internas.
- **Depende de:** `clientes`, `embarcacoes`, `ordens_servico`, `usuarios`, `includes/sgq.php`.
- **Módulos que dependem dele:** `portal` (envia pesquisas de satisfação e ocorrências para o SGQ).
- **Validação de dados:** Sim, formulários estruturados com validação de campos mandatórios de auditoria, prazos de planos de ação e CSRF.
- **Bugs/Inconsistências:** **VIOLAÇÃO DIRETA DAS DIRETRIZES DO PROJETO (`AGENTS.md`).** A regra estabelece: *"ISO / SGQ: Qualquer regra, auditoria, processo ou padronização exclusiva de conformidade ISO está desconsiderada e separada do núcleo do sistema... Foco integral na simplicidade operacional, praticidade para o usuário e segurança da navegação"*. Este módulo contém 8 arquivos, 2.402 linhas e 5 tabelas no banco que poluem a arquitetura naval central.
- **Nomenclatura:** Nomes alinhados ao ISO 9001, porém alienígenas ao escopo das NORMAMs.

---

## 3. MAPA GERAL DE DEPENDÊNCIA TOPOLÓGICA

O mapa a seguir organiza todos os módulos em ordem hierárquica rigorosa, do que é mais **"base" (nível 0 e 1, do qual tudo depende)** até o que é mais **"topo" (níveis 7 e 8, que dependem de tudo e dos quais nada depende)**.

> **Regra de Ouro para as Próximas Etapas:** Nenhuma alteração pode ser iniciada nos níveis superiores sem antes sanar e estabilizar as camadas inferiores.

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│ NÍVEL 8: INTERFACES CONSUMIDORAS DE TOPO (Nenhum módulo depende deles)           │
│ - dashboard, portal (Área do Cliente), emails, feedback, notificacoes, relatorios│
└────────────────────────────────────────┬─────────────────────────────────────────┘
                                         │ Consomem dossiês, laudos e status
┌────────────────────────────────────────▼─────────────────────────────────────────┐
│ NÍVEL 7: TRÂMITE EXTERNO E PROTOCOLO DOCUMENTAL                                  │
│ - protocolos (Dossiês, Capitanias/Delegacias, Custódia de Originais, SISAP)      │
│ - portal_clientes (Gestão administrativa de acessos externos)                    │
└────────────────────────────────────────┬─────────────────────────────────────────┘
                                         │ Protocolam certificados e laudos
┌────────────────────────────────────────▼─────────────────────────────────────────┐
│ NÍVEL 6: CHANCELA, CERTIFICAÇÃO ESTATUTÁRIA E ASSINATURAS DIGITAIS               │
│ - documentacao (CSN, CNBL, CNARQ, LP, LC, CHT)                                   │
│ - certificados (Wizards) | documentos (Validação QR/Token)                       │
│ - minhas_assinaturas | assinaturas_publicas | responsaveis_assinatura            │
└────────────────────────────────────────┬─────────────────────────────────────────┘
                                         │ Exigem relatórios e pareceres técnicos
┌────────────────────────────────────────▼─────────────────────────────────────────┐
│ NÍVEL 5: EXECUÇÃO TÉCNICA NAVAL (NORMAM-202 / NORMAM-201 / DPC)                  │
│ - vistorias (Relatórios Oficiais, Checklists, Exigências, Retornos)               │
│ - campo & pwa-campo (Vistoria Offline PWA e API de Sincronização)                │
│ - analises_planos (Engenharia Naval, Pareceres de Plantas Navais)                │
└────────────────────────────────────────┬─────────────────────────────────────────┘
                                         │ Exigem agendamento e ordem de serviço
┌────────────────────────────────────────▼─────────────────────────────────────────┐
│ NÍVEL 4: PLANEJAMENTO OPERACIONAL NAVAL E FATURAMENTO                            │
│ - agendamentos (Ordens de Serviço, Atribuição de Vistoriador Naval)              │
│ - financeiro (Lançamentos a Receber/Pagar, Baixas, Comprovantes Bancários)       │
└────────────────────────────────────────┬─────────────────────────────────────────┘
                                         │ Transformam propostas aprovadas em OS/Títulos
┌────────────────────────────────────────▼─────────────────────────────────────────┐
│ NÍVEL 3: MOTOR COMERCIAL E SERVIÇOS                                              │
│ - comercial (Propostas Comerciais Navais, Assinatura Eletrônica de Venda)        │
│ - comercial/servicos (Catálogo Oficial de Serviços Navais)                       │
└────────────────────────────────────────┬─────────────────────────────────────────┘
                                         │ Precificam serviços sobre as embarcações
┌────────────────────────────────────────▼─────────────────────────────────────────┐
│ NÍVEL 2: ENTIDADES CENTRAIS DO DOMÍNIO NAVAL                                     │
│ - embarcacoes (Dados Técnicos Navais, TIE/PRPM, Casco, Motor, Foto Oficial)      │
│ - clientes (Armadores, Proprietários, Despachantes - Unificados)                 │
│ - configuracoes/normam202 (Catálogo de Exigências da NORMAM-202)                │
└────────────────────────────────────────┬─────────────────────────────────────────┘
                                         │ Vinculadas aos escritórios e colaboradores
┌────────────────────────────────────────▼─────────────────────────────────────────┐
│ NÍVEL 1: IDENTIDADE, ACESSO E INFRAESTRUTURA HUMANA                              │
│ - login, usuarios, perfil                                                        │
│ - configuracoes (Escritórios e Permissões Granulares de Cargos)                  │
└────────────────────────────────────────┬─────────────────────────────────────────┘
                                         │ Conexão PDO, sessões e funções mestres
┌────────────────────────────────────────▼─────────────────────────────────────────┐
│ NÍVEL 0: FUNDAÇÃO ABSOLUTA (Kernel do Sistema)                                   │
│ - config.php, includes/functions.php, includes/auth.php, includes/mailer.php    │
│ - Banco de Dados MySQL (87 tabelas), Storage MinIO/S3, PHP Session, OPcache      │
└──────────────────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────────────────┐
│ ⚠️ MÓDULOS FORA DO NÚCLEO NAVAL / A DESACOPLAR OU EXPURGAR:                     │
│ - sgq (ISO 9001 - Não faz parte do núcleo NORMAM/Marinha per AGENTS.md)          │
│ - contratos (Desativado na migration 075 - Código morto a remover)              │
│ - exigencias_catalogo (actions.php órfão a consolidar em normam202)              │
└──────────────────────────────────────────────────────────────────────────────────┘
```

---

## 4. DIAGNÓSTICO SEM FILTROS: O TAMANHO REAL DO PROBLEMA

### 4.1. Problemas Graves de Estrutura de Pastas e Duplicação de Código

1. **A Quadruplicação de Clientes:**
   - As pastas `modules/clientes`, `modules/armadores`, `modules/proprietarios` e `modules/despachantes` realizam **exatamente o mesmo CRUD** sobre a tabela única `clientes`.
   - São mais de 2.700 linhas de código duplicadas. Se um campo ou validação de cliente for ajustado em um deles, os outros três permanecem com regras defasadas.
   - *Solução correta:* Manter apenas `modules/clientes`, com abas/filtros de visualização por perfil (`?perfil=armador`, etc.).

2. **A Triplicação de Módulos de Documentação / Certificação:**
   - O sistema possui `modules/documentos`, `modules/documentacao` e `modules/certificados`.
   - `modules/documentos` cuida apenas de validação de autenticidade (QR Code/Token).
   - `modules/documentacao` cuida da emissão real dos certificados estáticos (CSN, CNBL, CNARQ, LP, LC, CHT).
   - `modules/certificados` cuida de um fluxo paralelo em wizard dos mesmos certificados.
   - Três pastas tratando do mesmo assunto com nomes quase sinônimos geram caos na navegação e duplicação de regras de cálculo naval.

3. **A Fragmentação do App de Campo em Três Pastas:**
   - `modules/campo/api.php` (API backend).
   - `campo/` (arquivos estáticos finais compilados do PWA).
   - `pwa-campo/` (código-fonte em TypeScript/Vite).
   - Sem uma estrutura padronizada (ex.: `apps/campo-pwa` e rota unificada `api/campo`), a manutenção do PWA fica desconexa do ERP.

---

### 4.2. Problemas Críticos de Banco de Dados e Modelagem

1. **Desnormalização Brutal nas Tabelas de Certificados (`certificados_*`):**
   - As tabelas `certificados_csn`, `certificados_cnbl`, `certificados_cnarq`, `certificados_lp`, `certificados_lc` e `certificados_cht` **não possuem foreign keys para `embarcacoes` nem para `clientes`**.
   - Em vez de relacionar, cada tabela copia em colunas de texto puro: `nome_embarcacao`, `numero_inscricao`, `indicativo_chamada`, `atividades_servicos`, `tipo_embarcacao`, `ano_construcao`, `comprimento_m`, `arqueacao_bruta`, `tipo_navegacao`, `area_navegacao`, `fabricante_motor`, `potencia_kw`, `material_casco`.
   - Se os dados da embarcação forem corrigidos após a digitação de um rascunho, o certificado permanece congelado com dados antigos sem qualquer vínculo de integridade referencial.

2. **Inconsistência de Collation no MySQL:**
   - Das 87 tabelas, a grande maioria está em `utf8mb4_general_ci`, enquanto tabelas fundamentais de assinatura e autenticação (`assinatura_convites`, `campo_login_tentativas`, `documento_aprovacoes`, `documento_assinaturas`, `responsaveis_assinatura`) foram criadas em `utf8mb4_unicode_ci`.
   - Essa mistura pode gerar erros inesperados de `Illegal mix of collations (utf8mb4_general_ci,IMPLICIT) and (utf8mb4_unicode_ci,IMPLICIT) for operation '='` em joins complexos.

3. **Inconsistência de Tipos de Chave Primária:**
   - Quase todo o sistema adotou `char(36)` UUID gerado pela aplicação (`uuid()`).
   - Porém, a tabela mestre `responsaveis_assinatura` usa `int` auto-increment.
   - Tabelas de histórico e auditoria (`analise_planos_agenda_historico`, `analise_planos_historico`, `portal_auditoria`, `protocolo_auditoria`) usam `bigint unsigned`.
   - Tabelas de configuração usam `varchar(100)` ou chave composta.
   - Essa heterogeneidade exige conversões manuais e impede joins padronizados.

4. **Falta de Padronização no Soft Delete:**
   - Algumas tabelas usam `ativo TINYINT(1) DEFAULT 1` (ex.: `embarcacoes`, `certificados_*`).
   - Outras usam `status = 'cancelado'` ou `status = 'INATIVO'` (ex.: `clientes`).
   - Outras utilizam `excluido_em DATETIME NULL` (ex.: `financeiro_lancamentos`).
   - Outras simplesmente realizam `DELETE` físico direto no banco (ex.: itens de permissão e catálogos), perdendo rastreabilidade histórica perante a Autoridade Marítima.

---

### 4.3. Arquivos Monolíticos de Alto Risco de Manutenção

Existem arquivos com complexidade ciclomática extrema e centenas de milhares de bytes em um único bloco:
- `modules/vistorias/relatorio.php`: **201.988 bytes** (~4.000 linhas). Mistura formulário de vistoria, fotos, regras de baixa, assinaturas, scripts AJAX e modais. É o arquivo mais perigoso do sistema para sofrer regressão acidental.
- `modules/comercial/nova.php`: **94.880 bytes**.
- `modules/protocolos/form.php`: **97.544 bytes**.
- `modules/vistorias/actions.php`: **85.494 bytes**.
- `modules/certificados/wizard_step2.php`: **81.126 bytes**.
- `includes/functions.php`: **80.553 bytes**. Mais de 100 funções utilitárias sem separação por domínio.

---

### 4.4. Inconsistências de Nomenclatura e Código Órfão/Morto

1. **Código Morto / Desativado mantido no repositório:**
   - `modules/contratos/`: 4 arquivos, 747 linhas. Desativado na migration 075, mas nunca apagado.
   - `modules/exigencias_catalogo/`: 1 arquivo de 144 linhas órfão de interface.
   - `index.php`: bloco entre as linhas 302 e 340 contendo dezenas de linhas de código comentado referente a fluxos legados de assinatura.
   - `modules/documentos/assinatura_publica_desativada.php`: arquivo que só serve para retornar HTTP 410.

2. **Módulo Incompleto exposto ao usuário:**
   - `modules/relatorios/`: arquivo de 37 linhas com texto estático "em desenvolvimento", exposto no menu lateral.

3. **Nomenclaturas Conflitantes:**
   - `portal` (cliente) vs `portal_clientes` (administrador do ERP).
   - `modules/minhas_assinaturas` (com underline) vs rota `minhas-assinaturas` (com hífen).

---

### 4.5. Brechas de Segurança e Fragilidades de Validação

1. **Ausência de CSRF em Telas Críticas:**
   - `modules/login/index.php` não gera nem valida token CSRF no login.
   - `modules/dashboard/index.php` não possui validação CSRF em filtros.
2. **Ausência de Rate Limiting no ERP Web:**
   - Apenas o PWA de campo (`campo_login_tentativas`) possui controle contra ataques de dicionário/força bruta. O login principal do ERP web (`modules/login`) permite tentativas ilimitadas de submissão de senha.
3. **Persistência de Sessão sem Invalidação Global:**
   - Quando um usuário é desativado ou tem suas permissões alteradas em `usuarios/actions.php`, a sessão aberta em seu navegador continua ativa até que ocorra expiração natural do cookie ou logout voluntário.

---

## 5. ORDEM RECOMENDADA DE REFATORAÇÃO E PRÓXIMOS PASSOS

Para qualquer intervenção futura no sistema, deve-se obedecer estritamente à seguinte ordem de estabilização, trabalhando sempre da base para o topo:

1. **FASE 1: LIMPEZA DE CÓDIGO MORTO E SEGURANÇA NA FUNDAÇÃO (Níveis 0 e 1)**
   - Remover pastas desativadas (`modules/contratos`) e arquivos órfãos (`modules/exigencias_catalogo`).
   - Adicionar CSRF e rate limiting no `modules/login`.
   - Limpar código comentado obsoleto em `index.php`.
   - Homogeneizar collation do MySQL para `utf8mb4_general_ci` em todas as 87 tabelas.

2. **FASE 2: CONSOLIDAÇÃO DOS CADASTROS MESTRES NAVAIS (Nível 2)**
   - Unificar `armadores`, `proprietarios` e `despachantes` dentro do módulo oficial `clientes`.
   - Eliminar a triplicação de telas e centralizar as regras de validação em um único ponto.
   - Estabelecer chaves estrangeiras com integridade referencial nas tabelas de embarcações e clientes.

3. **FASE 3: DESACOPLAMENTO DO CATÁLOGO DE SERVIÇOS E MOTOR COMERCIAL (Níveis 3 e 4)**
   - Mover o catálogo de serviços de dentro de `comercial/servicos` para um módulo mestre próprio de cadastros navais (`servicos`).
   - Modularizar o arquivo monolítico `comercial/nova.php`.

4. **FASE 4: REFATORAÇÃO E MODULARIZAÇÃO DE VISTORIAS E CAMPO (Nível 5)**
   - Fatiar o arquivo monolítico `vistorias/relatorio.php` (201 KB) em componentes menores e reutilizáveis (dados gerais, checklist, fotos, exigências, parecer).
   - Padronizar a arquitetura do PWA de campo entre `pwa-campo` e `campo`.

5. **FASE 5: UNIFICAÇÃO DA CERTIFICAÇÃO E DOCUMENTAÇÃO NAVAL (Nível 6)**
   - Fundir as regras duplicadas de `modules/certificados` para dentro de `modules/documentacao`.
   - Renomear conceitualmente `modules/documentos` para `autenticidade` ou `validacao_documental`.
   - Normalizar as tabelas `certificados_*`, substituindo a duplicação de strings por chaves estrangeiras consistentes para `embarcacoes`.

6. **FASE 6: DESACOPLAMENTO DO SGQ / ISO 9001 (Fora do Núcleo Naval)**
   - Isolar completamente `modules/sgq` da navegação e das tabelas operacionais da Marinha, cumprindo rigorosamente a diretriz mandatória do projeto (`AGENTS.md`).

---
*Relatório concluído com sucesso. Nenhuma linha de código funcional foi alterada durante esta etapa de diagnóstico.*
