<?php

namespace CustomDataBaseTables\Lib;


if ( !defined( 'CDBT' ) ) exit;

if ( !class_exists( 'CdbtAdmin' ) ) :

final class CdbtAdmin extends CdbtDB {

  var $query = [];

  protected $wpdb;

  /**
   * Factory Method
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
    
    $this->$name = is_callable($value) ? $value->bindTo($this, $this) : $value;
  }


  // Import traits
  use DynamicTemplate;
  use CdbtExtras;


  private function setup_globals() {
    // Global Object
    global $wpdb;
    $this->wpdb = $wpdb;
    
  }


  private function init() {
    
    // Plugin Core Initialize
    $this->core_init();
    
    // Capabilities
    $this->minimum_capability = apply_filters( 'cdbt_admin_minimum_capability', 'edit_posts' ); // -> Contributor
    $this->webmaster_capability = apply_filters( 'cdbt_admin_webmaster_capability', 'edit_pages' ); // -> Editor
    $this->maximum_capability = apply_filters( 'cdbt_admin_maximum_capability', 'activate_plugins' ); // -> Administrator, and Super Admin
    
    // Paths
    $this->admin_template_dir = apply_filters( 'cdbt_admin_template_dir', $this->plugin_dir . 'templates/admin/' );
    
    // Plugin Options Initialize
    $this->options_init();
    
    // DataBase Initialize
    $this->db_init();
    
  }

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
    
  }

  /**
   * Include Extensions
   */
  private function includes() {
    
    // Currently none
    
  }

  public function admin_initialize() {
    register_setting( 'cdbt_management_console', $this->domain_name );
  }

  public function admin_menus() {
    $operating_capability = $this->minimum_capability;
    
    $menus = [];
    
    $menus[] = add_menu_page( 
      __('CDBT Management Console', $this->domain_name), 
      __('CDBT', $this->domain_name), 
      $operating_capability, 
      'cdbt_management_console', 
      array($this, 'admin_page_render'), 
      'dashicons-admin-generic', 
      $this->admin_menu_position( 'top' )
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
      __('CDBT APIs Management', $this->domain_name), 
      __('APIs', $this->domain_name), 
      $operating_capability, 
      'cdbt_apis', 
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
      add_action( "admin_head-$menu", array($this, 'admin_header') );
      add_action( "admin_footer-$menu", array($this, 'admin_footer') );
      add_action( 'admin_footer', array($this, 'admin_footer') );
      add_action( 'admin_notices', array($this, 'admin_notices') );
    }
  }
  
  public function admin_page_render() {
    // render the admin pages defined at admin_menus()
    if (isset($this->query['page']) && !empty($this->query['page'])) {
      
      $template_file_path = sprintf('%s%s.php', $this->admin_template_dir, $this->query['page']);
      
      if (file_exists($template_file_path)) {
        $this->admin_controller();
        
        // require_once( apply_filters( 'include_template-' . $this->query['page'], $template_file_path ) );
        
        $page_render_method = 'render_' . $this->query['page'];
        $this->set_template_file_path( apply_filters( 'include_template-' . $this->query['page'], $template_file_path ) );
        // Define Dynamic Closure
        $this->$page_render_method = function(){ require( $this->template_file_path ); };
        $this->$page_render_method();
        
      }
    }
    
  }
  
  public function admin_assets() {
    // Fire this hook when register CSS and JavaScript to admin panel (on the all admin page)
    if (!array_key_exists('page', $this->query) || !preg_match('/^cdbt_.*$/iU', $this->query['page'])) 
      return;
    
    $assets = [
      'styles' => [
//        'cdbt-main-style' => [ $this->plugin_url . 'assets/styles/cdbt-main.css', array(), $this->version, 'all' ], 
        'cdbt-admin-style' => [ $this->plugin_url . 'assets/styles/cdbt-admin.css', true, $this->version, 'all' ], 
        'cdbt-fuelux' => [ $this->plugin_url . 'assets/styles/fuelux.css', true, null, 'all' ], 
      ], 
      'scripts' => [
//        'cdbt-main-script' => [ $this->plugin_url . 'assets/scripts/cdbt-main.js', array(), null, true ], 
        'cdbt-modernizr' => [ $this->plugin_url . 'assets/scripts/modernizr.js', array(), null, true ], 
        'cdbt-jquery' => [ $this->plugin_url . 'assets/scripts/jquery.js', array(), null, true ], 
        'cdbt-underscore' => [ $this->plugin_url . 'assets/scripts/underscore.js', array(), null, true ], 
        'cdbt-admin-script' => [ $this->plugin_url . 'assets/scripts/cdbt-admin.js', array(), null, true ], 
//        'cdbt-fuelux' => [ $this->plugin_url . 'assets/scripts/fuelux.js', array(), null, true ], 
//        'jquery-ui-core' => null, 
//        'jquery-ui-widget' => null, 
//        'jquery-ui-mouse' => null, 
//        'jquery-ui-position' => null, 
//        'jquery-ui-sortable' => null, 
//        'jquery-ui-autocomplete' => null, 
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
      }
    }
  }

  public function admin_header() {
    // Fire this hook when append into <head> tag on the admin pages for this plugin
    
  }

  public function admin_footer() {
    // Fire this hook when append into <body> tag (just before </body>) on the all admin pages
    if (array_key_exists('page', $this->query) && preg_match('/^cdbt_.*$/iU', $this->query['page'])) 
      printf( '<div class="plugin-meta"><span class="label label-info">Ver. %s</span></div>', $this->version );
    
    printf( "<script>jQuery(document).ready(function(\$){\$('li#toplevel_page_cdbt_management_console>ul.wp-submenu a.wp-first-item').text('%s');});</script>", __('Custom DB Tables', CDBT) );
  }

  public function admin_notices() {
    // Fire this hook when call to action of the admin notices (on the all admin pages)
    if (false !== get_transient( CDBT . '-error' )) {
      $messages = get_transient( CDBT . '-error' );
      $classes = 'error';
    } elseif (false !== get_transient( CDBT . '-notice' )) {
      $messages = get_transient( CDBT . 'notice' );
      $classes = 'updated';
    }
    
    if (isset($messages) && !empty($messages)) :
?>
    <div id="message" class="<?php echo $classes; ?>">
      <ul>
      <?php foreach( $messages as $message ): ?>
        <li><?php echo esc_html($message); ?></li>
      <?php endforeach; ?>
      </ul>
    </div>
<?php
    endif;
  }
  
  private function register_admin_notices( $code=null, $message, $expire_seconds=10, $is_init=false ) {
    $code = empty($code) ? CDBT . '-error' : $code;
    if (!$this->errors || $is_init) 
      $this->errors = new \WP_Error();
    
    if (is_object($this->errors)) {
      $this->errors->add( $code, $message );
      set_transient( $code, $this->errors->get_error_messages(), $expire_seconds );
    }
    
    // return $this->errors;
  }
  
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
    
    return apply_filters( 'cdbt_admin_menu_position', $position );
  }


  /**
   * Controllers of admin pages for this plugin
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
        $this->$worker_method();
      } else {
        // invalid access
        $this->register_admin_notices( CDBT . '-error', __('Invalid access this page.', CDBT), 3, true );
      }
    } else {
      // invalid access
      $this->register_admin_notices( CDBT . '-error', __('Invalid access this page.', CDBT), 3, true );
    }
    $this->admin_notices();
    
  }
  
  /**
   * Worker logic methods
   */
  
  // Page: cdbt_management_console | Tab: -
  public function do_cdbt_management_console() {
    // None at the moment
  }
  
  // Page: cdbt_options | Tab: general_setting
  public function do_cdbt_options_general_setting() {
    if ( 'update' === $_POST['action'] && !empty($_POST[$this->domain_name]) ) {
      
      $submit_options = $_POST[$this->domain_name];
      
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
      
      update_option( $this->domain_name, $updated_options );
      
      $this->register_admin_notices( CDBT . '-notice', __('Plugin options saved.', CDBT), 3, true );
    } else {
      $this->register_admin_notices( CDBT . '-error', __('Could not save options.', CDBT), 3, true );
    }
    
  }
  
  // Page: cdbt_options | Tab: debug
  public function do_cdbt_options_debug() {
    // None at the moment
  }
  
  // Page: cdbt_tables | Tab: (any)
  public function do_cdbt_tables_tabs() {
    
  }
  
  // Page: cdbt_shortcodes | Tab: (any)
  public function do_cdbt_shortcodes_tabs() {
    
  }
  
  // Page: cdbt_apis | Tab: (any)
  public function do_cdbt_apis_tabs() {
    
  }
  
}

endif; // end of class_exists()