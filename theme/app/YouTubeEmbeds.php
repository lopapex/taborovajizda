<?php

declare(strict_types=1);

namespace TaborovaJizda\Theme;

/**
 * Replaces YouTube oEmbed iframes (posts, pages and block widgets) with the
 * theme's click-to-load preview, so no YouTube iframe or script is loaded
 * until the visitor asks for the video. Without JavaScript the preview is a
 * plain link to the video on YouTube.
 */
final class YouTubeEmbeds
{
    public function register(): void
    {
        add_filter('embed_oembed_html', [$this, 'filter'], 10, 2);
    }

    public function filter(mixed $html, mixed $url): mixed
    {
        if (is_admin() || wp_is_json_request() || ! is_string($html) || ! is_string($url)) {
            return $html;
        }

        $id = self::videoId($html) ?? self::videoId($url);
        if ($id === null) {
            return $html;
        }

        $title = preg_match('/\stitle="([^"]*)"/', $html, $match) ? html_entity_decode($match[1], ENT_QUOTES) : 'YouTube video';

        return sprintf(
            '<div class="youtube-player youtube-player--box tj:bg-black" data-id="%1$s" data-title="%2$s"><a href="%3$s" target="_blank" rel="noopener noreferrer">%4$s</a></div>',
            esc_attr($id),
            esc_attr($title),
            esc_url('https://www.youtube.com/watch?v=' . rawurlencode($id)),
            esc_html(sprintf('Přehrát na YouTube: %s', $title))
        );
    }

    public static function videoId(string $value): ?string
    {
        $pattern = '~(?:youtube(?:-nocookie)?\.com/(?:embed/|watch\?(?:[^"\s]*&)?v=|shorts/)|youtu\.be/)([A-Za-z0-9_-]{11})~';
        return preg_match($pattern, $value, $match) ? $match[1] : null;
    }
}
