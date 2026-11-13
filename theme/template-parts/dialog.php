<?php
/**
 * Content dialog. Openers link to a real URL (or to "#<id>") and carry
 * data-dialog-open="<id>", so the content stays reachable without JavaScript
 * and every open dialog has an address (initialiseDialogs).
 *
 * @var array{id: string, meta: string, title: string, content: callable, close?: string, attributes?: string} $args
 */
?>
<dialog class="tj-dialog tj:w-[min(100%-1.5rem,52rem)] tj:max-h-[min(92dvh,60rem)] tj:overflow-y-auto tj:overscroll-contain tj:rounded-2xl tj:border-0 tj:bg-surface tj:p-0 tj:text-ink tj:shadow-dialog tj:backdrop:bg-night/60" id="<?php echo esc_attr($args['id']); ?>" aria-labelledby="<?php echo esc_attr($args['id'] . '-title'); ?>"<?php echo $args['attributes'] ?? ''; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped by the caller ?>>
	<div class="tj:p-[clamp(1.25rem,4vw,2.75rem)]">
		<div class="tj:flex tj:items-start tj:justify-between tj:gap-4">
			<p class="tj-meta tj:mt-[.7rem]" data-dialog-meta><?php echo esc_html($args['meta']); ?></p>
			<button class="tj-btn tj-btn-quiet tj:min-h-[44px] tj:px-4 tj:py-2 tj:text-[.95rem]" type="button" data-dialog-close aria-label="<?php echo esc_attr($args['close'] ?? 'Zavřít'); ?>">Zavřít</button>
		</div>
		<h2 class="tj-h2 tj:mt-3 tj:mb-6 tj:text-[clamp(1.6rem,3.4vw,2.4rem)]" id="<?php echo esc_attr($args['id'] . '-title'); ?>"><?php echo esc_html($args['title']); ?></h2>
		<?php ($args['content'])(); ?>
	</div>
</dialog>
