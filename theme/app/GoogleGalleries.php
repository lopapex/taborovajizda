<?php

declare(strict_types=1);

namespace TaborovaJizda\Theme;

/**
 * Photonic galleries from Google Photos stopped working when Google closed the
 * Photos Library API to plugins: the "Zobrazit galerii" button loads only an
 * empty block ("Google Photos is no longer supported…"). Until the photos are
 * moved elsewhere, the dead button and the empty stream are left out of the
 * post content. Photonic galleries from other sources are untouched.
 */
final class GoogleGalleries
{
    public function register(): void
    {
        add_filter('the_content', [$this, 'removeDeadGalleries'], 20);
    }

    public function removeDeadGalleries(string $html): string
    {
        if (! str_contains($html, 'photonic')) {
            return $html;
        }

        return (string) preg_replace(
            [
                // Lazy "Zobrazit galerii" button of a Google Photos gallery.
                // Photonic writes the attributes with single or double quotes.
                '#<input\b[^>]*\bphotonic-show-gallery-button\b[^>]*\bdata-photonic-shortcode=(["\'])(?:(?!\1).)*\btype=google\b(?:(?!\1).)*\1[^>]*>#is',
                // Directly rendered Google Photos stream that only holds the deprecation comment.
                '#<div\b[^>]*\bphotonic-google-stream\b[^>]*>\s*(?:<!--(?:(?!-->).)*-->\s*)?</div>#is',
            ],
            '',
            $html
        );
    }
}
