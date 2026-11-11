<?php
$heading = is_string($args['heading'] ?? null) ? $args['heading'] : 'Chcete nám poslat dotaz?';
$formId = is_string($args['form_id'] ?? null) ? $args['form_id'] : 'question-form';
?>
<section class="tj:rounded-xl tj:bg-season-surface tj:p-5 tj:sm:p-7" aria-labelledby="<?php echo esc_attr($formId . '-heading'); ?>">
	<h2 id="<?php echo esc_attr($formId . '-heading'); ?>" class="tj:mb-3 tj:mt-0 tj:text-3xl"><?php echo esc_html($heading); ?></h2>
	<p class="tj:mt-0">Napište nám a odpověď vám pošleme na uvedený e-mail.</p>
	<form id="<?php echo esc_attr($formId); ?>" class="tj:grid tj:gap-4" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
		<input type="hidden" name="action" value="tj_contact_submit">
		<?php wp_nonce_field('tj_form_submit', 'tj_nonce'); ?>
		<label class="tj:grid tj:gap-1 tj:font-bold" for="<?php echo esc_attr($formId . '-name'); ?>">
			Vaše jméno
			<input type="text" id="<?php echo esc_attr($formId . '-name'); ?>" name="jmeno" autocomplete="name" required>
		</label>
		<label class="tj:grid tj:gap-1 tj:font-bold" for="<?php echo esc_attr($formId . '-email'); ?>">
			E-mail pro odpověď
			<input type="email" id="<?php echo esc_attr($formId . '-email'); ?>" name="email" autocomplete="email" required>
		</label>
		<label class="tj:grid tj:gap-1 tj:font-bold" for="<?php echo esc_attr($formId . '-question'); ?>">
			Dotaz
			<textarea class="tj:min-h-40 tj:p-2" id="<?php echo esc_attr($formId . '-question'); ?>" name="dotaz" required></textarea>
		</label>
		<div><button type="submit">Odeslat dotaz</button></div>
	</form>
</section>
