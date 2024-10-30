<?php
/**
 * PayPal Payments Handling Class
 *
 * Class for handling PayPal payments.
 *
 * @since   1.0.0
 * @update  3.0.0
 * @package IMS
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * IMS_PayPal_Payment_Handler.
 *
 * Class for handling PayPal payments.
 *
 * @since   1.0.0
 * @updated 3.0.0
 */

if ( ! class_exists( 'IMS_PayPal_Payment_Handler' ) ) :
	/**
	 * This class handle PayPal payments.
	 */
	class IMS_PayPal_Payment_Handler {

		/**
		 * PayPal Client ID.
		 *
		 * @var string
		 */
		private $client_id;

		/**
		 * PayPal Client Secret ID.
		 *
		 * @var string
		 */
		private $client_secret;

		/**
		 * Currency code to charge payments in.
		 *
		 * @var string
		 */
		private $currency_code;

		/**
		 * PayPal checkout API request URL.
		 *
		 * @var string
		 */
		private $checkout_url;

		/**
		 * PayPal access token generate API request URL.
		 *
		 * @var string
		 */
		private $token_url;


		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {

			// Get PayPal settings.
			$paypal_settings = get_option( 'ims_paypal_settings' );

			$this->client_id     = empty( $paypal_settings['ims_paypal_client_id'] ) ? '' : esc_html( $paypal_settings['ims_paypal_client_id'] );
			$this->client_secret = empty( $paypal_settings['ims_paypal_client_secret'] ) ? '' : esc_html( $paypal_settings['ims_paypal_client_secret'] );
			$this->currency_code = empty( $paypal_settings['ims_currency_code'] ) ? 'USD' : esc_html( $paypal_settings['ims_currency_code'] );


			// Setting PayPal API URLs
			$base               = isset( $paypal_settings['ims_paypal_test_mode'] ) ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';
			$this->checkout_url = $base . '/v2/checkout/orders/';
			$this->token_url    = $base . '/v1/oauth2/token/';


			/**
			 * Action to run event on
			 * Doesn't need to be an existing WordPress action
			 *
			 * @param string - ims_paypal_membership_schedule_end
			 * @param string - paypal_membership_schedule_end
			 */
			add_action( 'ims_paypal_membership_schedule_end', array( $this, 'paypal_membership_schedule_end' ), 10, 3 );
		}

		/**
		 * Generate a unique UUID for the PayPal payment request reference.
		 *
		 * @since 3.0.0
		 * @return string
		 */
		private function generate_uuid() {
			return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x', // 32 bits for "time_low"
				mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

				// 16 bits for "time_mid"
				mt_rand( 0, 0xffff ),

				// 16 bits for "time_hi_and_version",
				// four most significant bits holds version number 4
				mt_rand( 0, 0x0fff ) | 0x4000,

				// 16 bits, 8 bits for "clk_seq_hi_res",
				// 8 bits for "clk_seq_low",
				// two most significant bits holds zero and one for variant DCE1.1
				mt_rand( 0, 0x3fff ) | 0x8000,

				// 48 bits for "node"
				mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ) );
		}

		/**
		 * Method: Start processing simple PayPal payment.
		 *
		 * @since 3.0.0
		 */
		public function create_paypal_order() {

			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'membership-paypal-nonce' ) ) {
				echo wp_json_encode( array(
					'success' => false,
					'message' => esc_html__( 'Nonce verification failed.', 'inspiry-memberships' ),
				) );
				die();
			}

			if ( ! isset( $_POST['membership_id'] ) ) {
				echo wp_json_encode( array(
					'success' => false,
					'message' => esc_html__( 'Please select a membership to continue.', 'inspiry-memberships' ),
				) );
				die();
			}

			// Get membership id.
			$membership_id = intval( $_POST['membership_id'] );

			// Get current user.
			$user    = wp_get_current_user();
			$user_id = $user->ID;


			if ( ! empty( $membership_id ) && ! empty( $user_id ) ) {

				// Get membership object.
				$membership       = ims_get_membership_object( $membership_id );
				$price_adjustment = IMS_Helper_Functions::get_price_adjustment_wrt_current_package( $membership_id );
				$price            = $membership->get_price();

				if ( $price_adjustment < $price ) {
					$price -= $price_adjustment;
				}


				// Replace with your access token, amount, currency, description, and metadata
				$accessToken       = $this->get_paypal_access_token();
				$memberships_title = esc_html( get_the_title( $membership_id ) );

				// PayPal API endpoint for creating an order
				$order_url = $this->checkout_url;

				// Set up the request data
				$order_data = array(
					'intent'         => 'CAPTURE',
					'purchase_units' => array(
						array(
							'amount'       => array(
								'currency_code' => $this->currency_code,
								'value'         => $price,
							),
							'description'  => $memberships_title, // Add a description here
							'custom_id'    => $membership_id, // Add custom metadata as needed
							"reference_id" => $this->generate_uuid(),
						),
					),

				);

				// Set up the request arguments
				$request_args = array(
					'headers' => array(
						'Authorization' => 'Bearer ' . $accessToken,
						'Content-Type'  => 'application/json',
					),
					'body'    => json_encode( $order_data ),
				);

				// Make the API request using the WordPress HTTP API
				$response = wp_safe_remote_post( $order_url, $request_args );

				// Check for errors in the API request
				if ( is_wp_error( $response ) ) {
					die( esc_html__( 'Error: ', 'inspiry-memberships' ) . $response->get_error_message() );
				}

				// Decode the JSON response
				$json_response = wp_remote_retrieve_body( $response );
				$data          = json_decode( $json_response, true );

				// Check if the order creation was successful
				if ( isset( $data['id'] ) ) {
					die( $data['id'] ); // Return the order ID
				} else {
					// Handle the case where order creation failed
					die( esc_html__( 'Error: Unable to create PayPal order for the property.', 'inspiry-memberships' ) );
				}
			}
			die();
		}

		/**
		 * Generate PayPal access token to perform API actions.
		 *
		 * @since 3.0.0
		 * @return mixed|void
		 */
		private function get_paypal_access_token() {

			// PayPal API endpoint for obtaining access token
			$token_url = $this->token_url;

			// Set up the request data
			$post_data = array(
				'grant_type' => 'client_credentials',
			);

			// Set up the request arguments
			$request_args = array(
				'body'    => http_build_query( $post_data ),
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( $this->client_id . ':' . $this->client_secret ),
					'Content-Type'  => 'application/x-www-form-urlencoded',
				),
			);

			// Make the API request using the WordPress HTTP API
			$response = wp_safe_remote_post( $token_url, $request_args );

			// Check for errors in the API request
			if ( is_wp_error( $response ) ) {
				die( esc_html__( 'Error: ', 'inspiry-memberships' ) . $response->get_error_message() );
			}

			// Decode the JSON response
			$json_response = wp_remote_retrieve_body( $response );
			$data          = json_decode( $json_response, true );

			// Check if the access token is present in the response
			if ( isset( $data['access_token'] ) ) {
				return $data['access_token'];
			} else {
				// Handle the case where access token retrieval failed
				die( esc_html__( 'Error: Unable to retrieve access token', 'inspiry-memberships' ) );
			}
		}

		/**
		 * Method: Get PayPal PayerID and execute PayPal Payment.
		 *
		 * @since 3.0.0
		 */
		public function complete_paypal_order_payment() {

			$orderID     = $_POST['order_id'];
			$accessToken = $this->get_paypal_access_token();

			// PayPal API endpoint for capturing a payment
			$capture_url = $this->checkout_url . $orderID . '/capture';

			// Set up the request arguments
			$request_args = array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $accessToken,
					'Content-Type'  => 'application/json',
				),
			);

			// Make the API request using the WordPress HTTP API
			$response = wp_safe_remote_post( $capture_url, $request_args );

			// Check for errors in the API request
			if ( is_wp_error( $response ) ) {
				die( esc_html__( 'Error: ', 'realhomes-paypal-payments' ) . $response->get_error_message() );
			}

			// Decode the JSON response
			$json_response = wp_remote_retrieve_body( $response );
			$payment_data  = json_decode( $json_response, true );

			// Check if the payment capture was successful
			if ( isset( $payment_data['status'] ) && $payment_data['status'] === 'COMPLETED' ) {

				$current_user = wp_get_current_user();

				// Get two major nodes for property payments details from payment data
				$payer_detail   = $payment_data['payer'];
				$payment_detail = $payment_data['purchase_units'][0]['payments']['captures'][0];

				// Prepare the property payment details from payment data
				$membership_id = $payment_detail['custom_id'];
				$payment_id    = $payment_detail['id'];

				$membership_methods = new IMS_Membership_Method();
				$receipt_methods    = new IMS_Receipt_Method();

				// Add membership.
				$membership_methods->add_user_membership( $current_user->ID, $membership_id, 'paypal' );
				// Generate receipt.
				$receipt_id = $receipt_methods->generate_receipt( $current_user->ID, $membership_id, 'paypal', $payment_id );

				// Mail the users.
				if ( ! empty( $receipt_id ) ) {
					IMS_Email::mail_user( $current_user->ID, $membership_id, 'paypal' );
					IMS_Email::mail_admin( $membership_id, $receipt_id, 'paypal' );
				}

				// Schedule the end of membership.
				$this->paypal_user_membership_end_schedule( $current_user->ID, $membership_id );

				die( json_encode( array( 'redirect_url' => IMS_Helper_Functions::$membership_page_url ) ) ); // On successful payment capture return redirect URL.
			} else {
				// Handle the case where payment capture failed
				die( esc_html__( 'Error: Unable to capture payment for the property', 'realhomes-paypal-payments' ) );
			}

		}

		/**
		 * Method: Once PayPal subscription is done execute the RealHomes membership subscription.
		 *
		 * @since 3.0.0
		 */
		public function add_recurring_membership() {

			if ( ! empty( $_POST['subscription_id'] ) && ! empty( $_POST['package_id'] ) ) {

				// Get current user.
				$current_user    = wp_get_current_user();
				$package_id      = $_POST['package_id'];
				$subscription_id = $_POST['subscription_id'];

				// Store the profile id in user meta.
				update_user_meta( $current_user->ID, 'ims_paypal_profile_id', $subscription_id );

				$membership_methods = new IMS_Membership_Method();
				$receipt_methods    = new IMS_Receipt_Method();

				$membership_methods->add_user_membership( $current_user->ID, $package_id, 'paypal' );
				$receipt_id = $receipt_methods->generate_recurring_paypal_receipt( $current_user->ID, $package_id, '' );

				if ( ! empty( $receipt_id ) ) {
					IMS_Email::mail_user( $current_user->ID, $package_id, 'paypal' );
					IMS_Email::mail_admin( $package_id, $receipt_id, 'paypal' );
				}

				die( json_encode( array( 'redirect_url' => IMS_Helper_Functions::$membership_page_url ) ) );
			}
		}

		/**
		 * Method: Schedule PayPal membership end.
		 *
		 * @since 1.0.0
		 *
		 * @param int $membership_id - ID of the membership purchased.
		 * @param int $user_id       - User ID who purchased membership.
		 */
		public function paypal_user_membership_end_schedule( $user_id = 0, $membership_id = 0 ) {

			// Bail if user or membership id is empty.
			if ( empty( $user_id ) || empty( $membership_id ) ) {
				return;
			}

			$membership_obj = ims_get_membership_object( $membership_id );
			$time_duration  = $membership_obj->get_duration();
			$time_unit      = $membership_obj->get_duration_unit();

			if ( 'days' === $time_unit ) {
				$seconds = 24 * 60 * 60;
			} else if ( 'weeks' === $time_unit ) {
				$seconds = 7 * 24 * 60 * 60;
			} else if ( 'months' === $time_unit ) {
				$seconds = 30 * 24 * 60 * 60;
			} else if ( 'years' === $time_unit ) {
				$seconds = 365 * 24 * 60 * 60;
			}

			$time_duration = $time_duration * $seconds;
			$schedule_args = array( $user_id, $membership_id );

			/**
			 * Schedule the event
			 *
			 * @param int - unix timestamp of when to run the event
			 * @param string - ims_paypal_membership_schedule_end
			 */
			wp_schedule_single_event( time() + $time_duration, 'ims_paypal_membership_schedule_end', $schedule_args );

			// Membership schedulled action hook.
			do_action( 'ims_paypal_membership_schedulled', $user_id, $membership_id );
		}

		/**
		 * Method: Function to be called when ims_paypal_membership_schedule_end
		 * event is fired.
		 *
		 * @since 1.0.0
		 *
		 * @param int $membership_id - ID of the membership purchased.
		 * @param int $user_id       - User ID who purchased membership.
		 */
		public function paypal_membership_schedule_end( $user_id, $membership_id ) {

			// Bail if user or membership id is empty.
			if ( empty( $user_id ) || empty( $membership_id ) ) {
				return;
			}

			$ims_membership_methods = new IMS_Membership_Method();
			$ims_membership_methods->cancel_user_membership( $user_id, $membership_id );
		}

		/**
		 * Method: Cancel PayPal user membership.
		 *
		 * @since 1.0.0
		 */
		public function cancel_paypal_membership( $user_id ) {

			// Bail if parameters are empty.
			if ( empty( $user_id ) ) {
				return false;
			}

//			$profile_id = get_user_meta( $user_id, 'ims_paypal_profile_id', true ); // TODO: this need to be handled separately.

//			if ( empty( $profile_id ) ) {

			// Get current membership and cancel it.
			$current_membership = get_user_meta( $user_id, 'ims_current_membership', true );
			$membership_methods = new IMS_Membership_Method();
			$membership_methods->cancel_user_membership( $user_id, $current_membership );

			wp_safe_redirect( IMS_Helper_Functions::$membership_page_url );
			exit;

//			}
		}

		/**
		 * Method: Handle PayPal IPN event.
		 *
		 * @since 1.0.0
		 */
		public function handle_paypal_ipn_event() {

			// Get PayPal settings.
			$paypal_settings = get_option( 'ims_paypal_settings' );

			if ( isset( $paypal_settings['ims_paypal_ipn_url'] ) && ! empty( $paypal_settings['ims_paypal_ipn_url'] ) ) {

				// Extract URL parameters.
				$ipn_url        = $paypal_settings['ims_paypal_ipn_url'];
				$ipn_url_params = parse_url( $ipn_url, PHP_URL_QUERY );
				$ipn_url_params = explode( '=', $ipn_url_params );

			} else {
				return false;
			}

			if ( isset( $_GET[ $ipn_url_params[0] ] ) && ( $ipn_url_params[1] === $_GET[ $ipn_url_params[0] ] ) ) {

				/**
				 *  STEP 1: Read POST data. Reading POSTed data directly
				 *  from $_POST causes serialization issues with array
				 *  data in the POST. Instead, read raw POST data from
				 *  the input stream.
				 */
				$raw_post_data  = file_get_contents( 'php://input' );
				$raw_post_array = explode( '&', $raw_post_data );
				$myPost         = array();

				// Bail if post data array is empty.
				if ( empty( $raw_post_array ) ) {
					return false;
				}

				foreach ( $raw_post_array as $keyval ) {

					$keyval = explode( '=', $keyval );
					if ( 2 == count( $keyval ) ) {
						$myPost[ $keyval[0] ] = urldecode( $keyval[1] );
					}

				}

				// Bail if myPost data array is empty.
				if ( empty( $myPost ) ) {
					return false;
				}

				// Read the IPN message sent from PayPal and prepend 'cmd=_notify-validate'
				$req = 'cmd=_notify-validate';
				if ( function_exists( 'get_magic_quotes_gpc' ) ) {
					$get_magic_quotes_exists = true;
				}
				foreach ( $myPost as $key => $value ) {
					if ( $get_magic_quotes_exists == true && 1 == get_magic_quotes_gpc() ) {
						$value = urlencode( stripslashes( $value ) );
					} else {
						$value = urlencode( $value );
					}
					$req .= "&$key=$value";
				}

				// Step 2: POST IPN data back to PayPal to validate.
				$sandbox_mode = $paypal_settings['ims_paypal_test_mode'];
				if ( ! empty( $sandbox_mode ) && ( 'on' === $sandbox_mode ) ) {
					$paypal_ipn_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
				} else {
					$paypal_ipn_url = 'https://www.paypal.com/cgi-bin/webscr';
				}

				$ch = curl_init( $paypal_ipn_url );
				curl_setopt( $ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1 );
				curl_setopt( $ch, CURLOPT_POST, 1 );
				curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
				curl_setopt( $ch, CURLOPT_POSTFIELDS, $req );
				curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 1 );
				curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 2 );
				curl_setopt( $ch, CURLOPT_FORBID_REUSE, 1 );
				curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Connection: Close' ) );

				$result = curl_exec( $ch );
				if ( empty( $result ) ) {
					curl_close( $ch );

					return false;
				} else {
					curl_close( $ch );
				}

				// Inspect IPN validation result and act accordingly.
				if ( 0 === strcmp( $result, "VERIFIED" ) ) {

					// The IPN is verified, process it.
					$payment_status   = ( isset( $_POST['payment_status'] ) && ! empty( $_POST['payment_status'] ) ) ? sanitize_text_field( $_POST['payment_status'] ) : false;
					$txn_id           = ( isset( $_POST['txn_id'] ) && ! empty( $_POST['txn_id'] ) ) ? sanitize_text_field( $_POST['txn_id'] ) : false;
					$txn_type         = ( isset( $_POST['txn_type'] ) && ! empty( $_POST['txn_type'] ) ) ? sanitize_text_field( $_POST['txn_type'] ) : false;
					$recurring_id     = ( isset( $_POST['recurring_payment_id'] ) && ! empty( $_POST['recurring_payment_id'] ) ) ? sanitize_text_field( $_POST['recurring_payment_id'] ) : false;

					$membership_methods = new IMS_Membership_Method();
					$receipt_methods    = new IMS_Receipt_Method();
					$user_id            = $membership_methods->get_user_by_paypal_profile( $recurring_id );

					if ( empty( $user_id ) ) {
						return false;
					}

					if ( 'recurring_payment' === $txn_type && 'Completed' == $payment_status ) {

						// Extend membership.
						$current_membership = get_user_meta( $user_id, 'ims_current_membership', true );
						$membership_methods->update_membership_due_date( $current_membership, $user_id );
						$membership_methods->update_user_recurring_membership( $user_id, $current_membership );
						$receipt_id = $receipt_methods->generate_receipt( $user_id, $current_membership, 'paypal', $txn_id, true );

						if ( ! empty( $receipt_id ) ) {
							IMS_Email::mail_user( $user_id, $current_membership, 'paypal', true );
							IMS_Email::mail_admin( $current_membership, $receipt_id, 'paypal', true );
						}

					} else if ( 'recurring_payment_profile_created' === $txn_type ) {

						// Membership created.

					} else if ( 'recurring_payment_failed' === $txn_type || 'recurring_payment_profile_cancel' === $txn_type ) {

						// Cancel user membership.
						$current_membership = get_user_meta( $user_id, 'ims_current_membership', true );
						$membership_methods->cancel_user_membership( $user_id, $current_membership );

					}

					return true;

				} else if ( 0 === strcmp( $result, "INVALID" ) ) {
					// IPN invalid, log for manual investigation.
					return false;
				}

			}

		}

	}

endif;
