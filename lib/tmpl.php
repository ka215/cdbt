<?php

namespace CustomDataBaseTables\Lib;


/**
 * Trait for creating dynamic any html templates
 *
 * @since 2.0.0
 *
 */
trait DynamicTemplate {
  
  private $template_file_path;
  
  private $component_options;
  
  /**
   * For this methods that render each template is dynamically generated as a closure.
   *
   * @since 2.0.0
   */
  public function set_template_file_path( $template_file_path ) {
    
    $this->template_file_path = $template_file_path;
    
  }
  
  /**
   * Dynamically method for rendering any components in html page
   *
   * @since 2.0.0
   */
  public function component_render( $component_name, $options=null ) {
    $template_file_name = sprintf('cdbt_%s.php', $component_name);
    
    $template_file_path = sprintf('%s%s/components/%s', $this->plugin_dir, $this->plugin_templates_dir, $template_file_name);
    
    if (!file_exists($template_file_path)) 
      return;
    
    $this->component_options = empty($options) ? [] : (array) $options;
    
    $component_render_method = 'render_' . $component_name;
    $this->set_template_file_path( apply_filters( 'include_template-' . $component_name, $template_file_path ) );
    // Define Dynamic Closure
    $this->$component_render_method = function(){ require( $this->template_file_path ); };
    $this->$component_render_method();
    
  }
  
}
