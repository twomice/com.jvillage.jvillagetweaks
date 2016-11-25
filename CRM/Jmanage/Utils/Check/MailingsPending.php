<?php

class CRM_Jmanage_Utils_Check_MailingsPending {
  function check(&$messages) {
    $error = FALSE;

    $sql = 'SELECT MAX(UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(scheduled_date)) as delta
              FROM civicrm_mailing_job
             WHERE start_date is NULL
               AND status = %1
               AND (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(scheduled_date)) > 1800';

    $dao = CRM_Core_DAO::executeQuery($sql, array(
      1 => array('Scheduled', 'String'),
    ));

    $dao->fetch();

    if (empty($dao->delta)) {
      $messages[] = new CRM_Utils_Check_Message(
        'jvillagetweaks_mailingspending',
        ts('No mailings are pending to be processed.'),
        ts('Mailings OK'),
        \Psr\Log\LogLevel::INFO,
        'fa-check'
      );
    }
    elseif ($dao->delta < 900) {
      $delta = round($dao->delta/60);

      $messages[] = new CRM_Utils_Check_Message(
        'jvillagetweaks_mailingspending',
        ts('Mailings have been waiting to be processed for the past %1 minutes.', array(1 => $delta)),
        ts('Mailings pending'),
        \Psr\Log\LogLevel::INFO,
        'fa-flag'
      );
    }
    elseif ($dao->delta < 1800) {
      $delta = round($dao->delta/60);

      $messages[] = new CRM_Utils_Check_Message(
        'jvillagetweaks_mailingspending',
        ts('Mailings have been waiting to be processed for the past %1 minutes.', array(1 => $delta)),
        ts('Mailings pending'),
        \Psr\Log\LogLevel::WARNING,
        'fa-flag'
      );
    }
    else {
      $delta = round($dao->delta/60);

      $messages[] = new CRM_Utils_Check_Message(
        'jvillagetweaks_mailingspending',
        ts('Mailings have been waiting to be processed for the past %1 minutes.', array(1 => $delta)),
        ts('Mailings pending'),
        \Psr\Log\LogLevel::CRITICAL,
        'fa-flag'
      );
    }
  }
}
