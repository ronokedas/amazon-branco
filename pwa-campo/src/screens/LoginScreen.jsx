import { LogIn, ShieldCheck } from 'lucide-react'

export function LoginScreen({ error, onLogin }) {
  return <main className="campo-login">
    <section className="campo-login-card">
      <header className="campo-login-header">
        <img src="/campo/brand-horizontal.svg" alt="Amazon Certificadora" />
        <h1>Amazon Campo</h1>
        <p>Use sua sessão do ERP para trabalhar em campo.</p>
      </header>

      {error ? <div className="campo-login-error" role="alert">{error}</div> : null}

      <button className="campo-login-submit" type="button" onClick={onLogin}>
        <LogIn size={18} /> Entrar no ERP
      </button>

      <footer className="campo-login-footer">
        <p><ShieldCheck size={15} /> Sistema protegido com criptografia</p>
        <small>A primeira entrada precisa de internet. Os pacotes já baixados podem ser usados offline por até 30 dias.</small>
        <span>Amazon Campo · v1.0.0</span>
      </footer>
    </section>
  </main>
}
