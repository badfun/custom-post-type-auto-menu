<?php
/**
 * The settings page view in Admin
 * http://wp.tutsplus.com/tutorials/theme-development/the-complete-guide-to-the-wordpress-settings-api-part-5-tabbed-navigation-for-your-settings-page/
 * http://wordpress.stackexchange.com/questions/127493/wordpress-settings-api-implementing-tabs-on-custom-menu-page
 *
 * @version 1.1.0
 *
 * @since 1.0.0
 */
?>
<div class="wrap">
    <?php screen_icon(); ?>

    <h2><?php echo __('Custom Post Type Auto Menu'); ?></h2>

    <?php settings_errors(); ?>

    <?php
    $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'cpt_auto_menu_select_cpt';
    ?>

    <h2 class="nav-tab-wrapper">
        <a href="?page=cpt_auto_menu&tab=select_cpt"
           class="nav-tab <?php echo $active_tab == 'select_cpt' ? 'nav-tab-active' : ''; ?>">CPT Settings</a>

        <?php
        // if custom post type has been chosen display menu tab
        if (get_option('cpt_auto_menu_cpt_list')) {
            ?>

            <a href="?page=cpt_auto_menu&tab=select_menu"
               class="nav-tab <?php echo $active_tab == 'select_menu' ? 'nav-tab-active' : ''; ?>">Menu Settings</a>

        <?php } ?>
    </h2>

    <form method="post" action="options.php">

        <?php
        if ($active_tab == 'select_cpt') {
            settings_fields('select_cpt_settings');
            do_settings_sections('select_cpt_settings');
        } else if ($active_tab == 'select_menu') {


            // testing shit ++++++++++++++++++++++++++++++
//            $selected_cpts = get_option('cpt_auto_menu_cpt_list');
//
//            echo '<pre>';
//            print_r($selected_cpts);
//            echo '</pre>';
//
//            echo '<br />';
//            $selected_menus = get_option('cpt_auto_menu_settings');
//
//            echo '<pre>';
//            print_r($selected_menus);
//            echo '</pre>';
//
//            echo '<pre>';
//            echo $this->settings;
//            echo '</pre>';


            // end testing shit ++++++++++++++++++++++++++++++

              settings_fields('select_menu_settings');
              do_settings_sections('select_menu_settings');


        }
        ?>

        <?php @submit_button(); ?>
    </form>
</div>