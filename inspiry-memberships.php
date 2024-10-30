<?php
/**
 * Plugin Name:     RealHomes Memberships
 * Plugin URI:      https://github.com/InspiryThemes/inspiry-memberships
 * Description:     Provides functionality to create membership packages for real estate RealHomes theme by InspiryThemes
 * Version:         3.0.2
 * Tested up to:    6.6.0
 * Requires PHP:    7.4
 * Author:          InspiryThemes
 * Author URI:      https://themeforest.net/item/real-homes-wordpress-real-estate-theme/5373914
 * Contributors:    inspirythemes, saqibsarwar, fahidjavid
 * License:         GPL-2.0+
 * License URI:     http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:     inspiry-memberships
 * Domain Path:     /languages/
 *
 * GitHub Plugin URI: https://github.com/InspiryThemes/inspiry-memberships
 *
 * @since            1.0.0
 * @package          IMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Inspiry_Memberships' ) ) :

	/**
	 * Inspiry_Memberships.
	 *
	 * Plugin Core Class.
	 *
	 * @since 1.0.0
	 */
	class Inspiry_Memberships {

		/**
		 * Version.
		 *
		 * @since    1.0.0
		 * @var    string
		 */
		public $version;

		/**
		 * Inspiry Memberships Instance.
		 *
		 * @since    1.0.0
		 * @var    Inspiry_Memberships
		 */
		protected static $_instance;

		/**
		 * Method: Creates an instance of the class.
		 *
		 * @since 1.0.0
		 */
		public static function instance() {

			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;

		}

		/**
		 * Method: Contructor.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {

            // Notice to deactivate if RealHomes Stripe payment plugin is already active.
			if ( class_exists( 'Inspiry_Stripe_Payments' ) ) {
				add_action( 'admin_notices', array( $this, 'deactivate_stripe_plugin_notice' ) );

				return;
			}

            // Notice to deactivate if RealHomes PayPal Payments plugin is already active.
			if ( class_exists( 'Realhomes_Paypal_Payments' ) ) {
				add_action( 'admin_notices', array( $this, 'deactivate_paypal_plugin_notice' ) );

				return;
			}


			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			}

			// setting version
			$this->version = get_plugin_data( __FILE__ )['Version'];

			// Get started here.
			$this->define_constants();
			$this->include_files();
			$this->init_hooks();

			// Plugin is loaded.
			do_action( 'ims_loaded' );

		}

		/**
		 * Load plugin textdomain.
		 *
		 * @since 1.0.3
		 */
		public function load_textdomain() {
			load_plugin_textdomain( 'inspiry-memberships', false, basename( dirname( __FILE__ ) ) . '/languages' );
		}

		/**
		 * Method: Define constants.
		 *
		 * @since 1.0.0
		 */
		public function define_constants() {

			// Plugin version.
			if ( ! defined( 'IMS_VERSION' ) ) {
				define( 'IMS_VERSION', $this->version );
			}

			// Plugin Name.
			if ( ! defined( 'IMS_BASE_NAME' ) ) {
				define( 'IMS_BASE_NAME', plugin_basename( __FILE__ ) );
			}

			// Plugin Directory URL.
			if ( ! defined( 'IMS_BASE_URL' ) ) {
				define( 'IMS_BASE_URL', plugin_dir_url( __FILE__ ) );
			}

			// Plugin Directory Path.
			if ( ! defined( 'IMS_BASE_DIR' ) ) {
				define( 'IMS_BASE_DIR', plugin_dir_path( __FILE__ ) );
			}

			// Plugin Docs URL.
			if ( ! defined( 'IMS_DOCS_URL' ) ) {
				define( 'IMS_DOCS_URL', 'https://inspirythemes.com/realhomes-memberships-setup/' );
			}

			// Plugin Issue Reporting URL.
			if ( ! defined( 'IMS_ISSUE_URL' ) ) {
				define( 'IMS_ISSUE_URL', add_query_arg( array( 'page' => 'realhomes-feedback' ), get_admin_url() . 'admin.php' ) );
			}

		}

		/**
		 * Method: Include files.
		 *
		 * @since 1.0.0
		 */
		public function include_files() {

			/**
			 * IMS-init.php.
			 *
			 * @since 1.0.0
			 */
			if ( file_exists( IMS_BASE_DIR . '/resources/load-resources.php' ) ) {
				include_once IMS_BASE_DIR . '/resources/load-resources.php';
			}

		}

		/**
		 * Method: Initialization hooks.
		 *
		 * @since 1.0.0
		 */
		public function init_hooks() {
			add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
			add_filter( 'plugin_action_links_' . IMS_BASE_NAME, array( $this, 'plugin_action_links' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'load_public_scripts' ) ); // Load public area scripts.
			add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_scripts' ) ); // Load admin area scripts.
		}

		/**
		 * Add plugin action links
		 *
		 * @since 1.0.0
		 *
		 * @param array $links - links related to plugin.
		 *
		 * @return array
		 */
		public function plugin_action_links( $links ) {
			$settings_link      = sprintf( '<a href="%1$s">%2$s</a>', admin_url( 'admin.php?page=ims_settings' ), esc_html__( 'Settings', 'inspiry-memberships' ) );
			$documentation_link = sprintf( '<a href="%1$s" target="_blank">%2$s</a>', 'https://inspirythemes.com/realhomes-memberships-setup/', esc_html__( 'Setup Guide', 'inspiry-memberships' ) );

			array_unshift( $links, $settings_link, $documentation_link );

			return $links;
		}

		/**
		 * Load public area scripts.
		 *
		 * @since 2.0.0
		 */
		public function load_public_scripts() {

			if ( ! is_admin() && ! empty( $_GET['module'] ) && ! empty( $_GET['submodule'] ) && 'membership' === $_GET['module'] && 'checkout' === $_GET['submodule'] ) {

				// JS functions file.
				wp_register_script(
					'ims-public-js',
					IMS_BASE_URL . 'resources/js/ims-public.js',
					array( 'jquery' ),
					IMS_VERSION,
					true
				);
				wp_enqueue_script( 'ims-public-js' );


				$stripe_settings = get_option( 'ims_stripe_settings' );
				$paypal_settings = get_option( 'ims_paypal_settings' );
				$basic_settings  = get_option( 'ims_basic_settings' );

				// Check if PayPal is enabled then enqueue the PayPal SDK.
				if ( ! empty( $paypal_settings['ims_paypal_enable'] ) && 'on' === $paypal_settings['ims_paypal_enable'] ) {
					$recurring_args = '';
					if ( 'on' === $basic_settings['ims_recurring_memberships_enable'] ) {
						$recurring_args = '&vault=true';
					}

					wp_enqueue_script( 'paypal-sdk', 'https://www.paypal.com/sdk/js?client-id=' . $paypal_settings['ims_paypal_client_id'] . '&enable-funding=paypal&disable-funding=card' . $recurring_args, array( 'jquery' ), null, false );
				}

				// Check if Stripe is enabled then enqueue the Stripe SDK.
				if ( ! empty( $stripe_settings['ims_stripe_enable'] ) && 'on' === $stripe_settings['ims_stripe_enable'] ) {
					wp_enqueue_script(
						'stripe-library-v3',
						'https://js.stripe.com/v3/',
						array( 'jquery' ),
						$this->version,
						false
					);
				}

			}

		}

		/**
		 * Load admin area scripts.
		 */
		public function load_admin_scripts( $hook ) {

			if ( is_admin() && 'memberships_page_ims_settings' === $hook ) {

				// JS functions file.
				wp_register_script(
					'ims-admin-js',
					IMS_BASE_URL . 'resources/js/ims-admin.js',
					array( 'jquery' ),
					IMS_VERSION,
					true
				);
				wp_enqueue_script( 'ims-admin-js' );

			}

		}

		public function deactivate_stripe_plugin_notice() {
			?>
            <div class="notice notice-error is-dismissible">
                <p><?php esc_html_e( 'Please deactivate the RealHomes Stripe Payments plugin to RealHomes Memberships plugin.', 'inspiry-memberships' ); ?></p>
            </div>
			<?php
		}

		function deactivate_paypal_plugin_notice() {
			?>
            <div class="notice notice-error is-dismissible">
                <p><?php esc_html_e( 'Please deactivate the RealHomes PayPal Payments plugin to RealHomes Memberships plugin.', 'inspiry-memberships' ); ?></p>
            </div>
			<?php
		}
	}

endif;


/**
 * Returns the main instance of Inspiry_Memberships.
 *
 * @since 1.0.0
 */
function ims() {
	return Inspiry_Memberships::instance();
}

ims();
