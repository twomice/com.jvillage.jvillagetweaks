<?php

/**
 *
 * @copyright Pogstone Inc. (c) 2006-2014
 * @author Sarah Gladstone
 * $Id$
 *  
 * website: http://pogstone.com
 */
class CRM_Contact_Form_Search_Custom_FancyParticipantListing extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

  protected $_allChosenEvents = null;
  protected $_allChosenPricesetOptions = null;
  protected $_tableName = null;
  protected $columns_for_temp_table = null;
  protected $_userChoices = null;
  protected $_layoutChoice = null;
  protected $_all_column_names = null;

  function __construct(&$formValues) {
    parent::__construct($formValues);

    $tmp_option_value_raw = $this->_formValues['event_id'];

    $this->_userChoices = $tmp_option_value_raw;

    $tmp_all_events = array();
    $tmp_all_priceset_options = array();

    if (is_array($this->_userChoices)) {
      foreach ($this->_userChoices as $dontCare => $curUserChoice) {
        $tmp_cur = split('_', $curUserChoice);
        $tmp_all_events[] = $tmp_cur[0];
        $tmp_all_priceset_options[] = $tmp_cur[1];
      }
    }

    $this->_allChosenEvents = $tmp_all_events;
    $this->_allChosenPricesetOptions = $tmp_all_priceset_options;

    $this->_layoutChoice = $this->_formValues['layout_choice'];
    $this->setColumns();
  }

  function priceSetDAO($eventID = null) {
    // get all the events that have a price set associated with it
    $sql = "
SELECT e.id    as id,
       e.title as title,
       e.start_date as start_date, 
       p.price_set_id as price_set_id
FROM   civicrm_event      e,
       civicrm_price_set_entity  p
WHERE  p.entity_table = 'civicrm_event'
AND    p.entity_id    = e.id 
";

    if (count($this->_allChosenEvents) > 0) {
      // user has already picked some events, they cannot make a change.
      $i = 1;
      foreach ($this->_allChosenEvents as $cur_eid) {
        $sql_eid_list = $sql_eid_list . $cur_eid;

        if ($i < count($this->_allChosenEvents)) {
          $sql_eid_list = $sql_eid_list . ", ";
        }

        $i = $i + 1;
      }

      $sql = $sql . " AND e.id IN ( " . $sql_eid_list . ") ";
    }

    $sql .= " ORDER BY e.start_date desc";

    $params = array();
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    return $dao;
  }

  function buildForm(&$form) {
    $dao = $this->priceSetDAO();

    $event = array();
    while ($dao->fetch()) {
      $event[$dao->id] = $dao->title . ' at ' . $dao->start_date;
    }

    $dao->free();

    if (empty($event)) {
      CRM_Core_Error::fatal(ts('There are no events with Price Sets'));
    }

    $tmpEventIds = $this->getEventsWithParticipants();

    /*
      $tmpEventIds = array();
      $dao = $this->priceSetDAO();

      while ( $dao->fetch()) {
      $cur_event_id = $dao->id;
      $cur_event_label = $dao->title.' at '.$dao->start_date;
      // TODO: Finish testing
      //$tmpPriceSetOptions['event_id_'.$cur_event_id] = '---- Select an option for '.$cur_event_label.' ----'  ;
      //$tmpPriceSetOptions[$cur_event_id] = '---- Select an option for '.$cur_event_label.' ----'  ;
      $tmpEventIds[$cur_event_id] = '--'.$cur_event_label.' --'  ;
      }
      $dao->free();
     */

    $tmp_ps_lineitems = array();
    $sql = "SELECT li.id as lineitem_id , li.label as lineitem_label ,e.id as event_id ,  e.title as event_title, e.start_date as event_start_date
        FROM civicrm_line_item li LEFT JOIN civicrm_participant p on li.entity_id = p.id
        LEFT JOIN civicrm_event e ON p.event_id = e.id
        WHERE entity_table = 'civicrm_participant'
        AND p.is_test  <> 1
        group by e.id, li.label
        ORDER BY e.start_date desc, li.label ";

    $params = array();
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    while ($dao->fetch()) {
      $cur_lineitem_id = $dao->lineitem_id;
      $cur_event_id = $dao->event_id;
      $cur_event_label = $dao->event_title . ' at ' . $dao->event_start_date . ' --- priceset item: ' . $dao->lineitem_label;
      $tmp_key = $cur_event_id . "_" . $dao->lineitem_label;
      $tmp_ps_lineitems[$tmp_key] = $cur_event_label;
    }
    $dao->free();

    $form->add('select', 'event_id', ts('Event(s)'), $tmpEventIds, TRUE,
            array('id' => 'event_id', 'multiple' => 'multiple', 'title' => ts('-- select --'))
    );
    $form->add('static', 'event_id_help', '', '<div class="help">When searching on multiple events, event-specific columns will contain values from only one of the events.</div>');

    if (count($this->_allChosenEvents) > 0) {
      $line_item_choices = self::getLineItemChoicesForEvents( );
      $form->add('select', 'lineitem_id', ts('Line Item(s)'), $line_item_choices, FALSE,
              array('id' => 'lineitem_id', 'multiple' => 'multiple', 'title' => ts('-- select --'))
      );
    }

    $layout_options = array();
    $layout_options['detail_broad'] = "Participant Detail (one row per participant, extra columns for each line item)";
    $layout_options['detail'] = "Participant Detail (one row per line item)";
    $layout_options['summary'] = "Summarized";

    $layout_select = $form->add('select',
                    'layout_choice',
                    ts('Layout Choice'),
                    $layout_options,
                    false);

    $counted_options = array();
    $counted_options['counted_only'] = "Counted Statuses Only (registered, attended, etc.)";
    $counted_options['not_counted_only'] = "Uncounted Statuses Only (cancelled, no-show, etc.)";
    $counted_options[''] = "Any Status";

    $counted_select = $form->add('select',
                    'counted_choice',
                    ts('Counted Status?'),
                    $counted_options,
                    false);

    $this->util_get_all_column_names_to_display();
    $tmp_all_columns = $this->_all_column_names;

    $form->add('select', 'user_columns_to_display', ts('Columns to Display'), $tmp_all_columns, FALSE,
            array('id' => 'user_columns_to_display', 'multiple' => 'multiple', 'title' => ts('-- select --'))
    );

    $form->addDate('start_date', ts('Registration Date From'), false, array('formatType' => 'custom'));

    $form->addDate('end_date', ts('...Through'), false, array('formatType' => 'custom'));


    $form->addDate('age_date', ts('Age Based on Date'), false, array('formatType' => 'custom'));

    $gender_options_tmp = CRM_Contact_BAO_Contact::buildOptions('gender_id');

    $gender_options = array("" => "-- select --");
    foreach ($gender_options_tmp as $key => $val) {
      $gender_options[$key] = $val;
    }

    $gender_select = $form->add('select', 'gender_choice', ts('Gender'),
                    $gender_options,
                    false);

    /**
     * You can define a custom title for the search form
     */
    $this->setTitle('Fancy Participant Listing');

    /**
     * if you are using the standard template, this array tells the template what elements
     * are part of the search criteria
     */
    if (count($this->_allChosenEvents) > 0) {
      $form->assign('elements', array('event_id', 'event_id_help', 'lineitem_id', 'layout_choice', 'counted_choice', 'start_date', 'end_date', 'age_date', 'gender_choice', 'user_columns_to_display'));
    } else {
      $form->assign('elements', array('event_id', 'event_id_help', 'layout_choice', 'counted_choice', 'start_date', 'end_date', 'age_date', 'gender_choice', 'user_columns_to_display'));
    }
  }

  function setColumns() {
    if ($this->_layoutChoice == 'summary') {
      $this->_columns = array(
        // ts('Count') => 'rec_count',
        ts('Total Quantity') => 'total_qty',
        ts('Total Amount') => 'total_amount',
        // ts('Actual Participant Count') => 'actual_participant_count',
        ts('Unit Price') => 'unit_price',
        ts('Label') => 'label',
        ts('Currency') => 'currency',
        ts('Event Title') => 'event_title',
        ts('Event Start Date') => 'event_start_date'
      );
    } elseif ($this->_layoutChoice == 'detail') {
      $this->_columns = array(ts('Contact Id') => 'contact_id',
        ts('Participant Id') => 'participant_id',
        ts('Participant Status') => 'participant_status_label',
        ts('Click to View') => 'participant_link',
        ts('Line Item Id') => 'line_item_id',
        ts('Registered by') => 'registered_by_name',
        ts('Name') => 'display_name',
        ts('First Name') => 'first_name',
        ts('Last Name') => 'last_name',
        ts('Age') => 'age',
        ts('Item') => 'label',
        ts('Total Amount') => 'line_total',
        ts('Quantity') => 'qty',
        ts('Unit Price') => 'unit_price',
        ts('Register Date') => 'register_date',
        ts('Event Title') => 'event_title',
        ts('Event Start Date') => 'event_start_date',
        ts('Membership Type') => 'membership_type',
        ts('Membership Status') => 'membership_status',
        ts('Participant Note') => 'part_note',
        //  ts('Number of Memberships') => 'num_memberships',
        ts('Email') => 'email',
        ts('Phone') => 'phone',
        ts('Address') => 'street_address',
        ts('Address line 1') => 'supplemental_address_1',
        ts('City') => 'city',
        ts('State') => 'state',
        ts('Postal Code') => 'postal_code',
      );
    } else if ($this->_layoutChoice == 'detail_broad') {

      $user_columns_to_display = $this->_formValues['user_columns_to_display'];
      $all_columns_to_display = $this->util_get_all_column_names_to_display();

      $check_columns_to_display = false;
      if ($user_columns_to_display . is_array() && count($user_columns_to_display) > 0) {
        $check_columns_to_display = true;
      }

      $tmp = array();
      if (!($check_columns_to_display)) {
        // always show all columns.
        while ($cur_col_label = current($all_columns_to_display)) {
          $cur_col_name = key($all_columns_to_display);
          $tmp[$cur_col_label] = $cur_col_name;

          next($all_columns_to_display);
        }
      } elseif ($check_columns_to_display) {
        // Only show column if the user selected it.
        while ($cur_col_label = current($all_columns_to_display)) {
          $cur_col_name = key($all_columns_to_display);
          if (in_array($cur_col_name, $user_columns_to_display, true)) {
            $tmp[$cur_col_label] = $cur_col_name;
          }

          next($all_columns_to_display);
        }
      }

      $this->_columns = $tmp;
    }

    $this->columns_for_temp_table = array(ts('Contact Id') => 'contact_id',
      ts('Participant Id') => 'participant_id'
    );

    if ($this->_eventID == 'event') {
      return;
    }

    if (!is_array($this->_allChosenEvents)) {
      return;
    }

    //  Loop through each event selected by user
    foreach ($this->_allChosenEvents as $tmpEventId) {
      // for the selected event, find the price set and all the columns associated with it.
      // create a column for each field and option group within it

      if ($tmpEventId == "event") {
        continue;
      }
      $dao = $this->priceSetDAO($tmpEventId);

      if ($dao->fetch() &&
              !$dao->price_set_id) {
        CRM_Core_Error::fatal(ts('There are no events with Price Sets'));
      }


      // get all the fields and all the option values associated with it
      //  require_once 'CRM/Price/BAO/Set.php';
      //  $priceSet = CRM_Price_BAO_Set::getSetDetail( $dao->price_set_id );

      $price_set_id = $dao->price_set_id;
      // print "<br>price set id: ".$price_set_id;

      $params = array(
        'version' => 3,
        'sequential' => 1,
        'price_set_id' => $price_set_id,
      );

      $result = civicrm_api('PriceField', 'get', $params);

      if ($result['is_error'] <> 0) {
        print "<br>Error calling PriceField API<br>";
        print_r($results);
      }

      $all_pricefields_in_set = $result['values'];

      // if( is_array( $all_pricefields_in_set ) ){

      foreach ($all_pricefields_in_set as $cur_ps_field) {

        //  print "<br>field id : ".$cur_ps_field['id'] ;
        //  print "<br>";
        // print_r( $cur_ps_field );


        $params = array(
          'version' => 3,
          'sequential' => 1,
          'price_field_id' => $cur_ps_field['id'],
        );
        $result_for_options = civicrm_api('PriceFieldValue', 'get', $params);

        if ($result_for_options['is_error'] <> 0) {
          print "<br>Error calling PriceFieldValue API<br>";
          print_r($result_for_options);
        }

        $ps_field_options = $result_for_options['values'];
        // print "<br>Options:<br>";
        // print_r( $ps_field_options) ;
        // foreach ( $value['options'] as $oKey => $oValue ) {  // ONly works with version 4.3
        foreach ($ps_field_options as $cur_ps_option) {

          if ($cur_ps_option['id'] == $tmp_priceset_id) {
            // print "<br>We have a match";
            // $columnHeader = CRM_Utils_Array::value( 'label', $oValue );
            $columnHeader = "price_field_" . $cur_ps_option['id'];


            if ($cur_ps_field['html_type'] != 'Text')
              $columnHeader .= ' - ' . $cur_ps_option['label'];
          }
          $columnHeader = "price_field_" . $cur_ps_option['id'];
          // print "<Br>columnHeader for tempTable:  ".$columnHeader;
          $this->columns_for_temp_table[$columnHeader] = $columnHeader;
        }
      }
    }

    // Get priceset field options for "orphaned" options, meaning that a contribution record came in before an admin removed this option.
    $tmp_sql = "SELECT distinct price_field_id , price_field_value_id, label FROM `civicrm_line_item`";
    $dao_options = CRM_Core_DAO::executeQuery($tmp_sql);
    while ($dao_options->fetch()) {
      $tmp_price_field_value_id = $dao_options->price_field_value_id;
      $columnHeader = "price_field_" . $tmp_price_field_value_id;

      $this->columns_for_temp_table[$columnHeader] = $columnHeader;
    }

    $dao_options->free();

    // }
  }

  function getEventsWithParticipants() {
    $events = array();

    // Only get paid events associated with pricesets.
    $sql = "SELECT e.id as event_id, e.title as event_title, e.start_date as event_start_date,
     count(distinct p.id) as participant_count
    FROM civicrm_event e JOIN civicrm_participant p ON e.id = p.event_id AND p.is_test <> 1
    JOIN civicrm_price_set_entity pse ON pse.entity_table = 'civicrm_event' AND pse.entity_id = e.id
    WHERE e.is_monetary = 1
    GROUP BY e.id
    ORDER BY e.start_date desc ";

    $dao = CRM_Core_DAO::executeQuery($sql);

    while ($dao->fetch()) {
      $tmp_eid = $dao->event_id;
      $e_title = $dao->event_title;
      $e_start_date = $dao->event_start_date;
      $e_count = $dao->participant_count;


      $events[$tmp_eid] = $e_title . " - " . $e_start_date . " - participants: " . $e_count;
    }
    $dao->free();

    return $events;
  }

  function getLineItemChoicesForEvents() {
    $tmp_choices = array();
    $parms = array();

    $sql = self::util_get_priceset_lineitems_list_sql();
    $dao = CRM_Core_DAO::executeQuery($sql, $parms);

    while ($dao->fetch()) {
      // distinct(pf.id) as priceset_field_id, pf.name as priceset_field_name,
      // pf.label as priceset_field_label, li.label as line_item_name, price_field_value_id as price_field_value_id
      $field_label = $dao->priceset_field_label;
      $item_label = $dao->line_item_name;

      //print "<br><br>field label: ".$field_label." <br>item label: ".$item_label;

      if ($field_label == $item_label) {
        $tmp_label = $field_label;
      } else {
        $tmp_label = $field_label . ' --- ' . $item_label;
      }

      $field_id = $dao->priceset_field_id;
      $item_id = $dao->price_field_value_id;

      $tmp_id = "li_" . $field_id . "_" . $item_id;
      $tmp_choices[$tmp_id] = substr($tmp_label, 0, 100);
    }

    $dao->free();
    return $tmp_choices;
  }

  function util_escape_name_for_sql(&$rawstr) {
    $clean_str = "";

    $remove = array(' ', '-', '/', '(', ')', ':', '.', ';', ',', '\\', '\'', '&', '%', '@', '#', '^', '*', '!', '=', '+', '<', '>', '?', '~', '`', '|', '[', ']', '{', '}');
    $clean_str = str_replace($remove, '_', $rawstr);

    return $clean_str;
  }

  function util_get_priceset_lineitems_list_sql() {
    $li_where = self::getListItemWhere();

    $tmp_sql = "SELECT distinct(pf.id) as priceset_field_id, pf.name as priceset_field_name, pf.label as priceset_field_label,
                              li.label as line_item_name, price_field_value_id as price_field_value_id
                      FROM civicrm_participant p
                      JOIN civicrm_contact c ON p.contact_id  = c.id AND c.is_deleted <> 1
                 LEFT JOIN civicrm_line_item li ON p.id = li.entity_id AND p.is_test <> 1
                 AND li.entity_table = 'civicrm_participant'
                 LEFT JOIN civicrm_event e ON p.event_id = e.id 
                 LEFT JOIN civicrm_price_field pf ON li.price_field_id = pf.id  " . $li_where .
            "GROUP BY pf.id, price_field_value_id
                  ORDER BY pf.id , li.label";

    //print "<br><br>Price set sql: ".$tmp_sql; 
    return $tmp_sql;
  }

  function util_get_custom_field_name_list_for_display() {
    $cf_names = array();
    $cf_name_sql = "SELECT cg.title as table_label, cg.table_name as table_name, cf.column_name as column_name, cf.label as label
                FROM civicrm_custom_group cg LEFT JOIN civicrm_custom_field cf ON cg.id = cf.custom_group_id 
                WHERE cg.extends = 'Participant' and cf.name is NOT NULL";

    $parms = array();
    $names = array();

    //  print "<br>custom field sql: ". $cf_name_sql;

    $dao = CRM_Core_DAO::executeQuery($cf_name_sql, $parms);

    while ($dao->fetch()) {
      $cur_table_name = $dao->table_name;
      $cur_table_label = $dao->table_label;
      $cur_field_name = $dao->column_name;
      $cur_field_label = $dao->label;
      $names[$cur_field_name] = $cur_table_label . "::" . $cur_field_label;
    }

    $dao->free();
    return $names;
  }

  function util_get_custom_field_name_list_for_select() {
    $cf_names = array();
    $cf_name_sql = "SELECT cg.table_name as table_name, cf.column_name as name
                FROM civicrm_custom_group cg LEFT JOIN civicrm_custom_field cf ON cg.id = cf.custom_group_id 
                WHERE cg.extends = 'Participant' and cf.name is NOT NULL";

    $parms = array();
    $sql = "";
    //  print "<br>custom field sql: ". $cf_name_sql ;
    $dao = CRM_Core_DAO::executeQuery($cf_name_sql, $parms);
    while ($dao->fetch()) {
      $cur_table_name = $dao->table_name;
      $cur_field_name = $dao->name;
      $sql = $sql . " " . $cur_table_name . "." . $cur_field_name . " , ";
    }
    $dao->free();

    return $sql;
  }

  function util_get_custom_field_sql() {
    $cf_table_names = array();
    $cf_table_name_sql = "SELECT table_name
                FROM civicrm_custom_group cg WHERE extends = 'Participant'";

    $parms = array();
    $dao = CRM_Core_DAO::executeQuery($cf_table_name_sql, $parms);
    while ($dao->fetch()) {
      $cur_table_name = $dao->table_name;
      $cf_table_names[] = $cur_table_name;
    }
    $dao->free();

    // now we have a nice array of table names for all custom field sets used for participants.
    $sql = "";
    foreach ($cf_table_names as $cur_table) {
      $sql = $sql . " LEFT JOIN " . $cur_table . " ON p.id = " . $cur_table . ".entity_id ";
    }

    return $sql;
  }

  function util_get_all_column_names_to_display() {
    $tmp_all_column_names = array();

    $tmp_all_column_names['contact_id'] = 'CID';
    $tmp_all_column_names['participant_id'] = 'PID';
    $tmp_all_column_names['participant_status_label'] = 'Participant Status';
    $tmp_all_column_names['participant_link'] = 'Link';
    $tmp_all_column_names['display_name'] = 'Display Name';
    $tmp_all_column_names['first_name'] = 'First Name';
    $tmp_all_column_names['last_name'] = 'Last Name';
    $tmp_all_column_names['age'] = 'Age';
    $tmp_all_column_names['email'] = 'Email';
    $tmp_all_column_names['phone'] = 'Phone';
    $tmp_all_column_names['street_address'] = 'Street Address';
    $tmp_all_column_names['supplemental_address_1'] = 'Supplemental Address 1';
    $tmp_all_column_names['city'] = 'City';
    $tmp_all_column_names['postal_code'] = 'Postal Code';
    $tmp_all_column_names['state'] = 'State';
    $tmp_all_column_names['registered_by_name'] = 'Registered by';
    $tmp_all_column_names['register_date'] = 'Register Date';
    $tmp_all_column_names['event_title'] = 'Event Title';
    $tmp_all_column_names['event_start_date'] = 'Event Start Date';
    $tmp_all_column_names['membership_type'] = 'Membership Type';
    $tmp_all_column_names['membership_status'] = 'Membership Status';
    $tmp_all_column_names['part_note'] = 'Participant Note';
    // $tmp_all_column_names['currency'] = 'Currency';


    $sql_li_name_sql = $this->util_get_priceset_lineitems_list_sql();

    $params = array();
    $li_names_dao = CRM_Core_DAO::executeQuery($sql_li_name_sql, $params);
    $li_names = array();

    $i = 1;
    while ($li_names_dao->fetch()) {
      $cur_name = $li_names_dao->line_item_name;
      $priceset_field_id = $li_names_dao->priceset_field_id;
      $priceset_field_name = $li_names_dao->priceset_field_name;
      $priceset_field_label = $li_names_dao->priceset_field_label;
      $priceset_field_value_id = $li_names_dao->price_field_value_id;

      if (strlen($cur_name) == 0) {
        $cur_name = "blank";
      }



      $col_label = "";
      if ($priceset_field_label <> $cur_name) {
        $col_label = $priceset_field_label . ' - ' . $cur_name . ' Qty';
      } else {
        $col_label = $cur_name . ' Qty';
      }


      $cur_table_name = "li_" . $priceset_field_id . "_" . $priceset_field_value_id;
      $cur_col_name = $cur_table_name . '_qty';



      $tmp_all_column_names[$cur_col_name] = $col_label;
    }
    $li_names_dao->free();


    // Add colums for each custom data field.
    $cf_names = $this->util_get_custom_field_name_list_for_display();
    while ($cf_col_label = current($cf_names)) {
      $cur_col_name = key($cf_names);
      $tmp_all_column_names[$cur_col_name] = $cf_col_label;

      next($cf_names);
    }

    $this->_all_column_names = $tmp_all_column_names;
    return $this->_all_column_names;
  }

  function util_get_priceset_field_options($event_id_parm) {
    $tmp_priceset_field_option_labels = array();
    $tmp_priceset_field_option_values = array();

    $dao = $this->priceSetDAO($event_id_parm);

    if ($dao->fetch() && !$dao->price_set_id) {
      CRM_Core_Error::fatal(ts('There are no events with Price Sets'));
    }

    // get all the fields and all the option values associated with it
    require_once 'CRM/Price/BAO/Set.php';
    $priceSet = CRM_Price_BAO_Set::getSetDetail($dao->price_set_id);

    return $priceSet;
  }

  function select($sum_flag) {

    if ($sum_flag == 'sum_only') {
      $tmp_select = " count( distinct p.id  ) as rec_count,
sum(li.qty) as total_qty, min(li.unit_price) as min_unit_price, max(li.unit_price) as max_unit_price, avg(li.unit_price) as avg_unit_price, 
sum(li.line_total) as total_amount , 
sum(li.participant_count) as actual_participant_count, li.label, e.currency as currency,
 e.title as event_title, e.start_date as event_start_date
 ";
    } else {
      print "<br> Todo";
    }

    return $tmp_select;
  }

  function all($offset = 0, $rowcount = 0, $sort = null, $includeContactIDs = false, $onlyIDs = false) {
    $all_sql = '';

    $ageDate = CRM_Utils_Date::processDate($this->_formValues['age_date']);

    if ($ageDate) {
      $yyyy = substr($ageDate, 0, 4);
      $mm = substr($ageDate, 4, 2);
      $dd = substr($ageDate, 6, 2);

      $tmp = $yyyy . "-" . $mm . "-" . $dd;
      $age_cutoff_date = "'" . $tmp . "'";
    } else {
      $age_cutoff_date = "now()";
    }

    $tmp_age_calc = "((date_format($age_cutoff_date,'%Y') - date_format(contact_a.birth_date,'%Y')) -
              (date_format($age_cutoff_date,'00-%m-%d') < date_format(contact_a.birth_date,'00-%m-%d'))) as age, ";

    if ($this->_layoutChoice == 'summary') {
      $selectClause = "
        count( p.id  ) as rec_count,  pf.name as priceset_field_name, pf.label as priceset_field_label,
        sum(li.qty) as total_qty  , li.unit_price,  sum( li.line_total) as total_amount  ,
        li.participant_count, if( pf.label <> li.label,  concat(pf.label, ' - ', li.label), li.label) as label ,e.currency as currency,
        e.title as event_title, e.start_date as event_start_date
      ";
      $groupBy = "GROUP BY li.price_field_id, li.price_field_value_id,  e.title, e.start_date";
    } elseif ($this->_layoutChoice == 'detail') {
      if ($onlyIDs) {
        $selectClause = "contact_a.id as contact_id, p.id as participant_id ";
      } else {
        $selectClause = "contact_a.id as contact_id, p.id as participant_id, '' as participant_link, contact_a.sort_name as display_name,
          contact_a.first_name, contact_a.last_name, civicrm_email.email as email, civicrm_phone.phone as phone,
          civicrm_address.street_address as street_address, civicrm_note.note as part_note,
          civicrm_address.supplemental_address_1 as supplemental_address_1, civicrm_address.city as city ,civicrm_address.postal_code as postal_code,
          civicrm_state_province.abbreviation as state, p.registered_by_id, contact_b.sort_name as registered_by_name,
          li.id as line_item_id,
          li.qty, li.unit_price, li.line_total, li.participant_count, if( pf.label <> li.label,  concat(pf.label, ' - ', li.label), li.label) as label,
          e.currency as currency, " . $tmp_age_calc . "
          p.register_date, e.title as event_title, e.start_date as event_start_date,
          mt.name as membership_type, ms.label as membership_status, status.label as participant_status_label ";
      }
      $groupBy = " group by li.id";
    } elseif ($this->_layoutChoice == 'detail_broad') {
      if ($onlyIDs) {
        $selectClause = "contact_a.id as contact_id, p.id as participant_id";
      } else {
        $selectClause = "
          contact_a.id as contact_id, p.id as participant_id, '' as participant_link,
          contact_a.sort_name   as display_name, contact_a.first_name, contact_a.last_name,
          civicrm_email.email as email, civicrm_phone.phone as phone, civicrm_address.street_address as street_address, civicrm_note.note as part_note,
          civicrm_address.supplemental_address_1 as supplemental_address_1, civicrm_address.city as city ,civicrm_address.postal_code as postal_code,
          civicrm_state_province.abbreviation as state, p.registered_by_id, contact_b.sort_name as registered_by_name," . $this->util_get_li_sql_fragment('SELECT')
          . $this->util_get_custom_field_name_list_for_select()
          . "  e.currency as currency, " . $tmp_age_calc . "
          p.register_date, e.title as event_title, e.start_date as event_start_date,
          mt.name as membership_type, ms.label as membership_status , status.label as participant_status_label
        ";
        $groupBy = "group by p.id";
      }
    } else {
      print "<br><br>Unrecognized layout choice: " . $this->_layoutChoice;
    }

    $all_sql = $this->sql($selectClause, $offset, $rowcount, $sort, $includeContactIDs, $groupBy);
    return $all_sql;
  }

  function from() {
    static $from;
    if (!isset($from)) {
      // Define a flag to control whether to limit memberships to the one with
      // the lowest weight. On layouts that don't include membership status,
      // there's no need to bother with this. But for layouts that do include
      // membership status, this will have the effect of showing membership
      // information only for the participant's membership which has the lowest
      // weight. (For example, "New" typically has a lower weight than "Expired,"
      // so if a participant has one memberships "New" and one membrership
      // "Expired," this flag set TRUE will have the effect of displaying the "New"
      // membership instead of the "Expired" one.
      $limit_memberships_by_status_weight = FALSE;

      if ($this->_layoutChoice == 'summary') {
        $from = "FROM
          civicrm_participant p
          LEFT JOIN civicrm_participant_status_type status  ON p.status_id  = status.id
          LEFT JOIN civicrm_line_item li ON p.id = li.entity_id AND li.entity_table = 'civicrm_participant'
          LEFT JOIN civicrm_event e ON p.event_id = e.id
          JOIN civicrm_contact contact_a on p.contact_id = contact_a.id
          LEFT JOIN civicrm_price_field pf ON li.price_field_id = pf.id
        ";
      } elseif ($this->_layoutChoice == 'detail') {
        // Define FROM, with membership table name as a sprintf() type specifier,
        // because we'll be substituting that table name below.
        $from = " FROM
          civicrm_participant p
          LEFT JOIN civicrm_participant_status_type status ON p.status_id = status.id
          LEFT JOIN civicrm_line_item li ON p.id = li.entity_id
          AND li.entity_table = 'civicrm_participant'
          LEFT JOIN civicrm_event e ON p.event_id = e.id
          Left  JOIN civicrm_participant p2 on p.registered_by_id = p2.id
          LEFT JOIN civicrm_contact contact_b on p2.contact_id = contact_b.id
          JOIN civicrm_contact contact_a on p.contact_id = contact_a.id
          left join %s m on contact_a.id = m.contact_id
          left join civicrm_membership_type mt on m.membership_type_id = mt.id
          left join civicrm_membership_status ms on m.status_id = ms.id
          LEFT JOIN civicrm_note  ON civicrm_note.entity_table ='civicrm_participant' AND civicrm_note.entity_id = p.id
          left join civicrm_email on contact_a.id = civicrm_email.contact_id AND (civicrm_email.is_primary = 1 OR civicrm_email.email is null)
          left join civicrm_phone on contact_a.id = civicrm_phone.contact_id  AND (civicrm_phone.is_primary = 1 OR civicrm_phone.phone is null)
          left join civicrm_address on contact_a.id = civicrm_address.contact_id AND (civicrm_address.is_primary = 1 OR civicrm_address.street_address is null)
          left join civicrm_state_province on civicrm_address.state_province_id = civicrm_state_province.id AND (civicrm_state_province.abbreviation like '%' or civicrm_state_province.abbreviation is null)
          LEFT JOIN civicrm_price_field pf ON li.price_field_id = pf.id
        ";
        // This layout includes membership status, so limit to the membership with
        // the lowest weight.
        $limit_memberships_by_status_weight = TRUE;
      } elseif ($this->_layoutChoice == 'detail_broad') {
        // Need to determine how many line items are connected to this participant, so that we have the correct number of columns in the result set/select statement.
        /*
          // Tried join below to get role name, but need to figure out how to handle p.role_id when it is multi-value.
          join civicrm_option_value role ON p.role_id = role.value
          join civicrm_option_group og_role ON role.option_group_id = og_role.id AND og_role.name = 'participant_role'
         */
        // Define FROM, with membership table name as a sprintf() type specifier,
        // because we'll be substituting that table name below.
        $from = "FROM
          civicrm_participant p LEFT JOIN civicrm_event e ON p.event_id = e.id
          LEFT JOIN civicrm_participant_status_type status ON p.status_id = status.id
          Left  JOIN civicrm_participant p2 on p.registered_by_id = p2.id
          LEFT JOIN civicrm_contact contact_b on p2.contact_id = contact_b.id
          JOIN civicrm_contact contact_a on p.contact_id = contact_a.id
          left join %s m on contact_a.id = m.contact_id
          left join civicrm_membership_type mt on m.membership_type_id = mt.id
          left join civicrm_membership_status ms on m.status_id = ms.id
          LEFT JOIN civicrm_note  ON civicrm_note.entity_table ='civicrm_participant' AND civicrm_note.entity_id = p.id
          left join civicrm_email on contact_a.id = civicrm_email.contact_id
          left join civicrm_phone on contact_a.id = civicrm_phone.contact_id
          left join civicrm_address on contact_a.id = civicrm_address.contact_id
          left join civicrm_state_province on civicrm_address.state_province_id = civicrm_state_province.id
        "
        . $this->util_get_li_sql_fragment('FROM')
        . $this->util_get_custom_field_sql();
        // This layout includes membership status, so limit to the membership with
        // the lowest weight.
        $limit_memberships_by_status_weight = TRUE;
      }

      // One time only, create a table with the relevant memberships, if called
      // for by $limit_memberships_by_status_weight.
      static $created_membership_temp_table = FALSE;
      if (!$created_membership_temp_table && $limit_memberships_by_status_weight) {
        // We create a temporary table, with a unique index on contact_id, so
        // that we can fill it with only one membership per contact.
        $membership_temp_table_name = 'jvillage_fancy_participant_membership';
        CRM_Core_DAO::executeQuery("CREATE TEMPORARY TABLE $membership_temp_table_name LIKE civicrm_membership");
        CRM_Core_DAO::executeQuery("ALTER TABLE $membership_temp_table_name ADD UNIQUE (contact_id)");

        // We're going to sort all relevant memberships (those belonging to
        // contacts who will show in the search results) by lowest membership
        // status weight. We'll then copy them into $membership_temp_table_name.
        // The unique index on contact_id, together with the use of INSERT
        // IGNORE, will ensure that only the memberships with the
        // lowest-weighted status will be copied into the table.
        
        // First, we need to replace %s in $from with civicrm_membership, so we
        // can populate the temp table using actual memberships.
        $sql_from = sprintf($from, 'civicrm_membership');
        $sql = "INSERT IGNORE INTO $membership_temp_table_name SELECT m.* " . $sql_from;
        $where = $this->where();
        if (!empty($where)) {
          $sql .= " WHERE " . $where;
        }        
        $sql .= "group by m.id order by ms.weight";
        CRM_Core_DAO::executeQuery($sql);

        // Replace %s with $membership_temp_table_name in returned $from value,
        // so that this table is used in place of civicrm_membership in the
        // final search query. This will limit the query
        // results only to the memberships with lowest-weighted status per
        // conatact.
        $from = sprintf($from, $membership_temp_table_name);

        // Note that we've created the temp table and don't need to do it again.
        $created_membership_temp_table = TRUE;
      }
    }
    return $from;
  }

  function getListItemWhere() {
    // print "<hr><br>Inside where function.";
    // 'filter_type',  'priceset_option_id', 'priceset_option_id_lineitems'
    $tmp_where = '';
    $partial_sql = '';

    $filter_type = $this->_formValues['filter_type'];
    //  print "<br>Filter type: ".$filter_type;
    if ($filter_type == 'priceset_items') {

      $tmp_lineitems_array = $this->_formValues['priceset_option_id_lineitems'];
      //print_r($tmp_lineitems_array ) ;
      if (!is_array($tmp_lineitems_array)) {
        return;
      }

      $i = 1;
      $tmp_lineitem_ids = '';
      foreach ($tmp_lineitems_array as $cur_lineitem) {
        $tmp_lineitem_ids = $tmp_lineitem_ids . $cur_lineitem;
        if ($i < sizeof($tmp_lineitems_array)) {
          $tmp_lineitem_ids = $tmp_lineitem_ids . ", ";
        }

        $i = $i + 1;
      }

      if (strlen($tmp_lineitem_ids) > 0) {
        $partial_sql = "li.id IN ( " . $tmp_lineitem_ids . ")";

        // $partial_sql = $partial_sql." AND "    ;
      }
    } else {



      //$tmp_priceset_id =  $this->_pricesetOptionId ;
      // print_r( $this->_allChosenPricesetOptions) ;
      if (!is_array($this->_allChosenPricesetOptions)) {
        return;
      }


      $need_or = false;
      $first_item = true;


      foreach ($this->_allChosenEvents as $curOption) {
        // foreach($this->_allChosenPricesetOptions as $curOption){
        //    print "<br><br>cur option in where loop: ".$curOption;
        if ($curOption == 'id' || $curOption == 'event' || (strlen($curOption) == 0)) {

          continue;
        }
        if ($first_item) {
          $partial_sql = " ( ";
        }

        if ($need_or) {
          $partial_sql = $partial_sql . " OR ";
        }
        // $tmp_fieldname = "price_field_".$curOption;
        // $partial_sql = $partial_sql.$tmp_fieldname." > 0 ";
        $partial_sql = $partial_sql . " e.id = " . $curOption;
        $first_item = false;

        $need_or = true;
      }
      if ($need_or) {
        $partial_sql = $partial_sql . " )  ";
      }
    }


    if (strlen($partial_sql) > 0) {
      $tmp_where = " WHERE " . $partial_sql;
    } else {
      $tmp_where = "";
    }

    return $tmp_where;
  }

  function where($includeContactIDs = false, $summary_section = false) {

    $clauses = array();
    $tmp_rtn = '';
    //$partial_sql = '';

    $event_id_filter = '';

    $need_or = false;
    $first_item = true;


    foreach ($this->_allChosenEvents as $curOption) {

      if ($curOption == 'id' || $curOption == 'event' || (strlen($curOption) == 0)) {

        continue;
      }
      if ($first_item) {
        $event_id_filter = " ( ";
      }

      if ($need_or) {
        $event_id_filter = $event_id_filter . " OR ";
      }


      $event_id_filter = $event_id_filter . " e.id = " . $curOption;
      $first_item = false;

      $need_or = true;
    }
    if ($need_or) {
      $event_id_filter = $event_id_filter . " ) ";
    }

    if (strlen($event_id_filter) > 0) {
      $clauses[] = $event_id_filter;
    } else {
      //print "<h2>Error: No event(s) selected!";
      return "";
    }
    // 'lineitem_id'
    // li_pricefieldID_valueID = 'valueID'
    // li_224_522.price_field_id = '224'
    // li_224_522.price_field_value_id = '522' 
    $layout_choice = $this->_formValues['layout_choice'];
    $line_item_id = $this->_formValues['lineitem_id'];
    $tmp_li_sql = "";
    // print_r($line_item_id);
    if (is_array($line_item_id) && count($line_item_id) > 0) {

      if ($summary_section <> true && $layout_choice == 'detail_broad') {
        foreach ($line_item_id as $cur_li) {
          $tmp_split_id = explode('_', $cur_li);
          $field_id = $tmp_split_id[1];
          $value_id = $tmp_split_id[2];

          if (strlen($tmp_li_sql) > 0) {
            $tmp_li_sql = $tmp_li_sql . " OR ";
          }

          $tmp_li_sql = $tmp_li_sql . "  (" . $cur_li . ".id IS NOT NULL ) ";
        }

        if (strlen($tmp_li_sql) > 0) {
          $tmp_li_sql = " ( " . $tmp_li_sql . " )";
        }
      } else {
        foreach ($line_item_id as $cur_li) {
          $tmp_split_id = explode('_', $cur_li);
          $field_id = $tmp_split_id[1];
          $value_id = $tmp_split_id[2];

          if (strlen($tmp_li_sql) > 0) {
            $tmp_li_sql = $tmp_li_sql . " OR  ";
          }

          $tmp_li_sql = $tmp_li_sql . "  (li.price_field_id = '" . $field_id . "' AND li.price_field_value_id = '" . $value_id . "'  ) ";
        }

        if (strlen($tmp_li_sql) > 0) {
          $tmp_li_sql = "  ( " . $tmp_li_sql . " )";
        }


        // li.price_field_id
      }

      $clauses[] = $tmp_li_sql;
    }
    /*
      $counted_options['counted_only'] = "Counted Statuses Only (registered, attended, etc.)";
      $counted_options['not_counted_only'] = "Uncounted Statuses Only (cancelled, no-show, etc.)";
      $counted_options[''] = "Any Status";

     */
    if ($this->_formValues['counted_choice'] == 'counted_only') {
      $clauses[] = " status.is_counted = 1 ";
    } else if ($this->_formValues['counted_choice'] == 'not_counted_only') {
      $clauses[] = " status.is_counted = 0 ";
    }

    $startDate = CRM_Utils_Date::processDate($this->_formValues['start_date']);
    if ($startDate) {
      $clauses[] = " (date(p.register_date) >= date( " . $startDate . "))  ";
    }


    $endDate = CRM_Utils_Date::processDate($this->_formValues['end_date']);
    if ($endDate) {
      $clauses[] = " (date(p.register_date) <= date( $endDate )) ";
    }

    $gender_choice = $this->_formValues['gender_choice'];

    if (strlen($gender_choice) > 0) {
      $clauses[] = "contact_a.gender_id = $gender_choice";
    }

    $clauses[] = " (p.is_test <> 1 ) ";
    $clauses[] = " (contact_a.is_deleted <> 1) ";

    $tmp_rtn = implode(' AND ', $clauses);

    //  print "<br> where :".$tmp_rtn; 
    return $tmp_rtn;
  }

  // FIXME: [ML] why was this disabled?
  function summaryxxx() {
    $sum_array = array();

    $grand_totals = true;
    /*  SELECT count( distinct p.id ) as rec_count, pf.name as priceset_field_name, pf.label as priceset_field_label, sum(li.qty) as total_qty, min(li.unit_price) as min_unit_price, avg(li.unit_price) as avg_unit_price, sum(li.line_total) as total_amount , sum(li.participant_count) as actual_participant_count, if( pf.label <> li.label, concat(pf.label, ' - ', li.label), li.label) as label ,e.currency as currency, e.title as event_title, e.start_date as event_start_date

     */
    $tmp_select = "
sum(t1.total_qty) as total_qty,  t1.min_unit_price,  t1.avg_unit_price, 
t1.total_amount , 
sum(t1.actual_participant_count) as actual_participant_count, t1.label, t1.currency as currency,
 t1.event_title, t1.event_start_date
 ";

    $tmp_inner_sql = self::all();
    $sql = "SELECT " . $tmp_select . " from ( " . $tmp_inner_sql . " ) as t1";

    $totalSelect = $this->select('sum_only');
    $from = $this->from();
    $where = $this->where(false, true);
    $group_by = "e.currency, li.label , e.title, e.start_date";

    $sql = "SELECT  $totalSelect
          $from
        WHERE $where ";

    // GROUP BY $group_by";
    // print "<br><br>Summary Section  sql: ".$sql;

    $dao = CRM_Core_DAO::executeQuery($sql, CRM_Core_DAO::$_nullArray);

    while ($dao->fetch()) {
      $cur_sum = array();

      if ($layout_choice == 'detail_broad') {
        $cur_sum['Currency'] = $dao->currency;
        $cur_sum['Event Title'] = $dao->event_title;
        $cur_sum['Event Start Date'] = $dao->event_start_date;
      } else {
        //$cur_sum['Registration Count'] = $dao->rec_count;
        $cur_sum['Total Quantity'] = $dao->total_qty;
        $cur_sum['Total Amount'] = $dao->total_amount;
        //$cur_sum['Actual Participant Count'] = $dao->actual_participant_count;
        $cur_sum['Min. Unit Price'] = $dao->min_unit_price;
        $cur_sum['Max. Unit Price'] = $dao->max_unit_price;
        //$cur_sum['Avg. Unit Price'] = $dao->avg_unit_price;
        //$cur_sum['Label'] = $dao->label;
        $cur_sum['Currency'] = $dao->currency;
        $cur_sum['Event Title'] = $dao->event_title;
        $cur_sum['Event Start Date'] = $dao->event_start_date;
      }

      $sum_array[] = $cur_sum;
    }

    $dao->free();
    return $sum_array;
  }

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

    $replaceSQL = "SELECT contact_a.id as contact_id, p.id as participant_id";
    $insertSQL = "
INSERT INTO civicrm_prevnext_cache (entity_table, entity_id1, entity_id2, cacheKey, data)
SELECT DISTINCT 'civicrm_contact', contact_a.id as contact_id, contact_a.id as contact_id, '$cacheKey', contact_a.display_name
";

    $sql = str_replace($replaceSQL, $insertSQL, $sql);
    return $sql;
  }

  function templateFile() {
    return 'CRM/Contact/Form/Search/Custom.tpl';
  }

  function setDefaultValues() {
    return array();
  }

  function alterRow(&$row) {
    $row['participant_link'] = "<a href='/civicrm/contact/view/participant?reset=1&id=" . $row['participant_id'] . "&cid=" . $row['contact_id'] . "&action=view&context=participant&selectedChild=event'>View Participant</a>";
    // $participant_url =" /civicrm/contact/view/participant?reset=1&id=31&cid=176&action=view&context=participant&selectedChild=event";
  }

  function setTitle($title) {
    if ($title) {
      CRM_Utils_System::setTitle($title);
    } else {
      CRM_Utils_System::setTitle(ts('Export Price Set Info for an Event'));
    }
  }

  function util_get_li_sql_fragment($fragment) {
    static $fragments = array();
    $fragment = strtoupper($fragment);
    if (!isset($fragments[$fragment])) {
      $fragments['SELECT'] = '';
      $fragments['FROM'] = '';

      $sql_li_name_sql = $this->util_get_priceset_lineitems_list_sql();

      $params = array();
      $li_names_dao = CRM_Core_DAO::executeQuery($sql_li_name_sql, $params);

      $i = 1;
      while ($li_names_dao->fetch()) {
        $cur_name = $li_names_dao->line_item_name;
        $priceset_field_id = $li_names_dao->priceset_field_id;
        $priceset_field_name = $li_names_dao->priceset_field_name;
        $priceset_field_value_id = $li_names_dao->price_field_value_id;

        if (strlen($cur_name) == 0) {
          $cur_name = "blank";
        }


        //$cur_table_name_raw = "li_".$priceset_field_id."_".$cur_name;
        //$cur_table_name = $this->util_escape_name_for_sql($cur_table_name_raw  ) ;
        //$cur_name_clean = $this->util_escape_name_for_sql(  $cur_name);
        $cur_table_name = "li_" . $priceset_field_id . "_" . $priceset_field_value_id;
        // print "<br><br> cur table name: ".$cur_table_name;


        $join_table_name = $cur_table_name;
        $fragments['SELECT'] .= $cur_table_name . ".id as " . $join_table_name . "_id,
                                   " . $cur_table_name . ".qty as " . $join_table_name . "_qty,
                                   " . $cur_table_name . ".unit_price as " . $join_table_name . "_unit_price,
                                   " . $cur_table_name . ".line_total as " . $join_table_name . "_line_total,
                                   " . $cur_table_name . ".participant_count as " . $join_table_name . "_participant_count ,
                                   " . $cur_table_name . ".label as " . $join_table_name . "_label, ";
        $tmp_all_column_names[] = $join_table_name . "_qty";

        $fragments['FROM'] .= " LEFT JOIN civicrm_line_item " . $join_table_name . " ON p.id = " . $join_table_name . ".entity_id AND " . $join_table_name . ".entity_table = 'civicrm_participant' AND  " . $join_table_name . ".price_field_id = '" . $priceset_field_id . "'  AND " . $join_table_name . ".price_field_value_id = '" . $priceset_field_value_id . "'  ";
        $i = $i + 1;
      }
      $li_names_dao->free();
    }

    return $fragments[$fragment];
  }

}
