<?php

namespace CustomDataBaseTables\Lib;

if ( !defined( 'CDBT' ) ) exit;

/**
 * Instance factory for this plugin
 *
 * @since v2.0.0
 */
function factory( $type='set_global' ) {
  if (is_admin()) {
    $call_class = __NAMESPACE__ . '\CdbtAdmin';
  } else {
    $call_class = __NAMESPACE__ . '\CdbtFrontend';
  }
  
  if (isset($type) && $type != 'set_global' ) {
    
    return $call_class::instance();
    
  } else {
    
    global $cdbt;
    $cdbt = $call_class::instance();
    
  }
  
}

$plugin_main_filepath = str_replace('lib/', 'cdbt.php', plugin_dir_path(__FILE__));

/**
 * Utility: Action hook is fired at the time this plugin has activated
 */
function plugin_activate() {
  $message = sprintf(__('Function called: %s; %s', CDBT), __FUNCTION__, __('Custom DataBase Tables plugin has activated.', CDBT));
  logger( $message );
  
  // as you fun
}
register_activation_hook( $plugin_main_filepath, __NAMESPACE__ . '\\plugin_activate' );

/**
 * Utility: Action hook is fired at the time this plugin was deactivation
 */
function plugin_deactivation() {
  $message = sprintf(__('Function called: %s; %s', CDBT), __FUNCTION__, __('Custom DataBase Tables plugin has been deactivation.', CDBT));
  logger( $message );
  
  // as you fun
}
register_deactivation_hook( $plugin_main_filepath, __NAMESPACE__ . '\\plugin_deactivation' );

/**
 * Utility: Action hook is fired at the time this plugin was deactivation
 */
function plugin_uninstall() {
  if ( !current_user_can( 'activate_plagins' ) ) 
    return;
  check_admin_referer( 'bulk-plugins' );
  
  if ( __FILE__ != WP_UNINSTALL_PLUGIN ) 
    return;
  
  $message = sprintf(__('Function called: %s; %s', CDBT), __FUNCTION__, __('Custom DataBase Tables plugin uninstall now.', CDBT));
  logger( $message, 3, 'C:\xampp\htdocs\v2.ka2.org\wp-content\plugins\uninstall.log' );
  die();
/*
$option_name = defined('CDBT') ? CDBT : 'custom-database-tables';
$nmp_options = get_option($option_name);
if (isset($nmp_options['uninstall_options'])) {
	$is_delete = $nmp_options['uninstall_options'];
} else {
	$is_delete = false;
}

if (!is_multisite()) {
	if ($is_delete) {
		delete_option($option_name);
		delete_option($option_name . '_previous_revision_backup');
	}
} else {
	global $wpdb;
	$blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
	$original_blog_id = get_current_blog_id();
	foreach ($blog_ids as $blog_id) {
		switch_to_blog($blog_id);
		if ($is_delete) {
			delete_option($option_name);
			delete_option($option_name . '_previous_revision_backup');
		}
	}
	switch_to_blog($original_blog_id);
	
	if ($is_delete) {
		delete_site_option($option_name);
		delete_site_option($option_name . '_previous_revision_backup');
	}
}
*/
}
register_uninstall_hook( $plugin_main_filepath, __NAMESPACE__ . '\\plugin_uninstall' );

unset($plugin_main_filepath);
