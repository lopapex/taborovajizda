<?php
$summary = (bool) ($args['summary'] ?? false);
$isPage = is_page();
$isSingle = is_single();
$isNews = (bool) array_filter(get_the_category(), static fn (WP_Term $category): bool => $category->slug === 'aktuality');
?>
<article <?php post_class('blog_post'); ?>>
	<h2 class="blog_post_title">
		<?php if (! $summary && ! $isPage && ! $isSingle) : ?><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a><?php else : ?><?php the_title(); ?><?php endif; ?>
	</h2>
	<div class="blog_content">
		<?php if (! $summary && $isNews) { the_post_thumbnail('thumbnail'); } ?>
		<?php the_content(); ?>
		<?php if ($summary) : ?><div class="zakonceni tj:clear-both"></div><?php endif; ?>
	</div>
	<?php if (! $summary && ! $isPage) : ?>
		<p class="blog_post_meta tj:clear-both tj:px-2 tj:py-[.3em] tj:text-right tj:italic">Vloženo <?php the_time('j. F Y'); ?> </p>
	<?php endif; ?>
</article>
