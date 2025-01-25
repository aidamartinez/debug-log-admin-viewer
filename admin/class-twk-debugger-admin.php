<?php
class Twk_Debugger_Admin {
    private $plugin_name;
    private $version;
    private $wp_config_path;
    private $backup_dir;
    private $max_backups = 5;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->wp_config_path = $this->find_wp_config_path();
        
        // Set up backup directory
        $upload_dir = wp_upload_dir();
        $this->backup_dir = $upload_dir['basedir'] . '/twk-debugger';
        
        // Create backup directory if it doesn't exist
        if (!file_exists($this->backup_dir)) {
            wp_mkdir_p($this->backup_dir);
            
            // Create .htaccess to protect backups
            file_put_contents($this->backup_dir . '/.htaccess', 'deny from all');
            
            // Create index.php for extra security
            file_put_contents($this->backup_dir . '/index.php', '<?php // Silence is golden');
        }
    }

    private function find_wp_config_path() {
        // First check in root directory
        if (file_exists(ABSPATH . 'wp-config.php')) {
            return ABSPATH . 'wp-config.php';
        }
        // Then check one directory up
        elseif (file_exists(dirname(ABSPATH) . '/wp-config.php')) {
            return dirname(ABSPATH) . '/wp-config.php';
        }
        return false;
    }

    private function manage_backups() {
        // Get all backup files
        $backup_files = glob($this->backup_dir . '/wp-config-backup-*.php');
        
        if ($backup_files === false) {
            return false;
        }

        // Sort by creation time (oldest first)
        usort($backup_files, function($a, $b) {
            return filemtime($a) - filemtime($b);
        });

        // Remove oldest backups if we have more than max_backups
        while (count($backup_files) >= $this->max_backups) {
            $oldest_backup = array_shift($backup_files);
            unlink($oldest_backup);
        }

        return true;
    }

    private function create_backup() {
        // Manage existing backups first
        $this->manage_backups();

        // Create new backup filename with timestamp
        $backup_filename = 'wp-config-backup-' . date('Y-m-d-H-i-s') . '.php';
        $backup_path = $this->backup_dir . '/' . $backup_filename;

        // Copy the current wp-config.php to backup location
        if (!copy($this->wp_config_path, $backup_path)) {
            return false;
        }

        return $backup_path;
    }

    public function add_options_page() {
        add_options_page(
            'TWK Debugger Settings',
            'TWK Debugger',
            'manage_options',
            $this->plugin_name,
            array($this, 'display_options_page')
        );
    }

    public function register_settings() {
        // Register the settings
        register_setting($this->plugin_name, 'twk_debugger_settings', array(
            'sanitize_callback' => array($this, 'validate_settings')
        ));
    }

    public function validate_settings($input) {
        // Check if debug settings have actually changed
        $current_constants = $this->get_config_constants();
        $settings_changed = false;
        
        if (
            (!empty($input['wp_debug']) != $current_constants['WP_DEBUG']) ||
            (!empty($input['wp_debug_log']) != $current_constants['WP_DEBUG_LOG']) ||
            (!empty($input['wp_debug_display']) != $current_constants['WP_DEBUG_DISPLAY'])
        ) {
            $settings_changed = true;
        }

        // Only proceed with backup and wp-config modification if debug settings changed
        if ($settings_changed) {
            // Create backup first
            $backup_path = $this->create_backup();
            if (!$backup_path) {
                add_settings_error(
                    $this->plugin_name,
                    'backup_failed',
                    'Could not create backup of wp-config.php file.',
                    'error'
                );
                return false;
            }

            // Get wp-config.php content
            $config_content = file_get_contents($this->wp_config_path);
            if ($config_content === false) {
                add_settings_error(
                    $this->plugin_name,
                    'wp_config_not_readable',
                    'Could not read wp-config.php file.',
                    'error'
                );
                return false;
            }

            // Prepare the debug constants
            $debug_constants = "/* TWK Debugger Constants */\n";
            $debug_constants .= "define( 'WP_DEBUG', " . (!empty($input['wp_debug']) ? 'true' : 'false') . " );\n";
            $debug_constants .= "define( 'WP_DEBUG_LOG', " . (!empty($input['wp_debug_log']) ? 'true' : 'false') . " );\n";
            $debug_constants .= "define( 'WP_DEBUG_DISPLAY', " . (!empty($input['wp_debug_display']) ? 'true' : 'false') . " );\n\n";

            // Regular expressions for each constant
            $patterns = array(
                '/\/\* TWK Debugger Constants \*\/\n/',
                '/define\s*\(\s*[\'"]WP_DEBUG[\'"]\s*,\s*(?:true|false)\s*\)\s*;/i',
                '/define\s*\(\s*[\'"]WP_DEBUG_LOG[\'"]\s*,\s*(?:true|false)\s*\)\s*;/i',
                '/define\s*\(\s*[\'"]WP_DEBUG_DISPLAY[\'"]\s*,\s*(?:true|false)\s*\)\s*;/i'
            );

            // Remove existing debug constants if they exist
            foreach ($patterns as $pattern) {
                $config_content = preg_replace($pattern, '', $config_content);
            }

            // Find the position to insert the new constants
            $marker = "/* That's all, stop editing! Happy blogging. */";
            $pos = strpos($config_content, $marker);

            if ($pos !== false) {
                $config_content = substr_replace(
                    $config_content,
                    $debug_constants . $marker,
                    $pos,
                    strlen($marker)
                );
            } else {
                // If marker not found, append to end of file
                $config_content .= $debug_constants;
            }

            // Write the modified content back to wp-config.php
            if (file_put_contents($this->wp_config_path, $config_content) === false) {
                add_settings_error(
                    $this->plugin_name,
                    'wp_config_not_writable',
                    'Could not update wp-config.php file. Please check file permissions.',
                    'error'
                );
                return false;
            }

            add_settings_error(
                $this->plugin_name,
                'settings_updated',
                'Debug settings updated successfully.',
                'success'
            );
        }

        return $input;
    }

    public function display_options_page() {
        // Get actual values from wp-config.php
        $config_constants = $this->get_config_constants();
        
        // Check if wp-config.php is writable
        $config_writable = $this->wp_config_path && is_writable($this->wp_config_path);
        
        // Handle log clearing
        if (isset($_POST['clear_debug_log']) && check_admin_referer('twk_debugger_clear_log')) {
            $log_file = WP_CONTENT_DIR . '/debug.log';
            if (file_exists($log_file)) {
                file_put_contents($log_file, '');
            }
        }

        // Get debug log content
        $log_content = '';
        if ($config_constants['WP_DEBUG_LOG']) {
            $log_file = WP_CONTENT_DIR . '/debug.log';
            if (file_exists($log_file)) {
                $log_content = file_get_contents($log_file);
            }
        }

        ?>
        <div class="wrap">
            <h2>TWK Debugger Settings</h2>
            
            <?php if (!$config_writable): ?>
            <div class="notice notice-error">
                <p>Warning: wp-config.php is not writable. Please check file permissions or contact your server administrator.</p>
            </div>
            <?php endif; ?>

            <form method="post" action="options.php">
                <?php
                settings_fields($this->plugin_name);
                ?>

                <h2>Debug Settings</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable WP_DEBUG</th>
                        <td>
                            <input type="checkbox" name="twk_debugger_settings[wp_debug]" value="1" <?php checked($config_constants['WP_DEBUG'], true); ?> />
                            <p class="description">Enables WordPress debug mode</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Enable WP_DEBUG_LOG</th>
                        <td>
                            <input type="checkbox" name="twk_debugger_settings[wp_debug_log]" value="1" <?php checked($config_constants['WP_DEBUG_LOG'], true); ?> />
                            <p class="description">Saves debug messages to wp-content/debug.log</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Enable WP_DEBUG_DISPLAY</th>
                        <td>
                            <input type="checkbox" name="twk_debugger_settings[wp_debug_display]" value="1" <?php checked($config_constants['WP_DEBUG_DISPLAY'], true); ?> />
                            <p class="description">Shows debug messages on the front end</p>
                        </td>
                    </tr>
                </table>

                <?php
                // This will output the new SE visibility section
                do_settings_sections($this->plugin_name);
                ?>

                <?php submit_button(); ?>
            </form>

            <?php if ($config_constants['WP_DEBUG_LOG'] && !empty($log_content)): ?>
                <h3>Debug Log</h3>
                <form method="post">
                    <?php wp_nonce_field('twk_debugger_clear_log'); ?>
                    <input type="submit" name="clear_debug_log" class="button button-secondary" value="Clear Log File" />
                </form>
                <div style="background: #fff; padding: 10px; margin-top: 10px; border: 1px solid #ccc; max-height: 400px; overflow-y: auto;">
                    <pre><?php echo esc_html($log_content); ?></pre>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    private function get_config_constants() {
        if (!$this->wp_config_path || !file_exists($this->wp_config_path)) {
            return array(
                'WP_DEBUG' => false,
                'WP_DEBUG_LOG' => false,
                'WP_DEBUG_DISPLAY' => false
            );
        }

        $config_content = file_get_contents($this->wp_config_path);
        $constants = array(
            'WP_DEBUG' => false,
            'WP_DEBUG_LOG' => false,
            'WP_DEBUG_DISPLAY' => false
        );

        foreach ($constants as $constant => $default) {
            if (preg_match('/define\s*\(\s*[\'"]' . $constant . '[\'"]\s*,\s*(true|false)\s*\)/i', $config_content, $matches)) {
                $constants[$constant] = strtolower($matches[1]) === 'true';
            }
        }

        return $constants;
    }

    // Add a method to display backup files in the admin page
    private function get_backup_files() {
        $backup_files = glob($this->backup_dir . '/wp-config-backup-*.php');
        
        if ($backup_files === false) {
            return array();
        }

        // Sort by creation time (newest first)
        usort($backup_files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        return $backup_files;
    }
} 