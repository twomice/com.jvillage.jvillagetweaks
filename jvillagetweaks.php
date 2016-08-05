<?php

require_once 'jvillagetweaks.civix.php';

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function jvillagetweaks_civicrm_config(&$config) {
  _jvillagetweaks_civix_civicrm_config($config);

  $host = $_SERVER['HTTP_HOST'];
  $root = '/var/aegir/platforms/civicrm-4.7';

  if ($config->userFramework == 'Drupal6') {
    $root = '/var/aegir/platforms/civicrm-4.7d6';
  }

  // redmine:766
  // Even if this is set above, it was not being set correctly in
  // packages/kcfinder/integration/civicrm.php
  $config->imageUploadURL = "https://$host/sites/$host/files/";
  $config->imageUploadDir = "$root/sites/$host/files/";

  // redmine #862, #931 and others: custom CSS for admin interface.
  CRM_Core_Resources::singleton()->addStyleFile('com.jvillage.jvillagetweaks', 'css/admin-tweaks.css');
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function jvillagetweaks_civicrm_xmlMenu(&$files) {
  _jvillagetweaks_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function jvillagetweaks_civicrm_install() {
  _jvillagetweaks_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function jvillagetweaks_civicrm_uninstall() {
  _jvillagetweaks_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function jvillagetweaks_civicrm_enable() {
  _jvillagetweaks_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function jvillagetweaks_civicrm_disable() {
  _jvillagetweaks_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed
 *   Based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function jvillagetweaks_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _jvillagetweaks_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function jvillagetweaks_civicrm_managed(&$entities) {
  _jvillagetweaks_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function jvillagetweaks_civicrm_caseTypes(&$caseTypes) {
  _jvillagetweaks_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function jvillagetweaks_civicrm_angularModules(&$angularModules) {
_jvillagetweaks_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function jvillagetweaks_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _jvillagetweaks_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Functions below this ship commented out. Uncomment as required.
 *

/**
 * Implements hook_civicrm_buildForm().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_buildForm
 */
function jvillagetweaks_civicrm_buildForm($formName, &$form) {
  if ($formName == 'CRM_Contribute_Form_Contribution_Main') {
    // Rename "Confirm Contribution" to just "Confirm".
    // Pogstone used to hack this directly on core.
    if ($form->elementExists('buttons')) {
      $buttons = $form->getElement('buttons');
      $buttons->_elements[0]->_attributes['value'] = ts('Confirm');
    }
  }
  elseif ($formName == 'CRM_Member_Form_MembershipType') {
    // redmine #862, allow to edit relationship types of existing memberships.
    if ($form->elementExists('relationship_type_id')) {
      $e = $form->getElement('relationship_type_id');
      $e->unfreeze();
    }
  }
}

/**
 * Implements hook_civicrm_pre().
 */
function jvillagetweaks_civicrm_pre($op, $objectName, $id, &$params) {
  // Redmine:723 Default to non-public mailings.
  // This was the default in CiviCRM 4.4, but was changed in 4.5+ (CRM-14716).
  if ($op == 'create' && $objectName == 'Mailing') {
    $params['visibility'] = 'User and User Admin Only';
  }
}

/**
 * Implements hook_coreResourceList().
 */
function jvillagetweaks_civicrm_coreResourceList(&$items, $region) {
  // Override the CKEditor config file location
  foreach ($items as $key => &$i) {
    if (isset($i['config']) && isset($i['config']['CKEditorCustomConfig'])) {
      unset($items[$key]);
    }
  }

  $items[] = array('config' => array(
    'CKEditorCustomConfig' => Civi::resources()->getUrl('com.jvillage.jvillagetweaks', 'js/crm-ckeditor-config-6.js')),
  );
}

/**
 * Implements hook_civicrm_check().
 */
function jvillagetweaks_civicrm_check(&$messages) {
  $checks = array(
    'SmtpPassword',
    'MailingsPending',
    'Timestamps',
  );

  foreach ($checks as $c) {
    $t = 'CRM_Jmanage_Utils_Check_' . $c;
    if (class_exists($t)) {
      $class = new $t();
      $class->check($messages);
    }
  }
}
