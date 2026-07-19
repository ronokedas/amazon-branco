<?php

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/analise_planos.php';

analisePlanosExigirAcesso();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verificarCSRF($_POST['csrf_token'] ?? '')) {
    setMensagem('error', 'Sessão expirada. Tente novamente.');
    redirecionar(APP_URL . 'analises-planos');
}

$acao = $_POST['action'] ?? '';
$analiseId = trim($_POST['analise_id'] ?? $_POST['id'] ?? '');
$retorno = fn(string $id = '') => APP_URL . ($id ? 'analises-planos/form?id=' . urlencode($id) : 'analises-planos');

try {
    if ($acao === 'salvar') {
        $tipo = $_POST['tipo_processo'] ?? '';
        $norma = $_POST['enquadramento'] ?? '';
        $embarcacao = trim($_POST['embarcacao_id'] ?? '');
        $objeto = trim($_POST['objeto'] ?? '');
        $analista = getCargo() === 'ADMIN' ? trim($_POST['analista_id'] ?? '') : ($_SESSION['usuario_id'] ?? '');
        if (!$embarcacao || !$objeto || !$analista || !in_array($tipo, ['LC','LCEC','LA','LR','OUTRO'], true) || !in_array($norma, ['NORMAM-201','NORMAM-202','OUTRO'], true)) {
            throw new InvalidArgumentException('Preencha embarcação, processo, enquadramento, objeto e analista.');
        }
        $pdo->beginTransaction();
        if ($analiseId === '') {
            $analiseId = gerarUUID();
            $numero = gerarNumeroDocumento('RAP', 'AM-RAP');
            $stmt = $pdo->prepare("INSERT INTO analises_planos (id,numero,embarcacao_id,solicitante_id,tipo_processo,enquadramento,objeto,estaleiro,numero_casco,responsavel_projeto_nome,responsavel_projeto_registro,art_numero,analista_id,responsavel_assinatura_id,observacoes,criado_por) VALUES (:id,:numero,:embarcacao,:solicitante,:tipo,:norma,:objeto,:estaleiro,:casco,:responsavel,:registro,:art,:analista,:assinatura,:observacoes,:usuario)");
            $stmt->execute([
                ':id'=>$analiseId, ':numero'=>$numero, ':embarcacao'=>$embarcacao, ':solicitante'=>($_POST['solicitante_id'] ?? '') ?: null,
                ':tipo'=>$tipo, ':norma'=>$norma, ':objeto'=>$objeto, ':estaleiro'=>trim($_POST['estaleiro'] ?? '') ?: null,
                ':casco'=>trim($_POST['numero_casco'] ?? '') ?: null, ':responsavel'=>trim($_POST['responsavel_projeto_nome'] ?? '') ?: null,
                ':registro'=>trim($_POST['responsavel_projeto_registro'] ?? '') ?: null, ':art'=>trim($_POST['art_numero'] ?? '') ?: null,
                ':analista'=>$analista, ':assinatura'=>(int)($_POST['responsavel_assinatura_id'] ?? 0) ?: null,
                ':observacoes'=>trim($_POST['observacoes'] ?? '') ?: null, ':usuario'=>$_SESSION['usuario_id'],
            ]);
            $itens = [
                ['Memorial Descritivo','NORMAM, Anexo 3-F'], ['Plano de Arranjo Geral','NORMAM, Anexo 3-F'],
                ['Plano de Linhas','NORMAM, Anexo 3-F'], ['Seção Mestra e Perfil Estrutural','NORMAM, Anexo 3-F'],
                ['Curvas Hidrostáticas e cálculos','NORMAM, Anexo 3-F'], ['Estudo/Folheto de Estabilidade','NORMAM, Anexo 3-F'],
                ['Plano de Capacidade','NORMAM, Anexo 3-F'], ['Plano de Segurança','NORMAM, Anexo 3-F'],
                ['Anotação de Responsabilidade Técnica (ART)','NORMAM, Anexo 3-F'],
            ];
            $ins = $pdo->prepare("INSERT INTO analise_planos_itens (id,analise_id,ordem,documento,referencia_normativa,criado_por) VALUES (UUID(),:analise,:ordem,:documento,:referencia,:usuario)");
            foreach ($itens as $i => $item) $ins->execute([':analise'=>$analiseId, ':ordem'=>$i+1, ':documento'=>$item[0], ':referencia'=>$item[1], ':usuario'=>$_SESSION['usuario_id']]);
            analisePlanosHistorico($pdo, $analiseId, 'CRIADA', null, 'RASCUNHO', 'Processo ' . $numero . ' criado.');
        } else {
            $atual = analisePlanosCarregar($pdo, $analiseId, true);
            if (in_array($atual['status'], ['CONCLUIDA','REPROVADA','CANCELADA'], true)) throw new RuntimeException('Processo finalizado não pode ser editado.');
            $stmt = $pdo->prepare("UPDATE analises_planos SET embarcacao_id=:embarcacao,solicitante_id=:solicitante,tipo_processo=:tipo,enquadramento=:norma,objeto=:objeto,estaleiro=:estaleiro,numero_casco=:casco,responsavel_projeto_nome=:responsavel,responsavel_projeto_registro=:registro,art_numero=:art,analista_id=:analista,responsavel_assinatura_id=:assinatura,observacoes=:observacoes WHERE id=:id");
            $stmt->execute([
                ':embarcacao'=>$embarcacao, ':solicitante'=>($_POST['solicitante_id'] ?? '') ?: null, ':tipo'=>$tipo, ':norma'=>$norma,
                ':objeto'=>$objeto, ':estaleiro'=>trim($_POST['estaleiro'] ?? '') ?: null, ':casco'=>trim($_POST['numero_casco'] ?? '') ?: null,
                ':responsavel'=>trim($_POST['responsavel_projeto_nome'] ?? '') ?: null, ':registro'=>trim($_POST['responsavel_projeto_registro'] ?? '') ?: null,
                ':art'=>trim($_POST['art_numero'] ?? '') ?: null, ':analista'=>$analista,
                ':assinatura'=>(int)($_POST['responsavel_assinatura_id'] ?? 0) ?: null, ':observacoes'=>trim($_POST['observacoes'] ?? '') ?: null, ':id'=>$analiseId,
            ]);
            analisePlanosHistorico($pdo, $analiseId, 'DADOS_ATUALIZADOS', $atual['status'], $atual['status']);
        }
        $pdo->commit();
        setMensagem('success', 'Análise de planos salva com sucesso.');
        redirecionar($retorno($analiseId));
    }

    $analise = analisePlanosCarregar($pdo, $analiseId);

    if ($acao === 'adicionar_submissao') {
        if (in_array($analise['status'], ['AGUARDANDO_APROVACAO','CONCLUIDA','REPROVADA','CANCELADA'], true)) throw new RuntimeException('O processo não aceita nova revisão neste estado.');
        $arquivos = $_FILES['arquivos'] ?? null;
        if (!$arquivos || !is_array($arquivos['name'] ?? null)) throw new RuntimeException('Selecione pelo menos um arquivo.');
        $preparados = [];
        foreach ($arquivos['name'] as $i => $nome) {
            if (($arquivos['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
            $arquivo = ['name'=>$nome,'type'=>$arquivos['type'][$i] ?? '','tmp_name'=>$arquivos['tmp_name'][$i] ?? '','error'=>$arquivos['error'][$i] ?? UPLOAD_ERR_NO_FILE,'size'=>$arquivos['size'][$i] ?? 0];
            $preparados[] = [$arquivo, analisePlanosValidarUpload($arquivo)];
        }
        if (!$preparados) throw new RuntimeException('Selecione pelo menos um arquivo válido.');
        $pdo->beginTransaction();
        $stmtRev = $pdo->prepare('SELECT COALESCE(MAX(revisao),0)+1 FROM analise_planos_submissoes WHERE analise_id=:id FOR UPDATE');
        $stmtRev->execute([':id'=>$analiseId]); $revisao=(int)$stmtRev->fetchColumn();
        $submissaoId=gerarUUID();
        $pdo->prepare('INSERT INTO analise_planos_submissoes (id,analise_id,revisao,descricao,recebido_em,criado_por) VALUES (:id,:analise,:revisao,:descricao,:data,:usuario)')->execute([
            ':id'=>$submissaoId, ':analise'=>$analiseId, ':revisao'=>$revisao, ':descricao'=>trim($_POST['descricao'] ?? '') ?: null,
            ':data'=>($_POST['recebido_em'] ?? '') ?: date('Y-m-d'), ':usuario'=>$_SESSION['usuario_id'],
        ]);
        $ins=$pdo->prepare('INSERT INTO analise_planos_arquivos (id,submissao_id,categoria,nome_original,extensao,mime_type,tamanho_bytes,sha256,chave_arquivo,criado_por) VALUES (:id,:submissao,:categoria,:nome,:extensao,:mime,:tamanho,:hash,:chave,:usuario)');
        foreach ($preparados as [$arquivo,$meta]) {
            $chave=analisePlanosGuardarUpload($arquivo,$analiseId,$meta);
            $ins->execute([':id'=>gerarUUID(),':submissao'=>$submissaoId,':categoria'=>trim($_POST['categoria'] ?? 'Outros'),':nome'=>$meta['nome'],':extensao'=>$meta['extensao'],':mime'=>$meta['mime'],':tamanho'=>$meta['tamanho'],':hash'=>$meta['sha256'],':chave'=>$chave,':usuario'=>$_SESSION['usuario_id']]);
        }
        $novo='EM_ANALISE';
        $pdo->prepare('UPDATE analises_planos SET status=:status WHERE id=:id')->execute([':status'=>$novo,':id'=>$analiseId]);
        analisePlanosHistorico($pdo,$analiseId,'REVISAO_RECEBIDA',$analise['status'],$novo,'Revisão '.$revisao.' com '.count($preparados).' arquivo(s).');
        $pdo->commit();
        setMensagem('success','Revisão adicionada e armazenada de forma privada.'); redirecionar($retorno($analiseId));
    }

    if ($acao === 'salvar_itens') {
        if (!in_array($analise['status'], ['RASCUNHO','EM_ANALISE','AGUARDANDO_CORRECAO'], true)) throw new RuntimeException('A matriz não pode ser alterada neste estado.');
        $ids=$_POST['item_id'] ?? []; $docs=$_POST['documento'] ?? []; $resultados=$_POST['resultado'] ?? [];
        $pdo->beginTransaction();
        $upd=$pdo->prepare('UPDATE analise_planos_itens SET ordem=:ordem,documento=:documento,revisao_documento=:revisao,referencia_normativa=:referencia,resultado=:resultado,observacao=:observacao WHERE id=:id AND analise_id=:analise');
        foreach ($ids as $i=>$id) {
            $res=$resultados[$i] ?? 'PENDENTE'; if(!in_array($res,['PENDENTE','CONFORME','EXIGENCIA','NAO_APLICA'],true))$res='PENDENTE';
            $upd->execute([':ordem'=>$i+1,':documento'=>trim($docs[$i] ?? '') ?: 'Item sem título',':revisao'=>trim($_POST['revisao_documento'][$i] ?? '') ?: null,':referencia'=>trim($_POST['referencia_normativa'][$i] ?? '') ?: null,':resultado'=>$res,':observacao'=>trim($_POST['item_observacao'][$i] ?? '') ?: null,':id'=>$id,':analise'=>$analiseId]);
        }
        if (trim($_POST['novo_documento'] ?? '') !== '') {
            $pdo->prepare('INSERT INTO analise_planos_itens (id,analise_id,ordem,documento,referencia_normativa,resultado,observacao,criado_por) VALUES (UUID(),:analise,:ordem,:documento,:referencia,:resultado,:observacao,:usuario)')->execute([':analise'=>$analiseId,':ordem'=>count($ids)+1,':documento'=>trim($_POST['novo_documento']),':referencia'=>trim($_POST['nova_referencia'] ?? '') ?: null,':resultado'=>'PENDENTE',':observacao'=>null,':usuario'=>$_SESSION['usuario_id']]);
        }
        analisePlanosHistorico($pdo,$analiseId,'MATRIZ_ATUALIZADA',$analise['status'],$analise['status']); $pdo->commit();
        setMensagem('success','Matriz técnica atualizada.'); redirecionar($retorno($analiseId));
    }

    if ($acao === 'salvar_exigencias') {
        if (!in_array($analise['status'], ['RASCUNHO','EM_ANALISE','AGUARDANDO_CORRECAO'], true)) throw new RuntimeException('As exigências não podem ser alteradas neste estado.');
        $ids=$_POST['exigencia_id'] ?? []; $pdo->beginTransaction();
        $upd=$pdo->prepare('UPDATE analise_planos_exigencias SET ordem=:ordem,descricao=:descricao,referencia_normativa=:referencia,prazo=:prazo,status=:status,observacao_cumprimento=:observacao WHERE id=:id AND analise_id=:analise');
        foreach($ids as $i=>$id){$status=$_POST['exigencia_status'][$i]??'PENDENTE';if(!in_array($status,['PENDENTE','CUMPRIDA','PARCIAL','TRANSCRITA'],true))$status='PENDENTE';$upd->execute([':ordem'=>$i+1,':descricao'=>trim($_POST['exigencia_descricao'][$i]??''),':referencia'=>trim($_POST['exigencia_referencia'][$i]??'')?:null,':prazo'=>($_POST['exigencia_prazo'][$i]??'')?:null,':status'=>$status,':observacao'=>trim($_POST['exigencia_observacao'][$i]??'')?:null,':id'=>$id,':analise'=>$analiseId]);}
        if(trim($_POST['nova_exigencia']??'')!==''){$pdo->prepare('INSERT INTO analise_planos_exigencias (id,analise_id,ordem,descricao,referencia_normativa,prazo,criado_por) VALUES (UUID(),:analise,:ordem,:descricao,:referencia,:prazo,:usuario)')->execute([':analise'=>$analiseId,':ordem'=>count($ids)+1,':descricao'=>trim($_POST['nova_exigencia']),':referencia'=>trim($_POST['nova_exigencia_referencia']??'')?:null,':prazo'=>($_POST['nova_exigencia_prazo']??'')?:null,':usuario'=>$_SESSION['usuario_id']]);}
        analisePlanosHistorico($pdo,$analiseId,'EXIGENCIAS_ATUALIZADAS',$analise['status'],$analise['status']);$pdo->commit();
        setMensagem('success','Exigências atualizadas.');redirecionar($retorno($analiseId));
    }

    if ($acao === 'criar_parecer') {
        if (!in_array($analise['status'], ['EM_ANALISE','AGUARDANDO_CORRECAO'], true)) throw new RuntimeException('O processo precisa estar em análise.');
        $resultado=$_POST['resultado']??''; if(!in_array($resultado,['EXIGENCIAS','APROVADO','APROVADO_COM_EXIGENCIAS','REPROVADO'],true))throw new InvalidArgumentException('Resultado inválido.');
        $resumo=trim($_POST['resumo']??'');$conclusao=trim($_POST['conclusao']??'');$responsavel=(int)($_POST['responsavel_assinatura_id']??$analise['responsavel_assinatura_id']);
        if(!$resumo||!$conclusao||!$responsavel)throw new InvalidArgumentException('Informe resumo, conclusão e responsável técnico.');
        $pend=$pdo->prepare("SELECT COUNT(*) FROM analise_planos_itens WHERE analise_id=:id AND resultado='PENDENTE'");$pend->execute([':id'=>$analiseId]);if((int)$pend->fetchColumn()>0)throw new RuntimeException('Classifique todos os itens da matriz antes de emitir o parecer.');
        if($resultado==='EXIGENCIAS'){$q=$pdo->prepare("SELECT COUNT(*) FROM analise_planos_exigencias WHERE analise_id=:id AND status IN ('PENDENTE','PARCIAL','TRANSCRITA')");$q->execute([':id'=>$analiseId]);if((int)$q->fetchColumn()<1)throw new RuntimeException('Cadastre ao menos uma exigência pendente.');}
        $pdo->beginTransaction();$v=$pdo->prepare('SELECT COALESCE(MAX(versao),0)+1 FROM analise_planos_pareceres WHERE analise_id=:id FOR UPDATE');$v->execute([':id'=>$analiseId]);$versao=(int)$v->fetchColumn();$parecerId=gerarUUID();
        $pdo->prepare("INSERT INTO analise_planos_pareceres (id,analise_id,versao,resultado,resumo,conclusao,status,responsavel_assinatura_id,criado_por) VALUES (:id,:analise,:versao,:resultado,:resumo,:conclusao,'AGUARDANDO_APROVACAO',:responsavel,:usuario)")->execute([':id'=>$parecerId,':analise'=>$analiseId,':versao'=>$versao,':resultado'=>$resultado,':resumo'=>$resumo,':conclusao'=>$conclusao,':responsavel'=>$responsavel,':usuario'=>$_SESSION['usuario_id']]);
        $pdo->prepare("UPDATE analises_planos SET status='AGUARDANDO_APROVACAO',responsavel_assinatura_id=:responsavel WHERE id=:id")->execute([':responsavel'=>$responsavel,':id'=>$analiseId]);analisePlanosHistorico($pdo,$analiseId,'PARECER_PREPARADO',$analise['status'],'AGUARDANDO_APROVACAO','Parecer v'.$versao.' preparado.');$pdo->commit();
        setMensagem('success','Parecer preparado. Um administrador deve aprovar e assinar para publicá-lo.');redirecionar($retorno($analiseId).'#pareceres');
    }

    if ($acao === 'alterar_status') {
        $novo=$_POST['novo_status']??'';if(!analisePlanosTransicaoPermitida($analise['status'],$novo))throw new RuntimeException('Transição de estado não permitida.');
        $pdo->beginTransaction();$pdo->prepare('UPDATE analises_planos SET status=:status WHERE id=:id')->execute([':status'=>$novo,':id'=>$analiseId]);analisePlanosHistorico($pdo,$analiseId,'STATUS_ALTERADO',$analise['status'],$novo,trim($_POST['motivo']??''));$pdo->commit();
        setMensagem('success','Situação atualizada.');redirecionar($retorno($analiseId));
    }

    throw new RuntimeException('Ação inválida.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Erro em Análise de Planos: '.$e->getMessage());
    setMensagem('error',$e->getMessage());
    redirecionar($retorno($analiseId));
}

