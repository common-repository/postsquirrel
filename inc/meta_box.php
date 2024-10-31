<?php

require_once __DIR__.'/../core/postsquirrel.php';

global $wpdb;

$networks_tbl = $wpdb->prefix.'psl_networks';
$data_tbl = $wpdb->prefix.'psl_data';

$data_db = $wpdb->get_row("select token, fingerprint, direct_post_profiles from $data_tbl",ARRAY_A);

if(!$data_db)
{
    ?>
    <p class="center">Configure your site key to connect your social networks!</p>
    <?php
	return false;
}

$postsquirrel = new postsquirrel();
$fingerprints = $postsquirrel->getNetworksFingerprint();
$current_fingerprint = $data_db['fingerprint'];

if($fingerprints !== NULL && $fingerprints !== "" && isset($fingerprints['networks']))
{
	if($fingerprints['networks'] !== $current_fingerprint)
	{
		$postsquirrel->syncNetworks();
	}
}

$all_networks = $wpdb->get_results("select name,image,network,network_key from $networks_tbl",ARRAY_A);

if(!$all_networks || count($all_networks) === 0)
{
	?>
    <p class="center">Connect your social accounts via postsquirrel to get started!</p>
	<?php
	return false;
}

$dp_active_profiles = $wpdb->get_var("select direct_post_profiles from $data_tbl");

$active_profiles = [];

if($dp_active_profiles && $dp_active_profiles !== "" && $dp_active_profiles !== NULL)
{
    $active_profiles = json_decode($dp_active_profiles,true);
}

?>

<div class="psl_meta_box_wrapper">

	<div class="psl_meta_network_wrapper">

        <div>
            <input type="checkbox" id="psl_select_all_networks" <?php if(count($all_networks) <= count($active_profiles)) { echo "checked"; } ?>>
            <label for="psl_select_all networks">Select all</label>
        </div>

		<?php
			foreach($all_networks as $network)
			{
				?>
					<div class="psl_meta_nt_box <?php if(in_array($network['network_key'],$active_profiles)) { echo "selected"; } ?>" title="<?php echo $network['name']; ?>" data-type="<?php echo $network['network']; ?>" data-id="<?php echo $network['network_key']; ?>">
						<img src="<?php echo $network['image']; ?>" alt="" width="64px" height="64px">
					</div>
				<?php
			}
		?>

	</div>

</div>
