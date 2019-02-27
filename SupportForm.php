<?php
namespace Stanford\SupportForm;

include_once "emLoggerTrait.php";

use REDCap;


class SupportForm extends \ExternalModules\AbstractExternalModule
{
    use emLoggerTrait;

    function __construct()
    {
        parent::__construct();

        $this->emDebug('hello');
    }
}