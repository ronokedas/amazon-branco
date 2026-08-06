import React, { useEffect, useState } from 'react';
import { ClipboardList, Ship, Calendar, RefreshCw, FileText, CheckCircle2, AlertCircle } from 'lucide-react';
import { InspectionPackage } from '../types';
import { getAllPackagesLocal } from '../db';
import { formatDateBr } from '../utils';
import { processSyncQueue } from '../sync';

interface InspectionsScreenProps {
  onOpenChecklist: (agendamentoId: string) => void;
  onViewPdfReport: (reportId: string, pkg?: InspectionPackage) => void;
}

export const InspectionsScreen: React.FC<InspectionsScreenProps> = ({
  onOpenChecklist,
  onViewPdfReport,
}) => {
  const [packages, setPackages] = useState<InspectionPackage[]>([]);
  const [isLoading, setIsLoading] = useState<boolean>(true);
  const [isSyncing, setIsSyncing] = useState<boolean>(false);

  const loadPackages = async () => {
    setIsLoading(true);
    const pkgs = await getAllPackagesLocal();
    setPackages(pkgs);
    setIsLoading(false);
  };

  useEffect(() => {
    loadPackages();
  }, []);

  const handleForceSync = async () => {
    setIsSyncing(true);
    await processSyncQueue();
    await loadPackages();
    setIsSyncing(false);
  };

  return (
    <div className="space-y-4 text-xs text-slate-900">
      
      {/* Top Banner */}
      <div className="flex items-center justify-between bg-white border border-slate-200 p-4 rounded-xl shadow-sm">
        <div>
          <h2 className="font-extrabold text-[#1a365d] text-base flex items-center space-x-2">
            <ClipboardList className="w-5 h-5 text-[#1a365d]" />
            <span>Vistorias Baixadas no Aparelho</span>
          </h2>
          <p className="text-slate-500 text-xs mt-0.5">
            Pacotes de vistoria salvos localmente para execução e envio offline.
          </p>
        </div>

        <button
          onClick={handleForceSync}
          disabled={isSyncing}
          className="p-2.5 bg-amber-500 hover:bg-amber-600 text-[#1a365d] rounded-xl font-bold transition active:scale-95 disabled:opacity-50 flex items-center space-x-1.5 shadow-sm"
          title="Sincronizar Fila"
        >
          <RefreshCw className={`w-4 h-4 ${isSyncing ? 'animate-spin' : ''}`} />
          <span className="hidden sm:inline font-bold uppercase text-[11px]">Sincronizar Fila</span>
        </button>
      </div>

      {isLoading ? (
        <div className="p-8 text-center text-slate-500 animate-pulse">Carregando vistorias locais...</div>
      ) : packages.length === 0 ? (
        <div className="p-8 text-center bg-white border border-slate-200 rounded-xl space-y-2 shadow-sm">
          <Ship className="w-8 h-8 text-slate-400 mx-auto" />
          <p className="text-slate-600 font-bold">Nenhuma vistoria baixada localmente ainda.</p>
          <p className="text-slate-500 text-xs">Acesse a aba <strong className="text-[#1a365d]">Agenda</strong> para baixar pacotes de campo.</p>
        </div>
      ) : (
        <div className="space-y-3">
          {packages.map((pkg) => {
            const status = pkg.vistoria.status || pkg.agendamento.status;
            const isFinished = status === 'AGUARDANDO_APROVACAO' || status === 'APROVADA';

            return (
              <div
                key={pkg.agendamento.id}
                className="bg-white border border-slate-200 rounded-xl p-4 shadow-sm hover:border-slate-300 transition space-y-3"
              >
                <div className="flex items-start justify-between">
                  <div>
                    <h3 className="font-extrabold text-[#1a365d] text-base">{pkg.agendamento.embarcacao_nome}</h3>
                    <p className="text-amber-600 font-mono text-xs font-bold">{pkg.agendamento.embarcacao_registro}</p>
                  </div>

                  <span className={`px-2.5 py-1 rounded-md text-[10px] font-black border uppercase ${
                    isFinished
                      ? 'bg-emerald-100 text-emerald-800 border-emerald-300'
                      : 'bg-slate-100 text-[#1a365d] border-slate-300'
                  }`}>
                    {status}
                  </span>
                </div>

                <div className="grid grid-cols-2 gap-2 text-slate-700 text-xs bg-slate-50 p-2.5 rounded-lg border border-slate-200">
                  <div><span className="text-slate-500 font-medium">Tipo:</span> <span className="font-bold">{pkg.agendamento.tipo_vistoria}</span></div>
                  <div><span className="text-slate-500 font-medium">Data:</span> <span className="font-bold">{formatDateBr(pkg.vistoria.data_vistoria)}</span></div>
                  <div><span className="text-slate-500 font-medium">Cliente:</span> <span className="font-bold">{pkg.agendamento.cliente_nome}</span></div>
                  <div><span className="text-slate-500 font-medium">Prazo:</span> <span className="font-bold">{pkg.vistoria.prazo_exigencias_dias} dias</span></div>
                </div>

                <div className="pt-1 flex items-center space-x-2">
                  <button
                    onClick={() => onOpenChecklist(pkg.agendamento.id)}
                    className="flex-1 py-2.5 px-3 bg-[#1a365d] hover:bg-[#122846] text-white font-bold rounded-xl transition text-center uppercase tracking-wider text-xs shadow-sm"
                  >
                    Abrir Checklist
                  </button>

                  {isFinished && (
                    <button
                      onClick={() => onViewPdfReport(pkg.agendamento.id, pkg)}
                      className="py-2.5 px-3 bg-white hover:bg-slate-50 text-slate-800 border border-slate-200 font-bold rounded-xl transition flex items-center space-x-1 shadow-sm text-xs"
                    >
                      <FileText className="w-4 h-4 text-[#1a365d]" />
                      <span>Ver PDF</span>
                    </button>
                  )}
                </div>

              </div>
            );
          })}
        </div>
      )}

    </div>
  );
};
