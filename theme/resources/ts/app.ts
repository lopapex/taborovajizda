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

function initialiseHomeTabs(): void {
  document.querySelectorAll<HTMLElement>('[data-home-tabs]').forEach((container) => {
    const tabs = Array.from(container.querySelectorAll<HTMLButtonElement>('[data-home-tab]'));
    const panels = Array.from(container.querySelectorAll<HTMLElement>('[role="tabpanel"]'));
    if (tabs.length === 0 || panels.length === 0) return;

    document.documentElement.classList.add('has-home-tabs');
    const activate = (tab: HTMLButtonElement) => {
      const panelId = tab.dataset.homeTab;
      if (!panelId) return;
      tabs.forEach((item) => {
        const active = item === tab;
        item.classList.toggle('is-active', active);
        item.setAttribute('aria-selected', String(active));
        item.tabIndex = active ? 0 : -1;
      });
      panels.forEach((panel) => panel.classList.toggle('is-active', panel.id === panelId));
    };

    const activateByPanelId = (panelId: string) => {
      const tab = tabs.find((item) => item.dataset.homeTab === panelId);
      if (tab) activate(tab);
    };
    const scrollToTabs = (smooth: boolean) => {
      const heading = container.querySelector<HTMLElement>('.tj-home-section__heading');
      (heading ?? container).scrollIntoView({
        behavior: smooth && !window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'smooth' : 'auto',
        block: 'start',
      });
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

    const expand = container.querySelector<HTMLButtonElement>('[data-archive-expand]');
    expand?.addEventListener('click', () => {
      const expanded = expand.getAttribute('aria-expanded') === 'true';
      container.classList.toggle('archive-expanded', !expanded);
      expand.setAttribute('aria-expanded', String(!expanded));
      expand.textContent = expanded ? 'Zobrazit všechny ročníky' : 'Zobrazit méně ročníků';
    });

    document.querySelectorAll<HTMLAnchorElement>('[data-home-tab-link]').forEach((link) => {
      const panelId = link.dataset.homeTabLink;
      if (!panelId) return;
      link.addEventListener('click', (event) => {
        event.preventDefault();
        activateByPanelId(panelId);
        window.history.pushState(null, '', link.hash);
        scrollToTabs(true);
      });
    });

    const panelIdFromHash: Record<string, string> = {
      '#pro-rodice': 'panel-parents',
      '#dokumenty': 'panel-documents',
      '#minule-rocniky': 'panel-archive',
    };
    const applyHash = () => {
      const panelId = panelIdFromHash[window.location.hash];
      if (!panelId) return;
      activateByPanelId(panelId);
      window.requestAnimationFrame(() => scrollToTabs(false));
    };
    applyHash();
    window.addEventListener('hashchange', applyHash);
  });
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
  image.src = thumbnail;
  image.alt = '';
  image.loading = 'lazy';
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
  container.append(button);
}

document.addEventListener('DOMContentLoaded', () => {
  initialiseMobileMenu();
  initialiseHomeTabs();
  initialiseDialogs();
  initialisePersonalIdValidation();
  document.querySelectorAll<HTMLElement>('.youtube-player').forEach(createYouTubePreview);

  const newsletter = document.forms.namedItem('register_news');
  if (newsletter) {
    const email = newsletter.elements.namedItem('newsletter-email');
    if (email instanceof HTMLInputElement) email.required = true;
  }
});
