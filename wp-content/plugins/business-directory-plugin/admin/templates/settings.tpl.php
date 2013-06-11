<?php
	echo wpbdp_admin_header(__('Business Directory Settings', 'WPBDM'), 'admin-settings');
?>

<h3 class="nav-tab-wrapper">
<?php if (isset($_REQUEST['settings-updated'])): ?>
	<div class="updated fade">
		<p><?php _e('Settings updated.', 'WPBDM'); ?></p>
	</div>
<?php endif; ?>

<?php foreach($wpbdp_settings->groups as $g): ?>
	<a class="nav-tab <?php echo $g->slug == wpbdp_getv($_REQUEST, 'groupid', 'general') ? 'nav-tab-active': ''; ?>"
	   href="<?php echo add_query_arg('groupid', $g->slug, remove_query_arg('settings-updated')); ?>">
	   <?php echo $g->name; ?>
	</a>
<?php endforeach; ?>
	<a class="nav-tab <?php echo wpbdp_getv($_REQUEST, 'groupid') == 'resetdefaults' ? 'nav-tab-active' : ''; ?>"
        href="<?php echo add_query_arg('groupid', 'resetdefaults', remove_query_arg('settings-updated')); ?>">
        <?php _e('Reset Defaults'); ?>
   	</a>
</h3>

<?php if (wpbdp_getv($_REQUEST, 'groupid', 'general') == 'resetdefaults'): ?>

<p><?php _e('Use this option if you want to go back to the factory-settings. Please notice that all of your customizations will be lost.', 'WPBDM'); ?></p>
<form action="" method="POST">
	<input type="hidden" name="resetdefaults" value="1" />
	<?php echo submit_button(__('Reset Defaults')); ?>
</form>

<?php else: ?>
<?php
	$group = $wpbdp_settings->groups[wpbdp_getv($_REQUEST, 'groupid', 'general')];
?>

<form action="<?php echo admin_url('options.php'); ?>" method="POST">
	<input type="hidden" name="groupid" value="<?php echo $group->slug; ?>" />
	<?php if ($group->help_text): ?>
		<p class="description"><?php echo $group->help_text; ?></p>
	<?php endif; ?>
	<?php settings_fields($group->wpslug); ?>
	<?php do_settings_sections($group->wpslug); ?>
	<?php echo submit_button(); ?>
</form>
<?php endif; ?>

<?php
	echo wpbdp_admin_footer();
?>