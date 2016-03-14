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


  /**
   * Retrieve the URL for calling Ajax
   *
   * @since 2.0.0
   *
   * @param array $args [require]
   * @return string $ajax_url
   **/
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
   * Ajax controller calls the actual processing in accordance with the requested event value
   *
   * @since 2.0.0
   * @since 2.0.8 Update for fixing bug
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
    
    if ( ! isset( $GLOBALS['_REQUEST']['event'] ) ) {
      if ( is_admin() ) {
        $this->ajax_error( __('Ajax event is not specified.', CDBT) );
      } else {
        wp_die();
      }
    } else {
      if ( isset( $GLOBALS['_POST']['event'] ) && ! empty( $GLOBALS['_POST']['event'] ) ) {
        $_event = rtrim( $GLOBALS['_POST']['event'] );
        $_params = $GLOBALS['_POST'];
      } else {
        $_event = rtrim( $GLOBALS['_REQUEST']['event'] );
        $_params = $GLOBALS['_REQUEST'];
      }
    }
    
    $event_method = 'ajax_event_' . $_event;
    
    if ( ! method_exists( $this, $event_method ) ) {
      $this->ajax_error( __('Method handling of an Ajax event does not exist.', CDBT) );
    }
    
    $this->$event_method( $_params );
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
            $this->register_admin_notices( CDBT . '-notice', '', 0, true );
          }
          
          $_SESSION[$session_key][$key] = $value;
        }
        
        if (isset($args['table'])) 
          $_SESSION[$session_key]['nonce'] = wp_create_nonce( 'cdbt_entry_data-' . $args['table'] );
        
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
   * Retrieve the generated nonce string via Ajax
   *
   * @since 2.0.0
   *
   * @param array $args [optional] Array of options for modal component
   * @return void Output the HTML document for callback on the frontend
   */
  public function ajax_event_retrieve_nonce( $args=[] ) {
    $nonce = '';
    
    if (array_key_exists('table', $args)) {
      $nonce = wp_create_nonce( 'cdbt_entry_data-' . $args['table'] );
    }
    
    wp_die($nonce);
  }
  
  
  /**
   * Retrieve the importing sql via Ajax
   *
   * @since 2.0.0
   *
   * @param array $args [require]
   * @return void Output the JavaScript for callback on the frontend
   */
/*
  public function ajax_event_retrieve_import_sql( $args=[] ) {
    static $message = '';
    $notices_class = CDBT . '-error';
    
    var_dump($this->get_binary_context($args['uploaded_temp_filename']));
    
  }
*/
  
  
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
  
  
  /**
   * Run the data entry via Ajax
   *
   * @since 2.0.8
   *
   * @param array $args [require]
   * @return void Output the JavaScript for callback on the frontend
   */
  public function ajax_event_entry_data( $args=[] ) {
    static $message = '';
    $notices_class = CDBT . '-error';
    
    die( $args );
    
  }
  
  
  /**
   * Run the data update via Ajax
   *
   * @since 2.0.0
   *
   * @param array $args [require]
   * @return void Output the JavaScript for callback on the frontend
   */
  public function ajax_event_update_data( $args=[] ) {
    static $message = '';
    $notices_class = CDBT . '-error';
    
    if (array_key_exists('table_name', $args) && array_key_exists('operate_action', $args) && 'edit' === $args['operate_action']) {
      
/*
      if (array_key_exists('where_condition', $args) && !empty($args['where_condition'])) {
        if ($this->update_data( $args['table_name'], $_where )) {
            $deleted_data++;
          }
        }
        if ($deleted_data === count($args['where_conditions'])) {
          $notices_class = CDBT . '-notice';
          $message = __('Specified data have been removed successfully.', CDBT);
        } else {
          $message = __('Some of the data could not remove.', CDBT);
        }
      } else {
        $message = __('Specified conditions for finding to delete data is invalid.', CDBT);
      }
*/
      
    } else {
      
      $message = sprintf( __('Parameters required for data deletion is missing.', CDBT) );
      
    }
    
//    $this->register_admin_notices( $notices_class, $message, 3, true );
//    die('location.reload();');
    
  }
  
  
  /**
   * Run of data deletion via Ajax
   *
   * @since 2.0.0
   *
   * @param array $args [require]
   * @return void Output the JavaScript for callback on the frontend
   */
  public function ajax_event_delete_data( $args=[] ) {
    static $message = '';
    $notices_class = CDBT . '-error';
    
    if (array_key_exists('table_name', $args) && array_key_exists('operate_action', $args) && 'edit' === $args['operate_action']) {
      
      if (is_array($args['where_conditions']) && !empty($args['where_conditions'])) {
        $deleted_data = 0;
        foreach ($args['where_conditions'] as $_where) {
          if ($this->delete_data( $args['table_name'], $_where )) {
            $deleted_data++;
          }
        }
        if ($deleted_data === count($args['where_conditions'])) {
          $notices_class = CDBT . '-notice';
          $message = __('Specified data have been removed successfully.', CDBT);
        } else {
          $message = __('Some of the data could not remove.', CDBT);
        }
      } else {
        $message = __('Specified conditions for finding to delete data is invalid.', CDBT);
      }
      
    } else {
      
      $message = sprintf( __('Parameters required for data deletion is missing.', CDBT) );
      
    }
    
    $this->register_admin_notices( $notices_class, $message, 3, true );
    die('location.reload();');
    
  }
  
  
  /**
   * Render the editing data form via Ajax
   *
   * @since 2.0.0
   *
   * @param array $args [require]
   * @return void Output the JavaScript for callback on the frontend
   */
  public function ajax_event_render_edit_form( $args=[] ) {
    
    if (array_key_exists('shortcode', $args)) {
      die( do_shortcode( stripslashes_deep($args['shortcode']) ) );
    }
    
  }
  
  
  /**
   * Run of shortcode deletion via Ajax
   *
   * @since 2.0.0
   *
   * @param array $args [require]
   * @return void Output the JavaScript for callback on the frontend
   */
  public function ajax_event_delete_shortcode( $args=[] ) {
    static $message = '';
    $notices_class = CDBT . '-error';
    
    if (array_key_exists('csid', $args) && array_key_exists('operate_action', $args) && 'delete' === $args['operate_action']) {
      $stored_shortcodes = $this->get_shortcode_option();
      $base_items = count($stored_shortcodes);
      if (!empty($stored_shortcodes) && intval($args['csid']) > 0) {
        foreach ($stored_shortcodes as $_i => $_shortcode_option) {
          if (intval($args['csid']) === intval($_shortcode_option['csid'])) {
        	  unset($stored_shortcodes[$_i]);
        	  break;
        	}
        }
      }
      if (count($stored_shortcodes) < $base_items) {
        if (update_option($this->domain_name . '-shortcodes', $stored_shortcodes, 'no')) {
          $notices_class = CDBT . '-notice';
          $message = sprintf(__('Have deleted a custom shortcode, that ID is follow: %d.', CDBT), intval($args['csid']));
        } else {
        	$message = __('Failed to delete the custom shortcode.', CDBT);
        }
      } else {
        $message = __('Specified custom shortcode does not exist.', CDBT);
      }
    }
    
    $this->register_admin_notices( $notices_class, $message, 3, true );
    die('location.reload();');
    
  }
  
  
  /**
   * Run of allowed host deletion via Ajax
   *
   * @since 2.0.0
   *
   * @param array $args [require]
   * @return void Output the JavaScript for callback on the frontend
   */
  public function ajax_event_delete_host( $args=[] ) {
    static $message = '';
    $notices_class = CDBT . '-error';
    
    if (array_key_exists('host_id', $args) && array_key_exists('operate_action', $args) && 'delete' === $args['operate_action']) {
      $current_hosts = $this->get_allowed_hosts();
      $base_items = count($current_hosts);
      if (!empty($current_hosts) && intval($args['host_id']) > 0) {
        foreach ($current_hosts as $_id => $_host) {
          if (intval($args['host_id']) === intval($_id)) {
        	  unset($current_hosts[$_id]);
        	  break;
        	}
        }
      }
      if (count($current_hosts) < $base_items) {
        $this->options['api_hosts'] = $current_hosts;
        if (update_option($this->domain_name, $this->options)) {
          $notices_class = CDBT . '-notice';
          $message = __('Have deleted the specific allowed host.', CDBT);
        } else {
        	$message = __('Failed to delete the specified host.', CDBT);
        }
      } else {
        $message = __('Specified host does not exist.', CDBT);
      }
    }
    
    $this->register_admin_notices( $notices_class, $message, 3, true );
    wp_die('location.reload();');
    
  }
  
  
  /**
   * Retrieve api key via Ajax
   *
   * @since 2.0.0
   *
   * @param array $args [require]
   * @return void Output the JavaScript for callback on the frontend
   */
  public function ajax_event_get_apikey( $args=[] ) {
    static $api_key = '';
    
    if (array_key_exists('host_id', $args) && array_key_exists('operate_action', $args) && 'retrieve' === $args['operate_action']) {
      $current_hosts = $this->get_allowed_hosts($args['host_id']);
      if ($current_hosts) {
        $api_key = $current_hosts['api_key'];
      }
    }
    
    wp_die($api_key);
  }
  
  
  /**
   * Refresh for frontend page via shortcode
   *
   * @since 2.0.0
   *
   * @param array $args [require] Array of data for setting to session
   * @return string $callback Like a javascript function
   */
  public function ajax_event_reload_page( $args ) {
    
    $this->register_admin_notices( CDBT . '-notice', '', 1, true );
    wp_die('location.href="";');
    
  }
  
  
  
  
}
