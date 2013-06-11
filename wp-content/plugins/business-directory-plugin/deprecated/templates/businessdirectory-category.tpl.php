<?php
// THIS TEMPLATE IS DEPRECATED. DO NOT USE.
// See http://businessdirectoryplugin.com/docs/matching-the-design-to-your-theme/ for info on Business Directory templates.
?>
<?php get_header(); ?>
<div id="content">

<div id="wpbdp-category-page" class="wpbdp-category-page businessdirectory-category businessdirectory wpbdp-page">
    <div class="wpbdp-bar cf">
        <?php wpbdp_the_main_links(); ?>
        <?php wpbdp_the_search_form(); ?>
    </div>

    <h2 class="category-name"><?php echo wpbusdirman_post_catpage_title(); ?></h2>
    <?php echo wpbdp_render('businessdirectory-listings'); ?>
</div>

</div>
<?php get_footer(); ?>