<?php

namespace CustomDataBaseTables\Lib;


if ( !defined( 'CDBT' ) ) exit;

if ( !class_exists( 'CdbtDB' ) ) :
/**
 * Database operation class for plugins
 *
 * @since CustomDataBaseTables v2.0.0
 */
class CdbtDB extends CdbtConfig {

  protected function db_init() {
    
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
   * @param string $narrow_type For default `all`, `enable` can get the currently manageable tables on this plugin. As other is `unreserved` and `unmanageable`.
   * @return mixed Array is if find, or return `false`.
   */
  public function get_table_list( $narrow_type='all' ) {
    $all_tables = $this->wpdb->get_results('SHOW TABLES', 'ARRAY_N');
    $all_tables = $this->array_flatten($all_tables);
    $unreserved_tables = array_diff($all_tables, $this->core_tables);
    
    $manageable_tables = [];
    foreach ($this->options['tables'] as $table) {
      if ( !in_array($table['table_type'], [ 'template' ]) ) 
        $manageable_tables[] = $table['table_name'];
    }
    
    $unmanageable_tables = array_diff($all_tables, $manageable_tables);
    
    $return_tables = false;
    
    if ('all' === $narrow_type && !empty($all_tables)) 
      $return_tables = $all_tables;
    
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