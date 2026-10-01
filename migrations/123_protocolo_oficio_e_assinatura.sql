-- Migração 123: Estrutura para Ofício Oficial e Assinatura Digital de Protocolos/Dossiês

-- Colunas para protocolo_dossies
ALTER TABLE protocolo_dossies ADD COLUMN IF NOT EXISTS destinatario_autoridade VARCHAR(255) NULL AFTER unidade_maritima_id;
ALTER TABLE protocolo_dossies ADD COLUMN IF NOT EXISTS numero_oficio VARCHAR(50) NULL AFTER numero;
ALTER TABLE protocolo_dossies ADD COLUMN IF NOT EXISTS normam_referencia VARCHAR(50) NULL DEFAULT 'NORMAM 202/DPC' AFTER assunto;
ALTER TABLE protocolo_dossies ADD COLUMN IF NOT EXISTS assinado TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE protocolo_dossies ADD COLUMN IF NOT EXISTS assinatura_em DATETIME NULL;
ALTER TABLE protocolo_dossies ADD COLUMN IF NOT EXISTS responsavel_assinatura_id INT NULL;
ALTER TABLE protocolo_dossies ADD COLUMN IF NOT EXISTS assinante_nome VARCHAR(200) NULL;
ALTER TABLE protocolo_dossies ADD COLUMN IF NOT EXISTS assinante_cargo VARCHAR(200) NULL;
ALTER TABLE protocolo_dossies ADD COLUMN IF NOT EXISTS assinante_registro VARCHAR(100) NULL;
ALTER TABLE protocolo_dossies ADD COLUMN IF NOT EXISTS assinatura_imagem LONGTEXT NULL;
ALTER TABLE protocolo_dossies ADD COLUMN IF NOT EXISTS assinatura_ip VARCHAR(45) NULL;

-- Colunas para protocolo_movimentacoes
ALTER TABLE protocolo_movimentacoes ADD COLUMN IF NOT EXISTS destinatario_autoridade VARCHAR(255) NULL AFTER destino_nome;
ALTER TABLE protocolo_movimentacoes ADD COLUMN IF NOT EXISTS numero_oficio VARCHAR(50) NULL AFTER sequencia;
ALTER TABLE protocolo_movimentacoes ADD COLUMN IF NOT EXISTS assinado TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE protocolo_movimentacoes ADD COLUMN IF NOT EXISTS assinatura_em DATETIME NULL;
ALTER TABLE protocolo_movimentacoes ADD COLUMN IF NOT EXISTS responsavel_assinatura_id INT NULL;
ALTER TABLE protocolo_movimentacoes ADD COLUMN IF NOT EXISTS assinante_nome VARCHAR(200) NULL;
ALTER TABLE protocolo_movimentacoes ADD COLUMN IF NOT EXISTS assinante_cargo VARCHAR(200) NULL;
ALTER TABLE protocolo_movimentacoes ADD COLUMN IF NOT EXISTS assinante_registro VARCHAR(100) NULL;
ALTER TABLE protocolo_movimentacoes ADD COLUMN IF NOT EXISTS assinatura_imagem LONGTEXT NULL;
ALTER TABLE protocolo_movimentacoes ADD COLUMN IF NOT EXISTS assinatura_ip VARCHAR(45) NULL;
