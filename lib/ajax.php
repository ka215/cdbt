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
   * @since 2.0.0
   **/
  public function ajax_handler() {
    if (!isset($GLOBALS['_REQUEST']['_wpnonce'])) 
      $this->ajax_error();
    
    if (!wp_verify_nonce( $GLOBALS['_REQUEST']['_wpnonce'], $this->domain_name . '_' . $this->plugin_ajax_action )) {
      if (isset($_REQUEST['api_key']) && !empty($_REQUEST['api_key'])) {
        // verify api key
        
      } else {
        $this->ajax_error();
      }
    }
    
    if (!isset($GLOBALS['_REQUEST']['event'])) 
      $this->ajax_error();
    
    $event_method = 'ajax_event_' . rtrim($GLOBALS['_REQUEST']['event']);
    
    if (!method_exists($this, $event_method)) 
      $this->ajax_error();
    
    $this->$event_method( $GLOBALS['_REQUEST'] );
    
  }


  /**
   * 
   *
   * @since 2.0.0
   **/
  public function ajax_error() {
    
    die( 'ERROR!' );
    
  }


  /**
   * Ajax events
   * -------------------------------------------------------------------------
  
  /**
   *
   *
   * @since 2.0.0
   */
  public function ajax_event_update_target_table( $args ) {
    
var_dump($args['target_table']);
    if (isset($args['target_table'])) 
      $_SESSION['target_table'] = $args['target_table'];
    
    if (isset($args['callback_url'])) 
      die( sprintf(";;location.href = '%s';", $args['callback_url']) );
    
    $this->ajax_error();
  }




}
