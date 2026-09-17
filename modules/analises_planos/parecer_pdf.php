<?php
require_once __DIR__.'/../../config.php';require_once __DIR__.'/../../includes/functions.php';require_once __DIR__.'/../../includes/analise_planos.php';require_once __DIR__.'/../../includes/assinaturas_usuarios.php';require_once __DIR__.'/../../vendor/autoload.php';
$id=trim($_GET['id']??'');$stmt=$pdo->prepare("SELECT p.*,ap.numero processo_numero,ap.tipo_processo,ap.enquadramento,ap.objeto,ap.estaleiro,ap.numero_casco,ap.responsavel_projeto_nome,ap.responsavel_projeto_registro,ap.art_numero,ap.observacoes,e.nome embarcacao_nome,e.registro,e.numero_inscricao,c.nome solicitante_nome,u.nome analista_nome,ra.nome_completo responsavel_nome,ra.cargo_titulo responsavel_cargo,ra.registro_profissional responsavel_registro,ra.cpf_cnpj responsavel_cpf_cnpj,ra.assinatura_arquivo,ra.assinatura_hash,u_val.nome admin_validador_nome FROM analise_planos_pareceres p INNER JOIN analises_planos ap ON ap.id=p.analise_id INNER JOIN embarcacoes e ON e.id=ap.embarcacao_id LEFT JOIN clientes c ON c.id=ap.solicitante_id LEFT JOIN usuarios u ON u.id=ap.analista_id LEFT JOIN responsaveis_assinatura ra ON ra.id=p.responsavel_assinatura_id LEFT JOIN usuarios u_val ON u_val.id=p.validado_por WHERE p.id=:id");$stmt->execute([':id'=>$id]);$p=$stmt->fetch(PDO::FETCH_ASSOC);if(!$p){http_response_code(404);die('Relatório não encontrado.');}
if(!isset($salvar_pdf_caminho)){require_once __DIR__.'/../../includes/auth.php';verificar_sessao();if(!analisePlanosPodeGerenciar()){http_response_code(403);die('Acesso negado.');}$ap=null;if(!empty($p['caminho_pdf_final'])&&!empty($p['hash_pdf_final'])){$ap=['caminho_pdf_final'=>$p['caminho_pdf_final'],'hash_pdf_final'=>$p['hash_pdf_final']];}else{$audit=$pdo->prepare("SELECT caminho_pdf_final,hash_pdf_final FROM documento_aprovacoes WHERE documento_tipo='PARECER_PLANOS' AND documento_id=:id AND status='APROVADO' ORDER BY versao DESC LIMIT 1");$audit->execute([':id'=>$id]);$ap=$audit->fetch(PDO::FETCH_ASSOC);}if($ap&&$ap['caminho_pdf_final']){$path=__DIR__.'/../../'.ltrim(str_replace(['../','..\\'],'',$ap['caminho_pdf_final']),'/\\');if(is_file($path)&&hash_equals($ap['hash_pdf_final'],hash_file('sha256',$path))){header('Content-Type: application/pdf');header('Content-Disposition: inline; filename="'.preg_replace('/[^A-Za-z0-9._-]/','-',$p['numero']).'-v'.$p['versao'].'.pdf"');readfile($path);exit;}}}
$snapshot=json_decode((string)($p['snapshot_json']??''),true)?:[];$itens=$snapshot['matriz']??[];$arquivos=$snapshot['arquivos']??[];$ex=$pdo->prepare('SELECT re.*,e.ordem FROM analise_planos_relatorio_exigencias re INNER JOIN analise_planos_exigencias e ON e.id=re.exigencia_id WHERE re.relatorio_id=:id ORDER BY e.ordem,re.id');$ex->execute([':id'=>$p['id']]);$exigencias=$ex->fetchAll(PDO::FETCH_ASSOC);
if(!class_exists('ParecerPlanosPdf')){class ParecerPlanosPdf extends TCPDF{public function Header(){$logo=__DIR__.'/../../img/logo.png';if(is_file($logo))$this->Image($logo,15,4.5,24,0,'PNG','','',true,300);$this->SetFont('helvetica','B',11.5);$this->SetTextColor(13,73,65);$this->SetXY(40,9.5);$this->Cell(155,6,'AMAZON CERTIFICADORA NAVAL',0,1,'R');$this->SetFont('helvetica','B',8.5);$this->SetTextColor(85,105,98);$this->SetX(40);$this->Cell(155,5,'RELATÓRIO DE ANÁLISE DE PLANOS',0,1,'R');$this->SetDrawColor(13,73,65);$this->SetLineWidth(0.5);$this->Line(15,30.5,195,30.5);}public function Footer(){$this->SetY(-15);$this->SetFont('helvetica','',7);$this->Cell(0,5,'Documento técnico gerado pelo ERP Amazon Naval · Página '.$this->getAliasNumPage().' de '.$this->getAliasNbPages(),0,0,'C');}}}

$numeroRelatorio=$p['numero']?:($p['processo_numero'].' · histórico v'.$p['versao']);$pdf=new ParecerPlanosPdf('P','mm','A4',true,'UTF-8');$pdf->SetCreator('ERP Amazon Naval');$pdf->SetTitle($numeroRelatorio);$pdf->SetMargins(15,35.5,15);$pdf->SetAutoPageBreak(true,20);$pdf->AddPage();$pdf->SetFont('helvetica','B',15);$pdf->Cell(0,9,$numeroRelatorio,0,1,'C');$pdf->SetFont('helvetica','B',10);$pdf->Cell(0,7,str_replace('_',' ',$p['finalidade']?:$p['resultado']),0,1,'C');
if(!function_exists('ppSection')){function ppSection(TCPDF $pdf,string $titulo):void{$pdf->Ln(3);$pdf->SetFillColor(13,73,65);$pdf->SetTextColor(255,255,255);$pdf->SetFont('helvetica','B',9);$pdf->Cell(0,7,$titulo,0,1,'L',true);$pdf->SetTextColor(0,0,0);$pdf->SetFont('helvetica','',8);}}
if(!function_exists('ppRows')){function ppRows(TCPDF $pdf,array $rows):void{foreach($rows as [$l,$v]){$pdf->SetFont('helvetica','B',8);$pdf->Cell(45,6,$l,1,0);$pdf->SetFont('helvetica','',8);$pdf->MultiCell(0,6,(string)($v?:'-'),1,'L',false,1);}}}

ppSection($pdf,'1. IDENTIFICAÇÃO');ppRows($pdf,[['Processo',$p['processo_numero']],['Embarcação',$p['embarcacao_nome']],['Inscrição/registro',$p['registro']?:$p['numero_inscricao']],['Documento',$p['tipo_processo'].' · '.$p['enquadramento']],['Objeto',$p['objeto']],['Solicitante',$p['solicitante_nome']],['Estaleiro / casco',trim(($p['estaleiro']?:'-').' / '.($p['numero_casco']?:'-'))],['Projeto / registro',trim(($p['responsavel_projeto_nome']?:'-').' / '.($p['responsavel_projeto_registro']?:'-'))],['ART',$p['art_numero']]]);
ppSection($pdf,'2. DOCUMENTOS DA REVISÃO ANALISADA');if(!$arquivos)$pdf->MultiCell(0,6,'Nenhum arquivo registrado.',1);else foreach($arquivos as $ar)$pdf->MultiCell(0,6,$ar['nome_original'].' · '.str_replace('_',' ',$ar['classificacao']).' · SHA-256 '.$ar['sha256'],1);
ppSection($pdf,'3. BASE NORMATIVA');$pdf->MultiCell(0,6,$p['enquadramento'].' e referências registradas na matriz técnica. Os planos e documentos são verificados quanto aos requisitos aplicáveis, incluindo identificação da embarcação, responsabilidade técnica e ART.',1);
ppSection($pdf,'4. MATRIZ DE ANÁLISE');$pdf->SetFont('helvetica','B',7);foreach([10,58,45,27,40] as $i=>$w)$pdf->Cell($w,6,['#','Documento/requisito','Referência','Resultado','Observação'][$i],1,0,'C');$pdf->Ln();$pdf->SetFont('helvetica','',7);foreach($itens as $i){$h=max(8,$pdf->getStringHeight(58,$i['documento']),$pdf->getStringHeight(40,(string)$i['observacao']));$pdf->MultiCell(10,$h,$i['ordem'],1,'C',false,0);$pdf->MultiCell(58,$h,$i['documento'],1,'L',false,0);$pdf->MultiCell(45,$h,$i['referencia_normativa'],1,'L',false,0);$pdf->MultiCell(27,$h,str_replace('_',' ',$i['resultado']),1,'C',false,0);$pdf->MultiCell(40,$h,(string)$i['observacao'],1,'L',false,1);}
ppSection($pdf,'5. EXIGÊNCIAS E BAIXAS DESTE CICLO');if(!$exigencias)$pdf->MultiCell(0,6,'Não foram registradas exigências.',1);else foreach($exigencias as $x)$pdf->MultiCell(0,6,$x['ordem'].'. '.$x['descricao_snapshot'].' | Ref.: '.($x['referencia_snapshot']?:'-').' | Resultado: '.$x['resultado'].' | Manifestação: '.$x['manifestacao_tecnica'],1);
ppSection($pdf,'6. RESUMO E CONCLUSÃO');$pdf->SetFont('helvetica','B',8);$pdf->Cell(0,6,'Resumo',1,1);$pdf->SetFont('helvetica','',8);$pdf->MultiCell(0,6,$p['resumo'],1);$pdf->SetFont('helvetica','B',8);$pdf->Cell(0,6,'Conclusão',1,1);$pdf->SetFont('helvetica','',8);$pdf->MultiCell(0,6,$p['conclusao'],1);
ppSection($pdf,'7. RESPONSABILIDADE TÉCNICA E ASSINATURA ELETRÔNICA');
ppRows($pdf,[
    ['Analista responsável',$p['analista_nome']?:'-'],
    ['Responsável técnico',$p['responsavel_nome']?:'-'],
    ['Cargo / registro',trim(($p['responsavel_cargo']?:'-').' / '.($p['responsavel_registro']?:'-'))],
    ['Emissão do parecer',$p['publicado_em']?date('d/m/Y H:i',strtotime($p['publicado_em'])):date('d/m/Y')]
]);

$isAssinado = !empty($p['assinado_analista_em']) || in_array($p['status'], ['AGUARDANDO_APROVACAO_ADMIN', 'PUBLICADO'], true);

if ($isAssinado) {
    if ($pdf->GetY() + 42 > 275) {
        $pdf->AddPage();
    }
    $sigY = $pdf->GetY() + 3;
    $sigX = 15;
    $sigW = 180;
    $sigH = 34;
    $colImgW = 55;

    $pdf->SetDrawColor(13, 73, 65);
    $pdf->SetLineWidth(0.3);
    $pdf->SetFillColor(248, 250, 249);
    $pdf->RoundedRect($sigX, $sigY, $sigW, $sigH, 2, '1111', 'DF');

    $sigFileRel = $p['assinatura_arquivo'] ?? '';
    $sigFileAbs = $sigFileRel ? __DIR__ . '/../../' . ltrim(str_replace(['../', '..\\'], '', $sigFileRel), '/\\') : '';

    if ($sigFileAbs && is_file($sigFileAbs)) {
        if (function_exists('garantirAssinaturaTransparente')) {
            garantirAssinaturaTransparente($sigFileAbs);
        }
        $pdf->Image($sigFileAbs, $sigX + 5, $sigY + 3, 45, 17, 'PNG', '', '', true, 150, '', false, false, 0, true, false, false);
    } else {
        $pdf->SetXY($sigX + 4, $sigY + 8);
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->SetTextColor(120, 120, 120);
        $pdf->Cell($colImgW - 8, 8, '[Assinatura Cadastrada]', 0, 0, 'C');
    }

    $pdf->SetXY($sigX + 2, $sigY + 22);
    $pdf->SetFont('helvetica', 'I', 6.0);
    $pdf->SetTextColor(90, 100, 95);
    $pdf->Cell($colImgW, 3.5, 'Representação gráfica da assinatura', 0, 1, 'C');

    $pdf->SetXY($sigX + 2, $sigY + 26);
    $pdf->SetFont('helvetica', 'B', 6.8);
    $pdf->SetTextColor(13, 73, 65);
    $pdf->Cell($colImgW, 4, 'ASSINADO DIGITALMENTE', 0, 1, 'C');

    $pdf->SetDrawColor(205, 215, 210);
    $pdf->SetLineWidth(0.2);
    $pdf->Line($sigX + $colImgW, $sigY + 2, $sigX + $colImgW, $sigY + $sigH - 2);

    $textX = $sigX + $colImgW + 4;
    $textW = $sigW - $colImgW - 6;

    $pdf->SetXY($textX, $sigY + 2.5);
    $pdf->SetFont('helvetica', 'B', 7.5);
    $pdf->SetTextColor(13, 73, 65);
    $pdf->Cell($textW, 4, 'CHANCELA TÉCNICA NAVAL · ASSINATURA ELETRÔNICA QUALIFICADA', 0, 1, 'L');

    $pdf->SetFont('helvetica', '', 6.8);
    $pdf->SetTextColor(30, 40, 35);

    $dtAssinatura = !empty($p['assinado_analista_em']) ? date('d/m/Y H:i:s', strtotime($p['assinado_analista_em'])) : date('d/m/Y H:i:s');
    $dtPublicado = !empty($p['publicado_em']) ? date('d/m/Y H:i:s', strtotime($p['publicado_em'])) : null;
    $signatario = $p['responsavel_nome'] ?: ($p['analista_nome'] ?: 'Analista Naval');
    $cargoReg = trim(($p['responsavel_cargo'] ?: 'Analista Técnico Naval') . ' | ' . ($p['responsavel_registro'] ?: 'CREA/Conselho'));

    $pdf->SetX($textX);
    $pdf->Cell($textW, 3.6, 'Signatário: ' . $signatario . (!empty($p['responsavel_cpf_cnpj']) ? ' · CPF: ' . $p['responsavel_cpf_cnpj'] : ''), 0, 1, 'L');

    $pdf->SetX($textX);
    $pdf->Cell($textW, 3.6, 'Qualificação: ' . $cargoReg, 0, 1, 'L');

    $pdf->SetX($textX);
    $pdf->Cell($textW, 3.6, 'Data/Hora da Assinatura: ' . $dtAssinatura . ' (Horário de Brasília)', 0, 1, 'L');

    if (!empty($p['assinatura_hash'])) {
        $pdf->SetX($textX);
        $pdf->SetFont('helvetica', '', 5.8);
        $pdf->SetTextColor(95, 105, 100);
        $pdf->Cell($textW, 3.2, 'Hash SHA-256: ' . $p['assinatura_hash'], 0, 1, 'L');
    }

    if ($p['status'] === 'PUBLICADO') {
        $validador = $p['admin_validador_nome'] ?: 'Administrador';
        $pdf->SetX($textX);
        $pdf->SetFont('helvetica', 'B', 6.5);
        $pdf->SetTextColor(13, 73, 65);
        $pdf->Cell($textW, 3.6, 'Homologação Administrativa: Aprovado por ' . $validador . ($dtPublicado ? ' em ' . $dtPublicado : ''), 0, 1, 'L');
    } else {
        $pdf->SetX($textX);
        $pdf->SetFont('helvetica', 'I', 6.5);
        $pdf->SetTextColor(170, 95, 0);
        $pdf->Cell($textW, 3.6, 'Situação: Assinado tecnicamente · Aguardando validação administrativa', 0, 1, 'L');
    }

    $pdf->SetY($sigY + $sigH + 2);
} else {
    $pdf->Ln(2);
    $pdf->SetFillColor(254, 243, 199);
    $pdf->SetDrawColor(217, 119, 6);
    $pdf->SetTextColor(146, 64, 14);
    $pdf->SetFont('helvetica', 'B', 7.5);
    $pdf->Cell(0, 7, 'DOCUMENTO PRELIMINAR · AGUARDANDO ASSINATURA ELETRÔNICA DO RESPONSÁVEL TÉCNICO', 1, 1, 'C', true);
    $pdf->SetTextColor(0, 0, 0);
}

$nome='Relatorio-'.preg_replace('/[^A-Za-z0-9._-]/','-',$numeroRelatorio).'.pdf';
if (isset($salvar_pdf_caminho) && $salvar_pdf_caminho) {
    $dir = dirname($salvar_pdf_caminho);
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
        @chmod($dir, 0775);
    }
    $pdf->Output($salvar_pdf_caminho, 'F');
} elseif (!empty($return_pdf_string)) {
    $pdf_content = $pdf->Output('', 'S');
} else {
    $pdf->Output($nome, 'I');
}
