<?php

add_shortcode('cdbt-view', 'cdbt_display_view_table');
add_shortcode('cdbt-entry', 'cdbt_display_entry_table');
add_shortcode('cdbt-edit', 'cdbt_display_edit_table');

function cdbt_display_view_table($atts, $content=''){
	extract(shortcode_atts(array(
		'table' => '', 
		'bootstrap_style' => true, 
		'display_title' => true, 
		'display_search' => true, 
		'display_list_num' => true, 
		'exclude_cols' => '', // a column name or Comma-separated columns name
		'add_class' => '', 
	), $atts));
	global $cdbt;
	if (empty($table) || !$cdbt->check_table_exists($table)) 
		return __('No table specified', PLUGIN_SLUG);
	if (!cdbt_check_current_table_role('view', $table)) 
		return __('You&apos;ve denied permission to view this table.', PLUGIN_SLUG);
	
	if (cdbt_get_boolean($bootstrap_style)) {
		wp_register_style('cdbt-common-style', $cdbt->dir_url . '/assets/css/cdbt-main.min.css', array(), $cdbt->version, 'all');
		wp_enqueue_style('cdbt-common-style');
		wp_register_script('cdbt-common-script', $cdbt->dir_url . '/assets/js/scripts.min.js', array(), null, false);
		wp_enqueue_script('cdbt-common-script');
	}
	add_action('wp_footer', 'cdbt_create_javascript', 9999);
	
	$options = array(
		'bootstrap_style' => cdbt_get_boolean($bootstrap_style), 
		'display_title' => cdbt_get_boolean($display_title), 
		'display_search' => cdbt_get_boolean($display_search), 
		'display_list_num' => cdbt_get_boolean($display_list_num), 
		'exclude_cols' => !empty($exclude_cols) ? explode(',', $exclude_cols) : array(), 
		'add_class' => $add_class, 
	);
	require_once PLUGIN_TMPL_DIR . DS . 'cdbt-public-list.php';
	$mode = 'list';
	$_cdbt_token = wp_create_nonce(PLUGIN_SLUG .'_'. $mode);
	
	return cdbt_render_list_page($table, $mode, $_cdbt_token, $options);
}

function cdbt_display_entry_table($atts, $content=''){
	extract(shortcode_atts(array(
		'table' => '', 
		'bootstrap_style' => true, 
		'display_title' => true, 
		'hidden_cols' => '', // a column name or Comma-separated columns name
		'add_class' => '', 
	), $atts));
	global $cdbt;
	if (empty($table) || !$cdbt->check_table_exists($table)) 
		return __('No table specified', PLUGIN_SLUG);
	if (!cdbt_check_current_table_role('input', $table)) 
		return __('You&apos;ve denied permission to view this table.', PLUGIN_SLUG);
	
	if (cdbt_get_boolean($bootstrap_style)) {
		wp_register_style('cdbt-common-style', $cdbt->dir_url . '/assets/css/cdbt-main.min.css', array(), $cdbt->version, 'all');
		wp_enqueue_style('cdbt-common-style');
		wp_register_script('cdbt-common-script', $cdbt->dir_url . '/assets/js/scripts.min.js', array(), null, false);
		wp_enqueue_script('cdbt-common-script');
	}
	add_action('wp_footer', 'cdbt_create_javascript', 9999);
	
	$options = array(
		'bootstrap_style' => cdbt_get_boolean($bootstrap_style), 
		'display_title' => cdbt_get_boolean($display_title), 
		'hidden_cols' => !empty($hidden_cols) ? explode(',', $hidden_cols) : array(), 
		'add_class' => $add_class, 
	);
	require_once PLUGIN_TMPL_DIR . DS . 'cdbt-public-input.php';
	$mode = 'input';
	$_cdbt_token = wp_create_nonce(PLUGIN_SLUG .'_'. $mode);
	
	return cdbt_render_input_page($table, $mode, $_cdbt_token, $options);
}

function cdbt_display_edit_table($atts, $content=''){
	extract(shortcode_atts(array(
		'table' => '', 
		'entry_page' => '', // integer of post ID or strings of post name
		'bootstrap_style' => true, 
		'display_title' => true, 
		'display_list_num' => true, 
		'exclude_cols' => '', // a column name or Comma-separated columns name
		'add_class' => '', 
	), $atts));
	global $cdbt;
	if (empty($table) || !$cdbt->check_table_exists($table)) 
		return __('No table specified', PLUGIN_SLUG);
	if (!cdbt_check_current_table_role('edit', $table)) 
		return __('You&apos;ve denied permission to view this table.', PLUGIN_SLUG);
	
	if (cdbt_get_boolean($bootstrap_style)) {
		wp_register_style('cdbt-common-style', $cdbt->dir_url . '/assets/css/cdbt-main.min.css', array(), $cdbt->version, 'all');
		wp_enqueue_style('cdbt-common-style');
		wp_register_script('cdbt-common-script', $cdbt->dir_url . '/assets/js/scripts.min.js', array(), null, false);
		wp_enqueue_script('cdbt-common-script');
	}
	add_action('wp_footer', 'cdbt_create_javascript', 9999);
	
	$options = array(
		'bootstrap_style' => cdbt_get_boolean($bootstrap_style), 
		'display_title' => cdbt_get_boolean($display_title), 
		'display_list_num' => cdbt_get_boolean($display_list_num), 
		'exclude_cols' => !empty($exclude_cols) ? explode(',', $exclude_cols) : array(), 
		'entry_page' => $entry_page, 
		'add_class' => $add_class, 
	);
	require_once PLUGIN_TMPL_DIR . DS . 'cdbt-public-edit.php';
	$mode = 'edit';
	$_cdbt_token = wp_create_nonce(PLUGIN_SLUG .'_'. $mode);
	
	return cdbt_render_edit_page($table, $mode, $_cdbt_token, $options);
}
