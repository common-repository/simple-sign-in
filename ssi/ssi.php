<?php
/*
Plugin Name: Simple Sign In
Plugin URI: http://peopletab.com/ssi.html
Description: Authentication alternative to OpenID
Version: 1.0
Author: Ian Szewczyk
Author URI: http://peopletab.com
*/

/*  Copyright 2008  Ian Szewczyk  (email : roamzero@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

@session_start();
$ssi_db_version = "1.0";
$ssi_pear_api = false;
$ssi_author = null;
$ssi_author_url = null;

function ssiInstall () {
   global $wpdb, $ssi_db_version;
 
   $consumer_table = $wpdb->prefix . "ssi_consumer_store";
   if($wpdb->get_var("show tables like '$consumer_table'") != $consumer_table) {
      add_option("ss_db_version", "1.0");
	  $provider_token_table = $wpdb->prefix . "ssi_provider_token_store";
	  $provider_user_table = $wpdb->prefix . "ssi_provider_user_store";
      $sql = "CREATE TABLE " .  $consumer_table . " (
	  id mediumint(9) NOT NULL AUTO_INCREMENT,
	  date_last_login int NOT NULL,
	  login_nick VARCHAR(255) NOT NULL,
	  login_domain VARCHAR(255) NOT NULL,
	  login_avatar_uri VARCHAR(255),
	  login_display_nick VARCHAR(255),
	  token_hash text NOT NULL,
	  token_hash_algo VARCHAR(255) NOT NULL,
	  UNIQUE KEY id (id));
	  
	  CREATE TABLE " .  $provider_token_table . " (
	  id mediumint(9) NOT NULL AUTO_INCREMENT,
	  user_id mediumint(9) NOT NULL,
	  date_created int NOT NULL,
	  token_value text NOT NULL,
	  token_hash text NOT NULL,
	  hash_algo VARCHAR(255) NOT NULL,
	  domain VARCHAR(255) NOT NULL,
	  UNIQUE KEY id (id));
	  
	  CREATE TABLE " .  $provider_user_table . " (
	  id mediumint(9) NOT NULL AUTO_INCREMENT,
	  login_nick text NOT NULL,
	  display_nick VARCHAR(255),
	  avatar_uri VARCHAR(255),
	  email VARCHAR(255),
	  language VARCHAR(255),
	  country VARCHAR(255),
	  timezone VARCHAR(255),
	  current_login_code VARCHAR(3),
	  current_nonce_hash VARCHAR(255),
	  current_client_hash VARCHAR(255),
	  current_client_hash_algo VARCHAR(255),
	  current_domain VARCHAR(255),
	  UNIQUE KEY id (id));
	  ";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
	   
	update_option("ssi_db_version", $ssi_db_version);
	 

   }
   
}
register_activation_hook(__FILE__, 'ssiInstall');

// DO NOT SHOW THE ADMIN LOGIN IF THE USER IS NOT LOGGED IN FOR SSI (PHISHING = BAD)
if (isset($_GET['page']) && $_GET['page'] == 'ssi-confirm' && !function_exists('auth_redirect')) {
	function auth_redirect() {
		global $wpdb;
		// Checks if a user is logged in, if not redirects them to the login page
		if ( (!empty($_COOKIE[USER_COOKIE]) &&
				//	!wp_login($_COOKIE[USER_COOKIE], $_COOKIE[PASS_COOKIE], true)) ||
				// (empty($_COOKIE[USER_COOKIE])) ) {
				// WP 2.5				
				!wp_validate_auth_cookie($_COOKIE[AUTH_COOKIE])) ||
				(empty($_COOKIE[AUTH_COOKIE])) ) {

		
			if(isset($_GET['from_uri']) && isset($_GET['nonce_hash']) && isset($_GET['login_nick'])) {
				$provider_user_table = $wpdb->prefix . "ssi_provider_user_store";
				$sql = 'SELECT id FROM '.$provider_user_table.' WHERE login_nick ="'.$wpdb->escape($_GET['login_nick']).'" LIMIT 1';
				$provider_user_id = $wpdb->get_var($sql);
				if (preg_match('/^[0-9]+$/', $provider_user_id)) {
					_ssiProcessConfirmation(urldecode($_GET['from_uri']), $_GET['nonce_hash'], $provider_user_id, '200');
				} else {
					include(ABSPATH . 'wp-content/plugins/ssi/Templates/ssiAdminClientAuthError.php');
					return false;
				}
			} else {
				include(ABSPATH . 'wp-content/plugins/ssi/Templates/ssiAdminClientAuthError.php');
				return false;
			}
			
	   }
   
   }
}



function ssiAdminMenus() {
	
    add_menu_page('Simple Sign In', 'SSI', 8, __FILE__, '_ssiAdminIndexPage');

	if (isset($_GET['page']) && $_GET['page'] == 'ssi-confirm') {
		
		if((isset($_POST['confirm_submit']) || isset($_POST['confirm_submit_cancel'])) && isset($_POST['ssi_confirm_nonce']) ) {
			if(isset($_POST['from_uri']) && isset($_POST['nonce_hash']) && isset($_POST['provider_user_id'])  
			   && ($_POST['ssi_confirm_nonce'] == $_SESSION['ssi_confirm_nonce'])) {
				_ssiProcessConfirmation($_POST['from_uri'], $_POST['nonce_hash'], $_POST['provider_user_id']);
			} 
		}
	
		add_submenu_page(__FILE__, 'Confirm', 'Confirm Login', 8, 'ssi-confirm', '_ssiAdminConfirmPage');
	}
}
add_action('admin_menu', 'ssiAdminMenus');

function ssiShowAvatar($s) {
	global $wpdb, $ssi_author, $ssi_author_url;
	
	if (strlen($ssi_author_url)) {
		$domain = parse_url($ssi_author_url);
		$domain = $domain['host'];
	} else {
		$domain = '';
	}
	
	$site = get_option('siteurl');
	
	$consumer_table = $wpdb->prefix . "ssi_consumer_store";
	$sql = 'SELECT login_display_nick, login_avatar_uri FROM '
	.$consumer_table.' WHERE login_nick = "'.$wpdb->escape($ssi_author).'" AND login_domain = "'.$wpdb->escape($domain).'" LIMIT 1';
	
	$user_data = $wpdb->get_row($sql);
	if ($user_data) {
		if(strlen($user_data->login_avatar_uri)) {
			$avatar_image = '<img src="'.clean_url($user_data->login_avatar_uri).'" width="20px" height="20px" class="ssi-avatar" onerror="this.src=\''.$site.'/wp-content/plugins/ssi/img/avi.gif\'"> ';
		} else {
			$avatar_image = '';
		}
		
		if(strlen($user_data->login_display_nick)) {
			$s = str_replace($ssi_author, wp_specialchars($user_data->login_display_nick), $s);
		}
		
	} else {
		$avatar_image = '';
	}
	
	return $avatar_image.$s;
}
add_filter('get_comment_author_link', 'ssiShowAvatar',1);

function ssiReadyAvatarAuthor($s) {
	global $ssi_author;
	$ssi_author = $s;
	return $s;
}
add_filter('get_comment_author', 'ssiReadyAvatarAuthor',1);

function ssiReadyAvatarUrl($s) {
	global $ssi_author_url;
	$ssi_author_url = $s;
	return $s;
}
add_filter('get_comment_author_url', 'ssiReadyAvatarUrl',1);

function _ssiAdminIndexPage()
{
	global $wpdb;
	$site = get_option('siteurl');
	$site = parse_url($site);
	$domain = $site['host'];
	$provider_user_table = $wpdb->prefix . "ssi_provider_user_store";
	

	if (isset($_POST['edit_id']) && isset($_POST['edit_id_submit'])) {

		$sql = 'SELECT * FROM '.$provider_user_table.' WHERE  id = "'.$wpdb->escape($_POST['edit_id']).'"';
		$edit_login_info = $wpdb->get_row($sql);
	
	}
	
	if (isset($_POST['edit_submit'])) {

		$sql = 'UPDATE '.$provider_user_table.' SET 
		display_nick = "'.$wpdb->escape($_POST['display_nick']).'", 
		avatar_uri = "'.$wpdb->escape($_POST['avatar_uri']).'", 
		email = "'.$wpdb->escape($_POST['email']).'",
		language = "'.$wpdb->escape($_POST['language']).'",
		country = "'.$wpdb->escape($_POST['country']).'",
		timezone = "'.$wpdb->escape($_POST['timezone']).'"		
		WHERE id = "'.$wpdb->escape($_POST['edit_id']).'"';
		
		$wpdb->query($sql);
		
		$message = 'Login information saved';
		
	}
	
	if (isset($_POST['delete_submit'])) {

		$sql = 'DELETE FROM '.$provider_user_table.' WHERE id = "'.$wpdb->escape($_POST['edit_id']).'" LIMIT 1';
		
		$wpdb->query($sql);
		
		$message = 'Login deleted';
		
	}
	
	if (isset($_POST['new_login_submit'])) {
		// check to make sure nick doesnt already exist
		if (strlen($_POST['login_nick'])) {
			$sql = 'SELECT login_nick FROM '.$provider_user_table.' WHERE login_nick = "'.$wpdb->escape($_POST['login_nick']).'"';
			$nick = $wpdb->get_var($sql);
			
			if($nick) {
				$error = "Login already exists.";
			}
		} else {
			$error = "Login nick required.";
		}
		
		$sql = 'INSERT INTO '.$provider_user_table.' (login_nick, display_nick, avatar_uri, email, language, country, timezone) VALUES 
			("'.$wpdb->escape($_POST['login_nick']).'",
			 "'.$wpdb->escape($_POST['display_nick']).'",
			 "'.$wpdb->escape($_POST['avatar_uri']).'",
			 "'.$wpdb->escape($_POST['email']).'",
			 "'.$wpdb->escape($_POST['language']).'",
			 "'.$wpdb->escape($_POST['country']).'",
			 "'.$wpdb->escape($_POST['timezone']).'")';
		if (!isset($error)) {
			$message = 'Login information created';
			$wpdb->query($sql);
		}
	}
	
	$sql = 'SELECT login_nick, id FROM '.$provider_user_table;
	$user_select = $wpdb->get_results($sql);
	
	include(ABSPATH . 'wp-content/plugins/ssi/Templates/ssiAdminIndex.php');
	
}

// WAIT FOR IT
function _ssiAdminConfirmPage()
{
	global $wpdb;
	if(isset($_GET['from_uri']) && isset($_GET['nonce_hash']) && isset($_GET['login_nick'])) {
		$_SESSION['ssi_confirm_nonce'] = wp_create_nonce();
		$provider_user_table = $wpdb->prefix . "ssi_provider_user_store";
		$sql = 'SELECT id FROM '.$provider_user_table.' WHERE login_nick = "'.$wpdb->escape($_GET['login_nick']).'" LIMIT 1';
		$from = parse_url($_GET['from_uri']);
		$from = $from['host'];
		$provider_user_id = $wpdb->get_var($sql);
		if (preg_match('/^[0-9]+$/', $provider_user_id)) {
			include(ABSPATH . 'wp-content/plugins/ssi/Templates/ssiAdminConfirm.php');
		} else {
			include(ABSPATH . 'wp-content/plugins/ssi/Templates/ssiAdminConfirmError.php');
		}
	} else {
		include(ABSPATH . 'wp-content/plugins/ssi/Templates/ssiAdminConfirmError.php');
	}
	
}

// READY THE TICKET
function _ssiProcessConfirmation($from_uri, $nonce_hash, $provider_user_id, $login_code = null)
{
	global $wpdb;

	// Setup Ticket Information
	_ssiBeginPearAPI();
	require_once 'Net/URL.php';
	$url =& new Net_URL($from_uri);
	
	$from_uri = $url->getURL();
	$client_hash_alg = "sha-1";
	$client_hash = sha1($_SERVER['HTTP_USER_AGENT'].$_SERVER['REMOTE_ADDR']);
	
	if (isset($_POST['confirm_submit_cancel'])) {
		$login_code = "201";
	} else if(!$login_code) {
		$login_code = "100";
	}
	
	// SQL
	$provider_user_table = $wpdb->prefix . "ssi_provider_user_store";
	$sql = 'UPDATE '.$provider_user_table.' SET current_login_code = "'.$wpdb->escape($login_code).'",
	  current_nonce_hash = "'.$wpdb->escape($nonce_hash).'",
	  current_client_hash = "'.$wpdb->escape($client_hash).'",
	  current_client_hash_algo = "'.$wpdb->escape($client_hash_alg).'",
	  current_domain = "'.$wpdb->escape($url->host).'"
	  WHERE id = "'.$wpdb->escape($provider_user_id).'"';
	
	$result = $wpdb->query($sql);
	
	// RESTTA redirect back to consumer
	$resource = array(
		'name' => 'consumer_auth'
	);
	
	$data = _ssiRESTTA('ssi', 'http://'.$url->host.'/restta.xml', $resource);

	if(!PEAR::isError($data))	{
		// BOMBS AWAY
		if ($login_code == '200') {
			wp_redirect($data['url']);
			exit();
		} else {
			wp_redirect($data['url']);
		}
		return;
	} else {
		// Uh Oh, error, list the error with a link back the page you were browsing
		include(ABSPATH . 'wp-content/plugins/ssi/Templates/ssiAdminConfirmProcessError.php');
	}
	
	_ssiEndPearAPI();
}





function _ssiRESTTA($requested_class, $url, $requested_resource)
{
	require_once 'HTTP/Request.php';
	$req =& new HTTP_Request($url);
	if (!PEAR::isError($req->sendRequest())) {
		$restta = $req->getResponseBody();
	} else {
		return PEAR::raiseError('RESTTA file not found');
	}
	
	
	if(isset($restta)) {
		// Include XML_Unserializer
		require_once 'XML/Unserializer.php'; 
		require_once 'Net/URL.php';
		$urlobj =& new Net_URL($url);
		// Instantiate the serializer
		$unserializer =& new XML_Unserializer(
								array('parseAttributes' => TRUE, 'forceEnum' => array('resource','input')));
		
		// Serialize the data structure
		$status = $unserializer->unserialize($restta);
		
		// Check whether serialization worked
		if (PEAR::isError($status)) {
			return $status;
		}
		
		$restta_data = $unserializer->getUnserializedData();
	
		
		
		
		foreach($restta_data['appClass'] AS $app_class) {
			if ($app_class['name'] == $requested_class) {
				foreach($app_class['resource'] AS $resource) {
					if($requested_resource['name'] == $resource['name']) {
						if(substr($resource['uriPattern'], 0, 4) != 'http') {
							if ($resource['pathPrefix']) {
								$pathPrefix = $resource['pathPrefix'];
							} else if($app_class['pathPrefix']) {
								$pathPrefix = $app_class['pathPrefix'];
							} else if($restta_data['pathPrefix']) {
								$pathPrefix = $restta_data['pathPrefix'];
							} else {
								$pathPrefix = '';
							}
						
						
							$resource['uriPattern'] = $urlobj->protocol.'://'.$urlobj->host.$pathPrefix.$resource['uriPattern'];
						
						}	
						$data = _ssiRESTTAProcessResource($resource, $requested_resource['input']);
						
						return $data;
					}
				
				}
							
			} 
			
			
			if ($app_class['name'] == 'restta'){
				foreach($app_class['resource'] AS $resource) {
					if($resource['name'] == 'restta_delegate') {
						if(substr($resource['uriPattern'], 0, 4) != 'http') {
							if ($resource['pathPrefix']) {
								$pathPrefix = $resource['pathPrefix'];
							} else if($app_class['pathPrefix']) {
								$pathPrefix = $app_class['pathPrefix'];
							} else if($restta_data['pathPrefix']) {
								$pathPrefix = $restta_data['pathPrefix'];
							} else {
								$pathPrefix = '';
							}
							$resource['uriPattern'] = $urlobj->protocol.'://'.$urlobj->host.$pathPrefix.$resource['uriPattern'];
						
						}	
						$input_data = array(
							'app_class' => $requested_class
						);
						$data = _ssiRESTTAProcessResource($resource, $input_data);
						if (PEAR::isError($data)) {
							return $data;
						} else {
							return _ssiRESTTA($app_class, $data['url'], $resource);
						}
					}
				}
				
			}
		}
	} else {
		// RESTTA error
		return PEAR::raiseError('No RESTTA file set');
	}
}

function _ssiRESTTAProcessResource($resource, $supplied_input)
{
	$get_array = array();
	$post_array = array();
	if($supplied_input) {
		foreach($resource['input'] AS $input) {
			if($input['type'] == 'get') {
				if(!$input['queryKey']) {
					$querykey = $input['name'];
				} else {
					$querykey = $input['queryKey'];
				}
				// Default fallback
				
				
				$get_array[$querykey] = $supplied_input[$input['name']];
				
			} else if($input['type'] == 'post') {
				if(!$input['queryKey']) {
					$querykey = $input['name'];
				} else {
					$querykey = $input['queryKey'];
				}
				// Default fallback
				
				
				$post_array[$querykey] = $supplied_input[$input['name']];
				
			} else if($input['type'] == 'uriPattern') {
				$resource['uriPattern'] = str_replace('['.$input['name'].']',$supplied_input[$input['name']], $resource['uriPattern']);
			}
		}
	}

	$fin_url =& new Net_URL($resource['uriPattern']);
	if (count($get_array)) {
		if (count($fin_url->querystring)) {
			$fin_url->querystring = array_merge($fin_url->querystring,$get_array);
		} else {
			$fin_url->querystring = $get_array;
			
		}
	}
	
	$return['url'] =  $fin_url->getURL();
	
	if (count($post_array)) {
		$return['post'] = $post_array;
	}
	
	return $return;
	
						

}

function _ssiHash($algo, $data)
{
	$algo = str_replace("-", "", $algo);

	switch ($algo) {
		case 'md5':
			return md5($data);
		break;
		case 'sha1':
			return sha1($data);
		break;
		case 'crc32':
			return crc32($data);
		break;
	}
	
	if (function_exists('mhash')) {
		$mhash_algos = array(
			'adler32' => MHASH_ADLER32,
			'crc32b' => MHASH_CRC32B, 
			'gost' => MHASH_GOST, 
            'haval128' => MHASH_HAVAL128, 
			'haval160' => MHASH_HAVAL160, 
			'haval192' => MHASH_HAVAL192, 
			'haval256' => MHASH_HAVAL256, 
			'ripemd160' => MHASH_RIPEMD160, 
            'sha256' => MHASH_SHA256, 
			'tiger' => MHASH_TIGER, 
			'tiger128' => MHASH_TIGER128, 
			'tiger160'  => MHASH_TIGER160);
		
		if(array_key_exists($algo, $mhash_algos)) {
			return bin2hex(mhash($mhash_algos[$algo], $data));
		} else {
			return false;
		}
	
	
	} else if (function_exists('hash') && function_exists('hash_algos')) {
		if(in_array($algo, hash_algos())) {
			return hash($algo, $data);
		} else {
			return false;
		}
	
	
	} else {
		return false;
	}
 }



// Use PEAR
function _ssiBeginPearAPI()
{
	global $ssi_pear_api;
	if($ssi_pear_api == false) {
		$path = get_include_path();
		$api_path = ABSPATH . 'wp-content/plugins/ssi/PEAR';
		$path = $api_path . PATH_SEPARATOR . $path;
		set_include_path($path);
		$ssi_pear_api = true;
	}

}


// Stop using PEAR 
function _ssiEndPearAPI()
{
	global $ssi_pear_api;
	if($ssi_pear_api == true) {
		$path = get_include_path();
		$api_path = ABSPATH . 'wp-content/plugins/ssi/PEAR';
		$path = substr($path, strlen($api_path) + 2);
		set_include_path($path);
		$ssi_pear_api = false;
	}
}

?>