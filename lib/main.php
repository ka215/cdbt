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
  var $query = [];

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
    
//var_dump($attributes);
    // Filters
    add_filter( 'body_class', array($this, 'add_body_classes') );
    add_filter( 'cdbt_dynamic_modal_options', array($this, 'insert_content_to_modal') ); // The content insertion via filter hook
    
    // Actions
    add_action( 'enqueue_scripts', array($this, 'cdbt_assets'), 99 ); // Note: priority = 99 is after the multibyte-patch plugin.
    add_action( 'cdbt_frontend_localize_script', array($this, 'cdbt_localize_script') );
    
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
        'cdbt-fuelux' => [ $this->plugin_url . 'assets/scripts/fuelux.js', array(), null, true ], 
      ]
    ];
    //
    // Filter the assets to be importing in admin panel (before registration)
    //
    $assets = apply_filters( 'cdbt_assets', $assets, $this->query['page'] );
    
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
//        'ajax_nonce' => wp_create_nonce($this->domain_name . '_' . $this->plugin_ajax_action), 
//        'get_text' => json_encode([ 'ID' => __('ID', CDBT), 'created' => __('created', CDBT), 'updated' => __('updated', CDBT) ]), 
      ]);
    }
  }


}

endif; // end of class_exists()