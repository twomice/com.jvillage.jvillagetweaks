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

require_once 'CRM/Contact/Form/Search/Interface.php';

class CRM_Contact_Form_Search_Custom_InHonorOf extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {
  protected $_formValues;

  function __construct(&$formValues) {
    $this->_formValues = $formValues;

    /**
     * Define the columns for search result rows
     */
    $this->_columns = array(
      ts('Donor Sort Name')  => 'sort_name',
      ts('Donor Display Name')  => 'display_name',
      ts('Joint Greeting') => 'joint_greeting',
      ts('Tribute Type') => 'honor_type_label',	
      ts('Tribute Sort Name') => 'tribute_sort_name',
      ts('Tribute Display Name') => 'tribute_display_name',
      ts('Amount') => 'total_amount',
      ts('Financial Type') => 'financial_type_name',
      ts('Contribution Date') => 'contrib_date',
      ts('Contribution Status') => 'contrib_status_label',
      ts('Tribute Note') => 'tribute_note',
      ts('Notify Sort Name') =>  't_n_sort_name', 
      ts('Notify Display Name') =>  't_n_display_name', 
      ts('Notify Email') => 't_n_email',
      ts('Notify Street Address') => 't_n_street_address',
      ts('Notify City') => 't_n_city',
      ts('Notify State/Province') => 't_n_state_name',
      ts('Notify Country') => 't_n_country_name', 
      ts('Notify Postal/Zip Code') => 't_n_postal_code', 
      ts('Donor Contact ID') => 'contact_id', 
    );
  }

  function buildForm(&$form) {
    /**
     * You can define a custom title for the search form
     */
    $this->setTitle('Find Tribute Contributions (ie In Honor of, In Memory of, etc)');

    /**
     * Define the search form fields here
     */
    require_once 'utils/util_money.php';
    if (pogstone_is_user_authorized('access CiviContribute') == false) {
      $this->setTitle('Not Authorized');
      return; 
    }

    $form->addDate('start_date', ts('Contribution Date From'), false, array( 'formatType' => 'custom'));
    $form->addDate('end_date', ts('...through'), false, array( 'formatType' => 'custom'));

    /*
      $tag = array('' => ts('- any tag -')) + CRM_Core_PseudoConstant::tag();
      $form->addElement('select', 'tag', ts('Tagged'), $tag);
     */

    /**
     * If you are using the sample template, this array tells the template fields to render
     * for the search form.
     */
    $form->assign('elements', array('start_date', 'end_date','tag'));
  }

  /**
   * Define the smarty template used to layout the search form and results listings.
   */
  function templateFile() {
    //return 'CRM/Contact/Form/Search/Custom.tpl';
    return 'CRM/Contact/Form/Search/Custom/Sample.tpl';
  }

  /**
   * Construct the search query
   */
  function all( $offset = 0, $rowcount = 0, $sort = null, $includeContactIDs = false, $onlyIDs = false ) {
    // check authority of end-user
    require_once 'utils/util_money.php';
    if (pogstone_is_user_authorized('access CiviContribute') == false) {
      return "select 'You are not authorized to this area' as total_amount from  civicrm_contact where 1=0 limit 1";
    }

    // SELECT clause must include contact_id as an alias for civicrm_contact.id
    if ($onlyIDs) {
      $select = "contact_a.id as contact_id";
    }
    else {
      $params = array(
        'version' => 3,
        'sequential' => 1,
        'name' => 'Tribute_first_name',
      );

      $result = civicrm_api('CustomField', 'getsingle', $params);
      $t_first_name = $result['column_name']; 

      $params = array(
        'version' => 3,
        'sequential' => 1,
        'name' => 'Tribute_last_name',
      );

      $result = civicrm_api('CustomField', 'getsingle', $params);
      $t_last_name = $result['column_name']; 
 
      // Tribute_Note
      $params = array(
        'version' => 3,
        'sequential' => 1,
        'name' => 'Tribute_Note',
      );

      $result = civicrm_api('CustomField', 'getsingle', $params);
      $t_note = $result['column_name']; 

      // Tribute_Notification_Prefix
      $params = array(
        'version' => 3,
        'sequential' => 1,
        'name' => 'Tribute_Notification_Prefix',
      );

      $result = civicrm_api('CustomField', 'getsingle', $params);
      $t_n_prefix = $result['column_name'];
 
      // Tribute_Notification_First_Name
      $params = array(
        'version' => 3,
        'sequential' => 1,
        'name' => 'Tribute_Notification_First_Name',
      );

      $result = civicrm_api('CustomField', 'getsingle', $params);
      $t_n_first_name = $result['column_name']; 

      // Tribute_Notification_Last_Name
      $params = array(
        'version' => 3,
        'sequential' => 1,
        'name' => 'Tribute_Notification_Last_Name',
      );

      $result = civicrm_api('CustomField', 'getsingle', $params);
      $t_n_last_name = $result['column_name'];

      // Tribute_Notification_Email
      $params = array(
        'version' => 3,
        'sequential' => 1,
        'name' => 'Tribute_Notification_Email',
      );

      $result = civicrm_api('CustomField', 'getsingle', $params);
      $t_n_email = $result['column_name']; 

      // Tribute_Notification_Street_Address
      $params = array(
        'version' => 3,
        'sequential' => 1,
        'name' => 'Tribute_Notification_Street_Address',
      );

      $result = civicrm_api('CustomField', 'getsingle', $params);
      $t_n_street_address = $result['column_name'];

      // Tribute_Notification_City
      $params = array(
        'version' => 3,
        'sequential' => 1,
        'name' => 'Tribute_Notification_City',
      );

      $result = civicrm_api('CustomField', 'getsingle', $params);
      $t_n_city = $result['column_name'];

      // Tribute_Notification_Country
      // Tribute_Notification_State_Province

      // Tribute_Notification_Postal_Code
      $params = array(
        'version' => 3,
        'sequential' => 1,
        'name' => 'Tribute_Notification_Postal_Code',
      );

      $result = civicrm_api('CustomField', 'getsingle', $params);
      $t_n_postal_code = $result['column_name']; 

      require_once('utils/Entitlement.php');
      $tmpEntitlement = new Entitlement();
 
      $select  = " contact_a.id AS contact_id, contact_a.sort_name AS sort_name, contact_a.display_name AS display_name,
        CASE WHEN soft.id IS NOT NULL
             THEN con2.sort_name 
             ELSE concat( ifnull(tribute.$t_last_name, ''), ', ', ifnull(tribute_prefix.tribute_prefix_label, ''), ' ', ifnull(tribute.$t_first_name, '')) END AS tribute_sort_name,
        CASE WHEN soft.id IS NOT NULL
             THEN con2.display_name
             ELSE concat( ifnull(tribute_prefix.tribute_prefix_label, ''), ' ', ifnull(tribute.$t_first_name, '') , ' ', ifnull(tribute.$t_last_name, '')) END AS tribute_display_name,
        CASE WHEN soft.id IS NOT NULL
             THEN soft.amount
             ELSE contr.total_amount END as total_amount,
        ft.name as financial_type_name, date(contr.receive_date) as contrib_date, contrib_status.contrib_status_label,
        CASE WHEN soft.id IS NOT NULL
             THEN soft_type.honor_type_label
             ELSE tribute_type.tribute_type_label END as honor_type_label,
        tribute.$t_note as tribute_note,
        CASE WHEN soft.id IS NOT NULL
             THEN con2.sort_name
             ELSE concat(ifnull(tribute.$t_n_last_name, ''), ', ', ifnull(t_n_prefix.t_n_prefix_label, ''), ' ', ifnull(tribute.$t_n_first_name, '')) END as t_n_sort_name,
        CASE WHEN soft.id IS NOT NULL
             THEN con2.display_name
             ELSE concat(ifnull(t_n_prefix.t_n_prefix_label, ''), ' ', ifnull(tribute.$t_n_first_name, ''), ' ', ifnull(tribute.$t_n_last_name, '')) END as t_n_display_name,
        CASE WHEN soft.id IS NOT NULL
             THEN s_email.email
             ELSE tribute.$t_n_email END as t_n_email,
        CASE WHEN soft.id IS NOT NULL THEN s_addr.street_address ELSE tribute.$t_n_street_address END as t_n_street_address,
       CASE WHEN soft.id IS NOT NULL THEN s_addr.city ELSE tribute.$t_n_city END as t_n_city,
       CASE WHEN soft.id IS NOT NULL THEN s_state.name ELSE t_n_state.name END as t_n_state_name, 
       CASE WHEN soft.id IS NOT NULL THEN s_country.name ELSE t_n_country.name END as t_n_country_name, 
       CASE WHEN soft.id IS NOT NULL THEN s_addr.postal_code ELSE tribute.$t_n_postal_code END as t_n_postal_code ";
    }

    $from = $this->from();
    $where = $this->where($includeContactIDs);

    $sql = " SELECT $select
               FROM $from
              WHERE $where ";

      //for only contact ids ignore order.
      if ( !$onlyIDs ) {
          // Define ORDER BY for query in $sort, with default value
          if ( ! empty( $sort ) ) {
              if ( is_string( $sort ) ) {
                  $sql .= " ORDER BY $sort ";
              } else {
                  $sql .= " ORDER BY " . trim( $sort->orderBy() );
              }
          } else {
              $sql .= "";
          }
      }
      
      
      if ( $rowcount > 0 && $offset >= 0 ) {
            $sql .= " LIMIT $offset, $rowcount ";
        }
     //  print "<br><br>sql: ".$sql; 
      return $sql;
      
      
  }

  function from() {
    $tmp_from = "";

    $params = array(
      'version' => 3,
      'sequential' => 1,
      'name' => 'Tribute_Info',
      'extends' => 'Contribution',
    );

    $result = civicrm_api('CustomGroup', 'getsingle', $params);
    $tribute_table_name = $result['table_name'];

    // Tribute_Type
    $params = array(
      'version' => 3,
      'sequential' => 1,
      'name' => 'Tribute_Type',
    );

    $result = civicrm_api('CustomField', 'getsingle', $params);
    $t_type = $result['column_name'];

    // tribute_prefix
    $params = array(
      'version' => 3,
      'sequential' => 1,
      'name' => 'Tribute_Prefix',
    );

    $result = civicrm_api('CustomField', 'getsingle', $params);
    $t_prefix = $result['column_name']; 

    $params = array(
      'version' => 3,
      'sequential' => 1,
      'name' => 'Tribute_Notification_Prefix',
    );

$result = civicrm_api('CustomField', 'getsingle', $params);
$t_n_prefix = $result['column_name']; 

 $params = array(
  'version' => 3,
  'sequential' => 1,
  'name' => 'Tribute_Notification_State_Province',
);

$result = civicrm_api('CustomField', 'getsingle', $params);
   $t_n_state_province_id = $result['column_name']; 
   
    $params = array(
  'version' => 3,
  'sequential' => 1,
  'name' => 'Tribute_Notification_Country',
);

$result = civicrm_api('CustomField', 'getsingle', $params);
   $t_n_country_id = $result['column_name']; 
 
    require_once('utils/Entitlement.php');
    $tmpEntitlement = new Entitlement();
 
     // civicrm_contribution_soft
     $sc_types = "1, 2"; // these are the soft credit type ids for "in memory" and "in honor"

     $tmp_from =  "civicrm_contribution contr 
  			JOIN civicrm_financial_type ft ON contr.financial_type_id = ft.id 
  			JOIN civicrm_contact contact_a on contr.contact_id = contact_a.id and contact_a.is_deleted <> 1
  			JOIN (select ov.value, ov.label as contrib_status_label FROM civicrm_option_value ov 
  			JOIN civicrm_option_group og ON ov.option_group_id = og.id AND og.name = 'contribution_status' ) as contrib_status
  			ON contrib_status.value = contr.contribution_status_id 
  			LEFT JOIN $tribute_table_name tribute ON contr.id = tribute.entity_id
  			LEFT JOIN (select ov.value, ov.label as tribute_type_label FROM civicrm_option_value ov 
  			JOIN civicrm_option_group og ON ov.option_group_id = og.id AND og.name = 'tribute_type_options' ) as tribute_type
  			ON tribute_type.value = tribute.$t_type 
  			LEFT JOIN (select ov.value, ov.label as tribute_prefix_label FROM civicrm_option_value ov 
  			JOIN civicrm_option_group og ON ov.option_group_id = og.id AND og.name = 'tribute_prefix_options' ) as tribute_prefix
  			ON tribute_prefix.value = tribute.$t_prefix 
  			LEFT JOIN (select ov.value, ov.label as t_n_prefix_label FROM civicrm_option_value ov 
  			JOIN civicrm_option_group og ON ov.option_group_id = og.id AND og.name = 'tribute_prefix_options' ) as t_n_prefix
  			ON t_n_prefix.value = tribute.$t_n_prefix 
  			LEFT JOIN civicrm_state_province t_n_state ON t_n_state.id = $t_n_state_province_id
  			LEFT JOIN civicrm_country t_n_country ON t_n_country.id = $t_n_country_id
  			LEFT JOIN civicrm_contribution_soft soft ON soft.contribution_id = contr.id  AND soft.soft_credit_type_id IN ( $sc_types )
  			LEFT JOIN civicrm_contact con2 ON soft.contact_id = con2.id 
  			LEFT JOIN (SELECT ov.value, ov.label as honor_type_label FROM civicrm_option_value ov 
  			          JOIN civicrm_option_group og ON ov.option_group_id = og.id AND og.name = 'soft_credit_type' ) as soft_type 
  			 ON soft_type.value  = soft.soft_credit_type_id 
  			 LEFT JOIN civicrm_email s_email ON s_email.contact_id = soft.contact_id AND s_email.is_primary =1 
  			 LEFT JOIN civicrm_address s_addr ON s_addr.contact_id = soft.contact_id AND s_addr.is_primary = 1
  			 LEFT JOIN civicrm_state_province s_state ON s_state.id = s_addr.state_province_id
  			 LEFT JOIN civicrm_country s_country ON s_country.id = s_addr.country_id ";

/*	
   $clauses[] = "og.name = 'honor_type' ";
    $clauses[] = "og.id = ov.option_group_id";
    $clauses[] = "contr.honor_type_id = ov.value";
  */
      return $tmp_from;
  }

 /*
  * WHERE clause is an array built from any required JOINS plus conditional filters based on search criteria field values
  *
  */
function where( $includeContactIDs = false ) {
  $clauses = array();

  $clauses[] = "contr.is_test <> 1";

  // $clauses[] = "contr.contact_id = contact_a.id";
  // $clauses[] = "contr.honor_contact_id = con2.id";
  // $clauses[] = "contr.honor_contact_id IS NOT NULL";
  // $clauses[] = "contr.financial_type_id = ft.id";
    
  $params = array(
    'version' => 3,
    'sequential' => 1,
    'name' => 'is_tribute_',
  );

  $result = civicrm_api('CustomField', 'getsingle', $params);
  $is_tribute_boolean = $result['column_name'];

  // Tribute_Type
  $params = array(
    'version' => 3,
    'sequential' => 1,
    'name' => 'Tribute_Type',
  );

  $result = civicrm_api('CustomField', 'getsingle', $params);
  $t_type = $result['column_name']; 

  require_once('utils/Entitlement.php');
  $tmpEntitlement = new Entitlement();
 
  $clauses[] = "( soft.contact_id IS NOT NULL OR tribute.$is_tribute_boolean = 1 OR length(tribute.$t_type) > 0 ) ";

    $startDate = CRM_Utils_Date::processDate( $this->_formValues['start_date'] );
     if ( $startDate ) {
         $clauses[] = "date(contr.receive_date) >= date( $startDate )";
     }

     $endDate = CRM_Utils_Date::processDate( $this->_formValues['end_date'] );
     if ( $endDate ) {
         $clauses[] = "date(contr.receive_date) <= date( $endDate ) ";
     }

/*
     $tag = CRM_Utils_Array::value( 'tag', $this->_formValues );
     if ( $tag ) {
         $clauses[] = "civicrm_entity_tag.tag_id = $tag";
         $clauses[] = "civicrm_tag.id = civicrm_entity_tag.tag_id";
     } else {
         $clauses[] = "civicrm_entity_tag.tag_id IS NOT NULL";
     }
*/

     if ($includeContactIDs) {
         $contactIDs = array();
         foreach ($this->_formValues as $id => $value) {
             if ($value && substr( $id, 0, CRM_Core_Form::CB_PREFIX_LEN ) == CRM_Core_Form::CB_PREFIX) {
                 $contactIDs[] = substr( $id, CRM_Core_Form::CB_PREFIX_LEN );
             }
         }

         if ( ! empty( $contactIDs ) ) {
                $contactIDs = implode( ', ', $contactIDs );
                $clauses[] = "contact_a.id IN ( $contactIDs )";
            }
        }
        return implode( ' AND ', $clauses );
    }

  function alterRow(&$row) {
    $params = array(
      'version' => 3,
      'sequential' => 1,
      'contact_id' => $row['contact_id'],
    );

    $result = civicrm_api('JointGreetings', 'getsingle', $params);
    $row['joint_greeting'] = $result['greetings.joint_casual'];
  }

  /**
   * Functions below generally don't need to be modified
   */
  function count() {
    $sql = $this->all();
    $dao = CRM_Core_DAO::executeQuery($sql);
    return $dao->N;
  }

  function contactIDs( $offset = 0, $rowcount = 0, $sort = null) {
    return $this->all( $offset, $rowcount, $sort, false, true );
  }

  function &columns() {
    return $this->_columns;
  }

  function setTitle( $title ) {
    if ($title) {
      CRM_Utils_System::setTitle($title);
    }
    else {
      CRM_Utils_System::setTitle(ts('Search'));
    }
  }

  function summary( ) {
    return null;
  }

}
