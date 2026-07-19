-- Perfil ANALISTA para revisao tecnica de relatorios de vistoria.

ALTER TABLE usuarios
    MODIFY cargo ENUM('ADMIN','VENDEDOR','VISTORIADOR','ANALISTA') NOT NULL DEFAULT 'VISTORIADOR';

ALTER TABLE usuario_perfis
    MODIFY perfil ENUM('ADMIN','VENDEDOR','VISTORIADOR','ANALISTA') NOT NULL;

INSERT IGNORE INTO usuario_perfis (usuario_id, perfil)
SELECT id, cargo FROM usuarios WHERE cargo IN ('ADMIN','VENDEDOR','VISTORIADOR','ANALISTA');
