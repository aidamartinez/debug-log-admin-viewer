<?php
class Twk_Debugger {
    protected $loader;
    protected $plugin_name;
    protected $version;
    protected $se_visibility;

    public function __construct() {
        $this->plugin_name = 'twk-debugger';
        $this->version = TWK_DEBUGGER_VERSION;
        
        $this->load_dependencies();
        $this->define_admin_hooks();
    }

    private function load_dependencies() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-twk-debugger-loader.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-twk-debugger-admin.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-twk-debugger-se-visibility.php';
        
        $this->loader = new Twk_Debugger_Loader();
        $this->se_visibility = new Twk_Debugger_SE_Visibility($this->plugin_name, $this->version);
    }

    private function define_admin_hooks() {
        $plugin_admin = new Twk_Debugger_Admin($this->plugin_name, $this->version);
        
        // Debug settings hooks
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_options_page');
        $this->loader->add_action('admin_init', $plugin_admin, 'register_settings');

        // SE Visibility hooks
        $this->loader->add_action('admin_init', $this->se_visibility, 'register_settings');
        $this->loader->add_action('admin_init', $this->se_visibility, 'add_settings_field');
        $this->loader->add_action('admin_bar_menu', $this->se_visibility, 'maybe_add_admin_bar_notice', 100);
        
        // Enqueue styles for admin only
        $this->loader->add_action('admin_enqueue_scripts', $this->se_visibility, 'enqueue_styles');
    }

    public function run() {
        $this->loader->run();
    }

    public function get_plugin_name() {
        return $this->plugin_name;
    }

    public function get_version() {
        return $this->version;
    }
} 