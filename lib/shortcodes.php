<?php

namespace CustomDataBaseTables\Lib;


/**
 * Trait of shortcode difinitions for this plugin 
 *
 * @since 2.0.0
 *
 */
trait CdbtShortcodes {
  
  private $shortcodes;
  
  /**
   * Register the built-in shortcodes
   *
   * @since 2.0.0
   **/
  protected function shortcode_register() {
    
    $this->shortcodes = [
      'cdbt-view' => [
        'method' => 'view_data_list', 
        'description' => __('Retrieve a table data that match the specified conditions, then it outputs as list.', CDBT), /* This shortcode is able to output the data of the specified table as a list format. */
        'type' => 'built-in', 
        'author' => 0, 
        'permission' => implode(', ', $this->convert_cap_level(0)), 
        'alias_id' => null, 
      ],
      'cdbt-entry' => [
        'method' => 'entry_data_form', 
        'description' => __('Render the data registration form for the specified table.', CDBT), 
        'type' => 'built-in', 
        'author' => 0, 
        'permission' => implode(', ', $this->convert_cap_level(1)), 
        'alias_id' => null, 
      ],
      'cdbt-edit' => [
        'method' => 'editable_data_list', 
        'description' => __('Render the editable data list for the specified table.', CDBT), 
        'type' => 'built-in', 
        'author' => 0, 
        'permission' => implode(', ', $this->convert_cap_level(7)), 
        'alias_id' => null, 
      ],
      'cdbt-extract' => [
        'method' => 'view_data_list', 
        'description' => __('Deprecated since version 2; This shortcode has been merged into "cdbt-view".', CDBT), 
        'type' => 'deprecated', 
        'author' => 0, 
        'permission' => implode(', ', $this->convert_cap_level(0)), 
        'alias_id' => null, 
      ],
      'cdbt-submit' => [
        'method' => 'submit_custom_query', 
        'description' => __('Deprecated since version 2.', CDBT), /* バージョン2で非推奨になりました。 */
        'type' => 'deprecated', 
        'author' => 0, 
        'permission' => implode(', ', $this->convert_cap_level(9)), 
        'alias_id' => null, 
      ],
    ];
    foreach ($this->shortcodes as $shortcode_name => $_attributes) {
      if (method_exists($this, $_attributes['method'])) 
        add_shortcode( $shortcode_name, array($this, $_attributes['method']) );
    }
    
    $this->option_shortcodes = get_option($this->domain_name . '-shortcodes', $this->option_shortcodes);
    
  }
  
  
  /**
   * Retrieve specific shortcode list as an array
   *
   * @since 2.0.0
   * @since 2.0.7 Fixed a bug
   *
   * @param string $shortcode_type [optional]
   * @return array $shortcode_list
   **/
  public function get_shortcode_list( $shortcode_type=null ) {
    $shortcode_list = $this->shortcodes;
    
    $custom_shortcodes = $this->get_shortcode_option();
    if ( ! empty( $custom_shortcodes ) ) {
      $_add_shortcodes = [];
      foreach ( $custom_shortcodes as $_i => $_shortcode_options ) {
        if ( $this->check_table_exists( $_shortcode_options['target_table'] ) ) {
          $_permissions = $this->get_table_permission( $_shortcode_options['target_table'], str_replace( 'cdbt-', '', $_shortcode_options['base_name'] ) );
          if ( ! $_permissions ) 
            $_permissions = $this->shortcodes[$_shortcode_options['base_name']]['permission'];
          $_desc = isset($_shortcode_options['description']) && !empty($_shortcode_options['description']) ? esc_html($_shortcode_options['description']) : '-';
        } else {
          $_permissions = [];
          $_desc = __('This shortcode is not valid because the table that is specified does not exist.', CDBT);
        }
        
        $_add_shortcodes[stripslashes_deep($_shortcode_options['alias_code'])] = [
          'description' => $_desc, 
          'type' => 'custom', 
          'author' => isset($_shortcode_options['author']) ? intval($_shortcode_options['author']) : 0, 
          'permission' => ! empty( $_permissions ) ? implode( ', ', $_permissions ) : '-', 
          'alias_id' => $_shortcode_options['csid'], 
        ];
      }
      $shortcode_list = array_merge($shortcode_list, $_add_shortcodes);
    }
    
    if (!empty($shortcode_type)) {
      foreach ($shortcode_list as $shortcode_name => $_attributes) {
        if ($shortcode_type !== $_attributes['type']) 
          unset($shortcode_list[$shortcode_name]);
      }
    }
    
    return $shortcode_list;
  }
  
  
  /**
   * Retrieve specific shortcode options via `get_option()`
   *
   * @since 2.0.0
   *
   * @param integer $csid [optional] Target data of a specific shortcode if specified custom shortcode id, otherwise the all shortcodes
   * @return array $shortcode_options
   **/
  public function get_shortcode_option( $csid=null ) {
    $stored_shortcodes = get_option($this->domain_name . '-shortcodes', $this->option_shortcodes);
    $shortcode_options = [];
    
    if (!empty($stored_shortcodes) && intval($csid) > 0) {
      foreach ($stored_shortcodes as $_i => $_shortcode_option) {
        if (intval($csid) === intval($_shortcode_option['csid'])) {
          $shortcode_options = $_shortcode_option;
          break;
        }
      }
    } else {
      $shortcode_options = $stored_shortcodes;
    }
    
    return $shortcode_options;
  }
  
  
  /**
   * Retrieve unique csid that has been increment
   *
   * @since 2.0.0
   *
   * @return integer $increment_csid
   **/
  public function get_increment_unique_csid() {
    $stored_shortcodes = $this->get_shortcode_option();
    $ids = [];
    
    if (!empty($stored_shortcodes)) {
      foreach ($stored_shortcodes as $_shortcode_option) {
        if (is_array($_shortcode_option) && array_key_exists('csid', $_shortcode_option)) 
          $ids[] = intval($_shortcode_option['csid']);
      }
      if (!empty($ids)) 
        $increment_csid = max($ids) + 1;
    }
    if (!isset($increment_csid)) 
      $increment_csid = 1;
    
    return $increment_csid;
  }
  
  
  /**
   * Check the allowed rendering range of shortcode
   *
   * @since 2.0.0
   *
   * @return boolean $is_allowed
   **/
  public function check_allowed_rendering_shortcode() {
    $is_allowed = true;
    
    if (!is_admin()) {
      if (isset($this->options['allow_rendering_shortcodes']) && $this->strtobool($this->options['allow_rendering_shortcodes']) && !is_singular()) 
        $is_allowed = false;
    }
    
    return $is_allowed;
  }
  
  /**
   * for [cdbt-view] ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
   * Retrieve a table data that match the specified conditions, then it outputs as list
   *
   * @since 1.0.0
   * @since 2.0.0 Have refactored logic.
   *
   * @param array $attributes [require] Array of attributes in shortcode
   * @param string $content [optional] For default is empty
   * @return string $html_content The formatted content as list
   **/
  public function view_data_list() {
    list($attributes, $content) = func_get_args();
    extract( shortcode_atts([
      'table' => '', // Required attribute
      'bootstrap_style' => true, // Change from v2.0.0 disabled.
      'display_list_num' => false, // The default value has changed to false from v2.0.0
      'display_search' => true, // 
      'display_title' => true, 
      'enable_sort' => true, // 
      'exclude_cols' => '', // String as array (not assoc); For example `col1,col2,col3,...`
      'add_class' => '', // Separator is a single-byte space character
      /* As legacy of `cdbt-extract` is follows: */
      'display_index_row' => true, 
      'narrow_keyword' => '', // String as array (not assoc) is `find_data()`; For example `keyword1,keyword2,...` Or String as hash is `get_data()`; For example `col1:keyword1,col2:keyword2,...`
      'display_cols' => '', // String as array (not assoc); For example `col1,col2,col3,...` If overlapped with `exclude_cols`, set to override the `exclude_cols`.
      'order_cols' => '', // String as array (not assoc); For example `col3,col2,col1,...` If overlapped with `display_cols`, set to override the `display_cols`.
      'sort_order' => 'created:desc', // String as hash for example `updated:desc,ID:asc,...`
      'limit_items' => '', // The default value is overwritten by the value of the max_show_records of the specified table.
      'image_render' => 'responsive', // class name for directly image render: 'rounded', 'circle', 'thumbnail', 'responsive', (until 'minimum', 'modal' )
      /* Added new attribute from 2.0.0 is follows: */
      'enable_repeater' => true, // Rendering by using repeater component at Fuel UX.
      'display_filter' => false, // Is enabled only if "enable_repeater" is true.
      'filter_column' => '', // Target column name to filter.
      'filters' => '', // String as array (assoc); For example `filter1:label1,filter2:label2,...`
      'display_view' => false, //  Is enabled only if "enable_repeater" is true.
      'thumbnail_column' => '', // Column name to be used as a thumbnail image (image binary or a URL of image must be stored in this column)
      'thumbnail_title_column' => '', // Column name to be used as a thumbnail title
      'thumbnail_width' => 100, // Integer of thumbnail block size
      'ajax_load' => false, //
      'csid' => 0, // Valid value of "Custom Shortcode ID" is 1 or more integer. 
      /* Added new attributes from 2.0.7 is follows: */
      'narrow_operator' => 'and', // String of either `and` or `or`; for method of `find_data()`
      // 'strip_tags' => true, // Whether to strip the tags in the string type data.
      /* Added new attributes from 2.0.10 is follows: */
      'truncate_strings' => 0, 
    ], $attributes) );
    if (empty($table) || !$this->check_table_exists($table)) 
      return;
    
    if (!$this->check_allowed_rendering_shortcode()) 
      return;
    
    // Initialization process for the shortcode
    $shortcode_name = 'cdbt-view';
    $table_schema = $this->get_table_schema($table);
    $table_option = $this->get_table_option($table);
    $pk_columns = $has_char = $has_text = $has_bin = $has_list = $has_bit = $has_datetime = [];
    if (false !== $table_option) {
      $table_type = $table_option['table_type'];
      $has_pk = !empty($table_option['primary_key']) ? true : false;
      $pk_columns = $has_pk ? $table_option['primary_key'] : [];
      $limit_items = empty( $limit_items ) || intval( $limit_items ) < 1 ? intval( $table_option['show_max_records'] ) : intval( $limit_items );
      $truncate_strings = empty( $truncate_strings ) || intval( $truncate_strings ) < 0 ? 0 : intval( $truncate_strings );
      $strip_tags = array_key_exists( 'sanitization', $table_option ) ? $table_option['sanitization'] : true;
      foreach ($table_schema as $column => $scheme) {
      	if ($this->validate->check_column_type($scheme['type'], 'char'))
      	  $has_char[] = $column;
      	
      	if ($this->validate->check_column_type($scheme['type'], 'text'))
      	  $has_text[] = $column;
      	
        if ($this->validate->check_column_type($scheme['type'], 'blob')) 
          $has_bin[] = $column;
        
        if ($this->validate->check_column_type($scheme['type'], 'list')) 
          $has_list[] = $column;
        
        if ($this->validate->check_column_type($scheme['type'], 'binary')) 
          $has_bit[] = $column;
        
        if ($this->validate->check_column_type($scheme['type'], 'datetime')) {
          if (in_array($scheme['type'], [ 'date', 'datetime', 'timestamp' ])) 
            $has_datetime[] = $column;
        }
        
      }
    } else {
      if (in_array($table, $this->core_tables)) 
        $table_type = 'wp_core';
      
      $has_pk = false;
      foreach ($table_schema as $column => $scheme) {
        if ($scheme['primary_key']) {
          $has_pk = true;
          $pk_columns[] = $column;
        }
      	if ($this->validate->check_column_type($scheme['type'], 'char')) 
      	  $has_char[] = $column;
      	
      	if ($this->validate->check_column_type($scheme['type'], 'text')) 
      	  $has_text[] = $column;
      	
        if ($this->validate->check_column_type($scheme['type'], 'blob')) 
          $has_bin[] = $column;
        
        if ($this->validate->check_column_type($scheme['type'], 'datetime')) {
          if (in_array($scheme['type'], [ 'date', 'datetime', 'timestamp' ])) 
            $has_datetime[] = $column;
        }
      }
      $limit_items = empty($limit_items) || intval($limit_items) < 1 ? intval($this->options['default_per_records']) : intval($limit_items);
      $strip_tags = false;
    }
    $content = '';
    
    // Check user permission
    $result_permit = false;
    if (isset($table_option['permission']) && isset($table_option['permission']['view_global']) && !empty($table_option['permission']['view_global'])) {
      // Standard from v2.0.0
      $result_permit = $this->is_permit_user($table_option['permission']['view_global']);
    } else
    if (isset($table_option['roles']) && isset($table_option['roles']['view_role'])) {
      // As legacy v.1.x
      foreach(array_reverse($this->user_roles) as $role_name) {
        $_role = get_role($role_name);
        if (is_object($_role) && array_key_exists('level_' . $table_option['roles']['view_role'], $_role->capabilities)) {
          $check_role = $_role->name;
          break;
        }
      }
      $result_permit = $this->is_permit_user( $check_role );
    } else
    if ('wp_core' === $table_type) {
      // If WordPress core tables
      $result_permit = $this->is_permit_user( 'administrator' );
    }
    
    // Filter the viewing rights check result of the shortcode
    // You can give viewing rights to specific users by utilizing this filter hook.
    //
    // @since 2.0.0
    $result_permit = apply_filters( 'cdbt_after_shortcode_permit', $result_permit, $shortcode_name, $table );
    
    if (!$result_permit) 
      return sprintf('<p>%s</p>', __('You can not see this content without permission.', CDBT));
    
    // Validation of the attributes, then sanitizing
    $boolean_atts = [ 'bootstrap_style', 'display_list_num', 'display_search', 'display_title', 'enable_sort', 'display_index_row', 'enable_repeater', 'display_filter', 'ajax_load', 'strip_tags' ];
    foreach ($boolean_atts as $attribute_name) {
      ${$attribute_name} = $this->strtobool( rawurldecode( ${$attribute_name} ) );
    }
    $not_assoc_atts = [ 'exclude_cols', 'display_cols', 'order_cols' ];
    foreach ($not_assoc_atts as $attribute_name) {
      ${$attribute_name} = $this->strtoarray( rawurldecode( ${$attribute_name} ) );
    }
    $hash_atts = [ 'narrow_keyword', 'sort_order', 'filters' ];
    foreach ($hash_atts as $attribute_name) {
      ${$attribute_name} = $this->strtohash( rawurldecode( ${$attribute_name} ) );
    }
    $add_classes = [];
    if ( ! empty( $add_class ) ) {
      foreach ( explode( ' ', rawurldecode( $add_class ) ) as $_class ) {
        $add_classes[] = esc_attr( trim( $_class ) );
      }
    }
    
    if ($csid > 0 && $this->validate->checkInt($csid)) {
      // Checking whether the shortcode exists that has "csid (Custom Shortcode ID)".
      $loaded_settings = $this->get_shortcode_option($csid);
      if ($loaded_settings['base_name'] === $shortcode_name && $loaded_settings['target_table'] === $table) {
        foreach ($loaded_settings as $_key => $_val) {
          if (!in_array($_key, [ 'base_name', 'target_table', 'description', 'csid', 'author', 'generate_shortcode', 'alias_code' ])) {
            ${$_key} = $_val;
          }
        }
      }
    } else {
      $csid = 0;
    }
    
/* debug codes
    $all_vars = [
      'table', 'bootstrap_style', 'display_list_num', 'display_search', 'display_title', 'enable_sort', 'exclude_cols', 'add_class', 
      'display_index_row', 'narrow_keyword', 'display_cols', 'order_cols', 'sort_order', 'limit_items', 'image_render', 
      'enable_repeater', 'display_filter', 'filter_column', 'filters', 'display_view', 'thumbnail_column', 'thumbnail_title_column', 'thumbnail_width', 'ajax_load', 
      'csid', 
    ];
    foreach ($all_vars as $_var) {
      print_r($_var . '="' . ${$_var} . '"'. "\n");
    }
*/
    
    if ($bootstrap_style && $enable_repeater) {
      $component_name = 'repeater';
    } else {
      $component_name = 'table';
    }
    
    if (!empty($image_render) && !in_array(strtolower($image_render), [ 'rounded', 'circle', 'thumbnail', 'responsive' ])) {
      $image_render = 'responsive';
    } else {
      $image_render = strtolower($image_render);
    }
    if ($display_title) {
      $disp_title = $this->get_table_comment($table);
      $disp_title = !empty($disp_title) ? $disp_title : $table;
      $title = '<h4 class="sub-description-title">' . sprintf( __('View Data in "%s" Table', CDBT), $disp_title ) . '</h4>';
    }
    
    $all_columns = array_keys($table_schema);
    if ($exclude_cols = $this->strtoarray($exclude_cols)) {
      $output_columns = [];
      foreach ($all_columns as $_col) {
        if (!in_array($_col, $exclude_cols)) 
          $output_columns[] = $_col;
      }
    }
    if ($display_cols = $this->strtoarray($display_cols)) {
      $output_columns = [];
      foreach ($all_columns as $_col) {
        if (in_array($_col, $display_cols)) 
          $output_columns[] = $_col;
      }
    }
    if ($order_cols = $this->strtoarray($order_cols)) {
      $output_columns = [];
      foreach ($order_cols as $_col) {
        if (in_array($_col, $all_columns)) 
          $output_columns[] = $_col;
      }
    }
    if (!isset($output_columns)) 
      $output_columns = $all_columns;
    
    $narrow_keyword = $this->is_assoc( $narrow_keyword ) ? $narrow_keyword : $this->strtohash( $narrow_keyword );
    if ( ! $narrow_keyword ) {
      $query_type = 'get';
    } else {
      $query_type = $this->is_assoc( $narrow_keyword ) ? 'get' : 'find';
      $conditions = [];
      if ( 'get' === $query_type ) {
        foreach ( $narrow_keyword as $_col => $_keywd ) {
          if ( in_array( $_col, $all_columns ) ) 
            $conditions[$_col] = $_keywd;
        }
      } else {
        $conditions = $narrow_keyword;
      }
    }
    if ( ! isset( $conditions ) ) 
      $conditions = null;
    
    $sort_order = $this->is_assoc( $sort_order ) ? $sort_order : $this->strtohash( $sort_order );
    if ( $this->is_assoc( $sort_order ) ) {
      $orders = [];
      foreach ( $sort_order as $_col => $_order ) {
        if ( ! is_int( $_col ) && in_array( $_col, $all_columns ) ) 
          $orders[$_col] = in_array( strtolower( $_order ), [ 'asc', 'desc' ] ) ? $_order : 'asc';
      }
    }
    if ( ! isset( $orders ) || empty( $orders ) ) 
      $orders = null;
    
    if (!in_array($filter_column, $all_columns)) {
      $filter_column = '';
    }
    $filters = $this->strtohash($filters);
    
    if ( ! $display_index_row ) {
      $add_classes[] = 'hidden-index-row';
    }
    $add_class = implode( ' ', $add_classes );
    
    if ('get' === $query_type) {
      // $datasource = $this->get_data($table, 'ARRAY_A');
      $datasource = $this->get_data($table, '`'.implode('`,`', $output_columns).'`', $conditions, $orders, 'ARRAY_A');
    } else {
      $datasource = [];
      // Added since version 2.0.7
      $narrow_operator = strtolower( $narrow_operator );
      if (is_array($conditions) && !empty($conditions)) {
        foreach ($conditions as $_i => $_keyword) {
          if (0 === $_i) {
            $datasource = $this->find_data($table, $_keyword, $narrow_operator, $output_columns, $orders, 'ARRAY_A');
          } else {
            // Currently, the plurality of keywords are not supported
            /*
            $diff_datasource = $this->find_data($table, $_keyword, $output_columns, $orders, 'ARRAY_A');
            if (is_array($diff_datasource) && is_array($datasource)) 
              $datasource = array_intersect($diff_datasource, $datasource);
              //$datasource = array_merge($datasource, $diff_datasource);
            */
            break;
          }
        }
      } else {
        $datasource = $this->find_data($table, $conditions, $narrow_operator, $output_columns, $orders, 'ARRAY_A');
      }
    }
    if (empty($datasource))
      return sprintf('<p>%s</p>', __('No data in this table.', CDBT));
    
    $custom_column_renderer = [];
    
    // If contain string as char in the data source (added since version 2.0.7)
    if ( ! empty( $has_char ) ) {
      foreach ( $has_char as $column ) {
        if ( array_key_exists( $column, $datasource[0] ) ) {
          foreach ($datasource as $i => $row_data) {
            if ( $strip_tags ) {
              $datasource[$i][$column] = strip_tags( $row_data[$column] );
            } else {
              $datasource[$i][$column] = stripslashes_deep( $this->validate->esc_column_value( $row_data[$column], 'char' ) );
            }
          }
        }
      }
    }
    
    // If contain string as text in the data source (added since version 2.0.7)
    if ( ! empty( $has_text ) ) {
      foreach ( $has_text as $column ) {
        if ( array_key_exists( $column, $datasource[0] ) ) {
          foreach ($datasource as $i => $row_data) {
            if ( $strip_tags ) {
              $datasource[$i][$column] = strip_tags( $row_data[$column] );
            } else {
              $datasource[$i][$column] = stripslashes_deep( $this->validate->esc_column_value( $row_data[$column], 'text' ) );
            }
          }
        }
      }
    }
    
    // If contain binary data in the datasource
    if ( ! empty( $has_bin ) ) {
      $custom_row_scripts = [];
      if ( $has_pk ) {
        
      }
      foreach ( $datasource as $i => $row_data ) {
        foreach ( $has_bin as $col_name ) {
          if ( array_key_exists( $col_name, $row_data ) ) {
            if ( 'image' === $this->check_binary_data( $row_data[$col_name] ) ) {
              $row_data[$col_name] = sprintf( 'data:%s;base64,%s', $this->esc_binary_data( $row_data[$col_name], 'mime_type' ), $this->esc_binary_data( $row_data[$col_name], 'bin_data' ) );
              if ( is_admin() && empty( $thumbnail_column ) ) {
                $display_view = true;
                $thumbnail_column = $col_name;
              }
              $custom_row_scripts[] = sprintf( 'helpers.rowData[\'%s\'] = !helpers.rowData[\'%s\'] ? \'data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==\' : helpers.rowData[\'%s\'];', $col_name, $col_name, $col_name );
            } else {
              $row_data[$col_name] = $this->esc_binary_data( $row_data[$col_name], 'origin_file' );
            }
            $_where_conditions = [];
            if ( $has_pk ) {
              $_where_conditions = $pk_columns;
            }
            $_render_script_base = 'rowData[\'%s\'] !== false ? \'<a href="javascript:;" class="binary-data modal-preview" data-column-name="%s" data-where-conditions="%s"><input type="hidden" data="\' + rowData[\'%s\'] + \'" data-class="img-%s"></a>\' : \'\'';
            $custom_column_renderer[$col_name] = sprintf( $_render_script_base, $col_name, $col_name, implode( ',', $_where_conditions ), $col_name, $image_render );
            $datasource[$i] = $row_data;
          } else {
            $custom_column_renderer[$col_name] = '';
          }
        }
      }
    }
    
    // If contain list type columns
    if (!empty($has_list)) {
      $_filter_items = [];
      foreach ($has_list as $column) {
        if (array_key_exists($column, $datasource[0])) {
          foreach ($this->parse_list_elements($table_schema[$column]['type_format']) as $list_item) {
            $_filter_items[] = sprintf( '%s:%s', esc_attr($list_item), __($list_item, CDBT) );
          }
          if ('set' === $table_schema[$column]['type']) {
            $custom_column_renderer[$column] = '\'<ul class="list-inline">\' + convert_list(rowData[\''. $column .'\']) + \'</ul>\'';
          }
        }
      }
      if ($display_filter && empty($filters)) {
        if (!empty($_filter_items)) 
          $filters = array_unique($_filter_items);
      }
      unset($_filter_items);
    }
    
    // If contain bit binary data in the datasource
    // @since 2.0.7 Updated
    if ( ! empty( $has_bit ) ) {
      foreach ( $has_bit as $column ) {
        if ( array_key_exists( $column, $datasource[0] ) || array_key_exists( 'BIN('. $column .')', $datasource[0] ) ) {
          
          foreach ( $datasource as $_i => $_data_row ) {
            foreach ( $_data_row as $_dcol => $_dval ) {
              if ( $column === $_dcol || 'BIN('. $column .')' === $_dcol ) {
                $datasource[$_i][$column] = $_dval;
                unset( $datasource[$_i][$_dcol] );
              } else {
                $datasource[$_i][$_dcol] = $_dval;
              }
            }
          }
          // Filter whether to use the icon display in the case of outputting the data registered in boolean form
          //
          // @since 2.0.0
          $bool_data_with_icon = apply_filters( 'cdbt_boolean_data_with_icon', true, $shortcode_name, $table );
          
          if ( $bool_data_with_icon ) {
            $custom_column_renderer[$column] = '\'<div class="center-block text-center"><small><i class="\' + (rowData[\''. $column .'\'] === \'1\' ? \'fa fa-circle-o\' : \'fa fa-time\' ) + \'"></i><span class="sr-only">\' + rowData[\''. $column .'\'] + \'</span></small></div>\'';
          } else {
            $custom_column_renderer[$column] = '\'<div class="center-block text-center">\' + (rowData[\''. $column .'\'] === \'1\' ? \'true\' : \'false\' ) + \'</div>\'';
          }
        }
      }
    }
    
    // If contain datetime data in the datasource
    if (!empty($has_datetime)) {
      foreach ($has_datetime as $column) {
        if (array_key_exists($column, $datasource[0])) {
          if (empty($this->options['display_datetime_format'])) {
            $_datetime_format = '[\''. get_option( 'date_format' ) .'\', \''. get_option( 'time_format' ) .'\']';
          } else {
            $_datetime_format = '[\''. $this->options['display_datetime_format'] .'\']';
          }
          $custom_column_renderer[$column] = '\'<div class="custom-datetime">\' + convert_datetime(rowData[\''. $column .'\'], '. $_datetime_format .') + \'</div>\'';
        }
      }
      unset($_datetime_format);
    }
    
    
    $columns = [];
    foreach ($output_columns as $column) {
      if (array_key_exists($column, $datasource[0])) {
        $columns[] = [
          'label' => empty($table_schema[$column]['logical_name']) ? $column : $table_schema[$column]['logical_name'], 
          'property' => $column, 
          'sortable' => $enable_sort, 
          'sortDirection' => array_key_exists($column, $sort_order) ? $sort_order[$column] : 'asc', 
          'dataNumric' => $this->validate->check_column_type( $table_schema[$column]['type'], 'numeric' ), 
          'truncateStrings' => $truncate_strings, 
          'className' => $enable_sort ? '' : 'disable-sort', 
        ];
      }
    }
    
    if (isset($custom_column_renderer) && !empty($custom_column_renderer)) {
      foreach ($columns as $i => $column_definition) {
        if (array_key_exists($column_definition['property'], $custom_column_renderer)) {
          $columns[$i] = array_merge($columns[$i], [ 'customColumnRenderer' => $custom_column_renderer[$column_definition['property']] ]);
        }
      }
      unset($i);
    }
    
    if ('regular' === $table_type && $display_list_num) {
      foreach ($datasource as $i => $datum) {
        $datasource[$i] = array_merge([ 'data_index_number' => $i + 1 ], $datum);
      }
      $add_column = [ 'label' => '#', 'property' => 'data_index_number', 'sortable' => $enable_sort, 'sortDirection' => 'asc', 'dataNumric' => true, 'width' => 80 ];
      array_unshift($columns, $add_column);
    }
    
    // Filter the column definition of the list content that is output by this shortcode
    //
    // @since 2.0.0
    $columns = apply_filters( 'cdbt_shortcode_custom_columns', $columns, $shortcode_name, $table );
    
    $component_options = [
      'id' => 'cdbt-repeater-view-' . $table, 
      'enableSearch' => $display_search, 
      'enableFilter' => $display_filter, 
      'filter_column' => $filter_column, 
      'filters' => $filters, 
      'enableView' => $display_view, 
      'defaultView' => 'list', 
      'listSelectable' => 'false', 
      'staticHeight' => -1, 
      'pageIndex' => 1, 
      'pageSize' => $limit_items, 
      'columns' => $columns, 
      'data' => $datasource, 
    ];
    
    if ('repeater' === $component_name) {
      $add_options = [ 
        'addClass' => $add_class, 
      ];
    } else {
      $add_options = [
        'tableClass' => $add_class, 
        'theadClass' => '', 
        'tbodyClass' => '', 
        'tfootClass' => '', 
      ];
    }
    $component_options = array_merge($component_options, $add_options);
    
    if ($display_view && !empty($thumbnail_column) && array_key_exists($thumbnail_column, $table_schema)) {
      $thumbnail_title = !empty($thumbnail_title_column) ? sprintf('<span>{{%s}}</span>', esc_html($thumbnail_title_column)) : '';
      $thumbnail_template = '\'<div class="thumbnail repeater-thumbnail" style="background: #ffffff;"><img src="{{'. $thumbnail_column .'}}" width="'. intval($thumbnail_width) .'">'. $thumbnail_title .'</div>\'';
      $component_options = array_merge($component_options, [ 'thumbnailTemplate' => $thumbnail_template ]);
      if (isset($custom_row_scripts) && !empty($custom_row_scripts)) 
        $component_options = array_merge($component_options, [ 'customRowScripts' => $custom_row_scripts ]);
    }
    
    // Filter the component definition of the list content that is output by this shortcode
    //
    // @since 2.0.0
    $component_options = apply_filters( 'cdbt_shortcode_custom_component_options', $component_options, $shortcode_name, $table );
    
    if ( is_admin() ) {
      if (isset($title)) 
        echo $title;
      
      return $this->component_render( $component_name, $component_options );
    } else {
      ob_start();
      if (isset($title)) 
        echo $title;
      
      echo $this->component_render( $component_name, $component_options );
      
      $render_content = ob_get_contents();
      ob_end_clean();
      
      return $render_content;
    }
    
  }
  
  
  /**
   * for [cdbt-entry] ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
   * Render the data registration form for the specified table
   *
   * @since 1.0.0
   * @since 2.0.0 Have refactored logic.
   *
   * @param array $attributes [require] Array of attributes in shortcode
   * @param string $content [optional] For default is empty
   * @return string $html_content The created form contents
   **/
  public function entry_data_form() {
    list($attributes, $content) = func_get_args();
    extract( shortcode_atts([
      'table' => '', // Required attribute
      'bootstrap_style' => true, // 
      'display_title' => true, 
      'hidden_cols' => '', // String as array (not assoc); For example `col1,col2,col3,...`
      'add_class' => '', // Separator is a single-byte space character
      /* Added new attribute from 2.0.0 is follows: */
      'action_url' => '', // String of url for form action [optional] For using shortcode of `cdbt-edit`
      'form_action' => 'entry_data', // String of action name as method after submiting [optional] Is `edit_data` if edit data
      'display_submit' => true, // Boolean [optional] For using shortcode of `cdbt-edit`
      'where_clause' => '', // String as array (assoc); For example `col1:value1,col2:value2,...`, For using shortcode of `cdbt-edit`
      'redirect_url' => '', // String of the url to redirect after data insertion (since version 2.0.5)
      'csid' => 0, // Valid value of "Custom Shortcode ID" is 1 or more integer. 
    ], $attributes) );
    if (empty($table) || !$this->check_table_exists($table)) 
      return;
    
    if (!$this->check_allowed_rendering_shortcode()) 
      return;
    
    // Initialization process for the shortcode
    $shortcode_name = 'cdbt-entry';
    $table_schema = $this->get_table_schema($table);
    $table_option = $this->get_table_option($table);
    if (false !== $table_option) {
      $table_type = $table_option['table_type'];
    } else {
      if (in_array($table, $this->core_tables)) 
        $table_type = 'wp_core';
    }
    $content = '';
    
    // Check user permission
    $result_permit = false;
    if (isset($table_option['permission']) && isset($table_option['permission']['entry_global']) && !empty($table_option['permission']['entry_global'])) {
      // Standard from v2.0.0
      $result_permit = $this->is_permit_user($table_option['permission']['entry_global']);
    } else
    if (isset($table_option['roles']) && isset($table_option['roles']['input_role'])) {
      // As legacy v.1.x
      foreach(array_reverse($this->user_roles) as $role_name) {
        $_role = get_role($role_name);
        if (is_object($_role) && array_key_exists('level_' . $table_option['roles']['input_role'], $_role->capabilities)) {
          $check_role = $_role->name;
          break;
        }
      }
      $result_permit = $this->is_permit_user( $check_role );
    } else
    if ('wp_core' === $table_type) {
      // If WordPress core tables
      $result_permit = $this->is_permit_user( 'administrator' );
    }
    
    // Filter the viewing rights check result of the shortcode
    // You can give viewing rights to specific users by utilizing this filter hook.
    //
    // @since 2.0.0
    $result_permit = apply_filters( 'cdbt_after_shortcode_permit', $result_permit, $shortcode_name, $table );
    
    if (!$result_permit) 
      return sprintf('<p>%s</p>', __('You can not see this content without permission.', CDBT));
    
    
    // Validation of the attributes, then sanitizing
    $boolean_atts = [ 'bootstrap_style', 'display_title', 'display_submit' ];
    foreach ($boolean_atts as $attribute_name) {
      ${$attribute_name} = $this->strtobool( rawurldecode( ${$attribute_name} ) );
    }
    $not_assoc_atts = [ 'hidden_cols' ];
    foreach ($not_assoc_atts as $attribute_name) {
      ${$attribute_name} = $this->strtoarray( rawurldecode( ${$attribute_name} ) );
    }
    $hash_atts = [ 'where_clause' ];
    foreach ($hash_atts as $attribute_name) {
      ${$attribute_name} = $this->strtohash( ${$attribute_name} );
    }
    if ( ! empty( $add_class ) ) {
      $add_classes = [];
      foreach ( explode( ' ', rawurldecode( $add_class ) ) as $_class ) {
        $add_classes[] = esc_attr( trim( $_class ) );
      }
      $add_class = implode( ' ', $add_classes );
    }
    if ($csid > 0 && $this->validate->checkInt($csid)) {
      // Checking whether the shortcode exists that has "csid (Custom Shortcode ID)".
      $loaded_settings = $this->get_shortcode_option($csid);
      if ($loaded_settings['base_name'] === $shortcode_name && $loaded_settings['target_table'] === $table) {
        foreach ($loaded_settings as $_key => $_val) {
          if (!in_array($_key, [ 'base_name', 'target_table', 'description', 'csid', 'author', 'generate_shortcode', 'alias_code' ])) {
            ${$_key} = $_val;
          }
        }
      }
    } else {
      $csid = 0;
    }
    if ($display_title) {
      $disp_title = $this->get_table_comment($table);
      $disp_title = !empty($disp_title) ? $disp_title : $table;
      $title = '<h4 class="sub-description-title">' . sprintf( __('Entry Data to "%s" Table', CDBT), $disp_title ) . '</h4>';
    }
    
    
    $elements_options = $has_bit = [];
    $is_file_upload = false;
    foreach ($table_schema as $column => $scheme) {
      if ( $scheme['primary_key'] && false !== strpos( $scheme['extra'], 'auto_increment' ) ) 
        continue;
      
      $detect_column_type = $this->validate->check_column_type($scheme['type']);
      if( array_key_exists('datetime', $detect_column_type) && 'CURRENT_TIMESTAMP' === strtoupper($scheme['default']) ) 
        continue;
      
      unset($input_type, $rows, $max_file_size, $max_length, $element_size, $pattern, $selectable_list);
      if (array_key_exists('char', $detect_column_type)) {
        if (array_key_exists('text', $detect_column_type)) {
          $input_type = 'textarea';
          // $max_length = $scheme['max_length'];
          if ('longtext' === $detect_column_type['text']) {
            $rows = 20;
          } else
          if ('midiumtext' === $detect_column_type['text']) {
            $rows = 15;
          } else
          if ('tinytext' === $detect_column_type['text']) {
            $rows = 5;
          } else {
            $rows = 10;
          }
        } else
        if (array_key_exists('blob', $detect_column_type)) {
          $input_type = 'file';
          $max_file_size = $scheme['max_length'];
          $is_file_upload = true;
        } else {
          $input_type = 'text';
          $max_length = $scheme['max_length'];
        }
      } else
      if (array_key_exists('numeric', $detect_column_type)) {
        if (array_key_exists('integer', $detect_column_type)) {
          $input_type = 'number';
          if ($scheme['unsigned']) 
            $min = 0;
          $element_size = ceil($scheme['max_length'] / 10) + 1;
          $pattern = $scheme['unsigned'] ? '^[0-9]+$' : '^(\-|)[0-9]+$';
        } else
        if (array_key_exists('binary', $detect_column_type)) {
          $input_type = 'boolean';
          if (preg_match('/^b\'(.*)\'$/iU', $scheme['default'], $matches) && is_array($matches) && array_key_exists(1, $matches)) {
            $scheme['default'] = $this->strtobool($matches[1]);
          }
          $has_bit[] = $column;
        } else {
          $input_type = 'text';
          $element_size = ceil($scheme['max_length'] / 10);
          $pattern = $scheme['unsigned'] ? '^[0-9]{0,}(|\.)[0-9]+$' : '^(\-|)[0-9]{0,}(|\.)[0-9]+$';
        }
      } else
      if (array_key_exists('list', $detect_column_type)) {
        $input_type = 'enum' === $detect_column_type['list'] ? 'select' : 'checkbox';
        $selectable_list = [];
        foreach ($this->parse_list_elements($scheme['type_format']) as $list_value) {
          $selectable_list[] = sprintf( '%s:%s', __($list_value, CDBT), esc_attr($list_value) );
        }
        unset($list_value);
      } else
      if (array_key_exists('datetime', $detect_column_type)) {
        if (in_array($detect_column_type['datetime'], [ 'timestamp', 'year' ])) {
          $input_type = 'number';
          $element_size = ceil($scheme['max_length'] / 10) + 1;
          $pattern = '^[0-9]+$';
        } else
        if ('time' === $detect_column_type['datetime']) {
          $input_type = 'text';
          $element_size = 3;
          $pattern = '^[0-9]{1,2}\:[0-9]{1,2}\:[0-9]{1,2}$';
        } else {
          $input_type = 'datetime';
          $is_datetime = 'datetime' === $detect_column_type['datetime'] ? true : false;
        }
      } else {
        $input_type = 'text';
      }
      
      // Fixed at version 2.0.5
      if ( ! is_array( $hidden_cols ) ) 
        $hidden_cols = $this->strtoarray( $hidden_cols );
      if ( isset( $hidden_cols ) && ! empty( $hidden_cols ) && in_array( $column, $hidden_cols ) ) {
        $input_type = 'hidden';
        $_required = false;
      } else {
        $_required = $scheme['not_null'];
      }
      
      $_temp_elements_options = [
        'elementName' => $column, 
        'elementLabel' => !empty($scheme['logical_name']) ? $scheme['logical_name'] : $column, 
        'elementType' => $input_type, 
        'isRequired' => $_required, 
        'defaultValue' => !empty($scheme['default']) ? $scheme['default'] : '', 
        'placeholder' => '', 
        'addClass' => '', 
        'selectableList' => isset($selectable_list) && !empty($selectable_list) ? implode(',', $selectable_list) : '', 
        'horizontalList' => false, 
        'elementSize' => isset($element_size) && !empty($element_size) ? intval($element_size) : 0, 
        'helperText' => '', 
        'elementExtras' => [], // 'maxlength' => '', 'pattern' => '', 
      ];
      if (isset($max_length) && !empty($max_length)) 
        $_temp_elements_options['elementExtras']['maxlength'] = $max_length;
      if (isset($pattern) && !empty($pattern)) 
        $_temp_elements_options['elementExtras']['pattern'] = $pattern;
      if (isset($rows) && !empty($rows)) 
        $_temp_elements_options['elementExtras']['rows'] = $rows;
      if (isset($max_file_size) && !empty($max_file_size)) 
        $_temp_elements_options['elementExtras']['maxlength'] = $this->convert_filesize($max_file_size);
      if ('datetime' === $input_type) {
        if (isset($is_datetime) && !empty($is_datetime)) 
          $_temp_elements_options['elementExtras']['datetime'] = $is_datetime ? 'true' : 'false';
        $_temp_elements_options['elementExtras']['data-moment-locale'] = 'ja';
        $_temp_elements_options['elementExtras']['data-moment-format'] = 'L';
      }
      
      $elements_options[] = $_temp_elements_options;
    }
    
    // Override of initial value to for editing
    // @since 2.0.7 Updated for bit type
    // @since 2.0.9 Fixed a bug of datatime type
    if ( ! empty( $where_clause ) && is_array( $where_clause ) ) {
       foreach ( $where_clause as $_key => $_value ) {
         $where_clause[$_key] = rawurldecode( $_value );
       }
       $_current_data = $this->get_data( $table, '*', $where_clause, 'ARRAY_A' );
      if ( ! empty( $_current_data ) && array_key_exists( 0, $_current_data ) ) {
        if ( ! empty( $has_bit ) ) {
          foreach ( $has_bit as $_bit_col ) {
            if ( array_key_exists( 'BIN('. $_bit_col .')', $_current_data[0] ) ) {
              $_current_data[0][$_bit_col] = $_current_data[0]['BIN('. $_bit_col .')'];
              unset( $_current_data[0]['BIN('. $_bit_col .')'] );
            }
          }
        }
        foreach ( $elements_options as $_i => $_element ) {
          if ( array_key_exists( $_element['elementName'], $_current_data[0] ) ) {
            $elements_options[$_i]['defaultValue'] = stripslashes_deep( $_current_data[0][$_element['elementName']] );
          }
        }
      }
    }
    
    
    // Filter the form content definition that is output by this shortcode
    //
    // @since 2.0.0
    $elements_options = apply_filters( 'cdbt_shortcode_custom_forms', $elements_options, $shortcode_name, $table );
    
    $component_options = [
      'id' => 'cdbt-entry-data-to-' . $table, 
      'entryTable' => $table, 
      'useBootstrap' => true, 
      'outputTitle' => isset($title) ? $title : '', 
      'fileUpload' => isset($is_file_upload) ? $is_file_upload : false, 
      'formElements' => $elements_options, 
    ];
    if (!empty($action_url)) 
      $component_options['actionUrl'] = $action_url;
    if (!empty($form_action)) 
      $component_options['formAction'] = $form_action;
    if (!$display_submit) 
      $component_options['displaySubmit'] = $display_submit;
    if (!empty($where_clause) && is_array($where_clause)) 
      $component_options['whereClause'] = $where_clause;
    if ( ! empty( $redirect_url ) ) 
      $component_options['redirectUrl'] = rawurldecode( $redirect_url );
    
    // Filter the component definition of the list content that is output by this shortcode
    //
    // @since 2.0.0
    $component_options = apply_filters( 'cdbt_shortcode_custom_component_options', $component_options, $shortcode_name, $table );
    
    if ( is_admin() ) {
      return $this->component_render('forms', $component_options);
    } else {
      ob_start();
      
      echo $this->component_render( 'forms', $component_options );
      
      $render_content = ob_get_contents();
      
      ob_end_clean();
      
      return $render_content;
    }
    
  }
  
  
  /**
   * for [cdbt-edit] ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
   * Render the editable data list for the specified table
   *
   * @since 1.0.0
   * @since 2.0.0 Have refactored logic.
   *
   * @param array $attributes [require] Array of attributes in shortcode
   * @param string $content [optional] For default is empty
   * @return string $html_content The created form contents
   **/
  public function editable_data_list() {
    list($attributes, $content) = func_get_args();
    extract( shortcode_atts([
      'table' => '', // Required attribute
      'entry_page' => '', // Deprecated attributes from v2.0.0 (actually not work)
      'bootstrap_style' => true, // Change from v2.0.0 disabled.
      'display_list_num' => false, // Deprecated attributes, and the default value has changed to false  from v2.0.0 (actually not work)
      'display_title' => true, 
      'enable_sort' => true, //  Is enabled only if "bootstrap_style" is true.
      'exclude_cols' => '', // String as array (not assoc); For example `col1,col2,col3,...`
      'add_class' => '', // Separator is a single-byte space character
      /* Added new attribute from 2.0.0 is follows: */
      'display_filter' => false, // Is enabled only if "bootstrap_style" is true.
      'filter_column' => '', // Target column name to filter.
      'filters' => '', // String as array (assoc); For example `filter1:label1,filter2:label2,...`
      'ajax_load' => false, // Is enabled only if "bootstrap_style" is true.
      'csid' => 0, // Valid value of "Custom Shortcode ID" is 1 or more integer. 
      /* Added new attribute from 2.0.6 is follows: */
      'narrow_keyword' => '', // String as array (not assoc) is `find_data()`; For example `keyword1,keyword2,...` Or String as hash is `get_data()`; For example `col1:keyword1,col2:keyword2,...`
      'sort_order' => 'created:desc', // String as hash for example `updated:desc,ID:asc,...`
      /* Added new attributes from 2.0.7 is follows: */
      'narrow_operator' => 'and', // String of either `and` or `or`; for method of `find_data()`
      // 'strip_tags' => true, // Whether to strip the tags in the string type data.
      /* Added new attributes from 2.0.10 is follows: */
      'truncate_strings' => 0, 
    ], $attributes) );
    if (empty($table) || !$this->check_table_exists($table)) 
      return;
    
    if (!$this->check_allowed_rendering_shortcode()) 
      return;
    
    // Initialization process for the shortcode
    $shortcode_name = 'cdbt-edit';
    $table_schema = $this->get_table_schema($table);
    $table_option = $this->get_table_option($table);
    $pk_columns = $has_char = $has_text = $has_bin = $has_list = $has_bit = $has_datetime = [];
    if (false !== $table_option) {
      $table_type = $table_option['table_type'];
      $has_pk = !empty($table_option['primary_key']) ? true : false;
      $pk_columns = $has_pk ? $table_option['primary_key'] : [];
      $limit_items = empty( $limit_items ) || intval( $limit_items ) < 1 ? intval( $table_option['show_max_records'] ) : intval( $limit_items );
      $truncate_strings = empty( $truncate_strings ) || intval( $truncate_strings ) < 0 ? 0 : intval( $truncate_strings );
      $strip_tags = array_key_exists( 'sanitization', $table_option ) ? $table_option['sanitization'] : true;
      foreach ($table_schema as $column => $scheme) {
      	if ($this->validate->check_column_type($scheme['type'], 'char'))
      	  $has_char[] = $column;
      	
      	if ($this->validate->check_column_type($scheme['type'], 'text'))
      	  $has_text[] = $column;
      	
        if ($this->validate->check_column_type($scheme['type'], 'blob')) 
          $has_bin[] = $column;
        
        if ($this->validate->check_column_type($scheme['type'], 'list')) 
          $has_list[] = $column;
        
        if ($this->validate->check_column_type($scheme['type'], 'binary')) 
          $has_bit[] = $column;
        
        if ($this->validate->check_column_type($scheme['type'], 'datetime')) {
          if (in_array($scheme['type'], [ 'date', 'datetime', 'timestamp' ])) 
            $has_datetime[] = $column;
        }
        
      }
    } else {
      if (in_array($table, $this->core_tables)) 
        $table_type = 'wp_core';
      
      $has_pk = false;
      foreach ($table_schema as $column => $scheme) {
        if ($scheme['primary_key']) {
          $has_pk = true;
          $pk_columns[] = $column;
        }
      	if ($this->validate->check_column_type($scheme['type'], 'char')) 
      	  $has_char[] = $column;
      	
      	if ($this->validate->check_column_type($scheme['type'], 'text')) 
      	  $has_text[] = $column;
      	
        if ($this->validate->check_column_type($scheme['type'], 'blob')) 
          $has_bin[] = $column;
        
        if ($this->validate->check_column_type($scheme['type'], 'datetime')) {
          if (in_array($scheme['type'], [ 'date', 'datetime', 'timestamp' ])) 
            $has_datetime[] = $column;
        }
      }
      $limit_items = intval($this->options['default_per_records']);
      $strip_tags = false;
    }
    $content = '';
    
    // Check user permission
    $result_permit = false;
    if (isset($table_option['permission']) && isset($table_option['permission']['edit_global']) && !empty($table_option['permission']['edit_global'])) {
      // Standard from v2.0.0
      $result_permit = $this->is_permit_user($table_option['permission']['edit_global']);
    } else
    if (isset($table_option['roles']) && isset($table_option['roles']['edit_role'])) {
      // As legacy v.1.x
      foreach(array_reverse($this->user_roles) as $role_name) {
        $_role = get_role($role_name);
        if (is_object($_role) && array_key_exists('level_' . $table_option['roles']['edit_role'], $_role->capabilities)) {
          $check_role = $_role->name;
          break;
        }
      }
      $result_permit = $this->is_permit_user( $check_role );
    } else
    if ('wp_core' === $table_type) {
      // If WordPress core tables
      $result_permit = $this->is_permit_user( 'administrator' );
    }
    
    // Filter the viewing rights check result of the shortcode
    // You can give viewing rights to specific users by utilizing this filter hook.
    //
    // @since 2.0.0
    $result_permit = apply_filters( 'cdbt_after_shortcode_permit', $result_permit, $shortcode_name, $table );
    
    if (!$result_permit) 
      return sprintf('<p>%s</p>', __('You can not see this content without permission.', CDBT));
    
    
    // Validation of the attributes, then sanitizing
    $boolean_atts = [ 'bootstrap_style', 'display_list_num', 'display_title', 'enable_sort', 'display_filter', 'ajax_load', 'strip_tags' ];
    foreach ($boolean_atts as $attribute_name) {
      ${$attribute_name} = $this->strtobool( rawurldecode( ${$attribute_name} ) );
    }
    $not_assoc_atts = [ 'exclude_cols' ];
    foreach ($not_assoc_atts as $attribute_name) {
      ${$attribute_name} = $this->strtoarray( rawurldecode( ${$attribute_name} ) );
    }
    $hash_atts = [ 'narrow_keyword', 'sort_order', 'filters' ];
    foreach ($hash_atts as $attribute_name) {
      ${$attribute_name} = $this->strtohash( rawurldecode( ${$attribute_name} ) );
    }
    if ( ! empty( $add_class ) ) {
      $add_classes = [];
      foreach ( explode( ' ', rawurldecode( $add_class ) ) as $_class ) {
        $add_classes[] = esc_attr( trim( $_class ) );
      }
      $add_class = implode( ' ', $add_classes );
    }
    $image_render = 'responsive';
    if ($csid > 0 && $this->validate->checkInt($csid)) {
      // Checking whether the shortcode exists that has "csid (Custom Shortcode ID)".
      $loaded_settings = $this->get_shortcode_option($csid);
      if ($loaded_settings['base_name'] === $shortcode_name && $loaded_settings['target_table'] === $table) {
        foreach ($loaded_settings as $_key => $_val) {
          if (!in_array($_key, [ 'base_name', 'target_table', 'description', 'csid', 'author', 'generate_shortcode', 'alias_code' ])) {
            ${$_key} = $_val;
          }
        }
      }
    } else {
      $csid = 0;
    }
    if ($display_title) {
      $disp_title = $this->get_table_comment($table);
      $disp_title = !empty($disp_title) ? $disp_title : $table;
      $title = '<h4 class="sub-description-title">' . sprintf( __('Edit Data of "%s" Table', CDBT), $disp_title ) . '</h4>';
    }
    
    $all_columns = array_keys($table_schema);
    if ($exclude_cols = $this->strtoarray($exclude_cols)) {
      $output_columns = [];
      foreach ($all_columns as $_col) {
        if (!in_array($_col, $exclude_cols)) 
          $output_columns[] = $_col;
      }
    }
    if (!isset($output_columns)) 
      $output_columns = $all_columns;
    
    if (!in_array($filter_column, $all_columns)) {
      $filter_column = '';
    }
    $filters = $this->strtohash($filters);
    
    // Added since version 2.0.6
    $narrow_keyword = $this->is_assoc( $narrow_keyword ) ? $narrow_keyword : $this->strtohash( $narrow_keyword );
    if ( ! $narrow_keyword ) {
      $query_type = 'get';
    } else {
      $query_type = $this->is_assoc( $narrow_keyword ) ? 'get' : 'find';
      $conditions = [];
      if ( 'get' === $query_type ) {
        foreach ( $narrow_keyword as $_col => $_keywd ) {
          if ( in_array( $_col, $all_columns ) ) 
            $conditions[$_col] = $_keywd;
        }
      } else {
        $conditions = $narrow_keyword;
      }
    }
    if ( ! isset( $conditions ) ) 
      $conditions = null;
    
    $sort_order = $this->is_assoc( $sort_order ) ? $sort_order : $this->strtohash( $sort_order );
    if ( $this->is_assoc( $sort_order ) ) {
      $orders = [];
      foreach ( $sort_order as $_col => $_order ) {
        if ( ! is_int( $_col ) && in_array( $_col, $all_columns ) ) 
          $orders[$_col] = in_array( strtolower( $_order ), [ 'asc', 'desc' ] ) ? $_order : 'asc';
      }
    }
    if ( ! isset( $orders ) || empty( $orders ) ) 
      $orders = null;
    
    if ( 'get' === $query_type ) {
      // $datasource = $this->get_data( $table, 'ARRAY_A' );
      $datasource = $this->get_data( $table, '`'.implode( '`,`', $output_columns ).'`', $conditions, $orders, 'ARRAY_A' );
    } else {
      $datasource = [];
      // Added since version 2.0.7
      $narrow_operator = strtolower( $narrow_operator );
      if ( is_array( $conditions ) && ! empty( $conditions ) ) {
        foreach ( $conditions as $_i => $_keyword ) {
          if ( 0 === $_i ) {
            $datasource = $this->find_data( $table, $_keyword, $narrow_operator, $output_columns, $orders, 'ARRAY_A' );
          } else {
            // Currently, the plurality of keywords are not supported
            /*
            $diff_datasource = $this->find_data( $table, $_keyword, $output_columns, $orders, 'ARRAY_A' );
            if ( is_array( $diff_datasource ) && is_array( $datasource ) ) {
              $datasource = array_intersect( $diff_datasource, $datasource );
              //$datasource = array_merge( $datasource, $diff_datasource );
            }
            */
            break;
          }
        }
      } else {
        $datasource = $this->find_data( $table, $conditions, $narrow_operator, $output_columns, $orders, 'ARRAY_A' );
      }
    }
    if ( empty( $datasource ) ) 
      return sprintf( '<p>%s</p>', __('No data in this table.', CDBT ) );
    
    $custom_column_renderer = [];
    
    // If contain string as char in the data source (added since version 2.0.7)
    if ( ! empty( $has_char ) ) {
      foreach ( $has_char as $column ) {
        if ( array_key_exists( $column, $datasource[0] ) ) {
          foreach ($datasource as $i => $row_data) {
            if ( $strip_tags ) {
              $datasource[$i][$column] = strip_tags( $row_data[$column] );
            } else {
              $datasource[$i][$column] = stripslashes_deep( $this->validate->esc_column_value( $row_data[$column], 'char' ) );
            }
          }
        }
      }
    }
    
    // If contain string as text in the data source (added since version 2.0.7)
    if ( ! empty( $has_text ) ) {
      foreach ( $has_text as $column ) {
        if ( array_key_exists( $column, $datasource[0] ) ) {
          foreach ($datasource as $i => $row_data) {
            if ( $strip_tags ) {
              $datasource[$i][$column] = strip_tags( $row_data[$column] );
            } else {
              $datasource[$i][$column] = stripslashes_deep( $this->validate->esc_column_value( $row_data[$column], 'text' ) );
            }
          }
        }
      }
    }
    
    // If contain binary data in the datasource
    if (!empty($has_bin)) {
      foreach ($datasource as $i => $row_data) {
        foreach ($has_bin as $col_name) {
          if (array_key_exists($col_name, $row_data)) {
            if ('image' === $this->check_binary_data($row_data[$col_name])) {
              $row_data[$col_name] = sprintf('data:%s;base64,%s', $this->esc_binary_data($row_data[$col_name], 'mime_type'), $this->esc_binary_data($row_data[$col_name], 'bin_data') );
              // $custom_row_scripts[] = sprintf( 'helpers.rowData.%s = !helpers.rowData.%s ? \'data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==\' : helpers.rowData.%s;', $col_name, $col_name, $col_name);
            } else {
              $_temp = $this->esc_binary_data( $row_data[$col_name], 'origin_file' );
              $row_data[$col_name] = !$_temp ? '' : $_temp;
            }
            $_where_conditions = [];
            if ($has_pk) {
              $_where_conditions = $pk_columns;
            }
            $_render_script_base = 'rowData[\'%s\'] !== false ? \'<div class="binary-data" data-column-name="%s" data-where-conditions="%s"><input type="hidden" data="\' + rowData[\'%s\'] + \'" data-class="img-%s"></div>\' : \'\'';
            $custom_column_renderer[$col_name] = sprintf($_render_script_base, $col_name, $col_name, implode(',', $_where_conditions), $col_name, $image_render);
            $datasource[$i] = $row_data;
          } else {
            $custom_column_renderer[$col_name] = '';
          }
        }
      }
    }
    
    // If contain list type columns
    if (!empty($has_list)) {
      $_filter_items = [];
      foreach ($has_list as $column) {
        foreach ($this->parse_list_elements($table_schema[$column]['type_format']) as $list_item) {
          $_filter_items[] = sprintf( '%s:%s', esc_attr($list_item), __($list_item, CDBT) );
        }
        if ('set' === $table_schema[$column]['type']) {
          $custom_column_renderer[$column] = '\'<ul class="list-inline">\' + convert_list(rowData[\''. $column .'\']) + \'</ul>\'';
        }
      }
      if ($display_filter && empty($filters)) {
        if (!empty($_filter_items)) 
          $filters = array_unique($_filter_items);
      }
      unset($_filter_items);
    }
    
    // If contain bit binary data in the datasource
    // @since 2.0.7 Updated
    if ( ! empty( $has_bit ) ) {
      foreach ( $has_bit as $column ) {
        if ( array_key_exists( $column, $datasource[0] ) || array_key_exists( 'BIN('. $column .')', $datasource[0] ) ) {
          
          foreach ( $datasource as $_i => $_data_row ) {
            foreach ( $_data_row as $_dcol => $_dval ) {
              if ( $column === $_dcol || 'BIN('. $column .')' === $_dcol ) {
                $datasource[$_i][$column] = $_dval;
                unset( $datasource[$_i][$_dcol] );
              } else {
                $datasource[$_i][$_dcol] = $_dval;
              }
            }
          }
          // Filter whether to use the icon display in the case of outputting the data registered in boolean form
          //
          // @since 2.0.0
          $bool_data_with_icon = apply_filters( 'cdbt_boolean_data_with_icon', true, $shortcode_name, $table );
          
          if ( $bool_data_with_icon ) {
            $custom_column_renderer[$column] = '\'<div class="center-block text-center"><small><i class="\' + (rowData[\''. $column .'\'] === \'1\' ? \'fa fa-circle-o\' : \'fa fa-time\' ) + \'"></i><span class="sr-only">\' + rowData[\''. $column .'\'] + \'</span></small></div>\'';
          } else {
            $custom_column_renderer[$column] = '\'<div class="center-block text-center">\' + (rowData[\''. $column .'\'] === \'1\' ? \'true\' : \'false\' ) + \'</div>\'';
          }
        }
      }
    }
    
    // If contain datetime data in the datasource
    if (!empty($has_datetime)) {
      foreach ($has_datetime as $column) {
        if (empty($this->options['display_datetime_format'])) {
          $_datetime_format = '[\''. get_option( 'date_format' ) .'\', \''. get_option( 'time_format' ) .'\']';
        } else {
        	$_datetime_format = '[\''. $this->options['display_datetime_format'] .'\']';
        }
        $custom_column_renderer[$column] = '\'<div class="custom-datetime">\' + convert_datetime(rowData[\''. $column .'\'], '. $_datetime_format .') + \'</div>\'';
      }
      unset($_datetime_format);
    }
    
    
    if ($bootstrap_style) {
      // Generate repeater
      $columns = [];
      foreach ($table_schema as $column => $scheme) {
        $_classes = [];
        if (!in_array($column, $output_columns)) 
          $_classes[] = 'hide';
        if (!$enable_sort) 
          $_classes[] = 'disable-sort';
        $columns[] = [
          'label' => empty($scheme['logical_name']) ? $column : $scheme['logical_name'], 
          'property' => $column, 
          'sortable' => $enable_sort, 
          'sortDirection' => 'asc', 
          'dataNumric' => $this->validate->check_column_type( $scheme['type'], 'numeric' ), 
          'truncateStrings' => $truncate_strings, 
          'className' => implode(' ', $_classes), 
        ];
      }
      
      if (isset($custom_column_renderer) && !empty($custom_column_renderer)) {
        foreach ($columns as $i => $column_definition) {
          if (array_key_exists($column_definition['property'], $custom_column_renderer)) {
            $columns[$i] = array_merge($columns[$i], [ 'customColumnRenderer' => $custom_column_renderer[$column_definition['property']] ]);
          }
        }
        unset($i);
      }
      
      // Responding to `listSelectable` for `cdbt-edit`
      $condition_keys = [];
      $disabled_edit = false;
      if ($has_pk) {
        foreach ($table_schema as $column => $scheme) {
          if ($scheme['primary_key']) {
            if ( false !== strpos( $scheme['extra'], 'auto_increment' ) ) {
              $condition_keys = [ $column ]; // Surrogate key is only one
              break;
            } else {
              $condition_keys[] = $column; // In the case of composite primary key
            }
          }
        }
      } else {
        foreach ($table_schema as $column => $scheme) {
          if ( 'UNI' === strtoupper($scheme['column_key']) ) {
            $condition_keys = [ $column ]; // If is a unique index
            break;
          } else
          if ( $scheme['not_null'] ) {
            if ( 'MUL' === strtoupper($scheme['column_key']) || 'datetime' === $scheme['type'] || 'timestamp' === $scheme['type'] ) 
              $condition_keys[] = $column; // The columns often high accuracy uniqueness
          }
        }
        if (empty($condition_keys)) {
        	foreach ($table_schema as $column => $scheme) {
        	  if (!$this->validate->check_column_type( $scheme['type'], 'blob' )) 
              $condition_keys[] = $column; // Considerably low matching
          }
        }
      }
      unset($column, $scheme);
      if (empty($condition_keys)) {
        $disabled_edit = true;
        $where_condition = '';
      } else {
        $_temp = [];
        foreach ($condition_keys as $column) {
          $_temp[] = sprintf('%s:\' + encodeURIComponent(rowData[\'%s\']) + \'', $column, $column);
        }
        $where_condition = sprintf( '<input type="hidden" class="row_where_condition" value="%s">', implode(',', $_temp) );
      }
      if (array_key_exists('customColumnRenderer', $columns[0])) {
        $_temp = is_array($columns[0]['customColumnRenderer']) ? implode("\n", $columns[0]['customColumnRenderer']) : $columns[0]['customColumnRenderer'];
        $columns[0]['customColumnRenderer'] = sprintf( '\'<div class="cdbt-repeater-left-main">\' + %s + \'</div>%s\'', $_temp, $where_condition );
      } else {
        $columns[0]['customColumnRenderer'] = sprintf( '\'<div class="cdbt-repeater-left-main">\' + rowData[\'%s\'] + \'</div>%s\'', $columns[0]['property'], $where_condition );
      }
      
      
      if ('regular' === $table_type && $display_list_num) {
        foreach ($datasource as $i => $datum) {
          $datasource[$i] = array_merge([ 'data-index-number' => $i + 1 ], $datum);
        }
        $add_column = [ 'label' => '#', 'property' => 'data-index-number', 'sortable' => true, 'sortDirection' => 'asc', 'dataNumric' => true, 'width' => 80 ];
        array_unshift($columns, $add_column);
      }
      
      // Filter the column definition of the list content that is output by this shortcode
      //
      // @since 2.0.0
      $columns = apply_filters( 'cdbt_shortcode_custom_columns', $columns, $shortcode_name, $table );
      
      $component_options = [
        'id' => 'cdbt-repeater-edit-' . $table, 
        'enableSearch' => true, 
        'enableFilter' => $display_filter, 
        'filter_column' => $filter_column, 
        'filters' => $filters, 
        'enableView' => false, 
        'defaultView' => 'list', 
        'enableEditor' => true, 
        'disableEdit' => $disabled_edit, 
        'listSelectable' => 'multi', 
        'staticHeight' => -1, 
        'pageIndex' => 1, 
        'pageSize' => $limit_items, 
        'columns' => $columns, 
        'data' => $datasource, 
        'addClass' => $add_class, 
      ];
      
      // Filter the component definition of the list content that is output by this shortcode
      //
      // @since 2.0.0
      $component_options = apply_filters( 'cdbt_shortcode_custom_component_options', $component_options, $shortcode_name, $table );
      
      if ( is_admin() ) {
        if (isset($title)) 
          echo $title;
        
        return $this->component_render('repeater', $component_options);
      } else {
        ob_start();
        if (isset($title)) 
          echo $title;
        
        echo $this->component_render('repeater', $component_options);
        
        $render_content = ob_get_contents();
        ob_end_clean();
        
        return $render_content;
      }
      
    }
    
  }
  
  
  /**
   * 
   *
   * @since 2.0.0
   *
   * @param string $table_name [require]
   * @return 
   **/
  public function submit_custom_query() {
/*
	extract(shortcode_atts(array(
		'table' => '', 
		'query' => '', 
		'type' => 'button', // or 'link'
		'label' => 'Submit', 
		'onclick' => '', // my custom onclick event name (when click on element)
		'callback' => '', // my callback function name (when ajax complete event)
		'final' => '', // my final process event name (when ending after ajax)
		'add_class' => '', 
	), $atts));
	global $cdbt;
	// verification for using shortcode
	if (empty($table) || !$cdbt->check_table_exists($table)) 
		return __('Specified table is not exist', CDBT_PLUGIN_SLUG);
	if (empty($query)) 
		return __('Specifying query is nothing', CDBT_PLUGIN_SLUG);
	if (preg_match('/^(insert|update)\s(.*)$/iU', $query, $matches)) {
		$query_action = strtolower($matches[1]);
		if ($query_action == 'insert' && !cdbt_check_current_table_role('input', $table)) 
			$err_permission = sprintf(__('You do not have a permission to %s this table', CDBT_PLUGIN_SLUG), __('input', CDBT_PLUGIN_SLUG));
		if ($query_action == 'update' && !cdbt_check_current_table_role('edit', $table)) 
			$err_permission = sprintf(__('You do not have a permission to %s this table', CDBT_PLUGIN_SLUG), __('edit', CDBT_PLUGIN_SLUG));
	} else {
		return __('Can not use your specified query', CDBT_PLUGIN_SLUG);
	}
	if (!isset($query_action)) 
		return __('Specified query is invalid', CDBT_PLUGIN_SLUG);
	
	// verification of sql query
	if ($query_action == 'insert') {
		if (preg_match('/into\s(.*)(\s|)\((.*)\)\s{1,}values(\s|)\((.*)\)\s{0,}(;|)$/iU', preg_replace('/(?:\n|\r|\r\n)/', '', trim($matches[2])), $parse_query)) {
			$query_elms = array();
			$query_elms['table_name'] = ($parse_query[1] == '@' || $parse_query[1] != $table) ? $table : trim($parse_query[1]);
			$query_elms['columns'] = explode(',', trim($parse_query[3]));
			$tmp_values = explode(',', trim($parse_query[5]));
			$query_elms['values'] = array();
			foreach ($query_elms['columns'] as $i => $col) {
				$query_elms['values'][] = $tmp_values[$i];
			}
			$prepared_query = sprintf('INSERT INTO `%s` (%s) VALUES (%s);', $query_elms['table_name'], implode(',', $query_elms['columns']), implode(',', $query_elms['values']));
		} else {
			return __('Specified query is invalid', CDBT_PLUGIN_SLUG);
		}
	}
	if ($query_action == 'update') {
		if (preg_match('/(.*)\s{1,}set\s{1,}(.*)(where\s{1,}(.*)|)(;|)$/iU', preg_replace('/(?:\n|\r|\r\n)/', '', trim($matches[2])), $parse_query)) {
			$query_elms = array();
			$query_elms['table_name'] = ($parse_query[1] == '@' || $parse_query[1] != $table) ? $table : trim($parse_query[1]);
			$tmp_sets = explode(',', trim($parse_query[2]));
			$query_elms['columns'] = $query_elms['values'] = $query_elms['set_clause'] = array();
			foreach ($tmp_sets as $val) {
				list($str_col, $str_val) = explode('=', trim($val));
				$query_elms['columns'][] = trim($str_col);
				$query_elms['values'][] = trim($str_val);
				$query_elms['set_clause'][] = trim($str_col) .' = '. trim($str_val);
			}
			$query_elms['where'] = trim($parse_query[4]);
			if (empty($query_elms['where'])) {
				$prepared_query = sprintf('UPDATE `%s` SET %s;', $query_elms['table_name'], implode(', ', $query_elms['set_clause']));
			} else {
				$prepared_query = sprintf('UPDATE `%s` SET %s WHERE %s;', $query_elms['table_name'], implode(', ', $query_elms['set_clause']), $query_elms['where']);
			}
			
		} else {
			return __('Specified query is invalid', CDBT_PLUGIN_SLUG);
		}
	}
	if (empty($prepared_query)) 
		return __('Specified query is invalid', CDBT_PLUGIN_SLUG);
	
	add_action('wp_footer', 'cdbt_create_javascript', 9999);
	
	// create content for rendering at HTML
	$hash_id = md5($prepared_query);
	if (get_option(CDBT_PLUGIN_SLUG . '_stored_queries') !== false) {
		$stored_queries = get_option(CDBT_PLUGIN_SLUG . '_stored_queries');
		$stored_queries[$hash_id] = $prepared_query;
		update_option(CDBT_PLUGIN_SLUG . '_stored_queries', $stored_queries);
	} else {
		add_option(CDBT_PLUGIN_SLUG . '_stored_queries', array($hash_id => $prepared_query), '', 'no');
	}
	$template_content = $type == 'link' ? '<a href="#" id="%s" class="%s" %s>%s</a>' : '<button type="button" id="%s" class="btn %s" %s>%s</button>';
	$attributes = array();
	if (isset($err_permission) && !empty($err_permission)) {
		$content_id = "cdbt-submit";
		$attributes[] = sprintf('title="%s"', $err_permission);
		$attributes[] = 'disabled="disabled"';
	} else {
		$content_id = "cdbt-submit-{$hash_id}";
		if (!empty($onclick)) 
			$attributes[] = sprintf('data-onclick="%s"', $onclick);
		if (!empty($callback)) 
			$attributes[] = sprintf('data-callback="%s"', $callback);
		if (!empty($final)) 
			$attributes[] = sprintf('data-final="%s"', $final);
	}
	$add_class = ($type != 'link' && empty($add_class)) ? 'btn-primary' : $add_class;
	$render_content = sprintf($template_content, $content_id, $add_class, implode(' ', $attributes), $label);
	
	return $render_content;
*/
  }
  
  

}