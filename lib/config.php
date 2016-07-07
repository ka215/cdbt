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
  
  var $option_shortcodes = array();
  
  var $allow_file_types; // For import and export
  
  var $db_charsets;
  
  var $db_collations;
  
  var $timezone_identifiers;
  
  var $db_engines;
  
  var $user_roles;
  
  var $user_capabilities;
  
  var $contribute_extends = array(); // Added since version 2.0.7
  
  var $support_email; // Added since version 2.0.9
  
  /**
   * Notification message text that can be overridden at default
   * @since 2.0.9
   */
  var $override_messages = array();
  
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
    $this->allow_file_types = [ 'csv', 'tsv', 'json', 'sql' ];
    
    $this->timezone_identifiers = \DateTimeZone::listIdentifiers();
    sort($this->timezone_identifiers);
    
    $this->user_roles = [ 'administrator', 'editor', 'author', 'contributor', 'subscriber', 'guest' ];
    
    $this->contribute_extends = [
      'jQuery' => [ 'url' => 'https://jquery.com/', 'version' => '2.2.1' ], 
      'jQuery UI' => [ 'url' => 'http://jqueryui.com/', 'version' => '1.11.4' ], 
      'Bootstrap' => [ 'url' => 'http://getbootstrap.com/', 'version' => '3.3.6' ], 
      'Underscore.js' => [ 'url' => 'http://underscorejs.org/', 'version' => '1.8.3' ], 
      'Fuel UX' => [ 'url' => 'http://getfuelux.com/', 'version' => '3.13.1' ], 
      'moment.js' => [ 'url' => 'http://momentjs.com/', 'version' => '2.11.2' ], 
      'Font Awesome' => [ 'url' => 'http://fortawesome.github.io/Font-Awesome/', 'version' => '4.6.2' ], 
      'Kinetic' => [ 'url' => 'https://github.com/davetayls/jquery.kinetic', 'version' => '2.1.0' ], 
      'Clipboard' => [ 'url' => 'https://github.com/zenorocha/clipboard.js', 'version' => '1.5.10' ], 
    ];
    
    $this->override_messages = [
      'An empty required field is exists.', 								// admin.php:505 main.php:463
      'You do not have permission to access to this page.', 				// admin.php:737,740
      'Your entry data has been successfully registered to "%s" table.', 	// admin.php:1623 main.php:661,673
      'Failed to insert data to "%s" table.', 								// admin.php:1625 main.php:665,676
      'Data updating are completed successfully.', 							// admin.php:1644 main.php:570
      'Failed to update data of of "%s" table.', 							// admin.php:1646 main.php:573
      'In the case of no change of between before and after, data does not updated.', 	// admin.php:1647 main.php:574
      'It might not have updated because there is the record which has same data.', 	// admin.php:1648 main.php:575
      'Reporting Errors', 													// admin.php:1910
      'Reporting Results', 													// admin.php:1910
      'Please select the data', 											// admin.php:1926
      'You select too many data', 											// admin.php:1931
      'Please retry to operate that after you select the data.', 			// admin.php:1927
      'Please retry after selecting one data you want to edit.', 			// admin.php:1932
      'Required field is empty', 											// admin.php:1935
      'Remove', 															// admin.php:1950,1981,2019,2025 templates/admin/cdbt_tables.php:1085
      'Please fill in the required fields of non-input.', 					// admin.php:1964
      'Form to edit data', 													// admin.php:1971
      'Update', 															// admin.php:1973
      'Removes the selected data', 											// admin.php:1978
      'Data of current deletion candidates: %s', 							// admin.php:1979
      'You can not restore that data after removed the data. Are you sure that you want to perform the data deletion?', 	// admin.php:1974
      'Preview Image', 														// admin.php:1979
      'Describe File', 														// admin.php:1984
      'Download', 															// admin.php:2004
      'Parameters required for data deletion is missing.', 					// ajax.php:444,487 main.php:625
      'Removed successfully the specified data.', 							// ajax.php:477 main.php:614
      'Can not remove some of the data.', 									// ajax.php:479 main.php:617
      'Specified conditions for finding to delete data is invalid.', 		// ajax.php:482 main.php:620
      'Could not multiple registration by the continuous transmission. So you reload this entry page, please try to refresh the token.', // main.php:669
      'The specified table&#39;s data was not found. There is no data in the table at all, or no data of matching condition.', // shortcodes/cdbt-edit.php:276 shortcodes/cdbt-view.php:304
      'View Data in "%s" Table', 											// shortcodes/cdbt-view.php:230 templates/admin/cdbt_tables.php:1357
      'Entry Data to "%s" Table', 											// shortcodes/cdbt-entry.php:130 templates/admin/cdbt_tables.php:1358
      'Edit Data of "%s" Table', 											// shortcodes/cdbt-edit.php:213 templates/admin/cdbt_tables.php:1359
      'Close', 																// templates/components/cdbt_modal.php:108,115
      // '', 
    ];
    
    $this->support_email = 'support&#064;ka2&#046;org';
    
    // Switching debug mode
    $this->debug = $this->strtobool($this->options['debug_mode']);
    if ( $this->debug ) 
      $this->prepare_debug();
    
    // Loading addons
    $this->extend = array_key_exists( 'activated_addons', $this->options ) ? $this->options['activated_addons'] : [];
    
  }


  /**
   * Define default options for plugin
   *
   * @since 2.0.0
   */
  public function set_option_template() {
    $default_datetime_format = get_option( 'links_updated_date_format' );
    $default_timezone = get_option( 'timezone_string', 'UTC' );
    
    $default_options = [
      'plugin_version' => $this->version, 
      'db_version' => $this->db_version, 
      'cleaning_options' => true, 
      'uninstall_options' => false, 
      'resume_options' => false, 
      'enable_core_tables' => false, // add new from ver.2
      'display_datetime_format' => $default_datetime_format, // add new from ver.2
      'plugin_menu_position' => 'default', // add new from ver. 2.0.7
      'notices_via_modal' => true, // add new from ver 2.0.7
      'debug_mode' => $this->debug, // add new from ver.2
      'use_wp_prefix' => true, 
      'charset' => 'utf8', 
      'timezone' => $default_timezone, 
      'default_db_engine' => '', // add new from ver.2
      'default_per_records' => 10, // add new from ver.2
      'allow_rendering_shortcodes' => true, // add new from ver.2
      'include_assets' => [], // add new from ver. 2.0.7
      'prevent_duplicate_sending' => false, // add new from ver. 2.0.7
      'display_list_format' => 'table', // add new from ver. 2.1.0
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
          'sanitization' => true, // Whether to do sanitization when register the string type data. since 2.0.7
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
      'api_hosts' => [
        // {host_id(not zero)} => [ 'host_name' => {host_name}, 'api_key' => {api_key}, 'desc' => {description}, 'permission' => {permission(bit)}, 'generated' => {generated_date(datetime)} ]
      ],
      'override_messages' => [], // add new from ver 2.0.9
      'activated_addons' => [], // add new since ver 2.1.33
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
   * @since 2.0.10 Modified
   */
  public function check_option_version() {
    $not_require_upgrade = true;
    
    if ( version_compare( $this->version, $this->options['plugin_version'] ) > 0 || version_compare( $this->db_version, $this->options['db_version'] ) > 0 ) 
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
   * @since 2.0.10 Updated
   */
  public function upgrade_options() {
    $default_options = $this->set_option_template();
    $new_options = [];
    
    if ( preg_match( '/^1\..*$/U', $this->options['plugin_version'] ) ) {
      // When upgrading from version 1.x do backup
      add_option( $this->domain_name . '/backup-v1', $this->options, '', 'no' );
    }
    
    foreach ( $default_options as $key => $value ) {
      if ( ! array_key_exists( $key, $this->options ) ) {
        
        $new_options[$key] = $value;
      } else {
        $new_options[$key] = $this->options[$key];
      }
    }
    unset( $key, $value );
    if ( $this->version !== $new_options['plugin_version'] ) 
      $new_options['plugin_version'] = $this->version;
    if ( $this->db_version !== $new_options['db_version'] ) 
      $new_options['db_version'] = $this->version;
    $_diff_array = array_diff_key( $this->options, $new_options );
    if ( ! empty( $_diff_array ) ) 
      $new_options = array_merge( $new_options, $_diff_array );
    
    // 
    // Filter to clean up the option settings in depending with the setting of "cleaning_options"
    // 
    $new_options = apply_filters( 'cdbt_cleaning_options', $new_options );
    
    update_option( $this->domain_name, $new_options );
    
    if ( isset( $this->debug ) && $this->debug ) 
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
      $message = sprintf( __('New options is not specified regarding the method "%s" call.', CDBT), __FUNCTION__ );
    
    if (empty($action) || !in_array($action, [ 'override', 'delete' ])) 
      $message = sprintf( __('Illegal action is specified to the method "%s" call.', CDBT), __FUNCTION__ );
    
    if (!empty($message)) {
      $this->logger( $message );
      return false;
    }
    
    $prev_options = $this->options;
    
    if (empty($option_key)) {
      $_diff_result = array_diff(array_keys($prev_options), array_keys($new_data));
      if ( empty($_diff_result) ) {
        // 
        // Filter to clean up the option settings in depending with the setting of "cleaning_options"
        // 
        $new_options = apply_filters( 'cdbt_cleaning_options', $new_data );
        if (!update_option( $this->domain_name, $new_options )) 
        	$mesage = __('Failed to save the option.', CDBT);
      } else {
        $message = __('Invalid options format.', CDBT);
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
              $message = __('No Table settings for removing.', CDBT);
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
              $message = __('No Api key for removing.', CDBT);
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
        $mesage = __('Did not carry out the update process.', CDBT);
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
    $_table_schema = $this->get_table_schema($table_name);
    if (is_array($_table_schema)) {
      foreach ($_table_schema as $column => $scheme) {
        if ($scheme['primary_key']) 
          $primary_keys[] = $column;
      }
    }
    $table_status = $this->get_table_status( $table_name );
    $table_charset = $this->get_table_charset( $table_name ); // $this->db_default_charset;
    $max_show_records = $this->options['default_per_records'];
    $sanitization = true;
    $user_permission_view = 'guest';
    $user_permission_entry = 'contributor';
    $user_permission_edit = 'editor';
    if ( ! empty( $table_options ) ) {
      $table_charset = array_key_exists('table_charset', $table_options) ? $table_options['table_charset'] : $table_charset;
      $max_show_records = array_key_exists('max_show_records', $table_options) ? $table_options['max_show_records'] : $max_show_records;
      $sanitization = array_key_exists( 'sanitization', $table_options ) ? $table_options['sanitization'] : $sanitization;
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
      'show_max_records' => $max_show_records, 
      'sanitization' => $sanitization, 
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
  
  
  /**
   * Retrieve allowed user permission of specified table.
   *
   * @since 2.0.0
   *
   * @param string $table_name [require] 
   * @param string $search_key [optional] 
   * @return mixed $tabel_permission
   */
  public function get_table_permission( $table_name=null, $search_key=null ) {
    if ( empty( $table_name ) ) 
      return false;
    
    $table_options = $this->get_table_option( $table_name );
    $table_permission = [];
    if ( is_array( $table_options ) && array_key_exists( 'permission', $table_options ) && ! empty( $table_options['permission'] ) ) {
      if ( ! empty( $search_key ) ) {
        foreach ( $table_options['permission'] as $_key => $_values ) {
          if ( $search_key === strstr( $_key, '_global', true ) ) 
            $table_permission += $_values; //array_merge($table_permission);
        }
      } else {
        $table_permission = $table_options['permission'];
      }
    } else
    if ( is_array( $table_options ) && array_key_exists( 'roles', $table_options ) && ! empty( $table_options['roles'] ) ) {
      // For legacy plugin version
      if ( ! empty( $search_key ) ) {
        foreach ( $table_options['roles'] as $_key => $_level ) {
          if ( $search_key === strstr( $_key, '_role', true ) ) 
            $table_permission = $this->convert_cap_level( $_level );
        }
      } else {
        foreach ( $table_options['roles'] as $_key => $_level ) {
          $table_permission[str_replace( '_role', '_global', $_key )] = $this->convert_cap_level( $_level );
        }
      }
    } else {
      $table_permission = false;
    }
    
    return $table_permission;
  }
  
  
  /**
   * Convert to the role name from capability level of user.
   *
   * @since 2.0.0
   *
   * @param int $level [require] 
   * @return array $user_role_names
   */
  public function convert_cap_level( $level=0 ) {
    if (empty($level) || !is_int($level)) 
      $level = 0;
    
    if ($level > 10) 
      $level = 10;
    
    $user_level_roles = [ 'subscriber', 'contributor', 'author', 'editor', 'editor', 'editor', 'editor', 'editor', 'administrator', 'administrator', 'administrator' ];
    $user_role_names = [];
    if ($level === 0) {
      $user_role_names[] = 'guest';
    }
    $user_role_names[] = $user_level_roles[$level];
    
    return $user_role_names;
  }
  
  
  /**
   * Prepare for debug mode.
   *
   * @since 2.0.4
   *
   * @return void
   */
  public function prepare_debug() {
    if ( ! isset( $this->log_distination_path ) ) 
      $this->log_distination_path = $this->plugin_dir . 'debug.log';
    
    if ( ! file_exists( $this->log_distination_path ) ) 
      @file_put_contents( $this->log_distination_path, '', LOCK_EX );
    
    if ( ! is_writable( $this->log_distination_path ) ) {
      if ( ! chmod( $this->log_distination_path, 0666 ) ) {
        $this->logger( __( 'Debug log file does not have writable permission.', CDBT ) );
      }
    }
    
  }
  
  
  
}

endif; // end of class_exists()