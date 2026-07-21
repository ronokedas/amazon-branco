<?php

function xlsxXml(string $valor): string
{
    return htmlspecialchars($valor, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function xlsxColuna(int $indice): string
{
    $nome='';
    while($indice>0){$indice--; $nome=chr(65+($indice%26)).$nome; $indice=intdiv($indice,26);}
    return $nome;
}

function xlsxCelula(mixed $valor, int $linha, int $coluna, int $estilo=0, ?string $formula=null): string
{
    $ref=xlsxColuna($coluna).$linha;
    $style=$estilo>0?' s="'.$estilo.'"':'';
    if($formula!==null){return '<c r="'.$ref.'"'.$style.'><f>'.xlsxXml($formula).'</f><v>'.(is_numeric($valor)?$valor:0).'</v></c>';}
    if(is_int($valor)||is_float($valor)){return '<c r="'.$ref.'"'.$style.'><v>'.$valor.'</v></c>';}
    return '<c r="'.$ref.'" t="inlineStr"'.$style.'><is><t xml:space="preserve">'.xlsxXml((string)$valor).'</t></is></c>';
}

/** Gera um XLSX enxuto e formatado. Cada celula pode ser valor simples ou [valor, estilo, formula]. */
function xlsxGerar(string $caminho, array $planilhas): void
{
    $zip=new ZipArchive();
    if($zip->open($caminho,ZipArchive::CREATE|ZipArchive::OVERWRITE)!==true) throw new RuntimeException('Não foi possível criar o arquivo Excel.');
    $sheetOverrides=[];$workbookSheets=[];$workbookRels=[];
    foreach(array_values($planilhas) as $i=>$planilha){
        $numero=$i+1;$nome=mb_substr(str_replace(['\\','/','?','*','[',']',':'],'-',(string)$planilha['nome']),0,31);
        $workbookSheets[]='<sheet name="'.xlsxXml($nome).'" sheetId="'.$numero.'" r:id="rId'.$numero.'"/>';
        $workbookRels[]='<Relationship Id="rId'.$numero.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$numero.'.xml"/>';
        $sheetOverrides[]='<Override PartName="/xl/worksheets/sheet'.$numero.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        $rows=[];$maxCol=1;
        foreach(array_values($planilha['linhas']) as $r=>$linha){
            $cells=[];$linhaNumero=$r+1;$maxCol=max($maxCol,count($linha));
            foreach(array_values($linha) as $c=>$celula){
                $valor=is_array($celula)?($celula[0]??''):$celula;
                $estilo=is_array($celula)?(int)($celula[1]??0):0;
                $formula=is_array($celula)?($celula[2]??null):null;
                $cells[]=xlsxCelula($valor,$linhaNumero,$c+1,$estilo,$formula);
            }
            $altura=$linhaNumero===1?' ht="28" customHeight="1"':'';
            $rows[]='<row r="'.$linhaNumero.'"'.$altura.'>'.implode('',$cells).'</row>';
        }
        $larguras=$planilha['larguras']??array_fill(0,$maxCol,16);$cols=[];
        foreach($larguras as $c=>$largura)$cols[]='<col min="'.($c+1).'" max="'.($c+1).'" width="'.(float)$largura.'" customWidth="1"/>';
        $freeze=(int)($planilha['congelar']??1);
        $pane=$freeze>0?'<sheetViews><sheetView workbookViewId="0" showGridLines="0"><pane ySplit="'.$freeze.'" topLeftCell="A'.($freeze+1).'" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>':'<sheetViews><sheetView workbookViewId="0" showGridLines="0"/></sheetViews>';
        $dim='A1:'.xlsxColuna($maxCol).max(1,count($rows));
        $tituloMesclado=$maxCol>1?'<mergeCells count="1"><mergeCell ref="A1:'.xlsxColuna($maxCol).'1"/></mergeCells>':'';
        $xml='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><dimension ref="'.$dim.'"/>'.$pane.'<sheetFormatPr defaultRowHeight="18"/><cols>'.implode('',$cols).'</cols><sheetData>'.implode('',$rows).'</sheetData>'.$tituloMesclado.'<pageMargins left="0.3" right="0.3" top="0.5" bottom="0.5" header="0.2" footer="0.2"/><pageSetup orientation="landscape" fitToWidth="1" fitToHeight="0"/></worksheet>';
        $zip->addFromString('xl/worksheets/sheet'.$numero.'.xml',$xml);
    }
    $zip->addFromString('[Content_Types].xml','<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/><Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'.implode('',$sheetOverrides).'</Types>');
    $zip->addFromString('_rels/.rels','<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/></Relationships>');
    $zip->addFromString('xl/workbook.xml','<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><bookViews><workbookView/></bookViews><sheets>'.implode('',$workbookSheets).'</sheets><calcPr calcId="191029" fullCalcOnLoad="1"/></workbook>');
    $zip->addFromString('xl/_rels/workbook.xml.rels','<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.implode('',$workbookRels).'<Relationship Id="rId'.(count($planilhas)+1).'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
    $zip->addFromString('xl/styles.xml','<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><numFmts count="2"><numFmt numFmtId="164" formatCode="R$ #,##0.00;[Red](R$ #,##0.00);-"/><numFmt numFmtId="165" formatCode="0.0%"/></numFmts><fonts count="3"><font><sz val="10"/><name val="Aptos"/></font><font><b/><color rgb="FFFFFFFF"/><sz val="16"/><name val="Aptos Display"/></font><font><b/><color rgb="FF173B32"/><sz val="10"/><name val="Aptos"/></font></fonts><fills count="4"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF08734D"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFEAF7F1"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="2"><border/><border><bottom style="thin"><color rgb="FFD9E5E0"/></bottom></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="7"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/><xf numFmtId="0" fontId="2" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/><xf numFmtId="0" fontId="2" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/><xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"/><xf numFmtId="165" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"/><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"/></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>');
    $agora=gmdate('Y-m-d\TH:i:s\Z');
    $zip->addFromString('docProps/core.xml','<?xml version="1.0" encoding="UTF-8" standalone="yes"?><cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:title>Relatório Financeiro</dc:title><dc:creator>Sistema Amazon</dc:creator><dcterms:created xsi:type="dcterms:W3CDTF">'.$agora.'</dcterms:created></cp:coreProperties>');
    $zip->addFromString('docProps/app.xml','<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties"><Application>Sistema Amazon</Application></Properties>');
    $zip->close();
}
