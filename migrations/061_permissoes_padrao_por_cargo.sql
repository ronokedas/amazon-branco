-- Ativa os módulos essenciais por cargo sem remover liberações personalizadas.

INSERT INTO usuario_permissoes (usuario_id, permissao, permitido)
SELECT u.id, p.permissao, 1
FROM usuarios u
JOIN (
    SELECT 'VENDEDOR' cargo, 'dashboard' permissao UNION ALL
    SELECT 'VENDEDOR', 'embarcacoes' UNION ALL
    SELECT 'VENDEDOR', 'armadores' UNION ALL
    SELECT 'VENDEDOR', 'proprietarios' UNION ALL
    SELECT 'VENDEDOR', 'despachantes' UNION ALL
    SELECT 'VENDEDOR', 'vistorias' UNION ALL
    SELECT 'VENDEDOR', 'agendamentos' UNION ALL
    SELECT 'VENDEDOR', 'comercial' UNION ALL
    SELECT 'VENDEDOR', 'servicos' UNION ALL
    SELECT 'VENDEDOR', 'contratos' UNION ALL
    SELECT 'VENDEDOR', 'emails' UNION ALL
    SELECT 'VISTORIADOR', 'dashboard' UNION ALL
    SELECT 'VISTORIADOR', 'vistorias' UNION ALL
    SELECT 'VISTORIADOR', 'embarcacoes' UNION ALL
    SELECT 'VISTORIADOR', 'certificados' UNION ALL
    SELECT 'VISTORIADOR', 'documentacao' UNION ALL
    SELECT 'ANALISTA', 'dashboard' UNION ALL
    SELECT 'ANALISTA', 'vistorias' UNION ALL
    SELECT 'ANALISTA', 'relatorios_aprovacao'
) p ON p.cargo = u.cargo
WHERE u.cargo <> 'ADMIN'
ON DUPLICATE KEY UPDATE permitido = 1;
