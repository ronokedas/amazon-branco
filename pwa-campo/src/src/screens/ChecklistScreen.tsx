import React, { useState, useEffect } from 'react';
import { 
  CheckCircle2, 
  XCircle, 
  MinusCircle, 
  Camera, 
  Search, 
  Calendar, 
  Clock, 
  Lock, 
  AlertTriangle, 
  Plus, 
  Trash2, 
  ChevronDown, 
  ChevronUp, 
  User, 
  Phone, 
  ArrowRight, 
  Save, 
  FileText,
  AlertCircle
} from 'lucide-react';
import { 
  InspectionPackage, 
  CatalogoItem, 
  ResponseStatus, 
  ExigenciaAvulsa, 
  InspectionBlock, 
  AvulsaStatus, 
  Anexo 
} from '../types';
import { calculateVencimento, formatDateBr, getTodayIso, generateUUID } from '../utils';
import { EvidenceModal } from '../components/EvidenceModal';
import { savePackageLocal } from '../db';

interface ChecklistScreenProps {
  packageData: InspectionPackage;
  onGoToSummary: (updatedPackage: InspectionPackage) => void;
}

export const ChecklistScreen: React.FC<ChecklistScreenProps> = ({
  packageData,
  onGoToSummary,
}) => {
  const [pkg, setPkg] = useState<InspectionPackage>(packageData);
  const [searchQuery, setSearchQuery] = useState<string>('');
  const [expandedCategories, setExpandedCategories] = useState<Record<string, boolean>>({
    cat_seco: true,
    cat_flutuando: fontDefaultExpanded(packageData.categorias[0]?.id),
  });
  
  // Evidence modal state
  const [selectedEvidenceItem, setSelectedEvidenceItem] = useState<{
    item: CatalogoItem | ExigenciaAvulsa;
    isAvulsa: boolean;
  } | null>(null);

  // New Avulsa modal / inline form state
  const [showAddAvulsa, setShowAddAvulsa] = useState<boolean>(false);
  const [newAvulsaBloco, setNewAvulsaBloco] = useState<InspectionBlock>('seco');
  const [newAvulsaDesc, setNewAvulsaDesc] = useState<string>('');
  const [newAvulsaNormam, setNewAvulsaNormam] = useState<string>('NORMAM-202/DPC');
  const [newAvulsaObs, setNewAvulsaObs] = useState<string>('');
  const [newAvulsaAS, setNewAvulsaAS] = useState<boolean>(false);

  const isCumprimentoMode = pkg.agendamento.finalidade === 'CUMPRIMENTO_EXIGENCIAS';

  function fontDefaultExpanded(firstCatId?: string) {
    return firstCatId ? true : false;
  }

  // Save package locally whenever modified
  const updateAndSavePkg = (newPkg: InspectionPackage) => {
    setPkg(newPkg);
    savePackageLocal(newPkg);
  };

  // Recalculate vencimento for all NON_CONFORME catalog items and avulsa items
  const handleDeadlineOrDateChange = (newPrazo: number, newDateIso: string) => {
    const updatedCategorias = pkg.categorias.map(cat => ({
      ...cat,
      itens: cat.itens.map(item => {
        if (item.resposta && item.resposta.status === 'NAO_CONFORME') {
          return {
            ...item,
            resposta: {
              ...item.resposta,
              vencimento: item.resposta.sem_prazo ? '' : calculateVencimento(newDateIso, newPrazo),
            },
          };
        }
        return item;
      }),
    }));

    const updatedAvulsas = pkg.exigencias_avulsas.map(av => ({
      ...av,
      vencimento: av.antes_de_suspender ? '' : calculateVencimento(newDateIso, newPrazo),
    }));

    const updatedPkg: InspectionPackage = {
      ...pkg,
      vistoria: {
        ...pkg.vistoria,
        prazo_exigencias_dias: newPrazo,
        data_vistoria: newDateIso,
      },
      categorias: updatedCategorias,
      exigencias_avulsas: updatedAvulsas,
    };

    updateAndSavePkg(updatedPkg);
  };

  // Set item status CONFORME, NAO_CONFORME, NAO_SE_APLICA
  const handleItemStatusChange = (
    catId: string, 
    itemId: string, 
    newStatus: ResponseStatus
  ) => {
    let targetItem: CatalogoItem | null = null;

    const updatedCategorias = pkg.categorias.map(cat => {
      if (cat.id !== catId) return cat;
      return {
        ...cat,
        itens: cat.itens.map(item => {
          if (item.id !== itemId) return item;

          const currentResp = item.resposta;
          const isAS = currentResp?.sem_prazo || false;
          const vencimento = newStatus === 'NAO_CONFORME'
            ? (isAS ? '' : calculateVencimento(pkg.vistoria.data_vistoria, pkg.vistoria.prazo_exigencias_dias))
            : '';

          const updatedItem: CatalogoItem = {
            ...item,
            resposta: {
              status: newStatus,
              observacao: currentResp?.observacao || '',
              vencimento,
              sem_prazo: isAS,
              item_normam: item.item_normam,
            },
          };

          targetItem = updatedItem;
          return updatedItem;
        }),
      };
    });

    const updatedPkg = { ...pkg, categorias: updatedCategorias };
    updateAndSavePkg(updatedPkg);

    // If marked NAO_CONFORME or CONFORME, trigger evidence screen if requested
    if (newStatus === 'NAO_CONFORME' && targetItem) {
      setSelectedEvidenceItem({ item: targetItem, isAvulsa: false });
    }
  };

  // Toggle A/S (Antes de Suspender) for a catalog item
  const handleToggleASTableItem = (catId: string, itemId: string) => {
    const updatedCategorias = pkg.categorias.map(cat => {
      if (cat.id !== catId) return cat;
      return {
        ...cat,
        itens: cat.itens.map(item => {
          if (item.id !== itemId || !item.resposta) return item;

          const newAS = !item.resposta.sem_prazo;
          const newVencimento = newAS 
            ? '' 
            : calculateVencimento(pkg.vistoria.data_vistoria, pkg.vistoria.prazo_exigencias_dias);

          return {
            ...item,
            resposta: {
              ...item.resposta,
              sem_prazo: newAS,
              vencimento: newVencimento,
            },
          };
        }),
      };
    });

    updateAndSavePkg({ ...pkg, categorias: updatedCategorias });
  };

  // Save evidence photos or technical notes
  const handleSaveItemEvidences = (anexos: Anexo[], observacao?: string) => {
    if (!selectedEvidenceItem) return;

    if (selectedEvidenceItem.isAvulsa) {
      const avId = selectedEvidenceItem.item.id;
      const updatedAvulsas = pkg.exigencias_avulsas.map(av => {
        if (av.id !== avId) return av;
        return {
          ...av,
          anexos,
          observacao: observacao !== undefined ? observacao : av.observacao,
        };
      });
      updateAndSavePkg({ ...pkg, exigencias_avulsas: updatedAvulsas });
    } else {
      const itemId = selectedEvidenceItem.item.id;
      const updatedCategorias = pkg.categorias.map(cat => ({
        ...cat,
        itens: cat.itens.map(item => {
          if (item.id !== itemId) return item;
          return {
            ...item,
            anexos,
            resposta: item.resposta ? { ...item.resposta, observacao: observacao || item.resposta.observacao } : null,
          };
        }),
      }));
      updateAndSavePkg({ ...pkg, categorias: updatedCategorias });
    }
  };

  // Add new Exigência Avulsa
  const handleAddAvulsa = () => {
    if (!newAvulsaDesc) return;

    const vencimento = newAvulsaAS 
      ? '' 
      : calculateVencimento(pkg.vistoria.data_vistoria, pkg.vistoria.prazo_exigencias_dias);

    const newAvulsa: ExigenciaAvulsa = {
      id: 'av_' + generateUUID().slice(0, 8),
      bloco_vistoria: newAvulsaBloco,
      descricao: newAvulsaDesc,
      item_normam: newAvulsaNormam || 'NORMAM-202/DPC',
      observacao: newAvulsaObs,
      status_item: 'inserida',
      vencimento,
      antes_de_suspender: newAvulsaAS,
      anexos: [],
    };

    updateAndSavePkg({
      ...pkg,
      exigencias_avulsas: [...pkg.exigencias_avulsas, newAvulsa],
    });

    // Reset form
    setNewAvulsaDesc('');
    setNewAvulsaObs('');
    setNewAvulsaAS(false);
    setShowAddAvulsa(false);
  };

  // Remove Exigência Avulsa
  const handleRemoveAvulsa = (avId: string) => {
    const updated = pkg.exigencias_avulsas.filter(a => a.id !== avId);
    updateAndSavePkg({ ...pkg, exigencias_avulsas: updated });
  };

  // Toggle A/S for an Avulsa item
  const handleToggleASAvulsa = (avId: string) => {
    const updatedAvulsas = pkg.exigencias_avulsas.map(av => {
      if (av.id !== avId) return av;
      const newAS = !av.antes_de_suspender;
      const newVencimento = newAS 
        ? '' 
        : calculateVencimento(pkg.vistoria.data_vistoria, pkg.vistoria.prazo_exigencias_dias);

      return {
        ...av,
        antes_de_suspender: newAS,
        vencimento: newVencimento,
      };
    });

    updateAndSavePkg({ ...pkg, exigencias_avulsas: updatedAvulsas });
  };

  // Calculate totals for progress
  let totalSelected = 0;
  let totalItemsInCatalog = 0;
  let countConforme = 0;
  let countNaoConforme = 0;
  let countNaoAplica = 0;

  pkg.categorias.forEach(cat => {
    cat.itens.forEach(item => {
      totalItemsInCatalog++;
      if (item.resposta) {
        totalSelected++;
        if (item.resposta.status === 'CONFORME') countConforme++;
        if (item.resposta.status === 'NAO_CONFORME') countNaoConforme++;
        if (item.resposta.status === 'NAO_SE_APLICA') countNaoAplica++;
      }
    });
  });

  const percentComplete = totalItemsInCatalog > 0 
    ? Math.round((totalSelected / totalItemsInCatalog) * 100) 
    : 0;

  const currentVencimentoGeralFormatted = formatDateBr(
    calculateVencimento(pkg.vistoria.data_vistoria, pkg.vistoria.prazo_exigencias_dias)
  );  return (
    <div className="space-y-4 text-xs text-slate-900">
      
      {/* 2.4 DEADLINE RULE BANNER - 60 vs 90 DIAS (CRÍTICO) */}
      <div className="bg-[#1a365d] text-white rounded-xl p-4 shadow-sm border border-slate-800 space-y-3">
        <div className="flex items-center justify-between flex-wrap gap-2">
          <div className="flex items-center space-x-2">
            <Clock className="w-5 h-5 text-amber-400 shrink-0" />
            <h2 className="font-bold text-white text-sm sm:text-base">Prazo Geral para Correção das Exigências (ERP)</h2>
          </div>
          <span className="text-xs font-mono text-amber-300 font-bold bg-amber-500/20 px-2.5 py-1 rounded-full border border-amber-500/30">
            Vencimento Padrão: {currentVencimentoGeralFormatted}
          </span>
        </div>

        <p className="text-xs text-slate-200 leading-relaxed">
          Selecione o prazo de regularização da vistoria (60 ou 90 dias). O vencimento individual será calculado automaticamente a partir da data de realização.
        </p>

        {/* Prazo Selector Radio Buttons */}
        <div className="grid grid-cols-2 gap-3 pt-1">
          <button
            type="button"
            onClick={() => handleDeadlineOrDateChange(60, pkg.vistoria.data_vistoria)}
            className={`py-2.5 px-3 rounded-lg border text-xs font-bold transition flex items-center justify-center space-x-2 ${
              pkg.vistoria.prazo_exigencias_dias === 60
                ? 'bg-amber-500 text-[#1a365d] border-amber-400 shadow-md ring-2 ring-amber-400'
                : 'bg-white/10 text-white border-white/20 hover:bg-white/20'
            }`}
          >
            <span>60 DIAS</span>
          </button>

          <button
            type="button"
            onClick={() => handleDeadlineOrDateChange(90, pkg.vistoria.data_vistoria)}
            className={`py-2.5 px-3 rounded-lg border text-xs font-bold transition flex items-center justify-center space-x-2 ${
              pkg.vistoria.prazo_exigencias_dias === 90
                ? 'bg-amber-500 text-[#1a365d] border-amber-400 shadow-md ring-2 ring-amber-400'
                : 'bg-white/10 text-white border-white/20 hover:bg-white/20'
            }`}
          >
            <span>90 DIAS (Padrão DPC)</span>
          </button>
        </div>
      </div>

      {/* 2.5 INSPECTION DATA PANEL */}
      <div className="bg-white border border-slate-200 rounded-xl p-4 shadow-sm space-y-3 text-xs">
        <h3 className="font-bold text-[#1a365d] text-sm flex items-center justify-between">
          <span>Dados do Acompanhamento de Campo</span>
          <span className="text-xs font-bold text-amber-600 font-mono">{pkg.agendamento.embarcacao_nome} ({pkg.agendamento.embarcacao_registro})</span>
        </h3>

        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label className="block text-slate-500 font-bold mb-1">Data da Vistoria</label>
            <input
              type="date"
              value={pkg.vistoria.data_vistoria}
              onChange={(e) => handleDeadlineOrDateChange(pkg.vistoria.prazo_exigencias_dias, e.target.value)}
              className="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-slate-800 font-mono text-xs focus:ring-2 focus:ring-[#1a365d] outline-none"
            />
          </div>

          <div>
            <label className="block text-slate-500 font-bold mb-1">Acompanhado por (Comandante / Resp.)</label>
            <input
              type="text"
              value={pkg.vistoria.operador_nome}
              onChange={(e) => updateAndSavePkg({
                ...pkg,
                vistoria: { ...pkg.vistoria, operador_nome: e.target.value }
              })}
              placeholder="Nome do acompanhante a bordo"
              className="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-slate-800 text-xs focus:ring-2 focus:ring-[#1a365d] outline-none"
            />
          </div>

          <div className="sm:col-span-2">
            <div className="flex items-center justify-between mb-1">
              <label className="text-slate-500 font-bold">Observações Técnicas Gerais</label>
              <span className="text-[10px] text-slate-400 font-mono">
                {pkg.vistoria.observacoes_tecnicas.length} / 10.000 chars
              </span>
            </div>
            <textarea
              rows={2}
              maxLength={10000}
              value={pkg.vistoria.observacoes_tecnicas}
              onChange={(e) => updateAndSavePkg({
                ...pkg,
                vistoria: { ...pkg.vistoria, observacoes_tecnicas: e.target.value }
              })}
              placeholder="Descreva observações sobre condições de maré, estaleiro, acessibilidade ou ressalvas técnicas..."
              className="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-slate-800 text-xs focus:ring-2 focus:ring-[#1a365d] outline-none resize-none"
            />
          </div>
        </div>

        {/* Responsible Person Info Banner */}
        <div className="p-2.5 bg-slate-50 rounded-lg border border-slate-200 flex items-center justify-between text-[11px] text-slate-700">
          <div className="flex items-center space-x-2">
            <User className="w-3.5 h-3.5 text-amber-600" />
            <span>Fechamento da Proposta: <strong className="text-slate-900">{pkg.agendamento.contato_nome}</strong></span>
          </div>
          <a href={`tel:${pkg.agendamento.contato_telefone}`} className="text-[#1a365d] font-mono font-bold underline flex items-center space-x-1">
            <Phone className="w-3 h-3" />
            <span>{pkg.agendamento.contato_telefone}</span>
          </a>
        </div>
      </div>

      {/* Summary Stats Grid Cards */}
      <div className="bg-white border border-slate-200 rounded-xl p-4 shadow-sm space-y-3">
        <h3 className="text-xs font-bold text-slate-400 uppercase tracking-widest">Resumo do Checklist</h3>
        
        <div className="grid grid-cols-3 gap-3">
          <div className="p-3 bg-emerald-50 rounded-lg border border-emerald-100 flex items-center justify-between">
            <div>
              <span className="text-[10px] font-bold text-emerald-800 uppercase block">Conforme</span>
              <span className="text-xl font-black text-emerald-600">{countConforme}</span>
            </div>
            <CheckCircle2 className="w-5 h-5 text-emerald-500" />
          </div>

          <div className="p-3 bg-rose-50 rounded-lg border border-rose-100 flex items-center justify-between">
            <div>
              <span className="text-[10px] font-bold text-rose-800 uppercase block">Não Conf.</span>
              <span className="text-xl font-black text-rose-600">{countNaoConforme}</span>
            </div>
            <XCircle className="w-5 h-5 text-rose-500" />
          </div>

          <div className="p-3 bg-slate-100 rounded-lg border border-slate-200 flex items-center justify-between">
            <div>
              <span className="text-[10px] font-bold text-slate-600 uppercase block">Não Aplica</span>
              <span className="text-xl font-black text-slate-500">{countNaoAplica}</span>
            </div>
            <MinusCircle className="w-5 h-5 text-slate-400" />
          </div>
        </div>

        <div className="pt-1">
          <div className="flex justify-between items-center text-[11px] font-bold text-slate-500 mb-1">
            <span>Avaliados: {totalSelected} de {totalItemsInCatalog}</span>
            <span className="text-[#1a365d]">PROGRESSO: {percentComplete}%</span>
          </div>
          <div className="w-full bg-slate-200 h-2 rounded-full overflow-hidden">
            <div className="bg-[#1a365d] h-full transition-all duration-300" style={{ width: `${percentComplete}%` }}></div>
          </div>
        </div>
      </div>

      {/* Search & Add Avulsa Bar */}
      <div className="flex items-center justify-between gap-2">
        <div className="relative flex-1">
          <Search className="absolute left-3.5 top-3 w-4 h-4 text-slate-400" />
          <input
            type="text"
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            placeholder="Buscar item NORMAM ou descrição..."
            className="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl text-slate-800 text-xs focus:ring-2 focus:ring-[#1a365d] outline-none shadow-sm"
          />
        </div>

        {!isCumprimentoMode && (
          <button
            type="button"
            onClick={() => setShowAddAvulsa(!showAddAvulsa)}
            className="py-2.5 px-4 bg-amber-500 hover:bg-amber-600 text-[#1a365d] font-black text-xs rounded-xl shadow-sm flex items-center space-x-1 shrink-0 active:scale-95 transition"
          >
            <Plus className="w-4 h-4" />
            <span>+ EXIGÊNCIA AVULSA</span>
          </button>
        )}
      </div>

      {/* 2.3 CHECKLIST CATEGORIES ACCORDION */}
      <div className="space-y-3">
        {pkg.categorias.map((cat, idx) => {
          const isExpanded = searchQuery ? true : !!expandedCategories[cat.id];
          
          // Filter items by search query
          const filteredItens = cat.itens.filter(i => {
            if (!searchQuery) return true;
            const q = searchQuery.toLowerCase();
            return (
              i.descricao.toLowerCase().includes(q) ||
              i.item_normam.toLowerCase().includes(q)
            );
          });

          if (searchQuery && filteredItens.length === 0) return null;

          const catAnsweredCount = cat.itens.filter(i => i.resposta !== null).length;

          return (
            <div
              key={cat.id}
              className="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm transition"
            >
              
              {/* Category Header */}
              <button
                type="button"
                onClick={() => setExpandedCategories(prev => ({ ...prev, [cat.id]: !prev[cat.id] }))}
                className="w-full p-3.5 bg-slate-50 hover:bg-slate-100 flex items-center justify-between text-left transition border-b border-slate-200"
              >
                <div className="flex items-center space-x-2">
                  <div className="w-6 h-6 bg-[#1a365d] text-white rounded flex items-center justify-center text-[10px] font-bold">
                    0{idx + 1}
                  </div>
                  <h4 className="font-bold text-[#1a365d] text-xs sm:text-sm">{cat.nome}</h4>
                  <span className="px-2 py-0.5 bg-white text-slate-500 font-mono text-[10px] rounded-full border border-slate-200 font-bold">
                    {catAnsweredCount}/{cat.itens.length}
                  </span>
                </div>
                {isExpanded ? <ChevronUp className="w-5 h-5 text-slate-400" /> : <ChevronDown className="w-5 h-5 text-slate-400" />}
              </button>

              {/* Category Items List */}
              {isExpanded && (
                <div className="p-3 space-y-3 bg-white">
                  {filteredItens.map((item) => {
                    const status = item.resposta?.status;
                    const isAS = item.resposta?.sem_prazo || false;
                    const itemAnexos = item.anexos || [];
                    const isNc = status === 'NAO_CONFORME';

                    return (
                      <div
                        key={item.id}
                        className={`p-3.5 rounded-xl border transition-all ${
                          isNc 
                            ? 'border-2 border-rose-500 bg-rose-50/30' 
                            : 'border-slate-200 hover:border-slate-300 bg-white'
                        }`}
                      >
                        
                        {/* Description & Status Button Group */}
                        <div className="flex flex-col sm:flex-row sm:items-start justify-between gap-3 mb-2">
                          <div className="flex-1">
                            <p className="text-xs font-bold text-slate-800 leading-snug">
                              {item.descricao}
                            </p>
                            <p className="text-[10px] text-slate-400 font-bold uppercase mt-0.5 font-mono">
                              {item.item_normam}
                            </p>
                          </div>

                          {/* Response Tri-state Buttons (C, NC, NA) */}
                          <div className="flex items-center gap-1 shrink-0">
                            <button
                              type="button"
                              onClick={() => handleItemStatusChange(cat.id, item.id, 'CONFORME')}
                              className={`px-3 py-1.5 text-[11px] font-black rounded-l-md transition ${
                                status === 'CONFORME'
                                  ? 'bg-emerald-500 text-white shadow-sm'
                                  : 'bg-slate-100 text-slate-500 hover:bg-slate-200'
                              }`}
                            >
                              C
                            </button>

                            <button
                              type="button"
                              onClick={() => handleItemStatusChange(cat.id, item.id, 'NAO_CONFORME')}
                              className={`px-3 py-1.5 text-[11px] font-black transition ${
                                status === 'NAO_CONFORME'
                                  ? 'bg-rose-600 text-white shadow-sm'
                                  : 'bg-slate-100 text-slate-500 hover:bg-slate-200'
                              }`}
                            >
                              NC
                            </button>

                            <button
                              type="button"
                              onClick={() => handleItemStatusChange(cat.id, item.id, 'NAO_SE_APLICA')}
                              className={`px-3 py-1.5 text-[11px] font-black rounded-r-md transition ${
                                status === 'NAO_SE_APLICA'
                                  ? 'bg-slate-600 text-white shadow-sm'
                                  : 'bg-slate-100 text-slate-500 hover:bg-slate-200'
                              }`}
                            >
                              NA
                            </button>
                          </div>
                        </div>

                        {/* Options for Non-conforming item */}
                        {isNc && (
                          <div className="mt-3 pt-3 border-t border-rose-200 space-y-2">
                            <div className="flex items-center justify-between flex-wrap gap-2">
                              
                              {/* Photo Evidence button */}
                              <button
                                type="button"
                                onClick={() => setSelectedEvidenceItem({ item, isAvulsa: false })}
                                className={`py-1.5 px-3 rounded-lg text-xs font-bold flex items-center space-x-1.5 border transition ${
                                  itemAnexos.length > 0
                                    ? 'bg-white text-[#1a365d] border-slate-300 hover:bg-slate-50'
                                    : 'bg-rose-100 text-rose-800 border-rose-300'
                                }`}
                              >
                                <Camera className="w-4 h-4 text-rose-600" />
                                <span>Fotos Anexadas: <strong>{itemAnexos.length}</strong></span>
                              </button>

                              {/* A/S Toggle */}
                              <div className="flex items-center space-x-2">
                                <label className="flex items-center space-x-1.5 cursor-pointer">
                                  <input
                                    type="checkbox"
                                    checked={isAS}
                                    onChange={() => handleToggleASTableItem(cat.id, item.id)}
                                    className="w-4 h-4 rounded text-amber-600 focus:ring-amber-500 bg-white border-slate-300"
                                  />
                                  <span className="text-xs font-black text-rose-700 uppercase">A/S (Antes de Suspender)</span>
                                </label>
                              </div>

                            </div>

                            {isAS && (
                              <div className="bg-amber-100/80 p-2 rounded border border-amber-300 flex items-center gap-2">
                                <AlertTriangle className="w-4 h-4 text-amber-700 shrink-0" />
                                <p className="text-[10px] text-amber-900 font-bold uppercase italic">
                                  Antes de Suspender: Exigência com Bloqueio de Certificado Ativo
                                </p>
                              </div>
                            )}

                          </div>
                        )}

                        {/* Attached photos indicator for Conforme items */}
                        {status === 'CONFORME' && (
                          <div className="mt-2 flex items-center justify-end">
                            <button
                              type="button"
                              onClick={() => setSelectedEvidenceItem({ item, isAvulsa: false })}
                              className="text-[11px] text-slate-500 hover:text-slate-800 font-semibold flex items-center space-x-1"
                            >
                              <Camera className="w-3.5 h-3.5" />
                              <span>{itemAnexos.length > 0 ? `Fotos (${itemAnexos.length})` : 'Adicionar foto'}</span>
                            </button>
                          </div>
                        )}

                      </div>
                    );
                  })}
                </div>
              )}

            </div>
          );
        })}
      </div>

      {/* 2.5 EXIGÊNCIAS AVULSAS SECTION */}
      <div className="bg-white border border-slate-200 rounded-xl p-4 shadow-sm space-y-3 text-xs">
        <div className="flex items-center justify-between">
          <h3 className="font-bold text-[#1a365d] text-sm flex items-center space-x-2">
            <FileText className="w-4 h-4 text-amber-600" />
            <span>Exigências Avulsas Fora do Catálogo ({pkg.exigencias_avulsas.length})</span>
          </h3>

          {!isCumprimentoMode && (
            <button
              type="button"
              onClick={() => setShowAddAvulsa(!showAddAvulsa)}
              className="py-1.5 px-3 bg-[#1a365d] hover:bg-[#122846] text-white rounded-lg text-xs font-bold flex items-center space-x-1 transition shadow-sm"
            >
              <Plus className="w-4 h-4" />
              <span>Adicionar Avulsa</span>
            </button>
          )}
        </div>

        {/* Add Avulsa Form */}
        {showAddAvulsa && (
          <div className="p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-3 animate-fade-in">
            <h4 className="font-bold text-slate-800">Nova Exigência Avulsa</h4>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
              <div>
                <label className="block text-slate-600 font-bold mb-1">Bloco de Vistoria</label>
                <select
                  value={newAvulsaBloco}
                  onChange={(e) => setNewAvulsaBloco(e.target.value as InspectionBlock)}
                  className="w-full p-2 bg-white border border-slate-200 rounded-lg text-slate-800 text-xs outline-none"
                >
                  <option value="seco">Vistoria em Seco</option>
                  <option value="flutuando">Vistoria Flutuando</option>
                  <option value="borda_livre">Borda Livre</option>
                  <option value="arqueacao">Arqueação</option>
                </select>
              </div>

              <div>
                <label className="block text-slate-600 font-bold mb-1">Item da NORMAM (Referência)</label>
                <input
                  type="text"
                  value={newAvulsaNormam}
                  onChange={(e) => setNewAvulsaNormam(e.target.value)}
                  placeholder="ex.: NORMAM-202/DPC, Cap. 3"
                  className="w-full p-2 bg-white border border-slate-200 rounded-lg text-slate-800 text-xs outline-none"
                />
              </div>

              <div className="sm:col-span-2">
                <label className="block text-slate-600 font-bold mb-1">Descrição da Exigência *</label>
                <input
                  type="text"
                  value={newAvulsaDesc}
                  onChange={(e) => setNewAvulsaDesc(e.target.value)}
                  placeholder="Descreva detalhadamente a exigência identificada..."
                  className="w-full p-2 bg-white border border-slate-200 rounded-lg text-slate-800 text-xs outline-none"
                />
              </div>

              <div className="sm:col-span-2 flex items-center justify-between pt-1">
                <label className="flex items-center space-x-2 cursor-pointer">
                  <input
                    type="checkbox"
                    checked={newAvulsaAS}
                    onChange={(e) => setNewAvulsaAS(e.target.checked)}
                    className="w-4 h-4 rounded text-amber-600 bg-white border-slate-300"
                  />
                  <span className="font-bold text-rose-700">A/S — Antes de suspender (Bloqueio)</span>
                </label>
              </div>
            </div>

            <div className="flex justify-end space-x-2 pt-1">
              <button
                type="button"
                onClick={() => setShowAddAvulsa(false)}
                className="px-3 py-1.5 text-slate-500 font-bold hover:text-slate-800"
              >
                Cancelar
              </button>
              <button
                type="button"
                onClick={handleAddAvulsa}
                disabled={!newAvulsaDesc}
                className="px-4 py-1.5 bg-[#1a365d] hover:bg-[#122846] text-white font-bold rounded-lg disabled:opacity-50"
              >
                Salvar Exigência
              </button>
            </div>
          </div>
        )}

        {/* Existing Avulsa List */}
        {pkg.exigencias_avulsas.length === 0 ? (
          <p className="text-slate-400 text-[11px] italic">Nenhuma exigência avulsa cadastrada para esta vistoria.</p>
        ) : (
          <div className="space-y-2">
            {pkg.exigencias_avulsas.map((av) => (
              <div key={av.id} className="p-3 bg-slate-50 border border-slate-200 rounded-lg space-y-2">
                <div className="flex items-start justify-between gap-2">
                  <div>
                    <span className="text-[10px] font-mono text-amber-700 font-bold uppercase">{av.item_normam} ({av.bloco_vistoria})</span>
                    <p className="text-slate-800 font-bold">{av.descricao}</p>
                  </div>

                  {!isCumprimentoMode && (
                    <button
                      type="button"
                      onClick={() => handleRemoveAvulsa(av.id)}
                      className="p-1 text-rose-600 hover:text-rose-800 rounded"
                      title="Remover"
                    >
                      <Trash2 className="w-4 h-4" />
                    </button>
                  )}
                </div>

                <div className="flex items-center justify-between text-[11px] pt-1">
                  <label className="flex items-center space-x-1.5 cursor-pointer">
                    <input
                      type="checkbox"
                      checked={av.antes_de_suspender}
                      onChange={() => handleToggleASAvulsa(av.id)}
                      className="w-3.5 h-3.5 rounded text-amber-600 bg-white border-slate-300"
                    />
                    <span className="text-amber-800 font-bold">A/S — Antes de suspender</span>
                  </label>

                  <span className="font-mono text-slate-600 font-bold">
                    {av.antes_de_suspender ? 'Sem prazo (A/S)' : `Vence em: ${formatDateBr(av.vencimento)}`}
                  </span>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>

      {/* Button to Proceed to Summary */}
      <div className="pt-2">
        <button
          type="button"
          onClick={() => onGoToSummary(pkg)}
          className="w-full py-3.5 px-6 bg-[#1a365d] hover:bg-[#122846] text-white font-black text-sm rounded-xl shadow-md flex items-center justify-center space-x-2 transition active:scale-98 uppercase tracking-wider"
        >
          <span>Ir para Resumo e Finalização</span>
          <ArrowRight className="w-5 h-5 text-amber-400" />
        </button>
      </div>

      {/* Evidence Modal if triggered */}
      {selectedEvidenceItem && (
        <EvidenceModal
          item={selectedEvidenceItem.item}
          isAvulsa={selectedEvidenceItem.isAvulsa}
          onClose={() => setSelectedEvidenceItem(null)}
          onSaveAnexos={handleSaveItemEvidences}
        />
      )}

    </div>
  );
};
