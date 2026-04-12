/* =============================================
   STEIRER PELLETS – Blog JavaScript
   Lädt Artikel aus der REST-API und rendert sie
   ============================================= */

'use strict';

const BLOG_CACHE_KEY = 'sp_blog_articles_cache';

/** Escapet HTML-Zeichen in User-Daten */
function escHtml(str) {
  const d = document.createElement('div');
  d.textContent = String(str ?? '');
  return d.innerHTML;
}

// ──────────────────────────────────────────────
// HELPERS
// ──────────────────────────────────────────────
const fmt = (dateStr) => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  return d.toLocaleDateString('de-AT', { day: '2-digit', month: 'long', year: 'numeric' });
};

const catIcon = (cat) => {
  const icons = {
    'Ratgeber':      'fas fa-book-open',
    'Tipps & Tricks':'fas fa-lightbulb',
    'Nachhaltigkeit':'fas fa-leaf',
    'Aktuell':       'fas fa-bolt',
    'Produkt':       'fas fa-box',
    'Video':         'fas fa-video',
  };
  return icons[cat] || 'fas fa-newspaper';
};

const catClass = (cat) => {
  const classes = {
    'Ratgeber':      'cat-ratgeber',
    'Tipps & Tricks':'cat-tipps',
    'Nachhaltigkeit':'cat-nach',
    'Aktuell':       'cat-aktuell',
    'Produkt':       'cat-produkt',
    'Video':         'cat-video',
  };
  return classes[cat] || 'cat-ratgeber';
};

const slugify = (str) => (str || '').toLowerCase()
  .replace(/ä/g,'ae').replace(/ö/g,'oe').replace(/ü/g,'ue').replace(/ß/g,'ss')
  .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');

function articleUrl(article) {
  return `artikel.html?slug=${encodeURIComponent(article.slug || article.id)}`;
}

function getCachedArticles() {
  try {
    const cached = JSON.parse(localStorage.getItem(BLOG_CACHE_KEY) || 'null');
    if (Array.isArray(cached) && cached.length) return cached;
  } catch (_) {}
  if (Array.isArray(window.BLOG_FALLBACK_ARTICLES) && window.BLOG_FALLBACK_ARTICLES.length) {
    return window.BLOG_FALLBACK_ARTICLES;
  }
  return [];
}

function cacheArticles(articles) {
  try {
    localStorage.setItem(BLOG_CACHE_KEY, JSON.stringify(articles));
  } catch (_) {}
}

// ──────────────────────────────────────────────
// ARTIKEL-BILD MAP
// Slugs → relative Bildpfade (von blog/ aus)
// ──────────────────────────────────────────────
const ARTICLE_IMAGES = {
  'wie-nachhaltig-sind-holzpellets-wirklich': '../images/blog-fahrer-befüllung.jpg',
};

function getArticleImage(art) {
  // 1. Bild aus Datenbank-Feld (falls gesetzt)
  if (art.image && art.image.trim()) return art.image;
  // 2. Bild aus lokaler Map
  const slug = art.slug || art.id || '';
  if (ARTICLE_IMAGES[slug]) return ARTICLE_IMAGES[slug];
  return null;
}

// ──────────────────────────────────────────────
// VIDEO EMBED HELPER
// ──────────────────────────────────────────────
function getVideoEmbedUrl(url) {
  if (!url) return null;
  // YouTube
  let m = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([\w-]+)/);
  if (m) return `https://www.youtube.com/embed/${m[1]}`;
  // Instagram Reel / Post
  m = url.match(/instagram\.com\/(reel|p)\/([\w-]+)/);
  if (m) return `https://www.instagram.com/${m[1]}/${m[2]}/embed`;
  // TikTok
  m = url.match(/tiktok\.com\/@[\w.]+\/video\/(\d+)/);
  if (m) return `https://www.tiktok.com/embed/v2/${m[1]}`;
  return null;
}

// ──────────────────────────────────────────────
// ARTICLE CARD HTML
// ──────────────────────────────────────────────
function renderCard(art) {
  const cc   = catClass(art.category);
  const icon = catIcon(art.category);
  const img  = getArticleImage(art);
  const safeTitle = escHtml(art.title);
  const videoEmbed = art.category === 'Video' ? getVideoEmbedUrl(art.video_url) : null;

  let headerContent;
  if (videoEmbed) {
    headerContent = `<iframe src="${escHtml(videoEmbed)}" class="article-card-video" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>`;
  } else if (img) {
    headerContent = `<img src="${escHtml(img)}" alt="${safeTitle}" class="article-card-img" />`;
  } else {
    headerContent = `<i class="${icon} article-card-icon"></i>`;
  }

  const cardTag = videoEmbed ? 'div' : 'a';
  const cardHref = videoEmbed ? '' : ` href="${articleUrl(art)}"`;
  const cardAria = videoEmbed ? '' : ` aria-label="${safeTitle}"`;

  return `
    <${cardTag} class="article-card ${videoEmbed ? 'video-card' : ''}"${cardHref}${cardAria}>
      <div class="article-card-header ${cc} ${img || videoEmbed ? 'has-image' : ''}">
        <span class="article-card-cat">${escHtml(art.category || 'Allgemein')}</span>
        ${headerContent}
      </div>
      <div class="article-card-body">
        <div class="article-card-meta">
          <span><i class="fas fa-calendar-alt"></i> ${fmt(art.published_at)}</span>
          ${!videoEmbed ? `<span><i class="fas fa-clock"></i> ${art.reading_time || '–'} Min.</span>` : ''}
        </div>
        <h2 class="article-card-title">${safeTitle}</h2>
        <p class="article-card-teaser">${escHtml(art.teaser)}</p>
        <div class="article-card-footer">
          ${videoEmbed ? `<a href="${articleUrl(art)}" class="article-card-link">Zum Artikel <i class="fas fa-arrow-right"></i></a>` : `<span class="article-card-link">Weiterlesen <i class="fas fa-arrow-right"></i></span>`}
          <span class="article-card-meta">${(art.tags || []).slice(0,2).map(t => `<span class="tag-pill" style="padding:2px 8px;font-size:0.7rem">${escHtml(t)}</span>`).join('')}</span>
        </div>
      </div>
    </${cardTag}>`;
}

// ──────────────────────────────────────────────
// FEATURED CARD HTML
// ──────────────────────────────────────────────
function renderFeatured(art) {
  const cc   = catClass(art.category);
  const icon = catIcon(art.category);
  const img  = getArticleImage(art);
  const safeTitle = escHtml(art.title);
  const visualContent = img
    ? `<img src="${escHtml(img)}" alt="${safeTitle}" class="featured-art-img" />`
    : `<i class="${icon} featured-art-icon"></i><i class="${icon} featured-art-main-icon"></i>`;
  return `
    <a class="featured-article" href="${articleUrl(art)}">
      <div class="featured-art-visual ${cc} ${img ? 'has-image' : ''}">
        <span class="featured-art-cat">${escHtml(art.category)}</span>
        <span class="featured-badge"><i class="fas fa-star"></i> Empfohlen</span>
        ${visualContent}
      </div>
      <div class="featured-art-body">
        <div class="featured-art-reading"><i class="fas fa-clock"></i> ${art.reading_time || '–'} Min. Lesezeit · ${fmt(art.published_at)}</div>
        <h2 class="featured-art-title">${safeTitle}</h2>
        <p class="featured-art-teaser">${escHtml(art.teaser)}</p>
        <span class="featured-art-cta">Jetzt lesen <i class="fas fa-arrow-right"></i></span>
      </div>
    </a>`;
}

// ──────────────────────────────────────────────
// BLOG INDEX PAGE
// ──────────────────────────────────────────────
const isBlogIndex = !!document.getElementById('articlesGrid');

if (isBlogIndex) {
  let allArticles  = [];
  let currentCat   = 'alle';
  let searchQuery  = '';
  let visibleCount = 6;
  const PAGE_SIZE  = 6;

  const articlesGrid  = document.getElementById('articlesGrid');
  const featuredWrap  = document.getElementById('featuredArticle');
  const loadingEl     = document.getElementById('blogLoading');
  const noResultsEl   = document.getElementById('blogNoResults');
  const loadMoreWrap  = document.getElementById('loadMoreWrap');
  const loadMoreBtn   = document.getElementById('loadMoreBtn');
  const tagCloud      = document.getElementById('tagCloud');
  const blogSearch    = document.getElementById('blogSearch');
  const searchClear   = document.getElementById('blogSearchClear');

  // ── FETCH articles ──
  async function loadArticles() {
    try {
      const res = await fetch('../data/blog_articles.json');
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const json = await res.json();
      allArticles = (Array.isArray(json) ? json : []).filter(a => a.status !== 'draft').sort((a,b) =>
        new Date(b.published_at) - new Date(a.published_at)
      );
      cacheArticles(allArticles);
      renderAll();
      buildTagCloud();
    } catch(e) {
      console.error('Blog load error:', e);
      allArticles = getCachedArticles().filter(a => a.status !== 'draft').sort((a, b) => new Date(b.published_at) - new Date(a.published_at));
      if (allArticles.length) {
        renderAll();
        buildTagCloud();
      } else if (loadingEl) {
        loadingEl.innerHTML = '<p style="color:#c00"><i class="fas fa-exclamation-triangle"></i> Artikel konnten nicht geladen werden.</p>';
      }
    }
  }

  function getFiltered() {
    return allArticles.filter(a => {
      const catMatch = currentCat === 'alle' || a.category === currentCat;
      const q = searchQuery.toLowerCase();
      const textMatch = !q ||
        a.title.toLowerCase().includes(q) ||
        a.teaser.toLowerCase().includes(q) ||
        (a.tags || []).some(t => t.toLowerCase().includes(q));
      return catMatch && textMatch;
    });
  }

  function renderAll() {
    if (loadingEl) loadingEl.style.display = 'none';

    const filtered = getFiltered();

    if (filtered.length === 0) {
      if (featuredWrap) featuredWrap.style.display = 'none';
      articlesGrid.innerHTML = '';
      noResultsEl.style.display = 'block';
      loadMoreWrap.style.display = 'none';
      return;
    }

    noResultsEl.style.display = 'none';

    // Featured = first featured article (or first article)
    const featured = filtered[0];
    const rest     = filtered.filter(a => a.id !== featured.id);

    if (featuredWrap) {
      featuredWrap.style.display = 'block';
      featuredWrap.innerHTML = renderFeatured(featured);
    }

    const toShow = rest.slice(0, visibleCount);
    articlesGrid.innerHTML = toShow.map(renderCard).join('');

    // Load more
    if (rest.length > visibleCount) {
      loadMoreWrap.style.display = 'block';
      loadMoreBtn.textContent = `Weitere ${Math.min(PAGE_SIZE, rest.length - visibleCount)} Artikel laden`;
    } else {
      loadMoreWrap.style.display = 'none';
    }
  }

  function buildTagCloud() {
    if (!tagCloud) return;
    const tagSet = new Set();
    allArticles.forEach(a => (a.tags || []).forEach(t => tagSet.add(t)));
    tagCloud.innerHTML = [...tagSet].map(t =>
      `<span class="tag-pill" data-tag="${escHtml(t)}">${escHtml(t)}</span>`
    ).join('');
    tagCloud.querySelectorAll('.tag-pill').forEach(pill => {
      pill.addEventListener('click', () => {
        blogSearch.value = pill.dataset.tag;
        searchQuery = pill.dataset.tag;
        if (searchClear) searchClear.style.display = 'inline-flex';
        visibleCount = PAGE_SIZE;
        renderAll();
      });
    });
  }

  // ── CATEGORY FILTER ──
  document.querySelectorAll('.blog-filter-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.blog-filter-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      currentCat = btn.dataset.cat;
      visibleCount = PAGE_SIZE;
      renderAll();
    });
  });

  // ── SEARCH ──
  if (blogSearch) {
    blogSearch.addEventListener('input', () => {
      searchQuery = blogSearch.value.trim();
      if (searchClear) searchClear.style.display = searchQuery ? 'inline-flex' : 'none';
      visibleCount = PAGE_SIZE;
      renderAll();
    });
  }
  if (searchClear) {
    searchClear.addEventListener('click', () => {
      blogSearch.value = '';
      searchQuery = '';
      searchClear.style.display = 'none';
      visibleCount = PAGE_SIZE;
      renderAll();
    });
  }

  // ── LOAD MORE ──
  if (loadMoreBtn) {
    loadMoreBtn.addEventListener('click', () => {
      visibleCount += PAGE_SIZE;
      renderAll();
    });
  }

  // ── RESET (called from HTML) ──
  window.resetFilters = () => {
    currentCat  = 'alle';
    searchQuery = '';
    if (blogSearch) blogSearch.value = '';
    if (searchClear) searchClear.style.display = 'none';
    visibleCount = PAGE_SIZE;
    document.querySelectorAll('.blog-filter-btn').forEach(b => b.classList.remove('active'));
    const allBtn = document.querySelector('.blog-filter-btn[data-cat="alle"]');
    if (allBtn) allBtn.classList.add('active');
    renderAll();
  };

  loadArticles();
}

// ──────────────────────────────────────────────
// ARTICLE DETAIL PAGE
// ──────────────────────────────────────────────
const isArticlePage = !!document.getElementById('articleBody');

if (isArticlePage) {
  const params  = new URLSearchParams(window.location.search);
  const slugParam = params.get('slug') || params.get('id') || '';

  async function loadArticle() {
    try {
      // Load prices for placeholder replacement
      try {
        const pRes = await fetch('../data/preise.json');
        if (pRes.ok) window._spPreise = await pRes.json();
      } catch (_) {}

      let all = [];
      try {
        const res  = await fetch('../data/blog_articles.json');
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const json = await res.json();
        all = Array.isArray(json) ? json.filter(a => a.status !== 'draft') : []; 
        cacheArticles(all);
      } catch (_) {
        all = getCachedArticles();
      }
      const art  = all.find(a => a.slug === slugParam || a.id === slugParam);

      document.getElementById('articleLoading').style.display = 'none';

      if (!art) {
        document.getElementById('articleNotFound').style.display = 'flex';
        return;
      }

      renderArticle(art, all);

    } catch(e) {
      document.getElementById('articleLoading').innerHTML =
        '<div class="container"><p style="color:#c00"><i class="fas fa-exclamation-triangle"></i> Fehler beim Laden.</p></div>';
    }
  }

  function renderArticle(art, allArticles) {
    document.getElementById('articleContent').style.display = 'block';

    // Meta tags and structured data
    const siteUrl = 'https://www.steirerpellets.at';
    const articleUrl = `${siteUrl}/blog/artikel.html?slug=${encodeURIComponent(art.slug)}`;
    const articleImage = getArticleImage(art)
      ? `${siteUrl}/${getArticleImage(art).replace(/^\.\.\//, '')}`
      : `${siteUrl}/images/blog-fahrer-befüllung.jpg`;
    const articleTitle = `${art.title} | Holzpellets News von Steirer Pellets`;
    const articleDesc = art.teaser || 'Ratgeber, Tipps und Kaufwissen rund um Holzpellets, Lagerung, Lieferplanung und Heizen mit Pellets.';

    document.getElementById('articlePageTitle').textContent = articleTitle;
    document.getElementById('articlePageDesc').setAttribute('content', articleDesc);

    const canonical = document.getElementById('articleCanonical');
    if (canonical) canonical.setAttribute('href', articleUrl);
    const ogTitle = document.getElementById('articleOgTitle');
    if (ogTitle) ogTitle.setAttribute('content', articleTitle);
    const ogDesc = document.getElementById('articleOgDesc');
    if (ogDesc) ogDesc.setAttribute('content', articleDesc);
    const ogUrl = document.getElementById('articleOgUrl');
    if (ogUrl) ogUrl.setAttribute('content', articleUrl);
    const ogImage = document.getElementById('articleOgImage');
    if (ogImage) ogImage.setAttribute('content', articleImage);
    const twitterTitle = document.getElementById('articleTwitterTitle');
    if (twitterTitle) twitterTitle.setAttribute('content', articleTitle);
    const twitterDesc = document.getElementById('articleTwitterDesc');
    if (twitterDesc) twitterDesc.setAttribute('content', articleDesc);
    const twitterImage = document.getElementById('articleTwitterImage');
    if (twitterImage) twitterImage.setAttribute('content', articleImage);

    const schema = document.getElementById('articleSchema');
    if (schema) {
      schema.textContent = JSON.stringify({
        '@context': 'https://schema.org',
        '@type': 'Article',
        headline: art.title,
        description: articleDesc,
        image: [articleImage],
        mainEntityOfPage: articleUrl,
        datePublished: art.published_at || undefined,
        author: {
          '@type': 'Organization',
          name: art.author || 'Steirer Pellets Team',
        },
        publisher: {
          '@type': 'Organization',
          name: 'Steirer Pellets GmbH',
          url: `${siteUrl}/`,
        }
      });
    }

    // Hero
    document.getElementById('articleBreadcrumbTitle').textContent = art.title;
    document.getElementById('articleCatBadge').innerHTML = `<i class="${catIcon(art.category)}"></i> ${art.category}`;
    document.getElementById('articleReadingTime').innerHTML = `<i class="fas fa-clock"></i> ${art.reading_time || '–'} Min. Lesezeit`;
    document.getElementById('articleMainTitle').textContent = art.title;
    document.getElementById('articleSub').textContent = art.teaser;
    document.getElementById('articleAuthor').textContent = art.author || 'Steirer Pellets Team';
    document.getElementById('articleDate').textContent = fmt(art.published_at);

    // Apply category background to hero
    const hero = document.getElementById('articleHero');
    if (hero) {
      const cc = catClass(art.category);
      hero.classList.add(cc + '-hero');
    }

    // Body
    const body = document.getElementById('articleBody');

    // Aufmacherbild vor dem Text (falls vorhanden)
    const heroImg = getArticleImage(art);
    if (heroImg) {
      const imgWrap = document.getElementById('articleHeroImage');
      if (imgWrap) {
        imgWrap.innerHTML = `<img src="${escHtml(heroImg)}" alt="${escHtml(art.title)}" class="article-hero-img" />`;
        imgWrap.style.display = 'block';
      }
    }

    let articleHtml = art.content || '<p>Kein Inhalt verfügbar.</p>';

    // Replace price placeholders with current values
    if (window._spPreise) {
      articleHtml = articleHtml
        .replace(/\{\{preis_gross\}\}/g, window._spPreise.preis_gross + ',– €')
        .replace(/\{\{preis_klein\}\}/g, window._spPreise.preis_klein + ',– €')
        .replace(/\{\{abschlauch\}\}/g, window._spPreise.abschlauch + ',– €');
    }

    body.innerHTML = articleHtml;

    // Activate social media embeds (Instagram, Facebook, TikTok)
    body.querySelectorAll('.social-embed').forEach(el => {
      const scripts = el.querySelectorAll('script');
      scripts.forEach(orig => {
        const s = document.createElement('script');
        if (orig.src) s.src = orig.src;
        else s.textContent = orig.textContent;
        orig.replaceWith(s);
      });
    });
    if (body.querySelector('.instagram-media') && !document.querySelector('script[src*="instagram.com/embed"]')) {
      const s = document.createElement('script'); s.src = 'https://www.instagram.com/embed.js'; s.async = true; document.body.appendChild(s);
    } else if (window.instgrm) { window.instgrm.Embeds.process(); }
    if (body.querySelector('.fb-post, .fb-video') && !document.querySelector('script[src*="connect.facebook"]')) {
      const s = document.createElement('script'); s.src = 'https://connect.facebook.net/de_DE/sdk.js#xfbml=1&version=v18.0'; s.async = true; s.defer = true; document.body.appendChild(s);
    } else if (window.FB) { window.FB.XFBML.parse(body); }

    // Build TOC from h2s
    buildToc(body);

    // Tags
    const tagsBox  = document.getElementById('articleTags');
    const tags = art.tags || [];
    if (tags.length && tagsBox) {
      tagsBox.innerHTML = tags.map(t =>
        `<span class="tag-pill">${escHtml(t)}</span>`
      ).join('');
    } else {
      document.getElementById('articleTagsBox').style.display = 'none';
    }

    // Share buttons
    const pageUrl = window.location.href;
    const shareText = encodeURIComponent(art.title + ' – Steirer Pellets News');
    document.getElementById('shareWhatsapp').addEventListener('click', () => {
      window.open(`https://wa.me/?text=${shareText}%20${encodeURIComponent(pageUrl)}`, '_blank');
    });
    document.getElementById('shareFacebook').addEventListener('click', () => {
      window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(pageUrl)}`, '_blank');
    });
    document.getElementById('shareCopy').addEventListener('click', () => {
      navigator.clipboard.writeText(pageUrl).then(() => {
        document.getElementById('shareCopySuccess').style.display = 'flex';
        setTimeout(() => { document.getElementById('shareCopySuccess').style.display = 'none'; }, 2000);
      });
    });

    // Related articles (same category, exclude self)
    const related = allArticles.filter(a =>
      a.id !== art.id && (a.category === art.category || (art.tags || []).some(t => (a.tags || []).includes(t)))
    ).slice(0, 3);
    const relatedGrid = document.getElementById('relatedArticles');
    if (relatedGrid) {
      if (related.length > 0) {
        relatedGrid.innerHTML = related.map(renderCard).join('');
      } else {
        // Fallback: show latest articles
        const latest = allArticles.filter(a => a.id !== art.id).slice(0, 3);
        relatedGrid.innerHTML = latest.map(renderCard).join('');
      }
    }
  }

  function buildToc(bodyEl) {
    const toc   = document.getElementById('articleToc');
    if (!toc) return;
    const h2s   = bodyEl.querySelectorAll('h2');
    if (!h2s.length) { toc.closest('.toc-box').style.display = 'none'; return; }
    const links = [];
    h2s.forEach((h, i) => {
      const anchorId = `toc-${i}`;
      h.id = anchorId;
      const link = document.createElement('a');
      link.href      = `#${anchorId}`;
      link.className = 'toc-link';
      link.textContent = h.textContent;
      toc.appendChild(link);
      links.push(link);
    });

    // Highlight active TOC link on scroll
    const tocObs = new IntersectionObserver(entries => {
      entries.forEach(e => {
        if (e.isIntersecting) {
          links.forEach(l => l.classList.remove('active'));
          const active = links.find(l => l.getAttribute('href') === `#${e.target.id}`);
          if (active) active.classList.add('active');
        }
      });
    }, { threshold: 0.6 });
    h2s.forEach(h => tocObs.observe(h));
  }

  loadArticle();
}

// ──────────────────────────────────────────────
// SHARED: Navbar, back-to-top, hamburger
// ──────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  const navbar    = document.getElementById('navbar');
  const backToTop = document.getElementById('backToTop');
  const hamburger = document.getElementById('hamburger');
  const navLinks  = document.getElementById('navLinks');

  window.addEventListener('scroll', () => {
    if (navbar)    navbar.classList.toggle('scrolled', window.scrollY > 60);
    if (backToTop) backToTop.classList.toggle('visible', window.scrollY > 400);
  });
  if (backToTop) backToTop.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));

  if (hamburger && navLinks) {
    const syncBodyLock = (open) => {
      document.body.style.overflow = open ? 'hidden' : '';
    };
    const closeNav = () => {
      navLinks.classList.remove('open');
      syncBodyLock(false);
      hamburger.setAttribute('aria-expanded', 'false');
      hamburger.querySelectorAll('span').forEach(b => { b.style.transform = ''; b.style.opacity = ''; });
    };
    hamburger.addEventListener('click', () => {
      const open = navLinks.classList.toggle('open');
      syncBodyLock(open);
      hamburger.setAttribute('aria-expanded', open ? 'true' : 'false');
      const bars = hamburger.querySelectorAll('span');
      if (open) {
        bars[0].style.transform = 'rotate(45deg) translate(5px,5px)';
        bars[1].style.opacity   = '0';
        bars[2].style.transform = 'rotate(-45deg) translate(5px,-5px)';
      } else {
        bars.forEach(b => { b.style.transform = ''; b.style.opacity = ''; });
      }
    });
    navLinks.querySelectorAll('a').forEach(l => l.addEventListener('click', () => {
      const href = l.getAttribute('href') || '';
      closeNav();
      if (href.startsWith('#')) {
        const target = document.querySelector(href);
        if (target) {
          setTimeout(() => target.scrollIntoView({ behavior: 'smooth', block: 'start' }), 30);
        }
      }
    }));
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && navLinks.classList.contains('open')) closeNav();
    });
  }

  // Newsletter form (sidebar)
  const nlForm    = document.getElementById('nlForm');
  const nlSuccess = document.getElementById('nlSuccess');
  if (nlForm) {
    nlForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const email = document.getElementById('nlEmail').value;
      let ok = false;
      try {
        const response = await fetch('../tables/newsletter_subscribers', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ email, subscribed_at: new Date().toISOString() })
        });
        ok = response.ok;
      } catch(_) {}
      if (!ok) return;
      nlForm.style.display     = 'none';
      if (nlSuccess) nlSuccess.style.display = 'flex';
    });
  }
});
