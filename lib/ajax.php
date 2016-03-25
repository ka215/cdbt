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
    
    wp_die( $modal_contents );
    
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
    
    wp_die( $nonce );
  }
  
  
  /**
   * Update plugin options via Ajax
   *
   * @since 2.0.9
   *
   * @param array $args [require]
   * @return void Output the JavaScript for callback on the frontend
   */
  public function ajax_event_update_options( $args=[] ) {
    
    if ( array_key_exists( 'hide_tutorial', $args ) ) {
      $_display_tutorial = $this->strtobool( $args['hide_tutorial'] );
      if ( $_display_tutorial ) {
        $this->options['hide_tutorial'] = CDBT_PLUGIN_VERSION;
      } else {
        if ( array_key_exists( 'hide_tutorial', $this->options ) ) 
          unset( $this->options['hide_tutorial'] );
      }
      if ( update_option( $this->domain_name, $this->options ) ) {
        wp_die( 'window.location.replace(window.location.href);' );
      }
    }
    wp_die();
    
  }
  
  
  /**
   * Retrieve the columns information of specific table via Ajax
   *
   * @since 2.0.9
   *
   * @param array $args [require]
   * @return void Output the JavaScript for callback on the frontend
   */
  public function ajax_event_get_columns_info( $args=[] ) {
    $render_html = '';
    
    if ( array_key_exists( 'table_name', $args ) ) {
      $columns_schema = $this->get_table_schema( $args['table_name'] );
      $columns_schema_index = is_array( $columns_schema ) ? array_keys( reset( $columns_schema ) ) : [];
      if ( empty( $columns_schema ) || empty( $columns_schema_index ) ) {
        wp_die( '<p class="col-sm-offset-2 text-warning">'. __('Specified table does not exist.', CDBT) .'</p>' );
      }
      foreach ( $columns_schema_index as $_i => $_val ) {
        if ( in_array( $_val, [ 'logical_name', 'octet_length', 'type' ] ) ) 
          unset( $columns_schema_index[$_i] );
      }
      $row_index_number = 1;
      $render_tmpl = <<<EOH
<div class="table-responsive">
  <p class="text-info" id="collapse-reference"><i class="fa fa-minus-square"></i> %s &nbsp; %s</p>
  <table id="columns-detail" class="table table-striped table-bordered table-hover table-condensed">
    <thead>
      <tr class="active">
        %s
      </tr>
    </thead>
    <tbody>
      %s
    </tbody>
    <tfoot>
      <tr><td colspan="%d" style="padding: 0;"></td></tr>
    </tfoot>
  </table>
</div>
EOH;
      $thead_th = $tbody = [];
      //$thead_th[] = '<th><small>#</small></th>';
      $thead_th[] = '<th><small>'. __('Column Name', CDBT) .'</small></th>';
      foreach ( $columns_schema_index as $columns_index_name ) {
        $thead_th[] = '<th class="text-center"><small>'. __($columns_index_name, CDBT) .'</small></th>';
      }
      foreach ( $columns_schema as $column_name => $column_scheme ) {
        $tbody_tr = '<tr id="row-index-number-'. $row_index_number .'">';
        $tbody_td = [];
        //$tbody_td[] = '<td><small>'. $row_index_number .'</small></td>';
        $tbody_td[] = '<td><small>'. $column_name .'</small></td>';
        foreach ( $columns_schema_index as $columns_index_name ) {
          if ( in_array( $columns_index_name, [ 'not_null', 'primary_key', 'unsigned' ] ) ) {
            $tbody_td[] = '<td class="text-center"><small>'. ( 1 === intval( $column_scheme[$columns_index_name] ) ? '<i class="fa fa-circle-thin text-center"></i>' : '' ) .'</small></td>';
          } else {
            $tbody_td[] = '<td><small>'. $column_scheme[$columns_index_name] .'</small></td>';
          }
        }
        $tbody_tr .= implode( "\n", $tbody_td );
        $tbody_tr .= '</tr>';
        $tbody[] = $tbody_tr;
        $row_index_number++;
      }
      $_label = sprintf( __('Reference: columns information of "%s" table', CDBT), $args['table_name'] );
      ob_start();
      $this->during_trial( 'reference_columns' );
      $_state = ob_get_contents();
      ob_end_clean();
      $render_html = sprintf( $render_tmpl, $_label, $_state, implode( "\n", $thead_th ), implode( "\n", $tbody ), count( $columns_schema_index ) + 2 );
    }
    wp_die( $render_html );
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
    wp_die( 'window.location.reload();' );
    
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
    wp_die( 'window.location.reload();' );
    
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
    
    wp_die( $args );
    
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
//    wp_die('location.reload();');
    
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
    wp_die( 'window.location.reload();' );
    
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
      wp_die( do_shortcode( stripslashes_deep($args['shortcode']) ) );
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
    wp_die( 'window.location.reload();' );
    
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
    wp_die( 'window.location.reload();' );
    
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
    $_run_script = 'window.location.reload();'; // 'location.href="";'
    wp_die( $_run_script );
    
  }
  
  
  
  
}
