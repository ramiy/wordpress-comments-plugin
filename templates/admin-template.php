<div class="wrap">
    <h1 class="spotim-page-title"><?php esc_html_e( 'Spot.IM Settings', 'spotim-comments' ); ?></h1>
    <form method="post" action="options.php">
        <?php
            settings_fields( $this->option_group );
            do_settings_sections( $this->slug );
            submit_button();
        ?>
    </form>
</div>