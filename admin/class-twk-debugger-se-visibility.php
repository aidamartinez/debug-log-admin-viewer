<?php
class Twk_Debugger_SE_Visibility {
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the settings for SE visibility notification
     */
    public function register_settings() {
        register_setting(
            $this->plugin_name,
            'twk_debugger_se_visibility_notification',
            array(
                'type' => 'boolean',
                'default' => false
            )
        );
    }

    /**
     * Add the settings field to the options page
     */
    public function add_settings_field() {
        add_settings_section(
            'twk_debugger_se_visibility_section',
            'Search Engine Visibility Notification',
            array($this, 'section_callback'),
            $this->plugin_name
        );

        add_settings_field(
            'twk_debugger_se_visibility_notification',
            'Enable Notification',
            array($this, 'field_callback'),
            $this->plugin_name,
            'twk_debugger_se_visibility_section'
        );
    }

    /**
     * Section description callback
     */
    public function section_callback() {
        echo '<p>Configure notifications for search engine visibility status.</p>';
    }

    /**
     * Field callback
     */
    public function field_callback() {
        $option = get_option('twk_debugger_se_visibility_notification', false);
        ?>
        <input type="checkbox" name="twk_debugger_se_visibility_notification" value="1" <?php checked($option, 1); ?> />
        <p class="description">Show a notification in the admin bar when search engines are discouraged from indexing this site.</p>
        <?php
    }

    /**
     * Register the stylesheets for the admin area.
     */
    public function enqueue_styles() {
        // Only enqueue if the notification is enabled and search engines are discouraged
        if (get_option('twk_debugger_se_visibility_notification', false) && !get_option('blog_public', 1)) {
            wp_enqueue_style(
                $this->plugin_name . '-admin',
                plugin_dir_url(__FILE__) . 'css/twk-debugger-admin.css',
                array(),
                $this->version,
                'all'
            );
        }
    }

    /**
     * Add the admin bar notification
     */
    public function maybe_add_admin_bar_notice($wp_admin_bar) {
        // Only show in admin area
        if (!is_admin()) {
            return;
        }

        // Check if notifications are enabled
        if (!get_option('twk_debugger_se_visibility_notification', false)) {
            return;
        }

        // Check if search engines are discouraged
        if (!get_option('blog_public', 1)) {
            $wp_admin_bar->add_node(array(
                'id' => 'twk-se-visibility-notice',
                'title' => 'SE Visibility: OFF',
                'parent' => 'top-secondary',
                'meta' => array(
                    'class' => 'twk-se-visibility-notice'
                )
            ));
        }
    }
} 