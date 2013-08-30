/**
 * Get the value of the Menu Name so we can pass it back to the form to populate the Parent Menu Item menu
 *
 * Clues: http://stackoverflow.com/questions/16784936/how-to-send-or-assign-jquery-variable-value-to-php-variable
 * http://www.9lessons.info/2010/08/dynamic-dependent-select-box-using.html
 *
 */
jQuery(document).ready(function($) {

    //check for change on the categories menu
    $('#menu_name').change(function() {

        //get category value
        var selected_menu = $('select#menu_name option:selected').text();

        // send ajax request
        $.ajax({
            type: 'POST',

            url: SelectedMenu.ajaxurl ,

            data: {
                action: 'admin_script_ajax',
                selected_menu: selected_menu,
                ajaxnonce: SelectedMenu.ajaxnonce
            }
        })
            .done(function(html) {
                // add our html info here
               $('#parent_name').html(html);

               // console.log(selected_menu);

            })

    });

});
