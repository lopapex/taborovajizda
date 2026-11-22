<?php

declare(strict_types=1);

namespace TaborovaJizda\Theme;

/**
 * Turns plain phone numbers and e-mail addresses in post and page content into
 * tel: and mailto: links, so they open the phone or mail app. Text that is
 * already linked, and anything inside script, style or form elements, is left
 * untouched.
 */
final class ContactLinks
{
    private const SKIPPED_ELEMENTS = ['a', 'script', 'style', 'textarea', 'option', 'title', 'button'];

    public function register(): void
    {
        add_filter('the_content', [$this, 'filter'], 20);
    }

    public function filter(mixed $content): mixed
    {
        if (! is_string($content) || $content === '' || is_admin() || is_feed()) {
            return $content;
        }

        return self::linkify($content);
    }

    public static function linkify(string $html): string
    {
        $tokens = preg_split('/(<[^>]*>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($tokens === false) {
            return $html;
        }

        $skipDepth = [];
        foreach ($tokens as $index => $token) {
            if ($token === '') {
                continue;
            }
            if ($token[0] === '<') {
                if (preg_match('#^<(/?)([a-z0-9]+)\b#i', $token, $tag) === 1) {
                    $name = strtolower($tag[2]);
                    if (in_array($name, self::SKIPPED_ELEMENTS, true) && ! str_ends_with($token, '/>')) {
                        $skipDepth[$name] = ($skipDepth[$name] ?? 0) + ($tag[1] === '/' ? -1 : 1);
                    }
                }
                continue;
            }
            if (array_sum(array_map(static fn (int $depth): int => max(0, $depth), $skipDepth)) > 0) {
                continue;
            }
            $tokens[$index] = self::linkifyText($token);
        }

        return implode('', $tokens);
    }

    private static function linkifyText(string $text): string
    {
        $text = preg_replace_callback(
            '/[A-Za-z0-9._%+\-]+@[A-Za-z0-9\-]+(?:\.[A-Za-z0-9\-]+)*\.[A-Za-z]{2,}/',
            static fn (array $match): string => sprintf('<a href="mailto:%s">%s</a>', $match[0], $match[0]),
            $text
        ) ?? $text;

        // Czech numbers written as 123 456 789, optionally with +420 (spaces may be non-breaking).
        $separator = '(?:\s|\x{00A0}|&nbsp;)';
        $pattern = '/(?<![\d+\/\-])(\+420' . $separator . '?)?([2-9]\d{2})' . $separator . '(\d{3})' . $separator . '(\d{3})(?![\d\/\-])/u';

        return preg_replace_callback(
            $pattern,
            static fn (array $match): string => sprintf('<a href="tel:+420%s%s%s">%s</a>', $match[2], $match[3], $match[4], $match[0]),
            $text
        ) ?? $text;
    }
}
