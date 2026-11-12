<?php
declare(strict_types=1);

use TaborovaJizda\Theme\Home;

$season = taborovajizda_season();
$links = array_filter([
    'Přihláška' => $season->acceptsRegistrations() ? home_url('/e-prihlaska/') : null,
    'Pro rodiče' => Home::anchor('pro-rodice'),
    'Dokumenty ke stažení' => Home::anchor('dokumenty'),
    'Minulé ročníky' => Home::anchor('minule-rocniky'),
    'Aktuality' => Home::anchor('aktuality'),
]);
$heading = 'tj:m-0 tj:mb-4 tj:[font-family:inherit] tj:text-[1.05rem] tj:tracking-[.04em] tj:text-on-night';
?>
<footer class="tj:relative tj:bg-night tj:text-on-night-muted tj:before:absolute tj:before:inset-x-0 tj:before:top-0 tj:before:h-[5px] tj:before:bg-(image:--tj-stripe-4) tj:[&_a]:text-on-night tj:[&_a]:decoration-sun/80 tj:[&_a]:underline-offset-[.2em] tj:[&_a:hover]:text-sun tj:[&_p]:m-0 tj:[&_p]:leading-[1.6] tj:[&_p+p]:mt-3">
	<div class="tj-container tj:grid tj:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)_minmax(0,1fr)] tj:gap-x-[clamp(1.5rem,4vw,3rem)] tj:gap-y-8 tj:pt-[clamp(2.5rem,6vw,4rem)] tj:pb-8 tj:max-md:grid-cols-2 tj:max-md:items-start">
		<div class="tj:grid tj:content-start tj:gap-4 tj:max-md:col-span-full">
			<a class="tj:block tj:w-[7.5rem] tj:transition-[rotate] tj:hover:-rotate-6" href="<?php echo esc_url(home_url('/')); ?>" aria-label="Táborová jízda, domů">
				<img class="tj:block tj:h-auto tj:w-full" src="<?php echo esc_url(get_theme_file_uri('/web_images/logo_color.png')); ?>" alt="Táborová jízda" width="900" height="636" loading="lazy">
			</a>
			<p class="tj:max-w-[30rem]">Letní tábory pro děti od roku 2014. Hry, výpravy a přátelství v Plátěné osadě u Jizery.</p>
			<?php get_template_part('template-parts/social-links', null, ['attributes' => ' aria-label="Sociální sítě"']); ?>
		</div>
		<nav aria-labelledby="footer-nav-heading">
			<h2 id="footer-nav-heading" class="<?php echo $heading; ?>">Rychlé odkazy</h2>
			<ul class="tj:m-0 tj:grid tj:list-none tj:gap-[.15rem] tj:p-0">
				<?php foreach ($links as $label => $url) : ?><li><a class="tj:inline-flex tj:min-h-[36px] tj:items-center tj:no-underline tj:hover:underline" href="<?php echo esc_url($url); ?>"><?php echo esc_html($label); ?></a></li><?php endforeach; ?>
			</ul>
		</nav>
		<div>
			<h2 class="<?php echo $heading; ?>">Kontakt</h2>
			<p><a href="<?php echo esc_attr(Home::PHONE_URI); ?>"><?php echo esc_html(str_replace(' ', "\u{00A0}", Home::PHONE)); ?></a><br><a href="mailto:<?php echo esc_attr($season->contactEmail()); ?>"><?php echo esc_html($season->contactEmail()); ?></a></p>
			<p><?php echo Home::ADDRESS; // phpcs:ignore WordPress.Security.EscapeOutput -- constant markup ?></p>
		</div>
	</div>
	<div class="tj-container tj:flex tj:flex-wrap tj:justify-between tj:gap-x-6 tj:gap-y-2 tj:border-t tj:border-ink/12 tj:pt-5 tj:pr-16 tj:pb-6 tj:text-[.9rem]">
		<p>© Táborová jízda Žamberk <?php echo esc_html((string) gmdate('Y')); ?></p>
		<a href="<?php echo esc_url(home_url('/ochrana-osobnich-udaju/')); ?>">Ochrana osobních údajů</a>
	</div>
</footer>
<a class="tj:fixed tj:right-4 tj:bottom-4 tj:z-15 tj:grid tj:size-12 tj:place-items-center tj:rounded-full tj:bg-sun tj:text-ink tj:opacity-0 tj:shadow-float tj:pointer-events-none tj:translate-y-4 tj:transition-[opacity,translate,background-color] tj:hover:-translate-y-[2px] tj:hover:bg-sun-light tj:[&.is-shown]:pointer-events-auto tj:[&.is-shown]:translate-y-0 tj:[&.is-shown]:opacity-100 tj:nojs:hidden" href="#main-content" data-to-top aria-label="Zpět nahoru"><span class="tj:mt-[.3rem] tj:size-3 tj:rotate-45 tj:rounded-[2px] tj:border-t-3 tj:border-l-3 tj:border-current" aria-hidden="true"></span></a>
<?php wp_footer(); ?>
</body>
</html>
