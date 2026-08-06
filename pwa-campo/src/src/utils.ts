/**
 * Date and calculation utilities for NORMAM inspection deadlines
 */

/**
 * Calculates deadline date = baseDate + days (60 or 90)
 * Format: YYYY-MM-DD
 */
export function calculateVencimento(baseDateIso: string, days: number): string {
  if (!baseDateIso) return '';
  const date = new Date(baseDateIso + 'T00:00:00');
  if (isNaN(date.getTime())) return '';
  
  date.setDate(date.getDate() + days);
  
  const yyyy = date.getFullYear();
  const mm = String(date.getMonth() + 1).padStart(2, '0');
  const dd = String(date.getDate()).padStart(2, '0');
  
  return `${yyyy}-${mm}-${dd}`;
}

/**
 * Formats YYYY-MM-DD to dd/mm/yyyy
 */
export function formatDateBr(dateIso?: string): string {
  if (!dateIso) return '-';
  const parts = dateIso.split('T')[0].split('-');
  if (parts.length !== 3) return dateIso;
  return `${parts[2]}/${parts[1]}/${parts[0]}`;
}

/**
 * Today ISO string YYYY-MM-DD
 */
export function getTodayIso(): string {
  const d = new Date();
  const yyyy = d.getFullYear();
  const mm = String(d.getMonth() + 1).padStart(2, '0');
  const dd = String(d.getDate()).padStart(2, '0');
  return `${yyyy}-${mm}-${dd}`;
}

/**
 * Generate UUID v4 for idempotent operations
 */
export function generateUUID(): string {
  if (typeof crypto !== 'undefined' && crypto.randomUUID) {
    return crypto.randomUUID();
  }
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
    const r = (Math.random() * 16) | 0;
    const v = c === 'x' ? r : (r & 0x3) | 0x8;
    return v.toString(16);
  });
}
