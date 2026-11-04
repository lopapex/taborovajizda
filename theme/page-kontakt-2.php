<?php get_header(); ?>
<article class="blog_post tj:mb-4 tj:pb-4 tj:text-left"><h2 class="tj:mt-0 tj:pl-[.6em] tj:pb-[.25em] tj:font-bold tj:tracking-[.08em] tj:text-white">Kontakt (informace a přihlášky)</h2><div class="blog_content tj:px-4 tj:pb-2"><p><b>Martin Vojtěch</b> (hlavní vedoucí)<br>adresa: Pionýrů 1332, 56401 Žamberk<br>telefon: 605 947 103<br>e-mail: info@taborovajizda.cz<br>číslo účtu: 27-3094190207/0100<br>ič: 08063991<br></p></div></article>
<article class="blog_post tj:mb-4 tj:pb-4 tj:text-left"><h2 class="tj:mt-0 tj:pl-[.6em] tj:pb-[.25em] tj:font-bold tj:tracking-[.08em] tj:text-white">Adresa tábora</h2><div class="blog_content tj:px-4 tj:pb-2"><p>Tábor Plátěná osada<br>Splzov<br>Železný Brod<br>468 22</p></div></article>
<article class="blog_post tj:mb-4 tj:pb-4 tj:text-left">
	<h2 class="tj:mt-0 tj:pl-[.6em] tj:pb-[.25em] tj:font-bold tj:tracking-[.08em] tj:text-white">Rychlý dotaz</h2>
	<div class="blog_content tj:px-4 tj:pb-2">
		<form id="dotaz" class="tj:flex tj:flex-wrap" action="<?php echo esc_url(home_url('/odeslat-dotaz/')); ?>" method="post">
			<input type="hidden" name="action" value="tj_contact_submit"><?php wp_nonce_field('tj_form_submit', 'tj_nonce'); ?>
			<label class="tj:mr-4 tj:mb-[.3em] tj:self-center tj:basis-[40%] tj:text-left tj:sm:basis-[20%] tj:sm:text-right" for="contact-jmeno">Vaše jméno:</label><input class="tj:mb-[.3em] tj:min-w-0 tj:max-w-[80vw] tj:grow tj:basis-[60%]" type="text" id="contact-jmeno" name="jmeno" autocomplete="name" required>
			<label class="tj:mr-4 tj:mb-[.3em] tj:self-center tj:basis-[40%] tj:text-left tj:sm:basis-[20%] tj:sm:text-right" for="contact-email">E-mail pro odpověď:</label><input class="tj:mb-[.3em] tj:min-w-0 tj:max-w-[80vw] tj:grow tj:basis-[60%]" type="email" id="contact-email" name="email" autocomplete="email" required>
			<label class="tj:mr-4 tj:mb-[.3em] tj:self-center tj:basis-[40%] tj:text-left tj:sm:basis-[20%] tj:sm:text-right" for="contact-dotaz">Dotaz:</label><textarea class="tj:mb-[.3em] tj:h-40 tj:min-w-0 tj:max-w-[80vw] tj:grow tj:basis-[90%] tj:p-2" id="contact-dotaz" name="dotaz" required></textarea>
			<div class="tj:grow tj:basis-[70%]"></div><input class="tj:mb-[.3em] tj:grow-0 tj:basis-auto" type="submit" value="odeslat">
		</form>
	</div>
</article>
</main>
<?php get_sidebar(); ?>
<?php get_footer(); ?>
