/**
 * Memuat script eksternal secara dinamis.
 * @param {string} src - URL dari script.
 * @param {string} [dataAttribute] - Nama atribut data (e.g., 'data-client-key').
 * @param {string} [dataValue] - Nilai atribut data.
 * @returns {Promise<void>} - Promise yang resolve saat script dimuat, reject jika gagal.
 */
export function loadScript(src, attrName = '', attrValue = '') {
  return new Promise((resolve, reject) => {
    if (document.querySelector(`script[src="${src}"]`)) {
      resolve();
      return;
    }
    const script = document.createElement('script');
    script.src = src;
    script.onload = resolve;
    script.onerror = reject;
    if (attrName && attrValue) {
      script.setAttribute(attrName, attrValue);
    }
    document.head.appendChild(script);
  });
}


// Anda bisa menambahkan fungsi utilitas lainnya di sini jika diperlukan
