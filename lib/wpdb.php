<?php

namespace CustomDataBaseTables\DataBase;


if ( !defined( 'CDBT' ) ) exit;

if ( !class_exists( 'CdbtDb' ) ) :
/**
 * Database operation class for plugins
 *
 * @since CustomDataBaseTables v2.0.0
 */
class CdbtDb {

  protected $core;

  protected $wpdb;

  public static function instance() {
    
    static $instance = null;
    
    if ( null === $instance ) {
      $instance = new self;
      $instance->setup_globals();
      $instance->init();
    }
    
    return $instance;
  }

  private function __construct() { /* Do nothing here */ }

  public function __destruct() { /* Do nothing here */ }

  public function __call( $name, $args=null ) {
    if ( method_exists($this->wpdb, $name) ) 
      return $this->wpdb->$name($args);
    
    if ( method_exists($this->core->util, $name) ) 
      return $this->core->util->$name($args);
    
    return;
  }

  public function __get( $name ) {
    if ( property_exists($this->wpdb, $name) ) 
      return $this->wpdb->$name;
    
    if ( property_exists($this->core->util, $name) ) 
      return $this->core->util->$name;
    
    return $this->$name;
  }

  public function __set( $name, $value ) {
    $protected_members = [
      'core', 
      'wpdb', 
    ];
    if ( in_array($name, $protected_members, true) ) 
      return;
    
    $this->$name = $value;
  }

  private function setup_globals() {
    // Global Object
    global $cdbt, $wpdb;
    $this->core = is_object($cdbt) && !empty($cdbt) ? $cdbt : \CustomDataBaseTables\Core\Cdbt::instance();
    $this->wpdb = $wpdb;
    
    var_dump($this->core->util);
  }

  private function init() {
    
    // Group of tables that are reserved in wordpress
    $this->reserved_tables = array_merge($this->tables, $this->old_tables, $this->global_tables, $this->ms_global_tables);
    
    // Tables of currently database for this site
    $this->core_tables = $this->wpdb->tables('all');
    
    // Database charset
    $this->charset = $this->charset; // default `utf8` or new `utf8mb4`
    
    // Database collate
    $this->collate = $this->collate; // default `utf8_general_ci` or new `utf8mb4_unicode_ci`
    
    // Showing of database errors
    $this->show_errors = true;
    $this->show_errors( $this->show_errors );
    
  }
  
  /**
   * Methods operate database with wrapping the wpdb
   */
  
  
  
  
  
  /**
   * Retrieve specific tables list in the database.
   *
   * @since 1.1.0
   * @since 2.0.0 Have refactored logic.
   *
   * @param string $narrow_type For default `enable`, can get the currently manageable tables on this plugin. As other is `unreserved` and `unmanageable`.
   * @return mixed Array is if find, or return `false`.
   */
  public function get_table_list( $narrow_type='enable' ) {
    $all_tables = $this->wpdb->get_results('SHOW TABLES', 'ARRAY_N');
    $all_tables = $this->util->array_flatten($all_tables);
    $unreserved_tables = array_diff($all_tables, $this->core_tables);
    
    $manageable_tables = [];
    foreach ($this->core->options['tables'] as $table) {
      if ( !in_array($table['table_type'], [ 'template' ]) ) 
        $manageable_tables[] = $table['table_name'];
    }
    
    $unmanageable_tables = array_diff($all_tables, $manageable_tables);
    
    $return_tables = false;
    
    if ('enable' === $narrow_type && !empty($manageable_tables)) 
      $return_tables = $manageable_tables;
    
    if ('unreserved' === $narrow_type && !empty($unreserved_tables)) 
      $return_tables = $unreserved_tables;
    
    if ('unmanageable' === $narrow_type && !empty($unmanageable_tables)) 
      $return_tables = $unmanageable_tables;
    
    return $return_tables;
    
  }



}

endif; // end of class_exists()