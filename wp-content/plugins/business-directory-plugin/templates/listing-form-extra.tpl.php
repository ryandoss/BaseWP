<div id="wpbdp-submit-page" class="wpbdp-submit-page businessdirectory-submit businessdirectory wpbdp-page step-extra">

    <h2>
        <?php echo !$listing_data['listing_id'] ? _x('Submit A Listing', 'templates', 'WPBDM') : _x('Edit Your Listing', 'templates', 'WPBDM'); ?>
    </h2>

    <form id="wpbdp-listing-form-extra" method="POST" action="" enctype="multipart/form-data">
        <input type="hidden" name="_step" value="before_save" />
        <input type="hidden" name="listing_data" value="<?php echo base64_encode(serialize($listing_data)); ?>" />            
        <?php foreach ( $sections as &$section ): ?>
            <div class="wpbdp-listing-form-extra-section <?php echo $section->id; ?>">
                <?php if ( $section->title ): ?><h3><?php echo esc_html( $section->title ); ?></h3><?php endif; ?>
                <div class="section-content"><?php echo $section->_output; ?></div>
            </div>
        <?php endforeach; ?>

        <p><input type="submit" name="do_extra_sections" value="<?php _ex('Finish', 'templates', 'WPBDM'); ?>" class="submit" /></p>
    </form> 

</div>