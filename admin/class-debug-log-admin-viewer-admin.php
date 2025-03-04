<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://github.com/aidamartinez/debug-log-admin-viewer
 * @since      1.0.0
 *
 * @package    Debug_Log_Admin_Viewer
 * @subpackage Debug_Log_Admin_Viewer/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for the admin area.
 *
 * @package    Debug_Log_Admin_Viewer
 * @subpackage Debug_Log_Admin_Viewer/admin
 * @author     TWK Media <software@thewebkitchen.co.uk>
 */
class Debug_Log_Admin_Viewer_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Path to wp-config.php file.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $wp_config_path    Path to wp-config.php file.
	 */
	private $wp_config_path;

	/**
	 * Maximum number of backups to keep
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      int
	 */
	private $max_backups = 5;

	/**
	 * Backup directory path
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string
	 */
	private $backup_dir;

	/**
	 * WordPress filesystem instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      WP_Filesystem    $filesystem    WordPress filesystem instance.
	 */
	private $filesystem;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string    $plugin_name    The name of this plugin.
	 * @param    string    $version        The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;

		// Initialize filesystem early
		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();
		global $wp_filesystem;
		$this->filesystem = $wp_filesystem;

		// Delay other initialization until WordPress is fully loaded
		add_action('plugins_loaded', array($this, 'init'));
	}

	/**
	 * Initialize the plugin after WordPress is fully loaded.
	 */
	public function init() {
		// Re-initialize filesystem if needed
		if (empty($this->filesystem)) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
			global $wp_filesystem;
			$this->filesystem = $wp_filesystem;
		}

		// Set up paths
		$this->wp_config_path = $this->debug_log_admin_viewer_find_wp_config_path();
		if (!$this->wp_config_path || !$this->filesystem->exists($this->wp_config_path)) {
			error_log('wp-config.php not found at: ' . $this->wp_config_path);
			return;
		}

		// Set up backup directory in uploads
		$upload_dir = wp_upload_dir();
		if (is_wp_error($upload_dir)) {
			error_log('Failed to get upload directory: ' . $upload_dir->get_error_message());
			return;
		}

		$this->backup_dir = trailingslashit($upload_dir['basedir']) . 'debug-log-admin-viewer';
		$this->max_backups = 5; // Keep only 5 most recent backups
		
		// Create backup directory if it doesn't exist
		if (!$this->filesystem->exists($this->backup_dir)) {
			//error_log('Backup directory does not exist, creating...');
			if (!wp_mkdir_p($this->backup_dir)) {
				error_log('Failed to create backup directory using wp_mkdir_p: ' . $this->backup_dir);
				return;
			}

			// Create an index.php file to prevent directory listing
			$index_file = trailingslashit($this->backup_dir) . 'index.php';
			if (!$this->filesystem->put_contents($index_file, '<?php // Silence is golden', FS_CHMOD_FILE)) {
				error_log('Failed to create index.php in backup directory');
			}

			// Create .htaccess to prevent direct access
			$htaccess_file = trailingslashit($this->backup_dir) . '.htaccess';
			if (!$this->filesystem->put_contents($htaccess_file, 'deny from all', FS_CHMOD_FILE)) {
				error_log('Failed to create .htaccess in backup directory');
			}
		}

		// Add hooks
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
		add_action('admin_menu', array($this, 'add_options_page'));
		add_action('admin_init', array($this, 'register_settings'));
	}

	/**
	 * Find the path to wp-config.php file.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   string    Path to wp-config.php file.
	 */
	private function debug_log_admin_viewer_find_wp_config_path() {
		// Re-initialize filesystem if needed
		if (empty($this->filesystem)) {
			require_once ABSPATH . 'wp-admin/includes/file.php';

			WP_Filesystem();
			global $wp_filesystem;
			$this->filesystem = $wp_filesystem;
		}

		$path = ABSPATH . 'wp-config.php';
		if (!$this->filesystem->exists($path)) {
			$path = dirname(ABSPATH) . '/wp-config.php';
		}

		return $path;
	}

	/**
	 * Manage backup files rotation.
	 *
	 * @return bool True on success, false on failure.
	 */
	private function debug_log_admin_viewer_manage_backups() {
		// Get list of backup files using WP_Filesystem
		$backup_files = $this->filesystem->dirlist($this->backup_dir);
		
		if (false === $backup_files) {
			error_log('Failed to list backup directory contents');
			return false;
		}

		$config_backups = array();
		foreach ($backup_files as $file) {
			if (preg_match('/^wp-config-backup-.*\.php$/', $file['name'])) {
				// Get file modification time using filesystem
				$filepath = trailingslashit($this->backup_dir) . $file['name'];
				$time = $this->filesystem->mtime($filepath);
				
				if ($time !== false) {
					$config_backups[] = array(
						'path' => $filepath,
						'time' => $time
					);
				}
			}
		}

		if (empty($config_backups)) {
			return true; // No backups to manage
		}

		// Sort by creation time (oldest first)
		usort($config_backups, function($a, $b) {
			return (int)$a['time'] - (int)$b['time'];
		});

		// Remove oldest backups if we have more than max_backups
		while (count($config_backups) >= $this->max_backups) {
			$oldest_backup = array_shift($config_backups);
			$this->filesystem->delete($oldest_backup['path']);
		}

		return true;
	}

	/**
	 * Create a backup of wp-config.php file.
	 *
	 * @return string|false Path to backup file or false on failure.
	 */
	private function debug_log_admin_viewer_create_backup() {
		// Manage existing backups first.
		if (!$this->debug_log_admin_viewer_manage_backups()) {
			error_log('Failed to manage existing backups');
			return false;
		}

		// Create new backup filename with timestamp.
		$backup_filename = 'wp-config-backup-' . gmdate('Y-m-d-H-i-s') . '.php';
		$backup_path = trailingslashit($this->backup_dir) . $backup_filename;

		// Ensure backup directory exists
		if (!$this->filesystem->exists($this->backup_dir)) {
			if (!wp_mkdir_p($this->backup_dir)) {
				error_log('Failed to create backup directory: ' . $this->backup_dir);
				return false;
			}
		}

		// Get source content
		$source_content = $this->filesystem->get_contents($this->wp_config_path);
		if (false === $source_content) {
			error_log('Could not read source file: ' . $this->wp_config_path);
			return false;
		}

		// Write backup file with proper permissions
		if (!$this->filesystem->put_contents($backup_path, $source_content, FS_CHMOD_FILE)) {
			error_log('Failed to write backup file: ' . $backup_path);
			return false;
		}

		// Verify backup content
		$backup_content = $this->filesystem->get_contents($backup_path);
		if ($backup_content !== $source_content) {
			$this->filesystem->delete($backup_path);
			error_log('Backup file verification failed');
			return false;
		}

		return $backup_path;
	}

	/**
	 * Add options page to WordPress admin menu.
	 */
	public function add_options_page() {
		add_options_page(
			'Debug Log Admin Viewer Settings',
			'Debug Log Admin Viewer',
			'manage_options',
			$this->plugin_name,
			array( $this, 'display_options_page' )
		);
	}

	/**
	 * Register plugin settings.
	 */
	public function register_settings() {
		register_setting(
			'debug_log_admin_viewer_settings',
			'debug_log_admin_viewer_settings',
			array(
				'sanitize_callback' => array( $this, 'debug_log_admin_viewer_validate_settings' ),
			)
		);
	}

	/**
	 * Validate and save settings.
	 *
	 * @param array $input The input array to validate.
	 * @return array
	 */
	public function debug_log_admin_viewer_validate_settings($input) {
		// Verify nonce
		if (!isset($_POST['debug_log_admin_viewer_nonce_field']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['debug_log_admin_viewer_nonce_field'])), 'debug_log_admin_viewer_nonce_action')) {
			add_settings_error(
				$this->plugin_name,
				'nonce_failed',
				'Security check failed. Please try again.',
				'error'
			);
			return array();
		}

		$validated = array();

		$validated['wp_debug'] = isset($input['wp_debug']) ? 1 : 0;
		$validated['wp_debug_log'] = isset($input['wp_debug_log']) ? 1 : 0;
		$validated['wp_debug_display'] = isset($input['wp_debug_display']) ? 1 : 0;

		// Update wp-config.php
		try {
			// Update each constant individually
			foreach ($validated as $constant => $value) {
				$constant = strtoupper($constant);
				if (!$this->debug_log_admin_viewer_update_single_wp_config_constant($constant, (bool)$value)) {
					throw new Exception("Failed to update $constant");
				}
			}

			add_settings_error(
				$this->plugin_name,
				'settings_updated',
				'Debug settings updated successfully.',
				'success'
			);
		} catch (Exception $e) {
			add_settings_error(
				$this->plugin_name,
				'config_update_failed',
				'Failed to update wp-config.php: ' . $e->getMessage(),
				'error'
			);
			return array();
		}

		return $validated;
	}

	/**
	 * Updates a single constant in the wp-config.php file.
	 *
	 * @param string $constant_name  The constant name.
	 * @param bool   $constant_value The constant value.
	 * @return bool True on success, false on failure.
	 */
	private function debug_log_admin_viewer_update_single_wp_config_constant( $constant_name, $constant_value ) {
		// Get the current content
		$config_content = $this->filesystem->get_contents($this->wp_config_path);
		if (false === $config_content) {
			error_log('Could not read wp-config.php');
			return false;
		}

		// Convert boolean to string representation
		$constant_value = $constant_value ? 'true' : 'false';

		// Check if constant already exists
		$pattern = "/define\s*\(\s*['\"]" . preg_quote($constant_name, '/') . "['\"]\s*,\s*(.*?)\s*\)/";
		$needs_update = false;

		if (preg_match($pattern, $config_content, $matches)) {
			// Check if value is different
			$current_value = trim($matches[1]);
			$needs_update = ($current_value !== $constant_value);
			
			if ($needs_update) {
				// Update existing constant
				$config_content = preg_replace(
					$pattern,
					"define('$constant_name', $constant_value)",
					$config_content
				);
			}
		} else {
			// Constant doesn't exist, need to add it
			$needs_update = true;

			// Find the stop editing comment
			$stop_editing_comment = "/* That's all, stop editing! Happy blogging. */";
			$pos = strpos($config_content, $stop_editing_comment);

			if ($pos !== false) {
				// Find our section
				$section_comment = "/* Added by Debug Log Admin Viewer */";
				$section_pos = strpos($config_content, $section_comment);

				if ($section_pos === false || $section_pos > $pos) {
					// Add new section with the constant
					$insert_content = "\n{$section_comment}\n"
						. "define('$constant_name', $constant_value);\n";
					$config_content = substr_replace($config_content, $insert_content, $pos, 0);
				} else {
					// Add to existing section
					$section_end = strpos($config_content, "/*", $section_pos + strlen($section_comment));
					if ($section_end === false || $section_end > $pos) {
						$section_end = $pos;
					}

					// Clean up any existing empty lines in our section
					$section = substr($config_content, $section_pos, $section_end - $section_pos);
					$section = preg_replace("/\n{2,}/", "\n", $section);
					$config_content = substr_replace($config_content, $section, $section_pos, $section_end - $section_pos);

					// Add constant to existing section
					$insert_content = "define('$constant_name', $constant_value);\n";
					$config_content = substr_replace($config_content, $insert_content, $section_end, 0);
				}

				// Ensure exactly one empty line before the stop editing comment
				$config_content = preg_replace("/\n+(" . preg_quote($stop_editing_comment, '/') . ")/", "\n\n$1", $config_content);
			} else {
				// If no stop editing comment found, add at the end
				$config_content .= "\n/* Added by Debug Log Admin Viewer */\n"
					. "define('$constant_name', $constant_value);\n";
			}
		}

		// Clean up multiple empty lines
		$config_content = preg_replace("/\n{3,}/", "\n\n", $config_content);

		if ($needs_update) {
			// Create backup before making changes
			if (!$this->debug_log_admin_viewer_create_backup()) {
				error_log('Failed to create backup before updating wp-config.php');
				return false;
			}

			// Write the updated content
			if (!$this->filesystem->put_contents($this->wp_config_path, $config_content, FS_CHMOD_FILE)) {
				error_log('Failed to write updated content to wp-config.php');
				return false;
			}
		}

		return true;
	}

	/**
	 * Get the debug log content.
	 *
	 * @return string The debug log content.
	 */
	public function debug_log_admin_viewer_get_debug_log_content() {
		$log_file = WP_CONTENT_DIR . '/debug.log';
		
		if (!file_exists($log_file)) {
			return '';
		}

		if (!$this->filesystem->is_writable($log_file)) {
			error_log('Debug log file is not writable');
			return '';
		}

		$content = file_get_contents($log_file);
		return $content ?: '';
	}

	/**
	 * Clear the debug log file.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function debug_log_admin_viewer_clear_debug_log() {
		$log_file = WP_CONTENT_DIR . '/debug.log';
		
		if (!file_exists($log_file)) {
			return true;
		}

		// Ensure WP_Filesystem is initialized
		if (empty($this->filesystem)) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
			global $wp_filesystem;
			$this->filesystem = $wp_filesystem;
		}

		if (!$this->filesystem->is_writable($log_file)) {
			error_log('Debug log file is not writable');
			return false;
		}

		return $this->filesystem->put_contents($log_file, '') !== false;
	}

	/**
	 * Parse debug log content and categorize errors.
	 *
	 * @param string $log_content Raw log content.
	 * @return array Categorized log entries.
	 */
	private function debug_log_admin_viewer_parse_debug_log( $log_content ) {
		$entries = array();
		$lines = explode( "\n", $log_content );
		$current_entry = null;
		
		foreach ( $lines as $line ) {
			if ( empty( trim( $line ) ) ) {
				continue;
			}

			// Check if line starts with a timestamp.
			$pattern = '/^\[(.+?)\]\s(.+)$/';
			if ( preg_match( $pattern, $line, $matches ) ) {
				// If we have a previous entry, save it.
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
					'type'      => $this->debug_log_admin_viewer_determine_error_type($message),
					'class'     => $this->debug_log_admin_viewer_determine_error_class($message),
				);
			} elseif ( $current_entry && (
				strpos( $line, 'Stack trace:' ) === 0 ||
				strpos( $line, '#' ) === 0 ||
				strpos( $line, 'thrown in' ) !== false
			) ) {
				// Append stack trace to current entry.
				$current_entry['message'] .= "\n" . $line;
				$current_entry['full_message'] .= "\n" . $line;
			} else {
				// If we have a previous entry, save it.
				if ( $current_entry ) {
					$entries[] = $current_entry;
				}
				
				// Create new entry for unknown format.
				$current_entry = array(
					'timestamp' => '',
					'message'   => $line,
					'full_message' => $line,
					'type'      => 'Unknown',
					'class'     => 'log-unknown',
				);
			}
		}
		
		// Add the last entry if exists.
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
	private function debug_log_admin_viewer_determine_error_type( $message ) {
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
	private function debug_log_admin_viewer_determine_error_class( $message ) {
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
	 * Display the debug log admin viewer.
	 *
	 * @param string $log_content Raw log content.
	 */
	private function display_debug_log_admin_viewer( $log_content ) {
		$entries = $this->debug_log_admin_viewer_parse_debug_log( $log_content );
		$error_types = array(
			'fatal'      => __( 'Fatal Errors', 'debug-log-admin-viewer' ),
			'parse'      => __( 'Parse Errors', 'debug-log-admin-viewer' ),
			'database'   => __( 'Database Errors', 'debug-log-admin-viewer' ),
			'warning'    => __( 'Warnings', 'debug-log-admin-viewer' ),
			'deprecated' => __( 'Deprecated', 'debug-log-admin-viewer' ),
			'strict'     => __( 'Strict Standards', 'debug-log-admin-viewer' ),
			'notice'     => __( 'Notices', 'debug-log-admin-viewer' ),
			'unknown'    => __( 'Other', 'debug-log-admin-viewer' ),
		);

		// Get active filters from URL
		$active_filters = isset( $_GET['filters'] ) ? explode(',', sanitize_text_field( wp_unslash( $_GET['filters'] ) ) ) : array_keys($error_types);

		$valid_filters = ['fatal', 'parse', 'database', 'warning', 'deprecated', 'strict', 'notice', 'unknown'];
		$active_filters = array_intersect($active_filters, $valid_filters);

		// Filter entries based on active filters
		$filtered_entries = array_filter($entries, function($entry) use ($active_filters) {
			foreach ($active_filters as $filter) {
				if ($entry['class'] === 'log-' . $filter) {
					return true;
				}
			}
			return false;
		});

		// Pagination settings
		$entries_per_page = 100;
		$current_page     = isset( $_GET['log_page'] ) ? max( 1, intval( $_GET['log_page'] ) ) : 1;
		$current_page     = esc_html( $current_page );
		$total_pages      = ceil( count( $filtered_entries ) / $entries_per_page );
		$offset           = ( $current_page - 1 ) * $entries_per_page;
		$paged_entries    = array_slice( $filtered_entries, $offset, $entries_per_page );

		?>
		<div class="debug-log-admin-viewer">
			<div class="debug-log-controls">
				<div class="debug-log-search">
					<input type="text" id="log-search" placeholder="<?php esc_attr_e( 'Search log entries...', 'debug-log-admin-viewer' ); ?>" />
				</div>
				<div class="debug-log-filters">
					<?php foreach ( $error_types as $type => $label ) : ?>
						<label>
							<input type="checkbox" 
								class="log-filter" 
								data-type="<?php echo esc_attr( $type ); ?>" 
								<?php checked( in_array( $type, $active_filters, true ) ); ?>
							/>
							<?php echo esc_html( $label ); ?>
						</label>
					<?php endforeach; ?>
				</div>
			</div>

			<?php if ( empty( $filtered_entries ) ) : ?>
				<p><?php esc_html_e( 'No log entries found matching the selected filters.', 'debug-log-admin-viewer' ); ?></p>
			<?php else : ?>
				<div class="debug-log-entries">
					<?php foreach ( $paged_entries as $entry ) : ?>
						<div class="log-entry <?php echo esc_attr( $entry['class'] ); ?>">
							<?php
							// Split message into main error and stack trace.
							$message_parts = $this->debug_log_admin_viewer_split_error_and_stack_trace( $entry['message'] );
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
								title="<?php esc_attr_e( 'Copy to clipboard', 'debug-log-admin-viewer' ); ?>">
								<span class="dashicons dashicons-clipboard"></span>
							</button>
						</div>
					<?php endforeach; ?>

					<?php if ( esc_html( $total_pages ) > 1 ) : ?>
						<div class="debug-log-pagination">
							<?php
							$base_url = remove_query_arg( array( 'log_page', 'filters' ) );
							
							// Previous page
							if ( $current_page > 1 ) :
								$prev_url = add_query_arg( array(
									'log_page' => $current_page - 1,
									'filters' => implode(',', $active_filters)
								), $base_url );
								?>
								<a href="<?php echo esc_url( $prev_url ); ?>" class="button">&laquo; <?php esc_html_e( 'Previous', 'debug-log-admin-viewer' ); ?></a>
							<?php endif; ?>

							<span class="debug-log-pagination-info">
								<?php
								printf(
									/* translators: 1: Current page, 2: Total pages */
									esc_html__( 'Page %1$s of %2$s', 'debug-log-admin-viewer' ),
									esc_html( $current_page ),
									esc_html( $total_pages )
								);
								?>
							</span>

							<?php
							// Next page
							if ( $current_page < esc_html( $total_pages ) ) :
								$next_url = add_query_arg( array(
									'log_page' => $current_page + 1,
									'filters' => implode(',', $active_filters)
								), $base_url );
								?>
								<a href="<?php echo esc_url( $next_url ); ?>" class="button"><?php esc_html_e( 'Next', 'debug-log-admin-viewer' ); ?> &raquo;</a>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Split error message and stack trace.
	 *
	 * @param string $message The full error message.
	 * @return array Array with error and stack_trace parts.
	 */
	private function debug_log_admin_viewer_split_error_and_stack_trace( $message ) {
		$parts = array(
			'error'       => '',
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
	 * Get WordPress configuration constants.
	 *
	 * @return array Array of configuration constants and their values.
	 */
	private function debug_log_admin_viewer_get_config_constants() {
		$defaults = array(
			'WP_DEBUG' => false,
			'WP_DEBUG_LOG' => false,
			'WP_DEBUG_DISPLAY' => false,
		);

		if (!$this->wp_config_path || !$this->filesystem->exists($this->wp_config_path)) {
			return $defaults;
		}

		$content = $this->filesystem->get_contents($this->wp_config_path);
		if (false === $content) {
			return $defaults;
		}

		foreach ($defaults as $constant => $default) {
			if (preg_match("/define\s*\(\s*['\"]" . preg_quote($constant, '"') . "['\"]\s*,\s*(true|false)\s*\)\s*;/i", $content, $matches)) {
				$defaults[$constant] = 'true' === strtolower($matches[1]);
			}
		}

		return $defaults;
	}

	/**
	 * Display the options page content.
	 */
	public function display_options_page() {		
		// Get actual values from wp-config.php.
		$config_constants = $this->debug_log_admin_viewer_get_config_constants();
		$config_writable = $this->filesystem->is_writable( $this->wp_config_path );
		
		// Handle log clearing.
		if ( isset( $_POST['debug_log_admin_viewer_clear_debug_log'] ) && check_admin_referer( 'debug_log_admin_viewer_clear_log_nonce' ) ) {
			$this->debug_log_admin_viewer_clear_debug_log();
		}

		// Get debug log content.
		$log_content = $this->debug_log_admin_viewer_get_debug_log_content();
		?>
		<div class="wrap">
			<h2><?php esc_html_e( 'Debug Log Admin Viewer Settings', 'debug-log-admin-viewer' ); ?></h2>

			<?php if ( ! $config_writable ) : ?>
				<div class="notice notice-error">
					<p><?php esc_html_e( 'Warning: wp-config.php is not writable. Please check file permissions or contact your server administrator.', 'debug-log-admin-viewer' ); ?></p>
				</div>
			<?php endif; ?>

			<form method="post" action="options.php">
				<?php
				settings_fields( 'debug_log_admin_viewer_settings' );
				wp_nonce_field('debug_log_admin_viewer_nonce_action', 'debug_log_admin_viewer_nonce_field');
				?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable WP_DEBUG', 'debug-log-admin-viewer' ); ?></th>
						<td>
							<input type="checkbox" name="debug_log_admin_viewer_settings[wp_debug]" 
								value="1" <?php checked( $config_constants['WP_DEBUG'], true ); ?> />
							<p class="description"><?php esc_html_e( 'Enables WordPress debug mode', 'debug-log-admin-viewer' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable WP_DEBUG_LOG', 'debug-log-admin-viewer' ); ?></th>
						<td>
							<input type="checkbox" name="debug_log_admin_viewer_settings[wp_debug_log]" 
								value="1" <?php checked( $config_constants['WP_DEBUG_LOG'], true ); ?> />
							<p class="description"><?php esc_html_e( 'Saves debug messages to wp-content/debug.log', 'debug-log-admin-viewer' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable WP_DEBUG_DISPLAY', 'debug-log-admin-viewer' ); ?></th>
						<td>
							<input type="checkbox" name="debug_log_admin_viewer_settings[wp_debug_display]" 
								value="1" <?php checked( $config_constants['WP_DEBUG_DISPLAY'], true ); ?> />
							<p class="description"><?php esc_html_e( 'Shows debug messages on the front end', 'debug-log-admin-viewer' ); ?></p>
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>

			<?php if ( $config_constants['WP_DEBUG_LOG'] && ! empty( $log_content ) ) : ?>
				<h3><?php esc_html_e( 'Debug Log', 'debug-log-admin-viewer' ); ?></h3>
				<form method="post">
					<?php wp_nonce_field( 'debug_log_admin_viewer_clear_log_nonce' ); ?>
					<input type="submit" name="debug_log_admin_viewer_clear_debug_log" class="button button-secondary" 
						value="<?php esc_attr_e( 'Clear Log File', 'debug-log-admin-viewer' ); ?>" />
				</form>
				<?php $this->display_debug_log_admin_viewer( $log_content ); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Register the stylesheets and JavaScript for the admin area.
	 */
	public function enqueue_admin_scripts() {
		$screen = get_current_screen();

		if ( $screen && $screen->id === 'settings_page_debug-log-admin-viewer' ) {
			wp_enqueue_style( 'dashicons' );
			wp_enqueue_style(
				$this->plugin_name,
				plugin_dir_url( __FILE__ ) . 'css/debug-log-admin-viewer-admin.css',
				array(),
				$this->version,
				'all'
			);

			// Use WordPress core's clipboard.js
			wp_enqueue_script('clipboard');

			wp_enqueue_script(
				$this->plugin_name,
				plugin_dir_url( __FILE__ ) . 'js/debug-log-admin-viewer-admin.js',
				array( 'jquery', 'clipboard' ),
				$this->version,
				true
			);
		}
	}
}
