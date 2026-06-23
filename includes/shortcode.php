<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Common render function
 */
function novel_proofreading_plugin_render( $atts = [] ) {
    $atts = shortcode_atts(
        [
            'view' => '',
            'book_id' => 0,
            'details' => 'closed'
        ],
        $atts,
        'novel_proofreading'
    );
    $atts['view'] = strtolower((string) $atts['view']);
    $atts['details'] = strtolower((string) $atts['details']);

    if ($atts['view'] === 'storyline_chains') {
        return novel_proofreading_plugin_render_storyline_chains($atts);
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
            r.id AS relic_id,
            r.relic_name

        FROM
            {$table_mapping} cm
        LEFT JOIN
            (
                SELECT DISTINCT
                    m2.storyline_id,
                    p.id,
                    p.name,
                    p.alias,
                    m3.person_related_subtype
                FROM {$table_mapping} m
                JOIN {$table_mapping} m2
                    ON m.chapter = m2.chapter and m.page = m2.page
                JOIN {$table_persons} p
                    ON m.person_id = p.id
                LEFT JOIN {$table_mapping} m3
                    ON m.person_id = m3.person_id AND m.chapter = m3.chapter
                WHERE m.person_id is not null and m2.storyline_id is not null AND m.type = 'PERSON'
                    and (m3.person_related_subtype is not null or not exists (
                        select * 
                        from {$table_mapping} m4 
                        where m4.person_related_subtype is not null and m4.person_id = m3.person_id
                    ))
            ) p2 ON p2.storyline_id = cm.storyline_id
        LEFT JOIN
            (
                SELECT DISTINCT
                    m2.storyline_id,
                    l.id,
                    l.name,
                    l.alias
                FROM {$table_mapping} m
                JOIN {$table_mapping} m2
                    ON m.chapter = m2.chapter and m.page = m2.page
                JOIN {$table_locations} l
                    ON m.location_id = l.id
                LEFT JOIN {$table_mapping} m3
                    ON m.location_id = m3.location_id AND m.chapter = m3.chapter
                WHERE m.location_id is not null and m2.storyline_id is not null AND m.type = 'LOCATION'
            ) l2 ON l2.storyline_id = cm.storyline_id
        LEFT JOIN
            (
                SELECT DISTINCT
                    m2.storyline_id,
                    d.id,
                    d.name
                FROM {$table_mapping} m
                JOIN {$table_mapping} m2
                    ON m.chapter = m2.chapter and m.page = m2.page
                JOIN {$table_datetimes} d
                    ON m.time_id = d.id
                LEFT JOIN {$table_mapping} m3
                    ON m.time_id = m3.time_id AND m.chapter = m3.chapter
                WHERE m.time_id is not null and m2.storyline_id is not null AND m.type = 'TIME'
            ) d2 ON d2.storyline_id = cm.storyline_id
        LEFT JOIN
            {$table_relics} r ON r.id = cm.relics_id

        WHERE
            {$where}

        ORDER BY
            cm.id,
            p2.name,
            p2.alias,
            p2.person_related_subtype,
            l2.name,
            d2.name,
            r.relic_name
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
