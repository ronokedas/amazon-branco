import { 
  Session, 
  AgendaItem, 
  InspectionPackage, 
  ReportItem, 
  SyncOperation 
} from './types';
import { 
  saveSessionLocal, 
  getSessionLocal, 
  clearSessionLocal, 
  saveAgendaLocal, 
  savePackageLocal, 
  getPackageLocal,
  getAgendaLocal,
  getReportsLocal,
  saveReportsLocal
} from './db';
import { MOCK_AGENDA, getMockPackage, MOCK_REPORTS } from './mockServerData';

let currentCsrfToken = '';

export async function setCsrfToken(token: string) {
  currentCsrfToken = token;
}

export async function getCsrfToken(): Promise<string> {
  if (!currentCsrfToken) {
    const session = await getSessionLocal();
    if (session?.csrf_token) {
      currentCsrfToken = session.csrf_token;
    }
  }
  return currentCsrfToken;
}

// Helper to check if mock mode is active
export async function isMockModeActive(): Promise<boolean> {
  return import.meta.env.DEV && import.meta.env.VITE_CAMPO_MOCK === 'true';
}

/**
 * Base HTTP fetch wrapper with CSRF, error handling, and mock fallback
 */
async function apiFetch<T>(endpoint: string, options: RequestInit = {}): Promise<{ ok: boolean; dados?: T; erro?: any }> {
  const token = await getCsrfToken();
  const headers = new Headers(options.headers || {});
  
  if (token && !headers.has('X-CSRF-Token')) {
    headers.set('X-CSRF-Token', token);
  }
  
  if (!(options.body instanceof FormData) && !headers.has('Content-Type')) {
    headers.set('Content-Type', 'application/json');
  }

  try {
    const res = await fetch(endpoint, {
      ...options,
      headers,
      credentials: 'same-origin',
    });

    // Handle 419 CSRF Token Expiration
    if (res.status === 419) {
      console.warn('CSRF token expirado (419). Tentando renovar sessão...');
      const renewRes = await fetch('/api/campo/v1/sessao', { credentials: 'same-origin' });
      if (renewRes.ok) {
        const renewJson = await renewRes.json();
        if (renewJson.ok && renewJson.dados?.csrf_token) {
          setCsrfToken(renewJson.dados.csrf_token);
          headers.set('X-CSRF-Token', renewJson.dados.csrf_token);
          // Retry original request
          const retryRes = await fetch(endpoint, { ...options, headers, credentials: 'same-origin' });
          return await retryRes.json();
        }
      }
    }

    const data = await res.json();
    return data;
  } catch (err: any) {
    console.warn(`Rede indisponível para ${endpoint}. Ativando modo offline/mock fallback:`, err.message);
    return { ok: false, erro: { mensagem: 'Não foi possível conectar ao sistema. Os dados locais continuam preservados.' } };
  }
}

/**
 * Mock Request Handler simulating the ERP PHP backend behavior perfectly
 */
async function handleMockRequest<T>(endpoint: string, options: RequestInit = {}): Promise<{ ok: boolean; dados?: T; erro?: any }> {
  await new Promise(resolve => setTimeout(resolve, 300)); // Simulate network latency

  if (endpoint.includes('/api/campo/v1/login')) {
    const body = options.body ? JSON.parse(options.body as string) : {};
    if (body.usuario && body.senha) {
      const mockSession: Session = {
        usuario: {
          id: 'usr_771',
          nome: 'Carlos Eduardo Silva',
          cargo: 'Vistoriador Naval Sênior',
          usuario: body.usuario || 'carlos.vistoriador',
        },
        csrf_token: 'csrf_mock_' + Math.random().toString(36).substring(2),
        expira_em: new Date(Date.now() + 86400000).toISOString(),
      };
      await saveSessionLocal(mockSession);
      setCsrfToken(mockSession.csrf_token);
      return { ok: true, dados: mockSession as any };
    } else {
      return { ok: false, erro: { mensagem: 'Usuário ou senha inválidos' } };
    }
  }

  if (endpoint.includes('/api/campo/v1/sessao')) {
    const session = await getSessionLocal();
    if (session) {
      return { ok: true, dados: session as any };
    }
    return { ok: false, erro: { mensagem: 'Sessão expirada' } };
  }

  if (endpoint.includes('/api/campo/v1/logout')) {
    await clearSessionLocal();
    return { ok: true };
  }

  if (endpoint.includes('/api/campo/v1/agenda')) {
    return { ok: true, dados: { vistorias: MOCK_AGENDA } as any };
  }

  if (endpoint.match(/\/api\/campo\/v1\/vistorias\/([^/]+)\/pacote/)) {
    const match = endpoint.match(/\/api\/campo\/v1\/vistorias\/([^/]+)\/pacote/);
    const agendamentoId = match ? match[1] : 'vist_001';
    
    // Check if package already exists in IndexedDB
    const existingPkg = await getPackageLocal(agendamentoId);
    if (existingPkg) {
      return { ok: true, dados: existingPkg as any };
    }

    const pkg = getMockPackage(agendamentoId);
    await savePackageLocal(pkg);
    return { ok: true, dados: pkg as any };
  }

  if (endpoint.match(/\/api\/campo\/v1\/vistorias\/([^/]+)\/sync/)) {
    const match = endpoint.match(/\/api\/campo\/v1\/vistorias\/([^/]+)\/sync/);
    const agendamentoId = match ? match[1] : 'vist_001';

    // Handle sync operations
    if (options.body instanceof FormData) {
      const operacaoId = options.body.get('operacao_id');
      const catalogoId = options.body.get('catalogo_id');
      
      return {
        ok: true,
        dados: {
          id: 'anx_' + Math.random().toString(36).substring(2, 8),
          url_arquivo: URL.createObjectURL(options.body.get('blob') as Blob || new Blob()),
          local: 'MinIO S3 Amazon Naval',
          status_upload: 'enviado',
          capturado_em: new Date().toISOString()
        } as any
      };
    } else if (options.body) {
      const payload = JSON.parse(options.body as string);
      if (payload.tipo === 'finalizacao' || payload.operacao_id) {
        return {
          ok: true,
          dados: {
            vistoria_id: agendamentoId,
            versao: (payload.versao || 1) + 1,
            status: 'AGUARDANDO_APROVACAO',
            relatorio_pdf_url: undefined
          } as any
        };
      }
    }

    return { ok: true, dados: { vistoria_id: agendamentoId, versao: 2 } as any };
  }

  if (endpoint.includes('/api/campo/v1/relatorios')) {
    return { ok: true, dados: { relatorios: MOCK_REPORTS } as any };
  }

  return { ok: true, dados: {} as any };
}

// Exported high-level API methods
export async function loginAPI(usuario: string, senha: string) {
  const res = await apiFetch<Session>('/api/campo/v1/login', {
    method: 'POST',
    body: JSON.stringify({ email: usuario, senha }),
  });
  if (res.ok && res.dados) {
    await saveSessionLocal(res.dados);
    setCsrfToken(res.dados.csrf_token);
  }
  return res;
}

export async function checkSessionAPI() {
  const res = await apiFetch<Session>('/api/campo/v1/sessao');
  if (res.ok && res.dados) {
    await saveSessionLocal(res.dados);
  }
  return res;
}

export async function logoutAPI() {
  await apiFetch('/api/campo/v1/logout', { method: 'POST' });
  await clearSessionLocal();
}

export async function getAgendaAPI(): Promise<{ ok: boolean; vistorias: AgendaItem[] }> {
  const res = await apiFetch<{ vistorias: AgendaItem[] }>('/api/campo/v1/agenda');
  if (res.ok && res.dados?.vistorias) {
    await saveAgendaLocal(res.dados.vistorias);
    return { ok: true, vistorias: res.dados.vistorias };
  }
  return { ok: false, vistorias: await getAgendaLocal() };
}

export async function getPackageAPI(agendamentoId: string): Promise<{ ok: boolean; package?: InspectionPackage; erro?: string }> {
  const res = await apiFetch<InspectionPackage>(`/api/campo/v1/vistorias/${agendamentoId}/pacote`);
  if (res.ok && res.dados) {
    await savePackageLocal(res.dados);
    return { ok: true, package: res.dados };
  }
  return { ok: false, erro: res.erro?.mensagem || 'Erro ao carregar pacote de vistoria' };
}

export async function syncOperationAPI(agendamentoId: string, op: SyncOperation) {
  if (op.tipo === 'anexo' || op.tipo === 'foto_embarcacao') {
    const formData = new FormData();
    formData.append('operacao_id', op.operacao_id);
    if (op.payload.catalogo_id) formData.append('catalogo_id', op.payload.catalogo_id);
    if (op.payload.nome) formData.append('nome', op.payload.nome);
    if (op.payload.capturado_em) formData.append('capturado_em', op.payload.capturado_em);
    if (op.payload.blob) formData.append('arquivo', op.payload.blob, op.payload.nome || 'evidencia');

    const rota = op.tipo === 'anexo' ? 'anexos' : 'foto-embarcacao';
    return await apiFetch(`/api/campo/v1/vistorias/${agendamentoId}/${rota}`, {
      method: 'POST',
      body: formData,
    });
  } else if (op.tipo === 'exclusao_anexo') {
    return await apiFetch(`/api/campo/v1/anexos/${op.payload.anexo_id}`, { method: 'DELETE' });
  } else if (op.tipo === 'finalizacao') {
    return await apiFetch(`/api/campo/v1/vistorias/${agendamentoId}/finalizar`, {
      method: 'POST',
      body: JSON.stringify(op.payload),
    });
  } else {
    return await apiFetch(`/api/campo/v1/vistorias/${agendamentoId}/rascunho`, {
      method: 'POST',
      body: JSON.stringify(op.payload),
    });
  }
}

export async function getReportsAPI(): Promise<{ ok: boolean; relatorios: ReportItem[] }> {
  const res = await apiFetch<{ relatorios: ReportItem[] }>('/api/campo/v1/relatorios');
  if (res.ok && res.dados?.relatorios) {
    await saveReportsLocal(res.dados.relatorios);
    return { ok: true, relatorios: res.dados.relatorios };
  }
  return { ok: false, relatorios: await getReportsLocal() };
}
