<?php
// This file declares a managed database record of type "CustomSearch".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed
return [
  [
    'name' => 'CRM_Activityloggingreport_Form_Search_ActivityLoggingSummary',
    'entity' => 'CustomSearch',
    'params' => [
      'version' => 3,
      'label' => 'Activity Logging Summary',
      'description' => 'Activity Logging Summary',
      'class_name' => 'CRM_Activityloggingreport_Form_Search_ActivityLoggingSummary',
    ],
  ],
];
