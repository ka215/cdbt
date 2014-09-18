<?php
class CustomDataBaseTables_Ajax {
	
	private static $instance;
	
	public static function instance() {
		if (isset(self::$instance)) 
			return self::$instance;
		
		self::$instance = new CustomDataBaseTables_Ajax;
		self::$instance->init();
		return self::$instance;
	}
	
	private function __costruct() {
		// Do nothing
	}
	
	protected function init() {
		add_action('wp_ajax_cdbt_ajax_core', array(&$this, 'cdbt_ajax_core'));
	}
	
	public function cdbt_ajax_core() {
		global $cdbt;
		$token = $_POST['token'];
		if (!wp_verify_nonce($token, PLUGIN_SLUG . '_ajax')) {
			die(__('Invalid access!', PLUGIN_SLUG));
		} else {
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
						$response = '';
						$response .= '<ul class="download-files">';
						foreach ($binary_files as $binary_file) {
							$url = esc_js(esc_url_raw(admin_url('admin-ajax.php', is_ssl() ? 'https' : 'http'))) . '?action=cdbt_media&id='. $binary_file['ID'] .'&filename='. $binary_file['origin_file'] .'&token='. wp_create_nonce(PLUGIN_SLUG . '_download');
							$response .= sprintf('<li><a href="%s">%s</a></li>', $url, rawurldecode($binary_file['origin_file']));
						}
						$response .= '</ul>';
						die( $response );
					} else {
						die(__('No binary data.', PLUGIN_SLUG));
					}
					break;
				case 'get_table_list': 
					global $wpdb;
					$res = $wpdb->get_results('SHOW TABLES', 'ARRAY_N');
					$exists_tables = array();
					foreach ($res as $i => $one_res) {
						if (!$cdbt->compare_reservation_tables($one_res[0])) {
							$is_enable = false;
							foreach ($cdbt->options['tables'] as $j => $table_data) {
								if (cdbt_compare_var($one_res[0], $table_data['table_name'])) {
									$is_enable = true;
									break;
								}
							}
							if (!$is_enable) {
								$exists_tables[] = $one_res[0];
							}
						}
					}
					$response = '';
					foreach ($exists_tables as $table_name) {
						$response .= sprintf('<option value="%s">%s</option>'."\n", $table_name, $table_name);
					}
					die($response);
					break;
				default: 
					die(__('Invalid access!', PLUGIN_SLUG));
					break;
			}
		}
	}
	
}
