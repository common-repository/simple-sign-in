<?php echo"<?xml version='1.0'?>" ?>
<ssi version="0.3">
	<loginCode>100</loginCode>
	<expire><?php echo $ticket_expire; ?></expire>
	<consumer><?php echo $user_data->current_domain; ?></consumer>
	<clientHash hashfunc="<?php echo $user_data->current_client_hash_algo; ?>"><?php echo $user_data->current_client_hash; ?></clientHash>
	<tokenHash hashfunc="<?php echo $new_token_hash_algo; ?>"><?php echo $new_token_hash; ?></tokenHash>
	<?php if (isset($token_val)) : ?>
	<tokenVal><?php echo $token_val;?></tokenVal>
	<?php endif; ?>
	<payload>
		<nickname><?php echo $user_data->display_nick; ?></nickname>
		<avatarUri><?php echo $user_data->avatar_uri; ?></avatarUri>
		<email><?php echo $user_data->email; ?></email>
		<timezone><?php echo $user_data->timezone; ?></timezone>
		<country><?php echo $user_data->country; ?></country>
		<language><?php echo $user_data->language; ?></language>
	</payload>
</ssi>