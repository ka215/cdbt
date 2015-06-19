<?php

namespace CustomDataBaseTables\Lib;


if ( !defined( 'CDBT' ) ) exit;

if ( !class_exists( 'CdbtDB' ) ) :
/**
 * Database operation class for plugins
 *
 * @since 2.0.0
 *
 * @see CustomDataBaseTables\Lib\CdbtConfig
 */
class CdbtDB extends CdbtConfig {

  /**
   * Initialize settings of database and tables for this plugin (non-save to database)
   *
   * @since 2.0.0
   */
  protected function db_init() {
    
    // Group of tables that are reserved in wordpress
    $this->reserved_tables = array_merge($this->tables, $this->old_tables, $this->global_tables, $this->ms_global_tables);
    
    // Tables of currently database for this site
    $this->core_tables = $this->wpdb->tables('all');
    
    // Database charset
    foreach (@$this->wpdb->get_results('show character set;') as $charset) {
      $this->db_charsets[] = $charset->Charset;
      $key = 'Default collation';
      $this->db_collations[] = $charset->$key;
    }
    
    // Database currently default charset (value of `character_set_database`)
    $tmp = $this->array_flatten(@$this->wpdb->get_results("show variables like 'character_set_database';", 'ARRAY_A'));
    $this->db_default_charset = $tmp['Value'];
    
    // Currently charset of WordPress
    $this->charset = $this->wpdb->charset;
    
    // Currently default collate of WordPress
    $this->collate = $this->wpdb->collate;
    
    // Database engines
    foreach (@$this->wpdb->get_results('show engines;') as $engine) {
      if (in_array(strtolower($engine->Support), [ 'yes', 'default' ])) {
        $this->db_engines[] = $engine->Engine;
        // Database currently default engine
        if ('default' === strtolower($engine->Support)) 
          $this->db_default_engine = $engine->Engine;
      }
    }
    
    // Showing of database errors
    $this->show_errors = true;
    $this->show_errors( $this->show_errors );
    
  }
  
  /**
   * Methods operate database with wrapping the wpdb
   * -------------------------------------------------------------------------
   */
  
  /**
   * Check whether table exist.
   *
   * @since 1.0.0
   * @since 2.0.0 Have refactored logic.
   *
   * @param string $table_name [require]
   * @return boolean
   */
  public function check_table_exists( $table_name=null ) {
    if (empty($table_name)) {
      $message = sprintf( __('Table name is not specified when the method "%s" call.', CDBT), __FUNCTION__ );
      $this->logger( $message );
      return false;
    }
    
    $result = $this->wpdb->get_var($this->wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
    
    return $table_name === $result;
  }
  
  
  /**
   * Create table on the database
   *
   * @since 1.0.0
   * @since 2.0.0 Have refactored logic.
   *
   * @param mixed $table_data [require] Array's deprecated, but allows for compatible with version 1.x
   * @param string $sql Validated SQL statement for creating new table [require]
   * @return boolean
   */
  public function create_table( $table_data=null, $sql=null ) {
    static $message = '';
    
    if (is_array($table_data)) {
      $table_name = isset($table_data['table_name']) ? $table_data['table_name'] : null;
      $sql = isset($table_data['sql']) ? $table_data['sql'] : $sql;
    } else {
      $table_name = $table_data;
    }
    
    if (empty($table_name)) 
      $message = sprintf( __('Table name does not exist at the time of "%s" call.', CDBT), __FUNCTION );
    
    if (empty($sql)) 
      $message = sprintf( __('SQL to create table does not exist at the time of "%s" call.', CDBT), __FUNCTION );
    
    // Check whether a table that trying to create does not already exist
    if ($this->check_table_exists( $table_name )) 
      $message = __('Can not create a table because the table already exists.', CDBT);
    
    if (!empty($message)) {
      $this->logger( $message );
      return false;
    }
    
    // Table creation process
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
    if (!empty($this->wpdb->last_error) && !$this->check_table_exists($table_name)) {
      $message = __('Failed to create table.', CDBT);
      $this->logger( $message );
      return false;
    }
    
    $this->wpdb->flush();
    $message = sprintf( __('Created a new table "%s".', CDBT), $table_name );
    $this->logger( $message );
    return true;
    
  }
  
  
  /**
   * Get table schema from database
   *
   * @since 1.0.0
   * @since 2.0.0 Have refactored logic.
   *
   * @param string $table_name [require]
   * @param string $db_name [optional]
   * @return mixed Return the schema array on success, otherwise is `false`
   */
  public function get_table_schema( $table_name=null, $db_name=null ) {
    if (empty($table_name)) {
      $message = sprintf( __('Table name is not specified when the method "%s" call.', CDBT), __FUNCTION__ );
      $this->logger( $message );
      return false;
    }
    
    if (empty($db_name)) 
      $db_name = DB_NAME; // WordPress defined
    
    if (!$this->check_table_exists($table_name)) {
      $message = sprintf( __('Specified table "%1$s" did not exist when called method "%2$s".', CDBT), $table_name, __FUNCTION__ );
      $this->logger( $message );
      return false;
    }
    
    $sql = $this->wpdb->prepare("SELECT 
      COLUMN_NAME,COLUMN_DEFAULT,IS_NULLABLE,DATA_TYPE,
      CHARACTER_MAXIMUM_LENGTH,CHARACTER_OCTET_LENGTH,
      NUMERIC_PRECISION,NUMERIC_SCALE,
      COLUMN_TYPE,COLUMN_KEY,EXTRA,COLUMN_COMMENT 
      FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s 
      ORDER BY ORDINAL_POSITION", 
      $db_name, $table_name
    );
    
    $table_schema = [];
    foreach ( $this->wpdb->get_results($sql) as $column_schema ) {
      $is_int_column = (preg_match('/^((|tiny|small|medium|big)int|float|double(| precision)|real|dec(|imal)|numeric|fixed|bool(|ean)|bit)$/i', strtolower($column_schema->DATA_TYPE)) ? true : false);
      $is_chr_column = (preg_match('/^((|var|national |n)char(|acter)|(|tiny|medium|long)text|(|tiny|medium|long)blob|(|var)binary|enum|set)$/i', strtolower($column_schema->DATA_TYPE)) ? true : false);
      $is_date_column = (preg_match('/^(date(|time)|time(|stamp)|year)$/i', strtolower($column_schema->DATA_TYPE)) ? true : false);
      $table_schema[$column_schema->COLUMN_NAME] = [
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
      ];
      
      if ($is_int_column) {
        $total_length = intval($column_schema->NUMERIC_PRECISION) + intval($column_schema->NUMERIC_SCALE);
        $table_schema[$column_schema->COLUMN_NAME]['max_length'] = $table_schema[$column_schema->COLUMN_NAME]['octet_length'] = $total_length;
      }
      
      if ($is_chr_column) 
        $table_schema[$column_schema->COLUMN_NAME]['max_length'] = intval($column_schema->CHARACTER_MAXIMUM_LENGTH);
      
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
    
    return $table_schema;
    
  }
  
  
  /**
   * Get a SQL statement to create table
   *
   * @since 1.0.0
   * @since 2.0.0 Have refactored logic.
   *
   * @param string $table_name [require]
   * @return mixed SQL statement strings if got that, otherwise false
   */
  public function get_create_table_sql( $table_name=null ) {
    static $message = '';
    
    if (empty($table_name)) {
      $message = sprintf( __('Table name is not specified when the method "%s" call.', CDBT), __FUNCTION__ );
      $this->logger( $message );
      return false;
    }
    
    if ($this->check_table_exists($table_name)) {
      $result = $this->array_flatten($this->wpdb->get_results( sprintf( 'SHOW CREATE TABLE `%s`;', esc_sql($table_name) ), ARRAY_A));
      if (isset($result['Create Table'])) 
        return $result['Create Table'];
    }
    
    $message = __('Getting a SQL statement to create table has failed.', CDBT);
    $this->logger( $message );
    return false;
    
  }
  
  
  /**
   * Get table comment
   * This method became the wrapper of new method `get_table_status()`.
   *
   * @since 1.0.0
   * @since 2.0.0 Deprecated
   *
   * @param string $table_name [require]
   * @return mixed Table comment string if could get that, otherwise false
   */
  public function get_table_comment( $table_name=null ) {
    
    return $this->get_table_status( $table_name, 'Comment' );
    
  }
  
  
  /**
   * Get table status
   *
   * @since 2.0.0
   *
   * @param string $table_name [require]
   * @param mixed $state_name [optional] Array of some table state name, or string of single state name
   * @return mixed Array of table status if could get that, or string of single state, otherwise false
   */
  public function get_table_status( $table_name=null, $state_name=null ) {
    static $message = '';
    
    if (empty($table_name)) 
      $message = sprintf( __('Table name is not specified when the method "%s" call.', CDBT), __FUNCTION__ );
    
    if (!$this->check_table_exists($table_name)) 
      $message = __('Specified table does not exist.', CDBT);
    
    $result = $this->array_flatten($this->wpdb->get_results( $this->wpdb->prepare( 'SHOW TABLE STATUS LIKE %s;', esc_sql($table_name) ), ARRAY_A ));
    if (!is_array($result) || empty($result) || empty(array_values($result))) 
      $message = __('Table status does not exist.', CDBT);
    
    if (!empty($message)) {
      $this->logger( $message );
      return false;
    }
    
    if (empty($state_name)) 
      return $result;
    
    if (is_array($state_name)) {
      $custom_result = [];
      foreach ($state_name as $state) {
        if (array_key_exists($state, $result)) 
          $custom_result[$state] = $result[$state];
      }
      if (!empty($custom_result)) 
        return  $custom_result;
    }
    
    if (is_string($state_name)) {
      $custom_result = false;
      if (array_key_exists($state_name, $result)) 
        $custom_result = $result[$state_name];
      if (false !== $custom_result) 
        return $custom_result;
    }
    
    $message = __('Specified table state name does not exist.', CDBT);
    $this->logger( $message );
    return false;
    
  }
  
  
  /**
   * Truncate all data in table for emptying
   *
   * @since 1.0.0
   * @since 2.0.0 Have refactored logic.
   *
   * @param string $table_name [require]
   * @return boolean
   */
  public function truncate_table( $table_name=null ) {
    static $message = '';
    
    if (empty($table_name)) 
      $message = sprintf( __('Table name is not specified when the method "%s" call.', CDBT), __FUNCTION__ );
    
    if (!$this->check_table_exists($table_name)) 
      $message = __('Specified table does not exist.', CDBT);
    
    $result = $this->wpdb->query( sprintf( 'TRUNCATE TABLE `%s`;', esc_sql($table_name) ) );
    $retvar = $this->strtobool($result);
    if ($retvar) {
      $message = sprintf( __('Table of "%s" has been truncated successfully.', CDBT), $table_name );
    } else {
      $message = sprintf( __('Failed to truncate the table of "%s".', CDBT), $table_name );
    }
    
    // Fire after the truncated table
    //
    // @since 2.0.0
    do_action( 'cdbt_after_table_truncated', $retvar, $table_name );
    
    $this->logger( $message );
    return $retvar;
  }
  
  
  /**
   * Drop specific table in database (as complete remove)
   *
   * @since 1.0.0
   * @since 2.0.0 Have refactored logic.
   *
   * @param string $table_name [require]
   * @return boolean
   */
  public function drop_table( $table_name=null ) {
    static $message = '';
    
    if (empty($table_name)) 
      $message = sprintf( __('Table name is not specified when the method "%s" call.', CDBT), __FUNCTION__ );
    
    if (!$this->check_table_exists($table_name)) 
      $message = __('Specified table does not exist.', CDBT);
    
    $result = $this->wpdb->query( sprintf( 'DROP TABLE `%s`;', esc_sql($table_name) ) );
    $retvar = $this->strtobool($result);
    if ($retvar) {
      $message = sprintf( __('Table of "%s" has been removed successfully.', CDBT), $table_name );
    } else {
      $message = sprintf( __('Failed to remove the table of "%s".', CDBT), $table_name );
    }
    
    // Fire after the dropped table
    //
    // @since 2.0.0
    do_action( 'cdbt_after_table_dropped', $retvar, $table_name );
    
    $this->logger( $message );
    return $retvar;
  }
  
  
  /**
   * Duplicate the table like as replication table
   *
   * @since 2.0.0
   *
   * @param string $replicate_table [require] Replicated destination table name
   * @param boolean $duplicate_with_data [require] Whether data copy at the replication table
   * @param string $origin_table [require] Replication origin table name
   * @return boolean
   */
  public function duplicate_table( $replicate_table=null, $duplicate_with_data=true, $origin_table=null ) {
    static $message = '';
    
    if (empty($replicate_table) || empty($origin_table)) 
      $message = sprintf( __('Table name is not specified when the method "%s" call.', CDBT), __FUNCTION__ );
    
    if (!$this->check_table_exists($origin_table)) 
      $message = __('Replication origin table does not exist.', CDBT);
    
    $result = $this->wpdb->query( sprintf( 'CREATE TABLE `%s` LIKE `%s`;', esc_sql($replicate_table), esc_sql($origin_table) ) );
    $retvar = $this->strtobool($result);
    if ($retvar) {
      $message = sprintf( __('Created a table "%1$s" replicated of the table "%2$s".', CDBT), $replicate_table, $origin_table );
      $this->logger( $message );
      if ($duplicate_with_data) {
        $check_data = $this->array_flatten($this->get_data( $origin_table, 'COUNT(*)', 'ARRAY_A'));
        if (is_array($check_data) && intval(reset($check_data)) > 0) {
          if ($this->wpdb->query( sprintf( 'INSERT INTO `%s` SELECT * FROM `%s`;', esc_sql($replicate_table), esc_sql($origin_table) ) )) {
            $message = sprintf( __('Then copied the data to replication table "%s".', CDBT), $replicate_table );
          } else {
            $message = __('Table replication has been completed, but have failed to copy the data.', CDBT);
          }
        }
      }
    } else {
      $message = sprintf( __('Failed to replicated table "%s" creation.', CDBT), $replicate_table );
    }
    
    // Fire after the duplicated table
    //
    // @since 2.0.0
    do_action( 'cdbt_after_table_duplicated', $retvar, $replicate_table, $origin_table );
    
    $this->logger( $message );
    return $retvar;
  }
  
  
  /**
   * Get data from specific table in database. 
   * This method is data retrieval by the full match conditions only.
   *
   * @since 1.0.0
   * @since 2.0.0 Have refactored logic.
   *
   * @param string $table_name [require]
   * @param mixed $columns [optional] Use as select clause, for default is wildcard of '*'.
   * @param array $conditions [optional] Use as where clause.
   * @param array $order [optional] Use as orderby and order clause, for default is "order by `created` desc".
   * @param int $limit [optional] Use as limit clause.
   * @param int $offset [optional] Use as offset clause.
   * @param string $output_type [optional] Use as wrapper argument for "wpdb->get_results()". For default is 'OBJECT' (or 'OBJECT_K', 'ARRAY_A', 'ARRAY_N')
   * @return mixed 
   */
  public function get_data( $table_name, $columns='*', $conditions=null, $order=['created'=>'desc'], $limit=null, $offset=null, $output_type='OBJECT' ) {
    // Initialize by dynamically allocating an argument
    $arguments = func_get_args();
    if (in_array(end($arguments), [ 'OBJECT', 'OBJECT_K', 'ARRAY_A', 'ARRAY_N' ])) {
      $output_type = array_pop($arguments);
    } else {
      $output_type = 'OBJECT';
    }
    $arg_default_vars = [
      'table_name' => null, 
      'columns' => '*', 
      'conditions' => null, 
      'order' => ['created'=>'desc'], 
      'limit' => null, 
      'offset' => null, 
    ];
    $i = 0;
    foreach ($arg_default_vars as $variable_name => $default_value) {
      if (isset($arguments[$i])) {
        ${$variable_name} = $arguments[$i];
      } else {
        ${$variable_name} = $default_value;
      }
      $i++;
    }
    
    // Check Table
    if (false === ($table_schema = $this->get_table_schema($table_name))) {
      $message = sprintf( __('Table name is not specified when the method "%s" call.', CDBT), __FUNCTION__ );
      $this->logger( $message );
      return false;
    }
    
    // Main Process
    $select_clause = is_array($columns) ? implode(',', $columns) : $columns;
    $where_clause = $order_clause = $limit_clause = null;
    if (!empty($conditions)) {
      $i = 0;
      foreach ($conditions as $key => $val) {
        if (array_key_exists($key, $table_schema)) {
          if ($i === 0) {
            $where_clause = "WHERE `$key` = '$val' ";
          } else {
            $where_clause .= "AND `$key` = '$val' ";
          }
          $i++;
        } else {
          continue;
        }
      }
    }
    
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
    
    $sql = sprintf(
      "SELECT %s FROM `%s` %s %s %s", 
      $select_clause, 
      $table_name, 
      $where_clause, 
      $order_clause, 
      $limit_clause 
    );
    // Filter of sql statement created by method `get_data`.
    //
    // @since 2.0.0
    $sql = apply_filters( 'cdbt_crud_get_data_sql', $sql, $table_name, [ $select_clause, $where_clause, $order_clause, $limit_clause ] );
    
    return $this->wpdb->get_results($sql, $output_type);
  }
  
  
  /**
   * Find data
   *
   * @since 1.0.0
   * @since 2.0.0 Have refactored logic.
   *
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
  public function find_data( $table_name, $table_schema=null, $search_key, $columns, $order=array('created'=>'desc'), $limit=null, $offset=null ) {
/*
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
		$search_key = preg_replace('/[\s@]+/u', ' ', trim($search_key), -1);
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
*/
  }
  
  
  /**
   * Insert data to specific table in database.
   * This method is the wrapper of "wpdb::insert()".
   *
   * @since 1.0.0
   * @since 2.0.0 Have refactored logic.
   *
   * @param string $table_name [require]
   * @param array $data
   * @return mixed Integer of the primary key that was inserted when was successful against a table with a surrogate key, otherwise is boolean that representing the success or failure of processing
   */
  public function insert_data( $table_name=null, $data=[] ) {
    static $message = '';
    
    // Check Table
    if (false === ($table_schema = $this->get_table_schema($table_name))) {
      $message = sprintf( __('Table name is not specified when the method "%s" call.', CDBT), __FUNCTION__ );
      $this->logger( $message );
      return false;
    }
    
    // Check Data
    if (empty($data)) {
      $message = __('Insertion data does not exists.', CDBT);
      $this->logger( $message );
      return false;
    }
    
    // Scanning of the table structure
    $has_pk = false;
    $primary_keys = [];
    $is_auto_add_column = [];
    $surrogate_key = '';
    foreach ($table_schema as $column => $scheme) {
      if ($scheme['primary_key']) {
        $has_pk = true;
        $primary_keys[] = $column;
        if (false !== strpos( $scheme['extra'], 'auto_increment' )) {
          $surrogate_key = $column;
          if ('ID' == $column) 
            $is_auto_add_column[] = $column;
        }
      }
      if ('created' === $column && 'datetime' === $scheme['type'] && '0000-00-00 00:00:00' === $scheme['default']) 
        $is_auto_add_column[] = $column;
      
      if ('updated' === $column && 'timestamp' === $scheme['type'] && 'CURRENT_TIMESTAMP' === $scheme['default'] && 'on update CURRENT_TIMESTAMP' === $scheme['extra']) 
        $is_auto_add_column[] = $column;
      
    }
    unset($column, $scheme);
    
    // Generation of insertion candidate data
    $insert_data = [];
    $field_format = [];
    foreach ($data as $column => $value) {
      if ($surrogate_key === $column) 
        continue;
      
      if (in_array('update', $is_auto_add_column) && 'update' === $column) 
        continue;
      
      $insert_data[$column] = $value;
      if ($this->validate->check_column_type( $table_schema[$column]['type'], 'integer' )) {
        $field_format[$column] = '%d';
      } else
      if ($this->validate->check_column_type( $table_schema[$column]['type'], 'float' )) {
        $field_format[$column] = '%f';
      } else {
        $field_format[$column] = '%s';
      }
      
    }
    
    if (empty($insert_data) || empty($field_format)) {
      $message = __('Inserted data is invalid.', CDBT);
      $this->logger( $message );
      return false;
    }
    
    if (empty(array_diff_key($insert_data, $field_format))) {
      $result = $this->wpdb->insert( $table_name, $insert_data, array_values($field_format) );
    } else {
      $result = $this->wpdb->insert( $table_name, $insert_data );
    }
    $retvar = $this->strtobool($result);
    if ($retvar) {
      if (!empty($surrogate_key)) 
        $retvar = $this->wpdb->insert_id;
    } else {
      $message = __('Failed to insert data.', CDBT);
      $this->logger( $message );
    }
    
    // Fire after the inserted data
    //
    // @since 2.0.0
    do_action( 'cdbt_after_inserted_data', $retvar, $table_name, $insert_data );
    
    return $retvar;
    
  }
  
  
  /**
   * Update data to specific table in database.
   * This method is the wrapper of "wpdb::update()".
   * If it contains binary data in the update data, it is necessary to generate context by `CdbtUtility::get_binary_context()` before calling this method.
   * It is not performed update processing if the update data or where conditions is nothing.
   *
   * @since 1.0.0
//   * @since 1.1.14 Added method of `update_where()`
   * @since 2.0.0 Have refactored logic.
   *
   * @param string $table_name [require]
   * @param mixed $data [require] Assoc array as key of column name; or hash string like assoc array (cf. `col1:val1,col2:val2,..`)
   * @param mixed $where_clause [require] Assoc array as key of column name; or hash string like assoc array (`ID:1` etc.)
   * @return boolean
   */
  public function update_data( $table_name=null, $data=[], $where_clause=[] ) {
    static $message = '';
    
    // Check Table
    if (false === ($table_schema = $this->get_table_schema($table_name))) {
      $message = sprintf( __('Table name is not specified when the method "%s" call.', CDBT), __FUNCTION__ );
      $this->logger( $message );
      return false;
    }
    $primary_keys = [];
    $foreign_keys = [];
    $unique_keys = [];
    $surrogate_key = '';
    $is_exists_updated = false;
    foreach ($table_schema as $column => $scheme) {
      if ($scheme['primary_key']) 
        $primary_keys[] = $column;
      if (false !== strpos( $scheme['extra'], 'auto_increment' )) 
        $surrogate_key = $column;
      if (false !== strpos( $scheme['column_key'], 'MUL' )) 
        $foreign_keys = $column;
      if (false !== strpos( $scheme['column_key'], 'UNI' )) 
        $unique_keys = $column;
      if ('updated' === $column && 'timestamp' === $scheme['type'] && 'CURRENT_TIMESTAMP' === $scheme['default'] && 'on update CURRENT_TIMESTAMP' === $scheme['extra']) 
        $is_exists_updated = true;
    }
    
    // Check update data
    if (empty($data)) {
      $message = __('Update data does not exist.', CDBT);
    } else
    if (!is_array($data)) {
      if (false === ($_update_data = $this->strtohash($data))) {
        $message = __('Update data is invalid.', CDBT);
      }
    } else {
      $_update_data = $data;
    }
    if (empty($message)) {
      unset($data);
      if (array_key_exists($surrogate_key, $_update_data)) 
        unset($_update_data[$surrogate_key]);
      if ($is_exists_updated && array_key_exists('updated', $_update_data)) 
        unset($_update_data['updated']);
      if (empty($_update_data)) 
        $message = __('You can not update to a column that will be modified automatically.', CDBT);
    }
    
    // Check condition to specify data
    if (empty($where_clause)) {
      $message = __('Condition to find the update data is not specified.', CDBT);
    } else
    if (!is_array($where_clause)) {
      if (false === ($_update_where = $this->strtohash($where_clause))) {
        $message = __('Condition for finding the update data is invalid.', CDBT);
      }
    } else {
      $_update_where = $where_clause;
    }
    
    if (!empty($message)) {
      $this->logger( $message );
      return false;
    }
    
    // Generate field formats
    $data = [];
    $data_field_format = [];
    foreach ($_update_data as $column => $value) {
      if ($surrogate_key === $column) 
        continue;
      
      if ($is_exists_updated && 'update' === $column) 
        continue;
      
      $data[$column] = $value;
      if ($this->validate->check_column_type( $table_schema[$column]['type'], 'integer' )) {
        $data_field_format[$column] = '%d';
      } else
      if ($this->validate->check_column_type( $table_schema[$column]['type'], 'float' )) {
        $data_field_format[$column] = '%f';
      } else {
        $data_field_format[$column] = '%s';
      }
    }
    $where_data = [];
    $where_field_format = [];
    foreach ($_update_where as $column => $value) {
      if ($this->validate->check_column_type( $table_schema[$column]['type'], 'integer' )) {
        $where_field_format[$column] = '%d';
        $where_data[$column] = intval($value);
      } else
      if ($this->validate->check_column_type( $table_schema[$column]['type'], 'float' )) {
        $where_field_format[$column] = '%f';
        $where_data[$column] = floatval($value);
      } else {
        $where_field_format[$column] = '%s';
        $where_data[$column] = esc_sql(strval(rawurldecode($value)));
      }
    }
    
    // Check of any duplicate records if table has no primary key
    if (empty($primary_keys) && empty($foreign_keys) && empty($unique_keys) && empty($surrogate_key) && !$is_exists_updated) {
      $same_rows = $this->array_flatten($this->get_data( $table_name, 'COUNT(*)', $where_data, 'ARRAY_N' ));
      if (intval($same_rows[0]) > 1) {
        $message = __('The record having the same data could not be updated in order that existed in the other.', CDBT);
        $this->logger( $message );
        return false;
      }
    }
    
    // Main processing of data update
    if (empty(array_diff_key($data, $data_field_format))) {
      if (empty(array_diff_key($where_data, $where_field_format))) {
        $result = $this->wpdb->update( $table_name, $data, $where_data, array_values($data_field_format), array_values($where_field_format) );
      } else {
        $result = $this->wpdb->update( $table_name, $data, $where_data, array_values($data_field_format) );
      }
    } else {
      $result = $this->wpdb->update( $table_name, $data, $where_data );
    }
    $retvar = $this->strtobool($result);
    if (!$retvar) {
      $message = __('Failed to modify your specified data.', CDBT);
      $this->logger( $message );
    }
    
    // Fire after the updated data
    //
    // @since 2.0.0
    do_action( 'cdbt_after_updated_data', $retvar, $table_name, $data, $where_data );
    
    return $retvar;
    
  }
  
  
  /**
   * update data (for where clause based)
   *
   * @since 1.1.14
   * @since 2.0.0 Have refactored logic.
   *
   * @param string $table_name (must containing prefix of table)
   * @param string $where_conditions
   * @param array $data
   * @param array $table_schema (optional) default null
   * @return boolean
   */
  public function update_where( $table_name, $where_conditions, $data, $table_schema=null ) {
/*
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
*/
  }
  
  
  /**
   * Upsert data to table that has primary key or unique key
   * - `UPDATE` if record that matches the specific conditions is exist, is otherwise `INSERT`.
   * - Able to bulk update of multiple records.
   * - Update by taking condition for each field.
   * - Target table must have a unique key index or primary key.
   *
   * @since 2.0.0
   *
   * @param string $table_name [require]
   * @param mixed $data [require] Assoc array as key of column name; or hash string like assoc array (cf. `col1:val1,col2:val2,..`)
   * @param mixed $where_condition [require] Single assoc array or hash string as keypair (`ID:1` etc.)
   * @return boolean
   */
  public function upsert_data( $table_name=null, $data=[], $where_condition=[] ) {
    static $message = '';
    
    // query: INSERT INTO $table_name ( (array_keys($where_condition) + array_keys($data)) ) VALUES ( (array_values($where_condition) + array_values($data)) ) ON DUPLICATE KEY UPDATE {$data -> 'key=val'}
    // Cf. http://qiita.com/yuzroz/items/f0eccf847b2ea42f885f
    
  }
  
  
  /**
   * Delete data in the table
   * This method is the wrapper of "wpdb::delete()".
   *
   * @since 1.0.0
   * @since 2.0.0 Have refactored logic.
   *
   * @param string $table_name [require]
   * @param string $where_clause [require] In legacy v1.0 was the designation of the `$primary_key_value`
   * @return boolean
   */
  public function delete_data( $table_name=null, $where_clause=null ) {
    static $message = '';
    
    // Check Table
    if (false === ($table_schema = $this->get_table_schema($table_name))) {
      $message = sprintf( __('Table name is not specified when the method "%s" call.', CDBT), __FUNCTION__ );
      $this->logger( $message );
      return false;
    }
    
    // Check condition to specify data
    if (empty($where_clause)) {
      $message = __('Condition to find the deletion data is not specified.', CDBT);
    }
    if (false === ($_deletion_where = $this->strtohash($where_clause))) {
      $message = __('Condition for finding the deletion data is invalid.', CDBT);
    }
    if (!empty($message)) {
      $this->logger( $message );
      return false;
    }
    
    
    $delete_where = [];
    $field_format = [];
    foreach ($_deletion_where as $column => $value) {
      if ($this->validate->check_column_type( $table_schema[$column]['type'], 'integer' )) {
        $field_format[$column] = '%d';
        $delete_where[$column] = intval($value);
      } else
      if ($this->validate->check_column_type( $table_schema[$column]['type'], 'float' )) {
        $field_format[$column] = '%f';
        $delete_where[$column] = floatval($value);
      } else {
        $field_format[$column] = '%s';
        $delete_where[$column] = esc_sql(strval(rawurldecode($value)));
      }
    }
    
    if (empty(array_diff_key($delete_where, $field_format))) {
      $result = $this->wpdb->delete( $table_name, $delete_where, array_values($field_format) );
    } else {
      $result = $this->wpdb->delete( $table_name, $delete_where );
    }
    $retvar = $this->strtobool($result);
    if (!$retvar) {
      $message = sprintf( __('Failed to remove data of the deletion condition of "%s".', CDBT), $where_clause );
      $this->logger( $message );
    }
    
    // Fire after the data deletion
    //
    // @since 2.0.0
    do_action( 'cdbt_after_data_deletion', $retvar, $table_name, $where_clause );
    
    return $retvar;
    
  }
  
  
  /**
   * run the custom query
   *
   * @since
   * @since 2.0.0
   *
   * @param string $query
   * @return mixed
   */
  protected function run_query( $query=null ) {
    
    return $this->wpdb->query( esc_sql($query) );
    
  }
  
  
  /**
   * Retrieve specific tables list in the database.
   *
   * @since 1.1.0
   * @since 2.0.0 Have refactored logic.
   *
   * @param string $narrow_type For default `all`, `enable` can get the currently manageable tables on this plugin. As other is `unreserved` and `unmanageable`.
   * @return mixed Array is if find, or return `false`.
   */
  public function get_table_list( $narrow_type='all' ) {
    $all_tables = $this->wpdb->get_results('SHOW TABLES', 'ARRAY_N');
    $all_tables = $this->array_flatten($all_tables);
    $unreserved_tables = array_diff($all_tables, $this->core_tables);
    
    $manageable_tables = [];
    foreach ($this->options['tables'] as $table) {
      if ( !in_array($table['table_type'], [ 'template' ]) ) 
        $manageable_tables[] = $table['table_name'];
    }
    
    $unmanageable_tables = array_diff($all_tables, $manageable_tables);
    
    $return_tables = false;
    
    if ('all' === $narrow_type && !empty($all_tables)) 
      $return_tables = $all_tables;
    
    if ('enable' === $narrow_type && !empty($manageable_tables)) 
      $return_tables = $manageable_tables;
    
    if ('unreserved' === $narrow_type && !empty($unreserved_tables)) 
      $return_tables = $unreserved_tables;
    
    if ('unmanageable' === $narrow_type && !empty($unmanageable_tables)) 
      $return_tables = $unmanageable_tables;
    
    return $return_tables;
    
  }
  
  
  /**
   * Compare to table name which already exists
   *
   * @since 1.x as `compare_reservation_tables()`
   * @since 2.0.0 deprecated
   *
   * @param string $table_name [require]
   * @return boolean
   */
  protected function compare_reservation_tables( $table_name=null ) {
    /*
    $naked_table_name = preg_replace('/^'. $wpdb->prefix .'(.*)$/iU', '$1', $table_name);
    $reservation_names = array(
      'commentmeta', 'comments', 'links', 'options', 'postmeta', 'posts', 'term_relationships', 'term_taxonomy', 'terms', 'usermeta', 'users', 
      'blogs', 'blog_versions', 'registration_log', 'signups', 'site', 'sitecategories', 'sitemeta', 
    );
    return in_array($naked_table_name, $reservation_names);
    */
  }
  
  
  /**
   * Parse the element definition of the list type column as an array
   *
   * @since 2.0.0
   *
   * @param string $list_string [require] Definition string in the list type column of `enum` or `set`
   * @return array $list_array Array of list type column element
   */
  protected function parse_list_elements( $list_string=null ) {
    $list_array = [];
    
    if (!empty($list_string) && preg_match('/^(enum|set)\((.*)\)$/iU', $list_string, $matches) && is_array($matches) && array_key_exists(2, $matches)) {
      foreach (explode(',', $matches[2]) as $list_value) {
        $list_array[] = trim($list_value, "'");
      }
    }
    
    return $list_array;
  }
  
  
  /**
   * Export data from any table.
   * This method is only validation of the setting values.
   * Generate the downloading data and output that does by the `CdbtUtility::download_file()` method.
   *
   * @since 1.0.0
   * @since 2.0.0 Have refactored logic.
   *
   * @param string $table_name [require]
   * @param array $export_columns [require]
   * @param string $export_file_type [require] Allowed file types conform to `$this->allow_file_types`
   * @return string $message
   */
  public function export_table( $table_name=null, $export_columns=[], $export_file_type=null ) {
    static $message = '';
    
    if (empty($table_name)) 
      $message = sprintf( __('Table name is not specified when the method "%s" call.', CDBT), __FUNCTION__ );
    
    if (!$this->check_table_exists($table_name)) 
      $message = __('Original table to export does not exist.', CDBT);
    
    if (empty($export_file_type) || !in_array($export_file_type, $this->allow_file_types)) 
      $message = sprintf( __('Specified "%s" format of download file does not correspond.', CDBT), $export_file_type );
    
    if (empty($message)) {
      if (empty($export_columns)) {
        $message = __('Export target column is not specified.', CDBT);
      } else {
        $table_scheme = $this->get_table_schema( $table_name );
        $check_columns = true;
        foreach ($export_columns as $column) {
          if (!array_key_exists($column, $table_scheme)) {
            $check_columns = false;
            break;
          }
        }
        if (!$check_columns) {
          $message = sprintf( __('Export target column "%s" does not exist.', CDBT), $column );
        }
      }
    }
    
    if (!empty($message)) {
      $this->logger( $message );
    }
    
    return $message;
    
  }
  
  
  /**
   * Inputted data is validation and sanitization and rasterization data is returned.
   *
   * @since 2.0.0
   *
   * @param string $table_name [require]
   * @param array $post_data [require]
   * @return mixed $raster_data False is returned if invalid data
   */
  protected function cleanup_data( $table_name=null, $post_data=[] ) {
    
    if (false === ($table_schema = $this->get_table_schema($table_name))) 
      return false;
    
    $regist_data = [];
    foreach ($post_data as $post_key => $post_value) {
      if (array_key_exists($post_key, $table_schema)) {
        $detect_column_type = $this->validate->check_column_type($table_schema[$post_key]['type']);
        
        if (array_key_exists('char', $detect_column_type)) {
          if (array_key_exists('text', $detect_column_type)) {
            // Sanitization data from textarea
            $allowed_html_tags = [ 'a' => [ 'href' => [], 'title' => [] ], 'br' => [], 'em' => [], 'strong' => [] ];
            // Filter of the tag list to be allowed for data in the text area
            //
            // @since 2.0.0
            $allowed_html_tags = apply_filters( 'cdbt_sanitize_data_allow_tags', $allowed_html_tags );
            $regist_data[$post_key] = wp_kses($post_value, $allowed_html_tags);
          } else {
            // Sanitization data from text field
            if (is_email($post_value)) {
              $regist_data[$post_key] = sanitize_email($post_value);
            } else {
              $regist_data[$post_key] = sanitize_text_field($post_value);
            }
          }
        }
        
        if (array_key_exists('numeric', $detect_column_type)) {
          if (array_key_exists('integer', $detect_column_type)) {
            // Sanitization data of integer
            $regist_data[$post_key] = $table_schema[$post_key]['unsigned'] ? absint($post_value) : intval($post_value);
          } else
          if (array_key_exists('float', $detect_column_type)) {
            // Sanitization data of float
            $regist_data[$post_key] = 'decimal' === $detect_column_type['float'] ? strval(floatval($post_value)) : floatval($post_value);
          } else
          if (array_key_exists('binary', $detect_column_type)) {
            // Sanitization data of bainary bit
            $regist_data[$post_key] = sprintf("b'%s'", decbin($post_value));
          } else {
            $regist_data[$post_key] = intval($post_value);
          }
        }
        
        if (array_key_exists('list', $detect_column_type)) {
          if ('enum' === $detect_column_type['list']) {
            // Validation data of enum element
            if (in_array($post_value, $this->parse_list_elements($table_schema[$post_key]['type_format']))) {
              $regist_data[$post_key] = $post_value;
            } else {
              $regist_data[$post_key] = $table_schema[$post_key]['default'];
            }
          } else
          if ('set' === $detect_column_type['list']) {
            // Validation data of enum element
            $post_value = is_array($post_value) ? $post_value : (array)$post_value;
            $list_array = $this->parse_list_elements($table_schema[$post_key]['type_format']);
            $_save_array = [];
            foreach ($post_value as $item) {
              if (in_array($item, $list_array)) 
                $_save_array[] = $item;
            }
            $regist_data[$post_key] = implode(',', $_save_array);
            unset($list_array, $_save_array, $item);
          }
        }
        
        if (array_key_exists('datetime', $detect_column_type)) {
          if (is_array($post_value)) {
            // Validation data of date
            if (array_key_exists('date', $post_value)) {
              if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $post_value['date'], $matches) && is_array($matches) && array_key_exists(3, $matches)) {
                $_date = sprintf('%04d-%02d-%02d', $matches[3], $matches[1], $matches[2]);
              } else {
                $_date = $post_value['date'];
              }
            } else {
              $_date = '';
            }
            // Validation data of time
            $_hour = $_minute = $_second = '00';
            foreach (['hour', 'minute', 'second'] as $key) {
              if (array_key_exists($key, $post_value) && $this->validate->checkDigit($post_value[$key]) && $this->validate->checkLength($post_value[$key], 2, 2)) {
                if ('hour' === $key) {
                  $_hour = $this->validate->checkRange(intval($post_value[$key]), 0, 23) ? $post_value[$key] : '00';
                } else {
                  if ('minute' === $key) {
                    $_minute = $this->validate->checkRange(intval($post_value[$key]), 0, 59) ? $post_value[$key] : '00';
                  } else {
                    $_second = $this->validate->checkRange(intval($post_value[$key]), 0, 59) ? $post_value[$key] : '00';
                  }
                }
              }
            }
            // Rasterization data of datetime
            if (isset($_date) && isset($_hour) && isset($_minute) && isset($_second)) {
              $regist_data[$post_key] = sprintf('%s %s:%s:%s', $_date, $_hour, $_minute, $_second);
            } else {
              $regist_data[$post_key] = !empty($_date.$_hour.$_minute.$_second) ? $_date.$_hour.$_minute.$_second : $table_schema[$post_key]['default'];
            }
          } else {
            $regist_data[$post_key] = empty($post_value) ? $table_schema[$post_key]['default'] : $post_value;
          }
          // Validation data of datetime
          if (!$this->validate->checkDateTime($regist_data[$post_key], 'Y-m-d H:i:s')) {
            $regist_data[$post_key] = '0000-00-00 00:00:00';
          }
          
          $_prev_timezone = date_default_timezone_get();
          // Filter for localize the datetime at the timezone specified by options
          //
          // @since 2.0.0
          $_localize_timezone = apply_filters( 'cdbt_local_timezone_datetime', $this->options['timezone'] );
          date_default_timezone_set( $_localize_timezone );
          
          $_timestamp = '0000-00-00 00:00:00' === $regist_data[$post_key] ? date_i18n('U') : strtotime($regist_data[$post_key]);
          $regist_data[$post_key] = date_i18n( 'Y-m-d H:i:s', $_timestamp );
          
          date_default_timezone_set( $_prev_timezone );
          unset($_date, $_hour, $_minute, $_second, $_prev_timezone, $_localize_timezone, $_timestamp);
        }
        
        if (array_key_exists('file', $detect_column_type)) {
          // Check the `$_FILES`
          var_dump($detect_column_type['file']); // debug code
        }
        
      }
    }
    
    if (!empty($_FILES[$this->domain_name])) {
      $uploaded_data = [];
      foreach ($_FILES[$this->domain_name] as $file_key => $file_data) {
        foreach ($file_data as $column => $value) {
          if (array_key_exists($column, $table_schema)) 
            $uploaded_data[$column][$file_key] = $value;
        }
      }
      unset($file_key, $file_data, $column, $value);
      // Verification the uploaded file
      $mines = get_allowed_mime_types();
      foreach ($uploaded_data as $column => $file_data) {
        $is_allowed_file = true;
        
        // Unauthorized file types to exclude
        if (!in_array($file_data['type'], $mines)) 
        	$is_allowed_file = false;
        
        // Verification file size is whether within the allowable range
        if (!$this->validate->checkRange($file_data['size'], 1, $table_schema[$column]['octet_length'])) 
        	$is_allowed_file = false;
        
        // Verification whether an error has occurred in the upload
        if ($file_data['error'] !== 0) 
          $is_allowed_file = false;
        
        // Verification whether the temporary file exists
        if (empty($file_data['tmp_name'])) 
          $is_allowed_file = false;
        
        if (!$is_allowed_file) 
          unset($uploaded_data[$column]);
        
      }
      unset($colmun, $file_data);
      if (!empty($uploaded_data)) {
        // Rasterization data of file
        foreach ($uploaded_data as $column => $file_data) {
          $regist_data[$column] = $this->get_binary_context( $file_data['tmp_name'], $file_data['name'], $file_data['type'], $file_data['size'], true );
        }
      }
    }
    
    return !empty($regist_data) ? $regist_data : false;
    
  }
  
  
  /**
   * data verification by column schema
   *
   * @since 
   * @since 2.0.0
   *
   * @param array $column_schema
   * @param string $data
   * @return array
   */
  protected function validate_data( $column_schema, $data ) {
/*
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
*/
  }
  
  
  
}

endif; // end of class_exists()