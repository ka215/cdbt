<?php

namespace CustomDataBaseTables\Lib;


if ( !class_exists( 'CdbtUtility' ) ) :
/**
 * Utility functions class for plugins
 *
 * @since CustomDataBaseTables v2.0.0
 */
class CdbtUtility {

  /**
   * Stored the last message at the time of logger method call as a cache
   */
  protected $logger_cache;

  public function __construct() {
    
    $this->setup_globals();
    
  }

  public function __destruct() { return true; }

  private function setup_globals() {
    global $cdbt;
    if (!isset($cdbt)) 
      $cdbt = $this;
  }


  /**
   * Logger for this plugin
   *
   * @since 2.0.0
   *
   * @param string $message
   * @param integer $logging_type 0: php system logger, 1: mail to $distination, 3: overwriting file of $distination (default), 4: to SAPI handler
   * @param string $distination
   * @return boolean
   */
  public function logger( $message='', $logging_type=3, $distination='' ) {
    if ( !defined( 'CDBT' ) ) 
      return;
    
    $options = get_option( CDBT );
    $this->logger_cache = $message;
    
    if (!$options['debug_mode']) 
      return;
    
    if (empty($message) || '' === trim($message)) {
      if (!is_wp_error($this->errors) || empty($this->errors->get_error_message())) 
        return;
      
      $message = apply_filters( 'cdbt_log_message', $this->errors->get_error_message(), $this->errors );
    }
    
    if (!in_array(intval($logging_type), [ 0, 1, 3, 4 ])) 
      $logging_type = 3;
    
    $current_datetime = date('Y-m-d H:i:s', time());
    $message = preg_replace( '/(?:\n|\r|\r\n)/', ' ', trim($message) );
    $log_message = sprintf("[%s] %s\n", $current_datetime, $message);
    
    if (3 == intval($logging_type)) {
      $this->log_distination_path = empty($message) || '' === trim($distination) ? str_replace('lib/', 'debug.log', plugin_dir_path(__FILE__)) : $distination;
      $this->log_distination_path = apply_filters( 'cdbt_log_distination_path', $this->log_distination_path );
    }
    
    if (false === error_log( $log_message, $logging_type, $this->log_distination_path )) {
      $this->errors = new \WP_Error();
      $this->errors->add( 'logging_error', __('Failed to logging.', $this->domain_name) );
      return false;
    } else {
      return true;
    }
  }


  /**
   * Outputting the hook information to the javascript console at the time of each hook call.
   *
   * @since 2.0.0
   *
   * @param string $functon callback function name of hook
   * @param string $type 'Action' or 'Filter'
   * @param boolean $display Whether echo the javascript
   * @return void
   */
  public function console_hook_name( $function, $type, $display=true ) {
    $parse_path = explode("\\", $function);
    $hook_name = array_pop($parse_path);
    if ($display) {
      printf('<script>if(window.console&&typeof window.console.log==="function"){console.log("%s : %sHook (%s)");}</script>', str_replace('my_', '', $hook_name), $type, $hook_name);
    }
  }

  /**
   * Flatten the array or object that has nested
   *
   * @since 2.0.0
   *
   * @param mixed $data Array or Object
   * @param boolean $return_array Return an array if `true`, otherwise an object
   * @return mixed $data Array or Object specified by `return_array`
   */
  public function array_flatten( $data, $return_array=true ) {
    if (is_object($data)) 
      $data = json_decode(json_encode($data), true);
    
    if (is_array($data)) {
      if (!$this->is_assoc($data))
        $data = array_reduce($data, 'array_merge', []);
    }
    
    return $return_array ? (array) $data : (object) $data;
  }

  /**
   * Reference sequence is whether the associative array
   *
   * @since 2.0.0
   *
   * @param array $data This variable should be expected array
   * @return boolean
   */
  public function is_assoc( &$data ) {
    if (!is_array($data)) 
      return false;
    
    reset($data);
    list($k) = each($data);
    return $k !== 0;
  }

}

endif; // end of class_exists()