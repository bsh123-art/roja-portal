(function () {
  const navRoot = document.querySelector('[data-roja-article-nav]');

  if (!navRoot) {
    return;
  }

  const articleId = Number(navRoot.dataset.articleId || 0);
  const categoryId = Number(navRoot.dataset.categoryId || 0);

  if (!articleId || !categoryId) {
    return;
  }

  const prevEl = navRoot.querySelector('[data-nav="prev"]');
  const nextEl = navRoot.querySelector('[data-nav="next"]');
  const loadingEl = navRoot.querySelector('[data-nav="loading"]');

  const setLoading = (isLoading) => {
    if (loadingEl) {
      loadingEl.hidden = !isLoading;
    }
  };

  const renderCard = (target, item, direction) => {
    if (!target || !item) {
      return;
    }

    const label = direction === 'previous' ? 'Previous' : 'Next';
    target.innerHTML = `
      <a href="${item.link}" class="rp-article-nav-card ${direction}">
        <span class="rp-article-nav-label">${label}</span>
        <span class="rp-article-nav-title">${item.title}</span>
        <span class="rp-article-nav-action">Read article</span>
      </a>
    `;
  };

  const fetchNavigation = async () => {
    setLoading(true);

    const url = new URL(window.location.origin + '/index.php');
    url.searchParams.set('option', 'com_rojaportal');
    url.searchParams.set('task', 'article.getAdjacentArticles');
    url.searchParams.set('article_id', String(articleId));
    url.searchParams.set('catid', String(categoryId));

    try {
      const response = await fetch(url, {
        method: 'GET',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        }
      });

      if (!response.ok) {
        throw new Error('HTTP ' + response.status);
      }

      const data = await response.json();
      if (data.status !== 'ok') {
        throw new Error('No adjacent article found');
      }

      renderCard(prevEl, data.prev, 'previous');
      renderCard(nextEl, data.next, 'next');
    } catch (error) {
      console.error('Roja article navigation error:', error);
      if (prevEl) prevEl.innerHTML = '';
      if (nextEl) nextEl.innerHTML = '';
    } finally {
      setLoading(false);
    }
  };

  fetchNavigation();
})();
