<?php

namespace CustomDataBaseTables\Lib;

trait CdbtExtras {

  /**
   * Filter to attribute of class in the body tag of rendered page
   *
   * @param mixed $classes It is `String` when "is_admin()" is true; otherwise is `Array`
   * @return mixed $classes
   */
  public function add_body_classes( $classes ) {
    if (is_array($classes)) {
      $classes[] = 'fuelux';
      return $classes;
    } else {
      $classes_array = explode(' ', $classes);
      $classes_array[] = 'fuelux';
      return implode(' ', $classes_array);
    }
  }
  // CdbtFrontend : add_filter( 'body_class', array($this, 'add_body_classes') );


  /**
   * Condition of features during trial
   *
   * @param string $feature_name
   * @return void
   */
  function during_trial( $feature_name ) {
    $new_features = [
      // from the version 2.0.0
      'enable_core_tables', 
      'debug_mode', 
      'default_charset', 
      'localize_timezone', 
      'default_db_engine', 
      'default_per_records', 
    ];
    if (in_array($feature_name, $new_features)) {
      printf( '<span class="label label-warning">%s</span>', __('Trialling', CDBT) );
    }
  }



}