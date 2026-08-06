import React, { useEffect, useState } from 'react';
import { 
  User, 
  Settings, 
  Smartphone, 
  RefreshCw, 
  LogOut, 
  ShieldCheck, 
  Server, 
  AlertTriangle, 
  CheckCircle2,
  HelpCircle
} from 'lucide-react';
import { Session } from '../types';
import { getSetting, setSetting, countPendingSyncOps } from '../db';
import { logoutAPI } from '../api';
import { processSyncQueue } from '../sync';

interface SettingsScreenProps {
  session: Session | null;
  onOpenInstallModal: () => void;
  onLogout: () => void;
}

export const SettingsScreen: React.FC<SettingsScreenProps> = ({
  session,
  onOpenInstallModal,
  onLogout,
}) => {
  const [mockMode, setMockMode] = useState<boolean>(false);
  const [pendingCount, setPendingCount] = useState<number>(0);
  const [showLogoutConfirm, setShowLogoutConfirm] = useState<boolean>(false);
  const [isSyncing, setIsSyncing] = useState<boolean>(false);
  const [notice, setNotice] = useState<string>('');

  useEffect(() => {
    const allowMock = import.meta.env.DEV && import.meta.env.VITE_CAMPO_MOCK === 'true';
    getSetting<boolean>('mockMode', false).then(value => setMockMode(allowMock && value));
    countPendingSyncOps().then(setPendingCount);
  }, []);

  const handleToggleMock = async (newVal: boolean) => {
    setMockMode(newVal);
    await setSetting('mockMode', newVal);
    setNotice(newVal ? 'Modo de Simulação / Demo ativado.' : 'Modo ERP Real ativado. Conectando em /api/campo/v1/');
    setTimeout(() => setNotice(''), 3000);
  };

  const handleSyncNow = async () => {
    setIsSyncing(true);
    const res = await processSyncQueue();
    setIsSyncing(false);
    const newCount = await countPendingSyncOps();
    setPendingCount(newCount);
    setNotice(res.success ? 'Fila sincronizada com sucesso!' : 'Erro na sincronização.');
    setTimeout(() => setNotice(''), 3000);
  };

  const handleLogoutClick = () => {
    if (pendingCount > 0) {
      setShowLogoutConfirm(true);
    } else {
      executeLogout();
    }
  };

  const executeLogout = async () => {
    await logoutAPI();
    onLogout();
  };

  return (
    <div className="space-y-4 text-xs text-slate-900">
      
      {notice && (
        <div className="p-3 bg-blue-50 border border-blue-200 rounded-xl text-[#1a365d] text-center font-bold animate-fade-in shadow-sm">
          {notice}
        </div>
      )}

      {/* User Info Card */}
      <div className="p-4 bg-white border border-slate-200 rounded-xl shadow-sm space-y-3">
        <div className="flex items-center space-x-3">
          <div className="w-12 h-12 rounded-xl bg-[#1a365d] text-white border border-slate-300 flex items-center justify-center shrink-0 font-bold">
            <User className="w-6 h-6" />
          </div>
          <div>
            <h2 className="font-extrabold text-[#1a365d] text-base">
              {session?.usuario.nome || 'Carlos Eduardo Silva'}
            </h2>
            <p className="text-amber-700 font-bold">{session?.usuario.cargo || 'Vistoriador Naval Sênior'}</p>
            <p className="text-slate-500 font-mono text-[10px] font-semibold">Usuário: {session?.usuario.usuario || 'carlos.vistoriador'}</p>
          </div>
        </div>
      </div>

      {/* PWA App Installation Box */}
      <div className="p-4 bg-white border border-slate-200 rounded-xl shadow-sm space-y-3">
        <div className="flex items-center space-x-2">
          <Smartphone className="w-5 h-5 text-[#1a365d]" />
          <h3 className="font-bold text-[#1a365d] text-sm">Instalação do App (PWA)</h3>
        </div>
        <p className="text-slate-600 leading-relaxed text-xs">
          Instale este aplicativo na tela de início do seu dispositivo iPhone ou Android para funcionamento otimizado em campo e acesso direto offline.
        </p>

        <button
          onClick={onOpenInstallModal}
          className="w-full py-3 px-4 bg-[#1a365d] hover:bg-[#122846] text-white font-bold rounded-xl shadow-sm flex items-center justify-center space-x-2 transition uppercase tracking-wider text-xs"
        >
          <Smartphone className="w-4 h-4 text-amber-400" />
          <span>Instalar Aplicativo de Vistoria</span>
        </button>
      </div>

      {/* ERP Connection & Simulator Settings */}
      <div className="p-4 bg-white border border-slate-200 rounded-xl shadow-sm space-y-3">
        <div className="flex items-center space-x-2">
          <Server className="w-5 h-5 text-amber-600" />
          <h3 className="font-bold text-[#1a365d] text-sm">Conexão com ERP Amazon Naval</h3>
        </div>

        <div className="p-3 bg-slate-50 rounded-lg border border-slate-200 flex items-center justify-between">
          <div>
            <span className="font-bold text-slate-900 block">Modo de Simulação / Offline (desenvolvimento)</span>
            <span className="text-[10px] text-slate-500 font-medium">Permite testar todas as funções sem o ERP Docker local</span>
          </div>

          <label className="relative inline-flex items-center cursor-pointer shrink-0">
            <input
              type="checkbox"
              checked={mockMode}
              disabled={!(import.meta.env.DEV && import.meta.env.VITE_CAMPO_MOCK === 'true')}
              onChange={(e) => handleToggleMock(e.target.checked)}
              className="sr-only peer"
            />
            <div className="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#1a365d]"></div>
          </label>
        </div>

        {/* Sync Status Queue */}
        <div className="p-3 bg-slate-50 rounded-lg border border-slate-200 flex items-center justify-between">
          <div>
            <span className="font-bold text-slate-900 block">Fila de Operações Unidades</span>
            <span className="text-[10px] text-slate-500 font-medium">{pendingCount} item(ns) aguardando reenvio</span>
          </div>

          <button
            onClick={handleSyncNow}
            disabled={isSyncing || pendingCount === 0}
            className="py-1.5 px-3 bg-[#1a365d] hover:bg-[#122846] text-white rounded-lg text-xs font-bold flex items-center space-x-1 disabled:opacity-50"
          >
            <RefreshCw className={`w-3.5 h-3.5 ${isSyncing ? 'animate-spin' : ''}`} />
            <span>Sincronizar</span>
          </button>
        </div>
      </div>

      {/* Logout Option */}
      <div className="pt-2">
        <button
          onClick={handleLogoutClick}
          className="w-full py-3.5 px-4 bg-rose-50 hover:bg-rose-100 text-rose-800 border border-rose-200 font-bold rounded-xl transition flex items-center justify-center space-x-2 shadow-sm text-xs"
        >
          <LogOut className="w-4 h-4 text-rose-600" />
          <span>Sair da Conta (Logout)</span>
        </button>
      </div>

      {/* Warning Modal when logging out with unsent data */}
      {showLogoutConfirm && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm p-4 animate-fade-in">
          <div className="bg-slate-900 border border-slate-800 rounded-2xl p-5 max-w-sm w-full space-y-4">
            <div className="flex items-center space-x-3 text-rose-400">
              <AlertTriangle className="w-6 h-6 shrink-0" />
              <h3 className="font-bold text-slate-100 text-sm">Atenção: Dados Pendentes de Envio</h3>
            </div>

            <p className="text-slate-300 text-xs leading-relaxed">
              Existem <strong>{pendingCount} item(ns) na fila de sincronização</strong> deste aparelho. Ao sair agora, esses rascunhos podem não ser transmitidos ao ERP até um novo login neste mesmo dispositivo.
            </p>

            <div className="flex items-center justify-end space-x-2 pt-2">
              <button
                onClick={() => setShowLogoutConfirm(false)}
                className="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-medium rounded-xl"
              >
                Cancelar
              </button>
              <button
                onClick={executeLogout}
                className="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold rounded-xl"
              >
                Sair Mesmo Assim
              </button>
            </div>
          </div>
        </div>
      )}

    </div>
  );
};
