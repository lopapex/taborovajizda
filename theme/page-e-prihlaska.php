<?php
declare(strict_types=1);

use TaborovaJizda\Theme\Home;

$season = taborovajizda_season();
$remainingPlaces = $season->remainingPlaces();
$acceptsRegistrations = $season->acceptsRegistrations();
$debugForm = ! $acceptsRegistrations && taborovajizda_debug_registration_cta();
$required = '<span class="tj:text-link" aria-hidden="true">*</span>';

/** One labelled form field; $control is printf markup receiving the id (and the hint id as aria-describedby). */
$field = static function (string $id, string $label, string $control, array $options = []) use ($required): void {
    $hint = $options['hint'] ?? null;
    $describedBy = $hint ? sprintf(' aria-describedby="%s-hint"', $id) : '';
    ?>
	<div class="tj:grid tj:min-w-0 tj:content-start tj:gap-[.4rem] <?php echo empty($options['wide']) ? '' : 'tj:col-span-full'; ?>">
		<label class="tj:font-extrabold tj:text-ink" for="<?php echo esc_attr($id); ?>"<?php echo empty($options['date']) ? '' : ' id="' . esc_attr($id) . '-label"'; ?>><?php echo esc_html($label); ?><?php echo empty($options['required']) ? '' : ' ' . $required; ?></label>
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
$fieldset = 'tj-panel tj:m-0 tj:min-w-0 tj:p-[clamp(1.25rem,4vw,2.25rem)] tj:[&>legend+*]:clear-both';
$legend = 'tj:float-left tj:m-0 tj:mb-5 tj:w-full tj:p-0 tj:text-[clamp(1.4rem,3vw,1.85rem)] tj:font-extrabold tj:leading-[1.15] tj:text-ink';
$grid = 'tj:grid tj:grid-cols-2 tj:gap-x-5 tj:gap-y-[1.1rem] tj:max-md:grid-cols-1';
get_header();
?>
<main id="main-content">
	<article class="tj:mx-auto tj:w-[min(100%-2rem,60rem)] tj:bg-paper tj:pt-[clamp(2.5rem,6vw,4.5rem)] tj:pb-[clamp(4rem,8vw,6rem)] tj:shadow-[0_0_0_100vmax_var(--tj-color-paper)] tj:[clip-path:inset(0_-100vmax)]">
		<header class="tj:relative tj:-mt-[clamp(2.5rem,6vw,4.5rem)] tj:bg-afternoon tj:pt-[clamp(2.5rem,6vw,4.5rem)] tj:pb-10 tj:shadow-[0_0_0_100vmax_var(--tj-color-afternoon)] tj:[clip-path:inset(0_-100vmax)] tj:[--tj-eyebrow:var(--tj-color-warm-ink)] tj:after:absolute tj:after:bottom-0 tj:after:left-[calc(50%-50vw)] tj:after:h-[5px] tj:after:w-screen tj:after:bg-(image:--tj-stripe-1)">
			<p class="tj-eyebrow">Táborová jízda <?php echo esc_html((string) $season->year()); ?></p>
			<h1 class="tj:m-0 tj:font-display tj:text-[clamp(2.5rem,6vw,4.25rem)] tj:leading-none tj:font-normal tj:uppercase">Elektronická přihláška</h1>
			<p class="tj:mt-4 tj:mb-0 tj:max-w-160 tj:text-[1.1rem] tj:leading-[1.55] tj:text-slate">Tři krátké kroky: údaje o dítěti, kontakt na Vás a doplňující informace. Kopii přihlášky i pokyny k platbě Vám pošleme na e-mail.</p>
			<?php get_template_part('template-parts/facts', null, ['facts' => Home::facts($season, false), 'class' => 'tj:mt-6']); ?>
		</header>

		<div class="tj:mt-10">
			<?php
			$notice = match (true) {
				! $acceptsRegistrations => '<p><strong>Přihlášky na ročník ' . esc_html((string) $season->year()) . ' jsou uzavřené.</strong> Termín dalšího ročníku zveřejníme na <a href="' . esc_url(home_url('/')) . '">hlavní stránce</a>.</p>'
					. ($debugForm ? '<p class="tj-debug">Ladicí zobrazení: formulář je vidět jen mimo produkci (lokál a stage) a odeslání se pouze simuluje.</p>' : ''),
				$season->isFull() => '<p><strong>Tábor je již obsazen.</strong> Vaše přihláška bude zařazena na seznam náhradníků. Pokud se uvolní místo, ozveme se.</p>',
				$remainingPlaces !== null && $remainingPlaces < 20 => '<p><strong>Tábor se rychle plní.</strong> Počet volných míst: ' . esc_html((string) $remainingPlaces) . '.</p>',
				default => '',
			};
			?>
			<?php if ($notice !== '') : ?>
				<div class="tj:mb-6 tj:rounded-lg tj:border-l-4 tj:border-sun tj:bg-sun/12 tj:px-5 tj:py-4 tj:leading-normal tj:text-ink tj:[&_a]:font-extrabold tj:[&_a]:text-link tj:[&_p]:m-0 tj:[&_p+p]:mt-2" role="status"><?php echo $notice; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above ?></div>
			<?php endif; ?>

			<?php if ($acceptsRegistrations || $debugForm) : ?>
			<ol class="tj:m-0 tj:mb-6 tj:grid tj:scroll-mt-24 tj:list-none tj:grid-cols-3 tj:gap-2 tj:p-0 tj:[[hidden]]:hidden" data-registration-steps hidden>
				<?php foreach (['Dítě', 'Kontakt', 'Dokončení'] as $index => $step) : ?>
					<li><button class="tj:group tj:flex tj:min-h-[52px] tj:w-full tj:cursor-pointer tj:items-center tj:gap-[.6rem] tj:rounded-lg tj:border tj:border-ink/15 tj:bg-white/80 tj:px-[.8rem] tj:py-[.55rem] tj:text-left tj:font-extrabold tj:text-muted tj:enabled:hover:border-sun-deep tj:disabled:cursor-default tj:disabled:opacity-60 tj:aria-[current=step]:border-sun-deep tj:aria-[current=step]:bg-sun-tint tj:aria-[current=step]:text-ink tj:aria-[current=step]:inset-ring tj:aria-[current=step]:inset-ring-sun-deep tj:[&.is-done]:text-ink tj:max-md:justify-center tj:max-md:p-2 tj:max-md:text-[.9rem]" type="button" data-step-target="<?php echo $index + 1; ?>"<?php echo $index === 0 ? ' aria-current="step"' : ' disabled'; ?>><span class="tj:grid tj:size-[1.9rem] tj:flex-none tj:place-items-center tj:rounded-full tj:bg-ink/8 tj:text-ink tj:group-aria-[current=step]:bg-sun tj:group-[.is-done]:bg-sun"><?php echo $index + 1; ?></span> <?php echo esc_html($step); ?></button></li>
				<?php endforeach; ?>
			</ol>

			<form id="eprihlaska" class="tj:group/form tj:grid tj:gap-5" action="<?php echo esc_url(home_url('/odeslat-dotaz/')); ?>" method="post" data-registration-form>
				<input type="hidden" name="action" value="tj_registration_submit">
				<?php wp_nonce_field('tj_form_submit', 'tj_nonce'); ?>
				<p class="tj:m-0 tj:leading-normal tj:text-muted">Pole označená <span class="tj:text-link">*</span> jsou povinná. Mějte prosím na paměti, že <strong>elektronická forma přihlášky je závazná</strong>.</p>

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
					<p class="tj:mt-0 tj:mb-5 tj:leading-normal tj:text-muted">Vyplňte prosím jen <strong>jeden</strong> kontakt. Další kontakty můžete napsat do doplňujících informací.</p>
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
					<?php $field('dotaz', 'Zdravotní potřeby, alergie, dieta a další poznámky', '<textarea class="tj-input" id="%1$s" name="dotaz" rows="6"%2$s></textarea>', ['hint' => 'Cokoliv, co bychom měli vědět a nenašli jste pro to kolonku. Pokud máte táborový poukaz na slevu, opište sem kód z jeho pravého dolního rohu.']); ?>
					<p class="tj:mt-5 tj:mb-0 tj:text-[.95rem] tj:leading-normal tj:text-muted tj:[&_a]:font-extrabold tj:[&_a]:text-link">Přihlášením vyjadřujete svůj souhlas se zpracováním osobních údajů. Více podrobností naleznete zde: <a href="<?php echo esc_url(home_url('/ochrana-osobnich-udaju/#menu')); ?>">Prohlášení o zpracování osobních údajů</a></p>
					<?php $stepNav(true, 'Přihlásit dítě', 'submit'); ?>
				</fieldset>
			</form>
			<?php endif; ?>
		</div>
	</article>
</main>
<?php get_footer(); ?>
