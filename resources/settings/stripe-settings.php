<?php
/**
 * Stripe Settings File
 *
 * File for adding stripe settings.
 *
 * @since   1.0.0
 * @package IMS
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $ims_settings;

$ims_stripe_settings_arr = apply_filters(
	'ims_stripe_settings',
	array(
		array(
			'id'   => 'ims_stripe_enable',
			'type' => 'checkbox',
			'name' => esc_html__( 'Enable Stripe', 'inspiry-memberships' ),
			'desc' => esc_html__( 'Check this to enable Stripe payments.', 'inspiry-memberships' ),
		),
		array(
			'id'   => 'ims_stripe_publishable',
			'type' => 'text',
			'name' => esc_html__( 'Publishable Key*', 'inspiry-memberships' ),
//			'desc' => esc_html__( 'Paste your stripe account publishable key.', 'inspiry-memberships' ),
			'desc' => sprintf( esc_html__( 'Paste your account publishable key here. For help consult %1sStripe Settings Guide%2s.', 'inspiry-stripe-payments' ), '<a href="https://inspirythemes.com/realhomes-memberships-setup/#stripe-settings" target="_blank">', '</a>' ),
		),
		array(
			'id'   => 'ims_stripe_secret',
			'type' => 'text',
			'name' => esc_html__( 'Secret Key*', 'inspiry-memberships' ),
			'desc' => esc_html__( 'Paste your stripe account secret key here.', 'inspiry-memberships' ),
		),
		array(
			'id'      => 'ims_stripe_btn_label',
			'type'    => 'text',
			'name'    => esc_html__( 'Stripe Button Label', 'inspiry-memberships' ),
			'desc'    => esc_html__( 'Default: Pay with Card', 'inspiry-memberships' ),
			'default' => 'Pay with Card',
		),
		array(
			'id'      => 'ims_stripe_webhook_url',
			'type'    => 'text',
			'name'    => esc_html__( 'Stripe WebHook URL', 'inspiry-memberships' ),
			'desc'    => sprintf( esc_html__( '%1sImportant:%2s Webhook URL plays an important role in accepting recurring payments through Stripe. It is important to set the webhook URL correctly otherwise recurring memberships through Stripe will not work. For help consult %3sStripe Settings Guide%4s.', 'inspiry-memberships' ), '<strong>', '</strong>', '<a href="https://inspirythemes.com/realhomes-memberships-setup/#stripe-settings" target="_blank">', '</a>' ),
			'default' => esc_url( add_query_arg( array( 'ims_stripe' => 'membership_event' ), home_url( '/' ) ) ),
		),
	)
);

if ( ! empty( $ims_stripe_settings_arr ) && is_array( $ims_stripe_settings_arr ) ) {
	foreach ( $ims_stripe_settings_arr as $ims_stripe_setting ) {
		$ims_settings->add_field( 'ims_stripe_settings', $ims_stripe_setting );
	}
}
