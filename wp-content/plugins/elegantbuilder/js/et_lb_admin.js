(function($){
	$(function() {
		var et_builder_width = $('#et_layout').width(),
			$et_builder_add_links = $( '#et_builder_controls a.et_add_element' ),
			$et_main_save_button = $( '#et_lb_main_save' ),
			et_module_settings_clicked = false,
			et_hidden_editor_object = tinyMCEPreInit.mceInit['et_lb_hidden_editor'],
			et_page_builder_original_width = 742,
			et_main_module_width = 0;
		
		$( 'body' ).delegate( 'span.et_settings_arrow', 'click', function(){
			var $this_setting_link = $(this),
				$settings_window = $('#active_module_settings'),
				$et_active_module = $this_setting_link.closest('.et_module');
				
			if ( et_module_settings_clicked ) return false;
			else et_module_settings_clicked = true;
			
			$('#et_layout .et_module').css( 'z-index', '1' );
			
			if ( $('#et_modules').is(':hidden') ) $et_builder_add_links.eq(0).trigger('click');
			
			$.ajax({
				type: "POST",
				url: et_lb_options.ajaxurl,
				data:
				{
					action : 'et_show_module_options',
					et_load_nonce : et_lb_options.et_load_nonce,
					et_module_class : $(this).closest('.et_module').attr('class'),
					et_modal_window : 0,
					et_module_exact_name : $(this).closest('.et_module').attr('data-placeholder')
				},
				error: function( xhr, ajaxOptions, thrownError ){
					et_module_settings_clicked = false;
				},
				success: function( data ){
					$et_main_save_button.hide();
					$et_active_module.addClass('et_active');
					
					$settings_window.hide().append(data).slideDown();
					$settings_window.find('.html-active').removeClass('html-active').addClass('tmce-active');
					$('#et_module_separator').show();
					
					$('#et_layout .et_module:not(.et_active,.et_m_column)').css('opacity',0.5);
					$('html:not(:animated),body:not(:animated)').animate({ scrollTop: $('#et_page_builder').offset().top - 82 }, 500);
					
					et_deactivate_ui_actions();
					et_module_settings_clicked = false;
					
					$( '#et_module_settings .et_lb_option' ).each( function(){
						var $this_option = $(this),
							this_option_id = $this_option.attr('id'),
							$found_element = $et_active_module.find('.et_module_settings .et_module_setting.' + this_option_id);
						
						if ( $found_element.length ){
							if ( $this_option.is('select') ){
								$this_option.find("option[value='" + $found_element.html() + "']").attr('selected','selected');
							} else if ( $this_option.is('input') ){
								$this_option.val( $found_element.html() );
							} else { 
								$this_option.html( $found_element.html() );
							}
						}
						
						if ( $this_option.hasClass('et_lb_wp_editor') && typeof tinyMCE !== "undefined" ) {
							tinyMCE.execCommand( "mceAddControl", true, this_option_id );
							quicktags( { id : this_option_id } );
							et_init_new_editor( this_option_id );							
						}
						
						et_init_sortable_attachments();
					} );
					
					if ( $et_active_module.hasClass('et_m_tabs') || $et_active_module.hasClass('et_m_simple_slider') ){
						$( '#et_module_settings #et_lb_tabs .wp-editor-wrap' ).each( function(index,value){
							var $this_div = $(this),
								this_editor_content = $this_div.html();
							
							$.ajax({
								type: "POST",
								url: et_lb_options.ajaxurl,
								async: false, // asynchronous requests might result in errors if there are a lot of tabs to render
								data:
								{
									action : 'et_convert_div_to_editor',
									et_load_nonce : et_lb_options.et_load_nonce,
									et_index : index
								},
								success: function( response ){
									var current_tab_id = 'et_tab_text_' + index;
									
									$this_div.closest('.et_lb_tab').find('a.et_lb_delete_tab').before( response );
									
									if ( typeof tinyMCE !== "undefined" ){
										tinyMCE.execCommand( "mceAddControl", true, current_tab_id );
										quicktags( { id : current_tab_id } );
										et_init_new_editor( current_tab_id );
										
										tinyMCE.getInstanceById( current_tab_id ).execCommand( "mceInsertContent", false, this_editor_content );
									} else {
										$this_div.closest('.et_lb_tab').find( '#' + current_tab_id ).val( this_editor_content );
									}
									$this_div.remove();
									
									et_make_editor_droppable();
									
									et_track_active_editor();
								}
							});
						} );
						
						if ( $( '#et_module_settings #et_lb_tabs .et_tabs_data-elements' ).length ){
							$( '#et_module_settings #et_lb_tabs' ).attr( 'data-elements', $( '#et_module_settings #et_lb_tabs .et_tabs_data-elements' ).val() );
							$( '#et_module_settings #et_lb_tabs .et_tabs_data-elements' ).remove();
						}
						et_init_sortable_tabs();
					}
					
					et_track_active_editor();
				}
			});
		} );
		
		$( 'body' ).delegate( 'span.et_delete, span.et_delete_column', 'click', function(){
			var $this_delete_button = $(this);
			
			if ( $this_delete_button.hasClass('et_delete') ){
				if ( $this_delete_button.find('.et_lb_delete_confirmation').length ){ 
					$this_delete_button.find('.et_lb_delete_confirmation').remove();
				} else { 
					$this_delete_button.append( '<span class="et_lb_delete_confirmation">' + '<span>' + et_lb_options.confirm_message + '</span>' + '<a href="#" class="et_lb_delete_confirm_yes">' + et_lb_options.confirm_message_yes + '</a><a href="#" class="et_lb_delete_confirm_no">' + et_lb_options.confirm_message_no + '</a></span>' );
				}
				return false;
			}
			
			et_lb_delete_module( $this_delete_button.closest('.et_module') );
		} );
		
		$( 'body' ).delegate( '.et_user_layout_delete', 'click', function(){
			var $this_delete_button = $(this);
						
			if ( $this_delete_button.find('.et_lb_delete_confirmation').length ){ 
				$this_delete_button.find('.et_lb_delete_confirmation').remove();
			} else { 
				$this_delete_button.append( '<span class="et_lb_delete_confirmation">' + '<span>' + et_lb_options.confirm_custom_layout_delete_message + '</span>' + '<a href="#" class="et_lb_delete_confirm_yes">' + et_lb_options.confirm_message_yes + '</a><a href="#" class="et_lb_delete_confirm_no">' + et_lb_options.confirm_message_no + '</a></span>' );
			}
			return false;
		} );
		
		$( 'body' ).delegate( '.et_lb_delete_confirm_yes', 'click', function(){
			var $this_button = $(this);
			
			if ( $this_button.closest('#et_lb_clear_all_wrapper').length ){
				$('#et_layout').html( '' );
				$('#et_lb_helper').show();
				$this_button.closest('.et_lb_delete_confirmation').remove();
				et_layout_save( true );
			} else if ( $this_button.closest('.et_sample_layout').length ) {
				$.ajax({
					type: "POST",
					url: et_lb_options.ajaxurl,
					data:
					{
						action : 'et_delete_sample_layout',
						et_load_nonce : et_lb_options.et_load_nonce,
						et_layout_key : $this_button.closest('.et_sample_layout').attr('data-name')
					},
					success: function( data ){
						$this_button.closest('.et_sample_layout').remove();
					}
				});
			} else if ( $this_button.closest('#et_lb_create_layout_wrapper').length && $this_button.siblings('#et_lb_new_layout_name').val() != '' ) {
				var layout_html = $('#et_layout').html(),
					$save_message = jQuery("#et_lb_ajax_save");
			
				$.ajax({
					type: "POST",
					url: et_lb_options.ajaxurl,
					data:
					{
						action : 'et_create_new_sample_layout',
						et_load_nonce : et_lb_options.et_load_nonce,
						et_layout_html : layout_html,
						et_new_layout_name : $this_button.siblings('#et_lb_new_layout_name').val()
					},
					beforeSend: function ( xhr ){
						$save_message.children("img").css("display","block");
						$save_message.children("span").css("margin","6px 0px 0px 30px").html( et_lb_options.saving_text );
						$save_message.fadeIn('fast');
					},
					success: function( data ){
						$save_message.children("img").css("display","none");
						$save_message.children("span").css("margin","0px").html( et_lb_options.saved_text );
						
						setTimeout(function(){
							$save_message.fadeOut("slow");
						},500);
						
						$this_button.closest('.et_lb_delete_confirmation').remove();
					}
				});
			} else {
				et_lb_delete_module( $(this).closest('.et_module') );
			}
			
			return false;
		} );
		
		$( '#et_lb_clear_all' ).click( function(){
			var $this_button = $(this);
						
			if ( $this_button.siblings('.et_lb_delete_confirmation').length ){ 
				$this_button.siblings('.et_lb_delete_confirmation').remove();
			} else { 
				$this_button.closest('span').append( '<span class="et_lb_delete_confirmation">' + '<span>' + et_lb_options.confirm_clear_all_message + '</span>' + '<a href="#" class="et_lb_delete_confirm_yes">' + et_lb_options.confirm_message_yes + '</a><a href="#" class="et_lb_delete_confirm_no">' + et_lb_options.confirm_message_no + '</a></span>' );
			}
			
			return false;
		} );
		
		$( '#et_lb_create_layout' ).click( function(){
			var $this_button = $(this);
						
			if ( $this_button.siblings('.et_lb_delete_confirmation').length ){ 
				$this_button.siblings('.et_lb_delete_confirmation').remove();
			} else { 
				$this_button.closest('span').append( '<span class="et_lb_delete_confirmation">' + '<label for="et_lb_new_layout_name">' + et_lb_options.create_layout_name + ':</label>' + '<input type="text" value="" id="et_lb_new_layout_name" name="et_lb_new_layout_name" />' + '<small>' + et_lb_options.create_layout_description_text + '</small>' + '<a href="#" class="et_lb_delete_confirm_yes">' + et_lb_options.create_layout_confirm_message_yes + '</a><a href="#" class="et_lb_delete_confirm_no">' + et_lb_options.create_layout_confirm_message_no + '</a></span>' );
			}
			
			return false;
		} );
		
		$(document).on("keypress", "#et_lb_new_layout_name", function(e) {
			// if the user hits enter, create new sample layout and make sure the form isn't submitted
			if ( e.which == 13 ) {
				$(this).siblings( '.et_lb_delete_confirm_yes' ).trigger( 'click' );
				return false;
			}
		});
		
		$( 'body' ).delegate( '#et_lb_secondary_buttons .et_lb_delete_confirm_no', 'click', function(){
			$(this).closest('.et_lb_delete_confirmation').remove();
			
			return false;
		} );
		
		$( 'body' ).delegate( '#et_close_dialog_settings', 'click', function(){
			var $et_dialog_form = $('form#et_dialog_settings');
			
			$et_dialog_form.find('.et_lb_wp_editor').each( function(){
				if ( typeof tinyMCE !== "undefined" ) tinyMCE.execCommand("mceRemoveControl", false, $(this).attr('id'));
			} );
			
			et_close_modal_window();
			
			return false;
		});
		
		$( 'body' ).delegate( 'form#et_module_settings input#submit, #et_close_module_settings', 'click', function(){
			var $et_active_module_settings = $('.et_active .et_module_settings');
			
			$et_active_module_settings.empty();
			$et_main_save_button.show();
			
			$('form#et_module_settings .et_lb_option').each( function(){
				var et_option_value, et_option_class,
					this_option_id = $(this).attr('id');
				
				et_option_class = this_option_id + ' et_module_setting';
				
				if ( $(this).is('#et_lb_tabs') || $(this).is('#et_lb_slides') ){
					$(this).find('.et_lb_tab_title').each(function(){
						var this_value = $(this).val();
						$(this).attr('value', this_value);
					});
					$(this).find('.et_lb_wp_editor').each(function(){
						var $this_textarea = $(this),
							this_value = $this_textarea.val(),
							this_value_id = $this_textarea.attr('id'),
							this_editor_content;
						
						if ( typeof tinyMCE !== "undefined" ){						
							this_editor_content = $this_textarea.is(':hidden') ? tinyMCE.get( this_value_id ).getContent() : switchEditors.wpautop( tinymce.DOM.get( this_value_id ).value );
							
							tinyMCE.execCommand("mceRemoveControl", false, this_value_id);
						} else {
							this_editor_content = $this_textarea.val();
						}
						$this_textarea.closest('.wp-editor-wrap').html( this_editor_content );
					});
					
					et_option_value = $(this).html();
					et_option_value += '<input type="hidden" class="et_tabs_data-elements" value="' + $(this).find('.et_lb_tab').length + '" />';
				}
				else if ( $(this).hasClass('et_lb_wp_editor') ){
					if ( typeof tinyMCE !== "undefined" ){
						et_option_value = $(this).is(':hidden') ? tinyMCE.get( this_option_id ).getContent() : switchEditors.wpautop( tinymce.DOM.get( this_option_id ).value );
						tinyMCE.execCommand("mceRemoveControl", false, this_option_id);
					} else {
						et_option_value = $(this).val();
					}
				}
				else if ( $(this).is('select, input') ) {
					et_option_value = $(this).val();
				}
				else if ( $(this).is('#et_slides') ){
					$(this).find('input, textarea').each(function(){
						var this_value = $(this).val();
						
						if ( $(this).is('input') ) $(this).attr('value', this_value);
						else $(this).html( this_value );
					});
					et_option_value = $(this).html();
				}
				
				if ( $(this).hasClass('et_lb_module_content') ) et_option_class += ' et_lb_module_content';
				
				$et_active_module_settings.append( '<div data-option_name="' + this_option_id + '" class="' + et_option_class + '">' + et_option_value + '</div>' );
			} );
			
			$( '#et_layout .et_module' ).removeClass('et_active').css('opacity',1);
			
			$(this).closest('#active_module_settings').slideUp().find('form#et_module_settings').remove();
			$('#et_module_separator').hide();
			
			$('#et_layout').css( 'height', 'auto' );
			
			et_reactivate_ui_actions();
			
			$('#et_lb_main_save').trigger('click');
			
			return false;
		} );
		
		$( 'body' ).delegate( 'form#et_dialog_settings input#submit', 'click', function(){
			var $et_dialog_form = $('form#et_dialog_settings'),
				et_current_module_name = 'et_lb_' + $et_dialog_form.find('input#et_saved_module_name').val(),
				et_shortcode_text, et_shortcode_content = '',
				advanced_option = false,
				editor_id = $et_dialog_form.find('input#et_paste_to_editor_id').val(),
				$current_textarea,
				current_textarea_value;
			
			et_shortcode_text = '[' + et_current_module_name;
			
			$et_dialog_form.find('.et_lb_option').each( function(){
				var et_option_value,
					this_option_id = $(this).attr('id'),
					shortcode_option_id = this_option_id.replace('et_dialog_','');
				
				if ( this_option_id == 'et_slides' ){
					advanced_option = true;
					et_shortcode_text += ']';
					
					$(this).find('.et_attachment').each( function(){
						var $this_attachment = $(this),
							attachment_id = $this_attachment.attr('data-attachment'),
							attachment_link = $this_attachment.find('.attachment_link').val(),
							attachment_description = $this_attachment.find('.attachment_description').val();
						
						et_shortcode_text += '[et_attachment attachment_id="' + attachment_id + '" link="' + attachment_link + '"]' + attachment_description + '[/et_attachment]';
					} );
				} else if ( this_option_id == 'et_lb_tabs' ){
					var $current_option = $(this);
					
					advanced_option = true;
					et_shortcode_text += ']';

					$current_option.find('.et_lb_tab').each( function(){
						var $this_tab = $(this),
							tab_title = $this_tab.find('.et_lb_tab_title').val(),
							tab_editor_id = $this_tab.find('textarea.et_lb_wp_editor').attr('id'),
							tab_content;
						
						if ( typeof tinyMCE !== "undefined" ){						
							tab_content = $this_tab.is(':hidden') ? tinyMCE.get( tab_editor_id ).getContent() : switchEditors.wpautop( tinymce.DOM.get( tab_editor_id ).value );
							
							tinyMCE.execCommand("mceRemoveControl", false, tab_editor_id);
						} else {
							tab_content = $('#' + tab_editor_id).val();
						}
						
						if ( $current_option.parent('#et_slides_interface').length ) et_shortcode_text += '[et_lb_simple_slide]' + tab_content + '[/et_lb_simple_slide]';
						else et_shortcode_text += '[et_lb_tab title="' + tab_title + '"]' + tab_content + '[/et_lb_tab]';
					} );
				}
				else {
				
					if ( $(this).hasClass('et_lb_wp_editor') ){
						if ( typeof tinyMCE !== "undefined" ){
							et_option_value = $(this).is(':hidden') ? tinyMCE.get( this_option_id ).getContent() : switchEditors.wpautop( tinymce.DOM.get( this_option_id ).value );
							tinyMCE.execCommand("mceRemoveControl", false, this_option_id);
						} else {
							et_option_value = $('#' + this_option_id).val();
						}
					}
					else if ( $(this).is(':checkbox') ){
						et_option_value = ( $(this).is(':checked') ) ? 1 : 0;
					}
					else if ( $(this).is('select, input') ) {
						et_option_value = $(this).val();
					}
					
					if ( $(this).hasClass('et_lb_module_content') ) {
						et_shortcode_content = et_option_value;
					} else {
						et_shortcode_text += ' ' + shortcode_option_id + '="' + et_option_value + '"';
					}
					
				}
			} );
			
			if ( ! advanced_option ) et_shortcode_text += ']' + et_shortcode_content + '[/' + et_current_module_name + ']';
			else et_shortcode_text += '[/' + et_current_module_name + ']';
			
			if ( typeof tinyMCE !== "undefined" ){
				switchEditors.go(editor_id,'tmce');
				tinyMCE.getInstanceById( editor_id ).execCommand("mceInsertContent", false, et_shortcode_text);
			} else {
				$current_textarea 		= $('#et_module_settings ' + '#' + editor_id);
				current_textarea_value 	= $current_textarea.val();
				$current_textarea.val( current_textarea_value + et_shortcode_text );
			}
			
			et_close_modal_window();
			
			return false;
		} );
		
		$( 'body' ).delegate( 'a.et_delete_attachment', 'click', function(){
			$(this).closest('.et_attachment').remove();
			return false;
		} );
		
		$et_builder_add_links.click( function(){
			var $et_clicked_link = $(this),
				$et_modules_container = $('#et_modules'),
				open_modules_window = false;
			
			if ( $et_clicked_link.hasClass('et_active') ) return false;
			
			$et_modules_container.find('.et_module').css( { 'opacity' : 0, 'display' : 'none' } );
			
			if ( $et_clicked_link.hasClass('et_add_module') )
				$et_modules_container.find('.et_module:not(.et_m_column, .et_sample_layout)').css({'display':'inline-block', 'opacity' : 0}).animate( { 'opacity' : 1 }, 500 );
			else if ( $et_clicked_link.hasClass('et_add_sample_layout') )
				$et_modules_container.find('.et_module.et_sample_layout').css({'display':'inline-block', 'opacity' : 0}).animate( { 'opacity' : 1 }, 500 );
			else
				$et_modules_container.find('.et_module.et_m_column').css({'display':'inline-block', 'opacity' : 0}).animate( { 'opacity' : 1 }, 500 );
				
			if ( $et_modules_container.is(':hidden') || open_modules_window ) {
				$et_modules_container.slideDown(700);
			}
				
			$et_builder_add_links.removeClass('et_active');
			$et_clicked_link.addClass('et_active');
			
			return false;
		} );
		
		(function et_integrate_media_uploader(){
			var et_fileInput = false,
				change_image = false,
				upload_field = false,
				$upload_field_input = null,
				et_upload_field_name = '',
				et_tb_interval;
				
			$( 'body' ).delegate( 'a#et_add_slider_images', 'click', function(){
				et_fileInput = true;
				
				et_tb_interval = setInterval( function() { 
					$('#TB_iframeContent').contents().find('.savesend .button').val('Insert Into Slider');
				}, 2000 );
				
				tb_show('', 'media-upload.php?post_id=0&amp;type=image&amp;TB_iframe=true');
				return false;
			});
			
			$( 'body' ).delegate( 'a.et_change_attachment_image', 'click', function(){
				et_fileInput = true;
				change_image = true;
				
				$(this).closest('.et_attachment').addClass('active');
				
				et_tb_interval = setInterval( function() { 
					$('#TB_iframeContent').contents().find('.savesend .button').val('Use This Image');
				}, 2000 );
				
				tb_show('', 'media-upload.php?post_id=0&amp;type=image&amp;TB_iframe=true');
				return false;
			});
			
			$( 'body' ).delegate( 'a.et_lb_upload_button', 'click', function(){
				et_fileInput = true;
				upload_field = true;
				
				$upload_field_input = $(this).siblings('.et_lb_upload_field');
				
				et_tb_interval = setInterval( function() { 
					$('#TB_iframeContent').contents().find('.savesend .button').val('Use This Image');
				}, 2000 );
				
				tb_show('', 'media-upload.php?post_id=0&amp;type=image&amp;TB_iframe=true');
				return false;
			});
			
			window.et_original_send_to_editor = window.send_to_editor;
			window.send_to_editor = function(html){
				var et_attachment_class;
				
				if ( et_fileInput ) {
					clearInterval(et_tb_interval);
					et_attachment_class = $( 'img', html ).attr('class');
					et_change_image = ( change_image ) ? 1 : 0;
					et_data_type = ( change_image ) ? 'json' : 'html';
					
					tb_remove();
					et_init_sortable_attachments();
					
					$.ajax({
						type: "POST",
						url: et_lb_options.ajaxurl,
						dataType: et_data_type,
						data:
						{
							action : 'et_add_slider_item',
							et_load_nonce : et_lb_options.et_load_nonce,
							et_attachment_class : et_attachment_class,
							et_change_image : et_change_image
						},
						success: function( data ){
							if ( change_image )	{
								var $active_attachment = $('.et_attachment.active').removeClass('active');
									
								attachment_settings = data;
								
								$active_attachment.attr( 'data-attachment', attachment_settings['attachment_id'] ).find('img').remove();
								$active_attachment.prepend( attachment_settings['attachment_image'] );
								
								change_image = false;
							}
							else if ( upload_field ){
								$upload_field_input.val( $(html).find('img').attr('src') );
								upload_field = false;
							}
							else {
								$('#et_slides:visible').append( data );
							}
						}
					});
					
					et_fileInput = false;
				} else {
					window.et_original_send_to_editor( html );
				}
			}
		})();
		
		$( 'body' ).delegate( 'a#et_lb_add_tab', 'click', function(){
			var element_name = 1 == $(this).parent('#et_slides_interface').length ? 'slides' : 'tabs',
				$et_tabs = $(this).closest('#et_'+element_name+'_interface').find('#et_lb_tabs'),
				next_element = parseInt( $et_tabs.attr('data-elements') ) + 1;
				
			$et_tabs.attr('data-elements',next_element);
			
			et_init_sortable_tabs();
			$.ajax({
				type: "POST",
				url: et_lb_options.ajaxurl,
				data:
				{
					action : 'et_add_'+element_name+'_item',
					et_load_nonce : et_lb_options.et_load_nonce,
					et_tabs_length : next_element
				},
				success: function( data ){
					var tab_editor_id = $(data).find('.et_lb_wp_editor').attr('id');
					
					$('#et_lb_tabs:visible').append( data );
					
					if ( typeof tinyMCE !== "undefined" ){
						tinyMCE.execCommand( "mceAddControl", true, tab_editor_id );
						quicktags( { id : tab_editor_id } );
						et_init_new_editor( tab_editor_id );
					}
					
					et_track_active_editor();
				}
			});
			
			return false;
		});
		
		$( 'body' ).delegate( 'a.et_lb_delete_tab', 'click', function(){
			var $et_tab_active = $(this).closest('.et_lb_tab');
			
			if ( typeof tinyMCE !== "undefined" ){
				tinyMCE.execCommand( "mceRemoveControl", true, $et_tab_active.find('.et_lb_wp_editor').attr('id') );
			}
			
			$et_tab_active.remove();
			
			return false;
		});
		
		$('#et_lb_main_save').click(function(){
			et_layout_save( true );
			return false;
		});
		
		function et_layout_save( show_save_message ){
			var layout_html = $('#et_layout').html(),
				layout_shortcode = et_lb_generate_layout_shortcode( $('#et_layout') ),
				$save_message = jQuery("#et_lb_ajax_save");
			
			$.ajax({
				type: "POST",
				url: et_lb_options.ajaxurl,
				data:
				{
					action : 'et_save_layout',
					et_load_nonce : et_lb_options.et_load_nonce,
					et_layout_html : layout_html,
					et_layout_shortcode : layout_shortcode,
					et_post_id : $('input#post_ID').val()
				},
				beforeSend: function ( xhr ){
					if ( show_save_message ){
						$save_message.children("img").css("display","block");
						$save_message.children("span").css("margin","6px 0px 0px 30px").html( et_lb_options.saving_text );
						$save_message.fadeIn('fast');
					}
				},
				success: function( data ){
					$save_message.children("img").css("display","none");
					$save_message.children("span").css("margin","0px").html( et_lb_options.saved_text );
					
					setTimeout(function(){
						$save_message.fadeOut("slow");
					},500);
				}
			});
		}
		
		//make sure the hidden WordPress Editor is in Visual mode
		//switchEditors.go('et_lb_hidden_editor','tmce');
		
		(function et_init_ui(){
			$( '#et_layout' ).droppable({
				accept: ":not(.ui-sortable-helper)",
				greedy: true,
				drop: function( event, ui ) {
					if ( ui.draggable.hasClass('et_sample_layout') ){
						et_lb_append_sample_layout( ui.draggable );
						return;
					}
					ui.draggable.clone().appendTo( this );
					et_init_modules_js( 0 );
				}
			}).sortable({
				forcePlaceholderSize: true,
				placeholder: 'et_module_placeholder',
				cursor: 'move',
				distance: 2,
				start: function(event, ui) {
					ui.placeholder.text( ui.item.attr('data-placeholder') );
					ui.placeholder.css( 'width', ui.item.width() );
				},
				update: function(event, ui){
					et_init_modules_js( 0 );
				},
				stop: function(event, ui) {
					et_layout_save( false );
				}
			});
			
			$( '#et_modules .et_module' ).draggable({
				revert: 'invalid',
				zIndex: 100,
				distance: 2,
				cursor: 'move',
				helper: 'clone'
			});
		})();
		
		$( '#et_layout .et_module .ui-resizable-handle' ).remove();
		et_init_modules_js( 1 );
		
		// resizable and sortable init
		function et_init_modules_js( et_first_time ){
			var $et_helper_text = $('#et_lb_helper');
			
			// remove 'resizable' handler from 'full width' modules
			$( '#et_layout > .et_module.et_full_width .et_move' ).remove();
			
			$( '#et_layout > .et_m_column' ).each( function(){
				$(this).removeClass('et_m_column_no_modules');
				if ( ! $(this).find('.et_module').length ) $(this).addClass('et_m_column_no_modules');
			} );
			
			$( '#et_layout > .et_module:not(.et_full_width)' ).resizable({
				handles: 'e',
				containment: 'parent',
				start: function(event, ui) {
					ui.helper.css({position: ""}); // firefox fix
					
					ui.helper.css({
						position: "relative !important",
						top: "0 !important",
						left: "0 !important"
					});
				},
				stop: function(event, ui) {        
					ui.helper.css({
						position: "",
						top: "",
						left: ""
					});
					et_calculate_modules();
				},
				resize: function(event, ui) {
					var module_width = ui.helper.hasClass('et_m_column_resizable') ? ( ui.size.width+26 ) : (ui.size.width+2),
						new_width = Math.floor( ( module_width / et_builder_width ) * 100 ),
						$module_width = ui.helper.find('> span.et_module_name > span.et_module_width');
					
					ui.helper.css({
						top: "",
						left: ""
					});
					
					if ( new_width >= 100 ) new_width = '';
					else new_width = ' (' + new_width + '%)';
					
					if ( $module_width.length ){
						$module_width.html( new_width );
					} else {
						ui.helper.find('> span.et_module_name').append('<span class="et_module_width">' + new_width + '</span>')
					}
					
					if ( ui.helper.hasClass('et_m_column_resizable') ) ui.helper.css('height','auto');
				}
			});
			
			$( '#et_layout .et_m_column' ).droppable({
				accept: ".et_module:not(.et_m_column,.et_full_width,.et_sample_layout)",
				hoverClass: 'et_column_active',
				greedy: true,
				drop: function( event, ui ) {
					// return if we're moving modules inside the column
					if ( ui.draggable.parents('.et_m_column').length && $(this).find('.ui-sortable-helper').length ) return;
					
					ui.draggable.clone().appendTo( this ).css( { 'width' : '100%', 'marginRight' : '0' } ).find('span.et_module_width').remove();
					
					if ( ui.draggable.parents('#et_layout').length ){
						ui.draggable.remove();
					}
					
					et_init_modules_js( 0 );
				}
			}).sortable({
				forcePlaceholderSize: true,
				cancel: 'span.et_column_name',
				placeholder: 'et_module_placeholder',
				cursor: 'move',
				distance: 2,
				connectWith: '#et_layout',
				zIndex: 10,
				start: function(event, ui) {
					ui.placeholder.text( ui.item.attr('data-placeholder') );
					ui.placeholder.css( 'width', ui.item.width() );
					ui.item.closest('.et_m_column').css( 'z-index', '10' );
				},
				stop: function(event, ui) {
					$( '#et_layout .et_m_column' ).css( 'z-index', '1' );
					
					et_layout_save( false );
				}
			});
			
			if ( $( '#et_layout > .et_module' ).length ) $et_helper_text.hide();
			else $et_helper_text.show();
			
			// columns and modules within columns can't be resized
			$( '#et_layout .et_m_column:not(.et_m_column_resizable)' ).resizable( "destroy" );
			
			$( '#et_layout .et_m_column > span.et_move' ).remove();
			
			$( '#et_layout .et_module' ).css( { 'position' : '', 'top' : '', 'left' : '', 'height' : 'auto !important', 'z-index' : '1' } ).removeClass('ui-sortable-helper').removeClass('et_column_active');
			
			// don't calculate modules width first time, the function was executed already in the et_layout_window_resize function
			if ( et_first_time != 1 ) et_calculate_modules();
			
			if ( typeof tinyMCE === "undefined" ) $('body').addClass( 'et_visual_editor_disabled' );
		}
		
		function et_calculate_modules(){
			var et_row_width = 0;
			
			$( '#et_layout > .et_module' ).each( function(){
				var $module_width_span = $(this).find('> span.et_module_name > span.et_module_width'),
					et_modifier = $(this).hasClass('et_m_column_resizable') ? 26 : 2;
				
				if ( ! $(this).hasClass('et_m_column') || $(this).hasClass('et_m_column_resizable') ){
					if ( $module_width_span.length && $module_width_span.text() !== '' ) $(this).css( 'width', et_builder_width * parseInt( $module_width_span.text().substring(2) ) / 100 - et_modifier );
					else {
						if ( $(this).hasClass('et_m_column_resizable') ) $(this).css( 'width', et_main_module_width - et_modifier );
						else $(this).css( 'width', et_main_module_width );
					}
				}
			} );
			
			$( '#et_layout > .et_module' ).removeClass('et_first').each( function(index){
				if ( index === 0 || et_row_width === 0 ) $(this).addClass('et_first');
				
				et_row_width += $(this).outerWidth(true);
				
				if ( et_row_width === et_builder_width ){
					$(this).next('.et_module').addClass('et_first');
					et_row_width = 0;
				} else if ( et_row_width > et_builder_width ){
					$(this).addClass('et_first');
					et_row_width = $(this).outerWidth(true);
				}
			} );
			
			$( '#et_layout > .et_module.et_first' ).each( function(){
				var et_modifier = $(this).hasClass('et_m_column_resizable') ? 26 : 2,
					module_width = $(this).width(),
					$module_width_span = $(this).find('> span.et_module_name > span.et_module_width');
				
				if ( $module_width_span.length && $module_width_span.text() !== '' ) {
					$module_width_span.text( ' (' + Math.round( ( ( module_width + et_modifier ) / et_builder_width ) * 100 ) + '%)' );
				}
			} );	
		}
		
		function et_lb_append_sample_layout( $layout_module ){
			$.ajax({
				type: "POST",
				url: et_lb_options.ajaxurl,
				data:
				{
					action : 'et_append_layout',
					et_load_nonce : et_lb_options.et_load_nonce,
					et_layout_name : $layout_module.attr('data-name')
				},
				success: function( data ){
					$( '#et_layout' ).append( data );
					$( '#et_layout .et_module .ui-resizable-handle' ).remove();
					et_init_modules_js( 0 );
				}
			});
		}
		
		function et_deactivate_ui_actions(){
			$( '#et_layout' ).droppable( "disable" ).sortable( "disable" );
			
			$( '#et_layout .et_m_column' ).droppable( "disable" ).sortable( "disable" );
			
			$( '#et_layout > .et_module span.et_move, #et_layout > .et_module span.et_delete, #et_layout > .et_module span.et_settings_arrow' ).css( 'display', 'none' );
			
			et_make_editor_droppable();
		}
		
		function et_reactivate_ui_actions(){
			$( '#et_layout' ).droppable( "enable" ).sortable( "enable" );

			$( '#et_layout .et_m_column' ).droppable( "enable" ).sortable( "enable" );
			
			$( '#et_layout > .et_module span.et_move, #et_layout > .et_module span.et_delete, #et_layout > .et_module span.et_settings_arrow' ).css( 'display', 'block' );
		}
		
		function et_make_editor_droppable(){
			$( '.wp-editor-container' ).droppable({
				accept: ".et_module",
				hoverClass: 'et_editor_hover',
				greedy: true,
				drop: function( event, ui ) {
					var et_paste_to_editor_id = $(this).find('.et_lb_wp_editor').attr('id'),
						et_action = 'et_show_module_options';
					
					// don't allow inserting module into the same module 
					if ( $('#et_layout .et_active').attr('data-placeholder') == ui.draggable.attr('data-placeholder') ) return;
					if ( ui.draggable.hasClass('et_sample_layout') ) return;
					
					if ( ui.draggable.hasClass('et_m_column') ) et_action = 'et_show_column_options';
					
					$.ajax({
						type: "POST",
						url: et_lb_options.ajaxurl,
						data:
						{
							action : et_action,
							et_load_nonce : et_lb_options.et_load_nonce,
							et_module_class : ui.draggable.attr('class'),
							et_modal_window : 1,
							et_paste_to_editor_id : et_paste_to_editor_id,
							et_module_exact_name : ui.draggable.attr('data-placeholder')
						},
						success: function( data ){
							$('body').append( '<div id="et_dialog_modal">' + '<div class="et_dialog_handle">Insert Shortcode</div>' + data + '</div> <div class="et_modal_blocker"></div>' );
							
							$('#et_dialog_modal').draggable( { 'handle' : 'div.et_dialog_handle' } );
							
							$( '#et_dialog_settings .et_lb_option' ).each( function(){
								var $this_option = $(this),
									this_option_id = $this_option.attr('id');
								
								if ( $this_option.hasClass('et_lb_wp_editor') && typeof tinyMCE !== "undefined" ) {
									tinyMCE.execCommand( "mceAddControl", true, this_option_id );
									quicktags( { id : this_option_id } );
									et_init_new_editor( this_option_id );
								}
							} );
							
							$('html:not(:animated),body:not(:animated)').animate({ scrollTop: 0 }, 500);
							
							et_track_active_editor();
						}
					});
				}
			});
		}
		
		function et_close_modal_window(){
			$( 'div#et_dialog_modal, div.et_modal_blocker' ).remove();
			$('html:not(:animated),body:not(:animated)').animate({ scrollTop: $('#et_page_builder').offset().top - 82 }, 500);
		}
		
		function et_init_sortable_attachments(){
			$('#et_slides').sortable({
				forcePlaceholderSize: true,
				cursor: 'move',
				distance: 2,
				zIndex: 10
			});
		}
		
		function et_init_sortable_tabs(){
			$('#et_lb_tabs, #et_lb_slides').sortable({
				forcePlaceholderSize: true,
				cursor: 'move',
				distance: 2,
				zIndex: 10,
				start: function(e, ui){
					$(this).find('.et_lb_wp_editor').each(function(){
						if ( typeof tinyMCE !== "undefined" ) tinyMCE.execCommand( 'mceRemoveControl', false, $(this).attr('id') );
					});
				},
				stop: function(e,ui) {
					$(this).find('.et_lb_wp_editor').each(function(){
						if ( typeof tinyMCE !== "undefined" ){
							tinyMCE.execCommand( 'mceAddControl', false, $(this).attr('id') );
							tinyMCE.execCommand( 'mceSetContent', false, switchEditors.wpautop( $(this).val() ) );
							//$(this).sortable("refresh");
						}
					});
				}
			});
		}
		
		function et_init_new_editor(editor_id){
			if ( typeof tinyMCEPreInit.mceInit[editor_id] !== "undefined" ) return;
			var et_new_editor_object = et_hidden_editor_object;
			
			et_new_editor_object['elements'] = editor_id;
			tinyMCEPreInit.mceInit[editor_id] = et_new_editor_object;
		}
		
		function et_lb_delete_module( $module ){
			$module.remove();
			et_init_modules_js( 0 );
			
			// save changes after the element is removed
			et_layout_save( false );
		}
		
		function et_lb_generate_layout_shortcode( html_element ){
			var shortcode_output = '';
			
			html_element.find( '> .et_module' ).each( function(){
				var $this_module = $(this),
					$this_module_width = $this_module.find('> .et_module_name > .et_module_width'),
					module_name = 'et_lb_' + $this_module.attr('data-name'),
					module_content = '';
				
				shortcode_output += '[' + module_name;
				
				if ( $this_module_width.length && $this_module_width.text() !== '' ) shortcode_output += ' width="' + parseInt( $this_module_width.text().replace(/[()]/,'') ) + '"';
				if ( $this_module.hasClass('et_first') ) shortcode_output += ' first_class="1"';
				
				if ( $this_module.hasClass('et_m_column') ){
					shortcode_output += ']' + '\n';
					shortcode_output += et_lb_generate_layout_shortcode( $this_module );
				} else {
					$this_module.find('> .et_module_settings .et_module_setting').each( function(){
						var $this_option = $(this),
							option_name = $this_option.attr('data-option_name'),
							option_value = $this_option.html();
						
						if ( option_name == 'et_slides' ){
							shortcode_output += ']';
							$this_option.find('.et_attachment').each( function(){
								var $this_attachment = $(this),
									attachment_id = $this_attachment.attr('data-attachment'),
									attachment_link = $this_attachment.find('.attachment_link').val(),
									attachment_description = $this_attachment.find('.attachment_description').html();
								
								shortcode_output += '[et_attachment attachment_id="' + attachment_id + '" link="' + attachment_link + '"]' + attachment_description + '[/et_attachment]';
							} );
						} else if ( option_name == 'et_lb_tabs' ){
							shortcode_output += ']';
							$this_option.find('.et_lb_tab').each( function(){
								var $this_tab = $(this),
									tab_title = $this_tab.find('.et_lb_tab_title').val(),
									tab_content = $this_tab.find('.wp-editor-wrap').html();
								
								if ( $this_option.closest('.et_module').hasClass('et_m_simple_slider') ){
									shortcode_output += '[et_lb_simple_slide]' + tab_content + '[/et_lb_simple_slide]';
								} else {
									shortcode_output += '[et_lb_tab title="' + tab_title + '"]' + tab_content + '[/et_lb_tab]';
								}
							} );
						}
						else {
							if ( $this_option.hasClass('et_lb_module_content') ){
								module_content = option_value;
							} else {
								shortcode_output += ' ' + option_name + '="' + option_value + '"';
							}
						}
					} );
					
					if ( ! ( shortcode_output.charAt(shortcode_output.length-1) === ']' ) ) shortcode_output += ']';
				}
				
				shortcode_output += module_content + '[/' + module_name + ']' + '\n';
			} );
			
			return shortcode_output;
		}
		
		function et_track_active_editor(){
			// change the active editor, when user clicks on new editors, created via ajax
			jQuery('.wp-editor-wrap').mousedown(function(e){
				wpActiveEditor = this.id.slice(3, -5);
			});
		}
		
		
		et_layout_window_resize();
		
		function et_layout_window_resize(){
			var $_et_page_builder = $('#et_page_builder')
				_window_width = $(window).width(),
				_new_page_builder_width = 0,
				_block_width_difference = 0;
			
			if ( _window_width > 1260 ) _new_page_builder_width = et_page_builder_original_width;
			else if ( _window_width <= 1260 && _window_width > 900 ) _new_page_builder_width = et_page_builder_original_width - ( 1260 - _window_width );
			else if ( _window_width <= 900 && _window_width > 850 ) _new_page_builder_width = et_page_builder_original_width - ( 1260 - _window_width ) + 113;
			else _new_page_builder_width = et_page_builder_original_width - ( 1260 - _window_width ) + 113 + 305;
			
			$_et_page_builder.width( _new_page_builder_width );
			
			et_builder_width = _new_page_builder_width - 42;
			
			et_main_module_width = et_builder_width - 2;
			
			if ( _window_width < 1260 ){
				_block_width_difference = _new_page_builder_width - _window_width;
			}
			
			et_calculate_modules();
		}
		
		$(window).resize( function(){
			et_layout_window_resize();
		} );
	});
})(jQuery)