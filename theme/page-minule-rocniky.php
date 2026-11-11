<?php
/**
 * Historical camp seasons archive.
 *
 * @package TaborovaJizda
 */

$requestedYear = isset($_GET['rok']) ? absint(wp_unslash((string) $_GET['rok'])) : null;
$availableYears = TaborovaJizda\Theme\Season\SeasonArchive::years();
$selectedYear = $requestedYear && in_array($requestedYear, $availableYears, true) ? $requestedYear : null;
$seasons = taborovajizda_season_archive($selectedYear);

get_header();
?>
<section class="tj:mx-auto tj:w-full tj:max-w-[72rem] tj:pb-12" aria-labelledby="archive-title">
	<div class="tj:mb-8 tj:flex tj:flex-col tj:gap-5 tj:sm:flex-row tj:sm:items-end tj:sm:justify-between">
		<div>
			<h1 id="archive-title" class="tj:mb-2 tj:text-4xl">Minulé ročníky</h1>
			<p class="tj:m-0 tj:max-w-2xl">Přehled táborových příběhů, trailerů, článků a fotografií od roku 2014.</p>
		</div>
		<form method="get" action="<?php echo esc_url(get_permalink()); ?>" class="tj:flex tj:items-end tj:gap-3">
			<label for="archive-year" class="tj:flex tj:flex-col tj:gap-1 tj:font-bold">
				Rok
				<select id="archive-year" name="rok">
					<option value="">Všechny ročníky</option>
					<?php foreach ($availableYears as $year) : ?>
						<option value="<?php echo esc_attr((string) $year); ?>" <?php selected($selectedYear, $year); ?>><?php echo esc_html((string) $year); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<button type="submit">Zobrazit</button>
		</form>
	</div>

	<div class="tj:grid tj:gap-10">
		<?php foreach ($seasons as $archiveSeason) : ?>
			<?php
			$categorySlug = $archiveSeason['categorySlug'];
			$category = $categorySlug ? get_category_by_slug($categorySlug) : null;
			$posts = $category instanceof WP_Term ? get_posts([
				'cat' => $category->term_id,
				'numberposts' => $selectedYear ? -1 : 4,
				'post_status' => 'publish',
			]) : [];
			$remainingPosts = ! $selectedYear && $category instanceof WP_Term ? max(0, $category->count - count($posts)) : 0;
			$thumbnailUrl = sprintf(
				'https://i.ytimg.com/vi/%s/%s.jpg',
				rawurlencode($archiveSeason['youtubeId']),
				rawurlencode($archiveSeason['thumbnail'])
			);
			?>
			<article id="rocnik-<?php echo esc_attr((string) $archiveSeason['year']); ?>" class="tj:rounded-xl tj:bg-season-background tj:p-4 tj:shadow-[var(--wp--preset--shadow--soft)] tj:sm:p-6">
				<header class="tj:mb-4 tj:flex tj:items-baseline tj:gap-4">
					<p class="tj:m-0 tj:text-2xl tj:font-bold tj:text-season-primary"><?php echo esc_html((string) $archiveSeason['year']); ?></p>
					<h2 class="tj:m-0 tj:text-3xl"><?php echo esc_html($archiveSeason['title']); ?></h2>
				</header>
				<div
					class="youtube-player tj:aspect-video tj:overflow-hidden tj:rounded-xl tj:bg-black"
					data-id="<?php echo esc_attr($archiveSeason['youtubeId']); ?>"
					data-title="<?php echo esc_attr('Trailer ' . $archiveSeason['year'] . ': ' . $archiveSeason['title']); ?>"
					data-thumbnail="<?php echo esc_url($thumbnailUrl); ?>"
				></div>

				<?php if ($posts) : ?>
					<section class="tj:mt-6" aria-labelledby="articles-<?php echo esc_attr((string) $archiveSeason['year']); ?>">
						<h3 id="articles-<?php echo esc_attr((string) $archiveSeason['year']); ?>" class="tj:mb-4 tj:text-2xl">Články a fotografie</h3>
						<div class="tj:grid tj:gap-4 tj:sm:grid-cols-2">
							<?php foreach ($posts as $archivePost) : ?>
								<?php get_template_part('template-parts/archive-post-card', null, ['post' => $archivePost]); ?>
							<?php endforeach; ?>
						</div>
						<?php if ($remainingPosts > 0) : ?>
							<p class="tj:mb-0 tj:mt-5">
								<a class="tj:font-bold" href="<?php echo esc_url(get_category_link($category)); ?>">
									<?php echo esc_html(sprintf('Zobrazit dalších %d %s', $remainingPosts, _n('článek', 'článků', $remainingPosts, 'taborovajizda'))); ?>
								</a>
							</p>
						<?php endif; ?>
					</section>
				<?php endif; ?>
			</article>
		<?php endforeach; ?>
	</div>
</section>
</main>
<?php get_sidebar(); ?>
<?php get_footer(); ?>
