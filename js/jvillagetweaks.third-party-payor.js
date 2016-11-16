/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

CRM.$(document).on('crmLoad', function() {
  // On load, store original values, and set an on-change handler for the
  // Third Party Payor field.
  jvillagetweaks_thirdpartypayor.prime();
  CRM.$("#custom_" + CRM.vars.jvillagetweaks.thirdpartypayor_custom_field_id + "_-1").on('change.thirdpartypayor', function(){jvillagetweaks_thirdpartypayor.update(CRM.$(this).val());});
})

CRM.$(document).ajaxComplete(function( event, xhr, settings ) {
  // When any AJAX call is completed, if it's the call to retrieve states for
  // a certain country, assume that we're updating the Country/State chain
  // select pair; this is the time to apply any "State" field value. If it's set
  // any earlier, the value will simply be removed by the chain-select behavior.
  if (settings.url.lastIndexOf('/civicrm/ajax/jqState?', 0) == 0) {
    jvillagetweaks_thirdpartypayor.setPendingState();
  }
});

// Intended value of the "State" field. This value must be held temporarily and
// applied to the field only after the Country field is updated.
var jvillagetweaks_thirdpartypayor_pendingStateId;

// Object defining methods to handle Third Party Payor field changes.
var jvillagetweaks_thirdpartypayor = {
  /**
   * Populate the "State" field with any value that's been held pending update
   * of the Country field.
   */
  setPendingState: function() {
    CRM.$('#billing_state_province_id-5').val(jvillagetweaks_thirdpartypayor_pendingStateId).change();
  },

  /**
   * Store original billing-address values for beneficiary.
   */
  prime: function() {
    this.street = CRM.$('#billing_street_address-5').val();
    this.city = CRM.$('#billing_city-5').val();
    this.country = CRM.$('#billing_country_id-5').val();
    this.state = CRM.$('#billing_state_province_id-5').val();
    this.postal = CRM.$('#billing_postal_code-5').val();
    this.first = CRM.$('#billing_first_name').val();
    this.middle = CRM.$('#billing_middle_name').val();
    this.last = CRM.$('#billing_last_name').val();
  },

  /**
   * Respond to changes in the value of the Third Party Payor field.
   */
  update: function (contact_id) {
    if (!contact_id) {
      // If no Third Party Payor contact is selected, revert billing address
      // fields to their original values, remove on-screen alerts, and
      // revert "billing Name and Address" fieldset legend.
      jvillagetweaks_thirdpartypayor_pendingStateId = this.state;
      CRM.$('#billing_street_address-5').val(this.street).change();
      CRM.$('#billing_city-5').val(this.city).change();
      CRM.$('#billing_state_province_id-5').val('').change();
      CRM.$('#billing_country_id-5').val(this.country).change();
      CRM.$('#billing_postal_code-5').val(this.postal).change();
      CRM.$('#billing_first_name').val(this.first).change();
      CRM.$('#billing_middle_name').val(this.middle).change();
      CRM.$('#billing_last_name').val(this.last).change();
      CRM.$('fieldset.billing_name_address-group legend').html('Billing Name and Address');
      CRM.$('#jvillagetweaks-thirdpartypayor-alert').remove();
    }
    else {
      // If a Third Party Payor contact is selected, retrieve name and billing
      // address values and populate them in the billing address fields; also
      // display an on-screen alert, and update the "billing Name and Address"
      // fieldset legend.
      CRM.api3('address', 'get', {
        'sequential': 1,
        'location_type_id': "Billing",
        'contact_id': contact_id,
        'options': {'sort': "id desc"}
      })
      .done(function(result){
        var values = result.values[0]
        jvillagetweaks_thirdpartypayor_pendingStateId = values.state_province_id;
        legend = 'Billing Name and Address';
        CRM.$('#billing_street_address-5').val(values.street_address).change();
        CRM.$('#billing_city-5').val(values.city).change();
        CRM.$('#billing_state_province_id-5').val('').change();
        CRM.$('#billing_country_id-5').val(values.country_id).change();
        CRM.$('#billing_postal_code-5').val(values.postal_code).change();
      });
      CRM.api3('contact', 'get', {
        'sequential': 1,
        'id': contact_id,
      })
      .done(function(result){
        var values = result.values[0]
        CRM.$('#billing_first_name').val(values.first_name).change();
        CRM.$('#billing_middle_name').val(values.middle_name).change();
        CRM.$('#billing_last_name').val(values.last_name).change();
      });
      CRM.$('fieldset.billing_name_address-group legend').html('Third Party Payor: Billing Name and Address');
      CRM.$('#jvillagetweaks-thirdpartypayor-alert').remove();
      CRM.$('#s2id_custom_' + CRM.vars.jvillagetweaks.thirdpartypayor_custom_field_id +'_-1').after('<div id="jvillagetweaks-thirdpartypayor-alert" class="crm-error">Third Party Payor selected. Please review the "Third Party Payor: Billing Name and Address" section above.</div>')
    }
  }
}