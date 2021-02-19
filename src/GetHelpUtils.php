<?php
/**
 * Created by PhpStorm.
 * User: scweber
 * Date: 2019-02-26
 * Time: 16:48
 */

namespace Stanford\SupportForm;
use \REDCap;

class GetHelpUtils
{
    public $module;
    function __construct($module)
    {
        $this->module = $module;
    }

    function logIt($message) {
        $this->module->emDebug($message);
    }

    function getNextId($project_id) {
        $records = REDCap::getData($project_id, 'array', null , array('record_id'));
        // $this->logIt('in getNextId with pid  '.$project_id.' and record_id_field '.REDCap::getRecordIdField().': '.print_r($records, true));
        $next_id = 1;
        foreach ($records as $k => $v) {
            if (is_numeric($k) && $k >= $next_id) {
                // $this->logIt('in getNextId loop: is_numeric(k) and k >= next_id. nextid was '.print_r($next_id, true));
                $next_id = $k + 1;
                // $this->logIt('and nextid is now '.print_r($next_id, true));
            } else {
                // $this->logIt('ERROR! ! ! in getNextId loop with non-numeric k ' . print_r($k, true));
            }
        }
        $this->emDebug("Found next id: $next_id");
        return $next_id ;
    }

    function parseDDEnum($enumString) {
        $enums = explode("|", $enumString);
        $parsedEnum = array();
        foreach ($enums as $enum) {
            list($index, $value) = explode(",", $enum);
            $parsedEnum[trim($index)] = trim($value);
        }
        //$this->logIt("ParsedEnum is: " . print_r($parsedEnum,true), "DEBUG");

        return $parsedEnum;
    }
}