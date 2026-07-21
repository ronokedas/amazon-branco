import { ArrowLeft, CheckCircle2, CloudOff, Menu } from 'lucide-react'

export function AppShell({ title, children, online, onBack, onMenu, footer = true, header = true }) {
  return (
    <main className="device-shell">
      {header ? <header className="topbar">
        {onBack ? <button className="icon-button" onClick={onBack} aria-label="Voltar"><ArrowLeft size={22} /></button> : <span aria-hidden="true" />}
        <strong>{title}</strong>
        {onMenu ? <button className="icon-button" onClick={onMenu} aria-label="Abrir ajustes"><Menu size={22} /></button> : <span aria-hidden="true" />}
      </header> : null}
      {footer ? (
        <aside className={`sync-status-bar ${online ? 'is-online' : 'is-offline'}`} aria-live="polite">
          <span className="sync-status-icon">{online ? <CheckCircle2 size={17} /> : <CloudOff size={17} />}</span>
          <strong>{online ? 'Salvo automaticamente neste aparelho' : 'Modo offline · salvo neste aparelho'}</strong>
        </aside>
      ) : null}
      <div className="screen-content">{children}</div>
    </main>
  )
}
