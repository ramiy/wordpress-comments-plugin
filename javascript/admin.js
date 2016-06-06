jQuery( document ).ready(function ( $ ) {

    $('#import_button').on('click', function( event ) {
        var $this = $(this),
            $messageField = $this.parent().find('.description');

        // empty message field from any text and reset css
        $messageField
            .css({ 'color': '' })
            .html('');

        // disable the import button
        $this.attr( 'disabled', true );

        $.post( ajaxurl, { 'action': 'start_import' }, function( response ) {
            if ( false === response.success ) {
                $messageField.css({ 'color': '#db3737' });
            }

            // show response message inside message field
            $messageField.html( response.data );

            // enable the import button
            $this.attr( 'disabled', false );

        }, 'json' );

        event.preventDefault();
    });

});