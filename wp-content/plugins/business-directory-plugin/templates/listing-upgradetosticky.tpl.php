<h2><?php _ex('Upgrade listing', 'templates', 'WPBDM'); ?></h2>

<?php if ($featured_text = wpbdp_get_option('featured-description')): ?>
	<p><?php echo $featured_text; ?></p>
<?php endif; ?>

<form action="" method="POST">
	<input type="hidden" name="action" value="upgradetostickylisting" />
	<input type="hidden" name="listing_id" value="<?php echo $listing->ID; ?>" />
	<input type="submit" name="do_upgrade" value="<?php echo sprintf(_x('Upgrade listing to %s for %s.', 'templates', 'WPBDM'),
                                                                     esc_attr($featured_level->name),
															 	     wpbdp_get_option('currency-symbol') .
															    	 $featured_level->cost); ?>" />
</form>