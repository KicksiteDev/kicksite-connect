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
    add_action( 'admin_menu', [ $admin, 'add_settings_page' ] );
    add_action( 'admin_notices', [ $admin_notices, 'display' ] );
  }

  private function kicksite_public_hooks() {
    $handle = new Kicksite_Auth_Handler();
    add_action( 'init', [ $handle, 'handle' ] );
  }
}