<div id="wpbdp-main-page" class="wpbdp-main-page wpbdp-main businessdirectory wpbdp-page <?php echo join(' ', $__page__['class']); ?>">

    <div class="wpbdp-bar cf">
          <?php wpbdp_the_main_links(); ?>
          <?php wpbdp_the_search_form(); ?>
    </div>

    <?php echo $__page__['before_content']; ?>

    <div class="wpbdp-page-content <?php echo join(' ', $__page__['content_class']); ?>">
    	<div id="wpbdp-categories" class="cf">
    		<?php wpbdp_the_directory_categories(); ?>
    	</div>

        <?php if ($listings) echo $listings; ?>
    </div>

</div>