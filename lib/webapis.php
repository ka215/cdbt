<?php

namespace CustomDataBaseTables\Lib;


/**
 * Trait of web api difinitions for this plugin 
 *
 * @since 2.0.0
 *
 */
trait CdbtApis {
  
  var $allowed_hosts;
  
  var $request_methods = [ 'get_data', 'find_data', 'insert_data', 'update_data', 'update_where', 'delete_data' ];
  
  /**
   * Initialize allowed hosts requesting via Web Api
   *
   * @since 2.0.0
   */
  protected function init_allowed_hosts() {
    $chk_post_data = $this->array_flatten( $_POST );
    if ( ! empty( $chk_post_data ) ) 
      return;
    
    $_api_hosts = ( isset( $this->options['api_hosts'] ) && is_array( $this->options['api_hosts'] ) ) ? $this->options['api_hosts'] : [];
    
    if ( isset( $this->options['api_key'] ) && is_array( $this->options['api_key'] ) && ! empty( $this->options['api_key'] ) ) {
      // Convert the option setting of version 1.x
      $_max_host_id = ! empty( $_api_hosts ) ? max( array_keys( $_api_hosts ) ) : 0;
      foreach ( $this->options['api_key'] as $_host_name => $_api_key ) {
        $_max_host_id++;
        $_api_hosts[$_max_host_id] = [
          'host_name' => $_host_name, 
          'api_key' => $_api_key, 
          'desc' => __( 'Converted from version 1.x', CDBT ), 
          'permission' => '11111', 
          'generated' => date( 'Y-m-d H:i:s' ), 
        ];
      }
      $this->options['api_hosts'] = $_api_hosts;
      unset( $this->options['api_key'] );
    }
    
    $this->allowed_hosts = $_api_hosts;
    
    // Added from v2.0.7; WIP
    $_ip = isset( $_SERVER['SERVER_NAME'] ) ? gethostbyname( $_SERVER['SERVER_NAME'] ) : gethostbyname( $_SERVER['HTTP_HOST'] );
    $_is_localhost = $this->strtobool( preg_match( '/127\.0\.0\.?/', $_ip ) );
    
    if ( isset( $this->allowed_hosts ) || $_is_localhost ) {
      add_action( 'init', array( $this, 'flush_rules' ) );
      //add_filter( 'rewrite_rules_array', array($this, 'insert_rewrite_rules') );
      add_action( 'generate_rewrite_rules', array( $this, 'insert_rewrite_rules' ) );
      add_filter( 'query_vars', array( $this, 'insert_query_vars' ), 10, 1 );
      
      if ( ! empty( $this->allowed_hosts ) || ! $_is_localhost ) {
        add_action( 'send_headers', array( $this, 'allow_host' ) );
      }
      add_action( 'pre_get_posts', array( $this, 'receive_api_request' ) );
      
    }
    
  }
  
  
  /**
   * Add the extended rule for requesting api.
   *
   * @since 1.1.6
   * @since 2.0.0 Have refactored logic.
   *
   * @param array $wp_rewrite Array including object of the currently rewrite rules
   */
  protected function insert_rewrite_rules( $wp_rewrite ) {
    if ( ! $this->plugin_enabled ) 
      return;
    
    $new_rules = [
      '^cdbt_api/([^/]*)/([^/]*)/([^/]*)?$' => 'index.php?cdbt_api_key=$matches[1]&cdbt_table=$matches[2]&cdbt_api_request=$matches[3]', 
      //'^cdbt_api/([^/]*)/([^/]*)/([^/]*)/([^/]*)?$' => 'index.php?cdbt_api_key=$matches[1]&cdbt_table=$matches[2]&cdbt_api_request=$matches[3]&params=$matches[4]', 
    ];
    $wp_rewrite[0]->rules = $new_rules + $wp_rewrite[0]->rules;
    
    //add_rewrite_endpoint( 'binary_download', EP_ALL );
  }
  
  
  /**
   * Add the each variables for your wordpress site can recognize it via requesting web api.
   *
   * @since 1.1.6
   * @since 2.0.0 Have refactored logic.
   *
   * @param array $qvars
   */
  public function insert_query_vars( $qvars ) {
    $add_queries = [ 'cdbt_api_key', 'cdbt_table', 'cdbt_api_request' ];
    foreach ( $add_queries as $_query ) {
      if ( ! array_key_exists( $_query , $qvars ) ) 
        $qvars[] = $_query;
    }
    return $qvars;
  }
  
  
  /**
   * Flush the rewrite rules if extended rules are not yet included.
   *
   * @since 1.1.6
   * @since 2.0.0 Have refactored logic.
   */
  public function flush_rules() {
    $_pattern = '^cdbt_api/([^/]*)/([^/]*)/([^/]*)?$';
    $rules = get_option( 'rewrite_rules' );
    if ( is_array( $rules ) && ! array_key_exists( $_pattern, $rules ) ) {
      global $wp_rewrite;
      $wp_rewrite->flush_rules();
    }
  }
  
  
  /**
   * Enable HTTP access control (CORS)
   *
   * @param 1.1.6
   */
  public function allow_host() {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST, GET");
    header("Access-Control-Max-Age: 86400");
  }
  
  
  
  
  /**
   * controller process when receive the api request
   *
   * @since 1.1.6
   * @since 2.0.0 Refactored
   * @since 2.0.7 Update to download binary file
   *
   * @param string $wp_query
   * @return void
   */
  public function receive_api_request( $wp_query ){
    if (is_admin()) 
      return;
    
    $_cdbt_api_key = isset($wp_query->query['cdbt_api_key']) ? trim($wp_query->query['cdbt_api_key']) : '';
    $_cdbt_table = isset($wp_query->query['cdbt_table']) ? trim($wp_query->query['cdbt_table']) : '';
    $_cdbt_request_method = isset($wp_query->query['cdbt_api_request']) ? trim($wp_query->query['cdbt_api_request']) : '';
//var_dump([ $_cdbt_api_key, $_cdbt_table, $_cdbt_request_method ]);
    if (isset($_cdbt_api_key) && !empty($_cdbt_api_key)) {
      $request_uri = stripslashes_deep($_SERVER['REQUEST_URI']);
      $request_date = date('c', $_SERVER['REQUEST_TIME']);
//var_dump([ $request_uri, $request_date ]);
//var_dump([ $this->verify_api_key($_cdbt_api_key) ]);
      if ($this->verify_api_key($_cdbt_api_key)) {
        $target_table = (isset($_cdbt_table) && !empty($_cdbt_table)) ? $_cdbt_table : '';
        $request = (isset($_cdbt_request_method) && !empty($_cdbt_request_method)) ? $_cdbt_request_method : '';
        if (!empty($target_table) && !empty($request)) {
          if ($this->check_table_exists($target_table)) {
            // 200: Successful
            $response = [ 'success' => [ 'code' => 200, 'table' => $target_table, 'request' => $request, 'request_uri' => $request_uri, 'request_date' => $request_date] ];
            switch($request) {
              case 'get_data': 
                $allow_args = [ 'columns' => 'mixed', 'conditions' => 'hash', 'order' => 'hash', 'limit' => 'int', 'offset' => 'int' ];
                $response['data'] = $this->api_method_wrapper($target_table, $request, $allow_args);
                break;
              case 'find_data': 
              	// Modified at version 2.0.7; added element of 'narrow_operator'
                $allow_args = [ 'search_key' => 'array', 'narrow_operator' => 'string', 'columns' => 'mixed', 'order' => 'hash', 'limit' => 'int', 'offset' => 'int' ];
                $response['data'] = $this->api_method_wrapper($target_table, $request, $allow_args);
                break;
              case 'insert_data': 
                $allow_args = [ 'data' => 'hash' ];
                $response['data'] = $this->api_method_wrapper($target_table, $request, $allow_args);
                break;
              case 'update_data': 
                $allow_args = [ 'primary_key_value' => 'int', 'data' => 'hash' ];
                $response['data'] = $this->api_method_wrapper($target_table, $request, $allow_args);
                break;
              case 'delete_data': 
                $allow_args = [ 'primary_key_value' => 'int' ];
                $response['data'] = $this->api_method_wrapper($target_table, $request, $allow_args);
                break;
              case 'binary_download': 
                $allow_args = [ 'column' => 'string', 'conditions' => 'hash', 'hash' => 'string' ];
                $response['data'] = $this->api_method_wrapper($target_table, $request, $allow_args);
                break;
              default: 
                $response = [ 'error' => [ 'code' => 400, 'desc' => 'Invalid Request', 'request_uri' => $request_uri, 'request_date' => $request_date] ];
                break;
            }
          } else {
            $response = [ 'error' => [ 'code' => 400, 'desc' => 'Invalid Request', 'request_uri' => $request_uri, 'request_date' => $request_date] ];
          }
        } else {
          // 400: Invalid API request
          $response = [ 'error' => [ 'code' => 400, 'desc' => 'Invalid Request', 'request_uri' => $request_uri, 'request_date' => $request_date] ];
        }
      } else {
        $target_table = ( isset( $_cdbt_table ) && ! empty( $_cdbt_table ) ) ? $_cdbt_table : '';
        if ( ! empty( $target_table ) && isset( $_cdbt_request_method ) && 'binary_download' === $_cdbt_request_method ) {
          if ( $this->check_table_exists( $target_table ) ) {
            // 200: Successful
            $response = [ 'success' => [ 'code' => 200, 'table' => $target_table, 'request' => $_cdbt_request_method, 'request_uri' => $request_uri, 'request_date' => $request_date] ];
            $allow_args = [ 'column' => 'string', 'conditions' => 'hash', 'hash' => 'string' ];
            $response['data'] = $this->api_method_wrapper( $target_table, $_cdbt_request_method, $allow_args );
          } else {
            $response = [ 'error' => [ 'code' => 400, 'desc' => 'Invalid Request', 'request_uri' => $request_uri, 'request_date' => $request_date] ];
          }
        }
        if ( ! isset( $response ) || empty( $response ) ) {
          // 401: Authentication failure
          $response = [ 'error' => [ 'code' => 401, 'desc' => 'Authentication Failure', 'request_uri' => $request_uri, 'request_date' => $request_date] ];
        }
      }
      $is_crossdomain = (isset($_REQUEST['callback']) && !empty($_REQUEST['callback'])) ? trim($_REQUEST['callback']) : false;
      $_charset = isset($wp_query->query['charset']) ? trim($wp_query->query['charset']) : 'utf-8';
      header( 'Content-Type: text/javascript; charset=' . $_charset );
      if ($is_crossdomain) {
        $response = $_charset !== 'utf-8' ? $is_crossdomain . '(' . json_encode($response, JSON_UNESCAPED_UNICODE) . ')' : $is_crossdomain . '(' . json_encode($response) . ')';
      } else {
        $response = $_charset !== 'utf-8' ? json_encode($response, JSON_UNESCAPED_UNICODE) : json_encode($response);
      }
      // Currently, logging of API request is not implemented yet.
      die($response);
    } else {
      // 403: Invalid access
      // $response = [ 'error' => [ 'code' => 403, 'desc' => 'Invalid Access' ] ];
      //header("HTTP/1.1 404 Not Found", false, 404);
    }
  }
  
  
  /**
   * Wrapper of executing requested method via Web API
   *
   * @since 1.1.6
   *
   * @param string $target_table
   * @param string $request For method name
   * @param array $allow_args
   * @return mixed
   */
  public function api_method_wrapper( $target_table=null, $request=null, $allow_args=[] ) {
    foreach ($allow_args as $var_name => $val_type) {
      ${$var_name} = (isset($_REQUEST[$var_name]) && !empty($_REQUEST[$var_name])) ? trim($_REQUEST[$var_name]) : null;
      if (!empty(${$var_name})) {
        if ('mixed' === $val_type) {
          if (preg_match('/^\{(.*)\}$/U', ${$var_name}, $matches)) {
            $tmp = explode(',', rawurldecode($matches[1]));
            $tmp_ary = [];
            foreach ($tmp as $line_str) {
              list($column_name, $column_value) = explode(':', trim($line_str));
              $column_name = trim(trim(stripcslashes($column_name)), "\"' ");
              $column_value = trim(trim(stripcslashes($column_value)), "\"' ");
              if (!empty($column_name)) 
                $tmp_ary[$column_name] = empty($column_value) ? 'NULL' : $column_value;
            }
            ${$var_name} = $tmp_ary;
          } else
          if (preg_match('/^\[(.*)\]$/U', ${$var_name}, $matches)) {
            $tmp = explode(',', rawurldecode($matches[1]));
            $tmp_ary = [];
            foreach ($tmp as $line_str) {
              $tmp_ary[] = trim(trim(stripcslashes($line_str)), "\"' ");
            }
            ${$var_name} = $tmp_ary;
          }
        } else
        if ('array' === $val_type) {
          if (preg_match('/^\[(.*)\]$/U', ${$var_name}, $matches)) {
            $tmp = explode(',', rawurldecode($matches[1]));
            $tmp_ary = [];
            foreach ($tmp as $line_str) {
              $tmp_ary[] = trim(trim(stripcslashes($line_str)), "\"' ");
            }
            ${$var_name} = $tmp_ary;
          } else {
            ${$var_name} = null;
          }
        } else
        if ('hash' === $val_type) {
          if (preg_match('/^\{(.*)\}$/U', ${$var_name}, $matches)) {
            $tmp = explode(',', rawurldecode($matches[1]));
            $tmp_ary = [];
            foreach ($tmp as $line_str) {
              list($column_name, $column_value) = explode(':', trim($line_str));
              $column_name = trim(trim(stripcslashes($column_name)), "\"' ");
              $column_value = trim(trim(stripcslashes($column_value)), "\"' ");
              if (!empty($column_name)) 
                $tmp_ary[$column_name] = empty($column_value) ? 'NULL' : $column_value;
            }
            ${$var_name} = $tmp_ary;
          } else {
            ${$var_name} = null;
          }
        } else
        if ('int' === $val_type) {
          ${$var_name} = intval($_REQUEST[$var_name]);
        }
      }
    }
    switch($request) {
      case 'get_data': 
        $result = $this->get_data($target_table, $columns, $conditions, $order, $limit, $offset);
        break;
      case 'find_data': 
        // Modified at version 2.0.7; added argument of 'narrow_operator'
        $result = $this->find_data($target_table, $search_key, $narrow_operator, $columns, $order, $limit, $offset);
        break;
      case 'insert_data': 
        $result = $this->insert_data($target_table, $data, null);
        break;
      case 'update_data': 
        $result = $this->update_data($target_table, $primary_key_value, $data, null);
        break;
      case 'update_where': 
        $result = $this->update_where($target_table, $where_conditions, $data, null);
        break;
      case 'delete_data': 
        $result = $this->delete_data($target_table, $primary_key_value);
        break;
      case 'binary_download': 
        $result = $this->download_binary($target_table, $column, $conditions);
        break;
      /*
      case 'run_query': 
        $result = $this->run_query($query);
        break;
      */
      default: 
        $result = false;
        break;
    }
    
    return $result;
  }
  
  
  /**
   * Utility methods for web apis
   * ---------------------------------------------------------------------------------------
   */
  
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
      // $host_list = isset($this->allowed_hosts[intval($host_id)]) ? $this->allowed_hosts[intval($host_id)] : [];
      $host_list = isset($this->options['api_hosts'][intval($host_id)]) ? $this->options['api_hosts'][intval($host_id)] : [];
    } else {
      $host_list = $this->options['api_hosts'];
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
  
  
  /**
   * Check which a specific api key is exists.
   *
   * @since 2.0.0
   *
   * @param string $api_key [require]
   * @return mixed Return the host id if an api key is exists, or otherwise  false.
   */
  public function check_api_key_exists( $api_key=null ) {
    if (!empty($api_key) && !empty($this->allowed_hosts)) {
      foreach ($this->allowed_hosts as $host_id => $_host) {
        if ($_host['api_key'] === $api_key) {
          return $host_id;
        }
      }
    }
    
    return false;
  }
  
  
  /**
   * Verify api key in the requested URI via web api
   *
   * @since 1.1.6
   * @since 2.0.0 Have refactored logic.
   *
   * @param string $api_key
   * @return boolean
   */
  public function verify_api_key( $api_key=null ) {
    if (empty($api_key)) 
      return false;
    
    $host_id = $this->check_api_key_exists($api_key);
    $allowed_host = $this->get_allowed_hosts($host_id);
    $client_host = '';
    $client_addr = '';
    if ($host_id) {
      if (isset($_SERVER['HTTP_ORIGIN']) && !empty($_SERVER['HTTP_ORIGIN'])) {
        $client_host = preg_replace('/^(http|https|ftp):\/\/(.*)/iU', '$2', $_SERVER['HTTP_ORIGIN']);
      } else
      if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
        $client_host = preg_replace('/^(http|https|ftp):\/\/(.*)(\/|\?|:).*$/iU', '$2', $_SERVER['HTTP_REFERER']);
      } else
      if (isset($_SERVER['REMOTE_HOST']) && !empty($_SERVER['REMOTE_HOST'])) {
        $client_host = $_SERVER['REMOTE_HOST'];
      } else
      if (isset($_SERVER['REMOTE_ADDR']) && !empty($_SERVER['REMOTE_ADDR'])) {
        $client_host = gethostbyaddr($_SERVER['REMOTE_ADDR']);
      } else {
        $client_host = '';
      }
      if (!empty($client_host)) {
        list($client_addr, ) = gethostbynamel($client_host);
      } else {
        $client_addr = $_SERVER['SERVER_ADDR'];
      }
      $connection_origin = '';
      if (!empty($client_host)) {
        $result = $client_host === $allowed_host['host_name'];
        $connection_origin = $client_host;
      }
      if (!$result && !empty($client_addr)) {
        $result = $client_addr === $allowed_host['host_name'];
        $connection_origin = $client_addr;
      }
      // Logging
      $message = sprintf(__('Requested from "%s" via Web API. This connection was %s.', CDBT), $connection_origin, $result ? __('successful', CDBT) : __('failur', CDBT));
      $this->logger( $message );
    } else {
      // Allow as myself host when api key is same of nonce.
      global $wp_query;
      $result = false;
      if (wp_verify_nonce( $api_key, 'cdbt_api_ownhost' )) {
        if (isset($_SERVER['REMOTE_ADDR']) && !empty($_SERVER['REMOTE_ADDR'])) {
        	$client_host = gethostbyaddr($_SERVER['REMOTE_ADDR']);
        	list($client_addr, ) = gethostbynamel($client_host);
          $result = true;
        }
      }
    }
    
    return (isset($result) && $result) ? true : false;
    
  }

}