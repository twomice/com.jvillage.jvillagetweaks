<?php

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Jmanage_Admin_Form_Settings extends CRM_Core_Form {
  function setDefaultValues() {
    $defaults = $this->_values;
    $defaults['jvillagetweaks_fontsizepdf'] = Civi::settings()->get('jvillagetweaks_fontsizepdf');

    if (! $defaults['jvillagetweaks_fontsizepdf']) {
      $defaults['jvillagetweaks_fontsizepdf'] = '14px';
    }

    return $defaults;
  }

  public function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Settings - Jmanage'));

    $this->add('text', 'jvillagetweaks_fontsizepdf',
      ts('Font Size for Financial Tokens', array('domain' => 'com.jvillage.jvillagetweaks')),
      TRUE);

    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => ts('Submit'),
        'isDefault' => TRUE,
      ),
    ));

    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());
    parent::buildQuickForm();
  }

  public function postProcess() {
    $values = $this->exportValues();

    $fontsize = $values['jvillagetweaks_fontsizepdf'];
    Civi::settings()->set('jvillagetweaks_fontsizepdf', $fontsize);

    CRM_Core_Session::setStatus(ts('Settings Saved'), '', 'success');
    parent::postProcess();
  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  public function getRenderableElementNames() {
    // The _elements list includes some items which should not be
    // auto-rendered in the loop -- such as "qfKey" and "buttons".  These
    // items don't have labels.  We'll identify renderable by filtering on
    // the 'label'.
    $elementNames = array();
    foreach ($this->_elements as $element) {
      /** @var HTML_QuickForm_Element $element */
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }

}
