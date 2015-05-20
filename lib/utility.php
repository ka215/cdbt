<?php

namespace CustomDataBaseTables\Common;


if ( !class_exists( 'CdbtUtility' ) ) :
/**
 * Utility functions class for plugins
 *
 * @since CustomDataBaseTables v2.0.0
 */
class CdbtUtility {

  public function __construct() {
    
    $this->setup_globals();
    
  }

  public function __destruct() { /* return true; */ }

  private function setup_globals() {
    
    
    
  }


  /**
   * 
   * 
   */
  public function array_flatten( $data, $assoc=true ) {
    if (is_object($data)) 
      $data = json_decode(json_encode($data), true);
    
    if (is_array($data)) {
      if (!$this->is_assoc($data))
        $data = array_reduce($data, 'array_merge', []);
    }
    
    return $assoc ? (array) $data : (object) $data;
  }
  
  /**
   * 
   * 
   */
  public function is_assoc( &$data ) {
    reset($data);
    list($k) = each($data);
    return $k !== 0;
  }

}

endif; // end of class_exists()