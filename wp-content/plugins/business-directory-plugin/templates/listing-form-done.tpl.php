<div id="wpbdp-submit-page" class="wpbdp-submit-page businessdirectory-submit businessdirectory wpbdp-page step-done">

	<h2>
		<?php echo !$listing_data['listing_id'] ? _x('Submit A Listing', 'templates', 'WPBDM') : _x('Edit Your Listing', 'templates', 'WPBDM'); ?>
	</h2>

	<h3 style="padding: 10px;"><?php _ex('Submission received', 'templates', 'WPBDM'); ?></h3>
    <?php if (isset($_GET['action']) && $_GET['action'] == 'editlisting'): ?>
        <p>
            <?php _ex('Your listing changes were saved.', 'templates', 'WPBDM'); ?><br />
            <?php if ($listing->post_status == 'publish'): ?>
            <a href="<?php echo get_permalink($listing->ID); ?>"><?php _ex('Return to listing.', 'templates', 'WPBDM'); ?></a>
            <?php endif; ?>            
        </p>

    <?php else: ?>
	   <p><?php _ex('Your listing has been submitted.', 'templates', 'WPBDM'); ?></p>
       <p>
        <?php if ($listing->post_status == 'publish'): ?><a href="<?php echo get_permalink($listing->ID); ?>"><?php _ex('Go to your listing', 'templates', 'WPBDM'); ?></a> | <?php endif; ?>
        <a href="<?php echo wpbdp_get_page_link('main'); ?>"><?php _ex('Return to directory.', 'templates', 'WPBDM'); ?></a>
       </p>
    <?php endif; ?>

</div>