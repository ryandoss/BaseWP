<?php 

//legacy
define('UBERMENU_NOTEXT', '--notext--');
define('UBERMENU_SKIP', '--divide--');
define('UBERMENU_DIVIDER', '<hr class="wpmega-divider" />'); // '<div class="wpmega-divider"></div>');

/*
 * Walker for the Front End UberMenu
 */
class UberMenuWalker extends Walker_Nav_Menu{

	private $index = 0;
	
	function start_lvl( &$output, $depth ) {
		$indent = str_repeat( "\t", $depth );
		$output .= "\n$indent<ul class=\"sub-menu sub-menu-".($depth+1)."\">\n";
	}
	
	function start_el( &$output, $item, $depth, $args ){
		
		global $uberMenu;
		$settings = $uberMenu->getSettings();
		
		//Test override settings
		$override = get_post_meta( $item->ID, '_menu_item_shortcode', true);
		$overrideOn = /*$depth > 0  && */ $settings->op( 'wpmega-shortcodes' ) && !empty( $override ) ? true : false;
		
		//Test sidebar settings
		$sidebar = get_post_meta( $item->ID, '_menu_item_sidebars', true);
		$sidebarOn = ( $settings->op( 'wpmega-top-level-widgets' ) || $depth > 0 ) && $settings->op( 'wpmega-sidebars' ) && !empty( $sidebar ) ? true : false;
		
		//For --Divides-- with no Content
		if(($item->title == '' || $item->title == UBERMENU_SKIP) && !$overrideOn  && !$sidebarOn ){ 
			if($item->title == UBERMENU_SKIP) $output.= '<li id="menu-item-'. $item->ID.'" class="wpmega-divider-container">'.UBERMENU_DIVIDER; //.'</li>'; 
			return; 
		}	//perhaps the filter should be called here
		          
		global $wp_query;
        $indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';
 
        //Handle class names depending on menu item settings
        $class_names = $value = '';
        $classes = empty( $item->classes ) ? array() : (array) $item->classes;
        
        //The Basics
        if($depth == 0) $classes[] = 'ss-nav-menu-item-'.$this->index++;
        $classes[] = 'ss-nav-menu-item-depth-'.$depth;
           
        //Megafy (top level)
        if($depth == 0 && get_post_meta( $item->ID, '_menu_item_isMega', true) != 'off' ){
        	$classes[] = 'ss-nav-menu-mega';
			
			//Full Width Submenus
			if( get_post_meta( $item->ID, '_menu_item_fullWidth', true ) == 'on' ){
				$classes[] = 'ss-nav-menu-mega-fullWidth';
				
				//Menu Item Columns
				$numCols = get_post_meta( $item->ID, '_menu_item_numCols', true );
				if( is_numeric( $numCols ) && $numCols <= 7 && $numCols > 0 ){
					$classes[] = 'mega-colgroup mega-colgroup-'.$numCols;
				}
			}
			
			//Submenu Alignment
			$alignment = get_post_meta( $item->ID, '_menu_item_alignSubmenu', true );	//center, right, left
			if( empty( $alignment ) ) $alignment = 'center';
			$classes[] = 'ss-nav-menu-mega-align'.ucfirst( $alignment );

        }
        else if($depth == 0) $classes[] = 'ss-nav-menu-reg';
        
        //Right Align
        if($depth == 0 && get_post_meta( $item->ID, '_menu_item_floatRight', true) == 'on' ) $classes[] = 'ss-nav-menu-mega-floatRight';
                
        //Second Level - Vertical Division
        if($depth == 1){
        	if(get_post_meta( $item->ID, '_menu_item_verticaldivision', true) == 'on' ) $classes[] = 'ss-nav-menu-verticaldivision';
        }
        
        //Third Level
        if($depth >= 2){
	        if(get_post_meta( $item->ID, '_menu_item_isheader', true) == 'on' ) $classes[] = 'ss-nav-menu-header';			//Headers
	        if(get_post_meta( $item->ID, '_menu_item_newcol', true) == 'on' ){												//New Columns
	        	$output.= '</ul></li>';
	        	$output.= '<li class="menu-item ss-nav-menu-item-depth-'.($depth-1).' sub-menu-newcol">'.
	        				'<span class="um-anchoremulator">&nbsp;</span><ul class="sub-menu sub-menu-'.$depth.'">';
	        }
        }
		
		//Highlight
		if( get_post_meta( $item->ID, '_menu_item_highlight', true ) == 'on' ) $classes[] = 'ss-nav-menu-highlight';		//Highlights
        
        //Thumbnail
        $thumb = $uberMenu->getImage( $item->ID, $settings->op( 'wpmega-image-width' ), $settings->op( 'wpmega-image-height' ) );
        if(!empty($thumb)) $classes[] = 'ss-nav-menu-with-img';
        
		
		//NoText, NoLink		
		$notext = get_post_meta( $item->ID, '_menu_item_notext', true ) == 'on' || $item->title == UBERMENU_NOTEXT ? true : false;
		$nolink = get_post_meta( $item->ID, '_menu_item_nolink', true ) == 'on' ? true : false;
		
		if( $notext ) $classes[] = 'ss-nav-menu-notext';
		if( $nolink ) $classes[] = 'ss-nav-menu-nolink';
        
        if( $sidebarOn ) $classes[] = 'ss-sidebar';
        if( $overrideOn ) $classes[] = 'ss-override';
        
		$prepend = '<span class="wpmega-link-title">';
        $append = '</span>';
        $description  = ! empty( $item->description ) ? '<span class="wpmega-item-description">'.esc_attr( $item->description ).'</span>' : '';
        
		if(	(	$depth == 0		&& 	!$settings->op( 'wpmega-description-0' ) )	||
			(	$depth == 1		&& 	!$settings->op( 'wpmega-description-1' ) )	||
			(	$depth >= 2		&& 	!$settings->op( 'wpmega-description-2' ) )  ){
        	$description = '';
        }
        
        if( !empty( $description ) ) $classes[] = 'ss-nav-menu-with-desc';
        
        $class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item ) );
        $class_names = ' class="'. esc_attr( $class_names ) . '"';

        $output .= $indent . '<li id="menu-item-'. $item->ID . '"' . $value . $class_names .'>';

        $attributes  = ! empty( $item->attr_title ) ? ' title="'  . esc_attr( $item->attr_title ) .'"' : '';
        $attributes .= ! empty( $item->target )     ? ' target="' . esc_attr( $item->target     ) .'"' : '';
        $attributes .= ! empty( $item->xfn )        ? ' rel="'    . esc_attr( $item->xfn        ) .'"' : '';
        $attributes .= ! empty( $item->url )        ? ' href="'   . esc_attr( $item->url        ) .'"' : '';
        //$attributes .= ! empty( $item->class )      ? ' class="'  . esc_attr( $item->class      ) .'"' : '';

        
		
        $item_output = '';
		
		/* Add title and normal link content - skip altogether if nolink and notext are both checked */
		if( !empty( $item->title ) && trim( $item->title ) != '' && !( $nolink && $notext ) ){
			
			//Determine the title
			$title = apply_filters( 'the_title', $item->title, $item->ID );
			if( $item->title == UBERMENU_NOTEXT || $notext ) $title = $prepend = $append = '';

			//Horizontal Divider automatically skips the link
			if( $item->title == UBERMENU_SKIP ){
    			$item_output.= UBERMENU_DIVIDER;
			}
			//A normal link or link emulator
			else{
				$item_output = $args->before;
				
				//To link or not to link?
				if( $nolink )  $item_output.= '<span class="um-anchoremulator" >';
				else $item_output.= '<a'. $attributes .'>';
								
					//Prepend Thumbnail
					$item_output.= $thumb;
				
					//Link Before (not added by UberMenu)
					if( !$nolink ) $item_output.= $args->link_before;
				
						//Text - Title
						if( !$notext ) $item_output.= $prepend . $title . $append;
				
						//Description
						$item_output.= $description;
				
					//Link After (not added by UberMenu)
					if( !$nolink ) $item_output.= $args->link_after;
				
				//Close Link or emulator
				if( $nolink ) $item_output.= '</span>'; 
				else $item_output.= '</a>';
				
				//Append after Link (not added by UberMenu)
		        $item_output .= $args->after;
			}
        }
		
		/* Add overrides and widget areas */
		if( $overrideOn || $sidebarOn ){
			$class = 'wpmega-nonlink';
        	
			//Get the widget area or shortcode
        	$gooeyCenter = '';
			//Content Overrides
        	if( $overrideOn ){
        		$gooeyCenter = do_shortcode( $override );
        	}
        	//Widget Areas
        	if( $sidebarOn ){
        		$class.= ' wpmega-widgetarea ss-colgroup-'.$uberMenu->sidebarCount( $sidebar );	
        		$gooeyCenter = $uberMenu->sidebar( $sidebar );
        	}
        	
        	$item_output.= '<div class="'.$class.'">';
        	$item_output.= $gooeyCenter;
        	$item_output.= '<div class="clear"></div>';
        	$item_output.= '</div>';
		}
		
       	$output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
		
	}
}


class UberMenuWalkerEdit extends Walker_Nav_Menu  {
	
	/**
	 * @see Walker_Nav_Menu::start_lvl()
	 * @since 3.0.0
	 *
	 * @param string $output Passed by reference.
	 */
	function start_lvl(&$output) {}

	/**
	 * @see Walker_Nav_Menu::end_lvl()
	 * @since 3.0.0
	 *
	 * @param string $output Passed by reference.
	 */
	function end_lvl(&$output) {
	}

	/**
	 * @see Walker::start_el()
	 * @since 3.0.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param object $item Menu item data object.
	 * @param int $depth Depth of menu item. Used for padding.
	 * @param object $args
	 */
	function start_el(&$output, $item, $depth, $args) {
		global $_wp_nav_menu_max_depth;
		$_wp_nav_menu_max_depth = $depth > $_wp_nav_menu_max_depth ? $depth : $_wp_nav_menu_max_depth;

		$indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';

		ob_start();
		$item_id = esc_attr( $item->ID );
		$removed_args = array(
			'action',
			'customlink-tab',
			'edit-menu-item',
			'menu-item',
			'page-tab',
			'_wpnonce',
		);

		$original_title = '';
		if ( 'taxonomy' == $item->type ) {
			$original_title = get_term_field( 'name', $item->object_id, $item->object, 'raw' );
			if ( is_wp_error( $original_title ) )
				$original_title = false;
		} elseif ( 'post_type' == $item->type ) {
			$original_object = get_post( $item->object_id );
			$original_title = $original_object->post_title;
		}

		$classes = array(
			'menu-item menu-item-depth-' . $depth,
			'menu-item-' . esc_attr( $item->object ),
			'menu-item-edit-' . ( ( isset( $_GET['edit-menu-item'] ) && $item_id == $_GET['edit-menu-item'] ) ? 'active' : 'inactive'),
		);

		$title = $item->title;

		if ( ! empty( $item->_invalid ) ) {
			$classes[] = 'menu-item-invalid';
			/* translators: %s: title of menu item which is invalid */
			$title = sprintf( __( '%s (Invalid)' ), $item->title );
		} elseif ( isset( $item->post_status ) && 'draft' == $item->post_status ) {
			$classes[] = 'pending';
			/* translators: %s: title of menu item in draft status */
			$title = sprintf( __('%s (Pending)'), $item->title );
		}

		$title = empty( $item->label ) ? $title : $item->label;

		?>
		<li id="menu-item-<?php echo $item_id;?>" class="<?php echo implode(' ', $classes);?>">
			<dl class="menu-item-bar">
				<dt class="menu-item-handle">
					<span class="item-title"><?php echo esc_html($title);?></span>
					<span class="item-controls">
						<span class="item-type"><?php echo esc_html($item -> type_label);?></span>
						<span class="item-order hide-if-js">
							<a href="<?php
							echo wp_nonce_url(add_query_arg(array('action' => 'move-up-menu-item', 'menu-item' => $item_id, ), remove_query_arg($removed_args, admin_url('nav-menus.php'))), 'move-menu_item');
							?>" class="item-move-up"><abbr title="<?php esc_attr_e('Move up');?>">&#8593;</abbr></a>
							|
							<a href="<?php
							echo wp_nonce_url(add_query_arg(array('action' => 'move-down-menu-item', 'menu-item' => $item_id, ), remove_query_arg($removed_args, admin_url('nav-menus.php'))), 'move-menu_item');
							?>" class="item-move-down"><abbr title="<?php esc_attr_e('Move down');?>">&#8595;</abbr></a>
						</span>
						<a class="item-edit" id="edit-<?php echo $item_id;?>" title="<?php esc_attr_e('Edit Menu Item');?>" href="<?php
							echo ( isset( $_GET['edit-menu-item'] ) && $item_id == $_GET['edit-menu-item'] ) ? admin_url( 'nav-menus.php' ) : add_query_arg( 'edit-menu-item', $item_id, remove_query_arg( $removed_args, admin_url( 'nav-menus.php#menu-item-settings-' . $item_id ) ) );
						?>"><?php _e('Edit Menu Item');?></a>
					</span>
				</dt>
			</dl>

			<div class="menu-item-settings" id="menu-item-settings-<?php echo $item_id;?>">
				<?php if( 'custom' == $item->type ) : ?>
					<p class="field-url description description-wide">
						<label for="edit-menu-item-url-<?php echo $item_id;?>">
							<?php _e('URL');?><br />
							<input type="text" id="edit-menu-item-url-<?php echo $item_id;?>" class="widefat code edit-menu-item-url" name="menu-item-url[<?php echo $item_id;?>]" value="<?php echo esc_attr($item -> url);?>" />
						</label>
					</p>
				<?php endif;?>
				<p class="description description-thin">
					<label for="edit-menu-item-title-<?php echo $item_id;?>">
						<?php _e('Navigation Label');?><br />
						<input type="text" id="edit-menu-item-title-<?php echo $item_id;?>" class="widefat edit-menu-item-title" name="menu-item-title[<?php echo $item_id;?>]" value="<?php echo esc_attr($item -> title);?>" />
					</label>
				</p>
				<p class="description description-thin">
					<label for="edit-menu-item-attr-title-<?php echo $item_id;?>">
						<?php _e('Title Attribute');?><br />
						<input type="text" id="edit-menu-item-attr-title-<?php echo $item_id;?>" class="widefat edit-menu-item-attr-title" name="menu-item-attr-title[<?php echo $item_id;?>]" value="<?php echo esc_attr($item -> post_excerpt);?>" />
					</label>
				</p>
				<p class="field-link-target description">
					<label for="edit-menu-item-target-<?php echo $item_id;?>">
						<input type="checkbox" id="edit-menu-item-target-<?php echo $item_id;?>" value="_blank" name="menu-item-target[<?php echo $item_id;?>]"<?php checked($item -> target, '_blank');?> />
						<?php _e('Open link in a new window/tab');?>
					</label>
				</p>
				<p class="field-css-classes description description-thin">
					<label for="edit-menu-item-classes-<?php echo $item_id;?>">
						<?php _e('CSS Classes (optional)');?><br />
						<input type="text" id="edit-menu-item-classes-<?php echo $item_id;?>" class="widefat code edit-menu-item-classes" name="menu-item-classes[<?php echo $item_id;?>]" value="<?php echo esc_attr(implode(' ', $item -> classes));?>" />
					</label>
				</p>
				<p class="field-xfn description description-thin">
					<label for="edit-menu-item-xfn-<?php echo $item_id;?>">
						<?php _e('Link Relationship (XFN)');?><br />
						<input type="text" id="edit-menu-item-xfn-<?php echo $item_id;?>" class="widefat code edit-menu-item-xfn" name="menu-item-xfn[<?php echo $item_id;?>]" value="<?php echo esc_attr($item -> xfn);?>" />
					</label>
				</p>
				<p class="field-description description description-wide">
					<label for="edit-menu-item-description-<?php echo $item_id;?>">
						<?php _e('Description');?><br />
						<textarea id="edit-menu-item-description-<?php echo $item_id;?>" class="widefat edit-menu-item-description" rows="3" cols="20" name="menu-item-description[<?php echo $item_id;?>]"><?php echo esc_html($item -> description);
							// textarea_escaped
 ?></textarea>
						<span class="description"><?php _e('The description will be displayed in the menu if the current theme supports it.');?></span>
					</label>
				</p>
				
				<?php do_action('ubermenu_menu_item_options', $item_id);?>

				<div class="menu-item-actions description-wide submitbox">
					<?php if( 'custom' != $item->type && $original_title !== false ) : ?>
						<p class="link-to-original">
							<?php printf(__('Original: %s'), '<a href="' . esc_attr($item -> url) . '">' . esc_html($original_title) . '</a>');?>
						</p>
					<?php endif;?>
					<a class="item-delete submitdelete deletion" id="delete-<?php echo $item_id;?>" href="<?php
					echo wp_nonce_url(add_query_arg(array('action' => 'delete-menu-item', 'menu-item' => $item_id, ), remove_query_arg($removed_args, admin_url('nav-menus.php'))), 'delete-menu_item_' . $item_id);
 ?>"><?php _e('Remove');?></a> <span class="meta-sep"> | </span> <a class="item-cancel submitcancel" id="cancel-<?php echo $item_id;?>" href="<?php	echo esc_url(add_query_arg(array('edit-menu-item' => $item_id, 'cancel' => time()), remove_query_arg($removed_args, admin_url('nav-menus.php'))));?>#menu-item-settings-<?php echo $item_id;?>"><?php _e('Cancel');?></a>
				</div>

				<input class="menu-item-data-db-id" type="hidden" name="menu-item-db-id[<?php echo $item_id;?>]" value="<?php echo $item_id;?>" />
				<input class="menu-item-data-object-id" type="hidden" name="menu-item-object-id[<?php echo $item_id;?>]" value="<?php echo esc_attr($item -> object_id);?>" />
				<input class="menu-item-data-object" type="hidden" name="menu-item-object[<?php echo $item_id;?>]" value="<?php echo esc_attr($item -> object);?>" />
				<input class="menu-item-data-parent-id" type="hidden" name="menu-item-parent-id[<?php echo $item_id;?>]" value="<?php echo esc_attr($item -> menu_item_parent);?>" />
				<input class="menu-item-data-position" type="hidden" name="menu-item-position[<?php echo $item_id;?>]" value="<?php echo esc_attr($item -> menu_order);?>" />
				<input class="menu-item-data-type" type="hidden" name="menu-item-type[<?php echo $item_id;?>]" value="<?php echo esc_attr($item -> type);?>" />
			</div><!-- .menu-item-settings-->
			<ul class="menu-item-transport"></ul>
		<?php
		$output .= ob_get_clean();
	}
}

	
