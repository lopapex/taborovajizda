<?php
declare(strict_types=1);

get_header();

$season = taborovajizda_season();
$registrationStatus = $season->registrationStatus();
$remainingPlaces = $season->remainingPlaces();
$registrationLabel = match ($registrationStatus) {
    'open' => $remainingPlaces === 1 ? 'Poslední volné místo' : 'Přihlášky jsou otevřené',
    'full' => 'Tábor je obsazený',
    'coming-soon' => 'Přihlášky brzy otevřeme',
    'closed' => 'Přihlášky jsou uzavřené',
    default => 'Informace o přihlášce',
};
$registrationCta = $registrationStatus === 'open' && ! $season->isFull() ? 'Přihlásit dítě' : 'Zjistit více';
$news = new WP_Query(['posts_per_page' => 3, 'category_name' => 'aktuality', 'ignore_sticky_posts' => true]);
$parentPage = get_page_by_path('informace-pro-rodice');
$documentsPage = get_page_by_path('dokumenty');
$archiveSeasons = array_values(array_filter(
    taborovajizda_season_archive(),
    static fn (array $archiveSeason): bool => $archiveSeason['year'] < $season->year()
));
$renderPageContent = static function (?WP_Post $page): void {
    if (! $page instanceof WP_Post) {
        return;
    }

    echo apply_filters('the_content', $page->post_content);
};
?>
<main id="main-content" class="tj-home-main">
	<section class="tj-home-hero" aria-labelledby="season-title">
		<div class="tj-home-hero__image" aria-hidden="true"></div>
		<div class="tj-home-hero__content">
			<p class="tj-home-eyebrow">Táborová jízda <?php echo esc_html((string) $season->year()); ?></p>
			<h1 id="season-title"><?php echo esc_html($season->title()); ?></h1>
			<p class="tj-home-hero__lead">Dva týdny her, výprav a přátelství v přírodě pro děti, které chtějí zažít léto naplno.</p>
			<div class="tj-home-facts" aria-label="Základní informace o táboře">
				<p><strong>Termín</strong><?php echo esc_html($season->formattedDateRange()); ?></p>
				<p><strong>Místa</strong><?php echo esc_html($registrationLabel); ?></p>
			</div>
			<div class="tj-home-hero__actions">
				<a class="tj-home-button" href="<?php echo esc_url(home_url('/e-prihlaska/#menu')); ?>"><?php echo esc_html($registrationCta); ?></a>
				<a class="tj-home-button tj-home-button--quiet" href="#o-tabore">Poznat tábor</a>
			</div>
		</div>
	</section>

	<section class="tj-home-section tj-home-section--intro" aria-labelledby="about-heading">
		<div class="tj-home-section__heading" id="o-tabore"><p class="tj-home-eyebrow">Od roku 1997</p><h2 id="about-heading">Tábor, který tvoříme společně</h2></div>
		<div class="tj-home-prose">
			<?php while (have_posts()) : the_post(); ?>
				<?php the_content(); ?>
			<?php endwhile; ?>
		</div>
	</section>

	<section class="tj-home-section tj-home-section--news" aria-labelledby="news-heading">
		<div class="tj-home-section__heading tj-home-section__heading--row" id="aktuality"><div><p class="tj-home-eyebrow">Z tábora</p><h2 id="news-heading">Aktuality</h2></div><a class="tj-home-text-link" href="<?php echo esc_url(home_url('/category/aktuality/')); ?>">Všechny aktuality</a></div>
		<?php if ($news->have_posts()) : ?>
			<div class="tj-home-post-grid">
				<?php while ($news->have_posts()) : $news->the_post(); ?>
					<?php get_template_part('template-parts/home/post-card', null, ['post_id' => get_the_ID()]); ?>
				<?php endwhile; ?>
			</div>
		<?php else : ?>
			<p class="tj-home-empty">Nové zprávy z tábora brzy přidáme.</p>
		<?php endif; wp_reset_postdata(); ?>
	</section>

	<section class="tj-home-section tj-home-section--tabs" aria-labelledby="parents-heading" data-home-tabs>
		<div class="tj-home-section__heading" id="pro-rodice"><p class="tj-home-eyebrow">Prakticky</p><h2 id="parents-heading">Vše důležité na jednom místě</h2></div>
		<div class="tj-home-tab-list" role="tablist" aria-label="Praktické informace">
			<button class="tj-home-tab is-active" type="button" role="tab" id="tab-parents" aria-selected="true" aria-controls="panel-parents" data-home-tab="panel-parents">Pro rodiče</button>
			<button class="tj-home-tab" type="button" role="tab" id="tab-documents" aria-selected="false" aria-controls="panel-documents" data-home-tab="panel-documents" tabindex="-1">Dokumenty</button>
			<button class="tj-home-tab" type="button" role="tab" id="tab-archive" aria-selected="false" aria-controls="panel-archive" data-home-tab="panel-archive" tabindex="-1">Minulé ročníky</button>
		</div>

		<div class="tj-home-tab-panel is-active" id="panel-parents" role="tabpanel" aria-labelledby="tab-parents">
			<div class="tj-home-tab-panel__intro"><p>Praktické informace před táborem i během něj.</p><a class="tj-home-text-link" href="<?php echo esc_url(home_url('/informace-pro-rodice/')); ?>">Otevřít samostatnou stránku</a></div>
			<div class="tj-home-prose tj-home-tab-content"><?php $renderPageContent($parentPage); ?></div>
		</div>
		<div class="tj-home-tab-panel" id="panel-documents" role="tabpanel" aria-labelledby="tab-documents">
			<div class="tj-home-tab-panel__intro"><p>Formuláře a dokumenty pohromadě.</p><a class="tj-home-text-link" href="<?php echo esc_url(home_url('/dokumenty/')); ?>">Otevřít samostatnou stránku</a></div>
			<div class="tj-home-prose tj-home-tab-content"><?php $renderPageContent($documentsPage); ?></div>
		</div>
		<div class="tj-home-tab-panel" id="panel-archive" role="tabpanel" aria-labelledby="tab-archive">
			<div class="tj-home-tab-panel__intro"><p>Příběhy a trailery z předchozích let.</p><a class="tj-home-text-link" href="<?php echo esc_url(home_url('/minule-rocniky/')); ?>">Otevřít celý archiv</a></div>
			<div class="tj-home-archive-grid">
				<?php foreach ($archiveSeasons as $index => $archiveSeason) : ?>
					<?php
					$category = $archiveSeason['categorySlug'] ? get_category_by_slug($archiveSeason['categorySlug']) : null;
					$archiveUrl = $category instanceof WP_Term ? get_category_link($category) : home_url('/minule-rocniky/#rocnik-' . $archiveSeason['year']);
					$thumbnailUrl = sprintf('https://i.ytimg.com/vi/%s/%s.jpg', rawurlencode($archiveSeason['youtubeId']), rawurlencode($archiveSeason['thumbnail']));
					?>
					<article class="tj-home-archive-card<?php echo $index > 1 ? ' is-extra' : ''; ?>">
						<a href="<?php echo esc_url($archiveUrl); ?>"><img src="<?php echo esc_url($thumbnailUrl); ?>" alt="<?php echo esc_attr('Tábor ' . $archiveSeason['year'] . ': ' . $archiveSeason['title']); ?>" loading="lazy"></a>
						<div><p class="tj-home-eyebrow"><?php echo esc_html((string) $archiveSeason['year']); ?></p><h3><a href="<?php echo esc_url($archiveUrl); ?>"><?php echo esc_html($archiveSeason['title']); ?></a></h3></div>
					</article>
				<?php endforeach; ?>
			</div>
			<?php if (count($archiveSeasons) > 2) : ?>
				<button class="tj-home-show-all" type="button" data-archive-expand aria-expanded="false">Zobrazit všechny ročníky</button>
			<?php endif; ?>
		</div>
	</section>
</main>
<?php get_footer(); ?>
