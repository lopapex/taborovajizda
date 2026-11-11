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
<?php get_template_part('template-parts/question-form', null, ['heading' => 'Rychlý dotaz', 'form_id' => 'contact-question']); ?>
</main>
<?php get_sidebar(); ?>
<?php get_footer(); ?>
