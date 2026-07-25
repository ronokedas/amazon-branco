-- Reafirma os rótulos UTF-8 do catálogo após importações por clientes de terminal legados.
UPDATE protocolo_catalogo_documentos SET nome='Anotação de Responsabilidade Técnica (ART)' WHERE codigo='ART';
UPDATE protocolo_catalogo_documentos SET nome='Plano de segurança' WHERE codigo='PLANO_SEGURANCA';
UPDATE protocolo_catalogo_documentos SET nome='Cálculos e folheto de estabilidade' WHERE codigo='CALCULOS_ESTABILIDADE';
UPDATE protocolo_catalogo_documentos SET nome='Relatório técnico de vistoria' WHERE codigo='RELATORIO_VISTORIA';
UPDATE protocolo_catalogo_documentos SET nome='Certificado ou licença existente' WHERE codigo='CERTIFICADO_EXISTENTE';
UPDATE protocolo_catalogo_documentos SET nome='TIE/TIEM ou documento de inscrição' WHERE codigo='TIE_TIEM';
UPDATE protocolo_catalogo_documentos SET nome='Documento de propriedade da embarcação' WHERE codigo='DOCUMENTO_PROPRIEDADE';
UPDATE protocolo_catalogo_documentos SET nome='Documento de identificação do interessado/representante' WHERE codigo='DOCUMENTO_PESSOAL';
UPDATE protocolo_catalogo_documentos SET nome='Procuração do representante' WHERE codigo='PROCURACAO';
UPDATE protocolo_unidades_maritimas SET nome='Capitania dos Portos da Amazônia Oriental',cidade='Belém' WHERE codigo='CPAOR';
