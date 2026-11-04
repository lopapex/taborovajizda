<?php
// díky wordpressu (a mojí lenosti) jsou linky nepoužitelné pro normální $_GET, proto je potřeba ruční parsing
if (!empty($_SERVER['QUERY_STRING'])) {
	parse_str($_SERVER['QUERY_STRING'], $get); 
	if (!empty($get['success'])) {
		if ($get['success'] == '1') { 	
?>
			<div class="notification_container">
				<div class="notification">
					<h2>Zpráva</h2>
					<p>
						Přihláška byla úspěšně přijata. Zkontrolujte, že vám přišel potvrzující e-mail. 
						Pokud e-mail během několika minut nedorazí, kontaktujte nás na <a target="_blank" href="mailto:info@taborovajizda.cz">info@taborovajizda.cz</a>.
					</p>
					<button type="button" class="btn_close" data-dialog-close><?php echo esc_html__('Zavřít', 'taborovajizda'); ?></button>
				</div>
			</div>
<?php
		}
		elseif ($get['success'] == '2') { 	
?>
			<div class="notification_container">
				<div class="notification">
					<h2>Zpráva</h2>
					<p>
						Váš dotaz byl úspěšně odeslán. Odpověď obdržíte na Vámi zadanou e-mailovou adresu.
					</p>
					<button type="button" class="btn_close" data-dialog-close><?php echo esc_html__('Zavřít', 'taborovajizda'); ?></button>
				</div>
			</div>
<?php
		}
		elseif ($get['success'] == '3') { 	
?>
			<div class="notification_container">
				<div class="notification">
					<h2>Zpráva</h2>
					<p>
						E-mailová adresa byla uložena a zároveň na ni byla odeslána potvrzující zpráva. 
						Adresu je nutné nejprve ověřit, v opačném případě nebudete dostávat žádné zprávy o aktuálním dění.
						Nepotvrzené e-mailové adresy budou po 30 dnech z databáze odstraněny.
					</p>
					<button type="button" class="btn_close" data-dialog-close><?php echo esc_html__('Zavřít', 'taborovajizda'); ?></button>
				</div>
			</div>
<?php
		}
		elseif ($get['success'] == '4') { 	
?>
			<div class="notification_container">
				<div class="notification">
					<h2>Zpráva</h2>
					<p>
						Ověřovací zpráva byla úspěšně odeslána. 
						E-mailovou adresu je nutné ověřit, v opačném případě nebudete dostávat žádné zprávy o aktuálním dění.
						Nepotvrzené e-mailové adresy budou po 30 dnech z databáze odstraněny.
					</p>
					<button type="button" class="btn_close" data-dialog-close><?php echo esc_html__('Zavřít', 'taborovajizda'); ?></button>
				</div>
			</div>
<?php
		}
	}
	elseif (!empty($get['error'])) { 
?>
		<div class="notification_container">
			<div class="notification">
				<h2>Omlouváme se, ale došlo k chybě</h2>
				<p>
				Chyba: 
<?php
				switch($get['error']) {
					case 1: echo 'Byla zadána neplatná e-mailová adresa.'; break;
					case 2: echo 'Chybí některý z povinných údajů.'; break;
					case 3: echo 'Selhalo nalezení aktuálního tábora v databázi.'; break;
					case 4: echo 'Selhalo vložení nové přihlášky do databáze.'; break;
					case 5: echo 'Selhalo vložení nové přihlášky, protože dítě s uvedeným rodným číslem je již na tábor přihlášeno.'; break;
					case 6: echo 'Byla zadána neplatná e-mailová adresa.'; break;
					case 7: echo 'Nebyly vyplněny povinné položky.'; break;
					case 8: echo 'Byla zadána neplatná e-mailová adresa.'; break;
					case 9: echo 'Selhalo vložení e-mailové adresy do databáze.'; break;
					case 10: echo 'Chybí některý z povinných údajů (e-mailová adresa).'; break;
					case 11: echo 'Selhalo vložení e-mailové adresy - adresa je již v databázi.'; break;
					case 12: echo 'Selhalo vložení e-mailové adresy - adresa je již v databázi, ale nebyla ověřena.'
									.'<a href="/odeslat-dotaz/?validation_resend='.$get['email'].'"> → Znovu zaslat ověřovací zprávu ←</a>'; 
						break;
					case 13: echo 'Znovuověření není možné. Byla zadána neplatná e-mailová adresa.'; break;
					case 14: echo 'Znovuověření není možné. Adresa není v databázi nebo je již ověřena.'; break;
					case 15: echo 'Bylo zadáno neplatné rodné číslo.'; break;
					case 100: echo 'Chybí udaje ke zpracování.'; break;
					case 101: echo 'Chyba při spojení s databází.'; break;
					case 102: echo 'Chybné použití skriptu - nezpracovatelný požadavek.'; break;
					default: echo 'Neznámá chyba.';
				}
?>
				</p>
				<button type="button" class="btn_close" data-dialog-close><?php echo esc_html__('Zavřít', 'taborovajizda'); ?></button>
			</div>
		</div>
<?php
	}
}
?>
