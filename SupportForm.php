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
        //  trigger this behavior for the RIC's 2021 intake form (22082) and Research IT (9132).
    
        $query_filter='[record_id] = ' . $record;
        $rcdata = REDCap::getData($project_id, 'array', null, null, null, null, false, false, false, $query_filter);

        @extract( $rcdata [$record][$event_id]);
        
        if (strchr($principal_name, ',') > 0) {
            $parts = explode(",", $principal_name);
            $firstname = array_pop($parts);
            $lastname = implode(" ", $parts);
        } else {
            $parts = explode(" ", $principal_name);
            $lastname = array_pop($parts);
            $firstname = implode(" ", $parts);
        }

        if (strlen($lastname) == 0) {
            $lastname=$principal_name;
        }
    
        if ($requestor_is_pi === '1') {
            $pi = $lastname . ', ' . $firstname;
        } 
        
// now set up an email for triggering a new case in Salesforce

// look up metadata needed to convert codes to labels
        $dd_array = REDCap::getDataDictionary($project_id, 'array', FALSE, array('category','area_of_inquiry','funding','curated_department', 'pubplan','research','appointment','requestor_is_pi'));

// cleanse the email field in case they tried to get fancy
        $pattern = '/[a-z0-9_\.\-\+]+@[a-z0-9\-]+\.([a-z]{2,3})(?:\.[a-z]{2})?/i'; //regex for pattern of e-mail address
        preg_match($pattern, $principal_email, $matches);
        $principal_email= $matches[0];
       
        $redcapURL = "https://redcap.stanford.edu" . APP_PATH_WEBROOT . "index.php?pid=$project_id" . "&arm=1&id=" . $record;
       
//$toAddr = 'scweber@stanford.edu';
        
        $contact = stripslashes($principal_name);
        $contactEmail = stripslashes($principal_email);
        $description = stripslashes($research_description);
        $availability = stripslashes($contact_info);
        
        // RIC formerly used area_of_inquiry , comment out for now in case they change their minds
//         $inquirymeta = $this->parseDDEnum($dd_array['area_of_inquiry']['select_choices_or_calculations']);
//         $area_of_inquiry_1 = ( $area_of_inquiry[1] ? $inquirymeta[1] : "");
//         $area_of_inquiry_2 = ( $area_of_inquiry[2] ? $inquirymeta[2] : "");
//         $area_of_inquiry_3 = ( $area_of_inquiry[3] ? $inquirymeta[3] : "");
//         $area_of_inquiry_4 = ( $area_of_inquiry[4] ? $inquirymeta[4] : "");
//         $area_of_inquiry_5 = ( $area_of_inquiry[5] ? $inquirymeta[5] : "");
        // Research IT uses category
        $categorymeta = $this->parseDDEnum($dd_array['area_of_inquiry']['select_choices_or_calculations']);
        $category_1 = ( $category[1] ? $categorymeta[1] : "");
        $category_2 = ( $category[2] ? $categorymeta[2] : "");
        $category_3 = ( $category[3] ? $categorymeta[3] : "");
        $category_99 = ( $category[99] ? $categorymeta[99] : "");
        
        $apptmeta = $this->parseDDEnum($dd_array['appointment']['select_choices_or_calculations']);
        $apptstr = $apptmeta[$appointment];
        $researchmeta = $this->parseDDEnum($dd_array['research']['select_choices_or_calculations']);
        $researchstr = $researchmeta[$research];
        $iampimeta = $this->parseDDEnum($dd_array['requestor_is_pi']['select_choices_or_calculations']);
        $iampistr = $iampimeta[$requestor_is_pi];
        
        // used only by the RIC
        $pubplanmeta = $this->parseDDEnum($dd_array['pubplan']['select_choices_or_calculations']);
        $pubplanstr = $pubplanmeta[$pubplan];
        if (!isset($pubplanstr)) {
            $pubplanstr = '';
        }
        $fundingmeta = $this->parseDDEnum($dd_array['funding']['select_choices_or_calculations']);
        $fundingstr = $fundingmeta[$funding];
        if (!isset($fundingstr) ) {
            $fundingstr = '';
        }
        
        $curated_departmentmeta = $this->parseDDEnum($dd_array['curated_department']['select_choices_or_calculations']);
        $curated_departmentstr = $curated_departmentmeta[$curated_department];
        
        if ($project_id == 22082) {
            $toAddr = "ric-support@stanford.edu" ;
            $QueueName__c = "queuename=RICQueue;shortname=RIC;longname=Research Informatics Center;url=https://med.stanford.edu/ric.html;email=ric-support@stanford.edu;owneralias=RIC";

        } else {
            $toAddr = "rit-support@stanford.edu" ;
            if (isset($category_1) || strlen($category_1) > 0) {
                    $QueueName__c = 'queuename=REDCap Queue;shortname=REDCap Help;longname=REDCap Support;url=http://redcap.stanford.edu/redcap/plugins/gethelp/redcap-support.html;email=redcap-help@stanford.edu';
            } else {
                $QueueName__c = 'queuename=RIT Level 1;shortname=Research IT;longname=Research IT;url=http://redcap.stanford.edu/redcap/plugins/gethelp/rit-support.html;email=rit-support@stanford.edu';
            }
        }
        
        // now re-instate their name, as the Salesforce logic parses it for the auto-generated response
        $name_component = $contact ?:  $webauth_user;
        $fromAddr = "\"$name_component\" <$contactEmail>";
        $this->emDebug("FromAddr :" . $fromAddr);
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
            (!isset($sciMember) || strlen($sciMember) == 0 ? '' : ', SCI member: ' . $sci_member) ;
        if ($project_id == 22082) {
            $message = $message .
//             "\nArea of inquiry: $area_of_inquiry_1 $area_of_inquiry_2 $area_of_inquiry_3 $area_of_inquiry_4 $area_of_inquiry_5 " .
            "\nPlans to publish: " . $pubplanstr .
            "\nFunding: " . $fundingstr .
            "\nDICOM: " . ($radiology_or_dicom ? 'Yes, this is a Radiology/DICOM consult. See REDCap (URL below) for more information.' : '') ;
        } else {
            $message = $message .
            "\nArea of inquiry: $category_1 $category_2 $category_3 $category_99 " ;
        }
        $message = $message .
            "\nREDCap URL $redcapURL" .
            "\nRequest submitted by: $contact <$contactEmail> ($webauth_user) [$apptstr]\n" ;
    
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
            'CustomOrigin__c' => "RIC Form V1" //,
//             'Primary_Category__c' => min($area_of_inquiry___1,$area_of_inquiry___2,$area_of_inquiry___3,$area_of_inquiry___4,$area_of_inquiry___5)
        ];
        if (isset($sci_member) && strlen($sci_member) > 0) {
            $caseAr['SCI_Sponsor__c'] = $sci_member;
            $caseAr['CancerCenter__c'] = 'true';
        }
        $emailmessage = json_encode($contactObj) . "~#~#~" . json_encode((object)$caseAr);
        $this->emDebug($emailmessage);
        $send_contact = \REDCap::email($toAddr, $fromAddr, "base64_encoded", base64_encode($emailmessage));
        // $send_contact=mail( $toAddr, "base64_encoded", base64_encode($emailmessage), $headers );
        $this->emDebug( "\tEmail to $send_contact from $contactEmail: ".$message.  "\n");
        
    }
    
}
