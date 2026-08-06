import React, { useEffect, useState } from 'react';
import { 
  Search, 
  Ship, 
  MapPin, 
  Calendar, 
  Phone, 
  User, 
  FileText, 
  Download, 
  CheckCircle2, 
  AlertCircle,
  Camera,
  RefreshCw,
  ExternalLink
} from 'lucide-react';
import { AgendaItem } from '../types';
import { getAgendaAPI, getPackageAPI } from '../api';
import { getAgendaLocal } from '../db';
import { formatDateBr } from '../utils';

interface AgendaScreenProps {
  onOpenChecklist: (agendamentoId: string) => void;
  onViewPdfReport: (reportUrl: string) => void;
}

export const AgendaScreen: React.FC<AgendaScreenProps> = ({
  onOpenChecklist,
  onViewPdfReport,
}) => {
  const [agenda, setAgenda] = useState<AgendaItem[]>([]);
  const [searchQuery, setSearchQuery] = useState<string>('');
  const [isLoading, setIsLoading] = useState<boolean>(true);
  const [errorMsg, setErrorMsg] = useState<string>('');
  const [downloadingId, setDownloadingId] = useState<string>('');

  const loadAgenda = async () => {
    setIsLoading(true);
    setErrorMsg('');

    try {
      // First try local cache
      const local = await getAgendaLocal();
      if (local && local.length > 0) {
        setAgenda(local);
      }

      // Fetch fresh agenda from API
      const res = await getAgendaAPI();
      if (res.ok && res.vistorias) {
        setAgenda(res.vistorias);
      }
    } catch (err: any) {
      setErrorMsg('Falha ao atualizar a agenda: ' + err.message);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    loadAgenda();
  }, []);

  const filteredAgenda = agenda.filter((item) => {
    const q = searchQuery.toLowerCase();
    return (
      item.embarcacao_nome.toLowerCase().includes(q) ||
      item.embarcacao_registro.toLowerCase().includes(q) ||
      item.cliente_nome.toLowerCase().includes(q)
    );
  });

  const handleStartInspection = async (agendamentoId: string) => {
    setDownloadingId(agendamentoId);
    try {
      const res = await getPackageAPI(agendamentoId);
      if (res.ok) {
        onOpenChecklist(agendamentoId);
      } else {
        setErrorMsg(res.erro || 'Erro ao carregar pacote de vistoria.');
      }
    } catch (err: any) {
      setErrorMsg('Erro de conexão ao baixar pacote: ' + err.message);
    } finally {
      setDownloadingId('');
    }
  };

  return (
    <div className="space-y-4">
      
      {/* Search & Refresh Bar */}
      <div className="flex items-center space-x-2">
        <div className="relative flex-1">
          <Search className="absolute left-3.5 top-3 w-4 h-4 text-slate-400" />
          <input
            type="text"
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            placeholder="Buscar por embarcação, registro ou cliente..."
            className="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl text-slate-800 text-xs sm:text-sm focus:ring-2 focus:ring-[#1a365d] focus:border-transparent outline-none transition shadow-sm"
          />
        </div>

        <button
          onClick={loadAgenda}
          disabled={isLoading}
          className="p-2.5 bg-white hover:bg-slate-50 text-slate-700 rounded-xl border border-slate-200 transition active:scale-95 disabled:opacity-50 shadow-sm"
          title="Atualizar Agenda"
        >
          <RefreshCw className={`w-4 h-4 ${isLoading ? 'animate-spin' : ''}`} />
        </button>
      </div>

      {errorMsg && (
        <div className="p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-xs flex items-center space-x-2 shadow-sm">
          <AlertCircle className="w-4 h-4 text-rose-600 shrink-0" />
          <span>{errorMsg}</span>
        </div>
      )}

      {/* Agenda Items List */}
      {isLoading && agenda.length === 0 ? (
        <div className="p-8 text-center text-slate-500 text-xs animate-pulse">
          Carregando agendamentos de vistoria...
        </div>
      ) : filteredAgenda.length === 0 ? (
        <div className="p-8 text-center bg-white border border-slate-200 rounded-xl space-y-2 shadow-sm">
          <Ship className="w-8 h-8 text-slate-400 mx-auto" />
          <p className="text-slate-600 text-xs font-medium">Nenhum agendamento encontrado para esta busca.</p>
        </div>
      ) : (
        <div className="space-y-4">
          {filteredAgenda.map((item) => {
            const isCumprimento = item.finalidade === 'CUMPRIMENTO_EXIGENCIAS';

            return (
              <div
                key={item.id}
                className="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm hover:shadow-md hover:border-slate-300 transition"
              >
                
                {/* Card Header Banner */}
                <div className="p-4 bg-slate-50 border-b border-slate-200 flex items-start justify-between gap-3">
                  <div className="flex items-start space-x-3">
                    {item.foto_url ? (
                      <img
                        src={item.foto_url}
                        alt={item.embarcacao_nome}
                        className="w-12 h-12 rounded-lg object-cover border border-slate-200 shrink-0 shadow-sm"
                      />
                    ) : (
                      <div className="w-12 h-12 rounded-lg bg-[#1a365d]/10 text-[#1a365d] flex items-center justify-center border border-[#1a365d]/20 shrink-0">
                        <Ship className="w-6 h-6" />
                      </div>
                    )}

                    <div>
                      <div className="flex items-center space-x-2 flex-wrap">
                        <h3 className="font-extrabold text-[#1a365d] text-base leading-tight">
                          {item.embarcacao_nome}
                        </h3>
                        {item.isDownloaded && (
                          <span className="inline-flex items-center space-x-1 px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold border border-emerald-200">
                            <CheckCircle2 className="w-3 h-3 text-emerald-600" />
                            <span>Baixada</span>
                          </span>
                        )}
                      </div>

                      <div className="text-xs text-amber-600 font-mono font-bold mt-0.5">
                        {item.embarcacao_registro}
                      </div>
                    </div>
                  </div>

                  {/* Finality Badge */}
                  <span className={`px-2.5 py-1 rounded-md text-[10px] font-black tracking-wide uppercase shrink-0 border ${
                    isCumprimento
                      ? 'bg-amber-100 text-amber-900 border-amber-300'
                      : 'bg-slate-100 text-[#1a365d] border-slate-300'
                  }`}>
                    {isCumprimento ? 'RETORNO A/S' : item.finalidade}
                  </span>
                </div>

                {/* Details Body */}
                <div className="p-4 space-y-3 text-xs">
                  
                  {/* Cumprimento Especial Notice */}
                  {isCumprimento && (
                    <div className="p-3 bg-amber-50 border border-amber-200 rounded-lg space-y-1.5 text-amber-900">
                      <div className="font-bold text-amber-900 flex items-center space-x-1.5">
                        <AlertCircle className="w-4 h-4 text-amber-600 shrink-0" />
                        <span>Vistoria de Cumprimento de Exigências (Retorno)</span>
                      </div>
                      {item.tarefa_cumprimento && (
                        <p className="text-[11px] text-amber-800 leading-relaxed">
                          {item.tarefa_cumprimento}
                        </p>
                      )}
                      {item.relatorio_url && (
                        <button
                          type="button"
                          onClick={() => onViewPdfReport(item.relatorio_url!)}
                          className="inline-flex items-center space-x-1 text-[11px] font-extrabold text-[#1a365d] hover:underline pt-0.5"
                        >
                          <FileText className="w-3.5 h-3.5" />
                          <span>Consultar Relatório Anterior (PDF)</span>
                          <ExternalLink className="w-3 h-3" />
                        </button>
                      )}
                    </div>
                  )}

                  {/* Info Grid */}
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-slate-700">
                    <div className="flex items-center space-x-2">
                      <FileText className="w-4 h-4 text-slate-400 shrink-0" />
                      <span className="truncate font-medium">{item.tipo_vistoria}</span>
                    </div>

                    <div className="flex items-center space-x-2">
                      <Calendar className="w-4 h-4 text-[#1a365d] shrink-0" />
                      <span>Data Agendada: <strong className="text-slate-900 font-bold">{formatDateBr(item.data_vistoria)}</strong></span>
                    </div>

                    <div className="flex items-center space-x-2 sm:col-span-2">
                      <MapPin className="w-4 h-4 text-rose-500 shrink-0" />
                      <span className="truncate text-slate-700 font-medium">{item.local}</span>
                    </div>

                    {/* Contact info for proposal closing */}
                    <div className="flex items-center space-x-2 sm:col-span-2 p-2.5 bg-slate-50 rounded-lg border border-slate-200">
                      <User className="w-4 h-4 text-amber-600 shrink-0" />
                      <div className="truncate">
                        <span className="text-slate-500 font-medium">Responsável Fechamento: </span>
                        <span className="text-slate-900 font-bold">{item.contato_nome}</span>
                        <a 
                          href={`tel:${item.contato_telefone}`}
                          className="ml-2 text-[#1a365d] font-mono font-bold underline inline-flex items-center space-x-1"
                        >
                          <Phone className="w-3 h-3" />
                          <span>{item.contato_telefone}</span>
                        </a>
                      </div>
                    </div>
                  </div>

                  {/* Action Button */}
                  <div className="pt-2">
                    <button
                      onClick={() => handleStartInspection(item.id)}
                      disabled={downloadingId === item.id}
                      className="w-full py-3 px-4 bg-[#1a365d] hover:bg-[#122846] text-white font-bold rounded-xl shadow-sm flex items-center justify-center space-x-2 transition active:scale-98 disabled:opacity-50 uppercase tracking-wider text-xs"
                    >
                      {downloadingId === item.id ? (
                        <>
                          <RefreshCw className="w-4 h-4 animate-spin" />
                          <span>Baixando Pacote da Vistoria...</span>
                        </>
                      ) : (
                        <>
                          <Download className="w-4 h-4" />
                          <span>{item.isDownloaded ? 'Abrir Vistoria Baixada' : 'Baixar Pacote e Iniciar Vistoria'}</span>
                        </>
                      )}
                    </button>
                  </div>

                </div>

              </div>
            );
          })}
        </div>
      )}

    </div>
  );
};
