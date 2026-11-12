<?php
$season = taborovajizda_season();
$remainingPlaces = $season->remainingPlaces();
$acceptsRegistrations = $season->acceptsRegistrations();
$debugForm = ! $acceptsRegistrations && taborovajizda_debug_registration_cta();
$showForm = $acceptsRegistrations || $debugForm;
$registrationFacts = array_filter([
    'Termín' => $season->formattedDateRange(),
    'Místo' => $season->location(),
    'Věk' => $season->formattedAgeRange(),
    'Cena' => $season->formattedPrice() ?? 'Upřesníme',
], static fn (?string $value): bool => $value !== null);
$required = '<span class="tj-field__required" aria-hidden="true">*</span>';
get_header();
?>
<main id="main-content" class="tj-home-main">
	<article class="tj-registration-page">
		<header class="tj-registration-page__header">
			<p class="tj-home-eyebrow">Táborová jízda <?php echo esc_html((string) $season->year()); ?></p>
			<h1>Elektronická přihláška</h1>
			<p>Tři krátké kroky: údaje o dítěti, kontakt na Vás a doplňující informace. Kopii přihlášky i pokyny k platbě Vám pošleme na e-mail.</p>
			<dl class="tj-home-facts tj-registration-facts" aria-label="Základní informace o táboře">
				<?php foreach ($registrationFacts as $label => $value) : ?>
					<div><dt><?php echo esc_html($label); ?></dt><dd><?php echo esc_html($value); ?></dd></div>
				<?php endforeach; ?>
			</dl>
		</header>

		<div class="tj-registration-page__content">
			<?php if (! $acceptsRegistrations) : ?>
				<div class="tj-registration-notice tj-registration-notice--closed" role="status">
					<p><strong>Přihlášky na ročník <?php echo esc_html((string) $season->year()); ?> jsou uzavřené.</strong> Termín dalšího ročníku zveřejníme na <a href="<?php echo esc_url(home_url('/')); ?>">hlavní stránce</a>.</p>
					<?php if ($debugForm) : ?><p class="tj-is-debug-cta">Ladicí zobrazení: formulář je vidět jen mimo produkci (lokál a stage) a odeslání se pouze simuluje.</p><?php endif; ?>
				</div>
			<?php elseif ($season->isFull()) : ?>
				<div class="tj-registration-notice" role="status"><p><strong>Tábor je již obsazen.</strong> Vaše přihláška bude zařazena na seznam náhradníků. Pokud se uvolní místo, ozveme se.</p></div>
			<?php elseif ($remainingPlaces !== null && $remainingPlaces < 20) : ?>
				<div class="tj-registration-notice" role="status"><p><strong>Tábor se rychle plní.</strong> Počet volných míst: <?php echo esc_html((string) $remainingPlaces); ?>.</p></div>
			<?php endif; ?>

			<?php if ($showForm) : ?>
			<ol class="tj-registration-steps" data-registration-steps hidden>
				<li><button class="tj-home-step" type="button" data-step-target="1" aria-current="step"><span>1</span> Dítě</button></li>
				<li><button class="tj-home-step" type="button" data-step-target="2" disabled><span>2</span> Kontakt</button></li>
				<li><button class="tj-home-step" type="button" data-step-target="3" disabled><span>3</span> Dokončení</button></li>
			</ol>

			<form id="eprihlaska" class="eprihlaska tj-registration-form" action="<?php echo esc_url(home_url('/odeslat-dotaz/')); ?>" method="post" data-registration-form>
				<input type="hidden" name="action" value="tj_registration_submit">
				<?php wp_nonce_field('tj_form_submit', 'tj_nonce'); ?>
				<p class="tj-registration-form__legend">Pole označená <span class="tj-field__required">*</span> jsou povinná. Mějte prosím na paměti, že <strong>elektronická forma přihlášky je závazná</strong>.</p>

				<fieldset class="tj-form-step" data-step="1">
					<legend>Údaje o dítěti</legend>
					<div class="tj-field-grid">
						<div class="tj-field"><label for="jmeno">Jméno <?php echo $required; ?></label><input class="tj-registration-control" type="text" id="jmeno" name="jmeno" autocomplete="given-name" required></div>
						<div class="tj-field"><label for="prijmeni">Příjmení <?php echo $required; ?></label><input class="tj-registration-control" type="text" id="prijmeni" name="prijmeni" autocomplete="family-name" required></div>
						<div class="tj-field"><label for="datum_nar" id="datum_nar-label">Datum narození <?php echo $required; ?></label><input class="tj-registration-control" type="date" id="datum_nar" name="datum_nar" autocomplete="bday" required data-czech-date></div>
						<div class="tj-field"><label for="rodne_c">Rodné číslo <?php echo $required; ?></label><input class="tj-registration-control" type="text" id="rodne_c" name="rodne_c" inputmode="numeric" aria-describedby="rodne_c-hint" required><p class="tj-field__hint" id="rodne_c-hint">S lomítkem i bez něj.</p></div>
						<div class="tj-field tj-field--wide"><label for="adresa">Adresa bydliště <?php echo $required; ?></label><input class="tj-registration-control" type="text" id="adresa" name="adresa" autocomplete="street-address" aria-describedby="adresa-hint" required><p class="tj-field__hint" id="adresa-hint">Ulice a číslo domu, obec a PSČ.</p></div>
						<div class="tj-field tj-field--wide"><label for="spolubydlici">S kým chci být ve stanu</label><input class="tj-registration-control" type="text" id="spolubydlici" name="spolubydlici"></div>
						<div class="tj-field"><label for="nastup">Kde se k vám připojím <?php echo $required; ?></label>
							<select class="tj-registration-control" id="nastup" name="nastup" required><option value="">Vyberte místo</option><option value="ŽBK">Žamberk</option><option value="HK">Hradec Králové</option><option value="MS">Malá Skála</option><option value="JINÉ">Dám ještě vědět</option></select></div>
						<div class="tj-field"><label for="vystup">Z tábora s vámi pojedu do <?php echo $required; ?></label>
							<select class="tj-registration-control" id="vystup" name="vystup" required><option value="">Vyberte místo</option><option value="ŽBK">Žamberk</option><option value="HK">Hradec Králové</option><option value="TÁBOR">Nikam: Budu vyzvednut na táboře</option><option value="JINÉ">Dám ještě vědět</option></select></div>
					</div>
					<div class="tj-form-step__nav"><button class="tj-home-button" type="button" data-step-next>Pokračovat</button></div>
				</fieldset>

				<fieldset class="tj-form-step" data-step="2">
					<legend>Kontakt na zákonného zástupce</legend>
					<p class="tj-form-step__hint">Vyplňte prosím jen <strong>jeden</strong> kontakt. Další kontakty můžete napsat do doplňujících informací.</p>
					<div class="tj-field-grid">
						<div class="tj-field"><label for="kontakt_jmeno">Jméno a příjmení</label><input class="tj-registration-control" type="text" id="kontakt_jmeno" name="kontakt_jmeno" autocomplete="name"></div>
						<div class="tj-field"><label for="kontakt_tel">Telefon</label><input class="tj-registration-control" type="tel" id="kontakt_tel" name="kontakt_tel" autocomplete="tel"></div>
						<div class="tj-field tj-field--wide"><label for="kontakt_email">E-mail <?php echo $required; ?></label><input class="tj-registration-control" type="email" id="kontakt_email" name="kontakt_email" autocomplete="email" aria-describedby="kontakt_email-hint" required><p class="tj-field__hint" id="kontakt_email-hint">Sem pošleme kopii přihlášky a pokyny k platbě.</p></div>
					</div>
					<div class="tj-form-step__nav"><button class="tj-home-button tj-home-button--quiet" type="button" data-step-prev>Zpět</button><button class="tj-home-button" type="button" data-step-next>Pokračovat</button></div>
				</fieldset>

				<fieldset class="tj-form-step" data-step="3">
					<legend>Doplňující informace</legend>
					<div class="tj-field">
						<label for="dotaz">Zdravotní potřeby, alergie, dieta a další poznámky</label>
						<textarea class="tj-registration-control" id="dotaz" name="dotaz" rows="6" aria-describedby="dotaz-hint"></textarea>
						<p class="tj-field__hint" id="dotaz-hint">Cokoliv, co bychom měli vědět a nenašli jste pro to kolonku. Pokud máte táborový poukaz na slevu, opište sem kód z jeho pravého dolního rohu.</p>
					</div>
					<p class="tj-registration-consent">Přihlášením vyjadřujete svůj souhlas se zpracováním osobních údajů. Více podrobností naleznete zde: <a href="<?php echo esc_url(home_url('/ochrana-osobnich-udaju/#menu')); ?>">Prohlášení o zpracování osobních údajů</a></p>
					<div class="tj-form-step__nav"><button class="tj-home-button tj-home-button--quiet" type="button" data-step-prev>Zpět</button><button class="tj-home-button" type="submit">Přihlásit dítě</button></div>
				</fieldset>
			</form>
			<?php endif; ?>
		</div>
	</article>
</main>
<?php get_footer(); ?>
