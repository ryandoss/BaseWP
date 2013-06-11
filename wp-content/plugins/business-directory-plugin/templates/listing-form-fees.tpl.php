<?php
if (!function_exists('_wpbdp_is_fee_selected')) {
	function _wpbdp_is_fee_selected($category, $feeid) {
		$fees = isset($_POST['fees']) ? $_POST['fees'] : array();
		return wpbdp_getv($fees, $category) == $feeid;
	}
}

if (!function_exists('_wpbdp_has_fee_selected')) {
	function _wpbdp_has_fee_selected($category) {
		$fees = isset($_POST['fees']) ? $_POST['fees'] : array();
		return isset($fees[$category]);
	}
}
?>
<div id="wpbdp-submit-page" class="wpbdp-submit-page businessdirectory-submit businessdirectory wpbdp-page step-fees">

	<h2>
		<?php echo !$listing_id ? _x('Submit A Listing', 'templates', 'WPBDM') : _x('Edit Your Listing', 'templates', 'WPBDM'); ?>
	</h2>

	<h3><?php _ex('Step 2 - Payment Options', 'templates', 'WPBDM'); ?></h3>

	<?php if ($validation_errors): ?>
		<ul class="validation-errors">
			<?php foreach ($validation_errors as $error_msg): ?>
			<li><?php echo $error_msg; ?></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<form id="wpbdp-listing-form-fees" method="POST" action="">
		<input type="hidden" name="action" value="<?php echo $listing_id ? 'editlisting' : 'submitlisting'; ?>" />
		<input type="hidden" name="_step" value="payment" />
		<input type="hidden" name="listing_data" value="<?php echo base64_encode(serialize($listing_data)); ?>" />

		<?php foreach ($fee_options as $fee_option): ?>
		<div class="fee-options">
			<?php $selected = false; ?>
			<h4><?php echo sprintf(_x('"%s" fee options', 'templates', 'WPBDM'), $fee_option['category']->name); ?></h4>
			<?php foreach ($fee_option['fees'] as $fee): ?>
					<p>
						<?php $has_fee_selected = _wpbdp_has_fee_selected($fee_option['category']->term_id); ?>
						<?php $is_fee_selected = $has_fee_selected && _wpbdp_is_fee_selected($fee_option['category']->term_id, $fee->id); ?>
						<?php $selected = $selected || $is_fee_selected; ?>

						<input type="radio" name="fees[<?php echo $fee_option['category']->term_id; ?>]" value="<?php echo $fee->id; ?>"
							<?php echo ($is_fee_selected || (!$has_fee_selected && !$selected)) ? ' checked="checked" ' : ''; ?>>
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
		</div>
		<?php endforeach; ?>

		<?php if ( wpbdp_get_option( 'featured-offer-in-submit' ) && !$listing_id ): ?>
		<div class="upgrade-to-featured-option">
			<b><?php echo sprintf( _x('Would you like to upgrade your listing to "%s" for %s more?', 'templates', 'WPBDM'), esc_attr( $upgrade_option->name ), wpbdp_get_option( 'currency-symbol' ) . ' ' . $upgrade_option->cost ); ?></b>
			<p class="description"><?php echo esc_html( $upgrade_option->description ); ?></p>
			<p>
				<label><input type="checkbox" name="upgrade-listing" value="upgrade" /> <?php _ex( 'Yes, upgrade my listing now.', 'templates', 'WPBDM'); ?></label>
			</p>
		</div>
		<?php endif; ?>

		<input type="submit" name="submit" class="submit" value="<?php _ex('Continue', 'templates', 'WPBDM'); ?>" />

	</form>

</div>