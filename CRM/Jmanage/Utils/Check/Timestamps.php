<?php

class CRM_Jmanage_Utils_Check_Timestamps {
  function check(&$messages) {
    $error = FALSE;

    $t = new CRM_Utils_Check_Component_Env();
    $msg = $t->checkMysqlTime();

    $messages = array_merge($msg, $messages);

    if (empty($msg)) {
      $phpNow = date('Y-m-d H:i');
      $sqlNow = CRM_Core_DAO::singleValueQuery("SELECT date_format(now(), '%Y-%m-%d %H:%i')");

      $messages[] = new CRM_Utils_Check_Message(
        'jvillagetweaks_timestamps',
        ts('PHP/MySQL timestamps are OK: PHP %1 vs MySQL %2', array(1 => $phpNow, 2 => $sqlNow)),
        ts('Timestamps'),
        \Psr\Log\LogLevel::INFO,
        'fa-check'
      );
    }
  }
}
