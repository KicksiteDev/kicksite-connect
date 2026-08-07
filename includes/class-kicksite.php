<?php

class Kicksite
{
  public function run() {
    $this->kicksite_admin_hooks();
    $this->kicksite_public_hooks();
  }

  private function kicksite_admin_hooks() {
    $admin = new Kicksite_Admin();
    $admin_notices = new Kicksite_Admin_Notices();
    $renderer = new Kicksite_Calendar_Renderer();
    $submission = new Kicksite_Calendar_Submission();
    add_action( 'admin_menu', [ $admin, 'add_settings_page' ] );
    add_action( 'admin_notices', [ $admin_notices, 'display' ] );
    add_action( 'admin_post_kicksite_save_settings', [ $admin, 'save_settings' ] );
    add_action( 'admin_enqueue_scripts', [ $admin, 'enqueue_styles' ] );
    // add_shortcode( 'kicksite_calendar', [ $renderer, 'shortcode_handler' ] );
  }

  private function kicksite_public_hooks() {
    $handle = new Kicksite_Auth_Handler();
    $schedule_api = new Kicksite_Schedule_Api();
    $renderer = new Kicksite_Calendar_Renderer();
    $submission = new Kicksite_Calendar_Submission();
    add_action( 'init', [ $handle, 'handle' ] );
    add_action( 'init', [ $handle, 'handle_post_login' ] );
    add_action( 'init', [ $schedule_api, 'fetch_schedule' ] );
    add_action( 'init', [ $renderer, 'register_block' ] );
    add_action( 'init', [ $renderer, 'register_acf_block_path' ] );
    // add_shortcode( 'kicksite_calendar', [ $renderer, 'shortcode_handler' ] );
    add_action( 'wp_enqueue_scripts', [ $renderer, 'enqueue_public_assets' ] );
    add_action( 'wp_ajax_nopriv_kicksite_get_calendar', [ $renderer, 'fetch_calendar_data' ] );
    add_action( 'wp_ajax_kicksite_get_calendar', [ $renderer, 'fetch_calendar_data' ] );
    //
    //
  }
}