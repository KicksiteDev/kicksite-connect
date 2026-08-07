<?php
/**
 * Plugin Name: Kicksite Connect
 * Description: Connects your WordPress site to the Kicksite platform.
 * Version:     1.0.0
 * Author:      Kicksite
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Web url for the plugin
define( 'KICKSITE_URL', plugin_dir_url( __FILE__ ) );

// Filepath for the plugin
define( 'KICKSITE_PATH', plugin_dir_path( __FILE__ ) );

// Plugin Version
define( 'KICKSITE_CONNECT_VERSION', '1.0.0' ); // ---> KEEP IN SYNC WITH HEADER VERSION NUMBER <---

// Slug for consistent naming
define( 'KICKSITE_SLUG', 'kicksite-connect' );

// Query parameter name the plugin listens for
define( "KICKSITE_TOKEN_PARAM", "kicksite_token" );

// WordPress options key for the stored secret
define( "KICKSITE_SECRET_OPTION", "kicksite_wp_secret" );

// WordPress option key for the client's Kicksite subdomain
define( "KICKSITE_SUBDOMAIN_OPTION", "kicksite_subdomain" );

// WordPress option key for the api bearer token generated in Kicksite
define( "KICKSITE_TOKEN_OPTION", "kicksite_bearer_token" );

// WordPress options key for a stored registration error
define( "KICKSITE_ERROR_OPTION", "kicksite_registration_error" );

// Role assigned to auto-provisioned users
define( "KICKSITE_WP_ROLE", "subscriber" );

// Kicksite admin email domain
define( 'KICKSITE_ADMIN_DOMAIN', '@kicksite.net' );

// Kicksite client application id found under "client applications"
define( "KICKSITE_APP_ID_OPTION", "kicksite_app_id" );

// Kicksite secret key found under "client applications"
define( "KICKSITE_SECRET_KEY_OPTION", "kicksite_secret_key" );

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
require_once ( __DIR__ . "/includes/features/calendar/class-calendar-renderer.php" );
require_once ( __DIR__ . "/includes/features/calendar/class-calendar-submission.php" );
require_once ( __DIR__ . "/includes/features/schedule/class-schedule-api.php" );

register_activation_hook( __FILE__, function() {
  Kicksite_Activator::activate();
} );

register_deactivation_hook( __FILE__, function() {
  Kicksite_Deactivator::deactivate();
} );

( new Kicksite())->run();