/**
 * Pass Ajax variables from Menu Settings page
 * @version 1.1.0
 *
 * @since 1.0.0
 *
 * Clues: http://stackoverflow.com/questions/16784936/how-to-send-or-assign-jquery-variable-value-to-php-variable
 * http://www.9lessons.info/2010/08/dynamic-dependent-select-box-using.html
 *
 * @TODO-bfp: selected_menu value is being passed for parent menu item too. Generates an Ajax warning but works.
 */
jQuery(document).ready(function($) {

    /*
     * Get the value of the Menu Name so we can pass it back to the form to populate the Parent Menu Item menu
     * http://stackoverflow.com/questions/12750307/jquery-select-change-event-get-selected-option
     * http://stackoverflow.com/questions/1409918/jquery-get-immediate-next-element-after-the-current-element
     */

    //check for change on the select menu (we use change not click so sibling has no effect
        $('select').on('change', function (e){

        var optionSelected = $("option:selected", this);
        var selected_menu = this.value;

        // send ajax request
        $.ajax({
            type: 'POST',

            url: AjaxSelected.ajaxurl ,

            data: {
                action: 'admin_script_ajax',
                selected_menu: selected_menu,
                ajaxnonce: AjaxSelected.ajaxnonce
            }
        })
            .done(function(html) {
                // add our html info here
               $(e.target).next().html(html);

               console.log(selected_menu);

            })

    });

});
