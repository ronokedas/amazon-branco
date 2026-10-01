<?php

function protocoloExigirAcesso(): void
{
    exigirAcesso('protocolos_documentais');
}

function protocoloUsuarioPodeAcessar(PDO $pdo, array $dossie): bool
{
    if (getCargo() === 'ADMIN') return true;
    if (!podeAcessar('protocolos_documentais')) return false;
    $usuario = (string)($_SESSION['usuario_id'] ?? '');
    if ($usuario === '') return false;
    if (($dossie['criado_por'] ?? '') === $usuario) return true;
    if (in_array(getCargo(), ['DIRETOR', 'OPERACIONAL'], true)) return true;
    if (getCargo() === 'ANALISTA' && podeAcessar('analises_planos')) return true;
    if (!empty($dossie['proposta_id'])) {
        $q=$pdo->prepare('SELECT 1 FROM propostas WHERE id=:id AND criado_por=:usuario');
        $q->execute([':id'=>$dossie['proposta_id'],':usuario'=>$usuario]);
        if ($q->fetchColumn()) return true;
    }
    if (!empty($dossie['analise_id'])) {
        $q=$pdo->prepare('SELECT 1 FROM analises_planos WHERE id=:id AND (analista_id=:usuario OR analista_id IS NULL)');
        $q->execute([':id'=>$dossie['analise_id'],':usuario'=>$usuario]);
        if ($q->fetchColumn()) return true;
    }
    if (!empty($dossie['vistoria_id'])) {
        $q=$pdo->prepare('SELECT 1 FROM vistorias v INNER JOIN agendamentos a ON a.id=v.agendamento_id WHERE v.id=:id AND a.vistoriador_id=:usuario');
        $q->execute([':id'=>$dossie['vistoria_id'],':usuario'=>$usuario]);
        if ($q->fetchColumn()) return true;
    }
    return false;
}

function protocoloCarregar(PDO $pdo, string $id, bool $lock=false): array
{
    $q=$pdo->prepare("SELECT d.*,e.nome embarcacao_nome,c.nome cliente_nome,u.nome criador_nome,
        um.nome unidade_nome,um.tipo unidade_tipo,um.url_consulta
        FROM protocolo_dossies d INNER JOIN embarcacoes e ON e.id=d.embarcacao_id
        LEFT JOIN clientes c ON c.id=d.cliente_id LEFT JOIN usuarios u ON u.id=d.criado_por
        LEFT JOIN protocolo_unidades_maritimas um ON um.id=d.unidade_maritima_id
        WHERE d.id=:id".($lock?' FOR UPDATE':''));
    $q->execute([':id'=>$id]);$d=$q->fetch(PDO::FETCH_ASSOC);
    if(!$d)throw new RuntimeException('Dossiê de protocolo não encontrado.');
    if(!protocoloUsuarioPodeAcessar($pdo,$d))throw new RuntimeException('Você não possui acesso a este protocolo.');
    return $d;
}

function protocoloAuditar(PDO $pdo,string $dossieId,?string $movId,string $evento,?string $anterior,?string $novo,string $detalhe='',?string $hash=null):void
{
    $q=$pdo->prepare('INSERT INTO protocolo_auditoria(dossie_id,movimentacao_id,evento,usuario_id,perfil,ip,estado_anterior,estado_novo,detalhe,hash_referencia)VALUES(:dossie,:mov,:evento,:usuario,:perfil,:ip,:anterior,:novo,:detalhe,:hash)');
    $q->execute([':dossie'=>$dossieId,':mov'=>$movId,':evento'=>$evento,':usuario'=>$_SESSION['usuario_id']??null,':perfil'=>getCargo(),':ip'=>obterIpCliente(),':anterior'=>$anterior,':novo'=>$novo,':detalhe'=>$detalhe?:null,':hash'=>$hash]);
}

function protocoloStatusPorNatureza(string $natureza,string $atual):string
{
    return match($natureza){
        'ENVIO_ORGAO','CUMPRIMENTO_EXIGENCIA'=>'ENVIADO_AO_ORGAO',
        'RETORNO_ORGAO'=>'EM_EXIGENCIA',
        'RETIRADA_ORGAO'=>'RETIRADO',
        'ENTREGA_CLIENTE'=>'ENTREGUE_AO_CLIENTE',
        default=>$atual,
    };
}

function protocoloRotulosStatus(): array
{
    return [
        'EM_PREPARACAO'=>'Em preparação','ENVIADO_AO_ORGAO'=>'Enviado ao órgão',
        'PROTOCOLADO'=>'Protocolado','EM_ANALISE_NO_ORGAO'=>'Em análise no órgão',
        'EM_EXIGENCIA'=>'Em exigência','A_DISPOSICAO'=>'Documento à disposição',
        'RETIRADO'=>'Retirado','ENTREGUE_AO_CLIENTE'=>'Entregue ao cliente',
        'ENCERRADO'=>'Encerrado','CANCELADO'=>'Cancelado',
    ];
}

function protocoloMascararDocumento(string $documento): string
{
    $valor=preg_replace('/\D+/','',$documento);
    if(strlen($valor)<5)return str_repeat('*',strlen($valor));
    return substr($valor,0,3).str_repeat('*',max(2,strlen($valor)-5)).substr($valor,-2);
}

function protocoloNotificarAdmins(PDO $pdo,string $evento,string $titulo,string $mensagem,string $dossieId):void
{
    $ids=$pdo->query("SELECT id FROM usuarios WHERE ativo=1 AND excluido_em IS NULL AND cargo='ADMIN'")->fetchAll(PDO::FETCH_COLUMN);
    $q=$pdo->prepare("INSERT INTO notificacoes(id,usuario_id,evento,titulo,mensagem,referencia_tipo,referencia_id,url)VALUES(UUID(),:usuario,:evento,:titulo,:mensagem,'PROTOCOLO',:referencia,:url)");
    foreach($ids as $id)$q->execute([':usuario'=>$id,':evento'=>$evento,':titulo'=>$titulo,':mensagem'=>$mensagem,':referencia'=>$dossieId,':url'=>'protocolos/form?id='.urlencode($dossieId)]);
}

function protocoloProcessarAlertas(PDO $pdo): int
{
    if(getCargo()!=='ADMIN')return 0;
    try{$cfg=$pdo->query('SELECT chave,valor FROM protocolo_configuracoes')->fetchAll(PDO::FETCH_KEY_PAIR);}catch(Throwable $e){return 0;}
    $semDocumento=max(1,(int)($cfg['dias_sem_documento']??$cfg['dias_sem_comprovante']??3));
    $semRegistro=max(1,(int)($cfg['dias_sem_registro_orgao']??$cfg['dias_sem_protocolo_oficial']??3));
    $validade=max(1,(int)($cfg['dias_alerta_validade']??15));
    $sql="SELECT d.id,d.numero,d.status,d.protocolo_externo_validade,
      EXISTS(SELECT 1 FROM protocolo_movimentacoes m WHERE m.dossie_id=d.id AND m.status='CONFIRMADA' AND m.tipo='SAIDA' AND m.confirmado_em<DATE_SUB(NOW(),INTERVAL {$semDocumento} DAY) AND NOT EXISTS(SELECT 1 FROM protocolo_comprovantes c WHERE c.movimentacao_id=m.id)) sem_documento,
      EXISTS(SELECT 1 FROM protocolo_movimentacoes m WHERE m.dossie_id=d.id AND m.status='CONFIRMADA' AND m.natureza IN('ENVIO_ORGAO','CUMPRIMENTO_EXIGENCIA') AND m.confirmado_em<DATE_SUB(NOW(),INTERVAL {$semRegistro} DAY)) AND d.protocolo_externo_em IS NULL sem_registro,
      EXISTS(SELECT 1 FROM protocolo_movimentacao_itens i JOIN protocolo_movimentacoes m ON m.id=i.movimentacao_id WHERE m.dossie_id=d.id AND m.status IN('CONFIRMADA','RETIFICADA') AND i.requer_devolucao=1 AND i.devolvido_em IS NULL) original_pendente
      FROM protocolo_dossies d WHERE d.status NOT IN('ENCERRADO','CANCELADO')";
    $rows=$pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);$criados=0;
    $admins=$pdo->query("SELECT id FROM usuarios WHERE cargo='ADMIN' AND ativo=1 AND excluido_em IS NULL")->fetchAll(PDO::FETCH_COLUMN);
    $ins=$pdo->prepare("INSERT INTO notificacoes(id,usuario_id,evento,titulo,mensagem,referencia_tipo,referencia_id,url)
      SELECT UUID(),:usuario,:evento,:titulo,:mensagem,'PROTOCOLO',:ref,:url FROM DUAL WHERE NOT EXISTS(SELECT 1 FROM notificacoes WHERE usuario_id=:usuario2 AND evento=:evento2 AND referencia_id=:ref2 AND lida_em IS NULL)");
    foreach($rows as $r){$eventos=[];
      if($r['sem_documento'])$eventos['PROTOCOLO_SEM_DOCUMENTO']='Saída sem documento anexado';
      if($r['sem_registro'])$eventos['PROTOCOLO_SEM_REGISTRO_ORGAO']='Envio ao órgão ainda sem registro do atendimento';
      if($r['status']==='EM_EXIGENCIA')$eventos['PROTOCOLO_EM_EXIGENCIA']='Processo documental em exigência';
      if($r['status']==='A_DISPOSICAO')$eventos['PROTOCOLO_A_DISPOSICAO']='Documento disponível para retirada';
      if($r['original_pendente'])$eventos['PROTOCOLO_ORIGINAL_PENDENTE']='Documento original ainda sob custódia';
      if($r['protocolo_externo_validade']&&strtotime($r['protocolo_externo_validade'])<=strtotime("+{$validade} days"))$eventos['PROTOCOLO_VALIDADE']='Validade do protocolo próxima';
      foreach($eventos as $evento=>$titulo)foreach($admins as $admin){$ins->execute([':usuario'=>$admin,':evento'=>$evento,':titulo'=>$titulo,':mensagem'=>$r['numero'].' requer acompanhamento.',':ref'=>$r['id'],':url'=>'protocolos/form?id='.$r['id'],':usuario2'=>$admin,':evento2'=>$evento,':ref2'=>$r['id']]);$criados+=$ins->rowCount();}
    }return $criados;
}

function protocoloValidarArquivo(array $arquivo):array
{
    if(($arquivo['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK||empty($arquivo['tmp_name']))throw new RuntimeException('Selecione um documento válido.');
    $tam=(int)($arquivo['size']??0);if($tam<1||$tam>15*1024*1024)throw new RuntimeException('Cada documento deve ter no máximo 15 MB.');
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($arquivo['tmp_name']);
    $permitidos=['application/pdf'=>'pdf','image/jpeg'=>'jpg','image/png'=>'png'];
    if(!isset($permitidos[$mime]))throw new RuntimeException('Envie documentos em PDF, JPG ou PNG.');
    $extensao=strtolower(pathinfo((string)($arquivo['name']??''),PATHINFO_EXTENSION));
    $extensoesAceitas=$permitidos[$mime]==='jpg'?['jpg','jpeg']:[$permitidos[$mime]];
    if(!in_array($extensao,$extensoesAceitas,true))throw new RuntimeException('A extensão do documento não corresponde ao conteúdo enviado.');
    return ['mime'=>$mime,'ext'=>$permitidos[$mime],'tam'=>$tam,'hash'=>hash_file('sha256',$arquivo['tmp_name']),'nome'=>mb_substr(basename($arquivo['name']??'documento'),0,255)];
}

function protocoloGuardarArquivo(array $arquivo,array $meta,string $dossieId):string
{
    $rel='storage/protocolos/'.date('Y').'/'.$dossieId.'/'.bin2hex(random_bytes(16)).'.'.$meta['ext'];
    $abs=dirname(__DIR__).'/'.$rel;if(!is_dir(dirname($abs))){@mkdir(dirname($abs),0777,true);@chmod(dirname($abs),0777);}
    if(!move_uploaded_file($arquivo['tmp_name'],$abs))throw new RuntimeException('Não foi possível guardar o documento.');
    return $rel;
}

function protocoloNormalizarArquivos(array $campo):array
{
    if(!isset($campo['name']))return [];
    if(!is_array($campo['name']))return [$campo];
    $arquivos=[];
    foreach($campo['name'] as $i=>$nome)$arquivos[]=[
        'name'=>$nome,
        'type'=>$campo['type'][$i]??'',
        'tmp_name'=>$campo['tmp_name'][$i]??'',
        'error'=>$campo['error'][$i]??UPLOAD_ERR_NO_FILE,
        'size'=>$campo['size'][$i]??0,
    ];
    return $arquivos;
}

function protocoloSnapshot(PDO $pdo,string $movId):array
{
    $q=$pdo->prepare('SELECT * FROM protocolo_movimentacao_itens WHERE movimentacao_id=:id ORDER BY criado_em,id');
    $q->execute([':id'=>$movId]);return $q->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Formata data por extenso em português para documentos oficiais e ofícios navais.
 */
function formatarDataExtensoNaval(?string $dt): string
{
    $ts = $dt ? strtotime($dt) : time();
    $dia = date('d', $ts);
    $meses = [
        1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril',
        5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto',
        9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro'
    ];
    $mes = $meses[(int)date('m', $ts)] ?? 'janeiro';
    $ano = date('Y', $ts);
    return "Belém/PA, {$dia} de {$mes} de {$ano}.";
}

/**
 * Formata a citação estrita de um documento para o corpo do ofício naval (ex: AM-CSN: 107/26).
 */
function formatarCitacaoItemNaval(array $it): string
{
    $desc = trim($it['descricao'] ?? '');
    $rev = trim($it['numero_revisao'] ?? '');

    if (preg_match('/AM-[A-Z0-9:\/\-\.]+/i', $desc, $mMatch)) {
        return strtoupper(trim($mMatch[0]));
    }

    $sigla = 'AM-DOC';
    $descUpper = mb_strtoupper($desc, 'UTF-8');
    if (str_contains($descUpper, 'VISTORIA') || str_contains($descUpper, 'REL-V')) {
        $sigla = 'AM-REL-V';
    } elseif (str_contains($descUpper, 'CSN') || str_contains($descUpper, 'SEGURANÇA')) {
        $sigla = 'AM-CSN';
    } elseif (str_contains($descUpper, 'CNARQ') || str_contains($descUpper, 'ARQUEAÇÃO')) {
        $sigla = 'AM-CNARQ';
    } elseif (str_contains($descUpper, 'CNBL') || str_contains($descUpper, 'BORDA LIVRE')) {
        $sigla = 'AM-CNBL';
    } elseif (str_contains($descUpper, 'ANALISE') || str_contains($descUpper, 'PARECER') || str_contains($descUpper, 'REL:AP') || str_contains($descUpper, 'PLANO')) {
        $sigla = 'AM-REL:AP';
    } elseif (str_contains($descUpper, 'NARQ') || str_contains($descUpper, 'NOTA DE ARQUEAÇÃO')) {
        $sigla = 'AM-NARQ';
    } elseif (str_contains($descUpper, 'LP') || str_contains($descUpper, 'LICENÇA PROVISÓRIA')) {
        $sigla = 'AM-LP';
    } elseif (str_contains($descUpper, 'LC') || str_contains($descUpper, 'CONSTRUÇÃO') || str_contains($descUpper, 'ALTERAÇÃO')) {
        $sigla = 'AM-LC';
    } elseif (str_contains($descUpper, 'CHT')) {
        $sigla = 'AM-CHT';
    }

    if ($rev !== '') {
        return $sigla . ': ' . $rev;
    }
    if (preg_match('/[0-9]+(\/[0-9]{2,4})?/', $desc, $mNum)) {
        return $sigla . ': ' . $mNum[0];
    }
    return $sigla . ': ' . $desc;
}

/**
 * Agrega o Acervo Documental Completo vinculado a uma Embarcação (AGENTS.md / NORMAM).
 * Localiza Propostas Comerciais, Relatórios de Vistoria (com multi-versões e retornos),
 * Projetos e Pranchas de Engenharia Naval (com suas revisões REV), Pareceres Técnicos,
 * Certificados/Licenças Oficiais e Documentos Externos anexados.
 */
function protocoloObterAcervoEmbarcacao(PDO $pdo, string $embarcacaoId, ?string $dossieId = null): array
{
    $embarcacaoId = trim($embarcacaoId);
    $resultado = [
        'resumo' => [
            'total' => 0,
            'propostas' => 0,
            'vistorias' => 0,
            'projetos' => 0,
            'certificados' => 0,
            'externos' => 0,
        ],
        'itens' => [],
    ];

    if ($embarcacaoId === '') {
        return $resultado;
    }

    // 1. PROPOSTAS COMERCIAIS (Somente assinadas digitalmente)
    try {
        $qProp = $pdo->prepare("
            SELECT p.id, p.numero, p.data_emissao, p.data_validade, p.valor_total, p.status, p.assinado,
                   p.created_at, u.nome AS criador_nome
            FROM propostas p
            INNER JOIN propostas_embarcacoes pe ON pe.proposta_id = p.id
            LEFT JOIN usuarios u ON u.id = p.criado_por
            WHERE pe.embarcacao_id = :emb_id AND p.status <> 'cancelada' AND p.assinado = 1
            ORDER BY p.data_emissao DESC, p.created_at DESC
        ");
        $qProp->execute([':emb_id' => $embarcacaoId]);
        $props = $qProp->fetchAll(PDO::FETCH_ASSOC);

        foreach ($props as $p) {
            $statusLabel = 'ASSINADA';
            $valorFmt = 'R$ ' . number_format((float)$p['valor_total'], 2, ',', '.');
            $versao = 'Proposta Comercial (Assinada Digitalmente)';

            $resultado['itens'][] = [
                'id' => 'prop_' . $p['id'],
                'origem_tipo' => 'PROPOSTA',
                'origem_id' => $p['id'],
                'categoria_grupo' => 'PROPOSTAS',
                'categoria_rotulo' => 'Proposta Comercial',
                'numero' => $p['numero'],
                'titulo' => 'Proposta Comercial nº ' . $p['numero'],
                'versao_label' => $versao,
                'versao_numero' => null,
                'data_documento' => $p['data_emissao'],
                'data_validade' => $p['data_validade'],
                'status' => $p['status'],
                'status_label' => $statusLabel,
                'suporte' => 'DIGITAL',
                'forma' => 'NATO_DIGITAL',
                'tamanho_bytes' => null,
                'hash' => null,
                'url_pdf' => APP_URL . 'comercial/pdf?id=' . urlencode($p['id']),
                'nome_arquivo' => 'Proposta_' . $p['numero'] . '.pdf',
                'detalhes' => 'Valor: ' . $valorFmt . ($p['criador_nome'] ? ' · Emissor: ' . $p['criador_nome'] : ''),
            ];
            $resultado['resumo']['propostas']++;
        }
    } catch (Throwable $e) {
        error_log('Erro ao buscar propostas no acervo: ' . $e->getMessage());
    }

    // 2. VISTORIAS EM CAMPO (Somente relatórios assinados digitalmente / aprovados)
    try {
        $qVist = $pdo->prepare("
            SELECT v.id, v.numero, v.finalidade, v.data_vistoria, v.data_emissao, v.status,
                   v.mobile_versao, v.relatorio_anterior_id, v.criado_em,
                   u.nome AS assinante_nome, va.numero AS relatorio_anterior_numero,
                   r.tipo AS retorno_tipo
            FROM vistorias v
            LEFT JOIN vistorias va ON va.id = v.relatorio_anterior_id
            LEFT JOIN vistoria_retornos r ON (r.relatorio_resultado_id = v.id OR r.relatorio_origem_id = v.id)
            LEFT JOIN usuarios u ON u.id = v.criado_por
            WHERE v.embarcacao_id = :emb_id AND v.status <> 'CANCELADA'
              AND (v.assinatura_status = 'ASSINADO' OR v.assinatura_em IS NOT NULL)
            ORDER BY v.data_vistoria DESC, v.criado_em DESC
        ");
        $qVist->execute([':emb_id' => $embarcacaoId]);
        $vists = $qVist->fetchAll(PDO::FETCH_ASSOC);

        foreach ($vists as $v) {
            $num = $v['numero'] ?: 'Vistoria S/N';
            $ehRetorno = ($v['finalidade'] === 'CUMPRIMENTO_EXIGENCIAS') || !empty($v['relatorio_anterior_id']) || !empty($v['retorno_tipo']);
            
            if ($ehRetorno) {
                $ref = $v['relatorio_anterior_numero'] ? ' (Ref. ' . $v['relatorio_anterior_numero'] . ')' : '';
                $versaoLabel = 'Revisão / Retorno de Exigências' . $ref . ' (Assinado)';
            } elseif (!empty($v['mobile_versao']) && (int)$v['mobile_versao'] > 1) {
                $versaoLabel = 'Revisão ' . (int)$v['mobile_versao'] . ' (Assinada)';
            } else {
                $versaoLabel = 'Versão Inicial (Assinada)';
            }

            $resultado['itens'][] = [
                'id' => 'vist_' . $v['id'],
                'origem_tipo' => 'VISTORIA',
                'origem_id' => $v['id'],
                'categoria_grupo' => 'VISTORIAS',
                'categoria_rotulo' => 'Vistoria em Campo',
                'numero' => $num,
                'titulo' => 'Relatório de Vistoria Naval (' . $num . ')',
                'versao_label' => $versaoLabel,
                'versao_numero' => (int)($v['mobile_versao'] ?? 1),
                'data_documento' => $v['data_vistoria'] ?: ($v['data_emissao'] ?: substr($v['criado_em'], 0, 10)),
                'data_validade' => null,
                'status' => $v['status'] ?: 'CONCLUIDA',
                'status_label' => 'ASSINADO',
                'suporte' => 'DIGITAL',
                'forma' => 'NATO_DIGITAL',
                'tamanho_bytes' => null,
                'hash' => null,
                'url_pdf' => APP_URL . 'vistorias/relatorio_pdf?id=' . urlencode($v['id']),
                'nome_arquivo' => 'Relatorio_Vistoria_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $num) . '.pdf',
                'detalhes' => 'Finalidade: ' . str_replace('_', ' ', $v['finalidade'] ?: 'VISTORIA') . ($v['assinante_nome'] ? ' · Vistoriador: ' . $v['assinante_nome'] : ''),
            ];
            $resultado['resumo']['vistorias']++;
        }
    } catch (Throwable $e) {
        error_log('Erro ao buscar vistorias no acervo: ' . $e->getMessage());
    }

    // 3. ANÁLISE DE PLANOS & ENGENHARIA NAVAL (Somente documentos e pareceres aprovados/assinados)
    try {
        // 3.1 Pranchas e Memoriais Aprovados / Aceitos em PDF
        $qArq = $pdo->prepare("
            SELECT ar.id AS arquivo_id, ar.nome_original, ar.categoria, ar.classificacao,
                   ar.tamanho_bytes, ar.sha256, ar.chave_arquivo, ar.criado_em,
                   s.revisao, s.recebido_em,
                   ap.id AS analise_id, ap.numero AS processo_numero, ap.tipo_processo, ap.enquadramento
            FROM analise_planos_arquivos ar
            INNER JOIN analise_planos_submissoes s ON s.id = ar.submissao_id
            INNER JOIN analises_planos ap ON ap.id = s.analise_id
            WHERE ap.embarcacao_id = :emb_id AND ap.status <> 'CANCELADA'
              AND ar.classificacao = 'ACEITO'
              AND (ar.extensao = 'pdf' OR ar.mime_type LIKE '%pdf%')
            ORDER BY ap.numero DESC, s.revisao DESC, ar.criado_em DESC
        ");
        $qArq->execute([':emb_id' => $embarcacaoId]);
        $arqs = $qArq->fetchAll(PDO::FETCH_ASSOC);

        foreach ($arqs as $ar) {
            $revNum = (int)($ar['revisao'] ?? 0);
            $revLabel = 'REV ' . str_pad((string)$revNum, 2, '0', STR_PAD_LEFT) . ' (Aprovado)';
            $catLabel = str_replace('_', ' ', $ar['categoria'] ?: 'PROJETO');

            $resultado['itens'][] = [
                'id' => 'proj_arq_' . $ar['arquivo_id'],
                'origem_tipo' => 'ANALISE_PLANOS_ARQUIVO',
                'origem_id' => $ar['arquivo_id'],
                'categoria_grupo' => 'PROJETOS',
                'categoria_rotulo' => 'Engenharia & Planos',
                'numero' => $ar['processo_numero'] ?: 'Processo S/N',
                'titulo' => $ar['nome_original'],
                'versao_label' => $revLabel,
                'versao_numero' => $revNum,
                'data_documento' => $ar['recebido_em'] ?: substr($ar['criado_em'], 0, 10),
                'data_validade' => null,
                'status' => 'ACEITO',
                'status_label' => 'APROVADO',
                'suporte' => 'DIGITAL',
                'forma' => 'NATO_DIGITAL',
                'tamanho_bytes' => (int)($ar['tamanho_bytes'] ?? 0),
                'hash' => $ar['sha256'] ?: null,
                'url_pdf' => APP_URL . 'analises_planos/arquivo?id=' . urlencode($ar['arquivo_id']),
                'nome_arquivo' => $ar['nome_original'],
                'detalhes' => 'Processo: ' . ($ar['processo_numero'] ?: 'S/N') . ' (' . ($ar['tipo_processo'] ?: 'Análise') . ') · Categoria: ' . $catLabel,
            ];
            $resultado['resumo']['projetos']++;
        }

        // 3.2 Pareceres Técnicos Oficiais Assinados Digitalmente pelo Analista
        $qPar = $pdo->prepare("
            SELECT p.id, p.numero, p.versao, p.finalidade, p.resultado, p.status,
                   p.caminho_pdf_final, p.hash_pdf_final, p.publicado_em, p.criado_em,
                   ap.numero AS processo_numero, ap.tipo_processo
            FROM analise_planos_pareceres p
            INNER JOIN analises_planos ap ON ap.id = p.analise_id
            WHERE ap.embarcacao_id = :emb_id 
              AND p.status = 'PUBLICADO' 
              AND p.assinado_analista_em IS NOT NULL
            ORDER BY p.criado_em DESC, p.versao DESC
        ");
        $qPar->execute([':emb_id' => $embarcacaoId]);
        $pares = $qPar->fetchAll(PDO::FETCH_ASSOC);

        foreach ($pares as $par) {
            $numPar = $par['numero'] ?: 'Parecer S/N';
            $versaoPar = 'Parecer Técnico V' . ((int)$par['versao']) . ' (Assinado Digitalmente)';

            $resultado['itens'][] = [
                'id' => 'proj_par_' . $par['id'],
                'origem_tipo' => 'ANALISE_PLANOS_PARECER',
                'origem_id' => $par['id'],
                'categoria_grupo' => 'PROJETOS',
                'categoria_rotulo' => 'Parecer Técnico Naval',
                'numero' => $numPar,
                'titulo' => 'Parecer Técnico de Engenharia (' . $numPar . ')',
                'versao_label' => $versaoPar,
                'versao_numero' => (int)$par['versao'],
                'data_documento' => $par['publicado_em'] ? substr($par['publicado_em'], 0, 10) : substr($par['criado_em'], 0, 10),
                'data_validade' => null,
                'status' => $par['resultado'] ?: 'APROVADO',
                'status_label' => 'ASSINADO',
                'suporte' => 'DIGITAL',
                'forma' => 'NATO_DIGITAL',
                'tamanho_bytes' => null,
                'hash' => $par['hash_pdf_final'] ?: null,
                'url_pdf' => APP_URL . 'analises_planos/parecer_pdf?id=' . urlencode($par['id']),
                'nome_arquivo' => 'Parecer_Tecnico_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $numPar) . '.pdf',
                'detalhes' => 'Processo: ' . ($par['processo_numero'] ?: 'S/N') . ' · Conclusão: ' . str_replace('_', ' ', $par['resultado'] ?: 'CONCLUÍDO'),
            ];
            $resultado['resumo']['projetos']++;
        }
    } catch (Throwable $e) {
        error_log('Erro ao buscar engenharia/projetos no acervo: ' . $e->getMessage());
    }

    // 4. CERTIFICADOS NAVAIS E LICENÇAS OFICIAIS (Somente Assinados Digitalmente: assinado = 1)
    $tabelasCert = [
        ['tipo' => 'CSN', 'tabela' => 'certificados_csn', 'num_col' => 'numero', 'dt_val_col' => 'data_validade', 'pdf_route' => 'documentacao/certificados/pdf', 'nome' => 'Certificado de Segurança da Navegação (CSN)'],
        ['tipo' => 'CNBL', 'tabela' => 'certificados_cnbl', 'num_col' => 'numero', 'dt_val_col' => 'data_validade', 'pdf_route' => 'documentacao/cnbl/pdf', 'nome' => 'Certificado Nacional de Borda Livre (CNBL)'],
        ['tipo' => 'CNARQ', 'tabela' => 'certificados_cnarq', 'num_col' => 'numero', 'dt_val_col' => 'data_validade', 'pdf_route' => 'documentacao/cnarq/pdf', 'nome' => 'Certificado Nacional de Arqueação (CNARQ)'],
        ['tipo' => 'LP', 'tabela' => 'certificados_lp', 'num_col' => 'numero_lp', 'dt_val_col' => 'validade_data', 'pdf_route' => 'documentacao/lp/pdf', 'nome' => 'Licença Provisória (LP)'],
        ['tipo' => 'LC', 'tabela' => 'certificados_lc', 'num_col' => 'numero_lc', 'dt_val_col' => 'data_validade', 'pdf_route' => 'documentacao/lc/pdf', 'nome' => 'Licença de Construção / Alteração (LC/LA/LR)'],
        ['tipo' => 'NAR', 'tabela' => 'certificados_nar', 'num_col' => 'numero', 'dt_val_col' => 'NULL', 'pdf_route' => 'documentacao/nar/pdf', 'nome' => 'Nota de Arqueação (NAR)'],
        ['tipo' => 'CHT', 'tabela' => 'certificados_cht', 'num_col' => 'numero_certificado', 'dt_val_col' => 'data_validade', 'pdf_route' => 'documentacao/cht/pdf', 'nome' => 'Certificado de Homologação de Tirantes (CHT)'],
    ];

    foreach ($tabelasCert as $tc) {
        try {
            $sqlVal = $tc['dt_val_col'] === 'NULL' ? 'NULL AS dt_validade' : $tc['dt_val_col'] . ' AS dt_validade';
            $qCert = $pdo->prepare("
                SELECT id, {$tc['num_col']} AS num_cert, data_emissao, {$sqlVal}, status, assinado,
                       caminho_arquivo_pdf, hash_arquivo_pdf
                FROM {$tc['tabela']}
                WHERE embarcacao_id = :emb_id AND ativo = 1 AND status <> 'cancelado' AND assinado = 1
                ORDER BY data_emissao DESC
            ");
            $qCert->execute([':emb_id' => $embarcacaoId]);
            $certs = $qCert->fetchAll(PDO::FETCH_ASSOC);

            foreach ($certs as $c) {
                $numCert = $c['num_cert'] ?: 'S/N';
                $statusCert = 'ASSINADO';
                $versaoLabel = 'Via Oficial Assinada Digitalmente';

                $resultado['itens'][] = [
                    'id' => 'cert_' . strtolower($tc['tipo']) . '_' . $c['id'],
                    'origem_tipo' => 'CERTIFICADO_' . $tc['tipo'],
                    'origem_id' => $c['id'],
                    'categoria_grupo' => 'CERTIFICADOS',
                    'categoria_rotulo' => 'Certificado ' . $tc['tipo'],
                    'numero' => $numCert,
                    'titulo' => $tc['nome'] . ' - ' . $numCert,
                    'versao_label' => $versaoLabel,
                    'versao_numero' => null,
                    'data_documento' => $c['data_emissao'],
                    'data_validade' => $c['dt_validade'] ?: null,
                    'status' => $statusCert,
                    'status_label' => $statusCert,
                    'suporte' => 'DIGITAL',
                    'forma' => 'NATO_DIGITAL',
                    'tamanho_bytes' => null,
                    'hash' => $c['hash_arquivo_pdf'] ?: null,
                    'url_pdf' => APP_URL . $tc['pdf_route'] . '?id=' . urlencode($c['id']),
                    'nome_arquivo' => $tc['tipo'] . '_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $numCert) . '.pdf',
                    'detalhes' => 'Emissão: ' . date('d/m/Y', strtotime($c['data_emissao'])) . ($c['dt_validade'] ? ' · Validade: ' . date('d/m/Y', strtotime($c['dt_validade'])) : ''),
                ];
                $resultado['resumo']['certificados']++;
            }
        } catch (Throwable $e) {
            error_log('Erro ao buscar certificados ' . $tc['tipo'] . ': ' . $e->getMessage());
        }
    }

    // 5. DOCUMENTOS EXTERNOS E COMPROVANTES DA EMBARCAÇÃO
    try {
        $qExt = $pdo->prepare("
            SELECT c.id, c.dossie_id, c.movimentacao_id, c.tipo, c.nome_original,
                   c.mime_type, c.tamanho_bytes, c.sha256, c.caminho, c.criado_em,
                   d.numero AS dossie_numero, m.sequencia AS mov_sequencia
            FROM protocolo_comprovantes c
            INNER JOIN protocolo_dossies d ON d.id = c.dossie_id
            LEFT JOIN protocolo_movimentacoes m ON m.id = c.movimentacao_id
            WHERE d.embarcacao_id = :emb_id
            ORDER BY c.criado_em DESC
        ");
        $qExt->execute([':emb_id' => $embarcacaoId]);
        $exts = $qExt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($exts as $ext) {
            $tipoLabel = str_replace('_', ' ', $ext['tipo'] ?: 'DOCUMENTO');
            $movLabel = $ext['mov_sequencia'] ? ' (Evento #' . str_pad((string)$ext['mov_sequencia'], 2, '0', STR_PAD_LEFT) . ')' : ' (Dossiê Geral)';

            $resultado['itens'][] = [
                'id' => 'ext_' . $ext['id'],
                'origem_tipo' => 'DOCUMENTO_EXTERNO',
                'origem_id' => $ext['id'],
                'categoria_grupo' => 'EXTERNOS',
                'categoria_rotulo' => 'Anexo / Documento Externo',
                'numero' => $ext['dossie_numero'],
                'titulo' => $ext['nome_original'],
                'versao_label' => 'Anexo Externo' . $movLabel,
                'versao_numero' => null,
                'data_documento' => substr($ext['criado_em'], 0, 10),
                'data_validade' => null,
                'status' => 'CONFERIDO',
                'status_label' => 'CONFERIDO',
                'suporte' => 'DIGITAL',
                'forma' => 'DIGITALIZADO',
                'tamanho_bytes' => (int)($ext['tamanho_bytes'] ?? 0),
                'hash' => $ext['sha256'] ?: null,
                'url_pdf' => APP_URL . 'protocolos/arquivo?id=' . urlencode($ext['id']),
                'nome_arquivo' => $ext['nome_original'],
                'detalhes' => 'Dossiê: ' . $ext['dossie_numero'] . ' · Tipo: ' . $tipoLabel,
            ];
            $resultado['resumo']['externos']++;
        }
    } catch (Throwable $e) {
        error_log('Erro ao buscar documentos externos no acervo: ' . $e->getMessage());
    }

    $resultado['resumo']['total'] = count($resultado['itens']);
    return $resultado;
}

