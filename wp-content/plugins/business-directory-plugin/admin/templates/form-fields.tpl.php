<?php
	echo wpbdp_admin_header(null, null, array(
		array(_x('Add New Form Field', 'form-fields admin', 'WPBDM'), esc_url(add_query_arg('action', 'addfield'))),
		array(_x('Preview Form', 'form-fields admin', 'WPBDM'), esc_url(add_query_arg('action', 'previewform'))),
	));
?>
	<?php wpbdp_admin_notices(); ?>

	<?php _ex('Make changes to your existing form fields.', 'form-fields admin', 'WPBDM'); ?>

	<?php $table->views(); ?>
	<?php $table->display(); ?>

<?php echo wpbdp_admin_footer(); ?>