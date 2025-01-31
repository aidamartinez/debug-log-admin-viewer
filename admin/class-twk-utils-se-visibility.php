<?php
/**
 * Search Engine Visibility notification functionality.
 *
 * @package    TWK_Utils
 * @subpackage TWK_Utils/admin
 */

class Twk_Utils_SE_Visibility {

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
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string    $plugin_name    The name of this plugin.
	 * @param    string    $version        The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version    = $version;
	}

	/**
	 * Register the settings for search engine visibility notification.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
	public function register_settings() {
		// Register setting in a separate group.
		register_setting(
			'twk_utils_misc_group',
			'twk_utils_se_visibility_notification',
			array(
				'type'    => 'boolean',
				'default' => false,
			)
		);

		// Add settings section.
		add_settings_section(
			'twk_utils_se_visibility_section',
			__( 'Search Engine Visibility Notification', 'twk-utils' ),
			array( $this, 'section_callback' ),
			$this->plugin_name . '_misc'
		);

		// Add settings field.
		add_settings_field(
			'twk_utils_se_visibility_notification',
			__( 'Enable Notification', 'twk-utils' ),
			array( $this, 'field_callback' ),
			$this->plugin_name . '_misc',
			'twk_utils_se_visibility_section'
		);
	}

	/**
	 * Render the settings section description.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
	public function section_callback() {
		echo '<p>' . esc_html__( 'Configure notifications for search engine visibility status.', 'twk-utils' ) . '</p>';
	}

	/**
	 * Render the settings field.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
	public function field_callback() {
		$option = get_option( 'twk_utils_se_visibility_notification', false );
		?>
		<input type="checkbox" 
			id="twk_utils_se_visibility_notification"
			name="twk_utils_se_visibility_notification" 
			value="1" 
			<?php checked( $option, 1 ); ?>
		/>
		<label for="twk_utils_se_visibility_notification">
			<p class="description">
				<?php esc_html_e( 'Show a notification in the admin bar when search engines are discouraged from indexing this site.', 'twk-utils' ); ?>
			</p>
		</label>
		<?php
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
	public function enqueue_styles() {
		if ( ! get_option( 'twk_utils_se_visibility_notification', false ) ) {
			return;
		}

		wp_enqueue_style(
			$this->plugin_name . '-admin',
			plugin_dir_url( __FILE__ ) . 'css/twk-utils-admin.css',
			array(),
			$this->version,
			'all'
		);
	}

	/**
	 * Add the admin bar notification if search engines are discouraged.
	 *
	 * @since    1.0.0
	 * @access   public
	 * @param    WP_Admin_Bar    $wp_admin_bar    WP_Admin_Bar instance.
	 */
	public function maybe_add_admin_bar_notice( $wp_admin_bar ) {
		// Only show in admin area.
		if ( ! is_admin() ) {
			return;
		}

		// Check if notifications are enabled.
		if ( ! get_option( 'twk_utils_se_visibility_notification', false ) ) {
			return;
		}

		// Check if search engines are discouraged.
		if ( ! get_option( 'blog_public', 1 ) ) {
			$wp_admin_bar->add_node(
				array(
					'id'     => 'twk-se-visibility-notice',
					'title'  => esc_html__( 'SE Visibility: OFF', 'twk-utils' ),
					'parent' => 'top-secondary',
					'meta'   => array(
						'class' => 'twk-se-visibility-notice',
					),
				)
			);
		}
	}
}
