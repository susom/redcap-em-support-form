<?php
namespace Stanford\SupportForm;

include_once "emLoggerTrait.php";

use REDCap;
use DateTime;

class SupportForm extends \ExternalModules\AbstractExternalModule
{
    use emLoggerTrait;
    private $project;
    
    
    public function __construct()
    {
        parent::__construct();
        
        try {
            
            if (isset($_GET['pid'])) {
                
                $this->setProject(new \Project(filter_var($_GET['pid'], FILTER_SANITIZE_NUMBER_INT)));
                
            }
            
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        
    }
    
    function parseDDEnum($enumString) {
        $enums = explode("|", $enumString);
        $parsedEnum = array();
        foreach ($enums as $enum) {
            list($index, $value) = explode(",", $enum);
            $parsedEnum[trim($index)] = trim($value);
        }
        //$this->emDebug("ParsedEnum is: " . print_r($parsedEnum,true), "DEBUG");
        
        return $parsedEnum;
    }
    /**
     * @param $project
     */
    public function setProject($project)
    {
        $this->project = $project;
    }
    
    function redcap_survey_complete ( int $project_id, string $record = NULL, string $instrument, int $event_id, int $group_id = NULL, string $survey_hash, int $response_id = NULL, int $repeat_instance = 1 ) {
//        $this->emDebug("! In redcap_survey_complete: "  . print_r($record,TRUE));
        if ($project_id != 22082) {
            // only trigger this behavior for the RIC's 2021 intake form.
            return;
        }
        $query_filter='[record_id] = ' . $record;
        $rcdata = REDCap::getData($project_id, 'array', null, null, null, null, false, false, false, $query_filter);
//        $this->emDebug("In redcap_survey_complete: "  . print_r($rcdata [$record][$event_id],TRUE));
        @extract( $rcdata [$record][$event_id]);
//        $this->emDebug(print_r($rcdata [$record][$event_id], TRUE));
//        $this->emDebug("is principal_name set? " . $principal_name . ' '. $principal_email);
        
        if (strchr($principal_name, ',') > 0) {
            $parts = explode(",", $principal_name);
            $firstname = array_pop($parts);
            $lastname = implode(" ", $parts);
        } else {
            $parts = explode(" ", $principal_name);
            $lastname = array_pop($parts);
            $firstname = implode(" ", $parts);
        }
//        $this->emDebug('$irb_number is '. $irb_number);
        if (strlen($lastname) == 0) {
            $lastname=$principal_name;
        }
    
        if ($requestor_is_pi === '1') {
//            $this->emDebug('success! setting pi '.$lastname . ', ' . $firstname);
            $pi = $lastname . ', ' . $firstname;
        } else {
            $this->emDebug('leaving pi as '.$pi);
        }
    
//        $data_json = json_encode($rcdata [$record][$event_id]);
//        $this->emDebug('1 data_json is '. $data_json );
//        $result = REDCap::saveData($project_id, 'json', $data_json, 'overwrite');
//        $this->emDebug('1 result is '.print_r($result, true));
        
// now set up an email for triggering a new case in Salesforce

// look up metadata needed to convert codes to labels
        $dd_array = REDCap::getDataDictionary($project_id, 'array', FALSE, array('area_of_inquiry','funding','curated_department', 'pubplan','research','appointment','requestor_is_pi'));

// cleanse the email field in case they tried to get fancy
        $pattern = '/[a-z0-9_\.\-\+]+@[a-z0-9\-]+\.([a-z]{2,3})(?:\.[a-z]{2})?/i'; //regex for pattern of e-mail address
        preg_match($pattern, $principal_email, $matches);
        $principal_email= $matches[0];
       
        $redcapURL = "https://redcap.stanford.edu" . APP_PATH_WEBROOT . "index.php?pid=$project_id";
       
//$toAddr = 'scweber@stanford.edu';
        $toAddr = "ric-support@stanford.edu" ;
        $contact = stripslashes($principal_name);
        $contactEmail = stripslashes($principal_email);
        $description = stripslashes($research_description);
        $availability = stripslashes($contact_info);
        $inquirymeta = $this->parseDDEnum($dd_array['area_of_inquiry']['select_choices_or_calculations']);
//        $this->emDebug('inquirymeta is ' . print_r($inquirymeta,TRUE));
        $area_of_inquiry_1 = ( $area_of_inquiry[1] ? $inquirymeta[1] : "");
        $area_of_inquiry_2 = ( $area_of_inquiry[2] ? $inquirymeta[2] : "");
        $area_of_inquiry_3 = ( $area_of_inquiry[3] ? $inquirymeta[3] : "");
        $area_of_inquiry_4 = ( $area_of_inquiry[4] ? $inquirymeta[4] : "");
        $area_of_inquiry_5 = ( $area_of_inquiry[5] ? $inquirymeta[5] : "");
        $apptmeta = $this->parseDDEnum($dd_array['appointment']['select_choices_or_calculations']);
        $apptstr = $apptmeta[$appointment];
        $researchmeta = $this->parseDDEnum($dd_array['research']['select_choices_or_calculations']);
        $researchstr = $researchmeta[$research];
        $iampimeta = $this->parseDDEnum($dd_array['requestor_is_pi']['select_choices_or_calculations']);
        $iampistr = $iampimeta[$requestor_is_pi];
        $pubplanmeta = $this->parseDDEnum($dd_array['pubplan']['select_choices_or_calculations']);
        $pubplanstr = $pubplanmeta[$pubplan];
//        $this->emDebug('$pubplan is '.print_r($pubplan, true));
//        $this->emDebug('$pubplanmeta is '.print_r($pubplanmeta, true));
//        $this->emDebug('$pubplanstr is '.print_r($pubplanstr, true));
        if (!isset($pubplanstr)) {
            $pubplanstr = '';
        }
//$this->emDebug('dd '.print_r($dd_array, true));
        $fundingmeta = $this->parseDDEnum($dd_array['funding']['select_choices_or_calculations']);
        $fundingstr = $fundingmeta[$funding];
        if (!isset($fundingstr) ) {
            $fundingstr = '';
        }
        $curated_departmentmeta = $this->parseDDEnum($dd_array['curated_department']['select_choices_or_calculations']);
        $curated_departmentstr = $curated_departmentmeta[$curated_department];
// To send HTML mail, the Content-type header must be set
        $headers = 'MIME-Version: 1.0' . "\n";
        $headers .= 'Content-type: text/plain; charset=utf-8' . "\n";
// Additional headers as http://php.net/manual/en/function.mail.php

// now re-instate their name, as the Salesforce logic parses it for the auto-generated response
        $fromAddr = "\"$contact\" <$contactEmail>";
    
        $headers .= "From: $fromAddr" . "\n";
        $headers .= "Reply-To: $fromAddr" . "\n";
        $datetimenow = new DateTime();
        $datestring = $datetimenow->format('M j, Y');
        $timestring = $datetimenow->format('g:i A');
        $customertag = 'On ' . $datestring . ' at ' . $timestring . ' ' . $firstname . ' ' . $lastname . ' (' . $principal_email . ") wrote:\n\n" ;
        $inquiry_detail = stripslashes($inquiry_detail);
        $message = "Summary: $project_title\n\nDescription: $description\n" .
            "\nQuestion: $inquiry_detail" .
            "\nAvailability: $availability" .
            "\n\nRequested For: $contact\nContact E-mail: $contactEmail\nPhone: $principal_phone\n" .
            "\nAppointment: " . $apptstr .
            "\nDepartment: " . $curated_departmentstr . "\n" .
            "\nIs requestor the PI?: " . $iampistr .
            "\nResearch?: " . $researchstr .
            (!isset($irb_number) || strlen($irb_number) == 0 ? '' : ', IRB: ' . $irb_number) .
            (!isset($pi) || strlen($pi) == 0 ? '' : ', PI: ' . $pi) .
            (!isset($sciMember) || strlen($sciMember) == 0 ? '' : ', SCI member: ' . $sci_member) .
            "\nArea of inquiry: $area_of_inquiry_1 $area_of_inquiry_2 $area_of_inquiry_3 $area_of_inquiry_4 $area_of_inquiry_5 " .
            "\nPlans to publish: " . $pubplanstr .
            "\nFunding: " . $fundingstr .
            "\nRequest submitted by: $contact <$contactEmail> ($webauth_user) [$apptstr]\n\n" .
            "\nRedcap URL $redcapURL";
    
        $contactObj = (object)[
            'LastName' => $lastname,
            'FirstName' => $firstname,
            'SUNet_ID__c' => $webauth_user,
            'Email' => $contactEmail,
            'Phone' => $principal_phone,
            'Department' => $curated_departmentstr,
            'suaffiliation__c' => '',
            'Rank__c' => $apptstr,
            'Stanford_Dept__c' => $curated_departmentstr
        ];
        $QueueName__c = "queuename=RICQueue;shortname=RIC;longname=Research Informatics Center;url=https://med.stanford.edu/ric.html;email=ric-support@stanford.edu;owneralias=RIC";
        $caseAr = [
            'SUnet_ID_case__c' => $webauth_user,
            'Subject' => $project_title,
            'Availability__c' => $availability,
            'Origin' => 'Web',
            'ContactEmail' => $contactEmail,
            'ContactPhone' => $principal_phone,
            'Description' => $customertag. $message,
            'Funding_status__c' => $fundingstr,
            'I_am_PI_case__c' =>  ($requestor_is_pi == 1 ? 'true' : 'false'),
            'IRB_Protocol__c' => $irb_number,
            'PI_Name__c' => $pi,
            'Project_Record_ID__c' => $record,
            'Project_Department__c' => $curated_departmentstr,
            'REDCap_StudyName__c' => $redcapURL,
            'Publication_Plans__c' => $pubplanstr,
            'Original_Queue_Name__c' => $QueueName__c,
            'Active_Queue__c' => $QueueName__c,
            'CustomOrigin__c' => "RIC Form V1",
            'Primary_Category__c' => min($area_of_inquiry___1,$area_of_inquiry___2,$area_of_inquiry___3,$area_of_inquiry___4,$area_of_inquiry___5)
        ];
        if (isset($sci_member) && strlen($sci_member) > 0) {
            $caseAr['SCI_Sponsor__c'] = $sci_member;
            $caseAr['CancerCenter__c'] = 'true';
        }
        $emailmessage = json_encode($contactObj) . "~#~#~" . json_encode((object)$caseAr);
        $this->emDebug($emailmessage);
        $send_contact=mail( $toAddr, "base64_encoded", base64_encode($emailmessage), $headers );
        
        $this->emDebug( "\tEmail to $send_contact from $contactEmail: ".$message.  "\n");
        
    }
    
}