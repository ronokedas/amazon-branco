import React, { useEffect, useState } from 'react';
import { Wifi, WifiOff, RefreshCw, ChevronLeft, ShieldCheck, Anchor } from 'lucide-react';
import { subscribePendingCount, processSyncQueue } from '../sync';

interface AppShellProps {
  title: string;
  subtitle?: string;
  showBack?: boolean;
  onBack?: () => void;
  children: React.ReactNode;
}

export const AppShell: React.FC<AppShellProps> = ({
  title,
  subtitle,
  showBack,
  onBack,
  children,
}) => {
  const [isOnline, setIsOnline] = useState<boolean>(
    typeof navigator !== 'undefined' ? navigator.onLine : true
  );
  const [pendingCount, setPendingCount] = useState<number>(0);
  const [isSyncing, setIsSyncing] = useState<boolean>(false);
  const [syncNotice, setSyncNotice] = useState<string>('');

  useEffect(() => {
    const handleOnline = () => setIsOnline(true);
    const handleOffline = () => setIsOnline(false);

    window.addEventListener('online', handleOnline);
    window.addEventListener('offline', handleOffline);

    const unsubscribe = subscribePendingCount(count => setPendingCount(count));

    return () => {
      window.removeEventListener('online', handleOnline);
      window.removeEventListener('offline', handleOffline);
      unsubscribe();
    };
  }, []);

  const handleManualSync = async () => {
    if (!isOnline) {
      setSyncNotice('Sem conexão à internet. Sincronização pendente.');
      setTimeout(() => setSyncNotice(''), 3000);
      return;
    }

    setIsSyncing(true);
    setSyncNotice('Sincronizando com o ERP...');
    const result = await processSyncQueue();
    setIsSyncing(false);

    if (result.success) {
      setSyncNotice(result.syncedCount > 0 ? `${result.syncedCount} itens sincronizados com sucesso!` : 'Tudo atualizado!');
    } else {
      setSyncNotice('Erro ao sincronizar. Tente novamente.');
    }
    setTimeout(() => setSyncNotice(''), 4000);
  };

  return (
    <div className="min-h-screen bg-slate-100 text-slate-900 flex flex-col font-sans pb-24 selection:bg-[#1a365d] selection:text-white">
      
      {/* Sticky Header: Deep Navy Professional Polish Theme */}
      <header className="sticky top-0 z-40 bg-[#1a365d] text-white shadow-md px-4 py-3 flex items-center justify-between border-b border-slate-800/50">
        
        <div className="flex items-center space-x-3 truncate">
          {showBack ? (
            <button
              onClick={onBack}
              className="p-1.5 -ml-1 text-white hover:text-amber-300 rounded-lg bg-white/10 hover:bg-white/20 transition active:scale-95 shrink-0"
              title="Voltar"
            >
              <ChevronLeft className="w-5 h-5" />
            </button>
          ) : (
            <div className="w-9 h-9 bg-amber-500 text-[#1a365d] font-black text-sm rounded-lg flex items-center justify-center shadow-inner shrink-0">
              AN
            </div>
          )}

          <div className="truncate">
            <div className="flex items-center space-x-1.5 text-[10px] font-mono font-bold text-amber-400 uppercase tracking-widest">
              <Anchor className="w-3 h-3 text-amber-400 shrink-0" />
              <span>Amazon Naval • Vistoria NORMAM</span>
            </div>
            <h1 className="font-bold text-white text-sm sm:text-base truncate leading-tight">
              {title}
            </h1>
            {subtitle && (
              <p className="text-[11px] text-slate-300 opacity-90 truncate leading-none mt-0.5">{subtitle}</p>
            )}
          </div>
        </div>

        {/* Header Right Status Bar */}
        <div className="flex items-center space-x-2 shrink-0">
          
          {/* Pending Sync Badge */}
          {pendingCount > 0 && (
            <button
              onClick={handleManualSync}
              disabled={isSyncing}
              className="flex items-center space-x-1 px-2.5 py-1 bg-amber-500/20 text-amber-300 border border-amber-500/40 rounded-full text-xs font-medium hover:bg-amber-500/30 transition active:scale-95"
              title="Itens pendentes de envio. Clique para sincronizar"
            >
              <RefreshCw className={`w-3 h-3 ${isSyncing ? 'animate-spin' : ''}`} />
              <span className="font-mono font-bold text-[11px]">{pendingCount}</span>
            </button>
          )}

          {/* Online/Offline Status Indicator */}
          <div className={`flex items-center space-x-1.5 px-3 py-1 rounded-full text-[11px] font-medium border uppercase tracking-wider ${
            isOnline 
              ? 'bg-white/10 text-emerald-300 border-white/20' 
              : 'bg-rose-500/20 text-rose-200 border-rose-500/30'
          }`}>
            {isOnline ? (
              <>
                <span className="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span className="hidden xs:inline">Sincronizado</span>
              </>
            ) : (
              <>
                <WifiOff className="w-3 h-3 text-rose-300 shrink-0" />
                <span className="hidden xs:inline">Offline</span>
              </>
            )}
          </div>

        </div>

      </header>

      {/* Sync Toast Notification */}
      {syncNotice && (
        <div className="bg-blue-600 text-white text-xs px-4 py-2 text-center font-medium shadow-md transition animate-fade-in flex items-center justify-center space-x-2">
          <RefreshCw className="w-3.5 h-3.5 animate-spin" />
          <span>{syncNotice}</span>
        </div>
      )}

      {/* Main Screen Content */}
      <main className="flex-1 max-w-4xl w-full mx-auto p-3 sm:p-5">
        {children}
      </main>

    </div>
  );
};
