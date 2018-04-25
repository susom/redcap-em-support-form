<?php
namespace Stanford\SupportForm;
/** @var \Stanford\SupportForm\SupportForm $module */

// RENDER THE COMMON HEADER
?>
<!DOCTYPE html>
<html>
    <head>
        <title><?php echo $module->getModuleName() ?></title>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>

        <!-- Bootstrap core CSS -->
        <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet" media='screen' />

        <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
        <!--[if lt IE 9]>
        <!--<script src='https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js'></script>-->
        <!--<script src='https://oss.maxcdn.com/respond/1.4.2/respond.min.js'></script>-->
        <![endif]-->

        <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
        <script src="<?php print $module->getUrl("js/jquery-3.2.1.min.js",false,true) ?>"></script>

        <!-- Include all compiled plugins (below), or include individual files as needed -->
        <script src='https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js'></script>

        <!-- Include DataTables for Bootstrap -->
        <script src="<?php print $module->getUrl("js/datatables.min.js", false,true) ?>"></script>
        <link href="<?php print $module->getUrl('css/datatables.min.css', false, true) ?>"  rel="stylesheet" type="text/css" media="screen,print"/>

        <!-- Include Select2 -->
        <script src="<?php print $module->getUrl("js/select2.full.min.js", false,true) ?>"></script>
        <link href="<?php print $module->getUrl('css/select2.min.css', false, true) ?>"  rel="stylesheet" type="text/css" media="screen,print"/>
        <link href="<?php print $module->getUrl('css/select2-bootstrap.min.css', false, true) ?>"  rel="stylesheet" type="text/css" media="screen,print"/>

        <!-- Add local css and js for module -->
        <!--    <link href="" rel="stylesheet" type="text/css" media="screen,print"/>-->
        <!--    <script src=''></script>-->

    </head>
    <body>
