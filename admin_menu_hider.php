<?php

/**
 * Plugin Name: Admin menu hider
 * Plugin URI: https://github.com/Harciropi/WP_admin_menu_hider
 * Description: This plugin hides selected admin menu or sub-menu items from other users whose you want.
 * Version: 1.2
 * Release Date: 2024.02.20.
 * Since: 2024.02.17.
 * Author: Soós András
 * Author URI: https://linkedin.com/in/soosandras-harciropi
 * License: GPLv2
 */

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

require_once plugin_dir_path( __FILE__ ) . '/util.php';

function user_id()
{
    $user_id = 0;
    
    $user = wp_get_current_user();
    if (!empty($user) && !empty($user->ID))
    {
        $user_id = base64_encode('userid|' . $user->ID);
    }
    
    return $user_id;
}

function users_datas()
{
    $users_datas = array();
    
    $args = wp_parse_args(array(), array('fields' => 'all_with_meta'));
    if (!empty($args))
    {
        $users = get_users($args);
        if (!empty($users))
        {
            foreach ($users as $user) {
                $users_datas[$user->ID] = $user->display_name;
            }
        }
    }
    
    return $users_datas;
}

function users_datas_base64($users_datas = array())
{
    $users_datas_base64 = array();
    
    if (empty($users_datas))
    {
        $users_datas = users_datas();
    }
    
    if (!empty($users_datas))
    {
        foreach (array_keys($users_datas) as $k)
        {
            $key = base64_encode('userid|' . $k);
            $users_datas_base64[$key] = 0;
        }
    }
    
    return $users_datas_base64;
}

function menu_datas()
{
    $menu_datas = array();
    
    if (!empty($GLOBALS['menu']))
    {
        $preg_replace_pattern = '/<[^>]*>/';
        $submenus = $GLOBALS['submenu'];
        foreach ($GLOBALS['menu'] as $main_menu)
        {
            if (!empty($main_menu[2]))
            {
                $name = trim(preg_replace($preg_replace_pattern, '', (!empty($main_menu[0]) ? $main_menu[0] : $main_menu[2])));
                if (!empty($name))
                {
                    $menu_datas[$main_menu[2]] = array(
                        'name' => $name,
                        'submenu' => array(),
                    );

                    if (!empty($submenus[$main_menu[2]]) && count($submenus[$main_menu[2]]) > 1)
                    {
                        foreach ($submenus[$main_menu[2]] as $submenu)
                        {
                            if (!empty($submenu[2]) && !empty($submenu[0]))
                            {
                                $subname = trim(preg_replace($preg_replace_pattern, '', $submenu[0]));
                                if (!empty($subname))
                                {
                                    $menu_datas[$main_menu[2]]['submenu'][$submenu[2]] = $subname;
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    
    return $menu_datas;
}

function admin_menu_hider_init() {
    $plugin_datas = get_option('admin_menu_hider_settings_options');
    
    $user_id = user_id();
    if (empty($plugin_datas[$user_id]))
    {
        hide_menu_items($plugin_datas);
    }
}
add_action('admin_init', 'admin_menu_hider_init');
    
function add_admin_menu_item() {
    add_menu_page(
        'Admin menu hider settings', // Page title
        'Menu hider', // Menu title
        'manage_options', // Capability required
        'admin-menu-hider-settings', // Menu slug
        'admin_menu_hider_settings_page', // Callback function for the settings page
        'dashicons-visibility', // Icon for the menu item
        null // Menu position (null means the end)
    );
}
add_action('admin_menu', 'add_admin_menu_item');

function add_plugin_links($links = array())
{
    $links[] = '<a href="' . admin_url('admin.php?page=admin-menu-hider-settings') . '">Settings</a>';
    
    return $links;
}
add_filter('plugin_action_links_'.plugin_basename(__FILE__), 'add_plugin_links');

function add_script_n_style() {
    wp_enqueue_script('admin-scripts', plugin_dir_url( __FILE__ ) . 'js/amh_script.js', array(), '1.0.0', array('in_footer'=>true));
    wp_enqueue_style('admin-styles', plugin_dir_url( __FILE__ ) . 'css/amh_style.css', array(), '1.0.0');
}
add_action('admin_enqueue_scripts', 'add_script_n_style');

function admin_menu_hider_settings_page() {
    if (!current_user_can( 'manage_options' ) ) {
        wp_die('You do not have sufficient permissions to access this page!');
    }
    
    $users_datas = users_datas();
    $menu_datas = menu_datas();
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_REQUEST['_wpnonce']) && !empty($_POST['submit']))
    {
        if (!empty(wp_verify_nonce($_REQUEST['_wpnonce'],'amh-setopt')))
        {
            $option_update = array();
            foreach ($_POST as $field => $value)
            {
                if ($field != 'submit')
                {
                    $option_update[$field] = $value;
                }
            }
            $success = update_option('admin_menu_hider_settings_options', $option_update);
        
            if ($success === true)
            {
                /** <TODO> Admin notices. */
            }
        }
    }
    $option_update = get_option('admin_menu_hider_settings_options');
    
    $create_nonce = wp_create_nonce('amh-setopt');
    $get_url = home_url(add_query_arg(null,null));
    $action_url = $get_url . (stristr($get_url,'?') ? '&' : '?') . '_wpnonce=' . $create_nonce;
    $return = '<form action="' . $action_url . '" method="post"><div id="admin_menu_hider_by_soos_andras_xd" class="wrap">';
        $return.= '<div class="title_line" >ADMIN MENU HIDER<br>SETTINGS</div>';
        $return.= '<div class="content_block" >';
        if (!empty($menu_datas))
        {
            $return.= '<div class="block_side block_left_side">';
                $return.= '<div class="side_title">Admin menu items that will not be seen</div>';
                foreach ($menu_datas as $menu_id => $menu_data)
                {
                    $menu_id = base64_encode($menu_id);
                    $chk = !empty($option_update[$menu_id]) ? ' checked="true"' : '';
                    $has_submenu = !empty($menu_data['submenu']) ? ' has_submenu' : '';
                    
                    $return.= '<div class="item_line menu_item' . $has_submenu . '"><div class="arrow_toggle"></div>';
                        $return.= '<input type="checkbox" id="' . $menu_id . '" name="' . $menu_id . '" value="1"' . $chk . ' />';
                        $return.= '<label for="' . $menu_id . '">' . $menu_data['name'] . '</label>';
                    $return.= '</div>'; //user_item
                    
                    if (!empty($has_submenu))
                    {
                        $return.= '<div class="submenu_block">';
                            foreach ($menu_data['submenu'] as $submenu_id => $submenu_name)
                            {
                                $full_id = base64_encode($menu_id . '|' . $submenu_id);
                                $subchk = !empty($option_update[$full_id]) ? ' checked="true"' : '';

                                $return.= '<div class="item_line submenu_item">';
                                    $return.= '<input type="checkbox" id="' . $full_id . '" name="' . $full_id . '" value="1"' . $subchk . ' />';
                                    $return.= '<label for="' . $full_id . '">' . $submenu_name . '</label>';
                                $return.= '</div>'; //submenu_item
                            }
                        $return.= '</div>'; //submenu_block
                    }
                }
            $return.= '</div>'; //block_left_side
        }
        
        if (!empty($users_datas))
        {
            $myself = user_id();
            $return.= '<div class="block_side block_right_side">';
                $return.= '<div class="side_title">Users who will be able to see all menu items</div>';
                foreach ($users_datas as $user_id => $user_name)
                {
                    $user_id = base64_encode('userid|' . $user_id);
                    $user_chk = !empty($option_update[$user_id]) ? ' checked="true"' : '';
                    $disabled = '';
                    
                    if ($myself == $user_id)
                    {
                        $user_chk = ' checked="true"';
                        $disabled = ' disabled';
                    }
                    
                    $return.= '<div class="item_line user_item' . $disabled . '">';
                        $return.= '<input type="checkbox" id="' . $user_id . '" name="' . $user_id . '" value="1"' . $user_chk . ' />';
                        $return.= '<label for="' . $user_id . '">' . $user_name . (!empty($disabled) ? " <i style='color:#AAAAAA'>- You can't exclude yourself!</i>" : '') . '</label>';
                    $return.= '</div>'; //user_item
                }
            $return.= '</div>'; //block_right_side
        }
        $return.= '</div>'; //content_block
    $return.= '</div>'; //admin_menu_hider_by_soos_andras_xd
    
    echo wp_kses($return,array(
        'form' => array('action'=>array(),'method'=>array()),
        'div' => array('id'=>array(),'class'=>array()),
        'input' => array('type'=>array(),'id'=>array(),'name'=>array(),'value'=>array(),'checked'=>array()),
        'label' => array('for'=>array()),
        'i' => array('style'=>array()),
    ));
    do_settings_sections('admin-menu-hider-settings');
    submit_button();
    echo wp_kses('</form>',array('form'));
    
    $text = "I'm always sleepy... :)";
    $donation = '<div id="buy_me_a_coffee_donate"><a href="' . esc_url('https://www.buymeacoffee.com/harciropi') . '" target="_blank"><img src="https://img.buymeacoffee.com/button-api/?text=' . $text . '&emoji=☕&slug=harciropi&button_colour=5F7FFF&font_colour=ffffff&font_family=Bree&outline_colour=000000&coffee_colour=FFDD00" /></a></div>';
    $donation.= '<div id="linkedin_profile"><a href="' . esc_url('https://linkedin.com/in/soosandras-harciropi') . '"><img src="' . plugin_dir_url( __FILE__ ) . 'src\li.svg' . '" /><span>Soós<br>András</span></a></div>';
    echo wp_kses_post($donation);
    
    add_filter('admin_footer_text', 'add_footer_content');
}

function add_footer_content() {
    $footer_content = '<div class="foot_of_the_sheet">';
        $footer_content.= '<div class="thigh">If you invited me for a coffee, you are the best face in the world! :) Thank YOU!</div>';
        $footer_content.= '<div class="calf">Technical connection, bug report or any other wish: <a href="' . esc_url('mailto:mail@soosandras.hu') . '">mail@soosandras.hu</a></div>';
    $footer_content.= '</div>'; //foot_of_the_sheet
    
    echo wp_kses_post($footer_content);
}

function hide_menu_items(&$plugin_datas)
{
    if (!empty($plugin_datas))
    {
        $chk = ''; // Check for success.
        $users_datas = users_datas_base64();
        foreach (array_keys($plugin_datas) as $page_datas)
        {
            if (!isset($users_datas[$page_datas]))
            {
                $page_ids = explode('|',base64_decode($page_datas));
                if (!empty($page_ids[1]))
                {
                    $page_ids[0] = base64_decode($page_ids[0]);
                    if ($chk == $page_ids[0]) // If removing the main menu was unsuccessful, try the sub-menu IDs. Maybe that's what it changed to.
                    {
                        $chk = remove_menu_page($page_ids[1]);
                        if ($chk === false)
                        {
                            $chk = $page_ids[0];
                        }
                    }
                    else
                    {
                        remove_submenu_page($page_ids[0],$page_ids[1]);
                    }
                }
                else
                {
                    $chk = remove_menu_page($page_ids[0]);
                    if ($chk === false)
                    {
                        $chk = $page_ids[0];
                    }
                }
            }
        }
    }
}
