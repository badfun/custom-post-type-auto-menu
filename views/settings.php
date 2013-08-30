<?php
/**
 * The settings page view in Admin
 * http://www.yaconiello.com/blog/how-to-handle-wordpress-settings
 */
?>
<div class="wrap">
    <?php screen_icon(); ?>

    <h2><?php echo __('Custom Post Type Auto Menu'); ?></h2>

    <form method="post" action="options.php">

        <?php @settings_fields('cpt_auto_menu-group'); ?>

        <?php do_settings_sections('cpt_auto_menu'); ?>

        <?php @submit_button(); ?>
    </form>
</div>