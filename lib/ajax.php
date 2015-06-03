<?php

namespace CustomDataBaseTables\Lib;


/**
 * Trait of ajax processes for this plugin
 *
 * @since 2.0.0
 *
 */
trait CdbtAjax {

  /**
   * Define action hooks of Ajax call
   * cf. $plugin_ajax_action is `cdbt_ajax_handler`
   *
   * @since 2.0.0
   **/
  protected function ajax_init() {
    
    add_action('wp_ajax_' . $this->plugin_ajax_action, array(&$this, 'ajax_handler'));
    add_action('wp_ajax_nopriv_' . $this->plugin_ajax_action, array(&$this, 'ajax_handler'));
    
  }


  public function ajax_url( $args=[] ) {
    if (!is_array($args)) 
      return;
    
    $base_url = esc_url_raw(admin_url( 'admin-ajax.php', is_ssl() ? 'https' : 'http' ));
    
    $ajax_queries = array_merge( [ 'action' => $this->plugin_ajax_action ], $args );
    $base_url = esc_url_raw(add_query_arg( $ajax_queries, $base_url ));
    
    return wp_nonce_url( $base_url, $this->domain_name . '_' . $this->plugin_ajax_action );
    
  }


  /**
   * Method of the handling of Ajax call
   * 
   *
   * @since 2.0.0
   **/
  public function ajax_handler() {
    if (!isset($GLOBALS['_REQUEST']['_wpnonce'])) 
      $this->ajax_error( __('Parameters for calling Ajax is not enough.', CDBT) );
    
    if (!wp_verify_nonce( $GLOBALS['_REQUEST']['_wpnonce'], $this->domain_name . '_' . $this->plugin_ajax_action )) {
      if (isset($_REQUEST['api_key']) && !empty($_REQUEST['api_key'])) {
        // verify api key
        
      } else {
        $this->ajax_error( __('Failed authentication. Invalid Ajax call.', CDBT) );
      }
    }
    
    if (!isset($GLOBALS['_REQUEST']['event'])) 
      $this->ajax_error( __('Ajax event is not specified.', CDBT) );
    
    $event_method = 'ajax_event_' . rtrim($GLOBALS['_REQUEST']['event']);
    
    if (!method_exists($this, $event_method)) 
      $this->ajax_error( __('Method handling of an Ajax event does not exist.', CDBT) );
    
    $this->$event_method( $GLOBALS['_REQUEST'] );
    
  }


  /**
   * Error Handling of Ajax
   *
   * @since 2.0.0
   *
   * @param $string $error_message [optional]
   **/
  public function ajax_error( $error_message=null ) {
    
    if (empty($error_message)) 
      $error_message = __('Error of Ajax.', CDBT);
    
    die( $error_message );
    
  }


  /**
   * Ajax events
   * -------------------------------------------------------------------------
  
  /**
   * Set the session before the callback processing as a URL redirection
   *
   * @since 2.0.0
   *
   * @param array $args [require] Array of data for setting to session
   * @return string $callback Like a javascript function
   */
  public function ajax_event_setup_session( $args ) {
    
    if (isset($args) && !empty($args)) {
      if (isset($args['session_key']) && !empty($args['session_key'])) {
        $session_key = $args['session_key'];
        unset($args['session_key']);
        
        $this->destroy_session( $session_key );
        
        foreach ($args as $key => $value) {
        	if (in_array($key, [ 'action', 'event', '_wpnonce' ])) {
        	  continue;
        	}
        	
          if ('callback_url' === $key) {
            $callback = sprintf( "location.href = '%s';", $value );
          }
          
          $_SESSION[$session_key][$key] = $value;
        }
        
        if (isset($callback)) 
          die( $callback );
        
      }
    }
    
    $this->ajax_error( __('Failed to update of the session.', CDBT) );
    
  }
  
  /**
   * Retrieve the component in the Modal dialog via Ajax
   *
   * @since 2.0.0
   *
   * @param array $args [optional] Array of options for modal component
   * @return void Output the HTML document for callback on the frontend
   */
  public function ajax_event_retrieve_modal( $args=[] ) {
    
    if (array_key_exists('insertContent', $args) && 'true' === $args['insertContent']) {
      //
      // Filter for modal content settings
      //
      $args = apply_filters( 'cdbt_dynamic_modal_options', $args );
    }
    
    $modal_contents = $this->component_render('modal', $args); // by trait `DynamicTemplate`
    
    die($modal_contents);
    
  }
  
  
  /**
   * Run the table export via Ajax
   *
   * @since 2.0.0
   *
   * @param array $args [require]
   * @return void Output the JavaScript for callback on the frontend
   */
  public function ajax_event_export_table( $args=[] ) {
    static $message = '';
    $notices_class = CDBT . '-error';
    /*
"array(9) {
  ["action"]=>
  string(17) "cdbt_ajax_handler"
  ["event"]=>
  string(12) "export_table"
  ["_wpnonce"]=>
  string(10) "99e49003c4"
  ["export_filetype"]=>
  string(3) "csv"
  ["add_index_line"]=>
  string(0) ""
  ["export_columns"]=>
  array(4) {
    [0]=>
    string(9) "option_id"
    [1]=>
    string(11) "option_name"
    [2]=>
    string(12) "option_value"
    [3]=>
    string(8) "autoload"
  }
  ["export_table"]=>
  string(12) "copy_options"
  ["table_name"]=>
  string(12) "copy_options"
  ["operate_action"]=>
  string(6) "export"
}
0"*/
//    var_dump($args);
    if (!isset($args['export_filetype']) || empty($args['export_filetype']) || !in_array($args['export_filetype'], $this->allow_file_types)) {
      $message = __('Format of the download file is not specified.', CDBT);
    } else
    if (!isset($args['export_columns']) || empty($args['export_columns']) || !is_array($args['export_columns'])) {
      $message = __('Export columns has not been specified. You must specify at least one or more columns.', CDBT);
      $args['export_columns'] = [];
    } else
    if (!isset($args['export_table']) || empty($args['export_table'])) {
      $message = __('Export table is not specified.', CDBT);
    }
    $add_index_line = isset($args['add_index_line']) && 1 === intval($args['add_index_line']) ? true : false;
    
    if (empty($message)) {
      $result = $this->export_table( $args['export_table'], array_values($args['export_columns']), $args['export_filetype'], $add_index_line );
//      var_dump($result);
    }
    
    // Set sessions
    $this->cdbt_sessions[$_POST['active_tab']] = [
      'target_table' => $args['export_table'], 
      'export_filetype' => $args['export_filetype'], 
      'add_index_line' => $add_index_line, 
      'export_columns' => $args['export_columns'], 
      'operate_action' => $args['operate_action'], 
    ];
    
  }
  
  
  /**
   * Run the table truncate via Ajax
   *
   * @since 2.0.0
   *
   * @param array $args [require]
   * @return void Output the JavaScript for callback on the frontend
   */
  public function ajax_event_truncate_table( $args=[] ) {
    static $message = '';
    $notices_class = CDBT . '-error';
    
    if (array_key_exists('table_name', $args) && array_key_exists('operate_action', $args) && 'truncate' === $args['operate_action']) {
      
      if ($this->truncate_table( $args['table_name'] )) {
        $notices_class = CDBT . '-notice';
        $message = sprintf( __('Table of "%s" has been truncated successfully.', CDBT), $args['table_name'] );
      } else {
        $message = sprintf( __('Failed to truncate the table of "%s".', CDBT), $args['table_name'] );
      }
      
    } else {
      
      $message = sprintf( __('Parameters required for table truncation is missing.', CDBT) );
      
    }
    
    $this->register_admin_notices( $notices_class, $message, 3, true );
    die('location.reload();');
    
  }
  
  
  /**
   * Run the table drop via Ajax
   *
   * @since 2.0.0
   *
   * @param array $args [require]
   * @return void Output the JavaScript for callback on the frontend
   */
  public function ajax_event_drop_table( $args=[] ) {
    static $message = '';
    $notices_class = CDBT . '-error';
    
    if (array_key_exists('table_name', $args) && array_key_exists('operate_action', $args) && 'drop' === $args['operate_action']) {
      
      if ($this->drop_table( $args['table_name'] )) {
        // Update of the plugin option
        if ($this->update_options( [ 'table_name' => $args['table_name'] ], 'delete', 'tables' )) {
          $notices_class = CDBT . '-notice';
          $message = sprintf( __('Table of "%s" has been removed successfully.', CDBT), $args['table_name'] );
        } else {
        	$message = __('Removing table was success, but failed to update options.', CDBT);
        }
      } else {
        $message = sprintf( __('Failed to remove the table of "%s".', CDBT), $args['table_name'] );
      }
      
    } else {
      
      $message = sprintf( __('Parameters required for table deletion is missing.', CDBT) );
      
    }
    
    $this->register_admin_notices( $notices_class, $message, 3, true );
    die('location.reload();');
    
  }
  
  


}
