<?php
/**
 * Plugin Name: Beepxtra Payment Gateway
 * Plugin URI: https://outlets.beepxtra.com/
 * Description: Receive payments using Beepxtra payments provider.
 * Author: Beepxtra
 * Author URI: https://www.beepxtra.com/
 * Version: 1.1.4
 * Text Domain: beepxtra-payment-gateway
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;

defined( 'ABSPATH' ) || exit;

define( 'BEEPXTRA_VERSION', '1.0.0' );
define( 'BEEPXTRA_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
define( 'BEEPXTRA_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );

/**
 * Initialize the gateway.
 * @since 1.0.0
 */
function beepxtra_init() {
   
    if ( class_exists( 'WC_Payment_Gateway' ) ) {
        require_once( plugin_basename( 'includes/class-wc-gateway-beepxtra.php' ) );
        require_once( plugin_basename( 'includes/class-wc-gateway-beepxtra-privacy.php' ) );
        load_plugin_textdomain( 'beepxtra-payment-gateway', false, trailingslashit( dirname( plugin_basename( __FILE__ ) ) ) );
        add_filter( 'woocommerce_payment_gateways', 'beepxtra_add_gateway' );
    } else {
        add_action( 'admin_notices', 'beepxtra_missing_wc_notice' );
    }
}
add_action( 'plugins_loaded', 'beepxtra_init', 0 );


function beepxtra_plugin_links( $links ) {
    $settings_url = add_query_arg(
        array(
            'page' => 'wc-settings',
            'tab' => 'checkout',
            'section' => 'beepxtra',
        ),
        admin_url( 'admin.php' )
    );

    $plugin_links = array(
        '<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Settings', 'beepxtra-payment-gateway' ) . '</a>',
        '<a href="https://www.beepxtra.com/en/contact-us">' . esc_html__( 'Support', 'beepxtra-payment-gateway' ) . '</a>',
        
    );

    return array_merge( $plugin_links, $links );
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'beepxtra_plugin_links' );


/**
 * Add the gateway to WooCommerce
 * @since 1.0.0
 */
function beepxtra_add_gateway( $methods ) {
    $methods[] = 'Beepxtra';
    return $methods;
}

add_action( 'woocommerce_blocks_loaded', 'beepxtra_woocommerce_blocks_support' );

function beepxtra_woocommerce_blocks_support() {
    if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
        require_once dirname( __FILE__ ) . '/includes/class-wc-gateway-beepxtra-blocks-support.php';
        add_action(
            'woocommerce_blocks_payment_method_type_registration',
            function( PaymentMethodRegistry $payment_method_registry ) {
                $payment_method_registry->register( new Beepxtra_Blocks_Support );
            }
        );
    }
}

/**
 * Declares support for HPOS.
 *
 * @return void
 */
function beepxtra_declare_hpos_compatibility() {
    if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
}
add_action( 'before_woocommerce_init', 'beepxtra_declare_hpos_compatibility' );

/**
 * Display notice if WooCommerce is not installed.
 *
 * @since 1.0.0
 */
function beepxtra_missing_wc_notice() {
    if ( class_exists( 'WooCommerce' ) ) {
        // Display nothing if WooCommerce is installed and activated.
        return;
    }

    echo '<div class="error"><p><strong>';
    echo sprintf(
        /* translators: %s WooCommerce download URL link. */
        esc_html__( 'WooCommerce Beepxtra Gateway requires WooCommerce to be installed and active. You can download %s here.', 'beepxtra-payment-gateway' ),
        '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>'
    );
    echo '</strong></p></div>';
}
add_action( 'admin_notices', 'beepxtra_missing_wc_notice' );

/**
 * Function to check for plugin updates
 *
 * @since 1.0.0
 */

function beepxtra_check_plugin_updates() {
    // Define your plugin information
    $plugin_slug = 'beepxtra-payment-gateway';
    $current_version = '1.1.0';
    $api_url = 'https://api.beepxtra.com/platform/paymentgatewayupdatecheck';

    // Make a request to your update-checking API
    $response = wp_remote_get($api_url);

    // Check if the request was successful
    if (!is_wp_error($response) && $response['response']['code'] == 200) {
        $update_data = json_decode($response['body']);

        // Compare the version received from the API with the current version
        if (version_compare($update_data->version, $current_version, '>')) {
            // Newer version available, notify the admin
            add_action('admin_notices', function() use ($update_data) {
               echo '<div class="notice notice-info"><p>A new version (' . esc_html( $update_data->version ) . ') of Beepxtra Payment Gateway is available! <a href="' . esc_url( $update_data->download_url ) . '">Download Now</a></p></div>';

            });
        }
    }
}

// Hook into plugin activation to check for updates
register_activation_hook(__FILE__, 'beepxtra_check_plugin_updates');

// Hook into admin init to periodically check for updates
add_action('admin_init', 'beepxtra_check_plugin_updates');

