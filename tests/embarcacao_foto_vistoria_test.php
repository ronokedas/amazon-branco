<?php
/**
 * Teste unitário e funcional para upload da foto oficial da embarcação no módulo de vistorias
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/embarcacao_foto.php';

echo "=== TESTE: FOTO OFICIAL DA EMBARCAÇÃO NO MÓDULO DE VISTORIAS ===\n\n";

global $pdo;

// 1. Obter ou criar uma embarcação e um agendamento/vistoria de teste
$stmtEmb = $pdo->query("SELECT id, nome FROM embarcacoes WHERE ativo = 1 ORDER BY id DESC LIMIT 1");
$embarcacao = $stmtEmb->fetch(PDO::FETCH_ASSOC);
if (!$embarcacao) {
    die("[ERRO] Nenhuma embarcação encontrada para teste.\n");
}
$embarcacaoId = $embarcacao['id'];
echo "[OK] Embarcação alvo: {$embarcacao['nome']} ({$embarcacaoId})\n";

$stmtVistoria = $pdo->query("SELECT id FROM vistorias ORDER BY id DESC LIMIT 1");
$vistoriaId = $stmtVistoria->fetchColumn() ?: null;
echo "[OK] Vistoria alvo: " . ($vistoriaId ?: 'Nenhuma (opcional)') . "\n";

$stmtUser = $pdo->query("SELECT id FROM usuarios LIMIT 1");
$usuarioId = (string)($stmtUser->fetchColumn() ?: '00000000-0000-0000-0000-000000000001');

// 2. Criar imagem JPEG mínima válida em arquivo temporário com hash único
$tempFile = tempnam(sys_get_temp_dir(), 'test_barco_');
$im = imagecreatetruecolor(100, 80);
$bg = imagecolorallocate($im, 10, 80, 120);
imagefill($im, 0, 0, $bg);
imagestring($im, 2, 10, 10, 'BARCO_' . microtime(true), imagecolorallocate($im, 255, 255, 255));
imagejpeg($im, $tempFile, 85);
imagedestroy($im);

$fileSize = filesize($tempFile);
echo "[OK] Imagem de teste JPEG criada com sucesso ({$fileSize} bytes)\n";

$uploadData = [
    'name'     => 'foto_teste_barco.jpg',
    'type'     => 'image/jpeg',
    'tmp_name' => $tempFile,
    'error'    => UPLOAD_ERR_OK,
    'size'     => $fileSize,
];

try {
    // 3. Testar processamento de upload
    $binario = file_get_contents($tempFile);
    $mime = 'image/jpeg';
    $fotoId = gerarUUID();
    $chave = embarcacaoFotoGuardar($binario, $mime, $embarcacaoId, $fotoId);
    echo "[OK] embarcacaoFotoGuardar retornou chave: {$chave}\n";
    
    $hash = hash('sha256', $binario);
    $fotoUrl = APP_URL . 'embarcacoes/foto?id=' . rawurlencode($embarcacaoId) . '&v=' . substr($hash, 0, 12);
    
    $stmtUpd = $pdo->prepare("UPDATE embarcacoes
        SET foto_chave = :chave,
            foto_url = :url,
            foto_nome_original = 'foto_teste_barco.jpg',
            foto_mime_type = :mime,
            foto_tamanho_bytes = :tamanho,
            foto_sha256 = :hash,
            foto_atualizada_em = NOW(),
            foto_atualizada_por = :usuario
        WHERE id = :id");
    $stmtUpd->execute([
        ':chave'   => $chave,
        ':url'     => $fotoUrl,
        ':mime'    => $mime,
        ':tamanho' => $fileSize,
        ':hash'    => $hash,
        ':usuario' => $usuarioId,
        ':id'      => $embarcacaoId,
    ]);
    
    if ($vistoriaId) {
        $stmtCheckAnexo = $pdo->prepare("SELECT id FROM vistoria_anexos WHERE vistoria_id = :v AND sha256 = :h LIMIT 1");
        $stmtCheckAnexo->execute([':v' => $vistoriaId, ':h' => $hash]);
        $existente = $stmtCheckAnexo->fetchColumn();

        if ($existente) {
            $stmtAnexo = $pdo->prepare("UPDATE vistoria_anexos SET url_arquivo = :url, chave_arquivo = :chave WHERE id = :id");
            $stmtAnexo->execute([':url' => $fotoUrl, ':chave' => $chave, ':id' => $existente]);
        } else {
            $stmtAnexo = $pdo->prepare("INSERT INTO vistoria_anexos
                (id, vistoria_id, catalogo_id, url_arquivo, chave_arquivo, nome_original, mime_type, tamanho_bytes, sha256, capturado_em, criado_por)
                VALUES (:id, :vistoria_id, NULL, :url, :chave, :nome, :mime, :tamanho, :hash, NOW(), :criado_por)");
            $stmtAnexo->execute([
                ':id'          => $fotoId,
                ':vistoria_id' => $vistoriaId,
                ':url'         => $fotoUrl,
                ':chave'       => $chave,
                ':nome'        => 'FOTO_OFICIAL_foto_teste_barco.jpg',
                ':mime'        => $mime,
                ':tamanho'     => $fileSize,
                ':hash'        => $hash,
                ':criado_por'  => $usuarioId,
            ]);
        }
        echo "[OK] Anexo de vistoria associado com sucesso para a vistoria {$vistoriaId}\n";
    }

    // 4. Conferir integridade no banco de dados da embarcação
    $stmtCheck = $pdo->prepare("SELECT foto_chave, foto_url, foto_sha256, foto_atualizada_em FROM embarcacoes WHERE id = :id");
    $stmtCheck->execute([':id' => $embarcacaoId]);
    $dadosFoto = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (empty($dadosFoto['foto_chave']) || empty($dadosFoto['foto_url'])) {
        throw new Exception("Campos foto_chave ou foto_url não foram preenchidos no banco!");
    }
    echo "[OK] Campos no banco validados: Chave = {$dadosFoto['foto_chave']}, URL = {$dadosFoto['foto_url']}\n";

    // 5. Testar remoção da foto oficial
    $removido = embarcacaoFotoRemover($pdo, $embarcacaoId, $usuarioId);
    if (!$removido) {
        throw new Exception("embarcacaoFotoRemover retornou falso!");
    }

    $stmtCheckRem = $pdo->prepare("SELECT foto_chave, foto_url FROM embarcacoes WHERE id = :id");
    $stmtCheckRem->execute([':id' => $embarcacaoId]);
    $dadosRem = $stmtCheckRem->fetch(PDO::FETCH_ASSOC);

    if (!empty($dadosRem['foto_chave']) || !empty($dadosRem['foto_url'])) {
        throw new Exception("Foto não foi desvinculada após embarcacaoFotoRemover!");
    }
    // 6. Validar estrutura visual e comportamental no arquivo relatorio.php
    require_once __DIR__ . '/helpers_relatorio.php';
    $relatorioHtml = carregarRelatorioParaTeste();
    if (!$relatorioHtml) {
        throw new Exception("Não foi possível ler modules/vistorias/relatorio.php");
    }

    $elementosObrigatorios = [
        'id="secaoFotoOficialEmbarcacao"' => 'Card da Foto Oficial da Embarcação',
        'id="vesselPhotoFrame"'            => 'Moldura de visualização da foto',
        'id="vesselPhotoBadge"'            => 'Badge de status da foto',
        'id="imgFotoOficialEmbarcacao"'    => 'Tag de imagem da foto oficial',
        'id="inputCameraEmbarcacao"'       => 'Input de captura direta com a câmera',
        'capture="environment"'            => 'Atributo de câmera traseira para dispositivos móveis',
        'id="inputArquivoEmbarcacao"'      => 'Input de seleção da galeria/arquivo',
        'id="btnRemoverFotoOficial"'       => 'Botão de remoção da foto oficial',
        'class="checklist-toolbar"'        => 'Barra de produtividade do checklist',
        'expandirTodasCategorias'          => 'Função para expandir todas as categorias',
        'recolherTodasCategorias'          => 'Função para recolher todas as categorias',
        'filtrarChecklistStatus'           => 'Função de filtragem por status (Pills)',
        'id="pillPendentes"'               => 'Badge de contador de itens pendentes',
        'id="pillConformes"'               => 'Badge de contador de itens conformes',
        'id="pillNaoConformes"'            => 'Badge de contador de exigências',
        'uploadFotoEmbarcacaoAjax'         => 'Função AJAX para envio instantâneo da foto',
        'removerFotoEmbarcacaoAjax'        => 'Função AJAX para remoção da foto',
        'thumbFotoCabecalhoEmbarcacao'     => 'Miniatura da foto no cabeçalho do relatório',
    ];

    foreach ($elementosObrigatorios as $marcador => $rotulo) {
        if (!str_contains($relatorioHtml, $marcador)) {
            throw new Exception("Marcador obrigatório ausente no relatório: {$marcador} ({$rotulo})");
        }
    }
    echo "[OK] Validação de interface do vistoriador: todos os 18 elementos e funções JS confirmados!\n";

} catch (Throwable $e) {
    echo "\n[ERRO CAPTURADO]: " . $e->getMessage() . "\nLinha: " . $e->getLine() . "\n" . $e->getTraceAsString() . "\n";
    exit(1);
} finally {
    if (file_exists($tempFile)) {
        @unlink($tempFile);
    }
}

echo "\n[SUCESSO] Todos os testes da foto oficial da embarcação foram aprovados com êxito!\n";

