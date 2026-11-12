<?php
get_header();
?>
<h1 class="tj:sr-only"><?php single_cat_title(); ?></h1>
<?php
while (have_posts()) {
	the_post();
	get_template_part('template-parts/post', null, ['summary' => true]);
}
?>
</main>
<?php get_sidebar(); ?>
<?php get_footer(); ?>
