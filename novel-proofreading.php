<?php
/*
 * Plugin Name:       Novel Proofreading and Linker Plugin
 * Plugin URI:        https://github.com/sumegizoltan73/novel-proofreading
 * Description:       Novel proofreading and linking - WordPress plugin.
 * Version:           1.1.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Zoltan Peter Sumegi & ChatGPT
 * Author URI:        https://www.sumegizoltanpeter.hu/
 * License:           MIT
 * License URI:       https://mit-license.org
 * Update URI:        https://github.com/sumegizoltan73/novel-proofreading
 * Text Domain:       novel-proofreading
 * Domain Path:       languages
 * Requires Plugins:  
 */


add_action( 'plugins_loaded', 'novel_proofreading_plugin_load_textdomain' );

require_once __DIR__ . '/includes/shortcode.php';
require_once __DIR__ . '/includes/widget.php';
require_once __DIR__ . '/includes/routes.php';
require_once plugin_dir_path(__FILE__) . 'includes/db.php';
require_once __DIR__ . '/includes/admin.php';


/**
 * Activation hook.
 */
register_activation_hook(
    __FILE__,
    'novel_proofreading_install'
);
add_action(
    'plugins_loaded',
    'novel_proofreading_update_db_check'
);

/**
 * Register our wporg_settings_init to the admin_init action hook.
 */
add_action( 'admin_init', 'novel_proofreading_plugin_settings_init' );

/**
 * custom option and settings
 */
function novel_proofreading_plugin_settings_init() {
	

}

/**
 * languages
 */
function novel_proofreading_plugin_load_textdomain() {
    load_plugin_textdomain(
        'novel-proofreading',
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages'
    );
}
