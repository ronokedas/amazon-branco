<?php
/**
 * Script de Saneamento: Limpeza de agendamentos duplicados, higienização de serviços de vistoria e auto-atribuição de analista
 */
require_once __DIR__ . '/../config.php';

echo "=== INICIANDO SANEAMENTO OPERACIONAL DE AGENDAMENTOS E ANÁLISES ===" . PHP_EOL;

try {
    // 1. Higienizar tipo_vistoria em agendamentos existentes (remover referências a Análise de Planos)
    $stmtAg = $pdo->query("SELECT id, tipo_vistoria FROM agendamentos WHERE tipo_vistoria LIKE '%Análise de Planos%' OR tipo_vistoria LIKE '%Analise de Planos%'");
    $agendamentosParaLimpar = $stmtAg->fetchAll(PDO::FETCH_ASSOC);
    $limpos = 0;

    $stmtUpdTipo = $pdo->prepare("UPDATE agendamentos SET tipo_vistoria = :tipo WHERE id = :id");
    foreach ($agendamentosParaLimpar as $ag) {
        $antigo = $ag['tipo_vistoria'];
        $novo = preg_replace('/,?\s*An[aá]lise de Planos\s*(Ec[12])?/iu', '', $antigo);
        $novo = trim($novo, " ,\t\n\r");
        if ($novo === '') {
            $novo = 'Vistoria Geral';
        }
        $stmtUpdTipo->execute([':tipo' => $novo, ':id' => $ag['id']]);
        $limpos++;
        echo "  [✓] Agendamento {$ag['id']}: '{$antigo}' -> '{$novo}'" . PHP_EOL;
    }
    echo "Total de tipos de vistoria higienizados: {$limpos}" . PHP_EOL;

    // 2. Remover agendamentos duplicados órfãos
    $stmtDupes = $pdo->query("
        SELECT a_orfao.id AS id_remover, a_orfao.proposta_id, a_ativo.id AS id_manter
        FROM agendamentos a_orfao
        JOIN agendamentos a_ativo ON a_ativo.proposta_id = a_orfao.proposta_id 
                                 AND a_ativo.embarcacao_id = a_orfao.embarcacao_id 
                                 AND a_ativo.id <> a_orfao.id
        WHERE a_orfao.status = 'pendente'
          AND (a_orfao.vistoriador_id IS NULL OR a_orfao.vistoriador_id = '')
          AND a_orfao.data_vistoria IS NULL
          AND (a_ativo.vistoriador_id IS NOT NULL OR a_ativo.data_vistoria IS NOT NULL OR a_ativo.status IN ('confirmado', 'em_andamento', 'concluido'))
    ");
    $duplicados = $stmtDupes->fetchAll(PDO::FETCH_ASSOC);
    $removidos = 0;
    $stmtDel = $pdo->prepare("DELETE FROM agendamentos WHERE id = :id");

    foreach ($duplicados as $dup) {
        $stmtDel->execute([':id' => $dup['id_remover']]);
        $removidos++;
        echo "  [✓] Rascunho duplicado órfão removido: {$dup['id_remover']} (Mantido: {$dup['id_manter']})" . PHP_EOL;
    }
    echo "Total de agendamentos duplicados removidos: {$removidos}" . PHP_EOL;

    // 3. Atribuir automaticamente Analista Naval ativo em análises de planos que estejam sem analista
    $analistaPadrao = $pdo->query("
        SELECT DISTINCT u.id, u.nome
        FROM usuarios u
        LEFT JOIN usuario_perfis up ON up.usuario_id = u.id
        WHERE u.ativo = 1
          AND u.excluido_em IS NULL
          AND (u.cargo = 'ANALISTA' OR up.perfil = 'ANALISTA')
        ORDER BY u.id ASC
        LIMIT 1
    ")->fetch(PDO::FETCH_ASSOC);

    if ($analistaPadrao) {
        $stmtAnalisesOrfas = $pdo->query("SELECT id, numero FROM analises_planos WHERE analista_id IS NULL OR analista_id = ''");
        $analisesOrfas = $stmtAnalisesOrfas->fetchAll(PDO::FETCH_ASSOC);
        $atribuidas = 0;
        $stmtUpdAnalise = $pdo->prepare("
            UPDATE analises_planos 
            SET analista_id = :analista, 
                prazo_agendado_em = COALESCE(prazo_agendado_em, DATE_ADD(NOW(), INTERVAL 7 DAY)),
                status = IF(status = 'AGUARDANDO_AGENDAMENTO', 'AGENDADA', status)
            WHERE id = :id
        ");
        foreach ($analisesOrfas as $an) {
            $stmtUpdAnalise->execute([':analista' => $analistaPadrao['id'], ':id' => $an['id']]);
            $atribuidas++;
            echo "  [✓] Análise {$an['numero']} ({$an['id']}) atribuída ao analista {$analistaPadrao['nome']}" . PHP_EOL;
        }
        echo "Total de análises sem analista atribuídas: {$atribuidas}" . PHP_EOL;
    }

    echo "=== SANEAMENTO CONCLUÍDO COM SUCESSO! ===" . PHP_EOL;
} catch (Throwable $e) {
    echo "ERRO NO SANEAMENTO: " . $e->getMessage() . PHP_EOL . $e->getTraceAsString() . PHP_EOL;
    exit(1);
}
