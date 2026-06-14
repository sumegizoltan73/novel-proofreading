<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action(
    'admin_menu',
    'novel_proofreading_admin_menu'
);

add_action(
    'wp_ajax_novel_proofreading_get_storyline_suggestions',
    'novel_proofreading_ajax_get_storyline_suggestions'
);

add_action(
    'wp_ajax_novel_proofreading_get_person_aliases',
    'novel_proofreading_ajax_get_person_aliases'
);

add_action(
    'wp_ajax_novel_proofreading_get_person_professions',
    'novel_proofreading_ajax_get_person_professions'
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

function novel_proofreading_get_storyline_suggestions($storyline_id) {
    global $wpdb;

    $table_mapping =
        $wpdb->prefix . 'novel_proofreading_common_mapping';

    return $wpdb->get_results(
        $wpdb->prepare(
            "
            SELECT
                type,
                description

            FROM
                {$table_mapping}

            WHERE
                    storyline_id = %d
                AND type IN ('AGREEMENT', 'SUGGESTION')

            ORDER BY
                id
            ",
            $storyline_id
        ),
        ARRAY_A
    );
}

function novel_proofreading_ajax_get_storyline_suggestions() {
    if (! current_user_can('manage_options')) {
        wp_send_json_error(
            [
                'message' => __( 'You are not allowed to view this data.', 'novel-proofreading' )
            ],
            403
        );
    }

    check_ajax_referer(
        'novel_proofreading_storyline_suggestions',
        'nonce'
    );

    $storyline_id = intval($_POST['storyline_id'] ?? 0);

    if ($storyline_id <= 0) {
        wp_send_json_error(
            [
                'message' => __( 'Storyline is required.', 'novel-proofreading' )
            ],
            400
        );
    }

    wp_send_json_success(
        [
            'items' => novel_proofreading_get_storyline_suggestions($storyline_id)
        ]
    );
}

function novel_proofreading_get_person_aliases($person_id) {
    global $wpdb;

    $table_mapping =
        $wpdb->prefix . 'novel_proofreading_person_alias_mapping';
    $table_persons =
        $wpdb->prefix . 'novel_proofreading_persons';

    return $wpdb->get_results(
        $wpdb->prepare(
            "
            SELECT
                ap.id,
                ap.name,
                ap.alias,
                ap.description

            FROM
                {$table_mapping} pam
            INNER JOIN
                {$table_persons} ap ON ap.id = pam.alias_person_id

            WHERE
                pam.person_id = %d

            ORDER BY
                ap.name,
                ap.alias,
                ap.id
            ",
            $person_id
        ),
        ARRAY_A
    );
}

function novel_proofreading_ajax_get_person_aliases() {
    if (! current_user_can('manage_options')) {
        wp_send_json_error(
            [
                'message' => __( 'You are not allowed to view this data.', 'novel-proofreading' )
            ],
            403
        );
    }

    check_ajax_referer(
        'novel_proofreading_person_aliases',
        'nonce'
    );

    $person_id = intval($_POST['person_id'] ?? 0);

    if ($person_id <= 0) {
        wp_send_json_error(
            [
                'message' => __( 'Person is required.', 'novel-proofreading' )
            ],
            400
        );
    }

    wp_send_json_success(
        [
            'items' => novel_proofreading_get_person_aliases($person_id)
        ]
    );
}

function novel_proofreading_get_person_professions($person_id, $scope) {
    global $wpdb;

    $table_professions =
        $wpdb->prefix . 'novel_proofreading_professions';
    $table_persons =
        $wpdb->prefix . 'novel_proofreading_persons';
    $table_alias_mapping =
        $wpdb->prefix . 'novel_proofreading_person_alias_mapping';
    $table_professions =
        $wpdb->prefix . 'novel_proofreading_professions';

    if ($scope === 'aliases') {
        return $wpdb->get_results(
            $wpdb->prepare(
                "
                SELECT
                    pr.profession_name,
                    pr.description,
                    p.name AS person_name,
                    p.alias AS person_alias

                FROM
                    {$table_alias_mapping} pam
                INNER JOIN
                    {$table_professions} pr ON pr.person_id = pam.alias_person_id
                INNER JOIN
                    {$table_persons} p ON p.id = pam.alias_person_id

                WHERE
                    pam.person_id = %d

                ORDER BY
                    p.name,
                    p.alias,
                    pr.profession_name,
                    pr.id
                ",
                $person_id
            ),
            ARRAY_A
        );
    }

    return $wpdb->get_results(
        $wpdb->prepare(
            "
            SELECT
                pr.profession_name,
                pr.description,
                p.name AS person_name,
                p.alias AS person_alias

            FROM
                {$table_professions} pr
            INNER JOIN
                {$table_persons} p ON p.id = pr.person_id

            WHERE
                pr.person_id = %d

            ORDER BY
                pr.profession_name,
                pr.id
            ",
            $person_id
        ),
        ARRAY_A
    );
}

function novel_proofreading_ajax_get_person_professions() {
    if (! current_user_can('manage_options')) {
        wp_send_json_error(
            [
                'message' => __( 'You are not allowed to view this data.', 'novel-proofreading' )
            ],
            403
        );
    }

    check_ajax_referer(
        'novel_proofreading_person_professions',
        'nonce'
    );

    $person_id = intval($_POST['person_id'] ?? 0);
    $scope = sanitize_text_field(
        wp_unslash($_POST['scope'] ?? 'person')
    );

    if ($person_id <= 0) {
        wp_send_json_error(
            [
                'message' => __( 'Person is required.', 'novel-proofreading' )
            ],
            400
        );
    }

    wp_send_json_success(
        [
            'items' => novel_proofreading_get_person_professions($person_id, $scope)
        ]
    );
}

function novel_proofreading_get_books() {
    global $wpdb;

    $items = [];

    $table_books =
        $wpdb->prefix . 'novel_proofreading_books';
    $table_series_mapping =
        $wpdb->prefix . 'novel_proofreading_series_mapping';
    $table_series =
        $wpdb->prefix . 'novel_proofreading_series';

    $result = $wpdb->get_results(
        "
        SELECT
            b.*,
            s.series_title
        FROM
            {$table_books} b
        LEFT JOIN
            {$table_series_mapping} sm ON sm.book_id = b.id
        LEFT JOIN 
            {$table_series} s ON s.id = sm.series_id

        ORDER BY b.id
        "
    );

    foreach ($result as $row) {

        $items[] = [
            'id' => intval($row->id),

            'series_title' => isset($row->series_title) ? $row->series_title : '', 

            'title' => $row->title,

            'subtitle' => $row->subtitle,

            'author' => $row->author,

            'year' => $row->year,

            'status' => $row->status
        ];
    }

    return $items;
}

function novel_proofreading_get_books_by_series($series_id) {
    global $wpdb;

    $items = [];

    $table_books =
        $wpdb->prefix . 'novel_proofreading_books';
    $table_series_mapping =
        $wpdb->prefix . 'novel_proofreading_series_mapping';
    $table_series =
        $wpdb->prefix . 'novel_proofreading_series';

    $result = $wpdb->get_results(
        "
        SELECT
            b.*,
            s.series_title
        FROM
            {$table_books} b
        JOIN
            {$table_series_mapping} sm ON sm.book_id = b.id
        JOIN 
            {$table_series} s ON s.id = sm.series_id
        WHERE
            s.id = {$series_id}

        ORDER BY b.id
        "
    );

    foreach ($result as $row) {

        $items[] = [
            'id' => intval($row->id),

            'series_title' => isset($row->series_title) ? $row->series_title : '', 

            'title' => $row->title,

            'subtitle' => $row->subtitle,

            'author' => $row->author,

            'year' => $row->year,

            'status' => $row->status
        ];
    }

    return $items;
}
function novel_proofreading_get_series() {
    global $wpdb;

    $items = [];

    $table_series =
        $wpdb->prefix . 'novel_proofreading_series';

    $result = $wpdb->get_results(
        "
        SELECT
            *

        FROM
            {$table_series}

        ORDER BY id
        "
    );

    foreach ($result as $row) {

        $items[] = [
            'id' => intval($row->id),

            'series_title' => $row->series_title,

            'series_subtitle' => $row->series_subtitle,

            'author' => $row->author,

            'year' => $row->year,

            'status' => $row->status
        ];
    }

    return $items;
}

function novel_proofreading_add_book() {
    global $wpdb;

    $table =
        $wpdb->prefix . 'novel_proofreading_books';

    $now = current_time(
        'mysql',
        true
    );

    $result = $wpdb->insert(
        $table,
        [
            'title' => sanitize_text_field(
                wp_unslash($_POST['title'] ?? '')
            ),

            'subtitle' => sanitize_text_field(
                wp_unslash($_POST['subtitle'] ?? '')
            ),

            'author' => sanitize_text_field(
                wp_unslash($_POST['author'] ?? '')
            ),

            'year' => sanitize_text_field(
                wp_unslash($_POST['year'] ?? '')
            ),

            'created_at' => $now,

            'updated_at' => $now
        ]
    );

    if ($result === false) {
        error_log(
            'INSERT ERROR: ' . $wpdb->last_error
        );

        return __( 'Book could not be added.', 'novel-proofreading' );
    }

    return __( 'Book added.', 'novel-proofreading' );
}

function novel_proofreading_remove_book($id) {
    global $wpdb;

    $table =
        $wpdb->prefix . 'novel_proofreading_books';

    $wpdb->query(
        $wpdb->prepare(
            "
            DELETE FROM {$table}

            WHERE
                id = %d
            ",
            $id
        )
    );

    return __( 'Book deleted.', 'novel-proofreading' );
}

function novel_proofreading_update_book($id) {
    global $wpdb;

    if ($id <= 0) {
        return __( 'Book could not be updated.', 'novel-proofreading' );
    }

    $table =
        $wpdb->prefix . 'novel_proofreading_books';

    $result = $wpdb->update(
        $table,
        [
            'title' => sanitize_text_field(
                wp_unslash($_POST['title'] ?? '')
            ),

            'subtitle' => sanitize_text_field(
                wp_unslash($_POST['subtitle'] ?? '')
            ),

            'author' => sanitize_text_field(
                wp_unslash($_POST['author'] ?? '')
            ),

            'year' => sanitize_text_field(
                wp_unslash($_POST['year'] ?? '')
            ),

            'status' => sanitize_text_field(
                wp_unslash($_POST['status'] ?? '')
            ),

            'updated_at' => current_time(
                'mysql',
                true
            )
        ],
        [
            'id' => $id
        ],
        [
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s'
        ],
        [
            '%d'
        ]
    );

    if ($result === false) {
        error_log(
            'UPDATE ERROR: ' . $wpdb->last_error
        );

        return __( 'Book could not be updated.', 'novel-proofreading' );
    }

    return __( 'Book updated.', 'novel-proofreading' );
}

function novel_proofreading_add_series() {
    global $wpdb;

    $table =
        $wpdb->prefix . 'novel_proofreading_series';

    $now = current_time(
        'mysql',
        true
    );

    $result = $wpdb->insert(
        $table,
        [
            'series_title' => sanitize_text_field(
                wp_unslash($_POST['series_title'] ?? '')
            ),

            'series_subtitle' => sanitize_text_field(
                wp_unslash($_POST['series_subtitle'] ?? '')
            ),

            'author' => sanitize_text_field(
                wp_unslash($_POST['author'] ?? '')
            ),

            'year' => sanitize_text_field(
                wp_unslash($_POST['year'] ?? '')
            ),

            'created_at' => $now,

            'updated_at' => $now
        ]
    );

    if ($result === false) {
        error_log(
            'INSERT ERROR: ' . $wpdb->last_error
        );

        return __( 'Series could not be added.', 'novel-proofreading' );
    }

    return __( 'Series added.', 'novel-proofreading' );
}

function novel_proofreading_update_series($id) {
    global $wpdb;

    if ($id <= 0) {
        return __( 'Series could not be updated.', 'novel-proofreading' );
    }

    $table =
        $wpdb->prefix . 'novel_proofreading_series';

    $result = $wpdb->update(
        $table,
        [
            'series_title' => sanitize_text_field(
                wp_unslash($_POST['series_title'] ?? '')
            ),

            'series_subtitle' => sanitize_text_field(
                wp_unslash($_POST['series_subtitle'] ?? '')
            ),

            'author' => sanitize_text_field(
                wp_unslash($_POST['author'] ?? '')
            ),

            'year' => sanitize_text_field(
                wp_unslash($_POST['year'] ?? '')
            ),

            'status' => sanitize_text_field(
                wp_unslash($_POST['status'] ?? '')
            ),

            'updated_at' => current_time(
                'mysql',
                true
            )
        ],
        [
            'id' => $id
        ],
        [
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s'
        ],
        [
            '%d'
        ]
    );

    if ($result === false) {
        error_log(
            'UPDATE ERROR: ' . $wpdb->last_error
        );

        return __( 'Series could not be updated.', 'novel-proofreading' );
    }

    return __( 'Series updated.', 'novel-proofreading' );
}

function novel_proofreading_remove_series($id) {
    global $wpdb;

    $table =
        $wpdb->prefix . 'novel_proofreading_series';

    $wpdb->query(
        $wpdb->prepare(
            "
            DELETE FROM {$table}

            WHERE
                id = %d
            ",
            $id
        )
    );

    return __( 'Series deleted.', 'novel-proofreading' );
}

function novel_proofreading_add_book_to_series() {
    global $wpdb;

    $series_id = intval($_POST['series_id'] ?? 0);
    $book_id = intval($_POST['book_id'] ?? 0);

    if ($series_id <= 0 || $book_id <= 0) {
        return __( 'Book and series are required.', 'novel-proofreading' );
    }

    $table =
        $wpdb->prefix . 'novel_proofreading_series_mapping';

    $now = current_time(
        'mysql',
        true
    );

    $result = $wpdb->insert(
        $table,
        [
            'series_id' => $series_id,

            'book_id' => $book_id,

            'created_at' => $now,

            'updated_at' => $now
        ],
        [
            '%d',
            '%d',
            '%s',
            '%s'
        ]
    );

    if ($result === false) {
        error_log(
            'INSERT ERROR: ' . $wpdb->last_error
        );

        return __( 'Book could not be added to series.', 'novel-proofreading' );
    }

    return __( 'Book added to series.', 'novel-proofreading' );
}

function novel_proofreading_remove_book_from_series($series_id, $book_id) {
    global $wpdb;

    $table =
        $wpdb->prefix . 'novel_proofreading_series_mapping';

    $wpdb->query(
        $wpdb->prepare(
            "
            DELETE FROM {$table}

            WHERE
                    series_id = %d
                AND book_id = %d
            ",
            $series_id,
            $book_id
        )
    );

    return __( 'Book deleted.', 'novel-proofreading' );
}

function novel_proofreading_get_persons() {
    global $wpdb;

    $items = [];

    $table_persons =
        $wpdb->prefix . 'novel_proofreading_persons';
    $table_books =
        $wpdb->prefix . 'novel_proofreading_books';
    $table_alias_mapping =
        $wpdb->prefix . 'novel_proofreading_person_alias_mapping';
    $table_professions =
        $wpdb->prefix . 'novel_proofreading_professions';

    $result = $wpdb->get_results(
        "
        SELECT
            p.*,
            b.title AS book_title,
            (
                SELECT
                    COUNT(*)
                FROM
                    {$table_alias_mapping} pam
                WHERE
                    pam.person_id = p.id
            ) AS alias_mapping_count,
            (
                SELECT
                    COUNT(*)
                FROM
                    {$table_professions} pr
                WHERE
                    pr.person_id = p.id
            ) AS profession_count,
            (
                SELECT
                    COUNT(*)
                FROM
                    {$table_alias_mapping} pam
                INNER JOIN
                    {$table_professions} pr ON pr.person_id = pam.alias_person_id
                WHERE
                    pam.person_id = p.id
            ) AS alias_profession_count

        FROM
            {$table_persons} p
        LEFT JOIN
            {$table_books} b ON b.id = p.book_id

        ORDER BY
            p.id
        "
    );

    foreach ($result as $row) {

        $items[] = [
            'id' => intval($row->id),

            'book_id' => intval($row->book_id),

            'book_title' => isset($row->book_title) ? $row->book_title : '',

            'name' => $row->name,

            'alias' => $row->alias,

            'description' => $row->description,

            'is_inaccurate' => $row->is_inaccurate,

            'has_alias_mapping' => intval($row->alias_mapping_count) > 0,

            'has_profession' => intval($row->profession_count) > 0,

            'has_alias_profession' => intval($row->alias_profession_count) > 0
        ];
    }

    return $items;
}

function novel_proofreading_sanitize_person_data() {
    $name = sanitize_text_field(
        wp_unslash($_POST['name'] ?? '')
    );
    $alias = sanitize_text_field(
        wp_unslash($_POST['alias'] ?? '')
    );

    if ($name === '' && $alias === '') {
        return new WP_Error(
            'missing_person_name',
            __( 'Name or alias is required.', 'novel-proofreading' )
        );
    }

    $book_id = intval($_POST['book_id'] ?? 0);

    if ($book_id <= 0) {
        return new WP_Error(
            'missing_person_book',
            __( 'Book is required.', 'novel-proofreading' )
        );
    }

    return [
        'book_id' => $book_id,

        'name' => $name,

        'alias' => $alias,

        'description' => sanitize_textarea_field(
            wp_unslash($_POST['description'] ?? '')
        ),

        'is_inaccurate' => isset($_POST['is_inaccurate']) ? 'Y' : 'N'
    ];
}

function novel_proofreading_add_person() {
    global $wpdb;

    $data = novel_proofreading_sanitize_person_data();

    if (is_wp_error($data)) {
        return $data->get_error_message();
    }

    $table =
        $wpdb->prefix . 'novel_proofreading_persons';

    $now = current_time(
        'mysql',
        true
    );

    $result = $wpdb->insert(
        $table,
        array_merge(
            $data,
            [
                'created_at' => $now,

                'created_by' => get_current_user_id(),

                'updated_at' => $now,

                'updated_by' => get_current_user_id()
            ]
        ),
        [
            '%d',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%d',
            '%s',
            '%d'
        ]
    );

    if ($result === false) {
        error_log(
            'INSERT ERROR: ' . $wpdb->last_error
        );

        return __( 'Person could not be added.', 'novel-proofreading' );
    }

    return __( 'Person added.', 'novel-proofreading' );
}

function novel_proofreading_update_person($id) {
    global $wpdb;

    if ($id <= 0) {
        return __( 'Person could not be updated.', 'novel-proofreading' );
    }

    $data = novel_proofreading_sanitize_person_data();

    if (is_wp_error($data)) {
        return $data->get_error_message();
    }

    $table =
        $wpdb->prefix . 'novel_proofreading_persons';

    $result = $wpdb->update(
        $table,
        array_merge(
            $data,
            [
                'updated_at' => current_time(
                    'mysql',
                    true
                ),

                'updated_by' => get_current_user_id()
            ]
        ),
        [
            'id' => $id
        ],
        [
            '%d',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%d'
        ],
        [
            '%d'
        ]
    );

    if ($result === false) {
        error_log(
            'UPDATE ERROR: ' . $wpdb->last_error
        );

        return __( 'Person could not be updated.', 'novel-proofreading' );
    }

    return __( 'Person updated.', 'novel-proofreading' );
}

function novel_proofreading_remove_person($id) {
    global $wpdb;

    $table =
        $wpdb->prefix . 'novel_proofreading_persons';

    $wpdb->query(
        $wpdb->prepare(
            "
            DELETE FROM {$table}

            WHERE
                id = %d
            ",
            $id
        )
    );

    return __( 'Person deleted.', 'novel-proofreading' );
}

function novel_proofreading_format_person_label($name, $alias) {
    $label = trim($name . ' ' . $alias);

    return $label !== '' ? $label : __( 'Unnamed person', 'novel-proofreading' );
}

function novel_proofreading_person_belongs_to_book($person_id, $book_id) {
    global $wpdb;

    if ($person_id <= 0) {
        return false;
    }

    $table =
        $wpdb->prefix . 'novel_proofreading_persons';

    $found = $wpdb->get_var(
        $wpdb->prepare(
            "
            SELECT
                COUNT(*)

            FROM
                {$table}

            WHERE
                    id = %d
                AND book_id = %d
            ",
            $person_id,
            $book_id
        )
    );

    return intval($found) > 0;
}

function novel_proofreading_get_person_alias_mappings() {
    global $wpdb;

    $items = [];

    $table_mapping =
        $wpdb->prefix . 'novel_proofreading_person_alias_mapping';
    $table_books =
        $wpdb->prefix . 'novel_proofreading_books';
    $table_persons =
        $wpdb->prefix . 'novel_proofreading_persons';

    $result = $wpdb->get_results(
        "
        SELECT
            pam.*,
            b.title AS book_title,
            p.name AS person_name,
            p.alias AS person_alias,
            ap.name AS alias_name,
            ap.alias AS alias_alias

        FROM
            {$table_mapping} pam
        LEFT JOIN
            {$table_books} b ON b.id = pam.book_id
        LEFT JOIN
            {$table_persons} p ON p.id = pam.person_id
        LEFT JOIN
            {$table_persons} ap ON ap.id = pam.alias_person_id

        ORDER BY
            pam.book_id,
            pam.id
        "
    );

    foreach ($result as $row) {
        $items[] = [
            'id' => intval($row->id),
            'book_id' => intval($row->book_id),
            'book_title' => isset($row->book_title) ? $row->book_title : '',
            'person_id' => intval($row->person_id),
            'alias_person_id' => intval($row->alias_person_id),
            'person_label' => novel_proofreading_format_person_label(
                $row->person_name,
                $row->person_alias
            ),
            'alias_label' => novel_proofreading_format_person_label(
                $row->alias_name,
                $row->alias_alias
            )
        ];
    }

    return $items;
}

function novel_proofreading_sanitize_person_alias_mapping_data() {
    $book_id = intval($_POST['book_id'] ?? 0);

    if ($book_id <= 0) {
        return new WP_Error(
            'missing_person_alias_mapping_book',
            __( 'Book is required.', 'novel-proofreading' )
        );
    }

    $person_id = intval($_POST['person_id'] ?? 0);
    $alias_person_id = intval($_POST['alias_person_id'] ?? 0);

    if ($person_id <= 0 || $alias_person_id <= 0) {
        return new WP_Error(
            'missing_person_alias_mapping_person',
            __( 'Name and alias are required.', 'novel-proofreading' )
        );
    }

    if ($person_id === $alias_person_id) {
        return new WP_Error(
            'invalid_person_alias_mapping_same_person',
            __( 'Name and alias must be different persons.', 'novel-proofreading' )
        );
    }

    if (
        ! novel_proofreading_person_belongs_to_book($person_id, $book_id) ||
        ! novel_proofreading_person_belongs_to_book($alias_person_id, $book_id)
    ) {
        return new WP_Error(
            'invalid_person_alias_mapping_book',
            __( 'Name and alias must belong to the selected book.', 'novel-proofreading' )
        );
    }

    return [
        'book_id' => $book_id,
        'person_id' => $person_id,
        'alias_person_id' => $alias_person_id
    ];
}

function novel_proofreading_add_person_alias_mapping() {
    global $wpdb;

    $data = novel_proofreading_sanitize_person_alias_mapping_data();

    if (is_wp_error($data)) {
        return $data->get_error_message();
    }

    $table =
        $wpdb->prefix . 'novel_proofreading_person_alias_mapping';

    $now = current_time(
        'mysql',
        true
    );

    $result = $wpdb->insert(
        $table,
        array_merge(
            $data,
            [
                'created_at' => $now,
                'created_by' => get_current_user_id(),
                'updated_at' => $now,
                'updated_by' => get_current_user_id()
            ]
        ),
        [
            '%d',
            '%d',
            '%d',
            '%s',
            '%d',
            '%s',
            '%d'
        ]
    );

    if ($result === false) {
        error_log(
            'INSERT ERROR: ' . $wpdb->last_error
        );

        return __( 'Person alias mapping could not be added.', 'novel-proofreading' );
    }

    return __( 'Person alias mapping added.', 'novel-proofreading' );
}

function novel_proofreading_update_person_alias_mapping($id) {
    global $wpdb;

    if ($id <= 0) {
        return __( 'Person alias mapping could not be updated.', 'novel-proofreading' );
    }

    $data = novel_proofreading_sanitize_person_alias_mapping_data();

    if (is_wp_error($data)) {
        return $data->get_error_message();
    }

    $table =
        $wpdb->prefix . 'novel_proofreading_person_alias_mapping';

    $result = $wpdb->update(
        $table,
        array_merge(
            $data,
            [
                'updated_at' => current_time(
                    'mysql',
                    true
                ),
                'updated_by' => get_current_user_id()
            ]
        ),
        [
            'id' => $id
        ],
        [
            '%d',
            '%d',
            '%d',
            '%s',
            '%d'
        ],
        [
            '%d'
        ]
    );

    if ($result === false) {
        error_log(
            'UPDATE ERROR: ' . $wpdb->last_error
        );

        return __( 'Person alias mapping could not be updated.', 'novel-proofreading' );
    }

    return __( 'Person alias mapping updated.', 'novel-proofreading' );
}

function novel_proofreading_remove_person_alias_mapping($id) {
    global $wpdb;

    $table =
        $wpdb->prefix . 'novel_proofreading_person_alias_mapping';

    $wpdb->query(
        $wpdb->prepare(
            "
            DELETE FROM {$table}

            WHERE
                id = %d
            ",
            $id
        )
    );

    return __( 'Person alias mapping deleted.', 'novel-proofreading' );
}

function novel_proofreading_get_type_options($category) {
    global $wpdb;

    $table =
        $wpdb->prefix . 'novel_proofreading_types';

    return $wpdb->get_results(
        $wpdb->prepare(
            "
            SELECT
                name

            FROM
                {$table}

            WHERE
                category = %s

            ORDER BY name
            ",
            $category
        ),
        ARRAY_A
    );
}

function novel_proofreading_get_locations() {
    global $wpdb;

    $items = [];

    $table_locations =
        $wpdb->prefix . 'novel_proofreading_locations';
    $table_books =
        $wpdb->prefix . 'novel_proofreading_books';

    $result = $wpdb->get_results(
        "
        SELECT
            l.*,
            b.title AS book_title

        FROM
            {$table_locations} l
        LEFT JOIN
            {$table_books} b ON b.id = l.book_id

        ORDER BY l.id
        "
    );

    foreach ($result as $row) {

        $items[] = [
            'id' => intval($row->id),

            'book_id' => intval($row->book_id),

            'book_title' => isset($row->book_title) ? $row->book_title : '',

            'name' => $row->name,

            'alias' => $row->alias,

            'area' => $row->area,

            'region' => $row->region,

            'description' => $row->description,

            'is_in_alternative_universe' => $row->is_in_alternative_universe,

            'is_inaccurate' => $row->is_inaccurate
        ];
    }

    return $items;
}

function novel_proofreading_sanitize_location_data() {
    $name = sanitize_text_field(
        wp_unslash($_POST['name'] ?? '')
    );
    $alias = sanitize_text_field(
        wp_unslash($_POST['alias'] ?? '')
    );

    if ($name === '' && $alias === '') {
        return new WP_Error(
            'missing_location_name',
            __( 'Name or alias is required.', 'novel-proofreading' )
        );
    }

    $book_id = intval($_POST['book_id'] ?? 0);

    if ($book_id <= 0) {
        return new WP_Error(
            'missing_location_book',
            __( 'Book is required.', 'novel-proofreading' )
        );
    }

    return [
        'book_id' => $book_id,

        'name' => $name,

        'alias' => $alias,

        'area' => sanitize_text_field(
            wp_unslash($_POST['area'] ?? '')
        ),

        'region' => sanitize_text_field(
            wp_unslash($_POST['region'] ?? '')
        ),

        'description' => sanitize_textarea_field(
            wp_unslash($_POST['description'] ?? '')
        ),

        'is_in_alternative_universe' => isset($_POST['is_in_alternative_universe']) ? 'Y' : 'N',

        'is_inaccurate' => isset($_POST['is_inaccurate']) ? 'Y' : 'N'
    ];
}

function novel_proofreading_add_location() {
    global $wpdb;

    $data = novel_proofreading_sanitize_location_data();

    if (is_wp_error($data)) {
        return $data->get_error_message();
    }

    $table =
        $wpdb->prefix . 'novel_proofreading_locations';

    $now = current_time(
        'mysql',
        true
    );

    $result = $wpdb->insert(
        $table,
        array_merge(
            $data,
            [
                'created_at' => $now,

                'created_by' => get_current_user_id(),

                'updated_at' => $now,

                'updated_by' => get_current_user_id()
            ]
        ),
        [
            '%d',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%d',
            '%s',
            '%d'
        ]
    );

    if ($result === false) {
        error_log(
            'INSERT ERROR: ' . $wpdb->last_error
        );

        return __( 'Location could not be added.', 'novel-proofreading' );
    }

    return __( 'Location added.', 'novel-proofreading' );
}

function novel_proofreading_update_location($id) {
    global $wpdb;

    if ($id <= 0) {
        return __( 'Location could not be updated.', 'novel-proofreading' );
    }

    $data = novel_proofreading_sanitize_location_data();

    if (is_wp_error($data)) {
        return $data->get_error_message();
    }

    $table =
        $wpdb->prefix . 'novel_proofreading_locations';

    $result = $wpdb->update(
        $table,
        array_merge(
            $data,
            [
                'updated_at' => current_time(
                    'mysql',
                    true
                ),

                'updated_by' => get_current_user_id()
            ]
        ),
        [
            'id' => $id
        ],
        [
            '%d',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%d'
        ],
        [
            '%d'
        ]
    );

    if ($result === false) {
        error_log(
            'UPDATE ERROR: ' . $wpdb->last_error
        );

        return __( 'Location could not be updated.', 'novel-proofreading' );
    }

    return __( 'Location updated.', 'novel-proofreading' );
}

function novel_proofreading_remove_location($id) {
    global $wpdb;

    $table =
        $wpdb->prefix . 'novel_proofreading_locations';

    $wpdb->query(
        $wpdb->prepare(
            "
            DELETE FROM {$table}

            WHERE
                id = %d
            ",
            $id
        )
    );

    return __( 'Location deleted.', 'novel-proofreading' );
}

function novel_proofreading_get_relics() {
    global $wpdb;

    $items = [];

    $table_relics =
        $wpdb->prefix . 'novel_proofreading_relics';
    $table_books =
        $wpdb->prefix . 'novel_proofreading_books';

    $result = $wpdb->get_results(
        "
        SELECT
            r.*,
            b.title AS book_title

        FROM
            {$table_relics} r
        LEFT JOIN
            {$table_books} b ON b.id = r.book_id

        ORDER BY r.id
        "
    );

    foreach ($result as $row) {
        $items[] = [
            'id' => intval($row->id),
            'book_id' => intval($row->book_id),
            'book_title' => isset($row->book_title) ? $row->book_title : '',
            'relic_name' => $row->relic_name,
            'description' => $row->description,
            'is_inaccurate' => $row->is_inaccurate
        ];
    }

    return $items;
}

function novel_proofreading_sanitize_relic_data() {
    $book_id = intval($_POST['book_id'] ?? 0);

    if ($book_id <= 0) {
        return new WP_Error(
            'missing_relic_book',
            __( 'Book is required.', 'novel-proofreading' )
        );
    }

    $relic_name = sanitize_text_field(
        wp_unslash($_POST['relic_name'] ?? '')
    );

    if ($relic_name === '') {
        return new WP_Error(
            'missing_relic_name',
            __( 'Relic name is required.', 'novel-proofreading' )
        );
    }

    return [
        'book_id' => $book_id,
        'relic_name' => $relic_name,
        'description' => sanitize_textarea_field(
            wp_unslash($_POST['description'] ?? '')
        ),
        'is_inaccurate' => isset($_POST['is_inaccurate']) ? 'Y' : 'N'
    ];
}

function novel_proofreading_add_relic() {
    global $wpdb;

    $data = novel_proofreading_sanitize_relic_data();

    if (is_wp_error($data)) {
        return $data->get_error_message();
    }

    $table =
        $wpdb->prefix . 'novel_proofreading_relics';

    $now = current_time(
        'mysql',
        true
    );

    $result = $wpdb->insert(
        $table,
        array_merge(
            $data,
            [
                'created_at' => $now,
                'created_by' => get_current_user_id(),
                'updated_at' => $now,
                'updated_by' => get_current_user_id()
            ]
        ),
        [
            '%d',
            '%s',
            '%s',
            '%s',
            '%s',
            '%d',
            '%s',
            '%d'
        ]
    );

    if ($result === false) {
        error_log(
            'INSERT ERROR: ' . $wpdb->last_error
        );

        return __( 'Relic could not be added.', 'novel-proofreading' );
    }

    return __( 'Relic added.', 'novel-proofreading' );
}

function novel_proofreading_update_relic($id) {
    global $wpdb;

    if ($id <= 0) {
        return __( 'Relic could not be updated.', 'novel-proofreading' );
    }

    $data = novel_proofreading_sanitize_relic_data();

    if (is_wp_error($data)) {
        return $data->get_error_message();
    }

    $table =
        $wpdb->prefix . 'novel_proofreading_relics';

    $result = $wpdb->update(
        $table,
        array_merge(
            $data,
            [
                'updated_at' => current_time(
                    'mysql',
                    true
                ),
                'updated_by' => get_current_user_id()
            ]
        ),
        [
            'id' => $id
        ],
        [
            '%d',
            '%s',
            '%s',
            '%s',
            '%s',
            '%d'
        ],
        [
            '%d'
        ]
    );

    if ($result === false) {
        error_log(
            'UPDATE ERROR: ' . $wpdb->last_error
        );

        return __( 'Relic could not be updated.', 'novel-proofreading' );
    }

    return __( 'Relic updated.', 'novel-proofreading' );
}

function novel_proofreading_remove_relic($id) {
    global $wpdb;

    $table =
        $wpdb->prefix . 'novel_proofreading_relics';

    $wpdb->query(
        $wpdb->prepare(
            "
            DELETE FROM {$table}

            WHERE
                id = %d
            ",
            $id
        )
    );

    return __( 'Relic deleted.', 'novel-proofreading' );
}

function novel_proofreading_get_datetimes() {
    global $wpdb;

    $items = [];

    $table_datetimes =
        $wpdb->prefix . 'novel_proofreading_datetimes';
    $table_books =
        $wpdb->prefix . 'novel_proofreading_books';

    $result = $wpdb->get_results(
        "
        SELECT
            d.*,
            b.title AS book_title

        FROM
            {$table_datetimes} d
        LEFT JOIN
            {$table_books} b ON b.id = d.book_id

        ORDER BY d.id
        "
    );

    foreach ($result as $row) {

        $items[] = [
            'id' => intval($row->id),

            'book_id' => intval($row->book_id),

            'book_title' => isset($row->book_title) ? $row->book_title : '',

            'name' => $row->name,

            'time_description' => $row->time_description,

            'description' => $row->description,

            'time_type' => $row->time_type,

            'is_inaccurate' => $row->is_inaccurate
        ];
    }

    return $items;
}

function novel_proofreading_sanitize_datetime_data() {
    $book_id = intval($_POST['book_id'] ?? 0);

    if ($book_id <= 0) {
        return new WP_Error(
            'missing_datetime_book',
            __( 'Book is required.', 'novel-proofreading' )
        );
    }

    $name = sanitize_text_field(
        wp_unslash($_POST['name'] ?? '')
    );

    if ($name === '') {
        return new WP_Error(
            'missing_datetime_name',
            __( 'Name is required.', 'novel-proofreading' )
        );
    }

    $time_type = sanitize_text_field(
        wp_unslash($_POST['time_type'] ?? '')
    );

    if ($time_type === '') {
        return new WP_Error(
            'missing_datetime_type',
            __( 'Time type is required.', 'novel-proofreading' )
        );
    }

    return [
        'book_id' => $book_id,

        'name' => $name,

        'time_description' => sanitize_textarea_field(
            wp_unslash($_POST['time_description'] ?? '')
        ),

        'description' => sanitize_textarea_field(
            wp_unslash($_POST['description'] ?? '')
        ),

        'time_type' => $time_type,

        'is_inaccurate' => isset($_POST['is_inaccurate']) ? 'Y' : 'N'
    ];
}

function novel_proofreading_add_datetime() {
    global $wpdb;

    $data = novel_proofreading_sanitize_datetime_data();

    if (is_wp_error($data)) {
        return $data->get_error_message();
    }

    $table =
        $wpdb->prefix . 'novel_proofreading_datetimes';

    $now = current_time(
        'mysql',
        true
    );

    $result = $wpdb->insert(
        $table,
        array_merge(
            $data,
            [
                'created_at' => $now,

                'created_by' => get_current_user_id(),

                'updated_at' => $now,

                'updated_by' => get_current_user_id()
            ]
        ),
        [
            '%d',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%d',
            '%s',
            '%d'
        ]
    );

    if ($result === false) {
        error_log(
            'INSERT ERROR: ' . $wpdb->last_error
        );

        return __( 'Datetime could not be added.', 'novel-proofreading' );
    }

    return __( 'Datetime added.', 'novel-proofreading' );
}

function novel_proofreading_update_datetime($id) {
    global $wpdb;

    if ($id <= 0) {
        return __( 'Datetime could not be updated.', 'novel-proofreading' );
    }

    $data = novel_proofreading_sanitize_datetime_data();

    if (is_wp_error($data)) {
        return $data->get_error_message();
    }

    $table =
        $wpdb->prefix . 'novel_proofreading_datetimes';

    $result = $wpdb->update(
        $table,
        array_merge(
            $data,
            [
                'updated_at' => current_time(
                    'mysql',
                    true
                ),

                'updated_by' => get_current_user_id()
            ]
        ),
        [
            'id' => $id
        ],
        [
            '%d',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%d'
        ],
        [
            '%d'
        ]
    );

    if ($result === false) {
        error_log(
            'UPDATE ERROR: ' . $wpdb->last_error
        );

        return __( 'Datetime could not be updated.', 'novel-proofreading' );
    }

    return __( 'Datetime updated.', 'novel-proofreading' );
}

function novel_proofreading_remove_datetime($id) {
    global $wpdb;

    $table =
        $wpdb->prefix . 'novel_proofreading_datetimes';

    $wpdb->query(
        $wpdb->prepare(
            "
            DELETE FROM {$table}

            WHERE
                id = %d
            ",
            $id
        )
    );

    return __( 'Datetime deleted.', 'novel-proofreading' );
}

function novel_proofreading_get_professions() {
    global $wpdb;

    $items = [];

    $table_professions =
        $wpdb->prefix . 'novel_proofreading_professions';
    $table_books =
        $wpdb->prefix . 'novel_proofreading_books';
    $table_persons =
        $wpdb->prefix . 'novel_proofreading_persons';

    $result = $wpdb->get_results(
        "
        SELECT
            pr.*,
            b.title AS book_title,
            p.name AS person_name,
            p.alias AS person_alias

        FROM
            {$table_professions} pr
        LEFT JOIN
            {$table_books} b ON b.id = pr.book_id
        LEFT JOIN
            {$table_persons} p ON p.id = pr.person_id

        ORDER BY pr.id
        "
    );

    foreach ($result as $row) {

        $items[] = [
            'id' => intval($row->id),

            'book_id' => intval($row->book_id),

            'book_title' => isset($row->book_title) ? $row->book_title : '',

            'person_id' => intval($row->person_id),

            'person_name' => isset($row->person_name) ? $row->person_name : '',

            'person_alias' => isset($row->person_alias) ? $row->person_alias : '',

            'profession_name' => $row->profession_name,

            'description' => $row->description,

            'is_inaccurate' => $row->is_inaccurate
        ];
    }

    return $items;
}

function novel_proofreading_sanitize_profession_data() {
    $book_id = intval($_POST['book_id'] ?? 0);

    if ($book_id <= 0) {
        return new WP_Error(
            'missing_profession_book',
            __( 'Book is required.', 'novel-proofreading' )
        );
    }

    $person_id = intval($_POST['person_id'] ?? 0);

    if ($person_id <= 0) {
        return new WP_Error(
            'missing_profession_person',
            __( 'Person is required.', 'novel-proofreading' )
        );
    }

    $profession_name = sanitize_text_field(
        wp_unslash($_POST['profession_name'] ?? '')
    );

    if ($profession_name === '') {
        return new WP_Error(
            'missing_profession_name',
            __( 'Profession name is required.', 'novel-proofreading' )
        );
    }

    return [
        'book_id' => $book_id,

        'person_id' => $person_id,

        'profession_name' => $profession_name,

        'description' => sanitize_textarea_field(
            wp_unslash($_POST['description'] ?? '')
        ),

        'is_inaccurate' => isset($_POST['is_inaccurate']) ? 'Y' : 'N'
    ];
}

function novel_proofreading_add_profession() {
    global $wpdb;

    $data = novel_proofreading_sanitize_profession_data();

    if (is_wp_error($data)) {
        return $data->get_error_message();
    }

    $table =
        $wpdb->prefix . 'novel_proofreading_professions';

    $now = current_time(
        'mysql',
        true
    );

    $result = $wpdb->insert(
        $table,
        array_merge(
            $data,
            [
                'created_at' => $now,

                'created_by' => get_current_user_id(),

                'updated_at' => $now,

                'updated_by' => get_current_user_id()
            ]
        ),
        [
            '%d',
            '%d',
            '%s',
            '%s',
            '%s',
            '%s',
            '%d',
            '%s',
            '%d'
        ]
    );

    if ($result === false) {
        error_log(
            'INSERT ERROR: ' . $wpdb->last_error
        );

        return __( 'Profession could not be added.', 'novel-proofreading' );
    }

    return __( 'Profession added.', 'novel-proofreading' );
}

function novel_proofreading_update_profession($id) {
    global $wpdb;

    if ($id <= 0) {
        return __( 'Profession could not be updated.', 'novel-proofreading' );
    }

    $data = novel_proofreading_sanitize_profession_data();

    if (is_wp_error($data)) {
        return $data->get_error_message();
    }

    $table =
        $wpdb->prefix . 'novel_proofreading_professions';

    $result = $wpdb->update(
        $table,
        array_merge(
            $data,
            [
                'updated_at' => current_time(
                    'mysql',
                    true
                ),

                'updated_by' => get_current_user_id()
            ]
        ),
        [
            'id' => $id
        ],
        [
            '%d',
            '%d',
            '%s',
            '%s',
            '%s',
            '%s',
            '%d'
        ],
        [
            '%d'
        ]
    );

    if ($result === false) {
        error_log(
            'UPDATE ERROR: ' . $wpdb->last_error
        );

        return __( 'Profession could not be updated.', 'novel-proofreading' );
    }

    return __( 'Profession updated.', 'novel-proofreading' );
}

function novel_proofreading_remove_profession($id) {
    global $wpdb;

    $table =
        $wpdb->prefix . 'novel_proofreading_professions';

    $wpdb->query(
        $wpdb->prepare(
            "
            DELETE FROM {$table}

            WHERE
                id = %d
            ",
            $id
        )
    );

    return __( 'Profession deleted.', 'novel-proofreading' );
}

function novel_proofreading_get_chain_role_options() {
    return [
        'OPENING' => __( 'Opening', 'novel-proofreading' ),
        'STEP' => __( 'Step', 'novel-proofreading' ),
        'RETURN' => __( 'Return', 'novel-proofreading' ),
        'CLOSING' => __( 'Closing', 'novel-proofreading' )
    ];
}

function novel_proofreading_sanitize_chain_role($role) {
    $role = sanitize_text_field(
        wp_unslash($role)
    );

    $roles = novel_proofreading_get_chain_role_options();

    return isset($roles[$role]) ? $role : 'STEP';
}

function novel_proofreading_storyline_belongs_to_book($storyline_id, $book_id) {
    global $wpdb;

    if ($storyline_id <= 0) {
        return true;
    }

    $table =
        $wpdb->prefix . 'novel_proofreading_storylines';

    $found = $wpdb->get_var(
        $wpdb->prepare(
            "
            SELECT
                COUNT(*)

            FROM
                {$table}

            WHERE
                    id = %d
                AND book_id = %d
            ",
            $storyline_id,
            $book_id
        )
    );

    return intval($found) > 0;
}

function novel_proofreading_get_events() {
    global $wpdb;

    $items = [];

    $table_events =
        $wpdb->prefix . 'novel_proofreading_events';
    $table_books =
        $wpdb->prefix . 'novel_proofreading_books';
    $table_storylines =
        $wpdb->prefix . 'novel_proofreading_storylines';

    $result = $wpdb->get_results(
        "
        SELECT
            e.*,
            b.title AS book_title,
            st.storyline_name AS storyline_name

        FROM
            {$table_events} e
        LEFT JOIN
            {$table_books} b ON b.id = e.book_id
        LEFT JOIN
            {$table_storylines} st ON st.id = e.storyline_id

        ORDER BY e.id
        "
    );

    foreach ($result as $row) {

        $items[] = [
            'id' => intval($row->id),

            'book_id' => intval($row->book_id),

            'book_title' => isset($row->book_title) ? $row->book_title : '',

            'storyline_id' => intval($row->storyline_id),

            'storyline_name' => isset($row->storyline_name) ? $row->storyline_name : '',

            'sequence_no' => intval($row->sequence_no),

            'chain_role' => isset($row->chain_role) ? $row->chain_role : 'STEP',

            'is_narrative' => isset($row->is_narrative) ? $row->is_narrative : 'N',

            'event_name' => $row->event_name,

            'description' => $row->description
        ];
    }

    return $items;
}

function novel_proofreading_sanitize_event_data() {
    $book_id = intval($_POST['book_id'] ?? 0);

    if ($book_id <= 0) {
        return new WP_Error(
            'missing_event_book',
            __( 'Book is required.', 'novel-proofreading' )
        );
    }

    $storyline_id = intval($_POST['storyline_id'] ?? 0);

    if ($storyline_id < 0) {
        return new WP_Error(
            'missing_event_storyline',
            __( 'Storyline is invalid.', 'novel-proofreading' )
        );
    }

    if (
        $storyline_id > 0 &&
        ! novel_proofreading_storyline_belongs_to_book($storyline_id, $book_id)
    ) {
        return new WP_Error(
            'invalid_event_storyline_book',
            __( 'Storyline must belong to the selected book.', 'novel-proofreading' )
        );
    }

    $sequence_no = intval($_POST['sequence_no'] ?? 0);

    if ($sequence_no < 0) {
        return new WP_Error(
            'invalid_event_sequence',
            __( 'Sequence number cannot be negative.', 'novel-proofreading' )
        );
    }

    $event_name = sanitize_text_field(
        wp_unslash($_POST['event_name'] ?? '')
    );

    if ($event_name === '') {
        return new WP_Error(
            'missing_event_name',
            __( 'Event name is required.', 'novel-proofreading' )
        );
    }

    return [
        'book_id' => $book_id,

        'storyline_id' => $storyline_id,

        'sequence_no' => $sequence_no,

        'chain_role' => novel_proofreading_sanitize_chain_role(
            $_POST['chain_role'] ?? 'STEP'
        ),

        'is_narrative' => isset($_POST['is_narrative']) &&
            sanitize_text_field(wp_unslash($_POST['is_narrative'])) === 'Y'
                ? 'Y'
                : 'N',

        'event_name' => $event_name,

        'description' => sanitize_textarea_field(
            wp_unslash($_POST['description'] ?? '')
        )
    ];
}

function novel_proofreading_add_event() {
    global $wpdb;

    $data = novel_proofreading_sanitize_event_data();

    if (is_wp_error($data)) {
        return $data->get_error_message();
    }

    $table =
        $wpdb->prefix . 'novel_proofreading_events';

    $now = current_time(
        'mysql',
        true
    );

    $result = $wpdb->insert(
        $table,
        array_merge(
            $data,
            [
                'created_at' => $now,

                'created_by' => get_current_user_id(),

                'updated_at' => $now,

                'updated_by' => get_current_user_id()
            ]
        ),
        [
            '%d',
            '%d',
            '%d',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%d',
            '%s',
            '%d'
        ]
    );

    if ($result === false) {
        error_log(
            'INSERT ERROR: ' . $wpdb->last_error
        );

        return __( 'Event could not be added.', 'novel-proofreading' );
    }

    novel_proofreading_sync_storyline_main_event(
        intval($wpdb->insert_id),
        $data
    );

    return __( 'Event added.', 'novel-proofreading' );
}

function novel_proofreading_update_event($id) {
    global $wpdb;

    if ($id <= 0) {
        return __( 'Event could not be updated.', 'novel-proofreading' );
    }

    $data = novel_proofreading_sanitize_event_data();

    if (is_wp_error($data)) {
        return $data->get_error_message();
    }

    $table =
        $wpdb->prefix . 'novel_proofreading_events';

    $result = $wpdb->update(
        $table,
        array_merge(
            $data,
            [
                'updated_at' => current_time(
                    'mysql',
                    true
                ),

                'updated_by' => get_current_user_id()
            ]
        ),
        [
            'id' => $id
        ],
        [
            '%d',
            '%d',
            '%d',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%d'
        ],
        [
            '%d'
        ]
    );

    if ($result === false) {
        error_log(
            'UPDATE ERROR: ' . $wpdb->last_error
        );

        return __( 'Event could not be updated.', 'novel-proofreading' );
    }

    novel_proofreading_sync_storyline_main_event(
        $id,
        $data
    );

    return __( 'Event updated.', 'novel-proofreading' );
}

function novel_proofreading_sync_storyline_main_event($event_id, $event_data) {
    global $wpdb;

    if (
        intval($event_data['storyline_id']) <= 0 ||
        $event_data['chain_role'] !== 'OPENING'
    ) {
        return;
    }

    $table =
        $wpdb->prefix . 'novel_proofreading_storylines';

    $wpdb->update(
        $table,
        [
            'main_event' => $event_id,

            'updated_at' => current_time(
                'mysql',
                true
            ),

            'updated_by' => get_current_user_id()
        ],
        [
            'id' => intval($event_data['storyline_id'])
        ],
        [
            '%d',
            '%s',
            '%d'
        ],
        [
            '%d'
        ]
    );
}

function novel_proofreading_remove_event($id) {
    global $wpdb;

    $table =
        $wpdb->prefix . 'novel_proofreading_events';

    $wpdb->query(
        $wpdb->prepare(
            "
            DELETE FROM {$table}

            WHERE
                id = %d
            ",
            $id
        )
    );

    return __( 'Event deleted.', 'novel-proofreading' );
}

function novel_proofreading_get_storylines() {
    global $wpdb;

    $items = [];

    $table_storylines =
        $wpdb->prefix . 'novel_proofreading_storylines';
    $table_books =
        $wpdb->prefix . 'novel_proofreading_books';
    $table_events =
        $wpdb->prefix . 'novel_proofreading_events';

    $result = $wpdb->get_results(
        "
        SELECT
            st.*,
            b.title AS book_title,
            e.event_name AS event_name

        FROM
            {$table_storylines} st
        LEFT JOIN
            {$table_books} b ON b.id = st.book_id
        LEFT JOIN
            {$table_events} e ON e.id = st.main_event

        ORDER BY st.book_id, st.id
        "
    );

    foreach ($result as $row) {

        $items[] = [
            'id' => intval($row->id),

            'book_id' => intval($row->book_id),

            'book_title' => isset($row->book_title) ? $row->book_title : '',

            'main_event' => intval($row->main_event),

            'storyline_name' => isset($row->storyline_name) ? $row->storyline_name : '',

            'event_name' => $row->event_name,

            'description' => $row->description
        ];
    }

    return $items;
}

function novel_proofreading_sanitize_storyline_data() {
    $book_id = intval($_POST['book_id'] ?? 0);

    if ($book_id <= 0) {
        return new WP_Error(
            'missing_storyline_book',
            __( 'Book is required.', 'novel-proofreading' )
        );
    }

    $storyline_name = sanitize_text_field(
        wp_unslash($_POST['storyline_name'] ?? '')
    );

    if ($storyline_name === '') {
        return new WP_Error(
            'missing_storyline_name',
            __( 'Storyline name is required.', 'novel-proofreading' )
        );
    }

    $main_event = intval($_POST['main_event'] ?? 0);

    if (
        $main_event > 0 &&
        ! novel_proofreading_event_belongs_to_book($main_event, $book_id)
    ) {
        return new WP_Error(
            'invalid_storyline_main_event_book',
            __( 'Main event must belong to the selected book.', 'novel-proofreading' )
        );
    }

    return [
        'book_id' => $book_id,

        'storyline_name' => $storyline_name,

        'main_event' => $main_event,

        'description' => sanitize_textarea_field(
            wp_unslash($_POST['description'] ?? '')
        )
    ];
}

function novel_proofreading_event_belongs_to_book($event_id, $book_id) {
    global $wpdb;

    if ($event_id <= 0) {
        return true;
    }

    $table =
        $wpdb->prefix . 'novel_proofreading_events';

    $found = $wpdb->get_var(
        $wpdb->prepare(
            "
            SELECT
                COUNT(*)

            FROM
                {$table}

            WHERE
                    id = %d
                AND book_id = %d
            ",
            $event_id,
            $book_id
        )
    );

    return intval($found) > 0;
}

function novel_proofreading_add_storyline() {
    global $wpdb;

    $data = novel_proofreading_sanitize_storyline_data();

    if (is_wp_error($data)) {
        return $data->get_error_message();
    }

    $table =
        $wpdb->prefix . 'novel_proofreading_storylines';

    $now = current_time(
        'mysql',
        true
    );

    $result = $wpdb->insert(
        $table,
        array_merge(
            $data,
            [
                'created_at' => $now,

                'created_by' => get_current_user_id(),

                'updated_at' => $now,

                'updated_by' => get_current_user_id()
            ]
        ),
        [
            '%d',
            '%s',
            '%d',
            '%s',
            '%s',
            '%d',
            '%s',
            '%d'
        ]
    );

    if ($result === false) {
        error_log(
            'INSERT ERROR: ' . $wpdb->last_error
        );

        return __( 'Storyline could not be added.', 'novel-proofreading' );
    }

    novel_proofreading_sync_event_from_storyline_main_event(
        intval($wpdb->insert_id),
        $data
    );

    return __( 'Storyline added.', 'novel-proofreading' );
}

function novel_proofreading_update_storyline($id) {
    global $wpdb;

    if ($id <= 0) {
        return __( 'Storyline could not be updated.', 'novel-proofreading' );
    }

    $data = novel_proofreading_sanitize_storyline_data();

    if (is_wp_error($data)) {
        return $data->get_error_message();
    }

    $table =
        $wpdb->prefix . 'novel_proofreading_storylines';

    $result = $wpdb->update(
        $table,
        array_merge(
            $data,
            [
                'updated_at' => current_time(
                    'mysql',
                    true
                ),

                'updated_by' => get_current_user_id()
            ]
        ),
        [
            'id' => $id
        ],
        [
            '%d',
            '%s',
            '%d',
            '%s',
            '%s',
            '%d'
        ],
        [
            '%d'
        ]
    );

    if ($result === false) {
        error_log(
            'UPDATE ERROR: ' . $wpdb->last_error
        );

        return __( 'Storyline could not be updated.', 'novel-proofreading' );
    }

    novel_proofreading_sync_event_from_storyline_main_event(
        $id,
        $data
    );

    return __( 'Storyline updated.', 'novel-proofreading' );
}

function novel_proofreading_sync_event_from_storyline_main_event($storyline_id, $storyline_data) {
    global $wpdb;

    if (intval($storyline_data['main_event']) <= 0) {
        return;
    }

    $table =
        $wpdb->prefix . 'novel_proofreading_events';

    $wpdb->update(
        $table,
        [
            'storyline_id' => $storyline_id,

            'chain_role' => 'OPENING',

            'updated_at' => current_time(
                'mysql',
                true
            ),

            'updated_by' => get_current_user_id()
        ],
        [
            'id' => intval($storyline_data['main_event'])
        ],
        [
            '%d',
            '%s',
            '%s',
            '%d'
        ],
        [
            '%d'
        ]
    );
}

function novel_proofreading_remove_storyline($id) {
    global $wpdb;

    $table =
        $wpdb->prefix . 'novel_proofreading_storylines';

    $wpdb->query(
        $wpdb->prepare(
            "
            DELETE FROM {$table}

            WHERE
                id = %d
            ",
            $id
        )
    );

    return __( 'Storyline deleted.', 'novel-proofreading' );
}

function novel_proofreading_update_chain_event($id) {
    global $wpdb;

    if ($id <= 0) {
        return __( 'Event chain data could not be updated.', 'novel-proofreading' );
    }

    $table =
        $wpdb->prefix . 'novel_proofreading_events';

    $event = $wpdb->get_row(
        $wpdb->prepare(
            "
            SELECT
                book_id,
                event_name,
                description

            FROM
                {$table}

            WHERE
                id = %d
            ",
            $id
        )
    );

    if (! $event) {
        return __( 'Event could not be updated.', 'novel-proofreading' );
    }

    $sequence_no = intval($_POST['sequence_no'] ?? 0);

    if ($sequence_no < 0) {
        return __( 'Sequence number cannot be negative.', 'novel-proofreading' );
    }

    $storyline_id = intval($_POST['storyline_id'] ?? 0);

    if (
        $storyline_id > 0 &&
        ! novel_proofreading_storyline_belongs_to_book($storyline_id, intval($event->book_id))
    ) {
        return __( 'Storyline must belong to the selected book.', 'novel-proofreading' );
    }

    $data = [
        'storyline_id' => $storyline_id,

        'sequence_no' => $sequence_no,

        'chain_role' => novel_proofreading_sanitize_chain_role(
            $_POST['chain_role'] ?? 'STEP'
        ),

        'updated_at' => current_time(
            'mysql',
            true
        ),

        'updated_by' => get_current_user_id()
    ];

    $result = $wpdb->update(
        $table,
        $data,
        [
            'id' => $id
        ],
        [
            '%d',
            '%d',
            '%s',
            '%s',
            '%d'
        ],
        [
            '%d'
        ]
    );

    if ($result === false) {
        error_log(
            'UPDATE ERROR: ' . $wpdb->last_error
        );

        return __( 'Event chain data could not be updated.', 'novel-proofreading' );
    }

    novel_proofreading_sync_storyline_main_event(
        $id,
        [
            'storyline_id' => $storyline_id,
            'chain_role' => $data['chain_role']
        ]
    );

    return __( 'Event chain data updated.', 'novel-proofreading' );
}

function novel_proofreading_format_mapping_reference($chapter, $page) {
    $parts = [];

    if ($chapter !== null && $chapter !== '') {
        $parts[] = sprintf(
            /* translators: %s: chapter */
            __( 'Chapter: %s', 'novel-proofreading' ),
            $chapter
        );
    }

    if ($page !== null && $page !== '') {
        $parts[] = sprintf(
            /* translators: %s: page */
            __( 'Page: %s', 'novel-proofreading' ),
            $page
        );
    }

    return implode(
        ', ',
        $parts
    );
}

function novel_proofreading_get_storyline_chains($book_id = 0) {
    global $wpdb;

    $chains = [];

    $table_storylines =
        $wpdb->prefix . 'novel_proofreading_storylines';
    $table_books =
        $wpdb->prefix . 'novel_proofreading_books';
    $table_events =
        $wpdb->prefix . 'novel_proofreading_events';
    $table_mapping =
        $wpdb->prefix . 'novel_proofreading_common_mapping';

    $where = '';
    $params = [];

    if ($book_id > 0) {
        $where = 'WHERE st.book_id = %d';
        $params[] = $book_id;
    }

    $sql = "
        SELECT
            st.id AS storyline_id,
            st.book_id,
            st.storyline_name,
            st.description AS storyline_description,
            b.title AS book_title,
            e.id AS event_id,
            e.sequence_no,
            e.chain_role,
            e.event_name,
            e.description AS event_description,
            cm.page,
            cm.chapter

        FROM
            {$table_storylines} st
        LEFT JOIN
            {$table_books} b ON b.id = st.book_id
        LEFT JOIN
            {$table_events} e ON e.storyline_id = st.id
        LEFT JOIN
            {$table_mapping} cm ON cm.event_id = e.id
                AND cm.type = 'EVENT'

        {$where}

        ORDER BY
            st.book_id,
            st.id,
            e.sequence_no,
            e.id,
            cm.id
    ";

    $rows = empty($params)
        ? $wpdb->get_results($sql)
        : $wpdb->get_results(
            $wpdb->prepare(
                $sql,
                $params
            )
        );

    foreach ($rows as $row) {
        $storyline_id = intval($row->storyline_id);

        if (! isset($chains[$storyline_id])) {
            $chains[$storyline_id] = [
                'id' => $storyline_id,
                'book_id' => intval($row->book_id),
                'book_title' => isset($row->book_title) ? $row->book_title : '',
                'storyline_name' => $row->storyline_name,
                'description' => $row->storyline_description,
                'storyline_references' => [],
                'events' => [],
                'stats' => [
                    'event_count' => 0,
                    'has_opening' => false,
                    'has_return' => false,
                    'has_closing' => false,
                    'has_suggestion' => false,
                    'first_reference' => '',
                    'last_reference' => ''
                ]
            ];
        }

        if ($row->event_id === null) {
            continue;
        }

        $event_id = intval($row->event_id);
        $reference = novel_proofreading_format_mapping_reference(
            $row->chapter,
            $row->page
        );

        if (! isset($chains[$storyline_id]['events'][$event_id])) {
            $chains[$storyline_id]['events'][$event_id] = [
                'id' => $event_id,
                'sequence_no' => intval($row->sequence_no),
                'chain_role' => $row->chain_role ?: 'STEP',
                'event_name' => $row->event_name,
                'description' => $row->event_description,
                'references' => []
            ];

            $chains[$storyline_id]['stats']['event_count']++;
            $chains[$storyline_id]['stats']['has_opening'] =
                $chains[$storyline_id]['stats']['has_opening'] ||
                $row->chain_role === 'OPENING';
            $chains[$storyline_id]['stats']['has_return'] =
                $chains[$storyline_id]['stats']['has_return'] ||
                $row->chain_role === 'RETURN';
            $chains[$storyline_id]['stats']['has_closing'] =
                $chains[$storyline_id]['stats']['has_closing'] ||
                $row->chain_role === 'CLOSING';
        }

        if ($reference !== '') {
            $chains[$storyline_id]['events'][$event_id]['references'][] = $reference;

            if ($chains[$storyline_id]['stats']['first_reference'] === '') {
                $chains[$storyline_id]['stats']['first_reference'] = $reference;
            }

            $chains[$storyline_id]['stats']['last_reference'] = $reference;
        }
    }

    foreach ($chains as $storyline_id => $chain) {
        $storyline_references = $wpdb->get_results(
            $wpdb->prepare(
                "
                SELECT
                    page,
                    chapter

                FROM
                    {$table_mapping}

                WHERE
                        type = 'STORYLINE'
                    AND storyline_id = %d

                ORDER BY id
                ",
                $storyline_id
            )
        );

        foreach ($storyline_references as $reference_row) {
            $reference = novel_proofreading_format_mapping_reference(
                $reference_row->chapter,
                $reference_row->page
            );

            if ($reference === '') {
                continue;
            }

            $chains[$storyline_id]['storyline_references'][] = $reference;

            if ($chains[$storyline_id]['stats']['first_reference'] === '') {
                $chains[$storyline_id]['stats']['first_reference'] = $reference;
            }

            $chains[$storyline_id]['stats']['last_reference'] = $reference;
        }
    }

    foreach ($chains as $storyline_id => $chain) {
        $chains[$storyline_id]['stats']['has_suggestion'] =
            ! empty(novel_proofreading_get_storyline_suggestions($storyline_id));

        $chains[$storyline_id]['events'] = array_values(
            $chain['events']
        );
        $chains[$storyline_id]['storyline_references'] = array_values(
            array_unique($chain['storyline_references'])
        );
    }

    return array_values($chains);
}

function novel_proofreading_get_entity_label($type, $row) {
    if ($type === 'STORYLINE') {
        return $row->storyline_name;
    }

    if ($type === 'EVENT') {
        return $row->event_name;
    }

    if ($type === 'PERSON') {
        return trim($row->person_name . ' ' . $row->person_alias);
    }

    if ($type === 'LOCATION') {
        return trim($row->location_name . ' ' . $row->location_alias);
    }

    if ($type === 'TIME') {
        return $row->time_name;
    }

    if ($type === 'RELIC') {
        return $row->relic_name;
    }

    $parts = [];

    if (! empty($row->storyline_name)) {
        $parts[] = $row->storyline_name;
    }

    if (! empty($row->event_name)) {
        $parts[] = $row->event_name;
    }

    if (! empty($row->person_name) || ! empty($row->person_alias)) {
        $parts[] = trim($row->person_name . ' ' . $row->person_alias);
    }

    if (! empty($row->location_name) || ! empty($row->location_alias)) {
        $parts[] = trim($row->location_name . ' ' . $row->location_alias);
    }

    if (! empty($row->time_name)) {
        $parts[] = $row->time_name;
    }

    if (! empty($row->relic_name)) {
        $parts[] = $row->relic_name;
    }

    return implode(', ', array_filter($parts));
}

function novel_proofreading_get_manuscript_references($book_id = 0, $type = '') {
    global $wpdb;

    $items = [];

    $table_mapping =
        $wpdb->prefix . 'novel_proofreading_common_mapping';
    $table_books =
        $wpdb->prefix . 'novel_proofreading_books';
    $table_storylines =
        $wpdb->prefix . 'novel_proofreading_storylines';
    $table_events =
        $wpdb->prefix . 'novel_proofreading_events';
    $table_persons =
        $wpdb->prefix . 'novel_proofreading_persons';
    $table_locations =
        $wpdb->prefix . 'novel_proofreading_locations';
    $table_datetimes =
        $wpdb->prefix . 'novel_proofreading_datetimes';
    $table_relics =
        $wpdb->prefix . 'novel_proofreading_relics';

    $where = [];
    $params = [];

    if ($book_id > 0) {
        $where[] = 'cm.book_id = %d';
        $params[] = $book_id;
    }

    if ($type !== '') {
        $where[] = 'cm.type = %s';
        $params[] = $type;
    }

    $where_sql = '';

    if (! empty($where)) {
        $where_sql = 'WHERE ' . implode(' AND ', $where);
    }

    $sql = "
        SELECT
            cm.*,
            b.title AS book_title,
            st.storyline_name,
            e.event_name,
            p.name AS person_name,
            p.alias AS person_alias,
            l.name AS location_name,
            l.alias AS location_alias,
            d.name AS time_name,
            r.relic_name

        FROM
            {$table_mapping} cm
        LEFT JOIN
            {$table_books} b ON b.id = cm.book_id
        LEFT JOIN
            {$table_storylines} st ON st.id = cm.storyline_id
        LEFT JOIN
            {$table_events} e ON e.id = cm.event_id
        LEFT JOIN
            {$table_persons} p ON p.id = cm.person_id
        LEFT JOIN
            {$table_locations} l ON l.id = cm.location_id
        LEFT JOIN
            {$table_datetimes} d ON d.id = cm.time_id
        LEFT JOIN
            {$table_relics} r ON r.id = cm.relics_id

        {$where_sql}

        ORDER BY
            cm.book_id,
            cm.chapter,
            cm.page,
            cm.id
    ";

    $result = empty($params)
        ? $wpdb->get_results($sql)
        : $wpdb->get_results(
            $wpdb->prepare(
                $sql,
                $params
            )
        );

    foreach ($result as $row) {
        $items[] = [
            'id' => intval($row->id),
            'book_id' => intval($row->book_id),
            'book_title' => isset($row->book_title) ? $row->book_title : '',
            'type' => $row->type,
            'description' => $row->description,
            'page' => $row->page,
            'chapter' => $row->chapter,
            'storyline_id' => intval($row->storyline_id),
            'event_id' => intval($row->event_id),
            'person_id' => intval($row->person_id),
            'location_id' => intval($row->location_id),
            'time_id' => intval($row->time_id),
            'relics_id' => intval($row->relics_id),
            'entity_label' => novel_proofreading_get_entity_label($row->type, $row)
        ];
    }

    return $items;
}

function novel_proofreading_reference_entity_belongs_to_book($table_suffix, $id, $book_id) {
    global $wpdb;

    if ($id <= 0) {
        return true;
    }

    $allowed_tables = [
        'storylines' => 'novel_proofreading_storylines',
        'events' => 'novel_proofreading_events',
        'persons' => 'novel_proofreading_persons',
        'locations' => 'novel_proofreading_locations',
        'datetimes' => 'novel_proofreading_datetimes',
        'relics' => 'novel_proofreading_relics'
    ];

    if (! isset($allowed_tables[$table_suffix])) {
        return false;
    }

    $table =
        $wpdb->prefix . $allowed_tables[$table_suffix];

    $found = $wpdb->get_var(
        $wpdb->prepare(
            "
            SELECT
                COUNT(*)

            FROM
                {$table}

            WHERE
                    id = %d
                AND book_id = %d
            ",
            $id,
            $book_id
        )
    );

    return intval($found) > 0;
}

function novel_proofreading_sanitize_manuscript_reference_data() {
    $book_id = intval($_POST['book_id'] ?? 0);

    if ($book_id <= 0) {
        return new WP_Error(
            'missing_reference_book',
            __( 'Book is required.', 'novel-proofreading' )
        );
    }

    $type = sanitize_text_field(
        wp_unslash($_POST['type'] ?? '')
    );

    $common_types = wp_list_pluck(
        novel_proofreading_get_type_options('COMMON_TYPE'),
        'name'
    );

    if (! in_array($type, $common_types, true)) {
        return new WP_Error(
            'missing_reference_type',
            __( 'Reference type is required.', 'novel-proofreading' )
        );
    }

    $storyline_id = intval($_POST['storyline_id'] ?? 0);
    $event_id = intval($_POST['event_id'] ?? 0);
    $person_id = intval($_POST['person_id'] ?? 0);
    $location_id = intval($_POST['location_id'] ?? 0);
    $time_id = intval($_POST['time_id'] ?? 0);
    $relics_id = intval($_POST['relics_id'] ?? 0);

    $type_entity_map = [
        'STORYLINE' => 'storyline_id',
        'EVENT' => 'event_id',
        'PERSON' => 'person_id',
        'LOCATION' => 'location_id',
        'TIME' => 'time_id',
        'RELIC' => 'relics_id'
    ];

    if (isset($type_entity_map[$type])) {
        $required_field = $type_entity_map[$type];

        $storyline_id = $required_field === 'storyline_id' ? $storyline_id : 0;
        $event_id = $required_field === 'event_id' ? $event_id : 0;
        $person_id = $required_field === 'person_id' ? $person_id : 0;
        $location_id = $required_field === 'location_id' ? $location_id : 0;
        $time_id = $required_field === 'time_id' ? $time_id : 0;
        $relics_id = $required_field === 'relics_id' ? $relics_id : 0;

        $required_values = [
            'storyline_id' => $storyline_id,
            'event_id' => $event_id,
            'person_id' => $person_id,
            'location_id' => $location_id,
            'time_id' => $time_id,
            'relics_id' => $relics_id
        ];

        if (intval($required_values[$required_field]) <= 0) {
            return new WP_Error(
                'missing_reference_entity',
                __( 'Referenced item is required.', 'novel-proofreading' )
            );
        }
    }

    $entity_checks = [
        'storylines' => $storyline_id,
        'events' => $event_id,
        'persons' => $person_id,
        'locations' => $location_id,
        'datetimes' => $time_id,
        'relics' => $relics_id
    ];

    foreach ($entity_checks as $table_suffix => $entity_id) {
        if (
            $entity_id > 0 &&
            ! novel_proofreading_reference_entity_belongs_to_book($table_suffix, $entity_id, $book_id)
        ) {
            return new WP_Error(
                'invalid_reference_entity_book',
                __( 'Referenced item must belong to the selected book.', 'novel-proofreading' )
            );
        }
    }

    return [
        'book_id' => $book_id,
        'type' => $type,
        'description' => sanitize_textarea_field(
            wp_unslash($_POST['description'] ?? '')
        ),
        'page' => sanitize_text_field(
            wp_unslash($_POST['page'] ?? '')
        ),
        'chapter' => sanitize_text_field(
            wp_unslash($_POST['chapter'] ?? '')
        ),
        'storyline_id' => $storyline_id > 0 ? $storyline_id : null,
        'event_id' => $event_id > 0 ? $event_id : null,
        'person_id' => $person_id > 0 ? $person_id : null,
        'location_id' => $location_id > 0 ? $location_id : null,
        'time_id' => $time_id > 0 ? $time_id : null,
        'relics_id' => $relics_id > 0 ? $relics_id : null
    ];
}

function novel_proofreading_add_manuscript_reference() {
    global $wpdb;

    $data = novel_proofreading_sanitize_manuscript_reference_data();

    if (is_wp_error($data)) {
        return $data->get_error_message();
    }

    $table =
        $wpdb->prefix . 'novel_proofreading_common_mapping';

    $now = current_time(
        'mysql',
        true
    );

    $result = $wpdb->insert(
        $table,
        array_merge(
            $data,
            [
                'created_at' => $now,
                'created_by' => get_current_user_id(),
                'updated_at' => $now,
                'updated_by' => get_current_user_id()
            ]
        ),
        [
            '%d',
            '%s',
            '%s',
            '%s',
            '%s',
            '%d',
            '%d',
            '%d',
            '%d',
            '%d',
            '%d',
            '%s',
            '%d',
            '%s',
            '%d'
        ]
    );

    if ($result === false) {
        error_log(
            'INSERT ERROR: ' . $wpdb->last_error
        );

        return __( 'Manuscript reference could not be added.', 'novel-proofreading' );
    }

    return __( 'Manuscript reference added.', 'novel-proofreading' );
}

function novel_proofreading_update_manuscript_reference($id) {
    global $wpdb;

    if ($id <= 0) {
        return __( 'Manuscript reference could not be updated.', 'novel-proofreading' );
    }

    $data = novel_proofreading_sanitize_manuscript_reference_data();

    if (is_wp_error($data)) {
        return $data->get_error_message();
    }

    $table =
        $wpdb->prefix . 'novel_proofreading_common_mapping';

    $result = $wpdb->update(
        $table,
        array_merge(
            $data,
            [
                'updated_at' => current_time(
                    'mysql',
                    true
                ),
                'updated_by' => get_current_user_id()
            ]
        ),
        [
            'id' => $id
        ],
        [
            '%d',
            '%s',
            '%s',
            '%s',
            '%s',
            '%d',
            '%d',
            '%d',
            '%d',
            '%d',
            '%d',
            '%s',
            '%d'
        ],
        [
            '%d'
        ]
    );

    if ($result === false) {
        error_log(
            'UPDATE ERROR: ' . $wpdb->last_error
        );

        return __( 'Manuscript reference could not be updated.', 'novel-proofreading' );
    }

    return __( 'Manuscript reference updated.', 'novel-proofreading' );
}

function novel_proofreading_remove_manuscript_reference($id) {
    global $wpdb;

    $table =
        $wpdb->prefix . 'novel_proofreading_common_mapping';

    $wpdb->query(
        $wpdb->prepare(
            "
            DELETE FROM {$table}

            WHERE
                id = %d
            ",
            $id
        )
    );

    return __( 'Manuscript reference deleted.', 'novel-proofreading' );
}

function novel_proofreading_admin_page() {

    $admin_notice = "";

    if (
        isset($_POST['novel_proofreading_action']) &&
        check_admin_referer(
            'novel_proofreading_books_action',
            'novel_proofreading_books_nonce'
        )
    ) {
        $action = sanitize_text_field(
            wp_unslash($_POST['novel_proofreading_action'])
        );

        if ($action === 'add_book') {
            $admin_notice = novel_proofreading_add_book();
        }

        if ($action === 'remove_book') {
            $admin_notice = novel_proofreading_remove_book(
                intval($_POST['book_id'] ?? 0)
            );
        }

        if ($action === 'update_book') {
            $admin_notice = novel_proofreading_update_book(
                intval($_POST['book_id'] ?? 0)
            );
        }

        if ($action === 'add_series') {
            $admin_notice = novel_proofreading_add_series();
        }

        if ($action === 'remove_series') {
            $admin_notice = novel_proofreading_remove_series(
                intval($_POST['series_id'] ?? 0)
            );
        }

        if ($action === 'update_series') {
            $admin_notice = novel_proofreading_update_series(
                intval($_POST['series_id'] ?? 0)
            );
        }

        if ($action === 'remove_book_from_series') {
            $admin_notice = novel_proofreading_remove_book_from_series(
                intval($_POST['sub_series_id'] ?? 0),
                intval($_POST['sub_book_id'] ?? 0)
            );
        }

        if ($action === 'add_book_to_series') {
            $admin_notice = novel_proofreading_add_book_to_series();
        }

        if ($action === 'add_person') {
            $admin_notice = novel_proofreading_add_person();
        }

        if ($action === 'remove_person') {
            $admin_notice = novel_proofreading_remove_person(
                intval($_POST['person_id'] ?? 0)
            );
        }

        if ($action === 'update_person') {
            $admin_notice = novel_proofreading_update_person(
                intval($_POST['person_id'] ?? 0)
            );
        }

        if ($action === 'add_person_alias_mapping') {
            $admin_notice = novel_proofreading_add_person_alias_mapping();
        }

        if ($action === 'remove_person_alias_mapping') {
            $admin_notice = novel_proofreading_remove_person_alias_mapping(
                intval($_POST['person_alias_mapping_id'] ?? 0)
            );
        }

        if ($action === 'update_person_alias_mapping') {
            $admin_notice = novel_proofreading_update_person_alias_mapping(
                intval($_POST['person_alias_mapping_id'] ?? 0)
            );
        }

        if ($action === 'add_location') {
            $admin_notice = novel_proofreading_add_location();
        }

        if ($action === 'remove_location') {
            $admin_notice = novel_proofreading_remove_location(
                intval($_POST['location_id'] ?? 0)
            );
        }

        if ($action === 'update_location') {
            $admin_notice = novel_proofreading_update_location(
                intval($_POST['location_id'] ?? 0)
            );
        }

        if ($action === 'add_relic') {
            $admin_notice = novel_proofreading_add_relic();
        }

        if ($action === 'remove_relic') {
            $admin_notice = novel_proofreading_remove_relic(
                intval($_POST['relic_id'] ?? 0)
            );
        }

        if ($action === 'update_relic') {
            $admin_notice = novel_proofreading_update_relic(
                intval($_POST['relic_id'] ?? 0)
            );
        }

        if ($action === 'add_datetime') {
            $admin_notice = novel_proofreading_add_datetime();
        }

        if ($action === 'remove_datetime') {
            $admin_notice = novel_proofreading_remove_datetime(
                intval($_POST['datetime_id'] ?? 0)
            );
        }

        if ($action === 'update_datetime') {
            $admin_notice = novel_proofreading_update_datetime(
                intval($_POST['datetime_id'] ?? 0)
            );
        }

        if ($action === 'add_profession') {
            $admin_notice = novel_proofreading_add_profession();
        }

        if ($action === 'remove_profession') {
            $admin_notice = novel_proofreading_remove_profession(
                intval($_POST['profession_id'] ?? 0)
            );
        }

        if ($action === 'update_profession') {
            $admin_notice = novel_proofreading_update_profession(
                intval($_POST['profession_id'] ?? 0)
            );
        }

        if ($action === 'add_event') {
            $admin_notice = novel_proofreading_add_event();
        }

        if ($action === 'remove_event') {
            $admin_notice = novel_proofreading_remove_event(
                intval($_POST['event_id'] ?? 0)
            );
        }

        if ($action === 'update_event') {
            $admin_notice = novel_proofreading_update_event(
                intval($_POST['event_id'] ?? 0)
            );
        }

        if ($action === 'add_storyline') {
            $admin_notice = novel_proofreading_add_storyline();
        }

        if ($action === 'remove_storyline') {
            $admin_notice = novel_proofreading_remove_storyline(
                intval($_POST['storyline_id'] ?? 0)
            );
        }

        if ($action === 'update_storyline') {
            $admin_notice = novel_proofreading_update_storyline(
                intval($_POST['storyline_id'] ?? 0)
            );
        }

        if ($action === 'update_chain_event') {
            $admin_notice = novel_proofreading_update_chain_event(
                intval($_POST['event_id'] ?? 0)
            );
        }

        if ($action === 'add_manuscript_reference') {
            $admin_notice = novel_proofreading_add_manuscript_reference();
        }

        if ($action === 'remove_manuscript_reference') {
            $admin_notice = novel_proofreading_remove_manuscript_reference(
                intval($_POST['manuscript_reference_id'] ?? 0)
            );
        }

        if ($action === 'update_manuscript_reference') {
            $admin_notice = novel_proofreading_update_manuscript_reference(
                intval($_POST['manuscript_reference_id'] ?? 0)
            );
        }
    }

    $items = novel_proofreading_get_books();
    $series_items = novel_proofreading_get_series();
    $person_items = novel_proofreading_get_persons();
    $person_alias_mapping_items = novel_proofreading_get_person_alias_mappings();
    $storyline_items = novel_proofreading_get_storylines();

    $location_items = novel_proofreading_get_locations();
    $relic_items = novel_proofreading_get_relics();
    $area_type_items = novel_proofreading_get_type_options('AREA_TYPE');
    $datetime_items = novel_proofreading_get_datetimes();
    $datetime_type_items = novel_proofreading_get_type_options('DATETIME_TYPE');
    $profession_items = novel_proofreading_get_professions();

    $event_items = novel_proofreading_get_events();
    $chain_role_items = novel_proofreading_get_chain_role_options();
    $selected_chain_book_id = intval($_GET['chain_book_id'] ?? 0);
    $storyline_chain_items = novel_proofreading_get_storyline_chains(
        $selected_chain_book_id
    );
    $common_type_items = novel_proofreading_get_type_options('COMMON_TYPE');
    $selected_reference_book_id = intval($_GET['reference_book_id'] ?? 0);
    $selected_reference_type = sanitize_text_field(
        wp_unslash($_GET['reference_type'] ?? '')
    );
    $manuscript_reference_items = novel_proofreading_get_manuscript_references(
        $selected_reference_book_id,
        $selected_reference_type
    );
    ?>

    <div class="wrap">

        <h1>Novel Proofreading</h1>

        <?php if ($admin_notice != "") : ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo esc_html($admin_notice); ?></p>
            </div>
        <?php endif; ?>

        
        <h2>1.&nbsp;<?php _e( 'Books', 'novel-proofreading' ); ?></h2>
        <button class="button" onclick="show_hide('.books-wrap')"><?php _e( 'Show / Hide Books', 'novel-proofreading' ); ?></button>
        <div class="books-wrap hidden">
            <h3>1.1&nbsp;<?php _e( 'List of Books', 'novel-proofreading' ); ?></h3>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php _e( 'Series', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Title', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Subtitle', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Author', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Year', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Status', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Edit', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Delete', 'novel-proofreading' ); ?></th>
                    </tr>
                </thead>
                <tbody id="novel-proofreading-books-repeater">
                    <?php foreach ( $items as $item ) : ?>
                        <?php $book_form_id = 'novel-proofreading-edit-book-' . intval($item['id']); ?>
                        <tr>
                            <td><?php echo esc_html($item['series_title']); ?></td>
                            <td><input form="<?php echo esc_attr($book_form_id); ?>" type="text" name="title" value="<?php echo esc_attr($item['title']); ?>" required /></td>
                            <td><input form="<?php echo esc_attr($book_form_id); ?>" type="text" name="subtitle" value="<?php echo esc_attr($item['subtitle']); ?>" required /></td>
                            <td><input form="<?php echo esc_attr($book_form_id); ?>" type="text" name="author" value="<?php echo esc_attr($item['author']); ?>" required /></td>
                            <td><input form="<?php echo esc_attr($book_form_id); ?>" type="text" name="year" value="<?php echo esc_attr($item['year']); ?>" maxlength="4" pattern="[0-9]{4}" required /></td>
                            <td><input form="<?php echo esc_attr($book_form_id); ?>" type="text" name="status" value="<?php echo esc_attr($item['status']); ?>" /></td>
                            <td>
                                <form id="<?php echo esc_attr($book_form_id); ?>" method="post">
                                    <?php wp_nonce_field( 'novel_proofreading_books_action', 'novel_proofreading_books_nonce' ); ?>
                                    <input type="hidden" name="novel_proofreading_action" value="update_book" />
                                    <input type="hidden" name="book_id" value="<?php echo esc_attr($item['id']); ?>" />
                                    <button type="submit" class="button button-primary"><?php _e( 'Save', 'novel-proofreading' ); ?></button>
                                </form>
                            </td>
                            <td>
                                <form method="post">
                                    <?php wp_nonce_field( 'novel_proofreading_books_action', 'novel_proofreading_books_nonce' ); ?>
                                    <input type="hidden" name="novel_proofreading_action" value="remove_book" />
                                    <input type="hidden" name="book_id" value="<?php echo esc_attr($item['id']); ?>" />
                                    <button type="button" class="button remove-item" onclick="confirm_delete(this)">-</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3>1.2&nbsp;<?php _e( 'Add Book', 'novel-proofreading' ); ?></h3>
            <form method="post">
                <?php wp_nonce_field( 'novel_proofreading_books_action', 'novel_proofreading_books_nonce' ); ?>
                <input type="hidden" name="novel_proofreading_action" value="add_book" />

                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="novel-proofreading-title"><?php _e( 'Title', 'novel-proofreading' ); ?></label>
                            </th>
                            <td>
                                <input type="text" class="regular-text" id="novel-proofreading-title" name="title" required />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="novel-proofreading-subtitle"><?php _e( 'Subtitle', 'novel-proofreading' ); ?></label>
                            </th>
                            <td>
                                <input type="text" class="regular-text" id="novel-proofreading-subtitle" name="subtitle" required />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="novel-proofreading-author"><?php _e( 'Author', 'novel-proofreading' ); ?></label>
                            </th>
                            <td>
                                <input type="text" class="regular-text" id="novel-proofreading-author" name="author" required />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="novel-proofreading-year"><?php _e( 'Year', 'novel-proofreading' ); ?></label>
                            </th>
                            <td>
                                <input type="text" class="regular-text" id="novel-proofreading-year" name="year" maxlength="4" pattern="[0-9]{4}" required />
                            </td>
                        </tr>
                    </tbody>
                </table>

                <button type="submit" class="button button-primary" id="add-item">
                    + <?php _e( 'Add Book', 'novel-proofreading' ); ?>
                </button>
            </form>
        </div>

        <h2>2.&nbsp;<?php _e( 'Series', 'novel-proofreading' ); ?></h2>
        <button class="button" onclick="show_hide('.series-wrap')"><?php _e( 'Show / Hide Series', 'novel-proofreading' ); ?></button>
        <div class="series-wrap hidden">
            <h3>2.1&nbsp;<?php _e( 'List of Series', 'novel-proofreading' ); ?></h3>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php _e( 'Series title', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Series subtitle', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Author', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Year', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Status', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Edit', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Delete', 'novel-proofreading' ); ?></th>
                    </tr>
                </thead>
                <tbody id="novel-proofreading-series-repeater">
                    <?php foreach ( $series_items as $item ) : ?>
                        <?php $series_form_id = 'novel-proofreading-edit-series-' . intval($item['id']); ?>
                        <tr>
                            <td><input form="<?php echo esc_attr($series_form_id); ?>" type="text" name="series_title" value="<?php echo esc_attr($item['series_title']); ?>" required /></td>
                            <td><input form="<?php echo esc_attr($series_form_id); ?>" type="text" name="series_subtitle" value="<?php echo esc_attr($item['series_subtitle']); ?>" required /></td>
                            <td><input form="<?php echo esc_attr($series_form_id); ?>" type="text" name="author" value="<?php echo esc_attr($item['author']); ?>" required /></td>
                            <td><input form="<?php echo esc_attr($series_form_id); ?>" type="text" name="year" value="<?php echo esc_attr($item['year']); ?>" maxlength="4" pattern="[0-9]{4}" required /></td>
                            <td><input form="<?php echo esc_attr($series_form_id); ?>" type="text" name="status" value="<?php echo esc_attr($item['status']); ?>" /></td>
                            <td>
                                <form id="<?php echo esc_attr($series_form_id); ?>" method="post">
                                    <?php wp_nonce_field( 'novel_proofreading_books_action', 'novel_proofreading_books_nonce' ); ?>
                                    <input type="hidden" name="novel_proofreading_action" value="update_series" />
                                    <input type="hidden" name="series_id" value="<?php echo esc_attr($item['id']); ?>" />
                                    <button type="submit" class="button button-primary"><?php _e( 'Save', 'novel-proofreading' ); ?></button>
                                </form>
                            </td>
                            <td>
                                <form method="post">
                                    <?php wp_nonce_field( 'novel_proofreading_books_action', 'novel_proofreading_books_nonce' ); ?>
                                    <input type="hidden" name="novel_proofreading_action" value="remove_series" />
                                    <input type="hidden" name="series_id" value="<?php echo esc_attr($item['id']); ?>" />
                                    <button type="button" class="button remove-item" onclick="confirm_delete(this)">-</button>
                                </form>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="7" style="padding-left: 50px;">
                                <table>
                                    <thead>
                                        <th><?php _e( 'Books in this Series', 'novel-proofreading' ); ?></th>
                                        <th><?php _e( 'Delete', 'novel-proofreading' ); ?></th>
                                    </thead>
                                    <tbody>
                                        <?php foreach ( novel_proofreading_get_books_by_series($item['id']) as $subitem ) : ?>
                                            <tr>
                                                <td><?php echo esc_html($subitem['title']); ?></td>
                                                <td>
                                                    <form method="post">
                                                        <?php wp_nonce_field( 'novel_proofreading_books_action', 'novel_proofreading_books_nonce' ); ?>
                                                        <input type="hidden" name="novel_proofreading_action" value="remove_book_from_series" />
                                                        <input type="hidden" name="sub_series_id" value="<?php echo esc_attr($item['id']); ?>" />
                                                        <input type="hidden" name="sub_book_id" value="<?php echo esc_attr($subitem['id']); ?>" />
                                                        <button type="button" class="button remove-item" onclick="confirm_delete(this)">-</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3>2.2&nbsp;<?php _e( 'Add Series', 'novel-proofreading' ); ?></h3>
            <form method="post">
                <?php wp_nonce_field( 'novel_proofreading_books_action', 'novel_proofreading_books_nonce' ); ?>
                <input type="hidden" name="novel_proofreading_action" value="add_series" />

                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="novel-proofreading-title"><?php _e( 'Series Title', 'novel-proofreading' ); ?></label>
                            </th>
                            <td>
                                <input type="text" class="regular-text" id="novel-proofreading-title" name="series_title" required />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="novel-proofreading-subtitle"><?php _e( 'Series Subtitle', 'novel-proofreading' ); ?></label>
                            </th>
                            <td>
                                <input type="text" class="regular-text" id="novel-proofreading-subtitle" name="series_subtitle" required />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="novel-proofreading-author"><?php _e( 'Author', 'novel-proofreading' ); ?></label>
                            </th>
                            <td>
                                <input type="text" class="regular-text" id="novel-proofreading-author" name="author" required />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="novel-proofreading-year"><?php _e( 'Year', 'novel-proofreading' ); ?></label>
                            </th>
                            <td>
                                <input type="text" class="regular-text" id="novel-proofreading-year" name="year" maxlength="4" pattern="[0-9]{4}" required />
                            </td>
                        </tr>
                    </tbody>
                </table>

                <button type="submit" class="button button-primary" id="add-item">
                    + <?php _e( 'Add Series', 'novel-proofreading' ); ?>
                </button>
            </form>

            <h3>2.3&nbsp;<?php _e( 'Add Book to Series', 'novel-proofreading' ); ?></h3>
            <form method="post">
                <?php wp_nonce_field( 'novel_proofreading_books_action', 'novel_proofreading_books_nonce' ); ?>
                <input type="hidden" name="novel_proofreading_action" value="add_book_to_series" />

                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="novel-proofreading-series-book-id"><?php _e( 'Book', 'novel-proofreading' ); ?></label>
                            </th>
                            <td>
                                <select id="novel-proofreading-series-book-id" name="book_id" required>
                                    <option value=""><?php _e( 'Select book', 'novel-proofreading' ); ?></option>
                                    <?php foreach ( $items as $item ) : ?>
                                        <option value="<?php echo esc_attr($item['id']); ?>">
                                            <?php echo esc_html($item['title'] . ' - ' . $item['author'] . ' (' . $item['year'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="novel-proofreading-series-id"><?php _e( 'Series', 'novel-proofreading' ); ?></label>
                            </th>
                            <td>
                                <select id="novel-proofreading-series-id" name="series_id" required>
                                    <option value=""><?php _e( 'Select series', 'novel-proofreading' ); ?></option>
                                    <?php foreach ( $series_items as $item ) : ?>
                                        <option value="<?php echo esc_attr($item['id']); ?>">
                                            <?php echo esc_html($item['series_title'] . ' - ' . $item['author'] . ' (' . $item['year'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <button type="submit" class="button button-primary" <?php disabled(empty($items) || empty($series_items)); ?>>
                    + <?php _e( 'Add Book to Series', 'novel-proofreading' ); ?>
                </button>
            </form>

        </div>

        <h2>3.&nbsp;<?php _e( 'Person', 'novel-proofreading' ); ?></h2>
        <button class="button" onclick="show_hide('.persons-wrap')"><?php _e( 'Show / Hide Persons', 'novel-proofreading' ); ?></button>
        <div class="persons-wrap hidden">
            <h3>3.1&nbsp;<?php _e( 'List of Persons', 'novel-proofreading' ); ?></h3>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php _e( 'Book', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Name', 'novel-proofreading' ); ?></th>
                        <th><?php _e( '(i)', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Alias', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Description', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Inaccurate', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Edit', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Delete', 'novel-proofreading' ); ?></th>
                    </tr>
                </thead>
                <tbody id="novel-proofreading-persons-repeater">
                    <?php foreach ( $person_items as $item ) : ?>
                        <?php $person_form_id = 'novel-proofreading-edit-person-' . intval($item['id']); ?>
                        <tr>
                            <td>
                                <select form="<?php echo esc_attr($person_form_id); ?>" name="book_id" required>
                                    <option value=""><?php _e( 'Select book', 'novel-proofreading' ); ?></option>
                                    <?php foreach ( $items as $book_item ) : ?>
                                        <option value="<?php echo esc_attr($book_item['id']); ?>" <?php selected($item['book_id'], $book_item['id']); ?>>
                                            <?php echo esc_html($book_item['title'] . ' - ' . $book_item['author'] . ' (' . $book_item['year'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <input form="<?php echo esc_attr($person_form_id); ?>" type="text" name="name" value="<?php echo esc_attr($item['name']); ?>" />
                                <?php if ($item['has_profession']) : ?>
                                    <button type="button" class="novel-proofreading-badge is-info novel-proofreading-person-profession-badge" data-person-id="<?php echo esc_attr($item['id']); ?>" data-profession-scope="person">
                                        (job)
                                    </button>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($item['has_alias_mapping']) : ?>
                                    <button type="button" class="novel-proofreading-badge is-info novel-proofreading-person-alias-badge" data-person-id="<?php echo esc_attr($item['id']); ?>">
                                        (i)
                                    </button>
                                <?php endif; ?>
                            </td>
                            <td>
                                <input form="<?php echo esc_attr($person_form_id); ?>" type="text" name="alias" value="<?php echo esc_attr($item['alias']); ?>" />
                                <?php if ($item['has_alias_profession']) : ?>
                                    <button type="button" class="novel-proofreading-badge is-info novel-proofreading-person-profession-badge" data-person-id="<?php echo esc_attr($item['id']); ?>" data-profession-scope="aliases">
                                        (job)
                                    </button>
                                <?php endif; ?>
                            </td>
                            <td><textarea form="<?php echo esc_attr($person_form_id); ?>" name="description" rows="2"><?php echo esc_textarea($item['description']); ?></textarea></td>
                            <td><input form="<?php echo esc_attr($person_form_id); ?>" type="checkbox" name="is_inaccurate" value="Y" <?php checked($item['is_inaccurate'], 'Y'); ?> /></td>
                            <td>
                                <form id="<?php echo esc_attr($person_form_id); ?>" method="post">
                                    <?php wp_nonce_field( 'novel_proofreading_books_action', 'novel_proofreading_books_nonce' ); ?>
                                    <input type="hidden" name="novel_proofreading_action" value="update_person" />
                                    <input type="hidden" name="person_id" value="<?php echo esc_attr($item['id']); ?>" />
                                    <button type="submit" class="button button-primary"><?php _e( 'Save', 'novel-proofreading' ); ?></button>
                                </form>
                            </td>
                            <td>
                                <form method="post">
                                    <?php wp_nonce_field( 'novel_proofreading_books_action', 'novel_proofreading_books_nonce' ); ?>
                                    <input type="hidden" name="novel_proofreading_action" value="remove_person" />
                                    <input type="hidden" name="person_id" value="<?php echo esc_attr($item['id']); ?>" />
                                    <button type="button" class="button remove-item" onclick="confirm_delete(this)">-</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3>3.2&nbsp;<?php _e( 'Add Person', 'novel-proofreading' ); ?></h3>
            <form method="post">
                <?php wp_nonce_field( 'novel_proofreading_books_action', 'novel_proofreading_books_nonce' ); ?>
                <input type="hidden" name="novel_proofreading_action" value="add_person" />

                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="novel-proofreading-person-book-id"><?php _e( 'Book', 'novel-proofreading' ); ?></label>
                            </th>
                            <td>
                                <select id="novel-proofreading-person-book-id" name="book_id" required>
                                    <option value=""><?php _e( 'Select book', 'novel-proofreading' ); ?></option>
                                    <?php foreach ( $items as $item ) : ?>
                                        <option value="<?php echo esc_attr($item['id']); ?>">
                                            <?php echo esc_html($item['title'] . ' - ' . $item['author'] . ' (' . $item['year'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="novel-proofreading-person-name"><?php _e( 'Name', 'novel-proofreading' ); ?></label>
                            </th>
                            <td>
                                <input type="text" class="regular-text" id="novel-proofreading-person-name" name="name" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="novel-proofreading-person-alias"><?php _e( 'Alias', 'novel-proofreading' ); ?></label>
                            </th>
                            <td>
                                <input type="text" class="regular-text" id="novel-proofreading-person-alias" name="alias" />
                                <p class="description"><?php _e( 'Name or alias is required.', 'novel-proofreading' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="novel-proofreading-person-description"><?php _e( 'Description', 'novel-proofreading' ); ?></label>
                            </th>
                            <td>
                                <textarea class="large-text" id="novel-proofreading-person-description" name="description" rows="3"></textarea>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <?php _e( 'Inaccurate', 'novel-proofreading' ); ?>
                            </th>
                            <td>
                                <label for="novel-proofreading-person-is-inaccurate">
                                    <input type="checkbox" id="novel-proofreading-person-is-inaccurate" name="is_inaccurate" value="Y" />
                                    <?php _e( 'Yes', 'novel-proofreading' ); ?>
                                </label>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <button type="submit" class="button button-primary" <?php disabled(empty($items)); ?>>
                    + <?php _e( 'Add Person', 'novel-proofreading' ); ?>
                </button>
            </form>

            <h3>3.3&nbsp;<?php _e( 'Person name and alias mapping', 'novel-proofreading' ); ?></h3>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php _e( 'Book', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Name', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Alias', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Edit', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Delete', 'novel-proofreading' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $person_alias_mapping_items as $mapping_item ) : ?>
                        <?php $person_alias_mapping_form_id = 'novel-proofreading-edit-person-alias-mapping-' . intval($mapping_item['id']); ?>
                        <tr>
                            <td>
                                <select form="<?php echo esc_attr($person_alias_mapping_form_id); ?>" name="book_id" class="novel-proofreading-book-select" required>
                                    <option value=""><?php _e( 'Select book', 'novel-proofreading' ); ?></option>
                                    <?php foreach ( $items as $book_item ) : ?>
                                        <option value="<?php echo esc_attr($book_item['id']); ?>" <?php selected($mapping_item['book_id'], $book_item['id']); ?>>
                                            <?php echo esc_html($book_item['title'] . ' - ' . $book_item['author'] . ' (' . $book_item['year'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <select form="<?php echo esc_attr($person_alias_mapping_form_id); ?>" name="person_id" class="novel-proofreading-person-select" required>
                                    <option value=""><?php _e( 'Select person', 'novel-proofreading' ); ?></option>
                                    <?php foreach ( $person_items as $person_item ) : ?>
                                        <option value="<?php echo esc_attr($person_item['id']); ?>" data-book-id="<?php echo esc_attr($person_item['book_id']); ?>" <?php selected($mapping_item['person_id'], $person_item['id']); ?>>
                                            <?php echo esc_html(novel_proofreading_format_person_label($person_item['name'], $person_item['alias']) . ' - ' . $person_item['book_title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <select form="<?php echo esc_attr($person_alias_mapping_form_id); ?>" name="alias_person_id" class="novel-proofreading-person-select" required>
                                    <option value=""><?php _e( 'Select alias', 'novel-proofreading' ); ?></option>
                                    <?php foreach ( $person_items as $person_item ) : ?>
                                        <option value="<?php echo esc_attr($person_item['id']); ?>" data-book-id="<?php echo esc_attr($person_item['book_id']); ?>" <?php selected($mapping_item['alias_person_id'], $person_item['id']); ?>>
                                            <?php echo esc_html(novel_proofreading_format_person_label($person_item['name'], $person_item['alias']) . ' - ' . $person_item['book_title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <form id="<?php echo esc_attr($person_alias_mapping_form_id); ?>" method="post">
                                    <?php wp_nonce_field( 'novel_proofreading_books_action', 'novel_proofreading_books_nonce' ); ?>
                                    <input type="hidden" name="novel_proofreading_action" value="update_person_alias_mapping" />
                                    <input type="hidden" name="person_alias_mapping_id" value="<?php echo esc_attr($mapping_item['id']); ?>" />
                                    <button type="submit" class="button button-primary"><?php _e( 'Save', 'novel-proofreading' ); ?></button>
                                </form>
                            </td>
                            <td>
                                <form method="post">
                                    <?php wp_nonce_field( 'novel_proofreading_books_action', 'novel_proofreading_books_nonce' ); ?>
                                    <input type="hidden" name="novel_proofreading_action" value="remove_person_alias_mapping" />
                                    <input type="hidden" name="person_alias_mapping_id" value="<?php echo esc_attr($mapping_item['id']); ?>" />
                                    <button type="button" class="button remove-item" onclick="confirm_delete(this)">-</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <form method="post">
                <?php wp_nonce_field( 'novel_proofreading_books_action', 'novel_proofreading_books_nonce' ); ?>
                <input type="hidden" name="novel_proofreading_action" value="add_person_alias_mapping" />

                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="novel-proofreading-person-alias-mapping-book-id"><?php _e( 'Book', 'novel-proofreading' ); ?></label>
                            </th>
                            <td>
                                <select id="novel-proofreading-person-alias-mapping-book-id" name="book_id" class="novel-proofreading-book-select" required>
                                    <option value=""><?php _e( 'Select book', 'novel-proofreading' ); ?></option>
                                    <?php foreach ( $items as $item ) : ?>
                                        <option value="<?php echo esc_attr($item['id']); ?>">
                                            <?php echo esc_html($item['title'] . ' - ' . $item['author'] . ' (' . $item['year'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="novel-proofreading-person-alias-mapping-person-id"><?php _e( 'Name', 'novel-proofreading' ); ?></label>
                            </th>
                            <td>
                                <select id="novel-proofreading-person-alias-mapping-person-id" name="person_id" class="novel-proofreading-person-select" required>
                                    <option value=""><?php _e( 'Select person', 'novel-proofreading' ); ?></option>
                                    <?php foreach ( $person_items as $item ) : ?>
                                        <option value="<?php echo esc_attr($item['id']); ?>" data-book-id="<?php echo esc_attr($item['book_id']); ?>">
                                            <?php echo esc_html(novel_proofreading_format_person_label($item['name'], $item['alias']) . ' - ' . $item['book_title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="novel-proofreading-person-alias-mapping-alias-person-id"><?php _e( 'Alias', 'novel-proofreading' ); ?></label>
                            </th>
                            <td>
                                <select id="novel-proofreading-person-alias-mapping-alias-person-id" name="alias_person_id" class="novel-proofreading-person-select" required>
                                    <option value=""><?php _e( 'Select alias', 'novel-proofreading' ); ?></option>
                                    <?php foreach ( $person_items as $item ) : ?>
                                        <option value="<?php echo esc_attr($item['id']); ?>" data-book-id="<?php echo esc_attr($item['book_id']); ?>">
                                            <?php echo esc_html(novel_proofreading_format_person_label($item['name'], $item['alias']) . ' - ' . $item['book_title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <button type="submit" class="button button-primary" <?php disabled(empty($items) || empty($person_items)); ?>>
                    + <?php _e( 'Add Person Alias Mapping', 'novel-proofreading' ); ?>
                </button>
            </form>

        </div>

        <h2>4.&nbsp;<?php _e( 'Locations', 'novel-proofreading' ); ?></h2>
        <button class="button" onclick="show_hide('.locations-wrap')"><?php _e( 'Show / Hide Locations', 'novel-proofreading' ); ?></button>
        <div class="locations-wrap hidden">
            <h3>4.1&nbsp;<?php _e( 'List of Locations', 'novel-proofreading' ); ?></h3>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php _e( 'Book', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Name', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Alias', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Area', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Region', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Description', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Alternative Universe', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Inaccurate', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Edit', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Delete', 'novel-proofreading' ); ?></th>
                    </tr>
                </thead>
                <tbody id="novel-proofreading-locations-repeater">
                    <?php foreach ( $location_items as $item ) : ?>
                        <?php $location_form_id = 'novel-proofreading-edit-location-' . intval($item['id']); ?>
                        <tr>
                            <td>
                                <select form="<?php echo esc_attr($location_form_id); ?>" name="book_id" required>
                                    <option value=""><?php _e( 'Select book', 'novel-proofreading' ); ?></option>
                                    <?php foreach ( $items as $book_item ) : ?>
                                        <option value="<?php echo esc_attr($book_item['id']); ?>" <?php selected($item['book_id'], $book_item['id']); ?>>
                                            <?php echo esc_html($book_item['title'] . ' - ' . $book_item['author'] . ' (' . $book_item['year'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input form="<?php echo esc_attr($location_form_id); ?>" type="text" name="name" value="<?php echo esc_attr($item['name']); ?>" /></td>
                            <td><input form="<?php echo esc_attr($location_form_id); ?>" type="text" name="alias" value="<?php echo esc_attr($item['alias']); ?>" /></td>
                            <td>
                                <select form="<?php echo esc_attr($location_form_id); ?>" name="area">
                                    <option value=""><?php _e( 'Select area', 'novel-proofreading' ); ?></option>
                                    <?php foreach ( $area_type_items as $area_type_item ) : ?>
                                        <option value="<?php echo esc_attr($area_type_item['name']); ?>" <?php selected($item['area'], $area_type_item['name']); ?>>
                                            <?php echo esc_html($area_type_item['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input form="<?php echo esc_attr($location_form_id); ?>" type="text" name="region" value="<?php echo esc_attr($item['region']); ?>" /></td>
                            <td><textarea form="<?php echo esc_attr($location_form_id); ?>" name="description" rows="2"><?php echo esc_textarea($item['description']); ?></textarea></td>
                            <td><input form="<?php echo esc_attr($location_form_id); ?>" type="checkbox" name="is_in_alternative_universe" value="Y" <?php checked($item['is_in_alternative_universe'], 'Y'); ?> /></td>
                            <td><input form="<?php echo esc_attr($location_form_id); ?>" type="checkbox" name="is_inaccurate" value="Y" <?php checked($item['is_inaccurate'], 'Y'); ?> /></td>
                            <td>
                                <form id="<?php echo esc_attr($location_form_id); ?>" method="post">
                                    <?php wp_nonce_field( 'novel_proofreading_books_action', 'novel_proofreading_books_nonce' ); ?>
                                    <input type="hidden" name="novel_proofreading_action" value="update_location" />
                                    <input type="hidden" name="location_id" value="<?php echo esc_attr($item['id']); ?>" />
                                    <button type="submit" class="button button-primary"><?php _e( 'Save', 'novel-proofreading' ); ?></button>
                                </form>
                            </td>
                            <td>
                                <form method="post">
                                    <?php wp_nonce_field( 'novel_proofreading_books_action', 'novel_proofreading_books_nonce' ); ?>
                                    <input type="hidden" name="novel_proofreading_action" value="remove_location" />
                                    <input type="hidden" name="location_id" value="<?php echo esc_attr($item['id']); ?>" />
                                    <button type="button" class="button remove-item" onclick="confirm_delete(this)">-</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3>4.2&nbsp;<?php _e( 'Add Location', 'novel-proofreading' ); ?></h3>
            <form method="post">
                <?php wp_nonce_field( 'novel_proofreading_books_action', 'novel_proofreading_books_nonce' ); ?>
                <input type="hidden" name="novel_proofreading_action" value="add_location" />

                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="novel-proofreading-location-book-id"><?php _e( 'Book', 'novel-proofreading' ); ?></label>
                            </th>
                            <td>
                                <select id="novel-proofreading-location-book-id" name="book_id" required>
                                    <option value=""><?php _e( 'Select book', 'novel-proofreading' ); ?></option>
                                    <?php foreach ( $items as $item ) : ?>
                                        <option value="<?php echo esc_attr($item['id']); ?>">
                                            <?php echo esc_html($item['title'] . ' - ' . $item['author'] . ' (' . $item['year'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="novel-proofreading-location-name"><?php _e( 'Name', 'novel-proofreading' ); ?></label>
                            </th>
                            <td>
                                <input type="text" class="regular-text" id="novel-proofreading-location-name" name="name" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="novel-proofreading-location-alias"><?php _e( 'Alias', 'novel-proofreading' ); ?></label>
                            </th>
                            <td>
                                <input type="text" class="regular-text" id="novel-proofreading-location-alias" name="alias" />
                                <p class="description"><?php _e( 'Name or alias is required.', 'novel-proofreading' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="novel-proofreading-location-area"><?php _e( 'Area', 'novel-proofreading' ); ?></label>
                            </th>
                            <td>
                                <select id="novel-proofreading-location-area" name="area">
                                    <option value=""><?php _e( 'Select area', 'novel-proofreading' ); ?></option>
                                    <?php foreach ( $area_type_items as $area_type_item ) : ?>
                                        <option value="<?php echo esc_attr($area_type_item['name']); ?>">
                                            <?php echo esc_html($area_type_item['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="novel-proofreading-location-region"><?php _e( 'Region', 'novel-proofreading' ); ?></label>
                            </th>
                            <td>
                                <input type="text" class="regular-text" id="novel-proofreading-location-region" name="region" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="novel-proofreading-location-description"><?php _e( 'Description', 'novel-proofreading' ); ?></label>
                            </th>
                            <td>
                                <textarea class="large-text" id="novel-proofreading-location-description" name="description" rows="3"></textarea>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <?php _e( 'Alternative Universe', 'novel-proofreading' ); ?>
                            </th>
                            <td>
                                <label for="novel-proofreading-location-is-in-alternative-universe">
                                    <input type="checkbox" id="novel-proofreading-location-is-in-alternative-universe" name="is_in_alternative_universe" value="Y" />
                                    <?php _e( 'Yes', 'novel-proofreading' ); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <?php _e( 'Inaccurate', 'novel-proofreading' ); ?>
                            </th>
                            <td>
                                <label for="novel-proofreading-location-is-inaccurate">
                                    <input type="checkbox" id="novel-proofreading-location-is-inaccurate" name="is_inaccurate" value="Y" />
                                    <?php _e( 'Yes', 'novel-proofreading' ); ?>
                                </label>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <button type="submit" class="button button-primary" <?php disabled(empty($items)); ?>>
                    + <?php _e( 'Add Location', 'novel-proofreading' ); ?>
                </button>
            </form>
        </div>

        <h2>5.&nbsp;<?php _e( 'Datetimes', 'novel-proofreading' ); ?></h2>
        <button class="button" onclick="show_hide('.datetimes-wrap')"><?php _e( 'Show / Hide Datetimes', 'novel-proofreading' ); ?></button>
        <div class="datetimes-wrap hidden">
            <h3>5.1&nbsp;<?php _e( 'List of Datetimes', 'novel-proofreading' ); ?></h3>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php _e( 'Book', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Name', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Time Description', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Description', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Time Type', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Inaccurate', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Edit', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Delete', 'novel-proofreading' ); ?></th>
                    </tr>
                </thead>
                <tbody id="novel-proofreading-datetimes-repeater">
                    <?php foreach ( $datetime_items as $item ) : ?>
                        <?php $datetime_form_id = 'novel-proofreading-edit-datetime-' . intval($item['id']); ?>
                        <tr>
                            <td>
                                <select form="<?php echo esc_attr($datetime_form_id); ?>" name="book_id" required>
                                    <option value=""><?php _e( 'Select book', 'novel-proofreading' ); ?></option>
                                    <?php foreach ( $items as $book_item ) : ?>
                                        <option value="<?php echo esc_attr($book_item['id']); ?>" <?php selected($item['book_id'], $book_item['id']); ?>>
                                            <?php echo esc_html($book_item['title'] . ' - ' . $book_item['author'] . ' (' . $book_item['year'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input form="<?php echo esc_attr($datetime_form_id); ?>" type="text" name="name" value="<?php echo esc_attr($item['name']); ?>" required /></td>
                            <td><textarea form="<?php echo esc_attr($datetime_form_id); ?>" name="time_description" rows="2"><?php echo esc_textarea($item['time_description']); ?></textarea></td>
                            <td><textarea form="<?php echo esc_attr($datetime_form_id); ?>" name="description" rows="2"><?php echo esc_textarea($item['description']); ?></textarea></td>
                            <td>
                                <select form="<?php echo esc_attr($datetime_form_id); ?>" name="time_type" required>
                                    <option value=""><?php _e( 'Select time type', 'novel-proofreading' ); ?></option>
                                    <?php foreach ( $datetime_type_items as $datetime_type_item ) : ?>
                                        <option value="<?php echo esc_attr($datetime_type_item['name']); ?>" <?php selected($item['time_type'], $datetime_type_item['name']); ?>>
                                            <?php echo esc_html($datetime_type_item['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input form="<?php echo esc_attr($datetime_form_id); ?>" type="checkbox" name="is_inaccurate" value="Y" <?php checked($item['is_inaccurate'], 'Y'); ?> /></td>
                            <td>
                                <form id="<?php echo esc_attr($datetime_form_id); ?>" method="post">
                                    <?php wp_nonce_field( 'novel_proofreading_books_action', 'novel_proofreading_books_nonce' ); ?>
                                    <input type="hidden" name="novel_proofreading_action" value="update_datetime" />
                                    <input type="hidden" name="datetime_id" value="<?php echo esc_attr($item['id']); ?>" />
                                    <button type="submit" class="button button-primary"><?php _e( 'Save', 'novel-proofreading' ); ?></button>
                                </form>
                            </td>
                            <td>
                                <form method="post">
                                    <?php wp_nonce_field( 'novel_proofreading_books_action', 'novel_proofreading_books_nonce' ); ?>
                                    <input type="hidden" name="novel_proofreading_action" value="remove_datetime" />
                                    <input type="hidden" name="datetime_id" value="<?php echo esc_attr($item['id']); ?>" />
                                    <button type="button" class="button remove-item" onclick="confirm_delete(this)">-</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3>5.2&nbsp;<?php _e( 'Add Datetime', 'novel-proofreading' ); ?></h3>
            <form method="post">
                <?php wp_nonce_field( 'novel_proofreading_books_action', 'novel_proofreading_books_nonce' ); ?>
                <input type="hidden" name="novel_proofreading_action" value="add_datetime" />

                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="novel-proofreading-datetime-book-id"><?php _e( 'Book', 'novel-proofreading' ); ?></label>
                            </th>
                            <td>
                                <select id="novel-proofreading-datetime-book-id" name="book_id" required>
                                    <option value=""><?php _e( 'Select book', 'novel-proofreading' ); ?></option>
                                    <?php foreach ( $items as $item ) : ?>
                                        <option value="<?php echo esc_attr($item['id']); ?>">
                                            <?php echo esc_html($item['title'] . ' - ' . $item['author'] . ' (' . $item['year'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="novel-proofreading-datetime-name"><?php _e( 'Name', 'novel-proofreading' ); ?></label>
                            </th>
                            <td>
                                <input type="text" class="regular-text" id="novel-proofreading-datetime-name" name="name" required />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="novel-proofreading-datetime-time-description"><?php _e( 'Time Description', 'novel-proofreading' ); ?></label>
                            </th>
                            <td>
                                <textarea class="large-text" id="novel-proofreading-datetime-time-description" name="time_description" rows="3"></textarea>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="novel-proofreading-datetime-description"><?php _e( 'Description', 'novel-proofreading' ); ?></label>
                            </th>
                            <td>
                                <textarea class="large-text" id="novel-proofreading-datetime-description" name="description" rows="3"></textarea>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="novel-proofreading-datetime-time-type"><?php _e( 'Time Type', 'novel-proofreading' ); ?></label>
                            </th>
                            <td>
                                <select id="novel-proofreading-datetime-time-type" name="time_type" required>
                                    <option value=""><?php _e( 'Select time type', 'novel-proofreading' ); ?></option>
                                    <?php foreach ( $datetime_type_items as $datetime_type_item ) : ?>
                                        <option value="<?php echo esc_attr($datetime_type_item['name']); ?>">
                                            <?php echo esc_html($datetime_type_item['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <?php _e( 'Inaccurate', 'novel-proofreading' ); ?>
                            </th>
                            <td>
                                <label for="novel-proofreading-datetime-is-inaccurate">
                                    <input type="checkbox" id="novel-proofreading-datetime-is-inaccurate" name="is_inaccurate" value="Y" />
                                    <?php _e( 'Yes', 'novel-proofreading' ); ?>
                                </label>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <button type="submit" class="button button-primary" <?php disabled(empty($items) || empty($datetime_type_items)); ?>>
                    + <?php _e( 'Add Datetime', 'novel-proofreading' ); ?>
                </button>
            </form>
        </div>

        <h2>6.&nbsp;<?php _e( 'Professions', 'novel-proofreading' ); ?></h2>
        <button class="button" onclick="show_hide('.professions-wrap')"><?php _e( 'Show / Hide Professions', 'novel-proofreading' ); ?></button>
        <div class="professions-wrap hidden">
            <h3>6.1&nbsp;<?php _e( 'List of Professions', 'novel-proofreading' ); ?></h3>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php _e( 'Book', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Person', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Profession', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Description', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Inaccurate', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Edit', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Delete', 'novel-proofreading' ); ?></th>
                    </tr>
                </thead>
                <tbody id="novel-proofreading-professions-repeater">
                    <?php foreach ( $profession_items as $item ) : ?>
                        <?php $profession_form_id = 'novel-proofreading-edit-profession-' . intval($item['id']); ?>
                        <tr>
                            <td>
                                <select form="<?php echo esc_attr($profession_form_id); ?>" name="book_id" required>
                                    <option value=""><?php _e( 'Select book', 'novel-proofreading' ); ?></option>
                                    <?php foreach ( $items as $book_item ) : ?>
                                        <option value="<?php echo esc_attr($book_item['id']); ?>" <?php selected($item['book_id'], $book_item['id']); ?>>
                                            <?php echo esc_html($book_item['title'] . ' - ' . $book_item['author'] . ' (' . $book_item['year'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <select form="<?php echo esc_attr($profession_form_id); ?>" name="person_id" required>
                                    <option value=""><?php _e( 'Select person', 'novel-proofreading' ); ?></option>
                                    <?php foreach ( $person_items as $person_item ) : ?>
                                        <option value="<?php echo esc_attr($person_item['id']); ?>" <?php selected($item['person_id'], $person_item['id']); ?>>
                                            <?php echo esc_html(trim($person_item['name'] . ' ' . $person_item['alias']) . ' - ' . $person_item['book_title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input form="<?php echo esc_attr($profession_form_id); ?>" type="text" name="profession_name" value="<?php echo esc_attr($item['profession_name']); ?>" required /></td>
                            <td><textarea form="<?php echo esc_attr($profession_form_id); ?>" name="description" rows="2"><?php echo esc_textarea($item['description']); ?></textarea></td>
                            <td><input form="<?php echo esc_attr($profession_form_id); ?>" type="checkbox" name="is_inaccurate" value="Y" <?php checked($item['is_inaccurate'], 'Y'); ?> /></td>
                            <td>
                                <form id="<?php echo esc_attr($profession_form_id); ?>" method="post">
                                    <?php wp_nonce_field( 'novel_proofreading_books_action', 'novel_proofreading_books_nonce' ); ?>
                                    <input type="hidden" name="novel_proofreading_action" value="update_profession" />
                                    <input type="hidden" name="profession_id" value="<?php echo esc_attr($item['id']); ?>" />
                                    <button type="submit" class="button button-primary"><?php _e( 'Save', 'novel-proofreading' ); ?></button>
                                </form>
                            </td>
                            <td>
                                <form method="post">
                                    <?php wp_nonce_field( 'novel_proofreading_books_action', 'novel_proofreading_books_nonce' ); ?>
                                    <input type="hidden" name="novel_proofreading_action" value="remove_profession" />
                                    <input type="hidden" name="profession_id" value="<?php echo esc_attr($item['id']); ?>" />
                                    <button type="button" class="button remove-item" onclick="confirm_delete(this)">-</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3>6.2&nbsp;<?php _e( 'Add Profession', 'novel-proofreading' ); ?></h3>
            <form method="post">
                <?php wp_nonce_field( 'novel_proofreading_books_action', 'novel_proofreading_books_nonce' ); ?>
                <input type="hidden" name="novel_proofreading_action" value="add_profession" />

                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="novel-proofreading-profession-book-id"><?php _e( 'Book', 'novel-proofreading' ); ?></label>
                            </th>
                            <td>
                                <select id="novel-proofreading-profession-book-id" name="book_id" required>
                                    <option value=""><?php _e( 'Select book', 'novel-proofreading' ); ?></option>
                                    <?php foreach ( $items as $item ) : ?>
                                        <option value="<?php echo esc_attr($item['id']); ?>">
                                            <?php echo esc_html($item['title'] . ' - ' . $item['author'] . ' (' . $item['year'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="novel-proofreading-profession-person-id"><?php _e( 'Person', 'novel-proofreading' ); ?></label>
                            </th>
                            <td>
                                <select id="novel-proofreading-profession-person-id" name="person_id" required>
                                    <option value=""><?php _e( 'Select person', 'novel-proofreading' ); ?></option>
                                    <?php foreach ( $person_items as $item ) : ?>
                                        <option value="<?php echo esc_attr($item['id']); ?>">
                                            <?php echo esc_html(trim($item['name'] . ' ' . $item['alias']) . ' - ' . $item['book_title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="novel-proofreading-profession-name"><?php _e( 'Profession', 'novel-proofreading' ); ?></label>
                            </th>
                            <td>
                                <input type="text" id="novel-proofreading-profession-name" name="profession_name" required />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="novel-proofreading-profession-description"><?php _e( 'Description', 'novel-proofreading' ); ?></label>
                            </th>
                            <td>
                                <textarea id="novel-proofreading-profession-description" name="description" rows="3"></textarea>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <?php _e( 'Inaccurate', 'novel-proofreading' ); ?>
                            </th>
                            <td>
                                <label for="novel-proofreading-profession-is-inaccurate">
                                    <input type="checkbox" id="novel-proofreading-profession-is-inaccurate" name="is_inaccurate" value="Y" />
                                    <?php _e( 'Yes', 'novel-proofreading' ); ?>
                                </label>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <button type="submit" class="button button-primary" <?php disabled(empty($items) || empty($person_items)); ?>>
                    + <?php _e( 'Add Profession', 'novel-proofreading' ); ?>
                </button>
            </form>
        </div>

        <h2>7.&nbsp;<?php _e( 'Storylines', 'novel-proofreading' ); ?></h2>
        <button class="button" onclick="show_hide('.storylines-wrap')"><?php _e( 'Show / Hide Storylines', 'novel-proofreading' ); ?></button>
        <div class="storylines-wrap hidden">
            <h3>7.1&nbsp;<?php _e( 'List of Storylines', 'novel-proofreading' ); ?></h3>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php _e( 'Book', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Storyline', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Main event', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Description', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Edit', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Delete', 'novel-proofreading' ); ?></th>
                    </tr>
                </thead>
                <tbody id="novel-proofreading-storylines-repeater">
                    <?php foreach ( $storyline_items as $item ) : ?>
                        <?php $storyline_form_id = 'novel-proofreading-edit-storyline-' . intval($item['id']); ?>
                        <tr>
                            <td>
                                <select form="<?php echo esc_attr($storyline_form_id); ?>" name="book_id" class="novel-proofreading-book-select" required>
                                    <option value=""><?php _e( 'Select book', 'novel-proofreading' ); ?></option>
                                    <?php foreach ( $items as $book_item ) : ?>
                                        <option value="<?php echo esc_attr($book_item['id']); ?>" <?php selected($item['book_id'], $book_item['id']); ?>>
                                            <?php echo esc_html($book_item['title'] . ' - ' . $book_item['author'] . ' (' . $book_item['year'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input form="<?php echo esc_attr($storyline_form_id); ?>" type="text" name="storyline_name" value="<?php echo esc_attr($item['storyline_name']); ?>" required /></td>
                            <td>
                                <select form="<?php echo esc_attr($storyline_form_id); ?>" name="main_event" class="novel-proofreading-event-select">
                                    <option value="0"><?php _e( 'Select event', 'novel-proofreading' ); ?></option>
                                    <?php foreach ( $event_items as $event_item ) : ?>
                                        <option value="<?php echo esc_attr($event_item['id']); ?>" data-book-id="<?php echo esc_attr($event_item['book_id']); ?>" <?php selected($item['main_event'], $event_item['id']); ?>>
                                            <?php echo esc_html($event_item['event_name'] . ' - ' . $event_item['book_title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><textarea form="<?php echo esc_attr($storyline_form_id); ?>" name="description" rows="2"><?php echo esc_textarea($item['description']); ?></textarea></td>
                            <td>
                                <form id="<?php echo esc_attr($storyline_form_id); ?>" method="post">
                                    <?php wp_nonce_field( 'novel_proofreading_books_action', 'novel_proofreading_books_nonce' ); ?>
                                    <input type="hidden" name="novel_proofreading_action" value="update_storyline" />
                                    <input type="hidden" name="storyline_id" value="<?php echo esc_attr($item['id']); ?>" />
                                    <button type="submit" class="button button-primary"><?php _e( 'Save', 'novel-proofreading' ); ?></button>
                                </form>
                            </td>
                            <td>
                                <form method="post">
                                    <?php wp_nonce_field( 'novel_proofreading_books_action', 'novel_proofreading_books_nonce' ); ?>
                                    <input type="hidden" name="novel_proofreading_action" value="remove_storyline" />
                                    <input type="hidden" name="storyline_id" value="<?php echo esc_attr($item['id']); ?>" />
                                    <button type="button" class="button remove-item" onclick="confirm_delete(this)">-</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3>7.2&nbsp;<?php _e( 'Add Storyline', 'novel-proofreading' ); ?></h3>
            <form method="post">
                <?php wp_nonce_field( 'novel_proofreading_books_action', 'novel_proofreading_books_nonce' ); ?>
                <input type="hidden" name="novel_proofreading_action" value="add_storyline" />

                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="novel-proofreading-storyline-book-id"><?php _e( 'Book', 'novel-proofreading' ); ?></label>
                            </th>
                            <td>
                                <select id="novel-proofreading-storyline-book-id" name="book_id" class="novel-proofreading-book-select" required>
                                    <option value=""><?php _e( 'Select book', 'novel-proofreading' ); ?></option>
                                    <?php foreach ( $items as $item ) : ?>
                                        <option value="<?php echo esc_attr($item['id']); ?>">
                                            <?php echo esc_html($item['title'] . ' - ' . $item['author'] . ' (' . $item['year'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="novel-proofreading-storyline-name"><?php _e( 'Storyline', 'novel-proofreading' ); ?></label>
                            </th>
                            <td>
                                <input type="text" id="novel-proofreading-storyline-name" name="storyline_name" required />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="novel-proofreading-storyline-main-event"><?php _e( 'Main event', 'novel-proofreading' ); ?></label>
                            </th>
                            <td>
                                <select id="novel-proofreading-storyline-main-event" name="main_event" class="novel-proofreading-event-select">
                                    <option value="0"><?php _e( 'Select event', 'novel-proofreading' ); ?></option>
                                    <?php foreach ( $event_items as $event_item ) : ?>
                                        <option value="<?php echo esc_attr($event_item['id']); ?>" data-book-id="<?php echo esc_attr($event_item['book_id']); ?>">
                                            <?php echo esc_html($event_item['event_name'] . ' - ' . $event_item['book_title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="novel-proofreading-storyline-description"><?php _e( 'Description', 'novel-proofreading' ); ?></label>
                            </th>
                            <td>
                                <textarea id="novel-proofreading-storyline-description" name="description" rows="3"></textarea>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <button type="submit" class="button button-primary" <?php disabled(empty($items)); ?>>
                    + <?php _e( 'Add Storyline', 'novel-proofreading' ); ?>
                </button>
            </form>
        </div>

        <h2>8.&nbsp;<?php _e( 'Events', 'novel-proofreading' ); ?></h2>
        <button class="button" onclick="show_hide('.events-wrap')"><?php _e( 'Show / Hide Events', 'novel-proofreading' ); ?></button>
        <div class="events-wrap hidden">
            <h3>8.1&nbsp;<?php _e( 'List of Events', 'novel-proofreading' ); ?></h3>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php _e( 'Book', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Storyline', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Sequence', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Chain role', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Narrative', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Event name', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Description', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Edit', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Delete', 'novel-proofreading' ); ?></th>
                    </tr>
                </thead>
                <tbody id="novel-proofreading-events-repeater">
                    <?php foreach ( $event_items as $item ) : ?>
                        <?php $event_form_id = 'novel-proofreading-edit-event-' . intval($item['id']); ?>
                        <tr>
                            <td>
                                <select form="<?php echo esc_attr($event_form_id); ?>" name="book_id" class="novel-proofreading-book-select" required>
                                    <option value=""><?php _e( 'Select book', 'novel-proofreading' ); ?></option>
                                    <?php foreach ( $items as $book_item ) : ?>
                                        <option value="<?php echo esc_attr($book_item['id']); ?>" <?php selected($item['book_id'], $book_item['id']); ?>>
                                            <?php echo esc_html($book_item['title'] . ' - ' . $book_item['author'] . ' (' . $book_item['year'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <select form="<?php echo esc_attr($event_form_id); ?>" name="storyline_id" class="novel-proofreading-storyline-select">
                                    <option value=""><?php _e( 'Select storyline', 'novel-proofreading' ); ?></option>
                                    <?php foreach ( $storyline_items as $storyline_item ) : ?>
                                        <option value="<?php echo esc_attr($storyline_item['id']); ?>" data-book-id="<?php echo esc_attr($storyline_item['book_id']); ?>" <?php selected($item['storyline_id'], $storyline_item['id']); ?>>
                                            <?php echo esc_html(trim($storyline_item['storyline_name'] . ' ') . $storyline_item['book_title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input form="<?php echo esc_attr($event_form_id); ?>" type="number" min="0" name="sequence_no" value="<?php echo esc_attr($item['sequence_no']); ?>" /></td>
                            <td>
                                <select form="<?php echo esc_attr($event_form_id); ?>" name="chain_role">
                                    <?php foreach ( $chain_role_items as $role_key => $role_label ) : ?>
                                        <option value="<?php echo esc_attr($role_key); ?>" <?php selected($item['chain_role'], $role_key); ?>>
                                            <?php echo esc_html($role_label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input form="<?php echo esc_attr($event_form_id); ?>" type="checkbox" name="is_narrative" value="Y" <?php checked($item['is_narrative'], 'Y'); ?> /></td>
                            <td><input form="<?php echo esc_attr($event_form_id); ?>" type="text" name="event_name" value="<?php echo esc_attr($item['event_name']); ?>" required /></td>
                            <td><textarea form="<?php echo esc_attr($event_form_id); ?>" name="description" rows="2"><?php echo esc_textarea($item['description']); ?></textarea></td>
                            <td>
                                <form id="<?php echo esc_attr($event_form_id); ?>" method="post">
                                    <?php wp_nonce_field( 'novel_proofreading_books_action', 'novel_proofreading_books_nonce' ); ?>
                                    <input type="hidden" name="novel_proofreading_action" value="update_event" />
                                    <input type="hidden" name="event_id" value="<?php echo esc_attr($item['id']); ?>" />
                                    <button type="submit" class="button button-primary"><?php _e( 'Save', 'novel-proofreading' ); ?></button>
                                </form>
                            </td>
                            <td>
                                <form method="post">
                                    <?php wp_nonce_field( 'novel_proofreading_books_action', 'novel_proofreading_books_nonce' ); ?>
                                    <input type="hidden" name="novel_proofreading_action" value="remove_event" />
                                    <input type="hidden" name="event_id" value="<?php echo esc_attr($item['id']); ?>" />
                                    <button type="button" class="button remove-item" onclick="confirm_delete(this)">-</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3>8.2&nbsp;<?php _e( 'Add Event', 'novel-proofreading' ); ?></h3>
            <form method="post">
                <?php wp_nonce_field( 'novel_proofreading_books_action', 'novel_proofreading_books_nonce' ); ?>
                <input type="hidden" name="novel_proofreading_action" value="add_event" />

                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="novel-proofreading-event-book-id"><?php _e( 'Book', 'novel-proofreading' ); ?></label>
                            </th>
                            <td>
                                <select id="novel-proofreading-event-book-id" name="book_id" class="novel-proofreading-book-select" required>
                                    <option value=""><?php _e( 'Select book', 'novel-proofreading' ); ?></option>
                                    <?php foreach ( $items as $item ) : ?>
                                        <option value="<?php echo esc_attr($item['id']); ?>">
                                            <?php echo esc_html($item['title'] . ' - ' . $item['author'] . ' (' . $item['year'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="novel-proofreading-event-storyline-id"><?php _e( 'Storyline', 'novel-proofreading' ); ?></label>
                            </th>
                            <td>
                                <select id="novel-proofreading-event-storyline-id" name="storyline_id" class="novel-proofreading-storyline-select">
                                    <option value=""><?php _e( 'Select storyline', 'novel-proofreading' ); ?></option>
                                    <?php foreach ( $storyline_items as $item ) : ?>
                                        <option value="<?php echo esc_attr($item['id']); ?>" data-book-id="<?php echo esc_attr($item['book_id']); ?>">
                                            <?php echo esc_html(trim($item['storyline_name'] . ' ') . $item['book_title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="novel-proofreading-event-sequence-no"><?php _e( 'Sequence', 'novel-proofreading' ); ?></label>
                            </th>
                            <td>
                                <input type="number" min="0" id="novel-proofreading-event-sequence-no" name="sequence_no" value="0" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="novel-proofreading-event-chain-role"><?php _e( 'Chain role', 'novel-proofreading' ); ?></label>
                            </th>
                            <td>
                                <select id="novel-proofreading-event-chain-role" name="chain_role">
                                    <?php foreach ( $chain_role_items as $role_key => $role_label ) : ?>
                                        <option value="<?php echo esc_attr($role_key); ?>">
                                            <?php echo esc_html($role_label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="novel-proofreading-event-name"><?php _e( 'Event', 'novel-proofreading' ); ?></label>
                            </th>
                            <td>
                                <input type="text" id="novel-proofreading-event-name" name="event_name" required />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <?php _e( 'Narrative', 'novel-proofreading' ); ?>
                            </th>
                            <td>
                                <label for="novel-proofreading-event-is-narrative">
                                    <input type="checkbox" id="novel-proofreading-event-is-narrative" name="is_narrative" value="Y" />
                                    <?php _e( 'Yes', 'novel-proofreading' ); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="novel-proofreading-event-description"><?php _e( 'Description', 'novel-proofreading' ); ?></label>
                            </th>
                            <td>
                                <textarea id="novel-proofreading-event-description" name="description" rows="3"></textarea>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <button type="submit" class="button button-primary" <?php disabled(empty($items)); ?>>
                    + <?php _e( 'Add Event', 'novel-proofreading' ); ?>
                </button>
            </form>
        </div>

        <h2>9.&nbsp;<?php _e( 'Storyline Chains', 'novel-proofreading' ); ?></h2>
        <button class="button" onclick="show_hide('.storyline-chains-wrap')"><?php _e( 'Show / Hide Storyline Chains', 'novel-proofreading' ); ?></button>
        <div class="storyline-chains-wrap hidden">
            <form method="get" class="novel-proofreading-chain-filter">
                <input type="hidden" name="page" value="novel-proofreading" />
                <label for="novel-proofreading-chain-book-id"><?php _e( 'Book', 'novel-proofreading' ); ?></label>
                <select id="novel-proofreading-chain-book-id" name="chain_book_id">
                    <option value="0"><?php _e( 'All books', 'novel-proofreading' ); ?></option>
                    <?php foreach ( $items as $item ) : ?>
                        <option value="<?php echo esc_attr($item['id']); ?>" <?php selected($selected_chain_book_id, $item['id']); ?>>
                            <?php echo esc_html($item['title'] . ' - ' . $item['author'] . ' (' . $item['year'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="button"><?php _e( 'Filter', 'novel-proofreading' ); ?></button>
            </form>

            <?php if (empty($storyline_chain_items)) : ?>
                <p><?php _e( 'No storyline chains found.', 'novel-proofreading' ); ?></p>
            <?php endif; ?>

            <?php foreach ( $storyline_chain_items as $chain ) : ?>
                <?php $stats = $chain['stats']; ?>
                <div class="novel-proofreading-chain">
                    <h3>
                        <?php echo esc_html($chain['storyline_name']); ?>
                        <span class="description"><?php echo esc_html($chain['book_title']); ?></span>
                    </h3>

                    <div class="novel-proofreading-chain-stats">
                        <span class="novel-proofreading-badge"><?php echo esc_html(sprintf(__('Events: %d', 'novel-proofreading'), $stats['event_count'])); ?></span>
                        <span class="novel-proofreading-badge <?php echo $stats['has_opening'] ? 'is-ok' : 'is-warning'; ?>"><?php echo esc_html($stats['has_opening'] ? __('Has opening', 'novel-proofreading') : __('Missing opening', 'novel-proofreading')); ?></span>
                        <span class="novel-proofreading-badge <?php echo $stats['has_closing'] ? 'is-ok' : 'is-warning'; ?>"><?php echo esc_html($stats['has_closing'] ? __('Closed', 'novel-proofreading') : __('Missing closing', 'novel-proofreading')); ?></span>
                        <?php if ($stats['has_suggestion']) : ?>
                            <button type="button" class="novel-proofreading-badge is-info novel-proofreading-storyline-suggestion-badge" data-storyline-id="<?php echo esc_attr($chain['id']); ?>">
                                <?php _e( 'Has suggestion', 'novel-proofreading' ); ?>
                            </button>
                        <?php endif; ?>
                        <?php if ($stats['has_return']) : ?>
                            <span class="novel-proofreading-badge is-info"><?php _e( 'Has return', 'novel-proofreading' ); ?></span>
                        <?php endif; ?>
                        <?php if ($stats['first_reference'] !== '') : ?>
                            <span class="novel-proofreading-badge"><?php echo esc_html(sprintf(__('First: %s', 'novel-proofreading'), $stats['first_reference'])); ?></span>
                        <?php endif; ?>
                        <?php if ($stats['last_reference'] !== '') : ?>
                            <span class="novel-proofreading-badge"><?php echo esc_html(sprintf(__('Last: %s', 'novel-proofreading'), $stats['last_reference'])); ?></span>
                        <?php endif; ?>
                        <?php if (! empty($chain['storyline_references'])) : ?>
                            <span class="novel-proofreading-badge"><?php echo esc_html(sprintf(__('Storyline refs: %s', 'novel-proofreading'), implode(', ', $chain['storyline_references']))); ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if ($stats['event_count'] === 0) : ?>
                        <p class="notice notice-warning inline"><?php _e( 'This storyline has no linked events.', 'novel-proofreading' ); ?></p>
                    <?php else : ?>
                        <table class="widefat striped novel-proofreading-chain-table">
                            <thead>
                                <tr>
                                    <th><?php _e( 'Sequence', 'novel-proofreading' ); ?></th>
                                    <th><?php _e( 'Role', 'novel-proofreading' ); ?></th>
                                    <th><?php _e( 'Event', 'novel-proofreading' ); ?></th>
                                    <th><?php _e( 'Description', 'novel-proofreading' ); ?></th>
                                    <th><?php _e( 'References', 'novel-proofreading' ); ?></th>
                                    <th><?php _e( 'Storyline', 'novel-proofreading' ); ?></th>
                                    <th><?php _e( 'Save', 'novel-proofreading' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $chain['events'] as $event ) : ?>
                                    <?php $chain_form_id = 'novel-proofreading-chain-event-' . intval($event['id']); ?>
                                    <tr>
                                        <td><input form="<?php echo esc_attr($chain_form_id); ?>" type="number" min="0" name="sequence_no" value="<?php echo esc_attr($event['sequence_no']); ?>" /></td>
                                        <td>
                                            <select form="<?php echo esc_attr($chain_form_id); ?>" name="chain_role">
                                                <?php foreach ( $chain_role_items as $role_key => $role_label ) : ?>
                                                    <option value="<?php echo esc_attr($role_key); ?>" <?php selected($event['chain_role'], $role_key); ?>>
                                                        <?php echo esc_html($role_label); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td><?php echo esc_html($event['event_name']); ?></td>
                                        <td><?php echo esc_html($event['description']); ?></td>
                                        <td><?php echo esc_html(implode(', ', array_unique($event['references']))); ?></td>
                                        <td>
                                            <select form="<?php echo esc_attr($chain_form_id); ?>" name="storyline_id">
                                                <option value="0"><?php _e( 'Select storyline', 'novel-proofreading' ); ?></option>
                                                <?php foreach ( $storyline_items as $storyline_item ) : ?>
                                                    <?php if (intval($storyline_item['book_id']) !== intval($chain['book_id'])) { continue; } ?>
                                                    <option value="<?php echo esc_attr($storyline_item['id']); ?>" <?php selected($chain['id'], $storyline_item['id']); ?>>
                                                        <?php echo esc_html($storyline_item['storyline_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <form id="<?php echo esc_attr($chain_form_id); ?>" method="post">
                                                <?php wp_nonce_field( 'novel_proofreading_books_action', 'novel_proofreading_books_nonce' ); ?>
                                                <input type="hidden" name="novel_proofreading_action" value="update_chain_event" />
                                                <input type="hidden" name="event_id" value="<?php echo esc_attr($event['id']); ?>" />
                                                <button type="submit" class="button button-primary"><?php _e( 'Save', 'novel-proofreading' ); ?></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <h2>10.&nbsp;<?php _e( 'Manuscript references', 'novel-proofreading' ); ?></h2>
        <button class="button" onclick="show_hide('.manuscript-references-wrap')"><?php _e( 'Show / Hide Manuscript References', 'novel-proofreading' ); ?></button>
        <div class="manuscript-references-wrap hidden">
            <form method="get" class="novel-proofreading-chain-filter">
                <input type="hidden" name="page" value="novel-proofreading" />
                <label for="novel-proofreading-reference-book-filter"><?php _e( 'Book', 'novel-proofreading' ); ?></label>
                <select id="novel-proofreading-reference-book-filter" name="reference_book_id">
                    <option value="0"><?php _e( 'All books', 'novel-proofreading' ); ?></option>
                    <?php foreach ( $items as $item ) : ?>
                        <option value="<?php echo esc_attr($item['id']); ?>" <?php selected($selected_reference_book_id, $item['id']); ?>>
                            <?php echo esc_html($item['title'] . ' - ' . $item['author'] . ' (' . $item['year'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <label for="novel-proofreading-reference-type-filter"><?php _e( 'Type', 'novel-proofreading' ); ?></label>
                <select id="novel-proofreading-reference-type-filter" name="reference_type">
                    <option value=""><?php _e( 'All types', 'novel-proofreading' ); ?></option>
                    <?php foreach ( $common_type_items as $type_item ) : ?>
                        <option value="<?php echo esc_attr($type_item['name']); ?>" <?php selected($selected_reference_type, $type_item['name']); ?>>
                            <?php echo esc_html($type_item['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="button"><?php _e( 'Filter', 'novel-proofreading' ); ?></button>
            </form>

            <h3>10.1&nbsp;<?php _e( 'List of Manuscript References', 'novel-proofreading' ); ?></h3>
            <table class="widefat striped novel-proofreading-reference-table">
                <thead>
                    <tr>
                        <th><?php _e( 'Book', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Type', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Referenced item', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Chapter', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Page', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Description', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Edit', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Delete', 'novel-proofreading' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $manuscript_reference_items as $reference_item ) : ?>
                        <?php $reference_form_id = 'novel-proofreading-edit-reference-' . intval($reference_item['id']); ?>
                        <tr class="novel-proofreading-reference-row">
                            <td>
                                <select form="<?php echo esc_attr($reference_form_id); ?>" name="book_id" class="novel-proofreading-book-select" required>
                                    <option value=""><?php _e( 'Select book', 'novel-proofreading' ); ?></option>
                                    <?php foreach ( $items as $book_item ) : ?>
                                        <option value="<?php echo esc_attr($book_item['id']); ?>" <?php selected($reference_item['book_id'], $book_item['id']); ?>>
                                            <?php echo esc_html($book_item['title'] . ' - ' . $book_item['author'] . ' (' . $book_item['year'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <select form="<?php echo esc_attr($reference_form_id); ?>" name="type" class="novel-proofreading-reference-type-select" required>
                                    <option value=""><?php _e( 'Select type', 'novel-proofreading' ); ?></option>
                                    <?php foreach ( $common_type_items as $type_item ) : ?>
                                        <option value="<?php echo esc_attr($type_item['name']); ?>" <?php selected($reference_item['type'], $type_item['name']); ?>>
                                            <?php echo esc_html($type_item['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <div class="novel-proofreading-reference-entity" data-reference-entity="STORYLINE">
                                    <select form="<?php echo esc_attr($reference_form_id); ?>" name="storyline_id" class="novel-proofreading-storyline-select">
                                        <option value="0"><?php _e( 'Select storyline', 'novel-proofreading' ); ?></option>
                                        <?php foreach ( $storyline_items as $storyline_item ) : ?>
                                            <option value="<?php echo esc_attr($storyline_item['id']); ?>" data-book-id="<?php echo esc_attr($storyline_item['book_id']); ?>" <?php selected($reference_item['storyline_id'], $storyline_item['id']); ?>>
                                                <?php echo esc_html(trim($storyline_item['storyline_name'] . ' ') . $storyline_item['book_title']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="novel-proofreading-reference-entity" data-reference-entity="EVENT">
                                    <select form="<?php echo esc_attr($reference_form_id); ?>" name="event_id" class="novel-proofreading-event-select">
                                        <option value="0"><?php _e( 'Select event', 'novel-proofreading' ); ?></option>
                                        <?php foreach ( $event_items as $event_item ) : ?>
                                            <option value="<?php echo esc_attr($event_item['id']); ?>" data-book-id="<?php echo esc_attr($event_item['book_id']); ?>" <?php selected($reference_item['event_id'], $event_item['id']); ?>>
                                                <?php echo esc_html($event_item['event_name'] . ' - ' . $event_item['book_title']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="novel-proofreading-reference-entity" data-reference-entity="PERSON">
                                    <select form="<?php echo esc_attr($reference_form_id); ?>" name="person_id" class="novel-proofreading-person-select">
                                        <option value="0"><?php _e( 'Select person', 'novel-proofreading' ); ?></option>
                                        <?php foreach ( $person_items as $person_item ) : ?>
                                            <option value="<?php echo esc_attr($person_item['id']); ?>" data-book-id="<?php echo esc_attr($person_item['book_id']); ?>" <?php selected($reference_item['person_id'], $person_item['id']); ?>>
                                                <?php echo esc_html(trim($person_item['name'] . ' ' . $person_item['alias']) . ' - ' . $person_item['book_title']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="novel-proofreading-reference-entity" data-reference-entity="LOCATION">
                                    <select form="<?php echo esc_attr($reference_form_id); ?>" name="location_id" class="novel-proofreading-location-select">
                                        <option value="0"><?php _e( 'Select location', 'novel-proofreading' ); ?></option>
                                        <?php foreach ( $location_items as $location_item ) : ?>
                                            <option value="<?php echo esc_attr($location_item['id']); ?>" data-book-id="<?php echo esc_attr($location_item['book_id']); ?>" <?php selected($reference_item['location_id'], $location_item['id']); ?>>
                                                <?php echo esc_html(trim($location_item['name'] . ' ' . $location_item['alias']) . ' - ' . $location_item['book_title']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="novel-proofreading-reference-entity" data-reference-entity="TIME">
                                    <select form="<?php echo esc_attr($reference_form_id); ?>" name="time_id" class="novel-proofreading-time-select">
                                        <option value="0"><?php _e( 'Select time', 'novel-proofreading' ); ?></option>
                                        <?php foreach ( $datetime_items as $datetime_item ) : ?>
                                            <option value="<?php echo esc_attr($datetime_item['id']); ?>" data-book-id="<?php echo esc_attr($datetime_item['book_id']); ?>" <?php selected($reference_item['time_id'], $datetime_item['id']); ?>>
                                                <?php echo esc_html($datetime_item['name'] . ' (' . $datetime_item['time_description'] . ') - ' . $datetime_item['book_title']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="novel-proofreading-reference-entity" data-reference-entity="RELIC">
                                    <select form="<?php echo esc_attr($reference_form_id); ?>" name="relics_id" class="novel-proofreading-relic-select">
                                        <option value="0"><?php _e( 'Select relic', 'novel-proofreading' ); ?></option>
                                        <?php foreach ( $relic_items as $relic_item ) : ?>
                                            <option value="<?php echo esc_attr($relic_item['id']); ?>" data-book-id="<?php echo esc_attr($relic_item['book_id']); ?>" <?php selected($reference_item['relics_id'], $relic_item['id']); ?>>
                                                <?php echo esc_html($relic_item['relic_name'] . ' - ' . $relic_item['book_title']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="novel-proofreading-reference-entity" data-reference-entity="MULTI">
                                    <span class="description"><?php echo esc_html($reference_item['entity_label']); ?></span>
                                </div>
                            </td>
                            <td><input form="<?php echo esc_attr($reference_form_id); ?>" type="text" name="chapter" value="<?php echo esc_attr($reference_item['chapter']); ?>" /></td>
                            <td><input form="<?php echo esc_attr($reference_form_id); ?>" type="text" name="page" value="<?php echo esc_attr($reference_item['page']); ?>" /></td>
                            <td><textarea form="<?php echo esc_attr($reference_form_id); ?>" name="description" rows="2"><?php echo esc_textarea($reference_item['description']); ?></textarea></td>
                            <td>
                                <form id="<?php echo esc_attr($reference_form_id); ?>" method="post">
                                    <?php wp_nonce_field( 'novel_proofreading_books_action', 'novel_proofreading_books_nonce' ); ?>
                                    <input type="hidden" name="novel_proofreading_action" value="update_manuscript_reference" />
                                    <input type="hidden" name="manuscript_reference_id" value="<?php echo esc_attr($reference_item['id']); ?>" />
                                    <button type="submit" class="button button-primary"><?php _e( 'Save', 'novel-proofreading' ); ?></button>
                                </form>
                            </td>
                            <td>
                                <form method="post">
                                    <?php wp_nonce_field( 'novel_proofreading_books_action', 'novel_proofreading_books_nonce' ); ?>
                                    <input type="hidden" name="novel_proofreading_action" value="remove_manuscript_reference" />
                                    <input type="hidden" name="manuscript_reference_id" value="<?php echo esc_attr($reference_item['id']); ?>" />
                                    <button type="button" class="button remove-item" onclick="confirm_delete(this)">-</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3>10.2&nbsp;<?php _e( 'Add Manuscript Reference', 'novel-proofreading' ); ?></h3>
            <form method="post" class="novel-proofreading-reference-form">
                <?php wp_nonce_field( 'novel_proofreading_books_action', 'novel_proofreading_books_nonce' ); ?>
                <input type="hidden" name="novel_proofreading_action" value="add_manuscript_reference" />

                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="novel-proofreading-reference-book-id"><?php _e( 'Book', 'novel-proofreading' ); ?></label>
                            </th>
                            <td>
                                <select id="novel-proofreading-reference-book-id" name="book_id" class="novel-proofreading-book-select" required>
                                    <option value=""><?php _e( 'Select book', 'novel-proofreading' ); ?></option>
                                    <?php foreach ( $items as $item ) : ?>
                                        <option value="<?php echo esc_attr($item['id']); ?>">
                                            <?php echo esc_html($item['title'] . ' - ' . $item['author'] . ' (' . $item['year'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="novel-proofreading-reference-type"><?php _e( 'Type', 'novel-proofreading' ); ?></label>
                            </th>
                            <td>
                                <select id="novel-proofreading-reference-type" name="type" class="novel-proofreading-reference-type-select" required>
                                    <option value=""><?php _e( 'Select type', 'novel-proofreading' ); ?></option>
                                    <?php foreach ( $common_type_items as $type_item ) : ?>
                                        <option value="<?php echo esc_attr($type_item['name']); ?>">
                                            <?php echo esc_html($type_item['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e( 'Referenced item', 'novel-proofreading' ); ?></th>
                            <td>
                                <div class="novel-proofreading-reference-entity" data-reference-entity="STORYLINE">
                                    <select name="storyline_id" class="novel-proofreading-storyline-select">
                                        <option value="0"><?php _e( 'Select storyline', 'novel-proofreading' ); ?></option>
                                        <?php foreach ( $storyline_items as $item ) : ?>
                                            <option value="<?php echo esc_attr($item['id']); ?>" data-book-id="<?php echo esc_attr($item['book_id']); ?>">
                                                <?php echo esc_html(trim($item['storyline_name'] . ' ') . $item['book_title']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="novel-proofreading-reference-entity" data-reference-entity="EVENT">
                                    <select name="event_id" class="novel-proofreading-event-select">
                                        <option value="0"><?php _e( 'Select event', 'novel-proofreading' ); ?></option>
                                        <?php foreach ( $event_items as $item ) : ?>
                                            <option value="<?php echo esc_attr($item['id']); ?>" data-book-id="<?php echo esc_attr($item['book_id']); ?>">
                                                <?php echo esc_html($item['event_name'] . ' - ' . $item['book_title']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="novel-proofreading-reference-entity" data-reference-entity="PERSON">
                                    <select name="person_id" class="novel-proofreading-person-select">
                                        <option value="0"><?php _e( 'Select person', 'novel-proofreading' ); ?></option>
                                        <?php foreach ( $person_items as $item ) : ?>
                                            <option value="<?php echo esc_attr($item['id']); ?>" data-book-id="<?php echo esc_attr($item['book_id']); ?>">
                                                <?php echo esc_html(trim($item['name'] . ' ' . $item['alias']) . ' - ' . $item['book_title']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="novel-proofreading-reference-entity" data-reference-entity="LOCATION">
                                    <select name="location_id" class="novel-proofreading-location-select">
                                        <option value="0"><?php _e( 'Select location', 'novel-proofreading' ); ?></option>
                                        <?php foreach ( $location_items as $item ) : ?>
                                            <option value="<?php echo esc_attr($item['id']); ?>" data-book-id="<?php echo esc_attr($item['book_id']); ?>">
                                                <?php echo esc_html(trim($item['name'] . ' ' . $item['alias']) . ' - ' . $item['book_title']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="novel-proofreading-reference-entity" data-reference-entity="TIME">
                                    <select name="time_id" class="novel-proofreading-time-select">
                                        <option value="0"><?php _e( 'Select time', 'novel-proofreading' ); ?></option>
                                        <?php foreach ( $datetime_items as $item ) : ?>
                                            <option value="<?php echo esc_attr($item['id']); ?>" data-book-id="<?php echo esc_attr($item['book_id']); ?>">
                                                <?php echo esc_html($item['name'] . ' (' . $item['time_description'] . ') - ' . $item['book_title']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="novel-proofreading-reference-entity" data-reference-entity="RELIC">
                                    <select name="relics_id" class="novel-proofreading-relic-select">
                                        <option value="0"><?php _e( 'Select relic', 'novel-proofreading' ); ?></option>
                                        <?php foreach ( $relic_items as $item ) : ?>
                                            <option value="<?php echo esc_attr($item['id']); ?>" data-book-id="<?php echo esc_attr($item['book_id']); ?>">
                                                <?php echo esc_html($item['relic_name'] . ' - ' . $item['book_title']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="novel-proofreading-reference-entity" data-reference-entity="MULTI">
                                    <p class="description"><?php _e( 'For mistake, suggestion and agreement references, use the description field in this first version.', 'novel-proofreading' ); ?></p>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="novel-proofreading-reference-chapter"><?php _e( 'Chapter', 'novel-proofreading' ); ?></label>
                            </th>
                            <td>
                                <input type="text" id="novel-proofreading-reference-chapter" name="chapter" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="novel-proofreading-reference-page"><?php _e( 'Page', 'novel-proofreading' ); ?></label>
                            </th>
                            <td>
                                <input type="text" id="novel-proofreading-reference-page" name="page" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="novel-proofreading-reference-description"><?php _e( 'Description', 'novel-proofreading' ); ?></label>
                            </th>
                            <td>
                                <textarea id="novel-proofreading-reference-description" name="description" rows="3"></textarea>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <button type="submit" class="button button-primary" <?php disabled(empty($items)); ?>>
                    + <?php _e( 'Add Manuscript Reference', 'novel-proofreading' ); ?>
                </button>
            </form>
        </div>

        <h2>11.&nbsp;<?php _e( 'Relics', 'novel-proofreading' ); ?></h2>
        <button class="button" onclick="show_hide('.relics-wrap')"><?php _e( 'Show / Hide Relics', 'novel-proofreading' ); ?></button>
        <div class="relics-wrap hidden">
            <h3>11.1&nbsp;<?php _e( 'List of Relics', 'novel-proofreading' ); ?></h3>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php _e( 'Book', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Relic', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Description', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Inaccurate', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Edit', 'novel-proofreading' ); ?></th>
                        <th><?php _e( 'Delete', 'novel-proofreading' ); ?></th>
                    </tr>
                </thead>
                <tbody id="novel-proofreading-relics-repeater">
                    <?php foreach ( $relic_items as $item ) : ?>
                        <?php $relic_form_id = 'novel-proofreading-edit-relic-' . intval($item['id']); ?>
                        <tr>
                            <td>
                                <select form="<?php echo esc_attr($relic_form_id); ?>" name="book_id" required>
                                    <option value=""><?php _e( 'Select book', 'novel-proofreading' ); ?></option>
                                    <?php foreach ( $items as $book_item ) : ?>
                                        <option value="<?php echo esc_attr($book_item['id']); ?>" <?php selected($item['book_id'], $book_item['id']); ?>>
                                            <?php echo esc_html($book_item['title'] . ' - ' . $book_item['author'] . ' (' . $book_item['year'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input form="<?php echo esc_attr($relic_form_id); ?>" type="text" name="relic_name" value="<?php echo esc_attr($item['relic_name']); ?>" required /></td>
                            <td><textarea form="<?php echo esc_attr($relic_form_id); ?>" name="description" rows="2"><?php echo esc_textarea($item['description']); ?></textarea></td>
                            <td><input form="<?php echo esc_attr($relic_form_id); ?>" type="checkbox" name="is_inaccurate" value="Y" <?php checked($item['is_inaccurate'], 'Y'); ?> /></td>
                            <td>
                                <form id="<?php echo esc_attr($relic_form_id); ?>" method="post">
                                    <?php wp_nonce_field( 'novel_proofreading_books_action', 'novel_proofreading_books_nonce' ); ?>
                                    <input type="hidden" name="novel_proofreading_action" value="update_relic" />
                                    <input type="hidden" name="relic_id" value="<?php echo esc_attr($item['id']); ?>" />
                                    <button type="submit" class="button button-primary"><?php _e( 'Save', 'novel-proofreading' ); ?></button>
                                </form>
                            </td>
                            <td>
                                <form method="post">
                                    <?php wp_nonce_field( 'novel_proofreading_books_action', 'novel_proofreading_books_nonce' ); ?>
                                    <input type="hidden" name="novel_proofreading_action" value="remove_relic" />
                                    <input type="hidden" name="relic_id" value="<?php echo esc_attr($item['id']); ?>" />
                                    <button type="button" class="button remove-item" onclick="confirm_delete(this)">-</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3>11.2&nbsp;<?php _e( 'Add Relic', 'novel-proofreading' ); ?></h3>
            <form method="post">
                <?php wp_nonce_field( 'novel_proofreading_books_action', 'novel_proofreading_books_nonce' ); ?>
                <input type="hidden" name="novel_proofreading_action" value="add_relic" />

                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="novel-proofreading-relic-book-id"><?php _e( 'Book', 'novel-proofreading' ); ?></label>
                            </th>
                            <td>
                                <select id="novel-proofreading-relic-book-id" name="book_id" required>
                                    <option value=""><?php _e( 'Select book', 'novel-proofreading' ); ?></option>
                                    <?php foreach ( $items as $item ) : ?>
                                        <option value="<?php echo esc_attr($item['id']); ?>">
                                            <?php echo esc_html($item['title'] . ' - ' . $item['author'] . ' (' . $item['year'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="novel-proofreading-relic-name"><?php _e( 'Relic', 'novel-proofreading' ); ?></label>
                            </th>
                            <td>
                                <input type="text" class="regular-text" id="novel-proofreading-relic-name" name="relic_name" required />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="novel-proofreading-relic-description"><?php _e( 'Description', 'novel-proofreading' ); ?></label>
                            </th>
                            <td>
                                <textarea class="large-text" id="novel-proofreading-relic-description" name="description" rows="3"></textarea>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <?php _e( 'Inaccurate', 'novel-proofreading' ); ?>
                            </th>
                            <td>
                                <label for="novel-proofreading-relic-is-inaccurate">
                                    <input type="checkbox" id="novel-proofreading-relic-is-inaccurate" name="is_inaccurate" value="Y" />
                                    <?php _e( 'Yes', 'novel-proofreading' ); ?>
                                </label>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <button type="submit" class="button button-primary" <?php disabled(empty($items)); ?>>
                    + <?php _e( 'Add Relic', 'novel-proofreading' ); ?>
                </button>
            </form>
        </div>
    </div>

    <?php
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
        array( 'wp-i18n', 'fullcalendar', 'jquery', 'sweetalert2' ),
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
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'storylineSuggestionsNonce' => wp_create_nonce(
                'novel_proofreading_storyline_suggestions'
            ),
            'personAliasesNonce' => wp_create_nonce(
                'novel_proofreading_person_aliases'
            ),
            'personProfessionsNonce' => wp_create_nonce(
                'novel_proofreading_person_professions'
            ),
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
