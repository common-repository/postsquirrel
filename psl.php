<?php
/*
   Plugin Name: Postsquirrel
   Plugin URI: https://postsquirrel.com
   description: Directly share to connected social media networks upon publishing posts. An extension of your postsquirrel account
   Version: 1.0
   Author: Postsquirrel
   License: GPL2
*/

register_activation_hook( __FILE__, 'psql_activate' );

function psql_activate()
{
	global $wpdb;

	$psl_table = $wpdb->prefix . 'psl_data';
	$psl_networks = $wpdb->prefix.'psl_networks';

	$query = 'create table if not exists ' . $psl_table . '(
            id int NOT NULL AUTO_INCREMENT,
            token varchar(255) NOT NULL,
            fingerprint varchar(255) NOT NULL,
            direct_post_profiles MEDIUMTEXT NOT NULL,
            PRIMARY KEY(id)
  	      );';

	$wpdb->query( $query );

	$query = 'create table if not exists ' . $psl_networks . '(
            id int NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            image varchar(255) NOT NULL,
            network varchar(255) NOT NULL,
            network_key varchar(255) NOT NULL,
            PRIMARY KEY(id)
  	      );';

	$wpdb->query( $query );
}

function psql_uninstall()
{
	global $wpdb;

	$psl_table = $wpdb->prefix . 'psl_data';
	$psl_networks = $wpdb->prefix.'psl_networks';

	$wpdb->query( "drop table '" . $psl_table . "' " );
	$wpdb->query( "drop table '" . $psl_networks . "' " );
}

register_uninstall_hook( __FILE__, 'psql_uninstall' );

function register_psql_menu()
{
	add_menu_page(
		'Postsquirrel',
		'Postsquirrel',
		'manage_options',
		'postsquirrel',
		'postsquirrel_init',
		plugins_url( 'postsquirrel/assets/menu.png' ),
		80
	);
}

add_action( 'admin_menu', 'register_psql_menu' );

function postsquirrel_init()
{
    require_once __DIR__ . '/inc/connected_data.php';
}

add_action( 'admin_enqueue_scripts', 'init_psql_scripts' );

function init_psql_scripts()
{
	wp_enqueue_style( 'psql_admin_style', plugins_url( 'postsquirrel/assets/css/psql_style.css' ),[],1.0 );
	wp_register_script( 'psql_admin_script', plugins_url( 'postsquirrel/assets/js/psql_script.js' ),[],1.0 );

	$obj_arr = array(
		'ajaxurl'  => admin_url( 'admin-ajax.php' ),
		'site_url' => site_url()
	);

	wp_localize_script( 'psql_admin_script', 'obj', $obj_arr );

	wp_enqueue_script( 'psql_admin_script' );
}

function psql_direct_post_section()
{
	include __DIR__ . '/inc/meta_box.php';
}

function init_psql_posts_scripts()
{
	add_meta_box(
		'postsquirrel_selectable_box',
		'Postsquirrel',
		'psql_direct_post_section',
		"post",
		'advanced',
		'high'
	);
}

function psql_dispatch_new_post($post_id)
{
	include __DIR__ . '/inc/dispatch.php';
}

function sync_psql_dp_profiles()
{
	global $wpdb;

    $psl_data = $wpdb->prefix.'psl_data';

    $e_data = $wpdb->get_row("select id from $psl_data");

    if(!$e_data)
    {
        return false;
    }

    $selected_profiles = NULL;
    if(isset($_POST['profiles']) && count($_POST['profiles']) > 0)
    {
	    $selected_profiles = array_map( 'sanitize_text_field', $_POST['profiles'] );
	    $selected_profiles = json_encode($selected_profiles);
    }

    $wpdb->update($psl_data,['direct_post_profiles'=>$selected_profiles],['id'=>$e_data->id]);
    exit;
}

function disconnect_psql_key()
{
	global $wpdb;

    $psl_data = $wpdb->prefix.'psl_data';
    $psl_networks = $wpdb->prefix.'psl_networks';

    $db_data_response = $wpdb->query("DELETE from $psl_data");
    $db_networks_response = $wpdb->query("DELETE from $psl_networks");

    if(is_wp_error($db_data_response) || is_wp_error($db_networks_response))
    {
	    $_SESSION['psl_info_message'] = "Something went wrong, please try again later!";
	    $_SESSION['psl_info_type'] = "error";
    }
    else
    {
	    $_SESSION['psl_info_message'] = "This site is disconnected from your postsquirrel account!";
	    $_SESSION['psl_info_type'] = "success";
    }

	exit;
}

function init_psql_func()
{
	if (session_status() !== PHP_SESSION_ACTIVE) {
		session_start();
	}
}

add_action('init','init_psql_func');

if ( is_admin() )
{
	add_action( 'load-post-new.php', 'init_psql_posts_scripts' );
	add_action("wp_ajax_sync_psql_dp_profiles","sync_psql_dp_profiles");
	add_action("wp_ajax_disconnect_psql_key","disconnect_psql_key");
	add_action('publish_post', 'psql_dispatch_new_post');
}
