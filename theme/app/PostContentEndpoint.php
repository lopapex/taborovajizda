<?php

declare(strict_types=1);

namespace TaborovaJizda\Theme;

/**
 * Read-only admin-ajax endpoint that returns the rendered content of one
 * published, non-password-protected post or page. The homepage uses it to
 * expand past season articles inline and to open internal links (for example
 * the packing list) in a dialog instead of leaving the page.
 *
 * Lookup by `id` (posts only, as before) or by `url` (posts and pages of this
 * site). An attachment page resolves to its file URL, so the caller can open
 * the document itself.
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
        $byUrl = isset($_GET['url']);
        $post = $byUrl
            ? $this->postFromUrl(esc_url_raw(wp_unslash((string) $_GET['url'])))
            : get_post(isset($_GET['id']) ? absint(wp_unslash((string) $_GET['id'])) : 0);

        if ($byUrl && $post instanceof \WP_Post && $post->post_type === 'attachment') {
            $parent = $post->post_parent ? get_post($post->post_parent) : null;
            $file = wp_get_attachment_url($post->ID);
            if ($file && (! $parent instanceof \WP_Post || $parent->post_status === 'publish')) {
                header('Cache-Control: public, max-age=300');
                wp_send_json_success(['file' => $file, 'title' => get_the_title($post)]);
            }
        }

        $allowedTypes = $byUrl ? ['post', 'page'] : ['post'];
        if (
            ! $post instanceof \WP_Post
            || ! in_array($post->post_type, $allowedTypes, true)
            || $post->post_status !== 'publish'
            || post_password_required($post)
        ) {
            wp_send_json_error(['message' => 'Obsah není dostupný.'], 404);
        }

        $previous = $GLOBALS['post'] ?? null;
        $GLOBALS['post'] = $post;
        setup_postdata($post);
        $html = apply_filters('the_content', $post->post_content);
        wp_reset_postdata();
        $GLOBALS['post'] = $previous;

        header('Cache-Control: public, max-age=300');
        wp_send_json_success([
            'html' => $html,
            'title' => html_entity_decode(get_the_title($post), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        ]);
    }

    /** Only URLs of this site; the path is looked up as a page, post or attachment slug (permalinks are /%postname%/). */
    private function postFromUrl(string $url): ?\WP_Post
    {
        if ($url === '' || wp_parse_url($url, PHP_URL_HOST) !== wp_parse_url(home_url(), PHP_URL_HOST)) {
            return null;
        }
        $postId = url_to_postid($url);
        if ($postId) {
            return get_post($postId);
        }
        $path = trim((string) wp_parse_url($url, PHP_URL_PATH), '/');
        $homePath = trim((string) wp_parse_url(home_url(), PHP_URL_PATH), '/');
        if ($homePath !== '' && str_starts_with($path, $homePath . '/')) {
            $path = substr($path, strlen($homePath) + 1);
        }
        if ($path === '') {
            return null;
        }
        $post = get_page_by_path($path, OBJECT, ['page', 'post', 'attachment']);

        return $post instanceof \WP_Post ? $post : null;
    }
}
