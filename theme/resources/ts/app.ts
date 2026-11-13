import '../css/app.css';

const $ = <T extends Element = HTMLElement>(selector: string, root: ParentNode = document) => root.querySelector<T>(selector);
const $$ = <T extends Element = HTMLElement>(selector: string, root: ParentNode = document) => Array.from(root.querySelectorAll<T>(selector));
const reducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const formFields = (root: ParentNode) => $$<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>('input:not([type="hidden"]), select, textarea', root);

/** Calls back with true once a sentinel of `height` at the page top leaves the viewport grown by `margin` (no scroll listener). */
function onScrolledPast(height: string, margin: string, callback: (past: boolean) => void): void {
  const sentinel = document.createElement('div');
  sentinel.setAttribute('aria-hidden', 'true');
  sentinel.style.cssText = `position:absolute;top:0;left:0;width:1px;height:${height};pointer-events:none;`;
  document.body.prepend(sentinel);
  new IntersectionObserver(([entry]) => callback(!entry.isIntersecting), { rootMargin: `${margin} 0px 0px 0px` }).observe(sentinel);
}

/** Post or page content from app/PostContentEndpoint.php. */
async function fetchContent(endpoint: string, query: string): Promise<{ html?: string; title?: string; meta?: string; file?: string }> {
  const response = await fetch(`${endpoint}&${query}`, { credentials: 'same-origin' });
  const payload = await response.json() as { success: boolean; data?: { html?: string; title?: string; meta?: string; file?: string } };
  if (!response.ok || !payload.success || !payload.data) throw new Error('unavailable');
  return payload.data;
}

/** Content loaded later into a dialog gets the same enhancements as the page. */
function enhance(root: HTMLElement): void {
  $$('.youtube-player', root).forEach(createYouTubePreview);
  // Gallery plugins (Photonic) print thumbnails with data-src and keep them hidden until
  // their own script runs at page load; content added later has to be woken up here.
  $$<HTMLImageElement>('img[data-src]:not([src])', root).forEach((img) => {
    if (img.dataset.srcset) img.srcset = img.dataset.srcset;
    img.src = img.dataset.src ?? '';
    img.style.visibility = 'visible';
  });
  markZoomable(root);
}

/* ---------- Header ---------- */

function initialiseMobileMenu(): void {
  $$('[data-mobile-menu]').forEach((container) => {
    const button = $<HTMLButtonElement>('[data-menu-toggle]', container);
    if (!button) return;
    const setOpen = (open: boolean) => {
      container.classList.toggle('is-open', open);
      button.setAttribute('aria-expanded', String(open));
    };
    button.addEventListener('click', () => setOpen(button.getAttribute('aria-expanded') !== 'true'));

    $$('a', container).forEach((link) => link.addEventListener('click', () => setOpen(false)));
  });
}

/** The link of the section crossing the band just below the sticky header is marked current. */
function initialiseNavScrollSpy(): void {
  const links = $$<HTMLAnchorElement>('[data-nav] a').filter((link) => link.hash && link.pathname === window.location.pathname);
  const sectionOf = (hash: string) => document.getElementById(hash.slice(1))?.closest('section') ?? null;
  const sections = [...new Set(links.map((link) => sectionOf(link.hash)))].filter((section): section is HTMLElement => section !== null);
  if (sections.length === 0) return;

  const visible = new Set<Element>();
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => (entry.isIntersecting ? visible.add(entry.target) : visible.delete(entry.target)));
    const current = sections.find((section) => visible.has(section));
    links.forEach((link) => {
      if (current && sectionOf(link.hash) === current) link.setAttribute('aria-current', 'true');
      else link.removeAttribute('aria-current');
    });
  }, { rootMargin: '-90px 0px -55% 0px' });
  sections.forEach((section) => observer.observe(section));
}

/** Desktop navigation: one underline slides to the current section (transform only). */
function initialiseNavIndicator(): void {
  const list = $('[data-nav="desktop"]');
  if (!list) return;
  const links = $$<HTMLAnchorElement>('a', list);
  const indicator = document.createElement('span');
  indicator.className = 'tj:pointer-events-none tj:absolute tj:bottom-[.3rem] tj:left-0 tj:h-[2px] tj:w-[100px] tj:origin-left tj:rounded-[2px] tj:bg-ember tj:opacity-0 tj:transition-[transform,opacity] tj:duration-250 tj:ease-out tj:[&.is-active]:opacity-100';
  indicator.setAttribute('aria-hidden', 'true');
  list.append(indicator);

  const place = () => {
    const current = links.find((link) => link.getAttribute('aria-current') === 'true');
    indicator.classList.toggle('is-active', Boolean(current));
    if (!current) return;
    // Same inset as the link padding, so the line sits under the text only.
    const inset = Number.parseFloat(getComputedStyle(current).paddingLeft) || 0;
    indicator.style.transform = `translateX(${current.offsetLeft + inset}px) scaleX(${Math.max(0, current.offsetWidth - inset * 2) / 100})`;
  };
  const observer = new MutationObserver(place);
  links.forEach((link) => observer.observe(link, { attributes: true, attributeFilter: ['aria-current'] }));
  new ResizeObserver(place).observe(list);
  void document.fonts?.ready.then(place);
}

/**
 * The link row never wraps: when logo, links and the registration button do
 * not fit on one line, the header switches to the hamburger layout.
 */
function initialiseHeader(): void {
  const header = $('[data-site-header]');
  const inner = header?.firstElementChild;
  const nav = header && $('nav', header);
  if (!header || !(inner instanceof HTMLElement) || !nav) return;

  const fit = () => {
    header.classList.remove('is-compact');
    // At phone widths CSS already hides the link row and shows the hamburger.
    if (getComputedStyle(nav).display === 'none') return;
    header.classList.toggle('is-compact', inner.scrollWidth > inner.clientWidth + 1);
  };
  new ResizeObserver(fit).observe(inner);
  void document.fonts?.ready.then(fit);
  // Stronger shadow and a smaller logo once the page is scrolled.
  onScrolledPast('64px', '0px', (past) => header.classList.toggle('is-scrolled', past));
}

/** "Back to top" appears once the visitor is about two screens down. */
function initialiseBackToTop(): void {
  const button = $<HTMLAnchorElement>('[data-to-top]');
  if (!button) return;
  onScrolledPast('1px', '200%', (past) => button.classList.toggle('is-shown', past));
  button.addEventListener('click', (event) => {
    event.preventDefault();
    window.scrollTo({ top: 0, behavior: reducedMotion() ? 'auto' : 'smooth' });
    // Focus the content start for keyboard and screen reader users, without a second jump.
    $('#main-content')?.focus({ preventScroll: true });
  });
}

/** Past seasons: phones start with the latest six; the ticker of their names can be paused. */
function initialiseSeasons(): void {
  const list = $('[data-seasons]');
  const all = $<HTMLButtonElement>('[data-seasons-all]');
  if (list && all) {
    all.addEventListener('click', () => {
      const first = $$<HTMLElement>(':scope > li', list).find((item) => item.offsetParent === null);
      list.classList.add('is-all');
      all.closest('p')?.remove();
      first?.querySelector('a')?.focus();
    });
    // Revealed another way (a shared season link): the button has nothing left to do.
    new MutationObserver(() => { if (list.classList.contains('is-all')) all.closest('p')?.remove(); }).observe(list, { attributes: true, attributeFilter: ['class'] });
  }
  const toggle = $<HTMLButtonElement>('[data-ticker-toggle]');
  const ticker = toggle?.parentElement && $('.tj-ticker', toggle.parentElement);
  if (!toggle || !ticker) return;
  toggle.addEventListener('click', () => {
    const paused = ticker.classList.toggle('is-paused');
    toggle.setAttribute('aria-pressed', String(paused));
    toggle.setAttribute('aria-label', paused ? 'Spustit běžící názvy ročníků' : 'Zastavit běžící názvy ročníků');
  });
}

/** Accordions where only one item may be open (FAQ, past season articles). */
function initialiseExclusiveDetails(): void {
  $$('[data-exclusive-details], [data-exclusive-accordion]').forEach((group) => {
    const items = $$<HTMLDetailsElement>('details', group);
    items.forEach((item) => item.addEventListener('toggle', () => {
      if (item.open) items.forEach((other) => { if (other !== item) other.open = false; });
    }));
  });
}

/** Past season articles load their content the first time they are opened. */
function initialiseSeasonArticles(): void {
  $$('[data-exclusive-accordion]').forEach((group) => {
    const endpoint = group.closest<HTMLElement>('[data-post-content-url]')?.dataset.postContentUrl;
    $$<HTMLDetailsElement>('details', group).forEach((item) => item.addEventListener('toggle', async () => {
      if (!item.open) return;
      // Closing the previous article shifts the list; keep the opened one near the top.
      const dialog = item.closest('dialog');
      const summary = $('summary', item);
      if (dialog && summary) dialog.scrollTop += summary.getBoundingClientRect().top - dialog.getBoundingClientRect().top - 16;

      const body = $('[data-article-body]', item);
      if (!endpoint || !body || body.dataset.loaded) return;
      body.dataset.loaded = 'loading';
      const fallback = body.innerHTML;
      body.setAttribute('aria-busy', 'true');
      body.innerHTML = '<p class="tj:text-muted">Načítám článek…</p>';
      try {
        body.innerHTML = (await fetchContent(endpoint, `id=${encodeURIComponent(body.dataset.postId ?? '')}`)).html || '';
        if (!body.innerHTML) throw new Error('empty');
        delete body.dataset.error;
        body.dataset.loaded = 'true';
        enhance(body);
      } catch {
        body.innerHTML = `<p class="tj:text-muted">Článek se nepodařilo načíst.</p>${fallback}`;
        body.dataset.error = 'true';
        delete body.dataset.loaded;
      } finally {
        body.removeAttribute('aria-busy');
      }
    }));
  });
}

/* ---------- Dialogs ---------- */

/**
 * Every open dialog has an address, so it can be shared and the Back button
 * closes it: a dialog printed in the page uses "#<id>", the page dialog the
 * real URL of the post it shows.
 */
const dialogState = (): string | null => (window.history.state as { tjDialog?: string } | null)?.tjDialog ?? null;
const lockScroll = () => document.documentElement.classList.toggle('has-dialog', $('.tj-dialog[open]') !== null);

function initialiseDialogs(): void {
  const openers = new WeakMap<HTMLDialogElement, { opener: HTMLElement; pointer: boolean }>();
  const open = (dialog: HTMLDialogElement, push: boolean) => {
    if (dialog.open) return;
    dialog.showModal();
    lockScroll();
    if (push) window.history.pushState({ tjDialog: dialog.id }, '', `#${dialog.id}`);
  };

  // Openers link to a real URL or to "#<id>", so the content stays reachable without JavaScript.
  $$('[data-dialog-open]').forEach((opener) => {
    const dialog = document.getElementById(opener.dataset.dialogOpen ?? '');
    if (!(dialog instanceof HTMLDialogElement)) return;
    opener.setAttribute('aria-haspopup', 'dialog');
    opener.addEventListener('click', (event) => {
      if (event.ctrlKey || event.metaKey || event.shiftKey || event.button !== 0) return;
      event.preventDefault();
      // `detail` counts pointer clicks; a keyboard-activated link reports 0.
      openers.set(dialog, { opener, pointer: event.detail > 0 });
      open(dialog, true);
    });
  });

  $$<HTMLDialogElement>('.tj-dialog').forEach((dialog) => {
    dialog.addEventListener('close', () => {
      lockScroll();
      // Stop a trailer started inside the dialog by restoring its preview.
      $$('.youtube-player', dialog).forEach((player) => { if ($('iframe', player)) createYouTubePreview(player); });
      // Closed by the button, Esc or the backdrop: leave its address too.
      if (dialogState() === dialog.id) window.history.back();
      else if (window.location.hash === `#${dialog.id}`) window.history.replaceState(null, '', window.location.pathname + window.location.search);
      const last = openers.get(dialog);
      openers.delete(dialog);
      // Dialogs closed with the mouse return focus without a visible ring.
      last?.opener.focus({ preventScroll: true, focusVisible: !last.pointer } as FocusOptions);
    });
    dialog.addEventListener('click', (event) => { if (event.target === dialog) dialog.close(); });
  });

  // Back and Forward: the history entry decides which dialog is open.
  window.addEventListener('popstate', () => {
    const wanted = dialogState();
    $$<HTMLDialogElement>('.tj-dialog[open]').forEach((dialog) => { if (dialog.id !== wanted) dialog.close(); });
    const target = wanted ? document.getElementById(wanted) : null;
    if (!(target instanceof HTMLDialogElement) || target.open) return;
    // The page dialog would need its content again: load the real page instead.
    if (target.hasAttribute('data-page-dialog')) window.location.reload();
    else open(target, false);
  });

  document.addEventListener('click', (event) => {
    const target = event.target instanceof Element ? event.target : null;
    target?.closest('[data-dialog-close]')?.closest('dialog')?.close();
    // Form result banner (template-parts/notifications.php): closing it also cleans the address.
    const notice = target?.closest('[data-notice-close]')?.closest('[data-notice]');
    if (!notice) return;
    notice.remove();
    const url = new URL(window.location.href);
    ['success', 'error'].forEach((key) => url.searchParams.delete(key));
    window.history.replaceState(window.history.state, '', url);
  });

  // A shared "#rocnik-2025" or "#pribeh" link opens its dialog right away.
  const openLinked = () => {
    const linked = window.location.hash.length > 1 ? document.getElementById(decodeURIComponent(window.location.hash.slice(1))) : null;
    if (!(linked instanceof HTMLDialogElement) || !linked.classList.contains('tj-dialog') || linked.hasAttribute('data-page-dialog')) return;
    // A season hidden on phones must be in the page before its dialog can show.
    linked.closest('[data-seasons]')?.classList.add('is-all');
    open(linked, false);
  };
  window.addEventListener('hashchange', openLinked);
  openLinked();
}

/**
 * Homepage: a link to a post or page of this site (a news item, a person, an
 * activity, the packing list) opens it in the page dialog under its real URL.
 * Files, lists, the registration form, admin and the homepage stay normal
 * links; an attachment page opens its file; on failure the link navigates.
 */
function initialisePageDialog(): void {
  const dialog = $<HTMLDialogElement>('[data-page-dialog]');
  const title = $('#page-dialog-title');
  const meta = dialog && $('[data-dialog-meta]', dialog);
  const body = $('[data-page-dialog-body]');
  const endpoint = dialog?.dataset.postContentUrl;
  if (!dialog || !title || !meta || !body || !endpoint || !document.body.classList.contains('home')) return;

  // The homepage path is the site root ("/" in production, "/test/" on stage).
  const homePath = window.location.pathname.replace(/\/?$/, '/');
  const homeUrl = window.location.pathname + window.location.search;
  const opensHere = (link: HTMLAnchorElement): URL | null => {
    if (link.hasAttribute('data-dialog-open') || link.hasAttribute('download') || link.closest('.tj-lightbox')) return null;
    let url: URL;
    try { url = new URL(link.href); } catch { return null; }
    if (url.origin !== window.location.origin || !url.pathname.startsWith(homePath)) return null;
    // Path relative to the site root, without the "index.php/" of stage permalinks.
    const path = '/' + url.pathname.slice(homePath.length).replace(/^index\.php(?:\/|$)/, '');
    if (path === '/' || /^\/(?:wp-admin|wp-content|wp-includes|wp-json|feed|category|tag|rocnik|author|page|e-prihlaska|odeslat-dotaz)(?:\/|$)/.test(path) || /\.[a-z0-9]{2,5}$/i.test(path)) return null;
    return url;
  };

  // Content is fetched once per address and as soon as the visitor shows intent
  // (pointer rests on a link, keyboard focus, touch), so the dialog usually opens filled.
  type Content = Awaited<ReturnType<typeof fetchContent>>;
  const cache = new Map<string, Promise<Content>>();
  const load = (url: URL): Promise<Content> => {
    let pending = cache.get(url.href);
    if (!pending) {
      pending = fetchContent(endpoint, `url=${encodeURIComponent(url.href)}`);
      cache.set(url.href, pending);
      pending.catch(() => cache.delete(url.href));
    }
    return pending;
  };
  const linkOf = (event: Event) => (event.target instanceof Element ? event.target.closest<HTMLAnchorElement>('a[href]') : null);
  const prefetch = (event: Event) => {
    const link = linkOf(event);
    const url = link && opensHere(link);
    if (url) void load(url).catch(() => undefined);
  };
  let hover = 0;
  document.addEventListener('pointerover', (event) => {
    if (event.pointerType !== 'mouse') return;
    window.clearTimeout(hover);
    // A pointer only passing over a row of links fetches nothing.
    hover = window.setTimeout(() => prefetch(event), 80);
  });
  document.addEventListener('pointerout', () => window.clearTimeout(hover));
  document.addEventListener('focusin', prefetch);
  document.addEventListener('touchstart', prefetch, { passive: true });

  let request = 0;
  let opener: HTMLAnchorElement | null = null;
  document.addEventListener('click', async (event) => {
    if (event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
    const link = linkOf(event);
    const url = link && opensHere(link);
    if (!link || !url) return;
    event.preventDefault();
    const state = { tjDialog: dialog.id };
    if (dialog.open) {
      // A link followed inside the dialog replaces its address.
      window.history.replaceState(state, '', url.href);
    } else {
      opener = link;
      dialog.showModal();
      lockScroll();
      window.history.pushState(state, '', url.href);
    }
    const current = ++request;
    title.textContent = link.textContent?.trim() || 'Táborová jízda';
    meta.textContent = '';
    body.replaceChildren();
    body.setAttribute('aria-busy', 'true');
    dialog.scrollTop = 0;
    // "Načítám…" appears only when the content really takes a moment.
    const waiting = window.setTimeout(() => {
      if (current === request && body.childElementCount === 0) body.innerHTML = '<p class="tj:text-muted">Načítám…</p>';
    }, 250);
    try {
      const data = await load(url);
      window.clearTimeout(waiting);
      if (current !== request) return;
      if (data.file) {
        // An attachment page: open the document itself and leave the homepage as it is.
        dialog.close();
        window.open(data.file, '_blank', 'noopener');
        return;
      }
      if (!data.html) throw new Error('empty');
      title.textContent = data.title || title.textContent;
      meta.textContent = data.meta ?? '';
      body.innerHTML = data.html;
      enhance(body);
    } catch {
      window.clearTimeout(waiting);
      // The address already is the page: load it for real.
      if (current === request) window.location.reload();
    } finally {
      if (current === request) body.removeAttribute('aria-busy');
    }
  });
  dialog.addEventListener('close', () => {
    request++;
    // Closed while the history entry was already left (Back): make sure the address is the homepage again.
    if (dialogState() !== dialog.id && window.location.pathname !== homePath) window.history.replaceState(null, '', homeUrl);
    if (opener?.isConnected) opener.focus({ preventScroll: true });
    opener = null;
  });
}

/** Every link to another website opens in a new tab, including content loaded later. */
function initialiseExternalLinks(): void {
  const mark = (link: HTMLAnchorElement | null) => {
    if (!link) return;
    try {
      const url = new URL(link.href);
      if (!/^https?:$/.test(url.protocol) || url.origin === window.location.origin) return;
    } catch {
      return;
    }
    link.target = '_blank';
    link.relList.add('noopener');
  };
  $$<HTMLAnchorElement>('a[href]').forEach(mark);
  document.addEventListener('click', (event) => {
    mark(event.target instanceof Element ? event.target.closest<HTMLAnchorElement>('a[href]') : null);
  }, true);
}

/* ---------- Photo viewer ---------- */

const zoomableSelector = '.tj-prose img, [data-zoom] img';

function isZoomable(img: HTMLImageElement): boolean {
  return img.matches(zoomableSelector)
    && !img.classList.contains('emoji')
    && !img.closest('.youtube-player')
    // Card thumbnails open their dialog instead; photos inside a card's dialog are zoomable.
    && (img.closest('dialog') !== null || !img.closest('[data-card]'));
}

/** Best available source: a linked image file, the widest srcset entry, or the current source. */
function largestSource(img: HTMLImageElement): string {
  const link = img.closest('a');
  if (link && /\.(?:jpe?g|png|webp|gif|avif)(?:[?#].*)?$/i.test(link.href)) return link.href;
  const widest = (img.getAttribute('srcset') ?? '').split(',')
    .map((entry) => entry.trim().split(/\s+/))
    .filter(([url, width]) => url && width?.endsWith('w'))
    .sort((a, b) => Number.parseInt(b[1], 10) - Number.parseInt(a[1], 10))[0];
  return widest?.[0] ?? (img.currentSrc || img.src);
}

/** Makes zoomable images reachable and operable from the keyboard. */
function markZoomable(root: ParentNode): void {
  $$<HTMLImageElement>(zoomableSelector, root).forEach((img) => {
    if (!isZoomable(img) || img.dataset.zoomable) return;
    img.dataset.zoomable = 'true';
    const label = img.alt ? `Zvětšit fotografii: ${img.alt}` : 'Zvětšit fotografii';
    const link = img.closest('a');
    if (link) {
      // The surrounding link is the control: name it and avoid a button nested in a link.
      if (!link.hasAttribute('aria-label')) link.setAttribute('aria-label', label);
      link.setAttribute('aria-haspopup', 'dialog');
      return;
    }
    img.tabIndex = 0;
    img.setAttribute('role', 'button');
    img.setAttribute('aria-label', label);
  });
}

/**
 * One photo viewer: its own modal dialog (so it stacks above open dialogs),
 * never navigates away and replaces plugin lightboxes.
 */
function initialiseLightbox(): void {
  const viewer = document.createElement('dialog');
  viewer.className = 'tj-lightbox';
  viewer.setAttribute('aria-label', 'Prohlížení fotografií');
  viewer.innerHTML = '<figure><img alt=""><figcaption></figcaption></figure>'
    + '<button class="tj-arrow" data-dir="close" type="button" aria-label="Zavřít prohlížení"></button>'
    + '<button class="tj-arrow" data-dir="prev" type="button" aria-label="Předchozí fotografie"></button>'
    + '<button class="tj-arrow" data-dir="next" type="button" aria-label="Další fotografie"></button>'
    + '<p aria-live="polite"></p>';
  document.body.append(viewer);
  const stage = $('figure', viewer)!;
  const image = $<HTMLImageElement>('img', viewer)!;
  const caption = $('figcaption', viewer)!;
  const counter = $('p', viewer)!;
  const previous = $<HTMLButtonElement>('[data-dir="prev"]', viewer)!;
  const next = $<HTMLButtonElement>('[data-dir="next"]', viewer)!;
  let items: HTMLImageElement[] = [];
  let index = 0;

  const show = (target: number, animate = true) => {
    index = (target + items.length) % items.length;
    const current = items[index];
    image.src = largestSource(current);
    image.classList.remove('is-swapping');
    // Paging with the arrow keys is instant; pointer and touch get a short fade.
    if (animate) {
      void image.offsetWidth;
      image.classList.add('is-swapping');
    }
    image.alt = current.alt;
    caption.textContent = current.closest('figure')?.querySelector('figcaption')?.textContent?.trim() || current.alt || '';
    caption.hidden = !caption.textContent;
    const single = items.length < 2;
    previous.hidden = next.hidden = single;
    counter.textContent = single ? '' : `${index + 1} / ${items.length}`;
    [items[index + 1], items[index - 1]].forEach((neighbour) => { if (neighbour) new Image().src = largestSource(neighbour); });
  };
  const open = (img: HTMLImageElement) => {
    // Browsing scope: every visible photo of the surrounding dialog, article or section
    // (collapsed articles are skipped).
    const scope: ParentNode = img.closest('dialog') ?? img.closest('article') ?? img.closest('section') ?? document;
    items = $$<HTMLImageElement>(zoomableSelector, scope).filter((candidate) => isZoomable(candidate) && !candidate.closest('details:not([open])'));
    if (!items.includes(img)) items = [img];
    show(items.indexOf(img));
    viewer.showModal();
  };

  // Capture phase: runs before plugin lightboxes and before links navigate.
  document.addEventListener('click', (event) => {
    if (!(event.target instanceof Element) || viewer.contains(event.target)) return;
    const img = event.target.closest('img') ?? event.target.closest('a')?.querySelector('img');
    if (!(img instanceof HTMLImageElement) || !isZoomable(img)) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    open(img);
  }, true);
  document.addEventListener('keydown', (event) => {
    if ((event.key !== 'Enter' && event.key !== ' ') || !(event.target instanceof HTMLImageElement) || !event.target.dataset.zoomable) return;
    event.preventDefault();
    open(event.target);
  });

  previous.addEventListener('click', () => show(index - 1));
  next.addEventListener('click', () => show(index + 1));
  $('[data-dir="close"]', viewer)?.addEventListener('click', () => viewer.close());
  viewer.addEventListener('click', (event) => { if (event.target === viewer || event.target === stage) viewer.close(); });
  viewer.addEventListener('keydown', (event) => {
    if (event.key === 'ArrowLeft') show(index - 1, false);
    if (event.key === 'ArrowRight') show(index + 1, false);
  });
  let startX: number | null = null;
  viewer.addEventListener('pointerdown', (event) => { startX = event.clientX; });
  viewer.addEventListener('pointerup', (event) => {
    if (startX === null || items.length < 2) return;
    const delta = event.clientX - startX;
    startX = null;
    if (Math.abs(delta) > 50) show(index + (delta < 0 ? 1 : -1));
  });
  markZoomable(document);
}

/** Click-to-load YouTube player: a thumbnail button that becomes the iframe. */
function createYouTubePreview(container: HTMLElement): void {
  const id = container.dataset.id;
  if (!id) return;
  const title = container.dataset.title || 'YouTube video';
  const fallbackThumbnail = `https://i.ytimg.com/vi/${encodeURIComponent(id)}/hqdefault.jpg`;

  const button = document.createElement('button');
  button.type = 'button';
  button.className = 'youtube-player__button';
  button.setAttribute('aria-label', `Přehrát ${title}`);
  const image = document.createElement('img');
  // loading must be set before src, otherwise the download starts immediately.
  image.loading = 'lazy';
  image.alt = '';
  image.src = container.dataset.thumbnail || fallbackThumbnail;
  image.addEventListener('error', () => { if (image.src !== fallbackThumbnail) image.src = fallbackThumbnail; }, { once: true });
  const playIcon = document.createElement('span');
  playIcon.className = 'play';
  playIcon.setAttribute('aria-hidden', 'true');
  button.append(image, playIcon);
  button.addEventListener('click', () => {
    const iframe = document.createElement('iframe');
    iframe.src = `https://www.youtube.com/embed/${encodeURIComponent(id)}?autoplay=1`;
    iframe.title = title;
    iframe.allow = 'autoplay; encrypted-media; picture-in-picture';
    iframe.allowFullscreen = true;
    container.replaceChildren(iframe);
  }, { once: true });
  // Server markup may contain a no-JS fallback link; replace it with the preview.
  container.replaceChildren(button);
}

/* ---------- Registration form ---------- */

const czechMonths = ['leden', 'únor', 'březen', 'duben', 'květen', 'červen', 'červenec', 'srpen', 'září', 'říjen', 'listopad', 'prosinec'];

/**
 * Replaces the native date picker (which follows the browser language) with
 * Czech day / month / year selects; the original input receives the ISO date.
 */
function initialiseCzechDateInputs(): void {
  $$<HTMLInputElement>('input[type="date"][data-czech-date]').forEach((input) => {
    const pad = (value: number) => String(value).padStart(2, '0');
    const makeSelect = (label: string, options: Array<[string, string]>) => {
      const select = document.createElement('select');
      select.className = `${input.className} tj:pr-6 tj:pl-[.55rem] tj:bg-position-[calc(100%-.95rem)_55%,calc(100%-.6rem)_55%]`;
      select.required = input.required;
      select.setAttribute('aria-label', label);
      select.append(new Option(label, ''), ...options.map(([value, text]) => new Option(text, value)));
      return select;
    };
    const thisYear = new Date().getFullYear();
    const day = makeSelect('Den', Array.from({ length: 31 }, (_, index) => [pad(index + 1), `${index + 1}.`]));
    const month = makeSelect('Měsíc', czechMonths.map((name, index) => [pad(index + 1), name]));
    const year = makeSelect('Rok', Array.from({ length: 26 }, (_, index) => [String(thisYear - 3 - index), String(thisYear - 3 - index)]));
    day.id = input.id;
    input.id = `${input.id}-value`;

    const group = document.createElement('div');
    group.className = 'tj:grid tj:grid-cols-[1fr_1.35fr_1.05fr] tj:gap-2';
    group.setAttribute('role', 'group');
    group.setAttribute('aria-labelledby', `${day.id}-label`);
    group.append(day, month, year);
    [year.value, month.value, day.value] = input.value ? input.value.split('-') : ['', '', ''];

    const sync = () => {
      day.setCustomValidity('');
      input.value = '';
      if (!day.value || !month.value || !year.value) return;
      if (new Date(Number(year.value), Number(month.value) - 1, Number(day.value)).getDate() !== Number(day.value)) {
        day.setCustomValidity('Tento den v zadaném měsíci neexistuje.');
        return;
      }
      input.value = `${year.value}-${month.value}-${day.value}`;
    };
    [day, month, year].forEach((select) => select.addEventListener('change', sync));
    input.type = 'hidden';
    input.removeAttribute('required');
    input.after(group);
  });
}

function initialisePersonalIdValidation(): void {
  const input = $<HTMLInputElement>('#rodne_c');
  if (!input) return;
  const pattern = /^\d{0,2}((0[1-9]|1[0-2])|(2[1-9]|3[0-2])|(5[1-9]|6[0-2])|(7[1-9]|8[0-2]))(0[1-9]|[1-2][0-9]|3[01])\/?[0-9]{3,4}$/;
  const validate = () => input.setCustomValidity(input.value.trim() === '' || pattern.test(input.value.trim()) ? '' : 'Rodné číslo nemá správný formát.');
  input.addEventListener('input', validate);
  input.addEventListener('blur', validate);
}

/** Validated steps and a final review of everything entered; without JavaScript the whole form stays on one page. */
function initialiseRegistrationSteps(): void {
  const form = $<HTMLFormElement>('[data-registration-form]');
  const indicator = $('[data-registration-steps]');
  if (!form || !indicator) return;
  const steps = $$<HTMLFieldSetElement>('fieldset[data-step]', form);
  const targets = $$<HTMLButtonElement>('[data-step-target]', indicator);
  if (steps.length < 2) return;

  let current = 0;
  let reached = 0;
  form.classList.add('is-stepped');
  indicator.hidden = false;

  // Review step: what was entered in each earlier step, with a way back to it.
  const summary = $('[data-registration-summary]', form);
  const fieldValue = (field: HTMLElement): string => {
    if (field instanceof HTMLSelectElement && field.closest('[role="group"]')) {
      const [year, month, day] = ($<HTMLInputElement>(`#${field.id}-value`, form)?.value ?? '').split('-');
      return year ? `${Number(day)}. ${Number(month)}. ${year}` : '';
    }
    if (field instanceof HTMLSelectElement) return field.value ? field.selectedOptions[0]?.textContent?.trim() ?? '' : '';
    return field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement ? field.value.trim() : '';
  };
  const buildSummary = () => {
    if (!summary) return;
    summary.replaceChildren(...steps.slice(0, -1).map((step, stepIndex) => {
      const section = document.createElement('section');
      section.className = 'tj:border-t tj:border-ink/14 tj:pt-4';
      const head = document.createElement('div');
      head.className = 'tj:flex tj:items-center tj:justify-between tj:gap-4';
      const heading = document.createElement('h3');
      heading.className = 'tj:m-0 tj:text-[1.1rem] tj:font-bold tj:text-ink';
      heading.textContent = $('legend', step)?.textContent?.trim() ?? '';
      const edit = document.createElement('button');
      edit.type = 'button';
      edit.className = 'tj-link tj:cursor-pointer tj:border-0 tj:bg-transparent tj:p-0';
      edit.textContent = 'Upravit';
      edit.addEventListener('click', () => show(stepIndex));
      head.append(heading, edit);
      const list = document.createElement('dl');
      list.className = 'tj:m-0 tj:mt-3 tj:grid tj:gap-x-6 tj:gap-y-2 tj:sm:grid-cols-[minmax(0,14rem)_minmax(0,1fr)]';
      $$<HTMLLabelElement>('label[for]', step).forEach((label) => {
        const field = document.getElementById(label.htmlFor);
        const value = field ? fieldValue(field) : '';
        if (!value) return;
        const term = document.createElement('dt');
        term.className = 'tj:text-[.95rem] tj:text-muted';
        term.textContent = (label.textContent ?? '').replace('*', '').trim();
        const detail = document.createElement('dd');
        detail.className = 'tj:m-0 tj:font-semibold tj:whitespace-pre-line tj:text-ink tj:max-sm:mb-2';
        detail.textContent = value;
        list.append(term, detail);
      });
      section.append(head, list);
      return section;
    }));
  };

  const show = (index: number, focus = true) => {
    if (index === steps.length - 1) buildSummary();
    current = index;
    reached = Math.max(reached, index);
    steps.forEach((step, stepIndex) => { step.hidden = stepIndex !== index; });
    targets.forEach((target, targetIndex) => {
      target.disabled = targetIndex > reached;
      target.classList.toggle('is-done', targetIndex < index);
      if (targetIndex === index) target.setAttribute('aria-current', 'step');
      else target.removeAttribute('aria-current');
    });
    if (focus) {
      indicator.scrollIntoView({ block: 'start', behavior: reducedMotion() ? 'auto' : 'smooth' });
      (formFields(steps[index])[0] ?? $<HTMLButtonElement>('button[type="submit"]', steps[index]))?.focus({ preventScroll: true });
    }
  };
  const firstInvalid = (index: number) => formFields(steps[index]).find((field) => !field.checkValidity());
  const stepIsValid = (index: number) => {
    const invalid = firstInvalid(index);
    invalid?.reportValidity();
    return !invalid;
  };

  form.addEventListener('click', (event) => {
    const button = event.target instanceof Element ? event.target.closest('[data-step-next], [data-step-prev]') : null;
    if (button?.hasAttribute('data-step-prev')) show(Math.max(current - 1, 0));
    else if (button && stepIsValid(current)) show(Math.min(current + 1, steps.length - 1));
  });
  targets.forEach((target, index) => target.addEventListener('click', () => {
    if (index <= reached && (index < current || stepIsValid(current))) show(index);
  }));
  form.addEventListener('submit', (event) => {
    const invalidStep = steps.findIndex((_, index) => firstInvalid(index));
    if (invalidStep === -1) return;
    event.preventDefault();
    show(invalidStep);
    stepIsValid(invalidStep);
  });
  show(0, false);
}

document.addEventListener('DOMContentLoaded', () => {
  initialiseMobileMenu();
  initialiseNavScrollSpy();
  initialiseNavIndicator();
  initialiseHeader();
  initialiseBackToTop();
  initialiseSeasons();
  initialiseExclusiveDetails();
  initialiseSeasonArticles();
  initialiseLightbox();
  initialisePageDialog();
  initialiseDialogs();
  initialiseExternalLinks();
  initialiseCzechDateInputs();
  initialisePersonalIdValidation();
  initialiseRegistrationSteps();
  $$('.youtube-player').forEach(createYouTubePreview);
});
