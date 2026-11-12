<?php
/**
 * Square brand-coloured social icons (contact section, footer).
 *
 * @var array{class?: string, attributes?: string} $args
 */
$colours = [
    'YouTube' => 'tj:bg-youtube',
    'Instagram' => 'tj:bg-linear-45/srgb tj:from-instagram-1 tj:via-instagram-2 tj:via-45% tj:to-instagram-3',
    'Facebook' => 'tj:bg-facebook',
];
?>
<ul class="tj:m-0 tj:flex tj:list-none tj:gap-[.6rem] tj:p-0 <?php echo esc_attr($args['class'] ?? ''); ?>"<?php echo $args['attributes'] ?? ''; // phpcs:ignore WordPress.Security.EscapeOutput -- static markup from the caller ?>>
	<?php foreach (taborovajizda_social_links() as $label => $url) : ?>
		<li><a class="tj:grid tj:size-[48px] tj:place-items-center tj:rounded-lg tj:text-white! tj:no-underline tj:transition tj:hover:-translate-y-[2px] tj:hover:brightness-112 tj:focus-visible:-translate-y-[2px] tj:focus-visible:brightness-112 <?php echo $colours[$label] ?? ''; ?>" href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr($label); ?>" title="<?php echo esc_attr($label); ?>"><span class="tj:grid tj:size-8 tj:place-items-center tj:[&_svg]:size-[1.6rem] tj:[&_svg]:fill-current" aria-hidden="true"><?php echo taborovajizda_social_icon($label); // phpcs:ignore WordPress.Security.EscapeOutput -- core block SVG ?></span></a></li>
	<?php endforeach; ?>
</ul>
