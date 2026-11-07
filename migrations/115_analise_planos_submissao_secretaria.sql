-- Migração 115: Suporte a envio de pranchas e documentos do armador pela Secretaria
ALTER TABLE analise_planos_submissoes 
  MODIFY COLUMN origem ENUM('ANALISTA','PORTAL','SECRETARIA') NOT NULL DEFAULT 'ANALISTA';

