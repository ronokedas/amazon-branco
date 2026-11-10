<?php
/**
 * Helpers para gestão e consolidação de múltiplos motores propulsores por embarcação.
 * Preserva integralmente a compatibilidade com as colunas legadas de `embarcacoes` e emissão CSN/NORMAM.
 */

if (!function_exists('embarcacaoNormalizarListaMotores')) {
    /**
     * Normaliza lista de motores vindos do POST (`motores[]`) ou das colunas legadas simples.
     *
     * @param mixed $motoresInput Array enviado pelo formulário dinâmico ou null
     * @param array|null $fallbackSimples Campos legados (`fabricante_motor`, `modelo_motor`, `numero_motor`, `potencia_kw`)
     * @return array Lista normalizada indexada a partir de 0 com campo `ordem` iniciando em 1
     */
    function embarcacaoNormalizarListaMotores($motoresInput, ?array $fallbackSimples = null): array
    {
        $listaBruta = [];

        if (is_array($motoresInput)) {
            $listaBruta = $motoresInput;
        } elseif (is_array($fallbackSimples)) {
            $listaBruta = [[
                'posicao' => $fallbackSimples['posicao'] ?? '',
                'fabricante' => $fallbackSimples['fabricante_motor'] ?? ($fallbackSimples['fabricante'] ?? ''),
                'modelo' => $fallbackSimples['modelo_motor'] ?? ($fallbackSimples['modelo'] ?? ''),
                'numero' => $fallbackSimples['numero_motor'] ?? ($fallbackSimples['numero'] ?? ''),
                'potencia_kw' => $fallbackSimples['potencia_kw'] ?? '',
            ]];
        }

        $normalizados = [];
        $ordem = 1;
        foreach ($listaBruta as $item) {
            if (!is_array($item)) {
                continue;
            }
            $posicao = trim((string)($item['posicao'] ?? ''));
            $fabricante = trim((string)($item['fabricante'] ?? ''));
            $modelo = trim((string)($item['modelo'] ?? ''));
            $numero = trim((string)($item['numero'] ?? ''));
            $potencia = trim((string)($item['potencia_kw'] ?? ''));

            if ($posicao === '' && $fabricante === '' && $modelo === '' && $numero === '' && $potencia === '') {
                continue;
            }

            $normalizados[] = [
                'ordem' => $ordem++,
                'posicao' => mb_substr($posicao, 0, 60, 'UTF-8'),
                'fabricante' => mb_substr($fabricante, 0, 300, 'UTF-8'),
                'modelo' => mb_substr($modelo, 0, 150, 'UTF-8'),
                'numero' => mb_substr($numero, 0, 100, 'UTF-8'),
                'potencia_kw' => mb_substr($potencia, 0, 50, 'UTF-8'),
            ];
        }

        return $normalizados;
    }
}

if (!function_exists('embarcacaoCalcularPotenciaTotal')) {
    /**
     * Soma a potência propulsiva total de múltiplos motores (kW e/ou HP/CV) com fallback textual seguro.
     */
    function embarcacaoCalcularPotenciaTotal(array $motores): string
    {
        $potencias = [];
        foreach ($motores as $m) {
            $val = trim((string)($m['potencia_kw'] ?? ''));
            if ($val !== '') {
                $potencias[] = $val;
            }
        }

        if (empty($potencias)) {
            return '';
        }
        if (count($potencias) === 1) {
            return $potencias[0];
        }

        $fmtNumero = static function (float $v): string {
            if (abs($v - round($v)) < 0.0001) {
                return (string)(int)round($v);
            }
            return rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
        };

        $somaKw = 0.0;
        $todosTemKw = true;
        $teveUnidadeKw = false;

        $somaAlt = 0.0;
        $todosTemAlt = true;
        $unidadeAlt = null;

        foreach ($potencias as $p) {
            $kwMatch = preg_match('/(\d+(?:[.,]\d+)?)\s*kW\b/i', $p, $mKw) === 1;
            $bareMatch = !$kwMatch && preg_match('/^\d+(?:[.,]\d+)?$/', $p) === 1;
            if ($kwMatch) {
                $somaKw += (float)str_replace(',', '.', $mKw[1]);
                $teveUnidadeKw = true;
            } elseif ($bareMatch) {
                $somaKw += (float)str_replace(',', '.', $p);
            } else {
                $todosTemKw = false;
            }

            if (preg_match('/(\d+(?:[.,]\d+)?)\s*(HP|CV)\b/i', $p, $mAlt) === 1) {
                $u = strtoupper($mAlt[2]);
                if ($unidadeAlt === null) {
                    $unidadeAlt = $u;
                } elseif ($unidadeAlt !== $u) {
                    $todosTemAlt = false;
                }
                $somaAlt += (float)str_replace(',', '.', $mAlt[1]);
            } else {
                $todosTemAlt = false;
            }
        }

        if ($todosTemKw) {
            $textoKw = $fmtNumero($somaKw) . ($teveUnidadeKw ? ' kW' : '');
            if ($todosTemAlt && $unidadeAlt !== null) {
                return $textoKw . ' / ' . $fmtNumero($somaAlt) . ' ' . $unidadeAlt;
            }
            return $textoKw;
        }

        if ($todosTemAlt && $unidadeAlt !== null) {
            return $fmtNumero($somaAlt) . ' ' . $unidadeAlt;
        }

        return implode(' + ', $potencias);
    }
}

if (!function_exists('embarcacaoFormatarMotoresResumo')) {
    /**
     * Formata os motores da embarcação para exibição tabular/CSN ("Fabricante, Modelo e Número do Motor").
     */
    function embarcacaoFormatarMotoresResumo(array $motores): array
    {
        $qtd = count($motores);
        $potenciaTotal = embarcacaoCalcularPotenciaTotal($motores);

        if ($qtd === 0) {
            return [
                'quantidade' => 0,
                'texto' => '',
                'potencia_total' => '',
            ];
        }

        if ($qtd === 1) {
            $m = $motores[0];
            $spec = implode(' - ', array_filter([
                trim((string)($m['fabricante'] ?? '')),
                trim((string)($m['modelo'] ?? '')),
                trim((string)($m['numero'] ?? '')),
            ], static fn($v) => $v !== ''));
            $pos = trim((string)($m['posicao'] ?? ''));
            $texto = ($pos !== '' && $spec !== '') ? "{$pos}: {$spec}" : ($spec !== '' ? $spec : $pos);

            return [
                'quantidade' => 1,
                'texto' => $texto,
                'potencia_total' => $potenciaTotal,
            ];
        }

        $linhas = [];
        foreach ($motores as $idx => $m) {
            $spec = implode(' - ', array_filter([
                trim((string)($m['fabricante'] ?? '')),
                trim((string)($m['modelo'] ?? '')),
                trim((string)($m['numero'] ?? '')),
            ], static fn($v) => $v !== ''));
            $pos = trim((string)($m['posicao'] ?? ''));
            $prefixo = $pos !== '' ? $pos : 'Motor ' . ($idx + 1);

            if ($spec !== '') {
                $linhas[] = "{$prefixo}: {$spec}";
            } elseif ($pos !== '') {
                $linhas[] = $pos;
            }
        }

        return [
            'quantidade' => $qtd,
            'texto' => implode("\n", $linhas),
            'potencia_total' => $potenciaTotal,
        ];
    }
}

if (!function_exists('embarcacaoBuscarMotores')) {
    /**
     * Carrega os motores cadastrados em `embarcacoes_motores`, com fallback transparente para o registro legado.
     */
    function embarcacaoBuscarMotores(PDO $pdo, string $embarcacaoId, ?array $embarcacaoFallback = null): array
    {
        $motores = [];
        if ($embarcacaoId !== '') {
            try {
                $stmt = $pdo->prepare("
                    SELECT id, embarcacao_id, ordem, posicao, fabricante, modelo, numero, potencia_kw
                    FROM embarcacoes_motores
                    WHERE embarcacao_id = :id
                    ORDER BY ordem ASC, criado_em ASC
                ");
                $stmt->execute([':id' => $embarcacaoId]);
                $motores = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            } catch (Throwable $e) {
                // Fallback silencioso caso a tabela ainda não exista em ambiente de migração pendente
                $motores = [];
            }
        }

        if (empty($motores) && is_array($embarcacaoFallback)) {
            $fab = trim((string)($embarcacaoFallback['fabricante_motor'] ?? ''));
            $mod = trim((string)($embarcacaoFallback['modelo_motor'] ?? ''));
            $num = trim((string)($embarcacaoFallback['numero_motor'] ?? ''));
            $pot = trim((string)($embarcacaoFallback['potencia_kw'] ?? ''));
            if ($fab !== '' || $mod !== '' || $num !== '' || $pot !== '') {
                $motores[] = [
                    'id' => '',
                    'embarcacao_id' => $embarcacaoId,
                    'ordem' => 1,
                    'posicao' => '',
                    'fabricante' => $fab,
                    'modelo' => $mod,
                    'numero' => $num,
                    'potencia_kw' => $pot,
                ];
            }
        }

        return $motores;
    }
}

if (!function_exists('embarcacaoSalvarMotores')) {
    /**
     * Persiste a lista completa de motores propulsores da embarcação.
     */
    function embarcacaoSalvarMotores(PDO $pdo, string $embarcacaoId, ?int $possuiPropulsao, array $motoresNormalizados): void
    {
        if ($embarcacaoId === '') {
            return;
        }

        try {
            $stmtDel = $pdo->prepare("DELETE FROM embarcacoes_motores WHERE embarcacao_id = :emb_id");
            $stmtDel->execute([':emb_id' => $embarcacaoId]);

            if ($possuiPropulsao === 0 || empty($motoresNormalizados)) {
                return;
            }

            $stmtIns = $pdo->prepare("
                INSERT INTO embarcacoes_motores (id, embarcacao_id, ordem, posicao, fabricante, modelo, numero, potencia_kw)
                VALUES (:id, :embarcacao_id, :ordem, :posicao, :fabricante, :modelo, :numero, :potencia_kw)
            ");

            $ordem = 1;
            foreach ($motoresNormalizados as $m) {
                $stmtIns->execute([
                    ':id' => gerarUUID(),
                    ':embarcacao_id' => $embarcacaoId,
                    ':ordem' => $ordem++,
                    ':posicao' => ($m['posicao'] ?? '') !== '' ? $m['posicao'] : null,
                    ':fabricante' => ($m['fabricante'] ?? '') !== '' ? $m['fabricante'] : null,
                    ':modelo' => ($m['modelo'] ?? '') !== '' ? $m['modelo'] : null,
                    ':numero' => ($m['numero'] ?? '') !== '' ? $m['numero'] : null,
                    ':potencia_kw' => ($m['potencia_kw'] ?? '') !== '' ? $m['potencia_kw'] : null,
                ]);
            }
        } catch (Throwable $e) {
            error_log('Erro ao sincronizar embarcacoes_motores: ' . $e->getMessage());
        }
    }
}

if (!function_exists('embarcacaoValidarMotoresCompletosParaCSN')) {
    /**
     * Valida se todos os motores cadastrados possuem fabricante, modelo, número e potência para emissão de CSN.
     *
     * @return string|null Mensagem de erro se incompleto, ou null se válido
     */
    function embarcacaoValidarMotoresCompletosParaCSN(array $dadosEmb, array $motores = []): ?string
    {
        if ((int)($dadosEmb['possui_propulsao'] ?? 0) !== 1) {
            return null;
        }

        if (!empty($motores)) {
            $total = count($motores);
            foreach ($motores as $idx => $m) {
                $fab = trim((string)($m['fabricante'] ?? ''));
                $mod = trim((string)($m['modelo'] ?? ''));
                $num = trim((string)($m['numero'] ?? ''));
                $pot = trim((string)($m['potencia_kw'] ?? ''));

                if ($fab === '' || $mod === '' || $num === '' || $pot === '') {
                    if ($total > 1) {
                        $pos = trim((string)($m['posicao'] ?? ''));
                        $rotulo = $pos !== '' ? $pos : 'Motor ' . ($idx + 1);
                        return "A embarcação possui propulsão. Preencha fabricante, modelo, número do motor e potência de todos os motores ({$rotulo} incompleto) antes de gerar o CSN.";
                    }
                    return 'A embarcação possui propulsão. Preencha fabricante, modelo, número do motor e potência antes de gerar o CSN.';
                }
            }
            return null;
        }

        $fabricanteMotor = trim((string)($dadosEmb['fabricante_motor'] ?? ''));
        $modeloMotor = trim((string)($dadosEmb['modelo_motor'] ?? ''));
        $numeroMotor = trim((string)($dadosEmb['numero_motor'] ?? ''));
        $potenciaKw = trim((string)($dadosEmb['potencia_kw'] ?? ''));

        if ($fabricanteMotor === '' || $modeloMotor === '' || $numeroMotor === '' || $potenciaKw === '') {
            return 'A embarcação possui propulsão. Preencha fabricante, modelo, número do motor e potência antes de gerar o CSN.';
        }

        return null;
    }
}
