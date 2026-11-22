<?php
/**
 * Key facts about the camp (hero, registration page). Colours follow the
 * surrounding text, so the list works on paper and on the night hero.
 *
 * @var array{facts: array<string, string>, map?: ?string, class?: string} $args
 */
use TaborovaJizda\Theme\Home;
?>
<dl class="tj:m-0 tj:grid tj:grid-cols-2 tj:gap-x-8 tj:gap-y-4 <?php echo esc_attr($args['class'] ?? ''); ?>" aria-label="Základní informace o táboře">
	<?php foreach ($args['facts'] as $label => $value) : ?>
		<div class="tj:min-w-0 <?php echo $label === 'Přihlášky' ? 'tj:col-span-full' : ''; ?>">
			<dt class="tj:text-[.85rem] tj:font-semibold tj:opacity-70"><?php echo esc_html($label); ?></dt>
			<dd class="tj:m-0 tj:text-[1.1rem] tj:leading-snug tj:font-bold">
				<?php if ($label === 'Místo' && ! empty($args['map'])) : ?>
					<a class="tj:text-inherit tj:underline tj:decoration-current/40 tj:decoration-[1.5px] tj:underline-offset-[.22em] tj:transition-colors tj:hover:decoration-current" href="<?php echo esc_url($args['map']); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($value); ?><span class="tj:sr-only"> (otevře Google Mapy v novém okně)</span></a>
				<?php else : ?>
					<?php echo esc_html(Home::dashes($value)); ?>
				<?php endif; ?>
			</dd>
		</div>
	<?php endforeach; ?>
</dl>
