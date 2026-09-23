-- Migration 114: Sequenciais para modalidades de licenças do Analista (LA, LR, EC)
INSERT IGNORE INTO `sequenciais_documentos` (`tipo_documento`, `ano`, `ultimo_numero`) VALUES ('LA', 2026, 0);
INSERT IGNORE INTO `sequenciais_documentos` (`tipo_documento`, `ano`, `ultimo_numero`) VALUES ('LR', 2026, 0);
INSERT IGNORE INTO `sequenciais_documentos` (`tipo_documento`, `ano`, `ultimo_numero`) VALUES ('EC', 2026, 0);
