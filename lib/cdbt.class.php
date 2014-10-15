<?php
class CustomDatabaseTables {
	
	/**
	 * plugin version
	 * @var string
	 */
	var $version = PLUGIN_VERSION;
	
	/**
	 * database version
	 * @var float
	 */
	var $db_version = DB_VERSION;
	
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
	protected $debug = false;
	
	/**
	 * constructor for PHP5
	 * @return array
	 */
	function __construct() {
		global $wpdb;
		
		foreach (explode(DS, dirname(__FILE__)) as $dir_name) {
			$this->dir .= (!empty($dir_name)) ? DS . $dir_name : '';
			if (self::DOMAIN == $dir_name) 
				break;
		}
		$path_list = explode('/', plugin_basename(__FILE__));
		$this->dir_url = @plugin_dir_url() . array_shift($path_list);
		
		load_plugin_textdomain(self::DOMAIN, false, basename($this->dir) . DS . 'langs');
		
		$this->options = get_option(self::DOMAIN);
		if (!empty($this->options['timezone'])) 
			date_default_timezone_set($this->options['timezone']);
		
		if ($this->options['plugin_version'] != $this->version) {
			if (version_compare($this->version, $this->options['plugin_version']) > 0) {
				$this->activate();
			}
		}
		
		$this->current_table = get_option(self::DOMAIN . '_current_table', '');
		
		CustomDataBaseTables_Ajax::instance();
		CustomDataBaseTables_Media::instance();
		
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
			$log_file_path = $this->dir . DS . 'log.txt';
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
		$this->log_info('cdbt plugin deactivated.');
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
		wp_parse_str($_SERVER['QUERY_STRING'], $this->query);
		//add_action('admin_init', array($this, 'admin_header'));
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
		cdbt_create_javascript();
	}
	
	/**
	 * load header for admin panel
	 * @return void
	 */
	function admin_header(){
		if (array_key_exists('page', $this->query) && $this->query['page'] == self::DOMAIN) {
			//
		}
	}
	
	/**
	 * load assets for admin panel
	 * @return void
	 */
	function admin_assets(){
		if (array_key_exists('page', $this->query) && $this->query['page'] == self::DOMAIN) {
			if (is_admin()) {
			wp_enqueue_style('cdbt-common-style', $this->dir_url . '/assets/css/cdbt-main.min.css', array(), $this->version, 'all');
			wp_enqueue_style('cdbt-admin-style', $this->dir_url . '/assets/css/cdbt-admin.css', true, $this->version, 'all');
			wp_register_script('cdbt-common-script', $this->dir_url . '/assets/js/scripts.min.js');
			wp_enqueue_script('jquery-ui-core');
			wp_enqueue_script('jquery-ui-widget');
			wp_enqueue_script('jquery-ui-mouse');
			wp_enqueue_script('jquery-ui-position');
			wp_enqueue_script('jquery-ui-sortable');
			wp_enqueue_script('jquery-ui-autocomplete');
			wp_enqueue_script('cdbt-common-script');
			}
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
		if (!empty($table_data['table_name'])) {
			$table_name =  $table_data['table_name'];
		} else {
			preg_match('/^create\stable\s(|`)(.*)(|`)\s\(.*/iU', $table_data['sql'], $matches);
			$table_name = trim($matches[2]);
		}
		if (!$this->check_table_exists($table_name)) {
			// if not exists table, create table.
			//$create_sql = $wpdb->prepare($table_data['sql'], $table_data['db_engine'], $this->options['charset']);
			$create_sql = $table_data['sql'];
			if (isset($create_sql) && !empty($create_sql)) {
				require_once ABSPATH . 'wp-admin/includes/upgrade.php';
				dbDelta($create_sql);
				if (!empty($wpdb->last_error) && !$this->check_table_exists($table_name)) {
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
				$is_int_column = (preg_match('/^((|tiny|small|medium|big)int|float|double(| precision)|real|dec(|imal)|numeric|fixed|bool(|ean)|bit)$/i', strtolower($column_schema->DATA_TYPE)) ? true : false);
				$is_chr_column = (preg_match('/^((|var|national |n)char(|acter)|(|tiny|medium|long)text|(|tiny|medium|long)blob|(|var)binary|enum|set)$/i', strtolower($column_schema->DATA_TYPE)) ? true : false);
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
			$result = array(false, __('Table is not exists', self::DOMAIN));
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
			$sql = $wpdb->prepare("SHOW TABLE STATUS LIKE %s", $table_name);
			foreach ($wpdb->get_results($sql) as $data) {
				if (!empty($data->Comment)) {
					$result = array(true, $data->Comment);
					break;
				} else {
					$result = array(false, __('Table comment is none', self::DOMAIN));
				}
			}
		} else {
			$result = array(false, __('Table is not exists', self::DOMAIN));
		}
		return $result;
	}
	
	/**
	 * get create table sql
	 * @param string $table_name (optional) default $this->current_table
	 * @return array
	 */
	function get_create_table_sql($table_name=null) {
		global $wpdb;
		$table_name = !empty($table_name) ? $table_name : $this->current_table;
		if ($this->check_table_exists($table_name)) {
			$sql = 'SHOW CREATE TABLE `'. $table_name . '`';
			$temp = $wpdb->get_results($sql);
			$temp = array_shift($temp);
			foreach ($temp as $key => $data) {
				if ($key == 'Create Table') {
					$result = array(true, $data);
					break;
				}
			}
		} else {
			$result = array(false, __('Table is not exists', self::DOMAIN));
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
	function get_data($table_name, $columns='*', $conditions=null, $order=array('created'=>'desc'), $limit=null, $offset=null) {
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
	function find_data($table_name, $table_schema=null, $search_key, $columns, $order=array('created'=>'desc'), $limit=null, $offset=null) {
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
		}
		if (!empty($limit)) {
			$limit_clause = "LIMIT ";
			$limit_clause .= (!empty($offset)) ? intval($offset) .', '. intval($limit) : intval($limit);
		}
		$search_key = preg_replace('/[\s　]+/u', ' ', trim($search_key), -1);
		$keywords = preg_split('/[\s]/', $search_key, 0, PREG_SPLIT_NO_EMPTY);
		if (!empty($keywords)) {
			if (!empty($table_schema)) 
				list(, , $table_schema) = $this->get_table_schema($table_name);
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
	 * @return int $insert_id
	 */
	function insert_data($table_name, $data, $table_schema=null) {
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
				$is_exists_created = true;
			if ($key == 'updated') 
				$is_exists_updated = true;
		}
		if (!empty($primary_key_name)) {
			if ($primary_key_a_i) {
				unset($data[$primary_key_name]);
			} else {
				$primary_key_value = $data[$primary_key_name];
			}
		}
		if ($is_exists_created) 
			$data['created'] = date('Y-m-d H:i:s', time());
		if ($is_exists_updated) 
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
	 * update data
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
		if ($is_exists_created && array_key_exists('created', $data)) 
			unset($data['created']);
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
			if (empty($data)) 
				return array(false, __('empty', self::DOMAIN));
		}
		if (!empty($data)) {
			if (preg_match('/^((|tiny|small|medium|big)int|float|double(| precision)|real|dec(|imal)|numeric|fixed|bool(|ean)|bit)$/i', strtolower($column_schema['type']))) {
				if (strtolower($column_schema['type_format']) != 'tinyint(1)') {
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
					if (!preg_match('/^(\-|)[0-9]+$/', $data)) 
						return array(false, __('not integer', self::DOMAIN));
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
		$org_sql = preg_replace("/\r|\n|\t/", '', $sql);
		$reg_base = '/^(CREATE\sTABLE\s'. $table_name .'\s\()(.*)$/iU';
		if (preg_match($reg_base, $org_sql, $matches)) {
			// parse while verification
			$sql_head = $matches[1];
			$reg_type = '((|tiny|small|medium|big)int|float|double(| precision)|decimal|numeric|fixed|bool(|ean)|bit|(|var)char|(|tiny|medium|long)text|(|tiny|medium|long)blob|(|var)binary|enum|set|date(|time)|time(|stamp)|year)';
			$reg_base = "/(|\s)((|`).*(|`)\s". $reg_type ."(|\(.*\))(\s.*(COMMENT\s'.*'|)|)(,|\)))+/iU";
			$parse_body = array();
			while (preg_match($reg_base, $matches[2], $one_column)) {
				$matches[2] = str_replace($one_column[0], '', $matches[2]);
				if (substr_count($one_column[0], '(') < substr_count($one_column[0], ')')) {
					$parse_body[] = trim(substr_replace($one_column[0], '', strrpos($one_column[0], ')'), 1), ', ');
				} else {
					$parse_body[] = trim($one_column[0], ', ');
				}
			}
			$reg_key = '/((primary key|key|index|unique(| index)|fulltext(| index)|foreign key|check)\s(|.*\s)\(.*\)(,|\)|\s\)))+/iU';
			$parse_key = array();
			while (preg_match($reg_key, $matches[2], $one_key)) {
				$matches[2] = str_replace($one_key[0], '', $matches[2]);
				if (substr_count($one_key[0], '(') < substr_count($one_key[0], ')')) {
					$parse_key[] = trim(substr_replace($one_key[0], '', strrpos($one_key[0], ')'), 1), ', ');
				} else {
					$parse_key[] = trim($one_key[0], ', ');
				}
			}
			$parse_option = array();
			$reg_opt = '(type|engine|auto_increment|avg_row_length|checksum|comment|(max|min)_rows|pack_keys|password|delay_key_write|row_format|raid_type|union|insert_method|(data|index) directory|default char(acter set|set))';
			$reg_base = "/(". $reg_opt ."(|\s)(|=)(|\s)(|'|\().*(|'|\))\s)+/iU";
			while (preg_match($reg_base, $matches[2], $one_opt)) {
				$matches[2] = str_replace($one_opt[0], '', $matches[2]);
				if (strtolower($one_opt[2]) == 'type' || strtolower($one_opt[2]) == 'engine') {
					$parse_option[] = trim(preg_replace('/^(.*)(|\s)=(|\s)(BDB|HEAP|ISAM|InnoDB|MERGE|MRG_MYISAM|MYISAM|MyISAM)/', '$1$2=$3%s', $one_opt[0]));
				} else if (strtolower($one_opt[2]) == 'default character set' || strtolower($one_opt[2]) == 'default charset') {
					$parse_option[] = trim(preg_replace("/^(.*)(|\s)=(|\s)(.*)$/iU", '$1$2=$3%s', $one_opt[0]));
				} else if (strtolower($one_opt[2]) == 'comment') {
					$parse_option[] = trim(preg_replace("/^(.*)(|\s)=(|\s)'(.*)'/iU", "$1$2=$3'%s'", $one_opt[0]));
				} else {
					$parse_option[] = trim($one_opt[0]);
				}
			}
			$endpoint = trim($matches[2]);
			if ((empty($endpoint) || $endpoint == ')' || $endpoint == ';') && !empty($parse_body)) {
				// make finalization sql
				$add_fields[0] = "`ID` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '". __('ID', self::DOMAIN) ."'";
				$add_fields[1] = "`created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '". __('Created Date', self::DOMAIN) ."'";
				$add_fields[2] = "`updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '". __('Updated Date', self::DOMAIN) ."'";
				foreach ($add_fields as $i => $field) {
					if (!in_array($field, $parse_body)) {
						if ($i == 0) {
							array_unshift($parse_body, $field);
						} else {
							array_push($parse_body, $field);
						}
					}
				}
				$add_key = "PRIMARY KEY (`ID`)";
				if (!in_array($add_key, $parse_key)) {
					array_unshift($parse_key, $add_key);
				}
				$add_option = array(
					'ENGINE|TYPE' => "ENGINE=%s", 
					'DEFAULT CHAR' => "DEFAULT CHARSET=%s", 
					'COMMENT' => "COMMENT='%s'", 
				);
				if (empty($parse_option)) {
					foreach ($add_option as $option) {
						array_push($parse_option, $option);
					}
				} else {
					foreach ($add_option as $key => $option) {
						$is_option = false;
						foreach ($parse_option as $get_option) {
							if (preg_match('/^('.$key.')/i', $get_option)) {
								$is_option = true;
								break;
							}
						}
						if (!$is_option) {
							array_push($parse_option, $option);
						}
					}
				}
				$ds = empty($parse_key) ? " \n" : ", \n";
				$fixed_sql = $sql_head ."\n". implode(", \n", $parse_body) .$ds. implode(", \n", $parse_key) ."\n) \n". implode(" \n", $parse_option) . ' ;';
				$result = array(true, $fixed_sql);
			} else {
				$result = array(false, null);
			}
		} else {
			$result = array(false, null);
		}
		return $result;
	}
	
	/**
	 * Validate and finalization sql for alter table
	 * @param string $table_name
	 * @param string $sql (for alter table)
	 * @return array
	 */
	function validate_alter_sql($table_name, $sql) {
		$org_sql = preg_replace("/\r|\n|\t/", '', $sql);
		$reg_base = '/^(ALTER\sTABLE\s'. $table_name .'\s)(.*)$/iU';
		if (preg_match($reg_base, $org_sql, $matches)) {
			
			$fixed_sql = $matches[1] . preg_replace('/(.*)(,|;)$/iU', '$1', trim($matches[2])) . ';';
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
					foreach ($import_data as $one_data) {
						foreach ($table_schema as $column_name => $column_schema) {
							if (!preg_match('/^(ID|created|updated)$/i', $column_name)) {
								$validate_result = $this->validate_data($column_schema, $one_data[$column_name]);
								if (!array_shift($validate_result))
									$one_data[$column_name] = '';
							} else {
								unset($one_data[$column_name]);
							}
						}
						$insert_ids[] = $this->insert_data($table_name, $one_data, $table_schema);
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
		global $wpdb;
		$res = $wpdb->get_results('SHOW TABLES', 'ARRAY_N');
		$table_list = array();
		foreach ($res as $i => $one_res) {
			$table_list[] = $one_res[0];
		}
		if (!empty($table_list)) {
			if ($narrow_type == 'unreserved' || $narrow_type == 'unmanageable') {
				foreach ($table_list as $i => $table_name) {
					if ($this->compare_reservation_tables($table_name)) {
						unset($table_list[$i]);
					}
				}
				if ($narrow_type == 'unmanageable') {
					foreach ($table_list as $i => $table_name) {
						foreach ($this->options['tables'] as $j => $table_data) {
							if (cdbt_compare_var($table_name, $table_data['table_name'])) {
								unset($table_list[$i]);
								break;
							}
						}
					}
				}
			} else if ($narrow_type == 'enable') {
				foreach ($table_list as $i => $table_name) {
					$is_enable = false;
					foreach ($this->options['tables'] as $j => $table_data) {
						if (cdbt_compare_var($table_name, $table_data['table_name'])) {
							$is_enable = true;
							break;
						}
					}
					if (!$is_enable) 
						unset($table_list[$i]);
				}
			}
			return $table_list;
		} else {
			return false;
		}
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