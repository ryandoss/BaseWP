/***********************************************
 * UberMenu Demo JavaScript
 * 
 * @author Chris Mavricos, Sevenspark http://sevenspark.com
 * @version 1.0
 * Last modified 2011-01-18
 * 
 ***********************************************/

jQuery(document).ready(function($){
	
	/* Input Sliding Interface */
	$('.spark-admin-op input[type="checkbox"], #wpmega-demo .spark-admin-op input[type="radio"]')	//not(.ss-radio-gallery input)
		//.add('.spark-panel input[type="checkbox"], .spark-panel input[type="radio"]')
		.each(function(k, el){
		var tog = $(el).is(':checked') ? 'on' : 'off';
		var $toggle = $('<label class="ss-toggle-onoff '+tog+'" for="'+$(el).attr('id')+
							'"><span class="ss-toggle-inner"><span class="ss-toggle-on">On</span><span class="ss-toggle-mid"></span><span class="ss-toggle-off">Off</span></span></label>');
		
		
		switch($(el).attr('type')){
		
			case 'checkbox':
		
				$(el).after($toggle);
				$(el).hide();
				
				$toggle.click(function(){
					
					//console.log($(el).is(':checked') ? 'checked' : 'not checked');
					
					if($(el).is(':checked')){
						//console.log('checked');
						var $this = $(this);
						$this.find('.ss-toggle-inner').animate({
							'margin-left'	:	'-51px'
						}, 'normal', function(){
							$this.removeClass('on').addClass('off');
						});
						$(el).attr('checked', false);
					}
					else{
						//console.log('not checked');
						var $this = $(this);
						$this.find('.ss-toggle-inner').animate({
							'margin-left'	:	'0px'
						}, 'normal', function(){
							$this.removeClass('off').addClass('on');
						});
						$(el).attr('checked', true);
					}
					
					return false;	//stops the label click from reversing the check, which is necessary in IE
				});
				break;
				
			case 'radio' :
				var $label = $(el).next('label');
				var labelText = $label.html();
				$label.hide();
				//console.log(labelText);
				
				$(el).after('<span class="ss-tog-label">'+labelText+'</span>');
				$(el).after($toggle);				
				$(el).hide();
				
				$toggle.click(function(){
					if($(this).prev().is(':checked')){
						//Do nothing, it's double clicking a radio button
					}
					else{
						
						var oldID = $('input[name="'+$(el).attr('name')+'"]:checked').attr('id');
						
						//turn on
						var $this = $(this);
						$this.find('.ss-toggle-inner').animate({
							'margin-left'	:	'0px'
						}, 'normal', function(){
							$this.removeClass('off').addClass('on');
						});
						//$this.prev().attr('checked', true);
						$(el).attr('checked', true);
						
						//turn off the old
						$('label[for="'+oldID+'"] .ss-toggle-inner').animate({
							'margin-left'	:	'-51px'
						}, 'normal', function(){
							$(this).parent('label').removeClass('on').addClass('off');
						})
						.siblings('input[type="radio"]').attr('checked', false);
					}
					return false;
				});
				break;
		}
	});
});