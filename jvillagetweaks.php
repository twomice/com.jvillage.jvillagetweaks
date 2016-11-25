<?php

require_once 'jvillagetweaks.civix.php';

/**
 * Implements hook_civicrm_validateForm().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_validateForm
 */
function jvillagetweaks_civicrm_validateForm($formName, &$fields, &$files, &$form, &$errors) {
  // Only when creating a new credit card contribution in the back-office area.
  if (
    $formName == 'CRM_Contribute_Form_Contribution'
    && $form->action = CRM_Core_Action::ADD
    && $form->isBackOffice == 1 
    && array_key_exists('billing_street_address-5', $form->_paymentFields)
  ) {
    // Only if Third Party Payor is given:
    $custom_field_info = _jvillagetweaks_get_custom_field_info('Extra Contribution Info', 'Third Party Payor');
    if (!empty($custom_field_info['field_id']) && !empty($fields["custom_{$custom_field_info['field_id']}_-1"])) {
      // Prevent billing address fields from being saved for this contact;
      // store them in the form, to be written elsewhere to the billing address
      // of the Third Party Payor contact (see jvillagetweaks_civicrm_postProcess).
      //
      // From the hook documentation:
      // "The hook is intended for validation rather than altering form values.
      // However, should you need to alter submitted values you need to access the
      // controller container object"
      $data = &$form->controller->container();
      $field_keys = array(
        'billing_street_address-5',
        'billing_city-5',
        'billing_country_id-5',
        'billing_state_province_id-5',
        'billing_postal_code-5',
      );
      $payor_billing_address_fields = array();
      foreach ($field_keys as $field_key) {
        $payor_billing_address_fields[$field_key] = $data['values']['Contribution'][$field_key];
        unset($data['values']['Contribution'][$field_key]);
      }
      $form->_jvillagetweaks_payor_billing_address_fields = $payor_billing_address_fields;
    }
  }
}


/**
 * Implements hook_civicrm_postProcess().
 *
 * @param string $formName
 * @param CRM_Core_Form $form
 */
function jvillagetweaks_civicrm_postProcess($formName, &$form) {
  // Only when creating a new credit card contribution in the back-office area.
  if (
    $formName == 'CRM_Contribute_Form_Contribution'
    && $form->action = CRM_Core_Action::ADD
    && $form->isBackOffice == 1
    && array_key_exists('billing_street_address-5', $form->_paymentFields)
  ) {
    // Only if Third Party Payor is given:
    $custom_field_info = _jvillagetweaks_get_custom_field_info('Extra Contribution Info', 'Third Party Payor');
    if (!empty($custom_field_info['field_id']) && !empty($form->_submitValues["custom_{$custom_field_info['field_id']}_-1"])) {
      $contact_id = $form->_submitValues["custom_{$custom_field_info['field_id']}_-1"];
      // Get most recent billing address for Third Party Payor, if any.
      $address_id = NULL;
      $result = civicrm_api3('Address', 'get', array(
        'sequential' => 1,
        'location_type_id' => "Billing",
        'contact_id' => $contact_id,
        'options' => array('sort' => "id desc"),
      ));
      if ($result['count']) {
        $address_id = $result['values'][0]['id'];
      }

      // Update or save billing address.
      $result = civicrm_api3('Address', 'create', array(
        'contact_id' => $contact_id,
        'id' => $address_id,
        'location_type_id' => "Billing",
        'street_address' => $form->_jvillagetweaks_payor_billing_address_fields['billing_street_address-5'],
        'city' => $form->_jvillagetweaks_payor_billing_address_fields['billing_city-5'],
        'country_id' => $form->_jvillagetweaks_payor_billing_address_fields['billing_country_id-5'],
        'state_province_id' => $form->_jvillagetweaks_payor_billing_address_fields['billing_state_province_id-5'],
        'postal_code' => $form->_jvillagetweaks_payor_billing_address_fields['billing_postal_code-5'],
        'is_billing' => 1,
      ));
    }
  }
}

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

  // The above is a silly assumption for prod, but on dev platforms
  // this can be very annoying (ex: redmine:1164).
  if (! empty($_SERVER['DOCUMENT_ROOT'])) {
    $root = $_SERVER['DOCUMENT_ROOT'];
  }

  // redmine:766
  // Even if this is set above, it was not being set correctly in
  // packages/kcfinder/integration/civicrm.php
  $config->imageUploadURL = "https://$host/sites/$host/files/";
  $config->imageUploadDir = "$root/sites/$host/files/";

  // redmine #862, #931 and others: custom CSS for admin interface.
  if (empty($_REQUEST['snippet'])) {
    CRM_Core_Resources::singleton()->addStyleFile('com.jvillage.jvillagetweaks', 'css/admin-tweaks.css');
  }
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
  elseif ($formName == 'CRM_Contribute_Form_Contribution') {
    // Only when creating a new credit card contribution in the back-office area.
    if (
      $form->action = CRM_Core_Action::ADD
      && $form->isBackOffice == 1
      && array_key_exists('billing_street_address-5', $form->_paymentFields)
    ) {
      // Add JavaScript to facilitate on-screen alerts and address swapping
      // when Third Party Payor is selected.
      $custom_field_info = _jvillagetweaks_get_custom_field_info('Extra Contribution Info', 'Third Party Payor');
      CRM_Core_Resources::singleton()->addVars('jvillagetweaks', array(
        'thirdpartypayor_custom_field_id' => $custom_field_info['field_id'],
      ));
      CRM_Core_Resources::singleton()->addScriptFile('com.jvillage.jvillagetweaks', 'js/jvillagetweaks.third-party-payor.js');
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

/**
 * Get relevant metadata for a given custom-group/custom-field pair.
 * FIXME: This should probably be in a utility class somewhere (or maybe it
 * is already, somewhere).
 *
 * @param String $group_title Title of the custom group.
 * @param String $field_title Label of the custom field.
 * @return Keyed array of relevant metadata, each value defaulting to '' (empty string)
 *   if no matching field is found:
 *   'group_id': CiviCR system ID of the custom group
 *   'field_id': CiviCR system ID of the custom field
 */
function _jvillagetweaks_get_custom_field_info($group_title, $field_title) {
  static $cache;
  $key = "{$group_title}{$field_title}";
  if (!array_key_exists($key, $cache)) {
    // Default values are blank, in case no matching group/field is found.
    $cache[$key] = array(
      'group_id' => '',
      'field_id' => '',
    );
    $result = civicrm_api3('CustomGroup', 'getsingle', array(
      'title' => $group_title,
    ));
    if (!empty($result['id'])) {
      $custom_group_id = $result['id'];
      $result = civicrm_api3('CustomField', 'getsingle', array(
        'sequential' => 1,
        'custom_group_id' => $custom_group_id,
        'label' => $field_title,
      ));
      if (!empty($result['id'])) {
        $cache[$key]['group_id'] = $custom_group_id;
        $cache[$key]['field_id'] = $result['id'];
      }
    }
  }
  return $cache[$key];
}
