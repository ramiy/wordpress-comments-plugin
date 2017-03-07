<div class="wrap spotim-page-wrap">
    <h1><?php echo get_admin_page_title(); ?></h1>

    <nav class="nav-tab-wrapper">
        <a href="<?php echo esc_attr( '?page=' . $this->slug . '&tab=general' ); ?>" class="nav-tab <?php echo ( 'general' === $this->active_tab ) ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'General', 'spotim-comments' ); ?></a>
        <a href="<?php echo esc_attr( '?page=' . $this->slug . '&tab=display' ); ?>" class="nav-tab <?php echo ( 'display' === $this->active_tab ) ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Display', 'spotim-comments' ); ?></a>
        <a href="<?php echo esc_attr( '?page=' . $this->slug . '&tab=import'  ); ?>" class="nav-tab <?php echo ( 'import'  === $this->active_tab ) ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Import',  'spotim-comments' ); ?></a>
        <a href="<?php echo esc_attr( '?page=' . $this->slug . '&tab=export'  ); ?>" class="nav-tab <?php echo ( 'export'  === $this->active_tab ) ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Export',  'spotim-comments' ); ?></a>
    </nav>

    <form method="post" action="options.php">
        <?php
            settings_fields( $this->option_group );
            do_settings_sections( $this->slug );
            submit_button();
        ?>
    </form>
</div>