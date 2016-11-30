<?php

class CRM_Jmanage_Form_Report_UpcomingYahrzeits extends CRM_Report_Form {

  protected $_addressField = FALSE;

  protected $_emailField = FALSE;

  protected $_summary = NULL;

  protected $_customGroupExtends = array('Relationship', 'Individual');
  protected $_customGroupGroupBy = FALSE;

  protected $_yahrzeit_table;
  protected $_relationship_types;
  protected $_membership_types;
  protected $_membership_groups;
  
  function __construct() {

    require_once 'CRM/Hebrew/HebrewDates.php';
    $hebrewCalendar = new HebrewCalendar();
    $this->_yahrzeit_table = $hebrewCalendar->get_sql_table_name();

    $this->_set_relationship_types();
    $this->_set_membership_types();
    $this->_set_membership_groups();

    $this->_columns = array(
      'civicrm_contact' => array(
//        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array(
          'sort_name' => array(
            'title' => ts('Deceased Name (sortable)'),
            'default' => TRUE,
          ),
          'display_name' => array(
            'title' => ts('Deceased Name (formatted)'),
            'default' => TRUE,
          ),
          'nick_name' => array(
            'title' => ts('Deceased Nickname'),
            'default' => TRUE,
          ),
          'id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_contact_mourner' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array(
          'sort_name' => array(
            'title' => ts('Mourner Name'),
            'default' => TRUE,
          ),
          'display_name' => array(
            'title' => ts('Mourner Display Name'),
            'default' => TRUE,
          ),
          'first_name' => array(
            'title' => ts('Mourner First name'),
            'default' => TRUE,
          ),
          'last_name' => array(
            'title' => ts('Mourner Last name'),
            'default' => TRUE,
          ),
          'id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
        ),
        'grouping' => 'contact-mourner-fields',
      ),
      'civicrm_contact_household' => array(
//        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array(
          'display_name' => array(
            'title' => ts('Mourner Household Name'),
            'default' => TRUE,
          ),
          'id' => array(
            'title' => ts('Mourner Household ID'),
            'default' => TRUE,
          ),
        ),
        'grouping' => 'contact-mourner-fields',
      ),
      'civicrm_address' => array(
//        'dao' => 'CRM_Core_DAO_Address',
        'fields' => array(
          'street_address' => array(
            'title' => ts('Street Address'),
            'default' => TRUE,
          ),
          'supplemental_address_1' => array(
            'title' => ts('Supplemental Address'),
            'default' => TRUE,
          ),
          'city' => array(
            'title' => ts('City'),
            'default' => TRUE,
          ),
          'postal_code' => array(
            'title' => ts('Postal Code'),
            'default' => TRUE,
          ),
          'state_province_id' => array(
            'title' => ts('State/Province'),
            'default' => TRUE,
          ),
        ),
        'grouping' => 'contact-mourner-fields',
      ),
      'civicrm_email' => array(
//        'dao' => 'CRM_Core_DAO_Email',
        'fields' => array(
          'email' => array(
            'title' => ts('Email'),
            'default' => TRUE,
          ),
        ),
        'grouping' => 'contact-mourner-fields',
      ),
      'civicrm_phone' => array(
//        'dao' => 'CRM_Core_DAO_phone',
        'fields' => array(
          'phone' => array(
            'title' => ts('Phone'),
            'default' => TRUE,
          ),
        ),
        'grouping' => 'contact-mourner-fields',
      ),   
      $this->_yahrzeit_table => array(
        'fields' => array(
          'deceased_contact_id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'deceased_date' => array(
            'title' => ts('Date of Death'),
            'default' => TRUE,
          ),
          'd_before_sunset' => array(
            'title' => ts('Before Sunset?'),
            'default' => TRUE,
          ),
          'yahrzeit_date' => array(
            'title' => ts('Yahrzeit Date (evening, sortable)'),
            'default' => TRUE,
          ),
          'yahrzeit_date' => array(
            'title' => ts('Yahrzeit Date (evening, formatted)'),
            'default' => TRUE,
          ),
          'yahrzeit_date_morning' => array(
            'title' => ts('Yahrzeit Date (morning, formatted)'),
            'default' => TRUE,
          ),
          'relationship_name_formatted' => array(
            'title' => ts('Relationship to Mourner'),
            'default' => TRUE,
          ),
          'yahrzeit_hebrew_date_format_hebrew' => array(
            'title' => ts('Hebrew Yahrzeit Date (Hebrew format)'),
            'default' => TRUE,
          ),
          'yahrzeit_hebrew_date_format_english' => array(
            'title' => ts('Hebrew Yahrzeit Date'),
            'default' => TRUE,
          ),
          'mourner_email' => array(
            'title' => ts('Email'),
            'default' => TRUE,
          ),
          'yahrzeit_erev_shabbat_before' => array(
            'title' => ts('Friday Night Before Yahrzeit'),
            'default' => TRUE,
          ),
          'yahrzeit_shabbat_morning_before' => array(
            'title' => ts('Saturday Morning Before Yahrzeit'),
            'default' => TRUE,
          ),
          'yahrzeit_erev_shabbat_after' => array(
            'title' => ts('Friday Night After Yahrzeit'),
            'default' => TRUE,
          ),
          'yahrzeit_shabbat_morning_after' => array(
            'title' => ts('Saturday Morning After Yahrzeit'),
            'default' => TRUE,
          ),
        ),
      ),
      'civicrm_relationship' => array(
        'fields' => array(
          'description' => array(
            'title' => ts('Relationship Description'),
            'default' => TRUE,
          ),
        ),
      ),
      'civicrm_note' => array(
        'fields' => array(
          'note' => array(
            'title' => ts('Relationship Note'),
            'default' => TRUE,
          ),
        ),
      ),
      'civicrm_membership' => array(
        'filters' => array(
          'membership_type_id' => array(
            'title' => ts('Mourner membership type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $this->_membership_types,
            'type' => CRM_Utils_Type::T_INT,
          ),
        ),
      ),
      'civicrm_membership_type' => array(
        'filters' => array(
          'member_of_contact_id' => array(
            'title' => ts('Mourner has membership in'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $this->_membership_groups,
            'type' => CRM_Utils_Type::T_INT,
          ),
        ),
      ),
    );

    $this->_groupFilter = TRUE;
    $this->_tagFilter = TRUE;
    parent::__construct();



  // FIXME: custom fields to check by 'default':
  // Deceased Hebrew Name (Religious: "Hebrew Name")
  // Hebrew Date of Death (Hebrew Calendar Demographics: "Hebrew Date of Death")
  // Mourner Preference (Yahrzeit Details: "Mourner observes the English date")
  // Plaque? (Memorial Plaque Info: "Has Plaque")
  // Plaque Location (Memorial Plaque Info: "Plaque Location")



    dsm($this->_columns, 'columns');
  }

  function preProcess() {
    $this->assign('reportTitle', ts('Membership Detail Report'));
    parent::preProcess();
  }

  function _fixme_deleteme_select() {
    $select = $this->_columnHeaders = array();

    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (CRM_Utils_Array::value('required', $field) ||
            CRM_Utils_Array::value($fieldName, $this->_params['fields'])
          ) {
            if ($tableName == 'civicrm_address') {
              $this->_addressField = TRUE;
            }
            elseif ($tableName == 'civicrm_email') {
              $this->_emailField = TRUE;
            }
            $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
          }
        }
      }
    }

    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  function from() {

    $this->_from = NULL;

    $this->_from = "FROM  civicrm_contact {$this->_aliases['civicrm_contact']} {$this->_aclFrom}
       INNER JOIN {$this->_yahrzeit_table} {$this->_aliases[$this->_yahrzeit_table]}
            ON {$this->_aliases[$this->_yahrzeit_table]}.deceased_contact_id =
               {$this->_aliases['civicrm_contact']}.id
     ";

    if ($this->isTableSelected('civicrm_contact_mourner')
      || $this->isTableSelected('civicrm_email')
      || $this->isTableSelected('civicrm_phone')
      || $this->isTableSelected('civicrm_address')
    ) {
      $this->_from .= "
         INNER JOIN civicrm_contact {$this->_aliases['civicrm_contact_mourner']}
              ON {$this->_aliases[$this->_yahrzeit_table]}.mourner_contact_id =
                 {$this->_aliases['civicrm_contact_mourner']}.id
       ";
    }
    if ($this->isTableSelected('civicrm_email')) {
      $this->_from .= "
         LEFT JOIN civicrm_email {$this->_aliases['civicrm_email']}
              ON {$this->_aliases[$this->_yahrzeit_table]}.mourner_contact_id =
                 {$this->_aliases['civicrm_email']}.contact_id AND {$this->_aliases['civicrm_email']}.is_primary
       ";
    }
    if ($this->isTableSelected('civicrm_phone')) {
      $this->_from .= "
         LEFT JOIN civicrm_phone {$this->_aliases['civicrm_phone']}
              ON {$this->_aliases[$this->_yahrzeit_table]}.mourner_contact_id =
                 {$this->_aliases['civicrm_phone']}.contact_id AND {$this->_aliases['civicrm_phone']}.is_primary
       ";
    }
    if ($this->isTableSelected('civicrm_address')) {
      $this->_from .= "
         LEFT JOIN civicrm_address {$this->_aliases['civicrm_address']}
              ON {$this->_aliases[$this->_yahrzeit_table]}.mourner_contact_id =
                 {$this->_aliases['civicrm_address']}.contact_id AND {$this->_aliases['civicrm_address']}.is_primary
       ";
    }
    if ($this->isTableSelected('civicrm_contact_household')) {
      $this->_from .= "
         LEFT JOIN civicrm_relationship rh
              ON rh.contact_id_a = {$this->_aliases['civicrm_contact_mourner']}.id AND rh.relationship_type_id = '{$this->_relationship_types['Household Member of']}'
         LEFT JOIN civicrm_contact {$this->_aliases['civicrm_contact_household']}
              ON rh.contact_id_b = {$this->_aliases['civicrm_contact_household']}.id
      ";
    }
    if ($this->isTableSelected('civicrm_relationship') || $this->isTableSelected('civicrm_note')) {
      $this->_from .= "
         LEFT JOIN civicrm_relationship {$this->_aliases['civicrm_relationship']}
          ON {$this->_aliases['civicrm_relationship']}.id = {$this->_aliases[$this->_yahrzeit_table]}.yahrzeit_relationship_id
       ";
    }
    if ($this->isTableSelected('civicrm_note')) {
      $this->_from .= "
         LEFT JOIN civicrm_note {$this->_aliases['civicrm_note']}
              ON {$this->_aliases['civicrm_note']}.entity_table = 'civicrm_relationship'
                AND {$this->_aliases['civicrm_note']}.entity_id = {$this->_aliases['civicrm_relationship']}.id
       ";
    }
    if ($this->isTableSelected('civicrm_membership') || $this->isTableSelected('civicrm_membership_type')) {
      $this->_from .= "
         LEFT JOIN civicrm_membership {$this->_aliases['civicrm_membership']}
            ON {$this->_aliases['civicrm_membership']}.contact_id = {$this->_aliases[$this->_yahrzeit_table]}.mourner_contact_id
              AND {$this->_aliases['civicrm_membership']}.status_id in (1,2,3)
       ";
    }
    if ($this->isTableSelected('civicrm_membership_type')) {
      $this->_from .= "
         LEFT JOIN civicrm_membership_type {$this->_aliases['civicrm_membership_type']}
            ON {$this->_aliases['civicrm_membership_type']}.id = {$this->_aliases['civicrm_membership']}.membership_type_id
       ";
    }
    
  }

  function where() {
    $clauses = array();
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;
          if (CRM_Utils_Array::value('operatorType', $field) & CRM_Utils_Type::T_DATE) {
            $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
            $from     = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
            $to       = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);

            $clause = $this->dateClause($field['name'], $relative, $from, $to, $field['type']);
          }
          else {
            $op = CRM_Utils_Array::value("{$fieldName}_op", $this->_params);
            if ($op) {
              $clause = $this->whereClause($field,
                $op,
                CRM_Utils_Array::value("{$fieldName}_value", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_min", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_max", $this->_params)
              );
            }
          }

          if (!empty($clause)) {
            $clauses[] = $clause;
          }
        }
      }
    }

    if (empty($clauses)) {
      $this->_where = "WHERE ( 1 ) ";
    }
    else {
      $this->_where = "WHERE " . implode(' AND ', $clauses);
    }

    if ($this->_aclWhere) {
      $this->_where .= " AND {$this->_aclWhere} ";
    }
  }

  function groupBy() {
    $this->_groupBy = " GROUP BY {$this->_aliases['civicrm_contact']}.id";
  }

  function orderBy() {
    $this->_orderBy = " ORDER BY {$this->_aliases[$this->_yahrzeit_table]}.yahrzeit_date, {$this->_aliases[$this->_yahrzeit_table]}.deceased_name ASC";
  }

  function postProcess() {
    $this->beginPostProcess();

    // get the acl clauses built before we assemble the query
    $this->buildACLClause($this->_aliases['civicrm_contact']);
    $sql = $this->buildQuery(TRUE);

    $rows = array();
    $this->buildRows($sql, $rows);

    $this->formatDisplay($rows);
    $this->doTemplateAssignment($rows);
    $this->endPostProcess($rows);
  }

  function alterDisplay(&$rows) {
    dsm($rows, 'rows');
    // custom code to alter rows
    $entryFound = FALSE;
    $checkList = array();
    foreach ($rows as $rowNum => $row) {

      if (!empty($this->_noRepeats) && $this->_outputMode != 'csv') {
        // not repeat contact display names if it matches with the one
        // in previous row
        $repeatFound = FALSE;
        foreach ($row as $colName => $colVal) {
          if (CRM_Utils_Array::value($colName, $checkList) &&
            is_array($checkList[$colName]) &&
            in_array($colVal, $checkList[$colName])
          ) {
            $rows[$rowNum][$colName] = "";
            $repeatFound = TRUE;
          }
          if (in_array($colName, $this->_noRepeats)) {
            $checkList[$colName][] = $colVal;
          }
        }
      }

      if (array_key_exists('civicrm_membership_membership_type_id', $row)) {
        if ($value = $row['civicrm_membership_membership_type_id']) {
          $rows[$rowNum]['civicrm_membership_membership_type_id'] = CRM_Member_PseudoConstant::membershipType($value, FALSE);
        }
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_address_state_province_id', $row)) {
        if ($value = $row['civicrm_address_state_province_id']) {
          $rows[$rowNum]['civicrm_address_state_province_id'] = CRM_Core_PseudoConstant::stateProvince($value, FALSE);
        }
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_address_country_id', $row)) {
        if ($value = $row['civicrm_address_country_id']) {
          $rows[$rowNum]['civicrm_address_country_id'] = CRM_Core_PseudoConstant::country($value, FALSE);
        }
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_contact_sort_name', $row) &&
        $rows[$rowNum]['civicrm_contact_sort_name'] &&
        array_key_exists('civicrm_contact_id', $row)
      ) {
        $url = CRM_Utils_System::url("civicrm/contact/view",
          'reset=1&cid=' . $row['civicrm_contact_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contact_sort_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_sort_name_hover'] = ts("View Contact Summary for this Contact.");
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_contact_mourner_sort_name', $row) &&
        $rows[$rowNum]['civicrm_contact_mourner_sort_name'] &&
        array_key_exists('civicrm_contact_mourner_id', $row)
      ) {
        $url = CRM_Utils_System::url("civicrm/contact/view",
          'reset=1&cid=' . $row['civicrm_contact_mourner_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contact_mourner_sort_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_mourner_sort_name_hover'] = ts("View Contact Summary for this Contact.");
        $entryFound = TRUE;
      }

      if (!$entryFound) {
        break;
      }
    }
  }

  function _set_relationship_types() {
    // Fixme: might be worth getting these via API.
    $this->_relationship_types = array(
      'Household Member of' => 7,
    );
  }

  function _set_membership_types() {
    $params = array(
      'is_active' => 1,
      'sequential' => 1,
      'options' => array(
        'sort' => 'name',
      ),
    );
    $result = civicrm_api3('MembershipType', 'get', $params);
    foreach($result['values'] as $value) {
      $this->_membership_types[$value['id']] = $value['name'];
    }
  }

  function _set_membership_groups() {
    // Use existing Pogstone custom code to build this option list.
    require_once('utils/CustomSearchTools.php');
    $searchTools = new CustomSearchTools();
    $this->_membership_groups = $searchTools->getMembershipOrgsforSelectList();
  }
}
