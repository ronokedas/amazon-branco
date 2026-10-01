-- Migração 124: Adiciona cargo SECRETARIA aos ENUMs de usuários e perfis
-- Sistema ERP Amazon Naval

-- 1. Modificar cargo na tabela usuarios
ALTER TABLE usuarios 
  MODIFY COLUMN cargo ENUM('ADMIN','VENDEDOR','VISTORIADOR','ANALISTA','SECRETARIA') NOT NULL DEFAULT 'VISTORIADOR';

-- 2. Modificar perfil na tabela usuario_perfis
ALTER TABLE usuario_perfis 
  MODIFY COLUMN perfil ENUM('ADMIN','VENDEDOR','VISTORIADOR','ANALISTA','SECRETARIA') NOT NULL;

-- 3. Modificar regras de comunicação caso existam
ALTER TABLE feedback_regras_comunicacao 
  MODIFY COLUMN cargo_origem ENUM('ADMIN','VENDEDOR','VISTORIADOR','ANALISTA','SECRETARIA') NOT NULL;

ALTER TABLE feedback_regras_comunicacao 
  MODIFY COLUMN cargo_destino ENUM('ADMIN','VENDEDOR','VISTORIADOR','ANALISTA','SECRETARIA') NOT NULL;
