<?php
get_header();
?>
	<div id="content">
    	<div class="container">
		<?php
        query_posts( array(
            'category_name' => 'news',
            'order' => 'ASC' )
        );
        ?>
		<?php if ( have_posts() ) : ?>
			<?php /* Start the Loop */ ?>
			<?php while ( have_posts() ) : the_post(); ?>
				<?php $image = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'thumbnail' ); ?>
                <img src="<?php echo $image[0]; ?>" alt="" class="alignleft" />
				<h2><?php the_title(); ?></h2>
				<p><?php the_content(); ?></p>
                <div class="clear"></div>
			<?php endwhile; ?>
		<?php endif; ?>
		</div>
	<?php pilr_pagination(); ?>
<?php get_footer(); ?>