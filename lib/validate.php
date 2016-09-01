<?php

namespace CustomDataBaseTables\Lib;


/**
 * CommonValidator
 * Check as strings, numbers, characters
 *
 * By Hikaru Tooyama@vexus2
 * URL: https://gist.github.com/3869601.git
 */
class CommonValidator
{
    /**
     * Format array of datetime
     */
    private static $DATE_FORMAT_ARRAY = array(
        self::DATE_FORMAT_YMD => self::NEW_DATE_FORMAT_YMD,
        self::DATE_FORMAT_YMDHIS => self::NEW_DATE_FORMAT_YMDHIS,
        self::DATE_FORMAT_YMD_H_I_S => self::NEW_DATE_FORMAT_YMD_H_I_S,
        self::DATE_FORMAT_Y_M_D => self::NEW_DATE_FORMAT_Y_M_D,
        self::DATE_FORMAT_Y_M_D_H_I_S => self::NEW_DATE_FORMAT_Y_M_D_H_I_S
    );

    /**
     * Format of datetime
     */
    const NEW_DATE_FORMAT_YMD = 'Ymd';
    const NEW_DATE_FORMAT_Y_M_D = 'Y-m-d';
    const NEW_DATE_FORMAT_YMDHIS = 'YmdHis';
    const NEW_DATE_FORMAT_YMD_H_I_S = 'Ymd H:i:s';
    const NEW_DATE_FORMAT_Y_M_D_H_I_S = 'Y-m-d H:i:s';

    const DATE_FORMAT_YMD = 'Ymd';
    const DATE_FORMAT_Y_M_D = 'Y-m-d';
    const DATE_FORMAT_YMDHIS = 'YmdHis';
    const DATE_FORMAT_YMD_H_I_S = 'Ymd H:i:s';
    const DATE_FORMAT_Y_M_D_H_I_S = 'Y-m-d H:i:s';


    /**
     * Limit the length of characters of the date (YYYYMMDD)
     */
    const YYYYMMDD_LENGTH = 8;


    /**
     * Alphanumeric check
     *
     * @param string $arg Checking value
     * @return bool In the case of alphanumeric characters true, false otherwise
     */
    public static function checkAlphanumeric($arg)
    {
        if(CommonValidator::checkString($arg) && preg_match('/^[a-zA-Z0-9]+$/', $arg)){
            return true;
        } else {
            return false;
        }
    }

    /**
     * Date format check
     * Check in the format specified If you specify the format
     * Check in the form of YYYYMMDD If you do not specify a format
     * Date does not exist is false
     *
     * @param string $arg Checking value
     * @param string $format Any format
     * @return bool In the case of a specified format date and a date that exist is true, otherwise false
     */
    public static function checkDate($arg, $format = 'Ymd')
    {
        if(isset(self::$DATE_FORMAT_ARRAY[$format])){
            $tmpFormat = self::$DATE_FORMAT_ARRAY[$format];
            if(!is_null($tmpFormat)){
                $format = $tmpFormat;
            }
        }else{
            $format = null;
        }

        if (CommonValidator::checkString($arg) && CommonValidator::checkLength($arg,1)
        || CommonValidator::checkRange($arg,1) && CommonValidator::checkString($format)) {
          try {
          	new \DateTime( $arg );
          } catch ( \Exception $e ) {
            // var_dump( \DateTimeImmutable::getLastErrors() );
            return false;
          }
          $date = new \DateTime( $arg );
          if( $date && $arg == $date->format( $format ) ){
              return true;
          } else {
              return false;
          }
        } else {
          return false;
        }
    }

    /**
     * Datetime format check
     * Check in the format specified If you specify the format
     * Check in the form of YYYYMMDDhhmmss If you do not specify a format
     * Datetime does not exist is false
     *
     * @param string $arg Checking value
     * @param string $format Any format
     * @return bool In the case of a specified format datetime and a datetime that exist is true, otherwise false
     */
    public static function checkDateTime($arg, $format = self::DATE_FORMAT_YMDHIS)
    {
        if (CommonValidator::checkDate($arg, $format)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Numeric checking
     * Check that it is all only numbers
     *
     * @param string $arg Checking value
     * @return bool All only numbers is true otherwise false
     */
    public static function checkDigit($arg)
    {
        if (CommonValidator::checkString($arg) && ctype_digit((string)$arg)) {
            return true;
        } else {
            return false;
        }

    }

    /**
     * Integer type check
     *
     * @param string $arg Checking value
     * @return bool In the case of type int string true, false otherwise
     */
    public static function checkInt($arg)
    {
        if (CommonValidator::checkString($arg) && is_numeric((string)$arg)) {
            $arg += 0;
            if (is_int($arg)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Length check of string
     * Length check of string type string
     * Specify the minimum and maximum, unlimited if you do not specify a maximum
     *
     * @param string $arg Check value (require set the string type value)
     * @return bool If it was a string length of the specified range true, false otherwise
     */
    public static function checkLength($arg, $min, $max = null)
    {
        if (is_string($arg) && CommonValidator::checkDigit($min) && mb_strlen($arg) >= $min
        && (is_null($max) || (CommonValidator::checkDigit($max) && mb_strlen($arg) <= $max))){
            return true;
        } else {
            return false;
        }
    }

    /**
     * Range check numbers
     * Range checking of integer number
     * Specify the minimum and maximum, unlimited if you do not specify a maximum
     *
     * @param int $arg Check value (require an integer number)
     * @return bool If it was a numerical value in the range specified true, false otherwise
     */
    public static function checkRange($arg, $min, $max = null)
    {
        if (CommonValidator::checkDigit($arg) && CommonValidator::checkDigit($min) && $arg >= $min
        && (is_null($max) || (CommonValidator::checkDigit($max) && $arg <= $max))){
            return true;
        } else {
            return false;
        }
    }

    /**
     * E-mail address type checking
     * Whether an email address check
     *
     * @param string $arg Checking value
     * @return bool True if it is a string of email address format, false otherwise
     */
    public static function checkMailAddress($mailAddress)
    {
        if (CommonValidator::checkString($mailAddress) && preg_match('/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/', $mailAddress)){
            return true;
        } else {
            return false;
        }
    }

    /**
     * Single-byte string type check
     * Whether single-byte string check
     *
     * @param string $arg Checking value
     * @return bool In the case of single-byte string true, false otherwise
     */
    public static function checkSingleByte($arg)
    {
        if (CommonValidator::checkString($arg) && preg_match('/^[!-~]+$/i', $arg)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * String type check
     * Check whether handleable as a string
     * Numbers is also true but array and class is false
     *
     * @param string $arg Checking value
     * @return bool True if it's the string, false otherwise
     */
    public static function checkString($arg)
    {
        if (is_string($arg) || is_numeric($arg)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * URI type checking
     * Check of included as "https://mailto:" format
     *
     * @param string $arg Checking value
     * @return bool In the case of URI type string is true, false otherwise
     */
    public static function checkUri($arg)
    {
        if (CommonValidator::checkString($arg) && preg_match(';^(https?://).+|(mailto:).+@.+;', $arg)) {
            return true;
        } else {
            return false;
        }
    }

}

/**
 * This extends class inherited a common validator for Custom DataBase Tables plugin
 *
 * @since 2.0.0
 *
 * @see CustomDataBaseTables\Lib\CommonValidator
 */
class CdbtValidator extends CommonValidator {
  
  /**
   * Instance factory method as entry point of this plugin.
   *
   * @since 2.0.0
   */
  public static function instance() {
    
    static $instance = null;
    
    if ( null === $instance ) {
      $instance = new self;
    }
    
    return $instance;
  }
  
  
  /**
   * Define magic methods as follow;
   */
  private function __construct() { /* Do nothing here */ }
  
  public function __destruct() { /* Do nothing here */ }
  
  
  /**
   * Define methods to extend added for Custom DataBase Tables plugin
   * -------------------------------------------------------------------------
   */
   
  /**
   * Validate only SQL statements to create table.
   * SQL formatting process that had been implemented in version 1.x system has been deleted.
   *
   * @since 1.0.0
   * @since 2.0.0 Have refactored logic.
   *
   * @param string $table_name [require]
   * @param string $sql For create table statements [require]
   * @param array $compare_vars For compare the setting values [optional]
   * @return mixed
   */
  public function validate_create_sql( $table_name=null, $sql=null, $compare_vars=[] ) {
    if (empty($table_name) || empty($sql)) 
      return;
    
    $org_sql = trim(preg_replace("/[\s|\r|\n|\t]+/", ' ', stripslashes($sql)));
    $reg_base = '/^(CREATE[\s]{1,}(|TEMPORARY[\s]{1,})TABLE[\s]{1,}(|IF[\s]NOT[\s]EXISTS[\s]{1,})(|`)'. $table_name .'(|`)[\s]{0,}(|\())(.*)$/iU';
    $error = '';
    
    // Check the preformat of the SQL
    if (!preg_match($reg_base, $org_sql, $matches)) {
      $error = __('There is an error in the SQL syntax. Please check the outer syntax like table name definition in particular.', CDBT);
      return $error;
    }
    
    // Check whether there is a semicolon at the end
    if (';' !== substr(trim($matches[7]), -1)) {
      $error = __('There is an error in the SQL syntax. Semicolon at the end does not exist.', CDBT);
      return $error;
    }
    
    $sql_string = trim($matches[7]);
    $main_definitions = explode(',', trim(substr($sql_string, strpos($sql_string, '(') + 1, strrpos($sql_string, ')') - 3)));
    
    // Check definition of columns
    $valid_columns = 0;
    foreach ($main_definitions as $column_definition) {
      $reg_type = '((|tiny|small|medium|big)int|float|double(| precision)|decimal|numeric|fixed|bool(|ean)|bit|(|var)char|(|tiny|medium|long)text|(|tiny|medium|long)blob|(|var)binary|enum|set|date(|time)|time(|stamp)|year)';
      // $reg_base = "/(|`)((.*)[\s]{1,}". $reg_type ."(|\(.*\))([\s]{1,}.*(COMMENT[\s]{1,}'.*'|)|)(,|\)))+/iU";
      if (preg_match('/'. $reg_type . '/i', $column_definition)) 
        $valid_columns++;
    }
    if (0 === $valid_columns) 
      return __('No valid column definition.', CDBT);
    
    // Whether or not there is an automatic additional columns
    if (!empty($compare_vars) && !empty($compare_vars['automatically_add_columns'])) {
      $chk_status = [ 'id_col'=>false, 'id_key'=>false, 'created_col'=>false, 'updated_col'=>false ];
      foreach ($main_definitions as $column_definition) {
        if (in_array('ID', $compare_vars['automatically_add_columns'])) {
          if (preg_match('/`ID` bigint\(20\) unsigned NOT NULL AUTO_INCREMENT COMMENT \'(.*)\'/iU', trim($column_definition))) 
            $chk_status['id_col'] = true;
          if (preg_match('/PRIMARY KEY \(`ID`\)/iU', trim($column_definition))) 
            $chk_status['id_key'] = true;
        } else {
          unset($chk_status['id_col']);
          unset($chk_status['id_key']);
        }
        if (in_array('created', $compare_vars['automatically_add_columns'])) {
          if (preg_match('/`created` datetime NOT NULL DEFAULT \'0000-00-00 00:00:00\' COMMENT \'(.*)\'/iU', trim($column_definition))) 
            $chk_status['created_col'] = true;
        } else {
        	unset($chk_status['created_col']);
        }
        if (in_array('updated', $compare_vars['automatically_add_columns'])) {
          if (preg_match('/`updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT \'(.*)\'/iU', trim($column_definition))) 
        	  $chk_status['updated_col'] = true;
        } else {
        	unset($chk_status['updated_col']);
        }
      }
      if (!empty($chk_status)) {
        foreach ($chk_status as $status) {
          if (!$status) 
            return __('Definition of automatic additional columns does not exist in SQL statement.', CDBT);
        }
      }
    }
    
    // Whether table option is consistent with the set value
    if (!empty($compare_vars) && ( !empty($compare_vars['table_db_engine']) || !empty($compare_vars['table_charset']) )) {
      $table_options = explode(' ', trim(substr(trim($matches[7], ';'), strrpos($sql_string, ')') + 1)));
      foreach ($table_options as $option_string) {
        if (!empty($compare_vars['table_db_engine']) && preg_match('/^ENGINE=(.*)$/iU', $option_string, $matches) && array_key_exists(1, $matches)) {
          if ($compare_vars['table_db_engine'] !== $matches[1]) 
            $error .= __('Set value of db engine is different to the value specified in the SQL statement.', CDBT);
        }
        if (!empty($compare_vars['table_charset']) && preg_match('/^CHARSET=(.*)$/iU', $option_string, $matches) && array_key_exists(1, $matches)) {
          if ($compare_vars['table_charset'] !== $matches[1]) 
            $error .= __('Set value of default charset is different to the value specified in the SQL statement.', CDBT);
        }
      }
      if (!empty($error)) 
        return $error;
    }
    
    // Validation complete
    return true;
    
  }
  
  
  /**
   * Validate only SQL statements to alter table.
   *
   * @since 1.0.0
   * @since 2.0.0 Have refactored logic.
   *
   * @param string $table_name [require]
   * @param string $sql For create table statements [require]
   * @return boolean
   */
  public function validate_alter_sql( $table_name=null, $sql=null ) {
    if (empty($table_name) || empty($sql)) 
      return;
    
    $origin_sql = trim(preg_replace("/[\s|\r|\n|\t]+/", ' ', $sql));
    $reg_base = '/^(ALTER[\s]{1,}TABLE[\s}{1,}'. $table_name .'{\s]{0,})(.*)$/iU';
    if (preg_match($reg_base, $origin_sql, $matches)) {
      // $fixed_sql = $matches[1] .' '. preg_replace('/(.*)(,|;)$/iU', '$1', trim($matches[2])) . ';';
      $result = true;
    } else {
      $result = false;
    }
    return $result;
  }
  
  
  /**
   * Detecting definition of column type in MySQL table, then checking
   *
   * @since 2.0.0
   *
   * @param string $column_type [require] Column type name retrieved by the method of `get_table_schema()`.
   * @param string $candidate_type [optional] You want to compare column type as candidate 
   * @return mixed Boolean If candidate type is specified, otherwise array of column type group name is returned.
   */
  public function check_column_type( $column_type=null, $candidate_type=null ) {
    if (empty($column_type)) 
      return false;
    
    $column_type_group = [];
    $reg_column_type = [
      'numeric' => '/((|tiny|small|medium|big)int|float|double(| precision)|real|dec(|imal)|numeric|fixed|bool(|ean)|bit)/i', 
      'integer' => '/((|tiny|small|medium|big)int|bool(|ean))/i', 
      'float' => '/(float|double(| precision)|real|dec(|imal)|numeric|fixed)/i', 
      'binary' => '/(bit)/i', 
      'char' => '/((|var|national |n)char(|acter)|(|tiny|medium|long)text|(|tiny|medium|long)blob|(|var)binary)/i', 
      'text' => '/((|tiny|medium|long)text)/i', 
      'blob' => '/((|tiny|medium|long)blob|(|var)binary)/i', 
      'list' => '/(enum|set)/i', 
      'datetime' => '/((date(|time))|(time(|stamp))|year)/i', 
    ];
    foreach ($reg_column_type as $type_group => $regx) {
      if (preg_match($regx, strtolower($column_type), $matches) && is_array($matches) && array_key_exists(1, $matches)) 
        $column_type_group[$type_group] = $column_type;
    }
    
    if (empty($column_type_group)) 
      return false;
    
    if (isset($candidate_type) && !empty($candidate_type)) {
      return array_key_exists($candidate_type, $column_type_group);
    }
    
    return $column_type_group;
    
  }
  
  
  /**
   * Escape stored value by detected column type in table
   *
   * @since 2.0.0
   * @since 2.0.7 Allowed of specifying at strings to the second argument
   *
   * @param string $raw_value [require] Raw values obtained from the database
   * @param mixed $detect_type [require] Array of column types that have been detected in `check_column_type()` or string
   * @return mixed Such as the escaped string or numeric
   */
  public function esc_column_value( $raw_value=null, $detect_type=[] ) {
    if ( is_null( $raw_value ) || empty( $detect_type )) 
      return false;
    if ( ! is_array( $detect_type ) ) 
      $detect_type = [ $detect_type => 1 ];
    
    if (array_key_exists('integer', $detect_type)) {
      $retvar = intval($raw_value);
    } else
    if (array_key_exists('float', $detect_type)) {
      $retvar = floatval($raw_value);
    } else
    if (array_key_exists('binary', $detect_type)) {
      $retvar = bindec($raw_value);
    } else
    if (array_key_exists('text', $detect_type)) {
      $retvar = esc_textarea($raw_value);
    } else
    if (array_key_exists('blob', $detect_type)) {
      $retvar = strval($raw_value);
    } else
    if (array_key_exists('char', $detect_type)) {
      $retvar = esc_html($raw_value);
    } else
    if (array_key_exists('list', $detect_type)) {
      $retvar = esc_attr($raw_value);
    } else
    if (array_key_exists('datetime', $detect_type)) {
      if ($this->checkDateTime($raw_value, 'Y-m-d H:i:s')) {
        $retvar = strval($raw_value);
      } else {
        $retvar = '';
      }
    } else {
      $retvar = false;
    }
    
    return $retvar;
  }
  
  
  /**
   * Check file mime type
   *
   * @since 2.0.0
   *
   * @param string $file_mime_type [require] Mime type of the file that you want to examine
   * @param string $candidate_type [require] File format to be compared
   * @param string $file_name [optional] Uploaded file name. Then verifies the file extension If this parameter is specified.
   * @return boolean
   */
  public function check_file_type( $file_mime_type=null, $candidate_type=null, $file_name=null ) {
    if (empty($file_mime_type) || empty($candidate_type)) 
      return false;
    
    $result = false;
    if ('csv' === $candidate_type) {
      $result = in_array($file_mime_type, [ 'text/plain', 'text/csv', 'text/comma-separated-values', 'application/csv', 'application/vnd.ms-excel', 'application/msexcel' ]);
    }
    if ('tsv' === $candidate_type) {
      $result = in_array($file_mime_type, [ 'text/plain', 'text/tsv', 'text/tab-separated-values', 'application/tsv', 'application/vnd.ms-excel', 'application/msexcel', 'application/octet-stream' ]);
    }
    if ('json' === $candidate_type) {
      $result = in_array($file_mime_type, [ 'application/json', 'text/javascript', 'application/javascript', 'application/x-javascript', 'application/octet-stream' ]);
    }
    if ('sql' === $candidate_type) {
      $result = in_array($file_mime_type, [ 'text/plain', 'text/sql', 'text/x-sql', 'application/sql', 'application/octet-stream' ]);
    }
    
    if (!empty($file_name)) {
      $_parse_file = explode('.', $file_name);
      if ($candidate_type !== end($_parse_file)) {
        $result = false;
      }
    }
    
    return $result;
    
  }
  
  

}