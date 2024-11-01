<?php
if (empty($wp)) {
	require_once('../../../wp-config.php');
	wp();
}
@session_start();
// Time to authenticate this shiznit in a giantass function
function _ssiConsumerAuth()
{
	global $wpdb;
	include_once(ABSPATH . 'wp-content/plugins/ssi/ssi.php');
	_ssiBeginPearAPI();
	
	$nonce_value = $_SESSION['ssi_nonce_value'];
	$from = $_SESSION['ssi_consumer_from'];
	$login_nick = $_SESSION['ssi_consumer_nick'];
	$login_domain = $_SESSION['ssi_consumer_to'];
	$login_val = $login_nick.'@'.$login_domain;
	$login_val = sanitize_email($login_val);
	
	// Get the token hash
	$consumer_table = $wpdb->prefix . "ssi_consumer_store";
	$sql = 'SELECT token_hash AS val, token_hash_algo AS algo FROM	'
	.$consumer_table.' WHERE login_nick = "'.$wpdb->escape($login_nick).'" AND login_domain = "'.$wpdb->escape($login_domain).'"';
	
	$token_hash = $wpdb->get_row($sql);
	
	// No token? 
	if (!$token_hash) {
		$create_user = true;
	}
	
	$resource = array(
		'name' => 'ticket_provider',
		'input' => array(
			'nonce_value' => $nonce_value,
			'nonce_hash_func' => 'sha-1',
			'token_hash' => $token_hash->val
		)
		
	);
	
	$data = @_ssiRESTTA('ssi', 'http://'.$login_domain.'/restta.xml', $resource);
		
	if(!PEAR::isError($data)) {
		// Get the damn ticket
		require_once 'HTTP/Request.php';
		$req =& new HTTP_Request($data['url']);
		if($data['post']) {
			$req->setMethod(HTTP_REQUEST_METHOD_POST);
			foreach ($data['post'] AS $key=>$val) {
				$req->addPostData($key, $val);
			}
		}
		
		if (!PEAR::isError($req->sendRequest())) {
			$ticket = $req->getResponseBody();
		
			require_once 'XML/Unserializer.php'; 
		
			// Instantiate the serializer
			$unserializer =& new XML_Unserializer(array('parseAttributes' => TRUE));
			
			// Serialize the data structure
			$status = $unserializer->unserialize($ticket);
			
			// Check whether serialization worked
			if (PEAR::isError($status)) {
				$error = 'Error: Could not parse ticket.';
				include(ABSPATH . 'wp-content/plugins/ssi/Templates/ssiConsumerAuthError.php');
				return;
			}
			
			$ticket_data = $unserializer->getUnserializedData();
			
			$error = null;
			
			// Authenticate
			
			// Check Login Code
			switch ($ticket_data['loginCode']) {
			case '200':
				$error = 'CODE 200: Client not validated.';
				break;
			case '201':
				$error = 'Login cancelled.';
				break;
			case '202':
				$error = 'CODE 202: Missing or invalid ticket_gen values.';
				break;
			case '203':
				$error = 'CODE 203: Unsupported hash function.';
				break;
			case '300':
				$error = 'CODE 300: Internal application error.';
				break;
			case '301':
				$error = 'CODE 301: Invalid nonce value provided.';
				break;
			case '302':
				$error = 'CODE 302: TokenHash error.';
				break;
			case '303':
				$error = 'CODE 303: Expecting tokenHash.';
				break;
			case '304':
				$error = 'CODE 304: Invalid domain.';
				break;
			case '100':
			    break;
			default:
				$error = 'And unspecified error has occurred.';
			} 
			
			if ($error) {
				include(ABSPATH . 'wp-content/plugins/ssi/Templates/ssiConsumerAuthError.php');
				return;
			}
			
			
			// Check expiration
			if((int)$ticket_data['expire'] < time()){
				$error = 'Ticket has expired, please try to login again.';
				include(ABSPATH . 'wp-content/plugins/ssi/Templates/ssiConsumerAuthError.php');
				return;
			}
			
			// Check domain
			$local_domain = preg_replace('/^(www\.)(.+)/i', '$2', $_SERVER['HTTP_HOST']);
			if(($ticket_data['consumer'] != $local_domain)){
				$error = 'Invalid ticket domain.';
				include(ABSPATH . 'wp-content/plugins/ssi/Templates/ssiConsumerAuthError.php');
				return;
			}
			
			// Check client
			$client_hash = _ssiHash($ticket_data['clientHash']['hashfunc'],$_SERVER['HTTP_USER_AGENT'].$_SERVER['REMOTE_ADDR']);
			if ($client_hash && $client_hash == $ticket_data['clientHash']['_content']) {
				// PASS
			} else {
				$error = 'ClientHash error, either hash function is not supported or invalid client.';
				include(ABSPATH . 'wp-content/plugins/ssi/Templates/ssiConsumerAuthError.php');
				return;

			}
			
			// Check tokenVal
			if ($token_hash) {
				$generated_token_hash = _ssiHash($token_hash->algo, $ticket_data['tokenVal']);
				if ($generated_token_hash && $generated_token_hash == $token_hash->val) {
					// PASS
				} else {
					$error = 'TokenHash error, either hash function is not supported or tokenVal is invalid.';
					include(ABSPATH . 'wp-content/plugins/ssi/Templates/ssiConsumerAuthError.php');
					return;

				}
			}
			
			if (!$error) {
				
				
				// Cleanup
				foreach($_SESSION AS $key => $val) {
					if (substr($key, 0, 3) == 'ssi') {
						unset($_SESSION[$key]);
					}
				}
				
				// Add data to the session too
				if(preg_match('/^[a-zA-Z0-9\.]+$/i', $ticket_data['payload']['nickname'])){
					$_SESSION['ssi_nickname'] =  $ticket_data['payload']['nickname'];
				}
				if(strlen($ticket_data['payload']['avatarUri'])){
					$_SESSION['ssi_avatar_uri'] =  clean_url($ticket_data['payload']['avatarUri']);
				}
				if(is_email(sanitize_email($ticket_data['payload']['email']))){
					$_SESSION['ssi_email'] =  $ticket_data['payload']['email'];
					setcookie('comment_author_email_' . COOKIEHASH, $ticket_data['payload']['email'], time() + 30000000, COOKIEPATH, COOKIE_DOMAIN);
				}
				if(preg_match('/^[_a-zA-Z]+\/[_a-zA-Z]+$/i', $ticket_data['payload']['timezone'])){
					$_SESSION['ssi_timezone'] =  $ticket_data['payload']['timezone'];
				}
				if(preg_match('/^[_a-zA-Z]{2}$/i', $ticket_data['payload']['country'])){
					$_SESSION['ssi_country'] =  $ticket_data['payload']['country'];
				}
				if(preg_match('/^[_a-zA-Z]{2}$/i', $ticket_data['payload']['language'])){
					$_SESSION['ssi_language'] =  $ticket_data['payload']['language'];
				}
				
				$_SESSION['ssi_login_val'] = $login_val;
				
				// Insert the damn data
				if($create_user) {
					$sql = 'INSERT INTO '.$consumer_table.' (date_last_login, login_nick, login_domain, login_display_nick, login_avatar_uri, token_hash, token_hash_algo) VALUES ("'.
					time().'", "'.$wpdb->escape($login_nick).
					'" , "'.$wpdb->escape($login_domain).
					'" , "'.$wpdb->escape($_SESSION['ssi_nickname']).
					'" , "'.$wpdb->escape($_SESSION['ssi_avatar_uri']).
					'" , "'.$wpdb->escape($ticket_data['tokenHash']['_content']).
					'", "'.$wpdb->escape($ticket_data['tokenHash']['hashfunc']).'" )';
					$wpdb->query($sql);
				} else {
					$sql = 'UPDATE '.$consumer_table.' SET date_last_login = "'.time().'" , 
					token_hash = "'.$wpdb->escape($ticket_data['tokenHash']['_content']).'", token_hash_algo ="'.
					$wpdb->escape($ticket_data['tokenHash']['hashfunc']).'",'. 
					'login_nick = "'.$wpdb->escape($login_nick).'",'.
					'login_domain = "'.$wpdb->escape($login_domain).'",'.
					'login_display_nick = "'.$wpdb->escape($_SESSION['ssi_nickname']).'",'.
					'login_avatar_uri = "'.$wpdb->escape($_SESSION['ssi_avatar_uri']) .'" '.
					'WHERE login_nick = "'.$wpdb->escape($login_nick).'" AND 
					login_domain = "'.$wpdb->escape($login_domain).'" LIMIT 1';
					$wpdb->query($sql);
				}
				setcookie('comment_author_' . COOKIEHASH, $login_nick, time() + 30000000, COOKIEPATH, COOKIE_DOMAIN);
				setcookie('comment_author_url_' . COOKIEHASH, clean_url($login_domain), time() + 30000000, COOKIEPATH, COOKIE_DOMAIN);
				
				$_SESSION['flink_nick'] = $login_nick;
				
				$_SESSION['flink_domain'] = $login_domain;
				
				// Profit!
				wp_redirect(clean_url($from));
			} else {
				include(ABSPATH . 'wp-content/plugins/ssi/Templates/ssiConsumerAuthError.php');
				return;
			}
		
		
		
		
		
		} else {
			$error = 'Error accessing your authentication provider';
			include(ABSPATH . 'wp-content/plugins/ssi/Templates/ssiConsumerAuthError.php');
			return;
		}
		
		
		
	} else {
		// RESTTA error
		$error = 'Error: RESTTA file invalid or missing.';
		include(ABSPATH . 'wp-content/plugins/ssi/Templates/ssiConsumerAuthError.php');
		_ssiEndPearAPI();
		return;
	}
	
	
	
	

}
_ssiConsumerAuth();
?>