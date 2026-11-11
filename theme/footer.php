<?php if (is_front_page()) : ?>
	<?php get_template_part('template-parts/home/footer'); ?>
	<?php return; ?>
<?php endif; ?>
	</div>
	<footer class="zapati">
		<div class="tj:mx-auto tj:mb-4 tj:py-[.3em] tj:text-center">
			<?php echo esc_html(sprintf('© Táborová jízda Žamberk %d, webmaster: Martin Vojtěch, používáme', (int) gmdate('Y'))); ?>
			<a class="tj:text-white tj:underline" href="https://cs.wordpress.org/" target="_blank" rel="noopener noreferrer">WordPress&nbsp;<?php echo esc_html(get_bloginfo('version')); ?></a>
		</div>
	</footer>
</div>
<?php wp_footer(); ?>
</body>
</html>
