<?php
/**
 * Every page outside the homepage and the registration: a post or page, a
 * list (category, archive) or "not found". A single post is the same article
 * the homepage shows in its dialog (Home::article).
 */
declare(strict_types=1);

use TaborovaJizda\Theme\Home;

get_header();

$title = match (true) {
	is_404() => 'Tahle stránka tu není',
	is_category() || is_tag() || is_tax() => single_term_title('', false),
	is_singular() => '',
	default => wp_strip_all_tags(get_the_archive_title()),
};
/** Where "back" leads: the homepage section the post belongs to. */
$back = match (true) {
	is_singular('post') && has_category('lide') => ['lide', 'Všichni lidé z tábora'],
	is_singular('post') && has_category('taborove_aktivity') => ['program', 'Celý program tábora'],
	is_singular('post') || is_category('aktuality') => ['aktuality', 'Aktuality na hlavní stránce'],
	is_page(['informace-pro-rodice', 'dokumenty']) => ['pro-rodice', 'Vše pro rodiče'],
	default => null,
};
?>
<main id="main-content" class="tj-section tj:min-h-[60vh]">
	<?php if (is_singular() && have_posts()) : the_post(); $article = Home::article(get_post()); ?>
		<article class="tj:mx-auto tj:max-w-3xl">
			<?php if ($article['meta'] !== '') : ?><p class="tj-meta"><?php echo esc_html($article['meta']); ?></p><?php endif; ?>
			<h1 class="tj-h1 tj:text-[clamp(2rem,4.4vw,3.25rem)]"><?php echo esc_html($article['title']); ?></h1>
			<div class="tj:mt-8"><?php echo $article['html']; // phpcs:ignore WordPress.Security.EscapeOutput -- the_content output ?></div>
			<?php if ($back) : ?>
				<p class="tj:mt-12 tj:mb-0 tj:border-t tj:border-ink/14 tj:pt-6"><a class="tj-link" href="<?php echo esc_url(home_url('/#' . $back[0])); ?>"><?php echo esc_html($back[1]); ?></a></p>
			<?php endif; ?>
		</article>
	<?php elseif (have_posts()) : ?>
		<h1 class="tj-h1 tj:text-[clamp(2rem,4.4vw,3.25rem)]"><?php echo esc_html($title); ?></h1>
		<ul class="tj:m-0 tj:mt-10 tj:grid tj:list-none tj:gap-6 tj:p-0 tj:md:grid-cols-2 tj:lg:grid-cols-3">
			<?php while (have_posts()) : the_post(); ?>
				<li class="tj-card tj:flex tj:min-w-0">
					<a class="tj:flex tj:flex-1 tj:flex-col tj:text-inherit tj:no-underline" href="<?php the_permalink(); ?>">
						<?php if (has_post_thumbnail()) : ?><span class="tj-frame tj:aspect-16/10 tj:rounded-none"><?php the_post_thumbnail('medium_large', ['loading' => 'lazy', 'alt' => '']); ?></span><?php endif; ?>
						<span class="tj:flex tj:flex-1 tj:flex-col tj:p-5">
							<span class="tj-meta"><?php echo esc_html(get_the_date()); ?></span>
							<span class="tj-h3"><?php the_title(); ?></span>
							<span class="tj:mt-2 tj:leading-normal tj:text-muted"><?php echo esc_html(wp_trim_words(get_the_excerpt(), 22)); ?></span>
						</span>
					</a>
				</li>
			<?php endwhile; ?>
		</ul>
		<div class="tj:mt-10 tj:[&_.nav-links]:flex tj:[&_.nav-links]:flex-wrap tj:[&_.nav-links]:gap-4 tj:[&_.page-numbers]:inline-flex tj:[&_.page-numbers]:min-h-[44px] tj:[&_.page-numbers]:items-center tj:[&_.page-numbers]:font-bold tj:[&_.page-numbers]:text-link tj:[&_.screen-reader-text]:sr-only">
			<?php the_posts_pagination(['prev_text' => 'Novější', 'next_text' => 'Starší']); ?>
		</div>
		<?php if ($back) : ?><p class="tj:mt-8 tj:mb-0"><a class="tj-link" href="<?php echo esc_url(home_url('/#' . $back[0])); ?>"><?php echo esc_html($back[1]); ?></a></p><?php endif; ?>
	<?php else : ?>
		<div class="tj:max-w-2xl">
			<h1 class="tj-h1 tj:text-[clamp(2rem,4.4vw,3.25rem)]"><?php echo esc_html($title); ?></h1>
			<p class="tj-lead">Odkaz je nejspíš starý, nebo se do adresy vloudil překlep. Tohle lidé hledají nejčastěji:</p>
			<ul class="tj:m-0 tj:mt-6 tj:grid tj:list-none tj:gap-1 tj:p-0">
				<?php foreach (['' => 'Hlavní stránka', '#pro-rodice' => 'Informace pro rodiče a dokumenty', '#aktuality' => 'Aktuality', '#kontakt' => 'Kontakt'] as $hash => $label) : ?>
					<li><a class="tj-link tj:inline-flex tj:min-h-[44px] tj:items-center" href="<?php echo esc_url(home_url('/' . $hash)); ?>"><?php echo esc_html($label); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>
</main>
<?php get_footer(); ?>
