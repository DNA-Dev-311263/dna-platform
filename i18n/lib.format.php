<?php

/*
 * FORMA - The E-Learning Suite
 *
 * Copyright (c) 2013-2023 (Forma)
 * https://www.formalms.org
 * License https://www.gnu.org/licenses/old-licenses/gpl-2.0.txt
 * Mod. by ABR 
 * from docebo 4.0.5 CE 2008-2012 (c) docebo
 * License https://www.gnu.org/licenses/old-licenses/gpl-2.0.txt
 */

defined('IN_FORMA') or exit('Direct access is forbidden.');

class Format
{
    private static $_regset = false;

    private function __construct()
    {
    }

    private static function init()
    {
        require_once _i18n_ . '/lib.regset.php';
        self::$_regset = new RegionalSettings();
    }

    /**
     * Return the current istance of the format file.
     */
    public static function instance()
    {
        $classname = __CLASS__;
        if (!self::$_regset) {
            self::init();
        }

        return self::$_regset;
    }

    /**
     * Convert a date from the iso format to the current regional format.
     *
     * @param <string> $date the date to convert
     * @param <string> $type 'date' or 'datetime'
     *
     * @return <string> the date in the current format
     */
    public static function date($date, $type = false, $seconds = false)
    {
        if (!self::$_regset) {
            self::istance();
        }

        return self::$_regset->databaseToRegional($date, $type, $seconds);
    }
    
    
	/** Converte una data/ora iso in una stringa formattata
	 *  (ABR)
	 */
	public static function datetimeToString($date_str, $format, $val_if_zero = false, $ignore_time_zero = true){
			
		$date = strtotime($date_str);
		$time_string = date("H:i", $date);
		$zero = '0000-00-00'.(!$ignore_time_zero ? ' 00:00:00' : '');
		
		if (strpos($date_str, $zero) !== false && $val_if_zero !== false) {
			return $val_if_zero;
		}
		
		switch($format){
			case "date":
				return self::date($date_str, 'date');
				break;
				
			case "day_name_short":
				$day_names = array(0, 	Lang::t('_MONDAY', 'calendar'), Lang::t('_TUESDAY', 'calendar'), Lang::t('_WEDNESDAY', 'calendar'), 
										Lang::t('_THURSDAY', 'calendar'), Lang::t('_FRIDAY', 'calendar'), Lang::t('_SATURDAY', 'calendar'), 
										Lang::t('_SUNDAY', 'calendar'));

				return date('d', $date)." ".substr($day_names[date('N', $date)], 0, 3);
				break;

			case "month_name_short":
				$month_names = array(0, Lang::t('_JAN', 'calendar'), Lang::t('_FEB', 'calendar'), Lang::t('_MAR', 'calendar'), 
										Lang::t('_APR', 'calendar'), Lang::t('_MAY', 'calendar'), Lang::t('_JUN', 'calendar'), 
										Lang::t('_JUL', 'calendar'), Lang::t('_AUG', 'calendar'), Lang::t('_SEP', 'calendar'), Lang::t('_OCT', 'calendar'),
										Lang::t('_NOV', 'calendar'), Lang::t('_DEC', 'calendar'));

				return date('d', $date)." ".$month_names[date('n', $date)];
				break;
				
			case "month_name_long":
				$month_names = array(0, Lang::t('_JANUARY', 'calendar'), Lang::t('_FEBRUARY', 'calendar'), Lang::t('_MARCH', 'calendar'), 
										Lang::t('_APRIL', 'calendar'), Lang::t('_MAY', 'calendar'), Lang::t('_JUNE', 'calendar'), 
										Lang::t('_JULY', 'calendar'), Lang::t('_AUGUST', 'calendar'), Lang::t('_SEPTEMBER', 'calendar'), Lang::t('_OCTOBER', 'calendar'),
										Lang::t('_NOVEMBER', 'calendar'), Lang::t('_DECEMBER', 'calendar'));

				return date('d', $date)." ".$month_names[date('n', $date)];
				break;
				
			case "datetime":
				if (!$ignore_time_zero || $time_string != '00:00')
					return self::date($date_str, 'datetime');
				else
					return self::date($date_str, 'date');
				break;	
						
			case "time":
				if (!$ignore_time_zero || $time_string != '00:00')
					return $time_string;
				break;			

		}
	}


    /**
     * Convert a date from the current regional format to a iso format.
     *
     * @param <string> $date the date to convert
     * @param <string> $type 'date' or 'datetime'
     *
     * @return <string> the date in iso
     */
    public static function dateDb($date, $type = false)
    {
        if (!self::$_regset) {
            self::istance();
        }

        return self::$_regset->regionalToDatabase($date, $type);
    }

    /**
     * Convert a date from the ISO format into timestamp.
     *
     * @param <string> $date the date to convert
     *
     * @return <string> the timestamp
     */
    public static function toTimestamp($date)
    {
        if (!self::$_regset) {
            self::istance();
        }

        return self::$_regset->databaseToTimestamp($date);
    }

    public function dateDistance($date)
    {
        // yyyy-mm-dd hh:mm:ss
        // 0123456789012345678
        $year = substr($date, 0, 4);
        $month = substr($date, 5, 2);
        $day = substr($date, 8, 2);

        $hour = substr($date, 11, 2);
        $minute = substr($date, 14, 2);
        $second = substr($date, 17, 2);

        $distance = time() - mktime($hour, $minute, $second, $month, $day, $year);
        //second -> minutes
        $distance = (int) ($distance / 60);
        // < 1 hour print minutes
        if (($distance >= 0) && ($distance < 60)) {
            return $distance . ' ' . Lang::t('_MINUTES', 'standard');
        }

        //minutes -> hour
        $distance = (int) ($distance / 60);
        if (($distance >= 0) && ($distance < 48)) {
            return $distance . ' ' . Lang::t('_HOURS', 'standard');
        }

        //hour -> day
        $distance = (int) ($distance / 24);
        if (($distance >= 0) && ($distance < 30)) {
            return $distance . ' ' . Lang::t('_DAYS', 'standard');
        }

        //echo > 1 month
        return Format::date($date, 'date');
    }
}
