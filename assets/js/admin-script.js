(function($){
    $(document).ready(function(){
        // Color picker init
        if ( typeof wp !== 'undefined' && wp.color && $('.wafbp-color-field').length ) {
            $('.wafbp-color-field').each(function(){
                $(this).wpColorPicker();
            });
        }

        // Copy shortcode buttons
        $(document).on('click', '.wafbp-copy-shortcode-btn, .wafbp-copy-shortcode', function(e){
            e.preventDefault();
            var shortcode = $(this).data('shortcode');
            if (!shortcode) return;
            var $temp = $('<input>');
            $('body').append($temp);
            $temp.val(shortcode).select();
            document.execCommand('copy');
            $temp.remove();
            alert(wafbpAdmin.copy_message || 'Shortcode copied to clipboard');
        });

        // Confirm delete (in case)
        $(document).on('click', '.wafbp-delete-form-btn', function(e){
            if (!confirm(wafbpAdmin.confirm_delete || 'Are you sure you want to delete this form?')) {
                e.preventDefault();
                return false;
            }
            return true;
        });
    });
})(jQuery);