<?php
class CustomDatabaseTables {
	
	/**
	 * plugin version
	 * @var string
	 */
	var $version = CDBT_PLUGIN_VERSION;
	
	/**
	 * database version
	 * @var float
	 */
	var $db_version = CDBT_DB_VERSION;
	
	/**
	 * name of controller table
	 * @var string
	 */
	var $controller_table;
	
	/**
	 * current target table name
	 * @var string
	 */
	var $current_table;
	
	/**
	 * default charset for database
	 * @var string
	 */
	var $charset;
	
	/**
	 * upsert to database timezone
	 * @var string
	 */
	var $timezone;
	
	/**
	 * plugins directory path
	 * @var string
	 */
	var $dir;
	
	/**
	 * plugins directory url
	 * @var string
	 */
	var $dir_url;
	
	/**
	 * query string of current uri
	 * @var array
	 */
	private $query = array();
	
	/**
	 * session key name
	 * @var string
	 */
	private $session = 'cdbt_session';
	
	/**
	 * domain name for i18n
	 * @var string
	 */
	const DOMAIN = CDBT_PLUGIN_SLUG;
	
	/**
	 * undocumented class variable
	 * @var array
	 */
	var $options = array();
	
	/**
	 * information message on admin panel
	 * @var array
	 */
	var $message = array();
	
	/**
	 * error message on admin panel
	 * @var array
	 */
	var $error = array();
	
	/**
	 * outputted logs on debug mode
	 * @var boolean
	 */
	protected $debug = false;
	
	/**
	 * constructor for PHP5
	 * @return array
	 */
	function __construct() {
		global $wpdb;
		
		foreach (explode(CDBT_DS, dirname(__FILE__)) as $dir_name) {
			$this->dir .= (!empty($dir_name)) ? CDBT_DS . $dir_name : '';
			if (self::DOMAIN == $dir_name) 
				break;
		}
		$path_list = explode('/', plugin_basename(__FILE__));
		$this->dir_url = @plugin_dir_url() . array_shift($path_list);
		
		load_plugin_textdomain(self::DOMAIN, false, basename($this->dir) . CDBT_DS . 'langs');
		
		$this->options = get_option(self::DOMAIN);
		if (!empty($this->options['timezone'])) 
			date_default_timezone_set($this->options['timezone']);
		
		if ($this->options['plugin_version'] != $this->version) {
			if (version_compare($this->version, $this->options['plugin_version']) > 0) {
				$this->activate();
			}
		}
		if ($this->options['db_version'] != $this->db_version) {
			if (version_compare($this->db_version, $this->options['db_version']) > 0) {
				$this->activate();
			}
		}
		
		if (isset($this->options['api_key'])) {
			add_filter('rewrite_rules_array', array($this, 'insert_rewrite_rules'));
			add_filter('query_vars', array($this, 'insert_query_vars'));
			add_action('wp_loaded', array($this, 'flush_rules'));
			if (!empty($this->options['api_key'])) {
				add_action('send_headers', array($this, 'allow_host'));
			}
		}
		
		$this->current_table = get_option(self::DOMAIN . '_current_table', '');
		
		CustomDataBaseTables_Ajax::instance();
		CustomDataBaseTables_Media::instance();
		
		add_filter('plugin_action_links', array($this, 'add_action_links'), 10, 2);
		add_action('admin_menu', array($this, 'create_admin'));
		add_action('pre_get_posts', array($this, 'receive_api_request'));
	}
	
	/**
	 * constructor for PHP4
	 * @return void
	 */
	function CustomDatabaseTables() {
		if (empty($this->options)) 
			$this->__construct();
	}
	
	/**
	 * outputted logs if debug mode is enabled
	 * @param string $msg
	 * @return void
	 */
	protected function log_info($msg) {
		if ($this->debug) {
			$now_datetime = date("Y-m-d H:i:s (e)", strtotime(current_time('mysql')));
			$log_file_path = $this->dir . CDBT_DS . 'log.txt';
			if (!file_exists($log_file_path)) 
				$log_file_path = substr($log_file_path, 1);
			error_log("[$now_datetime] CURRENT_TABLE=\"$this->current_table\" INFO=\"$msg\" ;\n", 3, $log_file_path);
		}
	}
	
	/**
	 * check version and table structure on plugin activation
	 * @return void
	 */
	function activate(){
		global $wpdb;
		$default_timezone = get_option('timezone_string', 'UTC');
		$default_options = array(
			'plugin_version' => $this->version, 
			'db_version' => $this->db_version, 
			'use_wp_prefix' => true, 
			'charset' => DB_CHARSET, 
			'timezone' => $default_timezone, 
			'cleaning_options' => true, 
			'uninstall_options' => false, 
			'resume_options' => false, 
			'api_key' => array(),
			'tables' => array(
				array(
					'table_name' => 'cdbt_schema_template', 
					'table_type' => 'controller_table', 
					'sql' => "CREATE TABLE `cdbt_schema_template` (
							`ID` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '". __('ID', self::DOMAIN) ."',
							{%column_definition%}
							`created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '". __('Created Date', self::DOMAIN) ."',
							`updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '". __('Updated Date', self::DOMAIN) ."',
							PRIMARY KEY (`ID`)
							{%keyindex_definition%}
							{%reference_definition%}
						) ENGINE=%s DEFAULT CHARSET=%s COMMENT='". __('Custom Database Tables Controller', self::DOMAIN) ."' AUTO_INCREMENT=1 
						{%table_options%} ;", 
					'db_engine' => 'InnoDB', 
					'show_max_records' => 10, 
					'roles' => array(
						'view_role' => '9', 
						'input_role' => '9', 
						'edit_role' => '9', 
						'admin_role' => '9' ), 
					'display_format' => array(
						// {column_name} => array('(require|optional)', '(show|hide|none)', '{display_item_name}', '{default_value}', '(string|integer|float|date|binary)')
					),
				), 
			), 
		);
		
		$pre_option = get_option(self::DOMAIN);
		if ($pre_option) {
			$this->options = $pre_option;
		} else {
			$this->options = $default_options;
		}
		if (in_array('plugin_version', $this->options)) {
			if ($this->options['plugin_version'] != $default_options['plugin_version']) {
				if (version_compare($default_options['plugin_version'], $this->options['plugin_version']) > 0) {
					foreach ($this->options['tables'] as $i => $table_data) {
						if ($table_data['table_name'] != $default_options['tables'][0]['table_name']) {
							$default_options['tables'][] = $table_data;
						}
					}
					$this->options = $default_options;
				}
			}
		} else {
			$this->options = $default_options;
		}
		if (count($this->options['tables']) > 1) {
			$this->current_table = get_option(self::DOMAIN . '_current_table', '');
			if (empty($this->current_table)) {
				for ($i=1; $i<count($this->options['tables']); $i++) {
					if ($this->options['tables'][$i]['table_type'] == 'enable_table') {
						$this->current_table = $this->options['tables'][$i]['table_name'];
						break;
					}
				}
			}
		} else {
			$this->current_table = '';
		}
		date_default_timezone_set($this->options['timezone']);
		
		if (get_option(self::DOMAIN) !== false) {
			update_option(self::DOMAIN, $this->options);
		} else {
			add_option(self::DOMAIN, $this->options, '', 'no');
		}
	}
	
	/**
	 * plugin deactivation
	 * @return void
	 */
	function deactivation(){
		$revision_option = self::DOMAIN . '_previous_revision_backup';
		if (get_option($revision_option) !== false) {
			update_option($revision_option, $this->options);
		} else {
			add_option($revision_option, $this->options, '', 'no');
		}
		delete_option(self::DOMAIN . '_current_table');
		delete_option(self::DOMAIN . '_stored_queries');
		$this->log_info('cdbt plugin deactivated.');
	}
	
	/**
	 * append action links to this plugin on list page
	 * @return array
	 */
	function add_action_links($links, $file){
		if ($file == self::DOMAIN . '/cdbt.php') {
			array_unshift($links, '<a href="'. admin_url('options-general.php?page=' . self::DOMAIN) .'">'. __('Settings') .'</a>');
		}
		return $links;
	}
	
	/**
	 * create admin panel
	 * @return void
	 */
	function create_admin(){
		$cdbt_plugin_page = add_options_page(__('Custom Database Tables Option: ', self::DOMAIN), __('Custom DB Tables', self::DOMAIN), 'manage_options', self::DOMAIN, array($this, 'admin_controller'), '');
		wp_parse_str($_SERVER['QUERY_STRING'], $this->query);
		add_action("admin_head-$cdbt_plugin_page", array($this, 'admin_header'));
		//add_action("load-$cdbt_plugin_page", array($this, 'admin_assets'));
		add_action("admin_enqueue_scripts", array($this, 'admin_assets'));
		add_action("admin_footer-$cdbt_plugin_page", array($this, 'admin_footer'), 999);
		add_action('admin_notice', array($this, 'admin_notice'));
	}
	
	/**
	 * render admin panel from template
	 * @return void
	 */
	function admin_controller(){
		$mode = array_key_exists('mode', $this->query) ? $this->query['mode'] : 'index';
		switch($mode) {
			case 'list':
				$template_name = 'cdbt-list.php';
				break;
			case 'input':
				$template_name = 'cdbt-input.php';
				break;
			case 'edit':
				$template_name = 'cdbt-edit.php';
				break;
			case 'admin':
				$template_name = 'cdbt-admin-controller.php';
				break;
			default:
				$template_name = 'cdbt-index.php';
				break;
		}
		require_once CDBT_PLUGIN_TMPL_DIR . CDBT_DS . $template_name;
	}
	
	/**
	 * load header for CDBT management console in admin panel
	 * @return void
	 */
	function admin_header(){
		if (array_key_exists('page', $this->query) && $this->query['page'] == self::DOMAIN) {
			// Action hook for add_action('cdbt_admin_header')
			do_action('cdbt_admin_header');
		}
	}
	
	/**
	 * load assets for CDBT management console in admin panel
	 * @return void
	 */
	function admin_assets(){
		if (!is_admin()) 
			return;
		if (array_key_exists('page', $this->query) && $this->query['page'] == self::DOMAIN) {
			$cdbt_admin_assets = array(
				'styles' => array(
					'cdbt-common-style' => array( $this->dir_url . '/assets/css/cdbt-main.min.css', array(), $this->version, 'all' ), 
					'cdbt-admin-style' => array( $this->dir_url . '/assets/css/cdbt-admin.css', true, $this->version, 'all' ), 
				), 
				'scripts' => array(
					'cdbt-common-script' => array( $this->dir_url . '/assets/js/scripts.min.js', array(), null, false ), 
					'jquery-ui-core' => null, 
					'jquery-ui-widget' => null, 
					'jquery-ui-mouse' => null, 
					'jquery-ui-position' => null, 
					'jquery-ui-sortable' => null, 
					'jquery-ui-autocomplete' => null, 
				)
			);
			// Filter hook for add_filter('cdbt_admin_assets')
			$cdbt_admin_assets = apply_filters('cdbt_admin_assets', $cdbt_admin_assets);
			foreach ($cdbt_admin_assets as $asset_type => $asset_instance) {
				if ($asset_type == 'styles') {
					foreach ($asset_instance as $asset_name => $asset_values) {
						wp_enqueue_style($asset_name, $asset_values[0], $asset_values[1], $asset_values[2], $asset_values[3]);
					}
				}
				if ($asset_type == 'scripts') {
					foreach ($asset_instance as $asset_name => $asset_values) {
						if (!empty($asset_values)) {
							wp_register_script($asset_name, $asset_values[0], $asset_values[1], $asset_values[2], $asset_values[3]);
						}
						wp_enqueue_script($asset_name);
					}
				}
			}
		}
	}
	
	/**
	 * load footer for CDBT management console in admin panel
	 * @return void
	 */
	function admin_footer(){
		if (array_key_exists('page', $this->query) && $this->query['page'] == self::DOMAIN) {
			// Action hook for add_action('cdbt_admin_footer')
			do_action('cdbt_admin_footer');
			cdbt_create_javascript();
		}
	}
	
	/**
	 * show notice on CDBT management console in admin panel
	 * @return void
	 */
	function admin_notice(){
		if (array_key_exists('page', $this->query) && $this->query['page'] == self::DOMAIN) {
			$notice_base = '<div class="%s"><ul>%s</ul></div>';
			if (!empty($this->error)) {
				$notice_list = '';
				// Filter hook for add_filter('cdbt_admin_error')
				$this->error = apply_filters('cdbt_admin_error', $error=$this->error);
				foreach ($this->error as $error) {
					$notice_list .= '<li>' . $error . '</li>';
				}
				printf($notice_base, 'error', $notice_list);
			}
			if (!empty($this->message)) {
				$notice_list = '';
				// Filter hook for add_filter('cdbt_admin_notice')
				$this->message = apply_filters('cdbt_admin_notice', $message=$this->message);
				foreach ($this->message as $message) {
					$notice_list .= '<li>' . $message . '</li>';
				}
				printf($notice_base, 'updated', $notice_list);
			}
		}
	}
	
	/**
	 * create api key for remote address
	 * @param string $remote_addr (optional) default null eq. $_SERVER['REMOTE_ADDR']
	 * @return string $api_key
	 */
	function generate_api_key($remote_addr=''){
		if (!defined(DB_NAME)) {
			$base_salt = md5(self::DOMAIN . DB_NAME . $_SERVER['SERVER_ADDR'] . (!empty($remote_addr) ? $remote_addr : $_SERVER['SERVER_PORT']) . uniqid());
			$base_salt = str_split(strtoupper($base_salt), strlen($base_salt)/4);
			$api_key = implode('-', $base_salt);
		} else {
			$api_key = '';
		}
		return $api_key;
	}
	
	/**
	 * verify api key
	 * @param string $api_key
	 * @return void
	 */
	function verify_api_key($api_key){
		if (isset($api_key) && !empty($api_key)) {
			$result = false;
			if (isset($this->options['api_key']) && !empty($this->options['api_key']) && is_array($this->options['api_key']) && count($this->options['api_key']) > 0) {
				if (isset($_SERVER['HTTP_ORIGIN']) && !empty($_SERVER['HTTP_ORIGIN'])) {
					$client_host = preg_replace('/^(http|https|ftp):\/\/(.*)/iU', '$2', $_SERVER['HTTP_ORIGIN']);
				} elseif (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
					$client_host = preg_replace('/^(http|https|ftp):\/\/(.*)(\/|\?|:).*$/iU', '$2', $_SERVER['HTTP_REFERER']);
				} elseif (isset($_SERVER['REMOTE_HOST']) && !empty($_SERVER['REMOTE_HOST'])) {
					$client_host = $_SERVER['REMOTE_HOST'];
				} elseif (isset($_SERVER['REMOTE_ADDR']) && !empty($_SERVER['REMOTE_ADDR'])) {
					$client_host = gethostbyaddr($_SERVER['REMOTE_ADDR']);
				} else {
					$client_host = '';
				}
				if (!empty($client_host)) {
					list($client_addr, ) = gethostbynamel($client_host);
				} else {
					$client_addr = $_SERVER['SERVER_ADDR'];
				}
				foreach ($this->options['api_key'] as $host_addr => $regist_api_key) {
					if (cdbt_compare_var($api_key, $regist_api_key)) {
						if (cdbt_compare_var($client_host, $host_addr)) {
							$result = true;
						} elseif (cdbt_compare_var($client_addr, $host_addr)) {
							$result = true;
						}
						if ($result) {
							break;
						}
					}
				}
			}
		} else {
			$result = false;
		}
		return $result;
	}
	
	/**
	 * flush_rules() if extend rules are not yet included
	 */
	function flush_rules() {
		$rules = get_option('rewrite_rules');
		if (!isset($rules['^cdbt_api/([^/]*)/([^/]*)/([^/]*)?$'])) {
			global $wp_rewrite;
			$wp_rewrite->flush_rules();
		}
	}
	
	/**
	 * Adding a extend rule for requesting api
	 */
	function insert_rewrite_rules($rules) {
		$newrules = array();
		$newrules['^cdbt_api/([^/]*)/([^/]*)/([^/]*)?$'] = 'index.php?cdbt_api_key=$matches[1]&cdbt_table=$matches[2]&cdbt_api_request=$matches[3]';
		return $newrules + $rules;
	}
	
	/**
	 * Adding the vars of requesting api so that WP recognizes it
	 */
	function insert_query_vars($vars) {
		array_push($vars, 'cdbt_api_key', 'cdbt_table', 'cdbt_api_request');
		return $vars;
	}
	
	/**
	 * Enable HTTP access control (CORS)
	 */
	function allow_host() {
		header("Access-Control-Allow-Origin: *");
		header("Access-Control-Allow-Methods: POST, GET");
		header("Access-Control-Max-Age: 86400");
	}
	
	/**
	 * controller process when receive the api request
	 * @param string $wp_query
	 * @return void
	 */
	function receive_api_request($wp_query){
		if (is_admin()) 
			return;
		if (isset($wp_query->query['cdbt_api_key']) && !empty($wp_query->query['cdbt_api_key'])) {
			$request_uri = $_SERVER['REQUEST_URI'];
			$request_date = date('c', $_SERVER['REQUEST_TIME']);
			if ($this->verify_api_key(trim($wp_query->query['cdbt_api_key']))) {
				$target_table = (isset($wp_query->query['cdbt_table']) && !empty($wp_query->query['cdbt_table'])) ? trim($wp_query->query['cdbt_table']) : '';
				$request = (isset($wp_query->query['cdbt_api_request']) && !empty($wp_query->query['cdbt_api_request'])) ? trim($wp_query->query['cdbt_api_request']) : '';
				if (!empty($target_table) && !empty($request)) {
					if ($this->check_table_exists($target_table)) {
						// 200: Successful
						$response = array('success' => array('code' => 200, 'table' => $target_table, 'request' => $request, 'request_uri' => $request_uri, 'request_date' => $request_date));
						switch($request) {
							case 'get_data': 
								$allow_args = array('columns' => 'mixed', 'conditions' => 'hash', 'order' => 'hash', 'limit' => 'int', 'offset' => 'int');
								$response['data'] = $this->api_method_wrapper($target_table, $request, $allow_args);
								break;
							case 'find_data': 
								$allow_args = array('search_key' => 'string', 'columns' => 'mixed', 'order' => 'hash', 'limit' => 'int', 'offset' => 'int');
								$response['data'] = $this->api_method_wrapper($target_table, $request, $allow_args);
								break;
							case 'insert_data': 
								$allow_args = array('data' => 'hash');
								$response['data'] = $this->api_method_wrapper($target_table, $request, $allow_args);
								break;
							case 'update_data': 
								$allow_args = array('primary_key_value' => 'int', 'data' => 'hash');
								$response['data'] = $this->api_method_wrapper($target_table, $request, $allow_args);
								break;
							case 'delete_data': 
								$allow_args = array('primary_key_value' => 'int');
								$response['data'] = $this->api_method_wrapper($target_table, $request, $allow_args);
								break;
							default: 
								$response = array('error' => array('code' => 400, 'desc' => 'Invalid Request', 'request_uri' => $request_uri, 'request_date' => $request_date));
								break;
						}
					} else {
						$response = array('error' => array('code' => 400, 'desc' => 'Invalid Request', 'request_uri' => $request_uri, 'request_date' => $request_date));
					}
				} else {
					// 400: Invalid API request
					$response = array('error' => array('code' => 400, 'desc' => 'Invalid Request', 'request_uri' => $request_uri, 'request_date' => $request_date));
				}
			} else {
				// 401: Authentication failure
				$response = array('error' => array('code' => 401, 'desc' => 'Authentication Failure', 'request_uri' => $request_uri, 'request_date' => $request_date));
			}
			$is_crossdomain = (isset($_REQUEST['callback']) && !empty($_REQUEST['callback'])) ? trim($_REQUEST['callback']) : false;
			header( 'Content-Type: text/javascript; charset=utf-8' );
			if ($is_crossdomain) {
				$response = $is_crossdomain . '(' . json_encode($response) . ')';
			} else {
				$response = json_encode($response);
			}
			// Currently, logging of API request is not implemented yet.
			die($response);
			exit;
		} else {
			// 403: Invalid access
			// $response = array('error' => array('code' => 403, 'desc' => 'Invalid Access'));
			//header("HTTP/1.1 404 Not Found", false, 404);
		}
	}
	
	/**
	 * Wrapper for executing core methods from requested API
	 * @param string $target_table
	 * @param string $request eq. name of this CRUD mothods
	 * @param array $allow_args
	 * @return mixed
	 */
	function api_method_wrapper($target_table, $request, $allow_args) {
		foreach ($allow_args as $var_name => $val_type) {
			${$var_name} = (isset($_REQUEST[$var_name]) && !empty($_REQUEST[$var_name])) ? trim($_REQUEST[$var_name]) : null;
			if (!empty(${$var_name})) {
				if ($val_type == 'mixed') {
					if (preg_match('/^\{(.*)\}$/U', ${$var_name}, $matches)) {
						$tmp = explode(',', $matches[1]);
						$tmp_ary = array();
						foreach ($tmp as $line_str) {
							list($column_name, $column_value) = explode(':', trim($line_str));
							$column_name = trim(trim(stripcslashes($column_name)), "\"' ");
							$column_value = trim(trim(stripcslashes($column_value)), "\"' ");
							if (!empty($column_name)) 
								$tmp_ary[$column_name] = empty($column_value) ? 'NULL' : $column_value;
						}
						${$var_name} = $tmp_ary;
					} elseif (preg_match('/^\[(.*)\]$/U', ${$var_name}, $matches)) {
						$tmp = explode(',', $matches[1]);
						$tmp_ary = array();
						foreach ($tmp as $line_str) {
							$tmp_ary[] = trim(trim(stripcslashes($line_str)), "\"' ");
						}
						${$var_name} = $tmp_ary;
					}
				} elseif ($val_type == 'array') {
					if (preg_match('/^\[(.*)\]$/U', ${$var_name}, $matches)) {
						$tmp = explode(',', $matches[1]);
						$tmp_ary = array();
						foreach ($tmp as $line_str) {
							$tmp_ary[] = trim(trim(stripcslashes($line_str)), "\"' ");
						}
						${$var_name} = $tmp_ary;
					} else {
						${$var_name} = null;
					}
				} elseif ($val_type == 'hash') {
					if (preg_match('/^\{(.*)\}$/U', ${$var_name}, $matches)) {
						$tmp = explode(',', $matches[1]);
						$tmp_ary = array();
						foreach ($tmp as $line_str) {
							list($column_name, $column_value) = explode(':', trim($line_str));
							$column_name = trim(trim(stripcslashes($column_name)), "\"' ");
							$column_value = trim(trim(stripcslashes($column_value)), "\"' ");
							if (!empty($column_name)) 
								$tmp_ary[$column_name] = empty($column_value) ? 'NULL' : $column_value;
						}
						${$var_name} = $tmp_ary;
					} else {
						${$var_name} = null;
					}
				} elseif ($val_type == 'int') {
					${$var_name} = intval($_REQUEST[$var_name]);
				}
			}
		}
		switch($request) {
			case 'get_data': 
				$result = $this->get_data($target_table, $columns, $conditions, $order, $limit, $offset);
				break;
			case 'find_data': 
				$result = $this->find_data($target_table, null, $search_key, $columns, $order, $limit, $offset);
				break;
			case 'insert_data': 
				$result = $this->insert_data($target_table, $data, null);
				break;
			case 'update_data': 
				$result = $this->update_data($target_table, $primary_key_value, $data, null);
				break;
			case 'update_where': 
				$result = $this->update_where($target_table, $where_conditions, $data, null);
				break;
			case 'delete_data': 
				$result = $this->delete_data($target_table, $primary_key_value);
				break;
//			case 'run_query': 
//				$result = $this->run_query($query);
//				break;
			default: 
				$result = false;
				break;
		}
		return $result;
	}
	
// //////////////////// following CRUD //////////////////////////////////////////////////
	
	/**
	 * Check table exists
	 * @param string $table_name (optional) default $this->current_table
	 * @return string
	 */
	function check_table_exists($table_name=null) {
	}
	
	/**
	 * Truncate table
	 * @param string $table_name (optional) default $this->current_table
	 * @return array
	 */
	function truncate_table($table_name=null) {
	}
	
	/**
	 * Drop table
	 * @param string $table_name (optional) default $this->current_table
	 * @return array
	 */
	function drop_table($table_name=null) {
	}
	
	/**
	 * Create table
	 * @param array $table_data
	 * @return array
	 */
	function create_table($table_data) {
	}
	
	/**
	 * Get table schema
	 * @param string $table_name (optional) default $this->current_table
	 * @return array
	 */
	function get_table_schema($table_name=null) {
	}
	
	/**
	 * get table comment
	 * @param string $table_name (optional) default $this->current_table
	 * @return array
	 */
	function get_table_comment($table_name=null) {
	}
	
	/**
	 * get create table sql
	 * @param string $table_name (optional) default $this->current_table
	 * @return array
	 */
	function get_create_table_sql($table_name=null) {
	}
	
	/**
	 * get data
	 * @param string $table_name (must containing prefix of table)
	 * @param array $columns (optional) default wildcard of '*' (eq. select clause)
	 * @param array $conditions (optional) (eq. where clause)
	 * @param array $order (optional) default 'order by `created` desc' (eq. orderby & order clause)
	 * @param int $limit (optional) (eq. limit clause)
	 * @param int $offset (optional) (eq. offset clause)
	 * @return array
	 */
	function get_data($table_name, $columns='*', $conditions=null, $order=array('created'=>'desc'), $limit=null, $offset=null) {
	}
	
	/**
	 * find data
	 * Locate the appropriate data by extracting the best column from the schema information of the table for the search keyword. 
	 * Same behavior as get_data() If there is no schema of the table argument is.
	 *
	 * @param string $table_name (must containing prefix of table)
	 * @param array $table_schema default null
	 * @param string $search_key
	 * @param array $columns (optional) default wildcard '*' (eq. select clause)
	 * @param array $order (optional) default 'order by `created` desc' (eq. orderby and order clause)
	 * @param int $limit (optional) (eq. limit clause)
	 * @param int $offset (optional) (eq. offset clause)
	 * @return array
	 */
	function find_data($table_name, $table_schema=null, $search_key, $columns, $order=array('created'=>'desc'), $limit=null, $offset=null) {
		global $wpdb;
		if (empty($table_schema)) 
			list(, , $table_schema) = $this->get_table_schema($table_name);
		$select_clause = is_array($columns) ? implode(',', $columns) : (!empty($columns) ? $columns : '*');
		$where_clause = $order_clause = $limit_clause = null;
		if (!empty($order)) {
			$i = 0;
			foreach ($order as $key => $val) {
				if (array_key_exists($key, $table_schema)) {
					$val = strtoupper($val) == 'DESC' ? 'DESC' : 'ASC';
					if ($i == 0) {
						$order_clause = "ORDER BY `$key` $val ";
					} else {
						$order_clause .= ", `$key` $val ";
					}
					$i++;
				} else {
					continue;
				}
			}
		}
		if (!empty($limit)) {
			$limit_clause = "LIMIT ";
			$limit_clause .= (!empty($offset)) ? intval($offset) .', '. intval($limit) : intval($limit);
		}
		$search_key = preg_replace('/[\s　]+/u', ' ', trim($search_key), -1);
		$keywords = preg_split('/[\s]/', $search_key, 0, PREG_SPLIT_NO_EMPTY);
		if (!empty($keywords)) {
			$primary_key_name = null;
			foreach ($table_schema as $col_name => $col_scm) {
				if (empty($primary_key_name) && $col_scm['primary_key']) {
					$primary_key_name = $col_name;
					break;
				}
			}
			$union_clauses = array();
			foreach ($keywords as $value) {
				if (!empty($table_schema)) {
					unset($table_schema[$primary_key_name], $table_schema['created'], $table_schema['updated']);
					$target_columns = array();
					foreach ($table_schema as $column_name => $column_info) {
						if (is_float($value)) {
							if (preg_match('/^(float|double(| precision)|real|dec(|imal)|numeric|fixed)$/', $column_info['type'])) 
								$target_columns[] = $column_name;
						} else if (is_int($value)) {
							if (preg_match('/^((|tiny|small|medium|big)int|bool(|ean)|bit)$/', $column_info['type'])) 
								$target_columns[] = $column_name;
						}
						if (preg_match('/^((|var|national |n)char(|acter)|(|tiny|medium|long)text|(|tiny|medium|long)blob|(|var)binary|enum|set)$/', $column_info['type'])) 
							$target_columns[] = $column_name;
					}
				}
			}
			if (!empty($target_columns)) {
				foreach ($target_columns as $target_column_name) {
					$i = 0;
					foreach ($keywords as $value) {
						if ($i == 0) {
							$where_clause = "WHERE `$target_column_name` LIKE '%%$value%%' ";
						} else {
							$where_clause .= "AND `$target_column_name` LIKE '%%$value%%' ";
						}
						$i++;
					}
					$union_clauses[] = sprintf('SELECT %s FROM %s %s', $select_clause, $table_name, $where_clause);
				}
			} else {
				// $table_schema is none
				
			}
			if (!empty($union_clauses)) {
				if (count($union_clauses) == 1) {
					$union_clause = array_shift($union_clauses) . ' %s';
					$sql = sprintf($union_clause, $limit_clause);
				} else {
					$i = 0;
					foreach ($union_clauses as $union_clause) {
						if ($i == 0) {
							$sql = '(' . $union_clause . ')';
						} else {
							$sql .= ' UNION (' . $union_clause . ')';
						}
						$i++;
					}
					$sql .= " $order_clause $limit_clause";
				}
			}
		}
		if (!isset($sql) || empty($sql)) {
			$sql = sprintf(
				"SELECT %s FROM `%s` %s %s %s", 
				$select_clause, 
				$table_name, 
				$where_clause, 
				$order_clause, 
				$limit_clause 
			);
		}
		return $wpdb->get_results($sql);
	}
	
	/**
	 * insert data
	 * @param string $table_name (must containing prefix of table)
	 * @param array $data
	 * @param array $table_schema default null
	 * @param bool $date_init default true
	 * @return int $insert_id
	 */
	function insert_data($table_name, $data, $table_schema=null, $date_init=true) {
		global $wpdb;
		if (empty($table_schema)) 
			list(, , $table_schema) = $this->get_table_schema($table_name);
		$primary_key_name = $primary_key_value = null;
		$primary_key_count = 0;
		$is_exists_created = $is_exists_updated = false;
		foreach ($table_schema as $key => $val) {
			if ($val['primary_key']) {
				if (empty($primary_key_name)) {
					$primary_key_name = $key;
					$primary_key_a_i = strtolower($val['extra']) == 'auto_increment' ? true : false;
				}
				$primary_key_count++;
			}
			if ($key == 'created') 
				$is_exists_created = $date_init;
			if ($key == 'updated') 
				$is_exists_updated = $date_init;
		}
		if (!empty($primary_key_name)) {
			if ($primary_key_a_i) {
				unset($data[$primary_key_name]);
			} else {
				$primary_key_value = $data[$primary_key_name];
			}
		}
		if ($is_exists_created || empty($data['created'])) 
			$data['created'] = date('Y-m-d H:i:s', time());
		if ($is_exists_updated || empty($data['updated'])) 
			unset($data['updated']);
		$format = array();
		foreach ($data as $column_name => $value) {
			if (array_key_exists($column_name, $table_schema)) {
				if (preg_match('/^((|tiny|small|medium|big)int|bool(|ean)|bit)$/', $table_schema[$column_name]['type']) && preg_match('/^(\-|)[0-9]+$/', $value)) {
					// is integer format
					$format[] = '%d';
				} else if (preg_match('/^(float|double(| precision)|real|dec(|imal)|numeric|fixed)$/', $table_schema[$column_name]['type']) && preg_match('/^(\-|)[0-9]+\.?[0-9]+$/', $value)) {
					// is double format
					$format[] = '%f';
				} else {
					// is string format
					$format[] = '%s';
				}
			}
		}
		if (isset($format) && !empty($format) && count($data) == count($format)) {
			$res = $wpdb->insert($table_name, $data, $format);
		} else {
			$res = $wpdb->insert($table_name, $data);
		}
		if ($primary_key_count == 1) {
			return (!cdbt_get_boolean($res)) ? $res : $wpdb->insert_id;
		} else if ($primary_key_count > 1) {
			return $primary_key_value;
		}
	}
	
	/**
	 * update data (for primary key based)
	 * @param string $table_name (must containing prefix of table)
	 * @param int $primary_key_value
	 * @param array $data
	 * @param array $table_schema default null
	 * @return int updated row (eq. primary key column's value)
	 */
	function update_data($table_name, $primary_key_value, $data, $table_schema=null) {
		global $wpdb;
		if (empty($table_schema)) 
			list(, , $table_schema) = $this->get_table_schema($table_name);
		$primary_key_name = null;
		$primary_key_count = 0;
		$is_exists_created = $is_exists_updated = false;
		foreach ($table_schema as $key => $val) {
			if ($val['primary_key']) {
				if (empty($primary_key_name)) {
					$primary_key_name = $key;
					$primary_key_a_i = strtolower($val['extra']) == 'auto_increment' ? true : false;
					if (preg_match('/^((|tiny|small|medium|big)int|bool(|ean)|bit)$/', $val['type']) && preg_match('/^(\-|)[0-9]+$/', $primary_key_value)) {
						$primary_key_value = intval($primary_key_value);
						$primary_key_format = '%d';
					} else if (preg_match('/^(float|double(| precision)|real|dec(|imal)|numeric|fixed)$/', $val['type']) && preg_match('/^(\-|)[0-9]+\.?[0-9]+$/', $primary_key_value)) {
						$primary_key_value = floatval($primary_key_value);
						$primary_key_format = '%f';
					} else {
						$primary_key_value = strval($primary_key_value);
						$primary_key_format = '%s';
					}
				}
				$primary_key_count++;
			}
			if ($key == 'created') 
				$is_exists_created = true;
			if ($key == 'updated') 
				$is_exists_updated = true;
		}
		if (array_key_exists($primary_key_name, $data)) {
			if ($primary_key_a_i) {
				unset($data[$primary_key_name]);
			}
		}
		//if ($is_exists_created && array_key_exists('created', $data)) 
		//	unset($data['created']);
		if ($is_exists_updated && array_key_exists('updated', $data)) 
			unset($data['updated']);
		if ($primary_key_count <= 1) {
			$format = array();
			foreach ($data as $column_name => $value) {
				if (array_key_exists($column_name, $table_schema)) {
					if (preg_match('/^((|tiny|small|medium|big)int|bool(|ean)|bit)$/', $table_schema[$column_name]['type']) && preg_match('/^(\-|)[0-9]+$/', $value)) {
						// is integer format
						$data[$column_name] = intval($value);
						$format[] = '%d';
					} else if (preg_match('/^(float|double(| precision)|real|dec(|imal)|numeric|fixed)$/', $table_schema[$column_name]['type']) && preg_match('/^(\-|)[0-9]+\.?[0-9]+$/', $value)) {
						// is double format
						$data[$column_name] = floatval($value);
						$format[] = '%f';
					} else {
						// is string format
						$data[$column_name] = strval($value);
						$format[] = '%s';
					}
					if (empty($value)) 
						$value = null;
				}
			}
		}
		if (isset($format) && !empty($format) && count($data) == count($format)) {
			$result = $wpdb->update($table_name, $data, array($primary_key_name => $primary_key_value), $format, array($primary_key_format));
			$result = ($result) ? $primary_key_value : $result;
			return $result;
		} else {
			return false;
		}
	}
	
	/**
	 * update data (for where clause based)
	 * @param string $table_name (must containing prefix of table)
	 * @param string $where_conditions
	 * @param array $data
	 * @param array $table_schema (optional) default null
	 * @return boolean
	 */
	function update_where($table_name, $where_conditions, $data, $table_schema=null) {
		global $wpdb;
		if (empty($table_schema)) 
			list(, , $table_schema) = $this->get_table_schema($table_name);
		$is_exists_created = $is_exists_updated = false;
		foreach ($table_schema as $key => $val) {
			if ($key == 'created') 
				$is_exists_created = true;
			if ($key == 'updated') 
				$is_exists_updated = true;
		}
		//if ($is_exists_created && array_key_exists('created', $data)) 
		//	unset($data['created']);
		if ($is_exists_updated && array_key_exists('updated', $data)) 
			unset($data['updated']);
		
		if (!empty($where_conditions)) {
			if (preg_match_all('/\s{0,}\"(.*)\"\s{0,}/iU', $where_conditions, $matches) && array_key_exists(1, $matches)) {
				foreach ($matches[1] as $stick) {
					$where_conditions = str_replace('"'. $stick .'"', "'$stick'", $where_conditions);
				}
			}
			$where_clause = 'WHERE ' . $where_conditions;
		} else {
			$where_clause = '';
		}
		
		$set_clauses = array();
		foreach ($data as $column_name => $value) {
			if (array_key_exists($column_name, $table_schema)) {
				if (preg_match('/^((|tiny|small|medium|big)int|bool(|ean)|bit)$/', $table_schema[$column_name]['type']) && preg_match('/^(\-|)[0-9]+$/', $value)) {
					// is integer format
					$set_clauses[] = sprintf('`%s` = %d', esc_sql($column_name), intval($value));
				} else if (preg_match('/^(float|double(| precision)|real|dec(|imal)|numeric|fixed)$/', $table_schema[$column_name]['type']) && preg_match('/^(\-|)[0-9]+\.?[0-9]+$/', $value)) {
					// is double format
					$set_clauses[] = sprintf('`%s` = %f', esc_sql($column_name), floatval($value));
				} else {
					// is string format
					$set_clauses[] = sprintf("`%s` = '%s'", esc_sql($column_name), strval($value));
				}
			}
		}
		$sql = sprintf('UPDATE `%s` SET %s %s;', $table_name, implode(', ', $set_clauses), $where_clause);
		return (boolean)$wpdb->query($sql);
	}
	
	/**
	 * run the custom query
	 * @param string $query
	 * @return mixed
	 */
	protected function run_query($query) {
		global $wpdb;
		return $wpdb->query($query);
	}
	
	/**
	 * delete data
	 * @param string $table_name (must containing prefix of table)
	 * @param string $primary_key_value
	 * @return bool
	 */
	function delete_data($table_name, $primary_key_value) {
		global $wpdb;
		list(, , $table_schema) = $this->get_table_schema($table_name);
		$primary_key_name = null;
		foreach ($table_schema as $key => $val) {
			if (empty($primary_key_name) && $val['primary_key']) {
				$primary_key_name = $key;
				if (preg_match('/^((|tiny|small|medium|big)int|bool(|ean)|bit)$/', $val['type']) && preg_match('/^(\-|)[0-9]+$/', $primary_key_value)) {
					$primary_key_value = intval($primary_key_value);
					$format = '%d';
				} else if (preg_match('/^(float|double(| precision)|real|dec(|imal)|numeric|fixed)$/', $val['type']) && preg_match('/^(\-|)[0-9]+\.?[0-9]+$/', $primary_key_value)) {
					$primary_key_value = floatval($primary_key_value);
					$format = '%f';
				} else {
					$primary_key_value = strval($primary_key_value);
					$format = '%s';
				}
				break;
			}
		}
		return $wpdb->delete($table_name, array($primary_key_name => $primary_key_value), array($format));
	}
	
	/**
	 * data verification by column schema
	 * @param array $column_schema
	 * @param string $data
	 * @return array
	 */
	function validate_data($column_schema, $data) {
		if ($column_schema['not_null'] && $column_schema['default'] == null) {
			if (strval($data) != '0' && empty($data)) 
				return array(false, __('empty', self::DOMAIN));
		}
		if (!empty($data)) {
			if (preg_match('/^((|tiny|small|medium|big)int|float|double(| precision)|real|dec(|imal)|numeric|fixed|bool(|ean)|bit)$/i', strtolower($column_schema['type']))) {
				if (strtolower($column_schema['type_format']) != 'tinyint(1)' && strtolower($column_schema['type_format']) != 'bit(1)') {
					if (preg_match('/^((|tiny|small|medium|big)int|bool(|ean))$/i', strtolower($column_schema['type']))) {
						$data = intval($data);
						if (!is_int($data)) 
							return array(false, __('not integer', self::DOMAIN));
					} else {
						$data = floatval($data);
						if ($data != 0 && !cdbt_get_boolean(preg_match('/^(\-|)[0-9]+\.?[0-9]+$/', $data))) {
							return array(false, __('not integer', self::DOMAIN));
						}
					}
				} else {
					$data = intval($data);
					if (preg_match('/^bit$/i', strtolower($column_schema['type']))) {
						if (!is_int($data)) 
							return array(false, __('not integer', self::DOMAIN));
					} else {
						if (!preg_match('/^(\-|)[0-9]+$/', $data)) 
							return array(false, __('not integer', self::DOMAIN));
					}
				}
				if ($column_schema['unsigned']) {
					if ($data < 0) 
						return array(false, __('not a positive number', self::DOMAIN));
				}
			}
			if (preg_match('/^((|var|national |n)char(|acter)|(|tiny|medium|long)text|(|tiny|medium|long)blob|(|var)binary)$/i', strtolower($column_schema['type']))) {
				if (!is_string($data)) 
					return array(false, __('invalid strings', self::DOMAIN));
			}
			if (preg_match('/^(enum|set)(.*?)$/i', strtolower($column_schema['type_format']), $matches)) {
				$eval_string = '$items = array' . $matches[2] . ';';
				eval($eval_string);
				if (!empty($data)) {
					if (!is_array($data)) 
						$data = explode(',', $data);
					foreach ($data as $tmp) {
						if (!in_array($tmp, $items)) {
							return array(false, __('invalid value', self::DOMAIN));
						}
					}
				}
			}
			$reg_date = '([1-9][0-9]{3})\D?(0[1-9]{1}|1[0-2]{1})\D?(0[1-9]{1}|[1-2]{1}[0-9]{1}|3[0-1]{1})\D?';
			$reg_time = '(0[0-9]{1}|1{1}[0-9]{1}|2{1}[0-3]{1})\D?(0[0-9]{1}|[1-5]{1}[0-9]{1})\D?(0[0-9]{1}|[1-5]{1}[0-9]{1})\D?';
			$reg_year = '([1-9][0-9]{3})\D?';
			if (preg_match('/^(datetime|timestamp)$/i', strtolower($column_schema['type']))) {
				if (!preg_match('/^'. $reg_date . $reg_time .'$/', $data)) 
					return array(false, __('invalid format', self::DOMAIN));
			}
			if (strtolower($column_schema['type']) == 'date') {
				if (!preg_match('/^'. $reg_date .'$/', $data)) 
					return array(false, __('invalid format', self::DOMAIN));
			}
			if (strtolower($column_schema['type']) == 'time') {
				if (!preg_match('/^'. $reg_time .'$/i', $data)) 
					return array(false, __('invalid format', self::DOMAIN));
			}
			if (strtolower($column_schema['type']) == 'year') {
				if (!preg_match('/^'. $reg_year .'$/i', $data)) 
					return array(false, __('invalid format', self::DOMAIN));
			}
			if (is_array($data)) {
				foreach ($data as $value) {
					if (function_exists('mb_strlen')) {
						$length = mb_strlen((string)$value);
					} else {
						$length = strlen((string)$value);
					}
					if ($length > intval($column_schema['max_length'])) {
						return array(false, __('max length over', self::DOMAIN));
					}
				}
			} else if (!preg_match('/^(datetime|timestamp|date|time|year)$/i', strtolower($column_schema['type']))) {
				if (function_exists('mb_strlen')) {
					$length = mb_strlen((string)$data);
				} else {
					$length = strlen((string)$data);
				}
				if ($length > intval($column_schema['max_length'])) {
					return array(false, __('max length over', self::DOMAIN));
				}
			}
		} else {
			if ($column_schema['not_null'])
				if (strval($data) != '0' && empty($data)) 
					return array(false, __('empty', self::DOMAIN));
		}
		return array(true, '');
	}
	
	/**
	 * Validate and finalization sql for create table
	 * @param string $table_name
	 * @param string $sql (for create table)
	 * @return array
	 */
	function validate_create_sql($table_name, $sql) {
	}
	
	/**
	 * Validate and finalization sql for alter table
	 * @param string $table_name
	 * @param string $sql (for alter table)
	 * @return array
	 */
	function validate_alter_sql($table_name, $sql) {
		//$org_sql = preg_replace("/\r|\n|\t/", '', $sql);
		$org_sql = trim(preg_replace("/[\s|\r|\n|\t]+/", ' ', $sql));
		//$reg_base = '/^(ALTER\sTABLE\s'. $table_name .'\s)(.*)$/iU';
		$reg_base = '/^(ALTER[\s]{1,}TABLE[\s}{1,}'. $table_name .'{\s]{0,})(.*)$/iU';
		if (preg_match($reg_base, $org_sql, $matches)) {
			
			$fixed_sql = $matches[1] .' '. preg_replace('/(.*)(,|;)$/iU', '$1', trim($matches[2])) . ';';
			$result = array(true, $fixed_sql);
		} else {
			$result = array(false, null);
		}
		return $result;
	}
	
	/**
	 * compare to reservation table names
	 * @param string $table_name
	 * @return boolean
	 */
	function compare_reservation_tables($table_name) {
		global $wpdb;
		$naked_table_name = preg_replace('/^'. $wpdb->prefix .'(.*)$/iU', '$1', $table_name);
		$reservation_names = array(
			'commentmeta', 'comments', 'links', 'options', 'postmeta', 'posts', 'term_relationships', 'term_taxonomy', 'terms', 'usermeta', 'users', 
			'blogs', 'blog_versions', 'registration_log', 'signups', 'site', 'sitecategories', 'sitemeta', 
		);
		return in_array($naked_table_name, $reservation_names);
	}
	
	/**
	 * import data from any table
	 * @param string $table_name
	 * @param array $import_data
	 * @return array
	 */
	function import_table($table_name, $import_data) {
		$table_name = !empty($table_name) ? $table_name : $this->current_table;
		if (!empty($import_data)) {
			list($is_table,,$table_schema) = $this->get_table_schema($table_name);
			if ($is_table) {
				$diff_ary = array_diff_assoc(array_keys($table_schema), array_keys($import_data[0]));
				if (empty($diff_ary)) {
					$insert_ids = array();
					$local_index = 1;
					foreach ($import_data as $one_data) {
						foreach ($table_schema as $column_name => $column_schema) {
							if ($column_schema['primary_key']) {
								if ($column_schema['extra'] == 'auto_increment') {
									unset($one_data[$column_name]);
								} elseif (empty($one_data[$column_name])) {
									$one_data[$column_name] = $local_index;
								}
							} elseif (!preg_match('/^(created|updated)$/i', $column_name)) {
								$validate_result = $this->validate_data($column_schema, $one_data[$column_name]);
								if (!array_shift($validate_result))
									$one_data[$column_name] = '';
							} else {
								if (empty($one_data[$column_name])) {
									unset($one_data[$column_name]);
								}
							}
						}
						$insert_ids[] = $this->insert_data($table_name, $one_data, $table_schema, false);
						$local_index++;
					}
					$result = array(true, sprintf(__('Data of %d was imported.', self::DOMAIN), count($insert_ids)));
				} else {
					$result = array(false, __('Import data is invalid.', self::DOMAIN));
				}
			} else {
				$result = array(false, __('Table is not exists', self::DOMAIN));
			}
		} else {
			$result = array(false, __('Import data is none.', self::DOMAIN));
		}
		return $result;
	}
	
	/**
	 * export data from any table
	 * @param string $table_name
	 * @param boolean $index_only (optional) default false
	 * @return array
	 */
	function export_table($table_name, $index_only=false) {
		$table_name = !empty($table_name) ? $table_name : $this->current_table;
		list($is_table,,$table_schema) = $this->get_table_schema($table_name);
		if ($is_table) {
			$data = array();
			$data[] = array_keys($table_schema);
			if (!$index_only) {
				$all_data = $this->get_data($table_name);
				foreach ($all_data as $data_obj) {
					$row = array();
					foreach ($data_obj as $key => $val) {
						$row[] = $val;
					}
					$data[] = $row;
				}
			}
			$result = array(true, $data);
		} else {
			$result = array(false, __('Table is not exists', self::DOMAIN));
		}
		return $result;
	}
	
	/**
	 * get table list in database
	 * @param string $narrow_type default 'enable' is managable table in this plugin.
	 * @return mixed
	 */
	function get_table_list($narrow_type='enable') {
	}
	

	/**
	 * incorporate table that created on out of plugin
	 * @param string $table_name
	 * @param 
	 * @return 
	 */
	function incorporate_table_option() {
		// Will be released in the next version
	}
	
}