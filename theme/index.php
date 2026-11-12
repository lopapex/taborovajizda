<?php
/**
 * Every page outside the homepage and the registration: a post or page, a
 * list (category, archive) or "not found", in the homepage look.
 */
declare(strict_types=1);

get_header();

$title = match (true) {
	is_singular() => get_the_title(),
	is_404() => 'Stránka nenalezena',
	is_category() || is_tag() || is_tax() => single_term_title('', false),
	default => wp_strip_all_tags(get_the_archive_title()),
};
$heading = 'tj:m-0 tj:font-display tj:text-[clamp(2.25rem,5vw,3.75rem)] tj:leading-none tj:font-normal tj:tracking-[.01em] tj:text-ink tj:uppercase';
?>
<main id="main-content" class="tj-section tj:min-h-[60vh] tj:[--tj-band:var(--tj-color-paper)]">
	<?php if (is_singular() && have_posts()) : the_post(); ?>
		<article class="tj:max-w-3xl">
			<?php if (is_single()) : ?><p class="tj-meta"><?php echo esc_html(get_the_date()); ?></p><?php endif; ?>
			<h1 class="<?php echo $heading; ?>"><?php echo esc_html($title); ?></h1>
			<div class="tj-prose tj:mt-8"><?php the_content(); ?></div>
		</article>
	<?php elseif (have_posts()) : ?>
		<h1 class="<?php echo $heading; ?>"><?php echo esc_html($title); ?></h1>
		<div class="tj:mt-10 tj:grid tj:gap-5 tj:md:grid-cols-2 tj:lg:grid-cols-3">
			<?php while (have_posts()) : the_post(); ?>
				<article class="tj-card tj:group/card tj:flex tj:flex-col tj:shadow-card" data-card>
					<?php if (has_post_thumbnail()) : ?>
						<a class="tj:block tj:aspect-16/10 tj:overflow-hidden tj:bg-sun tj:[&>img]:size-full tj:[&>img]:object-cover" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true"><?php the_post_thumbnail('medium_large', ['loading' => 'lazy', 'alt' => '']); ?></a>
					<?php endif; ?>
					<div class="tj:flex tj:flex-1 tj:flex-col tj:p-[1.4rem]">
						<p class="tj-meta"><?php echo esc_html(get_the_date()); ?></p>
						<h2 class="tj:m-0 tj:text-[1.3rem] tj:leading-[1.15]"><a class="tj:text-ink tj:no-underline tj:transition-colors tj:group-hover/card:text-link" href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
						<p class="tj:leading-normal tj:text-muted"><?php echo esc_html(wp_trim_words(get_the_excerpt(), 22)); ?></p>
						<a class="tj-link tj:mt-auto tj:self-start" href="<?php the_permalink(); ?>">Číst dál</a>
					</div>
				</article>
			<?php endwhile; ?>
		</div>
		<div class="tj:mt-10 tj:[&_.nav-links]:flex tj:[&_.nav-links]:flex-wrap tj:[&_.nav-links]:gap-3 tj:[&_.page-numbers]:font-extrabold tj:[&_.page-numbers]:text-link">
			<?php the_posts_pagination(['prev_text' => 'Novější', 'next_text' => 'Starší']); ?>
		</div>
	<?php else : ?>
		<h1 class="<?php echo $heading; ?>"><?php echo esc_html($title); ?></h1>
		<p class="tj:mt-6 tj:text-[1.1rem] tj:text-muted">Tady nic není. Pokračujte na <a class="tj-link" href="<?php echo esc_url(home_url('/')); ?>">hlavní stránku</a>.</p>
	<?php endif; ?>
</main>
<?php get_footer(); ?>
