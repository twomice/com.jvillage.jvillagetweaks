<?php

/*
 +--------------------------------------------------------------------+
 | ShulSuite 1.0                                                      |
 +--------------------------------------------------------------------+
 | Copyright Pogstone Inc. (c) 2004-2009                              |
 +--------------------------------------------------------------------+
 | This file is a part of ShulSuite.                                  |
 |                                                                    |
 | see the license FAQ at http://www.shulsuite.com                    |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright Pogstone Inc. (c) 2004-2009
 * $Id$
 *
 */

class CRM_Contact_Form_Search_Custom_TaxYearContributorListing extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

  //protected $_formValues;
  //protected $_tableName = null;
  protected $_summary_type ;
  protected $_group_of_contact;
  protected $_order_type;

  function __construct(&$formValues) {
    parent::__construct($formValues);
    // $this->_formValues = $formValues;

    $order_type = $this->_formValues['order_type'];

    $this->_order_type = $order_type;

    /**
     * Define the columns for search result rows
     */
    $all_cols = array(
      ts('Name') => 'sort_name',
      ts('Last Contribution Date of Year') => 'last_contrib_date',
      ts('City') => 'city',
      ts('Postal Code') => 'postal_code',
    );

    $summary_type = $this->_formValues['summary_type'];

    $this->_summary_type = $summary_type;

    if ($summary_type == 'household') {
      $all_cols['Contact ID'] = 'contact_id';
    }
    else {
      $all_cols['Household Name'] = ts('hh_name');
      $all_cols['Household ID'] = ts('hh_id');
    }

    $this->_columns = $all_cols;
  }

  function buildForm(&$form) {
    /**
     * You can define a custom title for the search form
     */
    $this->setTitle('Tax Year Contributors');

    /**
     * Define the search form fields here
     */
    require_once ('utils/Entitlement.php');
    $entitlement = new Entitlement();

    require_once('utils/CustomSearchTools.php');
    $searchTools = new CustomSearchTools();
    //$group_ids = $searchTools::getRegularGroupsforSelectList();

    $group_ids = CRM_Core_PseudoConstant::group();
    /*
        $tmp_select = $form->add  ('select', 'group_of_individual', ts('Contact is in the group'),
                     $group_ids,
                     false);

        $tmp_select->setMultiple(true);


          $tmp_mem_select = $form->add  ('select', 'membership_type_of_contact', ts('Contact has the membership of type'),
                     $mem_ids,
                     false);

        $tmp_mem_select->setMultiple(true);
    */

    $org_ids = $searchTools->getMembershipOrgsforSelectList();
    $mem_ids = $searchTools->getMembershipsforSelectList();

    if ($entitlement->isRunningCiviCRM_4_5()) {
      $select2style = array(
        'multiple' => TRUE,
        'style' => 'width: 100%; max-width: 60em;',
        'class' => 'crm-select2',
        'placeholder' => ts('- select -'),
      );

      $form->add('select', 'group_of_contact',
        ts('Contact is in the group'),
        $group_ids,
        FALSE,
        $select2style
      );

      $form->add('select', 'membership_org_of_contact',
        ts('Contact has Membership In'),
        $org_ids,
        FALSE,
        $select2style
      );

      $form->add('select', 'membership_type_of_contact',
        ts('Contact has the membership of type'),
        $mem_ids,
        FALSE,
        $select2style
      );
    }
    else{
      $form->add('select', 'group_of_contact', ts('Contact is in the group'), $group_ids, FALSE, array(
        'id' => 'group_of_contact', 'multiple' => 'multiple', 'title' => ts('-- select --')));

      $form->add('select', 'membership_org_of_contact', ts('Contact has Membership In'), $org_ids, FALSE, array(
        'id' => 'membership_org_of_contact', 'multiple' => 'multiple', 'title' => ts('-- select --')));

      $form->add('select', 'membership_type_of_contact', ts('Contact has the membership of type'), $mem_ids, FALSE, array(
        'id' => 'membership_type_of_contact', 'multiple' => 'multiple', 'title' => ts('-- select --')));
    }

    $cur_year = date("Y");

    $year_choices = array('' => ' -- select year --');

    $tmp_year = $cur_year;
    $iter = 0;

    while ($iter < 10) {
      $tmp_year = $cur_year - $iter;
      $year_choices[$tmp_year] = "Calendar $tmp_year";
      $iter = $iter + 1;
    }

    $form->add('select', 'cal_year', ts('Tax Year'), $year_choices, FALSE);

    // Date range, and override the default label, add help and position the from/to dates below main widget.
    $this->buildFormAddDateRangeWidget($form);

    $tax_status_choices = array(
      '' => '',
      'any' => 'Any Status',
      'deductible_only' => "Deductibles Only (not commonly used)",
    );

    $form->add('select', 'deduct_prefs', ts('Tax Status'), $tax_status_choices, false);

    $comm_prefs =  $searchTools->getCommunicationPreferencesForSelectList();

    $comm_prefs_select = $form->add('select', 'comm_prefs', ts('Communication Preference'), $comm_prefs, false);

    $summary_choices = array('' => '-- select --', 'contact' => 'Contact', 'household' => 'Household');
    $form->add('select', 'summary_type', ts('Summarize By'), $summary_choices, false);

    $order_choices = array('' => '-- select --' , 'name' => 'Alphabetical by Name', 'postal_code' => 'Postal/Zip Code');
    $form->add('select', 'order_type', ts('Order By'), $order_choices, false);

    $form->assign('elements', array('group_of_contact', 'membership_org_of_contact', 'membership_type_of_contact',  'cal_year', 'contribution_date_relative', 'deduct_prefs' , 'comm_prefs', 'summary_type', 'order_type'));
  }

  /**
   * Adds a field for a 'Relative Date' filter.
   *
   * Also includes hacks to change the default label, add help text and better positionning.
   */
  function buildFormAddDateRangeWidget(&$form) {
    CRM_Core_Form_Date::buildDateRange($form, 'contribution_date', 1, '_from', '_to', ts('From'), FALSE);

    $e = $form->getElement('contribution_date_relative');
    $e->_label = ts('Date Range');

    // Create a new div inside the 2nd 'td', then move the date widgets inside it.
    // Otherwise the date ranges are in a 3rd div, and the visual is wonky.
    $js = 'cj("tr.crm-contact-custom-search-form-row-contribution_date_relative > td:nth-child(2)").append("<div class=\'jvillage-date-range\'></div>"); ';
    $js .= 'cj("tr.crm-contact-custom-search-form-row-contribution_date_relative > td:nth-child(3) > *").appendTo("tr.crm-contact-custom-search-form-row-contribution_date_relative > td:nth-child(2) > .jvillage-date-range");';

    CRM_Core_Resources::singleton()->addScript($js, 'html-footer');
  }

  function templateFile() {
   return 'CRM/Contact/Form/Search/Custom/TaxYearContributorListing.tpl';
  }

  /**
   * Returns the main SQL query.
   *
   * SELECT clause must include contact_id as an alias for civicrm_contact.id
   * [ML] only for the normal display, not for actions (otherwise we run into other issues).
   */
  function all($offset = 0, $rowcount = 0, $sort = null, $includeContactIDs = FALSE, $onlyIDs = FALSE ) {
    $this->_sort = $sort;

    // [ML] I haven't had time to look more in detail, but from tests, seems like
    // CiviCRM expects to have a 'contact_id' column when running the search normally,
    // but expects an 'id' column when running actions...
    $contact_id_col = ($onlyIDs ? 'id' : 'contact_id');

    /******************************************************************************/
    // Get data for contacts

    $params = array(
      'version' => 3,
      'sequential' => 1,
      'name' => 'Third_Party_Payor',
    );

    $result = civicrm_api('CustomField', 'getsingle', $params);

    $third_party_col_name = $result['column_name'];
    $set_id = $result['custom_group_id'];

    $params = array(
      'version' => 3,
      'sequential' => 1,
      'id' => $set_id,
    );

    $result_custom_group = civicrm_api('CustomGroup', 'getsingle', $params);
    $third_party_table_name = $result_custom_group['table_name'];

    $grouby = '';

    // [ML] FIXME? Why? Remove?
    if (1 == 0) {
      $summary_type = $this->_formValues['summary_type'];

      if ($summary_type == 'household') {
        $groupby = " Group BY " . $contact_id_col;
        $select = " if (contact_a.contact_type = 'Household' OR  household.id is null , if (third_party.$third_party_col_name IS NOT NULL, third_party.$third_party_col_name , contact_a.id), household.id) as $contact_id_col ";
      }
      else {
        $select  = "if (third_party.$third_party_col_name IS NOT NULL, third_party.$third_party_col_name , contact_a.id) as $contact_id_col";
      }
    }
    else {
      $summary_type = $this->_formValues['summary_type'];

      if ($summary_type == 'household') {
          $groupby = " Group BY hh_id ";
          $select = "
            date(max(contrib.receive_date)) as last_contrib_date,
            IF ( contact_a.contact_type = 'Household' OR  household.id is null , if (third_party.$third_party_col_name IS NOT NULL, third_party.$third_party_col_name , contact_a.id) ,   household.id ) as $contact_id_col,
            IF ( contact_a.contact_type = 'Household' OR  household.id is null , if (third_party.$third_party_col_name IS NOT NULL, third_party.$third_party_col_name , contact_a.id) ,   household.id ) as hh_id,
            IF (contact_a.contact_type = 'Household' OR  household.id is null, if (third_party.$third_party_col_name IS NOT NULL, third_con.sort_name, contact_a.sort_name) , household.sort_name) as sort_name,
            addr.postal_code as postal_code, addr.city as city" ;

      }
      else {
        $groupby = " Group BY if (third_party.$third_party_col_name IS NOT NULL, third_party.$third_party_col_name , contact_a.id)  ";

        // if third-party payor is used, use them as the contact to display.
        $select = "if (third_party.$third_party_col_name IS NOT NULL, third_party.$third_party_col_name , contact_a.id)  as $contact_id_col ,
          if (third_party.$third_party_col_name IS NOT NULL, third_con.sort_name, contact_a.sort_name) as sort_name,
          date(max(contrib.receive_date)) as last_contrib_date,
          if ( contact_a.contact_type = 'Household' OR  household.id is null , contact_a.id,   household.id ) as hh_id,
          if (contact_a.contact_type = 'Household', contact_a.sort_name , household.sort_name) as hh_name,
          addr.postal_code as postal_code, addr.city as city";
      }
    }

    // make sure selected smart groups are cached in the cache table
    $group_of_contact = $this->_formValues['group_of_contact'];
    require_once('utils/CustomSearchTools.php');
    $searchTools = new CustomSearchTools();
    $searchTools::verifyGroupCacheTable($group_of_contact);

    $from  = $this->from();
    $where = $this->where($includeContactIDs);

    $non_event_contrib_sql =    "
      SELECT $select
      FROM   $from
      WHERE  $where
      $groupby ";

    $part_from = self::participant_from();
    $event_contrib_sql =  "
      SELECT $select
      FROM  $part_from
      WHERE  $where
      $groupby ";

    if ($onlyIDs) {
      $outer_select = " id, sort_name ";
    }
    else {
      $outer_select = " * ";
    }

    $sql = "SELECT $outer_select
              FROM ((".$non_event_contrib_sql.")
             UNION ALL (".$event_contrib_sql.")) as contact_a ";

    /**
     * [ML] FIXME: when civicrm runs actions, it appends somthing similar to:
     *
     * $sql = $searchSQL . " AND contact_a.id NOT IN (
     *                       SELECT contact_id FROM civicrm_group_contact
     *                      WHERE civicrm_group_contact.status = 'Removed'
     *                      AND   civicrm_group_contact.group_id = $groupID ) ";
     * c.f. CRM/Contact/BAO/GroupContactCache.php
     * so we cannot have an 'order' or 'group by' clause in the request.
     */

    if ($onlyIDs) {
      $sql .= ' WHERE 1=1 ';

      $table_name = 'civicrm_temp_taxyearcontrib_' . rand(0, 2000);

      CRM_Core_DAO::executeQuery("CREATE TEMPORARY TABLE $table_name ($sql)");
      $sql = 'SELECT id, id as contact_id FROM ' . $table_name . ' as contact_a WHERE 1=1 ';
    }
    else {
      $sql .= " GROUP BY $contact_id_col";
    }

    $order_type = $this->_formValues['order_type'];
    if ($order_type == 'postal_code') {
      $sql .= " ORDER BY postal_code ";
    }
    else {
      $sql .= " ORDER BY sort_name " ;
    }

    if ($rowcount > 0 && $offset >= 0 ) {
      $sql .= " LIMIT $offset, $rowcount ";
    }

    return $sql;
  }

  function participant_from() {
    $tmp_from = "";
    $tmp_group_join = "";
    if (count($this->_formValues['group_of_contact']) > 0) {
      $tmp_group_join = "LEFT JOIN civicrm_group_contact as groups on contact_a.id = groups.contact_id
        LEFT JOIN civicrm_group_contact_cache as groupcache ON contact_a.id = groupcache.contact_id ";
    }

    $tmp_mem_join = "";

    if (count($this->_formValues['membership_type_of_contact'] ) > 0 || count($this->_formValues['membership_org_of_contact'] ) > 0     ){
      $tmp_mem_join = "LEFT JOIN civicrm_membership as memberships on contact_a.id = memberships.contact_id
        LEFT JOIN civicrm_membership_status as mem_status on memberships.status_id = mem_status.id
        LEFT JOIN civicrm_membership_type mt ON memberships.membership_type_id = mt.id ";
    }

    if (strlen($comm_prefs = $this->_formValues['comm_prefs']) > 0  ){
      $tmp_email_join = "LEFT JOIN civicrm_email ON contact_a.id = civicrm_email.contact_id AND civicrm_email.is_primary = 1 ";
    }

    $params = array(
      'version' => 3,
      'sequential' => 1,
      'name' => 'Third_Party_Payor',
    );

   $result = civicrm_api('CustomField', 'getsingle', $params);

   $third_party_col_name = $result['column_name'];
   $set_id = $result['custom_group_id'];

   $params = array(
     'version' => 3,
     'sequential' => 1,
     'id' => $set_id,
   );

   $result_custom_group = civicrm_api('CustomGroup', 'getsingle', $params);
   $third_party_table_name = $result_custom_group['table_name'];

/*
 civicrm_line_item li JOIN civicrm_participant part ON li.entity_id = part.id AND li.entity_table =  'civicrm_participant'
   JOIN civicrm_participant_payment ep ON ifnull(part.registered_by_id, part.id) = ep.participant_id
        join civicrm_contribution contr ON  ep.contribution_id = contr.id

*/

    $tmp_from = "civicrm_contact contact_a JOIN civicrm_contribution contrib ON contrib.contact_id = contact_a.id
                     JOIN civicrm_participant_payment ep  ON  ep.contribution_id = contrib.id
                     JOIN civicrm_participant part ON ifnull(part.registered_by_id, part.id) = ep.participant_id
                     JOIN civicrm_line_item li ON li.entity_id = part.id AND li.entity_table =  'civicrm_participant'
                      JOIN civicrm_financial_type ft ON li.financial_type_id = ft.id
                      LEFT JOIN $third_party_table_name third_party ON third_party.entity_id = contrib.id
                      LEFT JOIN civicrm_contact third_con ON third_con.id  = third_party.$third_party_col_name
                  left join civicrm_relationship r ON r.contact_id_a = if (third_party.$third_party_col_name IS NOT NULL, third_party.$third_party_col_name , contact_a.id) AND r.is_active = 1  AND r.relationship_type_id IN (6, 7 )
                  LEFT JOIN civicrm_contact household ON r.contact_id_b = household.id AND household.is_deleted <> 1
                  LEFT JOIN civicrm_address addr ON if (third_party.$third_party_col_name IS NOT NULL, third_party.$third_party_col_name , contact_a.id) = addr.contact_id AND addr.is_primary = 1
                        ".  $tmp_email_join.$tmp_group_join.$tmp_mem_join;

    return $tmp_from ;
  }

  function from() {
    $tmp_from = "";
    $tmp_group_join = "";
    if (count($this->_formValues['group_of_contact'] ) > 0 ){
      $tmp_group_join = "LEFT JOIN civicrm_group_contact as groups on contact_a.id = groups.contact_id".
            " LEFT JOIN civicrm_group_contact_cache as groupcache ON contact_a.id = groupcache.contact_id ";
    }

    $tmp_mem_join = "";

    if (count($this->_formValues['membership_type_of_contact'] ) > 0 || count($this->_formValues['membership_org_of_contact'] ) > 0) {
      $tmp_mem_join = "LEFT JOIN civicrm_membership as memberships on contact_a.id = memberships.contact_id
        LEFT JOIN civicrm_membership_status as mem_status on memberships.status_id = mem_status.id
        LEFT JOIN civicrm_membership_type mt ON memberships.membership_type_id = mt.id ";
    }

    if (strlen($comm_prefs = $this->_formValues['comm_prefs']) > 0  ){
      $tmp_email_join = "LEFT JOIN civicrm_email ON contact_a.id = civicrm_email.contact_id AND civicrm_email.is_primary = 1 ";
    }

    $params = array(
      'version' => 3,
      'sequential' => 1,
      'name' => 'Third_Party_Payor',
    );

    $result = civicrm_api('CustomField', 'getsingle', $params);

    $third_party_col_name = $result['column_name'];
    $set_id = $result['custom_group_id'];

    $params = array(
      'version' => 3,
      'sequential' => 1,
      'id' => $set_id,
    );

    $result_custom_group = civicrm_api('CustomGroup', 'getsingle', $params);
    $third_party_table_name = $result_custom_group['table_name'];

    $tmp_from = " civicrm_contact contact_a
                      JOIN civicrm_contribution contrib ON contrib.contact_id = contact_a.id
                  LEFT JOIN civicrm_line_item li ON li.entity_id = contrib.id AND li.entity_table = 'civicrm_contribution'
                      JOIN civicrm_financial_type ft ON li.financial_type_id = ft.id
                      LEFT JOIN $third_party_table_name third_party ON third_party.entity_id = contrib.id
                      LEFT JOIN civicrm_contact third_con ON third_con.id  = third_party.$third_party_col_name
                  left join civicrm_relationship r ON r.contact_id_a = if (third_party.$third_party_col_name IS NOT NULL, third_party.$third_party_col_name , contact_a.id) AND r.is_active = 1  AND r.relationship_type_id IN (6, 7 )
                  LEFT JOIN civicrm_contact household ON r.contact_id_b = household.id AND household.is_deleted <> 1
                  LEFT JOIN civicrm_address addr ON if (third_party.$third_party_col_name IS NOT NULL, third_party.$third_party_col_name , contact_a.id) = addr.contact_id AND addr.is_primary = 1
                        ".  $tmp_email_join.$tmp_group_join.$tmp_mem_join;

    return $tmp_from ;
  }

  function where($includeContactIDs = false) {
    $clauses = array();

    $clauses[] = "contact_a.is_deleted <> 1";
    $clauses[] = "contrib.contribution_status_id = 1";
    $clauses[] = "contrib.is_test <> 1";
    $clauses[] = "contrib.total_amount > 0";
    $clauses[] = "lower(ft.name) NOT LIKE 'adjustment-%' " ;
    $clauses[] = "lower(ft.name) NOT LIKE '%---adjustment-%' " ;

    //$clauses[] = "contact_a.is_deceased <> 1";

    $deduct_prefs = $this->_formValues['deduct_prefs'];
    if ($deduct_prefs == 'deductible_only') {
      $clauses[] =  "ft.is_deductible  = 1  ";
    }

    $groups_of_individual = $this->_formValues['group_of_contact'];

    require_once('utils/CustomSearchTools.php');
    $searchTools = new CustomSearchTools();

    $comm_prefs = $this->_formValues['comm_prefs'];

    $searchTools->updateWhereClauseForCommPrefs($comm_prefs, $clauses);

    $tmp_sql_list = $searchTools->getSQLStringFromArray($groups_of_individual);

    if (strlen($tmp_sql_list) > 0) {
      // need to check regular groups as well as smart groups.
      $clauses[] = "((groups.group_id IN (".$tmp_sql_list.") AND groups.status = 'Added') OR (groupcache.group_id IN (".$tmp_sql_list.")  )) " ;
    }

    $membership_types_of_con = $this->_formValues['membership_type_of_contact'];

    $tmp_membership_sql_list = $searchTools->convertArrayToSqlString($membership_types_of_con);

    if (strlen($tmp_membership_sql_list) > 0 ){
      $clauses[] = "memberships.membership_type_id IN (".$tmp_membership_sql_list.")" ;
      $clauses[] = "mem_status.is_current_member = '1'";
      $clauses[] = "mem_status.is_active = '1'";
    }

    // 'membership_org_of_contact'
    $membership_org_of_con = $this->_formValues['membership_org_of_contact'];
    $tmp_membership_org_sql_list = $searchTools->convertArrayToSqlString($membership_org_of_con);

    if (strlen($tmp_membership_org_sql_list) > 0) {
      $clauses[] = "mt.member_of_contact_id IN (".$tmp_membership_org_sql_list.")" ;
      $clauses[] = "mt.is_active = '1'" ;
      $clauses[] = "mem_status.is_current_member = '1'";
      $clauses[] = "mem_status.is_active = '1'";
    }

    // Receive date (nb: user must provide either cal_year or contribution_date_relative)
    CRM_Contact_BAO_Query::fixDateValues(
      $this->_formValues['contribution_date_relative'],
      $this->_formValues['contribution_date_from'],
      $this->_formValues['contribution_date_to']
    );

    // Convert dates from MM/DD/YYYY to ISO
    if (! empty($this->_formValues['contribution_date_from'])) {
      $this->_formValues['contribution_date_from'] = date('Ymd', strtotime($this->_formValues['contribution_date_from']));
    }

    if (! empty($this->_formValues['contribution_date_to'])) {
      $this->_formValues['contribution_date_to'] = date('Ymd', strtotime($this->_formValues['contribution_date_to']));
    }

    // Tax Year field takes precedence, if selected.
    if (! empty($this->_formValues['cal_year'])) {
      // ex: 2015 => 20150101
      $this->_formValues['contribution_date_from'] = $this->_formValues['cal_year'] . '0101';

      // ex: 2015 => 20151231 235959
      $this->_formValues['contribution_date_to'] = $this->_formValues['cal_year'] . '1231235959';
    }

    if (empty($this->_formValues['contribution_date_from']) && empty($this->_formValues['contribution_date_to'])) {
      CRM_Core_Session::setStatus(ts("Please select either a Tax Year or a Relative Date Range."), ts('Warning'), 'warning');

      // Custom searches don't have a validate() function (I think?).
      // So this makes sure we do not return any results.
      $clauses[] = "1=0";
    }
    else {
      $clauses[] = "contrib.receive_date >= " . $this->_formValues['contribution_date_from'];
      $clauses[] = "contrib.receive_date <= " . $this->_formValues['contribution_date_to'];
    }

    if ($includeContactIDs) {
      $contactIDs = array();
      foreach ($this->_formValues as $id => $value ) {
        if ($value && substr($id, 0, CRM_Core_Form::CB_PREFIX_LEN) == CRM_Core_Form::CB_PREFIX) {
          $contactIDs[] = substr($id, CRM_Core_Form::CB_PREFIX_LEN);
        }
      }

      if (! empty($contactIDs)) {
        $contactIDs = implode(', ', $contactIDs);
        $clauses[] = "contact_a.id IN ($contactIDs)";
      }
    }

    $partial_where_clause = implode(' AND ', $clauses);

    return $partial_where_clause ;
  }

  /**
   * Functions below generally don't need to be modified
   */
  function count() {
    $sql = $this->all();
    $dao = CRM_Core_DAO::executeQuery($sql);
    return $dao->N;
  }

  function contactIDs($offset = 0, $rowcount = 0, $sort = null) {
    return $this->all($offset, $rowcount, $sort, false, true);
  }

  /**
   * This relies on a patch on core.
   * If the prevnext cache is not filled in correctly, we cannot select only a few individuals
   * in actions, such as create pdf letters.
   */
  function fillupPrevNextCacheSQL($start, $end, $sort, $cacheKey) {
    $sql = $this->contactIDs($start, $end, $sort);

    $replaceSQL = "SELECT id, id as contact_id";
    $insertSQL = "
INSERT INTO civicrm_prevnext_cache (entity_table, entity_id1, entity_id2, cacheKey, data)
SELECT DISTINCT 'civicrm_contact', id, id, '$cacheKey', sort_name
";

    $sql = str_replace($replaceSQL, $insertSQL, $sql);

    return $sql;
  }

  function &columns() {
    return $this->_columns;
  }

  function setTitle($title) {
    if ($title) {
      CRM_Utils_System::setTitle($title);
    }
    else {
      CRM_Utils_System::setTitle(ts('Search'));
    }
  }

  function summary() {
    return null;
  }

}
