<?php 

/***************************************************************************
 * 
 * UberMenu Shortcodes
 * Version 2.0
 * Last Updated: 2012-01-26
 * @author Chris Mavricos, Sevenspark, http://sevenspark.com
 * 
 * Copyright Chris Mavricos, Sevenspark 
 * 
 ***************************************************************************/

/*
 * Maps 
 */
function ubermenu_google_maps( $atts ){
	
	extract(shortcode_atts(array(
		'lat'		=>	0,		
		'lng'		=>	0,
		'address'	=>	'',
		'zoom' 		=> 	15,
		'title'		=>	'',
		'width'		=>	'100%',
		'height'	=>	'200px'
	), $atts));
	
	$html = '
	<div class="spark-map-canvas" 
			data-lat="'.$lat.'" 
			data-lng="'.$lng.'"
			'; 
	if( !empty( $address ) ) $html.= 'data-address="'.$address.'"
			';
	$html.= 'data-zoom="'.$zoom.'"
			';  
	if( !empty( $title ) ) $html.= 'data-maptitle="'.$title.'"
			';
	$html.=	'style="height:'.$height.'; width:'.$width.'"';
	
	$html.= '></div>';
	
	return $html;
}
add_shortcode( 'ubermenu-map' , 'ubermenu_google_maps' );


/*
 * Recent Posts Shortcodes - optional Image (via "Featured Image" functionality).
 */
function ubermenu_recent_posts($atts){
	
	global $uberMenu;
	
	extract(shortcode_atts(array(
		'num'		=>	3,		
		'img'		=>	'on',
		'excerpt'	=>	'off',
		'category'	=>	'',
		'default_img' => false,
		'offset'	=>	0,
	), $atts));
	
	$args = array(
		'numberposts'	=>	$num,
		'offset'		=>	$offset,
		'suppress_filters' => false
	);
	
	if(!empty($category)){
		if(is_numeric($category)){
			$args['category'] = $category;
		}
		else $args['category_name'] = $category;		
	}
	
	$posts = get_posts($args);
	
	$class = 'wpmega-postlist';
	if($img == 'on') $class.= ' wpmega-postlist-w-img';
	
	$html= '<ul class="'.$class.'">';
	foreach($posts as $post){
	  		
		$ex = $post->post_excerpt;
		if($ex == '') $ex = $post->post_content;
		//$ex = wpmega_trim($ex, 20);
		//$ex = strip_tags($ex);
		//if(strlen($ex) > 50) $ex = substr($ex, 0, 50).'...';
		$ex = apply_filters('get_the_excerpt', $post->post_excerpt);
		
		$post_url = get_permalink($post->ID);
		
		$image = '';
		$w = 45;
		$h = 45;

		if($img == 'on') $image = $uberMenu->getPostImage($post->ID, $w, $h, $default_img);
						
    	$html.= '<li>'.	$image.
    				'<div class="wpmega-postlist-title"><a href="'.$post_url.'">'.$post->post_title.'</a></div>';
    				
    	if($excerpt == 'on')
    		$html.= '<div class="wpmega-postlist-content">'.$ex.'</div>';
    		
    	$html.= 	'<div class="clear"></div>'.
    			'</li>';
	}
	$html.= '</ul>';
	
	return $html;
}
add_shortcode('wpmega-recent-posts', 'ubermenu_recent_posts');	//legacy
add_shortcode('ubermenu-recent-posts', 'ubermenu_recent_posts');

/*
 * Column Group Shortcode - must wrap [wpmega-col] shortcode
 */
function ubermenu_colgroup($atts, $data){

	$col_index = 0;
	
	$pattern = get_shortcode_regex();
		
	$pat = '/\[ubermenu\-col(?<atts>.*?)\]'.'(?<data>.*?)'.'\[\/ubermenu\-col\]/s';		//trailing /s makes dot (.) match newlines
	preg_match_all($pat, $data, $matches, PREG_SET_ORDER);
	
	if( empty( $matches ) ){
		$pat = '/\[wpmega\-col(?<atts>.*?)\]'.'(?<data>.*?)'.'\[\/wpmega\-col\]/s';		//trailing /s makes dot (.) match newlines
		preg_match_all($pat, $data, $matches, PREG_SET_ORDER);
	}
	
	$columns = array(); 
	
	foreach($matches as $m){
		
		//get the colspan
		$colspan_pat = '/colspan="(?<colspan>[\d]*?)"/';
		preg_match($colspan_pat, $m['atts'], $match);
		$colspan = isset($match['colspan']) ? $match['colspan'] : 1;
		
		$col_index += $colspan;	//increment by colspan, so if we have 2 cols in a 2/3rds format it's a 3-col with a 2-span and a 1-span
		
		$columns[] = '[ubermenu-col '.$m['atts'].' col_index="'.$col_index.'" ]'.$m['data'].'[/ubermenu-col]';
	}
	
	$html ='<div class="ss-colgroup ss-colgroup-'.$col_index.'">';
	
	foreach($columns as $c){		
		$html.= do_shortcode($c);		
	}
	
	$html.= '<div class="clear"></div></div>';
	
	return $html;
}
add_shortcode( 'wpmega-colgroup', 	'ubermenu_colgroup');	//legacy
add_shortcode( 'ubermenu-colgroup', 'ubermenu_colgroup');

/*
 * Column Shortcode
 */
function ubermenu_col($atts, $data){
	extract(shortcode_atts(array(
		'colspan'		=>	1,
		'col_index'		=>	0,
	), $atts));
	
	$col_index;
	$data = do_shortcode($data);
	$data = wpmega_trim_tag($data, array('br', 'br/', 'br /'));
	return '<div class="ss-col ss-col-'.$col_index.' ss-colspan-'.$colspan.'">'.$data.'</div>';
}
add_shortcode( 'wpmega-col', 	'ubermenu_col' );	//legacy
add_shortcode( 'ubermenu-col', 	'ubermenu_col' );

/** this allows shortcodes in widgets **/
add_filter('widget_text', 'do_shortcode');

/* Tag Trimming Helper Function */
function wpmega_trim_tag($s, $tags){
	$s = trim($s);
	foreach($tags as $tag){
		$tag = '<'.$tag.'>';
		if(strpos($s, $tag) === 0){
			$s = substr($s, strlen($tag));	
		}
		if(strpos($s, $tag) === strlen($s) - strlen($tag)){
			$s = substr($s, 0, strlen($s) - strlen($tag));
		}		
	}	
	return $s;
}



function ubermenu_searchform(){
	$form = '<form role="search" method="get" id="searchform" action="' . esc_url( home_url( '/' ) ) . '" >
	<div class="ubersearch"><label class="screen-reader-text" for="s">' . __('Search for:') . '</label>
	<input type="text" value="' . get_search_query() . '" name="s" id="s" />
	<input type="submit" id="searchsubmit" value="'. esc_attr__('&rarr;') .'" />
	</div>
	</form>';
	
	return $form;
	//get_search_form( false );
}
add_shortcode('ubermenu-search', 'ubermenu_searchform');

/*function wpmega_filter_search_val($form){
	
	$form = str_replace('value="Search"', 'value="&rarr;"', $form);
	return $form;
	
}
add_filter('get_search_form', 'wpmega_filter_search_val');*/
