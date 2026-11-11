<?php
$summary = (bool) ($args['summary'] ?? false);
$isPage = is_page();
$isSingle = is_single();
// Single posts and pages carry the page's h1; entries in a list stay h2 (the archive has its own h1).
$titleTag = ($isPage || $isSingle) ? 'h1' : 'h2';
$isNews = (bool) array_filter(get_the_category(), static fn (WP_Term $category): bool => $category->slug === 'aktuality');
?>
<article <?php post_class('blog_post'); ?>>
	<<?php echo $titleTag; ?> class="blog_post_title">
		<?php if (! $summary && ! $isPage && ! $isSingle) : ?><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a><?php else : ?><?php the_title(); ?><?php endif; ?>
	</<?php echo $titleTag; ?>>
	<div class="blog_content">
		<?php if (! $summary && $isNews) { the_post_thumbnail('thumbnail'); } ?>
		<?php the_content(); ?>
		<?php if ($summary) : ?><div class="zakonceni tj:clear-both"></div><?php endif; ?>
	</div>
	<?php if (! $summary && ! $isPage) : ?>
		<p class="blog_post_meta tj:clear-both tj:px-2 tj:py-[.3em] tj:text-right tj:italic">Vloženo <?php the_time('j. F Y'); ?> </p>
	<?php endif; ?>
</article>
