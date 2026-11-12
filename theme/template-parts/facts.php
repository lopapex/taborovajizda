<?php
/**
 * Key facts about the camp (hero, registration page).
 *
 * @var array{facts: array<string, string>, map?: ?string, class?: string} $args
 */
?>
<dl class="tj:m-0 tj:flex tj:flex-wrap tj:overflow-hidden tj:rounded-xl tj:border tj:border-ink/11 tj:bg-canvas/72 <?php echo esc_attr($args['class'] ?? 'tj:mt-7'); ?>" aria-label="Základní informace o táboře">
	<?php foreach ($args['facts'] as $label => $value) : ?>
		<div class="tj:m-0 tj:grid tj:flex-auto tj:gap-[.1rem] tj:px-4 tj:py-[.7rem] tj:shadow-divide">
			<dt class="tj:text-[.8rem] tj:font-bold tj:text-muted"><?php echo esc_html($label); ?></dt>
			<dd class="tj:m-0 tj:font-extrabold">
				<?php if ($label === 'Místo' && ! empty($args['map'])) : ?>
					<a class="tj:text-inherit tj:underline tj:decoration-ink/28 tj:underline-offset-[.2em] tj:transition-colors tj:hover:text-link tj:hover:decoration-current tj:focus-visible:text-link" href="<?php echo esc_url($args['map']); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($value); ?><span class="tj:sr-only"> (otevře Google Mapy v novém okně)</span><span aria-hidden="true"> ↗</span></a>
				<?php else : ?>
					<?php echo esc_html($value); ?>
				<?php endif; ?>
			</dd>
		</div>
	<?php endforeach; ?>
</dl>
