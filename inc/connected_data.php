<?php

require_once __DIR__.'/../core/postsquirrel.php';

global $wpdb;

$psl_data_tbl = $wpdb->prefix.'psl_data';
$psl_networks_tbl = $wpdb->prefix.'psl_networks';

if(isset($_SESSION['psl_info_message']))
{
    $message = sanitize_text_field($_SESSION['psl_info_message']);
	$message_status = sanitize_text_field($_SESSION['psl_info_type']);

	unset($_SESSION['psl_info_message']);
	unset($_SESSION['psl_info_type']);
}

if(isset($_POST['psl_site_key']))
{
    $site_key = sanitize_text_field($_POST['psl_site_key']);

    if($site_key === "")
    {
        $message = "Enter valid key!";
        $message_status = "error";
    }
    else
    {
        $postsquirrel = new postsquirrel();
        $status = $postsquirrel->connectKey($site_key);

        if($status['valid'])
        {
	        $message = $status['message'];
	        $message_status = "success";
        }
        else
        {
            $message = $status['reason'];
            $message_status = "error";
        }
    }
}

$postsquirrel = new postsquirrel();
$fingerprints = $postsquirrel->getNetworksFingerprint();

$current_fingerprint = $wpdb->get_var("select fingerprint from $psl_data_tbl");

if($fingerprints !== NULL && $fingerprints !== "" && isset($fingerprints['networks']))
{
    if($fingerprints['networks'] !== $current_fingerprint)
    {
        $postsquirrel->syncNetworks();
    }
}

$networks = $wpdb->get_results("select name,image,network,network_key from $psl_networks_tbl",ARRAY_A);
$token = $wpdb->get_var("select token from $psl_data_tbl");

if(isset($message) && isset($message_status))
{
    ?>
    <div class="notice notice-<?php echo esc_html($message_status); ?> is-dismissible">
        <p><?php echo esc_html($message); ?></p>
    </div>
    <?php
}

?>

<div class="psl_content_wrapper wrap">

	<div class="center p20">

		<h3>Site Key</h3>

		<div class="inner_wrapper">
            <form method="post">
                <input type="text" id="psl_site_key" name="psl_site_key" placeholder="Enter your key here.." value="<?php echo $token; ?>">
                <input type="submit" class="button button-primary" value="Connect" id="psl_connect_key">
                <input type="button" class="button button-danger" value="Remove site key" id="psl_disconnect_key">
            </form>
		</div>

	</div>

    <?php if($networks && count($networks) > 0) { ?>

        <div class="psl_networks_wrapper p20">

            <h3>Connected networks</h3>

            <?php
                foreach($networks as $network)
                {
                    ?>
                    <div class="psl_meta_nt_box" title="<?php echo $network['name']; ?>" data-type="<?php echo $network['network']; ?>" data-id="<?php echo $network['network_key']; ?>">
                        <img src="<?php echo $network['image']; ?>" alt="" width="64px" height="64px">
                        <p><?php echo $network['name']; ?></p>
                    </div>
                    <?php
                }
            ?>

        </div>

	<?php } else if($token) { ?>

        <div class="info_meta center">
            Connect your social accounts via postsquirrel to get started!
        </div>

    <?php } else { ?>

        <div class="info_meta center">
            Log in to <a href="https://postsquirrel.com/" target="_blank">postsquirrel.com</a> and login / sign up to connect your site and share your posts directly to connected social media platforms upon publishing your content!
        </div>

    <?php } ?>

</div>


