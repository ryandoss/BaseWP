<div id="wpbdp-renewal-page" class="wpbdp-renewal-page businessdirectory-renewal businessdirectory wpbdp-page">

    <div class="wpbdp-bar cf">
        <?php wpbdp_the_main_links(); ?>
    </div>

	<h2><?php _ex('Renew Listing', 'templates', 'WPBDM'); ?></h2>
	<p><?php echo sprintf(_x('You are about to renew "%s".', 'templates', 'WPBDM'), $listing->post_title); ?></p>

	<p><?php _ex('Select a listing payment option.', 'WPBDM'); ?></p>

	<form id="wpbdp-renewlisting-form" method="POST" action="">
		<h4 class="feecategoriesheader"><?php echo sprintf(_x('"%s" fee options', 'templates', 'WPBDM'), $category->name); ?></h4>
		<?php foreach ($fee_options as $fee): ?>
				<p>
					<input type="radio" name="fee_id" value="<?php echo $fee->id; ?>" <?php echo (count($fee_options) == 1 || (isset($_POST['fee_id']) && $_POST['fee_id'] == $fee->id)) ? ' checked="checked" ' : ''; ?>/>
						<b><?php echo esc_attr($fee->label); ?> <?php echo wpbdp_get_option('currency-symbol'); ?><?php echo $fee->amount; ?></b><br />
							<?php if (wpbdp_get_option('allow-images') && ($fee->images > 0)): ?>
								<?php if ($fee->days == 0): ?>
									<?php echo sprintf(_nx('Listing will run forever and includes %d image.', 'Listing will run forever and includes %d images.', $fee->images, 'templates', 'WPBDM'), $fee->images); ?>
								<?php else: ?>
									<?php echo sprintf(_nx('Listing will run for %d day', 'Listing will run for %d days', $fee->days, 'templates', 'WPBDM'), $fee->days) . ' '; ?>
									<?php echo sprintf(_nx('and includes %d image.', 'and includes %d images.', $fee->images, 'templates', 'WPBDM'), $fee->images); ?>
								<?php endif; ?>
							<?php else: ?>
								<?php if ($fee->days == 0): ?>
									<?php _ex('Listing will run forever.', 'templates', 'WPBDM'); ?>
								<?php else: ?>
									<?php echo sprintf(_nx('Listing will run for %d day.', 'Listing will run for %d days.', $fee->days, 'templates', 'WPBDM'), $fee->days); ?>
								<?php endif; ?>
							<?php endif; ?>
				</p>
		<?php endforeach; ?>

		<input type="submit" class="submit" name="submit" value="<?php _ex('Proceed to checkout', 'templates', 'WPBDM'); ?>" />

	</form>

</div>