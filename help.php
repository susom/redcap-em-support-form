<?php
/** @var \Stanford\SupportForm\SupportForm $module */


use Stanford\SupportForm\GetHelpUtils;

require_once ('src/GetHelpUtils.php');
$utils = new Stanford\SupportForm\GetHelpUtils($module);

// this is used to generate dropdown lists
$dd_array = REDCap::getDataDictionary(constant('PROJECT_ID'), 'array', FALSE, array('curated_department','research','appointment'));

$sunetid = $_SERVER['REMOTE_USER'];
$prj_type = $_GET['prj_type'];
$rc_inq = $prj_type === 'redcap';
$choir_inq = $prj_type === 'choir';
$stride_inq = $prj_type === 'stride';
$utils->logIt('rc_inq '.$rc_inq);
// Do LDAP Lookup
$ldap = file_get_contents('http://med.stanford.edu/webtools-dev/stanford_ldap/ldap_lookup.php?token=pXJ5xNwj1P&exact=true&only=name,displayname,mail,department,suaffiliation,ou,telephonenumber&userid='. $sunetid);
$ldapResult = json_decode($ldap);
$ldapResult->givenname = ucwords($ldapResult->givenname);
//$utils->logIt(  "\t" . $ldap . "\n");

// and this is used to show the list of projects for this user
$query_filter = "[webauth_user] = '" . $sunetid . "'";
$rcdata = REDCap::getData(constant('PROJECT_ID'), 'array', null, null, null, null, false, false, false, $query_filter);
$utils->logIt(   "\t PROJECT ID IS ".constant('PROJECT_ID').'...' .print_r( $rcdata,true) . "\n");
$first_time_ever = sizeof($rcdata) == 0;

if ($first_time_ever) {
    $utils->logIt('first time ever');
    $collapse_state = ' in ';
    $helptitle = '';
} else {
    $helptitle = 'Welcome back!';
    $collapse_state = '';
}
?>
<!DOCTYPE HTML>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">

    <!-- Optional theme -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css">

    <!-- Latest compiled and minified JavaScript -->
    <script   src="https://code.jquery.com/jquery-3.1.0.min.js"   integrity="sha256-cCueBR6CsyA4/9szpPfrX3s49M9vUU5BgtiJj06wt/s="   crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>

    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="<?php print $module->getUrl("css/local-style.css",false,true) ?>">

    <style>
        .my-brand-logo {
            position: relative;
            z-index: 10;
            float: left;
            display: block;
            width: 100%;
            height: 73px;
            margin-right: 0.7142857142857143rem;
            text-indent: -9999px;
            background: url(<?php print $module->getUrl("img/web-logo-color-filled-shield.png",false,true) ?>) no-repeat;

            background-position: 30px 0px ;
            background-size: auto 90%;
        }
    </style>
    <link rel="icon" type="image/ico" href="data:image/ico;base64,AAABAAEAEBAAAAEAIABoBAAAFgAAACgAAAAQAAAAIAAAAAEAIAAAAAAAAAQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAADyMwAACC/0oAgv8AAAAAAAAAAAAAgv8AAIL/GQkZMGeFy/8AULz/BAUKEL1xe/+FaXOvAAAAAAAAAAAAAAAAAA8jMAAAgv9KAIL/AAAAAEFovdsAAIL/GBCD/zAwMAlnhsv/AFC8/wUFCxC9cXv/hWlzrwAAAAAAAAAAAAAAAAAPIzAAAIL/SgCC/1pdYBcAAIL/AACC/4+Pg2UwMDABZ4bL/wBQvP8FBQsQvXF7/4Vpc68AAAAAAAAAAAAAAAAADyMwAACC/wAAgv8AAIL/AACC/wAAgv9wcGs3AAAAAGeGy/8AULz/BQULEL1xe/+FaXOvAAAAAAAAAAAAAAAAAA8jMAAAgv8AAIL/AACC/wAAgv8AAIL/FACC/zAwMAJnhsv/AFC8/wUFCxC9cXv/hWlzrwAAAAAAAAAAAAAAAAAPIzAAAIL/SgCC/wAAAAAAAAAAAACC/wAAgv8wLSgwZ4bL/wBQvP8FBQsQvXF7/4Vpc68AAAAAAAAAAAAAAAAADyMwAACC/1EAgv8AAAAA9vz+RwAAgv8AAIL/MC4qKQAAAABcNEsis3qcbr1xe/+FaXOvXDRLIlw0SyIAAAAAAA8jMAAAgv8AAIL/AACC/wAAgv8AAIL/TTaH+gAAAADBwtLYvXF7/71xe/+9cXv/vXF7/71xe/+9cXv/JBkaLAAAAAAAAFmvAABZrwAAWa8AAFmvhXh8iQAAAAAAAAAAfW9/bYVpc6+FaXOvhWlzr4Vpc6+FaXOvhWlzrwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA//8AAP//AAD//wAAmScAAJEnAACTJwAAgycAAIEnAACZJwAAmecAAIEBAACDgQAA//8AAP//AAD//wAA//8AAA=="/>
</head>
<body>
<div class="black-accent">&nbsp;</div>
<a href="http://med.stanford.edu/researchit.html">
    <div class="brand">
        <div class="my-brand-logo"></div>
    </div></a>
<div id="background" class="background">
    <form data-toggle="validator" role="form" method=POST action='<?php print $module->getUrl("submit_v2.php",false,true) ?>'>
        <div class="container">

            <div class="panel" id="buttonbar">
                <div class="col-md-7">
                    <p>&nbsp;</p>
                    <button type="button" class="redcap" onclick="showRequestForm()"><img src="<?php print $module->getUrl("img/combined-brand.png",false,true) ?>" height="174"></button>
                    <br/><button type="button"  class="rcbutton stanford" onclick="showRequestForm()">Get help with REDCap, CHOIR, Cohort Discovery and Chart Review</button></br>

                </div>

                <!--div class="col-md-5" >
                    <p>&nbsp;</p>
                    <a href="https://redcap.stanford.edu/plugins/gethelp/ric.php"   ><img src="<?php print $module->getUrl("img/ric3.jpg",false,true) ?>" height="179"></a>
                    <br/><a class="stanford" href="https://redcap.stanford.edu/plugins/gethelp/ric.php"><button type="button"   class="ric-dasher stanford" >Obtain clinical data for research purposes</button></a></p>

                </div>

                <div class="col-md-7" >
                    <p>&nbsp;</p>
                    <button type="button" class="mhealth" onclick="showStride()"><img src="<?php print $module->getUrl("img/cdh.png",false,true) ?>" height="174"></button>
                    <br/><button type="button"  class="stanford rcbutton" onclick="showStride()">Get help with STRIDE tools for cohort discovery and chart review</br>Deploy custom CHOIR, mHealth, and Learning Healthcare Systems</button></br>
                </div-->

                <div class="col-md-5" >
                    <p>&nbsp;</p>
                    <a href="https://stanford.service-now.com/it_services?id=sc_cat_item&sys_id=97bb9c8c134d9740d3b6b3b12244b0fb"   ><img src="<?php print $module->getUrl("img/dasher2.png",false,true) ?>" height="179"></a>
                    <br/><a class="stanford" href="https://stanford.service-now.com/it_services?id=sc_cat_item&sys_id=97bb9c8c134d9740d3b6b3b12244b0fb    "><button type="button"  class="stanford ric-dasher" >For all other matters request a DASHER Consultation </button></a></p>
                </div>

            </div>

            <div  id="rit_block" >

                <!--div class="panel-body hero">
                    <div >
                        <h3 id="rchelpbanner">Get help with REDCap</h3>
                        <h3 id="stridehelpbanner">Get help with CHOIR, Research Patient Cohort Discovery / Chart Review, or mHealth / LHS</h3>
                    </div>
                </div-->

                <div class="panel" id="new_project_panel" >
                    <div class="panel-heading">Research Project Description</div>
                    <div class="panel-body">
                        <input type="hidden" id="brand" name="brand" value="researchit.png">
                        <input type="hidden" id="brandurl" name="brandurl" value="http://med.stanford.edu/researchit.html">

                        <input type="hidden" id="salesforceEmail" name="salesforceEmail" value="rit-support@stanford.edu">
                        <input type="hidden" id="org" name="org" value="Research IT">
                        <input type="hidden" id="CustomOrigin__c" name="CustomOrigin__c" value="Research-IT V1">
                        <!--span class="help-block">Please tell us about the project that you are working on.</span-->
                        <div class="col-md-6">
                            <div class="form-group input-group">
                                <span class='input-group-addon' id='contact_label'>Project Main Contact Name</span>
                                <input id="contact" name="contact" class="form-control" aria-describedby="contact_label" value="<?php print $ldapResult->{'displayname'} ?>" >
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <div class="form-group input-group">
                                <span class="input-group-addon" id="contactEmail_label">Main Contact E-mail</span>
                                <input id="contactEmail" name="contactEmail" type="text" class="form-control" value="<?php print $ldapResult->{'mail'} ?>" aria-describedby="contactEmail_label" >
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <div class="form-group input-group">
                                <span class="input-group-addon" id="phone_label">Main Contact Phone</span>
                                <input id="contactPhone" name="contactPhone" type="text" class="form-control" value="<?php print $ldapResult->{'telephonenumber'} ?>" aria-describedby="phone_label" >
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <div class="form-group input-group">
                                <span class="input-group-addon" id="iAppt_label">Main Contact Affiliation</span>
                                <select id="iAppt" name="iAppt" class="form-control" aria-describedby="iAppt_label" >
                                    <option value=""></option>
                                    <?php
                                    $enums = $utils->parseDDEnum($dd_array['appointment']['select_choices_or_calculations']);
                                    $sufaculty = strpos($ldapResult->{'suaffiliation'}, 'faculty') !== FALSE;

                                    foreach ($enums as $k => $v) {
                                        if ($k == 1) continue;
                                        $selected = ($sufaculty && strpos($v, 'Medicine Faculty') !== FALSE ? 'selected' : '');
                                        print("            <option value='$k' $selected>$v</option>");
                                    }
                                    ?>
                                </select>
                            </div>

                        </div>

                        <div class="col-sm-6">
                            <div class=" form-group input-group">
                                <label class="input-group-addon" id="curatedDepartment_label">Department</label>
                                <select class="form-control" id="curatedDepartment"  name="curated_department" aria-describedby="curatedDepartment_label" >
                                    <option value=""></option>
                                    <?php
                                    $enums = $utils->parseDDEnum($dd_array['curated_department']['select_choices_or_calculations']);
                                    foreach ($enums as $k => $v) {
                                        print("            <option value='$k'>$v</option>");
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <div class="form-group input-group" >
                                <label class="input-group-addon " id="rco_label">Is this research?</label>
                                <select class="form-control" id="research"  name="research" aria-describedby="rco_label" >
                                    <option value=""></option>
                                    <option value="1">Yes</option>
                                    <option value='0'>No</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group input-group hidden" id="irb_block">
                                <span class="input-group-addon "  id="irb_label">IRB #</span>
                                <input type="text" class="form-control" id="irb_number"  name="irb_number" aria-describedby="irb_label">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group input-group hidden" id="areyouthepi_block">
                                <label class="input-group-addon " id="iAmPI_label">Is the main contact also the PI / faculty sponsor?</label>
                                <select class="form-control" id="iAmPI"  name="iAmPI" aria-describedby="iAmPI_label" >
                                    <option value=""></option>
                                    <option value="1">Yes</option>
                                    <option value='0'>No</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-12">
                            <div class="form-group input-group hidden" id="pi_block">
                                <span class="input-group-addon "  id="pi_label">Who is the PI / faculty sponsor?</span>
                                <input type="text" placeholder="LastName, First Name" class="form-control" id="pi"  name="pi" aria-describedby="pi_label">
                            </div>
                        </div>
                        <div class="col-sm-12">
                            <div class=" form-group input-group">
                                <label class="input-group-addon" id="projectTitle_label">Project Short Name</label>
                                <input type="text" class="form-control" maxlength="100" placeholder="Short project name or acroynm"  id="projectTitle"  name="projectTitle" >
                            </div>
                        </div>

                        <!-- Textarea -->
                        <div class="col-sm-12">
                            <div class="form-group input-group">
                                <label class="input-group-addon"  id="description_label">Project Description</label>

                                <textarea rows="3" class="form-control" placeholder="Please describe the project, not your request. There is a separate box for request details below." id="description" name="description" ></textarea>

                            </div>

                        </div>
                    </div>
                </div>

                <div class="panel"  >
                    <div class="panel-heading">Today's Request</div>
                    <div class="col-sm-12">
                        <div class="form-group input-group">

                            <!-- remove this next field if and when the project lookup feature is re-instated  -->
                            <input id="newCase" name="serviceProjectRecordId" type="hidden" value="0"/>

                            <input id="sunetid" name="sunetid" type="hidden" value="<?php print $sunetid ?>">
                            <input id="requestorEmail" name="requestorEmail" type="hidden" value="<?php print $ldapResult->{'mail'} ?>">
                            <input id="requestorName" name="requestorName" type="hidden" value="<?php print $ldapResult->{'displayname'} ?>">
                            <input id="requestorPhone" name="requestorPhone" type="hidden" value="<?php print $ldapResult->{'telephonenumber'} ?>">
                            <input id="requestorAffiliation" name="requestorAffiliation" type="hidden" value="<?php print $ldapResult->{'suaffiliation'} ?>">
                            <input id="requestorOu" name="requestorOu" type="hidden" value="<?php print $ldapResult->{'ou'} ?>">
                            <input id="department" name="department" type="hidden" value="<?php print $ldapResult->{'ou'} ?>">

                        </div>
                    </div>
                    <div class="panel-body">
                        <!-- Textarea -->
                        <div class="col-sm-12">
                            <div class="form-group input-group">
                                <label class="input-group-addon"  id="inquiryDetail_label">Problem description</label>
                                <textarea rows="4" class="form-control" placeholder="What do you need from us today?" id="inquiryDetail" name="inquiryDetail" aria-describedby="inquiryDetail_label" ></textarea>
                            </div>
                        </div>

                        <!-- Textarea -->
                        <div class="col-sm-12">
                            <div class="form-group input-group">
                                <label class="input-group-addon"  id="availability_label">Let's connect!</label>
                                <textarea rows="2" class="form-control" placeholder="Our usual response is to reply via email. If you would prefer a phone call or in-person meeting, please indicate your availability and preference" id="availability" name="availability" aria-describedby="availability_label" ></textarea>
                            </div>
                        </div>

                        <div class="col-md-3 mb-3">
                            <div class="form-group custom-control custom-checkbox mb-3">
                                <input type="checkbox" class="custom-control-input" id="proj_type_redcap" name="proj_type_redcap" onclick="selectQueue()">
                                <span class="custom-control-label" for="proj_type_redcap">REDCap</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group custom-control custom-checkbox mb-3">
                                <input type="checkbox" class="custom-control-input" id="proj_type_choir" name="proj_type_choir" onclick="selectQueue()">
                                <span class="custom-control-label" for="proj_type_choir">CHOIR</span>
                            </div>
                        </div>
                        <!--span class="help-block">Please tell us about the project that you are working on.</span-->
                        <div class="col-md-4">
                            <div class="form-group custom-control custom-checkbox mb-3">
                                <input type="checkbox" class="custom-control-input" id="proj_type_stride" name="proj_type_stride" onclick="selectQueue()">
                                <span class="custom-control-label" for="proj_type_stride">Cohort Discovery / Chart Review</span>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group custom-control custom-checkbox mb-3">
                                <input type="checkbox" class="custom-control-input" id="proj_type_other" name="proj_type_other" onclick="selectQueue()">
                                <span class="custom-control-label" for="proj_type_other">Other</span>
                            </div>
                        </div>

                        <div id='redcap_pid_block' class="hidden">
                            <div class="col-sm-6">
                                <div class=" form-group input-group">
                                    <label class="input-group-addon" id="redcapPid_label">REDCap Project ID (PID)</label>
                                    <input type="text" class="form-control"  id="redcapPid"  name="redcapPid" aria-describedby="redcapPid_label" <?php if ($rc_inq) echo 'required';?> >
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <span class="help-block">Please supply your PID.</span>
                            </div>
                            <div class="col-sm-12">
                                <span class="help-block">Your PID is the number after "pid=" in the address bar of your browser of your REDCap project: e.g. https://redcap.stanford.edu/redcap_v8.11.3/index.php?pid=<font color="LightCoral">1234</font></span>
                            </div>
                        </div>

                        <div id='other_block' class="hidden">
                            <div class="col-sm-6">
                                <span class="help-block">View the <a href="https://med.stanford.edu/researchit/use-cases.html">other initiatives we support</a>.</span>
                            </div>
                        </div>

                        <!-- Button -->
                        <div class="col-sm-12">&nbsp;</div>
                        <div class="col-sm-12">
                            <div class="form-group input-group">
                                <button type="submit" id="singlebutton" name="singlebutton" class="btn btn-stanford">Submit Request</button>
                            </div>
                        </div>

                        <div class="col-sm-12">&nbsp;</div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>

    function showRequestForm() {
        $("#rit_block").removeClass('hidden');
        $("#buttonbar").addClass('hidden');
        $("#rchelpbanner").removeClass('hidden');
        $("#stridehelpbanner").addClass('hidden');
    }

    function selectQueue() {
        $("#redcap_pid_block").addClass('hidden');
        $("#other_block").addClass('hidden');
        if ($("#proj_type_redcap:checked").length > 0) {
            $("#redcap_pid_block").removeClass('hidden');
            $("#QueueName__c").val("queuename=REDCap Queue;shortname=REDCap Help;longname=REDCap Support;url=http://redcap.stanford.edu/redcap/plugins/gethelp/redcap-support.html;email=rit-support@stanford.edu");
        }
        if ($("#proj_type_choir:checked").length > 0) {
            $("#QueueName__c").val("queuename=Research IT Queue;shortname=RIT Help;longname=Research IT Support;url=http://redcap.stanford.edu/redcap/plugins/gethelp/choir-support.html;email=rit-support@stanford.edu");
        }
        if ($("#proj_type_stride:checked").length > 0) {
            $("#QueueName__c").val("queuename=Research IT Queue;shortname=RIT Help;longname=Research IT Support;url=http://redcap.stanford.edu/redcap/plugins/gethelp/stride-support.html;email=rit-support@stanford.edu");
        }
        if ($("#proj_type_other:checked").length > 0) {
            $("#other_block").removeClass('hidden');
            $("#QueueName__c").val("queuename=Research IT Queue;shortname=RIT Help;longname=Research IT Support;url=http://redcap.stanford.edu/redcap/plugins/gethelp/rit-support.html;email=rit-support@stanford.edu");
        }
    }
    /*
    QUEUE_REDCAP = 'queuename=REDCap Queue;shortname=REDCap Help;longname=REDCap Support;url=http://redcap.stanford.edu/redcap/plugins/gethelp/redcap.php;email=redcap-help@stanford.edu';
    QUEUE_RESEARCH_IT = 'queuename=Research IT Queue;shortname=Research IT;longname=Research IT;url=http://redcap.stanford.edu/redcap/plugins/gethelp/rit.php;email=rit-support@stanford.edu';

    * */

    function showStride() {
        $("#rit_block").removeClass('hidden');
        $("#buttonbar").addClass('hidden');
        $("#redcap_pid_block").addClass('hidden');
        $("#rchelpbanner").addClass('hidden');
        $("#stridehelpbanner").removeClass('hidden');
    }

    function checkRequired() {
        n = $('#newproject:checked').length;

        if (n > 0 ) {

            $("#contact").prop('required',true);
            $("#contactEmail").prop('required',true);
            $("#contactPhone").prop('required',true);
            $("#iAppt").prop('required',true);
            $("#curatedDepartment").prop('required',true);
            $("#funding").prop('required',true);
            $("#pubplan").prop('required',true);
            $("#research").prop('required',true);
            $("#description").prop('required',true);
            $("#projectTitle").prop('required',true);
        } else {

            $("#contact").prop('required',false);
            $("#contactEmail").prop('required',false);
            $("#contactPhone").prop('required',false);
            $("#iAppt").prop('required',false);
            $("#curatedDepartment").prop('required',false);
            $("#funding").prop('required',false);
            $("#pubplan").prop('required',false);
            $("#research").prop('required',false);
            $("#description").prop('required',false);
            $("#projectTitle").prop('required',false);
        }
    }
    <?php if ($prj_type) { ?>
    $("#rit_block").removeClass('hidden');
    $("#buttonbar").addClass('hidden');
    <?php if ($rc_inq) { ?>
    $("#proj_type_redcap").prop('checked', true);
    <?php } else { ?>
    $("#proj_type_redcap").prop('checked', false);
    <?php }  ?>
    <?php if ($choir_inq) { ?>
    $("#proj_type_choir").prop('checked', true);
    <?php } else { ?>
    $("#proj_type_choir").prop('checked', false);
    <?php }  ?>
    <?php if ($stride_inq) { ?>
    $("#proj_type_stride").prop('checked', true);
    <?php } else { ?>
    $("#proj_type_stride").prop('checked', false);
    <?php }  ?>
    selectQueue();
    <?php } else { ?>
    $("#rit_block").addClass('hidden');
    $("#buttonbar").removeClass('hidden');
    <?php }  ?>

    $('#research').on('change', function() {
        if ($("#research").val() === '1') {
            $("#areyouthepi_block").removeClass('hidden');
            $("#iAmPI").prop('required',true);
            $("#irb_block").removeClass('hidden');
            $("#irb_number").prop('required',true);
        } else {
            $("#areyouthepi_block").addClass('hidden');
            $("#pi_block").addClass('hidden');
            $("#irb_block").addClass('hidden');
            $("#iAmPI").prop('required',false);
            $("#irb_number").prop('required',false);
        }
    });

    $('#iAmPI').on('change', function() {
        if ($("#iAmPI").val() === '0') {
            $("#pi_block").removeClass('hidden');
            $("#pi").prop('required',true);
        } else {
            $("#pi_block").addClass('hidden');
            $("#pi").prop('required',false);
        }
    });

    $('input[type=radio]').on('change', function() {
        if ($('#cohortid:checked').length > 0 || $('#epicdata:checked').length > 0 || $('#greenbutton:checked').length > 0 || $('#stats:checked').length > 0 || $('#compliance:checked').length > 0
            || $('#phs:checked').length > 0 || $('#srcc:checked').length > 0 ) {
            $("#rit_block").addClass('hidden');
        } else {
            $("#rit_block").removeClass('hidden');
        }

        checkRequired();
    });

    checkRequired();

</script>
</body>
</html>
