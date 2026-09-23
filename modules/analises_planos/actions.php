<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/analise_planos.php';
analisePlanosExigirAcesso();
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
       || !empty($_POST['is_ajax']) 
       || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verificarCSRF($_POST['csrf_token'] ?? '')) {
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Sessão expirada. Recarregue a página.']);
        exit;
    }
    setMensagem('error', 'Sessão expirada. Tente novamente.');
    redirecionar(APP_URL . 'analises-planos');
}

$acao = trim($_POST['action'] ?? '');
$analiseId = trim($_POST['analise_id'] ?? $_POST['id'] ?? '');
$abaRetorno = trim($_POST['aba'] ?? $_GET['aba'] ?? '');
$retorno = function(string $id = '', ?string $aba = null) use ($abaRetorno) {
    if (!$id) return APP_URL . 'analises-planos';
    $abaFinal = $aba !== null ? $aba : ($abaRetorno !== '' ? $abaRetorno : 'exigencias');
    return APP_URL . 'analises-planos/form?id=' . urlencode($id) . ($abaFinal !== '' ? '&aba=' . urlencode($abaFinal) : '');
};

// As funções analiseAcaoExigirTecnico, analiseAcaoResponsavelDoAnalista, analiseAcaoCriarLicenca e analiseAcaoPersistirParecerPdf
// estão centralizadas em includes/analise_planos.php para reuso seguro no sistema e testes.


try {
    if ($acao === 'criar_analise') {
        $embarcacaoId = trim($_POST['embarcacao_id'] ?? '');
        $solicitanteId = trim($_POST['solicitante_id'] ?? '');
        $tipoProcesso = trim($_POST['tipo_processo'] ?? 'LC');
        $enquadramento = trim($_POST['enquadramento'] ?? 'NORMAM-202');
        $classeCertificacao = trim($_POST['classe_certificacao'] ?? 'EC1');
        $objeto = trim($_POST['objeto'] ?? '');
        $analistaId = trim($_POST['analista_id'] ?? '') ?: null;
        $prazoAgendadoEm = trim($_POST['prazo_agendado_em'] ?? '') ?: null;
        $estaleiro = trim($_POST['estaleiro'] ?? '') ?: null;
        $numeroCasco = trim($_POST['numero_casco'] ?? '') ?: null;
        $responsavelProjetoNome = trim($_POST['responsavel_projeto_nome'] ?? '') ?: null;
        $responsavelProjetoRegistro = trim($_POST['responsavel_projeto_registro'] ?? '') ?: null;
        $artNumero = trim($_POST['art_numero'] ?? '') ?: null;
        $observacoes = trim($_POST['observacoes'] ?? '') ?: null;

        if ($embarcacaoId === '') {
            throw new RuntimeException('Selecione a embarcação para a análise.');
        }
        if ($objeto === '') {
            $objeto = 'Análise de planos ' . $tipoProcesso . ' (' . $classeCertificacao . ')';
        }
        if (!in_array($enquadramento, analisePlanosNormasPermitidas(), true)) {
            $enquadramento = 'NORMAM-202';
        }
        if (!in_array($tipoProcesso, analisePlanosTiposPermitidos(), true)) {
            $tipoProcesso = 'LC';
        }
        if (!in_array($classeCertificacao, ['EC1', 'EC2'], true)) {
            $classeCertificacao = 'EC1';
        }

        if ($solicitanteId === '') {
            $stmtEmb = $pdo->prepare("SELECT cliente_id, proprietario_id FROM embarcacoes WHERE id = :id");
            $stmtEmb->execute([':id' => $embarcacaoId]);
            $embRow = $stmtEmb->fetch(PDO::FETCH_ASSOC);
            $solicitanteId = $embRow['cliente_id'] ?? $embRow['proprietario_id'] ?? null;
        }

        $novoId = gerarUUID();
        $numero = gerarNumeroDocumento('RAP', 'AM-RAP');
        $statusInicial = $analistaId ? 'AGENDADA' : 'AGUARDANDO_AGENDAMENTO';
        $usuarioAtual = (string)($_SESSION['usuario_id'] ?? '');

        $stmtInsert = $pdo->prepare("INSERT INTO analises_planos (
            id, numero, embarcacao_id, solicitante_id, tipo_processo, enquadramento,
            classe_certificacao, objeto, estaleiro, numero_casco,
            responsavel_projeto_nome, responsavel_projeto_registro, art_numero,
            analista_id, status, prazo_agendado_em, observacoes, criado_por
        ) VALUES (
            :id, :numero, :embarcacao, :solicitante, :tipo, :norma,
            :classe, :objeto, :estaleiro, :casco,
            :resp_nome, :resp_reg, :art,
            :analista, :status, :prazo, :obs, :criado_por
        )");
        $stmtInsert->execute([
            ':id' => $novoId,
            ':numero' => $numero,
            ':embarcacao' => $embarcacaoId,
            ':solicitante' => $solicitanteId ?: null,
            ':tipo' => $tipoProcesso,
            ':norma' => $enquadramento,
            ':classe' => $classeCertificacao,
            ':objeto' => $objeto,
            ':estaleiro' => $estaleiro,
            ':casco' => $numeroCasco,
            ':resp_nome' => $responsavelProjetoNome,
            ':resp_reg' => $responsavelProjetoRegistro,
            ':art' => $artNumero,
            ':analista' => $analistaId,
            ':status' => $statusInicial,
            ':prazo' => $prazoAgendadoEm,
            ':obs' => $observacoes,
            ':criado_por' => $usuarioAtual,
        ]);

        analisePlanosSemearChecklist($pdo, $novoId, $tipoProcesso, $enquadramento, $classeCertificacao, $usuarioAtual);
        analisePlanosHistorico($pdo, $novoId, 'CRIACAO_DIRETA', null, $statusInicial, 'Processo criado diretamente no sistema pelo usuário ' . ($_SESSION['usuario_nome'] ?? ''));

        if ($analistaId && $prazoAgendadoEm) {
            $stmtHistAg = $pdo->prepare("INSERT INTO analise_planos_agenda_historico (
                analise_id, analista_anterior_id, analista_novo_id, prazo_anterior_em, prazo_novo_em, motivo, acao, criado_por
            ) VALUES (:analise, NULL, :analista, NULL, :prazo, 'Agendamento inicial na abertura do processo', 'AGENDAMENTO', :criador)");
            $stmtHistAg->execute([
                ':analise' => $novoId,
                ':analista' => $analistaId,
                ':prazo' => $prazoAgendadoEm,
                ':criador' => $usuarioAtual,
            ]);
            analisePlanosNotificar($pdo, $analistaId, 'ANALISE_AGENDADA', 'Nova Análise de Planos atribuída', "Você foi atribuído ao processo {$numero}.", $novoId, 'analises-planos/form?id=' . urlencode($novoId));
        }

        setMensagem('success', "Processo de Análise de Planos {$numero} criado com sucesso! O checklist normativo foi gerado.");
        redirecionar(APP_URL . 'analises-planos/form?id=' . urlencode($novoId));
    }

    if ($analiseId === '') throw new RuntimeException('Análise não informada.');
    $analise = analisePlanosCarregar($pdo, $analiseId, in_array($acao, ['agendar','iniciar','assinar_parecer','publicar','emitir_licenca'], true));
    $cargo = getCargo();
    $usuario = (string)($_SESSION['usuario_id'] ?? '');

    if ($acao === 'vincular_legado') {
        if($cargo!=='ADMIN')throw new RuntimeException('Somente o admin pode vincular processos legados.');
        if(!empty($analise['proposta_id'])||!empty($analise['servico_id'])||!empty($analise['vendedor_origem_id']))throw new RuntimeException('O processo já possui origem comercial vinculada.');
        $propostaId=trim($_POST['proposta_id']??'');$servicoId=trim($_POST['servico_id']??'');$vendedorId=trim($_POST['vendedor_origem_id']??'');
        $q=$pdo->prepare("SELECT p.id FROM propostas p WHERE p.id=:proposta AND p.cliente_id=:cliente AND p.status='assinada'");$q->execute([':proposta'=>$propostaId,':cliente'=>$analise['solicitante_id']]);if(!$q->fetchColumn())throw new RuntimeException('A proposta assinada não pertence ao cliente deste processo.');
        $q=$pdo->prepare("SELECT codigo_operacional FROM servicos WHERE id=:servico AND ativo=1");$q->execute([':servico'=>$servicoId]);$codigo=$q->fetchColumn();if(!in_array($codigo,['ANALISE_PLANOS_EC1','ANALISE_PLANOS_EC2'],true))throw new RuntimeException('Selecione um serviço de análise EC1 ou EC2.');
        $q=$pdo->prepare("SELECT id FROM usuarios WHERE id=:id AND ativo=1 AND excluido_em IS NULL AND cargo='VENDEDOR'");$q->execute([':id'=>$vendedorId]);if(!$q->fetchColumn())throw new RuntimeException('Vendedor de origem inválido.');
        $classe=$codigo==='ANALISE_PLANOS_EC1'?'EC1':'EC2';
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE analises_planos SET proposta_id=:proposta,servico_id=:servico,vendedor_origem_id=:vendedor,classe_certificacao=:classe,legado_sem_proposta=0 WHERE id=:id")->execute([':proposta'=>$propostaId,':servico'=>$servicoId,':vendedor'=>$vendedorId,':classe'=>$classe,':id'=>$analiseId]);
        analisePlanosHistorico($pdo,$analiseId,'ORIGEM_COMERCIAL_VINCULADA',$analise['status'],$analise['status'],'Processo legado vinculado manualmente pelo admin.');
        analisePlanosNotificar($pdo,$vendedorId,'ANALISE_LEGADO_VINCULADA','Análise vinculada à sua proposta',$analise['numero'].' foi vinculada pelo admin.',$analiseId,'analises-planos/form?id='.urlencode($analiseId));
        $pdo->commit();setMensagem('success','Origem comercial vinculada. O processo já pode ser agendado.');redirecionar($retorno($analiseId, 'vistoria_tramite'));
    }

    if ($acao === 'agendar') {
        if (in_array($analise['status'], ['CONCLUIDA','REPROVADA','CANCELADA'], true)) throw new RuntimeException('Processo finalizado não pode ser agendado.');
        if (!empty($analise['legado_sem_proposta']) && (empty($analise['proposta_id']) || empty($analise['servico_id']) || empty($analise['vendedor_origem_id']))) {
            throw new RuntimeException('Processo legado: o admin deve vincular proposta, serviço e vendedor de origem antes de agendar.');
        }
        $primeiro = empty($analise['prazo_agendado_em']);
        $vendedorOrigem = $cargo === 'VENDEDOR' && $analise['vendedor_origem_id'] === $usuario;
        $analistaAtual = $cargo === 'ANALISTA' && $analise['analista_id'] === $usuario;
        if ($cargo !== 'ADMIN' && !($primeiro ? $vendedorOrigem : ($vendedorOrigem || $analistaAtual))) throw new RuntimeException('Você não pode alterar esta agenda.');
        $analistaId = trim($_POST['analista_id'] ?? '');
        $prazoInput = trim($_POST['prazo_agendado_em'] ?? '');
        $motivo = trim($_POST['motivo'] ?? '');
        if (!$analistaId || !$prazoInput) throw new InvalidArgumentException('Informe analista e prazo.');
        $prazo = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $prazoInput, new DateTimeZone('America/Sao_Paulo'));
        if (!$prazo || $prazo < new DateTimeImmutable('now', new DateTimeZone('America/Sao_Paulo'))) throw new InvalidArgumentException('O prazo deve ser uma data futura.');
        if (!$primeiro && $motivo === '') throw new InvalidArgumentException('Informe o motivo do reagendamento.');
        $q=$pdo->prepare("SELECT COUNT(*) FROM usuarios u LEFT JOIN usuario_perfis p ON p.usuario_id=u.id WHERE u.id=:id AND u.ativo=1 AND u.excluido_em IS NULL AND (u.cargo='ANALISTA' OR p.perfil='ANALISTA')");
        $q->execute([':id'=>$analistaId]);if(!(int)$q->fetchColumn())throw new InvalidArgumentException('Selecione um analista ativo.');
        if (!empty($analise['iniciado_em']) && $cargo !== 'ADMIN' && $analistaId !== $analise['analista_id']) throw new RuntimeException('Depois do início, somente o admin pode trocar o analista.');
        $pdo->beginTransaction();
        $acaoAgenda=$primeiro?'AGENDAMENTO':($analistaId!==$analise['analista_id']?'REATRIBUICAO':'REAGENDAMENTO');
        $pdo->prepare("UPDATE analises_planos SET analista_id=:analista,prazo_agendado_em=:prazo,status=IF(status='AGUARDANDO_AGENDAMENTO','AGENDADA',status) WHERE id=:id")
            ->execute([':analista'=>$analistaId, ':prazo'=>$prazo->format('Y-m-d H:i:s'), ':id'=>$analiseId]);
        $pdo->prepare("INSERT INTO analise_planos_agenda_historico
            (analise_id,analista_anterior_id,analista_novo_id,prazo_anterior_em,prazo_novo_em,motivo,acao,criado_por)
            VALUES (:analise,:anterior,:novo,:prazo_anterior,:prazo_novo,:motivo,:acao,:usuario)")
            ->execute([':analise'=>$analiseId, ':anterior'=>$analise['analista_id'], ':novo'=>$analistaId,
                ':prazo_anterior'=>$analise['prazo_agendado_em'], ':prazo_novo'=>$prazo->format('Y-m-d H:i:s'),
                ':motivo'=>$motivo?:'Agendamento inicial', ':acao'=>$acaoAgenda, ':usuario'=>$usuario]);
        analisePlanosHistorico($pdo,$analiseId,$acaoAgenda,$analise['status'],$primeiro?'AGENDADA':$analise['status'],$motivo?:'Agendamento inicial.');
        if ($cargo === 'ANALISTA') {
            analisePlanosNotificar($pdo,$analise['vendedor_origem_id'],'ANALISE_REAGENDADA','Análise reagendada pelo analista',$analise['numero'].' tem novo prazo em '.$prazo->format('d/m/Y H:i').'.',$analiseId,'analises-planos/form?id='.urlencode($analiseId));
        } else {
            analisePlanosNotificar($pdo,$analistaId,$primeiro?'ANALISE_AGENDADA':'ANALISE_REAGENDADA',$primeiro?'Nova análise agendada':'Análise reagendada',$analise['numero'].' tem prazo em '.$prazo->format('d/m/Y H:i').'.',$analiseId,'analises-planos/form?id='.urlencode($analiseId));
        }
        $pdo->commit();
        setMensagem('success',$primeiro?'Análise agendada.':'Análise reagendada com histórico preservado.');
        redirecionar($retorno($analiseId, 'vistoria_tramite'));
    }

    if ($acao === 'iniciar') {
        analiseAcaoExigirTecnico($analise);
        if ($analise['status'] !== 'AGENDADA' && !empty($analise['iniciado_em'])) {
            throw new RuntimeException('Esta análise técnica já foi iniciada.');
        }
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE analises_planos SET status='EM_ANALISE',iniciado_em=COALESCE(iniciado_em, NOW()) WHERE id=:id")->execute([':id'=>$analiseId]);
        analisePlanosHistorico($pdo,$analiseId,'ANALISE_INICIADA',$analise['status'],'EM_ANALISE','Analista iniciou os trabalhos técnicos de conferência de planos e documentos.');
        $pdo->commit();
        setMensagem('success','Análise técnica iniciada com sucesso. Você já pode classificar os arquivos e avaliar a matriz normativa.');
        redirecionar($retorno($analiseId, 'enquadramento'));
    }

    if ($acao === 'salvar') {
        analiseAcaoExigirTecnico($analise);
        if (!in_array($analise['status'],['AGENDADA','EM_ANALISE','AGUARDANDO_DOCUMENTOS'],true)) throw new RuntimeException('O enquadramento não pode ser alterado neste estado.');
        $tipo=trim($_POST['tipo_processo']??'');$norma=trim($_POST['enquadramento']??'');
        if(!in_array($tipo,analisePlanosTiposPermitidos(),true)||!in_array($norma,analisePlanosNormasPermitidas(),true))throw new InvalidArgumentException('Selecione LC, LCEC, LA ou LR e uma NORMAM válida.');
        if($tipo==='LCEC'&&($_POST['construcao_concluida']??'')!=='1')throw new InvalidArgumentException('LCEC exige construção concluída.');
        $q=$pdo->prepare('SELECT COUNT(*) FROM analise_planos_itens WHERE analise_id=:id');$q->execute([':id'=>$analiseId]);$temItens=(int)$q->fetchColumn()>0;
        if($temItens&&$analise['tipo_processo']&&($analise['tipo_processo']!==$tipo||$analise['enquadramento']!==$norma))throw new RuntimeException('Não é possível trocar processo ou norma depois da criação do checklist.');
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE analises_planos SET tipo_processo=:tipo,enquadramento=:norma,objeto=:objeto,arqueacao_bruta=:ab,
            numero_passageiros=:passageiros,possui_propulsao=:propulsao,embarcacao_classificada=:classificada,
            tipo_navegacao=:navegacao,construcao_concluida=:concluida,estaleiro=:estaleiro,numero_casco=:casco,
            responsavel_projeto_nome=:responsavel,responsavel_projeto_registro=:registro,art_numero=:art,observacoes=:observacoes
            WHERE id=:id")->execute([
            ':tipo'=>$tipo, ':norma'=>$norma, ':objeto'=>trim($_POST['objeto']??'')?:'Análise de planos',
            ':ab'=>($_POST['arqueacao_bruta']??'')!==''?(float)$_POST['arqueacao_bruta']:null,
            ':passageiros'=>($_POST['numero_passageiros']??'')!==''?(int)$_POST['numero_passageiros']:null,
            ':propulsao'=>($_POST['possui_propulsao']??'')===''?null:(int)$_POST['possui_propulsao'],
            ':classificada'=>($_POST['embarcacao_classificada']??'')===''?null:(int)$_POST['embarcacao_classificada'],
            ':navegacao'=>trim($_POST['tipo_navegacao']??'')?:null, ':concluida'=>($_POST['construcao_concluida']??'')===''?null:(int)$_POST['construcao_concluida'],
            ':estaleiro'=>trim($_POST['estaleiro']??'')?:null, ':casco'=>trim($_POST['numero_casco']??'')?:null,
            ':responsavel'=>trim($_POST['responsavel_projeto_nome']??'')?:null, ':registro'=>trim($_POST['responsavel_projeto_registro']??'')?:null,
            ':art'=>trim($_POST['art_numero']??'')?:null, ':observacoes'=>trim($_POST['observacoes']??'')?:null, ':id'=>$analiseId]);
        analisePlanosSemearChecklist($pdo,$analiseId,$tipo,$norma,(string)$analise['classe_certificacao'],$usuario);
        analisePlanosHistorico($pdo,$analiseId,'ENQUADRAMENTO_ATUALIZADO',$analise['status'],$analise['status'],$tipo.' · '.$norma);
        $pdo->commit();
        setMensagem('success','Enquadramento salvo e checklist normativo preparado.');
        redirecionar($retorno($analiseId, 'enquadramento'));
    }

    if ($acao === 'adicionar_submissao') {
        analiseAcaoExigirTecnico($analise);
        if(!in_array($analise['status'],['AGENDADA','EM_ANALISE','AGUARDANDO_DOCUMENTOS'],true))throw new RuntimeException('O processo não aceita revisão neste estado.');
        $arquivos=$_FILES['arquivos']??null;if(!$arquivos||!is_array($arquivos['name']??null))throw new RuntimeException('Selecione pelo menos um arquivo.');
        $preparados=[];foreach($arquivos['name'] as $i=>$nome){if(($arquivos['error'][$i]??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE)continue;$arq=['name'=>$nome,'type'=>$arquivos['type'][$i]??'','tmp_name'=>$arquivos['tmp_name'][$i]??'','error'=>$arquivos['error'][$i]??UPLOAD_ERR_NO_FILE,'size'=>$arquivos['size'][$i]??0];$preparados[]=[$arq,analisePlanosValidarUpload($arq)];}
        if(!$preparados)throw new RuntimeException('Selecione pelo menos um arquivo válido.');
        $pdo->beginTransaction();$q=$pdo->prepare('SELECT COALESCE(MAX(revisao),0)+1 FROM analise_planos_submissoes WHERE analise_id=:id FOR UPDATE');$q->execute([':id'=>$analiseId]);$rev=(int)$q->fetchColumn();$subId=gerarUUID();
        $pdo->prepare("INSERT INTO analise_planos_submissoes(id,analise_id,revisao,descricao,recebido_em,origem,criado_por)VALUES(:id,:analise,:rev,:descricao,:data,'ANALISTA',:usuario)")->execute([':id'=>$subId,':analise'=>$analiseId,':rev'=>$rev,':descricao'=>trim($_POST['descricao']??'')?:null,':data'=>($_POST['recebido_em']??'')?:date('Y-m-d'),':usuario'=>$usuario]);
        $ins=$pdo->prepare("INSERT INTO analise_planos_arquivos(id,submissao_id,categoria,nome_original,extensao,mime_type,tamanho_bytes,sha256,chave_arquivo,criado_por)VALUES(:id,:sub,:categoria,:nome,:ext,:mime,:tam,:hash,:chave,:usuario)");
        foreach($preparados as [$arquivo,$meta]){$chave=analisePlanosGuardarUpload($arquivo,$analiseId,$meta);$ins->execute([':id'=>gerarUUID(),':sub'=>$subId,':categoria'=>trim($_POST['categoria']??'Outros'),':nome'=>$meta['nome'],':ext'=>$meta['extensao'],':mime'=>$meta['mime'],':tam'=>$meta['tamanho'],':hash'=>$meta['sha256'],':chave'=>$chave,':usuario'=>$usuario]);}
        $pdo->prepare("UPDATE analises_planos SET status='EM_ANALISE' WHERE id=:id")->execute([':id'=>$analiseId]);analisePlanosHistorico($pdo,$analiseId,'REVISAO_RECEBIDA',$analise['status'],'EM_ANALISE','Revisão '.$rev.' com '.count($preparados).' arquivo(s).');$pdo->commit();
        setMensagem('success','Revisão armazenada sem sobrescrever os documentos anteriores.');redirecionar($retorno($analiseId, 'arquivos'));
    }

    if ($acao === 'classificar_arquivo') {
        analiseAcaoExigirTecnico($analise);if(!in_array($analise['status'],['EM_ANALISE','AGUARDANDO_DOCUMENTOS'],true))throw new RuntimeException('Arquivos não podem ser classificados neste estado.');
        $arquivoId=trim($_POST['arquivo_id']??'');$classificacao=trim($_POST['classificacao']??'');$justificativa=trim($_POST['justificativa']??'');$itemId=trim($_POST['item_id']??'')?:null;
        if(!in_array($classificacao,['ACEITO','SUBSTITUIDO','REJEITADO'],true))throw new InvalidArgumentException('Classificação inválida.');
        if(in_array($classificacao,['SUBSTITUIDO','REJEITADO'],true)&&$justificativa==='')throw new InvalidArgumentException('Informe a justificativa para rejeitar ou substituir.');
        $stmt=$pdo->prepare("UPDATE analise_planos_arquivos ar INNER JOIN analise_planos_submissoes s ON s.id=ar.submissao_id SET ar.item_id=:item,ar.classificacao=:classificacao,ar.justificativa_classificacao=:justificativa,ar.classificado_por=:usuario,ar.classificado_em=NOW() WHERE ar.id=:arquivo AND s.analise_id=:analise");
        $stmt->execute([':item'=>$itemId,':classificacao'=>$classificacao,':justificativa'=>$justificativa?:null,':usuario'=>$usuario,':arquivo'=>$arquivoId,':analise'=>$analiseId]);if($stmt->rowCount()!==1)throw new RuntimeException('Arquivo não encontrado ou classificação inalterada.');
        analisePlanosHistorico($pdo,$analiseId,'ARQUIVO_CLASSIFICADO',$analise['status'],$analise['status'],$classificacao.($justificativa?' · '.$justificativa:''));
        setMensagem('success','Arquivo classificado.');redirecionar($retorno($analiseId, 'arquivos'));
    }

    if ($acao === 'aceitar_todos_arquivos') {
        analiseAcaoExigirTecnico($analise);
        if (!in_array($analise['status'], ['EM_ANALISE', 'AGUARDANDO_DOCUMENTOS'], true)) {
            throw new RuntimeException('Arquivos não podem ser alterados neste estado.');
        }
        $subId = trim($_POST['submissao_id'] ?? '');
        if ($subId) {
            $stmt = $pdo->prepare("UPDATE analise_planos_arquivos ar INNER JOIN analise_planos_submissoes s ON s.id=ar.submissao_id SET ar.classificacao='ACEITO', ar.justificativa_classificacao=COALESCE(NULLIF(ar.justificativa_classificacao,''),'Aceito pelo analista.'), ar.classificado_por=:usuario, ar.classificado_em=NOW() WHERE ar.submissao_id=:sub AND s.analise_id=:analise AND ar.classificacao='RECEBIDO'");
            $stmt->execute([':sub' => $subId, ':analise' => $analiseId, ':usuario' => $usuario]);
        } else {
            $stmt = $pdo->prepare("UPDATE analise_planos_arquivos ar INNER JOIN analise_planos_submissoes s ON s.id=ar.submissao_id SET ar.classificacao='ACEITO', ar.justificativa_classificacao=COALESCE(NULLIF(ar.justificativa_classificacao,''),'Aceito pelo analista.'), ar.classificado_por=:usuario, ar.classificado_em=NOW() WHERE s.analise_id=:analise AND ar.classificacao='RECEBIDO'");
            $stmt->execute([':analise' => $analiseId, ':usuario' => $usuario]);
        }
        analisePlanosHistorico($pdo, $analiseId, 'ARQUIVOS_ACEITOS_LOTE', $analise['status'], $analise['status'], 'Arquivos recebidos classificados como Aceitos em lote.');
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'mensagem' => 'Todos os arquivos foram aceitos com sucesso.']);
            exit;
        }
        setMensagem('success', 'Todos os arquivos foram classificados como Aceitos.');
        redirecionar($retorno($analiseId, 'arquivos'));
    }

    if ($acao === 'salvar_itens') {
        analiseAcaoExigirTecnico($analise);if(!in_array($analise['status'],['EM_ANALISE','AGUARDANDO_DOCUMENTOS'],true))throw new RuntimeException('A matriz não pode ser alterada neste estado.');
        $ids=$_POST['item_id']??[];$resultados=$_POST['resultado']??[];$pdo->beginTransaction();$upd=$pdo->prepare('UPDATE analise_planos_itens SET resultado=:resultado,observacao=:observacao WHERE id=:id AND analise_id=:analise');
        foreach($ids as $i=>$itemId){$res=$resultados[$i]??'PENDENTE';if(!in_array($res,['PENDENTE','CONFORME','EXIGENCIA','NAO_APLICA'],true))$res='PENDENTE';$upd->execute([':resultado'=>$res,':observacao'=>trim($_POST['item_observacao'][$i]??'')?:null,':id'=>$itemId,':analise'=>$analiseId]);}
        analisePlanosHistorico($pdo,$analiseId,'MATRIZ_ATUALIZADA',$analise['status'],$analise['status']);$pdo->commit();setMensagem('success','Matriz atualizada.');redirecionar($retorno($analiseId, 'enquadramento'));
    }

    if ($acao === 'inserir_exigencias_lote') {
        analiseAcaoExigirTecnico($analise);
        if (!in_array($analise['status'], ['EM_ANALISE', 'AGUARDANDO_DOCUMENTOS'], true)) {
            throw new RuntimeException('Exigências não podem ser alteradas neste estado.');
        }

        $itensParaInserir = [];
        if (!empty($_POST['itens_json'])) {
            $dec = json_decode($_POST['itens_json'], true);
            if (is_array($dec)) {
                $itensParaInserir = $dec;
            }
        } elseif (!empty($_POST['itens']) && is_array($_POST['itens'])) {
            $itensParaInserir = $_POST['itens'];
        } elseif (!empty($_POST['nova_exigencia'])) {
            $itensParaInserir[] = [
                'categoria' => trim($_POST['nova_exigencia_categoria'] ?? 'GERAL') ?: 'GERAL',
                'descricao' => trim($_POST['nova_exigencia']),
                'referencia_normativa' => trim($_POST['nova_exigencia_referencia'] ?? '') ?: null,
            ];
        }

        if (empty($itensParaInserir)) {
            throw new InvalidArgumentException('Nenhuma exigência técnica foi selecionada ou informada.');
        }

        $qOrdem = $pdo->prepare("SELECT COALESCE(MAX(ordem), 0) FROM analise_planos_exigencias WHERE analise_id = :analise");
        $qOrdem->execute([':analise' => $analiseId]);
        $ordemAtual = (int)$qOrdem->fetchColumn();

        $pdo->beginTransaction();
        $stmtInsert = $pdo->prepare("INSERT INTO analise_planos_exigencias (
            id, analise_id, ordem, descricao, referencia_normativa, categoria, as_impeditivo, status, criado_por
        ) VALUES (
            :id, :analise, :ordem, :descricao, :referencia, :categoria, :as_impeditivo, 'PENDENTE', :usuario
        )");

        $inseridos = [];
        foreach ($itensParaInserir as $it) {
            $desc = trim($it['descricao'] ?? $it['descricao_padrao'] ?? $it['titulo'] ?? '');
            if ($desc === '') continue;

            $cat = trim($it['categoria'] ?? 'GERAL') ?: 'GERAL';
            $ref = trim($it['referencia_normativa'] ?? $it['referencia'] ?? '') ?: null;
            $asVal = (!empty($it['as_impeditivo']) || !empty($it['as']) || !empty($_POST['nova_exigencia_as'])) ? 1 : 0;
            $ordemAtual++;
            $novoId = gerarUUID();

            $stmtInsert->execute([
                ':id' => $novoId,
                ':analise' => $analiseId,
                ':ordem' => $ordemAtual,
                ':descricao' => $desc,
                ':referencia' => $ref,
                ':categoria' => $cat,
                ':as_impeditivo' => $asVal,
                ':usuario' => $usuario,
            ]);

            $inseridos[] = [
                'id' => $novoId,
                'ordem' => $ordemAtual,
                'categoria' => $cat,
                'descricao' => $desc,
                'referencia_normativa' => $ref ?: '',
                'as_impeditivo' => $asVal,
                'status' => 'PENDENTE'
            ];
        }

        if (empty($inseridos)) {
            throw new InvalidArgumentException('Nenhuma exigência válida para inclusão.');
        }

        $totalInseridos = count($inseridos);
        analisePlanosHistorico($pdo, $analiseId, 'EXIGENCIAS_ATUALIZADAS', $analise['status'], $analise['status'], "{$totalInseridos} exigência(s) inserida(s) no processo.");
        $pdo->commit();

        $saldo = analisePlanosSaldoExigencias($pdo, $analiseId);
        $qTotal = $pdo->prepare("SELECT COUNT(*) FROM analise_planos_exigencias WHERE analise_id = :analise");
        $qTotal->execute([':analise' => $analiseId]);
        $totalGeral = (int)$qTotal->fetchColumn();

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'mensagem' => $totalInseridos === 1 
                    ? 'Exigência técnica inserida com sucesso!' 
                    : "{$totalInseridos} exigências inseridas com sucesso!",
                'total_inseridos' => $totalInseridos,
                'total_geral' => $totalGeral,
                'itens' => $inseridos,
                'saldo' => $saldo
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        setMensagem('success', "{$totalInseridos} exigência(s) registrada(s) com sucesso.");
        redirecionar($retorno($analiseId, 'exigencias'));
    }

    if ($acao === 'salvar_exigencias') {
        analiseAcaoExigirTecnico($analise);
        if (!in_array($analise['status'], ['EM_ANALISE', 'AGUARDANDO_DOCUMENTOS'], true)) {
            throw new RuntimeException('Exigências não podem ser alteradas neste estado.');
        }
        $ids = $_POST['exigencia_id'] ?? [];
        $categorias = $_POST['exigencia_categoria'] ?? [];
        $statusList = $_POST['exigencia_status'] ?? [];
        $asList = $_POST['exigencia_as'] ?? [];
        $pdo->beginTransaction();
        $upd = $pdo->prepare('UPDATE analise_planos_exigencias 
            SET ordem=:ordem, descricao=:descricao, referencia_normativa=:referencia, 
                categoria=:categoria, status=:status, as_impeditivo=:as_impeditivo, saneamento_pendente=:saneamento 
            WHERE id=:id AND analise_id=:analise');
        foreach ($ids as $i => $exId) {
            $cat = trim($categorias[$i] ?? '') ?: 'GERAL';
            $st = trim($statusList[$i] ?? '') ?: 'PENDENTE';
            $asItem = !empty($asList[$exId]) || !empty($asList[$i]) ? 1 : 0;
            if (!in_array($st, ['PENDENTE', 'PARCIAL', 'CUMPRIDA', 'NAO_CUMPRIDA'], true)) $st = 'PENDENTE';
            $upd->execute([
                ':ordem' => $i + 1,
                ':descricao' => trim($_POST['exigencia_descricao'][$i] ?? ''),
                ':referencia' => trim($_POST['exigencia_referencia'][$i] ?? '') ?: null,
                ':categoria' => $cat,
                ':status' => $st,
                ':as_impeditivo' => $asItem,
                ':saneamento' => ($st === 'CUMPRIDA' ? 0 : 1),
                ':id' => $exId,
                ':analise' => $analiseId
            ]);
        }
        if (trim($_POST['nova_exigencia'] ?? '') !== '') {
            $novaCat = trim($_POST['nova_exigencia_categoria'] ?? '') ?: 'GERAL';
            $novaAS = !empty($_POST['nova_exigencia_as']) ? 1 : 0;
            $pdo->prepare('INSERT INTO analise_planos_exigencias(id,analise_id,ordem,descricao,referencia_normativa,categoria,as_impeditivo,status,criado_por) VALUES (UUID(),:analise,:ordem,:descricao,:referencia,:categoria,:as_impeditivo,"PENDENTE",:usuario)')->execute([
                ':analise' => $analiseId,
                ':ordem' => count($ids) + 1,
                ':descricao' => trim($_POST['nova_exigencia']),
                ':referencia' => trim($_POST['nova_exigencia_referencia'] ?? '') ?: null,
                ':categoria' => $novaCat,
                ':as_impeditivo' => $novaAS,
                ':usuario' => $usuario
            ]);
        }
        analisePlanosHistorico($pdo, $analiseId, 'EXIGENCIAS_ATUALIZADAS', $analise['status'], $analise['status']);
        $pdo->commit();

        $saldo = analisePlanosSaldoExigencias($pdo, $analiseId);
        $qTotal = $pdo->prepare("SELECT COUNT(*) FROM analise_planos_exigencias WHERE analise_id = :analise");
        $qTotal->execute([':analise' => $analiseId]);
        $totalGeral = (int)$qTotal->fetchColumn();

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'mensagem' => 'Exigências atualizadas com sucesso.',
                'total_geral' => $totalGeral,
                'saldo' => $saldo
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        setMensagem('success', 'Exigências atualizadas.');
        redirecionar($retorno($analiseId, 'exigencias'));
    }

    if ($acao === 'toggle_as_exigencia') {
        analiseAcaoExigirTecnico($analise);
        if (!in_array($analise['status'], ['EM_ANALISE', 'AGUARDANDO_DOCUMENTOS'], true)) {
            throw new RuntimeException('Exigências não podem ser alteradas neste estado.');
        }
        $exId = trim($_POST['exigencia_id'] ?? '');
        $valor = isset($_POST['as_impeditivo']) ? (!empty($_POST['as_impeditivo']) ? 1 : 0) : null;
        
        $pdo->beginTransaction();
        if ($valor === null) {
            $pdo->prepare('UPDATE analise_planos_exigencias SET as_impeditivo = IF(as_impeditivo=1, 0, 1) WHERE id=:id AND analise_id=:analise')
                ->execute([':id' => $exId, ':analise' => $analiseId]);
        } else {
            $pdo->prepare('UPDATE analise_planos_exigencias SET as_impeditivo = :val WHERE id=:id AND analise_id=:analise')
                ->execute([':val' => $valor, ':id' => $exId, ':analise' => $analiseId]);
        }
        
        $stmtAS = $pdo->prepare('SELECT as_impeditivo FROM analise_planos_exigencias WHERE id=:id AND analise_id=:analise');
        $stmtAS->execute([':id' => $exId, ':analise' => $analiseId]);
        $novoAS = (int)$stmtAS->fetchColumn();
        
        analisePlanosHistorico($pdo, $analiseId, 'EXIGENCIA_AS_ALTERADA', $analise['status'], $analise['status'], "Exigência {$exId} condição A/S alterada para " . ($novoAS ? 'SIM' : 'NÃO') . ".");
        $pdo->commit();

        $saldo = analisePlanosSaldoExigencias($pdo, $analiseId);

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'mensagem' => $novoAS ? 'Exigência marcada como A/S (Grave - suspende emissão).' : 'Condição A/S removida da exigência.',
                'exigencia_id' => $exId,
                'as_impeditivo' => $novoAS,
                'saldo' => $saldo
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        setMensagem('success', 'Condição A/S atualizada.');
        redirecionar($retorno($analiseId, 'exigencias'));
    }

    if ($acao === 'baixa_rapida_exigencia') {
        analiseAcaoExigirTecnico($analise);
        if (!in_array($analise['status'], ['EM_ANALISE', 'AGUARDANDO_DOCUMENTOS'], true)) {
            throw new RuntimeException('Exigências não podem ser alteradas neste estado.');
        }
        $exId = trim($_POST['exigencia_id'] ?? '');
        $novoStatus = trim($_POST['status'] ?? 'CUMPRIDA');
        if (!in_array($novoStatus, ['PENDENTE', 'PARCIAL', 'CUMPRIDA', 'NAO_CUMPRIDA'], true)) {
            $novoStatus = 'CUMPRIDA';
        }

        $pdo->beginTransaction();
        $upd = $pdo->prepare('UPDATE analise_planos_exigencias 
            SET status=:status, saneamento_pendente=:saneamento,
                observacao_cumprimento=CONCAT(COALESCE(observacao_cumprimento,""), :nota)
            WHERE id=:id AND analise_id=:analise');
        $upd->execute([
            ':status' => $novoStatus,
            ':saneamento' => ($novoStatus === 'CUMPRIDA' ? 0 : 1),
            ':nota' => "\nAlteração rápida para " . $novoStatus . " por " . $usuario . " em " . date('d/m/Y H:i') . ".",
            ':id' => $exId,
            ':analise' => $analiseId
        ]);
        analisePlanosHistorico($pdo, $analiseId, 'EXIGENCIA_STATUS_ALTERADO', $analise['status'], $analise['status'], "Exigência alterada para {$novoStatus}.");
        $pdo->commit();

        $saldo = analisePlanosSaldoExigencias($pdo, $analiseId);

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'mensagem' => "Exigência marcada como {$novoStatus}.",
                'exigencia_id' => $exId,
                'novo_status' => $novoStatus,
                'saldo' => $saldo
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        setMensagem('success', "Situação da exigência atualizada para {$novoStatus}.");
        redirecionar($retorno($analiseId, 'exigencias'));
    }

    if ($acao === 'excluir_exigencia') {
        analiseAcaoExigirTecnico($analise);if(!in_array($analise['status'],['EM_ANALISE','AGUARDANDO_DOCUMENTOS'],true))throw new RuntimeException('Exigências não podem ser alteradas neste estado.');
        $exId = trim($_POST['exigencia_id'] ?? '');
        $q = $pdo->prepare('SELECT COUNT(*) FROM analise_planos_relatorio_exigencias WHERE exigencia_id=:id');
        $q->execute([':id'=>$exId]);
        if ((int)$q->fetchColumn() > 0) {
            throw new RuntimeException('Esta exigência já consta em relatório emitido e não pode ser excluída fisicamente.');
        }
        $pdo->prepare('DELETE FROM analise_planos_exigencias WHERE id=:id AND analise_id=:analise AND status="PENDENTE"')->execute([':id'=>$exId, ':analise'=>$analiseId]);
        analisePlanosHistorico($pdo,$analiseId,'EXIGENCIA_EXCLUIDA',$analise['status'],$analise['status']);

        $qTotal = $pdo->prepare("SELECT COUNT(*) FROM analise_planos_exigencias WHERE analise_id = :analise");
        $qTotal->execute([':analise' => $analiseId]);
        $totalGeral = (int)$qTotal->fetchColumn();

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'mensagem' => 'Exigência excluída com sucesso.',
                'exigencia_id' => $exId,
                'total_geral' => $totalGeral,
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        setMensagem('success','Exigência excluída.');redirecionar($retorno($analiseId, 'exigencias'));
    }

    if ($acao === 'criar_parecer') {
        analiseAcaoExigirTecnico($analise);
        if (!in_array($analise['status'], ['EM_ANALISE', 'AGUARDANDO_DOCUMENTOS'], true)) {
            throw new RuntimeException('O processo precisa estar em análise.');
        }
        $submissaoId = trim($_POST['submissao_id'] ?? '');
        $resultado = trim($_POST['resultado'] ?? '');
        $resumo = trim($_POST['resumo'] ?? '');
        $conclusao = trim($_POST['conclusao'] ?? '');
        if (!$submissaoId || !in_array($resultado, ['APROVADO', 'EXIGENCIAS', 'REPROVADO'], true) || !$resumo || !$conclusao) {
            throw new InvalidArgumentException('Preencha os campos obrigatórios do relatório técnico.');
        }
        $q = $pdo->prepare('SELECT id FROM analise_planos_submissoes WHERE id=:id AND analise_id=:analise');
        $q->execute([':id' => $submissaoId, ':analise' => $analiseId]);
        if (!$q->fetchColumn()) throw new RuntimeException('Revisão informada não pertence ao processo.');
        // Classifica automaticamente os arquivos recebidos desta revisão como ACEITO
        $pdo->prepare("UPDATE analise_planos_arquivos SET classificacao='ACEITO', justificativa_classificacao=COALESCE(NULLIF(justificativa_classificacao,''),'Aceito na emissão do relatório técnico.'), classificado_por=:usuario, classificado_em=NOW() WHERE submissao_id=:id AND classificacao='RECEBIDO'")->execute([':id' => $submissaoId, ':usuario' => $usuario]);

        $q = $pdo->prepare('SELECT * FROM analise_planos_exigencias WHERE analise_id=:id ORDER BY ordem,id');
        $q->execute([':id' => $analiseId]);
        $exigenciasCiclo = $q->fetchAll(PDO::FETCH_ASSOC);
        if ($resultado === 'EXIGENCIAS' && !$exigenciasCiclo) throw new RuntimeException('Cadastre ao menos uma exigência.');
        $resultadosEx = $_POST['baixa_resultado'] ?? [];
        $manifestacoes = $_POST['baixa_manifestacao'] ?? [];
        foreach ($exigenciasCiclo as $ex) {
            $r = $resultadosEx[$ex['id']] ?? '';
            $m = trim($manifestacoes[$ex['id']] ?? '');
            if (!in_array($r, ['CUMPRIDA', 'PARCIAL', 'NAO_CUMPRIDA'], true) || $m === '') {
                throw new RuntimeException('Informe o resultado e a manifestação técnica de todas as exigências.');
            }
            if ($resultado === 'APROVADO' && $r !== 'CUMPRIDA') {
                throw new RuntimeException('O relatório conclusivo exige baixa integral de todas as exigências.');
            }
        }
        if ($resultado === 'APROVADO') {
            // Em conclusão aprovada, aceita quaisquer arquivos ainda pendentes na análise
            $pdo->prepare("UPDATE analise_planos_arquivos ar INNER JOIN analise_planos_submissoes s ON s.id=ar.submissao_id SET ar.classificacao='ACEITO', ar.justificativa_classificacao=COALESCE(NULLIF(ar.justificativa_classificacao,''),'Aceito na aprovação conclusiva dos planos.'), ar.classificado_por=:usuario, ar.classificado_em=NOW() WHERE s.analise_id=:id AND ar.classificacao='RECEBIDO'")->execute([':id' => $analiseId, ':usuario' => $usuario]);
            // E marca arquivos rejeitados de revisões anteriores como substituídos
            $pdo->prepare("UPDATE analise_planos_arquivos ar INNER JOIN analise_planos_submissoes s ON s.id=ar.submissao_id SET ar.classificacao='SUBSTITUIDO', ar.justificativa_classificacao=CONCAT(COALESCE(ar.justificativa_classificacao,''),' (Substituído na aprovação conclusiva)') WHERE s.analise_id=:id AND ar.classificacao='REJEITADO'")->execute([':id' => $analiseId]);
        }

        $responsavel = analiseAcaoResponsavelDoAnalista($pdo, $analise);
        $pdo->beginTransaction();
        $q = $pdo->prepare('SELECT id FROM analise_planos_pareceres WHERE analise_id=:id AND status NOT IN ("PUBLICADO","DEVOLVIDO","CANCELADO") FOR UPDATE');
        $q->execute([':id' => $analiseId]);
        if ($q->fetchColumn()) throw new RuntimeException('Já existe um relatório aberto neste processo.');
        $q = $pdo->prepare('SELECT id FROM analise_planos_pareceres WHERE analise_id=:id AND status="PUBLICADO" ORDER BY versao DESC LIMIT 1 FOR UPDATE');
        $q->execute([':id' => $analiseId]);
        $anterior = $q->fetchColumn() ?: null;
        $q = $pdo->prepare('SELECT COALESCE(MAX(versao),0)+1 FROM analise_planos_pareceres WHERE analise_id=:id FOR UPDATE');
        $q->execute([':id' => $analiseId]);
        $versao = (int)$q->fetchColumn();
        $numero = gerarNumeroDocumento('RAP-REL','AM-RAP-REL');
        $parecerId = gerarUUID();
        $finalidade = $resultado === 'APROVADO' ? 'CONCLUSIVO' : ($anterior ? 'CUMPRIMENTO_EXIGENCIAS' : 'ANALISE_INICIAL');
        $snapshot = analisePlanosSnapshot($pdo, $analise, $submissaoId);

        $assinarAgora = !empty($_POST['assinar_agora']);
        $statusInicial = $assinarAgora ? 'PUBLICADO' : 'AGUARDANDO_ASSINATURA_ANALISTA';
        $ip = obterIpCliente();

        $pdo->prepare("INSERT INTO analise_planos_pareceres(
            id,numero,analise_id,versao,finalidade,submissao_id,relatorio_anterior_id,
            norma_versao_id,resultado,resumo,conclusao,snapshot_json,status,
            responsavel_assinatura_id,criado_por,assinado_analista_em,assinatura_analista_ip,
            publicado_em,validado_em,validado_por
        ) VALUES (
            :id,:numero,:analise,:versao,:finalidade,:submissao,:anterior,
            :norma,:resultado,:resumo,:conclusao,:snapshot,:status,
            :responsavel,:usuario,:assinado_em,:ip,:publicado_em,:validado_em,:validado_por
        )")->execute([
            ':id' => $parecerId,
            ':numero' => $numero,
            ':analise' => $analiseId,
            ':versao' => $versao,
            ':finalidade' => $finalidade,
            ':submissao' => $submissaoId,
            ':anterior' => $anterior,
            ':norma' => $analise['norma_versao_id'] ?? null,
            ':resultado' => $resultado,
            ':resumo' => $resumo,
            ':conclusao' => $conclusao,
            ':snapshot' => json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ':status' => $statusInicial,
            ':responsavel' => $responsavel['id'],
            ':usuario' => $usuario,
            ':assinado_em' => $assinarAgora ? date('Y-m-d H:i:s') : null,
            ':ip' => $assinarAgora ? $ip : null,
            ':publicado_em' => $assinarAgora ? date('Y-m-d H:i:s') : null,
            ':validado_em' => $assinarAgora ? date('Y-m-d H:i:s') : null,
            ':validado_por' => $assinarAgora ? $usuario : null,
        ]);

        $ins = $pdo->prepare('INSERT INTO analise_planos_relatorio_exigencias(id,relatorio_id,exigencia_id,submissao_id,resultado,manifestacao_tecnica,as_snapshot,descricao_snapshot,referencia_snapshot,criado_por) VALUES (UUID(),:relatorio,:exigencia,:submissao,:resultado,:manifestacao,:as_snapshot,:descricao,:referencia,:usuario)');
        $updEx = $pdo->prepare("UPDATE analise_planos_exigencias SET status=:status, as_impeditivo=:as_impeditivo, saneamento_pendente=:saneamento, observacao_cumprimento=CONCAT(COALESCE(observacao_cumprimento,''), :nota) WHERE id=:id AND analise_id=:analise");
        $baixaAS = $_POST['baixa_as'] ?? [];
        $baixaASSubmetido = !empty($_POST['baixa_as_submetido']);
        foreach ($exigenciasCiclo as $ex) {
            $rEx = $resultadosEx[$ex['id']];
            $mEx = trim($manifestacoes[$ex['id']]);
            if ($baixaASSubmetido) {
                $asEx = !empty($baixaAS[$ex['id']]) ? 1 : 0;
            } else {
                $asEx = isset($baixaAS[$ex['id']]) ? (!empty($baixaAS[$ex['id']]) ? 1 : 0) : (int)($ex['as_impeditivo'] ?? 0);
            }
            $ins->execute([
                ':relatorio' => $parecerId,
                ':exigencia' => $ex['id'],
                ':submissao' => $submissaoId,
                ':resultado' => $rEx,
                ':manifestacao' => $mEx,
                ':as_snapshot' => $asEx,
                ':descricao' => $ex['descricao'],
                ':referencia' => $ex['referencia_normativa'],
                ':usuario' => $usuario
            ]);
            $updEx->execute([
                ':status' => $rEx,
                ':as_impeditivo' => $asEx,
                ':saneamento' => ($rEx === 'CUMPRIDA' ? 0 : 1),
                ':nota' => "\nBaixa no relatório " . $numero . " (" . $rEx . ($asEx ? ' - A/S' : '') . "): " . $mEx,
                ':id' => $ex['id'],
                ':analise' => $analiseId
            ]);
        }

        // Inserção de novas exigências adicionadas diretamente neste ciclo de RAP
        $novasEx = [];
        if (!empty($_POST['novas_exigencias']) && is_array($_POST['novas_exigencias'])) {
            $novasEx = $_POST['novas_exigencias'];
        } elseif (!empty($_POST['novo_item_descricao'])) {
            $novasEx[] = [
                'descricao' => trim($_POST['novo_item_descricao']),
                'categoria' => trim($_POST['novo_item_categoria'] ?? 'GERAL') ?: 'GERAL',
                'referencia' => trim($_POST['novo_item_referencia'] ?? '') ?: null,
                'as' => !empty($_POST['novo_item_as']) ? 1 : 0,
                'resultado' => trim($_POST['novo_item_resultado'] ?? 'NAO_CUMPRIDA'),
                'manifestacao' => trim($_POST['novo_item_manifestacao'] ?? 'Apontada neste ciclo.'),
            ];
        }
        foreach ($novasEx as $nx) {
            $descNx = trim($nx['descricao'] ?? '');
            if ($descNx === '') continue;
            $catNx = trim($nx['categoria'] ?? 'GERAL') ?: 'GERAL';
            $refNx = trim($nx['referencia'] ?? $nx['referencia_normativa'] ?? '') ?: null;
            $asNx = (!empty($nx['as']) || !empty($nx['as_impeditivo'])) ? 1 : 0;
            $rNx = in_array($nx['resultado'] ?? '', ['CUMPRIDA','PARCIAL','NAO_CUMPRIDA'], true) ? $nx['resultado'] : 'NAO_CUMPRIDA';
            $mNx = trim($nx['manifestacao'] ?? '') ?: 'Nova exigência registrada na conferência documental deste ciclo.';
            $novoId = gerarUUID();
            $qMaxOrd = $pdo->prepare("SELECT COALESCE(MAX(ordem),0)+1 FROM analise_planos_exigencias WHERE analise_id=:analise");
            $qMaxOrd->execute([':analise' => $analiseId]);
            $proxOrd = (int)$qMaxOrd->fetchColumn();

            $pdo->prepare("INSERT INTO analise_planos_exigencias (id, analise_id, ordem, categoria, descricao, referencia_normativa, as_impeditivo, status, saneamento_pendente, observacao_cumprimento, criado_por) VALUES (:id, :analise, :ordem, :categoria, :descricao, :referencia, :as, :status, :saneamento, :obs, :usuario)")
                ->execute([
                    ':id' => $novoId, ':analise' => $analiseId, ':ordem' => $proxOrd, ':categoria' => $catNx,
                    ':descricao' => $descNx, ':referencia' => $refNx, ':as' => $asNx, ':status' => $rNx,
                    ':saneamento' => ($rNx === 'CUMPRIDA' ? 0 : 1),
                    ':obs' => "\nRegistrada no relatório {$numero}: {$mNx}", ':usuario' => $usuario
                ]);

            $ins->execute([
                ':relatorio' => $parecerId, ':exigencia' => $novoId, ':submissao' => $submissaoId,
                ':resultado' => $rNx, ':manifestacao' => $mNx, ':as_snapshot' => $asNx,
                ':descricao' => $descNx, ':referencia' => $refNx, ':usuario' => $usuario
            ]);
        }

        if ($resultado === 'APROVADO') {
            $pdo->prepare("UPDATE analise_planos_itens SET resultado='CONFORME' WHERE analise_id=:id AND resultado NOT IN ('CONFORME','NAO_APLICA')")->execute([':id' => $analiseId]);
        }

        if ($assinarAgora) {
            $parecerDados = [
                'id' => $parecerId,
                'numero' => $numero,
                'resultado' => $resultado,
                'finalidade' => $finalidade
            ];
            $novoStatus = analiseAcaoFinalizarParecer($pdo, $analise, $parecerDados, $responsavel, $usuario);
            $pdo->commit();
            setMensagem('success', $resultado === 'APROVADO' ? "Relatório {$numero} assinado e finalizado! Minuta da Licença gerada." : "Relatório {$numero} assinado e finalizado com sucesso.");
        } else {
            $pdo->prepare("UPDATE analises_planos SET status='AGUARDANDO_ASSINATURA_ANALISTA',responsavel_assinatura_id=:responsavel WHERE id=:id")
                ->execute([':responsavel' => $responsavel['id'], ':id' => $analiseId]);
            analisePlanosHistorico($pdo, $analiseId, 'RELATORIO_CICLO_PREPARADO', $analise['status'], 'AGUARDANDO_ASSINATURA_ANALISTA', $numero . ' preparado.');
            analisePlanosAuditarNorma($pdo, $analiseId, 'RELATORIO_CICLO_PREPARADO', $analise['status'], 'AGUARDANDO_ASSINATURA_ANALISTA', $numero);
            $pdo->commit();
            setMensagem('success', "Relatório {$numero} preparado. Clique em 'Assinar e Finalizar Documento' para concluir.");
        }
        redirecionar($retorno($analiseId, 'pareceres'));
    }

    if ($acao === 'assinar_parecer') {
        if (!in_array($cargo, ['ANALISTA', 'ADMIN'], true) || ($cargo === 'ANALISTA' && $analise['analista_id'] !== $usuario)) {
            throw new RuntimeException('Somente o analista atribuído pode assinar o relatório.');
        }
        $parecerId = trim($_POST['parecer_id'] ?? '');
        $responsavel = analiseAcaoResponsavelDoAnalista($pdo, $analise);

        $pdo->beginTransaction();
        $q = $pdo->prepare("SELECT * FROM analise_planos_pareceres WHERE id=:id AND analise_id=:analise AND status='AGUARDANDO_ASSINATURA_ANALISTA' FOR UPDATE");
        $q->execute([':id' => $parecerId, ':analise' => $analiseId]);
        $parecer = $q->fetch(PDO::FETCH_ASSOC);
        if (!$parecer) throw new RuntimeException('Parecer não está disponível para assinatura.');

        $novoStatus = analiseAcaoFinalizarParecer($pdo, $analise, $parecer, $responsavel, $usuario);
        $pdo->commit();

        setMensagem('success', $parecer['resultado'] === 'APROVADO' ? "Relatório {$parecer['numero']} assinado e finalizado! Minuta da Licença gerada." : "Relatório {$parecer['numero']} assinado e finalizado com sucesso.");
        redirecionar($retorno($analiseId, 'pareceres'));
    }

    if ($acao === 'publicar') {
        if ($cargo !== 'ADMIN') throw new RuntimeException('Somente o admin pode publicar ou devolver.');
        $pdo->beginTransaction();
        $parecerId = trim($_POST['parecer_id'] ?? '');
        $stmt = $pdo->prepare("SELECT * FROM analise_planos_pareceres WHERE id=:id AND analise_id=:analise AND status IN ('AGUARDANDO_APROVACAO_ADMIN','AGUARDANDO_ASSINATURA_ANALISTA') FOR UPDATE");
        $stmt->execute([':id' => $parecerId, ':analise' => $analiseId]);
        $parecer = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$parecer) throw new RuntimeException('Parecer não encontrado para validação.');

        if (!empty($_POST['devolver'])) {
            $motivo = trim($_POST['motivo'] ?? '');
            if ($motivo === '') throw new InvalidArgumentException('Informe o motivo da devolução.');
            $pdo->prepare("UPDATE analise_planos_pareceres SET status='DEVOLVIDO',devolvido_motivo=:motivo WHERE id=:id")->execute([':motivo' => $motivo, ':id' => $parecerId]);
            $pdo->prepare("UPDATE analises_planos SET status='EM_ANALISE' WHERE id=:id")->execute([':id' => $analiseId]);
            analisePlanosHistorico($pdo, $analiseId, 'RELATORIO_DEVOLVIDO', $analise['status'], 'EM_ANALISE', $motivo);
            analisePlanosAuditarNorma($pdo, $analiseId, 'RELATORIO_DEVOLVIDO', $analise['status'], 'EM_ANALISE', $motivo);
            analisePlanosNotificar($pdo, $analise['analista_id'], 'PARECER_DEVOLVIDO', 'Relatório devolvido pelo admin', $motivo, $analiseId, 'analises-planos/form?id=' . urlencode($analiseId));
            $pdo->commit();
            setMensagem('success', 'Relatório devolvido ao analista.');
            redirecionar($retorno($analiseId, 'pareceres'));
        }

        $responsavel = analiseAcaoResponsavelDoAnalista($pdo, $analise);
        $novoStatus = analiseAcaoFinalizarParecer($pdo, $analise, $parecer, $responsavel, $usuario);
        $pdo->commit();

        setMensagem('success', $parecer['resultado'] === 'APROVADO' ? 'Relatório conclusivo publicado e minuta da licença criada.' : 'Relatório publicado com sucesso.');
        redirecionar($retorno($analiseId, 'pareceres'));
    }

    if ($acao === 'emitir_licenca') {
        if (!in_array($cargo, ['ANALISTA', 'ADMIN'], true) || ($cargo === 'ANALISTA' && $analise['analista_id'] !== $usuario)) {
            throw new RuntimeException('Somente o analista naval atribuído ao processo pode emitir a licença oficial.');
        }

        // Validação de ao menos um relatório técnico (RAP) publicado
        $stmtPar = $pdo->prepare("SELECT id, numero, finalidade, resultado, status FROM analise_planos_pareceres
            WHERE analise_id = :id AND status = 'PUBLICADO'
            ORDER BY versao DESC LIMIT 1");
        $stmtPar->execute([':id' => $analiseId]);
        $parecerPublicado = $stmtPar->fetch(PDO::FETCH_ASSOC);

        if (!$parecerPublicado && $analise['status'] !== 'CONCLUIDA') {
            throw new RuntimeException('A emissão da licença exige que ao menos uma versão do relatório técnico (RAP) esteja emitida e publicada.');
        }

        // Validação estrita de zero exigências do tipo A/S pendentes
        $stmtExAS = $pdo->prepare("SELECT COUNT(*) FROM analise_planos_exigencias 
            WHERE analise_id = :id AND as_impeditivo = 1 AND (status <> 'CUMPRIDA' OR saneamento_pendente = 1)");
        $stmtExAS->execute([':id' => $analiseId]);
        $pendenciasAS = (int)$stmtExAS->fetchColumn();
        if ($pendenciasAS > 0) {
            throw new RuntimeException("Emissão bloqueada: o processo possui {$pendenciasAS} exigência(s) com condição grave A/S pendente(s). Conforme a regra naval, exigências A/S suspendem a emissão da licença até seu cumprimento integral.");
        }

        $pdo->beginTransaction();
        $responsavel = analiseAcaoResponsavelDoAnalista($pdo, $analise);
        $licencaId = analiseAcaoCriarLicenca($pdo, $analise, $responsavel);

        $stmtPendGeral = $pdo->prepare("SELECT COUNT(*) FROM analise_planos_exigencias WHERE analise_id = :id AND (status <> 'CUMPRIDA' OR saneamento_pendente = 1)");
        $stmtPendGeral->execute([':id' => $analiseId]);
        $totalRestante = (int)$stmtPendGeral->fetchColumn();

        if ($totalRestante === 0 && $analise['status'] !== 'CONCLUIDA') {
            $pdo->prepare("UPDATE analises_planos SET status = 'CONCLUIDA' WHERE id = :id")->execute([':id' => $analiseId]);
            analisePlanosHistorico($pdo, $analiseId, 'PROCESSO_CONCLUIDO_LICENCA', $analise['status'], 'CONCLUIDA', 'Licença oficial emitida e vinculada ao RAP conclusivo.');
        } else {
            analisePlanosHistorico($pdo, $analiseId, 'LICENCA_EMITIDA_CONDICIONAL', $analise['status'], $analise['status'], "Licença oficial emitida com {$totalRestante} exigência(s) regular(es) em acompanhamento.");
        }

        $pdo->commit();

        setMensagem('success', "Licença Oficial ({$analise['tipo_processo']}) gerada e vinculada com sucesso ao RAP {$analise['numero']}!");
        redirecionar(APP_URL . 'documentacao/lc/form?id=' . urlencode($licencaId));
    }

    throw new RuntimeException('Ação inválida.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Erro em Análise de Planos: '.$e->getMessage());
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
    setMensagem('error',$e->getMessage());
    redirecionar($retorno($analiseId));
}
