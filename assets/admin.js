(function($) {
    'use strict';

    // Legacy version repeater for the CPT meta box.
    $(document).on('click', '#pn-add-legacy', function() {
        var container = $('#pn-legacy-entries');
        var index = container.find('.pn-legacy-entry').length;

        var html = '<div class="pn-legacy-entry" data-index="' + index + '">' +
            '<table class="form-table">' +
            '<tr><th>Version</th><td>' +
            '<input type="text" name="pn_dl_legacy[' + index + '][version]" class="regular-text" placeholder="e.g. 2.0.0">' +
            ' <button type="button" class="button pn-remove-legacy">Remove</button>' +
            '</td></tr>' +
            '<tr><th>macOS URL</th><td>' +
            '<input type="url" name="pn_dl_legacy[' + index + '][mac_url]" class="large-text">' +
            '</td></tr>' +
            '<tr><th>Windows URL</th><td>' +
            '<input type="url" name="pn_dl_legacy[' + index + '][win_url]" class="large-text">' +
            '</td></tr>' +
            '</table></div>';

        container.append(html);
    });

    $(document).on('click', '.pn-remove-legacy', function() {
        $(this).closest('.pn-legacy-entry').remove();
    });

    // Regenerate API key on settings page.
    $(document).on('click', '#pn-regenerate-key', function() {
        if (!confirm('Are you sure? Any scripts using the current key will stop working.')) {
            return;
        }

        $.post(pnDownloads.ajaxUrl, {
            action: 'pn_regenerate_key',
            nonce: pnDownloads.nonce
        }, function(response) {
            if (response.success) {
                $('#pn_downloads_api_key').val(response.data.key);
                alert('API key regenerated. Update your scripts with the new key.');
            }
        });
    });

})(jQuery);
