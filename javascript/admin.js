jQuery( document ).ready(function ( $ ) {

    $('#import_button').on('click', function( event ) {
        var $importButton = $(this),
            $messageField = $importButton.parent().find('.description'),
            spotIdInputValue = $importButton.parents('form').find('[name="wp-spotim-settings[spot_id]"]').attr('value').trim(),
            importTokenInputValue = $importButton.parents('.form-table').find('[name="wp-spotim-settings[import_token]"]').attr('value').trim();

        // empty message field from any text and reset css
        $messageField
            .css({ 'color': '' })
            .html('');

        // disable the import button
        $importButton.attr( 'disabled', true );

        var data = {
            'action': 'start_import',
            'spotim_spot_id': spotIdInputValue,
            'spotim_import_token': importTokenInputValue,
            'spotim_page_number': 0
        };

        importCommetsToWP( data, $importButton, $messageField );

        event.preventDefault();
    });

    function importCommetsToWP( params, $importButton, $messageField ) {
        $.post( ajaxurl, params, function( response ) {
            switch( response.status ) {
                case 'continue':
                    params.spotim_page_number = params.spotim_page_number + 1;

                    importCommetsToWP( params, $importButton, $messageField );
                    break;
                case 'success':
                    break;
                case 'error':
                    $messageField.css({ 'color': '#db3737' });
                    break;
            }

            console.log( response.message );

            // show response message inside message field
            $messageField.html( response.message );

            // enable the import button
            $importButton.attr( 'disabled', false );

        }, 'json' );
    }

});