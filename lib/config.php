<?php

namespace CustomDataBaseTables\Config;


if ( !defined( 'CDBT' ) ) exit;

if ( !class_exists( 'CdbtConfig' ) ) :

class CdbtConfig {

  var $option_template = array();

  public static function instance() {
    
    static $instance = null;
    
    if ( null === $instance ) {
      $instance = new CdbtConfig;
      $instance->setup_globals();
      $instance->init();
      //$instance->setup_actions();
    }
    
    return $instance;
  }

  public function __construct() { /* Do nothing here */ }

  public function setup_globals() {
    // Global Object
    global $cdbt;
    $this->core = is_object($cdbt) && !empty($cdbt) ? $cdbt : \CustomDataBaseTables\Core\Cdbt::instance();
    
  }

  private function init() {
    
    if (empty($this->core->options)) 
      $this->core->options = get_option( $this->core->domain_name );
    
    if (empty($this->core->options)) 
      $this->initialize_options();
    
    if (!$this->validate_option_schema() || !$this->check_option_version()) 
      $this->upgrade_options();
    
//var_dump($this->core->options);
  }

  private function setup_actions() {
    /*
    // Initial Action
    add_action( 'admin_init', array($this, 'admin_initialize') );
    
    // General Actions
    add_action( 'admin_menu', array($this, 'admin_menus') );
    
    // Add New Actions
    do_action( 'cdbt_get_admin_template', array($this, 'get_admin_template') );
    
    // Filters
    add_filter( 'plugin_action_links', array($this, 'modify_plugin_action_links'), 10, 2 );
    */
  }


  public function set_option_template() {
    $default_timezone = get_option( 'timezone_string', 'UTC' );
    
    $default_options = [
      'plugin_version' => $this->core->version, 
      'db_version' => $this->core->db_version, 
      'cleaning_options' => true, 
      'uninstall_options' => false, 
      'resume_options' => false, 
      'enable_core_tables' => false, // add new from ver.2
      'debug_mode' => false, // add new from ver.2
      'use_wp_prefix' => true, 
      'charset' => 'utf8', 
      'timezone' => $default_timezone, 
      'default_db_engine' => '', // add new from ver.2
      'default_per_records' => 10, // add new from ver.2
      'tables' => [
        [
          'table_name' => '', // table name
          'table_type' => 'template', // Whether "regular" or "import" or "core" or "extend"
          'primay_key' => array(), // add new from ver.2
          'sql' => '', // create table sql
          'db_engine' => 'InnoDB', // "InnoDB" or "MyISAM"
          'show_max_records' => 10, // default is 10
          'roles' => [ // For old ver.1.x; Leave for backward compatibility
            'view_role' => 9, 
            'input_role' => 9, 
            'edit_role' => 9, 
            'admin_role' => 9, 
          ], 
          'permission' => [
            // {shortcode_name} => array of user capabilities (add new from ver.2)
          ], 
          'entry_scheme' => [ // change from "display_format" at ver.1
            // {column_name} => array('(require|optional)', '(show|hide|none)', '{display_item_name}', '{default_value}', '(string|integer|float|date|binary)')
          ], 
        ], 
      ], 
      'api_key' => array(),
    ];
    
    return $default_options;
  }


  public function validate_option_schema() {
    $default_options = $this->set_option_template();
    $missing_options = [];
    
    foreach ($default_options as $key => $value) {
      if (!array_key_exists($key, $this->core->options)) {
//var_dump($key . " is not exists in options.\n");
        $missing_options[$key] = $value;
      }
    }
    unset($key, $value);
    
    if (empty($missing_options)) {
      return true;
    } else {
      if (isset($this->core->debug) && $this->core->debug) 
        $this->core->logger( sprintf(__('The missing options is as follow: %s', CDBT), implode(', ', array_keys($missing_options))) );
      
      return false;
    }
    
  }


  public function check_option_version() {
    $not_require_upgrade = true;
    
    if (version_compare($this->core->version, $this->core->options['plugin_version']) > 0) 
      $not_require_upgrade = false;
    
    if (version_compare($this->core->db_version, $this->core->options['db_version']) > 0) 
      $not_require_upgrade = false;
    
    return $not_require_upgrade;
  }


  public function initialize_options() {
    $default_options = $this->set_option_template();
    
    add_option( $this->core->domain_name, $default_options, '', 'no' );
    
    unset($default_options);
    $this->core->options = get_option( $this->core->domain_name );
  }


  public function upgrade_options() {
    $default_options = $this->set_option_template();
    $new_options = [];
    
    foreach ($default_options as $key => $value) {
      if (!array_key_exists($key, $this->core->options)) {
        $new_options[$key] = $value;
      } else {
        $new_options[$key] = $this->core->options[$key];
      }
    }
    unset($key, $value);
    
    update_option($this->core->domain_name, $new_options);
    
    if (isset($this->core->debug) && $this->core->debug) 
      $this->core->logger( __('Plugin options has upgraded.', CDBT) );
    
  }


  public function load_options() {
    
    
  }
  
  
}

endif; // end of class_exists()