<?php

add_shortcode('cdbt-view', 'display_view_table');
add_shortcode('cdbt-entry', 'display_entry_table');
add_shortcode('cdbt-edit', 'display_edit_table');

function display_view_table($atts, $content=''){
	extract(shortcode_atts(array(
		'table' => '', 
		'bootstrap_style' => true, 
		'display_title' => true, 
		'display_search' => true, 
	), $atts));
	global $cdbt;
	if (empty($table) || !$cdbt->check_table_exists($table)) 
		return __('No table specified', PLUGIN_SLUG);
	if (!check_current_table_role('view', $table)) 
		return __('You&apos;ve denied permission to view this table.', PLUGIN_SLUG);
	
	if (get_boolean($bootstrap_style)) {
		wp_enqueue_style('cdbt_common_style', $cdbt->dir_url . '/assets/css/cdbt-main.min.css', false, $cdbt->version, 'all');
		wp_enqueue_script('cdbt_common_script', $cdbt->dir_url . '/assets/js/scripts.min.js', null, null, false);
	}
	$options = array(
		'display_title' => get_boolean($display_title), 
		'display_search' => get_boolean($display_search), 
	);
	require_once PLUGIN_TMPL_DIR . DS . 'cdbt-public-list.php';
	$mode = 'list';
	$_cdbt_token = wp_create_nonce(PLUGIN_SLUG .'_'. $mode);
	
	return render_list_page($table, $mode, $_cdbt_token, $options);
}

function display_entry_table($atts, $content=''){
	extract(shortcode_atts(array(
		'table' => '', 
		'bootstrap_style' => true, 
	), $atts));
	global $cdbt;
	if (empty($table) || !$cdbt->check_table_exists($table)) 
		return __('No table specified', PLUGIN_SLUG);
	if (!check_current_table_role('view', $table)) 
		return __('You&apos;ve denied permission to view this table.', PLUGIN_SLUG);
	
	if (get_boolean($bootstrap_style)) {
		wp_enqueue_style('cdbt_common_style', $cdbt->dir_url . '/assets/css/cdbt-main.min.css', false, $cdbt->version, 'all');
		wp_enqueue_script('cdbt_common_script', $cdbt->dir_url . '/assets/js/scripts.min.js', null, null, false);
	}
	require_once PLUGIN_TMPL_DIR . DS . 'cdbt-public-entry.php';
	$mode = 'list';
	$_cdbt_token = wp_create_nonce(PLUGIN_SLUG .'_'. $mode);
	
	return render_input_page($table, $mode, $_cdbt_token, $options);
}

function display_edit_table($atts, $content=''){
	extract(shortcode_atts(array(
		'table' => '', 
		'bootstrap_style' => true, 
	), $atts));
	global $cdbt;
	if (empty($table) || !$cdbt->check_table_exists($table)) 
		return __('No table specified', PLUGIN_SLUG);
	if (!check_current_table_role('view', $table)) 
		return __('You&apos;ve denied permission to view this table.', PLUGIN_SLUG);
	
	if (get_boolean($bootstrap_style)) {
		wp_enqueue_style('cdbt_common_style', $cdbt->dir_url . '/assets/css/cdbt-main.min.css', false, $cdbt->version, 'all');
		wp_enqueue_script('cdbt_common_script', $cdbt->dir_url . '/assets/js/scripts.min.js', null, null, false);
	}
	require_once PLUGIN_TMPL_DIR . DS . 'cdbt-public-edit.php';
	$mode = 'edit';
	$_cdbt_token = wp_create_nonce(PLUGIN_SLUG .'_'. $mode);
	
	return render_input_page($table, $mode, $_cdbt_token, $options);
}
