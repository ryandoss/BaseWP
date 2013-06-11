<?php get_header(); ?>
    <div id="content">
		<div class="container">
        
            <section id="slideshow">
                <ul class="slider">
                    <?php
                    query_posts( array(
                        'post_type' => 'homeslideshow',
                        'order' => 'ASC',
                        'orderby' => 'menu_order' )
                    );
                    if(have_posts()):
                        while(have_posts()): the_post();
                            if (has_post_thumbnail( $post->ID ) ):
                                $image = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'single-post-thumbnail' ); ?>
                                <li style="width:100%; height:100%; background-image:url(<?php echo $image[0]; ?>); background-position:center; background-repeat:no-repeat; background-size:cover;">
                                    <div style="width:100%; height:100%; background-image:url(<?php bloginfo('template_url'); ?>/images/slideshow_overlay.png); position:absolute; opacity:.5;"></div>
                                    <hgroup class="container">
                                        <?php the_content(); ?>
                                    </hgroup>
                                </li>
                            <?php endif; ?>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </ul>
            </section>
            <?php wp_reset_query(); ?>
            
            <div id="nav-below"><?php pilr_pagination(); ?></div>
            <?php
            while(have_posts()):the_post(); ?>
                <div class="post">
                    <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                    <?php the_content(); ?>
                </div>
			<?php endwhile; ?>
            <div id="nav-below"><?php pilr_pagination(); ?></div>
        </div>
    </div>
<?php get_footer(); ?>