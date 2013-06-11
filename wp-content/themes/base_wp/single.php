<?php get_header(); ?>
	<div id="content">
    	<div class="container">
			<?php the_post(); ?>
    		<?php the_content(); ?>
			Have a question about this product? Please feel free to let us know
            <?php
			$args = array(
				'comment_field' => '<p class="comment-form-comment">' .
				'<label for="comment">' . __( 'Question:' ) . '</label>' .
				'<textarea id="comment" name="comment" cols="45" rows="8" aria-required="true"></textarea>' .
				'</p><!-- #form-section-comment .form-section -->',
				
				'title_reply' => '',
				'comment_notes_before' => '',
				'comment_notes_after' => '',
				'label_submit' => 'Send Quesiton'
			);
			comment_form($args);
			
			?>
        </div>
    </div>
<?php get_footer(); ?>