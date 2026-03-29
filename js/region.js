/* =============================================
   REGION LANDING PAGE – JavaScript
   Liest den slug aus der URL und füllt die Seite
   ============================================= */

/** Escapet HTML-Zeichen in User-Daten */
function escHtml(str) {
  const d = document.createElement('div');
  d.textContent = String(str ?? '');
  return d.innerHTML;
}

document.addEventListener('DOMContentLoaded', () => {
  const LEGAL_LINKS = {
    impressum: '../impressum.html',
    datenschutz: '../datenschutz.html',
    agb: '../agb.html',
  };

  // ===== Slug aus URL auslesen =====
  // Unterstützt:  region/graz.html  ODER  region/index.html?region=graz
  let slug = '';

  // 1) Dateiname aus Pfad (graz.html → graz)
  const pathParts = window.location.pathname.split('/');
  const filename  = pathParts[pathParts.length - 1].replace('.html', '');
  if (filename && filename !== 'index' && REGION_CONTENT[filename]) {
    slug = filename;
  }

  // 2) Query-Parameter ?region=graz
  if (!slug) {
    const params = new URLSearchParams(window.location.search);
    const qSlug  = params.get('region');
    if (qSlug && REGION_CONTENT[qSlug]) slug = qSlug;
  }

  // 3) Fallback: erste Region
  if (!slug) slug = Object.keys(REGION_CONTENT)[0];

  const content = REGION_CONTENT[slug];
  if (!content) return;

  // Falsche Alt-Links auf die aktuelle Bestellsektion korrigieren.
  document.querySelectorAll('a[href="../index.html#anfrage"]').forEach(link => {
    link.setAttribute('href', '../index.html#bestellung');
  });
  document.querySelectorAll('a[href="#"]').forEach(link => {
    const label = (link.textContent || '').trim().toLowerCase();
    if (label.includes('impressum')) link.setAttribute('href', LEGAL_LINKS.impressum);
    if (label.includes('datenschutz')) link.setAttribute('href', LEGAL_LINKS.datenschutz);
    if (label === 'agb') link.setAttribute('href', LEGAL_LINKS.agb);
  });

  // ===== Meta-Tags setzen =====
  document.title = `${content.title} | Steirer Pellets`;
  const descEl = document.querySelector('meta[name="description"]');
  if (descEl) descEl.setAttribute('content', content.description);
  const canonicalEl = document.getElementById('pageCanonical');
  const pageUrl = `https://www.steirerpellets.at/region/index.html?region=${encodeURIComponent(slug)}`;
  if (canonicalEl) canonicalEl.setAttribute('href', pageUrl);
  const ogTitle = document.getElementById('pageOgTitle');
  if (ogTitle) ogTitle.setAttribute('content', `${content.title} | Steirer Pellets`);
  const ogDesc = document.getElementById('pageOgDesc');
  if (ogDesc) ogDesc.setAttribute('content', content.description);
  const ogUrl = document.getElementById('pageOgUrl');
  if (ogUrl) ogUrl.setAttribute('content', pageUrl);
  const twitterTitle = document.getElementById('pageTwitterTitle');
  if (twitterTitle) twitterTitle.setAttribute('content', `${content.title} | Steirer Pellets`);
  const twitterDesc = document.getElementById('pageTwitterDesc');
  if (twitterDesc) twitterDesc.setAttribute('content', content.description);

  // ===== Hero füllen =====
  setText('regionName',       content.name);
  setText('breadcrumbRegion', content.name);
  setText('regionTitle',      content.title);
  setText('regionTagline',    content.tagline);
  setText('regionHeadline',   `Holzpellets lose in ${content.name}`);
  setText('regionIntro',      content.intro);
  setText('regionHighlight',  content.highlight);
  setText('whyRegion',        content.name);
  setText('sidebarRegion',    content.name);

  // Facts
  const factsEl = document.getElementById('regionFacts');
  if (factsEl && content.facts) {
    factsEl.innerHTML = content.facts.map(f =>
      `<span class="region-fact"><i class="fas fa-check"></i>${escHtml(f)}</span>`
    ).join('');
  }

  // PLZ Tags
  const plzTagsEl = document.getElementById('plzTags');
  if (plzTagsEl && content.plzList) {
    plzTagsEl.innerHTML = content.plzList.map(plz => {
      const plzData = PLZ_DATA[plz];
      const ortName = plzData ? plzData.ort : '';
      return `<span class="plz-tag"><span>${escHtml(plz)}</span>${ortName ? ' · ' + escHtml(ortName) : ''}</span>`;
    }).join('');
  }

  // ===== Alle Regionen Grid =====
  const allGrid = document.getElementById('allRegionsGrid');
  if (allGrid) {
    allGrid.innerHTML = Object.entries(REGION_CONTENT).map(([s, r]) => {
      const examplePlz = r.plzList?.[0] || '';
      const isActive   = s === slug ? ' active' : '';
      return `
        <a href="${escHtml(s)}.html" class="region-card${isActive}">
          <i class="fas fa-map-marker-alt"></i>
          <span class="region-card-name">${escHtml(r.name)}</span>
          ${examplePlz ? `<span class="region-card-plz">z.B. ${escHtml(examplePlz)}</span>` : ''}
        </a>
      `;
    }).join('');
  }

  // ===== Sidebar Slider =====
  const sidebarSlider = document.getElementById('sidebarSlider');
  const sidebarMenge  = document.getElementById('sidebarMenge');
  const sidebarDisplay = document.getElementById('sidebarMengeDisplay');
  const sidebarFill   = document.getElementById('sidebarSliderFill');
  const sidebarHint   = document.getElementById('sidebarHint');

  function getMengeHint(t) {
    if (t <= 3)  return `${t} t · kleiner Pellettank`;
    if (t <= 8)  return `${t} t · Jahresbedarf Reihenhaus`;
    if (t <= 12) return `${t} t · Jahresbedarf Einfamilienhaus`;
    if (t <= 20) return `${t} t · großes Haus / Gewerbe`;
    return `${t} t · Großabnehmer`;
  }

  function updateSidebarSlider(val) {
    const t   = parseInt(val, 10);
    const min = parseFloat(sidebarSlider.min);
    const max = parseFloat(sidebarSlider.max);
    const pct = ((t - min) / (max - min)) * 100;

    const valEl = document.querySelector('#sidebarMengeDisplay .menge-val');
    if (valEl)        valEl.textContent       = t;
    if (sidebarMenge) sidebarMenge.value      = t;
    if (sidebarFill)  sidebarFill.style.width = `${pct}%`;
    if (sidebarHint)  sidebarHint.textContent = getMengeHint(t);
  }

  if (sidebarSlider) {
    sidebarSlider.addEventListener('input', () => updateSidebarSlider(sidebarSlider.value));
    updateSidebarSlider(sidebarSlider.value);
  }

  // ===== PLZ Vorausfüllen (aus URL-Param) =====
  const urlParams = new URLSearchParams(window.location.search);
  const prefillPlz = urlParams.get('plz');
  if (prefillPlz) {
    const sbPlzInput = document.getElementById('sbPlz');
    if (sbPlzInput) sbPlzInput.value = prefillPlz;
  }

  // ===== Sidebar Form Submit =====
  const regionForm = document.getElementById('regionForm');
  const sbSubmit   = document.getElementById('sbSubmitBtn');
  const sbSuccess  = document.getElementById('sidebarSuccess');

  if (regionForm) {
    regionForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const existingError = document.getElementById('sidebarError');
      if (existingError) existingError.remove();
      sbSubmit.innerHTML  = '<i class="fas fa-spinner fa-spin"></i> Senden...';
      sbSubmit.disabled   = true;

      const data = {
        region:  slug,
        menge:   sidebarMenge?.value,
        plz:     document.getElementById('sbPlz')?.value,
        name:    document.getElementById('sbName')?.value,
        telefon: document.getElementById('sbTel')?.value,
        email:   document.getElementById('sbEmail')?.value,
        sent_at: new Date().toISOString(),
      };

      let sent = false;

      const DEFAULT_WEB3FORMS_KEY = '8b18adc8-a507-499e-95a0-54c1485b341d';
      const WEB3FORMS_KEY = localStorage.getItem('sp_w3f_key_active') || DEFAULT_WEB3FORMS_KEY || '';
      if (WEB3FORMS_KEY && WEB3FORMS_KEY !== 'PENDING_CONFIRMATION') {
        try {
          const response = await fetch('https://api.web3forms.com/submit', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({
              access_key: WEB3FORMS_KEY,
              subject: `Region-Anfrage: ${content.name} – ${data.name}`,
              from_name: 'Steirer Pellets Regionsseite',
              replyto: data.email,
              region: content.name,
              menge: `${data.menge} Tonnen`,
              plz: data.plz,
              name: data.name,
              telefon: data.telefon,
              email: data.email,
              sent_at: data.sent_at,
              message: [
                `Neue Regionsanfrage für ${content.name}`,
                `Menge: ${data.menge} Tonnen`,
                `PLZ: ${data.plz}`,
                `Name: ${data.name}`,
                `Telefon: ${data.telefon}`,
                `E-Mail: ${data.email}`,
              ].join('\n')
            })
          });
          const json = await response.json();
          sent = !!json.success;
        } catch (_) {}
      }

      if (!sent) {
        try {
          const response = await fetch('../tables/kontaktanfragen', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(data),
          });
          sent = response.ok;
        } catch (_) {}
      }

      if (!sent) {
        const error = document.createElement('div');
        error.id = 'sidebarError';
        error.className = 'form-message form-message-error';
        error.setAttribute('role', 'alert');
        error.setAttribute('aria-live', 'assertive');
        error.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Die Anfrage konnte gerade nicht übertragen werden. Bitte versuchen Sie es erneut oder rufen Sie uns an.';
        sbSubmit.insertAdjacentElement('afterend', error);
        sbSubmit.innerHTML = '<i class="fas fa-paper-plane"></i> Anfrage senden';
        sbSubmit.disabled = false;
        return;
      }

      // Erfolg
      sbSubmit.style.display = 'none';
      if (sbSuccess) sbSuccess.classList.add('show');

      setTimeout(() => {
        regionForm.reset();
        updateSidebarSlider(10);
        if (sidebarSlider) sidebarSlider.value = 10;
        sbSubmit.style.display = '';
        sbSubmit.innerHTML = '<i class="fas fa-paper-plane"></i> Anfrage senden';
        sbSubmit.disabled = false;
        if (sbSuccess) sbSuccess.classList.remove('show');
      }, 10000);
    });
  }

  // ===== Back to Top =====
  const btt = document.getElementById('backToTop');
  window.addEventListener('scroll', () => {
    btt?.classList.toggle('visible', window.scrollY > 400);
  });
  btt?.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));

  // ===== Hamburger =====
  const hamburger = document.getElementById('hamburger');
  const navLinks  = document.getElementById('navLinks');
  const closeNav = () => {
    navLinks?.classList.remove('open');
    hamburger?.setAttribute('aria-expanded', 'false');
    hamburger?.querySelectorAll('span').forEach(b => { b.style.transform = ''; b.style.opacity = ''; });
  };
  hamburger?.addEventListener('click', () => {
    const open = navLinks.classList.toggle('open');
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
  navLinks?.querySelectorAll('a').forEach(link => link.addEventListener('click', closeNav));
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && navLinks?.classList.contains('open')) closeNav();
  });

  // ===== Helpers =====
  function setText(id, text) {
    const el = document.getElementById(id);
    if (el && text !== undefined && text !== null) el.textContent = text;
  }
  function setHtml(id, html) {
    const el = document.getElementById(id);
    if (el) el.innerHTML = html;
  }

  const styleEl = document.createElement('style');
  styleEl.textContent = `
    .form-message { border-radius: 10px; padding: 12px 14px; font-size: 0.875rem; display: flex; align-items: center; gap: 10px; margin-top: 12px; }
    .form-message-error { background: #fff1f2; border: 1px solid #fecdd3; color: #9f1239; }
  `;
  document.head.appendChild(styleEl);

  console.log(`🌲 Region-Seite geladen: ${slug} (${content.name})`);

  // ===== Preise dynamisch laden =====
  async function loadPreise() {
    try {
      const res = await fetch('../data/preise.json');
      const d   = await res.json();
      if (!d) return;

      if (d.preis_gross) {
        document.querySelectorAll('.pt-price-gross').forEach(el => el.textContent = d.preis_gross);
        document.querySelectorAll('.price-big-gross').forEach(el => el.textContent = d.preis_gross);
      }
      if (d.preis_klein) {
        document.querySelectorAll('.price-big-klein').forEach(el => el.textContent = d.preis_klein);
      }
      if (d.abschlauch) {
        document.querySelectorAll('.price-abschlauch-val').forEach(el => el.textContent = d.abschlauch);
      }

      // Cache in localStorage
      try { localStorage.setItem('sp_preise_cache', JSON.stringify(d)); } catch(_) {}

      console.log(`💶 Region-Preise geladen: ${d.preis_gross} €/t`);
    } catch (_) {
      // Fallback: aus Cache laden
      try {
        const cached = JSON.parse(localStorage.getItem('sp_preise_cache'));
        if (cached) {
          if (cached.preis_gross) {
            document.querySelectorAll('.pt-price-gross').forEach(el => el.textContent = cached.preis_gross);
            document.querySelectorAll('.price-big-gross').forEach(el => el.textContent = cached.preis_gross);
          }
          if (cached.preis_klein) {
            document.querySelectorAll('.price-big-klein').forEach(el => el.textContent = cached.preis_klein);
          }
          if (cached.abschlauch) {
            document.querySelectorAll('.price-abschlauch-val').forEach(el => el.textContent = cached.abschlauch);
          }
          console.log('💶 Region-Preise aus Cache geladen');
        }
      } catch(_) {}
    }
  }

  loadPreise();
});
