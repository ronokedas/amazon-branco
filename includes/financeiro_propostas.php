<?php
/**
 * Agenda financeira das propostas comerciais.
 *
 * Cada parcela é um lançamento próprio no Financeiro. A entrada, quando
 * informada, fica como lançamento quitado separado e não reduz a quantidade
 * de parcelas contratadas.
 */

function financeiroMontarAgendaParcelasProposta(
    float $valorTotal,
    float $valorEntrada,
    int $quantidadeParcelas,
    string $dataAssinatura
): array {
    $totalCentavos = max(0, (int)round($valorTotal * 100));
    $entradaCentavos = min($totalCentavos, max(0, (int)round($valorEntrada * 100)));
    $saldoCentavos = $totalCentavos - $entradaCentavos;
    $quantidadeParcelas = max(1, min(24, $quantidadeParcelas));

    $dataBaseTexto = substr(trim($dataAssinatura), 0, 10);
    $dataBase = DateTimeImmutable::createFromFormat('!Y-m-d', $dataBaseTexto);
    $errosData = DateTimeImmutable::getLastErrors();
    if (
        !$dataBase
        || $dataBase->format('Y-m-d') !== $dataBaseTexto
        || (is_array($errosData) && ($errosData['warning_count'] > 0 || $errosData['error_count'] > 0))
    ) {
        throw new InvalidArgumentException('Data de assinatura inválida para gerar o parcelamento.');
    }

    $agenda = [];
    if ($entradaCentavos > 0) {
        $agenda[] = [
            'tipo' => 'ENTRADA',
            'numero_parcela' => 0,
            'total_parcelas' => $quantidadeParcelas,
            'valor' => number_format($entradaCentavos / 100, 2, '.', ''),
            'data_vencimento' => $dataBase->format('Y-m-d'),
            'status' => 'PAGO',
        ];
    }

    if ($saldoCentavos <= 0) {
        return $agenda;
    }

    // Distribui os centavos restantes sem criar parcelas negativas ou
    // alterar o total devido; a diferença máxima entre parcelas é de R$ 0,01.
    $valorBaseCentavos = intdiv($saldoCentavos, $quantidadeParcelas);
    $centavosExtras = $saldoCentavos % $quantidadeParcelas;

    for ($numero = 1; $numero <= $quantidadeParcelas; $numero++) {
        $valorCentavos = $valorBaseCentavos + ($numero <= $centavosExtras ? 1 : 0);
        $vencimento = $dataBase->modify('+' . ($numero * 30) . ' days');

        $agenda[] = [
            'tipo' => 'PARCELA',
            'numero_parcela' => $numero,
            'total_parcelas' => $quantidadeParcelas,
            'valor' => number_format($valorCentavos / 100, 2, '.', ''),
            'data_vencimento' => $vencimento->format('Y-m-d'),
            'status' => 'PENDENTE',
        ];
    }

    return $agenda;
}

/**
 * Gera a agenda financeira uma única vez, dentro da mesma transação
 * que confirma a assinatura/aprovação da proposta.
 */
function gerarLancamentosFinanceirosProposta(
    PDO $pdo,
    array $proposta,
    ?string $criadoPor,
    bool $aprovacaoInterna = false,
    ?string $dataReferencia = null
): void {
    $propostaId = (string)($proposta['id'] ?? '');
    if ($propostaId === '') {
        throw new RuntimeException('ID da proposta não informado para gerar o Financeiro.');
    }

    // Não converte nem duplica lançamentos antigos de propostas já assinadas.
    $stmtExistente = $pdo->prepare('SELECT COUNT(*) FROM financeiro_lancamentos WHERE proposta_id = :proposta');
    $stmtExistente->execute([':proposta' => $propostaId]);
    if ((int)$stmtExistente->fetchColumn() > 0) {
        return;
    }

    $stmtColunasParcelas = $pdo->query("
        SELECT COUNT(*)
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'financeiro_lancamentos'
          AND COLUMN_NAME IN ('numero_parcela', 'total_parcelas')
    ");
    if ((int)$stmtColunasParcelas->fetchColumn() !== 2) {
        throw new RuntimeException(
            'O Financeiro ainda não está preparado para parcelas individuais. '
            . 'Aplique a migration 137 executando: php scripts/run_migration_137.php'
        );
    }

    $escritorioId = (string)($proposta['escritorio_id'] ?? '');
    if ($escritorioId === '') {
        throw new RuntimeException('A proposta não possui um escritório responsável.');
    }

    $responsavelVendaId = financeiroResponsavelVenda($pdo, $proposta['criado_por'] ?? null);
    $valorTotal = round(max(0, (float)($proposta['valor_total'] ?? 0)), 2);
    $valorEntrada = round(max(0, min($valorTotal, (float)($proposta['valor_entrada'] ?? 0))), 2);
    $quantidadeParcelas = (int)($proposta['parcelas'] ?? 1);
    $dataAssinatura = $dataReferencia
        ?? (string)($proposta['assinatura_em'] ?? date('Y-m-d'));

    $agenda = financeiroMontarAgendaParcelasProposta(
        $valorTotal,
        $valorEntrada,
        $quantidadeParcelas,
        $dataAssinatura
    );
    if (empty($agenda)) {
        throw new RuntimeException('A proposta não possui valores para gerar lançamentos financeiros.');
    }

    $numeroProposta = (string)($proposta['numero'] ?? $propostaId);
    $formaEntrada = in_array(
        (string)($proposta['forma_pagamento'] ?? ''),
        ['a_vista', 'parcelado', 'boleto', 'pix'],
        true
    ) ? (string)$proposta['forma_pagamento'] : 'a_vista';

    $stmtLancamento = $pdo->prepare("
        INSERT INTO financeiro_lancamentos (
            id, tipo, frequencia, status, data_vencimento, cliente_id, descricao,
            valor, valor_original, saldo_devedor, data, categoria, observacoes,
            criado_por, escritorio_id, responsavel_usuario_id, proposta_id,
            numero_parcela, total_parcelas
        ) VALUES (
            :id, 'RECEITA', 'unica', :status, :data_vencimento, :cliente_id, :descricao,
            :valor, :valor_original, :saldo_devedor, :data, 'SERVIÇOS', :observacoes,
            :criado_por, :escritorio, :responsavel, :proposta,
            :numero_parcela, :total_parcelas
        )
    ");

    $stmtBaixaEntrada = $pdo->prepare("
        INSERT INTO financeiro_historico_baixas
            (id, lancamento_id, valor_pago, data_pagamento, forma_pagamento, criado_por)
        VALUES
            (:id, :lancamento_id, :valor_pago, :data_pagamento, :forma_pagamento, :criado_por)
    ");

    foreach ($agenda as $item) {
        $ehEntrada = $item['tipo'] === 'ENTRADA';
        $valor = (string)$item['valor'];
        $descricao = 'Referente à Proposta Comercial nº ' . $numeroProposta;
        $observacoes = $aprovacaoInterna
            ? 'Gerado após autorização interna da proposta nº ' . $numeroProposta . '.'
            : 'Gerado após assinatura da proposta nº ' . $numeroProposta . '.';

        if ($ehEntrada) {
            $descricao .= ' — Entrada';
            $observacoes .= ' Entrada recebida integralmente na data da assinatura.';
        } else {
            $descricao .= ' — Parcela ' . $item['numero_parcela'] . '/' . $item['total_parcelas'];
            $observacoes .= ' Parcela ' . $item['numero_parcela'] . ' de ' . $item['total_parcelas']
                . ', com vencimento em ' . date('d/m/Y', strtotime($item['data_vencimento'])) . '.';
        }

        $lancamentoId = gerarUUID();
        $saldoDevedor = $ehEntrada ? '0.00' : $valor;

        $stmtLancamento->execute([
            ':id' => $lancamentoId,
            ':status' => $item['status'],
            ':data_vencimento' => $item['data_vencimento'],
            ':cliente_id' => $proposta['cliente_id'],
            ':descricao' => $descricao,
            ':valor' => $valor,
            ':valor_original' => $valor,
            ':saldo_devedor' => $saldoDevedor,
            ':data' => $item['data_vencimento'],
            ':observacoes' => $observacoes,
            ':criado_por' => $criadoPor,
            ':escritorio' => $escritorioId,
            ':responsavel' => $responsavelVendaId,
            ':proposta' => $propostaId,
            ':numero_parcela' => $item['numero_parcela'],
            ':total_parcelas' => $item['total_parcelas'],
        ]);

        if ($ehEntrada) {
            $stmtBaixaEntrada->execute([
                ':id' => gerarUUID(),
                ':lancamento_id' => $lancamentoId,
                ':valor_pago' => $valor,
                ':data_pagamento' => $item['data_vencimento'],
                ':forma_pagamento' => $formaEntrada,
                ':criado_por' => $criadoPor,
            ]);
        }
    }
}
