<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       stellarcowboy.com
 * @since      1.0.0
 *
 * @package    WooCommerce_Api_Email
 * @subpackage WooCommerce_Api_Email/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    WooCommerce_Api_Email
 * @subpackage WooCommerce_Api_Email/includes
 * @author     Kenneth White <kwhitedesign@gmail.com>
 */
class WooCommerce_Api_Email {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      WooCommerce_Api_Email_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * The product ID of the code product from WooCommerce
	 *
	 * @since  	1.0.0
	 * @access 	protected
	 * @var  	integer 		$woocom_product_id 	Option name of this plugin
	 */
	protected $woocom_product_id;

	/**
	 * The API key to include in the URL query
	 *
	 * @since  	1.0.0
	 * @access 	protected
	 * @var  	string 		$woocom_api_key 	The API key to include in the URL query
	 */
	protected $woocom_api_key;

	/**
	 * The API URL
	 *
	 * @since  	1.0.0
	 * @access 	protected
	 * @var  	string 		$woocom_api_url 	The API URL
	 */
	protected $woocom_api_url;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->plugin_name = 'woocommerce-api-email';
		$this->version = '1.0.0';
		$this->woocom_product_id = 1608; // Define product ID
		$this->woocom_api_key = ""; // Define key
		$this->woocom_api_url = ""; // Define API URL

		$this->load_dependencies();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - WooCommerce_Api_Email_Loader. Orchestrates the hooks of the plugin.
	 * - WooCommerce_Api_Email_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-woocommerce-api-email-loader.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-woocommerce-api-email-public.php';

		$this->loader = new WooCommerce_Api_Email_Loader();

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new WooCommerce_Api_Email_Public(
			$this->get_plugin_name(),
			$this->get_version(),
			$this->get_woocom_product_id(),
			$this->get_woocom_api_key(),
			$this->get_woocom_api_url()
		);

		$this->loader->add_action( 'woocommerce_order_status_processing', $plugin_public, 'spr_collect_api_response', 1, 1 );
		$this->loader->add_action( 'woocommerce_thankyou', $plugin_public, 'spr_custom_add_content', 10, 1 );
		$this->loader->add_action( 'woocommerce_thankyou', $plugin_public, 'spr_send_error_email', 11, 1 );
		$this->loader->add_action( 'woocommerce_email_before_order_table', $plugin_public, 'spr_custom_add_content', 20, 3 );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    WooCommerce_Api_Email_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Retrieve the product ID of the reseller codes from WooCommerce
	 *
	 * @since     1.0.0
	 * @return    integer    The product ID of the reseller codes
	 */
	public function get_woocom_product_id() {
		return $this->woocom_product_id;
	}

	/**
	 * Retrieve the API key 
	 *
	 * @since     1.0.0
	 * @return    string    The API key 
	 */
	public function get_woocom_api_key() {
		return $this->woocom_api_key;
	}

	/**
	 * Retrieve the API URL 
	 *
	 * @since     1.0.0
	 * @return    string    The API URL 
	 */
	public function get_woocom_api_url() {
		return $this->woocom_api_url;
	}
}
