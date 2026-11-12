<?php
$contactEmail = \TaborovaJizda\Theme\Season\Season::current()->contactEmail();
$successMessages = [
	1 => sprintf('Přihláška byla úspěšně přijata. Zkontrolujte, že vám přišel potvrzující e-mail. Pokud e-mail během několika minut nedorazí, kontaktujte nás na %s.', $contactEmail),
	2 => 'Váš dotaz byl úspěšně odeslán. Odpověď obdržíte na Vámi zadanou e-mailovou adresu.',
	3 => 'E-mailová adresa byla uložena a zároveň na ni byla odeslána potvrzující zpráva. Adresu je nutné nejprve ověřit, v opačném případě nebudete dostávat žádné zprávy o aktuálním dění. Nepotvrzené e-mailové adresy budou po 30 dnech z databáze odstraněny.',
	4 => 'Ověřovací zpráva byla úspěšně odeslána. E-mailovou adresu je nutné ověřit, v opačném případě nebudete dostávat žádné zprávy o aktuálním dění. Nepotvrzené e-mailové adresy budou po 30 dnech z databáze odstraněny.',
];
$errorMessages = [
	1 => 'Byla zadána neplatná e-mailová adresa.', 2 => 'Chybí některý z povinných údajů.', 3 => 'Selhalo nalezení aktuálního tábora v databázi.',
	4 => 'Selhalo vložení nové přihlášky do databáze.', 5 => 'Selhalo vložení nové přihlášky, protože dítě s uvedeným rodným číslem je již na tábor přihlášeno.',
	6 => 'Byla zadána neplatná e-mailová adresa.', 7 => 'Nebyly vyplněny povinné položky.', 8 => 'Byla zadána neplatná e-mailová adresa.',
	9 => 'Selhalo vložení e-mailové adresy do databáze.', 10 => 'Chybí některý z povinných údajů (e-mailová adresa).',
	11 => 'Selhalo vložení e-mailové adresy – adresa je již v databázi.', 12 => 'Selhalo vložení e-mailové adresy – adresa je již v databázi, ale nebyla ověřena.',
	13 => 'Znovuověření není možné. Byla zadána neplatná e-mailová adresa.', 14 => 'Znovuověření není možné. Adresa není v databázi nebo je již ověřena.',
	15 => 'Bylo zadáno neplatné rodné číslo.', 100 => 'Chybí údaje ke zpracování.', 101 => 'Chyba při spojení s databází.', 102 => 'Chybné použití skriptu – nezpracovatelný požadavek.',
];
$success = isset($_GET['success']) ? absint($_GET['success']) : 0;
$error = isset($_GET['error']) ? absint($_GET['error']) : 0;
$message = $successMessages[$success] ?? ($errorMessages[$error] ?? '');
if ($message === '') {
	return;
}
?>
<div class="notification_container tj:fixed tj:inset-0 tj:z-[200] tj:h-full tj:w-screen" role="dialog" aria-modal="true">
	<div class="notification tj:absolute tj:left-1/2 tj:top-1/2 tj:w-[90%] tj:-translate-x-1/2 tj:-translate-y-1/2 tj:pb-4 tj:md:w-1/2">
		<h2 class="tj-card-title"><?php echo $error ? 'Omlouváme se, ale došlo k chybě' : 'Zpráva'; ?></h2>
		<p class="tj:px-4 tj:pb-8 tj:pt-2"><?php echo $error ? 'Chyba: ' : ''; ?><?php echo esc_html($message); ?>
		<?php if ($error === 12 && isset($_GET['email'])) : ?><a href="<?php echo esc_url(add_query_arg('validation_resend', sanitize_email(wp_unslash($_GET['email'])), home_url('/odeslat-dotaz/'))); ?>"> → Znovu zaslat ověřovací zprávu ←</a><?php endif; ?></p>
		<button type="button" class="btn_close tj:absolute tj:bottom-[10px] tj:right-[10px] tj:cursor-pointer tj:px-[10px] tj:py-[2px]" data-dialog-close>Zavřít</button>
	</div>
</div>
