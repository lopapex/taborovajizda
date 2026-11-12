import '../css/app.css';

function initialiseMobileMenu(): void {
  document.querySelectorAll<HTMLElement>('[data-mobile-menu]').forEach((container) => {
    const button = container.querySelector<HTMLButtonElement>('[data-menu-toggle]');
    const panel = container.querySelector<HTMLElement>('[data-menu-panel]') ?? button?.nextElementSibling;
    if (!button || !(panel instanceof HTMLElement)) return;

    button.addEventListener('click', () => {
      const open = button.getAttribute('aria-expanded') !== 'true';
      container.classList.toggle('is-open', open);
      button.classList.toggle('tj:rotate-90', open);
      button.querySelector('span')?.classList.toggle('tj:hidden', open);
      panel.classList.toggle('tj:hidden', !open);
      panel.classList.toggle('tj:flex', open);
      panel.hidden = !open;
      button.setAttribute('aria-expanded', String(open));
    });

    panel.querySelectorAll<HTMLAnchorElement>('.menu-item-has-children > a').forEach((link) => {
      const submenu = link.nextElementSibling;
      if (!(submenu instanceof HTMLElement)) return;
      submenu.classList.add('tj:hidden');
      link.setAttribute('aria-expanded', 'false');
      link.addEventListener('click', (event) => {
        const open = link.getAttribute('aria-expanded') === 'true';
        if (open) return;
        event.preventDefault();
        submenu.classList.remove('tj:hidden');
        submenu.classList.add('tj:block');
        link.setAttribute('aria-expanded', 'true');
      });
    });

    panel.querySelectorAll<HTMLAnchorElement>('a').forEach((link) => {
      link.addEventListener('click', () => {
        container.classList.remove('is-open');
        button.classList.remove('tj:rotate-90');
        panel.classList.add('tj:hidden');
        panel.classList.remove('tj:flex');
        panel.hidden = true;
        button.setAttribute('aria-expanded', 'false');
      });
    });
  });
}

function initialiseNavScrollSpy(): void {
  const links = Array.from(document.querySelectorAll<HTMLAnchorElement>('.tj-home-nav__links a, .tj-home-nav-panel a'))
    .filter((link) => link.hash && link.pathname === window.location.pathname);
  const sections = Array.from(new Set(links.map((link) => link.hash)))
    .map((hash) => document.getElementById(hash.slice(1))?.closest<HTMLElement>('section'))
    .filter((section): section is HTMLElement => section instanceof HTMLElement);
  if (sections.length === 0 || !('IntersectionObserver' in window)) return;

  const sectionHash = new Map<HTMLElement, string>();
  links.forEach((link) => {
    const section = document.getElementById(link.hash.slice(1))?.closest<HTMLElement>('section');
    if (section) sectionHash.set(section, link.hash);
  });
  const visible = new Set<HTMLElement>();
  const update = () => {
    // The topmost visible section wins.
    const current = sections.find((section) => visible.has(section));
    const hash = current ? sectionHash.get(current) : undefined;
    links.forEach((link) => {
      if (hash && link.hash === hash) link.setAttribute('aria-current', 'true');
      else link.removeAttribute('aria-current');
    });
  };
  // A section counts as current while it crosses the band just below the sticky header.
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) visible.add(entry.target as HTMLElement);
      else visible.delete(entry.target as HTMLElement);
    });
    update();
  }, { rootMargin: '-90px 0px -55% 0px' });
  sections.forEach((section) => observer.observe(section));
}

/**
 * Paging carousel: no scrollbar or swipe. The arrows move by the number of
 * visible cards, or by what is left at the end. Off-screen cards are inert so
 * keyboard focus never lands on a hidden card.
 */
function initialisePagers(): void {
  document.querySelectorAll<HTMLElement>('[data-pager]').forEach((pager) => {
    const track = pager.querySelector<HTMLElement>('[data-pager-track]');
    const previous = pager.querySelector<HTMLButtonElement>('[data-pager-prev]');
    const next = pager.querySelector<HTMLButtonElement>('[data-pager-next]');
    const status = pager.querySelector<HTMLElement>('[data-pager-status]');
    if (!track || !previous || !next) return;
    const items = Array.from(track.children) as HTMLElement[];
    let first = 0;
    let dots: HTMLButtonElement[] = [];
    const label = document.createElement('span');
    label.className = 'tj-home-visually-hidden';

    const perView = () => Math.max(1, Math.round(Number.parseFloat(getComputedStyle(pager).getPropertyValue('--per-view')) || 1));
    let shownFirst = -1;
    const render = () => {
      const visible = perView();
      first = Math.min(first, Math.max(0, items.length - visible));
      // After a page change the newly shown cards follow the track one by one (Web Animations, transform-free `translate`).
      if (shownFirst !== -1 && shownFirst !== first && !prefersReducedMotion()) {
        const direction = first > shownFirst ? 1 : -1;
        items.slice(first, first + visible).forEach((item, index) => {
          item.animate(
            [{ opacity: 0.35, translate: `${direction * 28}px 0` }, { opacity: 1, translate: '0 0' }],
            { duration: 420, delay: index * 45, easing: 'cubic-bezier(.2, .7, .2, 1)', fill: 'backwards' },
          );
        });
      }
      shownFirst = first;
      const offset = items[first] ? items[first].offsetLeft - items[0].offsetLeft : 0;
      track.style.transform = `translateX(${-offset}px)`;
      items.forEach((item, index) => { item.inert = index < first || index >= first + visible; });
      previous.disabled = first === 0;
      next.disabled = first + visible >= items.length;
      // Pagination dots: one per page; the last page may overlap the previous one.
      const pages = Math.max(1, Math.ceil(items.length / visible));
      const page = first >= items.length - visible ? pages - 1 : Math.floor(first / visible);
      if (status) {
        status.hidden = pages < 2;
        if (dots.length !== pages) {
          dots = Array.from({ length: pages }, (_, index) => {
            const dot = document.createElement('button');
            dot.type = 'button';
            dot.className = 'tj-home-pager__dot';
            dot.setAttribute('aria-label', `Stránka ${index + 1} z ${pages}`);
            dot.addEventListener('click', () => {
              first = Math.min(index * perView(), Math.max(0, items.length - perView()));
              render();
            });
            return dot;
          });
          status.replaceChildren(label, ...dots);
        }
        dots.forEach((dot, index) => {
          if (index === page) dot.setAttribute('aria-current', 'true');
          else dot.removeAttribute('aria-current');
        });
        label.textContent = `Stránka ${page + 1} z ${pages}`;
      }
    };
    previous.addEventListener('click', () => { first = Math.max(0, first - perView()); render(); });
    next.addEventListener('click', () => { first = Math.min(items.length - perView(), first + perView()); render(); });
    // Drag / swipe: the track follows the finger or mouse, then snaps to the next or previous page.
    const viewport = pager.querySelector<HTMLElement>('.tj-home-pager__viewport');
    if (viewport) {
      let startX = 0;
      let startY = 0;
      let pointerId = -1;
      let dragging = false;
      let moved = false;
      let baseOffset = 0;
      const currentOffset = () => (items[first] ? items[first].offsetLeft - items[0].offsetLeft : 0);

      viewport.addEventListener('pointerdown', (event) => {
        if (event.pointerType === 'mouse' && event.button !== 0) return;
        startX = event.clientX;
        startY = event.clientY;
        pointerId = event.pointerId;
        dragging = false;
        moved = false;
        baseOffset = currentOffset();
      });
      viewport.addEventListener('pointermove', (event) => {
        if (event.pointerId !== pointerId) return;
        const dx = event.clientX - startX;
        const dy = event.clientY - startY;
        if (!dragging) {
          // Small or mostly vertical movement is a click or a page scroll, not a drag.
          if (Math.abs(dx) < 8 || Math.abs(dx) < Math.abs(dy)) return;
          dragging = true;
          moved = true;
          viewport.setPointerCapture(event.pointerId);
          track.classList.add('is-dragging');
          viewport.classList.add('is-dragging');
        }
        const resisting = (first === 0 && dx > 0) || (next.disabled && dx < 0);
        track.style.transform = `translateX(${-baseOffset + (resisting ? dx * 0.35 : dx)}px)`;
      });
      const finishDrag = (event: PointerEvent) => {
        if (event.pointerId !== pointerId) return;
        pointerId = -1;
        if (!dragging) return;
        dragging = false;
        const dx = event.clientX - startX;
        track.classList.remove('is-dragging');
        viewport.classList.remove('is-dragging');
        const threshold = Math.min(90, viewport.clientWidth * 0.18);
        if (dx < -threshold && !next.disabled) first = Math.max(0, Math.min(items.length - perView(), first + perView()));
        else if (dx > threshold && !previous.disabled) first = Math.max(0, first - perView());
        render();
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
    }

    new ResizeObserver(render).observe(pager);
    render();
  });
}

/**
 * Past season articles expand inline inside the season dialog. Only one article
 * is open at a time and its content is fetched the first time it is opened.
 */
function initialiseSeasonArticles(): void {
  document.querySelectorAll<HTMLElement>('[data-exclusive-accordion]').forEach((group) => {
    const endpoint = group.closest<HTMLElement>('[data-post-content-url]')?.dataset.postContentUrl;
    const items = Array.from(group.querySelectorAll<HTMLDetailsElement>(':scope > details'));

    const load = async (body: HTMLElement) => {
      if (!endpoint || body.dataset.loaded) return;
      body.dataset.loaded = 'loading';
      const fallback = body.innerHTML;
      body.setAttribute('aria-busy', 'true');
      body.innerHTML = '<p class="tj-home-season-article__status">Načítám článek…</p>';
      try {
        const response = await fetch(`${endpoint}&id=${encodeURIComponent(body.dataset.postId ?? '')}`, { credentials: 'same-origin' });
        const payload = await response.json() as { success: boolean; data?: { html?: string } };
        if (!response.ok || !payload.success || !payload.data?.html) throw new Error('unavailable');
        body.innerHTML = payload.data.html;
        delete body.dataset.error;
        body.dataset.loaded = 'true';
        body.querySelectorAll<HTMLElement>('.youtube-player').forEach(createYouTubePreview);
        markZoomable(body);
      } catch {
        body.innerHTML = `<p class="tj-home-season-article__status">Článek se nepodařilo načíst.</p>${fallback}`;
        body.dataset.error = 'true';
        delete body.dataset.loaded;
      } finally {
        body.removeAttribute('aria-busy');
      }
    };

    items.forEach((item) => {
      item.addEventListener('toggle', () => {
        if (!item.open) return;
        // Fallback for browsers without exclusive <details name>.
        items.forEach((other) => { if (other !== item) other.open = false; });
        const body = item.querySelector<HTMLElement>('[data-article-body]');
        if (body) void load(body);
        // Closing the previous article shifts the list; keep the opened one pinned near the top.
        const dialog = item.closest<HTMLElement>('dialog');
        const summary = item.querySelector<HTMLElement>('summary');
        if (dialog && summary) {
          // Reading the rects forces layout after the other articles closed.
          dialog.scrollTop += summary.getBoundingClientRect().top - dialog.getBoundingClientRect().top - 16;
        }
      });
    });
  });
}

/** Accordions where only one item may be open (FAQ). */
function initialiseExclusiveDetails(): void {
  document.querySelectorAll<HTMLElement>('[data-exclusive-details]').forEach((group) => {
    const items = Array.from(group.querySelectorAll<HTMLDetailsElement>('details'));
    items.forEach((item) => item.addEventListener('toggle', () => {
      if (!item.open) return;
      items.forEach((other) => { if (other !== item) other.open = false; });
    }));
  });
}

const zoomableSelector = '.tj-home-prose img, .tj-home-person-dialog__photo img, .tj-home-intro__media img, .tj-home-news-dialog__image img';

function isZoomable(img: HTMLImageElement): boolean {
  return img.matches(zoomableSelector)
    && !img.classList.contains('emoji')
    && !img.closest('.youtube-player')
    // Card thumbnails open their dialog instead; photos inside a card's dialog are zoomable.
    && (img.closest('dialog') !== null || !img.closest('.tj-home-post-card, .tj-home-person-card, .tj-home-activity-card, .tj-home-archive-card'));
}

/** Best available source: a linked image file, the widest srcset entry, or the current source. */
function largestSource(img: HTMLImageElement): string {
  const link = img.closest('a');
  if (link && /\.(?:jpe?g|png|webp|gif|avif)(?:[?#].*)?$/i.test(link.href)) return link.href;
  const candidates = (img.getAttribute('srcset') ?? '')
    .split(',')
    .map((entry) => entry.trim().split(/\s+/))
    .filter(([url, width]) => Boolean(url) && Boolean(width?.endsWith('w')))
    .map(([url, width]) => ({ url, width: Number.parseInt(width, 10) }))
    .sort((a, b) => b.width - a.width);
  return candidates[0]?.url ?? (img.currentSrc || img.src);
}

/** Makes zoomable images inside root reachable and operable from the keyboard. */
function markZoomable(root: ParentNode): void {
  root.querySelectorAll<HTMLImageElement>(zoomableSelector).forEach((img) => {
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
 * One photo viewer for the whole redesign: always on top (its own modal dialog,
 * so it stacks above open dialogs), never navigates away and replaces plugin
 * lightboxes for images in our content.
 */
function initialiseLightbox(): void {
  if (!document.body.classList.contains('tj-home')) return;

  const viewer = document.createElement('dialog');
  viewer.className = 'tj-lightbox';
  viewer.setAttribute('aria-label', 'Prohlížení fotografií');
  viewer.innerHTML = [
    '<figure class="tj-lightbox__stage"><img class="tj-lightbox__image" alt=""><figcaption class="tj-lightbox__caption"></figcaption></figure>',
    '<button class="tj-lightbox__button tj-lightbox__button--close" type="button" aria-label="Zavřít prohlížení"></button>',
    '<button class="tj-lightbox__button tj-lightbox__button--prev" type="button" aria-label="Předchozí fotografie"></button>',
    '<button class="tj-lightbox__button tj-lightbox__button--next" type="button" aria-label="Další fotografie"></button>',
    '<p class="tj-lightbox__counter" aria-live="polite"></p>',
  ].join('');
  document.body.append(viewer);
  const image = viewer.querySelector<HTMLImageElement>('.tj-lightbox__image')!;
  const caption = viewer.querySelector<HTMLElement>('.tj-lightbox__caption')!;
  const counter = viewer.querySelector<HTMLElement>('.tj-lightbox__counter')!;
  const stage = viewer.querySelector<HTMLElement>('.tj-lightbox__stage')!;
  const previous = viewer.querySelector<HTMLButtonElement>('.tj-lightbox__button--prev')!;
  const next = viewer.querySelector<HTMLButtonElement>('.tj-lightbox__button--next')!;
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
    const figcaption = current.closest('figure')?.querySelector('figcaption')?.textContent?.trim();
    caption.textContent = figcaption || current.alt || '';
    caption.hidden = !caption.textContent;
    const single = items.length < 2;
    previous.hidden = single;
    next.hidden = single;
    counter.textContent = single ? '' : `${index + 1} / ${items.length}`;
    [items[index + 1], items[index - 1]].forEach((neighbour) => { if (neighbour) new Image().src = largestSource(neighbour); });
  };
  const open = (img: HTMLImageElement) => {
    // Browsing scope: all team portraits for a profile photo, otherwise every photo of the
    // surrounding dialog (e.g. all activities of a group) or section. Photos inside a
    // collapsed article are skipped, so only what the visitor can see is browsable.
    const scope: ParentNode = img.closest('.tj-home-person-dialog__photo')
      ? (document.querySelector('.tj-home-section--people') ?? document)
      : (img.closest('dialog') ?? img.closest('section') ?? document);
    items = Array.from(scope.querySelectorAll<HTMLImageElement>(zoomableSelector))
      .filter((candidate) => isZoomable(candidate) && !candidate.closest('details:not([open])'));
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
  viewer.querySelector('.tj-lightbox__button--close')?.addEventListener('click', () => viewer.close());
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

function prefersReducedMotion(): boolean {
  return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

/** Desktop navigation: one underline slides to the current section instead of each link drawing its own. */
function initialiseNavIndicator(): void {
  const list = document.querySelector<HTMLElement>('.tj-home-nav__links');
  if (!list) return;
  const links = Array.from(list.querySelectorAll<HTMLAnchorElement>('a'));
  const indicator = document.createElement('span');
  indicator.className = 'tj-home-nav__indicator';
  indicator.setAttribute('aria-hidden', 'true');
  list.append(indicator);
  document.documentElement.classList.add('has-nav-indicator');

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
  place();
}

/** "Back to top" appears once the visitor is about two screens down (sentinel, no scroll listener). */
function initialiseBackToTop(): void {
  const button = document.querySelector<HTMLAnchorElement>('[data-to-top]');
  if (!button || !('IntersectionObserver' in window)) return;
  const sentinel = document.createElement('div');
  sentinel.setAttribute('aria-hidden', 'true');
  sentinel.style.cssText = 'position:absolute;top:0;left:0;width:1px;height:200vh;pointer-events:none;';
  document.body.prepend(sentinel);
  new IntersectionObserver(([entry]) => button.classList.toggle('is-shown', !entry.isIntersecting)).observe(sentinel);
  button.addEventListener('click', (event) => {
    event.preventDefault();
    window.scrollTo({ top: 0, behavior: prefersReducedMotion() ? 'auto' : 'smooth' });
    // Move focus to the content start for keyboard and screen reader users, without a second jump.
    document.getElementById('main-content')?.focus({ preventScroll: true });
  });
}

const revealSelector = [
  '.tj-home-section__heading',
  '.tj-home-intro__text',
  '.tj-home-intro__media',
  '.tj-home-activity-card',
  '.tj-home-pager',
  '.tj-home-panel',
  '.tj-home-contact-form',
  '.tj-home-contact-details',
  '.tj-home-contact-social-row',
  '.tj-home-map',
].join(', ');

/**
 * Sections ease in once as they reach the viewport (hierarchy: the eye is led
 * down the page). Uses the individual `translate` property, so it never fights
 * the hover transforms of cards. Nothing is hidden without JavaScript or when
 * the visitor prefers reduced motion.
 */
function initialiseScrollReveal(): void {
  if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  const targets = Array.from(document.querySelectorAll<HTMLElement>(revealSelector))
    .filter((element) => !element.closest('dialog'));
  if (targets.length === 0) return;

  const siblingIndex = new Map<Element, number>();
  targets.forEach((element) => {
    // Anything already on screen or above it (reload mid-page, anchor jump) stays visible: no flash.
    if (element.getBoundingClientRect().top < window.innerHeight) return;
    const parent = element.parentElement;
    if (!parent) return;
    const index = siblingIndex.get(parent) ?? 0;
    siblingIndex.set(parent, index + 1);
    element.style.setProperty('--tj-reveal-delay', `${Math.min(index, 4) * 50}ms`);
    element.classList.add('tj-reveal');
  });

  const revealed = (element: Element) => {
    element.classList.add('is-revealed');
    observer.unobserve(element);
  };
  // Starts slightly before the element enters, so a fast scroll never shows an empty band.
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) revealed(entry.target);
    });
  }, { rootMargin: '0px 0px 10% 0px', threshold: 0 });
  document.querySelectorAll('.tj-reveal').forEach((element) => observer.observe(element));
  // A jump (menu link, back button) reveals everything above the landing point at once.
  window.addEventListener('hashchange', () => {
    document.querySelectorAll('.tj-reveal:not(.is-revealed)').forEach((element) => {
      if (element.getBoundingClientRect().top < window.innerHeight) revealed(element);
    });
  });
}

/**
 * The link row never wraps: when logo, links and the registration button do not
 * fit on one line, the header switches to the hamburger layout used on phones.
 * Re-measured on every width change and once webfonts have loaded.
 */
function initialiseResponsiveNav(): void {
  const header = document.querySelector<HTMLElement>('.tj-home-header');
  const inner = header?.querySelector<HTMLElement>('.tj-home-header__inner');
  const nav = header?.querySelector<HTMLElement>('.tj-home-nav');
  if (!header || !inner || !nav) return;

  const apply = () => {
    header.classList.remove('is-compact');
    // At phone widths CSS already hides the link row and shows the hamburger.
    if (getComputedStyle(nav).display === 'none') return;
    header.classList.toggle('is-compact', inner.scrollWidth > inner.clientWidth + 1);
  };
  new ResizeObserver(apply).observe(inner);
  void document.fonts?.ready.then(apply);
  apply();
}

/** Header gets a stronger shadow and a smaller logo once the page is scrolled (sentinel, no scroll listener). */
function initialiseHeaderState(): void {
  const header = document.querySelector<HTMLElement>('.tj-home-header');
  if (!header || !('IntersectionObserver' in window)) return;
  const sentinel = document.createElement('div');
  sentinel.setAttribute('aria-hidden', 'true');
  sentinel.style.cssText = 'position:absolute;top:0;left:0;width:1px;height:64px;pointer-events:none;';
  document.body.prepend(sentinel);
  new IntersectionObserver(([entry]) => header.classList.toggle('is-scrolled', !entry.isIntersecting)).observe(sentinel);
}

/**
 * Pro rodiče: the documents card is as tall as the FAQ card with every item
 * closed. The collapsed height is computed from the current height minus what
 * open items add, so it stays correct while an item is animating. While an
 * item is open the container gets .is-faq-open and the CSS moves the
 * "Nenašli jste odpověď?" strip under the documents card.
 */
function initialiseCollapsedEqualHeight(): void {
  document.querySelectorAll<HTMLElement>('[data-collapsed-equal]').forEach((container) => {
    const main = container.firstElementChild as HTMLElement | null;
    if (!main) return;
    const syncOpen = () => container.classList.toggle('is-faq-open', main.querySelector('details[open]') !== null);
    // `toggle` does not bubble; listen in the capture phase for every details element.
    main.addEventListener('toggle', syncOpen, true);
    syncOpen();
    const measure = () => {
      if (getComputedStyle(container).gridTemplateColumns.split(' ').length < 2) {
        container.style.removeProperty('--tj-collapsed-height');
        return;
      }
      const extra = Array.from(main.querySelectorAll<HTMLDetailsElement>('details')).reduce((sum, item) => {
        const summary = item.querySelector('summary');
        return sum + (summary ? item.getBoundingClientRect().height - summary.getBoundingClientRect().height : 0);
      }, 0);
      container.style.setProperty('--tj-collapsed-height', `${Math.round(main.getBoundingClientRect().height - extra)}px`);
    };
    new ResizeObserver(measure).observe(container);
    new ResizeObserver(measure).observe(main);
    measure();
  });
}

function initialiseArchiveExpand(): void {
  document.querySelectorAll<HTMLElement>('[data-archive-section]').forEach((section) => {
    const button = section.querySelector<HTMLButtonElement>('[data-archive-expand]');
    if (!button) return;
    button.addEventListener('click', () => {
      const expanded = button.getAttribute('aria-expanded') !== 'true';
      section.classList.toggle('archive-expanded', expanded);
      button.setAttribute('aria-expanded', String(expanded));
      button.textContent = (expanded ? button.dataset.labelLess : button.dataset.labelMore) ?? button.textContent;
      if (!expanded) section.scrollIntoView({ block: 'start', behavior: 'auto' });
    });
  });
}

function initialiseActivityTabs(): void {
  document.querySelectorAll<HTMLElement>('[data-activity-tabs]').forEach((container) => {
    const tabs = Array.from(container.querySelectorAll<HTMLButtonElement>('[data-activity-tab]'));
    const panels = Array.from(container.querySelectorAll<HTMLElement>('[role="tabpanel"]'));
    if (tabs.length === 0 || panels.length === 0) return;

    document.documentElement.classList.add('has-activity-tabs');
    const activate = (tab: HTMLButtonElement) => {
      const panelId = tab.dataset.activityTab;
      if (!panelId) return;
      tabs.forEach((item) => {
        const active = item === tab;
        item.classList.toggle('is-active', active);
        item.setAttribute('aria-selected', String(active));
        item.tabIndex = active ? 0 : -1;
      });
      panels.forEach((panel) => panel.classList.toggle('is-active', panel.id === panelId));
    };

    tabs.forEach((tab, index) => {
      tab.addEventListener('click', () => activate(tab));
      tab.addEventListener('keydown', (event) => {
        if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
        event.preventDefault();
        const targetIndex = event.key === 'Home' ? 0 : event.key === 'End' ? tabs.length - 1 : (index + (event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length;
        const target = tabs[targetIndex];
        target.focus();
        activate(target);
      });
    });
  });
}

function initialiseHorizontalCarousels(): void {
  document.querySelectorAll<HTMLElement>('[data-horizontal-carousel]').forEach((carousel) => {
    const wrapper = carousel.closest<HTMLElement>('.tj-home-carousel');
    const previous = wrapper?.querySelector<HTMLButtonElement>('[data-carousel-prev]');
    const next = wrapper?.querySelector<HTMLButtonElement>('[data-carousel-next]');
    const reducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const step = () => {
      const card = carousel.firstElementChild;
      if (!(card instanceof HTMLElement)) return carousel.clientWidth;
      const gap = Number.parseFloat(getComputedStyle(carousel).gap) || 0;
      return card.getBoundingClientRect().width + gap;
    };
    const updateButtons = () => {
      const maximum = carousel.scrollWidth - carousel.clientWidth;
      if (previous) previous.disabled = carousel.scrollLeft <= 1;
      if (next) next.disabled = carousel.scrollLeft >= maximum - 1;
    };
    const move = (direction: 1 | -1) => {
      carousel.scrollBy({ left: direction * step(), behavior: reducedMotion() ? 'auto' : 'smooth' });
    };

    previous?.addEventListener('click', () => move(-1));
    next?.addEventListener('click', () => move(1));
    carousel.addEventListener('keydown', (event) => {
      if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;
      event.preventDefault();
      move(event.key === 'ArrowRight' ? 1 : -1);
    });
    carousel.addEventListener('scroll', updateButtons, { passive: true });
    new ResizeObserver(updateButtons).observe(carousel);
    updateButtons();
  });
}

/**
 * Homepage: a link to another page of this site (for example the packing list
 * inside the FAQ or a document page) opens its content in a dialog instead of
 * leaving the page. Files, the registration form, admin and the homepage itself
 * stay normal links; an attachment page opens its file; if the content cannot
 * be loaded the link works as before.
 */
function initialiseInternalLinks(): void {
  const dialog = document.querySelector<HTMLDialogElement>('[data-page-dialog]');
  const title = dialog?.querySelector<HTMLElement>('[data-page-dialog-title]');
  const body = dialog?.querySelector<HTMLElement>('[data-page-dialog-body]');
  const endpoint = dialog?.dataset.postContentUrl;
  if (!dialog || !title || !body || !endpoint || !document.body.classList.contains('home')) return;

  const homePath = window.location.pathname;
  const opensHere = (link: HTMLAnchorElement): URL | null => {
    if (link.hasAttribute('data-home-dialog-open') || link.hasAttribute('download') || link.closest('.tj-lightbox')) return null;
    let url: URL;
    try { url = new URL(link.href); } catch { return null; }
    if (url.origin !== window.location.origin || !/^https?:$/.test(url.protocol)) return null;
    const path = url.pathname;
    if (path === homePath || path === '/') return null;
    if (/^\/(?:wp-admin|wp-content|wp-includes|wp-json|feed|e-prihlaska)(?:\/|$)/.test(path)) return null;
    if (/\.[a-z0-9]{2,5}$/i.test(path)) return null;
    return url;
  };

  let request = 0;
  const show = async (url: URL, opener: HTMLAnchorElement) => {
    const current = ++request;
    title.textContent = opener.textContent?.trim() || 'Táborová jízda';
    body.innerHTML = '<p class="tj-home-season-article__status">Načítám…</p>';
    body.setAttribute('aria-busy', 'true');
    if (!dialog.open) {
      dialog.showModal();
      document.documentElement.classList.add('has-home-dialog');
    }
    dialog.scrollTop = 0;
    try {
      const response = await fetch(`${endpoint}&url=${encodeURIComponent(url.href)}`, { credentials: 'same-origin' });
      const payload = await response.json() as { success: boolean; data?: { html?: string; title?: string; file?: string } };
      if (current !== request) return;
      if (!response.ok || !payload.success) throw new Error('unavailable');
      if (payload.data?.file) {
        // An attachment page: open the document itself and leave the homepage as it is.
        dialog.close();
        window.open(payload.data.file, '_blank', 'noopener');
        return;
      }
      if (!payload.data?.html) throw new Error('unavailable');
      title.textContent = payload.data.title || title.textContent;
      body.innerHTML = payload.data.html;
      body.querySelectorAll<HTMLElement>('.youtube-player').forEach(createYouTubePreview);
      markZoomable(body);
    } catch {
      if (current === request) window.location.href = url.href;
    } finally {
      if (current === request) body.removeAttribute('aria-busy');
    }
  };

  let opener: HTMLAnchorElement | null = null;
  document.addEventListener('click', (event) => {
    if (event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
    const link = event.target instanceof Element ? event.target.closest<HTMLAnchorElement>('a[href]') : null;
    if (!link) return;
    const url = opensHere(link);
    if (!url) return;
    event.preventDefault();
    if (!dialog.open) opener = link;
    void show(url, link);
  });
  dialog.addEventListener('close', () => {
    request++;
    if (opener?.isConnected) opener.focus();
    opener = null;
  });
}

/**
 * Every link to another website opens in a new tab, wherever it comes from
 * (templates, WordPress content, widgets or content loaded later into a
 * dialog). Existing links are marked on load; anything added later is marked
 * at click time, before the browser follows it.
 */
function initialiseExternalLinks(): void {
  const isExternal = (link: HTMLAnchorElement): boolean => {
    try {
      const url = new URL(link.href);
      return /^https?:$/.test(url.protocol) && url.origin !== window.location.origin;
    } catch {
      return false;
    }
  };
  const mark = (link: HTMLAnchorElement) => {
    link.target = '_blank';
    if (!link.relList.contains('noopener')) link.relList.add('noopener');
  };
  document.querySelectorAll<HTMLAnchorElement>('a[href]').forEach((link) => { if (isExternal(link)) mark(link); });
  document.addEventListener('click', (event) => {
    const link = event.target instanceof Element ? event.target.closest<HTMLAnchorElement>('a[href]') : null;
    if (link && isExternal(link)) mark(link);
  }, true);
}

function initialiseHomeDialogs(): void {
  const lastFocused = new WeakMap<HTMLDialogElement, HTMLElement>();
  // Openers are links to the post permalink, so content stays reachable without
  // JavaScript; with JavaScript they open the inline dialog instead.
  document.querySelectorAll<HTMLElement>('[data-home-dialog-open]').forEach((opener) => {
    const dialogId = opener.dataset.homeDialogOpen;
    const dialog = dialogId ? document.getElementById(dialogId) : null;
    if (!(dialog instanceof HTMLDialogElement)) return;
    opener.setAttribute('aria-haspopup', 'dialog');
    opener.addEventListener('click', (event) => {
      if (event.ctrlKey || event.metaKey || event.shiftKey || event.button !== 0) return;
      event.preventDefault();
      lastFocused.set(dialog, opener);
      dialog.showModal();
      document.documentElement.classList.add('has-home-dialog');
    });
  });
  document.querySelectorAll<HTMLDialogElement>('.tj-home-content-dialog').forEach((dialog) => {
    // Idempotent: runs directly after our own close() calls and again from the
    // native close event, which also covers closing with Escape.
    const afterClose = () => {
      if (!document.querySelector('.tj-home-content-dialog[open]')) document.documentElement.classList.remove('has-home-dialog');
      // Stop any trailer started inside the dialog by restoring its preview.
      dialog.querySelectorAll<HTMLElement>('.youtube-player').forEach((player) => {
        if (player.querySelector('iframe')) createYouTubePreview(player);
      });
      const opener = lastFocused.get(dialog);
      lastFocused.delete(dialog);
      opener?.focus();
    };
    const close = () => {
      dialog.close();
      afterClose();
    };
    dialog.querySelector<HTMLButtonElement>('[data-home-dialog-close]')?.addEventListener('click', close);
    dialog.addEventListener('click', (event) => {
      if (event.target === dialog) close();
    });
    dialog.addEventListener('close', afterClose);
  });
}

const czechMonths = ['leden', 'únor', 'březen', 'duben', 'květen', 'červen', 'červenec', 'srpen', 'září', 'říjen', 'listopad', 'prosinec'];

/**
 * Replaces the native date picker (which follows the browser's language, often
 * mm/dd/yyyy) with Czech day / month / year selects. The original input keeps
 * its name and receives the ISO date the backend expects.
 */
function initialiseCzechDateInputs(): void {
  document.querySelectorAll<HTMLInputElement>('input[type="date"][data-czech-date]').forEach((input) => {
    const thisYear = new Date().getFullYear();
    const makeSelect = (label: string, options: Array<[string, string]>) => {
      const select = document.createElement('select');
      select.className = input.className;
      select.required = input.required;
      select.setAttribute('aria-label', label);
      const placeholder = new Option(label, '');
      select.append(placeholder, ...options.map(([value, text]) => new Option(text, value)));
      return select;
    };
    const pad = (value: number) => String(value).padStart(2, '0');
    const day = makeSelect('Den', Array.from({ length: 31 }, (_, index) => [pad(index + 1), `${index + 1}.`]));
    const month = makeSelect('Měsíc', czechMonths.map((name, index) => [pad(index + 1), name]));
    const year = makeSelect('Rok', Array.from({ length: 26 }, (_, index) => {
      const value = String(thisYear - 3 - index);
      return [value, value];
    }));
    day.id = input.id;
    input.id = `${input.id}-value`;

    const group = document.createElement('div');
    group.className = 'tj-date-group';
    group.setAttribute('role', 'group');
    const label = document.getElementById(`${day.id}-label`);
    if (label) group.setAttribute('aria-labelledby', label.id);
    group.append(day, month, year);

    const [initialYear, initialMonth, initialDay] = input.value.split('-');
    if (initialYear) { year.value = initialYear; month.value = initialMonth; day.value = initialDay; }

    const sync = () => {
      day.setCustomValidity('');
      if (!day.value || !month.value || !year.value) { input.value = ''; return; }
      const date = new Date(Number(year.value), Number(month.value) - 1, Number(day.value));
      if (date.getDate() !== Number(day.value)) {
        input.value = '';
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

/**
 * Splits the registration form into three steps. Each step is validated before
 * moving on; without JavaScript the whole form stays on one page.
 */
function initialiseRegistrationSteps(): void {
  const form = document.querySelector<HTMLFormElement>('[data-registration-form]');
  const indicator = document.querySelector<HTMLElement>('[data-registration-steps]');
  if (!form || !indicator) return;
  const steps = Array.from(form.querySelectorAll<HTMLFieldSetElement>('.tj-form-step'));
  const targets = Array.from(indicator.querySelectorAll<HTMLButtonElement>('[data-step-target]'));
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
      indicator.scrollIntoView({ block: 'start', behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
      steps[index].querySelector<HTMLElement>('input:not([type="hidden"]), select, textarea')?.focus({ preventScroll: true });
    }
  };
  const stepIsValid = (index: number) => {
    const fields = Array.from(steps[index].querySelectorAll<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>('input:not([type="hidden"]), select, textarea'));
    const invalid = fields.find((field) => !field.checkValidity());
    invalid?.reportValidity();
    return !invalid;
  };

  form.querySelectorAll<HTMLButtonElement>('[data-step-next]').forEach((button) => button.addEventListener('click', () => {
    if (stepIsValid(current)) show(Math.min(current + 1, steps.length - 1));
  }));
  form.querySelectorAll<HTMLButtonElement>('[data-step-prev]').forEach((button) => button.addEventListener('click', () => show(Math.max(current - 1, 0))));
  targets.forEach((target, index) => target.addEventListener('click', () => {
    if (index <= reached && (index < current || stepIsValid(current))) show(index);
  }));
  form.addEventListener('submit', (event) => {
    const invalidStep = steps.findIndex((_, index) => {
      const fields = Array.from(steps[index].querySelectorAll<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>('input:not([type="hidden"]), select, textarea'));
      return fields.some((field) => !field.checkValidity());
    });
    if (invalidStep !== -1) {
      event.preventDefault();
      show(invalidStep);
      stepIsValid(invalidStep);
    }
  });
  show(0, false);
}

function initialisePersonalIdValidation(): void {
  const input = document.querySelector<HTMLInputElement>('#rodne_c');
  if (!input) return;
  const pattern = /^\d{0,2}((0[1-9]|1[0-2])|(2[1-9]|3[0-2])|(5[1-9]|6[0-2])|(7[1-9]|8[0-2]))(0[1-9]|[1-2][0-9]|3[01])\/?[0-9]{3,4}$/;
  const validate = () => input.setCustomValidity(input.value.trim() === '' || pattern.test(input.value.trim()) ? '' : 'Rodné číslo nemá správný formát.');
  input.addEventListener('input', validate);
  input.addEventListener('blur', validate);
}

function initialiseDialogs(): void {
  document.querySelectorAll<HTMLButtonElement>('[data-dialog-close]').forEach((button) => {
    button.addEventListener('click', () => {
      const container = button.closest<HTMLElement>('.notification_container');
      if (container) container.hidden = true;
    });
  });
}

function createYouTubePreview(container: HTMLElement): void {
  const id = container.dataset.id;
  if (!id) return;

  const title = container.dataset.title || 'YouTube video';
  const fallbackThumbnail = `https://i.ytimg.com/vi/${encodeURIComponent(id)}/hqdefault.jpg`;
  const thumbnail = container.dataset.thumbnail || fallbackThumbnail;

  const button = document.createElement('button');
  button.type = 'button';
  button.className = 'youtube-player__button';
  button.setAttribute('aria-label', `Přehrát ${title}`);

  const image = document.createElement('img');
  // loading must be set before src, otherwise the download starts immediately.
  image.loading = 'lazy';
  image.alt = '';
  image.src = thumbnail;
  image.addEventListener('error', () => {
    if (image.src !== fallbackThumbnail) image.src = fallbackThumbnail;
  }, { once: true });

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

document.addEventListener('DOMContentLoaded', () => {
  initialiseMobileMenu();
  initialiseArchiveExpand();
  initialiseNavScrollSpy();
  initialisePagers();
  initialiseSeasonArticles();
  initialiseExclusiveDetails();
  initialiseCollapsedEqualHeight();
  initialiseLightbox();
  initialiseScrollReveal();
  initialiseHeaderState();
  initialiseNavIndicator();
  initialiseBackToTop();
  initialiseResponsiveNav();
  initialiseActivityTabs();
  initialiseHorizontalCarousels();
  initialiseHomeDialogs();
  initialiseInternalLinks();
  initialiseExternalLinks();
  initialiseDialogs();
  initialisePersonalIdValidation();
  initialiseCzechDateInputs();
  initialiseRegistrationSteps();
  document.querySelectorAll<HTMLElement>('.youtube-player').forEach(createYouTubePreview);

  const newsletter = document.forms.namedItem('register_news');
  if (newsletter) {
    const email = newsletter.elements.namedItem('newsletter-email');
    if (email instanceof HTMLInputElement) email.required = true;
  }
});
