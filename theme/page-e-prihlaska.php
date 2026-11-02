<?php
define('ZBYVA_MIST',1);
get_header();
?>
<script src='https://www.google.com/recaptcha/api.js?render=6Lcr1nwUAAAAAHWMp_WAEdhBM8D2Y2jE97t8GEZT'></script>
	<div class="blog_post">
	    <h2>Elektronická přihláška na tábor</h2>
	    <div class="blog_content">
			<?php if (ZBYVA_MIST==0): ?>
			<p class="bez_odsazeni do_bloku" style="font-size:110%;">
				<strong><span class="zvyraznit">TÁBOR JE JIŽ OBSAZEN!</span> Vaše přihláška bude zařazena na seznam náhradníků. Pokud se uvolní místo, ozveme se.</strong>
			</p>
			<?php elseif (ZBYVA_MIST < 20): ?>
			<p class="bez_odsazeni do_bloku" style="font-size:110%;">
				<strong><span class="zvyraznit">TÁBOR SE RYCHLE PLNÍ!</span> Počet volných míst: <?php echo ZBYVA_MIST; ?>, tak neváhejte!</strong>
			</p>
			<?php endif; ?>
			<p class="bez_odsazeni do_bloku">Pro vaše pohodlí je tu pro vás elektronická forma přihlášky.
			Zvýrazněná pole jsou <strong>povinná</strong> a proto je prosím řádně vyplňte.
			Po odeslání přihlášky bude na Vámi vyplněný kontaktní e-mail odeslána kopie přihlášky s pokyny k platbě a se základními údaji k táboru.
			Veďte prosím v patrnosti, že <strong>elektronická forma přihlášky je závazná</strong>.
			</p>
		<form id="eprihlaska" action="/odeslat-dotaz/?eprihlaska=1" method="post" onsubmit="return validate_eprihlaska()">
		    <div class="sekce">
			<h4>Základní údaje o přihlašovaném</h4>
			<div><label for="jmeno" class="zvyraznit">Jméno: </label></div>
			<input type="text" name="jmeno">
			<div><label for="prijmeni" class="zvyraznit">Příjmení: </label></div>
			<input type="text" name="prijmeni">
			<div><label for="datum_nar" class="zvyraznit">Datum narození: </label></div>
			<input type="date" name="datum_nar">
			<div><label for="rodne_c" class="zvyraznit">Rodné číslo: </label></div>
			<input type="text" name="rodne_c">
			<div><label for="adresa" class="zvyraznit">Adresa bydliště: </label></div>
			<input type="text" name="adresa">
			<div><label for="spolubydlici">S kým chci být ve stanu: </label></div>
			<input type="text" name="spolubydlici">
			<div><label for="nastup" class="zvyraznit">Kde se k vám připojím: </label></div>
			<select class="" name="nastup">
				<option value="">--Prosím zvolte možnost--</option>
			    <option value="ŽBK">Žamberk</option>
			    <option value="HK">Hradec Králové</option>
			    <option value="MS">Malá Skála</option>
			    <option value="JINÉ">Dám ještě vědět</option>
			</select>
			<div><label for="vystup" class="zvyraznit">Z tábora s Vámi pojedu do: </label></div>
			<select class="" name="vystup">
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
			<input type="text" name="kontakt_jmeno">
			<div><label for="kontakt_tel">Telefon: </label></div>
			<input type="text" name="kontakt_tel">
			<div><label for="kontakt_email" class="zvyraznit">E-mail: </label></div>
			<input type="text" name="kontakt_email">
		    </div>
		    <div class="sekce">
			<h4>Doplňující informace</h4>
			<p>
			(speciální zdravotní potřeby, alergie, dieta, případně cokoliv, co by nás mohlo zajímat, a nenašli jste na to příslušnou kolonku)
			</p>
			<p>
			<strong>Pokud vlastníte táborový poukaz na slevu, zde opište kód z pravého dolního rohu</strong>
			</p>
			<textarea name="dotaz"></textarea>
		    </div>
		    <div class="sekce">
			<input type="submit" value="přihlásit">
		    </div>
		    <div>
			<p class="oou">
			    Přihlášením vyjadřujete svůj souhlas se zpracováním osobních udajů. Více podrobností naleznete zde:
			    <a href="/ochrana-osobnich-udaju/#menu">Prohlášení o zpracování osobních údajů</a>
			</p>
		    </div>
		</form>
	    </div>
	</div>
    </main>
<?php
    get_sidebar();
?>
<script>
function validate_eprihlaska() {
    var result = true;
    var jmeno = document.forms["eprihlaska"]["jmeno"].value;
    var prijmeni = document.forms["eprihlaska"]["prijmeni"].value;
    var datum_nar = document.forms["eprihlaska"]["datum_nar"].value;
    var rodne_c = document.forms["eprihlaska"]["rodne_c"].value;
    var adresa = document.forms["eprihlaska"]["adresa"].value;
    var kontakt_email = document.forms["eprihlaska"]["kontakt_email"].value;
	var nastup = document.forms["eprihlaska"]["nastup"].value;
	var vystup = document.forms["eprihlaska"]["vystup"].value;
    var rc_re = /^\d{0,2}((0[1-9]|1[0-2])|(2[1-9]|3[0-2])|(5[1-9]|6[0-2])|(7[1-9]|8[0-2]))(0[1-9]|[1-2][0-9]|3[01])\/?[0-9]{3,4}$/;
    if (jmeno.trim()=="") {
	document.forms["eprihlaska"]["jmeno"].classList.add('chybny_vstup');
	result = false;
    } else {
	document.forms["eprihlaska"]["jmeno"].classList.remove('chybny_vstup');
    }
    if (prijmeni.trim()=="") {
	document.forms["eprihlaska"]["prijmeni"].classList.add('chybny_vstup');
	result = false;
    } else {
	document.forms["eprihlaska"]["prijmeni"].classList.remove('chybny_vstup');
    }
    if (datum_nar.trim()=="") {
	document.forms["eprihlaska"]["datum_nar"].classList.add('chybny_vstup');
	result = false;
    } else {
	document.forms["eprihlaska"]["datum_nar"].classList.remove('chybny_vstup');
    }
    if (rodne_c.trim()=="") {
	document.forms["eprihlaska"]["rodne_c"].classList.add('chybny_vstup');
	result = false;
    } else if (!rc_re.exec(rodne_c.trim())) {
	document.forms["eprihlaska"]["rodne_c"].classList.add('chybny_vstup');
	result = false;
	alert("Rodné číslo nemá správný formát");
    }
    else {
	document.forms["eprihlaska"]["rodne_c"].classList.remove('chybny_vstup');
    }
    if (adresa.trim()=="") {
	document.forms["eprihlaska"]["adresa"].classList.add('chybny_vstup');
	result = false;
    } else {
	document.forms["eprihlaska"]["adresa"].classList.remove('chybny_vstup');
    }
    if (kontakt_email.trim()=="") {
	document.forms["eprihlaska"]["kontakt_email"].classList.add('chybny_vstup');
	result = false;
    } else {
	document.forms["eprihlaska"]["kontakt_email"].classList.remove('chybny_vstup');
    }
	if (nastup.trim()=="") {
	document.forms["eprihlaska"]["nastup"].classList.add('chybny_vstup');
	result = false;
    } else {
	document.forms["eprihlaska"]["nastup"].classList.remove('chybny_vstup');
    }
	if (vystup.trim()=="") {
	document.forms["eprihlaska"]["vystup"].classList.add('chybny_vstup');
	result = false;
    } else {
	document.forms["eprihlaska"]["vystup"].classList.remove('chybny_vstup');
    }
    if (!result) { alert("Musíte vyplnit všechny povinné údaje.");}
    return result;
}
</script>
<?php
get_footer();
?>
<script>
grecaptcha.ready(function() {
grecaptcha.execute('6Lcr1nwUAAAAAHWMp_WAEdhBM8D2Y2jE97t8GEZT', {action: 'eprihlaska'})
.then(function(token) {
// Verify the token on the server.
});
});
</script>
