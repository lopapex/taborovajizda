<?php
$season = taborovajizda_season();
get_header();
?>
	<div class="blog_post">
	    <h2>Elektronická přihláška na tábor</h2>
	    <div class="blog_content">
			<?php if ($season->registrationStatus() === 'full'): ?>
			<p class="bez_odsazeni do_bloku" style="font-size:110%;">
				<strong><span class="zvyraznit">TÁBOR JE JIŽ OBSAZEN!</span> Vaše přihláška bude zařazena na seznam náhradníků. Pokud se uvolní místo, ozveme se.</strong>
			</p>
			<?php endif; ?>
			<p class="bez_odsazeni do_bloku">Pro vaše pohodlí je tu pro vás elektronická forma přihlášky.
			Zvýrazněná pole jsou <strong>povinná</strong> a proto je prosím řádně vyplňte.
			Po odeslání přihlášky bude na Vámi vyplněný kontaktní e-mail odeslána kopie přihlášky s pokyny k platbě a se základními údaji k táboru.
			Veďte prosím v patrnosti, že <strong>elektronická forma přihlášky je závazná</strong>.
			</p>
		<form id="eprihlaska" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
			<input type="hidden" name="action" value="tj_registration_submit">
			<?php wp_nonce_field('tj_form_submit', 'tj_nonce'); ?>
		    <div class="sekce">
			<h4>Základní údaje o přihlašovaném</h4>
			<div><label for="jmeno" class="zvyraznit">Jméno: </label></div>
			<input type="text" id="jmeno" name="jmeno" autocomplete="given-name" required>
			<div><label for="prijmeni" class="zvyraznit">Příjmení: </label></div>
			<input type="text" id="prijmeni" name="prijmeni" autocomplete="family-name" required>
			<div><label for="datum_nar" class="zvyraznit">Datum narození: </label></div>
			<input type="date" id="datum_nar" name="datum_nar" autocomplete="bday" required>
			<div><label for="rodne_c" class="zvyraznit">Rodné číslo: </label></div>
			<input type="text" id="rodne_c" name="rodne_c" inputmode="numeric" required>
			<div><label for="adresa" class="zvyraznit">Adresa bydliště: </label></div>
			<input type="text" id="adresa" name="adresa" autocomplete="street-address" required>
			<div><label for="spolubydlici">S kým chci být ve stanu: </label></div>
			<input type="text" id="spolubydlici" name="spolubydlici">
			<div><label for="nastup" class="zvyraznit">Kde se k vám připojím: </label></div>
			<select id="nastup" name="nastup" required>
				<option value="">--Prosím zvolte možnost--</option>
			    <option value="ŽBK">Žamberk</option>
			    <option value="HK">Hradec Králové</option>
			    <option value="MS">Malá Skála</option>
			    <option value="JINÉ">Dám ještě vědět</option>
			</select>
			<div><label for="vystup" class="zvyraznit">Z tábora s Vámi pojedu do: </label></div>
			<select id="vystup" name="vystup" required>
				<option value="">--Prosím zvolte možnost--</option>
			    <option value="ŽBK">Žamberk</option>
			    <option value="HK">Hradec Králové</option>
			    <option value="TÁBOR">Nikam: Budu vyzvednut na táboře</option>
			    <option value="JINÉ">Dám ještě vědět</option>
			</select>
		    </div>
		    <div class="sekce">
			<h4>Kontakt na zákonného zástupce</h4>
			<p>
			(Vložte prosím pouze <strong>jeden</strong> kontakt. Pokud byste nám chtěli předat více kontaktů, použijte prostor pro doplňující informace níže.)
			</p>
			<div><label for="kontakt_jmeno">Jméno: </label></div>
			<input type="text" id="kontakt_jmeno" name="kontakt_jmeno" autocomplete="name">
			<div><label for="kontakt_tel">Telefon: </label></div>
			<input type="tel" id="kontakt_tel" name="kontakt_tel" autocomplete="tel">
			<div><label for="kontakt_email" class="zvyraznit">E-mail: </label></div>
			<input type="email" id="kontakt_email" name="kontakt_email" autocomplete="email" required>
		    </div>
		    <div class="sekce">
			<h4>Doplňující informace</h4>
			<p>
			(speciální zdravotní potřeby, alergie, dieta, případně cokoliv, co by nás mohlo zajímat, a nenašli jste na to příslušnou kolonku)
			</p>
			<p>
			<strong>Pokud vlastníte táborový poukaz na slevu, zde opište kód z pravého dolního rohu</strong>
			</p>
			<textarea id="dotaz" name="dotaz"></textarea>
		    </div>
		    <div class="sekce">
			<input type="submit" value="přihlásit">
		    </div>
		    <div>
			<p class="oou">
			    Přihlášením vyjadřujete svůj souhlas se zpracováním osobních udajů. Více podrobností naleznete zde:
			    <a href="<?php echo esc_url(home_url('/ochrana-osobnich-udaju/#menu')); ?>">Prohlášení o zpracování osobních údajů</a>
			</p>
		    </div>
		</form>
	    </div>
	</div>
    </main>
<?php
    get_sidebar();
?>

<?php
get_footer();
?>
