import '../css/app.css';

function validateNewsletterForm(form: HTMLFormElement): boolean {
  const input = form.elements.namedItem('newsletter-email');
  if (!(input instanceof HTMLInputElement) || input.value.trim() === '') {
    window.alert('Musíte vyplnit e-mail.');
    if (input instanceof HTMLInputElement) input.focus();
    return false;
  }
  return true;
}

const requiredRegistrationFields = [
  'jmeno',
  'prijmeni',
  'datum_nar',
  'rodne_c',
  'adresa',
  'kontakt_email',
  'nastup',
  'vystup',
] as const;

function validateRegistrationForm(form: HTMLFormElement): boolean {
  let valid = true;
  let firstInvalid: HTMLInputElement | HTMLSelectElement | null = null;

  for (const name of requiredRegistrationFields) {
    const field = form.elements.namedItem(name);
    if (!(field instanceof HTMLInputElement || field instanceof HTMLSelectElement)) continue;

    const missing = field.value.trim() === '';
    field.classList.toggle('chybny_vstup', missing);
    field.setAttribute('aria-invalid', String(missing));
    if (missing) {
      firstInvalid ??= field;
      valid = false;
    }
  }

  const personalId = form.elements.namedItem('rodne_c');
  if (personalId instanceof HTMLInputElement && personalId.value.trim() !== '') {
    const personalIdPattern = /^\d{0,2}((0[1-9]|1[0-2])|(2[1-9]|3[0-2])|(5[1-9]|6[0-2])|(7[1-9]|8[0-2]))(0[1-9]|[1-2][0-9]|3[01])\/?[0-9]{3,4}$/;
    if (!personalIdPattern.test(personalId.value.trim())) {
      personalId.classList.add('chybny_vstup');
      personalId.setAttribute('aria-invalid', 'true');
      firstInvalid ??= personalId;
      valid = false;
      window.alert('Rodné číslo nemá správný formát.');
    }
  }

  if (!valid) {
    window.alert('Musíte vyplnit všechny povinné údaje.');
    firstInvalid?.focus();
  }

  return valid;
}

function initialiseMobileMenu(): void {
  const container = document.querySelector<HTMLElement>('#mobile_menu_container');
  const button = container?.querySelector<HTMLButtonElement>('[data-menu-toggle]');
  const menu = button?.nextElementSibling;
  if (!container || !button || !(menu instanceof HTMLElement)) return;

  button.addEventListener('click', () => {
    const open = !menu.classList.contains('viditelne');
    button.classList.toggle('stlacen', open);
    menu.classList.toggle('viditelne', open);
    button.setAttribute('aria-expanded', String(open));
  });

  menu.querySelectorAll<HTMLAnchorElement>('.menu-item-has-children > a').forEach((link) => {
    link.setAttribute('aria-expanded', 'false');
    link.addEventListener('click', (event) => {
      const item = link.parentElement;
      if (!item || item.classList.contains('viditelne')) return;
      event.preventDefault();
      item.classList.add('viditelne');
      link.setAttribute('aria-expanded', 'true');
    });
  });
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
  document.querySelectorAll<HTMLElement>('.youtube-player').forEach(createYouTubePreview);

  const registration = document.forms.namedItem('eprihlaska');
  if (registration) {
    registration.addEventListener('submit', (event) => {
      if (!validateRegistrationForm(registration)) event.preventDefault();
    });
  }

  const newsletter = document.forms.namedItem('register_news');
  if (newsletter) {
    newsletter.addEventListener('submit', (event) => {
      if (!validateNewsletterForm(newsletter)) event.preventDefault();
    });
  }
});
