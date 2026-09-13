# Diretrizes de Desenvolvimento e Escopo Naval

## Pilares Mandatórios do Sistema
1. **NORMAM (Normas da Autoridade Marítima - DPC/Marinha do Brasil)**:
   - Documentação, habilitação, dotação de equipamentos de salvatagem e combate a incêndio, arqueação, borda livre e vistorias técnicas navais (ex.: NORMAM-201, NORMAM-202, NORMAM-211, NORMAM-212, etc.).
2. **NPCP / NPCF (Normas e Procedimentos das Capitanias dos Portos e Fluviais)**:
   - Particularidades regionais de jurisdição, restrições geográficas, limites de velocidade em bacias/canais e regulamentos locais da Capitania.
3. **RIPEAM (Regulamento Internacional para Evitar Abalroamentos no Mar)**:
   - Regras de governo e navegação, luzes de navegação, marcas diurnas, sinais sonoros e de manobra.

## Restrições e Delimitação de Escopo
- **ISO / SGQ (Gestão da Qualidade)**:
  - Qualquer regra, auditoria, processo ou padronização exclusiva de conformidade ISO está **desconsiderada e separada** do núcleo do sistema.
  - Não misturar regras da Autoridade Marítima com auditorias de gestão da qualidade.
  - Foco integral na **simplicidade operacional**, **praticidade para o usuário** e **segurança da navegação**.

## Padrão de Usabilidade e Design Autodidático
- **Campos Autodidáticos**: Todos os formulários operacionais devem conter rótulos claros acompanhados de textos de auxílio explicativos (`<small class="text-muted">`), instruindo o usuário sobre o que preencher, referências regulamentares (NORMAM) e a finalidade daquele dado.
- **Atalhos Rápidos de Assunto / Ação (Pills/Chips)**: Disponibilizar botões de preenchimento em 1 clique para os assuntos mais frequentes da rotina naval, minimizando digitação repetitiva e prevenindo erros cadastrais.
- **Organização em Abas Especializadas**: Telas densas ou com múltiplos estágios (dossiês, propostas, relatórios de vistoria) devem ser organizadas em abas temáticas focadas (ex.: Linha do Tempo, Nova Ação Guiada, Trâmite Oficial/SISAP, Custódia, Anexos, Auditoria Criptográfica).
- **Cards de Indicadores (KPIs) com Filtragem Instantânea**: Listagens devem exibir no topo métricas acionáveis (alertas de exigências, custódia de originais pendente, prazos) com abas de filtros de situação rápida.
- **Vínculos Inteligentes Automáticos**: A seleção de uma entidade principal (ex.: Embarcação) deve atualizar ou pré-filtrar automaticamente entidades dependentes (Cliente/Armador, Processos de Análise abertos, Ordens de Vistoria).
- **Ações Rápidas de Compartilhamento**: Manter links tokenizados e botões de compartilhamento direto via WhatsApp e cópia de link para validação ou aceite com clientes e despachantes.
