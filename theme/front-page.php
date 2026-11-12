<?php
declare(strict_types=1);

use TaborovaJizda\Theme\Home;

get_header();

$season = taborovajizda_season();
$year = $season->year();
$ended = $season->hasEnded();
$map = Home::map($season);
$trailer = taborovajizda_season_archive($year)[0] ?? null;
$pinned = Home::pinnedIds();
$about = Home::about(get_post());
$documents = Home::documents();
$part = static fn (string $name, array $args) => get_template_part('template-parts/' . $name, null, $args);
$youtubeThumbnail = static fn (array $video): string => sprintf('https://i.ytimg.com/vi/%s/%s.jpg', rawurlencode($video['youtubeId']), rawurlencode($video['thumbnail']));
$youtubeLink = static fn (array $video): string => '<a class="tj-link" href="' . esc_url('https://www.youtube.com/watch?v=' . rawurlencode($video['youtubeId'])) . '" target="_blank" rel="noopener noreferrer">Přehrát trailer na YouTube</a>';
?>
<main id="main-content">
	<section class="tj:relative tj:grid tj:min-h-[min(44rem,calc(100dvh-4.5rem))] tj:overflow-hidden tj:bg-canvas tj:text-ink" aria-labelledby="season-title">
		<div class="tj-hero-art tj:absolute tj:inset-x-0 tj:top-0 tj:-bottom-[clamp(1.5rem,2.5vw,3rem)]" aria-hidden="true"></div>
		<div class="tj:relative tj:z-1 tj:m-auto tj:grid tj:w-[min(100%-2rem,82rem)] tj:items-start tj:gap-[clamp(2rem,5vw,4rem)] tj:py-[clamp(3rem,8vw,6rem)] tj:max-md:gap-8 tj:max-md:pt-[clamp(9.5rem,43vw,14rem)] tj:max-md:pb-12 <?php echo $trailer ? 'tj:min-[60rem]:grid-cols-[minmax(0,1.1fr)_minmax(0,.9fr)]' : ''; ?>">
			<div class="tj-stagger tj:flex tj:min-w-0 tj:flex-col tj:items-start tj:rounded-xl tj:bg-paper/94 tj:p-[clamp(1.5rem,3vw,2.25rem)] tj:shadow-hero tj:max-[60rem]:p-5">
				<p class="tj-eyebrow"><?php echo esc_html(sprintf($ended ? 'Ohlédnutí za létem %d' : 'Táborová jízda %d', $year)); ?></p>
				<h1 id="season-title" class="tj:m-0 tj:max-w-[14ch] tj:font-display tj:text-[clamp(2.75rem,6vw,5rem)] tj:leading-[.95] tj:font-normal tj:tracking-[.01em] tj:uppercase"><?php echo esc_html($season->title()); ?></h1>
				<p class="tj:mt-5 tj:mb-0 tj:max-w-136 tj:text-[clamp(1.05rem,1.6vw,1.25rem)] tj:leading-normal">Dva týdny her, výprav a přátelství v přírodě pro děti, které chtějí zažít léto naplno.</p>
				<?php if ($ended) : ?>
					<p class="tj:mt-4 tj:mb-0 tj:max-w-136 tj:rounded-r-lg tj:border-l-4 tj:border-tangerine tj:bg-white/72 tj:px-4 tj:py-[.7rem] tj:font-bold tj:leading-[1.45] tj:text-ink">Další léto už připravujeme. Termín a otevření přihlášek oznámíme tady a na našich sociálních sítích.</p>
				<?php endif; ?>
				<?php $part('facts', ['facts' => Home::facts($season, true), 'map' => $map['google']]); ?>
				<div class="tj:mt-7 tj:flex tj:flex-wrap tj:gap-3">
					<?php if ($season->acceptsRegistrations() || taborovajizda_debug_registration_cta()) : ?>
						<a class="tj-btn <?php echo $season->acceptsRegistrations() ? '' : 'tj-debug'; ?>" href="<?php echo esc_url(Home::registrationUrl()); ?>"><?php echo esc_html(Home::registrationCta($season)); ?></a>
						<a class="tj-btn tj-btn-quiet" href="#o-tabore">Poznat tábor</a>
					<?php else : ?>
						<a class="tj-btn" href="#o-tabore">Poznat tábor</a>
					<?php endif; ?>
					<?php if (! $season->acceptsRegistrations()) : ?><a class="tj-btn tj-btn-quiet" href="#minule-rocniky">Minulé ročníky</a><?php endif; ?>
				</div>
			</div>
			<?php if ($trailer) : ?>
				<figure class="tj-rise tj:relative tj:mt-0 tj:mx-0 tj:[animation-delay:.18s]">
					<figcaption class="tj-eyebrow tj:pointer-events-none tj:absolute tj:top-4 tj:left-4 tj:z-3 tj:m-0 tj:rounded-full tj:bg-paper tj:px-[.7rem] tj:py-[.35rem] tj:shadow-pill"><?php echo esc_html(sprintf('Trailer %d', $trailer['year'])); ?></figcaption>
					<div class="youtube-player youtube-player--box tj:rounded-xl tj:border-6 tj:border-paper tj:bg-white/85 tj:shadow-trailer" data-id="<?php echo esc_attr($trailer['youtubeId']); ?>" data-title="<?php echo esc_attr(sprintf('Trailer %d: %s', $trailer['year'], $trailer['title'])); ?>" data-thumbnail="<?php echo esc_url($youtubeThumbnail($trailer)); ?>">
						<?php echo $youtubeLink($trailer); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above ?>
					</div>
				</figure>
			<?php endif; ?>
		</div>
	</section>

	<section class="tj-section tj:[--tj-band:var(--tj-color-morning)] tj:[--tj-link:var(--tj-color-burnt)]" aria-labelledby="news-heading">
		<?php $part('section-heading', ['anchor' => 'aktuality', 'id' => 'news-heading', 'title' => 'Aktuality ' . $year]); ?>
		<?php if ($news = Home::news($year)) : ?>
			<?php $part('pager', [
				'id' => 'news-track',
				'prev' => 'Předchozí aktuality',
				'next' => 'Další aktuality',
				'label' => 'Aktuality Táborové jízdy pro rok ' . $year,
				'class' => 'tj:mt-8 tj:[--tj-gap:1.25rem] tj:[--tj-per-view:3] tj:max-lg:[--tj-per-view:2] tj:max-md:[--tj-per-view:1]',
				'track' => 'tj-rotate tj:nojs:grid tj:nojs:grid-cols-[repeat(auto-fill,minmax(min(100%,18rem),1fr))] tj:nojs:gap-5',
				'items' => static function () use ($news, $pinned): void {
					foreach ($news as $post) :
						setup_postdata($GLOBALS['post'] = $post);
						$dialogId = 'news-dialog-' . $post->ID;
						$content = apply_filters('the_content', get_the_content());
						$image = Home::newsCardImage($post, $content);
						?>
						<article class="tj-card tj:group/card tj:flex tj:flex-col tj:shadow-card <?php echo $image ? '' : 'tj:border-t-[6px] tj:border-t-(--tj-card)'; ?>" data-card>
							<?php if ($image) : ?>
								<a class="tj:relative tj:block tj:aspect-16/10 tj:w-full tj:overflow-hidden tj:bg-sun tj:[&>img]:absolute tj:[&>img]:inset-0 tj:[&>img]:block tj:[&>img]:size-full tj:[&>img]:object-cover" href="<?php the_permalink(); ?>" data-dialog-open="<?php echo esc_attr($dialogId); ?>" aria-label="Otevřít aktualitu: <?php echo esc_attr(get_the_title()); ?>"><?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput -- core image markup ?></a>
							<?php endif; ?>
							<div class="tj:flex tj:flex-1 tj:flex-col tj:p-[1.4rem] tj:[&>p:not(.tj-meta)]:leading-[1.5] tj:[&>p:not(.tj-meta)]:text-muted">
								<?php if (in_array($post->ID, $pinned, true)) : ?><p class="tj:mt-0 tj:mb-2 tj:text-[.8rem] tj:font-extrabold tj:tracking-[.06em] tj:uppercase">Důležité oznámení</p><?php endif; ?>
								<?php if ($image) : ?>
									<p class="tj-meta"><?php echo esc_html(get_the_date()); ?></p>
								<?php else : ?>
									<p class="tj:mt-0 tj:mb-4 tj:inline-flex tj:items-baseline tj:gap-[.45rem] tj:self-start tj:rounded-lg tj:bg-(--tj-tint) tj:px-[.7rem] tj:py-[.35rem] tj:text-[.95rem] tj:font-extrabold tj:lowercase"><span class="tj:font-display tj:text-[1.6rem] tj:leading-none"><?php echo esc_html(get_the_date('j.')); ?></span> <span><?php echo esc_html(get_the_date('F Y')); ?></span></p>
								<?php endif; ?>
								<h3 class="tj:m-0 tj:text-[1.3rem] tj:leading-[1.15]"><a class="tj:text-ink tj:no-underline tj:transition-colors tj:group-hover/card:text-link" href="<?php the_permalink(); ?>" data-dialog-open="<?php echo esc_attr($dialogId); ?>"><?php the_title(); ?></a></h3>
								<p class="<?php echo $image ? '' : 'tj:line-clamp-6'; ?>"><?php echo esc_html(wp_trim_words(get_the_excerpt(), $image ? 22 : 48)); ?></p>
								<a class="tj-link tj:mt-auto tj:self-start tj:font-normal" href="<?php the_permalink(); ?>" data-dialog-open="<?php echo esc_attr($dialogId); ?>">Číst celou aktualitu</a>
							</div>
							<?php get_template_part('template-parts/dialog', null, [
								'id' => $dialogId,
								'meta' => get_the_date(),
								'title' => get_the_title(),
								'close' => 'Zavřít aktualitu',
								'content' => static function () use ($content): void {
									// Featured image only when the text has no photo of its own (avoids duplicates).
									if (has_post_thumbnail() && ! str_contains($content, '<img')) : ?>
										<div class="tj:mb-5 tj:[&_img]:block tj:[&_img]:aspect-3/2 tj:[&_img]:h-auto tj:[&_img]:w-full tj:[&_img]:rounded-lg tj:[&_img]:bg-black tj:[&_img]:object-contain" data-zoom><?php the_post_thumbnail('large'); ?></div>
									<?php endif; ?>
									<div class="tj-prose tj:mb-6"><?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput -- the_content output ?></div>
									<?php
								},
							]); ?>
						</article>
						<?php
					endforeach;
					wp_reset_postdata();
				},
			]); ?>
		<?php else : ?>
			<p>Nové zprávy z tábora brzy přidáme.</p>
		<?php endif; ?>
	</section>

	<section class="tj-section tj:grid tj:items-center tj:gap-[clamp(2rem,6vw,5rem)] tj:[--tj-band:var(--tj-color-paper)] tj:[--tj-stripe:var(--tj-stripe-2)] <?php echo $about['image'] ? 'tj:md:grid-cols-[minmax(0,1.15fr)_minmax(0,.85fr)]' : ''; ?>" aria-labelledby="about-heading">
		<div data-reveal>
			<?php $part('section-heading', ['anchor' => 'o-tabore', 'id' => 'about-heading', 'title' => 'Tábor, který tvoříme společně']); ?>
			<div class="tj-prose tj:mt-6 tj:max-w-152"><?php echo $about['intro']; // phpcs:ignore WordPress.Security.EscapeOutput -- the_content output ?></div>
			<?php if ($about['rest']) : ?>
				<a class="tj-btn tj-btn-quiet tj:mt-5" href="<?php echo esc_url(get_permalink()); ?>" data-dialog-open="story-dialog">Celý příběh tábora</a>
				<?php $part('dialog', [
					'id' => 'story-dialog',
					'meta' => 'O táboře',
					'title' => 'Tábor, který tvoříme společně',
					'close' => 'Zavřít příběh',
					'content' => static function () use ($about): void {
						echo '<div class="tj-prose tj-story">' . $about['rest'] . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- the_content output
					},
				]); ?>
			<?php endif; ?>
		</div>
		<?php if ($about['image']) : ?>
			<div class="tj:[&_figcaption]:mt-[.6rem] tj:[&_figcaption]:text-[.9rem] tj:[&_figcaption]:text-muted tj:[&_img]:block tj:[&_img]:h-auto tj:[&_img]:w-full tj:[&_img]:rounded-xl tj:[&_img]:shadow-media" data-reveal data-zoom><?php echo $about['image']; // phpcs:ignore WordPress.Security.EscapeOutput -- core image markup ?></div>
		<?php endif; ?>
	</section>

	<section class="tj-section tj:[--tj-stripe:var(--tj-stripe-3)]" aria-labelledby="people-heading">
		<?php $part('section-heading', ['anchor' => 'lide', 'id' => 'people-heading', 'title' => 'Lidé, kteří tábor tvoří']); ?>
		<?php if ($people = Home::people()) : ?>
			<?php $part('pager', [
				'id' => 'people-track',
				'prev' => 'Předchozí lidé',
				'next' => 'Další lidé',
				'tag' => 'ul',
				'track' => 'tj-rotate tj:m-0 tj:list-none tj:p-0 tj:nojs:mt-6 tj:nojs:grid tj:nojs:grid-cols-[repeat(auto-fill,minmax(min(100%,9.5rem),1fr))] tj:nojs:gap-x-4 tj:nojs:gap-y-6 tj:nojs:max-md:grid-cols-2',
				'items' => static function () use ($people, $part): void {
					foreach ($people as $post) :
						$person = Home::person($post);
						$dialogId = 'person-dialog-' . $post->ID;
						if ($person['initials'] === null) : ?>
							<li class="tj:grid tj:min-w-0 tj:content-start"><span class="tj:block tj:pt-[38%] tj:text-[1.15rem] tj:font-extrabold tj:leading-[1.3] tj:text-muted"><?php echo esc_html($person['name']); ?></span></li>
							<?php continue;
						endif; ?>
						<li class="tj:min-w-0">
							<a class="tj:group tj:grid tj:w-full tj:gap-[.2rem] tj:rounded-xl tj:text-ink tj:no-underline" href="<?php echo esc_url(get_permalink($post)); ?>" data-dialog-open="<?php echo esc_attr($dialogId); ?>" data-card>
								<span class="tj:mb-[.55rem] tj:grid tj:aspect-4/5 tj:place-items-center tj:overflow-hidden tj:rounded-xl tj:bg-(--tj-card) tj:shadow-card tj:transition-[transform,box-shadow] tj:duration-180 tj:group-hover:translate-y-[-0.2rem] tj:group-hover:shadow-ring tj:group-focus-visible:translate-y-[-0.2rem] tj:group-focus-visible:shadow-ring tj:[&_img]:block tj:[&_img]:size-full tj:[&_img]:object-cover">
									<?php if (has_post_thumbnail($post)) : ?>
										<?php echo get_the_post_thumbnail($post, 'medium', ['loading' => 'lazy', 'alt' => '']); ?>
									<?php else : ?>
										<span class="tj:text-[2.25rem] tj:font-extrabold tj:tracking-[.02em]" aria-hidden="true"><?php echo esc_html($person['initials']); ?></span>
									<?php endif; ?>
								</span>
								<span class="tj:font-extrabold tj:leading-[1.2] tj:group-hover:text-link"><?php echo esc_html($person['name']); ?></span>
								<?php if ($person['role']) : ?><span class="tj:text-[.9rem] tj:leading-[1.3] tj:text-muted"><?php echo esc_html($person['role']); ?></span><?php endif; ?>
							</a>
							<?php $part('dialog', [
								'id' => $dialogId,
								'meta' => 'Náš tým',
								'title' => get_the_title($post),
								'close' => 'Zavřít profil',
								'content' => static function () use ($post): void { ?>
									<div class="tj:grid tj:grid-cols-[min(40%,15rem)_minmax(0,1fr)] tj:items-start tj:gap-6 tj:max-[30rem]:grid-cols-1 tj:max-[30rem]:gap-4 tj:[&>:only-child]:col-span-full">
										<?php if (has_post_thumbnail($post)) : ?><figure class="tj:m-0 tj:mt-1 tj:aspect-4/5 tj:overflow-hidden tj:rounded-xl tj:bg-canvas tj:max-[30rem]:w-[min(70%,15rem)] tj:[&_img]:block tj:[&_img]:size-full tj:[&_img]:object-cover" data-zoom="team"><?php echo get_the_post_thumbnail($post, 'medium_large', ['alt' => get_the_title($post)]); ?></figure><?php endif; ?>
										<div class="tj-prose tj-person tj:mb-6"><?php echo Home::personContent($post); // phpcs:ignore WordPress.Security.EscapeOutput -- the_content output ?></div>
									</div>
								<?php },
							]); ?>
						</li>
						<?php
					endforeach;
				},
			]); ?>
		<?php else : ?>
			<p>Představení týmu brzy doplníme. Zatím se podívejte do <a class="tj-link" href="<?php echo esc_url(home_url('/category/lide/')); ?>">archivu lidí</a>.</p>
		<?php endif; ?>
	</section>

	<section class="tj-section tj:[--tj-band:var(--tj-color-afternoon)] tj:[--tj-stripe:var(--tj-stripe-4)] tj:[--tj-link:var(--tj-color-burnt)] tj:[--tj-eyebrow:var(--tj-color-warm-ink)]" aria-labelledby="program-heading">
		<?php $part('section-heading', ['anchor' => 'program', 'id' => 'program-heading', 'title' => 'Hry, výpravy a přírodu', 'eyebrow' => 'Co zažijeme']); ?>
		<?php if ($groups = Home::activityGroups()) : ?>
			<ul class="tj-rotate tj:m-0 tj:mt-8 tj:grid tj:list-none tj:grid-cols-4 tj:gap-5 tj:p-0 tj:max-md:grid-cols-1">
				<?php foreach ($groups as $key => $group) : ?>
					<?php $dialogId = 'activities-dialog-' . $key; ?>
					<li class="tj:min-w-0" data-reveal>
						<a class="tj:group tj:flex tj:h-full tj:flex-col tj:overflow-hidden tj:rounded-xl tj:border tj:border-t-[6px] tj:border-ink/7 tj:border-t-(--tj-card) tj:bg-surface tj:text-ink tj:no-underline tj:shadow-tile tj:transition-[transform,box-shadow] tj:duration-180 tj:hover:translate-y-[-0.3rem] tj:hover:shadow-ring-lift tj:focus-visible:translate-y-[-0.3rem] tj:focus-visible:shadow-ring-lift" href="<?php echo esc_url(get_permalink($group['posts'][0])); ?>" data-dialog-open="<?php echo esc_attr($dialogId); ?>" data-card>
							<span class="tj:block tj:aspect-4/3 tj:overflow-hidden tj:bg-linear-135/srgb tj:from-sun/35 tj:to-tangerine/35 tj:[&_img]:block tj:[&_img]:size-full tj:[&_img]:object-cover tj:[&_img]:transition-transform tj:[&_img]:duration-300 tj:group-hover:[&_img]:scale-105" aria-hidden="true"><?php echo $group['cover']; // phpcs:ignore WordPress.Security.EscapeOutput -- core image markup ?></span>
							<span class="tj:flex tj:flex-1 tj:flex-col tj:gap-3 tj:px-[1.2rem] tj:pt-[1.15rem] tj:pb-5">
								<span class="tj:text-[1.35rem] tj:font-extrabold tj:leading-[1.15]"><?php echo esc_html($group['label']); ?></span>
								<span class="tj:flex tj:flex-wrap tj:gap-[.35rem]">
									<?php foreach ($group['posts'] as $post) : ?><span class="tj:rounded-lg tj:bg-(--tj-tint) tj:px-2 tj:py-[.2rem] tj:text-[.85rem] tj:leading-[1.35]"><?php echo esc_html(html_entity_decode(get_the_title($post), ENT_QUOTES | ENT_HTML5, 'UTF-8')); ?></span><?php endforeach; ?>
								</span>
								<span class="tj:mt-auto tj:inline-flex tj:items-center tj:gap-[.35rem] tj:font-extrabold tj:text-link tj:after:content-['→'] tj:after:transition-transform tj:group-hover:after:translate-x-[3px]">Přečíst vše</span>
							</span>
						</a>
						<?php $part('dialog', [
							'id' => $dialogId,
							'meta' => 'Co zažijeme',
							'title' => $group['label'],
							'content' => static function () use ($group): void {
								foreach ($group['posts'] as $post) : ?>
									<section class="tj:[&+&]:mt-10 tj:[&+&]:border-t tj:[&+&]:border-ink/11 tj:[&+&]:pt-8" aria-labelledby="activity-<?php echo esc_attr((string) $post->ID); ?>">
										<h3 id="activity-<?php echo esc_attr((string) $post->ID); ?>" class="tj:m-0 tj:mb-4 tj:text-[clamp(1.5rem,2.6vw,2rem)] tj:leading-[1.1] tj:text-ink"><?php echo esc_html(get_the_title($post)); ?></h3>
										<div class="tj-prose tj-activity tj:max-w-248"><?php echo $group['content'][$post->ID]; // phpcs:ignore WordPress.Security.EscapeOutput -- the_content output ?></div>
									</section>
								<?php endforeach;
							},
						]); ?>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<p>Program tábora brzy doplníme.</p>
		<?php endif; ?>
	</section>

	<?php if ($pastSeasons = Home::pastSeasons($season)) : ?>
	<section class="tj-section tj:[--tj-band:var(--tj-color-dusk)]" aria-labelledby="archive-heading" data-post-content-url="<?php echo esc_url(Home::postContentUrl()); ?>">
		<?php $part('section-heading', ['anchor' => 'minule-rocniky', 'id' => 'archive-heading', 'title' => 'Minulé ročníky', 'night' => true]); ?>
		<?php $part('pager', [
			'id' => 'archive-track',
			'prev' => 'Předchozí ročníky',
			'next' => 'Další ročníky',
			'label' => 'Minulé ročníky Táborové jízdy',
			'class' => 'tj-pager-night tj:mt-8 tj:[--tj-gap:1.25rem] tj:[--tj-per-view:4] tj:max-lg:[--tj-per-view:3] tj:max-md:[--tj-gap:.75rem] tj:max-md:[--tj-per-view:1]',
			'track' => 'tj:nojs:grid tj:nojs:grid-cols-[repeat(auto-fill,minmax(min(100%,16rem),1fr))] tj:nojs:gap-5',
			'items' => static function () use ($pastSeasons, $youtubeThumbnail, $youtubeLink): void {
				foreach ($pastSeasons as $past) :
					$dialogId = 'season-dialog-' . $past['year'];
					$label = sprintf('Tábor %d: %s', $past['year'], $past['title']);
					?>
					<article class="tj-card tj:group/card tj:border-transparent tj:shadow-deep" data-card>
						<a class="tj:group tj:relative tj:block tj:rounded-xl" href="#minule-rocniky" data-dialog-open="<?php echo esc_attr($dialogId); ?>" aria-label="<?php echo esc_attr('Otevřít ročník ' . $label); ?>">
							<img class="tj:block tj:aspect-video tj:h-auto tj:w-full tj:object-cover" src="<?php echo esc_url($youtubeThumbnail($past)); ?>" alt="" loading="lazy" width="480" height="270">
							<span class="tj:absolute tj:top-1/2 tj:left-1/2 tj:size-14 tj:-translate-1/2 tj:rounded-full tj:bg-sun tj:shadow-play tj:transition-transform tj:group-hover:scale-110 tj:after:absolute tj:after:top-1/2 tj:after:left-[55%] tj:after:-translate-1/2 tj:after:border-y-[.6rem] tj:after:border-l-[1rem] tj:after:border-y-transparent tj:after:border-l-ink" aria-hidden="true"></span>
						</a>
						<div class="tj:p-5"><p class="tj-meta"><?php echo esc_html((string) $past['year']); ?></p><h3 class="tj:m-0 tj:text-[1.35rem] tj:leading-[1.1]"><a class="tj:text-ink tj:no-underline tj:transition-colors tj:group-hover/card:text-link" href="#minule-rocniky" data-dialog-open="<?php echo esc_attr($dialogId); ?>"><?php echo esc_html($past['title']); ?></a></h3></div>
						<?php get_template_part('template-parts/dialog', null, [
							'id' => $dialogId,
							'meta' => 'Ročník ' . $past['year'],
							'title' => $past['title'],
							'close' => 'Zavřít ročník',
							'content' => static function () use ($past, $dialogId, $label, $youtubeThumbnail, $youtubeLink): void { ?>
								<div class="youtube-player youtube-player--box tj:mt-6 tj:rounded-xl tj:bg-black" data-id="<?php echo esc_attr($past['youtubeId']); ?>" data-title="<?php echo esc_attr('Trailer ' . $label); ?>" data-thumbnail="<?php echo esc_url($youtubeThumbnail($past)); ?>">
									<?php echo $youtubeLink($past); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above ?>
								</div>
								<?php if ($past['posts']) : ?>
									<h3 class="tj:mt-10 tj:mb-5 tj:border-t tj:border-ink/12 tj:pt-8 tj:text-[1.4rem] tj:text-ink">Články a fotografie</h3>
									<div class="tj:grid tj:gap-3" data-exclusive-accordion>
										<?php foreach ($past['posts'] as $post) : ?>
											<details class="tj:group tj:scroll-mt-4 tj:rounded-lg tj:border tj:border-ink/11 tj:bg-canvas tj:open:border-sun tj:open:bg-surface" name="<?php echo esc_attr($dialogId . '-articles'); ?>">
												<summary class="tj:flex tj:min-h-[56px] tj:cursor-pointer tj:list-none tj:items-center tj:gap-4 tj:px-[1.15rem] tj:py-[.9rem] tj:hover:bg-sun/14 tj:[&::-webkit-details-marker]:hidden tj:after:ml-1 tj:after:size-[.55rem] tj:after:flex-none tj:after:rotate-45 tj:after:border-r-2 tj:after:border-b-2 tj:after:border-current tj:after:transition-transform tj:group-open:after:rotate-[-135deg]"><span class="tj:flex-1 tj:font-extrabold tj:leading-[1.3] tj:text-ink"><?php echo esc_html(html_entity_decode(get_the_title($post), ENT_QUOTES | ENT_HTML5, 'UTF-8')); ?></span><span class="tj:text-[.85rem] tj:whitespace-nowrap tj:text-muted"><?php echo esc_html(get_the_date('j. n. Y', $post)); ?></span></summary>
												<div class="tj-prose tj:px-[1.15rem] tj:pt-2 tj:pb-6 tj:text-[1rem] tj:[&_img]:h-auto tj:[&_img]:max-w-full tj:[&_img]:rounded-lg" data-article-body data-post-id="<?php echo esc_attr((string) $post->ID); ?>">
													<p class="tj:js:in-[[data-article-body]:not([data-error])]:hidden"><a href="<?php echo esc_url(get_permalink($post)); ?>">Přečíst článek</a></p>
												</div>
											</details>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>
							<?php },
						]); ?>
					</article>
					<?php
				endforeach;
			},
		]); ?>
	</section>
	<?php endif; ?>

	<section class="tj-section tj:[--tj-band:var(--tj-color-paper)] tj:[--tj-stripe:var(--tj-stripe-2)]" aria-labelledby="parents-heading">
		<?php $part('section-heading', ['anchor' => 'pro-rodice', 'id' => 'parents-heading', 'title' => 'Pro rodiče']); ?>
		<div class="tj:mt-8 tj:grid tj:items-stretch tj:gap-5 tj:md:grid-cols-2" data-collapsed-equal>
			<div class="tj-panel tj:[--tj-panel:var(--tj-color-grass)]" data-reveal data-exclusive-details>
				<h3 class="tj:m-0 tj:mb-4 tj:text-[clamp(1.35rem,2.4vw,1.75rem)] tj:leading-[1.15] tj:text-ink">Časté otázky</h3>
				<div class="tj-prose tj-faq"><?php echo Home::faq(); // phpcs:ignore WordPress.Security.EscapeOutput -- the_content output ?></div>
			</div>
			<?php if ($documents['html'] !== '') : ?>
				<section class="tj-panel tj:flex tj:min-w-0 tj:flex-col tj:items-start tj:[--tj-panel:var(--tj-color-sky)] tj:md:in-[.is-faq-open]:min-h-(--tj-collapsed) tj:md:in-[.is-faq-open]:self-start" id="dokumenty" aria-labelledby="documents-heading" data-reveal>
					<h3 id="documents-heading" class="tj:m-0 tj:mb-4 tj:text-[clamp(1.5rem,2.6vw,2rem)] tj:leading-[1.1] tj:text-ink">Dokumenty ke stažení</h3>
					<?php if ($documents['links'] !== []) : ?>
						<ul class="tj:m-0 tj:mb-3 tj:grid tj:w-full tj:list-none tj:gap-2 tj:pl-[1.1rem] tj:[&>li+li]:mt-[.6rem]">
							<?php foreach ($documents['links'] as $link) : ?>
								<li class="tj:[&>a]:flex tj:[&>a]:min-h-[48px] tj:[&>a]:items-center tj:[&>a]:gap-3 tj:[&>a]:rounded-lg tj:[&>a]:border tj:[&>a]:border-sun/40 tj:[&>a]:bg-sun/8 tj:[&>a]:px-[.9rem] tj:[&>a]:py-[.65rem] tj:[&>a]:text-[.95rem] tj:[&>a]:font-bold tj:[&>a]:leading-[1.3] tj:[&>a]:text-ink tj:[&>a]:no-underline tj:[&>a]:transition-[background-color,translate] tj:[&>a:hover]:translate-x-[3px] tj:[&>a:hover]:bg-sun/18 tj:[&>a]:before:flex-none tj:[&>a]:before:rounded-[.35rem] tj:[&>a]:before:bg-sun tj:[&>a]:before:px-[.4rem] tj:[&>a]:before:py-[.15rem] tj:[&>a]:before:text-[.7rem] tj:[&>a]:before:font-extrabold tj:[&>a]:before:content-['PDF']"><?php echo $link; // phpcs:ignore WordPress.Security.EscapeOutput -- the_content output ?></li>
							<?php endforeach; ?>
						</ul>
						<?php if ($documents['notes'] !== '') : ?>
							<div class="tj-prose tj:mt-2 tj:text-[1rem] tj:[&>p]:mt-0 tj:[&>p]:mb-3 tj:[&>p]:leading-[1.5] tj:[&>p]:text-muted tj:[&>p:first-child]:text-ink tj:[&>p:last-child]:mb-0"><?php echo $documents['notes']; // phpcs:ignore WordPress.Security.EscapeOutput -- the_content output ?></div>
						<?php endif; ?>
					<?php else : ?>
						<div class="tj-prose"><?php echo $documents['html']; // phpcs:ignore WordPress.Security.EscapeOutput -- the_content output ?></div>
					<?php endif; ?>
				</section>
			<?php endif; ?>
		</div>
	</section>

	<section class="tj-section tj:[--tj-stripe:var(--tj-stripe-3)]" aria-labelledby="contact-heading">
		<?php $part('section-heading', ['anchor' => 'kontakt', 'id' => 'contact-heading', 'title' => 'Jsme na příjmu', 'eyebrow' => 'Spojení']); ?>
		<div class="tj:mt-10 tj:grid tj:items-stretch tj:gap-[clamp(1.5rem,4vw,3rem)] tj:md:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)]">
			<div class="tj:h-full tj:shadow-card" data-reveal>
				<?php $part('question-form', ['heading' => 'Chcete nám poslat dotaz?', 'form_id' => 'home-question']); ?>
			</div>
			<div class="tj:flex tj:min-w-0 tj:flex-col tj:gap-5">
				<div class="tj:grid tj:grid-cols-2 tj:gap-x-6 tj:gap-y-4 tj:text-[1.1rem] tj:leading-[1.55] tj:text-muted tj:[&_a]:font-extrabold tj:[&_a]:text-link tj:[&_p]:m-0 tj:[&_strong]:text-[1.3rem] tj:[&_strong]:text-ink" data-reveal>
					<p><strong>Matěj Vašek</strong><br>hlavní vedoucí</p>
					<p><a href="<?php echo esc_attr(Home::PHONE_URI); ?>"><?php echo esc_html(Home::PHONE); ?></a><br><a href="mailto:<?php echo esc_attr($season->contactEmail()); ?>"><?php echo esc_html($season->contactEmail()); ?></a></p>
					<p><?php echo Home::ADDRESS; // phpcs:ignore WordPress.Security.EscapeOutput -- constant markup ?></p>
					<p>Tábor Plátěná osada<br>Splzov, Železný Brod</p>
				</div>
				<div class="tj:flex tj:flex-wrap tj:items-center tj:gap-x-4 tj:gap-y-3" data-reveal>
					<p class="tj:m-0 tj:font-extrabold tj:text-ink" id="social-label">Sledujte nás</p>
					<?php $part('social-links', ['class' => 'tj:flex-wrap', 'attributes' => ' aria-labelledby="social-label"']); ?>
				</div>
				<?php if ($map['embed']) : ?>
					<figure class="tj:m-0 tj:flex tj:flex-1 tj:flex-col tj:overflow-hidden tj:rounded-xl tj:border tj:border-ink/8 tj:bg-surface" data-reveal>
						<iframe class="tj:block tj:min-h-60 tj:w-full tj:flex-1 tj:border-0" src="<?php echo esc_url($map['embed']); ?>" title="<?php echo esc_attr('Mapa tábora: ' . ($season->location() ?? 'Táborová jízda')); ?>" loading="lazy" referrerpolicy="no-referrer"></iframe>
						<figcaption class="tj:flex tj:flex-wrap tj:items-center tj:gap-x-5 tj:gap-y-1 tj:px-[1.1rem] tj:py-3 tj:font-extrabold tj:text-ink">
							<a class="tj-link" href="<?php echo esc_url($map['google']); ?>" target="_blank" rel="noopener noreferrer">Google Mapy<span class="tj:sr-only"> (nové okno)</span></a>
							<a class="tj-link" href="<?php echo esc_url($map['mapy']); ?>" target="_blank" rel="noopener noreferrer">Mapy.cz<span class="tj:sr-only"> (nové okno)</span></a>
						</figcaption>
					</figure>
				<?php endif; ?>
			</div>
		</div>
	</section>
	<?php // Internal links to other pages of the site open here instead of leaving the homepage (initialiseInternalLinks). ?>
	<?php $part('dialog', [
		'id' => 'page-dialog',
		'meta' => 'Táborová jízda',
		'title' => '',
		'attributes' => ' data-page-dialog data-post-content-url="' . esc_url(Home::postContentUrl()) . '"',
		'content' => static function (): void {
			echo '<div class="tj-prose tj:mb-6" data-page-dialog-body aria-live="polite"></div>';
		},
	]); ?>
</main>
<?php get_footer(); ?>
