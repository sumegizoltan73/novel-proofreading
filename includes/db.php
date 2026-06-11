<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define('NOVEL_PROOFREADING_DB_VERSION', '0.7');

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

    $series_table_name =
        $wpdb->prefix . 'novel_proofreading_series';
    $series_mapping_table_name =
        $wpdb->prefix . 'novel_proofreading_series_mapping';


    $sql_series = "
    CREATE TABLE $series_table_name (

        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

        series_title VARCHAR(1024) NOT NULL,
        series_subtitle VARCHAR(1024) NOT NULL,
        author VARCHAR(255) NOT NULL,
        year VARCHAR(4) NOT NULL,

        status VARCHAR(20) NOT NULL DEFAULT 'PROOFREADING',

        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,

        PRIMARY KEY  (id),

        UNIQUE KEY idx_series_title (
            series_title,
            series_subtitle
        ),

        KEY idx_series_status (
            status
        )

    ) $charset_collate ;

    CREATE TABLE $series_mapping_table_name (

        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

        series_id BIGINT UNSIGNED NOT NULL,
        book_id BIGINT UNSIGNED NOT NULL,
        
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,

        PRIMARY KEY  (id),

        KEY idx_series_mapping (
            series_id,
            book_id
        )

    ) $charset_collate ;
    ";

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
    dbDelta($sql_series);

    $types_table_name =
        $wpdb->prefix . 'novel_proofreading_types';

    $common_mapping_table_name =
        $wpdb->prefix . 'novel_proofreading_common_mapping';

    $datetimes_table_name =
        $wpdb->prefix . 'novel_proofreading_datetimes';

    $professions_table_name =
        $wpdb->prefix . 'novel_proofreading_professions';

    $persons_table_name =
        $wpdb->prefix . 'novel_proofreading_persons';

    $locations_table_name =
        $wpdb->prefix . 'novel_proofreading_locations';

    $presence_mapping_table_name =
        $wpdb->prefix . 'novel_proofreading_presence_mapping';

    $sql = "
    CREATE TABLE $types_table_name (

        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

        name VARCHAR(64) NOT NULL,
        category VARCHAR(32) NOT NULL,

        created_at DATETIME NOT NULL,
        created_by BIGINT UNSIGNED NOT NULL DEFAULT 0,

        PRIMARY KEY  (id),

        UNIQUE KEY idx_name_category (
            name,
            category
        ),

        KEY idx_category (
            category
        )

    ) $charset_collate;

    CREATE TABLE $common_mapping_table_name (

        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

        book_id BIGINT UNSIGNED NOT NULL,
        type VARCHAR(32) NOT NULL,
        person_related_subtype VARCHAR(32) NULL,
        description VARCHAR(2048) NULL,
        page VARCHAR(64) NULL,
        chapter VARCHAR(255) NULL,
        storyline_id BIGINT UNSIGNED NULL,
        event_id BIGINT UNSIGNED NULL,
        person_id BIGINT UNSIGNED NULL,
        location_id BIGINT UNSIGNED NULL,
        time_id BIGINT UNSIGNED NULL,

        created_at DATETIME NOT NULL,
        created_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
        updated_at DATETIME NULL,
        updated_by BIGINT UNSIGNED NULL,
        suggested_at DATETIME NULL,
        suggested_by BIGINT UNSIGNED NULL,
        to_be_solved CHAR(1) NOT NULL DEFAULT 'N',
        is_solved CHAR(1) NOT NULL DEFAULT 'N',
        solved_at DATETIME NULL,
        solved_type VARCHAR(32) NULL,

        PRIMARY KEY  (id),

        KEY idx_book_type (
            book_id,
            type
        ),

        KEY idx_storyline (
            storyline_id
        ),

        KEY idx_event (
            event_id
        ),

        KEY idx_person (
            person_id
        ),

        KEY idx_location (
            location_id
        ),

        KEY idx_time (
            time_id
        ),

        KEY idx_solved (
            to_be_solved,
            is_solved
        )

    ) $charset_collate;

    CREATE TABLE $datetimes_table_name (

        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

        book_id BIGINT UNSIGNED NOT NULL,
        name VARCHAR(255) NOT NULL,
        time_description TEXT NULL,
        description TEXT NULL,
        time_type VARCHAR(32) NOT NULL,
        is_inaccurate CHAR(1) NOT NULL DEFAULT 'N',

        created_at DATETIME NOT NULL,
        created_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
        updated_at DATETIME NULL,
        updated_by BIGINT UNSIGNED NULL,

        PRIMARY KEY  (id),

        KEY idx_book_time_type (
            book_id,
            time_type
        ),

        KEY idx_book_name (
            book_id,
            name
        )

    ) $charset_collate;

    CREATE TABLE $professions_table_name (

        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

        book_id BIGINT UNSIGNED NOT NULL,
        person_id BIGINT UNSIGNED NOT NULL,
        profession_name VARCHAR(255) NOT NULL,
        description TEXT NULL,
        is_inaccurate CHAR(1) NOT NULL DEFAULT 'N',

        created_at DATETIME NOT NULL,
        created_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
        updated_at DATETIME NULL,
        updated_by BIGINT UNSIGNED NULL,

        PRIMARY KEY  (id),

        KEY idx_book_person (
            book_id,
            person_id
        ),

        KEY idx_profession_name (
            profession_name
        )

    ) $charset_collate;

    CREATE TABLE $persons_table_name (

        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

        book_id BIGINT UNSIGNED NOT NULL,
        name VARCHAR(255) NULL,
        alias VARCHAR(255) NULL,
        description TEXT NULL,
        is_inaccurate CHAR(1) NOT NULL DEFAULT 'N',

        created_at DATETIME NOT NULL,
        created_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
        updated_at DATETIME NULL,
        updated_by BIGINT UNSIGNED NULL,

        PRIMARY KEY  (id),

        KEY idx_book_name (
            book_id,
            name
        ),

        KEY idx_book_alias (
            book_id,
            alias
        )

    ) $charset_collate;

    CREATE TABLE $locations_table_name (

        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

        book_id BIGINT UNSIGNED NOT NULL,
        name VARCHAR(255) NOT NULL,
        alias VARCHAR(255) NULL,
        area VARCHAR(32) NULL,
        region VARCHAR(255) NULL,
        description TEXT NULL,
        is_in_alternative_universe CHAR(1) NOT NULL DEFAULT 'N',
        is_inaccurate CHAR(1) NOT NULL DEFAULT 'N',

        created_at DATETIME NOT NULL,
        created_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
        updated_at DATETIME NULL,
        updated_by BIGINT UNSIGNED NULL,

        PRIMARY KEY  (id),

        KEY idx_book_name (
            book_id,
            name
        ),

        KEY idx_book_area (
            book_id,
            area
        )

    ) $charset_collate;
    ";

    dbDelta($sql);

    $storylines_table_name =
        $wpdb->prefix . 'novel_proofreading_storylines';
    $events_table_name =
        $wpdb->prefix . 'novel_proofreading_events';
    $sql = "
    CREATE TABLE $storylines_table_name (

        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

        book_id BIGINT UNSIGNED NOT NULL,
        storyline_name VARCHAR(255) NOT NULL,
        main_event BIGINT UNSIGNED NOT NULL DEFAULT 0,
        description TEXT NULL,

        created_at DATETIME NOT NULL,
        created_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
        updated_at DATETIME NULL,
        updated_by BIGINT UNSIGNED NULL,

        PRIMARY KEY  (id),

        KEY idx_storyline_name (
            book_id,
            storyline_name
        ),

        KEY idx_storyline_event (
            book_id,
            main_event
        )

    ) $charset_collate;

    CREATE TABLE $events_table_name (

        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

        book_id BIGINT UNSIGNED NOT NULL,
        storyline_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
        sequence_no INT NOT NULL DEFAULT 0,
        chain_role VARCHAR(32) NOT NULL DEFAULT 'STEP',
        event_name VARCHAR(255) NOT NULL,
        description TEXT NULL,

        created_at DATETIME NOT NULL,
        created_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
        updated_at DATETIME NULL,
        updated_by BIGINT UNSIGNED NULL,

        PRIMARY KEY  (id),

        KEY idx_event_name (
            book_id,
            event_name
        ),

        KEY idx_event_storyline (
            book_id,
            storyline_id,
            sequence_no
        )

    ) $charset_collate;
    ";

    dbDelta($sql);

    // SEED common_types
    $wpdb->query(
        "
        INSERT INTO $types_table_name (
            name,
            category,
            created_at,
            created_by
        )
        SELECT
            seed.name,
            seed.category,
            " . $wpdb->prepare('%s', current_time('mysql')) . ",
            0
        FROM (
            SELECT 'STORYLINE' AS name, 'COMMON_TYPE' AS category
            UNION ALL SELECT 'EVENT', 'COMMON_TYPE'
            UNION ALL SELECT 'PERSON', 'COMMON_TYPE'
            UNION ALL SELECT 'LOCATION', 'COMMON_TYPE'
            UNION ALL SELECT 'TIME', 'COMMON_TYPE'
            UNION ALL SELECT 'MISTAKE', 'COMMON_TYPE'
            UNION ALL SELECT 'SUGGESTION', 'COMMON_TYPE'
            UNION ALL SELECT 'AGREEMENT', 'COMMON_TYPE'
            UNION ALL SELECT 'REWRITTED', 'SOLVED_TYPE'
            UNION ALL SELECT 'ADDITION', 'SOLVED_TYPE'
            UNION ALL SELECT 'DELETED', 'SOLVED_TYPE'
            UNION ALL SELECT 'BIRTHDATE', 'DATETIME_TYPE'
            UNION ALL SELECT 'EVENTDATE', 'DATETIME_TYPE'
            UNION ALL SELECT 'AGE', 'DATETIME_TYPE'
            UNION ALL SELECT 'LIFEPATH', 'DATETIME_TYPE'
            UNION ALL SELECT 'SERVICETIME', 'DATETIME_TYPE'
            UNION ALL SELECT 'LOCATION', 'PRESENCE_TYPE'
            UNION ALL SELECT 'TIME', 'PRESENCE_TYPE'
            UNION ALL SELECT 'PERSON', 'PRESENCE_TYPE'
            UNION ALL SELECT 'EVENT', 'PRESENCE_TYPE'
            UNION ALL SELECT 'STORYLINE', 'PRESENCE_TYPE'
            UNION ALL SELECT 'ACTOR', 'PERSON_SUBTYPE'
            UNION ALL SELECT 'HIGHLIGHTED', 'PERSON_SUBTYPE'
            UNION ALL SELECT '2ND_ACTOR', 'PERSON_SUBTYPE'
            UNION ALL SELECT 'EVIL', 'PERSON_SUBTYPE'
            UNION ALL SELECT 'HERO', 'PERSON_SUBTYPE'
            UNION ALL SELECT 'SPACE', 'AREA_TYPE'
            UNION ALL SELECT 'GALAXY', 'AREA_TYPE'
            UNION ALL SELECT 'ANOTHER_GALAXY', 'AREA_TYPE'
            UNION ALL SELECT 'ANOTHER_PLANET', 'AREA_TYPE'
            UNION ALL SELECT 'EARTH', 'AREA_TYPE'
            UNION ALL SELECT 'COUNTRY', 'AREA_TYPE'
            UNION ALL SELECT 'CITY', 'AREA_TYPE'
        ) seed
        WHERE NOT EXISTS (
            SELECT 1
            FROM $types_table_name existing
            WHERE existing.name = seed.name
                AND existing.category = seed.category
        )
        "
    );

    $wpdb->query("DROP TABLE $presence_mapping_table_name ;");
    
	update_option(
        'novel_proofreading_db_version',
        NOVEL_PROOFREADING_DB_VERSION
    );
}
