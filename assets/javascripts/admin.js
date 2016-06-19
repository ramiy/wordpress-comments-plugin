spotimVariables.pageNumber = parseInt( spotimVariables.pageNumber, 10 );

jQuery( document ).ready(function ( $ ) {

    $('#import_button').on('click', function( event ) {
        var $importButton = $(this),
            $messageField = $importButton.parent().find('.description'),
            spotIdInputValue = $importButton.parents('form').find('[name="wp-spotim-settings[spot_id]"]').attr('value').trim(),
            importTokenInputValue = $importButton.parents('.form-table').find('[name="wp-spotim-settings[import_token]"]').attr('value').trim(),
            postsPerRequestValue = $importButton.parents('.form-table').find('[name="wp-spotim-settings[posts_per_request]"]').attr('value').trim();


        // empty message field from any text and reset css
        $messageField
            .removeClass('red-color')
            .html('');

        // disable the import button
        $importButton.attr( 'disabled', true );

        var data = {
            'action': 'start_import',
            'spotim_spot_id': spotIdInputValue,
            'spotim_import_token': importTokenInputValue,
            'spotim_posts_per_request': postsPerRequestValue,

            // pageNumber is defined in options class,
            // inject from admin_javascript > spotim_variables.
            'spotim_page_number': spotimVariables.pageNumber
        };

        importCommetsToWP( data, $importButton, $messageField );

        event.preventDefault();
    });

    // checks for page number to be above zero to trigger #import_button
    if ( !! spotimVariables.pageNumber ) {
        $('#import_button').trigger('click');
    }

    function importCommetsToWP( params, $importButton, $messageField ) {
        $.post( ajaxurl, params, function( response ) {
            switch( response.status ) {
                case 'continue':
                    params.spotim_page_number = params.spotim_page_number + 1;

                    importCommetsToWP( params, $importButton, $messageField );
                    break;
                case 'success':
                    // enable the import button
                    $importButton.attr( 'disabled', false );

                    // reset page number to zero
                    spotimVariables.pageNumber = 0;
                    break;
                case 'error':
                    $messageField.addClass('red-color');

                    // enable the import button
                    $importButton.attr( 'disabled', false );
                    break;
            }

            console.log( response.message );

            // show response message inside message field
            $messageField.html( response.message );

        }, 'json' )
        .fail(function( response ) {
            console.log( 'error?', response );

            $messageField.addClass('red-color');

            // enable the import button
            $importButton.attr( 'disabled', false );

            // show response message inside message field
            $messageField.html( spotimVariables.errorMessage );
        });
    }

});