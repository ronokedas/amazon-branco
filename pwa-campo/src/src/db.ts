import { openDB, DBSchema, IDBPDatabase } from 'idb';
import { 
  Session, 
  AgendaItem, 
  InspectionPackage, 
  SyncOperation, 
  ReportItem 
} from './types';

interface VistoriaNavalDB extends DBSchema {
  session: {
    key: string;
    value: Session & { id: string };
  };
  agenda: {
    key: string;
    value: AgendaItem;
  };
  packages: {
    key: string;
    value: InspectionPackage;
  };
  syncQueue: {
    key: string;
    value: SyncOperation;
    indexes: { 'by-agendamento': string; 'by-status': string };
  };
  reports: {
    key: string;
    value: ReportItem;
  };
  settings: {
    key: string;
    value: { key: string; value: any };
  };
}

const DB_NAME = 'pwa_vistoria_naval_db';
const DB_VERSION = 1;

let dbPromise: Promise<IDBPDatabase<VistoriaNavalDB>> | null = null;

export function getDB(): Promise<IDBPDatabase<VistoriaNavalDB>> {
  if (!dbPromise) {
    dbPromise = openDB<VistoriaNavalDB>(DB_NAME, DB_VERSION, {
      upgrade(db) {
        if (!db.objectStoreNames.contains('session')) {
          db.createObjectStore('session', { keyPath: 'id' });
        }
        if (!db.objectStoreNames.contains('agenda')) {
          db.createObjectStore('agenda', { keyPath: 'id' });
        }
        if (!db.objectStoreNames.contains('packages')) {
          db.createObjectStore('packages', { keyPath: 'agendamento.id' });
        }
        if (!db.objectStoreNames.contains('syncQueue')) {
          const syncStore = db.createObjectStore('syncQueue', { keyPath: 'operacao_id' });
          syncStore.createIndex('by-agendamento', 'agendamento_id');
          syncStore.createIndex('by-status', 'status');
        }
        if (!db.objectStoreNames.contains('reports')) {
          db.createObjectStore('reports', { keyPath: 'id' });
        }
        if (!db.objectStoreNames.contains('settings')) {
          db.createObjectStore('settings', { keyPath: 'key' });
        }
      },
    });
  }
  return dbPromise;
}

// Session Helpers
export async function saveSessionLocal(session: Session): Promise<void> {
  const db = await getDB();
  await db.put('session', { ...session, id: 'current_session' });
}

export async function getSessionLocal(): Promise<Session | null> {
  try {
    const db = await getDB();
    const res = await db.get('session', 'current_session');
    return res || null;
  } catch (err) {
    console.warn('Error reading local session:', err);
    return null;
  }
}

export async function clearSessionLocal(): Promise<void> {
  const db = await getDB();
  await db.delete('session', 'current_session');
}

// Agenda Helpers
export async function saveAgendaLocal(items: AgendaItem[]): Promise<void> {
  const db = await getDB();
  const tx = db.transaction('agenda', 'readwrite');
  for (const item of items) {
    await tx.store.put(item);
  }
  await tx.done;
}

export async function getAgendaLocal(): Promise<AgendaItem[]> {
  const db = await getDB();
  return db.getAll('agenda');
}

// Package Helpers
export async function savePackageLocal(pkg: InspectionPackage): Promise<void> {
  const db = await getDB();
  await db.put('packages', pkg);
  
  // Mark agenda item as downloaded
  const agendaItem = await db.get('agenda', pkg.agendamento.id);
  if (agendaItem) {
    agendaItem.isDownloaded = true;
    await db.put('agenda', agendaItem);
  }
}

export async function getPackageLocal(agendamentoId: string): Promise<InspectionPackage | null> {
  const db = await getDB();
  const pkg = await db.get('packages', agendamentoId);
  return pkg || null;
}

export async function getAllPackagesLocal(): Promise<InspectionPackage[]> {
  const db = await getDB();
  return db.getAll('packages');
}

// Sync Queue Helpers
export async function enqueueSyncOp(op: SyncOperation): Promise<void> {
  const db = await getDB();
  await db.put('syncQueue', op);
}

export async function getPendingSyncOps(): Promise<SyncOperation[]> {
  const db = await getDB();
  return db.getAllFromIndex('syncQueue', 'by-status', 'pendente');
}

export async function getAllSyncOps(): Promise<SyncOperation[]> {
  const db = await getDB();
  return db.getAll('syncQueue');
}

export async function updateSyncOpStatus(
  operacaoId: string, 
  status: 'pendente' | 'enviando' | 'erro' | 'sucesso',
  mensagemErro?: string
): Promise<void> {
  const db = await getDB();
  const op = await db.get('syncQueue', operacaoId);
  if (op) {
    op.status = status;
    if (mensagemErro !== undefined) op.mensagem_erro = mensagemErro;
    await db.put('syncQueue', op);
  }
}

export async function removeSyncOp(operacaoId: string): Promise<void> {
  const db = await getDB();
  await db.delete('syncQueue', operacaoId);
}

export async function countPendingSyncOps(): Promise<number> {
  const db = await getDB();
  const ops = await db.getAllFromIndex('syncQueue', 'by-status', 'pendente');
  return ops.length;
}

// Reports Helpers
export async function saveReportsLocal(reports: ReportItem[]): Promise<void> {
  const db = await getDB();
  const tx = db.transaction('reports', 'readwrite');
  for (const report of reports) {
    await tx.store.put(report);
  }
  await tx.done;
}

export async function getReportsLocal(): Promise<ReportItem[]> {
  const db = await getDB();
  return db.getAll('reports');
}

// Settings Helpers
export async function getSetting<T>(key: string, defaultValue: T): Promise<T> {
  try {
    const db = await getDB();
    const res = await db.get('settings', key);
    return res ? res.value : defaultValue;
  } catch {
    return defaultValue;
  }
}

export async function setSetting(key: string, value: any): Promise<void> {
  const db = await getDB();
  await db.put('settings', { key, value });
}
