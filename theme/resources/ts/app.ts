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

    const perView = () => Math.max(1, Math.round(Number.parseFloat(getComputedStyle(pager).getPropertyValue('--per-view')) || 1));
    const render = () => {
      const visible = perView();
      first = Math.min(first, Math.max(0, items.length - visible));
      const offset = items[first] ? items[first].offsetLeft - items[0].offsetLeft : 0;
      track.style.transform = `translateX(${-offset}px)`;
      items.forEach((item, index) => { item.inert = index < first || index >= first + visible; });
      previous.disabled = first === 0;
      next.disabled = first + visible >= items.length;
      const last = Math.min(first + visible, items.length);
      if (status) status.textContent = `${last - first === 1 ? first + 1 : `${first + 1}-${last}`} z ${items.length}`;
    };
    previous.addEventListener('click', () => { first = Math.max(0, first - perView()); render(); });
    next.addEventListener('click', () => { first = Math.min(items.length - perView(), first + perView()); render(); });
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

/**
 * Pro rodiče: both columns match while every FAQ item is closed; opening an
 * item grows only the FAQ card, the side column keeps its collapsed height.
 */
function initialiseCollapsedEqualHeight(): void {
  document.querySelectorAll<HTMLElement>('[data-collapsed-equal]').forEach((container) => {
    const main = container.firstElementChild as HTMLElement | null;
    if (!main) return;
    const measure = () => {
      if (main.querySelector('details[open]')) return;
      const twoColumns = getComputedStyle(container).gridTemplateColumns.split(' ').length > 1;
      if (twoColumns) container.style.setProperty('--tj-collapsed-height', `${main.getBoundingClientRect().height}px`);
      else container.style.removeProperty('--tj-collapsed-height');
    };
    new ResizeObserver(measure).observe(container);
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
  initialiseActivityTabs();
  initialiseHorizontalCarousels();
  initialiseHomeDialogs();
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
