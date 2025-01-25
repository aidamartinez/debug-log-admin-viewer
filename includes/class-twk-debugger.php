<?php
class Twk_Debugger {
    protected $loader;
    protected $plugin_name;
    protected $version;

    public function __construct() {
        $this->plugin_name = 'twk-debugger';
        $this->version = TWK_DEBUGGER_VERSION;
        
        $this->load_dependencies();
        $this->define_admin_hooks();
    }

    private function load_dependencies() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-twk-debugger-loader.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-twk-debugger-admin.php';
        
        $this->loader = new Twk_Debugger_Loader();
    }

    private function define_admin_hooks() {
        $plugin_admin = new Twk_Debugger_Admin($this->get_plugin_name(), $this->get_version());
        
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_options_page');
        $this->loader->add_action('admin_init', $plugin_admin, 'register_settings');
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