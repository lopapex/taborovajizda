<?php
declare(strict_types=1);

$postId = (int) ($args['post_id'] ?? get_the_ID());
?>
<article class="tj-home-post-card">
	<a class="tj-home-post-card__image" href="<?php echo esc_url(get_permalink($postId)); ?>" tabindex="-1" aria-hidden="true">
		<?php if (has_post_thumbnail($postId)) : ?>
			<?php echo get_the_post_thumbnail($postId, 'medium_large', ['loading' => 'lazy']); ?>
		<?php else : ?>
			<span class="tj-home-post-card__placeholder">
				<img src="<?php echo esc_url(get_theme_file_uri('/web_images/logo_color.png')); ?>" alt="" loading="lazy">
			</span>
		<?php endif; ?>
	</a>
	<div class="tj-home-post-card__body">
		<p class="tj-home-eyebrow"><?php echo esc_html(get_the_date('j. n. Y', $postId)); ?></p>
		<h3><a href="<?php echo esc_url(get_permalink($postId)); ?>"><?php echo esc_html(get_the_title($postId)); ?></a></h3>
		<p><?php echo esc_html(wp_trim_words(get_the_excerpt($postId), 22)); ?></p>
		<a class="tj-home-text-link" href="<?php echo esc_url(get_permalink($postId)); ?>">Číst aktualitu</a>
	</div>
</article>
