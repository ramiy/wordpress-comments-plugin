<div class="wrap">
    <h1 class="spotim-page-title"><?php echo get_admin_page_title(); ?></h1>
    <form method="post" action="options.php">
        <?php
            settings_fields( $this->option_group );
            do_settings_sections( $this->slug );
            submit_button();
        ?>
    </form>
</div>