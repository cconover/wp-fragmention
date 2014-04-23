<?php
/**
 * Plugin Name: WP Fragmention
 * Plugin URI: https://christiaanconover.com/code/wp-fragmention?ref=plugin-data
 * Description: Add support for Fragmention links to WordPress.
 * Version: 0.1.1
 * Author: Christiaan Conover
 * Author URI: https://christiaanconover.com?ref=wp-fragmention-plugin-author-uri
 * License: GPLv2
 * @package cconover
 * @subpackage fragmention
 **/
 
 namespace cconover;

/**
 * Main plugin class
 */
class Fragmention {
	// Plugin constants
	const ID = 'cc-fragmention'; // Plugin ID
	const NAME = 'Fragmention '; // Plugin name
	const VERSION = '0.1.1'; // Plugin version
	const WPVER = '2.7'; // Minimum version of WordPress required for this plugin
	const PREFIX = 'cc_fragmention_'; // Plugin database/method prefix
	
	// Class properties
	private $options; // Plugin options and settings
	
	// Class constructor
	function __construct() {
		// Add the script
		add_action( 'wp_enqueue_scripts', array( &$this, 'loadscript' ) );
		
		// Admin
		if ( is_admin() ) {
			// Admin initialization
			$this->admin_initialize();
			
			// Activation and deactivation hooks
			register_activation_hook( __FILE__, array( &$this, 'activate' ) ); // Plugin activation
			register_deactivation_hook( __FILE__, array( &$this, 'deactivate' ) ); // Plugin deactivation
		}
	} // End __construct()
	
	/* ===== Add Script ===== */
	// Load the Fragmention script
	function loadscript() {
		// Use the WordPress hook to add the script inside <head>
		wp_enqueue_script(
			self::ID, // Handle for the script
			plugins_url( 'cc-fragmention.js', __FILE__ ), // Path to the script file
			array(), // Script dependencies
			self::VERSION // Script version
		);
		
		// Add stylesheet for Fragmention highlighting
		wp_enqueue_style(
			self::ID, // Handle for the stylesheet
			plugins_url( 'cc-fragmention.css', __FILE__ ), // Path to the stylesheet file
			array(), // Stylesheet dependencies
			self::VERSION // Stylesheet version
		);
	} // End loadscript()
	
	/* ===== Admin Initialization ===== */
	function admin_initialize() {
		// Get plugin options from database
		$this->options = get_option( self::PREFIX . 'options' );
		
		// Run upgrade process
		$this->upgrade();
	} // End admin_initialize()
	
	// Plugin upgrade
	function upgrade() {
		// Check whether the database-stored plugin version number is less than the current plugin version number, or whether there is no plugin version saved in the database
		if ( ! empty( $this->options['dbversion'] ) && version_compare( $this->options['dbversion'], self::VERSION, '<' ) ) {
			// Set local variable for options (always the first step in the upgrade process)
			$options = $this->options;
			
			/* Update the plugin version saved in the database (always the last step of the upgrade process) */
			// Set the value of the plugin version
			$options['dbversion'] = self::VERSION;
			
			// Save to the database
			update_option( self::PREFIX . 'options', $options );
			/* End update plugin version */
		}
	} // End upgrade()
	/*
	===== End Admin Initialization =====
	*/
	
	/*
	===== Plugin Activation and Deactivation =====
	*/
	// Plugin activation
	public function activate() {
		// Check to make sure the version of WordPress being used is compatible with the plugin
		if ( version_compare( get_bloginfo( 'version' ), self::WPVER, '<' ) ) {
	 		wp_die( 'Your version of WordPress is too old to use this plugin. Please upgrade to the latest version of WordPress.' );
	 	}
	 	
	 	// Default plugin options
	 	$options = array(
	 		'dbversion' => self::VERSION, // Current plugin version
	 	);
	 	
	 	// Add options to database
	 	add_option( self::PREFIX . 'options', $options );
	} // End activate()
	
	// Plugin deactivation
	public function deactivate() {
		// Remove the plugin options from the database
		delete_option( self::PREFIX . 'options' );
	} // End deactivate
	
	/* ===== End Plugin Activation and Deactivation ===== */
} // End main plugin class

// Create plugin object
$cc_fragmention = new \cconover\Fragmention;
?>