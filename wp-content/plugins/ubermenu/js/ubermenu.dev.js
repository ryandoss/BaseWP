/**
 * 
 * UberMenu JavaScript
 * 
 * @author Chris Mavricos, SevenSpark http://sevenspark.com
 * @version 2.0.0.1
 * Last Modified 2012-02-10
 * 
 */


var $u = jQuery;
var uberMenuWarning = false;

if( typeof uberMenuSettings != 'undefined' && uberMenuSettings.noconflict == 'on' ){
	//Settings may not be defined if using a caching program.
	$u = jQuery.noConflict();
}
else uberMenuWarning = true;

jQuery(document).ready(function($){
	
	//boolean-ify settings
	uberMenuSettings['removeConflicts'] = uberMenuSettings['removeConflicts'] == 'on' ? true : false;
	uberMenuSettings['noconflict'] = uberMenuSettings['noconflict'] == 'on' ? true : false;
	uberMenuSettings['autoAlign'] = uberMenuSettings['autoAlign'] == 'on' ? true : false;
	uberMenuSettings['fullWidthSubs'] = uberMenuSettings['fullWidthSubs'] == 'on' ? true : false;
	uberMenuSettings['androidClick'] = uberMenuSettings['androidClick'] == 'on' ? true : false;
	uberMenuSettings['loadGoogleMaps'] = uberMenuSettings['loadGoogleMaps'] == 'on' ? true : false;
	uberMenuSettings['hoverInterval'] = parseInt( uberMenuSettings['hoverInterval'] );
	uberMenuSettings['hoverTimeout'] = parseInt( uberMenuSettings['hoverTimeout'] );
	uberMenuSettings['speed'] = parseInt( uberMenuSettings['speed'] );

	//If we were supposed to run in noConflict mode, but didn't because the variable wasn't set to begin with, alert the user
	if( uberMenuWarning && uberMenuSettings['noconflict'] && typeof console != 'undefined' ){
		console.log('[UberMenu Notice] Not running in noConflict mode.  Are you using a caching plugin?  If so, you need to load the UberMenu scripts in the footer.');
	}
	
	//If this is Android, and we're using click for android, swap the trigger
	if( uberMenuSettings['androidClick'] ){
		var deviceAgent = navigator.userAgent.toLowerCase();
		if( deviceAgent.match(/(android)/) ){
			uberMenuSettings['trigger'] = 'click';
		}
	}
	
	//Client Side	
	var $menu = $u( '#megaMenu' );
	if( $menu.size() == 0 ) return;
	
	$menu.uberMenu( uberMenuSettings );
	var $um = $menu.data( 'uberMenu' );
	
	//Google Maps
	if(uberMenuSettings['loadGoogleMaps'] &&
	   typeof google !== 'undefined' &&
       typeof google.maps !== 'undefined' &&
       typeof google.maps.LatLng !== 'undefined') {
		$u('.spark-map-canvas').each(function(){
			
			var $canvas = $u(this);
			var dataZoom = $canvas.attr('data-zoom') ? parseInt($canvas.attr('data-zoom')) : 8;
			
			var latlng = $canvas.attr('data-lat') ? 
							new google.maps.LatLng($canvas.attr('data-lat'), $canvas.attr('data-lng')) :
							new google.maps.LatLng(40.7143528, -74.0059731);
					
			var myOptions = {
				zoom: dataZoom,
				mapTypeId: google.maps.MapTypeId.ROADMAP,
				center: latlng
			};
					
			var map = new google.maps.Map(this, myOptions);
			
			if($canvas.attr('data-address')){
				var geocoder = new google.maps.Geocoder();
				geocoder.geocode({ 
						'address' : $canvas.attr('data-address') 
					},
					function(results, status) {					
						if (status == google.maps.GeocoderStatus.OK) {
							map.setCenter(results[0].geometry.location);
							latlng = results[0].geometry.location;
							var marker = new google.maps.Marker({
								map: map,
								position: results[0].geometry.location,
								title: $canvas.attr('data-mapTitle')
							});
						}
				});
			}
			
			var $li = $u(this).parents( 'li.ss-nav-menu-item-depth-0' );
			var mapHandler = function(){
				google.maps.event.trigger(map, "resize");
				map.setCenter(latlng);
				//Only resize the first time we open
				$li.unbind( 'ubermenuopen', mapHandler );
			}			
			$li.bind( 'ubermenuopen', mapHandler );
		});
	}
	
});


;(function($) {

	$.uberMenu = function(el, options) {

		var defaults = {
			
			 speed			: 300
			,trigger		: 'hover'		//hover, hoverInterval, click
			,orientation 	: 'horizontal'	//horizontal, vertical
			,transition		: 'slide'		//slide, fade, none
			
			,hoverInterval	: 100
			,hoverTimeout	: 400
			,removeConflicts: true
			,autoAlign		: false
			
			//,maxSubmenuWidth: false
			,fullWidthSubs	: false
			
			,onOpen			: function(){}
		}
		
		var plugin = this;
		plugin.settings = {}

		var init = function() {
						
			plugin.settings = $u.extend({}, defaults, options);
			//console.log(plugin.settings);
			plugin.el = el;
			plugin.$megaMenu = $u(el);
			
			
			//Remove Conflicts - remove events and styles that might be added by the theme, as long as "Remove Conflicts" is not deactivated
			if( plugin.$megaMenu.hasClass( 'wpmega-noconflict' ) ){
				//$u('#megaMenu.wpmega-noconflict ul, #megaMenu.wpmega-noconflict ul li, #megaMenu.wpmega-noconflict ul li a')
				plugin.$megaMenu.find( 'ul, ul li, ul li a' ).removeAttr('style').unbind();
			}
					
			
			//Remove 'nojs'
			plugin.$megaMenu.removeClass('megaMenu-nojs').addClass('megaMenu-withjs');
			
			//Setup menus w/ subs
			$u('#megaMenu > ul > li:has(ul)').addClass('mega-with-sub');			
			
			//Setup flyout menus w/ subs
			$u('#megaMenu li.ss-nav-menu-reg li:has(> ul)').addClass('megaReg-with-sub');
			
						
			//Mega Menus
			var $megaItems = plugin.$megaMenu.find( 'ul.megaMenu > li.ss-nav-menu-mega.mega-with-sub' );
			
			//Setup Positioning
			if( !plugin.settings.fullWidthSubs ){
				positionMegaMenus( $megaItems , true );
				$u( window ).resize( function(){
					positionMegaMenus( $megaItems , false );	//reposition but don't re-align
				});
			}
			else{
				$megaItems.find( '> ul.sub-menu-1' ).hide();
			}
			
			switch( plugin.settings.trigger ){
				
				//Setup click items
				case 'click':
					$megaItems.find( '> a, > span.um-anchoremulator' )
						.click( 
							function(e){
								
								var $li = $u(this).parent('li');
							
								//Normal Links
								//if( $li.has('ul.sub-menu').size() == 0 ){ return true; };
									
								//Mega Drops
								e.preventDefault();	//No clicking allowed
								if( $li.hasClass( 'wpmega-expanded' ) ){
									$li.removeClass( 'wpmega-expanded' );
									closeSubMenu( $li.get(0) , false );
								}
								else{
									$li.addClass( 'wpmega-expanded' );
									showMega( $li.get(0) );
								}
								
							});
							
					break;
			
				//Setup hoverIntent items
				case 'hoverIntent':
					$megaItems
						.hoverIntent({
							
							over: function(){				
								showMega( this );
							}, 			
							out: function(e){
								if(typeof e === 'object' && $u(e.fromElement).is('#megaMenu form, #megaMenu input, #megaMenu select, #megaMenu textarea, #megaMenu label')){
									return; //Chrome has difficulty with Form element hovers
								}
								closeSubMenu( this , false);
							},				
							timeout: plugin.settings.hoverTimeout,
							interval: plugin.settings.hoverInterval,
							sensitivity: 2
							
						});
				
					break;
			
				//Setup Hover items
				case 'hover':
					$megaItems
						.hover( 
							function(){
								showMega( this );							
							},
							function(e){
								if(typeof e === 'object' && $u(e.fromElement).is('#megaMenu form, #megaMenu input, #megaMenu select, #megaMenu textarea, #megaMenu label')){
									return; //Chrome has difficulty with Form element hovers
								}
								closeSubMenu( this );
							});
							
					break;
			
			}
			
			//Flyout Menus
			var $flyItems = plugin.$megaMenu.find( 'ul.megaMenu > li.ss-nav-menu-reg.mega-with-sub, li.ss-nav-menu-reg li.megaReg-with-sub' );
			$flyItems.find( 'ul.sub-menu' ).hide();
			switch( plugin.settings.trigger ){
				
				//Setup click items
				case 'click':
					$flyItems.find( '> a, > span.um-anchoremulator' )
						.click( 
							function(e){
								
								var $li = $u(this).parent('li');
																	
								//Flyouts
								e.preventDefault();	//No clicking allowed
								e.stopPropagation();
								if( $li.hasClass( 'wpmega-expanded' ) ){
									$li.removeClass( 'wpmega-expanded' );
									closeSubMenu( $li.get(0) );
								}
								else{
									$li.addClass( 'wpmega-expanded' );
									showFlyout( $li.get(0) );
								}
								
							});
					break;
						
				//Setup HoverIntent items
				case 'hoverIntent':
					$flyItems
						.hoverIntent({
							
							over: function(){				
								showFlyout( this );
							}, 			
							out: function(e){
								if(typeof e === 'object' && $u(e.fromElement).is('#megaMenu form, #megaMenu input, #megaMenu select, #megaMenu textarea, #megaMenu label')){
									return; //Chrome has difficulty with Form element hovers
								}
								closeSubMenu( this , false);
							},				
							timeout: plugin.settings.hoverTimeout,
							interval: plugin.settings.hoverInterval,
							sensitivity: 2
							
						});
				
					break;
				
				//Setup hover items
				case 'hover':
				
					$flyItems.hover(
						function(){
							showFlyout( this );
						},
						function(){
							closeSubMenu( this );
						}
					);
					break;
				
			}
			
			
			//Close when body is clicked
			$u(document).click( function(e){
				closeAllSubmenus();				
			});
			//But not when the menu is clicked
			plugin.$megaMenu.click( function(e){
				e.stopPropagation();
			});
			
			//Mobile - iOS
			var deviceAgent = navigator.userAgent.toLowerCase();
			var is_iOS = deviceAgent.match(/(iphone|ipod|ipad)/);
			
			if (is_iOS) {
		        
		       plugin.$megaMenu.prepend('<a href="#" class="uber-close">&times;</a>'); // Close Submenu
		        
		        var $navClose = $u('.uber-close');
		        $navClose.hide().click(function(e){
		        	e.preventDefault();
		        	$u(this).hide();
		        });
				
		        plugin.$megaMenu.find('ul.megaMenu > li').hover(function(e){
		        	e.preventDefault();
		        	
	        		$navClose.css({
	        			left : $u(this).position().left + parseInt($u(this).css('marginLeft')) + 2
	        		}).show();
		        	
		        }, function(e){
		        	$navClose.hide();
		        });
		              
			}
			
			
			//Add last-child class
			
		}
		
		var positionMegaMenus = function( $megaItems , runAlignment ){
			
			plugin.menuEdge = plugin.settings.orientation == 'vertical' 
								? plugin.$megaMenu.find('> ul.megaMenu').offset().top 
								: plugin.$megaMenu.find('> ul.megaMenu').offset().left;
			var menuBarWidth = plugin.$megaMenu.find('> ul.megaMenu').outerWidth();
			var menuBarHeight = plugin.$megaMenu.find('> ul.megaMenu').outerHeight();
			
			$megaItems.each( function() {
				
				var $li = $u(this);
				var isOpen = $li.hasClass('megaHover');
				
				//Find submenu
				var $sub = $li.find( '> ul.sub-menu-1' );
								
				//AutoAlign
				if( runAlignment && plugin.settings.autoAlign ){
					var $subItems = $sub.find('li.ss-nav-menu-item-depth-1:not(.ss-sidebar)');	//subitems that aren't widget areas
					var maxColW = 0;
					$sub.css('left', '-999em').show();	//remove from view to inspect size
					$subItems.each(function(){
						if( $u(this).width() > maxColW ) maxColW = $u(this).width();
						//console.log( 'maxColW = ' + $u(this).width() );
					});	
					$subItems.width( maxColW );
					$sub.css( 'left', '' );
				}
				
				//Position centered submenus that are non-full-width
				switch( plugin.settings.orientation ){
					
					case 'horizontal':
					
						if( $u(this).hasClass( 'ss-nav-menu-mega-alignCenter' ) &&
							!$u(this).hasClass( 'ss-nav-menu-mega-fullWidth' ) ){
								
							var topWidth = $u(this).outerWidth();
							var subWidth = $sub.outerWidth();
							
							var centerLeft = ( $u(this).offset().left + ( topWidth / 2 ) )
										- ( plugin.menuEdge + ( subWidth / 2 ) );
							
							
							//If submenu is left of menuEdge
							var left = centerLeft > 0 ? centerLeft : 0;
							
							//If submenu is right of menuEdge
							if( left + subWidth > menuBarWidth ){
								//console.log( menuBarWidth + ' - ' + subWidth );
								left = menuBarWidth - subWidth;
							} 
							
							
							$sub.css({						
								left	: left
							});
						}
						break;
						
					case 'vertical':
					
						if( $u(this).hasClass( 'ss-nav-menu-mega-alignCenter' ) ){
							
							var topHeight = $u(this).outerHeight();
							var subHeight = $sub.outerHeight();
							
							var centerTop = ( $u(this).offset().top + ( topHeight / 2 ) )
										- ( plugin.menuEdge + ( subHeight / 2 ) );
							
							
							//If submenu is above menuEdge
							var top = centerTop > 0 ? centerTop : 0;
							
							//If submenu is below of menuEdge
							if( top + subHeight > menuBarHeight ){
								left = menuBarHeight - subHeight;
							} 
														
							$sub.css({						
								top	: top
							});
							
						}
					
						break;
						
				}
								
				//Hide the submenu
				if( !isOpen ) $sub.hide();
			});
			
		}
		
		//Private Methods
		var showMega = function( li ){
			
			var $li = $u(li);
			
			closeAllSubmenus( $li );
			
			$li.addClass('megaHover');

			var $subMenu = $li.find('ul.sub-menu-1');
						
			switch( plugin.settings.transition ){
				
				case 'slide':
					$subMenu.stop( true, true ).slideDown( plugin.settings.speed , 'swing' , function(){
						$li.trigger('ubermenuopen');
					} ); 
					break;
				
				case 'fade':
					$subMenu.stop( true, true ).fadeIn( plugin.settings.speed , 'swing' , function(){
						$li.trigger('ubermenuopen');
					} );
					break;
					
				case 'none':
					$subMenu.show();
					$li.trigger('ubermenuopen');
					break;
					
			}
			
		}
		
		var showFlyout = function( li ){
			
			var $li = $u(li);
			if( !$li.has('ul.sub-menu') ) return;
			
			//Top Level
			if( $li.hasClass( 'ss-nav-menu-reg' ) ) closeAllSubmenus( $li );
			//Sub Level
			else $li.siblings().each( function(){ closeSubMenu( this , true) } );	//auto close all siblings' sub-menus
			
			
			$li.addClass( 'megaHover' );

			var $subMenu = $li.find( '> ul.sub-menu' );
			
			
			switch( plugin.settings.transition ){
				
				case 'slide':
					$subMenu.stop( true, true ).slideDown( plugin.settings.speed , 'swing' , function(){
						$li.trigger('ubermenuopen');
					} );
					break;
				
				case 'fade':
					$subMenu.stop( true, true ).fadeIn( plugin.settings.speed , 'swing' , function(){
						$li.trigger('ubermenuopen');
					} );
					break;
					
				case 'none':
					$subMenu.show();
					$li.trigger('ubermenuopen');
					break;
					
			}
			
		}
		
		var closeSubMenu = function( li , immediate ){
			
			var $li = $u(li);
			
			var $subMenu = $li.find('> ul.sub-menu');
	
			if( immediate ){
				$subMenu.hide();
				$li.removeClass('megaHover').removeClass('wpmega-expanded');
				return;
			}
			
			if($subMenu.size() > 0){
								
				switch( plugin.settings.transition ){
				
					case 'slide':					
						$subMenu.stop( true, true ).slideUp( plugin.settings.speed , function(){
							$li.removeClass('megaHover').removeClass('wpmega-expanded');
							$li.trigger('ubermenuclose');
						});
						break;
						
					case 'fade':
					
						$subMenu.stop( true, true ).fadeOut( plugin.settings.speed , function(){
							$li.removeClass('megaHover').removeClass('wpmega-expanded');
							$li.trigger('ubermenuclose');
						});
						break;
						
					case 'none':
						$subMenu.hide();
						$li.removeClass('megaHover').removeClass('wpmega-expanded');
						$li.trigger('ubermenuclose');
						break;
						
					
				}
				
			}
			else $li.removeClass('megaHover').removeClass('wpmega-expanded');
			
		}
		
		var closeAllSubmenus = function( $not ){
			
			var $topItems = plugin.$megaMenu.find( '> ul.megaMenu > li' );
			
			if( $not != null ){
				$topItems = $topItems.not( $not );
			}
			
			$topItems
				.removeClass('megaHover').removeClass('wpmega-expanded')
				.find( '> ul.sub-menu' ).hide();
			
		}
		
		
		//Public Methods
		plugin.openMega = function( id ){
			showMega( id );
		}
		
		plugin.openFlyout = function( id ){
			showFlyout( id );
		}
		
		plugin.close = function( id , immediate ){
			if( !immediate ) immediate = false;
			closeSubMenu( id , immediate );
		}
		
		
		//Initialize
		init();
		
	}
	
	$.fn.uberMenu = function(options) {

        return this.each(function() {
            if ( undefined == $u(this).data( 'uberMenu' ) ){
                var uberMenu = new $.uberMenu( this, options );
                $u( this ).data( 'uberMenu', uberMenu );
            }
        });

    }


})( jQuery );


/* 
 * API Functions
 * Pass the top level menu item ID to control the submenu 
 */
function uberMenu_openMega( id ){
	var $uber = $u('#megaMenu').data( 'uberMenu' );
	$uber.openMega( id );
}

function uberMenu_openFlyout( id ){
	var $uber = $u('#megaMenu').data( 'uberMenu' );
	$uber.openFlyout( id );
}

function uberMenu_close( id ){
	var $uber = $u('#megaMenu').data( 'uberMenu' );
	$uber.close( id );
}
