<?php
	echo wpbdp_admin_header(_x('Uninstall Business Directory', 'admin uninstall', 'WPBDM'));
?>

<?php wpbdp_admin_notices(); ?>

<p>
	<?php _ex("If you are certain you wish to uninstall the plugin, please click the link below to proceed. Please note that all your data related to the plugin, your ads, images and everything else created by the plugin will be destroyed.", 'admin uninstall', "WPBDM"); ?>
</p>

<form action="" method="POST">
	<input type="hidden" name="doit" value="1" />
	<?php submit_button(_x('Proceed with Uninstall', 'admin uninstall', 'WPBDM'), 'delete'); ?>
</form>

<?php
	echo wpbdp_admin_footer();
?>