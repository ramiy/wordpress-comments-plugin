jQuery( document ).ready(function ( $ ) {

    $('#import_button').on('click', function( event ) {
        $this = $(this);
        $this.attr( 'disabled', true );

        $.post( ajaxurl, { 'action': 'start_import' }, function( response ) {
            $this.attr( 'disabled', false );

            console.log( response );
        }, 'json' );

        event.preventDefault();
    });

});