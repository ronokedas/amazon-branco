<?php
/**
 * MODULO: AGENDAMENTOS
 * Arquivo: os.php - Exibe a Ordem de Servico completa com dados da embarcacao,
 *                      cliente, contato, tipo de vistoria e opcao de imprimir.
 * ACESSO: ?id=UUID (OS id) — ADMIN ve todas, VISTORIADOR apenas as dele
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
$cargo = getCargo();
exigirAcesso('agendamentos');

$usuario_id = $_SESSION['usuario_id'];
$os_id = $_GET['id'] ?? '';

if (empty($os_id)) {
    setMensagem('error', 'ID da Ordem de Serviço não informado.');
    redirecionar(APP_URL . 'agendamentos');
}

try {
    // Buscar OS completa com todos os dados
    $stmt = $pdo->prepare("
        SELECT 
            os.*,
            c.nome          AS cliente_nome,
            c.cpf_cnpj      AS cliente_cpfcnpj,
            c.telefone      AS cliente_telefone,
            c.email         AS cliente_email,
            c.endereco      AS cliente_endereco,
            c.perfil        AS cliente_perfil,
            c.tipo_pessoa   AS cliente_tipo_pessoa,
            e.nome                  AS embarcacao_nome,
            e.registro              AS embarcacao_registro,
            e.tipo_embarcacao       AS embarcacao_tipo,
            e.ano                   AS embarcacao_ano,
            e.comprimento_total,
            e.comprimento_casco,
            e.comprimento_lpp,
            e.pontal_moldado,
            e.boca_moldada,
            e.boca_maxima,
            e.material_casco,
            e.tipo_servico,
            e.tipo_navegacao,
            e.area_navegacao,
            e.arqueacao_bruta,
            e.numero_inscricao,
            e.porto_inscricao,
            e.indicativo_chamada,
            e.numero_tripulantes,
            e.numero_passageiros_n1,
            e.numero_passageiros_n2,
            u.nome          AS vistoriador_nome,
            u.email         AS vistoriador_email,
            criador.nome    AS criado_por_nome,
            a.status        AS agendamento_status,
            a.id            AS agendamento_id,
            p.numero        AS proposta_numero
        FROM ordens_servico os
        INNER JOIN clientes c     ON os.cliente_id    = c.id
        INNER JOIN embarcacoes e  ON os.embarcacao_id = e.id
        INNER JOIN usuarios u     ON os.vistoriador_id = u.id
        INNER JOIN agendamentos a ON os.agendamento_id = a.id
        LEFT  JOIN propostas p    ON os.proposta_id    = p.id
        LEFT  JOIN usuarios criador ON os.criado_por   = criador.id
        WHERE os.id = :id
    ");
    $stmt->execute([':id' => $os_id]);
    $os = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$os) {
        setMensagem('error', 'Ordem de Serviço não encontrada.');
        redirecionar(APP_URL . 'agendamentos');
    }

    // VISTORIADOR so pode ver a propria OS
    if ($cargo === 'VISTORIADOR' && $os['vistoriador_id'] !== $usuario_id) {
        setMensagem('error', 'Acesso negado. Esta OS não está atribuída a você.');
        redirecionar(APP_URL . 'agendamentos');
    }

} catch (Exception $e) {
    error_log('Erro ao carregar OS: ' . $e->getMessage());
    setMensagem('error', 'Erro ao carregar Ordem de Serviço.');
    redirecionar(APP_URL . 'agendamentos');
}

// Status labels
$os_status_labels = [
    'pendente'     => ['label' => 'Pendente',     'class' => 'badge-warning'],
    'em_andamento' => ['label' => 'Em Andamento',  'class' => 'badge-info'],
    'executado'    => ['label' => 'Executada',     'class' => 'badge-success'],
    'cancelado'    => ['label' => 'Cancelada',     'class' => 'badge-danger'],
];

$st = $os_status_labels[$os['status']] ?? ['label' => $os['status'], 'class' => 'badge-secondary'];

// Formatar dados auxiliares
$perfil_label = [
    'armador'       => 'Armador',
    'proprietario'  => 'Proprietário',
    'despachante'   => 'Despachante',
];

$titulo_page = 'OS ' . $os['numero'] . ' - ERP Sistema';

// ----- CSS especifico para impressao -----
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($titulo_page); ?></title>
    <!-- Fontes e icones -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>assets/css/style.css">
    <style>
        /* ===== ESTILOS DE IMPRESSAO ===== */
        @media print {
            body {
                background: #fff !important;
                color: #000 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .sidebar, .sidebar-overlay, .header-fixo, .no-print {
                display: none !important;
            }
            .conteudo-principal {
                margin-left: 0 !important;
                padding: 0 !important;
            }
            .os-container {
                box-shadow: none !important;
                border: 1px solid #ccc !important;
                max-width: 100% !important;
                margin: 0 !important;
            }
            .os-header {
                background: #003366 !important;
                color: #fff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .os-header h1, .os-header span {
                color: #fff !important;
            }
            .badge {
                border: 1px solid #333 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .badge-success { background: #2ECC71 !important; color: #fff !important; }
            .badge-warning { background: #F39C12 !important; color: #fff !important; }
            .badge-info    { background: #3498DB !important; color: #fff !important; }
            .badge-danger  { background: #E74C3C !important; color: #fff !important; }
            table { border-collapse: collapse !important; }
            table td, table th { border: 1px solid #999 !important; }
            .section-title {
                background: #f0f0f0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            @page {
                size: A4;
                margin: 10mm;
            }
            .os-footer {
                position: fixed;
                bottom: 0;
                width: 100%;
                border-top: 1px solid #ccc;
                padding-top: 4px;
                font-size: 9px;
                text-align: center;
            }
        }

        /* ===== ESTILOS DE TELA ===== */
        .conteudo-principal {
            padding: 20px 25px !important;
        }
        .os-container {
            max-width: 900px;
            margin: 0 auto;
            background: var(--cor-card-bg, #1e1e2d);
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .os-header {
            background: linear-gradient(135deg, #003366, #0055a5);
            color: #fff;
            padding: 20px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .os-header h1 {
            font-size: 1.3rem;
            margin: 0;
            color: #fff;
        }
        .os-header .os-numero {
            font-size: 1.6rem;
            font-weight: 700;
            font-family: 'Courier New', monospace;
            letter-spacing: 1px;
        }
        .os-body {
            padding: 25px;
        }
        .section-title {
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--cor-destaque, #2ECC71);
            margin: 22px 0 10px 0;
            padding-bottom: 6px;
            border-bottom: 2px solid var(--cor-destaque, #2ECC71);
        }
        .section-title:first-child {
            margin-top: 0;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px 25px;
        }
        .info-grid.full {
            grid-template-columns: 1fr;
        }
        .info-item {
            display: flex;
            flex-direction: column;
            padding: 6px 0;
            border-bottom: 1px solid var(--cor-borda, rgba(255,255,255,0.08));
        }
        .info-item .label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: var(--cor-texto-secundario, #999);
            margin-bottom: 2px;
        }
        .info-item .value {
            font-size: 0.95rem;
            font-weight: 500;
            color: var(--cor-texto, #ddd);
        }
        .info-item .value strong {
            color: var(--cor-texto, #fff);
        }
        .dados-tecnicos-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        .dados-tecnicos-table td {
            padding: 7px 10px;
            border: 1px solid var(--cor-borda, rgba(255,255,255,0.08));
            font-size: 0.85rem;
            vertical-align: top;
        }
        .dados-tecnicos-table td:first-child {
            font-weight: 600;
            background: rgba(255,255,255,0.03);
            width: 35%;
            color: var(--cor-texto-secundario, #aaa);
        }
        .obs-box {
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--cor-borda, rgba(255,255,255,0.1));
            border-radius: 6px;
            padding: 14px 18px;
            white-space: pre-wrap;
            font-size: 0.9rem;
            line-height: 1.6;
            color: var(--cor-texto, #ccc);
        }
        .os-footer-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 25px;
            border-top: 1px solid var(--cor-borda, rgba(255,255,255,0.08));
            background: rgba(255,255,255,0.02);
        }
        .btn-imprimir {
            background: var(--cor-destaque, #2ECC71);
            color: #000;
            border: none;
            padding: 10px 22px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        .btn-imprimir:hover {
            opacity: 0.85;
            transform: translateY(-1px);
        }
        .btn-voltar {
            color: var(--cor-texto-secundario, #999);
            text-decoration: none;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: color 0.2s;
        }
        .btn-voltar:hover {
            color: var(--cor-texto, #fff);
        }
        .assinar-area {
            margin-top: 30px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            text-align: center;
        }
        .assinar-linha {
            border-top: 2px solid var(--cor-borda, rgba(255,255,255,0.2));
            margin-top: 50px;
            padding-top: 6px;
            font-size: 0.8rem;
            color: var(--cor-texto-secundario, #999);
        }
        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
            .os-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body class="tema-escuro">
<?php 
// Incluir header e sidebar APENAS se nao for request de impressao
// (O proprio media print cuida de esconder, mas segmentamos para clareza)
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div class="os-container">

        <!-- ===== CABECALHO ===== -->
        <div class="os-header">
            <div>
                <h1><i class="fas fa-clipboard-list"></i> ORDEM DE SERVIÇO</h1>
                <span class="os-numero"><?php echo h($os['numero']); ?></span>
            </div>
            <span class="badge <?php echo $st['class']; ?>" style="font-size: 0.9rem; padding: 6px 14px;">
                <?php echo $st['label']; ?>
            </span>
        </div>

        <!-- ===== CORPO ===== -->
        <div class="os-body">

            <!-- STATUS E DATAS -->
            <div class="info-grid">
                <div class="info-item">
                    <span class="label"><i class="fas fa-calendar-day"></i> Data da Vistoria</span>
                    <span class="value"><strong><?php echo formatarData($os['data_vistoria']); ?></strong> às <?php echo h(substr($os['hora_vistoria'] ?? '--:--', 0, 5)); ?></span>
                </div>
                <div class="info-item">
                    <span class="label"><i class="fas fa-map-marker-alt"></i> Local</span>
                    <span class="value"><?php echo h($os['local'] ?: 'Não informado'); ?></span>
                </div>
                <div class="info-item">
                    <span class="label"><i class="fas fa-user-check"></i> Vistoriador Responsável</span>
                    <span class="value"><strong><?php echo h($os['vistoriador_nome']); ?></strong> <?php echo $os['vistoriador_email'] ? '(' . h($os['vistoriador_email']) . ')' : ''; ?></span>
                </div>
                <div class="info-item">
                    <span class="label"><i class="fas fa-file-invoice"></i> Proposta Vinculada</span>
                    <span class="value"><?php echo $os['proposta_numero'] ? h($os['proposta_numero']) : '<em class="text-muted">Nenhuma</em>'; ?></span>
                </div>
                <div class="info-item">
                    <span class="label"><i class="fas fa-clock"></i> Emitida em</span>
                    <span class="value"><?php echo formatarDataCompleta($os['created_at']); ?></span>
                </div>
                <div class="info-item">
                    <span class="label"><i class="fas fa-user-edit"></i> Emitida por</span>
                    <span class="value"><?php echo h($os['criado_por_nome'] ?? 'Sistema'); ?></span>
                </div>
            </div>

            <!-- ===== DADOS DO CLIENTE ===== -->
            <h3 class="section-title"><i class="fas fa-user-tie"></i> Dados do Cliente</h3>
            <div class="info-grid">
                <div class="info-item">
                    <span class="label">Nome / Razão Social</span>
                    <span class="value"><strong><?php echo h($os['cliente_nome']); ?></strong></span>
                </div>
                <div class="info-item">
                    <span class="label">Perfil</span>
                    <span class="value">
                        <span class="badge badge-<?php echo $os['cliente_perfil'] === 'armador' ? 'primary' : ($os['cliente_perfil'] === 'despachante' ? 'warning' : 'secondary'); ?>">
                            <?php echo h($perfil_label[$os['cliente_perfil']] ?? $os['cliente_perfil']); ?>
                        </span>
                    </span>
                </div>
                <div class="info-item">
                    <span class="label"><?php echo $os['cliente_tipo_pessoa'] === 'PJ' ? 'CNPJ' : 'CPF'; ?></span>
                    <span class="value"><?php echo h($os['cliente_cpfcnpj'] ?: '-'); ?></span>
                </div>
                <div class="info-item">
                    <span class="label">Telefone</span>
                    <span class="value"><?php echo h($os['cliente_telefone'] ?: '-'); ?></span>
                </div>
                <div class="info-item">
                    <span class="label">Email</span>
                    <span class="value"><?php echo h($os['cliente_email'] ?: '-'); ?></span>
                </div>
                <div class="info-item">
                    <span class="label">Endereço</span>
                    <span class="value"><?php echo h($os['cliente_endereco'] ?: '-'); ?></span>
                </div>
            </div>

            <!-- ===== DADOS DA EMBARCACAO ===== -->
            <h3 class="section-title"><i class="fas fa-ship"></i> Dados da Embarcação</h3>
            <div class="info-grid">
                <div class="info-item">
                    <span class="label">Nome</span>
                    <span class="value"><strong><?php echo h($os['embarcacao_nome']); ?></strong></span>
                </div>
                <div class="info-item">
                    <span class="label">Registro</span>
                    <span class="value"><?php echo h($os['embarcacao_registro'] ?: '-'); ?></span>
                </div>
                <div class="info-item">
                    <span class="label">Tipo</span>
                    <span class="value"><?php echo h($os['embarcacao_tipo'] ?: '-'); ?></span>
                </div>
                <div class="info-item">
                    <span class="label">Ano</span>
                    <span class="value"><?php echo h($os['embarcacao_ano'] ?: '-'); ?></span>
                </div>
            </div>

            <!-- Tabela de dados tecnicos -->
            <table class="dados-tecnicos-table" style="margin-top: 10px;">
                <tr>
                    <td>Comprimento Total</td>
                    <td><?php echo $os['comprimento_total'] ? number_format((float)$os['comprimento_total'], 2, ',', '.') . ' m' : '<em class="text-muted">N/I</em>'; ?></td>
                    <td>Comprimento Casco</td>
                    <td><?php echo $os['comprimento_casco'] ? number_format((float)$os['comprimento_casco'], 2, ',', '.') . ' m' : '<em class="text-muted">N/I</em>'; ?></td>
                </tr>
                <tr>
                    <td>Comprimento LPP</td>
                    <td><?php echo $os['comprimento_lpp'] ? number_format((float)$os['comprimento_lpp'], 2, ',', '.') . ' m' : '<em class="text-muted">N/I</em>'; ?></td>
                    <td>Pontal Moldado</td>
                    <td><?php echo $os['pontal_moldado'] ? number_format((float)$os['pontal_moldado'], 2, ',', '.') . ' m' : '<em class="text-muted">N/I</em>'; ?></td>
                </tr>
                <tr>
                    <td>Boca Moldada</td>
                    <td><?php echo $os['boca_moldada'] ? number_format((float)$os['boca_moldada'], 2, ',', '.') . ' m' : '<em class="text-muted">N/I</em>'; ?></td>
                    <td>Boca Máxima</td>
                    <td><?php echo $os['boca_maxima'] ? number_format((float)$os['boca_maxima'], 2, ',', '.') . ' m' : '<em class="text-muted">N/I</em>'; ?></td>
                </tr>
                <tr>
                    <td>Material do Casco</td>
                    <td><?php echo h($os['material_casco'] ?: '<em class="text-muted">N/I</em>'); ?></td>
                    <td>Arqueação Bruta</td>
                    <td><?php echo h($os['arqueacao_bruta'] ?: '<em class="text-muted">N/I</em>'); ?></td>
                </tr>
                <tr>
                    <td>Tipo de Serviço</td>
                    <td><?php echo h($os['tipo_servico'] ?: '<em class="text-muted">N/I</em>'); ?></td>
                    <td>Tipo de Navegação</td>
                    <td><?php echo h($os['tipo_navegacao'] ?: '<em class="text-muted">N/I</em>'); ?></td>
                </tr>
                <tr>
                    <td>Área de Navegação</td>
                    <td><?php echo h($os['area_navegacao'] ?: '<em class="text-muted">N/I</em>'); ?></td>
                    <td>Nº Inscrição</td>
                    <td><?php echo h($os['numero_inscricao'] ?: '<em class="text-muted">N/I</em>'); ?></td>
                </tr>
                <tr>
                    <td>Porto de Inscrição</td>
                    <td><?php echo h($os['porto_inscricao'] ?: '<em class="text-muted">N/I</em>'); ?></td>
                    <td>Indicativo de Chamada</td>
                    <td><?php echo h($os['indicativo_chamada'] ?: '<em class="text-muted">N/I</em>'); ?></td>
                </tr>
                <tr>
                    <td>Nº Tripulantes</td>
                    <td><?php echo (int)$os['numero_tripulantes']; ?></td>
                    <td>Nº Passageiros (N1/N2)</td>
                    <td><?php echo (int)$os['numero_passageiros_n1']; ?> / <?php echo (int)$os['numero_passageiros_n2']; ?></td>
                </tr>
            </table>

            <!-- ===== TIPO DE VISTORIA / CONTATO NO LOCAL ===== -->
            <h3 class="section-title"><i class="fas fa-clipboard-check"></i> Vistoria e Contato no Local</h3>
            <div class="info-grid">
                <div class="info-item">
                    <span class="label">Tipo de Vistoria</span>
                    <span class="value"><strong><?php echo h($os['tipo_vistoria']); ?></strong></span>
                </div>
                <div class="info-item">
                    <span class="label">Data / Hora</span>
                    <span class="value"><?php echo formatarData($os['data_vistoria']); ?> às <?php echo h(substr($os['hora_vistoria'] ?? '--:--', 0, 5)); ?></span>
                </div>
                <div class="info-item">
                    <span class="label">Local</span>
                    <span class="value"><?php echo h($os['local'] ?: 'Não informado'); ?></span>
                </div>
                <div class="info-item"></div>
                <div class="info-item">
                    <span class="label">Nome do Contato</span>
                    <span class="value"><strong><?php echo h($os['contato_nome'] ?: '-'); ?></strong></span>
                </div>
                <div class="info-item">
                    <span class="label">Telefone do Contato</span>
                    <span class="value"><?php echo h($os['contato_telefone'] ?: '-'); ?></span>
                </div>
            </div>

            <!-- ===== OBSERVACOES ===== -->
            <?php if (!empty($os['observacoes'])): ?>
            <h3 class="section-title"><i class="fas fa-sticky-note"></i> Observações</h3>
            <div class="obs-box"><?php echo nl2br(h($os['observacoes'])); ?></div>
            <?php endif; ?>

            <!-- ===== AREA DE ASSINATURAS (apenas para impressao) ===== -->
            <div class="assinar-area" style="margin-top: 40px;">
                <div>
                    <div class="assinar-linha">Vistoriador Responsável<br><strong><?php echo h($os['vistoriador_nome']); ?></strong></div>
                </div>
                <div>
                    <div class="assinar-linha">Cliente / Contato no Local<br><strong><?php echo h($os['cliente_nome']); ?></strong></div>
                </div>
            </div>

            <!-- ===== DATA EMISSAO NO RODAPE ===== -->
            <div style="text-align: center; margin-top: 25px; font-size: 0.75rem; color: var(--cor-texto-secundario, #999);">
                Belém/PA, <?php echo formatarData($os['created_at']); ?> — Emitida por <?php echo h($os['criado_por_nome'] ?? 'Sistema'); ?>
            </div>

        </div><!-- /.os-body -->

        <!-- ===== BOTOES DE ACAO ===== -->
        <div class="os-footer-actions no-print">
            <a href="<?php echo APP_URL; ?>agendamentos" class="btn-voltar">
                <i class="fas fa-arrow-left"></i> Voltar para Agendamentos
            </a>
            <button type="button" class="btn-imprimir" onclick="window.print()">
                <i class="fas fa-print"></i> Imprimir OS
            </button>
        </div>

    </div><!-- /.os-container -->
</div><!-- /.conteudo-principal -->

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>
