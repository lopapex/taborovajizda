<?php
declare(strict_types=1);

use TaborovaJizda\Theme\Home;

$season = taborovajizda_season();
$remainingPlaces = $season->remainingPlaces();
$acceptsRegistrations = $season->acceptsRegistrations();
$debugForm = ! $acceptsRegistrations && taborovajizda_debug_registration_cta();
$required = '<span class="tj:text-ember" aria-hidden="true">*</span>';

/** One labelled form field; $control is printf markup receiving the id (and the hint id as aria-describedby). */
$field = static function (string $id, string $label, string $control, array $options = []) use ($required): void {
    $hint = $options['hint'] ?? null;
    $describedBy = $hint ? sprintf(' aria-describedby="%s-hint"', $id) : '';
    ?>
	<div class="tj:grid tj:min-w-0 tj:content-start tj:gap-[.4rem] <?php echo empty($options['wide']) ? '' : 'tj:col-span-full'; ?>">
		<label class="tj:font-semibold tj:text-ink" for="<?php echo esc_attr($id); ?>"<?php echo empty($options['date']) ? '' : ' id="' . esc_attr($id) . '-label"'; ?>><?php echo esc_html($label); ?><?php echo empty($options['required']) ? '' : ' ' . $required; ?></label>
		<?php printf($control, esc_attr($id), $describedBy); ?>
		<?php if ($hint) : ?><p class="tj:m-0 tj:text-[.9rem] tj:leading-[1.4] tj:text-muted" id="<?php echo esc_attr($id); ?>-hint"><?php echo esc_html($hint); ?></p><?php endif; ?>
	</div>
	<?php
};
$input = static fn (string $type, string $name, string $attributes = ''): string => '<input class="tj-input" type="' . $type . '" id="%1$s" name="' . $name . '"' . $attributes . '%2$s>';
$select = static fn (string $name, array $options): string => '<select class="tj-input" id="%1$s" name="' . $name . '" required%2$s><option value="">Vyberte místo</option>'
    . implode('', array_map(static fn (string $value, string $label): string => '<option value="' . esc_attr($value) . '">' . esc_html($label) . '</option>', array_keys($options), $options)) . '</select>';
$stepNav = static function (bool $back, string $next = 'Pokračovat', string $type = 'button'): void {
    ?>
	<div class="tj:mt-7 tj:flex tj:flex-wrap tj:justify-between tj:gap-3 tj:[&>:only-child]:ml-auto">
		<?php if ($back) : ?><button class="tj-btn tj-btn-quiet tj:group-[:not(.is-stepped)]/form:hidden" type="button" data-step-prev>Zpět</button><?php endif; ?>
		<button class="tj-btn <?php echo $type === 'button' ? 'tj:group-[:not(.is-stepped)]/form:hidden' : ''; ?>" type="<?php echo $type; ?>"<?php echo $type === 'button' ? ' data-step-next' : ''; ?>><?php echo esc_html($next); ?></button>
	</div>
	<?php
};
$fieldset = 'tj-card tj:m-0 tj:min-w-0 tj:overflow-visible tj:p-[clamp(1.25rem,4vw,2.25rem)] tj:[&>legend+*]:clear-both';
$legend = 'tj-h3 tj:float-left tj:mb-5 tj:w-full tj:p-0 tj:text-[clamp(1.4rem,3vw,1.75rem)]';
$grid = 'tj:grid tj:grid-cols-2 tj:gap-x-5 tj:gap-y-[1.1rem] tj:max-md:grid-cols-1';
get_header();
?>
<main id="main-content">
	<header class="tj:bg-night tj:text-on-night">
		<div class="tj:mx-auto tj:grid tj:w-[min(100%-2rem,60rem)] tj:gap-x-12 tj:gap-y-7 tj:py-[clamp(2.5rem,6vw,4rem)] tj:md:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)] tj:md:items-end">
			<div>
				<p class="tj:m-0 tj:mb-3 tj:font-semibold tj:text-on-night-muted"><?php echo esc_html(Home::dashes($season->title()) . ' ' . $season->year()); ?></p>
				<h1 class="tj-h1 tj:text-[clamp(2.1rem,5vw,3.5rem)]">Přihláška na tábor</h1>
				<p class="tj:mt-4 tj:mb-0 tj:max-w-136 tj:text-[1.1rem] tj:leading-[1.55] tj:text-on-night-muted">Čtyři krátké kroky: dítě, kontakt na Vás, doplňující informace a kontrola. Kopii přihlášky a pokyny k platbě pošleme na Váš e-mail.</p>
			</div>
			<?php get_template_part('template-parts/facts', null, ['facts' => Home::facts($season, false)]); ?>
		</div>
	</header>
	<article class="tj:mx-auto tj:w-[min(100%-2rem,60rem)] tj:pb-[clamp(4rem,8vw,6rem)]">
		<div class="tj:pt-8">
			<?php
			$notice = match (true) {
				! $acceptsRegistrations => '<p><strong>Přihlášky na ročník ' . esc_html((string) $season->year()) . ' jsou uzavřené.</strong> Termín dalšího ročníku zveřejníme na <a href="' . esc_url(home_url('/')) . '">hlavní stránce</a>.</p>'
					. ($debugForm ? '<p class="tj-debug">Ladicí zobrazení: formulář je vidět jen mimo produkci (lokál a stage) a odeslání se pouze simuluje.</p>' : ''),
				$season->isFull() => '<p><strong>Tábor je již obsazen.</strong> Přihlášku zařadíme na seznam náhradníků. Když se uvolní místo, ozveme se.</p>',
				$remainingPlaces !== null && $remainingPlaces < 20 => '<p><strong>Tábor se rychle plní.</strong> Počet volných míst: ' . esc_html((string) $remainingPlaces) . '.</p>',
				default => '',
			};
			?>
			<?php if ($notice !== '') : ?>
				<div class="tj:mb-6 tj:rounded-xl tj:bg-sand tj:px-5 tj:py-4 tj:leading-normal tj:text-ink tj:[&_a]:font-bold tj:[&_a]:text-link tj:[&_p]:m-0 tj:[&_p+p]:mt-2" role="status"><?php echo $notice; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above ?></div>
			<?php endif; ?>

			<?php if ($acceptsRegistrations || $debugForm) : ?>
			<ol class="tj:sticky tj:top-18 tj:z-10 tj:m-0 tj:mb-6 tj:-mx-2 tj:grid tj:scroll-mt-24 tj:list-none tj:grid-cols-4 tj:gap-2 tj:bg-paper/95 tj:px-2 tj:py-3 tj:backdrop-blur-sm tj:[[hidden]]:hidden" data-registration-steps hidden>
				<?php foreach (['Dítě', 'Kontakt', 'Doplnění', 'Kontrola'] as $index => $step) : ?>
					<li><button class="tj:group tj:flex tj:min-h-[48px] tj:w-full tj:cursor-pointer tj:items-center tj:gap-[.6rem] tj:rounded-xl tj:border tj:border-ink/15 tj:bg-surface tj:px-[.8rem] tj:py-2 tj:text-left tj:font-semibold tj:text-muted tj:transition-colors tj:duration-150 tj:enabled:hover:border-ink/40 tj:disabled:cursor-default tj:disabled:opacity-55 tj:aria-[current=step]:border-night tj:aria-[current=step]:bg-night tj:aria-[current=step]:text-on-night tj:[&.is-done]:text-ink tj:max-md:flex-col tj:max-md:gap-1 tj:max-md:px-1 tj:max-md:text-center tj:max-md:text-[.8rem]" type="button" data-step-target="<?php echo $index + 1; ?>"<?php echo $index === 0 ? ' aria-current="step"' : ' disabled'; ?>><span class="tj:grid tj:size-[1.75rem] tj:flex-none tj:place-items-center tj:rounded-full tj:bg-ink/8 tj:text-[.9rem] tj:tabular-nums tj:text-ink tj:group-aria-[current=step]:bg-sun tj:group-aria-[current=step]:text-night tj:group-[.is-done]:bg-grass tj:group-[.is-done]:text-night"><?php echo $index + 1; ?></span> <?php echo esc_html($step); ?></button></li>
				<?php endforeach; ?>
			</ol>

			<form id="eprihlaska" class="tj:group/form tj:grid tj:gap-5" action="<?php echo esc_url(home_url('/odeslat-dotaz/')); ?>" method="post" data-registration-form>
				<input type="hidden" name="action" value="tj_registration_submit">
				<?php wp_nonce_field('tj_form_submit', 'tj_nonce'); ?>
				<p class="tj:m-0 tj:leading-normal tj:text-muted">Pole označená <span class="tj:text-ember">*</span> jsou povinná. <strong class="tj:text-ink">Odeslaná přihláška je závazná</strong>, před odesláním si ji ještě zkontrolujete.</p>

				<fieldset class="<?php echo $fieldset; ?>" data-step="1">
					<legend class="<?php echo $legend; ?>">Údaje o dítěti</legend>
					<div class="<?php echo $grid; ?>">
						<?php
						$field('jmeno', 'Jméno', $input('text', 'jmeno', ' autocomplete="given-name" required'), ['required' => true]);
						$field('prijmeni', 'Příjmení', $input('text', 'prijmeni', ' autocomplete="family-name" required'), ['required' => true]);
						$field('datum_nar', 'Datum narození', $input('date', 'datum_nar', ' autocomplete="bday" required data-czech-date'), ['required' => true, 'date' => true]);
						$field('rodne_c', 'Rodné číslo', $input('text', 'rodne_c', ' inputmode="numeric" required'), ['required' => true, 'hint' => 'S lomítkem i bez něj.']);
						$field('adresa', 'Adresa bydliště', $input('text', 'adresa', ' autocomplete="street-address" required'), ['required' => true, 'wide' => true, 'hint' => 'Ulice a číslo domu, obec a PSČ.']);
						$field('spolubydlici', 'S kým chci být ve stanu', $input('text', 'spolubydlici'), ['wide' => true]);
						$field('nastup', 'Kde se k vám připojím', $select('nastup', ['ŽBK' => 'Žamberk', 'HK' => 'Hradec Králové', 'MS' => 'Malá Skála', 'JINÉ' => 'Dám ještě vědět']), ['required' => true]);
						$field('vystup', 'Z tábora s vámi pojedu do', $select('vystup', ['ŽBK' => 'Žamberk', 'HK' => 'Hradec Králové', 'TÁBOR' => 'Nikam: Budu vyzvednut na táboře', 'JINÉ' => 'Dám ještě vědět']), ['required' => true]);
						?>
					</div>
					<?php $stepNav(false); ?>
				</fieldset>

				<fieldset class="<?php echo $fieldset; ?>" data-step="2">
					<legend class="<?php echo $legend; ?>">Kontakt na zákonného zástupce</legend>
					<p class="tj:mt-0 tj:mb-5 tj:leading-normal tj:text-muted">Vyplňte prosím jen <strong>jeden</strong> kontakt. Další můžete připsat v dalším kroku do poznámek.</p>
					<div class="<?php echo $grid; ?>">
						<?php
						$field('kontakt_jmeno', 'Jméno a příjmení', $input('text', 'kontakt_jmeno', ' autocomplete="name"'));
						$field('kontakt_tel', 'Telefon', $input('tel', 'kontakt_tel', ' autocomplete="tel"'));
						$field('kontakt_email', 'E-mail', $input('email', 'kontakt_email', ' autocomplete="email" required'), ['required' => true, 'wide' => true, 'hint' => 'Sem pošleme kopii přihlášky a pokyny k platbě.']);
						?>
					</div>
					<?php $stepNav(true); ?>
				</fieldset>

				<fieldset class="<?php echo $fieldset; ?>" data-step="3">
					<legend class="<?php echo $legend; ?>">Doplňující informace</legend>
					<?php $field('dotaz', 'Zdravotní potřeby, alergie, dieta a další poznámky', '<textarea class="tj-input" id="%1$s" name="dotaz" rows="6"%2$s></textarea>', ['hint' => 'Cokoli, co bychom měli vědět a nenašli jste pro to kolonku. Máte-li táborový poukaz na slevu, opište sem kód z jeho pravého dolního rohu.']); ?>
					<?php $stepNav(true); ?>
				</fieldset>

				<fieldset class="<?php echo $fieldset; ?>" data-step="4">
					<legend class="<?php echo $legend; ?>">Kontrola a odeslání</legend>
					<p class="tj:mt-0 tj:mb-5 tj:leading-normal tj:text-muted tj:nojs:hidden">Projděte si prosím údaje. Cokoli můžete ještě upravit.</p>
					<div class="tj:grid tj:gap-6 tj:empty:hidden" data-registration-summary></div>
					<p class="tj:mt-6 tj:mb-0 tj:text-[.95rem] tj:leading-normal tj:text-muted">Odesláním přihlášky souhlasíte se zpracováním osobních údajů. Podrobnosti jsou v <a class="tj-link" href="<?php echo esc_url(home_url('/ochrana-osobnich-udaju/#menu')); ?>">prohlášení o zpracování osobních údajů</a>.</p>
					<?php $stepNav(true, 'Závazně přihlásit dítě', 'submit'); ?>
				</fieldset>
			</form>
			<?php endif; ?>
		</div>
	</article>
</main>
<?php get_footer(); ?>
