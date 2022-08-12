<?php
/**
 * Custom Post Type Auto Menu
 *
 * @package Custom Post Type Auto Menu
 * @author  Bad Fun Productions
 * @license GPL-3.0
 * @link https://badfunproductions.com
 * @copyright 2021 Bad Fun Productions
 *
 * @wordpress-plugin
 * Plugin Name: Custom Post Type Auto Menu
 * Plugin URI: https://github.com/badfun/custom-post-type-auto-menu
 * Description: Automatically adds new custom post type posts to the chosen menu and parent item as a sub-menu item.
 * Version: 1.3.2
 * Author: Bad Fun Productions
 * Author URI: http://badfunproductions.com
 * Author Email: info@badfunproductions.com
 * Text Domain: bfp-cpt-auto-menu
 * Domain Path: /lang/
 */

namespace BFP\CptAutoMenu;

defined( 'ABSPATH' ) or die( 'You are not authorized to view this file.' );

spl_autoload_register( function ( $class ) {

$prefix = __NAMESPACE__;
$base_dir = __DIR__ . '/includes';

// make sure prefix is used
$len = strlen( $prefix );
if ( strncmp( $prefix, $class, $len ) !== 0 ) {
	return;
}

// get the class name
$relative_class = substr( $class, $len );

// replace the namespace prefix with the base directory, replace namespace
// separators with directory separators in the relative class name, append
// with .php
$file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

// if the file exists, require it
if ( file_exists( $file ) ) {
	require $file;
}
});

$cptAutoMenu = new Plugin();

add_action( 'wp_loaded', array( $cptAutoMenu, 'load' ) );
