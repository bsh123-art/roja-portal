(function () {
  const fetchJson = async (task, data) => {
    const params = new URLSearchParams({ option: 'com_rojacomments', task: task, format: 'json', token: (window.Joomla && Joomla.token) ? Joomla.token : '' });
    Object.entries(data || {}).forEach(([key, value]) => params.set(key, value));

    const response = await fetch('index.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8', 'X-Requested-With': 'XMLHttpRequest' },
      body: params.toString()
    });
    const body = await response.json();
    if (!response.ok || body.success === false) { throw new Error(body.message || 'Permintaan gagal.'); }
    return body;
  };

  const renderComment = (comment, depth = 0) => {
    const item = document.createElement('article');
    item.className = 'roja-comments__item' + (depth > 0 ? ' roja-comments__item--reply' : '');
    item.id = 'comment-' + String(comment.id);

    const author = document.createElement('div');
    author.className = 'roja-comments__item-head';
    author.innerHTML = [
      '<div class="roja-comments__avatar">' + (comment.user_name ? comment.user_name.charAt(0).toUpperCase() : 'G') + '</div>',
      '<div class="roja-comments__meta">',
      '<p class="roja-comments__user">' + (comment.user_name || 'Guest') + '</p>',
      '<div class="roja-comments__time">' + (comment.created || 'Baru') + '</div>',
      '</div>'
    ].join('');

    const content = document.createElement('p');
    content.className = 'roja-comments__content';
    content.textContent = comment.comment || '';

    const actions = document.createElement('div');
    actions.className = 'roja-comments__actions-row';
    actions.innerHTML = [
      '<button type="button" class="roja-comments__button" data-roja-recommend="' + comment.id + '">♡ ' + (comment.recommend_count || 0) + ' Recommend</button>',
      '<button type="button" class="roja-comments__button" data-roja-reply="' + comment.id + '">Balas</button>',
      '<button type="button" class="roja-comments__button" data-roja-share="' + comment.id + '">Bagikan</button>',
      '<button type="button" class="roja-comments__button" data-roja-report="' + comment.id + '">Laporkan</button>'
    ].join('');

    item.appendChild(author);
    item.appendChild(content);
    item.appendChild(actions);

    if (Array.isArray(comment.children) && comment.children.length) {
      const replies = document.createElement('div');
      replies.className = 'roja-comments__replies';
      comment.children.forEach((child) => replies.appendChild(renderComment(child, depth + 1)));
      item.appendChild(replies);
    }

    return item;
  };

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.roja-comments').forEach((section) => {
      const list = section.querySelector('[data-roja-list]');
      const status = section.querySelector('[data-roja-status]');
      const input = section.querySelector('[data-roja-comment-input]');
      const submit = section.querySelector('[data-roja-submit]');
      const counter = section.querySelector('[data-roja-counter]');
      const more = section.querySelector('[data-roja-load-more]');
      const sort = section.querySelector('[data-roja-sort]');
      const filterButtons = section.querySelectorAll('[data-roja-filter]');
      const countButton = section.querySelector('[data-roja-count-button]');
      const articleId = Number(section.dataset.articleId || 0);
      const maxLength = Number(section.dataset.maxLength || 2000);
      let currentFilter = 'all';
      let currentSort = 'newest';
      let offset = 0;

      const setStatus = (message) => {
        if (status) status.textContent = message || '';
      };
      const updateCounter = () => {
        if (counter && input) counter.textContent = String(input.value.length) + ' / ' + maxLength;
      };

      const loadComments = async (append = false) => {
        if (!articleId) return;
        try {
          const response = await fetchJson('comments.list', { article_id: articleId, offset: String(offset), limit: '20', filter: currentFilter, sort: currentSort });
          const items = response.data?.items || [];
          if (!append) list.innerHTML = '';
          items.forEach((comment) => list.appendChild(renderComment(comment)));
          const total = response.data?.total || 0;
          const countNode = section.querySelector('[data-roja-comment-total]');
          if (countNode) countNode.textContent = String(total);
          if (more) more.hidden = total <= offset + items.length;
          setStatus(items.length ? '' : 'Belum ada komentar.');
        } catch (error) {
          setStatus(error.message || 'Komentar gagal dimuat.');
        }
      };

      if (input) input.addEventListener('input', updateCounter);
      if (submit) {
        submit.addEventListener('click', async () => {
          const comment = (input && input.value || '').trim();
          if (!comment) { setStatus('Komentar tidak boleh kosong.'); return; }
          try {
            const response = await fetchJson('comments.create', { article_id: articleId, comment: comment, guest_name: '', guest_email: '', website: '' });
            setStatus(response.message || 'Komentar berhasil dikirim.');
            if (input) { input.value = ''; updateCounter(); }
            offset = 0; await loadComments(false);
          } catch (error) { setStatus(error.message || 'Komentar gagal dikirim.'); }
        });
      }
      if (more) {
        more.addEventListener('click', async () => { offset += 20; await loadComments(true); });
      }
      if (sort) {
        sort.addEventListener('change', async (event) => { currentSort = event.target.value; offset = 0; await loadComments(false); });
      }
      filterButtons.forEach((button) => {
        button.addEventListener('click', async () => {
          currentFilter = button.dataset.rojaFilter || 'all';
          filterButtons.forEach((btn) => btn.classList.toggle('is-active', btn === button));
          offset = 0;
          await loadComments(false);
        });
      });
      section.addEventListener('click', async (event) => {
        const target = event.target;
        const recommend = target.closest('[data-roja-recommend]');
        const reply = target.closest('[data-roja-reply]');
        const share = target.closest('[data-roja-share]');
        const report = target.closest('[data-roja-report]');

        if (recommend) {
          try {
            const response = await fetchJson('comments.recommend', { comment_id: recommend.dataset.rojaRecommend });
            setStatus(response.message || 'Rekomendasi berhasil.');
            offset = 0; await loadComments(false);
          } catch (error) { setStatus(error.message || 'Rekomendasi gagal.'); }
        }
        if (reply) {
          const value = window.prompt('Balas komentar:', '');
          if (!value || !value.trim()) return;
          try {
            const response = await fetchJson('comments.reply', { article_id: articleId, parent_id: reply.dataset.rojaReply, comment: value, guest_name: '', guest_email: '', website: '' });
            setStatus(response.message || 'Balasan berhasil dikirim.');
            offset = 0; await loadComments(false);
          } catch (error) { setStatus(error.message || 'Balasan gagal dikirim.'); }
        }
        if (share) {
          const link = window.location.origin + window.location.pathname + '#comment-' + share.dataset.rojaShare;
          try { await navigator.clipboard.writeText(link); setStatus('Link komentar disalin.'); } catch (error) { setStatus('Link komentar: ' + link); }
        }
        if (report) {
          const choice = window.prompt('Pilih alasan pelaporan:\n1 Spam\n2 Pelecehan\n3 Ujaran kebencian\n4 Informasi pribadi\n5 Tidak relevan\n6 Lainnya', 'other');
          const reasons = { '1': 'spam', '2': 'harassment', '3': 'hate', '4': 'privacy', '5': 'irrelevant', '6': 'other' };
          try {
            const response = await fetchJson('comments.report', { comment_id: report.dataset.rojaReport, reason: reasons[choice] || 'other', description: '' });
            setStatus(response.message || 'Laporan dikirim.');
          } catch (error) { setStatus(error.message || 'Laporan gagal dikirim.'); }
        }
      });
      if (countButton) countButton.addEventListener('click', () => section.scrollIntoView({ behavior: 'smooth', block: 'start' }));
      updateCounter();
      loadComments(false);
    });
  });
})();
