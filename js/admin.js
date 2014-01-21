/**
 * Pass Ajax variables from forms
 *
 * Clues: http://stackoverflow.com/questions/16784936/how-to-send-or-assign-jquery-variable-value-to-php-variable
 * http://www.9lessons.info/2010/08/dynamic-dependent-select-box-using.html
 *
 */
jQuery(document).ready(function($) {

    /**
     * Pass the selected CPT's checkbox value through AJAX as an array
     */
     $('input[type="checkbox"]').change(function(){

         // get selected cpt
         //http://stackoverflow.com/questions/18582810/pass-multiple-checkboxes-to-php-via-ajax
         //http://stackoverflow.com/questions/12063243/jquery-multiple-checkbox-get-value-in-var-or-print-in-span
         var selected_cpt = new Array();
         $( 'input[type="checkbox"].cpts_list:checked' ).each( function() {
             selected_cpt.push( $( this ).val() );
         } );

         var valid = false;

         // send ajax request
         $.ajax({
             type: 'POST',

             url: AjaxSelected.ajaxurl,

             data: {
                 action: 'admin_script_ajax',
                 selected_cpt: selected_cpt,
                 ajaxnonce: AjaxSelected.ajaxnonce
             }
         })
             .done(function(html){
                 // add html info here
                 $('#cpts_list').html(html);

                 console.log(selected_cpt);
             })

     })


    /**
     * Get the value of the Menu Name so we can pass it back to the form to populate the Parent Menu Item menu
     */
    //check for change on the categories menu
    $('#menu_name').change(function() {

        //get category value
        var selected_menu = $('select#menu_name option:selected').text();

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
               $('#parent_name').html(html);

               console.log(selected_menu);

            })

    });

});
