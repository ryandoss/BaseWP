(function($){
	$(document).ready(function(){
		var $et_lb_tab_link = $('.et_lb_tabs_nav a'),
			$et_lb_simple_slider_nav = $('.et_lb_simple_slider_nav a');
		
		if ( $.fn.fitVids )
			$(".et_lb_module").fitVids();

		if ( $.fn.fancybox && $.easing )
			jQuery(".et_lb_image_box a[class*=fancybox]").fancybox({
				'overlayOpacity'	:	0.7,
				'overlayColor'		:	'#000000',
				'transitionIn'		: 'elastic',
				'transitionOut'		: 'elastic',
				'easingIn'      	: 'easeOutBack',
				'easingOut'     	: 'easeInBack',
				'speedIn' 			: '700',
				'centerOnScroll'	: true
			});
		
		$('.et_lb_toggle_title').click(function(){
			var $this_heading = $(this),
				$module = $this_heading.closest('.et_lb_toggle'),
				$content = $module.find('.et_lb_toggle_content');
			
			$content.slideToggle(700);
			
			if ( $module.hasClass('et_lb_toggle_close') ){				
				$module.removeClass('et_lb_toggle_close').addClass('et_lb_toggle_open');
			} else {
				$module.removeClass('et_lb_toggle_open').addClass('et_lb_toggle_close');
			}
		});
		
		$et_lb_tab_link.click( function(){
			var $this_link = $(this),
				$et_tab = $this_link.closest('.et_lb_tabs').find('.et_lb_tab'),
				active_tab_class = 'et_lb_tab_active',
				animation_speed = 300,
				active_tab_num,
				new_tab_num;
			
			if ( $this_link.parent('li').hasClass(active_tab_class) ) return false;
			if( $et_tab.is(':animated') ) return false;
			
			active_tab_num = $this_link.closest('ul').find('.'+active_tab_class).prevAll().length;
			new_tab_num = $this_link.parent('li').prevAll().length;
			
			$et_tab.eq(active_tab_num).animate( { opacity : 0 }, animation_speed, function(){
				$(this).css('display','none');
				$this_link.parent('li').addClass(active_tab_class).siblings().removeClass(active_tab_class);
				$et_tab.eq(new_tab_num).css({'display' : 'block', opacity : 0}).animate( { opacity : 1 }, animation_speed );
			} );
			
			return false;
		} );
		
		$et_lb_simple_slider_nav.click( function(){
			var $this_nav_link = $(this),
				$this_simple_slider = $this_nav_link.closest('.et_lb_simple_slider'),
				$et_simple_slide = $this_simple_slider.find('.et_lb_simple_slide'),
				active_tab_class = 'et_lb_simple_slide_active',
				slider_animation_speed = 300,
				slides_num = $et_simple_slide.length,
				active_slide_num,
				new_slide_num;
			
			if( $et_simple_slide.is(':animated') ) return false;
			
			active_slide_num = $this_simple_slider.find('.'+active_tab_class).prevAll().length;
			if ( $this_nav_link.hasClass('et_lb_simple_slider_prev') ){
				new_slide_num = ( active_slide_num - 1 ) < 0 ? slides_num - 1 : active_slide_num - 1;
			} else {
				new_slide_num = ( active_slide_num + 1 ) == slides_num ? 0 : active_slide_num + 1;
			}
			
			$et_simple_slide.eq(active_slide_num).animate( { opacity : 0 }, slider_animation_speed, function(){
				$et_simple_slide.removeClass(active_tab_class);
				$(this).css('display','none');

				$et_simple_slide.eq(new_slide_num).addClass(active_tab_class).css({'display' : 'block', opacity : 0}).animate( { opacity : 1 }, slider_animation_speed );
			} );
			
			return false;
		} );
		
		$('.et_lb_image_box a').hover( 
			function(){
				$(this).find('.et_lb_zoom_icon').css({ 'display' : 'block', 'opacity' : 0 }).animate( { opacity : 1 }, 300 );
			},
			function(){
				$(this).find('.et_lb_zoom_icon').animate( { opacity : 0 }, 300 );
			}
		);
		
		$('.et_lb_note-video iframe').each( function(){
			var src_attr = $(this).attr('src'),
				wmode_character = src_attr.indexOf( '?' ) == -1 ? '?' : '&amp;',
				this_src = src_attr + wmode_character + 'wmode=opaque';
			
			$(this).attr('src',this_src);
		} );
	});
	
	$(window).load(function(){
		var $et_lb_featured = $('.flex-container');
		
		if ( $et_lb_featured.length ){
			$et_lb_featured.each( function(){
				var $this_slider = $(this),
					this_slider_class = $this_slider.attr('class'),
					et_slider_effect = /et_lb_slider_effect_(\w+)/g.exec( this_slider_class ),
					et_slider_animation_duration = /et_lb_slider_animation_duration_(\d+)/g.exec( this_slider_class ),
					et_slider_animation_autospeed = /et_lb_slider_animation_autospeed_(\d+)/g.exec( this_slider_class ),
					et_lb_slider_settings, this_slider_width, $this_slider_control, this_slider_control_width, this_slider_controls_left;

				et_lb_slider_settings = { slideshow: false }
				
				if ( et_slider_effect ) et_lb_slider_settings.animation = et_slider_effect[1];
				if ( et_slider_animation_duration ) et_lb_slider_settings.animationDuration = et_slider_animation_duration[1];
				if ( et_slider_animation_autospeed ) et_lb_slider_settings.slideshowSpeed = et_slider_animation_autospeed[1];
				if ( $this_slider.is('.et_lb_slider_animation_auto_on') ) et_lb_slider_settings.slideshow = true;
				if ( $this_slider.is('.et_lb_slider_pause_hover_on') ) et_lb_slider_settings.pauseOnHover = true;
				
				$this_slider.flexslider( et_lb_slider_settings );
				
				$this_slider_control = $this_slider.find('.flex-control-nav');
				this_slider_width = $this_slider.innerWidth();
				this_slider_control_width = $this_slider_control.innerWidth();
				this_slider_controls_left = ( this_slider_width - this_slider_control_width ) / 2;
				
				$this_slider_control.css( { 'left' : this_slider_controls_left } );
			} );
		}	
	});
})(jQuery)