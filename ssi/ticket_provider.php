<?php
if (empty($wp)) {
	require_once('../../../wp-config.php');
	wp();
}

// given the data provided, generated the ticket
function _ssiGenerateTicket()
{
	global $wpdb;
	
	include_once(ABSPATH . 'wp-content/plugins/ssi/ssi.php');
	
	if(isset($_GET['nonce_value'])) {
		$nonce_value = $_GET['nonce_value'];
	} else {
		$login_code = '301';
		include(ABSPATH . 'wp-content/plugins/ssi/Templates/ssiTicketProviderError.php');
		return;
	}
	
	if(isset($_GET['nonce_hash_func'])) {
		$hash_func = $_GET['nonce_hash_func'];
	} else {
		$login_code = '301';
		include(ABSPATH . 'wp-content/plugins/ssi/Templates/ssiTicketProviderError.php');
		return;
	}
	
	// Get the data
	$nonce_hash = _ssiHash($hash_func, $nonce_value);
	
	if(!$nonce_hash) {
		$login_code = '203';
		include(ABSPATH . 'wp-content/plugins/ssi/Templates/ssiTicketProviderError.php');
		return;
	}
	
	$provider_user_table = $wpdb->prefix . "ssi_provider_user_store";
	$sql = 'SELECT * FROM '.$provider_user_table.' WHERE  current_nonce_hash = "'.$nonce_hash.'"';
	
	$user_data = $wpdb->get_row($sql);
	
	if (!$user_data || ($nonce_hash != $user_data->current_nonce_hash)) {
		$login_code = '301';
		include(ABSPATH . 'wp-content/plugins/ssi/Templates/ssiTicketProviderError.php');
		return;
	
	}
	
	if($user_data->current_login_code != '100') {
		$login_code = $user_data->current_login_code;
		include(ABSPATH . 'wp-content/plugins/ssi/Templates/ssiTicketProviderError.php');
		return;
	}
	
	//if(gethostbyname($user_data->current_domain) != $_SERVER['REMOTE_ADDR']) {
	//	$login_code = '304';
	//	include(ABSPATH . 'wp-content/plugins/ssi/Templates/ssiTicketProviderError.php');
	//	return;
	//}
	
	// Token handling
	$provider_token_table = $wpdb->prefix . "ssi_provider_token_store";
	$sql = 'SELECT * FROM '.$provider_token_table.' WHERE user_id = "'.$user_data->id.'" AND domain = "'.$user_data->current_domain.'"';
	
	$token_data = $wpdb->get_results($sql);
	
	
	
	// If there is a token_hash val, keep it and validate it, generate a new pair, and delete anything that isn't the 
	// the selected pair 
	
	if(isset($_GET['token_hash']) && strlen($_GET['token_hash'])) {
		$token_valid = false;
		foreach($token_data AS $token) {
			if($token->token_hash == $_GET['token_hash']) {
				$token_valid = true;
				$token_ignore_id = $token->id;
				$token_val = $token->token_value;
			}
		}
		if (!$token_valid) {
			$login_code = '302';
			include(ABSPATH . 'wp-content/plugins/ssi/Templates/ssiTicketProviderError.php');
			return;
		}
	} else if ($token_data && !isset($_GET['token_hash'])) {
		$login_code = '303';
		include(ABSPATH . 'wp-content/plugins/ssi/Templates/ssiTicketProviderError.php');
		return;
	
	} 
	
	// New pair
	$new_token_val = wp_create_nonce();
	$new_token_hash = _ssiHash('sha-1', $new_token_val);
	$new_token_hash_algo = 'sha-1';
	
	// Delete the old tokens
	if($token_ignore_id) {
		$sql = 'DELETE FROM '.$provider_token_table.' WHERE domain = "'.$user_data->current_domain.'" AND id <> "'.$token_ignore_id.'"';
		$wpdb->query($sql);
	}
	
	
	// Store the new pair
	$sql = 'INSERT INTO '.$provider_token_table.' (date_created, user_id, token_value, token_hash, hash_algo, domain) 
	VALUES ("'.time().'","'.$user_data->id.'","'.$new_token_val.'","'.$new_token_hash.'","'.$new_token_hash_algo.'","'.$wpdb->escape($user_data->current_domain).'")';
	$wpdb->query($sql);
	
	
	$ticket_expire = time() + 120;
	
	// Make the ticket
	include(ABSPATH . 'wp-content/plugins/ssi/Templates/ssiTicketProvider.php');
}
 _ssiGenerateTicket();
 

 
?>