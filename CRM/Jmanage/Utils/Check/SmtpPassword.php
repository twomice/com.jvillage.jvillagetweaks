<?php

class CRM_Jmanage_Utils_Check_SmtpPassword {
  function check(&$messages) {
    $error = FALSE;
    $mailingInfo = Civi::settings()->get('mailing_backend');

    if (defined('CIVICRM_MAIL_LOG')) {
      $error = TRUE;
      $messages[] = new CRM_Utils_Check_Message(
        'jvillagetweaks_smtppassword',
        ts('Outgoing emails are blocked and are being logged in a text file.'),
        ts('Email disabled'),
        \Psr\Log\LogLevel::INFO,
        'fa-flag'
      );
    }
    elseif ($mailingInfo['outBound_option'] == CRM_Mailing_Config::OUTBOUND_OPTION_REDIRECT_TO_DB) {
      $error = TRUE;
      $messages[] = new CRM_Utils_Check_Message(
        'jvillagetweaks_smtppassword',
        ts('Outgoing emails are being logged to the database'),
        ts('Email logged to DB'),
        \Psr\Log\LogLevel::INFO,
        'fa-flag'
      );
    }
    else {
      if ($mailingInfo['outBound_option'] == CRM_Mailing_Config::OUTBOUND_OPTION_SMTP) {
        $password = CRM_Utils_Crypt::decrypt($mailingInfo['smtpPassword']);

        if (preg_match("/[^!-~]/", $password)) {
          $error = TRUE;
          $messages[] = new CRM_Utils_Check_Message(
            'jvillagetweaks_smtppassword',
            ts('<a %1>Outgoing emails</a> password seems badly encrypted.', array(1 => 'href="' . CRM_Utils_System::url('civicrm/admin/setting/smtp', array('reset' => 1)) . '"')),
            ts('Invalid outbound email password'),
            \Psr\Log\LogLevel::CRITICAL,
            'fa-flag'
          );
        }
      }
      else {
        $error = TRUE;
        $messages[] = new CRM_Utils_Check_Message(
          'jvillagetweaks_smtppassword',
          ts('Outgoing emails are not using SMTP delivery.'),
          ts('Invalid outbound email method'),
          \Psr\Log\LogLevel::CRITICAL,
          'fa-flag'
        );
      }
    }

    if (! $error) {
      $messages[] = new CRM_Utils_Check_Message(
        'jvillagetweaks_smtppassword',
        ts('Outgoing emails seem correctly configured. You can test it from the outgoing emails settings page or by sending an email to a test contact.'),
        ts('Outgoing emails'),
        \Psr\Log\LogLevel::INFO,
        'fa-check'
      );
    }
  }
}
