<?php

/**
 * Recursos opcionais para acelerar testes manuais do cadastro de embarcações.
 * Os dados só são enviados ao navegador quando a configuração estiver ativa.
 */

function dadosTesteEmbarcacoesAtivos(PDO $pdo): bool
{
    try {
        $stmt = $pdo->prepare("SELECT valor FROM configuracoes WHERE chave = 'dados_teste_embarcacoes' LIMIT 1");
        $stmt->execute();
        return $stmt->fetchColumn() === '1';
    } catch (Throwable $e) {
        error_log('Erro ao consultar modo de dados de teste: ' . $e->getMessage());
        return false;
    }
}

function perfisDadosTesteEmbarcacoes(array $tiposEmbarcacao): array
{
    $tiposPorNome = [];
    foreach ($tiposEmbarcacao as $tipo) {
        $tiposPorNome[mb_strtolower(trim((string)$tipo['nome']), 'UTF-8')] = (string)$tipo['id'];
    }

    $tipoId = static function (string $nome) use ($tiposPorNome): string {
        return $tiposPorNome[mb_strtolower($nome, 'UTF-8')] ?? '';
    };

    $base = [
        'nome' => 'EMBARCAÇÃO TESTE',
        'tipo_embarcacao_id' => '',
        'ano' => '2022',
        'porto_inscricao' => 'Manaus - AM',
        'numero_inscricao' => 'TESTE000000',
        'indicativo_chamada' => 'TESTE01',
        'observacoes' => 'Cadastro fictício gerado pelo preenchimento rápido para validação do fluxo do sistema.',
        'possui_propulsao' => '1',
        'fabricante_motor' => 'Cummins',
        'modelo_motor' => 'QSB 6.7',
        'numero_motor' => 'MOTOR-TESTE-000',
        'potencia_kw' => '224 kW',
        'material_casco' => 'Aço',
        'tipo_navegacao' => 'Interior',
        'area_navegacao' => 'Área 1',
        'tipo_servico' => 'Transporte de Passageiros',
        'autorizado_carga' => '1',
        'numero_tripulantes' => '4',
        'numero_passageiros_n1' => '20',
        'numero_passageiros_n2' => '10',
        'obs_passageiros' => 'Lotação fictícia para teste',
        'acessibilidade' => '1',
        'comprimento_total' => '18.50',
        'comprimento_casco' => '17.90',
        'comprimento_lpp' => '16.80',
        'pontal_moldado' => '2.40',
        'boca_moldada' => '5.20',
        'boca_maxima' => '5.45',
        'arqueacao_bruta' => '68.50',
        'arqueacao_liquida' => '31.20',
        'metodo_arqueacao' => 'Regra I',
        'local_construcao' => 'Manaus - AM',
        'numero_casco' => 'CASCO-TESTE-000',
        'porte_bruto' => '42.80',
        'estaleiro_nome' => 'Estaleiro Modelo Testes Ltda.',
        'estaleiro_cpf_cnpj' => '12.345.678/0001-90',
        'estaleiro_endereco' => 'Avenida Naval, 1000, Distrito Industrial, Manaus - AM',
        'cnarq_data_quilha' => '15/03/2021',
        'cnarq_calado_moldado_m' => '1.650',
        'cnarq_data_local_arqueacao_original' => 'Manaus - AM, 20 de junho de 2022',
        'cnarq_data_local_ultima_rearqueacao' => 'Não se aplica - primeira arqueação',
        'cnarq_espacos_incluidos_ab' => "Casa de máquinas | Popa | 4,20\nSalão de passageiros | Meia-nau | 8,50\nPique de proa | Proa | 2,10",
        'cnarq_espacos_incluidos_al' => "Salão de passageiros | Meia-nau | 8,50\nCompartimento de carga | Proa | 3,20",
        'cnarq_espacos_excluidos_m3' => '3.40',
        'cnbl_tipo_embarcacao' => 'B',
        'cnbl_area_navegacao' => 'Área 1',
        'borda_livre_mm' => '620',
        'borda_livre_tipo' => 'Tipo B',
        'calado_maximo_m' => '1.80',
        'aresta_superior_linha_conves' => '620',
        'centro_disco_situado' => '740',
        'acrescimo_agua_salgada' => '35',
        'dist_linha_conves_bico_proa' => '8200',
        'dist_linha_conves_abaixo_disco' => '120',
        'marca_linha_carga_area1' => '180',
        'marca_linha_carga_area2' => '240',
    ];

    return [
        'lancha' => [
            'label' => 'Lancha de passageiros',
            'description' => 'Embarcação rápida, com propulsão e 30 passageiros.',
            'dados' => array_replace($base, [
                'nome' => 'LANCHA TESTE AMAZÔNIA',
                'tipo_embarcacao_id' => $tipoId('Lancha'),
                'ano' => '2023',
                'material_casco' => 'Fibra de Vidro',
                'fabricante_motor' => 'Yamaha',
                'modelo_motor' => 'F300 BETX',
                'potencia_kw' => '224 kW / 300 HP',
                'tipo_servico' => 'Transporte de Passageiros',
                'autorizado_carga' => '0',
                'numero_tripulantes' => '3',
                'numero_passageiros_n1' => '20',
                'numero_passageiros_n2' => '10',
                'comprimento_total' => '12.80',
                'comprimento_casco' => '12.20',
                'comprimento_lpp' => '11.60',
                'pontal_moldado' => '1.85',
                'boca_moldada' => '3.75',
                'boca_maxima' => '3.90',
                'arqueacao_bruta' => '24.60',
                'arqueacao_liquida' => '11.30',
                'porte_bruto' => '8.40',
                'cnarq_calado_moldado_m' => '1.050',
                'cnbl_tipo_embarcacao' => 'C',
                'borda_livre_mm' => '480',
                'calado_maximo_m' => '1.20',
            ]),
        ],
        'empurrador' => [
            'label' => 'Empurrador fluvial',
            'description' => 'Embarcação de trabalho com motor diesel e operação de carga.',
            'dados' => array_replace($base, [
                'nome' => 'EMPURRADOR TESTE SOLIMÕES',
                'tipo_embarcacao_id' => $tipoId('Empurrador'),
                'ano' => '2020',
                'fabricante_motor' => 'Caterpillar',
                'modelo_motor' => 'C18 ACERT',
                'potencia_kw' => '447 kW / 600 HP',
                'tipo_navegacao' => 'Apoio Portuário',
                'tipo_servico' => 'Empurra',
                'numero_tripulantes' => '8',
                'numero_passageiros_n1' => '0',
                'numero_passageiros_n2' => '0',
                'obs_passageiros' => 'Sem transporte de passageiros',
                'acessibilidade' => '0',
                'comprimento_total' => '22.40',
                'comprimento_casco' => '21.80',
                'comprimento_lpp' => '20.30',
                'pontal_moldado' => '3.10',
                'boca_moldada' => '7.80',
                'boca_maxima' => '8.00',
                'arqueacao_bruta' => '146.80',
                'arqueacao_liquida' => '54.20',
                'porte_bruto' => '96.50',
                'cnarq_calado_moldado_m' => '2.150',
                'cnbl_tipo_embarcacao' => 'B',
                'borda_livre_mm' => '760',
                'calado_maximo_m' => '2.35',
            ]),
        ],
        'balsa' => [
            'label' => 'Balsa de carga',
            'description' => 'Embarcação sem propulsão para testar o fluxo sem motor.',
            'dados' => array_replace($base, [
                'nome' => 'BALSA TESTE RIO NEGRO',
                'tipo_embarcacao_id' => $tipoId('Balsa'),
                'ano' => '2019',
                'possui_propulsao' => '0',
                'fabricante_motor' => '',
                'modelo_motor' => '',
                'numero_motor' => '',
                'potencia_kw' => '',
                'tipo_servico' => 'Transporte de Carga',
                'numero_tripulantes' => '2',
                'numero_passageiros_n1' => '0',
                'numero_passageiros_n2' => '0',
                'obs_passageiros' => 'Não autorizada a transportar passageiros',
                'acessibilidade' => '0',
                'comprimento_total' => '48.00',
                'comprimento_casco' => '47.50',
                'comprimento_lpp' => '45.80',
                'pontal_moldado' => '3.80',
                'boca_moldada' => '12.00',
                'boca_maxima' => '12.20',
                'arqueacao_bruta' => '498.70',
                'arqueacao_liquida' => '224.40',
                'porte_bruto' => '720.00',
                'cnarq_calado_moldado_m' => '2.700',
                'cnbl_tipo_embarcacao' => 'A',
                'borda_livre_tipo' => 'Tipo A',
                'borda_livre_mm' => '920',
                'calado_maximo_m' => '2.95',
            ]),
        ],
    ];
}
