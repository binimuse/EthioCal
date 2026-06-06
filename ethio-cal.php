<?php
/**
 * Plugin Name: EthioCal – Ethiopian Calendar
 * Plugin URI:  https://github.com/binimusema/ethio-cal
 * Description: A native Gutenberg block, shortcode, REST API, and developer API for the Ethiopian (Ge'ez) calendar.
 * Version:     1.0.0
 * Author:      Bini Musema
 * Author URI:  https://github.com/binimusema
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ethio-cal
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/vendor/autoload.php';

( new EthioCal\Plugin() )->register();
