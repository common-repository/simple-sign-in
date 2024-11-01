<?php get_header(); ?>

	<div id="content" class="narrowcolumn">

		<style>
		#ssi-auth {
			padding: 1em;
			margin-left: auto;
			margin-right: auto;
		}
		#ssi-auth h3 a {
			text-decoration: underline;
			font-weight: bold;
		}
		</style>
		<h2>Authentication Error</h2>
		<div id="ssi-auth">
		<h3>There was a problem authenticating the login.</h3>
		<h3><?php echo $error; ?></h3>
		<h3>Would you like to <a href="<?php echo clean_url($from); ?>">return?</a></h3>
		</div>
		</form>

	</div>

<?php get_footer(); ?>
