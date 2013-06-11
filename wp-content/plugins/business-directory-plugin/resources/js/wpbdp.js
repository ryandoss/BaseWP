if (typeof(window.WPBDP) == 'undefined') {
    window.WPBDP = {};
}

jQuery(document).ready(function($){

    $('.listing-actions input.delete-listing').click(function(e){
        var message = $(this).attr('data-confirmation-message');

        if (confirm(message)) {
            return true;
        }
        
        return false;
    });

});

WPBDP.fileUpload = {

    resizeIFrame: function(field_id, height) {
        var iframe = jQuery( '#wpbdp-upload-iframe-' + field_id )[0];
        var iframeWin = iframe.contentWindow || iframe.contentDocument.parentWindow;
        
        if ( iframeWin.document.body ) {
            iframe.height = iframeWin.document.documentElement.scrollHeight || iframeWin.document.body.scrollHeight;
        }
    },

    handleUpload: function(o) {
        var $input = jQuery(o);
        var $form = $input.parent('form');

        $form.submit();
    },

    finishUpload: function(field_id, upload_id) {
        var $iframe = jQuery('#wpbdp-upload-iframe-' + field_id);
        // $iframe.contents().find('form').hide();

        var $input = jQuery('input[name="listingfields[' + field_id + ']"]');
        $input.val(upload_id);

        var $preview = $input.siblings('.preview');
        $preview.find('img').remove();
        $preview.prepend($iframe.contents().find('.preview').html());
        $iframe.contents().find('.preview').remove();

        $preview.find('.delete').show();
    },

    deleteUpload: function(field_id) {
        var $input = jQuery('input[name="listingfields[' + field_id + ']"]');
        var $preview = $input.siblings('.preview');

        $input.val('');
        $preview.find('img').remove();

        $preview.find('.delete').hide();
        
        return false;
    }

};