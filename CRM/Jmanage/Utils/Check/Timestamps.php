<?php

class CRM_Jmanage_Utils_Check_Timestamps {
  function check(&$messages) {
    $error = FALSE;

    //CRM-19115 - Always set MySQL time before checking it.
    CRM_Core_Config::singleton()->userSystem->setMySQLTimeZone();

    $phpNow = date('Y-m-d H:i');
    $sqlNow = CRM_Core_DAO::singleValueQuery("SELECT date_format(now(), '%Y-%m-%d %H:%i')");

    $tzstring = CRM_Utils_System::getTimeZoneString();

    if ($phpNow == $sqlNow) {
      $messages[] = new CRM_Utils_Check_Message(
        'jvillagetweaks_timestamps',
        ts('PHP/MySQL timestamps are OK: PHP %1 vs MySQL %2, timezone = %3', array(1 => $phpNow, 2 => $sqlNow, 3 => $tzstring)),
        ts('Timestamps'),
        \Psr\Log\LogLevel::INFO,
        'fa-check'
      );
    }
    else {
      $messages[] = new CRM_Utils_Check_Message(
        'jvillagetweaks_timestamps',
        ts('PHP/MySQL timestamps ERROR: PHP %1 vs MySQL %2, timezone = %3', array(1 => $phpNow, 2 => $sqlNow, 3 => $tzstring)),
        ts('Please send this to Jvillage in a support ticket: Timestamps: PHP %1, MySQL %2, tz %3', array(1 => $phpNow, 2 => $sqlNow, 3 => $tzstring)),
        \Psr\Log\LogLevel::CRITICAL,
        'fa-check'
      );
    }
  }
}
