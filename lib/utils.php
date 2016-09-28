<?php

namespace CustomDataBaseTables\Lib;


if ( !class_exists( 'CdbtUtility' ) ) :
/**
 * Utility functions class for plugins
 *
 * @since CustomDataBaseTables v2.0.0
 */
class CdbtUtility {

  /**
   * Stored the last message at the time of logger method call as a cache
   */
  protected $logger_cache;
  
  public function __construct() {
    
    $this->setup_globals();
    
  }

  public function __destruct() { return true; }

  private function setup_globals() {
    global $cdbt;
    if (!isset($cdbt)) 
      $cdbt = $this;
    
  }


  /**
   * Logger for this plugin
   *
   * @since 2.0.0
   *
   * @param string $message
   * @param integer $logging_type 0: php system logger, 1: mail to $distination, 3: overwriting file of $distination (default), 4: to SAPI handler
   * @param string $distination
   * @return boolean
   */
  public function logger( $message='', $logging_type=3, $distination='' ) {
    if ( !defined( 'CDBT' ) ) 
      return;
    
    $options = get_option( CDBT );
    $this->logger_cache = $message;
    
    if (!$options['debug_mode']) 
      return;
    
    if ( ! isset( $this->errors ) || is_object($this->errors) ) 
      $this->errors = new \WP_Error();
    
    if (empty($message) || '' === trim($message)) {
      $message = $this->errors->get_error_message();
      if (!is_wp_error($this->errors) || empty($message)) 
        return;
      
      $message = apply_filters( 'cdbt_log_message', $message, $this->errors );
    }
    
    if (!in_array(intval($logging_type), [ 0, 1, 3, 4 ])) 
      $logging_type = 3;
    
    $current_datetime = date('Y-m-d H:i:s', time());
    $message = preg_replace( '/(?:\n|\r|\r\n)/', ' ', trim($message) );
    $log_message = sprintf("[%s] %s\n", $current_datetime, $message);
    
    if (3 == intval($logging_type)) {
      $this->log_distination_path = empty($message) || '' === trim($distination) ? str_replace('lib/', 'debug.log', plugin_dir_path(__FILE__)) : $distination;
      $this->log_distination_path = apply_filters( 'cdbt_log_distination_path', $this->log_distination_path );
    }
    
    if (false === error_log( $log_message, $logging_type, $this->log_distination_path )) {
      $this->errors = new \WP_Error();
      $this->errors->add( 'logging_error', __('Failed to export the log.', $this->domain_name) );
      return false;
    } else {
      return true;
    }
  }


  /** 
   * Converts bytes into human readable file size. 
   *
   * @since 2.0.0
   *
   * @author Mogilev Arseny 
   *
   * @param mixed $bytes [require] Numeric string as integer
   * @return string human readable file size
   */ 
  public function convert_filesize( $bytes=0 ) {
    $bytes = floatval($bytes);
    
    $arBytes = [
      [ 'unit' => 'TB', 'value' => pow(1024, 4) ], 
      [ 'unit' => 'GB', 'value' => pow(1024, 3) ], 
      [ 'unit' => 'MB', 'value' => pow(1024, 2) ], 
      [ 'unit' => 'KB', 'value' => 1024 ], 
      [ 'unit' => 'B',   'value' => 1 ], 
    ];
    
    foreach ( $arBytes as $arItem ) {
      if ( $bytes >= $arItem['value'] ) {
        $result = $bytes / $arItem['value'];
        $result = strval(round($result, 2)).' '.$arItem['unit'];
        break;
      }
    }
    return $result;
    
  }
  
  
  /**
   * Retrieve array as context string of the binary file
   *
   * @since 2.0.0
   * @since 2.0.7 Changed to convert binary data via unpack
   *
   * @param string $file_path [require] For example is temporarily file path in the `$_FILES` etc.
   * @param string $file_name [optional] Origin file name in the `$_FILES`
   * @param string $file_type [optional] Mime type in the `$_FILES`
   * @param integer $file_size [optional] File size in the `$_FILES`
   * @param boolean $add_hash [optional] For default is true
   * @return mixed Array or null
   */
  public function get_binary_context( $file_path=null, $file_name=null, $file_type=null, $file_size=0, $add_hash=true ) {
    if ( empty( $file_path ) ) 
      return null;
    
    $_raw_data = @file_get_contents( $file_path );
    if ( $_raw_data ) {
      $bin_context['bin_data'] = unpack( "H*", $_raw_data );
    } else {
      return null;
    }
    
    if ( $bin_context['bin_data'] ) {
      
      if ( ! empty( $file_name ) ) {
        $bin_context['origin_file'] = rawurlencode( $file_name );
      } else {
        $bin_context['origin_file'] = rawurlencode( basename( $file_path ) );
      }
      
      if ( ! empty( $file_type ) ) 
        $bin_context['mime_type'] = $file_type;
      
      if ( ! empty( $file_size ) && $file_size > 0 ) {
        $bin_context['file_size'] = $file_size;
      } else {
        $bin_context['file_size'] = filesize( $file_path );
      }
      
      if ( $add_hash ) 
        $bin_context['hash'] = md5( $_raw_data );
      
      return serialize( $bin_context );
    }
    
    return null;
  }
  
  
  /**
   * Escape of binary data as the string of serialized array
   *
   * @since 2.0.0
   * @since 2.0.7 Changed to convert binary data via pack
   *
   * @param string $binary_context [require] Serialized array string is generated by the `get_binary_context()`
   * @param string $get_attribute [optional] Attribute name of binary context or `array`
   * @return mixed Array or string or false. Also binary string is returned in the base64-encoded.
   */
  public function esc_binary_data( $binary_context=null, $get_attribute=null ) {
    if ( empty( $binary_context ) ) 
      return false;
    
    $binary_array = unserialize( $binary_context );
    if ( array_key_exists( 'bin_data', $binary_array ) ) {
      $binary_array['bin_data'] = base64_encode( pack( "H*", $binary_array['bin_data'][1] ) );
    }
    
    $get_attribute = empty( $get_attribute ) ? 'array' : $get_attribute;
    if ( array_key_exists( $get_attribute, $binary_array ) ) {
      return $binary_array[$get_attribute];
    } else
    if ( 'array' === $get_attribute ) {
      return $binary_array;
    } else {
      return false;
    }
    
  }
  
  
  /**
   * Check of binary data as the string of serialized array
   *
   * @since 2.0.0
   *
   * @param string $binary_context [require] Serialized array string is generated by the `get_binary_context()`
   * @param boolean $return_mime_type [optional] Return raw MIME Type only if true, otherwise false as default
   * @return mixed String or boolean.
   */
  public function check_binary_data( $binary_context=null, $return_mime_type=false ) {
    if ( empty( $binary_context ) ) 
      return false;
    
    $binary_array = unserialize( stripslashes( $binary_context ));
    if ( ! array_key_exists( 'mime_type', $binary_array ) ) {
      return 'unknown';
    }
    
    if ( $return_mime_type ) 
      return $binary_array['mime_type'];
    
    list( $format, $detail ) = explode( '/', $binary_array['mime_type'] );
    if ( 'application' !== $format ) 
      return $format;
    
    if ( preg_match( '/^(msword|onenote|vnd.ms-|vnd.openxmlformats-)(|.*)$/iU', $detail ) ) {
      return 'ms_office';
    } else
    if ( preg_match( '/^vnd.oasis\..*$/iU', $detail ) ) {
      return 'open_office';
    } else
    if ( preg_match( '/^vnd.apple\..*$/iU', $detail ) ) {
      return 'i_work';
    } else
    if ( 'wordperfect' === $detail ) {
      return 'word_perfect';
    } else {
      return 'unknown';
    }
    
    return false;
    
  }
  
  
  /**
   * This process download the file when exporting table data.
   *
   * @since 2.0.0
   * @since 2.1.33 Added action hooks
   *
   * @param array $data_sources [require]
   * @return boolean
   */
  public function download_file( $data_sources ) {
    static $message = '';
    $notice_class = CDBT . '-error';
    
    $add_index_line = isset( $data_sources['add_index_line'] ) && ! empty( $data_sources['add_index_line'] ) ? true : false;
    $output_encoding = isset( $data_sources['output_encoding'] ) && ! empty( $data_sources['output_encoding'] ) ? $data_sources['output_encoding'] : '';
    if ( empty( $output_encoding ) && function_exists( 'mb_internal_encoding' ) ) 
      $output_encoding = mb_internal_encoding();
    
    $file_name = sprintf( '%s.%s', $data_sources['export_table'], $data_sources['export_filetype'] );
    if ( 'sql' !== $data_sources['export_filetype'] ) {
      $current_memory = floor( memory_get_usage() / 1024 );
      $table_size = $this->get_table_size( $data_sources['export_table'] );
      $divided = ceil( $table_size / $current_memory );
      $table_rows = $this->get_table_rows( $data_sources['export_table'] );
      // Action hook of just before running the sql to export table
      // 
      // @since 2.1.33
      do_action( 'cdbt_before_export_table', $data_sources );
      if ( $divided > 1 ) {
        $raw_data = [];
        $_limit = ceil( $table_rows / $divided );
        for ( $_i = 0; $_i < $divided; $_i++ ) {
          $_offset = $_limit * $_i;
          $raw_data = array_merge( $raw_data, $this->get_data( $data_sources['export_table'], $data_sources['export_columns'], null, 'and', null, $_limit, $_offset ) );
        }
        // It maybe should output to temporary file from divide data.
        // There is not performed yet.
      } else {
        $raw_data = $this->get_data( $data_sources['export_table'], $data_sources['export_columns'] );
      }
    }
    
    $download_ready = true;
    switch ( $data_sources['export_filetype'] ) {
      case 'csv': 
      case 'tsv': 
        $raw_array = json_decode(json_encode($raw_data), true);
        $escaped_data = [];
        $current_encoding = [];
        if ($add_index_line) {
          $escaped_data[] = '"' . implode('","', $data_sources['export_columns']) . '"';
        }
        foreach ($raw_array as $raw_row) {
          $escaped_row = $this->esc_xsv($raw_row, $data_sources['export_filetype']);
          if (function_exists('mb_detect_encoding')) 
            $current_encoding[] = mb_detect_encoding($escaped_row);
          $escaped_data[] = $escaped_row;
        }
        if (!empty($output_encoding) && function_exists('mb_convert_variables')) {
          mb_convert_variables($output_encoding, implode(',', array_unique($current_encoding)), $escaped_data);
        }
        $output_data = implode("\n", $escaped_data);
        $file_size = strlen($output_data);
        $file_content_type = 'csv' === $data_sources['export_filetype'] ? 'text/comma-separated-values' : 'text/tab-separated-values';
        
        break;
      case 'json': 
        $json_data = json_encode($raw_data);
        $current_encoding = function_exists('mb_detect_encoding') ? mb_detect_encoding($json_data) : 'UTF-8';
        if (!empty($output_encoding) && function_exists('mb_convert_encoding')) {
          $output_data = mb_convert_encoding($json_data, $output_encoding, $current_encoding);
        } else {
        	$output_data = $json_data;
        }
        $file_size = strlen($output_data);
        $file_content_type = 'application/json';
        
        break;
      case 'sql': 
        $sql_text = $this->dump_table( $data_sources['export_table'], $data_sources['export_columns'], false );
        $current_encoding = function_exists('mb_detect_encoding') ? mb_detect_encoding($sql_text) : 'UTF-8';
        if (!empty($output_encoding) && function_exists('mb_convert_encoding')) {
          $output_data = mb_convert_encoding($sql_text, $output_encoding, $current_encoding);
        } else {
        	$output_data = $sql_text;
        }
        $file_size = strlen($output_data);
        $file_content_type = 'text/x-sql';
        
        break;
      default:
        $download_ready = false;
        
        break;
    }
    // Action hook of after exporting table data
    // 
    // @since 2.1.33
    do_action( 'cdbt_after_export_table', $download_ready );
    
    if ( $download_ready ) {
      try {
//        header( 'Content-Type: application/octet-stream' );
        header( 'Content-Type: ' . $file_content_type );
        header( 'Content-Disposition: attachment; filename=' . $file_name );
        header( 'Content-Length: ' . $file_size );
        $fp = fopen('php://output', 'w');
        fwrite($fp, $output_data);
        fclose($fp);
        
        $download_result = true;
        $notice_class = CDBT . '-notice';
        $message = __('Exported successfully the data of table.', CDBT);
        
      } catch(Exception $e) {
        
        $download_result = false;
        $message = __('Failed to export the data.', CDBT);
        
      }
    } else {
      $download_result = false;
      $message = __('Failed to export owing not to generate the download file.', CDBT);
    }
    // Action hook of finally process of exporting as after download
    // 
    // @since 2.1.33
    do_action( 'cdbt_final_export_table', $download_ready );
    
    $this->logger( $message );
    $this->download_result = $download_result;
    $this->download_message = $message;
    return $download_result;
    
  }
  
  
  /**
   * Download specific binary data in the table via shortcode
   *
   * @since 2.0.0
   * @since 2.0.7 Changed to convert binary data via pack
   *
   * @param string $table_name [require]
   * @param string $target_column [require]
   * @param array $where_conditions [require]
   * @return mixed Return of void if it will be successfully, otherwise boolean false
   */
  public function download_binary( $table_name=null, $target_column=null, $where_conditions=[] ) {
    if ( empty( $table_name ) || empty( $target_column ) || empty( $where_conditions ) || ! is_array( $where_conditions ) ) 
      return false;
    
    $result = $this->array_flatten( $this->get_data( $table_name, $target_column, $where_conditions, null, 1, ARRAY_A ) );
    if ( array_key_exists( $target_column, $result ) ) {
      $binary_data = unserialize( $result[$target_column] );
    }
    
    if ( isset( $binary_data['bin_data'] ) && ! empty( $binary_data['bin_data'] ) ) {
      try {
        header( 'Content-Type: ' . $binary_data['mime_type'] );
        header( 'Content-Disposition: attachment; filename="' . rawurldecode( $binary_data['origin_file'] ) . '"' );
        header( 'Content-Length: ' . $binary_data['file_size'] );
        
        $fp = fopen( 'php://output', 'w' );
        fwrite( $fp, pack( "H*", $binary_data['bin_data'] ) );
        fclose( $fp );
//        $download_content = ob_get_contents();
//        ob_end_clean();
        //echo str_replace(["\r", "\n"], '', $binary_data['bin_data']);
//        wp_die($binary_data['bin_data']);
        $download_result = true;
        $message = __( 'Download Now', CDBT );
        
      } catch( Exception $e ) {
        $download_result = false;
        $message = __( 'Failed to download', CDBT );
      }
    } else {
      $download_result = false;
      $message = __( 'Not Download', CDBT );
    }
    
    $this->logger( $message );
    wp_die( $message );
    
  }
  
  
  /**
   * Convert to the array of uploading file of the CSV or TSV
   *
   * @since 2.0.0
   *
   * @param string $file_path [require] Temporary file path of the submitted file as $_FILES
   * @param string $file_type [require] `csv` or `tsv`
   * @return array (no assoc)
   */
  public function xsvtoarray( $file_path=null, $file_type='csv' ) {
    static $return_array = [];
    
    if (empty($file_path) || !file_exists($file_path) || !in_array($file_type, [ 'csv', 'tsv' ])) 
      return $return_array;
    
    if (false === ($fh = fopen($file_path, 'r'))) 
      return $return_array;
    
    $_delimiter = $file_type === 'csv' ? ',' : "\t";
    $_enclosure = '"';
    $_escape = '\\';
    
    while (false !== ($_buff = fgetcsv($fh, 0, $_delimiter, $_enclosure, $_escape))) {
      $return_array[] = $_buff;
    }
    
    fclose($fh);
    
    return $return_array;
  }
  
  
  /**
   * Escape an array as a single line string for CSV or TSV
   *
   * @since 2.0.0
   *
   * @param array $base_array [require]
   * @param string $file_type [require] `csv` or `tsv`
   * @return string Escaped row string
   */
  public function esc_xsv( $base_array, $file_type='csv' ) {
    if (!is_array($base_array)) 
      return;
    
    $escaped_array = [];
    foreach ($base_array as $k => $v) {
      $v = str_replace('"', '""', $v);
      $v = str_replace(',', '","', $v);
      $v = str_replace("\t", chr(9), $v);
      $v = str_replace(["\r\n", "\r", "\n"], chr(10), $v);
      $escaped_array[$k] = $v;
    }
    
    $separator = 'csv' === $file_type ? '","' : '"' . chr(9) . '"';
    return '"' . implode($separator, $escaped_array) . '"';
    
  }
  
  
  /**
   * User permission checker
   *
   * @since 2.0.0
   *
   * @param mixed $compare_caproles [require] Array of capabilities and roles or string
   * @return boolean
   */
  public function is_permit_user( $compare_caproles=[] ) {
    if (empty($compare_caproles)) 
      return false;
    
    if (!is_array($compare_caproles)) 
      $compare_caproles = (array)$compare_caproles;
    
    if (in_array('guest', $compare_caproles)) 
      return true;
    
    if (!is_user_logged_in()) 
      return false;
    
    $current_user = wp_get_current_user();
    $current_user_capabilities = array_keys($current_user->caps);
    if (empty($current_user_capabilities)) 
      return false;
    
    if (in_array('cdbt_operate_plugin', $current_user_capabilities)) 
      return true;
    
    $has_caproles = [];
    foreach ($current_user_capabilities as $role_name) {
      $_temp = get_role($role_name);
      if (is_object($_temp)) {
        foreach ($_temp->capabilities as $cap => $v) {
          if ($v) $has_caproles[] = $cap;
        }
      }
    }
    
    $must_caproles = [];
    foreach ($compare_caproles as $role_name) {
      $_temp = get_role($role_name);
      if (is_object($_temp)) {
        foreach ($_temp->capabilities as $cap => $v) {
          if ($v) $must_caproles[] = $cap;
        }
      }
    }
    
    $result_array = array_diff($must_caproles, $has_caproles);
    return empty($result_array);
    
  }
  
  
  /**
   * Convert to array from string like array
   *
   * @since 2.0.0
   *
   * @param string $string [require]
   * @return mixed Return array if conversion success, False otherwise
   */
  public function strtoarray( $string=null ) {
    if (empty($string)) 
      return false;
    
    if (is_array($string)) 
      return $string;
    
    $_ary = explode(',', trim($string));
    $fixed_ary = [];
    foreach ($_ary as $_val) {
      $_val = trim($_val);
      if (empty($_val) || 0 === strlen($_val)) {
        continue;
      } else {
        $fixed_ary[] = $_val;
      }
    }
    
    if (!empty($fixed_ary)) 
      return $fixed_ary;
    
    return false;
    
  }
  
  
  /**
   * Convert to object or array from string like hash
   *
   * @since 2.0.0
   * @since 2.1.33 Updated
   *
   * @param string $string [require]
   * @param string $var_type [optional] Whether return value is at assoc array or object. For default is `array`
   * @return mixed Return specified variables if conversion success, False otherwise
   */
  public function strtohash( $string=null, $var_type='array' ) {
    if ( empty( $string ) || ! in_array( strtolower( $var_type ), [ 'array', 'object' ] ) ) 
      return false;
    
    if ( ! ( $_ary = $this->strtoarray( $string ) ) ) 
      return false;
    
    $_assoc = [];
    foreach ( $_ary as $_row ) {
      if ( strpos( $_row, ':' ) !== false ) {
        list( $_key, $_val ) = preg_split( '/(?<!\\\):/im', $_row ); //explode( ':', $_row );
        $_key = trim( trim( stripcslashes( trim( $_key ) ), "\"' " ) );
        $_val = trim( trim( stripcslashes( trim( $_val ) ), "\"' " ) );
        if ( ! empty( $_key ) && strlen( $_key ) > 0 ) {
          if ( array_key_exists( $_key, $_assoc ) ) {
            if ( is_array( $_assoc[$_key] ) ) {
              $_assoc[$_key][] = $_val;
            } else {
              $_assoc[$_key] = array_merge( ( array )$_assoc[$_key], [ $_val ] );
            }
          } else {
            $_assoc[$_key] = $_val;
          }
        }
      } else {
        $_row = trim( trim( stripcslashes( trim( $_row ) ), "\"' " ) );
        $_assoc[] = $_row;
      }
    }
    
    if ( empty( $_assoc ) ) 
      return false;
    
    if ( 'object' === strtolower( $var_type ) ) 
      return ( object )$_assoc;
    
    return $_assoc;
    
  }
  
  
  /**
   * Convert to boolean from string like boolean
   *
   * @since 2.0.0
   *
   * @param string $string [required]
   * @return boolean
   */
  public function strtobool( $string=null ) {
    $_boolstr = strval( $string );
    if ( empty( $_boolstr ) || ! in_array( strtolower( $_boolstr ), [ 'true', 'false', '1', '0', 1, 0 ] ) ) 
      return false;
    
    return in_array( strtolower( $_boolstr ), [ 'true', '1', 1 ] );
  }
  
  /**
   * Flatten the array or object that has nested
   *
   * @since 2.0.0
   *
   * @param mixed $data Array or Object
   * @param boolean $return_array Return an array if `true`, otherwise an object
   * @return mixed $data Array or Object specified by `return_array`
   */
  public function array_flatten( $data, $return_array=true ) {
    if ( is_object( $data ) ) 
      $data = json_decode( json_encode( $data ), true );
    
    if ( is_array( $data ) ) {
      if ( ! $this->is_assoc( $data ) )
        $data = array_reduce( $data, 'array_merge', [] );
    }
    
    return $return_array ? (array) $data : (object) $data;
  }
  
  /**
   * Whether the associative array or not
   *
   * @since 2.0.0
   *
   * @param array $data [required] This argument have to expect an array
   * @param bool $multidimensional [optional] True if want to contain a multidimensional array to associative array, the default value is false
   * @return boolean
   */
  public function is_assoc( $data, $multidimensional=false ) {
    if ( ! is_array( $data ) || empty( $data ) ) 
      return false;
    
    $has_array = false;
    foreach ( $data as $key => $value ) {
      if ( is_array( $value ) ) 
        $has_array = true;
      
      if ( ! is_int( $key ) ) 
        return true;
    }
    
    return $multidimensional && $has_array ? true : false;
   
  }
  
  
  /**
   * Filter the gettext function
   *
   * @since 2.0.9
   * @since 2.0.11 Fixed for hash is false
   *
   * @param string $translated_text
   * @param string $text
   * @param string $domain
   * @return string $translated_message
   */
  public function cdbt_gettext_messages( $translated_text, $text, $domain ) {
    if ( $domain === $this->domain_name ) {
      $msg_hash = $this->create_hash( $text );
      if ( $msg_hash && array_key_exists( $msg_hash, $this->options['override_messages'] ) ) {
        $translated_text = $this->cdbt_strarc( $this->options['override_messages'][$msg_hash], 'decode' );
      }
    }
    return $translated_text;
  }
  
  
  /**
   * Create short hash (URL Safe)
   *
   * @since 2.0.9
   *
   * @param mixed $data [required]
   * @param string $algorithm [optional] Default is `crc32`etc
   * @return string $hash
   */
  public function create_hash( $data, $algorithm='crc32' ) {
    if ( empty( $data ) ) 
      return false;
    
    if ( empty( $algorithm ) ) 
      $algorithm = 'crc32';
    
    return strtr( rtrim( base64_encode( pack( 'H*', hash( $algorithm, $data ) ) ), '=' ), '+/', '-_' );
  }
  
  
  /**
   * Do compression encoding or uncompression decoding
   *
   * @since 2.0.9
   *
   * @param string $data [required]
   * @param string $method [optional] Default is `encode`; Or `decode`
   * @param string $lib [optional] Default is `deflate`; Or `zlib`
   * @return string
   */
  public function cdbt_strarc( $data, $method='encode', $lib='deflate' ) {
    if ( empty( $data ) || ! is_string( $data ) ) 
      return false;
    
    $method = in_array( strtolower( $method ), [ 'encode', 'decode' ] ) ? strtolower( $method ) : 'encode';
    $lib = in_array( strtolower( $lib ), [ 'deflate', 'zlib' ] ) ? strtolower( $lib ) : 'deflate';
    
    if ( 'encode' === $method ) {
      $data = 'deflate' === $lib ? gzdeflate( $data, 9 ) : gzcompress( $data, 9 );
      $data = base64_encode( $data );
    } else {
      $data = base64_decode( $data );
      $data = 'deflate' === $lib ? gzinflate( $data ) : gzuncompress( $data );
    }
    
    return $data;
  }
  
  
}

endif; // end of class_exists()