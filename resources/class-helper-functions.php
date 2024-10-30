<?php
/**
 * Functions Class
 *
 * Class for general plugin functions.
 *
 * @since   2.1.0
 * @package IMS
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * IMS_Helper_Functions.
 *
 * Class for helper plugin functions.
 *
 * @since 1.0.0
 */
if ( ! class_exists( 'IMS_Helper_Functions' ) ) {

	class IMS_Helper_Functions {

		/**
		 * Single Instance of Class.
		 *
		 * @since  1.0.0
		 * @var    IMS_Helper_Functions
		 */
		protected static $_instance;

		/**
		 * $basic_settings.
		 *
		 * @since  1.0.0
		 * @var    array
		 */
		public $basic_settings;

		/**
		 * $stripe_settings.
		 *
		 * @since  1.0.0
		 * @var    array
		 */
		public $stripe_settings;

		/**
		 * Membership page URL.
		 * @var
		 */
		public static $membership_page_url;

		function __construct() {
			add_action( 'init', function () {
				$this::$membership_page_url = $this::get_dashboard_membership_page_url();
			}, 9 ); // Set redirect URL on initialisation as page ID loads after loading plugin.
		}

		/**
		 * Method: Provides a single instance of the class.
		 *
		 * @since 1.0.0
		 *
		 * @return IMS_Helper_Functions
		 */
		public static function instance() {

			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

		/**
		 * is_memberships.
		 *
		 * @since 1.0.0
		 */
		public static function is_memberships() {

			// Get settings.
			$plugin_settings = get_option( 'ims_basic_settings' );

			if ( ! empty( $plugin_settings ) && ( 'on' === $plugin_settings['ims_memberships_enable'] ) ) {
				return true;
			}

			return false;
		}

		/**
		 * get_formatted_price.
		 *
		 * @since 1.0.0
		 */
		public static function get_formatted_price( $price ) {

			// Get settings.
			$currency_settings = get_option( 'ims_basic_settings' );
			$currency_position = $currency_settings['ims_currency_position']; // Currency Symbol Position.
			$formatted_price   = '';

			if ( ! empty( $price ) ) {
				if ( 'after' === $currency_position ) {
					$formatted_price = esc_html( $price . $currency_settings['ims_currency_symbol'] );
				} else {
					$formatted_price = esc_html( $currency_settings['ims_currency_symbol'] . $price );
				}
			} else {
				//return esc_html__( 'Price not available', 'inspiry-memberships' );
				if ( 'after' === $currency_position ) {
					$formatted_price = esc_html( '0' . $currency_settings['ims_currency_symbol'] );
				} else {
					$formatted_price = esc_html( $currency_settings['ims_currency_symbol'] . '0' );
				}
			}

			return $formatted_price;
		}

		/**
		 * Get all memberships.
		 *
		 * @since 1.0.0
		 * @return array|bool Array of Memberships data.
		 */
		public static function ims_get_all_memberships() {
			/**
			 * The WordPress Query class.
			 * @link http://codex.wordpress.org/Function_Reference/WP_Query
			 */
			$membership_args = array(
				'post_type'      => 'ims_membership',
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'orderby'        => 'menu_order',
				'meta_key'       => 'ims_membership_duration',
				'meta_value'     => '0',
				'meta_type'      => 'numeric',
				'meta_compare'   => '>',
			);

			$memberships_query = new WP_Query( apply_filters( 'ims_membership_args', $membership_args ) );

			if ( $memberships_query->have_posts() ) {

				// Membership Data array.
				$memberships_data = array();

				while ( $memberships_query->have_posts() ) {
					$memberships_query->the_post();
					$membership_obj = ims_get_membership_object( get_the_ID() );

					// Memberships data.
					$memberships_data[] = array(
						'ID'            => get_the_ID(),
						'title'         => get_the_title(),
						'format_price'  => self::get_formatted_price( $membership_obj->get_price() ),
						'price'         => $membership_obj->get_price(),
						'properties'    => $membership_obj->get_properties(),
						'featured_prop' => $membership_obj->get_featured_properties(),
						'duration'      => $membership_obj->get_duration(),
						'duration_unit' => $membership_obj->get_duration_unit(),
						'is_popular'    => $membership_obj->get_popular(),
					);
				}

				return $memberships_data;
			}

			return false;
		}

		/**
		 * Get membership by user.
		 *
		 * @since 1.0.0
		 */
		public static function ims_get_membership_by_user( $user ) {

			// Get user id.
			if ( ! is_object( $user ) ) {
				return false;
			}

			$user_id = $user->ID;

			// Get current membership details.
			$membership_id          = get_user_meta( $user_id, 'ims_current_membership', true );
			$package_properties     = get_user_meta( $user_id, 'ims_package_properties', true );
			$current_properties     = get_user_meta( $user_id, 'ims_current_properties', true );
			$package_featured_props = get_user_meta( $user_id, 'ims_package_featured_props', true );
			$current_featured_props = get_user_meta( $user_id, 'ims_current_featured_props', true );
			$membership_due_date    = get_user_meta( $user_id, 'ims_membership_due_date', true );

			if ( ! empty( $membership_id ) ) {

				// Get membership object.
				$membership_id  = intval( $membership_id );
				$membership_obj = ims_get_membership_object( $membership_id );

				$membership_data = array(
					'ID'               => get_the_ID(),
					'title'            => get_the_title( $membership_id ),
					'format_price'     => self::get_formatted_price( $membership_obj->get_price() ),
					'price'            => $membership_obj->get_price(),
					'properties'       => $package_properties,
					'current_props'    => $current_properties,
					'featured_prop'    => $package_featured_props,
					'current_featured' => $current_featured_props,
					'duration'         => $membership_obj->get_duration(),
					'duration_unit'    => $membership_obj->get_duration_unit(),
					'due_date'         => $membership_due_date,
				);

				return $membership_data;
			}

			return false;
		}

		/**
		 * Method: Displays cancel membership form.
		 *
		 * @since 1.0.0
		 */
		public static function cancel_user_membership_form() {

			// If not user logged in
			if ( ! is_user_logged_in() ) {
				return;
			}

			// Get user id.
			$user_id = get_current_user_id();

			?>
            <div class="ims-cancel-membership-wrap">
                <form action="" method="POST" id="ims-cancel-user-membership">
                    <h4><?php esc_html_e( 'Are you sure?', 'inspiry-memberships' ); ?></h4>
                    <p><strong><?php esc_html_e( 'Caution: ', 'inspiry-memberships' ); ?></strong><?php esc_html_e( 'Membership cancellation is an irreversible action!', 'inspiry-memberships' ); ?></p>
                    <button class="btn btn-secondary" id="ims-btn-confirm" type="submit"><?php esc_html_e( 'Yes', 'inspiry-memberships' ); ?></button>
                    <button class="btn btn-secondary" id="ims-btn-close" type="button"><?php esc_html_e( 'No', 'inspiry-memberships' ); ?></button>
                    <input type="hidden" name="action" value="ims_cancel_user_membership" />
                    <input type="hidden" name="user_id" value="<?php echo esc_attr( $user_id ); ?>" />
                    <input type="hidden" name="ims_cancel_membership_nonce" value="<?php echo wp_create_nonce( 'ims-cancel-membership-nonce' ); ?>" />
                </form>
            </div>
			<?php
		}

		/**
		 * @param string $duration_unit
		 *
		 * @return mixed|string
		 */
		public static function get_readable_duration_unit( $duration_unit ) {

			$duration_units = array(
				'day'    => esc_html__( 'Day', 'inspiry-memberships' ),
				'days'   => esc_html__( 'Days', 'inspiry-memberships' ),
				'week'   => esc_html__( 'Week', 'inspiry-memberships' ),
				'weeks'  => esc_html__( 'Weeks', 'inspiry-memberships' ),
				'month'  => esc_html__( 'Month', 'inspiry-memberships' ),
				'months' => esc_html__( 'Months', 'inspiry-memberships' ),
				'year'   => esc_html__( 'Year', 'inspiry-memberships' ),
				'years'  => esc_html__( 'Years', 'inspiry-memberships' ),
			);

			if ( ! empty( $duration_unit ) && isset( $duration_units[ $duration_unit ] ) ) {
				return $duration_units[ $duration_unit ];
			}

			return $duration_unit;
		}

		/**
		 * Displays package checkout form.
		 *
		 * @since 1.1.3
		 *
		 * @param string $redirect_url Redirect URL after successful payment.
		 */
		public static function checkout_form( $redirect_url = '' ) {

			if ( empty( $redirect_url ) ) {
				$redirect_url = home_url();
			}

			// Get plugin settings.
			$basic_settings  = get_option( 'ims_basic_settings' );
			$stripe_settings = get_option( 'ims_stripe_settings' );
			$paypal_settings = get_option( 'ims_paypal_settings' );
			$wire_settings   = get_option( 'ims_wire_settings' );

			if ( isset( $_GET['package_id'] ) && ! empty( $_GET['package_id'] ) && intval( $_GET['package_id'] ) ) {
				$package_id = intval( $_GET['package_id'] );

				// Check for valid package id.
				if ( 'publish' !== get_post_status( $package_id ) ) {
					printf( '<div class="dashboard-no-items"><p><strong>%s</strong></p></div>', esc_html__( 'Invalid Package ID', 'inspiry-memberships' ) );

					return;
				}

				$price_adjustment = self::get_price_adjustment_wrt_current_package( $package_id );

				// Get current package.
				$package                 = ims_get_membership_object( $package_id );
				$package_price_digits    = $package->get_price();
				$package_price_formatted = $package->get_formatted_price();

				if ( $price_adjustment ) {
					if ( $package_price_digits <= $price_adjustment ) {
						$price_adjustment = $package_price_digits;
						$total_price      = 0;
					} else {
						$total_price = $package_price_digits - $price_adjustment;
						$total_price = self::get_formatted_price( $total_price );
					}
				} else {
					$total_price = $package_price_digits;

                    // Formatting price only if it is not zero
                    if ( $total_price ) {
	                    $total_price = self::get_formatted_price( $total_price );
                    }

                }
				?>
                <form id="ims-checkout-form" class="ims-checkout-form" method="post" action="<?php echo esc_url( $redirect_url ); ?>">
                    <div class="row">
                        <!-- Package Information -->
                        <div class="col-lg-4 order-lg-2">
                            <div class="box">
                                <div class="box-head">
                                    <h3 class="box-title"><?php esc_html_e( 'Your Order', 'inspiry-memberships' ); ?></h3>
                                </div>
                                <div class="box-body">
                                    <table class="package-order-table">
                                        <thead>
                                        <tr>
                                            <th><?php esc_html_e( 'Package', 'inspiry-memberships' ); ?></th>
                                            <th><?php esc_html_e( 'Price', 'inspiry-memberships' ); ?></th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr class="data-row">
                                            <td><strong><?php echo esc_html( get_the_title( $package_id ) ); ?></strong></td>
                                            <td><strong><?php echo esc_html( $package_price_formatted ); ?></strong></td>
                                        </tr>
										<?php
                                        if ( $price_adjustment ) {
                                            ?>
                                            <tr class="data-row">
                                                <td><strong><?php esc_html_e( 'Current Package Adjustment', 'inspiry-memberships' ); ?></strong></td>
                                                <td><strong>- <?php echo esc_html( self::get_formatted_price( $price_adjustment ) ); ?></strong></td>
                                            </tr>
                                            <?php
                                        }
										?>
                                        <tr class="total-price">
                                            <td><strong><?php esc_html_e( 'Total', 'inspiry-memberships' ); ?></strong></td>
                                            <td><strong><?php echo esc_html( $total_price ); ?></strong></td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div><!-- .box-body -->
                            </div><!-- .box -->
                        </div><!-- End Package Information -->

                        <div class="col-lg-8 order-lg-1">
                            <div class="box">
                                <div class="box-head">
                                    <h3 class="box-title"><?php esc_html_e( 'Payment Method', 'inspiry-memberships' ); ?></h3>
                                </div>
                                <div class="box-body">
									<?php
									// Store all payment methods.
									$payment_methods           = array();
									$stripe_btn_display        = 'hide';
									$paypal_btn_display        = 'hide';
									$bank_transfer_btn_display = 'hide';

									// Add Bank Transfer payment method
									if ( ! empty( $wire_settings['ims_wire_enable'] ) && 'on' === $wire_settings['ims_wire_enable'] ) {
										$payment_methods[] = array(
											'id'    => 'bank_transfer',
											'label' => esc_html__( 'Direct Bank Transfer', 'inspiry-memberships' ),
										);
										wp_nonce_field( 'membership-wire-nonce', 'membership_wire_nonce' );
									}

									// Add PayPal payment method
									if ( ! empty( $paypal_settings['ims_paypal_enable'] ) && 'on' === $paypal_settings['ims_paypal_enable'] ) {
										$payment_methods[] = array(
											'id'    => 'paypal',
											'label' => esc_html__( 'PayPal', 'inspiry-memberships' ),
										);
										wp_nonce_field( 'membership-paypal-nonce', 'membership_paypal_nonce' );
									}

									// Add Stripe payment method
									if ( ! empty( $stripe_settings['ims_stripe_enable'] ) && 'on' === $stripe_settings['ims_stripe_enable'] ) {
										$payment_methods[] = array(
											'id'    => 'stripe',
											'label' => esc_html__( 'Stripe', 'inspiry-memberships' ),
										);
									}
									?>
                                    <!-- Payment Methods -->
                                    <div id="payment-methods" class="payment-methods">
                                        <div class="row">
											<?php
											// Set the first payment method button display on page load.
											foreach ( $payment_methods as $payment_method => $payment_method_data ) :
												$payment_method_id = $payment_method_data['id'];

												$image_wrap_class = ''; // reset the image wrap class to empty
												$is_checked       = false; // reset the checked payment method

												// Set first payment method as current.
												if ( 0 === $payment_method ) {
													$is_checked       = true;
													$image_wrap_class = ' current';

													// Show stripe button.
													if ( 'stripe' === $payment_method_id ) {
														$stripe_btn_display = ''; // remove the hide class
													} else if ( 'paypal' === $payment_method_id ) {
														$paypal_btn_display = ''; // remove the hide class
													} else if ( 'bank_transfer' === $payment_method_id ) {
														$bank_transfer_btn_display = ''; // remove the hide class
													}
												}
												?>
                                                <div class="payment-method">
                                                    <label for="payment-method-<?php echo esc_attr( $payment_method_id ); ?>" class="image-wrap<?php echo esc_attr( $image_wrap_class ); ?>">
													<span class="image-wrap-inner">
														<?php include_once IMS_BASE_DIR . 'resources/img/' . esc_html( $payment_method_id ) . '.svg'; ?>
													</span>
                                                    </label>
                                                    <div class="radio-field radio-field-white">
                                                        <input id="payment-method-<?php echo esc_attr( $payment_method_id ); ?>" type="radio" name="payment_method" value="<?php echo esc_html( $payment_method_id ); ?>"<?php checked( true, $is_checked ); ?>>
                                                        <label for="payment-method-<?php echo esc_attr( $payment_method_id ); ?>"><?php echo esc_html( $payment_method_data['label'] ); ?></label>
                                                    </div>
                                                </div>
											<?php endforeach; ?>
                                        </div>
                                    </div><!-- End Payment Methods -->

									<?php
									// Recurring payment option.
									if ( ! empty( $package_price_digits ) && 'on' === $basic_settings['ims_recurring_memberships_enable'] ) {
										if ( empty( $price_adjustment ) ) {
										?>
                                        <div id="ims-recurring-wrap" class="ims-recurring-wrap checkbox-field checkbox-field-white hide">
                                            <input type="checkbox" name="ims_recurring" id="ims_recurring" />
                                            <label for="ims_recurring"><?php esc_html_e( 'Recurring Payments?', 'inspiry-memberships' ); ?></label>
                                        </div>
										<?php
										} else {
											?>
                                            <div id="ims-recurring-wrap" class="ims-recurring-wrap checkbox-field checkbox-field-white hide">
                                                <p class="note"><?php esc_html_e( 'The "Recurring Payment" option is not available when current package price adjustment is being processed.', 'inspiry-memberships' ); ?></p>
                                            </div>
                                            <?php
										}
									}
									?>

									<?php if ( isset( $basic_settings['ims_terms_and_conditions'] ) && ! empty( $basic_settings['ims_terms_and_conditions'] ) ) {
										?>
                                        <div id="ims-terms-and-conditions-wrap" class="ims-terms-and-conditions-wrap checkbox-field checkbox-field-white">
                                            <input type="checkbox" name="ims_terms_and_conditions" id="ims_terms_and_conditions" />
                                            <label for="ims_terms_and_conditions">
												<?php
												echo wp_kses(
													$basic_settings['ims_terms_and_conditions'],
													array(
														'a'      => array(
															'href'   => array(),
															'title'  => array(),
															'alt'    => array(),
															'target' => array(),
														),
														'br'     => array(),
														'em'     => array(),
														'strong' => array(),
													)
												);
												?>
                                            </label>
                                        </div>
									<?php } ?>

                                </div><!-- .box-body -->
								<?php if ( empty( $total_price ) ) { ?>
                                    <div class="box-overlay"></div>
								<?php } ?>
                            </div>

                            <div class="ims-btn-wrap">
								<?php if ( ! empty( $total_price ) ) { ?>
                                    <div id="ims-stripe-payment-btn" class="<?php echo esc_attr( $stripe_btn_display ); ?>"></div>
                                    <div id="ims-paypal-payment-btn" <?php echo esc_attr( $paypal_btn_display ); ?>></div>
                                    <button id="ims-bank-transfer-payment-btn" type="button" class="btn btn-primary <?php echo esc_attr( $bank_transfer_btn_display ); ?>"><?php esc_html_e( 'Complete Payment', 'inspiry-memberships' ); ?></button>
								<?php } else {
									?>
                                    <button type="button" class="btn btn-primary" id="ims-free-membership-btn"><?php esc_html_e( 'Subscribe', 'inspiry-memberships' ); ?></button>
									<?php
								} ?>

                                <span class="ims-form-loader">
									<svg xmlns="http://www.w3.org/2000/svg" width="32px" height="32px" viewBox="0 0 128 128"><rect x="0" y="0" width="100%" height="100%" fill="#FFFFFF"></rect><g><path d="M75.4 126.63a11.43 11.43 0 0 1-2.1-22.65 40.9 40.9 0 0 0 30.5-30.6 11.4 11.4 0 1 1 22.27 4.87h.02a63.77 63.77 0 0 1-47.8 48.05v-.02a11.38 11.38 0 0 1-2.93.37z" fill="#1ea69a" fill-opacity="1"></path><animateTransform attributeName="transform" type="rotate" from="0 64 64" to="360 64 64" dur="1000ms" repeatCount="indefinite"></animateTransform></g></svg>
								</span>
                                <div class="checkout-form-response-log"></div>
                            </div>
							<?php
							if ( 'show' === get_option( 'inspiry_checkout_badges_display', 'show' ) ) :
								$badges = array(
									'visa',
									'mastercard',
									'amex',
									'discover',
								);
								?>
                                <div class="ims-badges">
									<?php foreach ( $badges as $badge ) : ?>
                                        <div class="cards-wrapper <?php echo esc_attr( $badge ); ?>-card"><?php include_once IMS_BASE_DIR . 'resources/img/' . $badge . '.svg'; ?></div>
									<?php endforeach; ?>
                                </div>
							<?php endif; ?>
                        </div>
                    </div><!-- .row -->
                    <input type="hidden" name="package_id" value="<?php echo esc_attr( $package_id ); ?>" />
                    <input type="hidden" name="pp_plan_id" value="<?php echo esc_attr( get_post_meta( $package_id, 'ims_membership_paypal_plan_id', true ) ); ?>" />
                    <input type="hidden" name="order_id" value="" />
                    <input type="hidden" name="redirect" value="<?php echo esc_url( $redirect_url ); ?>" />
					<?php wp_nonce_field( 'membership-select-nonce', 'membership_select_nonce' ); ?>
                    <button type="submit" class="btn btn-primary hide" id="ims-submit-order"><?php esc_html_e( 'Complete Payment', 'inspiry-memberships' ); ?></button>
                </form>
				<?php
			} else {
				printf( '<div class="dashboard-no-items"><p><strong>%s</strong></p></div>', esc_html__( 'Invalid Package ID', 'inspiry-memberships' ) );
			}
		}


		/**
		 * Get current user price adjustment with respect to remaining days.
		 * If the optional new package ID is provided then it will check if the adjustment price is
		 * greater than the new package price so that it can be equal to the full new package price.
		 *
		 * @param string $new_package_id
		 *
		 * @return mixed|boolean
		 */
		public static function get_price_adjustment_wrt_current_package( $new_package_id = 0 ) {

			$ims_settings   = get_option( 'ims_basic_settings' );
			$adjustment_net = false;

			if ( 'on' === $ims_settings['ims_adjust_current_amount'] ) {
				$the_current_user    = wp_get_current_user();
				$current_membership  = self::ims_get_membership_by_user( $the_current_user );
				$the_current_user_id = $the_current_user->ID;
				$adjustment_offset   = intval( $ims_settings['ims_adjustment_amount_offset'] );

				if ( ! empty( $current_membership ) ) {
					$package_time   = get_user_meta( $the_current_user_id, 'ims_membership_due_date', true );
					$current_time   = date( get_option( 'date_format' ) );
					$remaining_days = floor( ( strtotime( $package_time ) - strtotime( $current_time ) ) / ( 60 * 60 * 24 ) );
					$package_price  = $current_membership['price'];
					$duration       = $current_membership['duration'];
					$duration_unit  = $current_membership['duration_unit'];

					$duration_days = $duration;
					if ( $duration_unit == 'months' ) {
						$duration_days = 30 * $duration;
					} else if ( $duration_unit == 'weeks' ) {
						$duration_days = 7 * $duration;
					} else if ( $duration_unit == 'years' ) {
						$duration_days = 365 * $duration;
					}

					if ( 0 < intval( $package_price ) && 0 < intval( $duration_days ) && 0 < intval( $remaining_days ) ) {
						$adjustment_gross = floor( ( $package_price / $duration_days ) * $remaining_days );
						$adjustment_net   = min( $adjustment_gross, $package_price );

						if ( intval( $new_package_id ) > 0 ) {
							$new_membership = ims_get_membership_object( $new_package_id );
							if ( ! empty( $new_membership ) ) {
								$new_package_price = $new_membership->get_price();
								if ( $new_package_price <= $adjustment_net ) {
									$adjustment_net = $new_package_price;
								}
							}
						}

						if ( 0 < $adjustment_offset ) {
							$adjustment_net -= $adjustment_offset;
						}
					}


				}

			}


			return $adjustment_net;
		}

		/**
		 * Get currently featured properties by the given user. It accepts the following argument values
		 * user_id (optional) | If not provided then current user will be taken
		 * remaining (optional) | Will return remaining featured properties if set to true
		 *
		 * @since 2.4.2
		 *
		 * @param array $args
		 *
		 * @return integer
		 */
		public static function get_user_featured_properties( $args = array() ) {

			if ( isset( $args['user_id'] ) && intval( $args['user_id'] ) ) {
				$user_id = $args['user_id'];
			} else {
				$user_id = get_current_user_id();
			}

			$remaining = $args['remaining'] ?? false;

			$properties = get_posts( array(
				'author'         => $user_id,
				'post_type'      => 'property',
				'meta_key'       => 'REAL_HOMES_featured',
				'meta_value'     => 1,
				'meta_compare'   => '=',
				'posts_per_page' => -1
			) );

			$featured_properties = count( $properties );

			if ( isset( $current_membership['featured_prop'] ) && $remaining === true ) {
				$current_membership       = self::ims_get_membership_by_user( get_userdata( $user_id ) );
				$current_allowed_featured = $current_membership['featured_prop'];
				$featured_properties      = intval( $current_allowed_featured ) - $featured_properties;
			}

			return $featured_properties;
		}

		/**
		 * Return front-end dashboard membership page URL if not empty otherwise home URL.
		 *
		 * @since 3.0.0
		 *
		 * @return string
		 */
		static function get_dashboard_membership_page_url() {

			$dashboard_page_id = get_option( 'inspiry_dashboard_page' );

			if ( ! empty( $dashboard_page_id ) ) {

				/* WPML filter to get translated page id if translation exists otherwise default id */
				$dashboard_page_id = apply_filters( 'wpml_object_id', $dashboard_page_id, 'page', true );
				$dashboard_url     = get_permalink( $dashboard_page_id );

				return add_query_arg( array( 'module' => 'membership' ), $dashboard_url );
			}

			return home_url( '/' );
		}
	}
}

/**
 * Returns the main instance of IMS_Helper_Functions.
 *
 * @since 2.1.0
 * @retun IMS_Helper_Functions
 */
function IMS_Helper_Functions() {
	return IMS_Helper_Functions::instance();
}

IMS_Helper_Functions();
