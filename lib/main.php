<?php

namespace CustomDataBaseTables\Lib;


if ( !defined( 'CDBT' ) ) exit;

if ( !class_exists( 'CdbtFrontend' ) ) :


final class CdbtFrontend extends CdbtDB {

  /**
   * Instance factory method as entry point of plugin.
   *
   * @since 2.0.0
   */
  public static function instance() {
    
    static $instance = null;
    
    if ( null === $instance ) {
      $instance = new self;
      $instance->setup_globals();
      $instance->init();
      $instance->setup_actions();
    }
    
    return $instance;
  }

 /**
  * Define magic methods as follow;
  */
  public function __construct() { /* Do nothing here */ }

  public function __destruct() { /* Do nothing here */ }




  /**
   * Wrapping global object of wordpress
   *
   * @since 2.0.0
   */
  private function setup_globals() {
    
    global $wpdb;
    $this->wpdb = $wpdb;
    
  }


  /**
   * 
   *
   * @since 2.0.0
   */
  private function init() {
    
    
    
  }


  /**
   * 
   *
   * @since 2.0.0
   */
  private function setup_actions() {
    
    
    
  }


}

endif; // end of class_exists()