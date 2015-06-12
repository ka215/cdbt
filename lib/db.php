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
  function check_table_exists( $table_name=null ) {
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
  function create_table( $table_data=null, $sql=null ) {
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
  function get_table_schema( $table_name=null, $db_name=null ) {
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
  function get_create_table_sql( $table_name=null ) {
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
  function get_table_comment( $table_name=null ) {
    
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
  function get_table_status( $table_name=null, $state_name=null ) {
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
  function truncate_table( $table_name=null ) {
    static $message = '';
    
    if (empty($table_name)) 
      $message = sprintf( __('Table name is not specified when the method "%s" call.', CDBT), __FUNCTION__ );
    
    if (!$this->check_table_exists($table_name)) 
      $message = __('Specified table does not exist.', CDBT);
    
    if ($this->wpdb->query( sprintf( 'TRUNCATE TABLE `%s`;', esc_sql($table_name) ) )) {
      $message = sprintf( __('Table of "%s" has been truncated successfully.', CDBT), $table_name );
      $this->logger( $message );
      return true;
    } else {
      $message = sprintf( __('Failed to truncate the table of "%s".', CDBT), $table_name );
    }
    
    $this->logger( $message );
    return false;
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
  function drop_table( $table_name=null ) {
    static $message = '';
    
    if (empty($table_name)) 
      $message = sprintf( __('Table name is not specified when the method "%s" call.', CDBT), __FUNCTION__ );
    
    if (!$this->check_table_exists($table_name)) 
      $message = __('Specified table does not exist.', CDBT);
    
    if ($this->wpdb->query( sprintf( 'DROP TABLE `%s`;', esc_sql($table_name) ) )) {
      $message = sprintf( __('Table of "%s" has been removed successfully.', CDBT), $table_name );
      $this->logger( $message );
      return true;
    } else {
      $message = sprintf( __('Failed to remove the table of "%s".', CDBT), $table_name );
    }
    
    $this->logger( $message );
    return false;
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
  function duplicate_table( $replicate_table=null, $duplicate_with_data=true, $origin_table=null ) {
    static $message = '';
    
    if (empty($replicate_table) || empty($origin_table)) 
      $message = sprintf( __('Table name is not specified when the method "%s" call.', CDBT), __FUNCTION__ );
    
    if (!$this->check_table_exists($origin_table)) 
      $message = __('Replication origin table does not exist.', CDBT);
    
    if ($this->wpdb->query( sprintf( 'CREATE TABLE `%s` LIKE `%s`;', esc_sql($replicate_table), esc_sql($origin_table) ) )) {
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
      $this->logger( $message );
      return true;
    } else {
      $message = sprintf( __('Failed to replicated table "%s" creation.', CDBT), $replicate_table );
    }
    
    $this->logger( $message );
    return false;
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
  function get_data( $table_name, $columns='*', $conditions=null, $order=['created'=>'desc'], $limit=null, $offset=null, $output_type='OBJECT' ) {
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
//var_dump([ $table_name, $columns, $conditions, $order, $limit, $offset, $output_type ]);
    
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
   * Insert data to specific table in database.
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
    
    return $retvar;
    
  }
  
  
  /**
   * Delete data in the table
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
    
    return $retvar;
    
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
   * Parse the element definition of the list type column as an array
   *
   * @since 2.0.0
   *
   * @param string $list_string [require] Definition string in the list type column of `enum` or `set`
   * @return array $list_array Array of list type column element
   */
  public function parse_list_elements( $list_string=null ) {
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
  
  



}

endif; // end of class_exists()