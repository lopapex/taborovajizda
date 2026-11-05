<?php
$season = taborovajizda_season();
$remainingPlaces = $season->remainingPlaces();
get_header();
?>
<article class="blog_post">
	<h2 class="tj-card-title">Elektronická přihláška na tábor</h2>
	<div class="blog_content">
		<?php if ($season->isFull()) : ?>
			<p class="bez_odsazeni do_bloku tj:text-[110%]"><strong><span class="zvyraznit">TÁBOR JE JIŽ OBSAZEN!</span> Vaše přihláška bude zařazena na seznam náhradníků. Pokud se uvolní místo, ozveme se.</strong></p>
		<?php elseif ($remainingPlaces !== null && $remainingPlaces < 20) : ?>
			<p class="bez_odsazeni do_bloku tj:text-[110%]"><strong><span class="zvyraznit">TÁBOR SE RYCHLE PLNÍ!</span> Počet volných míst: <?php echo esc_html((string) $remainingPlaces); ?>, tak neváhejte!</strong></p>
		<?php endif; ?>
		<p class="bez_odsazeni do_bloku">Pro vaše pohodlí je tu pro vás elektronická forma přihlášky.
		Zvýrazněná pole jsou <strong>povinná</strong> a proto je prosím řádně vyplňte.
		Po odeslání přihlášky bude na Vámi vyplněný kontaktní e-mail odeslána kopie přihlášky s pokyny k platbě a se základními údaji k táboru.
		Veďte prosím v patrnosti, že <strong>elektronická forma přihlášky je závazná</strong>.</p>
		<form id="eprihlaska" class="eprihlaska tj:px-4" action="<?php echo esc_url(home_url('/odeslat-dotaz/')); ?>" method="post">
			<input type="hidden" name="action" value="tj_registration_submit">
			<?php wp_nonce_field('tj_form_submit', 'tj_nonce'); ?>
			<section class="sekce tj-form-section">
				<h4 class="tj-form-heading">Základní údaje o přihlašovaném</h4>
				<div class="tj-registration-label"><label for="jmeno" class="zvyraznit">Jméno: </label></div><input class="tj-registration-control" type="text" id="jmeno" name="jmeno" autocomplete="given-name" required>
				<div class="tj-registration-label"><label for="prijmeni" class="zvyraznit">Příjmení: </label></div><input class="tj-registration-control" type="text" id="prijmeni" name="prijmeni" autocomplete="family-name" required>
				<div class="tj-registration-label"><label for="datum_nar" class="zvyraznit">Datum narození: </label></div><input class="tj-registration-control" type="date" id="datum_nar" name="datum_nar" autocomplete="bday" required>
				<div class="tj-registration-label"><label for="rodne_c" class="zvyraznit">Rodné číslo: </label></div><input class="tj-registration-control" type="text" id="rodne_c" name="rodne_c" inputmode="numeric" required>
				<div class="tj-registration-label"><label for="adresa" class="zvyraznit">Adresa bydliště: </label></div><input class="tj-registration-control" type="text" id="adresa" name="adresa" autocomplete="street-address" required>
				<div class="tj-registration-label"><label for="spolubydlici">S kým chci být ve stanu: </label></div><input class="tj-registration-control" type="text" id="spolubydlici" name="spolubydlici">
				<div class="tj-registration-label"><label for="nastup" class="zvyraznit">Kde se k vám připojím: </label></div>
				<select class="tj-registration-control" id="nastup" name="nastup" required><option value="">--Prosím zvolte možnost--</option><option value="ŽBK">Žamberk</option><option value="HK">Hradec Králové</option><option value="MS">Malá Skála</option><option value="JINÉ">Dám ještě vědět</option></select>
				<div class="tj-registration-label"><label for="vystup" class="zvyraznit">Z tábora s Vámi pojedu do: </label></div>
				<select class="tj-registration-control" id="vystup" name="vystup" required><option value="">--Prosím zvolte možnost--</option><option value="ŽBK">Žamberk</option><option value="HK">Hradec Králové</option><option value="TÁBOR">Nikam: Budu vyzvednut na táboře</option><option value="JINÉ">Dám ještě vědět</option></select>
			</section>
			<section class="sekce tj-form-section">
				<h4 class="tj-form-heading">Kontakt na zákonného zástupce</h4>
				<p class="tj:basis-full">(Vložte prosím pouze <strong>jeden</strong> kontakt. Pokud byste nám chtěli předat více kontaktů, použijte prostor pro doplňující informace níže.)</p>
				<div class="tj-registration-label"><label for="kontakt_jmeno">Jméno: </label></div><input class="tj-registration-control" type="text" id="kontakt_jmeno" name="kontakt_jmeno" autocomplete="name">
				<div class="tj-registration-label"><label for="kontakt_tel">Telefon: </label></div><input class="tj-registration-control" type="tel" id="kontakt_tel" name="kontakt_tel" autocomplete="tel">
				<div class="tj-registration-label"><label for="kontakt_email" class="zvyraznit">E-mail: </label></div><input class="tj-registration-control" type="email" id="kontakt_email" name="kontakt_email" autocomplete="email" required>
			</section>
			<section class="sekce tj-form-section">
				<h4 class="tj-form-heading">Doplňující informace</h4>
				<p class="tj:basis-full">(speciální zdravotní potřeby, alergie, dieta, případně cokoliv, co by nás mohlo zajímat, a nenašli jste na to příslušnou kolonku)</p>
				<p class="tj:basis-full"><strong>Pokud vlastníte táborový poukaz na slevu, zde opište kód z pravého dolního rohu</strong></p>
				<textarea class="tj-registration-control tj:h-40 tj:basis-full tj:px-2 tj:py-[.3em]" id="dotaz" name="dotaz"></textarea>
			</section>
			<section class="sekce tj-form-section"><input class="tj-registration-control" type="submit" value="přihlásit"></section>
			<p class="oou tj:text-[.8em] tj:italic">Přihlášením vyjadřujete svůj souhlas se zpracováním osobních udajů. Více podrobností naleznete zde: <a href="<?php echo esc_url(home_url('/ochrana-osobnich-udaju/#menu')); ?>">Prohlášení o zpracování osobních údajů</a></p>
		</form>
	</div>
</article>
</main>
<?php get_sidebar(); ?>
<?php get_footer(); ?>
