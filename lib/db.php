<?php

namespace CustomDataBaseTables\Lib;


if ( !defined( 'CDBT' ) ) exit;

if ( !class_exists( 'CdbtDB' ) ) :
/**
 * Database operation class for plugins
 *
 * @since CustomDataBaseTables v2.0.0
 */
class CdbtDB extends CdbtConfig {

  protected function db_init() {
    
    // Group of tables that are reserved in wordpress
    $this->reserved_tables = array_merge($this->tables, $this->old_tables, $this->global_tables, $this->ms_global_tables);
    
    // Tables of currently database for this site
    $this->core_tables = $this->wpdb->tables('all');
    
    // Database charset
    $this->charset = $this->charset; // default `utf8` or new `utf8mb4`
    
    // Database collate
    $this->collate = $this->collate; // default `utf8_general_ci` or new `utf8mb4_unicode_ci`
    
    // Showing of database errors
    $this->show_errors = true;
    $this->show_errors( $this->show_errors );
    
  }
  
  /**
   * Methods operate database with wrapping the wpdb
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
    
    return $table_name == $result;
  }
  
  
  /**
   * Get table schema
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



}

endif; // end of class_exists()