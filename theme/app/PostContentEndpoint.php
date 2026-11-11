<?php

declare(strict_types=1);

namespace TaborovaJizda\Theme;

/**
 * Read-only admin-ajax endpoint that returns the rendered content of one
 * published, non-password-protected post. The homepage uses it to expand past
 * season articles inline without embedding every article in the page.
 */
final class PostContentEndpoint
{
    public const ACTION = 'tj_post_content';

    public function register(): void
    {
        add_action('wp_ajax_' . self::ACTION, [$this, 'respond']);
        add_action('wp_ajax_nopriv_' . self::ACTION, [$this, 'respond']);
    }

    public function respond(): void
    {
        $postId = isset($_GET['id']) ? absint(wp_unslash((string) $_GET['id'])) : 0;
        $post = $postId ? get_post($postId) : null;

        if (
            ! $post instanceof \WP_Post
            || $post->post_type !== 'post'
            || $post->post_status !== 'publish'
            || post_password_required($post)
        ) {
            wp_send_json_error(['message' => 'Článek není dostupný.'], 404);
        }

        $previous = $GLOBALS['post'] ?? null;
        $GLOBALS['post'] = $post;
        setup_postdata($post);
        $html = apply_filters('the_content', $post->post_content);
        wp_reset_postdata();
        $GLOBALS['post'] = $previous;

        header('Cache-Control: public, max-age=300');
        wp_send_json_success(['html' => $html]);
    }
}
