<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action(
    'admin_menu',
    'novel_proofreading_admin_menu'
);

function novel_proofreading_admin_menu() {
    add_menu_page(
        'Novel Proofreading',
        'Novel Proofreading',
        'manage_options',
        'novel-proofreading',
        'novel_proofreading_admin_page',
        'dashicons-book-alt',
        30
    );
}

function novel_proofreading_admin_page() {

    $admin_notice = "";

    
}

add_action(
    'admin_enqueue_scripts',
    'novel_proofreading_admin_assets'
);

function novel_proofreading_admin_assets($hook) {

    if ($hook !== 'toplevel_page_novel-proofreading') {
        return;
    }

    $plugin_file = dirname( __DIR__ ) . '/novel-proofreading.php';

    
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

add_filter( 'pre_load_script_translations', function( $translations, $file, $handle, $domain ) {
    if ( $domain === 'novel-proofreading' ) {
        // Ez kiírja a PHP hibanaplóba (wp-content/debug.log), hogy pontosan milyen fájlnevet vár a WP
        error_log( "WP ezt a fájlt keresi: " . $file );
    }
    return $translations;
}, 10, 4 );