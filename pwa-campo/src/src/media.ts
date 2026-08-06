/**
 * Image processing and camera utilities for App Vistoria Naval
 */

export interface ProcessedImage {
  blob: Blob;
  dataUrl: string;
  sha256: string;
  sizeBytes: number;
}

/**
 * Resizes and compresses an image file/blob to a maximum dimension while maintaining readability
 */
export async function compressAndProcessImage(
  file: File | Blob, 
  maxWidth = 1600, 
  maxHeight = 1600, 
  quality = 0.82
): Promise<ProcessedImage> {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onerror = () => reject(new Error('Falha ao ler arquivo de imagem'));
    reader.onload = (e) => {
      const img = new Image();
      img.onerror = () => reject(new Error('Formato de imagem inválido'));
      img.onload = async () => {
        let width = img.width;
        let height = img.height;

        // Calculate aspect ratio scale
        if (width > maxWidth || height > maxHeight) {
          if (width / height > maxWidth / maxHeight) {
            height = Math.round((height * maxWidth) / width);
            width = maxWidth;
          } else {
            width = Math.round((width * maxHeight) / height);
            height = maxHeight;
          }
        }

        const canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;

        const ctx = canvas.getContext('2d');
        if (!ctx) {
          reject(new Error('Não foi possível obter o contexto 2D do Canvas'));
          return;
        }

        // Draw image
        ctx.fillStyle = '#FFFFFF';
        ctx.fillRect(0, 0, width, height);
        ctx.drawImage(img, 0, 0, width, height);

        // Convert to canvas blob
        canvas.toBlob(async (blob) => {
          if (!blob) {
            reject(new Error('Erro ao comprimir imagem'));
            return;
          }

          const dataUrl = canvas.toDataURL('image/jpeg', quality);
          const arrayBuffer = await blob.arrayBuffer();
          const sha256 = await calculateSHA256(arrayBuffer);

          resolve({
            blob,
            dataUrl,
            sha256,
            sizeBytes: blob.size,
          });
        }, 'image/jpeg', quality);
      };

      img.src = e.target?.result as string;
    };

    reader.readAsDataURL(file);
  });
}

/**
 * Calculates SHA-256 checksum of an ArrayBuffer
 */
export async function calculateSHA256(buffer: ArrayBuffer): Promise<string> {
  if (crypto.subtle && crypto.subtle.digest) {
    const hashBuffer = await crypto.subtle.digest('SHA-256', buffer);
    const hashArray = Array.from(new Uint8Array(hashBuffer));
    return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
  }
  
  // Fallback hash implementation if SubtleCrypto is unavailable in non-secure context
  let hash = 0;
  const view = new Uint8Array(buffer);
  for (let i = 0; i < view.length; i++) {
    hash = (hash << 5) - hash + view[i];
    hash |= 0;
  }
  return Math.abs(hash).toString(16).padStart(16, '0');
}

/**
 * Converts ArrayBuffer / Blob to base64 string
 */
export function blobToBase64(blob: Blob): Promise<string> {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onloadend = () => resolve(reader.result as string);
    reader.onerror = reject;
    reader.readAsDataURL(blob);
  });
}
