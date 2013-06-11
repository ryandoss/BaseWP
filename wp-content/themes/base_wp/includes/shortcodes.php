<?php

/***
* Display Blog Info
**/
if(!function_exists('pilr_bloginfo'))
{
	function pilr_bloginfo( $atts ) {
		$type = "url";
		if( isset($atts[0]) ) $type = trim($atts[0]);
		switch($type) {
			case 'images':
				$bloginfo = get_bloginfo('template_url').'/images/';
			break;
			default:
				$bloginfo = get_bloginfo($type).'/';
			break;
		}
		return $bloginfo;
	}
	add_shortcode('bloginfo', 'pilr_bloginfo');
}


/***
* Recent Posts
**/
if(!function_exists('pilr_posts'))
{
	function pilr_posts($atts)
	{
		extract(shortcode_atts(array('type' => 'post', 'category' => 'general', 'count' => '1'), $atts));
		global $post;
		
		$html = "";
		
		$my_query = new WP_Query( array(
			'post_type' => $type,
			'category_name' => $category,
			'posts_per_page' => $count
		));
		
		if( $my_query->have_posts() ) : while( $my_query->have_posts() ) : $my_query->the_post();
		
		$html .= "<h2>" . get_the_title() . "</h2>";
		$html .= "<p>" . get_the_excerpt() . "</p>";
		$html .= "<a href=\"" . get_permalink() . "\" class=\"button\">Read more</a>";
		
		endwhile; else:
		$html .= "No posts in ".$category." at this time.";
		endif;
		return $html;
	}
	add_shortcode( 'posts', 'pilr_posts' );
}


/***
* Create a horizontal rule
**/
if(!function_exists('pilr_hr'))
{
	function pilr_hr( $atts, $content = "", $shortcodename = "" ) {
		$top = $toplink = false;
		if (isset($atts[0]) && trim($atts[0]) == 'top')  $top = 'top';
		if($top == 'top') $toplink = '<a href="#toTop" class="scrollTop">top</a>';
		
		if($shortcodename != "hr_invisible")
		{
			$output = '<div class="'.$shortcodename.'">'.$toplink.'<span class="hr_inner"></span></div>';
		} else {
			$output = '<div class="'.$shortcodename.'"></div>';
		}
		return $output;
	}
	add_shortcode('hr', 'pilr_hr');
	add_shortcode('hr_invisible', 'pilr_hr');
}


/***
* Display Button
**/
if(!function_exists('pilr_buttons'))
{
	function pilr_buttons( $atts, $content = "" ) {
		$color = "grey";
		if(isset($atts[0]) && ($atts[0] == "brown" || $atts[0] == "blue")) $color = $atts[0];
		$output = '<span class="btn '.$color.'">'.$content.'</span>';
		return $output;
	}
	add_shortcode('button', 'pilr_buttons');
}


/***
* Shortcode for displaying minislider
**/
if(!function_exists('pilr_minislider'))
{
	function pilr_minislider( $atts, $content = "" )
	{
		global $post;
		
		$args = array(
			'post_type' => 'attachment',
			'order' => 'ASC',
			'numberposts' => -1,
			'post_status' => null,
			'post_parent' => $post->ID
		);
		?>
		<ul class="mini_slider">
			<?php
			$attachments = get_posts( $args );
			if ( $attachments ) {
				foreach ( $attachments as $attachment ) {
					$imgSrc = wp_get_attachment_image_src( $attachment->ID, array(200,200) );
					$imgTitle = apply_filters( 'the_title', $attachment->post_title );
					$imgDesc = apply_filters( 'the_description', $attachment->post_content );
					?>
					<li>
						<img src="<?php echo $imgSrc[0]; ?>" alt="" />
					</li>
					<?php
				}
			}
			?>
		</ul>
        <?php
	}
	add_shortcode('minislider', 'pilr_minislider');
}


/***
* Columns
**/
if(!function_exists('pilr_columns'))
{
	function pilr_columns($atts, $content = "", $shortcodename = "")
	{
		if (isset($atts[0]) && trim($atts[0]) == 'first')  $first = 'first';

		$output  = '<div class="'.$shortcodename.' '.$first.'">';
		$output .=  do_shortcode( $content );
		$output .= '</div>';
			
		return $output;
	}

	add_shortcode('one_third'	, 'pilr_columns');
	add_shortcode('two_third'	, 'pilr_columns');
	add_shortcode('one_fourth'	, 'pilr_columns');
	add_shortcode('three_fourth', 'pilr_columns');
	add_shortcode('one_half'	, 'pilr_columns');
	add_shortcode('one_fifth'	, 'pilr_columns');
	add_shortcode('two_fifth'	, 'pilr_columns');
	add_shortcode('three_fifth'	, 'pilr_columns');
	add_shortcode('four_fifth'	, 'pilr_columns');
}
?>