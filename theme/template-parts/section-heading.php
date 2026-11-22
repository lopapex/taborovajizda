<?php
/**
 * Homepage section heading: anchor target, the h2 the section is labelled by and an optional intro sentence.
 *
 * @var array{anchor: string, id: string, title: string, lead?: string} $args
 */
?>
<div class="tj-flow tj:max-w-3xl tj:scroll-mt-26" id="<?php echo esc_attr($args['anchor']); ?>">
	<h2 id="<?php echo esc_attr($args['id']); ?>" class="tj-h2"><?php echo esc_html($args['title']); ?></h2>
	<?php if (! empty($args['lead'])) : ?><p class="tj-lead"><?php echo esc_html($args['lead']); ?></p><?php endif; ?>
</div>
