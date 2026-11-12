<?php
/**
 * Paging carousel (resources/ts/app.ts → initialisePagers). Without JavaScript
 * the track is a plain grid.
 *
 * @var array{id: string, prev: string, next: string, class?: string, tag?: string, track?: string, label?: string, items: callable} $args
 */
$tag = $args['tag'] ?? 'div';
?>
<div class="tj-pager <?php echo esc_attr($args['class'] ?? ''); ?>" data-pager data-reveal>
	<button class="tj-arrow" data-dir="prev" type="button" aria-controls="<?php echo esc_attr($args['id']); ?>" aria-label="<?php echo esc_attr($args['prev']); ?>" disabled></button>
	<div data-pager-viewport>
		<<?php echo $tag; ?> class="<?php echo esc_attr($args['track'] ?? ''); ?>" id="<?php echo esc_attr($args['id']); ?>" data-pager-track<?php echo isset($args['label']) ? ' aria-label="' . esc_attr($args['label']) . '"' : ''; ?>>
			<?php ($args['items'])(); ?>
		</<?php echo $tag; ?>>
	</div>
	<button class="tj-arrow" data-dir="next" type="button" aria-controls="<?php echo esc_attr($args['id']); ?>" aria-label="<?php echo esc_attr($args['next']); ?>"></button>
	<p data-pager-status aria-live="polite"></p>
</div>
