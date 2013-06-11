<?php
/**
* Register post types
 **********************************************/
add_action( 'init', 'create_post_types' );
function create_post_types() {
	
	// Home Slideshow
	$labels = array(
		'name' => _x('Home Slideshow', 'post type general name'),
		'singular_name' => _x('Home Slideshow', 'post type singular name'),
		'add_new' => _x('Add New', 'homeslideshow'),
		'add_new_item' => __('Add New Slide'),
		'edit_item' => __('Edit Slide'),
		'new_item' => __('New Slide'),
		'all_items' => __('All Slides'),
		'view_item' => __('View Slide'),
		'search_items' => __('Search Slides'),
		'not_found' =>  __('No slides found'),
		'not_found_in_trash' => __('No slides found in Trash'), 
		'parent_item_colon' => '',
		'menu_name' => 'Home Slideshow'
	);
	$args = array(
		'labels' => $labels,
		'public' => true,
		'publicly_queryable' => true,
		'show_ui' => true,
		'show_in_menu' => true,
		'query_var' => true,
		'rewrite' => true,
		'capability_type' => 'post',
		'has_archive' => true, 
		'hierarchical' => true,
		'menu_position' => null,
	    'show_in_nav_menus' => false,
		'supports' => array('title','editor','thumbnail','page-attributes')
	);
	register_post_type( 'homeslideshow', $args );
}


/**
* Add taxonomies for projects
 **********************************************
add_action( 'init', 'add_taxonomies' );
function add_taxonomies() {
	$labels = array(
		'name' => _x( 'Project Categories', 'taxonomy general name' ),
		'singular_name' => _x( 'Project Category', 'taxonomy singular name' ),
		'search_items' =>  __( 'Search Project Categories' ),
		'popular_items' => __( 'Popular Project Categories' ),
		'all_items' => __( 'All Project Categories' ),
		'parent_item' => null,
		'parent_item_colon' => null,
		'edit_item' => __( 'Edit Project Category' ), 
		'update_item' => __( 'Update Project Category' ),
		'add_new_item' => __( 'Add New Project Category' ),
		'new_item_name' => __( 'New Project Category Name' ),
		'separate_items_with_commas' => __( 'Separate Project Categories with commas' ),
		'add_or_remove_items' => __( 'Add or remove Project Categories' ),
		'choose_from_most_used' => __( 'Choose from the most used Project Categories' ),
		'menu_name' => __( 'Project Categories' ),
	);
	
	$args = array(
		'hierarchical' => true,
		'labels' => $labels,
		'show_ui' => true,
		'update_count_callback' => '_update_post_term_count',
		'query_var' => true,
		'rewrite' => array( 'slug' => 'crossland_projects' )
	);
	
	register_taxonomy('project_categories', 'projects', $args);
	register_taxonomy_for_object_type('project_categories', 'projects');
}*/
?>