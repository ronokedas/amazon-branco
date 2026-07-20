<?php

require_once __DIR__ . '/aprovacao_documentos.php';
require_once __DIR__ . '/campo_storage.php';

function exportacaoTipos(): array {
    return [
        'VISTORIAS'=>'Relatórios de vistoria e fotos',
        'CSN'=>'Certificados CSN', 'CNBL'=>'Certificados CNBL', 'CNARQ'=>'Certificados CNARQ',
        'LP'=>'Licenças Provisórias', 'LC'=>'Licenças de Construção', 'CHT'=>'Certificados CHT',
        'PARECER_PLANOS'=>'Pareceres de análise de planos', 'PROPOSTAS'=>'Propostas e orçamentos',
    ];
}

function exportacaoSlug(string $valor, int $limite = 90): string {
    $valor = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $valor) ?: $valor;
    $valor = preg_replace('/[^A-Za-z0-9._-]+/', '-', $valor) ?: 'documento';
    return trim(substr(trim($valor, '-._'), 0, $limite), '-._') ?: 'documento';
}

function exportacaoDataHoraLocal(?string $valor, string $formato = 'd/m/Y H:i'): string {
    if (!$valor) return '—';
    try {
        return (new DateTimeImmutable($valor, new DateTimeZone('UTC')))
            ->setTimezone(new DateTimeZone('America/Sao_Paulo'))
            ->format($formato);
    } catch (Throwable $e) {
        error_log('Data inválida em exportação de documentos: ' . $e->getMessage());
        return '—';
    }
}

function exportacaoBaseSegura(string $relativo): string {
    $relativo = str_replace(['../','..\\'], '', ltrim($relativo, '/\\'));
    $absoluto = realpath(BASE_PATH . '/' . $relativo);
    $base = realpath(BASE_PATH);
    if (!$absoluto || !$base || !str_starts_with($absoluto, $base . DIRECTORY_SEPARATOR) || !is_file($absoluto)) {
        throw new RuntimeException('Arquivo documental não encontrado: ' . basename($relativo));
    }
    return $absoluto;
}

function exportacaoGerarPdfIsolado(string $tipo, string $documentoId, string $destino): void {
    $script = BASE_PATH . '/scripts/gerar_pdf_exportacao.php';
    $comando = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($script) . ' '
        . escapeshellarg($tipo) . ' ' . escapeshellarg($documentoId) . ' ' . escapeshellarg($destino);
    $processo = proc_open($comando, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, BASE_PATH);
    if (!is_resource($processo)) throw new RuntimeException('Não foi possível iniciar o gerador de PDF.');
    $saida = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $erro = stream_get_contents($pipes[2]); fclose($pipes[2]);
    $codigo = proc_close($processo);
    if ($codigo !== 0 || !is_file($destino) || filesize($destino) < 200) {
        throw new RuntimeException('Falha ao gerar PDF para exportação: ' . trim($erro ?: $saida));
    }
}

function exportacaoRegistro(): array {
    $cert = static function(string $tipo, string $tabela, string $numero, string $data, string $embarcacaoExpr = "COALESCE(e.nome,d.nome_embarcacao,'')"): array {
        return ['tipo'=>$tipo, 'pasta'=>'certificados/' . strtolower($tipo), 'sql'=>"SELECT d.id,d.{$numero} numero,d.status,d.ativo,d.{$data} data_doc,
            c.id cliente_id,c.nome cliente,COALESCE(e.id,'') embarcacao_id,{$embarcacaoExpr} embarcacao
            FROM {$tabela} d LEFT JOIN vistorias v ON v.id=d.vistoria_id
            LEFT JOIN clientes c ON c.id=v.pessoa_id LEFT JOIN embarcacoes e ON e.id=v.embarcacao_id"];
    };
    return [
        'VISTORIAS'=>['tipo'=>'RELATORIO','pasta'=>'vistorias','sql'=>"SELECT v.id,v.numero,v.status,v.data_vistoria data_doc,c.id cliente_id,c.nome cliente,e.id embarcacao_id,e.nome embarcacao
            FROM vistorias v LEFT JOIN clientes c ON c.id=v.pessoa_id LEFT JOIN embarcacoes e ON e.id=v.embarcacao_id"],
        'CSN'=>$cert('CSN','certificados_csn','numero','data_emissao'),
        'CNBL'=>$cert('CNBL','certificados_cnbl','numero','data_emissao'),
        'CNARQ'=>$cert('CNARQ','certificados_cnarq','numero','data_emissao'),
        'CHT'=>$cert('CHT','certificados_cht','numero_certificado','data_emissao',"COALESCE(e.nome,'')"),
        'LP'=>['tipo'=>'LP','pasta'=>'certificados/lp','sql'=>"SELECT d.id,d.numero_lp numero,d.status,d.ativo,d.data_emissao data_doc,NULL cliente_id,NULL cliente,e.id embarcacao_id,e.nome embarcacao
            FROM certificados_lp d LEFT JOIN embarcacoes e ON e.id=d.embarcacao_id"],
        'LC'=>['tipo'=>'LC','pasta'=>'certificados/lc','sql'=>"SELECT d.id,d.numero_lc numero,d.status,d.ativo,d.data_emissao data_doc,NULL cliente_id,NULL cliente,e.id embarcacao_id,e.nome embarcacao
            FROM certificados_lc d LEFT JOIN embarcacoes e ON e.id=d.embarcacao_id"],
        'PARECER_PLANOS'=>['tipo'=>'PARECER_PLANOS','pasta'=>'pareceres-planos','sql'=>"SELECT p.id,CONCAT('Parecer-',ap.numero,'-v',p.versao) numero,p.status,p.criado_em data_doc,c.id cliente_id,c.nome cliente,e.id embarcacao_id,e.nome embarcacao
            FROM analise_planos_pareceres p INNER JOIN analises_planos ap ON ap.id=p.analise_id
            LEFT JOIN clientes c ON c.id=ap.solicitante_id LEFT JOIN embarcacoes e ON e.id=ap.embarcacao_id"],
        'PROPOSTAS'=>['tipo'=>'PROPOSTA','pasta'=>'propostas','sql'=>"SELECT p.id,p.numero,p.status,p.data_emissao data_doc,c.id cliente_id,c.nome cliente,
            MIN(e.id) embarcacao_id,GROUP_CONCAT(DISTINCT e.nome ORDER BY e.nome SEPARATOR ', ') embarcacao
            FROM propostas p INNER JOIN clientes c ON c.id=p.cliente_id
            LEFT JOIN propostas_embarcacoes pe ON pe.proposta_id=p.id LEFT JOIN embarcacoes e ON e.id=pe.embarcacao_id GROUP BY p.id"],
    ];
}

function exportacaoListarDocumentos(PDO $pdo, string $categoria, array $filtros): array {
    $registro = exportacaoRegistro()[$categoria] ?? null;
    if (!$registro) return [];
    $sql = 'SELECT * FROM (' . $registro['sql'] . ') documentos WHERE 1=1';
    $params = [];
    if (!empty($filtros['data_inicio'])) { $sql .= ' AND DATE(data_doc)>=:inicio'; $params[':inicio']=$filtros['data_inicio']; }
    if (!empty($filtros['data_fim'])) { $sql .= ' AND DATE(data_doc)<=:fim'; $params[':fim']=$filtros['data_fim']; }
    if (!empty($filtros['cliente_id'])) { $sql .= ' AND cliente_id=:cliente'; $params[':cliente']=$filtros['cliente_id']; }
    if (!empty($filtros['embarcacao_id'])) { $sql .= ' AND embarcacao_id=:embarcacao'; $params[':embarcacao']=$filtros['embarcacao_id']; }
    $sql .= ' ORDER BY data_doc,id';
    $stmt = $pdo->prepare($sql); $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function exportacaoGerarAtual(PDO $pdo, string $tipo, array $documento): array {
    $status = (string)($documento['status'] ?? 'ATUAL');
    $stmt = $pdo->prepare("SELECT * FROM documento_artefatos WHERE documento_tipo=:tipo AND documento_id=:id AND status_documento=:status ORDER BY versao DESC LIMIT 1");
    $stmt->execute([':tipo'=>$tipo, ':id'=>$documento['id'], ':status'=>$status]);
    $existente = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($existente && is_file(BASE_PATH . '/' . ltrim($existente['caminho_arquivo'],'/'))) return $existente;

    $versaoStmt = $pdo->prepare("SELECT COALESCE(MAX(versao),0)+1 FROM documento_artefatos WHERE documento_tipo=:tipo AND documento_id=:id");
    $versaoStmt->execute([':tipo'=>$tipo, ':id'=>$documento['id']]);
    $versao = (int)$versaoStmt->fetchColumn();
    $relDir = 'storage/private/documento_artefatos/' . date('Y') . '/' . strtolower($tipo) . '/' . exportacaoSlug($documento['id']) . '/';
    $absDir = BASE_PATH . '/' . $relDir;
    if (!is_dir($absDir) && !mkdir($absDir,0750,true) && !is_dir($absDir)) throw new RuntimeException('Falha ao preparar arquivo documental.');
    $nome = 'v' . str_pad((string)$versao,2,'0',STR_PAD_LEFT) . '_' . exportacaoSlug($status) . '.pdf';
    $destino = $absDir . $nome;
    exportacaoGerarPdfIsolado($tipo,$documento['id'],$destino);
    $id = gerarUUID();
    $hash = hash_file('sha256',$destino);
    $pdo->prepare("INSERT INTO documento_artefatos (id,documento_tipo,documento_id,versao,status_documento,caminho_arquivo,nome_arquivo,tamanho_bytes,sha256)
        VALUES (:id,:tipo,:documento,:versao,:status,:caminho,:nome,:tamanho,:hash)")
        ->execute([':id'=>$id,':tipo'=>$tipo,':documento'=>$documento['id'],':versao'=>$versao,':status'=>$status,
            ':caminho'=>$relDir.$nome,':nome'=>$nome,':tamanho'=>filesize($destino),':hash'=>$hash]);
    return ['id'=>$id,'versao'=>$versao,'status_documento'=>$status,'caminho_arquivo'=>$relDir.$nome,'nome_arquivo'=>$nome,
        'tamanho_bytes'=>filesize($destino),'sha256'=>$hash];
}

function exportacaoAdicionar(ZipArchive $zip, string $arquivo, string $caminhoZip, array &$manifesto, array $meta): void {
    if (!$zip->addFile($arquivo,$caminhoZip)) throw new RuntimeException('Falha ao adicionar arquivo ao ZIP.');
    $manifesto[] = $meta + ['caminho'=>$caminhoZip,'tamanho'=>(int)filesize($arquivo),'sha256'=>hash_file('sha256',$arquivo)];
}

function exportacaoProcessar(PDO $pdo, array $job): void {
    $categorias = json_decode((string)$job['categorias_json'],true) ?: [];
    $filtros = json_decode((string)($job['filtros_json'] ?? '{}'),true) ?: [];
    $raiz = 'exportacao-documentos-' . date('Y-m-d') . '/';
    $dir = BASE_PATH . '/storage/private/exportacoes';
    if (!is_dir($dir) && !mkdir($dir,0750,true) && !is_dir($dir)) throw new RuntimeException('Falha ao preparar pasta de exportações.');
    $nomeZip = 'documentos-' . date('Y-m-d_H-i-s') . '-' . substr($job['id'],0,8) . '.zip';
    $caminhoZip = $dir . '/' . $nomeZip;
    $zip = new ZipArchive();
    if ($zip->open($caminhoZip, ZipArchive::CREATE|ZipArchive::OVERWRITE) !== true) throw new RuntimeException('Não foi possível criar o ZIP.');
    $manifesto = [];
    $temporarios = [];
    try {
        foreach ($categorias as $categoria) {
            $provider = exportacaoRegistro()[$categoria] ?? null;
            if (!$provider) continue;
            foreach (exportacaoListarDocumentos($pdo,$categoria,$filtros) as $doc) {
                $numero = exportacaoSlug((string)($doc['numero'] ?: $doc['id']));
                $embarcacao = exportacaoSlug((string)($doc['embarcacao'] ?? 'sem-embarcacao'));
                $baseDoc = $categoria === 'VISTORIAS' ? $raiz . 'vistorias/' . $numero . '_' . $embarcacao . '/' : $raiz . $provider['pasta'] . '/' . $numero . '/';
                $artefatos = $pdo->prepare("SELECT * FROM documento_artefatos WHERE documento_tipo=:tipo AND documento_id=:id ORDER BY versao");
                $artefatos->execute([':tipo'=>$provider['tipo'],':id'=>$doc['id']]);
                $lista = $artefatos->fetchAll(PDO::FETCH_ASSOC);
                if (!array_key_exists('ativo', $doc) || (int)$doc['ativo'] === 1) {
                    $atual = exportacaoGerarAtual($pdo,$provider['tipo'],$doc);
                    if (!array_filter($lista,fn($item)=>$item['id']===$atual['id'])) $lista[]=$atual;
                }
                foreach ($lista as $artefato) {
                    $arquivo = exportacaoBaseSegura($artefato['caminho_arquivo']);
                    $nome = 'v' . str_pad((string)$artefato['versao'],2,'0',STR_PAD_LEFT) . '_' . exportacaoSlug((string)$artefato['status_documento']) . '.pdf';
                    exportacaoAdicionar($zip,$arquivo,$baseDoc . 'relatorios/' . $nome,$manifesto,[
                        'categoria'=>$categoria,'documento'=>$doc['numero'],'versao'=>(int)$artefato['versao'],'situacao'=>$artefato['status_documento'],
                        'cliente'=>$doc['cliente'],'embarcacao'=>$doc['embarcacao'],
                    ]);
                }
                if ($provider['tipo'] !== 'PROPOSTA') {
                    $aprovacoes = $pdo->prepare("SELECT versao,status,caminho_pdf_original,caminho_pdf_final FROM documento_aprovacoes
                        WHERE documento_tipo=:tipo AND documento_id=:id AND status IN ('APROVADO','CANCELADO') ORDER BY versao");
                    $aprovacoes->execute([':tipo'=>$provider['tipo'],':id'=>$doc['id']]);
                    foreach ($aprovacoes->fetchAll(PDO::FETCH_ASSOC) as $aprovacao) {
                        foreach (['caminho_pdf_original'=>'original','caminho_pdf_final'=>'aprovado'] as $campo=>$variante) {
                            if (empty($aprovacao[$campo])) continue;
                            $arquivo=exportacaoBaseSegura($aprovacao[$campo]);
                            $nome='v'.str_pad((string)$aprovacao['versao'],2,'0',STR_PAD_LEFT).'_'.$variante.'.pdf';
                            exportacaoAdicionar($zip,$arquivo,$baseDoc.'relatorios/'.$nome,$manifesto,['categoria'=>$categoria,'documento'=>$doc['numero'],'versao'=>(int)$aprovacao['versao'],'situacao'=>strtoupper($variante),'cliente'=>$doc['cliente'],'embarcacao'=>$doc['embarcacao']]);
                        }
                    }
                }
                if ($categoria === 'VISTORIAS') {
                    $fotos = $pdo->prepare("SELECT va.*, ec.codigo_interno, ec.item_normam, ec.descricao AS item_descricao
                        FROM vistoria_anexos va
                        LEFT JOIN exigencias_catalogo ec ON ec.id=va.catalogo_id
                        WHERE va.vistoria_id=:id AND va.excluido_em IS NULL
                        ORDER BY ec.codigo_interno, va.capturado_em, va.criado_em");
                    $fotos->execute([':id'=>$doc['id']]);
                    $pastasPorItem = [];
                    $quantidadePorItem = [];
                    foreach ($fotos->fetchAll(PDO::FETCH_ASSOC) as $foto) {
                        $ext = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$foto['mime_type']] ?? 'bin';
                        $item = exportacaoSlug((string)($foto['codigo_interno'] ?: $foto['item_normam'] ?: 'evidencia-geral'), 60);
                        $chaveItem = (string)($foto['catalogo_id'] ?: 'geral');
                        if (!isset($pastasPorItem[$chaveItem])) {
                            $pastasPorItem[$chaveItem] = sprintf('%03d_', count($pastasPorItem) + 1) . $item . '/';
                        }
                        $quantidadePorItem[$chaveItem] = ($quantidadePorItem[$chaveItem] ?? 0) + 1;
                        $pastaFoto = $pastasPorItem[$chaveItem];
                        $nomeOriginal = pathinfo((string)($foto['nome_original'] ?: 'foto'), PATHINFO_FILENAME);
                        $nomeFoto = sprintf('foto-%02d_', $quantidadePorItem[$chaveItem])
                            . date('Y-m-d_H-i-s',strtotime($foto['capturado_em'] ?: $foto['criado_em'])) . '_'
                            . exportacaoSlug($nomeOriginal, 45) . '_' . substr((string)$foto['sha256'],0,8) . '.' . $ext;
                        $tmp=tempnam(sys_get_temp_dir(),'exp_foto_');
                        if ($tmp === false) throw new RuntimeException('Falha ao preparar arquivo temporario da evidencia.');
                        $temporarios[]=$tmp;
                        campoStorageBaixarPara((string)$foto['chave_arquivo'], $tmp);
                        $arquivo=$tmp;
                        exportacaoAdicionar($zip,$arquivo,$baseDoc.'fotos/'.$pastaFoto.$nomeFoto,$manifesto,[
                            'categoria'=>'FOTO_VISTORIA','documento'=>$doc['numero'],'versao'=>1,'situacao'=>$doc['status'],
                            'cliente'=>$doc['cliente'],'embarcacao'=>$doc['embarcacao'],'item_normam'=>$foto['item_normam'] ?? '',
                            'descricao_item'=>$foto['item_descricao'] ?? '',
                        ]);
                    }
                }
                $zip->addFromString($baseDoc.'metadados.json',json_encode($doc,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
            }
        }
        $json=json_encode($manifesto,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $zip->addFromString($raiz.'manifesto.json',$json);
        $colunasManifesto=['categoria','documento','versao','situacao','cliente','embarcacao','item_normam','descricao_item','caminho','tamanho','sha256'];
        $csv=fopen('php://temp','w+'); fputcsv($csv,$colunasManifesto);
        foreach ($manifesto as $linha) fputcsv($csv,array_map(fn($k)=>$linha[$k]??'',$colunasManifesto));
        rewind($csv); $zip->addFromString($raiz.'manifesto.csv',stream_get_contents($csv)); fclose($csv);
    } finally {
        $zip->close();
        foreach ($temporarios as $temporario) @unlink($temporario);
    }
    $pdo->prepare("UPDATE exportacoes_documentos SET status='CONCLUIDA',caminho_arquivo=:caminho,nome_arquivo=:nome,
        tamanho_bytes=:tamanho,quantidade_arquivos=:quantidade,sha256=:hash,concluido_em=NOW(),expira_em=DATE_ADD(NOW(),INTERVAL 24 HOUR) WHERE id=:id")
        ->execute([':caminho'=>'storage/private/exportacoes/'.$nomeZip,':nome'=>$nomeZip,':tamanho'=>filesize($caminhoZip),':quantidade'=>count($manifesto),':hash'=>hash_file('sha256',$caminhoZip),':id'=>$job['id']]);
}
