<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Acesso restrito.');
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/campo_storage.php';

try {
    campoStorageGarantirBucket();
    echo 'Bucket privado de evidencias preparado: ' . campoStorageBucket() . PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, 'ERRO ao preparar armazenamento de evidencias: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

// Manutenção de armazenamento: remover sessões antigas e arquivos temporários órfãos
function purgarArquivosAntigos(string $diretorio, int $diasExpiracao): int {
    if (!is_dir($diretorio)) {
        return 0;
    }
    $removidos = 0;
    $limite = time() - ($diasExpiracao * 86400);
    $itens = @scandir($diretorio);
    if ($itens === false) {
        return 0;
    }
    foreach ($itens as $item) {
        if ($item === '.' || $item === '..' || $item === '.gitkeep' || $item === '.gitignore') {
            continue;
        }
        $caminho = $diretorio . DIRECTORY_SEPARATOR . $item;
        if (is_file($caminho)) {
            $mtime = @filemtime($caminho);
            // Arquivos de sessão vazios (0 bytes gerados por testes/pings) com mais de 1 hora
            // ou qualquer sessão/temp com mais do limite de dias
            $isVazio = (filesize($caminho) === 0 && ($mtime < (time() - 3600)));
            if (($mtime !== false && $mtime < $limite) || $isVazio) {
                if (@unlink($caminho)) {
                    $removidos++;
                }
            }
        }
    }
    return $removidos;
}

$raiz = dirname(__DIR__);
$sessoesLimpas = purgarArquivosAntigos($raiz . '/storage/sessions', 14);
$tempsLimpas = purgarArquivosAntigos($raiz . '/temp_pdf', 2);
$tmpsLimpas = purgarArquivosAntigos($raiz . '/tmp/pdfs', 2);

echo "Manutenção de armazenamento concluída: {$sessoesLimpas} sessões antigas removidas, " . ($tempsLimpas + $tmpsLimpas) . " temporários removidos." . PHP_EOL;


