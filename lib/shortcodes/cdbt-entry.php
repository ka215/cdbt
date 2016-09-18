<?php

namespace CustomDataBaseTables\Lib;

/**
 * Trait for shortcode of "cdbt-entry"
 *
 * @since 2.1.31
 *
 */
trait CdbtEntry {
  
  /**
   * for [cdbt-entry] ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
   * Renders the data registration form for a specific table.
   *
   * @since 1.0.0
   * @since 2.0.0 Refactored logic.
   *
   * @param  array  $attributes [require]  - Array in shortcode's attributes
   * @param  string $content    [optional] - Should be actually nothing
   * @return string $html_content          - The formatted content to the specific list
   **/
  public function entry_data_form() {
    list($attributes, $content) = func_get_args();
    extract( shortcode_atts([
      'table' => '', 					// @attribute string [required] Specifies the table name you want to display the data.
      'bootstrap_style' => true, 		// @attribute bool   [optional] Renders the data via the style of bootstrap if true.
      'display_title' => true, 			// @attribute bool   [optional] Displays the heading of content as a title if true.
      'hidden_cols' => '', 				// @attribute string [optional] Specifies a comma delimited the column names if you want to hide any column. Then the hidden column will be rendered as field of "hidden" type. e.g. "column1,column2,column3,..."
      'add_class' => '', 				// @attribute string [optional] Specifies a CSS class name for styling the element of listed data table. If there are multiple class, please separated by a single-byte space.
      /* The Added new attributes since v2.0.x are follows: */
      'action_url' => '', 				// @attribute string [optional] ??? Specifies the form's action string. This attribute is for an internal processing on the "cdbt-edit" shortcode only.
      'form_action' => 'entry_data', 	// @attribute string [optional] Specifies the form's action string. This attribute is for an internal processing on the "cdbt-edit" shortcode only. This attribute value is the fixed value.
      'display_submit' => true, 		// @attribute bool   [optional] Specifies whether or not the submit button should be displayed. This attribute is for an internal processing on the "cdbt-edit" shortcode only.
      'where_clause' => '', 			// @attribute string [optional] Specifies the condition to narrow down a single data as the default value of entry form. This attribute is for an internal processing on the "cdbt-edit" shortcode only. e.g. "column1:value1,column2:value2,..."
      'csid' => 0, 						// @attribute int    [optional] This is the alias number to call a custom shortcode settings that are stored in this plugin.
      /* The Added new attribute since v2.0.5 is followed: */
      'redirect_url' => '', 			// @attribute string [optional] Specifies the url to redirect after the time of insertion and the update of the data. If not specified, self page is reloaded.
      /* The Added new attribute since v2.0.11 is followed: */
      'submit_button_label' => '', 		// @attribute string [optional] Specifies the label name of button for submitting in the entry form.
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
      $table_type = in_array( $table, $this->core_tables ) ? 'wp_core' : null;
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
    } else {
      $result_permit = false;
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
    if ( $csid > 0 && $this->validate->checkInt( $csid ) ) {
      // Checks whether the shortcode has "csid (Custom Shortcode ID)" or not.
      $loaded_settings = $this->get_shortcode_option( $csid );
      if ( isset( $loaded_settings['base_name'] ) && $loaded_settings['base_name'] === $shortcode_name && $loaded_settings['target_table'] === $table ) {
        foreach ( $loaded_settings as $_key => $_val ) {
          if ( ! in_array( $_key, [ 'base_name', 'target_table', 'description', 'csid', 'author', 'generate_shortcode', 'alias_code' ] ) ) {
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
    
    // For hooking the editing form; added since 2.1.32
    $shortcode_name = ! empty( $action_url ) && 'edit_data' === $form_action ? 'cdbt-edit' : $shortcode_name;
    
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
    if (!empty($action_url)) {
      // To convert relative uri since 2.1.34
      $home_url = trailingslashit( get_home_url( '/' ) );
      $action_url = str_replace( $home_url, '/', $action_url );
      $component_options['actionUrl'] = $action_url;
    }
    if (!empty($form_action)) 
      $component_options['formAction'] = $form_action;
    if (!$display_submit) 
      $component_options['displaySubmit'] = $display_submit;
    if (!empty($where_clause) && is_array($where_clause)) 
      $component_options['whereClause'] = $where_clause;
    if ( ! empty( $redirect_url ) ) 
      $component_options['redirectUrl'] = rawurldecode( $redirect_url );
    if ( ! empty( $submit_button_label ) ) 
      $component_options['submitLabel'] = $submit_button_label;
    
    // Filter the component definition of the list content that is output by this shortcode
    //
    // @since 2.0.0
    $component_options = apply_filters( 'cdbt_shortcode_custom_component_options', $component_options, $shortcode_name, $table );
    
    /* old render
    if ( is_admin() ) {
      return $this->component_render('forms', $component_options);
    } else {
      ob_start();
      
      echo $this->component_render( 'forms', $component_options );
      
      $render_content = ob_get_contents();
      
      ob_end_clean();
      
      return $render_content;
    }
    */
    // Buffering @since 2.1.34
    ob_start();
    echo $this->component_render( 'forms', $component_options );
    $render_content = ob_get_contents();
    ob_clean();
    
    return $render_content;
    
  }
  
}