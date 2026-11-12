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
async function fetchContent(endpoint: string, query: string): Promise<{ html?: string; title?: string; file?: string }> {
  const response = await fetch(`${endpoint}&${query}`, { credentials: 'same-origin' });
  const payload = await response.json() as { success: boolean; data?: { html?: string; title?: string; file?: string } };
  if (!response.ok || !payload.success || !payload.data) throw new Error('unavailable');
  return payload.data;
}

/** Content loaded later into a dialog gets the same enhancements as the page. */
function enhance(root: HTMLElement): void {
  $$('.youtube-player', root).forEach(createYouTubePreview);
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

/** Desktop navigation: one underline slides to the current section. */
function initialiseNavIndicator(): void {
  const list = $('[data-nav="desktop"]');
  if (!list) return;
  const links = $$<HTMLAnchorElement>('a', list);
  const indicator = document.createElement('span');
  indicator.className = 'tj:pointer-events-none tj:absolute tj:bottom-[.35rem] tj:left-0 tj:h-[2px] tj:w-0 tj:rounded-[2px] tj:bg-sun tj:opacity-0 tj:transition-[translate,width,opacity] tj:duration-300 tj:ease-soft tj:[&.is-active]:opacity-100';
  indicator.setAttribute('aria-hidden', 'true');
  list.append(indicator);

  const place = () => {
    const current = links.find((link) => link.getAttribute('aria-current') === 'true');
    indicator.classList.toggle('is-active', Boolean(current));
    if (!current) return;
    // Same inset as the link padding, so the line sits under the text only.
    const inset = Number.parseFloat(getComputedStyle(current).paddingLeft) || 0;
    indicator.style.translate = `${current.offsetLeft + inset}px 0`;
    indicator.style.width = `${Math.max(0, current.offsetWidth - inset * 2)}px`;
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

/**
 * Blocks ease in once as they reach the viewport. Nothing is hidden without
 * JavaScript, under reduced motion or when it is already on screen.
 */
function initialiseScrollReveal(): void {
  if (reducedMotion()) return;
  const reveal = (element: Element) => {
    element.classList.add('is-revealed');
    observer.unobserve(element);
  };
  const observer = new IntersectionObserver((entries) => entries.forEach((entry) => entry.isIntersecting && reveal(entry.target)), { rootMargin: '0px 0px 10% 0px' });
  const siblingIndex = new Map<Element, number>();
  $$('[data-reveal]').filter((element) => !element.closest('dialog')).forEach((element) => {
    if (element.getBoundingClientRect().top < window.innerHeight || !element.parentElement) return;
    const index = siblingIndex.get(element.parentElement) ?? 0;
    siblingIndex.set(element.parentElement, index + 1);
    element.style.setProperty('--tj-delay', `${Math.min(index, 4) * 50}ms`);
    element.classList.add('tj-reveal');
    observer.observe(element);
  });
  // A jump (menu link, back button) reveals everything above the landing point at once.
  window.addEventListener('hashchange', () => $$('.tj-reveal:not(.is-revealed)').forEach((element) => {
    if (element.getBoundingClientRect().top < window.innerHeight) reveal(element);
  }));
}

/* ---------- Homepage content ---------- */

/**
 * Paging carousel: the arrows and dots move by the number of visible cards,
 * the track follows a drag or swipe. Off-screen cards are inert.
 */
function initialisePagers(): void {
  $$('[data-pager]').forEach((pager) => {
    const track = $('[data-pager-track]', pager);
    const viewport = $('[data-pager-viewport]', pager);
    const previous = $<HTMLButtonElement>('[data-dir="prev"]', pager);
    const next = $<HTMLButtonElement>('[data-dir="next"]', pager);
    const status = $('[data-pager-status]', pager);
    if (!track || !viewport || !previous || !next || !status) return;
    const items = Array.from(track.children) as HTMLElement[];
    const label = document.createElement('span');
    label.className = 'tj:sr-only';
    let dots: HTMLButtonElement[] = [];
    let first = 0;
    let shownFirst = -1;

    const perView = () => Math.max(1, Math.round(Number.parseFloat(getComputedStyle(pager).getPropertyValue('--tj-per-view')) || 1));
    const offset = () => (items[first] ? items[first].offsetLeft - items[0].offsetLeft : 0);
    const go = (target: number) => {
      first = Math.max(0, Math.min(target, items.length - perView()));
      render();
    };
    const render = () => {
      const visible = perView();
      first = Math.min(first, Math.max(0, items.length - visible));
      // After a page change the newly shown cards follow the track one by one.
      if (shownFirst !== -1 && shownFirst !== first && !reducedMotion()) {
        const direction = first > shownFirst ? 1 : -1;
        items.slice(first, first + visible).forEach((item, index) => item.animate(
          [{ opacity: 0.35, translate: `${direction * 28}px 0` }, { opacity: 1, translate: '0 0' }],
          { duration: 420, delay: index * 45, easing: 'cubic-bezier(.2, .7, .2, 1)', fill: 'backwards' },
        ));
      }
      shownFirst = first;
      track.style.transform = `translateX(${-offset()}px)`;
      items.forEach((item, index) => { item.inert = index < first || index >= first + visible; });
      previous.disabled = first === 0;
      next.disabled = first + visible >= items.length;

      // One dot per page; the last page may overlap the previous one.
      const pages = Math.max(1, Math.ceil(items.length / visible));
      const page = first >= items.length - visible ? pages - 1 : Math.floor(first / visible);
      status.hidden = pages < 2;
      if (dots.length !== pages) {
        dots = Array.from({ length: pages }, (_, index) => {
          const dot = document.createElement('button');
          dot.type = 'button';
          dot.className = 'tj-pager-dot';
          dot.setAttribute('aria-label', `Stránka ${index + 1} z ${pages}`);
          dot.addEventListener('click', () => go(index * perView()));
          return dot;
        });
        status.replaceChildren(label, ...dots);
      }
      dots.forEach((dot, index) => (index === page ? dot.setAttribute('aria-current', 'true') : dot.removeAttribute('aria-current')));
      label.textContent = `Stránka ${page + 1} z ${pages}`;
    };
    previous.addEventListener('click', () => go(first - perView()));
    next.addEventListener('click', () => go(first + perView()));

    // Drag / swipe: horizontal movement drags the track, vertical stays a page scroll.
    let startX = 0;
    let startY = 0;
    let pointerId = -1;
    let dragging = false;
    let moved = false;
    viewport.addEventListener('pointerdown', (event) => {
      if (event.pointerType === 'mouse' && event.button !== 0) return;
      ({ clientX: startX, clientY: startY, pointerId } = event);
      dragging = moved = false;
    });
    viewport.addEventListener('pointermove', (event) => {
      if (event.pointerId !== pointerId) return;
      const dx = event.clientX - startX;
      if (!dragging) {
        if (Math.abs(dx) < 8 || Math.abs(dx) < Math.abs(event.clientY - startY)) return;
        dragging = moved = true;
        viewport.setPointerCapture(pointerId);
        viewport.classList.add('is-dragging');
        track.classList.add('is-dragging');
      }
      const resisting = (first === 0 && dx > 0) || (next.disabled && dx < 0);
      track.style.transform = `translateX(${-offset() + (resisting ? dx * 0.35 : dx)}px)`;
    });
    const finishDrag = (event: PointerEvent) => {
      if (event.pointerId !== pointerId) return;
      pointerId = -1;
      if (!dragging) return;
      dragging = false;
      viewport.classList.remove('is-dragging');
      track.classList.remove('is-dragging');
      const dx = event.clientX - startX;
      const threshold = Math.min(90, viewport.clientWidth * 0.18);
      go(dx < -threshold ? first + perView() : dx > threshold ? first - perView() : first);
      window.setTimeout(() => { moved = false; }, 80);
    };
    viewport.addEventListener('pointerup', finishDrag);
    viewport.addEventListener('pointercancel', finishDrag);
    // A drag must not also click the card underneath.
    viewport.addEventListener('click', (event) => {
      if (!moved) return;
      event.preventDefault();
      event.stopPropagation();
    }, true);
    viewport.addEventListener('dragstart', (event) => event.preventDefault());

    new ResizeObserver(render).observe(pager);
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

/**
 * Pro rodiče: the documents card is as tall as the FAQ card with every item
 * closed, and keeps that height while an item is open.
 */
function initialiseCollapsedEqualHeight(): void {
  $$('[data-collapsed-equal]').forEach((container) => {
    const main = container.firstElementChild as HTMLElement | null;
    if (!main) return;
    const syncOpen = () => container.classList.toggle('is-faq-open', $('details[open]', main) !== null);
    // `toggle` does not bubble; listen in the capture phase.
    main.addEventListener('toggle', syncOpen, true);
    syncOpen();
    const measure = () => {
      if (getComputedStyle(container).gridTemplateColumns.split(' ').length < 2) {
        container.style.removeProperty('--tj-collapsed');
        return;
      }
      const extra = $$<HTMLDetailsElement>('details', main).reduce((sum, item) => {
        const summary = $('summary', item);
        return sum + (summary ? item.getBoundingClientRect().height - summary.getBoundingClientRect().height : 0);
      }, 0);
      container.style.setProperty('--tj-collapsed', `${Math.round(main.getBoundingClientRect().height - extra)}px`);
    };
    const observer = new ResizeObserver(measure);
    observer.observe(container);
    observer.observe(main);
  });
}

/* ---------- Dialogs ---------- */

function initialiseDialogs(): void {
  const openers = new WeakMap<HTMLDialogElement, { opener: HTMLElement; pointer: boolean }>();
  // Openers link to the real URL, so content stays reachable without JavaScript.
  $$('[data-dialog-open]').forEach((opener) => {
    const dialog = document.getElementById(opener.dataset.dialogOpen ?? '');
    if (!(dialog instanceof HTMLDialogElement)) return;
    opener.setAttribute('aria-haspopup', 'dialog');
    opener.addEventListener('click', (event) => {
      if (event.ctrlKey || event.metaKey || event.shiftKey || event.button !== 0) return;
      event.preventDefault();
      // `detail` counts pointer clicks; a keyboard-activated link reports 0.
      openers.set(dialog, { opener, pointer: event.detail > 0 });
      dialog.showModal();
      document.documentElement.classList.add('has-dialog');
    });
  });
  $$<HTMLDialogElement>('.tj-dialog').forEach((dialog) => {
    dialog.addEventListener('close', () => {
      if (!$('.tj-dialog[open]')) document.documentElement.classList.remove('has-dialog');
      // Stop a trailer started inside the dialog by restoring its preview.
      $$('.youtube-player', dialog).forEach((player) => { if ($('iframe', player)) createYouTubePreview(player); });
      const last = openers.get(dialog);
      openers.delete(dialog);
      // Dialogs closed with the mouse return focus without a visible ring.
      last?.opener.focus({ preventScroll: true, focusVisible: !last.pointer } as FocusOptions);
    });
    dialog.addEventListener('click', (event) => { if (event.target === dialog) dialog.close(); });
  });
  // Close buttons of dialogs and of the form notification (template-parts/notifications.php).
  document.addEventListener('click', (event) => {
    const button = event.target instanceof Element ? event.target.closest('[data-dialog-close]') : null;
    if (!button) return;
    const dialog = button.closest('dialog');
    if (dialog) dialog.close();
    else button.closest('[role="dialog"]')?.remove();
  });
}

/**
 * Homepage: a link to another page of this site opens its content in a
 * dialog. Files, the registration form, admin and the homepage stay normal
 * links; an attachment page opens its file; on failure the link navigates.
 */
function initialiseInternalLinks(): void {
  const dialog = $<HTMLDialogElement>('[data-page-dialog]');
  const title = $('#page-dialog-title');
  const body = $('[data-page-dialog-body]');
  const endpoint = dialog?.dataset.postContentUrl;
  if (!dialog || !title || !body || !endpoint || !document.body.classList.contains('home')) return;

  // The homepage path is the site root ("/" in production, "/test/" on stage).
  const homePath = window.location.pathname.replace(/\/?$/, '/');
  const opensHere = (link: HTMLAnchorElement): URL | null => {
    if (link.hasAttribute('data-dialog-open') || link.hasAttribute('download') || link.closest('.tj-lightbox')) return null;
    let url: URL;
    try { url = new URL(link.href); } catch { return null; }
    if (url.origin !== window.location.origin || !url.pathname.startsWith(homePath)) return null;
    // Path relative to the site root, without the "index.php/" of stage permalinks.
    const path = '/' + url.pathname.slice(homePath.length).replace(/^index\.php(?:\/|$)/, '');
    if (path === '/' || /^\/(?:wp-admin|wp-content|wp-includes|wp-json|feed|e-prihlaska|odeslat-dotaz)(?:\/|$)/.test(path) || /\.[a-z0-9]{2,5}$/i.test(path)) return null;
    return url;
  };

  let request = 0;
  let opener: HTMLAnchorElement | null = null;
  document.addEventListener('click', async (event) => {
    if (event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
    const link = event.target instanceof Element ? event.target.closest<HTMLAnchorElement>('a[href]') : null;
    const url = link && opensHere(link);
    if (!link || !url) return;
    event.preventDefault();
    if (!dialog.open) {
      opener = link;
      dialog.showModal();
      document.documentElement.classList.add('has-dialog');
    }
    const current = ++request;
    title.textContent = link.textContent?.trim() || 'Táborová jízda';
    body.innerHTML = '<p class="tj:text-muted">Načítám…</p>';
    body.setAttribute('aria-busy', 'true');
    dialog.scrollTop = 0;
    try {
      const data = await fetchContent(endpoint, `url=${encodeURIComponent(url.href)}`);
      if (current !== request) return;
      if (data.file) {
        // An attachment page: open the document itself and leave the homepage as it is.
        dialog.close();
        window.open(data.file, '_blank', 'noopener');
        return;
      }
      if (!data.html) throw new Error('empty');
      title.textContent = data.title || title.textContent;
      body.innerHTML = data.html;
      enhance(body);
    } catch {
      if (current === request) window.location.href = url.href;
    } finally {
      if (current === request) body.removeAttribute('aria-busy');
    }
  });
  dialog.addEventListener('close', () => {
    request++;
    if (opener?.isConnected) opener.focus();
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

  const show = (target: number) => {
    index = (target + items.length) % items.length;
    const current = items[index];
    image.src = largestSource(current);
    image.classList.remove('is-swapping');
    void image.offsetWidth;
    image.classList.add('is-swapping');
    image.alt = current.alt;
    caption.textContent = current.closest('figure')?.querySelector('figcaption')?.textContent?.trim() || current.alt || '';
    caption.hidden = !caption.textContent;
    const single = items.length < 2;
    previous.hidden = next.hidden = single;
    counter.textContent = single ? '' : `${index + 1} / ${items.length}`;
    [items[index + 1], items[index - 1]].forEach((neighbour) => { if (neighbour) new Image().src = largestSource(neighbour); });
  };
  const open = (img: HTMLImageElement) => {
    // Browsing scope: all team portraits for a profile photo, otherwise every visible
    // photo of the surrounding dialog or section (collapsed articles are skipped).
    const scope: ParentNode = img.closest('[data-zoom="team"]')
      ? ($('#people-track') ?? document)
      : (img.closest('dialog') ?? img.closest('section') ?? document);
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
    if (event.key === 'ArrowLeft') show(index - 1);
    if (event.key === 'ArrowRight') show(index + 1);
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

/** Three validated steps; without JavaScript the whole form stays on one page. */
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

  const show = (index: number, focus = true) => {
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
      formFields(steps[index])[0]?.focus({ preventScroll: true });
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
  initialisePagers();
  initialiseExclusiveDetails();
  initialiseSeasonArticles();
  initialiseCollapsedEqualHeight();
  initialiseLightbox();
  initialiseScrollReveal();
  initialiseDialogs();
  initialiseInternalLinks();
  initialiseExternalLinks();
  initialiseCzechDateInputs();
  initialisePersonalIdValidation();
  initialiseRegistrationSteps();
  $$('.youtube-player').forEach(createYouTubePreview);
});
