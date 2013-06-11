<div id="wpbdmentry">
	<div id="lco">
		<div class="left buttonform">
			<?php echo wpbusdirman_post_menu_button_submitlisting() ;?>
			<?php echo wpbusdirman_post_menu_button_viewlistings() ;?>
		</div>
		
		<div class="right">
			<form id="wpbdmsearchform" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get">
			<input id="intextbox" maxlength="150" name="s" size="20" type="text" value="" />
			<input name="post_type" type="hidden" value="<?php echo wpbdp()->get_post_type(); ?>" />
			<input id="wpbdmsearchsubmit" class="wpbdmsearchbutton" type="submit" value="<?php _ex('Search Listings', 'templates', 'WPBDM'); ?>" />
			</form>
		</div>
	</div>

	<div id="wpbusdirmancats">
		<div style="clear:both;"></div>
		<ul><?php print wpbusdirman_post_list_categories(); ?></ul>
	</div>
	<br style="clear: both;" />
</div>