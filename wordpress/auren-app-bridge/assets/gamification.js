/* Auren's header reads the same authenticated GamiPress adapter as the app. */
(() => {
  'use strict';
  const config = window.aurenGamification;
  if (!config || new URL(config.endpoint, location.href).origin !== location.origin) return;
  const hosts = [...document.querySelectorAll('#header-aside .header-aside-inner, .bb-mobile-header > .header-aside')];
  if (!hosts.length) return;
  let loading = false;
  const widgets = hosts.map(host => {
    const el = document.createElement('div');
    el.className = 'auren-gami'; el.hidden = true;
    host.prepend(el); return el;
  });
  const node = (tag, cls, value) => {
    const el = document.createElement(tag); el.className = cls;
    if (value !== undefined) el.textContent = value;
    return el;
  };
  const picture = (url, cls) => {
    if (!url) return null;
    const src = new URL(url, location.href);
    if (!['https:', 'http:'].includes(src.protocol)) return null;
    const img = node('img', cls); img.src = src.href; img.alt = ''; return img;
  };
  function render(el, data) {
    el.replaceChildren(); el.hidden = !data.available || !data.credits;
    if (el.hidden) return;
    const rank = data.rank;
    if (rank?.current) {
      const level = node('div', 'auren-gami__level'); level.tabIndex = 0;
      const medal = picture(rank.current.image_url, 'auren-gami__medal') || node('span', 'auren-gami__medal', '★');
      const info = node('div', 'auren-gami__info');
      info.append(node('span', 'auren-gami__name', `${rank.current.number ? `Nivel ${rank.current.number} · ` : ''}${rank.current.name}`));
      const percent = rank.progress.percent;
      const required = rank.progress.requirements.filter(r => !r.optional);
      let description = rank.next ? `Siguiente nivel: ${rank.next.name}. ` : 'Nivel más alto disponible.';
      description += required.map(r => `${r.title}: ${r.current} / ${r.total} ${r.unit}`).join('. ');
      if (percent !== null) {
        const track = node('div', 'auren-gami__track'); track.setAttribute('role', 'progressbar');
        track.setAttribute('aria-label', `Avance hacia ${rank.next?.name || 'el siguiente nivel'}`);
        track.setAttribute('aria-valuemin', '0'); track.setAttribute('aria-valuemax', '100'); track.setAttribute('aria-valuenow', String(percent));
        const fill = node('span', 'auren-gami__fill'); fill.style.width = `${Math.max(0, Math.min(100, percent))}%`;
        track.append(fill); info.append(track);
      }
      level.title = description; level.setAttribute('aria-label', `${info.textContent}. ${description}`);
      level.append(medal, info); el.append(level);
    }
    const credit = node('span', 'auren-gami__credits'); credit.tabIndex = 0;
    credit.title = 'Auren Credits'; credit.setAttribute('aria-label', `${data.credits.balance} Auren Credits`);
    const image = picture(data.credits.image_url, 'auren-gami__coin'); if (image) credit.append(image);
    credit.append(node('span', 'auren-gami__balance', new Intl.NumberFormat('es-MX').format(data.credits.balance)));
    credit.append(node('span', 'auren-gami__tooltip', 'Auren Credits')); el.append(credit);
  }
  async function refresh() {
    if (loading || document.visibilityState === 'hidden') return;
    loading = true;
    const controller = new AbortController(); const timeout = setTimeout(() => controller.abort(), 15000);
    try {
      const response = await fetch(config.endpoint, {headers: {'X-WP-Nonce': config.nonce, Accept: 'application/json'}, credentials: 'same-origin', cache: 'no-store', redirect: 'error', signal: controller.signal});
      if (!response.ok) { if ([401, 403].includes(response.status)) widgets.forEach(el => {el.hidden = true; el.replaceChildren();}); return; }
      const data = await response.json(); widgets.forEach(el => render(el, data));
    } catch { /* Keep the last confirmed values during a transient connection failure. */ }
    finally { clearTimeout(timeout); loading = false; }
  }
  refresh(); window.addEventListener('focus', refresh); window.addEventListener('pageshow', refresh);
  document.addEventListener('visibilitychange', refresh); setInterval(refresh, 60000);
})();
