<?php
	echo wpbdp_admin_header(_x('Delete Form Field', 'form-fields admin', 'WPBDM'));
?>

<p>
	<?php echo sprintf(_x('Are you sure you want to delete the "%s" field?', 'form-fields admin', 'WPBDM'), $field->get_label()); ?>
</p>

<form action="" method="POST">
	<input type="hidden" name="id" value="<?php echo $field->get_id(); ?>" />
	<input type="hidden" name="doit" value="1" />
	<?php submit_button(_x('Delete Field', 'form-fields admin', 'WPBDM'), 'delete'); ?>
</form>

<?php
	echo wpbdp_admin_footer();
?>