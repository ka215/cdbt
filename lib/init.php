<?php

namespace CustomDataBaseTables\Lib;

if ( !defined( 'CDBT' ) ) exit;

/**
 * Instance factory for CustomDataBaseTables plugin
 *
 * @since v2.0.0
 */
function factory( $type='set_global' ) {
  if (is_admin()) {
    $call_class = __NAMESPACE__ . '\CdbtAdmin';
  } else {
    $call_class = __NAMESPACE__ . '\CdbtFrontend';
  }
  
  if (isset($type) && $type != 'set_global' ) {
    
    return $call_class::instance();
    
  } else {
    
    global $cdbt;
    $cdbt = $call_class::instance();
    
  }
  
}
