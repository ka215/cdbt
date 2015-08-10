<?php

namespace CustomDataBaseTables\Lib;


if ( !defined( 'CDBT' ) ) exit;

if ( !class_exists( 'CdbtFrontend' ) ) :


final class CdbtFrontend extends CdbtDB {

  /**
   * Member is stored current queries
   *
   * @param array
   */
//  var $query = [];

  /**
   * This message is going to emit to frontend
   *
   * @param string
   */
  var $emit_message;

  /**
   * Protected menber for wrapping of wpdb object
   */
  protected $wpdb;

  /**
   * Instance factory method as entry point of plugin.
   *
   * @since 2.0.0
   */
  public static function instance() {
    
    static $instance = null;
    
    if ( null === $instance ) {
      $instance = new self;
      $instance->setup_globals();
      $instance->init();
      $instance->setup_actions();
    }
    
    return $instance;
  }

 /**
  * Define magic methods as follow;
  */
  public function __construct() { /* Do nothing here */ }

  public function __destruct() { /* Do nothing here */ }

  public function __call( $name, $args=null ) {
    if ( method_exists($this->wpdb, $name) ) {
      return $this->wpdb->$name($args);
    } elseif ( method_exists($this, $name) ) {
      return $this->$name($args);
    } elseif ( is_callable($this->$name) ) {
      return call_user_func($this->$name, $args);
    } else {
      throw new \RuntimeException( sprintf( __('Method "%s" does not exist.', CDBT), $name ) );
    }
  }

  public function __get( $name ) {
    if ( property_exists($this->wpdb, $name) ) {
      return $this->wpdb->$name;
    } elseif ( property_exists($this, $name) ) {
      return $this->$name;
    } else {
      throw new \RuntimeException( sprintf( __('Property "%s" does not exist.', CDBT), $name ) );
    }
  }

  public function __set( $name, $value ) {
    $protected_members = [
      'wpdb', 
    ];
    if ( in_array($name, $protected_members, true) ) 
      return;
    
    if (is_callable($value)) {
      // Whether closure is or
      $ref_func = new \ReflectionFunction($value);
      $this->$name = $ref_func->isClosure() ? $value->bindTo($this, $this) : $value;
    } else {
      $this->$name = $value;
    }
  }


  /**
   * Imported traits.
   * (Required php version 5.4 more)
   *
   * @since 2.0.0
   */
  use CdbtAjax;
  use DynamicTemplate;
  use CdbtShortcodes;
  use CdbtExtras;

  /**
   * Wrapping global object of wordpress
   *
   * @since 2.0.0
   */
  private function setup_globals() {
    
    global $wpdb;
    $this->wpdb = $wpdb;
    
  }


  /**
   * Initialization for the plugin
   *
   * @since 2.0.0
   */
  private function init() {
    
    // Plugin Core Initialize
    $this->core_init();
    $this->core_actions();
    
    // Paths
    $this->admin_template_dir = apply_filters( 'cdbt_admin_template_dir', $this->plugin_dir . 'templates/admin/' );
    
    // Plugin Options Initialize
    $this->options_init();
    if ($this->options['debug_mode']) 
      $this->debug = true;
    
    // DataBase Initialize
    $this->db_init();
    
    // Ajax Initialize
    $this->ajax_init();
    
    // Shortcode Initialize
    $this->shortcode_register();
    
    // Emit Message Initialize
    $this->emit_message = '';
    
  }


  /**
   * Definition actions for the plugin
   *
   * @since 2.0.0
   */
  private function setup_actions() {
    
    // Include Extensions
    $this->includes();
    
    // Before template redirection
    add_action( 'template_redirect', array($this, 'before_template_redirection') );
    
    // Initial Action
    add_action( 'init', array($this, 'frontend_initialize') );
    
  }


  /**
   * Include Extensions
   *
   * @since -
   */
  private function includes() {
    
    if (class_exists( $validator_class = __NAMESPACE__ . '\CdbtValidator')) 
      $this->validate = $validator_class::instance();
    
  }


  /**
   * Initialize sessions and actions for shortcode
   *
   * @since 2.0.0
   */
  public function frontend_initialize() {
    
    $this->cdbt_sessions = $_SESSION;
    
    foreach ($this->shortcodes as $shortcode_name => $definitions) {
      add_action( "pre_shortcode_{$shortcode_name}", array($this, 'cdbt_pre_shortcode_render'), 10, 2 );
    }
    
  }


  /**
   * For updating a session
   *
   * @since 2.0.0
   *
   * @param string $session_key [optional] Update all sessions if session key does not specify
   */
  public function update_session( $session_key=null ) {
    
    if (empty($session_key)) {
      // global sessions
      $this->cdbt_sessions = array_merge($this->cdbt_sessions, array_diff($_SESSION, $this->cdbt_sessions));
    } else {
      // local page sessions
      $this->cdbt_sessions[$session_key] = $_SESSION;
      foreach ($this->cdbt_sessions as $key => $value) {
        if ($session_key !== $key) 
          unset($this->cdbt_sessions[$key]);
      }
    }
    $_SESSION = [];
    
  }


  /**
   * Destroy a session
   *
   * @since 2.0.0
   *
   * @param string $session_key [optional] Destroy all sessions if session key does not specify
   */
  public function destroy_session( $session_key=null ) {
    
    if (empty($session_key)) {
      // global sessions
      $this->cdbt_sessions = [];
      $_SESSION = [];
      session_write_close();
    } else {
      // local page (or tab) sessions
      if (array_key_exists($session_key, $this->cdbt_sessions)) 
        unset($this->cdbt_sessions[$session_key]);
      
      if (array_key_exists($session_key, $_SESSION)) 
        unset($_SESSION[$session_key]);
      
    }
    
  }


  /**
   * Find of using shortcode in singular page
   *
   * @since 2.0.0
   */
  public function before_template_redirection() {
    
	if (is_singular()) {
      global $post;
      if (false !== strpos($post->post_content, '[')) {
        $pattern = get_shortcode_regex();
        preg_replace_callback( "/$pattern/s", array($this, 'pre_shortcode_tag_callback'), $post->post_content );
      }
    }
    
  }
  
  
  /**
   * Add processing before rendering the page that contains the shortcode
   *
   * @since 2.0.0
   *
   * @param array $matches [require]
   */
  public function pre_shortcode_tag_callback( $matches=[] ) {
    
	if (($matches[1] === '[' && $matches[6] === ']')) 
      return;
    
	$shortcode_name = $matches[2];
    
	$attributes = shortcode_parse_atts( $matches[3] );
    
	$content = '';
	if (isset($matches[5])) 
	  $content = $matches[5];
	
	// Fire before rendering the page that contains the shortcode
	//
	// @since 2.0.0
	do_action( "pre_shortcode_{$shortcode_name}", $attributes, $content );
	
  }
  
  
  /**
   * Various preparatory process for rendering a shortcode
   *
   * @since 2.0.0
   *
   * @param array $attributes Array of attribute values of the shortcode
   * @param string $content Nested in the shortcode content string
   */
  public function cdbt_pre_shortcode_render( $attributes=[], $content=null ) {
    
    // Actions
    add_action( 'wp_enqueue_scripts', array($this, 'cdbt_assets'), 99 ); // Note: priority = 99 is after the multibyte-patch plugin.
    add_action( 'cdbt_frontend_localize_script', array($this, 'cdbt_localize_script') );
    
    // Filters
    add_filter( 'body_class', array($this, 'add_body_classes') );
    add_filter( 'cdbt_dynamic_modal_options', array($this, 'insert_content_to_modal') ); // The content insertion via filter hook
    
    $this->action_controller();
  }
  
  
  /**
   * Define used assets for using shortcodes
   *
   * @since 2.0.0
   */
  public function cdbt_assets() {
    // Fire this hook when register CSS and JavaScript at using shortcode page
    
    $assets = [
      'styles' => [
        'cdbt-main-style' => [ $this->plugin_url . 'assets/styles/cdbt-main.css', array(), $this->version, 'all' ], 
        'cdbt-fuelux' => [ $this->plugin_url . 'assets/styles/fuelux.css', true, null, 'all' ], 
      ], 
      'scripts' => [
        'cdbt-modernizr' => [ $this->plugin_url . 'assets/scripts/modernizr.js', array(), null, true ], 
        'cdbt-jquery' => [ $this->plugin_url . 'assets/scripts/jquery.js', array(), null, true ], 
        'cdbt-underscore' => [ $this->plugin_url . 'assets/scripts/underscore.js', array(), null, true ], 
        'cdbt-main-script' => [ $this->plugin_url . 'assets/scripts/cdbt-main.js', array(), null, true ], 
//        'cdbt-fuelux' => [ $this->plugin_url . 'assets/scripts/fuelux.js', array(), null, true ], 
      ]
    ];
    //
    // Filter the assets to be importing in admin panel (before registration)
    //
    $assets = apply_filters( 'cdbt_assets', $assets );
    
    foreach ($assets as $asset_type => $asset_data) {
      if ('styles' === $asset_type) {
        foreach ($asset_data as $asset_name => $asset_values) {
          wp_enqueue_style( $asset_name, $asset_values[0], $asset_values[1], $asset_values[2], $asset_values[3] );
        }
      }
      if ('scripts' === $asset_type) {
        foreach ($asset_data as $asset_name => $asset_values) {
          if (!empty($asset_values)) 
            wp_register_script( $asset_name, $asset_values[0], $asset_values[1], $asset_values[2], $asset_values[3] );
          
          wp_enqueue_script( $asset_name );
          
        }
        // Fire after execution of `wp_enqueue_script()`
        // Action for passing a variable to javascript
        // 
        do_action( 'cdbt_frontend_localize_script', $asset_data );
        
      }
    }
  }
  
  
  /**
   * Fire after execution of `wp_enqueue_script()` for passing a variable to javascript
   *
   * @since 2.0.0
   */
  public function cdbt_localize_script( $asset_data ) {
    if ( array_key_exists( 'cdbt-main-script', $asset_data ) ) {
      wp_localize_script( 'cdbt-main-script', 'cdbt_main_vars', [
        'is_debug' => $this->debug ? 'true' : 'false', 
        'ajax_url' => $this->ajax_url( [ 'event' => 'setup_session' ] ), 
//        'nonce' => wp_create_nonce($this->domain_name . '_' . 'shortcode-actions'), 
        'emit_message' => $this->emit_message, 
      ]);
    }
  }
  
  
  /**
   * Controllers of frontend actions for this plugin
   *
   * @since 2.0.0
   */
  public function action_controller() {
    if (empty( $_POST )) {
      $this->destroy_session();
      return;
    }
    
//var_dump([$_POST, $_SESSION]);
    
    if (wp_verify_nonce( $_POST['_wpnonce'], 'cdbt_entry_data-' . $_POST['table'] )) {
      $worker_method = sprintf('do_%s', $_POST['action']);
      if (method_exists($this, $worker_method)) {
        $_session_key = str_replace('_', '-', $worker_method .'-'. $_POST['table']);
        $_SESSION = array_map( 'stripslashes_deep', $_POST );
        $this->update_session( $_session_key );
        $this->$worker_method();
      } else {
        // invalid access
        $this->destroy_session( $worker_method );
        $this->emit_message = __('Invalid access this page. method is none.', CDBT);
        //$this->register_admin_notices( CDBT . '-error', __('Invalid access this page.', CDBT), 3, true );
      }
    } else {
      // invalid access
      $this->destroy_session();
      $this->emit_message = __('Invalid access this page.', CDBT);
      //$this->register_admin_notices( CDBT . '-error', __('Invalid access this page.', CDBT), 3, true );
    }
    //$this->admin_notices();
    
  }
  
  
  
  
  /**
   * Worker logic methods
   * -------------------------------------------------------------------------
   */

  /**
   * Common access authentication process for the plugin management console pages
   *
   * @since 2.0.0
   *
   * @param array $allow_actions [require] Array of action names that are allowed by the current management page
   * @return string $message Null is returned in case of authentication success
   */
  private function access_page_authentication( $allow_actions ) {
    static $message = null;
    
    if (!in_array($_POST['action'], $allow_actions) || empty($_POST[$this->domain_name]) ) {
      $message = __('Illegal access is.', CDBT);
    }
    
    return $message;
    
  }


  /**
   * Update data via shortcode `cdbt-edit` from the frontend
   *
   * @since 2.0.0
   */
  public function do_edit_data() {
    static $message = '';
    
    // Access authentication process to the page
    $message = $this->access_page_authentication( [ 'edit_data' ] );
    if (!empty($message)) {
      $this->emit_message = $message;
      //$this->register_admin_notices( CDBT . '-error', $message, 3, true );
      return;
    }
    
    $post_data = array_map( 'stripslashes_deep', $_POST[$this->domain_name] );
    
//var_dump($post_data);
    $table_name = $_POST['table'];
    $register_data = $this->cleanup_data( $table_name, $post_data );
    $where_clause = unserialize(stripslashes_deep($_POST['where_clause']));
    if ($this->update_data( $table_name, $register_data, $where_clause )) {
      $notice_class = CDBT . '-notice';
      $message = __('Update of the data has been completed successfully.', CDBT);
    } else {
      $message = sprintf(__('Could not update data of "%s" table.', CDBT), $table_name);
      $message .= "\n". __('Not done updating of data if there is no change to the data in updating before and after.', CDBT);
      $message .= "\n". __('Or, it is possible that the record having the same data could not be updated in order that existed in the other.', CDBT);
    }
    
    if (!empty($message)) {
      $this->emit_message = $message;
      $this->destroy_session();
    }
    return;
    
  }
  
  
  /**
   * Remove data via shortcode `cdbt-edit` from the frontend
   *
   * @since 2.0.0
   */
  public function do_delete_data() {
    static $message = '';
    $_POST[$this->domain_name] = 'deletion';
    
    // Access authentication process to the page
    $message = $this->access_page_authentication( [ 'delete_data' ] );
    if (!empty($message)) {
      $this->emit_message = $message;
      //$this->register_admin_notices( CDBT . '-error', $message, 3, true );
      return;
    }
    
    $post_data = array_map( 'stripslashes_deep', $_POST );
    if (array_key_exists('table', $post_data) && array_key_exists('where_conditions', $post_data)) {
      $_where_conditions = $this->strtoarray($post_data['where_conditions']);
      if (is_array($_where_conditions) && !empty($_where_conditions)) {
        $deleted_data = 0;
        foreach ($_where_conditions as $_where) {
          if ($this->delete_data( $post_data['table'], $_where )) {
            $deleted_data++;
          }
        }
        if ($deleted_data === count($_where_conditions)) {
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
    
    if (!empty($message)) {
      $this->emit_message = $message;
      $this->destroy_session();
    }
    return;
    
  }


}

endif; // end of class_exists()