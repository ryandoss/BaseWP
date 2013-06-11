/* Full Screen PILR Slider
 * Copyright (c) 2012 Ryan Doss.
 *
 * @version 1.01
 */
(function($)
{
	$.fn.pilrslider = function(d, m)
	{
		return this.each(function()
		{
			var o = $(this),
				methods = {
					init: function()
					{
						
						o.slides.each(function(i,s)
						{
							$(s).css({opacity:0,position:'absolute',zIndex:i});
						});
						
						o.slides.eq(o.currentIndex).css({opacity:1,zIndex:o.count});
						
						o.css({height:o.slides.eq(o.currentIndex).height()});
						
						if(o.settings.autoPlay && o.settings.pauseOnHover)
						{
							$(o.parent()).hover(function() {
								methods.pauseAuto();
								if(o.settings.showTimer)
									methods.pauseTimer();
								o.paused = true;
							}, function() { 
								if(o.settings.autoPlay)
									methods.startAuto();
								if(o.settings.showTimer)
									methods.startTimer();
								o.paused = false;
							});
						}
						
						// Show Naviation on Hover
						if(o.settings.showNavOnHover)
						{
								
							$(o.parent()).hover(function() {
								if(o.settings.showNumeric && o.count > 1)
								{
									o.controls.numeric.animate({opacity:1}, o.showNavSpeed);
								}
								if(o.settings.showDirectional && o.count > 1)
								{
									o.controls.arrow.animate({opacity:1}, o.showNavSpeed);
								}
							}, function() {
								if(o.settings.showNumeric && o.count > 1)
								{
									o.controls.numeric.animate({opacity:0}, o.showNavSpeed);
								}
								if(o.settings.showDirectional && o.count > 1)
								{
									o.controls.arrow.animate({opacity:0}, o.showNavSpeed);
								}
							});
						}
						
						if(o.count > 1)
						{
							methods.appendControls();
							
							if(o.settings.autoPlay)
								methods.startAuto();
								
							if(o.settings.showTimer)
								methods.startTimer();
						}
					},
					
					appendControls: function()
					{
						// Create timer
						if(o.settings.showTimer)
							o.timer = $('<div />').addClass('timer').css({position:'absolute', width:'0%', height:'8px', backgroundColor:'#fff', opacity:0.5, zIndex:1000}).insertAfter(o);
						
						// Show numeric controls
						if(o.settings.showNumeric)
						{
							o.controls.numeric = $('<div />').addClass('ctrl_nav').css({zIndex:o.count+1}).insertAfter(o);
							o.slides.each(function(i)
							{
								var a = i == o.currentIndex ? ' class="active_item"' : '';
								$('<a href=""' + a + ' data-show-slide="' + i + '">' + (i + 1) +'</a>').appendTo(o.controls.numeric);
							});
							
							// Activate numeric controls
							o.controls.numeric.find('a').bind('click', function(e)
							{
								e.preventDefault();
								if(o.animating)
									return;
								var a = $(this).data('show-slide');
								if(a != o.currentIndex)
									methods.nextItem(a);
							});
							if(o.settings.showNavOnHover)
								o.controls.numeric.css('opacity', 0);
						}

						// If Keypress is acitve create keyboard shortcuts
						if(o.settings.keyPress)
						{
							$(document).bind('keydown',function(e)
							{
								if(o.animating)
									return;
								var key = e.which;
								switch(key)
								{
									case 37:
										a = o.currentIndex <= 0 ? o.count-1 : o.currentIndex-1;
									break;
									case 39:
										a = o.currentIndex >= o.count-1 ? 0 : o.currentIndex+1;
									break;
									default:
									break;
								}
								if(a != o.currentIndex && (key == 37 || key == 39))
									methods.nextItem(a);
							});
						}
						
						// Show directional controls
						if(o.settings.showDirectional)
						{
							var a = ['previous', 'next'];
							o.controls.arrow = $('<div />').addClass('arrow_nav').css({zIndex:o.count+1}).insertBefore(o);
							for(i in a)
							{
								var c = ' class="arrow_' + a[i] + '"';
								$('<a href=""' + c + ' data-show-slide="' + a[i] + '" >' + a[i] + '</a>').prependTo(o.controls.arrow);
							}
							
							// Acivate directional controls
							o.controls.arrow.find('a').bind('click', function(e)
							{
								e.preventDefault();
								if(o.animating)
									return;
								var a = $(this).data('show-slide');
								switch(a)
								{
									case 'next':
										a = o.currentIndex >= o.count-1 ? 0 : o.currentIndex+1;
									break;
									case 'previous':
										a = o.currentIndex <= 0 ? o.count-1 : o.currentIndex-1;
									break;
								}
								if(a != o.currentIndex)
									methods.nextItem(a);
							});
							
							if(o.settings.showNavOnHover)
								o.controls.arrow.css('opacity', 0);
							
						}
					},
					
					nextItem: function(param)
					{
						methods.pauseAuto();
						if(o.settings.showTimer)
							methods.pauseTimer();
						
						o.nextIndex = o.currentIndex >= o.count-1 ? 0 : o.currentIndex+1;
						
						if(param != undefined)
							o.nextIndex = param;
						
						o.currentSlide = o.slides.eq(o.currentIndex).css({zIndex:o.currentIndex});
						o.nextSlide = o.slides.eq(o.nextIndex);
						
						if(o.settings.resizeSlide)
							o.nextSlide.parent().animate({height:o.nextSlide.height() - 3 + 'px'});
						
						if(o.settings.showNumeric)
						{
							o.controls.numeric.find('a').eq(o.currentIndex).removeClass('active_item');
							o.controls.numeric.find('a').eq(o.nextIndex).addClass('active_item');
						}
						
						o.animating = true;
						o.nextSlide.css({zIndex:o.count,opacity:0}).animate({opacity:1}, o.transition, function()
						{
							o.currentSlide.css({position:'absolute',zIndex:o.currentIndex}).animate({opacity:0}, o.transition);
							o.currentIndex = o.nextIndex;
							
							if(!o.paused)
							{
								if(o.settings.showTimer)
									methods.startTimer();
								if(o.settings.autoPlay)
									methods.startAuto();
							}
							o.animating = false;
						});
					},
					
					startTimer: function()
					{
						o.timer.animate({width:'100%'}, o.delay);
					},
					
					pauseTimer: function()
					{
						o.timer.stop();
						o.timer.css({width:'0%'});
					},
					
					startAuto: function()
					{
						o.timeout = setTimeout(function()
						{
							methods.nextItem();
						}, o.delay);
					},
					
					pauseAuto: function()
					{
						clearTimeout(o.timeout);
					}
				},
				defaults = {
					slides: 'li',
					start: 0,
					timeBetween: 6,
					transitionTime: 1,
					transitionType: 'fade',
					pauseOnHover: true,
					showDirectional: true,
					showNumeric: true,
					showTimer: true,
					showNavOnHover: true,
					showNavSpeed: .2,
					autoPlay: true,
					resizeSlide: true,
					keyPress: true
				};

			o.methods = $.extend(methods, $.fn.pilrslider.methods, m);
			o.settings = $.extend({}, defaults, $.fn.pilrslider.defaults, d);
			o.slides = o.find(o.settings.slides);
			o.count = o.slides.length,
			o.currentIndex = o.settings.start;
			o.currentSlide = o.slides.eq(o.currentIndex);
			o.nextIndex = 0;
			o.nextSlide = o.currentSlide;
			o.delay = o.settings.timeBetween * 1000,
			o.transition = o.settings.transitionTime * 1000,
			o.showNavSpeed = o.settings.showNavSpeed * 1000,
			o.controls = {},
			o.timer = {},
			o.paused = false,
			o.animating = false,
			o.timeout = false;
			
			if(o.count > 1)
				methods.init();
		});
	}
})(jQuery);