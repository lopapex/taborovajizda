<?php
get_header();
while (have_posts()) {
	the_post();
	get_template_part('template-parts/post', null, ['summary' => false]);
}
?>
</main>
<?php get_sidebar(); ?>
<?php get_footer(); ?>
