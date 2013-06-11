<?php
/**
* Includes
 **********************************************/
include('includes/shortcodes.php');
include('includes/custom-post-types.php');


/**
* Register navigation menus
 **********************************************/
register_nav_menu( 'main', 'Main Menu' );
register_nav_menu( 'socket', 'Socket Menu' );


/**
* Register widget areas
 **********************************************/
register_sidebar( array(
	'name' => 'Sidebar',
	'id' => 'sidebar',
	'before_widget' => '',
	'after_widget' => '',
	'before_title' => '',
	'after_title' => ''
));


/**
* Pagination
 **********************************************/
function pilr_pagination($pages = '', $range = 2)
{
     $showitems = ($range * 2)+1;  

     global $paged;
     if(empty($paged)) $paged = 1;

     if($pages == '')
     {
         global $wp_query;
         $pages = $wp_query->max_num_pages;
         if(!$pages)
         {
             $pages = 1;
         }
     }   

     if(1 != $pages)
     {
         echo '<div class="pagination">';
		 echo '<span class="pagination_meta">Page '.$paged.' of '.$pages.'</span>';
		 if($paged > 1) echo "<a href='".get_pagenum_link(1)."'>&laquo;</a>";
         if($paged > 2) echo "<a class='prev' href='".get_pagenum_link($paged - 1)."'>&lsaquo;</a>";

         for ($i=1; $i <= $pages; $i++)
         {
             if (1 != $pages &&( !($i >= $paged+$range+1 || $i <= $paged-$range-1) || $pages <= $showitems ))
             {
                 echo ($paged == $i)? "<span class='current'>".$i."</span>":"<a href='".get_pagenum_link($i)."' class='inactive' >".$i."</a>";
             }
         }

         if ($paged < $pages) echo "<a class='next' href='".get_pagenum_link($paged + 1)."'>&rsaquo;</a>";
         if ($paged < $pages-1) echo "<a href='".get_pagenum_link($pages)."'>&raquo;</a>";
         echo "</div>\n"; 
     }
}


/**
* Gallery Metabox setup
 **********************************************/
function be_gallery_metabox( $post_types ) {
	return array( 'page', 'post' );
}
add_action( 'be_gallery_metabox_post_types', 'be_gallery_metabox' );


/**
* Change excpert read more link
 **********************************************/
function pilr_excerpt($text) {
	return str_replace(' [...]', '<br /><a href="'.get_permalink().'">Read More &rarr;</a>', $text);
}
add_filter('the_excerpt', 'pilr_excerpt');


/**
* Clean up formatting in shortcodes
 **********************************************/
function pilr_clean_shortcodes($content) {   
	$array = array (
		'<p>[' => '[', 
		']</p>' => ']', 
		']<br />' => ']'
	);

	$content = strtr($content, $array);
	return $content;
}
add_filter('the_content', 'pilr_clean_shortcodes');


/**
* Enable shortcodes in menu & widget areas
 **********************************************/
add_filter('widget_text', 'do_shortcode');


/**
* Enable excerpts for pages
 **********************************************/
add_post_type_support( 'page', 'excerpt' );


/**
* Enables post thumbnails on post types
 **********************************************/
add_theme_support('post-thumbnails');


/**
* Enable Styles in WYSIWYG editor
 **********************************************/
add_editor_style();
?>