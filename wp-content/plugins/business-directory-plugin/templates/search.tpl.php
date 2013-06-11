<?php
$api = wpbdp_formfields_api();
?>
<div id="wpbdp-search-page" class="wpbdp-search-page businessdirectory-search businessdirectory wpbdp-page">

    <div class="wpbdp-bar cf"><?php wpbdp_the_main_links(); ?></div>

    <h2 class="title"><?php _ex('Search', 'search', 'WPBDM'); ?></h2>

<?php // if (!$searching): ?>
<h3><?php _ex('Find a listing', 'templates', 'WPBDM'); ?></h3>
<!-- Search Form -->
<form action="" id="wpbdp-search-form" method="GET">
    <input type="hidden" name="action" value="search" />
    <input type="hidden" name="page_id" value="<?php echo wpbdp_get_page_id('main'); ?>" />
    <input type="hidden" name="dosrch" value="1" />
    <input type="hidden" name="q" value="" />

    <?php echo $fields; ?>
    <?php do_action('wpbdp_after_search_fields'); ?>

    <p>
        <input type="reset" class="reset" value="<?php _ex( 'Clear', 'search', 'WPBDM' ); ?> " onclick="window.location.href = '<?php echo wpbdp_get_page_link( 'search' ); ?>';" />
        <input type="submit" class="submit" value="<?php _ex('Search', 'search', 'WPBDM'); ?>" />
    </p>
</form>
<!-- Search Form -->
<?php // endif; ?>

<?php if ($searching): ?>
    <h3><?php _ex('Search Results', 'search', 'WPBDM'); ?></h3>

    <?php do_action( 'wpbdp_before_search_results' ); ?>
    <div class="search-results">
    <?php if (have_posts()): ?>
        <?php echo wpbdp_render('businessdirectory-listings'); ?>
    <?php else: ?>
        <?php _ex("No listings found.", 'templates', "WPBDM"); ?>
        <br />
        <?php echo sprintf('<a href="%s">%s</a>.', wpbdp_get_page_link('main'),
                           _x('Return to directory', 'templates', 'WPBDM')); ?>    
    <?php endif; ?>
    </div>
    <?php do_action( 'wpbdp_after_search_results' ); ?>
<?php endif; ?>
</div>
