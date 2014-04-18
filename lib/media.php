<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php';

$token = $_REQUEST['token'];
if (wp_verify_nonce($token, PLUGIN_SLUG . '_media')) {
	$mode = 'view';
} else if (wp_verify_nonce($token, PLUGIN_SLUG . '_download')) {
	$mode = 'download';
} else {
	file_not_found();
}
if ($mode == 'view' || $mode == 'download') {
	global $cdbt;
	if (!empty($cdbt)) {
		$id = $_REQUEST['id'];
		$filename = $_REQUEST['filename'];
		$data = $cdbt->get_data($cdbt->current_table, '*', array('ID' => $id), null, 1);
		foreach (array_shift($data) as $key => $val) {
			if (is_string($val) && strlen($val) > 24 && preg_match('/^a:\d:\{s:11:\"origin_file\"\;$/i', substr($val, 0, 24))) {
				$file_data = unserialize($val);
				if ($file_data['origin_file'] == $filename) {
					break;
				}
			}
		}
		if (isset($file_data) && !empty($file_data)) {
			$disp = $mode == 'view' ? 'inline' : 'attachment';
			$dl_filename = rawurldecode($file_data['origin_file']);
			header("Content-Disposition: {$disp}; filename=\"{$dl_filename}\"");
			header("Content-Length: {$file_data['file_size']}");
			header("Content-type: {$file_data['mine_type']}");
			echo $file_data['bin_data'];
		} else {
			file_not_found();
		}
	}
}

function file_not_found() {
	header('HTTP', true, 404);
}