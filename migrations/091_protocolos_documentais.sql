-- Protocolo de entrada, saída e tramitação documental.

CREATE TABLE protocolo_unidades_maritimas (
  id char(36) NOT NULL,
  codigo varchar(30) NULL,
  nome varchar(180) NOT NULL,
  tipo enum('CAPITANIA','DELEGACIA','AGENCIA') NOT NULL,
  cidade varchar(120) NOT NULL,
  uf char(2) NOT NULL,
  endereco varchar(500) NULL,
  telefone varchar(50) NULL,
  email varchar(180) NULL,
  jurisdicao text NULL,
  formato_protocolo_regex varchar(255) NULL,
  url_consulta varchar(500) NULL,
  ativo tinyint(1) NOT NULL DEFAULT 1,
  criado_por char(36) NULL,
  criado_em datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_protocolo_unidade_nome_cidade (nome,cidade,uf),
  CONSTRAINT fk_protocolo_unidade_criador FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE protocolo_catalogo_documentos (
  id char(36) NOT NULL,
  codigo varchar(50) NOT NULL,
  categoria enum('ANALISE_PLANOS','VISTORIA','INSCRICAO','CERTIFICADOS','PROPRIEDADE','RESPONSABILIDADE_TECNICA','DOCUMENTOS_PESSOAIS','OUTROS') NOT NULL,
  nome varchar(180) NOT NULL,
  contexto varchar(100) NULL,
  norma_referencia varchar(255) NULL,
  ativo tinyint(1) NOT NULL DEFAULT 1,
  ordem int NOT NULL DEFAULT 0,
  criado_em datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_protocolo_catalogo_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE protocolo_dossies (
  id char(36) NOT NULL,
  numero varchar(40) NOT NULL,
  embarcacao_id char(36) NOT NULL,
  cliente_id char(36) NULL,
  assunto varchar(255) NOT NULL,
  servico_id char(36) NULL,
  proposta_id char(36) NULL,
  analise_id char(36) NULL,
  vistoria_id char(36) NULL,
  certificado_tipo varchar(30) NULL,
  certificado_id char(36) NULL,
  status enum('EM_PREPARACAO','ENVIADO_AO_ORGAO','PROTOCOLADO','EM_ANALISE_NO_ORGAO','EM_EXIGENCIA','A_DISPOSICAO','RETIRADO','ENTREGUE_AO_CLIENTE','ENCERRADO','CANCELADO') NOT NULL DEFAULT 'EM_PREPARACAO',
  protocolo_externo_numero varchar(100) NULL,
  protocolo_externo_em datetime NULL,
  protocolo_externo_validade date NULL,
  unidade_maritima_id char(36) NULL,
  cancelado_motivo varchar(500) NULL,
  cancelado_por char(36) NULL,
  cancelado_em datetime NULL,
  criado_por char(36) NOT NULL,
  criado_em datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_protocolo_dossie_numero (numero),
  KEY idx_protocolo_dossie_busca (embarcacao_id,cliente_id,status),
  KEY idx_protocolo_dossie_vinculos (analise_id,vistoria_id),
  CONSTRAINT fk_protocolo_dossie_embarcacao FOREIGN KEY (embarcacao_id) REFERENCES embarcacoes(id) ON DELETE RESTRICT,
  CONSTRAINT fk_protocolo_dossie_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL,
  CONSTRAINT fk_protocolo_dossie_servico FOREIGN KEY (servico_id) REFERENCES servicos(id) ON DELETE SET NULL,
  CONSTRAINT fk_protocolo_dossie_proposta FOREIGN KEY (proposta_id) REFERENCES propostas(id) ON DELETE SET NULL,
  CONSTRAINT fk_protocolo_dossie_analise FOREIGN KEY (analise_id) REFERENCES analises_planos(id) ON DELETE SET NULL,
  CONSTRAINT fk_protocolo_dossie_vistoria FOREIGN KEY (vistoria_id) REFERENCES vistorias(id) ON DELETE SET NULL,
  CONSTRAINT fk_protocolo_dossie_unidade FOREIGN KEY (unidade_maritima_id) REFERENCES protocolo_unidades_maritimas(id) ON DELETE SET NULL,
  CONSTRAINT fk_protocolo_dossie_criador FOREIGN KEY (criado_por) REFERENCES usuarios(id),
  CONSTRAINT fk_protocolo_dossie_cancelador FOREIGN KEY (cancelado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE protocolo_movimentacoes (
  id char(36) NOT NULL,
  dossie_id char(36) NOT NULL,
  sequencia int unsigned NOT NULL,
  tipo enum('ENTRADA','SAIDA') NOT NULL,
  natureza enum('RECEBIMENTO_CLIENTE','ENVIO_ORGAO','RETORNO_ORGAO','CUMPRIMENTO_EXIGENCIA','RETIRADA_ORGAO','ENTREGA_CLIENTE','TRANSFERENCIA_INTERNA','OUTRA') NOT NULL,
  status enum('RASCUNHO','CONFIRMADA','RETIFICADA','CANCELADA') NOT NULL DEFAULT 'RASCUNHO',
  origem_tipo enum('CLIENTE','REPRESENTANTE','AMAZON_NAVAL','CAPITANIA','DELEGACIA','AGENCIA','CORREIOS','TRANSPORTADORA','OUTRO') NOT NULL,
  origem_nome varchar(180) NOT NULL,
  destino_tipo enum('CLIENTE','REPRESENTANTE','AMAZON_NAVAL','CAPITANIA','DELEGACIA','AGENCIA','CORREIOS','TRANSPORTADORA','OUTRO') NOT NULL,
  destino_nome varchar(180) NOT NULL,
  unidade_maritima_id char(36) NULL,
  cidade varchar(120) NOT NULL,
  uf char(2) NOT NULL,
  meio_envio enum('PRESENCIAL','EMAIL','PORTAL','CORREIOS','TRANSPORTADORA','MENSAGEIRO','OUTRO') NOT NULL,
  portador_nome varchar(180) NULL,
  codigo_rastreio varchar(120) NULL,
  movimentado_em datetime NOT NULL,
  observacoes text NULL,
  retifica_movimentacao_id char(36) NULL,
  protocolo_anterior_id char(36) NULL,
  idempotency_key varchar(100) NULL,
  snapshot_json json NULL,
  pdf_caminho varchar(500) NULL,
  pdf_hash char(64) NULL,
  confirmado_por char(36) NULL,
  confirmado_em datetime NULL,
  criado_por char(36) NOT NULL,
  criado_em datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_protocolo_movimento_seq (dossie_id,sequencia),
  UNIQUE KEY uk_protocolo_movimento_idempotencia (dossie_id,idempotency_key),
  KEY idx_protocolo_movimento_status (dossie_id,status,movimentado_em),
  CONSTRAINT fk_protocolo_movimento_dossie FOREIGN KEY (dossie_id) REFERENCES protocolo_dossies(id) ON DELETE RESTRICT,
  CONSTRAINT fk_protocolo_movimento_unidade FOREIGN KEY (unidade_maritima_id) REFERENCES protocolo_unidades_maritimas(id) ON DELETE SET NULL,
  CONSTRAINT fk_protocolo_movimento_retifica FOREIGN KEY (retifica_movimentacao_id) REFERENCES protocolo_movimentacoes(id) ON DELETE RESTRICT,
  CONSTRAINT fk_protocolo_movimento_anterior FOREIGN KEY (protocolo_anterior_id) REFERENCES protocolo_movimentacoes(id) ON DELETE RESTRICT,
  CONSTRAINT fk_protocolo_movimento_confirmador FOREIGN KEY (confirmado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
  CONSTRAINT fk_protocolo_movimento_criador FOREIGN KEY (criado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE protocolo_movimentacao_itens (
  id char(36) NOT NULL,
  movimentacao_id char(36) NOT NULL,
  catalogo_id char(36) NULL,
  descricao varchar(255) NOT NULL,
  categoria varchar(60) NOT NULL,
  suporte enum('FISICO','DIGITAL') NOT NULL,
  forma enum('ORIGINAL','COPIA_SIMPLES','COPIA_AUTENTICADA','NATO_DIGITAL','DIGITALIZADO') NOT NULL,
  quantidade int unsigned NOT NULL DEFAULT 1,
  numero_revisao varchar(100) NULL,
  data_documento date NULL,
  condicao_documento varchar(120) NULL,
  requer_devolucao tinyint(1) NOT NULL DEFAULT 0,
  devolvido_em datetime NULL,
  arquivo_origem_tipo varchar(60) NULL,
  arquivo_origem_id char(36) NULL,
  arquivo_nome varchar(255) NULL,
  arquivo_hash char(64) NULL,
  observacao text NULL,
  criado_em datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_protocolo_item_movimento (movimentacao_id),
  CONSTRAINT fk_protocolo_item_movimento FOREIGN KEY (movimentacao_id) REFERENCES protocolo_movimentacoes(id) ON DELETE RESTRICT,
  CONSTRAINT fk_protocolo_item_catalogo FOREIGN KEY (catalogo_id) REFERENCES protocolo_catalogo_documentos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE protocolo_comprovantes (
  id char(36) NOT NULL,
  dossie_id char(36) NOT NULL,
  movimentacao_id char(36) NULL,
  tipo enum('PROTOCOLO_EXTERNO','RECIBO','COMPROVANTE_ENTREGA','RASTREIO','OUTRO') NOT NULL,
  nome_original varchar(255) NOT NULL,
  mime_type varchar(120) NOT NULL,
  tamanho_bytes bigint unsigned NOT NULL,
  sha256 char(64) NOT NULL,
  caminho varchar(500) NOT NULL,
  criado_por char(36) NOT NULL,
  criado_em datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_protocolo_comprovante_dossie (dossie_id,movimentacao_id),
  CONSTRAINT fk_protocolo_comprovante_dossie FOREIGN KEY (dossie_id) REFERENCES protocolo_dossies(id) ON DELETE RESTRICT,
  CONSTRAINT fk_protocolo_comprovante_mov FOREIGN KEY (movimentacao_id) REFERENCES protocolo_movimentacoes(id) ON DELETE RESTRICT,
  CONSTRAINT fk_protocolo_comprovante_usuario FOREIGN KEY (criado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE protocolo_aceites (
  id char(36) NOT NULL,
  movimentacao_id char(36) NOT NULL,
  token_hash char(64) NOT NULL,
  expira_em datetime NOT NULL,
  nome varchar(180) NULL,
  documento_mascarado varchar(40) NULL,
  termo_aceito tinyint(1) NOT NULL DEFAULT 0,
  ip varchar(45) NULL,
  aceito_em datetime NULL,
  criado_por char(36) NOT NULL,
  criado_em datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_protocolo_aceite_token (token_hash),
  UNIQUE KEY uk_protocolo_aceite_mov (movimentacao_id),
  CONSTRAINT fk_protocolo_aceite_mov FOREIGN KEY (movimentacao_id) REFERENCES protocolo_movimentacoes(id) ON DELETE RESTRICT,
  CONSTRAINT fk_protocolo_aceite_criador FOREIGN KEY (criado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE protocolo_auditoria (
  id bigint unsigned NOT NULL AUTO_INCREMENT,
  dossie_id char(36) NOT NULL,
  movimentacao_id char(36) NULL,
  evento varchar(80) NOT NULL,
  usuario_id char(36) NULL,
  perfil varchar(40) NULL,
  ip varchar(45) NULL,
  estado_anterior varchar(60) NULL,
  estado_novo varchar(60) NULL,
  detalhe text NULL,
  hash_referencia char(64) NULL,
  criado_em datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_protocolo_auditoria (dossie_id,criado_em),
  CONSTRAINT fk_protocolo_auditoria_dossie FOREIGN KEY (dossie_id) REFERENCES protocolo_dossies(id) ON DELETE RESTRICT,
  CONSTRAINT fk_protocolo_auditoria_mov FOREIGN KEY (movimentacao_id) REFERENCES protocolo_movimentacoes(id) ON DELETE SET NULL,
  CONSTRAINT fk_protocolo_auditoria_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO sequenciais_documentos (tipo_documento,ano,ultimo_numero)
VALUES ('PROTOCOLO',YEAR(CURDATE()),0);

INSERT INTO protocolo_catalogo_documentos (id,codigo,categoria,nome,contexto,norma_referencia,ordem) VALUES
(UUID(),'REQ_INTERESSADO','ANALISE_PLANOS','Requerimento do interessado','NORMAM-202','NORMAM-202/DPC',10),
(UUID(),'ART','RESPONSABILIDADE_TECNICA','Anotação de Responsabilidade Técnica (ART)','NORMAM-202','NORMAM-202/DPC',20),
(UUID(),'MEMORIAL_DESCRITIVO','ANALISE_PLANOS','Memorial descritivo','LC_LCEC','NORMAM-202/DPC',30),
(UUID(),'PLANO_ARRANJO_GERAL','ANALISE_PLANOS','Plano de arranjo geral','LC_LCEC','NORMAM-202/DPC',40),
(UUID(),'PLANO_LINHAS','ANALISE_PLANOS','Plano de linhas','LC_LCEC','NORMAM-202/DPC',50),
(UUID(),'PLANO_SEGURANCA','ANALISE_PLANOS','Plano de segurança','LC_LCEC','NORMAM-202/DPC',60),
(UUID(),'CALCULOS_ESTABILIDADE','ANALISE_PLANOS','Cálculos e folheto de estabilidade','LC_LCEC','NORMAM-202/DPC',70),
(UUID(),'RELATORIO_VISTORIA','VISTORIA','Relatório técnico de vistoria','VISTORIA','NORMAM-202/DPC',80),
(UUID(),'CERTIFICADO_EXISTENTE','CERTIFICADOS','Certificado ou licença existente','DOCUMENTACAO',NULL,90),
(UUID(),'TIE_TIEM','INSCRICAO','TIE/TIEM ou documento de inscrição','INSCRICAO',NULL,100),
(UUID(),'DOCUMENTO_PROPRIEDADE','PROPRIEDADE','Documento de propriedade da embarcação','PROPRIEDADE',NULL,110),
(UUID(),'DOCUMENTO_PESSOAL','DOCUMENTOS_PESSOAIS','Documento de identificação do interessado/representante','GERAL',NULL,120),
(UUID(),'PROCURACAO','DOCUMENTOS_PESSOAIS','Procuração do representante','GERAL',NULL,130);

INSERT INTO protocolo_unidades_maritimas
  (id,codigo,nome,tipo,cidade,uf,url_consulta)
VALUES
  (UUID(),'CPAOR','Capitania dos Portos da Amazônia Oriental','CAPITANIA','Belém','PA','https://atendimento-dpc.marinha.mil.br/sisap/agendamento/consultaprocesso/#/');
