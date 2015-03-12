<?php
class CustomDataBaseTables_Media extends CustomDatabaseTables {
	
	private static $instance;
	
	public static function instance() {
		if (isset(self::$instance)) 
			return self::$instance;
		
		self::$instance = new CustomDataBaseTables_Media;
		self::$instance->init();
		return self::$instance;
	}
	
//	private function __construct() {
	public function __construct() {
		// Do nothing
	}
	
	protected function init() {
		add_action('wp_ajax_cdbt_media', array(&$this, 'cdbt_media'));
	}
	
	public function cdbt_media() {
		global $cdbt;
		$token = $_REQUEST['token'];
		if (wp_verify_nonce($token, CDBT_PLUGIN_SLUG . '_media')) {
			$mode = 'view';
		} else if (wp_verify_nonce($token, CDBT_PLUGIN_SLUG . '_download')) {
			$mode = 'download';
		} else if (wp_verify_nonce($token, CDBT_PLUGIN_SLUG . '_csv_tmpl_download')) {
			$mode = 'csv_tmpl_download';
		} else if (wp_verify_nonce($token, CDBT_PLUGIN_SLUG . '_csv_export')) {
			$mode = 'csv_export';
		} else {
			cdbt_file_not_found();
		}
		if ($mode == 'view' || $mode == 'download') {
			$id = $_REQUEST['id'];
			$filename = $_REQUEST['filename'];
			$table = (isset($_REQUEST['table']) && !empty($_REQUEST['table'])) ? $_REQUEST['table'] : $cdbt->current_table;
			$data = $cdbt->get_data($table, '*', array('ID' => $id), null, 1);
			if (is_array($data) && !empty($data)) {
				$data = array_shift($data);
				foreach ($data as $key => $val) {
					if (is_string($val) && strlen($val) > 24 && preg_match('/^a:\d:\{s:11:\"origin_file\"\;$/i', substr($val, 0, 24))) {
						$file_data = unserialize($val);
						if ($file_data['origin_file'] == $filename) {
							break;
						}
					}
				}
			}
			if (isset($file_data) && !empty($file_data)) {
				$disp = $mode == 'view' ? 'inline' : 'attachment';
				$dl_filename = rawurldecode($file_data['origin_file']);
				header("Content-Disposition: {$disp}; filename=\"{$dl_filename}\"");
				header("Content-Length: {$file_data['file_size']}");
				header("Content-type: {$file_data['mine_type']}");
				die($file_data['bin_data']);
			} else {
				cdbt_file_not_found();
			}
		} else if ($mode == 'csv_tmpl_download') {
			$table_name = $_REQUEST['tablename'];
			list($result, $data) = $cdbt->export_table($table_name, true);
			if ($result) {
				$export_data = array();
				foreach ($data as $row) {
					$headers = array();
					foreach ($row as $val) {
						if (function_exists('mb_convert_encoding')) {
							$val = mb_convert_encoding($val, 'SJIS', 'UTF-8, UTF-7, ASCII, EUC-JP,SJIS, eucJP-win, SJIS-win, JIS, ISO-2022-JP, ISO-8859-1');
						}
						$headers[] = $val;
					}
					$export_data[] = $headers;
				}
				$this->export_csv($export_data, $table_name . '.csv', true);
				die();
			} else {
				header('Location: ' . $_SERVER['HTTP_REFERER']);
			}
		} else if ($mode == 'csv_export') {
			$table_name = $_REQUEST['tablename'];
			list($result, $data) = $cdbt->export_table($table_name, false);
			if ($result) {
				$export_data = array();
				foreach ($data as $row) {
					$line = array();
					foreach ($row as $val) {
						$is_binary = (preg_match('/^a:\d:\{s:11:\"origin_file\"\;$/i', substr($val, 0, 24))) ? true : false;
						if ($is_binary) {
							eval('$tmp = array(' . trim(preg_replace('/(a:\d+:{|(|;)s:\d+:|(|;)i:|"$)/', ",", substr($val, 0, strpos($val, 'bin_data'))), ',,') . ');');
							foreach ($tmp as $i => $buf) {
								if ($buf == 'origin_file') $origin_file = $tmp[intval($i)+1];
								if ($buf == 'mine_type') $mine_type = $tmp[intval($i)+1];
								if ($buf == 'file_size') $file_size = $tmp[intval($i)+1];
							}
							$val = rawurldecode($origin_file);
						}
						if (function_exists('mb_convert_encoding')) {
							$val = mb_convert_encoding($val, 'SJIS', 'UTF-8, UTF-7, ASCII, EUC-JP,SJIS, eucJP-win, SJIS-win, JIS, ISO-2022-JP, ISO-8859-1');
						}
						$line[] = $val;
					}
					$export_data[] = $line;
				}
				$this->export_csv($export_data, $table_name . '.csv', true);
				die();
			} else {
				header('Location: ' . $_SERVER['HTTP_REFERER']);
			}
		}
		
	}
	
	public function cdbt_file_not_found() {
		header('HTTP', true, 404);
	}
	
	public function export_csv($data, $filename, $attachment = false) {
		if ($attachment) {
			header( 'Content-Type: application/octet-stream' );
			header( 'Content-Disposition: attachment; filename=' . $filename );
			$fp = fopen('php://output', 'w');
		} else {
			$fp = fopen($filename, 'w');
		}
		
		foreach ($data as $line) {
			fputcsv($fp, $line);
		}
		
		fclose($fp);
	}
	
}
