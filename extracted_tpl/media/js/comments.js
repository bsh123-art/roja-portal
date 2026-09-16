(() => {
  'use strict';
  const section = document.getElementById('rp-comments');
  if (!section || section.dataset.initialized) return;
  const button = section.querySelector('.rp-comments-load');
  const share = section.querySelector('.rp-comments-share');
  const close = section.querySelector('.rp-comments-close');
  const status = section.querySelector('.rp-comments-status');
  const thread = document.getElementById('disqus_thread');
  if (!button || !status || !thread) return;
  const { shortname, identifier, url, title } = section.dataset;
  if (!/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/.test(shortname || '')
      || !/^joomla-article-[1-9][0-9]*$/.test(identifier || '')) return;
  let pageUrl;
  try { pageUrl = new URL(url); } catch { return; }
  if (!['http:', 'https:'].includes(pageUrl.protocol)) return;
  section.dataset.initialized = 'true';
  let loading = false;
  let opener = null;
  const openPanel = () => {
    section.classList.add('is-open');
    section.setAttribute('aria-modal', 'true');
    document.body.classList.add('rp-comments-lock');
  };
  const closePanel = () => {
    section.classList.remove('is-open');
    section.removeAttribute('aria-modal');
    document.body.classList.remove('rp-comments-lock');
    opener?.focus();
  };
  close?.addEventListener('click', closePanel);
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && section.classList.contains('is-open')) closePanel();
  });
  share?.addEventListener('click', async () => {
    const shareData = { title, url: pageUrl.href };
    try {
      if (navigator.share) await navigator.share(shareData);
      else if (navigator.clipboard) await navigator.clipboard.writeText(pageUrl.href);
      status.textContent = 'Tautan diskusi disalin';
    } catch {
      status.textContent = 'Tautan diskusi siap dibagikan';
    }
  });
  button.addEventListener('click', () => {
    opener = document.activeElement;
    openPanel();
    if (loading) return;
    loading = true;
    button.disabled = true;
    status.textContent = 'Memuat komentar…';
    window.disqus_config = function () {
      this.page.url = pageUrl.href;
      this.page.identifier = identifier;
      this.page.title = title;
      this.callbacks = this.callbacks || {};
      this.callbacks.onReady = [() => {
        status.textContent = '';
        button.hidden = true;
      }];
    };
    const script = document.createElement('script');
    script.src = `https://${shortname}.disqus.com/embed.js`;
    script.async = true;
    script.setAttribute('data-timestamp', String(Date.now()));
    script.onerror = () => {
      loading = false;
      button.disabled = false;
      button.querySelector('span').textContent = 'Coba muat komentar lagi';
      status.textContent = 'Komentar belum dapat dimuat. Periksa koneksi atau pemblokir konten, lalu coba lagi.';
      script.remove();
    };
    script.onload = () => {
      button.hidden = true;
      status.textContent = '';
    };
    document.head.appendChild(script);
  });
})();
