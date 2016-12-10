<?php

class CRM_Jmanage_Form_Report_UpcomingYahrzeits extends CRM_Report_Form {

  protected $_addressField = FALSE;

  protected $_emailField = FALSE;

  protected $_summary = NULL;

  protected $_customGroupExtends = array('Relationship', 'Individual', 'Contact');
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
        'fields' => array(
          'deceased_first_name' => array(
            'title' => ts('Deceased First Name'),
            'name' => 'first_name',
            'default' => TRUE,
          ),
          'deceased_last_name' => array(
            'title' => ts('Deceased Last Name'),
            'name' => 'last_name',
            'default' => TRUE,
          ),
          'deceased_sort_name' => array(
            'title' => ts('Deceased Name (sortable)'),
            'name' => 'sort_name',
          ),
          'deceased_display_name' => array(
            'title' => ts('Deceased Name (formatted)'),
            'name' => 'display_name',
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
          'mourner_sort_name' => array(
            'title' => ts('Mourner Name'),
            'name' => 'sort_name',
          ),
          'mourner_display_name' => array(
            'title' => ts('Mourner Display Name'),
            'name' => 'display_name',
          ),
          'first_name' => array(
            'title' => ts('Mourner First Name'),
            'default' => TRUE,
          ),
          'last_name' => array(
            'title' => ts('Mourner Last Name'),
            'default' => TRUE,
          ),
          'id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
        ),
        'filters' => array(
          'gender_id' => array(
            'title' => ts('Mourner gender'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contact_BAO_Contact::buildOptions('gender_id'),
            'type' => CRM_Utils_Type::T_INT,
          ),
          'preferred_communication_method' => array(
            'title' => ts('Mourner preferred communication method'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contact_BAO_Contact::buildOptions('preferred_communication_method'),
            'type' => CRM_Utils_Type::T_INT,
          ),
          'is_deceased' => array(
            'title' => ts('Mourner is deceased'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contact_BAO_Contact::buildOptions('is_deceased'),
            'type' => CRM_Utils_Type::T_INT,
          ),
          'has_mourner' => array(
            'title' => ts('Has Mourner'),
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'options' => array(-1 => '(Any)', 1 => 'Yes', 0 => 'No'),
            'type' => CRM_Utils_Type::T_INT,
          ),
        ),
        'grouping' => 'contact-mourner-fields',
      ),
      'civicrm_address' => array(
        'fields' => array(
          'street_address' => array(
            'title' => ts('Street Address'),
          ),
          'city' => array(
            'title' => ts('City'),
          ),
          'postal_code' => array(
            'title' => ts('Postal Code'),
          ),
          'state_province_id' => array(
            'title' => ts('State/Province'),
          ),
        ),
        'grouping' => 'contact-mourner-fields',
      ),
      'civicrm_email' => array(
        'fields' => array(
          'email' => array(
            'title' => ts('Mourner Email'),
          ),
        ),
        'grouping' => 'contact-mourner-fields',
      ),
      'civicrm_phone' => array(
        'fields' => array(
          'phone' => array(
            'title' => ts('Phone'),
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
          ),
          'yahrzeit_date_sortable' => array(
            'title' => ts('Yahrzeit Date (evening, sortable)'),
            'name' => 'yahrzeit_date',
          ),
          'yahrzeit_date_formatted' => array(
            'title' => ts('Yahrzeit Date (evening, formatted)'),
            'name' => 'yahrzeit_date',
            'default' => TRUE,
          ),
          'yahrzeit_date_morning' => array(
            'title' => ts('Yahrzeit Date (morning, formatted)'),
          ),
          'relationship_name_formatted' => array(
            'title' => ts('Relationship to Mourner'),
            'default' => TRUE,
          ),
          'yahrzeit_hebrew_date_format_english' => array(
            'title' => ts('Hebrew Yahrzeit Date'),
            'default' => TRUE,
          ),
          'yahrzeit_hebrew_date_format_hebrew' => array(
            'title' => ts('Hebrew Yahrzeit Date (Hebrew format)'),
          ),
          'yahrzeit_erev_shabbat_before' => array(
            'title' => ts('Friday Night Before Yahrzeit'),
          ),
          'yahrzeit_shabbat_morning_before' => array(
            'title' => ts('Saturday Morning Before Yahrzeit'),
          ),
          'yahrzeit_erev_shabbat_after' => array(
            'title' => ts('Friday Night After Yahrzeit'),
          ),
          'yahrzeit_shabbat_morning_after' => array(
            'title' => ts('Saturday Morning After Yahrzeit'),
          ),
        ),
        'filters' => array(
          'yahrzeit_date' => array(
            'title' => ts('Yahrzeit Date - Evening'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ),
          'yahrzeit_date_morning' => array(
            'title' => ts('Yahrzeit Date - Morning'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ),
          'yahrzeit_erev_shabbat_before' => array(
            'title' => ts('Friday Night Before Yahrzeit'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ),
          'yahrzeit_erev_shabbat_after' => array(
            'title' => ts('Friday Night After Yahrzeit'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ),
          'yahrzeit_shabbat_morning_before' => array(
            'title' => ts('Saturday Morning Before Yahrzeit '),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ),
          'yahrzeit_shabbat_morning_after' => array(
            'title' => ts('Saturday Morning After Yahrzeit'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ),
        ),
      ),
      'civicrm_relationship' => array(
        'fields' => array(
          'relationship_id' => array(
            'no_display' => TRUE,
            'name' => 'id',
            'required' => TRUE,
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
    parent::__construct();

    // Change label for 'groups' filter
    $this->_columns['civicrm_group']['filters']['gid']['title'] = ts('Mourner group(s)');

  }

  function preProcess() {
    $this->assign('reportTitle', ts('Membership Detail Report'));
    parent::preProcess();
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
         LEFT JOIN civicrm_contact {$this->_aliases['civicrm_contact_mourner']}
              ON {$this->_aliases[$this->_yahrzeit_table]}.mourner_contact_id =
                 {$this->_aliases['civicrm_contact_mourner']}.id
                 AND NOT {$this->_aliases['civicrm_contact_mourner']}.is_deleted
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
    if ($this->isTableSelected('civicrm_relationship')) {
      $this->_from .= "
         LEFT JOIN civicrm_relationship {$this->_aliases['civicrm_relationship']}
          ON {$this->_aliases['civicrm_relationship']}.id = {$this->_aliases[$this->_yahrzeit_table]}.yahrzeit_relationship_id
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
    $params = $this->_params;

    // Some filters need special handling. Handle them here, and remove them from $params.
    $clauses = array_merge($clauses, $this->_whereClauseSpecialParams($params));

    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;
          if (CRM_Utils_Array::value('operatorType', $field) & CRM_Utils_Type::T_DATE) {
            $relative = CRM_Utils_Array::value("{$fieldName}_relative", $params);
            $from     = CRM_Utils_Array::value("{$fieldName}_from", $params);
            $to       = CRM_Utils_Array::value("{$fieldName}_to", $params);

            $clause = $this->dateClause($field['name'], $relative, $from, $to, $field['type']);
          }
          else {
            $op = CRM_Utils_Array::value("{$fieldName}_op", $params);
            if ($op) {
              $clause = $this->whereClause($field,
                $op,
                CRM_Utils_Array::value("{$fieldName}_value", $params),
                CRM_Utils_Array::value("{$fieldName}_min", $params),
                CRM_Utils_Array::value("{$fieldName}_max", $params)
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
    // Establish correct format for some dates.
    switch (CRM_Utils_Date::getDateFormat()) {
      case 'dd/mm/yy':
        $nice_date_format = 'j F Y';
        break;
      case 'mm/dd/yy': 
        $nice_date_format = 'F j, Y';
        break;
    }
   
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

      if (array_key_exists('pogstone_temp_yahrzeits_yahrzeit_date_morning', $row)) {
        if ($value = $row['pogstone_temp_yahrzeits_yahrzeit_date_morning']) {
          $rows[$rowNum]['pogstone_temp_yahrzeits_yahrzeit_date_morning'] = date($nice_date_format, strtotime($value));
        }
        $entryFound = TRUE;
      }

      if (array_key_exists('pogstone_temp_yahrzeits_yahrzeit_date_sortable', $row)) {
        if ($value = $row['pogstone_temp_yahrzeits_yahrzeit_date_sortable']) {
          $rows[$rowNum]['pogstone_temp_yahrzeits_yahrzeit_date_sortable'] = date('Y-m-d', strtotime($value));
        }
        $entryFound = TRUE;
      }

      if (array_key_exists('pogstone_temp_yahrzeits_yahrzeit_date_formatted', $row)) {
        if ($value = $row['pogstone_temp_yahrzeits_yahrzeit_date_formatted']) {
          $rows[$rowNum]['pogstone_temp_yahrzeits_yahrzeit_date_formatted'] = date($nice_date_format, strtotime($value));
        }
        $entryFound = TRUE;
      }

      if (array_key_exists('pogstone_temp_yahrzeits_yahrzeit_erev_shabbat_before', $row)) {
        if ($value = $row['pogstone_temp_yahrzeits_yahrzeit_erev_shabbat_before']) {
          $rows[$rowNum]['pogstone_temp_yahrzeits_yahrzeit_erev_shabbat_before'] = date($nice_date_format, strtotime($value));
        }
        $entryFound = TRUE;
      }

      if (array_key_exists('pogstone_temp_yahrzeits_yahrzeit_shabbat_morning_before', $row)) {
        if ($value = $row['pogstone_temp_yahrzeits_yahrzeit_shabbat_morning_before']) {
          $rows[$rowNum]['pogstone_temp_yahrzeits_yahrzeit_shabbat_morning_before'] = date($nice_date_format, strtotime($value));
        }
        $entryFound = TRUE;
      }

      if (array_key_exists('pogstone_temp_yahrzeits_yahrzeit_erev_shabbat_after', $row)) {
        if ($value = $row['pogstone_temp_yahrzeits_yahrzeit_erev_shabbat_after']) {
          $rows[$rowNum]['pogstone_temp_yahrzeits_yahrzeit_erev_shabbat_after'] = date($nice_date_format, strtotime($value));
        }
        $entryFound = TRUE;
      }

      if (array_key_exists('pogstone_temp_yahrzeits_yahrzeit_shabbat_morning_after', $row)) {
        if ($value = $row['pogstone_temp_yahrzeits_yahrzeit_shabbat_morning_after']) {
          $rows[$rowNum]['pogstone_temp_yahrzeits_yahrzeit_shabbat_morning_after'] = date($nice_date_format, strtotime($value));
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
    foreach (CRM_Core_PseudoConstant::relationshipType() as $id => $type) {
      $this->_relationship_types[$type['label_a_b']] = $id;
    }
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

  /**
   * Build where clause for groups.
   * Overrides parent::whereGroupClause() because we want to join groups on the
   * mourner, whereas parent::whereGroupClause() joins on contact (deceased).
   *
   * @param string $field
   * @param mixed $value
   * @param string $op
   *
   * @return string
   */
  public function whereGroupClause($field, $value, $op) {
    $smartGroupQuery = "";

    $group = new CRM_Contact_DAO_Group();
    $group->is_active = 1;
    $group->find();
    $smartGroups = array();
    while ($group->fetch()) {
      if (in_array($group->id, $this->_params['gid_value']) &&
        $group->saved_search_id
      ) {
        $smartGroups[] = $group->id;
      }
    }

    CRM_Contact_BAO_GroupContactCache::check($smartGroups);

    $smartGroupQuery = '';
    if (!empty($smartGroups)) {
      $smartGroups = implode(',', $smartGroups);
      $smartGroupQuery = " UNION DISTINCT
                  SELECT DISTINCT smartgroup_contact.contact_id
                  FROM civicrm_group_contact_cache smartgroup_contact
                  WHERE smartgroup_contact.group_id IN ({$smartGroups}) ";
    }

    $sqlOp = $this->getSQLOperator($op);
    if (!is_array($value)) {
      $value = array($value);
    }
    $clause = "{$field['dbAlias']} IN (" . implode(', ', $value) . ")";

    $contactAlias = $this->_aliases['civicrm_contact_mourner'];
    if (!empty($this->relationType) && $this->relationType == 'b_a') {
      $contactAlias = $this->_aliases['civicrm_contact_b'];
    }
    return " {$contactAlias}.id {$sqlOp} (
                          SELECT DISTINCT {$this->_aliases['civicrm_group']}.contact_id
                          FROM civicrm_group_contact {$this->_aliases['civicrm_group']}
                          WHERE {$clause} AND {$this->_aliases['civicrm_group']}.status = 'Added'
                          {$smartGroupQuery} ) ";
  }

  /**
   * Process special filters parameters which are not going to play well with
   * $this->whereClause(); this function removes those items from $params
   * (by reference) and handles them according to its own logic.
   *
   * @param <type> $params
   * @return Array of where clauses.
   */
  function _whereClauseSpecialParams(&$params) {
    $clauses = array();

    $has_mourner_op = $params['has_mourner_op'];
    $has_mourner_value = $params['has_mourner_value'];
    unset($params['has_mourner_op']);
    unset($params['has_mourner_value']);

    $field = $this->_columns[$this->_yahrzeit_table]['filters']['has_mourner'];

    $clause = NULL;
    switch ($has_mourner_value) {
      case 1:
        $clause = "{$this->_aliases['civicrm_contact_mourner']}.id IS NOT NULL";
        break;
      case 0:
        $clause = "{$this->_aliases['civicrm_contact_mourner']}.id IS NULL";
        break;
    }

    if (!empty($clause)) {
      $clauses[] = $clause;
    }
    return $clauses;
  }
}
