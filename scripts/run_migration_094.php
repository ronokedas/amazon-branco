<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

try {
    echo "Iniciando migração 094..." . PHP_EOL;

    $cols = $pdo->query('SHOW COLUMNS FROM exigencias_catalogo')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('obrigatoria', $cols, true)) {
        $pdo->exec('ALTER TABLE exigencias_catalogo ADD COLUMN obrigatoria TINYINT(1) NOT NULL DEFAULT 0 AFTER ativo');
        echo "Coluna 'obrigatoria' adicionada." . PHP_EOL;
    }
    if (!in_array('exige_foto', $cols, true)) {
        $pdo->exec('ALTER TABLE exigencias_catalogo ADD COLUMN exige_foto TINYINT(1) NOT NULL DEFAULT 0 AFTER obrigatoria');
        echo "Coluna 'exige_foto' adicionada." . PHP_EOL;
    }
    if (!in_array('ordem_exibicao', $cols, true)) {
        $pdo->exec('ALTER TABLE exigencias_catalogo ADD COLUMN ordem_exibicao INT NOT NULL DEFAULT 0 AFTER exige_foto');
        echo "Coluna 'ordem_exibicao' adicionada." . PHP_EOL;
    }

    // Configurar exigências mestras
    $stmtUp = $pdo->exec("UPDATE exigencias_catalogo 
        SET obrigatoria = 1, exige_foto = 1 
        WHERE descricao LIKE '%coletes salva vidas%'
           OR descricao LIKE '%coletes salva-vidas%'
           OR descricao LIKE '%boias salva vidas%'
           OR descricao LIKE '%boias salva-vidas%'
           OR descricao LIKE '%bombas de inc%'
           OR descricao LIKE '%bomba de inc%'
           OR descricao LIKE '%extintor%'
           OR descricao LIKE '%luzes de navega%'
           OR descricao LIKE '%escala de calado%'
           OR descricao LIKE '%governo de emerg%'
           OR descricao LIKE '%leme%'
           OR descricao LIKE '%plimsoll%'
           OR descricao LIKE '%borda livre%'
           OR descricao LIKE '%esgoto%'
           OR descricao LIKE '%bandejas coletoras%'
           OR descricao LIKE '%lota%passageiros%'");
    echo "Exigências mestras atualizadas: {$stmtUp} registros marcados como obrigatórios com foto." . PHP_EOL;

    // Inserir as 4 novas exigências mestras se não existirem
    $novas = [
        [
            'id' => 'e202-0001-4921-b12a-000000000001',
            'codigo' => 'EX-553',
            'cat' => '71c05e83-0d67-4137-b2b7-478c4241a057',
            'descr' => 'Marcação física da Linha de Borda Livre / Disco de Plimsoll soldada ou marcada em baixo relevo a meia-nau em ambos os bordos, com pintura contrastante de acordo com o Certificado Nacional de Borda Livre (CNBL)',
            'normam' => 'NORMAM-202/DPC, Cap. 02, Seção II',
            'bloco' => 'borda_livre',
            'prazo' => 15,
            'obrig' => 1,
            'foto' => 1
        ],
        [
            'id' => 'e202-0002-4921-b12a-000000000002',
            'codigo' => 'EX-554',
            'cat' => '65bf89f0-f44d-4746-89f7-f530c9aa990d',
            'descr' => 'Válvula de descarga direta de água de porão com lacre numerado ou dispositivo de interrupção bloqueado para impedir descarte involuntário de resíduos oleosos nos rios (Prevenção da Poluição Hídrica)',
            'normam' => 'NORMAM-202/DPC, Cap. 08, Item 8.3',
            'bloco' => 'flutuando',
            'prazo' => 7,
            'obrig' => 1,
            'foto' => 1
        ],
        [
            'id' => 'e202-0003-4921-b12a-000000000003',
            'codigo' => 'EX-555',
            'cat' => 'e70f7906-4e9d-4367-b10a-2ad2a007817a',
            'descr' => 'Painel de controle e alarme sonoro/visual de falha ou queima de lâmpadas das luzes de navegação no comando/passadiço',
            'normam' => 'NORMAM-202/DPC, Cap. 04, Seção II',
            'bloco' => 'flutuando',
            'prazo' => 15,
            'obrig' => 1,
            'foto' => 1
        ],
        [
            'id' => 'e202-0004-4921-b12a-000000000004',
            'codigo' => 'EX-556',
            'cat' => 'b2aca3e2-50a9-4086-a7bf-aea8bbfd9a0d',
            'descr' => 'Placa informativa de capacidade máxima de passageiros (N1 e N2), tripulantes e limites de carga afixada em local visível ao público e passageiros',
            'normam' => 'NORMAM-202/DPC, Cap. 07, Seção I',
            'bloco' => 'flutuando',
            'prazo' => 10,
            'obrig' => 1,
            'foto' => 1
        ]
    ];

    $stmtIns = $pdo->prepare("INSERT INTO exigencias_catalogo 
        (id, codigo_interno, categoria_id, descricao, item_normam, bloco_vistoria, tipo_vistoria, prazo_padrao_dias, ativo, obrigatoria, exige_foto, ordem_exibicao)
        VALUES (:id, :codigo, :cat, :descr, :normam, :bloco, :tipo, :prazo, 1, :obrig, :foto, 0)
        ON DUPLICATE KEY UPDATE 
            descricao = VALUES(descricao), 
            item_normam = VALUES(item_normam),
            obrigatoria = VALUES(obrigatoria),
            exige_foto = VALUES(exige_foto)");

    foreach ($novas as $n) {
        $stmtIns->execute([
            ':id' => $n['id'],
            ':codigo' => $n['codigo'],
            ':cat' => $n['cat'],
            ':descr' => $n['descr'],
            ':normam' => $n['normam'],
            ':bloco' => $n['bloco'],
            ':tipo' => $n['bloco'],
            ':prazo' => $n['prazo'],
            ':obrig' => $n['obrig'],
            ':foto' => $n['foto']
        ]);
        echo "Item {$n['codigo']} inserido/atualizado com sucesso." . PHP_EOL;
    }

    $total = $pdo->query("SELECT COUNT(*) FROM exigencias_catalogo WHERE ativo = 1")->fetchColumn();
    $obrig = $pdo->query("SELECT COUNT(*) FROM exigencias_catalogo WHERE ativo = 1 AND obrigatoria = 1")->fetchColumn();
    $foto = $pdo->query("SELECT COUNT(*) FROM exigencias_catalogo WHERE ativo = 1 AND exige_foto = 1")->fetchColumn();

    echo "Status Final do Catálogo:" . PHP_EOL;
    echo "- Total de Exigências Ativas: {$total}" . PHP_EOL;
    echo "- Exigências Obrigatórias: {$obrig}" . PHP_EOL;
    echo "- Exigências com Foto Obrigatória: {$foto}" . PHP_EOL;
    echo "MIGRAÇÃO CONCLUÍDA COM SUCESSO!" . PHP_EOL;

} catch (Exception $e) {
    echo "ERRO NA MIGRAÇÃO: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
