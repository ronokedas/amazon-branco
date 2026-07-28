-- Simplifica o registro no orgao e generaliza os anexos do dossie.

ALTER TABLE protocolo_comprovantes
  MODIFY COLUMN tipo enum(
    'PROTOCOLO_EXTERNO',
    'RECIBO',
    'COMPROVANTE_ENTREGA',
    'RASTREIO',
    'OUTRO',
    'DOCUMENTO'
  ) NOT NULL;

INSERT INTO protocolo_configuracoes (chave, valor, descricao)
SELECT
  'dias_sem_documento',
  COALESCE((SELECT valor FROM protocolo_configuracoes WHERE chave='dias_sem_comprovante'), '3'),
  'Dias apos uma saida para alertar falta de documento anexado'
ON DUPLICATE KEY UPDATE descricao=VALUES(descricao);

INSERT INTO protocolo_configuracoes (chave, valor, descricao)
SELECT
  'dias_sem_registro_orgao',
  COALESCE((SELECT valor FROM protocolo_configuracoes WHERE chave='dias_sem_protocolo_oficial'), '3'),
  'Dias apos envio ao orgao para alertar falta de registro do atendimento'
ON DUPLICATE KEY UPDATE descricao=VALUES(descricao);
