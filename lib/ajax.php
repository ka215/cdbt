<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php';

$token = $_POST['token'];
if (!wp_verify_nonce($token, PLUGIN_SLUG . '_ajax')) {
	die(__('Invalid access!', PLUGIN_SLUG));
} else {
	global $cdbt;
	$mode = $_POST['mode'];
	switch($mode) {
		case 'download': 
			$id = $_POST['id'];
			$table = (isset($_POST['table']) && !empty($_POST['table'])) ? $_POST['table'] : $cdbt->current_table;
			$data = $cdbt->get_data($table, '*', array('ID' => $id), null, 1);
			$binary_files = array();
			foreach (array_shift($data) as $key => $val) {
				if (is_string($val) && strlen($val) > 24 && preg_match('/^a:\d:\{s:11:\"origin_file\"\;$/i', substr($val, 0, 24))) {
					$binary_files[] = array_merge(array('ID' => $id), unserialize($val));
				}
			}
			if (!empty($binary_files)) {
				$response = '<ul class="download-files">';
				foreach ($binary_files as $binary_file) {
					$response .= sprintf('<li><a href="%s?id=%s&filename=%s&table=%s&token=%s">%s</a></li>', plugins_url(PLUGIN_SLUG) . '/lib/media.php', $binary_file['ID'], $binary_file['origin_file'], $table, wp_create_nonce(PLUGIN_SLUG . '_download'), rawurldecode($binary_file['origin_file']));
				}
				$response .= '</ul>';
				echo $response;
			} else {
				_e('No binary data.', PLUGIN_SLUG);
			}
			break;
		default: 
			die(__('Invalid access!', PLUGIN_SLUG));
			break;
	}
}