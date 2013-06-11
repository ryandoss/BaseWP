<div id="wpbdp-manage-listings-page" class="wpbdp-manage-listings-page businessdirectory-manage-listings businessdirectory wpbdp-page">
<?php if (!$current_user): ?>
	<?php echo wpbdp_render('parts/login-required'); ?>
<?php else: ?>
	<?php if (have_posts()): ?>
		<p><?php _ex("Your current listings are shown below. To edit a listing click the edit button. To delete a listing click the delete button.", 'templates', "WPBDM"); ?></p>
		<?php echo wpbdp_render('businessdirectory-listings'); ?>
	<?php else: ?>
		<p><?php _ex('You do not currently have any listings in the directory.', 'templates', 'WPBDM'); ?></p>
		<?php echo sprintf('<a href="%s">%s</a>.', wpbdp_get_page_link('main'),
					 	   _x('Return to directory', 'templates', 'WPBDM')); ?>		
	<?php endif; ?>
<?php endif; ?>
</div>