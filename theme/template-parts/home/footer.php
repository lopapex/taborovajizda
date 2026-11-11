<?php declare(strict_types=1); ?>
<footer class="tj-home-footer">
	<div class="tj-home-footer__inner">
		<a class="tj-home-footer__logo" href="<?php echo esc_url(home_url('/')); ?>">Táborová jízda</a>
		<p>© Táborová jízda Žamberk <?php echo esc_html((string) gmdate('Y')); ?></p>
		<a href="<?php echo esc_url(home_url('/#kontakt')); ?>">Kontakt</a>
		<a href="<?php echo esc_url(home_url('/ochrana-osobnich-udaju/')); ?>">Ochrana osobních údajů</a>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
