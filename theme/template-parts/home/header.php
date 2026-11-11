<?php
declare(strict_types=1);

$season = taborovajizda_season();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
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
				<li><a href="#o-tabore">O táboře</a></li>
				<li><a href="#aktuality">Aktuality</a></li>
				<li><a href="#pro-rodice" data-home-tab-link="panel-parents">Pro rodiče</a></li>
				<li><a href="#dokumenty" data-home-tab-link="panel-documents">Dokumenty</a></li>
				<li><a href="#minule-rocniky" data-home-tab-link="panel-archive">Minulé ročníky</a></li>
				<li><a href="<?php echo esc_url(home_url('/kontakt-2/')); ?>">Kontakt</a></li>
			</ul>
		</nav>
		<div class="tj-home-mobile-menu" data-mobile-menu>
			<button class="tj-home-menu-button" type="button" data-menu-toggle aria-expanded="false" aria-controls="home-mobile-menu"><span>Menu</span><span class="tj-home-menu-icon" aria-hidden="true"></span></button>
			<ul id="home-mobile-menu" class="tj-home-nav-panel">
				<li><a href="#o-tabore">O táboře</a></li>
				<li><a href="#aktuality">Aktuality</a></li>
				<li><a href="#pro-rodice" data-home-tab-link="panel-parents">Pro rodiče</a></li>
				<li><a href="#dokumenty" data-home-tab-link="panel-documents">Dokumenty</a></li>
				<li><a href="#minule-rocniky" data-home-tab-link="panel-archive">Minulé ročníky</a></li>
				<li><a href="<?php echo esc_url(home_url('/kontakt-2/')); ?>">Kontakt</a></li>
			</ul>
		</div>
		<a class="tj-home-header-cta" href="<?php echo esc_url(home_url('/e-prihlaska/#menu')); ?>">Přihláška</a>
	</div>
</header>
