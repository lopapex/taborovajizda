<?php
get_header();
?>
<?php
	    if ( have_posts() ) : while ( have_posts() ) : the_post();
		get_template_part('template-parts/components/post-summary', get_post_format());
	    endwhile; endif; 
?>
	</main>
<?php
	get_sidebar();
?>
<?php
get_footer();
?>
