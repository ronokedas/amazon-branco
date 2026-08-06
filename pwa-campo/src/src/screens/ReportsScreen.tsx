import React, { useEffect, useState } from 'react';
import { FileText, RefreshCw, ExternalLink, Ship, Calendar, ShieldCheck, Clock } from 'lucide-react';
import { ReportItem } from '../types';
import { getReportsAPI } from '../api';
import { getReportsLocal } from '../db';
import { formatDateBr } from '../utils';

interface ReportsScreenProps {
  onViewPdfReport: (reportId: string, reportItem?: ReportItem) => void;
}

export const ReportsScreen: React.FC<ReportsScreenProps> = ({ onViewPdfReport }) => {
  const [reports, setReports] = useState<ReportItem[]>([]);
  const [isLoading, setIsLoading] = useState<boolean>(true);
  const [errorMsg, setErrorMsg] = useState<string>('');

  const loadReports = async () => {
    setIsLoading(true);
    setErrorMsg('');

    try {
      const local = await getReportsLocal();
      if (local && local.length > 0) {
        setReports(local);
      }

      const res = await getReportsAPI();
      if (res.ok && res.relatorios) {
        setReports(res.relatorios);
      }
    } catch (err: any) {
      setErrorMsg('Erro ao atualizar relatórios: ' + err.message);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    loadReports();
  }, []);

  return (
    <div className="space-y-4 text-xs text-slate-900">
      
      {/* Top Banner */}
      <div className="flex items-center justify-between bg-white border border-slate-200 p-4 rounded-xl shadow-sm">
        <div>
          <h2 className="font-extrabold text-[#1a365d] text-base flex items-center space-x-2">
            <FileText className="w-5 h-5 text-[#1a365d]" />
            <span>Relatórios de Vistoria Transmitidos</span>
          </h2>
          <p className="text-slate-500 text-xs mt-0.5">
            Documentos auditáveis gerados no ERP Amazon Naval (TCPDF).
          </p>
        </div>

        <button
          onClick={loadReports}
          disabled={isLoading}
          className="p-2.5 bg-white hover:bg-slate-50 text-slate-700 rounded-xl border border-slate-200 transition active:scale-95 disabled:opacity-50 shadow-sm"
          title="Atualizar Relatórios"
        >
          <RefreshCw className={`w-4 h-4 ${isLoading ? 'animate-spin' : ''}`} />
        </button>
      </div>

      {isLoading && reports.length === 0 ? (
        <div className="p-8 text-center text-slate-500 animate-pulse">Buscando relatórios no ERP...</div>
      ) : reports.length === 0 ? (
        <div className="p-8 text-center bg-white border border-slate-200 rounded-xl space-y-2 shadow-sm">
          <FileText className="w-8 h-8 text-slate-400 mx-auto" />
          <p className="text-slate-600 font-bold">Nenhum relatório de vistoria disponível.</p>
        </div>
      ) : (
        <div className="space-y-3">
          {reports.map((rep) => {
            return (
              <div
                key={rep.id}
                className="bg-white border border-slate-200 rounded-xl p-4 shadow-sm hover:border-slate-300 transition space-y-3"
              >
                <div className="flex items-start justify-between">
                  <div>
                    <span className="font-mono text-[#1a365d] font-bold text-xs">{rep.numero}</span>
                    <h3 className="font-extrabold text-[#1a365d] text-base">{rep.embarcacao_nome}</h3>
                    <p className="text-amber-600 font-mono text-xs font-bold">{rep.embarcacao_registro}</p>
                  </div>

                  <span className={`px-2.5 py-1 rounded-md text-[10px] font-black border uppercase ${
                    rep.status === 'APROVADA'
                      ? 'bg-emerald-100 text-emerald-800 border-emerald-300'
                      : rep.status === 'APROVADA_COM_EXIGENCIAS'
                      ? 'bg-amber-100 text-amber-900 border-amber-300'
                      : 'bg-slate-100 text-[#1a365d] border-slate-300'
                  }`}>
                    {rep.status}
                  </span>
                </div>

                <div className="flex items-center justify-between text-slate-600 text-xs bg-slate-50 p-2.5 rounded-lg border border-slate-200 font-medium">
                  <span className="flex items-center space-x-1">
                    <Calendar className="w-3.5 h-3.5 text-[#1a365d]" />
                    <span>Realizada em: <strong className="text-slate-900">{formatDateBr(rep.data_vistoria)}</strong></span>
                  </span>

                  <span className="font-bold text-[#1a365d] uppercase">{rep.finalidade}</span>
                </div>

                <button
                  onClick={() => onViewPdfReport(rep.id, rep)}
                  className="w-full py-3 px-4 bg-[#1a365d] hover:bg-[#122846] text-white font-bold rounded-xl shadow-sm flex items-center justify-center space-x-2 transition active:scale-98 uppercase tracking-wider text-xs"
                >
                  <FileText className="w-4 h-4 text-amber-400" />
                  <span>Visualizar Relatório PDF Auditável</span>
                  <ExternalLink className="w-3.5 h-3.5" />
                </button>
              </div>
            );
          })}
        </div>
      )}

    </div>
  );
};
