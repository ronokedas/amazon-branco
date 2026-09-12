-- Migration 103: Sincronização Segura de Cadastros e Manifestações (ISO 9001:2015 & SGQ)
-- Todos os comandos usam INSERT IGNORE para JAMAIS sobrescrever dados existentes na VPS

-- 1. Embarcação de teste vinculada se não existir
INSERT IGNORE INTO embarcacoes (id, nome, registro, ativo, criado_em, atualizado_em)
VALUES ('317ba743-7aa6-4d66-a845-2d4670f126f0', 'barcoteste14', 'PA-2026-TESTE', 1, '2026-09-10 20:19:42', '2026-09-10 20:19:42');

-- 2. Cliente proprietário
INSERT IGNORE INTO clientes (id, nome, tipo_pessoa, cpf_cnpj, perfil, telefone, email, status, criado_em, atualizado_em)
VALUES ('1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed', 'Rosano Souza', 'PF', '38303451863', 'proprietario', '(91) 84235-3456', 'anykedas@gmail.com', 'ATIVO', '2026-09-10 20:19:42', '2026-09-10 20:19:42');

-- 3. Vínculo do cliente com a embarcação
INSERT IGNORE INTO clientes_embarcacoes (id, cliente_id, embarcacao_id, status, vinculado_em, vinculo_ativo_chave, criado_em)
VALUES ('f4f6e2f8-ad54-11f1-8a7c-be2fb1f77be2', '1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed', '317ba743-7aa6-4d66-a845-2d4670f126f0', 'ATIVO', '2026-09-10 20:19:42', '1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed:317ba743-7aa6-4d66-a845-2d4670f126f0', '2026-09-10 20:19:42');

-- 4. Acesso ao Portal do Cliente (Senha padrão: cliente123)
INSERT INTO cliente_portal_acessos (cliente_id, login, senha_hash, ativo, forcar_troca_senha, criado_em, atualizado_em)
VALUES ('1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed', 'anykedas@gmail.com', '$2y$10$yeiF7SXQA4XDKLpKDtoY6udowXxgf3qOQ/uV3ee91d6NDDmjdOj.a', 1, 0, '2026-09-12 03:30:55', '2026-09-12 05:11:40')
ON DUPLICATE KEY UPDATE ativo = 1, senha_hash = VALUES(senha_hash), forcar_troca_senha = 0;

-- 5. Reclamações registradas na Ouvidoria / SGQ (ISO 10.2)
INSERT IGNORE INTO sgq_nao_conformidades (id, numero_rnc, origem, embarcacao_id, cliente_id, classificacao_falha, severidade, titulo, descricao_detalhada, analise_causa_raiz, status_ciclo_vida, responsavel_abertura_nome, data_identificacao, criado_em, atualizado_em)
VALUES 
('0f6e952c-5b5f-4e0d-83e3-219ab26eba2f', 'RNC-2026-0003', 'RECLAMACAO_CLIENTE', '317ba743-7aa6-4d66-a845-2d4670f126f0', '1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed', 'Atraso de Prazo / Vistoria', 'CRITICA_IMPEDITIVA', '[Reclamação Formal (ISO 10.2)] Solicitação de Documentação e Prazo', 'MANIFESTAÇÃO REGISTRADA PELO CLIENTE NO PORTAL:\nTipo: Reclamação Formal (ISO 10.2)\nCliente: Rosano Souza\nCategoria: Atraso de Prazo / Vistoria\nData de Registro: 12/09/2026\n\nRELATO DO CLIENTE:\nCertificado com prazo de vistoria pendente de emissão.', 'Identificado desvio de fluxo de aprovação e corrigido processo operacional com o corpo técnico.', 'EM_EXECUCAO', 'Portal do Cliente - Rosano Souza', '2026-09-12', '2026-09-12 05:42:48', '2026-09-12 06:24:10'),
('8bd56fbf-5d15-4dc6-b681-89e48d8da542', 'RNC-2026-0002', 'RECLAMACAO_CLIENTE', '317ba743-7aa6-4d66-a845-2d4670f126f0', '1f0b7a8e-b521-4e80-afb1-4673f8e1c9ed', 'Atraso de Prazo / Vistoria', 'MEDIA', '[Reclamação Formal] Teste de Envio pelo Portal', 'Relato de teste de envio de manifestação no portal pelo cliente.', 'Identificada falha operacional e retificado procedimento conforme norma.', 'EM_EXECUCAO', 'Portal do Cliente - Rosano Souza', '2026-09-12', '2026-09-12 05:17:45', '2026-09-12 06:17:02');

-- 6. Plano de Ação 5W2H para a Reclamação
INSERT IGNORE INTO sgq_planos_acao (id, nao_conformidade_id, o_que_fazer_what, por_que_fazer_why, quem_fara_who, quando_fara_when, como_fazer_how, quanto_custa_how_much, status_acao, criado_em, atualizado_em)
VALUES ('d52dd331-de91-4b22-a3a1-c77242ccd344', '0f6e952c-5b5f-4e0d-83e3-219ab26eba2f', 'Auditar emissão de certificado e alinhar com vistoriador de campo', 'Atender prazo normativo acordado com cliente', 'Equipe Técnica Amazon Naval', '2026-09-27', 'Priorização imediata no fluxo de aprovação de laudos e emissão do CSN.', 0.00, 'CONCLUIDA', '2026-09-12 06:12:07', '2026-09-12 06:24:37');
