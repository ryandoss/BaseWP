<?php
	echo wpbdp_admin_header(_x('Uninstall Business Directory', 'admin uninstall', 'WPBDM'));
?>

<?php wpbdp_admin_notices(); ?>

<p><?php _ex("Uninstall completed.", 'admin uninstall', "WPBDM"); ?></p>
<p><a href="<?php echo admin_url(); ?>"><?php _ex('Return to Dashboard.', 'admin uninstall', 'WPBDM'); ?></p>

<?php
	echo wpbdp_admin_footer();
?>