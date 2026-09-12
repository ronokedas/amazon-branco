<?php
/**
 * MÓDULO: PORTAL DO CLIENTE - OUVIDORIA & RECLAMAÇÕES (ISO 9001:2015)
 * Arquivo: modules/portal/ouvidoria_actions.php
 * Processamento de manifestações e abertura automática de RNC no SGQ
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/cliente_portal.php';
require_once __DIR__ . '/../../includes/sgq.php';

requireClienteSenhaDefinitiva();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirecionar(APP_URL . 'portal/ouvidoria');
}

if (!isset($_POST['csrf_token']) || !verificarCSRF($_POST['csrf_token'])) {
    setMensagem('error', 'Token de segurança expirado ou inválido. Tente novamente.');
    redirecionar(APP_URL . 'portal/ouvidoria');
}

$action = trim($_POST['action'] ?? '');
$clienteId = clientePortalId();
$clienteNome = clientePortalNome();

if ($action === 'registrar_manifestacao') {
    try {
        $tipoManifestacao = trim(sanitizar($_POST['tipo_manifestacao'] ?? 'RECLAMACAO'));
        $embarcacaoId = trim(sanitizar($_POST['embarcacao_id'] ?? ''));
        $classificacaoFalha = trim(sanitizar($_POST['classificacao_falha'] ?? 'Geral'));
        $severidade = trim(sanitizar($_POST['severidade'] ?? 'MEDIA'));
        $titulo = trim(sanitizar($_POST['titulo'] ?? ''));
        $descricao = trim(sanitizar($_POST['descricao'] ?? ''));

        if ($titulo === '' || $descricao === '') {
            throw new Exception('Por favor, informe o título e a descrição detalhada da sua manifestação.');
        }

        // Validar severidade
        if (!in_array($severidade, ['BAIXA', 'MEDIA', 'CRITICA_IMPEDITIVA'], true)) {
            $severidade = 'MEDIA';
        }

        // Se informou embarcação, validar que pertence ao cliente
        if ($embarcacaoId !== '') {
            $idsValidos = clientePortalEmbarcacaoIds($pdo, $clienteId);
            if (!in_array($embarcacaoId, $idsValidos, true)) {
                $embarcacaoId = null;
            }
        } else {
            $embarcacaoId = null;
        }

        // Gerar número oficial de RNC (ISO 10.2)
        $numeroRnc = sgqGerarNumeroRNC($pdo);
        $rncId = gerarUUID();

        // Montar prefixo descritivo de acordo com o tipo
        $rotulosTipo = [
            'RECLAMACAO' => 'Reclamação Formal (ISO 10.2)',
            'SUGESTAO'   => 'Sugestão de Melhoria (ISO 10.3)',
            'DUVIDA'     => 'Esclarecimento Técnico',
            'ELOGIO'     => 'Elogio / Reconhecimento',
        ];
        $tipoLabel = $rotulosTipo[$tipoManifestacao] ?? 'Reclamação';

        $tituloCompleto = "[{$tipoLabel}] " . $titulo;
        $descricaoCompleta = "MANIFESTAÇÃO REGISTRADA PELO CLIENTE NO PORTAL:\n"
            . "Tipo: {$tipoLabel}\n"
            . "Cliente: {$clienteNome}\n"
            . "Categoria: {$classificacaoFalha}\n"
            . "Data de Registro: " . date('d/m/Y H:i') . "\n\n"
            . "RELATO DO CLIENTE:\n"
            . $descricao;

        // Inserir RNC oficial no SGQ
        $stmtInsert = $pdo->prepare("
            INSERT INTO sgq_nao_conformidades (
                id,
                numero_rnc,
                origem,
                embarcacao_id,
                cliente_id,
                classificacao_falha,
                severidade,
                titulo,
                descricao_detalhada,
                status_ciclo_vida,
                responsavel_abertura_nome,
                data_identificacao
            ) VALUES (
                :id,
                :numero_rnc,
                'RECLAMACAO_CLIENTE',
                :embarcacao_id,
                :cliente_id,
                :classificacao,
                :severidade,
                :titulo,
                :descricao,
                'ABERTA',
                :resp_nome,
                NOW()
            )
        ");

        $stmtInsert->execute([
            ':id'             => $rncId,
            ':numero_rnc'     => $numeroRnc,
            ':embarcacao_id'  => $embarcacaoId,
            ':cliente_id'     => $clienteId,
            ':classificacao'  => $classificacaoFalha,
            ':severidade'     => $severidade,
            ':titulo'         => mb_substr($tituloCompleto, 0, 255),
            ':descricao'      => $descricaoCompleta,
            ':resp_nome'      => 'Portal do Cliente - ' . mb_substr($clienteNome, 0, 100),
        ]);

        // Registrar auditoria se existir função
        if (function_exists('registrarAuditoria')) {
            registrarAuditoria($pdo, 'ouvidoria_registro', "Manifestação {$numeroRnc} registrada pelo cliente {$clienteNome}", $rncId);
        }

        setMensagem(
            'success',
            "Sua manifestação foi registrada com sucesso! Protocolo oficial: {$numeroRnc}. Nossa equipe técnica e o SGQ já receberam sua solicitação."
        );
        redirecionar(APP_URL . 'portal/ouvidoria');
    } catch (Exception $e) {
        error_log('[PORTAL OUVIDORIA ERRO] ' . $e->getMessage());
        setMensagem('error', 'Não foi possível registrar a manifestação: ' . $e->getMessage());
        redirecionar(APP_URL . 'portal/ouvidoria');
    }
}

redirecionar(APP_URL . 'portal/ouvidoria');
