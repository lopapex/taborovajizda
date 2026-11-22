<?php
/**
 * Shared question form; its recipient is contactEmail from config/season.json.
 *
 * @var array{heading?: string, form_id?: string} $args
 */
$heading = is_string($args['heading'] ?? null) ? $args['heading'] : 'Pošlete nám dotaz';
$formId = is_string($args['form_id'] ?? null) ? $args['form_id'] : 'question-form';
$fields = [
    'name' => ['Vaše jméno', '<input class="tj-input" type="text" id="%s" name="jmeno" autocomplete="name" required>'],
    'email' => ['E-mail pro odpověď', '<input class="tj-input" type="email" id="%s" name="email" autocomplete="email" required>'],
    'question' => ['Dotaz', '<textarea class="tj-input tj:min-h-36" id="%s" name="dotaz" required></textarea>'],
];
?>
<section class="tj-card tj:h-full tj:border-0 tj:bg-night tj:p-5 tj:text-on-night tj:sm:p-7 tj:[&_:focus-visible]:outline-sun" aria-labelledby="<?php echo esc_attr($formId . '-heading'); ?>">
	<h3 id="<?php echo esc_attr($formId . '-heading'); ?>" class="tj-h3 tj:mb-4 tj:text-[1.5rem]"><?php echo esc_html($heading); ?></h3>
	<form id="<?php echo esc_attr($formId); ?>" class="tj:grid tj:gap-4" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
		<input type="hidden" name="action" value="tj_contact_submit">
		<?php wp_nonce_field('tj_form_submit', 'tj_nonce'); ?>
		<?php foreach ($fields as $key => [$label, $control]) : ?>
			<label class="tj:grid tj:gap-[.35rem] tj:font-semibold" for="<?php echo esc_attr("$formId-$key"); ?>">
				<?php echo esc_html($label); ?>
				<?php printf($control, esc_attr("$formId-$key")); // phpcs:ignore WordPress.Security.EscapeOutput -- static markup ?>
			</label>
		<?php endforeach; ?>
		<div><button class="tj-btn" type="submit">Odeslat dotaz</button></div>
	</form>
</section>
