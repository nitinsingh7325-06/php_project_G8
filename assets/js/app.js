/* The Wave Men's Salon — App JS */
window.Wave = window.Wave || {};

Wave.csrf = function () {
  return document.querySelector('meta[name="csrf-token"]')?.content
    || document.querySelector('input[name="_csrf"]')?.value
    || '';
};

Wave.ajax = function (url, options = {}) {
  const opts = {
    method: options.method || 'POST',
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': Wave.csrf(),
      Accept: 'application/json',
      ...(options.json !== false ? { 'Content-Type': 'application/json' } : {}),
      ...(options.headers || {}),
    },
    credentials: 'same-origin',
    ...options,
  };

  if (options.data && opts.headers['Content-Type'] === 'application/json') {
    opts.body = JSON.stringify({ ...options.data, _csrf: Wave.csrf() });
  }

  return fetch(url, opts).then(async (res) => {
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      throw Object.assign(new Error(data.message || 'Request failed'), { data, status: res.status });
    }
    return data;
  });
};

Wave.toast = function (message, type = 'success') {
  let el = document.getElementById('wave-toast');
  if (!el) {
    el = document.createElement('div');
    el.id = 'wave-toast';
    el.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:9999;min-width:260px;max-width:360px;';
    document.body.appendChild(el);
  }
  const color = type === 'error' ? '#e74c3c' : '#D4AF37';
  el.innerHTML = `<div style="background:rgba(18,18,18,0.95);border:1px solid ${color};color:#F5F5F5;padding:14px 16px;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,.4);">${message}</div>`;
  setTimeout(() => { el.innerHTML = ''; }, 3500);
};

Wave.toggleSidebar = function () {
  document.querySelector('.sidebar')?.classList.toggle('show');
  document.querySelector('.sidebar')?.classList.toggle('collapsed');
  document.querySelector('.main-panel')?.classList.toggle('expanded');
};

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-toggle-sidebar]').forEach((btn) => {
    btn.addEventListener('click', Wave.toggleSidebar);
  });

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((e) => {
      if (e.isIntersecting) {
        e.target.style.opacity = '1';
        e.target.style.transform = 'none';
      }
    });
  }, { threshold: 0.12 });

  document.querySelectorAll('.reveal').forEach((el) => {
    el.style.opacity = '0';
    el.style.transform = 'translateY(20px)';
    el.style.transition = 'opacity .6s ease, transform .6s ease';
    observer.observe(el);
  });
});
