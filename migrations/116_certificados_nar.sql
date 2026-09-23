-- Migração 116: Criação da Tabela de Notas de Arqueação (AM-NAR) e Unificação de Memorial Descritivo
-- Escopo Naval: NORMAM-201 / NORMAM-202 / DPC

-- 1. Unificação da categoria 'MEMORIAL DESCRITO' -> 'MEMORIAL DESCRITIVO'
UPDATE analise_planos_referencias_normam SET categoria = 'MEMORIAL DESCRITIVO' WHERE categoria = 'MEMORIAL DESCRITO';
UPDATE analise_planos_exigencias SET categoria = 'MEMORIAL DESCRITIVO' WHERE categoria = 'MEMORIAL DESCRITO';
UPDATE analise_planos_arquivos SET categoria = 'MEMORIAL DESCRITIVO' WHERE categoria = 'MEMORIAL DESCRITO';

-- 2. Tabela de Notas de Arqueação de Embarcações (AM-NAR)
CREATE TABLE IF NOT EXISTS certificados_nar (
    id CHAR(36) NOT NULL PRIMARY KEY,
    numero VARCHAR(50) NOT NULL,
    ano INT NOT NULL,
    sequencial INT NOT NULL,
    enquadramento_comprimento ENUM('L_MAIOR_IGUAL_24','L_MENOR_24') NOT NULL DEFAULT 'L_MAIOR_IGUAL_24',
    embarcacao_id CHAR(36) NOT NULL,
    cliente_id CHAR(36) NULL,
    analise_id CHAR(36) NULL,
    token_assinatura CHAR(64) NOT NULL,
    status ENUM('rascunho','emitido','assinado','cancelado') NOT NULL DEFAULT 'rascunho',
    
    -- Características Gerais
    nome_embarcacao VARCHAR(255) NOT NULL,
    armador VARCHAR(255) NULL,
    construtor VARCHAR(255) NULL,
    numero_casco VARCHAR(100) NULL,
    material_casco VARCHAR(100) NULL,
    tipo_embarcacao VARCHAR(100) NULL,
    atividade_servico VARCHAR(150) NULL,
    classificacao VARCHAR(150) NULL,
    porto_inscricao VARCHAR(150) NULL,
    data_construcao_quilha VARCHAR(100) NULL,

    -- Características do Casco
    comprimento_total_ct DECIMAL(8,3) NULL,
    comprimento_regra_l DECIMAL(8,3) NULL,
    comprimento_lpp DECIMAL(8,3) NULL,
    boca_moldada_b DECIMAL(8,3) NULL,
    pontal_moldado_p DECIMAL(8,3) NULL,
    calado_leve_av DECIMAL(8,3) NULL,
    calado_leve_ar DECIMAL(8,3) NULL,
    calado_leve_medio DECIMAL(8,3) NULL,
    calado_carregado_av DECIMAL(8,3) NULL,
    calado_carregado_ar DECIMAL(8,3) NULL,
    calado_carregado_medio DECIMAL(8,3) NULL,

    -- Tripulantes e Passageiros
    numero_tripulantes INT NULL DEFAULT 0,
    n1_passageiros_camarotes INT NULL DEFAULT 0,
    n2_demais_passageiros INT NULL DEFAULT 0,

    -- Características Calculadas e Volumes
    deslocamento_carregado DECIMAL(10,3) NULL,
    deslocamento_leve DECIMAL(10,3) NULL,
    porte_bruto DECIMAL(10,3) NULL,
    espacos_fechados_abaixo_conves DECIMAL(12,2) NULL DEFAULT 0,
    espacos_fechados_acima_conves DECIMAL(12,2) NULL DEFAULT 0,
    espacos_excluidos DECIMAL(12,2) NULL DEFAULT 0,
    volume_total_fechado_v DECIMAL(12,2) NULL DEFAULT 0,
    volume_espacos_carga_vc DECIMAL(12,2) NULL DEFAULT 0,
    coeficiente_k1 DECIMAL(8,4) NULL,
    coeficiente_k2 DECIMAL(8,4) NULL,
    arqueacao_bruta_ab INT NULL,
    arqueacao_liquida_al INT NULL,

    -- Detalhamento dos Volumes em JSON
    volumes_abaixo_conves_json LONGTEXT NULL,
    volumes_acima_conves_json LONGTEXT NULL,
    volumes_excluidos_json LONGTEXT NULL,
    volumes_carga_json LONGTEXT NULL,
    metodo_obtencao_abaixo VARCHAR(255) NULL DEFAULT 'Volume obtido com a utilização de curvas hidrostáticas.',
    metodo_obtencao_acima VARCHAR(255) NULL DEFAULT 'Volume obtido com a utilização de formas geométricas.',

    -- Seção de Observações / NOTAS Técnicas
    observacoes_notas LONGTEXT NULL,

    -- Responsável Técnico e Assinatura
    responsavel_assinatura_id INT NULL,
    assinante_nome VARCHAR(200) NULL,
    assinante_titulo VARCHAR(200) NULL,
    assinante_registro VARCHAR(100) NULL,
    local_emissao VARCHAR(150) NULL DEFAULT 'Belém - PA',
    data_emissao DATE NOT NULL,
    assinatura_imagem LONGTEXT NULL,
    assinatura_ip VARCHAR(45) NULL,
    assinatura_em DATETIME NULL,
    assinado TINYINT(1) NOT NULL DEFAULT 0,
    caminho_arquivo_pdf VARCHAR(255) NULL,
    hash_arquivo_pdf CHAR(64) NULL,

    -- Controle e Auditoria
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_por CHAR(36) NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_nar_embarcacao (embarcacao_id),
    INDEX idx_nar_cliente (cliente_id),
    INDEX idx_nar_analise (analise_id),
    INDEX idx_nar_token (token_assinatura),
    INDEX idx_nar_status (status),
    INDEX idx_nar_numero (numero)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
