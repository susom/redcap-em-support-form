# redcap-em-support-form
Collect customer-supplied information and use it to create tickets in Salesforce

This EM is used by two sites, [Research IT](https://redcap.stanford.edu/redcap_v10.6.3/ExternalModules/?prefix=support_form&page=help&pid=13941&NOAUTH)
and the [Research Informatics Center](https://redcap.stanford.edu/plugins/gethelp/ric.php). Both sites
refer to the legacy plugin 'gethelp' ; both plugin endpoints now redirect to the appropriate endpoint in this EM.

The magic for both orgs is the integration with Salesforce. On survey completion the post handler builds up an email
consisting of base 64 encoded JSON that is sent to the Salesforce email handler for the SoM org. 
The handler decodes the message, parses the JSON, and creates a new case from the customer-submitted information.

## Research IT
The help form used by Research IT is rendered by help.php in this EM; the results are processed by submit_v2.php

## Research Informatics Center
In order to make it easier for the RIC to customize their intake form, the pattern used is slightly different:
the RIC's intake form is the survey in PID 22082 styled with Shazam. That way they can use the REDCap
Designer to modify their questions, and still keep the custom integration with Salesforce.  The EM
hook redcap_survey_complete is used to send the email to Salesforce. 