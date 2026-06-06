<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Common render function
 */
function novel_proofreading_plugin_render( $atts = [] ) {

    novel_proofreading_plugin_enqueue_assets();

    ob_start();
    ?>

    <div id="novel-proofreading-calendar"></div>

    <?php

    return ob_get_clean();
}

function novel_proofreading_plugin_enqueue_assets() {

    static $loaded = false;

    if ($loaded) {
        return;
    }

    $loaded = true;

    wp_enqueue_script(
        'fullcalendar',
        plugin_dir_url(__FILE__) . '../assets/vendor/fullcalendar/index.global.min.js',
        [],
        '6.1.20',
        true
    );

    wp_enqueue_script(
        'fullcalendar-locales',
        plugin_dir_url(__FILE__) . '../assets/vendor/fullcalendar/locales-all.global.min.js',
        ['fullcalendar'],
        '6.1.20',
        true
    );

    wp_enqueue_script(
        'sweetalert2',
        'https://cdn.jsdelivr.net/npm/sweetalert2@11',
        [],
        '11',
        true
    );

    wp_enqueue_script(
        'momentjs',
        'https://cdn.jsdelivr.net/npm/moment@2.30.1/moment.min.js',
        [],
        '2.30.1',
        true
    );
    wp_enqueue_script(
        'daterangepicker',
        'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js',
        ['jquery', 'momentjs'],
        '3.1',
        true
    );
    wp_enqueue_style(
        'daterangepicker-style',
        'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css',
        [],
        '3.1'
    );


    wp_enqueue_script(
        'novel-proofreading-admin',
        plugin_dir_url(__FILE__) . '../assets/js/admin.js?nocache=' . date("Ymd_His"),
        array( 'wp-i18n', 'fullcalendar', 'jquery' ),
        filemtime(
            plugin_dir_path(__FILE__) .
            '../assets/js/admin.js'
        ),
        true
    );

    wp_enqueue_style(
        'novel-proofreading-admin-style',
        plugin_dir_url(__FILE__) . '../assets/css/admin.css?nocache=' . date("Ymd_His"),
        [],
        '1.0'
    );

    wp_enqueue_style(
        'novel-proofreading-style',
        plugin_dir_url(__FILE__) . '../assets/css/style.css?nocache=' . date("Ymd_His"),
        [],
        '1.0'
    );

    wp_localize_script(
        'novel-proofreading-admin',
        'novelProofreading',
        [
            'nonce' => wp_create_nonce('wp_rest'),
            'restUrl' => rest_url(
                'novel-proofreading/v1/'
            )
        ]
    );
}

/**
 * Shortcode registration
 */
function novel_proofreading_plugin_shortcode( $atts ) {
    return novel_proofreading_plugin_render( $atts );
}

add_shortcode(
    'novel_proofreading',
    'novel_proofreading_plugin_shortcode'
);