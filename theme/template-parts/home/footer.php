<?php declare(strict_types=1); ?>
<?php
$season = taborovajizda_season();
$homeAnchor = is_front_page() ? '' : home_url('/');
?>
<footer class="tj-home-footer">
	<div class="tj-home-footer__inner">
		<div class="tj-home-footer__brand">
			<a class="tj-home-footer__logo" href="<?php echo esc_url(home_url('/')); ?>" aria-label="Táborová jízda, domů">
				<img src="<?php echo esc_url(get_theme_file_uri('/web_images/logo_color.png')); ?>" alt="Táborová jízda" width="900" height="636" loading="lazy">
			</a>
			<p>Letní tábory pro děti od roku 2014. Hry, výpravy a přátelství v Plátěné osadě u Jizery.</p>
			<ul class="tj-home-footer__social" aria-label="Sociální sítě">
				<?php foreach (taborovajizda_social_links() as $socialLabel => $socialUrl) : ?>
					<li><a class="tj-home-social tj-home-social--<?php echo esc_attr(strtolower($socialLabel)); ?>" href="<?php echo esc_url($socialUrl); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr($socialLabel); ?>" title="<?php echo esc_attr($socialLabel); ?>"><span class="tj-home-social__icon" aria-hidden="true"><?php echo taborovajizda_social_icon($socialLabel); // phpcs:ignore WordPress.Security.EscapeOutput -- core block SVG ?></span></a></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<nav class="tj-home-footer__col" aria-labelledby="footer-nav-heading">
			<h2 id="footer-nav-heading">Rychlé odkazy</h2>
			<ul>
				<?php if ($season->acceptsRegistrations()) : ?><li><a href="<?php echo esc_url(home_url('/e-prihlaska/')); ?>">Přihláška</a></li><?php endif; ?>
				<li><a href="<?php echo esc_url($homeAnchor . '#pro-rodice'); ?>">Pro rodiče</a></li>
				<li><a href="<?php echo esc_url($homeAnchor . '#dokumenty'); ?>">Dokumenty ke stažení</a></li>
				<li><a href="<?php echo esc_url(is_front_page() ? '#minule-rocniky' : home_url('/minule-rocniky/')); ?>">Minulé ročníky</a></li>
				<li><a href="<?php echo esc_url(is_front_page() ? '#aktuality' : home_url('/category/aktuality/')); ?>">Aktuality</a></li>
			</ul>
		</nav>
		<div class="tj-home-footer__col">
			<h2>Kontakt</h2>
			<p><a href="tel:+420605947103">605&nbsp;947&nbsp;103</a><br><a href="mailto:<?php echo esc_attr($season->contactEmail()); ?>"><?php echo esc_html($season->contactEmail()); ?></a></p>
			<p>Pionýrů 1332<br>564 01 Žamberk</p>
		</div>
	</div>
	<div class="tj-home-footer__bottom">
		<p>© Táborová jízda Žamberk <?php echo esc_html((string) gmdate('Y')); ?></p>
		<a href="<?php echo esc_url(home_url('/ochrana-osobnich-udaju/')); ?>">Ochrana osobních údajů</a>
	</div>
</footer>
<a class="tj-home-to-top" href="#main-content" data-to-top aria-label="Zpět nahoru"><span aria-hidden="true"></span></a>
<?php wp_footer(); ?>
</body>
</html>
