import { ArrowLeft, CheckCircle2, CloudOff, Menu, RefreshCw, WifiOff } from 'lucide-react'

export function AppShell({ title, children, online, pending, syncing, onSync, onBack, onMenu, footer = true, header = true }) {
  return (
    <main className="device-shell">
      {header ? <header className="topbar">
        {onBack ? <button className="icon-button" onClick={onBack} aria-label="Voltar"><ArrowLeft size={22} /></button> : <span aria-hidden="true" />}
        <strong>{title}</strong>
        {onMenu ? <button className="icon-button" onClick={onMenu} aria-label="Abrir ajustes"><Menu size={22} /></button> : <span aria-hidden="true" />}
      </header> : null}
      {footer ? (
        <aside className={`sync-status-bar ${pending ? 'has-pending' : ''} ${online ? 'is-online' : 'is-offline'}`} aria-live="polite">
          <span className="sync-status-icon">{!online ? <CloudOff size={17} /> : syncing ? <RefreshCw size={17} className="spin" /> : <CheckCircle2 size={17} />}</span>
          <strong>{syncing ? 'Sincronizando…' : pending ? `${pending === 1 ? '1 alteração' : `${pending} alterações`} aguardando envio` : online ? 'Rascunho sincronizado' : 'Salvo neste aparelho'}</strong>
          {pending && online ? <button onClick={onSync} disabled={syncing}><RefreshCw size={15} /> Enviar agora</button> : !online ? <span><WifiOff size={14} /> Offline</span> : null}
        </aside>
      ) : null}
      <div className="screen-content">{children}</div>
    </main>
  )
}
