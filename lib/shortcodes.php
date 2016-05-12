<?php

namespace CustomDataBaseTables\Lib;


/**
 * Trait of shortcode difinitions for this plugin 
 *
 * @since 2.0.0
 * @since 2.1.0 Refactored
 *
 */
trait CdbtShortcodes {
  
  private $shortcodes;
  
  /**
   * Import traits for shortcode
   * (Required php version 5.4 more)
   *
   * @since 2.1.0
   */
  use CdbtView;
  use CdbtEntry;
  use CdbtEdit;
  //use CdbtSubmit;
  
  /**
   * Register the built-in shortcodes
   *
   * @since 2.0.0
   **/
  protected function shortcode_register() {
    
    $this->shortcodes = [
      'cdbt-view' => [
        'method' => 'view_data_list', 
        'description' => __('Retrieve data that match the specified conditions from the table, then it outputs as list.', CDBT), 
        'type' => 'built-in', 
        'author' => 0, 
        'permission' => implode(', ', $this->convert_cap_level(0)), 
        'alias_id' => null, 
      ],
      'cdbt-entry' => [
        'method' => 'entry_data_form', 
        'description' => __('Outputs the data registration forms of the specified table.', CDBT), 
        'type' => 'built-in', 
        'author' => 0, 
        'permission' => implode(', ', $this->convert_cap_level(1)), 
        'alias_id' => null, 
      ],
      'cdbt-edit' => [
        'method' => 'editable_data_list', 
        'description' => __('Outputs the editable data list of the specified table.', CDBT), 
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
/*
      'cdbt-submit' => [
        'method' => 'submit_custom_query', 
        'description' => __('Deprecated since version 2.', CDBT), 
        'type' => 'deprecated', 
        'author' => 0, 
        'permission' => implode(', ', $this->convert_cap_level(9)), 
        'alias_id' => null, 
      ],
*/
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
          $_desc = __('This shortcode is invalid because the specified table does not exist.', CDBT);
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
  
  
}