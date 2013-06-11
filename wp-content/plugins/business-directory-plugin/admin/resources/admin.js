var WPBDP_associations_fieldtypes = {};

(function($) {

    /* Form Fields */
    var WPBDPAdmin_FormFields = {
        $f_association: null,
        $f_fieldtype: null,

        init: function() {
            WPBDPAdmin_FormFields.$f_association = $('form#wpbdp-formfield-form select#field-association');
            WPBDPAdmin_FormFields.$f_association.change( WPBDPAdmin_FormFields.onAssociationChange );

            WPBDPAdmin_FormFields.$f_fieldtype = $('form#wpbdp-formfield-form select#field-type');
            WPBDPAdmin_FormFields.$f_fieldtype.change( WPBDPAdmin_FormFields.onFieldTypeChange );
        },

        onFieldTypeChange: function() {
            var $field_type = $(this).find('option:selected');

            if ( !$field_type.length )
                return;

            var field_type = $field_type.val();

            var request_data = {
                action: "wpbdp-renderfieldsettings",
                association: WPBDPAdmin_FormFields.$f_association.find('option:selected').val(),
                field_type: field_type,
                field_id: $('#wpbdp-formfield-form input[name="field[id]"]').val()
            };

            $.post( ajaxurl, request_data, function(response) {
                if ( response.ok && response.html ) {
                    $('#wpbdp-fieldsettings-html').html(response.html);
                    $('#wpbdp-fieldsettings').show();
                } else {
                    $('#wpbdp-fieldsettings-html').empty();
                    $('#wpbdp-fieldsettings').hide();
                }
            }, 'json' );
        },

        onAssociationChange: function() {
            $f_fieldtype = WPBDPAdmin_FormFields.$f_fieldtype;

            var association = $(this).find('option:selected').val();
            var valid_types = WPBDP_associations_fieldtypes[ association ];

            $f_fieldtype.find('option').removeAttr('disabled');

            $f_fieldtype.find('option').each(function(i,v){
                if ( $.inArray( $(v).val(), valid_types ) < 0 ) {
                    $(v).attr('disabled', 'disabled');
                }
            });

            if ( $f_fieldtype.find('option:selected').attr('disabled') == 'disabled' ) {
                $f_fieldtype.find('option').removeAttr('selected');
                $f_fieldtype.find('option[value="' + valid_types[0] + '"]').attr('selected', 'selected');
            }     
        }
    };


    $(document).ready(function(){
        WPBDPAdmin_FormFields.init();
    });

})(jQuery);


jQuery(document).ready(function($){

    /* Manage Fees */
    $('form#wpbdp-fee-form input[name="_days"]').change(function(){
        var value = $(this).val();

        // alert(value);

        if (value == 0) {
            $('form input#wpbdp-fee-form-days-n').attr('disabled', true);
            $('form input[name="fee[days]"]').val('0');
        } else {
            $('form input#wpbdp-fee-form-days-n').removeAttr('disabled');
            $('form input[name="fee[days]"]').val($('form input#wpbdp-fee-form-days-n').val());
            $('form input#wpbdp-fee-form-days-n').focus();
        }

        return true;
    });

    $('form#wpbdp-fee-form').submit(function(){
        // alert($('form#wpbdp-fee-form input[name="fee[days]"]').val());
        // return false;
        $('form input[name="fee[days]"]').removeAttr('disabled');
        return true;
    });


    /* Listing Info Metabox */

    $('#BusinessDirectory_listinginfo .listing-metabox-tabs a').click(function(e){
        e.preventDefault();

        var href = $(this).attr('href');

        var $selected = $(this).parent('li').siblings('.selected');

        if ($selected.length > 0) {
            if ($selected.find('a:first').attr('href') == href) {
                return;
            } else {
                // hide current tab (if any)
                $selected.removeClass('selected');
                $($selected.find('a:first').attr('href')).hide();
            }
        }

        // show new tab
        $(this).parent('li').addClass('selected');
        $(href).show();
    });

    $('#BusinessDirectory_listinginfo .listing-metabox-tabs li.selected a').click();


    /* Listing Info metabox / Transactions */

    $('#listing-metabox-transactions .transaction .summary').click(function(e){
        e.preventDefault();
        $(this).find('.handle a').text($(this).parent('.transaction').hasClass('open') ? '+' : '-');
        $(this).parent('.transaction').toggleClass('open');
        $(this).siblings('.details').toggle();
    });

    /* Listing info metabox / fees */

    $('#listing-metabox-fees a.assignfee-link').click(function(e){
        e.preventDefault();
        $(this).siblings('.assignfee').show();
    });

    $('#listing-metabox-fees .assignfee .close-handle').click(function(e){
        e.preventDefault();
        $(this).parent('.assignfee').hide();
    });


    /* Ajax placeholders */

    $('.wpbdp-ajax-placeholder').each(function(i,v){
        wpbdp_load_placeholder($(v));
    });

    $('a.delete-image-button').live('click', function(e){
        e.preventDefault();
        jQuery.get($(this).attr('href'), function(res){
            wpbdp_load_placeholder($("#wpbdp-listing-images")); 
        });

        return false;
    }); 


    /*
     * Admin bulk actions
     */

    $('input[id="doaction"]').click(function(e) {
        var $selected_option = $('select[name="action"] option:selected');
        var action_val = $selected_option.val();

        if (action_val.split('-')[0] == 'listing') {
            var action = action_val.split('-')[1];

            if (action != 'sep0' && action != 'sep1' && action != 'sep2') {
                var $checked_posts = $('input[name="post[]"]:checked');
                var uri = $selected_option.attr('data-uri');

                $checked_posts.each(function(i,v){
                    uri += '&post[]=' + $(v).val();
                });

                window.location.href = uri;

                return false;
            }
        }

        return true;
    });

    /* Form fields form preview */
    $('.wpbdp-admin.wpbdp-page-formfields-preview form input[type="submit"]').click(function(e){
        e.preventDefault();
        alert('This form is just a preview. It doesn\'t work.');
    });

    /* CSV import */
    $('form#wpbdp-csv-import-form input.assign-listings-to-user').change(function(e){
        if ( $(this).is(':checked') ) {
            $('form#wpbdp-csv-import-form .default-user-selection').show();
            //$('form#wpbdp-csv-import-form select.default-user').hide('disabled');
        } else {
            $('form#wpbdp-csv-import-form .default-user-selection').hide();
            //$('form#wpbdp-csv-import-form select.default-user').attr('disabled', 'disabled');
        }

    }).change();

});

function wpbdp_load_placeholder($v) {
    var action = $v.attr('data-action');
    var post_id = $v.attr('data-post_id');
    var baseurl = $v.attr('data-baseurl');

    $v.load(ajaxurl, {"action": action, "post_id": post_id, "baseurl": baseurl});
}