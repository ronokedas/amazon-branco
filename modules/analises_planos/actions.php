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

$acao = trim($_POST['action'] ?? '');
$analiseId = trim($_POST['analise_id'] ?? $_POST['id'] ?? '');
$retorno = fn(string $id = '') => APP_URL . ($id ? 'analises-planos/form?id=' . urlencode($id) : 'analises-planos');

function analiseAcaoExigirTecnico(array $analise): void
{
    $cargo = getCargo();
    $usuario = (string)($_SESSION['usuario_id'] ?? '');
    if (!($cargo === 'ANALISTA' && $analise['analista_id'] === $usuario)) {
        throw new RuntimeException('Somente o analista atribuído pode executar esta ação técnica.');
    }
    if (analisePlanosEhLegadoForaEscopo($analise)) {
        throw new RuntimeException('Processo NORMAM-201 legado: conteúdo preservado somente para consulta.');
    }
}

function analiseAcaoResponsavelDoAnalista(PDO $pdo, array $analise): array
{
    $stmt = $pdo->prepare("SELECT ra.* FROM responsaveis_assinatura ra
        WHERE ra.usuario_id=:usuario AND ra.ativo=1
          AND ra.cpf_cnpj IS NOT NULL AND ra.cpf_cnpj<>''
          AND ra.assinatura_arquivo IS NOT NULL AND ra.assinatura_arquivo<>''
          AND ra.assinatura_hash IS NOT NULL AND ra.assinatura_hash<>''
        LIMIT 1");
    $stmt->execute([':usuario' => $analise['analista_id']]);
    $responsavel = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$responsavel) throw new RuntimeException('O analista precisa ter identidade técnica e assinatura válidas vinculadas à própria conta.');
    return $responsavel;
}

function analiseAcaoCriarLicenca(PDO $pdo, array $analise, array $responsavel): string
{
    analisePlanosExigirNormam202($analise);
    analisePlanosValidarConclusao($pdo, (string)$analise['id']);
    $aplicabilidade = analisePlanosAvaliarAplicabilidade($analise);
    if (!$aplicabilidade['permitido']) {
        throw new RuntimeException('Licença bloqueada: ' . $aplicabilidade['fundamento']);
    }
    $ultimo = $pdo->prepare("SELECT numero FROM analise_planos_pareceres
        WHERE analise_id=:id AND finalidade='CONCLUSIVO' AND resultado='APROVADO'
          AND status='PUBLICADO' ORDER BY versao DESC LIMIT 1");
    $ultimo->execute([':id'=>$analise['id']]);
    $relatorioConclusivo = $ultimo->fetchColumn();
    if (!$relatorioConclusivo) {
        throw new RuntimeException('A licença exige o último relatório conclusivo validado.');
    }
    $existente = $pdo->prepare('SELECT id FROM certificados_lc WHERE analise_id=:id LIMIT 1 FOR UPDATE');
    $existente->execute([':id' => $analise['id']]);
    $idExistente = $existente->fetchColumn();
    if ($idExistente) return (string)$idExistente;

    $tipo = (string)$analise['tipo_processo'];
    $tipoSequencial = $tipo === 'LCEC' ? 'EC' : $tipo;
    $numero = gerarNumeroDocumento($tipoSequencial, $tipo === 'LCEC' ? 'AM-EC' : 'AM-' . $tipo);
    $id = gerarUUID();
    $stmt = $pdo->prepare("SELECT e.*,c.nome proprietario_nome,c.cpf_cnpj proprietario_documento,c.endereco proprietario_endereco
        FROM embarcacoes e LEFT JOIN clientes c ON c.id=:cliente WHERE e.id=:embarcacao LIMIT 1");
    $stmt->execute([':cliente'=>$analise['solicitante_id'], ':embarcacao'=>$analise['embarcacao_id']]);
    $dados = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $insert = $pdo->prepare("INSERT INTO certificados_lc
        (id,numero_lc,embarcacao_id,token_assinatura,tipo_licenca,nome_embarcacao,tipo_embarcacao,
         numero_casco,material_casco,porte_bruto,numero_passageiros,tipo_navegacao,propulsao,
         proprietario_nome,proprietario_cpf_cnpj,proprietario_endereco,estaleiro_nome,
         data_emissao,local_emissao,relatorio_numero,responsavel_assinatura_id,status,ativo,criado_por,
         vistoria_id,analise_id,dados_json)
        VALUES (:id,:numero,:embarcacao,:token,:tipo,:nome,:tipo_embarcacao,:casco,:material,:porte,
                :passageiros,:navegacao,:propulsao,:proprietario,:documento,:endereco,:estaleiro,
                CURDATE(),'Belém-PA',:relatorio,:responsavel,'emitido',1,:usuario,NULL,:analise,:dados)");
    $insert->execute([
        ':id'=>$id, ':numero'=>$numero, ':embarcacao'=>$analise['embarcacao_id'],
        ':token'=>bin2hex(random_bytes(32)), ':tipo'=>$tipo,
        ':nome'=>$dados['nome'] ?? $analise['embarcacao_nome'], ':tipo_embarcacao'=>$dados['tipo'] ?? null,
        ':casco'=>$analise['numero_casco'] ?: ($dados['numero_casco'] ?? null),
        ':material'=>$dados['material_casco'] ?? null, ':porte'=>$dados['porte_bruto'] ?? null,
        ':passageiros'=>$analise['numero_passageiros'], ':navegacao'=>$analise['tipo_navegacao'],
        ':propulsao'=>$analise['possui_propulsao'] === null ? null : ((int)$analise['possui_propulsao'] ? 'Com propulsão' : 'Sem propulsão'),
        ':proprietario'=>$dados['proprietario_nome'] ?? null, ':documento'=>$dados['proprietario_documento'] ?? null,
        ':endereco'=>$dados['proprietario_endereco'] ?? null, ':estaleiro'=>$analise['estaleiro'],
        ':relatorio'=>$analise['numero'].' + '.$relatorioConclusivo, ':responsavel'=>$responsavel['id'],
        ':usuario'=>$_SESSION['usuario_id'], ':analise'=>$analise['id'],
        ':dados'=>json_encode(['normam'=>$analise['enquadramento'],'classe'=>$analise['classe_certificacao']], JSON_UNESCAPED_UNICODE),
    ]);
    return $id;
}

function analiseAcaoPersistirParecerPdf(PDO $pdo, string $parecerId, string $analiseId): array
{
    $ano = date('Y');
    $relativo = 'storage/documentos_aprovados/' . $ano . '/parecer_planos/' . $parecerId . '.pdf';
    $absoluto = __DIR__ . '/../../' . $relativo;
    if (!is_dir(dirname($absoluto)) && !mkdir(dirname($absoluto), 0750, true) && !is_dir(dirname($absoluto))) {
        throw new RuntimeException('Não foi possível preparar o armazenamento do parecer.');
    }
    $oldGet = $_GET;
    $_GET = ['id' => $parecerId];
    $salvar_pdf_caminho = $absoluto;
    ob_start();
    require __DIR__ . '/parecer_pdf.php';
    ob_end_clean();
    $_GET = $oldGet;
    if (!is_file($absoluto) || filesize($absoluto) < 200) throw new RuntimeException('Não foi possível gerar o PDF definitivo do parecer.');
    $hash = hash_file('sha256', $absoluto);
    $pdo->prepare('UPDATE analise_planos_pareceres SET caminho_pdf_final=:caminho,hash_pdf_final=:hash WHERE id=:id AND analise_id=:analise')
        ->execute([':caminho'=>$relativo, ':hash'=>$hash, ':id'=>$parecerId, ':analise'=>$analiseId]);
    return [$relativo, $hash];
}

try {
    if ($analiseId === '') throw new RuntimeException('Análise não informada.');
    $analise = analisePlanosCarregar($pdo, $analiseId, in_array($acao, ['agendar','iniciar','assinar_parecer','publicar'], true));
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
        $pdo->commit();setMensagem('success','Origem comercial vinculada. O processo já pode ser agendado.');redirecionar($retorno($analiseId));
    }

    if ($acao === 'agendar') {
        if (in_array($analise['status'], ['CONCLUIDA','REPROVADA','CANCELADA'], true)) throw new RuntimeException('Processo finalizado não pode ser agendado.');
        if (empty($analise['proposta_id']) || empty($analise['servico_id']) || empty($analise['vendedor_origem_id'])) {
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
        redirecionar($retorno($analiseId));
    }

    if ($acao === 'iniciar') {
        analiseAcaoExigirTecnico($analise);
        if ($analise['status'] !== 'AGENDADA') throw new RuntimeException('Somente uma análise agendada pode ser iniciada.');
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE analises_planos SET status='EM_ANALISE',iniciado_em=NOW() WHERE id=:id")->execute([':id'=>$analiseId]);
        analisePlanosHistorico($pdo,$analiseId,'ANALISE_INICIADA','AGENDADA','EM_ANALISE');
        $pdo->commit();
        setMensagem('success','Análise técnica iniciada.');
        redirecionar($retorno($analiseId));
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
        redirecionar($retorno($analiseId));
    }

    if ($acao === 'adicionar_submissao') {
        analiseAcaoExigirTecnico($analise);
        if(!in_array($analise['status'],['EM_ANALISE','AGUARDANDO_DOCUMENTOS'],true))throw new RuntimeException('O processo não aceita revisão neste estado.');
        $arquivos=$_FILES['arquivos']??null;if(!$arquivos||!is_array($arquivos['name']??null))throw new RuntimeException('Selecione pelo menos um arquivo.');
        $preparados=[];foreach($arquivos['name'] as $i=>$nome){if(($arquivos['error'][$i]??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE)continue;$arq=['name'=>$nome,'type'=>$arquivos['type'][$i]??'','tmp_name'=>$arquivos['tmp_name'][$i]??'','error'=>$arquivos['error'][$i]??UPLOAD_ERR_NO_FILE,'size'=>$arquivos['size'][$i]??0];$preparados[]=[$arq,analisePlanosValidarUpload($arq)];}
        if(!$preparados)throw new RuntimeException('Selecione pelo menos um arquivo válido.');
        $pdo->beginTransaction();$q=$pdo->prepare('SELECT COALESCE(MAX(revisao),0)+1 FROM analise_planos_submissoes WHERE analise_id=:id FOR UPDATE');$q->execute([':id'=>$analiseId]);$rev=(int)$q->fetchColumn();$subId=gerarUUID();
        $pdo->prepare("INSERT INTO analise_planos_submissoes(id,analise_id,revisao,descricao,recebido_em,origem,criado_por)VALUES(:id,:analise,:rev,:descricao,:data,'ANALISTA',:usuario)")->execute([':id'=>$subId,':analise'=>$analiseId,':rev'=>$rev,':descricao'=>trim($_POST['descricao']??'')?:null,':data'=>($_POST['recebido_em']??'')?:date('Y-m-d'),':usuario'=>$usuario]);
        $ins=$pdo->prepare("INSERT INTO analise_planos_arquivos(id,submissao_id,categoria,nome_original,extensao,mime_type,tamanho_bytes,sha256,chave_arquivo,criado_por)VALUES(:id,:sub,:categoria,:nome,:ext,:mime,:tam,:hash,:chave,:usuario)");
        foreach($preparados as [$arquivo,$meta]){$chave=analisePlanosGuardarUpload($arquivo,$analiseId,$meta);$ins->execute([':id'=>gerarUUID(),':sub'=>$subId,':categoria'=>trim($_POST['categoria']??'Outros'),':nome'=>$meta['nome'],':ext'=>$meta['extensao'],':mime'=>$meta['mime'],':tam'=>$meta['tamanho'],':hash'=>$meta['sha256'],':chave'=>$chave,':usuario'=>$usuario]);}
        $pdo->prepare("UPDATE analises_planos SET status='EM_ANALISE' WHERE id=:id")->execute([':id'=>$analiseId]);analisePlanosHistorico($pdo,$analiseId,'REVISAO_RECEBIDA',$analise['status'],'EM_ANALISE','Revisão '.$rev.' com '.count($preparados).' arquivo(s).');$pdo->commit();
        setMensagem('success','Revisão armazenada sem sobrescrever os documentos anteriores.');redirecionar($retorno($analiseId));
    }

    if ($acao === 'classificar_arquivo') {
        analiseAcaoExigirTecnico($analise);if(!in_array($analise['status'],['EM_ANALISE','AGUARDANDO_DOCUMENTOS'],true))throw new RuntimeException('Arquivos não podem ser classificados neste estado.');
        $arquivoId=trim($_POST['arquivo_id']??'');$classificacao=trim($_POST['classificacao']??'');$justificativa=trim($_POST['justificativa']??'');$itemId=trim($_POST['item_id']??'')?:null;
        if(!in_array($classificacao,['ACEITO','SUBSTITUIDO','REJEITADO'],true))throw new InvalidArgumentException('Classificação inválida.');
        if(in_array($classificacao,['SUBSTITUIDO','REJEITADO'],true)&&$justificativa==='')throw new InvalidArgumentException('Informe a justificativa para rejeitar ou substituir.');
        $stmt=$pdo->prepare("UPDATE analise_planos_arquivos ar INNER JOIN analise_planos_submissoes s ON s.id=ar.submissao_id SET ar.item_id=:item,ar.classificacao=:classificacao,ar.justificativa_classificacao=:justificativa,ar.classificado_por=:usuario,ar.classificado_em=NOW() WHERE ar.id=:arquivo AND s.analise_id=:analise");
        $stmt->execute([':item'=>$itemId,':classificacao'=>$classificacao,':justificativa'=>$justificativa?:null,':usuario'=>$usuario,':arquivo'=>$arquivoId,':analise'=>$analiseId]);if($stmt->rowCount()!==1)throw new RuntimeException('Arquivo não encontrado ou classificação inalterada.');
        analisePlanosHistorico($pdo,$analiseId,'ARQUIVO_CLASSIFICADO',$analise['status'],$analise['status'],$classificacao.($justificativa?' · '.$justificativa:''));
        setMensagem('success','Arquivo classificado.');redirecionar($retorno($analiseId));
    }

    if ($acao === 'salvar_itens') {
        analiseAcaoExigirTecnico($analise);if(!in_array($analise['status'],['EM_ANALISE','AGUARDANDO_DOCUMENTOS'],true))throw new RuntimeException('A matriz não pode ser alterada neste estado.');
        $ids=$_POST['item_id']??[];$resultados=$_POST['resultado']??[];$pdo->beginTransaction();$upd=$pdo->prepare('UPDATE analise_planos_itens SET resultado=:resultado,observacao=:observacao WHERE id=:id AND analise_id=:analise');
        foreach($ids as $i=>$itemId){$res=$resultados[$i]??'PENDENTE';if(!in_array($res,['PENDENTE','CONFORME','EXIGENCIA','NAO_APLICA'],true))$res='PENDENTE';$upd->execute([':resultado'=>$res,':observacao'=>trim($_POST['item_observacao'][$i]??'')?:null,':id'=>$itemId,':analise'=>$analiseId]);}
        analisePlanosHistorico($pdo,$analiseId,'MATRIZ_ATUALIZADA',$analise['status'],$analise['status']);$pdo->commit();setMensagem('success','Matriz atualizada.');redirecionar($retorno($analiseId));
    }

    if ($acao === 'salvar_exigencias') {
        analiseAcaoExigirTecnico($analise);if(!in_array($analise['status'],['EM_ANALISE','AGUARDANDO_DOCUMENTOS'],true))throw new RuntimeException('Exigências não podem ser alteradas neste estado.');
        $ids=$_POST['exigencia_id']??[];$pdo->beginTransaction();$upd=$pdo->prepare('UPDATE analise_planos_exigencias SET ordem=:ordem,descricao=:descricao,referencia_normativa=:referencia WHERE id=:id AND analise_id=:analise AND status<>"CUMPRIDA"');
        foreach($ids as $i=>$exId){$upd->execute([':ordem'=>$i+1,':descricao'=>trim($_POST['exigencia_descricao'][$i]??''),':referencia'=>trim($_POST['exigencia_referencia'][$i]??'')?:null,':id'=>$exId,':analise'=>$analiseId]);}
        if(trim($_POST['nova_exigencia']??'')!==''){$pdo->prepare('INSERT INTO analise_planos_exigencias(id,analise_id,ordem,descricao,referencia_normativa,status,criado_por)VALUES(UUID(),:analise,:ordem,:descricao,:referencia,"PENDENTE",:usuario)')->execute([':analise'=>$analiseId,':ordem'=>count($ids)+1,':descricao'=>trim($_POST['nova_exigencia']),':referencia'=>trim($_POST['nova_exigencia_referencia']??'')?:null,':usuario'=>$usuario]);}
        analisePlanosHistorico($pdo,$analiseId,'EXIGENCIAS_ATUALIZADAS',$analise['status'],$analise['status']);$pdo->commit();setMensagem('success','Exigências atualizadas.');redirecionar($retorno($analiseId));
    }

    if ($acao === 'criar_parecer') {
        analiseAcaoExigirTecnico($analise);if(!in_array($analise['status'],['EM_ANALISE','AGUARDANDO_DOCUMENTOS'],true))throw new RuntimeException('O processo precisa estar em análise.');
        $resultado=trim($_POST['resultado']??'');if(!in_array($resultado,['EXIGENCIAS','APROVADO','REPROVADO'],true))throw new InvalidArgumentException('Resultado inválido. Não é permitida conclusão com exigências.');
        if(!$analise['tipo_processo']||!$analise['enquadramento'])throw new RuntimeException('Conclua o enquadramento antes do parecer.');
        $resumo=trim($_POST['resumo']??'');$conclusao=trim($_POST['conclusao']??'');if(!$resumo||!$conclusao)throw new InvalidArgumentException('Informe resumo e conclusão.');
        $submissaoId=trim($_POST['submissao_id']??'');if($submissaoId==='')throw new InvalidArgumentException('Selecione a revisão documental analisada neste ciclo.');
        $q=$pdo->prepare('SELECT id FROM analise_planos_submissoes WHERE id=:submissao AND analise_id=:analise');$q->execute([':submissao'=>$submissaoId,':analise'=>$analiseId]);if(!$q->fetchColumn())throw new RuntimeException('A revisão selecionada não pertence a este processo.');
        $q=$pdo->prepare("SELECT COUNT(*) FROM analise_planos_arquivos WHERE submissao_id=:id AND classificacao='RECEBIDO'");$q->execute([':id'=>$submissaoId]);if((int)$q->fetchColumn()>0)throw new RuntimeException('Classifique todos os arquivos da revisão antes de emitir o relatório.');
        $q=$pdo->prepare('SELECT * FROM analise_planos_exigencias WHERE analise_id=:id ORDER BY ordem,id');$q->execute([':id'=>$analiseId]);$exigenciasCiclo=$q->fetchAll(PDO::FETCH_ASSOC);
        if($resultado==='EXIGENCIAS'&&!$exigenciasCiclo)throw new RuntimeException('Cadastre ao menos uma exigência.');
        $resultadosEx=$_POST['baixa_resultado']??[];$manifestacoes=$_POST['baixa_manifestacao']??[];
        foreach($exigenciasCiclo as $ex){$r=$resultadosEx[$ex['id']]??'';$m=trim($manifestacoes[$ex['id']]??'');if(!in_array($r,['CUMPRIDA','PARCIAL','NAO_CUMPRIDA'],true)||$m==='')throw new RuntimeException('Informe o resultado e a manifestação técnica de todas as exigências.');if($resultado==='APROVADO'&&$r!=='CUMPRIDA')throw new RuntimeException('O relatório conclusivo exige baixa integral de todas as exigências.');}
        if($resultado==='APROVADO'){
            $q=$pdo->prepare("SELECT COUNT(*) FROM analise_planos_itens WHERE analise_id=:id AND aplicavel=1 AND impeditivo_emissao=1 AND resultado NOT IN ('CONFORME','NAO_APLICA')");$q->execute([':id'=>$analiseId]);if((int)$q->fetchColumn()>0)throw new RuntimeException('Existem itens impeditivos ainda não conformes.');
            $q=$pdo->prepare("SELECT COUNT(*) FROM analise_planos_arquivos ar INNER JOIN analise_planos_submissoes s ON s.id=ar.submissao_id WHERE s.analise_id=:id AND ar.classificacao IN ('RECEBIDO','REJEITADO')");$q->execute([':id'=>$analiseId]);if((int)$q->fetchColumn()>0)throw new RuntimeException('Resolva todos os arquivos recebidos ou rejeitados antes do relatório conclusivo.');
        }
        $responsavel=analiseAcaoResponsavelDoAnalista($pdo,$analise);
        $pdo->beginTransaction();$q=$pdo->prepare('SELECT id FROM analise_planos_pareceres WHERE analise_id=:id AND status NOT IN ("PUBLICADO","DEVOLVIDO","CANCELADO") FOR UPDATE');$q->execute([':id'=>$analiseId]);if($q->fetchColumn())throw new RuntimeException('Já existe um relatório aberto neste processo.');
        $q=$pdo->prepare('SELECT id FROM analise_planos_pareceres WHERE analise_id=:id AND status="PUBLICADO" ORDER BY versao DESC LIMIT 1 FOR UPDATE');$q->execute([':id'=>$analiseId]);$anterior=$q->fetchColumn()?:null;
        $q=$pdo->prepare('SELECT COALESCE(MAX(versao),0)+1 FROM analise_planos_pareceres WHERE analise_id=:id FOR UPDATE');$q->execute([':id'=>$analiseId]);$versao=(int)$q->fetchColumn();
        $numero=gerarNumeroDocumento('RAP-REL','AM-RAP-REL');$parecerId=gerarUUID();$finalidade=$resultado==='APROVADO'?'CONCLUSIVO':($anterior?'CUMPRIMENTO_EXIGENCIAS':'ANALISE_INICIAL');$snapshot=analisePlanosSnapshot($pdo,$analise,$submissaoId);
        $pdo->prepare("INSERT INTO analise_planos_pareceres(id,numero,analise_id,versao,finalidade,submissao_id,relatorio_anterior_id,norma_versao_id,resultado,resumo,conclusao,snapshot_json,status,responsavel_assinatura_id,criado_por)VALUES(:id,:numero,:analise,:versao,:finalidade,:submissao,:anterior,:norma,:resultado,:resumo,:conclusao,:snapshot,'AGUARDANDO_ASSINATURA_ANALISTA',:responsavel,:usuario)")->execute([':id'=>$parecerId,':numero'=>$numero,':analise'=>$analiseId,':versao'=>$versao,':finalidade'=>$finalidade,':submissao'=>$submissaoId,':anterior'=>$anterior,':norma'=>$analise['norma_versao_id']??null,':resultado'=>$resultado,':resumo'=>$resumo,':conclusao'=>$conclusao,':snapshot'=>json_encode($snapshot,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),':responsavel'=>$responsavel['id'],':usuario'=>$usuario]);
        $ins=$pdo->prepare('INSERT INTO analise_planos_relatorio_exigencias(id,relatorio_id,exigencia_id,submissao_id,resultado,manifestacao_tecnica,descricao_snapshot,referencia_snapshot,criado_por)VALUES(UUID(),:relatorio,:exigencia,:submissao,:resultado,:manifestacao,:descricao,:referencia,:usuario)');
        foreach($exigenciasCiclo as $ex){$ins->execute([':relatorio'=>$parecerId,':exigencia'=>$ex['id'],':submissao'=>$submissaoId,':resultado'=>$resultadosEx[$ex['id']],':manifestacao'=>trim($manifestacoes[$ex['id']]),':descricao'=>$ex['descricao'],':referencia'=>$ex['referencia_normativa'],':usuario'=>$usuario]);}
        $pdo->prepare("UPDATE analises_planos SET status='AGUARDANDO_ASSINATURA_ANALISTA',responsavel_assinatura_id=:responsavel WHERE id=:id")->execute([':responsavel'=>$responsavel['id'],':id'=>$analiseId]);analisePlanosHistorico($pdo,$analiseId,'RELATORIO_CICLO_PREPARADO',$analise['status'],'AGUARDANDO_ASSINATURA_ANALISTA',$numero.' preparado.');analisePlanosAuditarNorma($pdo,$analiseId,'RELATORIO_CICLO_PREPARADO',$analise['status'],'AGUARDANDO_ASSINATURA_ANALISTA',$numero);$pdo->commit();
        setMensagem('success','Relatório '.$numero.' preparado. Assine tecnicamente com sua própria identidade.');redirecionar($retorno($analiseId).'#pareceres');
    }

    if ($acao === 'assinar_parecer') {
        if($cargo!=='ANALISTA'||$analise['analista_id']!==$usuario)throw new RuntimeException('Somente o analista atribuído pode assinar.');
        $parecerId=trim($_POST['parecer_id']??'');$responsavel=analiseAcaoResponsavelDoAnalista($pdo,$analise);
        $pdo->beginTransaction();$stmt=$pdo->prepare("UPDATE analise_planos_pareceres SET status='AGUARDANDO_APROVACAO_ADMIN',assinado_analista_em=NOW(),assinatura_analista_ip=:ip WHERE id=:id AND analise_id=:analise AND criado_por=:usuario AND responsavel_assinatura_id=:responsavel AND status='AGUARDANDO_ASSINATURA_ANALISTA'");
        $stmt->execute([':ip'=>obterIpCliente(),':id'=>$parecerId,':analise'=>$analiseId,':usuario'=>$usuario,':responsavel'=>$responsavel['id']]);if($stmt->rowCount()!==1)throw new RuntimeException('Parecer não está disponível para sua assinatura.');
        $pdo->prepare("UPDATE analises_planos SET status='AGUARDANDO_APROVACAO_ADMIN' WHERE id=:id")->execute([':id'=>$analiseId]);analisePlanosHistorico($pdo,$analiseId,'RELATORIO_ASSINADO_ANALISTA','AGUARDANDO_ASSINATURA_ANALISTA','AGUARDANDO_APROVACAO_ADMIN');analisePlanosAuditarNorma($pdo,$analiseId,'RELATORIO_ASSINADO_ANALISTA','AGUARDANDO_ASSINATURA_ANALISTA','AGUARDANDO_APROVACAO_ADMIN',$parecerId);analisePlanosNotificarAdmins($pdo,'PARECER_AGUARDANDO_ADMIN','Relatório aguardando validação',$analise['numero'].' foi assinado pelo analista.',$analiseId);$pdo->commit();
        setMensagem('success','Parecer assinado e enviado ao admin.');redirecionar($retorno($analiseId).'#pareceres');
    }

    if ($acao === 'publicar') {
        if($cargo!=='ADMIN')throw new RuntimeException('Somente o admin pode publicar ou devolver.');
        $pdo->beginTransaction();
        $parecerId=trim($_POST['parecer_id']??'');$stmt=$pdo->prepare("SELECT * FROM analise_planos_pareceres WHERE id=:id AND analise_id=:analise AND status='AGUARDANDO_APROVACAO_ADMIN' FOR UPDATE");$stmt->execute([':id'=>$parecerId,':analise'=>$analiseId]);$parecer=$stmt->fetch(PDO::FETCH_ASSOC);if(!$parecer)throw new RuntimeException('Parecer não está aguardando o admin.');
        if(!empty($_POST['devolver'])){$motivo=trim($_POST['motivo']??'');if($motivo==='')throw new InvalidArgumentException('Informe o motivo da devolução.');$pdo->prepare("UPDATE analise_planos_pareceres SET status='DEVOLVIDO',devolvido_motivo=:motivo WHERE id=:id")->execute([':motivo'=>$motivo,':id'=>$parecerId]);$pdo->prepare("UPDATE analises_planos SET status='EM_ANALISE' WHERE id=:id")->execute([':id'=>$analiseId]);analisePlanosHistorico($pdo,$analiseId,'RELATORIO_DEVOLVIDO','AGUARDANDO_APROVACAO_ADMIN','EM_ANALISE',$motivo);analisePlanosAuditarNorma($pdo,$analiseId,'RELATORIO_DEVOLVIDO','AGUARDANDO_APROVACAO_ADMIN','EM_ANALISE',$motivo);analisePlanosNotificar($pdo,$analise['analista_id'],'PARECER_DEVOLVIDO','Relatório devolvido pelo admin',$motivo,$analiseId,'analises-planos/form?id='.urlencode($analiseId));$pdo->commit();setMensagem('success','Relatório devolvido ao analista.');redirecionar($retorno($analiseId).'#pareceres');}
        $responsavel=analiseAcaoResponsavelDoAnalista($pdo,$analise);
        $novoStatus=match($parecer['resultado']){'EXIGENCIAS'=>'AGUARDANDO_DOCUMENTOS','REPROVADO'=>'REPROVADA',default=>'CONCLUIDA'};
        $resultados=$pdo->prepare('SELECT exigencia_id,resultado FROM analise_planos_relatorio_exigencias WHERE relatorio_id=:id');$resultados->execute([':id'=>$parecerId]);
        $updEx=$pdo->prepare("UPDATE analise_planos_exigencias SET status=:status,saneamento_pendente=0,observacao_cumprimento=CONCAT(COALESCE(observacao_cumprimento,''),:nota) WHERE id=:id AND analise_id=:analise");
        foreach($resultados->fetchAll(PDO::FETCH_ASSOC) as $r){$updEx->execute([':status'=>$r['resultado']==='NAO_CUMPRIDA'?'NAO_CUMPRIDA':$r['resultado'],':nota'=>"\nBaixa registrada no relatório ".$parecer['numero'].'.',':id'=>$r['exigencia_id'],':analise'=>$analiseId]);}
        if($parecer['resultado']==='APROVADO')analisePlanosValidarConclusao($pdo,$analiseId);
        $pdo->prepare("UPDATE analise_planos_pareceres SET status='PUBLICADO',publicado_em=NOW(),validado_em=NOW(),validado_por=:usuario WHERE id=:id")->execute([':usuario'=>$usuario,':id'=>$parecerId]);
        if($parecer['resultado']==='APROVADO'){
            if(empty($analise['proposta_id'])||empty($analise['servico_id'])||empty($analise['vendedor_origem_id']))throw new RuntimeException('Vincule a origem comercial do processo legado antes de publicar uma nova licença.');
            analiseAcaoCriarLicenca($pdo,$analise,$responsavel);
        }
        $pdo->prepare('UPDATE analises_planos SET status=:status WHERE id=:id')->execute([':status'=>$novoStatus,':id'=>$analiseId]);analisePlanosHistorico($pdo,$analiseId,'RELATORIO_CICLO_PUBLICADO','AGUARDANDO_APROVACAO_ADMIN',$novoStatus,$parecer['numero'].' publicado pelo admin.');analisePlanosAuditarNorma($pdo,$analiseId,'RELATORIO_CICLO_PUBLICADO','AGUARDANDO_APROVACAO_ADMIN',$novoStatus,$parecer['numero']);
        analiseAcaoPersistirParecerPdf($pdo,$parecerId,$analiseId);
        $pdo->commit();
        setMensagem('success',$parecer['resultado']==='APROVADO'?'Relatório conclusivo publicado e minuta da licença criada.':'Relatório de ciclo publicado.');
        redirecionar($retorno($analiseId).'#pareceres');
    }

    throw new RuntimeException('Ação inválida.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Erro em Análise de Planos: '.$e->getMessage());
    setMensagem('error',$e->getMessage());
    redirecionar($retorno($analiseId));
}
