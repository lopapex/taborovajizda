 <?php

//require_once 'func.php';
?>
 <!DOCTYPE html>
 <html lang="cs">

 <head>
 	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
 	<meta http-equiv="X-UA-Compatible" content="IE=edge">
 	<meta name="viewport" content="width=device-width, initial-scale=1">
 	<meta name="author" content="https://www.martin-web.cz">
 	<meta name="keywords" content="tábor 2019,plátěná osada,tábor,letní tábor,žamberk,taborová jízda,zábava,táborové hry">
 	<meta name="facebook-domain-verification" content="k0jm5er9z71c32gqyg6hmjom8pm93r" />
 	<?php wp_head();?>
 	<script>
 		document.createElement('header');
 		document.createElement('section');
 		document.createElement('main');
 		document.createElement('article');
 		document.createElement('aside');
 		document.createElement('nav');
 		document.createElement('footer');
 	</script>
 </head>
 <?php

?>

 <body id="taborovajizda_body" class="taborovajizda">
 	<?php
// Sestava dialogů a informačních oken
require_once 'inc/dialogs.php';
?>
 	<div id="varovani_prohlizec">
 		Tento prohlížeč nepodporuje moderní standardy zobrazení, a proto stránka nemusí být (a pravděpodobně nebude) vykreslena správně.
 		Použijte raději nejnovější verzi některého z následujících prohlížečů:
 		<a href="https://www.mozilla.org/cs/firefox/">Firefox</a>,
 		<a href="https://www.google.com/chrome/">Chrome</a>,
 		<a href="https://www.microsoft.com/cs-cz/windows/microsoft-edge">Edge (pouze pro Windows 10)</a>.
 	</div>
 	<div id="page-background" class="page-background">

 	</div>
 	<div class="main-page">
 		<header class="zahlavi">
 			<div id="logo"><img src="<?php echo get_template_directory_uri(); ?>/web_images/logo_color.svg" alt="Táborová jízda Logo"></div>
 			<a id="call-to-action" href="/e-prihlaska/#menu">Chci se přihlásit</a>
 			<div class="camp-date">
 				15. 8. - 28. 8. 2026
 			</div>
 			<div class="camp-name">
 				Zombie Land — Splzov
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
 				<button onclick="this.classList.toggle('stlacen'); this.nextElementSibling.classList.toggle('viditelne'); this.blur();">&equiv;<span>
 						<?php echo $titulek; ?></span></button>
 				<?php
            wp_nav_menu(array(
                'theme_location' => 'menu_main_mobile',
                'walker' => new tj_menu_walker(),
                'url_after' => '#menu_mobile',
                'container' => ''));
?>
 			</div>
 		</nav>
 		<div class="main-content">
 			<main class="obsah">