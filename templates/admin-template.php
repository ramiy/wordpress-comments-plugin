<div class="wrap spotim-page-wrap">
    <h1><?php echo get_admin_page_title(); ?></h1>

    <nav class="nav-tab-wrapper">
        <a href="?page=<?php echo $this->slug; ?>&tab=general" class="nav-tab <?php echo $this->active_tab == 'general' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'General', 'spotim-comments' ); ?></a>
        <a href="?page=<?php echo $this->slug; ?>&tab=display" class="nav-tab <?php echo $this->active_tab == 'display' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Display', 'spotim-comments' ); ?></a>
        <a href="?page=<?php echo $this->slug; ?>&tab=import"  class="nav-tab <?php echo $this->active_tab == 'import'  ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Import',  'spotim-comments' ); ?></a>
        <a href="?page=<?php echo $this->slug; ?>&tab=export"  class="nav-tab <?php echo $this->active_tab == 'export'  ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Export',  'spotim-comments' ); ?></a>
    </nav>

    <form method="post" action="options.php">
        <?php
            settings_fields( $this->option_group );
            do_settings_sections( $this->slug );
            submit_button();
        ?>
    </form>
</div>