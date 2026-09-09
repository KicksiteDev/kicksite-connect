<?php

class Kicksite
{
  public function run() {
    $this->kicksite_admin_hooks();
    $this->kicksite_public_hooks();
    $this->kicksite_update_hooks();
  }

  private function kicksite_admin_hooks() {
    $admin = new Kicksite_Admin();
    $admin_notices = new Kicksite_Admin_Notices();
    add_action( 'admin_menu', [ $admin, 'add_settings_page' ] );
    add_action( 'admin_notices', [ $admin_notices, 'display' ] );
    add_action( 'admin_post_kicksite_save_settings', [ $admin, 'save_settings' ] );
    add_action( 'admin_enqueue_scripts', [ $admin, 'enqueue_styles' ] );
  }

  private function kicksite_public_hooks() {
    $handle = new Kicksite_Auth_Handler();
    $schedule_api = new Kicksite_Schedule_Api();
    $marker = new Kicksite_Site_Marker();
    add_action( 'init', [ $handle, 'handle' ] );
    add_action( 'init', [ $handle, 'handle_post_login' ] );
    add_action( 'init', [ $schedule_api, 'fetch_token' ] );
    add_action( 'init', [ $schedule_api, 'fetch_schedule' ] );
    add_action( 'wp_head', [ $marker, 'render_marker' ] );
  }

  // Registered outside the admin hooks because WordPress runs its update checks
  // from cron, where no admin request is involved.
  private function kicksite_update_hooks() {
    $updater = new Kicksite_Updater();

    // The hook suffix is the HOST from this plugin's Update URI header. Change
    // that header and this string has to change with it, or WordPress will call
    // a filter nobody is listening on and updates stop silently.
    // tests/Unit/UpdaterTest.php asserts the two stay in agreement.
    add_filter( 'update_plugins_github.com', [ $updater, 'check' ], 10, 3 );
    add_filter( 'auto_update_plugin', [ $updater, 'force_auto_update' ], 10, 2 );
    add_action( 'upgrader_process_complete', [ $updater, 'clear_cache' ] );
    add_action( 'init', [ $updater, 'maybe_run_migrations' ] );
  }
}
