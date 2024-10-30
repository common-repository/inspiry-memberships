<?php
/**
 * PayPal Settings File
 *
 * File for adding paypal settings.
 *
 * @since   1.0.0
 * @package IMS
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $ims_settings;

$ims_paypal_settings_arr = apply_filters(
	'ims_paypal_settings',
	array(
		array(
			'id'   => 'ims_paypal_enable',
			'type' => 'checkbox',
			'name' => esc_html__( 'Enable PayPal', 'inspiry-memberships' ),
			'desc' => esc_html__( 'Check this to enable PayPal payments.', 'inspiry-memberships' ),
		),
		array(
			'id'   => 'ims_paypal_test_mode',
			'type' => 'checkbox',
			'name' => esc_html__( 'Sandbox Mode', 'inspiry-memberships' ),
			'desc' => sprintf( esc_html__( 'The PayPal sandbox is a self-contained, virtual testing environment that simulates the live PayPal production environment. For more info consult %1sPayPal sandbox testing guide%2s.', 'inspiry-memberships' ), '<a href="https://developer.paypal.com/tools/sandbox/" target="_blank">', '</a>'),
		),
		array(
			'id'   => 'ims_paypal_client_id',
			'type' => 'text',
			'name' => esc_html__( 'Client ID', 'inspiry-memberships' ),
			'desc' => sprintf( esc_html__( 'Paste your account Client ID here. For help consult %sPayPal Settings Guide%2s.', 'inspiry-stripe-payments' ), '<a href="https://inspirythemes.com/realhomes-memberships-setup/#paypal-settings" target="_blank">', '</a>' ),
		),
		array(
			'id'   => 'ims_paypal_client_secret',
			'type' => 'text',
			'name' => esc_html__( 'Client Secret', 'inspiry-memberships' ),
			'desc' => esc_html__( 'Paste your account Client Secret here.', 'inspiry-memberships' ),
		),
		array(
			'id'      => 'ims_paypal_ipn_url',
			'type'    => 'text',
			'name'    => esc_html__( 'PayPal IPN URL', 'inspiry-memberships' ),
			'desc'    => sprintf( esc_html__( '%1sImportant:%2s Webhook URL plays an important role in accepting recurring payments through PayPal. It is important to set the webhook URL correctly otherwise recurring memberships through PayPal will not work. For help consult %3sPayPal Settings Guide%4s.', 'inspiry-memberships' ), '<strong>', '</strong>', '<a href="https://inspirythemes.com/realhomes-memberships-setup/#paypal-settings" target="_blank">', '</a>' ),
			'default' => esc_url( add_query_arg( array( 'ims_paypal' => 'notification' ), home_url( '/' ) ) ),
		),
	)
);

if ( ! empty( $ims_paypal_settings_arr ) && is_array( $ims_paypal_settings_arr ) ) {
	foreach ( $ims_paypal_settings_arr as $ims_paypal_setting ) {
		$ims_settings->add_field( 'ims_paypal_settings', $ims_paypal_setting );
	}
}
