<?php

namespace CustomDataBaseTables\Admin;


if ( !defined( 'CDBT' ) ) exit;

if ( !class_exists( 'CdbtAdmin' ) ) :

class CdbtAdmin {

  public static function instance() {
    
    static $instance = null;
    
    if ( null === $instance ) {
      $instance = new CdbtAdmin;
      $instance->setup_globals();
      $instance->init();
      $instance->setup_actions();
    }
    
    return $instance;
  }

  public function __construct() { /* Do nothing here */ }

  public function setup_globals() {
    // Global Object
    global $cdbt;
    $this->core = is_object($cdbt) && !empty($cdbt) ? $cdbt : \CustomDataBaseTables\Core\Cdbt::instance();
    
  }

  private function init() {
    
    // Capabilities
    $this->minimum_capability = apply_filters( 'cdbt_admin_minimum_capability', 'edit_posts' ); // -> Contributor
    $this->webmaster_capability = apply_filters( 'cdbt_admin_webmaster_capability', 'edit_pages' ); // -> Editor
    $this->maximum_capability = apply_filters( 'cdbt_admin_maximum_capability', 'activate_plugins' ); // -> Administrator, and Super Admin
    
    // Paths
    $this->admin_template_dir = apply_filters( 'cdbt_admin_template_dir', $this->core->plugin_dir . 'templates/admin/' );
    
  }

  private function setup_actions() {
    
    // Initial Action
    add_action( 'admin_init', array($this, 'admin_initialize') );
    
    // General Actions
    add_action( 'admin_menu', array($this, 'admin_menus') );
    
    // Add New Actions
    do_action( 'cdbt_get_admin_template', array($this, 'get_admin_template') );
    
    // Filters
    add_filter( 'plugin_action_links', array($this, 'modify_plugin_action_links'), 10, 2 );
    
  }

  public function admin_initialize() {
    register_setting( 'cdbt_management_console', $this->core->domain_name );
  }

  public function admin_menus() {
    $operating_capability = $this->minimum_capability;
    
    $menus = [];
    
    $menus[] = add_menu_page( 
      __('CDBT Management Console', $this->core->domain_name), 
      __('CDBT', $this->core->domain_name), 
      $operating_capability, 
      'cdbt_management_console', 
      array($this, 'admin_page_render'), 
      'dashicons-admin-generic', 
      55 // default is before appearance (55), or after tools (77), or after setting (85)
    );
    
    $menus[] = add_submenu_page( 
      'cdbt_management_console', 
      __('CDBT Tables Management', $this->core->domain_name), 
      __('Tables', $this->core->domain_name), 
      $operating_capability, 
      'cdbt_tables', 
      array($this, 'admin_page_render') 
    );
    
    $menus[] = add_submenu_page( 
      'cdbt_management_console', 
      __('CDBT Shortcodes Management', $this->core->domain_name), 
      __('Shortcodes', $this->core->domain_name), 
      $operating_capability, 
      'cdbt_shortcodes', 
      array($this, 'admin_page_render') 
    );
    
    $menus[] = add_submenu_page( 
      'cdbt_management_console', 
      __('CDBT APIs Management', $this->core->domain_name), 
      __('APIs', $this->core->domain_name), 
      $operating_capability, 
      'cdbt_apis', 
      array($this, 'admin_page_render') 
    );
    
    $menus[] = add_submenu_page( 
      'cdbt_management_console', 
      __('CDBT Plugin Options', $this->core->domain_name), 
      __('Plugin Options', $this->core->domain_name), 
      $operating_capability, 
      'cdbt_options', 
      array($this, 'admin_page_render') 
    );
    
    // Parsed QUERY_STRING is stored $this->query
    wp_parse_str( $_SERVER['QUERY_STRING'], $this->query );
    
    foreach ($menus as $menu) {
      add_action( 'admin_enqueue_scripts', array($this, 'admin_assets') );
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
        
        require_once( apply_filters( 'include_template-' . $this->query['page'], $template_file_path ) );
      }
      
    }
    
  }
  
  public function admin_assets() {
    // Fire this hook when register CSS and JavaScript to admin panel (on the all admin page)
    if (!array_key_exists('page', $this->query) || !preg_match('/^cdbt_.*$/iU', $this->query['page'])) 
      return;
    
    $assets = [
      'styles' => [
//        'cdbt-common-style' => [ $this->core->plugin_url . 'assets/css/styles.min.css', array(), $this->core->version, 'all' ], 
        'cdbt-admin-style' => [ $this->core->plugin_url . 'assets/css/admin-styles.css', true, $this->core->version, 'all' ], 
      ], 
      'scripts' => [
//        'cdbt-common-script' => [ $this->core->plugin_url . 'assets/js/scripts.min.js', array(), null, true ], 
        'cdbt-admin-script' => [ $this->core->plugin_url . 'assets/js/admin-scripts.js', array(), null, true ], 
        'jquery-ui-core' => null, 
        'jquery-ui-widget' => null, 
        'jquery-ui-mouse' => null, 
        'jquery-ui-position' => null, 
        'jquery-ui-sortable' => null, 
        'jquery-ui-autocomplete' => null, 
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
      printf( '<div class="plugin-meta"><span class="label label-info">Ver. %s</span></div>', $this->core->version );
    
    printf( "<script>jQuery(document).ready(function(\$){\$('li#toplevel_page_cdbt_management_console>ul.wp-submenu a.wp-first-item').text('%s');});</script>", __('Custom DB Tables', CDBT) );
  }

  public function admin_notices() {
    // Fire this hook when call to action of the admin notices (on the all admin pages)
    if (false !== get_transient( "{CDBT}-error" )) {
      $messages = get_transient( "{CDBT}-error" );
      $classes = 'error';
    } elseif (false !== get_transient( "{CDBT}-notice" )) {
      $messages = get_transient( "{CDBT}-notice" );
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
  
  private function register_admin_notices( $code="{CDBT}-error", $message, $expire_seconds=10, $is_init=false ) {
    if (!$this->core->errors || $is_init) 
      $this->core->errors = new \WP_Error();
    
    if (is_object($this->core->errors)) {
      $this->core->errors->add( $code, $message );
      set_transient( $code, $this->core->errors->get_error_messages(), $expire_seconds );
    }
    
    // return $this->core->errors;
  }
  
  public function modify_plugin_action_links( $links, $file ) {
    if (plugin_basename($this->core->plugin_main_file) !== $file) 
      return $links;
    
    if (false === $this->core->plugin_enabled) 
      return $links;
    
    $prepend_new_links = $append_new_links = array();
    
    $prepend_new_links['settings'] = sprintf(
      '<a href="%s">%s</a>', 
      add_query_arg([ 'page' => 'cdbt_management_console' ], admin_url('admin.php')), 
      esc_html__( 'Settings', $this->core->domain_name )
    );
    
    unset($links['edit']);
    
    $append_new_links['edit'] = sprintf(
      '<a href="%s">%s</a>', 
      add_query_arg([ 'file' => plugin_basename($this->core->plugin_main_file) ], admin_url('plugin-editor.php')), 
      esc_html__( 'Edit', $this->core->domain_name )
    );
    
    return array_merge($prepend_new_links, $links, $append_new_links);
  }

  /**
   * Controllers of admin pages for this plugin
   */
  public function admin_controller() {
    if (empty( $_POST )) 
      return;
    
    $options = get_option($this->core->domain_name);
    
    if (check_admin_referer( 'cdbt_management_console-' . $this->query['page'] )) {
      switch ($this->query['page']) {
        case 'cdbt_options': 
          if ( 'update' === $_POST['action'] && !empty($_POST[$this->core->domain_name]) ) {
            $submit_options = $_POST[$this->core->domain_name];
            
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
            
            $updated_options = array_merge($options, $submit_options);
            
            update_option( $this->core->domain_name, $updated_options );
            
            $this->register_admin_notices( "{CDBT}-notice", __('Plugin options saved.', CDBT), 3, true );
          } else {
            $this->register_admin_notices( "{CDBT}-error", __('Could not save options.', CDBT), 3, true );
          }
          $this->admin_notices();
          break;
        case '': 
          break;
        default:
          break;
      }
    }
  }
  
  
}

endif; // end of class_exists()