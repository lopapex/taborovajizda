<?php
/**
 * Result of a form submission (?success= / ?error=, see app/Forms).
 */
$contactEmail = taborovajizda_season()->contactEmail();
$successMessages = [
	1 => sprintf('Přihláška byla úspěšně přijata. Zkontrolujte, že Vám přišel potvrzující e-mail. Pokud e-mail během několika minut nedorazí, kontaktujte nás na %s.', $contactEmail),
	2 => 'Váš dotaz byl úspěšně odeslán. Odpověď obdržíte na Vámi zadanou e-mailovou adresu.',
];
$errorMessages = [
	1 => 'Byla zadána neplatná e-mailová adresa.',
	2 => 'Chybí některý z povinných údajů.',
	3 => 'Selhalo nalezení aktuálního tábora v databázi.',
	4 => 'Selhalo vložení nové přihlášky do databáze.',
	5 => 'Selhalo vložení nové přihlášky, protože dítě s uvedeným rodným číslem je již na tábor přihlášeno.',
	6 => 'Byla zadána neplatná e-mailová adresa.',
	7 => 'Nebyly vyplněny povinné položky.',
	15 => 'Bylo zadáno neplatné rodné číslo.',
	100 => 'Chybí údaje ke zpracování.',
	101 => 'Chyba při spojení s databází.',
	102 => 'Chybné použití skriptu – nezpracovatelný požadavek.',
];
$success = isset($_GET['success']) ? absint($_GET['success']) : 0;
$error = isset($_GET['error']) ? absint($_GET['error']) : 0;
$message = $successMessages[$success] ?? ($errorMessages[$error] ?? '');
if ($message === '') {
	return;
}
?>
<div class="tj:fixed tj:inset-0 tj:z-200 tj:grid tj:place-items-center tj:bg-ink/55 tj:p-4" role="dialog" aria-modal="true" aria-labelledby="form-result-title">
	<div class="tj-panel tj:w-full tj:max-w-xl tj:bg-white">
		<h2 id="form-result-title" class="tj:mt-0 tj:mb-3 tj:text-ink"><?php echo $error ? 'Omlouváme se, ale došlo k chybě' : 'Hotovo'; ?></h2>
		<p class="tj:mt-0 tj:mb-6 tj:leading-normal tj:text-muted"><?php echo esc_html($message); ?></p>
		<button class="tj-btn" type="button" data-dialog-close>Zavřít</button>
	</div>
</div>
