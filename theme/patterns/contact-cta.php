<?php
/**
 * Title: Kontaktujte nás
 * Slug: taborovajizda/contact-cta
 * Categories: call-to-action
 * Description: Jednoduchá výzva ke kontaktu bez ročníkových údajů.
 */
?>
<!-- wp:group {"align":"wide","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide">
    <!-- wp:heading -->
    <h2 class="wp-block-heading"><?php echo esc_html__('Potřebujete poradit?', 'taborovajizda'); ?></h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p><?php echo esc_html__('Napište nám a ozveme se s informacemi k táboru.', 'taborovajizda'); ?></p>
    <!-- /wp:paragraph -->

    <!-- wp:buttons -->
    <div class="wp-block-buttons">
        <!-- wp:button -->
        <div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url(home_url('/kontakt/')); ?>"><?php echo esc_html__('Kontakt', 'taborovajizda'); ?></a></div>
        <!-- /wp:button -->
    </div>
    <!-- /wp:buttons -->
</div>
<!-- /wp:group -->
