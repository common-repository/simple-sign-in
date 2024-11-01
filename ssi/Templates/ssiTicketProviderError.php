<?php echo"<?xml version='1.0'?>" ?>
<ssi version="0.3">
	<loginCode><?php echo $login_code; ?></loginCode>
	<expire><?php echo $ticket_expire; ?></expire>
	<consumer><?php echo $user_data->current_domain; ?></consumer>
	<clientHash hashfunc="<?php echo $user_data->current_client_hash_algo; ?>"><?php echo $user_data->current_client_hash; ?></clientHash>
</ssi>