<?php

namespace CustomDataBaseTables\Lib;

if ( !class_exists( 'CdbtCore' ) ) :
/**
 * Main Plugin Core Class
 * 
 * @since CustomDataBaseTables v2.0.0
 *
 * @see CustomDataBaseTables\Lib\Utility
 */
class CdbtCore extends CdbtUtility {
  
  /**
   * @var mixed False when not logged in; WP_User object when logged in
   */
  public $current_user = false;
  
  /**
   * @var obj Append to this plugin as addon
   */
  public $extend;
  
  /**
   * @var array Overloads get_option()
   */
  public $options = array();
  
  /**
   * @var array Overloads get_user_meta()
   */
  public $user_options = array();
  
  /**
   * @var mixed False when not error or default; WP_Error object when has errors
   */
  public $errors = false;
  
  /**
   * @var boolean True is if debug mode
   */
  public $debug = false;
  
  
/*
  public function __call( $name, $args=null ) {
    // For compatible methods with version 1.x
    $legend_methods = [
      'truncate_table' => 'CdbtDb', 
      'drop_table' => 'CdbtDb', 
      'create_table' => 'CdbtDb', 
      'get_table_schema' => 'CdbtDb', 
      'get_table_comment' => 'CdbtDb', 
      'get_create_table_sql' => 'CdbtDb', 
      'get_data' => 'CdbtDb', 
      'find_data' => 'CdbtDb', 
      'insert_data' => 'CdbtDb', 
      'update_data' => 'CdbtDb', 
      'update_where' => 'CdbtDb', 
      'run_query' => false, // deprecated
      'delete_data' => 'CdbtDb', 
      'validate_data' => 'Validation', 
      'validate_create_sql' => 'Validation', 
      'validate_alter_sql' => 'Validation', 
      'compare_reservation_tables' => 'CdbtDb', 
      'import_table' => 'CdbtDb', 
      'export_table' => 'CdbtDb', 
      'get_table_list' => 'CdbtDb', 
      'incorporate_table_option' => false, // deprecated
    ];
    if ( method_exists($this, $name) ) {
      return $this->$name($args);
    } elseif ( method_exists($this->util, $name)) {
      return $this->util->$name($args);
    } elseif ( array_key_exists($name, $legend_methods) ) {
      if ('CdbtDb' === $legend_methods[$name]) 
        return $this->db->$name($args);
      
      if ('Validation' === $legend_methods[$name]) 
        return $this->validate->$name($args);
      
    }
    
    throw new \BadMethodCallException( sprintf( __('Method "%s" does not exist.', CDBT), $name ) );
  }
*/
  
  
  protected function core_init() {
    
    // Plugin Name
    $this->domain_name = CDBT;
    $this->basename = apply_filters( 'cdbt_plugin_name', $this->domain_name );
    
    // Versions
    $this->version = CDBT_PLUGIN_VERSION;
    $this->db_version = CDBT_DB_VERSION;
    
    // Paths
    $this->file = __FILE__;
    $this->plugin_lib_dir = apply_filters( 'cdbt_plugin_lib_dir_name', 'lib' );
    $this->plugin_templates_dir = apply_filters( 'cdbt_plugin_templates_dir_name', 'templates' );
    $this->plugin_assets_dir = apply_filters( 'cdbt_plugin_assets_dir_name', 'assets' );
    $this->plugin_dir = apply_filters( 'cdbt_plugin_dir_path', str_replace($this->plugin_lib_dir . '/', '', plugin_dir_path( $this->file )) );
    $this->plugin_url = apply_filters( 'cdbt_plugin_dir_url', str_replace($this->plugin_lib_dir . '/', '', plugin_dir_url( $this->file )) );
    $this->plugin_main_file = apply_filters( 'cdbt_plugin_main_file', $this->plugin_dir . 'cdbt.php' );
    
    // Languages
    $this->plugin_lang_dir = apply_filters( 'cdbt_plugin_lang_dir', plugin_basename($this->plugin_dir) . '/langs' );
    load_plugin_textdomain( $this->domain_name )
    or load_plugin_textdomain( $this->domain_name, false, $this->plugin_lang_dir );
    
    // State
    $this->plugin_enabled = false;
    
    
  }
  
  
  public function check_plugin_notices() {
    
    $php_min_version = '5.4';
    $extensions = [
//      'iconv', 
      'mbstring', 
//      'id3'
    ];
    
    $php_current_version = phpversion();
    $this->errors = new \WP_Error();
    
    if (version_compare( $php_min_version, $php_current_version, '>=' )) 
      $this->errors->add('php_version_error', sprintf(__('Your server is running PHP version %s but this plugin requires at least PHP %s. Please run an upgrade.', $this->domain_name), $php_current_version, $php_min_version));
    
    foreach ($extensions as $extension) {
      if (!extension_loaded($extension)) 
        $this->errors->add('lack_extension_error', sprintf(__('Please install the extension %s to run this plugin.', $this->domain_name), $extension));
    }
    
    if (!is_wp_error($this->errors) || empty($this->errors->get_error_message())) {
      $this->plugin_enabled = true;
      return;
    }
    
    unset( $_GET['activate'] );
    
    $this->logger( $this->errors->get_error_message() );
    
    printf( '<div class="error"><p>%s</p><p>%s</p></div>', $this->errors->get_error_message(), sprintf(__('The %s has been deactivated.', $this->domain_name), __('Custom DataBase Tables', $this->domain_name)) );
    
    deactivate_plugins( $this->plugin_main_file );
  }
  
  
  
}

endif; // end of class_exists()
