-- Central de Feedback / Mensagens Internas
ALTER TABLE usuarios
    ADD COLUMN gestor_id CHAR(36) NULL AFTER escritorio_id,
    ADD INDEX idx_usuarios_gestor (gestor_id),
    ADD CONSTRAINT fk_usuarios_gestor FOREIGN KEY (gestor_id) REFERENCES usuarios(id) ON DELETE SET NULL;

CREATE TABLE feedback_regras_comunicacao (
    id CHAR(36) NOT NULL PRIMARY KEY,
    cargo_origem ENUM('ADMIN','VENDEDOR','VISTORIADOR','ANALISTA') NOT NULL,
    escopo ENUM('ADMIN','GESTOR_DIRETO','SUBORDINADOS','OUTROS_GESTORES','CARGO') NOT NULL,
    cargo_destino ENUM('ADMIN','VENDEDOR','VISTORIADOR','ANALISTA') NULL,
    chave_destino VARCHAR(20) GENERATED ALWAYS AS (COALESCE(cargo_destino, '')) STORED,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_feedback_regra (cargo_origem, escopo, chave_destino),
    INDEX idx_feedback_regra_consulta (cargo_origem, ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE feedbacks (
    id CHAR(36) NOT NULL PRIMARY KEY,
    remetente_id CHAR(36) NOT NULL,
    destinatario_id CHAR(36) NULL COMMENT 'NULL representa a caixa compartilhada dos administradores',
    categoria ENUM('DUVIDA','SUGESTAO','BUG','RECLAMACAO','ELOGIO') NOT NULL,
    prioridade ENUM('BAIXA','MEDIA','ALTA','URGENTE') NOT NULL DEFAULT 'MEDIA',
    status ENUM('ABERTO','RESPONDIDO','RESOLVIDO') NOT NULL DEFAULT 'ABERTO',
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_feedback_remetente FOREIGN KEY (remetente_id) REFERENCES usuarios(id),
    CONSTRAINT fk_feedback_destinatario FOREIGN KEY (destinatario_id) REFERENCES usuarios(id),
    INDEX idx_feedback_destino (destinatario_id, atualizado_em),
    INDEX idx_feedback_remetente (remetente_id, atualizado_em),
    INDEX idx_feedback_filtros (status, prioridade, categoria, criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE feedback_mensagens (
    id CHAR(36) NOT NULL PRIMARY KEY,
    feedback_id CHAR(36) NOT NULL,
    autor_id CHAR(36) NOT NULL,
    texto TEXT NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_feedback_mensagem_feedback FOREIGN KEY (feedback_id) REFERENCES feedbacks(id) ON DELETE CASCADE,
    CONSTRAINT fk_feedback_mensagem_autor FOREIGN KEY (autor_id) REFERENCES usuarios(id),
    INDEX idx_feedback_mensagem_thread (feedback_id, criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE feedback_anexos (
    id CHAR(36) NOT NULL PRIMARY KEY,
    mensagem_id CHAR(36) NOT NULL,
    nome_arquivo VARCHAR(255) NOT NULL,
    chave_arquivo VARCHAR(600) NOT NULL,
    tipo_mime VARCHAR(150) NOT NULL,
    extensao VARCHAR(10) NOT NULL,
    tamanho BIGINT UNSIGNED NOT NULL,
    sha256 CHAR(64) NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_feedback_anexo_mensagem FOREIGN KEY (mensagem_id) REFERENCES feedback_mensagens(id) ON DELETE CASCADE,
    INDEX idx_feedback_anexo_mensagem (mensagem_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE feedback_participantes (
    feedback_id CHAR(36) NOT NULL,
    usuario_id CHAR(36) NOT NULL,
    lida TINYINT(1) NOT NULL DEFAULT 0,
    arquivado_em DATETIME NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (feedback_id, usuario_id),
    CONSTRAINT fk_feedback_participante_feedback FOREIGN KEY (feedback_id) REFERENCES feedbacks(id) ON DELETE CASCADE,
    CONSTRAINT fk_feedback_participante_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    INDEX idx_feedback_participante_nao_lida (usuario_id, lida, arquivado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO feedback_regras_comunicacao (id, cargo_origem, escopo, cargo_destino)
SELECT UUID(), cargo, escopo, NULL
FROM (
    SELECT 'VENDEDOR' cargo UNION ALL SELECT 'VISTORIADOR' UNION ALL SELECT 'ANALISTA'
) cargos
CROSS JOIN (
    SELECT 'ADMIN' escopo UNION ALL SELECT 'GESTOR_DIRETO' UNION ALL
    SELECT 'SUBORDINADOS' UNION ALL SELECT 'OUTROS_GESTORES'
) escopos;

-- Rollback manual (apaga os dados do modulo):
-- DROP TABLE feedback_participantes, feedback_anexos, feedback_mensagens, feedbacks, feedback_regras_comunicacao;
-- ALTER TABLE usuarios DROP FOREIGN KEY fk_usuarios_gestor, DROP INDEX idx_usuarios_gestor, DROP COLUMN gestor_id;
