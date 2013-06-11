<?php get_header(); ?>
	<div id="content">
    	<div class="container">
        	<div class="page_content">
				<?php the_post(); ?>
                <?php the_content(); ?>
        		<?php dynamic_sidebar( apply_filters( 'ups_sidebar', 'sidebar' ) ); ?>
            	<div class="clear"></div>
            </div>
        </div>
    </div>
<?php get_footer(); ?>