/* =============================================
   STEIRER PELLETS – JavaScript v5
   3-Schritt-Bestellformular · Slider · PLZ-Routing
   Deklarationen ALLE oben, keine Reihenfolge-Bugs
   ============================================= */

'use strict';

document.addEventListener('DOMContentLoaded', () => {

  // ══════════════════════════════════════════════
  // ALLE DOM-REFERENZEN – oben deklariert
  // ══════════════════════════════════════════════

  // Navbar
  const navbar    = document.getElementById('navbar');
  const backToTop = document.getElementById('backToTop');

  // Hamburger
  const hamburger = document.getElementById('hamburger');
  const navLinks  = document.getElementById('navLinks');

  // Particles
  const pCont = document.getElementById('particles');

  // Tank-Fill
  const fillBar = document.getElementById('fillBar');

  // Formular-Slider
  const slider        = document.getElementById('mengeSlider');
  const mengeInput    = document.getElementById('mengeInput');
  const mengeDisplay  = document.getElementById('mengeDisplay');
  const sliderFill    = document.getElementById('sliderFill');
  const mengeHintText = document.getElementById('mengeHintText');
  const mengeMarks    = document.getElementById('mengeMarks');

  // Preisberechnung-Felder (Formular)
  const pcPreisProT   = document.getElementById('pcPreisProT');
  const pcTonnen      = document.getElementById('pcTonnen');
  const pcPelletTotal = document.getElementById('pcPelletTotal');
  const pcTotal       = document.getElementById('pcTotal');

  // Quick-Rechner Felder
  const qcSlider  = document.getElementById('qcSlider');
  const qcDisplay = document.getElementById('qcDisplay');
  const qcFill    = document.getElementById('qcFill');
  const qcPreisT  = document.getElementById('qcPreisT');
  const qcT2      = document.getElementById('qcT2');
  const qcPellets = document.getElementById('qcPellets');
  const qcTotal   = document.getElementById('qcTotal');
  const qcMarks   = document.getElementById('qcMarks');

  // KW-Picker
  const kwPrev   = document.getElementById('kwPrev');
  const kwNext   = document.getElementById('kwNext');
  const kwNum    = document.getElementById('kwNum');
  const kwYear   = document.getElementById('kwYear');
  const kwDates  = document.getElementById('kwDates');
  const kwHint   = document.getElementById('kwHint');
  const lieferkw = document.getElementById('lieferkw');

  // PLZ
  const plzInput    = document.getElementById('plz');
  const ortInput    = document.getElementById('ort');
  const plzStatus   = document.getElementById('plzStatus');
  const plzDropdown = document.getElementById('plzDropdown');
  const plzInfo     = document.getElementById('plzInfo');

  // Formular-Schritte
  const step1       = document.getElementById('step1');
  const step2       = document.getElementById('step2');
  const step3       = document.getElementById('step3');
  const stepSuccess = document.getElementById('stepSuccess');
  const progressBar = document.getElementById('progressBar');
  const dot1        = document.getElementById('dot1');
  const dot2        = document.getElementById('dot2');
  const dot3        = document.getElementById('dot3');
  const orderForm   = document.getElementById('orderForm');
  const nextBtn1    = document.getElementById('nextBtn1');
  const nextBtn2    = document.getElementById('nextBtn2');
  const backBtn1    = document.getElementById('backBtn1');
  const backBtn2    = document.getElementById('backBtn2');

  // ══════════════════════════════════════════════
  // PREISE – werden aus DB geladen, Fallback statisch
  // ══════════════════════════════════════════════
  let PREIS_GROSS = 398;   // € / t ab 4 t
  let PREIS_KLEIN = 418;   // € / t unter 4 t
  let ABSCHLAUCH  = 58;    // € einmalig
  const SLIDER_MARKS_VALUES = [2, 5, 10, 15, 20, 25];
  const PREIS_CACHE_KEY = 'sp_preise_cache';

  // ══════════════════════════════════════════════
  // HILFSFUNKTIONEN
  // ══════════════════════════════════════════════

  function calcPrice(t) {
    const ton     = parseFloat(t);
    const preis   = ton >= 4 ? PREIS_GROSS : PREIS_KLEIN;
    const pellets = ton * preis;
    const gesamt  = pellets + ABSCHLAUCH;
    return { ton, preis, pellets, gesamt };
  }

  function fmtEur(n) {
    return n.toLocaleString('de-AT', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) + ' €';
  }

  function getRegionUrl(slugOrPlz) {
    if (typeof getLandingUrl === 'function') return getLandingUrl(slugOrPlz);
    return `region/index.html?region=${encodeURIComponent(slugOrPlz)}`;
  }

  function setOrderError(message) {
    const errorBox = document.getElementById('orderError');
    if (!errorBox) return;
    if (!message) {
      errorBox.hidden = true;
      errorBox.textContent = '';
      return;
    }
    errorBox.hidden = false;
    errorBox.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${message}`;
  }

  function getMengeHint(t) {
    const ton = parseFloat(t);
    if (ton <= 2)  return '2 t · Mindestbestellung';
    if (ton <= 4)  return `${ton} t · ideal für Wohnung / kleines Haus`;
    if (ton <= 6)  return `${ton} t · Jahresbedarf Einfamilienhaus`;
    if (ton <= 10) return `${ton} t · großes Einfamilienhaus`;
    if (ton <= 16) return `${ton} t · großes Haus oder Gewerbe`;
    return `${ton} t · Großabnehmer / Gewerbe`;
  }

  function updateSliderFill(sliderEl, fillEl) {
    if (!sliderEl || !fillEl) return;
    const min = parseFloat(sliderEl.min) || 2;
    const max = parseFloat(sliderEl.max) || 25;
    const val = parseFloat(sliderEl.value);
    const pct = ((val - min) / (max - min)) * 100;
    fillEl.style.width = pct + '%';
  }

  /**
   * Skalen-Marks dynamisch aufbauen
   * Jede Marke wird mit left-% positioniert,
   * damit die Zahl exakt unter dem Thumb-Wert sitzt.
   */
  function buildMarks(sliderEl, marksEl) {
    if (!sliderEl || !marksEl) return;
    const min = parseFloat(sliderEl.min) || 2;
    const max = parseFloat(sliderEl.max) || 25;
    marksEl.innerHTML = '';
    SLIDER_MARKS_VALUES.forEach(v => {
      const pct  = ((v - min) / (max - min)) * 100;
      const span = document.createElement('span');
      span.textContent   = v;
      span.style.left    = pct + '%';
      span.dataset.value = v;
      marksEl.appendChild(span);
    });
  }

  /**
   * Aktive Mark hervorheben
   */
  function highlightMark(marksEl, val) {
    if (!marksEl) return;
    const t = parseInt(val, 10);
    // Nächste Marke zum aktuellen Wert
    let closest = SLIDER_MARKS_VALUES.reduce((prev, curr) =>
      Math.abs(curr - t) < Math.abs(prev - t) ? curr : prev
    );
    marksEl.querySelectorAll('span').forEach(s => {
      s.classList.toggle('active', parseInt(s.dataset.value, 10) === closest);
    });
  }

  // ══════════════════════════════════════════════
  // NAVBAR / HAMBURGER / PARTICLES
  // ══════════════════════════════════════════════

  if (navbar) {
    window.addEventListener('scroll', () => {
      navbar.classList.toggle('scrolled', window.scrollY > 60);
      if (backToTop) backToTop.classList.toggle('visible', window.scrollY > 400);
    });
  }
  if (backToTop) backToTop.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));

  if (hamburger && navLinks) {
    const closeNav = () => {
      navLinks.classList.remove('open');
      hamburger.setAttribute('aria-expanded', 'false');
      hamburger.querySelectorAll('span').forEach(b => { b.style.transform = ''; b.style.opacity = ''; });
    };
    hamburger.addEventListener('click', () => {
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
    navLinks.querySelectorAll('a').forEach(l => l.addEventListener('click', closeNav));
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && navLinks.classList.contains('open')) closeNav();
    });
  }

  if (pCont) {
    for (let i = 0; i < 20; i++) {
      const p    = document.createElement('div');
      p.classList.add('particle');
      const size = Math.random() * 5 + 2;
      Object.assign(p.style, {
        width:             `${size}px`,
        height:            `${size}px`,
        left:              `${Math.random() * 100}%`,
        bottom:            '-10px',
        opacity:           Math.random() * 0.45 + 0.1,
        animationDuration: `${Math.random() * 12 + 10}s`,
        animationDelay:    `${Math.random() * 12}s`,
      });
      pCont.appendChild(p);
    }
  }

  // ── SCROLL ANIMATIONS ──
  const animEls = document.querySelectorAll(
    '.feature-card, .prod-badge, .info-point, .delivery-step, .delivery-info-item, ' +
    '.testimonial-card, .sustain-point, .trust-item, .contact-item'
  );
  animEls.forEach((el, i) => {
    el.classList.add('animate-in');
    el.style.transitionDelay = `${(i % 4) * 0.08}s`;
  });
  const sectionHeaders = document.querySelectorAll(
    '.section-header, .sustainability-text, .sustainability-visual, ' +
    '.produkt-visual, .produkt-info, .contact-info, .contact-cta-box'
  );
  sectionHeaders.forEach(el => el.classList.add('animate-in'));

  const obs = new IntersectionObserver(entries => {
    entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); obs.unobserve(e.target); } });
  }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
  [...animEls, ...sectionHeaders].forEach(el => obs.observe(el));

  // ── ACTIVE NAV ──
  const sections   = document.querySelectorAll('section[id]');
  const navAnchors = document.querySelectorAll('.nav-links a');
  const secObs = new IntersectionObserver(entries => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        navAnchors.forEach(a => a.classList.remove('active'));
        const a = document.querySelector(`.nav-links a[href="#${e.target.id}"]`);
        if (a) a.classList.add('active');
      }
    });
  }, { threshold: 0.4 });
  sections.forEach(s => secObs.observe(s));

  // ── TANK FILL ──
  if (fillBar) {
    const tankObs = new IntersectionObserver(entries => {
      entries.forEach(e => {
        if (e.isIntersecting) {
          setTimeout(() => { fillBar.style.width = '82%'; }, 300);
          tankObs.unobserve(e.target);
        }
      });
    }, { threshold: 0.3 });
    tankObs.observe(fillBar.closest('.pellet-graphic') || fillBar);
  }

  // ══════════════════════════════════════════════
  // MENGE-SCHIEBEREGLER (Formular)
  // ══════════════════════════════════════════════

  function updateMenge(val) {
    const t = parseInt(val, 10);

    // Bubble + Eingabefeld
    if (mengeDisplay) {
      mengeDisplay.textContent  = t;
      mengeDisplay.style.transform = 'scale(1.25)';
      setTimeout(() => { mengeDisplay.style.transform = 'scale(1)'; }, 130);
    }
    if (mengeInput) mengeInput.value = t;
    if (mengeHintText) mengeHintText.textContent = getMengeHint(t);

    // Farbtrack
    updateSliderFill(slider, sliderFill);
    // Mark hervorheben
    highlightMark(mengeMarks, t);

    // Live-Preisberechnung
    const { ton, preis, pellets, gesamt } = calcPrice(t);
    if (pcPreisProT)   pcPreisProT.textContent   = preis;
    if (pcTonnen)      pcTonnen.textContent       = ton;
    if (pcPelletTotal) pcPelletTotal.textContent  = fmtEur(pellets);
    if (pcTotal)       pcTotal.textContent        = fmtEur(gesamt);

    // Quick-Rechner synchron halten (ohne Rekursion)
    syncQcToMenge(t);
  }

  if (slider) {
    buildMarks(slider, mengeMarks);
    slider.addEventListener('input', () => updateMenge(slider.value));
    updateMenge(slider.value); // Initialwert setzen
  }

  // ══════════════════════════════════════════════
  // SCHNELLRECHNER (#preise)
  // ══════════════════════════════════════════════

  /** Aktualisiert nur den QC ohne Formular-Slider anzufassen */
  function renderQc(val) {
    const t = parseInt(val, 10);
    if (qcDisplay) qcDisplay.textContent = t;
    if (qcT2)      qcT2.textContent      = t;
    updateSliderFill(qcSlider, qcFill);
    highlightMark(qcMarks, t);
    const { preis, pellets, gesamt } = calcPrice(t);
    if (qcPreisT)  qcPreisT.textContent  = preis + ' €';
    if (qcPellets) qcPellets.textContent = fmtEur(pellets);
    if (qcTotal)   qcTotal.textContent   = fmtEur(gesamt);
  }

  /** Synchronisiert QC-Slider aus Formular-Wert */
  function syncQcToMenge(t) {
    if (!qcSlider) return;
    qcSlider.value = t;
    renderQc(t);
  }

  if (qcSlider) {
    buildMarks(qcSlider, qcMarks);
    qcSlider.addEventListener('input', () => {
      renderQc(qcSlider.value);
      // Formular-Slider auch synchron halten
      if (slider) {
        slider.value = qcSlider.value;
        updateMenge(qcSlider.value);
      }
    });
    renderQc(qcSlider.value); // Initialwert
  }

  // ══════════════════════════════════════════════
  // KALENDERWOCHE-PICKER
  // ══════════════════════════════════════════════

  let selectedKW     = null;
  let selectedKWYear = null;

  function getISOWeek(date) {
    const d      = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
    const dayNum = d.getUTCDay() || 7;
    d.setUTCDate(d.getUTCDate() + 4 - dayNum);
    const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
    return { kw: Math.ceil((((d - yearStart) / 86400000) + 1) / 7), year: d.getUTCFullYear() };
  }

  function getWeekDates(kw, year) {
    const jan4   = new Date(Date.UTC(year, 0, 4));
    const dayNum = jan4.getUTCDay() || 7;
    const monday = new Date(jan4);
    monday.setUTCDate(jan4.getUTCDate() + (kw - 1) * 7 - (dayNum - 1));
    const sunday = new Date(monday);
    sunday.setUTCDate(monday.getUTCDate() + 6);
    return { monday, sunday };
  }

  function fmtDate(d) {
    return d.toLocaleDateString('de-AT', { day: '2-digit', month: '2-digit' });
  }

  function renderKwPicker() {
    if (!kwNum) return;
    kwNum.textContent  = String(selectedKW).padStart(2, '0');
    kwYear.textContent = selectedKWYear;

    const { monday, sunday } = getWeekDates(selectedKW, selectedKWYear);
    kwDates.textContent = `${fmtDate(monday)} – ${fmtDate(sunday)}.${selectedKWYear}`;

    if (lieferkw) lieferkw.value = `KW${String(selectedKW).padStart(2,'0')}-${selectedKWYear}`;

    const now  = new Date();
    const { kw: nowKw, year: nowYear } = getISOWeek(now);
    const isPast = (selectedKWYear < nowYear) ||
                   (selectedKWYear === nowYear && selectedKW <= nowKw + 1);

    if (kwHint) {
      kwHint.textContent = isPast
        ? '⚠️ Bitte eine Woche mindestens 2 Wochen in der Zukunft wählen.'
        : `Lieferung in KW${String(selectedKW).padStart(2,'0')}/${selectedKWYear} (${fmtDate(monday)} – ${fmtDate(sunday)})`;
      kwHint.className = 'kw-hint' + (isPast ? ' kw-hint-warn' : ' kw-hint-ok');
    }
  }

  function initKwPicker() {
    if (!kwPrev) return;
    const now  = new Date();
    const { kw, year } = getISOWeek(now);
    let initKw   = kw + 2;
    let initYear = year;
    if (initKw > 52) { initKw -= 52; initYear++; }
    selectedKW     = initKw;
    selectedKWYear = initYear;
    renderKwPicker();
  }

  if (kwPrev) {
    kwPrev.addEventListener('click', () => {
      selectedKW--;
      if (selectedKW < 1) { selectedKW = 52; selectedKWYear--; }
      renderKwPicker();
    });
    kwNext.addEventListener('click', () => {
      selectedKW++;
      if (selectedKW > 52) { selectedKW = 1; selectedKWYear++; }
      renderKwPicker();
    });
    initKwPicker();
  }

  // ══════════════════════════════════════════════
  // PLZ DROPDOWN + LANDING PAGE WEITERLEITUNG
  // ══════════════════════════════════════════════

  let plzTimeout     = null;
  let currentPlzData = null;

  function showPlzDropdown(results) {
    if (!plzDropdown) return;
    if (!results.length) { plzDropdown.classList.remove('open'); return; }
    plzDropdown.innerHTML = results.map(r => `
      <div class="plz-option" data-plz="${r.plz}" data-ort="${r.ort}" data-slug="${r.slug}">
        <span><span class="plz-opt-code">${r.plz}</span> <span class="plz-opt-name">${r.ort}</span></span>
        <span class="plz-opt-region">${r.region || ''}</span>
      </div>
    `).join('');
    plzDropdown.classList.add('open');
    plzDropdown.querySelectorAll('.plz-option').forEach(opt => {
      opt.addEventListener('click', () => selectPLZ(opt.dataset.plz, opt.dataset.ort, opt.dataset.slug));
    });
  }

  function selectPLZ(plz, ort, slug) {
    if (plzInput) plzInput.value = plz;
    if (ortInput) ortInput.value = ort;
    if (plzDropdown) plzDropdown.classList.remove('open');
    currentPlzData = { plz, ort, slug };
    showPlzFound(plz, ort, slug);
    setPlzStatus('ok');
  }

  function showPlzFound(plz, ort, slug) {
    if (!plzInfo) return;
    const url = getRegionUrl(slug);
    plzInfo.className = 'plz-info found';
    plzInfo.innerHTML = `
      <i class="fas fa-map-marker-alt"></i>
      <span><strong>${plz} ${ort}</strong> · Lieferung möglich</span>
      <a href="${url}"><i class="fas fa-external-link-alt"></i> Regionseite</a>
    `;
  }

  function showPlzNotFound(plz) {
    if (!plzInfo) return;
    plzInfo.className = 'plz-info not-found';
    plzInfo.innerHTML = `<i class="fas fa-info-circle"></i> PLZ <strong>${plz}</strong> nicht im System – Anfrage trotzdem möglich!`;
    currentPlzData = null;
  }

  function hidePlzInfo() {
    if (!plzInfo) return;
    plzInfo.className = 'plz-info';
    plzInfo.innerHTML = '';
    currentPlzData = null;
  }

  function setPlzStatus(type) {
    if (!plzStatus) return;
    plzStatus.className = 'plz-status';
    if (type === 'ok')      { plzStatus.className += ' ok';      plzStatus.innerHTML = '<i class="fas fa-check-circle"></i>'; }
    if (type === 'error')   { plzStatus.className += ' error';   plzStatus.innerHTML = '<i class="fas fa-times-circle"></i>'; }
    if (type === 'loading') { plzStatus.className += ' loading'; plzStatus.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i>'; }
    if (type === '')        { plzStatus.innerHTML = ''; }
  }

  if (plzInput) {
    plzInput.addEventListener('input', () => {
      const val = plzInput.value.trim();
      clearTimeout(plzTimeout);
      hidePlzInfo();
      setPlzStatus('');
      if (val.length < 2) { plzDropdown && plzDropdown.classList.remove('open'); return; }

      if (typeof searchPLZ === 'function') {
        const results = searchPLZ(val);
        showPlzDropdown(results);
      }

      if (val.length === 4) {
        setPlzStatus('loading');
        plzTimeout = setTimeout(() => {
          if (typeof lookupPLZ === 'function') {
            const found = lookupPLZ(val);
            if (found) {
              if (ortInput) ortInput.value = found.ort;
              selectPLZ(val, found.ort, found.slug);
            } else {
              setPlzStatus('error');
              showPlzNotFound(val);
              plzDropdown && plzDropdown.classList.remove('open');
            }
          }
        }, 350);
      }
    });
    plzInput.addEventListener('keydown', e => {
      if (e.key === 'Escape') plzDropdown && plzDropdown.classList.remove('open');
    });
  }

  document.addEventListener('click', e => {
    if (!e.target.closest('.plz-group') && plzDropdown) plzDropdown.classList.remove('open');
  });

  // ══════════════════════════════════════════════
  // 3-SCHRITT FORMULAR
  // ══════════════════════════════════════════════

  function setStep(n) {
    [step1, step2, step3, stepSuccess].forEach(s => { if (s) s.classList.remove('active'); });
    [dot1, dot2, dot3].forEach(d => { if (d) { d.classList.remove('active', 'done'); } });

    if (n === 1 && step1) {
      step1.classList.add('active');
      if (progressBar) progressBar.style.width = '33%';
      dot1?.classList.add('active');
    }
    if (n === 2 && step2) {
      step2.classList.add('active');
      if (progressBar) progressBar.style.width = '66%';
      dot1?.classList.add('done'); dot2?.classList.add('active');
    }
    if (n === 3 && step3) {
      step3.classList.add('active');
      if (progressBar) progressBar.style.width = '90%';
      dot1?.classList.add('done'); dot2?.classList.add('done'); dot3?.classList.add('active');
    }
    if (n === 4 && stepSuccess) {
      stepSuccess.classList.add('active');
      if (progressBar) progressBar.style.width = '100%';
      [dot1, dot2, dot3].forEach(d => d?.classList.add('done'));
    }

    const card = document.getElementById('order-card');
    if (card) card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  function shakeEl(el) {
    if (!el) return;
    el.style.animation = 'shake 0.35s ease';
    el.classList.add('input-error');
    setTimeout(() => { el.style.animation = ''; el.classList.remove('input-error'); }, 600);
  }

  // Schritt 1 → 2
  if (nextBtn1) {
    nextBtn1.addEventListener('click', () => {
      const kwVal  = lieferkw?.value;
      if (!kwVal) { shakeEl(document.getElementById('kwPicker')); return; }
      const now    = new Date();
      const { kw: nowKw, year: nowYear } = getISOWeek(now);
      const isPast = (selectedKWYear < nowYear) ||
                     (selectedKWYear === nowYear && selectedKW <= nowKw + 1);
      if (isPast) { shakeEl(document.getElementById('kwPicker')); return; }
      setStep(2);
    });
  }

  // Schritt 2 → 3
  if (nextBtn2) {
    nextBtn2.addEventListener('click', () => {
      const plzEl    = document.getElementById('plz');
      const ortEl    = document.getElementById('ort');
      const strasseEl = document.getElementById('strasse');
      if (!plzEl?.value.trim() || plzEl.value.trim().length < 3) { plzEl?.focus(); shakeEl(plzEl); return; }
      if (!ortEl?.value.trim())    { ortEl?.focus();    shakeEl(ortEl);    return; }
      if (!strasseEl?.value.trim()) { strasseEl?.focus(); shakeEl(strasseEl); return; }
      setStep(3);
      buildOrderSummary();
    });
  }

  // Zurück-Buttons
  if (backBtn1) backBtn1.addEventListener('click', () => setStep(1));
  if (backBtn2) backBtn2.addEventListener('click', () => setStep(2));

  // ── Bestellzusammenfassung ──
  function buildOrderSummary() {
    const box = document.getElementById('orderSummary');
    if (!box) return;
    const t      = parseInt(mengeInput?.value || 5, 10);
    const kwStr  = lieferkw?.value || '–';
    const { preis, pellets, gesamt } = calcPrice(t);
    const plzVal = document.getElementById('plz')?.value || '';
    const ortVal = document.getElementById('ort')?.value || '';
    const strVal = document.getElementById('strasse')?.value || '';

    box.innerHTML = `
      <div class="summary-title"><i class="fas fa-receipt"></i> Bestellübersicht</div>
      <div class="summary-row"><span><i class="fas fa-fire"></i> Menge</span><strong>${t} Tonnen lose</strong></div>
      <div class="summary-row"><span><i class="fas fa-calendar-week"></i> Lieferwoche</span><strong>${kwStr}</strong></div>
      <div class="summary-row"><span><i class="fas fa-map-marker-alt"></i> Lieferort</span><strong>${plzVal} ${ortVal}${strVal ? ', ' + strVal : ''}</strong></div>
      <div class="summary-divider"></div>
      <div class="summary-row"><span>Pellets (${preis} €/t × ${t} t)</span><strong>${fmtEur(pellets)}</strong></div>
      <div class="summary-row"><span>Abschlauchgebühr (einmalig)</span><strong>${ABSCHLAUCH} €</strong></div>
      <div class="summary-row summary-total"><span><strong>Geschätzter Gesamtpreis</strong></span><strong class="summary-total-val">${fmtEur(gesamt)}</strong></div>
      <div class="summary-note">inkl. MwSt. · unverbindliche Schätzung · Bestätigung per Telefon/E-Mail</div>
    `;
  }

  // ── FORMULAR ABSENDEN ──
  if (orderForm) {
    orderForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const submitBtn = document.getElementById('submitBtn');
      const t = parseInt(mengeInput?.value || 5, 10);
      const { preis, pellets, gesamt } = calcPrice(t);
      setOrderError('');

      if (submitBtn) {
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Bestellung wird gesendet…';
        submitBtn.disabled  = true;
      }

      const data = {
        menge:           t,
        lieferkw:        lieferkw?.value,
        plz:             document.getElementById('plz')?.value,
        ort:             document.getElementById('ort')?.value,
        strasse:         document.getElementById('strasse')?.value,
        zufahrt:         document.getElementById('zufahrt')?.value,
        vorname:         document.getElementById('vorname')?.value,
        nachname:        document.getElementById('nachname')?.value,
        email:           document.getElementById('email')?.value,
        telefon:         document.getElementById('telefon')?.value,
        marketing_ok:    document.getElementById('marketing')?.checked ? 'Ja' : 'Nein',
        gesamtpreis:     fmtEur(gesamt),
        preis_pro_tonne: preis + ' €/t',
        sent_at:         new Date().toLocaleString('de-AT')
      };

      // ──────────────────────────────────────────
      // E-MAIL VERSAND
      // ──────────────────────────────────────────

      // Web3Forms Access Key – wird aus Admin-Bereich gesetzt
      // oder hier direkt eintragen: 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx'
      const DEFAULT_WEB3FORMS_KEY = '8b18adc8-a507-499e-95a0-54c1485b341d';
      const WEB3FORMS_KEY = localStorage.getItem('sp_w3f_key_active') || DEFAULT_WEB3FORMS_KEY || 'PENDING_CONFIRMATION';

      // Formspree Backup (optional – eigene ID eintragen)
      const FORMSPREE_ID = 'YOUR_FORMSPREE_ID';

      let sent = false;
      let stored = false;

      // METHODE 1: Web3Forms (kein Server nötig, sofort nach E-Mail-Bestätigung aktiv)
      if (WEB3FORMS_KEY !== 'PENDING_CONFIRMATION') {
        try {
          const bestellText = [
            `━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━`,
            `🌲 NEUE PELLETS-BESTELLUNG`,
            `━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━`,
            ``,
            `📦 BESTELLUNG`,
            `  Menge:         ${data.menge} Tonnen (lose)`,
            `  Lieferwoche:   ${data.lieferkw}`,
            `  Preis/Tonne:   ${data.preis_pro_tonne}`,
            `  Gesamtpreis:   ${data.gesamtpreis}`,
            ``,
            `📍 LIEFERADRESSE`,
            `  Straße:        ${data.strasse}`,
            `  Ort:           ${data.plz} ${data.ort}`,
            `  Zufahrt:       ${data.zufahrt || '–'}`,
            ``,
            `👤 KONTAKT`,
            `  Name:          ${data.vorname} ${data.nachname}`,
            `  E-Mail:        ${data.email}`,
            `  Telefon:       ${data.telefon}`,
            ``,
            `  Marketing OK:  ${data.marketing_ok}`,
            `  Eingegangen:   ${data.sent_at}`,
            `━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━`,
          ].join('\n');

          const payload = {
            access_key:  WEB3FORMS_KEY,
            subject:     `🌲 Neue Pellets-Bestellung: ${data.menge} t – ${data.vorname} ${data.nachname} (${data.plz} ${data.ort})`,
            from_name:   'Steirer Pellets Website',
            replyto:     data.email,
            message:     bestellText,
            // Alle Felder strukturiert
            Menge:           data.menge + ' Tonnen',
            Lieferwoche:     data.lieferkw,
            'Preis pro Tonne': data.preis_pro_tonne,
            Gesamtpreis:     data.gesamtpreis,
            Straße:          data.strasse,
            PLZ_Ort:         data.plz + ' ' + data.ort,
            Zufahrt:         data.zufahrt || '–',
            Vorname:         data.vorname,
            Nachname:        data.nachname,
            'E-Mail Kunde':  data.email,
            Telefon:         data.telefon,
            Marketing:       data.marketing_ok,
            Eingegangen:     data.sent_at,
          };

          const r = await fetch('https://api.web3forms.com/submit', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body:    JSON.stringify(payload)
          });
          const j = await r.json();
          if (j.success) sent = true;
        } catch (_) {}
      }

      // METHODE 2: PHP-Mailer (auf eigenem Webspace mit PHP)
      if (!sent) {
        try {
          const r = await fetch('mail.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(data)
          });
          if (r.ok) {
            const j = await r.json();
            if (j.ok) sent = true;
          }
        } catch (_) {}
      }

      // METHODE 3: Formspree (eigene ID eintragen auf formspree.io)
      if (!sent && FORMSPREE_ID !== 'YOUR_FORMSPREE_ID') {
        try {
          const fd = new FormData();
          Object.entries(data).forEach(([k, v]) => fd.append(k, v));
          fd.append('_subject', `Neue Pellets-Bestellung: ${data.menge} t – ${data.vorname} ${data.nachname}`);
          fd.append('_replyto', data.email);
          const r = await fetch(`https://formspree.io/f/${FORMSPREE_ID}`, {
            method:  'POST',
            body:    fd,
            headers: { 'Accept': 'application/json' }
          });
          if (r.ok) sent = true;
        } catch (_) {}
      }

      // BACKUP: Genspark-Tabelle (immer gespeichert als Sicherheitsnetz)
      try {
        const backupRes = await fetch('tables/bestellungen', {
          method:  'POST',
          headers: { 'Content-Type': 'application/json' },
          body:    JSON.stringify({ ...data, gesamtpreis: gesamt })
        });
        stored = backupRes.ok;
      } catch (_) {}

      if (!sent && !stored) {
        setOrderError('Die Bestellung konnte gerade nicht übertragen werden. Bitte versuchen Sie es erneut oder rufen Sie uns unter +43 3574 / 2200 an.');
        if (submitBtn) {
          submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Bestellung absenden';
          submitBtn.disabled = false;
        }
        return;
      }

      // ── Erfolgs-Anzeige ──
      const successSum = document.getElementById('successSummary');
      if (successSum) {
        successSum.innerHTML = `
          <p><i class="fas fa-fire" style="color:var(--green-logo);margin-right:6px"></i><strong>Menge:</strong> ${t} Tonnen lose</p>
          <p><i class="fas fa-calendar-week" style="color:var(--green-logo);margin-right:6px"></i><strong>Lieferwoche:</strong> ${data.lieferkw}</p>
          <p><i class="fas fa-map-marker-alt" style="color:var(--green-logo);margin-right:6px"></i><strong>Lieferort:</strong> ${data.plz} ${data.ort}, ${data.strasse}</p>
          <p><i class="fas fa-receipt" style="color:var(--green-logo);margin-right:6px"></i><strong>Geschätzter Gesamtpreis:</strong> ${fmtEur(gesamt)}</p>
        `;
      }

      setStep(4);

      // Reset nach 18 Sekunden
      setTimeout(() => {
        orderForm.reset();
        updateMenge(5);
        if (slider) slider.value = 5;
        if (submitBtn) {
          submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Bestellung absenden';
          submitBtn.disabled  = false;
        }
        setOrderError('');
        hidePlzInfo();
        setPlzStatus('');
        currentPlzData = null;
        initKwPicker();
        setStep(1);
      }, 18000);
    });
  }

  // ══════════════════════════════════════════════
  // INLINE-CSS: Animationen & dynamische Styles
  // ══════════════════════════════════════════════
  const styleEl = document.createElement('style');
  styleEl.textContent = `
    @keyframes shake {
      0%,100% { transform: translateX(0); }
      20%      { transform: translateX(-7px); }
      40%      { transform: translateX(7px); }
      60%      { transform: translateX(-4px); }
      80%      { transform: translateX(4px); }
    }
    .input-error {
      border-color: #e53e3e !important;
      box-shadow: 0 0 0 3px rgba(229,62,62,0.15) !important;
    }
    .menge-val { transition: transform 0.13s cubic-bezier(0.34,1.56,0.64,1); }
    .step-dot.done span { background: var(--green-logo); color: white; }
    .kw-hint-warn { color: #c97010; background: #fff8ee; border-color: #e8922a; }
    .kw-hint-ok   { color: #1d6b28; background: #f2faf3; border-color: var(--green-logo); }

    /* Slider-Mark: aktive Marke grün & fett */
    .slider-marks span.active {
      color: var(--green-logo);
      font-weight: 800;
    }

    /* Bestellzusammenfassung */
    .summary-title { font-weight: 700; color: var(--gray-800); margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
    .summary-title i { color: var(--green-logo); }
    .summary-row { display: flex; justify-content: space-between; align-items: center; padding: 6px 0; font-size: 0.875rem; border-bottom: 1px solid var(--gray-200); }
    .summary-row:last-child { border-bottom: none; }
    .summary-row span { color: var(--gray-600); display: flex; align-items: center; gap: 6px; }
    .summary-row span i { color: var(--green-logo); width: 14px; }
    .summary-divider { height: 1px; background: var(--gray-200); margin: 8px 0; }
    .summary-total { padding-top: 10px; margin-top: 4px; }
    .summary-total-val { font-size: 1.1rem; color: var(--green-logo); }
    .summary-note { font-size: 0.72rem; color: var(--gray-500); text-align: right; margin-top: 6px; }
    .form-message { border-radius: 10px; padding: 12px 14px; font-size: 0.875rem; display: flex; align-items: center; gap: 10px; margin-bottom: 14px; }
    .form-message[hidden] { display: none !important; }
    .form-message-error { background: #fff1f2; border: 1px solid #fecdd3; color: #9f1239; }
  `;
  document.head.appendChild(styleEl);

  // Initialen Step setzen
  setStep(1);

  // ══════════════════════════════════════════════
  // PREISE AUS DATENBANK LADEN
  // ══════════════════════════════════════════════
  async function loadPreise() {
    try {
      const res  = await fetch('tables/preise?limit=1');
      const json = await res.json();
      const row  = (json.data || [])[0];
      if (!row) return;

      if (row.preis_gross) PREIS_GROSS = Number(row.preis_gross);
      if (row.preis_klein) PREIS_KLEIN = Number(row.preis_klein);
      if (row.abschlauch)  ABSCHLAUCH  = Number(row.abschlauch);
      localStorage.setItem(PREIS_CACHE_KEY, JSON.stringify({
        preis_gross: PREIS_GROSS,
        preis_klein: PREIS_KLEIN,
        abschlauch: ABSCHLAUCH
      }));

      // Alle Preis-Anzeigen auf der Seite aktualisieren
      applyPreiseToPage();
    } catch (e) {
      try {
        const cached = JSON.parse(localStorage.getItem(PREIS_CACHE_KEY) || 'null');
        if (cached) {
          PREIS_GROSS = Number(cached.preis_gross) || PREIS_GROSS;
          PREIS_KLEIN = Number(cached.preis_klein) || PREIS_KLEIN;
          ABSCHLAUCH = Number(cached.abschlauch) || ABSCHLAUCH;
          applyPreiseToPage();
          return;
        }
      } catch (_) {}
      console.warn('Preise konnten nicht aus DB geladen werden – Fallback aktiv.', e);
    }
  }

  function applyPreiseToPage() {
    // Hero-Preisteaser
    const ptGross = document.querySelectorAll('.pt-price-gross');
    const ptKlein = document.querySelectorAll('.pt-price-klein');
    const ptAbsch = document.querySelectorAll('.pt-abschlauch-val');
    ptGross.forEach(el => el.textContent = PREIS_GROSS);
    ptKlein.forEach(el => el.textContent = PREIS_KLEIN);
    ptAbsch.forEach(el => el.textContent = ABSCHLAUCH);

    // Preistabelle (#preise Sektion)
    const pcGross = document.querySelectorAll('.price-big-gross');
    const pcKlein = document.querySelectorAll('.price-big-klein');
    const pcAbschItems = document.querySelectorAll('.price-abschlauch-val');
    pcGross.forEach(el => el.textContent = PREIS_GROSS);
    pcKlein.forEach(el => el.textContent = PREIS_KLEIN);
    pcAbschItems.forEach(el => el.textContent = ABSCHLAUCH);

    // Slider-Kalkulator neu berechnen
    if (slider) updateMenge(slider.value);
    if (qcSlider) renderQc(qcSlider.value);

    console.log(`💶 Preise geladen: ${PREIS_GROSS} €/t (ab 4t), ${PREIS_KLEIN} €/t (<4t), Abschlauch ${ABSCHLAUCH} €`);
  }

  loadPreise();

  console.log('🌲 Steirer Pellets v5 – Slider & Marks synchron, E-Mail-Versand aktiv!');
});
