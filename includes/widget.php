<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class NovelProofreadingPluginWidget extends WP_Widget {

    public function __construct() {

        parent::__construct(
            'novel_proofreading_plugin_widget',
            __( 'Novel Proofreading', 'novel-proofreading' ),
            [
                'description' => __(
                    'Novel Proofreading plugin widget',
                    'novel-proofreading'
                ),
            ]
        );
    }

    public function widget( $args, $instance ) {

        echo $args['before_widget'];

        echo novel_proofreading_plugin_render();

        echo $args['after_widget'];
    }

    public function form( $instance ) {

        echo '<p>' .
            esc_html__(
                'Ez a widget a shortcode renderelést használja.',
                'novel-proofreading'
            ) .
        '</p>';
    }
}

add_action(
    'widgets_init',
    function () {
        register_widget( 'NovelProofreadingPluginWidget' );
    }
);