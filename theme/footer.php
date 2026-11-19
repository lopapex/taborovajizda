<?php
declare(strict_types=1);

use TaborovaJizda\Theme\Home;

$season = taborovajizda_season();
$links = array_filter([
    'Přihláška' => $season->acceptsRegistrations() ? home_url('/e-prihlaska/') : null,
    'Pro rodiče' => Home::anchor('pro-rodice'),
    'Dokumenty ke stažení' => Home::anchor('dokumenty'),
    'Aktuality' => Home::anchor('aktuality'),
    'Minulé ročníky' => Home::anchor('minule-rocniky'),
]);
$heading = 'tj:m-0 tj:mb-3 tj:font-heading tj:text-[1.1rem] tj:font-bold tj:text-on-night';
?>
<footer class="tj:relative tj:z-1 tj:-mt-[clamp(1.5rem,4vw,3.25rem)] tj:rounded-t-[clamp(1.5rem,4vw,3.25rem)] tj:bg-night tj:text-on-night-muted tj:[&_a]:text-on-night tj:[&_a]:decoration-on-night/40 tj:[&_a]:underline-offset-[.2em] tj:[&_a:hover]:text-sun tj:[&_p]:m-0 tj:[&_p]:leading-[1.6] tj:[&_p+p]:mt-3">
	<div class="tj-container tj:grid tj:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)_minmax(0,1fr)] tj:gap-x-[clamp(1.5rem,4vw,3rem)] tj:gap-y-8 tj:pt-[clamp(2.5rem,6vw,4rem)] tj:pb-8 tj:max-md:grid-cols-2 tj:max-md:items-start">
		<div class="tj:grid tj:content-start tj:gap-4 tj:max-md:col-span-full">
			<a class="tj:block tj:w-28" href="<?php echo esc_url(home_url('/')); ?>" aria-label="Táborová jízda, domů">
				<img class="tj:block tj:h-auto tj:w-full" src="<?php echo esc_url(get_theme_file_uri('/web_images/logo_color_320.png')); ?>" alt="Táborová jízda" width="320" height="226" loading="lazy">
			</a>
			<p class="tj:max-w-120">Letní tábory pro děti od roku 2014. Hry, výpravy a kamarádství v Plátěné osadě u Jizery.</p>
			<?php get_template_part('template-parts/social-links', null, ['attributes' => ' aria-label="Sociální sítě"']); ?>
		</div>
		<nav aria-labelledby="footer-nav-heading">
			<h2 id="footer-nav-heading" class="<?php echo $heading; ?>">Rychlé odkazy</h2>
			<ul class="tj:m-0 tj:grid tj:list-none tj:gap-[.15rem] tj:p-0">
				<?php foreach ($links as $label => $url) : ?><li><a class="tj:inline-flex tj:min-h-8 tj:items-center tj:no-underline tj:hover:underline" href="<?php echo esc_url($url); ?>"><?php echo esc_html($label); ?></a></li><?php endforeach; ?>
			</ul>
		</nav>
		<div>
			<h2 class="<?php echo $heading; ?>">Kontakt</h2>
			<p><a href="<?php echo esc_attr(Home::PHONE_URI); ?>"><?php echo esc_html(str_replace(' ', "\u{00A0}", Home::PHONE)); ?></a><br><a href="mailto:<?php echo esc_attr($season->contactEmail()); ?>"><?php echo esc_html($season->contactEmail()); ?></a></p>
			<p><?php echo Home::ADDRESS; // phpcs:ignore WordPress.Security.EscapeOutput -- constant markup ?></p>
		</div>
	</div>
	<div class="tj-container tj:flex tj:flex-wrap tj:justify-between tj:gap-x-6 tj:gap-y-2 tj:border-t tj:border-on-night/15 tj:pt-5 tj:pr-16 tj:pb-6 tj:text-[.9rem]">
		<p>© Táborová jízda Žamberk <?php echo esc_html((string) gmdate('Y')); ?></p>
		<a href="<?php echo esc_url(home_url('/ochrana-osobnich-udaju/')); ?>">Ochrana osobních údajů</a>
	</div>
</footer>
<?php
// Social rail in the bottom left corner, paired with the back-to-top button on the right (that edge has the scrollbar); low, so it stays off the lines being read. A slim night tab with the logo stripe.
// The icons need no caption; a mouse or keyboard reveals the network name beside the rail. It slides away while the footer (with its own icons) is in view.
?>
<aside class="tj:fixed tj:left-0 tj:bottom-3 tj:z-15 tj:rounded-r-2xl tj:bg-night tj:py-1 tj:pl-1 tj:pr-[calc(.25rem+6px)] tj:shadow-[var(--tj-shadow-lift),0_0_0_1px_--alpha(var(--tj-color-on-night)/14%)] tj:transition-[translate,opacity,visibility] tj:duration-500 tj:ease-out tj:starting:-translate-x-full tj:starting:opacity-0 tj:motion-reduce:transition-[opacity,visibility] tj:before:absolute tj:before:inset-y-3 tj:before:right-[4px] tj:before:w-[3px] tj:before:rounded-full tj:before:bg-[linear-gradient(180deg,var(--tj-color-sun)_0_25%,var(--tj-color-ember)_25%_50%,var(--tj-color-sky)_50%_75%,var(--tj-color-grass)_75%)] tj:md:bottom-4 tj:[&.is-away]:invisible tj:[&.is-away]:-translate-x-full tj:[&.is-away]:opacity-0" data-social-rail aria-label="Sledujte nás">
	<ul class="tj:m-0 tj:grid tj:list-none tj:gap-0.5 tj:p-0">
		<?php foreach (taborovajizda_social_links() as $label => $url) : ?>
			<li><a class="tj:group tj:relative tj:grid tj:size-10 tj:place-items-center tj:rounded-[.65rem] tj:text-on-night! tj:no-underline tj:transition-[color,background-color,scale] tj:duration-150 tj:hover:bg-on-night/10 tj:hover:text-sun! tj:focus-visible:text-sun! tj:active:scale-92 tj:[&_svg]:size-5 tj:[&_svg]:fill-current" href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr($label); ?>"><?php echo taborovajizda_social_icon($label); // phpcs:ignore WordPress.Security.EscapeOutput -- core block SVG ?><span class="tj:pointer-events-none tj:absolute tj:top-1/2 tj:left-[calc(100%+1rem)] tj:-translate-x-1 tj:-translate-y-1/2 tj:rounded-md tj:bg-night tj:px-2.5 tj:py-1 tj:text-[.85rem] tj:font-semibold tj:whitespace-nowrap tj:text-on-night tj:opacity-0 tj:shadow-lift tj:transition-[opacity,translate] tj:duration-150 tj:ease-out tj:group-hover:translate-x-0 tj:group-hover:opacity-100 tj:group-focus-visible:translate-x-0 tj:group-focus-visible:opacity-100" aria-hidden="true"><?php echo esc_html($label); ?></span></a></li>
		<?php endforeach; ?>
	</ul>
</aside>
<a class="tj:fixed tj:right-3 tj:bottom-3 tj:z-15 tj:grid tj:size-10 tj:place-items-center tj:md:right-4 tj:md:bottom-4 tj:md:size-12 tj:rounded-full tj:bg-sun tj:text-night tj:opacity-0 tj:shadow-lift tj:pointer-events-none tj:translate-y-2 tj:transition-[opacity,translate,background-color] tj:duration-200 tj:ease-out tj:hover:bg-sun-light tj:active:scale-95 tj:[&.is-shown]:pointer-events-auto tj:[&.is-shown]:translate-y-0 tj:[&.is-shown]:opacity-100 tj:nojs:hidden" href="#main-content" data-to-top aria-label="Zpět nahoru"><span class="tj:mt-[.3rem] tj:size-3 tj:rotate-45 tj:rounded-[2px] tj:border-t-3 tj:border-l-3 tj:border-current" aria-hidden="true"></span></a>
<?php wp_footer(); ?>
</body>
</html>
