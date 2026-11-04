<?php
get_header();
while (have_posts()) {
	the_post();
	get_template_part('template-parts/post', null, ['summary' => true]);
}
?>
</main>
<?php get_sidebar(); ?>
<?php get_footer(); ?>
