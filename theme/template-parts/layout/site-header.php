<?php
$season = taborovajizda_season();
$seasonLogo = $season->asset('logo') ?: 'web_images/logo_color.svg';
?>
 <!DOCTYPE html>
 <html lang="cs">

 <head>
 	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
 	<meta http-equiv="X-UA-Compatible" content="IE=edge">
 	<meta name="viewport" content="width=device-width, initial-scale=1">
 	<meta name="author" content="https://www.martin-web.cz">
 	<meta name="keywords" content="tábor <?php echo esc_attr((string) $season->year()); ?>,plátěná osada,tábor,letní tábor,žamberk,táborová jízda,zábava,táborové hry">
 	<meta name="facebook-domain-verification" content="k0jm5er9z71c32gqyg6hmjom8pm93r" />
	<?php wp_head(); ?>
 </head>
 <?php

?>

 <body id="taborovajizda_body" <?php body_class(['taborovajizda', 'season-' . $season->year()]); ?>>
 	<?php wp_body_open(); ?>
 	<?php
// Sestava dialogů a informačních oken.
get_template_part('template-parts/components/dialogs');
?>
 	<div id="varovani_prohlizec">
		<?php echo esc_html__('Tento prohlížeč nepodporuje moderní standardy zobrazení, a proto stránka nemusí být vykreslena správně.', 'taborovajizda'); ?>
 		Použijte raději nejnovější verzi některého z následujících prohlížečů:
 		<a href="https://www.mozilla.org/cs/firefox/">Firefox</a>,
 		<a href="https://www.google.com/chrome/">Chrome</a>,
 		<a href="https://www.microsoft.com/cs-cz/windows/microsoft-edge">Edge (pouze pro Windows 10)</a>.
 	</div>
 	<div id="page-background" class="page-background">

 	</div>
 	<div class="main-page">
 		<header class="zahlavi">
 			<div id="logo"><img src="<?php echo esc_url(get_theme_file_uri('/' . ltrim($seasonLogo, '/'))); ?>" alt="<?php echo esc_attr__('Logo Táborové jízdy', 'taborovajizda'); ?>"></div>
			<a id="call-to-action" href="<?php echo esc_url(home_url('/e-prihlaska/#menu')); ?>"><?php echo esc_html__('Chci se přihlásit', 'taborovajizda'); ?></a>
 			<div class="camp-date">
 				<?php echo esc_html($season->formattedDateRange()); ?>
 			</div>
 			<div class="camp-name">
 				<?php echo esc_html($season->title()); ?>
 			</div>
 		</header>
 		<nav id="menu" class="ribbon">
 			<?php
        wp_nav_menu(array(
            'theme_location' => 'menu_main',
            'walker' => new tj_menu_walker(),
            'url_after' => '#menu',
            'container' => ''));
?>
 		</nav>
 		<nav id="menu_mobile">
 			<div id="mobile_menu_container">
 				<?php
            $menu_polozky = wp_get_nav_menu_items('Hlavní menu');
$aktualni_polozka = current(wp_filter_object_list($menu_polozky, array( 'object_id' => get_queried_object_id() )));
$titulek = empty($aktualni_polozka) ? "domů" : $aktualni_polozka->title;
?>
				<button type="button" data-menu-toggle aria-expanded="false" aria-controls="mobile-primary-menu">&equiv;<span>
 						<?php echo $titulek; ?></span></button>
 				<?php
            wp_nav_menu(array(
                'theme_location' => 'menu_main_mobile',
                'walker' => new tj_menu_walker(),
                'url_after' => '#menu_mobile',
                'menu_id' => 'mobile-primary-menu',
                'container' => ''));
?>
 			</div>
 		</nav>
 		<div class="main-content">
 			<main class="obsah">
