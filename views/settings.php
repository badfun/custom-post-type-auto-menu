<?php
/**
 * The settings page view in Admin
 * @link http://wp.tutsplus.com/tutorials/theme-development/the-complete-guide-to-the-wordpress-settings-api-part-5-tabbed-navigation-for-your-settings-page/
 * @link http://wordpress.stackexchange.com/questions/127493/wordpress-settings-api-implementing-tabs-on-custom-menu-page
 *
 * @version 1.1.3
 *
 * @since   1.0.0
 *
 * @TODO-bfp: add styling to this page
 */
?>
<div class="cpt-auto-menu-wrapper">
	<?php screen_icon(); ?>

	<h2><?php esc_html_e( 'Custom Post Type Auto Menu', 'bfp-cpt-auto-menu' ); ?></h2>

	<?php settings_errors(); ?>

	<?php
	$active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'cpt_auto_menu_select_cpt';
	?>

	<h2 class="nav-tab-wrapper">
		<a href="?page=cpt_auto_menu&tab=select_cpt"
		   class="nav-tab <?php echo $active_tab == 'select_cpt' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'CPT Settings', 'bfp-cpt-auto-menu' ); ?></a>

		<?php
		// if custom post type has been chosen display menu tab
		if ( $this->get_selected_cpts() ) {
			?>

			<a href="?page=cpt_auto_menu&tab=select_menu"
			   class="nav-tab <?php echo $active_tab == 'select_menu' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Menu Settings', 'bfp-cpt-auto-menu' ); ?></a>

		<?php } ?>
	</h2>

	<form method="post" action="options.php">

		<?php
		if ( $active_tab == 'select_cpt' ) {
			settings_fields( 'select_cpt_settings' );
			do_settings_sections( 'select_cpt_settings' );
		} else if ( $active_tab == 'select_menu' ) {

			settings_fields( 'select_menu_settings' );
			do_settings_sections( 'select_menu_settings' );

		}
		?>

		<?php @submit_button( __( 'Save Settings', 'bfp-cpt-auto-menu' ) ); ?>
	</form>

	<br />
	<br />
	<br />
	<br />

	<hr />
	<p><a target="_blank" href="http://wordpress.org/plugins/custom-post-type-auto-menu/">
			<?php esc_html_e( 'Custom Post Type Auto Menu', 'bfp-cpt-auto-menu' ); ?></a>
		<?php esc_html_e( 'version', 'bfp-cpt-auto-menu' );
		echo ' ' . $this->version; ?>
		<?php esc_html_e( 'by', 'bfp-cpt-auto-menu' ); ?>
		<a href="http://badfunproductions.com" target="_blank">Bad Fun Productions</a> -
		<a href="https://github.com/badfun/custom-post-type-auto-menu"
		   target="_blank"><?php esc_html_e( 'Please Report Bugs', 'bfp-cpt-auto-menu' ); ?></a> &middot;
		<?php esc_html_e( 'Follow on Twitter:', 'bfp-cpt-auto-menu' ); ?>
		<a href="http://twitter.com/badfunpro" target="_blank">BadFun Productions</a></p>

</div>