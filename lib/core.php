<?php

namespace CustomDataBaseTables\Core;

if ( !class_exists( 'Cdbt' ) ) :
/**
 * Main Plugin Core Class
 * 
 * @since CustomDataBaseTables (r******)
 */
final class Cdbt {
  
  /**
   * Magic method?
   *
   * @var array
   */
  private $data;
  
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
   * Factory Method
   */
  public static function instance() {
    
    static $instance = null;
    
    if ( null === $instance ) {
      $instance = new self;
      $instance->init();
      $instance->includes();
      $instance->setup_actions();
    }
    
    return $instance;
  }
  
  
  private function __construct() { /* Do nothing here */ }
  
  
  private function init() {
    
    // Plugin Name
    $this->domain_name = CDBT;
    $this->basename = apply_filters( 'cdbt_plugin_name', $this->domain_name );
    
    // Versions
    $this->version = CDBT_PLUGIN_VERSION;
    $this->db_version = CDBT_DB_VERSION;
    
    // Paths
    $this->file = __FILE__;
    $this->plugin_lib_dir = apply_filters( 'cdbt_plugin_lib_dir_name', 'lib' );
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
  
  /**
   * Include Worker Classes
   */
  private function includes() {
    
    if (class_exists( 'CustomDataBaseTables\Config\CdbtConfig' )) {
      $this->config = \CustomDataBaseTables\Config\CdbtConfig::instance();
    }
    
    if (class_exists( 'CustomDataBaseTables\Shortcodes\CdbtShortcodes' )) {
      $this->shortcodes = \CustomDataBaseTables\Shortcodes\CdbtShortcodes::instance();
    }
    
    if (is_admin()) {
      if (class_exists( 'CustomDataBaseTables\Admin\CdbtAdmin' )) {
        $this->admin = \CustomDataBaseTables\Admin\CdbtAdmin::instance();
      }
      
    }
    
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
  
  
  private function setup_actions() {
    
    if (!empty($GLOBALS['pagenow']) && 'plugins.php' === $GLOBALS['pagenow'] ) 
      add_action( 'admin_notices', array($this, 'check_plugin_notices'));
    
    $this->options = $this->config->load_options();
    
    $this->debug = $this->options['debug_mode'] ? true : false;
    
  }
  
  
  public function logger( $log_message='', $logging_type=3, $log_distination='' ) {
    if (false === $this->debug)
      return;
    
    if (!isset($log_message) || empty($log_message)) {
      if (!is_wp_error($this->errors) || empty($this->errors->get_error_message())) 
        return;
      
      $log_message = apply_filters( 'cdbt_log_message', $this->errors->get_error_message(), $this->errors );
    }
    
    if (!in_array(intval($logging_type), [0, 1, 3, 4])) 
      $logging_type = 3;
    
    if (empty($log_distination)) 
      $log_distination = $this->plugin_dir . 'debug.log';
    
    if (false === \CustomDataBaseTables\Common\logger( $log_message, $logging_type, $log_distination )) {
      $this->errors = new \WP_Error();
      $this->errors->add( 'logging_error', __('Failed to logging.', $this->domain_name) );
    }
    
  }
  
  
  private function __destruct() { /* Do nothing here */ }
  
}

function cdbt( $type='set_global' ) {
  if (isset($type) && $type != 'set_global' ) {
    return Cdbt::instance();
  } else {
    global $cdbt;
    $cdbt = Cdbt::instance();
  }
}

endif; // end of class_exists()
