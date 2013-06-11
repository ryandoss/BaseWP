jQuery.noConflict();
jQuery(function($) {
	// Remove outline on IE7
	$("a").each(function() {
		$(this).attr("hideFocus", "true").css("outline", "none");
	});
	
	// Show/Hide subnavigation
	$('#main_menu ul li').hover(
		function(){
			$('ul', this).css({display:"block"});
		}, function(){
			$('ul', this).css({display:"none"});
		}
	);
	
	// mobile nav
	$('#mobile_main_menu_btn').bind("click", function()
	{
		$(this).toggleClass('mobile_main_menu_btn_active');
		$('#main_mobile_menu').slideToggle();
	});
	
	// Animate scroll
	$('.scrollTop').bind('click', function()
	{
		$('body,html').animate({
			scrollTop:0
		}, 800);
	});
	
	// Home Slideshow
	$('.slider').pilrslider({
		slides: 'li',
		start: 0,
		timeBetween: 6,
		transitionTime: 1,
		transitionType: 'fade',
		pauseOnHover: true,
		showDirectional: true,
		showNumeric: true,
		showTimer: false,
		showNavOnHover: true,
		autoPlay: true,
		resizeSlide: true,
		keyPress: true
	});
	
	// Lazy Load images
	$(".content img").lazyload({ 
		effect : "fadeIn"
	});
});