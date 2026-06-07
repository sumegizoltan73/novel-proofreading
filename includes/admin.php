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
    }

    $items = novel_proofreading_get_books();
    $series_items = novel_proofreading_get_series();

    ?>

    <div class="wrap">

        <h1>Novel Proofreading</h1>

        <?php if ($admin_notice != "") : ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo esc_html($admin_notice); ?></p>
            </div>
        <?php endif; ?>

        
        <h2><?php _e( '1. Books', 'novel-proofreading' ); ?></h2>
        <button class="button" onclick="show_hide('.books-wrap')"><?php _e( 'Show / Hide Books', 'novel-proofreading' ); ?></button>
        <div class="books-wrap hidden">
            <h3><?php _e( '1.1 List of Books', 'novel-proofreading' ); ?></h3>
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
                                    <button type="submit" class="button remove-item">-</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3><?php _e( '1.2 Add Book', 'novel-proofreading' ); ?></h3>
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

        <h2><?php _e( '2. Series', 'novel-proofreading' ); ?></h2>
        <button class="button" onclick="show_hide('.series-wrap')"><?php _e( 'Show / Hide Series', 'novel-proofreading' ); ?></button>
        <div class="series-wrap hidden">
            <h3><?php _e( '2.1 List of Series', 'novel-proofreading' ); ?></h3>
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
                                    <button type="submit" class="button remove-item">-</button>
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
                                                        <button type="submit" class="button remove-item">-</button>
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

            <h3><?php _e( '2.2 Add Series', 'novel-proofreading' ); ?></h3>
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

            <h3><?php _e( '2.3 Add Book to Series', 'novel-proofreading' ); ?></h3>
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
