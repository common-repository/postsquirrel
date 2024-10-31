<?php

class postsquirrel
{
    protected $api_host = "https://postsquirrel.com/api/";
    protected $api_version = "1.0";
    protected $user_url = "/user";
    protected $networks_url = "/networks";
    protected $fingerprint = "/networks/fingerprint";
    protected $dispatch = "/dispatch";
    protected $check_token = "/token/check";
    protected $token;
    protected $timeout;
    protected $psl_data_tbl;
    protected $psl_networks_tbl;

    public function __construct()
    {
    	global $wpdb;

    	$this->psl_data_tbl = $wpdb->prefix.'psl_data';
    	$this->psl_networks_tbl = $wpdb->prefix.'psl_networks';
    	$this->timeout = 10;

    	$psl_data = $wpdb->get_row("select token, fingerprint from $this->psl_data_tbl");

    	if($psl_data)
	    {
		    $this->token = $psl_data->token;
	    }
    }

    public function connectKey($key)
    {
    	$this->token = $key;

    	$token_data = $this->checkToken();

	    if(!$token_data['valid'])
	    {
	    	return ['valid' => false, 'reason' => "Invalid site key!"];
	    }

	    $fingerprint = $token_data['fingerprint']['networks'];
	    $add_new_key = $this->addNewKey($key,$fingerprint);

	    if(!$add_new_key)
	    {
	    	return ['valid' => false, 'reason' => "Something went wrong. Please try again later!"];
	    }

	    $this->syncNetworks();

	    return ['valid' => true, 'message' => "This site is connected to your postsquirrel account!"];
    }

    public function getNetworksFingerprint()
    {
    	return $this->call($this->api_host.$this->api_version.$this->fingerprint);
    }

    public function syncNetworks()
    {
    	global $wpdb;

    	$response =  $this->call($this->api_host.$this->api_version.$this->networks_url);
	    $psl_data_id = $wpdb->get_var("select id from $this->psl_data_tbl");

        if(is_array($response) && count($response) > 0)
        {
        	$wpdb->query("DELETE FROM $this->psl_networks_tbl");
        	$arr = [];
        	$network_keys = [];

        	foreach($response as $item)
	        {
	        	$arr['name'] = $item['name'];
	        	$arr['image'] = $item['image'];
	        	$arr['network'] = $item['type'];
	        	$arr['network_key'] = $item['id'];
		        $network_keys[] = $item['id'];

		        $wpdb->insert($this->psl_networks_tbl,$arr);
	        }

        	$wpdb->update($this->psl_data_tbl,['direct_post_profiles' => json_encode($network_keys)],['id'=>$psl_data_id]);
        }
        else
        {
        	$wpdb->update($this->psl_data_tbl,['direct_post_profiles'=>json_encode([])],['id'=>$psl_data_id]);
        	$wpdb->query("DELETE from $this->psl_networks_tbl");
        }
    }

    public function dispatchPost($postUrl)
    {
    	global $wpdb;

	    $networks = $wpdb->get_var("select direct_post_profiles from $this->psl_data_tbl");

	    $params['body'] = [
	        'link' => $postUrl,
		    'profiles' => $networks
	    ];

	    return $this->call($this->api_host.$this->api_version.$this->dispatch,$params);
    }

    protected function call($url,$data = [], $type = "get")
    {
    	$data['headers'] = "Authorization: Bearer $this->token";
    	$data['timeout'] = $this->timeout;
    	$data['redirection'] = 0;

    	if($type === "post")
	    {
	    	$response = wp_remote_post($url,$data);
	    }
    	else
	    {
	    	$response = wp_remote_get($url,$data);
	    }

    	$http_info = wp_remote_retrieve_response_code($response);

    	if($http_info === 419)
	    {
	    	$this->removeConnection();
	    }
    	elseif($http_info === 302 || !isset($response['body']) || $http_info === 404)
	    {
	    	return NULL;
	    }

	    return json_decode($response['body'],true);
    }

    protected function checkToken()
    {
	    $data['headers'] = "Authorization: Bearer $this->token";
	    $data['timeout'] = $this->timeout;
	    $data['redirection'] = 0;

    	$response = wp_remote_get($this->api_host.$this->api_version.$this->check_token,$data);

	    $http_info = wp_remote_retrieve_response_code($response);

	    if($http_info === 419)
	    {
		    $this->removeConnection();
	    }
	    elseif($http_info === 302 || !isset($response['body']) || $http_info === 404)
	    {
		    return NULL;
	    }

	    return json_decode($response['body'],true);
    }

    protected function addNewKey($token,$fingerprint)
    {
    	global $wpdb;

    	$new_data = [
    	    'token' => $token,
		    'fingerprint' => $fingerprint
	    ];

    	$wpdb->query("DELETE from $this->psl_data_tbl");

    	$db_query = $wpdb->insert($this->psl_data_tbl,$new_data);

    	return !is_wp_error($db_query);
    }

	protected function removeConnection()
	{
		global $wpdb;
		$wpdb->query("DELETE from $this->psl_networks_tbl");
	}
}