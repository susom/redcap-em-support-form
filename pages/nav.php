<?php
namespace Stanford\SupportForm;
/** @var \Stanford\SupportForm\SupportForm $module */

// RENDER THE COMMON NAV
?>
<div class='container-fluid'>
    <nav class="navbar navbar-default">
        <div class="container">
            <div class="navbar-header">
                <!-- Hamburger Button for smaller screens -->
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="#"><strong><?php echo $module->getModuleName() ?></strong></a>
            </div>
            <div id="navbar" class="navbar-collapse collapse">
                <ul class="nav navbar-nav navbar-right">
                    <li>
                        <a href="#" data-toggle="modal" data-target="#reportModal">
                            <span class="glyphicon glyphicon-question-sign icon-size"></span>
                            <strong>Report an Issue</strong>
                        </a>
                    </li>
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                            <span class="glyphicon glyphicon-user"></span> 
                            <strong><?php echo $sunet;?></strong>
                            <span class="glyphicon glyphicon-chevron-down"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <div class="navbar-login">
                                <div class="row">
                                    <div class="col-lg-12 text-center">
                                        <p>
                                            <span class="glyphicon glyphicon-user icon-size"></span>
                                        </p>
                                        <?php if (true) { ?>
                                            <div style="padding:5px;">
                                                <p><strong><?php echo "name" ?></strong></p>
                                                <p class="small"><?php echo "email" ?></p>
                                                <p class="small"><?php echo "phone" ?></p>
                                                <p class="">
                                                    <a href="#" id="editCurrentClinicianAssessments" class="btn btn-primary btn-block btn-xs">Edit My Default Assessments</a>
                                                </p>
                                                <p class="">
                                                    <a href="#" id="editMyNotificationPrefs" class="btn btn-primary btn-block btn-xs">Edit My Notification Prefs</a>
                                                </p>
                                            </div>
                                        <?php } else { ?>
                                            <p class="small">Blah.</p>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</div>
