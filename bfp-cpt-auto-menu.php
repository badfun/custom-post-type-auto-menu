<?php
/*
Plugin Name: Custom Post Type Auto Menu
Plugin URI: https://github.com/badfun/custom-post-type-auto-menu
Description: Automatically adds new custom post type posts to the chosen menu and parent item as a sub-menu item.
Version: 1.1.3
Author: Ken Dirschl, Bad Fun Productions
Author URI: http://badfunproductions.com
Author Email: ken@badfunproductions.com
Text Domain: bfp-cpt-auto-menu
Domain Path: /lang/

License:

  Copyright 2014 Bad Fun Productions

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/

if ( ! class_exists( 'Custom_Post_Type_Auto_Menu' ) ) {


	class Custom_Post_Type_Auto_Menu {

		/**
		 * Create private instance variable
		 * @link  http://jumping-duck.com/tutorial/wordpress-plugin-structure/
		 *
		 * @since 1.0.0
		 *
		 * @var bool
		 */
		private static $instance = false;


		/**
		 * Instantiate singleton object
		 * @link  http://jumping-duck.com/tutorial/wordpress-plugin-structure/
		 *
		 * @since 1.0.0
		 *
		 * @return bool|Custom_Post_Type_Auto_Menu
		 */
		public static function get_instance() {
			if ( ! self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}


		/**
		 * Plugin version, used for cache-busting of style and script file references.
		 *
		 * @since   1.0.0
		 *
		 * @var     string
		 */
		protected $version = '1.1.3';


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
		 * Constructor. Load action hooks here.
		 * @version 1.1.0
		 *
		 * @since   1.0.0
		 */
		private function __construct() {
			// load text domain for internationalization
			add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

			// test for nav menus
			add_action( 'admin_notices', array( $this, 'test_for_nav_menu_support' ) );
			add_action( 'admin_notices', array( $this, 'test_for_nav_menu' ) );

			// load main hook action
			add_action( 'save_post', array( $this, 'cpt_auto_menu_save' ) );

			// load admin settings actions
			add_action( 'admin_init', array( &$this, 'admin_init' ) );

			// create an admin page and prepare enqueue our scripts
			add_action( 'admin_menu', array( &$this, 'add_admin_menu_page' ) );

			// redirect must happen before headers are sent
			add_action( 'admin_menu', array( $this, 'cpt_settings_redirect' ) );

			// load ajax handler
			add_action( 'wp_ajax_admin_script_ajax', array( $this, 'admin_script_ajax_handler' ) );

			// register activation
			register_activation_hook( __FILE__, array( $this, 'activate' ) );

		}


		/**
		 * Properties
		 * @version 1.1.0
		 *
		 * @since   1.0.0
		 *
		 * @var string
		 */
		private $parent_menu;
		private $parent_menu_ID;
		private $parent_menu_item;
		private $parent_menu_item_ID;
		private $current_cpt;
		private $cpt_list;
		private $settings;
		private $main_page;


		/**
		 * Activation
		 * @version 1.1.0
		 *
		 * @since   1.0.0
		 */
		static function activate() {
			//@TODO-bfp: this redirect not working
			// Redirect to settings page if not activating multiple plugins at once
			if ( ! isset( $_GET['activate-multi'] ) ) {
				wp_redirect( admin_url( 'admin.php?page=cpt_auto_menu&tab=select_cpt' ) );
			}
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
		 * Add admin menu page and load admin js action to load script only on our page
		 * @link  http://wordpress.stackexchange.com/questions/41207/how-do-i-enqueue-styles-scripts-on-certain-wp-admin-pages#76420
		 * dashicons requires version 3.8: @link http://melchoyce.github.io/dashicons/
		 *
		 * @since 1.1.0
		 *
		 */
		public function add_admin_menu_page() {
			$this->main_page = add_menu_page(

				__( 'CPT Auto Menu Settings', 'bfp-cpt-auto-menu' ),
				__( 'CPT Auto Menu', 'bfp-cpt-auto-menu' ),
				'manage_options',
				'cpt_auto_menu',
				array( &$this, 'plugin_settings_page' ),
				'dashicons-screenoptions'

			);

			add_action( 'load-' . $this->main_page, array( $this, 'load_admin_js' ) );

			// also use this hook for page redirection
			add_action( 'load-' . $this->main_page, array( $this, 'admin_page_redirect' ) );

			// and for adding our stylesheet
			add_action( 'admin_print_styles-' . $this->main_page, array( $this, 'load_admin_css' ) );
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
		 */
		public function enqueue_admin_js() {

			wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( 'js/admin.js', __FILE__ ), array( 'jquery' ), $this->version );

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
		 * Register our css stylesheet
		 *
		 * @since 1.1.0
		 *
		 */
		private function admin_register_css() {
			wp_register_style( 'cpt-auto-menu-style', plugins_url( 'css/cpt-auto-menu.css', __FILE__ ) );
		}


		/**
		 * Load our css stylesheet.
		 *
		 * @since 1.1.0
		 *
		 */
		public function load_admin_css() {
			wp_enqueue_style( 'cpt-auto-menu-style' );
		}

		/**
		 * Test for Nav Menu Support. If theme does not support menus output admin error notice
		 *
		 * @since 1.0.0
		 *
		 * @return bool
		 *
		 */
		public function test_for_nav_menu_support() {

			if ( ! current_theme_supports( 'menus' ) ) {
				$html = '<div class="error"><p>';
				$html .= __( 'Your theme does not support custom navigation menus. The plugin requires themes built for at least WordPress 3.0', 'bfp-cpt-auto-menu' );
				$html .= '</p></div>';

				echo $html;

			}

			// otherwise return true
			return true;
		}


		/**
		 * Test that a Nav Menu has been setup, otherwise output admin error notice
		 *
		 * @since 1.0.0
		 *
		 * @return bool
		 */
		public function test_for_nav_menu() {

			// if theme does not support menus don't need to show this message as well
			if ( current_theme_supports( 'menus' ) ) {

				$menus = wp_get_nav_menus();

				if ( empty( $menus ) ) {
					$html = '<div class="error"><p>';
					$html .= __( 'You do not have any menus setup with your theme. You need to create a menu to use this plugin.', 'bfp-cpt-auto-menu' );
					$html .= '</p></div>';

					echo $html;

				}

				// otherwise return true
				return true;

			}

		}


		/**
		 * Receive the Ajax data from POST and use it on the page
		 * Some good info here: @link http://www.garyc40.com/2010/03/5-tips-for-using-ajax-in-wordpress/
		 * @version 1.1.0
		 *
		 * @since   1.0.0
		 *
		 *
		 */
		public function admin_script_ajax_handler() {

			// Menu name determines Parent Menu Item
			if ( isset( $_POST['selected_menu'] ) ) {

				// verify our nonce
				if ( ! wp_verify_nonce( $_POST['ajaxnonce'], 'ajax-form-nonce' ) ) {
					die ( __( 'There is an access error', 'bfp-cpt-auto-menu' ) );
				}

				// verify user has permission
				if ( ! current_user_can( 'edit_posts' ) ) {
					die ( __( 'You do not have sufficient permission', 'bfp-cpt-auto-menu' ) );
				}

				$main_menu = wp_get_nav_menu_object( $_POST['selected_menu'] );

				// make sure there is a value then extract the ID
				if ( true == $main_menu ) {
					$parent_menu_ID = (int) $main_menu->term_id;


					// get option if one exists
					$parent_menu_item = $this->settings['parent_menu'];

					$menu_items = wp_get_nav_menu_items( $parent_menu_ID, array( 'post_status' => 'publish' ) );

					foreach ( $menu_items as $menu_item ) {
						// only display items in the root menu
						if ( $menu_item->menu_item_parent != 0 ) {
							continue;
						}
						echo '<option value="' . $menu_item->title . '"' . selected( $this->settings['parent_menu'], $menu_item->title, false ) . '>' . ucfirst( $menu_item->title ) . '</option>';
					}

				}

			}
		}


		/**
		 * Get all custom post types as names and put in an array for later access
		 *
		 * @since 1.1.0
		 *
		 * @return array
		 */
		private function get_custom_post_type_names() {
			$args = array(
				'public'   => true,
				'_builtin' => false
			);

			// note: $output and $operator are defaults but here for readability
			$output   = 'names';
			$operator = 'and';

			$custom_post_types = get_post_types( $args, $output, $operator );

			return $custom_post_types;

		}


		/**
		 * Get the settings for the CPT's saved in options. This way it is a single call to database
		 * @link http://stackoverflow.com/questions/8102221/php-multidimensional-array-searching-find-key-by-specific-value
		 *
		 * @param $cpt
		 *
		 * @return mixed
		 */
		private function get_cpt_settings( $cpt ) {
			// make sure settings exist and option is not empty
			if ( get_option( 'cpt_auto_menu_settings' ) && ( true == get_option( 'cpt_auto_menu_settings' ) ) ) {

				$settings = get_option( 'cpt_auto_menu_settings' );

				// loop through the main array for each sub array
				foreach ( $settings as $setting ) {
					// loop through sub array
					foreach ( $setting as $key => $value ) {

						if ( $value === $cpt ) {
							return $setting;
							break;
						}

					}

				}

			}

		}


		/**
		 * Get the Custom Post Type for the current post in a single call
		 *
		 * @since 1.1.0
		 *
		 * @return bool|string
		 *
		 */
		private function get_current_cpt() {
			$screen = get_current_screen();

			// if we are on our page do nothing
			if ( $screen->id == $this->main_page ) {
				return;
			}

			// otherwise get the current custom post type
			$this->current_cpt = get_post_type();

			return $this->current_cpt;
		}


		/**
		 * Get the selected menu name matched with current cpt
		 *
		 * @version 1.1.0
		 *
		 * @since   1.0.0
		 *
		 * @return mixed
		 */
		private function get_parent_menu_name() {
			$cpt = $this->get_current_cpt();

			// get the settings array for this cpt
			$settings = $this->get_cpt_settings( $cpt );

			if ( $settings['menu_name'] != false ) {

				$this->parent_menu = $settings['menu_name'];

				return $this->parent_menu;

			}

			return;
		}


		/**
		 * Extract the selected menu ID from the Nav Menu Object
		 *
		 * @since 1.0.0
		 *
		 * @return int
		 */
		private function get_parent_menu_ID() {
			// first retrieve the object
			$main_menu = wp_get_nav_menu_object( $this->get_parent_menu_name() );

			// nav menu object returns false if there is no menu
			if ( $main_menu != false ) {
				// then extract the ID
				$this->parent_menu_ID = (int) $main_menu->term_id;

				return $this->parent_menu_ID;
			}

		}


		/**
		 * Get the selected parent menu item name matched with current cpt
		 * @version 1.1.0
		 *
		 * @since   1.0.0
		 *
		 * @return mixed
		 */
		private function get_parent_menu_item() {
			// get current cpt
			$cpt = $this->get_current_cpt();
			// get the settings array for this cpt
			$settings = $this->get_cpt_settings( $cpt );

			$this->parent_menu_item = $settings['parent_menu'];

			return $this->parent_menu_item;
		}


		/**
		 * Extract the ID from the current list of menu items
		 * @TODO-bfp: consider allowing sub sub-menu's as well
		 *
		 * @since   1.0.0
		 *
		 * @return int
		 */
		private function get_parent_menu_item_ID() {
			// retrieve the list menu items
			$menu_items = wp_get_nav_menu_items( $this->get_parent_menu_ID(), array( 'post_status' => 'publish' ) );

			// wp_get_nav_menu_items returns false if there are no menu items
			if ( $menu_items == false ) {
				return;
			}

			// loop through each post object
			foreach ( $menu_items as $menu_item ) {
				// make sure it is top level only
				if ( $menu_item->menu_item_parent != 0 ) {
					continue;
				}

				// if menu item title matches user selected setting extract id
				if ( $this->get_parent_menu_item() == $menu_item->title ) {
					$this->parent_menu_item_ID = $menu_item->ID;
				}
			}

			return $this->parent_menu_item_ID;
		}


		/**
		 * Get selected custom post types saved in options or create empty array
		 *
		 * @since 1.1.0
		 *
		 * @return array|mixed|void
		 */
		private function get_selected_cpts() {
			if ( get_option( 'cpt_auto_menu_cpt_list' ) ) {
				$this->cpt_list = get_option( 'cpt_auto_menu_cpt_list' );
			} else {
				$this->cpt_list = array();
			}

			return $this->cpt_list;
		}


		/**
		 * Hook into WP's admin_init action hook and add our sections and fields
		 * @version 1.1.0
		 *
		 * @since   1.0.0
		 *
		 */
		public function admin_init() {
			// add our option rows to options table
			if ( false == get_option( 'cpt_auto_menu_cpt_list' ) ) {
				add_option( 'cpt_auto_menu_cpt_list' );
			}

			if ( false == get_option( 'cpt_auto_menu_settings' ) ) {
				add_option( 'cpt_auto_menu_settings' );
			}


			// register the settings
			register_setting( 'select_cpt_settings', 'cpt_auto_menu_cpt_list', array( &$this, 'cpt_settings_validation' ) );
			register_setting( 'select_menu_settings', 'cpt_auto_menu_settings', array( &$this, 'menu_settings_validation' ) );

			/*
			 * Sections
			 */

			// Select custom post type(s) section
			add_settings_section(
				'select_cpt_section',
				__( 'Custom Post Type Settings', 'bfp-cpt-auto-menu' ),
				array( &$this, 'select_cpt_section' ),
				'select_cpt_settings'
			);

			// Select menu and menu parent item section
			add_settings_section(
				'select_menu_section',
				__( 'Menu and Parent Menu Item Settings', 'bfp-cpt-auto-menu' ),
				array( &$this, 'select_menu_section' ),
				'select_menu_settings'
			);

			/*
			 * Fields
			 */

			// select custom post types checkbox menu
			add_settings_field(
				'cpt_auto_menu-select_cpts',
				__( 'Available Custom Post Types', 'bfp-cpt-auto-menu' ),
				array( &$this, 'settings_field_select_cpts' ),
				'select_cpt_settings',
				'select_cpt_section',
				array()
			);


			// select menu and parent menu item
			add_settings_field(
				'cpt_auto_menu-select_menus',
				__( 'Select Menu and Parent Menu Item', 'bfp-cpt-auto-menu' ),
				array( &$this, 'settings_field_select_menus' ),
				'select_menu_settings',
				'select_menu_section',
				array()
			);

			// add our registered stylesheet
			$this->admin_register_css();

		}


		/**
		 * Select CPT section callback
		 * @version 1.1.0
		 *
		 * @since   1.0.0
		 *
		 * @link    http://wordpress.stackexchange.com/questions/89251/run-function-on-settings-save
		 *
		 */
		public function select_cpt_section() {

			echo __( 'Select the custom post types for which you would like an automated menu.', 'bfp-cpt-auto-menu' );

			// if selected cpts are not an empty array then redirect to menu page, otherwise give error message
			if ( $this->get_selected_cpts() ) {
				// redirect after save
				$this->cpt_settings_redirect();
			}

		}


		/**
		 * Select menu section callback
		 *
		 * @since 1.1.0
		 *
		 */
		public function select_menu_section() {

			echo __( 'Select the menu and parent menu item for each custom post type', 'bfp-cpt-auto-menu' );
		}


		/**
		 * Select which custom post types require auto menu option
		 *
		 * @since 1.1.0
		 */
		public function settings_field_select_cpts() {

			$selected_cpts = $this->get_selected_cpts();

			// need to define html variable before foreach loop with concantanation
			$html = '';

			// get existing custom post types and display as checklist
			foreach ( $this->get_custom_post_type_names() as $post_type ) {
				// check if cpt exists in get options array
				if ( in_array( $post_type, $selected_cpts ) ) {

					// if yes add checked option
					$html .= '<input type="checkbox" class="cpts_list" name="cpt_auto_menu_cpt_list[]" value="' . $post_type . '" checked="checked">' . ucfirst( $post_type ) . '<br />';
				} else {
					// otherwise display without
					$html .= '<input type="checkbox" class="cpts_list" name="cpt_auto_menu_cpt_list[]" value="' . $post_type . '">' . ucfirst( $post_type ) . '<br />';

				}
			}

			echo $html;
		}


		/**
		 * Get menu names and add to Select Menu
		 *
		 * @since 1.0.0
		 *
		 */
		private function settings_field_select_menu() {

			$text = __( 'Select Menu', 'bfp-cpt-auto-menu' );

			// get list of menus
			//@TODO-bfp: may need to lowercase results
			$menus = get_terms( 'nav_menu' );

			$html = '<select class="menu_name" name="cpt_auto_menu_settings[menu_name][]">';
			$html .= '<option value="default" class="highlight">' . $text . '</option>';


			foreach ( $menus as $menu ) {

				$html .= '<option value="' . $menu->name . '"' . selected( $this->settings['menu_name'], $menu->name, false ) . '>' . ucfirst( $menu->name ) . '</option>';
			}


			$html .= '</select>';
			echo $html;
		}


		/**
		 * Get menu item names and add to Select Parent Menu Item
		 *
		 * @since   1.0.0
		 *
		 * @TODO-bfp: wp_get_nav_menu_items() generating php notice
		 *
		 */
		private function settings_field_select_parent_menu_item() {

			$text = __( 'Select Menu Item', 'bfp-cpt-auto-menu' );

			$html = '<select class="parent_name" name="cpt_auto_menu_settings[parent_name][]">';
			$html .= '<option value="default" class="highlight">' . $text . '</option>';

			// if options exist in db then show selected option
			if ( $this->settings['parent_menu'] != false ) {

				$main_menu = wp_get_nav_menu_object( $this->settings['menu_name'] );

				// then extract the ID
				$parent_menu_ID = (int) $main_menu->term_id;

				// get option if one exists
				$parent_menu_item = $this->settings['parent_menu'];

				$menu_items = wp_get_nav_menu_items( $parent_menu_ID, array( 'post_status' => 'publish' ) );

				foreach ( $menu_items as $menu_item ) {
					// only display items in the root menu
					if ( $menu_item->menu_item_parent != 0 ) {
						continue;
					}
					$html .= '<option value="' . $menu_item->title . '"' . selected( $this->settings['parent_menu'], $menu_item->title, false ) . '>' . ucfirst( $menu_item->title ) . '</option>';
				}

			}

			$html .= '</select>';
			echo $html;

		}


		/**
		 * Main function for returning multiple fields based on users CPT selection
		 *
		 * @since 1.1.0
		 *
		 */
		public function settings_field_select_menus() {
			// get saved cpt options
			$selected_cpts = $this->get_selected_cpts();
			// set up an id for our array keys
			$i = 0;

			// for each cpt selected display our fields
			foreach ( $selected_cpts as $selected_cpt ) {

				// get settings array for each cpt and pass into a reusable variable
				$this->settings = $this->get_cpt_settings( $selected_cpt );

				$form = '<input type="hidden" name="cpt_auto_menu_settings[id][]" value="' . $i ++ . '">';
				$form .= '<input type="hidden" name="cpt_auto_menu_settings[cpt][]" value="' . $selected_cpt . '">';

				echo '<h4 class="cpt-heading">' . ucfirst( $selected_cpt ) . '</h4>';
				echo $form;

				echo $this->settings_field_select_menu();
				echo $this->settings_field_select_parent_menu_item();

				echo '<br />';

			}

		}


		/**
		 * Callback to redirect to Menu Settings tab after saving CPT settings. Hooked in Settings Section callback
		 *
		 * @since   1.1.0
		 *
		 * @version 1.1.3
		 *
		 * @param $input
		 *
		 * @return mixed
		 */
		public function cpt_settings_redirect() {
			// check if save settings have been submitted and at least one cpt has been selected
			if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] == true ) {

				// if no cpt selected echo error
				if ( ! $this->get_selected_cpts() ) {

					add_settings_error(
						'cpt_error',
						esc_attr( 'settings_updated' ),
						__( 'You need to select at least one Custom Post Type', 'bfp-cpt-auto-menu' ),
						'error'
					);

				} else {
					// otherwise safe to redirect to menu page
					wp_redirect( admin_url( 'admin.php?page=cpt_auto_menu&tab=select_menu' ) );
					exit;
				}

			}

			return;
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
		 * Callback from cpt settings.
		 * @link    https://github.com/tommcfarlin/WordPress-Settings-Sandbox/blob/master/functions.php
		 *
		 * @since   1.1.0
		 *
		 * @version 1.1.3
		 *
		 * @param $input
		 *
		 * @return mixed
		 */
		public function cpt_settings_validation( $input ) {

			$output = array();

			foreach ( $input as $key => $value ) {
				// check to see if current option has value. If so, process it.
				if ( isset ( $input[$key] ) ) {
					// strip all HTML and PHP tags and properly handle quoted strings
					$output[$key] = strip_tags( stripslashes( $input[$key] ) );
				}
			}

			return apply_filters( 'cpt_settings_validation', $output, $input );
		}


		/**
		 * Callback to merge the fields arrays and sanitize the inputs
		 * @link  http://stackoverflow.com/questions/6553752/array-combine-three-or-more-arrays-with-php
		 *
		 * @since 1.1.0
		 *
		 * @param $input
		 *
		 * @return array
		 */
		public function menu_settings_validation( $input ) {
			$keys              = $input['id'];
			$cpt_array         = $input['cpt'];
			$menu_name_array   = $input['menu_name'];
			$parent_name_array = $input['parent_name'];

			$output = strip_tags( stripslashes( array() ) );

			foreach ( $keys as $id => $key ) {
				$output[$key] = array(
					'cpt'         => $cpt_array[$id],
					'menu_name'   => $menu_name_array[$id],
					'parent_menu' => $parent_name_array[$id]

				);
			}

			return apply_filters( 'menu_settings_validation', $output, $input );

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
			include( plugin_dir_path( __FILE__ ) . '/views/settings.php' );

		}

		/**
		 * Create Nav Menu Items from new Custom Post Type
		 *
		 * @since   1.0.0
		 *
		 * @param $post_id
		 *
		 * @TODO-bfp: remove menu item if exists for a draft. If user has published, and then makes a draft, menu item is not removed
		 * @TODO-bfp: bulk trashes do not work.
		 */
		public function cpt_auto_menu_save( $post_id ) {
			// get the current post
			$post = get_post( $post_id );

			//  verify post is not a revision or auto-save: http: //tommcfarlin.com/wordpress-save_post-called-twice/
			if ( wp_is_post_revision( $post->ID ) && wp_is_post_autosave( $post->ID ) ) {
				return;
			}

			$itemData = array(
				'menu-item-object-id' => $post->ID,
				'menu-item-parent-id' => $this->get_parent_menu_item_ID(),
				'menu-item-position'  => 0,
				'menu-item-object'    => $this->get_current_cpt(),
				'menu-item-type'      => 'post_type',
				'menu-item-status'    => 'publish'
			);

			// Check if menu items exist
			$current_menu_items = wp_get_nav_menu_items( $this->get_parent_menu_ID(), array( 'post_status' => 'publish' ) );

			// if no menu items exist then exit
			if ( ! is_array( $current_menu_items ) ) {
				return;
			}

			// create array for titles
			$current_menu_titles = array();

			// extract list of titles from menu objects and populate array
			foreach ( $current_menu_items as $current_menu_item ) {
				$current_menu_titles[] = $current_menu_item->title;

				// get the menu post object id from matching the current id to the object id in menu post object
				// check if item is being trashed
				if ( $current_menu_item->object_id == $post->ID && get_post_status( $post->ID ) == 'trash' ) {
					// the id of the menu post object NOT the post!
					$menuID = $current_menu_item->db_id;

					// delete the nav menu item post object
					wp_delete_post( $menuID );

				}

			}


			// otherwise get title of current project
			$new_project_title = get_the_title( $post->ID );


			// make sure title does not exist already and that it is not an auto-draft
			if ( ! in_array( $new_project_title, $current_menu_titles ) && ( get_post_status( $post->ID ) != 'auto-draft' ) ) {

				// make sure the selected custom post type matches
				if ( $this->get_current_cpt() != get_post_type( $post->ID ) ) {
					return;
				}

				// finally check that we are not adding a draft to the menu
				if ( get_post_status( $post->ID ) != 'draft' ) {

					// if new title then go ahead and add new menu item
					wp_update_nav_menu_item( $this->get_parent_menu_ID(), 0, $itemData );
				}

			}

		}

	}

}

$cpt_auto_menu_save = Custom_Post_Type_Auto_Menu::get_instance();