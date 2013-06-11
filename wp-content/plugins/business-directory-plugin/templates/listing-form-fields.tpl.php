<div id="wpbdp-submit-page" class="wpbdp-submit-page businessdirectory-submit businessdirectory wpbdp-page step-fields">

	<div class="wpbdp-bar cf"><?php echo wpbdp_the_main_links(); ?></div>

	<h2>
		<?php echo !$listing_id ? _x('Submit A Listing', 'templates', 'WPBDM') : _x('Edit Your Listing', 'templates', 'WPBDM'); ?>
	</h2>

	<?php if ($validation_errors): ?>
		<ul class="validation-errors">
			<?php foreach ($validation_errors as $error_msg): ?>
			<li><?php echo $error_msg; ?></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<form id="wpbdp-listing-form-fields" method="POST" action="" enctype="application/x-www-form-urlencoded">
		<input type="hidden" name="action" value="<?php echo $listing_id ? 'editlisting' : 'submitlisting'; ?>" />
		<input type="hidden" name="_step" value="fields" />
		<input type="hidden" name="listing_id" value="<?php echo $listing_id ? $listing_id : 0; ?>" />

		<?php foreach ($fields as $field): ?>
			<?php echo $field['html']; ?>
		<?php endforeach; ?>

		<div class="wpbdp-form-field recaptcha"><?php echo $recaptcha; ?></div>

		<p><input type="submit" class="submit" value="<?php _ex('Continue', 'templates', 'WPBDM'); ?>" /></p>
	</form>

</div>