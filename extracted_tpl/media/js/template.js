(() => {
  'use strict';
  const breakingLabel = document.body.dataset.rpBreakingLabel;
  document.querySelectorAll('.roja-breaking').forEach(list => {
    list.tabIndex = 0;
    list.setAttribute('role', 'region');
    if (breakingLabel) {
      list.setAttribute('aria-label', breakingLabel);
    }
  });

  document.querySelectorAll('.rp-article-tools').forEach(tools => {
    const article = tools.closest('.com-content-article') || document.querySelector('.com-content-article');
    const info = article?.querySelector('.article-info.text-muted, .article-info');
    if (!article || !info || tools.dataset.bound) return;
    tools.dataset.bound = '1';
    info.insertAdjacentElement('afterend', tools);
    const body = article.querySelector('.com-content-article__body');
    const listen = tools.querySelector('.rp-article-listen');
    const share = tools.querySelector('.rp-article-share');
    const save = tools.querySelector('.rp-article-save');
    const comments = tools.querySelector('.rp-article-comments');
    const commentsPanel = document.getElementById('rp-comments');
    const commentsLoader = commentsPanel?.querySelector('.rp-comments-load');
    if (comments && (!commentsPanel || !commentsLoader)) {
      comments.hidden = true;
      comments.setAttribute('aria-disabled', 'true');
    }
    const storageKey = `roja-saved-article-${location.pathname}`;
    const indonesiaVoice = () => window.speechSynthesis.getVoices().find(voice => {
      const language = voice.lang.toLowerCase().replace('_', '-');
      return language === 'id-id' || language.startsWith('id-');
    });
    const saved = localStorage.getItem(storageKey) === '1';
    save?.setAttribute('aria-checked', String(saved));
    save?.classList.toggle('is-saved', saved);
    listen?.addEventListener('click', () => {
      if (!body || !('speechSynthesis' in window)) return;
      if (speechSynthesis.speaking) {
        speechSynthesis.cancel();
        listen.classList.remove('is-active');
        return;
      }
      const text = body.innerText.trim();
      if (!text) return;
      const utterance = new SpeechSynthesisUtterance(text);
      utterance.lang = 'id-ID';
      utterance.rate = 0.95;
      utterance.pitch = 1;
      utterance.volume = 1;
      const voice = indonesiaVoice();
      if (voice) utterance.voice = voice;
      const finish = () => listen.classList.remove('is-active');
      utterance.onend = finish;
      utterance.onerror = finish;
      window.speechSynthesis.speak(utterance);
      listen.classList.add('is-active');
    });
    share?.addEventListener('click', async () => {
      const data = { title: document.title, url: location.href };
      if (navigator.share) await navigator.share(data).catch(() => {});
      else if (navigator.clipboard) await navigator.clipboard.writeText(location.href);
      share.classList.add('is-active');
    });
    save?.addEventListener('click', () => {
      const next = save.getAttribute('aria-checked') !== 'true';
      save.setAttribute('aria-checked', String(next));
      save.classList.toggle('is-saved', next);
      localStorage.setItem(storageKey, next ? '1' : '0');
    });
    comments?.addEventListener('click', () => {
      if (!commentsPanel || !commentsLoader) return;
      commentsLoader.click();
    });
  });
  const button = document.querySelector('.rp-menu-toggle');
  const nav = document.getElementById('rp-navigation');
  const overlay = document.querySelector('.rp-nav-overlay');
  if (!button || !nav || !overlay) return;
  const closeButton = nav.querySelector('.rp-menu-close');
  const mobileSearch = nav.querySelector('.rp-mobile-search');
  const search = document.querySelector('.rp-tools .rp-search');
  const anchor = document.createComment('ROJA desktop search position');
  if (search) search.before(anchor);
  const mobile = window.matchMedia('(max-width: 780px)');
  let opened = false;
  let previousOverflow = '';
  const focusable = () => [...nav.querySelectorAll('a[href],button,input,select,textarea,[tabindex]')]
    .filter(el => !el.disabled && el.tabIndex >= 0 && el.getClientRects().length && getComputedStyle(el).visibility !== 'hidden');
  const close = (restoreFocus = true) => {
    if (!opened) return;
    opened = false;
    nav.classList.remove('is-open');
    overlay.hidden = true;
    button.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = previousOverflow;
    if (restoreFocus) button.focus();
  };
  const open = () => {
    if (!mobile.matches || opened) return;
    opened = true;
    previousOverflow = document.body.style.overflow;
    nav.classList.add('is-open');
    overlay.hidden = false;
    button.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
    (focusable()[0] || closeButton)?.focus();
  };
  const sync = () => {
    const active = document.activeElement;
    close(false);
    if (search && mobileSearch) {
      if (mobile.matches) mobileSearch.appendChild(search);
      else anchor.after(search);
    }
    if (mobile.matches && nav.contains(active)) button.focus();
    if (!mobile.matches && active === closeButton) nav.querySelector('a[href]')?.focus();
  };
  button.addEventListener('click', () => opened ? close() : open());
  closeButton?.addEventListener('click', () => close());
  overlay.addEventListener('click', () => close());
  nav.addEventListener('click', e => { if (e.target.closest('a[href]') && mobile.matches) close(); });
  document.addEventListener('keydown', e => {
    if (!opened) return;
    if (e.key === 'Escape') { e.preventDefault(); close(); }
    if (e.key === 'Tab') {
      const items = focusable();
      if (!items.length) return;
      const first = items[0], last = items[items.length - 1];
      if (e.shiftKey && (document.activeElement === first || !nav.contains(document.activeElement))) {
        e.preventDefault(); last.focus();
      } else if (!e.shiftKey && (document.activeElement === last || !nav.contains(document.activeElement))) {
        e.preventDefault(); first.focus();
      }
    }
  });
  mobile.addEventListener('change', sync);
  sync();
})();
