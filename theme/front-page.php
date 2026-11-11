<?php
declare(strict_types=1);

get_header();

$season = taborovajizda_season();
$registrationStatus = $season->registrationStatus();
$remainingPlaces = $season->remainingPlaces();
$seasonEnded = $season->hasEnded();
$acceptsRegistrations = $season->acceptsRegistrations();
$registrationLabel = $seasonEnded
    ? sprintf('Ročník %d proběhl', $season->year())
    : match (true) {
        $season->isFull() && $registrationStatus !== 'closed' => 'Obsazeno, přijímáme náhradníky',
        $registrationStatus === 'open' && $remainingPlaces === 1 => 'Poslední volné místo',
        $registrationStatus === 'open' => 'Přihlášky jsou otevřené',
        $registrationStatus === 'coming-soon' => 'Přihlášky brzy otevřeme',
        $registrationStatus === 'closed' => 'Přihlášky jsou uzavřené',
        default => null,
    };
$registrationCta = $season->isFull() ? 'Přihlásit náhradníka' : 'Přihlásit dítě';
$heroFacts = array_filter([
    'Termín' => $season->formattedDateRange(),
    'Místo' => $season->location(),
    'Věk' => $season->formattedAgeRange(),
    'Cena' => $seasonEnded ? null : ($season->formattedPrice() ?? 'Upřesníme'),
    'Přihlášky' => $registrationLabel,
], static fn (?string $value): bool => $value !== null);
$heroTrailer = taborovajizda_season_archive($season->year())[0] ?? null;
$news = new WP_Query([
    'posts_per_page' => -1,
    'category_name' => 'aktuality',
    'ignore_sticky_posts' => true,
    'date_query' => [[
        'after' => ['year' => $season->year(), 'month' => 1, 'day' => 1],
        'before' => ['year' => $season->year(), 'month' => 12, 'day' => 31],
        'inclusive' => true,
    ]],
]);
$activities = new WP_Query(['posts_per_page' => -1, 'category_name' => 'taborove_aktivity', 'ignore_sticky_posts' => true]);
$people = new WP_Query(['posts_per_page' => -1, 'category_name' => 'lide', 'ignore_sticky_posts' => true]);
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
	<section class="tj-home-hero<?php echo $heroTrailer ? ' tj-home-hero--with-media' : ''; ?>" aria-labelledby="season-title">
		<div class="tj-home-hero__image" aria-hidden="true"></div>
		<div class="tj-home-hero__inner">
			<div class="tj-home-hero__content">
				<p class="tj-home-eyebrow">Táborová jízda <?php echo esc_html((string) $season->year()); ?></p>
				<h1 id="season-title"><?php echo esc_html($season->title()); ?></h1>
				<p class="tj-home-hero__lead">Dva týdny her, výprav a přátelství v přírodě pro děti, které chtějí zažít léto naplno.</p>
				<dl class="tj-home-facts" aria-label="Základní informace o táboře">
					<?php foreach ($heroFacts as $label => $value) : ?>
						<div><dt><?php echo esc_html($label); ?></dt><dd><?php echo esc_html($value); ?></dd></div>
					<?php endforeach; ?>
				</dl>
				<div class="tj-home-hero__actions">
					<?php if ($acceptsRegistrations) : ?>
						<a class="tj-home-button" href="<?php echo esc_url(home_url('/e-prihlaska/#menu')); ?>"><?php echo esc_html($registrationCta); ?></a>
						<a class="tj-home-button tj-home-button--quiet" href="#o-tabore">Poznat tábor</a>
					<?php else : ?>
						<a class="tj-home-button" href="#o-tabore">Poznat tábor</a>
						<a class="tj-home-button tj-home-button--quiet" href="<?php echo esc_url(home_url('/minule-rocniky/')); ?>">Minulé ročníky</a>
					<?php endif; ?>
				</div>
			</div>
			<?php if ($heroTrailer) : ?>
				<?php
				$heroTrailerTitle = sprintf('Trailer %d: %s', $heroTrailer['year'], $heroTrailer['title']);
				$heroTrailerUrl = 'https://www.youtube.com/watch?v=' . rawurlencode($heroTrailer['youtubeId']);
				?>
				<figure class="tj-home-hero__media">
					<div class="youtube-player tj-home-hero__trailer" data-id="<?php echo esc_attr($heroTrailer['youtubeId']); ?>" data-title="<?php echo esc_attr($heroTrailerTitle); ?>" data-thumbnail="<?php echo esc_url(sprintf('https://i.ytimg.com/vi/%s/%s.jpg', rawurlencode($heroTrailer['youtubeId']), rawurlencode($heroTrailer['thumbnail']))); ?>">
						<a class="tj-home-text-link" href="<?php echo esc_url($heroTrailerUrl); ?>" rel="noopener noreferrer">Přehrát trailer na YouTube</a>
					</div>
					<figcaption><?php echo esc_html($heroTrailerTitle); ?></figcaption>
				</figure>
			<?php endif; ?>
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
		<div class="tj-home-section__heading" id="aktuality"><p class="tj-home-eyebrow">Z tábora</p><h2 id="news-heading">Aktuality <?php echo esc_html((string) $season->year()); ?></h2></div>
		<?php if ($news->have_posts()) : ?>
			<div class="tj-home-carousel">
				<button class="tj-home-carousel__button" type="button" data-carousel-prev aria-controls="news-carousel" aria-label="Předchozí aktualita" disabled>←</button>
				<div class="tj-home-people-carousel tj-home-news-carousel" id="news-carousel" data-horizontal-carousel tabindex="0" aria-label="Aktuality Táborové jízdy pro rok <?php echo esc_attr((string) $season->year()); ?>">
				<?php while ($news->have_posts()) : $news->the_post(); ?>
					<?php $dialogId = 'news-dialog-' . get_the_ID(); ?>
					<article class="tj-home-post-card">
						<button class="tj-home-post-card__image tj-home-post-card__open" type="button" data-home-dialog-open="<?php echo esc_attr($dialogId); ?>" aria-haspopup="dialog" aria-label="Otevřít aktualitu: <?php echo esc_attr(get_the_title()); ?>">
							<?php if (has_post_thumbnail()) : ?>
								<?php the_post_thumbnail('medium_large', ['loading' => 'lazy']); ?>
							<?php else : ?>
								<span class="tj-home-post-card__placeholder"><img src="<?php echo esc_url(get_theme_file_uri('/web_images/logo_color.png')); ?>" alt="" loading="lazy"></span>
							<?php endif; ?>
						</button>
						<div class="tj-home-post-card__body">
							<p class="tj-home-eyebrow"><?php echo esc_html(get_the_date()); ?></p>
							<h3><button class="tj-home-post-card__title" type="button" data-home-dialog-open="<?php echo esc_attr($dialogId); ?>" aria-haspopup="dialog"><?php the_title(); ?></button></h3>
							<p><?php echo esc_html(wp_trim_words(get_the_excerpt(), 22)); ?></p>
							<button class="tj-home-text-link tj-home-post-card__read" type="button" data-home-dialog-open="<?php echo esc_attr($dialogId); ?>" aria-haspopup="dialog">Číst celou aktualitu</button>
						</div>
						<dialog class="tj-home-content-dialog" id="<?php echo esc_attr($dialogId); ?>" aria-labelledby="<?php echo esc_attr($dialogId . '-title'); ?>">
							<div class="tj-home-news-dialog__content">
								<div class="tj-home-news-dialog__top"><p class="tj-home-eyebrow"><?php echo esc_html(get_the_date()); ?></p><button class="tj-home-news-dialog__close" type="button" data-home-dialog-close aria-label="Zavřít aktualitu">Zavřít</button></div>
								<h2 id="<?php echo esc_attr($dialogId . '-title'); ?>"><?php the_title(); ?></h2>
								<?php if (has_post_thumbnail()) : ?><div class="tj-home-news-dialog__image"><?php the_post_thumbnail('large'); ?></div><?php endif; ?>
								<div class="tj-home-prose tj-home-news-dialog__body"><?php the_content(); ?></div>
							</div>
						</dialog>
					</article>
				<?php endwhile; ?>
				</div>
				<button class="tj-home-carousel__button" type="button" data-carousel-next aria-controls="news-carousel" aria-label="Další aktualita">→</button>
			</div>
		<?php else : ?>
			<p class="tj-home-empty">Nové zprávy z tábora brzy přidáme.</p>
		<?php endif; wp_reset_postdata(); ?>
	</section>

	<section class="tj-home-section tj-home-section--program" aria-labelledby="program-heading">
		<div class="tj-home-section__heading" id="program"><p class="tj-home-eyebrow">Co zažijeme</p><h2 id="program-heading">Program tvoří lidé, hry a příroda</h2></div>
		<?php if ($activities->have_posts()) : ?>
			<div class="tj-home-activity-tabs" data-activity-tabs>
				<div class="tj-home-tab-list" role="tablist" aria-label="Táborové aktivity">
					<?php $activityIndex = 0; while ($activities->have_posts()) : $activities->the_post(); ?>
						<?php $activityId = 'activity-' . get_the_ID(); ?>
						<button class="tj-home-tab<?php echo $activityIndex === 0 ? ' is-active' : ''; ?>" type="button" role="tab" id="tab-<?php echo esc_attr($activityId); ?>" aria-selected="<?php echo $activityIndex === 0 ? 'true' : 'false'; ?>" aria-controls="panel-<?php echo esc_attr($activityId); ?>" data-activity-tab="panel-<?php echo esc_attr($activityId); ?>"<?php echo $activityIndex === 0 ? '' : ' tabindex="-1"'; ?>><?php the_title(); ?></button>
					<?php $activityIndex++; endwhile; $activities->rewind_posts(); ?>
				</div>
				<?php $activityIndex = 0; while ($activities->have_posts()) : $activities->the_post(); ?>
					<?php $activityId = 'activity-' . get_the_ID(); ?>
					<div class="tj-home-activity-panel<?php echo $activityIndex === 0 ? ' is-active' : ''; ?>" id="panel-<?php echo esc_attr($activityId); ?>" role="tabpanel" aria-labelledby="tab-<?php echo esc_attr($activityId); ?>">
						<div class="tj-home-prose tj-home-activity-content"><?php the_content(); ?></div>
					</div>
				<?php $activityIndex++; endwhile; wp_reset_postdata(); ?>
			</div>
		<?php else : ?>
			<p class="tj-home-empty">Program tábora brzy doplníme.</p>
		<?php endif; ?>
		<div class="tj-home-people-heading"><p class="tj-home-eyebrow">Náš tým</p><h3>Lidé, kteří tábor tvoří</h3></div>
		<?php if ($people->have_posts()) : ?>
			<div class="tj-home-carousel">
				<button class="tj-home-carousel__button" type="button" data-carousel-prev aria-controls="people-carousel" aria-label="Předchozí člověk" disabled>←</button>
				<div class="tj-home-people-carousel" id="people-carousel" data-horizontal-carousel tabindex="0" aria-label="Články o lidech z Táborové jízdy">
			<?php while ($people->have_posts()) : $people->the_post(); ?>
				<?php $dialogId = 'person-dialog-' . get_the_ID(); ?>
				<article class="tj-home-person-card">
					<?php if (has_post_thumbnail()) : ?><button class="tj-home-program-card__image tj-home-person-card__open" type="button" data-home-dialog-open="<?php echo esc_attr($dialogId); ?>" aria-haspopup="dialog" aria-label="Otevřít profil: <?php echo esc_attr(get_the_title()); ?>"><?php the_post_thumbnail('medium_large', ['loading' => 'lazy']); ?></button><?php endif; ?>
					<div><p class="tj-home-eyebrow">Lidé</p><h3><button class="tj-home-person-card__title" type="button" data-home-dialog-open="<?php echo esc_attr($dialogId); ?>" aria-haspopup="dialog"><?php the_title(); ?></button></h3><p><?php echo esc_html(wp_trim_words(get_the_excerpt(), 18)); ?></p></div>
					<dialog class="tj-home-content-dialog" id="<?php echo esc_attr($dialogId); ?>" aria-labelledby="<?php echo esc_attr($dialogId . '-title'); ?>">
						<div class="tj-home-news-dialog__content">
							<div class="tj-home-news-dialog__top"><p class="tj-home-eyebrow">Náš tým</p><button class="tj-home-news-dialog__close" type="button" data-home-dialog-close aria-label="Zavřít profil">Zavřít</button></div>
							<h2 id="<?php echo esc_attr($dialogId . '-title'); ?>"><?php the_title(); ?></h2>
							<?php if (has_post_thumbnail()) : ?><div class="tj-home-news-dialog__image"><?php the_post_thumbnail('large'); ?></div><?php endif; ?>
							<div class="tj-home-prose tj-home-news-dialog__body"><?php the_content(); ?></div>
						</div>
					</dialog>
				</article>
			<?php endwhile; wp_reset_postdata(); ?>
				</div>
				<button class="tj-home-carousel__button" type="button" data-carousel-next aria-controls="people-carousel" aria-label="Další člověk">→</button>
			</div>
		<?php else : ?>
			<p class="tj-home-empty">Představení týmu brzy doplníme. Zatím se podívejte do <a class="tj-home-text-link" href="<?php echo esc_url(home_url('/category/lide/')); ?>">archivu lidí</a>.</p>
		<?php endif; ?>
	</section>

	<section class="tj-home-section tj-home-section--tabs" aria-labelledby="parents-heading" data-home-tabs>
		<div class="tj-home-section__heading" id="pro-rodice"><p class="tj-home-eyebrow">Prakticky</p><h2 id="parents-heading">Vše důležité na jednom místě</h2></div>
		<div class="tj-home-tab-list" role="tablist" aria-label="Praktické informace">
			<button class="tj-home-tab is-active" type="button" role="tab" id="tab-parents" aria-selected="true" aria-controls="panel-parents" data-home-tab="panel-parents">Pro rodiče</button>
			<button class="tj-home-tab" type="button" role="tab" id="tab-documents" aria-selected="false" aria-controls="panel-documents" data-home-tab="panel-documents" tabindex="-1">Dokumenty</button>
			<button class="tj-home-tab" type="button" role="tab" id="tab-archive" aria-selected="false" aria-controls="panel-archive" data-home-tab="panel-archive" tabindex="-1">Minulé ročníky</button>
		</div>

		<div class="tj-home-tab-panel is-active" id="panel-parents" role="tabpanel" aria-labelledby="tab-parents">
			<div class="tj-home-tab-panel__intro"><p>Praktické informace před táborem i během něj.</p></div>
			<div class="tj-home-prose tj-home-tab-content"><?php $renderPageContent($parentPage); ?></div>
		</div>
		<div class="tj-home-tab-panel" id="panel-documents" role="tabpanel" aria-labelledby="tab-documents">
			<div class="tj-home-tab-panel__intro"><p>Formuláře a dokumenty pohromadě.</p></div>
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
						<?php if ($index < 2) : ?>
							<div class="youtube-player tj-home-archive-card__trailer" data-id="<?php echo esc_attr($archiveSeason['youtubeId']); ?>" data-title="<?php echo esc_attr('Trailer ' . $archiveSeason['year'] . ': ' . $archiveSeason['title']); ?>" data-thumbnail="<?php echo esc_url($thumbnailUrl); ?>"></div>
						<?php else : ?>
							<a href="<?php echo esc_url($archiveUrl); ?>"><img src="<?php echo esc_url($thumbnailUrl); ?>" alt="<?php echo esc_attr('Tábor ' . $archiveSeason['year'] . ': ' . $archiveSeason['title']); ?>" loading="lazy"></a>
						<?php endif; ?>
						<div><p class="tj-home-eyebrow"><?php echo esc_html((string) $archiveSeason['year']); ?></p><h3><a href="<?php echo esc_url($archiveUrl); ?>"><?php echo esc_html($archiveSeason['title']); ?></a></h3></div>
					</article>
				<?php endforeach; ?>
			</div>
			<?php if (count($archiveSeasons) > 2) : ?>
				<button class="tj-home-show-all" type="button" data-archive-expand aria-expanded="false">Zobrazit všechny ročníky</button>
			<?php endif; ?>
		</div>
	</section>

	<section class="tj-home-section tj-home-section--contact" aria-labelledby="contact-heading">
		<div class="tj-home-section__heading" id="kontakt"><p class="tj-home-eyebrow">Spojení</p><h2 id="contact-heading">Jsme na příjmu</h2></div>
		<div class="tj-home-contact-grid">
			<div class="tj-home-contact-details">
				<p><strong>Martin Vojtěch</strong><br>hlavní vedoucí</p>
				<p><a href="tel:+420605947103">605 947 103</a><br><a href="mailto:<?php echo esc_attr($season->contactEmail()); ?>"><?php echo esc_html($season->contactEmail()); ?></a></p>
				<p>Pionýrů 1332<br>564 01 Žamberk</p>
				<p>Tábor Plátěná osada<br>Splzov, Železný Brod</p>
				<p class="tj-home-contact-social"><a href="https://www.youtube.com/@taborovajizda6412" rel="noopener noreferrer">YouTube</a><a href="https://www.instagram.com/taborova.jizda/?hl=cs" rel="noopener noreferrer">Instagram</a></p>
			</div>
			<div class="tj-home-contact-form">
				<?php get_template_part('template-parts/question-form', null, ['heading' => 'Chcete nám poslat dotaz?', 'form_id' => 'home-question']); ?>
			</div>
		</div>
	</section>
</main>
<?php get_footer(); ?>
