<?php

namespace CustomDataBaseTables\Lib;


if ( !defined( 'CDBT' ) ) exit;

if ( !class_exists( 'CdbtFrontend' ) ) :


final class CdbtFrontend extends CdbtDB {

  /**
   * This message is going to emit to frontend
   *
   * @param string
   */
  var $emit_message;
  var $emit_type;

  /**
   * Protected menber for wrapping of wpdb object
   */
  protected $wpdb;

  /**
   * This member to cache the action performed immediately before
   *
   * @since 2.0.8
   */
  var $prev_action_cache;

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
      throw new \RuntimeException( sprintf( __('No method error "%s".', CDBT), $name ) );
    }
  }

  public function __get( $name ) {
    if ( property_exists($this->wpdb, $name) ) {
      return $this->wpdb->$name;
    } elseif ( property_exists($this, $name) ) {
      return $this->$name;
    } else {
      throw new \RuntimeException( sprintf( __('No property error "%s".', CDBT), $name ) );
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
  use CdbtApis;
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
    
    // Web API Initialize
    $this->init_allowed_hosts();
    
    // Emit Message Initialize
    $this->emit_message = '';
    $this->emit_type = 'error';
    
    // Action cache Initialize
    $this->prev_action_cache = [];
    
  }


  /**
   * Definition actions for the plugin
   *
   * @since 2.0.0
   */
  private function setup_actions() {
    
    // Include Extensions
    //$this->includes();
    add_action( 'init', array($this, 'includes'), 1 );
    
    // Before template redirection
    add_action( 'template_redirect', array($this, 'before_template_redirection') );
    
    // Initial Action
    add_action( 'init', array($this, 'frontend_initialize'), 2 );
    
  }


  /**
   * Include Extensions
   *
   * @since -
   */
  private function includes() {
    
    if (class_exists( $validator_class = __NAMESPACE__ . '\CdbtValidator')) 
      $this->validate = $validator_class::instance();
    
    if ( ! empty( $this->options['activated_addons'] ) ) {
      $this->addons = [];
      foreach ( $this->options['activated_addons'] as $addon_name => $addon_path ) {
        if ( class_exists( $addon_path ) ) 
          $this->addons[$addon_name] = new $addon_path();
      }
    }
    
  }


  /**
   * Initialize sessions and actions for shortcode
   *
   * @since 2.0.0
   * @since 2.0.7 Revision version
   */
  public function frontend_initialize() {
    
    if ( ! session_id() ) {
      if ( array_key_exists( 'prevent_duplicate_sending', $this->options ) && $this->options['prevent_duplicate_sending'] ) {
        session_set_cookie_params( 0, '/', $_SERVER['HTTP_HOST'] );
        
        /*
        // Issue a one-time token
        if ( isset( $_COOKIE[session_name()] ) ) {
          $_sid = $_COOKIE[session_name()];
          session_id( $_sid );
        }
        */
        if ( ! isset( $_COOKIE['_cdbt_token'] ) ) {
          $_cdbt_token = sha1( session_id() . microtime( true ) );
          setcookie( '_cdbt_token', $_cdbt_token, time() + 60 * 60 );
        }
        $this->cdbt_sessions = ! empty( $_SESSION ) ? $_SESSION : [];
      }
      
      session_start();
    }
    
    foreach ($this->shortcodes as $shortcode_name => $definitions) {
      add_action( "pre_shortcode_{$shortcode_name}", array($this, 'cdbt_pre_shortcode_render'), 10, 2 );
    }
    
  }


  /**
   * For updating a session
   *
   * @since 2.0.0
   * @revision 2.0.6
   *
   * @param string $session_key [optional] Update all sessions if session key does not specify
   */
  public function update_session( $session_key=null ) {
    
    if ( empty( $session_key ) ) {
      // global sessions
      $this->cdbt_sessions = array_merge($this->cdbt_sessions, array_diff($_SESSION, $this->cdbt_sessions));
    } else {
      // local page sessions
      $this->cdbt_sessions[$session_key] = $_SESSION;
      foreach ( $this->cdbt_sessions as $key => $value ) {
        if ( $session_key !== $key ) 
          unset( $this->cdbt_sessions[$key] );
      }
    }
    $_SESSION = [];
    
  }


  /**
   * Destroy a session
   *
   * @since 2.0.0
   * @revision 2.0.6
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
    add_action( 'wp_head', array($this, 'cdbt_header') );
    add_action( 'cdbt_frontend_localize_script', array($this, 'cdbt_localize_script') );
    
    // Filters
    add_filter( 'body_class', array($this, 'add_body_classes'), 99 );
    add_filter( 'cdbt_dynamic_modal_options', array($this, 'insert_content_to_modal') ); // The content insertion via filter hook
    //if ( isset( $attributes['enable_repeater'] ) && $this->strtobool( $attributes['enable_repeater'] ) ) { // For repeater only; future deprecated
      add_filter( 'cdbt_shortcode_custom_columns', array($this, 'string_type_custom_column_renderer'), 10, 3 );
    //}
    
    $this->action_controller();
  }
  
  
  /**
   * Define used assets for using shortcodes
   *
   * @since 2.0.0
   * @updated 2.0.4
   */
  public function cdbt_assets() {
    // Fire this hook when register CSS and JavaScript at using shortcode page
    
    // For conflict scripts avoidance
    if ( isset( $this->options['include_assets'] ) ) {
      if ( isset( $this->options['include_assets']['main_jquery'] ) && $this->options['include_assets']['main_jquery'] ) 
        wp_deregister_script( 'jquery' );
      if ( isset( $this->options['include_assets']['main_underscore_js'] ) && $this->options['include_assets']['main_underscore_js'] ) 
        wp_deregister_script( 'underscore' );
    } else {
      wp_deregister_script( 'jquery' );
      wp_deregister_script( 'underscore' );
    }
    $assets = [
      'styles' => [
        'cdbt-fuelux-style' => [ $this->plugin_url . 'assets/styles/fuelux.css', true, $this->contribute_extends['Fuel UX']['version'], 'all' ], 
        'cdbt-main-style' => [ $this->plugin_url . 'assets/styles/cdbt-main.css', [ 'cdbt-fuelux-style' ], $this->version, 'all' ], 
      ], 
      'scripts' => [
        'cdbt-jquery' => [ $this->plugin_url . 'assets/scripts/jquery.js', [], $this->contribute_extends['jQuery']['version'], false ], 
        'cdbt-underscore' => [ $this->plugin_url . 'assets/scripts/underscore.js', [ 'cdbt-jquery' ], $this->contribute_extends['Underscore.js']['version'], true ], 
        'cdbt-bootstrap' => [ $this->plugin_url . 'assets/scripts/bootstrap.js', [ 'cdbt-jquery' ], $this->contribute_extends['Bootstrap']['version'], true ], 
        'cdbt-kinetic' => [ $this->plugin_url . 'assets/scripts/jquery.kinetic.js', [ 'cdbt-jquery' ], $this->contribute_extends['Kinetic']['version'], true ], 
        'cdbt-clipboard' => [ $this->plugin_url . 'assets/scripts/clipboard.js', [ 'cdbt-jquery' ], $this->contribute_extends['Clipboard']['version'], true ], 
        'cdbt-fuelux-script' => [ $this->plugin_url . 'assets/scripts/fuelux.js', [ 'cdbt-bootstrap' ], $this->contribute_extends['Fuel UX']['version'], true ], 
        'cdbt-main-script' => [ $this->plugin_url . 'assets/scripts/cdbt-main.js', [ 'cdbt-underscore' ], $this->version, true ], 
      ]
    ];
    // Override from the option of `include_assets`
    if ( isset( $this->options['include_assets'] ) ) {
      if ( isset( $this->options['include_assets']['main_jquery'] ) && ! $this->options['include_assets']['main_jquery'] ) {
        unset( $assets['scripts']['cdbt-jquery'] );
        $assets['scripts']['jquery'] = null;
        $assets['scripts']['cdbt-underscore'][1] = [ 'jquery' ];
        $assets['scripts']['cdbt-bootstrap'][1] = [ 'jquery' ];
        //$assets['scripts']['cdbt-fuelux-script'][3] = false;
      }
      if ( isset( $this->options['include_assets']['main_underscore_js'] ) && ! $this->options['include_assets']['main_underscore_js'] ) {
        unset( $assets['scripts']['cdbt-underscore'] );
        $assets['scripts']['underscore'] = null;
        $assets['scripts']['cdbt-main-script'][1] = [ 'underscore' ];
      }
      if ( isset( $this->options['include_assets']['main_bootstrap'] ) && ! $this->options['include_assets']['main_bootstrap'] ) {
        unset( $assets['scripts']['cdbt-bootstrap'] );
        $assets['scripts']['cdbt-fuelux-script'][1] = [];
      }
      if ( isset( $this->options['include_assets']['main_kinetic'] ) && ! $this->options['include_assets']['main_kinetic'] ) {
        unset( $assets['scripts']['cdbt-kinetic'] );
      }
      if ( isset( $this->options['include_assets']['main_clipboard'] ) && ! $this->options['include_assets']['main_clipboard'] ) {
        unset( $assets['scripts']['cdbt-clipboard'] );
      }
      if ( isset( $this->options['include_assets']['main_fuel_ux'] ) && ! $this->options['include_assets']['main_fuel_ux'] ) {
        unset( $assets['styles']['cdbt-fuelux-style'] );
        unset( $assets['scripts']['cdbt-fuelux-script'] );
        $assets['styles']['cdbt-main-style'][1] = [];
      }
    }
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
        'emit_message' => $this->emit_message, 
        'emit_type' => $this->emit_type, 
        'local_err_msg' => rawurlencode( __( 'An empty required field is exists.', CDBT ) ), 
      ]);
    }
  }
  
  
  /**
   * Fire this hook when append into <head> tag on the front-end for this plugin
   *
   * @since 2.0.10
   */
  public function cdbt_header(){
    if ( ! $this->options['include_assets']['main_jquery'] ) {
      echo "<script>if (typeof jQuery !== 'undefined' ) { var $ = jQuery; }</script>\n";
    }
    
    // Added action hook for using `add_action('cdbt_header')`
    // 
    // @since 2.0.10
    do_action( 'cdbt_header' );
  }
  
  /**
   * Controllers of frontend actions for this plugin
   *
   * @since 2.0.0
   */
  public function action_controller() {
    if ( empty( $_POST ) ) {
      $this->destroy_session();
      return;
    }
    
    if ( isset( $_POST['_wpnonce'] ) && isset( $_POST['table'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'cdbt_entry_data-' . $_POST['table'] ) ) {
      $worker_method = sprintf('do_%s', $_POST['action']);
      if ( method_exists( $this, $worker_method ) ) {
        $_session_key = str_replace('_', '-', $worker_method .'-'. $_POST['table']);
        $_SESSION = array_merge( $_SESSION, array_map( 'stripslashes_deep', $_POST ) );
        $this->update_session( $_session_key );
        if ( isset( $this->prev_action_cache ) && isset($_COOKIE['once_action']) && $this->prev_action_cache !== $_COOKIE['once_action'] ) {
          $this->$worker_method();
        } else {
          return;
        }
        $this->prev_action_cache = $_COOKIE['once_action'];
      } else {
        // invalid access
        $this->destroy_session( $worker_method );
        $this->emit_message = __('Unauthorized Access to this page.', CDBT);
      }
    } else {
      // invalid access
      $this->destroy_session();
      $this->emit_message = __('Unauthorized Access to this page.', CDBT);
    }
    
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
      $message = __('Unauthorized Access.', CDBT);
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
      return;
    }
    
    $post_data = array_map( 'stripslashes_deep', $_POST[$this->domain_name] );
    
    $table_name = $_POST['table'];
    $register_data = $this->cleanup_data( $table_name, $post_data );
    $where_clause = unserialize(stripslashes_deep($_POST['where_clause']));
    if ($this->update_data( $table_name, $register_data, $where_clause )) {
      $message = __('Data updating are completed successfully.', CDBT);
      $this->emit_type = 'notice';
    } else {
      $message = sprintf(__('Failed to update data of of "%s" table.', CDBT), $table_name);
      $message .= "\n". __('In the case of no change of between before and after, data does not updated.', CDBT);
      $message .= "\n". __('It might not have updated because there is the record which has same data.', CDBT);
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
    $_POST[$this->domain_name] = 'deletion'; // For page authentication dummy
    
    // Access authentication process to the page
    $message = $this->access_page_authentication( [ 'delete_data' ] );
    if (!empty($message)) {
      $this->emit_message = $message;
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
          $message = __('Removed successfully the specified data.', CDBT);
          $this->emit_type = 'notice';
        } else {
          $message = __('Can not remove some of the data.', CDBT);
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
  
  
  /**
   * Insert data via shortcode `cdbt-entry` from the frontend
   *
   * @since 2.0.0
   * @since 2.0.7 Revision version
   */
  public function do_entry_data() {
    static $message = '';
    
    // Access authentication process to the page
    $message = $this->access_page_authentication( [ 'entry_data' ] );
    if (!empty($message)) {
      $this->emit_message = $message;
      return;
    }
    
    $table_name = $_POST['table'];
    $post_data = $_POST[$this->domain_name];
    $register_data = $this->cleanup_data( $table_name, $post_data );
    
    if ( array_key_exists( 'prevent_duplicate_sending', $this->options ) && $this->options['prevent_duplicate_sending'] ) {
      if ( isset( $_COOKIE['_cdbt_token'] ) && ! empty( $_POST['_cdbt_token'] ) && $_COOKIE['_cdbt_token'] === $_POST['_cdbt_token'] ) {
        if ($this->insert_data( $table_name, $register_data )) {
          $message = sprintf( __( 'Your entry data has been successfully registered to "%s" table.', CDBT ), $table_name );
          $this->emit_type = 'notice';
          setcookie( '_cdbt_token', '' );
        } else {
          $message = sprintf( __( 'Failed to insert data to "%s" table.', CDBT ), $table_name );
          $this->cdbt_sessions[__FUNCTION__][$this->domain_name] = $post_data;
        }
      } else {
        $message = __( 'Could not multiple registration by the continuous transmission. So you reload this entry page, please try to refresh the token.', CDBT );
      }
    } else {
      if ($this->insert_data( $table_name, $register_data )) {
        $message = sprintf( __( 'Your entry data has been successfully registered to "%s" table.', CDBT ), $table_name );
        $this->emit_type = 'notice';
      } else {
        $message = sprintf( __( 'Failed to insert data to "%s" table.', CDBT ), $table_name );
        $this->cdbt_sessions[__FUNCTION__][$this->domain_name] = $post_data;
      }
    }
    unset( $post_data, $register_data );
    
    if ( ! empty( $message ) ) {
      $this->emit_message = $message;
    }
    
    return;
    
  }


}

endif; // end of class_exists()