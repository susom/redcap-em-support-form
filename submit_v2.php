<?php
/** @var \Stanford\SupportForm\SupportForm $module */

use Stanford\SupportForm\GetHelpUtils;

require_once ('src/GetHelpUtils.php');
$utils = new Stanford\SupportForm\GetHelpUtils($module);

// this next one-line magic incantation instantiates instance variables corresponding to the field names in the form
@extract($_POST);
$utils->logIt(print_r($_POST, TRUE));
//
// $contact, $contactEmail, $contactPhone, $iAppt, $department, $funding, $research, $iAmPI, $pi, $irb_number
// $sunetid, $requestorName ,$requestorEmail , $requestorPhone, $requestorAffiliation, $requestorOu,
// $projectTitle, $availability, $description , $salesforceEmail   are now set.
// $serviceProjectRecordId will be > 0 if the user selected a prior project rather than creating a new one
//

if (strchr($contact, ',') > 0) {
    $parts = explode(",", $contact);
    $firstname = array_pop($parts);
    $lastname = implode(" ", $parts);
} else {
    $parts = explode(" ", $contact);
    $lastname = array_pop($parts);
    $firstname = implode(" ", $parts);
}

if (strlen($lastname) == 0) {
    $lastname=$contact;
}

if ($iAmPI === '1') {
    $utils->logIt('success! setting pi '.$lastname . ', ' . $firstname);
    $pi = $lastname . ', ' . $firstname;
} else {
    $utils->logIt('leaving pi as '.$pi);
}
$separator = '';
$category = '';
$QueueName__c = 'queuename=RIT Level 1;shortname=Research IT;longname=Research IT;url=http://redcap.stanford.edu/redcap/plugins/gethelp/rit-support.html;email=rit-support@stanford.edu';
$queue = "Research IT";
if (isset($proj_type_redcap) && $proj_type_redcap === 'on') {
    $utils->logIt('found redcap');
    $category .= $separator . 'REDCap';
    $separator = ', ';
    $queue = 'REDCap Queue';
    $QueueName__c = 'queuename=REDCap Queue;shortname=REDCap Help;longname=REDCap Support;url=http://redcap.stanford.edu/redcap/plugins/gethelp/redcap-support.html;email=redcap-help@stanford.edu';
}
if (isset($proj_type_choir) && $proj_type_choir === 'on') {
    $utils->logIt('found choir');
    $category .=  $separator . 'CHOIR';
    $separator = ', ';
}
if (isset($proj_type_stride) && $proj_type_stride === 'on') {
    $utils->logIt('found stride');
    $category .= $separator . 'STRIDE';
    $separator = ', ';
}
if (isset($proj_type_other) && $proj_type_other === 'on') {
    $utils->logIt('found other');
    $category .= $separator . 'Other';
    $separator = ', ';
}

// split the information into two sections. New projects need a record in Service Metrics
// and all projects need a record in Service Case Log
// If the request supplied the service_metric_record_id, use that to look up the existing project
// serviceProjectRecordId can be selected from a radio group on the input form
if ($serviceProjectRecordId === '0') {
    $newCase = true;

    $utils->logIt('creating new record');
    $serviceProjectRecordId = $utils->getNextId(constant('PROJECT_ID'));
    $utils->logIt (' curated_department is '.$curated_department);
    $data = array(
        'record_id' => $serviceProjectRecordId,
        'date_of_initial_completion' => date("Y-m-d h:i:s"),
        'webauth_user' => $sunetid,
        'consulting_team' => $org,
        'principal_name' => $contact,
        'principal_email' => $contactEmail,
        'principal_phone' => $contactPhone,
        'appointment' => $iAppt,
        'funding' => $funding,
        'curated_department' => $curated_department,
        'pubplan' => $pubplan,
        'research' => $research,
        'requestor_is_pi' => $iAmPI,
        'pi' => $pi,
        'irb_number' => $irb_number,
        'project_title' => $projectTitle,
        'research_description' => $description,
        'requestor_name' => $requestorName,
        'requestor_phone' => $requestorPhone,
        'requestor_email' => $requestorEmail,
        'requestor_affiliation' => $requestorAffiliation,
        'requestor_ou' => $requestorOu,
        'area_of_inquiry' =>  $category,
        'inquiry_detail' => $inquiryDetail . ' ' . $availability,
        'redcap_pid' => $redcapPid,
        'project_summary_complete' => '2'
    );

    $data_json = json_encode(array($data));
    $utils->logIt('1 data_json is '. $data_json );
    $result = REDCap::saveData( 'json', $data_json, 'overwrite');
    $utils->logIt('1 result is '.print_r($result, true));
} else {
    $newCase = false;

    // look up the project context to fill in some of the blanks
    $utils->logIt('looks like we found a prior record:   '.print_r($serviceProjectRecordId, true));

    $query_filter = "[record_id] = '" . $serviceProjectRecordId . "'";
    $records = REDCap::getData(constant('PROJECT_ID'), 'array', null , array('record_id', 'project_title'), null, null, false, false, false, $query_filter);
    foreach ($records as $k => $v) {
        foreach ($v as $v2 => $rec) {
            $projectTitle = $rec['project_title'];
            $utils->logIt('projectTitle is now set to  '.print_r($projectTitle, true));
        }
    }
}


// now set up an email for triggering a new case in Salesforce

// look up metadata needed to convert codes to labels
$dd_array = REDCap::getDataDictionary(constant('PROJECT_ID'), 'array', FALSE, array('funding','curated_department', 'pubplan','research','appointment','requestor_is_pi'));

// cleanse the email field in case they tried to get fancy
$pattern = '/[a-z0-9_\.\-\+]+@[a-z0-9\-]+\.([a-z]{2,3})(?:\.[a-z]{2})?/i'; //regex for pattern of e-mail address
preg_match($pattern, $contactEmail, $matches);
$contactEmail= $matches[0];

if (isset($redcapPid) && strlen($redcapPid) > 0) {
    $redcapURL =
        "https://redcap.stanford.edu" . APP_PATH_WEBROOT . "index.php?pid=$redcapPid";
} else {
    $redcapURL = '';
}
//$toAddr = 'scweber@stanford.edu';
$toAddr = $salesforceEmail ;
$contact = stripslashes($contact);
$contactEmail = stripslashes($contactEmail);
$description = stripslashes($description);
$availability = stripslashes($availability);
$apptmeta =$utils->parseDDEnum($dd_array['appointment']['select_choices_or_calculations']);
$apptstr = $apptmeta[$iAppt];
$researchmeta =$utils->parseDDEnum($dd_array['research']['select_choices_or_calculations']);
$researchstr = $researchmeta[$research];
$iampimeta =$utils->parseDDEnum($dd_array['requestor_is_pi']['select_choices_or_calculations']);
$iampistr = $iampimeta[$iAmPI];
$pubplanmeta = $utils->parseDDEnum($dd_array['pubplan']['select_choices_or_calculations']);
$pubplanstr = $pubplanmeta[$pubplan];
$utils->logIt('$pubplan is '.print_r($pubplan, true));
$utils->logIt('$pubplanmeta is '.print_r($pubplanmeta, true));
$utils->logIt('$pubplanstr is '.print_r($pubplanstr, true));
if (!isset($pubplanstr)) {
    $pubplanstr = '';
}
//$utils->logIt('dd '.print_r($dd_array, true));
$fundingmeta = $utils->parseDDEnum($dd_array['funding']['select_choices_or_calculations']);
$fundingstr = $fundingmeta[$funding];
if (!isset($fundingstr) ) {
    $fundingstr = '';
}
$curated_departmentmeta =$utils->parseDDEnum($dd_array['curated_department']['select_choices_or_calculations']);
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
$customertag = 'On ' . $datestring . ' at ' . $timestring . ' ' . $firstname . ' ' . $lastname . ' (' . $contactEmail . ") wrote:\n\n" ;

if ( $newCase ) {
    $message = "Summary: $projectTitle\n\nDescription: $description\n" .
        "\nQuestion: $inquiryDetail" .
        "\nAvailability: $availability" .
        "\n\nRequested For: $contact\nContact E-mail: $contactEmail\nPhone: $contactPhone\n" .
        "\nAppointment: " . $apptstr .
        "\nRequestor Department: $requestorOu" .
        "\nProject Department: " . $curated_departmentstr . "\n" .
        "\nIs requestor the PI?: " . $iampistr .
        "\nResearch?: " . $researchstr .
        (!isset($irb_number) || strlen($irb_number) == 0 ? '' : ', IRB: ' . $irb_number) .
        (!isset($pi) || strlen($pi) == 0 ? '' : ', PI: ' . $pi) .
        "\nArea of inquiry: $category $redcapURL" .
        "\nRequest submitted by: $requestorName <$requestorEmail> ($sunetid) [$requestorAffiliation - $requestorOu]\n";

    if (strcasecmp($contactEmail, $requestorEmail) == 0) {
        $contactSunetid = $sunetid;
        $contactOu = $requestorOu;
        $contactAffiliation = $requestorAffiliation;
    } else {
        $contactSunetid = '';
        $contactOu = '';
        $contactAffiliation = '';
    }

    $contactObj = (object)[
        'LastName' => $lastname,
        'FirstName' => $firstname,
        'SUNet_ID__c' => $contactSunetid,
        'Email' => $contactEmail,
        'Phone' => $contactPhone,
        'Department' => $contactOu,
        'suaffiliation__c' => $contactAffiliation,
        'Rank__c' => $apptstr,
        'Stanford_Dept__c' => $curated_departmentstr
    ];
    $caseAr = [
        'SUnet_ID_case__c' => $sunetid,
        'Subject' => $projectTitle,
        'Availability__c' => $availability,
        'Origin' => 'Web',
        'ContactEmail' => $contactEmail,
        'ContactPhone' => $contactPhone,
        'Description' => $customertag. $message,
        'Funding_status__c' => $fundingstr,
        'I_am_PI_case__c' =>  ($iAmPI == 1 ? 'true' : 'false'),
        'IRB_Protocol__c' => $irb_number,
        'PI_Name__c' => $pi,
        'Project_Record_ID__c' => $serviceProjectRecordId,
        'Project_Department__c' => $curated_departmentstr,
        'REDCap_StudyName__c' => $redcapURL,
        'Publication_Plans__c' => $pubplanstr,
        'Original_Queue_Name__c' => $QueueName__c,
        'Active_Queue__c' => $QueueName__c,
        'CustomOrigin__c' => $CustomOrigin__c,
        'Primary_Category__c' => $category,
        'QueueName__c' => $queue,
    ];
    $emailmessage = json_encode($contactObj) . "~#~#~" . json_encode((object)$caseAr);
    $utils->logIt($emailmessage);
    $send_contact=mail( $toAddr, "base64_encoded", base64_encode($emailmessage), $headers );
} else {
    $subject = "Project_Record_ID__c=".$serviceProjectRecordId. "\n";
    $emailmessage =   $inquiryDetail . "\n" . $availability;
    $utils->logIt($emailmessage);
    $send_contact=mail( $toAddr, $subject, $emailmessage, $headers );
}

echo "<html><head><link rel='stylesheet' href='https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css'><style>
        .my-brand-logo {
            position: relative;
            z-index: 10;
            float: left;
            display: block;
            width: 100%;
            height: 73px;
            margin-right: 0.7142857142857143rem;
            text-indent: -9999px;
            background: url("; print $module->getUrl("img/web-logo-color-filled-shield.png",false,true) ; echo ") no-repeat;

            background-position: 30px 0px ;
            background-size: auto 90%;
        }
    </style></head><body><a href='http://med.stanford.edu/researchit.html'>
    <div class='brand'>
        <div class='my-brand-logo'></div>
    </div></a>
<div class='container'>  <h2>Thank You</h2>";

if($send_contact){
    echo "<p>Thanks for contacting $org";
    if ($contact !== $requestorName) {
        echo " on behalf of $contact, $requestorName. The support team has been notified.  Please ask $contact to check their email ";
    } else {
        echo ", $requestorName. Your request has been sent to the support team.  Please check your email ";
    }
    echo "for an automated acknowledgement of your submission.</p>";
}
else {
    $utils->logIt( "\tERROR Email send failure to $toAddr from $contactEmail:".$message.  "\n");
    echo "<p>Sorry, there was a problem contacting the consultation services team. Please try again later.</p>";
}
echo "</div></body></html>";
$utils->logIt( "\tEmail from $contactEmail:".$message.  "\n");

