<div id="wpbdp-category-page" class="wpbdp-category-page businessdirectory-category businessdirectory wpbdp-page">
    <div class="wpbdp-bar cf">
        <?php wpbdp_the_main_links(); ?>
        <?php wpbdp_the_search_form(); ?>
    </div>

    <?php echo $__page__['before_content']; ?>

    <h2 class="category-name">
        <?php if ( $is_tag ): ?>
            <?php echo sprintf( _x( 'Listings tagged: %s', 'templates', 'WPBDM' ), $category->name ); ?>
        <?php else: ?>
            <?php echo esc_attr($category->name); ?>
        <?php endif; ?>
    </h2>

    <?php do_action( 'wpbdp_before_category_page', $category ); ?>
    <?php
    	echo apply_filters( 'wpbdp_category_page_listings', wpbdp_render('businessdirectory-listings', array('excludebuttons' => true)), $category );
    ?>
    <?php do_action( 'wpbdp_after_category_page', $category ); ?>

</div>