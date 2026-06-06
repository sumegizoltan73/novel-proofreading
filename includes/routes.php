<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action(
    'rest_api_init',
    'novel_proofreading_register_routes'
);

function novel_proofreading_register_routes() {

    /*
    register_rest_route(
        'novel-proofreading/v1',
        '/generate-slots',
        [
            'methods'  => 'POST',

            'callback' => 'novel_proofreading_generate_slots',

            'permission_callback' => function () {

                return current_user_can(
                    'manage_options'
                );
            }
        ]
    );
    */
}
