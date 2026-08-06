import React, { useState } from 'react';
import { Anchor, ShieldCheck, Lock, User, AlertCircle, ArrowRight, Smartphone, RefreshCw } from 'lucide-react';
import { loginAPI } from '../api';
import { Session } from '../types';

interface LoginScreenProps {
  onLoginSuccess: (session: Session) => void;
}

export const LoginScreen: React.FC<LoginScreenProps> = ({ onLoginSuccess }) => {
  const [usuario, setUsuario] = useState<string>('carlos.vistoriador');
  const [senha, setSenha] = useState<string>('senha123');
  const [isLoading, setIsLoading] = useState<boolean>(false);
  const [errorMsg, setErrorMsg] = useState<string>('');

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!usuario || !senha) {
      setErrorMsg('Por favor, informe o usuário e a senha.');
      return;
    }

    setIsLoading(true);
    setErrorMsg('');

    try {
      const res = await loginAPI(usuario, senha);
      if (res.ok && res.dados) {
        onLoginSuccess(res.dados);
      } else {
        setErrorMsg(res.erro?.mensagem || 'Falha na autenticação. Verifique suas credenciais.');
      }
    } catch (err: any) {
      setErrorMsg('Erro ao conectar ao servidor de login: ' + err.message);
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-slate-100 text-slate-900 flex items-center justify-center p-4">
      <div className="w-full max-w-md bg-white border border-slate-200 rounded-2xl shadow-xl p-6 sm:p-8 space-y-6">
        
        {/* Brand Logo & Header */}
        <div className="text-center space-y-2">
          <div className="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-[#1a365d] text-amber-400 shadow-md border border-slate-300 mb-2">
            <Anchor className="w-9 h-9" />
          </div>
          <h1 className="text-xl sm:text-2xl font-black tracking-tight text-[#1a365d] uppercase">
            AMAZON NAVAL
          </h1>
          <p className="text-xs text-amber-700 font-bold uppercase tracking-wide">
            Aplicativo de Vistoria de Campo NORMAM
          </p>
        </div>

        {errorMsg && (
          <div className="p-3.5 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-xs flex items-start space-x-2.5 animate-fade-in">
            <AlertCircle className="w-4 h-4 text-rose-600 shrink-0 mt-0.5" />
            <span className="font-medium">{errorMsg}</span>
          </div>
        )}

        {/* Login Form */}
        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wider">
              Usuário do ERP
            </label>
            <div className="relative">
              <User className="absolute left-3.5 top-3.5 w-4 h-4 text-slate-400" />
              <input
                type="text"
                value={usuario}
                onChange={(e) => setUsuario(e.target.value)}
                placeholder="ex.: carlos.vistoriador"
                className="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 text-sm focus:ring-2 focus:ring-[#1a365d] focus:border-transparent outline-none transition font-medium"
              />
            </div>
          </div>

          <div>
            <label className="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wider">
              Senha
            </label>
            <div className="relative">
              <Lock className="absolute left-3.5 top-3.5 w-4 h-4 text-slate-400" />
              <input
                type="password"
                value={senha}
                onChange={(e) => setSenha(e.target.value)}
                placeholder="••••••••"
                className="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 text-sm focus:ring-2 focus:ring-[#1a365d] focus:border-transparent outline-none transition font-medium"
              />
            </div>
          </div>

          <button
            type="submit"
            disabled={isLoading}
            className="w-full py-3.5 px-4 bg-[#1a365d] hover:bg-[#122846] text-white font-bold text-sm rounded-xl shadow-md flex items-center justify-center space-x-2 transition active:scale-98 disabled:opacity-50 uppercase tracking-wider"
          >
            {isLoading ? (
              <>
                <RefreshCw className="w-4 h-4 animate-spin" />
                <span>Autenticando...</span>
              </>
            ) : (
              <>
                <span>Acessar Módulo de Campo</span>
                <ArrowRight className="w-4 h-4 text-amber-400" />
              </>
            )}
          </button>
        </form>

        {/* Offline & Security Footer Info */}
        <div className="pt-4 border-t border-slate-200 text-center space-y-2 text-xs text-slate-500 font-medium">
          <div className="flex items-center justify-center space-x-1.5 text-slate-700 font-bold">
            <ShieldCheck className="w-4 h-4 text-emerald-600" />
            <span>Sessão segura autenticada via CSRF</span>
          </div>
          <p>
            Funciona online com sincronização offline local no aparelho (PWA).
          </p>
        </div>

      </div>
    </div>
  );
};
