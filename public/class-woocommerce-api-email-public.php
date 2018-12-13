<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       stellarcowboy.com
 * @since      1.0.0
 *
 * @package    WooCommerce_Api_Email
 * @subpackage WooCommerce_Api_Email/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    WooCommerce_Api_Email
 * @subpackage WooCommerce_Api_Email/public
 * @author     Kenneth White <kwhitedesign@gmail.com>
 */
class WooCommerce_Api_Email_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * The product ID of the code product from WooCommerce
	 *
	 * @since  	1.0.0
	 * @access 	private
	 * @var  	integer 		$woocom_product_id 	Option name of this plugin
	 */
	private $woocom_product_id;

	/**
	 * The API key to include in the URL query
	 *
	 * @since  	1.0.0
	 * @access 	private
	 * @var  	string 		$woocom_api_key 	The API key to include in the URL query
	 */
	private $woocom_api_key;

	/**
	 * The API URL
	 *
	 * @since  	1.0.0
	 * @access 	private
	 * @var  	string 		$woocom_api_url 	The API URL
	 */
	private $woocom_api_url;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 * @param  		integer 		$woocom_product_id 	Option name of this plugin
	 * @param  		string 		$woocom_api_key 	The API key to include in the URL query
	 */

	public function __construct( $plugin_name, $version, $woocom_product_id, $woocom_api_key, $woocom_api_url ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->woocom_product_id = $woocom_product_id;
		$this->woocom_api_key = $woocom_api_key;
		$this->woocom_api_url = $woocom_api_url;
	}

	/**
	* Collect API results - triggered on woocommerce_payment_complete
	*
	* API results are saved to a transient for pickup by customer email
	*
	* @since    1.0.0
	* @param      integer    $order_id       The order id passed through the action
	*/
	public function spr_collect_api_response( $order_id ) {

		// setup order object
		$this->object = new WC_Order( $order_id );

		// collect customer email for use in return query
		$this->customer_email = urlencode( $this->object->billing_email );

		// collect order ID for use in error email
		$this->order_id = $order_id;

		// Get all the items from the order
		foreach( $this->object->get_items() as $item ) {
			// If product ID's are in this order, run API call

	        if ( $item['product_id'] == $this->woocom_product_id ) {
				// This function will optionally run the API call for each quantity of an item
		        // Get item quantity
	            $this->item_quantity .= $item['qty'];
			}
		}

		// curl to the API url to get the repsonses
        $curl = curl_init();
	    curl_setopt($curl, CURLOPT_URL, $this->woocom_api_url ."?apiKey=" . $this->woocom_api_key );
	    curl_setopt($curl, CURLOPT_POST, 1);
	    curl_setopt($curl, CURLOPT_POSTFIELDS, "idCount=" . $this->item_quantity);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_VERBOSE, true);

		// return curl result in a string
		$this->results_string = curl_exec ($curl);
		$this->error_string = curl_error($curl);
	    curl_close ($curl);

		// convert the results string into a php array
		$this->results_array = json_decode( $this->results_string, TRUE );

		// convert API response into array package to pass between actions
		$this->spr_data_store = array (
			'results_array' => $this->results_array,
			'results_string' => $this->results_string,
			'error_string' => $this->error_string,
		);

		// Set WordPress transient to persist data for 2 minutes
		set_transient( $this->order_id . '_woocommerce_api_email_string', $this->spr_data_store, 120 );

		// TRANSIENT IS LOGGING CORRECTLY INTO woocommerce_payment_complete ACTION
	}


	/**
	* Display function for woocommerce_email_before_order_table action
	*
	* @since    1.0.0
	* @param      integer    $order_id       The order id passed through the action
	*/
	public function spr_custom_add_content( $order_id ) {

		$this->order_id = $order_id;
		$this->correct_order_id = $this->order_id->id;
		if (empty( $this->correct_order_id )) {
			$this->correct_order_id = $order_id;
		}

		// Collect API data from transient
		$this->spr_data_store = get_transient( $this->correct_order_id . '_woocommerce_api_email_string' ); //$trans_test

		if ( ! empty( $this->spr_data_store['error_string'] ) ) {
			// Alert customer of error and send email to Admin
			$this->email_trigger = 1;
			echo $this->spr_customer_error_message();
		} elseif ( empty( $this->spr_data_store['results_string'] ) || ! is_array( $this->spr_data_store['results_array'] ) ) {
			// Alert customer of error and send email to Admin
			$this->email_trigger = 2;
			echo $this->spr_customer_error_message();
		} else {
			// Output results
			echo "<style>
				#pthanks a { text-overflow:ellipsis }
			</style>";
		    echo '<h2 id="h2thanks">Your API Response</h2>';
		    // Conditionally add customer email to URL query if email exists
		    if ( ! empty ($this->customer_email ) ) {
			    $this->customer_email_query = '&email=' . urlencode( $this->customer_email );
			} else {
				$this->object = new WC_Order( $this->correct_order_id );
				// collect customer email for use in return query
				$this->customer_email_query = '&email=' . urlencode( $this->object->billing_email );
			}
		    
		    // Print API Response
		    echo '<p id="pthanks">';
		    foreach ( $this->spr_data_store['results_array']['responses'] as $result ) {
			    echo $result . "<br>";
		    }
		    echo '</p>';
		}
		return;
	}

	/**
	* Display function for woocommerce_email_before_order_table action
	*
	* @since    1.0.0
	* @return      string    $customer_error_message       An error message presented to the customer
	*/
	public function spr_customer_error_message() {
		ob_start(); ?>
		<h2 id="h2thanks">We're Sorry</h2>
		<p id="pthanks">There has been an error in displaying your API Response. We have been alerted to the problem and will send your response to you in a separate email.</p>
		<?php
		$customer_error_message = ob_get_clean();
		$this->customer_error_message = $customer_error_message;
		return $this->customer_error_message;
	}

	/**
	* The array of email addresses to receive error notices
	*
	* @since    1.0.0
	* @return   array    $error_email_recipients       The array of email addresses to receive error notices
	*/
	public function spr_error_email_recipients() {
		$this->error_email_recipients = array(
		    'kwhitedesign@gmail.com'
		);
		return $this->error_email_recipients;
	}

	/**
	* The array of email addresses to receive error notices
	*
	* @since    1.0.0
	* @return   array    $error_email_recipients       The array of email addresses to receive error notices
	*/
	public function spr_send_error_email( $order_id ) {
		// Collect API data from transient
		$this->spr_data_store = get_transient( $this->order_id . '_woocommerce_api_email_string' );
		$email_trigger = $this->email_trigger;

		if ( $email_trigger == 1 ) {
			$subj = 'Store API error';
			$body = 'Order #: ' . $this->order_id . ' The API is returning the error "' . $this->spr_data_store['error_string'] . '"';
			wp_mail( $this->spr_error_email_recipients(), $subj, $body );

		} elseif ( $email_trigger == 2 ) {
			$subj = 'Store API error - Empty results';
			$body = 'Order #: ' . $this->order_id . ' The API is returning an empty result set.' . $this->spr_data_store['results_string'];

			wp_mail( $this->spr_error_email_recipients(), $subj, $body );
		} else {
			return;
		}
	}
}
