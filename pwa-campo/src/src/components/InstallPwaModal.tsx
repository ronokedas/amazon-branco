import React from 'react';
import { X, Share, PlusSquare, Smartphone, Download, CheckCircle2 } from 'lucide-react';

interface InstallPwaModalProps {
  deferredPrompt: any;
  onClose: () => void;
  onInstallClicked?: () => void;
}

export const InstallPwaModal: React.FC<InstallPwaModalProps> = ({
  deferredPrompt,
  onClose,
  onInstallClicked,
}) => {
  const isIos = typeof navigator !== 'undefined' && /iPhone|iPad|iPod/.test(navigator.userAgent);

  const handleNativeInstall = async () => {
    if (deferredPrompt) {
      deferredPrompt.prompt();
      const choiceResult = await deferredPrompt.userChoice;
      if (choiceResult.outcome === 'accepted') {
        console.log('Usuário instalou o PWA no dispositivo');
      }
      if (onInstallClicked) onInstallClicked();
      onClose();
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 animate-fade-in text-slate-900">
      <div className="relative w-full max-w-md bg-white border border-slate-200 rounded-2xl shadow-2xl p-5 space-y-4">
        
        {/* Header */}
        <div className="flex items-start justify-between border-b border-slate-200 pb-3">
          <div className="flex items-center space-x-3">
            <div className="p-2.5 bg-[#1a365d] text-amber-400 rounded-xl border border-slate-300">
              <Smartphone className="w-6 h-6" />
            </div>
            <div>
              <h3 className="font-extrabold text-[#1a365d] text-base">Instalar Aplicativo</h3>
              <p className="text-xs text-slate-500 font-medium">Instale na tela inicial para acesso offline direto</p>
            </div>
          </div>
          <button
            onClick={onClose}
            className="p-1 text-slate-400 hover:text-slate-700 rounded-lg hover:bg-slate-100 transition"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Android / Chrome Native Install Option */}
        {deferredPrompt ? (
          <div className="space-y-3 pt-2">
            <p className="text-xs text-slate-600 leading-relaxed font-medium">
              O seu navegador suporta instalação direta de aplicativo web progressivo. Clique abaixo para instalar no seu aparelho Android ou computador.
            </p>
            <button
              onClick={handleNativeInstall}
              className="w-full py-3 px-4 bg-[#1a365d] hover:bg-[#122846] text-white font-bold rounded-xl shadow-sm flex items-center justify-center space-x-2 transition active:scale-98 uppercase tracking-wider text-xs"
            >
              <Download className="w-5 h-5 text-amber-400" />
              <span>Instalar Aplicativo Agora</span>
            </button>
          </div>
        ) : isIos ? (
          /* iOS Safari Step by Step Guide */
          <div className="space-y-3 pt-2">
            <p className="text-xs text-slate-700 font-bold">
              Siga os passos abaixo para instalar no seu iPhone / iPad (Safari):
            </p>

            <div className="space-y-2 text-xs">
              <div className="flex items-center space-x-3 p-3 bg-slate-50 rounded-xl border border-slate-200">
                <div className="p-2 bg-blue-100 text-[#1a365d] rounded-lg font-bold">
                  <Share className="w-5 h-5" />
                </div>
                <div>
                  <span className="font-bold text-slate-900">1. Toque em Compartilhar</span>
                  <p className="text-[11px] text-slate-500 font-medium">Botão de compartilhar na barra inferior do Safari</p>
                </div>
              </div>

              <div className="flex items-center space-x-3 p-3 bg-slate-50 rounded-xl border border-slate-200">
                <div className="p-2 bg-emerald-100 text-emerald-800 rounded-lg font-bold">
                  <PlusSquare className="w-5 h-5" />
                </div>
                <div>
                  <span className="font-bold text-slate-900">2. Adicionar à Tela de Início</span>
                  <p className="text-[11px] text-slate-500 font-medium">Role as opções para baixo e selecione esta opção</p>
                </div>
              </div>

              <div className="flex items-center space-x-3 p-3 bg-slate-50 rounded-xl border border-slate-200">
                <div className="p-2 bg-amber-100 text-amber-900 rounded-lg font-bold">
                  <CheckCircle2 className="w-5 h-5" />
                </div>
                <div>
                  <span className="font-bold text-slate-900">3. Confirme em "Adicionar"</span>
                  <p className="text-[11px] text-slate-500 font-medium">O ícone do App de Vistoria será criado na sua tela</p>
                </div>
              </div>
            </div>
          </div>
        ) : (
          <div className="space-y-3 pt-2 text-xs text-slate-600 font-medium">
            <p>
              O aplicativo já está pronto para uso offline neste navegador. Caso queira fixá-lo na área de trabalho, utilize as opções de atalho do navegador.
            </p>
          </div>
        )}

        <div className="pt-2 border-t border-slate-200 text-right">
          <button
            onClick={onClose}
            className="px-4 py-2 bg-[#1a365d] hover:bg-[#122846] text-white text-xs font-bold rounded-xl transition shadow-sm"
          >
            Entendido
          </button>
        </div>

      </div>
    </div>
  );
};
