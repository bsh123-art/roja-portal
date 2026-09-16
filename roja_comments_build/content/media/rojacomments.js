(() => {
  'use strict';
  document.querySelectorAll('.roja-comments').forEach(section => {
    if (section.dataset.ready) return;
    section.dataset.ready = '1';
    const list = section.querySelector('[data-comment-list]');
    const input = section.querySelector('[data-comment-input]');
    const status = section.querySelector('[data-comment-status]');
    const prompt = section.querySelector('[data-comment-open]');
    const composer = section.querySelector('[data-comment-composer]');
    const counter = section.querySelector('[data-comment-counter]');
    const token = document.querySelector('input[name="' + Joomla.getOptions('csrf.token', '') + '"]')?.value || Joomla.getOptions('csrf.token', '');
    const request = async (task, data = {}) => { const body = new URLSearchParams({ option: 'com_ajax', plugin: 'rojacomments', group: 'ajax', format: 'json', task, article_id: section.dataset.articleId, [token]: '1', ...data }); const response = await fetch('index.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' }, body }); const json = await response.json(); if (json.success === false) throw new Error(json.message || 'Permintaan gagal'); return json.data || json; };
    const escape = value => String(value ?? '').replace(/[&<>"']/g, character => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[character]));
    const render = (comments, append = false) => { const html = comments.map(item => `<article class="roja-comments__item" style="--roja-depth:${Math.min(item.parent_id ? 1 : 0, 1)}" data-comment-id="${item.id}"><header><strong>${escape(item.name)}</strong><time>${escape(item.created)}</time></header><p>${escape(item.comment)}</p><footer><button type="button" data-recommend="${item.id}">♡ ${item.recommend_count} Rekomendasikan</button><button type="button" data-reply="${item.id}">Balas</button><button type="button" data-report="${item.id}">Laporkan</button></footer></article>`).join(''); if (append) list.insertAdjacentHTML('beforeend', html); else list.innerHTML = html; };
    let offset = 0;
    let sort = 'newest';
    const load = async (append = false) => { try { const result = await request('list', { sort, offset }); render(result.comments || [], append); section.querySelector('[data-comments-count]').textContent = `${result.total || 0} komentar`; section.querySelector('[data-comment-more]').hidden = offset + 10 >= (result.total || 0); } catch (error) { status.textContent = error.message; } };
    prompt.addEventListener('click', () => { composer.hidden = false; input.focus(); });
    input.addEventListener('input', () => { counter.textContent = `${input.value.length} / ${section.dataset.maxLength}`; });
    section.querySelector('[data-comment-cancel]').addEventListener('click', () => { composer.hidden = true; input.value = ''; counter.textContent = `0 / ${section.dataset.maxLength}`; });
    section.querySelector('[data-comment-submit]').addEventListener('click', async () => { if (!input.value.trim()) return; try { await request('create', { comment: input.value, parent_id: input.dataset.parentId || 0 }); status.textContent = 'Komentar menunggu moderasi.'; input.value = ''; delete input.dataset.parentId; composer.hidden = true; } catch (error) { status.textContent = error.message; } });
    section.querySelector('[data-sort-select]').addEventListener('change', event => { sort = event.target.value; offset = 0; load(); });
    section.querySelectorAll('[data-sort]').forEach(tab => tab.addEventListener('click', () => { sort = tab.dataset.sort === 'recommended' ? 'recommended' : 'newest'; offset = 0; section.querySelectorAll('[data-sort]').forEach(item => item.classList.toggle('is-active', item === tab)); load(); }));
    section.querySelector('[data-comment-more]').addEventListener('click', () => { offset += 10; load(true); });
    list.addEventListener('click', async event => { const button = event.target.closest('[data-recommend]'); const reply = event.target.closest('[data-reply]'); const report = event.target.closest('[data-report]'); try { if (button) { const result = await request('recommend', { comment_id: button.dataset.recommend }); button.disabled = true; if (result.duplicate) status.textContent = 'Anda sudah merekomendasikan komentar ini.'; } if (reply) { composer.hidden = false; input.dataset.parentId = reply.dataset.reply; input.focus(); } if (report) { await request('report', { comment_id: report.dataset.report, reason: 'other' }); status.textContent = 'Laporan dikirim ke moderator.'; } } catch (error) { status.textContent = error.message; } });
    load();
  });
})();
