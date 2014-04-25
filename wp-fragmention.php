<?php
/**
 * Plugin Name: WP Fragmention
 * Plugin URI: https://christiaanconover.com/code/wp-fragmention?ref=plugin-data
 * Description: Add support for Fragmention links to WordPress.
 * Version: 0.1.3
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
	const VERSION = '0.1.3'; // Plugin version
	const WPVER = '2.7'; // Minimum version of WordPress required for this plugin
	const PREFIX = 'cc_fragmention_'; // Plugin database/method prefix
	
	// Class properties
	private $options; // Plugin options and settings
	
	// Class constructor
	function __construct() {
		// Get plugin options from database
		$this->options = get_option( self::PREFIX . 'options' );
		
		// Add the script
		add_action( 'wp_enqueue_scripts', array( &$this, 'loadscript' ) );
		
		// Admin
		if ( is_admin() ) {
			// Admin initialization
			$this->admin_initialize();
			
			/* Hooks and filters */
			add_action( 'admin_menu', array( &$this, 'options_menu' ) ); // Add menu entry to Settings menu
			add_action( 'admin_init', array( &$this, 'options_init' ) ); // Initialize plugin options
			
			// Activation and deactivation hooks
			register_activation_hook( __FILE__, array( &$this, 'activate' ) ); // Plugin activation
			register_deactivation_hook( __FILE__, array( &$this, 'deactivate' ) ); // Plugin deactivation
			/* End hooks and filters */
		}
	} // End __construct()
	
	/* ===== Add Script ===== */
	// Load the Fragmention script
	function loadscript() {
		// Use the WordPress hook to add the script inside <head>
		wp_enqueue_script(
			self::ID, // Handle for the script
			plugins_url( 'assets/js/fragmention.js', __FILE__ ), // Path to the script file
			array(), // Script dependencies
			self::VERSION // Script version
		);
		
		// Add stylesheet for Fragmention highlighting
		wp_enqueue_style(
			self::ID, // Handle for the stylesheet
			plugins_url( 'assets/css/fragmention.css', __FILE__ ), // Path to the stylesheet file
			array(), // Stylesheet dependencies
			self::VERSION // Stylesheet version
		);
		
		// If tooltip-on-highlight is enabled, add the script for it
		if ( ! empty( $this->options['tooltip'] ) ) {
			wp_enqueue_script(
				self::ID . '-highlight', // Handle for the script
				plugins_url( 'assets/js/highlighter.js', __FILE__ ), // Path to the script file
				array( 'jquery' ), // Script dependencies
				self::VERSION // Script version
			);
		}
	} // End loadscript()
	
	/* ===== Plugin Options ===== */
	// Create submenu entry under the Settings menu
	function options_menu() {
		add_options_page(
			self::NAME, // Page title. This is displayed in the browser title bar.
			self::NAME, // Menu title. This is displayed in the Settings submenu.
			'manage_options', // Capability required to access the options page for this plugin
			self::ID, // Menu slug
			array( &$this, 'options_page' ) // Function to render the options page
		);
	} // End options_menu()
	
	// Set up options page
	function options_init() {
		// Register the plugin settings
		register_setting(
			self::PREFIX . 'options_fields', // The namespace for plugin options fields. This must match settings_fields() used when rendering the form.
			self::PREFIX . 'options', // The name of the plugin options entry in the database.
			array( &$this, 'options_validate' ) // The callback method to validate plugin options
		);
		
		// Options section
		add_settings_section(
			'options', // Name of the section
			'Options', // Title of the section, displayed on the options page
			null, // Callback method to display plugin options
			self::ID // Page ID for the options page
		);
		
		// tooltip on highlight
		add_settings_field(
			'tooltip', // Field ID
			'Link tooltip on text highlight', // Field title/label, displayed to the user
			array( &$this, 'tooltip_callback' ), // Callback method to display the option field
			self::ID, // Page ID for the options page
			'options' // Settings section in which to display the field
		);
	} // End options_init()
	
	/* Plugin options callbacks */
	// tooltip on highlight
	function tooltip_callback() {
		// Whether the checkbox should be checked on page load
		if ( ! empty( $this->options['tooltip'] ) ) {
			$checked = 'checked';
		}
		else {
			$checked = null;
		}
		
		echo '<input type="checkbox" id="' . self::PREFIX . 'options_tooltip" name="' . self::PREFIX . 'options[tooltip]" value="yes" ' . $checked . '>';
		echo '<p class="description">When a user highlights text on the site, a tooltip can appear with the auto-generated fragmention link to that text, allowing the user to easily copy the link.</p>';
	} // End tooltip_callback()
	
	// Validate plugin options
	function options_validate( $input ) {
		// Set a local variable for the existing plugin options. This is so we don't mix up data.
		$options = $this->options;
		
		// If 'tooltip' is not empty, set its value
		if ( ! empty( $input['tooltip'] ) ) {
			$options['tooltip'] = 'yes';
		}
		else {
			$options['tooltip'] = null;
		}
		
		// Return the validated options
		return $options;
	} // End options_validate()
	
	// Render options page
	function options_page() {
		// Make sure the user has the necessary privileges to manage plugin options
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Sorry, you do not have sufficient privileges to access the plugin options for ' . self::NAME . '.' );
		}
		?>
		
		<div class="wrap">
			<h2><?php echo self::NAME; ?></h2>
			
			<form action="options.php" method="post">
				<?php
				settings_fields( self::PREFIX . 'options_fields' ); // Retrieve the fields created for plugin options
				do_settings_sections( self::ID ); // Display the section(s) for the options page
				submit_button(); // Form submit button generated by WordPress
				?>
			</form>
		</div>
		
		<?php
	} // End options_page()
	/* ===== End Plugin Options ===== */
	
	/* ===== Admin Initialization ===== */
	function admin_initialize() {
		// Run upgrade process
		$this->upgrade();
	} // End admin_initialize()
	
	// Plugin upgrade
	function upgrade() {
		// Check whether the database-stored plugin version number is less than the current plugin version number, or whether there is no plugin version saved in the database
		if ( ! empty( $this->options['dbversion'] ) && version_compare( $this->options['dbversion'], self::VERSION, '<' ) ) {
			// Set local variable for options (always the first step in the upgrade process)
			$options = $this->options;
			
			// If the 'tooltip' option isn't present, add it and set it to active
			if ( empty( $options['tooltip'] ) ) {
				$options['tooltip'] = 'yes';
			}
			
			/* Update the plugin options saved in the database (always the last step of the upgrade process) */
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
	 		'tooltip' => 'yes', // Display tooltip containing fragmention link when text is highlighted
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