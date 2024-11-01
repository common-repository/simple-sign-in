<?php
/*
Plugin Name: SSI Login Widget
Plugin URI: http://peopletab.com/ssi
Description: Add SSI login to your sidebar
Author: Ian Szewczyk
Version: 1.0
Author URI: http://peopletab.com
*/

@session_start();
function widget_ssi_consumer_init()
{
	register_sidebar_widget('SSI Login', 'widget_ssi_consumer');
	// Is there a user login ?
	if(isset($_POST['ssi_login_value']) && is_email(sanitize_email($_POST['ssi_login_value']))) {
		$data = explode('@',$_POST['ssi_login_value']);
		$login_nick = $data[0];
		$domain = $data[1];
		$local_domain = preg_replace('/^(www\.)(.+)/i', '$2', $_SERVER['HTTP_HOST']);
		//Check data
		if ($domain != $local_domain)
		{
		
			// Use PEAR
			_ssiBeginPearAPI();
			require_once 'Net/URL.php';
			$url =& new Net_URL();
			
			if(preg_match('/^[a-z0-9-]+(\.[a-z0-9-]+)+/i', $domain))  {
				
				$_SESSION['ssi_nonce_value'] = wp_create_nonce();
				$nonce_hash = sha1($_SESSION['ssi_nonce_value']);
				$from = $url->getURL();
				if (preg_match('/consumer_auth\.php/', $from)) {
					$from = 'http://'.$url->host;
				} 
				$_SESSION['ssi_consumer_from'] = $from;
				$_SESSION['ssi_consumer_to'] = $domain;
				$_SESSION['ssi_consumer_nick'] = $login_nick;
				
				$resource = array(
					'name' => 'ticket_gen',
					'input' => array(
						'from_uri' => urlencode($from),
						'nonce_hash' => $nonce_hash,
						'login_nick' => $login_nick 
					)
					
				);
				
				$data = _ssiRESTTA('ssi', 'http://'.$domain.'/restta.xml', $resource);
					
				if(!PEAR::isError($data)) {
					// POST not supported on redirects
					wp_redirect($data['url']);
				} else {
					// RESTTA error
					_ssiEndPearAPI();
					return;
				}
				
			} else {
				// Bad host error
				_ssiEndPearAPI();
				return;
			}
			
		}
	}
	
	
}
add_action('plugins_loaded', 'widget_ssi_consumer_init');

function widget_ssi_consumer()
{
	$site = get_option('siteurl');
	include(ABSPATH . 'wp-content/plugins/ssi/Templates/ssiConsumerWidget.php');
}



?>