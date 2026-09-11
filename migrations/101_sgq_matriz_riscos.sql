-- Migration 101: Módulo de Gestão de Riscos e Oportunidades (ISO 9001:2015 Cláusula 6.1 & NORMAM)

CREATE TABLE IF NOT EXISTS sgq_matriz_riscos (
    id CHAR(36) NOT NULL PRIMARY KEY,
    codigo_risco VARCHAR(30) NOT NULL UNIQUE,
    processo_setor VARCHAR(100) NOT NULL,
    tipo_risco ENUM('AMEACA','OPORTUNIDADE') NOT NULL DEFAULT 'AMEACA',
    descricao_risco VARCHAR(255) NOT NULL,
    causas TEXT NULL,
    impacto_consequencias TEXT NULL,
    probabilidade TINYINT UNSIGNED NOT NULL DEFAULT 3,
    impacto TINYINT UNSIGNED NOT NULL DEFAULT 3,
    nivel_risco ENUM('BAIXO','MEDIO','ALTO','CRITICO') NOT NULL DEFAULT 'MEDIO',
    acao_mitigacao TEXT NOT NULL,
    responsavel_nome VARCHAR(150) NOT NULL,
    prazo_revisao DATE NULL,
    status_tratamento ENUM('IDENTIFICADO','EM_MITIGACAO','MITIGADO','RESIDUAL_ACEITO') NOT NULL DEFAULT 'EM_MITIGACAO',
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_risco_processo (processo_setor),
    KEY idx_risco_nivel (nivel_risco),
    KEY idx_risco_status (status_tratamento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Sequencial para códigos RSK
INSERT INTO sequenciais_documentos (tipo_documento, ano, ultimo_numero)
SELECT 'RSK', YEAR(CURRENT_DATE), 0
WHERE NOT EXISTS (
    SELECT 1 FROM sequenciais_documentos WHERE tipo_documento = 'RSK' AND ano = YEAR(CURRENT_DATE)
);

-- Riscos operacionais e estratégicos regulatórios padrão
INSERT IGNORE INTO sgq_matriz_riscos 
    (id, codigo_risco, processo_setor, tipo_risco, descricao_risco, causas, impacto_consequencias, probabilidade, impacto, nivel_risco, acao_mitigacao, responsavel_nome, prazo_revisao, status_tratamento)
VALUES
    (UUID(), 'RSK-2026-001', 'Operação de Campo', 'AMEACA', 'Indisponibilidade de sinal 4G/5G em áreas fluviais e terminais de carga remotos', 'Infraestrutura de telecomunicações instável na bacia amazônica', 'Atraso no sincronismo de laudos e fotos da vistoria', 4, 3, 'ALTO', 'Utilização obrigatória do PWA de Campo com suporte a armazenamento local (IndexedDB) e sincronização resiliente em segundo plano.', 'Coordenador Operacional', DATE_ADD(CURRENT_DATE, INTERVAL 6 MONTH), 'EM_MITIGACAO'),
    (UUID(), 'RSK-2026-002', 'Competência Técnica', 'AMEACA', 'Escalação de vistoriador com credencial profissional ou da Autoridade Marítima (DPC) expirada', 'Falha no acompanhamento tempestivo do calendário de reciclagem', 'Rejeição do laudo pela Capitania dos Portos e não conformidade ISO 7.2', 2, 5, 'ALTO', 'Trava automática de validação no ERP no ato do agendamento contra a data da vistoria com bloqueio e retorno HTTP 400 em caso de tentativa de burla.', 'Responsável Técnico', DATE_ADD(CURRENT_DATE, INTERVAL 1 YEAR), 'MITIGADO'),
    (UUID(), 'RSK-2026-003', 'Controle Metrológico', 'AMEACA', 'Utilização de aparelho de medição de espessura de chapeamento com laudo de calibração RBC vencido', 'Falta de calibração periódica do transdutor de ultrassom', 'Imprecisão na medição de perda de espessura de casco e apontamentos A/S contestados', 2, 4, 'MEDIO', 'Plano de calibração anual compulsório de todos os instrumentos de medição da empresa em laboratório RBC credenciado pelo Inmetro.', 'Gerente da Qualidade', DATE_ADD(CURRENT_DATE, INTERVAL 6 MONTH), 'EM_MITIGACAO'),
    (UUID(), 'RSK-2026-004', 'Regulatório / NORMAM', 'AMEACA', 'Publicação de nova Portaria da DPC alterando critérios de salvatagem sem atualização imediata dos checklists', 'Descompasso entre o Diário Oficial da União e as rotinas operacionais', 'Emissão de relatório com base em versão revogada de NORMAM', 3, 3, 'MEDIO', 'Reunião mensal de alinhamento normativo do corpo técnico e revisão imediata dos blocos de checklist no ERP.', 'Responsável Técnico', DATE_ADD(CURRENT_DATE, INTERVAL 3 MONTH), 'EM_MITIGACAO'),
    (UUID(), 'RSK-2026-005', 'Comercial e Estratégico', 'OPORTUNIDADE', 'Aumento de demanda por certificação de comboios de empurradores fluviais de grande porte no Arco Norte', 'Crescimento do escoamento de safras agrícolas por hidrovias', 'Expansão da receita e consolidação da liderança técnica regional', 4, 4, 'ALTO', 'Treinamento e habilitação do corpo de engenheiros navais para vistorias em comboios integrados e balsas oceânicas.', 'Diretoria Executiva', DATE_ADD(CURRENT_DATE, INTERVAL 6 MONTH), 'EM_MITIGACAO');
