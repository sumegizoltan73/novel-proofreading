<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action(
    'wp_ajax_novel_proofreading_accept_storyline_agreement',
    'novel_proofreading_plugin_ajax_accept_storyline_agreement'
);

/**
 * Common render function
 */
function novel_proofreading_plugin_render( $atts = [] ) {
    $atts = shortcode_atts(
        [
            'view' => '',
            'book_id' => 0,
            'details' => 'closed',
            'suggestions' => 'on'
        ],
        $atts,
        'novel_proofreading'
    );
    $atts['view'] = strtolower((string) $atts['view']);
    $atts['details'] = strtolower((string) $atts['details']);
    $atts['suggestions'] = strtolower((string) $atts['suggestions']);
    if ($atts['view'] === 'storyline_chains') {
        return novel_proofreading_plugin_render_storyline_chains($atts);
    }
    else if ($atts['view'] === 'storyline_chains_and_events') {
        return novel_proofreading_plugin_render_storyline_chains_and_events($atts);
    }
    else if ($atts['view'] === 'storyline_agreements') {
        return novel_proofreading_plugin_render_storyline_agreements($atts);
    }

    novel_proofreading_plugin_enqueue_assets();

    ob_start();
    ?>

    <div id="novel-proofreading-calendar"></div>

    <?php

    return ob_get_clean();
}

function novel_proofreading_plugin_enqueue_frontend_assets() {

    wp_enqueue_style(
        'novel-proofreading-style',
        plugin_dir_url(__FILE__) . '../assets/css/style.css',
        [],
        filemtime(
            plugin_dir_path(__FILE__) .
            '../assets/css/style.css'
        )
    );
}

function novel_proofreading_plugin_render_storyline_agreements($atts) {

    novel_proofreading_plugin_enqueue_frontend_assets();

    $book_id = intval($atts['book_id']);
    $items = novel_proofreading_plugin_get_storyline_agreements($book_id);
    $can_accept = current_user_can('edit_posts');
    $instance_id = 'novel-proofreading-storyline-agreements-' . uniqid();

    ob_start();
    ?>

    <div
        id="<?php echo esc_attr($instance_id); ?>"
        class="novel-proofreading-list novel-proofreading-storyline-agreement-list"
        data-ajax-url="<?php echo esc_url(admin_url('admin-ajax.php')); ?>"
        data-nonce="<?php echo esc_attr(wp_create_nonce('novel_proofreading_accept_storyline_agreement')); ?>"
    >
        <?php if (empty($items)) : ?>
            <p class="novel-proofreading-empty"><?php _e( 'No storyline suggestions or agreements found.', 'novel-proofreading' ); ?></p>
        <?php endif; ?>

        <?php foreach ($items as $item) : ?>
            <article
                class="novel-proofreading-list-item novel-proofreading-storyline-agreement-item"
                data-agreement-id="<?php echo esc_attr($item['id']); ?>"
            >
                <div class="novel-proofreading-storyline-agreement-header">
                    <h3 class="novel-proofreading-list-title">
                        <?php echo esc_html($item['storyline_name']); ?>
                        <span class="novel-proofreading-list-subtitle"><?php echo esc_html($item['book_title']); ?></span>
                    </h3>
                    <span class="novel-proofreading-badge <?php echo esc_attr($item['type'] === 'AGREEMENT' ? 'is-ok' : 'is-info'); ?>">
                        <?php echo esc_html($item['type'] === 'AGREEMENT' ? __('Agreement', 'novel-proofreading') : __('Suggestion', 'novel-proofreading')); ?>
                    </span>
                </div>

                <?php if ($item['description'] !== '') : ?>
                    <p class="novel-proofreading-storyline-agreement-description">
                        <?php echo esc_html($item['description']); ?>
                    </p>
                <?php endif; ?>

                <div class="novel-proofreading-chain-stats">
                    <?php if ($item['chapter'] !== '') : ?>
                        <span class="novel-proofreading-badge"><?php echo esc_html(sprintf(__('Chapter: %s', 'novel-proofreading'), $item['chapter'])); ?></span>
                    <?php endif; ?>
                    <?php if ($item['page'] !== '') : ?>
                        <span class="novel-proofreading-badge"><?php echo esc_html(sprintf(__('Page: %s', 'novel-proofreading'), $item['page'])); ?></span>
                    <?php endif; ?>
                    <?php if ($item['event_name'] !== '') : ?>
                        <span class="novel-proofreading-badge"><?php echo esc_html(sprintf(__('Event: %s', 'novel-proofreading'), $item['event_name'])); ?></span>
                    <?php endif; ?>
                </div>

                <?php if ($can_accept) : ?>
                    <div class="novel-proofreading-storyline-agreement-actions">
                        <button type="button" class="novel-proofreading-accept-agreement">
                            <?php _e( 'Accept modification', 'novel-proofreading' ); ?>
                        </button>
                        <span class="novel-proofreading-agreement-status" aria-live="polite"></span>
                    </div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>

    <?php if ($can_accept && ! empty($items)) : ?>
        <script>
        (function () {
            var root = document.getElementById(<?php echo wp_json_encode($instance_id); ?>);
            if (!root) {
                return;
            }

            root.addEventListener('click', function (event) {
                var button = event.target.closest('.novel-proofreading-accept-agreement');
                if (!button) {
                    return;
                }

                var item = button.closest('.novel-proofreading-storyline-agreement-item');
                var status = item ? item.querySelector('.novel-proofreading-agreement-status') : null;
                if (!item) {
                    return;
                }

                button.disabled = true;
                if (status) {
                    status.textContent = <?php echo wp_json_encode(__('Saving...', 'novel-proofreading')); ?>;
                }

                var body = new URLSearchParams();
                body.append('action', 'novel_proofreading_accept_storyline_agreement');
                body.append('nonce', root.getAttribute('data-nonce'));
                body.append('mapping_id', item.getAttribute('data-agreement-id'));

                fetch(root.getAttribute('data-ajax-url'), {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                    },
                    body: body.toString()
                })
                    .then(function (response) {
                        return response.json();
                    })
                    .then(function (response) {
                        if (!response || !response.success) {
                            throw new Error(response && response.data && response.data.message ? response.data.message : <?php echo wp_json_encode(__('Modification could not be accepted.', 'novel-proofreading')); ?>);
                        }

                        item.classList.add('is-accepted');
                        if (status) {
                            status.textContent = response.data.message;
                        }
                        button.remove();
                    })
                    .catch(function (error) {
                        button.disabled = false;
                        if (status) {
                            status.textContent = error.message;
                        }
                    });
            });
        }());
        </script>
    <?php endif; ?>

    <?php

    return ob_get_clean();
}

function novel_proofreading_plugin_get_storyline_agreements($book_id = 0) {
    global $wpdb;

    $table_mapping = $wpdb->prefix . 'novel_proofreading_common_mapping';
    $table_books = $wpdb->prefix . 'novel_proofreading_books';
    $table_storylines = $wpdb->prefix . 'novel_proofreading_storylines';
    $table_events = $wpdb->prefix . 'novel_proofreading_events';

    $where = [
        "cm.type IN ('AGREEMENT', 'SUGGESTION')",
        'cm.storyline_id IS NOT NULL',
        "cm.is_solved = 'N'"
    ];
    $params = [];

    if ($book_id > 0) {
        $where[] = 'cm.book_id = %d';
        $params[] = $book_id;
    }

    $sql = "
        SELECT
            cm.id,
            cm.book_id,
            cm.type,
            cm.description,
            cm.page,
            cm.chapter,
            cm.storyline_id,
            cm.event_id,
            b.title AS book_title,
            st.storyline_name,
            e.event_name

        FROM
            {$table_mapping} cm
        LEFT JOIN
            {$table_books} b ON b.id = cm.book_id
        LEFT JOIN
            {$table_storylines} st ON st.id = cm.storyline_id
        LEFT JOIN
            {$table_events} e ON e.id = cm.event_id

        WHERE
            " . implode(' AND ', $where) . "

        ORDER BY
            cm.book_id,
            st.storyline_name,
            cm.chapter,
            cm.page,
            cm.id
    ";

    $rows = empty($params)
        ? $wpdb->get_results($sql)
        : $wpdb->get_results($wpdb->prepare($sql, $params));

    $items = [];
    foreach ($rows as $row) {
        $items[] = [
            'id' => intval($row->id),
            'book_id' => intval($row->book_id),
            'type' => $row->type,
            'description' => trim((string) $row->description),
            'page' => trim((string) $row->page),
            'chapter' => trim((string) $row->chapter),
            'storyline_id' => intval($row->storyline_id),
            'event_id' => intval($row->event_id),
            'book_title' => isset($row->book_title) ? $row->book_title : '',
            'storyline_name' => isset($row->storyline_name) ? $row->storyline_name : '',
            'event_name' => isset($row->event_name) ? $row->event_name : ''
        ];
    }

    return $items;
}

function novel_proofreading_plugin_ajax_accept_storyline_agreement() {

    if (! current_user_can('edit_posts')) {
        wp_send_json_error(
            [
                'message' => __( 'You are not allowed to accept modifications.', 'novel-proofreading' )
            ],
            403
        );
    }

    check_ajax_referer(
        'novel_proofreading_accept_storyline_agreement',
        'nonce'
    );

    $mapping_id = intval($_POST['mapping_id'] ?? 0);
    if ($mapping_id <= 0) {
        wp_send_json_error(
            [
                'message' => __( 'Modification is required.', 'novel-proofreading' )
            ],
            400
        );
    }

    global $wpdb;

    $table_mapping = $wpdb->prefix . 'novel_proofreading_common_mapping';
    $now = current_time('mysql', true);
    $mapping = $wpdb->get_row(
        $wpdb->prepare(
            "
            SELECT
                id

            FROM
                {$table_mapping}

            WHERE
                    id = %d
                AND type IN ('AGREEMENT', 'SUGGESTION')
                AND storyline_id IS NOT NULL
            ",
            $mapping_id
        )
    );

    if (! $mapping) {
        wp_send_json_error(
            [
                'message' => __( 'Modification does not exist.', 'novel-proofreading' )
            ],
            404
        );
    }

    $result = $wpdb->update(
        $table_mapping,
        [
            'to_be_solved' => 'N',
            'is_solved' => 'Y',
            'solved_at' => $now,
            'solved_type' => 'ACCEPTED',
            'updated_at' => $now,
            'updated_by' => get_current_user_id()
        ],
        [
            'id' => $mapping_id,
            'is_solved' => 'N'
        ],
        [
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%d'
        ],
        [
            '%d',
            '%s'
        ]
    );

    if ($result === false) {
        wp_send_json_error(
            [
                'message' => __( 'Modification could not be accepted.', 'novel-proofreading' )
            ],
            500
        );
    }

    if (intval($result) === 0) {
        wp_send_json_error(
            [
                'message' => __( 'Modification was already accepted or does not exist.', 'novel-proofreading' )
            ],
            409
        );
    }

    wp_send_json_success(
        [
            'message' => __( 'Modification accepted.', 'novel-proofreading' )
        ]
    );
}

function novel_proofreading_plugin_render_storyline_chains($atts) {

    if (! function_exists('novel_proofreading_get_storyline_chains')) {
        return '';
    }

    novel_proofreading_plugin_enqueue_frontend_assets();

    $book_id = intval($atts['book_id']);
    $details_open = $atts['details'] === 'open';
    $chains = novel_proofreading_get_storyline_chains($book_id);
    $instance_id = 'novel-proofreading-storyline-list-' . uniqid();

    ob_start();
    ?>

    <div id="<?php echo esc_attr($instance_id); ?>" class="novel-proofreading-list novel-proofreading-storyline-chain-list">
        <div class="novel-proofreading-list-toolbar">
            <button
                type="button"
                class="novel-proofreading-details-toggle"
                aria-pressed="<?php echo esc_attr($details_open ? 'true' : 'false'); ?>"
            >
                <?php echo esc_html($details_open ? __('Hide details', 'novel-proofreading') : __('Details', 'novel-proofreading')); ?>
            </button>
        </div>

        <?php if (empty($chains)) : ?>
            <p class="novel-proofreading-empty"><?php _e( 'No storyline chains found.', 'novel-proofreading' ); ?></p>
        <?php endif; ?>

        <?php foreach ($chains as $chain) : ?>
            <?php
            $stats = $chain['stats'];
            $detail_groups = novel_proofreading_plugin_get_storyline_detail_groups($chain);
            $anchor_id = novel_proofreading_plugin_get_storyline_anchor_id($instance_id, $chain['id']);
            ?>
            <article class="novel-proofreading-list-item novel-proofreading-storyline-chain-item">
                <h3 id="<?php echo esc_attr($anchor_id); ?>" class="novel-proofreading-list-title">
                    <?php echo esc_html($chain['storyline_name']); ?>
                    <span class="novel-proofreading-list-subtitle"><?php echo esc_html($chain['book_title']); ?></span>
                </h3>

                <div class="novel-proofreading-chain-stats">
                    <span class="novel-proofreading-badge"><?php echo esc_html(sprintf(__('Events: %d', 'novel-proofreading'), $stats['event_count'])); ?></span>
                    <span class="novel-proofreading-badge <?php echo $stats['has_opening'] ? 'is-ok' : 'is-warning'; ?>"><?php echo esc_html($stats['has_opening'] ? __('Has opening', 'novel-proofreading') : __('Missing opening', 'novel-proofreading')); ?></span>
                    <span class="novel-proofreading-badge <?php echo $stats['has_closing'] ? 'is-ok' : 'is-warning'; ?>"><?php echo esc_html($stats['has_closing'] ? __('Closed', 'novel-proofreading') : __('Missing closing', 'novel-proofreading')); ?></span>
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

                <div class="novel-proofreading-storyline-route">
                    <?php if (! empty($chain['previous_storylines'])) : ?>
                        <div class="novel-proofreading-storyline-route-previous">
                            <?php foreach ($chain['previous_storylines'] as $previous_storyline) : ?>
                                <a href="#<?php echo esc_attr(novel_proofreading_plugin_get_storyline_anchor_id($instance_id, $previous_storyline['id'])); ?>">
                                    <?php echo esc_html($previous_storyline['storyline_name']); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="novel-proofreading-storyline-route-current">
                        <?php _e( '|-> This storyline', 'novel-proofreading' ); ?>
                    </div>

                    <?php if (! empty($chain['related_storylines'])) : ?>
                        <div class="novel-proofreading-storyline-branches">
                            <?php foreach ($chain['related_storylines'] as $related_storyline) : ?>
                                <a href="#<?php echo esc_attr(novel_proofreading_plugin_get_storyline_anchor_id($instance_id, $related_storyline['id'])); ?>">
                                    <?php echo esc_html($related_storyline['storyline_name']); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="novel-proofreading-detail-bands" <?php echo $details_open ? '' : 'hidden'; ?>>
                    <?php foreach ($detail_groups as $group) : ?>
                        <?php if (empty($group['items'])) : ?>
                            <?php continue; ?>
                        <?php endif; ?>
                        <div class="novel-proofreading-detail-band">
                            <span class="novel-proofreading-detail-label"><?php echo esc_html($group['label']); ?>:</span>
                            <span class="novel-proofreading-detail-badges">
                                <?php foreach ($group['items'] as $item) : ?>
                                    <span class="novel-proofreading-badge <?php echo esc_attr($group['class']); ?>"><?php echo esc_html($item['label']); ?></span>
                                <?php endforeach; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <script>
    (function () {
        var root = document.getElementById(<?php echo wp_json_encode($instance_id); ?>);
        if (!root) {
            return;
        }

        var toggle = root.querySelector('.novel-proofreading-details-toggle');
        if (!toggle) {
            return;
        }

        toggle.addEventListener('click', function () {
            var isOpen = toggle.getAttribute('aria-pressed') === 'true';
            var nextOpen = !isOpen;
            var bands = root.querySelectorAll('.novel-proofreading-detail-bands');

            for (var i = 0; i < bands.length; i++) {
                bands[i].hidden = !nextOpen;
            }

            toggle.setAttribute('aria-pressed', nextOpen ? 'true' : 'false');
            toggle.textContent = nextOpen
                ? <?php echo wp_json_encode(__('Hide details', 'novel-proofreading')); ?>
                : <?php echo wp_json_encode(__('Details', 'novel-proofreading')); ?>;
        });
    }());
    </script>

    <?php

    return ob_get_clean();
}

function novel_proofreading_plugin_render_storyline_chains_and_events($atts) {

    if (! function_exists('novel_proofreading_get_storyline_chains')) {
        return '';
    }

    novel_proofreading_plugin_enqueue_frontend_assets();

    $book_id = intval($atts['book_id']);
    $details_open = $atts['details'] === 'open';
    $chains = novel_proofreading_get_storyline_chains($book_id);
    $suggestions = [];

    if ($atts['suggestions'] === 'on' && ! empty($chains)) {
        global $wpdb;

        $storyline_ids = array_values(array_filter(array_map('intval', array_column($chains, 'id'))));
        if (! empty($storyline_ids)) {
            $placeholders = implode(', ', array_fill(0, count($storyline_ids), '%d'));
            $table_mapping = $wpdb->prefix . 'novel_proofreading_common_mapping';
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT storyline_id, type, description
                    FROM {$table_mapping}
                    WHERE storyline_id IN ({$placeholders})
                        AND type IN ('AGREEMENT', 'SUGGESTION')
                    ORDER BY id",
                    $storyline_ids
                ),
                ARRAY_A
            );

            foreach ($rows as $row) {
                $suggestions[intval($row['storyline_id'])][] = $row;
            }
        }
    }
    $instance_id = 'novel-proofreading-storyline-list-' . uniqid();

    ob_start();
    ?>

    <div id="<?php echo esc_attr($instance_id); ?>" class="novel-proofreading-list novel-proofreading-storyline-chain-list">
        <div class="novel-proofreading-list-toolbar">
            <button
                type="button"
                class="novel-proofreading-details-toggle"
                aria-pressed="<?php echo esc_attr($details_open ? 'true' : 'false'); ?>"
            >
                <?php echo esc_html($details_open ? __('Hide details', 'novel-proofreading') : __('Details', 'novel-proofreading')); ?>
            </button>
        </div>

        <?php if (empty($chains)) : ?>
            <p class="novel-proofreading-empty"><?php _e( 'No storyline chains found.', 'novel-proofreading' ); ?></p>
        <?php endif; ?>

        <?php foreach ($chains as $chain) : ?>
            <?php
            $stats = $chain['stats'];
            $detail_groups = novel_proofreading_plugin_get_storyline_detail_groups($chain);
            $anchor_id = novel_proofreading_plugin_get_storyline_anchor_id($instance_id, $chain['id']);
            $chain_suggestions = $suggestions[intval($chain['id'])] ?? [];
            ?>
            <article class="novel-proofreading-list-item novel-proofreading-storyline-chain-item">
                <h3 id="<?php echo esc_attr($anchor_id); ?>" class="novel-proofreading-list-title">
                    <?php echo esc_html($chain['storyline_name']); ?>
                    <span class="novel-proofreading-list-subtitle"><?php echo esc_html($chain['book_title']); ?></span>
                </h3>
                <?php if (trim((string) $chain['description']) !== '') : ?>
                    <div class="novel-proofreading-storyline-description novel-proofreading-detail-bands" <?php echo $details_open ? '' : 'hidden'; ?>>
                        <?php echo esc_html($chain['description']); ?>
                    </div>
                <?php endif; ?>

                <div class="novel-proofreading-chain-stats">
                    <span class="novel-proofreading-badge"><?php echo esc_html(sprintf(__('Events: %d', 'novel-proofreading'), $stats['event_count'])); ?></span>
                    <span class="novel-proofreading-badge <?php echo $stats['has_opening'] ? 'is-ok' : 'is-warning'; ?>"><?php echo esc_html($stats['has_opening'] ? __('Has opening', 'novel-proofreading') : __('Missing opening', 'novel-proofreading')); ?></span>
                    <span class="novel-proofreading-badge <?php echo $stats['has_closing'] ? 'is-ok' : 'is-warning'; ?>"><?php echo esc_html($stats['has_closing'] ? __('Closed', 'novel-proofreading') : __('Missing closing', 'novel-proofreading')); ?></span>
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

                <div class="novel-proofreading-storyline-route">
                    <?php if (! empty($chain['previous_storylines'])) : ?>
                        <div class="novel-proofreading-storyline-route-previous">
                            <?php foreach ($chain['previous_storylines'] as $previous_storyline) : ?>
                                <a href="#<?php echo esc_attr(novel_proofreading_plugin_get_storyline_anchor_id($instance_id, $previous_storyline['id'])); ?>">
                                    <?php echo esc_html($previous_storyline['storyline_name']); ?>
                                </a>
                                <?php if (trim((string) $previous_storyline['description']) !== '') : ?>
                                    <div class="novel-proofreading-storyline-description novel-proofreading-storyline-branch-description novel-proofreading-detail-bands" <?php echo $details_open ? '' : 'hidden'; ?>>
                                        <?php echo esc_html($previous_storyline['description']); ?>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="novel-proofreading-storyline-route-current">
                        <?php _e( '|-> This storyline', 'novel-proofreading' ); ?>
                    </div>

                    <?php if (! empty($chain['related_storylines'])) : ?>
                        <div class="novel-proofreading-storyline-branches">
                            <?php foreach ($chain['related_storylines'] as $related_storyline) : ?>
                                <a href="#<?php echo esc_attr(novel_proofreading_plugin_get_storyline_anchor_id($instance_id, $related_storyline['id'])); ?>">
                                    <?php echo esc_html($related_storyline['storyline_name']); ?>
                                </a>
                                <?php if (trim((string) $related_storyline['description']) !== '') : ?>
                                    <div class="novel-proofreading-storyline-description novel-proofreading-storyline-branch-description novel-proofreading-detail-bands" <?php echo $details_open ? '' : 'hidden'; ?>>
                                        <?php echo esc_html($related_storyline['description']); ?>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="novel-proofreading-detail-bands" <?php echo $details_open ? '' : 'hidden'; ?>>
                    <?php foreach ($detail_groups as $group) : ?>
                        <?php if (empty($group['items'])) : ?>
                            <?php continue; ?>
                        <?php endif; ?>
                        <div class="novel-proofreading-detail-band">
                            <span class="novel-proofreading-detail-label"><?php echo esc_html($group['label']); ?>:</span>
                            <span class="novel-proofreading-detail-badges">
                                <?php foreach ($group['items'] as $item) : ?>
                                    <span class="novel-proofreading-badge <?php echo esc_attr($group['class']); ?>"><?php echo esc_html($item['label']); ?></span>
                                <?php endforeach; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (! empty($chain_suggestions)) : ?>
                    <div class="novel-proofreading-storyline-suggestions">
                        <?php foreach ($chain_suggestions as $suggestion) : ?>
                            <?php $suggestion_type = strtolower($suggestion['type']); ?>
                            <div class="novel-proofreading-storyline-suggestion is-<?php echo esc_attr($suggestion_type); ?>">
                                <span class="novel-proofreading-badge is-info">
                                    <?php echo esc_html($suggestion['type'] === 'AGREEMENT' ? __('Agreement', 'novel-proofreading') : __('Suggestion', 'novel-proofreading')); ?>
                                </span>
                                <?php echo esc_html($suggestion['description']); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($stats['event_count'] === 0) : ?>
                    <p class="notice notice-warning inline"><?php _e( 'This storyline has no linked events.', 'novel-proofreading' ); ?></p>
                <?php else : ?>
                    <div class="novel-proofreading-scroll-table">
                        <table class="widefat striped novel-proofreading-chain-table">
                        <thead>
                            <tr>
                                <th><?php _e( 'Sequence', 'novel-proofreading' ); ?></th>
                                <th><?php _e( 'Role', 'novel-proofreading' ); ?></th>
                                <th><?php _e( 'Event', 'novel-proofreading' ); ?></th>
                                <th><?php _e( 'Description', 'novel-proofreading' ); ?></th>
                                <th><?php _e( 'References', 'novel-proofreading' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $chain['events'] as $event ) : ?>
                                <tr>
                                    <td><?php echo esc_attr($event['sequence_no']); ?></td>
                                    <td>
                                        <?php echo esc_attr($event['chain_role']); ?>
                                    </td>
                                    <td><?php echo esc_html($event['event_name']); ?></td>
                                    <td><?php echo esc_html($event['description']); ?></td>
                                    <td><?php echo esc_html(implode(', ', array_unique($event['references']))); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>

    <script>
    (function () {
        var root = document.getElementById(<?php echo wp_json_encode($instance_id); ?>);
        if (!root) {
            return;
        }

        var toggle = root.querySelector('.novel-proofreading-details-toggle');
        if (!toggle) {
            return;
        }

        toggle.addEventListener('click', function () {
            var isOpen = toggle.getAttribute('aria-pressed') === 'true';
            var nextOpen = !isOpen;
            var bands = root.querySelectorAll('.novel-proofreading-detail-bands');

            for (var i = 0; i < bands.length; i++) {
                bands[i].hidden = !nextOpen;
            }

            toggle.setAttribute('aria-pressed', nextOpen ? 'true' : 'false');
            toggle.textContent = nextOpen
                ? <?php echo wp_json_encode(__('Hide details', 'novel-proofreading')); ?>
                : <?php echo wp_json_encode(__('Details', 'novel-proofreading')); ?>;
        });
    }());
    </script>

    <?php

    return ob_get_clean();
}

function novel_proofreading_plugin_get_storyline_anchor_id($instance_id, $storyline_id) {
    return $instance_id . '-chain-' . intval($storyline_id);
}

function novel_proofreading_plugin_get_storyline_detail_groups($chain) {
    $details = novel_proofreading_plugin_get_storyline_details($chain);

    return [
        [
            'label' => __( 'Persons', 'novel-proofreading' ),
            'class' => 'is-person',
            'items' => $details['persons']
        ],
        [
            'label' => __( 'Locations', 'novel-proofreading' ),
            'class' => 'is-location',
            'items' => $details['locations']
        ],
        [
            'label' => __( 'Datetimes', 'novel-proofreading' ),
            'class' => 'is-time',
            'items' => $details['times']
        ],
        [
            'label' => __( 'Relics', 'novel-proofreading' ),
            'class' => 'is-relic',
            'items' => $details['relics']
        ]
    ];
}

function novel_proofreading_plugin_get_storyline_details($chain) {
    global $wpdb;

    $empty = [
        'persons' => [],
        'locations' => [],
        'times' => [],
        'relics' => []
    ];

    $storyline_id = intval($chain['id']);
    if ($storyline_id <= 0) {
        return $empty;
    }

    $event_ids = [];
    foreach ($chain['events'] as $event) {
        $event_id = intval($event['id']);
        if ($event_id > 0) {
            $event_ids[] = $event_id;
        }
    }

    $table_mapping = $wpdb->prefix . 'novel_proofreading_common_mapping';
    $table_persons = $wpdb->prefix . 'novel_proofreading_persons';
    $table_events = $wpdb->prefix . 'novel_proofreading_events';
    $table_locations = $wpdb->prefix . 'novel_proofreading_locations';
    $table_datetimes = $wpdb->prefix . 'novel_proofreading_datetimes';
    $table_relics = $wpdb->prefix . 'novel_proofreading_relics';

    $where = 'cm.storyline_id = %d';
    $params = [$storyline_id];

    if (! empty($event_ids)) {
        $event_placeholders = implode(',', array_fill(0, count($event_ids), '%d'));
        $where = '(' . $where . " OR cm.event_id IN ({$event_placeholders}))";
        $params = array_merge($params, $event_ids);
    }

    $sql = "
        SELECT
            cm.id AS mapping_id,
            p2.id AS person_id,
            p2.name AS person_name,
            p2.alias AS person_alias,
            p2.person_related_subtype,
            l2.id AS location_id,
            l2.name AS location_name,
            l2.alias AS location_alias,
            d2.id AS time_id,
            d2.name AS time_name,
            r2.id AS relic_id,
            r2.relic_name

        FROM
            {$table_mapping} cm
        LEFT JOIN
            (
                SELECT distinct
                    e.storyline_id,
                    p.id,
                    p.name,
                    p.alias,
                    case when m3.person_related_subtype is null then m4.person_related_subtype else m3.person_related_subtype end as person_related_subtype
                FROM {$table_events} e
                JOIN {$table_mapping} m
                    ON m.storyline_id = e.storyline_id
                JOIN {$table_mapping} m4
                	ON m4.event_id = e.id
                JOIN {$table_mapping} m3
                	ON (m3.chapter = m.chapter or m3.chapter = m4.chapter) and 
                        exists (
                            select * 
                            from {$table_mapping} m2
                            where m2.type = 'PERSON'
                                and (
                                    ((cast(m.page as int) + 3 > cast(m2.page as int)
                                    and cast(m2.page as int) > cast(m.page as int) - 3))
                                    or 
                                    ((cast(m4.page as int) + 3 > cast(m2.page as int)
                                    and cast(m2.page as int) > cast(m4.page as int) - 3))
                                )
                        )
                JOIN {$table_persons} p
                    ON p.id = m3.person_id
            ) p2 ON p2.storyline_id = cm.storyline_id
        LEFT JOIN
            (
                SELECT distinct
                    e.storyline_id,
                    l.id,
                    l.name,
                    l.alias
                FROM {$table_events} e
                JOIN {$table_mapping} m
                    ON m.storyline_id = e.storyline_id
                JOIN {$table_mapping} m4
                	ON m4.event_id = e.id
                JOIN {$table_mapping} m3
                	ON (m3.chapter = m.chapter or m3.chapter = m4.chapter) and 
                        exists (
                            select * 
                            from {$table_mapping} m2
                            where m2.type = 'LOCATION'
                                and (
                                    ((cast(m.page as int) + 3 > cast(m2.page as int)
                                    and cast(m2.page as int) > cast(m.page as int) - 3))
                                    or 
                                    ((cast(m4.page as int) + 3 > cast(m2.page as int)
                                    and cast(m2.page as int) > cast(m4.page as int) - 3))
                                )
                        )
                JOIN {$table_locations} l
                    ON l.id = m3.location_id
            ) l2 ON l2.storyline_id = cm.storyline_id
        LEFT JOIN
            (
                SELECT distinct
                    e.storyline_id,
                    d.id,
                    d.name
                FROM {$table_events} e
                JOIN {$table_mapping} m
                    ON m.storyline_id = e.storyline_id
                JOIN {$table_mapping} m4
                	ON m4.event_id = e.id
                JOIN {$table_mapping} m3
                	ON (m3.chapter = m.chapter or m3.chapter = m4.chapter) and 
                        exists (
                            select * 
                            from {$table_mapping} m2
                            where m2.type = 'TIME'
                                and (
                                    ((cast(m.page as int) + 3 > cast(m2.page as int)
                                    and cast(m2.page as int) > cast(m.page as int) - 3))
                                    or 
                                    ((cast(m4.page as int) + 3 > cast(m2.page as int)
                                    and cast(m2.page as int) > cast(m4.page as int) - 3))
                                )
                        )
                JOIN {$table_datetimes} d
                    ON d.id = m3.time_id
            ) d2 ON d2.storyline_id = cm.storyline_id
        LEFT JOIN
            (
                SELECT distinct
                    e.storyline_id,
                    r.id,
                    r.relic_name
                FROM {$table_events} e
                JOIN {$table_mapping} m
                    ON m.storyline_id = e.storyline_id
                JOIN {$table_mapping} m4
                	ON m4.event_id = e.id
                JOIN {$table_mapping} m3
                	ON (m3.chapter = m.chapter or m3.chapter = m4.chapter) and 
                        exists (
                            select * 
                            from {$table_mapping} m2
                            where m2.type = 'RELIC'
                                and (
                                    ((cast(m.page as int) + 3 > cast(m2.page as int)
                                    and cast(m2.page as int) > cast(m.page as int) - 3))
                                    or 
                                    ((cast(m4.page as int) + 3 > cast(m2.page as int)
                                    and cast(m2.page as int) > cast(m4.page as int) - 3))
                                )
                        )
                JOIN {$table_relics} r
                    ON r.id = m3.relics_id
            ) r2 ON r2.storyline_id = cm.storyline_id

        WHERE
            {$where}

        ORDER BY
            cm.id,
            p2.name,
            p2.alias,
            p2.person_related_subtype,
            l2.name,
            d2.name,
            r2.relic_name
    ";

    $rows = $wpdb->get_results(
        $wpdb->prepare(
            $sql,
            $params
        )
    );

    foreach ($rows as $row) {
        novel_proofreading_plugin_add_storyline_detail_item(
            $empty['persons'],
            intval($row->person_id),
            trim($row->person_name . ' ' . $row->person_alias . ' ' . $row->person_related_subtype),
            intval($row->mapping_id)
        );
        novel_proofreading_plugin_add_storyline_detail_item(
            $empty['locations'],
            intval($row->location_id),
            trim($row->location_name . ' ' . $row->location_alias),
            intval($row->mapping_id)
        );
        novel_proofreading_plugin_add_storyline_detail_item(
            $empty['times'],
            intval($row->time_id),
            $row->time_name,
            intval($row->mapping_id)
        );
        novel_proofreading_plugin_add_storyline_detail_item(
            $empty['relics'],
            intval($row->relic_id),
            $row->relic_name,
            intval($row->mapping_id)
        );
    }

    foreach ($empty as $type => $items) {
        uasort(
            $items,
            function ($left, $right) {
                if ($left['order'] === $right['order']) {
                    return strnatcasecmp($left['label'], $right['label']);
                }

                return $left['order'] < $right['order'] ? -1 : 1;
            }
        );

        $empty[$type] = array_values($items);
    }

    return $empty;
}

function novel_proofreading_plugin_add_storyline_detail_item(&$items, $id, $label, $order) {

    $id = intval($id);
    $label = trim((string) $label);

    if ($id <= 0 || $label === '' || isset($items[$id])) {
        return;
    }

    $items[$id] = [
        'id' => $id,
        'label' => $label,
        'order' => $order
    ];
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
