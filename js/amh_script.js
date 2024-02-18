/**
 * js/juery script
 * 
 * @version 2024.02.17.
 * @package admin_menu_hider
 * @author Soós András
 */

var $ = jQuery;
$(document).ready(function() {
    amh_init();
});

function amh_init()
{
    $('.menu_item.has_submenu > .arrow_toggle').on('click', function(){
        $(this).parent().toggleClass('opened');
    });
    
    $('.menu_item.has_submenu').on('change', function(e){
        if ($(this).find(':input:checked').length>0)
        {
            $(this).next('.submenu_block').find(':input:not(:checked)').prop('checked',true);
        }
        else
        {
            $(this).next('.submenu_block').find(':input:checked').prop('checked',false);
        }
    });
}
