<?php

$cdbt_shortcodes = array(
	'cdbt-view' => 'cdbt_display_view_table', 
	'cdbt-entry' => 'cdbt_display_entry_table', 
	'cdbt-edit' => 'cdbt_display_edit_table', 
	'cdbt-extract' => 'cdbt_display_extract_data', 
);
foreach ($cdbt_shortcodes as $shortcode_name => $function_name) {
	add_shortcode($shortcode_name, $function_name);
}

function cdbt_display_view_table($atts, $content='') {
	extract(shortcode_atts(array(
		'table' => '', 
		'bootstrap_style' => true, 
		'display_title' => true, 
		'display_search' => true, 
		'display_list_num' => true, 
		'enable_sort' => true, 
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
		wp_register_style('cdbt-custom-style', $cdbt->dir_url . '/assets/css/cdbt-style.css', array(), $cdbt->version, 'all');
		wp_enqueue_style('cdbt-common-style');
		wp_enqueue_style('cdbt-custom-style');
		wp_register_script('cdbt-common-script', $cdbt->dir_url . '/assets/js/scripts.min.js', array(), null, false);
		wp_enqueue_script('cdbt-common-script');
	}
	add_action('wp_footer', 'cdbt_create_javascript', 9999);
	
	$options = array(
		'bootstrap_style' => cdbt_get_boolean($bootstrap_style), 
		'display_title' => cdbt_get_boolean($display_title), 
		'display_search' => cdbt_get_boolean($display_search), 
		'display_list_num' => cdbt_get_boolean($display_list_num), 
		'enable_sort' => cdbt_get_boolean($enable_sort), 
		'exclude_cols' => !empty($exclude_cols) ? explode(',', $exclude_cols) : array(), 
		'add_class' => $add_class, 
	);
	require_once PLUGIN_TMPL_DIR . DS . 'cdbt-public-list.php';
	$mode = 'list';
	$_cdbt_token = wp_create_nonce(PLUGIN_SLUG .'_'. $mode);
	
	return cdbt_render_list_page($table, $mode, $_cdbt_token, $options);
}

function cdbt_display_entry_table($atts, $content='') {
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
		wp_register_style('cdbt-custom-style', $cdbt->dir_url . '/assets/css/cdbt-style.css', array(), $cdbt->version, 'all');
		wp_enqueue_style('cdbt-common-style');
		wp_enqueue_style('cdbt-custom-style');
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

function cdbt_display_edit_table($atts, $content='') {
	extract(shortcode_atts(array(
		'table' => '', 
		'entry_page' => '', // integer of post ID or strings of post name
		'bootstrap_style' => true, 
		'display_title' => true, 
		'display_list_num' => true, 
		'enable_sort' => true, 
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
		wp_register_style('cdbt-custom-style', $cdbt->dir_url . '/assets/css/cdbt-style.css', array(), $cdbt->version, 'all');
		wp_enqueue_style('cdbt-common-style');
		wp_enqueue_style('cdbt-custom-style');
		wp_register_script('cdbt-common-script', $cdbt->dir_url . '/assets/js/scripts.min.js', array(), null, false);
		wp_enqueue_script('cdbt-common-script');
	}
	add_action('wp_footer', 'cdbt_create_javascript', 9999);
	
	$options = array(
		'bootstrap_style' => cdbt_get_boolean($bootstrap_style), 
		'display_title' => cdbt_get_boolean($display_title), 
		'display_list_num' => cdbt_get_boolean($display_list_num), 
		'enable_sort' => cdbt_get_boolean($enable_sort), 
		'exclude_cols' => !empty($exclude_cols) ? explode(',', $exclude_cols) : array(), 
		'entry_page' => $entry_page, 
		'add_class' => $add_class, 
	);
	require_once PLUGIN_TMPL_DIR . DS . 'cdbt-public-edit.php';
	$mode = 'edit';
	$_cdbt_token = wp_create_nonce(PLUGIN_SLUG .'_'. $mode);
	
	return cdbt_render_edit_page($table, $mode, $_cdbt_token, $options);
}

function cdbt_display_extract_data($atts, $content='') {
	extract(shortcode_atts(array(
		'table' => '', 
		'bootstrap_style' => true, 
		'display_index_row' => true, 
		'narrow_keyword' => array(), // example: "keyword1,keyword2,..." is find_data() or "col1:keyword1,col2:keyword2,..." is get_data()
		'display_cols' => array(), // example: "col1,col2,col3,..."
		'order_cols' => array(), // example: "col3,col2,col1,..."
		'sort_order' => array('created'=>'desc'), // eq. hash example: "updated:desc,ID:asc,..."
		'limit_items' => 5, 
		'add_class' => '', 
	), $atts));
	global $cdbt;
	if (empty($table) || !$cdbt->check_table_exists($table)) 
		return __('No table specified', PLUGIN_SLUG);
	if (!cdbt_check_current_table_role('view', $table)) 
		return __('You&apos;ve denied permission to view this table.', PLUGIN_SLUG);
	
	if (cdbt_get_boolean($bootstrap_style)) {
		wp_register_style('cdbt-common-style', $cdbt->dir_url . '/assets/css/cdbt-main.min.css', array(), $cdbt->version, 'all');
		wp_register_style('cdbt-custom-style', $cdbt->dir_url . '/assets/css/cdbt-style.css', array(), $cdbt->version, 'all');
		wp_enqueue_style('cdbt-common-style');
		wp_enqueue_style('cdbt-custom-style');
		wp_register_script('cdbt-common-script', $cdbt->dir_url . '/assets/js/scripts.min.js', array(), null, false);
		wp_enqueue_script('cdbt-common-script');
	}
	add_action('wp_footer', 'cdbt_create_javascript', 9999);
	
	if (!empty($narrow_keyword) && !is_array($narrow_keyword)) {
		$tmp_ary = explode(',', $narrow_keyword);
		$narrow_keyword = array();
		foreach ($tmp_ary as $tmp_val) {
			$parse_val = explode(':', $tmp_val);
			if (count($parse_val) > 1) {
				$col_name = trim(trim(stripcslashes($parse_val[0])), "\"' ");
				$keyword = trim(trim(stripcslashes($parse_val[1])), "\"' ");
				if (!empty($col_name) && !empty($keyword)) 
					$narrow_keyword[$col_name] = $keyword;
			} else {
				$keyword = trim(trim(stripcslashes($parse_val[0])), "\"' ");
				if (empty($keyword)) 
					$narrow_keyword[] = $keyword;
			}
		}
	}
	if (empty($narrow_keyword)) 
		$narrow_keyword = array();
	
	if (!empty($sort_order) && !is_array($sort_order)) {
		$tmp_ary = explode(',', $sort_order);
		$sort_order = array();
		foreach ($tmp_ary as $tmp_val) {
			list($col_name, $order_str) = explode(':', $tmp_val);
			$col_name = trim(trim(stripcslashes($col_name)), "\"' ");
			$order_str = trim(trim(stripcslashes($order_str)), "\"' ");
			if (!empty($col_name) && !empty($order_str)) 
				$sort_order[$col_name] = $order_str;
		}
	}
	if (empty($sort_order)) 
		$sort_order = array('created'=>'desc');
	
	$options = array(
		'bootstrap_style' => cdbt_get_boolean($bootstrap_style), 
		'display_index_row' => cdbt_get_boolean($display_index_row), 
		'narrow_keyword' => $narrow_keyword, 
		'display_cols' => !empty($display_cols) ? explode(',', $display_cols) : array(), 
		'order_cols' => !empty($order_cols) ? explode(',', $order_cols) : array(), 
		'sort_order' => $sort_order, 
		'limit_items' => intval($limit_items), 
		'add_class' => $add_class, 
	);
	require_once PLUGIN_TMPL_DIR . DS . 'cdbt-public-contents.php';
	$mode = 'extract';
	$_cdbt_token = wp_create_nonce(PLUGIN_SLUG .'_'. $mode);
	
	return cdbt_render_contents($table, $mode, $_cdbt_token, $options);
}
