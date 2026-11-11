<?php
/** @var WP_Post|null $post */
$post = $args['post'] ?? null;
if (! $post instanceof WP_Post) {
    return;
}

$permalink = get_permalink($post);
?>
<article class="tj:flex tj:h-full tj:flex-col tj:overflow-hidden tj:rounded-xl tj:bg-season-surface">
	<?php if (has_post_thumbnail($post)) : ?>
		<a href="<?php echo esc_url($permalink); ?>" class="tj:block tj:aspect-[4/3] tj:overflow-hidden">
			<?php echo get_the_post_thumbnail($post, 'medium', ['class' => 'tj:h-full tj:w-full tj:object-cover', 'loading' => 'lazy']); ?>
		</a>
	<?php endif; ?>
	<div class="tj:flex tj:flex-1 tj:flex-col tj:gap-2 tj:p-4">
		<h3 class="tj:m-0 tj:text-xl"><a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html(get_the_title($post)); ?></a></h3>
		<p class="tj:m-0 tj:text-sm tj:text-season-text"><?php echo esc_html(get_the_date('j. n. Y', $post)); ?></p>
		<a class="tj:mt-auto tj:font-bold" href="<?php echo esc_url($permalink); ?>">Přečíst článek</a>
	</div>
</article>
