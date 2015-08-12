<?php

namespace CustomDataBaseTables\Lib;


/**
 * Trait of web api difinitions for this plugin 
 *
 * @since 2.0.0
 *
 */
trait CdbtApis {
  
  private $allowed_hosts;
  
  var $request_methods = [ 'get_data', 'find_data', 'insert_data', 'update_data', 'delete_data' ];
  
  /**
   * Initialize allowed hosts requesting via Web Api
   *
   * @since 2.0.0
   */
  protected function init_allowed_hosts() {
    $_api_hosts = ( isset($this->options['api_hosts']) && is_array($this->options['api_hosts']) ) ? $this->options['api_hosts'] : [];
    
    if (isset($this->options['api_key']) && is_array($this->options['api_key']) && !empty($this->options['api_key'])) {
      // Convert the option setting of version 1.x
      $_max_host_id = max(array_keys($_api_hosts));
      foreach ($this->options['api_key'] as $_host_name => $_api_key) {
        $_max_host_id++;
        $_api_hosts[$_max_host_id] = [
          'host_name' => $_host_name, 
          'api_key' => $_api_key, 
          'desc' => __('Converted from version 1.x', CDBT), 
          'permission' => '11111', 
          'generated' => date('Y-m-d H:i:s'), 
        ];
      }
      unset($this->options['api_key']);
    }
    
    $this->allowed_hosts = $_api_hosts;
    
  }
  
  
  /**
   * Retrieve specific allowed hosts as an array
   *
   * @since 2.0.0
   *
   * @param string $host_id [optional]
   * @return array $host_list
   */
  public function get_allowed_hosts( $host_id=null ) {
    $host_list = [];
    
    if (!empty($host_id)) {
      $host_list = isset($this->allowed_hosts[intval($host_id)]) ? $this->allowed_hosts[intval($host_id)] : [];
    } else {
      $host_list = $this->allowed_hosts;
    }
    
    return $host_list;
  }
  
  
  /**
   * Check the permissions of the request method
   *
   * @since 2.0.0
   *
   * @param string $host_id [require]
   * @param string $method [optional] Name of the method that want to check
   * @return mixed $result Boolean if the method is specified; otherwise an array of all valid methods.
   */
  public function check_method_permission( $host_id=null, $method=null ) {
    if (empty($host_id)) 
      return;
    
    $_host = $this->get_allowed_hosts(intval($host_id));
    if (empty($_host)) 
      return;
    
    $_valid_methods = [];
    foreach (str_split($_host['permission']) as $_i => $_boolint) {
      if ($this->strtobool($_boolint)) {
        $_valid_methods[] = $this->request_methods[$_i];
      }
    }
    
    if (!empty($method)) {
      return in_array($method, $_valid_methods);
    } else {
      return $_valid_methods;
    }
    
  }
  
  
  /**
   * create api key for remote address
   *
   * @since 1.1.6
   * @since 2.0.0 Have refactored logic.
   *
   * @param string $remote_addr [optional] For default is null
   * @return string $api_key
   */
  public function generate_api_key( $remote_addr=null ){
    if (empty($remote_addr)) 
      $remote_addr = $_SERVER['REMOTE_ADDR'] .':'. $_SERVER['SERVER_PORT'];
    
    if (!defined(DB_NAME)) {
      $base_salt = md5($this->domain_name . DB_NAME . $_SERVER['SERVER_ADDR'] . $remote_addr . uniqid());
      $base_salt = str_split(strtoupper($base_salt), strlen($base_salt)/4);
      $api_key = implode('-', $base_salt);
    } else {
      $api_key = '';
    }
    return $api_key;
    
  }
  
  


}