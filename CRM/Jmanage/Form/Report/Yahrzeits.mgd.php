<?php
// This file declares a managed database record of type "ReportTemplate".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// http://wiki.civicrm.org/confluence/display/CRMDOC42/Hook+Reference
return array (
  0 => 
  array (
    'name' => 'CRM_Jmanage_Form_Report_UpcomingYahrzeits',
    'entity' => 'ReportTemplate',
    'params' => 
    array (
      'version' => 3,
      'label' => 'Yahrzeit Report',
      'description' => 'Yahrzeit Report',
      'class_name' => 'CRM_Jmanage_Form_Report_Yahrzeits',
      'report_url' => 'com.jvillage.jvillagetweaks/yahrzeits',
      'component' => '',
    ),
  ),
);