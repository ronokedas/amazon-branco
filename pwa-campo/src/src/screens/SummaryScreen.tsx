import React, { useState } from 'react';
import { 
  CheckCircle2, 
  XCircle, 
  MinusCircle, 
  FileText, 
  Camera, 
  Send, 
  AlertTriangle, 
  ExternalLink, 
  Ship, 
  User, 
  Calendar, 
  Clock, 
  MapPin, 
  Phone, 
  RefreshCw,
  ShieldCheck
} from 'lucide-react';
import { InspectionPackage, SyncOperation } from '../types';
import { formatDateBr, generateUUID } from '../utils';
import { enqueueSyncOp, savePackageLocal } from '../db';
import { processSyncQueue } from '../sync';

interface SummaryScreenProps {
  packageData: InspectionPackage;
  onBackToChecklist: () => void;
  onViewPdfReport: (reportId: string, pkg?: InspectionPackage) => void;
}

export const SummaryScreen: React.FC<SummaryScreenProps> = ({
  packageData,
  onBackToChecklist,
  onViewPdfReport,
}) => {
  const [pkg, setPkg] = useState<InspectionPackage>(packageData);
  const [isSubmitting, setIsSubmitting] = useState<boolean>(false);
  const [validationErrors, setValidationErrors] = useState<string[]>([]);
  const [submitSuccess, setSubmitSuccess] = useState<boolean>(false);
  const [pdfReportUrl, setPdfReportUrl] = useState<string>('');

  // Count items
  let countConforme = 0;
  let countNaoConforme = 0;
  let countNaoAplica = 0;
  let totalPhotos = 0;

  const missingPhotosItems: string[] = [];
  const missingDeadlineItems: string[] = [];

  pkg.categorias.forEach(cat => {
    cat.itens.forEach(item => {
      if (item.resposta) {
        if (item.resposta.status === 'CONFORME') countConforme++;
        if (item.resposta.status === 'NAO_SE_APLICA') countNaoAplica++;
        
        if (item.resposta.status === 'NAO_CONFORME') {
          countNaoConforme++;
          
          // Check photo validation
          const anexosCount = item.anexos ? item.anexos.length : 0;
          totalPhotos += anexosCount;
          if (anexosCount === 0) {
            missingPhotosItems.push(`${item.item_normam}: ${item.descricao.slice(0, 40)}...`);
          }

          // Check deadline/AS validation
          if (!item.resposta.sem_prazo && !item.resposta.vencimento) {
            missingDeadlineItems.push(`${item.item_normam}: ${item.descricao.slice(0, 40)}...`);
          }
        } else if (item.anexos) {
          totalPhotos += item.anexos.length;
        }
      }
    });
  });

  pkg.exigencias_avulsas.forEach(av => {
    if (av.anexos) totalPhotos += av.anexos.length;
  });

  const handleValidationAndSubmit = async () => {
    const errors: string[] = [];

    // 1. Validate inspection date
    if (!pkg.vistoria.data_vistoria) {
      errors.push('A data de realização da vistoria é obrigatória.');
    }

    // 2. Validate non-conforming items deadlines / AS
    if (missingDeadlineItems.length > 0) {
      errors.push('Defina o prazo geral ou marque individualmente a exigência como A/S — Antes de suspender.');
    }

    // 3. Validate non-conforming items mandatory photos
    if (missingPhotosItems.length > 0) {
      errors.push(`Toda exigência NÃO CONFORME precisa de pelo menos 1 evidência fotográfica. Faltam fotos em ${missingPhotosItems.length} item(ns).`);
    }

    // 4. Validate custom avulsa descriptions
    const emptyAvulsa = pkg.exigencias_avulsas.some(av => !av.descricao.trim());
    if (emptyAvulsa) {
      errors.push('Todas as exigências avulsas adicionadas precisam ter descrição preenchida.');
    }

    if (errors.length > 0) {
      setValidationErrors(errors);
      return;
    }

    setValidationErrors([]);
    setIsSubmitting(true);

    try {
      // Create draft sync operation
      const draftOpId = generateUUID();
      const draftOp: SyncOperation = {
        operacao_id: draftOpId,
        agendamento_id: pkg.agendamento.id,
        tipo: 'rascunho',
        payload: {
          operacao_id: draftOpId,
          versao: pkg.vistoria.mobile_versao || 1,
          respostas: pkg.categorias.flatMap(cat => 
            cat.itens
              .filter(i => i.resposta !== null)
              .map(i => ({
                catalogo_id: i.id,
                status: i.resposta!.status,
                observacao: i.resposta!.observacao || '',
                vencimento: i.resposta!.vencimento || '',
                sem_prazo: i.resposta!.sem_prazo,
                item_normam: i.item_normam,
              }))
          ),
          dados_vistoria: {
            data_vistoria: pkg.vistoria.data_vistoria,
            prazo_exigencias_dias: pkg.vistoria.prazo_exigencias_dias,
            operador_nome: pkg.vistoria.operador_nome,
            observacoes_tecnicas: pkg.vistoria.observacoes_tecnicas,
          },
          exigencias_avulsas: pkg.exigencias_avulsas.map(av => ({
            bloco_vistoria: av.bloco_vistoria,
            descricao: av.descricao,
            item_normam: av.item_normam,
            observacao: av.observacao,
            status_item: av.status_item,
            vencimento: av.vencimento,
            antes_de_suspender: av.antes_de_suspender,
            id_local: av.id,
          })),
        },
        timestamp: Date.now(),
        status: 'pendente',
      };

      // Create finalization operation
      const finalOpId = generateUUID();
      const finalOp: SyncOperation = {
        operacao_id: finalOpId,
        agendamento_id: pkg.agendamento.id,
        tipo: 'finalizacao',
        payload: {
          operacao_id: finalOpId,
          tipo: 'finalizacao',
        },
        timestamp: Date.now() + 100,
        status: 'pendente',
      };

      // Enqueue operations in IndexedDB
      await enqueueSyncOp(draftOp);
      await enqueueSyncOp(finalOp);

      // Update package status locally
      const updatedPkg: InspectionPackage = {
        ...pkg,
        vistoria: { ...pkg.vistoria, status: 'AGUARDANDO_APROVACAO' },
        agendamento: { ...pkg.agendamento, status: 'AGUARDANDO_APROVACAO' },
      };
      await savePackageLocal(updatedPkg);
      setPkg(updatedPkg);

      // Trigger queue process if online
      if (navigator.onLine) {
        await processSyncQueue();
      }

      setSubmitSuccess(true);
      setPdfReportUrl(pkg.agendamento.relatorio_url || null);

    } catch (err: any) {
      setValidationErrors(['Falha ao salvar operação no aparelho: ' + err.message]);
    } finally {
      setIsSubmitting(false);
    }
  };

  const vencimentoCalculadoFormatted = formatDateBr(
    pkg.vistoria.data_vistoria 
      ? new Date(new Date(pkg.vistoria.data_vistoria + 'T00:00:00').getTime() + (pkg.vistoria.prazo_exigencias_dias * 86400000)).toISOString().split('T')[0]
      : ''
  );

  return (
    <div className="space-y-4 text-xs text-slate-900">
      
      {/* Header Banner */}
      <div className="p-4 bg-white border border-slate-200 rounded-xl shadow-sm space-y-3">
        <div className="flex items-center justify-between">
          <div className="flex items-center space-x-2">
            <Ship className="w-5 h-5 text-[#1a365d] shrink-0" />
            <h2 className="font-extrabold text-[#1a365d] text-base">Resumo da Vistoria de Campo</h2>
          </div>
          <span className="px-2.5 py-1 bg-amber-100 text-amber-900 font-mono text-xs font-bold rounded-md border border-amber-300">
            {pkg.vistoria.numero}
          </span>
        </div>

        {/* Vessel metadata */}
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 p-3 bg-slate-50 rounded-lg border border-slate-200 text-slate-700">
          <div><span className="text-slate-500 font-medium">Embarcação:</span> <strong className="text-slate-900">{pkg.agendamento.embarcacao_nome}</strong></div>
          <div><span className="text-slate-500 font-medium">Registro:</span> <strong className="text-amber-700 font-mono font-bold">{pkg.agendamento.embarcacao_registro}</strong></div>
          <div><span className="text-slate-500 font-medium">Cliente:</span> <span className="font-semibold text-slate-800">{pkg.agendamento.cliente_nome}</span></div>
          <div><span className="text-slate-500 font-medium">Local:</span> <span className="font-semibold text-slate-800">{pkg.agendamento.local}</span></div>
        </div>
      </div>

      {/* Counters Summary Box */}
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div className="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-center space-y-1 shadow-sm">
          <CheckCircle2 className="w-5 h-5 text-emerald-600 mx-auto" />
          <div className="font-black text-emerald-900 text-lg">{countConforme}</div>
          <div className="text-[10px] text-emerald-800 font-bold uppercase">Conformes</div>
        </div>

        <div className="p-3 bg-rose-50 border border-rose-200 rounded-xl text-center space-y-1 shadow-sm">
          <XCircle className="w-5 h-5 text-rose-600 mx-auto" />
          <div className="font-black text-rose-900 text-lg">{countNaoConforme}</div>
          <div className="text-[10px] text-rose-800 font-bold uppercase">Não Conformes</div>
        </div>

        <div className="p-3 bg-slate-100 border border-slate-200 rounded-xl text-center space-y-1 shadow-sm">
          <MinusCircle className="w-5 h-5 text-slate-500 mx-auto" />
          <div className="font-black text-slate-800 text-lg">{countNaoAplica}</div>
          <div className="text-[10px] text-slate-600 font-bold uppercase">Não Aplica</div>
        </div>

        <div className="p-3 bg-blue-50 border border-blue-200 rounded-xl text-center space-y-1 shadow-sm">
          <Camera className="w-5 h-5 text-[#1a365d] mx-auto" />
          <div className="font-black text-[#1a365d] text-lg">{totalPhotos}</div>
          <div className="text-[10px] text-[#1a365d] font-bold uppercase">Fotos Anexadas</div>
        </div>
      </div>

      {/* Parameters & Responsible Persons Summary */}
      <div className="bg-white border border-slate-200 rounded-xl p-4 shadow-sm space-y-3">
        <h3 className="font-bold text-[#1a365d] text-xs uppercase tracking-wider">
          Parâmetros e Prazos Definidos
        </h3>

        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 text-slate-700">
          <div className="flex items-center space-x-2 p-2.5 bg-slate-50 rounded-lg border border-slate-200">
            <Calendar className="w-4 h-4 text-[#1a365d] shrink-0" />
            <div>
              <span className="text-slate-500 block text-[10px] font-medium">Data da Vistoria</span>
              <strong className="text-slate-900 font-bold">{formatDateBr(pkg.vistoria.data_vistoria)}</strong>
            </div>
          </div>

          <div className="flex items-center space-x-2 p-2.5 bg-slate-50 rounded-lg border border-slate-200">
            <Clock className="w-4 h-4 text-amber-600 shrink-0" />
            <div>
              <span className="text-slate-500 block text-[10px] font-medium">Prazo Geral Escolhido</span>
              <strong className="text-slate-900 font-bold">{pkg.vistoria.prazo_exigencias_dias} dias (vence em {vencimentoCalculadoFormatted})</strong>
            </div>
          </div>

          <div className="flex items-center space-x-2 p-2.5 bg-slate-50 rounded-lg border border-slate-200">
            <User className="w-4 h-4 text-emerald-600 shrink-0" />
            <div>
              <span className="text-slate-500 block text-[10px] font-medium">Acompanhado por (Presente)</span>
              <strong className="text-slate-900 font-bold">{pkg.vistoria.operador_nome || 'Não informado'}</strong>
            </div>
          </div>

          <div className="flex items-center space-x-2 p-2.5 bg-slate-50 rounded-lg border border-slate-200">
            <Phone className="w-4 h-4 text-indigo-600 shrink-0" />
            <div>
              <span className="text-slate-500 block text-[10px] font-medium">Responsável Fechamento</span>
              <strong className="text-slate-900 font-bold">{pkg.agendamento.contato_nome} ({pkg.agendamento.contato_telefone})</strong>
            </div>
          </div>
        </div>

        {pkg.vistoria.observacoes_tecnicas && (
          <div>
            <span className="text-slate-600 font-bold block mb-1">Observações Técnicas:</span>
            <p className="p-3 bg-slate-50 rounded-lg border border-slate-200 text-slate-800 whitespace-pre-wrap leading-relaxed">
              {pkg.vistoria.observacoes_tecnicas}
            </p>
          </div>
        )}
      </div>

      {/* Validation Errors Box */}
      {validationErrors.length > 0 && (
        <div className="p-4 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 space-y-2 animate-fade-in shadow-sm">
          <div className="flex items-center space-x-2 font-bold text-rose-900">
            <AlertTriangle className="w-5 h-5 text-rose-600 shrink-0" />
            <span>Pendências Impeditivas para o Envio:</span>
          </div>
          <ul className="list-disc list-inside space-y-1 text-[11px] text-rose-800">
            {validationErrors.map((err, idx) => (
              <li key={idx}>{err}</li>
            ))}
          </ul>
        </div>
      )}

      {/* Submission Success Banner */}
      {submitSuccess ? (
        <div className="p-5 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-900 space-y-4 shadow-sm animate-fade-in">
          <div className="flex items-center space-x-3">
            <div className="p-2 bg-emerald-600 text-white rounded-lg font-bold">
              <ShieldCheck className="w-6 h-6" />
            </div>
            <div>
              <h3 className="font-bold text-emerald-950 text-base">Vistoria Finalizada com Sucesso!</h3>
              <p className="text-xs text-emerald-800">
                Os dados foram registrados no pacote local e enfileirados para transmissão ao ERP.
              </p>
            </div>
          </div>

          <p className="text-xs text-slate-700 leading-relaxed bg-white p-3 rounded-lg border border-slate-200">
            Tudo está salvo neste aparelho. O ERP receberá as evidências e atualizará o status da vistoria para <strong>AGUARDANDO APROVAÇÃO</strong>.
          </p>

          <button
            type="button"
            onClick={() => onViewPdfReport(pkg.agendamento.id, pkg)}
            className="w-full py-3.5 px-4 bg-[#1a365d] hover:bg-[#122846] text-white font-bold rounded-xl shadow-sm flex items-center justify-center space-x-2 transition active:scale-98 uppercase tracking-wider"
          >
            <FileText className="w-5 h-5 text-amber-400" />
            <span>Ver Relatório em PDF Auditável</span>
            <ExternalLink className="w-4 h-4" />
          </button>
        </div>
      ) : (
        /* Action Buttons */
        <div className="space-y-2 pt-2">
          <button
            type="button"
            onClick={handleValidationAndSubmit}
            disabled={isSubmitting}
            className="w-full py-3.5 px-6 bg-[#1a365d] hover:bg-[#122846] text-white font-black text-sm rounded-xl shadow-md flex items-center justify-center space-x-2 transition active:scale-98 disabled:opacity-50 uppercase tracking-wider"
          >
            {isSubmitting ? (
              <>
                <RefreshCw className="w-5 h-5 animate-spin" />
                <span>Enviando e Gerando Relatório...</span>
              </>
            ) : (
              <>
                <Send className="w-5 h-5 text-amber-400" />
                <span>Enviar Vistoria para Aprovação</span>
              </>
            )}
          </button>

          <button
            type="button"
            onClick={onBackToChecklist}
            className="w-full py-3 px-4 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-xl border border-slate-200 transition text-xs shadow-sm"
          >
            Voltar ao Checklist e Ajustar
          </button>
        </div>
      )}

    </div>
  );
};
