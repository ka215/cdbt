<?php
if ($_SERVER['SCRIPT_FILENAME'] == __FILE__) die();

if (is_admin()) {
	if (!check_admin_referer(self::DOMAIN .'_admin', '_cdbt_token')) 
		die(__('access is not from admin panel!', self::DOMAIN));
} else {
	die(__('Invild access!', self::DOMAIN));
}

$inherit_values = array();
foreach ($_REQUEST as $key => $value) {
	if (preg_match('/^(page|mode|_cdbt_token|action|handle|section|_wp_http_referer)$/', $key)) {
		${$key} = $value;
//var_dump('$'.$key.'="'.$value.'";'."\n");
	} else {
		$inherit_values[$key] = $value;
	}
}
$information_html = $contents_html = $nav_tabs_list = $tabs_content = null;
$tabs = array(
	'general' => false, 
	'create' => false, 
	'tables' => false, 
);
//var_dump($inherit_values);
//var_dump(get_option(self::DOMAIN . '_current_table'));
//var_dump($this->options);

if (wp_verify_nonce($_cdbt_token, self::DOMAIN .'_'. $mode)) {
	if (!isset($action) || empty($action) || !array_key_exists($action, $tabs)) 
		$action = 'general';
	$tabs[$action] = true;
	global $wpdb;
	
	switch ($action) {
		case 'general': 
			if (isset($handle) && compare_var($handle, 'save')) {
				foreach ($inherit_values as $key => $value) {
					if (preg_match('/^use_wp_prefix$/', $key)) {
						$this->options[$key] = get_boolean($value);
					} else {
						$this->options[$key] = $value;
					}
				}
				if (update_option(self::DOMAIN, $this->options)) {
					$msg = array('success', __('Completed successful to save option setting.', self::DOMAIN));
				} else {
					$msg = array('warning', __('Failed to save option setting. Please note it is not saved if there is no change.', self::DOMAIN));
				}
			}
			break;
		case 'create': 
			if ($handle == 'create-table') {
				if ($section == 'confirm') {
					$create_full_table_name = null;
					if (compare_var(empty($inherit_values['naked_table_name']), true)) {
						$msg = array('warning', __('Table name is empty.', self::DOMAIN));
					} else {
						$create_full_table_name = (get_boolean($inherit_values['use_wp_prefix_for_newtable']) ? $wpdb->prefix : '') . trim($inherit_values['naked_table_name']);
					}
					if (compare_var(empty($create_full_table_name), true)) {
						$msg = array('warning', __('Table name is empty.', self::DOMAIN));
					} else {
							if (preg_match('/^([a-zA-Z0-9_\-]+)$/', $naked_table_name, $matches)) {
								if ($this->compare_reservation_tables($matches[1])) {
									$message = __('Table name is invalid. Table name is not allowed that use reserved name on WordPress.', self::DOMAIN);
									$msg_type = 'warning';
								}
								if ($create_full_table_name == $wpdb->prefix) {
									$message = __('Table name is invalid. Table name of the only prefix is not allowed.', self::DOMAIN);
									$msg_type = 'warning';
								}
								if (strlen($create_full_table_name) > 64) {
									$message = __('Table name is invalid. Maximum string length of the table name is 64 bytes.', self::DOMAIN);
									$msg_type = 'warning';
								}
								
							} else {
								$message = __('Table name is invalid. Characters that can not be used in table name is included.', self::DOMAIN);
								$msg_type = 'warning';
							}
						
					}
				} else {
				}
			}
			break;
		case 'tables': 
var_dump($handle);
var_dump($section);
			break;
	}
	// view tabs
	$nav_tabs_html = '<ul class="nav nav-tabs">%s</ul><!-- /.nav-tabs -->';
	$tabs_content_html = '<div class="tab-content">%s</div><!-- /.tab-content -->';
	foreach ($tabs as $tab_name => $active) {
		$nav_active_class = ($active) ? 'active' : '';
		$nav_tabs_list .= sprintf('<li class="%s"><a href="#cdbt-%s" data-toggle="tab">%s</a></li>', $nav_active_class, $tab_name, translate_tab_name($tab_name));
		$tabs_content .= sprintf('<div class="tab-pane %s" id="cdbt-%s">%s</div>', $nav_active_class, $tab_name, create_tab_content($tab_name, $_cdbt_token, $inherit_values));
	}
	if (!empty($msg)) {
		$cls_btn = $msg[0] == 'success' ? '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>' : '';
		$information_html = sprintf('<div class="alert alert-%s tab-header">%s%s</div>', $msg[0], $msg[1], $cls_btn);
	}
} else {
	$_cdbt_token = wp_create_nonce(self::DOMAIN . '_admin');
	create_console_menu($_cdbt_token);
	
	$information_html = sprintf('<div class="alert alert-danger">%s</div>', __('Invild access!', self::DOMAIN));
}

create_console_menu($_cdbt_token);

$contents_base = (!empty($msg) && $msg[0] == 'success') ? $nav_tabs_html . $information_html . $tabs_content_html : $nav_tabs_html . $tabs_content_html;
$contents_html = sprintf($contents_base, $nav_tabs_list, $tabs_content);
printf('<div class="tab-container">%s</div>', $contents_html);

create_console_footer((!empty($msg) && $msg[0] != 'success') ? $information_html : '');

function create_tab_content($tab_name, $nonce, $inherit_values=null) {
	global $wpdb, $cdbt;
	$cdbt_options = get_option(PLUGIN_SLUG);
	$controller_table = $cdbt_options['tables'][0]['table_name'];
	$content_html = null;
	$nonce_field = wp_nonce_field(PLUGIN_SLUG .'_admin', '_cdbt_token', true, false);
	switch ($tab_name) {
		case 'general': 
			// save to plugin option.
			require_once PLUGIN_TMPL_DIR . DS . 'cdbt-admin-general.php';
			break;
		case 'create': 
			// create database table.
			require_once PLUGIN_TMPL_DIR . DS . 'cdbt-admin-create.php';
			break;
		case 'tables': 
			// enable tables list
			require_once PLUGIN_TMPL_DIR . DS . 'cdbt-admin-tables.php';
			break;
		default: 
			break;
	}
	return $content_html;
}

function translate_tab_name($tab_name){
	$translate_tab_name = array(
		'general' => __('General setting', PLUGIN_SLUG), 
		'create' => __('Create table', PLUGIN_SLUG), 
		'tables' => __('Enable tables list', PLUGIN_SLUG), 
	);
	return $translate_tab_name[$tab_name];
}
