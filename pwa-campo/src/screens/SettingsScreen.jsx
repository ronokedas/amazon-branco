import { CheckCircle2, CloudOff, Download, LogOut, ShieldCheck, UserRound, Wifi } from 'lucide-react'
import { useState } from 'react'
import { AppShell } from '../components/AppShell'
import { BottomNav } from '../components/BottomNav'

export function SettingsScreen({ session, online, onInstall, onLogout, onNavigate }) {
  const [installHelp, setInstallHelp] = useState(false)
  const instalar = async () => { if (!await onInstall?.()) setInstallHelp(true) }
  return <AppShell title="Ajustes" online={online} footer={false}>
    <section className="tab-heading"><span className="tab-heading-icon"><UserRound /></span><span><h1>{session?.usuario?.nome || 'Usuário'}</h1><p>{(session?.usuario?.perfis || []).join(' · ') || 'Vistoriador'}</p></span></section>
    <section className="settings-groups">
      <article className="settings-card"><h2>Dados neste aparelho</h2><div className="setting-row"><span className={online ? 'setting-icon online' : 'setting-icon offline'}>{online ? <Wifi /> : <CloudOff />}</span><span><strong>{online ? 'Conectado à internet' : 'Trabalhando offline'}</strong><small>As alterações são salvas automaticamente e enviadas somente ao finalizar a vistoria.</small></span></div></article>
      <article className="settings-card"><h2>Aplicativo neste aparelho</h2><div className="setting-row"><span className="setting-icon"><Download /></span><span><strong>Instalar Amazon Campo</strong><small>Acesso rápido pela tela inicial, quando o navegador oferecer instalação.</small></span></div><button className="secondary-button" onClick={instalar}>Instalar neste aparelho</button>{installHelp ? <p className="install-help" role="status">Use o menu do navegador e escolha <strong>Instalar aplicativo</strong> ou <strong>Adicionar à tela inicial</strong>.</p> : null}</article>
      <article className="settings-card"><h2>Segurança</h2><div className="setting-row"><span className="setting-icon"><ShieldCheck /></span><span><strong>Sessão do ERP</strong><small>Os pacotes baixados permanecem disponíveis offline por até 30 dias neste aparelho.</small></span></div><button type="button" className="logout-link" onClick={onLogout}><LogOut size={18} /> Sair do ERP neste navegador</button></article>
      <div className="app-version"><CheckCircle2 size={15} /> Amazon Campo 1.0 · dados locais protegidos</div>
    </section>
    <BottomNav active="settings" onNavigate={onNavigate} />
  </AppShell>
}
