<?php
/**
 * Content dialog. Openers link to a real URL (or to "#<id>") and carry
 * data-dialog-open="<id>", so the content stays reachable without JavaScript
 * and every open dialog has an address (initialiseDialogs). It sits near the
 * top instead of the centre, so content that loads or unfolds later only
 * grows it downwards and the dialog never jumps.
 *
 * @var array{id: string, meta: string, title: string, content: callable, close?: string, attributes?: string} $args
 */
?>
<dialog class="tj-dialog tj:mt-[4dvh] tj:mb-auto tj:w-[min(100%-1.5rem,52rem)] tj:max-h-[min(92dvh,60rem)] tj:overflow-y-auto tj:overscroll-contain tj:rounded-2xl tj:border-0 tj:bg-surface tj:p-0 tj:text-ink tj:shadow-dialog tj:backdrop:bg-night/60" id="<?php echo esc_attr($args['id']); ?>" aria-labelledby="<?php echo esc_attr($args['id'] . '-title'); ?>"<?php echo $args['attributes'] ?? ''; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped by the caller ?>>
	<div class="tj:p-[clamp(1.25rem,4vw,2.75rem)]">
		<div class="tj:flex tj:items-start tj:justify-between tj:gap-4">
			<p class="tj-meta tj:mt-[.7rem]" data-dialog-meta><?php echo esc_html($args['meta']); ?></p>
			<div class="tj:flex tj:flex-none tj:items-center tj:gap-3">
				<?php // Previous / next within the list the dialog was opened from (initialiseDialogSequences; also ← → and a swipe); hidden elsewhere. ?>
				<div data-dialog-pager hidden>
					<div class="tj:flex tj:items-center tj:gap-1.5">
						<button class="tj-arrow" data-dir="prev" type="button" data-dialog-step="-1" aria-label="Předchozí"></button>
						<span class="tj:min-w-[4.5ch] tj:text-center tj:text-[.85rem] tj:font-semibold tj:text-muted tj:tabular-nums tj:max-sm:hidden" data-dialog-position></span>
						<button class="tj-arrow" data-dir="next" type="button" data-dialog-step="1" aria-label="Další"></button>
					</div>
				</div>
				<?php // A cross in the same quiet circle as the arrows; the name comes from aria-label. ?>
				<button class="tj-arrow" data-dir="close" type="button" data-dialog-close aria-label="<?php echo esc_attr($args['close'] ?? 'Zavřít'); ?>" autofocus></button>
			</div>
		</div>
		<?php // Title and content slide out and in when stepping; the header with the arrows stays put. ?>
		<div data-dialog-slide>
			<h2 class="tj-h2 tj:mt-3 tj:mb-6 tj:text-[clamp(1.6rem,3.4vw,2.4rem)]" id="<?php echo esc_attr($args['id'] . '-title'); ?>"><?php echo esc_html($args['title']); ?></h2>
			<?php ($args['content'])(); ?>
			<?php
			// After the content, where the header arrows are out of sight: previous left and next right like the arrows; wider screens add the neighbour's name and an arrow, at the outer edges (initialiseDialogSequences fills them; hidden outside a list).
			// On phones the name would only wrap into a stub: two equal halves with the word centred; screen readers hear the name from aria-label.
			$step = 'tj:flex tj:min-h-12 tj:w-full tj:min-w-0 tj:cursor-pointer tj:flex-col tj:justify-center tj:gap-1 tj:rounded-xl tj:border tj:border-solid tj:border-ink/14 tj:bg-transparent tj:px-4 tj:py-2 tj:sm:min-h-[4.5rem] tj:sm:px-4 tj:sm:py-3 tj:font-[inherit] tj:text-inherit tj:transition-[border-color,background-color,scale] tj:duration-150 tj:hover:border-ink/35 tj:hover:bg-ink/4 tj:active:scale-[.98] tj:[&[hidden]]:hidden';
			$stepLabel = 'tj:font-bold tj:sm:text-[.85rem] tj:sm:font-semibold tj:sm:text-muted';
			$stepTitle = 'tj:line-clamp-2 tj:text-[1.05rem] tj:leading-snug tj:font-bold tj:max-sm:hidden';
			?>
			<div class="tj:mt-10 tj:border-t tj:border-ink/12 tj:pt-6 tj:[&[hidden]]:hidden" data-dialog-pager-bottom hidden>
				<div class="tj:grid tj:grid-cols-2 tj:gap-2 tj:sm:gap-3">
					<button class="<?php echo $step; ?> tj:text-center tj:sm:text-left" type="button" data-dialog-step="-1" hidden><span class="<?php echo $stepLabel; ?>"><span class="tj:max-sm:hidden" aria-hidden="true">←&nbsp;</span>Předchozí</span><span class="<?php echo $stepTitle; ?>" data-step-title></span></button>
					<button class="<?php echo $step; ?> tj:col-start-2 tj:text-center tj:sm:text-right" type="button" data-dialog-step="1" hidden><span class="<?php echo $stepLabel; ?>">Další<span class="tj:max-sm:hidden" aria-hidden="true">&nbsp;→</span></span><span class="<?php echo $stepTitle; ?>" data-step-title></span></button>
				</div>
			</div>
		</div>
	</div>
</dialog>
