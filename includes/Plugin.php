<?php
/**
 * Custom Post Type Auto Menu
 *
 * @package Custom Post Type Auto Menu
 * @author  Bad Fun Productions
 * @license GPL-3.0
 * @link https://badfunproductions.com
 * @copyright 2020 Bad Fun Productions
 */

namespace BFP\CptAutoMenu;

defined( 'ABSPATH' ) or die( 'You do not have the required permissions' );

if ( ! class_exists( 'Plugin' ) ) {

	class Plugin {

		/**
		 * Plugin version, used for cache-busting of style and script file references.
		 *
		 * @since   1.0.0
		 *
		 * @var     string
		 */
		protected $version = '1.3.0';

		/**
		 * Unique identifier for your plugin.
		 *
		 * Use this value (not the variable name) as the text domain when internationalizing strings of text. It should
		 * match the Text Domain file header in the main plugin file.
		 *
		 * @since    1.0.0
		 *
		 * @var      string
		 */
		protected $plugin_slug = 'bfp-cpt-auto-menu';

		/**
		 * Flag to track if plugin is loaded
		 *
		 * @var bool
		 */
		private $loaded;
		private $settings;
		private $nav_menu_handler;
		private $main_page;


		/**
		 * BFP_CptAutoMenu constructor.
		 */
		public function __construct() {
			$this->loaded           = false;
			$this->settings         = new Settings_Page();
			$this->nav_menu_handler = new Menu_Handler( $this );

			// create an admin page and prepare enqueue our scripts
			add_action( 'admin_menu', array( &$this, 'add_admin_menu_page' ) );
		}

		/**
		 * Load the plugin text domain for translation.
		 *
		 * @since    1.0.0
		 */
		public function load_plugin_textdomain() {

			$domain = $this->plugin_slug;
			$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

			load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
			load_plugin_textdomain( $domain, false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
		}

		/**
		 * Loads the plugin into WordPress
		 */
		public function load() {
			if ( $this->loaded ) {
				return;
			}

			$this->loaded = true;
		}

		/**
		 * Add admin menu page and load admin js action to load script only on our page
		 * @link  http://wordpress.stackexchange.com/questions/41207/how-do-i-enqueue-styles-scripts-on-certain-wp-admin-pages#76420
		 * dashicons requires version 3.8: @link http://melchoyce.github.io/dashicons/
		 *
		 * @since 1.1.0
		 *
		 */
		public function add_admin_menu_page() {
			$this->main_page = add_menu_page(
				__( 'CPT Auto Menu Settings', 'bfp-cpt-auto-menu' ), __( 'CPT Auto Menu', 'bfp-cpt-auto-menu' ), 'manage_options', 'cpt_auto_menu', array(
				&$this,
				'plugin_settings_page'
			), 'dashicons-screenoptions'
			);

			add_action( 'load-' . $this->main_page, array( $this, 'load_admin_js' ) );

			// also use this hook for page redirection
			add_action( 'load-' . $this->main_page, array( $this, 'admin_page_redirect' ) );

			// and for adding our stylesheet
			add_action( 'admin_print_styles-' . $this->main_page, array( $this, 'load_admin_css' ) );
		}

		/**
		 * Make sure we always land on a tab and not the base page.
		 * @TODO-bfp: this is a work-around due to how we are loading pages and tabs. Could be better
		 *
		 * @since   1.1.0
		 *
		 * @version 1.1.2
		 *
		 */
		public function admin_page_redirect() {
			// if our request is for the base page
			if ( isset( $_GET['page'] ) && $_GET['page'] == 'cpt_auto_menu' ) {

				// if neither tab has been requested
				if ( isset( $_GET['tab'] ) != 'select_cpt' && isset( $_GET['tab'] ) != 'select_menu' ) {
					// means we are on base page and can be redirected to first tab
					wp_redirect( admin_url( 'admin.php?page=cpt_auto_menu&tab=select_cpt' ) );
					exit;
				}
			}

			return;
		}

		/**
		 * Prepare our js to be loaded into the queue after add admin menu page has been called
		 * @link  http://wordpress.stackexchange.com/questions/41207/how-do-i-enqueue-styles-scripts-on-certain-wp-admin-pages#76420
		 *
		 * @since 1.1.0
		 */
		public function load_admin_js() {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_js' ) );
		}

		/**
		 * Register and enqueue admin-specific JavaScript.
		 *
		 * @since     1.0.0
		 *
		 * @version 2.0.0
		 */
		public function enqueue_admin_js() {

			wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( 'js/admin.js', dirname( __FILE__ ) ), [
				'jquery',
				'wp-element',
				'wp-components'
			], $this->version, true );

			// wp_localize_script added to same action hook
			$this->localize_admin_script();
		}

		/**
		 * Localize admin script so we can pass Selected Menu object in ajax. Also add nonce for security.
		 *
		 * @since 1.0.0
		 *
		 */
		private function localize_admin_script() {

			wp_localize_script(
				$this->plugin_slug . '-admin-script', 'AjaxSelected', array(
					'ajaxurl'   => admin_url( 'admin-ajax.php' ),
					'ajaxnonce' => wp_create_nonce( 'ajax-form-nonce' )
				)
			);
		}

		/**
		 * Menu Callback and View
		 *
		 * @since 1.0.0
		 *
		 */
		public function plugin_settings_page() {

			// if user does not have capabilities give error message
			if ( ! current_user_can( 'manage_options' ) ) {

				wp_die( __( 'You do not have sufficient permissions to access this page', 'bfp-cpt-auto-menu' ) );
			}

			// render the settings template
			include( plugin_dir_path( __DIR__ ) . 'views/settings.php' );
		}

		/**
		 * Load our css stylesheet.
		 *
		 * @since 1.1.0
		 *
		 * @version 2.0.0
		 *
		 */
		public function load_admin_css() {
			wp_enqueue_style( 'cpt-auto-menu-style', plugins_url( 'css/cpt-auto-menu.css', dirname( __FILE__ ) ), [ 'wp-components' ], $this->version );
		}

	}

}


