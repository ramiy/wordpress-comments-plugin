jQuery( document ).ready(function ( $ ) {
    var cancelImportProcess = false;

    spotimVariables.pageNumber = parseInt( spotimVariables.pageNumber, 10 );

    // Import
    $( '#import_button' ).on( 'click', function( event ) {
        var $importButton = $(this),
            $parentElement = $importButton.parent(),
            $messageField = $importButton.siblings( '.description' ),
            spotIdInputValue = $importButton.data( 'spot-id' ).trim(),
            importTokenInputValue = $importButton.data( 'import-token' ).trim(),
            postsPerRequestValue = parseInt( $importButton.data( 'posts-per-request' ) );

        $parentElement.addClass( 'in-progress' );

        // Empty message field from any text and reset css
        $messageField
            .removeClass( 'red-color' )
            .empty();

        // Disable the import button
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

        importCommentsToWP( data, $importButton, $messageField );

        event.preventDefault();
    });

    // Cancel import
    $( '#cancel_import_link' ).on( 'click', function( event ) {
        var cancelImportLink = $(this);
            $messageField = cancelImportLink.siblings( '.description' ),
            $parentElement = cancelImportLink.parent();
            data = {
                'action': 'cancel_import',
                'spotim_page_number': 0
            };

        $parentElement.removeClass( 'in-progress' );
        cancelImportProcess = true;

        $messageField
            .removeClass( 'red-color' )
            .text( spotimVariables.cancelImportMessage );

        $.post( ajaxurl, data, function() {
            window.location.reload( true );
        }, 'json' )
        .fail(function() {
            window.location.reload( true );
        });


        event.preventDefault();
    });

    // Checks for page number to be above zero to trigger #import_button
    if ( !! spotimVariables.pageNumber ) {
        $( '#import_button' ).trigger( 'click' );
    }

    // Import Commets to WordPress
    function importCommentsToWP( params, $importButton, $messageField ) {
        $.post( ajaxurl, params, function( response ) {
            if ( cancelImportProcess ) {
                return;
            }

            switch( response.status ) {
                case 'continue':
                    params.spotim_page_number = params.spotim_page_number + 1;

                    importCommentsToWP( params, $importButton, $messageField );
                    break;
                case 'success':
                    // Enable the import button and hide cancel link
                    $importButton
                        .attr( 'disabled', false )
                        .parent()
                            .removeClass( 'in-progress' );

                    // Reset page number to zero
                    spotimVariables.pageNumber = 0;
                    break;
                case 'error':
                    $messageField.addClass( 'red-color' );

                    // Enable the import button and hide cancel link
                    $importButton
                        .attr( 'disabled', false )
                        .parent()
                            .removeClass( 'in-progress' );
                    break;
            }

            // Show response message inside message field
            $messageField.text( response.message );

        }, 'json' )
        .fail(function( response ) {
            $messageField.addClass( 'red-color' );

            // Enable the import button and hide cancel link
            $importButton
                .attr( 'disabled', false )
                .parent()
                    .removeClass( 'in-progress' );

            // Show response message inside message field
            $messageField.text( spotimVariables.errorMessage );
        });
    }

});