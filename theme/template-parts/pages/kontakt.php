<?php
get_header();
?>
		<div class="blog_post">
			<h2>Kontakt (informace a přihlášky)</h2>
			<div class="blog_content">
				<p>
				<b>Martin Vojtěch</b> (hlavní vedoucí)<br>
				adresa: Pionýrů 1332, 56401 Žamberk<br>
				telefon: 605 947 103<br>
				e-mail: info@taborovajizda.cz<br>
				číslo účtu: 27-3094190207/0100<br>
				ič:  08063991<br>
				</p>
			</div>
		</div>
		<div class="blog_post">
			<h2>Adresa tábora</h2>
			<div class="blog_content">
				<p>
					Tábor Plátěná osada<br>
					Splzov<br>
					Železný Brod<br>
					468 22
				</p>
			</div>
		</div>
		<div class="blog_post">
			<h2>Rychlý dotaz</h2>
			<div class="blog_content">
				<form id="dotaz" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
					<input type="hidden" name="action" value="tj_contact_submit">
					<?php wp_nonce_field('tj_form_submit', 'tj_nonce'); ?>
					<label for="contact-jmeno">Vaše jméno:</label><input type="text" id="contact-jmeno" name="jmeno" autocomplete="name" required>
					<label for="contact-email">E-mail pro odpověď:</label><input type="email" id="contact-email" name="email" autocomplete="email" required>
					<label for="contact-dotaz">Dotaz:</label><textarea id="contact-dotaz" name="dotaz" required></textarea>
					<div class="volny_blok"></div>
					<input type="submit" value="odeslat">
				</form>
			</div>
		</div>
	</main>
<?php
	get_sidebar();
?>
<?php
get_footer();
?>
