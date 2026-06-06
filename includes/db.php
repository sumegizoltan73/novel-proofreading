<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define('NOVEL_PROOFREADING_DB_VERSION', '0.1');

function novel_proofreading_install() {

    novel_proofreading_create_tables();
}

function novel_proofreading_update_db_check() {

    $installed_version =
        get_option(
            'novel_proofreading_db_version'
        );

    if (
        $installed_version !==
        NOVEL_PROOFREADING_DB_VERSION
    ) {

        novel_proofreading_install();
    }
}
function novel_proofreading_create_tables() {

    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    $table_name =
        $wpdb->prefix . 'novel_proofreading_books';


    $sql = "
    CREATE TABLE $table_name (

        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

        title VARCHAR(1024) NOT NULL,
        subtitle VARCHAR(1024) NOT NULL,
        author VARCHAR(255) NOT NULL,
        year VARCHAR(4) NOT NULL,

        status VARCHAR(20) NOT NULL DEFAULT 'PROOFREADING',

        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,

        PRIMARY KEY  (id),

        UNIQUE KEY idx_title (
            title,
            subtitle
        ),

        KEY idx_status (
            status
        )

    ) $charset_collate ;
    ";

    require_once(
        ABSPATH . 'wp-admin/includes/upgrade.php'
    );

    dbDelta($sql);
    
		update_option(
        'novel_proofreading_db_version',
        NOVEL_PROOFREADING_DB_VERSION
    );
}
