<?php
declare(strict_types=1);

$season = taborovajizda_season();
$homeAnchor = is_front_page() ? '' : home_url('/');
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<script>document.documentElement.classList.add('js');</script>
	<meta name="author" content="https://www.martin-web.cz">
	<meta name="facebook-domain-verification" content="k0jm5er9z71c32gqyg6hmjom8pm93r">
	<?php wp_head(); ?>
</head>
<body <?php body_class(['taborovajizda', 'season-' . $season->year(), 'tj-home']); ?>>
<?php wp_body_open(); ?>
<?php get_template_part('template-parts/notifications'); ?>
<a class="tj-home-skip-link" href="#main-content">Přeskočit na obsah</a>
<header class="tj-home-header">
	<div class="tj-home-header__inner">
		<a class="tj-home-logo" href="<?php echo esc_url(home_url('/')); ?>" aria-label="Táborová jízda, domů">
			<img src="<?php echo esc_url(get_theme_file_uri('/web_images/logo_color.png')); ?>" alt="Táborová jízda">
		</a>
		<nav class="tj-home-nav" aria-label="Hlavní navigace">
			<ul class="tj-home-nav__links">
				<li><a href="<?php echo esc_url($homeAnchor . '#aktuality'); ?>">Aktuality</a></li>
				<li><a href="<?php echo esc_url($homeAnchor . '#o-tabore'); ?>">O táboře</a></li>
				<li><a href="<?php echo esc_url($homeAnchor . '#lide'); ?>">Lidé</a></li>
				<li><a href="<?php echo esc_url($homeAnchor . '#program'); ?>">Program</a></li>
				<li><a href="<?php echo esc_url($homeAnchor . '#minule-rocniky'); ?>">Minulé ročníky</a></li>
				<li><a href="<?php echo esc_url($homeAnchor . '#pro-rodice'); ?>">Pro rodiče</a></li>
				<li><a href="<?php echo esc_url($homeAnchor . '#kontakt'); ?>">Kontakt</a></li>
			</ul>
		</nav>
		<div class="tj-home-mobile-menu" data-mobile-menu>
			<button class="tj-home-menu-button" type="button" data-menu-toggle aria-expanded="false" aria-controls="home-mobile-menu"><span>Menu</span><span class="tj-home-menu-icon" aria-hidden="true"></span></button>
			<ul id="home-mobile-menu" class="tj-home-nav-panel">
				<li><a href="<?php echo esc_url($homeAnchor . '#aktuality'); ?>">Aktuality</a></li>
				<li><a href="<?php echo esc_url($homeAnchor . '#o-tabore'); ?>">O táboře</a></li>
				<li><a href="<?php echo esc_url($homeAnchor . '#lide'); ?>">Lidé</a></li>
				<li><a href="<?php echo esc_url($homeAnchor . '#program'); ?>">Program</a></li>
				<li><a href="<?php echo esc_url($homeAnchor . '#minule-rocniky'); ?>">Minulé ročníky</a></li>
				<li><a href="<?php echo esc_url($homeAnchor . '#pro-rodice'); ?>">Pro rodiče</a></li>
				<li><a href="<?php echo esc_url($homeAnchor . '#kontakt'); ?>">Kontakt</a></li>
			</ul>
		</div>
		<?php if ($season->acceptsRegistrations()) : ?>
			<a class="tj-home-header-cta" href="<?php echo esc_url(home_url('/e-prihlaska/#menu')); ?>"><?php echo esc_html($season->isFull() ? 'Přihlásit náhradníka' : 'Přihlásit dítě'); ?></a>
		<?php elseif (taborovajizda_debug_registration_cta()) : ?>
			<a class="tj-home-header-cta tj-is-debug-cta" href="<?php echo esc_url(home_url('/e-prihlaska/#menu')); ?>" title="Ladicí zobrazení: jen v lokálním prostředí, ročník přihlášky nepřijímá"><?php echo esc_html($season->isFull() ? 'Přihlásit náhradníka' : 'Přihlásit dítě'); ?></a>
		<?php endif; ?>
	</div>
</header>
