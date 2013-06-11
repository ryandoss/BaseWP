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
	
	// Animate scroll
	$('.scrollTop').bind('click', function()
	{
		$('body,html').animate({
			scrollTop:0
		}, 800);
	});
	
	// Lazy Load images
	$(".content img").lazyload({ 
		effect : "fadeIn"
	});
	
	// Slideshow
	$(".slideshow").slider({
		slides: 'li',
		start: 0,
		timeBetween: 6,
		transitionTime: .5,
		pauseOnHover: false,
		showDirectional: false,
		showNumeric: false,
		showTimer: true,
		showNavOnHover: false,
		autoPlay: true,
		resizeSlide: false,
		keyPress: true
	});
	
	// Infobox
	$('.info_box').hover(
		function() {
			$('.overlay', this).css({opacity:0, display:'block'}).animate({opacity:1});
		}, function() {
			$('.overlay', this).animate({opacity:0});
	});
	
	// Load next posts page via AJAX
	$('.pagination a').click(function(e)
	{
		e.preventDefault();
		var url = $(this).attr('href');
		
	});
});
