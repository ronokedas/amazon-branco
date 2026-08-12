import { Eye, EyeOff, LoaderCircle, LockKeyhole, LogIn, Mail, ShieldCheck } from 'lucide-react'
import { useState } from 'react'

export function LoginScreen({ loading, error, onLogin }) {
  const [email, setEmail] = useState('')
  const [senha, setSenha] = useState('')
  const [mostrar, setMostrar] = useState(false)
  const submit = event => { event.preventDefault(); onLogin({ email, senha }) }

  return <main className="campo-login">
    <section className="campo-login-card">
      <header className="campo-login-header">
        <img src="/campo/brand-horizontal.svg" alt="Amazon Certificadora" />
        <h1>Amazon Campo</h1>
        <p>Acesso exclusivo para vistoriadores</p>
      </header>

      {error ? <div className="campo-login-error" role="alert">{error}</div> : null}

      <form onSubmit={submit}>
        <label htmlFor="campo-email"><Mail size={16} /> E-mail</label>
        <div className="campo-login-input">
          <input id="campo-email" type="email" autoComplete="username" required value={email} onChange={event => setEmail(event.target.value)} placeholder="seu@email.com" />
        </div>

        <label htmlFor="campo-senha"><LockKeyhole size={16} /> Senha</label>
        <div className="campo-login-input campo-login-password">
          <input id="campo-senha" type={mostrar ? 'text' : 'password'} autoComplete="current-password" required value={senha} onChange={event => setSenha(event.target.value)} placeholder="Digite sua senha" />
          <button type="button" onClick={() => setMostrar(value => !value)} aria-label={mostrar ? 'Ocultar senha' : 'Mostrar senha'}>
            {mostrar ? <EyeOff /> : <Eye />}
          </button>
        </div>

        <button className="campo-login-submit" type="submit" disabled={loading}>
          {loading ? <><LoaderCircle className="spin" /> Entrando…</> : <><LogIn size={18} /> Entrar</>}
        </button>
      </form>

      <footer className="campo-login-footer">
        <p><ShieldCheck size={15} /> Sistema protegido com criptografia</p>
        <small>A primeira entrada precisa de internet. Depois, os pacotes baixados funcionam offline.</small>
        <span>Amazon Campo · v1.0.0</span>
      </footer>
    </section>
  </main>
}
