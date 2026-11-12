<?php
/**
 * Homepage section heading: anchor target, optional eyebrow and the h2 the section is labelled by.
 *
 * @var array{anchor: string, id: string, title: string, eyebrow?: string, night?: bool} $args
 */
?>
<div class="tj:max-w-2xl tj:scroll-mt-26" id="<?php echo esc_attr($args['anchor']); ?>" data-reveal>
	<?php if (! empty($args['eyebrow'])) : ?><p class="tj-eyebrow"><?php echo esc_html($args['eyebrow']); ?></p><?php endif; ?>
	<h2 id="<?php echo esc_attr($args['id']); ?>" class="tj:m-0 tj:font-display tj:text-[clamp(2rem,4vw,3.25rem)] tj:leading-[1.02] tj:font-normal tj:tracking-[.01em] tj:uppercase tj:after:mt-[.85rem] tj:after:block tj:after:h-[4px] tj:after:w-14 tj:after:rounded-[2px] tj:after:bg-(--tj-season-accent,var(--tj-color-sun)) <?php echo empty($args['night']) ? 'tj:text-ink' : 'tj:text-on-night'; ?>"><?php echo esc_html($args['title']); ?></h2>
</div>
