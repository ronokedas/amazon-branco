import { AgendaItem, InspectionPackage, ReportItem } from './types';

export const MOCK_AGENDA: AgendaItem[] = [
  {
    id: 'vist_001',
    embarcacao_id: 'emb_9921',
    embarcacao_nome: 'B/M AMAZON KING III',
    embarcacao_registro: 'CPNP 441-009823-1',
    tipo_vistoria: 'Vistoria de Segurança e Habitabilidade',
    finalidade: 'INICIAL',
    data_vistoria: '2026-08-06',
    local: 'Porto de Manaus - Cais das Lajes (Estaleiro Solimões)',
    contato_nome: 'Cap. Raimundo Nonato',
    contato_telefone: '(92) 99182-4430',
    vistoriador_nome: 'Carlos Eduardo Silva',
    cliente_nome: 'Navegação Rio Negro Ltda.',
    foto_url: 'https://images.unsplash.com/photo-1559136555-9303baea8ebd?auto=format&fit=crop&w=800&q=80',
    status: 'AGENDADA',
    prazo_exigencias_dias: 90,
    operador_nome: 'Cap. Raimundo Nonato',
  },
  {
    id: 'vist_002',
    embarcacao_id: 'emb_8812',
    embarcacao_nome: 'F/B RAINHA DO SOLIMÕES',
    embarcacao_registro: 'CPNP 441-012948-2',
    tipo_vistoria: 'Vistoria de Cumprimento de Exigências',
    finalidade: 'CUMPRIMENTO_EXIGENCIAS',
    data_vistoria: '2026-08-07',
    local: 'Terminal Hidroviário de Itacoatiara',
    contato_nome: 'Eng. Mário Siqueira',
    contato_telefone: '(92) 98411-9022',
    vistoriador_nome: 'Carlos Eduardo Silva',
    cliente_nome: 'Transportes Fluviais do Amazonas',
    foto_url: 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&w=800&q=80',
    tarefa_cumprimento: 'Verificação de substituição dos extintores CO2 e teste da bomba de esgoto da praça de máquinas.',
    relatorio_url: '/vistorias/relatorio?agendamento_id=vist_002_anterior',
    status: 'RETORNO_PENDENTE',
    prazo_exigencias_dias: 90,
    operador_nome: 'Eng. Mário Siqueira',
  },
  {
    id: 'vist_003',
    embarcacao_id: 'emb_7734',
    embarcacao_nome: 'REBOCADOR TAPAJÓS I',
    embarcacao_registro: 'CPNP 442-003811-9',
    tipo_vistoria: 'Vistoria de Arqueação e Borda Livre',
    finalidade: 'RENOVACAO',
    data_vistoria: '2026-08-08',
    local: 'Base Fluvial Belém - Doca Seca 02',
    contato_nome: 'Sr. Arnaldo Castro',
    contato_telefone: '(91) 98112-7001',
    vistoriador_nome: 'Carlos Eduardo Silva',
    cliente_nome: 'Empresa de Navegação Pará-Amazonas',
    foto_url: 'https://images.unsplash.com/photo-1516834474-48c0abc2a902?auto=format&fit=crop&w=800&q=80',
    status: 'AGENDADA',
    prazo_exigencias_dias: 90,
    operador_nome: 'Sr. Arnaldo Castro',
  }
];

export function getMockPackage(agendamentoId: string): InspectionPackage {
  const agendamento = MOCK_AGENDA.find(a => a.id === agendamentoId) || MOCK_AGENDA[0];

  return {
    agendamento: { ...agendamento },
    vistoria: {
      id: agendamento.id,
      numero: `VIS-2026-${agendamento.id.slice(-3).toUpperCase()}`,
      status: agendamento.status,
      finalidade: agendamento.finalidade,
      data_vistoria: agendamento.data_vistoria,
      prazo_exigencias_dias: agendamento.prazo_exigencias_dias || 90,
      operador_nome: agendamento.operador_nome || agendamento.contato_nome,
      observacoes_tecnicas: 'Condições gerais de navegação satisfatórias. Vistoria realizada com acompanhamento do responsável legal.',
      mobile_versao: 1,
      relatorio_anterior_id: agendamento.finalidade === 'CUMPRIMENTO_EXIGENCIAS' ? 'REL-2026-001-ANT' : undefined
    },
    categorias: [
      {
        id: 'cat_seco',
        nome: 'Vistoria em Seco (Casco e Propulsão)',
        itens: [
          {
            id: 'item_seco_01',
            descricao: 'Estado de conservação do chapeamento do casco e fundo (isento de deformações ou corrosão acentuada)',
            item_normam: 'NORMAM-202/DPC, item 3.2.1',
            resposta: null,
            anexos: []
          },
          {
            id: 'item_seco_02',
            descricao: 'Verificação da hélice, eixos de propulsão e buchas do leme',
            item_normam: 'NORMAM-202/DPC, item 3.2.4',
            resposta: null,
            anexos: []
          },
          {
            id: 'item_seco_03',
            descricao: 'Inspeção visual das tomadas de água do mar (seacocks) e grelhas de aspiração',
            item_normam: 'NORMAM-202/DPC, item 3.2.7',
            resposta: null,
            anexos: []
          }
        ]
      },
      {
        id: 'cat_flutuando',
        nome: 'Vistoria Flutuando (Máquinas e Esgoto)',
        itens: [
          {
            id: 'item_flut_01',
            descricao: 'Funcionamento da bomba de esgoto principal e de emergência da praça de máquinas',
            item_normam: 'NORMAM-201/DPC, item 4.1.2',
            resposta: null,
            anexos: []
          },
          {
            id: 'item_flut_02',
            descricao: 'Condições do gerador auxiliar, quadro elétrico principal e isolamento dos cabos',
            item_normam: 'NORMAM-201/DPC, item 4.3.1',
            resposta: null,
            anexos: []
          },
          {
            id: 'item_flut_03',
            descricao: 'Sistema de governo (leme mecânico/hidráulico) e máquina de leme de emergência',
            item_normam: 'NORMAM-201/DPC, item 4.5.8',
            resposta: null,
            anexos: []
          }
        ]
      },
      {
        id: 'cat_salvatagem',
        nome: 'Salvatagem e Combate a Incêndio',
        itens: [
          {
            id: 'item_salv_01',
            descricao: 'Coletes salva-vidas aprovados para 100% dos passageiros + 10% infantis com fita refletiva e apito',
            item_normam: 'NORMAM-202/DPC, Cap. 4, Seç. I',
            resposta: null,
            anexos: []
          },
          {
            id: 'item_salv_02',
            descricao: 'Boias circulares com retinida flutuante e luz de acendimento automático',
            item_normam: 'NORMAM-202/DPC, item 4.12',
            resposta: null,
            anexos: []
          },
          {
            id: 'item_salv_03',
            descricao: 'Validade e carga dos extintores de incêndio portáteis (CO2 / Pó Químico Seco)',
            item_normam: 'NORMAM-201/DPC, Cap. 5, Seç. III',
            resposta: null,
            anexos: []
          }
        ]
      },
      {
        id: 'cat_borda_arqueacao',
        nome: 'Borda Livre e Arqueação',
        itens: [
          {
            id: 'item_borda_01',
            descricao: 'Marcação do disco de Plimsoll e linhas de carga nas duas alhetas do casco',
            item_normam: 'NORMAM-203/DPC, Regra 6',
            resposta: null,
            anexos: []
          },
          {
            id: 'item_borda_02',
            descricao: 'Estanqueidade de escotilhas, portas estanques e vigotas da superestrutura',
            item_normam: 'NORMAM-203/DPC, Regra 12',
            resposta: null,
            anexos: []
          }
        ]
      }
    ],
    exigencias_avulsas: [
      {
        id: 'exig_av_01',
        bloco_vistoria: 'flutuando',
        descricao: 'Instalar corrimão de proteção no passadiço da praça de máquinas lado boreste',
        item_normam: 'NORMAM-201/DPC, item 2.14',
        observacao: 'Falta barreira de proteção física para os maquinistas.',
        status_item: agendamento.finalidade === 'CUMPRIMENTO_EXIGENCIAS' ? 'pendente' : 'inserida',
        vencimento: '2026-11-04',
        antes_de_suspender: false
      }
    ]
  };
}

export const MOCK_REPORTS: ReportItem[] = [
  {
    id: 'rel_101',
    numero: 'REL-2026-0089',
    embarcacao_nome: 'B/M AMAZON KING II',
    embarcacao_registro: 'CPNP 441-008122-8',
    data_vistoria: '2026-07-28',
    status: 'APROVADA',
    finalidade: 'INICIAL',
    relatorio_pdf_url: '/vistorias/relatorio?agendamento_id=rel_101'
  },
  {
    id: 'rel_102',
    numero: 'REL-2026-0094',
    embarcacao_nome: 'BALSA MANAUS VI',
    embarcacao_registro: 'CPNP 442-019283-0',
    data_vistoria: '2026-08-01',
    status: 'APROVADA_COM_EXIGENCIAS',
    finalidade: 'PERIODICA',
    relatorio_pdf_url: '/vistorias/relatorio?agendamento_id=rel_102'
  }
];
