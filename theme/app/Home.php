<?php

declare(strict_types=1);

namespace TaborovaJizda\Theme;

use TaborovaJizda\Theme\Season\Season;
use TaborovaJizda\Theme\Season\SeasonArchive;
use WP_Post;
use WP_Query;

/**
 * Data for the redesigned homepage and registration page. Templates only print
 * what these methods return.
 */
final class Home
{
    public const PHONE = '605 947 103';
    public const PHONE_URI = 'tel:+420605947103';
    public const ADDRESS = 'Pionýrů 1332<br>564 01 Žamberk';

    /** Activity posts grouped by slug; unknown slugs fall into "Další aktivity". */
    private const ACTIVITY_GROUPS = [
        'program-a-hra' => ['Program a hra', ['program', 'celotaborova-hra', 'taborova-klasika']],
        'vypravy-a-sport' => ['Výpravy a sport', ['celodenni-vylety', 'akcni-hry', 'horolezectvi', 'lode']],
        'tabornictvi' => ['Tábornictví', ['zalesactvi-a-tabornicke-dovednosti', 'zdravoveda', 'vareni', 'taborak']],
        'vecery-a-tvoreni' => ['Večery a tvoření', ['nocni-hry', 'diskoteka-a-dalsi-vecerni-sarady', 'vytvarka']],
    ];

    public static function registrationCta(Season $season): string
    {
        return $season->isFull() ? 'Přihlásit náhradníka' : 'Přihlásit dítě';
    }

    /**
     * Typographic dashes for display: "7-17 let" and "Zombie Land - Splzov" are
     * stored with hyphens. A number range gets a word joiner, so it never wraps.
     */
    public static function dashes(string $text): string
    {
        return preg_replace(['/ - /u', '/(?<=\d)-(?=\d)/u'], [' – ', "–\u{2060}"], $text) ?? $text;
    }

    /** The homepage h1 says what the site is; the season name lives in the hero stamp. */
    public static function heroTitle(Season $season): string
    {
        $age = $season->formattedAgeRange();

        return $age ? sprintf('Letní tábor pro děti %s u Jizery', self::dashes($age)) : 'Letní tábor pro děti u Jizery';
    }

    public static function registrationUrl(): string
    {
        return home_url('/e-prihlaska/#menu');
    }

    /** Link to a homepage section that also works from other pages. */
    public static function anchor(string $id): string
    {
        return (is_front_page() ? '' : home_url('/')) . '#' . $id;
    }

    /** @return array<string, string> Label => value. */
    public static function facts(Season $season, bool $withStatus): array
    {
        $ended = $withStatus && $season->hasEnded();
        $status = $season->registrationStatus();

        return array_filter([
            'Termín' => $season->formattedDateRange(),
            'Místo' => $season->location(),
            'Věk' => $season->formattedAgeRange(),
            'Cena' => $ended ? null : ($season->formattedPrice() ?? 'Upřesníme'),
            'Přihlášky' => ! $withStatus ? null : ($ended
                ? sprintf('Ročník %d proběhl, chystáme další', $season->year())
                : match (true) {
                    $season->isFull() && $status !== 'closed' => 'Obsazeno, přijímáme náhradníky',
                    $status === 'open' && $season->remainingPlaces() === 1 => 'Poslední volné místo',
                    $status === 'open' => 'Přihlášky jsou otevřené',
                    $status === 'coming-soon' => 'Přihlášky brzy otevřeme',
                    $status === 'closed' => 'Přihlášky jsou uzavřené',
                    default => null,
                }),
        ], static fn (?string $value): bool => $value !== null);
    }

    /** @return array{google: ?string, mapy: ?string, embed: ?string} */
    public static function map(Season $season): array
    {
        $coordinates = $season->mapCoordinates();
        $query = $coordinates ? $coordinates['lat'] . ',' . $coordinates['lon'] : $season->location();
        if (! $coordinates) {
            return ['google' => $query ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($query) : null, 'mapy' => null, 'embed' => null];
        }

        ['lat' => $lat, 'lon' => $lon] = $coordinates;

        return [
            'google' => 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($query),
            'mapy' => 'https://mapy.cz/zakladni?x=' . $lon . '&y=' . $lat . '&z=16&source=coor&id=' . rawurlencode($lon . ',' . $lat),
            'embed' => add_query_arg([
                'bbox' => implode(',', [$lon - 0.025, $lat - 0.012, $lon + 0.025, $lat + 0.012]),
                'layer' => 'mapnik',
                'marker' => $lat . ',' . $lon,
            ], 'https://www.openstreetmap.org/export/embed.html'),
        ];
    }

    public static function postContentUrl(): string
    {
        return add_query_arg('action', PostContentEndpoint::ACTION, admin_url('admin-ajax.php'));
    }

    /** Only public posts, even for logged-in editors who can read private ones. */
    private static function posts(string $category, array $query = []): array
    {
        return (new WP_Query($query + ['posts_per_page' => -1, 'category_name' => $category, 'post_status' => 'publish', 'ignore_sticky_posts' => true]))->posts;
    }

    /** @return int[] Posts marked "Stick to the top of the blog". */
    public static function pinnedIds(): array
    {
        return array_map('intval', (array) get_option('sticky_posts', []));
    }

    /** @return WP_Post[] This year's news, pinned posts first. */
    public static function news(int $year): array
    {
        $posts = self::posts('aktuality', ['date_query' => [['year' => $year]]]);
        $pinned = self::pinnedIds();
        usort($posts, static fn (WP_Post $a, WP_Post $b): int => (int) in_array($b->ID, $pinned, true) <=> (int) in_array($a->ID, $pinned, true));

        return $posts;
    }

    /** Featured image, or else the first real photo of the post itself (never emoji or icons). */
    public static function newsCardImage(WP_Post $post, string $content): string
    {
        if (has_post_thumbnail($post)) {
            return get_the_post_thumbnail($post, 'medium_large', ['loading' => 'lazy']);
        }
        preg_match_all('/<img\b[^>]*\bwp-image-(\d+)\b/i', $content, $images);
        foreach ($images[1] as $imageId) {
            $image = wp_get_attachment_image((int) $imageId, 'medium_large', false, ['loading' => 'lazy', 'alt' => '']);
            if ($image !== '') {
                return $image;
            }
        }

        return '';
    }

    /**
     * Leading paragraphs form the intro, the first image becomes the section
     * visual and everything else stays available under "Celý příběh tábora".
     *
     * @return array{intro: string, image: string, rest: string}
     */
    public static function about(?WP_Post $post): array
    {
        if (! $post instanceof WP_Post) {
            return ['intro' => '', 'image' => '', 'rest' => ''];
        }
        if (! has_blocks($post->post_content)) {
            return ['intro' => apply_filters('the_content', $post->post_content), 'image' => '', 'rest' => ''];
        }

        $intro = $rest = [];
        $image = null;
        foreach (parse_blocks($post->post_content) as $block) {
            if ($block['blockName'] === null && trim($block['innerHTML']) === '') {
                continue;
            }
            if ($image === null && $block['blockName'] === 'core/image') {
                $image = $block;
            } elseif ($image === null && $rest === [] && $block['blockName'] === 'core/paragraph' && count($intro) < 3) {
                $intro[] = $block;
            } else {
                $rest[] = $block;
            }
        }

        $render = static fn (array $blocks): string => $blocks ? apply_filters('the_content', serialize_blocks($blocks)) : '';
        $imageId = (int) ($image['attrs']['id'] ?? 0);

        return [
            'intro' => $render($intro),
            // The intro visual is large and unfloated, whatever size the editor picked.
            'image' => $imageId
                ? '<figure>' . wp_get_attachment_image($imageId, 'large', false, ['loading' => 'lazy', 'sizes' => '(max-width: 48rem) 100vw, 40vw']) . '</figure>'
                : ($image ? $render([$image]) : ''),
            'rest' => $render($rest),
        ];
    }

    /** @return WP_Post[] The head leader always opens the team list. */
    public static function people(): array
    {
        $posts = self::posts('lide');
        usort($posts, static fn (WP_Post $a, WP_Post $b): int => (int) str_contains($b->post_title, 'hlavní vedoucí') <=> (int) str_contains($a->post_title, 'hlavní vedoucí'));

        return $posts;
    }

    /**
     * Titles follow "Name (role)". Entries that are not a person (for example
     * "…a další") have no initials and stay plain text.
     *
     * @return array{name: string, role: string, initials: ?string}
     */
    public static function person(WP_Post $post): array
    {
        $name = html_entity_decode(wp_strip_all_tags(get_the_title($post)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $role = '';
        if (preg_match('/^(.*?)\s*\(([^)]+)\)\s*$/u', $name, $parts)) {
            [, $name, $role] = $parts;
        }
        $words = array_slice(preg_split('/\s+/u', trim(preg_replace('/[„“"].*?[„“"]/u', '', $name))), 0, 2);

        return [
            'name' => $name,
            'role' => $role,
            'initials' => preg_match('/^\p{L}/u', $name) ? mb_strtoupper(implode('', array_map(static fn (string $word): string => mb_substr($word, 0, 1), $words))) : null,
        ];
    }

    /** Profile text without its embedded portrait: the dialog shows the featured image instead. */
    public static function personContent(WP_Post $post): string
    {
        $content = apply_filters('the_content', $post->post_content);
        if (! has_post_thumbnail($post)) {
            return $content;
        }

        return preg_replace([
            '#<figure\b[^>]*>\s*(?:<a\b[^>]*>\s*)?<img\b[^>]*>(?:\s*</a>)?.*?</figure>#is',
            '#<p>\s*(?:<a\b[^>]*>\s*)?<img\b[^>]*>(?:\s*</a>)?\s*</p>#is',
            '#(?:<a\b[^>]*>\s*)?<img\b[^>]*>(?:\s*</a>)?#is',
        ], '', $content) ?? $content;
    }

    /**
     * One post or page as it is shown on its own page and in the homepage
     * dialog: a person gets the portrait beside the profile, other posts their
     * featured image when the text has no photo of its own.
     *
     * @return array{title: string, meta: string, html: string}
     */
    public static function article(WP_Post $post): array
    {
        $previous = $GLOBALS['post'] ?? null;
        $GLOBALS['post'] = $post;
        setup_postdata($post);
        $content = apply_filters('the_content', $post->post_content);
        wp_reset_postdata();
        $GLOBALS['post'] = $previous;

        $title = html_entity_decode(wp_strip_all_tags(get_the_title($post)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if ($post->post_type === 'post' && has_category('lide', $post)) {
            $person = self::person($post);
            $portrait = has_post_thumbnail($post)
                ? '<figure class="tj:m-0 tj:aspect-4/5 tj:overflow-hidden tj:rounded-2xl tj:bg-sand tj:max-sm:w-[min(70%,15rem)] tj:[&_img]:block tj:[&_img]:size-full tj:[&_img]:object-cover" data-zoom>' . get_the_post_thumbnail($post, 'medium_large', ['alt' => $person['name']]) . '</figure>'
                : '';

            return [
                'title' => $person['name'],
                'meta' => $person['role'] !== '' ? $person['role'] : 'Náš tým',
                'html' => '<div class="tj:grid tj:items-start tj:gap-6 tj:sm:grid-cols-[minmax(0,15rem)_minmax(0,1fr)] tj:[&>:only-child]:col-span-full">' . $portrait
                    . '<div class="tj-prose tj-person">' . self::personContent($post) . '</div></div>',
            ];
        }

        $lead = has_post_thumbnail($post) && ! str_contains($content, '<img')
            ? '<figure class="tj:m-0 tj:mb-6 tj:[&_img]:block tj:[&_img]:h-auto tj:[&_img]:w-full tj:[&_img]:rounded-2xl" data-zoom>' . get_the_post_thumbnail($post, 'large') . '</figure>'
            : '';
        $meta = match (true) {
            $post->post_type !== 'post' => '',
            has_category('taborove_aktivity', $post) => 'Program tábora',
            default => get_the_date('', $post),
        };

        return ['title' => $title, 'meta' => $meta, 'html' => $lead . '<div class="tj-prose">' . $content . '</div>'];
    }

    /** @return array<string, array{label: string, posts: WP_Post[], content: array<int, string>, cover: string}> */
    public static function activityGroups(): array
    {
        $grouped = [];
        foreach (self::posts('taborove_aktivity') as $post) {
            foreach (self::ACTIVITY_GROUPS as $key => [, $slugs]) {
                $position = array_search($post->post_name, $slugs, true);
                if ($position !== false) {
                    $grouped[$key][$position] = $post;
                    continue 2;
                }
            }
            $grouped['dalsi'][] = $post;
        }

        $groups = [];
        foreach (array_keys(self::ACTIVITY_GROUPS + ['dalsi' => null]) as $key) {
            if (empty($grouped[$key])) {
                continue;
            }
            ksort($grouped[$key]);
            $posts = array_values($grouped[$key]);
            $content = [];
            foreach ($posts as $post) {
                $content[$post->ID] = apply_filters('the_content', $post->post_content);
            }
            $groups[$key] = [
                'label' => self::ACTIVITY_GROUPS[$key][0] ?? 'Další aktivity',
                'posts' => $posts,
                'content' => $content,
                'cover' => self::activityCover($posts, $content),
            ];
        }

        return $groups;
    }

    /** First featured image of the group, or else the first photo of its (gallery) content. */
    private static function activityCover(array $posts, array $content): string
    {
        foreach ($posts as $post) {
            if (has_post_thumbnail($post)) {
                return get_the_post_thumbnail($post, 'medium_large', ['loading' => 'lazy', 'alt' => '']);
            }
        }
        foreach ($content as $html) {
            if (preg_match('/<img[^>]+src="([^"]+)"/i', $html, $match)) {
                $attachmentId = attachment_url_to_postid(preg_replace('/-\d+x\d+(?=\.[a-z]+$)/i', '', $match[1]));

                return ($attachmentId ? wp_get_attachment_image($attachmentId, 'medium_large', false, ['loading' => 'lazy', 'alt' => '']) : '')
                    ?: '<img src="' . esc_url($match[1]) . '" alt="" loading="lazy">';
            }
        }

        return '';
    }

    /** @return array<int, array{year: int, title: string, youtubeId: string, thumbnail: string, posts: WP_Post[]}> */
    public static function pastSeasons(Season $season): array
    {
        $seasons = array_filter(SeasonArchive::all(), static fn (array $archived): bool => $archived['year'] < $season->year());

        return array_values(array_map(static function (array $archived): array {
            $category = $archived['categorySlug'] ? get_category_by_slug($archived['categorySlug']) : null;
            $archived['posts'] = $category ? get_posts(['cat' => $category->term_id, 'numberposts' => -1, 'post_status' => 'publish']) : [];

            return $archived;
        }, $seasons));
    }

    /** FAQ questions end with an ellipsis ("Jak se přihlásit…"); the open/close marker already says there is more. */
    public static function faq(): string
    {
        $page = get_page_by_path('informace-pro-rodice');
        if (! $page instanceof WP_Post) {
            return '';
        }

        return preg_replace('/\s*(?:…|\.{3}|&hellip;|&#8230;)\s*(<\/summary>)/u', '$1', apply_filters('the_content', $page->post_content)) ?? '';
    }

    /**
     * The Dokumenty page split into download links (PDF by URL or by an "(pdf)"
     * label) and the rest of the page as notes.
     *
     * @return array{html: string, links: string[], notes: string}
     */
    public static function documents(): array
    {
        $page = get_page_by_path('dokumenty');
        if (! $page instanceof WP_Post) {
            return ['html' => '', 'links' => [], 'notes' => ''];
        }

        $links = [];
        $html = preg_replace_callback('/<a\b([^>]*)>(.*?)<\/a>/is', static function (array $link) use (&$links): string {
            $isDocument = preg_match('/href="[^"]+\.pdf(?:[?#][^"]*)?"/i', $link[1]) || stripos(wp_strip_all_tags($link[2]), '(pdf)') !== false;
            if (! $isDocument) {
                return $link[0];
            }
            // Shorter label: no "(pdf)" suffix; the instruction after a dash becomes a hint line.
            $label = preg_replace('/\s*\(pdf\)\s*$/i', '', $link[2]) ?? $link[2];
            $label = preg_replace_callback(
                '/(?:\s|&nbsp;)+(?:[–-]|&#821[12];|&ndash;)(?:\s|&nbsp;)+([^<]*)$/u',
                static fn (array $hint): string => '<span class="tj:text-[.8rem] tj:font-semibold tj:text-muted">' . trim($hint[1]) . '</span>',
                $label
            ) ?? $label;
            $links[] = '<a' . $link[1] . ' data-document><span class="tj:grid tj:gap-[.15rem]">' . $label . '</span></a>';

            return '<a' . $link[1] . ' data-document>' . $link[2] . '</a>';
        }, apply_filters('the_content', $page->post_content)) ?? '';

        // The rest of the page: list items without a download become paragraphs.
        $notes = preg_replace('/<li\b[^>]*>(?:(?!<\/li>).)*data-document(?:(?!<\/li>).)*<\/li>/is', '', $html) ?? '';
        $notes = preg_replace(['/<li\b[^>]*>/i', '/<\/li>/i', '/<\/?ul\b[^>]*>/i'], ['<p>', '</p>', ''], $notes) ?? '';

        return ['html' => $html, 'links' => $links, 'notes' => trim($notes)];
    }
}
