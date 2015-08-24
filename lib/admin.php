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
    $this->includes();
    
    // Initial Action
    add_action( 'admin_init', array($this, 'admin_initialize') );
    
    // General Actions
    if (!empty($GLOBALS['pagenow']) && 'plugins.php' === $GLOBALS['pagenow'] ) 
      add_action( 'admin_notices', array($this, 'check_plugin_notices'));
    
    add_action( 'admin_menu', array($this, 'admin_menus') );
    
    // Add New Actions
    do_action( 'cdbt_get_admin_template', array($this, 'get_admin_template') );
    
    // Filters
    add_filter( 'plugin_action_links', array($this, 'modify_plugin_action_links'), 10, 2 );
    add_filter( 'admin_body_class', array($this, 'add_body_classes') );
    add_filter( 'cdbt_dynamic_modal_options', array($this, 'insert_content_to_modal') ); // The content insertion via filter hook
    
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
   * Initialize for inserting plugin option settings into admin panel of wordpress
   * And initialize sessions
   *
   * @since 2.0.0
   */
  public function admin_initialize() {
    
    register_setting( 'cdbt_management_console', $this->domain_name );
    
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
    
    $menus = [];
    
    $menus[] = add_menu_page( 
      __('CDBT Management Console', $this->domain_name), 
      __('CDBT', $this->domain_name), 
      $operating_capability, 
      'cdbt_management_console', 
      array($this, 'admin_page_render'), 
      'dashicons-admin-generic', 
      $this->admin_menu_position( 'bottom' )
    );
    
    $menus[] = add_submenu_page( 
      'cdbt_management_console', 
      __('CDBT Tables Management', $this->domain_name), 
      __('Tables', $this->domain_name), 
      $operating_capability, 
      'cdbt_tables', 
      array($this, 'admin_page_render') 
    );
    
    $menus[] = add_submenu_page( 
      'cdbt_management_console', 
      __('CDBT Shortcodes Management', $this->domain_name), 
      __('Shortcodes', $this->domain_name), 
      $operating_capability, 
      'cdbt_shortcodes', 
      array($this, 'admin_page_render') 
    );
    
    $menus[] = add_submenu_page( 
      'cdbt_management_console', 
      __('CDBT Web APIs Management', $this->domain_name), 
      __('Web APIs', $this->domain_name), 
      $operating_capability, 
      'cdbt_web_apis', 
      array($this, 'admin_page_render') 
    );
    
    $menus[] = add_submenu_page( 
      'cdbt_management_console', 
      __('CDBT Plugin Options', $this->domain_name), 
      __('Plugin Options', $this->domain_name), 
      $operating_capability, 
      'cdbt_options', 
      array($this, 'admin_page_render') 
    );
    
    // Parsed QUERY_STRING is stored $this->query
    wp_parse_str( $_SERVER['QUERY_STRING'], $this->query );
    
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
   */
  public function admin_assets() {
    // Fire this hook when register CSS and JavaScript to admin panel (on the all admin page)
    if (!array_key_exists('page', $this->query) || !preg_match('/^cdbt_.*$/iU', $this->query['page'])) 
      return;
    
    $assets = [
      'styles' => [
        'cdbt-admin-style' => [ $this->plugin_url . 'assets/styles/cdbt-admin.css', true, $this->version, 'all' ], 
        'cdbt-fuelux' => [ $this->plugin_url . 'assets/styles/fuelux.css', true, null, 'all' ], 
      ], 
      'scripts' => [
        // 'cdbt-modernizr' => [ $this->plugin_url . 'assets/scripts/modernizr.js', array(), null, false ], 
        'cdbt-jquery' => [ $this->plugin_url . 'assets/scripts/jquery.js', array(), null, false ], 
        'cdbt-underscore' => [ $this->plugin_url . 'assets/scripts/underscore.js', array(), null, true ], 
        // 'cdbt-fuelux' => [ $this->plugin_url . 'assets/scripts/fuelux.js', array(), null, true ], 
        'cdbt-admin-script' => [ $this->plugin_url . 'assets/scripts/cdbt-admin.js', array(), null, true ], 
      ]
    ];
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
      $add_scripts = [
        'jquery-ui-core' => null, 
        'jquery-ui-widget' => null, 
        'jquery-ui-mouse' => null, 
        'jquery-ui-position' => null, 
        'jquery-ui-sortable' => null, 
        'jquery-ui-autocomplete' => null, 
        'cdbt-table-creator-script' => [ $this->plugin_url . 'assets/scripts/cdbt-table-creator.js', array('jquery-ui-core'), null, true ],
      ];
      $assets['scripts'] = array_merge($assets['scripts'], $add_scripts);
    }
    
    return $assets;
  }


  /**
   * Fire after execution of `wp_enqueue_script()` for passing a variable to javascript
   *
   * @since 2.0.0
   */
  public function admin_localize_script( $asset_data ) {
    if ( array_key_exists( 'cdbt-admin-script', $asset_data ) ) {
      $cdbt_admin_vars = [
        'is_debug' => $this->debug ? 'true' : 'false', 
        'ajax_url' => $this->ajax_url( [ 'event' => 'setup_session' ] ), 
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
    
    printf( "<script>jQuery(document).ready(function(\$){\$('li#toplevel_page_cdbt_management_console>ul.wp-submenu a.wp-first-item').text('%s');});</script>", __('Custom DB Tables', CDBT) );
    
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
    if (false !== get_transient( CDBT . '-error' )) {
      $messages = get_transient( CDBT . '-error' );
      // Added filter hook for using `add_filter('cdbt_admin_error')`
      //
      // @since 1.0.0
      $messages = apply_filters( 'cdbt_admin_error', $messages);
      $classes = 'error';
    } elseif (false !== get_transient( CDBT . '-notice' )) {
      $messages = get_transient( CDBT . '-notice' );
      // Added filter hook for using `add_filter('cdbt_admin_notice')`
      //
      // @since 1.0.0
      $messages = apply_filters( 'cdbt_admin_notice', $messages);
      $classes = 'updated';
    }
    
    if (isset($messages) && !empty($messages)) :
?>
    <div id="message" class="<?php echo $classes; ?>">
      <ul>
      <?php foreach( $messages as $message ): ?>
        <li><?php echo $message; ?></li>
      <?php endforeach; ?>
      </ul>
    </div>
<?php
    endif;
  }


  /**
   * Register the notice messages on the admin panel 
   *
   * @since 2.0.0
   */
  private function register_admin_notices( $code=null, $message, $expire_seconds=1, $is_init=false ) {
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
    if (array_key_exists($position, $defined_position)) {
      $position = $defined_position[$position];
    } else {
      $position = intval($position) > 0 ? intval($position) : $defined_position['default'];
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
      } else {
        // invalid access
        $this->destroy_session( $worker_method );
        $this->register_admin_notices( CDBT . '-error', __('Invalid access this page.', CDBT), 3, true );
      }
    } else {
      // invalid access
      $this->destroy_session();
      $this->register_admin_notices( CDBT . '-error', __('Invalid access this page.', CDBT), 3, true );
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
      $message = __('Illegal access is.', CDBT);
    } else
    if (!isset($_POST['_wpnonce'])) {
      $message = __('You do not have access privileges on this page.', CDBT);
    } else
    if (!wp_verify_nonce( $_POST['_wpnonce'], 'cdbt_management_console-' . $this->query['page'] ) && !wp_verify_nonce( $_POST['_wpnonce'], 'cdbt_entry_data-' . $_POST['table'] )) {
      $message = __('You do not have access privileges on this page.', CDBT);
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
   */
  public function do_cdbt_options_general_setting() {
    static $message = '';
    
    // Access authentication process to the page
    $message = $this->access_page_authentication( [ 'update' ] );
    if (!empty($message)) {
      $this->register_admin_notices( CDBT . '-error', $message, 3, true );
      return;
    }
    
    $submit_options = array_map( 'stripslashes_deep', $_POST[$this->domain_name] );
    
    // sanitaize empty values
    foreach ($submit_options as $key => $value) {
      if (empty($value)) 
        unset($submit_options[$key]);
    }
    
    // sanitaize checkbox values
    $checkbox_options = [ 'cleaning_options', 'uninstall_options', 'resume_options', 'enable_core_tables', 'debug_mode', 'use_wp_prefix' ];
    foreach ($checkbox_options as $option_name) {
      if (!array_key_exists($option_name, $submit_options)) 
        $submit_options[$option_name] = false;
    }
    
    $updated_options = array_merge($this->current_options, $submit_options);
    
    $updated_options = apply_filters( 'before_update_options_general_setting', $updated_options );
    
    if ($this->update_options( $updated_options ) ) {
      $this->register_admin_notices( CDBT . '-notice', __('Plugin options saved.', CDBT), 3, true );
    } else {
      $this->register_admin_notices( CDBT . '-error', __('Could not save options.', CDBT), 3, true );
    }
    
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
      $this->register_admin_notices( CDBT . '-error', __('Log file does not exist.', CDBT), 3, true ); /* ログファイルがありません。 */
      return;
    }
    
    // Backup log file
    $_content = trim($submit_options['debug-log']);
    if ( $submit_options['debug_log_option'] && !empty($_content) ) {
      $_dist = $this->plugin_dir . 'backup/debug-' . date('Ymd', time()) . '.log';
      if (!@opendir($this->plugin_dir . 'backup')) {
        if (!wp_mkdir_p($this->plugin_dir . 'backup')) {
          $this->register_admin_notices( CDBT . '-error', __('Could not make a directory for backup. Then, it was interrupted of log deletion.', CDBT), 3, true ); /* バックアップ用のディレクトリ作成ができませんでした。ログ削除は中断されました。 */
          return;
        }
      }
      if (!@copy($_source, $_dist)) {
        system(sprintf('mv %s %s', $_source, $_dist), $result);
        if (1 === $result) {
          $this->register_admin_notices( CDBT . '-error', __('Could not copy of the log file.', CDBT), 3, true ); /* ログファイルのコピーができませんでした。 */
          return;
        }
      }
    }
    
    // Remove log contents
    if ($_fp = @fopen($_source, 'w')) {
      if (false === @fwrite($_fp, '')) {
        $this->register_admin_notices( CDBT . '-error', __('Could not clear the log.', CDBT), 3, true ); /* ログの削除ができませんでした。 */
        return;
      }
    }
    fclose($_fp);
    
    $this->register_admin_notices( CDBT . '-notice', __('Have cleared the log.', CDBT), 3, true );
  }


  /**
   * Page: cdbt_tables | Tab: create_table
   *
   * @since 2.0.0
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
      
      // Check the required item is whether it is empty
      $check_items = [ 'table_name', 'table_charset', 'table_db_engine', 'create_table_sql' ];
      foreach ($check_items as $item_key) {
        if (!isset($source_data[$item_key]) || empty($source_data[$item_key])) 
          $errors[] = sprintf( __('%s does not exist.', CDBT), __($item_key, CDBT) );
      }
      if (!empty($errors)) {
        $this->register_admin_notices( CDBT . '-error', implode("\n", $errors), 3, true );
        return;
      }
      
      // Check the single byte characters
      $check_items = [ 'table_name', 'table_charset', 'table_db_engine' ];
      foreach ($check_items as $item_key) {
        if (!$this->validate->checkSingleByte( $source_data[$item_key] )) 
          $errors[] = sprintf(__('Contains characters which cannot be used in %s.', CDBT), __($item_key, CDBT) );
      }
      if (!empty($errors)) {
        $this->register_admin_notices( CDBT . '-error', implode("\n", $errors), 3, true );
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
        $message = __('Incorporatable table does not exist.', CDBT);
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
        $message = __( 'Table incorporation has been completed successfully.', CDBT );
        $notice_class = CDBT . '-notice';
      } else {
        $message = __( 'Failed the table incorporation.', CDBT );
        $notice_class = CDBT . '-error';
      }
      
      $this->register_admin_notices( $notice_class, $message, 1, true );
      $this->logger( $message );
      $this->destroy_session();
      return;
      
    }
    
  }


  /**
   * Page: cdbt_tables | Tab: modify_table
   *
   * @since 2.0.0
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
          __('Invalid sql statement of `ALTER TABLE`.', CDBT), 
          __('Failed to run the query of `ALTER TABLE`.', CDBT), 
        ];
        $modify_done = 0;
        if ($post_data['table_name'] !== $current_options['table_name']) {
          // Rename table
          $_sql = sprintf( 'ALTER TABLE %s RENAME TO %s;', esc_sql($table_name), esc_sql($post_data['table_name']) );
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
          $_sql = sprintf( "ALTER TABLE %s COMMENT '%s';", esc_sql($table_name), esc_sql($post_data['table_comment']) );
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
          $_sql = sprintf( 'ALTER TABLE %s CHARSET=%s;', esc_sql($table_name), esc_sql($post_data['table_charset']) );
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
          $_sql = sprintf( 'ALTER TABLE %s ENGINE=%s;', esc_sql($table_name), esc_sql($post_data['table_db_engine']) );
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
          if (!$this->validate->validate_alter_sql( $table_name, esc_sql($post_data['alter_table_sql']) )) {
            $message = $process_msg[0];
          } else {
            $result = $this->run_query(esc_sql($post_data['alter_table_sql']));
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
            $current_options['db_engine'] = $new_table_status['Engine'];
            if ($this->update_options( $current_options, 'override', 'tables' )) {
              $message = __('Modification was successful.', CDBT); 
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
          $message = __('There was no item to be modify. Please run again after you correct the item you want to modify.', CDBT);
        }
        
        break;
      case 'update_options': 
        
        $this->cdbt_sessions[$_POST['active_tab']] = [
          'target_table' => $table_name, 
        ];
        
        $modification_option = [];
        if ($post_data['max_show_records'] !== $current_options['show_max_records']) {
          // Modify max show records
          $_new_value = intval($post_data['max_show_records']);
          $modification_option['show_max_records'] = $_new_value;
        }
        if ($post_data['user_permission_view'] !== implode(',', $current_options['permission']['view_global'])) {
          // Modify user permission view
          $_new_value = $this->strtoarray($post_data['user_permission_view']);
          $modification_option['permission']['view_global'] = $_new_value;
        }
        if ($post_data['user_permission_entry'] !== implode(',', $current_options['permission']['entry_global'])) {
          // Modify user permission entry
          $_new_value = $this->strtoarray($post_data['user_permission_entry']);
          $modification_option['permission']['entry_global'] = $_new_value;
        }
        if ($post_data['user_permission_edit'] !== implode(',', $current_options['permission']['edit_global'])) {
          // Modify user permission edit
          $_new_value = $this->strtoarray($post_data['user_permission_edit']);
          $modification_option['permission']['edit_global'] = $_new_value;
        }
        
        if (!empty($modification_option)) {
          foreach ($modification_option as $key => $value) {
            if (array_key_exists($key, $current_options)) {
              if ('permission' !== $key) {
                $current_options[$key] = $value;
              } else {
                foreach ($modification_option[$key] as $_key => $_value) {
                  $current_options[$key][$_key] = $_value;
                }
              }
            }
          }
          if ($this->update_options( $current_options, 'override', 'tables' )) {
            // If modification succeeds
            $message = __('Modification was successful.', CDBT); 
            $notice_class = CDBT . '-notice';
            $this->cdbt_sessions[$_POST['active_tab']]['is_modified'] = true;
            unset($modification_option, $current_options, $key, $value, $_key, $_value);
          } else {
            $message = __('Failed to update of the plugin options.', CDBT);
          }
        } else {
          $message = __('There was no item to be modify. Please run again after you correct the item you want to modify.', CDBT);
        }
        
        break;
      default:
        $message = __('Illegal operation was called.', CDBT);
        break;
    }
    
    if (!empty($message)) {
      $this->register_admin_notices( $notice_class, $message, 1, true );
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
          $message = __('Could not change the operate table.', CDBT);
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
          $message = __('Invalid step transition.', CDBT);
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
                      $message = __('The uploaded file could not be parsed. It likely has deficiencies in the file.', CDBT);
                    } else
                    if (count(end($_raw_array)) !== count($add_first_row)) {
                      $message = __('The number of data to be imported is not consistent with the number of the specified column. Please specify the column to match the import data again.', CDBT);
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
                          $escaped_sql = base64_encode($importation_sql);
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
                      $escaped_sql = base64_encode($importation_sql);
                    break;
                  case 'sql': 
                    $bin_context = $this->get_binary_context( $_FILES[$this->domain_name]['tmp_name']['upfile'], $_FILES[$this->domain_name]['name']['upfile'], $_FILES[$this->domain_name]['type']['upfile'], $_FILES[$this->domain_name]['size']['upfile'] );
                    $escaped_sql = $this->esc_binary_data($bin_context, 'bin_data');
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
            $message = __('Uploaded file format is invalid, or parameter is not enough.', CDBT);
          }
          if (empty($message)) {
            $this->cdbt_sessions[$_POST['active_tab']]['import_current_step'] = 2;
          }
        } else
        if (intval($post_data['import_current_step']) === 2) {
          // Run the data import
//var_dump($post_data['import_sql']);
          $result = $this->run_query(base64_decode($post_data['import_sql']));
          if ($result) {
            // Row number of execution results if successful insertion
            $this->cdbt_sessions[$_POST['active_tab']]['import_result'] = true;
            $this->cdbt_sessions[$_POST['active_tab']]['result_message'] = sprintf( __('%d of the data has been successfully imported.', CDBT), intval($result) );
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
        	$message = __('Parameter for duplicating the table is incomplete.', CDBT);
        } else
        if (!isset($post_data['duplicate_origin_table']) || empty($post_data['duplicate_origin_table'])) {
        	$message = __('Original table for duplicating is not specified.', CDBT);
        } else
        if ($this->check_table_exists($post_data['duplicate_table_name'])) {
          $message = __('Duplicate table name already exists. Please specify a different table name.', CDBT);
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
            $message = __('Failed to replication of the table.', CDBT);
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
        $message = __('Illegal operation was called.', CDBT);
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
    $message = $this->access_page_authentication( [ 'change_table', 'view_data', 'entry_data', 'edit_data' ] );
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
          $message = __('Could not change the operate table.', CDBT);
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
          $message = sprintf(__('Could not insert data to "%s" table.', CDBT), $table_name);
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
          $message = __('Update of the data has been completed successfully.', CDBT);
        } else {
          $message = sprintf(__('Could not update data of "%s" table.', CDBT), $table_name);
          $message .= "\n". __('Not done updating of data if there is no change to the data in updating before and after.', CDBT);
          $message .= "\n". __('Or, it is possible that the record having the same data could not be updated in order that existed in the other.', CDBT);
        }
        
        break;
      default:
        $message = __('Illegal operation was called.', CDBT);
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
    foreach($_POST[$this->domain_name] as $_key => $_val) {
      if (is_array($_val)) {
        $post_data = array_merge($post_data, $this->array_flatten($_val));
      } else {
        $post_data[$_key] = $_val;
      }
    }
    
    // Check the required item is whether it is empty
    $check_items = [ 'base_name', 'target_table', 'csid' ];
    foreach ($check_items as $item_key) {
      if (!isset($post_data[$item_key]) || empty($post_data[$item_key])) 
        $errors[] = sprintf( __('%s does not exist.', CDBT), __($item_key, CDBT) );
    }
    if (!empty($errors)) {
      $this->register_admin_notices( CDBT . '-error', implode("\n", $errors), 3, true );
      return;
    }
    
    // sanitaize checkbox values
    $checkbox_options = [ 'bootstrap_style', 'display_list_num', 'display_search', 'display_title', 'enable_sort', 'display_index_row', 'display_filter', 'display_view', 'ajax_load', 'display_submit' ];
    foreach ($checkbox_options as $option_name) {
      $post_data[$option_name] = array_key_exists($option_name, $post_data) ? $this->strtobool($post_data[$option_name]) : false;
    }
    
    $stored_shortcode = $this->get_shortcode_option(intval($post_data['csid']));
    if (empty($stored_shortcode)) {
      $all_shortcodes = array_merge($this->get_shortcode_option(), [ $post_data ]);
      if (update_option($this->domain_name . '-shortcodes', $all_shortcodes, 'no')) {
        $notice_class = CDBT . '-notice';
        $message = sprintf(__('Have been saved successfully as a custom shortcode ID: %d.', CDBT), intval($post_data['csid']));
      } else {
        $message = __('Could not save the custom shortcode.', CDBT);
      }
    } else {
      $message = __('Could not save because the specific custom shortcode id already exists.', CDBT);
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
   */
  public function do_cdbt_shortcodes_shortcode_edit() {
    static $message = '';
    $notice_class = CDBT . '-error';
    
    // Access authentication process to the page
    $message = $this->access_page_authentication( [ 'edit_shortcode' ] );
    if (!empty($message)) {
      $this->register_admin_notices( $notice_class, $message, 3, true );
      return;
    }
    
    if ( get_magic_quotes_gpc() ) 
      $_POST = array_map( 'stripslashes_deep', $_POST );
    
    $post_data = [];
    foreach($_POST[$this->domain_name] as $_key => $_val) {
      if (is_array($_val)) {
        $post_data = array_merge($post_data, $this->array_flatten($_val));
      } else {
        $post_data[$_key] = $_val;
      }
    }
    
    // Check the required item is whether it is empty
    $check_items = [ 'base_name', 'target_table', 'csid' ];
    foreach ($check_items as $item_key) {
      if (!isset($post_data[$item_key]) || empty($post_data[$item_key])) 
        $errors[] = sprintf( __('%s does not exist.', CDBT), __($item_key, CDBT) );
    }
    if (!empty($errors)) {
      $this->register_admin_notices( CDBT . '-error', implode("\n", $errors), 3, true );
      return;
    }
    
    // sanitaize checkbox values
    $checkbox_options = [ 'bootstrap_style', 'display_list_num', 'display_search', 'display_title', 'enable_sort', 'display_index_row', 'display_filter', 'display_view', 'ajax_load', 'display_submit' ];
    foreach ($checkbox_options as $option_name) {
      $post_data[$option_name] = array_key_exists($option_name, $post_data) ? $this->strtobool($post_data[$option_name]) : false;
    }
    
    $stored_shortcode = $this->get_shortcode_option(intval($post_data['csid']));
    if (!empty($stored_shortcode)) {
      $all_shortcodes = $this->get_shortcode_option();
      foreach ($all_shortcodes as $_i => $shortcode_option) {
        if (intval($post_data['csid']) === intval($shortcode_option['csid'])) {
          $all_shortcodes[$_i] = $post_data;
          break;
        }
      }
      if (update_option($this->domain_name . '-shortcodes', $all_shortcodes, 'no')) {
        $notice_class = CDBT . '-notice';
        $message = sprintf(__('Have been updated successfully as a custom shortcode ID: %d.', CDBT), intval($post_data['csid']));
      } else {
        $message = __('Could not update the custom shortcode.', CDBT);
      }
    } else {
      $message = __('Could not update because the specified custom shortcode does not exist.', CDBT);
    }
    
    if (!empty($message)) {
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
      $message = __('Hosts that do not allow all of the request methods can not be registered.', CDBT);
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
    
    $_current_hosts = $this->allowed_hosts;
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
          break;
        case 'changing_item_none': 
          $args['modalTitle'] = __('Modification item none', CDBT);
          $args['modalBody'] = __('Please run again after you correct the item you want to modify.', CDBT);
          break;
        case 'export_table': 
          $post_data = $args['modalExtras'];
          $post_data['export_columns'] = empty($post_data['export_columns']) ? [] : $post_data['export_columns'];
          $error = $this->export_table( $post_data['export_table'], $post_data['export_columns'], $post_data['export_filetype'] );
          $args['modalTitle'] = sprintf(__('Export data from "%s" table', CDBT), $post_data['export_table']);
          $args['modalBody'] = empty($error) ? __('If specified table has a lot of data for exporting, it may take long time until the download is complete.<br>Please start the export if it is good.', CDBT) : $error;
          if (empty($error)) {
            $args['modalFooter'] = [ sprintf('<button type="button" id="run_export_table" class="btn btn-primary">%s</button>', __('Export', CDBT)), ];
            $args['modalShowEvent'] = "$('#run_export_table').on('click', function(){ $('#cdbtModal').modal('hide'); });";
          }
          break;
        case 'truncate_table': 
          $args['modalTitle'] = sprintf(__('Truncate data in "%s" table', CDBT), $args['modalExtras']['table_name']);
          $args['modalBody'] = __('When you truncate a table, all data that currently stored will be lost. Then, you can not resume this process.<br>Do you want to truncate this table really?', CDBT);
          $args['modalFooter'] = [ sprintf('<button type="button" id="run_truncate_table" class="btn btn-primary">%s</button>', __('Truncate', CDBT)), ];
          $args['modalShowEvent'] = "$('#run_truncate_table').on('click', function(){ $('#cdbtModal').modal('hide'); });";
          break;
        case 'drop_table': 
          $args['modalTitle'] = sprintf(__('Remove the "%s" table', CDBT), $args['modalExtras']['table_name']);
          $args['modalBody'] = __('If you have removed a table, at same time all data that currently stored will be lost. Then, you can not resume this process.<br>Do you want to remove the table really?', CDBT);
          $args['modalFooter'] = [ sprintf('<button type="button" id="run_drop_table" class="btn btn-primary">%s</button>', __('Delete', CDBT)), ];
          $args['modalShowEvent'] = "$('#run_drop_table').on('click', function(){ $('#cdbtModal').modal('hide'); });";
          break;
        case 'table_unknown': 
          $args['modalTitle'] = __('Table is not selected', CDBT);
          $args['modalBody'] = __('Please retry to operate that after the table selection.', CDBT);
          break;
        case 'no_selected_item': 
          $args['modalTitle'] = __('Data is not selected', CDBT);
          $args['modalBody'] = __('Please retry to operate that after the data selection.', CDBT);
          break;
        case 'too_many_selected_item': 
          $args['modalTitle'] = __('Selected data is too many', CDBT);
          $args['modalBody'] = __('Please retry after selecting one data you want to edit.', CDBT);
          break;
        case 'edit_data_form': 
          $args['modalTitle'] = __('Edit Data Form', CDBT);
          $args['modalBody'] = sprintf('<input type="hidden" id="edit-data-form" value="[cdbt-entry table=\'%s\' display_title=\'false\' action_url=\'%s\' form_action=\'edit_data\' display_submit=\'false\' where_clause=\'%s\']">', $args['modalExtras']['table_name'], $args['modalExtras']['action_url'], $args['modalExtras']['where_clause'] );
          $args['modalFooter'] = [ sprintf('<button type="button" id="run_update_data" class="btn btn-primary">%s</button>', __('Update', CDBT)), ];
//          $args['modalShowEvent'] = "$('#run_update_data').on('click', function(){ $('#cdbtModal').modal('hide'); });";
          break;
        case 'delete_data': 
          $args['modalTitle'] = sprintf(__('Remove the selected %s of data', CDBT), $args['modalExtras']['items']);
          $args['modalBody'] = __('You can not restore that data after deleted the data. Are you sure to delete the data?', CDBT);
          $args['modalFooter'] = [ sprintf('<button type="button" id="run_delete_data" class="btn btn-primary">%s</button>', __('Delete', CDBT)), ];
          $args['modalShowEvent'] = "$('#run_delete_data').on('click', function(){ $('#cdbtModal').modal('hide'); });";
          break;
        case 'delete_shortcode': 
          $_current_shortcode = $this->get_shortcode_option($args['modalExtras']['target_scid']);
          $args['modalTitle'] = __('Remove the shortcode', CDBT);
          $args['modalBody'] = __('You can not restore the shortcode settings after deleted. Are you sure to delete this shortcode settings?', CDBT) . sprintf('<div style="margin: 1em;"><pre><code>%s</code></pre></div>', stripslashes_deep($_current_shortcode['generate_shortcode']));
          $args['modalFooter'] = [ sprintf('<button type="button" id="run_delete_shortcode" class="btn btn-primary" data-csid="%s">%s</button>', $args['modalExtras']['target_scid'], __('Delete', CDBT)), ];
          $args['modalShowEvent'] = "$('#run_delete_shortcode').on('click', function(){ $('#cdbtModal').modal('hide'); });";
          break;
        case 'delete_host': 
          $args['modalTitle'] = __('Remove the allowed host', CDBT);
          $args['modalBody'] = sprintf(__('You can not restore the allowed host %s after deleted. Are you sure to delete this host settings?', CDBT), $args['modalExtras']['host_name']);
          $args['modalFooter'] = [ sprintf('<button type="button" id="run_delete_host" class="btn btn-primary" data-hostid="%s">%s</button>', $args['modalExtras']['host_id'], __('Delete', CDBT)), ];
          $args['modalShowEvent'] = "$('#run_delete_host').on('click', function(){ $('#cdbtModal').modal('hide'); });";
          break;
        case 'preview_shortcode': 
          $args['modalTitle'] = __('Preview shortcode', CDBT);
          $args['modalBody'] = stripslashes_deep($args['modalExtras']['shortcode']);
          break;
        case 'preview_request_api': 
//var_dump($args['modalExtras']['request_uri']);
//       	$response = @file_get_contents($args['modalExtras']['request_uri']);
//var_dump($response);
          $args['modalTitle'] = __('Preview Request Web API', CDBT);
          $args['modalBody'] = '<iframe src="'. $args['modalExtras']['request_uri'] .'" style="width: 100%; height: 100%; overflow: hidden;"></iframe>';
          break;
        case 'table_creator': 
        	$conponent_options = [
        	  'targetTable' => isset($args['modalExtras']['target_table']) ? $args['modalExtras']['target_table'] : '', 
            'columnDefinitions' => isset($args['modalExtras']['column_definitions']) ? $args['modalExtras']['column_definitions'] : '', 
          ];
        	ob_start();
        	$this->component_render('table_creator', $conponent_options); // by trait `DynamicTemplate`
        	$_component = ob_get_contents();
        	ob_end_clean();
        	$args['modalTitle'] = __('Table Creator', CDBT);
          $args['modalBody'] = __('<p class="text-info">テーブルクリエーターでは直感的にテーブルのカラム構成を作成できます。なお、このウィンドウを閉じても「テーブル作成」か「リセット」をしない限り設定内容はキャッシュされます。</p>', CDBT) . $_component;
          $args['modalFooter'] = [ sprintf('<button type="button" id="reset_sql" class="btn btn-default">%s</button>', __('Reset', CDBT)), sprintf('<button type="button" id="apply_sql" class="btn btn-primary">%s</button>', __('Apply SQL', CDBT)) ];
        	break;
        default:
          break;
      }
    }
    
    return $args;
  }
  
  
}

endif; // end of class_exists()