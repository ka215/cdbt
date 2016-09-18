<?php

namespace CustomDataBaseTables\Lib;


if ( !defined( 'CDBT' ) ) exit;

if ( !class_exists( 'CdbtAdmin' ) ) :

final class CdbtAdmin extends CdbtDB {

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
   * Menber of current target table name for manageable
   */
  //var $target_table;

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
   * Initialization for the plugin management console
   *
   * @since 2.0.0
   */
  private function init() {
    
    // Plugin Core Initialize
    $this->core_init();
    $this->core_actions();
    
    // Capabilities
    $this->minimum_capability = 'edit_posts'; // -> Contributor
    $this->webmaster_capability = 'edit_pages'; // -> Editor
    $this->maximum_capability = 'activate_plugins'; // -> Administrator, and Super Admin
    $this->operate_capability = 'cdbt_operate_plugin';
    
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
    
  }


  /**
   * Definition actions for the plugin management console
   *
   * @since 2.0.0
   */
  private function setup_actions() {
    
    // Include Extensions
    //$this->includes();
    add_action( 'admin_init', array($this, 'includes'), 1 );
    
    // Initial Action
    add_action( 'admin_init', array($this, 'admin_initialize'), 2 );
    
    // General Actions
    if (!empty($GLOBALS['pagenow']) && 'plugins.php' === $GLOBALS['pagenow'] ) 
      add_action( 'admin_notices', array($this, 'check_plugin_notices'));
    
    add_action( 'admin_menu', array($this, 'admin_menus') );
    
    // Add New Actions
    do_action( 'cdbt_get_admin_template', array($this, 'get_admin_template') );
    
    // Filters
    add_filter( 'plugin_action_links', array($this, 'modify_plugin_action_links'), 10, 2 );
    add_filter( 'admin_body_class', array($this, 'add_body_classes'), 99 );
    add_filter( 'cdbt_dynamic_modal_options', array($this, 'insert_content_to_modal') ); // The content insertion via filter hook
    
    add_filter( 'cdbt_shortcode_custom_columns', array($this, 'string_type_custom_column_renderer'), 10, 3 ); // Future deprecated
    
  }


  /**
   * Include Extensions
   *
   * @since 2.1.33 Added loading addons
   */
  private function includes() {
    
    if ( class_exists( $validator_class = __NAMESPACE__ . '\CdbtValidator') ) 
      $this->validate = $validator_class::instance();
    
    if ( ! empty( $this->options['activated_addons'] ) ) {
      //$this->addons = [];
      foreach ( $this->options['activated_addons'] as $addon_name => $addon_path ) {
        if ( class_exists( $addon_path ) ) 
          $this->addons[$addon_name] = new $addon_path();
      }
    }
    
  }


  /**
   * Initialize for inserting plugin option settings into admin panel of wordpress
   * And initialize sessions
   *
   * @since 2.0.0
   */
  public function admin_initialize() {
    
    register_setting( 'cdbt_management_console', $this->domain_name );
    
    if ( ! session_id() ) 
      session_start();
    
    $this->cdbt_sessions = $_SESSION;
    
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
   * Define plugin option settings menu
   *
   * @since 2.0.0
   */
  public function admin_menus() {
    $operating_capability = apply_filters( 'cdbt_operating_capability', $this->maximum_capability );
    // Filters the slug of top level menu on this plugin (for add-on extension)
    //
    // @since 2.1.34
    $cdbt_top_level_menu = apply_filters( 'cdbt_top_level_menu', 'cdbt_management_console' );
    
    $menus = [];
    if ( array_key_exists( 'plugin_menu_position', $this->options ) ) {
      $_menu_position = $this->options['plugin_menu_position'];
    } else {
      $_menu_position = 'bottom';
    }
    
    $menus[] = add_menu_page( 
      __('CDBT Management Console', $this->domain_name), 
      __('CDBT', $this->domain_name), 
      $operating_capability, 
      $cdbt_top_level_menu, 
      array($this, 'admin_page_render'), 
      'dashicons-admin-generic', 
      $this->admin_menu_position( $_menu_position )
    );
    
    $menus[] = add_submenu_page( 
      $cdbt_top_level_menu, 
      __('CDBT Tables Management', $this->domain_name), 
      __('Tables', $this->domain_name), 
      $operating_capability, 
      'cdbt_tables', 
      array($this, 'admin_page_render') 
    );
    
    $menus[] = add_submenu_page( 
      $cdbt_top_level_menu, 
      __('CDBT Shortcodes Management', $this->domain_name), 
      __('Shortcodes', $this->domain_name), 
      $operating_capability, 
      'cdbt_shortcodes', 
      array($this, 'admin_page_render') 
    );
    
    $menus[] = add_submenu_page( 
      $cdbt_top_level_menu, 
      __('CDBT WEB APIs Management', $this->domain_name), 
      __('Web APIs', $this->domain_name), 
      $operating_capability, 
      'cdbt_web_apis', 
      array($this, 'admin_page_render') 
    );
    
    $menus[] = add_submenu_page( 
      $cdbt_top_level_menu, 
      __('CDBT Plugin Options', $this->domain_name), 
      __('Plugin Options', $this->domain_name), 
      $operating_capability, 
      'cdbt_options', 
      array($this, 'admin_page_render') 
    );
    
    // Parsed QUERY_STRING is stored $this->query
    wp_parse_str( $_SERVER['QUERY_STRING'], $this->query );
    
    // Filter to extend the plugin option menus
    //
    // @since 2.1.34
    $menus = apply_filters( 'cdbt_admin_menus', $menus, $cdbt_top_level_menu, $this->query );
    
    foreach ($menus as $menu) {
      add_action( 'admin_enqueue_scripts', array($this, 'admin_assets'), 99 ); // Note: priority = 99 is after the multibyte-patch plugin.
      add_filter( 'cdbt_admin_assets', array($this, 'enqueue_jquery_ui'), 10, 2 );
      add_action( 'cdbt_admin_localize_script', array($this, 'admin_localize_script') );
      add_action( "admin_head-$menu", array($this, 'admin_header') );
      add_action( "admin_footer-$menu", array($this, 'admin_footer') );
      add_action( 'admin_footer', array($this, 'admin_footer') );
      add_action( 'admin_notices', array($this, 'admin_notices') );
    }
  }


  /**
   * Render page after load the page templates using closure
   *
   * @since 2.0.0
   */
  public function admin_page_render() {
    // render the admin pages defined at `admin_menus()`
    if (isset($this->query['page']) && !empty($this->query['page'])) {
      
      $template_file_path = sprintf('%s%s.php', $this->admin_template_dir, $this->query['page']);
      
      if (file_exists($template_file_path)) {
        $this->admin_controller();
        
        // Deprecated old process
        // require_once( apply_filters( 'include_template-' . $this->query['page'], $template_file_path ) );
        
        $page_render_method = 'render_' . $this->query['page'];
        $this->set_template_file_path( apply_filters( 'include_template-' . $this->query['page'], $template_file_path ) );
        // Define Dynamic Closure
        $this->$page_render_method = function(){ require( $this->template_file_path ); };
        $this->$page_render_method();
        
      }
    }
    
  }


  /**
   * Define used assets at admin panel and register
   *
   * @since 2.0.0
   * @since 2.0.4 Updated
   * @since 2.0.7 Changed assets including
   */
  public function admin_assets() {
    // Fire this hook when register CSS and JavaScript to admin panel (on the all admin page)
    if (!array_key_exists('page', $this->query) || !preg_match('/^cdbt_.*$/iU', $this->query['page'])) 
      return;
    
    // For conflict scripts avoidance
    if ( isset( $this->options['include_assets'] ) ) {
      if ( isset( $this->options['include_assets']['admin_jquery'] ) && $this->options['include_assets']['admin_jquery'] ) 
        wp_deregister_script( 'jquery' );
      if ( isset( $this->options['include_assets']['admin_underscore_js'] ) && $this->options['include_assets']['admin_underscore_js'] ) 
        wp_deregister_script( 'underscore' );
    } else {
      wp_deregister_script( 'jquery' );
      wp_deregister_script( 'underscore' );
    }
    $assets = [
      'styles' => [
        'cdbt-fuelux-style' => [ $this->plugin_url . 'assets/styles/fuelux.css', true, $this->contribute_extends['Fuel UX']['version'], 'all' ], 
        'cdbt-admin-style' => [ $this->plugin_url . 'assets/styles/cdbt-admin.css', [ 'cdbt-fuelux-style' ], $this->version, 'all' ], 
      ], 
      'scripts' => [
        'cdbt-jquery' => [ $this->plugin_url . 'assets/scripts/jquery.js', [], $this->contribute_extends['jQuery']['version'], false ], 
        'cdbt-underscore' => [ $this->plugin_url . 'assets/scripts/underscore.js', [ 'cdbt-jquery' ], $this->contribute_extends['Underscore.js']['version'], true ], 
        'cdbt-bootstrap' => [ $this->plugin_url . 'assets/scripts/bootstrap.js', [ 'cdbt-jquery' ], $this->contribute_extends['Bootstrap']['version'], true ], 
        'cdbt-kinetic' => [ $this->plugin_url . 'assets/scripts/jquery.kinetic.js', [ 'cdbt-jquery' ], $this->contribute_extends['Kinetic']['version'], true ], 
        'cdbt-clipboard' => [ $this->plugin_url . 'assets/scripts/clipboard.js', [ 'cdbt-jquery' ], $this->contribute_extends['Clipboard']['version'], true ], 
        'cdbt-fuelux-script' => [ $this->plugin_url . 'assets/scripts/fuelux.js', [ 'cdbt-bootstrap' ], $this->contribute_extends['Fuel UX']['version'], true ], 
        'cdbt-admin-script' => [ $this->plugin_url . 'assets/scripts/cdbt-admin.js', [ 'cdbt-underscore' ], $this->version, true ], 
      ]
    ];
    // Override from the option of `include_assets`
    if ( isset( $this->options['include_assets'] ) ) {
      if ( isset( $this->options['include_assets']['admin_jquery'] ) && ! $this->options['include_assets']['admin_jquery'] ) {
        unset( $assets['scripts']['cdbt-jquery'] );
        $assets['scripts']['jquery'] = null;
        $assets['scripts']['cdbt-underscore'][1] = [ 'jquery' ];
        $assets['scripts']['cdbt-bootstrap'][1] = [ 'jquery' ];
        //$assets['scripts']['cdbt-fuelux-script'][3] = false;
      }
      if ( isset( $this->options['include_assets']['admin_underscore_js'] ) && ! $this->options['include_assets']['admin_underscore_js'] ) {
        unset( $assets['scripts']['cdbt-underscore'] );
        $assets['scripts']['underscore'] = null;
        $assets['scripts']['cdbt-main-script'][1] = [ 'underscore' ];
      }
      if ( isset( $this->options['include_assets']['admin_bootstrap'] ) && ! $this->options['include_assets']['admin_bootstrap'] ) {
        unset( $assets['scripts']['cdbt-bootstrap'] );
        $assets['scripts']['cdbt-fuelux-script'][1] = [];
      }
      if ( isset( $this->options['include_assets']['admin_kinetic'] ) && ! $this->options['include_assets']['admin_kinetic'] ) {
        unset( $assets['scripts']['cdbt-kinetic'] );
      }
      if ( isset( $this->options['include_assets']['admin_clipboard'] ) && ! $this->options['include_assets']['admin_clipboard'] ) {
        unset( $assets['scripts']['cdbt-clipboard'] );
      }
      if ( isset( $this->options['include_assets']['admin_fuel_ux'] ) && ! $this->options['include_assets']['admin_fuel_ux'] ) {
        unset( $assets['styles']['cdbt-fuelux-style'] );
        unset( $assets['scripts']['cdbt-fuelux-script'] );
        $assets['styles']['cdbt-main-style'][1] = [];
      }
    }
    //
    // Filter the assets to be importing in admin panel (before registration)
    //
    $assets = apply_filters( 'cdbt_admin_assets', $assets, $this->query['page'] );
    
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
        do_action( 'cdbt_admin_localize_script', $asset_data );
        
      }
    }
  }


  public function enqueue_jquery_ui( $assets=[], $nowpage=null ) {
    if ('cdbt_tables' === $nowpage && isset($this->query['tab']) && in_array($this->query['tab'], [ 'create_table', 'modify_table' ])) {
      $add_styles = [
        'cdbt-table-creator-style' => [ $this->plugin_url . 'assets/styles/cdbt-table-creator.css', true, $this->version, 'all' ],
      ];
      $assets['styles'] = array_merge($assets['styles'], $add_styles);
      $_inherit_script = array_key_exists( 'cdbt-jquery', $assets['scripts'] ) ? 'cdbt-jquery' : 'jquery';
      $add_scripts = [
        'cdbt-table-creator-script' => [ $this->plugin_url . 'assets/scripts/cdbt-table-creator.js', [ $_inherit_script ], null, true ],
      ];
      $assets['scripts'] = array_merge($assets['scripts'], $add_scripts);
    }
    
    return $assets;
  }


  /**
   * Fire after execution of `wp_enqueue_script()` for passing a variable to javascript
   *
   * @since 2.0.0
   * @since 2.0.7 Revision version
   */
  public function admin_localize_script( $asset_data ) {
    if ( array_key_exists( 'cdbt-admin-script', $asset_data ) ) {
      $cdbt_admin_vars = [
        'is_debug' => $this->debug ? 'true' : 'false', 
        'ajax_url' => $this->ajax_url( [ 'event' => 'setup_session' ] ), 
        'notices_via_modal' => isset( $this->options['notices_via_modal'] ) && $this->options['notices_via_modal'] ? 'true' : 'false', 
        'local_err_msg' => rawurlencode( __( 'An empty required field is exists.', CDBT ) ), 
        'local_copied' => rawurlencode( __( 'Copied', CDBT ) ), 
      ];
      if (array_key_exists( 'cdbt-table-creator-script', $asset_data ) ) {
        $cdbt_admin_vars['column_types'] =  null;
        $cdbt_admin_vars['cdbt_tc_translate'] = null;
      }
      
      wp_localize_script( 'cdbt-admin-script', 'cdbt_admin_vars', $cdbt_admin_vars );
    }
  }


  /**
   * Fire this hook when append into <head> tag on the admin pages for this plugin
   *
   * @since 2.0.0
   */
  public function admin_header() {
    if ( isset( $this->options['include_assets']['admin_jquery'] ) && ! $this->options['include_assets']['admin_jquery'] ) {
      echo "<script>if (typeof jQuery !== 'undefined' ) { var $ = jQuery; }</script>\n";
    }
    
    // Added action hook for using `add_action('cdbt_admin_header')`
    // 
    // @since 2.0.0
    do_action( 'cdbt_admin_header' );
    
  }


  /**
   * Fire this hook when append into <body> tag (just before </body>) on the all admin pages
   *
   * @since 2.0.0
   */
  public function admin_footer() {
    if (array_key_exists('page', $this->query) && preg_match('/^cdbt_.*$/iU', $this->query['page'])) 
      printf( '<div class="plugin-meta"><span class="label label-info">Ver. %s</span></div>', $this->version );
    
    //printf( "<script>jQuery(document).ready(function(\$){\$('li#toplevel_page_cdbt_management_console>ul.wp-submenu a.wp-first-item').text('%s');});</script>", __('Custom DB Tables', CDBT) );
    printf( "<script>(function(\$){\$('li#toplevel_page_cdbt_management_console>ul.wp-submenu a.wp-first-item').text('%s');})(jQuery);</script>", __('Custom DB Tables', CDBT) );
    
    // Added action hook for using `add_action('cdbt_admin_footer')`
    // 
    // @since 1.0.0
    do_action( 'cdbt_admin_footer' );
  }


  /**
   * Fire this hook when call to action of the admin notices (on the all admin pages)
   *
   * @since 2.0.0
   */
  public function admin_notices() {
    $messages = [];
    $notice_class = '';
    if (false !== ( $messages = get_transient( CDBT . '-error' ) )) {
      delete_transient( CDBT . '-error' );
      // Added filter hook for using `add_filter('cdbt_admin_error')`
      //
      // @since 1.0.0
      $messages = apply_filters( 'cdbt_admin_error', $messages);
      $notice_class = 'error';
    } else
    if (false !== ( $messages = get_transient( CDBT . '-notice' ) )) {
      delete_transient( CDBT . '-notice' );
      // Added filter hook for using `add_filter('cdbt_admin_notice')`
      //
      // @since 1.0.0
      $messages = apply_filters( 'cdbt_admin_notice', $messages);
      $notice_class = 'updated';
    }
    
    if (is_array($messages) && !empty($messages)) {
      $notification_html = '<div id="message" class="%s"><ul>%s</ul></div>';
      $message_list = [];
      foreach ($messages as $message) {
        if (!empty($message) && !is_null($message)) 
          $message_list[] = '<li>' . $message . '</li>';
      }
      if (!empty($message_list)) {
        echo sprintf($notification_html, $notice_class, implode('', $message_list));
      } else {
        return false;
      }
    }
  }


  /**
   * Register the notice messages on the admin panel 
   *
   * @since 2.0.0
   * @since 2.1.33 Change to public method for addons
   */
  public function register_admin_notices( $code=null, $message, $expire_seconds=10, $is_init=false ) {
    $code = empty($code) ? CDBT . '-error' : $code;
    // Filter of expiry time at displaying the notice message on the admin screen
    //
    // @since 2.0.0
    $expire_seconds = apply_filters( 'cdbt_transient_expire', $expire_seconds, $code, $message, $is_init );
    if (!$this->errors || $is_init) 
      $this->errors = new \WP_Error();
    
    if (is_object($this->errors)) {
      $this->errors->add( $code, $message );
      set_transient( $code, $this->errors->get_error_messages(), $expire_seconds );
      $this->errors->remove($code);
    }
    
  }


  /**
   * Override some contents at plugins page (`plugins.php`) in the admin panel
   *
   * @since 2.0.0
   */
  public function modify_plugin_action_links( $links, $file ) {
    if (plugin_basename($this->plugin_main_file) !== $file) 
      return $links;
    
    if (false === $this->plugin_enabled) 
      return $links;
    
    $prepend_new_links = $append_new_links = array();
    
    $prepend_new_links['settings'] = sprintf(
      '<a href="%s">%s</a>', 
      add_query_arg([ 'page' => 'cdbt_management_console' ], admin_url('admin.php')), 
      esc_html__( 'Settings', $this->domain_name )
    );
    
    unset($links['edit']);
    
    $append_new_links['edit'] = sprintf(
      '<a href="%s">%s</a>', 
      add_query_arg([ 'file' => plugin_basename($this->plugin_main_file) ], admin_url('plugin-editor.php')), 
      esc_html__( 'Edit', $this->domain_name )
    );
    
    return array_merge($prepend_new_links, $links, $append_new_links);
  }
  
  /**
   * Define position inserted plugin menu in admin panel.
   *
   * @since 2.0.0
   */
  private function admin_menu_position( $position='default' ) {
    $defined_position = [
      'top' => 3, // after dashboard
      'default' => 55, // before appearance
      'middle' => 77, // after tools
      'bottom' => 85, // after setting
    ];
    if ( array_key_exists( $position, $defined_position ) ) {
      $position = $defined_position[$position];
    } else {
      $position = intval($position) > 0 ? intval( $position ) : $defined_position['default'];
    }
    
    // Filter of menu position of this plugin in the admin menu
    //
    // @since 2.0.0
    return apply_filters( 'cdbt_admin_menu_position', $position );
  }


  /**
   * Controllers of admin pages for this plugin
   *
   * @since 2.0.0
   * @since 2.1.33 Added for addons
   */
  public function admin_controller() {
    if (empty( $_POST )) 
      return;
    
    $this->current_options = get_option($this->domain_name);
    
    if (check_admin_referer( 'cdbt_management_console-' . $this->query['page'] )) {
      // Call the worker method of each tab in admin pages
      if (isset($this->query['tab']) && !empty($this->query['tab'])) {
        $current_tab = '_' . $this->query['tab'];
      } elseif (isset($_POST['active_tab']) && !empty($_POST['active_tab'])) {
        $current_tab = '_' . $_POST['active_tab'];
      } else {
        $current_tab = '';
      }
      $worker_method = sprintf('do_%s%s', $this->query['page'], $current_tab);
      if (method_exists($this, $worker_method)) {
        $_SESSION = array_map( 'stripslashes_deep', $_POST );
        $this->update_session( $worker_method );
        $this->$worker_method();
      } elseif ( isset( $_POST[$this->domain_name]['for_addon'] ) && in_array( stripslashes( $_POST[$this->domain_name]['for_addon'] ), $this->extend ) ) {
        $_classname = array_search( stripslashes( $_POST[$this->domain_name]['for_addon'] ), $this->extend );
        if ( is_object( $this->addons[$_classname] ) && method_exists( $this->addons[$_classname], $worker_method ) ) {
          $_SESSION = array_map( 'stripslashes_deep', $_POST );
          $this->update_session( $worker_method );
          $this->addons[$_classname]->$worker_method();
        } else {
          // invalid access (No method in add-on)
          $this->destroy_session( $worker_method );
          $this->register_admin_notices( CDBT . '-error', __('Unauthorized access to this addon.', CDBT), 3, true );
        }
      } else {
        // invalid access (No method)
        $this->destroy_session( $worker_method );
        $this->register_admin_notices( CDBT . '-error', __('Unauthorized Access to this page.', CDBT), 3, true );
      }
    } else {
      // invalid access
      $this->destroy_session();
      $this->register_admin_notices( CDBT . '-error', __('Unauthorized Access to this page.', CDBT), 3, true );
    }
    $this->admin_notices();
    
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
    } else
    if (!isset($_POST['_wpnonce'])) {
      $message = __('You do not have permission to access to this page.', CDBT);
    } else
    if (!wp_verify_nonce( $_POST['_wpnonce'], 'cdbt_management_console-' . $this->query['page'] ) && !wp_verify_nonce( $_POST['_wpnonce'], 'cdbt_entry_data-' . $_POST['table'] )) {
      $message = __('You do not have permission to access to this page.', CDBT);
    }
    
    return $message;
    
  }


  /**
   * Page: cdbt_management_console | Tab: -
   *
   * @since 2.0.0
   */
  public function do_cdbt_management_console() {
    // None at the moment
  }


  /**
   * Page: cdbt_options | Tab: general_setting
   *
   * @since 2.0.0
   * @since 2.0.7 Added new options
   */
  public function do_cdbt_options_general_setting() {
    static $message = '';
    
    // Access authentication process to the page
    $message = $this->access_page_authentication( [ 'update', 'initialize' ] );
    if (!empty($message)) {
      $this->register_admin_notices( CDBT . '-error', $message, 3, true );
      return;
    }
    
    if ( 'update' === $_POST['action'] ) {
      $submit_options = array_map( 'stripslashes_deep', $_POST[$this->domain_name] );
    } else
    if ( 'initialize' === $_POST['action'] ) {
      $submit_options = $this->set_option_template();
    }
    
    // sanitaize empty values
    foreach ($submit_options as $key => $value) {
      if (empty($value)) 
        unset($submit_options[$key]);
    }
    
    // sanitaize checkbox values
    $checkbox_options = [ 'cleaning_options', 'uninstall_options', 'resume_options', 'enable_core_tables', 'notices_via_modal', 'debug_mode', 'use_wp_prefix', 'allow_rendering_shortcodes', 'prevent_duplicate_sending' ];
    foreach ($checkbox_options as $option_name) {
      if (!array_key_exists($option_name, $submit_options)) 
        $submit_options[$option_name] = false;
    }
    
    // for value of `plugin_menu_position`
    if ( array_key_exists( 'plugin_menu_position', $submit_options ) ) {
      $_candidates = [ 3 => 'top', 55 => 'default', 77 => 'middle', 85 => 'bottom' ];
      $_fixed_pos = null;
      foreach( $_candidates as $_num => $_pos ) {
        if ( strpos( strtolower( $submit_options['plugin_menu_position'] ), $_pos ) !== false ) {
          $_fixed_pos = $_pos;
          break;
        } else
        if ( strpos( $submit_options['plugin_menu_position'], ':' ) !== false ) {
          list( , $_str ) = explode( ':', $submit_options['plugin_menu_position'] );
          $_pos_num = intval( trim( $_str ) );
          if ( array_key_exists( $_pos_num, $_candidates ) ) {
            $_fixed_pos = $_candidates[$_pos_num];
            break;
          }
        }
      }
      if ( empty( $_fixed_pos ) ) {
        if ( intval( $submit_options['plugin_menu_position'] ) > 0 ) {
          $_fixed_pos = intval( $submit_options['plugin_menu_position'] );
        } else {
          $_fixed_pos = 'default';
        }
      }
    } else {
      $_fixed_pos = 'bottom';
    }
    $submit_options['plugin_menu_position'] = $_fixed_pos;
    
    // for values of `include_assets`
    $_chk_include_assets = [ 'admin_jquery', 'admin_underscore_js', 'admin_bootstrap', 'admin_fuel_ux', 'admin_kinetic', 'admin_clipboard', 'main_jquery', 'main_underscore_js', 'main_bootstrap', 'main_fuel_ux', 'main_kinetic', 'main_clipboard' ];
    if ( array_key_exists( 'include_assets', $submit_options ) ) {
      foreach ( $_chk_include_assets as $_asset_name ) {
        if ( ! array_key_exists( $_asset_name, $submit_options['include_assets'] ) ) {
          $submit_options['include_assets'][$_asset_name] = false;
        } else {
          $submit_options['include_assets'][$_asset_name] = true;
        }
      }
    } else {
      foreach ( $_chk_include_assets as $_asset_name ) {
        $submit_options['include_assets'][$_asset_name] = true;
      }
    }
    
    $updated_options = array_merge($this->current_options, $submit_options);
    
    $updated_options = apply_filters( 'before_update_options_general_setting', $updated_options );
    
    if ($this->update_options( $updated_options ) ) {
      $this->register_admin_notices( CDBT . '-notice', __('Saved plugin options.', CDBT), 3, true );
    } else {
      $this->register_admin_notices( CDBT . '-error', __('Failed to save options.', CDBT), 3, true );
    }
    
  }


  /**
   * Page: cdbt_options | Tab: messages
   *
   * @since 2.0.9
   */
  public function do_cdbt_options_messages() {
    static $message = '';
    
    // Access authentication process to the page
    $message = $this->access_page_authentication( [ 'override', 'format' ] );
    if (!empty($message)) {
      $this->register_admin_notices( CDBT . '-error', $message, 3, true );
      return;
    }
    
    $updated_options = $this->current_options;
    // Filter translate text to extend
    $override_messages = apply_filters( 'cdbt_override_translate_text', $this->override_messages );
    if ( 'override' === $_POST['action'] ) {
      $update_messages = array_map( 'stripslashes_deep', $_POST[$this->domain_name] );
      
      $_comparison = [];
      foreach ( $override_messages as $_origin_text ) {
        $_comparison[] = $this->create_hash( $_origin_text );
      }
      
      foreach ( $update_messages['override_messages'] as $_hash => $_text ) {
        if ( ( $_key = array_search( $_hash, $_comparison ) ) !== false ) {
          if ( ! empty( $_text ) ) {
            $_has_placeholder = ( $_placeholders = substr_count( $override_messages[$_key], '%' ) ) > 0;
            if ( $_has_placeholder ) {
              $_placeholder_strings = [];
              $_haystack = $override_messages[$_key];
              while ( $_placeholders > 0 ) {
                $_placeholder_strings[] = substr( $_haystack, strpos( $_haystack, '%' ), 2 );
                $_haystack = substr( strstr( $_haystack, '%' ), 1 );
                $_placeholders = substr_count( $_haystack, '%' );
              }
              if ( substr_count( $_text, '%' ) !== count( $_placeholder_strings ) ) {
                foreach ( $_placeholder_strings as $_placeholder_str ) {
                  if ( strpos( $_text, $_placeholder_str ) === false ) {
                    $_text .= '<input type="hidden" value="'. $_placeholder_str .'">';
                  }
                }
              }
            }
            $updated_options['override_messages'][$_hash] = $this->cdbt_strarc( $_text );
          } else {
            unset( $updated_options['override_messages'][$_hash] );
          }
        }
      }
    } else {
      $updated_options['override_messages'] = [];
    }
    $updated_options = apply_filters( 'before_update_options_messages', $updated_options );
    
    if ( $this->update_options( $updated_options ) ) {
      $message = 'override' === $_POST['action'] ? __('Saved the messages.', CDBT) : __('Initialized the messages.', CDBT);
      $msg_type = CDBT . '-notice';
    } else {
      $message = 'override' === $_POST['action'] ? __('Failed to save the messages.', CDBT) : __('Failed to initialize the messages.', CDBT);
      $msg_type = CDBT . '-error';
    }
    $this->register_admin_notices( $msg_type, $message, 3, true );
    
  }


  /**
   * Page: cdbt_options | Tab: debug
   *
   * @since 2.0.0
   */
  public function do_cdbt_options_debug() {
    static $message = '';
    
    // Access authentication process to the page
    $message = $this->access_page_authentication( [ 'debug_log' ] );
    if (!empty($message)) {
      $this->register_admin_notices( CDBT . '-error', $message, 3, true );
      return;
    }
    
    $submit_options = array_map( 'stripslashes_deep', $_POST[$this->domain_name] );
    
    // sanitaize checkbox values
    $checkbox_options = [ 'debug_log_option' ];
    foreach ($checkbox_options as $option_name) {
      if (!array_key_exists($option_name, $submit_options)) 
        $submit_options[$option_name] = false;
    }
    
    $_source = $this->plugin_dir . 'debug.log';
    if (!file_exists($_source)) {
      $this->register_admin_notices( CDBT . '-error', __('No log files.', CDBT), 3, true );
      return;
    }
    
    // Backup log file
    $_content = trim($submit_options['debug-log']);
    if ( $submit_options['debug_log_option'] && !empty($_content) ) {
      $_dist = $this->plugin_dir . 'backup/debug-' . date('Ymd', time()) . '.log';
      if (!@opendir($this->plugin_dir . 'backup')) {
        if (!wp_mkdir_p($this->plugin_dir . 'backup')) {
          $this->register_admin_notices( CDBT . '-error', __('Log deletion was interrupted owing to failure to create the directory for backup.', CDBT), 3, true );
          return;
        }
      }
      if (!@copy($_source, $_dist)) {
        system(sprintf('mv %s %s', $_source, $_dist), $result);
        if (1 === $result) {
          $this->register_admin_notices( CDBT . '-error', __('Failed to copy the log file.', CDBT), 3, true );
          return;
        }
      }
    }
    
    // Remove log contents
    if ($_fp = @fopen($_source, 'w')) {
      if (false === @fwrite($_fp, '')) {
        $this->register_admin_notices( CDBT . '-error', __('Failed to clear the logs.', CDBT), 3, true );
        return;
      }
    }
    fclose($_fp);
    
    $this->register_admin_notices( CDBT . '-notice', __('The log file was removed.', CDBT), 3, true );
  }


  /**
   * Page: cdbt_options | Tab: addons
   *
   * @since 2.1.33
   * @since 2.1.34 Updated
   */
  public function do_cdbt_options_addons() {
    static $message = '';
    
    // Access authentication process to the page
    $message = $this->access_page_authentication( [ 'install', 'activate', 'deactivate' ] );
    if ( ! empty( $message ) ) {
      $this->register_admin_notices( CDBT . '-error', $message, 3, true );
      return;
    }
    
    $submit_options = array_map( 'stripslashes_deep', $_POST[$this->domain_name] );
    
    $note_type = '-notice';
    if ( 'install' === $_POST['action'] ) {
      // download addon
      if ( file_exists( $submit_options['dist_uri'] ) ) {
        
//var_dump( $submit_options );
        $message = __( 'This add-on installed correctly. Please activate for using this add-on.', CDBT );
        
      } else {
        $message = __( 'Specified add-on does not exist.', CDBT );
        $note_type = '-error';
      }
    } else {
      if ( class_exists( __NAMESPACE__ .'\\Addons\\'. $submit_options['addon_class'] ) ) {
        $addon_class_path = __NAMESPACE__ .'\\Addons\\'. $submit_options['addon_class'];
        $addon_instance = new $addon_class_path();
        //$addon_instance->{$_POST['action'].'_addon'}();
        if ( 'activate' === $_POST['action'] ) {
          $this->extend[$submit_options['addon_class']] = $addon_class_path;
        } elseif ( 'deactivate' === $_POST['action'] ) {
          if ( array_key_exists( $submit_options['addon_class'], $this->extend ) ) 
            unset( $this->extend[$submit_options['addon_class']] );
        }
        $this->options['activated_addons'] = $this->extend;
        if ( $this->update_options( $this->options ) ) {
          if ( 'activate' === $_POST['action']  ) {
            $message = __( 'This add-on has been activated correctly now.', CDBT );
          } else
          if ( 'deactivate' === $_POST['action'] ) {
            $message = __( 'This add-on has been deactivated correctly.', CDBT );
          }
        }
      } else {
        // not installed crrectly
        $message = __( 'This add-on has not install correctly. Please try to install once more.', CDBT );
        $note_type = '-error';
      }
    }
    
    $this->register_admin_notices( CDBT . $note_type, $message, 3, true );
  }


  /**
   * Page: cdbt_tables | Tab: create_table
   *
   * @since 2.0.0
   * @since 2.0.7 Revision version
   */
  public function do_cdbt_tables_create_table() {
    static $message = '';
    
    // Access authentication process to the page
    $message = $this->access_page_authentication( [ 'create_table', 'resume_table' ] );
    if (!empty($message)) {
      $this->register_admin_notices( CDBT . '-error', $message, 3, true );
      return;
    }
    
    // Table creation process
    if ('create_table' === $_POST['action']) {
      // Validation params
      $source_data = array_map( 'stripslashes_deep', $_POST[$this->domain_name] );
      $errors = [];
      
      // For added option of sanitization (Since version 2.0.7)
      if ( ! array_key_exists( 'sanitization', $source_data ) ) 
        $source_data['sanitization'] = false;
      
      // Check the required item is whether it is empty
      $check_items = [ 'table_name', 'table_charset', 'table_db_engine', 'create_table_sql' ];
      foreach ($check_items as $item_key) {
        if (!isset($source_data[$item_key]) || empty($source_data[$item_key])) 
          $errors[] = sprintf( __('No %s.', CDBT), __($item_key, CDBT) );
      }
      if (!empty($errors)) {
        $this->register_admin_notices( CDBT . '-error', implode("\n", $errors), 3, true );
        return;
      }
      
      // Check the single byte characters
      $check_items = [ 'table_name', 'table_charset', 'table_db_engine' ];
      foreach ($check_items as $item_key) {
        if (!$this->validate->checkSingleByte( $source_data[$item_key] )) 
          // $errors[] = sprintf(__('Contains characters which cannot be used in %s.', CDBT), __($item_key, CDBT) );
          $errors[] = $item_key;
      }
      if ( ! empty( $errors ) ) {
        // $_error_str = implode("\n", $errors);
        $_error_str = __('Contains unavailable characters.', CDBT);
        $this->register_admin_notices( CDBT . '-error', $_error_str, 3, true );
        return;
      }
      
      // Check SQL statements for creating table
      $source_data['create_table_sql'] = stripslashes_deep($source_data['create_table_sql']);
      $result = $this->validate->validate_create_sql( $source_data['table_name'], $source_data['create_table_sql'] );
      if (true !== $result) {
        $this->register_admin_notices( CDBT . '-error', $result, 3, true );
        return;
      }
      
      // Run create table
      $is_created = $this->create_table( $source_data['table_name'], $source_data['create_table_sql'] );
      if ($is_created) {
        if ($this->add_new_table( $source_data['table_name'], 'regular', $source_data )) {
          $notice_class = CDBT . '-notice';
          $this->destroy_session(__FUNCTION__);
          $this->cdbt_sessions[$_POST['active_tab']]['target_table'] = $source_data['table_name'];
          $this->cdbt_sessions[$_POST['active_tab']]['to_redirect'] = add_query_arg([ 'page'=>'cdbt_tables', 'tab'=>'operate_table' ], admin_url('admin.php'));
        } else {
          $notice_class = CDBT . '-error';
        }
      } else {
        $notice_class = CDBT . '-error';
      }
      $this->register_admin_notices( $notice_class, $this->logger_cache, 3, true );
      return;
      
    }
    
    // Table resume processing
    if ('resume_table' === $_POST['action']) {
      
      $enable_tables = !$this->get_table_list( 'enable' ) ? [] : $this->get_table_list( 'enable' );
      $unreserved_tables = !$this->get_table_list( 'unreserved' ) ? [] : $this->get_table_list( 'unreserved' );
      $resume_table_list = array_diff($unreserved_tables, $enable_tables);
      
      if (empty($resume_table_list) || !in_array($_POST[$this->domain_name]['resume_table'], $resume_table_list)) {
        $message = __('No fetchable table.', CDBT);
        $this->register_admin_notices( CDBT . '-error', $message, 3, true );
        $this->destroy_session();
        return;
      }
      
      if (is_array($_POST[$this->domain_name]['resume_table'])) {
        $resume_table_name = array_map( 'stripslashes_deep', $_POST[$this->domain_name]['resume_table'] );
      } else {
        $resume_table_name = stripslashes_deep($_POST[$this->domain_name]['resume_table']);
      }
      $resume_table_options = [];
      // Filter the sub-option of the table to resume
      //
      // @since 2.0.0
      $resume_table_options = apply_filters( 'cdbt_resume_table_options', $resume_table_options, $resume_table_name );
      
      if ($this->add_new_table( $resume_table_name, 'regular', $resume_table_options )) {
        $message = __( 'Table import is completed successfully.', CDBT );
        $notice_class = CDBT . '-notice';
      } else {
        $message = __( 'Failed to import the table.', CDBT );
        $notice_class = CDBT . '-error';
      }
      
      $this->register_admin_notices( $notice_class, $message, 3, true );
      $this->logger( $message );
      $this->destroy_session();
      return;
      
    }
    
  }


  /**
   * Page: cdbt_tables | Tab: modify_table
   *
   * @since 2.0.0
   * @since 2.0.7 Revision version
   */
  public function do_cdbt_tables_modify_table() {
    static $message = '';
    $notice_class = CDBT . '-error';
    
    // Access authentication process to the page
    $message = $this->access_page_authentication( [ 'modify_table', 'update_options' ] );
    if (!empty($message)) {
      $this->register_admin_notices( $notice_class, $message, 3, true );
      return;
    }
    
    if ( get_magic_quotes_gpc() ) 
      $_POST = array_map( 'stripslashes_deep', $_POST );
    
    // Process of changing the table and switching operation action
    $table_name = $_POST['target_table'];
    $post_data = $_POST[$this->domain_name];
    $current_options = $this->get_table_option($table_name);
    $after_modified = 'reload';
    switch($_POST['action']) {
      case 'modify_table': 
        
        $this->cdbt_sessions[$_POST['active_tab']] = [
          'target_table' => $table_name, 
        ];
        $modification_db = [];
        $process_msg = [
          __('Invalid sql statement "ALTER TABLE".', CDBT), 
          __('Failed to run the query "ALTER TABLE".', CDBT), 
        ];
        $modify_done = 0;
        if ($post_data['table_name'] !== $current_options['table_name']) {
          // Rename table
          $_sql = sprintf( 'ALTER TABLE `%s` RENAME TO `%s`;', esc_sql($table_name), esc_sql($post_data['table_name']) );
          if (!$this->validate->validate_alter_sql( $table_name, $_sql )) {
            $message = $process_msg[0];
          } else {
            $result = $this->run_query($_sql);
            if ($result) {
              $table_name = $post_data['table_name'];
              $modify_done++;
            } else {
              $message = $process_msg[1];
            }
          }
        }
        if (empty($message) && $post_data['table_comment'] !== $current_options['table_name']) {
          // Modify table comment
          $_sql = sprintf( "ALTER TABLE `%s` COMMENT '%s';", esc_sql($table_name), esc_sql($post_data['table_comment']) );
          if (!$this->validate->validate_alter_sql( $table_name, $_sql )) {
            $message = $process_msg[0];
          } else {
            $result = $this->run_query($_sql);
            if (!$result) {
              $message = $process_msg[1];
            } else {
              $modify_done++;
            }
          }
        }
        if (empty($message) && $post_data['table_charset'] !== $current_options['table_charset']) {
          // Change table charset
          $_sql = sprintf( 'ALTER TABLE `%s` CHARSET=%s;', esc_sql($table_name), esc_sql($post_data['table_charset']) );
          if (!$this->validate->validate_alter_sql( $table_name, $_sql )) {
            $message = $process_msg[0];
          } else {
            $result = $this->run_query($_sql);
            if (!$result) {
              $message = $process_msg[1];
            } else {
              $modify_done++;
            }
          }
        }
        if (empty($message) && $post_data['table_db_engine'] !== $current_options['db_engine']) {
          // Change database engine
          $_sql = sprintf( 'ALTER TABLE `%s` ENGINE=%s;', esc_sql($table_name), esc_sql($post_data['table_db_engine']) );
          if (!$this->validate->validate_alter_sql( $table_name, $_sql )) {
            $message = $process_msg[0];
          } else {
            $result = $this->run_query($_sql);
            if (!$result) {
              $message = $process_msg[1];
            } else {
              $modify_done++;
            }
          }
        }
        if (empty($message) && !empty($post_data['alter_table_sql'])) {
          // Custom alter table SQL
          if (!$this->validate->validate_alter_sql( $table_name, $post_data['alter_table_sql'] )) {
            $message = $process_msg[0];
          } else {
            // Filter sql statement for alter table defined by user
            //
            // @since 2.0.7
            $alter_table_sql = apply_filters( 'cdbt_before_alter_table', $post_data['alter_table_sql'], $table_name );
            $result = $this->run_query( $alter_table_sql );
            if (!$result) {
              $message = $process_msg[1];
            } else {
              $after_modified = 'redirect';
              $modify_done++;
            }
          }
        }
        
        if ($modify_done > 0) {
          if (empty($message)) {
            // If modification succeeds
            $new_pk = [];
            foreach ($this->get_table_schema($table_name) as $column => $scheme) {
              if ($scheme['primary_key']) 
                $new_pk[] = $column;
            }
            $new_table_status = $this->get_table_status($table_name);
            // Overrides
            $current_options['table_name'] = $new_table_status['Name'];
            $current_options['table_comment'] = $new_table_status['Comment'];
            $current_options['primary_key'] = $new_pk;
            $current_options['sql'] = $this->get_create_table_sql($table_name);
            $current_options['table_charset'] = $post_data['table_charset'];
            $current_options['table_collation'] = $new_table_status['Collation'];
            $current_options['db_engine'] = $new_table_status['Engine'];
            if ($this->update_options( $current_options, 'override', 'tables' )) {
              $message = __('Succeeded to change.', CDBT); 
              $notice_class = CDBT . '-notice';
              $this->destroy_session(__FUNCTION__);
              $this->cdbt_sessions[$_POST['active_tab']]['is_modified'] = true;
              $this->cdbt_sessions[$_POST['active_tab']]['to_redirect'] = 'redirect' === $after_modified ? add_query_arg([ 'page'=>'cdbt_tables', 'tab'=>'operate_table' ], admin_url('admin.php')) : '';
              unset($modification_option, $current_options, $key, $value, $_key, $_value);
            } else {
              $message = __('Failed to update of the plugin options.', CDBT);
            }
          }
        } else {
          $message = __('There are no items to be modified. Please run again after you correct the items you want to modify.', CDBT);
        }
        
        break;
      case 'update_options': 
        
        $this->cdbt_sessions[$_POST['active_tab']] = [
          'target_table' => $table_name, 
        ];
        
        $modification_option = [];
        if ( ! empty( $post_data['max_show_records'] ) && $post_data['max_show_records'] !== $current_options['show_max_records']) {
          // Modify max show records
          $_new_value = intval($post_data['max_show_records']);
          $modification_option['show_max_records'] = $_new_value;
        }
        $post_data['sanitization'] = ! array_key_exists( 'sanitization', $post_data ) ? false : $post_data['sanitization'];
        if ( ! isset( $current_options['sanitization'] ) || $post_data['sanitization'] !== $current_options['sanitization'] ) {
          // Modify sanitization (added since 2.0.7)
          $_new_value = $this->strtobool( $post_data['sanitization'] );
          $modification_option['sanitization'] = $_new_value;
        }
        if ( ! empty( $post_data['user_permission_view'] ) && $post_data['user_permission_view'] !== implode(',', $current_options['permission']['view_global'])) {
          // Modify user permission view
          $_new_value = $this->strtoarray($post_data['user_permission_view']);
          $modification_option['permission']['view_global'] = $_new_value;
        }
        if ( ! empty( $post_data['user_permission_entry'] ) && $post_data['user_permission_entry'] !== implode(',', $current_options['permission']['entry_global'])) {
          // Modify user permission entry
          $_new_value = $this->strtoarray($post_data['user_permission_entry']);
          $modification_option['permission']['entry_global'] = $_new_value;
        }
        if ( ! empty( $post_data['user_permission_edit'] ) && $post_data['user_permission_edit'] !== implode(',', $current_options['permission']['edit_global'])) {
          // Modify user permission edit
          $_new_value = $this->strtoarray($post_data['user_permission_edit']);
          $modification_option['permission']['edit_global'] = $_new_value;
        }
        
        if ( ! empty( $modification_option ) ) {
          foreach ( $modification_option as $key => $value ) {
            if ( array_key_exists( $key, $current_options ) ) {
              if ( 'permission' !== $key ) {
                $current_options[$key] = $value;
              } else {
                foreach ( $modification_option[$key] as $_key => $_value ) {
                  $current_options[$key][$_key] = $_value;
                }
              }
            } else {
              $_default_options = $this->set_option_template();
              if ( array_key_exists( $key, $_default_options['tables'][0] ) ) 
                $current_options[$key] = $value;
            }
          }
          if ( $this->update_options( $current_options, 'override', 'tables' ) ) {
            // If modification succeeds
            $message = __('Succeeded to change.', CDBT); 
            $notice_class = CDBT . '-notice';
            $this->cdbt_sessions[$_POST['active_tab']]['is_modified'] = true;
            unset( $modification_option, $current_options, $key, $value, $_key, $_value );
          } else {
            $message = __('Failed to update of the plugin options.', CDBT);
          }
        } else {
          $message = __('There are no items to be modified. Please run again after you correct the items you want to modify.', CDBT);
        }
        
        break;
      default:
        $message = __('Called invalid operation.', CDBT);
        break;
    }
    
    if (!empty($message)) {
      $this->register_admin_notices( $notice_class, $message, 3, true );
    }
    return;
    
  }


  /**
   * Page: cdbt_tables | Tab: operate_table
   *
   * @since 2.0.0
   */
  public function do_cdbt_tables_operate_table() {
    static $message = '';
    $notice_class = CDBT . '-error';
    
    // Access authentication process to the page
    $message = $this->access_page_authentication( [ 'change_table', 'import_table', 'export_table', 'duplicate_table', 'backup_table' ] );
    if (!empty($message)) {
      $this->register_admin_notices( $notice_class, $message, 3, true );
      return;
    }
    
    if ( get_magic_quotes_gpc() ) 
      $_POST = array_map( 'stripslashes_deep', $_POST );
    
    // Process of changing the table and switching operation action
    switch($_POST['action']) {
      case 'change_table': 
        
        $post_data = $_POST[$this->domain_name];
        if (empty($post_data['operate_target_table'])) {
          $message = __('Faied to modify the operation table.', CDBT);
        } else {
          $this->cdbt_sessions[$_POST['active_tab']] = [
            'target_table' => $post_data['operate_target_table'], 
            'operate_current_table' => isset($post_data['operate_current_table']) && !empty($post_data['operate_current_table']) ? $post_data['operate_current_table'] : $post_data['operate_target_table'], 
            'operate_action' => isset($post_data['operate_action']) && !empty($post_data['operate_action']) ? $post_data['operate_action'] : 'detail', 
          ];
        }
        
        break;
      case 'import_table': 
        
        $post_data = $_POST[$this->domain_name];
        $this->cdbt_sessions[$_POST['active_tab']] = [
          'operate_target_table' => $_POST['import_to'], 
          'operate_current_table' => $_POST['import_to'], 
          'operate_action' => 'import', 
          'import_current_step' => 1, 
        ];
        if (!isset($post_data['import_current_step']) || empty($post_data['import_current_step'])) {
          $message = __('Unauthorized step.', CDBT);
        } else
        if (intval($post_data['import_current_step']) === 1) {
//var_dump($post_data);
          $step1_validate = false;
          if (isset($post_data['import_filetype']) && in_array($post_data['import_filetype'], $this->allow_file_types)) {
            $this->cdbt_sessions[$_POST['active_tab']][$this->domain_name]['import_filetype'] = $post_data['import_filetype'];
            if (in_array($post_data['import_filetype'], ['csv', 'tsv']) && isset($post_data['add_first_line'])) {
              $add_first_row = $this->strtoarray($post_data['add_first_line']);
              if (is_array($add_first_row) && !empty($add_first_row)) {
                $step1_validate = true;
                $this->cdbt_sessions[$_POST['active_tab']][$this->domain_name]['add_first_line'] = $add_first_row;
              }
            } else
            if (in_array($post_data['import_filetype'], ['json', 'sql'])) {
              $step1_validate = true;
            }
          }
          if ($step1_validate) {
            if ($_FILES[$this->domain_name]['size']['upfile'] > 0) {
              // Check file type and format
              if ($this->validate->check_file_type($_FILES[$this->domain_name]['type']['upfile'], $post_data['import_filetype'], $_FILES[$this->domain_name]['name']['upfile'])) {
                switch($post_data['import_filetype']){
                  case 'csv': 
                  case 'tsv': 
                    $_raw_array = $this->xsvtoarray( $_FILES[$this->domain_name]['tmp_name']['upfile'], $post_data['import_filetype'] );
                    if (empty($_raw_array)) {
                      $message = __('Failed to parse the uploaded file. It may be due to incorrect in the file.', CDBT);
                    } else
                    if (count(end($_raw_array)) !== count($add_first_row)) {
                      $message = __('The number of importing data does not match the number of the specified column. Please specify the column to match the import data again.', CDBT);
                    } else {
                      $_diff_result = array_diff($add_first_row, stripslashes_deep(end($_raw_array)));
                      if ( empty($_diff_result) ) 
                        array_pop($_raw_array);
                      
                      $_diff_result = array_diff($add_first_row, stripslashes_deep(reset($_raw_array)));
                      if ( empty($_diff_result) ) 
                        array_shift($_raw_array);
                      
                      if (empty($_raw_array)) {
                        $message = __('It does not contain the data to import in your uploaded files.', CDBT);
                      } else {
//var_dump( array_merge( $add_first_row, $_raw_array ) );
                        $importation_sql = $this->create_import_sql( $_POST['import_to'], array_merge( [$add_first_row], $_raw_array ) );
                        if ($importation_sql !== false) 
                          $escaped_sql = addslashes_gpc($importation_sql);
                      }
                    }
                    break;
                  case 'json': 
                  	$_json_data = @file_get_contents($_FILES[$this->domain_name]['tmp_name']['upfile']);
                    $_raw_array = json_decode($_json_data);
                    $_importation_base = $_columns = [];
                    $_i = 0;
                    foreach ($_raw_array as $_row) {
                      $_values = [];
                      foreach ($_row as $_key => $_value) {
                        if ($_i === 0) {
                          $_columns[] = $_key;
                        }
                        $_values[] = esc_sql($_value);
                      }
                      $_importation_base[] = $_values;
                      $_i++;
                    }
                    $importation_sql = $this->create_import_sql( $_POST['import_to'], array_merge( [$_columns], $_importation_base ) );
                    if ($importation_sql !== false) 
                      $escaped_sql = addslashes_gpc($importation_sql);
                    break;
                  case 'sql': 
                    $_base_sql = @file_get_contents( $_FILES[$this->domain_name]['tmp_name']['upfile'] );
                    $_base_sql = 'INSERT INTO `'. $_POST['import_to'] .'` '. strstr( $_base_sql, '(' );
                    $escaped_sql = addslashes_gpc( $_base_sql );
                    break;
                }
                if (isset($escaped_sql) && !empty($escaped_sql)) {
                  $this->cdbt_sessions[$_POST['active_tab']][$this->domain_name]['upfile'] = $escaped_sql;
                }
              } else {
                $message = __('Uploaded file format is different from the specified format.', CDBT);
              }
            } else {
              $message = __('Import file has not been uploaded.', CDBT);
            }
          } else {
            $message = __('Invalid format or Prameter is not enough.', CDBT);
          }
          if (empty($message)) {
            $this->cdbt_sessions[$_POST['active_tab']]['import_current_step'] = 2;
          }
        } else
        if (intval($post_data['import_current_step']) === 2) {
          // Run the data import
          $import_sql = stripslashes_deep($post_data['import_sql']);
          $is_valid_sql = preg_match('/^INSERT INTO `'. $this->cdbt_sessions[$_POST['active_tab']]['operate_target_table'] .'` \(.*$/', $import_sql);
          $result = $this->strtobool($is_valid_sql) ? $this->run_query($import_sql) : false;
          //$result = false;
          if ($result) {
            // Row number of execution results if successful insertion
            $this->cdbt_sessions[$_POST['active_tab']]['import_result'] = true;
            $this->cdbt_sessions[$_POST['active_tab']]['result_message'] = sprintf( __('%d data is imported successfully.', CDBT), intval($result) );
          } else {
            $this->cdbt_sessions[$_POST['active_tab']]['import_result'] = false;
            $this->cdbt_sessions[$_POST['active_tab']]['result_message'] = __('Failed to import the data.', CDBT);
          }
          $this->cdbt_sessions[$_POST['active_tab']]['import_current_step'] = 3;
        }
        
        break;
      case 'export_table': 
        
        if ($this->download_result) 
          $notice_class = CDBT . '-notice';
        $this->cdbt_sessions[$_POST['active_tab']] = [
          'operate_target_table' => $post_data['export_table'], 
          'operate_current_table' => $post_data['export_table'], 
          'operate_action' => 'detail', 
        ];
        
        $post_data = $_POST[$this->domain_name];
        $this->cdbt_sessions[$_POST['active_tab']] = [
          'operate_target_table' => $post_data['export_table'], 
          'operate_current_table' => $post_data['export_table'], 
          'operate_action' => 'detail', 
        ];
        $message = $this->download_message;
        unset($this->download_result, $this->download_message);
        
        break;
      case 'duplicate_table': 
        
        $post_data = $_POST[$this->domain_name];
        $duplicate_with_data = $this->strtobool($post_data['duplicate_with_data']);
        if (!isset($post_data['duplicate_table_name']) || empty($post_data['duplicate_table_name'])) {
          $message = __('Duplicate table name is not specified.', CDBT);
        } else
        if (!isset($post_data['duplicate_with_data']) || empty($post_data['duplicate_with_data']) || !in_array($post_data['duplicate_with_data'], [ 'true', 'false' ])) {
        	$message = __('Parameter for duplicating the table has incomplete type.', CDBT);
        } else
        if (!isset($post_data['duplicate_origin_table']) || empty($post_data['duplicate_origin_table'])) {
        	$message = __('Original table for duplicating is not specified.', CDBT);
        } else
        if ($this->check_table_exists($post_data['duplicate_table_name'])) {
          $message = __('Same duplicate table name already exists. Please specify a different table name.', CDBT);
        }
        
        if (empty($message)) {
          if ($this->duplicate_table( $post_data['duplicate_table_name'], $duplicate_with_data, $post_data['duplicate_origin_table'] )) {
            // Register as a managed table of plugin
            if ($this->add_new_table( $post_data['duplicate_table_name'], 'regular', $this->get_table_option($post_data['duplicate_origin_table']) )) {
              $notice_class = CDBT . '-notice';
              $message = __('Duplication of the table has been completed successfully.', CDBT);
            } else {
              $message = __('Duplication of table has been completed, but have failed to register as a manageable table of plugin. Please retry from resuming the table.', CDBT);
            }
          } else {
            $message = __('Failed to duplication of the table.', CDBT);
          }
        }
        // Set sessions
        if (CDBT . '-error' === $notice_class) {
          $this->cdbt_sessions[$_POST['active_tab']] = [
            'target_table' => $post_data['duplicate_origin_table'], 
            'operate_current_table' => $post_data['duplicate_origin_table'], 
            'duplicate_table_name' => $post_data['duplicate_table_name'], 
            'duplicate_with_data' => $duplicate_with_data, 
            'operate_action' => 'duplicate', 
          ];
        } else {
          $this->cdbt_sessions[$_POST['active_tab']] = [
            'operate_target_table' => $post_data['duplicate_table_name'], 
            'operate_current_table' => $post_data['duplicate_table_name'], 
            'operate_action' => 'detail', 
          ];
        }
        break;
      case 'backup_table': 
        
        break;
      default:
        $message = __('Called invalid operation.', CDBT);
        break;
    }
    
    if (!empty($message)) {
      $this->register_admin_notices( $notice_class, $message, 3, true );
    }
    return;
    
  }


  /**
   * Page: cdbt_tables | Tab: operate_data
   *
   * @since 2.0.0
   */
  public function do_cdbt_tables_operate_data() {
    static $message = '';
    $notice_class = CDBT . '-error';
    
    // Access authentication process to the page
    $message = $this->access_page_authentication( [ 'change_table', 'view_data', 'entry_data', 'edit_data', 'download_binary' ] );
    if (!empty($message)) {
      $this->register_admin_notices( $notice_class, $message, 3, true );
      return;
    }
    
    if ( get_magic_quotes_gpc() ) 
      $_POST = array_map( 'stripslashes_deep', $_POST );
    
    // Process of changing the table and switching operation action
    switch($_POST['action']) {
      case 'change_table': 
        
        $post_data = $_POST[$this->domain_name];
        if (empty($post_data['operate_target_table'])) {
          $message = __('Faied to modify the operation table.', CDBT);
        } else {
          $this->cdbt_sessions[$_POST['active_tab']] = [
            'target_table' => $post_data['operate_target_table'], 
            'operate_current_table' => isset($post_data['operate_current_table']) && !empty($post_data['operate_current_table']) ? $post_data['operate_current_table'] : $post_data['operate_target_table'], 
            'operate_action' => isset($post_data['operate_action']) && !empty($post_data['operate_action']) ? $post_data['operate_action'] : 'view', 
          ];
        }
        
        break;
      case 'view_data': 
        
        // No action
        
        break;
      case 'entry_data': 
        
        $table_name = $_POST['table'];
        $post_data = $_POST[$this->domain_name];
        foreach($post_data as $_k => $_v) {
          if ('' === $_v || is_null($_v)) 
            unset($post_data[$_k]);
        }
        unset($this->cdbt_sessions[__FUNCTION__]);
        $this->cdbt_sessions[$_POST['active_tab']] = [
          'target_table' => $table_name, 
          'operate_current_table' => $table_name, 
          'operate_action' => 'entry', 
        ];
        $register_data = $this->cleanup_data( $table_name, $post_data );
        if ($this->insert_data( $table_name, $register_data )) {
          $notice_class = CDBT . '-notice';
          $message = sprintf(__('Your entry data has been successfully registered to "%s" table.', CDBT), $table_name);
        } else {
          $message = sprintf(__('Failed to insert data to "%s" table.', CDBT), $table_name);
          $this->cdbt_sessions[$_POST['active_tab']][$this->domain_name] = $post_data;
        }
        
        break;
      case 'edit_data': 
        
        $table_name = $_POST['table'];
        $post_data = $_POST[$this->domain_name];
        unset($this->cdbt_sessions[__FUNCTION__]);
        $this->cdbt_sessions[$_POST['active_tab']] = [
          'target_table' => $table_name, 
          'operate_current_table' => $table_name, 
          'operate_action' => 'edit', 
        ];
        $register_data = $this->cleanup_data( $table_name, $post_data );
        $where_clause = unserialize(stripslashes_deep($_POST['where_clause']));
        if ($this->update_data( $table_name, $register_data, $where_clause )) {
          $notice_class = CDBT . '-notice';
          $message = __('Data updating are completed successfully.', CDBT);
        } else {
          $message = sprintf(__('Failed to update data of of "%s" table.', CDBT), $table_name);
          $message .= "\n". __('In the case of no change of between before and after, data does not updated.', CDBT);
          $message .= "\n". __('It might not have updated because there is the record which has same data.', CDBT);
        }
        
        break;
      case 'download_binary': 
      	$post_data = $_REQUEST[$this->domain_name];
      	$table_name = trim($post_data['table_name']);
      	$target_column = trim($post_data['target_column']);
      	$where_clause = $this->strtohash($post_data['where_clause']);
      	$this->download_binary( $table_name, $target_column, $where_clause );
      	
      	break;
      default:
        $message = __('Called invalid operation.', CDBT);
        break;
    }
    
    if (!empty($message)) {
      $this->register_admin_notices( $notice_class, $message, 3, true );
    }
    return;
    
  }


  /**
   * Page: cdbt_shortcodes | Tab: shortcode_register
   *
   * @since 2.0.0
   */
  public function do_cdbt_shortcodes_shortcode_register() {
    static $message = '';
    $notice_class = CDBT . '-error';
    
    // Access authentication process to the page
    $message = $this->access_page_authentication( [ 'register_shortcode' ] );
    if (!empty($message)) {
      $this->register_admin_notices( $notice_class, $message, 3, true );
      return;
    }
    
    if ( get_magic_quotes_gpc() ) 
      $_POST = array_map( 'stripslashes_deep', $_POST );
    
    $post_data = [];
    foreach ( $_POST[$this->domain_name] as $_key => $_val ) {
      if ( is_array( $_val ) && ! in_array( $_key, [ 'display_index_row', 'narrow_operator', 'footer_interface' ] ) ) {
        $post_data = array_merge( $post_data, $this->array_flatten( $_val ) );
      } else {
        $post_data[$_key] = $_val;
      }
    }
    
    // Check the required item is whether it is empty
    $check_items = [ 'base_name', 'target_table', 'csid' ];
    foreach ($check_items as $item_key) {
      if (!isset($post_data[$item_key]) || empty($post_data[$item_key])) 
        $errors[] = sprintf( __('No %s.', CDBT), __($item_key, CDBT) );
    }
    if (!empty($errors)) {
      $this->register_admin_notices( CDBT . '-error', implode("\n", $errors), 3, true );
      return;
    }
    
    // sanitaize checkbox values
    $checkbox_options = [ 'bootstrap_style', 'enable_repeater', 'display_list_num', 'display_search', 'display_title', 'enable_sort', 'display_filter', 'display_view', 'draggable', 'ajax_load', 'display_submit' ];
    foreach ( $checkbox_options as $option_name ) {
      $post_data[$option_name] = array_key_exists( $option_name, $post_data ) ? $this->strtobool( $post_data[$option_name] ) : false;
    }
    
    // radio button values
    $radio_options = [ 'display_index_row', 'narrow_operator', 'footer_interface' ];
    foreach ( $radio_options as $option_name ) {
      $post_data[$option_name] = is_array( $post_data[$option_name] ) ? $post_data[$option_name][0] : $post_data[$option_name];
    }
    
    $stored_shortcode = $this->get_shortcode_option(intval($post_data['csid']));
    if (empty($stored_shortcode)) {
      $all_shortcodes = array_merge($this->get_shortcode_option(), [ $post_data ]);
      if (update_option($this->domain_name . '-shortcodes', $all_shortcodes, 'no')) {
        $notice_class = CDBT . '-notice';
        $message = sprintf(__('Saved successfully as a custom shortcode ID: %d.', CDBT), intval($post_data['csid']));
      } else {
        $message = __('Failed to save the custom shortcode.', CDBT);
      }
    } else {
      $message = __('Failed to save because the specific custom shortcode id already exists.', CDBT);
    }
    
    if (!empty($message)) {
      $this->cdbt_sessions[$_POST['active_tab']][$this->domain_name] = $post_data;
      $this->register_admin_notices( $notice_class, $message, 3, true );
    }
    return;
    
  }


  /**
   * Page: cdbt_shortcodes | Tab: shortcode_edit
   *
   * @since 2.0.0
   * @since 2.1.31 Updated
   */
  public function do_cdbt_shortcodes_shortcode_edit() {
    static $message = '';
    $notice_class = CDBT . '-error';
    
    // Verifies whether or not is a valid accessing to this process
    $message = $this->access_page_authentication( [ 'edit_shortcode' ] );
    if ( ! empty( $message ) ) {
      $this->register_admin_notices( $notice_class, $message, 3, true );
      return;
    }
    
    if ( get_magic_quotes_gpc() ) 
      $_POST = array_map( 'stripslashes_deep', $_POST );
    
    $post_data = [];
    foreach( $_POST[$this->domain_name] as $_key => $_val ) {
      if ( $this->is_assoc( $_val ) ) {
        $post_data = array_merge( $post_data, $this->array_flatten( $_val ) );
      } else {
        $post_data[$_key] = $_val; //stripslashes_deep( $_val );
      }
    }
    
    // Checks whether the required items is empty or not
    $check_items = [ 'base_name', 'target_table', 'csid' ];
    foreach ( $check_items as $item_key ) {
      if ( ! isset( $post_data[$item_key] ) || empty( $post_data[$item_key] ) ) 
        $errors[] = sprintf( __('No %s.', CDBT), __($item_key, CDBT) );
    }
    if ( ! empty( $errors ) ) {
      $this->register_admin_notices( CDBT . '-error', implode( "\n", $errors ), 3, true );
      return;
    }
    
    // Optimize the checkbox's values
    $checkbox_options = [ 'bootstrap_style', 'enable_repeater', 'display_list_num', 'display_search', 'display_title', 'enable_sort', 'display_filter', 'display_view', 'draggable', 'ajax_load', 'display_submit' ];
    foreach ( $checkbox_options as $option_name ) {
      $post_data[$option_name] = array_key_exists( $option_name, $post_data ) ? $this->strtobool( $post_data[$option_name] ) : false;
    }
    // Optimize the radio's values
    $radio_options = [ 'display_index_row', 'narrow_operator', 'footer_interface' ];
    foreach ( $radio_options as $option_name ) {
      if ( 'display_index_row' === $option_name ) {
        if ( isset( $post_data[$option_name] ) ) {
          $post_data[$option_name] = is_array( $post_data[$option_name] ) ? array_shift( $post_data[$option_name] ) : strval( $post_data[$option_name] );
        } else {
          $post_data[$option_name] = 'true';
        }
      } else {
        $post_data[$option_name] = is_array( $post_data[$option_name] ) ? array_shift( $post_data[$option_name] ) : strval( $post_data[$option_name] );
      }
    }
    
    $stored_shortcode = $this->get_shortcode_option( intval( $post_data['csid'] ) );
    if ( ! empty( $stored_shortcode ) ) {
      $all_shortcodes = $this->get_shortcode_option();
      foreach ( $all_shortcodes as $_i => $shortcode_option ) {
        if ( intval( $post_data['csid'] ) === intval( $shortcode_option['csid'] ) ) {
          $all_shortcodes[$_i] = $post_data;
          break;
        }
      }
      if ( update_option( $this->domain_name . '-shortcodes', $all_shortcodes, 'no' ) ) {
        $notice_class = CDBT . '-notice';
        $message = sprintf( __('Updated successfully as a custom shortcode ID: %d.', CDBT), intval( $post_data['csid'] ) );
      } else {
        $message = __('Failed to update the custom shortcode.', CDBT);
      }
    } else {
      $message = __('Failed to update because there is not the specified custom shortcode.', CDBT);
    }
    
    if ( ! empty( $message ) ) {
      $this->cdbt_sessions[$_POST['active_tab']][$this->domain_name] = $post_data;
      $this->register_admin_notices( $notice_class, $message, 3, true );
    }
    return;
    
  }


  /**
   * Page: cdbt_web_apis | Tab: apikey_generator
   *
   * @since 2.0.0
   */
  public function do_cdbt_web_apis_apikey_generator() {
    static $message = '';
    $notice_class = CDBT . '-error';
    
    // Access authentication process to the page
    $message = $this->access_page_authentication( [ 'generate' ] );
    if (!empty($message)) {
      $this->register_admin_notices( $notice_class, $message, 3, true );
      return;
    }
    
    if ( get_magic_quotes_gpc() ) 
      $_POST = array_map( 'stripslashes_deep', $_POST );
    
    // Check the required items
    $_post_data = $_POST[$this->domain_name];
    if (!isset($_post_data['host_name']) || empty($_post_data['host_name'])) {
      $message = __('Request origin host does not specified.', CDBT);
    } else {
      if (preg_match('/^(([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5]).){3}([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/', $_post_data['host_name'])) {
        // done
      } else
      if (preg_match('/^((http|https|ftp):\/\/|)([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?\/?/i', $_post_data['host_name'], $matches)) {
        $_post_data['host_name'] = $matches[3];
      } else {
        $message = __('Specified request origin host is invalid.', CDBT);
      }
    }
    if (empty($message) && (!isset($_post_data['permission']) || empty($_post_data['permission']))) {
      $message = __('You can not registered the hosts which do not allow all of the request methods.', CDBT);
    }
    if (!empty($message)) {
      $this->register_admin_notices( $notice_class, $message, 3, true );
      return;
    }
    
    // 
    $_permit_bit_var = '';
    foreach ($this->request_methods as $_method) {
      $_permit_bit_var .= isset($_post_data['permission'][$_method]) && $this->strtobool($_post_data['permission'][$_method]) ? '1' : '0';
    }
    
    $_new_host = [
      'host_name' => $_post_data['host_name'], 
      'api_key' => $this->generate_api_key($_post_data['host_name']), 
      'desc' => esc_textarea($_post_data['description']), 
      'permission' => $_permit_bit_var, 
      'generated' => date('Y-m-d H:i:s'),
    ];
    
    $_current_hosts = $this->get_allowed_hosts();
    $_ids = array_keys($_current_hosts);
    $_max_host_id = count($_ids) > 0 ? max(array_keys($_current_hosts)) : 0;
    if (!isset($_current_hosts[$_max_host_id + 1])) {
      $_current_hosts[$_max_host_id + 1] = $_new_host;
      $this->options['api_hosts'] = $_current_hosts;
      if (update_option($this->domain_name, $this->options)) {
        $notice_class = CDBT . '-notice';
        $message = sprintf(__('The "%s" was registered as a host to allow the API request.', CDBT), $_post_data['host_name']);
        $this->destroy_session();
      } else {
        $message = __('Failed to generate the API key.', CDBT);
      }
    }
    
    if (!empty($message)) {
      $this->register_admin_notices( $notice_class, $message, 3, true );
    }
    return;
    
  }
  
  
  /**
   * Inserting contents into the modal via filter hook
   *
   * @since 2.0.0
   *
   * @param array $args [require] Original options for modal
   * @return array $args Array that has been filtered
   */
  public function insert_content_to_modal( $args ) {
    if (array_key_exists('modalTitle', $args)) {
      switch ($args['modalTitle']) {
        case 'notices_error': 
        case 'notices_updated': 
          $args['modalTitle'] = 'notices_error' === $args['modalTitle'] ? __('Reporting Errors', CDBT) : __('Reporting Results', CDBT);
          $args['modalBody'] = stripslashes_deep($args['modalBody']);
          if ( is_admin() ) {
            $_pages = [ 'page=cdbt_tables&tab=modify_table', 'page=cdbt_options', 'page=cdbt_options&tab=general_setting' ];
            foreach ( $_pages as $_param ) {
              if ( strpos( $_SERVER['HTTP_REFERER'], $_param ) ) {
                $args['modalShowEvent'] = "$('#cdbtModal').on('hidden.bs.modal', function(){ location.replace('". $_SERVER['HTTP_REFERER'] ."'); });";
                break;
              }
            }
            if ( strpos( $_SERVER['HTTP_REFERER'], 'page=cdbt_shortcodes&tab=shortcode_register' ) ) {
              $args['modalShowEvent'] = "$('#cdbtModal').on('hidden.bs.modal', function(){ location.href='". admin_url('admin.php?page=cdbt_shortcodes&tab=shortcode_list') ."'; });";
            }
          }
          break;
        case 'changing_item_none': 
          $args['modalTitle'] = __('No modification item', CDBT);
          $args['modalBody'] = __('Please run again after you correct the item you want to modify.', CDBT);
          break;
        case 'export_table': 
          $post_data = $args['modalExtras'];
          $post_data['export_columns'] = empty($post_data['export_columns']) ? [] : $post_data['export_columns'];
          $error = $this->export_table( $post_data['export_table'], $post_data['export_columns'], $post_data['export_filetype'] );
          $args['modalTitle'] = sprintf(__('Export data from "%s" table', CDBT), $post_data['export_table']);
          $args['modalBody'] = empty($error) ? __('If specified export table has a lot of data, it might take a long time to complete the download. Please start the export if it is convinient for you.', CDBT) : $error;
          if (empty($error)) {
            $args['modalFooter'] = [ sprintf('<button type="button" id="run_export_table" class="btn btn-primary">%s</button>', __('Export', CDBT)), ];
            $args['modalShowEvent'] = "$('#run_export_table').on('click', function(){ $('#cdbtModal').modal('hide'); });";
          }
          break;
        case 'truncate_table': 
          $args['modalTitle'] = sprintf(__('Truncate data in "%s" table', CDBT), $args['modalExtras']['table_name']);
          $args['modalBody'] = __('If you truncate the table, all currently stored data will be lost. In addition, you can not resume this prosess.<br>Are you sure to truncate this table?', CDBT);
          $args['modalFooter'] = [ sprintf('<button type="button" id="run_truncate_table" class="btn btn-primary">%s</button>', __('Truncate', CDBT)), ];
          $args['modalShowEvent'] = "$('#run_truncate_table').on('click', function(){ $('#cdbtModal').modal('hide'); });";
          break;
        case 'drop_table': 
          $args['modalTitle'] = sprintf(__('Remove the "%s" table', CDBT), $args['modalExtras']['table_name']);
          $args['modalBody'] = __('If you removed the table, all currently stored data will be lost. In addition, you can not resume this prosess.<br>Are you sure to remove this table?', CDBT);
          $args['modalFooter'] = [ sprintf('<button type="button" id="run_drop_table" class="btn btn-primary">%s</button>', __('Remove', CDBT)), ];
          $args['modalShowEvent'] = "$('#run_drop_table').on('click', function(){ $('#cdbtModal').modal('hide'); });";
          break;
        case 'table_unknown': 
          $args['modalTitle'] = __('Please select the table', CDBT);
          $args['modalBody'] = __('Please retry to operate that after you select the table.', CDBT);
          break;
        case 'no_selected_item': 
          $args['modalTitle'] = __('Please select the data', CDBT);
          $args['modalBody'] = __('Please retry to operate that after you select the data.', CDBT);
          $args['modalShowEvent'] = "return false;";
          break;
        case 'too_many_selected_item': 
          $args['modalTitle'] = __('You select too many data', CDBT);
          $args['modalBody'] = __('Please retry after selecting one data you want to edit.', CDBT);
          break;
        case 'empty_required_field': 
          $args['modalTitle'] = __('Required field is empty', CDBT);
          $args['modalBody'] = __('Please fill in the required fields of non-input.', CDBT);
          break;
        case 'edit_data_form': 
          $args['modalTitle'] = __('Form to edit data', CDBT);
          $args['modalBody'] = sprintf('<input type="hidden" id="edit-data-form" value="cdbt-entry table=\'%s\' display_title=\'false\' action_url=\'%s\' form_action=\'edit_data\' display_submit=\'false\' where_clause=\'%s\'">', $args['modalExtras']['table_name'], $args['modalExtras']['action_url'], $args['modalExtras']['where_clause'] );
          $args['modalFooter'] = [ sprintf('<button type="button" id="run_update_data" class="btn btn-primary">%s</button>', __('Update', CDBT)), ];
          // $args['modalShowEvent'] = "$('#run_update_data').on('click', function(){ $('#cdbtModal').modal('hide'); });";
          break;
        case 'delete_data': 
          //$args['modalTitle'] = sprintf(__('Remove the selected %s of data', CDBT), $args['modalExtras']['items']);
          $args['modalTitle'] = __('Removes the selected data', CDBT);
          $args['modalBody'] = sprintf( __('Data of current deletion candidates: %s', CDBT), $args['modalExtras']['items'] ) . "<br>\n";
          $args['modalBody'] .= __('You can not restore that data after removed the data. Are you sure that you want to perform the data deletion?', CDBT);
          $args['modalFooter'] = [ sprintf('<button type="button" id="run_delete_data" class="btn btn-primary">%s</button>', __('Remove', CDBT)), ];
          $args['modalShowEvent'] = "$('#run_delete_data').on('click', function(){ $('#cdbtModal').modal('hide'); });";
          break;
        case 'image_preview': 
          $args['modalTitle'] = __('Preview Image', CDBT);
          $args['modalBody'] = stripslashes_deep($args['modalBody']);
          $args['modalShowEvent'] = "if($('.preview-image-body').width() < $('.preview-image-body img').width() && $.fn['kinetic'] !== undefined){ $('.preview-image-body').css({overflowX:'hidden'}).kinetic(); }";
          break;
        case 'binary_downloader': 
          $args['modalTitle'] = __( 'Describe File', CDBT );
          $_table_name = trim( $args['modalExtras']['table_name'] );
          $_target_column = trim( $args['modalExtras']['target_column'] );
          $_where_clause = $this->strtohash( $args['modalExtras']['where_clause'] );
          $ret = $this->array_flatten( $this->get_data( $_table_name, $_target_column, $_where_clause, null, null, null, 1, ARRAY_A ) );
          if ( array_key_exists( $_target_column, $ret ) ) {
            $_bin_array = unserialize( $ret[$_target_column] );
            unset($_bin_array['bin_data']);
          }
          if ( $_bin_array ) {
            $info_html = '<table class="table table-bordered describe-file-info"><tbody>%s</tbody></table>';
            $inner_line_tmpl = '<tr><th>%s</th><td>%s</td></tr>';
            $inner_lines = [];
            $inner_lines[] = sprintf($inner_line_tmpl, __('File Name', CDBT), rawurldecode($_bin_array['origin_file']));
            $inner_lines[] = sprintf($inner_line_tmpl, __('MIME Type', CDBT), $_bin_array['mime_type']);
            $inner_lines[] = sprintf($inner_line_tmpl, __('File Size', CDBT), $this->convert_filesize($_bin_array['file_size']));
            $inner_lines[] = sprintf($inner_line_tmpl, __('File Hash', CDBT), esc_attr($_bin_array['hash']));
            $download_url = '/index.php?cdbt_api_key=%s&cdbt_table=%s&cdbt_api_request=binary_download&column=%s&conditions={%s}&hash=%s';
            $download_url = sprintf($download_url, wp_create_nonce( 'cdbt_api_ownhost-' . $_table_name ), $_table_name, $_target_column, $args['modalExtras']['where_clause'], esc_attr($_bin_array['hash']));
            $args['modalBody'] = sprintf($info_html, implode("\n", $inner_lines));
            $args['modalFooter'] = [ sprintf('<a href="%s" id="run_download_file" class="btn btn-primary">%s</a>', $download_url, __('Download', CDBT)), ];
            //$args['modalShowEvent'] = "$('#run_download_file').on('click', function(){ $('#cdbtModal').modal('hide'); });";
          }
          break;
        case 'delete_shortcode': 
          $_current_shortcode = $this->get_shortcode_option($args['modalExtras']['target_scid']);
          $_generated_shortcode = array_key_exists( 'generate_shortcode', $_current_shortcode ) ? stripslashes_deep( substr( $_current_shortcode['generate_shortcode'], 1, -1 ) ) : '';
          $args['modalTitle'] = __('Remove the shortcode', CDBT);
          $args['modalBody'] = __('You can not restore the shortcode settings after deleted. Are you sure to delete this shortcode settings?', CDBT) . sprintf( '<div style="margin: 1em;"><pre><code>&#91;%s&#93;</code></pre></div>', $_generated_shortcode );
          $args['modalFooter'] = [ sprintf('<button type="button" id="run_delete_shortcode" class="btn btn-primary" data-csid="%s">%s</button>', $args['modalExtras']['target_scid'], __('Remove', CDBT)), ];
          $args['modalShowEvent'] = "$('#run_delete_shortcode').on('click', function(){ $('#cdbtModal').modal('hide'); });";
          break;
        case 'delete_host': 
          $args['modalTitle'] = __('Remove the allowed host', CDBT);
          $args['modalBody'] = sprintf(__('You can not restore the allowed host %s after deleted. Are you sure to delete this host settings?', CDBT), $args['modalExtras']['host_name']);
          $args['modalFooter'] = [ sprintf('<button type="button" id="run_delete_host" class="btn btn-primary" data-hostid="%s">%s</button>', $args['modalExtras']['host_id'], __('Remove', CDBT)), ];
          $args['modalShowEvent'] = "$('#run_delete_host').on('click', function(){ $('#cdbtModal').modal('hide'); });";
          break;
        case 'preview_shortcode': 
          $args['modalTitle'] = __('Preview shortcode', CDBT);
          $args['modalBody'] = stripslashes_deep($args['modalExtras']['shortcode']);
          //$args['modalShowEvent'] = "if ($('.modal-body').find('.cdbt-entry-data-form').size() > 0) { $('.datepicker').datepicker({ date: new Date($('input[name=\"custom-database-tables[created][prev_date]\"]').val()), allowPastDates: true, restrictDateSelection: true, momentConfig: { culture: $('.cdbt-datepicker').attr('data-moment-locale'), format: $('.cdbt-datepicker').attr('data-moment-format') } }); } else { for (var k in repeater) { repeater[k](); }; };";
          $args['modalShowEvent'] = "if ($('.modal-body').find('.cdbt-entry-data-form').size() > 0) { var now = new Date(); $('.cdbt-datepicker').datepicker('getDate', now); $('.datepicker-combobox-hour input[type=text]').val(('00' + now.getHours()).slice(-2)); $('.datepicker-combobox-minute input[type=text]').val(('00' + now.getMinutes()).slice(-2)); $('.datepicker-combobox-second input[type=text]').val(('00' + now.getSeconds()).slice(-2)); } else { if (typeof repeater !== 'undefined') { for (var k in repeater) { repeater[k](); }; }; if (typeof DynamicTables !== 'undefined') { _.each($('.cdbt-table-wrapper').find('table').map(function(){return this.id; }).get(), function(v){ var table = new DynamicTables[v](); return table.render('disabled'); }); }; }; $(document).on('click', '.modal-body button, .modal-body a', function(e){ e.stopPropagation(); e.preventDefault(); return false; });";
          break;
        case 'preview_request_api': 
          $request_uri = $args['modalExtras']['request_uri'];
          $args['modalTitle'] = __('Preview Request Web API', CDBT);
          $args['modalBody'] = '<iframe src="'. $request_uri .'" style="width: 100%; height: 480px; font-size: 13px; line-height: 1.2;"></iframe>';
          break;
        case 'table_creator': 
          $conponent_options = [
            'targetTable' => isset($args['modalExtras']['target_table']) ? $args['modalExtras']['target_table'] : '', 
            'columnDefinitions' => isset($args['modalExtras']['column_definitions']) ? $args['modalExtras']['column_definitions'] : '', 
          ];
          ob_start();
          $this->component_render('table_creator', $conponent_options); // by trait `DynamicTemplate`
          $_component = ob_get_contents();
          ob_clean();
          $args['modalTitle'] = __('Table Creator', CDBT);
          $args['modalBody'] = '<p class="text-info">' . __('In the "table creator" you can intuitively create the columns configuration of table. It will be cached the settings after you click of "Apply SQL". Then it is never lost even if you close this modal window.', CDBT) . '</p>' . $_component;
          $args['modalFooter'] = [ sprintf('<button type="button" id="reset_sql" class="btn btn-default">%s</button>', __('Reset', CDBT)), sprintf('<button type="button" id="apply_sql" class="btn btn-primary">%s</button>', __('Apply SQL', CDBT)) ];
          break;
        case 'hide_tutorial': 
          $args['modalTitle'] = __('Tutorial displaying confirmation', CDBT);
          $args['modalBody'] = '<p class="text-info">' . __('Do you want to hide the display of the tutorial? If so, please click the "Hide" below.', CDBT) . '</p>';
          $args['modalFooter'] = [ sprintf('<button type="button" id="hide_tutorial" class="btn btn-primary">%s</button>', __('Hide', CDBT)) ];
          $args['modalShowEvent'] = "$('#hide_tutorial').on('click', function(){ $('#cdbtModal').modal('hide'); });";
          break;
        case 'view_item_full': 
          $args['modalTitle'] = __('Show Full Content', CDBT);
          $args['modalBody'] = '<textarea style="overflow: hidden; resize: none;" readonly>'. mb_encode_numericentity( stripslashes_deep( $args['modalBody'] ), array( 0x0, 0x10ffff, 0, 0xffffff ), 'UTF-8' ) .'</textarea>';
          $args['modalShowEvent'] = "$('#cdbtModal').find('.modal-body>textarea').addClass('view-full-content').css({ height:$('#cdbtModal').find('.modal-body>textarea')[0].scrollHeight+'px' });";
          break;
        default:
          break;
      }
    }
    
    return $args;
  }
  
  
}

endif; // end of class_exists()