<?php
/**
 * MÓDULO: PORTAL DO CLIENTE - OUVIDORIA & GESTÃO DA QUALIDADE (ISO 9001:2015)
 * Arquivo: modules/portal/ouvidoria.php
 * Canal formal de manifestações, reclamações e melhoria contínua (ISO 8.2.1, 9.1.2 e 10.2)
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/cliente_portal.php';

requireClienteSenhaDefinitiva();

$clienteId = clientePortalId();
$clienteNome = clientePortalNome();
$embarcacoes = clientePortalEmbarcacoes($pdo, $clienteId);

// Consultar manifestações do cliente vinculadas ao SGQ
$stmt = $pdo->prepare("
    SELECT r.*, e.nome AS embarcacao_nome,
           (SELECT COUNT(*) FROM sgq_planos_acao pa WHERE pa.nao_conformidade_id = r.id) AS total_planos_5w2h,
           (SELECT COUNT(*) FROM sgq_planos_acao pa WHERE pa.nao_conformidade_id = r.id AND pa.status_acao = 'CONCLUIDA') AS planos_concluidos
    FROM sgq_nao_conformidades r
    LEFT JOIN embarcacoes e ON e.id = r.embarcacao_id
    WHERE r.cliente_id = :cli_id AND r.origem = 'RECLAMACAO_CLIENTE'
    ORDER BY r.criado_em DESC
");
$stmt->execute([':cli_id' => $clienteId]);
$manifestacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = count($manifestacoes);
$abertas = 0;
$emTratamento = 0;
$encerradas = 0;

foreach ($manifestacoes as $m) {
    if ($m['status_ciclo_vida'] === 'ABERTA') {
        $abertas++;
    } elseif ($m['status_ciclo_vida'] === 'ENCERRADA_EFICAZ') {
        $encerradas++;
    } else {
        $emTratamento++;
    }
}

$titulo_page = 'Ouvidoria & Qualidade - ' . APP_NAME;
require_once __DIR__ . '/../../includes/portal_header.php';
?>

<section class="portal-page-header">
    <div>
        <h1>Ouvidoria & Qualidade</h1>
        <p>Canal oficial de comunicação, feedback e tratamento de reclamações em conformidade com a ISO 9001:2015.</p>
    </div>
    <div class="portal-page-header-mark">
        <i class="fa-solid fa-headset"></i>
    </div>
</section>

<!-- Banner de Conformidade ISO 9001 -->
<div class="portal-preserve" style="margin-bottom: 24px;">
    <i class="fa-solid fa-award"></i>
    <div>
        <strong>Compromisso com a Qualidade & Satisfação do Cliente (ISO 9001:2015)</strong>
        <span>
            Conforme as cláusulas <strong>8.2.1</strong> (Comunicação com o Cliente), <strong>9.1.2</strong> (Satisfação do Cliente) e <strong>10.2</strong> (Não Conformidade e Ação Corretiva), toda manifestação ou reclamação gera um protocolo oficial e é tratada com investigação técnica de causa raiz e plano de ação corretiva pela Diretoria Técnica da Amazon Certificadora.
        </span>
    </div>
</div>

<!-- Cards de Métricas de Manifestações -->
<div class="portal-metrics" style="margin-bottom: 24px;">
    <div class="portal-metric">
        <i class="fa-regular fa-clipboard"></i>
        <strong><?php echo $total; ?></strong>
        <span>Total de<br>manifestações</span>
    </div>
    <div class="portal-metric">
        <i class="fa-solid fa-hourglass-half" style="color: #d97706;"></i>
        <strong><?php echo $abertas; ?></strong>
        <span>Aguardando<br>análise inicial</span>
    </div>
    <div class="portal-metric">
        <i class="fa-solid fa-gears" style="color: #2563eb;"></i>
        <strong><?php echo $emTratamento; ?></strong>
        <span>Em tratamento<br>técnico (5W2H)</span>
    </div>
    <div class="portal-metric">
        <i class="fa-regular fa-circle-check" style="color: #059669;"></i>
        <strong><?php echo $encerradas; ?></strong>
        <span>Concluídas &<br>resolvidas</span>
    </div>
</div>

<div class="portal-analysis-layout">
    <!-- Formulário de Registro de Nova Manifestação -->
    <section class="portal-panel portal-analysis-main">
        <div class="portal-panel-header" style="margin-bottom: 20px;">
            <h2><i class="fa-solid fa-pen-to-square"></i> Registrar Nova Manifestação / Reclamação</h2>
        </div>

        <form method="POST" action="<?php echo APP_URL; ?>portal/ouvidoria/actions" class="portal-analysis-form">
            <input type="hidden" name="csrf_token" value="<?php echo h(gerarCSRF()); ?>">
            <input type="hidden" name="action" value="registrar_manifestacao">

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px;">
                <div class="form-group">
                    <label for="tipo_manifestacao"><i class="fa-solid fa-tag"></i> Tipo de Manifestação *</label>
                    <select id="tipo_manifestacao" name="tipo_manifestacao" required>
                        <option value="RECLAMACAO" selected>Reclamação Formal (ISO 10.2)</option>
                        <option value="SUGESTAO">Sugestão de Melhoria Contínua (ISO 10.3)</option>
                        <option value="DUVIDA">Dúvida / Esclarecimento Técnico</option>
                        <option value="ELOGIO">Elogio / Reconhecimento</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="classificacao_falha"><i class="fa-solid fa-folder-tree"></i> Categoria / Assunto *</label>
                    <select id="classificacao_falha" name="classificacao_falha" required>
                        <option value="Atraso de Prazo / Vistoria">Atraso de Prazo / Agendamento de Vistoria</option>
                        <option value="Qualidade Técnica / Laudo">Qualidade Técnica / Divergência em Laudo</option>
                        <option value="Atendimento Comercial">Atendimento Comercial / Suporte</option>
                        <option value="Erro em Documento / Certificado">Erro em Documento / Certificado Naval</option>
                        <option value="Financeiro / Faturamento">Financeiro / Cobrança / Faturamento</option>
                        <option value="Outros / Geral">Outros / Geral</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px;">
                <div class="form-group">
                    <label for="embarcacao_id"><i class="fa-solid fa-ship"></i> Embarcação Vinculada (opcional)</label>
                    <select id="embarcacao_id" name="embarcacao_id">
                        <option value="">Geral (não vinculada a uma embarcação específica)</option>
                        <?php foreach ($embarcacoes as $emb): ?>
                            <option value="<?php echo h($emb['id']); ?>">
                                <?php echo h($emb['nome'] . ($emb['registro'] ? ' - Reg: ' . $emb['registro'] : '')); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="severidade"><i class="fa-solid fa-triangle-exclamation"></i> Gravidade / Urgência *</label>
                    <select id="severidade" name="severidade" required>
                        <option value="MEDIA" selected>Normal (Tratamento padrão SGQ)</option>
                        <option value="CRITICA_IMPEDITIVA">Urgente (Afeta Despacho da Embarcação / Navegabilidade)</option>
                        <option value="BAIXA">Baixa (Informativo / Sem impacto operacional)</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="titulo"><i class="fa-solid fa-heading"></i> Assunto / Título Resumido *</label>
                <input type="text" id="titulo" name="titulo" required placeholder="Ex.: Divergência nas dimensões registradas no laudo da balsa" maxlength="200">
            </div>

            <div class="form-group">
                <label for="descricao"><i class="fa-solid fa-align-left"></i> Relato Detalhado dos Fatos *</label>
                <textarea id="descricao" name="descricao" rows="5" required style="width: 100%; padding: 12px; border: 1px solid #cfdcd6; border-radius: 9px; font: inherit; resize: vertical;" placeholder="Descreva com detalhes o que aconteceu, incluindo datas, nomes envolvidos, impacto e a solução esperada para que nossa equipe técnica possa atuar com precisão."></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; margin-top: 10px;">
                <button type="submit" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 10px; padding: 0 24px;">
                    <i class="fa-solid fa-paper-plane"></i> Enviar Manifestação com Protocolo Oficial
                </button>
            </div>
        </form>
    </section>

    <!-- Barra Lateral Informativa -->
    <aside class="portal-analysis-side">
        <div class="portal-side-card">
            <h2><i class="fa-solid fa-shield-halved" style="color: var(--p-green);"></i> Garantia ISO 9001</h2>
            <ul class="portal-steps" style="margin-top: 12px;">
                <li class="portal-step">
                    <span class="portal-step-number">1</span>
                    <div>
                        <strong>Protocolo Imediato</strong>
                        <p>Ao registrar, o sistema gera o identificador oficial RNC para rastreabilidade.</p>
                    </div>
                </li>
                <li class="portal-step">
                    <span class="portal-step-number">2</span>
                    <div>
                        <strong>Análise Técnica pelo RT</strong>
                        <p>O Responsável Técnico analisa a causa raiz para entender a origem da ocorrência.</p>
                    </div>
                </li>
                <li class="portal-step">
                    <span class="portal-step-number">3</span>
                    <div>
                        <strong>Plano de Ação Corretiva</strong>
                        <p>Ações 5W2H são executadas para sanar o ocorrido e evitar reincidência.</p>
                    </div>
                </li>
                <li class="portal-step">
                    <span class="portal-step-number">4</span>
                    <div>
                        <strong>Retorno ao Cliente</strong>
                        <p>Você acompanha o parecer e o fechamento diretamente por este painel.</p>
                    </div>
                </li>
            </ul>
        </div>
    </aside>
</div>

<!-- Listagem do Histórico de Manifestações do Cliente -->
<section class="portal-panel" style="margin-top: 24px;">
    <div class="portal-panel-header" style="margin-bottom: 20px;">
        <h2><i class="fa-solid fa-clock-rotate-left"></i> Histórico de Manifestações & Acompanhamento</h2>
    </div>

    <?php if (empty($manifestacoes)): ?>
        <div class="portal-empty" style="text-align: center; padding: 40px 20px;">
            <i class="fa-solid fa-check-double" style="font-size: 36px; color: var(--p-green); margin-bottom: 12px;"></i>
            <h3>Nenhuma manifestação registrada</h3>
            <p style="color: var(--p-muted); margin-top: 6px;">Você ainda não possui reclamações ou manifestações registradas. Quando enviar, o protocolo e o andamento aparecerão aqui em tempo real.</p>
        </div>
    <?php else: ?>
        <div class="portal-table-wrap">
            <table class="portal-table">
                <thead>
                    <tr>
                        <th>Protocolo / Assunto</th>
                        <th>Embarcação</th>
                        <th>Classificação</th>
                        <th>Data de Registro</th>
                        <th>Situação SGQ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($manifestacoes as $item): ?>
                        <?php
                        $status = $item['status_ciclo_vida'];
                        $badgeClass = 'is-valid';
                        $statusNome = 'Concluída';

                        switch ($status) {
                            case 'ABERTA':
                                $badgeClass = 'is-warning';
                                $statusNome = 'Aberta / Aguardando RT';
                                break;
                            case 'EM_ANALISE_CAUSA':
                                $badgeClass = 'is-analysis';
                                $statusNome = 'Em Investigação Técnica';
                                break;
                            case 'PLANO_ACAO_DEFINIDO':
                            case 'EM_EXECUCAO':
                            case 'AGUARDANDO_EFICACIA':
                                $badgeClass = 'is-analysis';
                                $statusNome = 'Plano de Ação em Andamento';
                                break;
                            case 'ENCERRADA_EFICAZ':
                                $badgeClass = 'is-valid';
                                $statusNome = 'Encerrada / Resolvida';
                                break;
                            case 'REABERTA':
                                $badgeClass = 'is-rejected';
                                $statusNome = 'Reaberta';
                                break;
                        }
                        ?>
                        <tr>
                            <td data-label="Protocolo / Assunto">
                                <div style="display: flex; align-items: flex-start; gap: 10px;">
                                    <i class="fa-solid fa-file-signature portal-table-icon" style="margin-top: 3px;"></i>
                                    <div>
                                        <span style="display: inline-block; font-family: monospace; font-weight: 800; color: var(--p-green); font-size: 13px;">
                                            <?php echo h($item['numero_rnc']); ?>
                                        </span>
                                        <strong style="display: block; margin-top: 2px;"><?php echo h($item['titulo']); ?></strong>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Embarcação">
                                <?php if (!empty($item['embarcacao_nome'])): ?>
                                    <i class="fa-solid fa-ship" style="color: var(--p-muted); margin-right: 4px;"></i>
                                    <?php echo h($item['embarcacao_nome']); ?>
                                <?php else: ?>
                                    <span style="color: var(--p-muted);">Geral</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Classificação">
                                <span style="display: inline-block; padding: 2px 8px; border-radius: 6px; background: #f0f4f2; font-size: 12px; font-weight: 600;">
                                    <?php echo h($item['classificacao_falha'] ?: 'Geral'); ?>
                                </span>
                            </td>
                            <td data-label="Data"><?php echo date('d/m/Y H:i', strtotime($item['criado_em'])); ?></td>
                            <td data-label="Situação">
                                <span class="portal-status <?php echo $badgeClass; ?>">
                                    <?php echo h($statusNome); ?>
                                </span>
                                <?php if (!empty($item['analise_causa_raiz'])): ?>
                                    <div style="margin-top: 6px; font-size: 11px; color: var(--p-muted);">
                                        <i class="fa-solid fa-comment-dots"></i> Parecer técnico registrado
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../../includes/portal_footer.php'; ?>
