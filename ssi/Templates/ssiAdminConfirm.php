<style>
#ssi-confirm {
	padding: 1em;
	margin-left: auto;
	margin-right: auto;
	width: 45em;
}
</style>
<h2>Confirm Login</h2>
<div id="ssi-confirm">
<h3>Do you wish to login to <?php echo $from; ?>?</h3>
<br />
<div id="comment-confirm-form">
<form name="input" action="" method="post">
<input type="hidden" name="nonce_hash" value="<?php echo wp_specialchars($_GET['nonce_hash']);?>">
<input type="hidden" name="from_uri" value="<?php echo wp_specialchars($_GET['from_uri']);?>">
<input type="hidden" name="ssi_confirm_nonce" value="<?php echo $_SESSION['ssi_confirm_nonce'];?>">
<input type="hidden" name="provider_user_id" value="<?php echo $provider_user_id;?>">
<input type="submit" name="confirm_submit" value="Login">
<input type="submit" name="confirm_submit_cancel" value="Cancel">
</form>
</div>
</div>