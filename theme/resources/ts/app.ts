import '../css/app.css';

function initialiseMobileMenu(): void {
  const container = document.querySelector<HTMLElement>('#mobile_menu_container');
  const button = container?.querySelector<HTMLButtonElement>('[data-menu-toggle]');
  const menu = button?.nextElementSibling;
  if (!container || !button || !(menu instanceof HTMLElement)) return;

  button.addEventListener('click', () => {
    const open = button.getAttribute('aria-expanded') !== 'true';
    button.classList.toggle('tj:rotate-90', open);
    button.querySelector('span')?.classList.toggle('tj:hidden', open);
    menu.classList.toggle('tj:hidden', !open);
    menu.classList.toggle('tj:flex', open);
    button.setAttribute('aria-expanded', String(open));
  });

  menu.querySelectorAll<HTMLAnchorElement>('.menu-item-has-children > a').forEach((link) => {
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

  const button = document.createElement('button');
  button.type = 'button';
  button.className = 'youtube-player__button';
  button.setAttribute('aria-label', 'Přehrát video');
  button.innerHTML = `<img src="https://i.ytimg.com/vi/${encodeURIComponent(id)}/hqdefault.jpg" alt="" loading="lazy"><span class="play" aria-hidden="true"></span>`;
  button.addEventListener('click', () => {
    const iframe = document.createElement('iframe');
    iframe.src = `https://www.youtube.com/embed/${encodeURIComponent(id)}?autoplay=1`;
    iframe.title = 'YouTube video';
    iframe.allow = 'autoplay; encrypted-media; picture-in-picture';
    iframe.allowFullscreen = true;
    container.replaceChildren(iframe);
  }, { once: true });
  container.append(button);
}

document.addEventListener('DOMContentLoaded', () => {
  initialiseMobileMenu();
  initialiseDialogs();
  initialisePersonalIdValidation();
  document.querySelectorAll<HTMLElement>('.youtube-player').forEach(createYouTubePreview);

  const newsletter = document.forms.namedItem('register_news');
  if (newsletter) {
    const email = newsletter.elements.namedItem('newsletter-email');
    if (email instanceof HTMLInputElement) email.required = true;
  }
});
