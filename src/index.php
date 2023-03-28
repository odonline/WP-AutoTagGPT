<?php
/**
 * Plugin Name: AutoTagWP
 * Plugin URI: https://github.com/odonline/wp-autotag-ai/
 * Description: Wordpress plugin that add AI logic for tag posts using OpenAI.
 * Version: 1.0.0
 * Author: Damián Ares<dares@gtsur.com>
 * Author URI: https://github.com/odonline/
 * License: BSD-3-Clause
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define constants.
define('AutoTagWP_DIR', plugin_dir_path(__FILE__));

// Load plugin class.
require_once AutoTagWP_DIR . 'includes/AutoTagWP.php';

// Initialize plugin.
add_action('plugins_loaded', function () {
    new AutoTagWP();
});
