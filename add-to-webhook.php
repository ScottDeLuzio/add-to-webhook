<?php
/*
Plugin Name: CWCFP Add Fields To Webhook
Plugin URI: https://conditionalcheckoutfields.com/
Description: Add conditional fields to WooCommerce Webhooks
Version: 1.0.0
Author: Scott DeLuzio
Author URI: https://scottdeluzio.com
*/
/* Copyright 2017 Scott DeLuzio */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function filter_woocommerce_webhook_payload__conditional_checkout_fields( $payload, $resource, $resource_id, $this_id ) {
	if ( $resource != 'order' || empty( $resource_id ) ) {
		return $payload;
	}

	$order = wc_get_order( $resource_id );

	$field_count 	= get_option( 'pro_conditional_fields_qty' );
	$conditional_a 	= 1;

	for ( $conditional_a; $conditional_a <= $field_count; $conditional_a++ ) {
		$sort 					= get_option( 'pro_conditional_fields_sort_' . $conditional_a );
		$output_fields[$sort] 	= array(
			'field_title' 		=> sanitize_text_field( get_option( 'pro_conditional_fields_title_' . $conditional_a ) ),
			'customer_input'	=> get_post_meta( $order->get_id(), get_option( 'pro_conditional_fields_title_' . $conditional_a ) ),
		);
	}
	if ( isset( $output_fields ) ){
		ksort( $output_fields );
		$max 	= max( array_map( 'count', $output_fields ) );
		$repeat	= 0;

		for( $repeat; $repeat <= $max + 1; $repeat++ ){
			foreach( $output_fields as $key => $value ){
				if ( isset( $value['customer_input'][$repeat] ) ){
					$payload['conditional_fields'][$value['field_title']] = $value['customer_input'][$repeat];
				}
			}
		}
	}

	return $payload;
};
/**
* filter add for above function
*/
add_filter( 'woocommerce_webhook_payload', 'filter_woocommerce_webhook_payload__conditional_checkout_fields', 10, 4 );