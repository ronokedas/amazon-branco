-- Migração 094: Gerenciador de Exigências NORMAM-202 e Evidências Fotográficas Obrigatórias

ALTER TABLE `exigencias_catalogo` ADD COLUMN IF NOT EXISTS `obrigatoria` TINYINT(1) NOT NULL DEFAULT 0 AFTER `ativo`;
ALTER TABLE `exigencias_catalogo` ADD COLUMN IF NOT EXISTS `exige_foto` TINYINT(1) NOT NULL DEFAULT 0 AFTER `obrigatoria`;
ALTER TABLE `exigencias_catalogo` ADD COLUMN IF NOT EXISTS `ordem_exibicao` INT NOT NULL DEFAULT 0 AFTER `exige_foto`;

-- Configurar exigências mestras
UPDATE `exigencias_catalogo` 
SET `obrigatoria` = 1, `exige_foto` = 1 
WHERE `descricao` LIKE '%coletes salva vidas%'
   OR `descricao` LIKE '%coletes salva-vidas%'
   OR `descricao` LIKE '%boias salva vidas%'
   OR `descricao` LIKE '%boias salva-vidas%'
   OR `descricao` LIKE '%bombas de inc%'
   OR `descricao` LIKE '%bomba de inc%'
   OR `descricao` LIKE '%extintor%'
   OR `descricao` LIKE '%luzes de navega%'
   OR `descricao` LIKE '%escala de calado%'
   OR `descricao` LIKE '%governo de emerg%'
   OR `descricao` LIKE '%leme%'
   OR `descricao` LIKE '%plimsoll%'
   OR `descricao` LIKE '%borda livre%'
   OR `descricao` LIKE '%esgoto%'
   OR `descricao` LIKE '%bandejas coletoras%'
   OR `descricao` LIKE '%lota%passageiros%';

-- Novas exigências essenciais
INSERT INTO `exigencias_catalogo` 
(`id`, `codigo_interno`, `categoria_id`, `descricao`, `item_normam`, `bloco_vistoria`, `tipo_vistoria`, `prazo_padrao_dias`, `ativo`, `obrigatoria`, `exige_foto`, `ordem_exibicao`)
VALUES
('e202-0001-4921-b12a-000000000001', 'EX-553', '71c05e83-0d67-4137-b2b7-478c4241a057', 'Marcação física da Linha de Borda Livre / Disco de Plimsoll soldada ou marcada em baixo relevo a meia-nau em ambos os bordos, com pintura contrastante de acordo com o Certificado Nacional de Borda Livre (CNBL)', 'NORMAM-202/DPC, Cap. 02, Seção II', 'borda_livre', 'borda_livre', 15, 1, 1, 1, 1),
('e202-0002-4921-b12a-000000000002', 'EX-554', '65bf89f0-f44d-4746-89f7-f530c9aa990d', 'Válvula de descarga direta de água de porão com lacre numerado ou dispositivo de interrupção bloqueado para impedir descarte involuntário de resíduos oleosos nos rios (Prevenção da Poluição Hídrica)', 'NORMAM-202/DPC, Cap. 08, Item 8.3', 'flutuando', 'flutuando', 7, 1, 1, 1, 2),
('e202-0003-4921-b12a-000000000003', 'EX-555', 'e70f7906-4e9d-4367-b10a-2ad2a007817a', 'Painel de controle e alarme sonoro/visual de falha ou queima de lâmpadas das luzes de navegação no comando/passadiço', 'NORMAM-202/DPC, Cap. 04, Seção II', 'flutuando', 'flutuando', 15, 1, 1, 1, 3),
('e202-0004-4921-b12a-000000000004', 'EX-556', 'b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d', 'Placa informativa de capacidade máxima de passageiros (N1 e N2), tripulantes e limites de carga afixada em local visível ao público e passageiros', 'NORMAM-202/DPC, Cap. 07, Seção I', 'flutuando', 'flutuando', 10, 1, 1, 1, 4)
ON DUPLICATE KEY UPDATE 
    descricao = VALUES(descricao),
    item_normam = VALUES(item_normam),
    obrigatoria = VALUES(obrigatoria),
    exige_foto = VALUES(exige_foto);
