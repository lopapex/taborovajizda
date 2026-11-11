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
// Activity posts are grouped into a few tabs by slug. Posts not listed here
// fall into "Další aktivity", so newly published activities never disappear.
$activityGroups = [
    'program-a-hra' => ['label' => 'Program a hra', 'slugs' => ['program', 'celotaborova-hra', 'taborova-klasika']],
    'vypravy-a-sport' => ['label' => 'Výpravy a sport', 'slugs' => ['celodenni-vylety', 'akcni-hry', 'horolezectvi', 'lode']],
    'tabornictvi' => ['label' => 'Tábornictví', 'slugs' => ['zalesactvi-a-tabornicke-dovednosti', 'zdravoveda', 'vareni', 'taborak']],
    'vecery-a-tvoreni' => ['label' => 'Večery a tvoření', 'slugs' => ['nocni-hry', 'diskoteka-a-dalsi-vecerni-sarady', 'vytvarka']],
];
$activityPostsByGroup = array_fill_keys(array_keys($activityGroups), []);
foreach ($activities->posts as $activityPost) {
    foreach ($activityGroups as $key => $group) {
        $position = array_search($activityPost->post_name, $group['slugs'], true);
        if ($position !== false) {
            $activityPostsByGroup[$key][$position] = $activityPost;
            continue 2;
        }
    }
    $activityPostsByGroup['dalsi'][] = $activityPost;
}
if (! empty($activityPostsByGroup['dalsi'])) {
    $activityGroups['dalsi'] = ['label' => 'Další aktivity', 'slugs' => []];
}
$activityPostsByGroup = array_filter(array_map(static function (array $posts): array {
    ksort($posts);
    return array_values($posts);
}, $activityPostsByGroup));
$visibleArchiveSeasons = 4;
// Official profiles, verified against the production widgets on 11. 11. 2026.
$socialLinks = [
    'YouTube' => 'https://www.youtube.com/@taborovajizda6412',
    'Instagram' => 'https://www.instagram.com/taborova.jizda/',
    'Facebook' => 'https://www.facebook.com/taborovajizda.cz',
];
$mapCoordinates = $season->mapCoordinates();
$mapQuery = $mapCoordinates ? $mapCoordinates['lat'] . ',' . $mapCoordinates['lon'] : $season->location();
$mapsUrl = $mapQuery ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($mapQuery) : null;
$socialIcon = static fn (string $service): string => function_exists('block_core_social_link_get_icon') ? block_core_social_link_get_icon($service) : '';
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
						<div><dt><?php echo esc_html($label); ?></dt><dd>
							<?php if ($label === 'Místo' && $mapsUrl) : ?>
								<a class="tj-home-facts__map" href="<?php echo esc_url($mapsUrl); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($value); ?><span class="tj-home-visually-hidden"> (otevře Google Mapy v novém okně)</span><span aria-hidden="true"> ↗</span></a>
							<?php else : ?>
								<?php echo esc_html($value); ?>
							<?php endif; ?>
						</dd></div>
					<?php endforeach; ?>
				</dl>
				<div class="tj-home-hero__actions">
					<?php if ($acceptsRegistrations) : ?>
						<a class="tj-home-button" href="<?php echo esc_url(home_url('/e-prihlaska/#menu')); ?>"><?php echo esc_html($registrationCta); ?></a>
						<a class="tj-home-button tj-home-button--quiet" href="#o-tabore">Poznat tábor</a>
					<?php else : ?>
						<?php if (taborovajizda_debug_registration_cta()) : ?>
							<a class="tj-home-button tj-is-debug-cta" href="<?php echo esc_url(home_url('/e-prihlaska/#menu')); ?>" title="Ladicí zobrazení: jen v lokálním prostředí, ročník přihlášky nepřijímá"><?php echo esc_html($registrationCta); ?></a>
						<?php endif; ?>
						<a class="tj-home-button<?php echo taborovajizda_debug_registration_cta() ? ' tj-home-button--quiet' : ''; ?>" href="#o-tabore">Poznat tábor</a>
						<a class="tj-home-button tj-home-button--quiet" href="#minule-rocniky">Minulé ročníky</a>
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
	<section class="tj-home-section tj-home-section--news" aria-labelledby="news-heading">
		<div class="tj-home-section__heading" id="aktuality"><h2 id="news-heading">Aktuality <?php echo esc_html((string) $season->year()); ?></h2></div>
		<?php if ($news->have_posts()) : ?>
			<div class="tj-home-pager tj-home-pager--news" data-pager>
				<button class="tj-home-pager__button tj-home-pager__button--prev" type="button" data-pager-prev aria-controls="news-track" aria-label="Předchozí aktuality" disabled></button>
				<div class="tj-home-pager__viewport">
				<div class="tj-home-pager__track tj-home-news-track" id="news-track" data-pager-track aria-label="Aktuality Táborové jízdy pro rok <?php echo esc_attr((string) $season->year()); ?>">
				<?php while ($news->have_posts()) : $news->the_post(); ?>
					<?php $dialogId = 'news-dialog-' . get_the_ID(); ?>
					<article class="tj-home-post-card<?php echo has_post_thumbnail() ? '' : ' tj-home-post-card--text'; ?>">
						<?php if (has_post_thumbnail()) : ?>
							<a class="tj-home-post-card__image tj-home-post-card__open" href="<?php the_permalink(); ?>" data-home-dialog-open="<?php echo esc_attr($dialogId); ?>" aria-label="Otevřít aktualitu: <?php echo esc_attr(get_the_title()); ?>">
								<?php the_post_thumbnail('medium_large', ['loading' => 'lazy']); ?>
							</a>
						<?php else : ?>
							<a class="tj-home-post-card__image tj-home-post-card__open tj-home-post-card__visual" href="<?php the_permalink(); ?>" data-home-dialog-open="<?php echo esc_attr($dialogId); ?>" aria-label="Otevřít aktualitu: <?php echo esc_attr(get_the_title()); ?>">
								<span class="tj-home-post-card__visual-day"><?php echo esc_html(get_the_date('j.')); ?></span>
								<span class="tj-home-post-card__visual-month"><?php echo esc_html(get_the_date('F Y')); ?></span>
							</a>
						<?php endif; ?>
						<div class="tj-home-post-card__body">
							<p class="tj-home-meta"><?php echo esc_html(get_the_date()); ?></p>
							<h3><a class="tj-home-post-card__title" href="<?php the_permalink(); ?>" data-home-dialog-open="<?php echo esc_attr($dialogId); ?>"><?php the_title(); ?></a></h3>
							<p><?php echo esc_html(wp_trim_words(get_the_excerpt(), 22)); ?></p>
							<a class="tj-home-text-link tj-home-post-card__read" href="<?php the_permalink(); ?>" data-home-dialog-open="<?php echo esc_attr($dialogId); ?>">Číst celou aktualitu</a>
						</div>
						<dialog class="tj-home-content-dialog" id="<?php echo esc_attr($dialogId); ?>" aria-labelledby="<?php echo esc_attr($dialogId . '-title'); ?>">
							<div class="tj-home-news-dialog__content">
								<div class="tj-home-news-dialog__top"><p class="tj-home-meta"><?php echo esc_html(get_the_date()); ?></p><button class="tj-home-news-dialog__close" type="button" data-home-dialog-close aria-label="Zavřít aktualitu">Zavřít</button></div>
								<h2 id="<?php echo esc_attr($dialogId . '-title'); ?>"><?php the_title(); ?></h2>
								<?php if (has_post_thumbnail()) : ?><div class="tj-home-news-dialog__image"><?php the_post_thumbnail('large'); ?></div><?php endif; ?>
								<div class="tj-home-prose tj-home-news-dialog__body"><?php the_content(); ?></div>
							</div>
						</dialog>
					</article>
				<?php endwhile; ?>
				</div>
				</div>
				<button class="tj-home-pager__button tj-home-pager__button--next" type="button" data-pager-next aria-controls="news-track" aria-label="Další aktuality"></button>
				<p class="tj-home-pager__status" data-pager-status aria-live="polite"></p>
			</div>
		<?php else : ?>
			<p class="tj-home-empty">Nové zprávy z tábora brzy přidáme.</p>
		<?php endif; wp_reset_postdata(); ?>
	</section>

	<?php
	$aboutPost = get_post();
	$aboutIntro = '';
	$aboutImage = '';
	$aboutRest = '';
	if ($aboutPost instanceof WP_Post && has_blocks($aboutPost->post_content)) {
	    // Leading paragraphs form the intro, the first image becomes the section
	    // visual and everything else stays available under "Celý příběh tábora".
	    $introBlocks = [];
	    $restBlocks = [];
	    $imageBlock = null;
	    $inIntro = true;
	    foreach (parse_blocks($aboutPost->post_content) as $block) {
	        if ($block['blockName'] === null && trim($block['innerHTML']) === '') {
	            continue;
	        }
	        if ($imageBlock === null && $block['blockName'] === 'core/image') {
	            $imageBlock = $block;
	            $inIntro = false;
	            continue;
	        }
	        if ($inIntro && $block['blockName'] === 'core/paragraph' && count($introBlocks) < 3) {
	            $introBlocks[] = $block;
	            continue;
	        }
	        $inIntro = false;
	        $restBlocks[] = $block;
	    }
	    $aboutIntro = apply_filters('the_content', serialize_blocks($introBlocks));
	    $aboutImage = $imageBlock ? apply_filters('the_content', serialize_blocks([$imageBlock])) : '';
	    $aboutRest = $restBlocks ? apply_filters('the_content', serialize_blocks($restBlocks)) : '';
	} elseif ($aboutPost instanceof WP_Post) {
	    $aboutIntro = apply_filters('the_content', $aboutPost->post_content);
	}
	?>
	<section class="tj-home-section tj-home-section--intro<?php echo $aboutImage ? ' tj-home-section--intro-media' : ''; ?>" aria-labelledby="about-heading">
		<div class="tj-home-intro__text">
			<div class="tj-home-section__heading" id="o-tabore"><h2 id="about-heading">Tábor, který tvoříme společně</h2></div>
			<div class="tj-home-prose tj-home-intro__lead"><?php echo $aboutIntro; ?></div>
			<?php if ($aboutRest) : ?>
				<a class="tj-home-button tj-home-button--quiet tj-home-story-open" href="<?php echo esc_url(get_permalink()); ?>" data-home-dialog-open="story-dialog">Celý příběh tábora</a>
				<dialog class="tj-home-content-dialog" id="story-dialog" aria-labelledby="story-dialog-title">
					<div class="tj-home-news-dialog__content">
						<div class="tj-home-news-dialog__top"><p class="tj-home-meta">O táboře</p><button class="tj-home-news-dialog__close" type="button" data-home-dialog-close aria-label="Zavřít příběh">Zavřít</button></div>
						<h2 id="story-dialog-title">Tábor, který tvoříme společně</h2>
						<div class="tj-home-prose tj-home-story"><?php echo $aboutRest; ?></div>
					</div>
				</dialog>
			<?php endif; ?>
		</div>
		<?php if ($aboutImage) : ?>
			<div class="tj-home-intro__media"><?php echo $aboutImage; ?></div>
		<?php endif; ?>
	</section>

	<section class="tj-home-section tj-home-section--program" aria-labelledby="program-heading">
		<div class="tj-home-section__heading" id="program"><p class="tj-home-eyebrow">Co zažijeme</p><h2 id="program-heading">Program tvoří lidé, hry a příroda</h2></div>
		<?php if ($activityPostsByGroup !== []) : ?>
			<ul class="tj-home-activity-cards">
				<?php foreach ($activityPostsByGroup as $groupKey => $groupPosts) : ?>
					<?php
					$dialogId = 'activities-dialog-' . $groupKey;
					$groupLabel = $activityGroups[$groupKey]['label'];
					$cover = null;
					$coverUrl = null;
					foreach ($groupPosts as $groupPost) {
					    if (has_post_thumbnail($groupPost)) {
					        $cover = $groupPost;
					        break;
					    }
					}
					$renderedActivities = [];
					foreach ($groupPosts as $groupPost) {
					    $renderedActivities[$groupPost->ID] = apply_filters('the_content', $groupPost->post_content);
					}
					$coverHtml = '';
					if (! $cover) {
					    // No featured image: use the first photo from the activities' own (gallery) content.
					    foreach ($renderedActivities as $renderedActivity) {
					        if (preg_match('/<img[^>]+src="([^"]+)"/i', $renderedActivity, $imageMatch)) {
					            $coverUrl = $imageMatch[1];
					            $attachmentId = attachment_url_to_postid(preg_replace('/-\d+x\d+(?=\.[a-z]+$)/i', '', $coverUrl));
					            $coverHtml = $attachmentId ? wp_get_attachment_image($attachmentId, 'medium_large', false, ['loading' => 'lazy', 'alt' => '']) : '';
					            break;
					        }
					    }
					}
					?>
					<li class="tj-home-activity-card">
						<a class="tj-home-activity-card__open" href="<?php echo esc_url(get_permalink($groupPosts[0])); ?>" data-home-dialog-open="<?php echo esc_attr($dialogId); ?>">
							<span class="tj-home-activity-card__media<?php echo $cover || $coverUrl ? '' : ' is-empty'; ?>" aria-hidden="true">
								<?php if ($cover) : ?><?php echo get_the_post_thumbnail($cover, 'medium_large', ['loading' => 'lazy', 'alt' => '']); ?><?php elseif ($coverHtml) : ?><?php echo $coverHtml; ?><?php elseif ($coverUrl) : ?><img src="<?php echo esc_url($coverUrl); ?>" alt="" loading="lazy"><?php endif; ?>
							</span>
							<span class="tj-home-activity-card__body">
								<span class="tj-home-activity-card__title"><?php echo esc_html($groupLabel); ?></span>
								<span class="tj-home-activity-card__items">
									<?php foreach ($groupPosts as $groupPost) : ?><span><?php echo esc_html(html_entity_decode(get_the_title($groupPost), ENT_QUOTES | ENT_HTML5, 'UTF-8')); ?></span><?php endforeach; ?>
								</span>
								<span class="tj-home-activity-card__cta">Přečíst vše</span>
							</span>
						</a>
						<dialog class="tj-home-content-dialog" id="<?php echo esc_attr($dialogId); ?>" aria-labelledby="<?php echo esc_attr($dialogId . '-title'); ?>">
							<div class="tj-home-news-dialog__content">
								<div class="tj-home-news-dialog__top"><p class="tj-home-meta">Co zažijeme</p><button class="tj-home-news-dialog__close" type="button" data-home-dialog-close aria-label="Zavřít">Zavřít</button></div>
								<h2 id="<?php echo esc_attr($dialogId . '-title'); ?>"><?php echo esc_html($groupLabel); ?></h2>
								<?php foreach ($groupPosts as $activityPost) : ?>
									<section class="tj-home-activity" aria-labelledby="activity-<?php echo esc_attr((string) $activityPost->ID); ?>">
										<h3 id="activity-<?php echo esc_attr((string) $activityPost->ID); ?>"><?php echo esc_html(get_the_title($activityPost)); ?></h3>
										<div class="tj-home-prose tj-home-activity-content"><?php echo $renderedActivities[$activityPost->ID]; ?></div>
									</section>
								<?php endforeach; ?>
							</div>
						</dialog>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<p class="tj-home-empty">Program tábora brzy doplníme.</p>
		<?php endif; ?>
		<div class="tj-home-people-heading"><h3>Lidé, kteří tábor tvoří</h3></div>
		<?php if ($people->have_posts()) : ?>
			<div class="tj-home-pager" data-pager>
			<button class="tj-home-pager__button tj-home-pager__button--prev" type="button" data-pager-prev aria-controls="people-track" aria-label="Předchozí lidé" disabled></button>
			<div class="tj-home-pager__viewport">
			<ul class="tj-home-people-grid tj-home-pager__track" id="people-track" data-pager-track>
			<?php while ($people->have_posts()) : $people->the_post(); ?>
				<?php
				$dialogId = 'person-dialog-' . get_the_ID();
				$personTitle = get_the_title();
				// Titles follow "Name (role)"; show the role as a quieter second line.
				$personName = html_entity_decode(wp_strip_all_tags($personTitle), ENT_QUOTES | ENT_HTML5, 'UTF-8');
				$personRole = '';
				if (preg_match('/^(.*?)\s*\(([^)]+)\)\s*$/u', $personName, $personParts)) {
				    $personName = $personParts[1];
				    $personRole = $personParts[2];
				}
				$initials = implode('', array_map(static fn (string $part): string => mb_substr($part, 0, 1), array_slice(preg_split('/\s+/u', trim(preg_replace('/[„“"].*?[„“"]/u', '', $personName))), 0, 2)));
				if (! preg_match('/^\p{L}/u', $personName)) {
				    $initials = '…';
				}
				?>
				<li class="tj-home-person-card">
					<a class="tj-home-person-card__open" href="<?php the_permalink(); ?>" data-home-dialog-open="<?php echo esc_attr($dialogId); ?>">
						<span class="tj-home-person-card__portrait">
							<?php if (has_post_thumbnail()) : ?>
								<?php the_post_thumbnail('medium', ['loading' => 'lazy', 'alt' => '']); ?>
							<?php else : ?>
								<span class="tj-home-person-card__initials" aria-hidden="true"><?php echo esc_html(mb_strtoupper($initials)); ?></span>
							<?php endif; ?>
						</span>
						<span class="tj-home-person-card__name"><?php echo esc_html($personName); ?></span>
						<?php if ($personRole) : ?><span class="tj-home-person-card__role"><?php echo esc_html($personRole); ?></span><?php endif; ?>
					</a>
					<dialog class="tj-home-content-dialog" id="<?php echo esc_attr($dialogId); ?>" aria-labelledby="<?php echo esc_attr($dialogId . '-title'); ?>">
						<div class="tj-home-news-dialog__content">
							<div class="tj-home-news-dialog__top"><p class="tj-home-meta">Náš tým</p><button class="tj-home-news-dialog__close" type="button" data-home-dialog-close aria-label="Zavřít profil">Zavřít</button></div>
							<h2 id="<?php echo esc_attr($dialogId . '-title'); ?>"><?php the_title(); ?></h2>
							<?php if (has_post_thumbnail()) : ?><div class="tj-home-news-dialog__image"><?php the_post_thumbnail('large'); ?></div><?php endif; ?>
							<div class="tj-home-prose tj-home-news-dialog__body"><?php the_content(); ?></div>
						</div>
					</dialog>
				</li>
			<?php endwhile; wp_reset_postdata(); ?>
			</ul>
			</div>
			<button class="tj-home-pager__button tj-home-pager__button--next" type="button" data-pager-next aria-controls="people-track" aria-label="Další lidé"></button>
			<p class="tj-home-pager__status" data-pager-status aria-live="polite"></p>
			</div>
		<?php else : ?>
			<p class="tj-home-empty">Představení týmu brzy doplníme. Zatím se podívejte do <a class="tj-home-text-link" href="<?php echo esc_url(home_url('/category/lide/')); ?>">archivu lidí</a>.</p>
		<?php endif; ?>
	</section>

	<section class="tj-home-section tj-home-section--parents" aria-labelledby="parents-heading">
		<div class="tj-home-section__heading" id="pro-rodice"><h2 id="parents-heading">Pro rodiče</h2></div>
		<?php
		$documentsHtml = '';
		if ($documentsPage instanceof WP_Post) {
		    $documentsHtml = apply_filters('the_content', $documentsPage->post_content);
		    // Mark downloadable documents (PDF by URL or by an "(pdf)" label) for the download style.
		    $documentsHtml = preg_replace_callback('/<a\b([^>]*)>(.*?)<\/a>/is', static function (array $link): string {
		        $isDocument = preg_match('/href="[^"]+\.pdf(?:[?#][^"]*)?"/i', $link[1]) || stripos(wp_strip_all_tags($link[2]), '(pdf)') !== false;
		        if (! $isDocument) {
		            return $link[0];
		        }
		        $attributes = preg_match('/\bclass="/', $link[1]) ? preg_replace('/\bclass="/', 'class="tj-home-document-link ', $link[1], 1) : $link[1] . ' class="tj-home-document-link"';
		        return '<a' . $attributes . '>' . $link[2] . '</a>';
		    }, $documentsHtml) ?? $documentsHtml;
		}
		?>
		<div class="tj-home-parents tj-home-parents--split">
			<div class="tj-home-panel tj-home-parents__faq">
				<h3>Časté otázky</h3>
				<div class="tj-home-prose tj-home-parents__content"><?php $renderPageContent($parentPage); ?></div>
			</div>
			<div class="tj-home-parents__side">
				<?php if ($documentsHtml !== '') : ?>
					<section class="tj-home-panel tj-home-parents__documents" id="dokumenty" aria-labelledby="documents-heading">
						<h3 id="documents-heading">Dokumenty ke stažení</h3>
						<p>Formuláře k odjezdu a seznam věcí do kufru na jednom místě.</p>
						<a class="tj-home-button" href="<?php echo esc_url(get_permalink($documentsPage)); ?>" data-home-dialog-open="documents-dialog">Zobrazit dokumenty</a>
						<dialog class="tj-home-content-dialog" id="documents-dialog" aria-labelledby="documents-dialog-title">
							<div class="tj-home-news-dialog__content">
								<div class="tj-home-news-dialog__top"><p class="tj-home-meta">Pro rodiče</p><button class="tj-home-news-dialog__close" type="button" data-home-dialog-close aria-label="Zavřít dokumenty">Zavřít</button></div>
								<h2 id="documents-dialog-title">Dokumenty ke stažení</h2>
								<div class="tj-home-prose tj-home-documents"><?php echo $documentsHtml; ?></div>
							</div>
						</dialog>
					</section>
				<?php endif; ?>
				<section class="tj-home-panel tj-home-parents__ask" aria-labelledby="ask-heading">
					<h3 id="ask-heading">Nenašli jste odpověď?</h3>
					<p>Zavolejte hlavnímu vedoucímu nebo nám napište, rádi poradíme.</p>
					<p class="tj-home-parents__phone"><a href="tel:+420605947103">605 947 103</a></p>
					<a class="tj-home-text-link" href="#kontakt">Napsat dotaz</a>
				</section>
			</div>
		</div>
	</section>

	<?php if ($archiveSeasons !== []) : ?>
	<section class="tj-home-section tj-home-section--archive" aria-labelledby="archive-heading" data-archive-section data-post-content-url="<?php echo esc_url(add_query_arg('action', TaborovaJizda\Theme\PostContentEndpoint::ACTION, admin_url('admin-ajax.php'))); ?>">
		<div class="tj-home-section__heading" id="minule-rocniky"><h2 id="archive-heading">Minulé ročníky</h2></div>
		<div class="tj-home-archive-grid">
			<?php foreach ($archiveSeasons as $index => $archiveSeason) : ?>
				<?php
				$seasonYear = (string) $archiveSeason['year'];
				$dialogId = 'season-dialog-' . $seasonYear;
				$seasonLabel = sprintf('Tábor %s: %s', $seasonYear, $archiveSeason['title']);
				$fallbackUrl = add_query_arg('rok', $seasonYear, home_url('/minule-rocniky/'));
				$thumbnailUrl = sprintf('https://i.ytimg.com/vi/%s/%s.jpg', rawurlencode($archiveSeason['youtubeId']), rawurlencode($archiveSeason['thumbnail']));
				$category = $archiveSeason['categorySlug'] ? get_category_by_slug($archiveSeason['categorySlug']) : null;
				$seasonPosts = $category instanceof WP_Term ? get_posts(['cat' => $category->term_id, 'numberposts' => -1, 'post_status' => 'publish']) : [];
				?>
				<article class="tj-home-archive-card<?php echo $index >= $visibleArchiveSeasons ? ' is-extra' : ''; ?>">
					<a class="tj-home-archive-card__open" href="<?php echo esc_url($fallbackUrl); ?>" data-home-dialog-open="<?php echo esc_attr($dialogId); ?>" aria-label="<?php echo esc_attr('Otevřít ročník ' . $seasonLabel); ?>">
						<img src="<?php echo esc_url($thumbnailUrl); ?>" alt="" loading="lazy" width="480" height="270">
						<span class="tj-home-play" aria-hidden="true"></span>
					</a>
					<div><p class="tj-home-meta"><?php echo esc_html($seasonYear); ?></p><h3><a href="<?php echo esc_url($fallbackUrl); ?>" data-home-dialog-open="<?php echo esc_attr($dialogId); ?>"><?php echo esc_html($archiveSeason['title']); ?></a></h3></div>
					<dialog class="tj-home-content-dialog" id="<?php echo esc_attr($dialogId); ?>" aria-labelledby="<?php echo esc_attr($dialogId . '-title'); ?>">
						<div class="tj-home-news-dialog__content">
							<div class="tj-home-news-dialog__top"><p class="tj-home-meta">Ročník <?php echo esc_html($seasonYear); ?></p><button class="tj-home-news-dialog__close" type="button" data-home-dialog-close aria-label="Zavřít ročník">Zavřít</button></div>
							<h2 id="<?php echo esc_attr($dialogId . '-title'); ?>"><?php echo esc_html($archiveSeason['title']); ?></h2>
							<div class="youtube-player tj-home-season-dialog__trailer" data-id="<?php echo esc_attr($archiveSeason['youtubeId']); ?>" data-title="<?php echo esc_attr('Trailer ' . $seasonLabel); ?>" data-thumbnail="<?php echo esc_url($thumbnailUrl); ?>">
								<a class="tj-home-text-link" href="<?php echo esc_url('https://www.youtube.com/watch?v=' . rawurlencode($archiveSeason['youtubeId'])); ?>" rel="noopener noreferrer">Přehrát trailer na YouTube</a>
							</div>
							<?php if ($seasonPosts) : ?>
								<h3 class="tj-home-season-dialog__heading">Články a fotografie</h3>
								<div class="tj-home-season-articles" data-exclusive-accordion>
									<?php foreach ($seasonPosts as $seasonPost) : ?>
										<details class="tj-home-season-article" name="<?php echo esc_attr($dialogId . '-articles'); ?>">
											<summary><span class="tj-home-season-article__title"><?php echo esc_html(html_entity_decode(get_the_title($seasonPost), ENT_QUOTES | ENT_HTML5, 'UTF-8')); ?></span><span class="tj-home-season-article__date"><?php echo esc_html(get_the_date('j. n. Y', $seasonPost)); ?></span></summary>
											<div class="tj-home-prose tj-home-season-article__body" data-article-body data-post-id="<?php echo esc_attr((string) $seasonPost->ID); ?>">
												<p class="tj-home-season-article__fallback"><a href="<?php echo esc_url(get_permalink($seasonPost)); ?>">Přečíst článek</a></p>
											</div>
										</details>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
						</div>
					</dialog>
				</article>
			<?php endforeach; ?>
		</div>
		<?php if (count($archiveSeasons) > $visibleArchiveSeasons) : ?>
			<button class="tj-home-show-all" type="button" data-archive-expand aria-expanded="false" data-label-more="<?php echo esc_attr(sprintf('Zobrazit všechny ročníky (%d)', count($archiveSeasons))); ?>" data-label-less="Zobrazit méně ročníků"><?php echo esc_html(sprintf('Zobrazit všechny ročníky (%d)', count($archiveSeasons))); ?></button>
		<?php endif; ?>
	</section>
	<?php endif; ?>

	<section class="tj-home-section tj-home-section--contact" aria-labelledby="contact-heading">
		<div class="tj-home-section__heading" id="kontakt"><p class="tj-home-eyebrow">Spojení</p><h2 id="contact-heading">Jsme na příjmu</h2></div>
		<?php
		$mapEmbed = null;
		if ($mapCoordinates) {
		    $mapLat = $mapCoordinates['lat'];
		    $mapLon = $mapCoordinates['lon'];
		    $mapEmbed = add_query_arg([
		        'bbox' => implode(',', [$mapLon - 0.025, $mapLat - 0.012, $mapLon + 0.025, $mapLat + 0.012]),
		        'layer' => 'mapnik',
		        'marker' => $mapLat . ',' . $mapLon,
		    ], 'https://www.openstreetmap.org/export/embed.html');
		}
		?>
		<div class="tj-home-contact-grid">
			<div class="tj-home-contact-form">
				<?php get_template_part('template-parts/question-form', null, ['heading' => 'Chcete nám poslat dotaz?', 'form_id' => 'home-question']); ?>
			</div>
			<div class="tj-home-contact-side">
				<div class="tj-home-contact-details">
					<p><strong>Martin Vojtěch</strong><br>hlavní vedoucí</p>
					<p><a href="tel:+420605947103">605 947 103</a><br><a href="mailto:<?php echo esc_attr($season->contactEmail()); ?>"><?php echo esc_html($season->contactEmail()); ?></a></p>
					<p>Pionýrů 1332<br>564 01 Žamberk</p>
					<p>Tábor Plátěná osada<br>Splzov, Železný Brod</p>
				</div>
				<div class="tj-home-contact-social-row">
					<p class="tj-home-contact-social__label" id="social-label">Sledujte nás</p>
					<ul class="tj-home-contact-social" aria-labelledby="social-label">
						<?php foreach ($socialLinks as $socialLabel => $socialUrl) : ?>
							<li><a class="tj-home-social tj-home-social--<?php echo esc_attr(strtolower($socialLabel)); ?>" href="<?php echo esc_url($socialUrl); ?>" rel="noopener noreferrer" aria-label="<?php echo esc_attr($socialLabel); ?>" title="<?php echo esc_attr($socialLabel); ?>"><span class="tj-home-social__icon" aria-hidden="true"><?php echo $socialIcon(strtolower($socialLabel)); // phpcs:ignore WordPress.Security.EscapeOutput -- core block SVG ?></span></a></li>
						<?php endforeach; ?>
					</ul>
				</div>
				<?php if ($mapEmbed) : ?>
					<figure class="tj-home-map">
						<iframe src="<?php echo esc_url($mapEmbed); ?>" title="<?php echo esc_attr('Mapa tábora: ' . ($season->location() ?? 'Táborová jízda')); ?>" loading="lazy" referrerpolicy="no-referrer"></iframe>
						<figcaption>
							<a class="tj-home-text-link" href="<?php echo esc_url($mapsUrl); ?>" target="_blank" rel="noopener noreferrer">Google Mapy<span class="tj-home-visually-hidden"> (nové okno)</span></a>
							<a class="tj-home-text-link" href="<?php echo esc_url('https://mapy.cz/zakladni?x=' . $mapLon . '&y=' . $mapLat . '&z=16&source=coor&id=' . rawurlencode($mapLon . ',' . $mapLat)); ?>" target="_blank" rel="noopener noreferrer">Mapy.cz<span class="tj-home-visually-hidden"> (nové okno)</span></a>
						</figcaption>
					</figure>
				<?php endif; ?>
			</div>
		</div>
	</section>
</main>
<?php get_footer(); ?>
