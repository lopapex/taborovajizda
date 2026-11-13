<?php
declare(strict_types=1);

use TaborovaJizda\Theme\Home;

get_header();

$season = taborovajizda_season();
$year = $season->year();
$ended = $season->hasEnded();
$registers = $season->acceptsRegistrations();
$debugCta = ! $registers && taborovajizda_debug_registration_cta();
$map = Home::map($season);
$trailer = taborovajizda_season_archive($year)[0] ?? null;
$pinned = Home::pinnedIds();
$about = Home::about(get_post());
$documents = Home::documents();
$part = static fn (string $name, array $args) => get_template_part('template-parts/' . $name, null, $args);
$plain = static fn (string $html): string => html_entity_decode(wp_strip_all_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
$youtubeThumbnail = static fn (array $video): string => sprintf('https://i.ytimg.com/vi/%s/%s.jpg', rawurlencode($video['youtubeId']), rawurlencode($video['thumbnail']));
$youtubeUrl = static fn (array $video): string => 'https://www.youtube.com/watch?v=' . rawurlencode($video['youtubeId']);
$player = static function (array $video, string $title) use ($youtubeThumbnail, $youtubeUrl): void { ?>
	<div class="youtube-player youtube-player--box" data-id="<?php echo esc_attr($video['youtubeId']); ?>" data-title="<?php echo esc_attr($title); ?>" data-thumbnail="<?php echo esc_url($youtubeThumbnail($video)); ?>">
		<a href="<?php echo esc_url($youtubeUrl($video)); ?>" target="_blank" rel="noopener noreferrer">Přehrát trailer na YouTube</a>
	</div>
<?php };

/** News, always right under the hero: the lead story and every other item of the season's year (older years live in "Minulé ročníky"). */
$newsSection = static function () use ($part, $year, $pinned, $plain): void {
	$news = Home::news($year);
	$lead = $news[0] ?? null;
	$rest = array_slice($news, 1);
	?>
	<section class="tj-section tj-vivid tj:[--tj-band:var(--tj-color-band-sun)]" aria-labelledby="news-heading">
		<?php $part('section-heading', ['anchor' => 'aktuality', 'id' => 'news-heading', 'title' => 'Aktuality ' . $year]); ?>
		<?php if ($lead) : ?>
			<div class="tj-flow tj:mt-9 tj:grid tj:items-start tj:gap-x-12 tj:gap-y-8 tj:lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)] tj:lg:items-stretch">
				<?php
				$image = Home::newsCardImage($lead, apply_filters('the_content', $lead->post_content));
				// Without a photo the lead story fills the height of the list beside it and speaks through type.
				$leadCard = $image ? 'tj:lg:sticky tj:lg:top-26' : 'tj:lg:h-full';
				?>
				<?php // The lead story is a night card: on the yellow band it reads as part of the hero above, not as a white box. ?>
				<article class="tj-card tj:border-0 tj:bg-night tj:text-on-night tj:[&_:focus-visible]:outline-sun tj:[--tj-color-muted:var(--tj-color-on-night-muted)] tj:[--wp--preset--color--muted:var(--tj-color-on-night-muted)] <?php echo $leadCard; ?>">
					<a class="tj:flex tj:h-full tj:flex-col tj:text-inherit tj:no-underline" href="<?php echo esc_url(get_permalink($lead)); ?>">
						<?php if ($image) : ?><span class="tj-frame tj:aspect-16/9 tj:rounded-none"><?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput -- core image markup ?></span><?php endif; ?>
						<span class="tj:flex tj:flex-1 tj:flex-col tj:justify-center tj:p-[clamp(1.25rem,3vw,2.25rem)]">
							<span class="tj-meta tj:block"><?php echo esc_html(get_the_date('', $lead)); ?><?php echo in_array($lead->ID, $pinned, true) ? ' · důležité' : ''; ?></span>
							<span class="tj-h3 tj:block <?php echo $image ? 'tj:text-[1.5rem]' : 'tj:text-[clamp(1.6rem,2.8vw,2.4rem)] tj:leading-[1.1]'; ?>"><?php echo esc_html($plain(get_the_title($lead))); ?></span>
							<span class="tj:mt-3 tj:block tj:leading-[1.55] tj:text-muted"><?php echo esc_html(wp_trim_words(get_the_excerpt($lead), $image ? 30 : 50)); ?></span>
						</span>
					</a>
				</article>
				<div>
					<?php if ($rest) : ?>
						<ul class="tj:m-0 tj:list-none tj:p-0">
							<?php foreach ($rest as $post) : ?>
								<li class="tj:border-b tj:border-ink/12 tj:first:border-t">
									<a class="tj:group tj:grid tj:min-h-[56px] tj:gap-x-5 tj:py-4 tj:text-inherit tj:no-underline tj:sm:grid-cols-[6.5rem_minmax(0,1fr)] tj:sm:items-baseline" href="<?php echo esc_url(get_permalink($post)); ?>">
										<span class="tj:text-[.9rem] tj:font-semibold tj:text-muted tj:tabular-nums"><?php echo esc_html(get_the_date('j. n. Y', $post)); ?></span>
										<span class="tj:text-[1.1rem] tj:leading-snug tj:font-bold tj:transition-colors tj:duration-150 tj:group-hover:text-link"><?php echo esc_html($plain(get_the_title($post))); ?></span>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
			</div>
		<?php else : ?>
			<p class="tj-lead">Nové zprávy z tábora brzy přidáme.</p>
		<?php endif; ?>
	</section>
	<?php
};
?>
<main id="main-content">
	<?php // Fills the first screen (viewport minus the header, plus the part the next band's curve covers); the illustration takes whatever height the text leaves. ?>
	<section class="tj:flex tj:min-h-[calc(100svh-4.5rem+clamp(1.5rem,4vw,3.25rem))] tj:flex-col tj:bg-night tj:text-on-night" aria-labelledby="hero-title">
		<div class="tj:flex tj:flex-1 tj:overflow-hidden">
			<div class="tj-hero-art tj:min-h-[clamp(11rem,26vw,22rem)] tj:flex-1" role="img" aria-label="<?php echo esc_attr(sprintf('Ilustrace ročníku %d: %s', $year, Home::dashes($season->title()))); ?>"></div>
		</div>
		<div class="tj-container tj:relative tj:grid tj:gap-x-16 tj:gap-y-7 tj:pt-[clamp(2.75rem,5vw,3.75rem)] tj:pb-[clamp(5rem,10vw,8rem)] tj:min-[60rem]:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)]">
			<p class="tj-stamp tj:absolute tj:top-0 tj:left-0 tj:-translate-y-[62%]"><?php echo esc_html(Home::dashes($season->title()) . ' ' . $year); ?></p>
			<div class="tj-enter tj:min-w-0">
				<h1 id="hero-title" class="tj-h1"><?php echo esc_html(Home::heroTitle($season)); ?></h1>
				<p class="tj:mt-5 tj:mb-0 tj:max-w-136 tj:text-[clamp(1.05rem,1.5vw,1.25rem)] tj:leading-[1.55] tj:text-on-night-muted">
					<?php echo $ended
						? esc_html(sprintf('Léto %d je za námi. Termín dalšího tábora a otevření přihlášek oznámíme tady a na sociálních sítích.', $year))
						: 'Dva týdny her, výprav a kamarádství v Plátěné osadě v Českém ráji.'; ?>
				</p>
			</div>
			<?php // Phones: the buttons come before the facts, so the main action stays on the first screen. ?>
			<div class="tj-enter tj:flex tj:min-w-0 tj:flex-col tj:min-[60rem]:pt-2">
				<?php $part('facts', ['facts' => Home::facts($season, true), 'map' => $map['google']]); ?>
				<div class="tj:mt-7 tj:flex tj:flex-wrap tj:gap-3 tj:max-[60rem]:order-first tj:max-[60rem]:mt-0 tj:max-[60rem]:mb-8">
					<?php if ($registers || $debugCta) : ?>
						<a class="tj-btn <?php echo $debugCta ? 'tj-debug' : ''; ?>" href="<?php echo esc_url(Home::registrationUrl()); ?>"><?php echo esc_html(Home::registrationCta($season)); ?></a>
					<?php endif; ?>
					<?php if ($trailer) : ?>
						<a class="tj-btn <?php echo $registers || $debugCta ? 'tj-btn-quiet' : ''; ?>" href="<?php echo esc_url($youtubeUrl($trailer)); ?>" data-dialog-open="trailer">
							<svg class="tj:size-[.85rem] tj:flex-none" viewBox="0 0 12 12" aria-hidden="true"><path d="M2 1.2v9.6a.6.6 0 0 0 .92.5l7.5-4.8a.6.6 0 0 0 0-1L2.92.7A.6.6 0 0 0 2 1.2Z" fill="currentColor"/></svg>
							<?php echo esc_html(sprintf('Přehrát trailer %d', $trailer['year'])); ?>
						</a>
					<?php endif; ?>
					<?php if (! $registers && ! $debugCta) : ?><a class="tj-btn <?php echo $trailer ? 'tj-btn-quiet' : ''; ?>" href="#o-tabore">Poznat tábor</a><?php endif; ?>
				</div>
			</div>
		</div>
		<?php if ($trailer) : ?>
			<?php $part('dialog', [
				'id' => 'trailer',
				'meta' => 'Trailer ' . $trailer['year'],
				'title' => Home::dashes($trailer['title']),
				'close' => 'Zavřít trailer',
				'content' => static fn () => $player($trailer, sprintf('Trailer %d: %s', $trailer['year'], $trailer['title'])),
			]); ?>
		<?php endif; ?>
	</section>

	<?php $newsSection(); ?>

	<section class="tj-section tj:grid tj:items-center tj:gap-[clamp(2rem,6vw,5rem)] <?php echo $about['image'] ? 'tj:md:grid-cols-[minmax(0,1.1fr)_minmax(0,.9fr)]' : ''; ?>" aria-labelledby="about-heading">
		<div>
			<?php $part('section-heading', ['anchor' => 'o-tabore', 'id' => 'about-heading', 'title' => 'Tábor, který tvoříme společně']); ?>
			<div class="tj-prose tj:mt-6 tj:max-w-152"><?php echo $about['intro']; // phpcs:ignore WordPress.Security.EscapeOutput -- the_content output ?></div>
			<?php if ($about['rest']) : ?>
				<a class="tj-btn tj-btn-quiet tj:mt-6" href="#pribeh" data-dialog-open="pribeh">Přečíst celý příběh</a>
				<?php $part('dialog', [
					'id' => 'pribeh',
					'meta' => 'O táboře',
					'title' => 'Tábor, který tvoříme společně',
					'close' => 'Zavřít příběh',
					'content' => static function () use ($about): void {
						echo '<div class="tj-prose">' . $about['rest'] . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- the_content output
					},
				]); ?>
			<?php endif; ?>
		</div>
		<?php if ($about['image']) : ?>
			<div class="tj-flow tj:[&_figure]:m-0 tj:[&_figcaption]:hidden tj:[&_img]:block tj:[&_img]:h-auto tj:[&_img]:w-full tj:[&_img]:rotate-2 tj:[&_img]:rounded-2xl tj:[&_img]:border-8 tj:[&_img]:border-surface tj:[&_img]:shadow-lift" data-zoom><?php echo $about['image']; // phpcs:ignore WordPress.Security.EscapeOutput -- core image markup ?></div>
		<?php endif; ?>
	</section>

	<section class="tj-section tj-vivid tj:[--tj-band:var(--tj-color-band-ember)]" aria-labelledby="program-heading">
		<?php $part('section-heading', ['anchor' => 'program', 'id' => 'program-heading', 'title' => 'Co na táboře zažijeme', 'lead' => 'Dva týdny, kdy se pořád něco děje: od celotáborové hry po večerní táborák.']); ?>
		<?php if ($groups = Home::activityGroups()) : ?>
			<ul class="tj-flow tj:m-0 tj:mt-10 tj:grid tj:list-none tj:gap-x-12 tj:gap-y-10 tj:p-0 tj:md:grid-cols-2">
				<?php foreach ($groups as $group) : ?>
					<li class="tj:grid tj:min-w-0 tj:grid-cols-[minmax(0,2fr)_minmax(0,3fr)] tj:items-start tj:gap-5 tj:max-sm:grid-cols-[6.5rem_minmax(0,1fr)] tj:max-sm:gap-4">
						<a class="tj-frame tj:aspect-4/3 tj:max-sm:aspect-square" href="<?php echo esc_url(get_permalink($group['posts'][0])); ?>" tabindex="-1" aria-hidden="true"><?php echo $group['cover']; // phpcs:ignore WordPress.Security.EscapeOutput -- core image markup ?></a>
						<div class="tj:min-w-0">
							<h3 class="tj-h3"><?php echo esc_html($group['label']); ?></h3>
							<ul class="tj:m-0 tj:mt-2 tj:list-none tj:p-0">
								<?php foreach ($group['posts'] as $post) : ?>
									<li><a class="tj-link tj:inline-flex tj:min-h-[44px] tj:items-center tj:font-semibold" href="<?php echo esc_url(get_permalink($post)); ?>"><?php echo esc_html($plain(get_the_title($post))); ?></a></li>
								<?php endforeach; ?>
							</ul>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<p class="tj-lead">Program tábora brzy doplníme.</p>
		<?php endif; ?>
	</section>

	<section class="tj-section" aria-labelledby="people-heading">
		<?php $part('section-heading', ['anchor' => 'lide', 'id' => 'people-heading', 'title' => 'Lidé, kteří tábor tvoří', 'lead' => 'Vedoucí, kteří sami začínali jako táborové děti. Klikněte na kohokoli a přečtěte si, kdo to je.']); ?>
		<?php if ($people = Home::people()) : ?>
			<ul class="tj-flow tj-tilt tj:m-0 tj:mt-10 tj:grid tj:list-none tj:grid-cols-3 tj:gap-x-3 tj:gap-y-6 tj:p-0 tj:max-sm:text-[.85rem] tj:sm:gap-x-5 tj:sm:gap-y-8 tj:md:grid-cols-4 tj:lg:grid-cols-6">
				<?php foreach ($people as $post) :
					$person = Home::person($post);
					if ($person['initials'] === null) : ?>
						<li class="tj:grid tj:min-w-0 tj:place-items-center tj:rounded-2xl tj:border tj:border-dashed tj:border-ink/25 tj:p-3 tj:text-center tj:font-semibold tj:text-muted"><?php echo esc_html($person['name']); ?></li>
						<?php continue;
					endif; ?>
					<li class="tj:min-w-0">
						<a class="tj:group tj:block tj:text-inherit tj:no-underline" href="<?php echo esc_url(get_permalink($post)); ?>">
							<span class="tj-frame tj:grid tj:aspect-4/5 tj:place-items-center">
								<?php if (has_post_thumbnail($post)) : ?>
									<?php echo get_the_post_thumbnail($post, 'medium', ['loading' => 'lazy', 'alt' => '']); ?>
								<?php else : ?>
									<span class="tj:font-heading tj:text-[2rem] tj:font-extrabold tj:text-muted" aria-hidden="true"><?php echo esc_html($person['initials']); ?></span>
								<?php endif; ?>
							</span>
							<span class="tj:mt-[.6rem] tj:block tj:leading-[1.25] tj:font-bold tj:transition-colors tj:duration-150 tj:group-hover:text-link"><?php echo esc_html($person['name']); ?></span>
							<?php if ($person['role']) : ?><span class="tj:block tj:text-[.9rem] tj:leading-[1.35] tj:text-muted"><?php echo esc_html($person['role']); ?></span><?php endif; ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<p class="tj-lead">Představení týmu brzy doplníme.</p>
		<?php endif; ?>
	</section>

	<section class="tj-section tj-vivid tj:[--tj-band:var(--tj-color-band-sky)]" aria-labelledby="parents-heading">
		<?php $part('section-heading', ['anchor' => 'pro-rodice', 'id' => 'parents-heading', 'title' => 'Pro rodiče', 'lead' => 'Odpovědi na to, na co se ptáte nejčastěji, a dokumenty, které budete před odjezdem potřebovat.']); ?>
		<div class="tj-flow tj:mt-10 tj:grid tj:items-start tj:gap-x-16 tj:gap-y-12 tj:lg:grid-cols-[minmax(0,1.45fr)_minmax(0,1fr)]">
			<div data-exclusive-details>
				<h3 class="tj-h3 tj:mb-3 tj:text-[1.5rem]">Časté otázky</h3>
				<div class="tj-prose tj-faq tj:border-t tj:border-ink/14"><?php echo Home::faq(); // phpcs:ignore WordPress.Security.EscapeOutput -- the_content output ?></div>
			</div>
			<?php if ($documents['html'] !== '') : ?>
				<section class="tj:min-w-0 tj:scroll-mt-26 tj:lg:sticky tj:lg:top-26" id="dokumenty" aria-labelledby="documents-heading">
					<h3 id="documents-heading" class="tj-h3 tj:mb-3 tj:text-[1.5rem]">Dokumenty ke stažení</h3>
					<?php if ($documents['links'] !== []) : ?>
						<ul class="tj:m-0 tj:list-none tj:border-t tj:border-ink/14 tj:p-0">
							<?php foreach ($documents['links'] as $link) : ?>
								<li class="tj:border-b tj:border-ink/14 tj:[&>a]:flex tj:[&>a]:min-h-[56px] tj:[&>a]:items-center tj:[&>a]:gap-3 tj:[&>a]:py-3 tj:[&>a]:leading-[1.3] tj:[&>a]:font-bold tj:[&>a]:text-ink tj:[&>a]:no-underline tj:[&>a]:transition-colors tj:[&>a]:duration-150 tj:[&>a:hover]:text-link tj:[&>a]:before:flex-none tj:[&>a]:before:rounded-[.35rem] tj:[&>a]:before:bg-ink tj:[&>a]:before:px-[.45rem] tj:[&>a]:before:py-[.2rem] tj:[&>a]:before:text-[.7rem] tj:[&>a]:before:font-bold tj:[&>a]:before:tracking-[.04em] tj:[&>a]:before:text-paper tj:[&>a]:before:content-['PDF']"><?php echo $link; // phpcs:ignore WordPress.Security.EscapeOutput -- the_content output ?></li>
							<?php endforeach; ?>
						</ul>
						<?php if ($documents['notes'] !== '') : ?>
							<div class="tj-prose tj:mt-5 tj:text-[.95rem] tj:[&>p]:mt-0 tj:[&>p]:mb-3 tj:[&>p]:leading-[1.5] tj:[&>p:last-child]:mb-0"><?php echo $documents['notes']; // phpcs:ignore WordPress.Security.EscapeOutput -- the_content output ?></div>
						<?php endif; ?>
					<?php else : ?>
						<div class="tj-prose"><?php echo $documents['html']; // phpcs:ignore WordPress.Security.EscapeOutput -- the_content output ?></div>
					<?php endif; ?>
				</section>
			<?php endif; ?>
		</div>
	</section>

	<?php if ($pastSeasons = Home::pastSeasons($season)) : ?>
	<section class="tj-section tj:text-on-night tj:[--tj-band:var(--tj-color-night)] tj:[--tj-play:2.75rem]" aria-labelledby="archive-heading" data-post-content-url="<?php echo esc_url(Home::postContentUrl()); ?>">
		<?php $part('section-heading', ['anchor' => 'minule-rocniky', 'id' => 'archive-heading', 'title' => 'Minulé ročníky', 'lead' => 'Každý rok jiný příběh. Pusťte si trailer a přečtěte si, jak to tehdy dopadlo.']); ?>
		<div class="tj:relative tj:left-[calc(50%-50vw)] tj:mt-9 tj:w-screen">
		<button class="tj:absolute tj:top-1/2 tj:right-3 tj:z-1 tj:grid tj:size-11 tj:-translate-y-1/2 tj:cursor-pointer tj:place-items-center tj:rounded-full tj:border-0 tj:bg-night tj:text-on-night tj:shadow-[0_0_0_1.5px_var(--tj-color-on-night-muted)] tj:nojs:hidden tj:motion-reduce:hidden" type="button" data-ticker-toggle aria-pressed="false" aria-label="Zastavit běžící názvy ročníků">
			<span class="tj:flex tj:gap-[.25rem] tj:in-aria-pressed:hidden" aria-hidden="true"><span class="tj:h-[.9rem] tj:w-[.25rem] tj:rounded-[1px] tj:bg-current"></span><span class="tj:h-[.9rem] tj:w-[.25rem] tj:rounded-[1px] tj:bg-current"></span></span>
			<svg class="tj:hidden tj:size-[.9rem] tj:in-aria-pressed:block" viewBox="0 0 12 12" aria-hidden="true"><path d="M2 1.2v9.6a.6.6 0 0 0 .92.5l7.5-4.8a.6.6 0 0 0 0-1L2.92.7A.6.6 0 0 0 2 1.2Z" fill="currentColor"/></svg>
		</button>
		<div class="tj-ticker tj:font-season tj:text-[clamp(1.4rem,3vw,2.25rem)] tj:leading-none tj:text-sun tj:uppercase" aria-hidden="true">
			<?php foreach ([0, 1] as $copy) : ?>
				<ul>
					<?php foreach ($pastSeasons as $past) : ?>
						<li class="tj:flex tj:items-center tj:gap-6 tj:pr-6 tj:whitespace-nowrap"><?php echo esc_html(Home::dashes($past['title'])); ?><span class="tj:size-[.45em] tj:rounded-full tj:bg-ember"></span></li>
					<?php endforeach; ?>
				</ul>
			<?php endforeach; ?>
		</div>
		</div>
		<?php // Phones show the six latest seasons; the button reveals the rest and hides it again (a shared "#rocnik-…" link reveals it too). ?>
		<ul class="tj-flow tj-tilt tj:m-0 tj:mt-10 tj:grid tj:list-none tj:grid-cols-2 tj:gap-x-5 tj:gap-y-8 tj:p-0 tj:md:grid-cols-3 tj:lg:grid-cols-4" id="rocniky" data-seasons>
			<?php foreach ($pastSeasons as $index => $past) :
				$dialogId = 'rocnik-' . $past['year'];
				$title = Home::dashes($past['title']);
				?>
				<li class="tj:min-w-0 <?php echo $index >= 6 ? 'tj:max-md:[html.js_[data-seasons]:not(.is-all)_&]:hidden' : ''; ?>">
					<a class="tj:group tj:block tj:text-inherit tj:no-underline" href="#<?php echo esc_attr($dialogId); ?>" data-dialog-open="<?php echo esc_attr($dialogId); ?>">
						<span class="tj-frame tj:relative tj:aspect-video tj:bg-night tj:shadow-deep">
							<img src="<?php echo esc_url($youtubeThumbnail($past)); ?>" alt="" loading="lazy" width="480" height="270"<?php echo isset($past['thumbnailZoom']) ? ' style="scale:' . esc_attr((string) $past['thumbnailZoom']) . '"' : ''; ?>>
							<span class="play" aria-hidden="true"></span>
						</span>
						<span class="tj:mt-3 tj:block tj:text-[.9rem] tj:font-semibold tj:text-on-night-muted tj:tabular-nums"><?php echo esc_html((string) $past['year']); ?></span>
						<span class="tj:block tj:font-heading tj:text-[1.15rem] tj:leading-[1.2] tj:font-bold tj:underline tj:decoration-transparent tj:decoration-[1.5px] tj:underline-offset-[.22em] tj:transition-colors tj:duration-150 tj:group-hover:decoration-current"><?php echo esc_html($title); ?></span>
					</a>
					<?php $part('dialog', [
						'id' => $dialogId,
						'meta' => 'Ročník ' . $past['year'],
						'title' => $title,
						'close' => 'Zavřít ročník',
						'content' => static function () use ($past, $dialogId, $title, $player, $plain): void {
							$player($past, sprintf('Trailer tábora %d: %s', $past['year'], $title));
							if ($past['posts']) : ?>
								<h3 class="tj-h3 tj:mt-9 tj:mb-4">Články a fotografie</h3>
								<div class="tj:border-t tj:border-ink/14" data-exclusive-accordion>
									<?php foreach ($past['posts'] as $post) : ?>
										<details class="tj:group tj:scroll-mt-4 tj:border-b tj:border-ink/14" name="<?php echo esc_attr($dialogId . '-articles'); ?>">
											<summary class="tj:flex tj:min-h-[56px] tj:cursor-pointer tj:list-none tj:items-center tj:gap-4 tj:py-3 tj:transition-colors tj:duration-150 tj:hover:text-link tj:[&::-webkit-details-marker]:hidden tj:after:mr-[.35rem] tj:after:size-[.55rem] tj:after:flex-none tj:after:rotate-45 tj:after:border-r-2 tj:after:border-b-2 tj:after:border-current tj:after:transition-[rotate] tj:after:duration-200 tj:group-open:after:rotate-[-135deg]"><span class="tj:flex-1 tj:leading-[1.3] tj:font-bold"><?php echo esc_html($plain(get_the_title($post))); ?></span><span class="tj:text-[.85rem] tj:whitespace-nowrap tj:text-muted tj:tabular-nums"><?php echo esc_html(get_the_date('j. n. Y', $post)); ?></span></summary>
											<div class="tj-prose tj:pt-1 tj:pb-6 tj:text-[1rem]" data-article-body data-post-id="<?php echo esc_attr((string) $post->ID); ?>">
												<p class="tj:js:in-[[data-article-body]:not([data-error])]:hidden"><a href="<?php echo esc_url(get_permalink($post)); ?>">Přečíst článek</a></p>
											</div>
										</details>
									<?php endforeach; ?>
								</div>
							<?php endif;
						},
					]); ?>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php if (count($pastSeasons) > 6) : ?>
			<p class="tj:mt-8 tj:mb-0 tj:md:hidden tj:nojs:hidden"><button class="tj-btn tj-btn-quiet" type="button" data-seasons-all data-less="Zobrazit jen posledních 6" aria-controls="rocniky"><?php echo esc_html(sprintf('Zobrazit všech %d ročníků', count($pastSeasons))); ?></button></p>
		<?php endif; ?>
	</section>
	<?php endif; ?>

	<section class="tj-section tj-vivid tj:[--tj-band:var(--tj-color-band-grass)]" aria-labelledby="contact-heading">
		<?php $part('section-heading', ['anchor' => 'kontakt', 'id' => 'contact-heading', 'title' => 'Napište nám', 'lead' => 'Zeptejte se na cokoli kolem tábora. Odpověď pošleme na Váš e-mail.']); ?>
		<div class="tj-flow tj:mt-10 tj:grid tj:items-stretch tj:gap-[clamp(2rem,5vw,4rem)] tj:md:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)]">
			<?php $part('question-form', ['heading' => 'Pošlete nám dotaz', 'form_id' => 'home-question']); ?>
			<div class="tj:flex tj:min-w-0 tj:flex-col tj:gap-6">
				<div class="tj:grid tj:grid-cols-2 tj:gap-x-6 tj:gap-y-5 tj:leading-[1.55] tj:text-muted tj:max-[26rem]:grid-cols-1 tj:[&_a]:font-bold tj:[&_p]:m-0 tj:[&_strong]:text-[1.15rem] tj:[&_strong]:text-ink">
					<p><strong>Matěj Vašek</strong><br>hlavní vedoucí</p>
					<p><a class="tj-link" href="<?php echo esc_attr(Home::PHONE_URI); ?>"><?php echo esc_html(Home::PHONE); ?></a><br><a class="tj-link" href="mailto:<?php echo esc_attr($season->contactEmail()); ?>"><?php echo esc_html($season->contactEmail()); ?></a></p>
					<p><strong>Táborová jízda Žamberk</strong><br><?php echo Home::ADDRESS; // phpcs:ignore WordPress.Security.EscapeOutput -- constant markup ?></p>
					<p><strong>Tábořiště</strong><br>Plátěná osada<br>Splzov, Železný Brod</p>
				</div>
				<?php $part('social-links', ['class' => 'tj:flex-wrap', 'attributes' => ' aria-label="Sociální sítě"']); ?>
				<?php if ($map['embed']) : ?>
					<div class="tj:flex tj:flex-1 tj:flex-col">
						<iframe class="tj:block tj:min-h-64 tj:w-full tj:flex-1 tj:rounded-2xl tj:border tj:border-ink/10" src="<?php echo esc_url($map['embed']); ?>" title="<?php echo esc_attr('Mapa tábora: ' . ($season->location() ?? 'Táborová jízda')); ?>" loading="lazy" referrerpolicy="no-referrer"></iframe>
						<p class="tj:mt-3 tj:mb-0 tj:flex tj:flex-wrap tj:gap-x-6 tj:gap-y-1">
							<a class="tj-link" href="<?php echo esc_url($map['google']); ?>" target="_blank" rel="noopener noreferrer">Otevřít v Google Mapách<span class="tj:sr-only"> (nové okno)</span></a>
							<a class="tj-link" href="<?php echo esc_url($map['mapy']); ?>" target="_blank" rel="noopener noreferrer">Otevřít na Mapy.cz<span class="tj:sr-only"> (nové okno)</span></a>
						</p>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</section>
	<?php // Links to other pages of the site open here instead of leaving the homepage (initialisePageDialog). ?>
	<?php $part('dialog', [
		'id' => 'page-dialog',
		'meta' => '',
		'title' => '',
		'attributes' => ' data-page-dialog data-post-content-url="' . esc_url(Home::postContentUrl()) . '"',
		'content' => static function (): void {
			echo '<div data-page-dialog-body aria-live="polite"></div>';
		},
	]); ?>
</main>
<?php get_footer(); ?>
