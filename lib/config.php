<?php

namespace CustomDataBaseTables\Lib;


if ( !defined( 'CDBT' ) ) exit;

if ( !class_exists( 'CdbtConfig' ) ) :
/**
 * Plugin Configurations Class for CustomDataBaseTables
 * 
 * @since 2.0.0
 *
 * @see CustomDataBaseTables\Lib\CdbtCore
 */
class CdbtConfig extends CdbtCore {
  
  var $option_template = array();
  
  var $db_charsets;
  
  var $db_collations;
  
  var $timezone_identifiers;
  
  var $db_engines;
  
  var $user_roles;
  
  var $user_capabilities;
  
  /**
   * Initialize of the plugin options if options does not exist or loaded options
   *
   * @since 2.0.0
   */
  protected function options_init() {
    
    if (empty($this->options)) 
      $this->options = get_option( $this->domain_name );
    
    if (empty($this->options)) 
      $this->initialize_options();
    
    if (!$this->validate_option_schema() || !$this->check_option_version()) 
      $this->upgrade_options();
    
    // Define base variables for plugin options
    $this->timezone_identifiers = \DateTimeZone::listIdentifiers();
    sort($this->timezone_identifiers);
    
    $this->user_roles = [ 'administrator', 'editor', 'author', 'contributor', 'subscriber', 'guest' ];
    
    $this->user_capabilities['subscriber'] = explode(' ', 'read');
    $this->user_capabilities['contributor'] = array_merge(explode(' ', 'edit_posts delete_posts'), $this->user_capabilities['subscriber']);
    $this->user_capabilities['author'] = array_merge(explode(' ', 'edit_published_posts upload_files publish_posts delete_published_posts'), $this->user_capabilities['contributor']);
    $this->user_capabilities['editor'] = array_merge(explode(' ', 'moderate_comments manage_categories manage_links edit_others_posts edit_pages edit_others_pages edit_published_pages publish_pages delete_pages delete_others_pages delete_published_pages delete_others_posts delete_private_posts edit_private_posts read_private_posts delete_private_pages edit_private_pages read_private_pages unfiltered_html'), $this->user_capabilities['author']);
    $this->user_capabilities['administrator'] = array_merge(explode(' ', 'activate_plugins create_users delete_plugins delete_themes delete_users edit_files edit_plugins edit_theme_options edit_themes edit_users export import install_plugins install_themes list_users manage_options promote_users remove_users switch_themes update_core update_plugins update_themes edit_dashboard'), $this->user_capabilities['editor']);
    $this->user_capabilities['super_admin'] = array_merge(explode(' ', 'manage_network manage_sites manage_network_users manage_network_plugins manage_network_themes manage_network_options'), $this->user_capabilities['administrator']);
    
  }


  /**
   * Define default options for plugin
   *
   * @since 2.0.0
   */
  public function set_option_template() {
    $default_timezone = get_option( 'timezone_string', 'UTC' );
    
    $default_options = [
      'plugin_version' => $this->version, 
      'db_version' => $this->db_version, 
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


  /**
   * Validate current options
   *
   * @since 2.0.0
   */
  public function validate_option_schema() {
    $default_options = $this->set_option_template();
    $missing_options = [];
    
    foreach ($default_options as $key => $value) {
      if (!array_key_exists($key, $this->options)) {
        $missing_options[$key] = $value;
      }
    }
    unset($key, $value);
    
    if (empty($missing_options)) {
      return true;
    } else {
      if (isset($this->debug) && $this->debug) 
        $this->logger( sprintf(__('The missing options is as follow: %s', CDBT), implode(', ', array_keys($missing_options))) );
      
      return false;
    }
    
  }


  /**
   * Check versions of current options
   *
   * @since 2.0.0
   */
  public function check_option_version() {
    $not_require_upgrade = true;
    
    if (version_compare($this->version, $this->options['plugin_version']) > 0) 
      $not_require_upgrade = false;
    
    if (version_compare($this->db_version, $this->options['db_version']) > 0) 
      $not_require_upgrade = false;
    
    return $not_require_upgrade;
  }


  /**
   * Firstly save options when options was not exists
   *
   * @since 2.0.0
   */
  public function initialize_options() {
    $default_options = $this->set_option_template();
    
    add_option( $this->domain_name, $default_options, '', 'no' );
    
    unset($default_options);
    $this->options = get_option( $this->domain_name );
  }


  /**
   * Update the settings while complementing the items that are missing
   *
   * @since 2.0.0
   */
  public function upgrade_options() {
    $default_options = $this->set_option_template();
    $new_options = [];
    
    foreach ($default_options as $key => $value) {
      if (!array_key_exists($key, $this->options)) {
        $new_options[$key] = $value;
      } else {
        $new_options[$key] = $this->options[$key];
      }
    }
    unset($key, $value);
    
    update_option($this->domain_name, $new_options);
    
    if (isset($this->debug) && $this->debug) 
      $this->logger( __('Plugin options has upgraded.', CDBT) );
    
  }


  /**
   * As the getter method for options
   *
   * @since 2.0.0
   */
  public function load_options() {
    
    return get_option( $this->domain_name );
    
  }
  
  
}

endif; // end of class_exists()