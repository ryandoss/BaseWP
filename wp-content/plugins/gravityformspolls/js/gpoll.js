jQuery(document).ready(function() {

	view_results_markup = '<a href="javascript:void(0)" class="gpoll_button">' + gpoll_strings.viewResults + '</a><div class="gpoll_summary"></div>';

	function gpollGetCurrentForm(element){
		var form = jQuery(element).closest("form");
		return form;
	}
	function gpollGetFormId(form){
        var formId = jQuery(form).attr("id").replace("gform_", "");
        return formId;
    }
	function gpollAnimateBars(root){
		jQuery(root).find(".gpoll_bar > span").show();
		jQuery(root).find(".gpoll_bar > span").each(function() {
			jQuery(this).width(0)
				.animate({
						width: jQuery(this).data("origwidth") + '%'
				}, 1500);
		});

	}

	function gpollGetQueryVariable( query, variable) {
        var vars = query.split("&");
        for (var i = 0; i < vars.length; i++) {
            var pair = vars[i].split("=");
            if (pair[0] == variable) {
                return unescape(pair[1]);
            }
        }
    }

	function gpollAddFieldNumbers(){
		var qnumdiv= '<span class="gpoll_field_number"></span>';
		jQuery(".gpoll_field").prepend(qnumdiv);

		//ensures that we only add numbers to the front end - not in admin
		var questions = jQuery("li.gpoll_field span.gpoll_field_number");
		var c= 0;
		jQuery.each(questions, function(i, l){
		   jQuery(questions[i]).html(c += 1);
		   //make the numbers div high enough so the inputs don't wrap round
		   new_height = jQuery(questions[i]).parent().height() + 15;
		   jQuery(questions[i]).height(new_height);
		   jQuery(questions[i]).parent().css("margin-bottom","20px")
		});
		jQuery(".ginput_container").css("margin-left","auto");
		jQuery(".gpoll_field > label").css("display","inline");

	}
	
	function gpollPollVars(style, numbers, percentages, counts, showResultsLink, displayResults, cookieDuration, confirmation){
		this.style = style;
		this.numbers = numbers;
		this.percentages = percentages;
		this.counts = counts;
		this.showResultsLink = showResultsLink;
        this.displayResults = displayResults;
        this.cookieDuration = cookieDuration;
        this.confirmation = confirmation;
        
	}

	function gpollGetPollVars(form){
		gform_field_values = jQuery(form).find("input[name='gform_field_values']").val();
		var pollVars;
        pollVars
		if (gform_field_values != undefined) {

			var style = gpollGetQueryVariable(gform_field_values,"gpoll_style");
			var numbers = gpollGetQueryVariable(gform_field_values,"gpoll_numbers");
			var percentages = gpollGetQueryVariable(gform_field_values,"gpoll_percentages");
			var counts = gpollGetQueryVariable(gform_field_values,"gpoll_counts");
			var showResultsLink = gpollGetQueryVariable(gform_field_values,"gpoll_show_results_link");
            var displayResults = gpollGetQueryVariable(gform_field_values,"gpoll_display_results");
            var cookieDuration = gpollGetQueryVariable(gform_field_values,"gpoll_cookie");
            var confirmation = gpollGetQueryVariable(gform_field_values,"gpoll_confirmation");
            
			pollVars = new gpollPollVars(style, numbers, percentages, counts, showResultsLink, displayResults, cookieDuration, confirmation);
		}
		
		return pollVars;
	}

	jQuery(".gpoll_button").live("click", function(){
		form = jQuery(this).closest(".gform_wrapper form");
		gpollMaybeGetResultsUI(form, true);
	});
	
	jQuery(".gpoll_back_button").live("click", function(){
		form = gpollGetCurrentForm(jQuery(this));
        formId = gpollGetFormId(form);
		jQuery(form).find(".gpoll_summary").fadeOut();
		jQuery(form).find("#gform_fields_" + formId).fadeIn();
		jQuery(form).find("#gform_submit_button_" + formId).fadeIn();
		jQuery(form).find(".gpoll_button").fadeIn();
		jQuery(form).find(".gpoll_summary").remove();
		jQuery(form).find(".gform_button").parent().append(view_results_markup);
	});

	jQuery(".gform_wrapper form").each(function(){
		pollVars = gpollGetPollVars(this);
		if ( pollVars.numbers == "1" )
				gpollAddFieldNumbers();
		if ( pollVars.showResultsLink == "1" )
			jQuery(this).find(".gform_button").parent().append(view_results_markup);
	});
	
	jQuery(".gpoll_container").each(function(){
		gpollAnimateBars(this);
	});

	jQuery(document).bind('gform_confirmation_loaded', function(event, formId){
		gf_selector = "div#gforms_confirmation_message.gform_confirmation_message_" + formId;
		pollsContainer = jQuery(gf_selector);
		jQuery(gf_selector + " div.gpoll_bar > span").hide();
		gpollAnimateBars(pollsContainer);
	});

	function gpollCookieExists(key){
        if ( document.cookie.indexOf(key) === -1 )
            return false
        else 
            return true
    }
    function gpollMaybeGetResultsUI(form, previewResults){
        var container;
        
        pollVars = gpollGetPollVars(form);
        formId = gpollGetFormId(form);
        
        if (previewResults){
            pollVars.viewResults = 1;
        } else {
            hasVoted = gpollCookieExists("gpoll_form_" + formId);
            if (false === hasVoted || "" === pollVars.cookieDuration)
                return;  
            container = jQuery(form).closest(".gpoll_ajax_container");
            jQuery(container).hide()
        }
            
        pollVars.action = 'gpoll_ajax';
        pollVars.formId = formId;
        jQuery.ajax({
            url:gpoll_vars.ajaxurl,
            type:'POST',
            dataType: 'json',
            data: pollVars,
            success:function(result) {
                if (result === -1){
                    //permission denied
                }
                else {
                    
                    if (previewResults){
                        jQuery(form).find(".gpoll_summary").html(result.resultsUI);
                        jQuery(form).find("#gform_fields_" + formId).hide();
                        jQuery(form).find("#gform_submit_button_" + formId).hide();
                        jQuery(form).find(".gpoll_button").remove();

                        jQuery(form).find(".gpoll_bar > span").hide();
                        
                        jQuery(form).find(".gpoll_summary").hide().fadeIn(function (){
                            gpollAnimateBars(form);
                            back_button_markup = '<a href="javascript:void(0)" class="gpoll_back_button" style="display:none;">' + gpoll_strings.backToThePoll + '</a>';
                            jQuery(form).find(".gpoll_summary").append(back_button_markup)
                            jQuery(form).find(".gpoll_back_button").fadeIn('slow');
                        });
                    } else if (false === result.canVote){
                        jQuery(container).html(result.resultsUI);
                        jQuery(container).show();
                        gpollAnimateBars(container);
                    } else {
                        jQuery(container).show();
                    }
                    
                    
                }
            }
        });
     }

    jQuery(".gpoll_ajax_container").each(function(){
            var form = jQuery(this).find(".gform_wrapper form");
            gpollMaybeGetResultsUI(form, false);
        });
    
});


