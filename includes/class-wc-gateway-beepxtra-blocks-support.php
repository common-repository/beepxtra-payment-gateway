<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Beepxtra payment method integration
 *
 * @since 1.5.0
 */
final class Beepxtra_Blocks_Support extends AbstractPaymentMethodType {
    /**
     * Name of the payment method.
     *
     * @var string
     */
    protected $name = 'beepxtra';

    /**
     * Initializes the payment method type.
     */
    public function initialize() {
        $this->settings = get_option( 'woocommerce_beepxtra_settings', [] );
    }

    /**
     * Returns if this payment method should be active. If false, the scripts will not be enqueued.
     *
     * @return boolean
     */
    public function is_active() {
        $payment_gateways_class   = WC()->payment_gateways();
        $payment_gateways         = $payment_gateways_class->payment_gateways();

                // Check if the 'beepxtra' index exists in the $payment_gateways array
        if ( isset( $payment_gateways['beepxtra'] ) ) {
            // Access the 'beepxtra' index and check if it's available
            return $payment_gateways['beepxtra']->is_available();
        } else {
            // If 'beepxtra' index doesn't exist, return false or handle it accordingly
            return false; // or handle the case where 'beepxtra' is not available
        }

    }

    /**
     * Returns an array of scripts/handles to be registered for this payment method.
     *
     * @return array
     */
    public function get_payment_method_script_handles() {
        $asset_path   = BEEPXTRA_PATH . '/build/index.asset.php';
        $version      = BEEPXTRA_VERSION;
        $dependencies = [];
        if ( file_exists( $asset_path ) ) {
            $asset        = require $asset_path;
            $version      = is_array( $asset ) && isset( $asset['version'] )
                ? $asset['version']
                : $version;
            $dependencies = is_array( $asset ) && isset( $asset['dependencies'] )
                ? $asset['dependencies']
                : $dependencies;
        }
        wp_register_script(
            'wc-beepxtra-blocks-integration',
            BEEPXTRA_URL . '/build/index.js',
            $dependencies,
            $version,
            true
        );
        wp_set_script_translations(
            'wc-beepxtra-blocks-integration',
            'beepxtra-payment-gateway'
        );
        return [ 'wc-beepxtra-blocks-integration' ];
    }

    /**
     * Returns an array of key=>value pairs of data made available to the payment methods script.
     *
     * @return array
     */
    public function get_payment_method_data() {
        return [
            'title'       => $this->get_setting( 'title' ),
            'description' => $this->get_setting( 'description' ),
            'supports'    => $this->get_supported_features(),
            'logo_url'    => BEEPXTRA_URL . '/assets/images/icon.png',
        ];
    }

    /**
     * Returns an array of supported features.
     *
     * @return string[]
     */
    public function get_supported_features() {
        $payment_gateways = WC()->payment_gateways->payment_gateways();
        return $payment_gateways['beepxtra']->supports;
    }
}
