jQuery( document ).ready(function ( $ ) {
    var cancelImportProcess = false;

    spotimVariables.pageNumber = parseInt( spotimVariables.pageNumber, 10 );

    $('#import_button').on('click', function( event ) {
        var $importButton = $(this),
            $parentElement = $importButton.parent(),
            $messageField = $importButton.siblings('.description'),
            spotIdInputValue = $importButton.parents('form').find('[name="wp-spotim-settings[spot_id]"]').attr('value').trim(),
            importTokenInputValue = $importButton.parents('.form-table').find('[name="wp-spotim-settings[import_token]"]').attr('value').trim(),
            postsPerRequestValue = $importButton.parents('.form-table').find('[name="wp-spotim-settings[posts_per_request]"]').attr('value').trim();


        $parentElement.addClass('in-progress');

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

    $('#cancel_import_link').on('click', function( event ) {
        var cancelImportLink = $(this);
            $messageField = cancelImportLink.siblings('.description'),
            $parentElement = cancelImportLink.parent();
            data = {
                'action': 'cancel_import',
                'spotim_page_number': 0
            };

        $parentElement.removeClass('in-progress');
        cancelImportProcess = true;

        $messageField
            .removeClass('red-color')
            .html( spotimVariables.cancelImportMessage );

        $.post( ajaxurl, data, function() {
            window.location.reload( true );
        }, 'json' )
        .fail(function() {
            window.location.reload( true );
        });


        event.preventDefault();
    });

    // checks for page number to be above zero to trigger #import_button
    if ( !! spotimVariables.pageNumber ) {
        $('#import_button').trigger('click');
    }

    function importCommetsToWP( params, $importButton, $messageField ) {
        $.post( ajaxurl, params, function( response ) {
            if ( cancelImportProcess ) {
                return;
            }

            switch( response.status ) {
                case 'continue':
                    params.spotim_page_number = params.spotim_page_number + 1;

                    importCommetsToWP( params, $importButton, $messageField );
                    break;
                case 'success':
                    // enable the import button and hide cancel link
                    $importButton
                        .attr( 'disabled', false )
                        .parent()
                            .removeClass('in-progress');

                    // reset page number to zero
                    spotimVariables.pageNumber = 0;
                    break;
                case 'error':
                    $messageField.addClass('red-color');

                    // enable the import button and hide cancel link
                    $importButton
                        .attr( 'disabled', false )
                        .parent()
                            .removeClass('in-progress');
                    break;
            }

            // show response message inside message field
            $messageField.html( response.message );

        }, 'json' )
        .fail(function( response ) {
            $messageField.addClass('red-color');

            // enable the import button and hide cancel link
            $importButton
                .attr( 'disabled', false )
                .parent()
                    .removeClass('in-progress');

            // show response message inside message field
            $messageField.html( spotimVariables.errorMessage );
        });
    }

});