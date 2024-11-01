<?php
if (empty($wp)) {
	require_once('../../../wp-config.php');
	wp();
}
@session_start();
// Cleanup
foreach($_SESSION AS $key => $val) {
	if (substr($key, 0, 3) == 'ssi') {
		unset($_SESSION[$key]);
	}
}

if($_SERVER['HTTP_REFERER']) {
	wp_redirect(clean_url($_SERVER['HTTP_REFERER']));
} else {
	wp_redirect(clean_url('http://'.$_SERVER['HTTP_HOST']));
}

?>