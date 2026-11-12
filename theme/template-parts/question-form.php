<?php
/**
 * Shared question form; its recipient is contactEmail from config/season.json.
 *
 * @var array{heading?: string, form_id?: string} $args
 */
$heading = is_string($args['heading'] ?? null) ? $args['heading'] : 'Chcete nám poslat dotaz?';
$formId = is_string($args['form_id'] ?? null) ? $args['form_id'] : 'question-form';
$fields = [
    'name' => ['Vaše jméno', '<input class="tj-input" type="text" id="%s" name="jmeno" autocomplete="name" required>'],
    'email' => ['E-mail pro odpověď', '<input class="tj-input" type="email" id="%s" name="email" autocomplete="email" required>'],
    'question' => ['Dotaz', '<textarea class="tj-input tj:min-h-40" id="%s" name="dotaz" required></textarea>'],
];
?>
<section class="tj:h-full tj:rounded-xl tj:border-t-4 tj:border-sky tj:bg-surface tj:p-5 tj:shadow-soft tj:sm:p-7" aria-labelledby="<?php echo esc_attr($formId . '-heading'); ?>">
	<h2 id="<?php echo esc_attr($formId . '-heading'); ?>" class="tj:mt-0 tj:mb-3 tj:text-3xl tj:tracking-[-.035em] tj:text-ink"><?php echo esc_html($heading); ?></h2>
	<p class="tj:mt-0">Napište nám a odpověď Vám pošleme na uvedený e-mail.</p>
	<form id="<?php echo esc_attr($formId); ?>" class="tj:grid tj:gap-4" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
		<input type="hidden" name="action" value="tj_contact_submit">
		<?php wp_nonce_field('tj_form_submit', 'tj_nonce'); ?>
		<?php foreach ($fields as $key => [$label, $control]) : ?>
			<label class="tj:grid tj:gap-1 tj:font-bold" for="<?php echo esc_attr("$formId-$key"); ?>">
				<?php echo esc_html($label); ?>
				<?php printf($control, esc_attr("$formId-$key")); // phpcs:ignore WordPress.Security.EscapeOutput -- static markup ?>
			</label>
		<?php endforeach; ?>
		<div><button class="tj-btn tj:px-[1.1rem]" type="submit">Odeslat dotaz</button></div>
	</form>
</section>
