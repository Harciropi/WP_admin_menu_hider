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
    
    $('.menu_item.has_submenu').on('change', function(){
        if ($(this).find(':input:checked').length>0)
        {
            $(this).next('.submenu_block').find(':input:not(:checked)').prop('checked',true);
        }
        else
        {
            $(this).next('.submenu_block').find(':input:checked').prop('checked',false);
        }
    });
    
    $('.submenu_item > input').on('change', function(){
        let parent = $(this).parents('.submenu_block');
        let chk_chkbox = parent.find(':input:checked').length;
        let all_chkbox = parent.find(':input').length;
        if (chk_chkbox == all_chkbox)
        {
            parent.prev('.has_submenu').children(':input').prop('checked',true);
        }
        else
        {
            parent.prev('.has_submenu').children(':input').prop('checked',false);
        }
    });
}
