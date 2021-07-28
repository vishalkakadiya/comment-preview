<?php
/**
 * Plugin Name:     WP Comment Preview
 * Plugin URI:      https://github.com/vishalkakadiya
 * Description:     Allow to display comment preview.
 * Author:          Vishal Kakadiya
 * Author URI:      https://github.com/vishalkakadiya
 * Text Domain:     comment-preview
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         WP_Comment_Preview
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

define( 'WP_COMMENT_PREVIEW_URL', plugin_dir_url( __FILE__ ) );
define( 'WP_COMMENT_PREVIEW_PATH', plugin_dir_path( __FILE__ ) );

require_once __DIR__ . '/inc/classes/class-comment-preview.php';

new \CommentPreview\Inc\Comment_Preview();
