<?php
/**
 * Result of a form submission (?success= / ?error=, see app/Forms): a banner
 * under the header that says what happened and what to do next.
 */
$contactEmail = taborovajizda_season()->contactEmail();
$successMessages = [
	1 => ['Přihláška je přijatá', 'Na Váš e-mail jsme poslali její kopii a pokyny k platbě. Pokud zpráva do několika minut nedorazí, podívejte se do spamu nebo nám napište.'],
	2 => ['Dotaz je odeslaný', 'Odpověď pošleme na e-mail, který jste uvedli.'],
];
$errorMessages = [
	1 => 'E-mailová adresa není platná. Opravte ji prosím a odešlete formulář znovu.',
	2 => 'Chybí některý z povinných údajů. Doplňte ho prosím a odešlete formulář znovu.',
	3 => 'Přihlášku se nepodařilo přiřadit k letošnímu táboru.',
	4 => 'Přihlášku se nepodařilo uložit.',
	5 => 'Dítě s tímto rodným číslem už je na tábor přihlášené.',
	6 => 'E-mailová adresa není platná. Opravte ji prosím a odešlete formulář znovu.',
	7 => 'Chybí některý z povinných údajů. Doplňte ho prosím a odešlete formulář znovu.',
	15 => 'Rodné číslo nemá správný formát. Zkontrolujte ho prosím a odešlete přihlášku znovu.',
	100 => 'Formulář dorazil bez údajů. Vyplňte ho prosím znovu.',
	101 => 'Nepodařilo se spojit s databází.',
	102 => 'Požadavek se nepodařilo zpracovat.',
];
$success = isset($_GET['success']) ? absint($_GET['success']) : 0;
$error = isset($_GET['error']) ? absint($_GET['error']) : 0;
[$title, $message] = $successMessages[$success] ?? (isset($errorMessages[$error]) ? ['Odeslání se nepovedlo', $errorMessages[$error]] : ['', '']);
if ($message === '') {
	return;
}
$failed = ! isset($successMessages[$success]);
?>
<div class="tj:relative tj:z-10 tj:border-b tj:border-ink/10 <?php echo $failed ? 'tj:bg-error tj:text-white' : 'tj:bg-grass tj:text-night'; ?>" role="<?php echo $failed ? 'alert' : 'status'; ?>" data-notice>
	<div class="tj-container tj:flex tj:flex-wrap tj:items-center tj:gap-x-6 tj:gap-y-3 tj:py-4">
		<p class="tj:m-0 tj:min-w-0 tj:flex-1 tj:basis-80 tj:leading-snug">
			<strong class="tj:block tj:font-heading tj:text-[1.2rem]"><?php echo esc_html($title); ?></strong>
			<?php echo esc_html($message); ?>
			<?php if ($failed) : ?>Kdyby to nešlo ani napodruhé, napište nám na <a class="tj:font-bold tj:text-inherit" href="mailto:<?php echo esc_attr($contactEmail); ?>"><?php echo esc_html($contactEmail); ?></a>.<?php endif; ?>
		</p>
		<button class="tj-btn tj-btn-quiet tj:nojs:hidden" type="button" data-notice-close>Zavřít</button>
	</div>
</div>
