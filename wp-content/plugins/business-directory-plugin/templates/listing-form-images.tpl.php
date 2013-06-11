<script type="text/javascript">
function wpbdp_listingform_delete_image(id) {
	var form = document.getElementById('wpbdp-listing-form-images');
	form.delete_image.value = id;
	form.submit();
	return false;
}
</script>

<div id="wpbdp-submit-page" class="wpbdp-submit-page businessdirectory-submit businessdirectory wpbdp-page step-images">

	<h2>
		<?php echo !$listing_data['listing_id'] ? _x('Submit A Listing', 'templates', 'WPBDM') : _x('Edit Your Listing', 'templates', 'WPBDM'); ?>
	</h2>

	<h3><?php _ex('Step 3 - Listing Images', 'templates', 'WPBDM'); ?></h3>

	<?php if ($validation_errors): ?>
		<ul class="validation-errors">
			<?php foreach ($validation_errors as $error_msg): ?>
			<li><?php echo $error_msg; ?></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<form id="wpbdp-listing-form-images" method="POST" action="" enctype="multipart/form-data">
		<input type="hidden" name="action" value="<?php echo $listing ? 'editlisting' : 'submitlisting'; ?>" />		
		<input type="hidden" name="_step" value="images" />
		<input type="hidden" name="listing_data" value="<?php echo base64_encode(serialize($listing_data)); ?>" />
		<input type="hidden" name="delete_image" value="0" />

		<?php foreach ($images as $image_id): ?>
			<div class="image">
				<img src="<?php echo wp_get_attachment_thumb_url($image_id); ?>" /><br />
				<input type="button" class="button" onclick="return wpbdp_listingform_delete_image('<?php echo $image_id; ?>');" class="delete-image" value="<?php _ex('Delete Image', 'templates', 'WPBDM'); ?>" /> <br />

				<label>
					<input type="radio" name="thumbnail_id" value="<?php echo $image_id; ?>" <?php echo (count($images) == 1 || $thumbnail_id == $image_id) ? 'checked="checked"' : ''; ?> />
					<?php _ex('Set this image as the listing thumbnail.', 'templates', 'WPBDM'); ?>
				</label>
			</div>
		<?php endforeach; ?>

		<p class="cf" />

		<?php if ($can_upload_images): ?>
			<p><?php echo sprintf(_x("If you would like to include an image with your listing please upload the image of your choice. You are allowed [%s] images and have [%s] image slots still available.", 'templates', 'WPBDM'),		
							   $images_allowed,
							   $images_left); ?></p>
		<div class="upload-form">
			<input type="file" name="image" />
			<input type="submit" class="submit" name="upload_image" value="<?php _ex('Upload Image', 'templates', 'WPBDM'); ?>" />
		</div>

		<p><?php _ex('If you prefer not to add an image click "Finish". Your listing will be submitted.', 'templates', 'WPBDM'); ?></p>
		<?php else: ?>
			<p><?php _ex("Your image slots are all full at this time.  You may click Finish if you are done, or Delete Image to reupload a new image in place of a new one.", 'templates', 'WPBDM'); ?></p>		
		<?php endif; ?>

		<input type="submit" class="submit" name="finish" value="<?php _ex('Finish', 'templates', 'WPBDM'); ?>" />

	</form>

</div>