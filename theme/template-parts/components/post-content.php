<?php
	$is_page = is_page();
	$is_single = is_single();
	$categories = get_the_category();
	$is_aktualita = false;
	foreach ($categories as $category) {
		($category->slug == 'aktuality') && $is_aktualita = true;
	}
?>
			<div class="blog_post">
    			<h2 class="blog_post_title">
<?php 
$post_title = $is_page||$is_single?get_the_title():'<a href="'.get_permalink().'">'.get_the_title().'</a>';
echo $post_title;
?>
				</h2>
    			<div class="blog_content">
					<?php 
						$is_aktualita&&the_post_thumbnail('thumbnail');
						the_content();
					?>
    			</div>
    			<?php if (!$is_page): ?>
    			<p class="blog_post_meta">Vloženo <?php the_time('j. F Y'); ?> </p>
    			<?php endif; ?>
    		</div>
