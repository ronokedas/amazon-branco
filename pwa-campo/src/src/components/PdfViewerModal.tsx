import React from 'react';
import { X, Printer, ShieldCheck, Download, AlertTriangle, FileText } from 'lucide-react';
import { InspectionPackage, ReportItem } from '../types';
import { formatDateBr } from '../utils';

interface PdfViewerModalProps {
  reportId: string;
  pkg?: InspectionPackage | null;
  reportItem?: ReportItem | null;
  onClose: () => void;
}

export const PdfViewerModal: React.FC<PdfViewerModalProps> = ({
  reportId,
  pkg,
  reportItem,
  onClose,
}) => {
  const vesselName = pkg?.agendamento.embarcacao_nome || reportItem?.embarcacao_nome || 'B/M AMAZON KING III';
  const vesselCpnp = pkg?.agendamento.embarcacao_registro || reportItem?.embarcacao_registro || 'CPNP 441-009823-1';
  const clientName = pkg?.agendamento.cliente_nome || 'Navegação Rio Negro Ltda.';
  const dateBr = formatDateBr(pkg?.vistoria.data_vistoria || reportItem?.data_vistoria || new Date().toISOString());
  const inspectorName = pkg?.agendamento.vistoriador_nome || 'Carlos Eduardo Silva';
  const operatorName = pkg?.vistoria.operador_nome || pkg?.agendamento.contato_nome || 'Cap. Raimundo Nonato';
  const reportNumber = pkg?.vistoria.numero || reportItem?.numero || `VIS-2026-${reportId.slice(-3).toUpperCase()}`;

  // Gather non-conforming items for PDF view
  const nonConformities: Array<{
    itemNormam: string;
    descricao: string;
    vencimento: string;
    isAS: boolean;
    observacao?: string;
  }> = [];

  if (pkg) {
    pkg.categorias.forEach(cat => {
      cat.itens.forEach(item => {
        if (item.resposta?.status === 'NAO_CONFORME') {
          nonConformities.push({
            itemNormam: item.item_normam,
            descricao: item.descricao,
            vencimento: item.resposta.sem_prazo ? 'A/S' : formatDateBr(item.resposta.vencimento),
            isAS: item.resposta.sem_prazo,
            observacao: item.resposta.observacao,
          });
        }
      });
    });

    pkg.exigencias_avulsas.forEach(av => {
      nonConformities.push({
        itemNormam: av.item_normam || 'EXIGÊNCIA AVULSA',
        descricao: av.descricao,
        vencimento: av.antes_de_suspender ? 'A/S' : formatDateBr(av.vencimento),
        isAS: av.antes_de_suspender,
        observacao: av.observacao,
      });
    });
  }

  const hasASItems = nonConformities.some(n => n.isAS);

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/85 backdrop-blur-md p-2 sm:p-4 overflow-y-auto animate-fade-in">
      <div className="relative w-full max-w-3xl bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[95vh]">
        
        {/* Modal Top Control Bar */}
        <div className="flex items-center justify-between p-3 sm:p-4 bg-slate-800/90 border-b border-slate-700/80">
          <div className="flex items-center space-x-2">
            <FileText className="w-5 h-5 text-blue-400" />
            <div>
              <h3 className="font-semibold text-slate-100 text-sm sm:text-base">
                Relatório de Vistoria Naval (PDF Auditável)
              </h3>
              <p className="text-xs text-slate-400 font-mono">Gerado via TCPDF / ERP Amazon Naval ({reportNumber})</p>
            </div>
          </div>

          <div className="flex items-center space-x-2">
            <button
              onClick={() => window.print()}
              className="p-2 text-slate-300 hover:text-white rounded-lg bg-slate-700/60 hover:bg-slate-700 transition"
              title="Imprimir Relatório"
            >
              <Printer className="w-4 h-4" />
            </button>
            <button
              onClick={onClose}
              className="p-2 text-slate-400 hover:text-white rounded-lg hover:bg-slate-700 transition"
            >
              <X className="w-5 h-5" />
            </button>
          </div>
        </div>

        {/* PDF Simulated Sheet Area */}
        <div className="p-4 sm:p-8 overflow-y-auto bg-slate-950 text-slate-900 flex-1 space-y-6 select-text">
          
          {/* Simulated White Paper */}
          <div className="bg-white p-6 sm:p-10 rounded-xl shadow-xl text-slate-900 font-sans border border-slate-200 space-y-6 text-xs sm:text-sm">
            
            {/* Document Header */}
            <div className="flex flex-col sm:flex-row sm:items-center justify-between border-b-2 border-blue-900 pb-4 gap-4">
              <div className="flex items-center space-x-3">
                <div className="w-12 h-12 bg-blue-950 text-amber-400 rounded-lg flex items-center justify-center font-bold text-xl tracking-wider">
                  AN
                </div>
                <div>
                  <h1 className="font-extrabold text-blue-950 text-base sm:text-lg tracking-tight uppercase">
                    AMAZON NAVAL CERTIFICAÇÃO
                  </h1>
                  <p className="text-slate-600 text-[11px] font-medium">
                    Sociedade Classificadora & Vistorias NORMAM/DPC
                  </p>
                </div>
              </div>

              <div className="text-right text-[11px] text-slate-600 font-mono">
                <div className="font-bold text-blue-900 text-xs">{reportNumber}</div>
                <div>Data: {dateBr}</div>
                <div>Via Oficial Auditável</div>
              </div>
            </div>

            {/* Vessel & Inspection Metadata Table */}
            <div className="grid grid-cols-2 sm:grid-cols-3 gap-3 p-3 bg-slate-50 rounded-lg border border-slate-200 text-xs">
              <div>
                <span className="block text-slate-500 font-semibold text-[10px] uppercase">Embarcação</span>
                <span className="font-bold text-slate-900">{vesselName}</span>
              </div>
              <div>
                <span className="block text-slate-500 font-semibold text-[10px] uppercase">Registro / CPNP</span>
                <span className="font-mono text-slate-900">{vesselCpnp}</span>
              </div>
              <div>
                <span className="block text-slate-500 font-semibold text-[10px] uppercase">Cliente / Armador</span>
                <span className="font-medium text-slate-800">{clientName}</span>
              </div>
              <div>
                <span className="block text-slate-500 font-semibold text-[10px] uppercase">Vistoriador Responsável</span>
                <span className="font-medium text-slate-800">{inspectorName}</span>
              </div>
              <div>
                <span className="block text-slate-500 font-semibold text-[10px] uppercase">Acompanhado por</span>
                <span className="font-medium text-slate-800">{operatorName}</span>
              </div>
              <div>
                <span className="block text-slate-500 font-semibold text-[10px] uppercase">Finalidade</span>
                <span className="font-semibold text-blue-900">{pkg?.vistoria.finalidade || 'VISTORIA DE CAMPO'}</span>
              </div>
            </div>

            {/* Critical A/S Warning Banner if applicable */}
            {hasASItems && (
              <div className="p-3 bg-amber-50 border-l-4 border-amber-600 rounded-r-lg text-amber-900 text-xs space-y-1">
                <div className="flex items-center space-x-2 font-bold text-amber-950">
                  <AlertTriangle className="w-4 h-4 text-amber-600 shrink-0" />
                  <span>ALERTA DE SUSPENSÃO (A/S — Antes de Suspender)</span>
                </div>
                <p className="text-[11px] leading-relaxed text-amber-800">
                  Esta vistoria contém exigências classificadas como <strong>A/S (Antes de Suspender)</strong>. Os certificados de segurança desta embarcação permanecem bloqueados e suspensos no sistema até a comprovação e sanção oficial do cumprimento destas pendências.
                </p>
              </div>
            )}

            {/* Non-Conformities / Exigencies Table */}
            <div>
              <h2 className="font-bold text-blue-950 text-sm uppercase mb-2 border-b border-slate-200 pb-1">
                Quadro de Exigências / Não Conformidades
              </h2>

              {nonConformities.length === 0 ? (
                <div className="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs rounded-lg flex items-center space-x-2">
                  <ShieldCheck className="w-4 h-4 text-emerald-600 shrink-0" />
                  <span>Nenhuma não conformidade apontada nesta vistoria. Embarcação totalmente conforme com as normas DPC/NORMAM.</span>
                </div>
              ) : (
                <div className="overflow-x-auto border border-slate-200 rounded-lg">
                  <table className="w-full text-left border-collapse text-[11px]">
                    <thead>
                      <tr className="bg-slate-100 text-slate-700 font-bold border-b border-slate-200 uppercase text-[10px]">
                        <th className="p-2 w-28">Item NORMAM</th>
                        <th className="p-2">Descrição da Exigência</th>
                        <th className="p-2 w-28 text-center">Vencimento</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-200">
                      {nonConformities.map((item, idx) => (
                        <tr key={idx} className={item.isAS ? 'bg-amber-50/60' : ''}>
                          <td className="p-2 font-mono text-slate-700 font-semibold align-top">{item.itemNormam}</td>
                          <td className="p-2 text-slate-900 align-top">
                            <div>{item.descricao}</div>
                            {item.observacao && (
                              <div className="text-[10px] text-slate-500 italic mt-0.5">Obs: {item.observacao}</div>
                            )}
                          </td>
                          <td className="p-2 text-center align-top font-bold">
                            {item.isAS ? (
                              <span className="inline-block px-2 py-0.5 bg-amber-600 text-white rounded font-mono text-[10px]">
                                A/S
                              </span>
                            ) : (
                              <span className="font-mono text-slate-800">{item.vencimento}</span>
                            )}
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}
            </div>

            {/* Technical Observations Section */}
            {pkg?.vistoria.observacoes_tecnicas && (
              <div>
                <h2 className="font-bold text-blue-950 text-xs uppercase mb-1">
                  Observações Técnicas do Vistoriador
                </h2>
                <div className="p-3 bg-slate-50 rounded-lg border border-slate-200 text-[11px] text-slate-800 whitespace-pre-wrap leading-relaxed">
                  {pkg.vistoria.observacoes_tecnicas}
                </div>
              </div>
            )}

            {/* Signatures Footer */}
            <div className="pt-8 border-t border-slate-200 grid grid-cols-2 gap-8 text-center text-[10px]">
              <div>
                <div className="border-b border-slate-400 w-4/5 mx-auto mb-1"></div>
                <div className="font-bold text-slate-900">{inspectorName}</div>
                <div className="text-slate-500">Vistoriador Naval Credenciado</div>
              </div>

              <div>
                <div className="border-b border-slate-400 w-4/5 mx-auto mb-1"></div>
                <div className="font-bold text-slate-900">{operatorName}</div>
                <div className="text-slate-500">Responsável / Comandante Acompanhante</div>
              </div>
            </div>

          </div>

        </div>

        {/* Footer actions */}
        <div className="p-3 bg-slate-800/80 border-t border-slate-700/80 flex items-center justify-between text-xs">
          <span className="text-slate-400 font-mono">Gerado via TCPDF • ERP Amazon Naval</span>
          <button
            onClick={onClose}
            className="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white font-medium rounded-xl transition"
          >
            Fechar Visualização
          </button>
        </div>

      </div>
    </div>
  );
};
