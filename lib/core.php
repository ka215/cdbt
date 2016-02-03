<?php

namespace CustomDataBaseTables\Lib;

if ( !class_exists( 'CdbtCore' ) ) :
/**
 * Main Plugin Core Class for CustomDataBaseTables
 * 
 * @since 2.0.0
 *
 * @see CustomDataBaseTables\Lib\CdbtUtility
 */
class CdbtCore extends CdbtUtility {
  
  var $plugin_enabled;
  
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
  public $errors;
  
  /**
   * @var boolean True is if debug mode
   */
  public $debug = false;
  
  
  /**
   * @var array Store the session information for this plugin
   */
  protected $cdbt_sessions;
  
  
  /**
   * Initialize plugin core
   *
   * @since 2.0.0
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
    
    // Ajax Action name
    $this->plugin_ajax_action = apply_filters( 'cdbt_plugin_ajax_action', 'cdbt_ajax_handler' );
    
  }
  
  
  /**
   * Plugin activation and deactivation actions 
   *
   * @since 2.0.0
   */
  protected function core_actions() {
    
    register_deactivation_hook( $this->plugin_main_file, array( &$this, 'plugin_deactivation' ) );
    register_activation_hook( $this->plugin_main_file, array( &$this, 'plugin_activate' ) );
    
    add_action( 'plugins_loaded', array($this, 'plugin_loaded') );
    //add_action( 'init', array($this, 'init_cdbt_sessions') );
    add_action( 'shutdown', array( $this, 'cdbt_shutdown' ) );
    
  }
  
  
  /**
   * Run the hooked action before http header response
   *
   * @since 2.0.0
   */
  protected function plugin_loaded() {
    $this->plugin_enabled = true;
    
    if ( (isset($_POST['page']) && 'cdbt_tables' === $_POST['page']) 
      && (isset($_POST['action']) && !empty($_POST['action'])) 
      && (isset($_POST[$this->domain_name]) && !empty($_POST[$this->domain_name]) && is_array($_POST[$this->domain_name])) 
      && (isset($_POST['file_download']) && 'true' === $_POST['file_download']) ) {
      if ('export_table' === $_POST['action']) 
        $this->download_file( $_POST[$this->domain_name] );
      
    }
    
  }
  
  /**
   * Start of the session
   *
   * @since 2.0.0
   */
  protected function init_cdbt_sessions() {
    if ( ! session_id() ) 
      session_start();
    
  }
  
  
  /**
   * Shutdown all processes
   *
   * @since 2.0.4
   */
  protected function cdbt_shutdown() {
    // Finish buffering
    $buffer = ob_get_contents();
    ob_get_clean();
    
    if ( $buffer ) 
      echo $buffer;
    
  }
  
  
  /**
   * Operating environment check for this plugin
   *
   * @since 2.0.0
   */
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
    
    $message = $this->errors->get_error_message();
    if (!is_wp_error($this->errors) || empty($message)) {
      $this->plugin_enabled = true;
      return;
    }
    
    unset( $_GET['activate'] );
    
    $this->logger( $message );
    
    printf( '<div class="error"><p>%s</p><p>%s</p></div>', $message, sprintf(__('The %s has been deactivated.', $this->domain_name), __('Custom DataBase Tables', $this->domain_name)) );
    
    $this->plugin_enabled = false;
    deactivate_plugins( $this->plugin_main_file );
  }
  
  
  /**
   * Fire an action at the time this plugin has activated.
   *
   * since 2.0.0
   * revised 2.0.4
   */
  public function plugin_activate() {
    if (!current_user_can('activate_plugins') || $this->plugin_enabled) 
      return;
    
    
    $plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
    ob_start();
    check_admin_referer( "activate-plugin_{$plugin}" );
    $buffer = ob_get_contents();
    ob_end_clean();
    if ( ! wp_validate_boolean( $buffer ) ) 
      $this->logger( $buffer );
    
    $this->plugin_enabled = true;
    
    //$role = get_role( 'administrator' );
    //$role->add_cap( 'cdbt_operate_plugin', false ); 
    
    // Add rewrite rules
    $this->prepend_rewrite_rules();
    flush_rewrite_rules();
    
    $message = sprintf(__('Function called: %s; %s', CDBT), __FUNCTION__, __('Custom DataBase Tables plugin has activated.', CDBT));
    $this->logger( $message );
    
    // as you fun
  }
  
  /**
   * Fire an action at the time this plugin was deactivation.
   *
   * since 2.0.0
   */
  public function plugin_deactivation() {
    if (!current_user_can('activate_plugins') || !$this->plugin_enabled) 
      return;
    
    $this->plugin_enabled = false;
    
    //$role = get_role( 'administrator' );
    //$role->remove_cap( 'cdbt_operate_plugin' );
    
    $message = sprintf(__('Function called: %s; %s', CDBT), __FUNCTION__, __('Custom DataBase Tables plugin has been deactivation.', CDBT));
    $this->logger( $message );
    
    // Delete rewrite rules
    flush_rewrite_rules();
  }
  
  
  /**
   * Add the extended rule for requesting api.
   *
   * @since 2.0.0
   *
   */
  protected function prepend_rewrite_rules() {
    
    add_rewrite_rule( '^cdbt_api/([^/]*)/([^/]*)/([^/]*)?$', 'index.php?cdbt_api_key=$matches[1]&cdbt_table=$matches[2]&cdbt_api_request=$matches[3]', 'top' );
    
  }
  
  
  
  
}

endif; // end of class_exists()
