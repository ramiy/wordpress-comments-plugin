<div class="wrap">
    <div id="icon-themes" class="icon32"></div>
    <h2 class="spotim-page-title">
        <?php _e( 'Spot.IM Settings', self::$options->lang_slug ); ?>
    </h2>
    <form method="post" action="options.php">
        <?php
            settings_fields( self::$options->option_group );
            do_settings_sections( self::$options->slug );
            submit_button();
        ?>
    </form>
</div>