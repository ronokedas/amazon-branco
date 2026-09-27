-- Migration 118: Modelos Customizáveis de Observações Técnicas das Licenças Navais (LC, LA, LR, LCEC)
-- Permite que o Analista Naval edite, personalize e mantenha salvos os seus próprios modelos de observações
-- com suporte a interpolação inteligente de tags dinâmicas do formulário (quilha, RAP, dimensões, lotação, etc.)

CREATE TABLE IF NOT EXISTS `modelos_observacoes_licenca` (
    `id` VARCHAR(36) NOT NULL PRIMARY KEY,
    `usuario_id` VARCHAR(36) NULL,
    `tipo_licenca` VARCHAR(10) NOT NULL,
    `titulo` VARCHAR(150) NOT NULL,
    `conteudo_template` TEXT NOT NULL,
    `ativo` TINYINT(1) NOT NULL DEFAULT 1,
    `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_usuario_tipo_licenca` (`usuario_id`, `tipo_licenca`),
    KEY `idx_tipo_licenca` (`tipo_licenca`),
    KEY `idx_usuario_id` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
