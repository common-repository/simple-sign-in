<?php if($_SESSION['ssi_nickname']): ?>
<style>
#ssi-greeting {
	
}
</style>
<li id="ssi-greeting" class="widget widget_ssi"><b>
<?php
echo 'Welcome, '.wp_specialchars($_SESSION['ssi_nickname']).'!';
?>
</b><br />
<a href="<?php echo $site.'/wp-content/plugins/ssi/logout.php' ?>">Sign Out</a>
</li>
<?php else: ?>

<li id="ssi" class="widget widget_ssi">
<style>
input.ssi {
	background:#FFFFFF url(<?php echo $site.'/wp-content/plugins/ssi/img/ssi.gif' ?>) no-repeat scroll 0pt 50%;
	padding-left:20px;
	border: 1px solid green;
}
input#ssi_submit {
	visibility: hidden;
	position: absolute;
}

/* brTip */
div.brTip-box {
	background: #FFF;
	border: 1px solid green;
	display: none;
	position: absolute;
	width: 200px;
}
div.brTip-title {
	background: green;
	color: #FFF;
	display: block;
	margin: 0;
	padding: 3px;
	text-align: center;
}
div.brTip-content {
	color: #333;
	margin: 0;
	padding: 5px;
	text-align: left;
}
</style>
<form action="" method="post" id="ssi-form" title="Enter <b>username@domain</b> to login. Remember to be logged in to your provider first!">
<input type="text" class="ssi" id="ssi_login_value" name="ssi_login_value" value="" />
<input type="submit" id="ssi_submit" name="ssi_submit" value="Sign In" />
</form>
</li>
<!-- JS Scripts -->
<script type="text/javascript" src="<?php echo $site.'/wp-content/plugins/ssi/js/jquery-1.2.3.pack.js'?>"></script>
<script type="text/javascript" src="<?php echo $site.'/wp-content/plugins/ssi/js/brTip/brTip.pack.js'?>"></script>
<script type="text/javascript">
$('#ssi-form').brTip({title: 'Simple Sign In', opacity: 0.8, toShow: 50, toHide: 50, top: 10, left: 10});
</script>
<!-- JS Scripts -->

<?php endif; ?>