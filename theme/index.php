<?php
get_header();
while (have_posts()) {
	the_post();
	get_template_part('template-parts/post', null, ['summary' => false]);
}
if (is_front_page()) {
	get_template_part('template-parts/question-form', null, ['form_id' => 'homepage-question']);
}
?>
</main>
<?php get_sidebar(); ?>
<?php get_footer(); ?>
