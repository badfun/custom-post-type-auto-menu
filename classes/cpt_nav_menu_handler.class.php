<?php
defined( 'ABSPATH' ) or die( 'You do not have the required permissions' );

class Cpt_Nav_Menu_Handler {

	/**
	 * Properties
	 *
	 * @since 1.5.5
	 *
	 * @var
	 */
	protected $current_post_type;
	protected $current_menu_items = null;
	protected $current_post_menu_item;
	protected $current_menu_titles;
	protected $new_post_id;
	protected $new_post;
	protected $CPT;
	protected $cpt_settings;
	protected $parent_menu_ID;
	protected $cpt_settings_array = array();

	/**
	 * Constructor
	 *
	 * @since 1.1.5
	 *
	 * @param $CPT
	 */
	public function __construct( $CPT ) {
		$this->CPT = $CPT;

		add_action( 'transition_post_status', array( $this, 'transition_post_status' ), 10, 3 );
	}

	/**
	 * Get new post
	 *
	 * @since 1.1.5
	 *
	 * @return mixed
	 */
	public function get_new_post() {
		return $this->new_post;
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

		if ( isset( $this->parent_menu_ID ) ) {
			return $this->parent_menu_ID;
		}
		$main_menu = wp_get_nav_menu_object( $this->get_parent_menu_name() );

		// nav menu object returns false if there is no menu
		if ( $main_menu != false ) {
			// then extract the ID
			$this->parent_menu_ID = (int) $main_menu->term_id;

			return $this->parent_menu_ID;
		}
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
		$settings = $this->get_cpt_settings_array();

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

	/**
	 * Get CPT settings array
	 *
	 * @since 1.1.5
	 *
	 * @return array|mixed|void
	 */
	protected function get_cpt_settings_array() {
		if ( ! empty( $this->cpt_settings_array ) ) {
			return $this->cpt_settings_array;
		}

		if ( get_option( 'cpt_auto_menu_settings' ) && ( true == get_option( 'cpt_auto_menu_settings' ) ) ) {

			$this->cpt_settings_array = get_option( 'cpt_auto_menu_settings' );
		}

		return $this->cpt_settings_array;
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
	 * Create new nav menu items from cpt
	 *
	 * @since 1.1.5
	 *
	 * @param $new_status
	 * @param $old_status
	 * @param $post
	 */
	function transition_post_status( $new_status, $old_status, $post ) {

		$this->current_post_type = $post->post_type;
		$this->new_post          = $post;
		$this->new_post_id       = $post->ID;

		if ( ! $this->should_process_post_type() ) {
			return;
		}
		if ( $old_status === $new_status ) {
			return;
		}

		if ( $this->setup_current_menu() === false ) {
			return;
		}


		if ( $old_status == 'publish' && $new_status != 'publish' ) {

			if ( $this->menu_item_already_exist() ) {
				$this->remove_menu_item( $this->get_current_post_menu_item_db_id() );
			}
		}
		if ( $new_status == 'publish' ) {
			if ( $this->menu_item_already_exist() ) {
				return;
			}

			$this->add_new_item_to_menu();
		}
	}

	/**
	 * @since 1.1.5
	 *
	 * @return bool
	 */
	public function should_process_post_type() {

		$args       = $args = array(
			'public'   => true,
			'_builtin' => false
		);
		$post_types = array_keys( get_post_types( $args ) );
		$new_post   = $this->get_new_post();
		if ( in_array( trim( $new_post->post_type ), $post_types ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Add new item to menu
	 *
	 * @since 1.1.5
	 */
	public function add_new_item_to_menu() {
		$itemData  = $this->get_item_data();
		$parent_id = $this->get_parent_menu_ID();
		$this->add_item_to_menu( $parent_id, 0, $itemData );
	}

	/**
	 * Update Nav menu item to add new menu item
	 *
	 * @since 1.1.5
	 *
	 * @param $parent_id
	 * @param $order
	 * @param $itemData
	 */
	public function add_item_to_menu( $parent_id, $order, $itemData ) {

		wp_update_nav_menu_item( $parent_id, $order, $itemData );
	}

	/**
	 * Remove menu item using delete post
	 *
	 * @since 1.1.5
	 *
	 * @param $id
	 */
	public function remove_menu_item( $id ) {
		wp_delete_post( $id );
	}

	/**
	 * Get menu item data
	 *
	 * @since 1.1.5
	 *
	 * @return array
	 */
	public function get_item_data() {
		$post     = $this->get_new_post();
		$itemData = array(
			'menu-item-object-id' => $post->ID,
			'menu-item-parent-id' => $this->get_parent_menu_item_ID(),
			'menu-item-position'  => 0,
			'menu-item-object'    => $this->get_current_cpt(),
			'menu-item-type'      => 'post_type',
			'menu-item-status'    => 'publish'
		);

		return $itemData;
	}

	/**
	 * Get new cpt item title
	 *
	 * @since 1.1.5
	 *
	 * @return mixed
	 */
	public function get_new_item_title() {
		$new_post = $this->get_new_post();

		return $new_post->post_title;
	}

	/**
	 * Check if menu items already exist
	 *
	 * @since 1.1.5
	 *
	 * @return mixed|null
	 */
	public function get_current_menu_items() {
		if ( $this->current_menu_items == null ) {
			$this->current_menu_items = wp_get_nav_menu_items( $this->get_parent_menu_ID(), array( 'post_status' => 'publish' ) );
		}


		return $this->current_menu_items;
	}

	/**
	 * @since 1.1.5
	 *
	 * @return mixed
	 */
	public function get_curent_post_menu_item_id() {
		return $this->current_post_menu_item->ID;
	}

	/**
	 * @since 1.1.5
	 *
	 * @return mixed
	 */
	public function get_current_post_menu_item_db_id() {
		return $this->current_post_menu_item->db_id;
	}

	/**
	 * Create an array for titles and extract list of titles from menu objects to populate array
	 *
	 * @since 1.1.5
	 *
	 * @return bool
	 */
	public function setup_current_menu() {
		$current_titles     = array();
		$post               = $this->get_new_post();
		$current_menu_items = $this->get_current_menu_items();
		if ( $current_menu_items == null ) {
			return false;
		}
		foreach ( $current_menu_items as $current_menu_item ) {
			if ( $current_menu_item->object_id == $post->ID ) {
				$this->current_post_menu_item = $current_menu_item;
			}
			$current_titles[] = $current_menu_item->title;
		}

		$this->current_menu_titles = $current_titles;
	}

	/**
	 * Get current menu titles
	 *
	 * @since 1.1.5
	 *
	 * @return mixed
	 */
	public function get_current_menu_titles() {
		return $this->current_menu_titles;
	}

	/**
	 * Decode entities to prevent problems with html character
	 *
	 * @since 1.1.5
	 *
	 * @param        $text
	 * @param string $type
	 *
	 * @return mixed|string|void
	 */
	public function clean_for_compare( $text, $type = 'title' ) {

		if ( ! apply_filters( 'ctp_auto_override_clean_compare', false, $type ) ) {
			$text = wp_kses_decode_entities( $text );
			$text = trim( $text );
		}

		$text = apply_filters( 'ctp_auto_clean_title', $text, $type );

		return $text;
	}

	/**
	 * Check if menu items exist
	 *
	 * @since 1.1.5
	 *
	 * @return bool
	 */
	public function menu_item_already_exist() {
		$new_title      = $this->get_new_item_title();
		$current_titles = $this->get_current_menu_titles();
		$new_title      = $this->clean_for_compare( $new_title );
		$current_titles = array_map( array( $this, 'clean_for_compare' ), $current_titles );

		return in_array( $new_title, $current_titles );
	}

	/**
	 * Get the Custom Post Type for the current post in a single call
	 *
	 * @since   1.1.0
	 *
	 * @version 1.1.5
	 *
	 * @return bool|string
	 *
	 */
	public function get_current_cpt() {

		return $this->current_post_type;
	}

}
