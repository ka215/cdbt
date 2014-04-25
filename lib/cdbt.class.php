<?php
class CustomDatabaseTables {
	
	/**
	 * plugin version
	 * @var string
	 */
	var $version;
	
	/**
	 * database version
	 * @var float
	 */
	var $db_version;
	
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
	const DOMAIN = PLUGIN_SLUG;
	
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
	protected $debug = true;
	
	/**
	 * constructor for PHP5
	 * @return array
	 */
	function __construct() {
		global $wpdb;
		
		/*
		if (!isset($_SESSION)) {
			session_start();
		}
		if (!isset($_SESSION[$this->session]) || empty($_SESSION[$this->session])) {
			$_SESSION[$this->session] = array();
		}
		*/
		foreach (explode(DS, dirname(__FILE__)) as $dir_name) {
			$this->dir .= (!empty($dir_name)) ? DS . $dir_name : '';
			if (self::DOMAIN == $dir_name) 
				break;
		}
		$path_list = explode('/', plugin_basename(__FILE__));
		$this->dir_url = @plugin_dir_url() . array_shift($path_list);
		
		load_plugin_textdomain(self::DOMAIN, false, basename($this->dir) . DS . 'langs');
		
		$this->options = get_option(self::DOMAIN, '');
		if (!empty($this->options['timezone'])) 
			date_default_timezone_set($this->options['timezone']);
		
		$this->current_table = get_option(self::DOMAIN . '_current_table', '');
		
//		add_action('wp_enqueue_scripts', array($this, 'load_common_assets'));
		add_filter('plugin_action_links', array($this, 'add_action_links'), 10, 2);
		add_action('admin_menu', array($this, 'create_admin'));
		
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
			error_log("[$now_datetime] CURRENT_TABLE=\"$this->current_table\" INFO=\"$msg\" ;\n", 3, $this->dir . DS . 'log.txt');
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
			'plugin_version' => PLUGIN_VERSION, 
			'db_version' => (float)1.0, 
			'use_wp_prefix' => true, 
			'charset' => DB_CHARSET, 
			'timezone' => $default_timezone, 
			'cleaning_options' => true, 
			'tables' => array(
				array(
					'table_name' => $wpdb->prefix . 'cdbt_controller', 
					'table_type' => 'controller_table', 
					'sql' => "CREATE TABLE `". $wpdb->prefix . "cdbt_controller` (
							`ID` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '". __('ID', self::DOMAIN) ."',
							`table_name` varchar(64) NOT NULL COMMENT '". __('Table Name', self::DOMAIN) ."',
							`enable` boolean DEFAULT 1 COMMENT '". __('Enable', self::DOMAIN) ."',
							`create_sql` text COMMENT '". __('Create Table SQL', self::DOMAIN) ."',
							`show_max_records` tinyint(4) DEFAULT 10 COMMENT '". __('Show Max Records', self::DOMAIN) ."',
							`view_role` enum('0','1','2','3','4','5','6','7','8','9') DEFAULT '1' COMMENT '". __('View Role', self::DOMAIN) ."',
							`input_role` enum('0','1','2','3','4','5','6','7','8','9') DEFAULT '3' COMMENT '". __('Input Role', self::DOMAIN) ."',
							`edit_role` enum('0','1','2','3','4','5','6','7','8','9') DEFAULT '5' COMMENT '". __('Edit Role', self::DOMAIN) ."',
							`admin_role` enum('0','1','2','3','4','5','6','7','8','9') DEFAULT '9' COMMENT '". __('Admin Role', self::DOMAIN) ."',
							`created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '". __('Created Date', self::DOMAIN) ."',
							`updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '". __('Updated Date', self::DOMAIN) ."',
							PRIMARY KEY (`ID`)
						) ENGINE=%s DEFAULT CHARSET=%s COMMENT='". __('Custom Database Tables Controller', self::DOMAIN) ."' AUTO_INCREMENT=1 ;", 
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
		
		$this->options = get_option(self::DOMAIN, $default_options);
		if (in_array('plugin_version', $this->options)) {
			if ($this->options['plugin_version'] != $default_options['plugin_version']) {
				if (version_compare($default_options['plugin_version'], $this->options['plugin_version']) >= 0) {
					update_option(self::DOMAIN . '_previous_revision_backup', $this->options);
					$this->options = array_merge($this->options, $default_options);
				}
			}
		} else {
			update_option(self::DOMAIN . '_previous_revision_backup', $this->options);
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
		$this->db_version = $this->options['db_version'];
		date_default_timezone_set($this->options['timezone']);
		
		update_option(self::DOMAIN, $this->options);
	}
	
	/**
	 * plugin deactivation
	 * @return void
	 */
	function deactivation(){
		//delete_option(self::DOMAIN . '_current_table');
		$this->log_info('cdbt plugin deactivated.');
	}
	
	/**
	 * load stylesheet and javascript for this plugin (common assets)
	 * @return void
	 */
	function load_common_assets(){
		wp_enqueue_style('cdbt_common_style', $this->dir_url . '/assets/css/cdbt-main.min.css', false, null, 'all');
		wp_enqueue_script('cdbt_common_script', $this->dir_url . '/assets/js/script.min.js', null, null, false);
		//wp_enqueue_style('cdbt_style', $this->dir_url . '/assets/css/cdbt-style.css', false, self::$version, 'all');
		//wp_enqueue_script('cdbt_script', $this->dir_url . '/assets/js/cdbt-main.js', null, self::$version, true);
	}
	
	/**
	 * append action links to this plugin on list page
	 * @return array
	 */
	function add_action_links($links, $file){
		if ($file == self::DOMAIN . '/cdbt.php') {
			$links[] = '<a href="'. admin_url('options-general.php?page=' . self::DOMAIN) .'">'. __('Settings') .'</a>';
			// $links[] = '<a href="http://www.ka2.org/custom-database-tables/pro/" target="_blank">'. __('Upgrade', self::DOMAIN) .'</a>';
		}
		return $links;
	}
	
	/**
	 * create admin panel
	 * @return void
	 */
	function create_admin(){
		add_options_page(__('Custom Database Tables Option: ', self::DOMAIN), __('Custom Database Tables', self::DOMAIN), 'manage_options', self::DOMAIN, array($this, 'admin_controller'), plugin_dir_url(__FILE__) . 'assets/img/undo.png');
		//add_submenu_page(plugin_dir_url(__FILE__), __('Custom Database Tables', self::DOMAIN), __('Custom Database Tables', self::DOMAIN), 'manage_options', self::DOMAIN, array($this, 'admin_controller'));
		wp_parse_str($_SERVER['QUERY_STRING'], $this->query);
		add_action('admin_init', array($this, 'admin_header'));
		add_action('admin_enqueue_scripts', array($this, 'admin_assets'));
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
		require_once PLUGIN_TMPL_DIR . DS . $template_name;
	}
	
	/**
	 * load header for admin panel
	 * @return void
	 */
	function admin_header(){
		if (array_key_exists('page', $this->query) && $this->query['page'] == self::DOMAIN) {
			# printf('<h2>%s</h2>', __('Custom DataBase Tables Management console', self::DOMAIN));
		}
	}
	
	/**
	 * load assets for admin panel
	 * @return void
	 */
	function admin_assets(){
		if (array_key_exists('page', $this->query) && $this->query['page'] == self::DOMAIN) {
			wp_enqueue_style('cdbt_common_style', $this->dir_url . '/assets/css/cdbt-main.min.css', false, $this->version, 'all');
			wp_enqueue_style('cdbt_admin_style', $this->dir_url . '/assets/css/cdbt-admin.css', false, $this->version, 'all');
			wp_enqueue_script('cdbt_common_script', $this->dir_url . '/assets/js/scripts.min.js', null, null, false);
			wp_enqueue_script('cdbt_admin_script', $this->dir_url . '/assets/js/cdbt-admin.js.php', null, $this->version, true);
		}
	}
	
	/**
	 * show notice on admin panel
	 * @return void
	 */
	function admin_notice(){
		if (array_key_exists('page', $this->query) && $this->query['page'] == self::DOMAIN) {
			$notice_base = '<div class="%s"><ul>%s</ul></div>';
			if (!empty($this->error)) {
				$notice_list = '';
				foreach ($this->error as $error) {
					$notice_list .= '<li>' . $error . '</li>';
				}
				printf($notice_base, 'error', $notice_list);
			}
			if (!empty($this->message)) {
				$notice_list = '';
				foreach ($this->message as $message) {
					$notice_list .= '<li>' . $message . '</li>';
				}
				printf($notice_base, 'updated', $notice_list);
			}
		}
	}
	
// //////////////////// following CRUD //////////////////////////////////////////////////
	
	/**
	 * Check table exists
	 * @param string $table_name (optional) default $this->current_table
	 * @return string
	 */
	function check_table_exists($table_name=null) {
		global $wpdb;
		$table_name = !empty($table_name) ? $table_name : $this->current_table;
		$is_tbl_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
		return $is_tbl_exists;
	}
	
	/**
	 * Truncate table
	 * @param string $table_name (optional) default $this->current_table
	 * @return array
	 */
	function truncate_table($table_name=null) {
		global $wpdb;
		$table_name = !empty($table_name) ? $table_name : $this->current_table;
		if ($this->check_table_exists($table_name)) {
			// if exists table, truncate table.
			$e = $wpdb->query("TRUNCATE TABLE `". $table_name ."`");
			if ($e) {
				$result = array(true, __('Completed to truncate table.', self::DOMAIN));
			} else {
				$result = array(false, __('Failed to truncate table.', self::DOMAIN));
			}
		} else {
			$result = array(false, __('Table is not exists', self::DOMAIN));
		}
		return $result;
	}
	
	/**
	 * Drop table
	 * @param string $table_name (optional) default $this->current_table
	 * @return array
	 */
	function drop_table($table_name=null) {
		global $wpdb;
		$table_name = !empty($table_name) ? $table_name : $this->current_table;
		if ($this->check_table_exists($table_name)) {
			// if exists table, drop table.
			$e = $wpdb->query("DROP TABLE `". $table_name . "`");
			if ($e) {
				$result = array(true, __('Completed to drop table.', self::DOMAIN));
			} else {
				$result = array(false, __('Failed to drop table.', self::DOMAIN));
			}
		} else {
			$result = array(false, __('Table is not exists', self::DOMAIN));
		}
		return $result;
	}
	
	/**
	 * Create table
	 * @param array $table_data
	 * @return array
	 */
	function create_table($table_data) {
		global $wpdb;
		if (!$this->check_table_exists()) {
			// if not exists table, create table.
			$create_sql = $wpdb->prepare($table_data['sql'], $table_data['db_engine'], $this->options['charset']);
			if (isset($create_sql) && !empty($create_sql)) {
				require_once ABSPATH . 'wp-admin/includes/upgrade.php';
				dbDelta($create_sql);
				if (!empty($wpdb->last_error) && !$this->check_table_exists()) {
					$result = array(false, __('Failed to create table.', self::DOMAIN));
				} else {
					$result = array(true, __('New table was created.', self::DOMAIN));
					$wpdb->flush();
				}
			} else {
				$result = array(false, __('Create table sql is none.', self::DOMAIN));
			}
		} else {
			$result = array(false, __('This table is already created.', self::DOMAIN));
		}
		return $result;
	}
	
	/**
	 * Get table schema
	 * @param string $table_name (optional) default $this->current_table
	 * @return array
	 */
	function get_table_schema($table_name=null) {
		global $wpdb;
		$table_name = !empty($table_name) ? $table_name : $this->current_table;
		if ($this->check_table_exists($table_name)) {
			$sql = $wpdb->prepare("SELECT 
				COLUMN_NAME,COLUMN_DEFAULT,IS_NULLABLE,DATA_TYPE,
				CHARACTER_MAXIMUM_LENGTH,CHARACTER_OCTET_LENGTH,
				NUMERIC_PRECISION,NUMERIC_SCALE,
				COLUMN_TYPE,COLUMN_KEY,EXTRA,COLUMN_COMMENT 
				FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s 
				ORDER BY ORDINAL_POSITION", 
				DB_NAME, $table_name
			);
			$table_schema = array();
			foreach ($wpdb->get_results($sql) as $column_schema) {
				$is_int_column = (preg_match('/^((|tiny|small|medium|big)int|float|doubleprecision|real|dec(|imal)|numeric|fixed|bool(|ean)|bit)$/i', strtolower($column_schema->DATA_TYPE)) ? true : false);
				$is_chr_column = (preg_match('/^((|var|national|n)char(|acter)|(|tiny|medium|long)text|(|tiny|medium|long)blob|(|var)binary|enum|set)$/i', strtolower($column_schema->DATA_TYPE)) ? true : false);
				$is_date_column = (preg_match('/^(date(|time)|time(|stamp)|year)$/i', strtolower($column_schema->DATA_TYPE)) ? true : false);
				$table_schema[$column_schema->COLUMN_NAME] = array(
					'logical_name' => $column_schema->COLUMN_COMMENT, 
					'max_length' => null, 
					'octet_length' => intval($column_schema->CHARACTER_OCTET_LENGTH), 
					'not_null' => (strtoupper($column_schema->IS_NULLABLE) == 'NO' ? true : false), 
					'default' => $column_schema->COLUMN_DEFAULT, 
					'type' => $column_schema->DATA_TYPE, 
					'type_format' => $column_schema->COLUMN_TYPE, 
					'primary_key' => (strtoupper($column_schema->COLUMN_KEY) == 'PRI' ? true : false), 
					'column_key' => $column_schema->COLUMN_KEY, 
					'unsigned' => (preg_match('/unsigned/i', strtolower($column_schema->COLUMN_TYPE)) ? true : false),
					'extra' => $column_schema->EXTRA, 
				);
				if ($is_int_column) {
					$total_length = intval($column_schema->NUMERIC_PRECISION) + intval($column_schema->NUMERIC_SCALE);
					$table_schema[$column_schema->COLUMN_NAME]['max_length'] = $table_schema[$column_schema->COLUMN_NAME]['octet_length'] = $total_length;
				}
				if ($is_chr_column) {
					$table_schema[$column_schema->COLUMN_NAME]['max_length'] = intval($column_schema->CHARACTER_MAXIMUM_LENGTH);
				}
				if ($is_date_column) {
					if ($column_schema->DATA_TYPE == 'year') 
						$string_length = strlen('YYYY');
					if ($column_schema->DATA_TYPE == 'time')
						$string_length = strlen('HH:MM:SS');
					if ($column_schema->DATA_TYPE == 'date')
						$string_length = strlen('YYYY-MM-DD');
					if ($column_schema->DATA_TYPE == 'datetime' || $column_schema->DATA_TYPE == 'timestamp')
						$string_length = strlen('YYYY-MM-DD HH:MM:SS');
					$table_schema[$column_schema->COLUMN_NAME]['max_length'] = $string_length;
				}
			}
			$result = array(true, $table_name, $table_schema);
		} else {
			$result = array(false, __('table is not exists', self::DOMAIN));
		}
		return $result;
	}
	
	/**
	 * get table comment
	 * @param string $table_name (optional) default $this->current_table
	 * @return array
	 */
	function get_table_comment($table_name=null) {
		global $wpdb;
		$table_name = !empty($table_name) ? $table_name : $this->current_table;
		if ($this->check_table_exists($table_name)) {
			$sql = $wpdb->prepare("SHOW TABLE STATUS LIKE '%s'", $table_name);
			foreach ($wpdb->get_results($sql) as $data) {
				if (!empty($data->Comment)) {
					$result = array(true, $data->Comment);
					break;
				} else {
					$result = array(false, __('table comment is none', self::DOMAIN));
				}
			}
		} else {
			$result = array(false, __('table is not exists', self::DOMAIN));
		}
		return $result;
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
	function get_data($table_name, $columns='*', $conditions=null, $order=null, $limit=null, $offset=null) {
		global $wpdb;
		$select_clause = is_array($columns) ? implode(',', $columns) : (!empty($columns) ? $columns : '*');
		$where_clause = $order_clause = $limit_clause = null;
		if (!empty($conditions)) {
			$i = 0;
			foreach ($conditions as $key => $val) {
				if ($i == 0) {
					$where_clause = "WHERE `$key` = '$val' ";
				} else {
					$where_clause .= "AND `$key` = '$val' ";
				}
				$i++;
			}
		}
		if (!empty($order)) {
			$i = 0;
			foreach ($order as $key => $val) {
				$val = strtoupper($val) == 'DESC' ? 'DESC' : 'ASC';
				if ($i == 0) {
					$order_clause = "ORDER BY `$key` $val ";
				} else {
					$order_clause .= ", `$key` $val ";
				}
				$i++;
			}
		} else {
			$order_clause = "ORDER BY `created` DESC ";
		}
		if (!empty($limit)) {
			$limit_clause = "LIMIT ";
			$limit_clause .= (!empty($offset)) ? intval($offset) .', '. intval($limit) : intval($limit);
		}
		$sql = sprintf(
			"SELECT %s FROM `%s` %s %s %s", 
			$select_clause, 
			$table_name, 
			$where_clause, 
			$order_clause, 
			$limit_clause 
		);
		return $wpdb->get_results($sql);
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
	function find_data($table_name, $table_schema=null, $search_key, $columns, $order=null, $limit=null, $offset=null) {
		global $wpdb;
		$select_clause = is_array($columns) ? implode(',', $columns) : (!empty($columns) ? $columns : '*');
		$where_clause = $order_clause = $limit_clause = null;
		if (!empty($order)) {
			$i = 0;
			foreach ($order as $key => $val) {
				$val = strtoupper($val) == 'DESC' ? 'DESC' : 'ASC';
				if ($i == 0) {
					$order_clause = "ORDER BY `$key` $val ";
				} else {
					$order_clause .= ", `$key` $val ";
				}
				$i++;
			}
		} else {
			$order_clause = "ORDER BY `created` DESC ";
		}
		if (!empty($limit)) {
			$limit_clause = "LIMIT ";
			$limit_clause .= (!empty($offset)) ? intval($offset) .', '. intval($limit) : intval($limit);
		}
		$search_key = preg_replace('/[\s　]+/', ' ', trim($search_key), -1);
		$keywords = preg_split('/[\s]/', $search_key, 0, PREG_SPLIT_NO_EMPTY);
		if (!empty($keywords)) {
			$union_clauses = array();
			foreach ($keywords as $value) {
				if (!empty($table_schema)) {
					unset($table_schema['ID'], $table_schema['created'], $table_schema['updated']);
					$target_columns = array();
					foreach ($table_schema as $column_name => $column_info) {
						if (is_float($value)) {
							if (preg_match('/^(float|doubleprecision|real|dec(|imal)|numeric|fixed)$/', $column_info['type'])) 
								$target_columns[] = $column_name;
						} else if (is_int($value)) {
							if (preg_match('/^((|tiny|small|medium|big)int|bool(|ean)|bit)$/', $column_info['type'])) 
								$target_columns[] = $column_name;
						}
						if (preg_match('/^((|var|national|n)char(|acter)|(|tiny|medium|long)text|(|tiny|medium|long)blob|(|var)binary|enum|set)$/', $column_info['type'])) 
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
				break;
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
	 * @return int $insert_id
	 */
	function insert_data($table_name, $data, $table_schema=null) {
		global $wpdb;
		unset($data['ID']);
		$data['created'] = date('Y-m-d H:i:s', time());
		unset($data['updated']);
		if (!empty($table_schema)) {
			$format = array();
			foreach ($data as $column_name => $value) {
				if (array_key_exists($column_name, $table_schema)) {
					if (preg_match('/^((|tiny|small|medium|big)int|bool(|ean)|bit)$/', $table_schema[$column_name]['type']) && preg_match('/^\d+$/', $value)) {
						// is integer format
						$format[] = '%d';
					} else if (preg_match('/^(float|doubleprecision|real|dec(|imal)|numeric|fixed)$/', $table_schema[$column_name]['type']) && preg_match('/^\d+(.{,1}\d+)?$/', $value)) {
						// is double format
						$format[] = '%f';
					} else {
						// is string format
						$format[] = '%s';
					}
				}
			}
		}
		if (isset($format) && !empty($format) && count($data) == count($format)) {
			$wpdb->insert($table_name, $data, $format);
		} else {
			$wpdb->insert($table_name, $data);
		}
		return $wpdb->insert_id;
	}
	
	/**
	 * update data
	 * @param string $table_name (must containing prefix of table)
	 * @param int $ID
	 * @param array $data
	 * @param array $table_schema default null
	 * @return int updated row (eq. ID column's value)
	 */
	function update_data($table_name, $ID, $data, $table_schema=null) {
		global $wpdb;
		$ID = intval($ID);
		if (array_key_exists('ID', $data)) 
			unset($data['ID']);
		if (array_key_exists('created', $data)) 
			unset($data['created']);
		if (array_key_exists('updated', $data)) 
			unset($data['updated']);
		if (!empty($table_schema)) {
			$format = array();
			foreach ($data as $column_name => $value) {
				if (array_key_exists($column_name, $table_schema)) {
					if (preg_match('/^((|tiny|small|medium|big)int|bool(|ean)|bit)$/', $table_schema[$column_name]['type']) && preg_match('/^\d+$/', $value)) {
						// is integer format
						$data[$column_name] = (int)$value;
						$format[] = '%d';
					} else if (preg_match('/^(float|doubleprecision|real|dec(|imal)|numeric|fixed)$/', $table_schema[$column_name]['type']) && preg_match('/^\d+(.{,1}\d+)?$/', $value)) {
						// is double format
						$data[$column_name] = (float)$value;
						$format[] = '%f';
					} else {
						// is string format
						$data[$column_name] = (string)$value;
						$format[] = '%s';
					}
					if (empty($value)) 
						$value = null;
				}
			}
		}
		if ($ID > 0 && isset($format) && !empty($format) && count($data) == count($format)) {
			$result = $wpdb->update($table_name, $data, array('ID' => $ID), $format, array('%d'));
			$result = ($result) ? $ID : $result;
			return $result;
		} else {
			return 0;
		}
	}
	
	/**
	 * delete data
	 * @param string $table_name (must containing prefix of table)
	 * @param int $ID
	 * @return bool
	 */
	function delete_data($table_name, $ID) {
		global $wpdb;
		$ID = intval($ID);
		return $wpdb->delete($table_name, array('ID' => $ID), array('%d'));
	}
	
	/**
	 * data verification by column schema
	 * @param array $column_schema
	 * @param string $data
	 * @return array
	 */
	function validate_data($column_schema, $data) {
		if ($column_schema['not_null'] && $column_schema['default'] == null) {
			if (empty($data)) 
				return array(false, __('empty', self::DOMAIN));
		}
		if (!empty($data)) {
			if (preg_match('/^((|tiny|small|medium|big)int|float|doubleprecision|real|dec(|imal)|numeric|fixed|bool(|ean)|bit)$/i', strtolower($column_schema['type']))) {
				if (strtolower($column_schema['type_format']) != 'tinyint(1)') {
					if (preg_match('/^((|tiny|small|medium|big)int|bool(|ean))$/i', strtolower($column_schema['type']))) {
						$data = intval($data);
						if (!is_int($data)) 
							return array(false, __('not integer', self::DOMAIN));
					} else {
						$data = floatval($data);
						if (!preg_match('/^(\-|)[0-9]+\.?[0-9]+$/', $data)) 
							return array(false, __('not integer', self::DOMAIN));
					}
				} else {
					$data = intval($data);
					if (!preg_match('/^(\-|)[0-9]+$/', $data)) 
						return array(false, __('not integer', self::DOMAIN));
				}
				if ($column_schema['unsigned']) {
					if ($data < 0) 
						return array(false, __('not a positive number', self::DOMAIN));
				}
			}
			if (preg_match('/^((|var|national|n)char(|acter)|(|tiny|medium|long)text|(|tiny|medium|long)blob|(|var)binary)$/i', strtolower($column_schema['type']))) {
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
				return array(false, __('empty', self::DOMAIN));
		}
		return array(true, '');
	}
	
	/**
	 * Validate sql for create table
	 * @param string $table_name (must containing prefix of table) default 'cdbt_sample'
	 * @param string $sql (for create table)
	 * @return array
	 */
	function validate_sql_create_table($table_name='cdbt_sample', $sql) {
	//var_dump(str_replace("\\", '', preg_replace("/\r|\n|\t/", '', $sql)));
	//	$reg_base = '/^CREATE\sTABLE\s(.*)+\s(.*)+\sENGINE=(innoDB|MyISAM)\sDEFAULT\sCHARSET=(.*)+\s(.*)+;$/i';
		$reg_base = "/^CREATE TABLE (.*)? \(`ID` int\(11\) unsigned NOT NULL AUTO_INCREMENT(.*)?`created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00'( COMMENT \'(.*)?\'|),`updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP( COMMENT \'(.*)?\'|),PRIMARY KEY \(`ID`\),UNIQUE KEY `ID` \(`ID`\)\) ENGINE=(InnoDB|MyISAM) DEFAULT CHARSET=(.*)?( COMMENT='(.*)?'|) AUTO_INCREMENT=1 ;/i";
		if (preg_match($reg_base, str_replace("\\", '', preg_replace("/\r|\n|\t/", '', trim($sql))), $matches)) {
var_dump($matches);
			if ($matches[1] == $table_name) {
				$fixed_sql = str_replace("\\", '', $sql);
				$fixed_sql = str_replace($matches[1], '%s', $fixed_sql);
				$fixed_sql = str_replace('CHARSET='.$matches[4], '%s', $fixed_sql);
				$result = array(true, $fixed_sql);
			}
		} else {
			$result = array(false, '');
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
 * This function features description
 * @param 
 * @return 
 */

}
