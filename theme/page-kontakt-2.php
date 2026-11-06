<?php get_header(); ?>
<article class="blog_post">
	<h2 class="tj-card-title">Kontakt (informace a přihlášky)</h2>
	<div class="blog_content">
		<?php $contactEmail = \TaborovaJizda\Theme\Season\Season::current()->contactEmail(); ?>
		<p><b>Martin Vojtěch</b> (hlavní vedoucí)<br>adresa: Pionýrů 1332, 56401 Žamberk<br>telefon: 605 947 103<br>e-mail: <a href="mailto:<?php echo esc_attr($contactEmail); ?>"><?php echo esc_html($contactEmail); ?></a><br>číslo účtu: 27-3094190207/0100<br>ič: 08063991<br></p>
	</div>
</article>
<article class="blog_post">
	<h2 class="tj-card-title">Adresa tábora</h2>
	<div class="blog_content">
		<p>Tábor Plátěná osada<br>Splzov<br>Železný Brod<br>468 22</p>
	</div>
</article>
<article class="blog_post">
	<h2 class="tj-card-title">Rychlý dotaz</h2>
	<div class="blog_content">
		<form id="dotaz" class="tj:flex tj:flex-wrap" action="<?php echo esc_url(home_url('/odeslat-dotaz/')); ?>" method="post">
			<input type="hidden" name="action" value="tj_contact_submit">
			<?php wp_nonce_field('tj_form_submit', 'tj_nonce'); ?>
			<label class="tj-contact-label" for="contact-jmeno">Vaše jméno:</label>
			<input class="tj-contact-control" type="text" id="contact-jmeno" name="jmeno" autocomplete="name" required>
			<label class="tj-contact-label" for="contact-email">E-mail pro odpověď:</label>
			<input class="tj-contact-control" type="email" id="contact-email" name="email" autocomplete="email" required>
			<label class="tj-contact-label" for="contact-dotaz">Dotaz:</label>
			<textarea class="tj-contact-control tj:h-40 tj:basis-[90%] tj:p-2" id="contact-dotaz" name="dotaz" required></textarea>
			<div class="tj:grow tj:basis-[70%]"></div>
			<input class="tj:mb-[.3em] tj:grow-0 tj:basis-auto" type="submit" value="odeslat">
		</form>
	</div>
</article>
</main>
<?php get_sidebar(); ?>
<?php get_footer(); ?>
