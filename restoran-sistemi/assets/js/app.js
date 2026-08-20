/* Ortak frontend yardımcıları */
(function () {
  const BASE = window.BASE_URL || '';

  async function api(path, data, method) {
    const opts = {
      method: method || (data ? 'POST' : 'GET'),
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.CSRF_TOKEN || '' },
    };
    if (data) opts.body = JSON.stringify(Object.assign({ csrf_token: window.CSRF_TOKEN }, data));

    const res = await fetch(BASE + path, opts);
    let json;
    try {
      json = await res.json();
    } catch (e) {
      throw new Error('Sunucu beklenmeyen bir cevap döndürdü.');
    }
    if (!res.ok || json.success === false) {
      throw new Error(json.message || 'İşlem gerçekleştirilemedi.');
    }
    return json.data;
  }

  function money(v) {
    return new Intl.NumberFormat('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(v || 0)) + ' ₺';
  }

  function toast(message, type) {
    const el = document.createElement('div');
    el.className = 'alert alert-' + (type || 'success') + ' position-fixed shadow';
    el.style.cssText = 'top:80px;right:20px;z-index:1080;min-width:260px';
    el.textContent = message;
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 3000);
  }

  function escapeHtml(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, (c) => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[c]));
  }

  window.App = { api, money, toast, escapeHtml };
})();
