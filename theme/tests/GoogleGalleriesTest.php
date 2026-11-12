<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/GoogleGalleries.php';

use TaborovaJizda\Theme\GoogleGalleries;

$galleries = new GoogleGalleries();
$cases = [
    'single quotes (stage)' => [
        "<p>Text</p>\n<input type='button' value='Zobrazit galerii' class='photonic-show-gallery-button' data-photonic-shortcode='type=google&#038;style=default&#038;album_id=ABC'>",
        "<p>Text</p>\n",
    ],
    'double quotes' => [
        '<input type="button" value="Zobrazit galerii" class="photonic-show-gallery-button" data-photonic-shortcode="type=google&amp;view=photos">',
        '',
    ],
    'empty Google stream' => [
        "<div class=\"photonic-google-stream photonic-stream \" id=\"photonic-google-stream-1\">\n\n<!-- \n\tGoogle Photos is no longer supported due to an API deprecation by Google.\n-->\n\n</div>",
        '',
    ],
    'other Photonic source stays' => [
        "<input type='button' value='Zobrazit galerii' class='photonic-show-gallery-button' data-photonic-shortcode='type=flickr&#038;photoset_id=1'>",
        "<input type='button' value='Zobrazit galerii' class='photonic-show-gallery-button' data-photonic-shortcode='type=flickr&#038;photoset_id=1'>",
    ],
    'no Photonic' => ['<p>Bez galerie</p>', '<p>Bez galerie</p>'],
];

foreach ($cases as $label => [$input, $expected]) {
    $actual = $galleries->removeDeadGalleries($input);
    if ($actual !== $expected) {
        fwrite(STDERR, "$label: unexpected result\n" . var_export($actual, true) . "\n");
        exit(1);
    }
}

fwrite(STDOUT, "Google gallery tests passed.\n");
