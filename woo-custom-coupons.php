<?php
/*
 * Plugin Name:       Woo Custom Coupons
 * Description:       One of its key features is the ability to apply "buy two, get one free" coupon, coupon code is 'buy2get1free'.
 * Version:           1.0
 * Requires at least: 5.2
 * Requires PHP:      7.4
 * Author:            Dhaval K
 * Author URI:        https://github.com/dhavalkasavala
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       woo-custom-coupons
 * Domain Path:       /languages
 */

 if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

function wcc_plugin_init() {
    // If Woocommerce Plugin is NOT active
    if ( current_user_can( 'activate_plugins' ) && !class_exists( 'woocommerce' ) ) {

        add_action( 'admin_init', 'wcc_plugin_deactivate' );
        add_action( 'admin_notices', 'wcc_plugin_admin_notice' );

        // Deactivate the Woo Custom Coupons Plugin
        function wcc_plugin_deactivate() {
          deactivate_plugins( plugin_basename( __FILE__ ) );
        }

        // Throw an Alert to tell the Admin why it didn't activate
        function wcc_plugin_admin_notice() {
            $dpa_child_plugin = __( 'Woo Custom Coupons', 'woo-custom-coupons' );
            $dpa_parent_plugin = __( 'Woocommerce', 'woo-custom-coupons' );

            echo '<div class="error"><p>'
                . sprintf( __( '%1$s requires %2$s to function correctly. Please activate %2$s before activating %1$s. For now, the plugin has been deactivated.', 'woo-custom-coupons' ), '<strong>' . esc_html( $dpa_child_plugin ) . '</strong>', '<strong>' . esc_html( $dpa_parent_plugin ) . '</strong>' )
                . '</p></div>';

           if ( isset( $_GET['activate'] ) )
                unset( $_GET['activate'] );
        }

    } 
}
add_action( 'plugins_loaded', 'wcc_plugin_init' );


function wcc_woo_coupon_create() {
    if( !wc_get_coupon_id_by_code( 'buy2get1free' ) ) {
        $coupon = new WC_Coupon();

        $coupon->set_code( 'buy2get1free' ); // Coupon code

        $coupon->save();
    }
}
add_action('wp', 'wcc_woo_coupon_create');


add_action('woocommerce_cart_calculate_fees', 'wcc_buy_one_get_one_free', 10, 1 );
function wcc_buy_one_get_one_free( $wc_cart ){
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;

    $cart_item_count = $wc_cart->get_cart_contents_count();
    if ( $cart_item_count < 2 ) return;

    // Set HERE your coupon codes
    $coupons_codes = array('buy2get1free');
    $discount = 0; // initializing

    $matches = array_intersect( $coupons_codes, $wc_cart->get_applied_coupons() );
    if( count($matches) == 0 ) return;

    // Iterating through cart items
    foreach ( $wc_cart->get_cart() as $key => $cart_item ) {
        $qty = intval( $cart_item['quantity'] );
        // Iterating through item quantities
        for( $i = 0; $i < $qty; $i++ ) {
			if( $qty % 2 == 0) {
            	$items_prices[] = $qty * floatval( wc_get_price_excluding_tax( $cart_item['data'] ) ) / 2;
			}else {
				$items_prices[] = ($qty - 1) * floatval( wc_get_price_excluding_tax( $cart_item['data'] ) ) / 2 ;
			}
		}
    }
	
	$items_prices = array_unique($items_prices);
	
    // summing prices for this free items
    foreach($items_prices as $key => $item_price ) {
        $discount -= $item_price;
	}
    if( $discount != 0 ){
        // The discount
        $label = '"'.reset($matches).'" '.__("discount");
        $wc_cart->add_fee( $label, number_format( $discount, 2 ), true, 'standard' );
        # Note: Last argument in add_fee() method is related to applying the tax or not to the discount (true or false)
    }
}