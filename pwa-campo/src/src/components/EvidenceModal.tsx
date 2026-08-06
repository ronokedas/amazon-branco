import React, { useRef, useState } from 'react';
import { Camera, Image as ImageIcon, Trash2, X, Check, AlertTriangle } from 'lucide-react';
import { Anexo, CatalogoItem, ExigenciaAvulsa } from '../types';
import { compressAndProcessImage } from '../media';
import { generateUUID } from '../utils';

interface EvidenceModalProps {
  item: CatalogoItem | ExigenciaAvulsa;
  isAvulsa?: boolean;
  onClose: () => void;
  onSaveAnexos: (anexos: Anexo[], observacao?: string) => void;
}

export const EvidenceModal: React.FC<EvidenceModalProps> = ({
  item,
  isAvulsa = false,
  onClose,
  onSaveAnexos,
}) => {
  const fileInputCameraRef = useRef<HTMLInputElement>(null);
  const fileInputGalleryRef = useRef<HTMLInputElement>(null);

  const initialAnexos = isAvulsa 
    ? ((item as ExigenciaAvulsa).anexos || [])
    : ((item as CatalogoItem).anexos || []);

  const initialObs = isAvulsa
    ? (item as ExigenciaAvulsa).observacao || ''
    : (item as CatalogoItem).resposta?.observacao || '';

  const [anexos, setAnexos] = useState<Anexo[]>(initialAnexos);
  const [observacao, setObservacao] = useState<string>(initialObs);
  const [isProcessing, setIsProcessing] = useState<boolean>(false);
  const [errorMsg, setErrorMsg] = useState<string>('');

  const isNaoConforme = isAvulsa 
    ? (item as ExigenciaAvulsa).status_item !== 'cumprida'
    : (item as CatalogoItem).resposta?.status === 'NAO_CONFORME';

  const handleFileChange = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const files = e.target.files;
    if (!files || files.length === 0) return;

    setIsProcessing(true);
    setErrorMsg('');

    try {
      const file = files[0];
      const processed = await compressAndProcessImage(file, 1600, 1600, 0.82);

      const newAnexo: Anexo = {
        id: 'anx_' + generateUUID().slice(0, 8),
        url_arquivo: processed.dataUrl,
        local: processed.dataUrl,
        status_upload: 'pendente',
        capturado_em: new Date().toISOString(),
        nome_original: file.name || 'evidencia_foto.jpg',
        size_bytes: processed.sizeBytes,
        sha256: processed.sha256,
        blobData: processed.blob,
      };

      setAnexos(prev => [...prev, newAnexo]);
    } catch (err: any) {
      console.error('Erro ao processar imagem:', err);
      setErrorMsg('Falha ao capturar ou comprimir a imagem: ' + (err.message || 'Erro desconhecido'));
    } finally {
      setIsProcessing(false);
      // Reset input
      if (e.target) e.target.value = '';
    }
  };

  const handleRemoveAnexo = (id: string) => {
    setAnexos(prev => prev.filter(a => a.id !== id));
  };

  const handleSave = () => {
    if (isNaoConforme && anexos.length === 0) {
      setErrorMsg('Para itens NÃO CONFORME, é obrigatório anexar pelo menos 1 evidência fotográfica.');
      return;
    }
    onSaveAnexos(anexos, observacao);
    onClose();
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 overflow-y-auto animate-fade-in text-slate-900">
      <div className="relative w-full max-w-lg bg-white border border-slate-200 rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
        
        {/* Header */}
        <div className="flex items-center justify-between p-4 bg-[#1a365d] text-white border-b border-slate-200">
          <div className="flex items-center space-x-2">
            <Camera className="w-5 h-5 text-amber-400" />
            <h3 className="font-extrabold text-white text-base">Evidências Fotográficas</h3>
          </div>
          <button
            onClick={onClose}
            className="p-1.5 text-slate-300 hover:text-white rounded-lg hover:bg-white/10 transition"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Body */}
        <div className="p-4 space-y-4 overflow-y-auto flex-1 text-xs">
          
          {/* Item details */}
          <div className="p-3 bg-slate-50 rounded-xl border border-slate-200">
            <div className="flex items-center justify-between text-xs text-amber-700 font-mono font-bold mb-1">
              <span>{item.item_normam || 'NORMAM'}</span>
              {isNaoConforme && (
                <span className="px-2 py-0.5 rounded bg-rose-100 text-rose-800 font-sans font-bold">
                  Evidência Obrigatória
                </span>
              )}
            </div>
            <p className="text-slate-900 font-bold text-xs sm:text-sm">
              {isAvulsa ? (item as ExigenciaAvulsa).descricao : (item as CatalogoItem).descricao}
            </p>
          </div>

          {errorMsg && (
            <div className="p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-xs flex items-start space-x-2 font-medium">
              <AlertTriangle className="w-4 h-4 text-rose-600 shrink-0 mt-0.5" />
              <span>{errorMsg}</span>
            </div>
          )}

          {/* Action buttons: Camera vs Gallery */}
          <div className="grid grid-cols-2 gap-3">
            <button
              type="button"
              disabled={isProcessing}
              onClick={() => fileInputCameraRef.current?.click()}
              className="flex items-center justify-center space-x-2 py-3 px-4 bg-[#1a365d] hover:bg-[#122846] active:scale-98 text-white rounded-xl font-bold shadow-sm transition disabled:opacity-50 uppercase tracking-wider text-xs"
            >
              <Camera className="w-5 h-5 text-amber-400" />
              <span>Tirar Foto</span>
            </button>

            <button
              type="button"
              disabled={isProcessing}
              onClick={() => fileInputGalleryRef.current?.click()}
              className="flex items-center justify-center space-x-2 py-3 px-4 bg-white hover:bg-slate-50 active:scale-98 text-slate-800 border border-slate-200 rounded-xl font-bold transition disabled:opacity-50 shadow-sm uppercase tracking-wider text-xs"
            >
              <ImageIcon className="w-5 h-5 text-[#1a365d]" />
              <span>Galeria</span>
            </button>

            {/* Hidden Camera input with capture="environment" for native iOS & Android camera */}
            <input
              ref={fileInputCameraRef}
              type="file"
              accept="image/*"
              capture="environment"
              onChange={handleFileChange}
              className="hidden"
            />

            {/* Hidden Gallery input */}
            <input
              ref={fileInputGalleryRef}
              type="file"
              accept="image/*"
              onChange={handleFileChange}
              className="hidden"
            />
          </div>

          {isProcessing && (
            <div className="p-3 text-center text-xs text-[#1a365d] font-bold animate-pulse bg-blue-50 rounded-xl border border-blue-200">
              Comprimindo e processando foto para salvamento...
            </div>
          )}

          {/* Photos Grid */}
          <div>
            <div className="flex items-center justify-between mb-2">
              <span className="text-xs font-bold text-slate-700 uppercase tracking-wider">
                Fotos Anexadas ({anexos.length})
              </span>
            </div>

            {anexos.length === 0 ? (
              <div className="p-6 text-center border-2 border-dashed border-slate-200 rounded-xl text-slate-500 text-xs font-medium">
                Nenhuma foto anexada a este item ainda.
              </div>
            ) : (
              <div className="grid grid-cols-2 gap-3">
                {anexos.map((anx, idx) => (
                  <div key={anx.id || idx} className="relative group rounded-xl overflow-hidden border border-slate-200 bg-slate-50 shadow-sm">
                    <img
                      src={anx.local || anx.url_arquivo}
                      alt={`Evidência ${idx + 1}`}
                      className="w-full h-32 object-cover"
                    />
                    <div className="p-1.5 bg-white text-[10px] text-slate-700 truncate flex items-center justify-between border-t border-slate-200 font-medium">
                      <span className="truncate">{anx.nome_original || `Foto #${idx + 1}`}</span>
                      <button
                        type="button"
                        onClick={() => handleRemoveAnexo(anx.id)}
                        className="p-1 text-rose-600 hover:text-rose-800 rounded transition"
                        title="Excluir foto"
                      >
                        <Trash2 className="w-3.5 h-3.5" />
                      </button>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>

          {/* Observation textarea */}
          <div>
            <label className="block text-xs font-bold text-slate-700 mb-1">
              Observação técnica / Justificativa (opcional)
            </label>
            <textarea
              rows={3}
              value={observacao}
              onChange={(e) => setObservacao(e.target.value)}
              placeholder="Descreva detalhes específicos da não conformidade ou evidência encontrada..."
              className="w-full p-2.5 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 text-xs focus:ring-2 focus:ring-[#1a365d] focus:border-transparent outline-none resize-none font-medium"
            />
          </div>

        </div>

        {/* Footer */}
        <div className="p-4 bg-slate-50 border-t border-slate-200 flex items-center justify-end space-x-3">
          <button
            type="button"
            onClick={onClose}
            className="px-4 py-2 text-slate-700 hover:text-slate-900 text-xs font-bold rounded-xl hover:bg-slate-200 transition"
          >
            Cancelar
          </button>
          <button
            type="button"
            onClick={handleSave}
            className="flex items-center space-x-1.5 px-5 py-2.5 bg-[#1a365d] hover:bg-[#122846] active:scale-98 text-white text-xs font-bold rounded-xl shadow-sm transition uppercase tracking-wider"
          >
            <Check className="w-4 h-4 text-amber-400" />
            <span>Salvar Evidências</span>
          </button>
        </div>

      </div>
    </div>
  );
};
