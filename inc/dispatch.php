<?php

global $wpdb;

require_once __DIR__ . '/../../postsquirrel/core/postsquirrel.php';

$postsquirrel = new Postsquirrel();

$data_tbl = $wpdb->prefix.'psl_data';

$selected_networks = $wpdb->get_var("select direct_post_profiles from $data_tbl");

if(!$selected_networks || $selected_networks === "" || !isset($post_id))
{
	return false;
}

$post_data = get_post($post_id);
$post_guid = $post_data->guid;
$postsquirrel->dispatchPost($post_guid);

