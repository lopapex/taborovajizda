<?php
declare(strict_types=1);

use TaborovaJizda\Theme\Home;

$season = taborovajizda_season();
$nav = ['aktuality' => 'Aktuality', 'o-tabore' => 'O táboře', 'program' => 'Program', 'lide' => 'Lidé', 'pro-rodice' => 'Pro rodiče', 'minule-rocniky' => 'Minulé ročníky', 'kontakt' => 'Kontakt'];
$navLink = 'tj:relative tj:inline-flex tj:min-h-[44px] tj:items-center tj:rounded-lg tj:px-[.7rem] tj:py-2 tj:text-[.95rem] tj:font-semibold tj:whitespace-nowrap tj:text-inherit tj:no-underline tj:transition-colors tj:duration-150 tj:hover:text-link tj:aria-current:text-link';
$cta = $season->acceptsRegistrations() || taborovajizda_debug_registration_cta();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#0f2133">
	<script>document.documentElement.classList.add('js');</script>
	<meta name="author" content="https://www.martin-web.cz">
	<meta name="facebook-domain-verification" content="k0jm5er9z71c32gqyg6hmjom8pm93r">
	<?php wp_head(); ?>
</head>
<body <?php body_class(['taborovajizda', 'season-' . $season->year()]); ?>>
<?php wp_body_open(); ?>
<a class="tj:fixed tj:top-3 tj:left-3 tj:z-200 tj:translate-y-[-200%] tj:rounded-lg tj:bg-sun tj:px-4 tj:py-3 tj:font-bold tj:text-night tj:focus:translate-y-0" href="#main-content">Přeskočit na obsah</a>
<header class="tj:sticky tj:top-0 tj:z-20 tj:bg-paper/94 tj:text-ink tj:shadow-header tj:backdrop-blur-md tj:transition-shadow tj:duration-200 tj:[&.is-scrolled]:shadow-header-scrolled tj:after:absolute tj:after:inset-x-0 tj:after:bottom-[-4px] tj:after:h-[4px] tj:after:bg-(image:--tj-stripe)" data-site-header>
	<div class="tj-container tj:flex tj:min-h-17 tj:items-center tj:gap-4 tj:max-[26rem]:gap-2">
		<a class="tj:my-[.3rem] tj:block tj:w-22 tj:flex-none tj:max-md:w-20" href="<?php echo esc_url(home_url('/')); ?>" aria-label="Táborová jízda, domů">
			<img class="tj:block tj:h-auto tj:w-full" src="<?php echo esc_url(get_theme_file_uri('/web_images/logo_color_320.png')); ?>" alt="Táborová jízda" width="320" height="226">
		</a>
		<nav class="tj:ml-auto tj:min-w-0 tj:max-md:hidden tj:compact:hidden" aria-label="Hlavní navigace">
			<ul class="tj:relative tj:m-0 tj:flex tj:list-none tj:flex-nowrap tj:gap-[.15rem] tj:p-0" data-nav="desktop">
				<?php foreach ($nav as $id => $label) : ?><li><a class="<?php echo $navLink; ?>" href="<?php echo esc_url(Home::anchor($id)); ?>"><?php echo esc_html($label); ?></a></li><?php endforeach; ?>
			</ul>
		</nav>
		<div class="tj:ml-auto tj:hidden tj:max-md:order-3 tj:max-md:block tj:compact:order-3 tj:compact:block <?php echo $cta ? 'tj:max-md:ml-2 tj:compact:ml-2' : ''; ?>" data-mobile-menu>
			<button class="tj:inline-flex tj:min-h-[44px] tj:cursor-pointer tj:items-center tj:gap-[.55rem] tj:rounded-lg tj:border tj:border-ink/25 tj:bg-transparent tj:px-[.85rem] tj:py-2 tj:font-semibold tj:text-inherit tj:nojs:hidden tj:max-[26rem]:w-[44px] tj:max-[26rem]:justify-center tj:max-[26rem]:p-2" type="button" data-menu-toggle aria-expanded="false" aria-controls="home-mobile-menu">
				<span class="tj:max-[26rem]:sr-only">Menu</span>
				<span class="tj:relative tj:block tj:h-[2px] tj:w-5 tj:bg-current tj:transition-[background-color] tj:duration-150 tj:before:absolute tj:before:left-0 tj:before:top-[-0.4rem] tj:before:h-[2px] tj:before:w-5 tj:before:bg-current tj:before:transition-[top,rotate] tj:before:duration-150 tj:after:absolute tj:after:left-0 tj:after:top-[.4rem] tj:after:h-[2px] tj:after:w-5 tj:after:bg-current tj:after:transition-[top,rotate] tj:after:duration-150 tj:menu-open:bg-transparent tj:menu-open:before:top-0 tj:menu-open:before:rotate-45 tj:menu-open:after:top-0 tj:menu-open:after:-rotate-45" aria-hidden="true"></span>
			</button>
			<ul id="home-mobile-menu" class="tj:absolute tj:inset-x-0 tj:top-full tj:m-0 tj:hidden tj:list-none tj:flex-col tj:border-b tj:border-ink/10 tj:bg-paper tj:px-4 tj:pt-3 tj:pb-4 tj:shadow-lift tj:transition-[opacity,translate] tj:duration-180 tj:ease-out tj:starting:-translate-y-2 tj:starting:opacity-0 tj:menu-open:block tj:nojs:static tj:nojs:flex tj:nojs:flex-row tj:nojs:flex-wrap tj:nojs:justify-end tj:nojs:gap-x-4 tj:nojs:gap-y-1 tj:nojs:bg-transparent tj:nojs:p-0 tj:nojs:shadow-none" data-nav="mobile">
				<?php foreach ($nav as $id => $label) : ?><li><a class="<?php echo $navLink; ?> tj:w-full tj:text-[1.1rem]" href="<?php echo esc_url(Home::anchor($id)); ?>"><?php echo esc_html($label); ?></a></li><?php endforeach; ?>
			</ul>
		</div>
		<?php if ($cta) : ?>
			<a class="tj-btn tj:min-h-[44px] tj:px-4 tj:py-2 tj:text-[.95rem] tj:max-md:order-2 tj:max-md:ml-auto tj:compact:order-2 tj:compact:ml-auto <?php echo $season->acceptsRegistrations() ? '' : 'tj-debug'; ?>" href="<?php echo esc_url(Home::registrationUrl()); ?>"<?php echo $season->acceptsRegistrations() ? '' : ' title="Ladicí zobrazení: jen mimo produkci, ročník přihlášky nepřijímá"'; ?>><?php echo esc_html(Home::registrationCta($season)); ?></a>
		<?php endif; ?>
	</div>
</header>
<?php get_template_part('template-parts/notifications'); ?>
