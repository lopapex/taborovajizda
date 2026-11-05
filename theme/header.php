<?php
$season = taborovajizda_season();
$locations = get_nav_menu_locations();
$mobileMenu = isset($locations['menu_main_mobile']) ? wp_get_nav_menu_object($locations['menu_main_mobile']) : null;
$mobileItems = $mobileMenu ? wp_get_nav_menu_items($mobileMenu) : [];
$currentItem = current(wp_filter_object_list($mobileItems ?: [], ['object_id' => get_queried_object_id()]));
$currentTitle = $currentItem ? $currentItem->title : 'domů';
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
<body id="taborovajizda_body" <?php body_class(['taborovajizda', 'season-' . $season->year(), 'tj:m-0', 'tj:min-h-screen']); ?>>
<?php wp_body_open(); ?>
<?php get_template_part('template-parts/notifications'); ?>
<div id="page-background" class="page-background tj:fixed tj:inset-0 tj:-z-10 tj:h-screen tj:w-screen tj:bg-cover tj:bg-center"></div>
<div class="main-page tj:mx-auto tj:mt-8 tj:flex tj:w-screen tj:flex-col tj:sm:w-[90vw]">
	<header class="zahlavi tj:relative tj:flex tj:h-80">
		<div id="logo"><img class="tj:absolute tj:left-[.3em] tj:top-[.3em] tj:h-12 tj:md:h-[8vw]" src="<?php echo esc_url(get_theme_file_uri('/web_images/logo_color.png')); ?>" alt="Logo Táborové jízdy"></div>
		<a id="call-to-action" class="tj:absolute tj:left-1/2 tj:top-[40%] tj:w-4/5 tj:-translate-x-1/2 tj:-translate-y-1/2 tj:cursor-pointer tj:rounded-[5px] tj:px-0 tj:py-[.4em] tj:text-center tj:text-[1.2rem] tj:font-bold tj:no-underline tj:md:top-1/2 tj:md:w-[12em] tj:md:text-2xl" href="<?php echo esc_url(home_url('/e-prihlaska/#menu')); ?>">Chci se přihlásit</a>
		<div class="camp-date tj:absolute tj:right-0 tj:top-0 tj:px-8 tj:py-4 tj:text-[2rem] tj:text-white tj:md:text-[2.5rem]">
			<?php echo esc_html($season->formattedDateRange()); ?>
		</div>
		<div class="camp-name tj:absolute tj:bottom-0 tj:right-0 tj:px-8 tj:py-4 tj:text-[2.5rem] tj:font-bold tj:text-white tj:md:text-5xl">
			<?php echo esc_html($season->title()); ?>
		</div>
	</header>
	<nav id="menu" class="desktop-menu ribbon tj:sticky tj:top-0 tj:z-[100] tj:mt-8 tj:hidden tj:h-[1em] tj:w-[90%] tj:overflow-visible tj:md:block">
		<?php wp_nav_menu([
			'theme_location' => 'menu_main',
			'container' => false,
			'menu_class' => 'tj:m-0 tj:flex tj:list-none tj:justify-evenly tj:p-0',
		]); ?>
	</nav>
	<nav id="menu_mobile" class="mobile-menu tj:relative tj:z-[100] tj:mt-8 tj:block tj:md:hidden">
		<div id="mobile_menu_container" class="tj:absolute tj:-top-4 tj:w-full" data-mobile-menu>
			<button class="tj:cursor-pointer tj:px-2 tj:pb-[.4em] tj:pt-[.2em] tj:text-2xl" type="button" data-menu-toggle aria-expanded="false" aria-controls="mobile-primary-menu">&equiv;<span> <?php echo esc_html($currentTitle); ?></span></button>
			<?php wp_nav_menu([
				'theme_location' => 'menu_main_mobile',
				'container' => false,
				'menu_id' => 'mobile-primary-menu',
				'menu_class' => 'tj:hidden tj:list-none tj:flex-wrap tj:m-0 tj:p-[.5vw] tj:text-center',
			]); ?>
		</div>
	</nav>
	<div class="main-content tj:flex tj:flex-row tj:flex-wrap tj:pt-[5vw]">
		<main class="obsah tj:block tj:w-[70vw] tj:grow tj:px-[3vw] tj:pb-4 tj:pt-4 tj:md:w-[60vw] tj:md:px-4 tj:md:pt-0">
