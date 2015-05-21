<?php

namespace CustomDataBaseTables\Lib;

trait DynamicTemplate {
  
  private $template_file_path;
  
  private $component_options;
  
  // For this methods that render each template is dynamically generated as a closure.
  public function set_template_file_path( $template_file_path ) {
    
    $this->template_file_path = $template_file_path;
    
  }
  
  
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
