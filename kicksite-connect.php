<?php
/**
 * Plugin Name: Kicksite Connect
 * Description: Connects your WordPress site to the Kicksite platform.
 * Version:     1.0.0
 * Author:      Kicksite
 * Update URI:  https://github.com/KicksiteDev/kicksite-connect
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Filepath for the plugin
define( 'KICKSITE_URL', plugin_dir_url( __FILE__ ) );

// Slug for consistent naming
define( 'KICKSITE_SLUG', 'kicksite-connect' );

// Must match the Version header above. tests/Unit/UpdaterTest.php asserts the two
// agree, because a mismatch would make every site either miss updates entirely or
// reinstall the same release forever.
define( "KICKSITE_VERSION", "1.0.0" );

// "kicksite-connect/kicksite-connect.php" — how WordPress identifies this plugin.
define( "KICKSITE_PLUGIN_BASENAME", plugin_basename( __FILE__ ) );

// GitHub repository that publishes releases. This repo MUST be public: customer
// sites download the release zip from it anonymously, with no credentials.
define( "KICKSITE_UPDATE_REPO", "KicksiteDev/kicksite-connect" );

// WordPress option key recording the version that last completed its migrations
define( "KICKSITE_VERSION_OPTION", "kicksite_installed_version" );

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

// Name attribute of the hidden site identity marker meta tag.
// A separate Kicksite service matches on this exactly. Never change it.
define( "KICKSITE_MARKER_NAME", "kicksite-site-marker" );

// Content attribute of the hidden site identity marker meta tag.
// A separate Kicksite service matches on this exactly. Never change it.
define( "KICKSITE_MARKER_VALUE", "kicksite" );

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
require_once ( __DIR__ . "/includes/features/schedule/class-schedule-api.php" );
require_once ( __DIR__ . "/includes/features/marker/class-site-marker.php" );
require_once ( __DIR__ . "/includes/features/updater/class-updater.php" );

register_activation_hook( __FILE__, function() {
  Kicksite_Activator::activate();
} );

register_deactivation_hook( __FILE__, function() {
  Kicksite_Deactivator::deactivate();
} );

( new Kicksite())->run();
