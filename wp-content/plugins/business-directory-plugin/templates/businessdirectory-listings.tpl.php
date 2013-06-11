<div id="wpbdp-view-listings-page" class="wpbdp-view-listings-page wpbdp-page <?php echo join(' ', $__page__['class']); ?>">

    <?php if (!isset($stickies)) $stickies = null; ?>
    <?php if (!isset($excludebuttons)) $excludebuttons = true; ?>

    <?php if (!$excludebuttons): ?>
        <div class="wpbdp-bar cf">
            <?php wpbdp_the_main_links(); ?>
            <?php wpbdp_the_search_form(); ?>
        </div>
    <?php endif; ?>

    <?php echo $__page__['before_content']; ?>

    <div class="wpbdp-page-content <?php echo join(' ', $__page__['content_class']); ?>">

        <?php wpbdp_the_listing_sort_options(); ?>

        <?php if (!have_posts()): ?>
            <?php _ex("No listings found.", 'templates', "WPBDM"); ?>
        <?php else: ?>
            <div class="listings">
                <?php while (have_posts()): the_post(); ?>
                    <?php echo wpbdp_render_listing(null, 'excerpt'); ?>
                <?php endwhile; ?>

                <div class="wpbdp-pagination">
                <?php if (function_exists('wp_pagenavi')) : ?>
                        <?php wp_pagenavi(); ?>
                <?php elseif (function_exists('wp_paginate')): ?>
                        <?php wp_paginate(); ?>
                <?php else: ?>
                    <span class="prev"><?php previous_posts_link(_x('&laquo; Previous ', 'templates', 'WPBDM')); ?></span>
                    <span class="next"><?php next_posts_link(_x('Next &raquo;', 'templates', 'WPBDM')); ?></span>
                <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>

</div>
