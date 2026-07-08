<?php
/**
 * Plugin Name: Kicksite Connect
 * Description: Connects your WordPress site to the Kicksite platform.
 * Version:     1.0.0
 * Author:      Kicksite
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Registration endpoint on the Kicksite App
define( "KICKSITE_REGISTER_URL", "https://app.kicksite.net/api/v1/wp/register" );

// Query parameter name the plugin listens for
define( "KICKSITE_TOKEN_PARAM", "kicksite_token" );

// WordPress options key for the stored secret
define( "KICKSITE_SECRET_OPTION", "kicksite_wp_secret" );

// WordPress options key for a stored registration error
define( "KICKSITE_ERROR_OPTION", "kicksite_registration_error" );

// Role assigned to auto-provisioned users
define( "KICKSITE_WP_ROLE", "subscriber" );

// Required functions
require_once ( __DIR__ . "/includes/class-kicksite.php" );
require_once ( __DIR__ . "/includes/class-activator.php" );
require_once ( __DIR__ . "/includes/class-api-client.php" );
require_once ( __DIR__ . "/includes/class-deactivator.php" );

require_once ( __DIR__ . "/includes/admin/class-admin.php" );
require_once ( __DIR__ . "/includes/admin/class-admin-notices.php" );

require_once ( __DIR__ . "/includes/features/autologin/class-auth-handler.php" );
require_once ( __DIR__ . "/includes/features/autologin/class-token-validator.php" );
require_once ( __DIR__ . "/includes/features/autologin/class-user-provisioner.php" );

register_activation_hook( __FILE__, function() {
  // Calls the Kicksite_Activator class and activate method to pass in a new instance off the Kicksite Api Client
  Kicksite_Activator::activate( new Kicksite_Api_Client() );
} );

register_deactivation_hook( __FILE__, function() {
  Kicksite_Deactivator::deactivate();
} );

( new Kicksite())->run();