import { 
  getPendingSyncOps, 
  updateSyncOpStatus, 
  removeSyncOp, 
  countPendingSyncOps 
} from './db';
import { syncOperationAPI } from './api';
import { SyncOperation } from './types';

let isSyncing = false;
type SyncCallback = (count: number) => void;
const syncListeners: SyncCallback[] = [];

export function subscribePendingCount(cb: SyncCallback) {
  syncListeners.push(cb);
  countPendingSyncOps().then(cb);
  return () => {
    const idx = syncListeners.indexOf(cb);
    if (idx >= 0) syncListeners.splice(idx, 1);
  };
}

export async function notifySyncCountChange() {
  const count = await countPendingSyncOps();
  syncListeners.forEach(cb => cb(count));
}

/**
 * Process pending offline operations queue
 */
export async function processSyncQueue(): Promise<{ success: boolean; syncedCount: number; errors: string[] }> {
  if (isSyncing) {
    return { success: true, syncedCount: 0, errors: [] };
  }

  if (!navigator.onLine) {
    console.log('App offline. Sincronização em fila para quando a conexão retornar.');
    return { success: false, syncedCount: 0, errors: ['Dispositivo offline'] };
  }

  isSyncing = true;
  const errors: string[] = [];
  let syncedCount = 0;

  try {
    const pendingOps = await getPendingSyncOps();
    if (pendingOps.length === 0) {
      isSyncing = false;
      await notifySyncCountChange();
      return { success: true, syncedCount: 0, errors: [] };
    }

    // Sort operations by timestamp so draft and attachments sync in correct order
    pendingOps.sort((a, b) => a.timestamp - b.timestamp);

    for (const op of pendingOps) {
      await updateSyncOpStatus(op.operacao_id, 'enviando');

      try {
        const res = await syncOperationAPI(op.agendamento_id, op);

        if (res.ok) {
          await updateSyncOpStatus(op.operacao_id, 'sucesso');
          await removeSyncOp(op.operacao_id);
          syncedCount++;
        } else {
          const errorMsg = res.erro?.mensagem || 'Erro desconhecido na sincronização';
          
          // Handle version conflict by updating version
          if (res.erro?.codigo === 'CONFLITO_VERSAO' && op.tipo === 'rascunho') {
            console.warn('Conflito de versão detectado. Atualizando versão e re-tentando...');
            if (op.payload) {
              op.payload.versao = res.erro?.detalhes?.versao_atual || (op.payload.versao + 1);
            }
          }

          await updateSyncOpStatus(op.operacao_id, 'erro', errorMsg);
          errors.push(`Op ${op.tipo} (${op.operacao_id}): ${errorMsg}`);
        }
      } catch (err: any) {
        const msg = err.message || 'Falha de conexão com o servidor';
        await updateSyncOpStatus(op.operacao_id, 'erro', msg);
        errors.push(msg);
      }
    }
  } finally {
    isSyncing = false;
    await notifySyncCountChange();
  }

  return {
    success: errors.length === 0,
    syncedCount,
    errors
  };
}

// Auto sync when back online
if (typeof window !== 'undefined') {
  window.addEventListener('online', () => {
    console.log('Rede restabelecida. Disparando sincronização da fila de vistoria...');
    processSyncQueue();
  });
}
