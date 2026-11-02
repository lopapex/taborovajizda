<?php

function _wp_fix_issue_25449($upload_dir)
{
    if (is_ssl()) {
        $upload_dir['url']     = set_url_scheme($upload_dir['url'], 'https');
        $upload_dir['baseurl'] = set_url_scheme($upload_dir['baseurl'], 'https');
    } else {
        $upload_dir['url']     = set_url_scheme($upload_dir['url'], 'http');
        $upload_dir['baseurl'] = set_url_scheme($upload_dir['baseurl'], 'http');
    }
    return $upload_dir;
}
add_filter('upload_dir', '_wp_fix_issue_25449');

function taborovajizda_the_content($content)
{
    //print_log($content);
    // Check if we're inside the main loop in a single post page.
    if (in_the_loop() && is_main_query()) {
        return taborovajizda_make_content_images_responsive($content);
    }

    return $content;
}
add_filter('the_content', 'taborovajizda_the_content', 1000, 1);

/**
 * Filters 'img' elements in post content to add 'srcset' and 'sizes' attributes.
 *
 * @since 4.4.0
 *
 * @see wp_image_add_srcset_and_sizes()
 *
 * @param string $content The raw post content to be filtered.
 * @return string Converted content with 'srcset' and 'sizes' attributes added to images.
 */
function taborovajizda_make_content_images_responsive($content)
{
    if (! preg_match_all('/<img [^>]+>/', $content, $matches)) {
        return $content;
    }
    // print_log($matches);
    $selected_images = $attachment_ids = array();

    foreach ($matches[0] as $image) {
        if (preg_match('/envira-gallery-image-([0-9]+)/i', $image, $class_id) && ($attachment_id = absint($class_id[1]))) {
            // print_log($class_id);
            /*
             * If exactly the same image tag is used more than once, overwrite it.
             * All identical tags will be replaced later with 'str_replace()'.
             */
            $selected_images[ $image ] = $attachment_id;
            // Overwrite the ID when the same image is included more than once.
            $attachment_ids[ $attachment_id ] = true;
        }
    }

    if (count($attachment_ids) > 1) {
        /*
         * Warm the object cache with post and meta information for all found
         * images to avoid making individual database calls.
         */
        _prime_post_caches(array_keys($attachment_ids), false, true);
    }
    //print_log($selected_images);
    foreach ($selected_images as $image => $attachment_id) {
        $image_meta = wp_get_attachment_metadata($attachment_id);
        $content = str_replace($image, taborovajizda_image_add_srcset_and_sizes($image, $image_meta, $attachment_id), $content);
    }

    return $content;
}


/**
 * Adds 'srcset' and 'sizes' attributes to an existing 'img' element.
 *
 * @since 4.4.0
 *
 * @see wp_calculate_image_srcset()
 * @see wp_calculate_image_sizes()
 *
 * @param string $image         An HTML 'img' element to be filtered.
 * @param array  $image_meta    The image meta data as returned by 'wp_get_attachment_metadata()'.
 * @param int    $attachment_id Image attachment ID.
 * @return string Converted 'img' element with 'srcset' and 'sizes' attributes added.
 */

function taborovajizda_image_add_srcset_and_sizes($image, $image_meta, $attachment_id)
{
    //print_log($image); // !DEBUG!
    //print_log($image_meta);
    // Ensure the image meta exists.
    if (empty($image_meta['sizes'])) {
        return $image;
    }
    $image_src = preg_match('/src="([^"]+)"/', $image, $match_src) ? $match_src[1] : '';
    list($image_src) = explode('?', $image_src);
    // Return early if we couldn't get the image source.
    if (!$image_src) {
        return $image;
    }
    // Bail early if an image has been inserted and later edited.
    if (preg_match('/-e[0-9]{13}/', $image_meta['file'], $img_edit_hash) && strpos(wp_basename($image_src), $img_edit_hash[0]) === false) {
        return $image;
    }
    $base_url = trailingslashit(wp_upload_dir()['baseurl']);
    $image_base_url = $base_url;
    $dirname = dirname($image_meta['file']);
    if ($dirname !== '.') {
        $image_base_url .= trailingslashit($dirname);
    }
    $all_sizes = wp_list_pluck($image_meta['sizes'], 'file');
    foreach ($all_sizes as $key => $file) {
        $all_sizes[$key] = $image_base_url . $file;
    }
    // Add the original image.
    $all_sizes[] = $base_url . $image_meta['file'];
    // Bail early if the image src doesn't match any of the known image sizes.
    if (!in_array($image_src, $all_sizes)) {
        return $image;
    }
    $width = preg_match('/ width="([0-9]+)"/', $image, $match_width) ? (int) $match_width[1] : 0;
    $height = preg_match('/ height="([0-9]+)"/', $image, $match_height) ? (int) $match_height[1] : 0;
    if (!$width || !$height) {
        // If attempts to parse the size value failed, attempt to use the image meta data to match
        // the image file name from 'src' against the available sizes for an attachment.

        $image_filename = wp_basename($image_src);
        if ($image_filename === wp_basename($image_meta['file'])) {
            $width = (int) $image_meta['width'];
            $height = (int) $image_meta['height'];
        } else {
            foreach ($image_meta['sizes'] as $image_size_data) {
                if ($image_filename === $image_size_data['file']) {
                    $width = (int) $image_size_data['width'];
                    $height = (int) $image_size_data['height'];
                    break;
                }
            }
        }
    }

    if (!$width || !$height) {
        return $image;
    }
    $size_array = array($width, $height);
    $srcset = wp_calculate_image_srcset($size_array, $image_src, $image_meta, $attachment_id);
    if ($srcset) {
        $sizes = taborovajizda_calculate_image_sizes("", $size_array, $image_src, $image_meta, $attachment_id);
        /*
        $sizes = 	'(max-width: 150px) 100vw, '
                    .'(max-width: 300px) 100vw, '
                    .'(max-width: 560px) 100vw, '
                    .'(max-width: 768px) 90vw, '
                    .'(max-width: 1024px) '.(100*$width/768).'vw, '
                    .'(max-width: 1600px) '.(100*$width/1024).'vw, '
                    .'(max-width: 1920px) '.(100*$width/1600).'vw, '
                    .'(max-width: 2200px) '.(100*$width/1920).'vw, '
                    .'(max-width: 2500px) '.(100*$width/2200).'vw, '
                    .'(max-width: 2800px) '.(100*$width/2500).'vw, '
                    .'(max-width: 3100px) '.(100*$width/2800).'vw, '
                    .''.(100*$width/3100).'vw';
        */
        /*
        // Check if there is already a 'sizes' attribute.
        $sizes = strpos($image, ' sizes=');
        if (!$sizes) {
            $sizes = wp_calculate_image_sizes($size_array, $image_src, $image_meta, $attachment_id);
        }
        */
    }

    if ($srcset && $sizes) {
        $image = preg_replace('/\sdata-envira-srcset=\"([^"]*)\"/', ' ', $image);
        $image = preg_replace('/\ssrcset=\"([^"]*)\"/', ' ', $image);
        //$image = preg_replace('/\ssrc=\"([^"]*)\"/', ' src=""', $image);
        //$image = preg_replace('/\sdata-envira-src=\"([^"]*)\"/', ' data-envira-src=""', $image);
        //$image = preg_replace('/\sdata-safe-src=\"([^"]*)\"/', ' data-safe-src=""', $image);
        // Format the 'srcset' and 'sizes' string and escape attributes.
        $attr = "";
        $attr = sprintf(' data-envira-srcset="%s"', esc_attr($srcset));
        $attr .= sprintf(' data-src="%s"', esc_attr($image_base_url.wp_basename($image_meta['file'])));
        //$attr .= sprintf(' data-envira-src="%s"', esc_attr($image_base_url.wp_basename($image_meta['file'])));
        //$attr .= sprintf(' data-safe-src="%s"', esc_attr($image_base_url.wp_basename($image_meta['file'])));
        $attr .= sprintf(' srcset="%s"', esc_attr($srcset));
        if (is_string($sizes)) {
            $attr .= sprintf(' sizes="%s"', esc_attr($sizes));
            $attr .= sprintf(' data-envira-sizes="%s"', esc_attr($sizes));
        }
        // Add 'srcset' and 'sizes' attributes to the image markup.
        $image = preg_replace('/<img ([^>]+?)[\\/ ]*>/', '<a href="'.$image_base_url.wp_basename($image_meta['file']).'"><img $1' . $attr . ' /></a>', $image);
    }
    return $image;
}

function taborovajizda_calculate_image_sizes($sizes, $size_array, $image_src, $image_meta, $attachment_id)
{
    //print_log($image_meta);
    $width = $size_array[0];
    $sizes = 	'(max-width: 150px) 100vw, '
                .'(max-width: 300px) 100vw, '
                .'(max-width: 560px) 100vw, '
                .'(max-width: 768px) 90vw, '
                .'(max-width: 1024px) '.(100*$width/768).'vw, '
                .'(max-width: 1600px) '.(100*$width/1024).'vw, '
                .'(max-width: 1920px) '.(100*$width/1600).'vw, '
                .'(max-width: 2200px) '.(100*$width/1920).'vw, '
                .'(max-width: 2500px) '.(100*$width/2200).'vw, '
                .'(max-width: 2800px) '.(100*$width/2500).'vw, '
                .'(max-width: 3100px) '.(100*$width/2800).'vw, '
                .''.(100*$width/3100).'vw';
    if ('post' === get_post_type()) {
    }
    return $sizes;
}
add_filter('wp_calculate_image_sizes', 'taborovajizda_calculate_image_sizes', 10, 5);


// Vlastní menu walker. Zatím jen řeší přidání #menu k položkám (overkill?)
class tj_menu_walker extends Walker_Nav_Menu
{
    public function start_el(&$output, $item, $depth=0, $args=array(), $id = 0)
    {
        //echo "<pre>"; print_r($args->url_after); echo "</pre>";exit;
        $object = $item->object;
        $type = $item->type;
        $title = $item->title;
        $description = $item->description;
        $permalink = $item->url;
        $ul_script = "this.parentElement.classList.toggle('viditelne'); this.parentElement.setAttribute('onclick', 'this.setAttribute(\'onclick\', this.getAttribute(\'data-onclick\'));'); return true;";
        $script = "this.classList.toggle('viditelne'); this.getElementsByTagName('ul')[0].setAttribute('onclick', this.getAttribute('data-ul-onclick')); this.setAttribute('data-onclick',this.getAttribute('onclick')); this.setAttribute('onclick','return true;'); return false;";
        $data_ul_onclick = ($args->url_after=='#menu_mobile')&&in_array('menu-item-has-children', $item->classes) ? ' data-ul-onclick="'.$ul_script.'"' : '';
        $onclick = ($args->url_after=='#menu_mobile')&&in_array('menu-item-has-children', $item->classes) ? ' onclick="'.$script.'"' : '';
        $output .= "<li class='".implode(" ", $item->classes)."'$data_ul_onclick$onclick>";
        //print_log($item);
        //Add SPAN if no Permalink
        if ($permalink && $permalink != '#') {
            $output .= '<a href="'.$permalink.$args->url_after.'">';
        } else {
            $output .= '<span>';
        }

        $output .= $title;

        if ($permalink && $permalink != '#') {
            $output .= '</a>';
        } else {
            $output .= '</span>';
        }
    }
}
/*
this.classList.toggle('viditelne');
this.getElementsByTagName('ul')[0].setAttribute('onclick','this.parentElement.classList.toggle('viditelne');this.parentElementsetAttribute('onclick','this.classList.toggle('viditelne'); this.getElementsByTagName('ul')[0].setAttribute('onclick','this.parentElement.classList.toggle('viditelne');'); this.setAttribute('onclick','return true;'); return false;');');
this.setAttribute('onclick','return true;');
return false;

this.classList.toggle('viditelne'); this.getElementsByTagName('ul')[0].setAttribute('onclick','this.parentElement.classList.toggle('viditelne');'); this.setAttribute('onclick','return true;'); return false;
*/
if (! function_exists('taborovajizda_setup')) :
    /**
     * Sets up theme defaults and registers support for various WordPress features.
     *
     * Note that this function is hooked into the after_setup_theme hook, which runs
     * before the init hook. The init hook is too late for some features, such as indicating
     * support post thumbnails.
     */
    function taborovajizda_setup()
    {
        /**
         * Make theme available for translation.
         * Translations can be placed in the /languages/ directory.
         */
        load_theme_textdomain('taborovajizda', get_template_directory() . '/languages');

        /**
         * Add default posts and comments RSS feed links to <head>.
         */
        add_theme_support('automatic-feed-links');

        /**
         * Enable support for post thumbnails and featured images.
         */
        add_theme_support('post-thumbnails');

        // Allow wide img alignment in gutenberg editor
        add_theme_support('align-wide');

        /**
         * Add support for two custom navigation menus.
         */
        register_nav_menus(array(
            'menu_main'   => __('Primary Menu', 'taborovajizda'),
            'menu_main_mobile'   => __('Primary Menu Mobile', 'taborovajizda')
            ));

        /**
         * Enable support for the following post formats:
         * aside, gallery, quote, image, and video
         */
        add_theme_support('post-formats', array( 'aside', 'gallery', 'quote', 'image', 'video' ));

        add_image_size('bigger-medium', '500', '500', false);
        add_image_size('very-large', '1300', '1300', false);
        add_image_size('xlarge', '1600', '1600', false);
        add_image_size('xxlarge', '1920', '1920', false);
        add_image_size('xxxlarge', '2200', '2200', false);
        add_image_size('xxxxlarge', '2500', '2500', false);
        add_image_size('5xlarge', '2800', '2800', false);
        add_image_size('6xlarge', '3100', '3100', false);
        add_image_size('ultra-large', '9999', '9999', false);

        add_editor_style('style.css'); // tries to include style-editor.css directly from your theme folder

        /*
        wp_enqueue_style( 'normalize-style', get_template_directory_uri() . '/css/normalize-style.css', array(), filemtime(get_template_directory() . '/css/normalize-style.css'));
        wp_enqueue_style( 'style', get_stylesheet_uri() );
        wp_enqueue_style( 'current-style', get_template_directory_uri() . '/css/style-2019-mafia.css', array(), filemtime(get_template_directory() . '/css/style-2019-mafia.css'));

        wp_enqueue_script( 'taborovajizda-script', get_template_directory_uri() . '/js/functions.js', null, null, true);
        //wp_enqueue_script( 'taborovajizda-script-lazyload', get_template_directory_uri() . '/js/lazyload.js', null, null, true);
        */
    }
endif; // taborovajizda_setup
add_action('after_setup_theme', 'taborovajizda_setup');

function taborovajizda_scripts()
{
    $css_file = '/css/normalize-style.css';
    wp_enqueue_style('normalize-style', get_template_directory_uri() . $css_file, array(), filemtime(get_template_directory() . $css_file));

    wp_enqueue_style('style', get_stylesheet_uri(), array(), filemtime(get_template_directory() .'/style.css'));

    $css_file = '/css/style-2026-zombie-camp.css';
    wp_enqueue_style('current-style', get_template_directory_uri() . $css_file, array(), filemtime(get_template_directory() . $css_file));

    wp_enqueue_script('taborovajizda-script-functions', get_template_directory_uri() . '/js/functions.js', null, null, true);
    //wp_enqueue_script( 'obzamberk-script-lazyload', get_template_directory_uri() . '/js/lazyload.js', null, null, true);
}
add_action('wp_enqueue_scripts', 'taborovajizda_scripts');

function taborovajizda_custom_sizes($sizes)
{
    return array_merge($sizes, array(
        'bigger-medium' => __('bigger-medium'),
        'medium_large' => __('medium_large'),
        'very-large' => __('very-large'),
        'xlarge' => __('xlarge'),
        'xxlarge' => __('xxlarge'),
        'xxxlarge' => __('xxxlarge'),
        'xxxxlarge' => __('xxxxlarge'),
        '5xlarge' => __('5xlarge'),
        '6xlarge' => __('6xlarge'),
        'ultra-large' => __('ultra-large')
        ));
}
add_filter('image_size_names_choose', 'taborovajizda_custom_sizes');


/*
if (!function_exists('taborovajizda_customize_register')) {
    function taborovajizda_customize_register( $wp_customize ) {
       //All our sections, settings, and controls will be added here
    }
    add_action( 'customize_register', 'taborovajizda_customize_register' );
};
*/

if (!function_exists('taborovajizda_register_widgets')) {
    function taborovajizda_register_widgets()
    {
        register_sidebar(array(
            'name' => __('Boční panel', 'taborovajizda'),
            'id' => 'sidebar-widget-area',
            'before_widget' => '<div id="%1$s" class="widget %2$s">',
            'after_widget' => '</div>',
            'before_title' => '<h2 class="widget_title">',
            'after_title' => '</h2>'
            ));
    }
    add_action('widgets_init', 'taborovajizda_register_widgets');
};


if (!function_exists('taborovajizda_notify_new_post')) {
    function taborovajizda_notify_new_post($new_status, $old_status, $post)
    {
        // Proveď pouze pro nové posty něco -> publish
        $ID = $post->ID;
        $categories = get_the_category($post, 'category');
        $is_aktualita = false;
        foreach ($categories as $category) {
            ($category->slug == 'aktuality') && $is_aktualita = true;
            error_log('###########'.print_r($category, true));
        }
        // error_log("ID: $ID, is_aktualita: $is_aktualita, old: $old_status, new: $new_status.");
        if ($is_aktualita && $new_status == 'publish' && $old_status != 'publish') {
            $title = $post->post_title;
            $permalink = get_permalink($ID);
            /*
            // Pověs informaci o novém postu na FB
            /////////////////////////////////////////
            $fb = new Facebook\Facebook([
                'app_id' => '274313900070719',
                'app_secret' => getenv('TJ_FB_APP_SECRET'),
                'default_graph_version' => 'v3.1',
                ]);

            $arr = array(
                'message' => 'Nový příspěvek na www stránkách.',
                'link' => $permalink
                );
            $res = $fb->post('149483548721500/feed/', $arr,	getenv('TJ_FB_ACCESS_TOKEN'));
            */

            // Zašli upozornění e-mailem
            ////////////////////////////////////////
            $subject = sprintf('www.taborovajizda.cz má nový příspěvek: %s', $title);
            $message = sprintf('Pokud se Vám příspěvek nezobrazuje správně, klikněte na odkaz: %s'."\n\n", $permalink);
            $message .= $post->post_content;
            $headers[] = 'From: Táborová jízda <info@taborovajizda.cz>';
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
            $to = 'info@taborovajizda.cz'; // pošli záznam na e-mail webovek (funguje jako log)
            // připojení k db a získání kontaktů
            $conn = new mysqli(TJ_DB_HOST, TJ_DB_USER, TJ_DB_PASSWORD);
            // ověř připojení
            if ($conn->connect_error) {
                // error_log('Chyba při spojení s DB.');
                return new WP_Error('db_connect_error', 'Chyba při spojení s DB.'); //chyba při spojení s DB
            }
            // změň db na databázi s kontakty
            $conn->select_db(TJ_DB_NAME);
            // vyber ověřené e-mailové adresy z DB
            $sql = "select email, validate_id from newsletter where email is not null and is_validated=true;";
            if (!$result = $conn->query($sql)) {
                // uzavření spojeni s DB
                $conn->close();
                // error_log("Nepodařilo se získat kontaktní e-maily z databáze.\n".$sql);
                return new WP_Error('sql_select_error', "Nepodařilo se získat kontaktní e-maily z databáze.\n".$sql);
            }
            // zpracování výsledků (pokud nějaké jsou)
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $validate_id = $row['validate_id'];
                    $unsubscribe = "\n\nPro odhlášení odběru novinek klikněte na následující odkaz: https://www.taborovajizda.cz/odeslat-dotaz/?unsubscribe=$validate_id";
                    wp_mail($row['email'], $subject, $message.$unsubscribe, $headers);
                }
                // pošli záznam na e-mail webovek (funguje jako log)
                wp_mail($to, $subject, $message, $headers);
            }

            //Pošli informaci o novém postu pomocí SMS
            ///////////////////////////////////////////////
            /*
            // Konfigurace sms API klienta
            $sms_config = Configuration::getDefaultConfiguration();
            // SMS API key removed from Git; configure it outside the theme before enabling SMS.
            // vytvoření objektů pro komunikaci s sms rozhranní
            $sms_api_client = new ApiClient($sms_config);
            $sms_client = new MessageApi($sms_api_client);

            // vyber telefonní čísla z DB
            $sql = "select tel from kontakty where tel is not null;";
            if (!$result = $conn->query($sql)) {
                // došlo k chybě. uzavření spojeni s DB
                $conn->close();
                return new WP_Error('sql_select_error', "Nepodařilo se získat telefonní čísla z databáze.\n".$sql);
            }
            // zpracování výsledků (pokud nějaké jsou)
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                // vytvoření žádosti pro odeslání
                $sms_request = new SendMessageRequest([
                    'phoneNumber' => $row['tel'],
                    'message' => "Jóga Lenka se hlásí :)\nNový příspěvek: $title\nPodrobnosti zde: $permalink",
                    'deviceId' => 101771
                    ]);
                $sms_messages[] = $sms_request;
                }
                // odeslání sms ke zpracování
                try {
                    $sms_result = $sms_client->sendMessages($sms_messages);
                }
                catch (Exception $e) {
                    echo 'Při běhu došlo k chybě: ',  $e->getMessage(), "\n";
                }
            }
            */
            // uzavření spojeni s DB
            $conn->close();
        }
    }
    add_action('transition_post_status', 'taborovajizda_notify_new_post', 9, 3);
}


function strpos_recursive($haystack, $needle, $offset = 0, &$results = array())
{
    $offset = strpos($haystack, $needle, $offset);
    if ($offset === false) {
        return $results;
    } else {
        $results[] = $offset;
        return strpos_recursive($haystack, $needle, ($offset + 1), $results);
    }
}

function print_html($var)
{
    echo "<pre>";
    print_r($var);
    echo "</pre>";
}

function print_log($var)
{
    $var_str = print_r($var, true);
    $var_str = str_replace("\n", '\\n', $var_str);
    $var_str = str_replace("'", "\'", $var_str);
    echo "<script>console.log('".$var_str."');</script>";
}
