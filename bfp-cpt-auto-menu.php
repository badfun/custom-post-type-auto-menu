<?php
/*
Plugin Name: Custom Post Type Auto Menu
Plugin URI: https://github.com/badfun/custom-post-type-auto-menu
Description: Automatically adds new custom post type posts to the chosen menu and parent item as a sub-menu item.
Version: 1.1.0
Author: Ken Dirschl, Bad Fun Productions
Author URI: http://badfunproductions.com
Author Email: ken@badfunproductions.com
License:

  Copyright 2013 Bad Fun Productions

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

//@TODO-bfp: fix page where options reside
//@TOD-bfp: redirect to menu settings after cpt settings is saved
//@TODO-bfp: solve double 'settings saved' error
//@TODO-bfp: remove all debug code

if (!class_exists('Custom_Post_Type_Auto_Menu')) {


    class Custom_Post_Type_Auto_Menu
    {

        /**
         * Create private instance variable
         * http://jumping-duck.com/tutorial/wordpress-plugin-structure/
         *
         * @since 1.0.0
         *
         * @var bool
         */
        private static $instance = false;


        /**
         * Instantiate singleton object
         * http://jumping-duck.com/tutorial/wordpress-plugin-structure/
         *
         * @since 1.0.0
         *
         * @return bool|Custom_Post_Type_Auto_Menu
         */
        public static function get_instance() {
            if (!self::$instance) {
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
        protected $version = '1.1.0';


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
         */
        private function __construct() {
            // load text domain for internationalization
            add_action('init', array($this, 'load_plugin_textdomain'));

            // test for nav menus
            add_action('admin_notices', array($this, 'test_for_nav_menu_support'));
            add_action('admin_notices', array($this, 'test_for_nav_menu'));

            // load main hook action
            add_action('save_post', array($this, 'custom_post_type_auto_menu'));

            // load admin settings actions
            add_action('admin_init', array(&$this, 'admin_init'));
            add_action('admin_menu', array(&$this, 'add_menu'));

            // Load admin style sheet and JavaScript.
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

            // load ajax handler
            add_action('wp_ajax_admin_script_ajax', array($this, 'admin_script_ajax_handler'));

            // register activation
            register_activation_hook(__FILE__, array($this, 'activate'));

        }


        /**
         * Properties
         *
         * @var string
         */
        private $parent_menu;
        private $parent_menu_ID;
        private $parent_menu_item;
        private $parent_menu_item_ID;
        private $cpt_name;
        private $cpt_list;
        private $menu_selects;


        /**
         * Activation
         */
        static function activate() {
            //@TODO-bfp: this redirect not working
            // Redirect to settings page if not activating multiple plugins at once
            if (!isset($_GET['activate-multi'])) {
                wp_redirect(admin_url('options-general.php?page=cpt_auto_menu'));
            }
        }


        /**
         * Load the plugin text domain for translation.
         *
         * @since    1.0.0
         */
        public function load_plugin_textdomain() {

            $domain = $this->plugin_slug;
            $locale = apply_filters('plugin_locale', get_locale(), $domain);

            load_textdomain($domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo');
            load_plugin_textdomain($domain, FALSE, dirname(plugin_basename(__FILE__)) . '/lang/');
        }


        /**
         * Register and enqueue admin-specific JavaScript.
         *
         * @since     1.0.0
         *
         * @TODO-bfp: Important! use page hook suffix to load our script only on our page:
         * http://codex.wordpress.org/Function_Reference/wp_enqueue_script#Link_Scripts_Only_on_a_Plugin_Administration_Screen
         *
         */
        public function enqueue_admin_scripts() {

            wp_enqueue_script($this->plugin_slug . '-admin-script', plugins_url('js/admin.js', __FILE__), array('jquery'), $this->version);

            // wp_localize_script added to same action hook
            $this->localize_admin_script();

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
                'public' => true,
                '_builtin' => false
            );

            // note: $output and $operator are defaults but here for readability
            $output = 'names';
            $operator = 'and';

            $custom_post_types = get_post_types($args, $output, $operator);

            return $custom_post_types;

        }


        /**
         * Localize admin script so we can pass Selected Menu object in ajax. Also add nonce for security.
         *
         * @since 1.0.0
         *
         */
        private function localize_admin_script() {

            wp_localize_script($this->plugin_slug . '-admin-script', 'AjaxSelected', array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'ajaxnonce' => wp_create_nonce('ajax-form-nonce')
                ));
        }


        /**
         * Receive the Ajax data from POST and use it on the page
         * Some good info here: http://www.garyc40.com/2010/03/5-tips-for-using-ajax-in-wordpress/
         * @version 1.1.0
         *
         * @since 1.0.0
         *
         */
        public function admin_script_ajax_handler() {

            // Menu name determines Parent Menu Item
            if (isset($_POST['selected_menu'])) {

                // verify our nonce
                if (!wp_verify_nonce($_POST['ajaxnonce'], 'ajax-form-nonce')) {
                    die ('There is an access error');
                }

                // verify user has permission
                if (!current_user_can('edit_posts')) {
                    die ('You do not have sufficient permission');
                }

                $main_menu = wp_get_nav_menu_object($_POST['selected_menu']);

                // then extract the ID
                $parent_menu_ID = (int)$main_menu->term_id;

                // get option if one exists
                $parent_menu_item = get_option('select_parent_menu');

                $menu_items = wp_get_nav_menu_items($parent_menu_ID, array('post_status' => 'publish'));

                foreach ($menu_items as $menu_item) {
                    // only display items in the root menu
                    if ($menu_item->menu_item_parent != 0) {
                        continue;
                    }
                    echo '<option value="' . $menu_item->title . '"' . selected($parent_menu_item['parent_name'], $menu_item->title, false) . '>' . ucfirst($menu_item->title) . '</option>';
                }

            } /*
             * Selected CPT's display individual fields for each
             */
            elseif (isset($_POST['selected_cpt'])) {
                // verify our nonce
                if (!wp_verify_nonce($_POST['ajaxnonce'], 'ajax-form-nonce')) {
                    die ('There is an access error');
                }

                // verify user has permission
                if (!current_user_can('edit_posts')) {
                    die ('You do not have sufficient permission');
                }

                //  $this->test_function();


            }
        }

        private function test_function() {

            // run function for each selected
            $selected_cpts = $_POST['selected_cpt'];
            foreach ($selected_cpts as $selected_cpt) {
                $this->settings_field_select_cpt();
                $this->settings_field_select_menu();
                $this->settings_field_select_parent_menu_item();

                echo '<br />';


            }
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

            if (!current_theme_supports('menus')) {
                $html = '<div class="error"><p>';
                $html .= __('Your theme does not support custom navigation menus. The plugin requires themes built for at least WordPress 3.0');
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
            if (current_theme_supports('menus')) {

                $menus = wp_get_nav_menus();

                if (empty($menus)) {
                    $html = '<div class="error"><p>';
                    $html .= __('You do not have any menus setup with your theme. You need to create a menu to use this plugin.');
                    $html .= '</p></div>';

                    echo $html;

                }
                // otherwise return true
                return true;

            }

        }


        /**
         * Get the selected menu name from Admin settings
         *
         * @since 1.0.0
         *
         * @return mixed
         */
        private function get_parent_menu_name() {

            // first make sure that a menu has been selected
            if (get_option('select_menu') != false) {

                $this->parent_menu = get_option('select_menu');

                return $this->parent_menu['menu_name'];

            }

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
            $main_menu = wp_get_nav_menu_object($this->get_parent_menu_name());

            // nav menu object returns false if there is no menu
            if ($main_menu != false) {
                // then extract the ID
                $this->parent_menu_ID = (int)$main_menu->term_id;

                return $this->parent_menu_ID;
            }

        }


        /**
         * Get the selected parent menu item name from the Admin settings
         *
         * @since 1.0.0
         *
         * @return mixed
         */
        private function get_parent_menu_item() {
            $this->parent_menu_item = get_option('select_parent_menu');

            return $this->parent_menu_item['parent_name'];
        }


        /**
         * Extract the ID from the current list of menu items
         * @TODO-bfp: consider allowing sub sub-menu's as well
         *
         * @since 1.0.0
         *
         * @return int
         */
        private function get_parent_menu_item_ID() {
            // retrieve the list menu items
            $menu_items = wp_get_nav_menu_items($this->get_parent_menu_ID(), array('post_status' => 'publish'));

            // wp_get_nav_menu_items returns false if there are no menu items
            if ($menu_items == false) {
                return;
            }

            // loop through each post object
            foreach ($menu_items as $menu_item) {
                // make sure it is top level only
                if ($menu_item->menu_item_parent != 0) {
                    continue;
                }

                // if menu item title matches user selected setting extract id
                if ($this->get_parent_menu_item() == $menu_item->title) {
                    $this->parent_menu_item_ID = $menu_item->ID;
                }
            }

            return $this->parent_menu_item_ID;
        }


        /**
         * Get the Custom Post Type selected in Admin settings
         * @TODO-bfp: this is probably deprecated. double-check
         *
         * @since 1.0.0
         *
         * @return mixed
         */
        private function get_cpt_name() {
            $this->cpt_name = get_option('select_cpt');

            return $this->cpt_name['cpt_name'];
        }


        /**
         * Get selected custom post types saved in options or create empty array
         *
         * @since 1.1.0
         *
         * @return array|mixed|void
         */
        private function get_selected_cpts() {
            if (get_option('cpt_auto_menu_cpt_list')) {
                $this->cpt_list = get_option('cpt_auto_menu_cpt_list');
            } else {
                $this->cpt_list = array();
            }

            return $this->cpt_list;
        }


        private function get_selected_menu() {
            if (get_option('cpt_auto_menu_settings')) {
                $this->menu_selects = get_option('cpt_auto_menu_settings');
            } else {
                $this->menu_selects = array();
            }
        }


        /**
         * Hook into WP's admin_init action hook and add our sections and fields
         * @version 1.1.0
         *
         * @since 1.0.0
         *
         */
        public function admin_init() {

            // register the settings
            //@TODO-bfp: add sanitization callback to both settings
            register_setting('select_cpt_settings', 'cpt_auto_menu_cpt_list');
            register_setting('select_menu_settings', 'cpt_auto_menu_settings', array(&$this, 'menu_settings_validation'));

            /*
             * Sections
             */

            // Select custom post type(s) section
            add_settings_section(
                'select_cpt_section',
                __('Custom Post Type Settings'),
                array(&$this, 'select_cpt_section'),
                'select_cpt_settings'
            );

            // Select menu and menu parent item section
            add_settings_section(
                'select_menu_section',
                __('Menu and Parent Menu Item Settings'),
                array(&$this, 'select_menu_section'),
                'select_menu_settings'
            );

            /*
             * Fields
             */

            // select custom post types checkbox menu
            add_settings_field(
                'cpt_auto_menu-select_cpts',
                __('Available Custom Post Types'),
                array(&$this, 'settings_field_select_cpts'),
                'select_cpt_settings',
                'select_cpt_section',
                array()
            );


            // select menu and parent menu item
            add_settings_field(
                'cpt_auto_menu-select_menus',
                __('Select Menu and Parent Menu Item'),
                array(&$this, 'settings_field_select_menus'),
                'select_menu_settings',
                'select_menu_section',
                array()
            );


            // single cpt field
            // @TODO-bfp: remove this after testing

//            add_settings_field(
//                'cpt_auto_menu-select_cpt',
//                __('Custom Post Type'),
//                array(&$this, 'settings_field_select_cpt'),
//                'select_menu_settings',
//                'select_menu_section',
//                array(
//                    'field' => 'select_cpt'
//                )
//            );

            // menu to associate with cpt
//            add_settings_field(
//                'cpt_auto_menu-select_menu',
//                __('Menu Name'),
//                array(&$this, 'settings_field_select_menu'),
//                'select_menu_settings',
//                'select_menu_section',
//                array(
//                    'field' => 'select_menu'
//                )
//            );
//
//            // parent menu item to associate with cpt
//            add_settings_field(
//                'cpt_auto_menu-select_parent_menu',
//                __('Parent Menu Item'),
//                array(&$this, 'settings_field_select_parent_menu_item'),
//                'select_menu_settings',
//                'select_menu_section',
//                array(
//                    'field' => 'select_parent_menu'
//                )
//            );


        }

        /**
         * Select CPT section description
         * @version 1.1.0
         *
         * @since 1.0.0
         *
         */
        public function select_cpt_section() {

            echo __('Select the custom post types for which you would like an automated menu.');

        }


        /**
         * Select menu section description
         *
         * @since 1.1.0
         *
         */
        public function select_menu_section() {

            echo __('Select the menu and parent menu item for each custom post type');
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
            foreach ($this->get_custom_post_type_names() as $post_type) {
                // check if cpt exists in get options array
                if (in_array($post_type, $selected_cpts)) {

                    // if yes add checked option
                    $html .= '<input type="checkbox" class="cpts_list" name="cpt_auto_menu_cpt_list[]" value="' . $post_type . '" checked="checked">' . ucfirst($post_type) . '<br />';
                } else {
                    // otherwise display without
                    $html .= '<input type="checkbox" class="cpts_list" name="cpt_auto_menu_cpt_list[]" value="' . $post_type . '">' . ucfirst($post_type) . '<br />';

                }
            }

            echo $html;
        }


//        /**
//         * Get custom post type names and add to Select Custom Post Type Menu
//         *
//         * @since 1.0.0
//         *
//         */
//        public function settings_field_select_cpt() {
//            // get current setting if there is one
//            $cpt_option = get_option('select_cpt');
//
//            $text = __('Select a Custom Post Type');
//
//            $html = '<select id="cpt_name" name="select_cpt[cpt_name]">';
//            $html .= '<option value="default" class="highlight">' . $text . '</option>';
//
//            // get a list of all public custom post types
//            foreach ($this->get_custom_post_type_names() as $post_type) {
//                // add post types to option value and capitalise first letter
//                $html .= '<option value="' . $post_type . '"' . selected($cpt_option['cpt_name'], $post_type, false) . ' >' . ucfirst($post_type) . '</option>';
//            }
//
//            $html .= '</select>';
//
//            echo $html;
//        }

        /**
         * Get menu names and add to Select Menu
         *
         * @since 1.0.0
         *
         */
        private function settings_field_select_menu() {

            $text = __('Select Menu');

            // get list of menus
            //@TODO-bfp: may need to lowercase results
            $menus = get_terms('nav_menu');

            $html = '<select class="menu_name" name="cpt_auto_menu_settings[menu_name][]">';
            $html .= '<option value="default" class="highlight">' . $text . '</option>';

            // get current option if there is one
            $menu_option = get_option('cpt_auto_menu_settings[menu_name]');

            foreach ($menus as $menu) {

                $html .= '<option value="' . $menu->name . '"' . selected($menu_option, $menu->name, false) . '>' . ucfirst($menu->name) . '</option>';
            }

            $html .= '</select>';
            echo $html;
        }

        /**
         * Get menu item names and add to Select Parent Menu Item
         *
         * @since 1.0.0
         *
         */
        private function settings_field_select_parent_menu_item() {

            $text = __('Select Menu Item');

            $html = '<select class="parent_name" name="cpt_auto_menu_settings[parent_name][]">';
            $html .= '<option value="default" class="highlight">' . $text . '</option>';
            // new select options are pulled in from selected_menu_name.php

            // if options exist in db then show selected option
            if (get_option('cpt_auto_menu_settings[parent_name]') != false) {
                $parent_menu_item = get_option('cpt_auto_menu_settings[parent_name]');

                $menu_items = wp_get_nav_menu_items($this->get_parent_menu_ID(), array('post_status' => 'publish'));

                foreach ($menu_items as $menu_item) {
                    // only display items in the root menu
                    if ($menu_item->menu_item_parent != 0) {
                        continue;
                    }
                    $html .= '<option value="' . $menu_item->title . '"' . selected($parent_menu_item, $menu_item->title, false) . '>' . ucfirst($menu_item->title) . '</option>';
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

           // for each selected display our fields
            foreach ($selected_cpts as $selected_cpt) {

                $form = '<input type="hidden" name="cpt_auto_menu_settings[id][]" value="' . $i++ . '">';
                $form .= '<input type="hidden" name="cpt_auto_menu_settings[cpt][]" value="' . $selected_cpt . '">';

                echo $selected_cpt;
                echo $form;


                echo $this->settings_field_select_menu();
                echo $this->settings_field_select_parent_menu_item();

                echo '<br />';

            }

        }


        /**
         * Callback to merge the fields arrays and sanitize the inputs
         * http://stackoverflow.com/questions/6553752/array-combine-three-or-more-arrays-with-php
         *
         * @since 1.1.0
         *
         * @param $input
         * @return array
         */
        public function menu_settings_validation($input) {

            // testing only
            print_r($input);
            echo '<br />';

            $cpt_array = $input['cpt'];

            print_r($cpt_array);
            echo '<br />';

            $menu_name_array = $input['menu_name'];

            print_r($menu_name_array);
            echo '<br />';

            $parent_name_array = $input['parent_name'];

            print_r($parent_name_array);
            echo '<br />';


            // begin here
            $keys = $input['id'];

            $cpt_array = $input['cpt'];

            $menu_name_array = $input['menu_name'];

            $parent_name_array = $input['parent_name'];

            $output = array();

            foreach ($keys as $id => $key) {
                $output[$key] = array(
                    'cpt' => $cpt_array[$id],
                    'menu_name' => $menu_name_array[$id],
                    'parent_menu' => $parent_name_array[$id]
                );
            }

            foreach( $output as $key => $value) {
                echo $key; // array indexes
                echo $value['cpt'] . '<br />'; // data
                echo $value['menu_name']. '<br />';
                echo $value['parent_menu']. '<br />';
            }


// throw this error for testing
//            $test = $this->run_some_shit();

            return $output;

        }


        /**
         * Add a menu to Settings
         *
         * @since 1.0.0
         * @TODO-bfp: possibly make this part of the Appearence/Menus
         */
        public function add_menu() {

            add_options_page(
                __('CPT Auto Menu Settings'),
                __('CPT Auto Menu'),
                'manage_options',
                'cpt_auto_menu',
                array(&$this, 'plugin_settings_page')
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
            if (!current_user_can('manage_options')) {

                wp_die(__('You do not have sufficient permissions to access this page'));

            }

            // render the settings template
            include(plugin_dir_path(__FILE__) . '/views/settings.php');

        }

        /**
         * Create Nav Menu Items from new Custom Post Type
         *
         * @since 1.0.0
         *
         * @param $post_id
         *
         */
        public function custom_post_type_auto_menu($post_id) {
            // get the current post
            $post = get_post($post_id);

            //  verify post is not a revision or auto-save: http: //tommcfarlin.com/wordpress-save_post-called-twice/
            if (wp_is_post_revision($post->ID) && wp_is_post_autosave($post->ID)) {
                return;
            }

            $itemData = array(
                'menu-item-object-id' => $post->ID,
                'menu-item-parent-id' => $this->get_parent_menu_item_ID(),
                'menu-item-position' => 0,
                'menu-item-object' => $this->get_cpt_name(),
                'menu-item-type' => 'post_type',
                'menu-item-status' => 'publish'
            );

            // Check if menu items exist
            $current_menu_items = wp_get_nav_menu_items($this->get_parent_menu_ID(), array('post_status' => 'publish'));

            // if no menu items exist then exit
            if (!is_array($current_menu_items)) {
                return;
            }

            // if post is in trash delete post
            if (get_post_status($post->ID) == 'trash') {
                wp_delete_post($post->ID);
            }

            // create array for titles
            $current_menu_titles = array();
            // extract list of titles from menu objects and populate array
            foreach ($current_menu_items as $current_menu_item) {
                $current_menu_titles[] = $current_menu_item->title;
            }

            // get title of current project
            $new_project_title = get_the_title($post->ID);


            // make sure title does not exist already and that it is not an auto-draft
            if (!in_array($new_project_title, $current_menu_titles) && (get_post_status($post->ID) != 'auto-draft')) {

                // make sure the selected custom post type matches
                if ($this->get_cpt_name() != get_post_type($post->ID)) {
                    return;
                }

                // finally check that we are not adding a draft to the menu
                // @TODO-bfp: remove menu item if exists for a draft. If user has published, and then makes a draft, menu item is not removed
                if (get_post_status($post->ID) != 'draft') {
                    // if new title then go ahead and add new menu item
                    wp_update_nav_menu_item($this->get_parent_menu_ID(), 0, $itemData);
                }


            }
        }


    }

}

$custom_post_type_auto_menu = Custom_Post_Type_Auto_Menu::get_instance();