jQuery( document ).ready(function ( $ ) {

    $('#import_button').on('click', function( event ) {
        var $this = $(this),
            $messageField = $this.parent().find('.description'),
            spotIdInputValue = $this.parents('form').find('[name="wp-spotim-settings[spot_id]"]').attr('value').trim(),
            importTokenInputValue = $this.parents('.form-table').find('[name="wp-spotim-settings[import_token]"]').attr('value').trim();

        // empty message field from any text and reset css
        $messageField
            .css({ 'color': '' })
            .html('');

        // disable the import button
        $this.attr( 'disabled', true );

        var data = {
            'action': 'start_import',
            'spotim_spot_id': spotIdInputValue,
            'spotim_import_token': importTokenInputValue
        };

        $.post( ajaxurl, data, function( response ) {
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