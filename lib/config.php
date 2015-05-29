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
    
    // Enable Filters
    add_filter( 'cdbt_cleaning_options', array($this, 'cleaning_options') );
    
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
          'table_comment' => '', // table comment; add new from ver.2
          'primary_key' => array(), // add new from ver.2
          'sql' => '', // create table sql
          'table_charset' => 'utf8', // add new from ver.2
          'table_collation' => '', // add new from ver.2
          'db_engine' => 'InnoDB', // "InnoDB" or "MyISAM"
          'show_max_records' => 10, // default is 10
          'roles' => [ // For old ver.1.x; Leave for backward compatibility
            'view_role' => 0, 
            'input_role' => 1, 
            'edit_role' => 4, 
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
      'api_key' => [
        // {host_name} => {api_key}
      ],
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
   * Update the settings while complementing the items that are missing.
   * Don't use to update normally options. In that case should use the `update_options()`.
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
    
    // 
    // Filter to clean up the option settings in depending with the setting of "cleaning_options"
    // 
    $new_options = apply_filters( 'cdbt_cleaning_options', $new_options );
    
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


  /**
   * Get any table setting information from the plugin option
   *
   * @since 2.0.0
   *
   * @param string $table_name [optional] All the table information is subject if not specified
   * @return mixed Normally is array; False is if can not get 
   */
  public function get_table_option( $table_name=null ) {
    static $message = '';
    static $result;
    
    if (empty($this->options['tables'])) {
      $message = __('Table settings in plugin option is empty.', CDBT);
      $this->logger( $message );
      return false;
    }
    
    foreach ($this->options['tables'] as $table) {
      if (empty($table_name)) {
        $result[$table['table_name']] = $table;
      } else {
        if ($table['table_name'] == $table_name) {
          $result = $table;
          break;
        }
      }
    }
    
    return empty($result) ? false : $result;
    
  }


  /**
   * For updating currently option settings
   *
   * @since 2.0.0
   *
   * @param mixed $new_data [require] Array or string of updates data
   * @param string $action [require] The `override` (default) is newly added if the same item does not exist, or `delete` is required $option_key.
   * @param string $option_key [optional] Target option key name for updating, and an update for the entire array of options in the case of null
   * @return boolean True if the update success, otherwise false
   */
  public function update_options( $new_data=[], $action='override', $option_key=null ) {
    static $message = '';
    static $prev_options;
    static $new_options;
    
    if (empty($new_data)) 
      $message = sprintf( __('New options is not specified when the method "%s" call.', CDBT), __FUNCTION__ );
    
    if (empty($action) || !in_array($action, [ 'override', 'delete' ])) 
      $message = sprintf( __('Illegal action is specified to the method "%s" call.', CDBT), __FUNCTION__ );
    
    if (!empty($message)) {
      $this->logger( $message );
      return false;
    }
    
    $prev_options = $this->options;
    
    if (empty($option_key)) {
      if ( empty(array_diff(array_keys($prev_options), array_keys($new_data))) ) {
        // 
        // Filter to clean up the option settings in depending with the setting of "cleaning_options"
        // 
        $new_options = apply_filters( 'cdbt_cleaning_options', $new_data );
        if (!update_option( $this->domain_name, $new_options )) 
        	$mesage = __('Failed to save the option.', CDBT);
      } else {
        $message = __('Options format is invalid.', CDBT);
      }
    } else {
      if (!array_key_exists($option_key, $prev_options)) 
        $message = __('Specified option key does not exist in option settings.', CDBT);
      
      $new_options = $prev_options;
      if (is_array($new_options[$option_key])) {
        $executed = false;
        if ('tables' === $option_key) {
          if ('override' === $action) {
            foreach($new_options[$option_key] as $i => $table) {
              if ($table['table_name'] === $new_data['table_name']) {
                // Override data
                $new_options[$option_key][$i] = $new_data;
                $executed = true;
                break;
              }
            }
            if (!$executed) {
              // Add new data
              $new_options[$option_key][] = $new_data;
              $executed = true;
            }
          }
          if ('delete' === $action) {
            foreach($new_options[$option_key] as $i => $table) {
              if ($table['table_name'] === $new_data['table_name']) {
                // Delete data
                unset($new_options[$option_key][$i]);
                $executed = true;
                break;
              }
            }
            if (!$executed) 
              $message = __('Table settings for removing does not exist.', CDBT);
          }
        }
        if ('api_key' === $option_key) {
          if ('override' === $action) {
            // Override or add data
            array_merge($new_options[$option_key], $new_data);
            $executed = true;
          }
          if ('delete' === $action) {
            if (array_key_exists(key($new_data), $new_options[$option_key])) {
              // Delete data
              unset($new_options[$option_key][key($new_data)]);
              $executed = true;
            } else {
              $message = __('Api key settings for removing does not exist.', CDBT);
            }
          }
        }
      } else {
        if ('override' === $action) {
          $new_options[$option_key] = $new_data;
          $executed = true;
        }
        if ('delete' === $action) {
        	$new_options[$option_key] = null;
        	$executed = true;
        }
      }
      if ($executed) {
        // 
        // Filter to clean up the option settings in depending with the setting of "cleaning_options"
        // 
        $new_options = apply_filters( 'cdbt_cleaning_options', $new_options );
        if (!update_option( $this->domain_name, $new_options )) 
        	$mesage = __('Failed to save the option.', CDBT);
      } else {
        $mesage = __('Update process of setting did not take place.', CDBT);
      }
    }
    
    if (!empty($message)) {
      $this->logger( $message );
      return false;
    } else {
      return true;
    }
    
  }
  
  
  /**
   * Add table to option settings and save
   *
   * @since 2.0.0
   *
   * @param array $table_name [require]
   * @param string $table_type [require] `regular` (default) or `import` or `core` or `extend`
   * @param array $table_options [optional] For example is like specified values when create table
   * @return boolean True if the update success, otherwise false
   */
  function add_new_table( $table_name=null, $table_type='regular', $table_options=null ) {
    static $message = '';
    
    $primary_keys = [];
    foreach ($this->get_table_schema($table_name) as $column => $scheme) {
      if ($scheme['primary_key']) 
        $primary_keys[] = $column;
    }
    $table_status = $this->get_table_status( $table_name );
    $table_charset = $this->db_default_charset;
    $show_max_records = $this->options['default_per_records'];
    $user_permission_view = 'guest';
    $user_permission_entry = 'contributor';
    $user_permission_edit = 'editor';
    if (!empty($table_options)) {
      $table_charset = array_key_exists('table_charset', $table_options) ? $table_options['table_charset'] : $table_charset;
      $show_max_records = array_key_exists('show_max_records', $table_options) ? $table_options['show_max_records'] : $show_max_records;
      $user_permission_view = array_key_exists('user_permission_view', $table_options) ? $table_options['user_permission_view'] : $user_permission_view;
      $user_permission_entry = array_key_exists('user_permission_entry', $table_options) ? $table_options['user_permission_entry'] : $user_permission_view;
      $user_permission_edit = array_key_exists('user_permission_edit', $table_options) ? $table_options['user_permission_edit'] : $user_permission_view;
    }
    
    $new_table = [
      'table_name' => $table_status['Name'], 
      'table_type' => $table_type, 
      'table_comment' => $table_status['Comment'], 
      'primary_key' => $primary_keys, 
      'sql' => $this->get_create_table_sql($table_name), 
      'table_charset' => $table_charset,
      'table_collation' => $table_status['Collation'], 
      'db_engine' => $table_status['Engine'], 
      'show_max_records' => $show_max_records, 
      'roles' => [
        'view_role' => 0, 
        'input_role' => 1, 
        'edit_role' => 4, 
        'admin_role' => 9, 
      ], 
      'permission' => [
        'view_global' => [ $user_permission_view ], 
        'entry_global' => [ $user_permission_entry ], 
        'edit_global' => [ $user_permission_edit ], 
      ], 
      'entry_scheme' => [], 
    ];
    
    return $this->update_options( $new_table, 'override', 'tables' );
    
  }
  
  
  /**
   * Processing of `cdbt_cleaning_options` filter hook
   *
   * @since 2.0.0
   *
   * @param array $latest_options [require] Array of unfiltered option settings
   * @return array $latest_options
   */
  public function cleaning_options( $latest_options=null ) {
    if (!$this->options['cleaning_options']) 
      return $latest_options;
    
    if (empty($latest_options)) 
      return $this->options;
    
    if (!empty($latest_options['tables'])) {
      foreach ($latest_options['tables'] as $i => $table) {
        if (!$this->check_table_exists($table['table_name'])) 
          unset($latest_options['tables'][$i]);
      }
    }
    
    return $latest_options;
  }
  
  
}

endif; // end of class_exists()