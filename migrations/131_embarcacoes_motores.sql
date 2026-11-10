-- Migration 131: Suporte a múltiplos motores propulsores por embarcação (NORMAM-201/202 e CSN)
-- Mantém compatibilidade integral com colunas legadas de embarcacoes (fabricante_motor, modelo_motor, numero_motor, potencia_kw)

CREATE TABLE IF NOT EXISTS `embarcacoes_motores` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `embarcacao_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ordem` int NOT NULL DEFAULT 1,
  `posicao` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fabricante` varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `modelo` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `numero` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `potencia_kw` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_emb_motores_emb` (`embarcacao_id`, `ordem`),
  CONSTRAINT `fk_emb_motores_embarcacao` FOREIGN KEY (`embarcacao_id`) REFERENCES `embarcacoes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Migrar motores já cadastrados nas embarcações existentes (idempotente)
INSERT INTO `embarcacoes_motores` (`id`, `embarcacao_id`, `ordem`, `posicao`, `fabricante`, `modelo`, `numero`, `potencia_kw`)
SELECT UUID(), e.id, 1, NULL,
       NULLIF(TRIM(e.fabricante_motor), ''),
       NULLIF(TRIM(e.modelo_motor), ''),
       NULLIF(TRIM(e.numero_motor), ''),
       NULLIF(TRIM(e.potencia_kw), '')
FROM `embarcacoes` e
WHERE (
    NULLIF(TRIM(COALESCE(e.fabricante_motor, '')), '') IS NOT NULL
    OR NULLIF(TRIM(COALESCE(e.modelo_motor, '')), '') IS NOT NULL
    OR NULLIF(TRIM(COALESCE(e.numero_motor, '')), '') IS NOT NULL
    OR NULLIF(TRIM(COALESCE(e.potencia_kw, '')), '') IS NOT NULL
)
  AND NOT EXISTS (
    SELECT 1 FROM `embarcacoes_motores` em WHERE em.embarcacao_id = e.id
);

-- Ampliar capacidade textual para consolidar múltiplos motores no CSN e potência total na embarcação
ALTER TABLE `embarcacoes`
  MODIFY COLUMN `potencia_kw` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL;

ALTER TABLE `certificados_csn`
  MODIFY COLUMN `fabricante_motor` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  MODIFY COLUMN `potencia_kw` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL;
