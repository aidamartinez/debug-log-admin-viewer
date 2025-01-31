<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package    TWK_Utils
 * @subpackage TWK_Utils/admin
 */

class Twk_Utils_Admin {
	/**
	 * The ID of this plugin.
	 *
	 * @var string
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Path to wp-config.php file.
	 *
	 * @var string
	 */
	private $wp_config_path;

	/**
	 * Path to backup directory.
	 *
	 * @var string
	 */
	private $backup_dir;

	/**
	 * Maximum number of backups to keep.
	 *
	 * @var int
	 */
	private $max_backups = 5;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name     = $plugin_name;
		$this->version        = $version;
		$this->wp_config_path = $this->find_wp_config_path();
		
		// Set up backup directory.
		$upload_dir          = wp_upload_dir();
		$this->backup_dir    = $upload_dir['basedir'] . '/twk-utils';
		
		// Create backup directory if it doesn't exist.
		if ( ! file_exists( $this->backup_dir ) ) {
			wp_mkdir_p( $this->backup_dir );
			
			// Create .htaccess to protect backups.
			file_put_contents( $this->backup_dir . '/.htaccess', 'deny from all' );
			
			// Create index.php for extra security.
			file_put_contents( $this->backup_dir . '/index.php', '<?php // Silence is golden' );
		}

		// Add these lines to hook the enqueue function
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	/**
	 * Find the wp-config.php file path.
	 *
	 * @return string|false Path to wp-config.php or false if not found.
	 */
	private function find_wp_config_path() {
		// First check in root directory.
		if ( file_exists( ABSPATH . 'wp-config.php' ) ) {
			return ABSPATH . 'wp-config.php';
		} elseif ( file_exists( dirname( ABSPATH ) . '/wp-config.php' ) ) {
			return dirname( ABSPATH ) . '/wp-config.php';
		}
		return false;
	}

	/**
	 * Manage backup files rotation.
	 *
	 * @return bool True on success, false on failure.
	 */
	private function manage_backups() {
		$backup_files = glob( $this->backup_dir . '/wp-config-backup-*.php' );
		
		if ( false === $backup_files ) {
			return false;
		}

		// Sort by creation time (oldest first).
		usort(
			$backup_files,
			function( $a, $b ) {
				return filemtime( $a ) - filemtime( $b );
			}
		);

		// Remove oldest backups if we have more than max_backups.
		while ( count( $backup_files ) >= $this->max_backups ) {
			$oldest_backup = array_shift( $backup_files );
			unlink( $oldest_backup );
		}

		return true;
	}

	/**
	 * Create a backup of wp-config.php file.
	 *
	 * @return string|false Path to backup file or false on failure.
	 */
	private function create_backup() {
		// Manage existing backups first.
		$this->manage_backups();

		// Create new backup filename with timestamp.
		$backup_filename = 'wp-config-backup-' . date( 'Y-m-d-H-i-s' ) . '.php';
		$backup_path    = $this->backup_dir . '/' . $backup_filename;

		// Copy the current wp-config.php to backup location.
		if ( ! copy( $this->wp_config_path, $backup_path ) ) {
			return false;
		}

		return $backup_path;
	}

	/**
	 * Add options page to WordPress admin menu.
	 */
	public function add_options_page() {
		add_options_page(
			'TWK Utils Settings',
			'TWK Utils',
			'manage_options',
			$this->plugin_name,
			array( $this, 'display_options_page' )
		);
	}

	/**
	 * Register plugin settings.
	 */
	public function register_settings() {
		// Debug settings.
		register_setting(
			'twk_utils_debug_group',
			'twk_utils_debug_settings',
			array(
				'sanitize_callback' => array( $this, 'validate_settings' ),
			)
		);

		// SE Visibility notification setting.
		register_setting(
			$this->plugin_name,
			'twk_utils_se_visibility_notification',
			array(
				'type'    => 'boolean',
				'default' => false,
			)
		);
	}

	/**
	 * Validate and save settings.
	 *
	 * @param array $input The input array to validate.
	 * @return array
	 */
	public function validate_settings( $input ) {
		$current_constants = $this->get_config_constants();
		$settings_changed = false;
		$debug_info = array(); // Array to store debug information
		
		// Ensure input is an array.
		$input = is_array( $input ) ? $input : array();
		
		// Compare with current settings.
		if (
			isset( $input['wp_debug'] ) !== $current_constants['WP_DEBUG'] ||
			isset( $input['wp_debug_log'] ) !== $current_constants['WP_DEBUG_LOG'] ||
			isset( $input['wp_debug_display'] ) !== $current_constants['WP_DEBUG_DISPLAY']
		) {
			$settings_changed = true;
		}

		if ( $settings_changed ) {
			// Store initial state
			$debug_info['initial_perms'] = substr(sprintf('%o', fileperms($this->wp_config_path)), -4);
			$debug_info['is_writable'] = is_writable($this->wp_config_path);
			$debug_info['file_owner'] = fileowner($this->wp_config_path);
			$debug_info['php_user'] = getmyuid();

			// Check if wp-config.php exists and is writable
			if ( ! $this->wp_config_path || ! file_exists( $this->wp_config_path ) ) {
				error_log('TWK Utils: wp-config.php not found at: ' . $this->wp_config_path);
				add_settings_error(
					$this->plugin_name,
					'wp_config_not_found',
					'wp-config.php file not found. Debug info: ' . json_encode($debug_info),
					'error'
				);
				return $input;
			}

			// Try to make the file writable if it isn't already
			$original_perms = null;
			if ( ! is_writable( $this->wp_config_path ) ) {
				$original_perms = fileperms( $this->wp_config_path );
				$debug_info['original_perms'] = substr(sprintf('%o', $original_perms), -4);
				
				// Try to modify permissions
				if ( ! @chmod( $this->wp_config_path, 0644 ) ) {
					error_log('TWK Utils: Failed to modify wp-config.php permissions');
					add_settings_error(
						$this->plugin_name,
						'wp_config_not_writable',
						'Cannot modify wp-config.php permissions. Debug info: ' . json_encode($debug_info),
						'error'
					);
					return $input;
				}
				$debug_info['modified_perms'] = '0644';
			}

			// Create backup
			$backup_path = $this->create_backup();
			if ( ! $backup_path ) {
				if ( $original_perms !== null ) {
					@chmod( $this->wp_config_path, $original_perms );
				}
				error_log('TWK Utils: Failed to create backup');
				add_settings_error(
					$this->plugin_name,
					'backup_failed',
					'Could not create backup. Debug info: ' . json_encode($debug_info),
					'error'
				);
				return $input;
			}

			// Read current content
			$config_content = @file_get_contents( $this->wp_config_path );
			if ( false === $config_content ) {
				if ( $original_perms !== null ) {
					@chmod( $this->wp_config_path, $original_perms );
				}
				error_log('TWK Utils: Failed to read wp-config.php');
				add_settings_error(
					$this->plugin_name,
					'wp_config_not_readable',
					'Could not read wp-config.php. Debug info: ' . json_encode($debug_info),
					'error'
				);
				return $input;
			}

			// Store original content length for verification
			$debug_info['original_content_length'] = strlen($config_content);

			// Prepare new constants
			$new_constants = array(
				'WP_DEBUG' => ! empty( $input['wp_debug'] ),
				'WP_DEBUG_LOG' => ! empty( $input['wp_debug_log'] ),
				'WP_DEBUG_DISPLAY' => ! empty( $input['wp_debug_display'] )
			);

			// Remove any existing TWK Utils Constants block
			$config_content = preg_replace(
				'/\/\* TWK Utils Debug Constants \*\/\n.*?\n\n/s',
				'',
				$config_content
			);

			// Remove any existing debug constants
			foreach ( array_keys( $new_constants ) as $constant ) {
				$config_content = preg_replace(
					"/define\s*\(\s*['\"]" . $constant . "['\"]\s*,\s*(true|false)\s*\);\n?/i",
					'',
					$config_content
				);
			}

			// Clean up any multiple blank lines created by removals
			$config_content = preg_replace("/\n{3,}/", "\n\n", $config_content);

			// Create new constants block
			$constants_block = array();
			foreach ( $new_constants as $name => $value ) {
				$constants_block[] = "define( '" . $name . "', " . ($value ? 'true' : 'false') . " );";
			}
			$constants_block = "/* TWK Utils Debug Constants */\n" . implode("\n", $constants_block) . "\n\n";

			// Add new constants before the "stop editing" line
			$marker = "/* That's all, stop editing! Happy blogging. */";
			$pos = strpos( $config_content, $marker );
			if ( false !== $pos ) {
				$config_content = substr_replace( $config_content, $constants_block, $pos, 0 );
			} else {
				// If marker not found, add at the end of the file
				$config_content = rtrim($config_content) . "\n\n" . $constants_block;
			}

			// Store new content length
			$debug_info['new_content_length'] = strlen($config_content);

			// Write the modified content
			if ( false === @file_put_contents( $this->wp_config_path, $config_content ) ) {
				if ( $original_perms !== null ) {
					@chmod( $this->wp_config_path, $original_perms );
				}
				error_log('TWK Utils: Failed to write to wp-config.php');
				add_settings_error(
					$this->plugin_name,
					'wp_config_not_writable',
					'Could not write to wp-config.php. Debug info: ' . json_encode($debug_info),
					'error'
				);
				return $input;
			}

			// Verify the changes
			$new_constants = $this->get_config_constants();
			$debug_info['final_constants'] = $new_constants;

			if (
				$new_constants['WP_DEBUG'] !== ! empty( $input['wp_debug'] ) ||
				$new_constants['WP_DEBUG_LOG'] !== ! empty( $input['wp_debug_log'] ) ||
				$new_constants['WP_DEBUG_DISPLAY'] !== ! empty( $input['wp_debug_display'] )
			) {
				error_log('TWK Utils: Constants verification failed. Debug info: ' . json_encode($debug_info));
				add_settings_error(
					$this->plugin_name,
					'settings_not_updated',
					'Settings verification failed. Debug info: ' . json_encode($debug_info),
					'error'
				);
				return $input;
			}

			// Restore original permissions
			if ( $original_perms !== null ) {
				@chmod( $this->wp_config_path, $original_perms );
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

	/**
	 * Display the options page content.
	 */
	public function display_options_page() {
		// Get current tab.
		$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'debug';
		
		// Get actual values from wp-config.php.
		$config_constants = $this->get_config_constants();
		$config_writable = $this->wp_config_path && is_writable( $this->wp_config_path );
		
		// Handle log clearing.
		if ( isset( $_POST['clear_debug_log'] ) && check_admin_referer( 'twk_debugger_clear_log' ) ) {
			$log_file = WP_CONTENT_DIR . '/debug.log';
			if ( file_exists( $log_file ) ) {
				file_put_contents( $log_file, '' );
			}
		}

		// Get debug log content.
		$log_content = '';
		if ( $config_constants['WP_DEBUG_LOG'] ) {
			$log_file = WP_CONTENT_DIR . '/debug.log';
			if ( file_exists( $log_file ) ) {
				$log_content = file_get_contents( $log_file );
			}
		}
		?>
		<div class="wrap">
			<h2><?php esc_html_e( 'TWK Utils Settings', 'twk-utils' ); ?></h2>

			<h2 class="nav-tab-wrapper">
				<a href="?page=<?php echo esc_attr( $this->plugin_name ); ?>&tab=debug" 
					class="nav-tab <?php echo $active_tab === 'debug' ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'DEBUG', 'twk-utils' ); ?>
				</a>
				<a href="?page=<?php echo esc_attr( $this->plugin_name ); ?>&tab=misc" 
					class="nav-tab <?php echo $active_tab === 'misc' ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Miscellaneous', 'twk-utils' ); ?>
				</a>
			</h2>

			<?php if ( ! $config_writable ) : ?>
				<div class="notice notice-error">
					<p><?php esc_html_e( 'Warning: wp-config.php is not writable. Please check file permissions or contact your server administrator.', 'twk-utils' ); ?></p>
				</div>
			<?php endif; 

			if ( 'debug' === $active_tab ) : ?>
				<form method="post" action="options.php">
					<?php
					settings_fields( 'twk_utils_debug_group' );
					?>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Enable WP_DEBUG', 'twk-utils' ); ?></th>
							<td>
								<input type="checkbox" name="twk_utils_debug_settings[wp_debug]" 
									value="1" <?php checked( $config_constants['WP_DEBUG'], true ); ?> />
								<p class="description"><?php esc_html_e( 'Enables WordPress debug mode', 'twk-utils' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Enable WP_DEBUG_LOG', 'twk-utils' ); ?></th>
							<td>
								<input type="checkbox" name="twk_utils_debug_settings[wp_debug_log]" 
									value="1" <?php checked( $config_constants['WP_DEBUG_LOG'], true ); ?> />
								<p class="description"><?php esc_html_e( 'Saves debug messages to wp-content/debug.log', 'twk-utils' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Enable WP_DEBUG_DISPLAY', 'twk-utils' ); ?></th>
							<td>
								<input type="checkbox" name="twk_utils_debug_settings[wp_debug_display]" 
									value="1" <?php checked( $config_constants['WP_DEBUG_DISPLAY'], true ); ?> />
								<p class="description"><?php esc_html_e( 'Shows debug messages on the front end', 'twk-utils' ); ?></p>
							</td>
						</tr>
					</table>

					<?php submit_button(); ?>
				</form>

				<?php if ( $config_constants['WP_DEBUG_LOG'] && ! empty( $log_content ) ) : ?>
					<h3><?php esc_html_e( 'Debug Log', 'twk-utils' ); ?></h3>
					<form method="post">
						<?php wp_nonce_field( 'twk_debugger_clear_log' ); ?>
						<input type="submit" name="clear_debug_log" class="button button-secondary" 
							value="<?php esc_attr_e( 'Clear Log File', 'twk-utils' ); ?>" />
					</form>
					<?php $this->display_debug_log_viewer( $log_content ); ?>
				<?php endif;
			else : ?>
				<form method="post" action="options.php">
					<?php
					settings_fields( 'twk_utils_misc_group' );
					do_settings_sections( $this->plugin_name . '_misc' );
					submit_button();
					?>
				</form>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Get WordPress configuration constants.
	 *
	 * @return array Array of configuration constants and their values.
	 */
	private function get_config_constants() {
		if ( ! $this->wp_config_path || ! file_exists( $this->wp_config_path ) ) {
			return array(
				'WP_DEBUG'         => false,
				'WP_DEBUG_LOG'     => false,
				'WP_DEBUG_DISPLAY' => false,
			);
		}

		$config_content = file_get_contents( $this->wp_config_path );
		$constants     = array(
			'WP_DEBUG'         => false,
			'WP_DEBUG_LOG'     => false,
			'WP_DEBUG_DISPLAY' => false,
		);

		foreach ( $constants as $constant => $default ) {
			if ( preg_match( '/define\s*\(\s*[\'"]' . $constant . '[\'"]\s*,\s*(true|false)\s*\)/i', $config_content, $matches ) ) {
				$constants[ $constant ] = 'true' === strtolower( $matches[1] );
			}
		}

		return $constants;
	}

	/**
	 * Get list of backup files.
	 *
	 * @return array Array of backup file paths.
	 */
	private function get_backup_files() {
		$backup_files = glob( $this->backup_dir . '/wp-config-backup-*.php' );
		
		if ( false === $backup_files ) {
			return array();
		}

		// Sort by creation time (newest first).
		usort(
			$backup_files,
			function( $a, $b ) {
				return filemtime( $b ) - filemtime( $a );
			}
		);

		return $backup_files;
	}

	/**
	 * Updates the wp-config.php file with a new constant.
	 *
	 * @param string $constant_name  The constant name.
	 * @param mixed  $constant_value The constant value.
	 * @return bool True if successful, false otherwise.
	 */
	private function update_wp_config( $constant_name, $constant_value ) {
		$config_path    = ABSPATH . 'wp-config.php';
		$config_content = file_get_contents( $config_path );

		if ( false === $config_content ) {
			return false;
		}

		// Format the constant value.
		if ( is_bool( $constant_value ) ) {
			$constant_value = $constant_value ? 'true' : 'false';
		} elseif ( is_string( $constant_value ) ) {
			$constant_value = "'" . addslashes( $constant_value ) . "'";
		}

		$constants_marker  = '/* TWK Utils Constants */';
		$constants_start   = strpos( $config_content, $constants_marker );

		if ( false === $constants_start ) {
			// First constant being added.
			$config_content  = rtrim( $config_content ) . "\n\n" . $constants_marker . "\n";
			$config_content .= "define( '{$constant_name}', {$constant_value} );\n";
		} else {
			// Check if constant already exists.
			if ( false === strpos( $config_content, "define( '{$constant_name}'" ) ) {
				// Get the content before and after the marker.
				$before_constants = substr( $config_content, 0, $constants_start );
				$after_constants  = substr( $config_content, $constants_start );

				// Clean up any extra newlines before the marker.
				$before_constants = rtrim( $before_constants ) . "\n\n";

				// Add the new constant definition right after the marker.
				$after_constants = preg_replace(
					'/(' . preg_quote( $constants_marker ) . ')\n/',
					"$1\ndefine( '{$constant_name}', {$constant_value} );\n",
					$after_constants,
					1
				);

				$config_content = $before_constants . $after_constants;
			}
		}

		return false !== file_put_contents( $config_path, $config_content );
	}

	/**
	 * Parse debug log content and categorize errors.
	 *
	 * @param string $log_content Raw log content.
	 * @return array Categorized log entries.
	 */
	private function parse_debug_log( $log_content ) {
		$entries = array();
		$lines = explode( "\n", $log_content );
		$current_entry = null;
		
		foreach ( $lines as $line ) {
			if ( empty( trim( $line ) ) ) {
				continue;
			}

			// Check if line starts with a timestamp
			$pattern = '/^\[(.+?)\]\s(.+)$/';
			if ( preg_match( $pattern, $line, $matches ) ) {
				// If we have a previous entry, save it
				if ( $current_entry ) {
					$entries[] = $current_entry;
				}
				
				// Start new entry
				$timestamp = $matches[1];
				$message = $matches[2];
				
				$current_entry = array(
					'timestamp' => $timestamp,
					'message'   => $message,
					'full_message' => $line,
					'type'      => $this->determine_error_type($message),
					'class'     => $this->determine_error_class($message),
				);
			} elseif ( $current_entry && (
				strpos( $line, 'Stack trace:' ) === 0 ||
				strpos( $line, '#' ) === 0 ||
				strpos( $line, 'thrown in' ) !== false
			) ) {
				// Append stack trace to current entry
				$current_entry['message'] .= "\n" . $line;
				$current_entry['full_message'] .= "\n" . $line;
			} else {
				// If we have a previous entry, save it
				if ( $current_entry ) {
					$entries[] = $current_entry;
				}
				
				// Create new entry for unknown format
				$current_entry = array(
					'timestamp' => '',
					'message'   => $line,
					'full_message' => $line,
					'type'      => 'Unknown',
					'class'     => 'log-unknown',
				);
			}
		}
		
		// Add the last entry if exists
		if ( $current_entry ) {
			$entries[] = $current_entry;
		}

		return $entries;
	}

	/**
	 * Determine error type from message.
	 *
	 * @param string $message The error message.
	 * @return string The error type.
	 */
	private function determine_error_type( $message ) {
		if ( stripos( $message, 'Fatal error' ) !== false || stripos( $message, 'E_ERROR' ) !== false ) {
			return 'Fatal Error';
		} elseif ( stripos( $message, 'Parse error' ) !== false || stripos( $message, 'E_PARSE' ) !== false ) {
			return 'Parse Error';
		} elseif ( stripos( $message, 'Database error' ) !== false || stripos( $message, 'MySQL' ) !== false ) {
			return 'Database Error';
		} elseif ( stripos( $message, 'Warning' ) !== false || stripos( $message, 'E_WARNING' ) !== false ) {
			return 'Warning';
		} elseif ( stripos( $message, 'Deprecated' ) !== false || stripos( $message, 'E_DEPRECATED' ) !== false ) {
			return 'Deprecated';
		} elseif ( stripos( $message, 'Strict Standards' ) !== false || stripos( $message, 'E_STRICT' ) !== false ) {
			return 'Strict Standards';
		} elseif ( stripos( $message, 'Notice' ) !== false || stripos( $message, 'E_NOTICE' ) !== false ) {
			return 'Notice';
		}
		return 'Unknown';
	}

	/**
	 * Determine error class from message.
	 *
	 * @param string $message The error message.
	 * @return string The error class.
	 */
	private function determine_error_class( $message ) {
		if ( stripos( $message, 'Fatal error' ) !== false || stripos( $message, 'E_ERROR' ) !== false ) {
			return 'log-fatal';
		} elseif ( stripos( $message, 'Parse error' ) !== false || stripos( $message, 'E_PARSE' ) !== false ) {
			return 'log-parse';
		} elseif ( stripos( $message, 'Database error' ) !== false || stripos( $message, 'MySQL' ) !== false ) {
			return 'log-database';
		} elseif ( stripos( $message, 'Warning' ) !== false || stripos( $message, 'E_WARNING' ) !== false ) {
			return 'log-warning';
		} elseif ( stripos( $message, 'Deprecated' ) !== false || stripos( $message, 'E_DEPRECATED' ) !== false ) {
			return 'log-deprecated';
		} elseif ( stripos( $message, 'Strict Standards' ) !== false || stripos( $message, 'E_STRICT' ) !== false ) {
			return 'log-strict';
		} elseif ( stripos( $message, 'Notice' ) !== false || stripos( $message, 'E_NOTICE' ) !== false ) {
			return 'log-notice';
		}
		return 'log-unknown';
	}

	/**
	 * Display the debug log viewer.
	 *
	 * @param string $log_content Raw log content.
	 */
	private function display_debug_log_viewer( $log_content ) {
		$entries = $this->parse_debug_log( $log_content );
		$error_types = array(
			'fatal'      => __( 'Fatal Errors', 'twk-utils' ),
			'parse'      => __( 'Parse Errors', 'twk-utils' ),
			'database'   => __( 'Database Errors', 'twk-utils' ),
			'warning'    => __( 'Warnings', 'twk-utils' ),
			'deprecated' => __( 'Deprecated', 'twk-utils' ),
			'strict'     => __( 'Strict Standards', 'twk-utils' ),
			'notice'     => __( 'Notices', 'twk-utils' ),
			'unknown'    => __( 'Other', 'twk-utils' ),
		);

		// Pagination settings
		$entries_per_page = 100;
		$current_page = isset( $_GET['log_page'] ) ? max( 1, intval( $_GET['log_page'] ) ) : 1;
		$total_pages = ceil( count( $entries ) / $entries_per_page );
		$offset = ( $current_page - 1 ) * $entries_per_page;
		$paged_entries = array_slice( $entries, $offset, $entries_per_page );
		?>
		<div class="debug-log-viewer">
			<div class="debug-log-controls">
				<div class="debug-log-search">
					<input type="text" id="log-search" placeholder="<?php esc_attr_e( 'Search log entries...', 'twk-utils' ); ?>" />
				</div>
				<div class="debug-log-filters">
					<?php foreach ( $error_types as $type => $label ) : ?>
						<label>
							<input type="checkbox" 
								class="log-filter" 
								data-type="log-<?php echo esc_attr( $type ); ?>" 
								checked="checked"
							/>
							<?php echo esc_html( $label ); ?>
						</label>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="debug-log-entries">
				<?php if ( empty( $entries ) ) : ?>
					<p><?php esc_html_e( 'No log entries found.', 'twk-utils' ); ?></p>
				<?php else : ?>
					<?php foreach ( $paged_entries as $entry ) : ?>
						<div class="log-entry <?php echo esc_attr( $entry['class'] ); ?>">
							<?php
							// Split message into main error and stack trace
							$message_parts = $this->split_error_and_stack_trace( $entry['message'] );
							?>
							<pre class="log-message">
								<?php if ( ! empty( $entry['timestamp'] ) ) : ?>
									<strong>[<?php echo esc_html( $entry['timestamp'] ); ?>]</strong>
								<?php endif; ?>
								<?php if ( ! empty( $entry['type'] ) ) : ?>
									<strong><?php echo esc_html( $entry['type'] ); ?>:</strong>
								<?php endif; ?>
								<?php echo esc_html( $message_parts['error'] ); ?>
							</pre>
							<?php if ( ! empty( $message_parts['stack_trace'] ) ) : ?>
								<pre class="stack-trace"><?php echo esc_html( $message_parts['stack_trace'] ); ?></pre>
							<?php endif; ?>
							<button class="copy-log button button-small" 
								data-clipboard-text="<?php echo esc_attr( $entry['message'] ); ?>"
								title="<?php esc_attr_e( 'Copy to clipboard', 'twk-utils' ); ?>">
								<span class="dashicons dashicons-clipboard"></span>
							</button>
						</div>
					<?php endforeach; ?>

					<?php if ( $total_pages > 1 ) : ?>
						<div class="debug-log-pagination">
							<?php
							// Previous page
							if ( $current_page > 1 ) :
								$prev_url = add_query_arg( 'log_page', $current_page - 1 );
								?>
								<a href="<?php echo esc_url( $prev_url ); ?>" class="button">&laquo; <?php esc_html_e( 'Previous', 'twk-utils' ); ?></a>
							<?php endif; ?>

							<span class="debug-log-pagination-info">
								<?php
								printf(
									/* translators: 1: Current page, 2: Total pages */
									esc_html__( 'Page %1$s of %2$s', 'twk-utils' ),
									$current_page,
									$total_pages
								);
								?>
							</span>

							<?php
							// Next page
							if ( $current_page < $total_pages ) :
								$next_url = add_query_arg( 'log_page', $current_page + 1 );
								?>
								<a href="<?php echo esc_url( $next_url ); ?>" class="button"><?php esc_html_e( 'Next', 'twk-utils' ); ?> &raquo;</a>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Split error message and stack trace.
	 *
	 * @param string $message The full error message.
	 * @return array Array with error and stack_trace parts.
	 */
	private function split_error_and_stack_trace( $message ) {
		$parts = array(
			'error' => '',
			'stack_trace' => '',
		);

		if ( strpos( $message, 'Stack trace:' ) !== false ) {
			$split = explode( 'Stack trace:', $message, 2 );
			$parts['error'] = $split[0];
			$parts['stack_trace'] = 'Stack trace:' . $split[1];
		} else {
			$parts['error'] = $message;
		}

		return $parts;
	}

	/**
	 * Register the stylesheets and JavaScript for the admin area.
	 */
	public function enqueue_admin_scripts() {
		// Get current screen
		$screen = get_current_screen();
		
		// Check if we're on the settings page for this plugin
		if ( $screen && $screen->id === 'settings_page_twk-utils' ) {
			wp_enqueue_style( 'dashicons' );
			wp_enqueue_style(
				$this->plugin_name,
				plugin_dir_url( __FILE__ ) . 'css/twk-utils-admin.css',
				array(),
				$this->version,
				'all'
			);

			wp_enqueue_script(
				'clipboard',
				'https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.8/clipboard.min.js',
				array(),
				'2.0.8',
				true
			);

			wp_enqueue_script(
				$this->plugin_name,
				plugin_dir_url( __FILE__ ) . 'js/twk-utils-admin.js',
				array( 'jquery', 'clipboard' ),
				$this->version,
				true
			);
		}
	}
}
