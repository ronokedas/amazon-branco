-- Migration 134: Criar tabela para registro de alertas dispensados por usuário
-- Permite fechar alertas de relatórios assinados ou pendências já tratadas sem poluir o dashboard

CREATE TABLE IF NOT EXISTS `alertas_dispensados` (
    `id` int NOT NULL AUTO_INCREMENT,
    `usuario_id` char(36) NOT NULL,
    `alerta_tipo` varchar(50) NOT NULL,
    `referencia_id` char(36) NOT NULL,
    `dispensado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_usuario_alerta_ref` (`usuario_id`, `alerta_tipo`, `referencia_id`),
    KEY `idx_dispensa_busca` (`usuario_id`, `alerta_tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
