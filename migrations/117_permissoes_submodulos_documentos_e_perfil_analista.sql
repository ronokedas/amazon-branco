-- Migration 117: Submódulos granulares de documentos e restrição do perfil Analista Naval
-- O analista não acessa CSN, CNBL, CNARQ, Embarcações, Clientes ou Protocolos.
-- O analista acessa exclusivamente Análise de Planos, NAR, LP, LC/LA/LR/LCEC e Banco NORMAM.

-- 1. Inserir permissões de submódulos de documentos para todos os usuários não-admin
INSERT IGNORE INTO usuario_permissoes (usuario_id, permissao, permitido, atualizado_em)
SELECT u.id, p.permissao, 
    CASE 
        WHEN u.cargo = 'ANALISTA' AND p.permissao IN ('doc_nar', 'doc_lp', 'doc_lc') THEN 1
        WHEN u.cargo = 'ANALISTA' THEN 0
        WHEN u.cargo = 'VISTORIADOR' THEN 1
        ELSE 0
    END,
    NOW()
FROM usuarios u
CROSS JOIN (
    SELECT 'doc_csn' AS permissao UNION ALL
    SELECT 'doc_cnbl' UNION ALL
    SELECT 'doc_cnarq' UNION ALL
    SELECT 'doc_nar' UNION ALL
    SELECT 'doc_lp' UNION ALL
    SELECT 'doc_lc' UNION ALL
    SELECT 'doc_cht'
) p
WHERE u.cargo != 'ADMIN' AND u.excluido_em IS NULL;

-- 2. Atualizar permissões específicas para o cargo ANALISTA
-- Revogar expressamente o que é proibido para analista
UPDATE usuario_permissoes up
JOIN usuarios u ON u.id = up.usuario_id
SET up.permitido = 0, up.atualizado_em = NOW()
WHERE u.cargo = 'ANALISTA'
  AND up.permissao IN (
      'doc_csn', 'doc_cnbl', 'doc_cnarq', 'doc_cht', 'documentacao',
      'embarcacoes', 'clientes', 'armadores', 'proprietarios', 'despachantes',
      'protocolos_documentais', 'certificados', 'vencimentos_certificados',
      'vistorias', 'agendamentos', 'relatorios_aprovacao'
  );

-- Conceder expressamente o que o analista deve operar
UPDATE usuario_permissoes up
JOIN usuarios u ON u.id = up.usuario_id
SET up.permitido = 1, up.atualizado_em = NOW()
WHERE u.cargo = 'ANALISTA'
  AND up.permissao IN (
      'dashboard', 'analise_planos', 'doc_nar', 'doc_lp', 'doc_lc', 'configuracoes_normam202'
  );

-- 3. Garantir que os submódulos doc_nar, doc_lp, doc_lc estejam com 1 para o analista caso não existissem
INSERT INTO usuario_permissoes (usuario_id, permissao, permitido, atualizado_em)
SELECT u.id, 'doc_nar', 1, NOW()
FROM usuarios u
WHERE u.cargo = 'ANALISTA' AND u.excluido_em IS NULL
ON DUPLICATE KEY UPDATE permitido = 1;

INSERT INTO usuario_permissoes (usuario_id, permissao, permitido, atualizado_em)
SELECT u.id, 'doc_lp', 1, NOW()
FROM usuarios u
WHERE u.cargo = 'ANALISTA' AND u.excluido_em IS NULL
ON DUPLICATE KEY UPDATE permitido = 1;

INSERT INTO usuario_permissoes (usuario_id, permissao, permitido, atualizado_em)
SELECT u.id, 'doc_lc', 1, NOW()
FROM usuarios u
WHERE u.cargo = 'ANALISTA' AND u.excluido_em IS NULL
ON DUPLICATE KEY UPDATE permitido = 1;

-- 4. Invalidação de sessões de usuários para atualização em tempo real
UPDATE usuarios 
SET versao_sessao = versao_sessao + 1 
WHERE cargo = 'ANALISTA' AND excluido_em IS NULL;
