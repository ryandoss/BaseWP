<?php
	echo wpbdp_admin_header(_x('Delete Listing Fee', 'fees admin', 'WPBDM'));
?>

<p>
	<?php echo sprintf(_x('Are you sure you want to delete the "%s" fee?', 'fees admin', 'WPBDM'), $fee->label); ?>
</p>

<form action="" method="POST">
	<input type="hidden" name="id" value="<?php echo $fee->id; ?>" />
	<input type="hidden" name="doit" value="1" />
	<?php submit_button(_x('Delete Fee', 'fee admin', 'WPBDM'), 'delete'); ?>
</form>

<?php
	echo wpbdp_admin_footer();
?>