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

	date_default_timezone_set( get_option( 'timezone_string' ) );

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
	foreach( $order->get_items() as $item_id => $item ){
		$product_id	= $item->get_product_id();
		$term_list	= wp_get_post_terms( $product_id, 'product_cat', array( 'fields'=>'ids' ) );
		$cat_id		= (int)$term_list[0];
		$category	= '';
		foreach ( $term_list as $term ) {
			$cat		= get_term( $term, 'product_cat' );
			$category 	.= $cat->name . ',';
		}
		$category	= rtrim( $category, ',' );
		$type		= $item->get_type();

		$data		= array(
			'id'			=>	$item_id,
			'subtotal'		=>	wc_format_decimal( $order->get_line_subtotal( $item ), 2 ),
			'total'			=>	wc_format_decimal( $order->get_line_total( $item ), 2 ),
			'total_tax'		=>	wc_format_decimal( $order->get_line_tax( $item ), 2 ),
			'price'			=>	wc_format_decimal( $order->get_item_total( $item ), 2 ),
			'quantity'		=>	$item->get_quantity(),
			'tax_class'		=>	$item->get_tax_class(),
			'name'			=>	$item->get_name(),
			'category'		=>	$category,
			'product_id'	=>	$item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id(),
			'sku'			=>	is_object( $product ) ? $product->get_sku() : null,
			'player_name'	=>	wc_get_order_item_meta( $item_id, 'Player Name' ),
		);

		$payload[$type][]	= $data;
	}

	$payload['date_created_formatted'] = date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $order->get_date_created()->getTimestamp() );


	return $payload;
};
/**
* filter add for above function
*/
add_filter( 'woocommerce_webhook_payload', 'filter_woocommerce_webhook_payload__conditional_checkout_fields', 10, 4 );