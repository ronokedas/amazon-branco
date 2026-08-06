/**
 * Types & Contracts for App Vistoria Naval NORMAM
 */

export type ResponseStatus = 'CONFORME' | 'NAO_CONFORME' | 'NAO_SE_APLICA';

export type InspectionFinality = 'INICIAL' | 'PERIODICA' | 'RENOVACAO' | 'CUMPRIMENTO_EXIGENCIAS';

export type AvulsaStatus = 
  | 'inserida' 
  | 'pendente' 
  | 'cumprida' 
  | 'cumprida_parcial_reescrita' 
  | 'nao_cumprida_transcrita';

export type InspectionBlock = 'seco' | 'flutuando' | 'borda_livre' | 'arqueacao';

export interface User {
  id: string;
  nome: string;
  cargo: string;
  usuario: string;
}

export interface Session {
  usuario: User;
  csrf_token: string;
  expira_em: string;
  isOffline?: boolean;
}

export interface AgendaItem {
  id: string;
  embarcacao_id: string;
  embarcacao_nome: string;
  embarcacao_registro: string; // CPNP
  tipo_vistoria: string;
  finalidade: InspectionFinality;
  data_vistoria: string; // YYYY-MM-DD
  local: string;
  contato_nome: string; // Responsavel pelo fechamento
  contato_telefone: string;
  vistoriador_nome: string;
  cliente_nome: string;
  foto_url?: string;
  tarefa_cumprimento?: string;
  relatorio_url?: string;
  operador_nome?: string;
  prazo_exigencias_dias?: number; // 60 or 90
  status: string;
  isDownloaded?: boolean;
}

export interface Anexo {
  id: string;
  url_arquivo: string;
  local?: string; // base64 or blob URL
  status_upload?: 'pendente' | 'enviado';
  capturado_em: string;
  nome_original?: string;
  size_bytes?: number;
  sha256?: string;
  blobData?: ArrayBuffer | Blob;
}

export interface ItemResposta {
  status: ResponseStatus;
  observacao?: string;
  vencimento?: string; // YYYY-MM-DD or empty
  sem_prazo: boolean; // true = A/S (Antes de Suspender)
  item_normam?: string;
}

export interface CatalogoItem {
  id: string;
  descricao: string;
  item_normam: string; // e.g., "NORMAM-202/DPC, item 3.2"
  resposta: ItemResposta | null;
  anexos: Anexo[];
}

export interface Categoria {
  id: string;
  nome: string;
  itens: CatalogoItem[];
}

export interface ExigenciaAvulsa {
  id: string;
  id_local?: string;
  vistoria_id?: string;
  bloco_vistoria: InspectionBlock;
  descricao: string;
  item_normam: string;
  observacao: string;
  status_item: AvulsaStatus;
  vencimento: string; // YYYY-MM-DD or empty
  antes_de_suspender: boolean; // true = A/S
  anexos?: Anexo[];
}

export interface VistoriaDados {
  id: string;
  numero: string;
  status: string;
  finalidade: InspectionFinality;
  data_vistoria: string;
  prazo_exigencias_dias: number; // 60 or 90
  operador_nome: string; // Responsavel presente que acompanha
  observacoes_tecnicas: string;
  mobile_versao: number;
  relatorio_anterior_id?: string;
}

export interface InspectionPackage {
  agendamento: AgendaItem;
  categorias: Categoria[];
  vistoria: VistoriaDados;
  exigencias_avulsas: ExigenciaAvulsa[];
  dados_vistoria_locais?: any;
  respostas_locais?: any;
}

export type SyncOperationType = 
  | 'rascunho' 
  | 'anexo' 
  | 'foto_embarcacao' 
  | 'exclusao_anexo' 
  | 'finalizacao';

export interface SyncOperation {
  operacao_id: string; // UUID v4
  agendamento_id: string;
  tipo: SyncOperationType;
  payload: any;
  timestamp: number;
  status: 'pendente' | 'enviando' | 'erro' | 'sucesso';
  mensagem_erro?: string;
}

export interface ReportItem {
  id: string;
  numero: string;
  embarcacao_nome: string;
  embarcacao_registro: string;
  data_vistoria: string;
  status: string;
  finalidade: InspectionFinality;
  relatorio_pdf_url: string;
}

export interface SyncDraftPayload {
  operacao_id: string;
  versao: number;
  respostas: Array<{
    catalogo_id: string;
    status: ResponseStatus;
    observacao?: string;
    vencimento?: string;
    sem_prazo: boolean;
    item_normam?: string;
  }>;
  dados_vistoria: {
    data_vistoria: string;
    prazo_exigencias_dias: number;
    operador_nome: string;
    observacoes_tecnicas: string;
  };
  exigencias_avulsas: Array<{
    bloco_vistoria: InspectionBlock;
    descricao: string;
    item_normam: string;
    observacao: string;
    status_item: AvulsaStatus;
    vencimento: string;
    antes_de_suspender: boolean;
    id_local?: string;
  }>;
}
