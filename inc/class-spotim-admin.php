<?php

class SpotIM_Admin {

    private $hook;
    protected $_screens = array();
    protected $options;
    public $slug = 'wp-spotim-settings';

    public function __construct() {

        add_action('admin_menu', array($this, 'create_admin_menu'), 20);

        add_action('admin_init', array($this, 'register_settings'));
    }

    public function create_admin_menu() {

        $this->_screens['main'] = add_menu_page(__('Spot.IM Settings', 'wp-spotim'), __('Spot.IM', 'wp-spotim'), 'manage_options', $this->slug, array($this, 'admin_page_callback'));



        // Just make sure we are create instance.

        add_action('load-' . $this->_screens['main'], array(&$this, 'load_cb'));
    }

    public function register_settings() {

        $this->options = $this->get_options();



        // If no options exist, create them.

        if (!get_option($this->slug)) {

            update_option($this->slug, apply_filters('spotim_default_options', array(
                'enable_comments_replacement' => true,
                'spot_id' => 'sp_foo',
            )));
        }
        register_setting('wp-spotim-options', $this->slug, array($this, 'validate_options'));
        add_settings_section(
                'general_settings_section', // ID used to identify this section and with which to register options
                __('Commenting Options', 'wp-spotim'), // Title to be displayed on the administration page
                array('SpotIM_Settings_Fields', 'general_settings_section_header'), // Callback used to render the description of the section
                $this->slug  // Page on which to add this section of options
        );
        add_settings_field(
                'enable_comments_replacement', __('Enable Spot.IM comments?', 'wp-spotim'), array('SpotIM_Settings_Fields', 'yesno_field'), $this->slug, 'general_settings_section', array('id' => 'enable_comments_replacement', 'page' => $this->slug, 'value' => 1));

        add_settings_field(
                'enable_comments_on_page', __('Enable Spot.IM on pages?', 'wp-spotim'), array('SpotIM_Settings_Fields', 'yesno_field'), $this->slug, 'general_settings_section', array(
            'id' => 'enable_comments_on_page',
            'page' => $this->slug,
                )
        );

        add_settings_field(
                'spot_id', __('Your Spot ID', 'wp-spotim'), array('SpotIM_Settings_Fields', 'text_field'), $this->slug, 'general_settings_section', array(
            'id' => 'spot_id',
            'page' => $this->slug,
            'desc' => 'Find your Spot\'s ID at the <a href="https://www.spot.im/login" target="_blank">Spot management dashboard</a>.<br> Don\'t have an account? <a href="http://www.spot.im/" target="_blank">Create one</a> for free!'
                )
        );

        add_settings_field(
                'spot_export_comment_authors', __('Export Comment Authors & Author Email', 'wp-spotim'), array('SpotIM_Settings_Fields', 'export_author_field'), $this->slug, 'general_settings_section', array(
            'id' => 'export_comment_authors',
            'page' => $this->slug,
            'description' => 'Export Comment Authors & Author Email.'
                )
        );
    }

    public function get_option($key = '', $default_value = false) {

        $settings = $this->get_options();

        return !empty($settings[$key]) ? $settings[$key] : $default_value;
    }

    /**

     * Returns all options

     *

     * @since 0.1

     * @return array

     */
    public function get_options() {

        // Allow other plugins to get spotim's options.

        if (isset($this->options) && is_array($this->options) && !empty($this->options))
            return $this->options;



        return apply_filters('spotim_options', get_option($this->slug, array()));
    }

    public function validate_options($input) {

        $options = $this->options; // CTX,L1504
        // @todo some data validation/sanitization should go here

        $output = apply_filters('spotim_validate_options', $input, $options);

        // merge with current settings

        $output = array_merge($options, $output);



        return $output;
    }

    public function admin_page_callback() {
        ?>
        <div class="wrap">
            <div id="icon-themes" class="icon32"></div>
            <h2 class="spotim-page-title"><?php _e('Spot.IM Settings', 'wp-spotim'); ?></h2>
            <script>
                jQuery(document).ready(function ($) {
                    function spotimExportCommentAuthors(obj) {
                        var obj_value = $(obj).val();
                        if ($(obj).hasClass('busy'))
                            return false;

                        $(obj).addClass('busy');
                        $(obj).val('Exporting data...');
                        
                        $.ajax({
                            url: ajaxurl,
                            type: 'post',
                            dataType: 'json',
                            data: {
                                'action': 'spotim_export_comment_authors'
                            },
                            beforeSend: function () {

                            },
                            success: function (data) {
                                $(obj).removeClass('busy');
                                $(obj).val(obj_value);
                                if (data.status === "success") {
                                    window.open(data.export_file, 'Download');
                                }
                            }
                        });
                        return true;
                    }
                    $('#spotim_export_comment_authors').click(function () {
                        window.location = window.location.href + '&download=comment-authors';
                    });
                });
            </script>
            <form method="post" action="options.php">
                <?php
                settings_fields('wp-spotim-options');
                do_settings_sections($this->slug);
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function load_cb() {
        
    }

}

final class SpotIM_Settings_Fields {

    public static function general_settings_section_header() {
        ?>
        <p><?php _e('These are some basic settings for SpotIM.', 'wp-spotim'); ?></p>
        <?php
    }

    public static function raw_html($args) {

        if (empty($args['html']))
            return;
        echo $args['html'];
        if (!empty($args['desc'])) :
            ?>
            <p class="description"><?php echo $args['desc']; ?></p>
            <?php
        endif;
    }

    public static function text_field($args) {

        self::_set_name_and_value($args);

        extract($args, EXTR_SKIP);

        $args = wp_parse_args($args, array(
            'classes' => array(),
        ));

        if (empty($args['id']) || empty($args['page']))
            return;
        ?>

        <input type="text" id="<?php echo esc_attr($args['id']); ?>" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($value); ?>" class="<?php echo implode(' ', $args['classes']); ?>" />

        <?php if (!empty($desc)) : ?>

            <p class="description"><?php echo $desc; ?></p>

            <?php
        endif;
    }

    public static function textarea_field($args) {

        self::_set_name_and_value($args);

        extract($args, EXTR_SKIP);

        $args = wp_parse_args($args, array(
            'classes' => array(),
            'rows' => 5,
            'cols' => 50,
        ));

        if (empty($args['id']) || empty($args['page']))
            return;
        ?>
        <textarea id="<?php echo esc_attr($args['id']); ?>" name="<?php echo esc_attr($name); ?>" class="<?php echo implode(' ', $args['classes']); ?>" rows="<?php echo absint($args['rows']); ?>" cols="<?php echo absint($args['cols']); ?>"><?php echo esc_textarea($value); ?></textarea>
        <?php if (!empty($desc)) : ?>

            <p class="description"><?php echo $desc; ?></p>

            <?php
        endif;
    }

    public static function number_field($args) {

        self::_set_name_and_value($args);

        extract($args, EXTR_SKIP);
        $args = wp_parse_args($args, array(
            'classes' => array(),
            'min' => '1',
            'step' => '1',
            'desc' => '',
        ));

        if (empty($args['id']) || empty($args['page']))
            return;
        ?>

        <input type="number" id="<?php echo esc_attr($args['id']); ?>" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($value); ?>" class="<?php echo implode(' ', $args['classes']); ?>" min="<?php echo $args['min']; ?>" step="<?php echo $args['step']; ?>" />

        <?php if (!empty($args['sub_desc'])) echo $args['sub_desc']; ?>

        <?php if (!empty($args['desc'])) : ?>

            <p class="description"><?php echo $args['desc']; ?></p>

            <?php
        endif;
    }

    public static function select_field($args) {

        self::_set_name_and_value($args);

        extract($args, EXTR_SKIP);

        if (empty($options) || empty($id) || empty($page))
            return;
        ?>

        <select id="<?php echo esc_attr($id); ?>" name="<?php printf('%s[%s]', esc_attr($page), esc_attr($id)); ?>">

            <?php foreach ($options as $name => $label) : ?>

                <option value="<?php echo esc_attr($name); ?>" <?php selected($name, (string) $value); ?>>

                    <?php echo esc_html($label); ?>

                </option>

            <?php endforeach; ?>

        </select>

        <?php if (!empty($desc)) : ?>

            <p class="description"><?php echo $desc; ?></p>

        <?php endif; ?>

        <?php
    }

    public static function export_author_field($args) {

        self::_set_name_and_value($args);

        extract($args, EXTR_SKIP);
        ?>

        <label class="tix-comment-author description"><input class="button button-primary" type="button" name="export_comment" value="Export Comment Authors & Author Email" id="spotim_export_comment_authors" /></label>

        <?php if (isset($args['description'])) : ?>

            <p class="description"><?php echo $args['description']; ?></p>

        <?php endif; ?>

        <?php
    }

    public static function yesno_field($args) {

        self::_set_name_and_value($args);

        extract($args, EXTR_SKIP);
        ?>

        <label class="tix-yes-no description"><input type="radio" name="<?php echo esc_attr($name); ?>" value="1" <?php checked($value, true); ?>> <?php _e('Yes', 'wp-spotim'); ?></label>

        <label class="tix-yes-no description"><input type="radio" name="<?php echo esc_attr($name); ?>" value="0" <?php checked($value, false); ?>> <?php _e('No', 'wp-spotim'); ?></label>

        <?php if (isset($args['description'])) : ?>

            <p class="description"><?php echo $args['description']; ?></p>

        <?php endif; ?>

        <?php
    }

    private static function _set_name_and_value(&$args) {

        if (!isset($args['name'])) {

            $args['name'] = sprintf('%s[%s]', esc_attr($args['page']), esc_attr($args['id']));
        }
        if (!isset($args['value'])) {

            $args['value'] = WP_SpotIM::instance()->admin->get_option($args['id']);
        }
    }

}
