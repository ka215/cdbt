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



}