<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php';

$token = $_REQUEST['token'];
if (wp_verify_nonce($token, PLUGIN_SLUG . '_media')) {
	$mode = 'view';
} else if (wp_verify_nonce($token, PLUGIN_SLUG . '_download')) {
	$mode = 'download';
} else if (wp_verify_nonce($token, PLUGIN_SLUG . '_csv_tmpl_download')) {
	$mode = 'csv_tmpl_download';
} else if (wp_verify_nonce($token, PLUGIN_SLUG . '_csv_export')) {
	$mode = 'csv_export';
} else {
	cdbt_file_not_found();
}
if ($mode == 'view' || $mode == 'download') {
	global $cdbt;
	if (!empty($cdbt)) {
		$id = $_REQUEST['id'];
		$filename = $_REQUEST['filename'];
		$table = (isset($_REQUEST['table']) && !empty($_REQUEST['table'])) ? $_REQUEST['table'] : $cdbt->current_table;
		$data = $cdbt->get_data($table, '*', array('ID' => $id), null, 1);
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
			cdbt_file_not_found();
		}
	}
} else if ($mode == 'csv_tmpl_download') {
	global $cdbt;
	if (!empty($cdbt)) {
		$table_name = $_REQUEST['tablename'];
		list($result, $data) = $cdbt->export_table($table_name, true);
		if ($result) {
			$csv = array();
			foreach ($data as $row) {
				$line = array();
				foreach ($row as $val) {
					if (function_exists('mb_convert_encoding')) {
						$val = mb_convert_encoding($val, 'SJIS', 'UTF-8, UTF-7, ASCII, EUC-JP,SJIS, eucJP-win, SJIS-win, JIS, ISO-2022-JP, ISO-8859-1');
					}
					$line[] = '"'. $val .'"';
				}
				$csv[] = implode(',', $line);
			}
			$csv = implode("\r\n", $csv);
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename=' . $table_name . '.csv');
			echo $csv;
		} else {
			header('Location: ' . $_SERVER['HTTP_REFERER']);
		}
	}
} else if ($mode == 'csv_export') {
	global $cdbt;
	if (!empty($cdbt)) {
		$table_name = $_REQUEST['tablename'];
		list($result, $data) = $cdbt->export_table($table_name, false);
		if ($result) {
			$csv = array();
			foreach ($data as $row) {
				$line = array();
				foreach ($row as $val) {
					if (function_exists('mb_convert_encoding')) {
						$val = mb_convert_encoding($val, 'SJIS', 'UTF-8, UTF-7, ASCII, EUC-JP,SJIS, eucJP-win, SJIS-win, JIS, ISO-2022-JP, ISO-8859-1');
					}
					$line[] = '"'. $val .'"';
				}
				$csv[] = implode(',', $line);
			}
			$csv = implode("\r\n", $csv);
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename=' . $table_name . '.csv');
			echo $csv;
		} else {
			header('Location: ' . $_SERVER['HTTP_REFERER']);
		}
	}
}

function cdbt_file_not_found() {
	header('HTTP', true, 404);
}