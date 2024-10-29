<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
/**
 * Beepxtra Payment Gateway
 *
 *
 * @class  Beepxtra
 * @package WooCommerce
 * @category Payment Gateways
 * @author Beepxtra
 */


class Beepxtra extends WC_Payment_Gateway {

	/**
	 * Version
	 *
	 * @var string
	 */
	public $version;

	

	/**
	 * Constructor
	 */
	public function __construct() {

	    $this->version = BEEPXTRA_VERSION;
	    $this->id = 'beepxtra'; 
	    $this->method_title = __( 'Beepxtra Gateway', 'beepxtra-payment-gateway' );	      
	  	// Translators: %1$s represents the opening HTML anchor tag, and %2$s represents the closing HTML anchor tag.
		$this->method_description = sprintf(
		    __( 'Beepxtra gateway works by sending the user to %1$sBeepxtra%2$s to enter their payment information.', 'beepxtra-payment-gateway' ),
		    '<a href="https://beepxtra.com">',
		    '</a>'
		);

	  	$this->icon = plugins_url( 'assets/images/icon.png', __FILE__ );
	    
	    // Supported functionality
	    $this->supports = array(
	        'products',
	    );

	    $this->init_form_fields();
	    $this->init_settings();


	    // Setup default merchant data.
	    
	    $this->url = 'https://pay.beepxtra.com/gateway';
	    $this->title = $this->get_option( 'title' );
	    $this->response_url = esc_url( home_url( '/wc-api/Beepxtra' ) );
	    $this->description = $this->get_option( 'description' );
	    $this->outlet_id = $this->get_option( 'outlet_id' );
	    $this->enabled = $this->get_option( 'enabled' ) === 'yes' ? 'yes' : 'no';

	    add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ), array( $this, 'callback_handler' ) );
	    add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	    add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
	    add_action( 'admin_notices', array( $this, 'admin_notices' ) );													
	}

	/**
     * Initialise Gateway Settings Form Fields
     *
     * @since 1.0.0
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'       => __( 'Enable/Disable', 'beepxtra-payment-gateway' ),
                'label'       => __( 'Enable Beepxtra Gateway', 'beepxtra-payment-gateway' ),
                'type'        => 'checkbox',
                'description' => __( 'This controls whether or not this gateway is enabled within WooCommerce.', 'beepxtra-payment-gateway' ),
                'default'     => 'no',
                'desc_tip'    => true,
            ),
            'title' => array(
                'title'       => __( 'Title', 'beepxtra-payment-gateway' ),
                'type'        => 'text',
                'description' => __( 'This controls the title which the user sees during checkout.', 'beepxtra-payment-gateway' ),
                'default'     => __( 'Beepxtra', 'beepxtra-payment-gateway' ),
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => __( 'Description', 'beepxtra-payment-gateway' ),
                'type'        => 'text',
                'description' => __( 'This controls the description which the user sees during checkout.', 'beepxtra-payment-gateway' ),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'outlet_id' => array(
                'title'       => __( 'Beepxtra Outlet ID', 'beepxtra-payment-gateway' ),
                'type'        => 'text',
                'description' => __( 'This controls the Beextra outlet Id which the user will pay on checkout.', 'beepxtra-payment-gateway' ),
                'default'     => '',
                'desc_tip'    => true,
            ),
        );
    }

	/**
	 * Get the required form field keys for setup.
	 *
	 * @return array
	 */
	public function get_required_settings_keys() {
		return array(
			'email',
			'outlet_id'
		);
	}

	/**
	 * Determine if the gateway still requires setup.
	 *
	 * @return bool
	 */
	public function needs_setup() {
		return ! $this->get_option( 'email' ) || ! $this->get_option( 'outlet_id' );
	}

	

	/**
	 * check_requirements()
	 *
	 * Check if this gateway is enabled and available in the base currency being traded with.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function check_requirements() {

		$errors = [
			
			// Check if user entered a pass phrase
			empty( $this->get_option( 'outlet_id' ) )  ? 'wc-gateway-beepxtra-error-missing-pass-phrase' : null
		];

		return array_filter( $errors );
	}

	/**
	 * Check if the gateway is available for use.
	 *
	 * @return bool
	 */
	public function is_available() {
		if ( 'yes' === $this->enabled ) {
			$errors = $this->check_requirements();
			// Prevent using this gateway on frontend if there are any configuration errors.
			return 0 === count( $errors );
		}

		return parent::is_available();
	}

	/**
	 * Admin Panel Options
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 *
	 * @since 1.0.0
	 */
	public function admin_options() {
		
			parent::admin_options();
		
	}

	/**
	 * Generate the Beepxtra button link.
	 *
	 * @since 1.0.0
	 */
	
	public function generate_beepxtra_form( $order_id ) {
	    $order = wc_get_order($order_id);

	    $callback = $this->response_url . '?order_id=' . $order->get_id();  
	    $post_url = 'price=' . $order->get_total() . '&cur=' . $order->get_currency() . '&outlet_id=' . $this->outlet_id . '&callback=' . $callback;
	   	$encode=base64_encode($post_url);

	    
	    $gatewayurl = $this->url . '?data=' . $encode;

	    echo '<form action="' . esc_url($gatewayurl) . '" method="post" id="beepxtra_payment_form">
	            <input 
	                type="submit" 
	                class="button btn btn-primary" 
	                id="submit_beepxtra_payment_form" 
	                value="' . esc_attr__( 'Pay via Beepxtra Gateway', 'beepxtra-payment-gateway' ) . '" 
	            /> 
	            <a 
	                class="button cancel" 
	                href="' . esc_url( $order->get_cancel_order_url() ) . '"
	            >' . esc_html__( 'Cancel order &amp; restore cart', 'beepxtra-payment-gateway' ) . '</a>
	        </form>';
	}


	/**
	 * Process the payment and return the result.
	 *
	 * @since 1.0.0
	 */
	public function process_payment($order_id) {

		

		$order = wc_get_order( $order_id );
		return array(
			'result' 	 => 'success',
			'redirect'	 => $order->get_checkout_payment_url( true ),
		);
	}

  

	/**
	 * Beepxtra gateway response.
	 *
	 * @since 1.0.0
	 */
    public function callback_handler() {
	    // Handle the callback from your payment gateway
	    // This is where you will update the order status based on the callback data
		error_log('Callback handler triggered.');

	    $order_id = isset($_GET['order_id']) ? absint($_GET['order_id']) : 0;
    	$status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';

	    $order = wc_get_order($order_id);

	    if ($order && $status === 'success') {
	        error_log('Callback processing successful.');
	        // Update order status to "Processing" or any other desired status
        	$order->update_status('processing');

	        // Mark the order as complete (payment successful)
	        $order->payment_complete();



	        // Add order note
	        $order->add_order_note('Payment successfully processed via custom gateway.');

	        // Empty the cart
	        WC()->cart->empty_cart();

      		$thankyou_page_url = $order->get_checkout_order_received_url();
        	wp_safe_redirect($thankyou_page_url);
	        exit;
		}else {
	        // Handle unsuccessful payment
	        error_log('Callback processing unsuccessful.');
	        wp_redirect(home_url());
    		exit;
	    }

	   
    }




	/**
	 * Reciept page.
	 *
	 * Display text and a button to direct the user to Beepxtra.
	 *
	 * @param WC_Order $order Order object.
	 * @since 1.0.0
	 */
	public function receipt_page( $order ) {
		echo '<p>' . esc_html__( 'Thank you for your order, please click the button below to pay with Beepxtra.', 'beepxtra-payment-gateway' ) . '</p>';
		$this->generate_beepxtra_form( $order );
	}


	/**
	 * Handle logging the order details.
	 *
	 * @since 1.4.5
	 */
	public function log_order_details( $order ) {
		$customer_id = $order->get_user_id();

		$details = "Order Details:"
		. PHP_EOL . 'customer id:' . $customer_id
		. PHP_EOL . 'order id:   ' . $order->get_id()
		. PHP_EOL . 'parent id:  ' . $order->get_parent_id()
		. PHP_EOL . 'status:     ' . $order->get_status()
		. PHP_EOL . 'total:      ' . $order->get_total()
		. PHP_EOL . 'currency:   ' . $order->get_currency()
		. PHP_EOL . 'key:        ' . $order->get_order_key()
		. "";

		$this->log( $details );
	}



	/**
	 * Log system processes.
	 * @since 1.0.0
	 */
	public function log( $message ) {
		if ( 'yes' === $this->get_option( 'testmode' ) || $this->enable_logging ) {
			if ( empty( $this->logger ) ) {
				$this->logger = new WC_Logger();
			}
			$this->logger->add( 'beepxtra', $message );
		}
	}

	


	/**
	 * Gets user-friendly error message strings from keys
	 *
	 * @param   string  $key  The key representing an error
	 *
	 * @return  string        The user-friendly error message for display
	 */
	public function get_error_message( $key ) {
	    switch ( $key ) {
	      case 'wc-gateway-beepxtra-error-missing-pass-phrase':
	           return esc_html__( 'Beepxtra requires a passphrase to work.', 'beepxtra-payment-gateway' );
	        default:
	            return '';
	    }
	}

	/**
	 * Show possible admin notices
	 */
	public function admin_notices() {
	    // Get requirement errors.
	    $errors_to_show = $this->check_requirements();

	    // If everything is in place or the gateway isn't enabled, don't display it.
	    if ( ! $errors_to_show || 'no' === $this->enabled ) {
	        return;
	    }

	    // Use transients to display the admin notice once after saving values.
	    if ( ! get_transient( 'beepxtra_admin_notice_transient' ) ) {
	        set_transient( 'beepxtra_admin_notice_transient', 1, 1 );

	        $error_messages = array_reduce(
	            $errors_to_show,
	            function( $errors_list, $error_item ) {
	                $errors_list .= '<li>' . esc_html( $this->get_error_message( $error_item ) ) . '</li>';
	                return $errors_list;
	            },
	            ''
	        );

	        echo '<div class="notice notice-error is-dismissible"><p>' .
	            esc_html__( 'To use Beepxtra as a payment provider, you need to fix the problems below:', 'beepxtra-payment-gateway' ) .
	            '</p><ul style="list-style-type: disc; list-style-position: inside; padding-left: 2em;">' .
	            wp_kses_post( $error_messages ) .
	            '</ul></p></div>';
	    }
	}






}

