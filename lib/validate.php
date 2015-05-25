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
    const NEW_DATE_FORMAT_YMD = '%Y%m%d';
    const NEW_DATE_FORMAT_Y_M_D = '%Y-%m-%d';
    const NEW_DATE_FORMAT_YMDHIS = '%Y%m%d%H%M%S';
    const NEW_DATE_FORMAT_YMD_H_I_S = '%Y%m%d %H:%M:%S';
    const NEW_DATE_FORMAT_Y_M_D_H_I_S = '%Y-%m-%d %H:%M:%S';

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
            $date = new CommonDate($arg);
            if($date && $arg == $date->format($format) && checkdate($date->getMonth(), $date->getDay(), $date->getYear())){
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
     * @return bool All only numbers is trueAotherwise false
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
    
var_dump(trim($matches[7]));
    // Whether or not there is an automatic additional columns
    if (!empty($compare_vars)) {
var_dump($compare_vars);
    }
    
    
    // parse while verification
//      $sql_head = "CREATE TABLE `{$table_name}` (";
      $is_pk_exists = false;
      $pk_store = null;
      
// $matches[1] : "CREATE TABLE `%table_name` ("
// $matches[2] : " `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT \'ID\', `created` datetime NOT NULL DEFAULT \'0000-00-00 00:00:00\' COMMENT \'Created Datetime\', `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT \'Updated Datetime\', PRIMARY KEY (`ID`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1;"
$columns = explode(',', trim($matches[2]));
$tmp = array_pop($columns);
$columns[] = substr($tmp, 0, strrpos($tmp, ')'));
$options = str_replace(end($columns), '', $tmp);
//var_dump($columns);
//var_dump($options);
	
      // column definition
      $reg_type = '((|tiny|small|medium|big)int|float|double(| precision)|decimal|numeric|fixed|bool(|ean)|bit|(|var)char|(|tiny|medium|long)text|(|tiny|medium|long)blob|(|var)binary|enum|set|date(|time)|time(|stamp)|year)';
//      $reg_base = "/(|[\s]{1,})((.*)[\s]{1,}". $reg_type ."(|\(.*\))([\s]{1,}.*(COMMENT[\s]{1,}'.*'|)|)(,|\)))+/iU";
      $reg_base = "/(|`)((.*)[\s]{1,}". $reg_type ."(|\(.*\))([\s]{1,}.*(COMMENT[\s]{1,}'.*'|)|)(,|\)))+/iU";
      
      $parse_body = array();
//      while (preg_match($reg_base, $matches[2], $one_column)) {
      foreach ($columns as $one_column) {
//        $matches[2] = str_replace($one_column[0], '', $columns);
var_dump(preg_match($reg_base, trim($one_column), $matches));
var_dump($matches);
        $column_name = str_replace('`', '', trim($one_column[3]));
        $column_type = strtolower(trim($one_column[4])) . trim($one_column[14]);
        if (preg_match("/PRIMARY\sKEY/i", $one_column[15])) {
          $is_pk_exists = true;
          if (empty($pk_store)) 
            $pk_store = $column_name;
          $column_attributes = trim(preg_replace("/PRIMARY\sKEY/i", '', $one_column[15]));
        } else {
          $column_attributes = trim($one_column[15]);
        }
        $column_attributes = preg_split("/[\s]{1,}/", $column_attributes);
        $column_attributes_fixed = array();
        foreach ($column_attributes as $attribute) {
          if (!empty($attribute)) 
            $column_attributes_fixed[] = trim($attribute);
        }
        $column_attributes_string = rtrim(implode(' ', $column_attributes_fixed));
        $parse_body[] = "`{$column_name}` {$column_type} {$column_attributes_string}";
      }
      // keyindex definition
      $reg_key = "/((primary[\s]{1,}key|key|index|unique(|[\s]{1,}index|[\s]{1,}key)|fulltext(|[\s]{1,}index)|foreign[\s]{1,}key|check)[\s]{0,}(|.*[\s]{1,})\((.*)\)(,|\)|[\s]{1,}\)))+/iU";
      $parse_key = array();
      while (preg_match($reg_key, $matches[2], $one_key)) {
        $matches[2] = str_replace($one_key[0], '', $matches[2]);
        $key_name = trim($one_key[2]);
        $key_columns = str_replace('`', '', trim($one_key[6]));
        $key_columns = preg_split("/[\s]{0,},[\s]{0,}/", $key_columns);
        if (preg_match("/PRIMARY\sKEY/i", $key_name)) {
          $is_pk_exists = true;
          if (empty($pk_store)) 
            $pk_store = $key_columns[0];
          continue;
        }
        $key_attributes = preg_split("/[\s]{1,}/", trim($one_key[5]));
        $key_attributes_fixed = array();
        foreach ($key_attributes as $attribute) {
          if (!empty($attribute)) 
            $key_attributes_fixed[] = trim($attribute);
        }
        $key_attributes_string = rtrim(implode(' ', $key_attributes_fixed));
        $key_columns_array = array();
        foreach ($key_columns as $key_column_name) {
          $key_columns_array[] = "`{$key_column_name}`";
        }
        $key_columns_string = implode(',', $key_columns_array);
        $key_name_fixed = strtoupper($key_name);
        $parse_key[] = "{$key_name_fixed} {$key_attributes_string} ({$key_columns_string})";
      }
      // table options
      $parse_option = array();
      $reg_opt = '(type|engine|auto_increment|avg_row_length|checksum|comment|(max|min)_rows|pack_keys|password|delay_key_write|row_format|raid_type|union|insert_method|(data|index) directory|default char(acter set|set))';
      $reg_base = "/(". $reg_opt ."[\s]{0,}(|=)[\s]{0,}(|'|\()(.*)(|'|\))[\s]{1,})+/iU";
      while (preg_match($reg_base, $matches[2], $one_opt)) {
        $matches[2] = str_replace($one_opt[0], '', $matches[2]);
        if (strtolower($one_opt[2]) == 'type' || strtolower($one_opt[2]) == 'engine') {
          $parse_option[] = trim(preg_replace("/^(.*)[\s]{0,}=[\s]{0,}(BDB|HEAP|ISAM|InnoDB|MERGE|MRG_MYISAM|MYISAM|MyISAM)/", '$1$2=$3%s', $one_opt[0]));
        } else if (strtolower($one_opt[2]) == 'default character set' || strtolower($one_opt[2]) == 'default charset') {
          $parse_option[] = trim(preg_replace("/^(.*)[\s]{0,}=[\s]{0,}(.*)$/iU", '$1$2=$3%s', $one_opt[0]));
        } else if (strtolower($one_opt[2]) == 'comment') {
          $parse_option[] = trim(preg_replace("/^(.*)[\s]{0,}=[\s]{0,}'(.*)'/iU", "$1$2=$3'%s'", $one_opt[0]));
        } else {
          $parse_option[] = trim($one_opt[0]);
        }
      }
      //
      $endpoint = trim($matches[2]);
      if ((empty($endpoint) || $endpoint == ')' || $endpoint == ';') && !empty($parse_body)) {
/*
        // make finalization sql
        $add_fields[0] = "`ID` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '". __('ID', self::DOMAIN) ."'";
        $add_fields[1] = "`created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '". __('Created Date', self::DOMAIN) ."'";
        $add_fields[2] = "`updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '". __('Updated Date', self::DOMAIN) ."'";
        foreach ($add_fields as $i => $field) {
          if (!in_array($field, $parse_body)) {
            if ($i == 0) {
              if (!$is_pk_exists) 
                array_unshift($parse_body, $field);
            } else {
              array_push($parse_body, $field);
            }
          }
        }
        $add_key = !$is_pk_exists ? "PRIMARY KEY (`ID`)" : "PRIMARY KEY (`{$pk_store}`)";
        if (!in_array($add_key, $parse_key)) {
          array_unshift($parse_key, $add_key);
        }
        $add_option = array(
          'ENGINE|TYPE' => "ENGINE=%s", 
          'DEFAULT CHAR' => "DEFAULT CHARSET=%s", 
          'COMMENT' => "COMMENT='%s'", 
        );
        if (empty($parse_option)) {
          foreach ($add_option as $option) {
            array_push($parse_option, $option);
          }
        } else {
          foreach ($add_option as $key => $option) {
            $is_option = false;
            foreach ($parse_option as $get_option) {
              if (preg_match('/^('.$key.')/i', $get_option)) {
                $is_option = true;
                break;
              }
            }
            if (!$is_option) {
              array_push($parse_option, $option);
            }
          }
        }
        $ds = empty($parse_key) ? " \n" : ", \n";
        $fixed_sql = $sql_head ."\n". implode(", \n", $parse_body) .$ds. implode(", \n", $parse_key) ."\n) \n". implode(" \n", $parse_option) . ' ;';
*/
        $result = array(true, $fixed_sql);
      } else {
        $result = array(false, 'There is an error in the SQL syntax. Please check the syntax body about column definition in particular.');
      }
//    return $result;

var_dump($result);
    
  }
  
  
  
  

}