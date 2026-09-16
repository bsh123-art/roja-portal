(function () {
  const fetchJson = async (task, data) => {
    const params = new URLSearchParams({
      option: 'com_rojacomments',
      task: task,
      format: 'json',
      token: window.Joomla && window.Joomla.token ? window.Joomla.token : ''
    });

    Object.entries(data || {}).forEach(([key, value]) => params.set(key, value));

    const response = await fetch('index.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: params.toString()
    });

    const body = await response.json();
    if (!response.ok || body.success === false) {
      throw new Error(body.message || 'Permintaan gagal.');
    }

    return body;
  };

  const getInitials = (value) => {
    const text = (value || '').trim();
    if (!text) {
      return 'G';
    }
    return text.charAt(0).toUpperCase();
  };

  const renderComment = (comment, depth = 0) => {
    const item = document.createElement('article');
    item.className = 'roja-comments__item' + (depth > 0 ? ' roja-comments__item--reply' : '');
    item.id = 'comment-' + String(comment.id || '');

    const author = document.createElement('div');
    author.className = 'roja-comments__item-head';
    author.innerHTML = [
      '<div class="roja-comments__avatar">' + getInitials(comment.user_name || comment.guest_name || comment.author_name) + '</div>',
      '<div class="roja-comments__meta">',
      '<p class="roja-comments__user">' + (comment.user_name || comment.guest_name || 'Guest') + '</p>',
      '<div class="roja-comments__time">' + (comment.created || 'Baru') + '</div>',
      '</div>'
    ].join('');

    const content = document.createElement('p');
    content.className = 'roja-comments__content';
    content.textContent = comment.comment || '';

    const actions = document.createElement('div');
    actions.className = 'roja-comments__actions-row';
    actions.innerHTML = [
      '<button type="button" class="roja-comments__button" data-roja-recommend="' + (comment.id || '') + '">♡ ' + (comment.recommend_count || 0) + '</button>',
      '<button type="button" class="roja-comments__button" data-roja-reply="' + (comment.id || '') + '">Balas</button>',
      '<button type="button" class="roja-comments__button" data-roja-share="' + (comment.id || '') + '">Bagikan</button>',
      '<button type="button" class="roja-comments__button" data-roja-report="' + (comment.id || '') + '">Laporkan</button>'
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

  const setBodyLocked = (locked) => {
    document.body.classList.toggle('roja-comments-open', locked);
    document.body.style.overflow = locked ? 'hidden' : '';
  };

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.roja-comments-shell').forEach((shell) => {
      const articleId = Number(shell.dataset.rojaArticleId || 0);
      const maxLength = Number(shell.dataset.rojaMaxLength || 2000);
      const drawer = shell.querySelector('[data-roja-drawer]');
      const overlay = shell.querySelector('[data-roja-overlay]');
      const trigger = shell.querySelector('[data-roja-open]');
      const closeBtn = shell.querySelector('[data-roja-close]');
      const list = shell.querySelector('[data-roja-list]');
      const status = shell.querySelector('[data-roja-status]');
      const input = shell.querySelector('[data-roja-comment-input]');
      const submit = shell.querySelector('[data-roja-submit]');
      const cancel = shell.querySelector('[data-roja-cancel]');
      const counter = shell.querySelector('[data-roja-counter]');
      const more = shell.querySelector('[data-roja-load-more]');
      const sort = shell.querySelector('[data-roja-sort]');
      const filterButtons = shell.querySelectorAll('[data-roja-filter]');
      const totalNodes = shell.querySelectorAll('[data-roja-comment-total]');
      const articleTitle = shell.dataset.rojaArticleTitle || '';

      let currentFilter = 'all';
      let currentSort = 'newest';
      let offset = 0;
      let hasMore = false;
      let loaded = false;

      const setStatus = (message) => {
        if (status) {
          status.textContent = message || '';
        }
      };

      const updateCounter = () => {
        if (counter && input) {
          counter.textContent = String((input.value || '').length) + ' / ' + maxLength;
        }
      };

      const updateTotal = (total) => {
        totalNodes.forEach((node) => {
          node.textContent = String(total || 0);
        });
      };

      const openDrawer = () => {
        if (!drawer || !overlay) {
          return;
        }

        drawer.classList.add('is-open');
        overlay.classList.add('is-open');
        drawer.setAttribute('aria-hidden', 'false');
        overlay.setAttribute('aria-hidden', 'false');
        if (trigger) {
          trigger.setAttribute('aria-expanded', 'true');
        }
        setBodyLocked(true);
        if (!loaded) {
          loadComments(false);
        }
        if (input) {
          input.focus();
        }
      };

      const closeDrawer = () => {
        if (!drawer || !overlay) {
          return;
        }

        drawer.classList.remove('is-open');
        overlay.classList.remove('is-open');
        drawer.setAttribute('aria-hidden', 'true');
        overlay.setAttribute('aria-hidden', 'true');
        if (trigger) {
          trigger.setAttribute('aria-expanded', 'false');
        }
        setBodyLocked(false);
        if (trigger) {
          trigger.focus();
        }
      };

      const loadComments = async (append = false) => {
        if (!articleId || !list) {
          return;
        }

        try {
          const response = await fetchJson('comments.list', {
            article_id: articleId,
            offset: String(offset),
            limit: '20',
            filter: currentFilter,
            sort: currentSort
          });

          const items = Array.isArray(response.data?.items) ? response.data.items : [];
          if (!append) {
            list.innerHTML = '';
          }

          items.forEach((comment) => {
            list.appendChild(renderComment(comment));
          });

          const total = Number(response.data?.total || 0);
          updateTotal(total);
          hasMore = total > offset + items.length;
          if (more) {
            more.hidden = !hasMore;
          }
          loaded = true;
          setStatus(items.length ? '' : 'Belum ada komentar. Jadilah yang pertama memberi pendapat.');
        } catch (error) {
          setStatus(error.message || 'Komentar gagal dimuat.');
        }
      };

      if (trigger) {
        trigger.addEventListener('click', openDrawer);
      }

      if (overlay) {
        overlay.addEventListener('click', closeDrawer);
      }

      if (closeBtn) {
        closeBtn.addEventListener('click', closeDrawer);
      }

      if (cancel && input) {
        cancel.addEventListener('click', () => {
          input.value = '';
          updateCounter();
          setStatus('');
        });
      }

      if (input) {
        input.addEventListener('input', updateCounter);
      }

      if (submit && input) {
        submit.addEventListener('click', async () => {
          const comment = (input.value || '').trim();
          if (!comment) {
            setStatus('Komentar tidak boleh kosong.');
            return;
          }

          submit.disabled = true;
          submit.textContent = 'Mengirim...';

          try {
            const response = await fetchJson('comments.create', {
              article_id: articleId,
              comment: comment,
              guest_name: '',
              guest_email: '',
              website: ''
            });
            setStatus(response.message || 'Komentar berhasil dikirim.');
            input.value = '';
            updateCounter();
            offset = 0;
            await loadComments(false);
          } catch (error) {
            setStatus(error.message || 'Komentar gagal dikirim.');
          } finally {
            submit.disabled = false;
            submit.textContent = 'Kirim';
          }
        });
      }

      if (sort) {
        sort.addEventListener('change', async (event) => {
          currentSort = event.target.value;
          offset = 0;
          await loadComments(false);
        });
      }

      filterButtons.forEach((button) => {
        button.addEventListener('click', async () => {
          currentFilter = button.dataset.rojaFilter || 'all';
          filterButtons.forEach((btn) => btn.classList.toggle('is-active', btn === button));
          offset = 0;
          await loadComments(false);
        });
      });

      if (more) {
        more.addEventListener('click', async () => {
          offset += 20;
          await loadComments(true);
        });
      }

      shell.addEventListener('click', async (event) => {
        const target = event.target;
        const recommend = target.closest('[data-roja-recommend]');
        const reply = target.closest('[data-roja-reply]');
        const share = target.closest('[data-roja-share]');
        const report = target.closest('[data-roja-report]');
        const articleShare = target.closest('[data-roja-action="share-article"]');
        const articleRecommend = target.closest('[data-roja-action="recommend-article"]');

        if (articleRecommend) {
          try {
            const response = await fetchJson('comments.recommend', { comment_id: 0, article_id: articleId });
            setStatus(response.message || 'Artikel direkomendasikan.');
          } catch (error) {
            setStatus(error.message || 'Rekomendasi gagal.');
          }
          return;
        }

        if (articleShare) {
          const url = window.location.href.split('#')[0];
          try {
            await navigator.clipboard.writeText(url);
            setStatus('Tautan artikel disalin.');
          } catch (error) {
            setStatus('Tautan artikel: ' + url);
          }
          return;
        }

        if (recommend) {
          try {
            const response = await fetchJson('comments.recommend', { comment_id: recommend.dataset.rojaRecommend });
            setStatus(response.message || 'Rekomendasi berhasil.');
            offset = 0;
            await loadComments(false);
          } catch (error) {
            setStatus(error.message || 'Rekomendasi gagal.');
          }
          return;
        }

        if (reply) {
          const commentId = Number(reply.dataset.rojaReply || 0);
          const value = window.prompt('Balas komentar:', '');
          if (!value || !value.trim()) {
            return;
          }

          try {
            const response = await fetchJson('comments.reply', {
              article_id: articleId,
              parent_id: String(commentId),
              comment: value,
              guest_name: '',
              guest_email: '',
              website: ''
            });
            setStatus(response.message || 'Balasan berhasil dikirim.');
            offset = 0;
            await loadComments(false);
          } catch (error) {
            setStatus(error.message || 'Balasan gagal dikirim.');
          }
          return;
        }

        if (share) {
          const link = window.location.href.split('#')[0] + '#comment-' + (share.dataset.rojaShare || '');
          try {
            await navigator.clipboard.writeText(link);
            setStatus('Tautan komentar disalin.');
          } catch (error) {
            setStatus('Tautan komentar: ' + link);
          }
          return;
        }

        if (report) {
          const choice = window.prompt('Pilih alasan pelaporan:\n1 Spam\n2 Pelecehan\n3 Ujaran kebencian\n4 Informasi pribadi\n5 Tidak relevan\n6 Lainnya', 'other');
          const reasons = {
            '1': 'spam',
            '2': 'harassment',
            '3': 'hate',
            '4': 'privacy',
            '5': 'irrelevant',
            '6': 'other'
          };

          try {
            const response = await fetchJson('comments.report', {
              comment_id: report.dataset.rojaReport,
              reason: reasons[choice] || 'other',
              description: ''
            });
            setStatus(response.message || 'Laporan dikirim.');
          } catch (error) {
            setStatus(error.message || 'Laporan gagal dikirim.');
          }
        }
      });

      document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && drawer && drawer.classList.contains('is-open')) {
          closeDrawer();
        }
      });

      if (trigger && articleTitle) {
        trigger.setAttribute('title', 'Komentar pada ' + articleTitle);
      }

      updateCounter();
      updateTotal(Number(shell.querySelector('[data-roja-comment-total]')?.textContent || 0));
      if (trigger) {
        trigger.addEventListener('click', openDrawer);
      }
    });
  });
})();
