<div class="wrap">
    <div id="icon-themes" class="icon32"></div>
    <h2 class="spotim-page-title">
        <?php _e( 'Spot.IM Settings', 'wp-spotim' ); ?>
    </h2>
    <form method="post" action="options.php">
        <?php
            settings_fields( $this->option_group );
            do_settings_sections( $this->slug );
            submit_button();
        ?>
    </form>
</div>