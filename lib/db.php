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
  
  var $show_errors = false;
  
  var $suppress_errors = false;
  
  var $db_errors = [];
  
  var $common_error_messages = [];
  
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
    $tmp = $this->array_flatten( @$this->wpdb->get_results( "SHOW VARIABLES LIKE 'character_set_database';", 'ARRAY_A' ) );
    $this->db_default_charset = $tmp['Value'];
    
    // Database currently lower case (value of `lower_case_table_names`) @since 2.0.10
    $tmp = $this->array_flatten( @$this->wpdb->get_results( "SHOW VARIABLES WHERE variable_name = 'lower_case_table_names';", 'ARRAY_A' ) );
    $this->db_lower_case = intval( $tmp['Value'] );
    
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
    if ($this->show_errors) {
      $this->wpdb->show_errors();
    } else {
      $this->wpdb->hide_errors();
    }
    $this->wpdb->suppress_errors = $this->suppress_errors;
    
    $this->common_error_messages = [
      __( 'Table name is not specified when calling the "%s" method.', CDBT ), 
      __( 'The "%s" does not supported on your environment.', CDBT ), 
    ];
    
    // Added Filter
    add_filter( 'cdbt_lower_case_table_name', array( $this, 'lowercase_table_name' ) );
    add_filter( 'cdbt_select_clause_optimaize', array( $this, 'select_clause_optimaize' ), 10, 3 );
    
  }
  
  
  /**
   * Retrieve the error information from the global DB error object.
   *
   * @since 2.0.0
   *
   * @return boolean
   */
  protected function check_db_error() {
    global $EZSQL_ERROR;
    if (!empty($EZSQL_ERROR)) {
      foreach ($EZSQL_ERROR as $_i => $_ary) {
        if (preg_match('/^(describe).*$/iU', $_ary['query'])) 
          continue;
        
        $this->db_errors[] = $EZSQL_ERROR[$_i];
        $this->logger($_ary['error_str']);
      }
      
      return true;
    }
    
    return false;
  }
  
  
  /**
   * Retrieve an error message or a query of occured error after the database error checking.
   * Also, an error message with query string will write to the log at the same time.
   *
   * @since 2.0.0
   *
   * @param string $get_type [require] For default `error_str`; or `query`
   * @return string
   */
  protected function retrieve_db_error( $get_type='error_str' ) {
    if (empty($get_type) || !in_array($get_type, [ 'error_str', 'query' ])) 
      $get_type = 'error_str';
    
    $_return = '';
    if ($this->debug && $this->check_db_error()) {
      $_err = end($this->db_errors);
      $_return = "<br>\n" . $_err[$get_type];
      $this->logger( sprintf( '%s "%s"', $_err['error_str'], $_err['query'] ) );
    }
    
    return $_return;
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
    $table_name = $this->lowercase_table_name( $table_name );
    if ( empty( $table_name ) ) {
      $message = sprintf( $this->common_error_messages[0], __FUNCTION__ );
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
    $message = '';
    
    if (is_array($table_data)) {
      $table_name = isset($table_data['table_name']) ? $table_data['table_name'] : null;
      $sql = isset($table_data['sql']) ? $table_data['sql'] : $sql;
    } else {
      $table_name = $table_data;
    }
    
    if (empty($table_name)) 
      $message = sprintf( $this->common_error_messages[0], __FUNCTION );
    
    if ( empty( $sql ) ) 
      $message = sprintf( __('SQL for table creation is not specified when calling the "%s" method.', CDBT), __FUNCTION );
    
    // Check whether a table that trying to create does not already exist
    if ( $this->check_table_exists( $table_name ) ) 
      $message = __('Can not create a table because the table already exists.', CDBT);
    
    if ( ! empty( $message ) ) {
      $this->logger( $message );
      return false;
    }
    
    // Table creation process
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
    $table_name = $this->lowercase_table_name( $table_name );
    if ( ! empty( $this->wpdb->last_error ) && ! $this->check_table_exists( $table_name ) ) {
      $message = __('Failed to create table.', CDBT);
      $message .= $this->retrieve_db_error();
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
      $message = sprintf( $this->common_error_messages[0], __FUNCTION__ );
      $this->logger( $message );
      return false;
    }
    
    if (empty($db_name)) 
      $db_name = DB_NAME; // WordPress defined
    
    $table_name = $this->lowercase_table_name( $table_name );
    if ( ! $this->check_table_exists( $table_name ) ) {
      $message = sprintf( __('Specified table "%1$s" did not exist when called the "%2$s" method.', CDBT), $table_name, __FUNCTION__ );
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
    
    $table_name = $this->lowercase_table_name( $table_name );
    if ( empty( $table_name ) ) {
      $message = sprintf( $this->common_error_messages[0], __FUNCTION__ );
      $this->logger( $message );
      return false;
    }
    
    if ( $this->check_table_exists( $table_name ) ) {
      $result = $this->array_flatten( $this->wpdb->get_results( sprintf( 'SHOW CREATE TABLE `%s`;', $table_name ), ARRAY_A ) );
      if ( isset( $result['Create Table'] ) ) 
        return $result['Create Table'];
    }
    
    $message = __('Failed to get a SQL statement to create table.', CDBT);
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
   * Get table charset
   *
   * @since 2.0.7
   *
   * @param string $table_name [require]
   * @return mixed String of table default charset if could get that, otherwise false
   */
  public function get_table_charset( $table_name=null ) {
    static $message = '';
    
    if ( empty( $table_name ) ) {
      $message = sprintf( $this->common_error_messages[0], __FUNCTION__ );
      return false;
    }
    
    if ( $_create_sql = $this->get_create_table_sql( $table_name ) ) {
      if ( strpos( $_create_sql, 'DEFAULT CHARSET=' ) !== false ) {
        list( , $_charset_str ) = explode( ' ', substr( $_create_sql, strpos( $_create_sql, 'DEFAULT CHARSET=' ) ) );
        $charset = str_replace( '=', '', strstr( $_charset_str, '=' ) );
      }
    } else {
      $charset = false;
    }
    
    if ( ! empty( $message ) ) 
      $this->logger( $message );
    
    return $charset;
  }
  
  
  /**
   * Get table status
   *
   * @since 2.0.0
   * @since 2.1.34 Updated
   *
   * @param string $table_name [require]
   * @param mixed $state_name [optional] Array of some table state name, or string of single state name
   * @return mixed Array of table status if could get that, or string of single state, otherwise false
   */
  public function get_table_status( $table_name=null, $state_name=null ) {
    static $message = '';
    
    if ( empty( $table_name ) ) 
      $message = sprintf( $this->common_error_messages[0], __FUNCTION__ );
    
    if ( ! $this->check_table_exists( $table_name ) ) 
      $message = __( 'No specified table.', CDBT );
    
    $result = $this->array_flatten( $this->wpdb->get_results( $this->wpdb->prepare( 'SHOW TABLE STATUS LIKE %s;', $table_name ), ARRAY_A ) );
    if ( ! is_array( $result ) || empty( $result ) ) 
      $message = __('No table status.', CDBT);
    
    if ( ! empty( $message ) ) {
      $this->logger( $message );
      return false;
    }
    
    if ( empty( $state_name ) ) 
      return $result;
    
    if ( is_array( $state_name ) ) {
      $custom_result = [];
      foreach ( $state_name as $state ) {
        if ( array_key_exists( $state, $result ) ) 
          $custom_result[$state] = $result[$state];
      }
      if ( ! empty( $custom_result ) ) 
        return  $custom_result;
    }
    
    if ( is_string( $state_name ) ) {
      $custom_result = false;
      if ( array_key_exists( $state_name, $result ) ) 
        $custom_result = $result[$state_name];
      if ( false !== $custom_result ) 
        return $custom_result;
    }
    
    $message = __('No specified table state name.', CDBT);
    $this->logger( $message );
    return false;
    
  }
  
  
  /**
   * Get table size
   *
   * @since 2.1.33
   *
   * @param string $table_name [require]
   * @param unit $unit [optional] Default is "KB"; or "MB" or "GB"
   * @return int $size
   */
  public function get_table_size( $table_name=null, $unit='KB' ) {
    static $message = '';
    
    if ( empty( $table_name ) ) 
      $message = sprintf( $this->common_error_messages[0], __FUNCTION__ );
    
    if ( ! $this->check_table_exists( $table_name ) ) 
      $message = __('No specified table.', CDBT);
    
    $unit = in_array( strtoupper( $unit ), [ 'KB', 'MB', 'GB' ] ) ? strtoupper( $unit ) : 'KB';
    $_per_unit = 'KB' === $unit ? 1024 : ('MB' === $unit ? 1024 * 1024 : 1024 * 1024 * 1024);
    $result = $this->array_flatten( $this->wpdb->get_results( $this->wpdb->prepare( "SELECT floor( ( data_length + index_length ) / %d ) AS full_size FROM information_schema.TABLES WHERE `table_name` = '%s'", $_per_unit, esc_sql( $table_name ) ), ARRAY_A ) );
    if ( ! is_array($result) || empty( $result ) || ! $result ) 
      $message = sprintf( __('Could not get the "%s" table size', CDBT), $table_name );
    
    if ( ! empty( $message ) ) {
      $this->logger( $message );
      return false;
    }
    
    return intval( $result['full_size'] );
  }
  
  
  /**
   * Get table rows
   *
   * @since 2.1.33
   *
   * @param string $table_name [require]
   * @return int $rows
   */
  public function get_table_rows( $table_name=null ) {
    static $message = '';
    
    if ( empty( $table_name ) ) 
      $message = sprintf( $this->common_error_messages[0], __FUNCTION__ );
    
    if ( ! $this->check_table_exists( $table_name ) ) 
      $message = __('No specified table.', CDBT);
    
    if ( 'myisam' === strtolower( $this->get_table_status( $table_name, 'Engine' ) ) ) {
      return intval( $this->get_table_status( $table_name, 'Rows' ) );
    } else {
      $_schema = $this->get_table_schema( $table_name );
      $_count_col = $_first_col = null;
      $_i = 0;
      foreach ( $_schema as $_col => $_val ) {
      	if ( $_i == 0 ) 
      	  $_first_col = $_col;
        if ( $_val['primary_key'] ) {
          $_count_col = $_col;
          break;
        }
        $_i++;
      }
      if ( empty( $_count_col ) ) {
        $_count_col = $_first_col;
      }
      $sql = sprintf( 'SELECT COUNT(`%s`) AS rows FROM `%s`', $_count_col, $table_name );
      $result = $this->run_query( $sql, 'PDO' );
      return intval( $result['rows'] );
    }
    
  }
  
  
  /**
   * Truncate all data in table for emptying
   *
   * @since 1.0.0
   * @since 2.0.0 Have refactored logic
   * @since 2.1.34 Supported foreign key
   *
   * @param string $table_name [require]
   * @return boolean
   */
  public function truncate_table( $table_name=null ) {
    static $message = '';
    
    if (empty($table_name)) 
      $message = sprintf( $this->common_error_messages[0], __FUNCTION__ );
    
    if (!$this->check_table_exists($table_name)) 
      $message = __('No specified table.', CDBT);
    
    // Fire before truncating table
    // 
    // @since 2.1.34
    do_action( 'cdbt_before_truncate_table', $table_name );
    
    $result = $this->wpdb->query( sprintf( 'TRUNCATE TABLE `%s`;', esc_sql($table_name) ) );
    $retvar = $this->strtobool($result);
    if ($retvar) {
      $message = sprintf( __('Truncated successfully the table of "%s".', CDBT), $table_name );
    } else {
      $message = sprintf( __('Failed to truncate the table of "%s".', CDBT), $table_name );
      $message .= $this->retrieve_db_error();
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
      $message = sprintf( $this->common_error_messages[0], __FUNCTION__ );
    
    if (!$this->check_table_exists($table_name)) 
      $message = __('No specified table.', CDBT);
    
    $result = $this->wpdb->query( sprintf( 'DROP TABLE `%s`;', esc_sql($table_name) ) );
    $retvar = $this->strtobool($result);
    if ($retvar) {
      $message = sprintf( __('Removed successfully the table of "%s".', CDBT), $table_name );
    } else {
      $message = sprintf( __('Failed to remove the table of "%s".', CDBT), $table_name );
      $message .= $this->retrieve_db_error();
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
      $message = sprintf( $this->common_error_messages[0], __FUNCTION__ );
    
    if (!$this->check_table_exists($origin_table)) 
      $message = __('No origin table for duplication.', CDBT);
    
    $result = $this->wpdb->query( sprintf( 'CREATE TABLE `%s` LIKE `%s`;', esc_sql($replicate_table), esc_sql($origin_table) ) );
    $retvar = $this->strtobool($result);
    if ($retvar) {
      $message = sprintf( __('Created the table of "%1$s" as duplicate of the "%2$s".', CDBT), $replicate_table, $origin_table );
      $this->logger( $message );
      if ($duplicate_with_data) {
        $check_data = $this->array_flatten($this->get_data( $origin_table, 'COUNT(*)', 'ARRAY_A'));
        if (is_array($check_data) && intval(reset($check_data)) > 0) {
          if ($this->wpdb->query( sprintf( 'INSERT INTO `%s` SELECT * FROM `%s`;', esc_sql($replicate_table), esc_sql($origin_table) ) )) {
            $message = sprintf( __('Then copied the data into the duplicated table "%s".', CDBT), $replicate_table );
          } else {
            $message = __('The table duplication completed, but failed to copy the data.', CDBT);
            $message .= $this->retrieve_db_error();
          }
        }
      }
    } else {
      $message = sprintf( __('Failed to duplicate of the "%s" table.', CDBT), $replicate_table );
      $message .= $this->retrieve_db_error();
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
   * @since 2.0.7 Fixed a bug of $columns argument
   * @since 2.1.31 Added $operator
   * @since 2.1.33 Supported IN and BETWEEN
   *
   * @param string $table_name [require]
   * @param mixed $columns [optional] Use as select clause, for default is wildcard of '*'.
   * @param array $conditions [optional] Use as where clause.
   * @param string $operator [optional] Operators of multiple keywords; default is 'and', or 'or'. Note: Added version 2.1.31
   * @param array $order [optional] Use as orderby and order clause, for default is "order by `created` desc".
   * @param int $limit [optional] Use as limit clause.
   * @param int $offset [optional] Use as offset clause.
   * @param string $output_type [optional] Use as wrapper argument for "wpdb->get_results()". For default is 'OBJECT' (or 'OBJECT_K', 'ARRAY_A', 'ARRAY_N'), add 'SQL' since 2.1.33
   * @return mixed 
   */
  public function get_data( $table_name, $columns='*', $conditions=null, $operator='and', $order=['created'=>'desc'], $limit=null, $offset=null, $output_type='OBJECT' ) {
    // Initialize by dynamically allocating an argument
    $arguments = func_get_args();
    if ( in_array( end( $arguments ), [ 'OBJECT', 'OBJECT_K', 'ARRAY_A', 'ARRAY_N', 'SQL' ] ) ) {
      $output_type = array_pop( $arguments );
    } else {
      $output_type = 'OBJECT';
    }
    $arg_default_vars = [
      'table_name' => null, 
      'columns' => '*', 
      'conditions' => null, 
      'operator' => 'and', 
      'order' => ['created'=>'desc'], 
      'limit' => null, 
      'offset' => null, 
    ];
    $i = 0;
    foreach ( $arg_default_vars as $variable_name => $default_value ) {
      if ( isset( $arguments[$i] ) ) {
        ${$variable_name} = $arguments[$i];
      } else {
        ${$variable_name} = $default_value;
      }
      $i++;
    }
    
    // Check Table
    if ( false === ( $table_schema = $this->get_table_schema( $table_name ) ) ) {
      $message = sprintf( $this->common_error_messages[0], __FUNCTION__ );
      $this->logger( $message );
      return false;
    }
    
    // Main Process
    // Filter select cluase for value type optimaization
    //
    // @since 2.0.7
    $select_clause = apply_filters( 'cdbt_select_clause_optimaize', $columns, $table_name, 'get_data' );
    $where_clause = $order_clause = $limit_clause = null;
    if ( ! empty( $conditions ) ) {
      $i = 0;
      foreach ( $conditions as $key => $val ) {
        if ( array_key_exists( $key, $table_schema ) ) {
          $where_clause .= $i == 0 ? 'WHERE ' : strtoupper( $operator ) . ' ';
          $_col_type = $this->validate->check_column_type( $table_schema[$key]['type_format'] );
          if ( array_key_exists( 'datetime', $_col_type ) ) {
            if ( ! is_array( $val ) ) {
              $_target_date = $this->validate->checkDate( $val, 'Y-m-d' ) ? $val : date( 'Y-m-d', strtotime( $val ) );
              $where_clause .= sprintf( "`%s` BETWEEN '%s 00:00:00' AND '%s 23:59:59' ", $key, $_target_date, $_target_date );
            } else {
              $_start_date = $this->validate->checkDate( $val[0], 'Y-m-d' ) ? $val[0] : date( 'Y-m-d', strtotime( $val[0] ) );
              $_end_date = $this->validate->checkDate( $val[1], 'Y-m-d' ) ? $val[1] : date( 'Y-m-d', strtotime( $val[1] ) );
              $where_clause .= sprintf( "`%s` BETWEEN '%s 00:00:00' AND '%s 23:59:59' ", $key, $_start_date, $_end_date );
          	}
          } else {
            $where_clause .= ! is_array( $val ) ? "`$key` = '$val' " : "`$key` IN ('". implode( "','", $val ) ."') ";
          }
          $i++;
        } else {
          continue;
        }
      }
    }
    
    if ( ! empty( $order ) ) {
      $i = 0;
      foreach ($order as $key => $val ) {
        if ( array_key_exists( $key, $table_schema ) ) {
          $val = strtoupper( $val ) === 'DESC' ? 'DESC' : 'ASC';
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
    
    if ( ! empty( $limit ) ) {
      $limit_clause = "LIMIT ";
      $limit_clause .= ( ! empty( $offset ) ) ? intval( $offset ) .', '. intval( $limit ) : intval( $limit );
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
    
    if ( 'SQL' === $output_type ) {
      return $sql;
    } else {
      return $this->wpdb->get_results( $sql, $output_type );
    }
  }
  
  
  /**
   * Find data
   *
   * @since 1.0.0
   * @since 2.0.0 Refactored logic.
   * @since 2.1.33 Deprecated UNION query
   * @since 2.1.34 Enhanced concat filter
   *
   * Locate the appropriate data by extracting the best column from the schema information of the table for the search keyword. 
   * Same behavior as get_data() If there is no schema of the table argument is.
   *
   * @param string $table_name [require]
   * @param mixed $search_key [require] String or Array
   * @param string $operator [optional] Operators of multiple keywords; default is 'and', or 'or'. Note: Added version 2.0.7
   * @param mixed $columns [optional] Use as select clause, for default is wildcard of '*'. String or Array
   * @param array $order [optional] Use as orderby and order clause, for default is "order by `created` desc".
   * @param int $limit [optional] Use as limit clause.
   * @param int $offset [optional] Use as offset clause.
   * @param string $output_type [optional] Use as wrapper argument for "wpdb->get_results()". For default is 'OBJECT' (or 'OBJECT_K', 'ARRAY_A', 'ARRAY_N'), add 'SQL' since 2.1.33
   * @return array
   */
  public function find_data( $table_name, $search_key, $operator='and', $columns='*', $order=['created'=>'desc'], $limit=null, $offset=null, $output_type='OBJECT' ) {
    // Initialize by dynamically allocating an argument
    $arguments = func_get_args();
    if (in_array(end($arguments), [ 'OBJECT', 'OBJECT_K', 'ARRAY_A', 'ARRAY_N', 'SQL' ])) {
      $output_type = array_pop($arguments);
    } else {
      $output_type = 'OBJECT';
    }
    $arg_default_vars = [
      'table_name' => null, 
      'search_key' => null, 
      'operator' => 'and', 
      'columns' => '*', 
      'order' => ['created'=>'desc'], 
      'limit' => null, 
      'offset' => null, 
    ];
    $i = 0;
    foreach ($arg_default_vars as $variable_name => $default_value) {
      if ( isset( $arguments[$i] ) && !empty( $arguments[$i] ) ) {
        ${$variable_name} = $arguments[$i];
      } else {
        ${$variable_name} = $default_value;
      }
      $i++;
    }
    
    // Check Table
    if (false === ($table_schema = $this->get_table_schema($table_name))) {
      $message = sprintf( $this->common_error_messages[0], __FUNCTION__ );
      $this->logger( $message );
      return false;
    }
    
    // Main Process
    // Filter select cluase for value type optimaization
    //
    // @since 2.0.7
    $select_clause = apply_filters( 'cdbt_select_clause_optimaize', $columns, $table_name, 'find_data' );
    $where_clause = $order_clause = $limit_clause = null;
    if ( ! empty( $order ) ) {
      $i = 0;
      foreach ( $order as $key => $val ) {
        if ( array_key_exists( $key, $table_schema ) ) {
          $val = strtoupper( $val ) == 'DESC' ? 'DESC' : 'ASC';
          if ( $i == 0 ) {
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
    
    if ( ! empty( $limit ) ) {
      $limit_clause = "LIMIT ";
      $limit_clause .= ! empty( $offset ) ? intval( $offset ) .', '. intval( $limit ) : intval( $limit );
    }
    
    // Filter whether to concat columns or not
    // 
    // @since 2.1.33
    $is_concat = apply_filters( 'cdbt_find_concat_columns', false, $table_name );
    
    // Filter the separator string for concat query
    // 
    // @since 2.1.34
    $concat_separator = apply_filters( 'cdbt_find_concat_separator', 'CHAR(0)', $table_name );
    
    if ( is_array( $search_key ) ) {
      $keywords = $search_key;
    } else {
      $search_key = preg_replace( '/[\s　]+/u', ' ', trim( $search_key ), -1 );
//      if ( ! $is_concat ) {
        $keywords = preg_split( '/[\s]/', $search_key, 0, PREG_SPLIT_NO_EMPTY );
//      } else {
//        $keywords = [ $search_key ];
//      }
    }
    if ( ! empty( $keywords ) ) {
      $primary_key_name = null;
      $_col_index = [];
      $_i = 0;
      foreach ( $table_schema as $col_name => $col_scm ) {
        if ( empty( $primary_key_name ) && $col_scm['primary_key'] ) {
          $primary_key_name = $col_name;
        }
        $_col_index[$col_name] = $_i;
        $_i++;
      }
      foreach ( $keywords as $value ) {
        if ( ! empty( $table_schema ) ) {
          unset( $table_schema[$primary_key_name], $table_schema['created'], $table_schema['updated'] );
          $target_columns = [];
          foreach ( $table_schema as $column_name => $column_info ) {
            if ( is_float( $value ) ) {
              if ( preg_match( '/^(float|double(| precision)|real|dec(|imal)|numeric|fixed)$/', $column_info['type'] ) ) 
                $target_columns[] = $column_name;
            } else 
            if ( is_int( $value ) ) {
              if ( preg_match( '/^((|tiny|small|medium|big)int|bool(|ean)|bit)$/', $column_info['type'] ) ) 
                $target_columns[] = $column_name;
            }
            if ( preg_match( '/^((|var|national |n)char(|acter)|(|tiny|medium|long)text|(|tiny|medium|long)blob|(|var)binary|enum|set)$/', $column_info['type'] ) ) 
              $target_columns[] = $column_name;
          }
        }
      }
      if ( ! empty( $target_columns ) ) {
        $_conditions = [];
        foreach ( $keywords as $value ) {
          if ( $is_concat ) {
            // Filter the search value for concat
            // 
            // @since 2.1.34
            $search_value = apply_filters( 'cdbt_find_concat_value', "'%%". $value ."%%'", $value, $table_name );
            $_conditions[] = 'CONCAT_WS('. $concat_separator .',`'. implode( '`,`', $target_columns ) .'`) LIKE '. $search_value;
          } else {
            $_child_cond = [];
            foreach ( $target_columns as $target_column_name ) {
              $_child_cond[] = '`'. $target_column_name ."` LIKE '%%". $value ."%%'";
            }
            $_conditions[] = '( '. implode( ' OR ', $_child_cond ) .' )';
          }
        }
        $operator = in_array( strtolower( $operator ), [ 'and', 'or' ] ) ? strtoupper( $operator ) : 'AND';
        $operator = $is_concat ? 'AND' : $operator;
        $where_clause = 'WHERE '. implode( " $operator ", $_conditions ) .' ';
        $_select_statements = sprintf( 'SELECT %s FROM `%s` %s ', $select_clause, $table_name, $where_clause );
      } else {
        $_select_statements = sprintf( 'SELECT %s FROM `%s` ', $select_clause, $table_name );
      }
      $sql = $_select_statements . $order_clause .' '. $limit_clause;
    }
    
    if ( ! isset( $sql ) || empty( $sql ) ) {
      $sql = sprintf(
        "SELECT %s FROM `%s` %s %s %s", 
        $select_clause, 
        $table_name, 
        $where_clause, 
        $order_clause, 
        $limit_clause 
      );
    }
    // Filter of sql statement created by method `find_data`.
    //
    // @since 2.0.0
    $sql = apply_filters( 'cdbt_crud_find_data_sql', $sql, $table_name, [ $select_clause, $where_clause, $order_clause, $limit_clause ] );
    
    if ( 'SQL' === $output_type ) {
      return $sql;
    }
    
    $result = $this->wpdb->get_results($sql, $output_type);
    
    if ( ! empty( $result ) && '*' !== $select_clause && is_array( $where_clause ) ) {
      // Narrowing of result of union selection
      $_retval = [];
      $select_clause = explode( ',', str_replace( '`', '', $select_clause ) );
      switch ( $output_type ) {
        case 'OBJECT': 
        case 'OBJECT_K': 
          foreach ( $result as $_row_key => $_row_data ) {
            $_retval[$_row_key] = new \stdClass;
            foreach ( $select_clause as $_col ) {
              $_retval[$_row_key]->$_col = isset( $_row_data->$_col ) ? $_row_data->$_col : '';
            }
          }
          break;
        case 'ARRAY_A': 
          foreach ( $result as $_row_index => $_row_data ) {
            foreach ( $select_clause as $_col ) {
              $_retval[$_row_index][$_col] = isset( $_row_data[$_col] ) ? $_row_data[$_col] : '';
            }
          }
          break;
        case 'ARRAY_N': 
          foreach ( $result as $_row_index => $_row_data ) {
          	foreach ( $select_clause as $_col ) {
              $_retval[$_row_index][] = isset( $_row_data[$_col_index[$_col]] ) ? $_row_data[$_col_index[$_col]] : '';
            }
          }
          break;
      }
      $result = $_retval;
    }
    
    return $result;
  }
  
  
  /**
   * Insert data to specific table in database.
   * This method is the wrapper of "wpdb::insert()".
   *
   * @since 1.0.0
   * @since 2.0.0 Have refactored logic.
   *
   * @param string $table_name [require]
   * @param array $data [require]
   * @return mixed Integer of the primary key that was inserted when was successful against a table with a surrogate key, otherwise is boolean that representing the success or failure of processing
   */
  public function insert_data( $table_name=null, $data=[] ) {
    static $message = '';
    
    // Check Table
    if (false === ($table_schema = $this->get_table_schema($table_name))) {
      $message = sprintf( $this->common_error_messages[0], __FUNCTION__ );
      $this->logger( $message );
      return false;
    }
    
    // Check Data
    if (empty($data)) {
      $message = __('No insert data.', CDBT);
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
      
      if ('' === $value || is_null($value)) {
        if ($table_schema[$column]['not_null']) {
          if ( is_null( $table_schema[$column]['default'] ) ) {
            $insert_data = [];
            break;
          } else {
            $insert_data[$column] = $table_schema[$column]['default'];
          }
        } else {
          continue;
        }
      } else {
        if ( in_array( 'created', $is_auto_add_column ) && 'created' === $column ) {
          $value = $this->validate->checkDate( $value, 'Y-m-d H:i:s' ) && $value !== '0000-00-00 00:00:00' ? $value : date_i18n( 'Y-m-d H:i:s' );
        }
        $insert_data[$column] = $value;
      }
      
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
    
    // Filter the data before any data insertion to a table
    // 
    // @since 2.0.5
    $insert_data = apply_filters( 'cdbt_before_insert_data', $insert_data, $table_name, $field_format );
    
    $_diff_result = array_diff_key($insert_data, $field_format);
    if (empty($_diff_result)) {
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
      $message .= $this->retrieve_db_error();
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
      $message = sprintf( $this->common_error_messages[0], __FUNCTION__ );
      $this->logger( $message );
      return false;
    }
    $primary_keys = [];
    $foreign_keys = [];
    $unique_keys = [];
    $surrogate_key = '';
    $is_update_to_null = false;
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
      $message = __('No update data.', CDBT);
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
      $message = __('Condition to find the updating data is not specified.', CDBT);
    } else
    if (!is_array($where_clause)) {
      if (false === ($_update_where = $this->strtohash($where_clause))) {
        $message = __('Condition to find the updating data is invalid.', CDBT);
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
      
      if ('' === $value || 'null' === $value || is_null($value)) {
        if (is_null($table_schema[$column]['default']) || 'NULL' === strtoupper($table_schema[$column]['default'])) {
          $data[$column] = 'NULL';
          $is_update_to_null = true;
        } else {
          $data[$column] = $table_schema[$column]['default'];
        }
      } else {
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
        $message = __('Failed to update data because the record having the same data exist in the other.', CDBT);
        $this->logger( $message );
        return false;
      }
    }
    
    // Main processing of data update
    
    // Filter the data before any data modification to the table
    // 
    // @since 2.0.5
    $data = apply_filters( 'cdbt_before_update_data', $data, $table_name, $data_field_format );
    
    // Filter the where condition before any data modification to the table
    // 
    // @since 2.0.5
    $where_data = apply_filters( 'cdbt_before_update_where', $where_data, $table_name, $where_field_format );
    
    $_diff_result = array_diff_key($data, $data_field_format);
    if ($is_update_to_null) {
      add_filter('query', array($this, 'update_at_null_data'));
    }
    if (empty($_diff_result)) {
      $_diff_result = array_diff_key($where_data, $where_field_format);
      if (empty($_diff_result)) {
        $result = $this->wpdb->update( $table_name, $data, $where_data, array_values($data_field_format), array_values($where_field_format) );
      } else {
        $result = $this->wpdb->update( $table_name, $data, $where_data, array_values($data_field_format) );
      }
    } else {
      $result = $this->wpdb->update( $table_name, $data, $where_data );
    }
    if ($is_update_to_null) {
      remove_filter('query', array($this, 'update_at_null_data'));
    }
    $retvar = $this->strtobool($result);
    if (!$retvar) {
      $message = __('Failed to modify your specified data.', CDBT);
      $message .= $this->retrieve_db_error();
      $this->logger( $message );
    }
    
    // Fire after the updated data
    //
    // @since 2.0.0
    do_action( 'cdbt_after_updated_data', $retvar, $table_name, $data, $where_data );
    
    return $retvar;
    
  }
  
  /**
   * Filter the update query if it contains a null in modify data values.
   *
   * @since 2.0.0
   */
  public function update_at_null_data( $query ) {
    
    return str_ireplace( "'NULL'", 'NULL', $query );
    
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
   * @param mixed $where_clause [require] In legacy v1.0 was the designation of the `$primary_key_value`
   * @return boolean
   */
  public function delete_data( $table_name=null, $where_clause=null ) {
    static $message = '';
    
    // Check Table
    if (false === ($table_schema = $this->get_table_schema($table_name))) {
      $message = sprintf( $this->common_error_messages[0], __FUNCTION__ );
      $this->logger( $message );
      return false;
    }
    
    // Check condition to specify data
    if ( empty( $where_clause ) ) {
      $message = __('Condition to find the deleting data is not specified.', CDBT);
    } else {
      $_deletion_where = is_string( $where_clause ) ? $this->strtohash( $where_clause ) : $where_clause;
      if ( false === $_deletion_where || ! $this->is_assoc( $_deletion_where ) ) 
        $message = __('Condition to find the deleting data is invalid.', CDBT);
    }
    if ( ! empty( $message ) ) {
      $this->logger( $message );
      return false;
    }
    
    $delete_where = [];
    $field_format = [];
    foreach ($_deletion_where as $column => $value) {
      if ('null' === $value || is_null($value)) {
        continue;
      } else {
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
    }
    
    $_diff_result = array_diff_key($delete_where, $field_format);
    if (empty($_diff_result)) {
      $result = $this->wpdb->delete( $table_name, $delete_where, array_values($field_format) );
    } else {
      $result = $this->wpdb->delete( $table_name, $delete_where );
    }
    $retvar = $this->strtobool($result);
    if ( ! $retvar ) {
      $_hash_where_clause = '{ ';
      foreach ( $delete_where as $_col => $_val ) {
        $_hash_where_clause .= $_col .':'. $_val .', ';
      }
      $_hash_where_clause .= '}';
      $message = sprintf( __('Failed to remove data of the deletion condition of "%s".', CDBT), $_hash_where_clause );
      $message .= $this->retrieve_db_error();
      $this->logger( $message );
    }
    
    // Fire after the data deletion
    //
    // @since 2.0.0
    // @since 2.0.7 Enhancement of function
    do_action( 'cdbt_after_data_deletion', $retvar, $table_name, $_deletion_where );
    
    return $retvar;
    
  }
  
  
  /**
   * Run the custom query via `$wpdb::query` or `mysqli` or `PDO`
   *
   * @since 2.0.0 For bundle as protected method
   * @since 2.0.7 Change to public method, and added enhancement of function
   *
   * @param string $query [required] Must be the runnable correct SQL statement
   * @param string $api [optional] Whether mysql api is "wpdb" or "mysqli" or "PDO"; default value is "wpdb".
   * @return mixed
   */
  public function run_query( $query=null, $api='wpdb' ) {
  	if ( empty( $query ) || ! is_string( $query ) ) {
  	  return false;
  	} else {
  	  $query = stripslashes_deep( $query );
  	}
  	
    if ( empty( $api ) || 'wpdb' === $api || ! in_array( strtolower( $api ), [ 'wpdb', 'mysqli', 'pdo' ] ) ) {
      $retvar = $this->wpdb->query( $query );
    } elseif ( 'mysqli' === strtolower( $api ) && class_exists( '\mysqli' ) ) {
      $db_handler = new \mysqli( DB_HOST, DB_USER, DB_PASSWORD, DB_NAME );
      if ( is_object( $db_handler ) ) {
        $result = $db_handler->query( $query );
        if ( $result ) {
          $rows = $result->fetch_all( MYSQLI_ASSOC );
          $retvar = count( $rows ) > 1 ? $rows : $rows[0];
        } else {
          $retvar = $result;
        }
      } else {
        $message = sprintf( $this->common_error_messages[1], 'mysqli' );
      }
    } elseif ( 'pdo' === strtolower( $api ) && class_exists( '\PDO' ) ) {
      $db_handler = new \PDO( 'mysql:host='. DB_HOST .';dbname='. DB_NAME, DB_USER, DB_PASSWORD );
      if ( is_object( $db_handler ) ) {
        $result = $db_handler->query( $query, \PDO::FETCH_ASSOC );
        if ( $result ) {
          $rows = [];
          foreach ( $result as $row ) {
            $rows[] = $row;
          }
          $retvar = count( $rows ) > 1 ? $rows : $rows[0];
        } else {
          $retvar = $result;
        }
      } else {
        $message = sprintf( $this->common_error_messages[1], 'PDO' );
      }
    }
    
    if ( ! $retvar ) {
      $message = $this->retrieve_db_error();
    }
    if ( ! empty( $message ) ) {
      $this->logger( $message );
    }
    return $retvar;
    
  }
  
  
  /**
   * Run the dump of specific table like mysqldump
   *
   * @since 2.0.0
   * @since 2.0.7 Enhancement of function
   *
   * @param string $table_name [require]
   * @param array $columns [optional] Array of column names of when dump a specific column
   * @param boolean $contains_create_sql [optional] For default `False`
   * @return string $dump_sql
   */
  public function dump_table( $table_name=null, $columns=[], $contains_create_sql=false ) {
    if (empty($table_name)) 
      return '';
    
    $table_schema = $this->get_table_schema( $table_name );
    $orderby = null;
    foreach ($table_schema as $column => $scheme) {
      if ($scheme['primary_key']) 
        $orderby[$column] = 'ASC';
    }
    
    $target_columns = empty( $columns ) || $this->is_assoc( $columns ) ? '*' : $columns;
    $raw_data = $this->get_data( $table_name, $target_columns, null, $orderby, 'ARRAY_A' );
    $rows = [];
    foreach ($raw_data as $raw_value) {
      $esc_values = [];
      foreach ($raw_value as $val) {
        $esc_values[] = esc_sql($val);
      }
      $rows[] = "('". implode("','", $esc_values) ."')";
    }
    $insert_sql = '';
    if ( ! empty( $rows ) && ! empty( $raw_value ) ) {
      $insert_sql = sprintf( "INSERT INTO `%s` (`%s`) VALUES %s;", $table_name, implode( '`,`', array_keys( $raw_value ) ), implode( ',', $rows ) );
    }
    
    // Added at version 2.0.7
    $create_sql = '';
    if ( $this->strtobool( $contains_create_sql ) ) {
      $create_sql = $this->get_create_table_sql( $table_name );
      if ( 0 === stripos( $create_sql, 'CREATE TABLE ' ) ) {
        $create_sql = 'CREATE TABLE IF NOT EXISTS ' . substr( $create_sql, strlen( 'CREATE TABLE ' ) );
      }
      $create_sql .= "; \n";
    }
    $dump_sql = $create_sql . $insert_sql;
    
    return $dump_sql;
  }
  
  
  /**
   * Retrieve specific tables list in the database.
   *
   * @since 1.1.0
   * @since 2.0.0 Have refactored logic.
   * @since 2.1.34 Updated
   *
   * @param string $narrow_type For default `all`, `enable` can get the currently manageable tables on this plugin. As other is `unreserved` and `unmanageable`.
   * @return mixed Array is if find, or return `false`.
   */
  public function get_table_list( $narrow_type='all' ) {
    $all_tables = $this->wpdb->get_results( 'SHOW TABLES', 'ARRAY_N' );
    $all_tables = $this->array_flatten( $all_tables );
    $unreserved_tables = array_diff( $all_tables, $this->core_tables );
    
    $manageable_tables = [];
    foreach ( $this->options['tables'] as $table ) {
      if ( ! in_array( $table['table_type'], [ 'template' ] ) && ! empty( $table['table_name'] ) ) 
        $manageable_tables[] = $table['table_name'];
    }
    
    $unmanageable_tables = array_diff($all_tables, $manageable_tables);
    
    $return_tables = false;
    
    if ( 'all' === $narrow_type && ! empty( $all_tables ) ) 
      $return_tables = $all_tables;
    
    if ( 'enable' === $narrow_type && ! empty( $manageable_tables ) ) 
      $return_tables = $manageable_tables;
    
    if ( 'unreserved' === $narrow_type && ! empty( $unreserved_tables ) ) 
      $return_tables = $unreserved_tables;
    
    if ( 'unmanageable' === $narrow_type && ! empty( $unmanageable_tables ) ) 
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
  public function compare_reservation_tables( $table_name=null ) {
    if (empty($table_name)) 
      return false;
    
    return in_array($table_name, $this->core_tables);
    
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
   * Create the SQL statement for the import.
   *
   * @since 2.0.0
   *
   * @param string $table_name [require] 
   * @param array $importation_base_data [require] 
   * @return mixed 
   */
  public function create_import_sql( $table_name=null, $importation_base_data=[] ) {
    if (empty($table_name) || empty($importation_base_data)) 
      return false;
    
    $_columns = array_shift($importation_base_data);
    $rows = [];
    foreach ($importation_base_data as $_row) {
      $_esc_values = [];
      foreach ($_row as $_value) {
        $_esc_values[] = esc_sql($_value);
      }
      $_rows[] = "('". implode("','", $_esc_values) ."')";
    }
//    $importation_sql = sprintf("INSERT INTO `%s` (`%s`) VALUES %s ON DUPLICATE KEY UPDATE;", $table_name, implode('`,`', $_columns), implode(',', $_rows));
    $importation_sql = sprintf("INSERT INTO `%s` (`%s`) VALUES %s;", $table_name, implode('`,`', $_columns), implode(',', $_rows));
    
    return $importation_sql;
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
      $message = sprintf( $this->common_error_messages[0], __FUNCTION__ );
    
    if (!$this->check_table_exists($table_name)) 
      $message = __('No origin table for exporting.', CDBT);
    
    if (empty($export_file_type) || !in_array($export_file_type, $this->allow_file_types)) 
      $message = sprintf( __('The "%s" of specified download file format does not supported.', CDBT), $export_file_type );
    
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
          $message = sprintf( __('The "%s" column for exporting does not exist.', CDBT), $column );
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
   * @since 2.0.7 Added of escaping the HTML statements that is included in the field value of the string type.
   *
   * @param string $table_name [require]
   * @param array $post_data [require]
   * @return mixed $raster_data False is returned if invalid data
   */
  protected function cleanup_data( $table_name=null, $post_data=[] ) {
    
    if (false === ($table_schema = $this->get_table_schema($table_name))) 
      return false;
    
    $register_data = [];
    foreach ($post_data as $post_key => $post_value) {
      if (array_key_exists($post_key, $table_schema)) {
        if ('' === $post_value || is_null($post_value)) {
          $register_data[$post_key] = null;
          continue;
        }
      	
        $detect_column_type = $this->validate->check_column_type($table_schema[$post_key]['type']);
        
        if ( array_key_exists( 'char', $detect_column_type ) ) {
          $_table_option = $this->get_table_option( $table_name );
          if ( $_table_option && array_key_exists( 'sanitization', $_table_option ) && $_table_option['sanitization'] ) {
            if ( array_key_exists( 'text', $detect_column_type ) ) {
              // Sanitization data from textarea
              $allowed_html_tags = [ 'a' => [ 'href' => [], 'title' => [] ], 'br' => [], 'em' => [], 'strong' => [] ];
              // Filter of the tag list to be allowed for data in the text area
              //
              // @since 2.0.0
              $allowed_html_tags = apply_filters( 'cdbt_sanitize_data_allow_tags', $allowed_html_tags, $table_name );
              $register_data[$post_key] = wp_kses($post_value, $allowed_html_tags);
            } else {
              // Sanitization data from text field
              if ( is_email( $post_value ) ) {
                $register_data[$post_key] = sanitize_email( $post_value );
              } else {
                $register_data[$post_key] = sanitize_text_field( $post_value );
              }
            }
          } else {
            $register_data[$post_key] = $post_value;
          }
        }
        
        if (array_key_exists('numeric', $detect_column_type)) {
          if (array_key_exists('integer', $detect_column_type)) {
            // Sanitization data of integer
            $register_data[$post_key] = $table_schema[$post_key]['unsigned'] ? absint($post_value) : intval($post_value);
          } else
          if (array_key_exists('float', $detect_column_type)) {
            // Sanitization data of float
            $register_data[$post_key] = 'decimal' === $detect_column_type['float'] ? strval(floatval($post_value)) : floatval($post_value);
          } else
          if (array_key_exists('binary', $detect_column_type)) {
            // Sanitization data of bainary bit
            if ( in_array( $post_value, [ 0, 1, '0', '1', true, false, 'true', 'false', 'TRUE', 'FALSE' ] ) ) {
              $register_data[$post_key] = $this->strtobool( $post_value );
            } else {
              $register_data[$post_key] = sprintf( "b'%s'", decbin( $post_value ) );
            }
          } else {
            $register_data[$post_key] = intval( $post_value );
          }
        }
        
        if (array_key_exists('list', $detect_column_type)) {
          if ('enum' === $detect_column_type['list']) {
            // Validation data of enum element
            if (in_array($post_value, $this->parse_list_elements($table_schema[$post_key]['type_format']))) {
              $register_data[$post_key] = $post_value;
            } else {
              $register_data[$post_key] = $table_schema[$post_key]['default'];
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
            $register_data[$post_key] = implode(',', $_save_array);
            unset($list_array, $_save_array, $item);
          }
        }
        
        if (array_key_exists('datetime', $detect_column_type)) {
          if (is_array($post_value)) {
            if (in_array($detect_column_type['datetime'], [ 'date', 'datetime' ])) {
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
                $register_data[$post_key] = sprintf('%s %s:%s:%s', $_date, $_hour, $_minute, $_second);
              } else {
                $_datetime = $_date . $_hour . $_minute . $_second;
                $register_data[$post_key] = !empty($_datetime) ? $_datetime : $table_schema[$post_key]['default'];
              }
            } else {
              $register_data[$post_key] = empty($post_value) ? $table_schema[$post_key]['default'] : $post_value;
            }
            // Validation data of datetime
            if (!$this->validate->checkDateTime($register_data[$post_key], 'Y-m-d H:i:s')) {
              $register_data[$post_key] = '0000-00-00 00:00:00';
            }
            
            $_prev_timezone = date_default_timezone_get();
            // Filter for localize the datetime at the timezone specified by options
            //
            // @since 2.0.0
            $_localize_timezone = apply_filters( 'cdbt_local_timezone_datetime', $this->options['timezone'] );
            date_default_timezone_set( $_localize_timezone );
            
            $_timestamp = '0000-00-00 00:00:00' === $register_data[$post_key] ? date_i18n('U') : strtotime($register_data[$post_key]);
            $register_data[$post_key] = date_i18n( 'Y-m-d H:i:s', $_timestamp );
            
            date_default_timezone_set( $_prev_timezone );
            unset($_date, $_hour, $_minute, $_second, $_prev_timezone, $_localize_timezone, $_timestamp);
          } else
          if (in_array($detect_column_type['datetime'], [ 'year', 'timestamp' ])) {
            if ('year' === $detect_column_type['datetime']) {
              if (strlen($post_value) === 4 && preg_match('/^[0-9]{4}$/', $post_value)) {
                $register_data[$post_key] = $post_value;
              } else
              if (strlen($post_value) === 2 && preg_match('/^[0-9]{2}$/', $post_value)) {
                $register_data[$post_key] = $post_value;
              } else {
                $register_data[$post_key] = '00';
              }
            }
            if ('timestamp' === $detect_column_type['datetime']) {
              // Sanitization data of integer
              if (preg_match('/^[0-9]{1,10}$/', $post_value)) {
                $_timestamp = '0000-00-00 00:00:00' === $post_value ? date_i18n('U') : strtotime($post_value);
                $register_data[$post_key] = $_timestamp;
              } else {
                $register_data[$post_key] = time();
              }
            }
          } else {
            if (preg_match('/^[0-9]{1,2}\:[0-9]{1,2}\:[0-9]{1,2}$/', $post_value)) {
              $register_data[$post_key] = $post_value;
            } else {
          	  $register_data[$post_key] = '00:00:00';
          	}
          }
        }
        
        if (array_key_exists('file', $detect_column_type)) {
          // Check the `$_FILES`
          //var_dump($detect_column_type['file']); // debug code
        }
        
      }
      $_diff = array_diff_key( $table_schema, $post_data );
      if ( ! empty( $_diff ) ) {
        foreach ( $_diff as $_column => $_scheme ) {
          if ( $_scheme['primary_key'] && 'auto_increment' === $_scheme['extra'] ) 
            continue;
          if ( 'CURRENT_TIMESTAMP' === $_scheme['default'] && 'on update CURRENT_TIMESTAMP' === $_scheme['extra'] ) 
            continue;
          if ( 'bit(1)' === $_scheme['type_format'] ) {
            $register_data[$_column] = false;
          }
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
          $register_data[$column] = $this->get_binary_context( $file_data['tmp_name'], $file_data['name'], $file_data['type'], $file_data['size'], true );
        }
      }
    }
    
    return !empty($register_data) ? $register_data : false;
    
  }
  
  
  /**
   * Retrieve the column type difinitions in the table of MySQL
   *
   * @since 2.0.0
   *
   * @param string $narrow_key [optional] For default is null; otherwise is able to set values for "allowed_types" or specific column type.
   * @return array $return
   */
  public function get_column_types( $narrow_key=null ) {
    
    $column_types = [
      'tinyint' => [ 'arg_type' => 'precision', 'default' => 4, 'min' => 1, 'max' => 4, 'atts' => [ 'unsigned', 'zerofill' ], 'alias' => [] ], // precision: 精度。数字全体の有効桁数
      'smallint' => [ 'arg_type' => 'precision', 'default' => 6, 'min' => 1, 'max' => 6, 'atts' => [ 'unsigned', 'zerofill' ], 'alias' => [] ], // precision: 精度。数字全体の有効桁数
      'mediumint' => [ 'arg_type' => 'precision', 'default' => 9, 'min' => 1, 'max' => 9, 'atts' => [ 'unsigned', 'zerofill' ], 'alias' => [] ], // precision: 精度。数字全体の有効桁数
      'int' => [ 'arg_type' => 'precision', 'default' => 11, 'min' => 1, 'max' => 11, 'atts' => [ 'unsigned', 'zerofill' ], 'alias' => [ 'integer' ] ], // precision: 精度。数字全体の有効桁数
      'bigint' => [ 'arg_type' => 'precision', 'default' => 20, 'min' => 1, 'max' => 20, 'atts' => [ 'unsigned', 'zerofill' ], 'alias' => [] ], // precision: 精度。数字全体の有効桁数
      'float' => [ 'arg_type' => [ 'precision', 'scale' ], 'default' => '', 'min' => [ 1, 0 ], 'max' => [ 53, 30 ], 'atts' => [ 'unsigned', 'zerofill' ], 'alias' => [] ], // 小数部を含んで6桁まで入力された通りに保存する用途であれば、float型を使う
      'double' => [ 'arg_type' => [ 'precision', 'scale' ], 'default' => '', 'min' => [ 1, 0 ], 'max' => [ 53, 30 ], 'atts' => [ 'unsigned', 'zerofill' ], 'alias' => [ 'double precision', 'real' ] ], // precisionが25以上のfloat(*)はdoubleと同等
      'decimal' => [ 'arg_type' => [ 'precision', 'scale' ], 'default' => [ 10, 0 ], 'min' => [ 1, 0 ], 'max' => [ 65, 30 ], 'atts' => [ 'unsigned', 'zerofill' ], 'alias' => [ 'dec', 'numeric', 'fixed' ] ], // 小数点以下を指定して型を揃えて正確に扱うならば、decimal型を使う （例:緯度経度情報）
      'bool' => [ 'arg_type' => '', 'default' => '', 'min' => '', 'max' => '', 'atts' => [], 'alias' => [ 'boolean' ] ], // tinyint(1)のエイリアス
      'bit' => [ 'arg_type' => 'precision', 'default' => 1, 'min' => 1, 'max' => 64, 'atts' => [], 'alias' => [] ], // precisionはbitのByte数
      'varchar' => [ 'arg_type' => 'maxlength', 'default' => 1, 'min' => 0, 'max' => 255, 'atts' => [ 'binary' ], 'alias' => [ 'national varchar' ] ], // maxlengthが255より大きい場合はtext型に変換される
      'char' => [ 'arg_type' => 'maxlength', 'default' => 255, 'min' => 0, 'max' => 255, 'atts' => [ 'binary', 'ascii', 'unicode' ], 'alias' => [ 'national char', 'nchar', 'character' ] ], // maxlength省略時はchar(1)となる
      'tinytext' => [ 'arg_type' => '', 'default' => '', 'min' => '', 'max' => '', 'atts' => [], 'alias' => [] ], // 最大長 255文字
      'text' => [ 'arg_type' => '', 'default' => '', 'min' => '', 'max' => '', 'atts' => [], 'alias' => [] ], // 最大長 65535文字
      'mediumtext' => [ 'arg_type' => '', 'default' => '', 'min' => '', 'max' => '', 'atts' => [], 'alias' => [] ], // 最大長 16777215文字
      'longtext' => [ 'arg_type' => '', 'default' => '', 'min' => '', 'max' => '', 'atts' => [], 'alias' => [] ], // 最大長 4294967295文字
      'tinyblob' => [ 'arg_type' => '', 'default' => '', 'min' => '', 'max' => '', 'atts' => [], 'alias' => [] ], // 最大長 255Byte
      'blob' => [ 'arg_type' => '', 'default' => '', 'min' => '', 'max' => '', 'atts' => [], 'alias' => [] ], // 最大長 64KB
      'mediumblob' => [ 'arg_type' => '', 'default' => '', 'min' => '', 'max' => '', 'atts' => [], 'alias' => [] ], // 最大長 16MB
      'longblob' => [ 'arg_type' => '', 'default' => '', 'min' => '', 'max' => '', 'atts' => [], 'alias' => [] ], // 最大長 4GB
      'binary' => [ 'arg_type' => 'maxlength', 'default' => 255, 'min' => 0, 'max' => 255, 'atts' => [], 'alias' => [ 'char byte' ] ], // 最大長 255Byte、指定バイト数より格納値が少ない場合に末尾を0x00で埋める
      'varbinary' => [ 'arg_type' => 'maxlength', 'default' => 65535, 'min' => 0, 'max' => 65535, 'atts' => [], 'alias' => [] ], // 最大長 64KB、末尾の0x00埋めを行わない
      'enum' => [ 'arg_type' => 'array', 'default' => '', 'min' => 1, 'max' => 65535, 'atts' => [], 'alias' => [] ], // ユニークリスト 65535個まで
      'set' => [ 'arg_type' => 'array', 'default' => '', 'min' => 0, 'max' => 64, 'atts' => [], 'alias' => [] ], // ユニークリスト 64個まで
      'date' => [ 'arg_type' => '', 'default' => '', 'min' => '1000-01-01', 'max' => '9999-12-31', 'atts' => [], 'alias' => [] ], // 'YYYY-MM-DD'形式文字列か数値を使用できる
      'datetime' => [ 'arg_type' => '', 'default' => '', 'min' => '1000-01-01 00:00:00', 'max' => '9999-12-31 23:59:59', 'atts' => [], 'alias' => [] ], // 'YYYY-MM-DD HH:MM:SS'形式文字列か数値を使用できる
      'time' => [ 'arg_type' => '', 'default' => null, 'min' => '-838:59:59', 'max' => '838:59:59', 'atts' => [], 'alias' => [] ], // 'HH:MM:SS'形式文字列か数値を使用できる
      'timestamp' => [ 'arg_type' => [ 6, 8, 12, 14 ], 'default' => '', 'min' => 6, 'max' => 14, 'atts' => [],  'alias' => [] ], // 引数は表示形式の桁数（'YYMMDD','YYYYMMDD','YYMMDDHHMMSS', 'YYYYMMDDHHMMSS'）を表す
      'year' => [ 'arg_type' => [ 2, 4 ], 'default' => 4, 'min' => 2, 'max' => 4, 'atts' => [], 'alias' => [] ], // 'YYYY'か'YY'形式の文字列か数値を使用できる
    ];
    
    if (empty($narrow_key) || in_array(strtolower($narrow_key), [ 'allowed_types' ]) || array_key_exists(strtolower($narrow_key), $column_types)) 
      return $column_types;
    
    $results = [];
    if ('allowed_types' === strtolower($narrow_key)) {
      foreach ( $column_types as $_type => $_attr) {
        $results[] = $_type;
        if (isset($_attr['alias']) && !empty($_attr['alias'])) 
          $results = array_meerge($results, $_attr['alias']);
      }
      $results = array_unique($results);
    } else
    if (array_key_exists(strtolower($narrow_key), $column_types)) {
      $results = $column_types[strtolower($narrow_key)];
    }
    
    return $results;
    
  }
  
  
  /**
   * Filter select clause optimaization
   *
   * @since 2.0.7
   * @since 2.0.8 Fixed a bug
   *
   * @param mixed $columns [required] String or array
   * @param string $table_name [optional]
   * @param string $call_function [optional]
   * @return string $select_clause
   */
  public function select_clause_optimaize( $columns, $table_name=null, $call_function=null ) {
    $has_bit = [];
    if ( ! empty( $table_name ) && $_scheme = $this->get_table_schema( $table_name ) ) {
      foreach ( $_scheme as $_key => $_val ) {
        if ( 'bit' === $_val['type'] ) {
          $has_bit[$_key] = $_val['type_format'];
        }
      }
    }
    if ( is_array( $columns ) ) {
      $_cols = [];
      foreach ( $columns as $_i => $_col ) {
        if ( $_pos = strpos( $_col, chr(96) ) !== false ) {
          $_col = substr_replace( $_col, '', $_pos, 1);
        }
        if ( $_pos = strrpos( $_col, chr(96) ) !== false ) {
          $_col = substr_replace( $_col, '', $_pos, 1 );
        }
        $_col = trim( $_col );
      	if ( ! empty( $has_bit ) && array_key_exists( $_col, $has_bit ) ) {
      	  $_cols[$_i] = 'BIN('. $_col . ')';
        } else {
          $_cols[$_i] = '`'. $_col .'`';
        }
      }
    } else
    if ( is_string( $columns ) ) {
      $_cols = explode( ',', str_replace( chr(96), '', $columns ) );
      foreach ( $_cols as $_i => $_col ) {
        if ( strpos( strtolower( $_col ), 'count(' ) !== false || strpos( $_col, '*' ) !== false ) {
          $_cols[$_i] = trim( $_col );
        } else {
          if ( $_pos = strpos( $_col, chr(96) ) !== false ) {
            $_col = substr_replace( $_col, '', $_pos, 1);
          }
          if ( $_pos = strrpos( $_col, chr(96) ) !== false ) {
            $_col = substr_replace( $_col, '', $_pos, 1 );
          }
          $_col = trim( $_col );
          if ( ! empty( $has_bit ) && array_key_exists( $_col, $has_bit ) ) {
      	    $_cols[$_i] = 'BIN('. $_col . ')';
          } else {
            $_cols[$_i] = '`'. $_col .'`';
          }
        }
      }
    } else {
      $_cols = [ '*' ];
    }
    if ( in_array( '*', $_cols ) && ! empty( $_scheme ) ) {
      $_cols = array_keys( $_scheme );
      foreach ( $_cols as $_i => $_col ) {
        if ( ! empty( $has_bit ) && array_key_exists( $_col, $has_bit ) ) {
          $_cols[$_i] = 'BIN('. $_col . ')';
        } else {
          $_cols[$_i] = strpos( $_col, chr(96) ) === false ? chr(96) . $_col . chr(96) : $_col;
        }
      }
    }
    $select_clause = implode( ',', $_cols );
    
    return $select_clause;
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
  
  
  /**
   * Filter the table name by "lower_case_table_names" of database
   *
   * @since 2.0.10
   */
  protected function lowercase_table_name( $table_name ) {
    if ( ! empty( $table_name ) && isset( $this->db_lower_case ) ) {
      if ( is_string( $table_name ) ) {
        $table_name = $this->db_lower_case == 1 ? strtolower( $table_name ) : $table_name;
      } else 
      if ( is_array( $table_name ) && ! empty( $table_name ) ) {
        if ( count( $table_name ) == 1 ) 
          $table_name = $this->db_lower_case == 1 ? strtolower( $table_name[0] ) : $table_name[0];
      }
    }
    return $table_name;
  }
  
  
}

endif; // end of class_exists()